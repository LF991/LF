-- Script pour réinitialiser toutes les livraisons
-- Cela permettra à tous les livreurs de voir les commandes en attente

-- 1. Supprimer toutes les livraisons existantes (les commandes redeviendront disponibles)
DELETE FROM livraison;

-- OU si vous voulez garder l'historique, juste réinitialiser les livreurs assignés:
-- UPDATE livraison SET ID_Livreur = NULL, Statut_Livraison = 'En attente', Date_Debut_Livraison = NULL WHERE Statut_Livraison != 'Livrée';

-- 2. Remettre tous les livreurs en statut Disponible
UPDATE livreur SET Statut_Disponibilite = 'Disponible';

-- 3. Remettre toutes les commandes en attente
UPDATE commande SET Statut = 'En attente' WHERE Statut IN ('En préparation', 'Prête', 'Confirmée');

-- 4. Vérifier les résultats
SELECT * FROM livreur;
SELECT * FROM livraison;
SELECT * FROM commande ORDER BY ID_Commande DESC LIMIT 10;
