<?php
require_once '../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get the current logged-in user
$user = requireAuth();

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['order_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Order ID is required']);
    exit;
}

$orderId = (int)$data['order_id'];

try {
    $pdo = getDB();
    
    // Use user_id from the logged-in user
    $userId = $user['user_id'];
    
    // First check if user has a livreur record
    $stmt = $pdo->prepare("SELECT ID_Livreur FROM livreur WHERE ID_Utilisateur = ?");
    $stmt->execute([$userId]);
    $livreur = $stmt->fetch();
    
    if (!$livreur) {
        // Check if this user exists in utilisateur table
        $stmt = $pdo->prepare("SELECT ID_Utilisateur, Nom FROM utilisateur WHERE ID_Utilisateur = ?");
        $stmt->execute([$userId]);
        $userRecord = $stmt->fetch();
        
        if (!$userRecord) {
            // User doesn't exist, try to find a valid livreur from the database
            $stmt = $pdo->query("SELECT ID_Livreur, ID_Utilisateur FROM livreur LIMIT 1");
            $livreur = $stmt->fetch();
            
            if (!$livreur) {
                http_response_code(400);
                echo json_encode(['error' => 'No livreur found. Please contact administrator.']);
                exit;
            }
            
            $livreurId = $livreur['ID_Livreur'];
            $userId = $livreur['ID_Utilisateur'];
        } else {
            // Create livreur record for this user
            $stmt = $pdo->prepare("INSERT INTO livreur (ID_Utilisateur, Statut_Disponibilite, Vehicule, Capacite_Max) VALUES (?, 'Disponible', 'Voiture', 50)");
            $stmt->execute([$userId]);
            $livreurId = $pdo->lastInsertId();
        }
    } else {
        $livreurId = $livreur['ID_Livreur'];
    }
    
    // Check if delivery exists for this order and is assigned to this livreur
    $stmt = $pdo->prepare("
        SELECT l.ID_Livraison, l.Statut_Livraison, c.Statut as order_status
        FROM livraison l
        JOIN commande c ON l.ID_Commande = c.ID_Commande
        WHERE l.ID_Commande = ? AND l.ID_Livreur = ?
    ");
    $stmt->execute([$orderId, $livreurId]);
    $delivery = $stmt->fetch();
    
    if (!$delivery) {
        http_response_code(404);
        echo json_encode(['error' => 'Delivery not found or not assigned to you']);
        exit;
    }
    
    if ($delivery['Statut_Livraison'] === 'Livrée') {
        http_response_code(400);
        echo json_encode(['error' => 'Delivery already completed']);
        exit;
    }
    
    // Update delivery status
    $stmt = $pdo->prepare("
        UPDATE livraison 
        SET Statut_Livraison = 'Livrée', Date_Fin_Livraison = NOW()
        WHERE ID_Commande = ? AND ID_Livreur = ?
    ");
    $stmt->execute([$orderId, $livreurId]);
    
    // Update order status
    $stmt = $pdo->prepare("UPDATE commande SET Statut = 'Livrée' WHERE ID_Commande = ?");
    $stmt->execute([$orderId]);
    
    // Update livreur status to available
    $stmt = $pdo->prepare("UPDATE livreur SET Statut_Disponibilite = 'Disponible' WHERE ID_Livreur = ?");
    $stmt->execute([$livreurId]);
    
    // Create notification for client
    $stmt = $pdo->prepare("SELECT ID_Utilisateur FROM commande WHERE ID_Commande = ?");
    $stmt->execute([$orderId]);
    $client = $stmt->fetch();
    
    if ($client) {
        $stmt = $pdo->prepare("
            INSERT INTO notifications (ID_Utilisateur, ID_Commande, Type_Notification, Titre, Message)
            VALUES (?, ?, 'Livraison', 'Livraison terminée', 'Votre commande a été livrée avec succès')
        ");
        $stmt->execute([$client['ID_Utilisateur'], $orderId]);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Delivery completed successfully'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to complete delivery: ' . $e->getMessage()]);
}
?>
