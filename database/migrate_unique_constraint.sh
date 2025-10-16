#!/bin/bash

# Script de migration: Mise à jour de la contrainte d'unicité
# Remplace UNIQUE(compte_id, date_operation, libelle)
# par UNIQUE(compte_id, date_operation, libelle, montant, cb)

set -e

# Couleurs pour l'affichage
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${BLUE}🔄 MIGRATION: Contrainte d'unicité de la table operations${NC}"
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""
echo -e "${YELLOW}Cette migration va :${NC}"
echo -e "  - Supprimer la contrainte UNIQUE(compte_id, date_operation, libelle)"
echo -e "  - Créer une nouvelle contrainte UNIQUE(compte_id, date_operation, libelle, montant, cb)"
echo -e "  - Vérifier qu'il n'y a pas de doublons avec le nouveau critère"
echo ""
echo -e "${BLUE}💡 Pourquoi ce changement ?${NC}"
echo -e "   Permet d'avoir plusieurs opérations avec le même libellé et la même date,"
echo -e "   si elles diffèrent par le montant ou le type (CB ou non)."
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

echo -e "${YELLOW}🔍 Vérification des contraintes actuelles...${NC}"

CURRENT_CONSTRAINT=$(PGPASSWORD="$DB_PASSWORD" psql -h "$DB_HOST" -U "$DB_USER" -d "$DB_NAME" -t -c "SELECT conname FROM pg_constraint WHERE conrelid = 'operations'::regclass AND contype = 'u';" | xargs)

if [ -z "$CURRENT_CONSTRAINT" ]; then
    echo -e "${YELLOW}⚠️  Aucune contrainte d'unicité trouvée${NC}"
else
    echo -e "${BLUE}  Contrainte actuelle : $CURRENT_CONSTRAINT${NC}"
fi

echo ""
read -p "Continuer avec la migration ? (o/N) : " confirm

if [[ ! "$confirm" =~ ^[oO]$ ]]; then
    echo -e "${YELLOW}Opération annulée.${NC}"
    exit 0
fi

echo -e "${YELLOW}🔍 Vérification des doublons potentiels...${NC}"

DUPLICATES=$(PGPASSWORD="$DB_PASSWORD" psql -h "$DB_HOST" -U "$DB_USER" -d "$DB_NAME" -t -c "SELECT COUNT(*) FROM (SELECT compte_id, date_operation, libelle, montant, cb, COUNT(*) as occurrences FROM operations GROUP BY compte_id, date_operation, libelle, montant, cb HAVING COUNT(*) > 1) as doublons;")

if [ "$DUPLICATES" -gt 0 ]; then
    echo -e "${RED}⚠️  ATTENTION : $DUPLICATES doublons détectés avec le nouveau critère !${NC}"
    echo -e "${YELLOW}   La migration échouera si ces doublons ne sont pas résolus.${NC}"
    echo ""
    echo -e "${BLUE}Doublons détectés :${NC}"
    PGPASSWORD="$DB_PASSWORD" psql -h "$DB_HOST" -U "$DB_USER" -d "$DB_NAME" -c "SELECT compte_id, date_operation, libelle, montant, cb, COUNT(*) as occurrences FROM operations GROUP BY compte_id, date_operation, libelle, montant, cb HAVING COUNT(*) > 1 ORDER BY occurrences DESC LIMIT 10;"
    echo ""
    read -p "Voulez-vous continuer malgré les doublons ? (o/N) : " confirm_duplicates
    if [[ ! "$confirm_duplicates" =~ ^[oO]$ ]]; then
        echo -e "${YELLOW}Opération annulée.${NC}"
        exit 0
    fi
else
    echo -e "${GREEN}✅ Aucun doublon détecté${NC}"
fi

echo ""
echo -e "${YELLOW}🚀 Exécution de la migration...${NC}"

# Exécuter la migration
PGPASSWORD="$DB_PASSWORD" psql -h "$DB_HOST" -U "$DB_USER" -d "$DB_NAME" -f migration_update_unique_constraint.sql

if [ $? -eq 0 ]; then
    echo ""
    echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "${GREEN}✅ Migration terminée avec succès !${NC}"
    echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo ""
    echo -e "${BLUE}💡 La contrainte d'unicité a été mise à jour :${NC}"
    echo -e "   Ancien critère : (compte_id, date_operation, libelle)"
    echo -e "   Nouveau critère : (compte_id, date_operation, libelle, montant, cb)"
    echo ""
    echo -e "${BLUE}📌 Cela permet maintenant d'avoir :${NC}"
    echo -e "   - Plusieurs opérations avec le même libellé et date, mais montants différents"
    echo -e "   - Des opérations CB et non-CB avec le même libellé et date"
else
    echo -e "${RED}❌ Erreur lors de la migration${NC}"
    echo -e "${YELLOW}💡 Si l'erreur est due à des doublons, vous devez les supprimer manuellement${NC}"
    exit 1
fi

echo ""
