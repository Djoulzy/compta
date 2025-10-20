# Application de Gestion Comptable

Application web complète pour gérer vos relevés bancaires avec PHP/PostgreSQL en backend et React en frontend.

## 🌟 Fonctionnalités

- **Gestion multi-comptes** : Gérez plusieurs comptes bancaires
- **Import CSV intelligent** : Importez vos relevés avec détection automatique du compte et type
- **Gestion des imports** : Historique complet des imports avec possibilité de suppression
- **Détection de doublons** : Évite les imports multiples d'un même fichier (basé sur le hash du contenu)
- **Recherche et filtrage avancés** : Filtrez par date, type, montant, tags, comptes, etc.
- **Tags automatiques** : Catégorisez automatiquement vos opérations avec système de règles
- **Balance comptable** : Visualisez en temps réel vos débits, crédits et solde
- **Informations enrichies** : Références, informations complémentaires, types d'opération
- **Tri personnalisé** : Triez vos opérations par date d'opération ou de valeur
- **Interface intuitive** : Interface React moderne et responsive
- **API REST complète** : CRUD complet pour tous les objets métiers

## 📋 Prérequis

- PHP 8.3 ou supérieur avec PHP-FPM
- PostgreSQL 12 ou supérieur
- Node.js 16 ou supérieur
- npm ou yarn
- Serveur web (Nginx recommandé)

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

Le nouveau format CSV utilise le séparateur `;` et contient les colonnes suivantes :

```
Date de comptabilisation;Libelle simplifie;Libelle operation;Reference;Informations complementaires;Type operation;Categorie;Sous categorie;Debit;Credit;Date operation;Date de valeur;Pointage operation
```

### Colonnes utilisées :
- **Libelle operation** → Libellé de l'opération
- **Reference** → Référence de l'opération (unique)
- **Informations complementaires** → Informations complémentaires
- **Type operation** → Type d'opération (Virement, Carte, Prélèvement, etc.)
- **Debit** → Montant en débit (si applicable)
- **Credit** → Montant en crédit (si applicable)  
- **Date operation** → Date de l'opération
- **Date de valeur** → Date de valeur

### Colonnes ignorées :
- Date de comptabilisation, Libelle simplifie, Categorie, Sous categorie, Pointage operation

### Extraction automatique du nom de fichier :
- **Numéro de compte** : 
  - **Comptes normaux** : Extrait des premiers chiffres du nom de fichier
    - Exemple : `04003501208_20082023_20102025.csv` → compte `04003501208`
  - **Cartes bancaires** : Extrait du 3ème segment après "carte_"
    - Exemple : `carte_6106_04003501208_20082023_20102025.csv` → compte `04003501208`
- **Flag CB** : Déterminé par la présence de "carte" dans le nom de fichier (insensible à la casse)
  - Si le nom contient "carte" → CB = true
  - Sinon → CB = false

### Exemple :
```csv
Date de comptabilisation;Libelle simplifie;Libelle operation;Reference;Informations complementaires;Type operation;Categorie;Sous categorie;Debit;Credit;Date operation;Date de valeur;Pointage operation
20/08/2023;PRLV;PRLV SEPA FOURNISSEUR ABC;REF2023001;Facture électricité;Prélèvement;Energie;Electricité;125,30;;20/08/2023;20/08/2023;N
21/08/2023;VIR;VIREMENT SALAIRE ENTREPRISE;REF2023002;Salaire août 2023;Virement;Revenus;Salaire;;2500,00;21/08/2023;21/08/2023;N
22/08/2023;CB;CB SUPERMARCHE LECLERC;REF2023003;Courses alimentaires;Carte;Alimentation;Courses;85,75;;22/08/2023;23/08/2023;N
```

### Notes importantes :
- **Séparateur** : `;` (point-virgule)
- **Dates** : Format `JJ/MM/AAAA` ou `AAAA-MM-JJ`
- **Montants** : Peuvent utiliser la virgule ou le point comme séparateur décimal
- **Débit/Crédit** : Automatiquement déterminé selon la colonne non vide (Debit ou Credit)
- **Contrainte unique** : La combinaison (Reference, Compte) doit être unique - même référence autorisée pour des comptes différents

## 🏗️ Structure du projet

```
compta/
├── database/
│   ├── schema.sql                            # Script de création de la BDD
│   ├── migration_*.sql                       # Scripts de migration
│   ├── reset_database.sh                     # Script de remise à zéro
│   └── README.md                            # Documentation BDD
├── backend/
│   ├── api/
│   │   ├── comptes.php                      # API des comptes
│   │   ├── operations.php                   # API des opérations (CRUD + tags + infos)
│   │   ├── tags.php                         # API des tags
│   │   ├── upload.php                       # API d'upload CSV
│   │   └── imports.php                      # API de gestion des imports
│   ├── config/
│   │   └── Database.php                     # Configuration BDD
│   ├── models/
│   │   ├── Compte.php                       # Modèle Compte
│   │   ├── Operation.php                    # Modèle Opération (avec nouvelles colonnes)
│   │   ├── Tag.php                          # Modèle Tag
│   │   └── Import.php                       # Modèle Import (avec hash et historique)
│   ├── uploads/                             # Stockage des fichiers uploadés
│   └── .env.example                         # Configuration exemple
├── frontend/
│   ├── public/
│   │   └── index.html
│   ├── src/
│   │   ├── components/
│   │   │   ├── Balance.js                   # Balance en temps réel
│   │   │   ├── BalanceSticky.js            # Balance flottante
│   │   │   ├── CompteSelector.js           # Sélection de compte
│   │   │   ├── CompteManager.js            # Gestion des comptes
│   │   │   ├── ImportCSV.js                # Interface d'import
│   │   │   ├── ImportManager.js            # Gestion des imports
│   │   │   ├── OperationsTable.js          # Tableau des opérations
│   │   │   └── TagManager.js               # Gestion des tags
│   │   ├── services/
│   │   │   └── api.js                      # Service API
│   │   ├── App.js                          # Application principale
│   │   ├── App.css                         # Styles principaux
│   │   ├── index.js
│   │   └── index.css
│   └── package.json
├── deploy.sh                                # Script de déploiement automatique
├── nginx-production.conf                    # Configuration Nginx
└── README.md                                # Cette documentation
```

## 🎯 Utilisation

### 1. Premier lancement

Au premier lancement, l'application vous propose d'importer un fichier CSV pour créer votre premier compte.

### 2. Sélection du compte

Une fois les comptes créés, sélectionnez le compte à consulter depuis la page d'accueil.

### 3. Navigation dans l'interface

L'interface propose plusieurs vues accessibles via le menu :
- **🔄 Autre Compte** : Changer de compte
- **⚙️ Editer Comptes** : Gérer les comptes bancaires
- **🏷️ Tags** : Gérer les règles de tags automatiques
- **📥 Import CSV** : Importer un nouveau fichier
- **📋 Gestion Imports** : Voir l'historique et gérer les imports

### 4. Consultation des opérations

- **Filtrage avancé** : Par date, type, montant, tags, recherche textuelle
- **Tri intelligent** : Cliquez sur les en-têtes de colonnes
- **Balance dynamique** : Calculée automatiquement selon les filtres
- **Informations enrichies** : Référence, type d'opération, infos complémentaires

### 5. Gestion des tags automatiques

Les tags permettent de catégoriser automatiquement vos opérations :

1. Allez dans "🏷️ Tags"
2. Créez un nouveau tag avec une clé (ex: "supermarche") et un pattern (ex: "CARREFOUR")
3. Les opérations dont le libellé contient "CARREFOUR" seront automatiquement taguées "supermarche"
4. Modifiez ou supprimez les tags selon vos besoins
5. Ré-appliquez tous les tags sur l'historique si nécessaire

### 6. Import de nouveaux fichiers

1. Cliquez sur "📥 Import CSV"
2. Sélectionnez votre fichier au nouveau format
3. L'import se fait automatiquement avec :
   - **Détection de doublons** : Par hash du contenu du fichier
   - **Extraction automatique** : Numéro de compte et flag CB du nom de fichier
   - **Création de comptes** : Si le compte n'existe pas encore
   - **Application des tags** : Selon les règles définies
   - **Validation des données** : Contrôle de format et contraintes

### 7. Gestion des imports

1. Accédez à "📋 Gestion Imports"
2. Consultez l'historique complet des imports
3. Visualisez les détails de chaque import et ses opérations
4. Supprimez un import et toutes ses opérations si nécessaire

### 8. Mise à jour des informations

Vous pouvez enrichir vos opérations via l'API :
- **Tags** : `PUT /api/operations/tags/{id}`
- **Infos complémentaires** : `PUT /api/operations/infos/{id}`

## 🔌 API REST

L'application expose une API REST complète :

### Comptes
- `GET /api/comptes` - Liste tous les comptes
- `GET /api/comptes/{id}` - Détails d'un compte
- `POST /api/comptes` - Créer un compte
- `PUT /api/comptes/{id}` - Modifier un compte
- `DELETE /api/comptes/{id}` - Supprimer un compte

### Opérations
- `GET /api/operations` - Liste des opérations (avec filtres)
- `GET /api/operations/{id}` - Détails d'une opération
- `GET /api/operations/balance` - Balance calculée (avec filtres)
- `POST /api/operations` - Créer une opération
- `PUT /api/operations/tags/{id}` - Mettre à jour les tags
- `PUT /api/operations/infos/{id}` - Mettre à jour infos complémentaires et type
- `DELETE /api/operations/{id}` - Supprimer une opération

### Tags
- `GET /api/tags` - Liste tous les tags
- `GET /api/tags/{id}` - Détails d'un tag
- `POST /api/tags` - Créer un tag
- `PUT /api/tags/{id}` - Modifier un tag
- `DELETE /api/tags/{id}` - Supprimer un tag
- `POST /api/tags/reapply` - Ré-appliquer tous les tags

### Imports
- `POST /api/upload` - Uploader et traiter un fichier CSV
- `GET /api/imports` - Liste tous les imports avec statistiques
- `GET /api/imports/{id}` - Détails d'un import avec ses opérations
- `DELETE /api/imports/{id}` - Supprimer un import et ses opérations

### Filtres disponibles pour /api/operations
- `compte_id` : ID du compte
- `date_debut` / `date_fin` : Plage de dates
- `mois` / `annee` : Mois et année spécifiques
- `debit_credit` : D ou C
- `cb` : true/false
- `recherche` : Recherche textuelle dans le libellé
- `tag` : Filtrer par tag
- `sort` : Tri (date_operation_asc/desc, date_valeur_asc/desc)

## 🗄️ Base de données

### Structure des tables principales

#### Table `operations`
```sql
- id : SERIAL PRIMARY KEY
- fichier : VARCHAR(255) -- Nom du fichier d'origine
- import_id : INTEGER -- Référence vers l'import
- compte_id : INTEGER -- Référence vers le compte
- date_operation : DATE -- Date de l'opération
- date_valeur : DATE -- Date de valeur
- libelle : TEXT -- Libellé de l'opération
- montant : NUMERIC(12,2) -- Montant
- debit_credit : CHAR(1) -- D ou C
- cb : BOOLEAN -- Carte bancaire
- tags : JSONB -- Tags automatiques
- reference : VARCHAR(255) UNIQUE -- Référence unique (peut être NULL)
- informations_complementaires : VARCHAR(500) -- Infos complémentaires
- type_operation : VARCHAR(100) -- Type d'opération
- created_at, updated_at : TIMESTAMP
```

#### Table `imports`
```sql
- id : SERIAL PRIMARY KEY
- nom_fichier : VARCHAR(255) -- Nom du fichier stocké
- nom_fichier_original : VARCHAR(255) -- Nom original
- taille_fichier : INTEGER
- hash_fichier : VARCHAR(64) UNIQUE -- Hash SHA256 du contenu
- nombre_operations, nombre_erreurs : INTEGER
- statut : VARCHAR(20) -- en_cours, termine, erreur
- created_at, updated_at : TIMESTAMP
```

#### Table `comptes`
```sql
- id : SERIAL PRIMARY KEY
- nom : VARCHAR(255) UNIQUE -- Numéro de compte
- label : VARCHAR(255) -- Libellé du compte
- description : TEXT
- created_at, updated_at : TIMESTAMP
```

#### Table `tags`
```sql
- id : SERIAL PRIMARY KEY
- cle : VARCHAR(255) -- Nom du tag
- pattern : VARCHAR(255) -- Pattern de recherche
- created_at, updated_at : TIMESTAMP
```

### Migrations

Les migrations sont stockées dans `database/` :
- `schema.sql` : Schéma complet initial
- `migration_add_imports.sql` : Ajout du système d'imports
- `migration_add_reference_column.sql` : Ajout colonne reference
- `migration_add_info_type_columns.sql` : Ajout infos complémentaires et type

### Vue statistiques

La vue `vue_stats_imports` agrège les statistiques des imports :
```sql
SELECT i.*, 
       COUNT(o.id) as nombre_operations,
       SUM(CASE WHEN o.debit_credit = 'D' THEN o.montant ELSE 0 END) as total_debits,
       SUM(CASE WHEN o.debit_credit = 'C' THEN o.montant ELSE 0 END) as total_credits,
       COUNT(DISTINCT o.compte_id) as nombre_comptes_concernes
FROM imports i
LEFT JOIN operations o ON i.id = o.import_id
GROUP BY i.id
```

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
- Vérifiez que PostgreSQL est démarré : `sudo systemctl status postgresql`
- Vérifiez les paramètres dans le fichier `.env`
- Vérifiez les droits de l'utilisateur PostgreSQL
- Testez la connexion : `psql -h database -U compta_db`

### Erreur CORS
- Vérifiez que le backend autorise l'origine du frontend dans les en-têtes CORS
- Vérifiez la configuration dans le fichier `.env`
- Vérifiez que nginx route correctement `/api/*` vers PHP-FPM

### Erreur lors de l'import CSV
- **Format incorrect** : Vérifiez que le fichier utilise le séparateur `;` et a 13 colonnes
- **Permissions** : Vérifiez les permissions d'écriture du dossier `uploads` (jules:jules)
- **Limites PHP** : Vérifiez `upload_max_filesize` et `post_max_size` dans php.ini
- **Doublons** : Le système détecte les fichiers déjà importés par hash de contenu
- **Compte manquant** : Le numéro de compte doit être extractible du nom de fichier

### Erreur PHP-FPM
- Vérifiez que PHP 8.3-FPM est démarré : `sudo systemctl status php8.3-fpm`
- Vérifiez les logs : `sudo tail -f /var/log/php8.3-fpm.log`
- Vérifiez la configuration nginx pour le bon socket

### Performance lente
- Vérifiez les index PostgreSQL avec `EXPLAIN ANALYZE`
- Augmentez `shared_buffers` et `effective_cache_size` dans postgresql.conf
- Utilisez les filtres pour limiter le nombre d'opérations affichées

### Interface vide ou erreur React
- Vérifiez que le build frontend est à jour : `npm run build` puis `./deploy.sh`
- Vérifiez les logs navigateur (F12 → Console)
- Vérifiez que l'API répond : `curl http://compta.canebiere.net/api/comptes`

### Problème de déploiement
- Utilisez le script automatique : `./deploy.sh`
- Vérifiez les permissions après déploiement : `sudo chown -R jules:jules /var/www/compta`
- Testez la configuration nginx : `sudo nginx -t`
- Rechargez les services : `sudo systemctl reload nginx php8.3-fpm`

## 📚 Ressources utiles

- **Documentation PostgreSQL** : https://www.postgresql.org/docs/
- **Documentation React** : https://react.dev/
- **Documentation PHP** : https://www.php.net/docs.php
- **Documentation Nginx** : https://nginx.org/en/docs/

---

🎉 **Application développée avec ❤️ pour simplifier la gestion comptable personnelle**