<?php
$h = fn(string $v) => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
?>
<div class="page-header">
    <h1>Bereiche</h1>
    <a href="/backend/bereiche/neu" class="btn-primary">+ Neuer Bereich</a>
</div>

<table class="data-table sortable-table" id="area-table" data-endpoint="/backend/bereiche/sortierung">
    <thead>
        <tr>
            <th style="width:32px"></th>
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
    <tbody id="area-tbody">
    <?php foreach ($areas as $area): ?>
    <?php
        $qActive   = (int)$area['q_active'];
        $qInactive = (int)$area['q_inactive'];
        $sOpen     = (int)$area['s_open'];
        $sEval     = (int)$area['s_evaluation'];
        $sArch     = (int)$area['s_archived'];
        $isActive  = (bool)$area['active'];
    ?>
    <tr data-id="<?= (int)$area['id'] ?>">
        <td class="drag-handle" title="Reihenfolge ändern">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" width="16" height="16" style="color:#aaa;display:block">
                <path d="M7 2a2 2 0 1 1-4 0 2 2 0 0 1 4 0Zm0 6a2 2 0 1 1-4 0 2 2 0 0 1 4 0Zm0 6a2 2 0 1 1-4 0 2 2 0 0 1 4 0Zm6-12a2 2 0 1 1-4 0 2 2 0 0 1 4 0Zm0 6a2 2 0 1 1-4 0 2 2 0 0 1 4 0Zm0 6a2 2 0 1 1-4 0 2 2 0 0 1 4 0Z"/>
            </svg>
        </td>
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

<script>
(function() {
    const tbody = document.getElementById('area-tbody');
    if (!tbody) return;

    const endpoint = document.getElementById('area-table').dataset.endpoint;
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
