<?php
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
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

try {
    $pdo = getDB();
    
    // Get livreur ID from user
    $stmt = $pdo->prepare("SELECT ID_Livreur, Statut_Disponibilite FROM livreur WHERE ID_Utilisateur = ?");
    $stmt->execute([$user['user_id']]);
    $livreur = $stmt->fetch();
    
    if (!$livreur) {
        // Return default status if no livreur record exists
        echo json_encode([
            'success' => true,
            'availability' => 'Disponible',
            'message' => 'Default availability status'
        ]);
        exit;
    }
    
    echo json_encode([
        'success' => true,
        'availability' => $livreur['Statut_Disponibilite']
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to get availability: ' . $e->getMessage()]);
}
?>

