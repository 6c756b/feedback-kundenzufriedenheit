<?php
use App\Core\Auth;
use App\Core\Response;

if (!Auth::check()) {
    Response::redirect('/backend/login');
}

$user = Auth::user();

Response::setHeader('X-Frame-Options', 'DENY');
Response::setHeader('X-Content-Type-Options', 'nosniff');

if (!function_exists('e')) {
    function e(string $val): string {
        return htmlspecialchars($val, ENT_QUOTES, 'UTF-8');
    }
}
$config  = require ROOT . '/config.php';
$appVersion = is_file(ROOT . '/VERSION') ? trim(file_get_contents(ROOT . '/VERSION')) : '';
$uri = strtok($_SERVER['REQUEST_URI'] ?? '/', '?');

if (!function_exists('beNavLink')) {
    function beNavLink(string $href, string $label, string $icon, string $currentUri, bool $startsWith = true): string {
        $active = $startsWith ? str_starts_with($currentUri, $href) : $currentUri === $href;
        if ($href === '/backend' && $startsWith) {
            $active = $currentUri === '/backend';
        }
        $cls = $active ? ' is-active' : '';
        return '<a href="' . htmlspecialchars($href, ENT_QUOTES) . '" class="be-nav-link' . $cls . '">'
             . '<svg class="be-nav-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">' . $icon . '</svg>'
             . htmlspecialchars($label, ENT_QUOTES)
             . '</a>';
    }
}

$icons = [
    'dashboard'  => '<path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z"/>',
    'surveys'    => '<path stroke-linecap="round" stroke-linejoin="round" d="M15.666 3.888A2.25 2.25 0 0 0 13.5 2.25h-3c-1.03 0-1.9.693-2.166 1.638m7.332 0c.055.194.084.4.084.612v0a.75.75 0 0 1-.75.75H9a.75.75 0 0 1-.75-.75v0c0-.212.03-.418.084-.612m7.332 0c.646.049 1.288.11 1.927.184 1.1.128 1.907 1.077 1.907 2.185V19.5a2.25 2.25 0 0 1-2.25 2.25H6.75A2.25 2.25 0 0 1 4.5 19.5V6.257c0-1.108.806-2.057 1.907-2.185a48.208 48.208 0 0 1 1.927-.184"/>',
    'evaluation' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z"/>',
    'archive'    => '<path stroke-linecap="round" stroke-linejoin="round" d="m20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z"/>',
    'questions'  => '<path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 5.25h.008v.008H12v-.008Z"/>',
    'users'      => '<path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z"/>',
    'areas'      => '<path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L9.568 3Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6Z"/>',
    'regions'    => '<path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z"/>',
    'logs'       => '<path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/>',
    'apikeys'    => '<path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 0 1 3 3m3 0a6 6 0 0 1-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 0 1 21.75 8.25Z"/>',
    'profile'    => '<path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0 0 12 15.75a7.488 7.488 0 0 0-5.982 2.975m11.963 0a9 9 0 1 0-11.963 0m11.963 0A8.966 8.966 0 0 1 12 21a8.966 8.966 0 0 1-5.982-2.275M15 9.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>',
    'logout'     => '<path stroke-linecap="round" stroke-linejoin="round" d="M8.25 9V5.25A2.25 2.25 0 0 1 10.5 3h6a2.25 2.25 0 0 1 2.25 2.25v13.5A2.25 2.25 0 0 1 16.5 21h-6a2.25 2.25 0 0 1-2.25-2.25V15m-3 0-3-3m0 0 3-3m-3 3H15"/>',
];
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
    <link rel="stylesheet" href="/assets/css/common.css">
    <link rel="stylesheet" href="/assets/css/backend.css">
    <meta name="csrf-token" content="<?= htmlspecialchars(\App\Core\Csrf::token(), ENT_QUOTES, 'UTF-8') ?>">
    <?php if (!empty($needsChartJs)): ?>
    <script src="/assets/vendor/chart.js/chart.umd.min.js" defer></script>
    <?php endif; ?>
    <?php if (!empty($needsQuill)): ?>
    <link rel="stylesheet" href="/assets/vendor/quill/quill.snow.css">
    <?php endif; ?>
</head>
<body class="backend">

<div class="be-sidebar-overlay" id="be-sidebar-overlay"></div>

<header class="be-topbar">
    <button class="be-menu-toggle" id="be-menu-toggle" aria-label="Navigation öffnen">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:22px;height:22px">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/>
        </svg>
    </button>
    <span class="be-topbar-brand"><?= e($config['app']['name'] ?? '') ?></span>
</header>

<div class="be-wrap">

    <aside class="be-sidebar">
        <div class="be-sidebar-brand">
            <a href="/backend">
                <img src="<?= e($config['branding']['logo_nav'] ?? '') ?>" alt="" class="be-sidebar-brand-logo">
                <span class="be-sidebar-brand-name"><?= e($config['app']['name'] ?? '') ?></span>
            </a>
        </div>

        <nav class="be-sidebar-nav">
            <?= beNavLink('/backend', 'Dashboard', $icons['dashboard'], $uri) ?>
            <?= beNavLink('/backend/befragungen', 'Befragungen', $icons['surveys'], $uri) ?>
            <?= beNavLink('/backend/auswertung', 'Auswertung', $icons['evaluation'], $uri) ?>
            <?= beNavLink('/backend/archiv', 'Archiv', $icons['archive'], $uri) ?>

            <?php if (Auth::hasRole('admin')): ?>
            <div class="be-nav-group-label">Administration</div>
            <?= beNavLink('/backend/fragen', 'Fragen', $icons['questions'], $uri) ?>
            <?= beNavLink('/backend/benutzer', 'Benutzer', $icons['users'], $uri) ?>
            <?php endif; ?>

            <?php if (Auth::hasRole('superadmin')): ?>
            <div class="be-nav-group-label">System</div>
            <?= beNavLink('/backend/bereiche', 'Bereiche', $icons['areas'], $uri) ?>
            <?= beNavLink('/backend/metropolregionen', 'Metropolregionen', $icons['regions'], $uri) ?>
            <?= beNavLink('/backend/logs', 'Protokoll', $icons['logs'], $uri) ?>
            <?php endif; ?>
        </nav>

        <?php if ($appVersion): ?>
        <div class="be-sidebar-version">v<?= e($appVersion) ?></div>
        <?php endif; ?>
        <div class="be-sidebar-footer">
            <div class="be-sidebar-user">
                <a href="/backend/profil" class="be-sidebar-user-name" title="Profil bearbeiten">
                    <?= e($user['name'] ?? '') ?>
                </a>
                <form method="post" action="/backend/logout">
                    <?= \App\Core\Csrf::field() ?>
                    <button type="submit" class="be-sidebar-logout" title="Abmelden">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <?= $icons['logout'] ?>
                        </svg>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    <div class="be-body<?= isset($panelContent) ? ' be-body--has-panel' : '' ?>">
        <?php if (isset($panelContent)): ?>
        <aside class="be-panel">
            <button class="be-panel-mobile-toggle" aria-expanded="false">
                Navigation
                <svg class="be-panel-toggle-chevron" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:16px;height:16px">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/>
                </svg>
            </button>
            <div class="be-panel-content">
                <?= $panelContent ?>
            </div>
        </aside>
        <?php endif; ?>

        <main class="be-main">
            <?php if (!empty($chartData)): ?>
            <script>window.__chartData = <?= json_encode($chartData) ?>;</script>
            <?php endif; ?>
            <?php require ROOT . '/app/Views/partials/flash.php' ?>
            <?= $content ?>
        </main>
    </div>

</div>

<?php if (!empty($needsQuill)): ?>
<script src="/assets/vendor/quill/quill.min.js"></script>
<?php endif; ?>
<script src="/assets/js/app.js"></script>
<script>
function dismissFlash(el) {
    el.style.transition = 'opacity 0.3s, transform 0.3s';
    el.style.opacity = '0';
    el.style.transform = 'translateY(10px)';
    setTimeout(function() { el.remove(); }, 300);
}
document.querySelectorAll('.flash-success, .flash-info').forEach(function(el) {
    setTimeout(function() { dismissFlash(el); }, 4000);
});
document.querySelectorAll('.flash-close').forEach(function(btn) {
    btn.addEventListener('click', function() { dismissFlash(btn.closest('.flash')); });
});
</script>
</body>
</html>
