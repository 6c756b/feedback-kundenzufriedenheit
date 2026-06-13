-- Feedback – SQLite-Schema für lokale Entwicklung
-- Entspricht schema.sql, angepasst für SQLite 3.35+

PRAGMA foreign_keys = OFF;

CREATE TABLE IF NOT EXISTS areas (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    name        TEXT    NOT NULL,
    description TEXT    NULL,
    sort_order  INTEGER NOT NULL DEFAULT 0,
    active      INTEGER NOT NULL DEFAULT 1,
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS questions (
    id               INTEGER PRIMARY KEY AUTOINCREMENT,
    area_id          INTEGER NOT NULL,
    label_short      TEXT    NOT NULL,
    label_long       TEXT    NOT NULL,
    type             TEXT    NOT NULL CHECK(type IN ('slider','freitext','slider_freitext')),
    freitext_context TEXT    NULL,
    slider_label_min TEXT    NULL,
    slider_label_max TEXT    NULL,
    sequence         INTEGER NOT NULL DEFAULT 0,
    active           INTEGER NOT NULL DEFAULT 1,
    created_at       DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at       DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (area_id) REFERENCES areas(id)
);

CREATE TABLE IF NOT EXISTS users (
    id                INTEGER PRIMARY KEY AUTOINCREMENT,
    name              TEXT    NOT NULL,
    email             TEXT    NOT NULL UNIQUE,
    role              TEXT    NOT NULL DEFAULT 'none' CHECK(role IN ('none','reader','staff','admin','superadmin')),
    is_sales          INTEGER NOT NULL DEFAULT 0,
    is_projectlead    INTEGER NOT NULL DEFAULT 0,
    display_name      TEXT    NOT NULL DEFAULT '',
    job_title         TEXT    NOT NULL DEFAULT '',
    phone             TEXT    NOT NULL DEFAULT '',
    signature_image   TEXT    NOT NULL DEFAULT '',
    active            INTEGER NOT NULL DEFAULT 1,
    dashboard_filters TEXT    NULL,
    password_hash     TEXT    NULL,
    login_method      TEXT    NOT NULL DEFAULT 'ldap' CHECK(login_method IN ('ldap','local','both')),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS metropolregionen (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    name       TEXT    NOT NULL,
    sort_order INTEGER NOT NULL DEFAULT 0
);

INSERT OR IGNORE INTO metropolregionen (id, name, sort_order) VALUES
(1,  'Bremen/ Oldenburg',     1),
(2,  'Frankfurt/ Rhein-Main', 2),
(3,  'Hamburg',               3),
(4,  'Hannover',              4),
(5,  'München',               5),
(6,  'Münster/ Osnabrück',    6),
(7,  'Nürnberg',              7),
(8,  'OWL',                   8),
(9,  'Rheinland',             9),
(10, 'Rhein-Neckar',         10),
(11, 'Ruhrgebiet',           11),
(12, 'Stuttgart',            12),
(13, 'Ausland',              13);

CREATE TABLE IF NOT EXISTS surveys (
    id                  INTEGER PRIMARY KEY AUTOINCREMENT,
    code                TEXT    NOT NULL UNIQUE,
    customer_name       TEXT    NOT NULL,
    project_id          TEXT    NULL,
    customer_id         TEXT    NULL,
    contact_salutation  TEXT    NOT NULL DEFAULT '',
    contact_person      TEXT    NOT NULL,
    contact_email       TEXT    NOT NULL,
    project_name        TEXT    NOT NULL,
    area_id             INTEGER NOT NULL,
    sales_user_id       INTEGER NULL,
    project_lead_id     INTEGER NULL,
    metropolregion_id   INTEGER NULL,
    internal_notes      TEXT    NULL,
    reference_requested INTEGER NOT NULL DEFAULT 0,
    reference_granted   INTEGER NULL     DEFAULT NULL,
    status              TEXT    NOT NULL DEFAULT 'open' CHECK(status IN ('open','started','completed','archived','cancelled')),
    read_at             DATETIME NULL,
    email_sent_at       DATETIME NULL,
    email_sent_by       INTEGER NULL,
    email_sent_method   TEXT NULL CHECK(email_sent_method IN ('system','outlook')),
    created_by          INTEGER NOT NULL,
    created_at          DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at          DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (area_id)           REFERENCES areas(id),
    FOREIGN KEY (created_by)        REFERENCES users(id),
    FOREIGN KEY (sales_user_id)     REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (project_lead_id)   REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (metropolregion_id) REFERENCES metropolregionen(id) ON DELETE SET NULL,
    FOREIGN KEY (email_sent_by)     REFERENCES users(id) ON DELETE SET NULL
);

CREATE INDEX IF NOT EXISTS idx_surveys_status     ON surveys(status);
CREATE INDEX IF NOT EXISTS idx_surveys_area_id    ON surveys(area_id);
CREATE INDEX IF NOT EXISTS idx_surveys_created_by ON surveys(created_by);

CREATE TABLE IF NOT EXISTS survey_areas (
    survey_id INTEGER NOT NULL,
    area_id   INTEGER NOT NULL,
    PRIMARY KEY (survey_id, area_id),
    FOREIGN KEY (survey_id) REFERENCES surveys(id) ON DELETE CASCADE,
    FOREIGN KEY (area_id)   REFERENCES areas(id)
);

CREATE TABLE IF NOT EXISTS survey_answers (
    id             INTEGER PRIMARY KEY AUTOINCREMENT,
    survey_id      INTEGER NOT NULL,
    question_id    INTEGER NOT NULL,
    answer_type    TEXT    NOT NULL CHECK(answer_type IN ('slider','freitext','not_applicable')),
    slider_value   REAL    NULL,
    freitext_value TEXT    NULL,
    created_at     DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at     DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (survey_id, question_id),
    FOREIGN KEY (survey_id)   REFERENCES surveys(id),
    FOREIGN KEY (question_id) REFERENCES questions(id)
);

CREATE TABLE IF NOT EXISTS logs (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id     INTEGER NULL,
    actor_type  TEXT    NOT NULL CHECK(actor_type IN ('backend','frontend')),
    action      TEXT    NOT NULL,
    entity_type TEXT    NULL,
    entity_id   INTEGER NULL,
    description TEXT    NULL,
    ip_address  TEXT    NULL,
    survey_code TEXT    NULL,
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS login_attempts (
    id           INTEGER PRIMARY KEY AUTOINCREMENT,
    ip_address   TEXT    NOT NULL,
    attempted_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_login_attempts_ip_time ON login_attempts(ip_address, attempted_at);

PRAGMA foreign_keys = ON;
