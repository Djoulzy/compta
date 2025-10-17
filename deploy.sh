#!/bin/bash

# Script de déploiement pour l'application de gestion comptable

echo "🚀 Déploiement de l'application de gestion comptable..."

# Couleurs pour les messages
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Aller dans le répertoire du projet
cd /data/www/compta

# 1. Build du frontend React
echo -e "\n${YELLOW}📦 Build du frontend React...${NC}"
cd frontend
npm run build

if [ $? -ne 0 ]; then
    echo -e "${RED}❌ Erreur lors du build du frontend${NC}"
    exit 1
fi

echo -e "${GREEN}✅ Frontend buildé avec succès${NC}"

# 2. Copier les fichiers vers /var/www/compta
echo -e "\n${YELLOW}📋 Copie des fichiers vers /var/www/compta...${NC}"
cd ..

sudo mkdir -p /var/www/compta/frontend/build
sudo mkdir -p /var/www/compta/backend
sudo mkdir -p /var/log/nginx/compta

# Copier le backend
sudo rm -rf /var/www/compta/backend/*
sudo cp -r backend/* /var/www/compta/backend/
sudo mkdir -p /var/www/compta/backend/uploads

# Copier le fichier .env s'il existe
if [ -f backend/.env ]; then
    sudo cp backend/.env /var/www/compta/backend/.env
    echo -e "${GREEN}✅ Fichier .env copié${NC}"
else
    echo -e "${YELLOW}⚠️  Fichier .env non trouvé, utilisez .env.example comme modèle${NC}"
fi

# Copier le frontend build
sudo rm -rf /var/www/compta/frontend/build/*
sudo cp -r frontend/build/* /var/www/compta/frontend/build/

# Copier la configuration Nginx
sudo cp nginx-production.conf /etc/nginx/sites-available/compta.conf

# Activer le site si pas déjà fait
if [ ! -L /etc/nginx/sites-enabled/compta.conf ]; then
    sudo ln -s /etc/nginx/sites-available/compta.conf /etc/nginx/sites-enabled/compta.conf
fi

echo -e "${GREEN}✅ Fichiers copiés${NC}"

# 3. Définir les bonnes permissions
echo -e "\n${YELLOW}🔐 Configuration des permissions...${NC}"
sudo chown -R jules:jules /var/www/compta
sudo chmod -R 755 /var/www/compta
sudo chmod -R 775 /var/www/compta/backend/uploads

echo -e "${GREEN}✅ Permissions configurées${NC}"

# 4. Tester la configuration Nginx
echo -e "\n${YELLOW}🧪 Test de la configuration Nginx...${NC}"
sudo nginx -t

if [ $? -ne 0 ]; then
    echo -e "${RED}❌ Erreur dans la configuration Nginx${NC}"
    exit 1
fi

# 5. Recharger Nginx
echo -e "\n${YELLOW}🔄 Rechargement de Nginx...${NC}"
sudo systemctl reload nginx

if [ $? -ne 0 ]; then
    echo -e "${RED}❌ Erreur lors du rechargement de Nginx${NC}"
    exit 1
fi

echo -e "${GREEN}✅ Nginx rechargé avec succès${NC}"

# 6. Vérifier que PHP-FPM tourne
echo -e "\n${YELLOW}🔍 Vérification de PHP-FPM...${NC}"
sudo systemctl status php8.3-fpm --no-pager | grep -q "active (running)"

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ PHP-FPM est actif${NC}"
else
    echo -e "${RED}⚠️  PHP-FPM n'est pas actif, tentative de démarrage...${NC}"
    sudo systemctl start php8.3-fpm
fi

# 7. Résumé
echo -e "\n${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${GREEN}✅ Déploiement terminé avec succès !${NC}"
echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "\n📍 L'application est accessible sur:"
echo -e "   ${YELLOW}http://compta.canebiere.net${NC}"
echo -e "\n📝 Pour voir les logs d'erreur:"
echo -e "   ${YELLOW}sudo tail -f /var/log/nginx/compta/error.log${NC}"
echo -e "\n🔍 Pour tester l'API:"
echo -e "   ${YELLOW}curl http://compta.canebiere.net/api/comptes.php${NC}"

