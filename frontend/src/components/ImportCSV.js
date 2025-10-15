import React, { useState } from 'react';
import { importCSV } from '../services/api';

function ImportCSV({ onComplete }) {
  const [file, setFile] = useState(null);
  const [uploading, setUploading] = useState(false);
  const [result, setResult] = useState(null);
  const [dragOver, setDragOver] = useState(false);

  const handleFileSelect = (e) => {
    const selectedFile = e.target.files[0];
    if (selectedFile) {
      setFile(selectedFile);
      setResult(null);
    }
  };

  const handleDrop = (e) => {
    e.preventDefault();
    setDragOver(false);
    
    const droppedFile = e.dataTransfer.files[0];
    if (droppedFile && droppedFile.name.endsWith('.csv')) {
      setFile(droppedFile);
      setResult(null);
    }
  };

  const handleDragOver = (e) => {
    e.preventDefault();
    setDragOver(true);
  };

  const handleDragLeave = () => {
    setDragOver(false);
  };

  const handleUpload = async () => {
    if (!file) {
      alert('Veuillez sélectionner un fichier');
      return;
    }

    setUploading(true);
    setResult(null);

    const formData = new FormData();
    formData.append('file', file);

    try {
      const response = await importCSV(formData);
      setResult(response.data);
      setFile(null);
      
      if (response.data.success) {
        setTimeout(() => {
          if (onComplete) {
            onComplete();
          }
        }, 2000);
      }
    } catch (error) {
      setResult({
        success: false,
        error: error.response?.data?.error || 'Erreur lors de l\'import'
      });
    } finally {
      setUploading(false);
    }
  };

  return (
    <div className="card">
      <h2>Importer un fichier CSV</h2>
      
      <div className="alert alert-info mb-3">
        <strong>Format attendu:</strong><br />
        Fichier, Compte, Date opération, Date valeur, Libellé, Montant, Débit/Crédit, CB
        <br /><br />
        <strong>Remarques:</strong>
        <ul style={{ marginTop: '0.5rem', marginLeft: '1.5rem' }}>
          <li>Les dates doivent être au format <strong>AAAA-MM-JJ</strong> (ISO 8601) ou JJ/MM/AAAA</li>
          <li>Les montants peuvent utiliser la virgule ou le point comme séparateur décimal</li>
          <li>Débit/Crédit: utilisez "Débit"/"Crédit" ou "D"/"C"</li>
          <li>CB: utilisez "True"/"False", "Oui"/"Non", ou "1"/"0"</li>
          <li>Si le compte n'existe pas, il sera créé automatiquement</li>
          <li>Les tags existants seront appliqués automatiquement</li>
          <li>Les doublons (même compte + date + libellé) seront mis à jour</li>
        </ul>
      </div>

      <div
        className={`upload-area ${dragOver ? 'drag-over' : ''}`}
        onDrop={handleDrop}
        onDragOver={handleDragOver}
        onDragLeave={handleDragLeave}
        onClick={() => document.getElementById('file-input').click()}
      >
        <input
          id="file-input"
          type="file"
          accept=".csv"
          onChange={handleFileSelect}
          style={{ display: 'none' }}
        />
        
        {file ? (
          <div>
            <p style={{ fontSize: '1.2rem', marginBottom: '0.5rem' }}>📄 {file.name}</p>
            <p style={{ color: '#7f8c8d' }}>Taille: {(file.size / 1024).toFixed(2)} KB</p>
          </div>
        ) : (
          <div>
            <p style={{ fontSize: '1.2rem', marginBottom: '0.5rem' }}>
              📤 Glissez-déposez un fichier CSV ici
            </p>
            <p style={{ color: '#7f8c8d' }}>ou cliquez pour sélectionner un fichier</p>
          </div>
        )}
      </div>

      {file && (
        <div className="mt-2 text-center">
          <button
            className="btn btn-success"
            onClick={handleUpload}
            disabled={uploading}
          >
            {uploading ? 'Import en cours...' : 'Importer le fichier'}
          </button>
          <button
            className="btn btn-secondary"
            onClick={() => setFile(null)}
            disabled={uploading}
          >
            Annuler
          </button>
        </div>
      )}

      {result && (
        <div className={`alert ${result.success ? 'alert-success' : 'alert-error'} mt-3`}>
          {result.success ? (
            <>
              <h3 style={{ marginBottom: '0.5rem' }}>✓ Import réussi !</h3>
              <p><strong>Total de lignes traitées:</strong> {result.stats.total}</p>
              <p><strong>Opérations importées:</strong> {result.stats.inserted}</p>
              
              {result.stats.nouveaux_comptes.length > 0 && (
                <>
                  <p style={{ marginTop: '0.5rem' }}>
                    <strong>Nouveaux comptes créés:</strong>
                  </p>
                  <ul style={{ marginLeft: '1.5rem' }}>
                    {result.stats.nouveaux_comptes.map((compte, idx) => (
                      <li key={idx}>{compte}</li>
                    ))}
                  </ul>
                </>
              )}
              
              {result.stats.errors.length > 0 && (
                <>
                  <p style={{ marginTop: '0.5rem', color: '#e67e22' }}>
                    <strong>Avertissements:</strong>
                  </p>
                  <ul style={{ marginLeft: '1.5rem', fontSize: '0.9rem' }}>
                    {result.stats.errors.slice(0, 10).map((error, idx) => (
                      <li key={idx}>{error}</li>
                    ))}
                    {result.stats.errors.length > 10 && (
                      <li>... et {result.stats.errors.length - 10} autres erreurs</li>
                    )}
                  </ul>
                </>
              )}
              
              <p style={{ marginTop: '1rem', fontStyle: 'italic' }}>
                Redirection vers la page des opérations...
              </p>
            </>
          ) : (
            <>
              <h3 style={{ marginBottom: '0.5rem' }}>✗ Erreur lors de l'import</h3>
              <p>{result.error}</p>
            </>
          )}
        </div>
      )}
    </div>
  );
}

export default ImportCSV;
