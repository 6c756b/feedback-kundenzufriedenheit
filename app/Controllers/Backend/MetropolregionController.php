<?php

namespace App\Controllers\Backend;

use App\Core\Csrf;
use App\Core\Logger;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\Metropolregion;

class MetropolregionController
{
    private function buildPanel(int $currentId = 0): string
    {
        $panelRegions   = Metropolregion::findAll();
        $panelCurrentId = $currentId;
        ob_start();
        try {
            require ROOT . '/app/Views/backend/metropolregionen/_panel.php';
        } finally {
            return ob_get_clean() ?: '';
        }
    }

    public function index(array $params = []): void
    {
        $regions      = Metropolregion::findAll();
        $panelContent = $this->buildPanel();
        $pageTitle    = 'Metropolregionen';
        ob_start();
        require ROOT . '/app/Views/backend/metropolregionen/index.php';
        $content = ob_get_clean();
        require ROOT . '/app/Views/layout/backend.php';
    }

    public function create(array $params = []): void
    {
        $region       = [];
        $panelContent = $this->buildPanel();
        $pageTitle    = 'Neue Metropolregion';
        ob_start();
        require ROOT . '/app/Views/backend/metropolregionen/form.php';
        $content = ob_get_clean();
        require ROOT . '/app/Views/layout/backend.php';
    }

    public function edit(array $params = []): void
    {
        $region = Metropolregion::findById((int)$params['id']);
        if (!$region) {
            Response::notFound();
        }

        $panelContent = $this->buildPanel((int)$params['id']);
        $pageTitle    = 'Metropolregion bearbeiten';
        ob_start();
        require ROOT . '/app/Views/backend/metropolregionen/form.php';
        $content = ob_get_clean();
        require ROOT . '/app/Views/layout/backend.php';
    }

    public function updateOrder(array $params = []): void
    {
        if (!Csrf::validate()) {
            Response::json(['error' => 'CSRF'], 403);
        }
        $request = new Request();
        $ids     = $request->post('ids', []);
        if (!is_array($ids)) {
            Response::json(['error' => 'Ungültige Daten'], 400);
        }
        Metropolregion::updateOrder($ids);
        Response::json(['ok' => true]);
    }

    public function store(array $params = []): void
    {
        if (!Csrf::validate()) {
            Response::forbidden();
        }

        $request = new Request();
        $name    = trim($request->post('name', ''));

        if (!$name) {
            Session::flash('errors', ['Name ist Pflichtfeld.']);
            Session::flash('old', $_POST);
            Response::redirect('/backend/metropolregionen/neu');
        }

        $id = Metropolregion::create([
            'name'       => $name,
            'sort_order' => (int)$request->post('sort_order', 0),
        ]);

        Logger::backend('metropolregion.created', 'metropolregion', $id, 'Metropolregion angelegt: ' . $name);
        Session::flash('success', 'Metropolregion wurde angelegt.');
        Response::redirect('/backend/metropolregionen/' . $id . '/bearbeiten');
    }

    public function update(array $params = []): void
    {
        if (!Csrf::validate()) {
            Response::forbidden();
        }

        $id     = (int)$params['id'];
        $region = Metropolregion::findById($id);
        if (!$region) {
            Response::notFound();
        }

        $request = new Request();
        $name    = trim($request->post('name', ''));

        if (!$name) {
            Session::flash('errors', ['Name ist Pflichtfeld.']);
            Session::flash('old', $_POST);
            Response::redirect('/backend/metropolregionen/' . $id . '/bearbeiten');
        }

        Metropolregion::update($id, [
            'name'       => $name,
            'sort_order' => (int)$request->post('sort_order', 0),
        ]);

        Logger::backend('metropolregion.updated', 'metropolregion', $id, 'Metropolregion aktualisiert: ' . $region['name']);
        Session::flash('success', 'Metropolregion wurde aktualisiert.');
        Response::redirect('/backend/metropolregionen/' . $id . '/bearbeiten');
    }

    public function destroy(array $params = []): void
    {
        if (!Csrf::validate()) {
            Response::forbidden();
        }

        $id     = (int)$params['id'];
        $region = Metropolregion::findById($id);
        if (!$region) {
            Response::notFound();
        }

        Metropolregion::delete($id);
        Logger::backend('metropolregion.deleted', 'metropolregion', $id, 'Metropolregion gelöscht: ' . $region['name']);
        Session::flash('success', 'Metropolregion wurde gelöscht.');
        Response::redirect('/backend/metropolregionen');
    }
}
