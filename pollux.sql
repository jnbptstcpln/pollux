-- phpMyAdmin SQL Dump
-- version 4.9.2
-- https://www.phpmyadmin.net/
--
-- Hôte : localhost
-- Généré le :  mar. 18 août 2020 à 07:55
-- Version du serveur :  8.0.18
-- Version de PHP :  7.4.5

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

--
-- Base de données :  `pollux`
--

-- --------------------------------------------------------

--
-- Structure de la table `daemon`
--

CREATE TABLE `daemon` (
  `id` int(11) NOT NULL,
  `instance_id` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `state` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `last_update` datetime NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `domain` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `machine` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `machine_name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `settings` text COLLATE utf8mb4_general_ci NOT NULL,
  `_queue` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `daemon_log`
--

CREATE TABLE `daemon_log` (
  `id` int(11) NOT NULL,
  `daemon_identifier` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `created_on` datetime(3) NOT NULL,
  `message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `flow`
--

CREATE TABLE `flow` (
  `id` int(11) NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `domain` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `identifier` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `last_update` datetime NOT NULL,
  `scheme` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `flow_instance`
--

CREATE TABLE `flow_instance` (
  `id` int(11) NOT NULL,
  `identifier` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `state` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `flow_identifier` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `domain` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `daemon_identifier` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `created_on` datetime NOT NULL,
  `started_on` datetime DEFAULT NULL,
  `completed_on` datetime DEFAULT NULL,
  `environment_initial` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `environment` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `flow_instance_log`
--

CREATE TABLE `flow_instance_log` (
  `id` int(11) NOT NULL,
  `flow_instance_identifier` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `created_on` datetime(3) NOT NULL,
  `message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `daemon`
--
ALTER TABLE `daemon`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `daemon_log`
--
ALTER TABLE `daemon_log`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `flow`
--
ALTER TABLE `flow`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `UNIQUE` (`identifier`);

--
-- Index pour la table `flow_instance`
--
ALTER TABLE `flow_instance`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `UNIQUE` (`identifier`);

--
-- Index pour la table `flow_instance_log`
--
ALTER TABLE `flow_instance_log`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `daemon`
--
ALTER TABLE `daemon`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `daemon_log`
--
ALTER TABLE `daemon_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `flow`
--
ALTER TABLE `flow`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `flow_instance`
--
ALTER TABLE `flow_instance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `flow_instance_log`
--
ALTER TABLE `flow_instance_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;
