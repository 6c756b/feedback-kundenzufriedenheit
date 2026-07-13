<?php
$h = fn(?string $v) => htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8');
$statusLabels = ['archived' => 'Archiviert', 'cancelled' => 'Abgebrochen'];
$baseUrl = '/backend/archiv';
$sortBy  = $filters['sort_by']  ?? 'datum';
$sortDir = strtoupper($filters['sort_dir'] ?? 'DESC');

$qsParts = array_filter([
    'area_id'           => $filters['area_id'] ?? '',
    'customer_name'     => $filters['customer_name'] ?? '',
    'created_by'        => $filters['created_by'] ?? '',
    'sales_user_id'     => $filters['sales_user_id'] ?? '',
    'project_lead_id'   => $filters['project_lead_id'] ?? '',
    'metropolregion_id' => $filters['metropolregion_id'] ?? '',
    'sort_by'           => $sortBy !== 'datum' ? $sortBy : '',
    'sort_dir'          => ($sortBy !== 'datum' || $sortDir !== 'DESC') ? $sortDir : '',
]);
$qs          = http_build_query($qsParts);
$queryString = $qs ? '&' . $qs : '';

$sortLink = function(string $field, string $label) use ($filters, $sortBy, $sortDir, $baseUrl, $h): string {
    $newDir  = ($sortBy === $field && $sortDir === 'ASC') ? 'DESC' : 'ASC';
    $params  = array_filter([
        'area_id'           => $filters['area_id'] ?? '',
        'customer_name'     => $filters['customer_name'] ?? '',
        'created_by'        => $filters['created_by'] ?? '',
        'sales_user_id'     => $filters['sales_user_id'] ?? '',
        'project_lead_id'   => $filters['project_lead_id'] ?? '',
        'metropolregion_id' => $filters['metropolregion_id'] ?? '',
        'sort_by'           => $field,
        'sort_dir'          => $newDir,
    ]);
    $linkQs = http_build_query($params);
    $arrow  = $sortBy === $field ? ($sortDir === 'ASC' ? ' ↑' : ' ↓') : '';
    $cls    = 'sort-link' . ($sortBy === $field ? ' sort-active' : '');
    return '<a href="' . $h($baseUrl . ($linkQs ? '?' . $linkQs : '')) . '" class="' . $cls . '">' . $h($label) . $arrow . '</a>';
};
?>
<table class="data-table">
    <thead>
        <tr>
            <th><?= $sortLink('kunde', 'Kunde / Projekt') ?></th>
            <th>Bereiche</th>
            <th><?= $sortLink('befrager', 'Befrager') ?></th>
            <th><?= $sortLink('datum', 'Abgeschlossen') ?></th>
            <th style="white-space:nowrap"><?= $sortLink('note', 'Ø Note') ?></th>
            <th>Status</th>
            <th>Referenz</th>
            <th>Aktionen</th>
        </tr>
    </thead>
    <tbody>
    <?php if (!$surveys): ?>
        <tr><td colspan="8" class="text-center text-muted">Keine archivierten Befragungen gefunden.</td></tr>
    <?php endif; ?>
    <?php foreach ($surveys as $survey): ?>
    <tr>
        <td class="cell-two-line">
            <div class="cell-line1"><?= $h($survey['customer_name']) ?></div>
            <div class="cell-line2"><?= $h($survey['project_name']) ?></div>
        </td>
        <td><?= str_replace(', ', '<br>', $h($survey['area_names'] ?? '')) ?></td>
        <td><?= $h($survey['created_by_name']) ?></td>
        <td class="text-muted"><?= $h(date('d.m.Y', strtotime($survey['updated_at']))) ?></td>
        <td><?= $survey['avg'] !== null ? number_format((float)$survey['avg'], 2, ',', '') : '–' ?></td>
        <td>
            <span class="badge badge-<?= $h($survey['status']) ?>"><?= $h($statusLabels[$survey['status']] ?? $survey['status']) ?></span>
        </td>
        <td>
            <?php if ($survey['reference_granted']): ?>
                <span class="badge badge-open">Ja ✓</span>
            <?php elseif ($survey['reference_requested'] && $survey['reference_granted'] === null): ?>
                <span class="badge badge-completed" style="background:var(--color-gray-40);color:var(--color-navy-60)">Angefragt</span>
            <?php elseif ($survey['reference_requested'] && $survey['reference_granted'] == 0): ?>
                <span class="text-muted" style="font-size:12px">Nein</span>
            <?php endif; ?>
        </td>
        <td><div class="actions">
            <button type="button" class="btn-icon btn-primary-icon" title="Detail anzeigen"
                    data-detail-id="<?= (int)$survey['id'] ?>"
                    data-survey-read="1"
                    data-survey-status="<?= $h($survey['status']) ?>"><?= icon('info') ?></button>
            <a href="/backend/auswertung/<?= (int)$survey['id'] ?>/pdf" class="btn-icon btn-pdf" title="PDF exportieren"><?= icon('document') ?> PDF</a>
        </div></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<div class="table-meta text-muted"><?= (int)$totalCount ?> Einträge</div>
<?php require ROOT . '/app/Views/partials/pagination.php' ?>
