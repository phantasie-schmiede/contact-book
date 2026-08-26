# CardDAV Postman Setup

These Postman files test the handler in `vendor/psbits/contact-book/Classes/CardDav/CardDavHandler.php`.

## Files

- `postman/CardDAV-Handler.postman_collection.json`
- `postman/CardDAV-Handler.local.postman_environment.json`

## Import into Postman

1. Import the collection file.
2. Import the environment file.
3. Activate the "CardDAV Handler Local" environment.
4. Fill in the `username` and `password` variables.
5. Adjust `baseUrl`.

## Test Flow

- The requests are sorted numerically.
- The sequence from 01 to 11 covers discovery, reading, writing, deletion, and sync.
- `etag` and `syncToken` are automatically extracted from responses and stored as collection variables.

## Notes

- The handler accepts HTTPS only (`isHttpsRequest`).
- For local HTTP tests, the collection sets `X-Forwarded-Proto: https`.
- `PUT` uses `If-None-Match: *` for the create case; on reruns, a `412` may be correct.
