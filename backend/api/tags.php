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

$tagModel = new Tag();
$method = $_SERVER['REQUEST_METHOD'];
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = explode('/', $uri);

try {
    switch ($method) {
        case 'GET':
            if (!empty($uri[3])) {
                // GET /api/tags/1
                $tag = $tagModel->getById($uri[3]);
                if ($tag) {
                    echo json_encode($tag);
                } else {
                    http_response_code(404);
                    echo json_encode(['error' => 'Tag non trouvé']);
                }
            } else {
                // GET /api/tags
                $tags = $tagModel->getAll();
                echo json_encode($tags);
            }
            break;

        case 'POST':
            // POST /api/tags - Créer un nouveau tag
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['cle']) || empty($data['valeur'])) {
                http_response_code(400);
                echo json_encode(['error' => 'La clé et la valeur sont requises']);
                break;
            }
            
            $id = $tagModel->create($data['cle'], $data['valeur']);
            $tag = $tagModel->getById($id);
            
            // Réappliquer tous les tags
            $operationModel = new Operation();
            $operationModel->reapplyAllTags();
            
            http_response_code(201);
            echo json_encode($tag);
            break;

        case 'PUT':
            // PUT /api/tags/1 - Mettre à jour un tag
            if (empty($uri[3])) {
                http_response_code(400);
                echo json_encode(['error' => 'ID du tag requis']);
                break;
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['cle']) || empty($data['valeur'])) {
                http_response_code(400);
                echo json_encode(['error' => 'La clé et la valeur sont requises']);
                break;
            }
            
            $success = $tagModel->update($uri[3], $data['cle'], $data['valeur']);
            if ($success) {
                $tag = $tagModel->getById($uri[3]);
                
                // Réappliquer tous les tags
                $operationModel = new Operation();
                $operationModel->reapplyAllTags();
                
                echo json_encode($tag);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Échec de la mise à jour']);
            }
            break;

        case 'DELETE':
            // DELETE /api/tags/1 - Supprimer un tag
            if (empty($uri[3])) {
                http_response_code(400);
                echo json_encode(['error' => 'ID du tag requis']);
                break;
            }
            
            $success = $tagModel->delete($uri[3]);
            if ($success) {
                // Réappliquer tous les tags
                $operationModel = new Operation();
                $operationModel->reapplyAllTags();
                
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
