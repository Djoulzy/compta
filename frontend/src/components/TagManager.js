import React, { useState, useEffect } from 'react';
import { getTags, createTag, updateTag, deleteTag } from '../services/api';

function TagManager() {
  const [tags, setTags] = useState([]);
  const [editingTag, setEditingTag] = useState(null);
  const [formData, setFormData] = useState({ cle: '', valeur: '' });
  const [message, setMessage] = useState({ type: '', text: '' });

  useEffect(() => {
    const loadTags = async () => {
      try {
        const response = await getTags();
        setTags(response.data);
      } catch (error) {
        showMessage('error', 'Erreur lors du chargement des tags');
      }
    };
    
    loadTags();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  const loadTags = async () => {
    try {
      const response = await getTags();
      setTags(response.data);
    } catch (error) {
      showMessage('error', 'Erreur lors du chargement des tags');
    }
  };

  const showMessage = (type, text) => {
    setMessage({ type, text });
    setTimeout(() => setMessage({ type: '', text: '' }), 3000);
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    
    if (!formData.cle || !formData.valeur) {
      showMessage('error', 'La clé et la valeur sont requises');
      return;
    }

    try {
      if (editingTag) {
        await updateTag(editingTag.id, formData);
        showMessage('success', 'Tag mis à jour avec succès');
      } else {
        await createTag(formData);
        showMessage('success', 'Tag créé avec succès');
      }
      
      setFormData({ cle: '', valeur: '' });
      setEditingTag(null);
      loadTags();
    } catch (error) {
      showMessage('error', error.response?.data?.error || 'Erreur lors de la sauvegarde');
    }
  };

  const handleEdit = (tag) => {
    setEditingTag(tag);
    setFormData({ cle: tag.cle, valeur: tag.valeur });
  };

  const handleDelete = async (id) => {
    if (!window.confirm('Êtes-vous sûr de vouloir supprimer ce tag ?')) {
      return;
    }

    try {
      await deleteTag(id);
      showMessage('success', 'Tag supprimé avec succès');
      loadTags();
    } catch (error) {
      showMessage('error', 'Erreur lors de la suppression');
    }
  };

  const handleCancel = () => {
    setEditingTag(null);
    setFormData({ cle: '', valeur: '' });
  };

  return (
    <div className="card">
      <h2>Gestion des Tags</h2>
      
      {message.text && (
        <div className={`alert alert-${message.type}`}>
          {message.text}
        </div>
      )}

      <div className="alert alert-info mb-3">
        <strong>Information:</strong> Les tags sont automatiquement appliqués aux opérations 
        lorsque le libellé contient la valeur du tag. Après modification d'un tag, 
        toutes les opérations seront automatiquement ré-analysées.
      </div>

      <form onSubmit={handleSubmit} className="mb-3">
        <div className="form-group">
          <label>Nom du tag (clé)</label>
          <input
            type="text"
            placeholder="Ex: supermarche, essence, etc."
            value={formData.cle}
            onChange={(e) => setFormData({ ...formData, cle: e.target.value })}
          />
        </div>

        <div className="form-group">
          <label>Valeur à rechercher</label>
          <input
            type="text"
            placeholder="Ex: CARREFOUR, TOTAL, etc."
            value={formData.valeur}
            onChange={(e) => setFormData({ ...formData, valeur: e.target.value })}
          />
          <small style={{ color: '#7f8c8d', display: 'block', marginTop: '0.25rem' }}>
            Cette valeur sera recherchée dans les libellés des opérations
          </small>
        </div>

        <div>
          <button type="submit" className="btn btn-success">
            {editingTag ? 'Mettre à jour' : 'Ajouter'}
          </button>
          {editingTag && (
            <button type="button" className="btn btn-secondary" onClick={handleCancel}>
              Annuler
            </button>
          )}
        </div>
      </form>

      <h3 className="mt-3 mb-2">Liste des tags</h3>
      
      {tags.length === 0 ? (
        <p className="text-center">Aucun tag défini. Créez votre premier tag ci-dessus.</p>
      ) : (
        <ul className="tag-list">
          {tags.map(tag => (
            <li key={tag.id} className="tag-item">
              <div className="tag-info">
                <strong>{tag.cle}</strong>: {tag.valeur}
              </div>
              <div className="tag-actions">
                <button
                  className="btn btn-secondary"
                  onClick={() => handleEdit(tag)}
                >
                  Modifier
                </button>
                <button
                  className="btn btn-danger"
                  onClick={() => handleDelete(tag.id)}
                >
                  Supprimer
                </button>
              </div>
            </li>
          ))}
        </ul>
      )}
    </div>
  );
}

export default TagManager;
