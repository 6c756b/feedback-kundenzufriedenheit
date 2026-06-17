<?php

namespace App\Models;

use App\Core\Database;

class Survey
{
    private static function baseSelect(): string
    {
        $db = Database::getInstance();
        $gc = $db->driver() === 'sqlite'
            ? "GROUP_CONCAT(a_sa.name, ', ')"
            : "GROUP_CONCAT(a_sa.name ORDER BY a_sa.sort_order, a_sa.name SEPARATOR ', ')";

        return "SELECT s.*,
            (SELECT $gc
             FROM survey_areas sa_sub
             JOIN areas a_sa ON a_sa.id = sa_sub.area_id
             WHERE sa_sub.survey_id = s.id) AS area_names,
            u.name             AS created_by_name,
            u.signature_image  AS created_by_image,
            su.name            AS sales_user_name,
            su.signature_image AS sales_user_image,
            su.job_title       AS sales_user_job_title,
            su.email           AS sales_user_email,
            su.phone           AS sales_user_phone,
            pl.name            AS project_lead_name,
            pl.signature_image AS project_lead_image,
            pl.job_title       AS project_lead_job_title,
            pl.email           AS project_lead_email,
            pl.phone           AS project_lead_phone,
            esb.name AS email_sent_by_name,
            mr.name  AS metropolregion_name
        FROM surveys s
        LEFT JOIN users u   ON u.id   = s.created_by
        LEFT JOIN users su  ON su.id  = s.sales_user_id
        LEFT JOIN users pl  ON pl.id  = s.project_lead_id
        LEFT JOIN users esb ON esb.id = s.email_sent_by
        LEFT JOIN metropolregionen mr ON mr.id = s.metropolregion_id";
    }

    public static function findByCode(string $code): ?array
    {
        return Database::getInstance()->fetchOne(
            self::baseSelect() . ' WHERE s.code = ?',
            [$code]
        );
    }

    public static function findById(int $id): ?array
    {
        return Database::getInstance()->fetchOne(
            self::baseSelect() . ' WHERE s.id = ?',
            [$id]
        );
    }

    /** Liefert alle area_ids einer Befragung aus der Pivot-Tabelle. */
    public static function getAreaIds(int $surveyId): array
    {
        $rows = Database::getInstance()->fetchAll(
            'SELECT area_id FROM survey_areas WHERE survey_id = ? ORDER BY area_id',
            [$surveyId]
        );
        return array_column($rows, 'area_id');
    }

    /** Synchronisiert die survey_areas Pivot-Tabelle für eine Befragung. */
    public static function syncAreas(int $surveyId, array $areaIds): void
    {
        $db = Database::getInstance();
        $db->beginTransaction();
        try {
            $db->execute('DELETE FROM survey_areas WHERE survey_id = ?', [$surveyId]);
            foreach (array_unique(array_filter(array_map('intval', $areaIds))) as $areaId) {
                $db->insert('survey_areas', ['survey_id' => $surveyId, 'area_id' => $areaId]);
            }
            $db->commit();
        } catch (\Throwable $e) {
            $db->rollback();
            throw $e;
        }
    }

    public static function create(array $data): int
    {
        return Database::getInstance()->insert('surveys', $data);
    }

    public static function update(int $id, array $data): void
    {
        $sets   = implode(', ', array_map(fn($k) => "`$k` = ?", array_keys($data)));
        $params = array_values($data);
        $params[] = $id;
        Database::getInstance()->execute("UPDATE surveys SET $sets WHERE id = ?", $params);
    }

    public static function delete(int $id): void
    {
        $db = Database::getInstance();
        $db->beginTransaction();
        try {
            $db->execute('DELETE FROM survey_answers WHERE survey_id = ?', [$id]);
            $db->execute('DELETE FROM survey_areas   WHERE survey_id = ?', [$id]);
            $db->execute('DELETE FROM surveys         WHERE id = ?',       [$id]);
            $db->commit();
        } catch (\Throwable $e) {
            $db->rollback();
            throw $e;
        }
    }

    public static function listFiltered(array $filters, int $page, int $perPage = 25): array
    {
        [$where, $params] = self::buildWhereClause($filters);

        $allowedSort = [
            'kunde'    => 's.customer_name',
            'projekt'  => 's.project_name',
            'befrager' => 'u.name',
            'datum'    => 's.updated_at',
            'erstellt' => 's.created_at',
            'status'   => 's.read_at IS NULL',
            'referenz' => "CASE WHEN s.reference_requested = 0 THEN 0 WHEN s.reference_granted = 0 THEN 1 WHEN s.reference_granted IS NULL THEN 2 ELSE 3 END",
        ];

        $sortBy  = $filters['sort_by'] ?? 'datum';
        $sortDir = strtoupper($filters['sort_dir'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';

        if ($sortBy === 'note') {
            $noteSubq = "(SELECT AVG(sa_ord.slider_value) FROM survey_answers sa_ord WHERE sa_ord.survey_id = s.id AND sa_ord.answer_type = 'slider' AND sa_ord.slider_value IS NOT NULL)";
            $nullFill = $sortDir === 'ASC' ? '9999' : '-9999';
            $orderExpr = "COALESCE($noteSubq, $nullFill) $sortDir";
        } elseif (isset($allowedSort[$sortBy])) {
            $orderExpr = $allowedSort[$sortBy] . ' ' . $sortDir;
        } else {
            $orderExpr = 's.updated_at DESC';
        }

        $offset = ($page - 1) * $perPage;
        $params[] = $perPage;
        $params[] = $offset;

        return Database::getInstance()->fetchAll(
            self::baseSelect() . " $where ORDER BY $orderExpr LIMIT ? OFFSET ?",
            $params
        );
    }

    public static function countFiltered(array $filters): int
    {
        [$where, $params] = self::buildWhereClause($filters);

        $row = Database::getInstance()->fetchOne(
            "SELECT COUNT(*) AS cnt FROM surveys s $where",
            $params
        );
        return (int) ($row['cnt'] ?? 0);
    }

    private static function buildWhereClause(array $filters): array
    {
        $conditions = [];
        $params     = [];

        if (!empty($filters['area_id'])) {
            $conditions[] = 'EXISTS (SELECT 1 FROM survey_areas sa WHERE sa.survey_id = s.id AND sa.area_id = ?)';
            $params[]     = (int)$filters['area_id'];
        }

        // status_in (array) hat Vorrang vor status (string)
        if (!empty($filters['status_in'])) {
            $ph = implode(',', array_fill(0, count($filters['status_in']), '?'));
            $conditions[] = "s.status IN ($ph)";
            foreach ($filters['status_in'] as $s) {
                $params[] = $s;
            }
        } elseif (!empty($filters['status'])) {
            $conditions[] = 's.status = ?';
            $params[]     = $filters['status'];
        } elseif (empty($filters['show_archived'])) {
            $conditions[] = "s.status NOT IN ('archived','cancelled')";
        }

        if (!empty($filters['read_status']) && $filters['read_status'] === 'unread') {
            $conditions[] = 's.read_at IS NULL';
        }

        if (!empty($filters['customer_name'])) {
            $conditions[] = 's.customer_name LIKE ?';
            $params[]     = '%' . $filters['customer_name'] . '%';
        }

        if (!empty($filters['created_from'])) {
            $conditions[] = 'DATE(s.created_at) >= ?';
            $params[]     = $filters['created_from'];
        }

        if (!empty($filters['created_to'])) {
            $conditions[] = 'DATE(s.created_at) <= ?';
            $params[]     = $filters['created_to'];
        }

        if (!empty($filters['created_by'])) {
            $conditions[] = 's.created_by = ?';
            $params[]     = $filters['created_by'];
        }

        if (!empty($filters['sales_user_id'])) {
            $conditions[] = 's.sales_user_id = ?';
            $params[]     = $filters['sales_user_id'];
        }

        if (!empty($filters['project_lead_id'])) {
            $conditions[] = 's.project_lead_id = ?';
            $params[]     = (int)$filters['project_lead_id'];
        }

        if (!empty($filters['metropolregion_id'])) {
            $conditions[] = 's.metropolregion_id = ?';
            $params[]     = (int)$filters['metropolregion_id'];
        }

        if (!empty($filters['note_range'])) {
            $ranges      = array_intersect((array)$filters['note_range'], ['1-2', '2-3', '3-4', '4-5', '5-6']);
            if (!empty($ranges)) {
                $ph = implode(',', array_fill(0, count($ranges), '?'));
                $conditions[] = "(
                    SELECT CASE
                        WHEN AVG(sa_nr.slider_value) < 2 THEN '1-2'
                        WHEN AVG(sa_nr.slider_value) < 3 THEN '2-3'
                        WHEN AVG(sa_nr.slider_value) < 4 THEN '3-4'
                        WHEN AVG(sa_nr.slider_value) < 5 THEN '4-5'
                        ELSE '5-6'
                    END
                    FROM survey_answers sa_nr
                    WHERE sa_nr.survey_id = s.id AND sa_nr.answer_type = 'slider' AND sa_nr.slider_value IS NOT NULL
                ) IN ($ph)";
                foreach ($ranges as $r) {
                    $params[] = $r;
                }
            }
        }

        $where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
        return [$where, $params];
    }

    public static function markRead(int $id): void
    {
        Database::getInstance()->execute(
            'UPDATE surveys SET read_at = CURRENT_TIMESTAMP WHERE id = ? AND read_at IS NULL',
            [$id]
        );
    }

    public static function markUnread(int $id): void
    {
        Database::getInstance()->execute(
            'UPDATE surveys SET read_at = NULL WHERE id = ?',
            [$id]
        );
    }

    public static function archive(int $id): void
    {
        Database::getInstance()->execute(
            "UPDATE surveys SET status = 'archived' WHERE id = ?",
            [$id]
        );
    }

    public static function cancel(int $id): void
    {
        Database::getInstance()->execute(
            "UPDATE surveys SET status = 'cancelled' WHERE id = ?",
            [$id]
        );
    }

    public static function start(int $id): void
    {
        Database::getInstance()->execute(
            "UPDATE surveys SET status = 'started' WHERE id = ? AND status = 'open'",
            [$id]
        );
    }

    public static function markEmailSent(int $id, int $userId, string $method): void
    {
        Database::getInstance()->execute(
            "UPDATE surveys SET email_sent_at = CURRENT_TIMESTAMP, email_sent_by = ?, email_sent_method = ? WHERE id = ?",
            [$userId, $method, $id]
        );
    }

    public static function findByCrmId(int $crmId): ?array
    {
        return Database::getInstance()->fetchOne(
            'SELECT id FROM surveys WHERE crm_id = ?',
            [$crmId]
        );
    }

    public static function distinctCustomerNames(): array
    {
        $rows = Database::getInstance()->fetchAll(
            'SELECT DISTINCT customer_name FROM surveys ORDER BY customer_name'
        );
        return array_column($rows, 'customer_name');
    }
}
