<?php
require_once __DIR__ . '/../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Allow anonymous orders - no authentication required
// Use the getCurrentUser function from database.php which handles token properly
$user = getCurrentUser();
// If getCurrentUser returns false (invalid/expired token), treat as anonymous
if ($user === false) {
    $user = null;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['delivery_address']) || !isset($data['items']) || empty($data['items'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Delivery address and items are required']);
    exit;
}

$deliveryAddress = trim($data['delivery_address']);
$deliveryNotes = isset($data['delivery_notes']) ? trim($data['delivery_notes']) : null;
$items = $data['items'];

// Use total, subtotal, and delivery_fee from frontend if provided
$subtotal = isset($data['subtotal']) ? (float)$data['subtotal'] : 0;
$deliveryFee = isset($data['delivery_fee']) ? (float)$data['delivery_fee'] : 0;
$total = isset($data['total']) ? (float)$data['total'] : 0;

if (empty($deliveryAddress)) {
    http_response_code(400);
    echo json_encode(['error' => 'Delivery address cannot be empty']);
    exit;
}

try {
    $pdo = getDB();
    $pdo->beginTransaction();

    // Validate items and recalculate to verify
    $calculatedSubtotal = 0;
    $validItems = [];

    foreach ($items as $item) {
        if (!isset($item['product_id']) || !isset($item['quantity']) || !isset($item['price'])) {
            $pdo->rollBack();
            http_response_code(400);
            echo json_encode(['error' => 'Invalid item data']);
            exit;
        }

        $productId = (int)$item['product_id'];
        $quantity = (int)$item['quantity'];
        $price = (float)$item['price'];

        if ($quantity <= 0 || $price <= 0) {
            $pdo->rollBack();
            http_response_code(400);
            echo json_encode(['error' => 'Invalid quantity or price']);
            exit;
        }

        // Verify product exists and is available
        $stmt = $pdo->prepare("SELECT Stock, Statut FROM produit WHERE ID_Produit = ?");
        $stmt->execute([$productId]);
        $product = $stmt->fetch();

        if (!$product || $product['Statut'] !== 'Disponible' || $product['Stock'] < $quantity) {
            $pdo->rollBack();
            http_response_code(400);
            echo json_encode(['error' => 'Product not available or insufficient stock']);
            exit;
        }

        $calculatedSubtotal += $price * $quantity;
        $validItems[] = [
            'id' => $productId,
            'quantity' => $quantity,
            'price' => $price
        ];
    }

    // Use the total from frontend - always use the value sent from the frontend
    // The frontend calculates: total = (sum of product prices * quantities) + deliveryFee
    if (isset($data['total']) && $data['total'] > 0) {
        $total = (float)$data['total'];
    } else {
        // Fallback: calculate if not provided or invalid
        if ($deliveryFee === 0) {
            $deliveryFee = calculateDeliveryFee($pdo, $deliveryAddress);
        }
        $total = $calculatedSubtotal + $deliveryFee;
    }

    // For anonymous users, create a guest user entry
    if (!$user) {
        // Check if guest user exists, if not create one
        $stmt = $pdo->prepare("SELECT ID_Utilisateur FROM utilisateur WHERE Email = 'guest@supermarche.com'");
        $stmt->execute();
        $guestUser = $stmt->fetch();

        if (!$guestUser) {
            $stmt = $pdo->prepare("
                INSERT INTO utilisateur (Nom, Email, Mot_de_passe, Role, Adresse, Statut)
                VALUES ('Client Anonyme', 'guest@supermarche.com', '', 'Client', ?, 'Actif')
            ");
            $stmt->execute([$deliveryAddress]);
            $userId = $pdo->lastInsertId();
        } else {
            $userId = $guestUser['ID_Utilisateur'];
        }
    } else {
        $userId = $user['user_id'];
    }

    // Create order
    $stmt = $pdo->prepare("
        INSERT INTO commande (ID_Utilisateur, Adresse_Livraison, Prix_Total, Notes, Statut)
        VALUES (?, ?, ?, ?, 'Confirmée')
    ");
    $stmt->execute([$userId, $deliveryAddress, $total, $deliveryNotes]);

    $orderId = $pdo->lastInsertId();

    // Add products to order
    foreach ($validItems as $item) {
        $stmt = $pdo->prepare("
            INSERT INTO commande_produit (ID_Commande, ID_Produit, Quantite, Prix_Unitaire)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$orderId, $item['id'], $item['quantity'], $item['price']]);

        // Update product stock
        $stmt = $pdo->prepare("UPDATE produit SET Stock = Stock - ? WHERE ID_Produit = ?");
        $stmt->execute([$item['quantity'], $item['id']]);
        
        // Automatically set status to Indisponible if stock reaches 0 or less
        $stmt = $pdo->prepare("UPDATE produit SET Statut = 'Indisponible' WHERE ID_Produit = ? AND Stock <= 0");
        $stmt->execute([$item['id']]);
    }

    // Assign delivery and estimate time
    $deliveryInfo = assignDelivery($pdo, $orderId, $deliveryAddress);

    $pdo->commit();

    // Create notification only for authenticated users
    if ($user) {
        $stmt = $pdo->prepare("
            INSERT INTO notifications (ID_Utilisateur, ID_Commande, Type_Notification, Titre, Message)
            VALUES (?, ?, 'Commande', 'Commande confirmée', 'Votre commande a été confirmée et sera livrée bientôt')
        ");
        $stmt->execute([$userId, $orderId]);
    }

    echo json_encode([
        'success' => true,
        'order_id' => $orderId,
        'total' => $total,
        'delivery_fee' => $deliveryFee,
        'estimated_delivery_time' => $deliveryInfo['estimated_time'],
        'distance_km' => $deliveryInfo['distance_km']
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['error' => 'Failed to create order: ' . $e->getMessage()]);
}

function calculateDeliveryFee($pdo, $deliveryAddress) {
    // Calculate distance based on delivery address (simplified)
    // In a real application, you'd use GPS coordinates and mapping API
    $distance = calculateDistanceFromChlef($deliveryAddress);
    $feePerKm = 1.0; // €1 per km (matching frontend)

    return $distance * $feePerKm;
}

function calculateDistanceFromChlef($address) {
    // Distance from Chlef city to each municipality (approximate in km)
    $distances = [
        'Chlef' => 0,
        'Abou El Hassan' => 10,
        'Aïn Merane' => 15,
        'Bénairia' => 20,
        'Boukadir' => 25,
        'Chettia' => 30,
        'Djidioua' => 35,
        'El Karimia' => 40,
        'El Marsa' => 45,
        'Ouled Fares' => 50,
        'Oum Drou' => 55,
        'Sendjas' => 60,
        'Sobha' => 65,
        'Ténès' => 70,
        'Zemmora' => 75
    ];

    // Return distance if municipality is found, otherwise default to 10km
    return isset($distances[$address]) ? $distances[$address] : 10;
}

function assignDelivery($pdo, $orderId, $deliveryAddress) {
    // Calculate estimated time based on distance (simplified)
    $distance = calculateDistanceFromChlef($deliveryAddress);
    $estimatedTime = calculateEstimatedTime($distance);

    // Create delivery record WITHOUT assigning to a specific livreur
    // The order will be visible to ALL livreurs and the first one to accept will get it
    $stmt = $pdo->prepare("
        INSERT INTO livraison (ID_Commande, ID_Livreur, Temps_Estime, Statut_Livraison, Distance_KM)
        VALUES (?, NULL, ?, 'En attente', ?)
    ");
    $stmt->execute([$orderId, $estimatedTime, $distance]);

    return [
        'estimated_time' => $estimatedTime,
        'distance_km' => $distance
    ];
}

function calculateEstimatedTime($distanceKm) {
    // Formula: distance * 60 / speed (40 km/h)
    $speedKmh = 40;
    $minutes = ($distanceKm * 60) / $speedKmh;
    return round($minutes);
}
?>
