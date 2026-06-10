<?php
use App\Core\Response;

Response::setHeader('X-Frame-Options', 'DENY');
Response::setHeader('X-Content-Type-Options', 'nosniff');

if (!function_exists('e')) {
    function e(string $val): string {
        return htmlspecialchars($val, ENT_QUOTES, 'UTF-8');
    }
}

$config = require ROOT . '/config.php';
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($config['app']['name'] ?? '') ?> - <?= e($config['app']['survey_label'] ?? '') ?></title>
    <link rel="icon" type="image/png" href="<?= e($config['branding']['logo_favicon'] ?? '') ?>">
    <link rel="stylesheet" href="/assets/fonts/fonts.css">
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body class="frontend">

<?php if (empty($isLanding)): ?>
<header class="survey-header">
    <div class="survey-header-inner">
        <a href="/" class="survey-logo-link">
            <img src="<?= e($config['branding']['logo_main'] ?? '') ?>" alt="<?= e($config['branding']['logo_alt'] ?? '') ?>" class="survey-logo">
        </a>
        <span class="survey-slogan"><?= e($config['app']['company_slogan'] ?? '') ?></span>
    </div>
</header>
<?php endif; ?>

<main class="<?= empty($isLanding) ? 'survey-main' : 'lp-main' ?>">
    <?= $content ?>
</main>

<?php if (empty($isLanding)): ?>
<footer class="survey-footer">
    <div class="survey-footer-inner">
        <span>&copy; <?= e($config['app']['company_name'] ?? '') ?></span>
        <?php if (!empty($config['app']['impressum_url'])): ?>
        <a href="<?= e($config['app']['impressum_url']) ?>" target="_blank" rel="noopener">Impressum</a>
        <?php endif; ?>
        <?php if (!empty($config['app']['datenschutz_url'])): ?>
        <a href="<?= e($config['app']['datenschutz_url']) ?>" target="_blank" rel="noopener">Datenschutz</a>
        <?php endif; ?>
    </div>
</footer>
<?php endif; ?>

<script src="/assets/js/app.js"></script>
</body>
</html>
