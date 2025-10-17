-- Migration pour ajouter la gestion des imports
-- Date: 17 octobre 2025

-- Créer la table imports si elle n'existe pas
CREATE TABLE IF NOT EXISTS imports (
    id SERIAL PRIMARY KEY,
    nom_fichier VARCHAR(255) NOT NULL,
    nom_fichier_original VARCHAR(255) NOT NULL,
    taille_fichier INTEGER,
    hash_fichier VARCHAR(64) UNIQUE,
    nombre_operations INTEGER DEFAULT 0,
    nombre_erreurs INTEGER DEFAULT 0,
    statut VARCHAR(20) DEFAULT 'en_cours' CHECK (statut IN ('en_cours', 'termine', 'erreur')),
    compte_principal_id INTEGER REFERENCES comptes(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Ajouter la colonne import_id à la table operations si elle n'existe pas
DO $$ 
BEGIN
    IF NOT EXISTS (
        SELECT 1 
        FROM information_schema.columns 
        WHERE table_name = 'operations' 
        AND column_name = 'import_id'
    ) THEN
        ALTER TABLE operations ADD COLUMN import_id INTEGER REFERENCES imports(id) ON DELETE CASCADE;
        COMMENT ON COLUMN operations.import_id IS 'Référence vers l''import qui a créé cette opération';
    END IF;
END $$;

-- Créer les index pour la table imports
CREATE INDEX IF NOT EXISTS idx_imports_hash ON imports(hash_fichier);
CREATE INDEX IF NOT EXISTS idx_imports_statut ON imports(statut);
CREATE INDEX IF NOT EXISTS idx_imports_created_at ON imports(created_at);

-- Créer l'index pour import_id dans operations
CREATE INDEX IF NOT EXISTS idx_operations_import_id ON operations(import_id);

-- Ajouter le trigger updated_at pour imports
CREATE TRIGGER update_imports_updated_at BEFORE UPDATE ON imports
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- Créer une vue pour les statistiques d'imports
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
    c.nom as compte_principal,
    COUNT(o.id) as operations_actuelles
FROM imports i
LEFT JOIN comptes c ON i.compte_principal_id = c.id
LEFT JOIN operations o ON i.id = o.import_id
GROUP BY i.id, i.nom_fichier_original, i.nom_fichier, i.taille_fichier, i.statut, 
         i.nombre_operations, i.nombre_erreurs, i.created_at, c.nom
ORDER BY i.created_at DESC;

COMMENT ON TABLE imports IS 'Table stockant l''historique des fichiers CSV importés';
COMMENT ON VIEW vue_stats_imports IS 'Vue avec statistiques détaillées des imports';