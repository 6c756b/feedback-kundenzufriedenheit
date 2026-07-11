<?php
$h      = fn(string $v) => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
$newKey = \App\Core\Session::flash('api_key_created');
$old    = \App\Core\Session::flash('old') ?? [];
$today  = date('Y-m-d');
?>

<div class="page-header">
    <h1>API Keys</h1>
</div>

<div class="api-key-top-grid">
    <form method="post" action="/backend/api-keys" class="form-card">
        <?= \App\Core\Csrf::field() ?>
        <div class="form-grid">
            <div class="form-group form-full">
                <label for="api-key-name">Name / Beschreibung *</label>
                <input type="text" id="api-key-name" name="name" required maxlength="100"
                       placeholder="z.B. Odoo19 Production"
                       value="<?= $h($old['name'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="api-key-expires">Ablaufdatum</label>
                <input type="date" id="api-key-expires" name="expires_at"
                       min="<?= $h($today) ?>"
                       value="<?= $h($old['expires_at'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Berechtigungen *</label>
                <div class="api-key-perms">
                    <label class="api-key-perm-label">
                        <input type="checkbox" name="can_read" value="1"
                               <?= !isset($old['name']) || !empty($old['can_read']) ? 'checked' : '' ?>>
                        Lesen (GET)
                    </label>
                    <label class="api-key-perm-label">
                        <input type="checkbox" name="can_write" value="1"
                               <?= !isset($old['name']) || !empty($old['can_write']) ? 'checked' : '' ?>>
                        Schreiben (POST)
                    </label>
                </div>
            </div>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn-primary">Key erstellen</button>
        </div>
    </form>

    <?php if ($newKey): ?>
    <div class="api-key-notice">
        <button type="button" class="api-key-notice-close" onclick="this.closest('.api-key-notice').remove()" title="Schließen">&times;</button>
        <div class="api-key-notice-title">API Key erstellt</div>
        <div class="api-key-notice-hint">Bitte jetzt kopieren – er wird nicht erneut angezeigt.</div>
        <div class="api-key-reveal"><?= $h($newKey) ?></div>
        <button type="button" class="api-key-copy-btn"
                onclick="navigator.clipboard.writeText(<?= htmlspecialchars(json_encode($newKey), ENT_QUOTES) ?>).then(() => this.textContent = '✓ Kopiert')">
            In Zwischenablage kopieren
        </button>
    </div>
    <?php endif; ?>
</div>

<table class="data-table">
    <thead>
        <tr>
            <th>Name</th>
            <th>Berechtigungen</th>
            <th>Ablaufdatum</th>
            <th>Erstellt</th>
            <th>Zuletzt verwendet</th>
            <th>Status</th>
            <th>Aktionen</th>
        </tr>
    </thead>
    <tbody>
    <?php if (!$keys): ?>
        <tr><td colspan="7" class="text-center text-muted">Keine API Keys vorhanden.</td></tr>
    <?php endif; ?>
    <?php foreach ($keys as $key): ?>
    <?php $expired = $key['expires_at'] && $key['expires_at'] < $today; ?>
    <tr>
        <td><?= $h($key['name']) ?></td>
        <td>
            <span class="<?= $key['can_read']  ? 'badge badge-open' : 'badge badge-disabled' ?>">Lesen</span>
            <span class="<?= $key['can_write'] ? 'badge badge-open' : 'badge badge-disabled' ?>">Schreiben</span>
        </td>
        <td class="text-nowrap">
            <?php if ($key['expires_at']): ?>
                <span class="<?= $expired ? 'api-key-expired' : '' ?>">
                    <?= $h(date('d.m.Y', strtotime($key['expires_at']))) ?>
                    <?= $expired ? ' <span class="badge badge-archived">Abgelaufen</span>' : '' ?>
                </span>
            <?php else: ?>
                <span class="text-muted">–</span>
            <?php endif; ?>
        </td>
        <td class="text-muted text-nowrap"><?= $h(date('d.m.Y H:i', strtotime($key['created_at']))) ?></td>
        <td class="text-muted text-nowrap">
            <?= $key['last_used_at'] ? $h(date('d.m.Y H:i', strtotime($key['last_used_at']))) : '–' ?>
        </td>
        <td>
            <?php if ($expired): ?>
                <span class="badge badge-archived">Abgelaufen</span>
            <?php elseif ($key['active']): ?>
                <span class="badge badge-open">Aktiv</span>
            <?php else: ?>
                <span class="badge badge-archived">Inaktiv</span>
            <?php endif; ?>
        </td>
        <td>
            <div class="actions">
                <form method="post" action="/backend/api-keys/<?= (int)$key['id'] ?>/loeschen"
                      onsubmit="return confirm('API Key «<?= $h(addslashes($key['name'])) ?>» wirklich löschen?')">
                    <?= \App\Core\Csrf::field() ?>
                    <button type="submit" class="btn-icon btn-icon--danger" title="Löschen">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/>
                        </svg>
                    </button>
                </form>
            </div>
        </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
