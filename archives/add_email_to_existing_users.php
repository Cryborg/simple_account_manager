<?php
/**
 * Script pour ajouter une adresse email aux utilisateurs existants
 *
 * Usage: php add_email_to_existing_users.php
 */

require_once 'config.php';

echo "=== Ajout d'email aux utilisateurs existants ===\n\n";

$db = getDB();

// Lister tous les utilisateurs sans email
$stmt = $db->query("SELECT id, username, email FROM users WHERE email IS NULL OR email = ''");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($users)) {
    echo "✅ Tous les utilisateurs ont déjà une adresse email.\n";
    exit;
}

echo "Utilisateurs sans email :\n";
foreach ($users as $user) {
    echo "  - ID: {$user['id']}, Username: {$user['username']}\n";
}

echo "\n";

// Demander l'ID de l'utilisateur et son email
if (php_sapi_name() === 'cli') {
    echo "Entrez l'ID de l'utilisateur : ";
    $userId = trim(fgets(STDIN));

    echo "Entrez l'adresse email : ";
    $email = trim(fgets(STDIN));

    // Valider l'email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        die("❌ Adresse email invalide.\n");
    }

    // Mettre à jour
    $stmt = $db->prepare("UPDATE users SET email = ? WHERE id = ?");
    $stmt->execute([$email, $userId]);

    if ($stmt->rowCount() > 0) {
        echo "✅ Email ajouté avec succès pour l'utilisateur ID $userId\n";
    } else {
        echo "❌ Utilisateur non trouvé.\n";
    }
} else {
    echo "⚠️  Ce script doit être exécuté en ligne de commande.\n";
    echo "Exemple : php add_email_to_existing_users.php\n";
}
