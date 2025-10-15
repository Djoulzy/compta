import React, { useState, useEffect, useRef } from 'react';
import './App.css';
import CompteSelector from './components/CompteSelector';
import OperationsTable from './components/OperationsTable';
import TagManager from './components/TagManager';
import ImportCSV from './components/ImportCSV';
import CompteManager from './components/CompteManager';
import BalanceSticky from './components/BalanceSticky';
import { getComptes } from './services/api';

function App() {
  const [currentView, setCurrentView] = useState('operations'); // operations, import
  const [selectedCompte, setSelectedCompte] = useState(null);
  const [showCompteSelector, setShowCompteSelector] = useState(true);
  const [comptes, setComptes] = useState([]);
  const [showCompteManager, setShowCompteManager] = useState(false);
  const [showTagManager, setShowTagManager] = useState(false);
  const [currentFilters, setCurrentFilters] = useState({});

  // Ref pour accéder aux méthodes d'OperationsTable
  const operationsTableRef = useRef();

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

  const handleFiltersChange = (filters) => {
    setCurrentFilters(filters);
  };

  const handleTagsUpdated = () => {
    // Rafraîchir les opérations quand les tags sont modifiés
    if (operationsTableRef.current) {
      operationsTableRef.current.refreshAll();
    }
  };

  return (
    <div className="app">
      <header className="header">
        <div className="header-content">
          <h1>💰 Compta</h1>
          {!showCompteSelector && (
            <nav className="menu">
              {selectedCompte && (
                <button onClick={handleChangeCompte} title="Choisir un autre compte">
                  🔄 Autre Compte
                </button>
              )}
              <button onClick={() => setShowCompteManager(true)} title="Gérer les comptes">
                ⚙️ Editer Comptes
              </button>
              <button onClick={() => setShowTagManager(true)} title="Gérer les tags">
                🏷️ Tags
              </button>
              <button onClick={() => setCurrentView('import')} title="Importer un fichier CSV">
                📥 Import CSV
              </button>
            </nav>
          )}
        </div>
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
                  <h2>Compte: {selectedCompte.label || selectedCompte.nom}</h2>
                  <p className="compte-nom">Numéro: {selectedCompte.nom}</p>
                  {selectedCompte.description && (
                    <p>{selectedCompte.description}</p>
                  )}
                </div>
                <OperationsTable
                  ref={operationsTableRef}
                  compteId={selectedCompte.id}
                  onFiltersChange={handleFiltersChange}
                />
              </>
            )}

            {currentView === 'import' && (
              <ImportCSV onComplete={handleImportComplete} />
            )}
          </>
        )}

        {showCompteManager && (
          <CompteManager
            onClose={() => setShowCompteManager(false)}
            onUpdate={loadComptes}
          />
        )}

        {showTagManager && (
          <TagManager
            onClose={() => setShowTagManager(false)}
            onUpdate={handleTagsUpdated}
          />
        )}

        {/* Balance Sticky - Affichée seulement quand on visualise les opérations */}
        {!showCompteSelector && currentView === 'operations' && selectedCompte && (
          <BalanceSticky
            compteId={selectedCompte.id}
            filters={currentFilters}
          />
        )}
      </main>
    </div>
  );
}

export default App;
