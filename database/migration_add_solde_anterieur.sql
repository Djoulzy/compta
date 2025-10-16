-- Migration pour ajouter la colonne solde_anterieur à la table comptes
-- Date: 16 octobre 2025

-- Ajouter la colonne solde_anterieur si elle n'existe pas
DO $$ 
BEGIN
    IF NOT EXISTS (
        SELECT 1 
        FROM information_schema.columns 
        WHERE table_name = 'comptes' 
        AND column_name = 'solde_anterieur'
    ) THEN
        ALTER TABLE comptes ADD COLUMN solde_anterieur REAL DEFAULT 0;
        COMMENT ON COLUMN comptes.solde_anterieur IS 'Solde de départ du compte avant les opérations importées';
    END IF;
END $$;

-- Mettre à jour la vue pour inclure le solde antérieur
CREATE OR REPLACE VIEW vue_balance_comptes AS
SELECT 
    c.id,
    c.nom,
    c.solde_anterieur,
    COUNT(o.id) as nombre_operations,
    SUM(CASE WHEN o.debit_credit = 'D' THEN ABS(o.montant) ELSE 0 END) as total_debits,
    SUM(CASE WHEN o.debit_credit = 'C' THEN ABS(o.montant) ELSE 0 END) as total_credits,
    SUM(o.montant) as solde_operations,
    (c.solde_anterieur + COALESCE(SUM(o.montant), 0)) as solde_total
FROM comptes c
LEFT JOIN operations o ON c.id = o.compte_id
GROUP BY c.id, c.nom, c.solde_anterieur;

COMMENT ON VIEW vue_balance_comptes IS 'Vue calculant les statistiques et solde total (antérieur + opérations) par compte';