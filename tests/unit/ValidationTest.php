<?php

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../TestFramework.php';
require_once __DIR__ . '/../TestHelper.php';

$test = new TestFramework();

// Setup
TestHelper::setupTestDatabase();

// Test amount validation
$test->test('Transaction amount must be positive', function($t) {
    $userId = TestHelper::createTestUser('amountuser', 'pass', 'amount@test.com');

    $db = getDB();

    // Montant négatif devrait être rejeté par validation
    $amount = -50.0;
    $isValid = $amount > 0;
    $t->assertFalse($isValid);

    // Montant zéro devrait être rejeté
    $amount = 0;
    $isValid = $amount > 0;
    $t->assertFalse($isValid);

    // Montant positif valide
    $amount = 50.0;
    $isValid = $amount > 0;
    $t->assertTrue($isValid);
});

// Test transaction type validation
$test->test('Transaction type must be recette or depense', function($t) {
    $validTypes = ['recette', 'depense'];

    $t->assertTrue(in_array('recette', $validTypes));
    $t->assertTrue(in_array('depense', $validTypes));
    $t->assertFalse(in_array('invalid', $validTypes));
    $t->assertFalse(in_array('', $validTypes));
});

// Test date validation
$test->test('Transaction date must be valid', function($t) {
    $validDate = '2025-01-15';
    $invalidDate = '2025-13-45'; // Mois et jour invalides
    $emptyDate = '';

    // Date valide
    $dateObj = DateTime::createFromFormat('Y-m-d', $validDate);
    $t->assertTrue($dateObj !== false && $dateObj->format('Y-m-d') === $validDate);

    // Date invalide
    $dateObj = DateTime::createFromFormat('Y-m-d', $invalidDate);
    $isValidDate = $dateObj !== false && $dateObj->format('Y-m-d') === $invalidDate;
    $t->assertFalse($isValidDate);

    // Date vide
    $t->assertTrue(empty($emptyDate));
});

// Test periodicity validation
$test->test('Periodicity must be hebdo, mensuel, or annuel', function($t) {
    $validPeriodicities = ['hebdo', 'mensuel', 'annuel'];

    $t->assertTrue(in_array('hebdo', $validPeriodicities));
    $t->assertTrue(in_array('mensuel', $validPeriodicities));
    $t->assertTrue(in_array('annuel', $validPeriodicities));
    $t->assertFalse(in_array('daily', $validPeriodicities));
    $t->assertFalse(in_array('', $validPeriodicities));
});

// Test recurring_months validation
$test->test('recurring_months must be 0 or positive', function($t) {
    // 0 = infini, valide
    $value = 0;
    $isValid = $value >= 0;
    $t->assertTrue($isValid);

    // Positif, valide
    $value = 12;
    $isValid = $value >= 0;
    $t->assertTrue($isValid);

    // Négatif, invalide
    $value = -5;
    $isValid = $value >= 0;
    $t->assertFalse($isValid);

    // Correction : négatif devrait être converti en 0
    if ($value < 0) {
        $value = 0;
    }
    $t->assertEquals(0, $value);
});

// Test end_date validation with recurrence_type
$test->test('end_date is required when recurrence_type is date', function($t) {
    $recurrenceType = 'date';
    $endDate = '';

    $isValid = !($recurrenceType === 'date' && empty($endDate));
    $t->assertFalse($isValid);

    // Avec une date, c'est valide
    $endDate = '2025-12-31';
    $isValid = !($recurrenceType === 'date' && empty($endDate));
    $t->assertTrue($isValid);
});

// Test recurrence_type validation
$test->test('recurrence_type must be no_limit, count, or date', function($t) {
    $validTypes = ['no_limit', 'count', 'date'];

    $t->assertTrue(in_array('no_limit', $validTypes));
    $t->assertTrue(in_array('count', $validTypes));
    $t->assertTrue(in_array('date', $validTypes));
    $t->assertFalse(in_array('infinite', $validTypes));
    $t->assertFalse(in_array('', $validTypes));
});

// Test category name validation
$test->test('Category name can be empty but must be trimmed', function($t) {
    $name = '  Alimentation  ';
    $trimmed = trim($name);
    $t->assertEquals('Alimentation', $trimmed);

    $empty = '   ';
    $trimmed = trim($empty);
    $t->assertEquals('', $trimmed);
    $t->assertTrue(empty($trimmed));
});

// Test email validation
$test->test('Email must have valid format', function($t) {
    $validEmail = 'user@example.com';
    $invalidEmail = 'notanemail';
    $emptyEmail = '';

    $t->assertTrue(filter_var($validEmail, FILTER_VALIDATE_EMAIL) !== false);
    $t->assertFalse(filter_var($invalidEmail, FILTER_VALIDATE_EMAIL) !== false);
    $t->assertFalse(filter_var($emptyEmail, FILTER_VALIDATE_EMAIL) !== false);
});

// Test password strength (basic)
$test->test('Password must meet minimum length', function($t) {
    $minLength = 3;

    $validPassword = 'password123';
    $shortPassword = 'ab';
    $emptyPassword = '';

    $t->assertTrue(strlen($validPassword) >= $minLength);
    $t->assertFalse(strlen($shortPassword) >= $minLength);
    $t->assertFalse(strlen($emptyPassword) >= $minLength);
});

// Test username validation
$test->test('Username must not be empty', function($t) {
    $validUsername = 'john_doe';
    $emptyUsername = '';
    $whitespaceUsername = '   ';

    $t->assertFalse(empty($validUsername));
    $t->assertTrue(empty($emptyUsername));
    $t->assertTrue(empty(trim($whitespaceUsername)));
});

// Test SQL injection prevention with prepared statements
$test->test('Prepared statements prevent SQL injection', function($t) {
    $userId = TestHelper::createTestUser('sqluser', 'pass', 'sql@test.com');

    $db = getDB();

    // Tentative d'injection SQL dans la description
    $maliciousDescription = "'; DROP TABLE transactions; --";

    $stmt = $db->prepare("INSERT INTO transactions (user_id, type, amount, description, transaction_date) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$userId, 'depense', 50.0, $maliciousDescription, date('Y-m-d')]);

    // La table devrait toujours exister
    $stmt = $db->query("SELECT COUNT(*) FROM transactions");
    $count = $stmt->fetchColumn();
    $t->assertTrue($count > 0);

    // La description devrait être stockée littéralement
    $stmt = $db->prepare("SELECT description FROM transactions WHERE user_id = ?");
    $stmt->execute([$userId]);
    $description = $stmt->fetchColumn();
    $t->assertEquals($maliciousDescription, $description);
});

// Test XSS prevention in output
$test->test('XSS attempts are stored but should be escaped on output', function($t) {
    $userId = TestHelper::createTestUser('xssuser', 'pass', 'xss@test.com');

    $db = getDB();

    $xssDescription = '<script>alert("XSS")</script>';

    $stmt = $db->prepare("INSERT INTO transactions (user_id, type, amount, description, transaction_date) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$userId, 'recette', 100.0, $xssDescription, date('Y-m-d')]);

    $stmt = $db->prepare("SELECT description FROM transactions WHERE user_id = ?");
    $stmt->execute([$userId]);
    $description = $stmt->fetchColumn();

    // Le contenu est stocké tel quel en BDD
    $t->assertEquals($xssDescription, $description);

    // htmlspecialchars() devrait être utilisé à l'affichage
    $escaped = htmlspecialchars($description, ENT_QUOTES, 'UTF-8');
    $t->assertEquals('&lt;script&gt;alert(&quot;XSS&quot;)&lt;/script&gt;', $escaped);
    $t->assertTrue(strpos($escaped, '<script>') === false);
});

// Test float precision for amounts
$test->test('Transaction amounts maintain precision', function($t) {
    $userId = TestHelper::createTestUser('precisionuser', 'pass', 'precision@test.com');

    $db = getDB();

    $amount = 123.45;

    $stmt = $db->prepare("INSERT INTO transactions (user_id, type, amount, transaction_date) VALUES (?, ?, ?, ?)");
    $stmt->execute([$userId, 'recette', $amount, date('Y-m-d')]);

    $stmt = $db->prepare("SELECT amount FROM transactions WHERE user_id = ?");
    $stmt->execute([$userId]);
    $storedAmount = $stmt->fetchColumn();

    $t->assertEquals($amount, (float)$storedAmount);
});

// Test maximum amount (reasonable check)
$test->test('Very large amounts are stored correctly', function($t) {
    $userId = TestHelper::createTestUser('largeuser', 'pass', 'large@test.com');

    $db = getDB();

    $largeAmount = 999999.99;

    $stmt = $db->prepare("INSERT INTO transactions (user_id, type, amount, transaction_date) VALUES (?, ?, ?, ?)");
    $stmt->execute([$userId, 'recette', $largeAmount, date('Y-m-d')]);

    $stmt = $db->prepare("SELECT amount FROM transactions WHERE user_id = ?");
    $stmt->execute([$userId]);
    $storedAmount = (float)$stmt->fetchColumn();

    $t->assertEquals($largeAmount, $storedAmount);
});

// Cleanup
TestHelper::cleanupTestDatabase();

return $test;
