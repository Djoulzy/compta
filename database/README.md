# Scripts de gestion de la base de données

## 📁 Fichiers disponibles

- **schema.sql** : Schéma complet de la base de données
- **reset_database.sh** : Script de réinitialisation complète
- **migrate_montant_to_real.sh** : Migration DECIMAL → REAL (OBSOLÈTE)
- **migration_montant_to_real.sql** : Script SQL de migration (OBSOLÈTE)
- **migrate_real_to_numeric.sh** : Migration REAL → NUMERIC(12,2) (ACTUEL)
- **migration_real_to_numeric.sql** : Script SQL de migration (ACTUEL)
- **migrate_unique_constraint.sh** : Migration de la contrainte d'unicité (OBSOLÈTE - supprime la contrainte)
- **migration_update_unique_constraint.sql** : Script SQL de mise à jour de la contrainte (OBSOLÈTE)
- **migration_update_reference_constraint.sql** : Modification contrainte référence (reference, compte_id)
- **cleanup_duplicates.sh** : Nettoyage des doublons dans la table operations
- **cleanup_duplicates.sql** : Script SQL de nettoyage des doublons

---

## reset_database.sh

Script de réinitialisation complète de la base de données.

### ⚠️ ATTENTION

Ce script supprime **TOUTES** les données de la base de données de manière **IRRÉVERSIBLE** :
- Toutes les tables (operations, tags, comptes)
- Toutes les séquences
- Tous les fichiers uploadés

### Utilisation

```bash
cd /data/www/compta/database
./reset_database.sh
```

Le script vous demandera une confirmation (vous devez taper `OUI` en majuscules) avant de procéder.

### Fonctionnement

1. Charge la configuration depuis `backend/.env`
2. Demande une confirmation explicite
3. Supprime toutes les tables et séquences
4. Recrée le schéma complet depuis `schema.sql`
5. Nettoie le dossier `backend/uploads/`
6. Affiche l'état final de la base

### Configuration

Le script utilise automatiquement les paramètres de connexion définis dans `backend/.env` :
- `DB_HOST`
- `DB_PORT`
- `DB_NAME`
- `DB_USER`
- `DB_PASSWORD`

### Après la réinitialisation

La base de données est vide et prête à recevoir de nouvelles données. Vous pouvez :
1. Importer un fichier CSV depuis l'interface web
2. Créer des comptes et des opérations manuellement
3. Utiliser l'API pour insérer des données

### Exemple de sortie

```
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
⚠️  RÉINITIALISATION COMPLÈTE DE LA BASE DE DONNÉES
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Cette opération va :
  - Supprimer TOUTES les tables
  - Supprimer TOUTES les données
  - Recréer le schéma de base

Cette action est IRRÉVERSIBLE !

Êtes-vous sûr de vouloir continuer ? (tapez 'OUI' en majuscules) : OUI

📋 Configuration détectée :
  Base de données : compta_db
  Utilisateur : compta_db
  Hôte : database

🗑️  Suppression de toutes les tables...
✅ Tables supprimées avec succès
🔨 Recréation du schéma...
✅ Schéma recréé avec succès
🧹 Nettoyage des fichiers uploadés...
✅ Fichiers uploadés supprimés

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
✅ Réinitialisation terminée avec succès !
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

📊 État de la base :
 schemaname | tablename  | size
------------+------------+-------
 public     | comptes    | 8192 bytes
 public     | operations | 8192 bytes
 public     | tags       | 8192 bytes

💡 La base de données est maintenant vide et prête à recevoir de nouvelles données.
   Vous pouvez importer un fichier CSV depuis l'interface web.
```

## Sécurité

- Le script demande une confirmation explicite (`OUI` en majuscules)
- Les mots de passe ne sont jamais affichés
- Les variables d'environnement sensibles sont protégées
- L'opération est atomique (s'arrête en cas d'erreur)

---

## migrate_real_to_numeric.sh

Script de migration pour convertir la colonne `montant` de `REAL` vers `NUMERIC(12,2)`.

### Pourquoi NUMERIC(12,2) ?

Le type `NUMERIC(12,2)` de PostgreSQL offre :
- **Précision exacte** pour les valeurs monétaires (pas d'approximation)
- **2 décimales garanties** pour les centimes
- **Standard bancaire** pour les calculs financiers
- **Pas d'erreur d'arrondi** contrairement aux types flottants

### Utilisation

```bash
cd /data/www/compta/database
./migrate_real_to_numeric.sh
```

### Fonctionnement

1. Charge la configuration depuis `backend/.env`
2. Vérifie le type actuel de la colonne
3. Demande une confirmation
4. Supprime temporairement la vue `vue_balance_comptes`
5. Convertit la colonne vers NUMERIC(12,2)
6. Recrée la vue
7. Affiche un résumé des données après migration

### ⚠️ Important

- La migration préserve toutes les données existantes
- La conversion est effectuée dans une transaction (rollback automatique en cas d'erreur)
- Le script détecte si la migration a déjà été appliquée
- Les montants sont automatiquement arrondis à 2 décimales

### Exemple de sortie

```
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
🔄 MIGRATION: Colonne montant vers type NUMERIC(12,2)
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Cette migration va :
  - Convertir la colonne 'montant' de REAL vers NUMERIC(12,2)
  - Préserver toutes les données existantes
  - Garantir une précision exacte de 2 décimales
  - Afficher un résumé des modifications

💡 Pourquoi NUMERIC(12,2) ?
   - Précision exacte (pas d'approximation comme avec REAL)
   - 2 décimales garanties pour les montants
   - Standard pour les valeurs monétaires

📋 Configuration détectée :
  Base de données : compta_db
  Utilisateur : compta_db
  Hôte : database

🔍 Vérification du type actuel de la colonne...
  Type actuel : real

Continuer avec la migration ? (o/N) : o

🚀 Exécution de la migration...
BEGIN
DROP VIEW
ALTER TABLE
CREATE VIEW
 column_name | data_type | character_maximum_length | numeric_precision | numeric_scale 
-------------+-----------+--------------------------+-------------------+---------------
 montant     | numeric   |                          |                12 |             2
(1 ligne)

COMMIT
 nombre_operations | montant_min | montant_max |    montant_moyen     | somme_totale 
-------------------+-------------+-------------+----------------------+--------------
              1893 |   -13710.90 |    15022.20 | -11.9129054410987850 |    -22551.13
(1 ligne)

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
✅ Migration terminée avec succès !
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

💡 La colonne 'montant' utilise maintenant le type NUMERIC(12,2)
   Ce type garantit une précision exacte pour les montants monétaires.
```

---

## Structure de la base de données

### Table `operations`

La colonne `montant` utilise maintenant le type **REAL** :

```sql
CREATE TABLE operations (
    ...
    montant REAL NOT NULL,
    ...
);
```

### Types PostgreSQL comparés

| Type | Taille | Précision | Usage | Choix actuel |
|------|--------|-----------|-------|--------------|
| NUMERIC(12,2) | 8-16 octets | Exacte (2 décimales) | Comptabilité stricte | ✅ **ACTUEL** |
| REAL | 4 octets | ~6 chiffres significatifs | Calculs performants | ❌ Obsolète |
| DOUBLE PRECISION | 8 octets | ~15 chiffres significatifs | Science, stats | ❌ Non utilisé |

Pour des montants bancaires, le type `NUMERIC(12,2)` est le **standard recommandé** :
- ✅ Précision exacte (pas d'approximation)
- ✅ Pas d'erreur d'arrondi sur les additions/soustractions
- ✅ Conforme aux normes comptables
- ✅ 2 décimales garanties pour les centimes

---

## ⚠️ migrate_unique_constraint.sh (OBSOLÈTE)

**Ce script est obsolète.** La contrainte d'unicité a été **supprimée** de la table `operations`.

### Pourquoi la suppression ?

Initialement, une contrainte d'unicité était en place pour éviter les doublons. Cependant, cette approche posait des problèmes :

1. **Imports multiples** : Réimporter le même fichier CSV provoquait des erreurs
2. **Vraies opérations identiques** : Certaines opérations légitimes étaient rejetées (ex: plusieurs achats identiques le même jour)
3. **Complexité** : La gestion des conflits avec `ON CONFLICT` compliquait le code

### Solution actuelle

La table `operations` n'a **plus de contrainte d'unicité**, seulement :
- La clé primaire sur `id`
- Une clé étrangère vers `comptes(id)`
- Un check sur `debit_credit IN ('D', 'C')`

### Gestion des doublons

Si vous avez des doublons après plusieurs imports, utilisez le script **cleanup_duplicates.sh** pour les nettoyer.

---

## cleanup_duplicates.sh

Script de nettoyage des doublons dans la table `operations`.

### Qu'est-ce qu'un doublon ?

Un doublon est défini comme deux opérations ayant **exactement** :
- Le même compte (`compte_id`)
- La même date d'opération (`date_operation`)
- Le même libellé (`libelle`)
- Le même montant (`montant`)
- Le même type CB (`cb`)

### Utilisation

```bash
cd /data/www/compta/database
./cleanup_duplicates.sh
```

### Fonctionnement

1. Charge la configuration depuis `backend/.env`
2. Analyse et compte les doublons
3. Affiche des exemples de doublons détectés
4. Demande une confirmation
5. **Garde la première occurrence** (ID le plus petit)
6. **Supprime les occurrences suivantes**
7. Affiche un résumé des suppressions

### ⚠️ Important

- La suppression est **irréversible** (effectuée dans une transaction)
- Seule la **première occurrence** est gardée (basé sur l'ID)
- Les doublons légitimes (vraies opérations identiques) seront également supprimés
- **Faites une sauvegarde** avant d'exécuter ce script si vous avez des doutes

### Quand l'utiliser ?

- Après avoir réimporté plusieurs fois le même fichier CSV
- Après une migration depuis un ancien système
- Pour nettoyer périodiquement la base de données

### Exemple de sortie

```
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
🧹 NETTOYAGE: Suppression des doublons dans operations
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Ce script va :
  - Identifier les doublons (même compte, date, libellé, montant, CB)
  - Garder la première occurrence (ID le plus petit)
  - Supprimer les occurrences suivantes

💡 Pourquoi nettoyer ?
   Les doublons peuvent survenir lors d'imports multiples du même fichier.
   Cette opération permet de nettoyer la base sans perdre de données.

📋 Configuration détectée :
  Base de données : compta_db
  Utilisateur : compta_db
  Hôte : database

🔍 Analyse des doublons...
  Opérations totales : 1893
  Doublons à supprimer : 127

⚠️  Exemples de doublons détectés :
 compte_id | date_operation |                 libelle                  | montant | cb | occurrences 
-----------+----------------+------------------------------------------+---------+----+-------------
         1 | 2025-01-04     | CB SAS   V2L TOURI FACT 301224          |    -3.8 | t  |           3
         1 | 2024-03-04     | CB PRALOUP SKIPASS FACT 270224          |     -38 | t  |           2
         1 | 2024-05-06     | VIR SEPA BRUNO MARUSI                   |    -125 | f  |           2
(10 lignes)

Voulez-vous supprimer ces doublons ? (o/N) : o

🚀 Suppression des doublons en cours...

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
✅ Nettoyage terminé avec succès !
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

📊 Résumé :
  Opérations avant : 1893
  Doublons supprimés : 127
  Opérations après : 1766

✅ La base de données est maintenant propre !
```

---

## Structure de la base de données

### Table `operations`

**Pas de contrainte d'unicité** - Permet les doublons volontaires :

```sql
CREATE TABLE operations (
    id SERIAL PRIMARY KEY,
    fichier VARCHAR(255),
    compte_id INTEGER NOT NULL REFERENCES comptes(id) ON DELETE CASCADE,
    date_operation DATE NOT NULL,
    date_valeur DATE,
    libelle TEXT NOT NULL,
    montant NUMERIC(12, 2) NOT NULL,
    debit_credit CHAR(1) CHECK (debit_credit IN ('D', 'C')),
    cb BOOLEAN DEFAULT FALSE,
    tags JSONB DEFAULT '[]'::jsonb,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### Contraintes actuelles

| Type | Nom | Description |
|------|-----|-------------|
| PRIMARY KEY | `operations_pkey` | Clé primaire sur `id` |
| FOREIGN KEY | `operations_compte_id_fkey` | Référence vers `comptes(id)` |
| CHECK | `operations_debit_credit_check` | Vérifie que `debit_credit` est 'D' ou 'C' |

**Aucune contrainte d'unicité** - Les imports multiples sont autorisés.

---

## migrate_unique_constraint.sh

Script de migration pour mettre à jour la contrainte d'unicité de la table `operations`.

### Changement

**Ancien critère d'unicité :**
```sql
UNIQUE (compte_id, date_operation, libelle)
```

**Nouveau critère d'unicité :**
```sql
UNIQUE (compte_id, date_operation, libelle, montant, cb)
```

### Pourquoi ce changement ?

L'ancien critère empêchait d'avoir deux opérations avec :
- Même compte
- Même date
- Même libellé

Mais dans la réalité, on peut avoir le même jour :
- Plusieurs achats au même endroit avec des montants différents
- Des opérations CB et non-CB avec le même libellé
- Des opérations similaires mais distinctes

Le nouveau critère permet ces cas tout en évitant les vrais doublons.

### Utilisation

```bash
cd /data/www/compta/database
./migrate_unique_constraint.sh
```

### Fonctionnement

1. Charge la configuration depuis `backend/.env`
2. Affiche la contrainte actuelle
3. **Vérifie s'il y a des doublons** avec le nouveau critère
4. Demande une confirmation
5. Supprime l'ancienne contrainte
6. Crée la nouvelle contrainte
7. Affiche un résumé

### ⚠️ Gestion des doublons

Si des doublons existent déjà avec le nouveau critère (même compte, date, libellé, montant ET cb), la migration échouera. Dans ce cas :

1. Le script affiche les doublons détectés
2. Vous devez les supprimer manuellement avant de relancer la migration
3. Ou forcer la migration (les doublons causeront une erreur PostgreSQL)

### Exemples de cas d'usage

**Avant (bloqué) :**
```
Compte: Courant, Date: 2025-10-15, Libellé: "CARREFOUR"
Opération 1: -50.00 €
Opération 2: -30.00 € ❌ REJETÉ (même libellé, même date)
```

**Après (autorisé) :**
```
Compte: Courant, Date: 2025-10-15, Libellé: "CARREFOUR"
Opération 1: -50.00 €, CB: true
Opération 2: -30.00 €, CB: true  ✅ ACCEPTÉ (montant différent)
Opération 3: -50.00 €, CB: false ✅ ACCEPTÉ (CB différent)
```

### Exemple de sortie

```
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
🔄 MIGRATION: Contrainte d'unicité de la table operations
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Cette migration va :
  - Supprimer la contrainte UNIQUE(compte_id, date_operation, libelle)
  - Créer une nouvelle contrainte UNIQUE(compte_id, date_operation, libelle, montant, cb)
  - Vérifier qu'il n'y a pas de doublons avec le nouveau critère

💡 Pourquoi ce changement ?
   Permet d'avoir plusieurs opérations avec le même libellé et la même date,
   si elles diffèrent par le montant ou le type (CB ou non).

📋 Configuration détectée :
  Base de données : compta_db
  Utilisateur : compta_db
  Hôte : database

🔍 Vérification des contraintes actuelles...
  Contrainte actuelle : operations_compte_id_date_operation_libelle_key

Continuer avec la migration ? (o/N) : o

🔍 Vérification des doublons potentiels...
✅ Aucun doublon détecté

🚀 Exécution de la migration...
BEGIN
                           conname                            |                                definition                                
--------------------------------------------------------------+--------------------------------------------------------------------------
 operations_compte_id_date_operation_libelle_key             | UNIQUE (compte_id, date_operation, libelle)
(1 ligne)

ALTER TABLE
ALTER TABLE
                                      conname                                       |                                      definition                                       
------------------------------------------------------------------------------------+--------------------------------------------------------------------------------------
 operations_compte_id_date_operation_libelle_montant_cb_key                        | UNIQUE (compte_id, date_operation, libelle, montant, cb)
(1 ligne)

COMMIT
 compte_id | date_operation | libelle | montant | cb | occurrences 
-----------+----------------+---------+---------+----+-------------
(0 lignes)


━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
✅ Migration terminée avec succès !
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

💡 La contrainte d'unicité a été mise à jour :
   Ancien critère : (compte_id, date_operation, libelle)
   Nouveau critère : (compte_id, date_operation, libelle, montant, cb)

📌 Cela permet maintenant d'avoir :
   - Plusieurs opérations avec le même libellé et date, mais montants différents
   - Des opérations CB et non-CB avec le même libellé et date

---

## migration_update_reference_constraint.sql

Script de migration pour modifier la contrainte d'unicité sur la colonne `reference`.

### 🎯 Objectif
Modifier la contrainte d'unicité de la colonne `reference` pour qu'elle s'applique sur la combinaison `(reference, compte_id)` au lieu de `reference` seule.

### 🔄 Changements effectués
- **Suppression** de la contrainte `operations_reference_unique`
- **Ajout** de la contrainte `operations_reference_compte_unique` sur `(reference, compte_id)`
- **Mise à jour** de l'index correspondant

### ✅ Résultat
- **Autorisé** : Même référence pour des comptes différents
- **Interdit** : Même référence pour le même compte
- **Exemple** : La référence "VIR001" peut exister pour le compte A et le compte B, mais pas deux fois pour le compte A

### 🚀 Utilisation
```bash
cd /data/www/compta/database
PGPASSWORD=ptcmba51 psql -h database -U compta_db -d compta_db -f migration_update_reference_constraint.sql
```
```
