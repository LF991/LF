<?php
require_once '../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$user = requireRole('Admin');

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['order_id']) || !isset($data['livreur_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Order ID and Livreur ID are required']);
    exit;
}

$orderId = (int)$data['order_id'];
$livreurId = (int)$data['livreur_id'];
$estimatedTime = isset($data['estimated_time']) ? (int)$data['estimated_time'] : null;

try {
    $pdo = getDB();

    // Check if order exists and is ready for delivery
    $stmt = $pdo->prepare("SELECT Statut FROM commande WHERE ID_Commande = ?");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();

    if (!$order) {
        http_response_code(404);
        echo json_encode(['error' => 'Order not found']);
        exit;
    }

    if ($order['Statut'] !== 'Prête') {
        http_response_code(400);
        echo json_encode(['error' => 'Order must be ready for delivery']);
        exit;
    }

    // Check if livreur exists and is available
    $stmt = $pdo->prepare("SELECT Statut_Disponibilite FROM livreur WHERE ID_Livreur = ?");
    $stmt->execute([$livreurId]);
    $livreur = $stmt->fetch();

    if (!$livreur) {
        http_response_code(404);
        echo json_encode(['error' => 'Livreur not found']);
        exit;
    }

    if ($livreur['Statut_Disponibilite'] !== 'Disponible') {
        http_response_code(400);
        echo json_encode(['error' => 'Livreur is not available']);
        exit;
    }

    // Check if delivery already exists for this order
    $stmt = $pdo->prepare("SELECT ID_Livraison FROM livraison WHERE ID_Commande = ?");
    $stmt->execute([$orderId]);
    if ($stmt->fetch()) {
        http_response_code(400);
        echo json_encode(['error' => 'Delivery already assigned to this order']);
        exit;
    }

    // Create delivery
    $stmt = $pdo->prepare("
        INSERT INTO livraison (ID_Commande, ID_Livreur, Temps_Estime, Statut_Livraison)
        VALUES (?, ?, ?, 'Assignée')
    ");
    $stmt->execute([$orderId, $livreurId, $estimatedTime]);

    $deliveryId = $pdo->lastInsertId();

    // Update livreur status
    $stmt = $pdo->prepare("UPDATE livreur SET Statut_Disponibilite = 'En livraison' WHERE ID_Livreur = ?");
    $stmt->execute([$livreurId]);

    // Update order status
    $stmt = $pdo->prepare("UPDATE commande SET Statut = 'En livraison' WHERE ID_Commande = ?");
    $stmt->execute([$orderId]);

    // Create notification for client
    $stmt = $pdo->prepare("SELECT ID_Utilisateur FROM commande WHERE ID_Commande = ?");
    $stmt->execute([$orderId]);
    $clientId = $stmt->fetch()['ID_Utilisateur'];

    $stmt = $pdo->prepare("
        INSERT INTO notifications (ID_Utilisateur, ID_Commande, ID_Livraison, Type_Notification, Titre, Message)
        VALUES (?, ?, ?, 'Livraison', 'Livraison assignée', 'Votre commande est en cours de livraison')
    ");
    $stmt->execute([$clientId, $orderId, $deliveryId]);

    echo json_encode([
        'success' => true,
        'message' => 'Delivery assigned successfully',
        'delivery_id' => $deliveryId
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to assign delivery: ' . $e->getMessage()]);
}
?>
