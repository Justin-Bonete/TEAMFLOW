<?php ob_start(); ?>
<p class="eyebrow">FIRST-TIME SETUP</p>
<h2>Create administrator</h2>
<p class="muted">This one-time setup is disabled after the first account is created.</p>
<?php if (!empty($error)): ?><div class="alert"><?= e($error) ?></div><?php endif; ?>
<form method="post" action="<?= e(rtrim($_ENV['APP_URL'] ?? '', '/')) ?>/setup-admin" class="form-stack">
    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
    <div class="two-fields">
        <label>First name<input name="first_name" maxlength="80" required></label>
        <label>Last name<input name="last_name" maxlength="80" required></label>
    </div>
    <label>Work email<input type="email" name="email" maxlength="190" required></label>
    <label>Password<input type="password" name="password" minlength="12" required></label>
    <label>Confirm password<input type="password" name="password_confirmation" minlength="12" required></label>
    <button class="button-primary" type="submit">Create administrator <span>→</span></button>
</form>
<p class="form-note"><a href="<?= e(rtrim($_ENV['APP_URL'] ?? '', '/')) ?>/login">Return to sign in</a></p>
<?php $content = ob_get_clean(); require BASE_PATH . '/app/Views/layouts/auth.php'; ?>
