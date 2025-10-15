<?php

class Tag {
    private $db;
    private $table = 'tags';

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    // Récupérer tous les tags
    public function getAll() {
        $query = "SELECT * FROM " . $this->table . " ORDER BY cle";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // Récupérer un tag par ID
    public function getById($id) {
        $query = "SELECT * FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch();
    }

    // Créer un nouveau tag
    public function create($cle, $valeur) {
        $query = "INSERT INTO " . $this->table . " (cle, valeur) 
                  VALUES (:cle, :valeur) 
                  RETURNING id";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':cle', $cle);
        $stmt->bindParam(':valeur', $valeur);
        $stmt->execute();
        
        $result = $stmt->fetch();
        return $result['id'];
    }

    // Mettre à jour un tag
    public function update($id, $cle, $valeur) {
        $query = "UPDATE " . $this->table . " 
                  SET cle = :cle, valeur = :valeur 
                  WHERE id = :id";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':cle', $cle);
        $stmt->bindParam(':valeur', $valeur);
        return $stmt->execute();
    }

    // Supprimer un tag
    public function delete($id) {
        $query = "DELETE FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    // Appliquer les tags automatiquement sur une opération en fonction du libellé
    public function applyTagsToLibelle($libelle) {
        $tags = $this->getAll();
        $appliedTags = [];

        foreach ($tags as $tag) {
            // Recherche insensible à la casse
            if (stripos($libelle, $tag['valeur']) !== false) {
                $appliedTags[] = [
                    'cle' => $tag['cle'],
                    'valeur' => $tag['valeur']
                ];
            }
        }

        return $appliedTags;
    }
}
