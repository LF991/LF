<?php
// Debug script to check authentication and user issues
require_once 'config/database.php';

header('Content-Type: text/plain');

echo "=== Debug Authentication Issue ===\n\n";

// Check all users
echo "=== All Users in Database ===\n";
$stmt = $pdo->query("SELECT ID_Utilisateur, Nom, Email, Role, Mot_de_passe, Statut FROM utilisateur");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "ID: {$row['ID_Utilisateur']}, Name: {$row['Nom']}, Email: {$row['Email']}, Role: {$row['Role']}, Password: " . (empty($row['Mot_de_passe']) ? '(empty)' : substr($row['Mot_de_passe'], 0, 20)) . ", Status: {$row['Statut']}\n";
}

echo "\n=== Users with empty passwords (likely anonymous) ===\n";
$stmt = $pdo->query("SELECT ID_Utilisateur, Nom, Email, Role FROM utilisateur WHERE Mot_de_passe = '' OR Mot_de_passe IS NULL");
$count = 0;
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "ID: {$row['ID_Utilisateur']}, Name: {$row['Nom']}, Email: {$row['Email']}, Role: {$row['Role']}\n";
    $count++;
}
if ($count === 0) {
    echo "None found\n";
}

echo "\n=== Test Token Generation ===\n";
$testToken = generateToken(1, 'Client', 'Test User');
echo "Generated token: $testToken\n";

$decoded = verifyToken($testToken);
echo "Token verification result: " . print_r($decoded, true) . "\n";

echo "\n=== Check Orders with NULL user IDs ===\n";
$stmt = $pdo->query("SELECT ID_Commande, ID_Utilisateur, Statut, Prix_Total FROM commande WHERE ID_Utilisateur IS NULL OR ID_Utilisateur = 0");
$count = 0;
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "Order ID: {$row['ID_Commande']}, User ID: {$row['ID_Utilisateur']}, Status: {$row['Statut']}, Total: {$row['Prix_Total']}\n";
    $count++;
}
if ($count === 0) {
    echo "None found\n";
}

echo "\n=== Delivery records ===\n";
$stmt = $pdo->query("SELECT l.ID_Livraison, l.ID_Commande, l.ID_Livreur, l.Statut_Livraison, c.Statut as Commande_Statut FROM livraison l JOIN commande c ON l.ID_Commande = c.ID_Commande");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "Delivery ID: {$row['ID_Livraison']}, Order ID: {$row['ID_Commande']}, Livreur ID: {$row['ID_Livreur']}, Delivery Status: {$row['Statut_Livraison']}, Order Status: {$row['Commande_Statut']}\n";
}
?>
