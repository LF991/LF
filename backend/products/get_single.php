<?php
require_once '../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Product ID is required']);
    exit;
}

$productId = (int)$_GET['id'];

try {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT ID_Produit, Nom, Description, Prix, Stock, Categorie, Image_URL, Statut, Date_Ajout, Poids FROM produit WHERE ID_Produit = ? AND Statut = 'Disponible'");
    $stmt->execute([$productId]);
    $product = $stmt->fetch();

    if (!$product) {
        http_response_code(404);
        echo json_encode(['error' => 'Product not found']);
        exit;
    }

    echo json_encode([
        'success' => true,
        'product' => $product
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to fetch product: ' . $e->getMessage()]);
}
?>
