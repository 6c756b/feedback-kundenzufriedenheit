<?php
$tage   = ['Sunday'=>'Sonntag','Monday'=>'Montag','Tuesday'=>'Dienstag','Wednesday'=>'Mittwoch','Thursday'=>'Donnerstag','Friday'=>'Freitag','Saturday'=>'Samstag'];
$monate = ['January'=>'Januar','February'=>'Februar','March'=>'März','April'=>'April','May'=>'Mai','June'=>'Juni','July'=>'Juli','August'=>'August','September'=>'September','October'=>'Oktober','November'=>'November','December'=>'Dezember'];
$datum  = strtr(date('l, j. F Y'), array_merge($tage, $monate));
$h      = fn(string $v) => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');

// Aktive Filter-Labels für Chart-Titel
$activeAreaName = '';
if ($filterAreaId) {
    foreach ($areas as $area) {
        if ($area['id'] == $filterAreaId) { $activeAreaName = $area['name']; break; }
    }
}
$activeCreatedByName = '';
if ($filterCreatedBy) {
    foreach ($users as $u) {
        if ($u['id'] == $filterCreatedBy) { $activeCreatedByName = $u['name']; break; }
    }
}
$activeSalesName = '';
if ($filterSalesUser) {
    foreach ($salesUsers as $su) {
        if ($su['id'] == $filterSalesUser) { $activeSalesName = $su['name']; break; }
    }
}
$activeProjectLeadName = '';
if ($filterProjectLead) {
    foreach ($projectLeadUsers as $pl) {
        if ($pl['id'] == $filterProjectLead) { $activeProjectLeadName = $pl['name']; break; }
    }
}
$activeMetropolregionName = '';
if ($filterMetropolregion) {
    foreach ($metropolregionen as $mr) {
        if ($mr['id'] == $filterMetropolregion) { $activeMetropolregionName = $mr['name']; break; }
    }
}

// Query-String für Kachel-Links (alle aktiven Filter weitergeben)
$filterParts = [];
if ($filterAreaId)         $filterParts[] = 'area_id='           . (int)$filterAreaId;
if ($filterCreatedBy)      $filterParts[] = 'created_by='        . (int)$filterCreatedBy;
if ($filterSalesUser)      $filterParts[] = 'sales_user_id='     . (int)$filterSalesUser;
if ($filterProjectLead)    $filterParts[] = 'project_lead_id='   . (int)$filterProjectLead;
if ($filterMetropolregion) $filterParts[] = 'metropolregion_id=' . (int)$filterMetropolregion;
$filterQs    = $filterParts ? '?' . implode('&', $filterParts) : '';
$filterQsRaw = $filterParts ? implode('&', $filterParts) : '';

$hasFilters = $filterAreaId || $filterCreatedBy || $filterSalesUser || $filterProjectLead || $filterMetropolregion;
?>

<div class="dash-header">
    <div>
        <h1>Dashboard</h1>
        <p class="dash-date"><?= $datum ?></p>
    </div>
    <?php if (\App\Core\Auth::hasRole('staff')): ?>
    <div class="header-actions">
        <a href="/backend/befragungen/neu" class="btn-primary">+ Neue Befragung</a>
    </div>
    <?php endif; ?>
</div>

<!-- Dashboard-Filter -->
<form method="get" action="/backend" class="filter-bar" data-dash-filter>
    <div class="filter-row">
        <select name="area_id" class="filter-select" onchange="this.form.submit()">
            <option value="">Alle Bereiche</option>
            <?php foreach ($areas as $area): ?>
            <option value="<?= (int)$area['id'] ?>" <?= $filterAreaId == $area['id'] ? 'selected' : '' ?>>
                <?= $h($area['name']) ?>
            </option>
            <?php endforeach; ?>
        </select>

        <select name="created_by" class="filter-select" onchange="this.form.submit()">
            <option value="">Alle Befrager</option>
            <?php foreach ($users as $u): ?>
            <option value="<?= (int)$u['id'] ?>" <?= $filterCreatedBy == $u['id'] ? 'selected' : '' ?>>
                <?= $h($u['name']) ?>
            </option>
            <?php endforeach; ?>
        </select>

        <?php if (!empty($salesUsers)): ?>
        <select name="sales_user_id" class="filter-select" onchange="this.form.submit()">
            <option value="">Alle Vertriebler</option>
            <?php foreach ($salesUsers as $su): ?>
            <option value="<?= (int)$su['id'] ?>" <?= $filterSalesUser == $su['id'] ? 'selected' : '' ?>>
                <?= $h($su['name']) ?>
            </option>
            <?php endforeach; ?>
        </select>
        <?php endif; ?>

        <?php if (!empty($projectLeadUsers)): ?>
        <select name="project_lead_id" class="filter-select" onchange="this.form.submit()">
            <option value="">Alle Projektleitungen</option>
            <?php foreach ($projectLeadUsers as $pl): ?>
            <option value="<?= (int)$pl['id'] ?>" <?= $filterProjectLead == $pl['id'] ? 'selected' : '' ?>>
                <?= $h($pl['name']) ?>
            </option>
            <?php endforeach; ?>
        </select>
        <?php endif; ?>

        <select name="metropolregion_id" class="filter-select" onchange="this.form.submit()">
            <option value="">Alle Metropolregionen</option>
            <?php foreach ($metropolregionen as $mr): ?>
            <option value="<?= (int)$mr['id'] ?>" <?= $filterMetropolregion == $mr['id'] ? 'selected' : '' ?>>
                <?= $h($mr['name']) ?>
            </option>
            <?php endforeach; ?>
        </select>

        <?php if ($hasFilters): ?>
        <a href="/backend?reset=1" class="filter-reset">× zurücksetzen</a>
        <?php endif; ?>
    </div>
</form>

<div class="stats-grid stats-grid-3">

    <a href="/backend/befragungen<?= $filterQs ?>" class="stat-card stat-card-link">
        <div class="stat-eyebrow">Laufend</div>
        <div class="stat-value"><?= (int)$activeSurveys ?></div>
        <div class="stat-label">Laufende Befragungen</div>
        <div class="stat-sub"><?= (int)$startedSurveys ?> davon in Bearbeitung</div>
        <div class="stat-card-inner-bg">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
        </div>
    </a>

    <a href="/backend/auswertung<?= $filterQs ?>" class="stat-card stat-card-link <?= $unreadEvals > 0 ? 'stat-highlight' : '' ?>">
        <div class="stat-eyebrow">Auswertung</div>
        <div class="stat-value"><?= (int)$completedTotal ?></div>
        <div class="stat-label">Abgeschlossene Befragungen</div>
        <div class="stat-sub <?= $unreadEvals > 0 ? 'stat-sub-alert' : '' ?>"><?= (int)$unreadEvals ?> davon ungelesen</div>
        <div class="stat-card-inner-bg">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
            </svg>
        </div>
    </a>

    <a href="/backend/archiv<?= $filterQs ?>" class="stat-card stat-card-link">
        <div class="stat-eyebrow">Archiv</div>
        <div class="stat-value"><?= (int)$archivedTotal ?></div>
        <div class="stat-label">Archivierte Auswertungen</div>
        <div class="stat-sub"><?= (int)$cancelledCount ?> davon unbeantwortet</div>
        <div class="stat-card-inner-bg">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
            </svg>
        </div>
    </a>

</div>

<div class="charts-grid">
    <div class="chart-card chart-card-full">
        <?php
            $chartLabels = array_filter([$activeAreaName, $activeCreatedByName, $activeSalesName, $activeProjectLeadName, $activeMetropolregionName]);
            $chartSuffix = $chartLabels ? ' – ' . implode(', ', array_map($h, $chartLabels)) : '';
        ?>
        <h2>Bewertungsverlauf (12 Monate)<?= $chartSuffix ?></h2>
        <div class="chart-container" style="height:210px">
            <canvas id="chart-trend"></canvas>
        </div>
    </div>
    <div class="chart-card">
        <h2>Notenverteilung (12 Monate)<?= $chartSuffix ?></h2>
        <div class="chart-container" style="height:210px">
            <canvas id="chart-dist"></canvas>
        </div>
    </div>
    <div class="chart-card">
        <h2>Status (12 Monate)</h2>
        <div class="chart-container" style="height:210px">
            <canvas id="chart-status"></canvas>
        </div>
    </div>
</div>

<?php
// Hilfsfunktion: Pagination-Links für Log-Spalten
function logPageNav(int $cur, int $total, string $param, string $otherQs): string {
    if ($total <= 1) return '';
    $h   = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
    $url = fn($p)  => '/backend?' . $param . '=' . $p . ($otherQs ? '&' . $otherQs : '');
    $out = '<nav class="pagination" style="margin-top:12px;margin-bottom:12px">';
    if ($cur > 1)    $out .= '<a href="' . $h($url($cur - 1)) . '" class="page-link">&laquo;</a>';
    $s = max(1, $cur - 2); $e = min($total, $cur + 2);
    for ($i = $s; $i <= $e; $i++)
        $out .= '<a href="' . $h($url($i)) . '" class="page-link ' . ($i === $cur ? 'active' : '') . '">' . $i . '</a>';
    if ($cur < $total) $out .= '<a href="' . $h($url($cur + 1)) . '" class="page-link">&raquo;</a>';
    return $out . '</nav>';
}
$filterRaw = $filterQsRaw;
?>

<section class="dashboard-section">
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;align-items:start">

        <!-- Aktivitäten (Frontend) -->
        <div class="activity-card">
            <div class="activity-card-header">
                <h2>Letzte Aktivitäten</h2>
                <span class="activity-card-count"><?= $fTotal ?> (12 Mo.)</span>
            </div>
            <?php if ($frontendLogs): ?>
                <?php foreach ($frontendLogs as $log):
                    $isDone   = $log['action'] === 'frontend.completed';
                    $customer = $log['survey_customer_name'] ?? '–';
                    $project  = $log['survey_project_name']  ?? '';
                ?>
                <div class="activity-row">
                    <div class="activity-ts"><?= htmlspecialchars(date('d.m.Y H:i', strtotime($log['created_at'])), ENT_QUOTES, 'UTF-8') ?></div>
                    <div class="activity-icon <?= $isDone ? 'activity-icon-done' : 'activity-icon-started' ?>" title="<?= $isDone ? 'Beendet' : 'Gestartet' ?>">
                        <?php if ($isDone): ?>
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                        <?php else: ?>
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                        <?php endif; ?>
                    </div>
                    <div class="activity-who">
                        <?= htmlspecialchars($customer, ENT_QUOTES, 'UTF-8') ?>
                        <?php if ($project !== '' && $project !== $customer): ?>
                        <span class="activity-project"><?= htmlspecialchars($project, ENT_QUOTES, 'UTF-8') ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
                <div class="activity-legend">
                    <span class="activity-icon activity-icon-started"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polygon points="5 3 19 12 5 21 5 3"/></svg></span> Gestartet
                    &ensp;
                    <span class="activity-icon activity-icon-done"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg></span> Beendet
                </div>
                <?= logPageNav($fPage, $fTotalPages, 'fp', ($filterRaw ? $filterRaw . '&' : '') . ($mPage > 1 ? 'mp=' . $mPage : '')) ?>
            <?php else: ?>
                <p class="text-muted" style="padding:12px 0">Keine Einträge.</p>
            <?php endif; ?>
        </div>

        <!-- E-Mail-Versand (Backend) -->
        <div class="activity-card">
            <div class="activity-card-header">
                <h2>E-Mail-Versand</h2>
                <span class="activity-card-count"><?= $mTotal ?> (12 Mo.)</span>
            </div>
            <?php if ($mailingLogs): ?>
                <?php foreach ($mailingLogs as $log):
                    $isSystem = $log['action'] === 'survey.email_sent';
                    $iconClass = $isSystem ? 'activity-icon-system' : 'activity-icon-outlook';
                    $iconTitle = $isSystem ? 'System' : 'Outlook';
                    $customer  = $log['survey_customer_name'] ?? '–';
                    $sender    = $log['user_name'] ?? '';
                ?>
                <div class="activity-row">
                    <div class="activity-ts"><?= htmlspecialchars(date('d.m.Y H:i', strtotime($log['created_at'])), ENT_QUOTES, 'UTF-8') ?></div>
                    <div class="activity-icon <?= $iconClass ?>" title="<?= $iconTitle ?>">
                        <?php if ($isSystem): ?>
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                        <?php else: ?>
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                        <?php endif; ?>
                    </div>
                    <div class="activity-who">
                        <?= htmlspecialchars($customer, ENT_QUOTES, 'UTF-8') ?>
                        <?php if ($sender): ?>
                        <span class="activity-project"><?= htmlspecialchars($sender, ENT_QUOTES, 'UTF-8') ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
                <div class="activity-legend">
                    <span class="activity-icon activity-icon-system"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg></span> System
                    &ensp;
                    <span class="activity-icon activity-icon-outlook"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg></span> Outlook
                </div>
                <?= logPageNav($mPage, $mTotalPages, 'mp', ($filterRaw ? $filterRaw . '&' : '') . ($fPage > 1 ? 'fp=' . $fPage : '')) ?>
            <?php else: ?>
                <p class="text-muted" style="padding:12px 0">Keine Einträge.</p>
            <?php endif; ?>
        </div>

    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const d = window.__chartData;
    if (!d || typeof Chart === 'undefined') return;

    const primary  = '#86bc24';
    const defaults = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
    };

    const trendCtx = document.getElementById('chart-trend');
    if (trendCtx && d.monthly.labels.length) {
        new Chart(trendCtx, {
            type: 'line',
            data: {
                labels: d.monthly.labels,
                datasets: [{
                    data: d.monthly.data,
                    borderColor: primary,
                    backgroundColor: 'rgba(134,188,36,0.07)',
                    pointBackgroundColor: primary,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    tension: 0.35,
                    fill: true,
                    borderWidth: 2,
                }]
            },
            options: {
                ...defaults,
                scales: {
                    y: {
                        min: 1, max: 6,
                        ticks: { stepSize: 1, color: '#737587', font: { size: 11 } },
                        grid: { color: 'rgba(0,0,0,0.05)' },
                        border: { display: false }
                    },
                    x: {
                        ticks: { color: '#737587', font: { size: 11 } },
                        grid: { display: false },
                        border: { display: false }
                    }
                },
                plugins: {
                    ...defaults.plugins,
                    tooltip: { callbacks: { label: ctx => 'Ø ' + ctx.parsed.y.toFixed(2).replace('.', ',') } }
                }
            }
        });
    } else if (trendCtx) {
        trendCtx.closest('.chart-card').querySelector('h2').insertAdjacentHTML('afterend', '<p style="color:#737587;font-size:13px;padding:12px 0">Noch keine Daten vorhanden.</p>');
        trendCtx.remove();
    }

    const distCtx = document.getElementById('chart-dist');
    if (distCtx && d.noteDist.labels.length) {
        new Chart(distCtx, {
            type: 'bar',
            data: {
                labels: d.noteDist.labels,
                datasets: [{
                    data: d.noteDist.data,
                    backgroundColor: ['#86bc24','#b8d424','#e6e020','#f0a020','#c0392b'].slice(0, d.noteDist.labels.length),
                    borderRadius: 2,
                    borderSkipped: false,
                }]
            },
            options: {
                ...defaults,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1, precision: 0, color: '#737587', font: { size: 11 } },
                        grid: { color: 'rgba(0,0,0,0.05)' },
                        border: { display: false }
                    },
                    x: {
                        ticks: { color: '#737587', font: { size: 11 } },
                        grid: { display: false },
                        border: { display: false }
                    }
                },
                plugins: {
                    ...defaults.plugins,
                    tooltip: { callbacks: { label: ctx => ctx.parsed.y + ' Befragungen' } }
                },
                onClick: (event, elements) => {
                    if (!elements.length) return;
                    const label = d.noteDist.labels[elements[0].index];
                    let url = '/backend/auswertung?note_range[]=' + encodeURIComponent(label);
                    if (d.filterQsRaw) url += '&' + d.filterQsRaw;
                    window.location.href = url;
                },
                onHover: (event, elements) => {
                    event.native.target.style.cursor = elements.length ? 'pointer' : 'default';
                },
            }
        });
    } else if (distCtx) {
        distCtx.closest('.chart-card').querySelector('h2').insertAdjacentHTML('afterend', '<p style="color:#737587;font-size:13px;padding:12px 0">Noch keine Daten vorhanden.</p>');
        distCtx.remove();
    }

    const statusCtx = document.getElementById('chart-status');
    if (statusCtx && d.status.labels.length) {
        const statusColors = { 'Offen': '#86bc24', 'Gestartet': '#4a90d9', 'Abgeschlossen': '#1a1c28', 'Archiviert': '#adacab', 'Abgebrochen': '#fde8e8' };
        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: d.status.labels,
                datasets: [{
                    data: d.status.data,
                    backgroundColor: d.status.labels.map(l => statusColors[l] || '#ccc'),
                    borderWidth: 3,
                    borderColor: '#fff',
                }]
            },
            options: {
                ...defaults,
                plugins: {
                    legend: {
                        display: true,
                        position: 'bottom',
                        labels: { font: { size: 11 }, color: '#484953', padding: 14, boxWidth: 12, boxHeight: 12 }
                    }
                },
                cutout: '68%',
            }
        });
    } else if (statusCtx) {
        statusCtx.closest('.chart-card').querySelector('h2').insertAdjacentHTML('afterend', '<p style="color:#737587;font-size:13px;padding:12px 0">Noch keine Daten vorhanden.</p>');
        statusCtx.remove();
    }
});
</script>
