<?php
$db = new PDO('sqlite:' . __DIR__ . '/../database/feedback.sqlite');
try {
    $db->exec('ALTER TABLE users ADD COLUMN password_hash TEXT NULL');
    echo "Migration erfolgreich: Spalte password_hash hinzugefügt.\n";
} catch (PDOException $e) {
    if (str_contains($e->getMessage(), 'duplicate column')) {
        echo "Spalte password_hash existiert bereits.\n";
    } else {
        echo "Fehler: " . $e->getMessage() . "\n";
    }
}
