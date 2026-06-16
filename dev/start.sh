#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
PORT="${KZB_PORT:-8080}"
DB_PATH="$ROOT/database/feedback.sqlite"

cd "$ROOT"

# ── Voraussetzungen prüfen ───────────────────────────────────────────────────
if ! command -v php &>/dev/null; then
    echo "FEHLER: PHP ist nicht installiert oder nicht im PATH."
    exit 1
fi

if [ ! -f config.php ]; then
    if [ -f config.local.example.php ]; then
        echo "config.php nicht gefunden – kopiere config.local.example.php → config.php"
        cp config.local.example.php config.php
        echo "Bitte config.php prüfen und ggf. anpassen, dann erneut starten."
        exit 0
    else
        echo "FEHLER: Weder config.php noch config.local.example.php gefunden."
        exit 1
    fi
fi

# ── Umgebungsmodus prüfen ────────────────────────────────────────────────────
ENV_MODE=$(php -r "define('ROOT','$ROOT'); \$c=require '$ROOT/config.php'; echo \$c['env'] ?? 'production';")
if [ "$ENV_MODE" != "local" ]; then
    echo "FEHLER: config.php hat env='$ENV_MODE' – für lokale Entwicklung muss env='local' gesetzt sein."
    exit 1
fi

# ── SQLite-Datenbank anlegen wenn nicht vorhanden ───────────────────────────
if [ ! -f "$DB_PATH" ]; then
    echo "SQLite-Datenbank wird erstellt: $DB_PATH"
    php -r "
        \$pdo = new PDO('sqlite:$DB_PATH');
        \$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        \$sql = file_get_contents('$ROOT/database/schema_sqlite.sql');
        \$pdo->exec(\$sql);
        echo 'Schema angelegt.' . PHP_EOL;
    "
    echo "Seed-Daten werden importiert ..."
    php "$ROOT/database/seed_sqlite.php"
    echo ""
else
    echo "SQLite-Datenbank vorhanden: $DB_PATH"
    php "$ROOT/database/migrate_sqlite.php"
fi

# ── Server starten ───────────────────────────────────────────────────────────
echo "──────────────────────────────────────────────"
echo "  Feedback Entwicklungsserver"
echo "  http://localhost:$PORT"
echo "  Beenden mit Ctrl+C"
echo "──────────────────────────────────────────────"
echo ""
exec php -S "localhost:$PORT" -t "$ROOT/public/" "$ROOT/public/router.php"
