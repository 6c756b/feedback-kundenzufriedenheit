<?php
$h = fn(string $v) => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
?>
<div class="page-header">
    <h1>Metropolregionen</h1>
    <a href="/backend/metropolregionen/neu" class="btn-primary">+ Neue Metropolregion</a>
</div>

<table class="data-table">
    <thead>
        <tr>
            <th>Sort</th>
            <th>Name</th>
            <th>Aktionen</th>
        </tr>
    </thead>
    <tbody>
    <?php if (!$regions): ?>
        <tr><td colspan="3" class="text-center text-muted">Keine Metropolregionen gefunden.</td></tr>
    <?php endif; ?>
    <?php foreach ($regions as $region): ?>
    <tr>
        <td><?= (int)$region['sort_order'] ?></td>
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
