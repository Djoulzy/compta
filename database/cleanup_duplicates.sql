-- Script de nettoyage des doublons dans la table operations
-- Date: 16 octobre 2025
-- Description: Supprime les doublons en gardant la première occurrence (ID le plus petit)

BEGIN;

-- Afficher les doublons avant suppression
SELECT 
    compte_id, 
    date_operation, 
    libelle, 
    montant, 
    cb, 
    COUNT(*) as occurrences,
    array_agg(id ORDER BY id) as ids
FROM operations 
GROUP BY compte_id, date_operation, libelle, montant, cb 
HAVING COUNT(*) > 1
ORDER BY COUNT(*) DESC, date_operation DESC
LIMIT 20;

-- Compter le nombre total de doublons à supprimer
SELECT COUNT(*) as doublons_a_supprimer
FROM (
    SELECT id
    FROM (
        SELECT 
            id,
            ROW_NUMBER() OVER (
                PARTITION BY compte_id, date_operation, libelle, montant, cb 
                ORDER BY id
            ) as rn
        FROM operations
    ) t
    WHERE rn > 1
) doublons;

-- Supprimer les doublons (garde le premier, supprime les suivants)
DELETE FROM operations
WHERE id IN (
    SELECT id
    FROM (
        SELECT 
            id,
            ROW_NUMBER() OVER (
                PARTITION BY compte_id, date_operation, libelle, montant, cb 
                ORDER BY id
            ) as rn
        FROM operations
    ) t
    WHERE rn > 1
);

-- Afficher le résultat
SELECT 
    COUNT(*) as total_operations_restantes,
    COUNT(DISTINCT (compte_id, date_operation, libelle, montant, cb)) as operations_uniques
FROM operations;

COMMIT;

-- Vérifier qu'il ne reste plus de doublons
SELECT 
    compte_id, 
    date_operation, 
    libelle, 
    montant, 
    cb, 
    COUNT(*) as occurrences
FROM operations 
GROUP BY compte_id, date_operation, libelle, montant, cb 
HAVING COUNT(*) > 1;
