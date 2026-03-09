<?php
/**
 * Debug script to check livreur data and API response
 * Run this in browser: http://localhost/castlemarket/debug_livreurs.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/database.php';

header('Content-Type: text/html; charset=utf-8');

echo "<h1>Debug: Livreurs Data</h1>";

try {
    $pdo = getDB();
    
    if (!$pdo) {
        echo "<p style='color:red'>Database connection FAILED</p>";
        exit;
    }
    
    echo "<p style='color:green'>Database connection: OK</p>";
    
    // Check livreur table
    echo "<h2>1. Checking livreur table:</h2>";
    $stmt = $pdo->query("SELECT * FROM livreur");
    $livreurs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($livreurs)) {
        echo "<p style='color:red'>⚠️ NO records in livreur table!</p>";
    } else {
        echo "<p>Found " . count($livreurs) . " livreur(s)</p>";
        echo "<pre>" . print_r($livreurs, true) . "</pre>";
    }
    
    // Check utilisateur table for Livreur role
    echo "<h2>2. Checking utilisateur table (Role=Livreur):</h2>";
    $stmt = $pdo->query("SELECT * FROM utilisateur WHERE Role = 'Livreur'");
    $utilisateurs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($utilisateurs)) {
        echo "<p style='color:red'>⚠️ NO users with Role='Livreur'!</p>";
    } else {
        echo "<p>Found " . count($utilisateurs) . " utilisateur(s) with Role='Livreur'</p>";
        echo "<pre>" . print_r($utilisateurs, true) . "</pre>";
    }
    
    // Check the exact query used in backend/admin/delivery.php
    echo "<h2>3. Testing the API query (same as backend/admin/delivery.php):</h2>";
    $stmt = $pdo->query("
        SELECT l.*, u.Nom, u.Email, u.Telephone, u.Adresse 
        FROM livreur l 
        LEFT JOIN utilisateur u ON l.ID_Utilisateur = u.ID_Utilisateur 
        ORDER BY u.Nom
    ");
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>Query returned " . count($results) . " row(s)</p>";
    echo "<pre>" . print_r($results, true) . "</pre>";
    
    // Test JSON response
    echo "<h2>4. Testing JSON response format:</h2>";
    
    // Rename columns for frontend compatibility (same as backend code)
    foreach ($results as &$delivery) {
        $delivery['Nom'] = $delivery['Nom'] ?? '';
        $delivery['Email'] = $delivery['Email'] ?? '';
        $delivery['Telephone'] = $delivery['Telephone'] ?? '';
        $delivery['Statut'] = $delivery['Statut_Disponibilite'] ?? 'Disponible';
    }
    
    $jsonResponse = json_encode(['success' => true, 'deliveries' => $results], JSON_PRETTY_PRINT);
    echo "<pre>" . $jsonResponse . "</pre>";
    
    // Check if frontend can access the API
    echo "<h2>5. Testing API endpoint directly:</h2>";
    echo "<p>Check if <a href='/castlemarket/backend/admin/delivery.php' target='_blank'>backend/admin/delivery.php</a> returns data</p>";
    
} catch (Exception $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}
?>

