<?php $h = fn(string $v) => htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); ?>
<div class="lp-page">

    <!-- Top strip: Logo + Aktiv-Badge -->
    <div class="lp-topstrip">
        <a href="/" class="lp-topstrip-logo">
            <img src="<?= $h($config['branding']['logo_main'] ?? '') ?>" alt="<?= $h($config['branding']['logo_alt'] ?? '') ?>">
        </a>
        <div class="lp-topstrip-badge">
            <span class="lp-topstrip-dot"></span>
            <?= $h($config['app']['company_slogan'] ?? '') ?>
        </div>
    </div>

    <!-- Hero-Foto mit Headline-Overlay -->
    <div class="lp-hero" role="img" <?php if (!empty($config['branding']['hero_alt'])): ?>aria-label="<?= $h($config['branding']['hero_alt']) ?>"<?php endif; ?>>
        <img src="<?= $h($config['branding']['hero_image'] ?? '') ?>" alt="">
        <div class="lp-hero-overlay" aria-hidden="true"></div>
        <div class="lp-hero-caption">
            <div class="lp-hero-caption-line" aria-hidden="true"></div>
            <h1 class="lp-hero-h1"><?= nl2br($h($config['landing']['hero_headline'] ?? '')) ?></h1>
        </div>
    </div>

    <!-- Inhalt + Formular -->
    <div class="lp-body">
        <div class="lp-card">

            <!-- Links: Beschreibungstext -->
            <div class="lp-card-text">
                <p class="lp-kicker"><?= $h($config['app']['survey_label'] ?? '') ?></p>
                <h2 class="lp-card-heading"><?= $h($config['landing']['card_heading'] ?? '') ?></h2>
                <p class="lp-card-body-text"><?= $h($config['landing']['card_body'] ?? '') ?></p>
                <ul class="lp-checklist">
                    <?php foreach ($config['landing']['checklist'] ?? [] as $item): ?>
                    <li><?= $h($item) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- Rechts: Formular -->
            <div class="lp-card-form">
                <h2 class="lp-form-title">Zugangscode eingeben</h2>
                <p class="lp-form-desc">Ihren persönlichen Code finden Sie in der Einladungs-E-Mail.</p>

                <?php if ($error ?? null): ?>
                <div class="flash flash-error lp-form-flash"><?= $h($error) ?></div>
                <?php endif; ?>

                <form method="post" action="/umfrage/enter" class="lp-form" autocomplete="off">
                    <?= \App\Core\Csrf::field() ?>
                    <label class="lp-form-label" for="code">Ihr Zugangscode</label>
                    <input
                        type="text"
                        id="code"
                        name="code"
                        class="lp-form-input"
                        placeholder="z.&thinsp;B.&ensp;abc12345"
                        maxlength="8"
                        autocomplete="off"
                        autocapitalize="none"
                        autocorrect="off"
                        spellcheck="false"
                        required
                        <?php if (!empty($prefillCode)): ?>value="<?= $h($prefillCode) ?>"<?php endif; ?>
                    >
                    <button type="submit" class="lp-form-btn">
                        Befragung starten
                        <svg class="lp-form-btn-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <line x1="5" y1="12" x2="19" y2="12"/>
                            <polyline points="12 5 19 12 12 19"/>
                        </svg>
                    </button>
                </form>

                <div class="lp-form-trust">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <rect x="3" y="11" width="18" height="11" rx="2"/>
                        <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                    </svg>
                    Ihre Angaben werden vertraulich behandelt und nicht an Dritte weitergegeben.
                </div>
            </div>

        </div>

        <footer class="lp-footer">
            <div class="lp-footer-links">
                <?php if (!empty($config['app']['impressum_url'])): ?>
                <a href="<?= $h($config['app']['impressum_url']) ?>" target="_blank" rel="noopener">Impressum</a>
                <span class="lp-footer-dot" aria-hidden="true"></span>
                <?php endif; ?>
                <?php if (!empty($config['app']['datenschutz_url'])): ?>
                <a href="<?= $h($config['app']['datenschutz_url']) ?>" target="_blank" rel="noopener">Datenschutz</a>
                <span class="lp-footer-dot" aria-hidden="true"></span>
                <?php endif; ?>
                <a href="https://github.com/6c756b/feedback-kundenzufriedenheit" target="_blank" rel="noopener" class="lp-footer-github">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="12" height="12" aria-hidden="true"><path d="M12 0C5.37 0 0 5.37 0 12c0 5.3 3.438 9.8 8.205 11.385.6.113.82-.258.82-.577 0-.285-.01-1.04-.015-2.04-3.338.724-4.042-1.61-4.042-1.61-.546-1.385-1.335-1.755-1.335-1.755-1.087-.744.084-.729.084-.729 1.205.084 1.838 1.236 1.838 1.236 1.07 1.835 2.809 1.305 3.495.998.108-.776.417-1.305.76-1.605-2.665-.3-5.466-1.332-5.466-5.93 0-1.31.465-2.38 1.235-3.22-.135-.303-.54-1.523.105-3.176 0 0 1.005-.322 3.3 1.23.96-.267 1.98-.399 3-.405 1.02.006 2.04.138 3 .405 2.28-1.552 3.285-1.23 3.285-1.23.645 1.653.24 2.873.12 3.176.765.84 1.23 1.91 1.23 3.22 0 4.61-2.805 5.625-5.475 5.92.42.36.81 1.096.81 2.22 0 1.606-.015 2.896-.015 3.286 0 .315.21.69.825.57C20.565 21.795 24 17.295 24 12c0-6.63-5.37-12-12-12z"/></svg>
                    Open Source
                </a>
            </div>
            <span>&copy; <?= $h($config['app']['company_name'] ?? '') ?></span>
        </footer>
    </div>

</div>
