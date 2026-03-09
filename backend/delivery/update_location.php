<?php
require_once '../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get the current logged-in user
$user = requireAuth();

// Check if user is a livreur
$userRole = isset($user['role']) ? strtolower($user['role']) : '';
if ($userRole !== 'livreur') {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied. Only livreurs can update their location.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['latitude']) || !isset($data['longitude'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Latitude and longitude are required']);
    exit;
}

$latitude = (float)$data['latitude'];
$longitude = (float)$data['longitude'];

// Validate coordinates
if ($latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid coordinates']);
    exit;
}

try {
    $pdo = getDB();
    
    // Get the livreur ID from the logged-in user
    $userId = $user['user_id'];
    
    $stmt = $pdo->prepare("SELECT ID_Livreur FROM livreur WHERE ID_Utilisateur = ?");
    $stmt->execute([$userId]);
    $livreur = $stmt->fetch();
    
    if (!$livreur) {
        http_response_code(404);
        echo json_encode(['error' => 'Livreur not found for this user']);
        exit;
    }
    
    $livreurId = $livreur['ID_Livreur'];
    
    // Create point from coordinates using ST_GeomFromText
    $stmt = $pdo->prepare("
        UPDATE livreur 
        SET Position_GPS_Actuelle = ST_GeomFromText(?, 4326)
        WHERE ID_Livreur = ?
    ");
    $pointWKT = "POINT($longitude $latitude)";
    $stmt->execute([$pointWKT, $livreurId]);
    
    // Also save position to history for tracking
    $stmt = $pdo->prepare("
        INSERT INTO historique_positions (ID_Livreur, Position_GPS, Horodatage)
        VALUES (?, ST_GeomFromText(?, 4326), NOW())
    ");
    $stmt->execute([$livreurId, $pointWKT]);
    
    // Get updated position
    $stmt = $pdo->prepare("
        SELECT 
            ST_X(Position_GPS_Actuelle) as longitude,
            ST_Y(Position_GPS_Actuelle) as latitude
        FROM livreur 
        WHERE ID_Livreur = ?
    ");
    $stmt->execute([$livreurId]);
    $position = $stmt->fetch();
    
    echo json_encode([
        'success' => true,
        'message' => 'Location updated successfully',
        'position' => [
            'latitude' => (float)$position['latitude'],
            'longitude' => (float)$position['longitude']
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to update location: ' . $e->getMessage()]);
}
?>

