<?php
require_once __DIR__ . '/../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'PUT' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$user = requireAuth();
$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['product_id']) || !isset($data['quantity'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Product ID and quantity are required']);
    exit;
}

$productId = (int)$data['product_id'];
$quantity = (int)$data['quantity'];

if ($quantity <= 0) {
    // If quantity is 0 or negative, remove item from cart
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("DELETE FROM panier WHERE ID_Utilisateur = ? AND ID_Produit = ?");
        $stmt->execute([$user['user_id'], $productId]);

        echo json_encode([
            'success' => true,
            'message' => 'Item removed from cart'
        ]);
        exit;
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update cart: ' . $e->getMessage()]);
        exit;
    }
}

try {
    $pdo = getDB();
    
    // Check if product has enough stock
    $stmt = $pdo->prepare("SELECT Stock FROM produit WHERE ID_Produit = ?");
    $stmt->execute([$productId]);
    $product = $stmt->fetch();

    if (!$product) {
        http_response_code(404);
        echo json_encode(['error' => 'Product not found']);
        exit;
    }

    if ($product['Stock'] < $quantity) {
        http_response_code(400);
        echo json_encode(['error' => 'Insufficient stock']);
        exit;
    }

    // Update cart item
    $stmt = $pdo->prepare("UPDATE panier SET Quantite = ? WHERE ID_Utilisateur = ? AND ID_Produit = ?");
    $stmt->execute([$quantity, $user['user_id'], $productId]);

    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['error' => 'Item not found in cart']);
        exit;
    }

    echo json_encode([
        'success' => true,
        'message' => 'Cart updated successfully'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to update cart: ' . $e->getMessage()]);
}
?>
