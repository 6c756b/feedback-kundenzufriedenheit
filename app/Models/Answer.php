<?php

namespace App\Models;

use App\Core\Database;

class Answer
{
    public static function findBySurvey(int $surveyId): array
    {
        $rows = Database::getInstance()->fetchAll(
            'SELECT * FROM survey_answers WHERE survey_id = ?',
            [$surveyId]
        );

        // Indiziert nach question_id für schnellen Zugriff
        $indexed = [];
        foreach ($rows as $row) {
            $indexed[$row['question_id']] = $row;
        }
        return $indexed;
    }

    public static function upsert(int $surveyId, int $questionId, array $data): void
    {
        $data['survey_id']   = $surveyId;
        $data['question_id'] = $questionId;

        $cols         = array_keys($data);
        $placeholders = array_fill(0, count($cols), '?');
        $updateCols   = array_diff($cols, ['survey_id', 'question_id']);

        $db = Database::getInstance();

        if ($db->driver() === 'sqlite') {
            $updates = array_map(fn($c) => "`$c` = excluded.`$c`", $updateCols);
            $sql = sprintf(
                'INSERT INTO survey_answers (%s) VALUES (%s) ON CONFLICT(survey_id, question_id) DO UPDATE SET %s',
                implode(', ', array_map(fn($c) => "`$c`", $cols)),
                implode(', ', $placeholders),
                implode(', ', $updates)
            );
        } else {
            $updates = array_map(fn($c) => "`$c` = VALUES(`$c`)", $updateCols);
            $sql = sprintf(
                'INSERT INTO survey_answers (%s) VALUES (%s) ON DUPLICATE KEY UPDATE %s',
                implode(', ', array_map(fn($c) => "`$c`", $cols)),
                implode(', ', $placeholders),
                implode(', ', $updates)
            );
        }

        $db->execute($sql, array_values($data));
    }

    public static function getCompletionStatus(int $surveyId, int $totalQuestions): array
    {
        $answered = Database::getInstance()->fetchOne(
            'SELECT COUNT(*) AS cnt FROM survey_answers WHERE survey_id = ?',
            [$surveyId]
        );

        return [
            'answered' => (int) ($answered['cnt'] ?? 0),
            'total'    => $totalQuestions,
        ];
    }

    public static function allAnswered(int $surveyId, int $totalQuestions): bool
    {
        $status = self::getCompletionStatus($surveyId, $totalQuestions);
        return $status['answered'] >= $status['total'];
    }

    public static function getAverageSliderValue(int $surveyId): ?float
    {
        $row = Database::getInstance()->fetchOne(
            "SELECT AVG(slider_value) AS avg_val
             FROM survey_answers
             WHERE survey_id = ? AND answer_type = 'slider' AND slider_value IS NOT NULL",
            [$surveyId]
        );

        return isset($row['avg_val']) ? round((float)$row['avg_val'], 2) : null;
    }

    public static function getAverageSliderValueBulk(array $surveyIds): array
    {
        if (empty($surveyIds)) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($surveyIds), '?'));
        $rows = Database::getInstance()->fetchAll(
            "SELECT survey_id, AVG(slider_value) AS avg_val
             FROM survey_answers
             WHERE survey_id IN ($placeholders)
               AND answer_type = 'slider'
               AND slider_value IS NOT NULL
             GROUP BY survey_id",
            array_values($surveyIds)
        );

        $result = [];
        foreach ($rows as $row) {
            $result[(int)$row['survey_id']] = round((float)$row['avg_val'], 2);
        }
        return $result;
    }
}
