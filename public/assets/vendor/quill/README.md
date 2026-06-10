# Quill WYSIWYG Editor

Quill v2 is used as the email body editor in the survey detail view.

## Installation

Download the following two files from https://quilljs.com and place them here:

- `quill.min.js`  — minified JavaScript bundle
- `quill.snow.css` — Snow theme stylesheet

Direct download links (v2 latest):
```
https://cdn.jsdelivr.net/npm/quill@2/dist/quill.min.js
https://cdn.jsdelivr.net/npm/quill@2/dist/quill.snow.css
```

## Why self-hosted?

The project intentionally avoids external CDN dependencies at runtime to ensure
the tool remains functional offline and over a 10+ year lifespan without external
service dependencies. Quill is MIT-licensed.

## Version

Pin to Quill 2.x. Do not use Quill 1.x — the API for `setContents` and
`clipboard.dangerouslyPasteHTML` differs between major versions.
