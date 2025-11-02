<?php
require_once 'config.php';

// Script pour lister les utilisateurs et réinitialiser un mot de passe
echo "=== Gestion des utilisateurs ===\n\n";

$db = getDB();

// Lister tous les utilisateurs
echo "Liste des utilisateurs :\n";
$stmt = $db->query("SELECT id, username FROM users");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($users as $user) {
    echo "  - ID: {$user['id']}, Username: {$user['username']}\n";
}

echo "\n";

// Fonction pour réinitialiser le mot de passe
if ($argc > 2) {
    $username = $argv[1];
    $newPassword = $argv[2];

    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

    $stmt = $db->prepare("UPDATE users SET password = ? WHERE username = ?");
    $stmt->execute([$hashedPassword, $username]);

    if ($stmt->rowCount() > 0) {
        echo "✅ Mot de passe mis à jour pour l'utilisateur : $username\n";
        echo "   Nouveau mot de passe : $newPassword\n";
    } else {
        echo "❌ Utilisateur non trouvé : $username\n";
    }
} else {
    echo "Pour réinitialiser un mot de passe, utilisez :\n";
    echo "php reset_password.php <username> <nouveau_mot_de_passe>\n";
}
