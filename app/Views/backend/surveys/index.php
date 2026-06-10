<?php
use App\Core\Auth;
$h = fn(string $v) => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
$statusFilter = $filters['status_filter_raw'] ?? null;
$hasFilters = array_filter([
    $filters['area_id'] ?? '',
    $statusFilter ?? '',
    $filters['customer_name'] ?? '',
    $filters['created_from'] ?? '',
    $filters['created_to'] ?? '',
    $filters['created_by'] ?? '',
    $filters['sales_user_id'] ?? '',
    $filters['project_lead_id'] ?? '',
    $filters['metropolregion_id'] ?? '',
]);
?>
<div class="page-header">
    <h1>Befragungen</h1>
    <?php if (Auth::hasRole('staff')): ?>
    <div class="header-actions">
        <a href="/backend/befragungen/import" class="btn-ghost">↑ Importieren</a>
        <a href="/backend/befragungen/neu" class="btn-primary">+ Neue Befragung</a>
    </div>
    <?php endif; ?>
</div>

<form method="get" action="/backend/befragungen" class="filter-bar" data-auto-filter>
    <div class="filter-row">
        <select name="area_id" class="filter-select">
            <option value="">Alle Bereiche</option>
            <?php foreach ($areas as $area): ?>
            <?php if ($area['id'] == 1) continue; ?>
            <option value="<?= (int)$area['id'] ?>" <?= ($filters['area_id'] ?? '') == $area['id'] ? 'selected' : '' ?>>
                <?= $h($area['name']) ?>
            </option>
            <?php endforeach; ?>
        </select>

        <select name="status" class="filter-select">
            <option value="" <?= !$statusFilter ? 'selected' : '' ?>>Alle Status</option>
            <option value="open" <?= $statusFilter === 'open' ? 'selected' : '' ?>>Offen</option>
            <option value="started" <?= $statusFilter === 'started' ? 'selected' : '' ?>>Gestartet</option>
        </select>

        <div class="filter-search-wrap">
            <input type="text" name="customer_name" class="filter-text"
                   placeholder="Kunde suchen…" list="af-customers" autocomplete="off"
                   value="<?= $h($filters['customer_name'] ?? '') ?>">
            <datalist id="af-customers">
                <?php foreach ($customerNames as $cn): ?>
                <option value="<?= $h($cn) ?>">
                <?php endforeach; ?>
            </datalist>
        </div>

        <select name="created_by" class="filter-select">
            <option value="">Alle Ersteller</option>
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

        <span class="filter-date-range">
            <input type="date" name="created_from" class="filter-date"
                   title="Erstellt von" value="<?= $h($filters['created_from'] ?? '') ?>">
            <span class="filter-date-sep">–</span>
            <input type="date" name="created_to" class="filter-date"
                   title="Erstellt bis" value="<?= $h($filters['created_to'] ?? '') ?>">
        </span>

        <a href="/backend/befragungen" class="filter-reset <?= $hasFilters ? '' : 'filter-reset-inactive' ?>">× zurücksetzen</a>
    </div>
</form>

<div id="list-results">
<?php require ROOT . '/app/Views/backend/surveys/_list.php' ?>
</div>
