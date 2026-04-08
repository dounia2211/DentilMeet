-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : mer. 08 avr. 2026 à 12:40
-- Version du serveur : 10.4.32-MariaDB
-- Version de PHP : 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `dentilmeet`
--

-- --------------------------------------------------------

--
-- Structure de la table `admin`
--

CREATE TABLE `admin` (
  `id_admin` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `prenom` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `can_validate_dentist` tinyint(1) NOT NULL DEFAULT 0,
  `can_suspend` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `appointment`
--

CREATE TABLE `appointment` (
  `id_appointment` int(11) NOT NULL,
  `appointment_date` date NOT NULL,
  `appointment_time` time NOT NULL,
  `appointment_status` enum('en_attente','confirme','annule','termine') NOT NULL DEFAULT 'en_attente',
  `reason` varchar(255) DEFAULT NULL,
  `service_type` varchar(150) DEFAULT NULL,
  `total_price` decimal(10,2) DEFAULT 0.00,
  `deposit_amount` decimal(10,2) DEFAULT 0.00,
  `id_patient` int(11) NOT NULL,
  `id_dentist` int(11) NOT NULL,
  `id_plan` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `availability`
--

CREATE TABLE `availability` (
  `id_availability` int(11) NOT NULL,
  `day_of_week` varchar(20) DEFAULT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `specific_date` date DEFAULT NULL,
  `is_blocked` tinyint(1) NOT NULL DEFAULT 0,
  `id_dentist` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `chat_message`
--

CREATE TABLE `chat_message` (
  `id_message` int(11) NOT NULL,
  `message_text` text NOT NULL,
  `sent_at` datetime NOT NULL DEFAULT current_timestamp(),
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `sender_type` enum('patient','dentiste') NOT NULL,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `id_appointment` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `clinic`
--

CREATE TABLE `clinic` (
  `id_clinic` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `address` varchar(255) NOT NULL,
  `city` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `dentist`
--

CREATE TABLE `dentist` (
  `id_dentist` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `speciality` varchar(150) DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `year_of_experience` int(11) DEFAULT 0,
  `description` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `diploma_document` varchar(255) NOT NULL,
  `license_number` varchar(100) NOT NULL,
  `verification_status` enum('en_attente','approuve','refuse') NOT NULL DEFAULT 'en_attente',
  `submitted_at` datetime DEFAULT NULL,
  `is_suspended` tinyint(1) NOT NULL DEFAULT 0,
  `validated_by` int(11) DEFAULT NULL,
  `id_clinic` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `document`
--

CREATE TABLE `document` (
  `id_document` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_type` varchar(50) DEFAULT NULL,
  `document_type` enum('radio','analyse','ordonnance','compte_rendu') NOT NULL,
  `upload_date` datetime NOT NULL DEFAULT current_timestamp(),
  `file_path` varchar(255) NOT NULL,
  `notes` text DEFAULT NULL,
  `id_patient` int(11) NOT NULL,
  `id_dentist` int(11) NOT NULL,
  `id_appointment` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `favorite`
--

CREATE TABLE `favorite` (
  `id_favorite` int(11) NOT NULL,
  `id_patient` int(11) NOT NULL,
  `id_dentist` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `logs`
--

CREATE TABLE `logs` (
  `id_log` int(11) NOT NULL,
  `action` varchar(255) NOT NULL,
  `entity_type` varchar(100) DEFAULT NULL,
  `entity_id` int(11) DEFAULT NULL,
  `date_time` datetime NOT NULL DEFAULT current_timestamp(),
  `actor_type` enum('patient','dentiste','admin') NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `id_patient` int(11) DEFAULT NULL,
  `id_dentist` int(11) DEFAULT NULL,
  `id_admin` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `notification`
--

CREATE TABLE `notification` (
  `id_notification` int(11) NOT NULL,
  `message` text NOT NULL,
  `type` enum('rappel','confirmation','annulation','alerte') NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `id_patient` int(11) DEFAULT NULL,
  `id_dentist` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `patient`
--

CREATE TABLE `patient` (
  `id_patient` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `birth_date` date DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `is_suspended` tinyint(1) NOT NULL DEFAULT 0,
  `photo` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `patient`
--

INSERT INTO `patient` (`id_patient`, `full_name`, `email`, `password`, `phone`, `birth_date`, `address`, `created_at`, `is_suspended`, `photo`) VALUES
(1, 'Youcef Benali', 'youcef@gmail.com', '$2y$12$03QaqzIPoNTlXuYJWNxz6.tjDctRcYcJYc1Z/LE46enrK8vAzROhW', '0555123456', NULL, NULL, '2026-04-08 12:29:25', 0, NULL),
(3, 'Youcef Benali', 'dounia@gmail.com', '$2y$12$2k2le5ppqNibirWfNk6y4uQLFz7OAY0VZwG0Jf3oPPTtot6tmtji2', '0555123434', NULL, NULL, '2026-04-08 12:33:28', 0, NULL);

-- --------------------------------------------------------

--
-- Structure de la table `payment`
--

CREATE TABLE `payment` (
  `id_payment` int(11) NOT NULL,
  `method_payment` enum('especes','carte','virement') NOT NULL,
  `payment_status` enum('paye','en_attente','rembourse') NOT NULL DEFAULT 'en_attente',
  `transaction_reference` varchar(100) DEFAULT NULL,
  `invoice_number` varchar(100) DEFAULT NULL,
  `invoice_date` date DEFAULT NULL,
  `invoice_path` varchar(255) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `paid_at` datetime DEFAULT NULL,
  `id_appointment` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `review`
--

CREATE TABLE `review` (
  `id_review` int(11) NOT NULL,
  `rating` tinyint(4) NOT NULL CHECK (`rating` between 1 and 5),
  `comment` text DEFAULT NULL,
  `review_date` datetime NOT NULL DEFAULT current_timestamp(),
  `is_reported` tinyint(1) NOT NULL DEFAULT 0,
  `id_patient` int(11) NOT NULL,
  `id_dentist` int(11) NOT NULL,
  `id_appointment` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `treatment_plan`
--

CREATE TABLE `treatment_plan` (
  `id_plan` int(11) NOT NULL,
  `plan_title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `estimated_cost` decimal(10,2) DEFAULT 0.00,
  `plan_status` enum('en_cours','termine','abandonne') NOT NULL DEFAULT 'en_cours',
  `id_patient` int(11) NOT NULL,
  `id_dentist` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id_admin`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Index pour la table `appointment`
--
ALTER TABLE `appointment`
  ADD PRIMARY KEY (`id_appointment`),
  ADD KEY `id_patient` (`id_patient`),
  ADD KEY `id_dentist` (`id_dentist`),
  ADD KEY `id_plan` (`id_plan`);

--
-- Index pour la table `availability`
--
ALTER TABLE `availability`
  ADD PRIMARY KEY (`id_availability`),
  ADD KEY `id_dentist` (`id_dentist`);

--
-- Index pour la table `chat_message`
--
ALTER TABLE `chat_message`
  ADD PRIMARY KEY (`id_message`),
  ADD KEY `id_appointment` (`id_appointment`);

--
-- Index pour la table `clinic`
--
ALTER TABLE `clinic`
  ADD PRIMARY KEY (`id_clinic`);

--
-- Index pour la table `dentist`
--
ALTER TABLE `dentist`
  ADD PRIMARY KEY (`id_dentist`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `password` (`password`),
  ADD UNIQUE KEY `phone` (`phone`),
  ADD UNIQUE KEY `license_number` (`license_number`),
  ADD KEY `validated_by` (`validated_by`),
  ADD KEY `id_clinic` (`id_clinic`);

--
-- Index pour la table `document`
--
ALTER TABLE `document`
  ADD PRIMARY KEY (`id_document`),
  ADD KEY `id_patient` (`id_patient`),
  ADD KEY `id_dentist` (`id_dentist`),
  ADD KEY `id_appointment` (`id_appointment`);

--
-- Index pour la table `favorite`
--
ALTER TABLE `favorite`
  ADD PRIMARY KEY (`id_favorite`),
  ADD UNIQUE KEY `uq_favorite` (`id_patient`,`id_dentist`),
  ADD KEY `id_dentist` (`id_dentist`);

--
-- Index pour la table `logs`
--
ALTER TABLE `logs`
  ADD PRIMARY KEY (`id_log`),
  ADD KEY `id_patient` (`id_patient`),
  ADD KEY `id_dentist` (`id_dentist`),
  ADD KEY `id_admin` (`id_admin`);

--
-- Index pour la table `notification`
--
ALTER TABLE `notification`
  ADD PRIMARY KEY (`id_notification`),
  ADD KEY `id_patient` (`id_patient`),
  ADD KEY `id_dentist` (`id_dentist`);

--
-- Index pour la table `patient`
--
ALTER TABLE `patient`
  ADD PRIMARY KEY (`id_patient`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `password` (`password`),
  ADD UNIQUE KEY `phone` (`phone`);

--
-- Index pour la table `payment`
--
ALTER TABLE `payment`
  ADD PRIMARY KEY (`id_payment`),
  ADD UNIQUE KEY `invoice_number` (`invoice_number`),
  ADD KEY `id_appointment` (`id_appointment`);

--
-- Index pour la table `review`
--
ALTER TABLE `review`
  ADD PRIMARY KEY (`id_review`),
  ADD UNIQUE KEY `unique_review` (`id_patient`,`id_appointment`),
  ADD KEY `id_dentist` (`id_dentist`),
  ADD KEY `id_appointment` (`id_appointment`);

--
-- Index pour la table `treatment_plan`
--
ALTER TABLE `treatment_plan`
  ADD PRIMARY KEY (`id_plan`),
  ADD KEY `id_patient` (`id_patient`),
  ADD KEY `id_dentist` (`id_dentist`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `admin`
--
ALTER TABLE `admin`
  MODIFY `id_admin` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `appointment`
--
ALTER TABLE `appointment`
  MODIFY `id_appointment` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `availability`
--
ALTER TABLE `availability`
  MODIFY `id_availability` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `chat_message`
--
ALTER TABLE `chat_message`
  MODIFY `id_message` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `clinic`
--
ALTER TABLE `clinic`
  MODIFY `id_clinic` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `dentist`
--
ALTER TABLE `dentist`
  MODIFY `id_dentist` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `document`
--
ALTER TABLE `document`
  MODIFY `id_document` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `favorite`
--
ALTER TABLE `favorite`
  MODIFY `id_favorite` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `logs`
--
ALTER TABLE `logs`
  MODIFY `id_log` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `notification`
--
ALTER TABLE `notification`
  MODIFY `id_notification` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `patient`
--
ALTER TABLE `patient`
  MODIFY `id_patient` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `payment`
--
ALTER TABLE `payment`
  MODIFY `id_payment` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `review`
--
ALTER TABLE `review`
  MODIFY `id_review` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `treatment_plan`
--
ALTER TABLE `treatment_plan`
  MODIFY `id_plan` int(11) NOT NULL AUTO_INCREMENT;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `appointment`
--
ALTER TABLE `appointment`
  ADD CONSTRAINT `appointment_ibfk_1` FOREIGN KEY (`id_patient`) REFERENCES `patient` (`id_patient`) ON DELETE CASCADE,
  ADD CONSTRAINT `appointment_ibfk_2` FOREIGN KEY (`id_dentist`) REFERENCES `dentist` (`id_dentist`) ON DELETE CASCADE,
  ADD CONSTRAINT `appointment_ibfk_3` FOREIGN KEY (`id_plan`) REFERENCES `treatment_plan` (`id_plan`) ON DELETE SET NULL;

--
-- Contraintes pour la table `availability`
--
ALTER TABLE `availability`
  ADD CONSTRAINT `availability_ibfk_1` FOREIGN KEY (`id_dentist`) REFERENCES `dentist` (`id_dentist`) ON DELETE CASCADE;

--
-- Contraintes pour la table `chat_message`
--
ALTER TABLE `chat_message`
  ADD CONSTRAINT `chat_message_ibfk_1` FOREIGN KEY (`id_appointment`) REFERENCES `appointment` (`id_appointment`) ON DELETE SET NULL;

--
-- Contraintes pour la table `dentist`
--
ALTER TABLE `dentist`
  ADD CONSTRAINT `dentist_ibfk_1` FOREIGN KEY (`validated_by`) REFERENCES `admin` (`id_admin`) ON DELETE SET NULL,
  ADD CONSTRAINT `dentist_ibfk_2` FOREIGN KEY (`id_clinic`) REFERENCES `clinic` (`id_clinic`) ON DELETE SET NULL;

--
-- Contraintes pour la table `document`
--
ALTER TABLE `document`
  ADD CONSTRAINT `document_ibfk_1` FOREIGN KEY (`id_patient`) REFERENCES `patient` (`id_patient`) ON DELETE CASCADE,
  ADD CONSTRAINT `document_ibfk_2` FOREIGN KEY (`id_dentist`) REFERENCES `dentist` (`id_dentist`) ON DELETE CASCADE,
  ADD CONSTRAINT `document_ibfk_3` FOREIGN KEY (`id_appointment`) REFERENCES `appointment` (`id_appointment`) ON DELETE SET NULL;

--
-- Contraintes pour la table `favorite`
--
ALTER TABLE `favorite`
  ADD CONSTRAINT `favorite_ibfk_1` FOREIGN KEY (`id_patient`) REFERENCES `patient` (`id_patient`) ON DELETE CASCADE,
  ADD CONSTRAINT `favorite_ibfk_2` FOREIGN KEY (`id_dentist`) REFERENCES `dentist` (`id_dentist`) ON DELETE CASCADE;

--
-- Contraintes pour la table `logs`
--
ALTER TABLE `logs`
  ADD CONSTRAINT `logs_ibfk_1` FOREIGN KEY (`id_patient`) REFERENCES `patient` (`id_patient`) ON DELETE SET NULL,
  ADD CONSTRAINT `logs_ibfk_2` FOREIGN KEY (`id_dentist`) REFERENCES `dentist` (`id_dentist`) ON DELETE SET NULL,
  ADD CONSTRAINT `logs_ibfk_3` FOREIGN KEY (`id_admin`) REFERENCES `admin` (`id_admin`) ON DELETE SET NULL;

--
-- Contraintes pour la table `notification`
--
ALTER TABLE `notification`
  ADD CONSTRAINT `notification_ibfk_1` FOREIGN KEY (`id_patient`) REFERENCES `patient` (`id_patient`) ON DELETE CASCADE,
  ADD CONSTRAINT `notification_ibfk_2` FOREIGN KEY (`id_dentist`) REFERENCES `dentist` (`id_dentist`) ON DELETE CASCADE;

--
-- Contraintes pour la table `payment`
--
ALTER TABLE `payment`
  ADD CONSTRAINT `payment_ibfk_1` FOREIGN KEY (`id_appointment`) REFERENCES `appointment` (`id_appointment`) ON DELETE CASCADE;

--
-- Contraintes pour la table `review`
--
ALTER TABLE `review`
  ADD CONSTRAINT `review_ibfk_1` FOREIGN KEY (`id_patient`) REFERENCES `patient` (`id_patient`) ON DELETE CASCADE,
  ADD CONSTRAINT `review_ibfk_2` FOREIGN KEY (`id_dentist`) REFERENCES `dentist` (`id_dentist`) ON DELETE CASCADE,
  ADD CONSTRAINT `review_ibfk_3` FOREIGN KEY (`id_appointment`) REFERENCES `appointment` (`id_appointment`) ON DELETE CASCADE;

--
-- Contraintes pour la table `treatment_plan`
--
ALTER TABLE `treatment_plan`
  ADD CONSTRAINT `treatment_plan_ibfk_1` FOREIGN KEY (`id_patient`) REFERENCES `patient` (`id_patient`) ON DELETE CASCADE,
  ADD CONSTRAINT `treatment_plan_ibfk_2` FOREIGN KEY (`id_dentist`) REFERENCES `dentist` (`id_dentist`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
