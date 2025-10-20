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
            $query .= " AND (o.libelle ILIKE :recherche OR o.informations_complementaires ILIKE :recherche)";
            $params[':recherche'] = '%' . $filters['recherche'] . '%';
        }

        if (!empty($filters['tag'])) {
            // Gérer les tags multiples (array) ou un seul tag (string)
            $tagFilter = $filters['tag'];

            if (is_array($tagFilter) && count($tagFilter) > 0) {
                // Plusieurs tags sélectionnés - OR logique
                $tagConditions = [];
                foreach ($tagFilter as $index => $tag) {
                    $tagConditions[] = "o.tags @> :tag{$index}::jsonb";
                    $params[":tag{$index}"] = json_encode([['cle' => $tag]]);
                }
                $query .= " AND (" . implode(" OR ", $tagConditions) . ")";
            } elseif (is_string($tagFilter)) {
                // Un seul tag (compatibilité)
                $query .= " AND o.tags @> :tag::jsonb";
                $params[':tag'] = json_encode([['cle' => $tagFilter]]);
            }
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
                case 'informations_complementaires_asc':
                    $orderBy = "o.informations_complementaires ASC NULLS LAST";
                    break;
                case 'informations_complementaires_desc':
                    $orderBy = "o.informations_complementaires DESC NULLS LAST";
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
        // Déterminer si des filtres sont appliqués (autres que compte_id et tri)
        $hasFilters = false;
        $filterKeys = array_keys($filters);
        $excludedKeys = ['compte_id', 'tri']; // Exclure compte_id et tri de la détection de filtres

        foreach ($filterKeys as $key) {
            if (!in_array($key, $excludedKeys)) {
                $value = $filters[$key];

                // Vérifier si la valeur est considérée comme un filtre actif
                if (is_array($value)) {
                    // Pour les tableaux (comme tags), vérifier s'il n'est pas vide
                    if (!empty($value)) {
                        $hasFilters = true;
                        break;
                    }
                } else {
                    // Pour les chaînes, vérifier qu'elle n'est pas vide ou juste des espaces
                    if (!empty($value) && trim($value) !== '') {
                        $hasFilters = true;
                        break;
                    }
                }
            }
        }

        // Récupérer le solde antérieur du compte spécifique
        $solde_anterieur = 0;
        if (!empty($filters['compte_id'])) {
            $query_compte = "SELECT solde_anterieur FROM comptes WHERE id = :compte_id";
            $stmt_compte = $this->db->prepare($query_compte);
            $stmt_compte->bindParam(':compte_id', $filters['compte_id']);
            $stmt_compte->execute();
            $compte = $stmt_compte->fetch();
            $solde_anterieur = $compte ? floatval($compte['solde_anterieur']) : 0;

            // Si des filtres sont appliqués, ne pas inclure le solde antérieur
            if ($hasFilters) {
                $solde_anterieur = 0;
            }
        }

        $query = "SELECT 
                  COALESCE(SUM(CASE WHEN o.debit_credit = 'D' THEN o.montant ELSE 0 END), 0) as total_debits,
                  COALESCE(SUM(CASE WHEN o.debit_credit = 'C' THEN o.montant ELSE 0 END), 0) as total_credits,
                  COALESCE(SUM(o.montant), 0) as solde_operations,
                  COUNT(o.id) as nombre_operations,
                  $solde_anterieur::NUMERIC as solde_anterieur,
                  ($solde_anterieur::NUMERIC + COALESCE(SUM(o.montant), 0)) as solde_total
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
            $query .= " AND (o.libelle ILIKE :recherche OR o.informations_complementaires ILIKE :recherche)";
            $params[':recherche'] = '%' . $filters['recherche'] . '%';
        }

        if (!empty($filters['tag'])) {
            // Gérer les tags multiples (array) ou un seul tag (string)
            $tagFilter = $filters['tag'];

            if (is_array($tagFilter) && count($tagFilter) > 0) {
                // Plusieurs tags sélectionnés - OR logique
                $tagConditions = [];
                foreach ($tagFilter as $index => $tag) {
                    $tagConditions[] = "o.tags @> :tag{$index}::jsonb";
                    $params[":tag{$index}"] = json_encode([['cle' => $tag]]);
                }
                $query .= " AND (" . implode(" OR ", $tagConditions) . ")";
            } elseif (is_string($tagFilter)) {
                // Un seul tag (compatibilité)
                $query .= " AND o.tags @> :tag::jsonb";
                $params[':tag'] = json_encode([['cle' => $tagFilter]]);
            }
        }

        // Le solde_anterieur est déjà intégré directement dans la requête

        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        $result = $stmt->fetch();

        // Ajouter l'information sur l'inclusion du solde antérieur
        if ($result) {
            $result['solde_anterieur_inclus'] = !$hasFilters;
        }

        return $result;
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

    // Créer une nouvelle opération (sans upsert car contrainte d'unicité supprimée)
    public function upsert($data)
    {
        return $this->create($data);
    }

    // Créer une nouvelle opération
    public function create($data)
    {
        $query = "INSERT INTO " . $this->table . " 
                  (fichier, import_id, compte_id, date_operation, date_valeur, libelle, montant, debit_credit, cb, tags, reference, informations_complementaires, type_operation)
                  VALUES (:fichier, :import_id, :compte_id, :date_operation, :date_valeur, :libelle, :montant, :debit_credit, :cb, :tags, :reference, :informations_complementaires, :type_operation)
                  RETURNING id";

        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':fichier', $data['fichier']);
        $stmt->bindParam(':import_id', $data['import_id']);
        $stmt->bindParam(':compte_id', $data['compte_id']);
        $stmt->bindParam(':date_operation', $data['date_operation']);
        $stmt->bindParam(':date_valeur', $data['date_valeur']);
        $stmt->bindParam(':libelle', $data['libelle']);
        $stmt->bindParam(':montant', $data['montant']);
        $stmt->bindParam(':debit_credit', $data['debit_credit']);
        $stmt->bindParam(':cb', $data['cb'], PDO::PARAM_BOOL);
        $stmt->bindParam(':tags', $data['tags']);

        $reference = $data['reference'] ?? null;
        $informations_complementaires = $data['informations_complementaires'] ?? null;
        $type_operation = $data['type_operation'] ?? null;

        $stmt->bindParam(':reference', $reference);
        $stmt->bindParam(':informations_complementaires', $informations_complementaires);
        $stmt->bindParam(':type_operation', $type_operation);
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

    // Mettre à jour les informations complémentaires et le type d'opération
    public function updateInfosComplementaires($id, $informations_complementaires = null, $type_operation = null)
    {
        $query = "UPDATE " . $this->table . " SET 
                  informations_complementaires = :informations_complementaires,
                  type_operation = :type_operation,
                  updated_at = CURRENT_TIMESTAMP
                  WHERE id = :id";

        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':informations_complementaires', $informations_complementaires);
        $stmt->bindParam(':type_operation', $type_operation);
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
            $tags = $tagModel->applyTagsToLibelle($operation['libelle'], $operation['informations_complementaires'] ?? '');
            $this->updateTags($operation['id'], $tags);
        }

        return true;
    }
}
