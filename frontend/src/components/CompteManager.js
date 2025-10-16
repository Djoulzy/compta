import React, { useState, useEffect } from 'react';
import { getComptes, createCompte, updateCompte, deleteCompte } from '../services/api';

function CompteManager({ onClose, onUpdate }) {
    const [comptes, setComptes] = useState([]);
    const [loading, setLoading] = useState(false);
    const [editingCompte, setEditingCompte] = useState(null);
    const [formData, setFormData] = useState({
        nom: '',
        label: '',
        description: '',
        solde_anterieur: 0
    });

    useEffect(() => {
        loadComptes();
    }, []);

    const loadComptes = async () => {
        try {
            setLoading(true);
            const response = await getComptes();
            setComptes(response.data);
        } catch (error) {
            console.error('Erreur lors du chargement des comptes:', error);
        } finally {
            setLoading(false);
        }
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        try {
            if (editingCompte) {
                await updateCompte(editingCompte.id, formData);
            } else {
                await createCompte(formData);
            }
            await loadComptes();
            resetForm();
            if (onUpdate) onUpdate();
        } catch (error) {
            console.error('Erreur lors de la sauvegarde:', error);
        }
    };

    const handleEdit = (compte) => {
        setEditingCompte(compte);
        setFormData({
            nom: compte.nom,
            label: compte.label || '',
            description: compte.description || '',
            solde_anterieur: compte.solde_anterieur || 0
        });
    };

    const handleDelete = async (compteId) => {
        if (window.confirm('Êtes-vous sûr de vouloir supprimer ce compte ?')) {
            try {
                await deleteCompte(compteId);
                await loadComptes();
                if (onUpdate) onUpdate();
            } catch (error) {
                console.error('Erreur lors de la suppression:', error);
            }
        }
    };

    const resetForm = () => {
        setEditingCompte(null);
        setFormData({
            nom: '',
            label: '',
            description: '',
            solde_anterieur: 0
        });
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
                    <h2>Gestion des comptes</h2>
                    <button className="btn-close" onClick={onClose}>×</button>
                </div>

                <div className="modal-body">
                    <div className="form-section">
                        <h3>{editingCompte ? 'Modifier le compte' : 'Ajouter un nouveau compte'}</h3>
                        <form onSubmit={handleSubmit} className="compte-form">
                            <div className="form-group">
                                <label htmlFor="nom">Nom du compte *</label>
                                <input
                                    type="text"
                                    id="nom"
                                    name="nom"
                                    value={formData.nom}
                                    onChange={handleInputChange}
                                    required
                                    placeholder="Ex: 04003501208"
                                />
                            </div>

                            <div className="form-group">
                                <label htmlFor="label">Label d'affichage</label>
                                <input
                                    type="text"
                                    id="label"
                                    name="label"
                                    value={formData.label}
                                    onChange={handleInputChange}
                                    placeholder="Ex: Compte courant principal"
                                />
                            </div>

                            <div className="form-group">
                                <label htmlFor="description">Description</label>
                                <textarea
                                    id="description"
                                    name="description"
                                    value={formData.description}
                                    onChange={handleInputChange}
                                    rows="3"
                                    placeholder="Description optionnelle du compte"
                                />
                            </div>

                            <div className="form-group">
                                <label htmlFor="solde_anterieur">Solde antérieur (€)</label>
                                <input
                                    type="number"
                                    step="0.01"
                                    id="solde_anterieur"
                                    name="solde_anterieur"
                                    value={formData.solde_anterieur}
                                    onChange={handleInputChange}
                                    placeholder="0.00"
                                />
                                <small className="form-text">
                                    Solde de départ du compte avant les opérations importées
                                </small>
                            </div>

                            <div className="form-actions">
                                <button type="submit" className="btn btn-primary">
                                    {editingCompte ? 'Mettre à jour' : 'Créer'}
                                </button>
                                {editingCompte && (
                                    <button type="button" className="btn btn-secondary" onClick={resetForm}>
                                        Annuler
                                    </button>
                                )}
                            </div>
                        </form>
                    </div>

                    <div className="form-section">
                        <h3>Comptes existants</h3>
                        {loading ? (
                            <p>Chargement...</p>
                        ) : (
                            <div className="comptes-list">
                                {comptes.map(compte => (
                                    <div key={compte.id} className="compte-item">
                                        <div className="compte-info">
                                            <h4>{compte.label || compte.nom}</h4>
                                            <p className="compte-nom">Nom: {compte.nom}</p>
                                            {compte.description && (
                                                <p className="compte-description">{compte.description}</p>
                                            )}
                                            <div className="compte-stats">
                                                <span>Opérations: {compte.nombre_operations || 0}</span>
                                                <span>Solde antérieur: {parseFloat(compte.solde_anterieur || 0).toFixed(2)} €</span>
                                                <span>Solde opérations: {parseFloat(compte.solde_operations || 0).toFixed(2)} €</span>
                                                <span>Solde total: {parseFloat(compte.solde_total || 0).toFixed(2)} €</span>
                                            </div>
                                        </div>
                                        <div className="compte-actions">
                                            <button
                                                className="btn btn-sm btn-secondary"
                                                onClick={() => handleEdit(compte)}
                                            >
                                                Modifier
                                            </button>
                                            <button
                                                className="btn btn-sm btn-danger"
                                                onClick={() => handleDelete(compte.id)}
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

export default CompteManager;