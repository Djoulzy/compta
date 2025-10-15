<?php

class Compte {
    private $db;
    private $table = 'comptes';

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    // Récupérer tous les comptes
    public function getAll() {
        $query = "SELECT c.*, 
                  COALESCE(v.nombre_operations, 0) as nombre_operations,
                  COALESCE(v.total_debits, 0) as total_debits,
                  COALESCE(v.total_credits, 0) as total_credits,
                  COALESCE(v.solde, 0) as solde
                  FROM " . $this->table . " c
                  LEFT JOIN vue_balance_comptes v ON c.id = v.id
                  ORDER BY c.nom";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // Récupérer un compte par ID
    public function getById($id) {
        $query = "SELECT c.*, 
                  COALESCE(v.nombre_operations, 0) as nombre_operations,
                  COALESCE(v.total_debits, 0) as total_debits,
                  COALESCE(v.total_credits, 0) as total_credits,
                  COALESCE(v.solde, 0) as solde
                  FROM " . $this->table . " c
                  LEFT JOIN vue_balance_comptes v ON c.id = v.id
                  WHERE c.id = :id";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch();
    }

    // Récupérer un compte par nom
    public function getByNom($nom) {
        $query = "SELECT * FROM " . $this->table . " WHERE nom = :nom";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':nom', $nom);
        $stmt->execute();
        return $stmt->fetch();
    }

    // Créer un nouveau compte
    public function create($nom, $description = '') {
        $query = "INSERT INTO " . $this->table . " (nom, description) 
                  VALUES (:nom, :description) 
                  RETURNING id";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':nom', $nom);
        $stmt->bindParam(':description', $description);
        $stmt->execute();
        
        $result = $stmt->fetch();
        return $result['id'];
    }

    // Mettre à jour un compte
    public function update($id, $nom, $description = '') {
        $query = "UPDATE " . $this->table . " 
                  SET nom = :nom, description = :description 
                  WHERE id = :id";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':nom', $nom);
        $stmt->bindParam(':description', $description);
        return $stmt->execute();
    }

    // Supprimer un compte
    public function delete($id) {
        $query = "DELETE FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }
}
