<?php
require_once 'config.php';
requireLogin();

$db = getDB();
$userId = $_SESSION['user_id'];
$message = '';

// Gestion de la sauvegarde des paramètres
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $showYearInDates = isset($_POST['show_year_in_dates']) ? 1 : 0;

    updateUserSetting('show_year_in_dates', $showYearInDates);

    $message = 'Paramètres sauvegardés avec succès !';
}

// Récupérer les paramètres actuels
$settings = getUserSettings();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paramètres - Mes Comptes</title>
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <div class="container">
        <header class="settings-header">
            <h1>Paramètres</h1>
        </header>

        <?php if ($message): ?>
            <div class="success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <div class="settings-section">
            <form method="POST">
                <div class="settings-card">
                    <h3>Affichage</h3>

                    <div class="setting-item">
                        <div class="setting-info">
                            <label for="show_year_in_dates" class="setting-label">Afficher l'année dans les dates</label>
                            <p class="setting-description">Affiche l'année complète dans les dates de l'historique (ex: 15/01/2025 au lieu de 15/01)</p>
                        </div>
                        <div class="setting-control">
                            <label class="toggle-switch">
                                <input type="checkbox" id="show_year_in_dates" name="show_year_in_dates" <?= $settings['show_year_in_dates'] ? 'checked' : '' ?>>
                                <span class="toggle-slider"></span>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="settings-actions">
                    <button type="submit" class="btn-primary">Enregistrer les paramètres</button>
                </div>
            </form>
        </div>
    </div>

    <script src="js/sidebar.js"></script>
</body>
</html>
