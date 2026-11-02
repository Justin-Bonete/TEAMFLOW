<?php ob_start(); ?>
<p class="eyebrow">WELCOME BACK</p>
<h2>Sign in to TEAMFLOW</h2>
<p class="muted">Use your registered work email to continue.</p>
<?php if (!empty($error)): ?><div class="alert"><?= e($error) ?></div><?php endif; ?>
<form method="post" action="<?= e(rtrim($_ENV['APP_URL'] ?? '', '/')) ?>/login" class="form-stack">
    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
    <label>Email address<input type="email" name="email" autocomplete="username" required></label>
    <label>Password<input type="password" name="password" autocomplete="current-password" required></label>
    <button class="button-primary" type="submit">Sign in <span>→</span></button>
</form>
<p class="form-note">New installation? <a href="<?= e(rtrim($_ENV['APP_URL'] ?? '', '/')) ?>/setup-admin">Create the initial administrator</a>.</p>
<?php $content = ob_get_clean(); require BASE_PATH . '/app/Views/layouts/auth.php'; ?>
