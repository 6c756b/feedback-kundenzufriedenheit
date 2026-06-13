<?php
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Session;

$h           = fn(string $v) => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
$old         = Session::flash('old') ?? $user;
$errors      = Session::flash('errors') ?? [];
$loginMethod = $user['login_method'] ?? 'ldap';
$hasPassword = !empty($user['password_hash']);
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
            <label for="display_name">Anzeigename (Signatur)</label>
            <input type="text" id="display_name" name="display_name" value="<?= $h((string)($old['display_name'] ?? '')) ?>">
        </div>

        <div class="form-group">
            <label>E-Mail</label>
            <input type="email" value="<?= $h((string)($user['email'] ?? '')) ?>" disabled>
            <small style="color:#888;margin-top:4px;display:block">E-Mail-Adresse kann nicht geändert werden.</small>
        </div>
        <div class="form-group">
            <label for="job_title">Berufsbezeichnung</label>
            <input type="text" id="job_title" name="job_title" value="<?= $h((string)($old['job_title'] ?? '')) ?>">
        </div>

        <?php if ($loginMethod !== 'ldap'): ?>
        <div class="form-group">
            <label for="local_password">Lokales Passwort</label>
            <input type="password" id="local_password" name="local_password" autocomplete="new-password" placeholder="Leer lassen = keine Änderung">
            <small style="color:#888;margin-top:4px;display:block">
                Status: <?= $hasPassword ? '<strong>Gesetzt</strong>' : 'Nicht gesetzt' ?>
                <?php if ($loginMethod === 'both'): ?>
                &mdash; Fallback wenn LDAP nicht erreichbar ist.
                <?php endif; ?>
            </small>
        </div>
        <?php endif; ?>
        <div class="form-group">
            <label for="phone">Telefon</label>
            <input type="text" id="phone" name="phone" value="<?= $h((string)($old['phone'] ?? '')) ?>">
        </div>

        <?php if ($loginMethod !== 'ldap' && $hasPassword): ?>
        <div class="form-group">
            <label>&nbsp;</label>
            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;padding-top:4px">
                <input type="checkbox" name="clear_local_password" value="1">
                Lokales Passwort entfernen
            </label>
        </div>
        <?php elseif ($loginMethod !== 'ldap'): ?>
        <div></div>
        <?php endif; ?>
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

    <div class="form-actions">
        <button type="submit" class="btn-primary">Speichern</button>
    </div>
</form>

<?php if (!empty($renderedSignature)): ?>
<div style="margin-top:28px">
    <h3 style="margin-bottom:12px">Signatur-Vorschau</h3>
    <div style="border:1px solid #e0e0e0;border-radius:6px;padding:20px;background:#fff;overflow-x:auto">
        <?= $renderedSignature ?>
    </div>
</div>
<?php elseif (empty($user['display_name'])): ?>
<div style="margin-top:16px">
    <div class="flash flash-info">Anzeigename eintragen um die Signatur-Vorschau zu aktivieren.</div>
</div>
<?php endif; ?>
