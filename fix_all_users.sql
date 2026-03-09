-- Script to fix all user passwords for testing
-- Run this in phpMyAdmin or MySQL

-- IMPORTANT: This updates passwords to plain text for backward compatibility with the login system

-- Update Admin user
UPDATE utilisateur 
SET Mot_de_passe = 'admin123' 
WHERE Email = 'admin@supermarche.com';

-- Update or create Client user
INSERT INTO utilisateur (ID_Utilisateur, Nom, Email, Mot_de_passe, Role, Adresse, Telephone, Date_Inscription, Statut)
VALUES (3, 'Jean Client', 'client@test.com', 'client123', 'Client', '123 Rue de Paris, 75001 Paris', '+33612345678', NOW(), 'Actif')
ON DUPLICATE KEY UPDATE 
    Nom = 'Jean Client',
    Role = 'Client',
    Mot_de_passe = 'client123',
    Statut = 'Actif';

-- Update Livreur user  
UPDATE utilisateur 
SET Mot_de_passe = 'livreur123' 
WHERE Email = 'livreur@supermarche.com';

-- Verify all users
SELECT ID_Utilisateur, Nom, Email, Role, Statut, LENGTH(Mot_de_passe) as pwd_length
FROM utilisateur;
