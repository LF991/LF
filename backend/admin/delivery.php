<?php
// Activer l'affichage des erreurs pour le débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../../config/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Gérer la requête OPTIONS (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Authentification : requireAuth() retourne l'utilisateur ou génère une erreur 401
$user = requireAuth();

// Vérifier le rôle administrateur
if ($user['role'] !== 'Admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Accès refusé. Vous devez être administrateur.']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$pdo = getDB();

try {
    switch ($method) {
        case 'GET':
            if (isset($_GET['id'])) {
                // Récupérer un livreur spécifique
                $id = (int)$_GET['id'];
                $stmt = $pdo->prepare("
                    SELECT 
                        l.ID_Livreur,
                        u.Nom,
                        u.Email,
                        u.Telephone,
                        l.Statut_Disponibilite AS Statut,
                        l.Vehicule,
                        l.Note,
                        l.Capacite_Max
                    FROM livreur l
                    INNER JOIN utilisateur u ON l.ID_Utilisateur = u.ID_Utilisateur
                    WHERE l.ID_Livreur = ?
                ");
                $stmt->execute([$id]);
                $delivery = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$delivery) {
                    http_response_code(404);
                    echo json_encode(['error' => 'Livreur non trouvé']);
                    exit;
                }
                echo json_encode(['success' => true, 'delivery' => $delivery]);
            } else {
                // Liste de tous les livreurs (avec seulement les champs nécessaires pour l'affichage)
                $stmt = $pdo->query("
                    SELECT 
                        l.ID_Livreur,
                        u.Nom,
                        l.Statut_Disponibilite AS Statut
                    FROM utilisateur u
                    INNER JOIN livreur l ON u.ID_Utilisateur = l.ID_Utilisateur
                    WHERE u.Role = 'Livreur'
                    ORDER BY l.ID_Livreur DESC
                ");
                $deliveries = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode(['success' => true, 'deliveries' => $deliveries]);
            }
            break;

        case 'POST':
            // Créer un nouveau livreur
            $data = json_decode(file_get_contents('php://input'), true);
            if (!$data) {
                http_response_code(400);
                echo json_encode(['error' => 'Données invalides']);
                exit;
            }

            // Validation des champs requis
            if (empty($data['name']) || empty($data['email']) || empty($data['phone'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Nom, email et téléphone sont requis']);
                exit;
            }

            // Générer un mot de passe aléatoire (ou utiliser un mot de passe par défaut)
            $tempPassword = bin2hex(random_bytes(4)); // 8 caractères
            $hashedPassword = password_hash($tempPassword, PASSWORD_DEFAULT);

            $pdo->beginTransaction();

            // Insérer dans utilisateur
            $stmt = $pdo->prepare("
                INSERT INTO utilisateur (Nom, Email, Telephone, Mot_de_Passe, Role, Statut)
                VALUES (?, ?, ?, ?, 'Livreur', 'Actif')
            ");
            $stmt->execute([$data['name'], $data['email'], $data['phone'], $hashedPassword]);
            $userId = $pdo->lastInsertId();

            // Insérer dans livreur
            $stmt = $pdo->prepare("
                INSERT INTO livreur (ID_Utilisateur, Statut_Disponibilite, Vehicule, Capacite_Max, Note)
                VALUES (?, ?, ?, ?, ?)
            ");
            $vehicule = $data['vehicule'] ?? 'Non spécifié';
            $capacite = $data['capacite'] ?? 20.0;
            $note = $data['note'] ?? 5.0;
            $statut = $data['status'] ?? 'Disponible';
            $stmt->execute([$userId, $statut, $vehicule, $capacite, $note]);
            $livreurId = $pdo->lastInsertId();

            $pdo->commit();

            echo json_encode([
                'success' => true,
                'message' => 'Livreur créé avec succès',
                'delivery_id' => $livreurId,
                'temp_password' => $tempPassword // À afficher à l'admin
            ]);
            break;

        case 'PUT':
            if (!isset($_GET['id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'ID requis']);
                exit;
            }
            $id = (int)$_GET['id'];
            $data = json_decode(file_get_contents('php://input'), true);
            if (!$data) {
                http_response_code(400);
                echo json_encode(['error' => 'Données invalides']);
                exit;
            }

            // Récupérer l'ID_Utilisateur associé
            $stmt = $pdo->prepare("SELECT ID_Utilisateur FROM livreur WHERE ID_Livreur = ?");
            $stmt->execute([$id]);
            $livreur = $stmt->fetch();
            if (!$livreur) {
                http_response_code(404);
                echo json_encode(['error' => 'Livreur non trouvé']);
                exit;
            }
            $userId = $livreur['ID_Utilisateur'];

            $pdo->beginTransaction();

            // Mise à jour utilisateur
            $updates = [];
            $params = [];
            if (isset($data['name'])) {
                $updates[] = "Nom = ?";
                $params[] = $data['name'];
            }
            if (isset($data['email'])) {
                $updates[] = "Email = ?";
                $params[] = $data['email'];
            }
            if (isset($data['phone'])) {
                $updates[] = "Telephone = ?";
                $params[] = $data['phone'];
            }
            if (!empty($updates)) {
                $params[] = $userId;
                $stmt = $pdo->prepare("UPDATE utilisateur SET " . implode(', ', $updates) . " WHERE ID_Utilisateur = ?");
                $stmt->execute($params);
            }

            // Mise à jour livreur
            $updates = [];
            $params = [];
            if (isset($data['status'])) {
                $updates[] = "Statut_Disponibilite = ?";
                $params[] = $data['status'];
            }
            if (isset($data['vehicule'])) {
                $updates[] = "Vehicule = ?";
                $params[] = $data['vehicule'];
            }
            if (isset($data['capacite'])) {
                $updates[] = "Capacite_Max = ?";
                $params[] = $data['capacite'];
            }
            if (isset($data['note'])) {
                $updates[] = "Note = ?";
                $params[] = $data['note'];
            }
            if (!empty($updates)) {
                $params[] = $id;
                $stmt = $pdo->prepare("UPDATE livreur SET " . implode(', ', $updates) . " WHERE ID_Livreur = ?");
                $stmt->execute($params);
            }

            $pdo->commit();

            echo json_encode(['success' => true, 'message' => 'Livreur mis à jour avec succès']);
            break;

        case 'DELETE':
            if (!isset($_GET['id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'ID requis']);
                exit;
            }
            $id = (int)$_GET['id'];

            // Récupérer l'ID_Utilisateur
            $stmt = $pdo->prepare("SELECT ID_Utilisateur FROM livreur WHERE ID_Livreur = ?");
            $stmt->execute([$id]);
            $livreur = $stmt->fetch();
            if (!$livreur) {
                http_response_code(404);
                echo json_encode(['error' => 'Livreur non trouvé']);
                exit;
            }
            $userId = $livreur['ID_Utilisateur'];

            $pdo->beginTransaction();

            // Supprimer les livraisons associées à ce livreur
            $stmt = $pdo->prepare("DELETE FROM livraison WHERE ID_Livreur = ?");
            $stmt->execute([$id]);

            // Supprimer le livreur
            $stmt = $pdo->prepare("DELETE FROM livreur WHERE ID_Livreur = ?");
            $stmt->execute([$id]);

            // Optionnel : supprimer l'utilisateur si on veut (attention, peut avoir d'autres liens)
            // Ici on le garde mais on pourrait le désactiver ou le supprimer
            // Pour l'exemple, on ne supprime pas l'utilisateur, on change son rôle ?
            // Ou on le laisse car il peut avoir des commandes en tant que client ?
            // Décision : on ne supprime pas l'utilisateur, on le garde mais on peut changer son rôle en 'Client' si besoin.
            // Pour l'instant, on le laisse tel quel.

            $pdo->commit();

            echo json_encode(['success' => true, 'message' => 'Livreur supprimé avec succès']);
            break;

        default:
            http_response_code(405);
            echo json_encode(['error' => 'Méthode non autorisée']);
            break;
    }
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['error' => 'Erreur serveur : ' . $e->getMessage()]);
}
?>