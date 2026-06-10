<?php

namespace App\Controllers\Backend;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Logger;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\Area;
use App\Models\Metropolregion;
use App\Models\Survey;
use App\Models\User;
use App\Services\CodeGenerator;
use App\Services\Mailer;
use App\Services\OutlookText;
use App\Services\SignatureManager;

class SurveyController
{
    public function index(array $params = []): void
    {
        $request = new Request();
        $page    = max(1, (int)$request->get('page', 1));

        // Kein Status-Parameter → alle (open+started); leerer Wert → ebenfalls alle
        $statusFilter = array_key_exists('status', $_GET) ? $request->get('status') : null;

        if ($statusFilter === 'open' || $statusFilter === 'started') {
            $filters = [
                'area_id'           => $request->get('area_id'),
                'status'            => $statusFilter,
                'customer_name'     => $request->get('customer_name'),
                'created_from'      => $request->get('created_from'),
                'created_to'        => $request->get('created_to'),
                'created_by'        => $request->get('created_by'),
                'sales_user_id'     => $request->get('sales_user_id'),
                'project_lead_id'   => $request->get('project_lead_id'),
                'metropolregion_id' => $request->get('metropolregion_id'),
            ];
        } else {
            $filters = [
                'area_id'           => $request->get('area_id'),
                'status_in'         => ['open', 'started'],
                'customer_name'     => $request->get('customer_name'),
                'created_from'      => $request->get('created_from'),
                'created_to'        => $request->get('created_to'),
                'created_by'        => $request->get('created_by'),
                'sales_user_id'     => $request->get('sales_user_id'),
                'project_lead_id'   => $request->get('project_lead_id'),
                'metropolregion_id' => $request->get('metropolregion_id'),
            ];
        }
        $filters['status_filter_raw'] = $statusFilter;
        $filters['sort_by']           = $request->get('sort_by', 'erstellt');
        $filters['sort_dir']          = $request->get('sort_dir', 'DESC');

        $surveys          = Survey::listFiltered($filters, $page);
        $totalCount       = Survey::countFiltered($filters);
        $totalPages       = (int) ceil($totalCount / 25);
        $areas            = Area::findAllActive();
        $users            = User::findAll();
        $salesUsers       = User::findSales();
        $projectLeadUsers = User::findProjectLeads();
        $metropolregionen = Metropolregion::findAll();
        $customerNames    = Survey::distinctCustomerNames();

        if (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest') {
            ob_start();
            require ROOT . '/app/Views/backend/surveys/_list.php';
            echo ob_get_clean();
            exit;
        }

        $pageTitle = 'Befragungen';
        ob_start();
        require ROOT . '/app/Views/backend/surveys/index.php';
        $content = ob_get_clean();
        require ROOT . '/app/Views/layout/backend.php';
    }

    public function create(array $params = []): void
    {
        $areas            = Area::findAllActive();
        $salesUsers       = User::findSales();
        $projectLeadUsers = User::findProjectLeads();
        $metropolregionen = Metropolregion::findAll();
        $survey           = [];

        $pageTitle = 'Neue Befragung';
        ob_start();
        require ROOT . '/app/Views/backend/surveys/form.php';
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
            Response::redirect('/backend/befragungen/neu');
        }

        $areaIds   = array_filter(array_map('intval', (array)($_POST['area_ids'] ?? [])));
        $firstArea = !empty($areaIds) ? (int)reset($areaIds) : 0;
        $code      = CodeGenerator::generate();
        $user      = Auth::user();

        try {
            $id = Survey::create([
                'code'                => $code,
                'customer_name'       => trim($request->post('customer_name')),
                'project_id'          => trim($request->post('project_id')) ?: null,
                'contact_salutation'  => $request->post('contact_salutation') ?: '',
                'contact_person'      => trim($request->post('contact_person')),
                'contact_email'       => trim($request->post('contact_email')),
                'project_name'        => trim($request->post('project_name')),
                'area_id'             => $firstArea,
                'sales_user_id'       => (int)$request->post('sales_user_id') ?: null,
                'project_lead_id'     => (int)$request->post('project_lead_id') ?: null,
                'metropolregion_id'   => (int)$request->post('metropolregion_id') ?: null,
                'internal_notes'      => trim($request->post('internal_notes')) ?: null,
                'reference_requested' => $request->post('reference_requested') ? 1 : 0,
                'status'              => 'open',
                'created_by'          => $user['id'],
            ]);
        } catch (\PDOException $e) {
            if ($e->getCode() === '23000') {
                Session::flash('errors', ['Code-Kollision beim Anlegen. Bitte erneut versuchen.']);
                Response::redirect('/backend/befragungen/neu');
            }
            throw $e;
        }

        Survey::syncAreas($id, $areaIds);

        Logger::backend('survey.created', 'survey', $id, 'Befragung angelegt: ' . $code);
        Session::flash('success', 'Befragung wurde angelegt. Code: ' . $code);
        Response::redirect('/backend/befragungen/' . $id);
    }

    public function show(array $params = []): void
    {
        $survey = Survey::findById((int)$params['id']);
        if (!$survey) {
            Response::notFound();
        }

        $survey['area_ids']   = Survey::getAreaIds((int)$survey['id']);
        $salesUsers           = User::findSales();
        $needsQuill           = true;
        $allSignatures = [];
        foreach (User::findAll() as $u) {
            if (empty($u['active']) || empty($u['display_name'])) {
                continue;
            }
            $allSignatures[] = [
                'slug'    => SignatureManager::emailToSlug($u['email']),
                'email'   => $u['email'],
                'content' => SignatureManager::render($u),
            ];
        }
        $userEmail         = Auth::user()['email'] ?? '';
        $userSignatureSlug = SignatureManager::forEmail($userEmail);

        $pageTitle = 'Befragung - ' . $survey['customer_name'];
        ob_start();
        require ROOT . '/app/Views/backend/surveys/show.php';
        $content = ob_get_clean();
        require ROOT . '/app/Views/layout/backend.php';
    }

    public function edit(array $params = []): void
    {
        $survey = Survey::findById((int)$params['id']);
        if (!$survey) {
            Response::notFound();
        }

        $survey['area_ids'] = Survey::getAreaIds((int)$survey['id']);

        $areas            = Area::findAllActive();
        $salesUsers       = User::findSales();
        $projectLeadUsers = User::findProjectLeads();
        $metropolregionen = Metropolregion::findAll();
        $pageTitle        = 'Befragung bearbeiten';
        ob_start();
        require ROOT . '/app/Views/backend/surveys/form.php';
        $content = ob_get_clean();
        require ROOT . '/app/Views/layout/backend.php';
    }

    public function update(array $params = []): void
    {
        if (!Csrf::validate()) {
            Response::forbidden();
        }

        $id      = (int)$params['id'];
        $survey  = Survey::findById($id);
        if (!$survey) {
            Response::notFound();
        }

        $request = new Request();
        $errors  = $this->validate($request);

        if ($errors) {
            Session::flash('errors', $errors);
            Session::flash('old', $_POST);
            Response::redirect('/backend/befragungen/' . $id . '/bearbeiten');
        }

        $areaIds   = array_filter(array_map('intval', (array)($_POST['area_ids'] ?? [])));
        $firstArea = !empty($areaIds) ? (int)reset($areaIds) : (int)$survey['area_id'];

        Survey::update($id, [
            'customer_name'       => trim($request->post('customer_name')),
            'project_id'          => trim($request->post('project_id')) ?: null,
            'contact_salutation'  => $request->post('contact_salutation') ?: '',
            'contact_person'      => trim($request->post('contact_person')),
            'contact_email'       => trim($request->post('contact_email')),
            'project_name'        => trim($request->post('project_name')),
            'area_id'             => $firstArea,
            'sales_user_id'       => (int)$request->post('sales_user_id') ?: null,
            'project_lead_id'     => (int)$request->post('project_lead_id') ?: null,
            'metropolregion_id'   => (int)$request->post('metropolregion_id') ?: null,
            'internal_notes'      => trim($request->post('internal_notes')) ?: null,
            'reference_requested' => $request->post('reference_requested') ? 1 : 0,
        ]);

        Survey::syncAreas($id, $areaIds);

        Logger::backend('survey.updated', 'survey', $id, 'Befragung aktualisiert');
        Session::flash('success', 'Befragung wurde aktualisiert.');
        Response::redirect('/backend/befragungen/' . $id);
    }

    public function destroy(array $params = []): void
    {
        if (!Csrf::validate()) {
            Response::forbidden();
        }

        $id     = (int)$params['id'];
        $survey = Survey::findById($id);
        if (!$survey) {
            Response::notFound();
        }

        Survey::delete($id);
        Logger::backend('survey.deleted', 'survey', $id, 'Befragung gelöscht: ' . $survey['code']);
        Session::flash('success', 'Befragung wurde gelöscht.');
        Response::redirect('/backend/befragungen');
    }

    public function cancel(array $params = []): void
    {
        if (!Csrf::validate()) {
            Response::forbidden();
        }

        $id     = (int)$params['id'];
        $survey = Survey::findById($id);
        if (!$survey) {
            Response::notFound();
        }

        Survey::cancel($id);
        Logger::backend('survey.cancelled', 'survey', $id, 'Befragung abgebrochen: ' . $survey['code']);
        Session::flash('success', 'Befragung wurde abgebrochen.');
        Response::redirect('/backend/befragungen/' . $id);
    }

    public function sendEmail(array $params = []): void
    {
        if (!Csrf::validate()) {
            Response::forbidden();
        }

        $id     = (int)$params['id'];
        $survey = Survey::findById($id);
        if (!$survey) {
            Response::notFound();
        }

        $config    = require ROOT . '/config.php';
        $smtp      = $config['smtp'] ?? [];
        $user      = Auth::user();
        $request   = new Request();
        $fromEmail = !empty($user['email']) ? $user['email'] : ($smtp['from_fallback'] ?? '');
        $fromName  = !empty($user['name'])  ? $user['name']  : ($smtp['from_name']     ?? '');
        $subject  = trim($request->post('subject', '')) ?: OutlookText::subject($survey);
        $htmlBody = trim($request->post('body', ''))    ?: OutlookText::htmlBody($survey, $fromName);

        $signatureSlug = trim($request->post('signature_slug', ''));
        if ($signatureSlug !== '' && SignatureManager::slugIsValid($signatureSlug)) {
            $sigEmail = SignatureManager::slugToEmail($signatureSlug);
            $sigUser  = User::findByEmail($sigEmail);
            if ($sigUser && !empty($sigUser['display_name'])) {
                $htmlBody .= SignatureManager::render($sigUser);
            }
        }

        $sent = Mailer::send(
            $survey['contact_email'],
            $survey['contact_person'],
            $subject,
            $htmlBody,
            $fromEmail,
            $fromName
        );

        if (!$sent) {
            Session::flash('error', 'E-Mail konnte nicht gesendet werden. Bitte SMTP-Konfiguration prüfen.');
            Response::redirect('/backend/befragungen/' . $id);
        }

        Survey::markEmailSent($id, (int)$user['id'], 'system');
        Logger::backend('survey.email_sent', 'survey', $id, 'E-Mail über System gesendet an ' . $survey['contact_email']);
        Session::flash('success', 'E-Mail wurde erfolgreich gesendet.');
        Response::redirect('/backend/befragungen/' . $id);
    }

    public function confirmOutlookEmail(array $params = []): void
    {
        if (!Csrf::validate()) {
            Response::forbidden();
        }

        $id     = (int)$params['id'];
        $survey = Survey::findById($id);
        if (!$survey) {
            Response::notFound();
        }

        $user = Auth::user();
        Survey::markEmailSent($id, (int)$user['id'], 'outlook');
        Logger::backend('survey.email_confirmed', 'survey', $id, 'Outlook-E-Mail bestätigt');
        Session::flash('success', 'Outlook-Versand wurde bestätigt.');
        Response::redirect('/backend/befragungen/' . $id);
    }

    public function outlookText(array $params = []): void
    {
        $survey = Survey::findById((int)$params['id']);
        if (!$survey) {
            Response::json(['error' => 'Nicht gefunden'], 404);
        }

        $config    = require ROOT . '/config.php';
        $smtp      = $config['smtp'] ?? [];
        $user      = Auth::user();
        $fromName  = !empty($user['name'])  ? $user['name']  : ($smtp['from_name']     ?? '');
        $fromEmail = !empty($user['email']) ? $user['email'] : ($smtp['from_fallback'] ?? '');

        Response::json([
            'subject' => OutlookText::subject($survey),
            'from'    => trim("{$fromName} <{$fromEmail}>"),
            'to'      => trim($survey['contact_person'] . ' <' . $survey['contact_email'] . '>'),
            'body'    => OutlookText::htmlBody($survey, $fromName),
        ]);
    }

    public function importForm(array $params = []): void
    {
        $pageTitle = 'Befragungen importieren';
        ob_start();
        require ROOT . '/app/Views/backend/surveys/import.php';
        $content = ob_get_clean();
        require ROOT . '/app/Views/layout/backend.php';
    }

    public function importTemplate(array $params = []): void
    {
        $areas            = Area::findAllActive();
        $salesUsers       = User::findSales();
        $projectLeadUsers = User::findProjectLeads();
        $metropolregionen = Metropolregion::findAll();

        $areaExamples = [];
        foreach ($areas as $area) {
            if ($area['id'] != 1) {
                $areaExamples[] = $area['name'];
            }
            if (count($areaExamples) >= 2) {
                break;
            }
        }
        $areaExample   = $areaExamples ? implode(',', $areaExamples) : 'Bereich A,Bereich B';
        $salesExample  = !empty($salesUsers) ? $salesUsers[0]['name'] : 'Vera Vertrieb';
        $plExample     = !empty($projectLeadUsers) ? $projectLeadUsers[0]['name'] : 'Peter Projektleiter';
        $regionExample = !empty($metropolregionen) ? $metropolregionen[0]['name'] : 'OWL';

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="befragungen-import-vorlage.csv"');
        header('Cache-Control: no-cache, no-store');

        echo "\xEF\xBB\xBF"; // UTF-8 BOM für Excel

        $out = fopen('php://output', 'w');
        fputcsv($out, ['Kunde', 'Projektname', 'Projekt-ID', 'Ansprechpartner', 'Anrede', 'Email', 'Bereiche', 'Vertrieb', 'Metropolregion', 'Projektleitung', 'Kommentar', 'Referenz'], ';', '"', '');
        fputcsv($out, ['Musterfirma GmbH', 'Testprojekt 2024', 'DEMO-VORLAGE', 'Max Mustermann', 'Herr', 'max.mustermann@example.com', $areaExample, $salesExample, $regionExample, $plExample, 'Optionaler Kommentar', 'Nein'], ';', '"', '');
        fclose($out);
        exit;
    }

    public function importPreview(array $params = []): void
    {
        if (!Csrf::validate()) {
            Response::forbidden();
        }

        if (empty($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            Session::flash('error', 'Keine gültige CSV-Datei hochgeladen.');
            Response::redirect('/backend/befragungen/import');
        }

        $areas            = Area::findAllActive();
        $salesUsers       = User::findSales();
        $projectLeadUsers = User::findProjectLeads();
        $metropolregionen = Metropolregion::findAll();

        $areaMap = [];
        foreach ($areas as $area) {
            $areaMap[mb_strtolower(trim($area['name']))] = (int)$area['id'];
        }

        $salesMap = [];
        foreach ($salesUsers as $su) {
            $salesMap[mb_strtolower(trim($su['name']))] = (int)$su['id'];
        }

        $projectLeadMap = [];
        foreach ($projectLeadUsers as $pl) {
            $projectLeadMap[mb_strtolower(trim($pl['name']))] = (int)$pl['id'];
        }

        $metropolregionMap = [];
        foreach ($metropolregionen as $mr) {
            $metropolregionMap[mb_strtolower(trim($mr['name']))] = (int)$mr['id'];
        }

        $handle = fopen($_FILES['csv_file']['tmp_name'], 'r');
        if (!$handle) {
            Session::flash('error', 'CSV konnte nicht gelesen werden.');
            Response::redirect('/backend/befragungen/import');
        }

        // UTF-8 BOM entfernen falls vorhanden
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        $header = fgetcsv($handle, 10000, ';', '"', '');
        if (!$header) {
            fclose($handle);
            Session::flash('error', 'CSV ist leer oder hat keine Kopfzeile.');
            Response::redirect('/backend/befragungen/import');
        }

        $header = array_map('trim', $header);
        $required = ['Kunde', 'Projektname', 'Projekt-ID', 'Ansprechpartner', 'Anrede', 'Email', 'Bereiche', 'Vertrieb', 'Kommentar', 'Referenz'];
        $colMap = [];
        foreach ($required as $col) {
            $idx = array_search($col, $header);
            if ($idx === false) {
                fclose($handle);
                Session::flash('error', "Spalte \"{$col}\" fehlt in der CSV-Datei. Bitte Vorlage verwenden.");
                Response::redirect('/backend/befragungen/import');
            }
            $colMap[$col] = $idx;
        }
        // Optionale neue Spalten
        foreach (['Metropolregion', 'Projektleitung'] as $opt) {
            $idx = array_search($opt, $header);
            if ($idx !== false) {
                $colMap[$opt] = $idx;
            }
        }

        $rows    = [];
        $lineNum = 1;
        while (($raw = fgetcsv($handle, 10000, ';', '"', '')) !== false) {
            $lineNum++;
            $get    = fn(string $col): string => isset($colMap[$col]) ? trim($raw[$colMap[$col]] ?? '') : '';

            // Dummy-Zeile überspringen
            if ($get('Projekt-ID') === 'DEMO-VORLAGE') {
                continue;
            }

            $salutationRaw = $get('Anrede');
            $salutationMap = ['herr' => 'herr', 'frau' => 'frau'];
            $salutation    = $salutationMap[mb_strtolower($salutationRaw)] ?? '';

            $referenzRaw      = mb_strtolower($get('Referenz'));
            $referenceRequested = in_array($referenzRaw, ['ja', 'yes', '1', 'true', 'j']) ? 1 : 0;

            $parsed = [
                'customer_name'       => $get('Kunde'),
                'project_name'        => $get('Projektname'),
                'project_id'          => $get('Projekt-ID') ?: null,
                'contact_person'      => $get('Ansprechpartner'),
                'contact_salutation'  => $salutation,
                'contact_email'       => $get('Email'),
                'areas_raw'           => $get('Bereiche'),
                'sales_raw'           => $get('Vertrieb'),
                'metropolregion_raw'  => $get('Metropolregion'),
                'project_lead_raw'    => $get('Projektleitung'),
                'internal_notes'      => $get('Kommentar') ?: null,
                'reference_requested' => $referenceRequested,
                '_line'               => $lineNum,
                '_errors'             => [],
                '_warnings'           => [],
            ];

            if (!$parsed['customer_name']) {
                $parsed['_errors'][] = 'Kunde fehlt';
            }
            if (!$parsed['project_name']) {
                $parsed['_errors'][] = 'Projektname fehlt';
            }
            if (!$parsed['contact_person']) {
                $parsed['_errors'][] = 'Ansprechpartner fehlt';
            }
            if (!filter_var($parsed['contact_email'], FILTER_VALIDATE_EMAIL)) {
                $parsed['_errors'][] = 'Ungültige E-Mail-Adresse';
            }
            if ($salutationRaw !== '' && $salutation === '') {
                $parsed['_warnings'][] = "Anrede \"{$salutationRaw}\" nicht erkannt (leer gesetzt)";
            }

            // Bereiche auflösen
            $areaIds = [];
            if ($parsed['areas_raw'] !== '') {
                foreach (array_map('trim', explode(',', $parsed['areas_raw'])) as $aName) {
                    if ($aName === '') {
                        continue;
                    }
                    $key = mb_strtolower($aName);
                    if (isset($areaMap[$key])) {
                        $areaIds[] = $areaMap[$key];
                    } else {
                        $parsed['_warnings'][] = "Bereich \"{$aName}\" nicht gefunden";
                    }
                }
            }
            if (empty($areaIds)) {
                $parsed['_errors'][] = 'Kein gültiger Bereich angegeben';
            }
            $parsed['area_ids'] = array_values(array_unique($areaIds));

            // Vertrieb auflösen
            $parsed['sales_user_id'] = null;
            if ($parsed['sales_raw'] !== '') {
                $key = mb_strtolower($parsed['sales_raw']);
                if (isset($salesMap[$key])) {
                    $parsed['sales_user_id'] = $salesMap[$key];
                } else {
                    $parsed['_warnings'][] = "Vertriebler \"{$parsed['sales_raw']}\" nicht gefunden";
                }
            }

            // Metropolregion auflösen
            $parsed['metropolregion_id'] = null;
            if ($parsed['metropolregion_raw'] !== '') {
                $key = mb_strtolower($parsed['metropolregion_raw']);
                if (isset($metropolregionMap[$key])) {
                    $parsed['metropolregion_id'] = $metropolregionMap[$key];
                } else {
                    $parsed['_warnings'][] = "Metropolregion \"{$parsed['metropolregion_raw']}\" nicht gefunden";
                }
            }

            // Projektleitung auflösen
            $parsed['project_lead_id'] = null;
            if ($parsed['project_lead_raw'] !== '') {
                $key = mb_strtolower($parsed['project_lead_raw']);
                if (isset($projectLeadMap[$key])) {
                    $parsed['project_lead_id'] = $projectLeadMap[$key];
                } else {
                    $parsed['_warnings'][] = "Projektleitung \"{$parsed['project_lead_raw']}\" nicht gefunden";
                }
            }

            $rows[] = $parsed;
        }
        fclose($handle);

        if (empty($rows)) {
            Session::flash('error', 'CSV enthält keine importierbaren Datenzeilen (nur Kopfzeile oder leer).');
            Response::redirect('/backend/befragungen/import');
        }

        $_SESSION['_import_preview'] = $rows;

        $validCount = count(array_filter($rows, fn($r) => empty($r['_errors'])));
        $errorCount = count($rows) - $validCount;

        $pageTitle = 'Import-Vorschau';
        ob_start();
        require ROOT . '/app/Views/backend/surveys/import_preview.php';
        $content = ob_get_clean();
        require ROOT . '/app/Views/layout/backend.php';
    }

    public function importConfirm(array $params = []): void
    {
        if (!Csrf::validate()) {
            Response::forbidden();
        }

        $rows = $_SESSION['_import_preview'] ?? [];
        unset($_SESSION['_import_preview']);

        if (empty($rows)) {
            Session::flash('error', 'Keine Import-Vorschau vorhanden. Bitte erneut hochladen.');
            Response::redirect('/backend/befragungen/import');
        }

        $user    = Auth::user();
        $created = 0;
        $skipped = 0;

        foreach ($rows as $row) {
            if (!empty($row['_errors'])) {
                $skipped++;
                continue;
            }

            $code    = CodeGenerator::generate();
            $areaIds = $row['area_ids'];
            $firstArea = reset($areaIds);

            $id = Survey::create([
                'code'                => $code,
                'customer_name'       => $row['customer_name'],
                'project_id'          => $row['project_id'],
                'contact_salutation'  => $row['contact_salutation'],
                'contact_person'      => $row['contact_person'],
                'contact_email'       => $row['contact_email'],
                'project_name'        => $row['project_name'],
                'area_id'             => $firstArea,
                'sales_user_id'       => $row['sales_user_id'],
                'project_lead_id'     => $row['project_lead_id'] ?? null,
                'metropolregion_id'   => $row['metropolregion_id'] ?? null,
                'internal_notes'      => $row['internal_notes'],
                'reference_requested' => $row['reference_requested'],
                'status'              => 'open',
                'created_by'          => $user['id'],
            ]);

            Survey::syncAreas($id, $areaIds);
            Logger::backend('survey.imported', 'survey', $id, 'Befragung importiert: ' . $code);
            $created++;
        }

        $msg = "{$created} Befragung(en) erfolgreich importiert.";
        if ($skipped > 0) {
            $msg .= " {$skipped} Zeile(n) wegen Fehlern übersprungen.";
        }
        Session::flash('success', $msg);
        Response::redirect('/backend/befragungen');
    }

    private function validate(Request $request): array
    {
        $errors = [];

        if (!trim($request->post('customer_name', ''))) {
            $errors[] = 'Kundenname ist Pflichtfeld.';
        }
        if (!trim($request->post('contact_person', ''))) {
            $errors[] = 'Ansprechpartner ist Pflichtfeld.';
        }
        if (!filter_var($request->post('contact_email', ''), FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Gültige E-Mail-Adresse erforderlich.';
        }
        if (!trim($request->post('project_name', ''))) {
            $errors[] = 'Projektname ist Pflichtfeld.';
        }

        $areaIds = array_filter(array_map('intval', (array)($_POST['area_ids'] ?? [])));
        if (empty($areaIds)) {
            $errors[] = 'Mindestens ein Bereich muss ausgewählt werden.';
        }

        return $errors;
    }
}
