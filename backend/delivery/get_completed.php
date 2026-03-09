<?php
require_once '../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get the current logged-in user
$user = requireAuth();

// Check if user is a livreur
if ($user['role'] !== 'Livreur') {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied. Only livreurs can access this resource.']);
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
    
    $livreurId = $livreur['ID_Livreur'];
    
    // Get completed deliveries for this livreur
    $stmt = $pdo->prepare("
        SELECT
            c.ID_Commande,
            c.Date_Commande,
            c.Statut as order_status,
            c.Adresse_Livraison,
            c.Prix_Total,
            l.ID_Livraison,
            l.Statut_Livraison,
            l.Date_Debut_Livraison,
            l.Date_Fin_Livraison,
            u.Nom as client_name,
            u.Telephone as client_phone
        FROM livraison l
        JOIN commande c ON l.ID_Commande = c.ID_Commande
        LEFT JOIN utilisateur u ON c.ID_Utilisateur = u.ID_Utilisateur
        WHERE l.ID_Livreur = ? 
        AND l.Statut_Livraison = 'Livrée'
        ORDER BY l.Date_Fin_Livraison DESC
    ");
    $stmt->execute([$livreurId]);
    
    $orders = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'orders' => $orders
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to fetch completed deliveries: ' . $e->getMessage()]);
}
?>
