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
    
    // Get all notifications for the current user
    $stmt = $pdo->prepare("
        SELECT 
            ID_Notification,
            ID_Utilisateur,
            Titre,
            Message,
            Type_Notification,
            Statut_Lecture,
            Date_Creation,
            Date_Lecture
        FROM notifications
        WHERE ID_Utilisateur = ?
        ORDER BY Date_Creation DESC
        LIMIT 50
    ");
    
    $stmt->execute([$user['user_id']]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get unread count
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as unread_count 
        FROM notifications 
        WHERE ID_Utilisateur = ? AND Statut_Lecture = 'Non lu'
    ");
    $stmt->execute([$user['user_id']]);
    $unreadResult = $stmt->fetch(PDO::FETCH_ASSOC);
    $unreadCount = $unreadResult ? (int)$unreadResult['unread_count'] : 0;
    
    // Format notifications for response
    $formattedNotifications = array_map(function($notif) {
        return [
            'id' => (int)$notif['ID_Notification'],
            'title' => $notif['Titre'],
            'message' => $notif['Message'],
            'type' => $notif['Type_Notification'],
            'status' => $notif['Statut_Lecture'],
            'created_at' => $notif['Date_Creation'],
            'read_at' => $notif['Date_Lecture'],
            'link' => null
        ];
    }, $notifications);
    
    echo json_encode([
        'success' => true,
        'notifications' => $formattedNotifications,
        'unread_count' => $unreadCount
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to fetch notifications: ' . $e->getMessage()]);
}
?>
