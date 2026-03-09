<?php
require_once '../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'PUT' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$user = requireAuth();

$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request data']);
    exit;
}

$name = isset($data['name']) ? trim($data['name']) : '';
$email = isset($data['email']) ? trim($data['email']) : '';
$phone = isset($data['phone']) ? trim($data['phone']) : '';
$address = isset($data['address']) ? trim($data['address']) : '';
$currentPassword = isset($data['current_password']) ? $data['current_password'] : '';
$newPassword = isset($data['new_password']) ? $data['new_password'] : '';

// Validate required fields
if (empty($name)) {
    http_response_code(400);
    echo json_encode(['error' => 'Le nom est requis']);
    exit;
}

if (empty($email)) {
    http_response_code(400);
    echo json_encode(['error' => 'L\'email est requis']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['error' => 'Email invalide']);
    exit;
}

try {
    $pdo = getDB();
    
    // Check if email is already taken by another user
    $stmt = $pdo->prepare("SELECT ID_Utilisateur FROM utilisateurs WHERE Email = ? AND ID_Utilisateur != ?");
    $stmt->execute([$email, $user['user_id']]);
    if ($stmt->fetch()) {
        http_response_code(400);
        echo json_encode(['error' => 'Cet email est déjà utilisé par un autre compte']);
        exit;
    }
    
    // If changing password, verify current password
    if (!empty($newPassword)) {
        if (empty($currentPassword)) {
            http_response_code(400);
            echo json_encode(['error' => 'Le mot de passe actuel est requis pour changer le mot de passe']);
            exit;
        }
        
        // Verify current password
        $stmt = $pdo->prepare("SELECT Mot_De_Passe FROM utilisateurs WHERE ID_Utilisateur = ?");
        $stmt->execute([$user['user_id']]);
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!password_verify($currentPassword, $userData['Mot_De_Passe'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Le mot de passe actuel est incorrect']);
            exit;
        }
        
        // Hash new password
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        
        // Update with new password
        $stmt = $pdo->prepare("
            UPDATE utilisateurs 
            SET Nom = ?, Email = ?, Telephone = ?, Adresse = ?, Mot_De_Passe = ?
            WHERE ID_Utilisateur = ?
        ");
        $stmt->execute([$name, $email, $phone, $address, $hashedPassword, $user['user_id']]);
    } else {
        // Update without changing password
        $stmt = $pdo->prepare("
            UPDATE utilisateurs 
            SET Nom = ?, Email = ?, Telephone = ?, Adresse = ?
            WHERE ID_Utilisateur = ?
        ");
        $stmt->execute([$name, $email, $phone, $address, $user['user_id']]);
    }
    
    // Get updated user data
    $stmt = $pdo->prepare("SELECT ID_Utilisateur, Nom, Email, Telephone, Adresse, Role FROM utilisateurs WHERE ID_Utilisateur = ?");
    $stmt->execute([$user['user_id']]);
    $updatedUser = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'message' => 'Profil mis à jour avec succès',
        'user' => [
            'id' => (int)$updatedUser['ID_Utilisateur'],
            'name' => $updatedUser['Nom'],
            'email' => $updatedUser['Email'],
            'phone' => $updatedUser['Telephone'],
            'address' => $updatedUser['Adresse'],
            'role' => $updatedUser['Role']
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur lors de la mise à jour du profil: ' . $e->getMessage()]);
}
?>
