<?php
require_once 'config.php';

$db = getDB();

// Vérifier si les colonnes existent déjà
$columns = $db->query("PRAGMA table_info(transactions)")->fetchAll(PDO::FETCH_ASSOC);
$hasRecurringMonths = false;
$hasRemainingOccurrences = false;
$hasParentId = false;

foreach ($columns as $column) {
    if ($column['name'] === 'recurring_months') {
        $hasRecurringMonths = true;
    }
    if ($column['name'] === 'remaining_occurrences') {
        $hasRemainingOccurrences = true;
    }
    if ($column['name'] === 'parent_transaction_id') {
        $hasParentId = true;
    }
}

// Ajouter les colonnes si elles n'existent pas
if (!$hasRecurringMonths) {
    $db->exec("ALTER TABLE transactions ADD COLUMN recurring_months INTEGER DEFAULT 1");
    echo "Colonne recurring_months ajoutée\n";
}

if (!$hasRemainingOccurrences) {
    $db->exec("ALTER TABLE transactions ADD COLUMN remaining_occurrences INTEGER");
    echo "Colonne remaining_occurrences ajoutée\n";
}

if (!$hasParentId) {
    $db->exec("ALTER TABLE transactions ADD COLUMN parent_transaction_id INTEGER REFERENCES transactions(id)");
    echo "Colonne parent_transaction_id ajoutée\n";
}

echo "Migration des transactions récurrentes terminée avec succès !\n";
