<?php

namespace App\Controllers\Backend;

use App\Core\Database;
use App\Core\Request;
use App\Models\Area;
use App\Models\Metropolregion;
use App\Models\User;

class DashboardController
{
    public function index(array $params = []): void
    {
        $db      = Database::getInstance();
        $request = new Request();
        $userId  = \App\Core\Auth::user()['id'] ?? null;

        // Expliziter Reset: gespeicherte Filter löschen
        if (isset($_GET['reset'])) {
            if ($userId) {
                $db->execute('UPDATE users SET dashboard_filters = NULL WHERE id = ?', [$userId]);
            }
            header('Location: /backend');
            exit;
        }

        $filterKeys       = ['area_id', 'created_by', 'sales_user_id', 'project_lead_id', 'metropolregion_id'];
        $hasFilterParams  = (bool) array_intersect_key($_GET, array_flip($filterKeys));
        $hasPaginationParams = isset($_GET['fp']) || isset($_GET['mp']);

        if ($hasFilterParams) {
            // Aktuelle Filter in DB speichern
            if ($userId) {
                $toSave = [];
                if ((int)($request->get('area_id') ?: 0))           $toSave['area_id']           = (int)$request->get('area_id');
                if ((int)($request->get('created_by') ?: 0))        $toSave['created_by']        = (int)$request->get('created_by');
                if ((int)($request->get('sales_user_id') ?: 0))     $toSave['sales_user_id']     = (int)$request->get('sales_user_id');
                if ((int)($request->get('project_lead_id') ?: 0))   $toSave['project_lead_id']   = (int)$request->get('project_lead_id');
                if ((int)($request->get('metropolregion_id') ?: 0)) $toSave['metropolregion_id'] = (int)$request->get('metropolregion_id');
                $db->execute(
                    'UPDATE users SET dashboard_filters = ? WHERE id = ?',
                    [$toSave ? json_encode($toSave) : null, $userId]
                );
            }
        } elseif (!$hasPaginationParams && $userId) {
            // Keine Filter-Parameter → gespeicherte Filter laden und weiterleiten
            $savedJson = $db->fetchOne('SELECT dashboard_filters FROM users WHERE id = ?', [$userId])['dashboard_filters'] ?? null;
            if ($savedJson) {
                $saved = json_decode($savedJson, true);
                if ($saved) {
                    header('Location: /backend?' . http_build_query($saved));
                    exit;
                }
            }
        }

        $filterAreaId         = (int)$request->get('area_id');
        $filterCreatedBy      = (int)$request->get('created_by');
        $filterSalesUser      = (int)$request->get('sales_user_id');
        $filterProjectLead    = (int)$request->get('project_lead_id');
        $filterMetropolregion = (int)$request->get('metropolregion_id');

        $areas            = Area::findAllActive();
        $users            = User::findAll();
        $salesUsers       = User::findSales();
        $projectLeadUsers = User::findProjectLeads();
        $metropolregionen = Metropolregion::findAll();

        // Filters directly on surveys table
        $directWhere  = '';
        $directParams = [];
        if ($filterCreatedBy) {
            $directWhere  .= ' AND s.created_by = ?';
            $directParams[] = $filterCreatedBy;
        }
        if ($filterSalesUser) {
            $directWhere  .= ' AND s.sales_user_id = ?';
            $directParams[] = $filterSalesUser;
        }
        if ($filterProjectLead) {
            $directWhere  .= ' AND s.project_lead_id = ?';
            $directParams[] = $filterProjectLead;
        }
        if ($filterMetropolregion) {
            $directWhere  .= ' AND s.metropolregion_id = ?';
            $directParams[] = $filterMetropolregion;
        }

        // Area filter via EXISTS (alias _sa avoids conflict with survey_answers alias 'sa' in chart queries)
        $areaWhere  = $filterAreaId
            ? ' AND EXISTS (SELECT 1 FROM survey_areas _sa WHERE _sa.survey_id = s.id AND _sa.area_id = ?)'
            : '';
        $areaParams = $filterAreaId ? [$filterAreaId] : [];

        // Combined WHERE for tile + status chart queries
        $baseWhere  = $areaWhere . $directWhere;
        $baseParams = array_merge($areaParams, $directParams);

        // ── Tile 1: Laufende (open + started) ───────────────────
        $openSurveys = $db->fetchOne(
            "SELECT COUNT(*) AS cnt FROM surveys s WHERE s.status = 'open' $baseWhere",
            $baseParams
        )['cnt'] ?? 0;

        $startedSurveys = $db->fetchOne(
            "SELECT COUNT(*) AS cnt FROM surveys s WHERE s.status = 'started' $baseWhere",
            $baseParams
        )['cnt'] ?? 0;

        $activeSurveys = (int)$openSurveys + (int)$startedSurveys;

        // ── Tile 2: Abgeschlossene + Ungelesen ──────────────────
        $completedTotal = $db->fetchOne(
            "SELECT COUNT(*) AS cnt FROM surveys s WHERE s.status = 'completed' $baseWhere",
            $baseParams
        )['cnt'] ?? 0;

        $unreadEvals = $db->fetchOne(
            "SELECT COUNT(*) AS cnt FROM surveys s WHERE s.status = 'completed' AND s.read_at IS NULL $baseWhere",
            $baseParams
        )['cnt'] ?? 0;

        // ── Tile 3: Archiv (archived + cancelled) ───────────────
        $archivedTotal = $db->fetchOne(
            "SELECT COUNT(*) AS cnt FROM surveys s WHERE s.status IN ('archived','cancelled') $baseWhere",
            $baseParams
        )['cnt'] ?? 0;

        $cancelledCount = $db->fetchOne(
            "SELECT COUNT(*) AS cnt FROM surveys s WHERE s.status = 'cancelled' $baseWhere",
            $baseParams
        )['cnt'] ?? 0;

        $logSince    = date('Y-m-d', strtotime('-12 months'));
        $perPage     = 15;

        // ── Frontend-Aktivitäten (Teilnehmer) ───────────────────
        $fPage       = max(1, (int)$request->get('fp', 1));
        $fOffset     = ($fPage - 1) * $perPage;
        $fTotal      = (int)($db->fetchOne(
            "SELECT COUNT(*) AS cnt FROM logs l
             WHERE l.actor_type = 'frontend' AND l.created_at >= ?", [$logSince]
        )['cnt'] ?? 0);
        $fTotalPages = (int) ceil($fTotal / $perPage);

        $frontendLogs = $db->fetchAll(
            "SELECT l.*, s.customer_name AS survey_customer_name, s.project_name AS survey_project_name
             FROM logs l
             LEFT JOIN surveys s ON s.code = l.survey_code
             WHERE l.actor_type = 'frontend' AND l.created_at >= ?
             ORDER BY l.created_at DESC
             LIMIT ? OFFSET ?",
            [$logSince, $perPage, $fOffset]
        );

        // ── Backend-Mailings ─────────────────────────────────────
        $mPage       = max(1, (int)$request->get('mp', 1));
        $mOffset     = ($mPage - 1) * $perPage;
        $mTotal      = (int)($db->fetchOne(
            "SELECT COUNT(*) AS cnt FROM logs l
             WHERE l.action IN ('survey.email_sent','survey.email_confirmed') AND l.created_at >= ?",
            [$logSince]
        )['cnt'] ?? 0);
        $mTotalPages = (int) ceil($mTotal / $perPage);

        $mailingLogs = $db->fetchAll(
            "SELECT l.*, u.name AS user_name, s.customer_name AS survey_customer_name, s.status AS survey_status
             FROM logs l
             LEFT JOIN users u   ON u.id = l.user_id
             LEFT JOIN surveys s ON s.id = l.entity_id AND l.entity_type = 'survey'
             WHERE l.action IN ('survey.email_sent','survey.email_confirmed') AND l.created_at >= ?
             ORDER BY l.created_at DESC
             LIMIT ? OFFSET ?",
            [$logSince, $perPage, $mOffset]
        );

        // ── Chart 1: Bewertungsverlauf (12 Monate) ───────────────
        $since12m = date('Y-m-d', strtotime('-12 months'));
        $monthFmt = $db->driver() === 'sqlite'
            ? "strftime('%Y-%m', s.updated_at)"
            : "DATE_FORMAT(s.updated_at, '%Y-%m')";

        if ($filterAreaId) {
            $monthlyAvg = $db->fetchAll(
                "SELECT $monthFmt AS month,
                        AVG(sa.slider_value) AS avg_note
                 FROM surveys s
                 JOIN survey_answers sa ON sa.survey_id = s.id
                 JOIN questions q ON q.id = sa.question_id
                 WHERE s.status IN ('completed','archived')
                   AND s.updated_at >= ?
                   AND sa.answer_type = 'slider'
                   AND sa.slider_value IS NOT NULL
                   AND q.area_id = ?
                   $directWhere
                 GROUP BY $monthFmt
                 ORDER BY month ASC",
                array_merge([$since12m, $filterAreaId], $directParams)
            );
        } else {
            $monthlyAvg = $db->fetchAll(
                "SELECT $monthFmt AS month,
                        AVG(sa.slider_value) AS avg_note
                 FROM surveys s
                 JOIN survey_answers sa ON sa.survey_id = s.id AND sa.answer_type = 'slider' AND sa.slider_value IS NOT NULL
                 WHERE s.status IN ('completed','archived')
                   AND s.updated_at >= ?
                   $directWhere
                 GROUP BY $monthFmt
                 ORDER BY month ASC",
                array_merge([$since12m], $directParams)
            );
        }

        // ── Chart 2: Notenverteilung ─────────────────────────────
        if ($filterAreaId) {
            $noteDist = $db->fetchAll(
                "SELECT
                   CASE
                     WHEN avg_val < 2   THEN '1-2'
                     WHEN avg_val < 3   THEN '2-3'
                     WHEN avg_val < 4   THEN '3-4'
                     WHEN avg_val < 5   THEN '4-5'
                     ELSE '5-6'
                   END AS range_label,
                   COUNT(*) AS cnt
                 FROM (
                   SELECT sa.survey_id, AVG(sa.slider_value) AS avg_val
                   FROM surveys s
                   JOIN survey_answers sa ON sa.survey_id = s.id
                   JOIN questions q ON q.id = sa.question_id
                   WHERE s.status IN ('completed','archived')
                     AND s.updated_at >= ?
                     AND sa.answer_type = 'slider'
                     AND sa.slider_value IS NOT NULL
                     AND q.area_id = ?
                     $directWhere
                   GROUP BY sa.survey_id
                 ) t
                 GROUP BY range_label
                 ORDER BY range_label ASC",
                array_merge([$since12m, $filterAreaId], $directParams)
            );
        } else {
            $noteDist = $db->fetchAll(
                "SELECT
                   CASE
                     WHEN avg_val < 2   THEN '1-2'
                     WHEN avg_val < 3   THEN '2-3'
                     WHEN avg_val < 4   THEN '3-4'
                     WHEN avg_val < 5   THEN '4-5'
                     ELSE '5-6'
                   END AS range_label,
                   COUNT(*) AS cnt
                 FROM (
                   SELECT s.id, AVG(sa.slider_value) AS avg_val
                   FROM surveys s
                   JOIN survey_answers sa ON sa.survey_id = s.id AND sa.answer_type = 'slider' AND sa.slider_value IS NOT NULL
                   WHERE s.status IN ('completed','archived')
                     AND s.updated_at >= ?
                     $directWhere
                   GROUP BY s.id
                 ) t
                 GROUP BY range_label
                 ORDER BY range_label ASC",
                array_merge([$since12m], $directParams)
            );
        }

        // ── Chart 3: Status-Verteilung (12 Monate) ───────────────
        $statusDist = $db->fetchAll(
            "SELECT s.status, COUNT(*) AS cnt
             FROM surveys s
             WHERE (s.updated_at >= ? OR s.created_at >= ?)
             $baseWhere
             GROUP BY s.status",
            array_merge([$since12m, $since12m], $baseParams)
        );

        // Filter-QS für Chart-Click weitergeben
        $filterQsRawParts = [];
        if ($filterAreaId)         $filterQsRawParts[] = 'area_id='           . (int)$filterAreaId;
        if ($filterCreatedBy)      $filterQsRawParts[] = 'created_by='        . (int)$filterCreatedBy;
        if ($filterSalesUser)      $filterQsRawParts[] = 'sales_user_id='     . (int)$filterSalesUser;
        if ($filterProjectLead)    $filterQsRawParts[] = 'project_lead_id='   . (int)$filterProjectLead;
        if ($filterMetropolregion) $filterQsRawParts[] = 'metropolregion_id=' . (int)$filterMetropolregion;

        // Chart-Daten aufbereiten
        $chartData = [
            'monthly' => [
                'labels' => array_map(fn($r) => date('M Y', strtotime($r['month'] . '-01')), $monthlyAvg),
                'data'   => array_map(fn($r) => round((float)$r['avg_note'], 2), $monthlyAvg),
            ],
            'noteDist' => [
                'labels' => array_map(fn($r) => $r['range_label'], $noteDist),
                'data'   => array_map(fn($r) => (int)$r['cnt'], $noteDist),
            ],
            'status' => [
                'labels' => array_map(fn($r) => match($r['status']) {
                    'open'      => 'Offen',
                    'started'   => 'Gestartet',
                    'completed' => 'Abgeschlossen',
                    'archived'  => 'Archiviert',
                    'cancelled' => 'Abgebrochen',
                    default     => $r['status'],
                }, $statusDist),
                'data'   => array_map(fn($r) => (int)$r['cnt'], $statusDist),
            ],
            'filterQsRaw' => implode('&', $filterQsRawParts),
        ];

        $needsChartJs = true;
        $pageTitle    = 'Dashboard';
        ob_start();
        require ROOT . '/app/Views/backend/dashboard.php';
        $content = ob_get_clean();
        require ROOT . '/app/Views/layout/backend.php';
    }
}
