<?php $h = fn(string $v) => htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); ?>

<div class="sq-page sq-page-ty">

    <!-- Topstrip (in document flow, not fixed) -->
    <div class="sq-topstrip">
        <a href="/" class="sq-logo-link">
            <img src="<?= $h($config['branding']['logo_main'] ?? '') ?>" alt="<?= $h($config['branding']['logo_alt'] ?? '') ?>" class="sq-logo"<?php if (!empty($config['branding']['logo_height'])): ?> style="height:<?= $h($config['branding']['logo_height']) ?>"<?php endif; ?>>
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
        <div class="sq-footer-links">
            <?php if (!empty($config['app']['impressum_url'])): ?>
            <a href="<?= $h($config['app']['impressum_url']) ?>" target="_blank" rel="noopener">Impressum</a>
            <span class="sq-footer-dot" aria-hidden="true"></span>
            <?php endif; ?>
            <?php if (!empty($config['app']['datenschutz_url'])): ?>
            <a href="<?= $h($config['app']['datenschutz_url']) ?>" target="_blank" rel="noopener">Datenschutz</a>
            <span class="sq-footer-dot" aria-hidden="true"></span>
            <?php endif; ?>
            <a href="https://github.com/6c756b/feedback-kundenzufriedenheit" target="_blank" rel="noopener" class="sq-footer-github">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="12" height="12" aria-hidden="true"><path d="M12 0C5.37 0 0 5.37 0 12c0 5.3 3.438 9.8 8.205 11.385.6.113.82-.258.82-.577 0-.285-.01-1.04-.015-2.04-3.338.724-4.042-1.61-4.042-1.61-.546-1.385-1.335-1.755-1.335-1.755-1.087-.744.084-.729.084-.729 1.205.084 1.838 1.236 1.838 1.236 1.07 1.835 2.809 1.305 3.495.998.108-.776.417-1.305.76-1.605-2.665-.3-5.466-1.332-5.466-5.93 0-1.31.465-2.38 1.235-3.22-.135-.303-.54-1.523.105-3.176 0 0 1.005-.322 3.3 1.23.96-.267 1.98-.399 3-.405 1.02.006 2.04.138 3 .405 2.28-1.552 3.285-1.23 3.285-1.23.645 1.653.24 2.873.12 3.176.765.84 1.23 1.91 1.23 3.22 0 4.61-2.805 5.625-5.475 5.92.42.36.81 1.096.81 2.22 0 1.606-.015 2.896-.015 3.286 0 .315.21.69.825.57C20.565 21.795 24 17.295 24 12c0-6.63-5.37-12-12-12z"/></svg>
                Open Source
            </a>
        </div>
        <span>&copy; <?= $h($config['app']['company_name'] ?? '') ?></span>
    </footer>

</div><!-- .sq-page -->
