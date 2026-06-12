<?php

declare(strict_types=1);

define('ROOT', dirname(__DIR__));

// Autoloader (PSR-4: App\ → app/)
spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    $base   = ROOT . '/app/';

    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $file     = $base . str_replace('\\', '/', $relative) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

// Konfiguration laden
$config = require ROOT . '/config.php';

// Session starten
\App\Core\Session::start($config['app']['session_lifetime'] ?? 480);

// Globale Hilfsfunktionen
function icon(string $name): string {
    static $icons = [
        'eye'      => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>',
        'pencil'   => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>',
        'trash'    => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>',
        'document' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>',
        'check'    => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>',
        'x'        => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>',
        'archive'  => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>',
        'stop'     => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M9 10h6v4H9z"/></svg>',
        'info'     => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
        'eye-off'     => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>',
        'paperplane'  => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" fill="currentColor"><path d="M476 3.2L12.5 270.6c-18.1 10.4-15.8 35.6 2.2 43.2L121 358.4l287.3-253.2c5.5-4.9 13.3 2.6 8.6 8.3L176 407v80.5c0 23.6 28.5 32.1 42.5 15.8L282 426l124.6 52.2c14.2 6.3 30.4-2.5 33.4-18.2l72-432C515 7.8 493.3-6.8 476 3.2z"/></svg>',
    ];
    return $icons[$name] ?? '';
}

// Router
$router = new \App\Core\Router();

// ── Frontend (öffentlich) ────────────────────────────────────
$router->get('/',                                   [\App\Controllers\Frontend\SurveyController::class, 'codeInput']);
$router->post('/umfrage/enter',                     [\App\Controllers\Frontend\SurveyController::class, 'enter']);
$router->get('/umfrage/{code}',                     [\App\Controllers\Frontend\SurveyController::class, 'show']);
$router->get('/danke',                              [\App\Controllers\Frontend\SurveyController::class, 'thankYou']);
$router->post('/api/survey/{code}/answer',          [\App\Controllers\Frontend\SurveyController::class, 'saveAnswer']);
$router->post('/api/survey/{code}/complete',        [\App\Controllers\Frontend\SurveyController::class, 'complete']);
$router->post('/api/survey/{code}/reference',       [\App\Controllers\Frontend\SurveyController::class, 'saveReference']);

// ── Backend Auth ─────────────────────────────────────────────
$router->get('/backend/login',                      [\App\Controllers\Backend\AuthController::class, 'showLogin']);
$router->post('/backend/login',                     [\App\Controllers\Backend\AuthController::class, 'login']);
$router->post('/backend/logout',                    [\App\Controllers\Backend\AuthController::class, 'logout']);

// ── Backend Profil ───────────────────────────────────────────
$router->get('/backend/profil',                     [\App\Controllers\Backend\ProfileController::class, 'show'])
       ->middleware(['auth']);
$router->post('/backend/profil',                    [\App\Controllers\Backend\ProfileController::class, 'update'])
       ->middleware(['auth']);

// ── Backend Dashboard ────────────────────────────────────────
$router->get('/backend',                            [\App\Controllers\Backend\DashboardController::class, 'index'])
       ->middleware(['auth', 'role:reader']);

// ── Backend Befragungen ──────────────────────────────────────
$router->get('/backend/befragungen',                [\App\Controllers\Backend\SurveyController::class, 'index'])
       ->middleware(['auth', 'role:reader']);
$router->get('/backend/befragungen/neu',            [\App\Controllers\Backend\SurveyController::class, 'create'])
       ->middleware(['auth', 'role:staff']);
$router->post('/backend/befragungen',               [\App\Controllers\Backend\SurveyController::class, 'store'])
       ->middleware(['auth', 'role:staff']);
$router->get('/backend/befragungen/import',              [\App\Controllers\Backend\SurveyController::class, 'importForm'])
       ->middleware(['auth', 'role:staff']);
$router->get('/backend/befragungen/import/vorlage',      [\App\Controllers\Backend\SurveyController::class, 'importTemplate'])
       ->middleware(['auth', 'role:staff']);
$router->post('/backend/befragungen/import/vorschau',    [\App\Controllers\Backend\SurveyController::class, 'importPreview'])
       ->middleware(['auth', 'role:staff']);
$router->post('/backend/befragungen/import/bestaetigen', [\App\Controllers\Backend\SurveyController::class, 'importConfirm'])
       ->middleware(['auth', 'role:staff']);
$router->get('/backend/befragungen/{id}',           [\App\Controllers\Backend\SurveyController::class, 'show'])
       ->middleware(['auth', 'role:reader']);
$router->get('/backend/befragungen/{id}/bearbeiten',[\App\Controllers\Backend\SurveyController::class, 'edit'])
       ->middleware(['auth', 'role:staff']);
$router->post('/backend/befragungen/{id}',          [\App\Controllers\Backend\SurveyController::class, 'update'])
       ->middleware(['auth', 'role:staff']);
$router->post('/backend/befragungen/{id}/loeschen', [\App\Controllers\Backend\SurveyController::class, 'destroy'])
       ->middleware(['auth', 'role:superadmin']);
$router->post('/backend/befragungen/{id}/abbrechen',[\App\Controllers\Backend\SurveyController::class, 'cancel'])
       ->middleware(['auth', 'role:staff']);
$router->get('/backend/befragungen/{id}/outlook',         [\App\Controllers\Backend\SurveyController::class, 'outlookText'])
       ->middleware(['auth', 'role:staff']);
$router->post('/backend/befragungen/{id}/email-senden',   [\App\Controllers\Backend\SurveyController::class, 'sendEmail'])
       ->middleware(['auth', 'role:staff']);
$router->post('/backend/befragungen/{id}/email-bestaetigen', [\App\Controllers\Backend\SurveyController::class, 'confirmOutlookEmail'])
       ->middleware(['auth', 'role:staff']);

// ── Backend Fragen ───────────────────────────────────────────
$router->get('/backend/fragen',                     [\App\Controllers\Backend\QuestionController::class, 'index'])
       ->middleware(['auth', 'role:admin']);
$router->get('/backend/fragen/neu',                 [\App\Controllers\Backend\QuestionController::class, 'create'])
       ->middleware(['auth', 'role:admin']);
$router->post('/backend/fragen',                    [\App\Controllers\Backend\QuestionController::class, 'store'])
       ->middleware(['auth', 'role:admin']);
$router->get('/backend/fragen/{id}/bearbeiten',     [\App\Controllers\Backend\QuestionController::class, 'edit'])
       ->middleware(['auth', 'role:admin']);
$router->post('/backend/fragen/{id}',               [\App\Controllers\Backend\QuestionController::class, 'update'])
       ->middleware(['auth', 'role:admin']);
$router->post('/backend/fragen/{id}/loeschen',      [\App\Controllers\Backend\QuestionController::class, 'destroy'])
       ->middleware(['auth', 'role:admin']);
$router->post('/backend/fragen/sequenz',            [\App\Controllers\Backend\QuestionController::class, 'updateSequence'])
       ->middleware(['auth', 'role:admin']);

// ── Backend Auswertung ───────────────────────────────────────
$router->get('/backend/auswertung',                 [\App\Controllers\Backend\EvaluationController::class, 'index'])
       ->middleware(['auth', 'role:reader']);
$router->get('/backend/archiv',                     [\App\Controllers\Backend\EvaluationController::class, 'archiveIndex'])
       ->middleware(['auth', 'role:reader']);
$router->get('/backend/auswertung/{id}',            [\App\Controllers\Backend\EvaluationController::class, 'detail'])
       ->middleware(['auth', 'role:reader']);
$router->get('/backend/auswertung/{id}/pdf',        [\App\Controllers\Backend\EvaluationController::class, 'exportPdf'])
       ->middleware(['auth', 'role:reader']);
$router->post('/backend/auswertung/{id}/gelesen',   [\App\Controllers\Backend\EvaluationController::class, 'markRead'])
       ->middleware(['auth', 'role:staff']);
$router->post('/backend/auswertung/{id}/ungelesen', [\App\Controllers\Backend\EvaluationController::class, 'markUnread'])
       ->middleware(['auth', 'role:staff']);
$router->post('/backend/auswertung/{id}/archivieren',[\App\Controllers\Backend\EvaluationController::class, 'archive'])
       ->middleware(['auth', 'role:staff']);

// ── Backend Benutzer (admin) ────────────────────────────
$router->get('/backend/benutzer',                   [\App\Controllers\Backend\UserController::class, 'index'])
       ->middleware(['auth', 'role:admin']);
$router->get('/backend/benutzer/neu',               [\App\Controllers\Backend\UserController::class, 'create'])
       ->middleware(['auth', 'role:admin']);
$router->post('/backend/benutzer',                  [\App\Controllers\Backend\UserController::class, 'store'])
       ->middleware(['auth', 'role:admin']);
$router->get('/backend/benutzer/{id}/bearbeiten',   [\App\Controllers\Backend\UserController::class, 'edit'])
       ->middleware(['auth', 'role:admin']);
$router->post('/backend/benutzer/{id}',             [\App\Controllers\Backend\UserController::class, 'update'])
       ->middleware(['auth', 'role:admin']);

// ── Backend Bereiche (admin) ─────────────────────────────────
$router->get('/backend/bereiche',                   [\App\Controllers\Backend\AreaController::class, 'index'])
       ->middleware(['auth', 'role:admin']);
$router->get('/backend/bereiche/neu',               [\App\Controllers\Backend\AreaController::class, 'create'])
       ->middleware(['auth', 'role:admin']);
$router->post('/backend/bereiche',                  [\App\Controllers\Backend\AreaController::class, 'store'])
       ->middleware(['auth', 'role:admin']);
$router->get('/backend/bereiche/{id}/bearbeiten',   [\App\Controllers\Backend\AreaController::class, 'edit'])
       ->middleware(['auth', 'role:admin']);
$router->post('/backend/bereiche/{id}',             [\App\Controllers\Backend\AreaController::class, 'update'])
       ->middleware(['auth', 'role:admin']);

// ── Backend Metropolregionen (admin) ────────────────────
$router->get('/backend/metropolregionen',                   [\App\Controllers\Backend\MetropolregionController::class, 'index'])
       ->middleware(['auth', 'role:admin']);
$router->get('/backend/metropolregionen/neu',               [\App\Controllers\Backend\MetropolregionController::class, 'create'])
       ->middleware(['auth', 'role:admin']);
$router->post('/backend/metropolregionen',                  [\App\Controllers\Backend\MetropolregionController::class, 'store'])
       ->middleware(['auth', 'role:admin']);
$router->get('/backend/metropolregionen/{id}/bearbeiten',   [\App\Controllers\Backend\MetropolregionController::class, 'edit'])
       ->middleware(['auth', 'role:admin']);
$router->post('/backend/metropolregionen/{id}',             [\App\Controllers\Backend\MetropolregionController::class, 'update'])
       ->middleware(['auth', 'role:admin']);
$router->post('/backend/metropolregionen/{id}/loeschen',    [\App\Controllers\Backend\MetropolregionController::class, 'destroy'])
       ->middleware(['auth', 'role:admin']);

// ── Backend Logs (superadmin) ────────────────────────────────
$router->get('/backend/logs',                       [\App\Controllers\Backend\LogController::class, 'index'])
       ->middleware(['auth', 'role:superadmin']);

$router->dispatch();
