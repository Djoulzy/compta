-- Migration pour supprimer la colonne compte_principal_id de la table imports
-- Date: 17 octobre 2025
-- Raison: Un import peut contenir des opérations pour plusieurs comptes différents

-- Supprimer d'abord la vue qui dépend de la colonne
DROP VIEW IF EXISTS vue_stats_imports;

-- Supprimer la contrainte de clé étrangère et la colonne
DO $$ 
BEGIN
    -- Supprimer la colonne compte_principal_id si elle existe
    IF EXISTS (
        SELECT 1 
        FROM information_schema.columns 
        WHERE table_name = 'imports' 
        AND column_name = 'compte_principal_id'
    ) THEN
        ALTER TABLE imports DROP COLUMN compte_principal_id;
        RAISE NOTICE 'Colonne compte_principal_id supprimée de la table imports';
    ELSE
        RAISE NOTICE 'Colonne compte_principal_id n''existe pas dans la table imports';
    END IF;
END $$;

-- Recréer la vue des statistiques d'imports sans référence au compte principal
CREATE OR REPLACE VIEW vue_stats_imports AS
SELECT 
    i.id,
    i.nom_fichier_original,
    i.nom_fichier,
    i.taille_fichier,
    i.statut,
    i.nombre_operations,
    i.nombre_erreurs,
    i.created_at,
    COUNT(DISTINCT o.compte_id) as nombre_comptes_distincts,
    COUNT(o.id) as operations_actuelles,
    STRING_AGG(DISTINCT c.nom, ', ' ORDER BY c.nom) as comptes_concernes
FROM imports i
LEFT JOIN operations o ON i.id = o.import_id
LEFT JOIN comptes c ON o.compte_id = c.id
GROUP BY i.id, i.nom_fichier_original, i.nom_fichier, i.taille_fichier, i.statut, 
         i.nombre_operations, i.nombre_erreurs, i.created_at
ORDER BY i.created_at DESC;

COMMENT ON VIEW vue_stats_imports IS 'Vue avec statistiques détaillées des imports incluant les comptes concernés';