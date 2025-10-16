# État de la base de données - Table operations

**Date:** 16 octobre 2025

## ✅ Contraintes actuelles

La table `operations` possède les contraintes suivantes :

| Type | Nom | Description |
|------|-----|-------------|
| PRIMARY KEY | `operations_pkey` | Clé primaire sur `id` |
| FOREIGN KEY | `operations_compte_id_fkey` | Référence vers `comptes(id) ON DELETE CASCADE` |
| CHECK | `operations_debit_credit_check` | Vérifie que `debit_credit IN ('D', 'C')` |

## ❌ Contraintes supprimées

**Aucune contrainte d'unicité** n'est présente sur la table `operations`.

### Historique

1. **Initialement** : `UNIQUE (compte_id, date_operation, libelle)`
2. **Migration 1** : `UNIQUE (compte_id, date_operation, libelle, montant, cb)`
3. **État actuel** : **Aucune contrainte d'unicité**

### Raisons de la suppression

- Permettre les imports multiples du même fichier CSV
- Autoriser les vraies opérations identiques (ex: plusieurs achats identiques)
- Simplifier la gestion des imports
- Éviter les erreurs lors de réimportations

## 🔧 Impact sur le code

### Backend PHP

**Fichier:** `backend/models/Operation.php`

```php
// La méthode upsert() appelle maintenant simplement create()
public function upsert($data)
{
    return $this->create($data);
}

// La méthode create() fait un INSERT simple sans ON CONFLICT
public function create($data)
{
    $query = "INSERT INTO " . $this->table . " 
              (fichier, compte_id, date_operation, date_valeur, libelle, montant, debit_credit, cb, tags)
              VALUES (:fichier, :compte_id, :date_operation, :date_valeur, :libelle, :montant, :debit_credit, :cb, :tags)
              RETURNING id";
    // ... binding et exécution
}
```

**Pas de clause `ON CONFLICT`** - Chaque insertion crée une nouvelle ligne.

### API d'import

**Fichier:** `backend/api/import.php`

- Utilise `$operationModel->upsert($operationData)`
- Chaque ligne du CSV crée une **nouvelle opération**
- Les réimportations créent des **doublons**

## 🧹 Gestion des doublons

### Détection

Un doublon est défini par l'égalité stricte de :
- `compte_id`
- `date_operation`
- `libelle`
- `montant`
- `cb`

### Nettoyage

**Script:** `database/cleanup_duplicates.sh`

```bash
cd /data/www/compta/database
./cleanup_duplicates.sh
```

Ce script :
1. Détecte les doublons
2. **Garde la première occurrence** (ID le plus petit)
3. **Supprime les occurrences suivantes**

## 📊 État actuel de la base

**Commande de vérification :**

```sql
-- Lister toutes les contraintes
SELECT conname, contype, pg_get_constraintdef(oid) 
FROM pg_constraint 
WHERE conrelid = 'operations'::regclass 
ORDER BY contype;

-- Détecter les doublons
SELECT compte_id, date_operation, libelle, montant, cb, COUNT(*) as occurrences 
FROM operations 
GROUP BY compte_id, date_operation, libelle, montant, cb 
HAVING COUNT(*) > 1;
```

## 🎯 Recommandations

### Pour éviter les doublons

1. **Ne pas réimporter** plusieurs fois le même fichier CSV
2. **Vérifier** avant l'import si les données existent déjà
3. **Nettoyer périodiquement** avec `cleanup_duplicates.sh`

### Pour détecter les anomalies

```sql
-- Opérations en double le même jour
SELECT compte_id, date_operation, COUNT(*) as operations_jour
FROM operations
GROUP BY compte_id, date_operation
HAVING COUNT(*) > 10
ORDER BY COUNT(*) DESC;

-- Montants suspects
SELECT * FROM operations 
WHERE ABS(montant) > 10000
ORDER BY ABS(montant) DESC;
```

## 📝 Notes importantes

- ✅ Les imports multiples sont **autorisés**
- ✅ Les vraies opérations identiques sont **autorisées**
- ⚠️ Les doublons accidentels doivent être **nettoyés manuellement**
- ⚠️ Aucune protection automatique contre les doublons

## 🔄 Migrations disponibles

| Script | Statut | Description |
|--------|--------|-------------|
| `reset_database.sh` | ✅ Actif | Réinitialisation complète |
| `migrate_montant_to_real.sh` | ⚠️ Obsolète | Conversion DECIMAL → REAL |
| `migrate_real_to_numeric.sh` | ✅ Actif | Conversion REAL → NUMERIC(12,2) |
| `migrate_unique_constraint.sh` | ⚠️ Obsolète | Suppression de la contrainte |
| `cleanup_duplicates.sh` | ✅ Actif | Nettoyage des doublons |

## 📝 Historique des types de données

### Colonne `montant`

| Date | Type | Raison |
|------|------|--------|
| 14 oct 2025 | `DECIMAL(12,2)` | Version initiale |
| 15 oct 2025 | `REAL` | Optimisation performance |
| **16 oct 2025** | **`NUMERIC(12,2)`** | **Précision exacte pour montants bancaires** ✅ |
