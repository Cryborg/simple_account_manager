<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Mes Comptes') ?> - Mes Comptes</title>
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
    <link rel="stylesheet" href="css/style.css?v=<?= CSS_VERSION ?>">
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="login-icon"><?= $pageIcon ?? '€' ?></div>
                <h1><?= htmlspecialchars($pageHeading ?? 'Mes Comptes') ?></h1>
                <p class="login-subtitle"><?= htmlspecialchars($pageSubtitle ?? '') ?></p>
            </div>
