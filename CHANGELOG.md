# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).

---

## [Unreleased]

- Reference management improvements
- Allow login with password (when ldap not available)
- better dummy logos and hero image
- custom coloring
- some more landing-page designs (maybe)

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
