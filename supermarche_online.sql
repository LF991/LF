-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 19, 2025 at 03:24 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `supermarche_online`
--

-- --------------------------------------------------------

--
-- Table structure for table `commande`
--

CREATE TABLE `commande` (
  `ID_Commande` int(11) NOT NULL,
  `ID_Utilisateur` int(11) NOT NULL,
  `Date_Commande` datetime DEFAULT current_timestamp(),
  `Statut` enum('En attente','Confirmée','En préparation','Prête','En livraison','Livrée','Annulée') DEFAULT 'En attente',
  `Adresse_Livraison` text NOT NULL,
  `Prix_Total` decimal(10,2) NOT NULL,
  `Notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `commande_produit`
--

CREATE TABLE `commande_produit` (
  `ID_Commande_Produit` int(11) NOT NULL,
  `ID_Commande` int(11) NOT NULL,
  `ID_Produit` int(11) NOT NULL,
  `Quantite` int(11) NOT NULL,
  `Prix_Unitaire` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `conversation_ai`
--

CREATE TABLE `conversation_ai` (
  `ID_Conversation` int(11) NOT NULL,
  `ID_Utilisateur` int(11) NOT NULL,
  `Message_Utilisateur` text NOT NULL,
  `Reponse_AI` text NOT NULL,
  `Horodatage` datetime DEFAULT current_timestamp(),
  `Categorie` enum('Information','Probleme','Commande','Livraison','General') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `historique_positions`
--

CREATE TABLE `historique_positions` (
  `ID_Position` int(11) NOT NULL,
  `ID_Livreur` int(11) NOT NULL,
  `Position_GPS` point NOT NULL,
  `Horodatage` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `livraison`
--

CREATE TABLE `livraison` (
  `ID_Livraison` int(11) NOT NULL,
  `ID_Commande` int(11) NOT NULL,
  `ID_Livreur` int(11) DEFAULT NULL,
  `Temps_Estime` int(11) DEFAULT NULL,
  `Statut_Livraison` enum('En attente','Assignée','En cours','Livrée','Retard','Problème') DEFAULT 'En attente',
  `Date_Debut_Livraison` datetime DEFAULT NULL,
  `Date_Fin_Livraison` datetime DEFAULT NULL,
  `Distance_KM` decimal(6,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `livreur`
--

CREATE TABLE `livreur` (
  `ID_Livreur` int(11) NOT NULL,
  `ID_Utilisateur` int(11) NOT NULL,
  `Statut_Disponibilite` enum('Disponible','Indisponible','En livraison') DEFAULT 'Disponible',
  `Position_GPS_Actuelle` point DEFAULT NULL,
  `Vehicule` varchar(100) DEFAULT NULL,
  `Capacite_Max` decimal(8,2) DEFAULT 20.00,
  `Note` decimal(3,2) DEFAULT 5.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `livreur`
--

INSERT INTO `livreur` (`ID_Livreur`, `ID_Utilisateur`, `Statut_Disponibilite`, `Position_GPS_Actuelle`, `Vehicule`, `Capacite_Max`, `Note`) VALUES
(1, 2, 'Disponible', NULL, 'Vélo électrique', 20.00, 5.00);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `ID_Notification` int(11) NOT NULL,
  `ID_Utilisateur` int(11) NOT NULL,
  `ID_Commande` int(11) DEFAULT NULL,
  `ID_Livraison` int(11) DEFAULT NULL,
  `Type_Notification` enum('Commande','Livraison','Systeme','Promotion','Alerte') NOT NULL,
  `Titre` varchar(200) NOT NULL,
  `Message` text NOT NULL,
  `Statut_Lecture` enum('Non lu','Lu') DEFAULT 'Non lu',
  `Date_Creation` datetime DEFAULT current_timestamp(),
  `Date_Lecture` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `panier`
--

CREATE TABLE `panier` (
  `ID_Panier` int(11) NOT NULL,
  `ID_Utilisateur` int(11) NOT NULL,
  `ID_Produit` int(11) NOT NULL,
  `Quantite` int(11) NOT NULL DEFAULT 1,
  `Date_Ajout` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `produit`
--

CREATE TABLE `produit` (
  `ID_Produit` int(11) NOT NULL,
  `Nom` varchar(200) NOT NULL,
  `Description` text DEFAULT NULL,
  `Prix` decimal(10,2) NOT NULL,
  `Stock` int(11) DEFAULT 0,
  `Categorie` varchar(100) DEFAULT NULL,
  `Image_URL` varchar(500) DEFAULT NULL,
  `Statut` enum('Disponible','Indisponible') DEFAULT 'Disponible',
  `Date_Ajout` datetime DEFAULT current_timestamp(),
  `Poids` decimal(8,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `produit`
--

INSERT INTO `produit` (`ID_Produit`, `Nom`, `Description`, `Prix`, `Stock`, `Categorie`, `Image_URL`, `Statut`, `Date_Ajout`, `Poids`) VALUES
(1, 'Lait 1L', 'Lait entier frais', 2.50, 100, 'Produits laitiers', NULL, 'Disponible', '2025-11-14 18:00:54', 0.00),
(2, 'Pain complet', 'Pain complet bio', 1.80, 50, 'Boulangerie', NULL, 'Disponible', '2025-11-14 18:00:54', 0.00),
(3, 'Pommes Golden', 'Pommes golden bio, 1kg', 3.20, 75, 'Fruits', NULL, 'Disponible', '2025-11-14 18:00:54', 0.00),
(4, 'Poulet fermier', 'Poulet fermier, 1.5kg', 12.90, 30, 'Viandes', NULL, 'Disponible', '2025-11-14 18:00:54', 0.00),
(5, 'Eau minérale 6x1.5L', 'Pack eau minérale', 4.50, 80, 'Boissons', NULL, 'Disponible', '2025-11-14 18:00:54', 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `utilisateur`
--

CREATE TABLE `utilisateur` (
  `ID_Utilisateur` int(11) NOT NULL,
  `Nom` varchar(100) NOT NULL,
  `Email` varchar(150) NOT NULL,
  `Mot_de_passe` varchar(255) NOT NULL,
  `Role` enum('Client','Admin','Livreur') DEFAULT 'Client',
  `Adresse` text DEFAULT NULL,
  `Coordonnees_GPS` point DEFAULT NULL,
  `Telephone` varchar(20) DEFAULT NULL,
  `Date_Inscription` datetime DEFAULT current_timestamp(),
  `Statut` enum('Actif','Inactif') DEFAULT 'Actif'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `utilisateur`
--

INSERT INTO `utilisateur` (`ID_Utilisateur`, `Nom`, `Email`, `Mot_de_passe`, `Role`, `Adresse`, `Coordonnees_GPS`, `Telephone`, `Date_Inscription`, `Statut`) VALUES
(1, 'Admin Principal', 'admin@supermarche.com', '$2y$10$ExampleHash', 'Admin', 'Adresse admin', NULL, '+1234567890', '2025-11-14 18:00:53', 'Actif'),
(2, 'Jean Livreur', 'livreur@supermarche.com', '$2y$10$ExampleHash', 'Livreur', 'Adresse livreur', NULL, '+1234567891', '2025-11-14 18:00:54', 'Actif');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `commande`
--
ALTER TABLE `commande`
  ADD PRIMARY KEY (`ID_Commande`),
  ADD KEY `idx_commande_statut` (`Statut`),
  ADD KEY `idx_commande_utilisateur` (`ID_Utilisateur`);

--
-- Indexes for table `commande_produit`
--
ALTER TABLE `commande_produit`
  ADD PRIMARY KEY (`ID_Commande_Produit`),
  ADD KEY `ID_Commande` (`ID_Commande`),
  ADD KEY `ID_Produit` (`ID_Produit`);

--
-- Indexes for table `conversation_ai`
--
ALTER TABLE `conversation_ai`
  ADD PRIMARY KEY (`ID_Conversation`),
  ADD KEY `ID_Utilisateur` (`ID_Utilisateur`);

--
-- Indexes for table `historique_positions`
--
ALTER TABLE `historique_positions`
  ADD PRIMARY KEY (`ID_Position`),
  ADD KEY `ID_Livreur` (`ID_Livreur`);

--
-- Indexes for table `livraison`
--
ALTER TABLE `livraison`
  ADD PRIMARY KEY (`ID_Livraison`),
  ADD UNIQUE KEY `ID_Commande` (`ID_Commande`),
  ADD KEY `idx_livraison_statut` (`Statut_Livraison`),
  ADD KEY `idx_livraison_livreur` (`ID_Livreur`);

--
-- Indexes for table `livreur`
--
ALTER TABLE `livreur`
  ADD PRIMARY KEY (`ID_Livreur`),
  ADD UNIQUE KEY `ID_Utilisateur` (`ID_Utilisateur`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`ID_Notification`),
  ADD KEY `ID_Commande` (`ID_Commande`),
  ADD KEY `ID_Livraison` (`ID_Livraison`),
  ADD KEY `idx_notifications_utilisateur` (`ID_Utilisateur`),
  ADD KEY `idx_notifications_statut` (`Statut_Lecture`);

--
-- Indexes for table `panier`
--
ALTER TABLE `panier`
  ADD PRIMARY KEY (`ID_Panier`),
  ADD UNIQUE KEY `unique_panier` (`ID_Utilisateur`,`ID_Produit`),
  ADD KEY `ID_Produit` (`ID_Produit`),
  ADD KEY `idx_panier_utilisateur` (`ID_Utilisateur`);

--
-- Indexes for table `produit`
--
ALTER TABLE `produit`
  ADD PRIMARY KEY (`ID_Produit`),
  ADD KEY `idx_produit_categorie` (`Categorie`);

--
-- Indexes for table `utilisateur`
--
ALTER TABLE `utilisateur`
  ADD PRIMARY KEY (`ID_Utilisateur`),
  ADD UNIQUE KEY `Email` (`Email`),
  ADD KEY `idx_utilisateur_email` (`Email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `commande`
--
ALTER TABLE `commande`
  MODIFY `ID_Commande` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `commande_produit`
--
ALTER TABLE `commande_produit`
  MODIFY `ID_Commande_Produit` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `conversation_ai`
--
ALTER TABLE `conversation_ai`
  MODIFY `ID_Conversation` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `historique_positions`
--
ALTER TABLE `historique_positions`
  MODIFY `ID_Position` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `livraison`
--
ALTER TABLE `livraison`
  MODIFY `ID_Livraison` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `livreur`
--
ALTER TABLE `livreur`
  MODIFY `ID_Livreur` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `ID_Notification` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `panier`
--
ALTER TABLE `panier`
  MODIFY `ID_Panier` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `produit`
--
ALTER TABLE `produit`
  MODIFY `ID_Produit` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `utilisateur`
--
ALTER TABLE `utilisateur`
  MODIFY `ID_Utilisateur` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `commande`
--
ALTER TABLE `commande`
  ADD CONSTRAINT `commande_ibfk_1` FOREIGN KEY (`ID_Utilisateur`) REFERENCES `utilisateur` (`ID_Utilisateur`) ON DELETE CASCADE;

--
-- Constraints for table `commande_produit`
--
ALTER TABLE `commande_produit`
  ADD CONSTRAINT `commande_produit_ibfk_1` FOREIGN KEY (`ID_Commande`) REFERENCES `commande` (`ID_Commande`) ON DELETE CASCADE,
  ADD CONSTRAINT `commande_produit_ibfk_2` FOREIGN KEY (`ID_Produit`) REFERENCES `produit` (`ID_Produit`) ON DELETE CASCADE;

--
-- Constraints for table `conversation_ai`
--
ALTER TABLE `conversation_ai`
  ADD CONSTRAINT `conversation_ai_ibfk_1` FOREIGN KEY (`ID_Utilisateur`) REFERENCES `utilisateur` (`ID_Utilisateur`) ON DELETE CASCADE;

--
-- Constraints for table `historique_positions`
--
ALTER TABLE `historique_positions`
  ADD CONSTRAINT `historique_positions_ibfk_1` FOREIGN KEY (`ID_Livreur`) REFERENCES `livreur` (`ID_Livreur`) ON DELETE CASCADE;

--
-- Constraints for table `livraison`
--
ALTER TABLE `livraison`
  ADD CONSTRAINT `livraison_ibfk_1` FOREIGN KEY (`ID_Commande`) REFERENCES `commande` (`ID_Commande`) ON DELETE CASCADE,
  ADD CONSTRAINT `livraison_ibfk_2` FOREIGN KEY (`ID_Livreur`) REFERENCES `livreur` (`ID_Livreur`) ON DELETE SET NULL;

--
-- Constraints for table `livreur`
--
ALTER TABLE `livreur`
  ADD CONSTRAINT `livreur_ibfk_1` FOREIGN KEY (`ID_Utilisateur`) REFERENCES `utilisateur` (`ID_Utilisateur`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`ID_Utilisateur`) REFERENCES `utilisateur` (`ID_Utilisateur`) ON DELETE CASCADE,
  ADD CONSTRAINT `notifications_ibfk_2` FOREIGN KEY (`ID_Commande`) REFERENCES `commande` (`ID_Commande`) ON DELETE SET NULL,
  ADD CONSTRAINT `notifications_ibfk_3` FOREIGN KEY (`ID_Livraison`) REFERENCES `livraison` (`ID_Livraison`) ON DELETE SET NULL;

--
-- Constraints for table `panier`
--
ALTER TABLE `panier`
  ADD CONSTRAINT `panier_ibfk_1` FOREIGN KEY (`ID_Utilisateur`) REFERENCES `utilisateur` (`ID_Utilisateur`) ON DELETE CASCADE,
  ADD CONSTRAINT `panier_ibfk_2` FOREIGN KEY (`ID_Produit`) REFERENCES `produit` (`ID_Produit`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
