# Feedback KZB - Kundenzufriedenheitsbefragung

![Version](https://img.shields.io/badge/version-0.1.3a-blue)
![License](https://img.shields.io/badge/license-MIT-green)
![UI Language](https://img.shields.io/badge/UI-Deutsch-orange)
![PHP](https://img.shields.io/badge/PHP-8.1%2B-777bb4)

A self-hosted customer satisfaction survey platform built with plain PHP. No framework, no build tools, no package manager required in production.

Customers receive a unique access code via email and fill out a structured survey (slider ratings + free text). Staff evaluates results, exports PDF reports, and tracks reference requests - all from a role-protected backend.

---

## Features

- **Code-based survey access** - customers enter an 8-character code, no account needed
- **Slider + free text questions** - 1–6 scale with optional free-text follow-up, fully AJAX
- **PDF export** - color-coded bar charts per question area, generated server-side with FPDF
- **Email dispatch** - send invitations via SMTP or copy a pre-formatted Outlook text
- **CSV bulk import** - create multiple surveys from a spreadsheet in one step
- **Role-based backend** - five roles: `reader`, `staff`, `admin`, `superadmin`, plus `none`
- **LDAP authentication** - first login auto-creates a user record; no manual provisioning
- **Audit log** - every backend action is logged with user, entity, and timestamp
- **Survey lifecycle** - `open → started → completed` → evaluation → `archived`
- **Dashboard** - chart overview of open surveys, scores, and reference status
- **REST API** - create and retrieve surveys from external CRM systems via Bearer token authentication
- **API key management** - admin UI to create and revoke API keys with per-key read/write permissions and optional expiry
- **Responsive backend** - off-canvas sidebar and collapsible panel for mobile use
- **No external runtime dependencies** - FPDF, Quill, and Chart.js are vendored

---

## Requirements

- PHP 8.1+, with extensions: `pdo`, `pdo_sqlite` (dev) or `pdo_mysql` (prod), `ldap`, `mbstring`
- SQLite 3.35+ (development) or MySQL 8+ (production)
- An LDAP server for authentication (can be bypassed in `local` mode)

---

## Getting Started

### Local development

```bash
git clone https://github.com/6c756b/feedback-kundenzufriedenheit.git
cd feedback-kundenzufriedenheit

# Copy and configure
cp config.example.php config.php
# Edit config.php: set env='local', uncomment dev_auth block

# Start the dev server (creates SQLite DB + seeds demo data automatically)
bash dev/start.sh
```

Open [http://localhost:8080](http://localhost:8080).

The dev start script:
1. Checks that `config.php` exists and `env='local'` is set
2. Creates `database/feedback.sqlite` from `database/schema_sqlite.sql` on first run
3. Launches the PHP built-in server at `localhost:8080`

Default dev credentials (defined in `config.php` → `dev_auth`):

| Email | Password | Role |
|---|---|---|
| `admin@local.dev` | `admin` | superadmin |
| `staff@local.dev` | `staff` | staff |
| `reader@local.dev` | `reader` | reader |

### Production deployment

```bash
# 1. Clone and configure
cp config.example.php config.php
# Set env='production', configure db (MySQL), ldap, smtp

# 2. Apply schema
mysql -u user -p feedback < database/schema.sql

# 3. Point your web server document root to public/
# Apache/nginx must rewrite all requests to public/index.php
```

Apache example:

```apache
DocumentRoot /var/www/feedback/public
<Directory /var/www/feedback/public>
    AllowOverride All
    Options -Indexes
</Directory>
```

Nginx example:

```nginx
root /var/www/feedback/public;
index index.php;
location / { try_files $uri $uri/ /index.php?$query_string; }
location ~ \.php$ { fastcgi_pass unix:/run/php/php8.1-fpm.sock; include fastcgi_params; }
```

---

## Configuration

All configuration lives in `config.php` (not committed). Copy `config.example.php` to get started.

| Key | Description |
|---|---|
| `env` | `'local'` or `'production'` - controls LDAP bypass and error display |
| `db.driver` | `'sqlite'` or `'mysql'` |
| `ldap.*` | Host, port, base DN for LDAP bind authentication |
| `smtp.*` | SMTP credentials for survey invitation emails |
| `app.name` | Application name shown in browser title and nav |
| `app.company_name` | Used in PDF footer and email sender name |
| `branding.*` | Logo paths, hero image, favicon |
| `landing.*` | Headline, card text, and checklist on the public start page |
| `dev_auth.users` | Static credentials for `local` mode (bypasses LDAP) |

---

## Project Structure

```
app/
  Controllers/
    Backend/    # Dashboard, Surveys, Evaluation, Users, Areas, Questions, Logs
    Frontend/   # Public survey flow (code input, survey, thank-you)
  Core/         # Router, Auth (LDAP), Database (PDO singleton), Session, CSRF, Logger
  Models/       # Thin model classes - static methods wrapping SQL
  Services/     # PdfExport (FPDF), Mailer (SMTP), OutlookText, SignatureManager
  Views/
    backend/    # Admin UI views
    frontend/   # Customer-facing views
    layout/     # backend.php, frontend.php, backend-auth.php
database/
  schema.sql          # MySQL production schema
  schema_sqlite.sql   # SQLite development schema
dev/
  start.sh            # One-command local dev setup + server start
public/               # Web root - index.php (front controller), assets/
resources/
  templates/          # email.html, signature.html - editable HTML templates
```

---

## Role Hierarchy

| Role | Access |
|---|---|
| `none` | No backend access |
| `reader` | View surveys, evaluations, archive; export PDF |
| `staff` | All of reader + create/edit/send surveys, mark read, archive |
| `admin` | All of staff + manage users, questions, areas, and metropolregions |
| `superadmin` | All of admin + delete surveys + audit log |

Roles are assigned in the backend. First LDAP login creates a user with `role='none'` - an admin must activate and assign a role.

---

## Survey Flow

```
Staff creates survey → sends email with 8-char code
    ↓
Customer visits / → enters code → fills out survey (AJAX)
    ↓
Status: open → started → completed
    ↓
Staff reads result → status: evaluation (read_at set)
    ↓
Staff exports PDF / requests reference → archives survey
```

---

## REST API

An external CRM or third-party system can create and read surveys via a simple REST API.

### Authentication

All API requests require a `Bearer` token in the `Authorization` header:

```
Authorization: Bearer fbk_<64-hex-chars>
```

API keys are managed in the backend at `/backend/api-keys` (admin/superadmin). Each key has explicit read (`GET`) and write (`POST`) permissions and an optional expiry date.

### Endpoints

| Method | Path | Permission | Description |
|--------|------|------------|-------------|
| `POST` | `/api/v1/surveys` | write | Create a new survey |
| `GET` | `/api/v1/surveys/{id}` | read | Retrieve survey details |

### Create a survey (`POST /api/v1/surveys`)

```json
{
  "customer_name":       "Mustermann GmbH",
  "customer_email":      "kontakt@mustermann.de",
  "contact_person":      "Erika Musterfrau",
  "area_ids":            [1, 3],
  "project_name":        "Projekt Alpha",
  "project_id":          "P-2026-042",
  "sales_user":          "Max Muster",
  "project_lead":        "Anna Beispiel",
  "metropolregion":      "München",
  "crm_id":              12345,
  "created_by":          "Max Muster",
  "reference_requested": true
}
```

Required: `customer_name`, `area_ids` (array of integer IDs, min. 1). All other fields are optional.  
`sales_user`, `project_lead`, `metropolregion`, and `created_by` are looked up by name — unmatched values are silently ignored.

Response (201):

```json
{
  "success": true,
  "data": {
    "id":          42,
    "code":        "AB12CD34",
    "crm_id":      12345,
    "backend_url": "https://your-domain.de/backend/befragungen/42"
  }
}
```

---

## Adding Questions

Questions are grouped by **area** (Bereich). Each question has a type:

- `slider` - 1–6 rating scale with optional min/max labels
- `freitext` - open text input
- `slider_freitext` - slider with an optional follow-up text field

Manage areas and questions at `/backend/bereiche` and `/backend/fragen` (admin role required).

---

## Support

Open an issue at [github.com/6c756b/feedback-kundenzufriedenheit/issues](https://github.com/6c756b/feedback-kundenzufriedenheit/issues).

---

## Contributing

Pull requests are welcome. The project intentionally has no build pipeline - PHP, plain CSS, and vanilla JS only. Keep the zero-dependency approach: no Composer packages, no npm.

1. Fork the repository
2. Configure local dev (`config.example.php` → `config.php`, `env='local'`)
3. Run `bash dev/start.sh`
4. Make your changes, test against the local SQLite DB
5. Open a pull request

---

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for a full version history.

---

## License

MIT - see [LICENSE](LICENSE)
