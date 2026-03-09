<?php
require_once '../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

header('Content-Type: application/json');

// Get form data
$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';
$role = $_POST['role'] ?? '';
$name = $_POST['name'] ?? '';
$address = $_POST['address'] ?? '';
$phone = $_POST['phone'] ?? '';

// Validate required fields
if (empty($email) || empty($password) || empty($role) || empty($name)) {
    echo json_encode(['success' => false, 'message' => 'All required fields must be filled']);
    exit;
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email format']);
    exit;
}

// Validate and map role
if ($role === 'client') {
    $role = 'Client';
} elseif ($role === 'delivery_person') {
    $role = 'Livreur';
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid role selected']);
    exit;
}

try {
    // Check if email already exists
    $stmt = $pdo->prepare("SELECT ID_Utilisateur FROM utilisateur WHERE Email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Email already registered']);
        exit;
    }

    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Insert new user
    $stmt = $pdo->prepare("INSERT INTO utilisateur (Nom, Email, Mot_de_passe, Role, Adresse, Telephone, Date_Inscription, Statut) VALUES (?, ?, ?, ?, ?, ?, NOW(), 'Actif')");
    $stmt->execute([$name, $email, $hashedPassword, $role, $address, $phone]);

    // Start session and log user in
    session_start();
    $userId = $pdo->lastInsertId();
    $_SESSION['user_id'] = $userId;
    $_SESSION['user_email'] = $email;
    $_SESSION['user_role'] = $role;
    $_SESSION['user_name'] = $name;

    // Generate JWT token for auto-login
    $token = generateToken($userId, $role, $name);

    // If user is a Livreur, also create a record in the livreur table
    if ($role === 'Livreur') {
        $stmt = $pdo->prepare("
            INSERT INTO livreur (ID_Utilisateur, Statut_Disponibilite, Vehicule, Capacite_Max, Note, Position_GPS_Actuelle) 
            VALUES (?, 'Disponible', 'Vélo électrique', 20.00, 5.00, NULL)
        ");
        $stmt->execute([$userId]);
    }

    // Return user data and token for frontend
    echo json_encode([
        'success' => true,
        'message' => 'Registration successful',
        'token' => $token,
        'user' => [
            'id' => $userId,
            'email' => $email,
            'role' => $role,
            'name' => $name
        ]
    ]);

} catch (PDOException $e) {
    error_log("Registration error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Registration failed. Please try again.']);
}
?>
