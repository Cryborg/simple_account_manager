<?php
require_once '../config.php';
requireLogin();

header('Content-Type: application/json');

$db = getDB();
$userId = $_SESSION['user_id'];

// Récupérer les catégories distinctes pour un type donné
$type = $_GET['type'] ?? null;

if (!$type || !in_array($type, ['recette', 'depense'])) {
    echo json_encode(['error' => 'Type invalide']);
    exit;
}

$stmt = $db->prepare("SELECT name, icon FROM categories WHERE user_id = ? AND type = ?");
$stmt->execute([$userId, $type]);
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Trier en PHP pour gérer correctement les accents
if (class_exists('Collator')) {
    $collator = new Collator('fr_FR');
    usort($categories, function($a, $b) use ($collator) {
        return $collator->compare($a['name'], $b['name']);
    });
} else {
    // Fallback si l'extension intl n'est pas disponible
    usort($categories, function($a, $b) {
        return strnatcasecmp(
            strtr($a['name'], 'ÀÁÂÃÄÅàáâãäåÈÉÊËèéêëÌÍÎÏìíîïÒÓÔÕÖØòóôõöøÙÚÛÜùúûüÝýÿ', 'AAAAAAaaaaaaEEEEeeeeIIIIiiiiOOOOOOooooooUUUUuuuuYyy'),
            strtr($b['name'], 'ÀÁÂÃÄÅàáâãäåÈÉÊËèéêëÌÍÎÏìíîïÒÓÔÕÖØòóôõöøÙÚÛÜùúûüÝýÿ', 'AAAAAAaaaaaaEEEEeeeeIIIIiiiiOOOOOOooooooUUUUuuuuYyy')
        );
    });
}

echo json_encode($categories);
