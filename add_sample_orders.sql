-- Script to add sample orders for testing the livreur dashboard
-- Run this in phpMyAdmin or MySQL

-- First, make sure we have products
INSERT IGNORE INTO produit (ID_Produit, Nom, Description, Prix, Stock, Categorie, Image_URL, Statut, Date_Ajout, Poids) VALUES
(1, 'Lait 1L', 'Lait entier frais', 2.50, 100, 'Produits laitiers', NULL, 'Disponible', NOW(), 1.00),
(2, 'Pain complet', 'Pain complet bio', 1.80, 50, 'Boulangerie', NULL, 'Disponible', NOW(), 0.50),
(3, 'Pommes Golden', 'Pommes golden bio, 1kg', 3.20, 75, 'Fruits', NULL, 'Disponible', NOW(), 1.00),
(4, 'Poulet fermier', 'Poulet fermier, 1.5kg', 12.90, 30, 'Viandes', NULL, 'Disponible', NOW(), 1.50),
(5, 'Eau minérale 6x1.5L', 'Pack eau minérale', 4.50, 80, 'Boissons', NULL, 'Disponible', NOW(), 9.00);

-- Create a client user if not exists
INSERT IGNORE INTO utilisateur (ID_Utilisateur, Nom, Email, Mot_de_passe, Role, Adresse, Coordonnees_GPS, Telephone, Date_Inscription, Statut) VALUES
(3, 'Jean Client', 'client@test.com', 'client123', 'Client', '123 Rue de Paris, 75001 Paris', NULL, '+33612345678', NOW(), 'Actif');

-- Create sample orders with status 'Prête' for delivery
INSERT INTO commande (ID_Commande, ID_Utilisateur, Date_Commande, Statut, Adresse_Livraison, Prix_Total, Notes, Telephone) VALUES
(1, 3, NOW(), 'Prête', '123 Rue de Paris, 75001 Paris', 15.50, 'Livrer avant 18h', '+33612345678'),
(2, 3, NOW(), 'Prête', '45 Avenue Victor Hugo, 75016 Paris', 28.90, 'Sonner à l\'interphone', '+33612345678'),
(3, 3, NOW(), 'Confirmée', '78 Boulevard Saint-Germain, 75005 Paris', 8.50, 'Au rez-de-chaussée', '+33612345678');

-- Add products to orders
INSERT INTO commande_produit (ID_Commande_Produit, ID_Commande, ID_Produit, Quantite, Prix_Unitaire) VALUES
(1, 1, 1, 2, 2.50),   -- 2x Lait
(2, 1, 2, 1, 1.80),   -- 1x Pain
(3, 1, 3, 3, 3.20),   -- 3x Pommes
(4, 2, 4, 2, 12.90),  -- 2x Poulet
(5, 2, 5, 1, 4.50),   -- 1x Eau
(6, 3, 1, 1, 2.50),   -- 1x Lait
(7, 3, 2, 2, 1.80),   -- 2x Pain
(8, 3, 3, 1, 3.20);   -- 1x Pommes

-- Verify orders
SELECT c.ID_Commande, c.Statut, c.Adresse_Livraison, c.Prix_Total, u.Nom as client_name
FROM commande c
JOIN utilisateur u ON c.ID_Utilisateur = u.ID_Utilisateur
WHERE c.Statut IN ('Prête', 'Confirmée', 'En préparation');
