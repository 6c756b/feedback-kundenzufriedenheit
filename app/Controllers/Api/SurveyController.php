<?php

namespace App\Controllers\Api;

use App\Core\ApiAuth;
use App\Core\Response;
use App\Models\Answer;
use App\Models\Area;
use App\Models\Metropolregion;
use App\Models\Survey;
use App\Models\User;
use App\Services\CodeGenerator;

class SurveyController
{
    public function store(array $params = []): void
    {
        ApiAuth::authenticate();
        ApiAuth::requireWrite();

        $body = json_decode(file_get_contents('php://input'), true);
        if (!is_array($body)) {
            Response::json(['success' => false, 'error' => 'Ungültiger JSON-Body', 'code' => 'INVALID_JSON'], 400);
        }

        // --- Pflichtfelder ---
        $errors = [];

        $customerName = trim($body['customer_name'] ?? '');
        if ($customerName === '') {
            $errors[] = 'customer_name ist erforderlich';
        } elseif (strlen($customerName) > 255) {
            $errors[] = 'customer_name darf max. 255 Zeichen haben';
        }

        $customerEmail = trim($body['customer_email'] ?? '');
        if ($customerEmail !== '' && strlen($customerEmail) > 255) {
            $errors[] = 'customer_email darf max. 255 Zeichen haben';
        }

        $areaIds = array_values(array_unique(array_filter(array_map('intval', (array)($body['area_ids'] ?? [])))));
        if (empty($areaIds)) {
            $errors[] = 'area_ids ist erforderlich (min. 1 Element)';
        }

        if ($errors) {
            Response::json(['success' => false, 'error' => implode('; ', $errors), 'code' => 'VALIDATION_ERROR'], 400);
        }

        // --- Bereiche prüfen ---
        foreach ($areaIds as $areaId) {
            if (!Area::findById($areaId)) {
                Response::json([
                    'success' => false,
                    'error'   => "Bereich mit ID $areaId nicht gefunden",
                    'code'    => 'VALIDATION_ERROR',
                ], 400);
            }
        }

        // --- Namens-Lookups ---
        $salesUserId = null;
        if (!empty($body['sales_user'])) {
            $u = User::findSalesByName(trim($body['sales_user']));
            if ($u) {
                $salesUserId = (int)$u['id'];
            }
        }

        $projectLeadId = null;
        if (!empty($body['project_lead'])) {
            $u = User::findProjectLeadByName(trim($body['project_lead']));
            if ($u) {
                $projectLeadId = (int)$u['id'];
            }
        }

        $metropolregionId = null;
        if (!empty($body['metropolregion'])) {
            $mr = Metropolregion::findByName(trim($body['metropolregion']));
            if ($mr) {
                $metropolregionId = (int)$mr['id'];
            }
        }

        $contactPerson      = trim($body['contact_person'] ?? '');
        $projectId          = trim($body['project_id'] ?? '');
        $referenceRequested = !empty($body['reference_requested']) ? 1 : 0;
        $crmId              = isset($body['crm_id']) ? (int)$body['crm_id'] : null;
        $internalNotes      = trim($body['internal_notes'] ?? '') ?: null;

        // --- created_by (optional, per Name) ---
        $createdBy = null;
        if (!empty($body['created_by'])) {
            $u = User::findByName(trim($body['created_by']));
            if ($u) {
                $createdBy = (int)$u['id'];
            }
        }

        // --- Anlegen ---
        $config  = require ROOT . '/config.php';
        $baseUrl = rtrim($config['app']['url'] ?? '', '/');
        $code    = CodeGenerator::generate();

        $id = Survey::create([
            'code'              => $code,
            'customer_name'     => $customerName,
            'project_name'      => trim($body['project_name'] ?? ''),
            'contact_salutation'    => '',
            'contact_person'        => $contactPerson,
            'contact_email'         => $customerEmail,
            'area_id'               => $areaIds[0],
            'project_id'            => $projectId !== '' ? $projectId : null,
            'crm_id'                => $crmId,
            'reference_requested'   => $referenceRequested,
            'sales_user_id'     => $salesUserId,
            'project_lead_id'   => $projectLeadId,
            'metropolregion_id' => $metropolregionId,
            'status'            => 'open',
            'internal_notes'    => $internalNotes,
            'created_by'        => $createdBy,
        ]);

        Survey::syncAreas($id, $areaIds);

        Response::json([
            'success' => true,
            'data'    => [
                'id'          => $id,
                'code'        => $code,
                'backend_url' => $baseUrl . '/backend/befragungen/' . $id,
                'crm_id'      => $crmId,
            ],
        ], 201);
    }

    public function show(array $params = []): void
    {
        ApiAuth::authenticate();
        ApiAuth::requireRead();

        $survey = Survey::findById((int)$params['id']);
        if (!$survey) {
            Response::json([
                'success' => false,
                'error'   => 'Befragung nicht gefunden',
                'code'    => 'NOT_FOUND',
            ], 404);
        }

        $config  = require ROOT . '/config.php';
        $baseUrl = rtrim($config['app']['url'] ?? '', '/');

        $avg = $survey['status'] === 'completed'
            ? Answer::getAverageSliderValue((int)$survey['id'])
            : null;

        Response::json([
            'success' => true,
            'data'    => [
                'id'            => (int)$survey['id'],
                'code'          => $survey['code'],
                'status'        => $survey['status'],
                'crm_id'        => $survey['crm_id'] !== null ? (int)$survey['crm_id'] : null,
                'customer_name' => $survey['customer_name'],
                'project_name'  => $survey['project_name'],
                'avg_score'     => $avg !== null ? round((float)$avg, 2) : null,
                'completed_at'  => $survey['updated_at'] ?? null,
                'backend_url'   => $baseUrl . '/backend/befragungen/' . (int)$survey['id'],
            ],
        ]);
    }
}
