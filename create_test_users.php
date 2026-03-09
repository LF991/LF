<?php
require_once 'config/database.php';

header('Content-Type: application/json');

// Create test users with plain text passwords for testing
$users = [
    [
        'id' => 1,
        'name' => 'Admin Principal',
        'email' => 'admin@supermarche.com',
        'password' => 'admin',
        'role' => 'Admin',
        'address' => 'Admin Address',
        'phone' => '+1234567890'
    ],
    [
        'id' => 2,
        'name' => 'Jean Livreur',
        'email' => 'livreur@supermarche.com',
        'password' => 'livreur123',
        'role' => 'Livreur',
        'address' => '10 Rue du Livreur, 75010 Paris',
        'phone' => '+33698765432'
    ],
    [
        'id' => 3,
        'name' => 'Jean Client',
        'email' => 'client@test.com',
        'password' => 'client123',
        'role' => 'Client',
        'address' => '123 Rue de Paris, 75001 Paris',
        'phone' => '+33612345678'
    ]
];

try {
    $results = [];
    
    foreach ($users as $user) {
        // Check if user exists
        $stmt = $pdo->prepare("SELECT ID_Utilisateur FROM utilisateur WHERE Email = ?");
        $stmt->execute([$user['email']]);
        $exists = $stmt->fetch();
        
        if ($exists) {
            // Update existing user
            $stmt = $pdo->prepare("
                UPDATE utilisateur 
                SET Nom = ?, Mot_de_passe = ?, Role = ?, Adresse = ?, Telephone = ?, Statut = 'Actif'
                WHERE Email = ?
            ");
            $stmt->execute([
                $user['name'], 
                $user['password'], 
                $user['role'], 
                $user['address'], 
                $user['phone'],
                $user['email']
            ]);
            $results[] = "Updated user: " . $user['email'];
        } else {
            // Insert new user
            $stmt = $pdo->prepare("
                INSERT INTO utilisateur (ID_Utilisateur, Nom, Email, Mot_de_passe, Role, Adresse, Telephone, Statut)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'Actif')
            ");
            $stmt->execute([
                $user['id'],
                $user['name'],
                $user['email'],
                $user['password'],
                $user['role'],
                $user['address'],
                $user['phone']
            ]);
            $results[] = "Created user: " . $user['email'];
        }
        
        // If livreur, also create/update livreur table
        if ($user['role'] === 'Livreur') {
            $stmt = $pdo->prepare("SELECT ID_Livreur FROM livreur WHERE ID_Utilisateur = ?");
            $stmt->execute([$user['id']]);
            $livreurExists = $stmt->fetch();
            
            if (!$livreurExists) {
                $stmt = $pdo->prepare("
                    INSERT INTO livreur (ID_Livreur, ID_Utilisateur, Statut_Disponibilite, Vehicule, Capacite_Max, Note)
                    VALUES (?, ?, 'Disponible', 'Vélo électrique', 20.00, 5.00)
                ");
                $stmt->execute([$user['id'], $user['id']]);
                $results[] = "Created livreur record for: " . $user['email'];
            }
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Test users created/updated successfully',
        'results' => $results,
        'test_credentials' => [
            'admin@supermarche.com / admin',
            'livreur@supermarche.com / livreur123',
            'client@test.com / client123'
        ]
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
