<?php
require_once 'config/database.php';

try {
    // Update admin password to plain text 'admin'
    $stmt = $pdo->prepare("UPDATE utilisateur SET Mot_de_passe = ? WHERE Email = ?");
    $stmt->execute(['admin', 'admin@supermarche.com']);
    
    echo "Admin password updated to 'admin' (plain text).";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
