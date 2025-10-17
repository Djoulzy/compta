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
require_once __DIR__ . '/../models/Import.php';

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

    // Vérifier les doublons d'import
    $importModel = new Import();
    $fileHash = Import::generateFileHash($file['tmp_name'], $file['name']);

    $existingImport = $importModel->checkDuplicate($fileHash);
    if ($existingImport) {
        http_response_code(409);
        echo json_encode([
            'error' => 'Ce fichier a déjà été importé',
            'import_existant' => [
                'id' => $existingImport['id'],
                'nom_fichier' => $existingImport['nom_fichier_original'],
                'date_import' => $existingImport['created_at'],
                'nombre_operations' => $existingImport['nombre_operations']
            ]
        ]);
        exit();
    }

    // Sauvegarder le fichier uploadé
    $savedFile = $importModel->saveUploadedFile($file);

    // Lire le fichier CSV depuis son nouvel emplacement
    $csvData = array_map('str_getcsv', file($savedFile['chemin_complet']));

    if (empty($csvData)) {
        http_response_code(400);
        echo json_encode(['error' => 'Le fichier CSV est vide']);
        exit();
    }

    // Vérifier l'en-tête
    $header = array_shift($csvData);
    $expectedHeader = ['Fichier', 'Compte', 'Date opération', 'Date valeur', 'Libellé', 'Montant', 'Débit/Crédit', 'CB'];

    // Nettoyer les en-têtes (enlever les BOM et espaces)
    $header = array_map(function ($h) {
        return trim(str_replace("\xEF\xBB\xBF", '', $h));
    }, $header);

    $compteModel = new Compte();
    $operationModel = new Operation();
    $tagModel = new Tag();

    // Créer l'enregistrement d'import
    $importData = [
        'nom_fichier' => $savedFile['nom_fichier'],
        'nom_fichier_original' => $file['name'],
        'taille_fichier' => $savedFile['taille_fichier'],
        'hash_fichier' => $fileHash,
        'statut' => 'en_cours'
    ];

    $importId = $importModel->create($importData);

    $stats = [
        'import_id' => $importId,
        'total' => 0,
        'inserted' => 0,
        'updated' => 0,
        'errors' => [],
        'nouveaux_comptes' => [],
        'comptes_concernés' => []
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

            // Suivre les comptes concernés par cet import
            if (!in_array($nomCompte, $stats['comptes_concernés'])) {
                $stats['comptes_concernés'][] = $nomCompte;
            }

            // Appliquer les tags
            $tags = $tagModel->applyTagsToLibelle($libelle);

            // Insérer l'opération avec référence à l'import
            $operationData = [
                'fichier' => $fichier,
                'import_id' => $importId,
                'compte_id' => $compteId,
                'date_operation' => $dateOperation,
                'date_valeur' => $dateValeur,
                'libelle' => $libelle,
                'montant' => $montant,
                'debit_credit' => $debitCredit,
                'cb' => $cb,
                'tags' => json_encode($tags)
            ];

            $operationModel->create($operationData);
            $stats['inserted']++;
        } catch (Exception $e) {
            $stats['errors'][] = "Ligne " . ($lineNumber + 2) . ": " . $e->getMessage();
        }
    }

    // Mettre à jour les statistiques de l'import
    $statut = count($stats['errors']) > 0 ? 'termine' : 'termine';
    if ($stats['inserted'] === 0 && count($stats['errors']) > 0) {
        $statut = 'erreur';
    }

    $importModel->updateStats($importId, $stats['inserted'], count($stats['errors']), $statut);

    echo json_encode([
        'success' => true,
        'stats' => $stats
    ]);
} catch (Exception $e) {
    // En cas d'erreur, marquer l'import comme en erreur s'il existe
    if (isset($importId)) {
        try {
            $importModel->markAsError($importId, 1);
        } catch (Exception $e2) {
            // Ignore les erreurs de mise à jour du statut
        }
    }

    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ]);
} catch (Error $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Erreur fatale: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
