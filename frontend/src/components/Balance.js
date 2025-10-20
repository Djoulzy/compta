import React, { useState, useEffect } from 'react';
import { getBalance } from '../services/api';

function Balance({ compteId, filters = {} }) {
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
  const soldeTotal = balance.solde_total !== undefined
    ? parseFloat(balance.solde_total || 0)
    : parseFloat(balance.solde_anterieur || 0) + parseFloat(balance.solde_operations || 0);

  return (
    <div className="card">
      <h2>Balance Comptable</h2>
      <div className="balance">
        {balance.solde_anterieur_inclus && (
          <div className="balance-item">
            <h3>Solde antérieur</h3>
            <p>{parseFloat(balance.solde_anterieur || 0).toFixed(2)} €</p>
          </div>
        )}
        <div className="balance-item debit">
          <h3>Total Débits</h3>
          <p>{parseFloat(balance.total_debits || 0).toFixed(2)} €</p>
        </div>
        <div className="balance-item credit">
          <h3>Total Crédits</h3>
          <p>{parseFloat(balance.total_credits || 0).toFixed(2)} €</p>
        </div>
        <div className="balance-item">
          <h3>Solde opérations</h3>
          <p>{parseFloat(balance.solde_operations || 0).toFixed(2)} €</p>
        </div>
        <div className={`balance-item ${soldeTotal >= 0 ? 'credit' : 'debit'}`}>
          <h3>Solde total</h3>
          <p>{soldeTotal.toFixed(2)} €</p>
        </div>
        <div className="balance-item">
          <h3>Opérations</h3>
          <p>{balance.nombre_operations || 0}</p>
        </div>
      </div>
    </div>
  );
}

export default Balance;
