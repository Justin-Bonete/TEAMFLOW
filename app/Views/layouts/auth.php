<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($_ENV['APP_NAME'] ?? 'TEAMFLOW') ?></title>
    <link rel="stylesheet" href="<?= e(rtrim($_ENV['APP_URL'] ?? '', '/')) ?>/assets/css/app.css">
</head>
<body class="auth-page">
    <main class="auth-shell">
        <section class="auth-brand">
            <div class="brand-mark">T</div>
            <p class="eyebrow">TEAMFLOW WORKSPACE</p>
            <h1>Bring your team’s work into focus.</h1>
            <p class="muted">Plan projects, coordinate tasks, and keep every milestone visible.</p>
            <div class="brand-foot">A clearer way to move work forward.</div>
        </section>
        <section class="auth-panel">
            <div class="auth-content">
                <?= $content ?>
            </div>
        </section>
    </main>
</body>
</html>
