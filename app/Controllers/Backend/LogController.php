<?php

namespace App\Controllers\Backend;

use App\Core\Request;
use App\Models\Log;

class LogController
{
    public function index(array $params = []): void
    {
        $request = new Request();
        $page    = max(1, (int)$request->get('page', 1));

        $filters = [
            'actor_type' => $request->get('actor_type'),
            'action'     => $request->get('action'),
            'date_from'  => $request->get('date_from'),
            'date_to'    => $request->get('date_to'),
        ];

        $logs       = Log::findAll($filters, $page);
        $totalCount = Log::countAll($filters);
        $totalPages = (int) ceil($totalCount / 50);

        $pageTitle = 'Protokoll';
        ob_start();
        require ROOT . '/app/Views/backend/logs/index.php';
        $content = ob_get_clean();
        require ROOT . '/app/Views/layout/backend.php';
    }
}
