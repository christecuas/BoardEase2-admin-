-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 14, 2026 at 01:05 AM
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
-- Database: `boardease2`
--

-- --------------------------------------------------------

--
-- Table structure for table `active_boarders`
--

CREATE TABLE `active_boarders` (
  `active_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `status` enum('Active','Inactive') NOT NULL,
  `room_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `active_boarders`
--

INSERT INTO `active_boarders` (`active_id`, `user_id`, `status`, `room_id`) VALUES
(12, 44, 'Active', 82),
(17, 28, 'Active', 86),
(29, 38, 'Active', 90);

-- --------------------------------------------------------

--
-- Table structure for table `admin_accounts`
--

CREATE TABLE `admin_accounts` (
  `admin_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('super_admin') DEFAULT 'super_admin',
  `status` enum('active','inactive') DEFAULT 'active',
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_accounts`
--

INSERT INTO `admin_accounts` (`admin_id`, `name`, `email`, `password`, `role`, `status`, `last_login`, `created_at`, `updated_at`) VALUES
(1, 'Super Admin', 'admin@boardease.com', '$2y$10$5sSPAwaECIF2WfiqJQa26uP6VM86cfEJ/52xVAdL0GaYDk60eBiuu', 'super_admin', 'active', '2025-12-29 03:36:41', '2025-10-25 07:13:20', '2025-12-29 03:36:41'),
(2, 'Your Partner', 'partner@boardease.com', '$2y$10$5sSPAwaECIF2WfiqJQa26uP6VM86cfEJ/52xVAdL0GaYDk60eBiuu', 'super_admin', 'active', NULL, '2025-10-25 07:13:20', '2025-11-15 06:14:43');

-- --------------------------------------------------------

--
-- Table structure for table `admin_activity_log`
--

CREATE TABLE `admin_activity_log` (
  `activity_id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `activity_type` enum('login','logout','password_change','email_change','status_change','user_approved','user_rejected','user_created','user_updated','user_deleted','system_change','other') DEFAULT 'other',
  `activity_title` varchar(255) NOT NULL,
  `activity_description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_activity_log`
--

INSERT INTO `admin_activity_log` (`activity_id`, `admin_id`, `activity_type`, `activity_title`, `activity_description`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 1, 'logout', 'Super Admin logged out', 'Admin logout successful', '192.168.101.7', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-15 05:51:45'),
(2, 1, 'login', 'Super Admin logged in', 'Admin login successful from 192.168.101.7', '192.168.101.7', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-15 05:51:51'),
(3, 1, 'status_change', 'Account Status: Active - Your Partner', 'Admin account activated: Admin ID 2, Name: Your Partner, Email: partner@boardease.com, Previous Status: Inactive', '192.168.101.7', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-15 06:14:43'),
(4, 1, 'logout', 'Super Admin logged out', 'Admin logout successful', '192.168.101.7', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-15 06:15:14'),
(5, 1, 'login', 'Super Admin logged in', 'Admin login successful from 192.168.101.7', '192.168.101.7', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-15 06:15:22'),
(6, 1, 'logout', 'Super Admin logged out', 'Admin logout successful', '192.168.101.7', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-15 06:48:40'),
(7, 1, 'login', 'Super Admin logged in', 'Admin login successful from 192.168.101.7', '192.168.101.7', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-15 06:52:34'),
(8, 1, 'logout', 'Super Admin logged out', 'Admin logout successful', '192.168.101.7', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-15 14:14:27'),
(9, 1, 'login', 'Super Admin logged in', 'Admin login successful from 192.168.101.7', '192.168.101.7', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-17 12:51:16'),
(10, 1, 'login', 'Super Admin logged in', 'Admin login successful from 192.168.101.7', '192.168.101.7', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-18 03:18:28'),
(11, 1, 'login', 'Super Admin logged in', 'Admin login successful from 192.168.101.7', '192.168.101.7', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-18 10:41:12'),
(12, 1, 'login', 'Super Admin logged in', 'Admin login successful from 192.168.101.7', '192.168.101.7', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-19 02:28:22'),
(13, 1, 'user_approved', 'User registration approved: Christe Hanna Mae  Cuas', 'Registration ID: 113, Email: christehannamae.cuas@bisu.edu.ph, Role: ', '192.168.101.7', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-19 02:28:54'),
(14, 1, 'login', 'Super Admin logged in', 'Admin login successful from 192.168.101.7', '192.168.101.7', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-19 14:03:54'),
(15, 1, 'login', 'Super Admin logged in', 'Admin login successful from 192.168.137.96', '192.168.137.96', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-20 04:51:48'),
(16, 1, 'login', 'Super Admin logged in', 'Admin login successful from ::1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-20 04:52:57'),
(17, 1, 'login', 'Super Admin logged in', 'Admin login successful from 192.168.43.246', '192.168.43.246', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-20 04:57:01'),
(18, 1, 'login', 'Super Admin logged in', 'Admin login successful from 192.168.137.242', '192.168.137.242', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-20 05:19:31'),
(19, 1, 'login', 'Super Admin logged in', 'Admin login successful from 192.168.101.7', '192.168.101.7', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-20 11:39:47'),
(20, 1, 'login', 'Super Admin logged in', 'Admin login successful from 192.168.137.124', '192.168.137.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-21 01:30:50'),
(21, 1, 'login', 'Super Admin logged in', 'Admin login successful from 192.168.137.58', '192.168.137.58', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-21 02:21:41'),
(22, 1, 'login', 'Super Admin logged in', 'Admin login successful from 192.168.137.124', '192.168.137.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-21 06:30:59'),
(23, 1, 'login', 'Super Admin logged in', 'Admin login successful from 192.168.101.7', '192.168.101.7', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-23 02:32:08'),
(24, 1, '', 'User account suspended: Kimberly Mante', 'User ID: 36, Email: kimjulmante@gmail.com, Reason: jjjjjj', '192.168.101.7', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-23 06:12:58'),
(25, 1, '', 'Boarding house deactivated: Kikyam BH', 'Boarding House ID: 87, Name: Kikyam BH, Owner: Namz Baer (namzbaer@gmail.com), Reason: nnnn', '192.168.101.7', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-23 06:22:21'),
(26, 1, '', 'User account unsuspended: Kimberly Mante', 'User ID: 36, Email: kimjulmante@gmail.com', '192.168.101.7', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-23 06:26:17'),
(27, 1, '', 'User account unsuspended: Kimberly Mante', 'User ID: 36, Email: kimjulmante@gmail.com', '192.168.101.7', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-23 06:26:28'),
(28, 1, '', 'User account suspended: Ruel Cuas', 'User ID: 35, Email: cuasruel028@gmail.com, Reason: nnnnnn', '192.168.101.7', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-23 06:27:52'),
(29, 1, '', 'User account unsuspended: Ruel Cuas', 'User ID: 35, Email: cuasruel028@gmail.com', '192.168.101.7', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-23 06:29:55'),
(30, 1, '', 'User account suspended: Lizz Uy', 'User ID: 28, Email: hannacuas536@gmail.com, Reason: nnnn', '192.168.101.7', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-23 06:31:46'),
(31, 1, '', 'Boarding house deactivated: BH 1', 'Boarding House ID: 85, Name: BH 1, Owner: Namz Baer (namzbaer@gmail.com), Reason: uuuuu', '192.168.101.7', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-23 06:32:12'),
(32, 1, '', 'Boarding house activated: Kikyam BH', 'Boarding House ID: 87, Name: Kikyam BH, Owner: Namz Baer (namzbaer@gmail.com)', '192.168.101.7', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-23 06:34:22'),
(33, 1, '', 'Boarding house activated: BH 1', 'Boarding House ID: 85, Name: BH 1, Owner: Namz Baer (namzbaer@gmail.com)', '192.168.101.7', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-23 06:34:40'),
(34, 1, '', 'User account unsuspended: Lizz Uy', 'User ID: 28, Email: hannacuas536@gmail.com', '192.168.101.7', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-23 06:34:57'),
(35, 1, '', 'Boarding house deactivated: Kikyam BH', 'Boarding House ID: 87, Name: Kikyam BH, Owner: Namz Baer (namzbaer@gmail.com), Reason: too', '192.168.101.7', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-23 07:18:31'),
(36, 1, '', 'Boarding house activated: Kikyam BH', 'Boarding House ID: 87, Name: Kikyam BH, Owner: Namz Baer (namzbaer@gmail.com)', '192.168.101.7', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-23 07:20:55'),
(37, 1, '', 'User account suspended: Liza Cuas', 'User ID: 62, Email: christecuas947@gmail.com, Reason: mmm', '192.168.101.7', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-23 07:23:29'),
(38, 1, 'login', 'Super Admin logged in', 'Admin login successful from 192.168.101.7', '192.168.101.7', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-24 03:05:54'),
(39, 1, 'login', 'Super Admin logged in', 'Admin login successful from 192.168.20.67', '192.168.20.67', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-26 00:48:24'),
(40, 1, 'login', 'Super Admin logged in', 'Admin login successful from 192.168.254.122', '192.168.254.122', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-26 07:33:23'),
(41, 1, 'login', 'Super Admin logged in', 'Admin login successful from 192.168.254.114', '192.168.254.114', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-11-26 07:58:37'),
(42, 1, 'login', 'Super Admin logged in', 'Admin login successful from 192.168.254.114', '192.168.254.114', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-11-26 08:00:39'),
(43, 1, 'login', 'Super Admin logged in', 'Admin login successful from 192.168.254.114', '192.168.254.114', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-11-26 08:01:17'),
(44, 1, 'logout', 'Super Admin logged out', 'Admin logout successful', '192.168.254.114', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-11-26 08:19:12'),
(45, 1, 'login', 'Super Admin logged in', 'Admin login successful from 192.168.254.114', '192.168.254.114', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-11-26 08:20:15'),
(46, 1, 'login', 'Super Admin logged in', 'Admin login successful from 192.168.254.114', '192.168.254.114', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-11-26 08:20:51'),
(47, 1, 'login', 'Super Admin logged in', 'Admin login successful from 192.168.101.7', '192.168.101.7', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-26 11:09:14'),
(48, 1, 'login', 'Super Admin logged in', 'Admin login successful from 192.168.101.12', '192.168.101.12', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-11-26 11:14:04'),
(49, 1, 'login', 'Super Admin logged in', 'Admin login successful from 192.168.101.12', '192.168.101.12', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-11-26 11:16:00'),
(50, 1, 'login', 'Super Admin logged in', 'Admin login successful from 192.168.101.12', '192.168.101.12', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-11-26 11:17:00'),
(51, 1, 'login', 'Super Admin logged in', 'Admin login successful from 192.168.101.12', '192.168.101.12', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-11-26 11:20:10'),
(52, 1, 'login', 'Super Admin logged in', 'Admin login successful from 192.168.101.12', '192.168.101.12', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-11-26 11:41:36'),
(53, 1, 'login', 'Super Admin logged in', 'Admin login successful from 192.168.101.12', '192.168.101.12', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-11-26 12:11:15'),
(54, 1, 'login', 'Super Admin logged in', 'Admin login successful from 192.168.101.12', '192.168.101.12', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-11-26 12:29:32'),
(55, 1, 'login', 'Super Admin logged in', 'Admin login successful from 192.168.101.12', '192.168.101.12', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-11-26 12:35:51'),
(56, 1, 'login', 'Super Admin logged in', 'Admin login successful from 192.168.101.7', '192.168.101.7', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-26 12:38:13'),
(57, 1, 'login', 'Super Admin logged in', 'Admin login successful from 192.168.101.12', '192.168.101.12', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-11-26 12:43:33'),
(58, 1, 'login', 'Super Admin logged in', 'Admin login successful from 192.168.101.7', '192.168.101.7', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-26 12:44:14'),
(59, 1, 'login', 'Super Admin logged in', 'Admin login successful from 192.168.101.12', '192.168.101.12', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-11-26 12:48:12'),
(60, 1, 'login', 'Super Admin logged in', 'Admin login successful from 192.168.101.12', '192.168.101.12', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-11-26 12:49:12'),
(61, 1, 'login', 'Super Admin logged in', 'Admin login successful from 192.168.101.12', '192.168.101.12', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-11-26 13:51:50'),
(62, 1, 'login', 'Super Admin logged in', 'Admin login successful from 192.168.101.12', '192.168.101.12', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-11-26 14:17:39'),
(63, 1, 'logout', 'Super Admin logged out', 'Admin logout successful', '192.168.101.7', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-26 14:55:27'),
(64, 1, 'login', 'Super Admin logged in', 'Admin login successful from 192.168.101.5', '192.168.101.5', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-11-26 14:55:32'),
(65, 1, 'login', 'Super Admin logged in', 'Admin login successful from 192.168.101.7', '192.168.101.7', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-26 14:55:40'),
(66, 1, 'logout', 'Super Admin logged out', 'Admin logout successful', '192.168.101.12', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-11-26 15:00:10'),
(67, 1, 'logout', 'Super Admin logged out', 'Admin logout successful', '192.168.101.5', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-11-26 15:01:56'),
(68, 1, 'logout', 'Super Admin logged out', 'Admin logout successful', '192.168.101.7', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-26 15:02:47'),
(69, 1, 'login', 'Super Admin logged in', 'Admin login successful from 192.168.101.5', '192.168.101.5', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-11-26 15:04:06'),
(70, 1, 'logout', 'Super Admin logged out', 'Admin logout successful', '192.168.101.5', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-11-26 15:09:00'),
(71, 1, 'login', 'Super Admin logged in', 'Admin login successful from 192.168.101.7', '192.168.101.7', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-26 15:12:09'),
(72, 1, 'logout', 'Super Admin logged out', 'Admin logout successful', '192.168.101.7', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-26 15:14:25'),
(73, 1, 'login', 'Super Admin logged in', 'Admin login successful from 192.168.101.12', '192.168.101.12', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-11-26 15:23:35'),
(74, 1, 'login', 'Super Admin logged in', 'Admin login successful from 192.168.101.5', '192.168.101.5', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-11-26 15:24:07'),
(75, 1, 'logout', 'Super Admin logged out', 'Admin logout successful', '192.168.101.12', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-11-26 15:26:26'),
(76, 1, 'logout', 'Super Admin logged out', 'Admin logout successful', '192.168.101.5', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-11-26 15:27:07'),
(77, 1, 'login', 'Super Admin logged in', 'Admin login successful from 192.168.101.12', '192.168.101.12', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-11-26 15:29:21'),
(78, 1, 'logout', 'Super Admin logged out', 'Admin logout successful', '192.168.101.12', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-11-26 15:32:56'),
(79, 1, 'login', 'Super Admin logged in', 'Admin login successful from 192.168.101.12', '192.168.101.12', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-11-26 15:37:14'),
(80, 1, 'logout', 'Super Admin logged out', 'Admin logout successful', '192.168.101.12', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-11-26 15:40:17'),
(81, 1, 'login', 'Super Admin logged in', 'Admin login successful from 192.168.101.7', '192.168.101.7', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-26 15:40:50'),
(82, 1, 'login', 'Super Admin logged in', 'Admin login successful from 192.168.101.12', '192.168.101.12', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-11-26 15:41:07'),
(83, 1, 'login', 'Super Admin logged in', 'Admin login successful from 192.168.137.124', '192.168.137.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-27 02:33:42'),
(84, 1, 'login', 'Super Admin logged in', 'Admin login successful from 192.168.137.99', '192.168.137.99', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-11-27 03:34:48'),
(85, 1, 'login', 'Super Admin logged in', 'Admin login successful from 192.168.0.239', '192.168.0.239', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-27 06:09:21'),
(86, 1, 'login', 'Super Admin logged in', 'Admin login successful from 192.168.0.252', '192.168.0.252', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-11-27 06:12:39'),
(87, 1, 'login', 'Super Admin logged in', 'Admin login successful from 192.168.0.252', '192.168.0.252', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-11-27 06:32:29'),
(88, 1, 'login', 'Super Admin logged in', 'Admin login successful from 192.168.0.172', '192.168.0.172', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-11-27 08:15:43'),
(89, 1, 'login', 'Super Admin logged in', 'Admin login successful from 192.168.101.7', '192.168.101.7', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-27 12:37:38'),
(90, 1, 'login', 'Super Admin logged in', 'Admin login successful from 192.168.101.7', '192.168.101.7', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-27 12:37:41'),
(91, 1, 'login', 'Super Admin logged in', 'Admin login successful from 192.168.101.12', '192.168.101.12', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-11-27 13:07:54'),
(92, 1, 'logout', 'Super Admin logged out', 'Admin logout successful', '192.168.101.12', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-11-27 14:44:57'),
(93, 1, 'login', 'Super Admin logged in', 'Admin login successful from 192.168.101.12', '192.168.101.12', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-11-27 14:45:13'),
(94, 1, 'logout', 'Super Admin logged out', 'Admin logout successful', '192.168.101.7', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-27 15:11:31'),
(95, 1, 'login', 'Super Admin logged in', 'Admin login successful from 192.168.101.7', '192.168.101.7', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-27 15:14:01'),
(96, 1, 'logout', 'Super Admin logged out', 'Admin logout successful', '192.168.101.12', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-11-27 15:19:13'),
(97, 1, 'logout', 'Super Admin logged out', 'Admin logout successful', '192.168.101.7', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-27 15:19:17'),
(98, 1, 'login', 'Super Admin logged in', 'Admin login successful from 192.168.101.12', '192.168.101.12', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-11-27 15:24:49'),
(99, 1, 'logout', 'Super Admin logged out', 'Admin logout successful', '192.168.101.12', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-11-27 15:25:04'),
(100, 1, 'login', 'Super Admin logged in', 'Admin login successful from 192.168.101.12', '192.168.101.12', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-11-27 15:27:44'),
(101, 1, 'login', 'Super Admin logged in', 'Admin login successful from 192.168.101.7', '192.168.101.7', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-27 15:27:51'),
(102, 1, 'login', 'Super Admin logged in', 'Admin login successful from 192.168.101.7', '192.168.101.7', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-28 08:32:21'),
(103, 1, 'login', 'Super Admin logged in', 'Admin login successful from 192.168.101.12', '192.168.101.12', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-11-28 12:12:45'),
(104, 1, 'login', 'Super Admin logged in', 'Admin login successful from 192.168.101.12', '192.168.101.12', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-11-28 12:17:53'),
(105, 1, 'login', 'Super Admin logged in', 'Admin login successful from 192.168.101.7', '192.168.101.7', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-29 00:01:50'),
(106, 1, 'login', 'Super Admin logged in', 'Admin login successful from 192.168.254.104', '192.168.254.104', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-12-01 01:08:31'),
(107, 1, 'login', 'Super Admin logged in', 'Admin login successful from 192.168.254.110', '192.168.254.110', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-12-01 01:21:05'),
(108, 1, 'login', 'Super Admin logged in', 'Admin login successful from 192.168.254.104', '192.168.254.104', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-12-01 02:54:23'),
(109, 1, 'login', 'Super Admin logged in', 'Admin login successful from 192.168.20.75', '192.168.20.75', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-12-01 06:25:59'),
(110, 1, '', 'User account unsuspended: Liza Cuas', 'User ID: 62, Email: christecuas947@gmail.com', '192.168.20.75', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-12-01 06:26:16'),
(111, 1, '', 'User account suspended: Ruel Cuas', 'User ID: 44, Email: lizacuas975@gmail.com, Reason: noisy', '192.168.20.75', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-12-01 06:27:32'),
(112, 1, '', 'User account unsuspended: Ruel Cuas', 'User ID: 44, Email: lizacuas975@gmail.com', '192.168.20.75', 'Mozilla/5.0 (Linux; Android 8.0.0; SM-G955U Build/R16NW) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36 Edg/142.0.0.0', '2025-12-01 06:32:11'),
(113, 1, 'login', 'Super Admin logged in', 'Admin login successful from 192.168.101.7', '192.168.101.7', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-12-06 03:20:45'),
(114, 1, 'login', 'Super Admin logged in', 'Admin login successful from 192.168.20.82', '192.168.20.82', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-09 03:26:21'),
(115, 1, 'logout', 'Super Admin logged out', 'Admin logout successful', '192.168.20.82', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-09 03:28:37'),
(116, 1, 'login', 'Super Admin logged in', 'Admin login successful from 192.168.20.82', '192.168.20.82', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-09 03:28:51'),
(117, 1, '', 'User account suspended: Liza Cuas', 'User ID: 62, Email: christecuas947@gmail.com, Reason: bbbbbb', '192.168.20.82', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-09 03:31:06'),
(118, 1, '', 'User account unsuspended: Liza Cuas', 'User ID: 62, Email: christecuas947@gmail.com', '192.168.20.82', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-09 03:32:54'),
(119, 1, 'login', 'Super Admin logged in', 'Admin login successful from 192.168.20.82', '192.168.20.82', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-09 05:10:07'),
(120, 1, 'login', 'Super Admin logged in', 'Admin login successful from 192.168.101.7', '192.168.101.7', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-09 12:40:46'),
(121, 1, 'login', 'Super Admin logged in', 'Admin login successful from 192.168.137.48', '192.168.137.48', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-10 01:07:30'),
(122, 1, 'user_approved', 'User registration approved: Christopher  Mamac', 'Registration ID: 123, Email: mamacgwapo@gmail.com, Role: ', '192.168.137.48', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-10 01:08:53'),
(123, 1, 'logout', 'Super Admin logged out', 'Admin logout successful', '192.168.137.48', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-10 01:20:16'),
(124, 1, 'login', 'Super Admin logged in', 'Admin login successful from 192.168.137.120', '192.168.137.120', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-10 08:03:28'),
(125, 1, 'login', 'Super Admin logged in', 'Admin login successful from 192.168.101.4', '192.168.101.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-29 03:36:41');

-- --------------------------------------------------------

--
-- Table structure for table `boarder_favorites`
--

CREATE TABLE `boarder_favorites` (
  `fav_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL COMMENT 'References registrations.id (not users.user_id)',
  `bh_id` int(11) NOT NULL COMMENT 'References boarding_houses.bh_id',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `boarder_favorites`
--

INSERT INTO `boarder_favorites` (`fav_id`, `user_id`, `bh_id`, `created_at`) VALUES
(16, 103, 85, '2025-11-08 08:11:31'),
(17, 103, 87, '2025-11-08 08:55:40'),
(18, 51, 11, '2025-11-10 05:52:21'),
(19, 51, 12, '2025-11-13 05:45:27'),
(20, 51, 71, '2025-11-21 06:21:10'),
(21, 51, 72, '2025-11-21 06:21:14'),
(22, 51, 73, '2025-11-21 06:21:16'),
(23, 51, 74, '2025-11-21 06:21:18');

-- --------------------------------------------------------

--
-- Table structure for table `boarding_houses`
--

CREATE TABLE `boarding_houses` (
  `bh_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `bh_name` varchar(100) NOT NULL,
  `bh_address` varchar(255) NOT NULL,
  `bh_description` text DEFAULT NULL,
  `bh_rules` text DEFAULT NULL,
  `number_of_bathroom` int(11) NOT NULL,
  `area` double(10,2) DEFAULT NULL,
  `build_year` year(4) DEFAULT NULL,
  `status` enum('Active','Inactive') NOT NULL,
  `bh_created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `boarding_houses`
--

INSERT INTO `boarding_houses` (`bh_id`, `user_id`, `bh_name`, `bh_address`, `bh_description`, `bh_rules`, `number_of_bathroom`, `area`, `build_year`, `status`, `bh_created_at`) VALUES
(11, 1, 'BH CUAS', 'Tinibgan, Calape Bohol', 'ssss', 'sssss', 1, 5.00, '2024', 'Active', '2025-09-23 07:16:21'),
(12, 1, 'BH CUASS', 'Tinibgan', 'sss', 'sss', 2, 10.00, '2024', 'Active', '2025-09-23 07:16:27'),
(13, 1, 'BH CUAS', 'Tinibgan', 'sss', 'sss', 2, 10.00, '2024', 'Active', '2025-09-23 07:16:29'),
(14, 1, 'BH CUAS', 'Tinibgan', 'sss', 'sss', 2, 10.00, '2024', 'Active', '2025-09-23 07:17:42'),
(15, 1, 'BH MANTE', 'Bangi', 'bbb', 'bbb', 2, 14.00, '2025', 'Active', '2025-09-23 07:22:57'),
(16, 1, 'BH MANTE', 'Bangi Calape', 'bbb', 'bbb', 3, 14.00, '2025', 'Active', '2025-09-23 07:24:27'),
(17, 1, 'BH SKY', 'Bentig', 'bbb', 'bbb', 1, 5.00, '2025', 'Active', '2025-09-23 07:27:49'),
(18, 1, 'BH B', 'gg', 'ggg', 'ggg', 1, 5.00, '2024', 'Active', '2025-09-23 07:33:01'),
(19, 1, 'BH H', 'ggg', 'ggg', 'ggg', 1, 12.00, '2024', 'Active', '2025-09-23 07:34:57'),
(20, 1, 'BH C', 'hh', 'hh', 'hh', 1, 1.00, '2024', 'Active', '2025-09-23 07:38:07'),
(21, 1, 'BH G', 'Gg', 'gg', 'gg', 1, 1.00, '2024', 'Active', '2025-09-23 07:39:58'),
(22, 1, 'BH G', 'Gg', 'gg', 'gg', 1, 1.00, '2024', 'Active', '2025-09-23 07:40:32'),
(23, 1, 'BH J', 'jj', 'jj', 'jj', 1, 1.00, '2004', 'Active', '2025-09-23 07:42:45'),
(26, 1, 'BH K', 'kk', 'kk', 'kk', 1, 1.00, '2024', 'Active', '2025-09-23 07:56:35'),
(28, 1, 'BH K', 'kk', 'kk', 'kk', 1, 1.00, '2024', 'Active', '2025-09-23 07:56:36'),
(29, 1, 'BH K', 'kk', 'kk', 'kk', 1, 1.00, '2024', 'Active', '2025-09-23 07:56:36'),
(32, 1, 'BH K', 'kk', 'kk', 'kk', 1, 1.00, '2024', 'Active', '2025-09-23 07:57:22'),
(34, 1, 'BH L', 'yy', 'yy', 'yy', 1, 1.00, '2004', 'Active', '2025-09-23 08:02:54'),
(35, 1, 'BH L', 'yy', 'yy', 'yy', 1, 1.00, '2004', 'Active', '2025-09-23 08:03:03'),
(37, 1, 'BH L', 'yy', 'yy', 'yy', 1, 1.00, '2004', 'Active', '2025-09-23 08:03:13'),
(38, 1, 'BH L', 'yy', 'yy', 'yy', 1, 1.00, '2004', 'Active', '2025-09-23 08:03:27'),
(39, 1, 'BH L', 'yy', 'yy', 'yy', 1, 1.00, '2004', 'Active', '2025-09-23 08:05:16'),
(40, 1, 'BH L', 'kk', 'kk', 'kk', 1, 1.00, '2004', 'Active', '2025-09-23 08:08:38'),
(41, 1, 'BH L', 'kk', 'kk', 'kk', 1, 1.00, '2004', 'Active', '2025-09-23 08:08:47'),
(42, 1, 'GB', 'rr', 'rr', 'rr', 2, 2.00, '0000', 'Active', '2025-09-23 08:10:44'),
(43, 1, 'FG', 'uu', 'uu', 'uu', 1, 1.00, '2004', 'Active', '2025-09-23 08:23:31'),
(44, 1, 'BB', 'bb', 'bb', 'bb', 1, 6.00, '2023', 'Active', '2025-09-23 08:26:11'),
(45, 1, 'BB', 'bb', 'bb', 'bb', 1, 6.00, '2023', 'Active', '2025-09-23 08:31:34'),
(46, 1, 'AA', 'qq', 'qq', 'qq', 1, 23.00, '2023', 'Active', '2025-09-23 08:54:06'),
(47, 1, 'AA', 'qq', 'qq', 'qq', 1, 23.00, '2023', 'Active', '2025-09-23 08:54:52'),
(48, 1, 'AA', 'qq', 'qq', 'qq', 1, 23.00, '2023', 'Active', '2025-09-23 08:57:18'),
(49, 1, 'SS', 'ss', 'ss', 'ss', 1, 1.00, '2004', 'Active', '2025-09-23 09:01:39'),
(50, 1, 'DD', 'ee', 'ee', 'ee', 2, 20.00, '2020', 'Active', '2025-09-23 09:05:46'),
(52, 1, 'hh', 'ff', 'ff', 'ff', 2, 1.00, '2024', 'Active', '2025-09-23 09:11:38'),
(53, 1, 'DD', 'dd', 'dd', 'dd', 2, 1.00, '2022', 'Active', '2025-09-23 09:19:32'),
(54, 1, 'JJ', 'jj', 'jj', 'jj', 1, 1.00, '2001', 'Active', '2025-09-23 09:25:48'),
(55, 1, 'TODAY', 'today', 'today', 'today', 2, 4.00, '2024', 'Active', '2025-09-26 04:17:14'),
(56, 1, 'aa', 'aa', 'aa', 'aa', 2, 1.00, '2024', 'Active', '2025-09-27 13:12:29'),
(57, 1, 'qq', 'qq', 'qq', 'qq', 1, 12.00, '2024', 'Active', '2025-09-27 13:29:17'),
(58, 1, 'ww', 'ww', 'ww', 'ww', 2, 10.00, '2023', 'Active', '2025-09-28 01:16:03'),
(59, 1, 'ee', 'ee', 'uyy', 'uyy', 2, 10.00, '2024', 'Active', '2025-09-28 01:21:03'),
(60, 1, 'yy', 'yy', 'yy', 'yy', 2, 2.00, '2022', 'Active', '2025-09-28 04:59:43'),
(61, 1, 'BLENDER', 'ddd', 'ddd', 'dddd', 1, 2.00, '2023', 'Active', '2025-09-30 01:37:57'),
(63, 1, 'ggg', 'gg', 'gg', 'gg', 2, 1.00, '2004', 'Active', '2025-09-30 01:56:57'),
(64, 1, 'jjj', 'hshssh', 'hhh', 'hhh', 2, 2.00, '2023', 'Active', '2025-09-30 02:12:38'),
(65, 1, 'uu', 'gg', 'ggg', 'ggg', 2, 1.00, '2023', 'Active', '2025-09-30 02:14:13'),
(66, 1, 'p', 'o', 'o', 'o', 2, 10.00, '2024', 'Active', '2025-09-30 04:32:37'),
(67, 1, 'hays', 'hays', 'hays', 'hays', 2, 10.00, '2023', 'Active', '2025-09-30 04:46:48'),
(68, 1, 'Y', 'gg', 'bb', 'hh', 1, 2.00, '2023', 'Active', '2025-09-30 04:54:12'),
(70, 1, 'hagu', 'hh', 'hh', 'hh', 2, 1.00, '2023', 'Active', '2025-09-30 04:58:15'),
(71, 1, 'ho', 'ho', 'ho', 'ho', 2, 20.00, '2023', 'Active', '2025-09-30 05:00:08'),
(72, 1, 'BH DO', 'Calape', 'homey', 'm', 2, 10.00, '2023', 'Active', '2025-10-02 22:13:13'),
(73, 1, 'BH KIMB', 'Bangi', 'nnn', 'nnn', 2, 10.00, '2004', 'Active', '2025-10-03 01:09:28'),
(74, 1, 'Sunset Boarding House', '123 Main Street, Cebu City', 'A cozy boarding house near the university with modern amenities.', 'No smoking, No pets, Quiet hours 10PM-6AM', 3, 200.50, '2020', 'Active', '2025-10-04 12:46:17'),
(75, 4, 'Mountain View Lodge', '456 Oak Avenue, Cebu City', 'Beautiful boarding house with mountain views and fresh air.', 'Respect other residents, Keep common areas clean', 2, 150.75, '2019', 'Active', '2025-10-04 12:46:17'),
(76, 7, 'City Center Residence', '789 Pine Street, Cebu City', 'Conveniently located in the city center with easy access to everything.', 'No loud music, Clean up after yourself', 4, 300.00, '2021', 'Active', '2025-10-04 12:46:17'),
(77, 1, 'hh', 'hh', 'hh', 'hh', 2, 10.00, '2023', 'Active', '2025-10-05 03:13:35'),
(78, 1, 'bh', 'ttyyy', 'yyynnn', 'yyy', 2, 2.00, '2023', 'Active', '2025-10-08 16:48:19'),
(84, 1, 'test', 'calape', 'hg', 'hh', 2, 10.00, '2023', 'Active', '2025-10-09 02:30:04'),
(85, 29, 'BH 1', 'Purok 2 Patag, Tinibgan, Calape, Bohol', 'A boarding house is a house (frequently a family home) in which lodgers rent one or more rooms on a nightly basis and sometimes for extended periods of weeks, months, or years. The common parts of the house are maintained, and some services, such as laundry and cleaning, may be supplied.', 'yy', 2, 10.00, '2023', 'Active', '2025-10-12 03:48:57'),
(87, 29, 'Kikyam BH', 'Lucob, Calape, Bohol', 'This is a two storey building with aircon. Shalan!', 'No loud music from 9:00 PM - 6 AM', 2, 100.00, '2020', 'Active', '2025-10-28 02:25:46'),
(88, 29, 'Sample 1', 'San Isidro, Calape, Bohol', 'Naay free tubig\nbayad kuryente', '10 pm taman ang curfew', 2, 50.00, '2000', 'Active', '2025-12-09 21:23:15');

-- --------------------------------------------------------

--
-- Table structure for table `boarding_house_images`
--

CREATE TABLE `boarding_house_images` (
  `image_id` int(11) NOT NULL,
  `bh_id` int(11) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `boarding_house_images`
--

INSERT INTO `boarding_house_images` (`image_id`, `bh_id`, `image_path`, `uploaded_at`) VALUES
(1, 23, 'uploads/boarding_house_images/bh_23_68d24f780fd2f.jpg', '2025-09-23 07:42:48'),
(2, 40, 'uploads/boarding_house_images/bh_40_68d2558daaa50.jpg', '2025-09-23 08:08:45'),
(3, 40, 'uploads/boarding_house_images/bh_40_68d25592d75ea.jpg', '2025-09-23 08:08:50'),
(4, 41, 'uploads/boarding_house_images/bh_41_68d25596a90f6.jpg', '2025-09-23 08:08:54'),
(5, 41, 'uploads/boarding_house_images/bh_41_68d2559b9c9e4.jpg', '2025-09-23 08:08:59'),
(6, 42, 'uploads/boarding_house_images/bh_42_68d256071445a.jpg', '2025-09-23 08:10:47'),
(7, 43, 'uploads/boarding_house_images/bh_43_68d259096cc4d.jpg', '2025-09-23 08:23:37'),
(8, 43, 'uploads/boarding_house_images/bh_43_68d25910057e4.jpg', '2025-09-23 08:23:44'),
(9, 44, 'uploads/boarding_house_images/bh_44_68d259aa3260b.jpg', '2025-09-23 08:26:18'),
(10, 44, 'uploads/boarding_house_images/bh_44_68d259af8b8a6.jpg', '2025-09-23 08:26:23'),
(11, 45, 'uploads/boarding_house_images/bh_45_68d25aebdf439.jpg', '2025-09-23 08:31:39'),
(12, 45, 'uploads/boarding_house_images/bh_45_68d25af178cac.jpg', '2025-09-23 08:31:45'),
(13, 46, 'uploads/boarding_house_images/bh_46_68d260349584a.jpg', '2025-09-23 08:54:12'),
(14, 46, 'uploads/boarding_house_images/bh_46_68d2603a8c884.jpg', '2025-09-23 08:54:18'),
(15, 46, 'uploads/boarding_house_images/bh_46_68d2604037c39.jpg', '2025-09-23 08:54:24'),
(16, 46, 'uploads/boarding_house_images/bh_46_68d26045d49b6.jpg', '2025-09-23 08:54:29'),
(17, 47, 'uploads/boarding_house_images/bh_47_68d26062d57a4.jpg', '2025-09-23 08:54:58'),
(18, 47, 'uploads/boarding_house_images/bh_47_68d2606820154.jpg', '2025-09-23 08:55:04'),
(19, 47, 'uploads/boarding_house_images/bh_47_68d2606ed2535.jpg', '2025-09-23 08:55:10'),
(20, 47, 'uploads/boarding_house_images/bh_47_68d2607457902.jpg', '2025-09-23 08:55:16'),
(21, 48, 'uploads/boarding_house_images/bh_48_68d260f53b0ab.jpg', '2025-09-23 08:57:25'),
(22, 48, 'uploads/boarding_house_images/bh_48_68d260fb671b6.jpg', '2025-09-23 08:57:31'),
(23, 48, 'uploads/boarding_house_images/bh_48_68d26101d45d8.jpg', '2025-09-23 08:57:37'),
(24, 48, 'uploads/boarding_house_images/bh_48_68d2610d8f72c.jpg', '2025-09-23 08:57:49'),
(25, 49, 'uploads/boarding_house_images/bh_49_68d261f95b0e5.jpg', '2025-09-23 09:01:45'),
(26, 49, 'uploads/boarding_house_images/bh_49_68d261ff47bad.jpg', '2025-09-23 09:01:51'),
(27, 53, 'uploads/boarding_house_images/bh_53_68d2662b1ba04.jpg', '2025-09-23 09:19:39'),
(28, 53, 'uploads/boarding_house_images/bh_53_68d2663361e30.jpg', '2025-09-23 09:19:47'),
(29, 54, 'uploads/boarding_house_images/bh_54_68d267a205cc3.jpg', '2025-09-23 09:25:54'),
(30, 54, 'uploads/boarding_house_images/bh_54_68d267a77adc3.jpg', '2025-09-23 09:25:59'),
(31, 55, 'uploads/boarding_house_images/bh_55_68d613cd96fbf.jpg', '2025-09-26 04:17:17'),
(32, 55, 'uploads/boarding_house_images/bh_55_68d613d046db3.jpg', '2025-09-26 04:17:20'),
(33, 56, 'uploads/boarding_house_images/bh_56_68d7e2c316bf5.jpg', '2025-09-27 13:12:35'),
(34, 56, 'uploads/boarding_house_images/bh_56_68d7e2c812370.jpg', '2025-09-27 13:12:40'),
(35, 59, 'uploads/boarding_house_images/bh_59_68d88d82ab3aa.jpg', '2025-09-28 01:21:06'),
(36, 59, 'uploads/boarding_house_images/bh_59_68d88d8503f68.jpg', '2025-09-28 01:21:09'),
(37, 59, 'uploads/boarding_house_images/bh_59_68d88d8781469.jpg', '2025-09-28 01:21:11'),
(38, 60, 'uploads/boarding_house_images/bh_60_68d8c0e6752c0.jpg', '2025-09-28 05:00:22'),
(41, 11, 'uploads/boarding_house_images/bh_11_68d8c1ed07598.jpg', '2025-09-28 05:04:45'),
(42, 11, 'uploads/boarding_house_images/bh_11_68da7ed55e253.jpg', '2025-09-29 12:43:01'),
(44, 12, 'uploads/boarding_house_images/bh_12_68da7fa24259f.jpg', '2025-09-29 12:46:26'),
(45, 12, 'uploads/boarding_house_images/bh_12_68da7fa64a9fc.jpg', '2025-09-29 12:46:30'),
(46, 12, 'uploads/boarding_house_images/bh_12_68da7facc64f8.jpg', '2025-09-29 12:46:36'),
(47, 12, 'uploads/boarding_house_images/bh_12_68da7fad6dd0f.jpg', '2025-09-29 12:46:37'),
(48, 12, 'uploads/boarding_house_images/bh_12_68da7fb054e3a.jpg', '2025-09-29 12:46:40'),
(49, 12, 'uploads/boarding_house_images/bh_12_68da7fb2b9586.jpg', '2025-09-29 12:46:42'),
(50, 13, 'uploads/boarding_house_images/bh_13_68da81d496477.jpg', '2025-09-29 12:55:48'),
(51, 13, 'uploads/boarding_house_images/bh_13_68da81d722967.jpg', '2025-09-29 12:55:51'),
(52, 13, 'uploads/boarding_house_images/bh_13_68da81d9d8b05.jpg', '2025-09-29 12:55:53'),
(53, 14, 'uploads/boarding_house_images/bh_14_68da835705d66.jpg', '2025-09-29 13:02:15'),
(54, 14, 'uploads/boarding_house_images/bh_14_68da8359e7824.jpg', '2025-09-29 13:02:17'),
(55, 12, 'uploads/boarding_house_images/bh_12_68da8624153b9.jpg', '2025-09-29 13:14:12'),
(56, 15, 'uploads/boarding_house_images/bh_15_68da872fb1706.jpg', '2025-09-29 13:18:39'),
(59, 16, 'uploads/boarding_house_images/bh_16_68da8f356d75c.jpg', '2025-09-29 13:52:53'),
(60, 16, 'uploads/boarding_house_images/bh_16_68da8f37f1d74.jpg', '2025-09-29 13:52:56'),
(61, 22, 'uploads/boarding_house_images/bh_22_68da9155827f3.jpg', '2025-09-29 14:01:57'),
(62, 18, 'uploads/boarding_house_images/bh_18_68da98871b131.jpg', '2025-09-29 14:32:39'),
(63, 61, 'uploads/boarding_house_images/bh_61_68db3478b3e34.jpg', '2025-09-30 01:38:00'),
(64, 61, 'uploads/boarding_house_images/bh_61_68db347d5d74e.jpg', '2025-09-30 01:38:05'),
(67, 61, 'uploads/boarding_house_images/bh_61_68db34c4a8539.jpg', '2025-09-30 01:39:16'),
(68, 63, 'uploads/boarding_house_images/bh_63_68db38ecd65ae.jpg', '2025-09-30 01:57:00'),
(69, 64, 'uploads/boarding_house_images/bh_64_68db3c99e7d43.jpg', '2025-09-30 02:12:41'),
(70, 65, 'uploads/boarding_house_images/bh_65_68db3cf7b3a74.jpg', '2025-09-30 02:14:15'),
(71, 65, 'uploads/boarding_house_images/bh_65_68db3d259544f.jpg', '2025-09-30 02:15:01'),
(72, 72, 'uploads/boarding_house_images/bh_72_68def8fc1263f.jpg', '2025-10-02 22:13:16'),
(73, 73, 'uploads/boarding_house_images/bh_73_68df224bdd350.jpg', '2025-10-03 01:09:31'),
(75, 77, 'uploads/boarding_house_images/bh_77_68e1e2f8c0ac6.jpg', '2025-10-05 03:16:08'),
(76, 77, 'uploads/boarding_house_images/bh_77_68e1e4231be7b.jpg', '2025-10-05 03:21:07'),
(77, 78, 'uploads/boarding_house_images/bh_78_68e695df04939.jpg', '2025-10-08 16:48:31'),
(78, 78, 'uploads/boarding_house_images/bh_78_68e695f66b119.jpg', '2025-10-08 16:48:54'),
(79, 84, 'uploads/boarding_house_images/bh_84_68e71e2e738ab.jpg', '2025-10-09 02:30:06'),
(80, 85, 'uploads/boarding_house_images/bh_85_68eb25319895f.jpg', '2025-10-12 03:49:05'),
(81, 85, 'uploads/boarding_house_images/bh_85_68eb286b32357.jpg', '2025-10-12 04:02:51'),
(82, 87, 'uploads/boarding_house_images/bh_87_690029c015372.jpg', '2025-10-28 02:26:08'),
(83, 85, 'uploads/boarding_house_images/bh_85_690c83efa8140.jpg', '2025-11-06 11:18:07'),
(84, 85, 'uploads/boarding_house_images/bh_85_690c83f66a30d.jpg', '2025-11-06 11:18:14'),
(85, 85, 'uploads/boarding_house_images/bh_85_690c83fc1ad50.jpg', '2025-11-06 11:18:20'),
(86, 85, 'uploads/boarding_house_images/bh_85_690c84067b84f.jpg', '2025-11-06 11:18:30'),
(87, 88, 'uploads/boarding_house_images/bh_88_693893516608d.jpg', '2025-12-09 21:23:29'),
(88, 88, 'uploads/boarding_house_images/bh_88_6938935ca1247.jpg', '2025-12-09 21:23:40'),
(89, 88, 'uploads/boarding_house_images/bh_88_69389365c1e02.jpg', '2025-12-09 21:23:49');

-- --------------------------------------------------------

--
-- Table structure for table `boarding_house_rooms`
--

CREATE TABLE `boarding_house_rooms` (
  `bhr_id` int(11) NOT NULL,
  `bh_id` int(11) NOT NULL,
  `room_category` enum('Private Room','Bed Spacer') NOT NULL,
  `room_name` varchar(100) NOT NULL,
  `price` double(10,2) NOT NULL,
  `capacity` int(11) NOT NULL,
  `room_description` text DEFAULT NULL,
  `total_rooms` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `boarding_house_rooms`
--

INSERT INTO `boarding_house_rooms` (`bhr_id`, `bh_id`, `room_category`, `room_name`, `price`, `capacity`, `room_description`, `total_rooms`, `created_at`) VALUES
(1, 41, 'Private Room', 'Single Room', 5000.00, 2, '0', 3, '2025-09-23 08:09:01'),
(2, 42, 'Private Room', 'Single Room', 5000.00, 3, '0', 4, '2025-09-23 08:10:49'),
(3, 43, 'Private Room', 'Single Room', 4000.00, 2, '0', 3, '2025-09-23 08:23:46'),
(4, 44, 'Private Room', 'Single Room', 4000.00, 3, '0', 3, '2025-09-23 08:26:25'),
(5, 45, 'Private Room', 'Single Room', 4000.00, 3, '0', 3, '2025-09-23 08:31:47'),
(6, 46, 'Private Room', 'Double', 10000.00, 5, '0', 4, '2025-09-23 08:54:31'),
(7, 47, 'Private Room', 'Single', 10000.00, 5, '0', 4, '2025-09-23 08:55:18'),
(8, 48, 'Private Room', 'Single', 10000.00, 5, '0', 4, '2025-09-23 08:57:51'),
(9, 49, 'Bed Spacer', 'Group A', 5000.00, 5, '0', 5, '2025-09-23 09:01:53'),
(10, 50, 'Private Room', 'Single', 5000.00, 3, '0', 1, '2025-09-23 09:05:48'),
(12, 52, 'Private Room', 'Double', 4000.00, 2, '0', 1, '2025-09-23 09:11:40'),
(13, 53, 'Private Room', 'Double', 5000.00, 4, '0', 1, '2025-09-23 09:19:49'),
(14, 54, 'Bed Spacer', 'Group B', 8000.00, 4, '0', 1, '2025-09-23 09:26:01'),
(15, 55, 'Private Room', 'Family Room', 8000.00, 5, '0', 2, '2025-09-26 04:17:22'),
(16, 56, 'Private Room', 'SINGLE', 1000.00, 1, '0', 2, '2025-09-27 13:12:42'),
(17, 57, 'Private Room', 'Single Room', 2900.00, 3, '0', 1, '2025-09-27 13:29:19'),
(18, 58, 'Private Room', 'Family', 9000.00, 5, '0', 2, '2025-09-28 01:16:05'),
(19, 59, 'Private Room', 'Family', 2000.00, 3, '0', 1, '2025-09-28 01:21:13'),
(20, 60, 'Bed Spacer', 'Group C', 2000.00, 6, '0', 1, '2025-09-28 04:59:46'),
(21, 63, 'Private Room', 'Single', 2000.00, 2, '0', 1, '2025-09-30 01:57:02'),
(22, 64, 'Private Room', 'Single', 2000.00, 2, '0', 1, '2025-09-30 02:12:44'),
(23, 65, 'Private Room', 'Single', 2999.00, 3, '0', 1, '2025-09-30 02:14:17'),
(24, 11, 'Private Room', 'Single A', 2000.00, 3, 'homey', 3, '2025-09-30 03:30:49'),
(25, 11, 'Bed Spacer', 'Group B', 1000.00, 5, 'bigg', 1, '2025-09-30 03:44:05'),
(26, 13, 'Private Room', 'Family', 10000.00, 5, '0', 1, '2025-09-30 03:48:18'),
(28, 12, 'Private Room', 'Single A', 5000.00, 2, '1', 2, '2025-09-30 04:25:25'),
(29, 66, 'Private Room', 'Single', 5000.00, 3, '0', 1, '2025-09-30 04:32:39'),
(31, 11, '', 'Test Room', 1000.00, 2, '0', 1, '2025-09-30 04:39:43'),
(33, 67, 'Private Room', 'Single', 5000.00, 2, '10', 1, '2025-09-30 04:46:50'),
(34, 68, 'Private Room', 'Single', 2000.00, 2, 'home', 1, '2025-09-30 04:54:15'),
(36, 70, 'Private Room', 'Single', 3000.00, 2, 'home', 1, '2025-09-30 04:58:17'),
(37, 71, 'Private Room', 'Single', 2000.00, 2, 'ho', 1, '2025-09-30 05:00:10'),
(38, 72, 'Private Room', 'Single Room', 5000.00, 2, 'good for', 2, '2025-10-02 22:13:18'),
(39, 72, 'Bed Spacer', 'Group', 1000.00, 5, 'good', 2, '2025-10-02 22:14:59'),
(40, 11, 'Private Room', 'Kim Hauz and Room', 900.00, 10, 'Room availability', 12, '2025-10-03 00:52:21'),
(41, 12, 'Private Room', 'Single A', 1000.00, 2, 'hhh', 1, '2025-10-03 00:58:20'),
(42, 73, 'Private Room', 'Family Room', 8000.00, 3, 'family', 2, '2025-10-03 01:09:34'),
(43, 77, 'Private Room', 'Single', 10000.00, 5, 'homeyy is the key', 1, '2025-10-05 03:13:42'),
(44, 78, 'Private Room', 'Single A', 4000.00, 2, 'homeeeeyyy', 1, '2025-10-08 16:48:33'),
(45, 84, 'Private Room', 'Single A', 4000.00, 2, 'homeee', 1, '2025-10-09 02:30:08'),
(46, 85, 'Private Room', 'single a', 2009.00, 2, 'hhhhooo', 1, '2025-10-12 03:49:07'),
(47, 85, 'Bed Spacer', 'Group A', 1000.00, 2, 'manyyy', 2, '2025-10-12 03:54:52'),
(48, 85, 'Private Room', 'Room 2', 5000.00, 2, 'Just a vibe', 1, '2025-10-24 06:47:52'),
(49, 87, 'Private Room', 'Private Room 01', 5000.00, 5, 'Can occupy 5 person', 7, '2025-10-28 02:26:10'),
(50, 88, 'Private Room', 'Single Room', 5000.00, 3, 'Justtt', 2, '2025-12-09 21:23:52'),
(51, 88, 'Bed Spacer', 'Group A', 1000.00, 6, 'justt', 2, '2025-12-09 21:24:05');

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `booking_id` int(11) NOT NULL,
  `room_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `booking_status` enum('Pending','Confirmed','Cancelled','Completed') NOT NULL DEFAULT 'Pending',
  `booking_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`booking_id`, `room_id`, `user_id`, `start_date`, `end_date`, `booking_status`, `booking_date`) VALUES
(23, 83, 59, '2025-11-10', '2025-12-29', 'Completed', '2025-11-10 11:22:21'),
(24, 82, 44, '2025-11-10', '2026-01-31', 'Confirmed', '2025-11-10 11:58:57'),
(30, 86, 28, '2025-11-13', '2026-03-21', 'Confirmed', '2025-11-13 03:04:04'),
(34, 85, 35, '2025-11-13', '2025-11-14', 'Completed', '2025-11-13 03:47:15'),
(36, 89, 28, '2025-11-13', '2025-11-14', 'Completed', '2025-11-13 06:16:25'),
(38, 87, 59, '2025-11-15', '2025-11-16', 'Completed', '2025-11-15 11:30:58'),
(41, 88, 35, '2025-11-15', '2025-11-16', 'Completed', '2025-11-15 13:55:17'),
(46, 90, 38, '2025-11-16', '2026-01-31', 'Confirmed', '2025-11-16 05:08:10'),
(48, 91, 35, '2025-11-17', '2025-11-18', 'Completed', '2025-11-17 12:31:06'),
(49, 85, 38, '2025-11-18', '2025-12-13', 'Completed', '2025-11-18 11:47:27'),
(50, 89, 35, '2025-11-28', '2025-11-29', 'Completed', '2025-11-28 08:35:30'),
(58, 94, 62, '2025-12-10', '2025-12-13', 'Completed', '2025-12-10 01:15:47');

-- --------------------------------------------------------

--
-- Table structure for table `bs_permits`
--

CREATE TABLE `bs_permits` (
  `permit_id` int(11) NOT NULL,
  `reg_id` int(11) NOT NULL COMMENT 'Foreign key referencing registrations.id',
  `permit_file` varchar(255) NOT NULL COMMENT 'Path to business permit image file',
  `permit_number` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Permit number/index (1, 2, or 3)',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `bs_permits`
--

INSERT INTO `bs_permits` (`permit_id`, `reg_id`, `permit_file`, `permit_number`, `created_at`, `updated_at`) VALUES
(1, 117, 'uploads/business_permits/691d6a503c2f5_permit1.jpg', 1, '2025-11-19 06:57:26', '2025-11-19 06:57:26'),
(2, 117, 'uploads/business_permits/691d6a503e392_permit2.jpg', 2, '2025-11-19 06:57:26', '2025-11-19 06:57:26'),
(7, 123, 'uploads/business_permits/6938c7820e439_permit1.jpg', 1, '2025-12-10 01:06:20', '2025-12-10 01:06:20'),
(8, 124, 'uploads/business_permits/695a27d6ebb82_permit1.jpg', 1, '2026-01-04 08:42:04', '2026-01-04 08:42:04');

-- --------------------------------------------------------

--
-- Table structure for table `chat_groups`
--

CREATE TABLE `chat_groups` (
  `gc_id` int(11) NOT NULL,
  `bh_id` int(11) NOT NULL,
  `gc_name` varchar(100) NOT NULL,
  `gc_created_by` int(11) NOT NULL,
  `gc_created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `chat_groups`
--

INSERT INTO `chat_groups` (`gc_id`, `bh_id`, `gc_name`, `gc_created_by`, `gc_created_at`) VALUES
(4, 11, 'BH CUAS Chat', 1, '2025-10-04 12:50:44'),
(5, 12, 'BH CUASS Residents', 1, '2025-10-04 12:50:44'),
(6, 15, 'BH MANTE Discussion', 1, '2025-10-03 12:50:44'),
(7, 11, 'BH CUAS Chat', 1, '2025-10-04 12:56:44'),
(8, 12, 'BH CUASS Residents', 1, '2025-10-04 12:56:44'),
(9, 15, 'BH MANTE Discussion', 1, '2025-10-03 12:56:44'),
(11, 85, 'Test Group A', 29, '2025-10-14 03:58:45'),
(13, 85, 'Group C', 29, '2025-10-14 07:24:42'),
(15, 85, 'GGGG', 29, '2025-11-19 12:38:00'),
(16, 85, 'Jjj', 29, '2025-11-21 01:22:03'),
(17, 85, 'Jjj', 29, '2025-11-21 01:22:05'),
(19, 85, '3B', 29, '2025-12-06 04:36:18');

-- --------------------------------------------------------

--
-- Table structure for table `device_tokens`
--

CREATE TABLE `device_tokens` (
  `token_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `device_token` varchar(255) NOT NULL,
  `device_type` enum('android','ios','web') DEFAULT 'android',
  `app_version` varchar(50) DEFAULT '1.0.0',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `device_tokens`
--

INSERT INTO `device_tokens` (`token_id`, `user_id`, `device_token`, `device_type`, `app_version`, `is_active`, `created_at`, `updated_at`) VALUES
(10, 1, 'doIZWxHNRkqo_lVUVcNn6a:APA91bGvBwcxisdLz9oNw6CJB1gKSaqz0HmNSLqgOfua9_R_X97IWRIas6HSV0CS4m1LoSMwI2bX959PyMn-vDmxy2K8yIkptrFx8nyzNyaWib5IYH3-0PM', 'android', '1.0.0', 0, '2025-10-09 02:53:46', '2025-10-09 03:02:48'),
(11, 1, 'cfE4VW8eRFeGZjIiX1nWoi:APA91bFpYILFXsXlM5oOcoDbaAPtoUsFq2ylML7OG4kOajLO72qOziZY5jscHR5VDAkpmM8FTZUhdbitQxUaYFPqdBcUQPB-slJWrrz5thBNus6J380csCQ', 'android', '1.0.0', 0, '2025-10-09 03:02:48', '2025-10-09 03:05:51'),
(12, 1, 'cvivWukjRtuy1HWtqnBvZC:APA91bG-4_hUVl1_ElHRbEthGqwOuuGMUwTveK3bYNG-GXYPxXQQeRoQ2SJxmM_coHNE7YCJXRiiLGJyaKcMwYsbxmzxbIRbblxWsOpwSdnU3oAukVHG45I', 'android', '1.0.0', 0, '2025-10-09 03:05:51', '2025-11-17 12:41:06'),
(13, 29, 'cvivWukjRtuy1HWtqnBvZC:APA91bG-4_hUVl1_ElHRbEthGqwOuuGMUwTveK3bYNG-GXYPxXQQeRoQ2SJxmM_coHNE7YCJXRiiLGJyaKcMwYsbxmzxbIRbblxWsOpwSdnU3oAukVHG45I', 'android', '1.0.0', 0, '2025-10-12 03:33:02', '2025-10-12 05:10:07'),
(14, 24, 'cvivWukjRtuy1HWtqnBvZC:APA91bG-4_hUVl1_ElHRbEthGqwOuuGMUwTveK3bYNG-GXYPxXQQeRoQ2SJxmM_coHNE7YCJXRiiLGJyaKcMwYsbxmzxbIRbblxWsOpwSdnU3oAukVHG45I', 'android', '1.0.0', 0, '2025-10-12 04:36:07', '2025-10-14 02:24:32'),
(15, 6, 'cvivWukjRtuy1HWtqnBvZC:APA91bG-4_hUVl1_ElHRbEthGqwOuuGMUwTveK3bYNG-GXYPxXQQeRoQ2SJxmM_coHNE7YCJXRiiLGJyaKcMwYsbxmzxbIRbblxWsOpwSdnU3oAukVHG45I', 'android', '1.0.0', 1, '2025-10-12 04:37:15', '2025-10-12 04:37:15'),
(16, 29, 'f4s7iqzjRtiPhdh0hIia0t:APA91bEhK5oDk51TwRrtatuoJ1kRW7yPve8zhJ-Fi1NAhFwXJfPv-uVQ76rCTe1SPUxbWdahWG6Pz1WsiOZlB1cbvAgaG4m-tmlRGmNmQGSKBSIhjPDHOiI', 'android', '1.0.0', 0, '2025-10-12 05:10:07', '2025-10-12 05:25:49'),
(17, 29, 'cLsLWCccSKKVeX-J0jNLY2:APA91bHs8noetyjaDSli4BhNW1-d6_IjUBjxg2p4sIc5yonRjsh8llOelWp50fiAo__dToRGpm6hDiTTAaGONxqi7vD3fP8qcEFiMxwpCZjtJbvhNqptlhU', 'android', '1.0.0', 0, '2025-10-12 05:25:49', '2025-10-12 05:38:13'),
(18, 29, 'dAXDgbwuQLyxAEpSsU24Am:APA91bHtj93rIkmbpb5x7f5WszdR1eM5929L-cTWkwrk_d4Qkpq8ZR939K48_ruM07BTmIhYscW6_r4xSvYi-3iOo2ehnXWcV0HBbQ9usaRwV1bbXxxS1Ak', 'android', '1.0.0', 0, '2025-10-12 05:38:13', '2025-10-27 01:36:23'),
(19, 24, 'dAXDgbwuQLyxAEpSsU24Am:APA91bHtj93rIkmbpb5x7f5WszdR1eM5929L-cTWkwrk_d4Qkpq8ZR939K48_ruM07BTmIhYscW6_r4xSvYi-3iOo2ehnXWcV0HBbQ9usaRwV1bbXxxS1Ak', 'android', '1.0.0', 1, '2025-10-14 02:24:32', '2025-10-14 02:24:32'),
(20, 29, 'f7SS5GQyRL6yFRqlf10SZ9:APA91bHDlsLELpVloaU2Dz97xSIgK2wJnUihuPhwGGCAgTSQSPXZdKOvyHmVkMbIcQj-ETALUG_cJLhiJzQ302Xf4sZFvWT_TtoOnWJQSRedsHJj0Zkl-zw', 'android', '1.0.0', 0, '2025-10-24 07:10:22', '2025-11-01 06:31:18'),
(21, 29, 'eLd7YhTVRHqp7J75n5t0y3:APA91bF4ovvMnFaHY7IeMoxWGjJRiR4tYAPL-jEDDTh2kGClJLkKH6OZISQeb5YEbtpyLAx_0mWIzpDfVfkWtLxeGUusP8ShvKkVMmaS3WBkxplNaTFSP2c', 'android', '1.0.0', 1, '2025-10-24 23:14:44', '2025-12-06 09:39:15'),
(22, 36, 'dAXDgbwuQLyxAEpSsU24Am:APA91bHtj93rIkmbpb5x7f5WszdR1eM5929L-cTWkwrk_d4Qkpq8ZR939K48_ruM07BTmIhYscW6_r4xSvYi-3iOo2ehnXWcV0HBbQ9usaRwV1bbXxxS1Ak', 'android', '1.0.0', 0, '2025-10-27 00:20:07', '2025-10-27 23:24:45'),
(23, 29, 'fH4UJ38_SG6JP_XHlTlcN1:APA91bHgQxZxSi6VSfTywAXYAn2kN_-GnMZdLjWahSMRQbO93zZ9wmdmT3ndnAekuETCZ9W4TaC8m6XS8gFOVMNJggcueUf7UiOZO4bxioHYqlkBN--RpZE', 'android', '1.0.0', 0, '2025-10-27 01:36:23', '2025-10-27 23:23:24'),
(24, 29, 'fTbyP38mRYmoXKxi-YRLlB:APA91bFDsW1PJw0G2nMo-PQHsx6pzlTYdbJQy3i6Bm25z8e5Hgim9iLnwky5bQxRB-Dvinnd4HtUuJYuJJdqVdV6tnIF1Z2NR_K4Xrjyr5BrP96Tub3ZxMk', 'android', '1.0.0', 0, '2025-10-27 23:23:24', '2025-11-01 06:31:18'),
(25, 36, 'fTbyP38mRYmoXKxi-YRLlB:APA91bFDsW1PJw0G2nMo-PQHsx6pzlTYdbJQy3i6Bm25z8e5Hgim9iLnwky5bQxRB-Dvinnd4HtUuJYuJJdqVdV6tnIF1Z2NR_K4Xrjyr5BrP96Tub3ZxMk', 'android', '1.0.0', 1, '2025-10-27 23:24:45', '2025-10-27 23:24:45'),
(26, 29, 'dlZlJq5IRaeE3Uyfp0CQfL:APA91bHxfiyKjaXaoij-dQrdBkV9_NX4t-uOQ2QzjcIwSHGiJkI_us9PTBL5JS0aNuBxbYyr5nkj4a_ACrttvyBu_rHhzv19VNdQkEoQl0E2nHoHY2BXkrU', 'android', '1.0.0', 0, '2025-10-28 00:40:50', '2025-11-17 12:41:34'),
(27, 39, 'fTbyP38mRYmoXKxi-YRLlB:APA91bFDsW1PJw0G2nMo-PQHsx6pzlTYdbJQy3i6Bm25z8e5Hgim9iLnwky5bQxRB-Dvinnd4HtUuJYuJJdqVdV6tnIF1Z2NR_K4Xrjyr5BrP96Tub3ZxMk', 'android', '1.0.0', 1, '2025-10-28 11:36:06', '2025-10-28 11:36:06'),
(28, 40, 'fTbyP38mRYmoXKxi-YRLlB:APA91bFDsW1PJw0G2nMo-PQHsx6pzlTYdbJQy3i6Bm25z8e5Hgim9iLnwky5bQxRB-Dvinnd4HtUuJYuJJdqVdV6tnIF1Z2NR_K4Xrjyr5BrP96Tub3ZxMk', 'android', '1.0.0', 0, '2025-10-28 11:50:22', '2025-11-07 02:29:07'),
(29, 29, 'dlUZg4BlSIe-YTM8k17UkE:APA91bFGvCttUwhOlX8muO3QMeuHtvrywTSBcZtBP_Hz3TwdBbpiHdcyNrGZD7aeLUY04TU4qQg_p3O5urOYPpf3w9_1KOxBaeNk1sCT8dCGU1S9AxQIdxU', 'android', '1.0.0', 0, '2025-11-01 06:31:18', '2025-11-08 03:42:14'),
(30, 40, 'dlUZg4BlSIe-YTM8k17UkE:APA91bFGvCttUwhOlX8muO3QMeuHtvrywTSBcZtBP_Hz3TwdBbpiHdcyNrGZD7aeLUY04TU4qQg_p3O5urOYPpf3w9_1KOxBaeNk1sCT8dCGU1S9AxQIdxU', 'android', '1.0.0', 1, '2025-11-07 02:29:07', '2025-11-07 02:29:07'),
(31, 29, 'cHBx4_hZSVyviNGi1YpYGS:APA91bGW3V-CHNNxBbVOpkYBf49p1JQjnK-XAYuE54RJQYQCGZXl_cXod9iop-E72V8UyLP2umHU-dq6nHsFP2HtgoGb0sNhXLkHywF-DuG75_lzhu0GBt0', 'android', '1.0.0', 0, '2025-11-08 03:42:14', '2025-11-17 12:41:34'),
(32, 1, 'cCtvmnLcQui2lxxZ48ke2U:APA91bHqING1-2YhcMYwoIsIf5ku42solTwo0fXKFbaeA4A_1ITET9uSa6Ru5YBhTU-exg5w6ynu3wuk3xO0earyFrDYOMvLEQbGm6HQR45mD1yzLsc1Ac4', 'android', '1.0.0', 0, '2025-11-17 12:41:06', '2025-11-17 12:50:04'),
(33, 29, 'cCtvmnLcQui2lxxZ48ke2U:APA91bHqING1-2YhcMYwoIsIf5ku42solTwo0fXKFbaeA4A_1ITET9uSa6Ru5YBhTU-exg5w6ynu3wuk3xO0earyFrDYOMvLEQbGm6HQR45mD1yzLsc1Ac4', 'android', '1.0.0', 0, '2025-11-17 12:41:34', '2025-11-17 12:50:34'),
(34, 1, 'efnfrm_1Te6MAO-sjd62my:APA91bE32Sy0BRi6LN3DEwile8iXgICfaEhjfQpQFq422LBp4f-j-n5Slo-7xd45sQo4EjVwBIwChWzFSAhowFaDon2wO1WkvCmGjrnLHMIVlmoebqfq8rs', 'android', '1.0.0', 0, '2025-11-17 12:50:04', '2025-11-19 01:03:21'),
(35, 29, 'efnfrm_1Te6MAO-sjd62my:APA91bE32Sy0BRi6LN3DEwile8iXgICfaEhjfQpQFq422LBp4f-j-n5Slo-7xd45sQo4EjVwBIwChWzFSAhowFaDon2wO1WkvCmGjrnLHMIVlmoebqfq8rs', 'android', '1.0.0', 0, '2025-11-17 12:50:34', '2025-11-19 02:54:42'),
(36, 35, 'efnfrm_1Te6MAO-sjd62my:APA91bE32Sy0BRi6LN3DEwile8iXgICfaEhjfQpQFq422LBp4f-j-n5Slo-7xd45sQo4EjVwBIwChWzFSAhowFaDon2wO1WkvCmGjrnLHMIVlmoebqfq8rs', 'android', '1.0.0', 0, '2025-11-17 12:57:10', '2025-11-28 08:34:18'),
(37, 59, 'efnfrm_1Te6MAO-sjd62my:APA91bE32Sy0BRi6LN3DEwile8iXgICfaEhjfQpQFq422LBp4f-j-n5Slo-7xd45sQo4EjVwBIwChWzFSAhowFaDon2wO1WkvCmGjrnLHMIVlmoebqfq8rs', 'android', '1.0.0', 0, '2025-11-17 13:04:24', '2025-11-24 02:52:57'),
(38, 1, 'dlQeJkN4TmiBs4zkc_w-pM:APA91bFamW4fw2NmAk69F6s2-ButuuEx_xCpaeoC4T6-lc8iHfeXTvz_iGDtXJi0kDxD26oD16_nLbOUVuRyTM08G6Ft-l0fi_9iZPaaRiUMWj-9oeezlcY', 'android', '1.0.0', 0, '2025-11-19 01:03:21', '2025-11-20 03:02:21'),
(39, 29, 'dlQeJkN4TmiBs4zkc_w-pM:APA91bFamW4fw2NmAk69F6s2-ButuuEx_xCpaeoC4T6-lc8iHfeXTvz_iGDtXJi0kDxD26oD16_nLbOUVuRyTM08G6Ft-l0fi_9iZPaaRiUMWj-9oeezlcY', 'android', '1.0.0', 0, '2025-11-19 02:54:42', '2025-11-21 07:22:27'),
(40, 1, 'f1lGoRI9TrCO-bYAMoK_hk:APA91bGqQ3VuCFA0lCiKlO1BW0_mV26KDb-0Boq7Uq0pu7yUSspSJ8psH2PzVNuHttseVGkIj_qSAPVCKbAL2o1zWyrKb9k-Qu_7TZALoEX5kkA3kd4dLjU', 'android', '1.0.0', 0, '2025-11-20 03:02:21', '2025-11-20 06:52:00'),
(41, 1, 'fs4bM7OVQqy82f2sTy3RXD:APA91bFKdCQyPbyCq5TI4P7GrQh5zlCSVDKSgYTgGlC10pTuTvSOjxaGFgLO9j-ScAqeZWjYvbMb_iTKce85xrp7XJokcedwwsGv4mz0-xBAQca4vWyWkO0', 'android', '1.0.0', 0, '2025-11-20 06:52:00', '2025-11-21 07:21:51'),
(42, 29, 'fs4bM7OVQqy82f2sTy3RXD:APA91bFKdCQyPbyCq5TI4P7GrQh5zlCSVDKSgYTgGlC10pTuTvSOjxaGFgLO9j-ScAqeZWjYvbMb_iTKce85xrp7XJokcedwwsGv4mz0-xBAQca4vWyWkO0', 'android', '1.0.0', 0, '2025-11-20 07:07:25', '2025-11-21 07:22:27'),
(43, 28, 'dlQeJkN4TmiBs4zkc_w-pM:APA91bFamW4fw2NmAk69F6s2-ButuuEx_xCpaeoC4T6-lc8iHfeXTvz_iGDtXJi0kDxD26oD16_nLbOUVuRyTM08G6Ft-l0fi_9iZPaaRiUMWj-9oeezlcY', 'android', '1.0.0', 0, '2025-11-20 10:45:56', '2025-11-21 07:29:31'),
(44, 28, 'fs4bM7OVQqy82f2sTy3RXD:APA91bFKdCQyPbyCq5TI4P7GrQh5zlCSVDKSgYTgGlC10pTuTvSOjxaGFgLO9j-ScAqeZWjYvbMb_iTKce85xrp7XJokcedwwsGv4mz0-xBAQca4vWyWkO0', 'android', '1.0.0', 0, '2025-11-21 00:59:54', '2025-11-21 07:29:31'),
(45, 1, 'f5Qj9VtcTE-EJl8wkXuiif:APA91bFrLI_jX4Pb2RyDX_0GRppnjgzfsjYmZCOF5vQ21y9ui_Gk7pcint3JM6X93-AV3HMtFtObk-YxNpnHg6rTWBnAZxMwr4PPb3ex7W9p6IjTqu4QBJE', 'android', '1.0.0', 0, '2025-11-21 07:21:51', '2025-11-21 07:34:46'),
(46, 29, 'f5Qj9VtcTE-EJl8wkXuiif:APA91bFrLI_jX4Pb2RyDX_0GRppnjgzfsjYmZCOF5vQ21y9ui_Gk7pcint3JM6X93-AV3HMtFtObk-YxNpnHg6rTWBnAZxMwr4PPb3ex7W9p6IjTqu4QBJE', 'android', '1.0.0', 0, '2025-11-21 07:22:27', '2025-11-23 04:01:40'),
(47, 28, 'f5Qj9VtcTE-EJl8wkXuiif:APA91bFrLI_jX4Pb2RyDX_0GRppnjgzfsjYmZCOF5vQ21y9ui_Gk7pcint3JM6X93-AV3HMtFtObk-YxNpnHg6rTWBnAZxMwr4PPb3ex7W9p6IjTqu4QBJE', 'android', '1.0.0', 0, '2025-11-21 07:29:31', '2025-11-21 07:35:17'),
(48, 1, 'dQgLH-01QPiODdBeGad6vv:APA91bFl1C-Y4ytk-qWw9gmuCPLnrlXvC1THMlf41RCflqcONxiVVpq2o8J60d-9D2DQSiUlq5FhW-LW9zX6TEPsh39638IgCFioI4AujBFsXH7E8fEpeTA', 'android', '1.0.0', 0, '2025-11-21 07:34:46', '2025-11-22 11:32:28'),
(49, 28, 'dQgLH-01QPiODdBeGad6vv:APA91bFl1C-Y4ytk-qWw9gmuCPLnrlXvC1THMlf41RCflqcONxiVVpq2o8J60d-9D2DQSiUlq5FhW-LW9zX6TEPsh39638IgCFioI4AujBFsXH7E8fEpeTA', 'android', '1.0.0', 0, '2025-11-21 07:35:17', '2025-12-06 06:50:51'),
(50, 28, 'eLd7YhTVRHqp7J75n5t0y3:APA91bF4ovvMnFaHY7IeMoxWGjJRiR4tYAPL-jEDDTh2kGClJLkKH6OZISQeb5YEbtpyLAx_0mWIzpDfVfkWtLxeGUusP8ShvKkVMmaS3WBkxplNaTFSP2c', 'android', '1.0.0', 0, '2025-11-21 07:40:25', '2026-01-04 03:24:23'),
(51, 1, 'd7trpQjgSKyb4n4D_lMhXX:APA91bFdgzhWuac2siPyUa560zILxygwrQgXIIaDiLL9gakGxeFOATONeRs4yFTTXcA4tbfDH9i4PsQj1RJDv7kynFhyC4H1qZvjCNnbunhluhTawXQxLfU', 'android', '1.0.0', 0, '2025-11-22 11:32:28', '2025-11-23 07:21:45'),
(52, 29, 'dQgLH-01QPiODdBeGad6vv:APA91bFl1C-Y4ytk-qWw9gmuCPLnrlXvC1THMlf41RCflqcONxiVVpq2o8J60d-9D2DQSiUlq5FhW-LW9zX6TEPsh39638IgCFioI4AujBFsXH7E8fEpeTA', 'android', '1.0.0', 0, '2025-11-23 04:01:40', '2025-11-23 06:01:19'),
(53, 29, 'd7trpQjgSKyb4n4D_lMhXX:APA91bFdgzhWuac2siPyUa560zILxygwrQgXIIaDiLL9gakGxeFOATONeRs4yFTTXcA4tbfDH9i4PsQj1RJDv7kynFhyC4H1qZvjCNnbunhluhTawXQxLfU', 'android', '1.0.0', 0, '2025-11-23 06:01:19', '2025-11-23 08:55:52'),
(54, 28, 'd7trpQjgSKyb4n4D_lMhXX:APA91bFdgzhWuac2siPyUa560zILxygwrQgXIIaDiLL9gakGxeFOATONeRs4yFTTXcA4tbfDH9i4PsQj1RJDv7kynFhyC4H1qZvjCNnbunhluhTawXQxLfU', 'android', '1.0.0', 0, '2025-11-23 06:24:29', '2025-11-24 02:43:09'),
(55, 38, 'd7trpQjgSKyb4n4D_lMhXX:APA91bFdgzhWuac2siPyUa560zILxygwrQgXIIaDiLL9gakGxeFOATONeRs4yFTTXcA4tbfDH9i4PsQj1RJDv7kynFhyC4H1qZvjCNnbunhluhTawXQxLfU', 'android', '1.0.0', 1, '2025-11-23 06:34:03', '2025-11-23 06:34:03'),
(56, 1, 'fmiXfBUIR8Si-MvXSn0LNk:APA91bG1YNuPjzeLzw6wBe1OfZKsjeoCWmBrBFb92K9ckLN1MmJ6OOdMUYZBbqQDs5MABabUdj6l48B4-l9cz18WcJyGeTGFGDHSHvFLuvSR-tRhXi9wEYM', 'android', '1.0.0', 1, '2025-11-23 07:21:45', '2025-11-23 07:21:45'),
(57, 29, 'fmiXfBUIR8Si-MvXSn0LNk:APA91bG1YNuPjzeLzw6wBe1OfZKsjeoCWmBrBFb92K9ckLN1MmJ6OOdMUYZBbqQDs5MABabUdj6l48B4-l9cz18WcJyGeTGFGDHSHvFLuvSR-tRhXi9wEYM', 'android', '1.0.0', 1, '2025-11-23 08:55:52', '2025-12-06 07:34:02'),
(58, 28, 'fmiXfBUIR8Si-MvXSn0LNk:APA91bG1YNuPjzeLzw6wBe1OfZKsjeoCWmBrBFb92K9ckLN1MmJ6OOdMUYZBbqQDs5MABabUdj6l48B4-l9cz18WcJyGeTGFGDHSHvFLuvSR-tRhXi9wEYM', 'android', '1.0.0', 0, '2025-11-24 02:43:09', '2025-12-06 06:50:51'),
(59, 59, 'fmiXfBUIR8Si-MvXSn0LNk:APA91bG1YNuPjzeLzw6wBe1OfZKsjeoCWmBrBFb92K9ckLN1MmJ6OOdMUYZBbqQDs5MABabUdj6l48B4-l9cz18WcJyGeTGFGDHSHvFLuvSR-tRhXi9wEYM', 'android', '1.0.0', 1, '2025-11-24 02:52:57', '2025-11-24 02:52:57'),
(60, 44, 'fmiXfBUIR8Si-MvXSn0LNk:APA91bG1YNuPjzeLzw6wBe1OfZKsjeoCWmBrBFb92K9ckLN1MmJ6OOdMUYZBbqQDs5MABabUdj6l48B4-l9cz18WcJyGeTGFGDHSHvFLuvSR-tRhXi9wEYM', 'android', '1.0.0', 1, '2025-11-25 11:35:59', '2025-11-25 11:35:59'),
(61, 35, 'fmiXfBUIR8Si-MvXSn0LNk:APA91bG1YNuPjzeLzw6wBe1OfZKsjeoCWmBrBFb92K9ckLN1MmJ6OOdMUYZBbqQDs5MABabUdj6l48B4-l9cz18WcJyGeTGFGDHSHvFLuvSR-tRhXi9wEYM', 'android', '1.0.0', 1, '2025-11-28 08:34:18', '2025-11-28 08:34:18'),
(62, 29, 'dQgLH-01QPiODdBeGad6vv:APA91bGFHPsnda2vr_7HrM_hp54RyhH2pEX_cbz-Vdzy9gE7vHpCbzEW76l1opFj1xFEP7oYvi8hjeJp_ihfJVv-XiTSwXsRgzdFR0nEGd1nATjHfc_P4ko', 'android', '1.0.0', 1, '2025-12-06 06:08:28', '2025-12-06 06:08:28'),
(63, 28, 'dQgLH-01QPiODdBeGad6vv:APA91bGFHPsnda2vr_7HrM_hp54RyhH2pEX_cbz-Vdzy9gE7vHpCbzEW76l1opFj1xFEP7oYvi8hjeJp_ihfJVv-XiTSwXsRgzdFR0nEGd1nATjHfc_P4ko', 'android', '1.0.0', 0, '2025-12-06 06:50:51', '2026-01-04 03:24:23'),
(64, 62, 'fmiXfBUIR8Si-MvXSn0LNk:APA91bG1YNuPjzeLzw6wBe1OfZKsjeoCWmBrBFb92K9ckLN1MmJ6OOdMUYZBbqQDs5MABabUdj6l48B4-l9cz18WcJyGeTGFGDHSHvFLuvSR-tRhXi9wEYM', 'android', '1.0.0', 1, '2025-12-06 07:19:00', '2026-01-03 01:35:47'),
(65, 37, 'fmiXfBUIR8Si-MvXSn0LNk:APA91bG1YNuPjzeLzw6wBe1OfZKsjeoCWmBrBFb92K9ckLN1MmJ6OOdMUYZBbqQDs5MABabUdj6l48B4-l9cz18WcJyGeTGFGDHSHvFLuvSR-tRhXi9wEYM', 'android', '1.0.0', 1, '2025-12-06 09:24:00', '2025-12-06 09:24:00'),
(66, 62, 'dQgLH-01QPiODdBeGad6vv:APA91bGFHPsnda2vr_7HrM_hp54RyhH2pEX_cbz-Vdzy9gE7vHpCbzEW76l1opFj1xFEP7oYvi8hjeJp_ihfJVv-XiTSwXsRgzdFR0nEGd1nATjHfc_P4ko', 'android', '1.0.0', 1, '2025-12-10 01:13:20', '2025-12-10 01:13:20'),
(67, 28, 'dQgLH-01QPiODdBeGad6vv:APA91bFwA52GmAHkCYW-aSrZxC7xntcx4siLzH3Zzp1dRzla-l9iNFrOG_9uoNyP8jKOM3G2Mf2TWRwdb2ZJBV6BH1OdCAW7FWHW1qvXoM4F_P8JsvJroBM', 'android', '1.0.0', 1, '2026-01-04 03:24:23', '2026-01-04 03:24:23');

-- --------------------------------------------------------

--
-- Table structure for table `email_verifications`
--

CREATE TABLE `email_verifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `verification_code` varchar(6) NOT NULL,
  `expiry_time` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `email_verifications`
--

INSERT INTO `email_verifications` (`id`, `user_id`, `email`, `verification_code`, `expiry_time`, `created_at`) VALUES
(96, 51, 'christehannamae.cuas@bisu.edu.ph', '165901', '2025-12-02 11:07:49', '2025-12-02 09:37:49'),
(98, 124, 'mantekimberlyjul@gmail.com', '372226', '2026-01-04 10:12:04', '2026-01-04 08:42:04'),
(99, 125, 'mantekim96@gmail.com', '359276', '2026-01-04 11:10:25', '2026-01-04 09:40:25');

-- --------------------------------------------------------

--
-- Table structure for table `group_members`
--

CREATE TABLE `group_members` (
  `gm_id` int(11) NOT NULL,
  `gc_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `gm_role` enum('Owner','Boarder','Admin') DEFAULT 'Boarder',
  `gm_joined_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `group_members`
--

INSERT INTO `group_members` (`gm_id`, `gc_id`, `user_id`, `gm_role`, `gm_joined_at`) VALUES
(1, 11, 28, '', '2025-10-14 03:58:45'),
(2, 11, 1, '', '2025-10-14 03:58:45'),
(7, 13, 28, '', '2025-10-14 07:24:42'),
(8, 13, 1, '', '2025-10-14 07:24:42'),
(13, 15, 38, '', '2025-11-19 12:38:00'),
(14, 15, 59, '', '2025-11-19 12:38:00'),
(15, 15, 28, '', '2025-11-19 12:38:00'),
(16, 15, 44, '', '2025-11-19 12:38:00'),
(18, 16, 38, '', '2025-11-21 01:22:03'),
(19, 16, 59, '', '2025-11-21 01:22:03'),
(20, 16, 28, '', '2025-11-21 01:22:03'),
(21, 16, 44, '', '2025-11-21 01:22:03'),
(23, 17, 38, '', '2025-11-21 01:22:05'),
(24, 17, 59, '', '2025-11-21 01:22:05'),
(25, 17, 28, '', '2025-11-21 01:22:05'),
(26, 17, 44, '', '2025-11-21 01:22:05'),
(27, 17, 29, '', '2025-11-21 01:22:05'),
(31, 19, 38, '', '2025-12-06 04:36:18'),
(32, 19, 59, '', '2025-12-06 04:36:18'),
(33, 19, 28, '', '2025-12-06 04:36:18'),
(34, 19, 29, '', '2025-12-06 04:36:18');

-- --------------------------------------------------------

--
-- Table structure for table `group_messages`
--

CREATE TABLE `group_messages` (
  `groupmessage_id` int(11) NOT NULL,
  `gc_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `groupmessage_text` text NOT NULL,
  `groupmessage_timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  `groupmessage_status` enum('Sent','Delivered','Read') DEFAULT 'Sent'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `group_messages`
--

INSERT INTO `group_messages` (`groupmessage_id`, `gc_id`, `sender_id`, `groupmessage_text`, `groupmessage_timestamp`, `groupmessage_status`) VALUES
(39, 15, 38, 'hi guys', '2025-11-19 13:06:22', 'Read'),
(40, 17, 29, 'Hoo', '2025-12-01 06:08:08', 'Read'),
(41, 17, 29, 'Hoo', '2025-12-01 06:08:23', 'Read'),
(42, 17, 29, 'Hi', '2025-12-01 06:09:35', 'Read'),
(43, 17, 29, 'Hi', '2025-12-01 06:09:50', 'Read'),
(44, 17, 29, 'Hi everyone!!', '2025-12-01 06:14:45', 'Read'),
(45, 17, 29, 'Hi everyone!!', '2025-12-01 06:15:00', 'Read'),
(46, 17, 29, 'Loww', '2025-12-01 06:18:11', 'Read'),
(47, 17, 29, 'Loww', '2025-12-01 06:18:26', 'Read'),
(48, 17, 29, 'Huy', '2025-12-06 04:35:38', 'Read');

-- --------------------------------------------------------

--
-- Table structure for table `maintenance_requests`
--

CREATE TABLE `maintenance_requests` (
  `request_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `room_id` int(11) DEFAULT NULL,
  `subject` varchar(255) NOT NULL,
  `area_for_maintenance` varchar(50) NOT NULL,
  `mr_description` text NOT NULL,
  `mr_status` enum('Declined','Pending','In Progress','Resolved') NOT NULL DEFAULT 'Pending',
  `mr_approved_at` timestamp NULL DEFAULT NULL COMMENT 'Timestamp when status changed to In Progress',
  `mr_completed_at` timestamp NULL DEFAULT NULL COMMENT 'Timestamp when status changed to Resolved',
  `mr_created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `maintenance_requests`
--

INSERT INTO `maintenance_requests` (`request_id`, `user_id`, `room_id`, `subject`, `area_for_maintenance`, `mr_description`, `mr_status`, `mr_approved_at`, `mr_completed_at`, `mr_created_at`) VALUES
(16, 35, 91, 'DAMAGEE', 'BH Room', 'Jsjsjsk', 'Declined', NULL, NULL, '2025-11-18 06:30:14'),
(17, 35, 91, 'DAMAGEE', 'BH Room', 'Jsjsjsk', 'Resolved', '2025-11-18 06:30:44', '2025-11-18 06:31:12', '2025-11-18 06:30:17'),
(18, 35, 91, 'Damagee', 'BH Room', 'Nznzn', 'Pending', NULL, NULL, '2025-11-18 06:40:11'),
(19, 35, 91, 'Vahajaj', 'Kitchen', 'NNN', 'Pending', NULL, NULL, '2025-11-18 06:42:45'),
(20, 35, 91, 'Damana', 'Kitchen', 'JNkK', 'In Progress', '2025-11-19 12:03:18', NULL, '2025-11-18 06:46:26'),
(21, 35, 91, 'Ajjaaj', 'BH Room', 'Nsjssjj', 'Declined', NULL, NULL, '2025-11-18 06:50:12'),
(22, 35, 91, 'Sjhsuw', 'BH Room', 'Hzhsjsj', 'In Progress', '2025-11-18 06:55:27', NULL, '2025-11-18 06:54:23'),
(23, 28, 86, 'Damage of CR', 'BH Room', 'The water is not working!', 'Pending', NULL, NULL, '2025-12-06 03:31:03');

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `message_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `msg_text` text NOT NULL,
  `msg_timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  `msg_status` enum('Sent','Delivered','Read') DEFAULT 'Sent'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`message_id`, `sender_id`, `receiver_id`, `msg_text`, `msg_timestamp`, `msg_status`) VALUES
(1, 1, 2, 'Hello! Welcome to our boarding house.', '2025-10-04 10:57:56', ''),
(2, 2, 1, 'Thank you! I\'m excited to be here.', '2025-10-04 10:57:56', 'Read'),
(3, 1, 2, 'If you need anything, just let me know.', '2025-10-04 11:57:56', ''),
(5, 4, 6, 'Good morning! How are you settling in?', '2025-10-04 11:57:56', 'Read'),
(6, 6, 4, 'Everything is great, thank you!', '2025-10-04 12:12:56', 'Read'),
(15, 1, 2, 'Hello! This is a test message from the real messaging system.', '2025-10-04 14:04:54', ''),
(16, 2, 1, 'hiii', '2025-10-04 14:05:34', 'Read'),
(18, 6, 2, 'Hello! This is a test message from the real messaging system.', '2025-10-04 14:13:48', 'Sent'),
(19, 6, 1, 'Hello! This is a test message from the real messaging system.', '2025-10-04 14:13:53', 'Read'),
(20, 6, 1, 'Hello! This is a test message from the real messaging system.', '2025-10-04 14:13:57', 'Read'),
(22, 6, 2, 'Hello! This is a test message from the real messaging system.', '2025-10-04 14:23:52', 'Sent'),
(23, 6, 1, 'Hello! This is a test message from the real messaging system.', '2025-10-04 14:23:59', 'Read'),
(32, 1, 2, 'Hello! This is a test message from the real messaging system.', '2025-10-05 01:36:25', ''),
(33, 1, 2, 'Hello! This is a test message from the real messaging system.', '2025-10-05 01:46:18', ''),
(34, 1, 2, 'Hello! This is a test message from the real messaging system.', '2025-10-05 01:48:19', ''),
(35, 1, 2, 'Hello! This is a test message from the real messaging system.', '2025-10-05 02:01:51', ''),
(36, 2, 1, '🔔 Test Message Badge - 13:36:11', '2025-10-05 05:36:11', 'Read'),
(37, 2, 1, '🔔 Test Message Badge - 13:37:20', '2025-10-05 05:37:20', 'Read'),
(38, 2, 1, '🔔 Test Message Badge - 13:40:27', '2025-10-05 05:40:27', 'Read'),
(39, 2, 1, '🔔 Test Message Badge - 14:39:57', '2025-10-05 06:39:57', 'Read'),
(40, 1, 2, 'Test message from PHP', '2025-10-05 08:13:10', ''),
(41, 1, 6, 'hi', '2025-10-05 08:14:09', 'Read'),
(42, 1, 6, 'hi', '2025-10-05 08:14:12', 'Read'),
(43, 1, 2, 'Test message from PHP', '2025-10-05 08:20:05', ''),
(44, 1, 6, 'hhiii', '2025-10-05 08:22:02', 'Read'),
(45, 1, 6, 'hhiii', '2025-10-05 08:22:05', 'Read'),
(46, 1, 2, 'Test message from PHP', '2025-10-05 08:27:01', ''),
(47, 1, 6, 'hooo', '2025-10-05 08:29:11', 'Read'),
(48, 1, 6, 'hooo', '2025-10-05 08:29:14', 'Read'),
(49, 1, 2, 'uouu', '2025-10-05 08:29:52', ''),
(50, 1, 2, 'uouu', '2025-10-05 08:29:55', ''),
(51, 1, 2, 'Test message from PHP', '2025-10-05 08:34:29', ''),
(52, 1, 2, 'bitaw', '2025-10-05 08:41:51', ''),
(53, 1, 2, 'bitaw', '2025-10-05 08:41:53', ''),
(54, 1, 6, 'how about me', '2025-10-05 08:55:01', 'Read'),
(55, 1, 6, 'how about me', '2025-10-05 08:55:03', 'Read'),
(56, 1, 6, 'huy', '2025-10-05 09:20:38', 'Read'),
(57, 1, 6, 'huy', '2025-10-05 09:20:40', 'Read'),
(58, 1, 2, 'hey', '2025-10-05 09:22:12', ''),
(59, 1, 2, 'hey', '2025-10-05 09:22:15', ''),
(60, 1, 6, 'huy pud', '2025-10-05 09:27:49', 'Read'),
(61, 1, 6, 'huy pud', '2025-10-05 09:27:51', 'Read'),
(62, 1, 6, 'huy ba', '2025-10-05 09:28:10', 'Read'),
(63, 1, 6, 'huy ba', '2025-10-05 09:28:12', 'Read'),
(64, 1, 2, 'hello', '2025-10-05 09:28:29', ''),
(65, 1, 2, 'hello', '2025-10-05 09:28:31', ''),
(66, 1, 2, 'ouhh', '2025-10-05 09:35:00', ''),
(67, 1, 2, 'ouhh', '2025-10-05 09:35:02', ''),
(68, 1, 6, 'low', '2025-10-05 09:41:58', 'Read'),
(69, 1, 6, 'low', '2025-10-05 09:42:00', 'Read'),
(70, 1, 2, 'huyy', '2025-10-05 10:40:08', ''),
(71, 1, 2, 'huyy', '2025-10-05 10:40:11', ''),
(74, 1, 6, 'lowbat', '2025-10-05 10:41:10', 'Read'),
(75, 1, 6, 'lowbat', '2025-10-05 10:41:13', 'Read'),
(77, 1, 2, 'yes', '2025-10-05 11:00:32', 'Sent'),
(78, 1, 2, 'yes', '2025-10-05 11:00:34', 'Sent'),
(82, 1, 2, 'no', '2025-10-05 12:19:52', 'Sent'),
(83, 1, 2, 'no', '2025-10-05 12:19:55', 'Sent'),
(84, 1, 2, 'favri', '2025-10-05 12:24:37', 'Sent'),
(85, 1, 2, 'favri', '2025-10-05 12:24:39', 'Sent'),
(86, 1, 2, 'dam', '2025-10-05 12:29:10', 'Sent'),
(87, 1, 2, 'dam', '2025-10-05 12:29:12', 'Sent'),
(88, 1, 2, 'waley', '2025-10-05 12:29:29', 'Sent'),
(89, 1, 2, 'waley', '2025-10-05 12:29:31', 'Sent'),
(90, 1, 6, 'bat', '2025-10-05 12:30:29', 'Read'),
(91, 1, 6, 'bat', '2025-10-05 12:30:31', 'Read'),
(92, 1, 6, 'hey', '2025-10-05 12:34:56', 'Read'),
(93, 1, 6, 'hey', '2025-10-05 12:34:59', 'Read'),
(94, 1, 6, 'woi', '2025-10-05 12:38:33', 'Read'),
(96, 4, 1, 'hays', '2025-10-05 12:44:32', 'Read'),
(97, 4, 1, 'gaba gajud ni', '2025-10-05 12:45:02', 'Read'),
(98, 1, 4, 'kims', '2025-10-05 12:45:39', 'Sent'),
(99, 4, 1, 'yes', '2025-10-05 12:45:49', 'Read'),
(100, 4, 1, 'hi', '2025-10-05 12:51:27', 'Read'),
(101, 4, 1, 'hiii', '2025-10-05 12:52:01', 'Read'),
(102, 1, 6, 'REAL-TIME TEST MESSAGE 1759668852', '2025-10-05 12:54:12', 'Read'),
(103, 1, 6, 'API TEST MESSAGE 1759668852', '2025-10-05 12:54:14', 'Read'),
(104, 4, 1, 'yy', '2025-10-05 12:54:59', 'Read'),
(105, 4, 1, 'no\r\n', '2025-10-05 12:55:27', 'Read'),
(106, 1, 4, 'yesss', '2025-10-07 01:23:58', 'Sent'),
(107, 1, 2, 'hi', '2025-10-07 01:24:26', 'Sent'),
(108, 1, 2, 'hi', '2025-10-07 01:24:56', 'Sent'),
(109, 1, 4, 'huy dapat sa babaw ka', '2025-10-07 01:25:34', 'Sent'),
(112, 1, 6, 'boboerns', '2025-10-07 01:26:18', 'Read'),
(113, 1, 2, 'haystt', '2025-10-07 01:26:33', 'Sent'),
(114, 1, 2, 'nooo', '2025-10-07 01:35:52', 'Sent'),
(115, 1, 2, 'ye', '2025-10-07 07:07:28', 'Sent'),
(116, 1, 2, 'heyy', '2025-10-08 15:08:54', 'Sent'),
(118, 1, 2, 'Hello! This is a test message from the real messaging system.', '2025-10-08 15:11:02', 'Sent'),
(119, 2, 1, 'Hello! This is a test message from the real messaging system.', '2025-10-08 15:17:38', 'Read'),
(120, 1, 2, 'okays', '2025-10-08 15:18:21', 'Sent'),
(121, 1, 2, 'huhu', '2025-10-08 15:21:57', 'Sent'),
(122, 2, 1, 'Hello! This is a test message from the real messaging system.', '2025-10-08 15:26:20', 'Read'),
(123, 1, 6, 'huhuhu', '2025-10-08 15:32:26', 'Read'),
(124, 1, 6, 'huyyy', '2025-10-08 15:40:41', 'Read'),
(125, 2, 1, 'Hello! This is a test message from the real messaging system.', '2025-10-08 15:40:54', 'Read'),
(126, 1, 6, 'huyyy', '2025-10-08 15:57:48', 'Read'),
(127, 2, 1, 'Hello! This is a test message from the real messaging system.', '2025-10-08 15:57:59', 'Read'),
(128, 2, 1, 'Hello! This is a test message from the real messaging system.', '2025-10-08 15:58:04', 'Read'),
(129, 2, 1, 'Hello! This is a test message from the real messaging system.', '2025-10-08 16:04:47', 'Read'),
(130, 2, 1, 'Hello! This is a test message from the real messaging system.', '2025-10-08 16:05:03', 'Read'),
(131, 2, 1, 'Test notification message - should pop up!', '2025-10-08 16:06:49', 'Read'),
(132, 1, 2, 'we', '2025-10-08 16:09:57', 'Sent'),
(133, 1, 2, 'weeeee', '2025-10-08 16:10:08', 'Sent'),
(134, 1, 4, 'bay', '2025-10-08 16:14:26', 'Sent'),
(135, 2, 1, 'Hello! This is a test message from the real messaging system.', '2025-10-08 16:15:25', 'Read'),
(136, 2, 1, 'Hello! This is a test message from the real messaging system.', '2025-10-08 16:16:11', 'Read'),
(139, 1, 6, 'hagua mn ka', '2025-10-08 16:21:00', 'Read'),
(140, 2, 1, 'Hello! This is a test message from the real messaging system.', '2025-10-08 16:27:48', 'Read'),
(141, 1, 6, 'uy', '2025-10-09 02:31:29', 'Read'),
(142, 1, 6, 'dina lageh ka mogana notif', '2025-10-09 02:31:37', 'Read'),
(143, 2, 1, 'Hello! This is a test message from the real messaging system.', '2025-10-09 02:36:35', 'Read'),
(144, 1, 6, 'woyyy', '2025-10-09 02:54:52', 'Read'),
(145, 2, 1, 'Hello! This is a test message from the real messaging system.', '2025-10-09 02:55:19', 'Read'),
(146, 2, 1, 'Hello! This is a test message from the real messaging system.', '2025-10-09 02:56:32', 'Read'),
(147, 4, 2, 'Hello! This is a test message from the real messaging system.', '2025-10-09 02:59:06', 'Sent'),
(148, 4, 1, 'Hello! This is a test message from the real messaging system.', '2025-10-09 02:59:23', 'Read'),
(149, 4, 1, 'Hello! This is a test message from the real messaging system.', '2025-10-09 02:59:33', 'Read'),
(150, 1, 2, 'Hello! This is a test message from the real messaging system.', '2025-10-09 03:01:30', 'Sent'),
(151, 2, 1, 'Hello! This is a test message from the real messaging system.', '2025-10-09 03:01:44', 'Read'),
(152, 2, 1, 'Hello! This is a test message from the real messaging system.', '2025-10-09 03:03:22', 'Read'),
(153, 2, 1, 'Hello! This is a test message from the real messaging system.', '2025-10-09 03:03:30', 'Read'),
(154, 2, 1, 'Hello! This is a test message from the real messaging system.', '2025-10-09 03:03:36', 'Read'),
(156, 5, 1, 'Hello! This is a test message from the real messaging system.', '2025-10-09 03:03:50', 'Read'),
(157, 2, 1, 'Hello! This is a test message from the real messaging system.', '2025-10-09 03:04:44', 'Read'),
(158, 2, 1, 'we\r\n', '2025-10-09 03:04:56', 'Read'),
(159, 2, 1, 'we\r\n', '2025-10-09 03:06:05', 'Read'),
(160, 2, 1, 'wala ', '2025-10-09 03:06:11', 'Read'),
(161, 2, 29, 'hi', '2025-10-12 04:07:20', 'Read'),
(162, 1, 6, 'woyyy', '2025-10-12 04:08:27', 'Read'),
(163, 2, 29, 'hello', '2025-10-12 04:24:07', 'Read'),
(164, 8, 29, 'hello', '2025-10-12 04:24:38', 'Read'),
(165, 29, 8, 'hi', '2025-10-12 04:25:01', 'Sent'),
(166, 29, 2, 'huy', '2025-10-12 04:25:29', 'Sent'),
(167, 29, 6, 'hi', '2025-10-12 04:25:34', 'Read'),
(168, 29, 6, 'hello po', '2025-10-12 04:30:13', 'Read'),
(169, 6, 29, 'hello', '2025-10-12 04:30:47', 'Read'),
(170, 24, 29, 'hello', '2025-10-12 04:31:36', 'Read'),
(171, 24, 29, 'hoo', '2025-10-12 04:32:01', 'Read'),
(172, 24, 29, 'hoo', '2025-10-12 05:10:43', 'Read'),
(173, 24, 29, 'hupay', '2025-10-12 05:39:12', 'Read'),
(174, 24, 29, 'huhuhuhu\r\n\r\n', '2025-10-12 05:39:32', 'Read'),
(175, 27, 29, 'https://open.spotify.com/playlist/37i9dQZF1E36NC4j9YSysy\r\n\r\n', '2025-10-12 05:40:09', 'Read'),
(176, 27, 29, 'huhuhu', '2025-10-12 05:43:29', 'Read'),
(177, 27, 29, 'huhuhu', '2025-10-12 05:43:42', 'Read'),
(178, 29, 28, 'hi', '2025-10-14 05:38:53', 'Read'),
(179, 28, 29, 'yes?', '2025-10-14 05:41:19', 'Read'),
(180, 28, 29, 'hays', '2025-10-14 05:50:52', 'Read'),
(181, 29, 28, 'yes?', '2025-10-14 05:59:59', 'Read'),
(182, 28, 29, 'aw wala raman', '2025-10-14 06:01:09', 'Read'),
(183, 28, 29, 'huy', '2025-10-14 06:01:33', 'Read'),
(184, 29, 28, 'uy', '2025-10-14 06:02:19', 'Read'),
(185, 28, 29, 'huh', '2025-10-14 06:07:35', 'Read'),
(186, 28, 29, 'unsa', '2025-10-14 06:12:19', 'Read'),
(187, 29, 28, 'wala lagrh', '2025-10-14 06:14:27', 'Read'),
(188, 28, 29, 'noo', '2025-10-14 06:32:48', 'Read'),
(189, 29, 28, 'hey', '2025-10-14 06:53:55', 'Read'),
(190, 29, 28, 'okay', '2025-10-14 07:19:58', 'Read'),
(191, 29, 28, 'huyyyy', '2025-10-14 07:29:00', 'Read'),
(192, 29, 28, 'ha', '2025-10-14 07:44:32', 'Read'),
(193, 28, 29, 'wala', '2025-10-14 07:47:57', 'Read'),
(194, 29, 28, 'hays', '2025-10-14 07:53:56', 'Read'),
(195, 29, 28, 'haysh', '2025-10-14 07:53:59', 'Read'),
(196, 29, 28, 'hays', '2025-10-14 07:54:03', 'Read'),
(197, 28, 29, 'what happen', '2025-10-14 08:14:33', 'Read'),
(198, 29, 28, 'wala mannnn', '2025-10-14 08:15:35', 'Read'),
(199, 28, 29, 'sure ka?', '2025-10-14 08:40:31', 'Read'),
(200, 28, 29, 'sure ba', '2025-10-14 08:56:48', 'Read'),
(201, 29, 28, 'lagehhh', '2025-10-14 08:58:21', 'Read'),
(202, 28, 29, 'huy', '2025-10-14 09:03:01', 'Read'),
(203, 28, 29, 'jjj', '2025-10-14 09:04:41', 'Read'),
(204, 28, 29, 'jjjjjjjjj', '2025-10-14 09:06:28', 'Read'),
(205, 28, 29, 'hakdog', '2025-10-14 09:06:43', 'Read'),
(206, 28, 29, 'kk', '2025-10-14 09:11:51', 'Read'),
(207, 28, 29, 'hi', '2025-10-14 09:18:43', 'Read'),
(208, 29, 28, 'hello', '2025-10-14 09:19:14', 'Read'),
(209, 28, 29, 'yes?', '2025-10-23 13:37:42', 'Read'),
(210, 29, 28, 'b**o', '2025-10-25 03:46:58', 'Read'),
(211, 29, 28, 't***a', '2025-10-25 03:47:07', 'Read'),
(212, 29, 28, 'f**k', '2025-10-25 03:47:14', 'Read'),
(213, 29, 28, 's****d', '2025-10-25 03:47:25', 'Read'),
(214, 29, 28, 't*****a', '2025-10-25 03:47:46', 'Read'),
(215, 29, 28, 'hi', '2025-10-25 03:47:48', 'Read'),
(216, 29, 28, 'boboha nimo', '2025-10-25 03:49:40', 'Read'),
(217, 29, 28, 'b**o', '2025-10-25 03:49:45', 'Read'),
(218, 29, 28, 'fucking s****d', '2025-10-25 03:51:07', 'Read'),
(219, 29, 28, 'your so f*****g s****d', '2025-10-25 03:53:20', 'Read'),
(220, 29, 28, 's**t', '2025-10-25 03:53:28', 'Read'),
(221, 29, 28, 'f*****g', '2025-10-25 04:51:40', 'Read'),
(222, 28, 29, 's**t', '2025-10-25 04:52:54', 'Read'),
(223, 29, 28, 'b******t', '2025-10-25 08:06:12', 'Read'),
(224, 29, 28, 's**t', '2025-10-27 01:29:41', 'Read'),
(225, 29, 28, 'b******t', '2025-10-27 01:29:51', 'Read'),
(226, 28, 29, 'namz', '2025-10-27 01:31:46', 'Read'),
(227, 29, 28, 'kim your so s****d', '2025-10-27 01:32:36', 'Read'),
(228, 28, 29, 'i don\'t care', '2025-10-27 01:33:14', 'Read'),
(229, 29, 28, 'okay', '2025-10-27 01:33:33', 'Read'),
(230, 29, 28, 's**t', '2025-10-27 01:39:10', 'Read'),
(231, 29, 28, 'b**o ka', '2025-10-27 01:39:24', 'Read'),
(232, 29, 28, 'hiii', '2025-10-28 12:50:41', 'Read'),
(233, 29, 28, 'hu', '2025-10-28 12:51:13', 'Read'),
(234, 29, 28, 'hiii', '2025-10-28 12:52:03', 'Read'),
(235, 28, 29, 'yes?', '2025-10-28 12:53:32', 'Read'),
(236, 28, 29, 'huyyy', '2025-10-28 12:59:38', 'Read'),
(237, 29, 28, 'hiiii', '2025-10-28 13:19:32', 'Read'),
(238, 29, 28, 'hiii', '2025-10-31 06:55:38', 'Read'),
(239, 29, 28, 'hi liz', '2025-11-01 06:27:06', 'Read'),
(240, 28, 29, 'yesdd hi???', '2025-11-01 06:28:47', 'Read'),
(241, 28, 29, 's**t', '2025-11-01 06:28:57', 'Read'),
(242, 29, 28, 'hi', '2025-11-01 06:31:41', 'Read'),
(243, 28, 29, 'namz, u receive this message? hahaha', '2025-11-05 12:08:32', 'Read'),
(244, 29, 28, 'hi', '2025-11-05 12:17:55', 'Read'),
(245, 29, 28, 'hi kim', '2025-11-13 05:34:04', 'Read'),
(246, 29, 28, 'okay rana', '2025-11-13 05:34:08', 'Read'),
(247, 29, 59, 'h**lo', '2025-11-13 05:34:30', 'Read'),
(248, 29, 59, 'hi', '2025-11-13 05:34:44', 'Read'),
(249, 29, 59, 'ug', '2025-11-13 05:35:52', 'Read'),
(250, 29, 59, 'yuh', '2025-11-13 05:35:59', 'Read'),
(251, 29, 59, 'ily', '2025-11-13 05:36:07', 'Read'),
(252, 29, 59, 'euuudru', '2025-11-13 05:41:02', 'Read'),
(253, 28, 29, 'ouh', '2025-11-13 05:41:56', 'Read'),
(254, 28, 29, 'okay ra. bitaw ko', '2025-11-13 05:42:09', 'Read'),
(255, 29, 28, 'hahahaa', '2025-11-13 06:04:47', 'Read'),
(256, 29, 28, 'hi', '2025-11-17 12:42:25', 'Read'),
(257, 29, 28, 'h**lo', '2025-11-17 12:52:53', 'Read'),
(258, 29, 28, 'hii', '2025-11-17 12:53:03', 'Read'),
(259, 35, 29, 'hi', '2025-11-17 12:56:46', 'Read'),
(260, 59, 29, 'hi', '2025-11-17 12:58:27', 'Read'),
(261, 29, 59, 'hi!', '2025-11-17 13:02:39', 'Read'),
(262, 59, 29, 'hii?', '2025-11-17 13:03:29', 'Read'),
(263, 59, 29, 'low', '2025-11-17 13:13:12', 'Read'),
(264, 29, 35, 'hii', '2025-11-17 13:14:48', 'Read'),
(265, 29, 35, 'hi', '2025-11-17 13:18:59', 'Read'),
(266, 59, 29, 'hii', '2025-11-17 13:19:47', 'Read'),
(267, 29, 59, 'hi', '2025-11-17 13:21:55', 'Read'),
(268, 29, 59, 'hi', '2025-11-17 13:22:45', 'Read'),
(269, 29, 59, 'hi', '2025-11-17 13:30:35', 'Read'),
(270, 29, 59, 'hii', '2025-11-17 13:31:09', 'Read'),
(271, 29, 35, 'low', '2025-11-17 13:34:14', 'Read'),
(272, 29, 35, 'low', '2025-11-17 13:34:45', 'Read'),
(273, 29, 35, 'hi', '2025-11-17 13:39:25', 'Read'),
(274, 29, 35, 'hii', '2025-11-17 13:42:04', 'Read'),
(275, 29, 35, 'hii', '2025-11-17 13:43:23', 'Read'),
(276, 29, 59, 'hi', '2025-11-17 13:43:40', 'Read'),
(277, 35, 29, 'hi', '2025-11-17 13:44:50', 'Read'),
(278, 35, 29, 'hii', '2025-11-17 13:47:24', 'Read'),
(279, 29, 35, 'hi', '2025-11-17 13:48:16', 'Read'),
(280, 29, 35, 'hi', '2025-11-17 13:48:25', 'Read'),
(281, 29, 59, 'hi', '2025-11-17 13:48:47', 'Read'),
(282, 59, 29, 'hi', '2025-11-17 13:55:02', 'Read'),
(283, 59, 29, 'hi', '2025-11-17 13:57:59', 'Read'),
(284, 59, 29, 'hi', '2025-11-17 13:59:28', 'Read'),
(285, 29, 35, 'good eves', '2025-11-17 14:03:09', 'Read'),
(286, 35, 29, 'eves', '2025-11-17 14:06:34', 'Read'),
(287, 29, 35, 'eves pud', '2025-11-17 14:07:32', 'Read'),
(288, 29, 35, 'kumusta?', '2025-11-17 14:07:44', 'Read'),
(289, 29, 35, 'bitaw', '2025-11-17 14:08:08', 'Read'),
(290, 35, 29, 'aw maayu', '2025-11-17 14:09:18', 'Read'),
(291, 29, 35, 'nicest', '2025-11-17 14:10:47', 'Read'),
(292, 35, 29, 'okayssss', '2025-11-17 14:19:17', 'Read'),
(293, 29, 35, 'lakaw', '2025-11-17 14:20:07', 'Read'),
(294, 29, 35, 'lakwssss', '2025-11-17 14:20:19', 'Read'),
(295, 29, 35, 'okaysss', '2025-11-17 14:20:29', 'Read'),
(296, 29, 35, 'yes', '2025-11-17 14:37:12', 'Sent'),
(297, 29, 35, 'morning', '2025-11-18 02:32:09', 'Sent'),
(298, 38, 29, 'hi', '2025-11-19 11:59:19', 'Read'),
(299, 38, 28, 'hi', '2025-11-19 11:59:49', 'Read'),
(300, 29, 44, 'hissy', '2025-11-19 12:43:03', 'Read'),
(301, 29, 44, 'hi', '2025-11-19 12:43:18', 'Read'),
(302, 29, 44, 'kijj', '2025-11-19 12:43:23', 'Read'),
(303, 38, 29, 'hii', '2025-11-19 12:58:11', 'Read'),
(304, 38, 29, 'H**lo', '2025-11-19 13:19:11', 'Read'),
(305, 38, 29, 'yawa', '2025-11-19 13:19:19', 'Read'),
(306, 38, 29, 'b**o', '2025-11-19 13:19:27', 'Read'),
(307, 38, 29, 'b**o', '2025-11-19 13:19:41', 'Read'),
(308, 38, 29, 'jasjsjsjsjbssnbxbxxbxbxbfbfbfbfbfbffbffbfbfbfbfncnfnfnfnfnfnfnfnfnfnfnfnfnfnfnfnfnfnfnfnfnfnfdjdjdjdjrjrjrjrjrjrj', '2025-11-19 13:31:50', 'Read'),
(309, 29, 28, 'Hi', '2025-11-19 13:57:51', 'Read'),
(310, 29, 38, 'B**o ka', '2025-11-20 05:16:06', 'Sent'),
(311, 29, 38, 'Putangina mo', '2025-11-20 05:16:54', 'Sent'),
(312, 29, 38, 'P*******a mo', '2025-11-20 05:17:17', 'Sent'),
(313, 29, 38, 'Hi', '2025-11-20 05:17:34', 'Sent'),
(314, 59, 29, 'hi', '2025-11-20 05:21:08', 'Read'),
(315, 28, 29, 'Hi', '2025-11-20 06:47:43', 'Read'),
(316, 29, 28, 'Hi', '2025-11-20 06:48:33', 'Read'),
(317, 29, 28, 'Hi', '2025-11-20 06:49:14', 'Read'),
(318, 29, 28, 'Hi', '2025-11-20 06:53:26', 'Read'),
(319, 29, 28, 'Hikims', '2025-11-20 06:53:40', 'Read'),
(320, 29, 28, 'Hiiihi po', '2025-11-20 06:54:07', 'Read'),
(321, 29, 28, 'Jhhh', '2025-11-20 06:54:49', 'Read'),
(322, 28, 29, 'hiii', '2025-11-20 06:55:53', 'Read'),
(323, 29, 28, 'His', '2025-11-20 06:56:57', 'Read'),
(324, 28, 29, 'Hi kimmmmm', '2025-11-20 07:10:37', 'Read'),
(325, 29, 28, 'uy hallo', '2025-11-20 07:11:06', 'Read'),
(326, 28, 29, 'Halo', '2025-11-20 10:48:31', 'Read'),
(327, 29, 28, 'Hrllo', '2025-11-20 10:49:11', 'Read'),
(328, 28, 29, 'Najsjsjs', '2025-11-21 01:00:44', 'Read'),
(329, 29, 28, 'Uyss', '2025-11-21 01:01:03', 'Read'),
(330, 28, 29, 'Ho kim', '2025-11-21 05:38:33', 'Read'),
(331, 29, 28, 'Hytd', '2025-11-21 05:39:04', 'Read'),
(332, 28, 29, 'Bye ka oy', '2025-11-21 05:40:04', 'Read'),
(333, 29, 28, 'Ouh', '2025-11-21 05:40:41', 'Read'),
(334, 28, 29, 'Bsjsjssjsjsis', '2025-11-21 06:03:49', 'Read'),
(335, 28, 29, 'Oyoyoy', '2025-11-21 06:05:01', 'Read'),
(336, 28, 29, 'Hooo', '2025-11-21 06:09:18', 'Read'),
(337, 28, 29, 'Yes', '2025-11-21 06:10:43', 'Read'),
(338, 28, 29, 'Kims', '2025-11-21 06:11:07', 'Read'),
(339, 29, 28, 'Kim', '2025-11-23 06:02:50', 'Read'),
(340, 28, 29, 'Namz are you online?', '2025-11-24 05:18:24', 'Read'),
(341, 28, 29, 'Hahaha mag merge ko', '2025-11-24 05:18:40', 'Read'),
(342, 44, 29, 'Sddd', '2025-11-25 11:36:55', 'Sent'),
(343, 28, 29, 'Yesss', '2025-12-01 05:49:59', 'Read'),
(344, 28, 29, 'Hi', '2025-12-01 05:51:25', 'Read'),
(345, 28, 29, 'Hi', '2025-12-01 05:52:42', 'Read'),
(346, 28, 29, 'hiiii', '2025-12-01 05:53:55', 'Read'),
(347, 28, 29, 'Huuu', '2025-12-01 05:54:34', 'Read'),
(348, 28, 29, 'Hi po', '2025-12-01 05:55:53', 'Read'),
(349, 29, 28, 'Hii', '2025-12-01 05:57:50', 'Read'),
(350, 29, 28, 'Hi', '2025-12-01 06:00:35', 'Read'),
(351, 29, 28, 'Hin', '2025-12-01 06:02:04', 'Read'),
(352, 29, 28, 'Hiiiiii', '2025-12-01 06:12:34', 'Read'),
(353, 29, 28, 'Hi pooooo', '2025-12-01 06:14:19', 'Read'),
(354, 28, 29, 'Hii', '2025-12-06 03:29:47', 'Sent');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `notif_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `notif_title` varchar(150) NOT NULL,
  `notif_message` text NOT NULL,
  `notif_type` enum('booking','payment','announcement','maintenance','general') DEFAULT 'general',
  `notif_status` enum('unread','read') DEFAULT 'unread',
  `notif_created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`notif_id`, `user_id`, `notif_title`, `notif_message`, `notif_type`, `notif_status`, `notif_created_at`) VALUES
(164, 28, 'Registration Approved', 'Congratulations! Your BH Owner account has been approved. You can now log in to BoardEase.', 'general', 'read', '2025-11-09 23:38:39'),
(165, 29, 'Registration Approved', 'Congratulations! Your BH Owner account has been approved. You can now log in to BoardEase.', 'general', 'read', '2025-11-09 23:41:19'),
(166, 6, 'Important Matters', 'will going to have a meeting in the afternoon', 'general', 'unread', '2025-11-09 23:59:10'),
(167, 27, 'Important Matters', 'will going to have a meeting in the afternoon', 'general', 'unread', '2025-11-09 23:59:12'),
(168, 24, 'Important Matters', 'will going to have a meeting in the afternoon', 'general', 'unread', '2025-11-09 23:59:12'),
(169, 29, 'Important Matters', 'will going to have a meeting in the afternoon', 'general', 'read', '2025-11-09 23:59:13'),
(170, 36, 'Important Matters', 'will going to have a meeting in the afternoon', 'general', 'unread', '2025-11-09 23:59:13'),
(171, 37, 'Important Matters', 'will going to have a meeting in the afternoon', 'general', 'read', '2025-11-09 23:59:14'),
(172, 40, 'Important Matters', 'will going to have a meeting in the afternoon', 'general', 'unread', '2025-11-09 23:59:14'),
(173, 2, 'Meeting', 'will have a meeting afternoon', 'announcement', 'unread', '2025-11-10 00:15:42'),
(174, 1, 'Meeting', 'will have a meeting afternoon', 'announcement', 'unread', '2025-11-10 00:15:42'),
(175, 4, 'Meeting', 'will have a meeting afternoon', 'announcement', 'unread', '2025-11-10 00:15:43'),
(176, 58, 'Meeting', 'will have a meeting afternoon', 'announcement', 'unread', '2025-11-10 00:15:43'),
(177, 23, 'Meeting', 'will have a meeting afternoon', 'announcement', 'unread', '2025-11-10 00:15:43'),
(178, 28, 'Meeting', 'will have a meeting afternoon', 'announcement', 'read', '2025-11-10 00:15:43'),
(179, 35, 'Meeting', 'will have a meeting afternoon', 'announcement', 'read', '2025-11-10 00:15:43'),
(180, 38, 'Meeting', 'will have a meeting afternoon', 'announcement', 'read', '2025-11-10 00:15:43'),
(181, 44, 'Meeting', 'will have a meeting afternoon', 'announcement', 'read', '2025-11-10 00:15:43'),
(182, 45, 'New Booking Request', 'You have a new booking request from Ruel Cuas for single a', 'booking', 'unread', '2025-11-10 00:18:40'),
(183, 59, 'Registration Approved', 'Congratulations! Your User account has been approved. You can now log in to BoardEase.', '', 'read', '2025-11-10 00:31:11'),
(186, 62, 'Registration Approved', 'Congratulations! Your User account has been approved. You can now log in to BoardEase.', '', 'read', '2025-11-10 01:18:08'),
(187, 44, 'Booking Declined', 'Your booking request for single a has been declined. Reason: Declined by owner', 'booking', 'read', '2025-11-10 01:25:56'),
(188, 45, 'New Booking Request', 'You have a new booking request from Ruel Cuas for single a', 'booking', 'unread', '2025-11-10 01:27:48'),
(189, 44, 'Booking Approved', 'Your booking request for single a has been approved!', 'booking', 'read', '2025-11-10 01:39:28'),
(190, 45, 'New Booking Request', 'You have a new booking request from John Sagetarios for Room 2', 'booking', 'unread', '2025-11-10 01:48:57'),
(191, 59, 'Booking Declined', 'Your booking request for Room 2 has been declined. Reason: Declined by owner', 'booking', 'read', '2025-11-10 01:49:45'),
(192, 6, 'Meeting', 'meeting!', 'announcement', 'unread', '2025-11-10 01:53:33'),
(193, 27, 'Meeting', 'meeting!', 'announcement', 'unread', '2025-11-10 01:53:34'),
(194, 24, 'Meeting', 'meeting!', 'announcement', 'unread', '2025-11-10 01:53:34'),
(195, 29, 'Meeting', 'meeting!', 'announcement', 'read', '2025-11-10 01:53:35'),
(196, 36, 'Meeting', 'meeting!', 'announcement', 'unread', '2025-11-10 01:53:36'),
(197, 37, 'Meeting', 'meeting!', 'announcement', 'read', '2025-11-10 01:53:37'),
(198, 40, 'Meeting', 'meeting!', 'announcement', 'unread', '2025-11-10 01:53:37'),
(199, 2, 'Meeting', 'meeting all!!!', 'announcement', 'unread', '2025-11-10 01:53:58'),
(200, 1, 'Meeting', 'meeting all!!!', 'announcement', 'unread', '2025-11-10 01:53:58'),
(201, 4, 'Meeting', 'meeting all!!!', 'announcement', 'unread', '2025-11-10 01:53:59'),
(202, 6, 'Meeting', 'meeting all!!!', 'announcement', 'unread', '2025-11-10 01:53:59'),
(203, 58, 'Meeting', 'meeting all!!!', 'announcement', 'unread', '2025-11-10 01:54:00'),
(204, 27, 'Meeting', 'meeting all!!!', 'announcement', 'unread', '2025-11-10 01:54:00'),
(205, 24, 'Meeting', 'meeting all!!!', 'announcement', 'unread', '2025-11-10 01:54:00'),
(206, 23, 'Meeting', 'meeting all!!!', 'announcement', 'unread', '2025-11-10 01:54:01'),
(207, 28, 'Meeting', 'meeting all!!!', 'announcement', 'read', '2025-11-10 01:54:01'),
(208, 29, 'Meeting', 'meeting all!!!', 'announcement', 'read', '2025-11-10 01:54:01'),
(209, 35, 'Meeting', 'meeting all!!!', 'announcement', 'read', '2025-11-10 01:54:01'),
(210, 36, 'Meeting', 'meeting all!!!', 'announcement', 'unread', '2025-11-10 01:54:01'),
(211, 37, 'Meeting', 'meeting all!!!', 'announcement', 'read', '2025-11-10 01:54:02'),
(212, 38, 'Meeting', 'meeting all!!!', 'announcement', 'read', '2025-11-10 01:54:02'),
(213, 40, 'Meeting', 'meeting all!!!', 'announcement', 'unread', '2025-11-10 01:54:02'),
(214, 44, 'Meeting', 'meeting all!!!', 'announcement', 'read', '2025-11-10 01:54:03'),
(215, 59, 'Meeting', 'meeting all!!!', 'announcement', 'read', '2025-11-10 01:54:03'),
(216, 62, 'Meeting', 'meeting all!!!', 'announcement', 'read', '2025-11-10 01:54:03'),
(217, 45, 'New Booking Request', 'You have a new booking request from John Sagetarios for Room 2', 'booking', 'unread', '2025-11-10 01:59:17'),
(218, 59, 'Booking Declined', 'Your booking request for Room 2 has been declined. Reason: Declined by owner', 'booking', 'read', '2025-11-10 02:03:08'),
(219, 45, 'New Booking Request', 'You have a new booking request from John Sagetarios for Room 2', 'booking', 'unread', '2025-11-10 02:05:13'),
(220, 59, 'Booking Declined', 'Your booking request for Room 2 has been declined. Reason: Declined by owner', 'booking', 'read', '2025-11-10 02:14:30'),
(221, 29, 'New Booking Request', 'You have a new booking request from John Sagetarios for Room 2', 'booking', 'read', '2025-11-10 02:16:05'),
(222, 59, 'Booking Declined', 'Your booking request for Room 2 has been declined. Reason: Declined by owner', 'booking', 'read', '2025-11-10 02:17:03'),
(223, 29, 'New Booking Request', 'You have a new booking request from Ruel Cuas for Room 2', 'booking', 'read', '2025-11-10 03:53:04'),
(224, 29, 'New Booking Request', 'You have a new booking request from John Sagetarios for Room 2', 'booking', 'read', '2025-11-10 11:22:24'),
(225, 29, 'New Booking Request', 'You have a new booking request from Ruel Cuas for Group A', 'booking', 'read', '2025-11-10 11:58:59'),
(226, 29, 'New Booking Request', 'You have a new booking request from Ruel Cuas for Private Room 01', 'booking', 'read', '2025-11-13 01:41:12'),
(227, 29, 'New Payment Pending', 'A new payment of ₱166.67 is pending for Payment for Private Room 01 at Kikyam BH', 'payment', 'read', '2025-11-13 02:27:51'),
(228, 29, 'New Booking Request', 'You have a new booking request from Ruel Cuas for Private Room 01', 'booking', 'read', '2025-11-13 02:27:52'),
(229, 35, 'Payment Status Updated', 'Your payment of ₱166.67 status has been updated to: Fully Paid', 'payment', 'read', '2025-11-13 02:29:43'),
(230, 29, 'Payment Received', 'Payment of ₱166.67 has been received for Payment for Private Room 01 at Kikyam BH', 'payment', 'read', '2025-11-13 02:29:43'),
(231, 35, 'Payment Status Updated', 'Your payment of ₱166.67 status has been updated to: Fully Paid', 'payment', 'read', '2025-11-13 02:29:46'),
(232, 29, 'Payment Received', 'Payment of ₱166.67 has been received for Payment for Private Room 01 at Kikyam BH', 'payment', 'read', '2025-11-13 02:29:46'),
(233, 35, 'Payment Status Updated', 'Your payment of ₱166.67 status has been updated to: Fully Paid', 'payment', 'read', '2025-11-13 02:31:09'),
(234, 29, 'Payment Received', 'Payment of ₱166.67 has been received for Payment for Private Room 01 at Kikyam BH', 'payment', 'read', '2025-11-13 02:31:09'),
(235, 35, 'Payment Status Updated', 'Your payment of ₱166.67 status has been updated to: Fully Paid', 'payment', 'read', '2025-11-13 02:31:11'),
(236, 29, 'Payment Received', 'Payment of ₱166.67 has been received for Payment for Private Room 01 at Kikyam BH', 'payment', 'read', '2025-11-13 02:31:12'),
(237, 35, 'Payment Status Updated', 'Your payment of ₱166.67 status has been updated to: Fully Paid', 'payment', 'read', '2025-11-13 02:33:49'),
(238, 29, 'Payment Received', 'Payment of ₱166.67 has been received for Payment for Private Room 01 at Kikyam BH', 'payment', 'read', '2025-11-13 02:33:49'),
(239, 35, 'Payment Status Updated', 'Your payment of ₱166.67 status has been updated to: Fully Paid', 'payment', 'read', '2025-11-13 02:33:51'),
(240, 29, 'Payment Received', 'Payment of ₱166.67 has been received for Payment for Private Room 01 at Kikyam BH', 'payment', 'read', '2025-11-13 02:33:51'),
(241, 29, 'New Payment Pending', 'A new payment of ₱166.67 is pending for Payment for Private Room 01 at Kikyam BH', 'payment', 'read', '2025-11-13 02:42:53'),
(242, 29, 'New Booking Request', 'You have a new booking request from Ruel Cuas for Private Room 01', 'booking', 'read', '2025-11-13 02:42:54'),
(243, 35, 'Booking Approved', 'Your booking request for Private Room 01 has been approved!', 'booking', 'read', '2025-11-13 02:43:55'),
(244, 35, 'Payment Status Updated', 'Your payment of ₱166.67 status has been updated to: Fully Paid', 'payment', 'read', '2025-11-13 02:44:18'),
(245, 29, 'Payment Received', 'Payment of ₱166.67 has been received for Payment for Private Room 01 at Kikyam BH', 'payment', 'read', '2025-11-13 02:44:18'),
(246, 35, 'Payment Status Updated', 'Your payment of ₱166.67 status has been updated to: Fully Paid', 'payment', 'read', '2025-11-13 02:44:20'),
(247, 29, 'Payment Received', 'Payment of ₱166.67 has been received for Payment for Private Room 01 at Kikyam BH', 'payment', 'read', '2025-11-13 02:44:20'),
(248, 29, 'New Payment Pending', 'A new payment of ₱166.67 is pending for Payment for Private Room 01 at Kikyam BH', 'payment', 'read', '2025-11-13 02:54:00'),
(249, 29, 'New Booking Request', 'You have a new booking request from Ruel Cuas for Private Room 01', 'booking', 'read', '2025-11-13 02:54:01'),
(250, 35, 'Booking Approved', 'Your booking request for Private Room 01 has been approved!', 'booking', 'read', '2025-11-13 02:54:43'),
(251, 35, 'Payment Received', 'Payment of ₱166.67 has been received for Payment for Private Room 01 at Kikyam BH', 'payment', 'read', '2025-11-13 02:55:29'),
(252, 29, 'Payment Received', 'Payment of ₱166.67 has been received for Payment for Private Room 01 at Kikyam BH', 'payment', 'read', '2025-11-13 02:55:29'),
(253, 35, 'Payment Received', 'Payment of ₱166.67 has been received for Payment for Private Room 01 at Kikyam BH', 'payment', 'read', '2025-11-13 02:55:31'),
(254, 29, 'Payment Received', 'Payment of ₱166.67 has been received for Payment for Private Room 01 at Kikyam BH', 'payment', 'read', '2025-11-13 02:55:32'),
(255, 1, 'New Payment Pending', 'A new payment of ₱510.00 is pending for Payment for Kim Hauz and Room at BH CUAS', 'payment', 'unread', '2025-11-13 03:00:05'),
(256, 1, 'New Booking Request', 'You have a new booking request from Liz Uy for Kim Hauz and Room', 'booking', 'unread', '2025-11-13 03:00:14'),
(257, 29, 'New Payment Pending', 'A new payment of ₱5,000.00 is pending for Payment for Private Room 01 at Kikyam BH', 'payment', 'read', '2025-11-13 03:04:06'),
(258, 29, 'New Booking Request', 'You have a new booking request from Liz Uy for Private Room 01', 'booking', 'read', '2025-11-13 03:04:07'),
(259, 29, 'New Payment Pending', 'A new payment of ₱166.67 is pending for Payment for Private Room 01 at Kikyam BH', 'payment', 'read', '2025-11-13 03:04:26'),
(260, 29, 'New Booking Request', 'You have a new booking request from Ruel Cuas for Private Room 01', 'booking', 'read', '2025-11-13 03:04:26'),
(261, 28, 'Booking Approved', 'Your booking request for Private Room 01 has been approved!', 'booking', 'read', '2025-11-13 03:05:29'),
(262, 35, 'Booking Approved', 'Your booking request for Private Room 01 has been approved!', 'booking', 'read', '2025-11-13 03:05:50'),
(263, 28, 'Payment Status Updated', 'Your payment of ₱5,000.00 status has been updated to: Completed/Partially', 'payment', 'read', '2025-11-13 03:06:26'),
(264, 29, 'Payment Received', 'Payment of ₱5,000.00 has been received for Payment for Private Room 01 at Kikyam BH', 'payment', 'read', '2025-11-13 03:06:26'),
(265, 28, 'Payment Status Updated', 'Your payment of ₱5,000.00 status has been updated to: Completed/Partially', 'payment', 'read', '2025-11-13 03:06:28'),
(266, 29, 'Payment Received', 'Payment of ₱5,000.00 has been received for Payment for Private Room 01 at Kikyam BH', 'payment', 'read', '2025-11-13 03:06:28'),
(267, 35, 'Payment Status Updated', 'Your payment of ₱166.67 status has been updated to: Fully Paid', 'payment', 'read', '2025-11-13 03:15:44'),
(268, 29, 'Payment Received', 'Payment of ₱166.67 has been received for Payment for Private Room 01 at Kikyam BH', 'payment', 'read', '2025-11-13 03:15:44'),
(269, 29, 'New Payment Pending', 'A new payment of ₱166.67 is pending for Payment for Private Room 01 at Kikyam BH', 'payment', 'read', '2025-11-13 03:20:21'),
(270, 29, 'New Booking Request', 'You have a new booking request from Ruel Cuas for Private Room 01', 'booking', 'read', '2025-11-13 03:20:22'),
(271, 35, 'Booking Approved', 'Your booking request for Private Room 01 has been approved!', 'booking', 'read', '2025-11-13 03:22:59'),
(272, 35, 'Payment Status Updated', 'Your payment of ₱166.67 status has been updated to: Fully Paid', 'payment', 'read', '2025-11-13 03:23:26'),
(273, 29, 'Payment Received', 'Payment of ₱166.67 has been received for Payment for Private Room 01 at Kikyam BH', 'payment', 'read', '2025-11-13 03:23:27'),
(274, 29, 'New Payment Pending', 'A new payment of ₱166.67 is pending for Payment for Private Room 01 at Kikyam BH', 'payment', 'read', '2025-11-13 03:36:23'),
(275, 29, 'New Booking Request', 'You have a new booking request from Ruel Cuas for Private Room 01', 'booking', 'read', '2025-11-13 03:36:24'),
(276, 35, 'Booking Approved', 'Your booking request for Private Room 01 has been approved!', 'booking', 'read', '2025-11-13 03:37:25'),
(277, 35, 'Payment Status Updated', 'Your payment of ₱166.67 status has been updated to: Fully Paid', 'payment', 'read', '2025-11-13 03:37:48'),
(278, 29, 'Payment Received', 'Payment of ₱166.67 has been received for Payment for Private Room 01 at Kikyam BH', 'payment', 'read', '2025-11-13 03:37:48'),
(279, 29, 'New Payment Pending', 'A new payment of ₱166.67 is pending for Payment for Private Room 01 at Kikyam BH', 'payment', 'read', '2025-11-13 03:47:17'),
(280, 29, 'New Booking Request', 'You have a new booking request from Ruel Cuas for Private Room 01', 'booking', 'read', '2025-11-13 03:47:18'),
(281, 35, 'Booking Approved', 'Your booking request for Private Room 01 has been approved!', 'booking', 'read', '2025-11-13 03:48:22'),
(282, 35, 'Payment Status Updated', 'Your payment of ₱166.67 status has been updated to: Fully Paid', 'payment', 'read', '2025-11-13 03:48:39'),
(283, 29, 'Payment Received', 'Payment of ₱166.67 has been received for Payment for Private Room 01 at Kikyam BH', 'payment', 'read', '2025-11-13 03:48:39'),
(284, 28, 'New Message from Namz Baer', 'hi kim', 'general', 'read', '2025-11-13 05:34:04'),
(285, 28, 'New Message from Namz Baer', 'okay rana', 'general', 'read', '2025-11-13 05:34:08'),
(286, 59, 'New Message from Namz Baer', 'h**lo', 'general', 'read', '2025-11-13 05:34:30'),
(287, 59, 'New Message from Namz Baer', 'hi', 'general', 'read', '2025-11-13 05:34:44'),
(288, 59, 'New Message from Namz Baer', 'ug', 'general', 'read', '2025-11-13 05:35:52'),
(289, 59, 'New Message from Namz Baer', 'yuh', 'general', 'read', '2025-11-13 05:35:59'),
(290, 59, 'New Message from Namz Baer', 'ily', 'general', 'read', '2025-11-13 05:36:07'),
(291, 28, 'Group b', 'Namz Baer: 😄😅😙😂', 'general', 'read', '2025-11-13 05:40:49'),
(292, 1, 'Group b', 'Namz Baer: 😄😅😙😂', 'general', 'unread', '2025-11-13 05:40:49'),
(293, 59, 'New Message from Namz Baer', 'euuudru', 'general', 'read', '2025-11-13 05:41:02'),
(294, 28, 'GG', 'Namz Baer: ehjrkydhh', 'general', 'read', '2025-11-13 05:41:30'),
(295, 1, 'GG', 'Namz Baer: ehjrkydhh', 'general', 'unread', '2025-11-13 05:41:30'),
(296, 29, 'New Message from Liz Uy', 'ouh', 'general', 'read', '2025-11-13 05:41:56'),
(297, 29, 'New Message from Liz Uy', 'ouh', 'general', 'read', '2025-11-13 05:41:59'),
(298, 29, 'New Message from Liz Uy', 'okay ra. bitaw ko', 'general', 'read', '2025-11-13 05:42:09'),
(299, 29, 'New Message from Liz Uy', 'okay ra. bitaw ko', 'general', 'read', '2025-11-13 05:42:12'),
(300, 28, 'New Message from Namz Baer', 'hahahaa', 'general', 'read', '2025-11-13 06:04:47'),
(301, 29, 'New Payment Pending', 'A new payment of ₱166.67 is pending for Payment for Private Room 01 at Kikyam BH', 'payment', 'read', '2025-11-13 06:06:18'),
(302, 29, 'New Booking Request', 'You have a new booking request from Liz Uy for Private Room 01', 'booking', 'read', '2025-11-13 06:06:19'),
(303, 28, 'Payment Status Updated', 'Your payment of ₱166.67 status has been updated to: Fully Paid', 'payment', 'read', '2025-11-13 06:06:55'),
(304, 29, 'Payment Received', 'Payment of ₱166.67 has been received for Payment for Private Room 01 at Kikyam BH', 'payment', 'read', '2025-11-13 06:06:55'),
(305, 29, 'New Payment Pending', 'A new payment of ₱166.67 is pending for Payment for Private Room 01 at Kikyam BH', 'payment', 'read', '2025-11-13 06:16:27'),
(306, 29, 'New Booking Request', 'You have a new booking request from Liz Uy for Private Room 01', 'booking', 'read', '2025-11-13 06:16:28'),
(307, 28, 'Booking Approved', 'Your booking request for Private Room 01 has been approved!', 'booking', 'read', '2025-11-13 06:17:23'),
(308, 28, 'Payment Status Updated', 'Your payment of ₱166.67 status has been updated to: Fully Paid', 'payment', 'read', '2025-11-13 06:18:06'),
(309, 29, 'Payment Received', 'Payment of ₱166.67 has been received for Payment for Private Room 01 at Kikyam BH', 'payment', 'read', '2025-11-13 06:18:06'),
(310, 2, 'Meeting', 'meeting!!!', 'announcement', 'unread', '2025-11-15 02:44:03'),
(311, 1, 'Meeting', 'meeting!!!', 'announcement', 'unread', '2025-11-15 02:44:03'),
(312, 4, 'Meeting', 'meeting!!!', 'announcement', 'unread', '2025-11-15 02:44:04'),
(313, 6, 'Meeting', 'meeting!!!', 'announcement', 'unread', '2025-11-15 02:44:04'),
(314, 58, 'Meeting', 'meeting!!!', 'announcement', 'unread', '2025-11-15 02:44:05'),
(315, 27, 'Meeting', 'meeting!!!', 'announcement', 'unread', '2025-11-15 02:44:05'),
(316, 24, 'Meeting', 'meeting!!!', 'announcement', 'unread', '2025-11-15 02:44:05'),
(317, 23, 'Meeting', 'meeting!!!', 'announcement', 'unread', '2025-11-15 02:44:06'),
(318, 28, 'Meeting', 'meeting!!!', 'announcement', 'read', '2025-11-15 02:44:06'),
(319, 29, 'Meeting', 'meeting!!!', 'announcement', 'read', '2025-11-15 02:44:06'),
(320, 35, 'Meeting', 'meeting!!!', 'announcement', 'read', '2025-11-15 02:44:07'),
(321, 36, 'Meeting', 'meeting!!!', 'announcement', 'unread', '2025-11-15 02:44:07'),
(322, 37, 'Meeting', 'meeting!!!', 'announcement', 'read', '2025-11-15 02:44:08'),
(323, 38, 'Meeting', 'meeting!!!', 'announcement', 'read', '2025-11-15 02:44:08'),
(324, 40, 'Meeting', 'meeting!!!', 'announcement', 'unread', '2025-11-15 02:44:08'),
(325, 44, 'Meeting', 'meeting!!!', 'announcement', 'read', '2025-11-15 02:44:09'),
(326, 59, 'Meeting', 'meeting!!!', 'announcement', 'read', '2025-11-15 02:44:09'),
(327, 62, 'Meeting', 'meeting!!!', 'announcement', 'read', '2025-11-15 02:44:09'),
(328, 29, 'New Payment Pending', 'A new payment of ₱166.67 is pending for Payment for Private Room 01 at Kikyam BH', 'payment', 'read', '2025-11-15 11:23:27'),
(329, 29, 'New Booking Request', 'You have a new booking request from John Sagetarios for Private Room 01', 'booking', 'read', '2025-11-15 11:23:27'),
(330, 59, 'Payment Status Updated', 'Your payment of ₱166.67 status has been updated to: Fully Paid', 'payment', 'read', '2025-11-15 11:26:12'),
(331, 29, 'Payment Received', 'Payment of ₱166.67 has been received for Payment for Private Room 01 at Kikyam BH', 'payment', 'read', '2025-11-15 11:26:12'),
(332, 29, 'New Payment Pending', 'A new payment of ₱166.67 is pending for Payment for Private Room 01 at Kikyam BH', 'payment', 'read', '2025-11-15 11:31:00'),
(333, 29, 'New Booking Request', 'You have a new booking request from John Sagetarios for Private Room 01', 'booking', 'read', '2025-11-15 11:31:01'),
(334, 59, 'Booking Approved', 'Your booking request for Private Room 01 has been checked and approved!', 'booking', 'read', '2025-11-15 11:47:25'),
(335, 59, 'Payment Status Updated', 'Your payment of ₱166.67 status has been updated to: Fully Paid', 'payment', 'read', '2025-11-15 11:48:05'),
(336, 29, 'Payment Received', 'Payment of ₱166.67 has been received for Payment for Private Room 01 at Kikyam BH', 'payment', 'read', '2025-11-15 11:48:05'),
(337, 29, 'New Payment Pending', 'A new payment of ₱166.67 is pending for Payment for Private Room 01 at Kikyam BH', 'payment', 'read', '2025-11-15 13:09:44'),
(338, 29, 'New Booking Request', 'You have a new booking request from Ruel Cuas for Private Room 01', 'booking', 'read', '2025-11-15 13:09:45'),
(339, 35, 'Booking Approved', 'Your booking request for Private Room 01 has been checked and approved!', 'booking', 'read', '2025-11-15 13:28:20'),
(340, 29, 'New Payment Pending', 'A new payment of ₱166.67 is pending for Payment for Private Room 01 at Kikyam BH', 'payment', 'read', '2025-11-15 13:38:04'),
(341, 29, 'New Booking Request', 'You have a new booking request from Ruel Cuas for Private Room 01', 'booking', 'read', '2025-11-15 13:38:05'),
(342, 35, 'Booking Approved', 'Your booking request for Private Room 01 has been checked and approved!', 'booking', 'read', '2025-11-15 13:39:29'),
(343, 29, 'New Payment Pending', 'A new payment of ₱166.67 is pending for Payment for Private Room 01 at Kikyam BH', 'payment', 'read', '2025-11-15 13:55:19'),
(344, 29, 'New Booking Request', 'You have a new booking request from Ruel Cuas for Private Room 01', 'booking', 'read', '2025-11-15 13:55:20'),
(345, 35, 'Booking Approved', 'Your booking request for Private Room 01 has been checked and approved!', 'booking', 'read', '2025-11-15 13:56:04'),
(346, 29, 'Payment Received', 'Payment of ₱166.67 has been received for from boarder for Private Room 01 at Kikyam BH. Status: Fully Paid', 'payment', 'read', '2025-11-15 13:56:04'),
(347, 35, 'Payment Status Updated', 'Your payment of ₱166.67 status has been updated to: Fully Paid', 'payment', 'read', '2025-11-15 13:56:05'),
(348, 29, 'New Payment Pending', 'A new payment of ₱5,000.00 is pending for Payment for Private Room 01 at Kikyam BH', 'payment', 'read', '2025-11-16 02:47:37'),
(349, 29, 'New Booking Request', 'You have a new booking request from John Mark Sagetarios for Private Room 01', 'booking', 'read', '2025-11-16 02:47:38'),
(350, 38, 'Booking Approved', 'Your booking request for Private Room 01 has been checked and approved!', 'booking', 'read', '2025-11-16 04:28:02'),
(351, 29, 'Payment Received', 'Payment of ₱7,500.00 has been received for from boarder for Private Room 01 at Kikyam BH. Status: Fully Paid', 'payment', 'read', '2025-11-16 04:28:02'),
(352, 38, 'Payment Status Updated', 'Your payment of ₱7,500.00 status has been updated to: Fully Paid', 'payment', 'read', '2025-11-16 04:28:03'),
(353, 29, 'New Payment Pending', 'A new payment of ₱5,000.00 is pending for Payment for Private Room 01 at Kikyam BH', 'payment', 'read', '2025-11-16 04:32:59'),
(354, 29, 'New Booking Request', 'You have a new booking request from John Mark Sagetarios for Private Room 01', 'booking', 'read', '2025-11-16 04:33:00'),
(355, 38, 'Booking Approved', 'Your booking request for Private Room 01 has been checked and approved!', 'booking', 'read', '2025-11-16 04:34:21'),
(356, 29, 'Payment Received', 'Payment of ₱12,666.67 has been received for from boarder for Private Room 01 at Kikyam BH. Status: Fully Paid', 'payment', 'read', '2025-11-16 04:34:21'),
(357, 38, 'Payment Status Updated', 'Your payment of ₱12,666.67 status has been updated to: Fully Paid', 'payment', 'read', '2025-11-16 04:34:22'),
(358, 29, 'New Payment Pending', 'A new payment of ₱5,000.00 is pending for Payment for Private Room 01 at Kikyam BH', 'payment', 'read', '2025-11-16 04:40:50'),
(359, 29, 'New Booking Request', 'You have a new booking request from John Mark Sagetarios for Private Room 01', 'booking', 'read', '2025-11-16 04:40:51'),
(360, 38, 'Payment Status Updated', 'Your payment of ₱5,000.00 status has been updated to: Partially Paid', 'payment', 'read', '2025-11-16 04:41:28'),
(361, 29, 'Payment Received', 'Payment of ₱5,000.00 has been received for Payment for Private Room 01 at Kikyam BH', 'payment', 'read', '2025-11-16 04:41:28'),
(362, 29, 'New Payment Pending', 'A new payment of ₱5,000.00 is pending for Payment for Private Room 01 at Kikyam BH', 'payment', 'read', '2025-11-16 05:03:09'),
(363, 29, 'New Booking Request', 'You have a new booking request from John Mark Sagetarios for Private Room 01', 'booking', 'read', '2025-11-16 05:03:10'),
(364, 38, 'Booking Approved', 'Your booking request for Private Room 01 has been checked and approved!', 'booking', 'read', '2025-11-16 05:03:58'),
(365, 29, 'Payment Received', 'Payment of ₱5,000.00 has been received for from boarder for Private Room 01 at Kikyam BH. Status: Partially Paid', 'payment', 'read', '2025-11-16 05:03:58'),
(366, 38, 'Payment Status Updated', 'Your payment of ₱5,000.00 status has been updated to: Partially Paid', 'payment', 'read', '2025-11-16 05:03:59'),
(367, 29, 'New Payment Pending', 'A new payment of ₱5,000.00 is pending for Payment for Private Room 01 at Kikyam BH', 'payment', 'read', '2025-11-16 05:08:12'),
(368, 29, 'New Booking Request', 'You have a new booking request from John Mark Sagetarios for Private Room 01', 'booking', 'read', '2025-11-16 05:08:13'),
(369, 38, 'Payment Status Updated', 'Your payment of ₱5,000.00 status has been updated to: Partially Paid', 'payment', 'read', '2025-11-16 05:08:52'),
(370, 29, 'Payment Received', 'Payment of ₱5,000.00 has been received for Payment for Private Room 01 at Kikyam BH', 'payment', 'read', '2025-11-16 05:08:52'),
(371, 29, 'New Payment Pending', 'A new payment of ₱5,000.00 is pending for Payment for Private Room 01 at Kikyam BH', 'payment', 'read', '2025-11-16 05:14:37'),
(372, 29, 'New Booking Request', 'You have a new booking request from Ruel Cuas for Private Room 01', 'booking', 'read', '2025-11-16 05:14:38'),
(373, 44, 'Booking Approved', 'Your booking request for Private Room 01 has been checked and approved!', 'booking', 'read', '2025-11-16 05:15:21'),
(374, 29, 'Payment Received', 'Payment of ₱5,000.00 has been received for from boarder for Private Room 01 at Kikyam BH. Status: Partially Paid', 'payment', 'read', '2025-11-16 05:15:21'),
(375, 44, 'Payment Status Updated', 'Your payment of ₱5,000.00 status has been updated to: Partially Paid', 'payment', 'read', '2025-11-16 05:15:23'),
(376, 29, 'New Payment Pending', 'A new payment of ₱166.67 is pending for Payment for Private Room 01 at Kikyam BH', 'payment', 'read', '2025-11-17 12:31:09'),
(377, 29, 'New Booking Request', 'You have a new booking request from Ruel Cuas for Private Room 01', 'booking', 'read', '2025-11-17 12:31:10'),
(378, 35, 'Booking Approved', 'Your booking request for Private Room 01 has been checked and approved!', 'booking', 'read', '2025-11-17 12:33:32'),
(379, 29, 'Payment Received', 'Payment of ₱166.67 has been received for from boarder for Private Room 01 at Kikyam BH. Status: Fully Paid', 'payment', 'read', '2025-11-17 12:33:33'),
(380, 35, 'Payment Status Updated', 'Your payment of ₱166.67 status has been updated to: Fully Paid', 'payment', 'read', '2025-11-17 12:33:34'),
(381, 28, 'New Message', 'New message from Namz Baer: hi', 'general', 'read', '2025-11-17 12:42:25'),
(382, 2, 'New Announcement', 'meeting: meeting', 'announcement', 'unread', '2025-11-17 12:51:56'),
(383, 1, 'New Announcement', 'meeting: meeting', 'announcement', 'unread', '2025-11-17 12:51:56'),
(384, 4, 'New Announcement', 'meeting: meeting', 'announcement', 'unread', '2025-11-17 12:51:57'),
(385, 6, 'New Announcement', 'meeting: meeting', 'announcement', 'unread', '2025-11-17 12:51:57'),
(386, 58, 'New Announcement', 'meeting: meeting', 'announcement', 'unread', '2025-11-17 12:51:58'),
(387, 27, 'New Announcement', 'meeting: meeting', 'announcement', 'unread', '2025-11-17 12:51:58'),
(388, 24, 'New Announcement', 'meeting: meeting', 'announcement', 'unread', '2025-11-17 12:51:58'),
(389, 23, 'New Announcement', 'meeting: meeting', 'announcement', 'unread', '2025-11-17 12:51:59'),
(390, 28, 'New Announcement', 'meeting: meeting', 'announcement', 'read', '2025-11-17 12:51:59'),
(391, 29, 'New Announcement', 'meeting: meeting', 'announcement', 'read', '2025-11-17 12:51:59'),
(392, 35, 'New Announcement', 'meeting: meeting', 'announcement', 'read', '2025-11-17 12:52:00'),
(393, 36, 'New Announcement', 'meeting: meeting', 'announcement', 'unread', '2025-11-17 12:52:00'),
(394, 37, 'New Announcement', 'meeting: meeting', 'announcement', 'read', '2025-11-17 12:52:02'),
(395, 38, 'New Announcement', 'meeting: meeting', 'announcement', 'read', '2025-11-17 12:52:02'),
(396, 40, 'New Announcement', 'meeting: meeting', 'announcement', 'unread', '2025-11-17 12:52:02'),
(397, 44, 'New Announcement', 'meeting: meeting', 'announcement', 'read', '2025-11-17 12:52:03'),
(398, 59, 'New Announcement', 'meeting: meeting', 'announcement', 'read', '2025-11-17 12:52:03'),
(399, 62, 'New Announcement', 'meeting: meeting', 'announcement', 'read', '2025-11-17 12:52:03'),
(400, 28, 'New Message', 'New message from Namz Baer: h**lo', 'general', 'read', '2025-11-17 12:52:53'),
(401, 28, 'New Message', 'New message from Namz Baer: hii', 'general', 'read', '2025-11-17 12:53:03'),
(402, 6, 'New Announcement', 'Meeting: Meeting', 'announcement', 'unread', '2025-11-17 12:54:12'),
(403, 27, 'New Announcement', 'Meeting: Meeting', 'announcement', 'unread', '2025-11-17 12:54:15'),
(404, 24, 'New Announcement', 'Meeting: Meeting', 'announcement', 'unread', '2025-11-17 12:54:15'),
(405, 29, 'New Announcement', 'Meeting: Meeting', 'announcement', 'read', '2025-11-17 12:54:17'),
(406, 36, 'New Announcement', 'Meeting: Meeting', 'announcement', 'unread', '2025-11-17 12:54:18'),
(407, 37, 'New Announcement', 'Meeting: Meeting', 'announcement', 'read', '2025-11-17 12:54:20'),
(408, 40, 'New Announcement', 'Meeting: Meeting', 'announcement', 'unread', '2025-11-17 12:54:20'),
(409, 29, 'New Message', 'New message from Ruel Cuas: hi', 'general', 'read', '2025-11-17 12:56:46'),
(410, 29, 'New Message', 'New message from Ruel Cuas: hi', 'general', 'read', '2025-11-17 12:56:49'),
(411, 29, 'New Message', 'New message from John Sagetarios: hi', 'general', 'read', '2025-11-17 12:58:27'),
(412, 29, 'New Message', 'New message from John Sagetarios: hi', 'general', 'read', '2025-11-17 12:58:29'),
(413, 59, 'New Message', 'New message from Namz Baer: hi!', 'general', 'read', '2025-11-17 13:02:39'),
(414, 2, 'New Announcement', 'meeting: meeting', 'announcement', 'unread', '2025-11-17 13:04:06'),
(415, 1, 'New Announcement', 'meeting: meeting', 'announcement', 'unread', '2025-11-17 13:04:06'),
(416, 4, 'New Announcement', 'meeting: meeting', 'announcement', 'unread', '2025-11-17 13:04:07'),
(417, 58, 'New Announcement', 'meeting: meeting', 'announcement', 'unread', '2025-11-17 13:04:07'),
(418, 23, 'New Announcement', 'meeting: meeting', 'announcement', 'unread', '2025-11-17 13:04:07'),
(419, 28, 'New Announcement', 'meeting: meeting', 'announcement', 'read', '2025-11-17 13:04:07'),
(420, 35, 'New Announcement', 'meeting: meeting', 'announcement', 'read', '2025-11-17 13:04:07'),
(421, 38, 'New Announcement', 'meeting: meeting', 'announcement', 'read', '2025-11-17 13:04:08'),
(422, 44, 'New Announcement', 'meeting: meeting', 'announcement', 'read', '2025-11-17 13:04:08'),
(423, 59, 'New Announcement', 'meeting: meeting', 'announcement', 'read', '2025-11-17 13:04:08'),
(424, 62, 'New Announcement', 'meeting: meeting', 'announcement', 'read', '2025-11-17 13:04:08'),
(425, 29, 'New Message', 'New message from John Sagetarios: low', 'general', 'read', '2025-11-17 13:13:12'),
(426, 29, 'New Message', 'New message from John Sagetarios: low', 'general', 'read', '2025-11-17 13:13:15'),
(427, 35, 'New Message', 'New message from Namz Baer: hii', 'general', 'read', '2025-11-17 13:14:48'),
(428, 35, 'New Message', 'New message from Namz Baer: hii', 'general', 'read', '2025-11-17 13:14:51'),
(429, 35, 'New Message', 'New message from Namz Baer: hi', 'general', 'read', '2025-11-17 13:18:59'),
(430, 35, 'New Message', 'New message from Namz Baer: hi', 'general', 'read', '2025-11-17 13:19:02'),
(431, 29, 'New Message', 'New message from John Sagetarios: hii', 'general', 'read', '2025-11-17 13:19:47'),
(432, 29, 'New Message', 'New message from John Sagetarios: hii', 'general', 'read', '2025-11-17 13:19:49'),
(433, 59, 'New Message', 'New message from Namz Baer: hi', 'general', 'read', '2025-11-17 13:21:55'),
(434, 59, 'New Message', 'New message from Namz Baer: hi', 'general', 'read', '2025-11-17 13:21:57'),
(435, 59, 'New Message', 'New message from Namz Baer: hi', 'general', 'read', '2025-11-17 13:22:45'),
(436, 59, 'New Message', 'New message from Namz Baer: hi', 'general', 'read', '2025-11-17 13:22:48'),
(437, 59, 'New Message', 'New message from Namz Baer: hi', 'general', 'read', '2025-11-17 13:30:35'),
(438, 59, 'New Message', 'New message from Namz Baer: hi', 'general', 'read', '2025-11-17 13:30:37'),
(439, 59, 'New Message', 'New message from Namz Baer: hii', 'general', 'read', '2025-11-17 13:31:09'),
(440, 59, 'New Message', 'New message from Namz Baer: hii', 'general', 'read', '2025-11-17 13:31:12'),
(441, 35, 'New Message', 'New message from Namz Baer: low', 'general', 'read', '2025-11-17 13:34:14'),
(442, 35, 'New Message', 'New message from Namz Baer: low', 'general', 'read', '2025-11-17 13:34:16'),
(443, 35, 'New Message', 'New message from Namz Baer: low', 'general', 'read', '2025-11-17 13:34:45'),
(444, 35, 'New Message', 'New message from Namz Baer: low', 'general', 'read', '2025-11-17 13:34:48'),
(445, 6, 'New Announcement', 'meeting: meeting', 'announcement', 'unread', '2025-11-17 13:37:36'),
(446, 27, 'New Announcement', 'meeting: meeting', 'announcement', 'unread', '2025-11-17 13:37:37'),
(447, 24, 'New Announcement', 'meeting: meeting', 'announcement', 'unread', '2025-11-17 13:37:37'),
(448, 29, 'New Announcement', 'meeting: meeting', 'announcement', 'read', '2025-11-17 13:37:38'),
(449, 36, 'New Announcement', 'meeting: meeting', 'announcement', 'unread', '2025-11-17 13:37:39'),
(450, 37, 'New Announcement', 'meeting: meeting', 'announcement', 'read', '2025-11-17 13:37:40'),
(451, 40, 'New Announcement', 'meeting: meeting', 'announcement', 'unread', '2025-11-17 13:37:40'),
(452, 35, 'New Message', 'New message from Namz Baer: hi', 'general', 'read', '2025-11-17 13:39:25'),
(453, 35, 'New Message', 'New message from Namz Baer: hi', 'general', 'read', '2025-11-17 13:39:28'),
(454, 35, 'New Message', 'New message from Namz Baer: hii', 'general', 'read', '2025-11-17 13:42:04'),
(455, 35, 'New Message', 'New message from Namz Baer: hii', 'general', 'read', '2025-11-17 13:42:06'),
(456, 35, 'New Message', 'New message from Namz Baer: hii', 'general', 'read', '2025-11-17 13:43:23'),
(457, 35, 'New Message', 'New message from Namz Baer: hii', 'general', 'read', '2025-11-17 13:43:25'),
(458, 59, 'New Message', 'New message from Namz Baer: hi', 'general', 'read', '2025-11-17 13:43:40'),
(459, 59, 'New Message', 'New message from Namz Baer: hi', 'general', 'read', '2025-11-17 13:43:43'),
(460, 29, 'New Message', 'New message from Ruel Cuas: hi', 'general', 'read', '2025-11-17 13:44:50'),
(461, 29, 'New Message', 'New message from Ruel Cuas: hi', 'general', 'read', '2025-11-17 13:44:53'),
(462, 29, 'New Message', 'New message from Ruel Cuas: hii', 'general', 'read', '2025-11-17 13:47:24'),
(463, 29, 'New Message', 'New message from Ruel Cuas: hii', 'general', 'read', '2025-11-17 13:47:27'),
(464, 35, 'New Message', 'New message from Namz Baer: hi', 'general', 'read', '2025-11-17 13:48:16'),
(465, 35, 'New Message', 'New message from Namz Baer: hi', 'general', 'read', '2025-11-17 13:48:19'),
(466, 35, 'New Message', 'New message from Namz Baer: hi', 'general', 'read', '2025-11-17 13:48:25'),
(467, 35, 'New Message', 'New message from Namz Baer: hi', 'general', 'read', '2025-11-17 13:48:28'),
(468, 59, 'New Message', 'New message from Namz Baer: hi', 'general', 'read', '2025-11-17 13:48:47'),
(469, 59, 'New Message', 'New message from Namz Baer: hi', 'general', 'read', '2025-11-17 13:48:50'),
(470, 29, 'New Message', 'New message from John Sagetarios: hi', 'general', 'read', '2025-11-17 13:55:02'),
(471, 29, 'New Message', 'New message from John Sagetarios: hi', 'general', 'read', '2025-11-17 13:55:04'),
(472, 29, 'New Message', 'New message from John Sagetarios: hi', 'general', 'read', '2025-11-17 13:57:59'),
(473, 29, 'New Message', 'New message from John Sagetarios: hi', 'general', 'read', '2025-11-17 13:58:02'),
(474, 29, 'New Message', 'New message from John Sagetarios: hi', 'general', 'read', '2025-11-17 13:59:28'),
(475, 29, 'New Message', 'New message from John Sagetarios: hi', 'general', 'read', '2025-11-17 13:59:31'),
(476, 35, 'New Message', 'New message from Namz Baer: good eves', 'general', 'read', '2025-11-17 14:03:09'),
(477, 35, 'New Message', 'New message from Namz Baer: good eves', 'general', 'read', '2025-11-17 14:03:12'),
(478, 29, 'New Message', 'New message from Ruel Cuas: eves', 'general', 'read', '2025-11-17 14:06:34'),
(479, 29, 'New Message', 'New message from Ruel Cuas: eves', 'general', 'read', '2025-11-17 14:06:36'),
(480, 35, 'New Message', 'New message from Namz Baer: eves pud', 'general', 'read', '2025-11-17 14:07:32'),
(481, 35, 'New Message', 'New message from Namz Baer: eves pud', 'general', 'read', '2025-11-17 14:07:35'),
(482, 35, 'New Message', 'New message from Namz Baer: bitaw', 'general', 'read', '2025-11-17 14:08:08'),
(483, 35, 'New Message', 'New message from Namz Baer: bitaw', 'general', 'read', '2025-11-17 14:08:11'),
(484, 29, 'New Message', 'New message from Ruel Cuas: aw maayu', 'general', 'read', '2025-11-17 14:09:18'),
(485, 29, 'New Message', 'New message from Ruel Cuas: aw maayu', 'general', 'read', '2025-11-17 14:09:20'),
(486, 35, 'New Message', 'New message from Namz Baer: nicest', 'general', 'read', '2025-11-17 14:10:47'),
(487, 35, 'New Message', 'New message from Namz Baer: nicest', 'general', 'read', '2025-11-17 14:10:49'),
(488, 29, 'New Message', 'New message from Ruel Cuas: okayssss', 'general', 'read', '2025-11-17 14:19:17'),
(489, 29, 'New Message', 'New message from Ruel Cuas: okayssss', 'general', 'read', '2025-11-17 14:19:20'),
(490, 35, 'New Message', 'New message from Namz Baer: lakaw', 'general', 'read', '2025-11-17 14:20:07'),
(491, 35, 'New Message', 'New message from Namz Baer: lakaw', 'general', 'read', '2025-11-17 14:20:09'),
(492, 35, 'New Message', 'New message from Namz Baer: lakwssss', 'general', 'read', '2025-11-17 14:20:19'),
(493, 35, 'New Message', 'New message from Namz Baer: lakwssss', 'general', 'read', '2025-11-17 14:20:21'),
(494, 35, 'New Message', 'New message from Namz Baer: okaysss', 'general', 'read', '2025-11-17 14:20:29'),
(495, 35, 'New Message', 'New message from Namz Baer: okaysss', 'general', 'read', '2025-11-17 14:20:31'),
(496, 2, 'New Announcement', 'General: General', 'general', 'unread', '2025-11-17 14:22:05'),
(497, 1, 'New Announcement', 'General: General', 'general', 'unread', '2025-11-17 14:22:05'),
(498, 4, 'New Announcement', 'General: General', 'general', 'unread', '2025-11-17 14:22:06'),
(499, 6, 'New Announcement', 'General: General', 'general', 'unread', '2025-11-17 14:22:06'),
(500, 58, 'New Announcement', 'General: General', 'general', 'unread', '2025-11-17 14:22:07'),
(501, 27, 'New Announcement', 'General: General', 'general', 'unread', '2025-11-17 14:22:07'),
(502, 24, 'New Announcement', 'General: General', 'general', 'unread', '2025-11-17 14:22:07'),
(503, 23, 'New Announcement', 'General: General', 'general', 'unread', '2025-11-17 14:22:08'),
(504, 28, 'New Announcement', 'General: General', 'general', 'read', '2025-11-17 14:22:08'),
(505, 29, 'New Announcement', 'General: General', 'general', 'read', '2025-11-17 14:22:08'),
(506, 35, 'New Announcement', 'General: General', 'general', 'read', '2025-11-17 14:22:09'),
(507, 36, 'New Announcement', 'General: General', 'general', 'unread', '2025-11-17 14:22:10'),
(508, 37, 'New Announcement', 'General: General', 'general', 'read', '2025-11-17 14:22:11'),
(509, 38, 'New Announcement', 'General: General', 'general', 'read', '2025-11-17 14:22:11'),
(510, 40, 'New Announcement', 'General: General', 'general', 'unread', '2025-11-17 14:22:11'),
(511, 44, 'New Announcement', 'General: General', 'general', 'read', '2025-11-17 14:22:11'),
(512, 59, 'New Announcement', 'General: General', 'general', 'read', '2025-11-17 14:22:12'),
(513, 62, 'New Announcement', 'General: General', 'general', 'read', '2025-11-17 14:22:12'),
(514, 6, 'New Announcement', 'Meeting: Meeting', 'announcement', 'unread', '2025-11-17 14:22:43'),
(515, 27, 'New Announcement', 'Meeting: Meeting', 'announcement', 'unread', '2025-11-17 14:22:44'),
(516, 24, 'New Announcement', 'Meeting: Meeting', 'announcement', 'unread', '2025-11-17 14:22:45'),
(517, 29, 'New Announcement', 'Meeting: Meeting', 'announcement', 'read', '2025-11-17 14:22:45'),
(518, 36, 'New Announcement', 'Meeting: Meeting', 'announcement', 'unread', '2025-11-17 14:22:46'),
(519, 37, 'New Announcement', 'Meeting: Meeting', 'announcement', 'read', '2025-11-17 14:22:47'),
(520, 40, 'New Announcement', 'Meeting: Meeting', 'announcement', 'unread', '2025-11-17 14:22:47'),
(521, 35, 'New Message', 'New message from Namz Baer: yes', 'general', 'read', '2025-11-17 14:37:12'),
(522, 35, 'New Message', 'New message from Namz Baer: yes', 'general', 'read', '2025-11-17 14:37:15'),
(523, 35, 'New Message', 'New message from Namz Baer: morning', 'general', 'read', '2025-11-18 02:32:09'),
(524, 35, 'New Message', 'New message from Namz Baer: morning', 'general', 'read', '2025-11-18 02:32:11'),
(525, 38, 'Maintenance Status Updated', 'Maintenance request status updated to: In Progress', 'maintenance', 'read', '2025-11-18 03:14:56'),
(526, 38, 'Maintenance Status Updated', 'Maintenance request status updated to: In Progress', 'maintenance', 'read', '2025-11-18 03:14:59'),
(527, 35, 'Maintenance Status Updated', 'Maintenance request status updated to: Declined', 'maintenance', 'read', '2025-11-18 03:29:44'),
(528, 35, 'Maintenance Status Updated', 'Maintenance request status updated to: Declined', 'maintenance', 'read', '2025-11-18 03:29:46'),
(529, 59, 'Maintenance Status Updated', 'Maintenance request status updated to: In Progress', 'maintenance', 'read', '2025-11-18 03:39:03'),
(530, 35, 'Maintenance Status Updated', 'Maintenance request status updated to: Declined', 'maintenance', 'read', '2025-11-18 03:39:16'),
(531, 29, 'New Maintenance Request', 'A boarder has submitted a maintenance request for Room 2: Damage', 'maintenance', 'read', '2025-11-18 03:54:11'),
(532, 29, 'New Maintenance Request', 'A boarder has submitted a maintenance request for Room 2: Damage', 'maintenance', 'read', '2025-11-18 03:54:13'),
(533, 29, 'New Maintenance Request', 'A boarder has submitted a maintenance request for Room 2: Damage', 'maintenance', 'read', '2025-11-18 03:54:37'),
(534, 29, 'New Maintenance Request', 'A boarder has submitted a maintenance request for Room 2: Damage', 'maintenance', 'read', '2025-11-18 03:54:40'),
(535, 29, 'New Maintenance Request', 'A boarder has submitted a maintenance request for Private Room 01: Damage', 'maintenance', 'read', '2025-11-18 03:59:12'),
(536, 29, 'New Maintenance Request', 'A boarder has submitted a maintenance request for Private Room 01: Damage', 'maintenance', 'read', '2025-11-18 03:59:15'),
(537, 29, 'New Maintenance Request', 'Ruel Dalugdog Cuas has submitted a maintenance request for Private Room 01: Damage', 'maintenance', 'read', '2025-11-18 04:14:02'),
(538, 35, 'Maintenance Status Updated', 'Maintenance request status updated to: In Progress', 'maintenance', 'read', '2025-11-18 04:15:59'),
(539, 35, 'Maintenance Completed', 'Your maintenance request has been completed', 'maintenance', 'read', '2025-11-18 04:55:37'),
(540, 35, 'Maintenance Status Updated', 'Maintenance request status updated to: In Progress', 'maintenance', 'read', '2025-11-18 05:55:59'),
(541, 35, 'Maintenance Completed', 'Your maintenance request has been completed', 'maintenance', 'read', '2025-11-18 05:56:28'),
(542, 35, 'Maintenance Status Updated', 'Maintenance request status updated to: In Progress', 'maintenance', 'read', '2025-11-18 06:03:45'),
(543, 59, 'Maintenance Status Updated', 'Maintenance request status updated to: Declined', 'maintenance', 'read', '2025-11-18 06:03:55'),
(544, 35, 'Maintenance Completed', 'Your maintenance request has been completed', 'maintenance', 'read', '2025-11-18 06:05:13'),
(545, 59, 'Maintenance Status Updated', 'Maintenance request status updated to: Declined', 'maintenance', 'read', '2025-11-18 06:09:44'),
(546, 59, 'Maintenance Status Updated', 'Maintenance request status updated to: In Progress', 'maintenance', 'read', '2025-11-18 06:09:58'),
(547, 59, 'Maintenance Completed', 'Your maintenance request has been completed', 'maintenance', 'read', '2025-11-18 06:10:13'),
(548, 59, 'Maintenance Status Updated', 'Maintenance request status updated to: In Progress', 'maintenance', 'read', '2025-11-18 06:14:20'),
(549, 59, 'Maintenance Completed', 'Your maintenance request has been completed', 'maintenance', 'read', '2025-11-18 06:14:32'),
(550, 59, 'Maintenance Completed', 'Your maintenance request has been completed', 'maintenance', 'read', '2025-11-18 06:18:32'),
(551, 29, 'New Maintenance Request', 'Ruel Dalugdog Cuas has submitted a maintenance request for Private Room 01: damage', 'maintenance', 'read', '2025-11-18 06:21:11'),
(552, 35, 'Maintenance Status Updated', 'Maintenance request status updated to: In Progress', 'maintenance', 'read', '2025-11-18 06:22:22'),
(553, 35, 'Maintenance Status Updated', 'Maintenance request status updated to: Declined', 'maintenance', 'read', '2025-11-18 06:22:40'),
(554, 35, 'Maintenance Completed', 'Your maintenance request has been completed', 'maintenance', 'read', '2025-11-18 06:27:19'),
(555, 29, 'New Maintenance Request', 'Ruel Dalugdog Cuas has submitted a maintenance request for Private Room 01: DAMAGEE', 'maintenance', 'read', '2025-11-18 06:30:16'),
(556, 35, 'Maintenance Status Updated', 'Maintenance request status updated to: In Progress', 'maintenance', 'read', '2025-11-18 06:30:47'),
(557, 35, 'Maintenance Status Updated', 'Maintenance request status updated to: Declined', 'maintenance', 'read', '2025-11-18 06:31:00'),
(558, 35, 'Maintenance Completed', 'Your maintenance request has been completed', 'maintenance', 'read', '2025-11-18 06:31:15'),
(559, 29, 'New Maintenance Request', 'Ruel Dalugdog Cuas has submitted a maintenance request for Private Room 01: Damagee', 'maintenance', 'read', '2025-11-18 06:40:13'),
(560, 29, 'New Maintenance Request', 'Ruel Dalugdog Cuas has submitted a maintenance request for Private Room 01: Vahajaj', 'maintenance', 'read', '2025-11-18 06:42:47'),
(561, 29, 'New Maintenance Request', 'Ruel Dalugdog Cuas has submitted a maintenance request for Private Room 01: Damana', 'maintenance', 'read', '2025-11-18 06:46:28'),
(562, 29, 'New Maintenance Request', 'Ruel Dalugdog Cuas has submitted a maintenance request for Private Room 01: Ajjaaj', 'maintenance', 'read', '2025-11-18 06:50:14'),
(563, 29, 'New Maintenance Request', 'Ruel Dalugdog Cuas has submitted a maintenance request for Private Room 01: Sjhsuw', 'maintenance', 'read', '2025-11-18 06:54:25'),
(564, 35, 'Maintenance Status Updated', 'Maintenance request status updated to: In Progress', 'maintenance', 'read', '2025-11-18 06:55:29'),
(565, 35, 'Maintenance Status Updated', 'Maintenance request status updated to: Declined', 'maintenance', 'read', '2025-11-18 06:55:42'),
(566, 29, 'New Payment Pending', 'A new payment of ₱4,166.67 is pending for Payment for Private Room 01 at Kikyam BH', 'payment', 'read', '2025-11-18 11:47:29'),
(567, 29, 'New Booking Request', 'You have a new booking request from John Mark Sagetarios for Private Room 01', 'booking', 'read', '2025-11-18 11:47:30'),
(568, 63, 'Registration Approved', 'Your registration has been approved! You can now login to your account', '', 'read', '2025-11-19 02:28:54'),
(569, 59, 'Payment Status Updated', 'Your payment of ₱3,166.67 has been updated to Fully Paid for Room 2', 'payment', 'read', '2025-11-19 11:39:52'),
(570, 59, 'Payment Status Updated', 'Your payment of ₱3,166.67 status has been updated to: Fully Paid', 'payment', 'read', '2025-11-19 11:39:52'),
(571, 29, 'Payment Received', 'Payment of ₱3,166.67 received for Room 2 at BH 1', 'payment', 'read', '2025-11-19 11:39:53'),
(572, 29, 'Payment Received', 'Payment of ₱3,166.67 has been received for Payment for Room 2 at BH 1', 'payment', 'read', '2025-11-19 11:39:53'),
(573, 38, 'Booking Approved', 'Your booking request for Private Room 01 has been checked and approved!', 'booking', 'read', '2025-11-19 11:48:16'),
(574, 29, 'Payment Received', 'Payment of ₱4,166.67 has been received for from boarder for Private Room 01 at Kikyam BH. Status: Fully Paid', 'payment', 'read', '2025-11-19 11:48:16'),
(575, 38, 'Payment Status Updated', 'Your payment of ₱4,166.67 status has been updated to: Fully Paid', 'payment', 'read', '2025-11-19 11:48:17'),
(576, 38, 'Payment Status Updated', 'Your payment of ₱5,000.00 status has been updated to: Partially Paid', 'payment', 'read', '2025-11-19 11:56:06'),
(577, 29, 'Payment Received', 'Payment of ₱5,000.00 has been received for Payment for Private Room 01 at Kikyam BH', 'payment', 'read', '2025-11-19 11:56:06');
INSERT INTO `notifications` (`notif_id`, `user_id`, `notif_title`, `notif_message`, `notif_type`, `notif_status`, `notif_created_at`) VALUES
(578, 29, 'New Message', 'New message from John Mark Sagetarios: hi', 'general', 'read', '2025-11-19 11:59:19'),
(579, 29, 'New Message', 'New message from John Mark Sagetarios: hi', 'general', 'read', '2025-11-19 11:59:21'),
(580, 28, 'New Message', 'New message from John Mark Sagetarios: hi', 'general', 'read', '2025-11-19 11:59:49'),
(581, 35, 'Maintenance Status Updated', 'Maintenance request status updated to: In Progress', 'maintenance', 'read', '2025-11-19 12:03:20'),
(582, 44, 'New Message', 'New message from Namz Baer: hissy', 'general', 'read', '2025-11-19 12:43:03'),
(583, 44, 'New Message', 'New message from Namz Baer: hi', 'general', 'read', '2025-11-19 12:43:18'),
(584, 44, 'New Message', 'New message from Namz Baer: kijj', 'general', 'read', '2025-11-19 12:43:23'),
(585, 29, 'New Message', 'New message from John Mark Sagetarios: hii', 'general', 'read', '2025-11-19 12:58:11'),
(586, 29, 'New Message', 'New message from John Mark Sagetarios: hii', 'general', 'read', '2025-11-19 12:58:13'),
(587, 59, 'New Group Message', 'New message in GGGG from John Mark Sagetarios', 'general', 'read', '2025-11-19 13:06:22'),
(588, 28, 'New Group Message', 'New message in GGGG from John Mark Sagetarios', 'general', 'read', '2025-11-19 13:06:24'),
(589, 44, 'New Group Message', 'New message in GGGG from John Mark Sagetarios', 'general', 'read', '2025-11-19 13:06:24'),
(590, 29, 'New Message', 'New message from John Mark Sagetarios: H**lo', 'general', 'read', '2025-11-19 13:19:11'),
(591, 29, 'New Message', 'New message from John Mark Sagetarios: H**lo', 'general', 'read', '2025-11-19 13:19:13'),
(592, 29, 'New Message', 'New message from John Mark Sagetarios: yawa', 'general', 'read', '2025-11-19 13:19:19'),
(593, 29, 'New Message', 'New message from John Mark Sagetarios: yawa', 'general', 'read', '2025-11-19 13:19:22'),
(594, 29, 'New Message', 'New message from John Mark Sagetarios: b**o', 'general', 'read', '2025-11-19 13:19:27'),
(595, 29, 'New Message', 'New message from John Mark Sagetarios: b**o', 'general', 'read', '2025-11-19 13:19:29'),
(596, 29, 'New Message', 'New message from John Mark Sagetarios: b**o', 'general', 'read', '2025-11-19 13:19:41'),
(597, 29, 'New Message', 'New message from John Mark Sagetarios: b**o', 'general', 'read', '2025-11-19 13:19:43'),
(598, 29, 'New Message', 'New message from John Mark Sagetarios: jasjsjsjsjbssnbxbxxbxbxbfbfbfbfbfbffbffbfbfbfbfncn', 'general', 'read', '2025-11-19 13:31:50'),
(599, 29, 'New Message', 'New message from John Mark Sagetarios: jasjsjsjsjbssnbxbxxbxbxbfbfbfbfbfbffbffbfbfbfbfncn', 'general', 'read', '2025-11-19 13:31:53'),
(600, 28, 'New Message', 'New message from Namz Baer: Hi', 'general', 'read', '2025-11-19 13:57:51'),
(601, 2, 'New Announcement', 'Meeting: meeting -admin', 'announcement', 'unread', '2025-11-19 14:13:45'),
(602, 1, 'New Announcement', 'Meeting: meeting -admin', 'announcement', 'unread', '2025-11-19 14:13:45'),
(603, 4, 'New Announcement', 'Meeting: meeting -admin', 'announcement', 'unread', '2025-11-19 14:13:45'),
(604, 6, 'New Announcement', 'Meeting: meeting -admin', 'announcement', 'unread', '2025-11-19 14:13:45'),
(605, 58, 'New Announcement', 'Meeting: meeting -admin', 'announcement', 'unread', '2025-11-19 14:13:46'),
(606, 27, 'New Announcement', 'Meeting: meeting -admin', 'announcement', 'unread', '2025-11-19 14:13:46'),
(607, 24, 'New Announcement', 'Meeting: meeting -admin', 'announcement', 'unread', '2025-11-19 14:13:46'),
(608, 23, 'New Announcement', 'Meeting: meeting -admin', 'announcement', 'unread', '2025-11-19 14:13:47'),
(609, 28, 'New Announcement', 'Meeting: meeting -admin', 'announcement', 'read', '2025-11-19 14:13:47'),
(610, 29, 'New Announcement', 'Meeting: meeting -admin', 'announcement', 'read', '2025-11-19 14:13:47'),
(611, 35, 'New Announcement', 'Meeting: meeting -admin', 'announcement', 'read', '2025-11-19 14:13:47'),
(612, 36, 'New Announcement', 'Meeting: meeting -admin', 'announcement', 'unread', '2025-11-19 14:13:48'),
(613, 37, 'New Announcement', 'Meeting: meeting -admin', 'announcement', 'read', '2025-11-19 14:13:48'),
(614, 38, 'New Announcement', 'Meeting: meeting -admin', 'announcement', 'unread', '2025-11-19 14:13:48'),
(615, 44, 'New Announcement', 'Meeting: meeting -admin', 'announcement', 'read', '2025-11-19 14:13:48'),
(616, 59, 'New Announcement', 'Meeting: meeting -admin', 'announcement', 'read', '2025-11-19 14:13:48'),
(617, 62, 'New Announcement', 'Meeting: meeting -admin', 'announcement', 'read', '2025-11-19 14:13:49'),
(618, 63, 'New Announcement', 'Meeting: meeting -admin', 'announcement', 'unread', '2025-11-19 14:13:49'),
(619, 38, 'New Message', 'New message from Namz Baer: B**o ka', 'general', 'unread', '2025-11-20 05:16:06'),
(620, 38, 'New Message', 'New message from Namz Baer: Putangina mo', 'general', 'unread', '2025-11-20 05:16:54'),
(621, 38, 'New Message', 'New message from Namz Baer: P*******a mo', 'general', 'unread', '2025-11-20 05:17:17'),
(622, 38, 'New Message', 'New message from Namz Baer: Hi', 'general', 'unread', '2025-11-20 05:17:34'),
(623, 38, 'New Message', 'New message from Namz Baer: Hi', 'general', 'unread', '2025-11-20 05:17:36'),
(624, 2, 'New Announcement', 'Meeting: meeting', 'announcement', 'unread', '2025-11-20 05:19:59'),
(625, 1, 'New Announcement', 'Meeting: meeting', 'announcement', 'unread', '2025-11-20 05:19:59'),
(626, 4, 'New Announcement', 'Meeting: meeting', 'announcement', 'unread', '2025-11-20 05:20:00'),
(627, 6, 'New Announcement', 'Meeting: meeting', 'announcement', 'unread', '2025-11-20 05:20:00'),
(628, 58, 'New Announcement', 'Meeting: meeting', 'announcement', 'unread', '2025-11-20 05:20:01'),
(629, 27, 'New Announcement', 'Meeting: meeting', 'announcement', 'unread', '2025-11-20 05:20:01'),
(630, 24, 'New Announcement', 'Meeting: meeting', 'announcement', 'unread', '2025-11-20 05:20:01'),
(631, 23, 'New Announcement', 'Meeting: meeting', 'announcement', 'unread', '2025-11-20 05:20:02'),
(632, 28, 'New Announcement', 'Meeting: meeting', 'announcement', 'read', '2025-11-20 05:20:02'),
(633, 29, 'New Announcement', 'Meeting: meeting', 'announcement', 'read', '2025-11-20 05:20:02'),
(634, 35, 'New Announcement', 'Meeting: meeting', 'announcement', 'read', '2025-11-20 05:20:03'),
(635, 36, 'New Announcement', 'Meeting: meeting', 'announcement', 'unread', '2025-11-20 05:20:04'),
(636, 37, 'New Announcement', 'Meeting: meeting', 'announcement', 'read', '2025-11-20 05:20:05'),
(637, 38, 'New Announcement', 'Meeting: meeting', 'announcement', 'unread', '2025-11-20 05:20:05'),
(638, 44, 'New Announcement', 'Meeting: meeting', 'announcement', 'read', '2025-11-20 05:20:05'),
(639, 59, 'New Announcement', 'Meeting: meeting', 'announcement', 'read', '2025-11-20 05:20:05'),
(640, 62, 'New Announcement', 'Meeting: meeting', 'announcement', 'read', '2025-11-20 05:20:06'),
(641, 63, 'New Announcement', 'Meeting: meeting', 'announcement', 'unread', '2025-11-20 05:20:06'),
(642, 29, 'New Message', 'New message from John Sagetarios: hi', 'general', 'read', '2025-11-20 05:21:08'),
(643, 29, 'New Message', 'New message from John Sagetarios: hi', 'general', 'read', '2025-11-20 05:21:11'),
(644, 29, 'New Message', 'New message from Liz Uy: Hi', 'general', 'read', '2025-11-20 06:47:44'),
(645, 29, 'New Message', 'New message from Liz Uy: Hi', 'general', 'read', '2025-11-20 06:47:46'),
(646, 28, 'New Message', 'New message from Namz Baer: Hi', 'general', 'read', '2025-11-20 06:48:33'),
(647, 28, 'New Message', 'New message from Namz Baer: Hi', 'general', 'read', '2025-11-20 06:49:14'),
(648, 28, 'New Message', 'New message from Namz Baer: Hi', 'general', 'read', '2025-11-20 06:49:16'),
(649, 28, 'New Message', 'New message from Namz Baer: Hi', 'general', 'read', '2025-11-20 06:53:26'),
(650, 28, 'New Message', 'New message from Namz Baer: Hi', 'general', 'read', '2025-11-20 06:53:29'),
(651, 28, 'New Message', 'New message from Namz Baer: Hikims', 'general', 'read', '2025-11-20 06:53:40'),
(652, 28, 'New Message', 'New message from Namz Baer: Hiiihi po', 'general', 'read', '2025-11-20 06:54:07'),
(653, 28, 'New Message', 'New message from Namz Baer: Hiiihi po', 'general', 'read', '2025-11-20 06:54:10'),
(654, 28, 'New Message', 'New message from Namz Baer: Jhhh', 'general', 'read', '2025-11-20 06:54:49'),
(655, 28, 'New Message', 'New message from Namz Baer: Jhhh', 'general', 'read', '2025-11-20 06:54:52'),
(656, 29, 'New Message', 'New message from Liz Uy: hiii', 'general', 'read', '2025-11-20 06:55:53'),
(657, 29, 'New Message', 'New message from Liz Uy: hiii', 'general', 'read', '2025-11-20 06:55:56'),
(658, 28, 'New Message', 'New message from Namz Baer: His', 'general', 'read', '2025-11-20 06:56:58'),
(659, 28, 'New Message', 'New message from Namz Baer: His', 'general', 'read', '2025-11-20 06:57:01'),
(660, 29, 'New Message', 'New message from Liz Uy: Hi kimmmmm', 'general', 'read', '2025-11-20 07:10:37'),
(661, 29, 'New Message', 'New message from Liz Uy: Hi kimmmmm', 'general', 'read', '2025-11-20 07:10:40'),
(662, 28, 'New Message', 'New message from Namz Baer: uy hallo', 'general', 'read', '2025-11-20 07:11:06'),
(663, 29, 'New Message', 'New message from Liz Uy: Halo', 'general', 'read', '2025-11-20 10:48:31'),
(664, 29, 'New Message', 'New message from Liz Uy: Halo', 'general', 'read', '2025-11-20 10:48:33'),
(665, 28, 'New Message', 'New message from Namz Baer: Hrllo', 'general', 'read', '2025-11-20 10:49:11'),
(666, 28, 'New Message', 'New message from Namz Baer: Hrllo', 'general', 'read', '2025-11-20 10:49:14'),
(667, 29, '🔒 Email Address Changed', 'Your email address has been successfully changed to christehannamae.cuas@bisu.edu.ph. If you didn\'t make this change, please contact support immediately.', 'general', 'read', '2025-11-20 11:51:40'),
(668, 29, 'Email Changed', 'Your email address has been successfully changed', 'general', 'read', '2025-11-20 11:51:42'),
(669, 29, '🔒 Email Address Changed', 'Your email address has been successfully changed to namzbaer@gmail.com. If you didn\'t make this change, please contact support immediately.', 'general', 'read', '2025-11-20 12:02:23'),
(670, 29, 'Email Changed', 'Your email address has been successfully changed', 'general', 'read', '2025-11-20 12:02:25'),
(671, 29, '🔒 Email Address Changed', 'Your email address has been successfully changed to christehannamae.cuas@bisu.edu.ph. If you didn\'t make this change, please contact support immediately.', 'general', 'read', '2025-11-20 12:06:18'),
(672, 29, 'Email Changed', 'Your email address has been successfully changed', 'general', 'read', '2025-11-20 12:06:20'),
(673, 29, '🔒 Email Address Changed', 'Your email address has been successfully changed to namzbaer@gmail.com. If you didn\'t make this change, please contact support immediately.', 'general', 'read', '2025-11-20 12:11:15'),
(674, 29, 'Email Changed', 'Your email address has been successfully changed', 'general', 'read', '2025-11-20 12:11:17'),
(675, 29, '🔒 Email Address Changed', 'Your email address has been successfully changed to christehannamae.cuas@bisu.edu.ph. If you didn\'t make this change, please contact support immediately.', 'general', 'read', '2025-11-20 12:16:31'),
(676, 29, 'Email Changed', 'Your email address has been successfully changed', 'general', 'read', '2025-11-20 12:16:34'),
(677, 29, '🔒 Password Changed', 'Your password has been successfully changed. If you didn\'t make this change, please contact support immediately and reset your password.', 'general', 'read', '2025-11-20 12:20:52'),
(678, 29, 'Password Changed', 'Your password has been successfully changed', 'general', 'read', '2025-11-20 12:20:55'),
(679, 29, '🔒 Password Changed', 'Your password has been successfully changed. If you didn\'t make this change, please contact support immediately and reset your password.', 'general', 'read', '2025-11-20 12:24:37'),
(680, 29, 'Password Changed', 'Your password has been successfully changed', 'general', 'read', '2025-11-20 12:24:40'),
(681, 29, '🔒 Password Changed', 'Your password has been successfully changed. If you didn\'t make this change, please contact support immediately and reset your password.', 'general', 'read', '2025-11-20 12:26:47'),
(682, 29, 'Password Changed', 'Your password has been successfully changed', 'general', 'read', '2025-11-20 12:26:49'),
(683, 29, '🔒 Password Changed', 'Your password has been successfully changed. If you didn\'t make this change, please contact support immediately and reset your password.', 'general', 'read', '2025-11-20 12:27:20'),
(684, 29, 'Password Changed', 'Your password has been successfully changed', 'general', 'read', '2025-11-20 12:27:22'),
(685, 29, '🔒 Email Address Changed', 'Your email address has been successfully changed to namzbaer@gmail.com. If you didn\'t make this change, please contact support immediately.', 'general', 'read', '2025-11-20 12:28:02'),
(686, 29, 'Email Changed', 'Your email address has been successfully changed', 'general', 'read', '2025-11-20 12:28:04'),
(687, 28, '🔒 Password Changed', 'Your password has been successfully changed. If you didn\'t make this change, please contact support immediately and reset your password.', 'general', 'read', '2025-11-20 13:04:46'),
(688, 28, 'Password Changed', 'Your password has been successfully changed', 'general', 'read', '2025-11-20 13:04:49'),
(689, 28, '🔒 Password Changed', 'Your password has been successfully changed. If you didn\'t make this change, please contact support immediately and reset your password.', 'general', 'read', '2025-11-20 13:05:18'),
(690, 28, 'Password Changed', 'Your password has been successfully changed', 'general', 'read', '2025-11-20 13:05:20'),
(691, 28, '🔒 Email Address Changed', 'Your email address has been successfully changed to christehannamae.cuas@bisu.edu.ph. If you didn\'t make this change, please contact support immediately.', 'general', 'read', '2025-11-20 13:06:12'),
(692, 28, 'Email Changed', 'Your email address has been successfully changed', 'general', 'read', '2025-11-20 13:06:14'),
(693, 28, '🔒 Email Address Changed', 'Your email address has been successfully changed to hannacuas536@gmail.com. If you didn\'t make this change, please contact support immediately.', 'general', 'read', '2025-11-20 13:07:15'),
(694, 28, 'Email Changed', 'Your email address has been successfully changed', 'general', 'read', '2025-11-20 13:07:17'),
(695, 28, '🔒 Password Changed', 'Your password has been successfully changed. If you didn\'t make this change, please contact support immediately and reset your password.', 'general', 'read', '2025-11-20 14:54:10'),
(696, 28, 'Password Changed', 'Your password has been successfully changed', 'general', 'read', '2025-11-20 14:54:12'),
(697, 29, '🔒 Email Address Changed', 'Your email address has been successfully changed to christehannamae.cuas@bisu.edu.ph. If you didn\'t make this change, please contact support immediately.', 'general', 'read', '2025-11-20 14:58:19'),
(698, 29, 'Email Changed', 'Your email address has been successfully changed', 'general', 'read', '2025-11-20 14:58:21'),
(699, 29, '🔒 Email Address Changed', 'Your email address has been successfully changed to namzbaer@gmail.com. If you didn\'t make this change, please contact support immediately.', 'general', 'read', '2025-11-20 14:59:12'),
(700, 29, 'Email Changed', 'Your email address has been successfully changed', 'general', 'read', '2025-11-20 14:59:14'),
(701, 28, '🔒 Email Address Changed', 'Your email address has been successfully changed to christehannamae.cuas@bisu.edu.ph. If you didn\'t make this change, please contact support immediately.', 'general', 'read', '2025-11-20 15:03:24'),
(702, 28, 'Email Changed', 'Your email address has been successfully changed', 'general', 'read', '2025-11-20 15:03:27'),
(703, 28, '🔒 Email Address Changed', 'Your email address has been successfully changed to hannacuas536@gmail.com. If you didn\'t make this change, please contact support immediately.', 'general', 'read', '2025-11-20 15:04:19'),
(704, 28, 'Email Changed', 'Your email address has been successfully changed', 'general', 'read', '2025-11-20 15:04:21'),
(705, 28, '🔒 Password Changed', 'Your password has been successfully changed. If you didn\'t make this change, please contact support immediately and reset your password.', 'general', 'read', '2025-11-20 15:04:49'),
(706, 28, 'Password Changed', 'Your password has been successfully changed', 'general', 'read', '2025-11-20 15:04:51'),
(707, 29, '🔒 Password Changed', 'Your password has been successfully changed. If you didn\'t make this change, please contact support immediately and reset your password.', 'general', 'read', '2025-11-20 15:07:15'),
(708, 29, 'Password Changed', 'Your password has been successfully changed', 'general', 'read', '2025-11-20 15:07:17'),
(709, 29, '🔒 Password Changed', 'Your password has been successfully changed. If you didn\'t make this change, please contact support immediately and reset your password.', 'general', 'read', '2025-11-20 15:11:28'),
(710, 29, 'Password Changed', 'Your password has been successfully changed', 'general', 'read', '2025-11-20 15:11:30'),
(711, 29, '🔒 Password Changed', 'Your password has been successfully changed. If you didn\'t make this change, please contact support immediately and reset your password.', 'general', 'read', '2025-11-20 15:13:43'),
(712, 29, 'Password Changed', 'Your password has been successfully changed', 'general', 'read', '2025-11-20 15:13:45'),
(713, 29, '🔒 Password Changed', 'Your password has been successfully changed. If you didn\'t make this change, please contact support immediately and reset your password.', 'general', 'read', '2025-11-20 15:14:09'),
(714, 29, 'Password Changed', 'Your password has been successfully changed', 'general', 'read', '2025-11-20 15:14:11'),
(715, 29, '🔒 Email Address Changed', 'Your email address has been successfully changed to christehannamae.cuas@bisu.edu.ph. If you didn\'t make this change, please contact support immediately.', 'general', 'read', '2025-11-20 15:14:48'),
(716, 29, 'Email Changed', 'Your email address has been successfully changed', 'general', 'read', '2025-11-20 15:14:50'),
(717, 29, '🔒 Email Address Changed', 'Your email address has been successfully changed to namzbaer@gmail.com. If you didn\'t make this change, please contact support immediately.', 'general', 'read', '2025-11-20 15:15:33'),
(718, 29, 'Email Changed', 'Your email address has been successfully changed', 'general', 'read', '2025-11-20 15:15:35'),
(719, 28, '🔒 Password Changed', 'Your password has been successfully changed. If you didn\'t make this change, please contact support immediately and reset your password.', 'general', 'read', '2025-11-20 15:36:52'),
(720, 28, 'Password Changed', 'Your password has been successfully changed', 'general', 'read', '2025-11-20 15:36:54'),
(721, 28, '🔒 Email Address Changed', 'Your email address has been successfully changed to christehannamae.cuas@bisu.edu.ph. If you didn\'t make this change, please contact support immediately.', 'general', 'read', '2025-11-20 15:37:25'),
(722, 28, 'Email Changed', 'Your email address has been successfully changed', 'general', 'read', '2025-11-20 15:37:27'),
(723, 28, '🔒 Password Changed', 'Your password has been successfully changed. If you didn\'t make this change, please contact support immediately and reset your password.', 'general', 'read', '2025-11-20 15:37:46'),
(724, 28, 'Password Changed', 'Your password has been successfully changed', 'general', 'read', '2025-11-20 15:37:48'),
(725, 28, '🔒 Email Address Changed', 'Your email address has been successfully changed to hannacuas536@gmail.com. If you didn\'t make this change, please contact support immediately.', 'general', 'read', '2025-11-20 15:38:20'),
(726, 28, 'Email Changed', 'Your email address has been successfully changed', 'general', 'read', '2025-11-20 15:38:22'),
(727, 29, 'New Message', 'New message from Lizz Uy: Najsjsjs', 'general', 'read', '2025-11-21 01:00:44'),
(728, 29, 'New Message', 'New message from Lizz Uy: Najsjsjs', 'general', 'read', '2025-11-21 01:00:47'),
(729, 28, 'New Message', 'New message from Namz Baer: Uyss', 'general', 'read', '2025-11-21 01:01:03'),
(730, 28, 'New Message', 'New message from Namz Baer: Uyss', 'general', 'read', '2025-11-21 01:01:06'),
(731, 29, 'New Message', 'New message from Lizz Uy: Ho kim', 'general', 'read', '2025-11-21 05:38:34'),
(732, 29, 'New Message', 'New message from Lizz Uy: Ho kim', 'general', 'read', '2025-11-21 05:38:36'),
(733, 28, 'New Message', 'New message from Namz Baer: Hytd', 'general', 'read', '2025-11-21 05:39:04'),
(734, 28, 'New Message', 'New message from Namz Baer: Hytd', 'general', 'read', '2025-11-21 05:39:06'),
(735, 29, 'New Message', 'New message from Lizz Uy: Bye ka oy', 'general', 'read', '2025-11-21 05:40:04'),
(736, 29, 'New Message', 'New message from Lizz Uy: Bye ka oy', 'general', 'read', '2025-11-21 05:40:07'),
(737, 28, 'New Message', 'New message from Namz Baer: Ouh', 'general', 'read', '2025-11-21 05:40:41'),
(738, 28, 'New Message', 'New message from Namz Baer: Ouh', 'general', 'read', '2025-11-21 05:40:43'),
(739, 29, 'New Message', 'New message from Lizz Uy: Bsjsjssjsjsis', 'general', 'read', '2025-11-21 06:03:49'),
(740, 29, 'New Message', 'New message from Lizz Uy: Bsjsjssjsjsis', 'general', 'read', '2025-11-21 06:03:51'),
(741, 29, 'New Message', 'New message from Lizz Uy: Oyoyoy', 'general', 'read', '2025-11-21 06:05:01'),
(742, 29, 'New Message', 'New message from Lizz Uy: Oyoyoy', 'general', 'read', '2025-11-21 06:05:03'),
(743, 29, 'New Message', 'New message from Lizz Uy: Hooo', 'general', 'read', '2025-11-21 06:09:18'),
(744, 29, 'New Message', 'New message from Lizz Uy: Hooo', 'general', 'read', '2025-11-21 06:09:20'),
(745, 29, 'New Message', 'New message from Lizz Uy: Yes', 'general', 'read', '2025-11-21 06:10:43'),
(746, 29, 'New Message', 'New message from Lizz Uy: Yes', 'general', 'read', '2025-11-21 06:10:45'),
(747, 29, 'New Message', 'New message from Lizz Uy: Kims', 'general', 'read', '2025-11-21 06:11:07'),
(748, 29, 'New Message', 'New message from Lizz Uy: Kims', 'general', 'read', '2025-11-21 06:11:09'),
(749, 28, 'New Message', 'New message from Namz Baer: Kim', 'general', 'read', '2025-11-23 06:02:50'),
(750, 28, 'New Message', 'New message from Namz Baer: Kim', 'general', 'read', '2025-11-23 06:02:52'),
(751, 28, 'Payment Reminder', 'Reminder: Your payment of ₱5,000.00 for 2nd month is due in 4 days (Due: Nov 28, 2025).', 'payment', 'read', '2025-11-24 02:43:19'),
(752, 28, 'Payment Reminder', 'Reminder: Your payment of ₱5,000.00 for 2nd month is due in 4 days (Due: Nov 28, 2025).', 'payment', 'read', '2025-11-24 02:45:14'),
(753, 28, 'Payment Reminder', 'Reminder: Your payment of ₱5,000.00 for 2nd month for Kikyam BH - Room PR0-2 (Private Room 01) is due in 4 days (Due: Nov 28, 2025).', 'payment', 'read', '2025-11-24 02:48:17'),
(754, 28, 'Payment Reminder', 'Reminder: Your payment of ₱5,000.00 for 2nd month for Kikyam BH - Room PR0-2 (Private Room 01) is due today (Nov 24, 2025). Please make your payment to avoid late fees.', 'payment', 'read', '2025-11-24 02:49:34'),
(755, 28, 'Payment Reminder', 'Reminder: Your payment of ₱5,000.00 for 2nd month for Kikyam BH - Room PR0-2 (Private Room 01) is due in 4 days (Due: Nov 28, 2025).', 'payment', 'read', '2025-11-24 02:51:02'),
(756, 2, 'New Announcement', 'Meeeting: meeting', 'announcement', 'unread', '2025-11-24 03:07:09'),
(757, 1, 'New Announcement', 'Meeeting: meeting', 'announcement', 'unread', '2025-11-24 03:07:09'),
(758, 4, 'New Announcement', 'Meeeting: meeting', 'announcement', 'unread', '2025-11-24 03:07:10'),
(759, 6, 'New Announcement', 'Meeeting: meeting', 'announcement', 'unread', '2025-11-24 03:07:10'),
(760, 58, 'New Announcement', 'Meeeting: meeting', 'announcement', 'unread', '2025-11-24 03:07:11'),
(761, 27, 'New Announcement', 'Meeeting: meeting', 'announcement', 'unread', '2025-11-24 03:07:11'),
(762, 24, 'New Announcement', 'Meeeting: meeting', 'announcement', 'unread', '2025-11-24 03:07:11'),
(763, 23, 'New Announcement', 'Meeeting: meeting', 'announcement', 'unread', '2025-11-24 03:07:12'),
(764, 28, 'New Announcement', 'Meeeting: meeting', 'announcement', 'read', '2025-11-24 03:07:12'),
(765, 29, 'New Announcement', 'Meeeting: meeting', 'announcement', 'read', '2025-11-24 03:07:14'),
(766, 35, 'New Announcement', 'Meeeting: meeting', 'announcement', 'read', '2025-11-24 03:07:15'),
(767, 36, 'New Announcement', 'Meeeting: meeting', 'announcement', 'unread', '2025-11-24 03:07:16'),
(768, 37, 'New Announcement', 'Meeeting: meeting', 'announcement', 'read', '2025-11-24 03:07:17'),
(769, 38, 'New Announcement', 'Meeeting: meeting', 'announcement', 'unread', '2025-11-24 03:07:17'),
(770, 44, 'New Announcement', 'Meeeting: meeting', 'announcement', 'read', '2025-11-24 03:07:19'),
(771, 59, 'New Announcement', 'Meeeting: meeting', 'announcement', 'unread', '2025-11-24 03:07:19'),
(772, 29, 'New Message', 'New message from Lizz Uy: Hahaha mag merge ko', 'general', 'read', '2025-11-24 05:18:40'),
(773, 29, 'New Message', 'New message from Lizz Uy: Hahaha mag merge ko', 'general', 'read', '2025-11-24 05:18:43'),
(774, 28, 'Payment Reminder - Overdue', 'Hello Lizz Uy,\n\nThis is a reminder that your payment of ₱5,000.00 for PR0-2 is overdue.\n\nDue Date: 2025-11-14\nPlease make payment as soon as possible.\n\nThank you.', 'payment', 'read', '2025-11-25 08:55:14'),
(775, 28, 'Payment Reminder - Overdue', 'Hello Lizz Uy,\n\nThis is a reminder that your payment of ₱5,000.00 for PR0-2 is overdue.\n\nDue Date: 2025-11-14\nPlease make payment as soon as possible.\n\nThank you.', 'payment', 'read', '2025-11-25 08:55:15'),
(776, 28, 'Payment Overdue', 'Your payment of ₱5,000.00 is overdue. Please settle it as soon as possible', 'payment', 'read', '2025-11-25 08:55:20'),
(777, 28, 'Payment Overdue', 'Your payment of ₱5,000.00 is overdue. Please settle it as soon as possible', 'payment', 'read', '2025-11-25 08:55:21'),
(778, 28, 'Payment Reminder - Overdue', 'Hello Lizz Uy,\n\nThis is a reminder that your payment of ₱5,000.00 for PR0-2 is overdue.\n\nDue Date: 2025-11-14\nPlease make payment as soon as possible.\n\nThank you.', 'payment', 'read', '2025-11-25 09:16:06'),
(779, 28, 'Payment Overdue', 'Your payment of ₱5,000.00 is overdue. Please settle it as soon as possible', 'payment', 'read', '2025-11-25 09:16:08'),
(780, 28, 'Payment Reminder - Overdue', 'Hello Lizz Uy,\n\nThis is a reminder that your payment of ₱5,000.00 for PR0-2 is overdue.\n\nDue Date: 2025-11-14\nPlease make payment as soon as possible.\n\nThank you.', 'payment', 'read', '2025-11-25 09:16:09'),
(781, 28, 'Payment Overdue', 'Your payment of ₱5,000.00 is overdue. Please settle it as soon as possible', 'payment', 'read', '2025-11-25 09:16:11'),
(782, 28, 'Payment Reminder - Overdue', 'Hello Lizz Uy,\n\nThis is a reminder that your payment of ₱5,000.00 for PR0-2 is overdue.\n\nDue Date: 2025-11-14\nPlease make payment as soon as possible.\n\nThank you.', 'payment', 'read', '2025-11-25 09:18:03'),
(783, 28, 'Payment Overdue', 'Your payment of ₱5,000.00 is overdue. Please settle it as soon as possible', 'payment', 'read', '2025-11-25 09:18:05'),
(784, 28, 'Payment Reminder - Overdue', 'Hello Lizz Uy,\n\nThis is a reminder that your payment of ₱5,000.00 for PR0-2 is overdue.\n\nDue Date: 2025-11-14\nPlease make payment as soon as possible.\n\nThank you.', 'payment', 'read', '2025-11-25 09:21:39'),
(785, 28, 'Payment Overdue', 'Your payment of ₱5,000.00 is overdue. Please settle it as soon as possible', 'payment', 'read', '2025-11-25 09:21:41'),
(786, 28, 'Payment Reminder - Overdue', 'Hello Lizz Uy,\n\nThis is a reminder that your payment of ₱5,000.00 for PR0-2 is overdue.\n\nDue Date: 2025-11-14\nPlease make payment as soon as possible.\n\nThank you.', 'payment', 'read', '2025-11-25 09:32:36'),
(787, 28, 'Payment Overdue', 'Your payment of ₱5,000.00 is overdue. Please settle it as soon as possible', 'payment', 'read', '2025-11-25 09:32:38'),
(788, 28, 'Payment Overdue', 'Your payment of ₱5,000.00 for PR0-2 is overdue. Please make payment as soon as possible.', 'payment', 'read', '2025-11-25 09:36:28'),
(789, 28, 'Payment Overdue', 'Your payment of ₱5,000.00 is overdue. Please settle it as soon as possible', 'payment', 'read', '2025-11-25 09:36:30'),
(790, 28, 'Payment Reminder - Overdue', 'Hello Lizz Uy,\n\nThis is a reminder that your payment of ₱5,000.00 for PR0-2 is overdue.\n\nDue Date: 2025-11-24\nPlease make payment as soon as possible.\n\nThank you.', 'payment', 'read', '2025-11-25 10:08:12'),
(791, 28, 'Payment Overdue', 'Your payment of ₱5,000.00 is overdue. Please settle it as soon as possible', 'payment', 'read', '2025-11-25 10:08:14'),
(792, 28, 'Payment Reminder - Overdue', 'Hello Lizz Uy,\n\nThis is a reminder that your payment of ₱5,000.00 for PR0-2 is overdue.\n\nDue Date: 2026-01-13\nPlease make payment as soon as possible.\n\nThank you.', 'payment', 'read', '2025-11-25 10:08:40'),
(793, 28, 'Payment Overdue', 'Your payment of ₱5,000.00 is overdue. Please settle it as soon as possible', 'payment', 'read', '2025-11-25 10:08:42'),
(794, 28, 'Payment Reminder', 'Hello Lizz Uy,\n\nThis is a reminder that your payment of ₱5,000.00 for PR0-2 is due.\n\nDue Date: 2026-01-13\nPlease make payment on or before the due date.\n\nThank you.', 'payment', 'read', '2025-11-25 10:19:21'),
(795, 28, 'Payment Overdue', 'Your payment of ₱5,000.00 is overdue. Please settle it as soon as possible', 'payment', 'read', '2025-11-25 10:19:23'),
(796, 28, 'Payment Reminder - Overdue', 'Hello Lizz Uy,\n\nThis is a reminder that your payment of ₱5,000.00 for PR0-2 is overdue.\n\nDue Date: 2025-11-14 (11 days overdue)\nPlease make payment as soon as possible.\n\nThank you.', 'payment', 'read', '2025-11-25 10:19:43'),
(797, 28, 'Payment Overdue', 'Your payment of ₱5,000.00 is overdue. Please settle it as soon as possible', 'payment', 'read', '2025-11-25 10:19:45'),
(798, 28, 'Payment Reminder - Overdue', 'Hello Lizz Uy,\n\nThis is a reminder that your payment of ₱5,000.00 for PR0-2 is overdue.\n\nDue Date: 2025-11-24 (1 day overdue)\nPlease make payment as soon as possible.\n\nThank you.', 'payment', 'read', '2025-11-25 10:27:31'),
(799, 28, 'Payment Reminder', 'Hello Lizz Uy,\n\nThis is a reminder that your payment of ₱5,000.00 for PR0-2 is due.\n\nDue Date: 2026-01-13\nPlease make payment on or before the due date.\n\nThank you.', 'payment', 'read', '2025-11-25 10:27:50'),
(800, 28, 'Payment Reminder', 'Hello Lizz Uy,\n\nThis is a reminder that your payment of ₱5,000.00 for PR0-2 is due.\n\nDue Date: 2025-12-11\nPlease make payment on or before the due date.\n\nThank you.', 'payment', 'read', '2025-11-25 11:33:29'),
(801, 44, 'Payment Reminder', 'Hello Ruel Cuas Jr.,\n\nThis is a reminder that your payment of ₱1,000.00 for GA-1 is due.\n\nDue Date: 2025-12-11\nPlease make payment on or before the due date.\n\nThank you.', 'payment', 'read', '2025-11-25 11:33:31'),
(802, 29, 'New Message', 'New message from Ruel Cuas: Sddd', 'general', 'read', '2025-11-25 11:36:55'),
(803, 29, 'New Message', 'New message from Ruel Cuas: Sddd', 'general', 'read', '2025-11-25 11:36:57'),
(804, 28, 'Payment Overdue', 'Your payment of ₱5,000.00 for PR0-2 is overdue. Please make payment as soon as possible.', 'payment', 'read', '2025-11-28 08:27:16'),
(805, 28, 'Payment Overdue', 'Your payment of ₱5,000.00 is overdue. Please settle it as soon as possible', 'payment', 'read', '2025-11-28 08:27:18'),
(806, 28, 'Payment Reminder - Overdue', 'Hello Lizz Uy,\n\nThis is a reminder that your payment of ₱5,000.00 for PR0-2 is overdue.\n\nDue Date: 2025-11-24 (4 days overdue)\nPlease make payment as soon as possible.\n\nThank you.', 'payment', 'read', '2025-11-28 08:27:49'),
(807, 29, 'New Payment Pending', 'A new payment of ₱166.67 is pending for Payment for Private Room 01 at Kikyam BH', 'payment', 'read', '2025-11-28 08:35:33'),
(808, 29, 'New Booking Request', 'You have a new booking request from for Private Room 01', 'booking', 'read', '2025-11-28 08:35:33'),
(809, 28, 'Payment Reminder - Overdue', 'Hello Lizz Uy,\n\nThis is a reminder that your payment of ₱5,000.00 for PR0-2 is overdue.\n\nDue Date: 2025-11-24 (4 days overdue)\nPlease make payment as soon as possible.\n\nThank you.', 'payment', 'read', '2025-11-28 09:11:00'),
(810, 35, 'Booking Approved', 'Your booking request for Private Room 01 has been checked and approved!', 'booking', 'read', '2025-11-28 09:15:17'),
(811, 29, 'Payment Received', 'Payment of ₱166.67 has been received for from boarder for Private Room 01 at Kikyam BH. Status: Fully Paid', 'payment', 'read', '2025-11-28 09:15:17'),
(812, 35, 'Payment Status Updated', 'Your payment of ₱166.67 status has been updated to: Fully Paid', 'payment', 'read', '2025-11-28 09:15:18'),
(813, 28, 'Payment Status Updated', 'Your payment of ₱5,000.00 status has been updated to: Partially Paid', 'payment', 'read', '2025-11-28 12:09:35'),
(814, 29, 'Payment Received', 'Payment of ₱5,000.00 has been received for Payment for Private Room 01 at Kikyam BH', 'payment', 'read', '2025-11-28 12:09:37'),
(815, 44, 'Payment Reminder', 'Hello Ruel Cuas Jr.,\n\nThis is a reminder that your payment of ₱1,000.00 for GA-1 is due.\n\nDue Date: 2025-12-11\nPlease make payment on or before the due date.\n\nThank you.', 'payment', 'unread', '2025-12-01 05:46:06'),
(816, 29, 'New Message', 'New message from Lizz Uy: Yesss', 'general', 'read', '2025-12-01 05:49:59'),
(817, 29, 'New Message', 'New message from Lizz Uy: Yesss', 'general', 'read', '2025-12-01 05:50:03'),
(818, 29, 'New Message', 'New message from Lizz Uy: Hi', 'general', 'read', '2025-12-01 05:51:25'),
(819, 29, 'New Message', 'New message from Lizz Uy: Hi', 'general', 'read', '2025-12-01 05:51:28'),
(820, 29, 'New Message', 'New message from Lizz Uy: Hi', 'general', 'read', '2025-12-01 05:52:42'),
(821, 29, 'New Message', 'New message from Lizz Uy: hiiii', 'general', 'read', '2025-12-01 05:53:55'),
(822, 29, 'New Message', 'New message from Lizz Uy: hiiii', 'general', 'read', '2025-12-01 05:53:56'),
(823, 29, 'New Message', 'New message from Lizz Uy: Huuu', 'general', 'read', '2025-12-01 05:54:34'),
(824, 29, 'New Message', 'New message from Lizz Uy: Huuu', 'general', 'read', '2025-12-01 05:54:38'),
(825, 29, 'New Message', 'New message from Lizz Uy: Hi po', 'general', 'read', '2025-12-01 05:55:53'),
(826, 29, 'New Message', 'New message from Lizz Uy: Hi po', 'general', 'read', '2025-12-01 05:55:55'),
(827, 28, 'New Message', 'New message from Namz Baer: Hii', 'general', 'read', '2025-12-01 05:57:50'),
(828, 28, 'New Message', 'New message from Namz Baer: Hii', 'general', 'read', '2025-12-01 05:57:53'),
(829, 28, 'New Message', 'New message from Namz Baer: Hi', 'general', 'read', '2025-12-01 06:00:35'),
(830, 28, 'New Message', 'New message from Namz Baer: Hin', 'general', 'read', '2025-12-01 06:02:04'),
(831, 28, 'New Message', 'New message from Namz Baer: Hin', 'general', 'read', '2025-12-01 06:02:06'),
(832, 38, 'New Group Message', 'New message in Jjj from Namz Baer', 'general', 'unread', '2025-12-01 06:08:08'),
(833, 59, 'New Group Message', 'New message in Jjj from Namz Baer', 'general', 'unread', '2025-12-01 06:08:10'),
(834, 28, 'New Group Message', 'New message in Jjj from Namz Baer', 'general', 'read', '2025-12-01 06:08:14'),
(835, 28, 'New Group Message', 'New message in Jjj from Namz Baer', 'general', 'read', '2025-12-01 06:08:20'),
(836, 38, 'New Group Message', 'New message in Jjj from Namz Baer', 'general', 'unread', '2025-12-01 06:08:23'),
(837, 44, 'New Group Message', 'New message in Jjj from Namz Baer', 'general', 'unread', '2025-12-01 06:08:24'),
(838, 59, 'New Group Message', 'New message in Jjj from Namz Baer', 'general', 'unread', '2025-12-01 06:08:28'),
(839, 28, 'New Group Message', 'New message in Jjj from Namz Baer', 'general', 'read', '2025-12-01 06:08:33'),
(840, 28, 'New Group Message', 'New message in Jjj from Namz Baer', 'general', 'read', '2025-12-01 06:08:41'),
(841, 44, 'New Group Message', 'New message in Jjj from Namz Baer', 'general', 'unread', '2025-12-01 06:08:47'),
(842, 38, 'New Group Message', 'New message in Jjj from Namz Baer', 'general', 'unread', '2025-12-01 06:09:35'),
(843, 59, 'New Group Message', 'New message in Jjj from Namz Baer', 'general', 'unread', '2025-12-01 06:09:37'),
(844, 28, 'New Group Message', 'New message in Jjj from Namz Baer', 'general', 'read', '2025-12-01 06:09:44'),
(845, 38, 'New Group Message', 'New message in Jjj from Namz Baer', 'general', 'unread', '2025-12-01 06:09:50'),
(846, 28, 'New Group Message', 'New message in Jjj from Namz Baer', 'general', 'read', '2025-12-01 06:09:54'),
(847, 59, 'New Group Message', 'New message in Jjj from Namz Baer', 'general', 'unread', '2025-12-01 06:09:58'),
(848, 44, 'New Group Message', 'New message in Jjj from Namz Baer', 'general', 'unread', '2025-12-01 06:10:00'),
(849, 28, 'New Group Message', 'New message in Jjj from Namz Baer', 'general', 'read', '2025-12-01 06:10:04'),
(850, 28, 'New Group Message', 'New message in Jjj from Namz Baer', 'general', 'read', '2025-12-01 06:10:10'),
(851, 44, 'New Group Message', 'New message in Jjj from Namz Baer', 'general', 'unread', '2025-12-01 06:10:13'),
(852, 28, 'New Message', 'New message from Namz Baer: Hiiiiii', 'general', 'read', '2025-12-01 06:12:34'),
(853, 28, 'New Message', 'New message from Namz Baer: Hi pooooo', 'general', 'read', '2025-12-01 06:14:19'),
(854, 38, 'New Group Message', 'New message in Jjj from Namz Baer', 'general', 'unread', '2025-12-01 06:14:45'),
(855, 59, 'New Group Message', 'New message in Jjj from Namz Baer', 'general', 'unread', '2025-12-01 06:14:48'),
(856, 28, 'New Group Message', 'New message in Jjj from Namz Baer', 'general', 'read', '2025-12-01 06:14:51'),
(857, 28, 'New Group Message', 'New message in Jjj from Namz Baer', 'general', 'read', '2025-12-01 06:14:59'),
(858, 38, 'New Group Message', 'New message in Jjj from Namz Baer', 'general', 'unread', '2025-12-01 06:15:01'),
(859, 44, 'New Group Message', 'New message in Jjj from Namz Baer', 'general', 'unread', '2025-12-01 06:15:06'),
(860, 59, 'New Group Message', 'New message in Jjj from Namz Baer', 'general', 'unread', '2025-12-01 06:15:07'),
(861, 28, 'New Group Message', 'New message in Jjj from Namz Baer', 'general', 'read', '2025-12-01 06:15:14'),
(862, 28, 'New Group Message', 'New message in Jjj from Namz Baer', 'general', 'read', '2025-12-01 06:15:21'),
(863, 44, 'New Group Message', 'New message in Jjj from Namz Baer', 'general', 'unread', '2025-12-01 06:15:28'),
(864, 38, 'New Group Message', 'New message in Jjj from Namz Baer', 'general', 'unread', '2025-12-01 06:18:11'),
(865, 59, 'New Group Message', 'New message in Jjj from Namz Baer', 'general', 'unread', '2025-12-01 06:18:13'),
(866, 28, 'New Group Message', 'New message in Jjj from Namz Baer', 'general', 'read', '2025-12-01 06:18:16'),
(867, 28, 'New Group Message', 'New message in Jjj from Namz Baer', 'general', 'read', '2025-12-01 06:18:21'),
(868, 44, 'New Group Message', 'New message in Jjj from Namz Baer', 'general', 'unread', '2025-12-01 06:18:26'),
(869, 38, 'New Group Message', 'New message in Jjj from Namz Baer', 'general', 'unread', '2025-12-01 06:18:26'),
(870, 59, 'New Group Message', 'New message in Jjj from Namz Baer', 'general', 'unread', '2025-12-01 06:18:32'),
(871, 28, 'New Group Message', 'New message in Jjj from Namz Baer', 'general', 'read', '2025-12-01 06:18:37'),
(872, 28, 'New Group Message', 'New message in Jjj from Namz Baer', 'general', 'read', '2025-12-01 06:18:41'),
(873, 44, 'New Group Message', 'New message in Jjj from Namz Baer', 'general', 'unread', '2025-12-01 06:18:45'),
(874, 29, 'New Message', 'New message from Lizz Uy: Hii', 'general', 'read', '2025-12-06 03:29:47'),
(875, 29, 'New Maintenance Request', 'Lizz Dela Uy has submitted a maintenance request for Private Room 01: Damage of CR', 'maintenance', 'read', '2025-12-06 03:31:03'),
(876, 38, 'New Group Message', 'New message in Jjj from Namz Baer', 'general', 'unread', '2025-12-06 04:35:38'),
(877, 59, 'New Group Message', 'New message in Jjj from Namz Baer', 'general', 'unread', '2025-12-06 04:35:40'),
(878, 28, 'New Group Message', 'New message in Jjj from Namz Baer', 'general', 'read', '2025-12-06 04:35:42'),
(879, 28, 'New Group Message', 'New message in Jjj from Namz Baer', 'general', 'read', '2025-12-06 04:35:44'),
(880, 44, 'New Group Message', 'New message in Jjj from Namz Baer', 'general', 'unread', '2025-12-06 04:35:45'),
(881, 29, 'New Payment Pending', 'A new payment of ₱500.00 is pending for Payment for Group A at BH 1', 'payment', 'read', '2025-12-06 06:44:04'),
(882, 29, 'New Booking Request', 'You have a new booking request from for Group A', 'booking', 'read', '2025-12-06 06:44:05'),
(883, 35, 'Booking Approved', 'Your booking request for Group A has been checked and approved!', 'booking', 'unread', '2025-12-06 07:52:33'),
(884, 29, 'Payment Received', 'Payment of ₱500.00 has been received for from boarder for Group A at BH 1. Status: Fully Paid', 'payment', 'read', '2025-12-06 07:52:35'),
(885, 35, 'Payment Status Updated', 'Your payment of ₱500.00 status has been updated to: Fully Paid', 'payment', 'unread', '2025-12-06 07:52:36'),
(886, 29, 'New Payment Pending', 'A new payment of ₱1,000.00 is pending for Payment for Group A at BH 1', 'payment', 'read', '2025-12-06 07:54:22'),
(887, 29, 'New Booking Request', 'You have a new booking request from for Group A', 'booking', 'read', '2025-12-06 07:54:24'),
(888, 62, 'Booking Approved', 'Your booking request for Group A has been checked and approved!', 'booking', 'read', '2025-12-06 07:55:50'),
(889, 29, 'Payment Received', 'Payment of ₱1,000.00 has been received for from boarder for Group A at BH 1. Status: Partially Paid', 'payment', 'read', '2025-12-06 07:55:51'),
(890, 62, 'Payment Status Updated', 'Your payment of ₱1,000.00 status has been updated to: Partially Paid', 'payment', 'read', '2025-12-06 07:55:52'),
(891, 29, 'New Payment Pending', 'A new payment of ₱533.33 is pending for Payment for Group A at BH 1', 'payment', 'read', '2025-12-06 08:33:25'),
(892, 29, 'New Booking Request', 'You have a new booking request from for Group A', 'booking', 'read', '2025-12-06 08:33:26'),
(893, 35, 'Booking Approved', 'Your booking request for Group A has been checked and approved!', 'booking', 'unread', '2025-12-06 08:55:16'),
(894, 29, 'Payment Received', 'Payment of ₱533.33 has been received for from boarder for Group A at BH 1. Status: Fully Paid', 'payment', 'read', '2025-12-06 08:55:17'),
(895, 35, 'Payment Status Updated', 'Your payment of ₱533.33 status has been updated to: Fully Paid', 'payment', 'unread', '2025-12-06 08:55:18'),
(896, 29, 'New Payment Pending', 'A new payment of ₱266.67 is pending for Payment for Group A at BH 1', 'payment', 'read', '2025-12-06 09:00:23'),
(897, 29, 'New Booking Request', 'You have a new booking request from for Group A', 'booking', 'read', '2025-12-06 09:00:24'),
(898, 62, 'Booking Approved', 'Your booking request for Group A has been checked and approved!', 'booking', 'read', '2025-12-06 09:12:18'),
(899, 29, 'Payment Received', 'Payment of ₱266.67 has been received for from boarder for Group A at BH 1. Status: Fully Paid', 'payment', 'read', '2025-12-06 09:12:19'),
(900, 62, 'Payment Status Updated', 'Your payment of ₱266.67 status has been updated to: Fully Paid', 'payment', 'read', '2025-12-06 09:12:20'),
(901, 29, 'New Payment Pending', 'A new payment of ₱266.67 is pending for Payment for Group A at BH 1', 'payment', 'read', '2025-12-06 09:15:10'),
(902, 29, 'New Booking Request', 'You have a new booking request from for Group A', 'booking', 'read', '2025-12-06 09:15:11'),
(903, 62, 'Booking Approved', 'Your booking request for Group A has been checked and approved!', 'booking', 'read', '2025-12-06 09:15:45'),
(904, 29, 'Payment Received', 'Payment of ₱266.67 has been received for from boarder for Group A at BH 1. Status: Fully Paid', 'payment', 'read', '2025-12-06 09:15:46'),
(905, 62, 'Payment Status Updated', 'Your payment of ₱266.67 status has been updated to: Fully Paid', 'payment', 'read', '2025-12-06 09:15:47'),
(906, 29, 'New Payment Pending', 'A new payment of ₱500.00 is pending for Payment for Group A at BH 1', 'payment', 'read', '2025-12-06 09:20:18'),
(907, 29, 'New Booking Request', 'You have a new booking request from for Group A', 'booking', 'read', '2025-12-06 09:20:19'),
(908, 62, 'Booking Approved', 'Your booking request for Group A has been checked and approved!', 'booking', 'read', '2025-12-06 09:21:19'),
(909, 29, 'Payment Received', 'Payment of ₱500.00 has been received for from boarder for Group A at BH 1. Status: Fully Paid', 'payment', 'read', '2025-12-06 09:21:20'),
(910, 62, 'Payment Status Updated', 'Your payment of ₱500.00 status has been updated to: Fully Paid', 'payment', 'read', '2025-12-06 09:21:21'),
(911, 29, 'New Payment Pending', 'A new payment of ₱700.00 is pending for Payment for Group A at BH 1', 'payment', 'read', '2025-12-06 09:24:45'),
(912, 29, 'New Booking Request', 'You have a new booking request from for Group A', 'booking', 'read', '2025-12-06 09:24:46'),
(913, 37, 'Booking Approved', 'Your booking request for Group A has been checked and approved!', 'booking', 'read', '2025-12-06 09:26:27'),
(914, 29, 'Payment Received', 'Payment of ₱700.00 has been received for from boarder for Group A at BH 1. Status: Fully Paid', 'payment', 'read', '2025-12-06 09:26:28'),
(915, 37, 'Payment Status Updated', 'Your payment of ₱700.00 status has been updated to: Fully Paid', 'payment', 'read', '2025-12-06 09:26:30'),
(916, 64, 'Registration Approved', 'Your registration has been approved! You can now login to your account', '', 'unread', '2025-12-10 01:08:53'),
(917, 29, 'New Payment Pending', 'A new payment of ₱100.00 is pending for Payment for Group A at Sample 1', 'payment', 'unread', '2025-12-10 01:15:49'),
(918, 29, 'New Booking Request', 'You have a new booking request from for Group A', 'booking', 'unread', '2025-12-10 01:15:51'),
(919, 62, 'Booking Approved', 'Your booking request for Group A has been checked and approved!', 'booking', 'read', '2025-12-10 01:16:52'),
(920, 29, 'Payment Received', 'Payment of ₱100.00 has been received for from boarder for Group A at Sample 1. Status: Fully Paid', 'payment', 'unread', '2025-12-10 01:16:54'),
(921, 62, 'Payment Status Updated', 'Your payment of ₱100.00 status has been updated to: Fully Paid', 'payment', 'read', '2025-12-10 01:16:55'),
(922, 44, 'Payment Overdue', 'Your payment of ₱1,000.00 for GA-1 is overdue. Please make payment as soon as possible.', 'payment', 'unread', '2026-01-03 01:31:44'),
(923, 44, 'Payment Overdue', 'Your payment of ₱1,000.00 is overdue. Please settle it as soon as possible', 'payment', 'unread', '2026-01-03 01:31:46');

-- --------------------------------------------------------

--
-- Table structure for table `notification_templates`
--

CREATE TABLE `notification_templates` (
  `id` int(11) NOT NULL,
  `template_key` varchar(100) NOT NULL,
  `template_title` varchar(255) NOT NULL,
  `template_message` text NOT NULL,
  `notification_type` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notification_templates`
--

INSERT INTO `notification_templates` (`id`, `template_key`, `template_title`, `template_message`, `notification_type`, `created_at`, `updated_at`) VALUES
(1, 'booking_created', 'New Booking Request', 'You have a new booking request from {tenant_name} for {room_name}', 'booking', '2025-11-15 04:02:17', '2025-11-15 05:09:51'),
(2, 'booking_approved', 'Booking Approved', 'Your booking request for {room_name} has been checked and approved!', 'booking', '2025-11-15 04:02:17', '2025-11-15 05:09:51'),
(3, 'booking_declined', 'Booking Declined', 'Your booking request for {room_name} has been declined.{reason}', 'booking', '2025-11-15 04:02:17', '2025-11-15 05:09:51'),
(4, 'booking_cancelled', 'Booking Cancelled', 'Booking for {room_name} has been cancelled.', 'booking', '2025-11-15 04:02:17', '2025-11-15 05:09:51'),
(5, 'payment_received', 'Payment Received', 'Payment of ₱{amount} has been received{description}', 'payment', '2025-11-15 04:02:17', '2025-11-15 05:09:51'),
(6, 'payment_created', 'New Payment Pending', 'A new payment of ₱{amount} is pending{description}', 'payment', '2025-11-15 04:02:17', '2025-11-15 05:09:51'),
(7, 'payment_status_updated', 'Payment Status Updated', 'Your payment of ₱{amount} status has been updated to: {status}', 'payment', '2025-11-15 04:02:17', '2025-11-15 05:09:51'),
(8, 'payment_overdue', 'Payment Overdue', 'Your payment of ₱{amount} is overdue. Please settle it as soon as possible.', 'payment', '2025-11-15 04:02:17', '2025-11-15 05:09:51'),
(9, 'maintenance_request', 'New Maintenance Request', '{boarder_name} has submitted a maintenance request for {room_name}: {title}', 'maintenance', '2025-11-15 04:02:17', '2025-11-15 05:09:51'),
(10, 'maintenance_status_updated', 'Maintenance Status Updated', 'Maintenance request status updated to: {status}', 'maintenance', '2025-11-15 04:02:17', '2025-11-15 05:09:51'),
(11, 'maintenance_completed', 'Maintenance Completed', 'Your maintenance request has been completed.', 'maintenance', '2025-11-15 04:02:17', '2025-11-15 05:09:51'),
(12, 'maintenance_feedback', 'Maintenance Feedback', 'Feedback received for maintenance request.', 'maintenance', '2025-11-15 04:02:17', '2025-11-15 05:09:51'),
(13, 'announcement_new', 'New Announcement', '{title}: {message}', 'announcement', '2025-11-15 04:02:17', '2025-11-15 05:09:51'),
(14, 'announcement_owner_response', 'Owner Response', 'Owner responded to your review.', 'announcement', '2025-11-15 04:02:17', '2025-11-15 05:09:51'),
(15, 'registration_approved', 'Registration Approved', 'Your registration has been approved! You can now login to your account.', 'registration', '2025-11-15 04:02:17', '2025-11-15 05:09:51'),
(16, 'registration_rejected', 'Registration Rejected', 'Your registration has been rejected. Please contact support for more information.', 'registration', '2025-11-15 04:02:17', '2025-11-15 05:09:51'),
(17, 'message_new', 'New Message', 'New message from {sender_name}: {message_preview}', 'message', '2025-11-15 04:02:17', '2025-11-15 05:09:51'),
(18, 'message_group', 'New Group Message', 'New message in {group_name} from {sender_name}', 'message', '2025-11-15 04:02:17', '2025-11-15 05:09:51'),
(19, 'security_password_changed', 'Password Changed', 'Your password has been successfully changed.', 'security', '2025-11-15 04:02:17', '2025-11-15 05:09:51'),
(20, 'security_email_changed', 'Email Changed', 'Your email address has been successfully changed.', 'security', '2025-11-15 04:02:17', '2025-11-15 05:09:51');

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `used` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `password_resets`
--

INSERT INTO `password_resets` (`id`, `email`, `token`, `expires_at`, `created_at`, `used`) VALUES
(29, 'cuasruel028@gmail.com', '2818b907f9fdcfb1b1437ac637f4f9c11ea897e223c86496a0b44698ef16a4f5', '2025-11-25 13:04:21', '2025-11-25 19:34:21', 1),
(30, 'namzbaer@gmail.com', 'aba65293a62af4fb83ff31183a7ec814891ef8697ede793c5d6f6ddf84cdff01', '2025-12-01 07:49:43', '2025-12-01 14:19:43', 0),
(31, 'hannacuas536@gmail.com', 'da1e0e145cc3abd06e87618ffefe21917e9ef1d311e696d340f7e37958786dc6', '2025-12-01 07:52:37', '2025-12-01 14:22:37', 0),
(33, 'christecuas947@gmail.com', '2c6bad13d133aa8384021a9944601214f119eb8afaa62540157e770c17dcfacb', '2025-12-06 08:31:45', '2025-12-06 15:01:45', 1);

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `payment_id` int(11) NOT NULL,
  `booking_id` int(11) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `owner_id` int(11) NOT NULL,
  `payment_amount` decimal(10,2) NOT NULL,
  `payment_method` enum('Cash','GCash','Bank Transfer','Check') NOT NULL DEFAULT 'Cash',
  `payment_proof` text DEFAULT NULL,
  `payment_status` enum('Pending','Partially Paid','Fully Paid','Failed','Refunded') NOT NULL DEFAULT 'Pending',
  `payment_date` datetime NOT NULL DEFAULT current_timestamp(),
  `receipt_url` varchar(500) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`payment_id`, `booking_id`, `user_id`, `owner_id`, `payment_amount`, `payment_method`, `payment_proof`, `payment_status`, `payment_date`, `receipt_url`, `notes`, `created_at`, `updated_at`) VALUES
(26, 23, 59, 29, 5000.00, 'GCash', 'uploads/payment_proofs/payment_proof_23_1762773741.jpg', 'Fully Paid', '2025-11-10 19:22:21', NULL, 'Marked as paid by owner', '2025-11-10 11:22:21', '2025-11-19 11:39:53'),
(27, 24, 44, 29, 1000.00, 'GCash', 'uploads/payment_proofs/payment_proof_24_1762775937.jpg', 'Partially Paid', '2025-11-10 19:58:57', NULL, 'Marked as paid by owner', '2025-11-10 11:58:57', '2025-11-16 02:25:31'),
(28, 25, 35, 29, 166.67, 'GCash', 'uploads/payment_proofs/payment_proof_25_1762998070.jpg', 'Fully Paid', '2025-11-13 09:41:10', NULL, 'Marked as paid by owner', '2025-11-13 01:41:10', '2025-11-13 01:42:56'),
(29, 26, 35, 29, 166.67, 'GCash', 'uploads/payment_proofs/payment_proof_26_1763000868.jpg', 'Fully Paid', '2025-11-13 10:27:49', NULL, 'Marked as paid by owner', '2025-11-13 02:27:49', '2025-11-13 02:33:49'),
(30, 27, 35, 29, 166.67, 'GCash', 'uploads/payment_proofs/payment_proof_27_1763001771.jpg', 'Fully Paid', '2025-11-13 10:42:51', NULL, 'Marked as paid by owner', '2025-11-13 02:42:51', '2025-11-13 02:44:18'),
(31, 28, 35, 29, 166.67, 'GCash', 'uploads/payment_proofs/payment_proof_28_1763002438.jpg', 'Fully Paid', '2025-11-13 10:53:58', NULL, 'Marked as paid by owner', '2025-11-13 02:53:58', '2025-11-13 02:55:29'),
(32, 29, 28, 1, 510.00, 'Cash', 'uploads/payment_proofs/payment_proof_29_1763002801.jpg', 'Pending', '2025-11-13 11:00:01', NULL, NULL, '2025-11-13 03:00:01', '2025-11-13 03:00:01'),
(33, 30, 28, 29, 5000.00, 'Cash', 'uploads/payment_proofs/payment_proof_30_1763003044.jpg', 'Partially Paid', '2025-11-13 11:04:04', NULL, 'Marked as paid by owner', '2025-11-13 03:04:04', '2025-11-28 12:09:36'),
(34, 31, 35, 29, 166.67, 'GCash', 'uploads/payment_proofs/payment_proof_31_1763003063.jpg', 'Fully Paid', '2025-11-13 11:04:23', NULL, 'Marked as paid by owner', '2025-11-13 03:04:23', '2025-11-13 03:15:44'),
(35, 32, 35, 29, 166.67, 'GCash', 'uploads/payment_proofs/payment_proof_32_1763004019.jpg', 'Fully Paid', '2025-11-13 11:20:19', NULL, 'Marked as paid by owner', '2025-11-13 03:20:19', '2025-11-13 03:23:27'),
(36, 33, 35, 29, 166.67, 'GCash', 'uploads/payment_proofs/payment_proof_33_1763004981.jpg', 'Fully Paid', '2025-11-13 11:36:21', NULL, 'Marked as paid by owner', '2025-11-13 03:36:21', '2025-11-13 03:37:48'),
(37, 34, 35, 29, 166.67, 'GCash', 'uploads/payment_proofs/payment_proof_34_1763005635.jpg', 'Fully Paid', '2025-11-13 11:47:15', NULL, 'Marked as paid by owner', '2025-11-13 03:47:15', '2025-11-13 03:48:39'),
(38, 35, 28, 29, 166.67, 'Cash', 'uploads/payment_proofs/payment_proof_35_1763013975.jpg', 'Fully Paid', '2025-11-13 14:06:16', NULL, 'Marked as paid by owner', '2025-11-13 06:06:16', '2025-11-13 06:06:55'),
(39, 36, 28, 29, 166.67, 'GCash', 'uploads/payment_proofs/payment_proof_36_1763014585.jpg', 'Fully Paid', '2025-11-13 14:16:25', NULL, 'Marked as paid by owner', '2025-11-13 06:16:25', '2025-11-13 06:18:06'),
(40, 37, 59, 29, 166.67, 'GCash', 'uploads/payment_proofs/payment_proof_37_1763205805.jpg', 'Fully Paid', '2025-11-15 19:23:25', NULL, 'Marked as paid by owner', '2025-11-15 11:23:25', '2025-11-15 11:26:12'),
(41, 38, 59, 29, 166.67, 'GCash', 'uploads/payment_proofs/payment_proof_38_1763206258.jpg', 'Fully Paid', '2025-11-15 19:30:58', NULL, 'Marked as paid by owner', '2025-11-15 11:30:58', '2025-11-15 11:48:05'),
(44, 41, 35, 29, 166.67, 'GCash', 'uploads/payment_proofs/payment_proof_41_1763214917.jpg', 'Fully Paid', '2025-11-15 21:55:17', NULL, NULL, '2025-11-15 13:55:17', '2025-11-15 13:56:02'),
(49, 46, 38, 29, 5000.00, 'GCash', 'uploads/payment_proofs/payment_proof_46_1763269690.jpg', 'Partially Paid', '2025-11-16 13:08:10', NULL, 'Marked as paid by owner', '2025-11-16 05:08:10', '2025-11-19 11:56:07'),
(51, 48, 35, 29, 166.67, 'GCash', 'uploads/payment_proofs/payment_proof_48_1763382666.jpg', 'Fully Paid', '2025-11-17 20:31:06', NULL, NULL, '2025-11-17 12:31:06', '2025-11-17 12:33:30'),
(52, 49, 38, 29, 4166.67, 'GCash', 'uploads/payment_proofs/payment_proof_49_1763466447.jpg', 'Fully Paid', '2025-11-18 19:47:27', NULL, NULL, '2025-11-18 11:47:27', '2025-11-19 11:48:14'),
(56, 23, 59, 29, 3166.67, 'GCash', 'uploads/payment_proofs/payment_proof_23_1763552260.jpg', 'Fully Paid', '2025-11-19 19:37:40', NULL, 'Marked as paid by owner', '2025-11-19 11:37:40', '2025-11-19 11:39:53'),
(57, 46, 38, 29, 5000.00, 'GCash', 'uploads/payment_proofs/payment_proof_46_1763553280.jpg', 'Partially Paid', '2025-11-19 19:54:40', NULL, 'Marked as paid by owner', '2025-11-19 11:54:40', '2025-11-19 11:56:07'),
(58, 50, 35, 29, 166.67, 'GCash', 'uploads/payment_proofs/payment_proof_50_1764318930.jpg', 'Fully Paid', '2025-11-28 16:35:30', NULL, NULL, '2025-11-28 08:35:30', '2025-11-28 09:15:15'),
(59, 30, 28, 29, 5000.00, 'GCash', 'uploads/payment_proofs/payment_proof_30_1764331634.jpg', 'Partially Paid', '2025-11-28 20:07:14', NULL, 'Marked as paid by owner', '2025-11-28 12:07:14', '2025-11-28 12:09:36'),
(67, 30, 28, 29, 11333.33, 'GCash', 'uploads/payment_proofs/payment_proof_30_1765315985.jpg', '', '2025-12-10 05:33:05', NULL, NULL, '2025-12-09 21:33:05', '2026-01-03 01:31:44'),
(68, 58, 62, 29, 100.00, 'GCash', 'uploads/payment_proofs/payment_proof_58_1765329347.jpg', 'Fully Paid', '2025-12-10 09:15:47', NULL, NULL, '2025-12-10 01:15:47', '2025-12-10 01:16:50');

-- --------------------------------------------------------

--
-- Table structure for table `payment_breakdowns`
--

CREATE TABLE `payment_breakdowns` (
  `breakdown_id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `payment_id` int(11) DEFAULT NULL COMMENT 'Links to payments table if payment is made',
  `period_type` enum('month','days') NOT NULL COMMENT 'Type of period: month or days',
  `period_number` int(3) NOT NULL COMMENT 'Month number (1, 2, 3...) or 0 for days',
  `period_label` varchar(50) NOT NULL COMMENT 'Display label: "1st month", "2nd month", "3 days", etc.',
  `period_start_date` date NOT NULL COMMENT 'Start date of this payment period',
  `period_end_date` date NOT NULL COMMENT 'End date of this payment period',
  `amount` decimal(10,2) NOT NULL COMMENT 'Amount for this period',
  `is_selected` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Whether boarder selected this period for payment',
  `is_paid` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Whether this period has been paid',
  `due_date` date DEFAULT NULL COMMENT 'Due date for this payment period',
  `payment_status` enum('Pending','Paid','Overdue','Cancelled') NOT NULL DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payment_breakdowns`
--

INSERT INTO `payment_breakdowns` (`breakdown_id`, `booking_id`, `payment_id`, `period_type`, `period_number`, `period_label`, `period_start_date`, `period_end_date`, `amount`, `is_selected`, `is_paid`, `due_date`, `payment_status`, `created_at`, `updated_at`) VALUES
(17, 23, 26, 'month', 1, '1st month', '2025-11-11', '2025-12-10', 5000.00, 1, 1, '2025-11-11', 'Paid', '2025-11-10 11:22:21', '2025-11-10 11:36:18'),
(18, 23, 56, 'days', 0, '19 days', '2025-12-11', '2025-12-29', 3166.67, 0, 1, '2025-12-11', 'Paid', '2025-11-10 11:22:21', '2025-11-19 11:39:53'),
(19, 24, 27, 'month', 1, '1st month', '2025-11-11', '2025-12-10', 1000.00, 1, 1, '2025-11-11', 'Paid', '2025-11-10 11:58:57', '2025-11-10 12:02:22'),
(20, 24, 27, 'month', 2, '2nd month', '2025-12-11', '2026-01-09', 1000.00, 0, 0, '2025-12-11', 'Overdue', '2025-11-10 11:58:57', '2026-01-03 01:31:44'),
(21, 24, 27, 'days', 0, '22 days', '2026-01-10', '2026-01-31', 733.33, 0, 0, '2026-01-10', 'Pending', '2025-11-10 11:58:57', '2025-11-10 11:58:57'),
(27, 30, 33, 'month', 1, '1st month', '2025-11-14', '2025-12-13', 5000.00, 1, 1, '2025-11-14', 'Paid', '2025-11-13 03:04:04', '2025-11-13 03:06:26'),
(28, 30, 59, 'month', 2, '2nd month', '2025-11-24', '2026-01-12', 5000.00, 0, 1, '2025-11-24', 'Paid', '2025-11-13 03:04:04', '2025-11-28 12:09:36'),
(29, 30, 67, 'month', 3, '3rd month', '2026-01-13', '2026-02-11', 5000.00, 0, 0, '2026-01-13', 'Pending', '2025-11-13 03:04:04', '2025-12-09 21:33:05'),
(30, 30, 67, 'month', 4, '4th month', '2026-02-12', '2026-03-13', 5000.00, 0, 0, '2026-02-12', 'Pending', '2025-11-13 03:04:04', '2025-12-09 21:33:05'),
(31, 30, 67, 'days', 0, '8 days', '2026-03-14', '2026-03-21', 1333.33, 0, 0, '2026-03-14', 'Pending', '2025-11-13 03:04:04', '2025-12-09 21:33:05'),
(35, 34, 37, 'days', 0, '1 day', '2025-11-14', '2025-11-14', 166.67, 1, 1, '2025-11-14', 'Paid', '2025-11-13 03:47:15', '2025-11-13 03:48:39'),
(37, 36, 39, 'days', 0, '1 day', '2025-11-14', '2025-11-14', 166.67, 1, 1, '2025-11-14', 'Paid', '2025-11-13 06:16:25', '2025-11-13 06:18:06'),
(39, 38, 41, 'days', 0, '1 day', '2025-11-16', '2025-11-16', 166.67, 1, 1, '2025-11-16', 'Paid', '2025-11-15 11:30:58', '2025-11-15 11:48:05'),
(42, 41, 44, 'days', 0, '1 day', '2025-11-16', '2025-11-16', 166.67, 1, 1, '2025-11-16', 'Paid', '2025-11-15 13:55:17', '2025-11-15 13:56:02'),
(54, 46, 49, 'month', 1, '1st month', '2025-11-17', '2025-12-16', 5000.00, 1, 1, '2025-11-17', 'Paid', '2025-11-16 05:08:10', '2025-11-16 05:08:52'),
(55, 46, 57, 'month', 2, '2nd month', '2025-12-17', '2026-01-15', 5000.00, 0, 1, '2025-12-17', 'Paid', '2025-11-16 05:08:10', '2025-11-19 11:56:07'),
(56, 46, 49, 'days', 0, '16 days', '2026-01-16', '2026-01-31', 2666.67, 0, 0, '2026-01-16', 'Pending', '2025-11-16 05:08:10', '2025-11-16 05:08:10'),
(59, 48, 51, 'days', 0, '1 day', '2025-11-18', '2025-11-18', 166.67, 1, 1, '2025-11-18', 'Paid', '2025-11-17 12:31:06', '2025-11-17 12:33:30'),
(60, 49, 52, 'days', 0, '25 days', '2025-11-19', '2025-12-13', 4166.67, 1, 1, '2025-11-19', 'Paid', '2025-11-18 11:47:27', '2025-11-19 11:48:14'),
(61, 50, 58, 'days', 0, '1 day', '2025-11-29', '2025-11-29', 166.67, 1, 1, '2025-11-29', 'Paid', '2025-11-28 08:35:30', '2025-11-28 09:15:15'),
(70, 58, 68, 'days', 0, '3 days', '2025-12-11', '2025-12-13', 100.00, 1, 1, '2025-12-11', 'Paid', '2025-12-10 01:15:47', '2025-12-10 01:16:50');

-- --------------------------------------------------------

--
-- Table structure for table `payment_reminder_logs`
--

CREATE TABLE `payment_reminder_logs` (
  `log_id` int(11) NOT NULL,
  `breakdown_id` int(11) NOT NULL COMMENT 'Payment breakdown ID',
  `user_id` int(11) NOT NULL COMMENT 'User who should receive the reminder',
  `reminder_type` enum('5_days_before','3_days_before','2_days_before','1_day_before','due_date') NOT NULL COMMENT 'Type of reminder',
  `due_date` date NOT NULL COMMENT 'Due date of the payment',
  `reminder_date` date NOT NULL COMMENT 'Date when reminder was sent',
  `notif_id` int(11) DEFAULT NULL COMMENT 'Notification ID created',
  `fcm_sent` tinyint(1) DEFAULT 0 COMMENT 'Whether FCM push notification was sent',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Tracks payment reminder notifications to prevent duplicates';

--
-- Dumping data for table `payment_reminder_logs`
--

INSERT INTO `payment_reminder_logs` (`log_id`, `breakdown_id`, `user_id`, `reminder_type`, `due_date`, `reminder_date`, `notif_id`, `fcm_sent`, `created_at`) VALUES
(5, 28, 28, '', '2025-11-28', '2025-11-24', 755, 1, '2025-11-24 02:51:03');

-- --------------------------------------------------------

--
-- Table structure for table `registration`
--

CREATE TABLE `registration` (
  `reg_id` int(11) NOT NULL,
  `role` enum('Boarder','Owner') NOT NULL,
  `f_name` varchar(50) NOT NULL,
  `m_name` varchar(50) DEFAULT NULL,
  `l_name` varchar(50) NOT NULL,
  `birthdate` date NOT NULL,
  `phone_number` varchar(15) NOT NULL,
  `p_address` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `valid_id` varchar(50) NOT NULL,
  `front_id` varchar(255) DEFAULT NULL,
  `back_id` varchar(255) DEFAULT NULL,
  `id_number` varchar(50) NOT NULL,
  `gcash_qr` varchar(255) DEFAULT NULL,
  `gcash_number` varchar(15) NOT NULL,
  `status` enum('Approved','Pending','Declined') NOT NULL DEFAULT 'Pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `registration`
--

INSERT INTO `registration` (`reg_id`, `role`, `f_name`, `m_name`, `l_name`, `birthdate`, `phone_number`, `p_address`, `email`, `password`, `valid_id`, `front_id`, `back_id`, `id_number`, `gcash_qr`, `gcash_number`, `status`) VALUES
(1, 'Owner', 'John', 'Michael', 'Doe', '1985-03-15', '09123456789', '123 Main Street, Cebu City', 'john.doe@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Driver License', NULL, NULL, 'DL123456789', NULL, '09123456789', 'Approved'),
(2, 'Owner', 'Namz', 'Mm', 'Baer', '2004-09-10', '09171234568', 'Calape, Bohol', 'namzbaer@gmail.com', '$2y$10$Q.RNHpk7eHhoTHZTm2.11.RsRLhF/NbGeFVqUjI02MSTjLe9v9HTO', 'Passport', 'front_passport.jpg', 'back_passport.jpg', 'ID987654321', 'uploads/gcash_qr/gcash_qr_1_1759443376.jpg', '09925311409', 'Approved'),
(3, 'Boarder', 'Mike', 'James', 'Johnson', '1998-11-08', '09123456791', '789 Pine Street, Cebu City', 'mike.johnson@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Student ID', NULL, NULL, 'ST123456790', NULL, '09123456791', 'Approved'),
(4, 'Owner', 'Sarah', 'Elizabeth', 'Wilson', '1982-05-12', '09123456792', '321 Elm Street, Cebu City', 'sarah.wilson@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Driver License', NULL, NULL, 'DL123456791', NULL, '09123456792', 'Approved'),
(5, 'Boarder', 'David', 'Robert', 'Brown', '1996-09-30', '09123456793', '654 Maple Avenue, Cebu City', 'david.brown@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Student ID', NULL, NULL, 'ST123456792', NULL, '09123456793', 'Approved'),
(6, 'Boarder', 'Lisa', 'Ann', 'Davis', '1997-12-18', '09123456794', '987 Cedar Lane, Cebu City', 'lisa.davis@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Student ID', NULL, NULL, 'ST123456793', NULL, '09123456794', 'Approved'),
(7, 'Owner', 'Tom', 'William', 'Miller', '1980-01-25', '09123456795', '147 Birch Road, Cebu City', 'tom.miller@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Driver License', NULL, NULL, 'DL123456792', NULL, '09123456795', 'Approved'),
(8, 'Boarder', 'Emma', 'Grace', 'Garcia', '1999-04-03', '09123456796', '258 Spruce Drive, Cebu City', 'emma.garcia@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Student ID', NULL, NULL, 'ST123456794', NULL, '09123456796', 'Approved'),
(65, 'Owner', 'John', 'Michael', 'Doe', '1985-03-15', '09123456789', '123 Main Street, Cebu City', 'mae.sam@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Driver License', NULL, NULL, 'DL123456789', NULL, '09123456789', 'Approved'),
(66, 'Boarder', 'Jane', 'Marie', 'Smith', '1995-07-22', '09123456790', '456 Oak Avenue, Cebu City', 'jane.smith@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Student ID', NULL, NULL, 'ST123456789', NULL, '09123456790', 'Approved'),
(67, 'Boarder', 'Mike', 'James', 'Johnson', '1998-11-08', '09123456791', '789 Pine Street, Cebu City', 'ru.john@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Student ID', NULL, NULL, 'ST123456790', NULL, '09123456791', 'Approved'),
(69, 'Boarder', 'David', 'Robert', 'Brown', '1996-09-30', '09123456793', '654 Maple Avenue, Cebu City', 'hash.mon@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Student ID', NULL, NULL, 'ST123456792', NULL, '09123456793', 'Approved'),
(70, 'Boarder', 'Lisa', 'Ann', 'Davis', '1997-12-18', '09123456794', '987 Cedar Lane, Cebu City', 'am.ko@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Student ID', NULL, NULL, 'ST123456793', NULL, '09123456794', 'Approved'),
(71, 'Owner', 'Tom', 'William', 'Miller', '1980-01-25', '09123456795', '147 Birch Road, Cebu City', 'ho.lo@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Driver License', NULL, NULL, 'DL123456792', NULL, '09123456795', 'Approved'),
(72, 'Boarder', 'Emma', 'Grace', 'Garcia', '1999-04-03', '09123456796', '258 Spruce Drive, Cebu City', 'wo.uy@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Student ID', NULL, NULL, 'ST123456794', NULL, '09123456796', 'Approved'),
(137, 'Owner', 'John', 'Michael', 'Doe', '1985-03-15', '09123456789', '123 Main Street, Cebu City', 'chris.cuas@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Driver License', NULL, NULL, 'DL123456789', NULL, '09123456789', 'Approved'),
(138, 'Boarder', 'Jane', 'Marie', 'Smith', '1995-07-22', '09123456790', '456 Oak Avenue, Cebu City', 'cam.phpr@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Student ID', NULL, NULL, 'ST123456789', NULL, '09123456790', 'Approved'),
(139, 'Boarder', 'Mike', 'James', 'Johnson', '1998-11-08', '09123456791', '789 Pine Street, Cebu City', 'ruel.john@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Student ID', NULL, NULL, 'ST123456790', NULL, '09123456791', 'Approved'),
(140, 'Owner', 'Sarah', 'Elizabeth', 'Wilson', '1982-05-12', '09123456792', '321 Elm Street, Cebu City', 'willy.lon@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Driver License', NULL, NULL, 'DL123456791', NULL, '09123456792', 'Approved'),
(142, 'Boarder', 'Lisa', 'Ann', 'Davis', '1997-12-18', '09123456794', '987 Cedar Lane, Cebu City', 'amber.ko@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Student ID', NULL, NULL, 'ST123456793', NULL, '09123456794', 'Approved'),
(143, 'Owner', 'Tom', 'William', 'Miller', '1980-01-25', '09123456795', '147 Birch Road, Cebu City', 'hole.lo@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Driver License', NULL, NULL, 'DL123456792', NULL, '09123456795', 'Approved'),
(144, 'Boarder', 'Emma', 'Grace', 'Garcia', '1999-04-03', '09123456796', '258 Spruce Drive, Cebu City', 'wolo.uy@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Student ID', NULL, NULL, 'ST123456794', NULL, '09123456796', 'Approved');

-- --------------------------------------------------------

--
-- Table structure for table `registrations`
--

CREATE TABLE `registrations` (
  `id` int(11) NOT NULL,
  `role` varchar(50) NOT NULL COMMENT 'Boarder or BH Owner',
  `first_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) NOT NULL,
  `birth_date` date DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `gcash_num` varchar(20) DEFAULT NULL,
  `valid_id_type` varchar(100) DEFAULT NULL COMMENT 'Type of valid ID',
  `id_number` varchar(50) DEFAULT NULL COMMENT 'ID Number',
  `cb_agreed` tinyint(1) DEFAULT 0 COMMENT 'Terms and conditions agreed',
  `idFrontFile` varchar(255) DEFAULT NULL COMMENT 'Path to front ID image',
  `idBackFile` varchar(255) DEFAULT NULL COMMENT 'Path to back ID image',
  `gcash_qr` varchar(255) DEFAULT NULL COMMENT 'Path to GCash QR image',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status` enum('unverified','pending','approved','rejected') DEFAULT 'unverified',
  `email_verified` tinyint(1) DEFAULT 0,
  `suffix` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `registrations`
--

INSERT INTO `registrations` (`id`, `role`, `first_name`, `middle_name`, `last_name`, `birth_date`, `phone`, `address`, `email`, `password`, `gcash_num`, `valid_id_type`, `id_number`, `cb_agreed`, `idFrontFile`, `idBackFile`, `gcash_qr`, `created_at`, `updated_at`, `status`, `email_verified`, `suffix`) VALUES
(1, 'Boarder', 'Test', NULL, 'User', NULL, NULL, NULL, 'test@example.com', 'test123', NULL, NULL, NULL, 0, NULL, NULL, NULL, '2025-10-05 22:08:09', '2025-10-26 05:25:18', 'approved', 1, NULL),
(2, 'Boarder', 'Test', NULL, 'User', NULL, NULL, NULL, 'test2@example.com', 'test123', NULL, NULL, NULL, 0, NULL, NULL, NULL, '2025-10-05 22:16:52', '2025-10-26 05:25:18', 'approved', 1, NULL),
(3, 'Boarder', 'Kimberly Jul', 'Binag', 'Mante', '2025-10-06', '09925311463', 'Lucob', 'kimjul@gmail.con', 'dhdjdkdk', '2134546', 'Driver\'s License', '123456789', 0, 'uploads/68e2eed214c01_front.jpg', 'uploads/68e2eed215235_back.jpg', 'uploads/68e2eed2153c1_qr.jpg', '2025-10-05 22:18:58', '2025-10-26 05:25:18', 'approved', 1, NULL),
(5, 'BH Owner', 'Christe Hanna', 'Dalugdog', 'Cuas', '2003-10-07', '09123456789', 'Tinibgan, Calape, Bohol', 'christehanna@gmail.com', 'namie', '09925311463', 'GSIS e-card', '123456789', 0, 'uploads/68e4f3b4e49ab_front.jpg', 'uploads/68e4f3b4e66be_back.jpg', 'uploads/68e4f3b4e86af_qr.jpg', '2025-10-07 11:04:20', '2025-10-26 05:25:18', 'approved', 1, NULL),
(8, 'Boarder', 'Flora', 'Oracion', 'Mante', '2004-09-07', '09925311463', 'Lucob, Calape, Bohol', 'floramante@gmail.com', 'flora', '123456789', 'SSS ID', '123456789', 0, 'uploads/68e4f92302869_front.jpg', 'uploads/68e4f92304024_back.jpg', 'uploads/68e4f92305704_qr.jpg', '2025-10-07 11:27:31', '2025-10-26 05:25:18', 'approved', 1, NULL),
(31, 'BH Owner', 'Hanna', 'Dalu', 'Baer', '0000-00-00', '09925311409', 'tini', 'hanna@gmail.com', '$2y$10$PGaMA3PAWMCB8zizQL9GNuML9moOOTo0W2FGHJ/MFeGUvhvn9DrnW', '09925311409', 'PhilID (National ID)', '12345678', 0, 'uploads/registrations/68e671d0356d0_front.jpg', 'uploads/registrations/68e671d035d67_back.jpg', 'uploads/registrations/68e671d037dbd_qr.jpg', '2025-10-08 14:14:40', '2025-10-26 05:25:18', 'approved', 1, NULL),
(35, 'BH Owner', 'Mari', 'Dalu', 'Baer', '0000-00-00', '09925311409', 'tini', 'mari@gmail.com', '$2y$10$00.1846IMH5PJixoF53O4u2B4lhsoG2gzqqVN0YraZayL/ywf4AB2', '09925311409', 'PhilID (National ID)', '12345678', 0, 'uploads/registrations/68e6722a65d31_front.jpg', 'uploads/registrations/68e6722a664ab_back.jpg', 'uploads/registrations/68e6722a68582_qr.jpg', '2025-10-08 14:16:10', '2025-10-26 05:25:18', 'approved', 1, NULL),
(42, 'Boarder', 'Mama', 'Mo', 'Ko', '2025-10-08', '9929769150', 'tinibgan', 'mama@gmail.com', '$2y$10$70UDp1ckqdUDq7imWw04u.XX8wYwOgbM3xT7OPaMDxuSwOOtmAfc6', '09353549141', 'PhilID (National ID)', '235689', 0, 'uploads/registrations/68e675f4de651_front.jpg', 'uploads/registrations/68e675f4dedde_back.jpg', 'uploads/registrations/68e675f4df3f8_qr.jpg', '2025-10-08 14:32:20', '2025-10-26 05:25:18', 'approved', 1, NULL),
(51, 'Boarder', 'Lizz', 'Dela', 'Uy', '2005-10-09', '09929769150949494954', 'Purok 2, Ubayon, Loon, Bohol', 'hannacuas536@gmail.com', '$2y$10$ysgQ4YDI7.7BuzL3D6Wym.PkJHAR.T.cTQ43FM7VdTZEYAmxhovBm', '09925314096', 'PhilID (National ID)', '2356890', 0, 'uploads/registrations/68e709409683a_front.jpg', 'uploads/registrations/68e70940980cc_back.jpg', 'uploads/registrations/68e709409a367_qr.jpg', '2025-10-09 01:00:48', '2025-11-21 05:42:25', 'approved', 1, NULL),
(53, 'BH Owner', 'Namz', 'Dalug', 'Baer', '2025-10-09', '09925311409', 'Purok 2, Tinibgan, Calape, Bohol', 'namzbaer@gmail.com', '$2y$10$yDUR/8qwfefjwTIDYb9bZOrDTtIuKqFuagu10qfCTTjBluBPF0.tK', '09925311409', 'PhilID (National ID)', '2356890', 0, 'uploads/registrations/68e70b7a1a08c_front.jpg', 'uploads/registrations/68e70b7a1bcd8_back.jpg', 'uploads/gcash_qr/gcash_qr_29_1762776552.jpg', '2025-10-09 01:10:18', '2025-11-20 15:15:31', 'approved', 1, NULL),
(79, 'Boarder', 'Ruel', 'Dalugdog', 'Cuas', '2025-10-26', '09925311409', 'Patag, Tinibgan, Calape, Bohol', 'cuasruel028@gmail.com', '$2y$10$DY7Ro7tsvNvE48geln2oP.cqCMLSJNiNKLxhbXzFPkZD7u3kRz/QG', '09925311409', 'PhilID (National ID)', '123456789', 0, 'uploads/registrations/68fdb9b3ad6f2_front.jpg', 'uploads/registrations/68fdb9b3adcbe_back.jpg', 'uploads/registrations/68fdb9b3ae3e5_qr.jpg', '2025-10-26 06:03:31', '2025-12-06 04:16:46', 'approved', 1, NULL),
(84, 'BH Owner', 'Kimberly', 'Binag', 'Mante', '2025-10-27', '9925311409', 'lucob', 'kimjulmante@gmail.com', '$2y$10$nibA1zDk6rc1YA0qRGqWjOFZT158iHkTz0hYjcB6nimatAqqCBLEa', '09925311409', 'PhilID (National ID)', '123456789', 0, 'uploads/registrations/68feb9ecdee8d_front.jpg', 'uploads/registrations/68feb9ecdf784_back.jpg', 'uploads/registrations/68feb9ecdfe7e_qr.jpg', '2025-10-27 00:16:44', '2025-10-28 12:17:55', 'approved', 1, NULL),
(85, 'Boarder', 'Shevic', 'Rulona', 'Tacatane', '2025-10-27', '09925311463', 'Bentig', 'mayettacatane@gmail.com', '$2y$10$gnziH/TxdrRG8EEcC15Nvu1/QFmI5eAgGekP3KUTzW63MXVA4.g/q', '09925311463', 'Driver\'s License', '123456789', 0, 'uploads/registrations/68fecdda9e8de_front.jpg', 'uploads/registrations/68fecdda9ec38_back.jpg', 'uploads/registrations/68fecdda9ee9f_qr.jpg', '2025-10-27 01:41:46', '2025-12-06 09:23:08', 'approved', 1, NULL),
(86, 'Boarder', 'John Mark', 'Marimon', 'Sagetarios', '2025-10-27', '9929769150', 'ubayon', 'johnmark.sagetarios@bisu.edu.ph', '$2y$10$as8INj1J.ZXQdZYnR.jvPu7vuzASFr0KMpfLlyE8OqUxPA2ewHYRm', '09925311409', 'PhilID (National ID)', '123456789', 0, 'uploads/registrations/68fecfb7dcae8_front.jpg', 'uploads/registrations/68fecfb7dce3f_back.jpg', 'uploads/registrations/68fecfb7dd119_qr.jpg', '2025-10-27 01:49:43', '2025-10-27 01:53:48', 'approved', 1, NULL),
(103, 'Boarder', 'Ruel', 'Dalugdog', 'Cuas', '2002-10-31', '09925311409', 'Patag, Tinibgan, Calape, Bohol', 'lizacuas975@gmail.com', '$2y$10$OY/mpPzkbLpZW4v./vIqLe1QnLYGAevcJ9EDbz.Z15bboRV.0f/JG', NULL, 'Driver\'s License', '20-000299', 0, 'uploads/registrations/69046410b4b70_front.jpg', 'uploads/registrations/69046410b6e12_back.jpg', NULL, '2025-10-31 07:24:00', '2025-10-31 07:25:49', 'approved', 1, 'Jr.'),
(105, 'Boarder', 'John', 'Marimon', 'Sagetarios', '2001-11-10', '09925311409', 'Purok 1, Ubayon, Loon, Bohol', 'johnmarksagetarios114@gmail.com', '$2y$10$zf2d0LRgCvpDu8ro31dNbOkKT8FWq52UnFchA.uYLXoNtU1dEjLWO', NULL, 'PhilID (National ID)', '2938-6034-9840-8726', 0, 'uploads/registrations/6911306302530_front.jpg', 'uploads/registrations/6911306303503_back.jpg', NULL, '2025-11-10 00:22:59', '2025-11-10 00:31:05', 'approved', 1, NULL),
(108, 'Boarder', 'Liza', 'Dalugdog', 'Cuas', '1993-11-10', '09925311409', 'Purok 1, Ubayon, Loon, Bohol', 'christecuas947@gmail.com', '$2y$10$cgCt6awbFEg20UfwXgWpOuhDdVi7m6g/GgFSkWnFyGyZcaQp9m8LC', NULL, 'PhilID (National ID)', '2938-6034-9840-8726', 0, 'uploads/registrations/69113cf53cb70_front.jpg', 'uploads/registrations/69113cf53faf4_back.jpg', NULL, '2025-11-10 01:16:37', '2026-01-04 07:35:39', 'approved', 1, NULL),
(117, 'BH Owner', 'Kim', 'Ja', 'Ka', '1997-11-19', '09925311409', 'Purok 2, Tinibgan, Calape, Bohol', 'kikyamnarrates@gmail.com', '$2y$10$mxRvRlPtj0e9kfe3ZfNF5OG9Jh3OnHFEfCuvfHrVYYP06QuJ9MFXu', '09974593660', 'PhilID (National ID)', '2938-6034-9840-8726', 0, 'uploads/registrations/691d6a5034a5a_front.jpg', 'uploads/registrations/691d6a5036a4e_back.jpg', 'uploads/registrations/691d6a50383aa_qr.jpg', '2025-11-19 06:57:26', '2025-11-19 06:58:43', 'pending', 1, NULL),
(123, 'BH Owner', 'Christopher', 'Fernandico', 'Mamac', '1992-12-10', '09925311463', 'Jdjdjdjd Ywuwu, Anini, Anini-Y, Antique', 'mamacgwapo@gmail.com', '$2y$10$oz/Yf4AEQgyaDuASlDkLxOgPLSLUhZjqu1YgX1DG.yD1XQId1/KEG', '09925311463', 'PhilID (National ID)', '3076-4365-8904-7269', 0, 'uploads/registrations/6938c782032a5_front.jpg', 'uploads/registrations/6938c78206b3d_back.jpg', 'uploads/registrations/6938c78209fd6_qr.jpg', '2025-12-10 01:06:20', '2025-12-10 01:08:46', 'approved', 1, NULL),
(124, 'BH Owner', 'Marilyn', 'Binag', 'Mante', '2008-01-04', '09925311643', 'Cvbbhdhdjdj, Don Francisco, Butuan City, Agusan Del Norte', 'mantekimberlyjul@gmail.com', '$2y$10$ZIwyno6bTW70aU/dRHtXoONIXa4eEyXLfBCLEJPMRNjTqmMUwDEvu', '09925311466', 'PhilID (National ID)', '3076-4365-8904-7269', 0, 'uploads/registrations/695a27d6e6c3f_front.jpg', 'uploads/registrations/695a27d6e8364_back.jpg', 'uploads/registrations/695a27d6e921d_qr.jpg', '2026-01-04 08:42:04', '2026-01-04 08:42:04', 'unverified', 0, NULL),
(125, 'Boarder', 'Kimberly Jul', 'Binag', 'Mante', '2008-01-04', '09764646446', 'IJajabznznz17811, Culis, Hermosa, Bataan', 'mantekim96@gmail.com', '$2y$10$1HV3phVGwOZVQJQu2ura8.w2RcJtK5l7eT8v2P/FyDkSvLw8lR40i', NULL, 'PhilID (National ID)', '3076-4365-8904-7269', 0, 'uploads/registrations/695a35892ae5d_front.jpg', 'uploads/registrations/695a35892cdba_back.jpg', NULL, '2026-01-04 09:40:25', '2026-01-04 09:40:25', 'unverified', 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `review_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `bh_id` int(11) NOT NULL,
  `rating` int(11) NOT NULL CHECK (`rating` between 1 and 5),
  `comment` text DEFAULT NULL,
  `review_created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reviews`
--

INSERT INTO `reviews` (`review_id`, `user_id`, `bh_id`, `rating`, `comment`, `review_created_at`) VALUES
(1, 35, 87, 5, 'Goodie', '2025-11-17 12:15:55'),
(2, 35, 87, 4, 'Jssksjksnsnxbxnxnxbxbxxnxjjzjzzbznnxnznznznzbszbznzkzkzmmzznznznznznznznznznznznznnzznznznznsjajnsnsjssbsbzbzznjzkznnxnxnxnxnxnxnxnxnxnnx', '2025-11-18 09:40:04'),
(3, 28, 87, 2, 'Gdjjfj', '2025-12-09 21:35:33');

-- --------------------------------------------------------

--
-- Table structure for table `room_images`
--

CREATE TABLE `room_images` (
  `image_id` int(11) NOT NULL,
  `bhr_id` int(11) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `room_images`
--

INSERT INTO `room_images` (`image_id`, `bhr_id`, `image_path`, `uploaded_at`) VALUES
(1, 10, 'uploads/room_images/bhr_10_68d262f445462.jpg', '2025-09-23 09:05:56'),
(2, 10, 'uploads/room_images/bhr_10_68d262fa15cca.jpg', '2025-09-23 09:06:02'),
(5, 12, 'uploads/room_images/bhr_12_68d264500d2e7.jpg', '2025-09-23 09:11:44'),
(6, 12, 'uploads/room_images/bhr_12_68d2645213f54.jpg', '2025-09-23 09:11:46'),
(7, 13, 'uploads/room_images/bhr_13_68d2663baa88a.jpg', '2025-09-23 09:19:55'),
(8, 13, 'uploads/room_images/bhr_13_68d26641199f1.jpg', '2025-09-23 09:20:01'),
(9, 14, 'uploads/room_images/bhr_14_68d267b01e555.jpg', '2025-09-23 09:26:08'),
(10, 14, 'uploads/room_images/bhr_14_68d267b584fc2.jpg', '2025-09-23 09:26:13'),
(11, 15, 'uploads/room_images/bhr_15_68d613d60c007.jpg', '2025-09-26 04:17:26'),
(12, 15, 'uploads/room_images/bhr_15_68d613d9984a3.jpg', '2025-09-26 04:17:29'),
(13, 16, 'uploads/room_images/bhr_16_68d7e2cf8821a.jpg', '2025-09-27 13:12:47'),
(14, 16, 'uploads/room_images/bhr_16_68d7e2d424728.jpg', '2025-09-27 13:12:52'),
(15, 17, 'uploads/room_images/bhr_17_68d7e6b19bf68.jpg', '2025-09-27 13:29:21'),
(16, 18, 'uploads/room_images/bhr_18_68d88c5857f0a.jpg', '2025-09-28 01:16:08'),
(17, 18, 'uploads/room_images/bhr_18_68d88c5a94ade.jpg', '2025-09-28 01:16:10'),
(18, 19, 'uploads/room_images/bhr_19_68d88d8c4c62d.jpg', '2025-09-28 01:21:16'),
(19, 20, 'uploads/room_images/bhr_20_68d8c0c487e68.jpg', '2025-09-28 04:59:48'),
(20, 21, 'uploads/room_images/bhr_21_68db38f23eced.jpg', '2025-09-30 01:57:06'),
(21, 24, 'uploads/room_images/bhr_24_68db4eebdb7b1.jpg', '2025-09-30 03:30:51'),
(22, 26, 'uploads/room_images/bhr_26_68db53067ef57.jpg', '2025-09-30 03:48:22'),
(23, 24, 'uploads/room_images/bhr_24_68db58a501697.jpg', '2025-09-30 04:12:21'),
(25, 25, 'uploads/room_images/bhr_25_68db58e79bcc0.jpg', '2025-09-30 04:13:27'),
(26, 28, 'uploads/room_images/bhr_28_68db5bb8a14a3.jpg', '2025-09-30 04:25:28'),
(27, 36, 'uploads/room_images/bhr_36_68db6395ce2b3.jpg', '2025-09-30 04:59:01'),
(28, 37, 'uploads/room_images/bhr_37_68db63dcb314b.jpg', '2025-09-30 05:00:12'),
(29, 38, 'uploads/room_images/bhr_38_68def900cbf5a.jpg', '2025-10-02 22:13:20'),
(30, 39, 'uploads/room_images/bhr_39_68def9665ec5e.jpg', '2025-10-02 22:15:02'),
(31, 40, 'uploads/room_images/bhr_40_68df1e48ad236.jpg', '2025-10-03 00:52:24'),
(32, 40, 'uploads/room_images/bhr_40_68df1e7dacc4c.jpg', '2025-10-03 00:53:17'),
(33, 41, 'uploads/room_images/bhr_41_68df1fb133f47.jpg', '2025-10-03 00:58:25'),
(34, 42, 'uploads/room_images/bhr_42_68df225230698.jpg', '2025-10-03 01:09:38'),
(35, 42, 'uploads/room_images/bhr_42_68df2255d4045.jpg', '2025-10-03 01:09:41'),
(36, 42, 'uploads/room_images/bhr_42_68df22590d022.jpg', '2025-10-03 01:09:45'),
(37, 24, 'uploads/room_images/bhr_24_68e0c3f4a1f17.jpg', '2025-10-04 06:51:33'),
(38, 43, 'uploads/room_images/bhr_43_68e1e2693b73e.jpg', '2025-10-05 03:13:45'),
(39, 43, 'uploads/room_images/bhr_43_68e1e348e5635.jpg', '2025-10-05 03:17:28'),
(40, 44, 'uploads/room_images/bhr_44_68e695f80e080.jpg', '2025-10-08 16:48:56'),
(41, 45, 'uploads/room_images/bhr_45_68e71e33d82fa.jpg', '2025-10-09 02:30:11'),
(42, 46, 'uploads/room_images/bhr_46_68eb253cb2a48.jpg', '2025-10-12 03:49:16'),
(43, 47, 'uploads/room_images/bhr_47_68eb268fd47c6.jpg', '2025-10-12 03:54:55'),
(44, 48, 'uploads/room_images/bhr_48_68fb212184fb8.jpg', '2025-10-24 06:48:01'),
(45, 48, 'uploads/room_images/bhr_48_68fb212431eec.jpg', '2025-10-24 06:48:04'),
(46, 49, 'uploads/room_images/bhr_49_690029cf04c1d.jpg', '2025-10-28 02:26:23'),
(47, 46, 'uploads/room_images/bhr_46_690d556f9c807.jpg', '2025-11-07 02:11:59'),
(48, 49, 'uploads/room_images/bhr_49_6933afd6595d9.jpg', '2025-12-06 04:23:50'),
(49, 50, 'uploads/room_images/bhr_50_69389372e6e11.jpg', '2025-12-09 21:24:02'),
(50, 51, 'uploads/room_images/bhr_51_693893809222c.jpg', '2025-12-09 21:24:16');

-- --------------------------------------------------------

--
-- Table structure for table `room_units`
--

CREATE TABLE `room_units` (
  `room_id` int(11) NOT NULL,
  `bhr_id` int(11) NOT NULL,
  `room_number` varchar(50) NOT NULL,
  `status` enum('Available','Occupied','Unavailable','Partially Occupied') NOT NULL DEFAULT 'Available'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `room_units`
--

INSERT INTO `room_units` (`room_id`, `bhr_id`, `room_number`, `status`) VALUES
(1, 4, 'SR-1', 'Available'),
(2, 4, 'SR-2', 'Available'),
(3, 4, 'SR-3', 'Available'),
(4, 5, 'SR-1', 'Available'),
(5, 5, 'SR-2', 'Available'),
(6, 5, 'SR-3', 'Available'),
(7, 6, 'D-1', 'Available'),
(8, 6, 'D-2', 'Available'),
(9, 6, 'D-3', 'Available'),
(10, 6, 'D-4', 'Available'),
(11, 7, 'S-1', 'Available'),
(12, 7, 'S-2', 'Available'),
(13, 7, 'S-3', 'Available'),
(14, 7, 'S-4', 'Available'),
(15, 8, 'S-1', 'Available'),
(16, 8, 'S-2', 'Available'),
(17, 8, 'S-3', 'Available'),
(18, 8, 'S-4', 'Available'),
(19, 9, 'GA-1', 'Available'),
(20, 9, 'GA-2', 'Available'),
(21, 9, 'GA-3', 'Available'),
(22, 9, 'GA-4', 'Available'),
(23, 9, 'GA-5', 'Available'),
(24, 10, 'S-1', 'Available'),
(26, 12, 'D-1', 'Available'),
(27, 13, 'D-1', 'Available'),
(28, 14, 'GB-1', 'Available'),
(29, 15, 'FR-1', 'Available'),
(30, 15, 'FR-2', 'Available'),
(31, 16, 'S-1', 'Available'),
(32, 16, 'S-2', 'Available'),
(33, 17, 'SR-1', 'Available'),
(34, 18, 'F-1', 'Available'),
(35, 18, 'F-2', 'Available'),
(36, 19, 'F-1', 'Available'),
(37, 20, 'GC-1', 'Available'),
(38, 21, 'S-1', 'Available'),
(39, 22, 'S-1', 'Available'),
(40, 23, 'S-1', 'Available'),
(41, 24, 'S-1', 'Occupied'),
(42, 25, 'GB-1', 'Available'),
(43, 26, 'F-1', 'Available'),
(45, 28, 'SA-1', 'Available'),
(46, 29, 'S-1', 'Available'),
(47, 33, 'S-1', 'Available'),
(48, 34, 'S-1', 'Available'),
(50, 36, 'S-1', 'Available'),
(51, 37, 'S-1', 'Available'),
(52, 28, 'SA-2', 'Available'),
(53, 24, 'SA-2', 'Available'),
(54, 24, 'SA-3', 'Occupied'),
(59, 38, 'SR-1', 'Available'),
(60, 38, 'SR-2', 'Available'),
(61, 39, 'G-1', 'Available'),
(62, 39, 'G-2', 'Available'),
(63, 40, 'KHAR-1', 'Occupied'),
(64, 40, 'KHAR-2', 'Available'),
(65, 40, 'KHAR-3', 'Available'),
(66, 40, 'KHAR-4', 'Available'),
(67, 40, 'KHAR-5', 'Occupied'),
(68, 40, 'KHAR-6', 'Available'),
(69, 40, 'KHAR-7', 'Available'),
(70, 40, 'KHAR-8', 'Available'),
(71, 40, 'KHAR-9', 'Available'),
(72, 40, 'KHAR-10', 'Available'),
(73, 40, 'KHAR-11', 'Occupied'),
(74, 40, 'KHAR-12', 'Available'),
(75, 41, 'SA-1', 'Available'),
(76, 42, 'FR-1', 'Available'),
(77, 42, 'FR-2', 'Available'),
(78, 43, 'S-1', 'Available'),
(79, 44, 'SA-1', 'Available'),
(80, 45, 'SA-1', 'Available'),
(81, 46, 'SA-1', 'Occupied'),
(82, 47, 'GA-1', 'Partially Occupied'),
(83, 48, 'R2-1', 'Available'),
(84, 47, 'GA-2', 'Available'),
(85, 49, 'PR0-1', 'Available'),
(86, 49, 'PR0-2', 'Occupied'),
(87, 49, 'PR0-3', 'Available'),
(88, 49, 'PR0-4', 'Available'),
(89, 49, 'PR0-5', 'Available'),
(90, 49, 'PR0-6', 'Occupied'),
(91, 49, 'PR0-7', 'Available'),
(92, 50, 'SR-1', 'Available'),
(93, 50, 'SR-2', 'Available'),
(94, 51, 'GA-1', 'Available'),
(95, 51, 'GA-2', 'Available');

-- --------------------------------------------------------

--
-- Table structure for table `support_tickets`
--

CREATE TABLE `support_tickets` (
  `ticket_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `st_subject` varchar(150) NOT NULL,
  `st_description` text NOT NULL,
  `st_status` enum('Pending','In Progress','Resolved','Closed') NOT NULL DEFAULT 'Pending',
  `st_created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `reg_id` int(11) NOT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `status` enum('Active','Inactive') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `reg_id`, `profile_picture`, `status`) VALUES
(1, 2, 'uploads/profile_pictures/owner_1_68df20de76361.jpg', 'Active'),
(2, 1, 'profile_john.jpg', 'Active'),
(4, 3, 'profile_mike.jpg', 'Active'),
(5, 4, 'profile_sarah.jpg', 'Active'),
(6, 5, 'profile_david.jpg', 'Active'),
(7, 6, 'profile_lisa.jpg', 'Active'),
(8, 7, 'profile_tom.jpg', 'Active'),
(23, 42, NULL, 'Active'),
(24, 35, NULL, 'Active'),
(25, 10, NULL, 'Active'),
(27, 31, NULL, 'Active'),
(28, 51, 'uploads/profile_pictures/user_28_691dc860174ad.jpg', 'Active'),
(29, 53, 'uploads/profile_pictures/user_29_690c8e63c984b.jpg', 'Active'),
(30, 74, NULL, 'Active'),
(31, 75, NULL, 'Active'),
(32, 76, NULL, 'Active'),
(33, 77, NULL, 'Active'),
(34, 78, NULL, 'Active'),
(35, 79, NULL, 'Active'),
(36, 84, NULL, 'Active'),
(37, 85, NULL, 'Active'),
(38, 86, NULL, 'Active'),
(39, 88, NULL, 'Active'),
(40, 89, NULL, 'Active'),
(41, 94, NULL, 'Active'),
(42, 100, NULL, 'Active'),
(43, 101, NULL, 'Active'),
(44, 103, NULL, 'Active'),
(45, 29, NULL, 'Active'),
(58, 8, NULL, 'Active'),
(59, 105, 'uploads/profile_pictures/user_59_6923c8c4bb2df.jpg', 'Active'),
(62, 108, NULL, 'Active'),
(63, 113, NULL, 'Active'),
(64, 123, NULL, 'Active');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `active_boarders`
--
ALTER TABLE `active_boarders`
  ADD PRIMARY KEY (`active_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `room_id` (`room_id`);

--
-- Indexes for table `admin_accounts`
--
ALTER TABLE `admin_accounts`
  ADD PRIMARY KEY (`admin_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `admin_activity_log`
--
ALTER TABLE `admin_activity_log`
  ADD PRIMARY KEY (`activity_id`),
  ADD KEY `idx_admin_id` (`admin_id`),
  ADD KEY `idx_activity_type` (`activity_type`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `boarder_favorites`
--
ALTER TABLE `boarder_favorites`
  ADD PRIMARY KEY (`fav_id`),
  ADD UNIQUE KEY `unique_favorite` (`user_id`,`bh_id`),
  ADD KEY `fk_user_reg` (`user_id`),
  ADD KEY `fk_bh` (`bh_id`);

--
-- Indexes for table `boarding_houses`
--
ALTER TABLE `boarding_houses`
  ADD PRIMARY KEY (`bh_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `boarding_house_images`
--
ALTER TABLE `boarding_house_images`
  ADD PRIMARY KEY (`image_id`),
  ADD KEY `bh_id` (`bh_id`);

--
-- Indexes for table `boarding_house_rooms`
--
ALTER TABLE `boarding_house_rooms`
  ADD PRIMARY KEY (`bhr_id`),
  ADD KEY `bh_id` (`bh_id`);

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`booking_id`),
  ADD KEY `room_id` (`room_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `bs_permits`
--
ALTER TABLE `bs_permits`
  ADD PRIMARY KEY (`permit_id`),
  ADD KEY `fk_reg_id` (`reg_id`),
  ADD KEY `idx_reg_permit` (`reg_id`,`permit_number`);

--
-- Indexes for table `chat_groups`
--
ALTER TABLE `chat_groups`
  ADD PRIMARY KEY (`gc_id`),
  ADD KEY `bh_id` (`bh_id`),
  ADD KEY `gc_created_by` (`gc_created_by`);

--
-- Indexes for table `device_tokens`
--
ALTER TABLE `device_tokens`
  ADD PRIMARY KEY (`token_id`),
  ADD UNIQUE KEY `unique_user_token` (`user_id`,`device_token`),
  ADD KEY `idx_user_active` (`user_id`,`is_active`),
  ADD KEY `idx_token` (`device_token`);

--
-- Indexes for table `email_verifications`
--
ALTER TABLE `email_verifications`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_verification` (`user_id`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_expiry` (`expiry_time`);

--
-- Indexes for table `group_members`
--
ALTER TABLE `group_members`
  ADD PRIMARY KEY (`gm_id`),
  ADD KEY `gc_id` (`gc_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `group_messages`
--
ALTER TABLE `group_messages`
  ADD PRIMARY KEY (`groupmessage_id`),
  ADD KEY `gc_id` (`gc_id`),
  ADD KEY `sender_id` (`sender_id`);

--
-- Indexes for table `maintenance_requests`
--
ALTER TABLE `maintenance_requests`
  ADD PRIMARY KEY (`request_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `room_id` (`room_id`),
  ADD KEY `idx_approved_at` (`mr_approved_at`),
  ADD KEY `idx_completed_at` (`mr_completed_at`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`message_id`),
  ADD KEY `sender_id` (`sender_id`),
  ADD KEY `receiver_id` (`receiver_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notif_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `notification_templates`
--
ALTER TABLE `notification_templates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `template_key` (`template_key`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_token` (`token`),
  ADD KEY `idx_expires_at` (`expires_at`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`payment_id`),
  ADD KEY `booking_id` (`booking_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `owner_id` (`owner_id`),
  ADD KEY `payment_status` (`payment_status`),
  ADD KEY `payment_date` (`payment_date`),
  ADD KEY `idx_payments_user_owner` (`user_id`,`owner_id`),
  ADD KEY `idx_payments_status_date` (`payment_status`,`payment_date`),
  ADD KEY `idx_payments_method` (`payment_method`),
  ADD KEY `idx_payments_monthly_tracking` (`user_id`,`payment_status`),
  ADD KEY `idx_payments_owner_month` (`owner_id`,`payment_status`);

--
-- Indexes for table `payment_breakdowns`
--
ALTER TABLE `payment_breakdowns`
  ADD PRIMARY KEY (`breakdown_id`),
  ADD KEY `booking_id` (`booking_id`),
  ADD KEY `payment_id` (`payment_id`),
  ADD KEY `is_selected` (`is_selected`),
  ADD KEY `is_paid` (`is_paid`),
  ADD KEY `payment_status` (`payment_status`),
  ADD KEY `due_date` (`due_date`),
  ADD KEY `idx_booking_selected` (`booking_id`,`is_selected`),
  ADD KEY `idx_booking_paid` (`booking_id`,`is_paid`),
  ADD KEY `idx_payment_status_due` (`payment_status`,`due_date`),
  ADD KEY `idx_admin_dashboard` (`payment_status`,`due_date`,`is_selected`,`is_paid`);

--
-- Indexes for table `payment_reminder_logs`
--
ALTER TABLE `payment_reminder_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD UNIQUE KEY `unique_reminder` (`breakdown_id`,`reminder_type`,`reminder_date`),
  ADD KEY `breakdown_id` (`breakdown_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `reminder_date` (`reminder_date`),
  ADD KEY `due_date` (`due_date`);

--
-- Indexes for table `registration`
--
ALTER TABLE `registration`
  ADD PRIMARY KEY (`reg_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `registrations`
--
ALTER TABLE `registrations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_email` (`email`),
  ADD KEY `idx_role` (`role`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_status_created` (`status`,`created_at`),
  ADD KEY `idx_suffix` (`suffix`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`review_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `bh_id` (`bh_id`);

--
-- Indexes for table `room_images`
--
ALTER TABLE `room_images`
  ADD PRIMARY KEY (`image_id`),
  ADD KEY `bhr_id` (`bhr_id`);

--
-- Indexes for table `room_units`
--
ALTER TABLE `room_units`
  ADD PRIMARY KEY (`room_id`),
  ADD KEY `bhr_id` (`bhr_id`);

--
-- Indexes for table `support_tickets`
--
ALTER TABLE `support_tickets`
  ADD PRIMARY KEY (`ticket_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD KEY `reg_id` (`reg_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `active_boarders`
--
ALTER TABLE `active_boarders`
  MODIFY `active_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT for table `admin_accounts`
--
ALTER TABLE `admin_accounts`
  MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `admin_activity_log`
--
ALTER TABLE `admin_activity_log`
  MODIFY `activity_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=126;

--
-- AUTO_INCREMENT for table `boarder_favorites`
--
ALTER TABLE `boarder_favorites`
  MODIFY `fav_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT for table `boarding_houses`
--
ALTER TABLE `boarding_houses`
  MODIFY `bh_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=89;

--
-- AUTO_INCREMENT for table `boarding_house_images`
--
ALTER TABLE `boarding_house_images`
  MODIFY `image_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=90;

--
-- AUTO_INCREMENT for table `boarding_house_rooms`
--
ALTER TABLE `boarding_house_rooms`
  MODIFY `bhr_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=52;

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `booking_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=59;

--
-- AUTO_INCREMENT for table `bs_permits`
--
ALTER TABLE `bs_permits`
  MODIFY `permit_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `chat_groups`
--
ALTER TABLE `chat_groups`
  MODIFY `gc_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `device_tokens`
--
ALTER TABLE `device_tokens`
  MODIFY `token_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=68;

--
-- AUTO_INCREMENT for table `email_verifications`
--
ALTER TABLE `email_verifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=100;

--
-- AUTO_INCREMENT for table `group_members`
--
ALTER TABLE `group_members`
  MODIFY `gm_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `group_messages`
--
ALTER TABLE `group_messages`
  MODIFY `groupmessage_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT for table `maintenance_requests`
--
ALTER TABLE `maintenance_requests`
  MODIFY `request_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `message_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=355;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notif_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=924;

--
-- AUTO_INCREMENT for table `notification_templates`
--
ALTER TABLE `notification_templates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=201;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=69;

--
-- AUTO_INCREMENT for table `payment_breakdowns`
--
ALTER TABLE `payment_breakdowns`
  MODIFY `breakdown_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=71;

--
-- AUTO_INCREMENT for table `payment_reminder_logs`
--
ALTER TABLE `payment_reminder_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `registration`
--
ALTER TABLE `registration`
  MODIFY `reg_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=146;

--
-- AUTO_INCREMENT for table `registrations`
--
ALTER TABLE `registrations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=126;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `review_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `room_images`
--
ALTER TABLE `room_images`
  MODIFY `image_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT for table `room_units`
--
ALTER TABLE `room_units`
  MODIFY `room_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=96;

--
-- AUTO_INCREMENT for table `support_tickets`
--
ALTER TABLE `support_tickets`
  MODIFY `ticket_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=65;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `active_boarders`
--
ALTER TABLE `active_boarders`
  ADD CONSTRAINT `active_boarders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `active_boarders_ibfk_2` FOREIGN KEY (`room_id`) REFERENCES `room_units` (`room_id`);

--
-- Constraints for table `admin_activity_log`
--
ALTER TABLE `admin_activity_log`
  ADD CONSTRAINT `admin_activity_log_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `admin_accounts` (`admin_id`) ON DELETE CASCADE;

--
-- Constraints for table `boarder_favorites`
--
ALTER TABLE `boarder_favorites`
  ADD CONSTRAINT `fk_bh_favorites` FOREIGN KEY (`bh_id`) REFERENCES `boarding_houses` (`bh_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_user_reg_favorites` FOREIGN KEY (`user_id`) REFERENCES `registrations` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `boarding_houses`
--
ALTER TABLE `boarding_houses`
  ADD CONSTRAINT `boarding_houses_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `boarding_house_images`
--
ALTER TABLE `boarding_house_images`
  ADD CONSTRAINT `boarding_house_images_ibfk_1` FOREIGN KEY (`bh_id`) REFERENCES `boarding_houses` (`bh_id`) ON DELETE CASCADE;

--
-- Constraints for table `boarding_house_rooms`
--
ALTER TABLE `boarding_house_rooms`
  ADD CONSTRAINT `boarding_house_rooms_ibfk_1` FOREIGN KEY (`bh_id`) REFERENCES `boarding_houses` (`bh_id`) ON DELETE CASCADE;

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`room_id`) REFERENCES `room_units` (`room_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `bs_permits`
--
ALTER TABLE `bs_permits`
  ADD CONSTRAINT `fk_bs_permits_registration` FOREIGN KEY (`reg_id`) REFERENCES `registrations` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `chat_groups`
--
ALTER TABLE `chat_groups`
  ADD CONSTRAINT `chat_groups_ibfk_1` FOREIGN KEY (`bh_id`) REFERENCES `boarding_houses` (`bh_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `chat_groups_ibfk_2` FOREIGN KEY (`gc_created_by`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `email_verifications`
--
ALTER TABLE `email_verifications`
  ADD CONSTRAINT `email_verifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `registrations` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `group_members`
--
ALTER TABLE `group_members`
  ADD CONSTRAINT `group_members_ibfk_1` FOREIGN KEY (`gc_id`) REFERENCES `chat_groups` (`gc_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `group_members_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `group_messages`
--
ALTER TABLE `group_messages`
  ADD CONSTRAINT `group_messages_ibfk_1` FOREIGN KEY (`gc_id`) REFERENCES `chat_groups` (`gc_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `group_messages_ibfk_2` FOREIGN KEY (`sender_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `maintenance_requests`
--
ALTER TABLE `maintenance_requests`
  ADD CONSTRAINT `fk_maintenance_room` FOREIGN KEY (`room_id`) REFERENCES `room_units` (`room_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `maintenance_requests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`sender_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_3` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payments_ibfk_4` FOREIGN KEY (`owner_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `payment_breakdowns`
--
ALTER TABLE `payment_breakdowns`
  ADD CONSTRAINT `fk_breakdown_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_breakdown_payment` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`payment_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`bh_id`) REFERENCES `boarding_houses` (`bh_id`) ON DELETE CASCADE;

--
-- Constraints for table `room_images`
--
ALTER TABLE `room_images`
  ADD CONSTRAINT `room_images_ibfk_1` FOREIGN KEY (`bhr_id`) REFERENCES `boarding_house_rooms` (`bhr_id`) ON DELETE CASCADE;

--
-- Constraints for table `room_units`
--
ALTER TABLE `room_units`
  ADD CONSTRAINT `room_units_ibfk_1` FOREIGN KEY (`bhr_id`) REFERENCES `boarding_house_rooms` (`bhr_id`) ON DELETE CASCADE;

--
-- Constraints for table `support_tickets`
--
ALTER TABLE `support_tickets`
  ADD CONSTRAINT `support_tickets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
