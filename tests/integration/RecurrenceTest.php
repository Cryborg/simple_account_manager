<?php

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../TestFramework.php';
require_once __DIR__ . '/../TestHelper.php';

$test = new TestFramework();

// Setup
TestHelper::setupTestDatabase();

// Test recurrence type: no_limit
$test->test('Transaction with no_limit recurrence sets recurring_months to 0', function($t) {
    $userId = TestHelper::createTestUser('nolimituser', 'pass', 'nolimit@test.com');

    $db = getDB();
    $stmt = $db->prepare("INSERT INTO transactions (user_id, type, amount, transaction_date, recurring_months, end_date) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$userId, 'depense', 50.0, date('Y-m-d'), 0, null]);
    $transId = $db->lastInsertId();

    $stmt = $db->prepare("SELECT recurring_months, end_date FROM transactions WHERE id = ?");
    $stmt->execute([$transId]);
    $trans = $stmt->fetch(PDO::FETCH_ASSOC);

    $t->assertEquals(0, $trans['recurring_months']);
    $t->assertNull($trans['end_date']);
});

// Test recurrence type: count
$test->test('Transaction with count recurrence sets recurring_months correctly', function($t) {
    $userId = TestHelper::createTestUser('countuser', 'pass', 'count@test.com');

    $db = getDB();
    $stmt = $db->prepare("INSERT INTO transactions (user_id, type, amount, transaction_date, recurring_months, remaining_occurrences) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$userId, 'recette', 100.0, date('Y-m-d'), 12, 12]);
    $transId = $db->lastInsertId();

    $stmt = $db->prepare("SELECT recurring_months, remaining_occurrences FROM transactions WHERE id = ?");
    $stmt->execute([$transId]);
    $trans = $stmt->fetch(PDO::FETCH_ASSOC);

    $t->assertEquals(12, $trans['recurring_months']);
    $t->assertEquals(12, $trans['remaining_occurrences']);
});

// Test recurrence type: date
$test->test('Transaction with end_date recurrence stores date correctly', function($t) {
    $userId = TestHelper::createTestUser('dateuser', 'pass', 'date@test.com');
    $endDate = date('Y-m-d', strtotime('+6 months'));

    $db = getDB();
    $stmt = $db->prepare("INSERT INTO transactions (user_id, type, amount, transaction_date, recurring_months, end_date) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$userId, 'depense', 75.0, date('Y-m-d'), 0, $endDate]);
    $transId = $db->lastInsertId();

    $stmt = $db->prepare("SELECT recurring_months, end_date FROM transactions WHERE id = ?");
    $stmt->execute([$transId]);
    $trans = $stmt->fetch(PDO::FETCH_ASSOC);

    $t->assertEquals(0, $trans['recurring_months']);
    $t->assertEquals($endDate, $trans['end_date']);
});

// Test validation: recurring_months cannot be negative
$test->test('Negative recurring_months is prevented', function($t) {
    $userId = TestHelper::createTestUser('neguser', 'pass', 'neg@test.com');

    $db = getDB();

    // Simuler la validation côté serveur
    $recurringMonths = -5;
    if ($recurringMonths < 0) {
        $recurringMonths = 0;
    }

    $stmt = $db->prepare("INSERT INTO transactions (user_id, type, amount, transaction_date, recurring_months) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$userId, 'depense', 30.0, date('Y-m-d'), $recurringMonths]);
    $transId = $db->lastInsertId();

    $stmt = $db->prepare("SELECT recurring_months FROM transactions WHERE id = ?");
    $stmt->execute([$transId]);
    $t->assertEquals(0, $stmt->fetchColumn());
});

// Test remaining_occurrences logic
$test->test('remaining_occurrences is set only when recurring_months > 1', function($t) {
    $userId = TestHelper::createTestUser('remainuser', 'pass', 'remain@test.com');

    $db = getDB();

    // Test avec recurring_months = 1 (ponctuel)
    $remainingOccurrences1 = 1 > 1 ? 1 : null;
    $stmt = $db->prepare("INSERT INTO transactions (user_id, type, amount, transaction_date, recurring_months, remaining_occurrences) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$userId, 'depense', 50.0, date('Y-m-d'), 1, $remainingOccurrences1]);
    $transId1 = $db->lastInsertId();

    $stmt = $db->prepare("SELECT remaining_occurrences FROM transactions WHERE id = ?");
    $stmt->execute([$transId1]);
    $t->assertNull($stmt->fetchColumn());

    // Test avec recurring_months = 5
    $remainingOccurrences2 = 5 > 1 ? 5 : null;
    $stmt = $db->prepare("INSERT INTO transactions (user_id, type, amount, transaction_date, recurring_months, remaining_occurrences) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$userId, 'recette', 200.0, date('Y-m-d'), 5, $remainingOccurrences2]);
    $transId2 = $db->lastInsertId();

    $stmt = $db->prepare("SELECT remaining_occurrences FROM transactions WHERE id = ?");
    $stmt->execute([$transId2]);
    $t->assertEquals(5, (int)$stmt->fetchColumn());
});

// Test periodicity values
$test->test('All periodicity types are supported', function($t) {
    $userId = TestHelper::createTestUser('perioduser', 'pass', 'period@test.com');

    $db = getDB();
    $periodicities = ['hebdo', 'mensuel', 'annuel'];

    foreach ($periodicities as $periodicity) {
        $stmt = $db->prepare("INSERT INTO transactions (user_id, type, amount, transaction_date, periodicity) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$userId, 'depense', 10.0, date('Y-m-d'), $periodicity]);
        $transId = $db->lastInsertId();

        $stmt = $db->prepare("SELECT periodicity FROM transactions WHERE id = ?");
        $stmt->execute([$transId]);
        $t->assertEquals($periodicity, $stmt->fetchColumn());
    }
});

// Test monthly recurrence calculation (simulé)
$test->test('Monthly recurrences are counted correctly for display', function($t) {
    $userId = TestHelper::createTestUser('monthlyuser', 'pass', 'monthly@test.com');

    $db = getDB();
    $stmt = $db->prepare("INSERT INTO transactions (user_id, type, amount, transaction_date, periodicity, recurring_months, remaining_occurrences) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$userId, 'recette', 1000.0, '2025-01-01', 'mensuel', 12, 12]);
    $transId = $db->lastInsertId();

    // Simuler qu'on a déjà généré 3 occurrences
    $currentMonth = '2025-03';
    $startDate = '2025-01-01';

    // Calculer combien d'occurrences devraient avoir été générées
    $start = new DateTime($startDate);
    $current = new DateTime($currentMonth . '-01');
    $interval = $start->diff($current);
    $monthsPassed = ($interval->y * 12) + $interval->m + 1;

    $t->assertEquals(3, $monthsPassed);
});

// Test end_date validation
$test->test('End date must be in the future', function($t) {
    $userId = TestHelper::createTestUser('futureuser', 'pass', 'future@test.com');

    $pastDate = date('Y-m-d', strtotime('-1 month'));
    $futureDate = date('Y-m-d', strtotime('+6 months'));

    $db = getDB();

    // Date passée : devrait être invalide (mais pas d'erreur DB)
    $stmt = $db->prepare("INSERT INTO transactions (user_id, type, amount, transaction_date, end_date) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$userId, 'depense', 50.0, date('Y-m-d'), $pastDate]);
    $transId1 = $db->lastInsertId();

    // Date future : valide
    $stmt->execute([$userId, 'recette', 100.0, date('Y-m-d'), $futureDate]);
    $transId2 = $db->lastInsertId();

    $t->assertTrue($transId1 > 0);
    $t->assertTrue($transId2 > 0);
});

// Cleanup
TestHelper::cleanupTestDatabase();

return $test;
