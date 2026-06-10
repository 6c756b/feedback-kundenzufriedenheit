<?php
$h = fn(string $v) => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
$typeLabels = ['slider' => 'Slider', 'freitext' => 'Freitext', 'slider_freitext' => 'Slider + Freitext'];
?>
<div class="page-header">
    <h1>Fragenverwaltung</h1>
    <a href="/backend/fragen/neu" class="btn-primary">+ Neue Frage</a>
</div>

<?php if ($grouped): ?>
<div class="filter-bar" style="margin-bottom: var(--gap-block)">
    <div class="filter-bar-inputs">
        <label style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--color-navy-60)">Bereich anzeigen</label>
        <select id="area-filter">
            <option value="">Alle Bereiche</option>
            <?php foreach ($grouped as $areaId => $group): ?>
            <option value="area-group-<?= (int)$areaId ?>"><?= $h($group['area_name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
</div>
<?php endif; ?>

<?php foreach ($grouped as $areaId => $group): ?>
<?php
    $activeCount   = count(array_filter($group['questions'], fn($q) => $q['active']));
    $inactiveCount = count($group['questions']) - $activeCount;
?>
<section class="question-group" id="area-group-<?= (int)$areaId ?>" data-collapsed="true">
    <h2 class="group-title">
        <?= $h($group['area_name']) ?>
        <?php if (!$group['area_active']): ?>
        <span class="badge badge-archived" style="font-size:10px;vertical-align:middle;margin-left:8px">Bereich inaktiv</span>
        <?php endif; ?>
        <button type="button" class="group-toggle" aria-expanded="false">
            <?= $activeCount ?> Aktive <?= $activeCount === 1 ? 'Frage' : 'Fragen' ?>
            <?php if ($inactiveCount > 0): ?>
            | <?= $inactiveCount ?> Deaktiviert
            <?php endif; ?>
            <svg class="group-toggle-chevron" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" width="14" height="14"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd"/></svg>
        </button>
    </h2>
    <table class="data-table" hidden>
        <thead>
            <tr>
                <th style="width:60px">Seq.</th>
                <th>Kurzform</th>
                <th>Typ</th>
                <th>Aktiv</th>
                <th>Aktionen</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($group['questions'] as $q): ?>
        <tr class="<?= !$q['active'] ? 'row-inactive' : '' ?>">
            <td class="text-muted text-small" style="text-align:center"><?= (int)$q['sequence'] ?></td>
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
</section>
<?php endforeach; ?>

<script>
document.querySelectorAll('.group-toggle').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var section = btn.closest('.question-group');
        var table   = section.querySelector('table');
        var open    = btn.getAttribute('aria-expanded') === 'true';
        btn.setAttribute('aria-expanded', open ? 'false' : 'true');
        table.hidden = open;
        section.dataset.collapsed = open ? 'true' : 'false';
    });
});
</script>

<?php if (!$grouped): ?>
<p class="text-muted">Noch keine Fragen angelegt.</p>
<?php endif; ?>
