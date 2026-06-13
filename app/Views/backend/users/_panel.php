<?php
$h = fn(string $v) => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
$panelCurrentId = (int)($panelCurrentId ?? 0);

$groups = [
    'admin'     => ['label' => 'Administration', 'members' => []],
    'staff'     => ['label' => 'Verwaltung',     'members' => []],
    'reader'    => ['label' => 'Lesezugriff',    'members' => []],
    'sales'     => ['label' => 'Vertrieb',       'members' => []],
    'lead'      => ['label' => 'Projektleitung', 'members' => []],
];

foreach ($panelUsers as $u) {
    $role = $u['role'] ?? 'none';
    if (in_array($role, ['superadmin', 'admin'], true)) {
        $groups['admin']['members'][] = $u;
    }
    if ($role === 'staff') {
        $groups['staff']['members'][] = $u;
    }
    if ($role === 'reader') {
        $groups['reader']['members'][] = $u;
    }
    if (!empty($u['is_sales'])) {
        $groups['sales']['members'][] = $u;
    }
    if (!empty($u['is_projectlead'])) {
        $groups['lead']['members'][] = $u;
    }
}
?>
<div class="be-panel-header">
    <div class="be-panel-title">Benutzer</div>
</div>
<div class="be-panel-list" style="padding:0">
    <?php foreach ($groups as $gKey => $group):
        $members = $group['members'];
        if (empty($members)) continue;
        $isExpanded = false;
        foreach ($members as $m) {
            if ((int)$m['id'] === $panelCurrentId) { $isExpanded = true; break; }
        }
    ?>
    <div class="be-panel-group" data-group="<?= $h($gKey) ?>">
        <button type="button" class="be-panel-group-header" aria-expanded="<?= $isExpanded ? 'true' : 'false' ?>">
            <span class="be-panel-group-label"><?= $h($group['label']) ?></span>
            <span class="be-panel-group-count"><?= count($members) ?></span>
            <svg class="be-panel-group-chevron" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" width="12" height="12">
                <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd"/>
            </svg>
        </button>
        <div class="be-panel-group-body" <?= $isExpanded ? '' : 'hidden' ?>>
            <?php foreach ($members as $u): ?>
            <?php $isActive = $panelCurrentId === (int)$u['id']; ?>
            <a href="/backend/benutzer/<?= (int)$u['id'] ?>/bearbeiten"
               class="be-panel-item<?= $isActive ? ' is-active' : '' ?><?= !$u['active'] ? ' row-inactive' : '' ?>">
                <span class="be-panel-item-name"><?= $h($u['name']) ?></span>
                <?php if (!$u['active']): ?>
                <span class="be-panel-item-meta" style="color:#c0392b;font-size:10px;font-weight:700">Inaktiv</span>
                <?php endif; ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<a href="/backend/benutzer/neu" class="be-panel-add">
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
    Neuer Benutzer
</a>

<script>
document.querySelectorAll('.be-panel-group-header').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var body = btn.nextElementSibling;
        var open = btn.getAttribute('aria-expanded') === 'true';
        btn.setAttribute('aria-expanded', open ? 'false' : 'true');
        body.hidden = open;
    });
});
</script>
