import React, { useState, useEffect } from 'react';
import './App.css';
import CompteSelector from './components/CompteSelector';
import OperationsTable from './components/OperationsTable';
import TagManager from './components/TagManager';
import ImportCSV from './components/ImportCSV';
import { getComptes } from './services/api';

function App() {
  const [currentView, setCurrentView] = useState('operations'); // operations, tags, import
  const [selectedCompte, setSelectedCompte] = useState(null);
  const [showCompteSelector, setShowCompteSelector] = useState(true);
  const [comptes, setComptes] = useState([]);

  useEffect(() => {
    loadComptes();
  }, []);

  const loadComptes = async () => {
    try {
      const response = await getComptes();
      setComptes(response.data);
      // Ne pas changer automatiquement la vue, laisser l'utilisateur décider
    } catch (error) {
      console.error('Erreur lors du chargement des comptes:', error);
    }
  };

  const handleCompteSelect = (compte) => {
    setSelectedCompte(compte);
    setShowCompteSelector(false);
    setCurrentView('operations');
  };

  const handleChangeCompte = () => {
    setShowCompteSelector(true);
  };

  const handleImportComplete = () => {
    loadComptes();
    setShowCompteSelector(true);
    setCurrentView('operations');
  };

  const handleStartImport = () => {
    setShowCompteSelector(false);
    setCurrentView('import');
  };

  return (
    <div className="app">
      <header className="header">
        <h1>Gestion Comptable</h1>
        {!showCompteSelector && (
          <nav className="menu">
            {selectedCompte && (
              <button onClick={handleChangeCompte}>
                Choisir un autre compte
              </button>
            )}
            <button onClick={() => setCurrentView('tags')}>
              Gérer les tags
            </button>
            <button onClick={() => setCurrentView('import')}>
              Importer un fichier CSV
            </button>
          </nav>
        )}
      </header>

      <main className="container">
        {showCompteSelector ? (
          <CompteSelector
            comptes={comptes}
            onSelect={handleCompteSelect}
            onRefresh={loadComptes}
            onImport={handleStartImport}
          />
        ) : (
          <>
            {currentView === 'operations' && selectedCompte && (
              <>
                <div className="card">
                  <h2>Compte: {selectedCompte.nom}</h2>
                  {selectedCompte.description && (
                    <p>{selectedCompte.description}</p>
                  )}
                </div>
                <OperationsTable compteId={selectedCompte.id} />
              </>
            )}

            {currentView === 'tags' && (
              <TagManager />
            )}

            {currentView === 'import' && (
              <ImportCSV onComplete={handleImportComplete} />
            )}
          </>
        )}
      </main>
    </div>
  );
}

export default App;
