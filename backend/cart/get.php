<?php
require_once __DIR__ . '/../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$user = requireAuth();

try {
    $pdo = getDB();
    
    // Get cart items with product details
    $stmt = $pdo->prepare("
        SELECT 
            p.ID_Produit,
            p.Nom,
            p.Description,
            p.Prix,
            p.Stock,
            p.Categorie,
            p.Image_URL,
            p.Statut,
            pan.Quantite,
            pan.Date_Ajout
        FROM panier pan
        JOIN produit p ON pan.ID_Produit = p.ID_Produit
        WHERE pan.ID_Utilisateur = ?
        ORDER BY pan.Date_Ajout DESC
    ");
    $stmt->execute([$user['user_id']]);
    $items = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'items' => $items,
        'count' => count($items)
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to fetch cart: ' . $e->getMessage()]);
}
?>
