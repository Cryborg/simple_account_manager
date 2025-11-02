<?php

/**
 * Test Helper
 * Utilities for integration tests
 */

class TestHelper {
    private static $testDbPath = __DIR__ . '/../data/test_accounts.db';
    private static $originalDbPath = null;

    /**
     * Setup test database
     */
    public static function setupTestDatabase(): void {
        // Backup original DB_PATH
        self::$originalDbPath = $_ENV['DB_PATH'] ?? 'data/accounts.db';

        // Use test database
        $_ENV['DB_PATH'] = 'data/test_accounts.db';
        putenv('DB_PATH=data/test_accounts.db');

        // Delete existing test database
        if (file_exists(self::$testDbPath)) {
            unlink(self::$testDbPath);
        }

        // Create fresh test database
        self::initializeTestDatabase();
    }

    /**
     * Cleanup test database
     */
    public static function cleanupTestDatabase(): void {
        // Restore original DB_PATH
        if (self::$originalDbPath) {
            $_ENV['DB_PATH'] = self::$originalDbPath;
            putenv('DB_PATH=' . self::$originalDbPath);
        }

        // Delete test database
        if (file_exists(self::$testDbPath)) {
            unlink(self::$testDbPath);
        }
    }

    /**
     * Initialize test database with schema
     */
    private static function initializeTestDatabase(): void {
        $db = new PDO('sqlite:' . self::$testDbPath);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Create users table
        $db->exec("CREATE TABLE users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT UNIQUE NOT NULL,
            password TEXT NOT NULL,
            email TEXT,
            is_admin INTEGER DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        // Create transactions table
        $db->exec("CREATE TABLE transactions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            type TEXT NOT NULL,
            amount REAL NOT NULL,
            description TEXT,
            category_id INTEGER,
            transaction_date DATE NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )");

        // Create categories table
        $db->exec("CREATE TABLE categories (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            name TEXT NOT NULL,
            type TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )");

        // Create password_resets table
        $db->exec("CREATE TABLE password_resets (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            email TEXT NOT NULL,
            token TEXT NOT NULL,
            expires_at DATETIME NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        // Create user_settings table
        $db->exec("CREATE TABLE user_settings (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL UNIQUE,
            show_year_in_dates INTEGER DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )");

        // Create migrations_log table
        $db->exec("CREATE TABLE migrations_log (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            migration_name TEXT UNIQUE NOT NULL,
            executed_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
    }

    /**
     * Create a test user
     */
    public static function createTestUser(
        string $username = 'testuser',
        string $password = 'password123',
        string $email = 'test@example.com',
        bool $isAdmin = false
    ): int {
        // Add random suffix to prevent conflicts between test files
        $username = $username . '_' . uniqid();
        $email = str_replace('@', '_' . uniqid() . '@', $email);
        require_once __DIR__ . '/../config.php';

        $db = getDB();
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $db->prepare("INSERT INTO users (username, password, email, is_admin) VALUES (?, ?, ?, ?)");
        $stmt->execute([$username, $hashedPassword, $email, $isAdmin ? 1 : 0]);

        return (int) $db->lastInsertId();
    }

    /**
     * Simulate login by setting session
     */
    public static function simulateLogin(int $userId, string $username, bool $isAdmin = false): void {
        $_SESSION['user_id'] = $userId;
        $_SESSION['username'] = $username;
        $_SESSION['is_admin'] = $isAdmin;
    }

    /**
     * Simulate logout
     */
    public static function simulateLogout(): void {
        unset($_SESSION['user_id']);
        unset($_SESSION['username']);
        unset($_SESSION['is_admin']);
    }

    /**
     * Create a test transaction
     */
    public static function createTestTransaction(
        int $userId,
        string $type = 'depense',
        float $amount = 100.0,
        string $date = null
    ): int {
        require_once __DIR__ . '/../config.php';

        $db = getDB();
        $date = $date ?? date('Y-m-d');

        $stmt = $db->prepare("INSERT INTO transactions (user_id, type, amount, description, transaction_date) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$userId, $type, $amount, 'Test transaction', $date]);

        return (int) $db->lastInsertId();
    }

    /**
     * HTTP request helper (for integration tests)
     */
    public static function httpRequest(string $url, array $postData = null): array {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_COOKIEJAR, '/tmp/test_cookies.txt');
        curl_setopt($ch, CURLOPT_COOKIEFILE, '/tmp/test_cookies.txt');

        if ($postData !== null) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [
            'code' => $httpCode,
            'body' => $response
        ];
    }
}
