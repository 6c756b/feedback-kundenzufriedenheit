<?php

namespace App\Controllers\Backend;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Response;
use App\Core\Session;
use App\Models\ApiKey;

class ApiKeyController
{
    public function index(array $params = []): void
    {
        $keys      = ApiKey::findAll();
        $pageTitle = 'API Keys';

        ob_start();
        require ROOT . '/app/Views/backend/apikeys/index.php';
        $content = ob_get_clean();
        require ROOT . '/app/Views/layout/backend.php';
    }

    public function store(array $params = []): void
    {
        if (!Csrf::validate()) {
            Response::forbidden();
        }

        $name = trim($_POST['name'] ?? '');
        if ($name === '') {
            Session::flash('errors', ['Name ist erforderlich.']);
            Response::redirect('/backend/api-keys');
        }

        $canRead   = isset($_POST['can_read'])  ? 1 : 0;
        $canWrite  = isset($_POST['can_write']) ? 1 : 0;
        $expiresAt = trim($_POST['expires_at'] ?? '') ?: null;

        if ($canRead === 0 && $canWrite === 0) {
            Session::flash('errors', ['Mindestens eine Berechtigung (Lesen oder Schreiben) muss aktiviert sein.']);
            Response::redirect('/backend/api-keys');
        }

        $rawKey  = 'fbk_' . bin2hex(random_bytes(32));
        $keyHash = hash('sha256', $rawKey);
        $userId  = (int)Auth::user()['id'];

        ApiKey::create($name, $keyHash, $userId, $canRead, $canWrite, $expiresAt);

        Session::flash('api_key_created', $rawKey);
        Response::redirect('/backend/api-keys');
    }

    public function destroy(array $params = []): void
    {
        if (!Csrf::validate()) {
            Response::forbidden();
        }

        $key = ApiKey::findById((int)$params['id']);
        if (!$key) {
            Response::notFound();
        }

        ApiKey::delete((int)$params['id']);

        Session::flash('success', 'API Key wurde gelöscht.');
        Response::redirect('/backend/api-keys');
    }
}
