<?php

class Import
{
    private $db;
    private $table = 'imports';

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    // Récupérer tous les imports avec statistiques
    public function getAll()
    {
        $query = "SELECT * FROM vue_stats_imports ORDER BY created_at DESC";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // Récupérer un import par ID
    public function getById($id)
    {
        $query = "SELECT * FROM vue_stats_imports WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch();
    }

    // Vérifier si un fichier a déjà été importé (par hash)
    public function checkDuplicate($hash)
    {
        $query = "SELECT * FROM " . $this->table . " WHERE hash_fichier = :hash";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':hash', $hash);
        $stmt->execute();
        return $stmt->fetch();
    }

    // Créer un nouvel import
    public function create($data)
    {
        $query = "INSERT INTO " . $this->table . " 
                  (nom_fichier, nom_fichier_original, taille_fichier, hash_fichier, statut) 
                  VALUES (:nom_fichier, :nom_fichier_original, :taille_fichier, :hash_fichier, :statut) 
                  RETURNING id";

        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':nom_fichier', $data['nom_fichier']);
        $stmt->bindParam(':nom_fichier_original', $data['nom_fichier_original']);
        $stmt->bindParam(':taille_fichier', $data['taille_fichier']);
        $stmt->bindParam(':hash_fichier', $data['hash_fichier']);
        $stmt->bindParam(':statut', $data['statut']);
        $stmt->execute();

        $result = $stmt->fetch();
        return $result['id'];
    }

    // Mettre à jour les statistiques d'un import
    public function updateStats($id, $nombreOperations, $nombreErreurs, $statut = 'termine')
    {
        $query = "UPDATE " . $this->table . " 
                  SET nombre_operations = :nombre_operations, 
                      nombre_erreurs = :nombre_erreurs, 
                      statut = :statut 
                  WHERE id = :id";

        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':nombre_operations', $nombreOperations);
        $stmt->bindParam(':nombre_erreurs', $nombreErreurs);
        $stmt->bindParam(':statut', $statut);
        return $stmt->execute();
    }

    // Marquer un import comme en erreur
    public function markAsError($id, $nombreErreurs = 0)
    {
        return $this->updateStats($id, 0, $nombreErreurs, 'erreur');
    }

    // Supprimer un import (supprime en cascade les opérations liées)
    public function delete($id)
    {
        $query = "DELETE FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    // Générer un hash pour un fichier
    public static function generateFileHash($filePath, $originalName = null)
    {
        $fileContent = file_get_contents($filePath);
        return hash('sha256', $fileContent);
    }

    // Sauvegarder un fichier uploadé dans le répertoire uploads
    public function saveUploadedFile($uploadedFile)
    {
        $uploadDir = __DIR__ . '/../uploads/';

        // Créer le répertoire s'il n'existe pas
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Générer un nom unique
        $timestamp = date('Y-m-d_H-i-s');
        $extension = strtolower(pathinfo($uploadedFile['name'], PATHINFO_EXTENSION));
        $filename = $timestamp . '_' . uniqid() . '.' . $extension;
        $filepath = $uploadDir . $filename;

        // Déplacer le fichier
        if (move_uploaded_file($uploadedFile['tmp_name'], $filepath)) {
            return [
                'nom_fichier' => $filename,
                'chemin_complet' => $filepath,
                'taille_fichier' => filesize($filepath)
            ];
        }

        throw new Exception('Impossible de sauvegarder le fichier uploadé');
    }

    // Récupérer les opérations d'un import
    public function getOperations($importId)
    {
        $query = "SELECT o.*, c.nom as compte_nom 
                  FROM operations o
                  JOIN comptes c ON o.compte_id = c.id
                  WHERE o.import_id = :import_id
                  ORDER BY o.date_operation DESC, o.id DESC";

        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':import_id', $importId);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
