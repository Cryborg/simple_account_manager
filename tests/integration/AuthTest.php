<?php

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../TestFramework.php';
require_once __DIR__ . '/../TestHelper.php';

$test = new TestFramework();

// Setup
TestHelper::setupTestDatabase();

// Test user registration
$test->test('User can register with valid credentials', function($t) {
    $userId = TestHelper::createTestUser('newuser', 'password123', 'new@example.com');
    $t->assertTrue($userId > 0, 'User ID should be positive');

    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    $t->assertContains('newuser', $user['username'], 'Username should contain base name');
    $t->assertContains('new', $user['email'], 'Email should contain base email');
    $t->assertTrue(password_verify('password123', $user['password']));
});

// Test duplicate username prevention
$test->test('Cannot register duplicate username', function($t) {
    TestHelper::createTestUser('duplicate', 'pass123', 'user1@example.com');

    $db = getDB();
    $stmt = $db->prepare("INSERT INTO users (username, password, email) VALUES (?, ?, ?)");

    try {
        $stmt->execute(['duplicate', password_hash('pass456', PASSWORD_DEFAULT), 'user2@example.com']);
        $t->assertTrue(false, 'Should throw exception for duplicate username');
    } catch (PDOException $e) {
        $t->assertTrue(true, 'Exception thrown as expected');
    }
});

// Test login simulation
$test->test('User session is set correctly on login', function($t) {
    TestHelper::simulateLogout();
    $userId = TestHelper::createTestUser('logintest', 'password', 'login@test.com');

    TestHelper::simulateLogin($userId, 'logintest', false);

    $t->assertEquals($userId, $_SESSION['user_id']);
    $t->assertEquals('logintest', $_SESSION['username']);
    $t->assertFalse($_SESSION['is_admin']);
});

// Test admin privileges
$test->test('Admin user has correct privileges', function($t) {
    $adminId = TestHelper::createTestUser('admin', 'adminpass', 'admin@test.com', true);

    $db = getDB();
    $stmt = $db->prepare("SELECT is_admin FROM users WHERE id = ?");
    $stmt->execute([$adminId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    $t->assertEquals(1, $user['is_admin']);

    TestHelper::simulateLogin($adminId, 'admin', true);
    $t->assertTrue(isAdmin());
});

// Test password reset token generation
$test->test('Password reset token is created correctly', function($t) {
    require_once __DIR__ . '/../../email_config.php';

    $userId = TestHelper::createTestUser('resetuser', 'oldpass', 'reset@test.com');

    // Get the actual email that was created (with unique suffix)
    $db = getDB();
    $stmt = $db->prepare("SELECT email FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $actualEmail = $stmt->fetchColumn();

    $token = generateResetToken();
    $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));

    $stmt = $db->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
    $stmt->execute([$actualEmail, $token, $expiresAt]);

    $stmt = $db->prepare("SELECT * FROM password_resets WHERE email = ?");
    $stmt->execute([$actualEmail]);
    $reset = $stmt->fetch(PDO::FETCH_ASSOC);

    $t->assertEquals($token, $reset['token']);
    $t->assertTrue(strtotime($reset['expires_at']) > time());
});

// Test getUserSettings
$test->test('getUserSettings returns defaults for new user', function($t) {
    $userId = TestHelper::createTestUser('settingsuser', 'pass', 'settings@test.com');
    TestHelper::simulateLogin($userId, 'settingsuser');

    $settings = getUserSettings();
    $t->assertArrayHasKey('show_year_in_dates', $settings);
    $t->assertEquals(0, $settings['show_year_in_dates']);
});

// Test updateUserSetting
$test->test('User settings can be updated', function($t) {
    $userId = TestHelper::createTestUser('updateuser', 'pass', 'update@test.com');
    TestHelper::simulateLogin($userId, 'updateuser');

    updateUserSetting('show_year_in_dates', 1);

    $settings = getUserSettings();
    $t->assertEquals(1, $settings['show_year_in_dates']);
});

// Cleanup
TestHelper::cleanupTestDatabase();

return $test;
