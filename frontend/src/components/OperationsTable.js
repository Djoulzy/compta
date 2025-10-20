import React, { useState, useEffect, useImperativeHandle, forwardRef } from 'react';
import Select from 'react-select';
import { getOperations, getTags } from '../services/api';

const OperationsTable = forwardRef(({ compteId, onFiltersChange }, ref) => {
  const [operations, setOperations] = useState([]);
  const [tags, setTags] = useState([]);
  const [filters, setFilters] = useState({
    compte_id: compteId,
    debit_credit: '',
    cb: '',
    tag: [],
    mois: '',
    annee: '',
    recherche: '',
    tri: 'date_operation_desc'
  });
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    loadTags();
  }, []);

  const loadTags = async () => {
    try {
      const response = await getTags();
      setTags(response.data);
    } catch (error) {
      console.error('Erreur lors du chargement des tags:', error);
    }
  };

  const loadOperations = async () => {
    setLoading(true);
    try {
      const response = await getOperations(filters);
      setOperations(response.data);

      // Notifier le parent des changements de filtres pour la balance sticky
      if (onFiltersChange) {
        onFiltersChange(filters);
      }
    } catch (error) {
      console.error('Erreur lors du chargement des opérations:', error);
    } finally {
      setLoading(false);
    }
  };

  // Exposer les méthodes de rafraîchissement au parent via ref
  useImperativeHandle(ref, () => ({
    refreshOperations: loadOperations,
    refreshTags: loadTags,
    refreshAll: async () => {
      await loadTags();
      await loadOperations();
    }
  }));

  useEffect(() => {
    setFilters(prev => ({ ...prev, compte_id: compteId }));
  }, [compteId]);

  useEffect(() => {
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
      <div className="card filters-card">
        <h3>Filtres</h3>

        <div className="filters-compact">
          {/* Première ligne : Recherche et Tags (plus large) */}
          <div className="filters-row-main">
            <div className="form-group-search">
              <input
                type="text"
                placeholder="Rechercher dans les libellés..."
                value={filters.recherche}
                onChange={(e) => handleFilterChange('recherche', e.target.value)}
              />
            </div>
            <div className="form-group-tags">
              <Select
                isMulti
                value={filters.tag.map(tagValue => ({
                  value: tagValue,
                  label: tagValue
                }))}
                onChange={(selectedOptions) => {
                  const selectedValues = selectedOptions ? selectedOptions.map(option => option.value) : [];
                  handleFilterChange('tag', selectedValues);
                }}
                options={tags.map(tag => ({
                  value: tag.cle,
                  label: tag.cle
                }))}
                placeholder="Sélectionner des tags..."
                className="react-select-container"
                classNamePrefix="react-select"
                isClearable
                isSearchable
                noOptionsMessage={() => "Aucun tag disponible"}
                loadingMessage={() => "Chargement..."}
              />
            </div>
          </div>

          {/* Deuxième ligne : Autres filtres */}
          <div className="filters-row-secondary">
            <div className="form-group-compact">
              <select
                value={filters.debit_credit}
                onChange={(e) => handleFilterChange('debit_credit', e.target.value)}
              >
                <option value="">Débit/Crédit</option>
                <option value="D">Débit</option>
                <option value="C">Crédit</option>
              </select>
            </div>

            <div className="form-group-compact">
              <select
                value={filters.cb}
                onChange={(e) => handleFilterChange('cb', e.target.value)}
              >
                <option value="">Carte bancaire</option>
                <option value="true">Oui</option>
                <option value="false">Non</option>
              </select>
            </div>

            <div className="form-group-compact">
              <select
                value={filters.mois}
                onChange={(e) => handleFilterChange('mois', e.target.value)}
              >
                <option value="">Tous les mois</option>
                {months.map(month => (
                  <option key={month.value} value={month.value}>{month.label}</option>
                ))}
              </select>
            </div>

            <div className="form-group-compact">
              <select
                value={filters.annee}
                onChange={(e) => handleFilterChange('annee', e.target.value)}
              >
                <option value="">Toutes les années</option>
                {years.map(year => (
                  <option key={year} value={year}>{year}</option>
                ))}
              </select>
            </div>

            <button
              className="btn btn-secondary btn-compact"
              onClick={() => setFilters({
                compte_id: compteId,
                debit_credit: '',
                cb: '',
                tag: [],
                mois: '',
                annee: '',
                recherche: '',
                tri: 'date_operation_desc'
              })}
              title="Réinitialiser les filtres"
            >
              ⟲
            </button>
          </div>
        </div>
      </div>

      <div className="card operations-card">
        <div className="operations-header">
          <h3>Opérations</h3>
          {!loading && operations.length > 0 && (
            <span className="operations-count">{operations.length} opération{operations.length > 1 ? 's' : ''}</span>
          )}
        </div>

        {loading ? (
          <div className="loading">Chargement...</div>
        ) : operations.length === 0 ? (
          <p className="text-center">Aucune opération trouvée.</p>
        ) : (
          <div className="operations-table-container">
            <table className="operations-table">
              <thead>
                <tr>
                  <th onClick={() => handleSort('date_operation')}>
                    Date op.{getSortIcon('date_operation')}
                  </th>
                  <th onClick={() => handleSort('date_valeur')}>
                    Date val.{getSortIcon('date_valeur')}
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
                    <td>
                      <div>
                        <div style={{ fontWeight: '500', marginBottom: '2px' }}>
                          {operation.libelle}
                        </div>
                        {operation.informations_complementaires && (
                          <div style={{
                            fontSize: '0.8rem',
                            color: '#666',
                            lineHeight: '1.3',
                            width: '100%',
                            overflow: 'hidden',
                            textOverflow: 'ellipsis',
                            whiteSpace: 'nowrap'
                          }} title={operation.informations_complementaires}>
                            {operation.informations_complementaires.length > 90
                              ? operation.informations_complementaires.substring(0, 90)
                              : operation.informations_complementaires}
                          </div>
                        )}
                      </div>
                    </td>
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
});

export default OperationsTable;
