<?php
/**
 * Simple .env loader (Laravel-style)
 * Charge les variables d'environnement depuis le fichier .env
 */

function loadEnv($path = __DIR__ . '/.env'): void {
    if (!file_exists($path)) {
        // Si .env n'existe pas, créer à partir de .env.example
        $examplePath = __DIR__ . '/.env.example';
        if (file_exists($examplePath)) {
            copy($examplePath, $path);
        } else {
            return;
        }
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
        // Ignorer les commentaires
        if (strpos(trim($line), '#') === 0) {
            continue;
        }

        // Parser la ligne KEY=VALUE
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);

            // Retirer les guillemets si présents
            if (preg_match('/^"(.*)"$/', $value, $matches)) {
                $value = $matches[1];
            } elseif (preg_match("/^'(.*)'$/", $value, $matches)) {
                $value = $matches[1];
            }

            // Définir la variable d'environnement
            if (!array_key_exists($name, $_ENV)) {
                $_ENV[$name] = $value;
                putenv("$name=$value");
            }
        }
    }
}

/**
 * Récupérer une variable d'environnement
 * @param string $key Nom de la variable
 * @param mixed $default Valeur par défaut si non trouvée
 * @return mixed
 */
function env(string $key, $default = null) {
    $value = getenv($key);

    if ($value === false) {
        return $default;
    }

    // Convertir les valeurs booléennes
    switch (strtolower($value)) {
        case 'true':
        case '(true)':
            return true;
        case 'false':
        case '(false)':
            return false;
        case 'null':
        case '(null)':
            return null;
    }

    return $value;
}

// Charger automatiquement le .env
loadEnv();
