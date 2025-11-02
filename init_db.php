<?php
require_once 'config.php';

$db = getDB();

// Créer la table des utilisateurs
$db->exec("CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT UNIQUE NOT NULL,
    password TEXT NOT NULL,
    email TEXT,
    is_admin INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

// Créer la table des catégories
$db->exec("CREATE TABLE IF NOT EXISTS categories (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    name TEXT NOT NULL,
    type TEXT NOT NULL CHECK(type IN ('recette', 'depense')),
    icon TEXT DEFAULT '📁',
    color TEXT DEFAULT '#4a9eff',
    is_default INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(user_id, name, type),
    FOREIGN KEY (user_id) REFERENCES users(id)
)");

// Créer la table des transactions
$db->exec("CREATE TABLE IF NOT EXISTS transactions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    type TEXT NOT NULL CHECK(type IN ('recette', 'depense')),
    amount REAL NOT NULL,
    description TEXT,
    transaction_date DATE NOT NULL,
    category_id INTEGER,
    periodicity TEXT DEFAULT 'mensuel',
    recurring_months INTEGER DEFAULT 1,
    remaining_occurrences INTEGER,
    parent_transaction_id INTEGER,
    end_date DATE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (category_id) REFERENCES categories(id)
)");

// Créer la table des paramètres utilisateur
$db->exec("CREATE TABLE IF NOT EXISTS user_settings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL UNIQUE,
    show_year_in_dates INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)");

// Créer un utilisateur par défaut (admin/admin) si aucun utilisateur n'existe
$stmt = $db->query("SELECT COUNT(*) FROM users");
if ($stmt->fetchColumn() == 0) {
    $stmt = $db->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
    $stmt->execute(['admin', password_hash('admin', PASSWORD_DEFAULT)]);
    echo "Utilisateur par défaut créé : admin / admin\n";

    $adminId = $db->lastInsertId();

    // Créer les catégories par défaut pour l'admin
    $defaultCategories = [
        // Catégories de dépenses
        ['Santé', 'depense', '💊', '#f44336'],
        ['Épargne', 'depense', '💰', '#4caf50'],
        ['Impôts', 'depense', '🏛️', '#ff9800'],
        ['Assurance', 'depense', '🛡️', '#9c27b0'],
        ['Abonnement', 'depense', '📱', '#2196f3'],
        ['Maison', 'depense', '🏠', '#795548'],
        ['Crédit', 'depense', '🏦', '#ff5722'],
        ['Transport', 'depense', '🚗', '#607d8b'],
        ['Courses', 'depense', '🛒', '#4caf50'],
        ['Loisirs', 'depense', '🎮', '#9c27b0'],
        // Catégories de recettes
        ['Salaire', 'recette', '💼', '#4caf50'],
        ['Aide sociale', 'recette', '🤝', '#8bc34a'],
        ['Remboursement', 'recette', '💸', '#00bcd4'],
        ['Autre', 'recette', '💰', '#607d8b'],
    ];

    $stmt = $db->prepare("INSERT INTO categories (user_id, name, type, icon, color, is_default) VALUES (?, ?, ?, ?, ?, 1)");
    foreach ($defaultCategories as $cat) {
        $stmt->execute([$adminId, $cat[0], $cat[1], $cat[2], $cat[3]]);
    }

    echo "Catégories par défaut créées\n";
}

echo "Base de données initialisée avec succès !\n";
