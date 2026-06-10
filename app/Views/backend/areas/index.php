<?php
$h = fn(string $v) => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
?>
<div class="page-header">
    <h1>Bereiche</h1>
    <a href="/backend/bereiche/neu" class="btn-primary">+ Neuer Bereich</a>
</div>

<table class="data-table">
    <thead>
        <tr>
            <th>Sort</th>
            <th>Name</th>
            <th>Aktiv</th>
            <th>
                Fragen
                <span class="th-hint">
                    <span style="color:#3d6800">&#9646;</span> aktiv &nbsp;
                    <span style="color:#5a5958">&#9646;</span> inaktiv
                </span>
            </th>
            <th>
                Befragungen
                <span class="th-hint">
                    <span style="color:#1a5fa8">&#9646;</span> offen &nbsp;
                    <span style="color:#a05000">&#9646;</span> Auswertung &nbsp;
                    <span style="color:#5a5c6e">&#9646;</span> Archiv
                </span>
            </th>
            <th>Aktionen</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($areas as $area): ?>
    <?php
        $qActive   = (int)$area['q_active'];
        $qInactive = (int)$area['q_inactive'];
        $sOpen     = (int)$area['s_open'];
        $sEval     = (int)$area['s_evaluation'];
        $sArch     = (int)$area['s_archived'];
        $isActive  = (bool)$area['active'];
    ?>
    <tr>
        <td><?= (int)$area['sort_order'] ?></td>
        <td><?= $h($area['name']) ?></td>
        <td>
            <?php if ($isActive): ?>
            <span class="badge badge-open">Aktiv</span>
            <?php else: ?>
            <span class="badge badge-cancelled">Inaktiv</span>
            <?php endif; ?>
        </td>

        <td>
            <?php if ($qActive === 0 && $qInactive === 0): ?>
            <span style="color:var(--color-gray)">–</span>
            <?php else: ?>
            <div class="stat-pill">
                <?php if ($qActive > 0): ?>
                <span class="sp-green"><?= $qActive ?> aktiv</span>
                <?php endif; ?>
                <?php if ($qInactive > 0): ?>
                <span class="sp-gray"><?= $qInactive ?> inaktiv</span>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </td>

        <td>
            <?php if ($sOpen === 0 && $sEval === 0 && $sArch === 0): ?>
            <span style="color:var(--color-gray)">–</span>
            <?php else: ?>
            <div class="stat-pill">
                <?php if ($sOpen > 0): ?>
                <span class="sp-blue"><?= $sOpen ?> offen</span>
                <?php endif; ?>
                <?php if ($sEval > 0): ?>
                <span class="sp-amber"><?= $sEval ?> Ausw.</span>
                <?php endif; ?>
                <?php if ($sArch > 0): ?>
                <span class="sp-slate"><?= $sArch ?> Archiv</span>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </td>

        <td><div class="actions">
            <a href="/backend/bereiche/<?= (int)$area['id'] ?>/bearbeiten" class="btn-ghost btn-sm">Bearbeiten</a>
        </div></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
