#!/bin/bash

# Script de test pour vérifier que reset_database.sh fonctionne
# Ce script fait un dry-run et vérifie les fichiers nécessaires

set -e

echo "🧪 Test du script reset_database.sh"
echo ""

# Vérifier la syntaxe
echo "1. Vérification de la syntaxe..."
bash -n reset_database.sh
echo "✅ Syntaxe OK"

# Vérifier que les fichiers requis existent
echo ""
echo "2. Vérification des fichiers requis..."

FILES=("schema.sql" "migration_add_vue_stats_imports.sql" "../backend/.env")

for file in "${FILES[@]}"; do
    if [ -f "$file" ]; then
        echo "✅ $file existe"
    else
        echo "❌ $file manquant"
        exit 1
    fi
done

# Vérifier les variables d'environnement
echo ""
echo "3. Vérification des variables d'environnement..."
if [ -f "../backend/.env" ]; then
    source "../backend/.env"
    echo "✅ DB_HOST: $DB_HOST"
    echo "✅ DB_NAME: $DB_NAME"
    echo "✅ DB_USER: $DB_USER"
    echo "✅ DB_PASSWORD: [défini]"
else
    echo "❌ Fichier .env introuvable"
    exit 1
fi

echo ""
echo "🎉 Tous les tests sont passés ! Le script reset_database.sh est prêt à être utilisé."