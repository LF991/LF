<?php
require_once '../../config/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Note: Authentication temporarily disabled for admin dashboard access
// In production, uncomment the following line:
// $user = requireRole('Admin');

$method = $_SERVER['REQUEST_METHOD'];

try {
    $pdo = getDB();

    switch ($method) {
        case 'GET':
            // Get all users or single user
            if (isset($_GET['id'])) {
                $stmt = $pdo->prepare("SELECT ID_Utilisateur, Nom, Email, Role, Adresse, Telephone, Date_Inscription, Statut FROM utilisateur WHERE ID_Utilisateur = ?");
                $stmt->execute([sanitize($_GET['id'])]);
                $user = $stmt->fetch();

                if ($user) {
                    echo json_encode(['success' => true, 'user' => $user]);
                } else {
                    http_response_code(404);
                    echo json_encode(['error' => 'Utilisateur non trouvé']);
                }
            } else {
                // Get all users with optional filters
                $query = "SELECT ID_Utilisateur, Nom, Email, Role, Adresse, Telephone, Date_Inscription, Statut FROM utilisateur WHERE 1=1";
                $params = [];

                if (isset($_GET['role']) && !empty($_GET['role'])) {
                    $query .= " AND Role = ?";
                    $params[] = sanitize($_GET['role']);
                }

                if (isset($_GET['status']) && !empty($_GET['status'])) {
                    $query .= " AND Statut = ?";
                    $params[] = sanitize($_GET['status']);
                }

                $query .= " ORDER BY Date_Inscription DESC";

                $stmt = $pdo->prepare($query);
                $stmt->execute($params);
                $users = $stmt->fetchAll();

                echo json_encode(['success' => true, 'users' => $users]);
            }
            break;

        case 'POST':
            // Create new user
            $data = json_decode(file_get_contents('php://input'), true);

            if (!$data) {
                http_response_code(400);
                echo json_encode(['error' => 'Données invalides']);
                exit;
            }

            // Hash password
            $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);

            $stmt = $pdo->prepare("INSERT INTO utilisateur (Nom, Email, Mot_de_passe, Role, Adresse, Telephone, Statut, Date_Inscription)
                                  VALUES (?, ?, ?, ?, ?, ?, 'Actif', NOW())");

            $stmt->execute([
                sanitize($data['name']),
                sanitize($data['email']),
                $hashedPassword,
                sanitize($data['role']),
                sanitize($data['address'] ?? ''),
                sanitize($data['phone'] ?? '')
            ]);

            $userId = $pdo->lastInsertId();

            // If user is a Livreur, also create a record in the livreur table
            if ($data['role'] === 'Livreur') {
                $stmt = $pdo->prepare("
                    INSERT INTO livreur (ID_Utilisateur, Statut_Disponibilite, Vehicule, Capacite_Max, Note, Position_GPS_Actuelle) 
                    VALUES (?, 'Disponible', 'Vélo électrique', 20.00, 5.00, NULL)
                ");
                $stmt->execute([$userId]);
            }

            echo json_encode([
                'success' => true,
                'message' => 'Utilisateur créé avec succès',
                'user_id' => $userId
            ]);
            break;

        case 'PUT':
            // Update user
            if (!isset($_GET['id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'ID de l\'utilisateur requis']);
                exit;
            }

            $data = json_decode(file_get_contents('php://input'), true);

            if (!$data) {
                http_response_code(400);
                echo json_encode(['error' => 'Données invalides']);
                exit;
            }

            $updateFields = [];
            $params = [];

            if (isset($data['name'])) {
                $updateFields[] = "Nom = ?";
                $params[] = sanitize($data['name']);
            }
            if (isset($data['email'])) {
                $updateFields[] = "Email = ?";
                $params[] = sanitize($data['email']);
            }
            if (isset($data['role'])) {
                $updateFields[] = "Role = ?";
                $params[] = sanitize($data['role']);
            }
            if (isset($data['address'])) {
                $updateFields[] = "Adresse = ?";
                $params[] = sanitize($data['address']);
            }
            if (isset($data['phone'])) {
                $updateFields[] = "Telephone = ?";
                $params[] = sanitize($data['phone']);
            }
            if (isset($data['status'])) {
                $updateFields[] = "Statut = ?";
                $params[] = sanitize($data['status']);
            }
            if (isset($data['password']) && !empty($data['password'])) {
                $updateFields[] = "Mot_de_passe = ?";
                $params[] = password_hash($data['password'], PASSWORD_DEFAULT);
            }

            if (empty($updateFields)) {
                http_response_code(400);
                echo json_encode(['error' => 'Aucun champ à mettre à jour']);
                exit;
            }

            $params[] = sanitize($_GET['id']);
            $query = "UPDATE utilisateur SET " . implode(', ', $updateFields) . " WHERE ID_Utilisateur = ?";

            $stmt = $pdo->prepare($query);
            $stmt->execute($params);

            // If role is changed to Livreur, create livreur record if not exists
            if (isset($data['role']) && $data['role'] === 'Livreur') {
                $checkStmt = $pdo->prepare("SELECT ID_Livreur FROM livreur WHERE ID_Utilisateur = ?");
                $checkStmt->execute([sanitize($_GET['id'])]);
                if (!$checkStmt->fetch()) {
                    $stmt = $pdo->prepare("
                        INSERT INTO livreur (ID_Utilisateur, Statut_Disponibilite, Vehicule, Capacite_Max, Note, Position_GPS_Actuelle) 
                        VALUES (?, 'Disponible', 'Vélo électrique', 20.00, 5.00, NULL)
                    ");
                    $stmt->execute([sanitize($_GET['id'])]);
                }
            }

            echo json_encode(['success' => true, 'message' => 'Utilisateur mis à jour avec succès']);
            break;

        case 'DELETE':
            // Delete user
            if (!isset($_GET['id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'ID de l\'utilisateur requis']);
                exit;
            }

            $stmt = $pdo->prepare("DELETE FROM utilisateur WHERE ID_Utilisateur = ?");
            $stmt->execute([sanitize($_GET['id'])]);

            echo json_encode(['success' => true, 'message' => 'Utilisateur supprimé avec succès']);
            break;

        case 'OPTIONS':
            // Handle preflight requests
            http_response_code(200);
            break;

        default:
            http_response_code(405);
            echo json_encode(['error' => 'Méthode non autorisée']);
            break;
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur serveur: ' . $e->getMessage()]);
}
?>
