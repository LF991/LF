<?php
require_once 'config/database.php';

$stmt = $pdo->query('SELECT ID_Utilisateur, Email, Mot_de_passe, Role, Nom FROM utilisateur');
echo "Users in database:\n";
echo "==================\n";
foreach($stmt as $row) {
    echo "ID: " . $row['ID_Utilisateur'] . "\n";
    echo "Email: " . $row['Email'] . "\n";
    echo "Password: " . $row['Mot_de_passe'] . "\n";
    echo "Role: " . $row['Role'] . "\n";
    echo "Name: " . $row['Nom'] . "\n";
    echo "------------------\n";
}
?>
