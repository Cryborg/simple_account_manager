# Administration et Migrations

## Système d'administration

L'application dispose d'un système d'administration accessible uniquement aux utilisateurs ayant les droits admin.

### Accès à l'administration

1. **Page d'administration** : `/admin.php`
2. **Accessible uniquement aux admins** : Vous devez être connecté avec un compte ayant `is_admin = 1`
3. **Visible dans la sidebar** : Le lien "Administration" n'apparaît que pour les admins

### Fonctionnalités de l'interface admin

- 📊 **Statistiques** : Vue d'ensemble (utilisateurs, transactions, catégories)
- 🔄 **Gestion des migrations** : Voir et exécuter les migrations de base de données
- ⚠️ **Alertes** : Notification automatique quand des migrations sont en attente

## Système de migrations

### Comment ça fonctionne

1. **Tracking automatique** : Table `migrations_log` qui enregistre les migrations exécutées
2. **Détection intelligente** : Le système détecte automatiquement les migrations en attente
3. **Alertes visuelles** : Les admins voient une alerte orange sur toutes les pages quand des migrations sont en attente
4. **Résilience** : Le code continue de fonctionner même si des migrations ne sont pas exécutées

### Premier déploiement

Pour le premier déploiement, exécuter les migrations manuellement :

**Via CLI (recommandé) :**
```bash
php migrations/add_admin_field.php
php migrations/add_user_settings.php
```

Cela ajoutera les colonnes nécessaires et donnera automatiquement les droits admin à l'utilisateur "Cryborg".

### Ajouter une nouvelle migration

1. **Créer le fichier** dans `/migrations/` :
   ```php
   <?php
   // migrations/ma_nouvelle_migration.php
   require_once __DIR__ . '/../config.php';

   try {
       $db = getDB();

       // Votre code de migration ici
       $db->exec("ALTER TABLE users ADD COLUMN new_field TEXT");

       echo "✓ Migration exécutée avec succès\n";
   } catch (PDOException $e) {
       echo "✗ Erreur : " . $e->getMessage() . "\n";
       exit(1);
   }
   ```

2. **Détection automatique** : La migration apparaît automatiquement dans l'interface admin
3. **Exécution** : Clic sur "Exécuter" dans l'interface ou connexion en tant que Cryborg

### Exécuter manuellement les migrations

**Via l'interface (recommandé) :**
- Aller sur `/admin.php`
- Cliquer sur "▶️ Exécuter toutes les migrations"

**Via CLI (serveur) :**
```bash
php migrations/nom_de_la_migration.php
```

## Structure de la base de données

### Table `users`
```sql
id INTEGER PRIMARY KEY
username TEXT UNIQUE NOT NULL
password TEXT NOT NULL
email TEXT
is_admin INTEGER DEFAULT 0  -- 0 = utilisateur normal, 1 = admin
created_at DATETIME
```

### Table `migrations_log`
```sql
id INTEGER PRIMARY KEY
migration_name TEXT UNIQUE NOT NULL
executed_at DATETIME DEFAULT CURRENT_TIMESTAMP
```

## Gestion des admins

### Promouvoir un utilisateur en admin

```sql
UPDATE users SET is_admin = 1 WHERE username = 'nom_utilisateur';
```

### Révoquer les droits admin

```sql
UPDATE users SET is_admin = 0 WHERE username = 'nom_utilisateur';
```

### Vérifier les admins

```sql
SELECT username, is_admin FROM users WHERE is_admin = 1;
```

## Sécurité

✅ **Bonnes pratiques :**
- L'accès à `/admin.php` est protégé par `requireAdmin()`
- Les migrations ne peuvent être exécutées que par des admins
- Les alertes de migrations ne sont visibles que par les admins

⚠️ **Important :**
- Ne donnez les droits admin qu'aux personnes de confiance
- Les admins ont accès à toutes les migrations et statistiques

## Workflow de déploiement

1. **Push le code** sur le serveur
2. **Exécuter les migrations** via CLI :
   ```bash
   php migrations/add_admin_field.php
   php migrations/add_user_settings.php
   ```
3. **Vérifier dans l'admin** (`/admin.php`) que tout est à jour
4. **Se connecter avec Cryborg** pour accéder à l'interface d'administration

## Résolution de problèmes

### Alerte "Migrations en attente" alors que tout est à jour

**Symptôme :** L'alerte orange apparaît en disant qu'il y a des migrations en attente, mais quand tu exécutes la migration, elle dit "déjà exécutée" ou "colonne existe déjà".

**Cause :** Les migrations ont été exécutées manuellement via CLI mais n'ont pas été enregistrées dans `migrations_log`.

**Solution :**

```bash
php mark_migration_as_done.php
```

Ce script :
1. Liste les migrations en attente
2. Vérifie qu'elles sont déjà exécutées
3. Les marque comme exécutées dans `migrations_log`
4. L'alerte disparaît

**Exemple :**
```
$ php mark_migration_as_done.php

📋 Migrations en attente :
  1. add_admin_field
  2. add_user_settings

Marquer TOUTES ces migrations comme exécutées ? (tapez 'oui') : oui

✅ add_admin_field marquée comme exécutée
✅ add_user_settings marquée comme exécutée

✅ Terminé !
Migrations en attente : 0
```

### Une migration échoue

1. Consulter les logs dans `/admin.php`
2. Vérifier la compatibilité SQLite
3. Exécuter manuellement via CLI pour voir les erreurs détaillées

### Réinitialiser les migrations (Dangereux)

```sql
-- ATTENTION : Remet toutes les migrations en attente
DELETE FROM migrations_log;
```

⚠️ **Utiliser avec précaution** : Cela affichera à nouveau l'alerte de migrations en attente.

Si les migrations sont déjà exécutées, utiliser plutôt `mark_migration_as_done.php`.
