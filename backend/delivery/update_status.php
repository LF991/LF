<?php
require_once '../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get the current logged-in user
$user = requireAuth();

// Check if user is a livreur
$userRole = isset($user['role']) ? strtolower($user['role']) : '';
if ($userRole !== 'livreur') {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied. Only livreurs can update delivery status.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['order_id']) || !isset($data['status'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Order ID and status are required']);
    exit;
}

$orderId = (int)$data['order_id'];
$newStatus = trim($data['status']);

// Valid status values
$validStatuses = ['En cours', 'Livrée', 'Annulée', 'Retard'];
if (!in_array($newStatus, $validStatuses)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid status. Valid values: En cours, Livrée, Annulée, Retard']);
    exit;
}

try {
    $pdo = getDB();
    
    // Get the livreur ID from the logged-in user
    $userId = $user['user_id'];
    
    $stmt = $pdo->prepare("SELECT ID_Livreur FROM livreur WHERE ID_Utilisateur = ?");
    $stmt->execute([$userId]);
    $livreur = $stmt->fetch();
    
    if (!$livreur) {
        http_response_code(400);
        echo json_encode(['error' => 'Livreur not found for this user']);
        exit;
    }
    
    $livreurId = $livreur['ID_Livreur'];
    
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
    
    // Prevent updating if already delivered or cancelled
    if ($delivery['Statut_Livraison'] === 'Livrée') {
        http_response_code(400);
        echo json_encode(['error' => 'Cannot update a delivered order']);
        exit;
    }
    
    if ($delivery['Statut_Livraison'] === 'Annulée' && $newStatus !== 'En cours') {
        http_response_code(400);
        echo json_encode(['error' => 'Cannot update a cancelled delivery except to restart it']);
        exit;
    }
    
    // Start transaction
    $pdo->beginTransaction();
    
    // Update delivery status
    $deliveryUpdateFields = "Statut_Livraison = ?";
    $deliveryParams = [$newStatus];
    
    if ($newStatus === 'En cours' && $delivery['Statut_Livraison'] !== 'En cours') {
        $deliveryUpdateFields .= ", Date_Debut_Livraison = NOW()";
    } elseif ($newStatus === 'Livrée') {
        $deliveryUpdateFields .= ", Date_Fin_Livraison = NOW()";
    } elseif ($newStatus === 'Annulée') {
        // Make livreur available again if cancelled
        $stmt = $pdo->prepare("UPDATE livreur SET Statut_Disponibilite = 'Disponible' WHERE ID_Livreur = ?");
        $stmt->execute([$livreurId]);
    } elseif ($newStatus === 'En cours') {
        // Make livreur unavailable if in delivery
        $stmt = $pdo->prepare("UPDATE livreur SET Statut_Disponibilite = 'En livraison' WHERE ID_Livreur = ?");
        $stmt->execute([$livreurId]);
    }
    
    $stmt = $pdo->prepare("UPDATE livraison SET $deliveryUpdateFields WHERE ID_Commande = ? AND ID_Livreur = ?");
    $deliveryParams[] = $orderId;
    $deliveryParams[] = $livreurId;
    $stmt->execute($deliveryParams);
    
    // Update order status based on delivery status
    $orderStatus = '';
    switch ($newStatus) {
        case 'En cours':
            $orderStatus = 'En livraison';
            break;
        case 'Livrée':
            $orderStatus = 'Livrée';
            break;
        case 'Annulée':
            $orderStatus = 'Annulée';
            break;
        case 'Retard':
            // Keep the current order status, just mark delivery as delayed
            $orderStatus = $delivery['order_status'];
            break;
    }
    
    if ($orderStatus) {
        $stmt = $pdo->prepare("UPDATE commande SET Statut = ? WHERE ID_Commande = ?");
        $stmt->execute([$orderStatus, $orderId]);
    }
    
    // If delivery is completed, make livreur available again
    if ($newStatus === 'Livrée') {
        $stmt = $pdo->prepare("UPDATE livreur SET Statut_Disponibilite = 'Disponible' WHERE ID_Livreur = ?");
        $stmt->execute([$livreurId]);
    }
    
    // Create notification for client
    $stmt = $pdo->prepare("SELECT ID_Utilisateur FROM commande WHERE ID_Commande = ?");
    $stmt->execute([$orderId]);
    $client = $stmt->fetch();
    
    if ($client) {
        $notificationTitle = '';
        $notificationMessage = '';
        
        switch ($newStatus) {
            case 'En cours':
                $notificationTitle = 'Livraison en cours';
                $notificationMessage = 'Votre commande est en cours de livraison';
                break;
            case 'Livrée':
                $notificationTitle = 'Livraison terminée';
                $notificationMessage = 'Votre commande a été livrée avec succès';
                break;
            case 'Annulée':
                $notificationTitle = 'Livraison annulée';
                $notificationMessage = 'Votre livraison a été annulée';
                break;
            case 'Retard':
                $notificationTitle = 'Livraison retardée';
                $notificationMessage = 'Votre livraison accuse du retard';
                break;
        }
        
        if ($notificationTitle) {
            $stmt = $pdo->prepare("
                INSERT INTO notifications (ID_Utilisateur, ID_Commande, Type_Notification, Titre, Message)
                VALUES (?, ?, 'Livraison', ?, ?)
            ");
            $stmt->execute([$client['ID_Utilisateur'], $orderId, $notificationTitle, $notificationMessage]);
        }
    }
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Delivery status updated successfully',
        'new_status' => $newStatus
    ]);

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['error' => 'Failed to update delivery status: ' . $e->getMessage()]);
}
?>
