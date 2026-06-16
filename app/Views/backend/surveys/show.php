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

$personSvg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M7.5 6a4.5 4.5 0 1 1 9 0 4.5 4.5 0 0 1-9 0ZM3.751 20.105a8.25 8.25 0 0 1 16.498 0 .75.75 0 0 1-.437.695A18.683 18.683 0 0 1 12 22.5c-2.786 0-5.433-.608-7.812-1.7a.75.75 0 0 1-.437-.695Z" clip-rule="evenodd"/></svg>';

$userChip = function(?string $img, ?string $name) use ($h, $personSvg): string {
    if (!$name) return '<span class="text-muted">–</span>';
    $avatar = $img
        ? '<img src="' . $h($img) . '" class="user-avatar" alt="">'
        : '<span class="user-avatar user-avatar--icon">' . $personSvg . '</span>';
    return '<span class="user-chip">' . $avatar . '<span>' . $h($name) . '</span></span>';
};

$personCard = function(string $title, ?string $name, ?string $img, ?string $jobTitle, ?string $email, ?string $phone) use ($h, $personSvg): void {
    $avatar = $img
        ? '<img src="' . $h($img) . '" class="user-avatar user-avatar-lg" alt="">'
        : '<span class="user-avatar user-avatar-lg user-avatar--icon">' . $personSvg . '</span>';
    echo '<div class="detail-card detail-card--person">';
    echo '<div class="detail-card-title">' . $h($title) . '</div>';
    if ($name) {
        echo '<div class="person-header">' . $avatar;
        echo '<div class="person-info"><span class="person-name">' . $h($name) . '</span>';
        if ($jobTitle) echo '<span class="person-role">' . $h($jobTitle) . '</span>';
        echo '</div></div>';
        if ($email || $phone) {
            echo '<dl class="detail-list person-card-contacts">';
            if ($email) echo '<div class="detail-row"><dt>E-Mail</dt><dd><a class="detail-email" href="mailto:' . $h($email) . '">' . $h($email) . '</a></dd></div>';
            if ($phone) echo '<div class="detail-row"><dt>Telefon</dt><dd><a href="tel:' . $h($phone) . '">' . $h($phone) . '</a></dd></div>';
            echo '</dl>';
        }
    } else {
        echo '<span class="text-muted">–</span>';
    }
    echo '</div>';
};
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
        <?php
            $subtitle = '';
            if ($survey['project_id'])   $subtitle  = $h($survey['project_id']) . ' | ';
            if ($survey['project_name']) $subtitle .= $h($survey['project_name']);
        ?>
        <?php if ($subtitle): ?>
        <p style="margin:4px 0 0;color:var(--color-navy-60);font-size:14px"><?= $subtitle ?></p>
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

    <!-- Zeile 1: Vertrieb | Projektleitung | Erstellt -->
    <?php $personCard(
        'Vertrieb',
        $survey['sales_user_name']      ?? null,
        $survey['sales_user_image']     ?? null,
        $survey['sales_user_job_title'] ?? null,
        $survey['sales_user_email']     ?? null,
        $survey['sales_user_phone']     ?? null
    ); ?>
    <?php $personCard(
        'Projektleitung',
        $survey['project_lead_name']      ?? null,
        $survey['project_lead_image']     ?? null,
        $survey['project_lead_job_title'] ?? null,
        $survey['project_lead_email']     ?? null,
        $survey['project_lead_phone']     ?? null
    ); ?>
    <div class="detail-card detail-card--person">
        <div class="detail-card-title">Erstellt</div>
        <div class="person-header">
            <?php if (!empty($survey['created_by_image'])): ?>
            <img src="<?= $h($survey['created_by_image']) ?>" class="user-avatar user-avatar-lg" alt="">
            <?php else: ?>
            <span class="user-avatar user-avatar-lg user-avatar--icon"><?= $personSvg ?></span>
            <?php endif; ?>
            <div class="person-info">
                <span class="person-name"><?= $h($survey['created_by_name'] ?? '–') ?></span>
                <span class="person-role"><?= $h(date('d.m.Y H:i', strtotime($survey['created_at']))) ?> Uhr</span>
            </div>
        </div>
    </div>

    <!-- Zeile 2: Bereiche | Kundenkontakt | Daten -->
    <div class="detail-card">
        <div class="detail-card-title">Bereiche</div>
        <?php if ($survey['area_names']): ?>
        <ul class="detail-area-list">
            <?php foreach (explode(', ', $survey['area_names']) as $areaName): ?>
            <li><?= $h(trim($areaName)) ?></li>
            <?php endforeach; ?>
        </ul>
        <?php else: ?>
        <span class="text-muted">–</span>
        <?php endif; ?>
    </div>
    <div class="detail-card">
        <div class="detail-card-title">Kundenkontakt</div>
        <dl class="detail-list">
            <?php
                $salutation  = $salutationLabels[$survey['contact_salutation'] ?? ''] ?? '';
                $contactFull = trim(($salutation ? $salutation . ' ' : '') . $survey['contact_person']);
            ?>
            <div class="detail-row">
                <dt>Ansprechpartner</dt>
                <dd><?= $contactFull ? $h($contactFull) : '<span class="text-muted">–</span>' ?></dd>
            </div>
            <div class="detail-row">
                <dt>E-Mail</dt>
                <dd><?= $survey['contact_email']
                    ? '<a class="detail-email" href="mailto:' . $h($survey['contact_email']) . '">' . $h($survey['contact_email']) . '</a>'
                    : '<span class="text-muted">–</span>' ?></dd>
            </div>
        </dl>
    </div>
    <div class="detail-card">
        <div class="detail-card-title">Daten</div>
        <dl class="detail-list">
            <div class="detail-row">
                <dt>Code</dt>
                <dd><code><?= $h($survey['code']) ?></code></dd>
            </div>
            <div class="detail-row">
                <dt>E-Mail-Versand</dt>
                <dd>
                    <?php if ($survey['email_sent_at']): ?>
                        <span class="detail-row-stack">
                            <span><?= $h(date('d.m.Y H:i', strtotime($survey['email_sent_at']))) ?> Uhr</span>
                            <span class="text-muted text-small"><?= $h($survey['email_sent_by_name'] ?? '–') ?> &middot; <?= $survey['email_sent_method'] === 'system' ? 'System' : 'Outlook' ?></span>
                        </span>
                    <?php else: ?>
                        <span class="text-muted">Noch nicht versendet</span>
                    <?php endif; ?>
                </dd>
            </div>
        </dl>
    </div>

    <!-- Zeile 3: Metropolregion | Referenz -->
    <div class="detail-card">
        <div class="detail-card-title">Metropolregion</div>
        <span class="detail-single-value">
            <?= !empty($survey['metropolregion_name']) ? $h($survey['metropolregion_name']) : '<span class="text-muted">–</span>' ?>
        </span>
    </div>
    <div class="detail-card">
        <div class="detail-card-title">Referenz</div>
        <span class="detail-single-value">
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
        </span>
    </div>

</div>

<?php if ($survey['internal_notes']): ?>
<div class="detail-card" style="margin-top:16px">
    <div class="detail-card-title">Interne Anmerkungen</div>
    <p style="white-space:pre-wrap;margin:0;font-size:14px;color:var(--color-navy-80);line-height:1.6"><?= $h($survey['internal_notes']) ?></p>
</div>
<?php endif; ?>

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
