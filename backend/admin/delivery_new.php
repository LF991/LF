<?php
// Activer l'affichage des erreurs pour le débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Inclure la configuration de la base de données
require_once '../../config/database.php';

// Headers CORS
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    $pdo = getDB();
    
    $stmt = $pdo->query("
        SELECT 
            l.ID_Livreur,
            u.Nom,
            u.Email,
            u.Telephone,
            l.Statut_Disponibilite AS Statut,
            l.Vehicule,
            l.Note,
            l.Capacite_Max,
            l.Position_GPS_Actuelle
        FROM livreur l
        INNER JOIN utilisateur u ON l.ID_Utilisateur = u.ID_Utilisateur
        ORDER BY l.ID_Livreur DESC
    ");
    
    $deliveries = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'deliveries' => $deliveries
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

