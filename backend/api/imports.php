<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/Import.php';

$importModel = new Import();
$method = $_SERVER['REQUEST_METHOD'];
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = explode('/', $uri);

// API endpoint: /api/imports.php
// Debug temporaire
file_put_contents('/tmp/debug_imports.log', 'URI: ' . $_SERVER['REQUEST_URI'] . "\n" . 'Parsed URI: ' . print_r($uri, true) . "\nMethod: " . $method . "\n", FILE_APPEND);

try {
    switch ($method) {
        case 'GET':
            if (!empty($uri[3]) && $uri[3] !== 'imports') {
                if ($uri[3] === 'operations' && !empty($uri[4])) {
                    // GET /api/imports/operations/1 - Récupérer les opérations d'un import
                    $operations = $importModel->getOperations($uri[4]);
                    echo json_encode($operations);
                } else {
                    // GET /api/imports/1 - Récupérer un import spécifique
                    $import = $importModel->getById($uri[3]);
                    if ($import) {
                        echo json_encode($import);
                    } else {
                        http_response_code(404);
                        echo json_encode(['error' => 'Import non trouvé']);
                    }
                }
            } else {
                // GET /api/imports - Récupérer tous les imports
                $imports = $importModel->getAll();
                echo json_encode($imports);
            }
            break;

        case 'DELETE':
            // DELETE /api/imports/1 - Supprimer un import et ses opérations
            if (empty($uri[3])) {
                http_response_code(400);
                echo json_encode(['error' => 'ID de l\'import requis']);
                break;
            }

            $import = $importModel->getById($uri[3]);
            if (!$import) {
                http_response_code(404);
                echo json_encode(['error' => 'Import non trouvé']);
                break;
            }

            $success = $importModel->delete($uri[3]);
            if ($success) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Import et opérations associées supprimés avec succès',
                    'operations_supprimees' => $import['operations_actuelles']
                ]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Erreur lors de la suppression']);
            }
            break;

        default:
            http_response_code(405);
            echo json_encode([
                'error' => 'Méthode non autorisée',
                'debug' => [
                    'method' => $method,
                    'uri' => $_SERVER['REQUEST_URI'],
                    'request_method' => $_SERVER['REQUEST_METHOD']
                ]
            ]);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
