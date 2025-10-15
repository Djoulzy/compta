# Application de Gestion Comptable

Application web complète pour gérer vos relevés bancaires avec PHP/PostgreSQL en backend et React en frontend.

## 🌟 Fonctionnalités

- **Gestion multi-comptes** : Gérez plusieurs comptes bancaires
- **Import CSV** : Importez vos relevés bancaires au format CSV
- **Recherche et filtrage avancés** : Filtrez par date, type, montant, tags, etc.
- **Tags automatiques** : Catégorisez automatiquement vos opérations
- **Balance comptable** : Visualisez en temps réel vos débits, crédits et solde
- **Tri personnalisé** : Triez vos opérations par date d'opération ou de valeur
- **Interface intuitive** : Interface React moderne et responsive

## 📋 Prérequis

- PHP 7.4 ou supérieur
- PostgreSQL 12 ou supérieur
- Node.js 16 ou supérieur
- npm ou yarn
- Serveur web (Apache ou Nginx recommandé)

## 🚀 Installation

### 1. Base de données

```bash
# Créer la base de données
sudo -u postgres psql
CREATE DATABASE compta_db;
CREATE USER compta_user WITH PASSWORD 'votre_mot_de_passe';
GRANT ALL PRIVILEGES ON DATABASE compta_db TO compta_user;
\q

# Importer le schéma
psql -U compta_user -d compta_db -f database/schema.sql
```

### 2. Backend PHP

```bash
cd backend

# Copier le fichier de configuration
cp .env.example .env

# Éditer le fichier .env avec vos paramètres
nano .env
```

Configurez votre fichier `.env` :
```
DB_HOST=localhost
DB_PORT=5432
DB_NAME=compta_db
DB_USER=compta_user
DB_PASSWORD=votre_mot_de_passe

CORS_ORIGIN=http://localhost:3000

UPLOAD_MAX_SIZE=5242880
UPLOAD_DIR=uploads
```

### 3. Frontend React

```bash
cd frontend

# Installer les dépendances
npm install

# Créer le fichier .env
echo "REACT_APP_API_URL=http://localhost:8000/api" > .env
```

## 🏃 Démarrage

### Mode Développement

#### Backend (Serveur PHP intégré)
```bash
cd backend
php -S localhost:8000
```

#### Frontend (React Dev Server)
```bash
cd frontend
npm start
```

L'application sera accessible sur http://localhost:3000

### Mode Production (Nginx + PHP-FPM)

#### Configuration initiale

1. **Copier le fichier .env pour le backend** :
```bash
cp backend/.env.example backend/.env
# Éditer avec vos paramètres PostgreSQL
nano backend/.env
```

2. **Copier le fichier .env pour le frontend** :
```bash
cp frontend/.env.example frontend/.env
# Vérifier l'URL de l'API
nano frontend/.env
```

3. **Déployer l'application** :
```bash
./deploy.sh
```

Le script de déploiement va :
- Builder le frontend React
- Copier les fichiers vers `/var/www/compta`
- Configurer Nginx
- Définir les bonnes permissions
- Recharger Nginx

L'application sera accessible sur **http://compta.canebiere.net**

#### Déploiement manuel

Si vous préférez déployer manuellement :

```bash
# 1. Builder le frontend
cd frontend
npm run build

# 2. Copier les fichiers
sudo mkdir -p /var/www/compta/{frontend/build,backend}
sudo rsync -av backend/ /var/www/compta/backend/
sudo rsync -av frontend/build/ /var/www/compta/frontend/build/

# 3. Configurer Nginx
sudo cp nginx-production.conf /etc/nginx/sites-available/compta.conf
sudo ln -s /etc/nginx/sites-available/compta.conf /etc/nginx/sites-enabled/

# 4. Permissions
sudo chown -R www-data:www-data /var/www/compta
sudo chmod -R 755 /var/www/compta
sudo chmod -R 775 /var/www/compta/backend/uploads

# 5. Recharger Nginx
sudo nginx -t
sudo systemctl reload nginx
```

## 📊 Format du fichier CSV

Le fichier CSV doit contenir les colonnes suivantes (dans cet ordre) :

```
Fichier,Compte,Date opération,Date valeur,Libellé,Montant,Débit/Crédit,CB
```

### Exemple :
```csv
Fichier,Compte,Date opération,Date valeur,Libellé,Montant,Débit/Crédit,CB
releve_janvier.csv,Compte courant,2025-01-01,2025-01-02,CARREFOUR MARKET,45.50,Débit,True
releve_janvier.csv,Compte courant,2025-01-05,2025-01-05,VIREMENT SALAIRE,2500.00,Crédit,False
```

### Notes importantes :
- Les dates doivent être au format `AAAA-MM-JJ` (ISO 8601) ou `JJ/MM/AAAA`
- Les montants peuvent utiliser la virgule ou le point comme séparateur décimal
- Débit/Crédit peut être `D` ou `Débit` pour les débits, `C` ou `Crédit` pour les crédits
- CB (Carte Bancaire) peut être `True`/`False`, `Oui`/`Non`, ou `1`/`0`

## 🏗️ Structure du projet

```
compta/
├── database/
│   └── schema.sql          # Script de création de la BDD
├── backend/
│   ├── api/
│   │   ├── comptes.php     # API des comptes
│   │   ├── operations.php  # API des opérations
│   │   ├── tags.php        # API des tags
│   │   └── import.php      # API d'import CSV
│   ├── config/
│   │   └── Database.php    # Configuration BDD
│   ├── models/
│   │   ├── Compte.php      # Modèle Compte
│   │   ├── Operation.php   # Modèle Opération
│   │   └── Tag.php         # Modèle Tag
│   └── .env.example        # Configuration exemple
└── frontend/
    ├── public/
    │   └── index.html
    ├── src/
    │   ├── components/
    │   │   ├── Balance.js
    │   │   ├── CompteSelector.js
    │   │   ├── ImportCSV.js
    │   │   ├── OperationsTable.js
    │   │   └── TagManager.js
    │   ├── services/
    │   │   └── api.js      # Service API
    │   ├── App.js
    │   ├── index.js
    │   └── index.css
    └── package.json
```

## 🎯 Utilisation

### 1. Premier lancement

Au premier lancement, l'application vous propose d'importer un fichier CSV pour créer votre premier compte.

### 2. Sélection du compte

Une fois les comptes créés, sélectionnez le compte à consulter depuis la page d'accueil.

### 3. Consultation des opérations

- **Filtrage** : Utilisez les filtres pour afficher uniquement certaines opérations
- **Tri** : Cliquez sur les en-têtes de colonnes pour trier
- **Recherche** : Utilisez le champ de recherche pour trouver des opérations spécifiques
- **Balance** : La balance est calculée automatiquement selon les filtres appliqués

### 4. Gestion des tags

Les tags permettent de catégoriser automatiquement vos opérations :

1. Allez dans "Gérer les tags"
2. Créez un nouveau tag avec une clé (ex: "supermarche") et une valeur (ex: "CARREFOUR")
3. Les opérations dont le libellé contient "CARREFOUR" seront automatiquement taguées
4. Modifiez ou supprimez les tags selon vos besoins

### 5. Import de nouveaux fichiers

1. Cliquez sur "Importer un fichier CSV"
2. Glissez-déposez votre fichier ou cliquez pour le sélectionner
3. L'import se fait automatiquement avec :
   - Enregistrement des données (en mode update si la ligne existe déjà)
   - Création des comptes si nécessaire
   - Application automatique des tags

## 🔒 Sécurité

⚠️ **Important pour la production** :

1. Changez tous les mots de passe par défaut
2. Configurez correctement les CORS dans le backend
3. Activez HTTPS
4. Limitez les droits d'accès à la base de données
5. Configurez les limites de taille d'upload
6. Ajoutez une authentification utilisateur

## 🐛 Dépannage

### Erreur de connexion à la base de données
- Vérifiez que PostgreSQL est démarré
- Vérifiez les paramètres dans le fichier `.env`
- Vérifiez les droits de l'utilisateur PostgreSQL

### Erreur CORS
- Vérifiez que le backend autorise l'origine du frontend dans les en-têtes CORS
- Vérifiez la configuration dans le fichier `.env`

### Erreur lors de l'import CSV
- Vérifiez le format du fichier CSV
- Vérifiez les permissions d'écriture du dossier `uploads`
- Vérifiez la limite de taille d'upload dans PHP (`upload_max_filesize` et `post_max_size`)