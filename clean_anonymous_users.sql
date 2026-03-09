-- Clean up anonymous/guest users and related data
-- Run this in phpMyAdmin or MySQL

-- First, find the anonymous user
SELECT ID_Utilisateur, Nom, Email, Role FROM utilisateur WHERE Email = 'guest@supermarche.com' OR Nom LIKE '%Anonyme%';

-- Get the anonymous user ID if exists
-- Replace '1' with the actual ID from the query above
SET @anonymous_user_id = 1;

-- Delete cart items for anonymous user (if any)
-- DELETE FROM panier WHERE ID_Utilisateur = @anonymous_user_id;

-- Delete orders for anonymous user (if any)  
-- DELETE FROM commande_produit WHERE ID_Commande IN (SELECT ID_Commande FROM commande WHERE ID_Utilisateur = @anonymous_user_id);
-- DELETE FROM livraison WHERE ID_Commande IN (SELECT ID_Commande FROM commande WHERE ID_Utilisateur = @anonymous_user_id);
-- DELETE FROM commande WHERE ID_Utilisateur = @anonymous_user_id;

-- Finally delete the anonymous user
-- DELETE FROM utilisateur WHERE ID_Utilisateur = @anonymous_user_id;

-- Or simply delete all anonymous users:
-- DELETE FROM utilisateur WHERE Email = 'guest@supermarche.com' OR Nom LIKE '%Anonyme%';
