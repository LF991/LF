<?php
require_once '../../config/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Note: For simplicity, we're not requiring auth for stats
// In production, you should add: $user = requireRole('Admin');

$method = $_SERVER['REQUEST_METHOD'];

try {
    $pdo = getDB();

    if ($method === 'GET') {
        // Get total orders
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM commande");
        $totalOrders = $stmt->fetch()['total'];

        // Get total users
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM utilisateur");
        $totalUsers = $stmt->fetch()['total'];

        // Get total products
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM produit");
        $totalProducts = $stmt->fetch()['total'];

        // Get total revenue (sum of all completed orders)
        $stmt = $pdo->query("SELECT COALESCE(SUM(Prix_Total), 0) as total FROM commande WHERE Statut = 'Livrée'");
        $totalRevenue = $stmt->fetch()['total'];

        // Get pending orders
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM commande WHERE Statut = 'En attente'");
        $pendingOrders = $stmt->fetch()['total'];

        // Get active users (users with orders in last 30 days)
        $stmt = $pdo->query("SELECT COUNT(DISTINCT ID_Utilisateur) as total FROM commande WHERE Date_Commande >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
        $activeUsers = $stmt->fetch()['total'];

        echo json_encode([
            'success' => true,
            'stats' => [
                'total_orders' => intval($totalOrders),
                'total_users' => intval($totalUsers),
                'total_products' => intval($totalProducts),
                'total_revenue' => floatval($totalRevenue),
                'pending_orders' => intval($pendingOrders),
                'active_users' => intval($activeUsers)
            ]
        ]);
    } elseif ($method === 'OPTIONS') {
        http_response_code(200);
    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Méthode non autorisée']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur serveur: ' . $e->getMessage()]);
}
?>
