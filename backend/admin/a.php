<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../../config/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    $pdo = getDB();
    
    // Verify connection
    if (!$pdo) {
        throw new Exception('Database connection failed');
    }

    switch ($method) {
        case 'GET':
            // Get all delivery personnel or single delivery person
            if (isset($_GET['id'])) {
                $stmt = $pdo->prepare("
                    SELECT l.*, u.Nom, u.Email, u.Telephone, u.Adresse 
                    FROM livreur l 
                    LEFT JOIN utilisateur u ON l.ID_Utilisateur = u.ID_Utilisateur 
                    WHERE l.ID_Livreur = ?
                ");
                $stmt->execute([$_GET['id']]);
                $delivery = $stmt->fetch();

                if ($delivery) {
                    $delivery['Nom'] = $delivery['Nom'] ?? '';
                    $delivery['Email'] = $delivery['Email'] ?? '';
                    $delivery['Telephone'] = $delivery['Telephone'] ?? '';
                    $delivery['Statut'] = $delivery['Statut_Disponibilite'] ?? 'Disponible';
                    echo json_encode(['success' => true, 'delivery' => $delivery]);
                } else {
                    http_response_code(404);
                    echo json_encode(['error' => 'Livreur non trouvé']);
                }
            } else {
                // Get all delivery personnel with user info
                $stmt = $pdo->query("
                    SELECT l.*, u.Nom, u.Email, u.Telephone, u.Adresse 
                    FROM livreur l 
                    LEFT JOIN utilisateur u ON l.ID_Utilisateur = u.ID_Utilisateur 
                    ORDER BY u.Nom
                ");
                $deliveries = $stmt->fetchAll();

                // Rename columns for frontend compatibility
                foreach ($deliveries as &$delivery) {
                    $delivery['Nom'] = $delivery['Nom'] ?? '';
                    $delivery['Email'] = $delivery['Email'] ?? '';
                    $delivery['Telephone'] = $delivery['Telephone'] ?? '';
                    $delivery['Statut'] = $delivery['Statut_Disponibilite'] ?? 'Disponible';
                }

                echo json_encode(['success' => true, 'deliveries' => $deliveries]);
            }
            break;

        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);

            if (!$data) {
                http_response_code(400);
                echo json_encode(['error' => 'Données invalides']);
                exit;
            }

            $hashedPassword = password_hash('password123', PASSWORD_DEFAULT);
            
            $stmt = $pdo->prepare("INSERT INTO utilisateur (Nom, Email, Mot_de_passe, Role, Telephone, Statut, Date_Inscription)
                                  VALUES (?, ?, ?, 'Livreur', ?, 'Actif', NOW())");

            $stmt->execute([
                $data['name'] ?? '',
                $data['email'] ?? '',
                $hashedPassword,
                $data['phone'] ?? ''
            ]);

            $userId = $pdo->lastInsertId();

            $stmt = $pdo->prepare("INSERT INTO livreur (ID_Utilisateur, Statut_Disponibilite)
                                  VALUES (?, 'Disponible')");

            $stmt->execute([$userId]);

            $deliveryId = $pdo->lastInsertId();

            echo json_encode([
                'success' => true,
                'message' => 'Livreur créé avec succès',
                'delivery_id' => $deliveryId
            ]);
            break;

        case 'PUT':
            if (!isset($_GET['id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'ID du livreur requis']);
                exit;
            }

            $data = json_decode(file_get_contents('php://input'), true);

            if (!$data) {
                http_response_code(400);
                echo json_encode(['error' => 'Données invalides']);
                exit;
            }

            $stmt = $pdo->prepare("SELECT ID_Utilisateur FROM livreur WHERE ID_Livreur = ?");
            $stmt->execute([$_GET['id']]);
            $livreur = $stmt->fetch();

            if ($livreur) {
                $stmt = $pdo->prepare("UPDATE utilisateur SET Nom = ?, Email = ?, Telephone = ? WHERE ID_Utilisateur = ?");
                $stmt->execute([
                    $data['name'] ?? '',
                    $data['email'] ?? '',
                    $data['phone'] ?? '',
                    $livreur['ID_Utilisateur']
                ]);

                $stmt = $pdo->prepare("UPDATE livreur SET Statut_Disponibilite = ? WHERE ID_Livreur = ?");
                $stmt->execute([
                    $data['status'] ?? 'Disponible',
                    $_GET['id']
                ]);
            }

            echo json_encode(['success' => true, 'message' => 'Livreur mis à jour avec succès']);
            break;

        case 'DELETE':
            if (!isset($_GET['id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'ID du livreur requis']);
                exit;
            }

            $stmt = $pdo->prepare("DELETE FROM livreur WHERE ID_Livreur = ?");
            $stmt->execute([$_GET['id']]);

            echo json_encode(['success' => true, 'message' => 'Livreur supprimé avec succès']);
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
