#!/bin/bash

# Script de migration: Conversion de la colonne montant vers NUMERIC(12,2)
# Remplace REAL par NUMERIC pour une meilleure précision

set -e

# Couleurs pour l'affichage
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${BLUE}🔄 MIGRATION: Colonne montant vers type NUMERIC(12,2)${NC}"
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""
echo -e "${YELLOW}Cette migration va :${NC}"
echo -e "  - Convertir la colonne 'montant' de REAL vers NUMERIC(12,2)"
echo -e "  - Préserver toutes les données existantes"
echo -e "  - Garantir une précision exacte de 2 décimales"
echo -e "  - Afficher un résumé des modifications"
echo ""
echo -e "${BLUE}💡 Pourquoi NUMERIC(12,2) ?${NC}"
echo -e "   - Précision exacte (pas d'approximation comme avec REAL)"
echo -e "   - 2 décimales garanties pour les montants"
echo -e "   - Standard pour les valeurs monétaires"
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

if [ "$CURRENT_TYPE" = "numeric" ]; then
    echo -e "${GREEN}✅ La colonne est déjà de type NUMERIC. Vérification de la précision...${NC}"
    PRECISION=$(PGPASSWORD="$DB_PASSWORD" psql -h "$DB_HOST" -U "$DB_USER" -d "$DB_NAME" -t -c "SELECT numeric_precision, numeric_scale FROM information_schema.columns WHERE table_name = 'operations' AND column_name = 'montant';" | xargs)
    echo -e "${BLUE}  Précision actuelle : $PRECISION${NC}"
    
    if [ "$PRECISION" = "12 2" ]; then
        echo -e "${GREEN}✅ La colonne a déjà la bonne précision NUMERIC(12,2). Aucune migration nécessaire.${NC}"
        exit 0
    fi
fi

read -p "Continuer avec la migration ? (o/N) : " confirm

if [[ ! "$confirm" =~ ^[oO]$ ]]; then
    echo -e "${YELLOW}Opération annulée.${NC}"
    exit 0
fi

echo -e "${YELLOW}🚀 Exécution de la migration...${NC}"

# Exécuter la migration
PGPASSWORD="$DB_PASSWORD" psql -h "$DB_HOST" -U "$DB_USER" -d "$DB_NAME" -f migration_real_to_numeric.sql

if [ $? -eq 0 ]; then
    echo ""
    echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "${GREEN}✅ Migration terminée avec succès !${NC}"
    echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo ""
    echo -e "${BLUE}💡 La colonne 'montant' utilise maintenant le type NUMERIC(12,2)${NC}"
    echo -e "${BLUE}   Ce type garantit une précision exacte pour les montants monétaires.${NC}"
else
    echo -e "${RED}❌ Erreur lors de la migration${NC}"
    exit 1
fi

echo ""
