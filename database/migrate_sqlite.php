<?php
// Migration-Runner für SQLite.
// Liest *.sql-Dateien aus database/migrations/, extrahiert SQLite-spezifische
// Statements (Zeilen nach dem "-- SQLite"-Marker, die mit "-- " beginnen)
// und trackt angewandte Migrationen in der Tabelle _migrations.

$dbPath = __DIR__ . '/kzb.sqlite';
$migrationsDir = __DIR__ . '/migrations';

$pdo = new PDO('sqlite:' . $dbPath);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$pdo->exec('CREATE TABLE IF NOT EXISTS _migrations (
    name       TEXT PRIMARY KEY,
    applied_at TEXT NOT NULL
)');

$applied = $pdo->query('SELECT name FROM _migrations')->fetchAll(PDO::FETCH_COLUMN);

$files = glob($migrationsDir . '/*.sql');
sort($files);

$ran = 0;
foreach ($files as $file) {
    $name = basename($file);
    if (in_array($name, $applied)) {
        continue;
    }

    echo "Migration: $name\n";

    $statements = extractSqliteStatements(file_get_contents($file));

    foreach ($statements as $stmt) {
        try {
            $pdo->exec($stmt);
        } catch (PDOException $e) {
            if (str_contains($e->getMessage(), 'duplicate column name')) {
                echo "  Spalte existiert bereits – übersprungen.\n";
            } else {
                throw $e;
            }
        }
    }

    $stmt = $pdo->prepare('INSERT INTO _migrations (name, applied_at) VALUES (?, datetime(\'now\'))');
    $stmt->execute([$name]);
    echo "  Erledigt.\n";
    $ran++;
}

if ($ran === 0) {
    echo "Keine neuen Migrationen.\n";
}

function extractSqliteStatements(string $sql): array
{
    $lines = explode("\n", $sql);
    $inSqlite = false;
    $statements = [];

    foreach ($lines as $line) {
        $trimmed = trim($line);

        if (str_contains($trimmed, '-- SQLite')) {
            $inSqlite = true;
            continue;
        }

        if (!$inSqlite) {
            continue;
        }

        if (str_starts_with($trimmed, '-- ')) {
            $stmt = trim(substr($trimmed, 3));
            if ($stmt !== '') {
                $statements[] = $stmt;
            }
        } elseif ($trimmed !== '' && $trimmed !== '--') {
            // Ende des SQLite-Blocks wenn nicht-leere, nicht-kommentierte Zeile folgt
            $inSqlite = false;
        }
    }

    return $statements;
}
