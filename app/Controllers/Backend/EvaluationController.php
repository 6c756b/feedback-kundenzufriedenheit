<?php

namespace App\Controllers\Backend;

use App\Core\Csrf;
use App\Core\Logger;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\Answer;
use App\Models\Area;
use App\Models\Metropolregion;
use App\Models\Question;
use App\Models\Survey;
use App\Models\User;
use App\Services\PdfExport;

class EvaluationController
{
    public function index(array $params = []): void
    {
        $request = new Request();
        $page    = max(1, (int)$request->get('page', 1));

        $showAll  = $request->get('show_all');
        $statusIn = $showAll ? ['completed', 'archived'] : ['completed'];

        $noteRange = array_intersect(
            (array)($request->get('note_range') ?? []),
            ['1-2', '2-3', '3-4', '4-5', '5-6']
        );

        $filters = [
            'area_id'           => $request->get('area_id'),
            'customer_name'     => $request->get('customer_name'),
            'created_by'        => $request->get('created_by'),
            'sales_user_id'     => $request->get('sales_user_id'),
            'project_lead_id'   => $request->get('project_lead_id'),
            'metropolregion_id' => $request->get('metropolregion_id'),
            'status_in'         => $statusIn,
            'read_status'       => $request->get('read_status'),
            'note_range'        => $noteRange,
            'sort_by'           => $request->get('sort_by', 'datum'),
            'sort_dir'          => $request->get('sort_dir', 'DESC'),
        ];

        $surveys          = Survey::listFiltered($filters, $page);
        $totalCount       = Survey::countFiltered($filters);
        $totalPages       = (int) ceil($totalCount / 25);
        $areas            = Area::findAllActive();
        $users            = User::findAll();
        $salesUsers       = User::findSales();
        $projectLeadUsers = User::findProjectLeads();
        $metropolregionen = Metropolregion::findAll();
        $customerNames    = Survey::distinctCustomerNames();

        $surveyIds = array_column($surveys, 'id');
        $averages  = Answer::getAverageSliderValueBulk($surveyIds);
        foreach ($surveys as &$survey) {
            $survey['avg'] = $averages[(int)$survey['id']] ?? null;
        }
        unset($survey);

        if (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest') {
            ob_start();
            require ROOT . '/app/Views/backend/evaluation/_list.php';
            echo ob_get_clean();
            exit;
        }

        $pageTitle = 'Auswertung';
        ob_start();
        require ROOT . '/app/Views/backend/evaluation/index.php';
        $content = ob_get_clean();
        require ROOT . '/app/Views/layout/backend.php';
    }

    public function archiveIndex(array $params = []): void
    {
        $request = new Request();
        $page    = max(1, (int)$request->get('page', 1));

        $filters = [
            'area_id'           => $request->get('area_id'),
            'customer_name'     => $request->get('customer_name'),
            'created_by'        => $request->get('created_by'),
            'sales_user_id'     => $request->get('sales_user_id'),
            'project_lead_id'   => $request->get('project_lead_id'),
            'metropolregion_id' => $request->get('metropolregion_id'),
            'status_in'         => ['archived', 'cancelled'],
            'sort_by'           => $request->get('sort_by', 'datum'),
            'sort_dir'          => $request->get('sort_dir', 'DESC'),
        ];

        $surveys          = Survey::listFiltered($filters, $page);
        $totalCount       = Survey::countFiltered($filters);
        $totalPages       = (int) ceil($totalCount / 25);
        $areas            = Area::findAllActive();
        $users            = User::findAll();
        $salesUsers       = User::findSales();
        $projectLeadUsers = User::findProjectLeads();
        $metropolregionen = Metropolregion::findAll();
        $customerNames    = Survey::distinctCustomerNames();

        $surveyIds = array_column($surveys, 'id');
        $averages  = Answer::getAverageSliderValueBulk($surveyIds);
        foreach ($surveys as &$survey) {
            $survey['avg'] = $averages[(int)$survey['id']] ?? null;
        }
        unset($survey);

        if (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest') {
            ob_start();
            require ROOT . '/app/Views/backend/evaluation/_archive_list.php';
            echo ob_get_clean();
            exit;
        }

        $pageTitle = 'Archiv';
        ob_start();
        require ROOT . '/app/Views/backend/evaluation/archive.php';
        $content = ob_get_clean();
        require ROOT . '/app/Views/layout/backend.php';
    }

    public function detail(array $params = []): void
    {
        $survey = Survey::findById((int)$params['id']);
        if (!$survey) {
            Response::json(['error' => 'Nicht gefunden'], 404);
        }

        $areaIds   = Survey::getAreaIds((int)$survey['id']);
        $questions = Question::findForSurvey($areaIds);
        $answers   = Answer::findBySurvey((int)$survey['id']);
        $avg       = Answer::getAverageSliderValue((int)$survey['id']);

        $questionsWithAnswers = [];
        foreach ($questions as $q) {
            $questionsWithAnswers[] = [
                'question' => $q,
                'answer'   => $answers[$q['id']] ?? null,
            ];
        }

        Response::json([
            'survey'    => $survey,
            'questions' => $questionsWithAnswers,
            'avg'       => $avg,
        ]);
    }

    public function exportPdf(array $params = []): void
    {
        $survey = Survey::findById((int)$params['id']);
        if (!$survey) {
            Response::notFound();
        }

        $areaIds   = Survey::getAreaIds((int)$survey['id']);
        $questions = Question::findForSurvey($areaIds);
        $answers   = Answer::findBySurvey((int)$survey['id']);

        PdfExport::generate($survey, $questions, $answers);
    }

    public function markRead(array $params = []): void
    {
        if (!Csrf::validate()) {
            Response::forbidden();
        }

        $id = (int)$params['id'];
        Survey::markRead($id);
        Logger::backend('evaluation.read', 'survey', $id, 'Als gelesen markiert');

        if (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest') {
            Response::json(['ok' => true]);
        } else {
            Session::flash('success', 'Als gelesen markiert.');
            Response::redirect('/backend/auswertung');
        }
    }

    public function markUnread(array $params = []): void
    {
        if (!Csrf::validate()) {
            Response::forbidden();
        }

        $id = (int)$params['id'];
        Survey::markUnread($id);
        Logger::backend('evaluation.unread', 'survey', $id, 'Als ungelesen markiert');

        if (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest') {
            Response::json(['ok' => true]);
        } else {
            Session::flash('success', 'Als ungelesen markiert.');
            Response::redirect('/backend/auswertung');
        }
    }

    public function archive(array $params = []): void
    {
        if (!Csrf::validate()) {
            Response::forbidden();
        }

        $id = (int)$params['id'];
        Survey::archive($id);
        Logger::backend('evaluation.archived', 'survey', $id, 'Archiviert');
        Session::flash('success', 'Archiviert.');
        Response::redirect('/backend/auswertung');
    }
}
