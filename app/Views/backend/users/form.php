<?php
use App\Core\Csrf;
use App\Core\Session;

$h             = fn(string $v) => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
$old           = Session::flash('old') ?? $user;
$isNew         = empty($user['id']);
$action        = $isNew ? '/backend/benutzer' : '/backend/benutzer/' . (int)$user['id'];
$errors        = Session::flash('errors') ?? [];
$allRoles = [
    'none'       => 'Keine Berechtigung',
    'reader'     => 'Leserechte',
    'staff'      => 'Mitarbeiter',
    'admin'      => 'Admin',
    'superadmin' => 'Super-Admin',
];
$roles         = array_intersect_key($allRoles, array_flip($allowedRoles));
$isActive      = !isset($old['active']) || $old['active'];
$isSales       = !empty($old['is_sales']);
$isProjectlead = !empty($old['is_projectlead']);
$loginMethod   = $old['login_method'] ?? 'ldap';
$loginMethods  = [
    'ldap'  => 'LDAP',
    'local' => 'Lokales Passwort',
    'both'  => 'LDAP + Lokales Passwort',
];
$hasPassword   = !empty($user['password_hash']);
$hasSig        = !empty($old['display_name']) || !empty($old['job_title']) || !empty($old['phone']) || !empty($user['signature_image']);
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

    <!-- Gruppe 1: Zugangsdaten -->
    <div class="form-grid">
        <div class="form-group">
            <label for="name">Name *</label>
            <input type="text" id="name" name="name" required
                   value="<?= $h((string)($old['name'] ?? '')) ?>">
        </div>
        <div class="form-group">
            <label for="email">E-Mail / Login *</label>
            <input type="email" id="email" name="email" required
                   value="<?= $h((string)($old['email'] ?? '')) ?>">
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
            <label for="login_method">Login-Methode *</label>
            <select id="login_method" name="login_method" onchange="updateLoginMethodVisibility(this.value)">
                <?php foreach ($loginMethods as $val => $label): ?>
                <option value="<?= $h($val) ?>" <?= $loginMethod === $val ? 'selected' : '' ?>>
                    <?= $h($label) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label>Flags</label>
            <div style="display:flex;align-items:center;gap:24px;padding-top:6px">
                <div style="display:flex;align-items:center;gap:10px">
                    <input type="hidden" name="is_sales" id="is-sales-input" value="<?= $isSales ? '1' : '0' ?>">
                    <button type="button" class="toggle-btn <?= $isSales ? 'is-active' : '' ?>"
                            data-target="is-sales-input" title="Vertrieb Ja/Nein">
                        <span class="toggle-knob"></span>
                    </button>
                    <span class="toggle-label">Vertrieb</span>
                </div>
                <div style="display:flex;align-items:center;gap:10px">
                    <input type="hidden" name="is_projectlead" id="is-projectlead-input" value="<?= $isProjectlead ? '1' : '0' ?>">
                    <button type="button" class="toggle-btn <?= $isProjectlead ? 'is-active' : '' ?>"
                            data-target="is-projectlead-input" title="Projektleitung Ja/Nein">
                        <span class="toggle-knob"></span>
                    </button>
                    <span class="toggle-label">Projektleitung</span>
                </div>
            </div>
        </div>
                <?php if (!$isNew): ?>
        <div class="form-group" id="local-password-group">
            <label for="local_password">Lokales Passwort</label>
            <input type="password" id="local_password" name="local_password"
                   autocomplete="new-password" placeholder="Leer lassen = keine Änderung">
            <small style="color:#888;margin-top:4px;display:block">
                Status: <?= $hasPassword ? '<strong>Gesetzt</strong>' : 'Nicht gesetzt' ?>
            </small>
            <?php if ($hasPassword): ?>
            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;margin-top:6px">
                <input type="checkbox" name="clear_local_password" value="1">
                Lokales Passwort entfernen
            </label>
            <?php endif; ?>
        </div>
        <div class="form-group">
            <label for="signature_image">Profilbild (max. 2 MB)</label>
            <?php if (!empty($user['signature_image'])): ?>
            <img src="<?= $h($user['signature_image']) ?>" alt="Profilbild"
                 style="width:56px;height:56px;border-radius:50%;object-fit:cover;margin-bottom:8px">
            <?php endif; ?>
            <input type="file" id="signature_image" name="signature_image"
                   accept="image/png,image/jpeg,image/gif,image/webp">
            <?php if (!empty($user['signature_image'])): ?>
            <label class="checkbox-label" style="margin-top:6px">
                <input type="checkbox" name="clear_image" value="1">
                Bild entfernen
            </label>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Gruppe 2: Signatur -->
    <hr style="border:none;border-top:1px solid var(--color-gray-40);margin:var(--gap-section) 0 var(--gap-block)">

    <div class="toggle-wrap" style="margin-bottom:var(--gap-block)">
        <button type="button" class="toggle-btn <?= $hasSig ? 'is-active' : '' ?>"
                id="sig-toggle" title="Signatur anlegen Ja/Nein">
            <span class="toggle-knob"></span>
        </button>
        <span class="toggle-label" style="font-size:15px;font-weight:700;color:var(--color-navy)">Signatur anlegen</span>
    </div>

    <div id="sig-fields" style="display:<?= $hasSig ? 'grid' : 'none' ?>;grid-template-columns:1fr 1fr;gap:var(--gap-block)">
        <div class="form-group">
            <label for="display_name">Anzeigename *</label>
            <input type="text" id="display_name" name="display_name"
                   value="<?= $h((string)($old['display_name'] ?? '')) ?>">
        </div>
        <div class="form-group">
            <label for="job_title">Berufsbezeichnung</label>
            <input type="text" id="job_title" name="job_title"
                   value="<?= $h((string)($old['job_title'] ?? '')) ?>">
        </div>
        <div class="form-group">
            <label for="phone">Telefon</label>
            <input type="text" id="phone" name="phone"
                   value="<?= $h((string)($old['phone'] ?? '')) ?>">
        </div>
    </div>

    <div class="form-actions" style="margin-top:var(--gap-section)">
        <button type="submit" class="btn-primary"><?= $isNew ? 'Benutzer anlegen' : 'Speichern' ?></button>
        <a href="/backend/benutzer" class="btn-ghost">Abbrechen</a>
        <div style="margin-left:auto;display:flex;align-items:center;gap:10px">
            <input type="hidden" name="active" id="active-input" value="<?= $isActive ? '1' : '0' ?>">
            <button type="button" class="toggle-btn <?= $isActive ? 'is-active' : '' ?>"
                    data-target="active-input" title="Aktiv/Inaktiv">
                <span class="toggle-knob"></span>
            </button>
            <span class="toggle-label" id="active-label"><?= $isActive ? 'Aktiv' : 'Inaktiv' ?></span>
        </div>
    </div>
</form>

<script>
function updateLoginMethodVisibility(method) {
    var g = document.getElementById('local-password-group');
    if (g) g.style.display = method === 'ldap' ? 'none' : '';
}
updateLoginMethodVisibility(<?= json_encode($loginMethod) ?>);

(function() {
    var btn    = document.getElementById('sig-toggle');
    var fields = document.getElementById('sig-fields');
    if (!btn || !fields) return;

    btn.addEventListener('click', function() {
        var active = btn.classList.toggle('is-active');
        fields.style.display = active ? 'grid' : 'none';
        if (!active) {
            fields.querySelectorAll('input[type="text"]').forEach(function(i) { i.value = ''; });
        }
    });
})();
</script>

<?php if (!$isNew && !empty($renderedSignature)): ?>
<div style="margin-top:28px">
    <h3 style="margin-bottom:12px">Signatur-Vorschau</h3>
    <div style="border:1px solid #e0e0e0;border-radius:6px;padding:20px;background:#fff;overflow-x:auto">
        <?= $renderedSignature ?>
    </div>
</div>
<?php endif; ?>
