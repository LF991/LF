<?php
/**
 * Fix Script: Update product status based on stock
 * - Products with stock > 0 will be set to 'Disponible'
 * - Products with stock <= 0 will be set to 'Indisponible'
 */

require_once 'config/database.php';

header('Content-Type: application/json');

try {
    $pdo = getDB();
    
    // Update products with stock > 0 to 'Disponible'
    $stmt1 = $pdo->prepare("UPDATE produit SET Statut = 'Disponible' WHERE Stock > 0");
    $stmt1->execute();
    $updated_to_disponible = $stmt1->rowCount();
    
    // Update products with stock <= 0 to 'Indisponible'
    $stmt2 = $pdo->prepare("UPDATE produit SET Statut = 'Indisponible' WHERE Stock <= 0");
    $stmt2->execute();
    $updated_to_indisponible = $stmt2->rowCount();
    
    echo json_encode([
        'success' => true,
        'message' => 'Product statuses updated successfully',
        'updated_to_disponible' => $updated_to_disponible,
        'updated_to_indisponible' => $updated_to_indisponible,
        'total_updated' => $updated_to_disponible + $updated_to_indisponible
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error updating product status: ' . $e->getMessage()
    ]);
}
?>

