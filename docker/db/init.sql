SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

CREATE TABLE IF NOT EXISTS `refresh_tokens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `token` varchar(512) NOT NULL,
  `expires_at` datetime DEFAULT NULL,
  `revoked` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `tokens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `token` varchar(512) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `expires_at` datetime DEFAULT NULL,
  `revoked` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(100) NOT NULL,
  `level` int(11) NOT NULL DEFAULT 1,
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`data`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `vehicle_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

CREATE TABLE IF NOT EXISTS `vehicles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `category` int(11) DEFAULT NULL,
  `tax` double NOT NULL DEFAULT 0,
  `uses` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `fk_vehicle_category` (`category`),
  CONSTRAINT `fk_vehicle_category` FOREIGN KEY (`category`) REFERENCES `vehicle_categories` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

CREATE TABLE IF NOT EXISTS `ferries` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `status` int(11) NOT NULL DEFAULT 1,
  `owner` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_ferries_owner` (`owner`),
  CONSTRAINT `fk_ferries_owner` FOREIGN KEY (`owner`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

CREATE TABLE IF NOT EXISTS `ferry_routes` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `route` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

CREATE TABLE IF NOT EXISTS `boardings` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `ferry` int(10) unsigned DEFAULT NULL,
  `route` int(10) unsigned DEFAULT NULL,
  `init_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `departure_time` timestamp NULL DEFAULT NULL,
  `closed` int(11) DEFAULT 0,
  `agent` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_ferry_route` (`route`),
  KEY `fk_ferry` (`ferry`),
  KEY `fk_user` (`agent`),
  CONSTRAINT `fk_ferry` FOREIGN KEY (`ferry`) REFERENCES `ferries` (`id`),
  CONSTRAINT `fk_ferry_route` FOREIGN KEY (`route`) REFERENCES `ferry_routes` (`id`),
  CONSTRAINT `fk_user` FOREIGN KEY (`agent`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

CREATE TABLE IF NOT EXISTS `checkins` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `boarding` int(10) unsigned NOT NULL,
  `plate` varchar(100) DEFAULT NULL,
  `pax` int(11) DEFAULT NULL,
  `vehicle` int(10) unsigned DEFAULT NULL,
  `value` double DEFAULT NULL,
  `add_value` double DEFAULT NULL,
  `observation` varchar(255) DEFAULT NULL,
  `add_value_reason` varchar(255) DEFAULT NULL,
  `date_in` timestamp NOT NULL DEFAULT current_timestamp(),
  `refunded` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `fk_vehicle` (`vehicle`),
  CONSTRAINT `fk_vehicle` FOREIGN KEY (`vehicle`) REFERENCES `vehicles` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

COMMIT;
