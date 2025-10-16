#!/bin/bash

# Script de nettoyage des doublons dans la table operations
# Supprime les doublons en gardant la première occurrence (ID le plus petit)

set -e

# Couleurs pour l'affichage
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${BLUE}🧹 NETTOYAGE: Suppression des doublons dans operations${NC}"
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""
echo -e "${YELLOW}Ce script va :${NC}"
echo -e "  - Identifier les doublons (même compte, date, libellé, montant, CB)"
echo -e "  - Garder la première occurrence (ID le plus petit)"
echo -e "  - Supprimer les occurrences suivantes"
echo ""
echo -e "${BLUE}💡 Pourquoi nettoyer ?${NC}"
echo -e "   Les doublons peuvent survenir lors d'imports multiples du même fichier."
echo -e "   Cette opération permet de nettoyer la base sans perdre de données."
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

echo -e "${YELLOW}🔍 Analyse des doublons...${NC}"

# Compter les doublons
DUPLICATE_COUNT=$(PGPASSWORD="$DB_PASSWORD" psql -h "$DB_HOST" -U "$DB_USER" -d "$DB_NAME" -t -c "
SELECT COUNT(*) 
FROM (
    SELECT id
    FROM (
        SELECT 
            id,
            ROW_NUMBER() OVER (
                PARTITION BY compte_id, date_operation, libelle, montant, cb 
                ORDER BY id
            ) as rn
        FROM operations
    ) t
    WHERE rn > 1
) doublons;
" | xargs)

TOTAL_COUNT=$(PGPASSWORD="$DB_PASSWORD" psql -h "$DB_HOST" -U "$DB_USER" -d "$DB_NAME" -t -c "SELECT COUNT(*) FROM operations;" | xargs)

echo -e "${BLUE}  Opérations totales : $TOTAL_COUNT${NC}"
echo -e "${BLUE}  Doublons à supprimer : $DUPLICATE_COUNT${NC}"
echo ""

if [ "$DUPLICATE_COUNT" -eq 0 ]; then
    echo -e "${GREEN}✅ Aucun doublon détecté. La base est propre !${NC}"
    exit 0
fi

echo -e "${YELLOW}⚠️  Exemples de doublons détectés :${NC}"
PGPASSWORD="$DB_PASSWORD" psql -h "$DB_HOST" -U "$DB_USER" -d "$DB_NAME" << 'EOF'
SELECT 
    compte_id, 
    date_operation, 
    left(libelle, 40) as libelle, 
    montant, 
    cb, 
    COUNT(*) as occurrences
FROM operations 
GROUP BY compte_id, date_operation, libelle, montant, cb 
HAVING COUNT(*) > 1
ORDER BY COUNT(*) DESC, date_operation DESC
LIMIT 10;
EOF

echo ""
read -p "Voulez-vous supprimer ces doublons ? (o/N) : " confirm

if [[ ! "$confirm" =~ ^[oO]$ ]]; then
    echo -e "${YELLOW}Opération annulée.${NC}"
    exit 0
fi

echo ""
echo -e "${YELLOW}🚀 Suppression des doublons en cours...${NC}"

# Exécuter le nettoyage
PGPASSWORD="$DB_PASSWORD" psql -h "$DB_HOST" -U "$DB_USER" -d "$DB_NAME" -f cleanup_duplicates.sql > /tmp/cleanup_output.txt 2>&1

if [ $? -eq 0 ]; then
    echo ""
    echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "${GREEN}✅ Nettoyage terminé avec succès !${NC}"
    echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo ""
    
    # Afficher les statistiques finales
    NEW_TOTAL=$(PGPASSWORD="$DB_PASSWORD" psql -h "$DB_HOST" -U "$DB_USER" -d "$DB_NAME" -t -c "SELECT COUNT(*) FROM operations;" | xargs)
    DELETED=$((TOTAL_COUNT - NEW_TOTAL))
    
    echo -e "${BLUE}📊 Résumé :${NC}"
    echo -e "  Opérations avant : $TOTAL_COUNT"
    echo -e "  Doublons supprimés : $DELETED"
    echo -e "  Opérations après : $NEW_TOTAL"
    echo ""
    echo -e "${GREEN}✅ La base de données est maintenant propre !${NC}"
else
    echo -e "${RED}❌ Erreur lors du nettoyage${NC}"
    echo -e "${YELLOW}Voir les détails dans /tmp/cleanup_output.txt${NC}"
    exit 1
fi

echo ""
