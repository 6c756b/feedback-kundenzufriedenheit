<?php $h = fn(string $v) => htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); ?>
<div class="page-header">
    <h1>Protokoll</h1>
</div>

<form method="get" action="/backend/logs" class="filter-bar">
    <div class="filter-bar-inputs">
        <select name="actor_type">
            <option value="">Alle Typen</option>
            <option value="backend" <?= ($filters['actor_type'] ?? '') === 'backend' ? 'selected' : '' ?>>Backend</option>
            <option value="frontend" <?= ($filters['actor_type'] ?? '') === 'frontend' ? 'selected' : '' ?>>Frontend</option>
        </select>
        <input type="text" name="action" placeholder="Aktion…" value="<?= $h($filters['action'] ?? '') ?>">
        <input type="date" name="date_from" value="<?= $h($filters['date_from'] ?? '') ?>">
        <input type="date" name="date_to" value="<?= $h($filters['date_to'] ?? '') ?>">
    </div>
    <div class="filter-bar-actions">
        <button type="submit" class="btn-primary">Filtern</button>
        <a href="/backend/logs" class="btn-ghost">Filter zurücksetzen</a>
    </div>
</form>

<table class="data-table">
    <thead>
        <tr>
            <th>Zeit</th>
            <th>Typ</th>
            <th>Benutzer</th>
            <th>Aktion</th>
            <th>Entität</th>
            <th>Beschreibung</th>
            <th>IP</th>
        </tr>
    </thead>
    <tbody>
    <?php if (!$logs): ?>
        <tr><td colspan="7" class="text-center text-muted">Keine Einträge.</td></tr>
    <?php endif; ?>
    <?php foreach ($logs as $log): ?>
    <tr>
        <td class="text-muted text-nowrap"><?= $h(date('d.m.Y H:i', strtotime($log['created_at']))) ?></td>
        <td><span class="badge"><?= $h($log['actor_type']) ?></span></td>
        <td><?= $h($log['user_name'] ?? $log['survey_customer_name'] ?? ($log['survey_code'] ?? '-')) ?></td>
        <td><code><?= $h($log['action']) ?></code></td>
        <td class="text-muted"><?= ($log['entity_type'] && $log['entity_id']) ? $h($log['entity_type'] . ' #' . $log['entity_id']) : ($log['survey_code'] ? '<code>' . $h($log['survey_code']) . '</code>' : '') ?></td>
        <td><?= $h($log['description'] ?? '') ?></td>
        <td class="text-muted text-small"><?= $h($log['ip_address'] ?? '') ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<div class="table-meta text-muted"><?= (int)$totalCount ?> Einträge</div>
<?php
$baseUrl     = '/backend/logs';
$queryString = '&' . http_build_query(array_filter($filters));
require ROOT . '/app/Views/partials/pagination.php';
?>
