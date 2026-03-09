<?php
require_once '../../config/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Note: Authentication temporarily disabled for admin dashboard access
// In production, uncomment the following line:
// $user = requireRole('Admin');

$method = $_SERVER['REQUEST_METHOD'];

try {
    $pdo = getDB();

    switch ($method) {
        case 'GET':
            // Get all products or single product
            if (isset($_GET['id'])) {
                $stmt = $pdo->prepare("SELECT * FROM produit WHERE ID_Produit = ?");
                $stmt->execute([sanitize($_GET['id'])]);
                $product = $stmt->fetch();

                if ($product) {
                    echo json_encode(['success' => true, 'product' => $product]);
                } else {
                    http_response_code(404);
                    echo json_encode(['error' => 'Produit non trouvé']);
                }
            } else {
                // Get all products with optional filters
                $query = "SELECT * FROM produit WHERE 1=1";
                $params = [];

                if (isset($_GET['category']) && !empty($_GET['category'])) {
                    $query .= " AND Categorie = ?";
                    $params[] = sanitize($_GET['category']);
                }

                if (isset($_GET['status']) && !empty($_GET['status'])) {
                    $query .= " AND Statut = ?";
                    $params[] = sanitize($_GET['status']);
                }

                $query .= " ORDER BY Date_Ajout DESC";

                $stmt = $pdo->prepare($query);
                $stmt->execute($params);
                $products = $stmt->fetchAll();

                echo json_encode(['success' => true, 'products' => $products]);
            }
            break;

        case 'POST':
            // Create new product
            $data = json_decode(file_get_contents('php://input'), true);

            if (!$data) {
                http_response_code(400);
                echo json_encode(['error' => 'Données invalides']);
                exit;
            }

            // Determine status based on stock: stock > 0 = Disponible, stock <= 0 = Indisponible
            $statut = intval($data['stock']) > 0 ? 'Disponible' : 'Indisponible';

            $stmt = $pdo->prepare("INSERT INTO produit (Nom, Description, Prix, Stock, Categorie, Image_URL, Poids, Statut, Date_Ajout)
                                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");

            $stmt->execute([
                sanitize($data['name']),
                sanitize($data['description']),
                floatval($data['price']),
                intval($data['stock']),
                sanitize($data['category']),
                sanitize($data['image_url']),
                floatval($data['weight']),
                $statut
            ]);

            $productId = $pdo->lastInsertId();

            echo json_encode([
                'success' => true,
                'message' => 'Produit créé avec succès',
                'product_id' => $productId
            ]);
            break;

        case 'PUT':
            // Update product
            if (!isset($_GET['id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'ID du produit requis']);
                exit;
            }

            $data = json_decode(file_get_contents('php://input'), true);

            if (!$data) {
                http_response_code(400);
                echo json_encode(['error' => 'Données invalides']);
                exit;
            }

            // Determine status based on stock: stock > 0 = Disponible, stock <= 0 = Indisponible
            $new_stock = intval($data['stock']);
            $statut = $new_stock > 0 ? 'Disponible' : 'Indisponible';

            $stmt = $pdo->prepare("UPDATE produit SET
                                  Nom = ?, Description = ?, Prix = ?, Stock = ?, Categorie = ?, Image_URL = ?, Poids = ?, Statut = ?
                                  WHERE ID_Produit = ?");

            $stmt->execute([
                sanitize($data['name']),
                sanitize($data['description']),
                floatval($data['price']),
                $new_stock,
                sanitize($data['category']),
                sanitize($data['image_url']),
                floatval($data['weight']),
                $statut,
                sanitize($_GET['id'])
            ]);

            echo json_encode(['success' => true, 'message' => 'Produit mis à jour avec succès']);
            break;

        case 'DELETE':
            // Delete product
            if (!isset($_GET['id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'ID du produit requis']);
                exit;
            }

            $stmt = $pdo->prepare("DELETE FROM produit WHERE ID_Produit = ?");
            $stmt->execute([sanitize($_GET['id'])]);

            echo json_encode(['success' => true, 'message' => 'Produit supprimé avec succès']);
            break;

        case 'OPTIONS':
            // Handle preflight requests
            http_response_code(200);
            break;

        default:
            http_response_code(405);
            echo json_encode(['error' => 'Méthode non autorisée']);
            break;
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur serveur: ' . $e->getMessage()]);
}
?>
