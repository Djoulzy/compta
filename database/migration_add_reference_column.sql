-- Migration pour ajouter la colonne reference dans la table operations
-- Date: 2025-10-20

-- Ajouter la colonne reference
ALTER TABLE operations 
ADD COLUMN reference VARCHAR(255) NULL;

-- Ajouter une contrainte d'unicité sur la colonne reference
-- PostgreSQL permet plusieurs valeurs NULL dans une contrainte UNIQUE
ALTER TABLE operations 
ADD CONSTRAINT operations_reference_unique UNIQUE (reference);

-- Créer un index pour améliorer les performances des recherches
CREATE INDEX idx_operations_reference ON operations (reference) WHERE reference IS NOT NULL;