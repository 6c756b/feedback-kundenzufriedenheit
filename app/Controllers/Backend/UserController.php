<?php

namespace App\Controllers\Backend;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Logger;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\User;
use App\Services\SignatureManager;

class UserController
{
    private const ROLES_FULL       = ['none', 'reader', 'staff', 'admin', 'superadmin'];
    private const ROLES_RESTRICTED = ['none', 'reader', 'staff'];
    private const ALLOWED_MIME     = ['image/png', 'image/jpeg', 'image/gif', 'image/webp'];
    private const MAX_IMAGE_BYTES  = 2 * 1024 * 1024;

    private function allowedRoles(): array
    {
        return Auth::hasRole('superadmin') ? self::ROLES_FULL : self::ROLES_RESTRICTED;
    }

    private function canEditUser(array $targetUser): bool
    {
        if (Auth::hasRole('superadmin')) {
            return true;
        }
        return in_array($targetUser['role'], self::ROLES_RESTRICTED, true);
    }

    public function index(array $params = []): void
    {
        $request  = new Request();
        $sortBy   = $request->get('sort_by', 'name');
        $sortDir  = strtoupper($request->get('sort_dir', 'ASC')) === 'DESC' ? 'DESC' : 'ASC';
        $users    = User::findAll($sortBy, $sortDir);
        $allowedRoles = $this->allowedRoles();
        $pageTitle = 'Benutzerverwaltung';
        ob_start();
        require ROOT . '/app/Views/backend/users/index.php';
        $content = ob_get_clean();
        require ROOT . '/app/Views/layout/backend.php';
    }

    public function create(array $params = []): void
    {
        $user         = [];
        $allowedRoles = $this->allowedRoles();
        $pageTitle    = 'Neuer Benutzer';
        ob_start();
        require ROOT . '/app/Views/backend/users/form.php';
        $content = ob_get_clean();
        require ROOT . '/app/Views/layout/backend.php';
    }

    public function store(array $params = []): void
    {
        if (!Csrf::validate()) {
            Response::forbidden();
        }

        $request      = new Request();
        $allowedRoles = $this->allowedRoles();
        $errors       = $this->validate($request, null, $allowedRoles);

        if ($errors) {
            Session::flash('errors', $errors);
            Session::flash('old', $_POST);
            Response::redirect('/backend/benutzer/neu');
        }

        $id = User::create([
            'name'          => trim($request->post('name')),
            'email'         => strtolower(trim($request->post('email'))),
            'role'          => $request->post('role'),
            'is_sales'      => $request->post('is_sales') ? 1 : 0,
            'is_projectlead'=> $request->post('is_projectlead') ? 1 : 0,
            'active'        => $request->post('active') ? 1 : 0,
            'display_name'  => trim($request->post('display_name', '')),
            'job_title'     => trim($request->post('job_title', '')),
            'phone'         => trim($request->post('phone', '')),
        ]);

        Logger::backend('user.created', 'user', $id, 'Benutzer angelegt');
        Session::flash('success', 'Benutzer wurde angelegt.');
        Response::redirect('/backend/benutzer');
    }

    public function edit(array $params = []): void
    {
        $user = User::findById((int)$params['id']);
        if (!$user) {
            Response::notFound();
        }

        if (!$this->canEditUser($user)) {
            Response::forbidden();
        }

        $allowedRoles      = $this->allowedRoles();
        $renderedSignature = !empty($user['display_name']) ? SignatureManager::render($user) : '';
        $pageTitle         = 'Benutzer bearbeiten';
        ob_start();
        require ROOT . '/app/Views/backend/users/form.php';
        $content = ob_get_clean();
        require ROOT . '/app/Views/layout/backend.php';
    }

    public function update(array $params = []): void
    {
        if (!Csrf::validate()) {
            Response::forbidden();
        }

        $id   = (int)$params['id'];
        $user = User::findById($id);
        if (!$user) {
            Response::notFound();
        }

        if (!$this->canEditUser($user)) {
            Response::forbidden();
        }

        $request      = new Request();
        $allowedRoles = $this->allowedRoles();
        $errors       = $this->validate($request, $id, $allowedRoles);

        if ($errors) {
            Session::flash('errors', $errors);
            Session::flash('old', $_POST);
            Response::redirect('/backend/benutzer/' . $id . '/bearbeiten');
        }

        $data = [
            'name'          => trim($request->post('name')),
            'email'         => strtolower(trim($request->post('email'))),
            'role'          => $request->post('role'),
            'is_sales'      => $request->post('is_sales') ? 1 : 0,
            'is_projectlead'=> $request->post('is_projectlead') ? 1 : 0,
            'active'        => $request->post('active') ? 1 : 0,
            'display_name'  => trim($request->post('display_name', '')),
            'job_title'     => trim($request->post('job_title', '')),
            'phone'         => trim($request->post('phone', '')),
        ];

        if (!empty($_FILES['signature_image']['tmp_name']) && $_FILES['signature_image']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['signature_image'];
            if ($file['size'] <= self::MAX_IMAGE_BYTES) {
                $mime = mime_content_type($file['tmp_name']);
                if (in_array($mime, self::ALLOWED_MIME, true)) {
                    $data['signature_image'] = 'data:' . $mime . ';base64,' . base64_encode((string) file_get_contents($file['tmp_name']));
                }
            }
        } elseif (!empty($_POST['clear_image'])) {
            $data['signature_image'] = '';
        }

        User::update($id, $data);

        Logger::backend('user.updated', 'user', $id, 'Benutzer aktualisiert');
        Session::flash('success', 'Benutzer wurde aktualisiert.');
        Response::redirect('/backend/benutzer');
    }

    private function validate(Request $request, ?int $editId, array $allowedRoles): array
    {
        $errors = [];

        if (!trim($request->post('name', ''))) {
            $errors[] = 'Name ist Pflichtfeld.';
        }

        $email = strtolower(trim($request->post('email', '')));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Gültige E-Mail-Adresse erforderlich.';
        } else {
            $existing = User::findByEmail($email);
            if ($existing && (int)$existing['id'] !== $editId) {
                $errors[] = 'Diese E-Mail-Adresse ist bereits vergeben.';
            }
        }

        if (!in_array($request->post('role'), $allowedRoles, true)) {
            $errors[] = 'Ungültige Rolle.';
        }

        return $errors;
    }
}
