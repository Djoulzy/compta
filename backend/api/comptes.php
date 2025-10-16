<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Gérer les requêtes OPTIONS (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/Compte.php';
require_once __DIR__ . '/../models/Operation.php';
require_once __DIR__ . '/../models/Tag.php';

$compteModel = new Compte();
$method = $_SERVER['REQUEST_METHOD'];
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = explode('/', $uri);

// API endpoint: /api/comptes.php
try {
    switch ($method) {
        case 'GET':
            if (!empty($uri[3])) {
                // GET /api/comptes/1
                $compte = $compteModel->getById($uri[3]);
                if ($compte) {
                    echo json_encode($compte);
                } else {
                    http_response_code(404);
                    echo json_encode(['error' => 'Compte non trouvé']);
                }
            } else {
                // GET /api/comptes
                $comptes = $compteModel->getAll();
                echo json_encode($comptes);
            }
            break;

        case 'POST':
            // POST /api/comptes - Créer un nouveau compte
            $data = json_decode(file_get_contents('php://input'), true);

            if (empty($data['nom'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Le nom du compte est requis']);
                break;
            }

            $solde_anterieur = isset($data['solde_anterieur']) ? floatval($data['solde_anterieur']) : 0;
            $id = $compteModel->create($data['nom'], $data['description'] ?? '', $data['label'] ?? '', $solde_anterieur);
            $compte = $compteModel->getById($id);
            http_response_code(201);
            echo json_encode($compte);
            break;

        case 'PUT':
            // PUT /api/comptes/1 - Mettre à jour un compte
            if (empty($uri[3])) {
                http_response_code(400);
                echo json_encode(['error' => 'ID du compte requis']);
                break;
            }

            $data = json_decode(file_get_contents('php://input'), true);

            if (empty($data['nom'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Le nom du compte est requis']);
                break;
            }

            $success = $compteModel->update($uri[3], $data['nom'], $data['description'] ?? '', $data['label'] ?? '', floatval($data['solde_anterieur'] ?? 0));
            if ($success) {
                $compte = $compteModel->getById($uri[3]);
                echo json_encode($compte);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Échec de la mise à jour']);
            }
            break;

        case 'DELETE':
            // DELETE /api/comptes/1 - Supprimer un compte
            if (empty($uri[3])) {
                http_response_code(400);
                echo json_encode(['error' => 'ID du compte requis']);
                break;
            }

            $success = $compteModel->delete($uri[3]);
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
