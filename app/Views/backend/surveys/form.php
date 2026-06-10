<?php
use App\Core\Csrf;

$h    = fn(string $v) => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
$old  = \App\Core\Session::flash('old') ?? $survey;
$isNew = empty($survey['id']);
$action = $isNew ? '/backend/befragungen' : '/backend/befragungen/' . (int)$survey['id'];
$errors = \App\Core\Session::flash('errors') ?? [];

// Ausgewählte Bereiche: aus flash (POST-Array) oder aus der DB (survey['area_ids'])
$selectedAreaIds = array_map('intval', (array)($old['area_ids'] ?? $survey['area_ids'] ?? []));

$salutations = ['' => '– Keine Angabe –', 'herr' => 'Herr', 'frau' => 'Frau'];
?>
<div class="page-header">
    <h1><?= $isNew ? 'Neue Befragung' : 'Befragung bearbeiten' ?></h1>
    <a href="/backend/befragungen" class="btn-ghost">Zurück</a>
</div>

<?php if ($errors): ?>
<div class="flash flash-error">
    <ul><?php foreach ($errors as $e): ?><li><?= $h($e) ?></li><?php endforeach; ?></ul>
</div>
<?php endif; ?>

<form method="post" action="<?= $h($action) ?>" class="form-card">
    <?= Csrf::field() ?>

    <div class="form-grid">
        <!-- Zeile 1: Kunde | Ansprechpartner -->
        <div class="form-group">
            <label for="customer_name">Kunde *</label>
            <input type="text" id="customer_name" name="customer_name" required
                   value="<?= $h((string)($old['customer_name'] ?? '')) ?>">
        </div>
        <div class="form-group">
            <label for="contact_person">Ansprechpartner *</label>
            <input type="text" id="contact_person" name="contact_person" required
                   value="<?= $h((string)($old['contact_person'] ?? '')) ?>">
        </div>

        <!-- Zeile 2: Projektname | Anrede -->
        <div class="form-group">
            <label for="project_name">Projektname * <small>(Wird im E-Mail-Betreff angezeigt!)</small></label>
            <input type="text" id="project_name" name="project_name" required
                   value="<?= $h((string)($old['project_name'] ?? '')) ?>">
        </div>
        <div class="form-group">
            <label for="contact_salutation">Anrede</label>
            <select id="contact_salutation" name="contact_salutation">
                <?php foreach ($salutations as $val => $label): ?>
                <option value="<?= $h($val) ?>" <?= ($old['contact_salutation'] ?? '') === $val ? 'selected' : '' ?>>
                    <?= $h($label) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Zeile 3: Projekt-ID | E-Mail -->
        <div class="form-group">
            <label for="project_id">Projekt-ID</label>
            <input type="text" id="project_id" name="project_id"
                   value="<?= $h((string)($old['project_id'] ?? '')) ?>">
        </div>
        <div class="form-group">
            <label for="contact_email">E-Mail des Ansprechpartners *</label>
            <input type="email" id="contact_email" name="contact_email" required
                   value="<?= $h((string)($old['contact_email'] ?? '')) ?>">
        </div>

        <!-- Bereiche: Chip-Picker (full width) -->
        <div class="form-group form-full">
            <label>Bereiche * <small>(mind. 1 auswählen)</small></label>
            <div class="area-chips">
                <?php foreach ($areas as $area): ?>
                <label class="area-chip">
                    <input type="checkbox" name="area_ids[]" value="<?= (int)$area['id'] ?>"
                           <?= in_array((int)$area['id'], $selectedAreaIds) ? 'checked' : '' ?>>
                    <svg class="area-chip-icon area-chip-icon-add" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="8" cy="8" r="6.5"/><path d="M8 5.5v5M5.5 8h5"/>
                    </svg>
                    <svg class="area-chip-icon area-chip-icon-check" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 8l3.5 3.5L13 4.5"/>
                    </svg>
                    <?= $h($area['name']) ?>
                </label>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Vertrieb | Projektleitung -->
        <?php if (!empty($salesUsers)): ?>
        <div class="form-group">
            <label for="sales_user_id">Vertrieb</label>
            <select id="sales_user_id" name="sales_user_id">
                <option value="">– kein Vertriebler –</option>
                <?php foreach ($salesUsers as $su): ?>
                <option value="<?= (int)$su['id'] ?>"
                    <?= ($old['sales_user_id'] ?? '') == $su['id'] ? 'selected' : '' ?>>
                    <?= $h($su['name']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>

        <?php if (!empty($projectLeadUsers)): ?>
        <div class="form-group">
            <label for="project_lead_id">Projektleitung</label>
            <select id="project_lead_id" name="project_lead_id">
                <option value="">– keine Projektleitung –</option>
                <?php foreach ($projectLeadUsers as $pl): ?>
                <option value="<?= (int)$pl['id'] ?>"
                    <?= ($old['project_lead_id'] ?? '') == $pl['id'] ? 'selected' : '' ?>>
                    <?= $h($pl['name']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>

        <div class="form-group">
            <label for="metropolregion_id">Metropolregion</label>
            <select id="metropolregion_id" name="metropolregion_id">
                <option value="">– keine Angabe –</option>
                <?php foreach ($metropolregionen as $mr): ?>
                <option value="<?= (int)$mr['id'] ?>"
                    <?= ($old['metropolregion_id'] ?? '') == $mr['id'] ? 'selected' : '' ?>>
                    <?= $h($mr['name']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Rest -->
        <div class="form-group form-full">
            <label for="internal_notes">Interne Anmerkungen</label>
            <textarea id="internal_notes" name="internal_notes" rows="3"><?= $h((string)($old['internal_notes'] ?? '')) ?></textarea>
        </div>
        <div class="form-group">
            <label class="checkbox-label">
                <input type="checkbox" name="reference_requested" value="1"
                    <?= !empty($old['reference_requested']) ? 'checked' : '' ?>>
                Referenz einholen
            </label>
        </div>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn-primary"><?= $isNew ? 'Befragung anlegen' : 'Speichern' ?></button>
        <a href="/backend/befragungen" class="btn-ghost">Abbrechen</a>
    </div>
</form>
