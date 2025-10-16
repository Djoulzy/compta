-- Migration: Mise à jour de la contrainte d'unicité de la table operations
-- Date: 15 octobre 2025
-- Description: Remplace la contrainte UNIQUE(compte_id, date_operation, libelle)
--              par UNIQUE(compte_id, date_operation, libelle, montant, cb)

BEGIN;

-- Afficher l'ancienne contrainte
SELECT conname, pg_get_constraintdef(oid) as definition
FROM pg_constraint 
WHERE conrelid = 'operations'::regclass 
    AND contype = 'u';

-- Supprimer l'ancienne contrainte d'unicité
ALTER TABLE operations 
    DROP CONSTRAINT IF EXISTS operations_compte_id_date_operation_libelle_key;

-- Ajouter la nouvelle contrainte d'unicité
ALTER TABLE operations 
    ADD CONSTRAINT operations_compte_id_date_operation_libelle_montant_cb_key 
    UNIQUE (compte_id, date_operation, libelle, montant, cb);

-- Afficher la nouvelle contrainte
SELECT conname, pg_get_constraintdef(oid) as definition
FROM pg_constraint 
WHERE conrelid = 'operations'::regclass 
    AND contype = 'u';

COMMIT;

-- Vérifier qu'il n'y a pas de doublons avec le nouveau critère
SELECT compte_id, date_operation, libelle, montant, cb, COUNT(*) as occurrences
FROM operations
GROUP BY compte_id, date_operation, libelle, montant, cb
HAVING COUNT(*) > 1;
