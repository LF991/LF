<?php
require_once 'config/database.php';

header('Content-Type: application/json');

$email = $_GET['email'] ?? 'admin@supermarche.com';

try {
    // Get user from database directly
    $stmt = $pdo->prepare("SELECT ID_Utilisateur, Email, Mot_de_passe, Role, Nom FROM utilisateur WHERE Email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo json_encode(['found' => false, 'email' => $email]);
        exit;
    }

    // Test different password options
    $testPasswords = ['admin', 'admin123', 'password', 'test123'];
    
    $results = [];
    foreach ($testPasswords as $pwd) {
        $isHashed = password_get_info($user['Mot_de_passe'])['algo'] !== 0;
        
        $valid = false;
        if ($isHashed) {
            $valid = password_verify($pwd, $user['Mot_de_passe']);
        } else {
            $valid = ($user['Mot_de_passe'] === $pwd);
        }
        
        $results[] = [
            'password' => $pwd,
            'valid' => $valid,
            'is_hashed' => $isHashed
        ];
    }

    echo json_encode([
        'found' => true,
        'user' => [
            'id' => $user['ID_Utilisateur'],
            'email' => $user['Email'],
            'role' => $user['Role'],
            'name' => $user['Nom']
        ],
        'stored_password' => $user['Mot_de_passe'],
        'stored_password_length' => strlen($user['Mot_de_passe']),
        'stored_password_raw_bytes' => bin2hex($user['Mot_de_passe']),
        'password_info' => password_get_info($user['Mot_de_passe']),
        'test_results' => $results
    ]);

} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
