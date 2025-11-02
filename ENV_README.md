# Configuration avec .env (Laravel-style)

## Installation

1. **Copier le fichier d'exemple** :
   ```bash
   cp .env.example .env
   ```

2. **Configurer vos variables** dans `.env` :
   ```env
   # Application
   APP_NAME="Mes Comptes"
   APP_ENV=production

   # Database
   DB_PATH=data/accounts.db

   # SMTP Configuration
   SMTP_HOST=mail.example.com
   SMTP_PORT=587
   SMTP_USERNAME=noreply@example.com
   SMTP_PASSWORD=votre-mot-de-passe-smtp
   SMTP_FROM_EMAIL=noreply@example.com
   SMTP_FROM_NAME="Mes Comptes"
   ```

## Variables disponibles

| Variable | Description | Défaut |
|----------|-------------|--------|
| `APP_NAME` | Nom de l'application | "Mes Comptes" |
| `APP_ENV` | Environnement (local/production) | local |
| `DB_PATH` | Chemin relatif de la base SQLite | data/accounts.db |
| `SMTP_HOST` | Serveur SMTP | mail.example.com |
| `SMTP_PORT` | Port SMTP (587=TLS, 465=SSL) | 587 |
| `SMTP_USERNAME` | Nom d'utilisateur SMTP | noreply@example.com |
| `SMTP_PASSWORD` | Mot de passe SMTP | - |
| `SMTP_FROM_EMAIL` | Email d'envoi | noreply@example.com |
| `SMTP_FROM_NAME` | Nom d'expéditeur | Mes Comptes |

## Utilisation dans le code

```php
// Récupérer une variable
$dbPath = env('DB_PATH', 'data/accounts.db');

// Avec valeur par défaut
$appName = env('APP_NAME', 'Mon App');
```

## Sécurité

⚠️ **IMPORTANT** :
- Ne **JAMAIS** commiter le fichier `.env` dans Git
- Le fichier `.env` est déjà ignoré via `.gitignore`
- Seul `.env.example` doit être versionné
- Protégez vos identifiants SMTP

## Migrations de base de données

Les migrations sont gérées via l'interface d'administration (`admin.php`), accessible uniquement aux utilisateurs avec droits admin.

Pour le premier déploiement, les migrations doivent être exécutées manuellement via CLI.

## Déploiement en production

1. Uploadez tous les fichiers **sauf** `.env`
2. Créez un nouveau `.env` sur le serveur :
   ```bash
   cp .env.example .env
   nano .env  # ou vim, ou autre éditeur
   ```
3. Configurez les bonnes valeurs pour la production
4. Protégez le fichier :
   ```bash
   chmod 600 .env
   ```
5. Exécutez les migrations :
   ```bash
   php migrations/add_admin_field.php
   php migrations/add_user_settings.php
   ```
6. Connectez-vous avec le compte "Cryborg" pour accéder à l'administration
