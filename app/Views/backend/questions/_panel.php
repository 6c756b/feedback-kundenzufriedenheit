<?php
$h = fn(string $v) => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
$currentUri = strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
$currentAreaId = (int)($_GET['area_id'] ?? $preAreaId ?? 0);
?>
<div class="be-panel-header">
    <div class="be-panel-title">Bereiche</div>
    <a href="/backend/fragen" class="be-panel-header-link<?= !$currentAreaId ? ' is-active' : '' ?>" title="Alle Fragen anzeigen">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:13px;height:13px"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0ZM3.75 12h.007v.008H3.75V12Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm-.375 5.25h.007v.008H3.75v-.008Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z"/></svg>
        Alle
    </a>
</div>
<div class="be-panel-list">
    <?php
    $allQuestions = $grouped ?? [];
    $areas = $areas ?? \App\Models\Area::findAll();

    foreach ($areas as $area):
        $aId = (int)$area['id'];
        $qGroup = $allQuestions[$aId] ?? null;
        $count  = $qGroup ? count($qGroup['questions']) : 0;
        $activeCount = $qGroup ? count(array_filter($qGroup['questions'], fn($q) => $q['active'])) : 0;
        $isActive = $currentAreaId === $aId;
        $url = '/backend/fragen?area_id=' . $aId;
    ?>
    <a href="<?= $h($url) ?>" class="be-panel-item<?= $isActive ? ' is-active' : '' ?>">
        <span class="be-panel-item-name"><?= $h($area['name']) ?></span>
        <?php if (!$area['active']): ?>
        <span class="be-panel-item-meta" style="color:#c0392b;font-size:10px;font-weight:700">Inaktiv</span>
        <?php elseif ($count > 0): ?>
        <span class="be-panel-item-meta"><?= $activeCount ?>/<?= $count ?></span>
        <?php endif; ?>
    </a>
    <?php if ($isActive): ?>
    <div style="padding: 4px 8px 4px 16px;">
        <a href="/backend/fragen/neu?area_id=<?= $aId ?>" class="be-panel-section-add">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
            Neue Frage
        </a>
    </div>
    <?php endif; ?>
    <?php endforeach; ?>
</div>
