<?php
require_once '../../config/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Note: Authentication temporarily disabled for admin dashboard access
// In production, uncomment the following line:
// $user = requireRole('Admin');

$method = $_SERVER['REQUEST_METHOD'];

try {
    $pdo = getDB();

    switch ($method) {
        case 'GET':
            // Get all orders or single order
            if (isset($_GET['id'])) {
                $stmt = $pdo->prepare("
                    SELECT c.*, u.Nom as client_name, u.Email as client_email 
                    FROM commande c 
                    LEFT JOIN utilisateur u ON c.ID_Utilisateur = u.ID_Utilisateur 
                    WHERE c.ID_Commande = ?
                ");
                $stmt->execute([sanitize($_GET['id'])]);
                $order = $stmt->fetch();

                if ($order) {
                    // Get order items
                    $stmt = $pdo->prepare("
                        SELECT cp.*, p.Nom as product_name 
                        FROM commande_produit cp 
                        LEFT JOIN produit p ON cp.ID_Produit = p.ID_Produit 
                        WHERE cp.ID_Commande = ?
                    ");
                    $stmt->execute([$order['ID_Commande']]);
                    $items = $stmt->fetchAll();
                    
                    $order['items'] = $items;
                    echo json_encode(['success' => true, 'order' => $order]);
                } else {
                    http_response_code(404);
                    echo json_encode(['error' => 'Commande non trouvée']);
                }
            } else {
                // Get all orders with optional filters
                $query = "
                    SELECT c.*, u.Nom as client_name, u.Email as client_email 
                    FROM commande c 
                    LEFT JOIN utilisateur u ON c.ID_Utilisateur = u.ID_Utilisateur 
                    WHERE 1=1
                ";
                $params = [];

                if (isset($_GET['status']) && !empty($_GET['status'])) {
                    $query .= " AND c.Statut = ?";
                    $params[] = sanitize($_GET['status']);
                }

                if (isset($_GET['limit']) && !empty($_GET['limit'])) {
                    $limit = intval($_GET['limit']);
                } else {
                    $limit = 100;
                }

                $query .= " ORDER BY c.Date_Commande DESC LIMIT " . $limit;

                $stmt = $pdo->prepare($query);
                $stmt->execute($params);
                $orders = $stmt->fetchAll();

                echo json_encode(['success' => true, 'orders' => $orders]);
            }
            break;

        case 'PUT':
            // Update order status
            if (!isset($_GET['id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'ID de la commande requis']);
                exit;
            }

            $data = json_decode(file_get_contents('php://input'), true);

            if (!$data) {
                http_response_code(400);
                echo json_encode(['error' => 'Données invalides']);
                exit;
            }

            if (isset($data['status'])) {
                $stmt = $pdo->prepare("UPDATE commande SET Statut = ? WHERE ID_Commande = ?");
                $stmt->execute([sanitize($data['status']), sanitize($_GET['id'])]);
            }

            echo json_encode(['success' => true, 'message' => 'Statut de la commande mis à jour avec succès']);
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
