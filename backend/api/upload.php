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

/**
 * Extraire le numéro de compte et le flag CB du nom de fichier
 */
function parseFilenameInfo($filename)
{
    $result = [
        'compte_numero' => null,
        'cb' => false
    ];

    // Si le nom contient "carte", c'est une carte bancaire (insensible à la casse)
    if (stripos($filename, 'carte') !== false) {
        $result['cb'] = true;
        // Pour les cartes, format: carte_6106_04003501208_20082023_20102025.csv
        // Le numéro de compte est après le deuxième underscore (insensible à la casse)
        if (preg_match('/carte_\d+_(\d+)_/i', $filename, $matches)) {
            $result['compte_numero'] = $matches[1];
        }
    } else {
        // Pour les comptes normaux, le numéro est au début du fichier
        if (preg_match('/^(\d+)/', $filename, $matches)) {
            $result['compte_numero'] = $matches[1];
        }
    }

    return $result;
}

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

    // Lire le fichier CSV depuis son nouvel emplacement avec séparateur ";"
    $csvData = [];
    if (($handle = fopen($savedFile['chemin_complet'], 'r')) !== FALSE) {
        while (($row = fgetcsv($handle, 1000, ';')) !== FALSE) {
            $csvData[] = $row;
        }
        fclose($handle);
    }

    if (empty($csvData)) {
        http_response_code(400);
        echo json_encode(['error' => 'Le fichier CSV est vide']);
        exit();
    }

    // Extraire les informations du nom de fichier
    $filenameInfo = parseFilenameInfo($file['name']);

    // Vérifier l'en-tête (nouveau format)
    $header = array_shift($csvData);
    $expectedColumns = [
        'Date de comptabilisation',
        'Libelle simplifie',
        'Libelle operation',
        'Reference',
        'Informations complementaires',
        'Type operation',
        'Categorie',
        'Sous categorie',
        'Debit',
        'Credit',
        'Date operation',
        'Date de valeur',
        'Pointage operation'
    ];

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
        if (count($row) < 13) {
            $stats['errors'][] = "Ligne " . ($lineNumber + 2) . ": Nombre de colonnes insuffisant (attendu: 13, reçu: " . count($row) . ")";
            continue;
        }

        $stats['total']++;

        try {
            // Mapping des colonnes selon le nouveau format
            // Index: 0=Date comptabilisation, 1=Libelle simplifie, 2=Libelle operation, 3=Reference, 
            //        4=Informations complementaires, 5=Type operation, 6=Categorie, 7=Sous categorie,
            //        8=Debit, 9=Credit, 10=Date operation, 11=Date valeur, 12=Pointage operation

            $libelle = trim($row[2]); // Libelle operation
            $reference = trim($row[3]) ?: null;
            $informations_complementaires = trim($row[4]) ?: null;
            $type_operation = trim($row[5]) ?: null;
            $debit = trim($row[8]);
            $credit = trim($row[9]);
            $dateOperation = trim($row[10]);
            $dateValeur = trim($row[11]);

            // Déterminer le montant et le type (Débit/Crédit)
            $montant = 0;
            $debitCredit = '';

            if (!empty($debit) && $debit !== '0' && $debit !== '0,00') {
                $montant = str_replace(',', '.', $debit);
                $montant = floatval($montant);
                $debitCredit = 'D';
            } elseif (!empty($credit) && $credit !== '0' && $credit !== '0,00') {
                $montant = str_replace(',', '.', $credit);
                $montant = floatval($montant);
                $debitCredit = 'C';
            } else {
                $stats['errors'][] = "Ligne " . ($lineNumber + 2) . ": Aucun montant valide trouvé dans Débit ou Crédit";
                continue;
            }

            // Convertir les dates au format PostgreSQL
            $dateOperationObj = DateTime::createFromFormat('d/m/Y', $dateOperation);
            if ($dateOperationObj === false) {
                $dateOperationObj = DateTime::createFromFormat('Y-m-d', $dateOperation);
            }
            if ($dateOperationObj === false) {
                $stats['errors'][] = "Ligne " . ($lineNumber + 2) . ": Format de date opération invalide (attendu: DD/MM/YYYY ou YYYY-MM-DD)";
                continue;
            }
            $dateOperation = $dateOperationObj->format('Y-m-d');

            $dateValeurObj = DateTime::createFromFormat('d/m/Y', $dateValeur);
            if ($dateValeurObj === false) {
                $dateValeurObj = DateTime::createFromFormat('Y-m-d', $dateValeur);
            }
            if ($dateValeurObj !== false) {
                $dateValeur = $dateValeurObj->format('Y-m-d');
            } else {
                $dateValeur = null;
            }

            // Utiliser le numéro de compte extrait du nom de fichier
            $nomCompte = $filenameInfo['compte_numero'];
            if (!$nomCompte) {
                $stats['errors'][] = "Ligne " . ($lineNumber + 2) . ": Impossible d'extraire le numéro de compte du nom de fichier";
                continue;
            }

            // Vérifier ou créer le compte
            $compte = $compteModel->getByNom($nomCompte);
            if (!$compte) {
                $compteId = $compteModel->create($nomCompte, 'Créé automatiquement lors de l\'import - ' . basename($file['name']));
                $stats['nouveaux_comptes'][] = $nomCompte;
            } else {
                $compteId = $compte['id'];
            }

            // Suivre les comptes concernés par cet import
            if (!in_array($nomCompte, $stats['comptes_concernés'])) {
                $stats['comptes_concernés'][] = $nomCompte;
            }

            // Appliquer les tags
            $tags = $tagModel->applyTagsToLibelle($libelle, $informationsComplementaires);

            // Insérer l'opération avec référence à l'import et les nouvelles colonnes
            $operationData = [
                'fichier' => basename($file['name']),
                'import_id' => $importId,
                'compte_id' => $compteId,
                'date_operation' => $dateOperation,
                'date_valeur' => $dateValeur,
                'libelle' => $libelle,
                'montant' => $montant,
                'debit_credit' => $debitCredit,
                'cb' => $filenameInfo['cb'],
                'tags' => json_encode($tags),
                'reference' => $reference,
                'informations_complementaires' => $informations_complementaires,
                'type_operation' => $type_operation
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
