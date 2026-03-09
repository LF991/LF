<?php
require_once '../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get the current logged-in user
$user = requireAuth();

// Check if user is a livreur
if ($user['role'] !== 'Livreur') {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied. Only livreurs can access this resource.']);
    exit;
}

// Get order ID from query parameter
$orderId = isset($_GET['id']) ? (int)$_GET['id'] : null;

if (!$orderId) {
    http_response_code(400);
    echo json_encode(['error' => 'Order ID is required']);
    exit;
}

try {
    $pdo = getDB();
    
    // Get order details with livreur and client location
    $stmt = $pdo->prepare("
        SELECT
            c.ID_Commande,
            c.Date_Commande,
            c.Statut as order_status,
            c.Adresse_Livraison,
            c.Prix_Total,
            c.Notes,
            l.ID_Livraison,
            l.Statut_Livraison,
            l.Date_Debut_Livraison,
            l.Date_Fin_Livraison,
            l.Distance_KM as delivery_distance,
            u.Nom as client_name,
            u.Telephone as client_phone,
            u.Coordonnees_GPS as client_gps,
            lv.Position_GPS_Actuelle as livreur_gps,
            lv.ID_Livreur
        FROM commande c
        LEFT JOIN livraison l ON c.ID_Commande = l.ID_Commande
        LEFT JOIN utilisateur u ON c.ID_Utilisateur = u.ID_Utilisateur
        LEFT JOIN livreur lv ON l.ID_Livreur = lv.ID_Livreur
        WHERE c.ID_Commande = ?
    ");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();
    
    if (!$order) {
        http_response_code(404);
        echo json_encode(['error' => 'Order not found']);
        exit;
    }
    
    // Calculate real-time distance and estimated time if livreur has location
    $realTimeDistance = null;
    $realTimeEstimatedMinutes = null;
    $livreurPosition = null;
    $clientPosition = null;
    
    if ($order['livreur_gps'] && $order['client_gps']) {
        // Extract coordinates from MySQL POINT geometry
        $stmt = $pdo->prepare("
            SELECT 
                ST_X(?) as client_lon, ST_Y(?) as client_lat,
                ST_X(?) as livreur_lon, ST_Y(?) as livreur_lat
        ");
        $stmt->execute([$order['client_gps'], $order['client_gps'], $order['livreur_gps'], $order['livreur_gps']]);
        $coords = $stmt->fetch();
        
        if ($coords && $coords['client_lat'] && $coords['client_lon']) {
            $clientPosition = [
                'latitude' => (float)$coords['client_lat'],
                'longitude' => (float)$coords['client_lon']
            ];
            $livreurPosition = [
                'latitude' => (float)$coords['livreur_lat'],
                'longitude' => (float)$coords['livreur_lon']
            ];
            
            // Calculate distance using Haversine formula
            $realTimeDistance = calculateHaversineDistance(
                $coords['livreur_lat'], $coords['livreur_lon'],
                $coords['client_lat'], $coords['client_lon']
            );
            
            // Calculate estimated time at 40km/h
            $realTimeEstimatedMinutes = calculateEstimatedTime($realTimeDistance, 40);
        }
    }
    
    // Add real-time tracking data to order
    $order['real_time_distance_km'] = $realTimeDistance;
    $order['real_time_estimated_minutes'] = $realTimeEstimatedMinutes;
    $order['livreur_position'] = $livreurPosition;
    $order['client_position'] = $clientPosition;
    $order['delivery_speed_kmh'] = 40;
    
    // Get order items
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
    $stmt->execute([$orderId]);
    $items = $stmt->fetchAll();
    
    $order['items'] = $items;
    
    // Remove raw GPS data from response (we send formatted positions instead)
    unset($order['client_gps']);
    unset($order['livreur_gps']);
    
    echo json_encode([
        'success' => true,
        'order' => $order
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to fetch order details: ' . $e->getMessage()]);
}

/**
 * Calculate distance between two points using Haversine formula
 * @param float $lat1 Latitude of point 1
 * @param float $lon1 Longitude of point 1
 * @param float $lat2 Latitude of point 2
 * @param float $lon2 Longitude of point 2
 * @return float Distance in kilometers
 */
function calculateHaversineDistance($lat1, $lon1, $lat2, $lon2) {
    $earthRadius = 6371; // Earth's radius in kilometers
    
    $lat1Rad = deg2rad($lat1);
    $lat2Rad = deg2rad($lat2);
    $deltaLat = deg2rad($lat2 - $lat1);
    $deltaLon = deg2rad($lon2 - $lon1);
    
    $a = sin($deltaLat / 2) * sin($deltaLat / 2) +
         cos($lat1Rad) * cos($lat2Rad) *
         sin($deltaLon / 2) * sin($deltaLon / 2);
    
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    
    return round($earthRadius * $c, 2);
}

/**
 * Calculate estimated delivery time based on distance and speed
 * @param float $distanceKm Distance in kilometers
 * @param float $speedKmh Speed in km/h
 * @return float Estimated time in minutes
 */
function calculateEstimatedTime($distanceKm, $speedKmh) {
    if ($speedKmh <= 0) {
        $speedKmh = 40; // Default speed
    }
    // Formula: distance * 60 / speed
    $minutes = ($distanceKm * 60) / $speedKmh;
    return round($minutes);
}
?>
