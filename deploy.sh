#!/bin/bash

# Script de déploiement FTP
# Synchronise les fichiers locaux vers le serveur de production
# ATTENTION : Ne touche JAMAIS à la base de données de prod (data/)

set -e  # Arrêter en cas d'erreur

echo "╔══════════════════════════════════════════════════════════════╗"
echo "║            Déploiement FTP vers Production                   ║"
echo "╚══════════════════════════════════════════════════════════════╝"
echo ""

# Charger les variables d'environnement
if [ ! -f .env ]; then
    echo "❌ Erreur : fichier .env introuvable"
    echo "   Copier .env.example vers .env et configurer les valeurs FTP"
    exit 1
fi

# Fonction pour lire les variables du .env
get_env_var() {
    local var_name=$1
    local value=$(grep "^${var_name}=" .env | cut -d '=' -f2- | tr -d '"' | tr -d "'")
    echo "$value"
}

FTP_HOST=$(get_env_var "FTP_HOST")
FTP_USER=$(get_env_var "FTP_USER")
FTP_PASSWORD=$(get_env_var "FTP_PASSWORD")
FTP_REMOTE_PATH=$(get_env_var "FTP_REMOTE_PATH")

# Vérifier que les variables sont définies
if [ -z "$FTP_HOST" ] || [ -z "$FTP_USER" ] || [ -z "$FTP_PASSWORD" ]; then
    echo "❌ Erreur : Variables FTP manquantes dans .env"
    echo "   Vérifier FTP_HOST, FTP_USER, FTP_PASSWORD"
    exit 1
fi

echo "📡 Configuration FTP :"
echo "   Hôte     : $FTP_HOST"
echo "   User     : $FTP_USER"
echo "   Chemin   : $FTP_REMOTE_PATH"
echo ""

# Vérifier que lftp est installé
if ! command -v lftp &> /dev/null; then
    echo "❌ Erreur : lftp n'est pas installé"
    echo "   Installer avec : sudo apt install lftp"
    exit 1
fi

# Demander confirmation
echo "⚠️  ATTENTION : Cette opération va :"
echo "   • Uploader tous les fichiers modifiés"
echo "   • Supprimer les fichiers qui n'existent plus en local"
echo "   • PRÉSERVER la base de données de prod (data/)"
echo "   • PRÉSERVER le .env de prod"
echo ""
read -p "Continuer ? (tapez 'oui') : " CONFIRM

if [ "$CONFIRM" != "oui" ]; then
    echo "❌ Déploiement annulé"
    exit 0
fi

echo ""
echo "🚀 Déploiement en cours..."
echo ""

# Créer le fichier de commandes lftp
LFTP_SCRIPT=$(mktemp)

cat > "$LFTP_SCRIPT" <<EOF
set ftp:ssl-allow no
set net:timeout 30
set net:max-retries 3
set net:reconnect-interval-base 5

open -u "$FTP_USER","$FTP_PASSWORD" "$FTP_HOST"
lcd $(pwd)
cd $FTP_REMOTE_PATH

# Mirror : synchronisation avec suppression des fichiers obsolètes
# --reverse : de local vers remote
# --delete : supprimer les fichiers qui n'existent plus en local
# --verbose : afficher les détails
# --exclude : exclure certains fichiers/dossiers

mirror --reverse \\
  --delete \\
  --verbose \\
  --exclude-glob .git/ \\
  --exclude-glob .git \\
  --exclude-glob .gitignore \\
  --exclude-glob data/ \\
  --exclude-glob .env \\
  --exclude-glob .env.example \\
  --exclude-glob tests/ \\
  --exclude-glob test.sh \\
  --exclude-glob *.md \\
  --exclude-glob *.txt \\
  --exclude-glob deploy.sh \\
  --exclude-glob copy_db_to_windows.sh \\
  --exclude-glob watch_db.sh \\
  --exclude-glob check_db.php \\
  --exclude-glob mark_migration_as_done.php \\
  --exclude-glob clean_test_users.php \\
  --exclude-glob .vscode/ \\
  --exclude-glob .idea/

bye
EOF

# Exécuter lftp
if lftp -f "$LFTP_SCRIPT"; then
    echo ""
    echo "✅ Déploiement terminé avec succès !"
    echo ""
    echo "📋 Fichiers exclus (protégés) :"
    echo "   • data/ (base de données)"
    echo "   • .env (configuration serveur)"
    echo "   • tests/ (suite de tests)"
    echo "   • *.md, *.txt (documentation)"
    echo "   • Scripts utilitaires"
    echo ""
    echo "🌐 Vérifier le site en production"
else
    echo ""
    echo "❌ Erreur lors du déploiement"
    rm -f "$LFTP_SCRIPT"
    exit 1
fi

# Nettoyer
rm -f "$LFTP_SCRIPT"

echo "✅ Script terminé"
