<?php

class Compte
{
    private $db;
    private $table = 'comptes';

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    // Récupérer tous les comptes
    public function getAll()
    {
        $query = "SELECT c.*, 
                  COALESCE(v.nombre_operations, 0) as nombre_operations,
                  COALESCE(v.total_debits, 0) as total_debits,
                  COALESCE(v.total_credits, 0) as total_credits,
                  COALESCE(v.solde_operations, 0) as solde_operations,
                  COALESCE(v.solde_total, c.solde_anterieur) as solde_total
                  FROM " . $this->table . " c
                  LEFT JOIN vue_balance_comptes v ON c.id = v.id
                  ORDER BY c.nom";

        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // Récupérer un compte par ID
    public function getById($id)
    {
        $query = "SELECT c.*, 
                  COALESCE(v.nombre_operations, 0) as nombre_operations,
                  COALESCE(v.total_debits, 0) as total_debits,
                  COALESCE(v.total_credits, 0) as total_credits,
                  COALESCE(v.solde_operations, 0) as solde_operations,
                  COALESCE(v.solde_total, c.solde_anterieur) as solde_total
                  FROM " . $this->table . " c
                  LEFT JOIN vue_balance_comptes v ON c.id = v.id
                  WHERE c.id = :id";

        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch();
    }

    // Récupérer un compte par nom
    public function getByNom($nom)
    {
        $query = "SELECT * FROM " . $this->table . " WHERE nom = :nom";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':nom', $nom);
        $stmt->execute();
        return $stmt->fetch();
    }

    // Créer un nouveau compte
    public function create($nom, $description = '', $label = '', $solde_anterieur = 0)
    {
        $query = "INSERT INTO " . $this->table . " (nom, description, label, solde_anterieur) 
                  VALUES (:nom, :description, :label, :solde_anterieur) 
                  RETURNING id";

        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':nom', $nom);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':label', $label);
        $stmt->bindParam(':solde_anterieur', $solde_anterieur);
        $stmt->execute();

        $result = $stmt->fetch();
        return $result['id'];
    }

    // Mettre à jour un compte
    public function update($id, $nom, $description = '', $label = '', $solde_anterieur = 0)
    {
        $query = "UPDATE " . $this->table . " 
                  SET nom = :nom, description = :description, label = :label, solde_anterieur = :solde_anterieur 
                  WHERE id = :id";

        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':nom', $nom);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':label', $label);
        $stmt->bindParam(':solde_anterieur', $solde_anterieur);
        return $stmt->execute();
    }

    // Supprimer un compte
    public function delete($id)
    {
        $query = "DELETE FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }
}
