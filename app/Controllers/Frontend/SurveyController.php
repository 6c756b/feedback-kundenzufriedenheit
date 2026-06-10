<?php

namespace App\Controllers\Frontend;

use App\Core\Csrf;
use App\Core\Logger;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\Answer;
use App\Models\Question;
use App\Models\Survey;

class SurveyController
{
    public function codeInput(array $params = []): void
    {
        $config      = require ROOT . '/config.php';
        $error       = Session::flash('code_error');
        $isLanding   = true;
        $prefillCode = strtolower(trim((new Request())->get('code', '')));

        ob_start();
        require ROOT . '/app/Views/frontend/code-input.php';
        $content = ob_get_clean();
        require ROOT . '/app/Views/layout/frontend.php';
    }

    public function enter(array $params = []): void
    {
        $request = new Request();
        $code    = strtolower(trim($request->post('code', '')));

        if (!$code) {
            Session::flash('code_error', 'Bitte geben Sie einen Code ein.');
            Response::redirect('/');
        }

        Response::redirect('/umfrage/' . $code);
    }

    public function show(array $params = []): void
    {
        $code   = strtolower(trim($params['code'] ?? ''));
        $survey = Survey::findByCode($code);

        if (!$survey) {
            Session::flash('code_error', 'Dieser Code ist ungültig.');
            Response::redirect('/');
        }

        if (!in_array($survey['status'], ['open', 'started'], true)) {
            Session::flash('code_error', 'Diese Befragung wurde bereits abgeschlossen oder ist nicht mehr verfügbar.');
            Response::redirect('/');
        }

        // Beim ersten Aufruf: Status 'open' → 'started'
        if ($survey['status'] === 'open') {
            Survey::start((int)$survey['id']);
        }

        $areaIds    = Survey::getAreaIds((int)$survey['id']);
        $questions  = Question::findForSurvey($areaIds);
        $answers    = Answer::findBySurvey((int)$survey['id']);
        $totalCount = count($questions);
        $answered   = count($answers);
        $config     = require ROOT . '/config.php';

        Logger::frontend('frontend.opened', $code, 'Befragung geöffnet');

        $isLanding = true;

        ob_start();
        require ROOT . '/app/Views/frontend/survey.php';
        $content = ob_get_clean();
        require ROOT . '/app/Views/layout/frontend.php';
    }

    public function saveAnswer(array $params = []): void
    {
        if (!Csrf::validateHeader()) {
            Response::json(['error' => 'CSRF'], 403);
        }

        $code   = strtolower(trim($params['code'] ?? ''));
        $survey = Survey::findByCode($code);

        if (!$survey || !in_array($survey['status'], ['open', 'started'], true)) {
            Response::json(['error' => 'Ungültiger Code'], 400);
        }

        $request    = new Request();
        $data       = $request->jsonBody();
        $questionId = (int)($data['question_id'] ?? 0);
        $answerType = $data['answer_type'] ?? '';

        if (!$questionId || !in_array($answerType, ['slider', 'freitext', 'not_applicable'], true)) {
            Response::json(['error' => 'Ungültige Daten'], 400);
        }

        $allowedQuestions = Question::findForSurvey(Survey::getAreaIds((int)$survey['id']));
        $allowedIds       = array_column($allowedQuestions, 'id');
        if (!in_array($questionId, $allowedIds, true)) {
            Response::json(['error' => 'Ungültige Frage'], 400);
        }

        $answerData = ['answer_type' => $answerType];

        if ($answerType === 'slider') {
            $val = (float)($data['slider_value'] ?? 3);
            $val = max(1.0, min(6.0, $val));
            $answerData['slider_value']   = round($val, 1);
            $answerData['freitext_value'] = isset($data['freitext_value']) ? (string)$data['freitext_value'] : null;
        } elseif ($answerType === 'freitext') {
            $answerData['freitext_value'] = (string)($data['freitext_value'] ?? '');
        }

        Answer::upsert((int)$survey['id'], $questionId, $answerData);

        $allAnswers = Answer::findBySurvey((int)$survey['id']);

        Response::json([
            'ok'       => true,
            'progress' => [
                'answered' => count($allAnswers),
                'total'    => count($allowedQuestions),
            ],
        ]);
    }

    public function complete(array $params = []): void
    {
        if (!Csrf::validateHeader()) {
            Response::json(['error' => 'CSRF'], 403);
        }

        $code   = strtolower(trim($params['code'] ?? ''));
        $survey = Survey::findByCode($code);

        if (!$survey || !in_array($survey['status'], ['open', 'started'], true)) {
            Response::json(['error' => 'Ungültiger Code'], 400);
        }

        Survey::update((int)$survey['id'], ['status' => 'completed']);
        Logger::frontend('frontend.completed', $code, 'Befragung abgeschlossen');

        Response::json([
            'ok'                  => true,
            'reference_requested' => (bool)$survey['reference_requested'],
        ]);
    }

    public function saveReference(array $params = []): void
    {
        if (!Csrf::validateHeader()) {
            Response::json(['error' => 'CSRF'], 403);
        }

        $code   = strtolower(trim($params['code'] ?? ''));
        $survey = Survey::findByCode($code);

        if (!$survey || $survey['status'] !== 'completed') {
            Response::json(['error' => 'Ungültiger Code'], 400);
        }

        $request = new Request();
        $data    = $request->jsonBody();
        $granted = isset($data['granted']) ? (bool)$data['granted'] : null;

        if ($granted === null) {
            Response::json(['error' => 'Ungültige Antwort'], 400);
        }

        Survey::update((int)$survey['id'], [
            'reference_granted' => $granted ? 1 : 0,
        ]);

        Response::json(['ok' => true]);
    }

    public function thankYou(array $params = []): void
    {
        $config    = require ROOT . '/config.php';
        $isLanding = true;

        ob_start();
        require ROOT . '/app/Views/frontend/thank-you.php';
        $content = ob_get_clean();
        require ROOT . '/app/Views/layout/frontend.php';
    }
}
