import React from 'react';
import Select from 'react-select';

const OperationsFilters = ({ filters, onFilterChange, onReset, tags, compteId }) => {
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

  const handleReset = () => {
    onReset({
      compte_id: compteId,
      debit_credit: '',
      cb: '',
      tag: [],
      mois: '',
      annee: '',
      recherche: '',
      tri: 'date_operation_desc'
    });
  };

  return (
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
              onChange={(e) => onFilterChange('recherche', e.target.value)}
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
                onFilterChange('tag', selectedValues);
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
              onChange={(e) => onFilterChange('debit_credit', e.target.value)}
            >
              <option value="">Débit/Crédit</option>
              <option value="D">Débit</option>
              <option value="C">Crédit</option>
            </select>
          </div>

          <div className="form-group-compact">
            <select
              value={filters.cb}
              onChange={(e) => onFilterChange('cb', e.target.value)}
            >
              <option value="">CB</option>
              <option value="true">Oui</option>
              <option value="false">Non</option>
            </select>
          </div>

          <div className="form-group-compact">
            <select
              value={filters.mois}
              onChange={(e) => onFilterChange('mois', e.target.value)}
            >
              <option value="">Mois</option>
              {months.map(month => (
                <option key={month.value} value={month.value}>{month.label}</option>
              ))}
            </select>
          </div>

          <div className="form-group-compact">
            <select
              value={filters.annee}
              onChange={(e) => onFilterChange('annee', e.target.value)}
            >
              <option value="">Année</option>
              {years.map(year => (
                <option key={year} value={year}>{year}</option>
              ))}
            </select>
          </div>

          <button
            className="btn btn-secondary btn-compact"
            onClick={handleReset}
            title="Réinitialiser les filtres"
          >
            ⟲
          </button>
        </div>
      </div>
    </div>
  );
};

export default OperationsFilters;
