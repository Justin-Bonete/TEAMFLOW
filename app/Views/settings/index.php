<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Profile & settings · TEAMFLOW</title>
    <link rel="stylesheet" href="<?= e(rtrim($_ENV['APP_URL'] ?? '', '/')) ?>/assets/css/app.css">
    <link rel="stylesheet" href="<?= e(rtrim($_ENV['APP_URL'] ?? '', '/')) ?>/assets/css/dark-ui.css">
    <link rel="stylesheet" href="<?= e(rtrim($_ENV['APP_URL'] ?? '', '/')) ?>/assets/css/dashboard.css">
</head>
<body class="dashboard-page dark-dashboard">
    <?php $currentPage = 'settings'; require BASE_PATH . '/app/Views/layouts/workspace_start.php'; ?>
    <main class="dashboard-main">
        <?php require BASE_PATH . '/app/Views/settings/_panel.php'; ?>
    </main>
    <?php require BASE_PATH . '/app/Views/layouts/workspace_end.php'; ?>
</body>
</html>
