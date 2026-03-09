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
            $stmt = $pdo->prepare("INSERT INTO livreur (ID_Utilisateur, Statut_Disponibilite, Vehicule, Capacite_Max) VALUES (?, 'En livraison', 'Voiture', 50)");
            $stmt->execute([$userId]);
            $livreurId = $pdo->lastInsertId();
        }
    } else {
        $livreurId = $livreur['ID_Livreur'];
    }
    
    // Check if order exists and is ready for delivery
    $stmt = $pdo->prepare("SELECT Statut FROM commande WHERE ID_Commande = ?");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();
    
    if (!$order) {
        http_response_code(404);
        echo json_encode(['error' => 'Order not found']);
        exit;
    }
    
    $validStatuses = ['Prête', 'Confirmée', 'En préparation'];
    if (!in_array($order['Statut'], $validStatuses)) {
        http_response_code(400);
        echo json_encode(['error' => 'Order is not ready for delivery. Current status: ' . $order['Statut']]);
        exit;
    }
    
    // Check if delivery already exists for this order
    $stmt = $pdo->prepare("SELECT ID_Livraison, Statut_Livraison, ID_Livreur FROM livraison WHERE ID_Commande = ?");
    $stmt->execute([$orderId]);
    $existingDelivery = $stmt->fetch();
    
    if ($existingDelivery) {
        if ($existingDelivery['Statut_Livraison'] === 'Livrée') {
            http_response_code(400);
            echo json_encode(['error' => 'Delivery already completed for this order']);
            exit;
        }
        
        // If delivery is assigned to this same livreur, just update the status
        if ($existingDelivery['ID_Livreur'] == $livreurId) {
            if ($existingDelivery['Statut_Livraison'] === 'Assignée' || $existingDelivery['Statut_Livraison'] === 'En cours') {
                $stmt = $pdo->prepare("UPDATE livraison SET Statut_Livraison = 'En cours', Date_Debut_Livraison = NOW() WHERE ID_Livraison = ?");
                $stmt->execute([$existingDelivery['ID_Livraison']]);
                $deliveryId = $existingDelivery['ID_Livraison'];
            } else {
                $deliveryId = $existingDelivery['ID_Livraison'];
            }
        } else {
            // Different livreur - reassign the delivery
            if ($existingDelivery['Statut_Livraison'] !== 'Annulée') {
                $stmt = $pdo->prepare("
                    UPDATE livraison 
                    SET ID_Livreur = ?, Statut_Livraison = 'En cours', Date_Debut_Livraison = NOW()
                    WHERE ID_Commande = ?
                ");
                $stmt->execute([$livreurId, $orderId]);
                $deliveryId = $existingDelivery['ID_Livraison'];
            } else {
                // If cancelled, create new delivery
                $stmt = $pdo->prepare("
                    INSERT INTO livraison (ID_Commande, ID_Livreur, Statut_Livraison, Date_Debut_Livraison)
                    VALUES (?, ?, 'En cours', NOW())
                ");
                $stmt->execute([$orderId, $livreurId]);
                $deliveryId = $pdo->lastInsertId();
            }
        }
    } else {
        // Create new delivery
        $stmt = $pdo->prepare("
            INSERT INTO livraison (ID_Commande, ID_Livreur, Statut_Livraison, Date_Debut_Livraison)
            VALUES (?, ?, 'En cours', NOW())
        ");
        $stmt->execute([$orderId, $livreurId]);
        $deliveryId = $pdo->lastInsertId();
    }
    
    // Update order status
    $stmt = $pdo->prepare("UPDATE commande SET Statut = 'En livraison' WHERE ID_Commande = ?");
    $stmt->execute([$orderId]);
    
    // Update livreur status
    $stmt = $pdo->prepare("UPDATE livreur SET Statut_Disponibilite = 'En livraison' WHERE ID_Livreur = ?");
    $stmt->execute([$livreurId]);
    
    // Create notification for client
    $stmt = $pdo->prepare("SELECT ID_Utilisateur FROM commande WHERE ID_Commande = ?");
    $stmt->execute([$orderId]);
    $client = $stmt->fetch();
    
    if ($client) {
        $stmt = $pdo->prepare("
            INSERT INTO notifications (ID_Utilisateur, ID_Commande, ID_Livraison, Type_Notification, Titre, Message)
            VALUES (?, ?, ?, 'Livraison', 'Livraison acceptée', 'Votre commande est en cours de livraison')
        ");
        $stmt->execute([$client['ID_Utilisateur'], $orderId, $deliveryId]);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Delivery accepted successfully',
        'delivery_id' => $deliveryId
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to accept delivery: ' . $e->getMessage()]);
}
?>
