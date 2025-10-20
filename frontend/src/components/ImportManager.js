import React, { useState, useEffect } from 'react';
import '../App.css';

const ImportManager = () => {
    const [imports, setImports] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState('');
    const [selectedImport, setSelectedImport] = useState(null);
    const [showOperations, setShowOperations] = useState(false);

    useEffect(() => {
        loadImports();
    }, []);

    const loadImports = async () => {
        try {
            setLoading(true);
            const response = await fetch('/api/imports');
            if (!response.ok) {
                throw new Error('Erreur lors du chargement des imports');
            }
            const data = await response.json();
            setImports(data);
        } catch (err) {
            setError(err.message);
        } finally {
            setLoading(false);
        }
    };

    const deleteImport = async (importId) => {
        if (!window.confirm('Êtes-vous sûr de vouloir supprimer cet import et toutes ses opérations ?')) {
            return;
        }

        try {
            const response = await fetch(`/api/imports/${importId}`, {
                method: 'DELETE'
            });

            if (!response.ok) {
                throw new Error('Erreur lors de la suppression');
            }

            // Recharger la liste des imports
            loadImports();
            setSelectedImport(null);
            setShowOperations(false);
        } catch (err) {
            setError(err.message);
        }
    };

    const viewImportOperations = async (importItem) => {
        try {
            const response = await fetch(`/api/imports/${importItem.id}`);
            if (!response.ok) {
                throw new Error('Erreur lors du chargement des opérations');
            }
            const data = await response.json();
            setSelectedImport(data);
            setShowOperations(true);
        } catch (err) {
            setError(err.message);
        }
    };

    const formatDate = (dateString) => {
        return new Date(dateString).toLocaleDateString('fr-FR');
    };

    const formatAmount = (amount) => {
        return new Intl.NumberFormat('fr-FR', {
            style: 'currency',
            currency: 'EUR'
        }).format(amount);
    };

    if (loading) {
        return <div className="loading">Chargement des imports...</div>;
    }

    if (error) {
        return <div className="error">Erreur : {error}</div>;
    }

    return (
        <div className="import-manager">
            <h2>Gestion des Imports</h2>

            {!showOperations ? (
                <div className="imports-list">
                    <h3>Historique des imports ({imports.length})</h3>

                    {imports.length === 0 ? (
                        <p>Aucun import trouvé.</p>
                    ) : (
                        <div className="table-container">
                            <table className="imports-table">
                                <thead>
                                    <tr>
                                        <th>Date d'import</th>
                                        <th>Nom du fichier</th>
                                        <th>Hash du fichier</th>
                                        <th>Opérations</th>
                                        <th>Comptes concernés</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {imports.map(importItem => (
                                        <tr key={importItem.id}>
                                            <td>{formatDate(importItem.created_at)}</td>
                                            <td>
                                                <span className="filename" title={importItem.filename}>
                                                    {importItem.filename}
                                                </span>
                                            </td>
                                            <td>
                                                <span className="hash" title={importItem.file_hash}>
                                                    {importItem.file_hash.substring(0, 8)}...
                                                </span>
                                            </td>
                                            <td className="text-center">
                                                <span className="operations-count">
                                                    {importItem.total_operations}
                                                </span>
                                            </td>
                                            <td className="text-center">
                                                <span className="accounts-count">
                                                    {importItem.total_comptes}
                                                </span>
                                            </td>
                                            <td className="actions">
                                                <button
                                                    onClick={() => viewImportOperations(importItem)}
                                                    className="btn btn-view"
                                                    title="Voir les opérations"
                                                >
                                                    👁️ Voir
                                                </button>
                                                <button
                                                    onClick={() => deleteImport(importItem.id)}
                                                    className="btn btn-delete"
                                                    title="Supprimer l'import"
                                                >
                                                    🗑️ Supprimer
                                                </button>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </div>
            ) : (
                <div className="import-details">
                    <button
                        onClick={() => setShowOperations(false)}
                        className="btn btn-back"
                    >
                        ← Retour à la liste
                    </button>

                    <h3>Détails de l'import</h3>
                    <div className="import-info">
                        <p><strong>Fichier :</strong> {selectedImport.filename}</p>
                        <p><strong>Date :</strong> {formatDate(selectedImport.created_at)}</p>
                        <p><strong>Hash :</strong> {selectedImport.file_hash}</p>
                        <p><strong>Nombre d'opérations :</strong> {selectedImport.operations.length}</p>
                    </div>

                    <h4>Opérations importées</h4>
                    <div className="table-container">
                        <table className="operations-table">
                            <thead>
                                <tr>
                                    <th>Date Opération</th>
                                    <th>Date Valeur</th>
                                    <th>Libellé</th>
                                    <th>Montant</th>
                                    <th>Type</th>
                                    <th>CB</th>
                                    <th>Compte</th>
                                    <th>Référence</th>
                                    <th>Type Opération</th>
                                    <th>Infos Complémentaires</th>
                                </tr>
                            </thead>
                            <tbody>
                                {selectedImport.operations.map(operation => (
                                    <tr key={operation.id}>
                                        <td>{formatDate(operation.date_operation)}</td>
                                        <td>{formatDate(operation.date_valeur)}</td>
                                        <td>{operation.libelle}</td>
                                        <td className={operation.debit_credit === 'D' ? 'debit' : 'credit'}>
                                            {formatAmount(operation.montant)}
                                        </td>
                                        <td>
                                            <span className={`type ${operation.debit_credit.toLowerCase()}`}>
                                                {operation.debit_credit === 'D' ? 'Débit' : 'Crédit'}
                                            </span>
                                        </td>
                                        <td className="text-center">
                                            {operation.cb ? '💳' : ''}
                                        </td>
                                        <td>{operation.compte_numero}</td>
                                        <td>{operation.reference || '-'}</td>
                                        <td>
                                            {operation.type_operation ? (
                                                <span className="type-operation">
                                                    {operation.type_operation}
                                                </span>
                                            ) : '-'}
                                        </td>
                                        <td>
                                            {operation.informations_complementaires ? (
                                                <span className="infos-complementaires" title={operation.informations_complementaires}>
                                                    {operation.informations_complementaires.length > 30
                                                        ? operation.informations_complementaires.substring(0, 30) + '...'
                                                        : operation.informations_complementaires}
                                                </span>
                                            ) : '-'}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>

                    <div className="import-actions">
                        <button
                            onClick={() => deleteImport(selectedImport.id)}
                            className="btn btn-delete-large"
                        >
                            🗑️ Supprimer cet import et toutes ses opérations
                        </button>
                    </div>
                </div>
            )}
        </div>
    );
};

export default ImportManager;