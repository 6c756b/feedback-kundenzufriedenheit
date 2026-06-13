# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).

---

## [Unreleased]

- Reference management improvements
- better dummy logos and hero image
- custom coloring
- some more landing-page designs (maybe)

---

## [0.1.1a] - 2026-06-13 (WIP)

### Added

- Per-user login method: `ldap`, `local` (password only), or `both` (LDAP with local password fallback)
- Database column `login_method` on `users` table (migration `002_add_login_method.sql`)
- Login method selector in user edit form
- Migration runner (`database/migrate_sqlite.php`) applies pending `.sql` migrations from `database/migrations/` and tracks applied migrations in `_migrations` table
- `dev/start.ps1`: PowerShell equivalent of `dev/start.sh` for Windows development
- `branding.logo_height` config key: optional CSS height for the logo in the survey header (e.g. `'56px'`)
- Backend nav: username is a clickable link to the profile page

### Changed

- Auth flow now reads per-user `login_method` instead of always falling back to local password on LDAP failure; LDAP-only users have no password fallback
- Auto-provisioned users (first LDAP login) receive `login_method = 'ldap'`
- User edit form reorganised: left column for auth fields, right column for signature fields (Anzeigename, Berufsbezeichnung, Telefon, Profilbild); Rolle / Flags below
- Profile form field order

### Fixed

- SQLite schema brought in sync with MySQL schema (missing columns added)
- Session garbage collection probability and divisor now set explicitly (`gc_probability=1`, `gc_divisor=100`) to ensure reliable cleanup

---

## [0.1a] - 2026-06-10 (WIP)

Initial alpha release. Core platform is functional.

### Added

**Core infrastructure**
- No-framework PHP 8.1+ application with PSR-4 autoloading
- Custom router with middleware support (auth, role-based access)
- PDO-based database abstraction supporting SQLite (dev) and MySQL (prod)
- Session management, CSRF protection
- Audit log: every backend action recorded with user, entity, timestamp

**Authentication & authorization**
- LDAP authentication with auto-provisioning on first login
- Five-level role hierarchy: `none`, `reader`, `staff`, `admin`, `superadmin`
- Static dev credentials for local mode (bypasses LDAP)

**Survey lifecycle**
- 8-character access code generation for customers (no account required)
- Survey states: `open → started → completed → evaluation → archived`
- AJAX-based survey frontend with slider (1–6) and free-text question types
- CSV bulk import to create multiple surveys from a spreadsheet in one step

**Backend**
- Dashboard with open surveys, scores, and reference status overview
- Survey management: create, edit, send email, cancel, delete (superadmin)
- Evaluation view: mark read/unread, archive, export PDF
- PDF export with color-coded bar charts per question area (FPDF, server-side)
- Outlook text copy for manual email dispatch
- User management: create, edit, assign roles
- Question and area management with drag-and-drop sequence ordering
- Metropolregion management (linked to surveys and project leads)
- Audit log viewer (superadmin)

**Configuration**
- Single `config.php` (not committed) controls all environment settings
- `config.example.php` documents every available option
- Versioned via `app.version` config key

**Developer experience**
- `dev/start.sh`: one-command local setup (SQLite, seed data, built-in server)
- All frontend dependencies (FPDF, Quill, Chart.js) vendored — no build pipeline
