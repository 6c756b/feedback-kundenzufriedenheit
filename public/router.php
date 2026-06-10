<?php
// Router für PHP built-in server (php -S localhost:8080 -t public/ public/router.php)
// Statische Dateien (CSS, JS, Bilder) werden direkt ausgeliefert; alles andere → index.php
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if ($path !== '/' && file_exists(__DIR__ . $path)) {
    return false;
}
require __DIR__ . '/index.php';
