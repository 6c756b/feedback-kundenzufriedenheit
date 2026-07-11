-- Migration 003: CRM-API (api_keys-Tabelle, crm_id auf surveys, created_by nullable)
--
-- MySQL / MariaDB:
CREATE TABLE IF NOT EXISTS api_keys (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name         VARCHAR(100)    NOT NULL,
    key_hash     CHAR(64)        NOT NULL UNIQUE,
    user_id      BIGINT UNSIGNED NOT NULL,
    can_read     TINYINT(1)      NOT NULL DEFAULT 1,
    can_write    TINYINT(1)      NOT NULL DEFAULT 1,
    expires_at   DATE            DEFAULT NULL,
    active       TINYINT(1)      NOT NULL DEFAULT 1,
    last_used_at DATETIME        DEFAULT NULL,
    created_at   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE surveys
    ADD COLUMN crm_id INT DEFAULT NULL,
    ADD INDEX  idx_surveys_crm_id (crm_id);

ALTER TABLE surveys MODIFY COLUMN created_by BIGINT UNSIGNED NULL DEFAULT NULL;

-- SQLite:
-- CREATE TABLE IF NOT EXISTS api_keys (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL, key_hash TEXT NOT NULL UNIQUE, user_id INTEGER NOT NULL REFERENCES users(id), can_read INTEGER NOT NULL DEFAULT 1, can_write INTEGER NOT NULL DEFAULT 1, expires_at TEXT DEFAULT NULL, active INTEGER NOT NULL DEFAULT 1, last_used_at TEXT DEFAULT NULL, created_at TEXT NOT NULL DEFAULT (datetime('now')));
-- ALTER TABLE api_keys ADD COLUMN can_read INTEGER NOT NULL DEFAULT 1;
-- ALTER TABLE api_keys ADD COLUMN can_write INTEGER NOT NULL DEFAULT 1;
-- ALTER TABLE api_keys ADD COLUMN expires_at TEXT DEFAULT NULL;
-- ALTER TABLE surveys ADD COLUMN crm_id INTEGER DEFAULT NULL;
-- CREATE INDEX IF NOT EXISTS idx_surveys_crm_id ON surveys(crm_id);
-- PRAGMA foreign_keys = OFF;
-- CREATE TABLE surveys_new (id INTEGER PRIMARY KEY AUTOINCREMENT, code TEXT NOT NULL UNIQUE, customer_name TEXT NOT NULL, project_id TEXT NULL, customer_id TEXT NULL, contact_salutation TEXT NOT NULL DEFAULT '', contact_person TEXT NOT NULL, contact_email TEXT NOT NULL, project_name TEXT NOT NULL, area_id INTEGER NOT NULL, sales_user_id INTEGER NULL, project_lead_id INTEGER NULL, metropolregion_id INTEGER NULL, internal_notes TEXT NULL, reference_requested INTEGER NOT NULL DEFAULT 0, reference_granted INTEGER NULL DEFAULT NULL, status TEXT NOT NULL DEFAULT 'open' CHECK(status IN ('open','started','completed','archived','cancelled')), read_at DATETIME NULL, email_sent_at DATETIME NULL, email_sent_by INTEGER NULL, email_sent_method TEXT NULL CHECK(email_sent_method IN ('system','outlook')), created_by INTEGER NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME DEFAULT CURRENT_TIMESTAMP, crm_id INTEGER DEFAULT NULL, FOREIGN KEY (area_id) REFERENCES areas(id), FOREIGN KEY (sales_user_id) REFERENCES users(id) ON DELETE SET NULL, FOREIGN KEY (project_lead_id) REFERENCES users(id) ON DELETE SET NULL, FOREIGN KEY (metropolregion_id) REFERENCES metropolregionen(id) ON DELETE SET NULL, FOREIGN KEY (email_sent_by) REFERENCES users(id) ON DELETE SET NULL);
-- INSERT INTO surveys_new SELECT id, code, customer_name, project_id, customer_id, contact_salutation, contact_person, contact_email, project_name, area_id, sales_user_id, project_lead_id, metropolregion_id, internal_notes, reference_requested, reference_granted, status, read_at, email_sent_at, email_sent_by, email_sent_method, created_by, created_at, updated_at, crm_id FROM surveys;
-- DROP TABLE surveys;
-- ALTER TABLE surveys_new RENAME TO surveys;
-- CREATE INDEX IF NOT EXISTS idx_surveys_status ON surveys(status);
-- CREATE INDEX IF NOT EXISTS idx_surveys_area_id ON surveys(area_id);
-- CREATE INDEX IF NOT EXISTS idx_surveys_created_by ON surveys(created_by);
-- CREATE INDEX IF NOT EXISTS idx_surveys_crm_id ON surveys(crm_id);
-- PRAGMA foreign_keys = ON;
