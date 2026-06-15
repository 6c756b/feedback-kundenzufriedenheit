<?php
use App\Core\Csrf;
use App\Core\Session;

$h      = fn(string $v) => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
$old    = Session::flash('old') ?? $question;
$isNew  = empty($question['id']);
$action = $isNew ? '/backend/fragen' : '/backend/fragen/' . (int)$question['id'];
$errors = Session::flash('errors') ?? [];
$type   = $old['type'] ?? 'slider';
$isActive = !isset($old['active']) || $old['active'];

$effectiveAreaId = (int)($old['area_id'] ?? $preAreaId ?? 0);
$hasPresetArea   = $effectiveAreaId > 0 && $preArea !== null;
?>
<div class="page-header">
    <h1><?= $isNew ? 'Neue Frage' : 'Frage bearbeiten' ?></h1>
    <?php if ($hasPresetArea): ?>
    <a href="/backend/fragen?area_id=<?= $effectiveAreaId ?>" class="btn-ghost">Zurück zu <?= $h($preArea['name']) ?></a>
    <?php else: ?>
    <a href="/backend/fragen" class="btn-ghost">Zurück</a>
    <?php endif; ?>
</div>

<?php if ($errors): ?>
<div class="flash flash-error">
    <ul><?php foreach ($errors as $e): ?><li><?= $h($e) ?></li><?php endforeach; ?></ul>
</div>
<?php endif; ?>

<form method="post" action="<?= $h($action) ?>" class="form-card" id="question-form">
    <?= Csrf::field() ?>

    <div class="form-grid">
        <?php if ($hasPresetArea): ?>
        <input type="hidden" name="area_id" value="<?= $effectiveAreaId ?>">
        <div class="form-group">
            <label>Bereich</label>
            <div class="readonly-field"><?= $h($preArea['name']) ?></div>
            <?php if (!$preArea['active']): ?>
            <p style="margin-top:6px;font-size:13px;color:#a05000">
                Dieser Bereich ist inaktiv – die Frage wird Teilnehmern nicht angezeigt.
            </p>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <div class="form-group">
            <label for="area_id">Bereich *</label>
            <select id="area_id" name="area_id" required>
                <option value="">- wählen -</option>
                <?php foreach ($areas as $area): ?>
                <option value="<?= (int)$area['id'] ?>"
                    <?= ($old['area_id'] ?? '') == $area['id'] ? 'selected' : '' ?>>
                    <?= $h($area['name']) ?><?= $area['active'] ? '' : ' (inaktiv)' ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>

        <div class="form-group">
            <label for="sequence">Sequenz *</label>
            <input type="number" id="sequence" name="sequence" min="0" required
                   value="<?= (int)($old['sequence'] ?? 0) ?>">
        </div>

        <div class="form-group form-full">
            <label for="label_short">Kurzform *</label>
            <input type="text" id="label_short" name="label_short" required
                   value="<?= $h((string)($old['label_short'] ?? '')) ?>">
        </div>

        <div class="form-group form-full">
            <label for="label_long">Langform / Fragetext *</label>
            <textarea id="label_long" name="label_long" rows="3" required><?= $h((string)($old['label_long'] ?? '')) ?></textarea>
        </div>

        <div class="form-group form-full">
            <label>Fragetyp *</label>
            <div class="radio-group">
                <?php foreach (['slider' => 'Slider', 'freitext' => 'Freitext', 'slider_freitext' => 'Slider + Freitext'] as $val => $label): ?>
                <label class="radio-label">
                    <input type="radio" name="type" value="<?= $h($val) ?>"
                        <?= ($type === $val) ? 'checked' : '' ?> required>
                    <?= $h($label) ?>
                </label>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="form-group js-show-slider">
            <label for="slider_label_min">Slider-Label Note 1 (beste)</label>
            <input type="text" id="slider_label_min" name="slider_label_min"
                   value="<?= $h((string)($old['slider_label_min'] ?? '')) ?>">
        </div>
        <div class="form-group js-show-slider">
            <label for="slider_label_max">Slider-Label Note 6 (schlechteste)</label>
            <input type="text" id="slider_label_max" name="slider_label_max"
                   value="<?= $h((string)($old['slider_label_max'] ?? '')) ?>">
        </div>

        <div class="form-group js-show-freitext">
            <label for="freitext_context">Freitext-Label</label>
            <input type="text" id="freitext_context" name="freitext_context"
                   value="<?= $h((string)($old['freitext_context'] ?? '')) ?>">
        </div>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn-primary"><?= $isNew ? 'Frage anlegen' : 'Speichern' ?></button>
        <?php if ($hasPresetArea): ?>
        <a href="/backend/fragen?area_id=<?= $effectiveAreaId ?>" class="btn-ghost">Abbrechen</a>
        <?php else: ?>
        <a href="/backend/fragen" class="btn-ghost">Abbrechen</a>
        <?php endif; ?>
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

<script>
(function() {
    const form = document.getElementById('question-form');
    function updateVisibility() {
        const type = form.querySelector('input[name="type"]:checked')?.value;
        form.querySelectorAll('.js-show-slider').forEach(el => {
            el.style.display = (type === 'slider' || type === 'slider_freitext') ? '' : 'none';
        });
        form.querySelectorAll('.js-show-freitext').forEach(el => {
            el.style.display = (type === 'freitext' || type === 'slider_freitext') ? '' : 'none';
        });
    }
    form.querySelectorAll('input[name="type"]').forEach(r => r.addEventListener('change', updateVisibility));
    updateVisibility();
})();
</script>
