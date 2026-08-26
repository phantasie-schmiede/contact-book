PSBits Contact Book
===================

CardDAV support
---------------
The extension exposes a CardDAV endpoint through the TYPO3 middleware configured in
`/home/runner/work/contact-book/contact-book/ext_localconf.php`.

### Supported CardDAV functionality
- CardDAV discovery redirect from `/.well-known/carddav` to `/carddav/`.
- Basic authentication with TYPO3 frontend users (`fe_users`) over HTTPS only.
- `OPTIONS` for DAV capability and allowed method negotiation.
- `PROPFIND` on root, principal, addressbook-home, collection, and single-card resources.
- `GET` for health/root resources and individual vCard download.
- `REPORT`:
    - Addressbook multiget (`address-data` responses with ETag)
    - Sync collection (`sync-token` support including deleted contacts)
- `PUT` for create/update of contacts by card URL (`{uuid}.vcf`) with ETag precondition handling (`If-Match`,
  `If-None-Match`).
- `DELETE` with optional ETag precondition handling (`If-Match`).
- Change tracking for sync clients via collection token and tombstone storage
  (`tx_contactbook_carddav_tombstone`).
- vCard generation:
    - Default export as vCard 3.0
    - Apple-specific compatibility format when Apple user agents are detected
- vCard import/upsert on `PUT`, including UID normalization to match the requested card URL.

### CardDAV setup and connection
1. Ensure your TYPO3 site is reachable via HTTPS.
2. Ensure middleware registration exists in
   `/home/runner/work/contact-book/contact-book/ext_localconf.php`:
   - `$GLOBALS['TYPO3_CONF_VARS']['SYS']['middlewares']['contact-book/carddav']`
3. Create or use a frontend user (`fe_users`) with a valid username/password.
4. Assign that frontend user to exactly one contact book through
   `tx_contactbook_contactbooks_feusers` (`uid_foreign` = frontend user UID, `uid_local` = contact book UID).
5. Configure your CardDAV client with:
   - Username: TYPO3 frontend username
   - Password: TYPO3 frontend password
   - Server URL (recommended): `https://<your-domain>/.well-known/carddav`
   - Fallback URL: `https://<your-domain>/carddav/addressbooks/<username>/default/`
6. Trigger the first sync. The extension will provide ETags and sync tokens for incremental updates.

### Notes
- Phone numbers not starting with `+49` are currently ignored during vCard import.
- Each frontend user can currently access only the first mapped contact book returned by the assignment lookup.

Caching
-------
Add the following line to the TSconfig of your sysfolder containing contact records:
```
TCEMAIN.clearCacheCmd = cacheTag:tx_contactbook_contacts, cacheTag:tx_contactbook_groups
```
