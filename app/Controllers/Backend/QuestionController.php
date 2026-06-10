<?php

namespace App\Controllers\Backend;

use App\Core\Csrf;
use App\Core\Logger;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\Area;
use App\Models\Question;

class QuestionController
{
    public function index(array $params = []): void
    {
        $questions = Question::findAll();
        $areas     = Area::findAll();

        // Gruppieren nach Bereich
        $grouped = [];
        foreach ($questions as $q) {
            $grouped[$q['area_id']]['area_name']   = $q['area_name'];
            $grouped[$q['area_id']]['area_active']  = $q['area_active'];
            $grouped[$q['area_id']]['questions'][]  = $q;
        }

        $pageTitle = 'Fragenverwaltung';
        ob_start();
        require ROOT . '/app/Views/backend/questions/index.php';
        $content = ob_get_clean();
        require ROOT . '/app/Views/layout/backend.php';
    }

    public function create(array $params = []): void
    {
        $areas    = Area::findAll();
        $question = [];

        $pageTitle = 'Neue Frage';
        ob_start();
        require ROOT . '/app/Views/backend/questions/form.php';
        $content = ob_get_clean();
        require ROOT . '/app/Views/layout/backend.php';
    }

    public function store(array $params = []): void
    {
        if (!Csrf::validate()) {
            Response::forbidden();
        }

        $request = new Request();
        $errors  = $this->validate($request);

        if ($errors) {
            Session::flash('errors', $errors);
            Session::flash('old', $_POST);
            Response::redirect('/backend/fragen/neu');
        }

        $id = Question::create($this->buildData($request));
        Logger::backend('question.created', 'question', $id, 'Frage angelegt');
        Session::flash('success', 'Frage wurde angelegt.');
        Response::redirect('/backend/fragen');
    }

    public function edit(array $params = []): void
    {
        $question = Question::findById((int)$params['id']);
        if (!$question) {
            Response::notFound();
        }

        $areas     = Area::findAll();
        $pageTitle = 'Frage bearbeiten';
        ob_start();
        require ROOT . '/app/Views/backend/questions/form.php';
        $content = ob_get_clean();
        require ROOT . '/app/Views/layout/backend.php';
    }

    public function update(array $params = []): void
    {
        if (!Csrf::validate()) {
            Response::forbidden();
        }

        $id       = (int)$params['id'];
        $question = Question::findById($id);
        if (!$question) {
            Response::notFound();
        }

        $request = new Request();
        $errors  = $this->validate($request);

        if ($errors) {
            Session::flash('errors', $errors);
            Session::flash('old', $_POST);
            Response::redirect('/backend/fragen/' . $id . '/bearbeiten');
        }

        Question::update($id, $this->buildData($request));
        Logger::backend('question.updated', 'question', $id, 'Frage aktualisiert');
        Session::flash('success', 'Frage wurde aktualisiert.');
        Response::redirect('/backend/fragen');
    }

    public function destroy(array $params = []): void
    {
        if (!Csrf::validate()) {
            Response::forbidden();
        }

        $id       = (int)$params['id'];
        $question = Question::findById($id);
        if (!$question) {
            Response::notFound();
        }

        Question::delete($id);
        Logger::backend('question.deleted', 'question', $id, 'Frage gelöscht: ' . $question['label_short']);
        Session::flash('success', 'Frage wurde gelöscht.');
        Response::redirect('/backend/fragen');
    }

    public function updateSequence(array $params = []): void
    {
        if (!Csrf::validate()) {
            Response::json(['error' => 'CSRF'], 403);
        }

        $request = new Request();
        $ids     = $request->post('ids', []);

        if (!is_array($ids)) {
            Response::json(['error' => 'Ungültige Daten'], 400);
        }

        Question::updateSequence($ids);
        Response::json(['ok' => true]);
    }

    private function validate(Request $request): array
    {
        $errors = [];

        if (!(int)$request->post('area_id')) {
            $errors[] = 'Bereich ist Pflichtfeld.';
        }
        if (!trim($request->post('label_short', ''))) {
            $errors[] = 'Kurzform ist Pflichtfeld.';
        }
        if (!trim($request->post('label_long', ''))) {
            $errors[] = 'Langform ist Pflichtfeld.';
        }
        if (!in_array($request->post('type'), ['slider', 'freitext', 'slider_freitext'], true)) {
            $errors[] = 'Fragetyp ist Pflichtfeld.';
        }

        return $errors;
    }

    private function buildData(Request $request): array
    {
        $type = $request->post('type');
        return [
            'area_id'         => (int)$request->post('area_id'),
            'label_short'     => trim($request->post('label_short')),
            'label_long'      => trim($request->post('label_long')),
            'type'            => $type,
            'freitext_context'=> ($type !== 'slider') ? (trim($request->post('freitext_context')) ?: null) : null,
            'slider_label_min'=> ($type !== 'freitext') ? (trim($request->post('slider_label_min')) ?: null) : null,
            'slider_label_max'=> ($type !== 'freitext') ? (trim($request->post('slider_label_max')) ?: null) : null,
            'sequence'        => (int)$request->post('sequence', 0),
            'active'          => $request->post('active') ? 1 : 0,
        ];
    }
}
