<?php

require_once __DIR__ . '/../../email_config.php';
require_once __DIR__ . '/../TestFramework.php';

$test = new TestFramework();

// Test generateResetToken()
$test->test('generateResetToken() generates unique tokens', function($t) {
    $token1 = generateResetToken();
    $token2 = generateResetToken();

    $t->assertNotEquals($token1, $token2, 'Tokens should be unique');
    $t->assertEquals(64, strlen($token1), 'Token should be 64 characters long');
});

$test->test('generateResetToken() generates alphanumeric tokens', function($t) {
    $token = generateResetToken();
    $t->assertTrue(ctype_alnum($token), 'Token should be alphanumeric');
});

// Test SMTP configuration loading
$test->test('SMTP configuration loads from environment', function($t) {
    $t->assertNotNull(env('SMTP_HOST'));
    $t->assertNotNull(env('SMTP_PORT'));
    $t->assertNotNull(env('SMTP_USERNAME'));
    $t->assertNotNull(env('SMTP_FROM_EMAIL'));
});

return $test;
