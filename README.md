# PSBits Contact Book

A TYPO3 extension for managing contact books, contacts and contact groups, with
frontend editing, contact cards, event widgets and vCard import/export.

- **Composer package:** `psbits/contact-book`
- **TYPO3 extension key:** `contact_book`
- **PHP namespace:** `PSBits\ContactBook`

## Status

This project is in **alpha**. APIs, configuration and data structures may change.
Evaluate it in a non-production environment and back up existing data before
updating or importing contacts.

The current development branch is `0.x`. Frontend Templates and Frontend Users
are currently required as dependencies.

## Features

- Multiple contact books with frontend users assigned as owners and an active
  contact book per user.
- Contacts and contact groups with frontend management.
- Addresses, email addresses, phone numbers, websites, social media profiles,
  bank accounts, documents, jobs and employment information.
- Events associated with contacts, including birthdays.
- Searchable contact lists, autocomplete and AJAX list updates.
- Contact cards filtered by selected contacts, categories or a contact group.
- Contact group and event widgets.
- vCard import through a backend module and vCard export.
- German phone number parsing using landline area codes, mobile prefixes and
  service number blocks.
- English and German translations.

## Requirements

The runtime constraints are defined in [composer.json](composer.json):

| Dependency | Constraint |
| --- | --- |
| PHP | `^8.3` |
| TYPO3 CMS | `^13.4 \|\| ^14.3` |
| PSBits Foundation | `^4.2.2` |
| PSBits Frontend Templates | `@dev` |
| PSBits Frontend Users | `@dev` |
| PSBits View Helpers | `^4.0` |
| Ramsey UUID | `^4.7` |

This extension builds on the PSBits ecosystem. Frontend Templates supplies shared
Fluid partials, ViewHelpers and JavaScript assets; Frontend Users supplies frontend
user integration.

## Installation

For the current development version, run the following in your TYPO3 project's
root directory:

```sh
composer require 'psbits/contact-book'
```

After installation, apply the required database schema updates and clear TYPO3
caches using your usual deployment workflow.

## Configuration

### TypoScript and storage

Ensure that the extension's TypoScript constants and setup are included in your
site configuration. The entry points are:

- `EXT:contact_book/Configuration/TypoScript/constants.typoscript`
- `EXT:contact_book/Configuration/TypoScript/setup.typoscript`

Create a sysfolder for contact records and set its page UID in your TypoScript
constants:

```typoscript
plugin.tx_contactbook.persistence.storagePid = 123
```

Replace `123` with your sysfolder's UID. This setting also supplies the backend
module's storage PID. The setup uses
`plugin.tx_frontendtemplates.view.partialRootPath` for shared frontend partials,
so the Frontend Templates configuration must be available.

### Frontend plugins

Add the appropriate plugins to your site's content pages:

| Plugin | Purpose |
| --- | --- |
| Contact books | Create, manage and select contact books. |
| Contact list | Browse and manage contacts. |
| Contact groups | Manage groups of contacts. |
| Contact cards | Display contacts selected by contact, category or group. |
| Contact group widget | Display a selected contact group. |
| Event widget | Display events with configurable event types and limits. |

Contact cards, the contact group widget and the event widget have FlexForm
settings in their content elements. Autocomplete and AJAX refresh endpoints are
registered separately and do not need manually placed content elements.

For private frontend management, configure frontend login, assign contact book
owners and restrict access to the relevant pages and content elements. Do not
assume that every display plugin enforces private contact book ownership!

### Cache invalidation

Add the following page TSconfig to the sysfolder containing contact records so
backend record changes invalidate the tagged page caches:

```typoscript
TCEMAIN.clearCacheCmd = cacheTag:tx_contactbook_contacts, cacheTag:tx_contactbook_groups
```

Rendered contact lists use the `contacts_list` cache, configured with TYPO3's
database cache backend by default.

## vCard import

Open the contact import backend module, upload a vCard file and review the
resulting records. Configure the storage PID before importing.

The importer looks for existing contacts by first and last name. When it finds a
match, it adds imported information to the existing contact rather than creating
a new one. Back up your data first and test with a small sample, especially if
multiple contacts share the same name. Do not assume lossless round-trip support
for every vCard property or format.

Phone number import currently only processes values starting with `+49`; other
telephone values are skipped.

## Privacy and security

Contact records may contain personal and financial information, including IBANs.
Only store information you need and are entitled to process.

- Use HTTPS and restrict frontend and backend access to authorized users with multi-factor authentication.
- Review which fields are exposed by display plugins, exports and custom templates
  before making any contact information public.
- Protect database access and backups, and review cache and logging behavior for
  your deployment.
- Define appropriate retention and deletion practices for your use case.

Exclusively personal or family use may fall under the GDPR household exemption
([Article 2(2)(c)](https://eur-lex.europa.eu/eli/reg/2016/679/oj)). Business,
organizational or publicly accessible use may be subject to GDPR obligations.
Using this extension does not by itself establish GDPR compliance; operators
must assess their own processing and deployment. This is not legal advice.

## Development

For a standalone checkout, install Composer dependencies with `composer install`.
The post-install hook installs npm dependencies and synchronizes shared QA files
from `psbits/qa`. Node.js 24 or newer is required by [package.json](package.json).

Run the checks relevant to your changes:

```sh
composer lint:all
composer test:unit
composer test:functional
```

Additional local and Docker test runs are available through `composer test:local`
and `composer test:docker`; they require the corresponding test environments.
`composer test:all` runs all configured test suites.

`composer fix:all` applies automated formatting fixes. `composer qa` combines
fixes, linting and tests, so it can modify files.

## Support and license

Report bugs and feature requests in the
[GitHub issue tracker](https://github.com/phantasie-schmiede/contact-book/issues).
Do not include real contact details, bank information or credentials in public
reports.

Licensed under **GPL-3.0-or-later**. See [LICENSE.txt](LICENSE.txt).
