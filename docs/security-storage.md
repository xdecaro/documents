# Documents storage and security baseline

Documents 1.0.0 treats uploaded files as private by default.

## Storage

The default storage directory is created one level above `JPATH_ROOT`, under a site-specific hash directory. Stored files use generated UUID-based `.blob` names instead of user-supplied filenames.

The database stores only the generated storage name plus safe metadata. Diagnostics never expose the absolute private storage path.

Uninstall deliberately does not delete document tables or private files. This avoids destructive data loss from an accidental Joomla uninstall. Explicit purge tooling can be added later as a separate administrator action with its own authorization and confirmation flow.

## Upload validation

Server-side checks include:

- PHP upload success code;
- `is_uploaded_file()` verification;
- 25 MiB maximum file size;
- extension allow-list;
- server-detected MIME type using `finfo`;
- extension/MIME consistency;
- generated storage filename;
- SHA-256 integrity hash after storage.

Executable/web-active formats such as PHP, JavaScript, HTML and SVG are not accepted in 1.0.0.

## Download authorization

Downloads are streamed through the administrator component after Joomla ACL and viewing-level checks. Raw private storage paths are never returned to the browser.

Responses set `Content-Disposition: attachment`, `X-Content-Type-Options: nosniff` and private/no-store cache headers.

## CSRF and ACL

Create/edit/delete use Joomla MVC controllers/forms and Joomla form tokens. ACL is enforced server-side; JavaScript is not considered an authorization boundary.
