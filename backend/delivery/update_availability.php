<?php
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$user = getCurrentUser();
if (!$user || $user['role'] !== 'Livreur') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['availability'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Availability status is required']);
    exit;
}

$availability = sanitize($data['availability']);

if (!in_array($availability, ['Disponible', 'Indisponible'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid availability status']);
    exit;
}

try {
    $pdo = getDB();
    
    // Get livreur ID from user
    $stmt = $pdo->prepare("SELECT ID_Livreur FROM livreur WHERE ID_Utilisateur = ?");
    $stmt->execute([$user['user_id']]);
    $livreur = $stmt->fetch();
    
    if (!$livreur) {
        // Create livreur record if not exists
        $stmt = $pdo->prepare("INSERT INTO livreur (ID_Utilisateur, Statut_Disponibilite, Vehicule, Capacite_Max, Note) VALUES (?, ?, 'Vélo électrique', 20.00, 5.00)");
        $stmt->execute([$user['user_id'], $availability]);
        $livreurId = $pdo->lastInsertId();
    } else {
        $livreurId = $livreur['ID_Livreur'];
        
        // Update availability status
        $stmt = $pdo->prepare("UPDATE livreur SET Statut_Disponibilite = ? WHERE ID_Livreur = ?");
        $stmt->execute([$availability, $livreurId]);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Availability status updated successfully',
        'availability' => $availability
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to update availability: ' . $e->getMessage()]);
}
?>

