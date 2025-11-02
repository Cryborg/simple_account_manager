<?php

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../TestFramework.php';

$test = new TestFramework();

// Test database tables exist
$test->test('Users table exists', function($t) {
    $db = getDB();
    $result = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='users'");
    $t->assertNotNull($result->fetch(), 'Users table should exist');
});

$test->test('Transactions table exists', function($t) {
    $db = getDB();
    $result = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='transactions'");
    $t->assertNotNull($result->fetch(), 'Transactions table should exist');
});

$test->test('Categories table exists', function($t) {
    $db = getDB();
    $result = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='categories'");
    $t->assertNotNull($result->fetch(), 'Categories table should exist');
});

$test->test('Password_resets table exists', function($t) {
    $db = getDB();
    $result = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='password_resets'");
    $t->assertNotNull($result->fetch(), 'Password_resets table should exist');
});

$test->test('User_settings table exists', function($t) {
    $db = getDB();
    $result = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='user_settings'");
    $t->assertNotNull($result->fetch(), 'User_settings table should exist');
});

$test->test('Migrations_log table exists', function($t) {
    $db = getDB();
    $result = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='migrations_log'");
    $t->assertNotNull($result->fetch(), 'Migrations_log table should exist');
});

// Test users table structure
$test->test('Users table has required columns', function($t) {
    $db = getDB();
    $columns = $db->query("PRAGMA table_info(users)")->fetchAll(PDO::FETCH_ASSOC);
    $columnNames = array_column($columns, 'name');

    $requiredColumns = ['id', 'username', 'password', 'email', 'is_admin', 'created_at'];
    foreach ($requiredColumns as $col) {
        $t->assertTrue(in_array($col, $columnNames), "Users table should have column: {$col}");
    }
});

// Test transactions table structure
$test->test('Transactions table has required columns', function($t) {
    $db = getDB();
    $columns = $db->query("PRAGMA table_info(transactions)")->fetchAll(PDO::FETCH_ASSOC);
    $columnNames = array_column($columns, 'name');

    $requiredColumns = ['id', 'user_id', 'type', 'amount', 'description', 'transaction_date', 'created_at'];
    foreach ($requiredColumns as $col) {
        $t->assertTrue(in_array($col, $columnNames), "Transactions table should have column: {$col}");
    }
});

// Test password hashing
$test->test('Password hashing works correctly', function($t) {
    $password = 'test_password_123';
    $hash = password_hash($password, PASSWORD_DEFAULT);

    $t->assertTrue(password_verify($password, $hash), 'Password should verify correctly');
    $t->assertFalse(password_verify('wrong_password', $hash), 'Wrong password should not verify');
});

return $test;
