<?php
$h = fn(string $v) => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
$selectedNoteRanges = (array)($filters['note_range'] ?? []);
$hasFilters = !empty($filters['area_id'])
    || !empty($filters['customer_name'])
    || !empty($filters['created_by'])
    || !empty($filters['sales_user_id'])
    || !empty($filters['project_lead_id'])
    || !empty($filters['metropolregion_id'])
    || !empty($selectedNoteRanges);
$resetUrl = '/backend/auswertung' . ($filters['show_all'] ?? '' ? '?show_all=1' : '');
?>
<div class="page-header">
    <h1>Auswertung</h1>
</div>

<form method="get" action="/backend/auswertung" class="filter-bar" data-auto-filter>
    <?php if (!empty($filters['show_all'])): ?>
    <input type="hidden" name="show_all" value="1">
    <?php endif; ?>
    <?php if (!empty($filters['sort_by']) && $filters['sort_by'] !== 'datum'): ?>
    <input type="hidden" name="sort_by" value="<?= $h($filters['sort_by']) ?>">
    <?php endif; ?>
    <?php if (!empty($filters['sort_dir']) && $filters['sort_dir'] !== 'DESC'): ?>
    <input type="hidden" name="sort_dir" value="<?= $h($filters['sort_dir']) ?>">
    <?php endif; ?>

    <div class="filter-row">
        <select name="area_id" class="filter-select">
            <option value="">Alle Bereiche</option>
            <?php foreach ($areas as $area): ?>
            <option value="<?= (int)$area['id'] ?>" <?= ($filters['area_id'] ?? '') == $area['id'] ? 'selected' : '' ?>>
                <?= $h($area['name']) ?>
            </option>
            <?php endforeach; ?>
        </select>

        <div class="filter-search-wrap">
            <input type="text" name="customer_name" class="filter-text"
                   placeholder="Kunde suchen…" autocomplete="off"
                   value="<?= $h($filters['customer_name'] ?? '') ?>"
                   data-ac='<?= json_encode(array_values($customerNames), JSON_UNESCAPED_UNICODE) ?>'>
            <div class="ac-dropdown" hidden></div>
        </div>

        <select name="created_by" class="filter-select">
            <option value="">Alle Befrager</option>
            <?php foreach ($users as $u): ?>
            <option value="<?= (int)$u['id'] ?>" <?= ($filters['created_by'] ?? '') == $u['id'] ? 'selected' : '' ?>>
                <?= $h($u['name']) ?>
            </option>
            <?php endforeach; ?>
        </select>

        <?php if (!empty($salesUsers)): ?>
        <select name="sales_user_id" class="filter-select">
            <option value="">Alle Vertriebler</option>
            <?php foreach ($salesUsers as $su): ?>
            <option value="<?= (int)$su['id'] ?>" <?= ($filters['sales_user_id'] ?? '') == $su['id'] ? 'selected' : '' ?>>
                <?= $h($su['name']) ?>
            </option>
            <?php endforeach; ?>
        </select>
        <?php endif; ?>

        <?php if (!empty($projectLeadUsers)): ?>
        <select name="project_lead_id" class="filter-select">
            <option value="">Alle Projektleitungen</option>
            <?php foreach ($projectLeadUsers as $pl): ?>
            <option value="<?= (int)$pl['id'] ?>" <?= ($filters['project_lead_id'] ?? '') == $pl['id'] ? 'selected' : '' ?>>
                <?= $h($pl['name']) ?>
            </option>
            <?php endforeach; ?>
        </select>
        <?php endif; ?>

        <select name="metropolregion_id" class="filter-select">
            <option value="">Alle Metropolregionen</option>
            <?php foreach ($metropolregionen as $mr): ?>
            <option value="<?= (int)$mr['id'] ?>" <?= ($filters['metropolregion_id'] ?? '') == $mr['id'] ? 'selected' : '' ?>>
                <?= $h($mr['name']) ?>
            </option>
            <?php endforeach; ?>
        </select>

        <a href="<?= $h($resetUrl) ?>" class="filter-reset <?= $hasFilters ? '' : 'filter-reset-inactive' ?>">× zurücksetzen</a>
    </div>

    <div class="filter-row" style="margin-top:6px;align-items:center;gap:8px">
        <span style="font-size:12px;color:var(--color-gray-60);white-space:nowrap">Note:</span>
        <?php foreach (['1-2','2-3','3-4','4-5','5-6'] as $range): ?>
        <label class="note-range-chip">
            <input type="checkbox" name="note_range[]" value="<?= $range ?>"
                   <?= in_array($range, $selectedNoteRanges) ? 'checked' : '' ?>>
            <span><?= $range ?></span>
        </label>
        <?php endforeach; ?>
    </div>
</form>

<div id="list-results">
<?php require ROOT . '/app/Views/backend/evaluation/_list.php' ?>
</div>

<!-- Detail-Modal -->
<div id="detail-modal" class="modal" hidden>
    <div class="modal-backdrop" data-close-modal></div>
    <div class="modal-box modal-box-lg">
        <div class="modal-header">
            <h2 id="detail-modal-title">Auswertungsdetail</h2>
            <button type="button" class="modal-close" data-close-modal>&times;</button>
        </div>
        <div class="modal-body" id="detail-modal-body">
            <div class="loading">Lade…</div>
        </div>
        <div class="modal-footer" id="detail-modal-footer">
            <button type="button" class="btn-ghost" data-close-modal>Schließen</button>
        </div>
    </div>
</div>
