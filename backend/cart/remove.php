<?php
require_once '../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$user = requireAuth();

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['product_id']) || !is_numeric($data['product_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Product ID is required']);
    exit;
}

$productId = (int)$data['product_id'];

try {
    $pdo = getDB();
    $stmt = $pdo->prepare("DELETE FROM panier WHERE ID_Utilisateur = ? AND ID_Produit = ?");
    $stmt->execute([$user['user_id'], $productId]);

    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['error' => 'Item not found in cart']);
        exit;
    }

    echo json_encode([
        'success' => true,
        'message' => 'Item removed from cart'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to remove item from cart: ' . $e->getMessage()]);
}
?>
