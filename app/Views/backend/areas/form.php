<?php
use App\Core\Csrf;

$h      = fn(string $v) => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
$isNew  = empty($area['id']);
$action = $isNew ? '/backend/bereiche' : '/backend/bereiche/' . (int)$area['id'];
$old    = \App\Core\Session::flash('old') ?? $area;
$errors = \App\Core\Session::flash('errors') ?? [];
?>
<div class="page-header">
    <h1><?= $isNew ? 'Neuer Bereich' : 'Bereich bearbeiten' ?></h1>
    <a href="/backend/bereiche" class="btn-ghost">Zurück</a>
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

        <div class="form-group form-full">
            <label for="description">Beschreibung</label>
            <input type="text" id="description" name="description"
                   value="<?= $h((string)($old['description'] ?? '')) ?>">
        </div>

        <div class="form-group">
            <label for="sort_order">Sortierung</label>
            <input type="number" id="sort_order" name="sort_order" min="0"
                   value="<?= (int)($old['sort_order'] ?? 0) ?>">
        </div>

        <div class="form-group">
            <label>Aktiv</label>
            <div class="toggle-wrap">
                <input type="hidden" name="active" id="active-input"
                       value="<?= !empty($old['active']) ? '1' : '0' ?>">
                <button type="button"
                        class="toggle-btn <?= !empty($old['active']) ? 'is-active' : '' ?>"
                        data-target="active-input"
                        title="Aktiv/Inaktiv">
                    <span class="toggle-knob"></span>
                </button>
                <span class="toggle-label" id="active-label"><?= !empty($old['active']) ? 'Aktiv' : 'Inaktiv' ?></span>
            </div>
        </div>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn-primary"><?= $isNew ? 'Bereich anlegen' : 'Speichern' ?></button>
        <a href="/backend/bereiche" class="btn-ghost">Abbrechen</a>
    </div>
</form>
