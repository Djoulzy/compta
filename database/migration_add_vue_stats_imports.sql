-- Migration pour créer la vue vue_stats_imports
-- Date: 2025-10-17

CREATE OR REPLACE VIEW vue_stats_imports AS
SELECT 
    i.id,
    i.nom_fichier,
    i.nom_fichier_original,
    i.hash_fichier,
    i.taille_fichier,
    i.statut,
    i.created_at,
    i.updated_at,
    COUNT(o.id) as nombre_operations,
    COALESCE(SUM(CASE WHEN o.debit_credit = 'D' THEN o.montant::numeric ELSE 0 END), 0) as total_debits,
    COALESCE(SUM(CASE WHEN o.debit_credit = 'C' THEN o.montant::numeric ELSE 0 END), 0) as total_credits,
    COUNT(DISTINCT o.compte_id) as nombre_comptes_concernes
FROM imports i
LEFT JOIN operations o ON i.id = o.import_id
GROUP BY i.id, i.nom_fichier, i.nom_fichier_original, i.hash_fichier, i.taille_fichier, 
         i.statut, i.created_at, i.updated_at
ORDER BY i.created_at DESC;