<?php
use App\Core\Csrf;
use App\Core\Session;

$h      = fn(string $v) => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
$old    = Session::flash('old') ?? $user;
$isNew  = empty($user['id']);
$action = $isNew ? '/backend/benutzer' : '/backend/benutzer/' . (int)$user['id'];
$errors = Session::flash('errors') ?? [];
$allRoles = [
    'none'       => 'Keine Berechtigung',
    'reader'     => 'Leserechte',
    'staff'      => 'Mitarbeiter',
    'admin'      => 'Admin',
    'superadmin' => 'Super-Admin',
];
$roles    = array_intersect_key($allRoles, array_flip($allowedRoles));
$isActive       = !isset($old['active']) || $old['active'];
$isSales        = !empty($old['is_sales']);
$isProjectlead  = !empty($old['is_projectlead']);
?>
<div class="page-header">
    <h1><?= $isNew ? 'Neuer Benutzer' : 'Benutzer bearbeiten' ?></h1>
    <a href="/backend/benutzer" class="btn-ghost">Zurück</a>
</div>

<?php if ($errors): ?>
<div class="flash flash-error">
    <ul><?php foreach ($errors as $e): ?><li><?= $h($e) ?></li><?php endforeach; ?></ul>
</div>
<?php endif; ?>

<form method="post" action="<?= $h($action) ?>" class="form-card" enctype="multipart/form-data">
    <?= Csrf::field() ?>

    <div class="form-grid">
        <div class="form-group">
            <label for="name">Name *</label>
            <input type="text" id="name" name="name" required
                   value="<?= $h((string)($old['name'] ?? '')) ?>">
        </div>
        <div class="form-group">
            <label for="display_name">Anzeigename (Signatur)</label>
            <input type="text" id="display_name" name="display_name"
                   value="<?= $h((string)($old['display_name'] ?? '')) ?>">
        </div>
        <div class="form-group">
            <label for="email">E-Mail (LDAP-Login) *</label>
            <input type="email" id="email" name="email" required
                   value="<?= $h((string)($old['email'] ?? '')) ?>">
        </div>
        <div class="form-group">
            <label for="job_title">Berufsbezeichnung</label>
            <input type="text" id="job_title" name="job_title"
                   value="<?= $h((string)($old['job_title'] ?? '')) ?>">
        </div>
        <div class="form-group">
            <label for="role">Rolle *<span class="help-icon">?<span class="help-tooltip"><span class="help-tooltip-row"><span class="help-tooltip-role">Keine Berechtigung</span><span class="help-tooltip-desc">Kein Backend-Zugang</span></span><span class="help-tooltip-row"><span class="help-tooltip-role">Leserechte</span><span class="help-tooltip-desc">Befragungen &amp; Auswertungen lesen</span></span><span class="help-tooltip-row"><span class="help-tooltip-role">Mitarbeiter</span><span class="help-tooltip-desc">Befragungen anlegen &amp; versenden</span></span><span class="help-tooltip-row"><span class="help-tooltip-role">Admin</span><span class="help-tooltip-desc">Vollzugriff inkl. Benutzerverwaltung</span></span><span class="help-tooltip-row"><span class="help-tooltip-role">Super-Admin</span><span class="help-tooltip-desc">Alle Rechte</span></span></span></span></label>
            <select id="role" name="role" required>
                <?php foreach ($roles as $val => $label): ?>
                <option value="<?= $h($val) ?>"
                    <?= ($old['role'] ?? 'none') === $val ? 'selected' : '' ?>>
                    <?= $h($label) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="phone">Telefon</label>
            <input type="text" id="phone" name="phone"
                   value="<?= $h((string)($old['phone'] ?? '')) ?>">
        </div>
        <div class="form-group">
            <label>Flags</label>
            <div style="display:flex;align-items:center;gap:24px;padding-top:6px">
                <div style="display:flex;align-items:center;gap:10px">
                    <input type="hidden" name="is_sales" id="is-sales-input" value="<?= $isSales ? '1' : '0' ?>">
                    <button type="button"
                            class="toggle-btn <?= $isSales ? 'is-active' : '' ?>"
                            data-target="is-sales-input"
                            title="Vertrieb Ja/Nein">
                        <span class="toggle-knob"></span>
                    </button>
                    <span class="toggle-label" id="is-sales-label">Vertrieb</span>
                </div>
                <div style="display:flex;align-items:center;gap:10px">
                    <input type="hidden" name="is_projectlead" id="is-projectlead-input" value="<?= $isProjectlead ? '1' : '0' ?>">
                    <button type="button"
                            class="toggle-btn <?= $isProjectlead ? 'is-active' : '' ?>"
                            data-target="is-projectlead-input"
                            title="Projektleitung Ja/Nein">
                        <span class="toggle-knob"></span>
                    </button>
                    <span class="toggle-label" id="is-projectlead-label">Projektleitung</span>
                </div>
            </div>
        </div>
        <?php if (!$isNew): ?>
        <div class="form-group">
            <label for="signature_image">Profilbild (max. 2 MB)</label>
            <input type="file" id="signature_image" name="signature_image"
                   accept="image/png,image/jpeg,image/gif,image/webp">
            <?php if (!empty($user['signature_image'])): ?>
            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;margin-top:8px">
                <input type="checkbox" name="clear_image" value="1">
                Bild entfernen
            </label>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn-primary"><?= $isNew ? 'Benutzer anlegen' : 'Speichern' ?></button>
        <a href="/backend/benutzer" class="btn-ghost">Abbrechen</a>
        <div style="margin-left:auto;display:flex;align-items:center;gap:10px">
            <input type="hidden" name="active" id="active-input" value="<?= $isActive ? '1' : '0' ?>">
            <button type="button"
                    class="toggle-btn <?= $isActive ? 'is-active' : '' ?>"
                    data-target="active-input"
                    title="Aktiv/Inaktiv">
                <span class="toggle-knob"></span>
            </button>
            <span class="toggle-label" id="active-label"><?= $isActive ? 'Aktiv' : 'Inaktiv' ?></span>
        </div>
    </div>
</form>

<?php if (!$isNew && !empty($renderedSignature)): ?>
<div style="margin-top:28px">
    <h3 style="margin-bottom:12px">Signatur-Vorschau</h3>
    <div style="border:1px solid #e0e0e0;border-radius:6px;padding:20px;background:#fff;overflow-x:auto">
        <?= $renderedSignature ?>
    </div>
</div>
<?php elseif (!$isNew && empty($user['display_name'])): ?>
<div style="margin-top:16px">
    <div class="flash flash-info">Anzeigename eintragen um die Signatur-Vorschau zu aktivieren.</div>
</div>
<?php endif; ?>
