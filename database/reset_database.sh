#!/bin/bash

# Script de réinitialisation complète de la base de données
# ATTENTION : Ce script supprime TOUTES les données !

set -e

# Couleurs pour l'affichage
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${RED}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${RED}⚠️  RÉINITIALISATION COMPLÈTE DE LA BASE DE DONNÉES${NC}"
echo -e "${RED}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""
echo -e "${YELLOW}Cette opération va :${NC}"
echo -e "  - Supprimer TOUTES les tables"
echo -e "  - Supprimer TOUTES les données"
echo -e "  - Recréer le schéma de base"
echo ""
echo -e "${RED}Cette action est IRRÉVERSIBLE !${NC}"
echo ""
read -p "Êtes-vous sûr de vouloir continuer ? (tapez 'OUI' en majuscules) : " confirm

if [ "$confirm" != "OUI" ]; then
    echo -e "${YELLOW}Opération annulée.${NC}"
    exit 0
fi

# Charger les variables d'environnement
ENV_FILE="../backend/.env"
if [ ! -f "$ENV_FILE" ]; then
    echo -e "${RED}❌ Fichier .env non trouvé : $ENV_FILE${NC}"
    exit 1
fi

# Charger les variables
export $(grep -v '^#' "$ENV_FILE" | xargs)

echo ""
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

echo -e "${YELLOW}🗑️  Suppression de toutes les tables...${NC}"

# Supprimer toutes les tables, vues et séquences
PGPASSWORD="$DB_PASSWORD" psql -h "$DB_HOST" -U "$DB_USER" -d "$DB_NAME" <<EOF
-- Supprimer les vues
DROP VIEW IF EXISTS vue_stats_imports CASCADE;

-- Supprimer les tables dans l'ordre inverse des dépendances
DROP TABLE IF EXISTS operations CASCADE;
DROP TABLE IF EXISTS imports CASCADE;
DROP TABLE IF EXISTS tags CASCADE;
DROP TABLE IF EXISTS comptes CASCADE;

-- Supprimer les séquences
DROP SEQUENCE IF EXISTS operations_id_seq CASCADE;
DROP SEQUENCE IF EXISTS imports_id_seq CASCADE;
DROP SEQUENCE IF EXISTS tags_id_seq CASCADE;
DROP SEQUENCE IF EXISTS comptes_id_seq CASCADE;
EOF

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ Tables supprimées avec succès${NC}"
else
    echo -e "${RED}❌ Erreur lors de la suppression des tables${NC}"
    exit 1
fi

echo -e "${YELLOW}🔨 Recréation du schéma...${NC}"

# Recréer le schéma
PGPASSWORD="$DB_PASSWORD" psql -h "$DB_HOST" -U "$DB_USER" -d "$DB_NAME" -f schema.sql

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ Schéma de base recréé avec succès${NC}"
else
    echo -e "${RED}❌ Erreur lors de la recréation du schéma${NC}"
    exit 1
fi

echo -e "${YELLOW}🔧 Application des migrations...${NC}"

# Appliquer la vue des statistiques d'imports
PGPASSWORD="$DB_PASSWORD" psql -h "$DB_HOST" -U "$DB_USER" -d "$DB_NAME" -f migration_add_vue_stats_imports.sql

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ Migrations appliquées avec succès${NC}"
else
    echo -e "${RED}❌ Erreur lors de l'application des migrations${NC}"
    exit 1
fi

echo -e "${YELLOW}🧹 Nettoyage des fichiers uploadés...${NC}"

# Nettoyer le dossier uploads (sauf .gitkeep si présent)
UPLOAD_DIR="../backend/uploads"
if [ -d "$UPLOAD_DIR" ]; then
    find "$UPLOAD_DIR" -type f ! -name '.gitkeep' -delete
    echo -e "${GREEN}✅ Fichiers uploadés supprimés${NC}"
else
    echo -e "${YELLOW}⚠️  Dossier uploads non trouvé${NC}"
fi

echo ""
echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${GREEN}✅ Réinitialisation terminée avec succès !${NC}"
echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""
echo -e "${BLUE}📊 État de la base :${NC}"

# Vérifier l'état des tables
echo -e "${BLUE}Tables créées :${NC}"
PGPASSWORD="$DB_PASSWORD" psql -h "$DB_HOST" -U "$DB_USER" -d "$DB_NAME" <<EOF
SELECT 
    schemaname,
    tablename,
    pg_size_pretty(pg_total_relation_size(schemaname||'.'||tablename)) AS size
FROM pg_tables
WHERE schemaname = 'public'
ORDER BY tablename;
EOF

echo ""
echo -e "${BLUE}Vues créées :${NC}"
PGPASSWORD="$DB_PASSWORD" psql -h "$DB_HOST" -U "$DB_USER" -d "$DB_NAME" <<EOF
SELECT 
    schemaname,
    viewname
FROM pg_views
WHERE schemaname = 'public'
ORDER BY viewname;
EOF

echo ""
echo -e "${BLUE}💡 La base de données est maintenant vide et prête à recevoir de nouvelles données.${NC}"
echo -e "${BLUE}   Fonctionnalités disponibles :${NC}"
echo -e "${BLUE}   • Import de fichiers CSV via /api/upload${NC}"
echo -e "${BLUE}   • Gestion des imports via /api/imports${NC}"
echo -e "${BLUE}   • Prévention automatique des doublons${NC}"
echo -e "${BLUE}   • Statistiques détaillées par import${NC}"
echo ""
