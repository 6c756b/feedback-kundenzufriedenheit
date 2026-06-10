<?php
use App\Core\Csrf;
$config = require ROOT . '/config.php';
?>
<div class="login-box">
    <h1 class="login-title"><?= htmlspecialchars($config['app']['name'] ?? '', ENT_QUOTES, 'UTF-8') ?></h1>
    <p class="login-sub"><?= htmlspecialchars($config['app']['survey_label'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>

    <?php if ($error ?? null): ?>
    <div class="flash flash-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <form method="post" action="/backend/login" class="login-form">
        <?= Csrf::field() ?>
        <div class="form-group">
            <label for="email">E-Mail</label>
            <input type="email" id="email" name="email" required autocomplete="email" autofocus>
        </div>
        <div class="form-group">
            <label for="password">Passwort</label>
            <input type="password" id="password" name="password" required autocomplete="current-password">
        </div>
        <button type="submit" class="btn-primary btn-full">Anmelden</button>
    </form>

</div>
