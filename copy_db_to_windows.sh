#!/bin/bash

# Script pour copier la base de données SQLite vers Windows
# pour permettre l'accès depuis PHPStorm

echo "╔══════════════════════════════════════════════════════════════╗"
echo "║   Copie de la base de données vers Windows                  ║"
echo "╚══════════════════════════════════════════════════════════════╝"
echo ""

# Chemin de la base WSL
DB_SOURCE="data/accounts.db"

# Destinations possibles sur Windows
WINDOWS_TEMP="/mnt/c/Temp"
WINDOWS_USERPROFILE="/mnt/c/Users/$USER"
PROJECT_ROOT="/mnt/c/Projects/arcade-franck/tools/accounts/data"

# Créer le dossier Temp si nécessaire
if [ ! -d "$WINDOWS_TEMP" ]; then
    mkdir -p "$WINDOWS_TEMP"
fi

# Copier vers C:\Temp\
DEST_PATH="$WINDOWS_TEMP/accounts.db"
cp "$DB_SOURCE" "$DEST_PATH"

if [ $? -eq 0 ]; then
    echo "✅ Base de données copiée avec succès !"
    echo ""
    echo "📂 Chemin Windows pour PHPStorm :"
    echo "   C:\\Temp\\accounts.db"
    echo ""
    echo "📝 Configuration PHPStorm :"
    echo "   1. Ouvrir Database tool window"
    echo "   2. Cliquer sur '+' → Data Source → SQLite"
    echo "   3. File: C:\\Temp\\accounts.db"
    echo "   4. Test Connection → OK"
    echo ""
    echo "⚠️  Note : Cette copie est un snapshot."
    echo "   Pour voir les changements en temps réel, relance ce script."
    echo ""

    # Afficher la taille du fichier
    SIZE=$(ls -lh "$DEST_PATH" | awk '{print $5}')
    echo "📊 Taille : $SIZE"
    echo ""
else
    echo "❌ Erreur lors de la copie"
    exit 1
fi
