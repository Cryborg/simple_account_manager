<?php

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../TestFramework.php';
require_once __DIR__ . '/../TestHelper.php';

$test = new TestFramework();

// Setup
TestHelper::setupTestDatabase();

// Test transaction creation
$test->test('User can create a transaction', function($t) {
    $userId = TestHelper::createTestUser('transuser', 'pass', 'trans@test.com');
    $transId = TestHelper::createTestTransaction($userId, 'depense', 50.0);

    $t->assertTrue($transId > 0);

    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM transactions WHERE id = ?");
    $stmt->execute([$transId]);
    $trans = $stmt->fetch(PDO::FETCH_ASSOC);

    $t->assertEquals($userId, $trans['user_id']);
    $t->assertEquals('depense', $trans['type']);
    $t->assertEquals(50.0, $trans['amount']);
});

// Test transaction types
$test->test('Both recette and depense types work', function($t) {
    $userId = TestHelper::createTestUser('typeuser', 'pass', 'type@test.com');

    $recetteId = TestHelper::createTestTransaction($userId, 'recette', 100.0);
    $depenseId = TestHelper::createTestTransaction($userId, 'depense', 50.0);

    $db = getDB();

    $stmt = $db->prepare("SELECT type FROM transactions WHERE id = ?");
    $stmt->execute([$recetteId]);
    $t->assertEquals('recette', $stmt->fetchColumn());

    $stmt->execute([$depenseId]);
    $t->assertEquals('depense', $stmt->fetchColumn());
});

// Test transaction deletion
$test->test('User can delete their own transaction', function($t) {
    $userId = TestHelper::createTestUser('deluser', 'pass', 'del@test.com');
    $transId = TestHelper::createTestTransaction($userId, 'depense', 30.0);

    $db = getDB();
    $stmt = $db->prepare("DELETE FROM transactions WHERE id = ? AND user_id = ?");
    $stmt->execute([$transId, $userId]);

    $stmt = $db->prepare("SELECT COUNT(*) FROM transactions WHERE id = ?");
    $stmt->execute([$transId]);
    $t->assertEquals(0, $stmt->fetchColumn());
});

// Test user isolation
$test->test('User cannot see other users transactions', function($t) {
    $user1Id = TestHelper::createTestUser('user1', 'pass', 'user1@test.com');
    $user2Id = TestHelper::createTestUser('user2', 'pass', 'user2@test.com');

    TestHelper::createTestTransaction($user1Id, 'depense', 100.0);
    TestHelper::createTestTransaction($user2Id, 'recette', 200.0);

    $db = getDB();
    $stmt = $db->prepare("SELECT COUNT(*) FROM transactions WHERE user_id = ?");

    $stmt->execute([$user1Id]);
    $t->assertEquals(1, $stmt->fetchColumn());

    $stmt->execute([$user2Id]);
    $t->assertEquals(1, $stmt->fetchColumn());
});

// Test balance calculation
$test->test('Balance is calculated correctly', function($t) {
    $userId = TestHelper::createTestUser('balanceuser', 'pass', 'balance@test.com');

    TestHelper::createTestTransaction($userId, 'recette', 500.0);
    TestHelper::createTestTransaction($userId, 'recette', 300.0);
    TestHelper::createTestTransaction($userId, 'depense', 200.0);
    TestHelper::createTestTransaction($userId, 'depense', 150.0);

    $db = getDB();
    $stmt = $db->prepare("
        SELECT
            COALESCE(SUM(CASE WHEN type = 'recette' THEN amount ELSE 0 END), 0) as recettes,
            COALESCE(SUM(CASE WHEN type = 'depense' THEN amount ELSE 0 END), 0) as depenses
        FROM transactions
        WHERE user_id = ?
    ");
    $stmt->execute([$userId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    $t->assertEquals(800.0, $result['recettes']);
    $t->assertEquals(350.0, $result['depenses']);

    $balance = $result['recettes'] - $result['depenses'];
    $t->assertEquals(450.0, $balance);
});

// Test monthly filtering
$test->test('Transactions are filtered by month correctly', function($t) {
    $userId = TestHelper::createTestUser('monthuser', 'pass', 'month@test.com');

    TestHelper::createTestTransaction($userId, 'depense', 100.0, '2025-01-15');
    TestHelper::createTestTransaction($userId, 'depense', 200.0, '2025-01-20');
    TestHelper::createTestTransaction($userId, 'depense', 300.0, '2025-02-10');

    $db = getDB();
    $stmt = $db->prepare("
        SELECT COUNT(*) FROM transactions
        WHERE user_id = ? AND strftime('%Y-%m', transaction_date) = ?
    ");

    $stmt->execute([$userId, '2025-01']);
    $t->assertEquals(2, $stmt->fetchColumn());

    $stmt->execute([$userId, '2025-02']);
    $t->assertEquals(1, $stmt->fetchColumn());
});

// Test transaction with category
$test->test('Transaction can be associated with category', function($t) {
    $userId = TestHelper::createTestUser('catuser', 'pass', 'cat@test.com');

    $db = getDB();
    $stmt = $db->prepare("INSERT INTO categories (user_id, name, type) VALUES (?, ?, ?)");
    $stmt->execute([$userId, 'Alimentation', 'depense']);
    $categoryId = $db->lastInsertId();

    $stmt = $db->prepare("INSERT INTO transactions (user_id, type, amount, category_id, transaction_date) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$userId, 'depense', 50.0, $categoryId, date('Y-m-d')]);
    $transId = $db->lastInsertId();

    $stmt = $db->prepare("SELECT category_id FROM transactions WHERE id = ?");
    $stmt->execute([$transId]);
    $t->assertEquals((int)$categoryId, (int)$stmt->fetchColumn());
});

// Cleanup
TestHelper::cleanupTestDatabase();

return $test;
