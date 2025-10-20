-- Migration: Ajouter colonnes 'informations_complementaires' et 'type_operation' à la table operations
-- Date: 2025-10-20
-- Description: Ajout de deux nouvelles colonnes STRING optionnelles pour enrichir les données des opérations

-- Ajouter la colonne informations_complementaires
ALTER TABLE operations 
ADD COLUMN informations_complementaires VARCHAR(500) NULL;

-- Ajouter la colonne type_operation
ALTER TABLE operations 
ADD COLUMN type_operation VARCHAR(100) NULL;

-- Créer un index sur type_operation pour optimiser les recherches
CREATE INDEX idx_operations_type_operation ON operations(type_operation) 
WHERE type_operation IS NOT NULL;

-- Commentaires sur les colonnes
COMMENT ON COLUMN operations.informations_complementaires IS 'Informations complémentaires sur l''opération (détails, notes, etc.)';
COMMENT ON COLUMN operations.type_operation IS 'Type de l''opération (virement, prélèvement, carte, etc.)';