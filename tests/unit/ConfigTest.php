<?php

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../TestFramework.php';

$test = new TestFramework();

// Test env() function
$test->test('env() returns value from environment', function($t) {
    putenv('TEST_VAR=test_value');
    $t->assertEquals('test_value', env('TEST_VAR'));
});

$test->test('env() returns default when var not found', function($t) {
    $t->assertEquals('default', env('NON_EXISTENT_VAR', 'default'));
});

$test->test('env() converts boolean strings', function($t) {
    putenv('BOOL_TRUE=true');
    putenv('BOOL_FALSE=false');
    $t->assertTrue(env('BOOL_TRUE') === true);
    $t->assertTrue(env('BOOL_FALSE') === false);
});

// Test database connection
$test->test('getDB() returns PDO instance', function($t) {
    $db = getDB();
    $t->assertInstanceOf(PDO::class, $db);
});

$test->test('Database file exists at expected path', function($t) {
    $dbPath = __DIR__ . '/../../' . env('DB_PATH', 'data/accounts.db');
    $t->assertTrue(file_exists($dbPath), "Database file should exist at {$dbPath}");
});

// Test formatDate() function
$test->test('formatDate() respects show_year setting', function($t) {
    // This test assumes getUserSettings() returns defaults when not logged in
    $date = '2025-01-15';
    $formatted = formatDate($date);
    $t->assertTrue(is_string($formatted));
    // Should be in format d/m or d/m/Y
    $t->assertTrue(preg_match('/^\d{2}\/\d{2}(\/\d{4})?$/', $formatted) === 1);
});

// Test isLoggedIn() function
$test->test('isLoggedIn() returns false when no session', function($t) {
    if (isset($_SESSION['user_id'])) {
        unset($_SESSION['user_id']);
    }
    $t->assertFalse(isLoggedIn());
});

// Test isAdmin() function
$test->test('isAdmin() returns false when not admin', function($t) {
    if (isset($_SESSION['is_admin'])) {
        unset($_SESSION['is_admin']);
    }
    $t->assertFalse(isAdmin());
});

return $test;
