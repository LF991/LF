<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'supermarcher_online');
define('DB_USER', 'root'); // Change this to your MySQL username
define('DB_PASS', ''); // Change this to your MySQL password

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}

// Function to get database connection
function getDB() {
    global $pdo;
    return $pdo;
}

// Function to sanitize input
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// Function to generate JWT-like token (simple implementation)
function generateToken($userId, $role, $name = '') {
    $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
    $payload = json_encode([
        'user_id' => $userId,
        'role' => $role,
        'name' => $name,
        'iat' => time(),
        'exp' => time() + (24 * 60 * 60) // 24 hours
    ]);

    $headerEncoded = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
    $payloadEncoded = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));

    $signature = hash_hmac('sha256', $headerEncoded . "." . $payloadEncoded, 'your-secret-key', true);
    $signatureEncoded = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

    return $headerEncoded . "." . $payloadEncoded . "." . $signatureEncoded;
}

// Function to verify token
function verifyToken($token) {
    $parts = explode('.', $token);
    if (count($parts) !== 3) {
        error_log("Token verification failed: invalid token format");
        return false;
    }

    $header = $parts[0];
    $payload = $parts[1];
    $signature = $parts[2];

    $expectedSignature = hash_hmac('sha256', $header . "." . $payload, 'your-secret-key', true);
    $expectedSignatureEncoded = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($expectedSignature));

    if ($signature !== $expectedSignatureEncoded) {
        error_log("Token verification failed: signature mismatch");
        error_log("Expected: " . $expectedSignatureEncoded);
        error_log("Got: " . $signature);
        return false;
    }

    $payloadDecoded = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $payload)), true);
    if ($payloadDecoded['exp'] < time()) {
        error_log("Token verification failed: token expired");
        return false;
    }

    return $payloadDecoded;
}

// Function to get current user from token
function getCurrentUser() {
    $authHeader = null;
    
    // Try getallheaders() first (works on most servers including XAMPP)
    $headers = getallheaders();
    if ($headers) {
        // Case-insensitive search for Authorization header
        foreach ($headers as $key => $value) {
            if (strtolower($key) === 'authorization') {
                $authHeader = $value;
                break;
            }
        }
    }
    
    // If getallheaders doesn't work or didn't find the header, try $_SERVER
    if (!$authHeader) {
        // Try different header name variations
        foreach ($_SERVER as $key => $value) {
            if (strtolower($key) === 'http_authorization' || strtolower($key) === 'authorization') {
                $authHeader = $value;
                break;
            }
        }
    }
    
    if (!$authHeader) return null;

    if (strpos($authHeader, 'Bearer ') !== 0 && strpos($authHeader, 'bearer ') !== 0) return null;

    $token = substr($authHeader, 7);
    return verifyToken($token);
}

// Function to check if user has role
function hasRole($requiredRole) {
    $user = getCurrentUser();
    if (!$user) return false;
    return $user['role'] === $requiredRole;
}

// Function to require authentication
function requireAuth() {
    $user = getCurrentUser();
    if (!$user) {
        http_response_code(401);
        echo json_encode(['error' => 'Authentication required']);
        exit;
    }
    return $user;
}

// Function to require specific role
function requireRole($role) {
    $user = requireAuth();
    if ($user['role'] !== $role) {
        http_response_code(403);
        echo json_encode(['error' => 'Insufficient permissions']);
        exit;
    }
    return $user;
}
?>
