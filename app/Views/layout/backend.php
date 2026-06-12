<?php
use App\Core\Auth;
use App\Core\Response;

if (!Auth::check()) {
    Response::redirect('/backend/login');
}

$user = Auth::user();

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
    <link rel="preload" href="/assets/fonts/pt-sans-400-latin.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="/assets/fonts/pt-sans-700-latin.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="/assets/fonts/fonts.css">
    <link rel="stylesheet" href="/assets/css/app.css">
    <meta name="csrf-token" content="<?= htmlspecialchars(\App\Core\Csrf::token(), ENT_QUOTES, 'UTF-8') ?>">
    <?php if (!empty($needsChartJs)): ?>
    <script src="/assets/vendor/chart.js/chart.umd.min.js" defer></script>
    <?php endif; ?>
    <?php if (!empty($needsQuill)): ?>
    <link rel="stylesheet" href="/assets/vendor/quill/quill.snow.css">
    <?php endif; ?>
</head>
<body class="backend">

<nav class="main-nav">
    <div class="nav-brand">
        <a href="/backend" style="display:flex;align-items:center;gap:8px;text-decoration:none">
            <img src="<?= e($config['branding']['logo_nav'] ?? '') ?>" alt="" class="nav-brand-logo">
            <?= e($config['app']['name'] ?? '') ?>
        </a>
    </div>
    <?php $uri = strtok($_SERVER['REQUEST_URI'] ?? '/', '?'); ?>
    <ul class="nav-links">
        <li><a href="/backend" <?= $uri === '/backend' ? 'class="active"' : '' ?>>Dashboard</a></li>
        <li><a href="/backend/befragungen" <?= str_starts_with($uri, '/backend/befragungen') ? 'class="active"' : '' ?>>Befragungen</a></li>
        <li><a href="/backend/auswertung" <?= str_starts_with($uri, '/backend/auswertung') ? 'class="active"' : '' ?>>Auswertung</a></li>
        <li><a href="/backend/archiv" <?= str_starts_with($uri, '/backend/archiv') ? 'class="active"' : '' ?>>Archiv</a></li>
        <?php if (Auth::hasRole('admin')): ?>
        <li><a href="/backend/fragen" <?= str_starts_with($uri, '/backend/fragen') ? 'class="active"' : '' ?>>Fragen</a></li>
        <?php endif; ?>
        <?php if (Auth::hasRole('admin')): ?>
        <li><a href="/backend/benutzer" <?= str_starts_with($uri, '/backend/benutzer') ? 'class="active"' : '' ?>>Benutzer</a></li>
        <?php endif; ?>
        <?php if (Auth::hasRole('superadmin')): ?>
        <li><a href="/backend/bereiche" <?= str_starts_with($uri, '/backend/bereiche') ? 'class="active"' : '' ?>>Bereiche</a></li>
        <li><a href="/backend/metropolregionen" <?= str_starts_with($uri, '/backend/metropolregionen') ? 'class="active"' : '' ?>>Metropolregionen</a></li>
        <li><a href="/backend/logs" <?= str_starts_with($uri, '/backend/logs') ? 'class="active"' : '' ?>>Protokoll</a></li>
        <?php endif; ?>
    </ul>
    <div class="nav-user">
        <a href="/backend/profil" class="nav-user-name<?= str_starts_with($uri, '/backend/profil') ? ' active' : '' ?>"><?= e($user['name'] ?? '') ?></a>
        <form method="post" action="/backend/logout" style="display:inline">
            <?= \App\Core\Csrf::field() ?>
            <button type="submit" class="btn-ghost btn-sm">Abmelden</button>
        </form>
    </div>
</nav>

<?php if (!empty($chartData)): ?>
<script>window.__chartData = <?= json_encode($chartData) ?>;</script>
<?php endif; ?>

<main class="main-content">
    <?php require ROOT . '/app/Views/partials/flash.php' ?>
    <?= $content ?>
</main>

<?php if (!empty($needsQuill)): ?>
<script src="/assets/vendor/quill/quill.min.js"></script>
<?php endif; ?>
<script src="/assets/js/app.js"></script>
</body>
</html>
