<?php
// Erwartet: $page (aktuell), $totalPages (gesamt), $baseUrl (ohne ?page=)
if (!isset($totalPages) || $totalPages <= 1) return;
?>
<nav class="pagination" aria-label="Seitennavigation">
    <?php if ($page > 1): ?>
        <a href="<?= htmlspecialchars($baseUrl . '?page=' . ($page - 1) . ($queryString ?? ''), ENT_QUOTES, 'UTF-8') ?>" class="page-link">&laquo; Zurück</a>
    <?php endif; ?>

    <?php
    $start = max(1, $page - 2);
    $end   = min($totalPages, $page + 2);
    for ($i = $start; $i <= $end; $i++):
    ?>
        <a href="<?= htmlspecialchars($baseUrl . '?page=' . $i . ($queryString ?? ''), ENT_QUOTES, 'UTF-8') ?>"
           class="page-link <?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
    <?php endfor; ?>

    <?php if ($page < $totalPages): ?>
        <a href="<?= htmlspecialchars($baseUrl . '?page=' . ($page + 1) . ($queryString ?? ''), ENT_QUOTES, 'UTF-8') ?>" class="page-link">Weiter &raquo;</a>
    <?php endif; ?>
</nav>
