<?php
// Charger les variables d'environnement
require_once __DIR__ . '/env.php';

session_start();

// Configuration de la base de données
define('DB_PATH', __DIR__ . '/' . env('DB_PATH', 'data/accounts.db'));

// Créer le répertoire data s'il n'existe pas
if (!file_exists(__DIR__ . '/data')) {
    mkdir(__DIR__ . '/data', 0755, true);
}

// Connexion à la base de données
function getDB() {
    try {
        $db = new PDO('sqlite:' . DB_PATH);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $db;
    } catch (PDOException $e) {
        die('Erreur de connexion : ' . $e->getMessage());
    }
}

// Vérifier si l'utilisateur est connecté
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Rediriger vers login si non connecté
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

// Vérifier si l'utilisateur est admin
function isAdmin(): bool {
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
}

// Rediriger si non admin
function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        http_response_code(403);
        die('Accès interdit : droits administrateur requis');
    }
}

// Récupérer les paramètres de l'utilisateur connecté
function getUserSettings(): array {
    $defaults = ['show_year_in_dates' => 0];

    if (!isLoggedIn()) {
        return $defaults;
    }

    try {
        $db = getDB();

        // Vérifier si la table existe
        $result = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='user_settings'");
        if (!$result->fetch()) {
            // Table n'existe pas encore (migration non exécutée)
            return $defaults;
        }

        $stmt = $db->prepare("SELECT * FROM user_settings WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $settings = $stmt->fetch(PDO::FETCH_ASSOC);

        // Si aucun paramètre n'existe, créer les valeurs par défaut
        if (!$settings) {
            $stmt = $db->prepare("INSERT INTO user_settings (user_id) VALUES (?)");
            $stmt->execute([$_SESSION['user_id']]);
            return $defaults;
        }

        return $settings;
    } catch (PDOException $e) {
        // En cas d'erreur, retourner les valeurs par défaut
        error_log("Erreur getUserSettings: " . $e->getMessage());
        return $defaults;
    }
}

// Mettre à jour un paramètre utilisateur
function updateUserSetting(string $key, $value): bool {
    if (!isLoggedIn()) {
        return false;
    }

    try {
        $db = getDB();

        // Vérifier si la table existe
        $result = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='user_settings'");
        if (!$result->fetch()) {
            // Table n'existe pas encore
            return false;
        }

        // Vérifier si les paramètres existent
        $stmt = $db->prepare("SELECT id FROM user_settings WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);

        if (!$stmt->fetch()) {
            // Créer les paramètres si inexistants
            $stmt = $db->prepare("INSERT INTO user_settings (user_id) VALUES (?)");
            $stmt->execute([$_SESSION['user_id']]);
        }

        // Mettre à jour le paramètre
        $stmt = $db->prepare("UPDATE user_settings SET {$key} = ?, updated_at = CURRENT_TIMESTAMP WHERE user_id = ?");
        return $stmt->execute([$value, $_SESSION['user_id']]);
    } catch (PDOException $e) {
        error_log("Erreur updateUserSetting: " . $e->getMessage());
        return false;
    }
}

// Formater une date selon les préférences de l'utilisateur
function formatDate(string $date, array $settings = null): string {
    if ($settings === null) {
        $settings = getUserSettings();
    }

    $format = $settings['show_year_in_dates'] ? 'd/m/Y' : 'd/m';
    return date($format, strtotime($date));
}

// Formater un mois et une année en français
function formatMonthYear(string $yearMonth): string {
    $months = [
        'January' => 'Janvier',
        'February' => 'Février',
        'March' => 'Mars',
        'April' => 'Avril',
        'May' => 'Mai',
        'June' => 'Juin',
        'July' => 'Juillet',
        'August' => 'Août',
        'September' => 'Septembre',
        'October' => 'Octobre',
        'November' => 'Novembre',
        'December' => 'Décembre'
    ];

    $timestamp = strtotime($yearMonth . '-01');
    $monthEn = date('F', $timestamp);
    $year = date('Y', $timestamp);

    return $months[$monthEn] . ' ' . $year;
}

// Ajouter un message flash
function setFlash(string $type, string $message): void {
    if (!isset($_SESSION['flash_messages'])) {
        $_SESSION['flash_messages'] = [];
    }
    $_SESSION['flash_messages'][] = ['type' => $type, 'message' => $message];
}

// Récupérer et supprimer les messages flash
function getFlashMessages(): array {
    $messages = $_SESSION['flash_messages'] ?? [];
    unset($_SESSION['flash_messages']);
    return $messages;
}
