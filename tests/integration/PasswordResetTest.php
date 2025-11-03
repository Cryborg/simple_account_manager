<?php

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../email_config.php';
require_once __DIR__ . '/../TestFramework.php';
require_once __DIR__ . '/../TestHelper.php';

$test = new TestFramework();

// Setup
TestHelper::setupTestDatabase();

// Test token generation
$test->test('Password reset token is generated with correct length', function($t) {
    $token = generateResetToken();
    $t->assertEquals(64, strlen($token));
});

// Test token uniqueness
$test->test('Generated tokens are unique', function($t) {
    $token1 = generateResetToken();
    $token2 = generateResetToken();
    $token3 = generateResetToken();

    $t->assertTrue($token1 !== $token2);
    $t->assertTrue($token2 !== $token3);
    $t->assertTrue($token1 !== $token3);
});

// Test token creation in database
$test->test('Password reset token is stored in database correctly', function($t) {
    $user = TestHelper::createTestUser('resetuser', 'password123', 'reset@test.com');

    $db = getDB();
    $token = generateResetToken();
    $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));

    $stmt = $db->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
    $stmt->execute(['reset@test.com', $token, $expiresAt]);
    $resetId = $db->lastInsertId();

    $stmt = $db->prepare("SELECT * FROM password_resets WHERE id = ?");
    $stmt->execute([$resetId]);
    $reset = $stmt->fetch(PDO::FETCH_ASSOC);

    $t->assertEquals('reset@test.com', $reset['email']);
    $t->assertEquals($token, $reset['token']);
    $t->assertTrue($resetId > 0);
});

// Test token expiration
$test->test('Expired tokens are detected correctly', function($t) {
    $db = getDB();

    $now = time();

    // Token expiré (il y a 2 heures)
    $expiredToken = generateResetToken();
    $expiredTime = date('Y-m-d H:i:s', $now - 7200); // -2 heures
    $stmt = $db->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
    $stmt->execute(['expired@test.com', $expiredToken, $expiredTime]);

    // Token valide (dans 30 minutes)
    $validToken = generateResetToken();
    $validTime = date('Y-m-d H:i:s', $now + 1800); // +30 minutes
    $stmt->execute(['valid@test.com', $validToken, $validTime]);

    $currentTime = date('Y-m-d H:i:s', $now);

    // Vérifier token expiré
    $stmt = $db->prepare("SELECT COUNT(*) FROM password_resets WHERE token = ? AND expires_at > ?");
    $stmt->execute([$expiredToken, $currentTime]);
    $t->assertEquals(0, (int)$stmt->fetchColumn());

    // Vérifier token valide
    $stmt->execute([$validToken, $currentTime]);
    $t->assertEquals(1, (int)$stmt->fetchColumn());
});

// Test multiple reset requests
$test->test('Multiple password reset requests for same email are allowed', function($t) {
    $email = 'multiple@test.com';
    $db = getDB();

    // Nettoyer les anciens tokens de test
    $db->exec("DELETE FROM password_resets");

    $now = time();

    // Premier reset
    $token1 = generateResetToken();
    $expiresAt = date('Y-m-d H:i:s', $now + 3600);
    $stmt = $db->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
    $stmt->execute([$email, $token1, $expiresAt]);

    // Deuxième reset
    $token2 = generateResetToken();
    $stmt->execute([$email, $token2, $expiresAt]);

    // Les deux devraient exister
    $stmt = $db->prepare("SELECT COUNT(*) FROM password_resets WHERE email = ?");
    $stmt->execute([$email]);
    $t->assertEquals(2, (int)$stmt->fetchColumn());
});

// Test token validation
$test->test('Token validation works correctly', function($t) {
    $email = 'validate@test.com';
    TestHelper::createTestUser('validateuser', 'oldpass', $email);

    $db = getDB();
    $token = generateResetToken();
    $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));

    $stmt = $db->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
    $stmt->execute([$email, $token, $expiresAt]);

    // Vérifier qu'on peut retrouver le token
    $stmt = $db->prepare("SELECT * FROM password_resets WHERE token = ? AND expires_at > ?");
    $stmt->execute([$token, date('Y-m-d H:i:s')]);
    $reset = $stmt->fetch(PDO::FETCH_ASSOC);

    $t->assertEquals($email, $reset['email']);
    $t->assertEquals($token, $reset['token']);
});

// Test password update after reset
$test->test('Password can be updated using valid reset token', function($t) {
    $userId = TestHelper::createTestUser('updateuser', 'oldpassword', 'update@test.com');

    $db = getDB();

    // Récupérer l'email réel créé par TestHelper (qui ajoute un uniqid)
    $stmt = $db->prepare("SELECT email FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $email = $stmt->fetchColumn();

    $t->assertTrue(!empty($email));

    $token = generateResetToken();
    $now = time();
    $expiresAt = date('Y-m-d H:i:s', $now + 3600); // +1 heure

    // Créer le reset token
    $stmt = $db->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
    $stmt->execute([$email, $token, $expiresAt]);

    // Vérifier le token
    $currentTime = date('Y-m-d H:i:s', $now);
    $stmt = $db->prepare("SELECT * FROM password_resets WHERE token = ? AND expires_at > ?");
    $stmt->execute([$token, $currentTime]);
    $reset = $stmt->fetch(PDO::FETCH_ASSOC);

    $t->assertTrue(!empty($reset));

    // Mettre à jour le mot de passe
    $newPassword = password_hash('newpassword123', PASSWORD_DEFAULT);
    $stmt = $db->prepare("UPDATE users SET password = ? WHERE email = ?");
    $result = $stmt->execute([$newPassword, $email]);
    $t->assertTrue($result);

    // Supprimer le token utilisé
    $stmt = $db->prepare("DELETE FROM password_resets WHERE token = ?");
    $stmt->execute([$token]);

    // Vérifier que le mot de passe a changé
    $stmt = $db->prepare("SELECT password FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $hashedPassword = $stmt->fetchColumn();

    $t->assertTrue($hashedPassword !== false);
    $t->assertTrue(password_verify('newpassword123', $hashedPassword));
});

// Test token deletion after use
$test->test('Reset token is deleted after successful password reset', function($t) {
    $email = 'delete@test.com';
    TestHelper::createTestUser('deleteuser', 'password', $email);

    $db = getDB();
    $token = generateResetToken();
    $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));

    $stmt = $db->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
    $stmt->execute([$email, $token, $expiresAt]);

    // Simuler l'utilisation du token
    $stmt = $db->prepare("DELETE FROM password_resets WHERE token = ?");
    $stmt->execute([$token]);

    // Vérifier qu'il n'existe plus
    $stmt = $db->prepare("SELECT COUNT(*) FROM password_resets WHERE token = ?");
    $stmt->execute([$token]);
    $t->assertEquals(0, $stmt->fetchColumn());
});

// Test old tokens cleanup
$test->test('Old expired tokens can be cleaned up', function($t) {
    $db = getDB();

    // Nettoyer les anciens tokens de test
    $db->exec("DELETE FROM password_resets");

    $now = time();

    // Créer plusieurs tokens expirés
    for ($i = 0; $i < 5; $i++) {
        $token = generateResetToken();
        $expiredTime = date('Y-m-d H:i:s', $now - (($i + 2) * 3600)); // -2h, -3h, -4h, etc.
        $stmt = $db->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
        $stmt->execute(["old$i@test.com", $token, $expiredTime]);
    }

    // Créer un token valide
    $validToken = generateResetToken();
    $validTime = date('Y-m-d H:i:s', $now + 3600); // +1 heure
    $stmt = $db->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
    $stmt->execute(['validcleanup@test.com', $validToken, $validTime]);

    // Compter avant suppression
    $stmt = $db->query("SELECT COUNT(*) FROM password_resets");
    $beforeCount = (int)$stmt->fetchColumn();
    $t->assertEquals(6, $beforeCount); // 5 expirés + 1 valide

    // Supprimer les tokens expirés
    $currentTime = date('Y-m-d H:i:s', $now);
    $stmt = $db->prepare("DELETE FROM password_resets WHERE expires_at <= ?");
    $stmt->execute([$currentTime]);

    // Vérifier qu'il ne reste que le token valide
    $stmt = $db->query("SELECT COUNT(*) FROM password_resets");
    $afterCount = (int)$stmt->fetchColumn();
    $t->assertEquals(1, $afterCount);
});

// Cleanup
TestHelper::cleanupTestDatabase();

return $test;
