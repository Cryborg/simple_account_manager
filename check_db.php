<?php

require_once 'config.php';

echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║           Vérification de la Base de Données                ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n\n";

$db = getDB();

// 1. Lister les tables
echo "📋 TABLES :\n";
echo str_repeat("-", 60) . "\n";
$tables = $db->query("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name")->fetchAll(PDO::FETCH_COLUMN);
foreach ($tables as $table) {
    echo "  • {$table}\n";
}
echo "\n";

// 2. Structure de la table users
echo "👥 TABLE USERS - Structure :\n";
echo str_repeat("-", 60) . "\n";
$columns = $db->query("PRAGMA table_info(users)")->fetchAll(PDO::FETCH_ASSOC);
foreach ($columns as $col) {
    echo sprintf("  %-20s %-15s %s\n", $col['name'], $col['type'], $col['notnull'] ? 'NOT NULL' : '');
}
echo "\n";

// 3. Utilisateurs
echo "👥 UTILISATEURS :\n";
echo str_repeat("-", 60) . "\n";
$users = $db->query("SELECT id, username, email, is_admin, created_at FROM users ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
echo sprintf("%-5s %-20s %-30s %-10s %-20s\n", "ID", "Username", "Email", "Is Admin", "Created At");
echo str_repeat("-", 60) . "\n";
foreach ($users as $user) {
    echo sprintf(
        "%-5s %-20s %-30s %-10s %-20s\n",
        $user['id'],
        $user['username'],
        $user['email'] ?? 'N/A',
        $user['is_admin'] ? 'OUI' : 'Non',
        $user['created_at']
    );
}
echo "\n";

// 4. Nombre de transactions par utilisateur
echo "💰 TRANSACTIONS PAR UTILISATEUR :\n";
echo str_repeat("-", 60) . "\n";
$stats = $db->query("
    SELECT
        u.username,
        COUNT(t.id) as nb_transactions,
        COALESCE(SUM(CASE WHEN t.type = 'recette' THEN t.amount ELSE 0 END), 0) as total_recettes,
        COALESCE(SUM(CASE WHEN t.type = 'depense' THEN t.amount ELSE 0 END), 0) as total_depenses,
        COALESCE(SUM(CASE WHEN t.type = 'recette' THEN t.amount ELSE -t.amount END), 0) as solde
    FROM users u
    LEFT JOIN transactions t ON u.id = t.user_id
    GROUP BY u.id, u.username
    ORDER BY u.id
")->fetchAll(PDO::FETCH_ASSOC);

echo sprintf("%-20s %-15s %-15s %-15s %-15s\n", "Username", "Transactions", "Recettes", "Dépenses", "Solde");
echo str_repeat("-", 60) . "\n";
foreach ($stats as $stat) {
    echo sprintf(
        "%-20s %-15s %-15s %-15s %-15s\n",
        $stat['username'],
        $stat['nb_transactions'],
        number_format($stat['total_recettes'], 2) . ' €',
        number_format($stat['total_depenses'], 2) . ' €',
        number_format($stat['solde'], 2) . ' €'
    );
}
echo "\n";

// 5. Catégories par utilisateur
echo "📁 CATÉGORIES PAR UTILISATEUR :\n";
echo str_repeat("-", 60) . "\n";
$categories = $db->query("
    SELECT u.username, COUNT(c.id) as nb_categories
    FROM users u
    LEFT JOIN categories c ON u.id = c.user_id
    GROUP BY u.id, u.username
    ORDER BY u.id
")->fetchAll(PDO::FETCH_ASSOC);

foreach ($categories as $cat) {
    echo sprintf("  • %-20s : %d catégories\n", $cat['username'], $cat['nb_categories']);
}
echo "\n";

// 6. Migrations exécutées
echo "🔄 MIGRATIONS EXÉCUTÉES :\n";
echo str_repeat("-", 60) . "\n";
$migTableExists = in_array('migrations_log', $tables);
if ($migTableExists) {
    $migrations = $db->query("SELECT migration_name, executed_at FROM migrations_log ORDER BY executed_at")->fetchAll(PDO::FETCH_ASSOC);
    if (empty($migrations)) {
        echo "  Aucune migration enregistrée\n";
    } else {
        foreach ($migrations as $mig) {
            echo sprintf("  ✓ %-40s [%s]\n", $mig['migration_name'], $mig['executed_at']);
        }
    }
} else {
    echo "  ⚠️  Table migrations_log non trouvée\n";
}
echo "\n";

// 7. Dernières transactions (5 plus récentes)
echo "📊 DERNIÈRES TRANSACTIONS (5 plus récentes) :\n";
echo str_repeat("-", 60) . "\n";
$recentTrans = $db->query("
    SELECT
        u.username,
        t.type,
        t.amount,
        t.description,
        t.transaction_date,
        t.created_at
    FROM transactions t
    JOIN users u ON t.user_id = u.id
    ORDER BY t.transaction_date DESC, t.created_at DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

if (empty($recentTrans)) {
    echo "  Aucune transaction\n";
} else {
    echo sprintf("%-15s %-10s %-12s %-30s %-12s\n", "User", "Type", "Montant", "Description", "Date");
    echo str_repeat("-", 60) . "\n";
    foreach ($recentTrans as $trans) {
        echo sprintf(
            "%-15s %-10s %11s € %-30s %-12s\n",
            substr($trans['username'], 0, 14),
            $trans['type'],
            number_format($trans['amount'], 2),
            substr($trans['description'] ?? 'N/A', 0, 29),
            $trans['transaction_date']
        );
    }
}
echo "\n";

echo "✅ Vérification terminée !\n";
