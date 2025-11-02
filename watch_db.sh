#!/bin/bash

# Script de synchronisation automatique de la base de données
# Surveille les changements et copie automatiquement vers Windows

echo "╔══════════════════════════════════════════════════════════════╗"
echo "║   Synchronisation automatique de la base de données          ║"
echo "╚══════════════════════════════════════════════════════════════╝"
echo ""
echo "🔄 Mode surveillance activé..."
echo "📁 Source: data/accounts.db"
echo "📁 Destination: C:\\Temp\\accounts.db"
echo ""
echo "Appuyez sur Ctrl+C pour arrêter"
echo ""

DB_SOURCE="data/accounts.db"
DEST_PATH="/mnt/c/Temp/accounts.db"
LAST_MODIFIED=0

# Copie initiale
cp "$DB_SOURCE" "$DEST_PATH"
echo "✅ Copie initiale effectuée"

# Boucle de surveillance
while true; do
    # Récupérer la date de dernière modification
    CURRENT_MODIFIED=$(stat -c %Y "$DB_SOURCE" 2>/dev/null)

    if [ "$CURRENT_MODIFIED" != "$LAST_MODIFIED" ]; then
        # La base a été modifiée, copier
        cp "$DB_SOURCE" "$DEST_PATH"
        TIMESTAMP=$(date '+%H:%M:%S')
        echo "🔄 [$TIMESTAMP] Base synchronisée"
        LAST_MODIFIED=$CURRENT_MODIFIED
    fi

    # Attendre 2 secondes avant de revérifier
    sleep 2
done
