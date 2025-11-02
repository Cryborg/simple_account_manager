# Mes Comptes - Application de Gestion Financière

Application web simple de gestion de comptes bancaires permettant de suivre les dépenses et recettes au quotidien.

## 🚀 Démarrage Rapide

```bash
# Installer les dépendances (aucune, PHP vanilla)
# Configurer l'environnement
cp .env.example .env
# Éditer .env avec vos vraies valeurs

# Initialiser la base de données
php init_db.php

# Lancer le serveur
php -S localhost:8000

# Accéder à l'application
# http://localhost:8000
```

**Compte par défaut :** admin / admin

## 📚 Documentation

| Fichier | Description |
|---------|-------------|
| **CLAUDE.md** | Documentation technique complète du projet |

## 🔧 Scripts Utilitaires

### Scripts de Test

| Script | Usage | Description |
|--------|-------|-------------|
| `./test.sh` | Lancer tous les tests | Exécute la suite complète (38 tests) |
| `php tests/run_tests.php` | Alternative | Même chose que test.sh |

### Scripts de Base de Données

| Script | Usage | Description |
|--------|-------|-------------|
| `php check_db.php` | Vérifier la base | Affiche l'état complet de la DB |

### Scripts de Déploiement

| Script | Usage | Description |
|--------|-------|-------------|
| `./deploy.sh` | Déployer en prod | Synchronise via FTP (protège data/ et .env) |

### Scripts PHPStorm (WSL → Windows)

| Script | Usage | Description |
|--------|-------|-------------|
| `./copy_db_to_windows.sh` | Copie unique | Copie la DB vers C:\Temp\ |
| `./watch_db.sh` | Sync auto | Synchronisation continue (laisser tourner) |

## 🏗️ Structure du Projet

```
accounts/
├── 📄 Documentation
│   ├── README.md                    # Ce fichier
│   └── CLAUDE.md                    # Doc technique complète
│
├── 🔧 Configuration
│   ├── .env.example                 # Template de configuration
│   ├── .env                         # Configuration (ne pas committer)
│   ├── config.php                   # Configuration PHP
│   ├── email_config.php             # Configuration SMTP
│   └── migrations.php               # Système de migrations
│
├── 🗄️ Base de Données
│   ├── init_db.php                  # Initialisation
│   ├── data/
│   │   └── accounts.db              # Base SQLite
│   └── migrations/                  # Futures migrations
│
├── 🌐 Pages Publiques
│   ├── login.php
│   ├── register.php
│   ├── forgot_password.php
│   └── reset_password_form.php
│
├── 🔒 Pages Protégées
│   ├── index.php                    # Dashboard principal
│   ├── admin.php                    # Interface admin
│   ├── settings.php                 # Paramètres utilisateur
│   └── logout.php
│
├── 🎨 Assets
│   ├── css/
│   │   └── style.css
│   ├── js/
│   │   └── password-toggle.js
│   └── includes/
│       ├── auth_page_header.php
│       ├── auth_page_footer.php
│       ├── sidebar.php
│       └── migrations_alert.php
│
├── 🧪 Tests
│   ├── test.sh                      # Runner simple
│   ├── run_tests.php                # Runner principal
│   ├── TestFramework.php            # Framework custom
│   ├── TestHelper.php               # Utilitaires
│   ├── unit/                        # Tests unitaires (20)
│   │   ├── ConfigTest.php
│   │   ├── DatabaseTest.php
│   │   └── EmailTest.php
│   └── integration/                 # Tests d'intégration (18)
│       ├── AuthTest.php
│       ├── TransactionTest.php
│       └── CategoryTest.php
│
└── 🛠️ Utilitaires
    ├── check_db.php                 # Vérification DB
    ├── copy_db_to_windows.sh        # Copie DB (PHPStorm)
    └── watch_db.sh                  # Sync DB (PHPStorm)
```

## 🧪 Tests

L'application dispose d'une suite de tests complète :

```bash
# Lancer tous les tests
./test.sh

# Ou
php tests/run_tests.php
```

**Couverture :**
- 38 tests (20 unitaires + 18 intégration)
- Framework lightweight (KISS)
- Base de données de test isolée
- Exécution rapide (~1-2s)

## 🔑 Technologies

- **Backend :** PHP 8+ (vanilla, sans framework)
- **Base de données :** SQLite
- **Frontend :** HTML5, CSS3 (thème sombre)
- **Configuration :** Fichier .env (Laravel-style)

## 🎯 Fonctionnalités

### Authentification
- ✅ Login/Logout avec sessions
- ✅ Inscription avec email
- ✅ Réinitialisation de mot de passe par email
- ✅ Toggle visibilité mot de passe

### Gestion des Transactions
- ✅ Ajout de recettes/dépenses
- ✅ Association avec catégories
- ✅ Filtrage par mois
- ✅ Calcul du solde en temps réel
- ✅ Suppression avec confirmation

### Administration
- ✅ Interface admin (`/admin.php`)
- ✅ Gestion des migrations
- ✅ Statistiques globales
- ✅ Système d'alertes

### Paramètres Utilisateur
- ✅ Affichage année dans les dates
- ✅ Stockage en base de données
- ✅ Interface intuitive

## 🔐 Sécurité

- ✅ Hashage des mots de passe (bcrypt)
- ✅ Prepared statements (SQL injection)
- ✅ Validation des entrées
- ✅ Protection des pages par session
- ✅ Tokens sécurisés pour reset password
- ✅ Configuration sensible dans .env (hors Git)

## 🚀 Déploiement en Production

### Déploiement Automatique (Recommandé)

```bash
# 1. Lancer les tests
./test.sh

# 2. Déployer
./deploy.sh
```

Le script synchronise automatiquement les fichiers via FTP tout en **préservant** :
- ✅ La base de données de prod (`data/`)
- ✅ Le `.env` de prod
- ✅ Les fichiers qui ne doivent pas être en prod (tests, docs, etc.)

**Configuration FTP** dans `.env` :
```env
FTP_HOST=ftp.example.com
FTP_USER=username
FTP_PASSWORD=password
FTP_REMOTE_PATH=/www/path/to/app
```

## 🆘 Support

### Problèmes Courants

**Base de données verrouillée ?**
```bash
pkill -f "php -S"
php check_db.php
```

**PHPStorm ne peut pas lire la DB (WSL) ?**
```bash
./copy_db_to_windows.sh
# Puis dans PHPStorm: C:\Temp\accounts.db
```

## 📝 Principes de Développement

- **DRY** - Don't Repeat Yourself
- **KISS** - Keep It Simple, Stupid
- **SOLID** - Principes de conception objet
- **Tests** - Suite complète pour éviter les régressions
- **Documentation** - Tout est documenté

## 🤝 Contribution

### Workflow recommandé

1. Créer une branche
2. Faire les modifications
3. **Lancer les tests** : `./test.sh`
4. Commit si tous les tests passent
5. Push

### Ajouter une fonctionnalité

1. Écrire les tests en premier
2. Implémenter la fonctionnalité
3. Vérifier que les tests passent
4. Documenter dans CLAUDE.md

## 📜 Licence

Projet personnel - Tous droits réservés
