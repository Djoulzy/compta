-- Migration pour supprimer la contrainte d'unicité sur la table operations
-- Date: 16 octobre 2025

-- Trouver le nom de la contrainte d'unicité
DO $$ 
DECLARE
    constraint_name TEXT;
BEGIN
    -- Récupérer le nom de la contrainte d'unicité
    SELECT conname INTO constraint_name
    FROM pg_constraint 
    WHERE conrelid = 'operations'::regclass 
    AND contype = 'u';
    
    -- Supprimer la contrainte si elle existe
    IF constraint_name IS NOT NULL THEN
        EXECUTE 'ALTER TABLE operations DROP CONSTRAINT ' || constraint_name;
        RAISE NOTICE 'Contrainte d''unicité % supprimée de la table operations', constraint_name;
    ELSE
        RAISE NOTICE 'Aucune contrainte d''unicité trouvée sur la table operations';
    END IF;
END $$;

COMMENT ON TABLE operations IS 'Table des opérations bancaires - contrainte d''unicité supprimée pour permettre les doublons';