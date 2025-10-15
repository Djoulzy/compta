import React, { useState, useEffect } from 'react';
import { getTags, createTag, updateTag, deleteTag } from '../services/api';

function TagManager({ onClose, onUpdate }) {
  const [tags, setTags] = useState([]);
  const [loading, setLoading] = useState(false);
  const [editingTag, setEditingTag] = useState(null);
  const [formData, setFormData] = useState({ cle: '', valeur: '' });
  const [message, setMessage] = useState({ type: '', text: '' });

  useEffect(() => {
    loadTags();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  const loadTags = async () => {
    try {
      setLoading(true);
      const response = await getTags();
      setTags(response.data);
    } catch (error) {
      showMessage('error', 'Erreur lors du chargement des tags');
    } finally {
      setLoading(false);
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

      resetForm();
      await loadTags();
      if (onUpdate) onUpdate();
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
      await loadTags();
      if (onUpdate) onUpdate();
    } catch (error) {
      showMessage('error', 'Erreur lors de la suppression');
    }
  };

  const resetForm = () => {
    setEditingTag(null);
    setFormData({ cle: '', valeur: '' });
  };

  const handleInputChange = (e) => {
    const { name, value } = e.target;
    setFormData(prev => ({
      ...prev,
      [name]: value
    }));
  };

  return (
    <div className="modal-overlay">
      <div className="modal-content large">
        <div className="modal-header">
          <h2>Gestion des tags</h2>
          <button className="btn-close" onClick={onClose}>×</button>
        </div>

        <div className="modal-body">
          {message.text && (
            <div className={`alert alert-${message.type}`}>
              {message.text}
            </div>
          )}

          <div className="alert alert-info">
            <strong>Information:</strong> Les tags sont automatiquement appliqués aux opérations
            lorsque le libellé contient la valeur du tag. Après modification d'un tag,
            toutes les opérations seront automatiquement ré-analysées.
          </div>

          <div className="form-section">
            <h3>{editingTag ? 'Modifier le tag' : 'Ajouter un nouveau tag'}</h3>
            <form onSubmit={handleSubmit} className="tag-form">
              <div className="form-group">
                <label htmlFor="cle">Nom du tag (clé) *</label>
                <input
                  type="text"
                  id="cle"
                  name="cle"
                  value={formData.cle}
                  onChange={handleInputChange}
                  required
                  placeholder="Ex: supermarche, essence, restaurant"
                />
              </div>

              <div className="form-group">
                <label htmlFor="valeur">Valeur à rechercher *</label>
                <input
                  type="text"
                  id="valeur"
                  name="valeur"
                  value={formData.valeur}
                  onChange={handleInputChange}
                  required
                  placeholder="Ex: CARREFOUR, TOTAL, RESTAURANT"
                />
                <small className="form-text">
                  Cette valeur sera recherchée dans les libellés des opérations
                </small>
              </div>

              <div className="form-actions">
                <button type="submit" className="btn btn-primary">
                  {editingTag ? 'Mettre à jour' : 'Créer'}
                </button>
                {editingTag && (
                  <button type="button" className="btn btn-secondary" onClick={resetForm}>
                    Annuler
                  </button>
                )}
              </div>
            </form>
          </div>

          <div className="form-section">
            <h3>Tags existants</h3>
            {loading ? (
              <p>Chargement...</p>
            ) : tags.length === 0 ? (
              <p className="text-center">Aucun tag défini. Créez votre premier tag ci-dessus.</p>
            ) : (
              <div className="tags-list">
                {tags.map(tag => (
                  <div key={tag.id} className="tag-item">
                    <div className="tag-info">
                      <h4>{tag.cle}</h4>
                      <p className="tag-valeur">Recherche: "{tag.valeur}"</p>
                    </div>
                    <div className="tag-actions">
                      <button
                        className="btn btn-sm btn-secondary"
                        onClick={() => handleEdit(tag)}
                      >
                        Modifier
                      </button>
                      <button
                        className="btn btn-sm btn-danger"
                        onClick={() => handleDelete(tag.id)}
                      >
                        Supprimer
                      </button>
                    </div>
                  </div>
                ))}
              </div>
            )}
          </div>
        </div>
      </div>
    </div>
  );
}

export default TagManager;
