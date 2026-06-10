<?php
use App\Core\Csrf;

$h      = fn(string $v) => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
$isNew  = empty($region['id']);
$action = $isNew ? '/backend/metropolregionen' : '/backend/metropolregionen/' . (int)$region['id'];
$old    = \App\Core\Session::flash('old') ?? $region;
$errors = \App\Core\Session::flash('errors') ?? [];
?>
<div class="page-header">
    <h1><?= $isNew ? 'Neue Metropolregion' : 'Metropolregion bearbeiten' ?></h1>
    <a href="/backend/metropolregionen" class="btn-ghost">Zurück</a>
</div>

<?php if ($errors): ?>
<div class="flash flash-error">
    <ul><?php foreach ($errors as $e): ?><li><?= $h($e) ?></li><?php endforeach; ?></ul>
</div>
<?php endif; ?>

<form method="post" action="<?= $h($action) ?>" class="form-card" style="max-width:500px;">
    <?= Csrf::field() ?>

    <div class="form-grid">
        <div class="form-group form-full">
            <label for="name">Name *</label>
            <input type="text" id="name" name="name" required
                   value="<?= $h((string)($old['name'] ?? '')) ?>">
        </div>

        <div class="form-group">
            <label for="sort_order">Sortierung</label>
            <input type="number" id="sort_order" name="sort_order" min="0"
                   value="<?= (int)($old['sort_order'] ?? 0) ?>">
        </div>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn-primary"><?= $isNew ? 'Anlegen' : 'Speichern' ?></button>
        <a href="/backend/metropolregionen" class="btn-ghost">Abbrechen</a>
    </div>
</form>
