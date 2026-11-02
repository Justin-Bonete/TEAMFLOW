<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Access denied · TEAMFLOW</title>
    <link rel="stylesheet" href="<?= e(rtrim($_ENV['APP_URL'] ?? '', '/')) ?>/assets/css/app.css">
</head>
<body class="error-page">
    <p class="eyebrow">TEAMFLOW</p>
    <h1>403</h1>
    <p>You do not have permission to access this page.</p>
    <a href="<?= e(rtrim($_ENV['APP_URL'] ?? '', '/')) ?>/dashboard">Return to dashboard</a>
</body>
</html>
