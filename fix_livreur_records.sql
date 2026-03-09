-- Script to add missing livreur records for existing users
-- This will add livreur entries for users who have Role='Livreur' but no entry in livreur table

-- First, let's see which Livreur users don't have a livreur record
SELECT u.ID_Utilisateur, u.Nom, u.Email, u.Role
FROM utilisateur u
LEFT JOIN livreur l ON u.ID_Utilisateur = l.ID_Utilisateur
WHERE u.Role = 'Livreur' AND l.ID_Livreur IS NULL;

-- Insert missing livreur records
INSERT INTO livreur (ID_Utilisateur, Statut_Disponibilite, Vehicule, Capacite_Max, Note, Position_GPS_Actuelle)
SELECT u.ID_Utilisateur, 'Disponible', 'Vélo électrique', 20.00, 5.00, NULL
FROM utilisateur u
LEFT JOIN livreur l ON u.ID_Utilisateur = l.ID_Utilisateur
WHERE u.Role = 'Livreur' AND l.ID_Livreur IS NULL;

-- Verify the results
SELECT l.ID_Livreur, l.ID_Utilisateur, u.Nom, u.Email, l.Statut_Disponibilite
FROM livreur l
JOIN utilisateur u ON l.ID_Utilisateur = u.ID_Utilisateur
WHERE u.Role = 'Livreur';
