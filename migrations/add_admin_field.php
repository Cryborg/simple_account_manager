<?php
/**
 * Migration : Ajout du champ is_admin à la table users
 * Pour exécuter : php migrations/add_admin_field.php
 */

require_once __DIR__ . '/../config.php';

try {
    $db = getDB();

    // Vérifier si la colonne exists déjà
    $columns = $db->query("PRAGMA table_info(users)")->fetchAll(PDO::FETCH_ASSOC);
    $hasIsAdmin = false;
    $hasEmail = false;

    foreach ($columns as $column) {
        if ($column['name'] === 'is_admin') {
            $hasIsAdmin = true;
        }
        if ($column['name'] === 'email') {
            $hasEmail = true;
        }
    }

    // Ajouter la colonne email si elle n'existe pas
    if (!$hasEmail) {
        $db->exec("ALTER TABLE users ADD COLUMN email TEXT");
        echo "✓ Colonne email ajoutée\n";
    }

    // Ajouter la colonne is_admin si elle n'existe pas
    if (!$hasIsAdmin) {
        $db->exec("ALTER TABLE users ADD COLUMN is_admin INTEGER DEFAULT 0");
        echo "✓ Colonne is_admin ajoutée\n";

        // Donner les droits admin à l'utilisateur "Cryborg" s'il existe
        $stmt = $db->prepare("UPDATE users SET is_admin = 1 WHERE username = 'Cryborg'");
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            echo "✓ Droits admin accordés à Cryborg\n";
        }
    } else {
        echo "✓ Colonne is_admin existe déjà\n";
    }

} catch (PDOException $e) {
    echo "✗ Erreur lors de la migration : " . $e->getMessage() . "\n";
    exit(1);
}
