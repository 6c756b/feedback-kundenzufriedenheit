$ErrorActionPreference = "Stop"

$ROOT    = Split-Path -Parent $PSScriptRoot
$PORT    = if ($env:KZB_PORT) { $env:KZB_PORT } else { "8080" }
$DB_PATH = Join-Path $ROOT "database\feedback.sqlite"

Push-Location $ROOT
try {
    if (-not (Get-Command php -ErrorAction SilentlyContinue)) {
        Write-Host "FEHLER: PHP nicht gefunden." -ForegroundColor Red; exit 1
    }

    if (-not (Test-Path "config.php")) {
        if (Test-Path "config.example.php") {
            Copy-Item "config.example.php" "config.php"
            Write-Host "config.php angelegt - bitte env='local' setzen und neu starten."
            exit 0
        }
        Write-Host "FEHLER: config.php fehlt." -ForegroundColor Red; exit 1
    }

    $ENV_MODE = php -r "`$c = require 'config.php'; echo `$c['env'] ?? 'production';"
    if ($ENV_MODE -ne "local") {
        Write-Host "FEHLER: env='$ENV_MODE' - bitte env='local' in config.php setzen." -ForegroundColor Red
        exit 1
    }

    if (-not (Test-Path $DB_PATH)) {
        Write-Host "SQLite-Datenbank wird erstellt: $DB_PATH"
        php "dev\setup_db.php"
    } else {
        Write-Host "SQLite-Datenbank vorhanden: $DB_PATH"
        if (Test-Path "database\migrate_sqlite.php") { php "database\migrate_sqlite.php" }
    }

    Write-Host "----------------------------------------------"
    Write-Host "  Feedback Entwicklungsserver"
    $LocalIP = (Get-NetIPAddress -AddressFamily IPv4 | Where-Object { $_.InterfaceAlias -notmatch 'Loopback' -and $_.IPAddress -ne '127.0.0.1' } | Select-Object -First 1).IPAddress
    Write-Host "  http://localhost:$PORT  (lokal)"
    if ($LocalIP) { Write-Host "  http://${LocalIP}:$PORT  (Netzwerk)" }
    Write-Host "  Beenden mit Ctrl+C"
    Write-Host "----------------------------------------------"
    php -S "0.0.0.0:$PORT" -t "public" "public\router.php"
} finally {
    Pop-Location
}