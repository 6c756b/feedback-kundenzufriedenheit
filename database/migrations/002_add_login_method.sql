-- Migration 002: Login-Methode pro Benutzer
-- Werte: ldap (nur LDAP), local (nur lokales Passwort), both (LDAP + Fallback)
--
-- MySQL / MariaDB:
ALTER TABLE users ADD COLUMN login_method ENUM('ldap','local','both') NOT NULL DEFAULT 'ldap' AFTER password_hash;
--
-- SQLite:
-- ALTER TABLE users ADD COLUMN login_method TEXT NOT NULL DEFAULT 'ldap' CHECK(login_method IN ('ldap','local','both'));
