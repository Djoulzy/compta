-- Migration: Conversion de la colonne montant de REAL vers NUMERIC(12,2)
-- Date: 16 octobre 2025
-- Description: Change le type de données de la colonne montant pour utiliser NUMERIC au lieu de REAL

BEGIN;

-- Supprimer temporairement la vue qui dépend de la colonne montant
DROP VIEW IF EXISTS vue_balance_comptes;

-- Modifier le type de la colonne montant
ALTER TABLE operations 
    ALTER COLUMN montant TYPE NUMERIC(12, 2);

-- Recréer la vue avec le nouveau type
CREATE OR REPLACE VIEW vue_balance_comptes AS
SELECT 
    c.id,
    c.nom,
    COUNT(o.id) as nombre_operations,
    SUM(CASE WHEN o.debit_credit = 'D' THEN ABS(o.montant) ELSE 0 END) as total_debits,
    SUM(CASE WHEN o.debit_credit = 'C' THEN ABS(o.montant) ELSE 0 END) as total_credits,
    SUM(o.montant) as solde
FROM comptes c
LEFT JOIN operations o ON c.id = o.compte_id
GROUP BY c.id, c.nom;

-- Vérifier que la modification a bien été appliquée
SELECT 
    column_name, 
    data_type, 
    character_maximum_length,
    numeric_precision,
    numeric_scale
FROM information_schema.columns 
WHERE table_name = 'operations' 
    AND column_name = 'montant';

COMMIT;

-- Afficher un résumé des données après migration
SELECT 
    COUNT(*) as nombre_operations,
    MIN(montant) as montant_min,
    MAX(montant) as montant_max,
    AVG(montant) as montant_moyen,
    SUM(montant) as somme_totale
FROM operations;
