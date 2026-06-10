<?php
$h = fn(string $v) => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
$roleLabels = [
    'none'       => 'Keine Berechtigung',
    'reader'     => 'Leserechte',
    'staff'      => 'Mitarbeiter',
    'admin'      => 'Admin',
    'superadmin' => 'Super-Admin',
];

$sortBy  = $_GET['sort_by']  ?? 'name';
$sortDir = strtoupper($_GET['sort_dir'] ?? 'ASC');
$baseUrl = '/backend/benutzer';

$sortLink = function(string $field, string $label) use ($sortBy, $sortDir, $baseUrl, $h): string {
    $newDir = ($sortBy === $field && $sortDir === 'ASC') ? 'DESC' : 'ASC';
    $arrow  = $sortBy === $field ? ($sortDir === 'ASC' ? ' ↑' : ' ↓') : '';
    $cls    = 'sort-link' . ($sortBy === $field ? ' sort-active' : '');
    $url    = $baseUrl . '?sort_by=' . urlencode($field) . '&sort_dir=' . $newDir;
    return '<a href="' . $h($url) . '" class="' . $cls . '">' . $h($label) . $arrow . '</a>';
};
?>
<div class="page-header">
    <h1>Benutzerverwaltung</h1>
    <a href="/backend/benutzer/neu" class="btn-primary">+ Neuer Benutzer</a>
</div>

<div class="filter-bar" style="margin-bottom:12px">
    <div class="filter-row">
        <div class="filter-search-wrap">
            <input type="search" id="user-search" class="filter-text"
                   placeholder="Name oder E-Mail suchen…" autocomplete="off">
        </div>
        <div style="display:flex;gap:.4rem;flex-wrap:wrap;align-items:center;">
            <label class="note-range-chip"><input type="checkbox" class="user-tag-filter" value="vertrieb"> Vertrieb</label>
            <label class="note-range-chip"><input type="checkbox" class="user-tag-filter" value="projektleitung"> Projektleitung</label>
            <label class="note-range-chip"><input type="checkbox" class="user-tag-filter" value="mitarbeiter"> Mitarbeiter</label>
            <label class="note-range-chip"><input type="checkbox" class="user-tag-filter" value="admin"> Admin</label>
        </div>
    </div>
</div>

<table class="data-table" id="user-table">
    <thead>
        <tr>
            <th><?= $sortLink('name', 'Name') ?></th>
            <th><?= $sortLink('email', 'E-Mail') ?></th>
            <th><?= $sortLink('role', 'Rolle') ?></th>
            <th><?= $sortLink('is_sales', 'Vertrieb') ?></th>
            <th><?= $sortLink('is_projectlead', 'Projektleitung') ?></th>
            <th><?= $sortLink('active', 'Aktiv') ?></th>
            <th>Aktionen</th>
        </tr>
    </thead>
    <tbody>
    <?php if (!$users): ?>
        <tr><td colspan="7" class="text-center text-muted">Keine Benutzer gefunden.</td></tr>
    <?php endif; ?>
    <?php foreach ($users as $u): ?>
    <?php
        $tags = [];
        if ($u['is_sales'])      $tags[] = 'vertrieb';
        if ($u['is_projectlead']) $tags[] = 'projektleitung';
        if ($u['role'] === 'staff') $tags[] = 'mitarbeiter';
        if (in_array($u['role'], ['admin', 'superadmin'], true)) $tags[] = 'admin';
    ?>
    <tr class="<?= !$u['active'] ? 'row-inactive' : '' ?>"
        data-search="<?= $h(strtolower($u['name'] . ' ' . $u['email'])) ?>"
        data-tags="<?= $h(implode(' ', $tags)) ?>">
        <td><?= $h($u['name']) ?></td>
        <td><?= $h($u['email']) ?></td>
        <td><?= $h($roleLabels[$u['role']] ?? $u['role']) ?></td>
        <td><?= $u['is_sales'] ? '<span class="badge badge-open">Ja</span>' : '<span class="text-muted" style="font-size:12px">–</span>' ?></td>
        <td><?= $u['is_projectlead'] ? '<span class="badge badge-open">Ja</span>' : '<span class="text-muted" style="font-size:12px">–</span>' ?></td>
        <td>
            <?php if ($u['active']): ?>
                <span class="badge badge-open">Aktiv</span>
            <?php else: ?>
                <span class="badge badge-archived">Inaktiv</span>
            <?php endif; ?>
        </td>
        <td><div class="actions">
            <?php if (in_array($u['role'], $allowedRoles, true)): ?>
            <a href="/backend/benutzer/<?= (int)$u['id'] ?>/bearbeiten" class="btn-icon" title="Bearbeiten"><?= icon('pencil') ?></a>
            <?php endif; ?>
        </div></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<script>
(function() {
    const input = document.getElementById('user-search');
    const chips = document.querySelectorAll('.user-tag-filter');
    const rows  = document.querySelectorAll('#user-table tbody tr[data-search]');

    function applyFilters() {
        const q = input ? input.value.toLowerCase().trim() : '';
        const active = Array.from(chips).filter(c => c.checked).map(c => c.value);
        rows.forEach(row => {
            const matchSearch = q === '' || row.dataset.search.includes(q);
            const rowTags = (row.dataset.tags || '').split(' ').filter(Boolean);
            const matchTag = active.length === 0 || active.some(t => rowTags.includes(t));
            row.hidden = !matchSearch || !matchTag;
        });
    }

    if (input) input.addEventListener('input', applyFilters);
    chips.forEach(c => c.addEventListener('change', applyFilters));
})();
</script>
