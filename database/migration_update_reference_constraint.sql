-- Migration: Modification de la contrainte d'unicité sur reference
-- Date: 20 octobre 2025
-- Description: Remplace la contrainte d'unicité sur reference seule par une contrainte sur (reference, compte_id)

BEGIN;

-- Supprimer l'ancienne contrainte d'unicité
ALTER TABLE operations DROP CONSTRAINT IF EXISTS operations_reference_unique;

-- Ajouter la nouvelle contrainte d'unicité sur (reference, compte_id)
ALTER TABLE operations ADD CONSTRAINT operations_reference_compte_unique UNIQUE (reference, compte_id);

-- Mettre à jour l'index correspondant si nécessaire
DROP INDEX IF EXISTS idx_operations_reference;
CREATE INDEX IF NOT EXISTS idx_operations_reference_compte ON operations (reference, compte_id) WHERE reference IS NOT NULL;

COMMIT;

-- Vérification de la contrainte
SELECT 
    conname as constraint_name,
    contype as constraint_type,
    pg_get_constraintdef(oid) as constraint_definition
FROM pg_constraint 
WHERE conrelid = 'operations'::regclass 
AND conname LIKE '%reference%';