<?php
use App\Core\Response;

Response::setHeader('X-Frame-Options', 'DENY');
Response::setHeader('X-Content-Type-Options', 'nosniff');

function e(string $val): string {
    return htmlspecialchars($val, ENT_QUOTES, 'UTF-8');
}
$config = require ROOT . '/config.php';
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($config['app']['name'] ?? '') ?> - <?= e($pageTitle ?? '') ?></title>
    <link rel="icon" type="image/png" href="<?= e($config['branding']['logo_favicon'] ?? '') ?>">
    <link rel="stylesheet" href="/assets/fonts/fonts.css">
    <link rel="stylesheet" href="/assets/css/common.css">
    <link rel="stylesheet" href="/assets/css/backend.css">
</head>
<body class="backend auth-page">
<main class="auth-wrap">
    <?php require ROOT . '/app/Views/partials/flash.php' ?>
    <?= $content ?>
</main>
</body>
</html>
