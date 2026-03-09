<?php
/**
 * Fix Notifications Script
 * This script helps debug and fix notification issues for clients
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once '../config/database.php';

// Get current user from token
$user = null;
$authHeader = null;

$headers = getallheaders();
if ($headers) {
    foreach ($headers as $key => $value) {
        if (strtolower($key) === 'authorization') {
            $authHeader = $value;
            break;
        }
    }
}

if (!$authHeader) {
    foreach ($_SERVER as $key => $value) {
        if (strtolower($key) === 'http_authorization' || strtolower($key) === 'authorization') {
            $authHeader = $value;
            break;
        }
    }
}

if ($authHeader && (strpos($authHeader, 'Bearer ') === 0 || strpos($authHeader, 'bearer ') === 0)) {
    $token = substr($authHeader, 7);
    
    $parts = explode('.', $token);
    if (count($parts) === 3) {
        $payload = $parts[1];
        $payloadDecoded = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $payload)), true);
        if ($payloadDecoded && isset($payloadDecoded['user_id'])) {
            $user = $payloadDecoded;
        }
    }
}

// Handle different actions
$action = $_GET['action'] ?? 'status';

try {
    $pdo = getDB();
    
    switch ($action) {
        case 'create_test':
            // Create test notifications for the current user
            if (!$user) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Authentication required. Please login first.'
                ]);
                exit;
            }
            
            $userId = $user['user_id'];
            
            // Check if test notifications already exist
            $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM notifications WHERE ID_Utilisateur = ? AND Titre LIKE 'Test%'");
            $stmt->execute([$userId]);
            $existingCount = $stmt->fetch()['cnt'];
            
            if ($existingCount > 0) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Test notifications already exist',
                    'count' => (int)$existingCount
                ]);
                exit;
            }
            
            // Create test notifications
            $testNotifications = [
                [
                    'type' => 'commande',
                    'title' => 'Bienvenue sur CastleMarket',
                    'message' => 'Votre compte a été créé avec succès. Découvrez nos produits!'
                ],
                [
                    'type' => 'info',
                    'title' => 'Promotions spéciales',
                    'message' => 'Profitez de nos offres spéciales cette semaine!'
                ],
                [
                    'type' => 'livraison',
                    'title' => 'Information de livraison',
                    'message' => 'Vos commandes seront livrées bientôt.'
                ]
            ];
            
            $insertedCount = 0;
            foreach ($testNotifications as $notif) {
                $stmt = $pdo->prepare("
                    INSERT INTO notifications 
                    (ID_Utilisateur, Type_Notification, Titre, Message, Statut_Lecture, Date_Creation) 
                    VALUES (?, ?, ?, ?, 'Non lu', NOW())
                ");
                $stmt->execute([$userId, $notif['type'], $notif['title'], $notif['message']]);
                $insertedCount++;
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Test notifications created successfully',
                'count' => $insertedCount,
                'user_id' => $userId
            ]);
            break;
            
        case 'get_user_info':
            // Return current user info
            if (!$user) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Not authenticated'
                ]);
                exit;
            }
            
            // Get user's notification count
            $stmt = $pdo->prepare("SELECT COUNT(*) as total, SUM(CASE WHEN Statut_Lecture = 'Non lu' THEN 1 ELSE 0 END) as unread FROM notifications WHERE ID_Utilisateur = ?");
            $stmt->execute([$user['user_id']]);
            $stats = $stmt->fetch();
            
            echo json_encode([
                'success' => true,
                'user' => $user,
                'notifications' => [
                    'total' => (int)$stats['total'],
                    'unread' => (int)$stats['unread']
                ]
            ]);
            break;
            
        case 'list':
            // List all notifications for current user
            if (!$user) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Authentication required'
                ]);
                exit;
            }
            
            $stmt = $pdo->prepare("
                SELECT ID_Notification, ID_Utilisateur, Titre, Message, Type_Notification, 
                       Statut_Lecture, Date_Creation
                FROM notifications
                WHERE ID_Utilisateur = ?
                ORDER BY Date_Creation DESC
                LIMIT 50
            ");
            $stmt->execute([$user['user_id']]);
            $notifications = $stmt->fetchAll();
            
            echo json_encode([
                'success' => true,
                'notifications' => $notifications,
                'count' => count($notifications)
            ]);
            break;
            
        case 'status':
        default:
            // Show database status
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM notifications");
            $totalNotifs = $stmt->fetch()['total'];
            
            $stmt = $pdo->query("SELECT COUNT(DISTINCT ID_Utilisateur) as users FROM notifications");
            $usersWithNotifs = $stmt->fetch()['users'];
            
            // Show sample of recent notifications
            $stmt = $pdo->query("
                SELECT n.*, u.Email 
                FROM notifications n 
                LEFT JOIN utilisateur u ON n.ID_Utilisateur = u.ID_Utilisateur 
                ORDER BY n.Date_Creation DESC 
                LIMIT 5
            ");
            $sampleNotifs = $stmt->fetchAll();
            
            echo json_encode([
                'success' => true,
                'database_status' => [
                    'total_notifications' => $totalNotifs,
                    'users_with_notifications' => $usersWithNotifs
                ],
                'sample_notifications' => $sampleNotifs,
                'authenticated_user' => $user
            ]);
            break;
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>

