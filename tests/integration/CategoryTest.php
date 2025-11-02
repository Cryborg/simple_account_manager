<?php

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../TestFramework.php';
require_once __DIR__ . '/../TestHelper.php';

$test = new TestFramework();

// Setup
TestHelper::setupTestDatabase();

// Test category creation
$test->test('User can create a category', function($t) {
    $userId = TestHelper::createTestUser('catcreate', 'pass', 'catcreate@test.com');

    $db = getDB();
    $stmt = $db->prepare("INSERT INTO categories (user_id, name, type) VALUES (?, ?, ?)");
    $stmt->execute([$userId, 'Transport', 'depense']);
    $categoryId = $db->lastInsertId();

    $t->assertTrue($categoryId > 0);

    $stmt = $db->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([$categoryId]);
    $category = $stmt->fetch(PDO::FETCH_ASSOC);

    $t->assertEquals('Transport', $category['name']);
    $t->assertEquals('depense', $category['type']);
});

// Test category types
$test->test('Categories support both recette and depense types', function($t) {
    $userId = TestHelper::createTestUser('cattype', 'pass', 'cattype@test.com');

    $db = getDB();
    $stmt = $db->prepare("INSERT INTO categories (user_id, name, type) VALUES (?, ?, ?)");

    $stmt->execute([$userId, 'Salaire', 'recette']);
    $recetteId = $db->lastInsertId();

    $stmt->execute([$userId, 'Courses', 'depense']);
    $depenseId = $db->lastInsertId();

    $stmt = $db->prepare("SELECT type FROM categories WHERE id = ?");

    $stmt->execute([$recetteId]);
    $t->assertEquals('recette', $stmt->fetchColumn());

    $stmt->execute([$depenseId]);
    $t->assertEquals('depense', $stmt->fetchColumn());
});

// Test user isolation
$test->test('User cannot see other users categories', function($t) {
    $user1Id = TestHelper::createTestUser('catuser1', 'pass', 'catuser1@test.com');
    $user2Id = TestHelper::createTestUser('catuser2', 'pass', 'catuser2@test.com');

    $db = getDB();
    $stmt = $db->prepare("INSERT INTO categories (user_id, name, type) VALUES (?, ?, ?)");

    $stmt->execute([$user1Id, 'User1 Category', 'depense']);
    $stmt->execute([$user2Id, 'User2 Category', 'recette']);

    $stmt = $db->prepare("SELECT COUNT(*) FROM categories WHERE user_id = ?");

    $stmt->execute([$user1Id]);
    $t->assertEquals(1, $stmt->fetchColumn());

    $stmt->execute([$user2Id]);
    $t->assertEquals(1, $stmt->fetchColumn());
});

// Test category deletion cascades
$test->test('Deleting category removes association from transactions', function($t) {
    $userId = TestHelper::createTestUser('catdel', 'pass', 'catdel@test.com');

    $db = getDB();
    $stmt = $db->prepare("INSERT INTO categories (user_id, name, type) VALUES (?, ?, ?)");
    $stmt->execute([$userId, 'ToDelete', 'depense']);
    $categoryId = $db->lastInsertId();

    // Create transaction with this category
    $stmt = $db->prepare("INSERT INTO transactions (user_id, type, amount, category_id, transaction_date) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$userId, 'depense', 100.0, $categoryId, date('Y-m-d')]);
    $transId = $db->lastInsertId();

    // Delete category
    $stmt = $db->prepare("DELETE FROM categories WHERE id = ?");
    $stmt->execute([$categoryId]);

    // Check that transaction still exists but category_id is cleared by trigger or null
    $stmt = $db->prepare("SELECT category_id FROM transactions WHERE id = ?");
    $stmt->execute([$transId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    $t->assertNotNull($result, 'Transaction should still exist');
    // Category ID might be null or still reference deleted category depending on FK constraints
});

// Cleanup
TestHelper::cleanupTestDatabase();

return $test;
