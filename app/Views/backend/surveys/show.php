<?php
use App\Core\Auth;
use App\Core\Csrf;

$h = fn(string $v) => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
$statusLabels = [
    'open'      => 'Offen',
    'started'   => 'Gestartet',
    'completed' => 'Abgeschlossen',
    'cancelled' => 'Abgebrochen',
    'archived'  => 'Archiviert',
];
$salutationLabels = ['' => '–', 'herr' => 'Herr', 'frau' => 'Frau'];
$isOpenOrStarted  = in_array($survey['status'], ['open', 'started'], true);

$smtpConfig    = (require ROOT . '/config.php')['smtp'] ?? [];
$smtpAvailable = !empty($smtpConfig['host']);
?>
<div class="page-header">
    <h1>Befragung: <?= $h($survey['customer_name']) ?></h1>
    <div class="header-actions">
        <?php if (Auth::hasRole('staff') && $survey['status'] === 'open'): ?>
        <a href="/backend/befragungen/<?= (int)$survey['id'] ?>/bearbeiten" class="btn-ghost">Bearbeiten</a>
        <?php endif; ?>
        <?php if (Auth::hasRole('staff') && $isOpenOrStarted): ?>
        <button type="button" class="btn-primary" data-outlook-id="<?= (int)$survey['id'] ?>">
            E-Mailversand
        </button>
        <form method="post" action="/backend/befragungen/<?= (int)$survey['id'] ?>/abbrechen"
              onsubmit="return confirm('Befragung wirklich abbrechen?')" style="display:inline">
            <?= Csrf::field() ?>
            <button type="submit" class="btn-ghost btn-danger">Abbrechen</button>
        </form>
        <?php endif; ?>
        <?php if (Auth::hasRole('superadmin')): ?>
        <form method="post" action="/backend/befragungen/<?= (int)$survey['id'] ?>/loeschen"
              onsubmit="return confirm('Befragung wirklich löschen? Diese Aktion kann nicht rückgängig gemacht werden.')" style="display:inline">
            <?= Csrf::field() ?>
            <button type="submit" class="btn-ghost btn-danger">Löschen</button>
        </form>
        <?php endif; ?>
        <a href="/backend/befragungen" class="btn-ghost">Zurück</a>
    </div>
</div>

<div class="form-card">
    <div class="form-grid">
        <!-- Zeile 1: Kunde | Ansprechpartner -->
        <div class="form-group">
            <label>Kunde</label>
            <div class="readonly-field"><?= $h($survey['customer_name']) ?></div>
        </div>
        <div class="form-group">
            <label>Ansprechpartner</label>
            <div class="readonly-field"><?= $h($survey['contact_person']) ?></div>
        </div>

        <!-- Zeile 2: Projektname | Anrede -->
        <div class="form-group">
            <label>Projektname</label>
            <div class="readonly-field"><?= $h($survey['project_name']) ?></div>
        </div>
        <div class="form-group">
            <label>Anrede</label>
            <div class="readonly-field"><?= $h($salutationLabels[$survey['contact_salutation'] ?? ''] ?? '–') ?></div>
        </div>

        <!-- Zeile 3: Projekt-ID | E-Mail -->
        <div class="form-group">
            <label>Projekt-ID</label>
            <div class="readonly-field"><?= $survey['project_id'] ? $h($survey['project_id']) : '<span class="text-muted">–</span>' ?></div>
        </div>
        <div class="form-group">
            <label>E-Mail</label>
            <div class="readonly-field"><?= $h($survey['contact_email']) ?></div>
        </div>

        <!-- Zeile 4: Bereiche | Vertrieb -->
        <div class="form-group">
            <label>Bereiche</label>
            <div class="readonly-field"><?= $survey['area_names'] ? $h($survey['area_names']) : '<span class="text-muted">–</span>' ?></div>
        </div>
        <div class="form-group">
            <label>Vertrieb</label>
            <div class="readonly-field"><?= $survey['sales_user_name'] ? $h($survey['sales_user_name']) : '<span class="text-muted">–</span>' ?></div>
        </div>

        <!-- Zeile 4b: Projektleitung | Metropolregion -->
        <div class="form-group">
            <label>Projektleitung</label>
            <div class="readonly-field"><?= !empty($survey['project_lead_name']) ? $h($survey['project_lead_name']) : '<span class="text-muted">–</span>' ?></div>
        </div>
        <div class="form-group">
            <label>Metropolregion</label>
            <div class="readonly-field"><?= !empty($survey['metropolregion_name']) ? $h($survey['metropolregion_name']) : '<span class="text-muted">–</span>' ?></div>
        </div>

        <!-- Zeile 5: Code | Status -->
        <div class="form-group">
            <label>Code</label>
            <div class="readonly-field"><code><?= $h($survey['code']) ?></code></div>
        </div>
        <div class="form-group">
            <label>Status</label>
            <div class="readonly-field">
                <span class="badge badge-<?= $h($survey['status']) ?>"><?= $h($statusLabels[$survey['status']] ?? '') ?></span>
            </div>
        </div>

        <!-- Zeile 6: Erstellt von | Erstellt am -->
        <div class="form-group">
            <label>Erstellt von</label>
            <div class="readonly-field"><?= $h($survey['created_by_name']) ?></div>
        </div>
        <div class="form-group">
            <label>Erstellt am</label>
            <div class="readonly-field"><?= $h(date('d.m.Y H:i', strtotime($survey['created_at']))) ?></div>
        </div>

        <!-- E-Mail-Status -->
        <div class="form-group">
            <label>E-Mail versendet</label>
            <div class="readonly-field">
                <?php if ($survey['email_sent_at']): ?>
                    <?= $h(date('d.m.Y H:i', strtotime($survey['email_sent_at']))) ?> Uhr
                    · <?= $h($survey['email_sent_by_name'] ?? '–') ?>
                    · <?= $survey['email_sent_method'] === 'system' ? 'System' : 'Outlook' ?>
                <?php else: ?>
                    <span class="text-muted">–</span>
                <?php endif; ?>
            </div>
        </div>

        <!-- Anmerkungen + Referenz -->
        <?php if ($survey['internal_notes']): ?>
        <div class="form-group form-full">
            <label>Interne Anmerkungen</label>
            <div class="readonly-field" style="white-space:pre-wrap"><?= $h($survey['internal_notes']) ?></div>
        </div>
        <?php endif; ?>
        <div class="form-group">
            <label>Referenz einholen</label>
            <div class="readonly-field">
                <?php if ($survey['reference_requested']): ?>
                    <?php if ($survey['reference_granted'] === null): ?>
                        <span class="badge badge-completed">Angefragt</span>
                    <?php elseif ($survey['reference_granted']): ?>
                        <span class="badge badge-open">Ja ✓</span>
                    <?php else: ?>
                        <span class="text-muted">Nein</span>
                    <?php endif; ?>
                <?php else: ?>
                    <span class="text-muted">Nein</span>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($allSignatures)): ?>
<script>
window.__signatures = <?= json_encode(array_column($allSignatures, 'content', 'slug'), JSON_HEX_TAG | JSON_HEX_AMP) ?>;
</script>
<?php endif; ?>

<!-- Einladungs-Modal -->
<div id="outlook-modal" class="modal" hidden>
    <div class="modal-backdrop" data-close-modal></div>
    <div class="modal-box modal-box-lg">
        <div class="modal-header">
            <h2>E-Mail-Einladung</h2>
            <button type="button" class="modal-close" data-close-modal>&times;</button>
        </div>
        <div class="modal-body" style="padding:0">
            <form id="email-send-form" method="post"
                  action="/backend/befragungen/<?= (int)$survey['id'] ?>/email-senden">
                <?= Csrf::field() ?>
                <div class="email-meta">
                    <div class="email-meta-row">
                        <span class="email-meta-label">Von</span>
                        <span id="modal-from" class="email-meta-value">–</span>
                    </div>
                    <div class="email-meta-row">
                        <span class="email-meta-label">An</span>
                        <span id="modal-to" class="email-meta-value">–</span>
                    </div>
                    <div class="email-meta-row">
                        <span class="email-meta-label">Betreff</span>
                        <input type="text" name="subject" id="modal-subject" class="email-meta-subject">
                    </div>
                    <?php if (!empty($allSignatures)): ?>
                    <div class="email-meta-row">
                        <span class="email-meta-label">Signatur</span>
                        <select name="signature_slug" id="modal-signature" class="email-meta-select">
                            <option value="">– Keine Signatur –</option>
                            <?php foreach ($allSignatures as $sig): ?>
                            <option value="<?= $h($sig['slug']) ?>"
                                    <?= $sig['slug'] === ($userSignatureSlug ?? null) ? 'selected' : '' ?>>
                                <?= $h($sig['email']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                </div>
                <hr class="email-hr">
                <input type="hidden" name="body" id="modal-body-hidden">
                <div id="modal-body-editor" class="email-body-quill"></div>
                <textarea id="modal-body-fallback" class="email-body-textarea" style="display:none"></textarea>
                <div id="modal-sig-preview" class="modal-sig-preview" hidden>
                    <div class="modal-sig-divider">Signatur - Wird automatisch angehängt</div>
                    <div id="modal-sig-content" class="modal-sig-content"></div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <?php if ($smtpAvailable && $isOpenOrStarted): ?>
            <button type="submit" form="email-send-form" class="btn-primary">E-Mail senden</button>
            <?php endif; ?>
            <?php if ($isOpenOrStarted): ?>
            <form method="post" style="display:contents"
                  action="/backend/befragungen/<?= (int)$survey['id'] ?>/email-bestaetigen"
                  onsubmit="return confirm('Outlook-Versand als gesendet markieren?')">
                <?= Csrf::field() ?>
                <button type="submit" class="btn-ghost">Outlook ✓</button>
            </form>
            <?php endif; ?>
            <button type="button" id="copy-outlook" class="btn-ghost">Kopieren</button>
            <button type="button" class="btn-ghost" data-close-modal>Schließen</button>
        </div>
    </div>
</div>
