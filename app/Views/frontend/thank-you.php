<?php $h = fn(string $v) => htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); ?>

<div class="sq-page sq-page-ty">

    <!-- Topstrip (in document flow, not fixed) -->
    <div class="sq-topstrip">
        <a href="/" class="sq-logo-link">
            <img src="<?= $h($config['branding']['logo_main'] ?? '') ?>" alt="<?= $h($config['branding']['logo_alt'] ?? '') ?>" class="sq-logo">
        </a>
        <div class="sq-badge">
            <span class="sq-badge-dot"></span>
            <?= $h($config['app']['company_slogan'] ?? 'Einfach. Sicher. Arbeiten.') ?>
        </div>
    </div>

    <!-- Thank-you content -->
    <div class="sq-ty-main">

        <div class="sq-ty-icon">
            <svg width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <polyline points="20 6 9 17 4 12"/>
            </svg>
        </div>

        <h1 class="sq-ty-title">Vielen Dank!</h1>
        <div class="sq-ty-divider"></div>

        <p class="sq-ty-text">
            <?= nl2br($h($config['survey']['thankyou_text'] ?? '')) ?>
        </p>

        <p class="sq-ty-close">Sie können dieses Fenster jetzt schließen.</p>

    </div>

    <footer class="sq-footer">
        <?php if (!empty($config['app']['impressum_url']) || !empty($config['app']['datenschutz_url'])): ?>
        <div class="sq-footer-links">
            <?php if (!empty($config['app']['impressum_url'])): ?>
            <a href="<?= $h($config['app']['impressum_url']) ?>" target="_blank" rel="noopener">Impressum</a>
            <?php endif; ?>
            <?php if (!empty($config['app']['impressum_url']) && !empty($config['app']['datenschutz_url'])): ?>
            <span class="sq-footer-dot" aria-hidden="true"></span>
            <?php endif; ?>
            <?php if (!empty($config['app']['datenschutz_url'])): ?>
            <a href="<?= $h($config['app']['datenschutz_url']) ?>" target="_blank" rel="noopener">Datenschutz</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        <span>&copy; <?= $h($config['app']['company_name'] ?? '') ?></span>
    </footer>

</div><!-- .sq-page -->
