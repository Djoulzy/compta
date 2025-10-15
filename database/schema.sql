-- Script de création de la base de données pour l'application de gestion comptable
-- Date: 14 octobre 2025

-- Création de la base de données
-- CREATE DATABASE compta_db;

-- Se connecter à la base de données
-- \c compta_db;

-- Table des comptes bancaires
CREATE TABLE IF NOT EXISTS comptes (
    id SERIAL PRIMARY KEY,
    nom VARCHAR(255) NOT NULL UNIQUE,
    label VARCHAR(255),
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table des tags
CREATE TABLE IF NOT EXISTS tags (
    id SERIAL PRIMARY KEY,
    cle VARCHAR(100) NOT NULL UNIQUE,
    valeur TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table des opérations bancaires
CREATE TABLE IF NOT EXISTS operations (
    id SERIAL PRIMARY KEY,
    fichier VARCHAR(255),
    compte_id INTEGER NOT NULL REFERENCES comptes(id) ON DELETE CASCADE,
    date_operation DATE NOT NULL,
    date_valeur DATE,
    libelle TEXT NOT NULL,
    montant DECIMAL(12, 2) NOT NULL,
    debit_credit CHAR(1) CHECK (debit_credit IN ('D', 'C')),
    cb BOOLEAN DEFAULT FALSE,
    tags JSONB DEFAULT '[]'::jsonb,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    -- Contrainte d'unicité sur compte/date opération/libellé
    UNIQUE (compte_id, date_operation, libelle)
);

-- Index pour améliorer les performances
CREATE INDEX IF NOT EXISTS idx_operations_compte_id ON operations(compte_id);
CREATE INDEX IF NOT EXISTS idx_operations_date_operation ON operations(date_operation);
CREATE INDEX IF NOT EXISTS idx_operations_date_valeur ON operations(date_valeur);
CREATE INDEX IF NOT EXISTS idx_operations_debit_credit ON operations(debit_credit);
CREATE INDEX IF NOT EXISTS idx_operations_cb ON operations(cb);
CREATE INDEX IF NOT EXISTS idx_operations_tags ON operations USING GIN(tags);
CREATE INDEX IF NOT EXISTS idx_operations_libelle ON operations USING GIN(to_tsvector('french', libelle));

-- Fonction pour mettre à jour automatiquement updated_at
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ language 'plpgsql';

-- Triggers pour updated_at
CREATE TRIGGER update_comptes_updated_at BEFORE UPDATE ON comptes
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_tags_updated_at BEFORE UPDATE ON tags
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_operations_updated_at BEFORE UPDATE ON operations
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- Vue pour les statistiques par compte
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

-- Insertion de données de test (optionnel)
-- INSERT INTO comptes (nom, description) VALUES ('Compte courant', 'Compte principal');
-- INSERT INTO tags (cle, valeur) VALUES ('supermarche', 'CARREFOUR');
-- INSERT INTO tags (cle, valeur) VALUES ('essence', 'TOTAL');
