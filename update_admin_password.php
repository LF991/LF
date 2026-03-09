<?php
require_once 'config/database.php';

try {
    $hashedPassword = password_hash('admin', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("UPDATE utilisateur SET Mot_de_passe = ? WHERE Email = 'admin@supermarche.com'");
    $stmt->execute([$hashedPassword]);
    echo "Password updated successfully. Hash: " . $hashedPassword;
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
