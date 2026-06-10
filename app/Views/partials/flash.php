<?php
use App\Core\Session;

$success = Session::flash('success');
$errors  = Session::flash('errors');
$info    = Session::flash('info');
?>
<?php if ($success): ?>
<div class="flash flash-success"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>
<?php if ($info): ?>
<div class="flash flash-info"><?= htmlspecialchars($info, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>
<?php if ($errors): ?>
<div class="flash flash-error">
    <?php if (is_array($errors)): ?>
        <ul><?php foreach ($errors as $err): ?>
            <li><?= htmlspecialchars($err, ENT_QUOTES, 'UTF-8') ?></li>
        <?php endforeach; ?></ul>
    <?php else: ?>
        <?= htmlspecialchars($errors, ENT_QUOTES, 'UTF-8') ?>
    <?php endif; ?>
</div>
<?php endif; ?>
