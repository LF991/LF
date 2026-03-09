-- Script to find and remove anonymous/guest users
-- Run this in phpMyAdmin or MySQL

-- First, let's see all users
SELECT * FROM utilisateur;

-- Find anonymous/guest users
SELECT * FROM utilisateur WHERE Nom LIKE '%Anonyme%' OR Nom LIKE '%anonyme%' OR Email LIKE '%guest%';

-- Find users with empty password (likely created as guest)
SELECT ID_Utilisateur, Nom, Email, Role, Mot_de_passe FROM utilisateur WHERE Mot_de_passe = '' OR Mot_de_passe IS NULL;

-- Delete anonymous users (uncomment after checking)
-- DELETE FROM utilisateur WHERE Nom LIKE '%Anonyme%' OR Email LIKE '%guest%';
