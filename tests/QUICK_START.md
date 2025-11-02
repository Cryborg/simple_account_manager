# Quick Start - Tests

## Commandes Essentielles

### Lancer tous les tests

```bash
./test.sh
```

ou

```bash
php tests/run_tests.php
```

### Lancer un test spécifique

```bash
php tests/unit/ConfigTest.php
php tests/integration/AuthTest.php
```

## Workflow Recommandé

### 1. Avant de commit

```bash
./test.sh && git add . && git commit -m "Your message"
```

### 2. Après avoir ajouté une fonctionnalité

1. Écrire les tests pour la nouvelle fonctionnalité
2. Vérifier qu'ils échouent d'abord
3. Implémenter la fonctionnalité
4. Vérifier que les tests passent
5. Commit

### 3. Après avoir corrigé un bug

1. Écrire un test qui reproduit le bug
2. Vérifier qu'il échoue
3. Corriger le bug
4. Vérifier que le test passe
5. Commit avec le test

## Créer un Nouveau Test

```php
<?php
require_once __DIR__ . '/../TestFramework.php';
require_once __DIR__ . '/../../config.php';

$test = new TestFramework();

$test->test('Description claire du comportement', function($t) {
    // Arrange (préparer)
    $input = 'valeur';

    // Act (agir)
    $result = maFonction($input);

    // Assert (vérifier)
    $t->assertEquals('attendu', $result);
});

return $test;
```

## Assertions Courantes

```php
// Égalité stricte
$t->assertEquals($expected, $actual);

// Booléens
$t->assertTrue($condition);
$t->assertFalse($condition);

// Vérifier contenu
$t->assertContains('needle', 'haystack string');
$t->assertContains('value', ['array', 'values']);

// Non vide
$t->assertNotEmpty($value);

// Null
$t->assertNull($value);
$t->assertNotNull($value);

// Instance
$t->assertInstanceOf(PDO::class, $db);

// Tableau
$t->assertCount(5, $array);
$t->assertArrayHasKey('key', $array);
```

## Tests d'Intégration

Utiliser `TestHelper` pour setup/cleanup :

```php
require_once __DIR__ . '/../TestHelper.php';

// Au début du fichier
TestHelper::setupTestDatabase();

// Créer des données de test
$userId = TestHelper::createTestUser('username', 'password', 'email@test.com');
TestHelper::simulateLogin($userId, 'username');

// À la fin du fichier
TestHelper::cleanupTestDatabase();
```

## Débogage

### Voir les valeurs

```php
echo "Debug: " . var_export($variable, true) . "\n";
```

### Garder la base de test

Commenter le cleanup pour inspecter la DB :

```php
// TestHelper::cleanupTestDatabase();
```

Base disponible dans `data/test_accounts.db`

## Résultat Attendu

```
╔══════════════════════════════════════════════════════════════╗
║             Mes Comptes - Test Suite Runner                 ║
╚══════════════════════════════════════════════════════════════╝

┌─────────────────────────────────────────────────────────────┐
│ UNIT TESTS                                                 │
└─────────────────────────────────────────────────────────────┘

🧪 Running Tests...
============================================================
✓ Test 1
✓ Test 2
...
============================================================

╔══════════════════════════════════════════════════════════════╗
║                        FINAL REPORT                          ║
╚══════════════════════════════════════════════════════════════╝

Total Tests Run: 38
✓ Passed: 38
✗ Failed: 0
Duration: 1.28s

🎉 All tests passed!
```

## Git Pre-Commit Hook (Optionnel)

Installer le hook pour exécuter les tests automatiquement avant chaque commit :

```bash
cp tests/fixtures/pre-commit.example .git/hooks/pre-commit
chmod +x .git/hooks/pre-commit
```

Ensuite, à chaque `git commit`, les tests seront exécutés automatiquement.

## Bonnes Pratiques

✅ **DO**
- Exécuter les tests avant chaque commit
- Écrire des tests pour chaque nouvelle fonctionnalité
- Garder les tests simples et lisibles
- Utiliser des noms de tests descriptifs
- Isoler chaque test (pas de dépendances entre tests)

❌ **DON'T**
- Ne pas commit sans exécuter les tests
- Ne pas modifier les tests pour qu'ils passent (corriger le code à la place)
- Ne pas écrire de tests complexes ou longs
- Ne pas tester le framework ou PHP lui-même
- Ne pas dépendre de l'ordre d'exécution des tests

## En Cas de Problème

1. **Tests échouent après modification** : Normal ! Vérifier si c'est un vrai bug ou si les tests doivent être mis à jour
2. **"Database locked"** : S'assurer qu'aucun processus PHP n'utilise la DB de test
3. **Tests lents** : Vérifier qu'on n'utilise pas la DB de prod
4. **Erreurs aléatoires** : Vérifier l'isolation des tests (cleanup correct)

## Ressources

- Documentation complète : `TESTING_README.md`
- Code des tests : `tests/unit/` et `tests/integration/`
- Framework : `tests/TestFramework.php`
- Helper : `tests/TestHelper.php`
