#!/bin/bash

# Script de migration: Conversion de la colonne montant vers REAL
# ATTENTION : Ce script modifie la structure de la base de données

set -e

# Couleurs pour l'affichage
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${BLUE}🔄 MIGRATION: Colonne montant vers type REAL${NC}"
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""
echo -e "${YELLOW}Cette migration va :${NC}"
echo -e "  - Convertir la colonne 'montant' de DECIMAL(12,2) vers REAL"
echo -e "  - Préserver toutes les données existantes"
echo -e "  - Afficher un résumé des modifications"
echo ""

# Charger les variables d'environnement
ENV_FILE="../backend/.env"
if [ ! -f "$ENV_FILE" ]; then
    echo -e "${RED}❌ Fichier .env non trouvé : $ENV_FILE${NC}"
    exit 1
fi

# Charger les variables
export $(grep -v '^#' "$ENV_FILE" | xargs)

echo -e "${BLUE}📋 Configuration détectée :${NC}"
echo -e "  Base de données : $DB_NAME"
echo -e "  Utilisateur : $DB_USER"
echo -e "  Hôte : $DB_HOST"
echo ""

# Demander le mot de passe si nécessaire
if [ -z "$DB_PASSWORD" ]; then
    read -sp "Mot de passe PostgreSQL : " DB_PASSWORD
    echo ""
    export PGPASSWORD="$DB_PASSWORD"
else
    export PGPASSWORD="$DB_PASSWORD"
fi

echo -e "${YELLOW}🔍 Vérification du type actuel de la colonne...${NC}"

CURRENT_TYPE=$(PGPASSWORD="$DB_PASSWORD" psql -h "$DB_HOST" -U "$DB_USER" -d "$DB_NAME" -t -c "SELECT data_type FROM information_schema.columns WHERE table_name = 'operations' AND column_name = 'montant';" | xargs)

echo -e "${BLUE}  Type actuel : $CURRENT_TYPE${NC}"

if [ "$CURRENT_TYPE" = "real" ]; then
    echo -e "${GREEN}✅ La colonne est déjà de type REAL. Aucune migration nécessaire.${NC}"
    exit 0
fi

read -p "Continuer avec la migration ? (o/N) : " confirm

if [[ ! "$confirm" =~ ^[oO]$ ]]; then
    echo -e "${YELLOW}Opération annulée.${NC}"
    exit 0
fi

echo -e "${YELLOW}🚀 Exécution de la migration...${NC}"

# Exécuter la migration
PGPASSWORD="$DB_PASSWORD" psql -h "$DB_HOST" -U "$DB_USER" -d "$DB_NAME" -f migration_montant_to_real.sql

if [ $? -eq 0 ]; then
    echo ""
    echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "${GREEN}✅ Migration terminée avec succès !${NC}"
    echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo ""
    echo -e "${BLUE}💡 La colonne 'montant' utilise maintenant le type REAL${NC}"
    echo -e "${BLUE}   Ce type est plus performant pour les calculs numériques.${NC}"
else
    echo -e "${RED}❌ Erreur lors de la migration${NC}"
    exit 1
fi

echo ""
