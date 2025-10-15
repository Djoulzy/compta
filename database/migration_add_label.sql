-- Migration pour ajouter le champ label aux comptes
-- Date: 15 octobre 2025

-- Ajouter la colonne label si elle n'existe pas déjà
DO $$ 
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_name = 'comptes' AND column_name = 'label'
    ) THEN
        ALTER TABLE comptes ADD COLUMN label VARCHAR(255);
        
        -- Optionnel: initialiser avec le nom par défaut pour les comptes existants
        UPDATE comptes SET label = nom WHERE label IS NULL;
        
        RAISE NOTICE 'Colonne label ajoutée à la table comptes';
    ELSE
        RAISE NOTICE 'Colonne label existe déjà dans la table comptes';
    END IF;
END $$;