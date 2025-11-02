<?php
require_once 'config.php';

$db = getDB();

// Vérifier si la colonne end_date existe déjà
$columns = $db->query("PRAGMA table_info(transactions)")->fetchAll(PDO::FETCH_ASSOC);
$hasEndDate = false;

foreach ($columns as $column) {
    if ($column['name'] === 'end_date') {
        $hasEndDate = true;
    }
}

// Ajouter la colonne si elle n'existe pas
if (!$hasEndDate) {
    $db->exec("ALTER TABLE transactions ADD COLUMN end_date DATE");
    echo "Colonne end_date ajoutée\n";
}

echo "Migration terminée avec succès !\n";
