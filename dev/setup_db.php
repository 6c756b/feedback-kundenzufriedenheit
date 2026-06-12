<?php
$root   = dirname(__DIR__);
$dbPath = $root . '/database/feedback.sqlite';

$pdo = new PDO('sqlite:' . $dbPath);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->exec(file_get_contents($root . '/database/schema_sqlite.sql'));
echo 'Schema angelegt.' . PHP_EOL;

$seed = $root . '/database/seed_sqlite.php';
if (file_exists($seed)) {
    echo 'Seed-Daten werden importiert ...' . PHP_EOL;
    require $seed;
}