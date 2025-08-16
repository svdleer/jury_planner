-- Database Schema for mnc_jury
-- Generated on 2025-08-16T22:45:10.636329
-- 
-- This schema represents the existing database structure
-- analyzed from your production database

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

CREATE DATABASE IF NOT EXISTS `mnc_jury` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `mnc_jury`;


-- Table: all_matches (Current rows: 496)
CREATE TABLE `all_matches` (
  `id` int(11) NOT NULL auto_increment PRIMARY KEY,
  `date_time` datetime,
  `competition` varchar(25),
  `class` varchar(25),
  `home_team` varchar(25),
  `away_team` varchar(25),
  `location` varchar(25),
  `match_id` varchar(25),
  `sportlink_id` varchar(25)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: excluded_teams (Current rows: 5)
CREATE TABLE `excluded_teams` (
  `id` int(11) NOT NULL auto_increment PRIMARY KEY,
  `name` varchar(25)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: home_matches (Current rows: 253)
CREATE TABLE `home_matches` (
  `id` int(11) NOT NULL auto_increment PRIMARY KEY,
  `date_time` datetime,
  `competition` varchar(25),
  `class` varchar(25),
  `home_team` varchar(25),
  `away_team` varchar(25),
  `location` varchar(25),
  `match_id` varchar(25),
  `sportlink_id` varchar(25)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: jury_assignments (Current rows: 0)
CREATE TABLE `jury_assignments` (
  `id` int(11) NOT NULL auto_increment PRIMARY KEY,
  `match_id` int(11) NOT NULL,
  `team_id` int(11) NOT NULL,
  `locked` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: jury_shifts (Current rows: 0)
CREATE TABLE `jury_shifts` (
  `id` int(11) NOT NULL auto_increment PRIMARY KEY,
  `date_time` datetime NOT NULL,
  `match_id` int(11) NOT NULL,
  `team_id` int(11) NOT NULL,
  `points` int(11) NOT NULL,
  `locked` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: jury_teams (Current rows: 10)
CREATE TABLE `jury_teams` (
  `id` int(11) NOT NULL auto_increment PRIMARY KEY,
  `name` varchar(25)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: matches (Current rows: 0)
CREATE TABLE `matches` (
  `id` int(11) NOT NULL auto_increment PRIMARY KEY,
  `date_time` datetime,
  `competition` varchar(25),
  `class` varchar(25),
  `home_team` varchar(25),
  `away_team` varchar(25),
  `location` varchar(25),
  `match_id` varchar(25),
  `sportlink_id` varchar(25)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: mnc_teams (Current rows: 26)
CREATE TABLE `mnc_teams` (
  `id` int(11) NOT NULL auto_increment PRIMARY KEY,
  `sportlink_team_id` varchar(11) NOT NULL,
  `name` varchar(25)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: static_assignments (Current rows: 2)
CREATE TABLE `static_assignments` (
  `id` int(11) NOT NULL auto_increment PRIMARY KEY,
  `home_team` varchar(25) NOT NULL,
  `jury_team` varchar(25) NOT NULL,
  `points` int(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: team_points (Current rows: 0)
CREATE TABLE `team_points` (
  `id` int(11) NOT NULL auto_increment PRIMARY KEY,
  `team_id` int(11) NOT NULL,
  `points` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: users (Current rows: 3)
CREATE TABLE `users` (
  `id` int(11) NOT NULL auto_increment PRIMARY KEY,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `role` enum('admin','user') DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT 'current_timestamp()',
  `last_login` timestamp,
  `is_active` tinyint(1) DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

COMMIT;
