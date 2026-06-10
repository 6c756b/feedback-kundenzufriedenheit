<?php
$h          = fn(string $v) => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
$pct        = $totalCount > 0 ? round($answered / $totalCount * 100) : 0;
$surveyId   = (int)$survey['id'];
$code       = $h($survey['code']);
$hasWelcome = !empty($config['survey']['welcome_text']);
?>

<div class="sq-page">

    <!-- Fixed header: topstrip + progress strip -->
    <div class="sq-fixed-header">
        <div class="sq-topstrip">
            <a href="/" class="sq-logo-link">
                <img src="<?= $h($config['branding']['logo_main'] ?? '') ?>" alt="<?= $h($config['branding']['logo_alt'] ?? '') ?>" class="sq-logo">
            </a>
            <div class="sq-badge">
                <span class="sq-badge-dot"></span>
                <?= $h($config['app']['company_slogan'] ?? 'Einfach. Sicher. Arbeiten.') ?>
            </div>
        </div>

        <div class="sq-progress-strip">
            <div class="sq-progress-meta">
                <div class="sq-progress-title">
                    <?= $h($survey['customer_name']) ?>
                    <span class="sq-progress-sep">&middot;</span>
                    <span class="sq-progress-sub"><?= $h($survey['project_name']) ?></span>
                </div>
                <div class="sq-progress-right">
                    <span class="sq-progress-count" id="progress-count"><?= $answered ?> / <?= $totalCount ?></span>
                    <span class="sq-progress-count-sub">beantwortet</span>
                    <span class="sq-progress-pct" id="progress-pct"><?= $pct ?>&nbsp;%</span>
                </div>
            </div>
            <div class="sq-progress-bar-row">
                <div class="sq-progress-track">
                    <div class="sq-progress-fill" id="progress-bar" style="width:<?= $pct ?>%"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Welcome screen -->
    <?php if ($hasWelcome): ?>
    <div id="welcome-screen" class="welcome-screen">
        <div class="welcome-screen-inner">
            <div class="welcome-eyebrow"><?= $h($config['app']['survey_label'] ?? '') ?></div>
            <h1><?= $h($survey['customer_name']) ?></h1>
            <p class="survey-meta"><?= $h($survey['contact_person']) ?> &middot; <?= $h($survey['project_name']) ?></p>
            <div class="welcome-text">
                <?= nl2br($h(str_replace('\n', "\n", $config['survey']['welcome_text'] ?? ''))) ?>
            </div>
            <button type="button" id="welcome-continue">Zur Befragung</button>
        </div>
    </div>
    <?php endif; ?>

    <!-- Survey content -->
    <div id="survey-content" class="sq-content" <?= $hasWelcome ? 'hidden' : '' ?>>

        <form id="survey-form"
              data-code="<?= $code ?>"
              data-total="<?= $totalCount ?>"
              data-answered="<?= $answered ?>">
            <?= \App\Core\Csrf::field() ?>

            <?php
            $currentArea = null;
            $qNum = 0;
            foreach ($questions as $q):
                $qNum++;
                $qId        = (int)$q['id'];
                $answer     = $answers[$qId] ?? null;
                $aType      = $answer['answer_type'] ?? null;
                $sVal       = isset($answer['slider_value']) ? (float)$answer['slider_value'] : 3.0;
                $fVal       = $answer['freitext_value'] ?? '';
                $isNA       = $aType === 'not_applicable';
                $isAnswered = $answer !== null;

                if ($q['area_name'] !== $currentArea):
                    $currentArea = $q['area_name'];
            ?>
            <h2 class="area-heading"><?= $h($currentArea) ?></h2>
            <?php endif; ?>

            <div class="question-block <?= $isNA ? 'is-not-applicable' : '' ?> <?= $isAnswered ? 'is-answered' : '' ?>"
                 id="q-<?= $qId ?>">

                <div class="question-num">Frage <?= $qNum ?></div>
                <h3 class="question-title"><?= $h($q['label_short']) ?></h3>
                <?php if ($q['label_long']): ?>
                <p class="question-text"><?= $h($q['label_long']) ?></p>
                <?php endif; ?>

                <?php if ($q['type'] === 'slider' || $q['type'] === 'slider_freitext'): ?>
                <div class="slider-wrap <?= $isNA ? 'is-disabled' : '' ?>">
                    <div class="slider-grade-display">
                        <div class="slider-value-display" id="sv-<?= $qId ?>"><?= number_format($sVal, 1, ',', '') ?></div>
                        <div class="slider-grade-hint">Note</div>
                    </div>
                    <input type="range"
                           class="survey-slider"
                           id="slider-<?= $qId ?>"
                           data-qid="<?= $qId ?>"
                           min="1" max="6" step="0.1"
                           value="<?= $sVal ?>"
                           <?= $isNA ? 'disabled' : '' ?>>
                    <div class="slider-labels">
                        <span>Note 1 – <?= $h($q['slider_label_min'] ?? 'Sehr gut') ?></span>
                        <span>Note 6 – <?= $h($q['slider_label_max'] ?? 'Sehr schlecht') ?></span>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ($q['type'] === 'freitext' || $q['type'] === 'slider_freitext'): ?>
                <div class="freitext-wrap <?= $isNA ? 'is-disabled' : '' ?>">
                    <?php if ($q['freitext_context']): ?>
                    <label class="freitext-label" for="ft-<?= $qId ?>"><?= $h($q['freitext_context']) ?></label>
                    <?php endif; ?>
                    <textarea class="survey-freitext"
                              id="ft-<?= $qId ?>"
                              data-qid="<?= $qId ?>"
                              rows="3"
                              <?= $isNA ? 'disabled' : '' ?>><?= $h($fVal) ?></textarea>
                </div>
                <?php endif; ?>

                <div class="question-footer">
                    <button type="button"
                            class="btn-na <?= $isNA ? 'is-active' : '' ?>"
                            data-qid="<?= $qId ?>"
                            data-type="<?= $h($q['type']) ?>">
                        <?= $isNA ? '✓ Nicht zutreffend' : 'Nicht zutreffend' ?>
                    </button>
                </div>
            </div>

            <?php endforeach; ?>

            <div class="survey-submit">
                <button type="button" id="complete-btn">
                    Befragung abschließen
                </button>
                <p class="submit-hint" id="submit-hint">
                    <?php if ($answered < $totalCount): ?>
                    <?= $totalCount - $answered ?> Frage(n) noch offen – Sie können trotzdem abschließen.
                    <?php endif; ?>
                </p>
            </div>

        </form>

    </div><!-- #survey-content -->

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

<!-- Bestätigungs-Modal -->
<div id="confirm-modal" class="modal" hidden>
    <div class="modal-backdrop"></div>
    <div class="modal-box">
        <div class="modal-header">
            <h2>Befragung abschließen</h2>
        </div>
        <div class="modal-body" id="confirm-modal-body">
            <p>Sind Sie sicher, dass Sie die Befragung jetzt abschließen möchten? Eine nachträgliche Änderung ist nicht möglich.</p>
        </div>
        <div class="modal-footer">
            <button type="button" id="confirm-complete" class="btn-primary">Ja, abschließen</button>
            <button type="button" id="cancel-complete" class="btn-ghost">Abbrechen</button>
        </div>
    </div>
</div>

<!-- Referenz-Modal -->
<div id="reference-modal" class="modal" hidden>
    <div class="modal-backdrop"></div>
    <div class="modal-box">
        <div class="modal-header">
            <h2>Eine Bitte an Sie</h2>
        </div>
        <div class="modal-body">
            <p>Wir sind stets bemüht, unsere Leistungen weiterzuempfehlen – und das authentischste Empfehlungsschreiben ist das eines zufriedenen Kunden.</p>
            <p style="margin-top:12px">Dürfen wir Sie in Zukunft als Referenzkunden nennen? Falls ja, würde sich ein Mitarbeiter persönlich bei Ihnen melden, um die Details zu besprechen. Es entsteht für Sie keinerlei Verpflichtung.</p>
        </div>
        <div class="modal-footer">
            <button type="button" id="ref-yes" class="btn-primary">Ja, gerne</button>
            <button type="button" id="ref-no" class="btn-ghost">Nein, danke</button>
        </div>
    </div>
</div>
