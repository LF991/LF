<?php
ini_set('display_errors', 0);
error_reporting(0);
require_once '../../config/database.php';

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Get form data
$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

// Validate required fields
if (empty($email) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Email and password are required']);
    exit;
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email format']);
    exit;
}

try {
    // Get user from database
    $stmt = $pdo->prepare("SELECT ID_Utilisateur, Email, Mot_de_passe, Role, Nom FROM utilisateur WHERE Email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'Invalid email or password']);
        exit;
    }

    // Verify password - support both hashed and plain text for backward compatibility
    $passwordValid = false;
    $passwordHashInfo = password_get_info($user['Mot_de_passe']);
    $isHashed = ($passwordHashInfo['algo'] !== null && $passwordHashInfo['algo'] !== 0);
    
    if ($isHashed) {
        // Password is hashed (bcrypt, argon2, etc.)
        $passwordValid = password_verify($password, $user['Mot_de_passe']);
    } else {
        // Plain text password (legacy)
        $passwordValid = ($user['Mot_de_passe'] === $password);
    }
    
    if (!$passwordValid) {
        echo json_encode(['success' => false, 'message' => 'Invalid email or password']);
        exit;
    }

    // Start session and set user data
    session_start();
    $_SESSION['user_id'] = $user['ID_Utilisateur'];
    $_SESSION['user_email'] = $user['Email'];
    $_SESSION['user_role'] = $user['Role'];
    $_SESSION['user_name'] = $user['Nom'];

    // Generate JWT token with user name
    $token = generateToken($user['ID_Utilisateur'], $user['Role'], $user['Nom']);

    // Return user data for frontend
    echo json_encode([
        'success' => true,
        'message' => 'Login successful',
        'token' => $token,
        'user' => [
            'id' => $user['ID_Utilisateur'],
            'email' => $user['Email'],
            'role' => $user['Role'],
            'name' => $user['Nom']
        ]
    ]);

} catch (PDOException $e) {
    error_log("Login error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Login failed. Please try again.']);
}
