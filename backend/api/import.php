<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/Compte.php';
require_once __DIR__ . '/../models/Operation.php';
require_once __DIR__ . '/../models/Tag.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Méthode non autorisée']);
    exit();
}

try {
    // Vérifier si un fichier a été uploadé
    if (!isset($_FILES['file'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Aucun fichier uploadé']);
        exit();
    }

    $file = $_FILES['file'];
    
    // Vérifier les erreurs d'upload
    if ($file['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode(['error' => 'Erreur lors de l\'upload du fichier']);
        exit();
    }

    // Vérifier l'extension du fichier
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if ($extension !== 'csv') {
        http_response_code(400);
        echo json_encode(['error' => 'Le fichier doit être au format CSV']);
        exit();
    }

    // Lire le fichier CSV
    $csvData = array_map('str_getcsv', file($file['tmp_name']));
    
    if (empty($csvData)) {
        http_response_code(400);
        echo json_encode(['error' => 'Le fichier CSV est vide']);
        exit();
    }

    // Vérifier l'en-tête
    $header = array_shift($csvData);
    $expectedHeader = ['Fichier', 'Compte', 'Date opération', 'Date valeur', 'Libellé', 'Montant', 'Débit/Crédit', 'CB'];
    
    // Nettoyer les en-têtes (enlever les BOM et espaces)
    $header = array_map(function($h) {
        return trim(str_replace("\xEF\xBB\xBF", '', $h));
    }, $header);

    $compteModel = new Compte();
    $operationModel = new Operation();
    $tagModel = new Tag();

    $stats = [
        'total' => 0,
        'inserted' => 0,
        'updated' => 0,
        'errors' => [],
        'nouveaux_comptes' => []
    ];

    foreach ($csvData as $lineNumber => $row) {
        if (count($row) < 8) {
            $stats['errors'][] = "Ligne " . ($lineNumber + 2) . ": Nombre de colonnes insuffisant";
            continue;
        }

        $stats['total']++;

        try {
            $fichier = trim($row[0]);
            $nomCompte = trim($row[1]);
            $dateOperation = trim($row[2]);
            $dateValeur = trim($row[3]);
            $libelle = trim($row[4]);
            $montant = trim($row[5]);
            $debitCredit = strtoupper(trim($row[6]));
            $cb = strtolower(trim($row[7])) === 'oui' || strtolower(trim($row[7])) === 'true' || trim($row[7]) === '1';

            // Convertir les dates au format PostgreSQL
            // Accepte YYYY-MM-DD (format ISO) ou DD/MM/YYYY
            $dateOperationObj = DateTime::createFromFormat('Y-m-d', $dateOperation);
            if ($dateOperationObj === false) {
                $dateOperationObj = DateTime::createFromFormat('d/m/Y', $dateOperation);
            }
            if ($dateOperationObj === false) {
                $stats['errors'][] = "Ligne " . ($lineNumber + 2) . ": Format de date opération invalide (attendu: YYYY-MM-DD ou DD/MM/YYYY)";
                continue;
            }
            $dateOperation = $dateOperationObj->format('Y-m-d');

            $dateValeurObj = DateTime::createFromFormat('Y-m-d', $dateValeur);
            if ($dateValeurObj === false) {
                $dateValeurObj = DateTime::createFromFormat('d/m/Y', $dateValeur);
            }
            if ($dateValeurObj !== false) {
                $dateValeur = $dateValeurObj->format('Y-m-d');
            } else {
                $dateValeur = null;
            }

            // Convertir le montant (remplacer la virgule par un point)
            $montant = str_replace(',', '.', $montant);
            $montant = floatval($montant);

            // Normaliser Débit/Crédit
            if (strtolower($debitCredit) === 'débit' || strtolower($debitCredit) === 'debit') {
                $debitCredit = 'D';
            } elseif (strtolower($debitCredit) === 'crédit' || strtolower($debitCredit) === 'credit') {
                $debitCredit = 'C';
            }
            
            if ($debitCredit !== 'D' && $debitCredit !== 'C') {
                $stats['errors'][] = "Ligne " . ($lineNumber + 2) . ": Type de transaction invalide (attendu: D/C ou Débit/Crédit)";
                continue;
            }

            // Vérifier ou créer le compte
            $compte = $compteModel->getByNom($nomCompte);
            if (!$compte) {
                $compteId = $compteModel->create($nomCompte, 'Créé automatiquement lors de l\'import');
                $stats['nouveaux_comptes'][] = $nomCompte;
            } else {
                $compteId = $compte['id'];
            }

            // Appliquer les tags
            $tags = $tagModel->applyTagsToLibelle($libelle);

            // Insérer ou mettre à jour l'opération
            $operationData = [
                'fichier' => $fichier,
                'compte_id' => $compteId,
                'date_operation' => $dateOperation,
                'date_valeur' => $dateValeur,
                'libelle' => $libelle,
                'montant' => $montant,
                'debit_credit' => $debitCredit,
                'cb' => $cb,
                'tags' => json_encode($tags)
            ];

            $operationModel->upsert($operationData);
            $stats['inserted']++;

        } catch (Exception $e) {
            $stats['errors'][] = "Ligne " . ($lineNumber + 2) . ": " . $e->getMessage();
        }
    }

    echo json_encode([
        'success' => true,
        'stats' => $stats
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
