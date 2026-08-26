<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Contact Book.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\ContactBook\CardDav;

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\ParameterType;
use DOMDocument;
use DOMXPath;
use PSBits\ContactBook\CardDav\Dto\AuthenticationResult;
use PSBits\ContactBook\CardDav\Dto\CardDavResource;
use PSBits\ContactBook\CardDav\Dto\CardDavResourceType;
use PSBits\ContactBook\CardDav\Dto\SyncTokenState;
use PSBits\ContactBook\Domain\Demand\ContactBookDemand;
use PSBits\ContactBook\Domain\Model\Contact;
use PSBits\ContactBook\Domain\Model\ContactBook;
use PSBits\ContactBook\Domain\Repository\ContactBookRepository;
use PSBits\ContactBook\Domain\Repository\ContactRepository;
use PSBits\ContactBook\Service\Enum\VCardVersion;
use PSBits\ContactBook\Service\ImportService;
use PSBits\ContactBook\Service\VCardService;
use PSBits\Foundation\Utility\Xml\XmlUtility;
use PSBits\FrontendUsers\Domain\Demand\FrontendUserDemand;
use PSBits\FrontendUsers\Domain\Model\Typo3\FrontendUser;
use PSBits\FrontendUsers\Domain\Repository\Typo3\FrontendUserRepository;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use TYPO3\CMS\Core\Crypto\PasswordHashing\InvalidPasswordHashException;
use TYPO3\CMS\Core\Crypto\PasswordHashing\PasswordHashFactory;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException;
use TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException;
use TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;

use function array_filter;
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

/**
 * Class CardDavHandler
 *
 * @package PSBits\ContactBook\CardDav
 */
readonly class CardDavHandler
{
    private const string AUTHENTICATE_HEADER = 'Basic realm="ContactBook CardDAV"';
    private const array  CARDDAV_METHODS     = [
        'DELETE',
        'GET',
        'OPTIONS',
        'PROPFIND',
        'PUT',
        'REPORT',
    ];
    private const string DAV_HEADER     = '1, 2, addressbook, extended-mkcol, sync-collection';
    private const string HTTP_STATUS_OK = 'HTTP/1.1 200 OK';

    public function __construct(
        private ConnectionPool           $connectionPool,
        private ContactBookRepository    $contactBookRepository,
        private ContactRepository        $contactRepository,
        private FrontendUserRepository   $frontendUserRepository,
        private ImportService            $importService,
        private PersistenceManager       $persistenceManager,
        private LoggerInterface          $logger,
        private ResponseFactoryInterface $responseFactory,
        private StreamFactoryInterface   $streamFactory,
        private VCardService             $vCardService,
    ) {
    }

    /**
     * Handles the complete CardDAV request lifecycle from routing to method-specific processing.
     *
     * @throws InvalidPasswordHashException
     * @throws InvalidQueryException
     * @throws Exception
     */
    public function handle(ServerRequestInterface $request): ?ResponseInterface
    {
        $path = $this->normalizePath(
            $request->getUri()
                ->getPath()
        );

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
            ]);
        }

        if ('OPTIONS' === $method) {
            return $this->textResponse(200, '', [
                'Allow' => implode(', ', self::CARDDAV_METHODS),
            ]);
        }

        $authenticationResult = $this->authenticate($request);

        if ($authenticationResult->getResponse() instanceof ResponseInterface) {
            return $authenticationResult->getResponse();
        }

        $username     = $authenticationResult->getUsername();
        $frontendUser = $authenticationResult->getUser();
        $contactBook  = $this->resolveOwnedContactBook($frontendUser);

        if (!$contactBook instanceof ContactBook) {
            return $this->textResponse(403, 'No accessible contact book found.');
        }

        if ('PROPFIND' === $method) {
            return $this->handlePropfind($request, $path, $username, $contactBook);
        }

        if ('REPORT' === $method) {
            return $this->handleReport($request, $path, $username, $contactBook);
        }

        if ('GET' === $method) {
            return $this->handleGet($request, $path, $username, $contactBook);
        }

        if ('PUT' === $method) {
            return $this->handlePut($request, $path, $username, $contactBook);
        }

        return $this->handleDelete($request, $path, $username, $contactBook);
    }

    private function addressBookHomeHref(string $username): string
    {
        return '/carddav/addressbooks/' . urlencode($username) . '/';
    }

    /**
     * @param array<int, array<string, mixed>> $responses
     * @param array<int, array<string, mixed>> $contactRows
     *
     * @throws Exception
     */
    private function appendAddressDataResponsesFromContactRows(
        array       &$responses,
        array       $contactRows,
        string      $username,
        string      $userAgent,
        ContactBook $contactBook,
    ): void {
        $vCardVersion = $this->detectVCardVersion($userAgent);

        foreach ($contactRows as $contactRow) {
            $uuid    = (string)$contactRow['uuid'];
            $contact = $this->contactRepository->findByContactBookAndUuid($contactBook, $uuid);

            if (!$contact instanceof Contact) {
                // Skip stale DB rows gracefully when relation data changed in parallel.
                continue;
            }

            $responses[] = $this->buildAddressDataResponse(
                $this->cardHref($username, $uuid),
                $this->vCardService->getVCard($contact, $vCardVersion),
                $this->getContactEtag($contactBook, $uuid, (int)$contactRow['tstamp'])
            );
        }
    }

    /**
     * Validates Basic auth credentials against frontend users and returns an authenticated user context.
     *
     * @throws InvalidPasswordHashException
     */
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

        $userDemand = (new FrontendUserDemand())->setRespectStoragePage(false)
            ->setUsername($username);
        $user       = $this->frontendUserRepository->findOneByDemand($userDemand);

        if (!$user instanceof FrontendUser) {
            $this->logger->warning(
                'CardDAV authentication failed for unknown or disabled user.',
                ['username' => $username]
            );

            return $this->deniedAuthenticationResult('Invalid credentials.');
        }

        $hashFactory  = GeneralUtility::makeInstance(PasswordHashFactory::class);
        $hashInstance = $hashFactory->get($user->getPassword(), 'FE');

        if (!$hashInstance->checkPassword($password, $user->getPassword())) {
            $this->logger->warning('CardDAV authentication failed.', ['username' => $username]);

            return $this->deniedAuthenticationResult('Invalid credentials.');
        }

        return new AuthenticationResult(
            response: null,
            username: $username,
            user: $user,
        );
    }

    /**
     * @param array<string, string> $headers
     */
    private function bodyResponse(
        int    $statusCode,
        string $contentType,
        string $body,
        array  $headers = [],
    ): ResponseInterface {
        $response = $this->responseFactory->createResponse($statusCode)
            ->withHeader('Content-Type', $contentType)
            ->withHeader('DAV', self::DAV_HEADER);

        foreach ($headers as $name => $value) {
            $response = $response->withHeader($name, $value);
        }

        $stream = $this->streamFactory->createStream($body);

        return $response->withBody($stream);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildAddressDataResponse(string $href, string $vCard, string $etag): array
    {
        return [
            'd:href'     => $this->xmlNode($this->xmlEscape($href), 0),
            'd:propstat' => [
                'd:prop'                                   => [
                    'card:address-data'                        => $this->xmlNode($this->xmlEscape($vCard), 0),
                    'd:getetag'                                => $this->xmlNode($this->xmlEscape($etag), 1),
                    XmlUtility::SPECIAL_ARRAY_KEYS['POSITION'] => 0,
                ],
                'd:status'                                 => $this->xmlNode(self::HTTP_STATUS_OK, 1),
                XmlUtility::SPECIAL_ARRAY_KEYS['POSITION'] => 1,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildNotFoundResponse(string $href): array
    {
        return [
            'd:href'   => $this->xmlNode($this->xmlEscape($href), 0),
            'd:status' => $this->xmlNode('HTTP/1.1 404 Not Found', 1),
        ];
    }

    /**
     * @param array<string, array<string, mixed>|string> $properties
     *
     * @return array<string, mixed>
     */
    private function buildPropResponse(string $href, array $properties): array
    {
        $position = 0;
        $prop     = [XmlUtility::SPECIAL_ARRAY_KEYS['POSITION'] => 0];

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
            'd:href'     => $this->xmlNode($this->xmlEscape($href), 0),
            'd:propstat' => [
                'd:prop'                                   => $prop,
                'd:status'                                 => $this->xmlNode(self::HTTP_STATUS_OK, 1),
                XmlUtility::SPECIAL_ARRAY_KEYS['POSITION'] => 1,
            ],
        ];
    }

    private function cardHref(string $username, string $uuid): string
    {
        return $this->collectionHref($username) . urlencode($uuid) . '.vcf';
    }

    private function collectionHref(string $username): string
    {
        return '/carddav/addressbooks/' . urlencode($username) . '/default/';
    }

    private function decodeToken(string $token): SyncTokenState
    {
        // Token format: maxContactTimestamp:maxTombstoneTimestamp:fingerprint
        if (!preg_match('/^(\d+):(\d+):/u', $token, $matches)) {
            return new SyncTokenState(0, 0);
        }

        if (!ctype_digit($matches[1]) || !ctype_digit($matches[2])) {
            return new SyncTokenState(0, 0);
        }

        return new SyncTokenState((int)$matches[1], (int)$matches[2]);
    }

    private function deniedAuthenticationResult(string $message): AuthenticationResult
    {
        return new AuthenticationResult(
            response: $this->textResponse(401, $message, [
                'WWW-Authenticate' => self::AUTHENTICATE_HEADER,
            ]),
        );
    }

    private function detectVCardVersion(string $userAgent): VCardVersion
    {
        $userAgent = strtolower($userAgent);

        if (str_contains($userAgent, 'iphone') || str_contains($userAgent, 'ipad') || str_contains(
            $userAgent,
            'mac os x'
        )) {
            return VCardVersion::Apple;
        }

        return VCardVersion::V3_0;
    }

    /** @return string[] */
    private function extractRequestedUuids(string $payload): array
    {
        $matches = [];
        preg_match_all('#/default/([^/]+)\.vcf#u', $payload, $matches);

        return $matches[1];
    }

    private function extractSyncToken(string $payload): ?string
    {
        $document = new DOMDocument();

        if (!@$document->loadXML($payload)) {
            return null;
        }

        $xpath     = new DOMXPath($document);
        $syncToken = $xpath->evaluate('string(//*[local-name()="sync-token"])');

        return '' === trim($syncToken) ? null : trim($syncToken);
    }

    private function forceUid(string $content, string $uuid): string
    {
        // CardDAV object URLs and the card UID must stay in sync, otherwise clients create duplicates.
        if (preg_match('/^UID:/mi', $content)) {
            return preg_replace('/^UID:.*$/mi', 'UID:' . $uuid, $content);
        }

        return str_replace('END:VCARD', 'UID:' . $uuid . "\r\nEND:VCARD", $content);
    }

    /**
     * @return array<int, array<string, mixed>>
     * @throws Exception
     */
    private function getChangedContacts(ContactBook $contactBook, ?string $previousToken): array
    {
        if (null === $previousToken || '' === $previousToken) {
            // First sync: return full dataset.
            return $this->getContactRows($contactBook);
        }

        $syncTokenState = $this->decodeToken($previousToken);
        $minTimestamp   = max($syncTokenState->getMaxContactTimestamp(), $syncTokenState->getMaxTombstoneTimestamp());

        if (0 >= $minTimestamp) {
            // Invalid or legacy tokens fall back to a full sync instead of failing hard.
            return $this->getContactRows($contactBook);
        }

        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tx_contactbook_domain_model_contact');

        return $queryBuilder->select('uid', 'uuid', 'tstamp')
            ->from('tx_contactbook_domain_model_contact')
            ->where(
                $queryBuilder->expr()
                    ->eq('contact_book', $queryBuilder->createNamedParameter($contactBook->getUid())),
                $queryBuilder->expr()
                    ->eq('deleted', $queryBuilder->createNamedParameter(0, ParameterType::INTEGER)),
                $queryBuilder->expr()
                    ->gt('tstamp', $queryBuilder->createNamedParameter($minTimestamp, ParameterType::INTEGER))
            )
            ->orderBy('uid')
            ->executeQuery()
            ->fetchAllAssociative();
    }

    /**
     * The token combines contact and tombstone state so clients can detect both updates and deletions.
     *
     * @throws Exception
     */
    private function getCollectionToken(ContactBook $contactBook): string
    {
        $contactQueryBuilder = $this->connectionPool->getQueryBuilderForTable('tx_contactbook_domain_model_contact');
        $contactStats        = $contactQueryBuilder->selectLiteral('MAX(tstamp) AS max_tstamp', 'COUNT(uid) AS total')
            ->from('tx_contactbook_domain_model_contact')
            ->where(
                $contactQueryBuilder->expr()
                    ->eq('contact_book', $contactQueryBuilder->createNamedParameter($contactBook->getUid())),
                $contactQueryBuilder->expr()
                    ->eq('deleted', $contactQueryBuilder->createNamedParameter(0, ParameterType::INTEGER))
            )
            ->executeQuery()
            ->fetchAssociative() ?: [];

        $tombstoneQueryBuilder = $this->connectionPool->getQueryBuilderForTable('tx_contactbook_carddav_tombstone');
        $tombstoneStats        = $tombstoneQueryBuilder->selectLiteral('MAX(tstamp) AS max_tstamp', 'COUNT(uid) AS total')
            ->from('tx_contactbook_carddav_tombstone')
            ->where(
                $tombstoneQueryBuilder->expr()
                    ->eq('contact_book', $tombstoneQueryBuilder->createNamedParameter($contactBook->getUid())),
                $tombstoneQueryBuilder->expr()
                    ->eq('deleted', $tombstoneQueryBuilder->createNamedParameter(0, ParameterType::INTEGER))
            )
            ->executeQuery()
            ->fetchAssociative() ?: [];

        $maxContactTimestamp   = (int)($contactStats['max_tstamp'] ?? 0);
        $contactTotal          = (int)($contactStats['total'] ?? 0);
        $maxTombstoneTimestamp = (int)($tombstoneStats['max_tstamp'] ?? 0);
        $tombstoneTotal        = (int)($tombstoneStats['total'] ?? 0);
        $fingerprint           = hash(
            'sha256',
            implode(
                ':',
                [
                    $contactBook->getUid(),
                    $maxContactTimestamp,
                    $contactTotal,
                    $maxTombstoneTimestamp,
                    $tombstoneTotal,
                ]
            )
        );

        return sprintf('%d:%d:%s', $maxContactTimestamp, $maxTombstoneTimestamp, $fingerprint);
    }

    /**
     * ETag is deterministic for UUID + timestamp and changes whenever contact data timestamp changes.
     *
     * @throws Exception
     */
    private function getContactEtag(ContactBook $contactBook, string $uuid, ?int $timestamp = null): string
    {
        if (null === $timestamp) {
            $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tx_contactbook_domain_model_contact');
            $row          = $queryBuilder->select('tstamp')
                ->from('tx_contactbook_domain_model_contact')
                ->where(
                    $queryBuilder->expr()
                        ->eq('contact_book', $queryBuilder->createNamedParameter($contactBook->getUid())),
                    $queryBuilder->expr()
                        ->eq('uuid', $queryBuilder->createNamedParameter($uuid)),
                    $queryBuilder->expr()
                        ->eq('deleted', $queryBuilder->createNamedParameter(0, ParameterType::INTEGER))
                )
                ->setMaxResults(1)
                ->executeQuery()
                ->fetchAssociative();
            $timestamp = (int)($row['tstamp'] ?? 0);
        }

        return '"' . hash('sha256', $uuid . ':' . $timestamp) . '"';
    }

    /**
     * @return array<int, array<string, mixed>>
     * @throws Exception
     */
    private function getContactRows(ContactBook $contactBook): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tx_contactbook_domain_model_contact');

        return $queryBuilder->select('uid', 'uuid', 'tstamp')
            ->from('tx_contactbook_domain_model_contact')
            ->where(
                $queryBuilder->expr()
                    ->eq('contact_book', $queryBuilder->createNamedParameter($contactBook->getUid())),
                $queryBuilder->expr()
                    ->eq('deleted', $queryBuilder->createNamedParameter(0, ParameterType::INTEGER))
            )
            ->orderBy('uid')
            ->executeQuery()
            ->fetchAllAssociative();
    }

    /**
     * @return string[]
     * @throws Exception
     */
    private function getDeletedUuids(ContactBook $contactBook, ?string $previousToken): array
    {
        if (null === $previousToken || '' === $previousToken) {
            // First sync has no known prior state, therefore no deletion delta.
            return [];
        }

        $syncTokenState = $this->decodeToken($previousToken);
        $minTimestamp   = max($syncTokenState->getMaxContactTimestamp(), $syncTokenState->getMaxTombstoneTimestamp());

        if (0 >= $minTimestamp) {
            return [];
        }

        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tx_contactbook_carddav_tombstone');

        return array_map(
            static fn(array $row): string => (string)$row['contact_uuid'],
            $queryBuilder->select('contact_uuid')
                ->from('tx_contactbook_carddav_tombstone')
                ->where(
                    $queryBuilder->expr()
                        ->eq('contact_book', $queryBuilder->createNamedParameter($contactBook->getUid())),
                    $queryBuilder->expr()
                        ->eq('deleted', $queryBuilder->createNamedParameter(0, ParameterType::INTEGER)),
                    $queryBuilder->expr()
                        ->gt('tstamp', $queryBuilder->createNamedParameter($minTimestamp, ParameterType::INTEGER))
                )
                ->orderBy('uid')
                ->executeQuery()
                ->fetchAllAssociative()
        );
    }

    /**
     * @throws Exception
     */
    private function handleDelete(
        ServerRequestInterface $request,
        string                 $path,
        string                 $username,
        ContactBook            $contactBook,
    ): ResponseInterface {
        $resource = $this->parseResource($path, $username);

        if (!$resource instanceof CardDavResource || CardDavResourceType::Card !== $resource->getType()) {
            return $this->textResponse(404, 'Resource not found.');
        }

        $contactUuid = (string)$resource->getUuid();
        $contact     = $this->contactRepository->findByContactBookAndUuid($contactBook, $contactUuid);

        if (!$contact instanceof Contact) {
            return $this->textResponse(404, 'Contact not found.');
        }

        $ifMatch = trim($request->getHeaderLine('If-Match'));

        if ('' !== $ifMatch && '*' !== $ifMatch) {
            // Respect optimistic concurrency checks from clients and reject stale delete attempts.
            $currentEtag = $this->getContactEtag($contactBook, $contactUuid);

            if ($ifMatch !== $currentEtag) {
                return $this->textResponse(412, 'ETag mismatch.');
            }
        }

        try {
            $this->insertTombstone($contactBook, $contactUuid);
            $this->contactRepository->remove($contact);
            $this->persistenceManager->persistAll();
        } catch (IllegalObjectTypeException $exception) {
            throw new RuntimeException($exception->getMessage(), (int)$exception->getCode(), $exception);
        }

        return $this->textResponse(204, '');
    }

    /**
     * @throws Exception
     */
    private function handleGet(
        ServerRequestInterface $request,
        string                 $path,
        string                 $username,
        ContactBook            $contactBook,
    ): ResponseInterface {
        $resource = $this->parseResource($path, $username);

        if (!$resource instanceof CardDavResource) {
            return $this->textResponse(404, 'Resource not found.');
        }

        if (in_array(
            $resource->getType(),
            [
                CardDavResourceType::Home,
                CardDavResourceType::Principal,
                CardDavResourceType::Root,
            ],
            true
        )) {
            return $this->textResponse(200, 'CardDAV endpoint available.');
        }

        if (CardDavResourceType::Collection === $resource->getType()) {
            $href = $this->collectionHref($username);

            // Collection GET is treated as metadata response so clients can quickly refresh CTag state.
            return $this->multistatusResponse([
                $this->buildPropResponse(
                    $href,
                    [
                        'cs:getctag'    => $this->xmlEscape($this->getCollectionToken($contactBook)),
                        'd:displayname' => 'Contacts',
                    ]
                ),
            ]);
        }

        $contact = $this->contactRepository->findByContactBookAndUuid($contactBook, (string)$resource->getUuid());

        if (!$contact instanceof Contact) {
            return $this->textResponse(404, 'Contact not found.');
        }

        $vCard = $this->vCardService->getVCard(
            $contact,
            $this->detectVCardVersion($request->getHeaderLine('User-Agent'))
        );
        $etag = $this->getContactEtag($contactBook, (string)$resource->getUuid());

        return $this->bodyResponse(200, 'text/vcard; charset=utf-8', $vCard, [
            'ETag' => $etag,
        ]);
    }

    /**
     * Handles a PROPFIND request for CardDAV resources, returning hierarchical discovery information based on the
     * resource type and depth. It is used by clients to discover the structure of the CardDAV server, including
     * principals, address book homes, collections, and individual cards.
     *
     * @throws Exception
     */
    private function handlePropfind(
        ServerRequestInterface $request,
        string                 $path,
        string                 $username,
        ContactBook            $contactBook,
    ): ResponseInterface {
        $resource = $this->parseResource($path, $username);

        if (!$resource instanceof CardDavResource) {
            return $this->textResponse(404, 'Resource not found.');
        }

        $depth     = (int)($request->getHeaderLine('Depth') ?: '0');
        $responses = [];

        // Build hierarchical DAV discovery responses (root -> principal -> home -> collection -> card).
        if (CardDavResourceType::Root === $resource->getType()) {
            $responses[] = $this->buildPropResponse(
                '/carddav/',
                [
                    'd:current-user-principal' => [
                        'd:href' => $this->xmlNode($this->xmlEscape($this->principalHref($username)), 0),
                    ],
                    'd:resourcetype'           => [
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
                        'd:resourcetype'            => [
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
                    'd:displayname'             => $this->xmlEscape($username),
                    'd:resourcetype'            => [
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
                        'd:displayname'  => 'Contacts',
                        'd:resourcetype' => [
                            'card:addressbook' => $this->xmlEmptyNode(0),
                            'd:collection'     => $this->xmlEmptyNode(1),
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
                                'version'      => '3.0',
                            ],
                            XmlUtility::SPECIAL_ARRAY_KEYS['NODE_VALUE'] => '',
                            XmlUtility::SPECIAL_ARRAY_KEYS['POSITION']   => 0,
                        ],
                    ],
                    'cs:getctag'                  => $this->xmlEscape($this->getCollectionToken($contactBook)),
                    'd:displayname'               => 'Contacts',
                    'd:resourcetype'              => [
                        'card:addressbook' => $this->xmlEmptyNode(0),
                        'd:collection'     => $this->xmlEmptyNode(1),
                    ],
                    'd:sync-token'                => $this->xmlEscape($this->getCollectionToken($contactBook)),
                ]
            );

            if (0 < $depth) {
                // Depth > 0 returns all cards as leaf resources with their ETags.
                foreach ($this->getContactRows($contactBook) as $contactRow) {
                    $uuid        = (string)$contactRow['uuid'];
                    $responses[] = $this->buildPropResponse(
                        $this->cardHref($username, $uuid),
                        [
                            'd:getcontenttype' => 'text/vcard; charset=utf-8',
                            'd:getetag'        => $this->xmlEscape(
                                $this->getContactEtag($contactBook, $uuid, (int)$contactRow['tstamp'])
                            ),
                            'd:resourcetype'   => $this->xmlEmptyNode(0),
                        ]
                    );
                }
            }
        } else {
            $responses[] = $this->buildPropResponse(
                $this->cardHref($username, (string)$resource->getUuid()),
                [
                    'd:getcontenttype' => 'text/vcard; charset=utf-8',
                    'd:getetag'        => $this->xmlEscape(
                        $this->getContactEtag($contactBook, (string)$resource->getUuid())
                    ),
                    'd:resourcetype'   => $this->xmlEmptyNode(0),
                ]
            );
        }

        return $this->multistatusResponse($responses);
    }

    /**
     * @throws Exception
     * @throws InvalidQueryException
     */
    private function handlePut(
        ServerRequestInterface $request,
        string                 $path,
        string                 $username,
        ContactBook            $contactBook,
    ): ResponseInterface {
        $resource = $this->parseResource($path, $username);

        if (!$resource instanceof CardDavResource || CardDavResourceType::Card !== $resource->getType()) {
            return $this->textResponse(404, 'Resource not found.');
        }

        $contactUuid     = (string)$resource->getUuid();
        $existingContact = $this->contactRepository->findByContactBookAndUuid($contactBook, $contactUuid);
        $ifMatch         = trim($request->getHeaderLine('If-Match'));
        $ifNoneMatch     = trim($request->getHeaderLine('If-None-Match'));

        if ('*' === $ifNoneMatch && $existingContact instanceof Contact) {
            return $this->textResponse(412, 'Resource already exists.');
        }

        if ('' !== $ifMatch && '*' !== $ifMatch && $existingContact instanceof Contact) {
            // Update requests must present the latest ETag to prevent overwriting concurrent changes.
            $currentEtag = $this->getContactEtag($contactBook, $contactUuid);

            if ($ifMatch !== $currentEtag) {
                return $this->textResponse(412, 'ETag mismatch.');
            }
        }

        $content = (string)$request->getBody();

        if ('' === trim($content)) {
            return $this->textResponse(400, 'Empty vCard payload.');
        }

        $content = $this->forceUid($content, $contactUuid);

        try {
            // Persist contact data via vCard import so CardDAV and regular import share one parsing pipeline.
            $this->importService->upsertSingleVCardContent($content, $contactBook);
        } catch (IllegalObjectTypeException|UnknownObjectException $exception) {
            $this->logger->error('CardDAV PUT failed.', ['exception' => $exception]);

            return $this->textResponse(400, 'Invalid vCard payload.');
        }

        $etag       = $this->getContactEtag($contactBook, $contactUuid);
        $statusCode = $existingContact instanceof Contact ? 204 : 201;

        return $this->textResponse($statusCode, '', [
            'ETag' => $etag,
        ]);
    }

    /**
     * Handles a REPORT request for CardDAV resources, supporting both sync-collection and addressbook-multiget reports.
     * Sync-collection reports return incremental changes since a given sync token, including both updated and deleted
     * contacts.
     * Addressbook-multiget reports return specific contacts based on requested hrefs.
     *
     * @throws Exception
     */
    private function handleReport(
        ServerRequestInterface $request,
        string                 $path,
        string                 $username,
        ContactBook            $contactBook,
    ): ResponseInterface {
        $resource = $this->parseResource($path, $username);

        if (!$resource instanceof CardDavResource || CardDavResourceType::Collection !== $resource->getType()) {
            return $this->textResponse(404, 'Collection not found.');
        }

        $payload = (string)$request->getBody();

        if (str_contains($payload, 'sync-collection')) {
            // Sync report is incremental and includes both changed cards and tombstoned deletions.
            return $this->handleSyncCollectionReport($payload, $username, $contactBook, $request);
        }

        // addressbook-multiget returns only explicitly requested card hrefs when present.
        $requestedUuids = $this->extractRequestedUuids($payload);
        $contactRows    = $this->getContactRows($contactBook);

        if ([] !== $requestedUuids) {
            $contactRows = array_values(
                array_filter(
                    $contactRows,
                    static fn(array $row): bool => in_array((string)$row['uuid'], $requestedUuids, true)
                )
            );
        }

        $responses = [];
        $this->appendAddressDataResponsesFromContactRows(
            $responses,
            $contactRows,
            $username,
            $request->getHeaderLine('User-Agent'),
            $contactBook
        );

        return $this->multistatusResponse($responses);
    }

    /**
     * @throws Exception
     */
    private function handleSyncCollectionReport(
        string                 $payload,
        string                 $username,
        ContactBook            $contactBook,
        ServerRequestInterface $request,
    ): ResponseInterface {
        $previousToken = $this->extractSyncToken($payload);
        $currentToken  = $this->getCollectionToken($contactBook);
        $changes       = $this->getChangedContacts($contactBook, $previousToken);
        $deletedUuids  = $this->getDeletedUuids($contactBook, $previousToken);
        $responses     = [];

        $this->appendAddressDataResponsesFromContactRows(
            $responses,
            $changes,
            $username,
            $request->getHeaderLine('User-Agent'),
            $contactBook
        );

        foreach ($deletedUuids as $deletedUuid) {
            $responses[] = $this->buildNotFoundResponse($this->cardHref($username, $deletedUuid));
        }

        return $this->multistatusResponse($responses, $currentToken);
    }

    private function insertTombstone(ContactBook $contactBook, string $uuid): void
    {
        $timestamp    = $GLOBALS['EXEC_TIME'] ?? time();
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tx_contactbook_carddav_tombstone');
        $queryBuilder->insert('tx_contactbook_carddav_tombstone')
            ->values([
                'contact_book' => $contactBook->getUid(),
                'contact_uuid' => $uuid,
                'crdate'       => $timestamp,
                'tstamp'       => $timestamp,
                'deleted'      => 0,
                'pid'          => 0,
            ])
            ->executeStatement();
    }

    private function isHttpsRequest(ServerRequestInterface $request): bool
    {
        if ('https' === strtolower(
            $request->getUri()
                    ->getScheme()
        )) {
            return true;
        }

        return 'https' === strtolower($request->getHeaderLine('X-Forwarded-Proto'));
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
            'd:response'                                 => $responses,
            XmlUtility::SPECIAL_ARRAY_KEYS['NAMESPACES'] => [
                'card' => 'urn:ietf:params:xml:ns:carddav',
                'cs'   => 'http://calendarserver.org/ns/',
                'd'    => 'DAV:',
            ],
        ];

        if (null !== $syncToken) {
            // sync-token is attached only for sync responses where clients need a new cursor.
            $multistatusNode['d:sync-token'] = $this->xmlNode($this->xmlEscape($syncToken), count($responses));
        }

        $xml = XmlUtility::convertToXml(
            ['d:multistatus' => $multistatusNode],
            XmlUtility::XML_HEADER,
            false
        );

        return $this->xmlResponse(207, $xml);
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
        // Map URL paths to logical DAV resources used by all method handlers.
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

        if (preg_match(
            '#^/carddav/addressbooks/' . preg_quote($encodedUsername, '#') . '/default/([^/]+)\.vcf$#u',
            $path,
            $matches
        )) {
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

    private function redirectResponse(string $location): ResponseInterface
    {
        $response = $this->responseFactory->createResponse(308);

        return $response->withHeader('Location', $location);
    }

    /**
     * @throws InvalidQueryException
     */
    private function resolveOwnedContactBook(FrontendUser $frontendUser): ?ContactBook
    {
        // CardDAV currently exposes the first contact book owned by the authenticated frontend user.
        $contactBookDemand = (new ContactBookDemand())->setOwner($frontendUser)->setRespectStoragePage(false);

        return $this->contactBookRepository->findOneByDemand($contactBookDemand);
    }

    /**
     * @param array<string, string> $headers
     */
    private function textResponse(int $statusCode, string $message, array $headers = []): ResponseInterface
    {
        return $this->bodyResponse($statusCode, 'text/plain; charset=utf-8', $message, $headers);
    }

    /**
     * @return array<string, int|string>
     */
    private function xmlEmptyNode(int $position): array
    {
        return [
            XmlUtility::SPECIAL_ARRAY_KEYS['NODE_VALUE'] => '',
            XmlUtility::SPECIAL_ARRAY_KEYS['POSITION']   => $position,
        ];
    }

    private function xmlEscape(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_COMPAT, 'UTF-8');
    }

    /**
     * @return array<string, int|string>
     */
    private function xmlNode(string $value, int $position): array
    {
        return [
            XmlUtility::SPECIAL_ARRAY_KEYS['NODE_VALUE'] => $value,
            XmlUtility::SPECIAL_ARRAY_KEYS['POSITION']   => $position,
        ];
    }

    private function xmlResponse(int $statusCode, string $xml): ResponseInterface
    {
        return $this->bodyResponse($statusCode, 'application/xml; charset=utf-8', $xml);
    }
}
