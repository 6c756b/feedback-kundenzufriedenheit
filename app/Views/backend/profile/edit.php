<?php
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Session;

$h           = fn(string $v) => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
$old         = Session::flash('old') ?? $user;
$errors      = Session::flash('errors') ?? [];
$loginMethod = $user['login_method'] ?? 'ldap';
$hasPassword = !empty($user['password_hash']);
$hasSig      = !empty($old['display_name']) || !empty($old['job_title']) || !empty($old['phone']) || !empty($user['signature_image']);
?>
<div class="page-header">
    <h1>Mein Profil</h1>
</div>

<?php if ($errors): ?>
<div class="flash flash-error">
    <ul><?php foreach ($errors as $e): ?><li><?= $h($e) ?></li><?php endforeach; ?></ul>
</div>
<?php endif; ?>

<form method="post" action="/backend/profil" class="form-card" enctype="multipart/form-data">
    <?= Csrf::field() ?>

    <div class="form-grid">
        <div class="form-group">
            <label for="name">Name *</label>
            <input type="text" id="name" name="name" required value="<?= $h((string)($old['name'] ?? '')) ?>">
        </div>
        <div class="form-group">
            <label>E-Mail</label>
            <input type="email" value="<?= $h((string)($user['email'] ?? '')) ?>" disabled>
            <small style="color:#888;margin-top:4px;display:block">E-Mail-Adresse kann nicht geändert werden.</small>
        </div>

        <?php if ($loginMethod !== 'ldap'): ?>
        <div class="form-group">
            <label for="local_password">Lokales Passwort</label>
            <input type="password" id="local_password" name="local_password" autocomplete="new-password" placeholder="Leer lassen = keine Änderung">
            <small style="color:#888;margin-top:4px;display:block">
                Status: <?= $hasPassword ? '<strong>Gesetzt</strong>' : 'Nicht gesetzt' ?>
                <?php if ($loginMethod === 'both'): ?>&mdash; Fallback wenn LDAP nicht erreichbar ist.<?php endif; ?>
            </small>
            <?php if ($hasPassword): ?>
            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;margin-top:6px">
                <input type="checkbox" name="clear_local_password" value="1">
                Lokales Passwort entfernen
            </label>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <div style="margin-top:var(--gap-block);padding-top:var(--gap-block);border-top:1px solid var(--color-gray-40)">
        <div class="form-group" style="margin-bottom:var(--gap-block)">
            <label>Signatur</label>
            <div class="toggle-wrap" style="padding-top:4px">
                <button type="button" class="toggle-btn <?= $hasSig ? 'is-active' : '' ?>"
                        id="sig-toggle" title="Signatur anlegen Ja/Nein">
                    <span class="toggle-knob"></span>
                </button>
                <span class="toggle-label">Signatur anlegen</span>
            </div>
        </div>

        <div id="sig-fields" style="display:<?= $hasSig ? 'grid' : 'none' ?>;grid-template-columns:1fr 1fr;gap:var(--gap-block)">
            <div class="form-group">
                <label for="display_name">Anzeigename *</label>
                <input type="text" id="display_name" name="display_name" value="<?= $h((string)($old['display_name'] ?? '')) ?>">
            </div>
            <div class="form-group">
                <label for="job_title">Berufsbezeichnung</label>
                <input type="text" id="job_title" name="job_title" value="<?= $h((string)($old['job_title'] ?? '')) ?>">
            </div>
            <div class="form-group">
                <label for="phone">Telefon</label>
                <input type="text" id="phone" name="phone" value="<?= $h((string)($old['phone'] ?? '')) ?>">
            </div>
            <div class="form-group">
                <label for="signature_image">Profilbild (max. 2 MB)</label>
                <input type="file" id="signature_image" name="signature_image" accept="image/png,image/jpeg,image/gif,image/webp">
                <?php if (!empty($user['signature_image'])): ?>
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer;margin-top:8px">
                    <input type="checkbox" name="clear_image" value="1">
                    Bild entfernen
                </label>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn-primary">Speichern</button>
    </div>
</form>

<script>
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

<?php if (!empty($renderedSignature)): ?>
<div style="margin-top:28px">
    <h3 style="margin-bottom:12px">Signatur-Vorschau</h3>
    <div style="border:1px solid #e0e0e0;border-radius:6px;padding:20px;background:#fff;overflow-x:auto">
        <?= $renderedSignature ?>
    </div>
</div>
<?php endif; ?>
