# Accounts - Application de suivi de comptes

## Description
Application web simple de gestion de comptes bancaires permettant de suivre les dépenses et recettes au quotidien.

## Technologies
- **Backend**: PHP 8+ (vanilla, pas de framework)
- **Database**: SQLite
- **Frontend**: HTML5, CSS3 (thème sombre uniquement)
- **Configuration**: Système .env (Laravel-style)

## Configuration (.env)

L'application utilise un fichier `.env` pour gérer toutes les configurations sensibles :

```bash
# Installation
cp .env.example .env
# Éditer .env avec vos vraies valeurs
```

**Variables importantes :**
- `SMTP_*` : Configuration email pour la réinitialisation de mot de passe
- `DB_PATH` : Chemin de la base de données SQLite

Voir `ENV_README.md` pour plus de détails.

## Administration et Migrations

L'application dispose d'un système d'administration complet :

- **Interface admin** : `/admin.php` (accessible uniquement aux admins)
- **Gestion des migrations** : Tracking automatique et exécution via l'interface
- **Auto-migration** : Au premier login de "Cryborg", les migrations sont exécutées automatiquement
- **Alertes** : Les admins sont avertis visuellement quand des migrations sont en attente
- **Droits admin** : Champ `is_admin` dans la table `users`

Voir `ADMIN_README.md` pour la documentation complète.

## Structure du projet
```
accounts/
├── config.php              # Configuration et fonctions utilitaires
├── email_config.php        # Configuration SMTP et envoi d'emails
├── init_db.php            # Script d'initialisation de la DB
├── login.php              # Page de connexion
├── logout.php             # Déconnexion
├── register.php           # Page d'inscription (avec email)
├── forgot_password.php    # Page "Mot de passe oublié"
├── reset_password_form.php # Formulaire de nouveau mot de passe
├── index.php              # Page principale (gestion des transactions)
├── css/
│   └── style.css          # Styles (dark theme, responsive)
├── data/
│   └── accounts.db        # Base SQLite (créée automatiquement)
├── CLAUDE.md              # Cette documentation
└── PASSWORD_RESET_README.md # Documentation réinitialisation mot de passe
```

## Base de données

### Table: `users`
| Champ | Type | Description |
|-------|------|-------------|
| id | INTEGER PRIMARY KEY | ID auto-incrémenté |
| username | TEXT UNIQUE NOT NULL | Nom d'utilisateur |
| email | TEXT | Adresse email (pour réinitialisation mot de passe) |
| password | TEXT NOT NULL | Mot de passe hashé (PASSWORD_DEFAULT) |
| created_at | DATETIME | Date de création |

### Table: `password_resets`
| Champ | Type | Description |
|-------|------|-------------|
| id | INTEGER PRIMARY KEY | ID auto-incrémenté |
| email | TEXT NOT NULL | Email de l'utilisateur |
| token | TEXT NOT NULL | Token unique de réinitialisation |
| expires_at | DATETIME NOT NULL | Date d'expiration du token (1 heure) |
| created_at | DATETIME | Date de création |

### Table: `transactions`
| Champ | Type | Description |
|-------|------|-------------|
| id | INTEGER PRIMARY KEY | ID auto-incrémenté |
| user_id | INTEGER NOT NULL | Référence vers users.id |
| type | TEXT NOT NULL | 'recette' ou 'depense' |
| amount | REAL NOT NULL | Montant de la transaction |
| description | TEXT | Description optionnelle |
| transaction_date | DATE NOT NULL | Date de la transaction |
| created_at | DATETIME | Date d'enregistrement |

## Tests

L'application dispose d'une suite de tests complète pour garantir la non-régression :

```bash
# Exécuter tous les tests
php tests/run_tests.php
# ou
./test.sh
```

**Couverture :**
- 38 tests (20 unitaires + 18 d'intégration)
- Framework de test lightweight (KISS)
- Base de données de test isolée
- Exécution rapide (~1-2s)

Voir `TESTING_README.md` pour la documentation complète des tests.

## Installation

1. Cloner ou copier les fichiers dans le répertoire du projet
2. Initialiser la base de données :
   ```bash
   php init_db.php
   ```
3. Lancer un serveur PHP :
   ```bash
   php -S localhost:8000
   ```
4. Accéder à l'application : http://localhost:8000

**Compte par défaut**: admin / admin

## Fonctionnalités

### Authentification
- Login/logout avec sessions PHP
- Mot de passe hashé (bcrypt via PASSWORD_DEFAULT)
- Protection des pages nécessitant une authentification
- **Réinitialisation de mot de passe par email** :
  - Page "Mot de passe oublié"
  - Envoi d'email avec lien de réinitialisation
  - Token sécurisé avec expiration 1 heure
  - Configuration SMTP personnalisable

### Gestion des transactions
- Ajout de recettes et dépenses
- Champs : type, montant, date, description
- Suppression de transactions (avec confirmation)
- Affichage de l'historique trié par date décroissante

### Affichage
- **Solde actuel** : calcul en temps réel (recettes - dépenses)
- **Historique** : tableau responsive avec :
  - Date formatée (DD/MM/YYYY)
  - Type (badge coloré)
  - Description
  - Montant (+ ou - avec couleur)
  - Action de suppression

## Règles de développement appliquées

### Principes
- **SOLID, DRY, KISS** : code simple et maintenable
- **Pas de rétrocompatibilité** : suppression du code obsolète immédiatement
- **Gestion d'erreur centralisée** : via PDO ERRMODE_EXCEPTION

### Conventions
- **Nommage** :
  - Tables : snake_case (style Laravel)
  - Variables PHP : camelCase
  - Tous les termes techniques en anglais (tables, champs, variables)
  - Interface utilisateur en français
- **Typage** : types stricts pour paramètres et retours quand possible en PHP
- **BDD** : utilisation de prepared statements (sécurité SQL injection)

### Workflow de Développement

⚠️ **RÈGLE IMPORTANTE : Tests après chaque modification**

**Après toute modification de code (fonctionnalité, refactoring, bug fix) :**

```bash
./test.sh
```

**Ou :**

```bash
php tests/run_tests.php
```

✅ **Ne jamais commit sans que tous les tests passent**

**Pourquoi ?**
- Éviter les régressions
- Garantir la stabilité
- Détecter les bugs immédiatement
- Gagner du temps sur le long terme

**Quand lancer les tests ?**
- ✅ Après modification d'une fonction existante
- ✅ Après ajout d'une nouvelle fonctionnalité
- ✅ Après un refactoring
- ✅ Avant chaque commit
- ✅ Avant chaque déploiement

**Workflow recommandé :**

1. Faire les modifications
2. **Lancer les tests** : `./test.sh`
3. Si des tests échouent → corriger
4. Re-tester jusqu'à ce que tout passe
5. Commit
6. Déployer

### CSS
- Thème sombre uniquement
- Variables CSS pour la cohérence des couleurs
- Responsive avec `vw`, `clamp()`, `min()` et media queries
- Minimal usage de `!important`
- Éviter les valeurs fixes

## Sécurité
- Protection CSRF : à implémenter si nécessaire
- Validation des entrées utilisateur
- Prepared statements pour toutes les requêtes SQL
- Hashage des mots de passe
- Sessions sécurisées

## Améliorations récentes
- ✅ Réinitialisation de mot de passe par email
- ✅ Champ email obligatoire à l'inscription
- ✅ Configuration SMTP personnalisable
- ✅ Tokens sécurisés avec expiration

## Améliorations futures possibles
- Vérification d'email à l'inscription (email verification)
- Modification du profil utilisateur
- Export des données (CSV, PDF)
- Filtrage des transactions par date/type
- Graphiques de visualisation
- Catégories de dépenses (déjà implémenté)
- Multi-comptes
- API REST
- Tests unitaires
- Authentification à deux facteurs (2FA)
