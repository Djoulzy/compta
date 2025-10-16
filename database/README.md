# Scripts de gestion de la base de données

## 📁 Fichiers disponibles

- **schema.sql** : Schéma complet de la base de données
- **reset_database.sh** : Script de réinitialisation complète
- **migrate_montant_to_real.sh** : Migration du type de la colonne montant
- **migration_montant_to_real.sql** : Script SQL de migration du type montant
- **migrate_unique_constraint.sh** : Migration de la contrainte d'unicité
- **migration_update_unique_constraint.sql** : Script SQL de mise à jour de la contrainte

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

## migrate_montant_to_real.sh

Script de migration pour convertir la colonne `montant` de `DECIMAL(12,2)` vers `REAL`.

### Pourquoi REAL ?

Le type `REAL` de PostgreSQL (float simple précision) offre :
- **Meilleure performance** pour les calculs numériques
- **Moins d'espace disque** (4 octets vs 8-16 octets pour DECIMAL)
- **Précision suffisante** pour des montants bancaires (environ 6 chiffres significatifs)

### Utilisation

```bash
cd /data/www/compta/database
./migrate_montant_to_real.sh
```

### Fonctionnement

1. Charge la configuration depuis `backend/.env`
2. Vérifie le type actuel de la colonne
3. Demande une confirmation
4. Exécute la migration SQL (avec transaction)
5. Affiche un résumé des données après migration

### ⚠️ Important

- La migration préserve toutes les données existantes
- La conversion est effectuée dans une transaction (rollback automatique en cas d'erreur)
- Le script détecte si la migration a déjà été appliquée

### Exemple de sortie

```
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
🔄 MIGRATION: Colonne montant vers type REAL
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Cette migration va :
  - Convertir la colonne 'montant' de DECIMAL(12,2) vers REAL
  - Préserver toutes les données existantes
  - Afficher un résumé des modifications

📋 Configuration détectée :
  Base de données : compta_db
  Utilisateur : compta_db
  Hôte : database

🔍 Vérification du type actuel de la colonne...
  Type actuel : numeric

Continuer avec la migration ? (o/N) : o

🚀 Exécution de la migration...
BEGIN
ALTER TABLE
 column_name | data_type | character_maximum_length | numeric_precision | numeric_scale 
-------------+-----------+--------------------------+-------------------+---------------
 montant     | real      |                          |                24 |             0
(1 ligne)

COMMIT
 nombre_operations | montant_min | montant_max | montant_moyen |  somme_totale  
-------------------+-------------+-------------+---------------+----------------
               661 |     -795.32 |      5000.0 |   -29.6506... |      -19599.18
(1 ligne)

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
✅ Migration terminée avec succès !
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

💡 La colonne 'montant' utilise maintenant le type REAL
   Ce type est plus performant pour les calculs numériques.
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

| Type | Taille | Précision | Usage |
|------|--------|-----------|-------|
| DECIMAL(12,2) | 8-16 octets | Exacte (2 décimales) | Comptabilité stricte |
| REAL | 4 octets | ~6 chiffres significatifs | Calculs performants |
| DOUBLE PRECISION | 8 octets | ~15 chiffres significatifs | Science, stats |

Pour des montants bancaires typiques (-10000 € à +10000 €), le type `REAL` offre une précision largement suffisante tout en optimisant les performances.

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
```
