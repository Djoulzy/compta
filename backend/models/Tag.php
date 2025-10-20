<?php

class Tag
{
    private $db;
    private $table = 'tags';

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    // Récupérer tous les tags
    public function getAll()
    {
        $query = "SELECT * FROM " . $this->table . " ORDER BY cle";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // Récupérer un tag par ID
    public function getById($id)
    {
        $query = "SELECT * FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch();
    }

    // Créer un nouveau tag
    public function create($cle, $valeur)
    {
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
    public function update($id, $cle, $valeur)
    {
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
    public function delete($id)
    {
        $query = "DELETE FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    // Appliquer les tags automatiquement sur une opération en fonction du libellé et des informations complémentaires
    public function applyTagsToLibelle($libelle, $informations_complementaires = '')
    {
        $tags = $this->getAll();
        $appliedTags = [];

        // Combiner libellé et informations complémentaires pour la recherche
        $searchText = $libelle . ' ' . ($informations_complementaires ?? '');

        foreach ($tags as $tag) {
            $tagMatched = false;

            // Diviser la valeur du tag par les virgules pour traiter chaque token
            $tokens = array_map('trim', explode(',', $tag['valeur']));

            foreach ($tokens as $token) {
                if (empty($token)) continue;

                // Recherche insensible à la casse de chaque token
                if (stripos($searchText, $token) !== false) {
                    $tagMatched = true;
                    break; // Un seul token suffit pour appliquer le tag
                }
            }

            if ($tagMatched) {
                $appliedTags[] = [
                    'cle' => $tag['cle'],
                    'valeur' => $tag['valeur']
                ];
            }
        }

        return $appliedTags;
    }
}
