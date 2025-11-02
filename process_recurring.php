<?php
require_once 'config.php';

$db = getDB();

// Récupérer toutes les transactions avec remaining_occurrences > 0
$stmt = $db->query("
    SELECT * FROM transactions
    WHERE remaining_occurrences > 0
    AND parent_transaction_id IS NULL
    ORDER BY transaction_date ASC
");
$recurringTransactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

$today = date('Y-m-d');
$processedCount = 0;

foreach ($recurringTransactions as $transaction) {
    // Calculer la prochaine date en fonction de la périodicité
    $nextDate = null;
    $currentDate = $transaction['transaction_date'];
    
    switch ($transaction['periodicity']) {
        case 'hebdo':
            $nextDate = date('Y-m-d', strtotime($currentDate . ' +7 days'));
            break;
        case 'mensuel':
            $nextDate = date('Y-m-d', strtotime($currentDate . ' +1 month'));
            break;
        case 'annuel':
            $nextDate = date('Y-m-d', strtotime($currentDate . ' +1 year'));
            break;
    }
    
    // Si la prochaine date est passée ou aujourd'hui, créer la transaction
    if ($nextDate && $nextDate <= $today) {
        // Créer la nouvelle occurrence
        $stmt = $db->prepare("
            INSERT INTO transactions (user_id, type, amount, description, transaction_date, periodicity, category_id, recurring_months, remaining_occurrences, parent_transaction_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $newRemainingOccurrences = $transaction['remaining_occurrences'] - 1;
        $newRemainingOccurrences = $newRemainingOccurrences > 0 ? $newRemainingOccurrences : null;
        
        $stmt->execute([
            $transaction['user_id'],
            $transaction['type'],
            $transaction['amount'],
            $transaction['description'],
            $nextDate,
            $transaction['periodicity'],
            $transaction['category_id'],
            $transaction['recurring_months'],
            $newRemainingOccurrences,
            $transaction['id']
        ]);
        
        // Mettre à jour la transaction parent
        $stmt = $db->prepare("
            UPDATE transactions
            SET remaining_occurrences = ?
            WHERE id = ?
        ");
        $stmt->execute([null, $transaction['id']]);
        
        $processedCount++;
        echo "Transaction récurrente générée : {$transaction['description']} pour le {$nextDate}\n";
    }
}

echo "\nTotal : {$processedCount} transaction(s) récurrente(s) générée(s)\n";
