<?php
require_once '../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$user = requireAuth();

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['notification_id']) || !is_numeric($data['notification_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Notification ID is required']);
    exit;
}

$notificationId = (int)$data['notification_id'];

try {
    $pdo = getDB();

    // Check if notification belongs to user
    $stmt = $pdo->prepare("SELECT ID_Utilisateur FROM notifications WHERE ID_Notification = ?");
    $stmt->execute([$notificationId]);
    $notification = $stmt->fetch();

    if (!$notification) {
        http_response_code(404);
        echo json_encode(['error' => 'Notification not found']);
        exit;
    }

    if ($notification['ID_Utilisateur'] !== $user['user_id']) {
        http_response_code(403);
        echo json_encode(['error' => 'Access denied']);
        exit;
    }

    // Mark as read
    $stmt = $pdo->prepare("
        UPDATE notifications
        SET Statut_Lecture = 'Lu', Date_Lecture = NOW()
        WHERE ID_Notification = ?
    ");
    $stmt->execute([$notificationId]);

    echo json_encode([
        'success' => true,
        'message' => 'Notification marked as read'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to mark notification as read: ' . $e->getMessage()]);
}
?>
