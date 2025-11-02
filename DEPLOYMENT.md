# Guide de Déploiement

## 🚀 Déploiement vers Production

### Prérequis

1. **lftp** doit être installé :
   ```bash
   sudo apt install lftp
   ```

2. **Configuration FTP** dans `.env` :
   ```env
   FTP_HOST=ftp.cluster021.hosting.ovh.net
   FTP_USER=mcrbjsa
   FTP_PASSWORD="VotreMotDePasse"
   FTP_REMOTE_PATH=/www/tools/accounts
   ```

### Workflow de Déploiement

#### 1. Vérifier que tout est OK en local

```bash
# Lancer les tests
./test.sh

# Vérifier la base de données
php check_db.php
```

✅ **Tous les tests doivent passer avant de déployer !**

#### 2. Déployer

```bash
./deploy.sh
```

Le script va :
1. Demander confirmation
2. Synchroniser tous les fichiers vers le serveur
3. Supprimer les fichiers obsolètes sur le serveur
4. **PRÉSERVER** la base de données de prod (`data/`)
5. **PRÉSERVER** le `.env` de prod

#### 3. Vérifier en production

- Accéder au site : https://votre-site.com/tools/accounts
- Vérifier que tout fonctionne
- Tester une connexion
- Vérifier l'admin si migrations

## 🔒 Fichiers Protégés (Jamais Touchés)

Le script de déploiement **n'uploade JAMAIS** :

| Fichier/Dossier | Raison |
|-----------------|--------|
| `data/` | **Base de données de prod** (CRITIQUE) |
| `.env` | Configuration serveur (différente de local) |
| `tests/` | Pas nécessaire en prod |
| `*.md` | Documentation |
| `*.txt` | Documentation |
| `test.sh` | Script de test |
| `deploy.sh` | Script de déploiement |
| `check_db.php` | Utilitaire local |
| `copy_db_to_windows.sh` | Utilitaire local |
| `watch_db.sh` | Utilitaire local |
| `.git/` | Dépôt Git |

## 📋 Ce qui est Déployé

✅ Fichiers uploadés :
- Tous les fichiers PHP (sauf utilitaires)
- CSS, JS
- Includes
- Migrations
- Templates

## ⚠️ Sécurité

### Protection de la Base de Données

Le script exclut **TOUT** le dossier `data/` :
- ✅ La base de prod est 100% protégée
- ✅ Impossible de l'écraser par accident
- ✅ Les données utilisateur sont sécurisées

### Protection du .env

Le `.env` de prod contient :
- SMTP de prod
- Chemins spécifiques au serveur
- Mots de passe différents

❌ **Ne JAMAIS déployer le .env local vers prod**

Le script l'exclut automatiquement.

## 🔧 Fonctionnement du Script

Le script utilise **lftp mirror** :

```bash
mirror --reverse \       # De local vers serveur
  --delete \             # Supprimer fichiers obsolètes
  --exclude data/ \      # NE PAS toucher à data/
  --exclude .env \       # NE PAS toucher à .env
  ...
```

### Options Importantes

| Option | Description |
|--------|-------------|
| `--reverse` | Upload local → serveur (au lieu de download) |
| `--delete` | Supprime les fichiers qui n'existent plus en local |
| `--verbose` | Affiche les détails |
| `--exclude-glob` | Exclut des fichiers/dossiers |

## 🛠️ Dépannage

### Erreur : "lftp: command not found"

```bash
sudo apt install lftp
```

### Erreur : "Variables FTP manquantes"

Vérifier que `.env` contient :
- `FTP_HOST`
- `FTP_USER`
- `FTP_PASSWORD`
- `FTP_REMOTE_PATH`

### Erreur de connexion FTP

1. Vérifier les credentials dans `.env`
2. Vérifier que l'hôte FTP est accessible :
   ```bash
   ping ftp.cluster021.hosting.ovh.net
   ```
3. Tester manuellement :
   ```bash
   lftp -u mcrbjsa ftp.cluster021.hosting.ovh.net
   ```

### Les fichiers ne se mettent pas à jour

1. Vérifier que tu es dans le bon dossier
2. Vérifier `FTP_REMOTE_PATH` dans `.env`
3. Lancer avec `--verbose` pour voir les détails

## 📝 Premier Déploiement

Pour le tout premier déploiement :

1. **Créer le dossier sur le serveur** (si inexistant)
2. **Uploader .env de prod** (manuellement via FTP client)
3. **Exécuter les migrations** (via SSH ou interface admin)
4. **Vérifier** que tout fonctionne
5. **Ensuite** utiliser `deploy.sh` pour les mises à jour

## 🔄 Workflow Complet

```bash
# 1. Développement local
# ... faire des modifications ...

# 2. Tests
./test.sh
# ✅ Tous les tests passent

# 3. Commit
git add .
git commit -m "Description des changements"
git push

# 4. Déploiement
./deploy.sh
# ✅ Fichiers synchronisés

# 5. Vérification
# Tester sur https://votre-site.com/tools/accounts
```

## 📊 Exemple de Sortie

```
╔══════════════════════════════════════════════════════════════╗
║            Déploiement FTP vers Production                   ║
╚══════════════════════════════════════════════════════════════╝

📡 Configuration FTP :
   Hôte     : ftp.cluster021.hosting.ovh.net
   User     : mcrbjsa
   Chemin   : /www/tools/accounts

⚠️  ATTENTION : Cette opération va :
   • Uploader tous les fichiers modifiés
   • Supprimer les fichiers qui n'existent plus en local
   • PRÉSERVER la base de données de prod (data/)
   • PRÉSERVER le .env de prod

Continuer ? (tapez 'oui') : oui

🚀 Déploiement en cours...

Uploading config.php → /www/tools/accounts/config.php
Uploading index.php → /www/tools/accounts/index.php
...

✅ Déploiement terminé avec succès !

📋 Fichiers exclus (protégés) :
   • data/ (base de données)
   • .env (configuration serveur)
   • tests/ (suite de tests)
   • *.md, *.txt (documentation)
   • Scripts utilitaires

🌐 Vérifier le site en production
✅ Script terminé
```

## ⚡ Astuces

### Déploiement Rapide

Si tu es certain de vouloir déployer sans confirmation :

```bash
echo "oui" | ./deploy.sh
```

⚠️ **Attention** : Utiliser avec précaution !

### Voir les Changements Sans Déployer

Pour voir ce qui serait uploadé/supprimé sans vraiment le faire :

```bash
# Modifier deploy.sh temporairement
# Ajouter --dry-run à la commande mirror
```

### Déployer Un Seul Fichier

Pour uploader un seul fichier manuellement :

```bash
lftp -u mcrbjsa,"VotreMotDePasse" ftp.cluster021.hosting.ovh.net
cd /www/tools/accounts
put index.php
bye
```

## 🎯 Résumé

| Commande | Action |
|----------|--------|
| `./deploy.sh` | Déployer vers prod |
| `./test.sh` | Tester avant déploiement |
| `php check_db.php` | Vérifier la base locale |

**Workflow recommandé :** Test → Commit → Deploy → Verify ✅
