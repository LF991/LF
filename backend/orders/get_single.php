<?php
require_once '../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$user = requireAuth();

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Order ID is required']);
    exit;
}

$orderId = (int)$_GET['id'];

try {
    $pdo = getDB();

    // Check if order belongs to user (unless admin)
    $stmt = $pdo->prepare("SELECT ID_Utilisateur FROM commande WHERE ID_Commande = ?");
    $stmt->execute([$orderId]);
    $orderOwner = $stmt->fetch();

    if (!$orderOwner) {
        http_response_code(404);
        echo json_encode(['error' => 'Order not found']);
        exit;
    }

    if ($user['role'] !== 'Admin' && $orderOwner['ID_Utilisateur'] !== $user['user_id']) {
        http_response_code(403);
        echo json_encode(['error' => 'Access denied']);
        exit;
    }

    $stmt = $pdo->prepare("
        SELECT
            c.ID_Commande,
            c.Date_Commande,
            c.Statut,
            c.Adresse_Livraison,
            c.Prix_Total,
            c.Notes,
            l.Statut_Livraison,
            l.Date_Debut_Livraison,
            l.Date_Fin_Livraison,
            l.Distance_KM,
            l.Temps_Estime,
            u.Nom as Livreur_Nom,
            u.Telephone as Livreur_Telephone,
            lv.Position_GPS_Actuelle as livreur_gps,
            client.Coordonnees_GPS as client_gps
        FROM commande c
        LEFT JOIN livraison l ON c.ID_Commande = l.ID_Commande
        LEFT JOIN livreur lv ON l.ID_Livreur = lv.ID_Livreur
        LEFT JOIN utilisateur u ON lv.ID_Utilisateur = u.ID_Utilisateur
        LEFT JOIN utilisateur client ON c.ID_Utilisateur = client.ID_Utilisateur
        WHERE c.ID_Commande = ?
    ");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();

    if (!$order) {
        http_response_code(404);
        echo json_encode(['error' => 'Order not found']);
        exit;
    }

    // Calculate real-time estimated time if livreur is assigned and has location
    $realTimeEstimatedMinutes = null;
    $realTimeDistanceKm = null;
    $livreurPosition = null;
    $clientPosition = null;

    // Check if livreur is assigned (either via Statut or Statut_Livraison)
    $isInDelivery = in_array($order['Statut'], ['En livraison', 'En cours']) || 
                    in_array($order['Statut_Livraison'], ['Assignée', 'En cours']);

    if ($isInDelivery) {
        // Extract coordinates from MySQL POINT geometry using ST_X and ST_Y directly on columns
        $stmt = $pdo->prepare("
            SELECT 
                ST_X(client.Coordonnees_GPS) as client_lon, 
                ST_Y(client.Coordonnees_GPS) as client_lat,
                ST_X(lv.Position_GPS_Actuelle) as livreur_lon, 
                ST_Y(lv.Position_GPS_Actuelle) as livreur_lat
            FROM commande c
            LEFT JOIN livraison l ON c.ID_Commande = l.ID_Commande
            LEFT JOIN livreur lv ON l.ID_Livreur = lv.ID_Livreur
            LEFT JOIN utilisateur client ON c.ID_Utilisateur = client.ID_Utilisateur
            WHERE c.ID_Commande = ?
        ");
        $stmt->execute([$orderId]);
        $coords = $stmt->fetch();
        
        // Get client position if available
        if ($coords && $coords['client_lat'] && $coords['client_lon']) {
            $clientPosition = [
                'latitude' => (float)$coords['client_lat'],
                'longitude' => (float)$coords['client_lon']
            ];
        }
        
        // Get livreur position if available
        if ($coords && $coords['livreur_lat'] && $coords['livreur_lon']) {
            $livreurPosition = [
                'latitude' => (float)$coords['livreur_lat'],
                'longitude' => (float)$coords['livreur_lon']
            ];
            
            // Calculate distance and time if both positions are available
            if ($clientPosition) {
                $realTimeDistanceKm = calculateHaversineDistance(
                    $coords['livreur_lat'], $coords['livreur_lon'],
                    $coords['client_lat'], $coords['client_lon']
                );
                
                // Calculate estimated time at 40km/h
                $realTimeEstimatedMinutes = calculateEstimatedTime($realTimeDistanceKm, 40);
            }
        }
    }

    // Add real-time tracking data
    $order['real_time_estimated_minutes'] = $realTimeEstimatedMinutes;
    $order['real_time_distance_km'] = $realTimeDistanceKm;
    $order['livreur_position'] = $livreurPosition;
    $order['client_position'] = $clientPosition;
    $order['delivery_speed_kmh'] = 40;
    
    // If no real-time data, use static estimated time
    if (!$realTimeEstimatedMinutes && $order['Temps_Estime']) {
        $order['estimated_delivery_minutes'] = (int)$order['Temps_Estime'];
    } elseif ($realTimeEstimatedMinutes) {
        $order['estimated_delivery_minutes'] = $realTimeEstimatedMinutes;
    }

    // Get products for the order
    $stmt = $pdo->prepare("
        SELECT
            cp.Quantite,
            cp.Prix_Unitaire,
            p.ID_Produit,
            p.Nom,
            p.Description,
            p.Image_URL,
            p.Categorie
        FROM commande_produit cp
        JOIN produit p ON cp.ID_Produit = p.ID_Produit
        WHERE cp.ID_Commande = ?
    ");
    $stmt->execute([$orderId]);
    $order['produits'] = $stmt->fetchAll();

    // Remove raw GPS data
    unset($order['livreur_gps']);
    unset($order['client_gps']);

    echo json_encode([
        'success' => true,
        'order' => $order
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to fetch order: ' . $e->getMessage()]);
}

/**
 * Calculate distance between two points using Haversine formula
 */
function calculateHaversineDistance($lat1, $lon1, $lat2, $lon2) {
    $earthRadius = 6371;
    
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
 */
function calculateEstimatedTime($distanceKm, $speedKmh) {
    if ($speedKmh <= 0) {
        $speedKmh = 40;
    }
    // Formula: distance * 60 / speed
    $minutes = ($distanceKm * 60) / $speedKmh;
    return round($minutes);
}
?>
