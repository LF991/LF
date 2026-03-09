-- Script to fix livreur access for testing
-- Run this in phpMyAdmin or MySQL to add/update a livreur user

-- IMPORTANT: This system uses PLAIN TEXT passwords!

-- Create a livreur user if not exists (or update existing)
INSERT INTO utilisateur (ID_Utilisateur, Nom, Email, Mot_de_passe, Role, Adresse, Telephone, Date_Inscription, Statut)
VALUES (2, 'Jean Livreur', 'livreur@supermarche.com', 'livreur123', 'Livreur', '10 Rue du Livreur, 75010 Paris', '+33698765432', NOW(), 'Actif')
ON DUPLICATE KEY UPDATE 
    Nom = 'Jean Livreur',
    Role = 'Livreur',
    Mot_de_passe = 'livreur123',
    Statut = 'Actif';

-- Create livreur record (not required anymore but kept for compatibility)
INSERT IGNORE INTO livreur (ID_Livreur, ID_Utilisateur, Statut_Disponibilite, Vehicule, Capacite_Max, Note)
VALUES (2, 2, 'Disponible', 'Vélo électrique', 20.00, 5.00);

-- Verify the setup
SELECT u.ID_Utilisateur, u.Nom, u.Email, u.Role, l.ID_Livreur, l.Statut_Disponibilite
FROM utilisateur u
LEFT JOIN livreur l ON u.ID_Utilisateur = l.ID_Utilisateur
WHERE u.Role = 'Livreur';
