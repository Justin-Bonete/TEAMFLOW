<div class="settings-panel-content">
    <header class="settings-heading">
        <div><span class="eyebrow">YOUR ACCOUNT</span><h1>Profile & settings</h1><p>Manage your personal information, profile photo, and connected accounts.</p></div>
    </header>
    <?php if ($error): ?><div class="alert" role="alert"><?= e($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="success-message" role="status"><?= e($success) ?></div><?php endif; ?>

    <section class="settings-card profile-settings-card">
        <div class="settings-card-heading">
            <div><span class="eyebrow">PROFILE</span><h2>Personal information</h2></div>
            <span class="avatar settings-avatar"><?= e(strtoupper(substr($profile['first_name'], 0, 1))) ?><?php if (!empty($_SESSION['has_profile_avatar'])): ?><img class="avatar-photo" src="<?= e(rtrim($_ENV['APP_URL'] ?? '', '/')) ?>/profile/avatar?v=<?= (int) ($_SESSION['profile_avatar_version'] ?? 0) ?>" alt="" aria-hidden="true"><?php endif; ?></span>
        </div>
        <form method="post" action="<?= e(rtrim($_ENV['APP_URL'] ?? '', '/')) ?>/settings/profile" class="settings-form">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="return_to" value="panel">
            <label>First name<input name="first_name" maxlength="80" autocomplete="given-name" value="<?= e($profile['first_name']) ?>" required></label>
            <label>Last name<input name="last_name" maxlength="80" autocomplete="family-name" value="<?= e($profile['last_name']) ?>" required></label>
            <label class="settings-form-wide">Email address<input type="email" name="email" maxlength="190" autocomplete="email" value="<?= e($profile['email']) ?>" required><small>Changing your email or password requires your current password.</small></label>
            <label class="settings-form-wide">Current password<input type="password" name="current_password" autocomplete="current-password"><small>Only required when changing your email address or password.</small></label>
            <label>New password<input type="password" name="new_password" minlength="12" autocomplete="new-password"><small>Leave blank to keep your current password.</small></label>
            <label>Confirm new password<input type="password" name="new_password_confirmation" minlength="12" autocomplete="new-password"></label>
            <button class="button-primary settings-form-wide" type="submit">Save profile changes <span>→</span></button>
        </form>
    </section>

    <section class="settings-card">
        <div class="settings-card-heading">
            <div><span class="eyebrow">PROFILE PHOTO</span><h2>Choose how you appear</h2></div>
            <span class="settings-current-source">Current: <?= e(ucfirst($profile['avatar_source'])) ?></span>
        </div>
        <p class="settings-description">Upload a photo or use the avatar from your linked GitHub account. Photos are stored privately in TEAMFLOW.</p>
        <form method="post" action="<?= e(rtrim($_ENV['APP_URL'] ?? '', '/')) ?>/settings/avatar/upload" class="avatar-upload-form" enctype="multipart/form-data">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="return_to" value="panel">
            <label>Choose a JPEG, PNG, or WebP photo<input type="file" name="avatar" accept="image/jpeg,image/png,image/webp" required></label>
            <button class="button-secondary" type="submit">Upload photo</button>
        </form>
        <?php if ($profile['profile_image_path'] !== null): ?>
            <form method="post" action="<?= e(rtrim($_ENV['APP_URL'] ?? '', '/')) ?>/settings/avatar/remove" class="settings-inline-form">
                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="return_to" value="panel">
                <button class="button-secondary" type="submit">Remove uploaded photo</button>
            </form>
        <?php endif; ?>
        <form method="post" action="<?= e(rtrim($_ENV['APP_URL'] ?? '', '/')) ?>/settings/avatar/source" class="avatar-source-form">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="return_to" value="panel">
            <label>Display photo
                <select name="avatar_source">
                    <option value="initials" <?= $profile['avatar_source'] === 'initials' ? 'selected' : '' ?>>Initials</option>
                    <?php if ($profile['profile_image_path'] !== null): ?><option value="upload" <?= $profile['avatar_source'] === 'upload' ? 'selected' : '' ?>>Uploaded photo</option><?php endif; ?>
                    <?php if ($profile['github_id'] !== null && $profile['github_avatar_path'] !== null): ?><option value="github" <?= $profile['avatar_source'] === 'github' ? 'selected' : '' ?>>GitHub photo</option><?php endif; ?>
                </select>
            </label>
            <button class="button-secondary" type="submit">Use selected photo</button>
        </form>
    </section>

    <section class="settings-card github-settings-card">
        <div class="settings-card-heading">
            <div><span class="eyebrow">CONNECTED ACCOUNTS</span><h2>GitHub</h2></div>
            <?php if ($profile['github_id'] !== null): ?><span class="github-connected">Connected</span><?php endif; ?>
        </div>
        <?php if ($profile['github_id'] !== null): ?>
            <p class="settings-description">Connected as <strong>@<?= e($profile['github_login']) ?></strong>. TEAMFLOW uses a private copy of your GitHub avatar and does not sign you in with GitHub.</p>
            <form method="post" action="<?= e(rtrim($_ENV['APP_URL'] ?? '', '/')) ?>/settings/github/disconnect" class="settings-inline-form">
                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="return_to" value="panel">
                <button class="button-secondary" type="submit">Disconnect GitHub</button>
            </form>
        <?php elseif ($githubConfigured): ?>
            <p class="settings-description">Connect GitHub to use its profile photo. TEAMFLOW requests read-only profile access; your email and repositories are not imported.</p>
            <a class="button-secondary github-connect-button" href="<?= e(rtrim($_ENV['APP_URL'] ?? '', '/')) ?>/settings/github/connect?return_to=panel">Connect GitHub</a>
        <?php else: ?>
            <p class="settings-description">GitHub linking needs a GitHub OAuth App. Configure the client ID and secret in the server’s <code>.env</code> file, then use the callback URL below in the OAuth App settings.</p>
            <code class="github-callback-url"><?= e(rtrim($_ENV['APP_URL'] ?? '', '/')) ?>/settings/github/callback</code>
        <?php endif; ?>
    </section>
</div>
