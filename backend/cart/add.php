<?php
require_once __DIR__ . '/../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$user = requireAuth();
$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['product_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Product ID is required']);
    exit;
}

$productId = (int)$data['product_id'];
$quantity = isset($data['quantity']) ? (int)$data['quantity'] : 1;

if ($quantity <= 0) {
    $quantity = 1;
}

try {
    $pdo = getDB();
    
    // Check if product exists and is available
    $stmt = $pdo->prepare("SELECT ID_Produit, Stock, Statut FROM produit WHERE ID_Produit = ?");
    $stmt->execute([$productId]);
    $product = $stmt->fetch();

    if (!$product) {
        http_response_code(404);
        echo json_encode(['error' => 'Product not found']);
        exit;
    }

    if ($product['Statut'] !== 'Disponible') {
        http_response_code(400);
        echo json_encode(['error' => 'Product is not available']);
        exit;
    }

    if ($product['Stock'] < $quantity) {
        http_response_code(400);
        echo json_encode(['error' => 'Insufficient stock']);
        exit;
    }

    // Check if item already exists in cart
    $stmt = $pdo->prepare("SELECT ID_Panier, Quantite FROM panier WHERE ID_Utilisateur = ? AND ID_Produit = ?");
    $stmt->execute([$user['user_id'], $productId]);
    $existingItem = $stmt->fetch();

    if ($existingItem) {
        // Update quantity
        $newQuantity = $existingItem['Quantite'] + $quantity;
        
        if ($product['Stock'] < $newQuantity) {
            http_response_code(400);
            echo json_encode(['error' => 'Insufficient stock for this quantity']);
            exit;
        }

        $stmt = $pdo->prepare("UPDATE panier SET Quantite = ? WHERE ID_Panier = ?");
        $stmt->execute([$newQuantity, $existingItem['ID_Panier']]);
    } else {
        // Add new item to cart
        $stmt = $pdo->prepare("INSERT INTO panier (ID_Utilisateur, ID_Produit, Quantite) VALUES (?, ?, ?)");
        $stmt->execute([$user['user_id'], $productId, $quantity]);
    }

    // Get updated cart count
    $stmt = $pdo->prepare("SELECT SUM(Quantite) as total FROM panier WHERE ID_Utilisateur = ?");
    $stmt->execute([$user['user_id']]);
    $cartCount = $stmt->fetch();

    echo json_encode([
        'success' => true,
        'message' => 'Product added to cart',
        'cart_count' => $cartCount['total'] ?? 0
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to add to cart: ' . $e->getMessage()]);
}
?>
