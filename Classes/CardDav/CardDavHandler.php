<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Contact Book.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\ContactBook\CardDav;

use DOMDocument;
use DOMXPath;
use PSBits\ContactBook\CardDav\Dto\AuthenticationResult;
use PSBits\ContactBook\CardDav\Dto\CardDavResource;
use PSBits\ContactBook\CardDav\Dto\CardDavResourceType;
use PSBits\ContactBook\CardDav\Dto\SyncTokenState;
use PSBits\ContactBook\Domain\Model\Contact;
use PSBits\ContactBook\Domain\Model\ContactBook;
use PSBits\ContactBook\Domain\Repository\ContactBookRepository;
use PSBits\ContactBook\Domain\Repository\ContactRepository;
use PSBits\ContactBook\Service\Enum\VCardVersion;
use PSBits\ContactBook\Service\ImportService;
use PSBits\ContactBook\Service\VCardService;
use PSBits\Foundation\Utility\Xml\XmlUtility;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use TYPO3\CMS\Core\Crypto\PasswordHashing\PasswordHashFactory;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException;
use TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;

use function array_filter;
use function array_key_exists;
use function array_map;
use function array_values;
use function base64_decode;
use function count;
use function ctype_digit;
use function explode;
use function hash;
use function htmlspecialchars;
use function implode;
use function in_array;
use function is_array;
use function is_string;
use function max;
use function preg_match;
use function preg_match_all;
use function preg_quote;
use function preg_replace;
use function sprintf;
use function str_contains;
use function str_starts_with;
use function strtolower;
use function strtoupper;
use function substr;
use function time;
use function trim;
use function urlencode;

readonly class CardDavHandler
{
    private const string AUTHENTICATE_HEADER = 'Basic realm="ContactBook CardDAV"';

    private const array CARDDAV_METHODS = [
        'DELETE',
        'GET',
        'OPTIONS',
        'PROPFIND',
        'PUT',
        'REPORT',
    ];

    private const string DAV_HEADER = '1, 2, addressbook, extended-mkcol, sync-collection';
    private const string HTTP_STATUS_OK = 'HTTP/1.1 200 OK';

    public function __construct(
        private ConnectionPool $connectionPool,
        private ContactBookRepository $contactBookRepository,
        private ContactRepository $contactRepository,
        private ImportService $importService,
        private PersistenceManager $persistenceManager,
        private LoggerInterface $logger,
        private ResponseFactoryInterface $responseFactory,
        private StreamFactoryInterface $streamFactory,
        private VCardService $vCardService,
    ) {
    }

    public function handle(ServerRequestInterface $request): ?ResponseInterface
    {
        $path = $this->normalizePath($request->getUri()->getPath());

        if ('/.well-known/carddav' === $path) {
            // Discovery endpoints are commonly probed by clients before they know the actual DAV base path.
            return $this->redirectResponse('/carddav/');
        }

        if (!str_starts_with($path, '/carddav')) {
            return null;
        }

        if (!$this->isHttpsRequest($request)) {
            return $this->textResponse(403, 'CardDAV requires HTTPS.');
        }

        $method = strtoupper($request->getMethod());

        if (!in_array($method, self::CARDDAV_METHODS, true)) {
            return $this->textResponse(405, 'Method not allowed.', [
                'Allow' => implode(', ', self::CARDDAV_METHODS),
                'DAV'   => self::DAV_HEADER,
            ]);
        }

        if ('OPTIONS' === $method) {
            return $this->textResponse(200, '', [
                'Allow' => implode(', ', self::CARDDAV_METHODS),
                'DAV'   => self::DAV_HEADER,
            ]);
        }

        $authenticationResult = $this->authenticate($request);

        if ($authenticationResult->getResponse() instanceof ResponseInterface) {
            return $authenticationResult->getResponse();
        }

        $username = $authenticationResult->getUsername();
        $frontendUser = $authenticationResult->getUser();

        if (!is_array($frontendUser) || !isset($frontendUser['uid'])) {
            return $this->textResponse(401, 'Invalid credentials.');
        }

        $contactBookUid = $this->resolveOwnedContactBookUid((int)$frontendUser['uid']);

        if (null === $contactBookUid) {
            return $this->textResponse(403, 'No accessible contact book found.');
        }

        if ('PROPFIND' === $method) {
            return $this->handlePropfind($request, $path, $username, $contactBookUid);
        }

        if ('REPORT' === $method) {
            return $this->handleReport($request, $path, $username, $contactBookUid);
        }

        if ('GET' === $method) {
            return $this->handleGet($request, $path, $username, $contactBookUid);
        }

        if ('PUT' === $method) {
            return $this->handlePut($request, $path, $username, $contactBookUid);
        }

        return $this->handleDelete($request, $path, $username, $contactBookUid);
    }

    private function authenticate(ServerRequestInterface $request): AuthenticationResult
    {
        $authorizationHeader = $request->getHeaderLine('Authorization');

        if (!str_starts_with(strtolower($authorizationHeader), 'basic ')) {
            return $this->deniedAuthenticationResult('Authentication required.');
        }

        $decodedCredentials = base64_decode(substr($authorizationHeader, 6), true);

        if (!is_string($decodedCredentials) || !str_contains($decodedCredentials, ':')) {
            return $this->deniedAuthenticationResult('Invalid authentication.');
        }

        [
            $username,
            $password,
        ] = explode(':', $decodedCredentials, 2);

        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('fe_users');
        $user = $queryBuilder->select('uid', 'username', 'password', 'disable', 'deleted')
            ->from('fe_users')
            ->where(
                $queryBuilder->expr()->eq('username', $queryBuilder->createNamedParameter($username))
            )
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchAssociative();

        if (!is_array($user) || (int)$user['deleted'] || (int)$user['disable']) {
            $this->logger->warning('CardDAV authentication failed for unknown or disabled user.', ['username' => $username]);

            return $this->deniedAuthenticationResult('Invalid credentials.');
        }

        $hashFactory = GeneralUtility::makeInstance(PasswordHashFactory::class);
        $hashInstance = $hashFactory->get((string)$user['password'], 'FE');

        if (!$hashInstance->checkPassword($password, (string)$user['password'])) {
            $this->logger->warning('CardDAV authentication failed.', ['username' => $username]);

            return $this->deniedAuthenticationResult('Invalid credentials.');
        }

        return new AuthenticationResult(
            response: null,
            username: $username,
            user: $user,
        );
    }

    private function handleDelete(ServerRequestInterface $request, string $path, string $username, int $contactBookUid): ResponseInterface
    {
        $resource = $this->parseResource($path, $username);

        if (!$resource instanceof CardDavResource || CardDavResourceType::Card !== $resource->getType()) {
            return $this->textResponse(404, 'Resource not found.');
        }

        $contactUuid = (string)$resource->getUuid();
        $contact = $this->contactRepository->findByContactBookAndUuid($contactBookUid, $contactUuid);

        if (!$contact instanceof Contact) {
            return $this->textResponse(404, 'Contact not found.');
        }

        $ifMatch = trim($request->getHeaderLine('If-Match'));

        if ('' !== $ifMatch && '*' !== $ifMatch) {
            $currentEtag = $this->getContactEtag($contactBookUid, $contactUuid);

            if ($ifMatch !== $currentEtag) {
                return $this->textResponse(412, 'ETag mismatch.');
            }
        }

        try {
            $this->insertTombstone($contactBookUid, $contactUuid);
            $this->contactRepository->remove($contact);
            $this->persistenceManager->persistAll();
        } catch (IllegalObjectTypeException|UnknownObjectException $exception) {
            throw new RuntimeException($exception->getMessage(), (int)$exception->getCode(), $exception);
        }

        return $this->textResponse(204, '');
    }

    private function handleGet(
        ServerRequestInterface $request,
        string $path,
        string $username,
        int $contactBookUid,
    ): ResponseInterface {
        $resource = $this->parseResource($path, $username);

        if (!$resource instanceof CardDavResource) {
            return $this->textResponse(404, 'Resource not found.');
        }

        if (in_array($resource->getType(), [CardDavResourceType::Home, CardDavResourceType::Principal, CardDavResourceType::Root], true)) {
            return $this->textResponse(200, 'CardDAV endpoint available.');
        }

        if (CardDavResourceType::Collection === $resource->getType()) {
            $href = $this->collectionHref($username);

            return $this->multistatusResponse([
                $this->buildPropResponse(
                    $href,
                    [
                        'cs:getctag' => $this->xmlEscape($this->getCollectionToken($contactBookUid)),
                        'd:displayname' => 'Contacts',
                    ]
                ),
            ]);
        }

        $contact = $this->contactRepository->findByContactBookAndUuid($contactBookUid, (string)$resource->getUuid());

        if (!$contact instanceof Contact) {
            return $this->textResponse(404, 'Contact not found.');
        }

        $vCard = $this->vCardService->getVCard($contact, $this->detectVCardVersion($request->getHeaderLine('User-Agent')));
        $etag = $this->getContactEtag($contactBookUid, (string)$resource->getUuid());

        return $this->bodyResponse(200, 'text/vcard; charset=utf-8', $vCard, [
            'ETag' => $etag,
        ]);
    }

    private function handlePropfind(ServerRequestInterface $request, string $path, string $username, int $contactBookUid): ResponseInterface
    {
        $resource = $this->parseResource($path, $username);

        if (!$resource instanceof CardDavResource) {
            return $this->textResponse(404, 'Resource not found.');
        }

        $depth = (int)($request->getHeaderLine('Depth') ?: '0');
        $responses = [];

        if (CardDavResourceType::Root === $resource->getType()) {
            $responses[] = $this->buildPropResponse(
                '/carddav/',
                [
                    'd:current-user-principal' => [
                        'd:href' => $this->xmlNode($this->xmlEscape($this->principalHref($username)), 0),
                    ],
                    'd:resourcetype' => [
                        'd:collection' => $this->xmlEmptyNode(0),
                    ],
                ]
            );

            if (0 < $depth) {
                $responses[] = $this->buildPropResponse(
                    $this->principalHref($username),
                    [
                        'card:addressbook-home-set' => [
                            'd:href' => $this->xmlNode($this->xmlEscape($this->addressBookHomeHref($username)), 0),
                        ],
                        'd:resourcetype' => [
                            'd:principal' => $this->xmlEmptyNode(0),
                        ],
                    ]
                );
            }
        } elseif (CardDavResourceType::Principal === $resource->getType()) {
            $responses[] = $this->buildPropResponse(
                $this->principalHref($username),
                [
                    'card:addressbook-home-set' => [
                        'd:href' => $this->xmlNode($this->xmlEscape($this->addressBookHomeHref($username)), 0),
                    ],
                    'd:displayname' => $this->xmlEscape($username),
                    'd:resourcetype' => [
                        'd:principal' => $this->xmlEmptyNode(0),
                    ],
                ]
            );
        } elseif (CardDavResourceType::Home === $resource->getType()) {
            $responses[] = $this->buildPropResponse(
                $this->addressBookHomeHref($username),
                [
                    'd:resourcetype' => [
                        'd:collection' => $this->xmlEmptyNode(0),
                    ],
                ]
            );

            if (0 < $depth) {
                $responses[] = $this->buildPropResponse(
                    $this->collectionHref($username),
                    [
                        'd:displayname' => 'Contacts',
                        'd:resourcetype' => [
                            'card:addressbook' => $this->xmlEmptyNode(0),
                            'd:collection' => $this->xmlEmptyNode(1),
                        ],
                    ]
                );
            }
        } elseif (CardDavResourceType::Collection === $resource->getType()) {
            $responses[] = $this->buildPropResponse(
                $this->collectionHref($username),
                [
                    'card:supported-address-data' => [
                        'card:address-data-type' => [
                            XmlUtility::SPECIAL_ARRAY_KEYS['ATTRIBUTES'] => [
                                'content-type' => 'text/vcard',
                                'version' => '3.0',
                            ],
                            XmlUtility::SPECIAL_ARRAY_KEYS['NODE_VALUE'] => '',
                            XmlUtility::SPECIAL_ARRAY_KEYS['POSITION'] => 0,
                        ],
                    ],
                    'cs:getctag' => $this->xmlEscape($this->getCollectionToken($contactBookUid)),
                    'd:displayname' => 'Contacts',
                    'd:resourcetype' => [
                        'card:addressbook' => $this->xmlEmptyNode(0),
                        'd:collection' => $this->xmlEmptyNode(1),
                    ],
                    'd:sync-token' => $this->xmlEscape($this->getCollectionToken($contactBookUid)),
                ]
            );

            if (0 < $depth) {
                foreach ($this->getContactRows($contactBookUid) as $contactRow) {
                    $uuid = (string)$contactRow['uuid'];
                    $responses[] = $this->buildPropResponse(
                        $this->cardHref($username, $uuid),
                        [
                            'd:getcontenttype' => 'text/vcard; charset=utf-8',
                            'd:getetag' => $this->xmlEscape($this->getContactEtag($contactBookUid, $uuid, (int)$contactRow['tstamp'])),
                            'd:resourcetype' => $this->xmlEmptyNode(0),
                        ]
                    );
                }
            }
        } else {
            $responses[] = $this->buildPropResponse(
                $this->cardHref($username, (string)$resource->getUuid()),
                [
                    'd:getcontenttype' => 'text/vcard; charset=utf-8',
                    'd:getetag' => $this->xmlEscape($this->getContactEtag($contactBookUid, (string)$resource->getUuid())),
                    'd:resourcetype' => $this->xmlEmptyNode(0),
                ]
            );
        }

        return $this->multistatusResponse($responses);
    }

    private function handlePut(ServerRequestInterface $request, string $path, string $username, int $contactBookUid): ResponseInterface
    {
        $resource = $this->parseResource($path, $username);

        if (!$resource instanceof CardDavResource || CardDavResourceType::Card !== $resource->getType()) {
            return $this->textResponse(404, 'Resource not found.');
        }

        $contactUuid = (string)$resource->getUuid();
        $existingContact = $this->contactRepository->findByContactBookAndUuid($contactBookUid, $contactUuid);
        $ifMatch = trim($request->getHeaderLine('If-Match'));
        $ifNoneMatch = trim($request->getHeaderLine('If-None-Match'));

        if ('*' === $ifNoneMatch && $existingContact instanceof Contact) {
            return $this->textResponse(412, 'Resource already exists.');
        }

        if ('' !== $ifMatch && '*' !== $ifMatch && $existingContact instanceof Contact) {
            $currentEtag = $this->getContactEtag($contactBookUid, $contactUuid);

            if ($ifMatch !== $currentEtag) {
                return $this->textResponse(412, 'ETag mismatch.');
            }
        }

        $content = (string)$request->getBody();

        if ('' === trim($content)) {
            return $this->textResponse(400, 'Empty vCard payload.');
        }

        $contactBook = $this->findContactBookByUid($contactBookUid);

        if (!$contactBook instanceof ContactBook) {
            return $this->textResponse(500, 'Contact book not found.');
        }

        $content = $this->forceUid($content, $contactUuid);

        try {
            $this->importService->upsertSingleVCardContent($content, $contactBook);
        } catch (IllegalObjectTypeException|UnknownObjectException $exception) {
            $this->logger->error('CardDAV PUT failed.', ['exception' => $exception]);

            return $this->textResponse(400, 'Invalid vCard payload.');
        }

        $etag = $this->getContactEtag($contactBookUid, $contactUuid);
        $statusCode = $existingContact instanceof Contact ? 204 : 201;

        return $this->textResponse($statusCode, '', [
            'ETag' => $etag,
        ]);
    }

    private function handleReport(ServerRequestInterface $request, string $path, string $username, int $contactBookUid): ResponseInterface
    {
        $resource = $this->parseResource($path, $username);

        if (!$resource instanceof CardDavResource || CardDavResourceType::Collection !== $resource->getType()) {
            return $this->textResponse(404, 'Collection not found.');
        }

        $payload = (string)$request->getBody();

        if (str_contains($payload, 'sync-collection')) {
            return $this->handleSyncCollectionReport($payload, $username, $contactBookUid, $request);
        }

        $requestedUuids = $this->extractRequestedUuids($payload);
        $contactRows = $this->getContactRows($contactBookUid);

        if ([] !== $requestedUuids) {
            $contactRows = array_values(array_filter(
                $contactRows,
                static fn(array $row): bool => in_array((string)$row['uuid'], $requestedUuids, true)
            ));
        }

        $responses = [];
        $this->appendAddressDataResponsesFromContactRows($responses, $contactRows, $username, $request->getHeaderLine('User-Agent'), $contactBookUid);

        return $this->multistatusResponse($responses);
    }

    private function handleSyncCollectionReport(
        string $payload,
        string $username,
        int $contactBookUid,
        ServerRequestInterface $request,
    ): ResponseInterface {
        $previousToken = $this->extractSyncToken($payload);
        $currentToken = $this->getCollectionToken($contactBookUid);
        $changes = $this->getChangedContacts($contactBookUid, $previousToken);
        $deletedUuids = $this->getDeletedUuids($contactBookUid, $previousToken);
        $responses = [];

        $this->appendAddressDataResponsesFromContactRows($responses, $changes, $username, $request->getHeaderLine('User-Agent'), $contactBookUid);

        foreach ($deletedUuids as $deletedUuid) {
            $responses[] = $this->buildNotFoundResponse($this->cardHref($username, $deletedUuid));
        }

        return $this->multistatusResponse($responses, $currentToken);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildAddressDataResponse(string $href, string $vCard, string $etag): array
    {
        return [
            'd:href' => $this->xmlNode($this->xmlEscape($href), 0),
            'd:propstat' => [
                'd:prop' => [
                    'card:address-data' => $this->xmlNode($this->xmlEscape($vCard), 0),
                    'd:getetag' => $this->xmlNode($this->xmlEscape($etag), 1),
                    XmlUtility::SPECIAL_ARRAY_KEYS['POSITION'] => 0,
                ],
                'd:status' => $this->xmlNode(self::HTTP_STATUS_OK, 1),
                XmlUtility::SPECIAL_ARRAY_KEYS['POSITION'] => 1,
            ],
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function getChangedContacts(int $contactBookUid, ?string $previousToken): array
    {
        if (null === $previousToken || '' === $previousToken) {
            return $this->getContactRows($contactBookUid);
        }

        $syncTokenState = $this->decodeToken($previousToken);
        $minTimestamp = max($syncTokenState->getMaxContactTimestamp(), $syncTokenState->getMaxTombstoneTimestamp());

        if (0 >= $minTimestamp) {
            return $this->getContactRows($contactBookUid);
        }

        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tx_contactbook_domain_model_contact');

        return $queryBuilder->select('uid', 'uuid', 'tstamp')
            ->from('tx_contactbook_domain_model_contact')
            ->where(
                $queryBuilder->expr()->eq('contact_book', $queryBuilder->createNamedParameter($contactBookUid)),
                $queryBuilder->expr()->eq('deleted', $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)),
                $queryBuilder->expr()->gt('tstamp', $queryBuilder->createNamedParameter($minTimestamp, \PDO::PARAM_INT))
            )
            ->orderBy('uid')
            ->executeQuery()
            ->fetchAllAssociative();
    }

    /** @return string[] */
    private function getDeletedUuids(int $contactBookUid, ?string $previousToken): array
    {
        if (null === $previousToken || '' === $previousToken) {
            return [];
        }

        $syncTokenState = $this->decodeToken($previousToken);
        $minTimestamp = max($syncTokenState->getMaxContactTimestamp(), $syncTokenState->getMaxTombstoneTimestamp());

        if (0 >= $minTimestamp) {
            return [];
        }

        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tx_contactbook_carddav_tombstone');

        return array_map(
            static fn(array $row): string => (string)$row['contact_uuid'],
            $queryBuilder->select('contact_uuid')
                ->from('tx_contactbook_carddav_tombstone')
                ->where(
                    $queryBuilder->expr()->eq('contact_book', $queryBuilder->createNamedParameter($contactBookUid)),
                    $queryBuilder->expr()->eq('deleted', $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)),
                    $queryBuilder->expr()->gt('tstamp', $queryBuilder->createNamedParameter($minTimestamp, \PDO::PARAM_INT))
                )
                ->orderBy('uid')
                ->executeQuery()
                ->fetchAllAssociative()
        );
    }

    private function decodeToken(string $token): SyncTokenState
    {
        if (!preg_match('/^(\d+):(\d+):/u', $token, $matches)) {
            return new SyncTokenState(0, 0);
        }

        if (!array_key_exists(1, $matches) || !array_key_exists(2, $matches) || !ctype_digit($matches[1]) || !ctype_digit($matches[2])) {
            return new SyncTokenState(0, 0);
        }

        return new SyncTokenState((int)$matches[1], (int)$matches[2]);
    }

    private function getCollectionToken(int $contactBookUid): string
    {
        // The token combines contact and tombstone state so clients can detect both updates and deletions.
        $contactQueryBuilder = $this->connectionPool->getQueryBuilderForTable('tx_contactbook_domain_model_contact');
        $contactStats = $contactQueryBuilder->selectLiteral('MAX(tstamp) AS max_tstamp', 'COUNT(uid) AS total')
            ->from('tx_contactbook_domain_model_contact')
            ->where(
                $contactQueryBuilder->expr()->eq('contact_book', $contactQueryBuilder->createNamedParameter($contactBookUid)),
                $contactQueryBuilder->expr()->eq('deleted', $contactQueryBuilder->createNamedParameter(0, \PDO::PARAM_INT))
            )
            ->executeQuery()
            ->fetchAssociative() ?: [];

        $tombstoneQueryBuilder = $this->connectionPool->getQueryBuilderForTable('tx_contactbook_carddav_tombstone');
        $tombstoneStats = $tombstoneQueryBuilder->selectLiteral('MAX(tstamp) AS max_tstamp', 'COUNT(uid) AS total')
            ->from('tx_contactbook_carddav_tombstone')
            ->where(
                $tombstoneQueryBuilder->expr()->eq('contact_book', $tombstoneQueryBuilder->createNamedParameter($contactBookUid)),
                $tombstoneQueryBuilder->expr()->eq('deleted', $tombstoneQueryBuilder->createNamedParameter(0, \PDO::PARAM_INT))
            )
            ->executeQuery()
            ->fetchAssociative() ?: [];

        $maxContactTimestamp = (int)($contactStats['max_tstamp'] ?? 0);
        $contactTotal = (int)($contactStats['total'] ?? 0);
        $maxTombstoneTimestamp = (int)($tombstoneStats['max_tstamp'] ?? 0);
        $tombstoneTotal = (int)($tombstoneStats['total'] ?? 0);
        $fingerprint = hash('sha256', implode(':', [$contactBookUid, $maxContactTimestamp, $contactTotal, $maxTombstoneTimestamp, $tombstoneTotal]));

        return sprintf('%d:%d:%s', $maxContactTimestamp, $maxTombstoneTimestamp, $fingerprint);
    }

    private function getContactEtag(int $contactBookUid, string $uuid, ?int $timestamp = null): string
    {
        if (null === $timestamp) {
            $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tx_contactbook_domain_model_contact');
            $row = $queryBuilder->select('tstamp')
                ->from('tx_contactbook_domain_model_contact')
                ->where(
                    $queryBuilder->expr()->eq('contact_book', $queryBuilder->createNamedParameter($contactBookUid)),
                    $queryBuilder->expr()->eq('uuid', $queryBuilder->createNamedParameter($uuid)),
                    $queryBuilder->expr()->eq('deleted', $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT))
                )
                ->setMaxResults(1)
                ->executeQuery()
                ->fetchAssociative();
            $timestamp = (int)($row['tstamp'] ?? 0);
        }

        return '"' . hash('sha256', $uuid . ':' . $timestamp) . '"';
    }

    /** @return array<int, array<string, mixed>> */
    private function getContactRows(int $contactBookUid): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tx_contactbook_domain_model_contact');

        return $queryBuilder->select('uid', 'uuid', 'tstamp')
            ->from('tx_contactbook_domain_model_contact')
            ->where(
                $queryBuilder->expr()->eq('contact_book', $queryBuilder->createNamedParameter($contactBookUid)),
                $queryBuilder->expr()->eq('deleted', $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT))
            )
            ->orderBy('uid')
            ->executeQuery()
            ->fetchAllAssociative();
    }

    /** @return string[] */
    private function extractRequestedUuids(string $payload): array
    {
        $matches = [];
        preg_match_all('#/default/([^/]+)\.vcf#u', $payload, $matches);

        return $matches[1] ?? [];
    }

    private function extractSyncToken(string $payload): ?string
    {
        $document = new DOMDocument();

        if (!@$document->loadXML($payload)) {
            return null;
        }

        $xpath = new DOMXPath($document);
        $syncToken = $xpath->evaluate('string(//*[local-name()="sync-token"])');

        return '' === trim($syncToken) ? null : trim($syncToken);
    }

    /**
     * @param array<string, array<string, mixed>|string> $properties
     *
     * @return array<string, mixed>
     */
    private function buildPropResponse(string $href, array $properties): array
    {
        $position = 0;
        $prop = [XmlUtility::SPECIAL_ARRAY_KEYS['POSITION'] => 0];

        foreach ($properties as $propertyName => $propertyValue) {
            if (is_string($propertyValue)) {
                $prop[$propertyName] = $this->xmlNode($propertyValue, $position++);

                continue;
            }

            if (!isset($propertyValue[XmlUtility::SPECIAL_ARRAY_KEYS['POSITION']])) {
                $propertyValue[XmlUtility::SPECIAL_ARRAY_KEYS['POSITION']] = $position;
            }

            $prop[$propertyName] = $propertyValue;
            $position++;
        }

        return [
            'd:href' => $this->xmlNode($this->xmlEscape($href), 0),
            'd:propstat' => [
                'd:prop' => $prop,
                'd:status' => $this->xmlNode(self::HTTP_STATUS_OK, 1),
                XmlUtility::SPECIAL_ARRAY_KEYS['POSITION'] => 1,
            ],
        ];
    }

    private function addressBookHomeHref(string $username): string
    {
        return '/carddav/addressbooks/' . urlencode($username) . '/';
    }

    private function cardHref(string $username, string $uuid): string
    {
        return $this->collectionHref($username) . urlencode($uuid) . '.vcf';
    }

    private function collectionHref(string $username): string
    {
        return '/carddav/addressbooks/' . urlencode($username) . '/default/';
    }

    private function detectVCardVersion(string $userAgent): VCardVersion
    {
        $userAgent = strtolower($userAgent);

        if (str_contains($userAgent, 'iphone') || str_contains($userAgent, 'ipad') || str_contains($userAgent, 'mac os x')) {
            return VCardVersion::Apple;
        }

        return VCardVersion::V3_0;
    }

    private function findContactBookByUid(int $contactBookUid): ?ContactBook
    {
        return $this->contactBookRepository->findByUidWithoutStoragePage($contactBookUid);
    }

    private function forceUid(string $content, string $uuid): string
    {
        // CardDAV object URLs and the card UID must stay in sync, otherwise clients create duplicates.
        if (preg_match('/^UID:/mi', $content)) {
            return (string)preg_replace('/^UID:.*$/mi', 'UID:' . $uuid, $content);
        }

        return str_replace('END:VCARD', 'UID:' . $uuid . "\r\nEND:VCARD", $content);
    }

    private function insertTombstone(int $contactBookUid, string $uuid): void
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tx_contactbook_carddav_tombstone');
        $queryBuilder->insert('tx_contactbook_carddav_tombstone')
            ->values([
                'contact_book' => $contactBookUid,
                'contact_uuid' => $uuid,
                'crdate'       => $GLOBALS['EXEC_TIME'] ?? time(),
                'tstamp'       => $GLOBALS['EXEC_TIME'] ?? time(),
                'deleted'      => 0,
                'pid'          => 0,
            ])
            ->executeStatement();
    }

    private function isHttpsRequest(ServerRequestInterface $request): bool
    {
        if ('https' === strtolower($request->getUri()->getScheme())) {
            return true;
        }

        return 'https' === strtolower($request->getHeaderLine('X-Forwarded-Proto'));
    }

    private function normalizePath(string $path): string
    {
        if ('/' === $path || '' === $path) {
            return '/';
        }

        return '/' . trim($path, '/');
    }

    private function parseResource(string $path, string $username): ?CardDavResource
    {
        $encodedUsername = urlencode($username);

        if ('/carddav' === $path) {
            return new CardDavResource(CardDavResourceType::Root);
        }

        if ('/carddav/principals/' . $encodedUsername === $path) {
            return new CardDavResource(CardDavResourceType::Principal);
        }

        if ('/carddav/addressbooks/' . $encodedUsername === $path) {
            return new CardDavResource(CardDavResourceType::Home);
        }

        if ('/carddav/addressbooks/' . $encodedUsername . '/default' === $path) {
            return new CardDavResource(CardDavResourceType::Collection);
        }

        if (preg_match('#^/carddav/addressbooks/' . preg_quote($encodedUsername, '#') . '/default/([^/]+)\.vcf$#u', $path, $matches)) {
            return new CardDavResource(
                type: CardDavResourceType::Card,
                uuid: $matches[1],
            );
        }

        return null;
    }

    private function principalHref(string $username): string
    {
        return '/carddav/principals/' . urlencode($username) . '/';
    }

    private function deniedAuthenticationResult(string $message): AuthenticationResult
    {
        return new AuthenticationResult(
            response: $this->textResponse(401, $message, [
                'WWW-Authenticate' => self::AUTHENTICATE_HEADER,
            ]),
        );
    }

    /**
     * @param array<int, array<string, mixed>> $responses
     * @param array<int, array<string, mixed>> $contactRows
     */
    private function appendAddressDataResponsesFromContactRows(
        array &$responses,
        array $contactRows,
        string $username,
        string $userAgent,
        int $contactBookUid,
    ): void {
        $vCardVersion = $this->detectVCardVersion($userAgent);

        foreach ($contactRows as $contactRow) {
            $uuid = (string)$contactRow['uuid'];
            $contact = $this->contactRepository->findByContactBookAndUuid($contactBookUid, $uuid);

            if (!$contact instanceof Contact) {
                continue;
            }

            $responses[] = $this->buildAddressDataResponse(
                $this->cardHref($username, $uuid),
                $this->vCardService->getVCard($contact, $vCardVersion),
                $this->getContactEtag($contactBookUid, $uuid, (int)$contactRow['tstamp'])
            );
        }
    }

    /**
     * @param array<int, array<string, mixed>> $responses
     */
    private function multistatusResponse(array $responses, ?string $syncToken = null): ResponseInterface
    {
        foreach ($responses as $position => &$response) {
            if (!isset($response[XmlUtility::SPECIAL_ARRAY_KEYS['POSITION']])) {
                $response[XmlUtility::SPECIAL_ARRAY_KEYS['POSITION']] = $position;
            }
        }
        unset($response);

        $multistatusNode = [
            'd:response' => $responses,
            XmlUtility::SPECIAL_ARRAY_KEYS['NAMESPACES'] => [
                'card' => 'urn:ietf:params:xml:ns:carddav',
                'cs' => 'http://calendarserver.org/ns/',
                'd' => 'DAV:',
            ],
        ];

        if (null !== $syncToken) {
            $multistatusNode['d:sync-token'] = $this->xmlNode($this->xmlEscape($syncToken), count($responses));
        }

        $xml = XmlUtility::convertToXml(
            ['d:multistatus' => $multistatusNode],
            XmlUtility::XML_HEADER,
            false
        );

        return $this->xmlResponse(207, $xml);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildNotFoundResponse(string $href): array
    {
        return [
            'd:href' => $this->xmlNode($this->xmlEscape($href), 0),
            'd:status' => $this->xmlNode('HTTP/1.1 404 Not Found', 1),
        ];
    }

    /**
     * @return array<string, int|string>
     */
    private function xmlEmptyNode(int $position): array
    {
        return [
            XmlUtility::SPECIAL_ARRAY_KEYS['NODE_VALUE'] => '',
            XmlUtility::SPECIAL_ARRAY_KEYS['POSITION'] => $position,
        ];
    }

    /**
     * @return array<string, int|string>
     */
    private function xmlNode(string $value, int $position): array
    {
        return [
            XmlUtility::SPECIAL_ARRAY_KEYS['NODE_VALUE'] => $value,
            XmlUtility::SPECIAL_ARRAY_KEYS['POSITION'] => $position,
        ];
    }

    private function redirectResponse(string $location): ResponseInterface
    {
        $response = $this->responseFactory->createResponse(308);

        return $response->withHeader('Location', $location);
    }

    private function resolveOwnedContactBookUid(int $frontendUserUid): ?int
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tx_contactbook_contactbooks_feusers');
        $row = $queryBuilder->select('uid_local')
            ->from('tx_contactbook_contactbooks_feusers')
            ->where(
                $queryBuilder->expr()->eq('uid_foreign', $queryBuilder->createNamedParameter($frontendUserUid))
            )
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchAssociative();

        return is_array($row) ? (int)$row['uid_local'] : null;
    }

    private function xmlEscape(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_COMPAT, 'UTF-8');
    }

    private function bodyResponse(int $statusCode, string $contentType, string $body, array $headers = []): ResponseInterface
    {
        $response = $this->responseFactory->createResponse($statusCode)
            ->withHeader('Content-Type', $contentType)
            ->withHeader('DAV', self::DAV_HEADER);

        foreach ($headers as $name => $value) {
            $response = $response->withHeader($name, $value);
        }

        $stream = $this->streamFactory->createStream($body);

        return $response->withBody($stream);
    }

    private function textResponse(int $statusCode, string $message, array $headers = []): ResponseInterface
    {
        return $this->bodyResponse($statusCode, 'text/plain; charset=utf-8', $message, $headers);
    }

    private function xmlResponse(int $statusCode, string $xml): ResponseInterface
    {
        return $this->bodyResponse($statusCode, 'application/xml; charset=utf-8', $xml);
    }
}
