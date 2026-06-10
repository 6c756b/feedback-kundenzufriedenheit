<?php
use App\Core\Auth;
$h = fn(string $v) => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');

$baseUrl = '/backend/auswertung';
$sortBy  = $filters['sort_by']  ?? 'datum';
$sortDir = strtoupper($filters['sort_dir'] ?? 'DESC');

// Build persistent QS (including sort, note_range)
$qsParts = array_filter([
    'area_id'           => $filters['area_id'] ?? '',
    'customer_name'     => $filters['customer_name'] ?? '',
    'created_by'        => $filters['created_by'] ?? '',
    'sales_user_id'     => $filters['sales_user_id'] ?? '',
    'project_lead_id'   => $filters['project_lead_id'] ?? '',
    'metropolregion_id' => $filters['metropolregion_id'] ?? '',
    'sort_by'           => $sortBy !== 'datum' ? $sortBy : '',
    'sort_dir'          => ($sortBy !== 'datum' || $sortDir !== 'DESC') ? $sortDir : '',
    'show_all'          => $filters['show_all'] ?? '',
]);
$qs = http_build_query($qsParts);
if (!empty($filters['note_range'])) {
    foreach ((array)$filters['note_range'] as $r) {
        $qs .= ($qs ? '&' : '') . 'note_range[]=' . urlencode($r);
    }
}
$queryString = $qs ? '&' . $qs : '';

// Sort link helper
$sortLink = function(string $field, string $label) use ($filters, $sortBy, $sortDir, $baseUrl, $h): string {
    $newDir = ($sortBy === $field && $sortDir === 'ASC') ? 'DESC' : 'ASC';
    $params = array_filter([
        'area_id'           => $filters['area_id'] ?? '',
        'customer_name'     => $filters['customer_name'] ?? '',
        'created_by'        => $filters['created_by'] ?? '',
        'sales_user_id'     => $filters['sales_user_id'] ?? '',
        'project_lead_id'   => $filters['project_lead_id'] ?? '',
        'metropolregion_id' => $filters['metropolregion_id'] ?? '',
        'show_all'          => $filters['show_all'] ?? '',
        'sort_by'           => $field,
        'sort_dir'          => $newDir,
    ]);
    $linkQs = http_build_query($params);
    if (!empty($filters['note_range'])) {
        foreach ((array)$filters['note_range'] as $r) {
            $linkQs .= ($linkQs ? '&' : '') . 'note_range[]=' . urlencode($r);
        }
    }
    $arrow = '';
    if ($sortBy === $field) {
        $arrow = $sortDir === 'ASC' ? ' ↑' : ' ↓';
    }
    $cls = 'sort-link' . ($sortBy === $field ? ' sort-active' : '');
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
            <th><?= $sortLink('status', 'Status') ?></th>
            <th><?= $sortLink('referenz', 'Referenz') ?></th>
            <th>Aktionen</th>
        </tr>
    </thead>
    <tbody>
    <?php if (!$surveys): ?>
        <tr><td colspan="8" class="text-center text-muted">Keine Auswertungen gefunden.</td></tr>
    <?php endif; ?>
    <?php foreach ($surveys as $survey): ?>
    <tr class="<?= !$survey['read_at'] && $survey['status'] === 'completed' ? 'row-unread' : '' ?>">
        <td class="cell-two-line">
            <div class="cell-line1"><?= $h($survey['customer_name']) ?></div>
            <div class="cell-line2"><?= $h($survey['project_name']) ?></div>
        </td>
        <td><?= str_replace(', ', '<br>', $h($survey['area_names'] ?? '')) ?></td>
        <td><?= $h($survey['created_by_name']) ?></td>
        <td class="text-muted"><?= $h(date('d.m.Y', strtotime($survey['updated_at']))) ?></td>
        <td><?= $survey['avg'] !== null ? number_format((float)$survey['avg'], 2, ',', '') : '–' ?></td>
        <td>
            <?php if ($survey['read_at']): ?>
                <span class="badge badge-open">Gelesen</span>
            <?php else: ?>
                <span class="badge badge-unread">Ungelesen</span>
            <?php endif; ?>
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
                    data-survey-read="<?= $survey['read_at'] ? '1' : '0' ?>"
                    data-survey-status="<?= $h($survey['status']) ?>"><?= icon('info') ?></button>
            <?php if (Auth::hasRole('staff')): ?>
            <button type="button" class="btn-icon" title="Als gelesen markieren"
                    data-mark-read="<?= (int)$survey['id'] ?>"
                    <?= $survey['read_at'] ? 'hidden' : '' ?>><?= icon('check') ?></button>
            <button type="button" class="btn-icon" title="Als ungelesen markieren"
                    data-mark-unread="<?= (int)$survey['id'] ?>"
                    <?= !$survey['read_at'] ? 'hidden' : '' ?>><?= icon('eye-off') ?></button>
            <?php endif; ?>
        </div></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<div class="table-meta text-muted"><?= (int)$totalCount ?> Einträge</div>
<?php require ROOT . '/app/Views/partials/pagination.php' ?>
