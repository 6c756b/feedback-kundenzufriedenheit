-- Migration 001: Lokales Passwort-Fallback
-- Ausführen auf bestehenden Datenbanken, die mit schema.sql (MySQL)
-- oder schema_sqlite.sql (SQLite) angelegt wurden.
--
-- MySQL / MariaDB:
ALTER TABLE users ADD COLUMN password_hash VARCHAR(255) NULL AFTER dashboard_filters;
--
-- SQLite (separate Ausführung, da kein Semikolon-Batch):
-- ALTER TABLE users ADD COLUMN password_hash TEXT NULL;
