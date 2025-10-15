import React from 'react';

function CompteSelector({ comptes, onSelect, onRefresh, onImport }) {
  return (
    <div className="card">
      <h2>Sélectionner un compte</h2>

      {comptes.length === 0 ? (
        <div className="text-center">
          <p style={{ marginBottom: '1.5rem', fontSize: '1.1rem' }}>
            Aucun compte disponible. Veuillez importer un fichier CSV pour commencer.
          </p>
          <button className="btn btn-success" onClick={onImport}>
            📤 Importer un fichier CSV
          </button>
        </div>
      ) : (
        <div className="compte-list">
          {comptes.map((compte) => (
            <div
              key={compte.id}
              className="compte-card"
              onClick={() => onSelect(compte)}
            >
              <h3>{compte.label || compte.nom}</h3>
              <p className="compte-nom">{compte.nom}</p>
              {compte.description && <p className="compte-description">{compte.description}</p>}
              <div className="mt-2">
                <p><strong>Opérations:</strong> {compte.nombre_operations || 0}</p>
                <p><strong>Solde:</strong> {parseFloat(compte.solde || 0).toFixed(2)} €</p>
              </div>
            </div>
          ))}
        </div>
      )}
    </div>
  );
}

export default CompteSelector;
