import React, { useState, useEffect } from 'react';
import { getOperations, getTags } from '../services/api';
import Balance from './Balance';

function OperationsTable({ compteId }) {
  const [operations, setOperations] = useState([]);
  const [tags, setTags] = useState([]);
  const [filters, setFilters] = useState({
    compte_id: compteId,
    debit_credit: '',
    cb: '',
    tag: '',
    mois: '',
    annee: '',
    recherche: '',
    tri: 'date_operation_desc'
  });
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    const loadTags = async () => {
      try {
        const response = await getTags();
        setTags(response.data);
      } catch (error) {
        console.error('Erreur lors du chargement des tags:', error);
      }
    };
    
    loadTags();
  }, []);

  useEffect(() => {
    setFilters(prev => ({ ...prev, compte_id: compteId }));
  }, [compteId]);

  useEffect(() => {
    const loadOperations = async () => {
      setLoading(true);
      try {
        const response = await getOperations(filters);
        setOperations(response.data);
      } catch (error) {
        console.error('Erreur lors du chargement des opérations:', error);
      } finally {
        setLoading(false);
      }
    };
    
    loadOperations();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [JSON.stringify(filters)]);

  const handleFilterChange = (name, value) => {
    setFilters(prev => ({ ...prev, [name]: value }));
  };

  const handleSort = (field) => {
    const currentSort = filters.tri;
    let newSort = '';

    if (currentSort === `${field}_desc`) {
      newSort = `${field}_asc`;
    } else {
      newSort = `${field}_desc`;
    }

    setFilters(prev => ({ ...prev, tri: newSort }));
  };

  const getSortIcon = (field) => {
    if (filters.tri === `${field}_asc`) return ' ▲';
    if (filters.tri === `${field}_desc`) return ' ▼';
    return '';
  };

  const formatDate = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleDateString('fr-FR');
  };

  const currentYear = new Date().getFullYear();
  const years = Array.from({ length: 10 }, (_, i) => currentYear - i);
  const months = [
    { value: '1', label: 'Janvier' },
    { value: '2', label: 'Février' },
    { value: '3', label: 'Mars' },
    { value: '4', label: 'Avril' },
    { value: '5', label: 'Mai' },
    { value: '6', label: 'Juin' },
    { value: '7', label: 'Juillet' },
    { value: '8', label: 'Août' },
    { value: '9', label: 'Septembre' },
    { value: '10', label: 'Octobre' },
    { value: '11', label: 'Novembre' },
    { value: '12', label: 'Décembre' }
  ];

  return (
    <>
      <Balance compteId={compteId} filters={filters} />
      
      <div className="card">
        <h2>Filtres et Recherche</h2>
        
        <div className="filters">
          <div className="form-group">
            <label>Recherche (Libellé)</label>
            <input
              type="text"
              placeholder="Rechercher..."
              value={filters.recherche}
              onChange={(e) => handleFilterChange('recherche', e.target.value)}
            />
          </div>

          <div className="form-group">
            <label>Débit/Crédit</label>
            <select
              value={filters.debit_credit}
              onChange={(e) => handleFilterChange('debit_credit', e.target.value)}
            >
              <option value="">Tous</option>
              <option value="D">Débit</option>
              <option value="C">Crédit</option>
            </select>
          </div>

          <div className="form-group">
            <label>Carte Bancaire</label>
            <select
              value={filters.cb}
              onChange={(e) => handleFilterChange('cb', e.target.value)}
            >
              <option value="">Tous</option>
              <option value="true">Oui</option>
              <option value="false">Non</option>
            </select>
          </div>

          <div className="form-group">
            <label>Tag</label>
            <select
              value={filters.tag}
              onChange={(e) => handleFilterChange('tag', e.target.value)}
            >
              <option value="">Tous</option>
              {tags.map(tag => (
                <option key={tag.id} value={tag.cle}>{tag.cle}</option>
              ))}
            </select>
          </div>

          <div className="form-group">
            <label>Mois</label>
            <select
              value={filters.mois}
              onChange={(e) => handleFilterChange('mois', e.target.value)}
            >
              <option value="">Tous</option>
              {months.map(month => (
                <option key={month.value} value={month.value}>{month.label}</option>
              ))}
            </select>
          </div>

          <div className="form-group">
            <label>Année</label>
            <select
              value={filters.annee}
              onChange={(e) => handleFilterChange('annee', e.target.value)}
            >
              <option value="">Toutes</option>
              {years.map(year => (
                <option key={year} value={year}>{year}</option>
              ))}
            </select>
          </div>
        </div>

        <button
          className="btn btn-secondary mt-2"
          onClick={() => setFilters({
            compte_id: compteId,
            debit_credit: '',
            cb: '',
            tag: '',
            mois: '',
            annee: '',
            recherche: '',
            tri: 'date_operation_desc'
          })}
        >
          Réinitialiser les filtres
        </button>
      </div>

      <div className="card">
        <h2>Opérations</h2>
        
        {loading ? (
          <div className="loading">Chargement...</div>
        ) : operations.length === 0 ? (
          <p className="text-center">Aucune opération trouvée.</p>
        ) : (
          <div style={{ overflowX: 'auto' }}>
            <table>
              <thead>
                <tr>
                  <th onClick={() => handleSort('date_operation')}>
                    Date opération{getSortIcon('date_operation')}
                  </th>
                  <th onClick={() => handleSort('date_valeur')}>
                    Date valeur{getSortIcon('date_valeur')}
                  </th>
                  <th>Libellé</th>
                  <th>Montant</th>
                  <th>Type</th>
                  <th>CB</th>
                  <th>Tags</th>
                </tr>
              </thead>
              <tbody>
                {operations.map(operation => (
                  <tr key={operation.id}>
                    <td>{formatDate(operation.date_operation)}</td>
                    <td>{formatDate(operation.date_valeur)}</td>
                    <td>{operation.libelle}</td>
                    <td style={{ textAlign: 'right' }}>
                      <span style={{ 
                        color: operation.debit_credit === 'D' ? '#e74c3c' : '#27ae60',
                        fontWeight: 'bold'
                      }}>
                        {parseFloat(operation.montant).toFixed(2)} €
                      </span>
                    </td>
                    <td>
                      <span style={{
                        padding: '0.25rem 0.5rem',
                        borderRadius: '4px',
                        fontSize: '0.85rem',
                        backgroundColor: operation.debit_credit === 'D' ? '#f8d7da' : '#d4edda',
                        color: operation.debit_credit === 'D' ? '#721c24' : '#155724'
                      }}>
                        {operation.debit_credit === 'D' ? 'Débit' : 'Crédit'}
                      </span>
                    </td>
                    <td>{operation.cb ? 'Oui' : 'Non'}</td>
                    <td>
                      {operation.tags && JSON.parse(operation.tags).length > 0 ? (
                        JSON.parse(operation.tags).map((tag, idx) => (
                          <span
                            key={idx}
                            style={{
                              display: 'inline-block',
                              padding: '0.2rem 0.5rem',
                              margin: '0.1rem',
                              backgroundColor: '#3498db',
                              color: 'white',
                              borderRadius: '4px',
                              fontSize: '0.8rem'
                            }}
                          >
                            {tag.cle}
                          </span>
                        ))
                      ) : (
                        '-'
                      )}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </div>
    </>
  );
}

export default OperationsTable;
