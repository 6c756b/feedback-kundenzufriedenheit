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

class ProfileController
{
    private const ALLOWED_MIME    = ['image/png', 'image/jpeg', 'image/gif', 'image/webp'];
    private const MAX_IMAGE_BYTES = 2 * 1024 * 1024;

    public function show(array $params = []): void
    {
        $authUser = Auth::user();
        $user     = User::findById((int)$authUser['id']);
        if (!$user) {
            Response::notFound();
        }

        $renderedSignature = !empty($user['display_name']) ? SignatureManager::render($user) : '';
        $pageTitle         = 'Mein Profil';
        ob_start();
        require ROOT . '/app/Views/backend/profile/edit.php';
        $content = ob_get_clean();
        require ROOT . '/app/Views/layout/backend.php';
    }

    public function update(array $params = []): void
    {
        if (!Csrf::validate()) {
            Response::forbidden();
        }

        $authUser = Auth::user();
        $id       = (int)$authUser['id'];
        $user     = User::findById($id);
        if (!$user) {
            Response::notFound();
        }

        $request = new Request();
        $errors  = $this->validate($request);

        if ($errors) {
            Session::flash('errors', $errors);
            Session::flash('old', $_POST);
            Response::redirect('/backend/profil');
        }

        $data = [
            'name'         => trim($request->post('name')),
            'display_name' => trim($request->post('display_name', '')),
            'job_title'    => trim($request->post('job_title', '')),
            'phone'        => trim($request->post('phone', '')),
        ];

        if (!empty($_FILES['signature_image']['tmp_name']) && $_FILES['signature_image']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['signature_image'];
            if ($file['size'] <= self::MAX_IMAGE_BYTES) {
                $imageInfo = @getimagesize($file['tmp_name']);
                $mime = $imageInfo ? $imageInfo['mime'] : '';
                if (in_array($mime, self::ALLOWED_MIME, true)) {
                    $data['signature_image'] = 'data:' . $mime . ';base64,' . base64_encode((string) file_get_contents($file['tmp_name']));
                }
            }
        } elseif (!empty($_POST['clear_image'])) {
            $data['signature_image'] = '';
        }

        $localPw = $request->post('local_password', '');
        if ($localPw !== '') {
            $data['password_hash'] = password_hash($localPw, PASSWORD_DEFAULT);
        } elseif (!empty($request->post('clear_local_password'))) {
            $data['password_hash'] = null;
        }

        User::update($id, $data);

        // Session-Name aktualisieren
        $sessionData        = Auth::user();
        $sessionData['name'] = $data['name'];
        Session::set('auth_user', $sessionData);

        Logger::backend('profile.updated', 'user', $id, 'Eigenes Profil aktualisiert');
        Session::flash('success', 'Profil gespeichert.');
        Response::redirect('/backend/profil');
    }

    private function validate(Request $request): array
    {
        $errors = [];

        if (!trim($request->post('name', ''))) {
            $errors[] = 'Name ist Pflichtfeld.';
        }

        $localPw = $request->post('local_password', '');
        if ($localPw !== '' && strlen($localPw) < 8) {
            $errors[] = 'Passwort: mindestens 8 Zeichen erforderlich.';
        }

        return $errors;
    }
}
