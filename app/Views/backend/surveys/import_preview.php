<?php
use App\Core\Csrf;
$h = fn(string $v) => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');

// Bereiche-Map für Anzeige (area_id → name)
$areaNameMap = [];
foreach ($areas as $area) {
    $areaNameMap[(int)$area['id']] = $area['name'];
}

// Metropolregion-Map für Anzeige (id → name)
$metropolregionNameMap = [];
foreach ($metropolregionen as $mr) {
    $metropolregionNameMap[(int)$mr['id']] = $mr['name'];
}

// Projektleitungs-Map für Anzeige (id → name)
$projectLeadNameMap = [];
foreach ($projectLeadUsers as $pl) {
    $projectLeadNameMap[(int)$pl['id']] = $pl['name'];
}
?>
<div class="page-header">
    <h1>Import-Vorschau</h1>
    <a href="/backend/befragungen/import" class="btn-ghost">Zurück</a>
</div>

<div style="margin-bottom:1rem;display:flex;align-items:center;gap:1rem;flex-wrap:wrap;">
    <span>
        <strong><?= $validCount ?></strong> Zeile(n) importierbar
        <?php if ($errorCount > 0): ?>
        &nbsp;·&nbsp; <strong style="color:var(--danger)"><?= $errorCount ?></strong> mit Fehlern (werden übersprungen)
        <?php endif; ?>
    </span>
</div>

<?php if ($validCount === 0): ?>
<div class="flash flash-error">Keine importierbaren Zeilen gefunden. Bitte CSV prüfen.</div>
<div style="margin-top:1rem;">
    <a href="/backend/befragungen/import" class="btn-primary">Zurück zum Upload</a>
</div>
<?php else: ?>

<form method="post" action="/backend/befragungen/import/bestaetigen">
    <?= Csrf::field() ?>
    <div class="form-actions" style="margin-bottom:1rem;">
        <button type="submit" class="btn-primary">
            <?= $validCount ?> Befragung(en) importieren
        </button>
        <a href="/backend/befragungen" class="btn-ghost">Abbrechen</a>
    </div>
</form>

<?php endif; ?>

<div style="overflow-x:auto;">
<table class="data-table" style="font-size:.82rem;min-width:900px;">
    <thead>
        <tr>
            <th style="width:2rem;">#</th>
            <th>Kunde</th>
            <th>Projektname</th>
            <th>Ansprechpartner</th>
            <th>E-Mail</th>
            <th>Bereiche</th>
            <th>Vertrieb</th>
            <th>Metropolregion</th>
            <th>Projektleitung</th>
            <th>Ref.</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($rows as $row): ?>
    <?php
        $hasError   = !empty($row['_errors']);
        $hasWarning = !empty($row['_warnings']);
        $rowStyle   = $hasError ? 'background:rgba(220,38,38,.07);' : ($hasWarning ? 'background:rgba(245,158,11,.07);' : '');
        $areaLabels = array_map(fn($id) => $areaNameMap[$id] ?? "#{$id}", $row['area_ids'] ?? []);
    ?>
    <tr style="<?= $rowStyle ?>">
        <td style="color:var(--text-muted)"><?= (int)$row['_line'] ?></td>
        <td><?= $h($row['customer_name']) ?></td>
        <td>
            <?= $h($row['project_name']) ?>
            <?php if ($row['project_id']): ?>
            <small style="display:block;color:var(--text-muted)"><?= $h($row['project_id']) ?></small>
            <?php endif; ?>
        </td>
        <td>
            <?php if ($row['contact_salutation']): ?>
            <small style="color:var(--text-muted)"><?= $h(ucfirst($row['contact_salutation'])) ?> </small>
            <?php endif; ?>
            <?= $h($row['contact_person']) ?>
        </td>
        <td><?= $h($row['contact_email']) ?></td>
        <td><?= $h(implode(', ', $areaLabels)) ?></td>
        <td><?= $h($row['sales_raw'] ?: '–') ?></td>
        <td><?= $h($row['metropolregion_id'] ? ($metropolregionNameMap[$row['metropolregion_id']] ?? $row['metropolregion_raw']) : ($row['metropolregion_raw'] ?: '–')) ?></td>
        <td><?= $h($row['project_lead_id'] ? ($projectLeadNameMap[$row['project_lead_id']] ?? $row['project_lead_raw']) : ($row['project_lead_raw'] ?: '–')) ?></td>
        <td><?= $row['reference_requested'] ? 'Ja' : 'Nein' ?></td>
        <td>
            <?php if ($hasError): ?>
                <span style="color:var(--danger);font-weight:600;">Fehler</span>
                <ul style="margin:.25rem 0 0;padding-left:1rem;font-size:.8rem;color:var(--danger);">
                    <?php foreach ($row['_errors'] as $e): ?>
                    <li><?= $h($e) ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php elseif ($hasWarning): ?>
                <span style="color:#b45309;font-weight:600;">Warnung</span>
                <ul style="margin:.25rem 0 0;padding-left:1rem;font-size:.8rem;color:#b45309;">
                    <?php foreach ($row['_warnings'] as $w): ?>
                    <li><?= $h($w) ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <span style="color:var(--success)">OK</span>
            <?php endif; ?>
        </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>

<?php if ($validCount > 0): ?>
<form method="post" action="/backend/befragungen/import/bestaetigen" style="margin-top:1rem;">
    <?= Csrf::field() ?>
    <div class="form-actions">
        <button type="submit" class="btn-primary">
            <?= $validCount ?> Befragung(en) importieren
        </button>
        <a href="/backend/befragungen" class="btn-ghost">Abbrechen</a>
    </div>
</form>
<?php endif; ?>
