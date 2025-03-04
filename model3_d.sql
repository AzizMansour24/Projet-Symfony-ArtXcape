-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : dim. 23 fév. 2025 à 16:23
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
-- Base de données : `pidatabase`
--

-- --------------------------------------------------------

--
-- Structure de la table `model3_d`
--

CREATE TABLE `model3_d` (
  `id` int(11) NOT NULL,
  `event_id` int(11) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `description` longtext DEFAULT NULL,
  `file_url` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `model3_d`
--

INSERT INTO `model3_d` (`id`, `event_id`, `name`, `description`, `file_url`) VALUES
(1, 16, 'statue', 'sdqdqsddqdqd', 'Statue_of_Liberty_0203185133_texture.glb'),
(2, 16, 'dddddddddd', 'dddddddddddddd', 'Statue_of_Liberty_0203185133_texture.glb'),
(3, 16, 'bbbbbbb', 'bbbbbbbbbb', 'Statue_of_Liberty_0203185133_texture.glb');

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `model3_d`
--
ALTER TABLE `model3_d`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_B1A414D671F7E88B` (`event_id`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `model3_d`
--
ALTER TABLE `model3_d`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `model3_d`
--
ALTER TABLE `model3_d`
  ADD CONSTRAINT `FK_B1A414D671F7E88B` FOREIGN KEY (`event_id`) REFERENCES `event` (`idevent`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
