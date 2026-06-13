<?php
$h = fn(string $v) => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
?>
<div class="page-header">
    <h1>Metropolregionen</h1>
    <a href="/backend/metropolregionen/neu" class="btn-primary">+ Neue Metropolregion</a>
</div>

<table class="data-table sortable-table" id="region-table" data-endpoint="/backend/metropolregionen/sortierung">
    <thead>
        <tr>
            <th style="width:32px"></th>
            <th>Name</th>
            <th>Aktionen</th>
        </tr>
    </thead>
    <tbody id="region-tbody">
    <?php if (!$regions): ?>
        <tr><td colspan="3" class="text-center text-muted">Keine Metropolregionen gefunden.</td></tr>
    <?php endif; ?>
    <?php foreach ($regions as $region): ?>
    <tr data-id="<?= (int)$region['id'] ?>">
        <td class="drag-handle" title="Reihenfolge ändern">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" width="16" height="16" style="color:#aaa;display:block">
                <path d="M7 2a2 2 0 1 1-4 0 2 2 0 0 1 4 0Zm0 6a2 2 0 1 1-4 0 2 2 0 0 1 4 0Zm0 6a2 2 0 1 1-4 0 2 2 0 0 1 4 0Zm6-12a2 2 0 1 1-4 0 2 2 0 0 1 4 0Zm0 6a2 2 0 1 1-4 0 2 2 0 0 1 4 0Zm0 6a2 2 0 1 1-4 0 2 2 0 0 1 4 0Z"/>
            </svg>
        </td>
        <td><?= $h($region['name']) ?></td>
        <td><div class="actions">
            <a href="/backend/metropolregionen/<?= (int)$region['id'] ?>/bearbeiten" class="btn-icon" title="Bearbeiten"><?= icon('pencil') ?></a>
            <form method="post" action="/backend/metropolregionen/<?= (int)$region['id'] ?>/loeschen"
                  style="display:inline"
                  onsubmit="return confirm('Metropolregion «<?= $h($region['name']) ?>» wirklich löschen?')">
                <?= \App\Core\Csrf::field() ?>
                <button type="submit" class="btn-icon btn-danger" title="Löschen"><?= icon('trash') ?></button>
            </form>
        </div></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<script>
(function() {
    const tbody = document.getElementById('region-tbody');
    if (!tbody) return;

    const endpoint = document.getElementById('region-table').dataset.endpoint;
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
