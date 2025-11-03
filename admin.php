<?php
/**
 * Page d'administration
 * Accessible uniquement aux administrateurs
 */

require_once 'config.php';
require_once 'migrations.php';

requireAdmin();

$db = getDB();
$manager = getMigrationManager();

$message = '';
$messageType = '';

// Gestion de l'exécution des migrations
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'run_all') {
        $results = $manager->runPendingMigrations();
        $success = !empty(array_filter($results, fn($r) => $r['success']));
        $message = $success ? 'Migrations exécutées avec succès !' : 'Erreur lors de l\'exécution des migrations';
        $messageType = $success ? 'success' : 'error';
    } elseif ($_POST['action'] === 'run_one' && isset($_POST['migration'])) {
        $result = $manager->executeMigration($_POST['migration']);
        $message = $result['success'] ? 'Migration exécutée avec succès !' : 'Erreur : ' . $result['message'];
        $messageType = $result['success'] ? 'success' : 'error';
    }
}

$migrations = $manager->getAvailableMigrations();
$pendingCount = $manager->getPendingCount();

// Statistiques
$stats = [
    'total_users' => $db->query("SELECT COUNT(*) FROM users")->fetchColumn(),
    'total_transactions' => $db->query("SELECT COUNT(*) FROM transactions")->fetchColumn(),
    'total_categories' => $db->query("SELECT COUNT(*) FROM categories")->fetchColumn(),
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administration - Mes Comptes</title>
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
    <link rel="stylesheet" href="css/style.css?v=<?= CSS_VERSION ?>">
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <div class="container">
        <header class="settings-header">
            <h1>🔧 Administration</h1>
        </header>

        <?php if ($message): ?>
            <div class="<?= $messageType === 'success' ? 'success' : 'error' ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <!-- Statistiques -->
        <div class="settings-section">
            <div class="settings-card">
                <h3>📊 Statistiques</h3>
                <div class="admin-stats-grid">
                    <div class="admin-stat-card">
                        <div class="admin-stat-icon">👥</div>
                        <div class="admin-stat-value"><?= $stats['total_users'] ?></div>
                        <div class="admin-stat-label">Utilisateurs</div>
                    </div>
                    <div class="admin-stat-card">
                        <div class="admin-stat-icon">💸</div>
                        <div class="admin-stat-value"><?= $stats['total_transactions'] ?></div>
                        <div class="admin-stat-label">Transactions</div>
                    </div>
                    <div class="admin-stat-card">
                        <div class="admin-stat-icon">🏷️</div>
                        <div class="admin-stat-value"><?= $stats['total_categories'] ?></div>
                        <div class="admin-stat-label">Catégories</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Migrations -->
        <div class="settings-section">
            <div class="settings-card">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                    <h3>🔄 Migrations de base de données</h3>
                    <?php if ($pendingCount > 0): ?>
                        <span class="migration-badge warning"><?= $pendingCount ?> en attente</span>
                    <?php else: ?>
                        <span class="migration-badge success">✓ À jour</span>
                    <?php endif; ?>
                </div>

                <?php if ($pendingCount > 0): ?>
                    <div class="migration-warning">
                        <strong>⚠️ Attention :</strong> Il y a <?= $pendingCount ?> migration(s) en attente.
                        Il est recommandé de les exécuter dès que possible.
                        <form method="POST" style="margin-top: 1rem;">
                            <input type="hidden" name="action" value="run_all">
                            <button type="submit" class="btn-primary">
                                ▶️ Exécuter toutes les migrations
                            </button>
                        </form>
                    </div>
                <?php endif; ?>

                <div class="migrations-list">
                    <?php foreach ($migrations as $migration): ?>
                        <div class="migration-item <?= $migration['executed'] ? 'executed' : 'pending' ?>">
                            <div class="migration-info">
                                <div class="migration-name">
                                    <?= $migration['executed'] ? '✅' : '⏸️' ?>
                                    <?= htmlspecialchars($migration['name']) ?>
                                </div>
                                <div class="migration-status">
                                    <?= $migration['executed'] ? 'Exécutée' : 'En attente' ?>
                                </div>
                            </div>
                            <?php if (!$migration['executed']): ?>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="run_one">
                                    <input type="hidden" name="migration" value="<?= htmlspecialchars($migration['name']) ?>">
                                    <button type="submit" class="btn-secondary-small">Exécuter</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="js/sidebar.js"></script>
</body>
</html>
