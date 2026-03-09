<?php
require_once '../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get the current logged-in user
$user = requireAuth();

// Debug: Log user info
error_log("User in get_pending: " . print_r($user, true));

// Check if user is a livreur (case-insensitive check)
$userRole = isset($user['role']) ? strtolower($user['role']) : '';
if ($userRole !== 'livreur') {
    http_response_code(403);
    echo json_encode([
        'error' => 'Access denied. Only livreurs can access this resource.',
        'debug' => [
            'user_role' => $user['role'],
            'user_id' => $user['user_id']
        ]
    ]);
    exit;
}

try {
    $pdo = getDB();
    
    // Get the livreur ID from the livreur table using the user_id
    $stmt = $pdo->prepare("SELECT ID_Livreur FROM livreur WHERE ID_Utilisateur = ?");
    $stmt->execute([$user['user_id']]);
    $livreur = $stmt->fetch();
    
    if (!$livreur) {
        echo json_encode([
            'success' => true,
            'orders' => []
        ]);
        exit;
    }

    // Get orders that need delivery - either no delivery record OR no livreur assigned yet
    $stmt = $pdo->prepare("
        SELECT
            c.ID_Commande,
            c.Date_Commande,
            c.Statut,
            c.Adresse_Livraison,
            c.Prix_Total,
            u.Nom as client_name,
            u.Telephone as client_phone
        FROM commande c
        LEFT JOIN utilisateur u ON c.ID_Utilisateur = u.ID_Utilisateur
        LEFT JOIN livraison l ON c.ID_Commande = l.ID_Commande
        WHERE c.Statut IN ('En attente', 'Prête', 'Confirmée', 'En préparation') 
        AND (l.ID_Livraison IS NULL OR l.ID_Livreur IS NULL OR l.Statut_Livraison = 'Annulée')
        ORDER BY c.Date_Commande ASC
    ");
    $stmt->execute();
    
    $orders = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'orders' => $orders
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to fetch pending orders: ' . $e->getMessage()]);
}
?>
