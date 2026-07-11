<?php
$h = fn(string $v) => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
$typeLabels = ['slider' => 'Slider', 'freitext' => 'Freitext', 'slider_freitext' => 'Slider + Freitext'];

$filterAreaId = (int)($_GET['area_id'] ?? 0);
$displayGrouped = $filterAreaId ? array_filter($grouped, fn($g, $k) => $k == $filterAreaId, ARRAY_FILTER_USE_BOTH) : $grouped;

$selectedArea = null;
foreach ($areas as $a) {
    if ($a['id'] == $filterAreaId) { $selectedArea = $a; break; }
}
?>

<?php if ($filterAreaId && $selectedArea): ?>
<?php
    $activeCount   = 0;
    $inactiveCount = 0;
    if (isset($displayGrouped[$filterAreaId])) {
        $activeCount   = count(array_filter($displayGrouped[$filterAreaId]['questions'], fn($q) => $q['active']));
        $inactiveCount = count($displayGrouped[$filterAreaId]['questions']) - $activeCount;
    }
?>
<div class="page-header">
    <div>
        <h1>Fragen: <?= $h($selectedArea['name']) ?></h1>
        <p class="text-muted text-small" style="margin-top:2px"><?= $activeCount ?> aktiv<?= $inactiveCount > 0 ? ', ' . $inactiveCount . ' inaktiv' : '' ?></p>
    </div>
    <a href="/backend/fragen/neu?area_id=<?= $filterAreaId ?>" class="btn-primary">+ Neue Frage</a>
</div>

<?php if (isset($displayGrouped[$filterAreaId])): ?>
<table class="data-table sortable-table" id="question-table" data-endpoint="/backend/fragen/sequenz">
    <thead>
        <tr>
            <th style="width:32px"></th>
            <th>Kurzform</th>
            <th>Typ</th>
            <th>Aktiv</th>
            <th>Aktionen</th>
        </tr>
    </thead>
    <tbody id="question-tbody">
    <?php foreach ($displayGrouped[$filterAreaId]['questions'] as $q): ?>
    <tr class="<?= !$q['active'] ? 'row-inactive' : '' ?>" data-id="<?= (int)$q['id'] ?>">
        <td class="drag-handle" title="Reihenfolge ändern">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" width="16" height="16" style="color:#aaa;display:block">
                <path d="M7 2a2 2 0 1 1-4 0 2 2 0 0 1 4 0Zm0 6a2 2 0 1 1-4 0 2 2 0 0 1 4 0Zm0 6a2 2 0 1 1-4 0 2 2 0 0 1 4 0Zm6-12a2 2 0 1 1-4 0 2 2 0 0 1 4 0Zm0 6a2 2 0 1 1-4 0 2 2 0 0 1 4 0Zm0 6a2 2 0 1 1-4 0 2 2 0 0 1 4 0Z"/>
            </svg>
        </td>
        <td><?= $h($q['label_short']) ?></td>
        <td><?= $h($typeLabels[$q['type']] ?? $q['type']) ?></td>
        <td><?= $q['active'] ? '<span class="badge badge-open">Aktiv</span>' : '<span class="badge badge-archived">Inaktiv</span>' ?></td>
        <td><div class="actions">
            <a href="/backend/fragen/<?= (int)$q['id'] ?>/bearbeiten" class="btn-icon" title="Bearbeiten"><?= icon('pencil') ?></a>
            <form method="post" action="/backend/fragen/<?= (int)$q['id'] ?>/loeschen"
                  onsubmit="return confirm('Frage wirklich löschen?')" style="display:inline">
                <?= \App\Core\Csrf::field() ?>
                <button type="submit" class="btn-icon btn-danger" title="Löschen"><?= icon('trash') ?></button>
            </form>
        </div></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php else: ?>
<div style="background:#fff;border:1px solid var(--color-gray-40);padding:40px;text-align:center;color:var(--color-navy-60)">
    <p style="margin-bottom:12px">Noch keine Fragen in diesem Bereich.</p>
    <a href="/backend/fragen/neu?area_id=<?= $filterAreaId ?>" class="btn-primary">Erste Frage anlegen</a>
</div>
<?php endif; ?>

<?php else: ?>

<div class="page-header">
    <h1>Fragenverwaltung</h1>
    <a href="/backend/fragen/neu" class="btn-primary">+ Neue Frage</a>
</div>

<?php if ($grouped): ?>
<div class="q-area-grid">
    <?php foreach ($grouped as $areaId => $group):
        $aTotal   = count($group['questions']);
        $aActive  = count(array_filter($group['questions'], fn($q) => $q['active']));
        $pct      = $aTotal > 0 ? round($aActive / $aTotal * 100) : 0;
        $sLaufend = $group['s_open'] + $group['s_started'];
        $sAbschl  = $group['s_completed'];
        $sAusw    = $group['s_evaluation'];
        $sArch    = $group['s_archived'];
        $sTotal   = $sLaufend + $sAbschl + $sAusw + $sArch;
        $avgScore = $group['avg_score'];
    ?>
    <a href="/backend/fragen?area_id=<?= (int)$areaId ?>" class="q-area-card<?= !$group['area_active'] ? ' q-area-card--inactive' : '' ?>">
        <div class="q-area-card-header">
            <span class="q-area-card-name"><?= $h($group['area_name']) ?></span>
            <?php if (!$group['area_active']): ?>
            <span class="badge badge-cancelled" style="font-size:10px">Inaktiv</span>
            <?php endif; ?>
        </div>
        <div class="q-area-card-stats">
            <span class="q-area-stat-main"><?= $aActive ?></span>
            <span class="q-area-stat-label">aktive Fragen</span>
            <?php if ($aTotal - $aActive > 0): ?>
            <span class="q-area-stat-inactive">+ <?= $aTotal - $aActive ?> inaktiv</span>
            <?php endif; ?>
        </div>
        <div class="q-area-bar">
            <div class="q-area-bar-fill" style="width:<?= $pct ?>%"></div>
        </div>
        <?php if ($avgScore !== null): ?>
        <div class="q-area-avg">
            <div class="q-area-avg-top">
                <span class="q-area-avg-score"><?= number_format($avgScore, 1, ',', '') ?></span>
                <span class="q-area-avg-label">Ø Bewertung (1–6)</span>
            </div>
            <div class="q-area-avg-bar">
                <div class="q-area-avg-bar-fill" style="width:<?= round($avgScore / 6 * 100) ?>%"></div>
            </div>
        </div>
        <?php endif; ?>
        <?php if ($sTotal > 0): ?>
        <div class="stat-pill q-area-surveys">
            <?php if ($sLaufend > 0): ?><span class="sp-blue"><?= $sLaufend ?> laufend</span><?php endif; ?>
            <?php if ($sAbschl  > 0): ?><span class="sp-amber"><?= $sAbschl ?> abgeschl.</span><?php endif; ?>
            <?php if ($sAusw    > 0): ?><span class="sp-amber"><?= $sAusw ?> Ausw.</span><?php endif; ?>
            <?php if ($sArch    > 0): ?><span class="sp-slate"><?= $sArch ?> Archiv</span><?php endif; ?>
        </div>
        <?php endif; ?>
        <div class="q-area-card-footer">
            <?= $aTotal ?> Fragen gesamt &rsaquo;
        </div>
    </a>
    <?php endforeach; ?>
</div>
<?php else: ?>
<p class="text-muted">Noch keine Fragen angelegt.</p>
<?php endif; ?>

<?php endif; ?>

<script>
(function() {
    const tbody = document.getElementById('question-tbody');
    if (!tbody) return;

    const endpoint = document.getElementById('question-table').dataset.endpoint;
    const csrf     = document.querySelector('meta[name="csrf-token"]')?.content
                  || document.querySelector('input[name="csrf_token"]')?.value || '';
    let dragSrc    = null;

    function saveOrder() {
        const ids = Array.from(tbody.querySelectorAll('tr[data-id]')).map(r => r.dataset.id);
        const body = 'csrf_token=' + encodeURIComponent(csrf) + ids.map(id => '&ids[]=' + id).join('');
        fetch(endpoint, {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest'},
            body
        });
    }

    tbody.querySelectorAll('tr[data-id]').forEach(row => {
        row.setAttribute('draggable', 'true');

        row.querySelector('.drag-handle').style.cursor = 'grab';

        row.addEventListener('dragstart', e => {
            dragSrc = row;
            e.dataTransfer.effectAllowed = 'move';
            setTimeout(() => row.classList.add('drag-dragging'), 0);
        });

        row.addEventListener('dragend', () => {
            row.classList.remove('drag-dragging');
            tbody.querySelectorAll('tr').forEach(r => r.classList.remove('drag-over-top', 'drag-over-bot'));
            saveOrder();
            dragSrc = null;
        });

        row.addEventListener('dragover', e => {
            e.preventDefault();
            if (!dragSrc || dragSrc === row) return;
            const rect = row.getBoundingClientRect();
            const mid  = rect.top + rect.height / 2;
            tbody.querySelectorAll('tr').forEach(r => r.classList.remove('drag-over-top', 'drag-over-bot'));
            if (e.clientY < mid) {
                row.classList.add('drag-over-top');
                tbody.insertBefore(dragSrc, row);
            } else {
                row.classList.add('drag-over-bot');
                tbody.insertBefore(dragSrc, row.nextSibling);
            }
        });

        row.addEventListener('drop', e => e.preventDefault());
    });
})();
</script>
