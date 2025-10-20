import React, { useState, useEffect } from 'react';
import { getBalance } from '../services/api';

function BalanceSticky({ compteId, filters = {} }) {
    const [balance, setBalance] = useState({
        total_debits: 0,
        total_credits: 0,
        solde_operations: 0,
        solde_anterieur: 0,
        solde_total: 0,
        nombre_operations: 0
    });

    useEffect(() => {
        const loadBalance = async () => {
            try {
                const params = { compte_id: compteId, ...filters };
                const response = await getBalance(params);
                setBalance(response.data);
            } catch (error) {
                console.error('Erreur lors du chargement de la balance:', error);
            }
        };

        loadBalance();
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [compteId, JSON.stringify(filters)]);

    // Le solde total inclut le solde antérieur + solde des opérations
    const solde = balance.solde_total !== undefined
        ? parseFloat(balance.solde_total || 0)
        : parseFloat(balance.solde_anterieur || 0) + parseFloat(balance.solde_operations || 0);

    return (
        <div className="balance-sticky">
            <div className="balance-sticky-content">
                <div className="balance-sticky-title">
                    <h3>Balance Comptable</h3>
                </div>
                <div className="balance-sticky-items">
                    <div className="balance-sticky-item debit">
                        <span className="label">Débits</span>
                        <span className="value">{parseFloat(balance.total_debits || 0).toFixed(2)} €</span>
                    </div>
                    <div className="balance-sticky-item credit">
                        <span className="label">Crédits</span>
                        <span className="value">{parseFloat(balance.total_credits || 0).toFixed(2)} €</span>
                    </div>
                    <div className={`balance-sticky-item solde ${solde >= 0 ? 'credit' : 'debit'}`}>
                        <span className="label">Solde</span>
                        <span className="value">{solde.toFixed(2)} €</span>
                    </div>
                    <div className="balance-sticky-item operations">
                        <span className="label">Opérations</span>
                        <span className="value">{balance.nombre_operations || 0}</span>
                    </div>
                </div>
            </div>
        </div>
    );
}

export default BalanceSticky;