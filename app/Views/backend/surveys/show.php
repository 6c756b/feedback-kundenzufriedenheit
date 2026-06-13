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
$salutationLabels = ['' => '', 'herr' => 'Herr', 'frau' => 'Frau'];
$isOpenOrStarted  = in_array($survey['status'], ['open', 'started'], true);

$smtpConfig    = (require ROOT . '/config.php')['smtp'] ?? [];
$smtpAvailable = !empty($smtpConfig['host']);
?>

<!-- Seiten-Header -->
<div class="page-header" style="margin-bottom:24px">
    <div>
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:4px">
            <span class="badge badge-<?= $h($survey['status']) ?>" style="font-size:12px;padding:4px 10px">
                <?= $h($statusLabels[$survey['status']] ?? '') ?>
            </span>
        </div>
        <h1 style="margin:0;line-height:1.2"><?= $h($survey['customer_name']) ?></h1>
        <?php if ($survey['project_name']): ?>
        <p style="margin:4px 0 0;color:var(--color-navy-60);font-size:14px"><?= $h($survey['project_name']) ?></p>
        <?php endif; ?>
    </div>
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

<!-- Detail-Karten -->
<div class="survey-detail-grid">

    <!-- Kontakt -->
    <div class="detail-card">
        <div class="detail-card-title">Kontakt</div>
        <dl class="detail-list">
            <?php
                $salutation = $salutationLabels[$survey['contact_salutation'] ?? ''] ?? '';
                $contactFull = trim(($salutation ? $salutation . ' ' : '') . $survey['contact_person']);
            ?>
            <div class="detail-row">
                <dt>Ansprechpartner</dt>
                <dd><?= $contactFull ? $h($contactFull) : '<span class="text-muted">–</span>' ?></dd>
            </div>
            <div class="detail-row">
                <dt>E-Mail</dt>
                <dd><?= $survey['contact_email'] ? '<a href="mailto:' . $h($survey['contact_email']) . '">' . $h($survey['contact_email']) . '</a>' : '<span class="text-muted">–</span>' ?></dd>
            </div>
        </dl>
    </div>

    <!-- Projekt -->
    <div class="detail-card">
        <div class="detail-card-title">Projekt</div>
        <dl class="detail-list">
            <div class="detail-row">
                <dt>Projekt-ID</dt>
                <dd><?= $survey['project_id'] ? $h($survey['project_id']) : '<span class="text-muted">–</span>' ?></dd>
            </div>
            <div class="detail-row">
                <dt>Bereiche</dt>
                <dd><?= $survey['area_names'] ? $h($survey['area_names']) : '<span class="text-muted">–</span>' ?></dd>
            </div>
            <div class="detail-row">
                <dt>Vertrieb</dt>
                <dd><?= $survey['sales_user_name'] ? $h($survey['sales_user_name']) : '<span class="text-muted">–</span>' ?></dd>
            </div>
            <div class="detail-row">
                <dt>Projektleitung</dt>
                <dd><?= !empty($survey['project_lead_name']) ? $h($survey['project_lead_name']) : '<span class="text-muted">–</span>' ?></dd>
            </div>
            <div class="detail-row">
                <dt>Metropolregion</dt>
                <dd><?= !empty($survey['metropolregion_name']) ? $h($survey['metropolregion_name']) : '<span class="text-muted">–</span>' ?></dd>
            </div>
        </dl>
    </div>

    <!-- Versand -->
    <div class="detail-card">
        <div class="detail-card-title">Versand</div>
        <dl class="detail-list">
            <div class="detail-row">
                <dt>Code</dt>
                <dd><code style="background:#f0f1f4;padding:2px 6px;border-radius:4px;font-size:13px"><?= $h($survey['code']) ?></code></dd>
            </div>
            <div class="detail-row">
                <dt>E-Mail</dt>
                <dd>
                    <?php if ($survey['email_sent_at']): ?>
                        <?= $h(date('d.m.Y H:i', strtotime($survey['email_sent_at']))) ?> Uhr
                        &middot; <?= $h($survey['email_sent_by_name'] ?? '–') ?>
                        &middot; <?= $survey['email_sent_method'] === 'system' ? 'System' : 'Outlook' ?>
                    <?php else: ?>
                        <span class="text-muted">Noch nicht versendet</span>
                    <?php endif; ?>
                </dd>
            </div>
        </dl>
    </div>

    <!-- Verlauf -->
    <div class="detail-card">
        <div class="detail-card-title">Verlauf</div>
        <dl class="detail-list">
            <div class="detail-row">
                <dt>Erstellt von</dt>
                <dd><?= $h($survey['created_by_name']) ?> &middot; <?= $h(date('d.m.Y H:i', strtotime($survey['created_at']))) ?> Uhr</dd>
            </div>
            <div class="detail-row">
                <dt>Referenz</dt>
                <dd>
                    <?php if ($survey['reference_requested']): ?>
                        <?php if ($survey['reference_granted'] === null): ?>
                            <span class="badge badge-completed">Angefragt</span>
                        <?php elseif ($survey['reference_granted']): ?>
                            <span class="badge badge-open">Erteilt</span>
                        <?php else: ?>
                            <span class="text-muted">Abgelehnt</span>
                        <?php endif; ?>
                    <?php else: ?>
                        <span class="text-muted">Nicht angefragt</span>
                    <?php endif; ?>
                </dd>
            </div>
        </dl>
    </div>

    <?php if ($survey['internal_notes']): ?>
    <!-- Notizen -->
    <div class="detail-card detail-card--full">
        <div class="detail-card-title">Interne Anmerkungen</div>
        <p style="white-space:pre-wrap;margin:0;font-size:14px;color:var(--color-navy-80);line-height:1.6"><?= $h($survey['internal_notes']) ?></p>
    </div>
    <?php endif; ?>

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
