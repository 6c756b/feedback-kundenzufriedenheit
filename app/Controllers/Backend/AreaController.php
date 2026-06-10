<?php

namespace App\Controllers\Backend;

use App\Core\Csrf;
use App\Core\Logger;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\Area;

class AreaController
{
    public function index(array $params = []): void
    {
        $areas     = Area::findAllWithStats();
        $pageTitle = 'Bereiche';
        ob_start();
        require ROOT . '/app/Views/backend/areas/index.php';
        $content = ob_get_clean();
        require ROOT . '/app/Views/layout/backend.php';
    }

    public function create(array $params = []): void
    {
        $area      = [];
        $pageTitle = 'Neuer Bereich';
        ob_start();
        require ROOT . '/app/Views/backend/areas/form.php';
        $content = ob_get_clean();
        require ROOT . '/app/Views/layout/backend.php';
    }

    public function edit(array $params = []): void
    {
        $area = Area::findById((int)$params['id']);
        if (!$area) {
            Response::notFound();
        }

        $pageTitle = 'Bereich bearbeiten';
        ob_start();
        require ROOT . '/app/Views/backend/areas/form.php';
        $content = ob_get_clean();
        require ROOT . '/app/Views/layout/backend.php';
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
            Response::redirect('/backend/bereiche/neu');
        }

        $id = Area::create([
            'name'        => $name,
            'description' => trim($request->post('description')) ?: null,
            'sort_order'  => (int)$request->post('sort_order', 0),
            'active'      => $request->post('active') ? 1 : 0,
        ]);

        Logger::backend('area.created', 'area', $id, 'Bereich angelegt: ' . $name);
        Session::flash('success', 'Bereich wurde angelegt.');
        Response::redirect('/backend/bereiche');
    }

    public function update(array $params = []): void
    {
        if (!Csrf::validate()) {
            Response::forbidden();
        }

        $id   = (int)$params['id'];
        $area = Area::findById($id);
        if (!$area) {
            Response::notFound();
        }

        $request = new Request();
        $name    = trim($request->post('name', ''));

        if (!$name) {
            Session::flash('errors', ['Name ist Pflichtfeld.']);
            Session::flash('old', $_POST);
            Response::redirect('/backend/bereiche/' . $id . '/bearbeiten');
        }

        $data = [
            'name'        => $name,
            'description' => trim($request->post('description')) ?: null,
            'sort_order'  => (int)$request->post('sort_order', 0),
            'active'      => $request->post('active') ? 1 : 0,
        ];

        Area::update($id, $data);
        Logger::backend('area.updated', 'area', $id, 'Bereich aktualisiert: ' . $area['name']);
        Session::flash('success', 'Bereich wurde aktualisiert.');
        Response::redirect('/backend/bereiche');
    }
}
