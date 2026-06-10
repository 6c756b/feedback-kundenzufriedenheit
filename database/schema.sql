-- Feedback – Kundenzufriedenheitsbefragung
-- Datenbankschema v1.0
-- Charset: utf8mb4 | Engine: InnoDB

SET NAMES utf8mb4;
SET foreign_key_checks = 0;

CREATE TABLE IF NOT EXISTS areas (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(255) NOT NULL,
    description TEXT NULL,
    sort_order  INT NOT NULL DEFAULT 0,
    active      TINYINT(1) NOT NULL DEFAULT 1,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS questions (
    id               BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    area_id          BIGINT UNSIGNED NOT NULL,
    label_short      VARCHAR(255) NOT NULL,
    label_long       TEXT NOT NULL,
    type             ENUM('slider','freitext','slider_freitext') NOT NULL,
    freitext_context VARCHAR(255) NULL,
    slider_label_min VARCHAR(100) NULL,
    slider_label_max VARCHAR(100) NULL,
    sequence         INT NOT NULL DEFAULT 0,
    active           TINYINT(1) NOT NULL DEFAULT 1,
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (area_id) REFERENCES areas(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS users (
    id                 BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name               VARCHAR(255) NOT NULL,
    email              VARCHAR(255) NOT NULL UNIQUE,
    role               ENUM('none','reader','staff','admin','superadmin') NOT NULL DEFAULT 'none',
    is_sales           TINYINT(1) NOT NULL DEFAULT 0,
    is_projectlead     TINYINT(1) NOT NULL DEFAULT 0,
    display_name       VARCHAR(255) NOT NULL DEFAULT '',
    job_title          VARCHAR(255) NOT NULL DEFAULT '',
    phone              VARCHAR(100) NOT NULL DEFAULT '',
    signature_image    MEDIUMTEXT   NOT NULL DEFAULT '',
    active             TINYINT(1) NOT NULL DEFAULT 1,
    dashboard_filters  TEXT NULL,
    created_at         TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at         TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS metropolregionen (
    id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(255) NOT NULL,
    sort_order INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO metropolregionen (name, sort_order) VALUES
('Bremen/ Oldenburg',    1),
('Frankfurt/ Rhein-Main',2),
('Hamburg',              3),
('Hannover',             4),
('München',              5),
('Münster/ Osnabrück',   6),
('Nürnberg',             7),
('OWL',                  8),
('Rheinland',            9),
('Rhein-Neckar',        10),
('Ruhrgebiet',          11),
('Stuttgart',           12),
('Ausland',             13);

-- Kein Passwort-Feld: Auth läuft über LDAP

CREATE TABLE IF NOT EXISTS surveys (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code                VARCHAR(8) NOT NULL UNIQUE,
    customer_name       VARCHAR(255) NOT NULL,
    project_id          VARCHAR(100) NULL,
    customer_id         VARCHAR(100) NULL,              -- reserved for future ERP integration, not yet used
    contact_salutation  VARCHAR(10) NOT NULL DEFAULT '',
    contact_person      VARCHAR(255) NOT NULL,
    contact_email       VARCHAR(255) NOT NULL,
    project_name        VARCHAR(255) NOT NULL,
    area_id             BIGINT UNSIGNED NOT NULL,
    sales_user_id       BIGINT UNSIGNED NULL,
    project_lead_id     BIGINT UNSIGNED NULL,
    metropolregion_id   BIGINT UNSIGNED NULL,
    internal_notes      TEXT NULL,
    reference_requested TINYINT(1) NOT NULL DEFAULT 0,
    reference_granted   TINYINT(1) NULL DEFAULT NULL,
    status              ENUM('open','started','completed','archived','cancelled') NOT NULL DEFAULT 'open',
    read_at             TIMESTAMP NULL,
    email_sent_at       DATETIME NULL,
    email_sent_by       BIGINT UNSIGNED NULL,
    email_sent_method   ENUM('system','outlook') NULL,
    created_by          BIGINT UNSIGNED NOT NULL,
    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (area_id)          REFERENCES areas(id),
    FOREIGN KEY (created_by)       REFERENCES users(id),
    FOREIGN KEY (sales_user_id)    REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (project_lead_id)  REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (metropolregion_id) REFERENCES metropolregionen(id) ON DELETE SET NULL,
    FOREIGN KEY (email_sent_by)    REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_status     (status),
    INDEX idx_area_id    (area_id),
    INDEX idx_created_by (created_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS survey_areas (
    survey_id BIGINT UNSIGNED NOT NULL,
    area_id   BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (survey_id, area_id),
    FOREIGN KEY (survey_id) REFERENCES surveys(id) ON DELETE CASCADE,
    FOREIGN KEY (area_id)   REFERENCES areas(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS survey_answers (
    id             BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    survey_id      BIGINT UNSIGNED NOT NULL,
    question_id    BIGINT UNSIGNED NOT NULL,
    answer_type    ENUM('slider','freitext','not_applicable') NOT NULL,
    slider_value   DECIMAL(2,1) NULL,
    freitext_value TEXT NULL,
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_survey_question (survey_id, question_id),
    FOREIGN KEY (survey_id)   REFERENCES surveys(id),
    FOREIGN KEY (question_id) REFERENCES questions(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS logs (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     BIGINT UNSIGNED NULL,
    actor_type  ENUM('backend','frontend') NOT NULL,
    action      VARCHAR(100) NOT NULL,
    entity_type VARCHAR(50) NULL,
    entity_id   BIGINT UNSIGNED NULL,
    description TEXT NULL,
    ip_address  VARCHAR(45) NULL,
    survey_code VARCHAR(8) NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS login_attempts (
    id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ip_address   VARCHAR(45) NOT NULL,
    attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ip_time (ip_address, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET foreign_key_checks = 1;
