<?php
/**
 * Script de réinitialisation des livraisons
 * Exécutez ce fichier dans votre navigateur pour réinitialiser toutes les livraisons
 * URL: http://localhost/castlemarket/reset_deliveries.php
 */

require_once 'config/database.php';

header('Content-Type: text/html; charset=utf-8');

echo "<h1>Réinitialisation des livraisons</h1>";

try {
    $pdo = getDB();
    
    // Commencer la transaction
    $pdo->beginTransaction();
    
    // 1. Supprimer toutes les livraisons
    $stmt = $pdo->prepare("DELETE FROM livraison");
    $stmt->execute();
    $deletedDeliveries = $stmt->rowCount();
    echo "<p>✓ $deletedDeliveries livraisons supprimées</p>";
    
    // 2. Remettre tous les livreurs en disponible
    $stmt = $pdo->prepare("UPDATE livreur SET Statut_Disponibilite = 'Disponible'");
    $stmt->execute();
    $updatedLivreurs = $stmt->rowCount();
    echo "<p>✓ $updatedLivreurs livreurs remis en disponible</p>";
    
    // 3. Remettre toutes les commandes en attente
    $stmt = $pdo->prepare("UPDATE commande SET Statut = 'En attente' WHERE Statut IN ('En préparation', 'Prête', 'Confirmée', 'En livraison')");
    $stmt->execute();
    $updatedOrders = $stmt->rowCount();
    echo "<p>✓ $updatedOrders commandes remises en attente</p>";
    
    // Valider la transaction
    $pdo->commit();
    
    echo "<h2 style='color: green;'>✓ Réinitialisation terminée avec succès!</h2>";
    echo "<p>Maintenant, tous les livreurs pourront voir toutes les commandes en attente.</p>";
    echo "<p><a href='frontend/livreur/dashboard.html'>Aller au dashboard livreur</a></p>";
    
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "<h2 style='color: red;'>Erreur: " . $e->getMessage() . "</h2>";
}
?>
