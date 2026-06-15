<?php
$h = fn(string $v) => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
$panelCurrentId = (int)($panelCurrentId ?? 0);
?>
<div class="be-panel-header">
    <div class="be-panel-title">Bereiche</div>
</div>
<div class="be-panel-list">
    <?php foreach ($panelAreas as $a): ?>
    <?php $isActive = $panelCurrentId === (int)$a['id']; ?>
    <a href="/backend/bereiche/<?= (int)$a['id'] ?>/bearbeiten" class="be-panel-item<?= $isActive ? ' is-active' : '' ?>">
        <span class="be-panel-item-name"><?= $h($a['name']) ?></span>
        <?php if (!$a['active']): ?>
        <span class="be-panel-item-meta" style="color:#c0392b">inaktiv</span>
        <?php endif; ?>
    </a>
    <?php endforeach; ?>
</div>
<a href="/backend/bereiche/neu" class="be-panel-add">
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
    Neuer Bereich
</a>
