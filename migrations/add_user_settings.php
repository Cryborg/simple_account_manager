<?php
/**
 * Migration : Ajout de la table user_settings
 * Pour exécuter : php migrations/add_user_settings.php
 */

require_once __DIR__ . '/../config.php';

try {
    $db = getDB();

    // Vérifier si la table existe déjà
    $result = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='user_settings'");

    if ($result->fetch()) {
        echo "La table user_settings existe déjà.\n";
        exit(0);
    }

    // Créer la table user_settings
    $db->exec("CREATE TABLE user_settings (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL UNIQUE,
        show_year_in_dates INTEGER DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");

    echo "✓ Table user_settings créée avec succès !\n";

    // Créer les paramètres par défaut pour tous les utilisateurs existants
    $users = $db->query("SELECT id FROM users")->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $db->prepare("INSERT INTO user_settings (user_id) VALUES (?)");
    foreach ($users as $user) {
        $stmt->execute([$user['id']]);
    }

    echo "✓ Paramètres par défaut créés pour " . count($users) . " utilisateur(s)\n";

} catch (PDOException $e) {
    echo "✗ Erreur lors de la migration : " . $e->getMessage() . "\n";
    exit(1);
}
