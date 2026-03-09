<?php
require_once '../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$user = requireAuth();

try {
    $pdo = getDB();
    
    // Debug: Log the user ID
    error_log("User ID requesting orders: " . $user['user_id']);
    
    // Check if status filter is provided
    $status = isset($_GET['status']) ? $_GET['status'] : null;
    
    // Filter orders based on user role - clients only see their own orders
    $userRole = $user['role'];
    $userId = $user['user_id'];
    
    // For 'Client' role, only show their own orders
    // For 'admin' and 'Livreur', show all orders
    if ($userRole === 'Client') {
        if ($status) {
            $stmt = $pdo->prepare("
                SELECT
                    c.ID_Commande,
                    c.ID_Utilisateur,
                    c.Date_Commande,
                    c.Statut,
                    c.Adresse_Livraison,
                    c.Prix_Total,
                    c.Notes,
                    l.Statut_Livraison,
                    l.Date_Debut_Livraison,
                    l.Date_Fin_Livraison,
                    l.Distance_KM,
                    u.Nom as Livreur_Nom,
                    ucli.Nom as Client_Nom
                FROM commande c
                LEFT JOIN livraison l ON c.ID_Commande = l.ID_Commande
                LEFT JOIN livreur lv ON l.ID_Livreur = lv.ID_Livreur
                LEFT JOIN utilisateur u ON lv.ID_Utilisateur = u.ID_Utilisateur
                LEFT JOIN utilisateur ucli ON c.ID_Utilisateur = ucli.ID_Utilisateur
                WHERE c.ID_Utilisateur = ? AND c.Statut = ?
                ORDER BY c.Date_Commande DESC
            ");
            $stmt->execute([$userId, $status]);
        } else {
            $stmt = $pdo->prepare("
                SELECT
                    c.ID_Commande,
                    c.ID_Utilisateur,
                    c.Date_Commande,
                    c.Statut,
                    c.Adresse_Livraison,
                    c.Prix_Total,
                    c.Notes,
                    l.Statut_Livraison,
                    l.Date_Debut_Livraison,
                    l.Date_Fin_Livraison,
                    l.Distance_KM,
                    u.Nom as Livreur_Nom,
                    ucli.Nom as Client_Nom
                FROM commande c
                LEFT JOIN livraison l ON c.ID_Commande = l.ID_Commande
                LEFT JOIN livreur lv ON l.ID_Livreur = lv.ID_Livreur
                LEFT JOIN utilisateur u ON lv.ID_Utilisateur = u.ID_Utilisateur
                LEFT JOIN utilisateur ucli ON c.ID_Utilisateur = ucli.ID_Utilisateur
                WHERE c.ID_Utilisateur = ?
                ORDER BY c.Date_Commande DESC
            ");
            $stmt->execute([$userId]);
        }
    } else {
        // Admin or Livreur - show all orders
        if ($status) {
            $stmt = $pdo->prepare("
                SELECT
                    c.ID_Commande,
                    c.ID_Utilisateur,
                    c.Date_Commande,
                    c.Statut,
                    c.Adresse_Livraison,
                    c.Prix_Total,
                    c.Notes,
                    l.Statut_Livraison,
                    l.Date_Debut_Livraison,
                    l.Date_Fin_Livraison,
                    l.Distance_KM,
                    u.Nom as Livreur_Nom,
                    ucli.Nom as Client_Nom
                FROM commande c
                LEFT JOIN livraison l ON c.ID_Commande = l.ID_Commande
                LEFT JOIN livreur lv ON l.ID_Livreur = lv.ID_Livreur
                LEFT JOIN utilisateur u ON lv.ID_Utilisateur = u.ID_Utilisateur
                LEFT JOIN utilisateur ucli ON c.ID_Utilisateur = ucli.ID_Utilisateur
                WHERE c.Statut = ?
                ORDER BY c.Date_Commande DESC
            ");
            $stmt->execute([$status]);
        } else {
            $stmt = $pdo->prepare("
                SELECT
                    c.ID_Commande,
                    c.ID_Utilisateur,
                    c.Date_Commande,
                    c.Statut,
                    c.Adresse_Livraison,
                    c.Prix_Total,
                    c.Notes,
                    l.Statut_Livraison,
                    l.Date_Debut_Livraison,
                    l.Date_Fin_Livraison,
                    l.Distance_KM,
                    u.Nom as Livreur_Nom,
                    ucli.Nom as Client_Nom
                FROM commande c
                LEFT JOIN livraison l ON c.ID_Commande = l.ID_Commande
                LEFT JOIN livreur lv ON l.ID_Livreur = lv.ID_Livreur
                LEFT JOIN utilisateur u ON lv.ID_Utilisateur = u.ID_Utilisateur
                LEFT JOIN utilisateur ucli ON c.ID_Utilisateur = ucli.ID_Utilisateur
                ORDER BY c.Date_Commande DESC
            ");
            $stmt->execute();
        }
    }
    
    $orders = $stmt->fetchAll();

    // Get products for each order
    foreach ($orders as &$order) {
        $stmt = $pdo->prepare("
            SELECT
                cp.Quantite,
                cp.Prix_Unitaire,
                p.Nom,
                p.Image_URL
            FROM commande_produit cp
            JOIN produit p ON cp.ID_Produit = p.ID_Produit
            WHERE cp.ID_Commande = ?
        ");
        $stmt->execute([$order['ID_Commande']]);
        $order['produits'] = $stmt->fetchAll();
    }

    echo json_encode([
        'success' => true,
        'orders' => $orders
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to fetch orders: ' . $e->getMessage()]);
}
?>
