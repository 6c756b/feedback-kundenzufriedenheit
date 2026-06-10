<?php
use App\Core\Csrf;
$error = \App\Core\Session::flash('error');
?>
<div class="page-header">
    <h1>Befragungen importieren</h1>
    <a href="/backend/befragungen" class="btn-ghost">Zurück</a>
</div>

<?php if ($error): ?>
<div class="flash flash-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<div class="form-card" style="max-width:600px;">
    <p style="margin:0 0 1.25rem;">
        CSV hochladen um mehrere Befragungen auf einmal anzulegen.
        Nach dem Upload erscheint eine Vorschau zur Prüfung.
    </p>

    <div style="margin-bottom:1.5rem;">
        <a href="/backend/befragungen/import/vorlage" class="btn-ghost" download>
            ↓ Vorlage herunterladen (CSV)
        </a>
        <small style="display:block;margin-top:.4rem;color:var(--text-muted);">
            Enthält Kopfzeile und eine Beispielzeile, die beim Import automatisch entfernt wird.
        </small>
    </div>

    <form method="post" action="/backend/befragungen/import/vorschau"
          enctype="multipart/form-data">
        <?= Csrf::field() ?>

        <div class="form-group">
            <label for="csv_file">CSV-Datei *</label>
            <input type="file" id="csv_file" name="csv_file" accept=".csv,text/csv" required>
        </div>

        <div class="form-actions" style="margin-top:1.25rem;">
            <button type="submit" class="btn-primary">Vorschau anzeigen</button>
            <a href="/backend/befragungen" class="btn-ghost">Abbrechen</a>
        </div>
    </form>
</div>

<div class="form-card" style="max-width:600px;margin-top:1.5rem;">
    <h3 style="margin:0 0 .75rem;font-size:.95rem;">Spalten der CSV-Datei</h3>
    <table class="data-table" style="font-size:.85rem;">
        <thead>
            <tr>
                <th>Spalte</th>
                <th>Pflicht</th>
                <th>Hinweis</th>
            </tr>
        </thead>
        <tbody>
            <tr><td>Kunde</td>           <td>Ja</td>  <td></td></tr>
            <tr><td>Projektname</td>     <td>Ja</td>  <td></td></tr>
            <tr><td>Projekt-ID</td>      <td>Nein</td><td></td></tr>
            <tr><td>Ansprechpartner</td> <td>Ja</td>  <td></td></tr>
            <tr><td>Anrede</td>          <td>Nein</td><td>Herr oder Frau (leer lassen = keine Angabe)</td></tr>
            <tr><td>Email</td>           <td>Ja</td>  <td>Gültige E-Mail-Adresse</td></tr>
            <tr><td>Bereiche</td>        <td>Ja</td>  <td>Kommagetrennte Bereichsnamen (mind. 1)</td></tr>
            <tr><td>Vertrieb</td>        <td>Nein</td><td>Name des Vertriebs-Mitarbeiters</td></tr>
            <tr><td>Metropolregion</td>  <td>Nein</td><td>Name der Metropolregion (z.&nbsp;B. OWL)</td></tr>
            <tr><td>Projektleitung</td>  <td>Nein</td><td>Name des Projektleiters</td></tr>
            <tr><td>Kommentar</td>       <td>Nein</td><td>Interne Anmerkung</td></tr>
            <tr><td>Referenz</td>        <td>Nein</td><td>Ja / Nein</td></tr>
        </tbody>
    </table>
</div>
