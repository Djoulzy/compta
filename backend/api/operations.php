<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/Compte.php';
require_once __DIR__ . '/../models/Operation.php';
require_once __DIR__ . '/../models/Tag.php';

$operationModel = new Operation();
$method = $_SERVER['REQUEST_METHOD'];
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = explode('/', $uri);

try {
    switch ($method) {
        case 'GET':
            if (!empty($uri[3]) && $uri[3] === 'balance') {
                // GET /api/operations/balance - Obtenir la balance
                $filters = $_GET;
                $balance = $operationModel->getBalance($filters);
                echo json_encode($balance);
            } elseif (!empty($uri[3])) {
                // GET /api/operations/1
                $operation = $operationModel->getById($uri[3]);
                if ($operation) {
                    echo json_encode($operation);
                } else {
                    http_response_code(404);
                    echo json_encode(['error' => 'Opération non trouvée']);
                }
            } else {
                // GET /api/operations - Récupérer toutes les opérations avec filtres
                $filters = $_GET;
                $operations = $operationModel->getAll($filters);
                echo json_encode($operations);
            }
            break;

        case 'POST':
            // POST /api/operations - Créer une nouvelle opération
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['compte_id']) || empty($data['date_operation']) || 
                empty($data['libelle']) || !isset($data['montant'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Données requises manquantes']);
                break;
            }
            
            // Appliquer les tags automatiquement
            $tagModel = new Tag();
            $tags = $tagModel->applyTagsToLibelle($data['libelle']);
            $data['tags'] = json_encode($tags);
            
            $id = $operationModel->upsert($data);
            $operation = $operationModel->getById($id);
            http_response_code(201);
            echo json_encode($operation);
            break;

        case 'PUT':
            if (!empty($uri[3]) && $uri[3] === 'tags' && !empty($uri[4])) {
                // PUT /api/operations/tags/1 - Mettre à jour les tags d'une opération
                $data = json_decode(file_get_contents('php://input'), true);
                
                if (!isset($data['tags'])) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Tags requis']);
                    break;
                }
                
                $success = $operationModel->updateTags($uri[4], $data['tags']);
                if ($success) {
                    $operation = $operationModel->getById($uri[4]);
                    echo json_encode($operation);
                } else {
                    http_response_code(404);
                    echo json_encode(['error' => 'Échec de la mise à jour']);
                }
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'Endpoint invalide']);
            }
            break;

        case 'DELETE':
            // DELETE /api/operations/1 - Supprimer une opération
            if (empty($uri[3])) {
                http_response_code(400);
                echo json_encode(['error' => 'ID de l\'opération requis']);
                break;
            }
            
            $success = $operationModel->delete($uri[3]);
            if ($success) {
                echo json_encode(['success' => true]);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Échec de la suppression']);
            }
            break;

        default:
            http_response_code(405);
            echo json_encode(['error' => 'Méthode non autorisée']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
