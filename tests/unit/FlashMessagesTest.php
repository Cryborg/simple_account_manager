<?php

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../TestFramework.php';

$test = new TestFramework();

// Helper pour nettoyer la session entre chaque test
function clearSession() {
    if (session_status() === PHP_SESSION_ACTIVE) {
        $_SESSION = [];
    }
}

// Test setFlash success message
$test->test('setFlash() stores success message in session', function($t) {
    clearSession();

    setFlash('success', 'Transaction ajoutée avec succès !');

    $t->assertTrue(isset($_SESSION['flash_messages']));
    $t->assertEquals(1, count($_SESSION['flash_messages']));
    $t->assertEquals('success', $_SESSION['flash_messages'][0]['type']);
    $t->assertEquals('Transaction ajoutée avec succès !', $_SESSION['flash_messages'][0]['message']);
});

// Test setFlash error message
$test->test('setFlash() stores error message in session', function($t) {
    clearSession();

    setFlash('error', 'Veuillez remplir tous les champs.');

    $t->assertTrue(isset($_SESSION['flash_messages']));
    $t->assertEquals('error', $_SESSION['flash_messages'][0]['type']);
    $t->assertEquals('Veuillez remplir tous les champs.', $_SESSION['flash_messages'][0]['message']);
});

// Test setFlash warning message
$test->test('setFlash() stores warning message in session', function($t) {
    clearSession();

    setFlash('warning', 'Attention : cette action est irréversible.');

    $t->assertTrue(isset($_SESSION['flash_messages']));
    $t->assertEquals('warning', $_SESSION['flash_messages'][0]['type']);
});

// Test setFlash info message
$test->test('setFlash() stores info message in session', function($t) {
    clearSession();

    setFlash('info', 'Nouvelle fonctionnalité disponible.');

    $t->assertTrue(isset($_SESSION['flash_messages']));
    $t->assertEquals('info', $_SESSION['flash_messages'][0]['type']);
});

// Test multiple flash messages
$test->test('Multiple flash messages can be stored', function($t) {
    clearSession();

    setFlash('success', 'Message 1');
    setFlash('error', 'Message 2');
    setFlash('info', 'Message 3');

    $t->assertEquals(3, count($_SESSION['flash_messages']));
    $t->assertEquals('Message 1', $_SESSION['flash_messages'][0]['message']);
    $t->assertEquals('Message 2', $_SESSION['flash_messages'][1]['message']);
    $t->assertEquals('Message 3', $_SESSION['flash_messages'][2]['message']);
});

// Test getFlashMessages retrieves messages
$test->test('getFlashMessages() retrieves all flash messages', function($t) {
    clearSession();

    setFlash('success', 'Test message 1');
    setFlash('error', 'Test message 2');

    $messages = getFlashMessages();

    $t->assertEquals(2, count($messages));
    $t->assertEquals('success', $messages[0]['type']);
    $t->assertEquals('Test message 1', $messages[0]['message']);
    $t->assertEquals('error', $messages[1]['type']);
    $t->assertEquals('Test message 2', $messages[1]['message']);
});

// Test getFlashMessages clears messages after retrieval
$test->test('getFlashMessages() clears messages after retrieval', function($t) {
    clearSession();

    setFlash('success', 'Message to clear');

    // Premier appel : récupère les messages
    $messages = getFlashMessages();
    $t->assertEquals(1, count($messages));

    // Deuxième appel : devrait être vide
    $messagesAgain = getFlashMessages();
    $t->assertEquals(0, count($messagesAgain));

    // Vérifier que la session est propre
    $t->assertFalse(isset($_SESSION['flash_messages']));
});

// Test getFlashMessages with empty session
$test->test('getFlashMessages() returns empty array when no messages', function($t) {
    clearSession();

    $messages = getFlashMessages();

    $t->assertEquals(0, count($messages));
    $t->assertTrue(is_array($messages));
});

// Test flash message structure
$test->test('Flash messages have correct structure', function($t) {
    clearSession();

    setFlash('success', 'Structured message');

    $messages = getFlashMessages();
    $message = $messages[0];

    $t->assertTrue(isset($message['type']));
    $t->assertTrue(isset($message['message']));
    $t->assertTrue(is_string($message['type']));
    $t->assertTrue(is_string($message['message']));
});

// Test flash messages persist across function calls
$test->test('Flash messages persist until retrieved', function($t) {
    clearSession();

    setFlash('info', 'Persistent message');

    // Simuler plusieurs pages/appels sans récupérer les messages
    $t->assertTrue(isset($_SESSION['flash_messages']));
    $t->assertEquals(1, count($_SESSION['flash_messages']));

    // Toujours là
    $t->assertTrue(isset($_SESSION['flash_messages']));

    // Maintenant on les récupère
    $messages = getFlashMessages();
    $t->assertEquals(1, count($messages));

    // Et maintenant ils sont partis
    $t->assertFalse(isset($_SESSION['flash_messages']));
});

// Test adding messages after retrieval
$test->test('Can add new flash messages after retrieval', function($t) {
    clearSession();

    setFlash('success', 'First batch');
    $first = getFlashMessages();
    $t->assertEquals(1, count($first));

    setFlash('error', 'Second batch');
    $second = getFlashMessages();
    $t->assertEquals(1, count($second));
    $t->assertEquals('error', $second[0]['type']);
    $t->assertEquals('Second batch', $second[0]['message']);
});

// Test empty message handling
$test->test('Empty messages are still stored', function($t) {
    clearSession();

    setFlash('info', '');

    $messages = getFlashMessages();
    $t->assertEquals(1, count($messages));
    $t->assertEquals('', $messages[0]['message']);
});

// Test special characters in messages
$test->test('Flash messages handle special characters', function($t) {
    clearSession();

    $specialMessage = 'Message avec <script>alert("XSS")</script> et des accents éàù';
    setFlash('warning', $specialMessage);

    $messages = getFlashMessages();
    $t->assertEquals($specialMessage, $messages[0]['message']);
});

return $test;
