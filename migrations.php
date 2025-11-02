<?php
/**
 * Système de gestion des migrations
 */

require_once __DIR__ . '/config.php';

class MigrationManager {
    private $db;
    private $migrationsPath;

    public function __construct() {
        $this->db = getDB();
        $this->migrationsPath = __DIR__ . '/migrations/';
        $this->ensureMigrationsTable();
    }

    /**
     * Créer la table de tracking des migrations si elle n'existe pas
     */
    private function ensureMigrationsTable(): void {
        try {
            $this->db->exec("CREATE TABLE IF NOT EXISTS migrations_log (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                migration_name TEXT UNIQUE NOT NULL,
                executed_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )");
        } catch (PDOException $e) {
            // Table existe déjà ou erreur
        }
    }

    /**
     * Récupérer toutes les migrations disponibles
     */
    public function getAvailableMigrations(): array {
        $files = glob($this->migrationsPath . '*.php');
        $migrations = [];

        foreach ($files as $file) {
            $name = basename($file, '.php');
            $migrations[] = [
                'name' => $name,
                'file' => $file,
                'executed' => $this->isMigrationExecuted($name),
            ];
        }

        return $migrations;
    }

    /**
     * Vérifier si une migration a été exécutée
     */
    public function isMigrationExecuted(string $name): bool {
        try {
            $stmt = $this->db->prepare("SELECT id FROM migrations_log WHERE migration_name = ?");
            $stmt->execute([$name]);
            return $stmt->fetch() !== false;
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Récupérer les migrations en attente
     */
    public function getPendingMigrations(): array {
        $all = $this->getAvailableMigrations();
        return array_filter($all, fn($m) => !$m['executed']);
    }

    /**
     * Compter les migrations en attente
     */
    public function getPendingCount(): int {
        return count($this->getPendingMigrations());
    }

    /**
     * Exécuter une migration
     */
    public function executeMigration(string $name): array {
        if ($this->isMigrationExecuted($name)) {
            return ['success' => false, 'message' => 'Migration déjà exécutée'];
        }

        $file = $this->migrationsPath . $name . '.php';
        if (!file_exists($file)) {
            return ['success' => false, 'message' => 'Fichier introuvable'];
        }

        try {
            ob_start();
            include $file;
            $output = ob_get_clean();

            // Marquer comme exécutée
            $stmt = $this->db->prepare("INSERT INTO migrations_log (migration_name) VALUES (?)");
            $stmt->execute([$name]);

            return ['success' => true, 'message' => $output];
        } catch (Exception $e) {
            ob_end_clean();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Exécuter toutes les migrations en attente
     */
    public function runPendingMigrations(): array {
        $pending = $this->getPendingMigrations();
        $results = [];

        foreach ($pending as $migration) {
            $results[$migration['name']] = $this->executeMigration($migration['name']);
        }

        return $results;
    }
}

/**
 * Helper : Récupérer le gestionnaire de migrations
 */
function getMigrationManager(): MigrationManager {
    static $manager = null;
    if ($manager === null) {
        $manager = new MigrationManager();
    }
    return $manager;
}
