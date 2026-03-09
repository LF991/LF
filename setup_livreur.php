<?php
require_once 'config/database.php';

header('Content-Type: application/json');

$pdo = getDB();

// Check existing users
$stmt = $pdo->query("SELECT ID_Utilisateur, Nom, Email, Role FROM utilisateur WHERE Role = 'Livreur'");
$livreurs = $stmt->fetchAll();

echo "Users with role 'Livreur':\n";
print_r($livreurs);

// Check existing livreur records
$stmt = $pdo->query("SELECT * FROM livreur");
$livreurRecords = $stmt->fetchAll();

echo "\nRecords in livreur table:\n";
print_r($livreurRecords);

// Try to fix: make sure user ID 2 has a livreur record
echo "\n\nAttempting to fix livreur access...\n";

try {
    // Update password for livreur user (plain text for testing)
    $stmt = $pdo->prepare("UPDATE utilisateur SET Mot_de_passe = ? WHERE Email = 'livreur@supermarche.com'");
    $password = password_hash('livreur123', PASSWORD_DEFAULT);
    $stmt->execute([$password]);
    echo "Password updated for livreur@supermarche.com\n";
    
    // Ensure livreur record exists
    $stmt = $pdo->prepare("INSERT IGNORE INTO livreur (ID_Utilisateur, Statut_Disponibilite, Vehicule, Capacite_Max, Note) VALUES (2, 'Disponible', 'Vélo électrique', 20.00, 5.00)");
    $stmt->execute();
    echo "Livreur record created/updated for user ID 2\n";
    
    echo "\nSetup complete! Login with:\n";
    echo "Email: livreur@supermarche.com\n";
    echo "Password: livreur123\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
