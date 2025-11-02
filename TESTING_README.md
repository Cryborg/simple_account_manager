# Suite de Tests - Mes Comptes

## Vue d'ensemble

Cette suite de tests garantit la stabilité et la non-régression de l'application. Elle suit les principes **KISS** (Keep It Simple, Stupid) et **DRY** (Don't Repeat Yourself).

## Structure

```
tests/
├── run_tests.php           # 🚀 Runner principal (exécute tous les tests)
├── TestFramework.php       # Framework de test lightweight
├── TestHelper.php          # Utilitaires pour les tests d'intégration
├── unit/                   # Tests unitaires
│   ├── ConfigTest.php      # Tests des fonctions config.php
│   ├── EmailTest.php       # Tests des fonctions email
│   └── DatabaseTest.php    # Tests de la structure DB
└── integration/            # Tests d'intégration
    ├── AuthTest.php        # Tests d'authentification
    ├── TransactionTest.php # Tests des transactions
    └── CategoryTest.php    # Tests des catégories
```

## Installation

Aucune installation requise ! Les tests utilisent uniquement PHP vanilla sans dépendances externes.

## Exécution

### Lancer tous les tests

```bash
php tests/run_tests.php
```

ou

```bash
./tests/run_tests.php
```

### Lancer un fichier de test spécifique

```bash
php tests/unit/ConfigTest.php
```

## Types de tests

### 1. Tests Unitaires (`unit/`)

Testent des fonctions isolées sans dépendances externes.

**ConfigTest.php** :
- Fonction `env()` avec valeurs par défaut
- Conversion de booléens
- Connexion à la base de données
- Fonction `formatDate()`
- Fonctions `isLoggedIn()` et `isAdmin()`

**EmailTest.php** :
- Génération de tokens uniques
- Configuration SMTP

**DatabaseTest.php** :
- Existence des tables
- Structure des colonnes
- Hashage des mots de passe

### 2. Tests d'Intégration (`integration/`)

Testent les interactions entre composants avec une base de données de test.

**AuthTest.php** :
- Inscription utilisateur
- Prévention des doublons
- Connexion / déconnexion
- Privilèges admin
- Réinitialisation de mot de passe
- Paramètres utilisateur

**TransactionTest.php** :
- Création de transactions
- Types (recette / dépense)
- Suppression
- Isolation entre utilisateurs
- Calcul de balance
- Filtrage par mois
- Association avec catégories

**CategoryTest.php** :
- Création de catégories
- Types (recette / dépense)
- Isolation entre utilisateurs
- Suppression

## Framework de Test

### Assertions Disponibles

```php
$test->assertTrue($condition, $message);
$test->assertFalse($condition, $message);
$test->assertEquals($expected, $actual, $message);
$test->assertNotEquals($expected, $actual, $message);
$test->assertContains($needle, $haystack, $message);
$test->assertNotEmpty($value, $message);
$test->assertNull($value, $message);
$test->assertNotNull($value, $message);
$test->assertInstanceOf($class, $object, $message);
$test->assertCount($expected, $array, $message);
$test->assertArrayHasKey($key, $array, $message);
```

### Écrire un nouveau test

```php
<?php
require_once __DIR__ . '/../TestFramework.php';
require_once __DIR__ . '/../../config.php';

$test = new TestFramework();

$test->test('Description du test', function($t) {
    // Arrange
    $value = 'test';

    // Act
    $result = someFunction($value);

    // Assert
    $t->assertEquals('expected', $result);
});

return $test;
```

## Base de Données de Test

Les tests d'intégration utilisent une base de données SQLite séparée (`data/test_accounts.db`) qui est :
- **Créée automatiquement** avant chaque suite de tests
- **Supprimée automatiquement** après chaque suite
- **Isolée** de la base de production

### Utilitaires TestHelper

```php
// Setup/Cleanup
TestHelper::setupTestDatabase();
TestHelper::cleanupTestDatabase();

// Création de données de test
$userId = TestHelper::createTestUser('username', 'password', 'email@test.com', $isAdmin);
$transId = TestHelper::createTestTransaction($userId, 'depense', 100.0, '2025-01-15');

// Simulation de session
TestHelper::simulateLogin($userId, 'username', $isAdmin);
TestHelper::simulateLogout();
```

## Intégration Continue (CI/CD)

Pour intégrer dans un pipeline CI/CD :

```bash
#!/bin/bash
# run_ci_tests.sh

echo "Running test suite..."
php tests/run_tests.php

if [ $? -eq 0 ]; then
    echo "✓ All tests passed"
    exit 0
else
    echo "✗ Tests failed"
    exit 1
fi
```

## Bonnes Pratiques

### 1. Tester avant de commit

```bash
php tests/run_tests.php && git commit -m "Your message"
```

### 2. Écrire un test pour chaque bug fix

Avant de corriger un bug :
1. Écrire un test qui reproduit le bug
2. Vérifier que le test échoue
3. Corriger le bug
4. Vérifier que le test passe

### 3. Garder les tests rapides

- Tests unitaires : < 0.1s chacun
- Tests d'intégration : < 1s chacun
- Suite complète : < 5s

### 4. Tests indépendants

Chaque test doit pouvoir s'exécuter seul sans dépendre d'autres tests.

### 5. Noms descriptifs

```php
// ✓ BON
$test->test('User cannot register with duplicate username', ...);

// ✗ MAUVAIS
$test->test('Test 1', ...);
```

## Couverture de Code

### Composants Testés

| Composant | Couverture | Fichiers de Test |
|-----------|------------|------------------|
| Authentification | ✓ | AuthTest.php, ConfigTest.php |
| Transactions | ✓ | TransactionTest.php |
| Catégories | ✓ | CategoryTest.php |
| Email/Tokens | ✓ | EmailTest.php, AuthTest.php |
| Base de données | ✓ | DatabaseTest.php |
| Configuration | ✓ | ConfigTest.php |

### Non Testé (Frontend)

- Formulaires HTML
- JavaScript (password-toggle.js)
- CSS

## Débogage

### Afficher plus de détails

Modifier `TestFramework.php` pour ajouter du debug :

```php
echo "Debug: " . var_export($variable, true) . "\n";
```

### Garder la base de test

Commenter la ligne cleanup dans un test :

```php
// TestHelper::cleanupTestDatabase();
```

La base sera dans `data/test_accounts.db` pour inspection.

## FAQ

### Q: Les tests modifient-ils ma base de production ?

**Non.** Les tests d'intégration utilisent une base séparée (`test_accounts.db`).

### Q: Puis-je exécuter les tests en local ?

**Oui.** Aucune dépendance externe requise, juste PHP.

### Q: Comment tester une nouvelle fonctionnalité ?

1. Créer un nouveau fichier dans `tests/unit/` ou `tests/integration/`
2. Écrire les tests
3. Exécuter avec `php tests/run_tests.php`

### Q: Les tests sont-ils obligatoires avant le déploiement ?

**Fortement recommandé.** Ils garantissent qu'aucune régression n'est introduite.

## Maintenance

### Ajouter un nouveau test

1. Créer le fichier dans `tests/unit/` ou `tests/integration/`
2. Nommer le fichier avec le suffixe `Test.php`
3. Utiliser `TestFramework` et retourner l'instance
4. Le runner le détectera automatiquement

### Mettre à jour les tests après modification

Si tu modifies la structure de la base ou une fonction :
1. Mettre à jour les tests correspondants
2. Exécuter `php tests/run_tests.php` pour vérifier
3. Commit les tests avec le code

## Exemples de Sortie

### Succès

```
🧪 Running Tests...
============================================================

✓ env() returns value from environment
✓ env() returns default when var not found
✓ getDB() returns PDO instance

============================================================
Results: 3 passed, 0 failed

🎉 All tests passed!
```

### Échec

```
🧪 Running Tests...
============================================================

✓ env() returns value from environment
✗ User can register with valid credentials
  → Expected true, got false

============================================================
Results: 1 passed, 1 failed

⚠️  Some tests failed. Please review the output above.
```

## Support

En cas de problème avec les tests :
1. Vérifier que toutes les dépendances sont à jour
2. S'assurer que `data/` est accessible en écriture
3. Vérifier les permissions du fichier de test
4. Consulter les logs d'erreur PHP
