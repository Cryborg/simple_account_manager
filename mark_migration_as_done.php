<?php

/**
 * Marquer une migration comme exécutée sans la ré-exécuter
 * Utile quand une migration a été exécutée manuellement mais pas enregistrée
 */

require_once 'config.php';
require_once 'migrations.php';

echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║   Marquer des migrations comme exécutées                     ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n\n";

$manager = getMigrationManager();

// Récupérer toutes les migrations
$allMigrations = $manager->getAvailableMigrations();

// Afficher les migrations en attente
$pendingMigrations = array_filter($allMigrations, fn($m) => !$m['executed']);

if (empty($pendingMigrations)) {
    echo "✅ Aucune migration en attente. Tout est à jour !\n";
    exit(0);
}

echo "📋 Migrations en attente :\n";
echo str_repeat("-", 60) . "\n";

foreach ($pendingMigrations as $index => $migration) {
    echo sprintf("  %d. %s\n", $index + 1, $migration['name']);
}

echo "\n";
echo "Ces migrations semblent déjà exécutées mais pas enregistrées.\n";
echo "Ce script va les marquer comme exécutées dans migrations_log.\n";
echo "\n";

// Demander confirmation
echo "⚠️  Marquer TOUTES ces migrations comme exécutées ? (tapez 'oui') : ";
$handle = fopen("php://stdin", "r");
$confirmation = trim(fgets($handle));

if (strtolower($confirmation) !== 'oui') {
    echo "\n❌ Annulé.\n";
    exit(0);
}

echo "\n";

// Marquer les migrations comme exécutées
$db = getDB();

foreach ($pendingMigrations as $migration) {
    try {
        $stmt = $db->prepare("INSERT INTO migrations_log (migration_name, executed_at) VALUES (?, datetime('now'))");
        $stmt->execute([$migration['name']]);
        echo "✅ {$migration['name']} marquée comme exécutée\n";
    } catch (PDOException $e) {
        echo "❌ Erreur pour {$migration['name']}: {$e->getMessage()}\n";
    }
}

echo "\n";
echo "✅ Terminé !\n";
echo "\n";
echo "Vérification :\n";
$remaining = $manager->getPendingCount();
echo "  • Migrations en attente : {$remaining}\n";
