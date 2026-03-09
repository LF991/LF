<?php
require_once '../../config/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

$method = $_SERVER['REQUEST_METHOD'];

try {
    $pdo = getDB();

    switch ($method) {
        case 'GET':
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
            echo json_encode(['success' => true, 'deliveries' => $deliveries]);
            break;

        case 'OPTIONS':
            http_response_code(200);
            break;

        default:
            http_response_code(405);
            echo json_encode(['error' => 'Méthode non autorisée']);
            break;
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur serveur: ' . $e->getMessage()]);
}
?>

