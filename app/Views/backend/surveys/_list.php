<?php
use App\Core\Auth;
$h = fn(string $v) => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
$statusLabels = ['open' => 'Offen', 'started' => 'Gestartet', 'completed' => 'Abgeschlossen', 'cancelled' => 'Abgebrochen', 'archived' => 'Archiviert'];
$baseUrl = '/backend/befragungen';
$sortBy  = $filters['sort_by']  ?? 'erstellt';
$sortDir = strtoupper($filters['sort_dir'] ?? 'DESC');

$qsParts = array_filter([
    'area_id'           => $filters['area_id'] ?? '',
    'status'            => $filters['status_filter_raw'] ?? '',
    'customer_name'     => $filters['customer_name'] ?? '',
    'created_from'      => $filters['created_from'] ?? '',
    'created_to'        => $filters['created_to'] ?? '',
    'created_by'        => $filters['created_by'] ?? '',
    'sales_user_id'     => $filters['sales_user_id'] ?? '',
    'project_lead_id'   => $filters['project_lead_id'] ?? '',
    'metropolregion_id' => $filters['metropolregion_id'] ?? '',
    'sort_by'           => $sortBy !== 'erstellt' ? $sortBy : '',
    'sort_dir'          => ($sortBy !== 'erstellt' || $sortDir !== 'DESC') ? $sortDir : '',
]);
$qs          = http_build_query($qsParts);
$queryString = $qs ? '&' . $qs : '';

$sortLink = function(string $field, string $label) use ($filters, $sortBy, $sortDir, $baseUrl, $h): string {
    $newDir = ($sortBy === $field && $sortDir === 'ASC') ? 'DESC' : 'ASC';
    $params = array_filter([
        'area_id'           => $filters['area_id'] ?? '',
        'status'            => $filters['status_filter_raw'] ?? '',
        'customer_name'     => $filters['customer_name'] ?? '',
        'created_from'      => $filters['created_from'] ?? '',
        'created_to'        => $filters['created_to'] ?? '',
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
            <th><?= $sortLink('status', 'Status') ?></th>
            <th><?= $sortLink('erstellt', 'Erstellt') ?></th>
            <th><?= $sortLink('befrager', 'Von') ?></th>
            <th>Code</th>
            <th class="col-icon" title="E-Mail"><?= icon('paperplane') ?></th>
            <th>Aktionen</th>
        </tr>
    </thead>
    <tbody>
    <?php if (!$surveys): ?>
        <tr><td colspan="8" class="text-center text-muted">Keine Befragungen gefunden.</td></tr>
    <?php endif; ?>
    <?php foreach ($surveys as $survey): ?>
        <tr>
            <td class="cell-two-line">
                <div class="cell-line1"><?= $h($survey['customer_name']) ?></div>
                <div class="cell-line2"><?= $h($survey['project_name']) ?></div>
            </td>
            <td><?= str_replace(', ', '<br>', $h($survey['area_names'] ?? '')) ?></td>
            <td><span class="badge badge-<?= $h($survey['status']) ?>"><?= $h($statusLabels[$survey['status']] ?? $survey['status']) ?></span></td>
            <td class="text-muted"><?= $h(date('d.m.Y', strtotime($survey['created_at']))) ?></td>
            <td><?= $h($survey['created_by_name']) ?></td>
            <td><code><?= $h($survey['code']) ?></code></td>
            <td class="col-icon">
                <?php if ($survey['email_sent_at']): ?>
                <span class="icon-sent" title="E-Mail versendet am <?= $h(date('d.m.Y H:i', strtotime($survey['email_sent_at']))) ?>"><?= icon('paperplane') ?></span>
                <?php else: ?>
                <span class="icon-not-sent" title="Noch keine E-Mail versendet"><?= icon('paperplane') ?></span>
                <?php endif; ?>
            </td>
            <td><div class="actions">
                <a href="/backend/befragungen/<?= (int)$survey['id'] ?>" class="btn-icon" title="Ansehen"><?= icon('info') ?></a>
                <?php if (Auth::hasRole('staff') && $survey['status'] === 'open'): ?>
                <a href="/backend/befragungen/<?= (int)$survey['id'] ?>/bearbeiten" class="btn-icon" title="Bearbeiten"><?= icon('pencil') ?></a>
                <?php endif; ?>
            </div></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<div class="table-meta text-muted"><?= (int)$totalCount ?> Einträge</div>
<?php require ROOT . '/app/Views/partials/pagination.php' ?>
