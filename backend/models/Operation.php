<?php

class Operation
{
    private $db;
    private $table = 'operations';

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    // Récupérer les opérations avec filtres
    public function getAll($filters = [])
    {
        $query = "SELECT o.*, c.nom as compte_nom 
                  FROM " . $this->table . " o
                  JOIN comptes c ON o.compte_id = c.id
                  WHERE 1=1";

        $params = [];

        if (!empty($filters['compte_id'])) {
            $query .= " AND o.compte_id = :compte_id";
            $params[':compte_id'] = $filters['compte_id'];
        }

        if (!empty($filters['debit_credit'])) {
            $query .= " AND o.debit_credit = :debit_credit";
            $params[':debit_credit'] = $filters['debit_credit'];
        }

        if (isset($filters['cb']) && $filters['cb'] !== '') {
            $query .= " AND o.cb = :cb";
            $params[':cb'] = $filters['cb'] === 'true' || $filters['cb'] === true ? 't' : 'f';
        }

        if (!empty($filters['date_debut'])) {
            $query .= " AND o.date_operation >= :date_debut";
            $params[':date_debut'] = $filters['date_debut'];
        }

        if (!empty($filters['date_fin'])) {
            $query .= " AND o.date_operation <= :date_fin";
            $params[':date_fin'] = $filters['date_fin'];
        }

        if (!empty($filters['mois'])) {
            $query .= " AND EXTRACT(MONTH FROM o.date_operation) = :mois";
            $params[':mois'] = $filters['mois'];
        }

        if (!empty($filters['annee'])) {
            $query .= " AND EXTRACT(YEAR FROM o.date_operation) = :annee";
            $params[':annee'] = $filters['annee'];
        }

        if (!empty($filters['recherche'])) {
            $query .= " AND o.libelle ILIKE :recherche";
            $params[':recherche'] = '%' . $filters['recherche'] . '%';
        }

        if (!empty($filters['tag'])) {
            $query .= " AND o.tags @> :tag::jsonb";
            $params[':tag'] = json_encode([['cle' => $filters['tag']]]);
        }

        // Tri
        $orderBy = "o.date_operation DESC";
        if (!empty($filters['tri'])) {
            switch ($filters['tri']) {
                case 'date_operation_asc':
                    $orderBy = "o.date_operation ASC";
                    break;
                case 'date_operation_desc':
                    $orderBy = "o.date_operation DESC";
                    break;
                case 'date_valeur_asc':
                    $orderBy = "o.date_valeur ASC";
                    break;
                case 'date_valeur_desc':
                    $orderBy = "o.date_valeur DESC";
                    break;
            }
        }
        $query .= " ORDER BY " . $orderBy;

        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    // Calculer la balance pour un ensemble d'opérations
    public function getBalance($filters = [])
    {
        $query = "SELECT 
                  SUM(CASE WHEN o.debit_credit = 'D' THEN o.montant ELSE 0 END) as total_debits,
                  SUM(CASE WHEN o.debit_credit = 'C' THEN o.montant ELSE 0 END) as total_credits,
                  SUM(o.montant) as solde,
                  COUNT(*) as nombre_operations
                  FROM " . $this->table . " o
                  WHERE 1=1";

        $params = [];

        if (!empty($filters['compte_id'])) {
            $query .= " AND o.compte_id = :compte_id";
            $params[':compte_id'] = $filters['compte_id'];
        }

        if (!empty($filters['debit_credit'])) {
            $query .= " AND o.debit_credit = :debit_credit";
            $params[':debit_credit'] = $filters['debit_credit'];
        }

        if (isset($filters['cb']) && $filters['cb'] !== '') {
            $query .= " AND o.cb = :cb";
            $params[':cb'] = $filters['cb'] === 'true' || $filters['cb'] === true ? 't' : 'f';
        }

        if (!empty($filters['date_debut'])) {
            $query .= " AND o.date_operation >= :date_debut";
            $params[':date_debut'] = $filters['date_debut'];
        }

        if (!empty($filters['date_fin'])) {
            $query .= " AND o.date_operation <= :date_fin";
            $params[':date_fin'] = $filters['date_fin'];
        }

        if (!empty($filters['mois'])) {
            $query .= " AND EXTRACT(MONTH FROM o.date_operation) = :mois";
            $params[':mois'] = $filters['mois'];
        }

        if (!empty($filters['annee'])) {
            $query .= " AND EXTRACT(YEAR FROM o.date_operation) = :annee";
            $params[':annee'] = $filters['annee'];
        }

        if (!empty($filters['recherche'])) {
            $query .= " AND o.libelle ILIKE :recherche";
            $params[':recherche'] = '%' . $filters['recherche'] . '%';
        }

        if (!empty($filters['tag'])) {
            $query .= " AND o.tags @> :tag::jsonb";
            $params[':tag'] = json_encode([['cle' => $filters['tag']]]);
        }

        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        return $stmt->fetch();
    }

    // Récupérer une opération par ID
    public function getById($id)
    {
        $query = "SELECT * FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch();
    }

    // Créer ou mettre à jour une opération
    public function upsert($data)
    {
        $query = "INSERT INTO " . $this->table . " 
                  (fichier, compte_id, date_operation, date_valeur, libelle, montant, debit_credit, cb, tags)
                  VALUES (:fichier, :compte_id, :date_operation, :date_valeur, :libelle, :montant, :debit_credit, :cb, :tags)
                  ON CONFLICT (compte_id, date_operation, libelle)
                  DO UPDATE SET
                      fichier = EXCLUDED.fichier,
                      date_valeur = EXCLUDED.date_valeur,
                      montant = EXCLUDED.montant,
                      debit_credit = EXCLUDED.debit_credit,
                      cb = EXCLUDED.cb,
                      tags = EXCLUDED.tags
                  RETURNING id";

        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':fichier', $data['fichier']);
        $stmt->bindParam(':compte_id', $data['compte_id']);
        $stmt->bindParam(':date_operation', $data['date_operation']);
        $stmt->bindParam(':date_valeur', $data['date_valeur']);
        $stmt->bindParam(':libelle', $data['libelle']);
        $stmt->bindParam(':montant', $data['montant']);
        $stmt->bindParam(':debit_credit', $data['debit_credit']);
        $stmt->bindParam(':cb', $data['cb'], PDO::PARAM_BOOL);
        $stmt->bindParam(':tags', $data['tags']);
        $stmt->execute();

        $result = $stmt->fetch();
        return $result['id'];
    }

    // Mettre à jour les tags d'une opération
    public function updateTags($id, $tags)
    {
        $query = "UPDATE " . $this->table . " SET tags = :tags WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':tags', json_encode($tags));
        return $stmt->execute();
    }

    // Supprimer une opération
    public function delete($id)
    {
        $query = "DELETE FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    // Réappliquer tous les tags sur toutes les opérations
    public function reapplyAllTags()
    {
        $tagModel = new Tag();
        $operations = $this->getAll();

        foreach ($operations as $operation) {
            $tags = $tagModel->applyTagsToLibelle($operation['libelle']);
            $this->updateTags($operation['id'], $tags);
        }

        return true;
    }
}
