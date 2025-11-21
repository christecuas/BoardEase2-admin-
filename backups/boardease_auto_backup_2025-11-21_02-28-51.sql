-- Database Backup
-- Generated on: 2025-11-21 02:28:51
-- Database: boardease2

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `active_boarders`
--

DROP TABLE IF EXISTS `active_boarders`;
CREATE TABLE `active_boarders` (
  `active_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `status` enum('Active','Inactive') NOT NULL,
  `room_id` int(11) DEFAULT NULL,
  `boarding_house_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`active_id`),
  KEY `user_id` (`user_id`),
  KEY `room_id` (`room_id`),
  KEY `boarding_house_id` (`boarding_house_id`),
  CONSTRAINT `active_boarders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  CONSTRAINT `active_boarders_ibfk_2` FOREIGN KEY (`room_id`) REFERENCES `room_units` (`room_id`),
  CONSTRAINT `active_boarders_ibfk_3` FOREIGN KEY (`boarding_house_id`) REFERENCES `boarding_houses` (`bh_id`)
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `active_boarders`
--

LOCK TABLES `active_boarders` WRITE;
/*!40000 ALTER TABLE `active_boarders` DISABLE KEYS */;
INSERT INTO `active_boarders` VALUES ('11','59','Active','83','85');
INSERT INTO `active_boarders` VALUES ('12','44','Active','82','85');
INSERT INTO `active_boarders` VALUES ('17','28','Active','86','87');
INSERT INTO `active_boarders` VALUES ('29','38','Active','90','87');
INSERT INTO `active_boarders` VALUES ('32','38','Active','85','87');
/*!40000 ALTER TABLE `active_boarders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `admin_accounts`
--

DROP TABLE IF EXISTS `admin_accounts`;
CREATE TABLE `admin_accounts` (
  `admin_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('super_admin') DEFAULT 'super_admin',
  `status` enum('active','inactive') DEFAULT 'active',
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`admin_id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_accounts`
--

LOCK TABLES `admin_accounts` WRITE;
/*!40000 ALTER TABLE `admin_accounts` DISABLE KEYS */;
INSERT INTO `admin_accounts` VALUES ('1','Super Admin','admin@boardease.com','$2y$10$5sSPAwaECIF2WfiqJQa26uP6VM86cfEJ/52xVAdL0GaYDk60eBiuu','super_admin','active','2025-11-20 19:39:47','2025-10-25 15:13:20','2025-11-20 19:39:47');
INSERT INTO `admin_accounts` VALUES ('2','Your Partner','partner@boardease.com','$2y$10$5sSPAwaECIF2WfiqJQa26uP6VM86cfEJ/52xVAdL0GaYDk60eBiuu','super_admin','active',NULL,'2025-10-25 15:13:20','2025-11-15 14:14:43');
/*!40000 ALTER TABLE `admin_accounts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `admin_activity_log`
--

DROP TABLE IF EXISTS `admin_activity_log`;
CREATE TABLE `admin_activity_log` (
  `activity_id` int(11) NOT NULL AUTO_INCREMENT,
  `admin_id` int(11) NOT NULL,
  `activity_type` enum('login','logout','password_change','email_change','status_change','user_approved','user_rejected','user_created','user_updated','user_deleted','system_change','other') DEFAULT 'other',
  `activity_title` varchar(255) NOT NULL,
  `activity_description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`activity_id`),
  KEY `idx_admin_id` (`admin_id`),
  KEY `idx_activity_type` (`activity_type`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `admin_activity_log_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `admin_accounts` (`admin_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_activity_log`
--

LOCK TABLES `admin_activity_log` WRITE;
/*!40000 ALTER TABLE `admin_activity_log` DISABLE KEYS */;
INSERT INTO `admin_activity_log` VALUES ('1','1','logout','Super Admin logged out','Admin logout successful','192.168.101.7','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0','2025-11-15 13:51:45');
INSERT INTO `admin_activity_log` VALUES ('2','1','login','Super Admin logged in','Admin login successful from 192.168.101.7','192.168.101.7','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0','2025-11-15 13:51:51');
INSERT INTO `admin_activity_log` VALUES ('3','1','status_change','Account Status: Active - Your Partner','Admin account activated: Admin ID 2, Name: Your Partner, Email: partner@boardease.com, Previous Status: Inactive','192.168.101.7','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0','2025-11-15 14:14:43');
INSERT INTO `admin_activity_log` VALUES ('4','1','logout','Super Admin logged out','Admin logout successful','192.168.101.7','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0','2025-11-15 14:15:14');
INSERT INTO `admin_activity_log` VALUES ('5','1','login','Super Admin logged in','Admin login successful from 192.168.101.7','192.168.101.7','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0','2025-11-15 14:15:22');
INSERT INTO `admin_activity_log` VALUES ('6','1','logout','Super Admin logged out','Admin logout successful','192.168.101.7','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0','2025-11-15 14:48:40');
INSERT INTO `admin_activity_log` VALUES ('7','1','login','Super Admin logged in','Admin login successful from 192.168.101.7','192.168.101.7','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0','2025-11-15 14:52:34');
INSERT INTO `admin_activity_log` VALUES ('8','1','logout','Super Admin logged out','Admin logout successful','192.168.101.7','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0','2025-11-15 22:14:27');
INSERT INTO `admin_activity_log` VALUES ('9','1','login','Super Admin logged in','Admin login successful from 192.168.101.7','192.168.101.7','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0','2025-11-17 20:51:16');
INSERT INTO `admin_activity_log` VALUES ('10','1','login','Super Admin logged in','Admin login successful from 192.168.101.7','192.168.101.7','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0','2025-11-18 11:18:28');
INSERT INTO `admin_activity_log` VALUES ('11','1','login','Super Admin logged in','Admin login successful from 192.168.101.7','192.168.101.7','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0','2025-11-18 18:41:12');
INSERT INTO `admin_activity_log` VALUES ('12','1','login','Super Admin logged in','Admin login successful from 192.168.101.7','192.168.101.7','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0','2025-11-19 10:28:22');
INSERT INTO `admin_activity_log` VALUES ('13','1','user_approved','User registration approved: Christe Hanna Mae  Cuas','Registration ID: 113, Email: christehannamae.cuas@bisu.edu.ph, Role: ','192.168.101.7','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0','2025-11-19 10:28:54');
INSERT INTO `admin_activity_log` VALUES ('14','1','login','Super Admin logged in','Admin login successful from 192.168.101.7','192.168.101.7','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0','2025-11-19 22:03:54');
INSERT INTO `admin_activity_log` VALUES ('15','1','login','Super Admin logged in','Admin login successful from 192.168.137.96','192.168.137.96','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0','2025-11-20 12:51:48');
INSERT INTO `admin_activity_log` VALUES ('16','1','login','Super Admin logged in','Admin login successful from ::1','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0','2025-11-20 12:52:57');
INSERT INTO `admin_activity_log` VALUES ('17','1','login','Super Admin logged in','Admin login successful from 192.168.43.246','192.168.43.246','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0','2025-11-20 12:57:01');
INSERT INTO `admin_activity_log` VALUES ('18','1','login','Super Admin logged in','Admin login successful from 192.168.137.242','192.168.137.242','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0','2025-11-20 13:19:31');
INSERT INTO `admin_activity_log` VALUES ('19','1','login','Super Admin logged in','Admin login successful from 192.168.101.7','192.168.101.7','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0','2025-11-20 19:39:47');
/*!40000 ALTER TABLE `admin_activity_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `announcements`
--

DROP TABLE IF EXISTS `announcements`;
CREATE TABLE `announcements` (
  `announcement_id` int(11) NOT NULL AUTO_INCREMENT,
  `bh_id` int(11) NOT NULL,
  `an_title` varchar(150) NOT NULL,
  `an_content` text NOT NULL,
  `posted_by` int(11) NOT NULL,
  `an_created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`announcement_id`),
  KEY `bh_id` (`bh_id`),
  KEY `posted_by` (`posted_by`),
  CONSTRAINT `announcements_ibfk_1` FOREIGN KEY (`bh_id`) REFERENCES `boarding_houses` (`bh_id`) ON DELETE CASCADE,
  CONSTRAINT `announcements_ibfk_2` FOREIGN KEY (`posted_by`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `announcements`
--

--
-- Table structure for table `bills`
--

DROP TABLE IF EXISTS `bills`;
CREATE TABLE `bills` (
  `bill_id` int(11) NOT NULL AUTO_INCREMENT,
  `active_id` int(11) NOT NULL,
  `amount_due` double(10,2) NOT NULL,
  `due_date` date NOT NULL,
  `status` enum('Unpaid','Paid','Overdue') NOT NULL DEFAULT 'Unpaid',
  `payment_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`bill_id`),
  KEY `active_id` (`active_id`),
  KEY `payment_id` (`payment_id`),
  CONSTRAINT `bills_ibfk_1` FOREIGN KEY (`active_id`) REFERENCES `active_boarders` (`active_id`) ON DELETE CASCADE,
  CONSTRAINT `bills_ibfk_2` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`payment_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bills`
--

--
-- Table structure for table `boarder_favorites`
--

DROP TABLE IF EXISTS `boarder_favorites`;
CREATE TABLE `boarder_favorites` (
  `fav_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL COMMENT 'References registrations.id (not users.user_id)',
  `bh_id` int(11) NOT NULL COMMENT 'References boarding_houses.bh_id',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`fav_id`),
  UNIQUE KEY `unique_favorite` (`user_id`,`bh_id`),
  KEY `fk_user_reg` (`user_id`),
  KEY `fk_bh` (`bh_id`),
  CONSTRAINT `fk_bh_favorites` FOREIGN KEY (`bh_id`) REFERENCES `boarding_houses` (`bh_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_user_reg_favorites` FOREIGN KEY (`user_id`) REFERENCES `registrations` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `boarder_favorites`
--

LOCK TABLES `boarder_favorites` WRITE;
/*!40000 ALTER TABLE `boarder_favorites` DISABLE KEYS */;
INSERT INTO `boarder_favorites` VALUES ('6','51','87','2025-11-08 12:07:25');
INSERT INTO `boarder_favorites` VALUES ('16','103','85','2025-11-08 16:11:31');
INSERT INTO `boarder_favorites` VALUES ('17','103','87','2025-11-08 16:55:40');
INSERT INTO `boarder_favorites` VALUES ('18','51','11','2025-11-10 13:52:21');
INSERT INTO `boarder_favorites` VALUES ('19','51','12','2025-11-13 13:45:27');
/*!40000 ALTER TABLE `boarder_favorites` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `boarding_house_images`
--

DROP TABLE IF EXISTS `boarding_house_images`;
CREATE TABLE `boarding_house_images` (
  `image_id` int(11) NOT NULL AUTO_INCREMENT,
  `bh_id` int(11) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`image_id`),
  KEY `bh_id` (`bh_id`),
  CONSTRAINT `boarding_house_images_ibfk_1` FOREIGN KEY (`bh_id`) REFERENCES `boarding_houses` (`bh_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=87 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `boarding_house_images`
--

LOCK TABLES `boarding_house_images` WRITE;
/*!40000 ALTER TABLE `boarding_house_images` DISABLE KEYS */;
INSERT INTO `boarding_house_images` VALUES ('1','23','uploads/boarding_house_images/bh_23_68d24f780fd2f.jpg','2025-09-23 15:42:48');
INSERT INTO `boarding_house_images` VALUES ('2','40','uploads/boarding_house_images/bh_40_68d2558daaa50.jpg','2025-09-23 16:08:45');
INSERT INTO `boarding_house_images` VALUES ('3','40','uploads/boarding_house_images/bh_40_68d25592d75ea.jpg','2025-09-23 16:08:50');
INSERT INTO `boarding_house_images` VALUES ('4','41','uploads/boarding_house_images/bh_41_68d25596a90f6.jpg','2025-09-23 16:08:54');
INSERT INTO `boarding_house_images` VALUES ('5','41','uploads/boarding_house_images/bh_41_68d2559b9c9e4.jpg','2025-09-23 16:08:59');
INSERT INTO `boarding_house_images` VALUES ('6','42','uploads/boarding_house_images/bh_42_68d256071445a.jpg','2025-09-23 16:10:47');
INSERT INTO `boarding_house_images` VALUES ('7','43','uploads/boarding_house_images/bh_43_68d259096cc4d.jpg','2025-09-23 16:23:37');
INSERT INTO `boarding_house_images` VALUES ('8','43','uploads/boarding_house_images/bh_43_68d25910057e4.jpg','2025-09-23 16:23:44');
INSERT INTO `boarding_house_images` VALUES ('9','44','uploads/boarding_house_images/bh_44_68d259aa3260b.jpg','2025-09-23 16:26:18');
INSERT INTO `boarding_house_images` VALUES ('10','44','uploads/boarding_house_images/bh_44_68d259af8b8a6.jpg','2025-09-23 16:26:23');
INSERT INTO `boarding_house_images` VALUES ('11','45','uploads/boarding_house_images/bh_45_68d25aebdf439.jpg','2025-09-23 16:31:39');
INSERT INTO `boarding_house_images` VALUES ('12','45','uploads/boarding_house_images/bh_45_68d25af178cac.jpg','2025-09-23 16:31:45');
INSERT INTO `boarding_house_images` VALUES ('13','46','uploads/boarding_house_images/bh_46_68d260349584a.jpg','2025-09-23 16:54:12');
INSERT INTO `boarding_house_images` VALUES ('14','46','uploads/boarding_house_images/bh_46_68d2603a8c884.jpg','2025-09-23 16:54:18');
INSERT INTO `boarding_house_images` VALUES ('15','46','uploads/boarding_house_images/bh_46_68d2604037c39.jpg','2025-09-23 16:54:24');
INSERT INTO `boarding_house_images` VALUES ('16','46','uploads/boarding_house_images/bh_46_68d26045d49b6.jpg','2025-09-23 16:54:29');
INSERT INTO `boarding_house_images` VALUES ('17','47','uploads/boarding_house_images/bh_47_68d26062d57a4.jpg','2025-09-23 16:54:58');
INSERT INTO `boarding_house_images` VALUES ('18','47','uploads/boarding_house_images/bh_47_68d2606820154.jpg','2025-09-23 16:55:04');
INSERT INTO `boarding_house_images` VALUES ('19','47','uploads/boarding_house_images/bh_47_68d2606ed2535.jpg','2025-09-23 16:55:10');
INSERT INTO `boarding_house_images` VALUES ('20','47','uploads/boarding_house_images/bh_47_68d2607457902.jpg','2025-09-23 16:55:16');
INSERT INTO `boarding_house_images` VALUES ('21','48','uploads/boarding_house_images/bh_48_68d260f53b0ab.jpg','2025-09-23 16:57:25');
INSERT INTO `boarding_house_images` VALUES ('22','48','uploads/boarding_house_images/bh_48_68d260fb671b6.jpg','2025-09-23 16:57:31');
INSERT INTO `boarding_house_images` VALUES ('23','48','uploads/boarding_house_images/bh_48_68d26101d45d8.jpg','2025-09-23 16:57:37');
INSERT INTO `boarding_house_images` VALUES ('24','48','uploads/boarding_house_images/bh_48_68d2610d8f72c.jpg','2025-09-23 16:57:49');
INSERT INTO `boarding_house_images` VALUES ('25','49','uploads/boarding_house_images/bh_49_68d261f95b0e5.jpg','2025-09-23 17:01:45');
INSERT INTO `boarding_house_images` VALUES ('26','49','uploads/boarding_house_images/bh_49_68d261ff47bad.jpg','2025-09-23 17:01:51');
INSERT INTO `boarding_house_images` VALUES ('27','53','uploads/boarding_house_images/bh_53_68d2662b1ba04.jpg','2025-09-23 17:19:39');
INSERT INTO `boarding_house_images` VALUES ('28','53','uploads/boarding_house_images/bh_53_68d2663361e30.jpg','2025-09-23 17:19:47');
INSERT INTO `boarding_house_images` VALUES ('29','54','uploads/boarding_house_images/bh_54_68d267a205cc3.jpg','2025-09-23 17:25:54');
INSERT INTO `boarding_house_images` VALUES ('30','54','uploads/boarding_house_images/bh_54_68d267a77adc3.jpg','2025-09-23 17:25:59');
INSERT INTO `boarding_house_images` VALUES ('31','55','uploads/boarding_house_images/bh_55_68d613cd96fbf.jpg','2025-09-26 12:17:17');
INSERT INTO `boarding_house_images` VALUES ('32','55','uploads/boarding_house_images/bh_55_68d613d046db3.jpg','2025-09-26 12:17:20');
INSERT INTO `boarding_house_images` VALUES ('33','56','uploads/boarding_house_images/bh_56_68d7e2c316bf5.jpg','2025-09-27 21:12:35');
INSERT INTO `boarding_house_images` VALUES ('34','56','uploads/boarding_house_images/bh_56_68d7e2c812370.jpg','2025-09-27 21:12:40');
INSERT INTO `boarding_house_images` VALUES ('35','59','uploads/boarding_house_images/bh_59_68d88d82ab3aa.jpg','2025-09-28 09:21:06');
INSERT INTO `boarding_house_images` VALUES ('36','59','uploads/boarding_house_images/bh_59_68d88d8503f68.jpg','2025-09-28 09:21:09');
INSERT INTO `boarding_house_images` VALUES ('37','59','uploads/boarding_house_images/bh_59_68d88d8781469.jpg','2025-09-28 09:21:11');
INSERT INTO `boarding_house_images` VALUES ('38','60','uploads/boarding_house_images/bh_60_68d8c0e6752c0.jpg','2025-09-28 13:00:22');
INSERT INTO `boarding_house_images` VALUES ('41','11','uploads/boarding_house_images/bh_11_68d8c1ed07598.jpg','2025-09-28 13:04:45');
INSERT INTO `boarding_house_images` VALUES ('42','11','uploads/boarding_house_images/bh_11_68da7ed55e253.jpg','2025-09-29 20:43:01');
INSERT INTO `boarding_house_images` VALUES ('44','12','uploads/boarding_house_images/bh_12_68da7fa24259f.jpg','2025-09-29 20:46:26');
INSERT INTO `boarding_house_images` VALUES ('45','12','uploads/boarding_house_images/bh_12_68da7fa64a9fc.jpg','2025-09-29 20:46:30');
INSERT INTO `boarding_house_images` VALUES ('46','12','uploads/boarding_house_images/bh_12_68da7facc64f8.jpg','2025-09-29 20:46:36');
INSERT INTO `boarding_house_images` VALUES ('47','12','uploads/boarding_house_images/bh_12_68da7fad6dd0f.jpg','2025-09-29 20:46:37');
INSERT INTO `boarding_house_images` VALUES ('48','12','uploads/boarding_house_images/bh_12_68da7fb054e3a.jpg','2025-09-29 20:46:40');
INSERT INTO `boarding_house_images` VALUES ('49','12','uploads/boarding_house_images/bh_12_68da7fb2b9586.jpg','2025-09-29 20:46:42');
INSERT INTO `boarding_house_images` VALUES ('50','13','uploads/boarding_house_images/bh_13_68da81d496477.jpg','2025-09-29 20:55:48');
INSERT INTO `boarding_house_images` VALUES ('51','13','uploads/boarding_house_images/bh_13_68da81d722967.jpg','2025-09-29 20:55:51');
INSERT INTO `boarding_house_images` VALUES ('52','13','uploads/boarding_house_images/bh_13_68da81d9d8b05.jpg','2025-09-29 20:55:53');
INSERT INTO `boarding_house_images` VALUES ('53','14','uploads/boarding_house_images/bh_14_68da835705d66.jpg','2025-09-29 21:02:15');
INSERT INTO `boarding_house_images` VALUES ('54','14','uploads/boarding_house_images/bh_14_68da8359e7824.jpg','2025-09-29 21:02:17');
INSERT INTO `boarding_house_images` VALUES ('55','12','uploads/boarding_house_images/bh_12_68da8624153b9.jpg','2025-09-29 21:14:12');
INSERT INTO `boarding_house_images` VALUES ('56','15','uploads/boarding_house_images/bh_15_68da872fb1706.jpg','2025-09-29 21:18:39');
INSERT INTO `boarding_house_images` VALUES ('59','16','uploads/boarding_house_images/bh_16_68da8f356d75c.jpg','2025-09-29 21:52:53');
INSERT INTO `boarding_house_images` VALUES ('60','16','uploads/boarding_house_images/bh_16_68da8f37f1d74.jpg','2025-09-29 21:52:56');
INSERT INTO `boarding_house_images` VALUES ('61','22','uploads/boarding_house_images/bh_22_68da9155827f3.jpg','2025-09-29 22:01:57');
INSERT INTO `boarding_house_images` VALUES ('62','18','uploads/boarding_house_images/bh_18_68da98871b131.jpg','2025-09-29 22:32:39');
INSERT INTO `boarding_house_images` VALUES ('63','61','uploads/boarding_house_images/bh_61_68db3478b3e34.jpg','2025-09-30 09:38:00');
INSERT INTO `boarding_house_images` VALUES ('64','61','uploads/boarding_house_images/bh_61_68db347d5d74e.jpg','2025-09-30 09:38:05');
INSERT INTO `boarding_house_images` VALUES ('67','61','uploads/boarding_house_images/bh_61_68db34c4a8539.jpg','2025-09-30 09:39:16');
INSERT INTO `boarding_house_images` VALUES ('68','63','uploads/boarding_house_images/bh_63_68db38ecd65ae.jpg','2025-09-30 09:57:00');
INSERT INTO `boarding_house_images` VALUES ('69','64','uploads/boarding_house_images/bh_64_68db3c99e7d43.jpg','2025-09-30 10:12:41');
INSERT INTO `boarding_house_images` VALUES ('70','65','uploads/boarding_house_images/bh_65_68db3cf7b3a74.jpg','2025-09-30 10:14:15');
INSERT INTO `boarding_house_images` VALUES ('71','65','uploads/boarding_house_images/bh_65_68db3d259544f.jpg','2025-09-30 10:15:01');
INSERT INTO `boarding_house_images` VALUES ('72','72','uploads/boarding_house_images/bh_72_68def8fc1263f.jpg','2025-10-03 06:13:16');
INSERT INTO `boarding_house_images` VALUES ('73','73','uploads/boarding_house_images/bh_73_68df224bdd350.jpg','2025-10-03 09:09:31');
INSERT INTO `boarding_house_images` VALUES ('75','77','uploads/boarding_house_images/bh_77_68e1e2f8c0ac6.jpg','2025-10-05 11:16:08');
INSERT INTO `boarding_house_images` VALUES ('76','77','uploads/boarding_house_images/bh_77_68e1e4231be7b.jpg','2025-10-05 11:21:07');
INSERT INTO `boarding_house_images` VALUES ('77','78','uploads/boarding_house_images/bh_78_68e695df04939.jpg','2025-10-09 00:48:31');
INSERT INTO `boarding_house_images` VALUES ('78','78','uploads/boarding_house_images/bh_78_68e695f66b119.jpg','2025-10-09 00:48:54');
INSERT INTO `boarding_house_images` VALUES ('79','84','uploads/boarding_house_images/bh_84_68e71e2e738ab.jpg','2025-10-09 10:30:06');
INSERT INTO `boarding_house_images` VALUES ('80','85','uploads/boarding_house_images/bh_85_68eb25319895f.jpg','2025-10-12 11:49:05');
INSERT INTO `boarding_house_images` VALUES ('81','85','uploads/boarding_house_images/bh_85_68eb286b32357.jpg','2025-10-12 12:02:51');
INSERT INTO `boarding_house_images` VALUES ('82','87','uploads/boarding_house_images/bh_87_690029c015372.jpg','2025-10-28 10:26:08');
INSERT INTO `boarding_house_images` VALUES ('83','85','uploads/boarding_house_images/bh_85_690c83efa8140.jpg','2025-11-06 19:18:07');
INSERT INTO `boarding_house_images` VALUES ('84','85','uploads/boarding_house_images/bh_85_690c83f66a30d.jpg','2025-11-06 19:18:14');
INSERT INTO `boarding_house_images` VALUES ('85','85','uploads/boarding_house_images/bh_85_690c83fc1ad50.jpg','2025-11-06 19:18:20');
INSERT INTO `boarding_house_images` VALUES ('86','85','uploads/boarding_house_images/bh_85_690c84067b84f.jpg','2025-11-06 19:18:30');
/*!40000 ALTER TABLE `boarding_house_images` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `boarding_house_rooms`
--

DROP TABLE IF EXISTS `boarding_house_rooms`;
CREATE TABLE `boarding_house_rooms` (
  `bhr_id` int(11) NOT NULL AUTO_INCREMENT,
  `bh_id` int(11) NOT NULL,
  `room_category` enum('Private Room','Bed Spacer') NOT NULL,
  `room_name` varchar(100) NOT NULL,
  `price` double(10,2) NOT NULL,
  `capacity` int(11) NOT NULL,
  `room_description` text DEFAULT NULL,
  `total_rooms` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`bhr_id`),
  KEY `bh_id` (`bh_id`),
  CONSTRAINT `boarding_house_rooms_ibfk_1` FOREIGN KEY (`bh_id`) REFERENCES `boarding_houses` (`bh_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=50 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `boarding_house_rooms`
--

LOCK TABLES `boarding_house_rooms` WRITE;
/*!40000 ALTER TABLE `boarding_house_rooms` DISABLE KEYS */;
INSERT INTO `boarding_house_rooms` VALUES ('1','41','Private Room','Single Room','5000.00','2','0','3','2025-09-23 16:09:01');
INSERT INTO `boarding_house_rooms` VALUES ('2','42','Private Room','Single Room','5000.00','3','0','4','2025-09-23 16:10:49');
INSERT INTO `boarding_house_rooms` VALUES ('3','43','Private Room','Single Room','4000.00','2','0','3','2025-09-23 16:23:46');
INSERT INTO `boarding_house_rooms` VALUES ('4','44','Private Room','Single Room','4000.00','3','0','3','2025-09-23 16:26:25');
INSERT INTO `boarding_house_rooms` VALUES ('5','45','Private Room','Single Room','4000.00','3','0','3','2025-09-23 16:31:47');
INSERT INTO `boarding_house_rooms` VALUES ('6','46','Private Room','Double','10000.00','5','0','4','2025-09-23 16:54:31');
INSERT INTO `boarding_house_rooms` VALUES ('7','47','Private Room','Single','10000.00','5','0','4','2025-09-23 16:55:18');
INSERT INTO `boarding_house_rooms` VALUES ('8','48','Private Room','Single','10000.00','5','0','4','2025-09-23 16:57:51');
INSERT INTO `boarding_house_rooms` VALUES ('9','49','Bed Spacer','Group A','5000.00','5','0','5','2025-09-23 17:01:53');
INSERT INTO `boarding_house_rooms` VALUES ('10','50','Private Room','Single','5000.00','3','0','1','2025-09-23 17:05:48');
INSERT INTO `boarding_house_rooms` VALUES ('12','52','Private Room','Double','4000.00','2','0','1','2025-09-23 17:11:40');
INSERT INTO `boarding_house_rooms` VALUES ('13','53','Private Room','Double','5000.00','4','0','1','2025-09-23 17:19:49');
INSERT INTO `boarding_house_rooms` VALUES ('14','54','Bed Spacer','Group B','8000.00','4','0','1','2025-09-23 17:26:01');
INSERT INTO `boarding_house_rooms` VALUES ('15','55','Private Room','Family Room','8000.00','5','0','2','2025-09-26 12:17:22');
INSERT INTO `boarding_house_rooms` VALUES ('16','56','Private Room','SINGLE','1000.00','1','0','2','2025-09-27 21:12:42');
INSERT INTO `boarding_house_rooms` VALUES ('17','57','Private Room','Single Room','2900.00','3','0','1','2025-09-27 21:29:19');
INSERT INTO `boarding_house_rooms` VALUES ('18','58','Private Room','Family','9000.00','5','0','2','2025-09-28 09:16:05');
INSERT INTO `boarding_house_rooms` VALUES ('19','59','Private Room','Family','2000.00','3','0','1','2025-09-28 09:21:13');
INSERT INTO `boarding_house_rooms` VALUES ('20','60','Bed Spacer','Group C','2000.00','6','0','1','2025-09-28 12:59:46');
INSERT INTO `boarding_house_rooms` VALUES ('21','63','Private Room','Single','2000.00','2','0','1','2025-09-30 09:57:02');
INSERT INTO `boarding_house_rooms` VALUES ('22','64','Private Room','Single','2000.00','2','0','1','2025-09-30 10:12:44');
INSERT INTO `boarding_house_rooms` VALUES ('23','65','Private Room','Single','2999.00','3','0','1','2025-09-30 10:14:17');
INSERT INTO `boarding_house_rooms` VALUES ('24','11','Private Room','Single A','2000.00','3','homey','3','2025-09-30 11:30:49');
INSERT INTO `boarding_house_rooms` VALUES ('25','11','Bed Spacer','Group B','1000.00','5','bigg','1','2025-09-30 11:44:05');
INSERT INTO `boarding_house_rooms` VALUES ('26','13','Private Room','Family','10000.00','5','0','1','2025-09-30 11:48:18');
INSERT INTO `boarding_house_rooms` VALUES ('28','12','Private Room','Single A','5000.00','2','1','2','2025-09-30 12:25:25');
INSERT INTO `boarding_house_rooms` VALUES ('29','66','Private Room','Single','5000.00','3','0','1','2025-09-30 12:32:39');
INSERT INTO `boarding_house_rooms` VALUES ('31','11','','Test Room','1000.00','2','0','1','2025-09-30 12:39:43');
INSERT INTO `boarding_house_rooms` VALUES ('33','67','Private Room','Single','5000.00','2','10','1','2025-09-30 12:46:50');
INSERT INTO `boarding_house_rooms` VALUES ('34','68','Private Room','Single','2000.00','2','home','1','2025-09-30 12:54:15');
INSERT INTO `boarding_house_rooms` VALUES ('36','70','Private Room','Single','3000.00','2','home','1','2025-09-30 12:58:17');
INSERT INTO `boarding_house_rooms` VALUES ('37','71','Private Room','Single','2000.00','2','ho','1','2025-09-30 13:00:10');
INSERT INTO `boarding_house_rooms` VALUES ('38','72','Private Room','Single Room','5000.00','2','good for','2','2025-10-03 06:13:18');
INSERT INTO `boarding_house_rooms` VALUES ('39','72','Bed Spacer','Group','1000.00','5','good','2','2025-10-03 06:14:59');
INSERT INTO `boarding_house_rooms` VALUES ('40','11','Private Room','Kim Hauz and Room','900.00','10','Room availability','12','2025-10-03 08:52:21');
INSERT INTO `boarding_house_rooms` VALUES ('41','12','Private Room','Single A','1000.00','2','hhh','1','2025-10-03 08:58:20');
INSERT INTO `boarding_house_rooms` VALUES ('42','73','Private Room','Family Room','8000.00','3','family','2','2025-10-03 09:09:34');
INSERT INTO `boarding_house_rooms` VALUES ('43','77','Private Room','Single','10000.00','5','homeyy is the key','1','2025-10-05 11:13:42');
INSERT INTO `boarding_house_rooms` VALUES ('44','78','Private Room','Single A','4000.00','2','homeeeeyyy','1','2025-10-09 00:48:33');
INSERT INTO `boarding_house_rooms` VALUES ('45','84','Private Room','Single A','4000.00','2','homeee','1','2025-10-09 10:30:08');
INSERT INTO `boarding_house_rooms` VALUES ('46','85','Private Room','single a','2009.00','2','hhhhooo','1','2025-10-12 11:49:07');
INSERT INTO `boarding_house_rooms` VALUES ('47','85','Bed Spacer','Group A','1000.00','4','manyyy','2','2025-10-12 11:54:52');
INSERT INTO `boarding_house_rooms` VALUES ('48','85','Private Room','Room 2','5000.00','2','Just a vibe','1','2025-10-24 14:47:52');
INSERT INTO `boarding_house_rooms` VALUES ('49','87','Private Room','Private Room 01','5000.00','5','Can occupy 5 person','7','2025-10-28 10:26:10');
/*!40000 ALTER TABLE `boarding_house_rooms` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `boarding_houses`
--

DROP TABLE IF EXISTS `boarding_houses`;
CREATE TABLE `boarding_houses` (
  `bh_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `bh_name` varchar(100) NOT NULL,
  `bh_address` varchar(255) NOT NULL,
  `bh_description` text DEFAULT NULL,
  `bh_rules` text DEFAULT NULL,
  `number_of_bathroom` int(11) NOT NULL,
  `area` double(10,2) DEFAULT NULL,
  `build_year` year(4) DEFAULT NULL,
  `status` enum('Active','Inactive') NOT NULL,
  `bh_created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`bh_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `boarding_houses_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=88 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `boarding_houses`
--

LOCK TABLES `boarding_houses` WRITE;
/*!40000 ALTER TABLE `boarding_houses` DISABLE KEYS */;
INSERT INTO `boarding_houses` VALUES ('11','1','BH CUAS','Tinibgan, Calape Bohol','ssss','sssss','1','5.00','2024','Active','2025-09-23 15:16:21');
INSERT INTO `boarding_houses` VALUES ('12','1','BH CUASS','Tinibgan','sss','sss','2','10.00','2024','Active','2025-09-23 15:16:27');
INSERT INTO `boarding_houses` VALUES ('13','1','BH CUAS','Tinibgan','sss','sss','2','10.00','2024','Active','2025-09-23 15:16:29');
INSERT INTO `boarding_houses` VALUES ('14','1','BH CUAS','Tinibgan','sss','sss','2','10.00','2024','Active','2025-09-23 15:17:42');
INSERT INTO `boarding_houses` VALUES ('15','1','BH MANTE','Bangi','bbb','bbb','2','14.00','2025','Active','2025-09-23 15:22:57');
INSERT INTO `boarding_houses` VALUES ('16','1','BH MANTE','Bangi Calape','bbb','bbb','3','14.00','2025','Active','2025-09-23 15:24:27');
INSERT INTO `boarding_houses` VALUES ('17','1','BH SKY','Bentig','bbb','bbb','1','5.00','2025','Active','2025-09-23 15:27:49');
INSERT INTO `boarding_houses` VALUES ('18','1','BH B','gg','ggg','ggg','1','5.00','2024','Active','2025-09-23 15:33:01');
INSERT INTO `boarding_houses` VALUES ('19','1','BH H','ggg','ggg','ggg','1','12.00','2024','Active','2025-09-23 15:34:57');
INSERT INTO `boarding_houses` VALUES ('20','1','BH C','hh','hh','hh','1','1.00','2024','Active','2025-09-23 15:38:07');
INSERT INTO `boarding_houses` VALUES ('21','1','BH G','Gg','gg','gg','1','1.00','2024','Active','2025-09-23 15:39:58');
INSERT INTO `boarding_houses` VALUES ('22','1','BH G','Gg','gg','gg','1','1.00','2024','Active','2025-09-23 15:40:32');
INSERT INTO `boarding_houses` VALUES ('23','1','BH J','jj','jj','jj','1','1.00','2004','Active','2025-09-23 15:42:45');
INSERT INTO `boarding_houses` VALUES ('26','1','BH K','kk','kk','kk','1','1.00','2024','Active','2025-09-23 15:56:35');
INSERT INTO `boarding_houses` VALUES ('28','1','BH K','kk','kk','kk','1','1.00','2024','Active','2025-09-23 15:56:36');
INSERT INTO `boarding_houses` VALUES ('29','1','BH K','kk','kk','kk','1','1.00','2024','Active','2025-09-23 15:56:36');
INSERT INTO `boarding_houses` VALUES ('32','1','BH K','kk','kk','kk','1','1.00','2024','Active','2025-09-23 15:57:22');
INSERT INTO `boarding_houses` VALUES ('34','1','BH L','yy','yy','yy','1','1.00','2004','Active','2025-09-23 16:02:54');
INSERT INTO `boarding_houses` VALUES ('35','1','BH L','yy','yy','yy','1','1.00','2004','Active','2025-09-23 16:03:03');
INSERT INTO `boarding_houses` VALUES ('37','1','BH L','yy','yy','yy','1','1.00','2004','Active','2025-09-23 16:03:13');
INSERT INTO `boarding_houses` VALUES ('38','1','BH L','yy','yy','yy','1','1.00','2004','Active','2025-09-23 16:03:27');
INSERT INTO `boarding_houses` VALUES ('39','1','BH L','yy','yy','yy','1','1.00','2004','Active','2025-09-23 16:05:16');
INSERT INTO `boarding_houses` VALUES ('40','1','BH L','kk','kk','kk','1','1.00','2004','Active','2025-09-23 16:08:38');
INSERT INTO `boarding_houses` VALUES ('41','1','BH L','kk','kk','kk','1','1.00','2004','Active','2025-09-23 16:08:47');
INSERT INTO `boarding_houses` VALUES ('42','1','GB','rr','rr','rr','2','2.00','0000','Active','2025-09-23 16:10:44');
INSERT INTO `boarding_houses` VALUES ('43','1','FG','uu','uu','uu','1','1.00','2004','Active','2025-09-23 16:23:31');
INSERT INTO `boarding_houses` VALUES ('44','1','BB','bb','bb','bb','1','6.00','2023','Active','2025-09-23 16:26:11');
INSERT INTO `boarding_houses` VALUES ('45','1','BB','bb','bb','bb','1','6.00','2023','Active','2025-09-23 16:31:34');
INSERT INTO `boarding_houses` VALUES ('46','1','AA','qq','qq','qq','1','23.00','2023','Active','2025-09-23 16:54:06');
INSERT INTO `boarding_houses` VALUES ('47','1','AA','qq','qq','qq','1','23.00','2023','Active','2025-09-23 16:54:52');
INSERT INTO `boarding_houses` VALUES ('48','1','AA','qq','qq','qq','1','23.00','2023','Active','2025-09-23 16:57:18');
INSERT INTO `boarding_houses` VALUES ('49','1','SS','ss','ss','ss','1','1.00','2004','Active','2025-09-23 17:01:39');
INSERT INTO `boarding_houses` VALUES ('50','1','DD','ee','ee','ee','2','20.00','2020','Active','2025-09-23 17:05:46');
INSERT INTO `boarding_houses` VALUES ('52','1','hh','ff','ff','ff','2','1.00','2024','Active','2025-09-23 17:11:38');
INSERT INTO `boarding_houses` VALUES ('53','1','DD','dd','dd','dd','2','1.00','2022','Active','2025-09-23 17:19:32');
INSERT INTO `boarding_houses` VALUES ('54','1','JJ','jj','jj','jj','1','1.00','2001','Active','2025-09-23 17:25:48');
INSERT INTO `boarding_houses` VALUES ('55','1','TODAY','today','today','today','2','4.00','2024','Active','2025-09-26 12:17:14');
INSERT INTO `boarding_houses` VALUES ('56','1','aa','aa','aa','aa','2','1.00','2024','Active','2025-09-27 21:12:29');
INSERT INTO `boarding_houses` VALUES ('57','1','qq','qq','qq','qq','1','12.00','2024','Active','2025-09-27 21:29:17');
INSERT INTO `boarding_houses` VALUES ('58','1','ww','ww','ww','ww','2','10.00','2023','Active','2025-09-28 09:16:03');
INSERT INTO `boarding_houses` VALUES ('59','1','ee','ee','uyy','uyy','2','10.00','2024','Active','2025-09-28 09:21:03');
INSERT INTO `boarding_houses` VALUES ('60','1','yy','yy','yy','yy','2','2.00','2022','Active','2025-09-28 12:59:43');
INSERT INTO `boarding_houses` VALUES ('61','1','BLENDER','ddd','ddd','dddd','1','2.00','2023','Active','2025-09-30 09:37:57');
INSERT INTO `boarding_houses` VALUES ('63','1','ggg','gg','gg','gg','2','1.00','2004','Active','2025-09-30 09:56:57');
INSERT INTO `boarding_houses` VALUES ('64','1','jjj','hshssh','hhh','hhh','2','2.00','2023','Active','2025-09-30 10:12:38');
INSERT INTO `boarding_houses` VALUES ('65','1','uu','gg','ggg','ggg','2','1.00','2023','Active','2025-09-30 10:14:13');
INSERT INTO `boarding_houses` VALUES ('66','1','p','o','o','o','2','10.00','2024','Active','2025-09-30 12:32:37');
INSERT INTO `boarding_houses` VALUES ('67','1','hays','hays','hays','hays','2','10.00','2023','Active','2025-09-30 12:46:48');
INSERT INTO `boarding_houses` VALUES ('68','1','Y','gg','bb','hh','1','2.00','2023','Active','2025-09-30 12:54:12');
INSERT INTO `boarding_houses` VALUES ('70','1','hagu','hh','hh','hh','2','1.00','2023','Active','2025-09-30 12:58:15');
INSERT INTO `boarding_houses` VALUES ('71','1','ho','ho','ho','ho','2','20.00','2023','Active','2025-09-30 13:00:08');
INSERT INTO `boarding_houses` VALUES ('72','1','BH DO','Calape','homey','m','2','10.00','2023','Active','2025-10-03 06:13:13');
INSERT INTO `boarding_houses` VALUES ('73','1','BH KIMB','Bangi','nnn','nnn','2','10.00','2004','Active','2025-10-03 09:09:28');
INSERT INTO `boarding_houses` VALUES ('74','1','Sunset Boarding House','123 Main Street, Cebu City','A cozy boarding house near the university with modern amenities.','No smoking, No pets, Quiet hours 10PM-6AM','3','200.50','2020','Active','2025-10-04 20:46:17');
INSERT INTO `boarding_houses` VALUES ('75','4','Mountain View Lodge','456 Oak Avenue, Cebu City','Beautiful boarding house with mountain views and fresh air.','Respect other residents, Keep common areas clean','2','150.75','2019','Active','2025-10-04 20:46:17');
INSERT INTO `boarding_houses` VALUES ('76','7','City Center Residence','789 Pine Street, Cebu City','Conveniently located in the city center with easy access to everything.','No loud music, Clean up after yourself','4','300.00','2021','Active','2025-10-04 20:46:17');
INSERT INTO `boarding_houses` VALUES ('77','1','hh','hh','hh','hh','2','10.00','2023','Active','2025-10-05 11:13:35');
INSERT INTO `boarding_houses` VALUES ('78','1','bh','ttyyy','yyynnn','yyy','2','2.00','2023','Active','2025-10-09 00:48:19');
INSERT INTO `boarding_houses` VALUES ('84','1','test','calape','hg','hh','2','10.00','2023','Active','2025-10-09 10:30:04');
INSERT INTO `boarding_houses` VALUES ('85','29','BH 1','Purok 2 Patag, Tinibgan, Calape, Bohol','A boarding house is a house (frequently a family home) in which lodgers rent one or more rooms on a nightly basis and sometimes for extended periods of weeks, months, or years. The common parts of the house are maintained, and some services, such as laundry and cleaning, may be supplied.','yy','2','10.00','2023','Active','2025-10-12 11:48:57');
INSERT INTO `boarding_houses` VALUES ('87','29','Kikyam BH','Lucob, Calape, Bohol','This is a two storey building with aircon. Shalan!','No loud music from 9:00 PM - 6 AM','2','100.00','2020','Active','2025-10-28 10:25:46');
/*!40000 ALTER TABLE `boarding_houses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bookings`
--

DROP TABLE IF EXISTS `bookings`;
CREATE TABLE `bookings` (
  `booking_id` int(11) NOT NULL AUTO_INCREMENT,
  `room_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `booking_status` enum('Pending','Confirmed','Cancelled','Completed') NOT NULL DEFAULT 'Pending',
  `booking_date` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`booking_id`),
  KEY `room_id` (`room_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`room_id`) REFERENCES `room_units` (`room_id`) ON DELETE CASCADE,
  CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=50 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bookings`
--

LOCK TABLES `bookings` WRITE;
/*!40000 ALTER TABLE `bookings` DISABLE KEYS */;
INSERT INTO `bookings` VALUES ('23','83','59','2025-11-10','2025-12-29','Confirmed','2025-11-10 19:22:21');
INSERT INTO `bookings` VALUES ('24','82','44','2025-11-10','2026-01-31','Confirmed','2025-11-10 19:58:57');
INSERT INTO `bookings` VALUES ('30','86','28','2025-11-13','2026-03-21','Confirmed','2025-11-13 11:04:04');
INSERT INTO `bookings` VALUES ('34','85','35','2025-11-13','2025-11-14','Completed','2025-11-13 11:47:15');
INSERT INTO `bookings` VALUES ('36','89','28','2025-11-13','2025-11-14','Completed','2025-11-13 14:16:25');
INSERT INTO `bookings` VALUES ('38','87','59','2025-11-15','2025-11-16','Completed','2025-11-15 19:30:58');
INSERT INTO `bookings` VALUES ('41','88','35','2025-11-15','2025-11-16','Completed','2025-11-15 21:55:17');
INSERT INTO `bookings` VALUES ('46','90','38','2025-11-16','2026-01-31','Confirmed','2025-11-16 13:08:10');
INSERT INTO `bookings` VALUES ('48','91','35','2025-11-17','2025-11-18','Completed','2025-11-17 20:31:06');
INSERT INTO `bookings` VALUES ('49','85','38','2025-11-18','2025-12-13','Confirmed','2025-11-18 19:47:27');
/*!40000 ALTER TABLE `bookings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bs_permits`
--

DROP TABLE IF EXISTS `bs_permits`;
CREATE TABLE `bs_permits` (
  `permit_id` int(11) NOT NULL AUTO_INCREMENT,
  `reg_id` int(11) NOT NULL COMMENT 'Foreign key referencing registrations.id',
  `permit_file` varchar(255) NOT NULL COMMENT 'Path to business permit image file',
  `permit_number` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Permit number/index (1, 2, or 3)',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`permit_id`),
  KEY `fk_reg_id` (`reg_id`),
  KEY `idx_reg_permit` (`reg_id`,`permit_number`),
  CONSTRAINT `fk_bs_permits_registration` FOREIGN KEY (`reg_id`) REFERENCES `registrations` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `bs_permits`
--

LOCK TABLES `bs_permits` WRITE;
/*!40000 ALTER TABLE `bs_permits` DISABLE KEYS */;
INSERT INTO `bs_permits` VALUES ('1','117','uploads/business_permits/691d6a503c2f5_permit1.jpg','1','2025-11-19 14:57:26','2025-11-19 14:57:26');
INSERT INTO `bs_permits` VALUES ('2','117','uploads/business_permits/691d6a503e392_permit2.jpg','2','2025-11-19 14:57:26','2025-11-19 14:57:26');
/*!40000 ALTER TABLE `bs_permits` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `chat_groups`
--

DROP TABLE IF EXISTS `chat_groups`;
CREATE TABLE `chat_groups` (
  `gc_id` int(11) NOT NULL AUTO_INCREMENT,
  `bh_id` int(11) NOT NULL,
  `gc_name` varchar(100) NOT NULL,
  `gc_created_by` int(11) NOT NULL,
  `gc_created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`gc_id`),
  KEY `bh_id` (`bh_id`),
  KEY `gc_created_by` (`gc_created_by`),
  CONSTRAINT `chat_groups_ibfk_1` FOREIGN KEY (`bh_id`) REFERENCES `boarding_houses` (`bh_id`) ON DELETE CASCADE,
  CONSTRAINT `chat_groups_ibfk_2` FOREIGN KEY (`gc_created_by`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `chat_groups`
--

LOCK TABLES `chat_groups` WRITE;
/*!40000 ALTER TABLE `chat_groups` DISABLE KEYS */;
INSERT INTO `chat_groups` VALUES ('4','11','BH CUAS Chat','1','2025-10-04 20:50:44');
INSERT INTO `chat_groups` VALUES ('5','12','BH CUASS Residents','1','2025-10-04 20:50:44');
INSERT INTO `chat_groups` VALUES ('6','15','BH MANTE Discussion','1','2025-10-03 20:50:44');
INSERT INTO `chat_groups` VALUES ('7','11','BH CUAS Chat','1','2025-10-04 20:56:44');
INSERT INTO `chat_groups` VALUES ('8','12','BH CUASS Residents','1','2025-10-04 20:56:44');
INSERT INTO `chat_groups` VALUES ('9','15','BH MANTE Discussion','1','2025-10-03 20:56:44');
INSERT INTO `chat_groups` VALUES ('11','85','Test Group A','29','2025-10-14 11:58:45');
INSERT INTO `chat_groups` VALUES ('12','85','Group b','29','2025-10-14 12:00:05');
INSERT INTO `chat_groups` VALUES ('13','85','Group C','29','2025-10-14 15:24:42');
INSERT INTO `chat_groups` VALUES ('14','85','GG','29','2025-10-31 14:55:58');
INSERT INTO `chat_groups` VALUES ('15','85','GGGG','29','2025-11-19 20:38:00');
INSERT INTO `chat_groups` VALUES ('16','85','Jjj','29','2025-11-21 09:22:03');
INSERT INTO `chat_groups` VALUES ('17','85','Jjj','29','2025-11-21 09:22:05');
/*!40000 ALTER TABLE `chat_groups` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `device_tokens`
--

DROP TABLE IF EXISTS `device_tokens`;
CREATE TABLE `device_tokens` (
  `token_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `device_token` varchar(255) NOT NULL,
  `device_type` enum('android','ios','web') DEFAULT 'android',
  `app_version` varchar(50) DEFAULT '1.0.0',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`token_id`),
  UNIQUE KEY `unique_user_token` (`user_id`,`device_token`),
  KEY `idx_user_active` (`user_id`,`is_active`),
  KEY `idx_token` (`device_token`)
) ENGINE=InnoDB AUTO_INCREMENT=45 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `device_tokens`
--

LOCK TABLES `device_tokens` WRITE;
/*!40000 ALTER TABLE `device_tokens` DISABLE KEYS */;
INSERT INTO `device_tokens` VALUES ('10','1','doIZWxHNRkqo_lVUVcNn6a:APA91bGvBwcxisdLz9oNw6CJB1gKSaqz0HmNSLqgOfua9_R_X97IWRIas6HSV0CS4m1LoSMwI2bX959PyMn-vDmxy2K8yIkptrFx8nyzNyaWib5IYH3-0PM','android','1.0.0','0','2025-10-09 10:53:46','2025-10-09 11:02:48');
INSERT INTO `device_tokens` VALUES ('11','1','cfE4VW8eRFeGZjIiX1nWoi:APA91bFpYILFXsXlM5oOcoDbaAPtoUsFq2ylML7OG4kOajLO72qOziZY5jscHR5VDAkpmM8FTZUhdbitQxUaYFPqdBcUQPB-slJWrrz5thBNus6J380csCQ','android','1.0.0','0','2025-10-09 11:02:48','2025-10-09 11:05:51');
INSERT INTO `device_tokens` VALUES ('12','1','cvivWukjRtuy1HWtqnBvZC:APA91bG-4_hUVl1_ElHRbEthGqwOuuGMUwTveK3bYNG-GXYPxXQQeRoQ2SJxmM_coHNE7YCJXRiiLGJyaKcMwYsbxmzxbIRbblxWsOpwSdnU3oAukVHG45I','android','1.0.0','0','2025-10-09 11:05:51','2025-11-17 20:41:06');
INSERT INTO `device_tokens` VALUES ('13','29','cvivWukjRtuy1HWtqnBvZC:APA91bG-4_hUVl1_ElHRbEthGqwOuuGMUwTveK3bYNG-GXYPxXQQeRoQ2SJxmM_coHNE7YCJXRiiLGJyaKcMwYsbxmzxbIRbblxWsOpwSdnU3oAukVHG45I','android','1.0.0','0','2025-10-12 11:33:02','2025-10-12 13:10:07');
INSERT INTO `device_tokens` VALUES ('14','24','cvivWukjRtuy1HWtqnBvZC:APA91bG-4_hUVl1_ElHRbEthGqwOuuGMUwTveK3bYNG-GXYPxXQQeRoQ2SJxmM_coHNE7YCJXRiiLGJyaKcMwYsbxmzxbIRbblxWsOpwSdnU3oAukVHG45I','android','1.0.0','0','2025-10-12 12:36:07','2025-10-14 10:24:32');
INSERT INTO `device_tokens` VALUES ('15','6','cvivWukjRtuy1HWtqnBvZC:APA91bG-4_hUVl1_ElHRbEthGqwOuuGMUwTveK3bYNG-GXYPxXQQeRoQ2SJxmM_coHNE7YCJXRiiLGJyaKcMwYsbxmzxbIRbblxWsOpwSdnU3oAukVHG45I','android','1.0.0','1','2025-10-12 12:37:15','2025-10-12 12:37:15');
INSERT INTO `device_tokens` VALUES ('16','29','f4s7iqzjRtiPhdh0hIia0t:APA91bEhK5oDk51TwRrtatuoJ1kRW7yPve8zhJ-Fi1NAhFwXJfPv-uVQ76rCTe1SPUxbWdahWG6Pz1WsiOZlB1cbvAgaG4m-tmlRGmNmQGSKBSIhjPDHOiI','android','1.0.0','0','2025-10-12 13:10:07','2025-10-12 13:25:49');
INSERT INTO `device_tokens` VALUES ('17','29','cLsLWCccSKKVeX-J0jNLY2:APA91bHs8noetyjaDSli4BhNW1-d6_IjUBjxg2p4sIc5yonRjsh8llOelWp50fiAo__dToRGpm6hDiTTAaGONxqi7vD3fP8qcEFiMxwpCZjtJbvhNqptlhU','android','1.0.0','0','2025-10-12 13:25:49','2025-10-12 13:38:13');
INSERT INTO `device_tokens` VALUES ('18','29','dAXDgbwuQLyxAEpSsU24Am:APA91bHtj93rIkmbpb5x7f5WszdR1eM5929L-cTWkwrk_d4Qkpq8ZR939K48_ruM07BTmIhYscW6_r4xSvYi-3iOo2ehnXWcV0HBbQ9usaRwV1bbXxxS1Ak','android','1.0.0','0','2025-10-12 13:38:13','2025-10-27 09:36:23');
INSERT INTO `device_tokens` VALUES ('19','24','dAXDgbwuQLyxAEpSsU24Am:APA91bHtj93rIkmbpb5x7f5WszdR1eM5929L-cTWkwrk_d4Qkpq8ZR939K48_ruM07BTmIhYscW6_r4xSvYi-3iOo2ehnXWcV0HBbQ9usaRwV1bbXxxS1Ak','android','1.0.0','1','2025-10-14 10:24:32','2025-10-14 10:24:32');
INSERT INTO `device_tokens` VALUES ('20','29','f7SS5GQyRL6yFRqlf10SZ9:APA91bHDlsLELpVloaU2Dz97xSIgK2wJnUihuPhwGGCAgTSQSPXZdKOvyHmVkMbIcQj-ETALUG_cJLhiJzQ302Xf4sZFvWT_TtoOnWJQSRedsHJj0Zkl-zw','android','1.0.0','0','2025-10-24 15:10:22','2025-11-01 14:31:18');
INSERT INTO `device_tokens` VALUES ('21','29','eLd7YhTVRHqp7J75n5t0y3:APA91bF4ovvMnFaHY7IeMoxWGjJRiR4tYAPL-jEDDTh2kGClJLkKH6OZISQeb5YEbtpyLAx_0mWIzpDfVfkWtLxeGUusP8ShvKkVMmaS3WBkxplNaTFSP2c','android','1.0.0','0','2025-10-25 07:14:44','2025-11-17 20:41:34');
INSERT INTO `device_tokens` VALUES ('22','36','dAXDgbwuQLyxAEpSsU24Am:APA91bHtj93rIkmbpb5x7f5WszdR1eM5929L-cTWkwrk_d4Qkpq8ZR939K48_ruM07BTmIhYscW6_r4xSvYi-3iOo2ehnXWcV0HBbQ9usaRwV1bbXxxS1Ak','android','1.0.0','0','2025-10-27 08:20:07','2025-10-28 07:24:45');
INSERT INTO `device_tokens` VALUES ('23','29','fH4UJ38_SG6JP_XHlTlcN1:APA91bHgQxZxSi6VSfTywAXYAn2kN_-GnMZdLjWahSMRQbO93zZ9wmdmT3ndnAekuETCZ9W4TaC8m6XS8gFOVMNJggcueUf7UiOZO4bxioHYqlkBN--RpZE','android','1.0.0','0','2025-10-27 09:36:23','2025-10-28 07:23:24');
INSERT INTO `device_tokens` VALUES ('24','29','fTbyP38mRYmoXKxi-YRLlB:APA91bFDsW1PJw0G2nMo-PQHsx6pzlTYdbJQy3i6Bm25z8e5Hgim9iLnwky5bQxRB-Dvinnd4HtUuJYuJJdqVdV6tnIF1Z2NR_K4Xrjyr5BrP96Tub3ZxMk','android','1.0.0','0','2025-10-28 07:23:24','2025-11-01 14:31:18');
INSERT INTO `device_tokens` VALUES ('25','36','fTbyP38mRYmoXKxi-YRLlB:APA91bFDsW1PJw0G2nMo-PQHsx6pzlTYdbJQy3i6Bm25z8e5Hgim9iLnwky5bQxRB-Dvinnd4HtUuJYuJJdqVdV6tnIF1Z2NR_K4Xrjyr5BrP96Tub3ZxMk','android','1.0.0','1','2025-10-28 07:24:45','2025-10-28 07:24:45');
INSERT INTO `device_tokens` VALUES ('26','29','dlZlJq5IRaeE3Uyfp0CQfL:APA91bHxfiyKjaXaoij-dQrdBkV9_NX4t-uOQ2QzjcIwSHGiJkI_us9PTBL5JS0aNuBxbYyr5nkj4a_ACrttvyBu_rHhzv19VNdQkEoQl0E2nHoHY2BXkrU','android','1.0.0','0','2025-10-28 08:40:50','2025-11-17 20:41:34');
INSERT INTO `device_tokens` VALUES ('27','39','fTbyP38mRYmoXKxi-YRLlB:APA91bFDsW1PJw0G2nMo-PQHsx6pzlTYdbJQy3i6Bm25z8e5Hgim9iLnwky5bQxRB-Dvinnd4HtUuJYuJJdqVdV6tnIF1Z2NR_K4Xrjyr5BrP96Tub3ZxMk','android','1.0.0','1','2025-10-28 19:36:06','2025-10-28 19:36:06');
INSERT INTO `device_tokens` VALUES ('28','40','fTbyP38mRYmoXKxi-YRLlB:APA91bFDsW1PJw0G2nMo-PQHsx6pzlTYdbJQy3i6Bm25z8e5Hgim9iLnwky5bQxRB-Dvinnd4HtUuJYuJJdqVdV6tnIF1Z2NR_K4Xrjyr5BrP96Tub3ZxMk','android','1.0.0','0','2025-10-28 19:50:22','2025-11-07 10:29:07');
INSERT INTO `device_tokens` VALUES ('29','29','dlUZg4BlSIe-YTM8k17UkE:APA91bFGvCttUwhOlX8muO3QMeuHtvrywTSBcZtBP_Hz3TwdBbpiHdcyNrGZD7aeLUY04TU4qQg_p3O5urOYPpf3w9_1KOxBaeNk1sCT8dCGU1S9AxQIdxU','android','1.0.0','0','2025-11-01 14:31:18','2025-11-08 11:42:14');
INSERT INTO `device_tokens` VALUES ('30','40','dlUZg4BlSIe-YTM8k17UkE:APA91bFGvCttUwhOlX8muO3QMeuHtvrywTSBcZtBP_Hz3TwdBbpiHdcyNrGZD7aeLUY04TU4qQg_p3O5urOYPpf3w9_1KOxBaeNk1sCT8dCGU1S9AxQIdxU','android','1.0.0','1','2025-11-07 10:29:07','2025-11-07 10:29:07');
INSERT INTO `device_tokens` VALUES ('31','29','cHBx4_hZSVyviNGi1YpYGS:APA91bGW3V-CHNNxBbVOpkYBf49p1JQjnK-XAYuE54RJQYQCGZXl_cXod9iop-E72V8UyLP2umHU-dq6nHsFP2HtgoGb0sNhXLkHywF-DuG75_lzhu0GBt0','android','1.0.0','0','2025-11-08 11:42:14','2025-11-17 20:41:34');
INSERT INTO `device_tokens` VALUES ('32','1','cCtvmnLcQui2lxxZ48ke2U:APA91bHqING1-2YhcMYwoIsIf5ku42solTwo0fXKFbaeA4A_1ITET9uSa6Ru5YBhTU-exg5w6ynu3wuk3xO0earyFrDYOMvLEQbGm6HQR45mD1yzLsc1Ac4','android','1.0.0','0','2025-11-17 20:41:06','2025-11-17 20:50:04');
INSERT INTO `device_tokens` VALUES ('33','29','cCtvmnLcQui2lxxZ48ke2U:APA91bHqING1-2YhcMYwoIsIf5ku42solTwo0fXKFbaeA4A_1ITET9uSa6Ru5YBhTU-exg5w6ynu3wuk3xO0earyFrDYOMvLEQbGm6HQR45mD1yzLsc1Ac4','android','1.0.0','0','2025-11-17 20:41:34','2025-11-17 20:50:34');
INSERT INTO `device_tokens` VALUES ('34','1','efnfrm_1Te6MAO-sjd62my:APA91bE32Sy0BRi6LN3DEwile8iXgICfaEhjfQpQFq422LBp4f-j-n5Slo-7xd45sQo4EjVwBIwChWzFSAhowFaDon2wO1WkvCmGjrnLHMIVlmoebqfq8rs','android','1.0.0','0','2025-11-17 20:50:04','2025-11-19 09:03:21');
INSERT INTO `device_tokens` VALUES ('35','29','efnfrm_1Te6MAO-sjd62my:APA91bE32Sy0BRi6LN3DEwile8iXgICfaEhjfQpQFq422LBp4f-j-n5Slo-7xd45sQo4EjVwBIwChWzFSAhowFaDon2wO1WkvCmGjrnLHMIVlmoebqfq8rs','android','1.0.0','0','2025-11-17 20:50:34','2025-11-19 10:54:42');
INSERT INTO `device_tokens` VALUES ('36','35','efnfrm_1Te6MAO-sjd62my:APA91bE32Sy0BRi6LN3DEwile8iXgICfaEhjfQpQFq422LBp4f-j-n5Slo-7xd45sQo4EjVwBIwChWzFSAhowFaDon2wO1WkvCmGjrnLHMIVlmoebqfq8rs','android','1.0.0','1','2025-11-17 20:57:10','2025-11-17 20:57:10');
INSERT INTO `device_tokens` VALUES ('37','59','efnfrm_1Te6MAO-sjd62my:APA91bE32Sy0BRi6LN3DEwile8iXgICfaEhjfQpQFq422LBp4f-j-n5Slo-7xd45sQo4EjVwBIwChWzFSAhowFaDon2wO1WkvCmGjrnLHMIVlmoebqfq8rs','android','1.0.0','1','2025-11-17 21:04:24','2025-11-17 21:04:24');
INSERT INTO `device_tokens` VALUES ('38','1','dlQeJkN4TmiBs4zkc_w-pM:APA91bFamW4fw2NmAk69F6s2-ButuuEx_xCpaeoC4T6-lc8iHfeXTvz_iGDtXJi0kDxD26oD16_nLbOUVuRyTM08G6Ft-l0fi_9iZPaaRiUMWj-9oeezlcY','android','1.0.0','0','2025-11-19 09:03:21','2025-11-20 11:02:21');
INSERT INTO `device_tokens` VALUES ('39','29','dlQeJkN4TmiBs4zkc_w-pM:APA91bFamW4fw2NmAk69F6s2-ButuuEx_xCpaeoC4T6-lc8iHfeXTvz_iGDtXJi0kDxD26oD16_nLbOUVuRyTM08G6Ft-l0fi_9iZPaaRiUMWj-9oeezlcY','android','1.0.0','1','2025-11-19 10:54:42','2025-11-20 16:02:33');
INSERT INTO `device_tokens` VALUES ('40','1','f1lGoRI9TrCO-bYAMoK_hk:APA91bGqQ3VuCFA0lCiKlO1BW0_mV26KDb-0Boq7Uq0pu7yUSspSJ8psH2PzVNuHttseVGkIj_qSAPVCKbAL2o1zWyrKb9k-Qu_7TZALoEX5kkA3kd4dLjU','android','1.0.0','0','2025-11-20 11:02:21','2025-11-20 14:52:00');
INSERT INTO `device_tokens` VALUES ('41','1','fs4bM7OVQqy82f2sTy3RXD:APA91bFKdCQyPbyCq5TI4P7GrQh5zlCSVDKSgYTgGlC10pTuTvSOjxaGFgLO9j-ScAqeZWjYvbMb_iTKce85xrp7XJokcedwwsGv4mz0-xBAQca4vWyWkO0','android','1.0.0','1','2025-11-20 14:52:00','2025-11-20 14:52:00');
INSERT INTO `device_tokens` VALUES ('42','29','fs4bM7OVQqy82f2sTy3RXD:APA91bFKdCQyPbyCq5TI4P7GrQh5zlCSVDKSgYTgGlC10pTuTvSOjxaGFgLO9j-ScAqeZWjYvbMb_iTKce85xrp7XJokcedwwsGv4mz0-xBAQca4vWyWkO0','android','1.0.0','1','2025-11-20 15:07:25','2025-11-20 15:07:25');
INSERT INTO `device_tokens` VALUES ('43','28','dlQeJkN4TmiBs4zkc_w-pM:APA91bFamW4fw2NmAk69F6s2-ButuuEx_xCpaeoC4T6-lc8iHfeXTvz_iGDtXJi0kDxD26oD16_nLbOUVuRyTM08G6Ft-l0fi_9iZPaaRiUMWj-9oeezlcY','android','1.0.0','1','2025-11-20 18:45:56','2025-11-21 09:02:35');
INSERT INTO `device_tokens` VALUES ('44','28','fs4bM7OVQqy82f2sTy3RXD:APA91bFKdCQyPbyCq5TI4P7GrQh5zlCSVDKSgYTgGlC10pTuTvSOjxaGFgLO9j-ScAqeZWjYvbMb_iTKce85xrp7XJokcedwwsGv4mz0-xBAQca4vWyWkO0','android','1.0.0','1','2025-11-21 08:59:54','2025-11-21 08:59:54');
/*!40000 ALTER TABLE `device_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `email_verifications`
--

DROP TABLE IF EXISTS `email_verifications`;
CREATE TABLE `email_verifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `verification_code` varchar(6) NOT NULL,
  `expiry_time` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_verification` (`user_id`),
  KEY `idx_email` (`email`),
  KEY `idx_expiry` (`expiry_time`),
  CONSTRAINT `email_verifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `registrations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=93 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `email_verifications`
--

--
-- Table structure for table `group_members`
--

DROP TABLE IF EXISTS `group_members`;
CREATE TABLE `group_members` (
  `gm_id` int(11) NOT NULL AUTO_INCREMENT,
  `gc_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `gm_role` enum('Owner','Boarder','Admin') DEFAULT 'Boarder',
  `gm_joined_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`gm_id`),
  KEY `gc_id` (`gc_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `group_members_ibfk_1` FOREIGN KEY (`gc_id`) REFERENCES `chat_groups` (`gc_id`) ON DELETE CASCADE,
  CONSTRAINT `group_members_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `group_members`
--

LOCK TABLES `group_members` WRITE;
/*!40000 ALTER TABLE `group_members` DISABLE KEYS */;
INSERT INTO `group_members` VALUES ('1','11','28','','2025-10-14 11:58:45');
INSERT INTO `group_members` VALUES ('2','11','1','','2025-10-14 11:58:45');
INSERT INTO `group_members` VALUES ('4','12','28','','2025-10-14 12:00:05');
INSERT INTO `group_members` VALUES ('5','12','1','','2025-10-14 12:00:05');
INSERT INTO `group_members` VALUES ('6','12','29','','2025-10-14 12:00:05');
INSERT INTO `group_members` VALUES ('7','13','28','','2025-10-14 15:24:42');
INSERT INTO `group_members` VALUES ('8','13','1','','2025-10-14 15:24:42');
INSERT INTO `group_members` VALUES ('10','14','28','','2025-10-31 14:55:58');
INSERT INTO `group_members` VALUES ('11','14','1','','2025-10-31 14:55:58');
INSERT INTO `group_members` VALUES ('12','14','29','','2025-10-31 14:55:58');
INSERT INTO `group_members` VALUES ('13','15','38','','2025-11-19 20:38:00');
INSERT INTO `group_members` VALUES ('14','15','59','','2025-11-19 20:38:00');
INSERT INTO `group_members` VALUES ('15','15','28','','2025-11-19 20:38:00');
INSERT INTO `group_members` VALUES ('16','15','44','','2025-11-19 20:38:00');
INSERT INTO `group_members` VALUES ('18','16','38','','2025-11-21 09:22:03');
INSERT INTO `group_members` VALUES ('19','16','59','','2025-11-21 09:22:03');
INSERT INTO `group_members` VALUES ('20','16','28','','2025-11-21 09:22:03');
INSERT INTO `group_members` VALUES ('21','16','44','','2025-11-21 09:22:03');
INSERT INTO `group_members` VALUES ('22','16','29','','2025-11-21 09:22:03');
INSERT INTO `group_members` VALUES ('23','17','38','','2025-11-21 09:22:05');
INSERT INTO `group_members` VALUES ('24','17','59','','2025-11-21 09:22:05');
INSERT INTO `group_members` VALUES ('25','17','28','','2025-11-21 09:22:05');
INSERT INTO `group_members` VALUES ('26','17','44','','2025-11-21 09:22:05');
INSERT INTO `group_members` VALUES ('27','17','29','','2025-11-21 09:22:05');
/*!40000 ALTER TABLE `group_members` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `group_messages`
--

DROP TABLE IF EXISTS `group_messages`;
CREATE TABLE `group_messages` (
  `groupmessage_id` int(11) NOT NULL AUTO_INCREMENT,
  `gc_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `groupmessage_text` text NOT NULL,
  `groupmessage_timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  `groupmessage_status` enum('Sent','Delivered','Read') DEFAULT 'Sent',
  PRIMARY KEY (`groupmessage_id`),
  KEY `gc_id` (`gc_id`),
  KEY `sender_id` (`sender_id`),
  CONSTRAINT `group_messages_ibfk_1` FOREIGN KEY (`gc_id`) REFERENCES `chat_groups` (`gc_id`) ON DELETE CASCADE,
  CONSTRAINT `group_messages_ibfk_2` FOREIGN KEY (`sender_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=40 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `group_messages`
--

LOCK TABLES `group_messages` WRITE;
/*!40000 ALTER TABLE `group_messages` DISABLE KEYS */;
INSERT INTO `group_messages` VALUES ('1','12','29','hi guys','2025-10-14 12:08:15','Read');
INSERT INTO `group_messages` VALUES ('2','12','28','heyy','2025-10-14 13:07:39','Read');
INSERT INTO `group_messages` VALUES ('3','12','29','hiiii!!','2025-10-14 13:08:31','Read');
INSERT INTO `group_messages` VALUES ('4','12','28','samay','2025-10-14 13:09:19','Read');
INSERT INTO `group_messages` VALUES ('5','12','29','what?','2025-10-14 13:11:44','Read');
INSERT INTO `group_messages` VALUES ('6','12','28','yeahhh','2025-10-14 13:13:16','Read');
INSERT INTO `group_messages` VALUES ('7','12','29','huh','2025-10-14 13:17:09','Read');
INSERT INTO `group_messages` VALUES ('8','12','28','nooo','2025-10-14 13:20:31','Read');
INSERT INTO `group_messages` VALUES ('9','12','28','why','2025-10-14 13:20:36','Read');
INSERT INTO `group_messages` VALUES ('10','12','29','huh','2025-10-14 13:30:03','Read');
INSERT INTO `group_messages` VALUES ('11','12','29','nothing','2025-10-14 13:32:56','Read');
INSERT INTO `group_messages` VALUES ('12','12','28','huhuhu','2025-10-14 13:38:07','Read');
INSERT INTO `group_messages` VALUES ('13','12','28','wahatttt','2025-10-14 13:41:30','Read');
INSERT INTO `group_messages` VALUES ('14','12','29','huh','2025-10-14 13:50:02','Read');
INSERT INTO `group_messages` VALUES ('15','12','28','saman','2025-10-14 13:51:01','Read');
INSERT INTO `group_messages` VALUES ('16','12','29','wala man','2025-10-14 13:59:53','Read');
INSERT INTO `group_messages` VALUES ('17','12','28','huy','2025-10-14 14:01:38','Read');
INSERT INTO `group_messages` VALUES ('18','12','29','uy','2025-10-14 14:06:27','Read');
INSERT INTO `group_messages` VALUES ('19','12','29','uy','2025-10-14 14:06:38','Read');
INSERT INTO `group_messages` VALUES ('20','12','28','uy pud','2025-10-14 14:07:20','Read');
INSERT INTO `group_messages` VALUES ('21','12','28','unsa ba','2025-10-14 14:12:32','Read');
INSERT INTO `group_messages` VALUES ('22','12','29','wala lageh','2025-10-14 14:14:07','Read');
INSERT INTO `group_messages` VALUES ('23','12','28','heyyy','2025-10-14 14:32:37','Read');
INSERT INTO `group_messages` VALUES ('24','12','29','hiii','2025-10-14 15:19:39','Read');
INSERT INTO `group_messages` VALUES ('25','12','28','hey','2025-10-14 15:47:49','Read');
INSERT INTO `group_messages` VALUES ('26','12','28','wahta','2025-10-14 16:14:24','Read');
INSERT INTO `group_messages` VALUES ('27','12','29','wala','2025-10-14 16:15:24','Read');
INSERT INTO `group_messages` VALUES ('28','12','29','gegewg','2025-10-14 16:51:22','Read');
INSERT INTO `group_messages` VALUES ('29','12','29','tarung','2025-10-14 16:58:31','Read');
INSERT INTO `group_messages` VALUES ('30','12','28','lage','2025-10-14 17:14:37','Read');
INSERT INTO `group_messages` VALUES ('31','12','28','hi','2025-10-14 17:18:34','Read');
INSERT INTO `group_messages` VALUES ('32','12','29','hello','2025-10-14 17:19:23','Read');
INSERT INTO `group_messages` VALUES ('33','12','29','hi guys','2025-10-23 21:35:35','Read');
INSERT INTO `group_messages` VALUES ('34','12','29','yesss','2025-10-28 20:51:24','Read');
INSERT INTO `group_messages` VALUES ('35','12','28','hiii','2025-10-28 21:06:13','Read');
INSERT INTO `group_messages` VALUES ('36','14','29','hi guys','2025-10-31 14:56:26','Read');
INSERT INTO `group_messages` VALUES ('37','12','29','????','2025-11-13 13:40:49','Read');
INSERT INTO `group_messages` VALUES ('38','14','29','ehjrkydhh','2025-11-13 13:41:30','Read');
INSERT INTO `group_messages` VALUES ('39','15','38','hi guys','2025-11-19 21:06:22','Read');
/*!40000 ALTER TABLE `group_messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `maintenance_requests`
--

DROP TABLE IF EXISTS `maintenance_requests`;
CREATE TABLE `maintenance_requests` (
  `request_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `room_id` int(11) DEFAULT NULL,
  `subject` varchar(255) NOT NULL,
  `area_for_maintenance` varchar(50) NOT NULL,
  `mr_description` text NOT NULL,
  `mr_status` enum('Declined','Pending','In Progress','Resolved') NOT NULL DEFAULT 'Pending',
  `mr_approved_at` timestamp NULL DEFAULT NULL COMMENT 'Timestamp when status changed to In Progress',
  `mr_completed_at` timestamp NULL DEFAULT NULL COMMENT 'Timestamp when status changed to Resolved',
  `mr_created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`request_id`),
  KEY `user_id` (`user_id`),
  KEY `room_id` (`room_id`),
  KEY `idx_approved_at` (`mr_approved_at`),
  KEY `idx_completed_at` (`mr_completed_at`),
  CONSTRAINT `fk_maintenance_room` FOREIGN KEY (`room_id`) REFERENCES `room_units` (`room_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `maintenance_requests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `maintenance_requests`
--

LOCK TABLES `maintenance_requests` WRITE;
/*!40000 ALTER TABLE `maintenance_requests` DISABLE KEYS */;
INSERT INTO `maintenance_requests` VALUES ('16','35','91','DAMAGEE','BH Room','Jsjsjsk','Declined',NULL,NULL,'2025-11-18 14:30:14');
INSERT INTO `maintenance_requests` VALUES ('17','35','91','DAMAGEE','BH Room','Jsjsjsk','Resolved','2025-11-18 14:30:44','2025-11-18 14:31:12','2025-11-18 14:30:17');
INSERT INTO `maintenance_requests` VALUES ('18','35','91','Damagee','BH Room','Nznzn','Pending',NULL,NULL,'2025-11-18 14:40:11');
INSERT INTO `maintenance_requests` VALUES ('19','35','91','Vahajaj','Kitchen','NNN','Pending',NULL,NULL,'2025-11-18 14:42:45');
INSERT INTO `maintenance_requests` VALUES ('20','35','91','Damana','Kitchen','JNkK','In Progress','2025-11-19 20:03:18',NULL,'2025-11-18 14:46:26');
INSERT INTO `maintenance_requests` VALUES ('21','35','91','Ajjaaj','BH Room','Nsjssjj','Declined',NULL,NULL,'2025-11-18 14:50:12');
INSERT INTO `maintenance_requests` VALUES ('22','35','91','Sjhsuw','BH Room','Hzhsjsj','In Progress','2025-11-18 14:55:27',NULL,'2025-11-18 14:54:23');
/*!40000 ALTER TABLE `maintenance_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `messages`
--

DROP TABLE IF EXISTS `messages`;
CREATE TABLE `messages` (
  `message_id` int(11) NOT NULL AUTO_INCREMENT,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `msg_text` text NOT NULL,
  `msg_timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  `msg_status` enum('Sent','Delivered','Read') DEFAULT 'Sent',
  PRIMARY KEY (`message_id`),
  KEY `sender_id` (`sender_id`),
  KEY `receiver_id` (`receiver_id`),
  CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`sender_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=330 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `messages`
--

LOCK TABLES `messages` WRITE;
/*!40000 ALTER TABLE `messages` DISABLE KEYS */;
INSERT INTO `messages` VALUES ('1','1','2','Hello! Welcome to our boarding house.','2025-10-04 18:57:56','');
INSERT INTO `messages` VALUES ('2','2','1','Thank you! I\'m excited to be here.','2025-10-04 18:57:56','Read');
INSERT INTO `messages` VALUES ('3','1','2','If you need anything, just let me know.','2025-10-04 19:57:56','');
INSERT INTO `messages` VALUES ('5','4','6','Good morning! How are you settling in?','2025-10-04 19:57:56','Read');
INSERT INTO `messages` VALUES ('6','6','4','Everything is great, thank you!','2025-10-04 20:12:56','Read');
INSERT INTO `messages` VALUES ('15','1','2','Hello! This is a test message from the real messaging system.','2025-10-04 22:04:54','');
INSERT INTO `messages` VALUES ('16','2','1','hiii','2025-10-04 22:05:34','Read');
INSERT INTO `messages` VALUES ('18','6','2','Hello! This is a test message from the real messaging system.','2025-10-04 22:13:48','Sent');
INSERT INTO `messages` VALUES ('19','6','1','Hello! This is a test message from the real messaging system.','2025-10-04 22:13:53','Read');
INSERT INTO `messages` VALUES ('20','6','1','Hello! This is a test message from the real messaging system.','2025-10-04 22:13:57','Read');
INSERT INTO `messages` VALUES ('22','6','2','Hello! This is a test message from the real messaging system.','2025-10-04 22:23:52','Sent');
INSERT INTO `messages` VALUES ('23','6','1','Hello! This is a test message from the real messaging system.','2025-10-04 22:23:59','Read');
INSERT INTO `messages` VALUES ('32','1','2','Hello! This is a test message from the real messaging system.','2025-10-05 09:36:25','');
INSERT INTO `messages` VALUES ('33','1','2','Hello! This is a test message from the real messaging system.','2025-10-05 09:46:18','');
INSERT INTO `messages` VALUES ('34','1','2','Hello! This is a test message from the real messaging system.','2025-10-05 09:48:19','');
INSERT INTO `messages` VALUES ('35','1','2','Hello! This is a test message from the real messaging system.','2025-10-05 10:01:51','');
INSERT INTO `messages` VALUES ('36','2','1','? Test Message Badge - 13:36:11','2025-10-05 13:36:11','Read');
INSERT INTO `messages` VALUES ('37','2','1','? Test Message Badge - 13:37:20','2025-10-05 13:37:20','Read');
INSERT INTO `messages` VALUES ('38','2','1','? Test Message Badge - 13:40:27','2025-10-05 13:40:27','Read');
INSERT INTO `messages` VALUES ('39','2','1','? Test Message Badge - 14:39:57','2025-10-05 14:39:57','Read');
INSERT INTO `messages` VALUES ('40','1','2','Test message from PHP','2025-10-05 16:13:10','');
INSERT INTO `messages` VALUES ('41','1','6','hi','2025-10-05 16:14:09','Read');
INSERT INTO `messages` VALUES ('42','1','6','hi','2025-10-05 16:14:12','Read');
INSERT INTO `messages` VALUES ('43','1','2','Test message from PHP','2025-10-05 16:20:05','');
INSERT INTO `messages` VALUES ('44','1','6','hhiii','2025-10-05 16:22:02','Read');
INSERT INTO `messages` VALUES ('45','1','6','hhiii','2025-10-05 16:22:05','Read');
INSERT INTO `messages` VALUES ('46','1','2','Test message from PHP','2025-10-05 16:27:01','');
INSERT INTO `messages` VALUES ('47','1','6','hooo','2025-10-05 16:29:11','Read');
INSERT INTO `messages` VALUES ('48','1','6','hooo','2025-10-05 16:29:14','Read');
INSERT INTO `messages` VALUES ('49','1','2','uouu','2025-10-05 16:29:52','');
INSERT INTO `messages` VALUES ('50','1','2','uouu','2025-10-05 16:29:55','');
INSERT INTO `messages` VALUES ('51','1','2','Test message from PHP','2025-10-05 16:34:29','');
INSERT INTO `messages` VALUES ('52','1','2','bitaw','2025-10-05 16:41:51','');
INSERT INTO `messages` VALUES ('53','1','2','bitaw','2025-10-05 16:41:53','');
INSERT INTO `messages` VALUES ('54','1','6','how about me','2025-10-05 16:55:01','Read');
INSERT INTO `messages` VALUES ('55','1','6','how about me','2025-10-05 16:55:03','Read');
INSERT INTO `messages` VALUES ('56','1','6','huy','2025-10-05 17:20:38','Read');
INSERT INTO `messages` VALUES ('57','1','6','huy','2025-10-05 17:20:40','Read');
INSERT INTO `messages` VALUES ('58','1','2','hey','2025-10-05 17:22:12','');
INSERT INTO `messages` VALUES ('59','1','2','hey','2025-10-05 17:22:15','');
INSERT INTO `messages` VALUES ('60','1','6','huy pud','2025-10-05 17:27:49','Read');
INSERT INTO `messages` VALUES ('61','1','6','huy pud','2025-10-05 17:27:51','Read');
INSERT INTO `messages` VALUES ('62','1','6','huy ba','2025-10-05 17:28:10','Read');
INSERT INTO `messages` VALUES ('63','1','6','huy ba','2025-10-05 17:28:12','Read');
INSERT INTO `messages` VALUES ('64','1','2','hello','2025-10-05 17:28:29','');
INSERT INTO `messages` VALUES ('65','1','2','hello','2025-10-05 17:28:31','');
INSERT INTO `messages` VALUES ('66','1','2','ouhh','2025-10-05 17:35:00','');
INSERT INTO `messages` VALUES ('67','1','2','ouhh','2025-10-05 17:35:02','');
INSERT INTO `messages` VALUES ('68','1','6','low','2025-10-05 17:41:58','Read');
INSERT INTO `messages` VALUES ('69','1','6','low','2025-10-05 17:42:00','Read');
INSERT INTO `messages` VALUES ('70','1','2','huyy','2025-10-05 18:40:08','');
INSERT INTO `messages` VALUES ('71','1','2','huyy','2025-10-05 18:40:11','');
INSERT INTO `messages` VALUES ('74','1','6','lowbat','2025-10-05 18:41:10','Read');
INSERT INTO `messages` VALUES ('75','1','6','lowbat','2025-10-05 18:41:13','Read');
INSERT INTO `messages` VALUES ('77','1','2','yes','2025-10-05 19:00:32','Sent');
INSERT INTO `messages` VALUES ('78','1','2','yes','2025-10-05 19:00:34','Sent');
INSERT INTO `messages` VALUES ('82','1','2','no','2025-10-05 20:19:52','Sent');
INSERT INTO `messages` VALUES ('83','1','2','no','2025-10-05 20:19:55','Sent');
INSERT INTO `messages` VALUES ('84','1','2','favri','2025-10-05 20:24:37','Sent');
INSERT INTO `messages` VALUES ('85','1','2','favri','2025-10-05 20:24:39','Sent');
INSERT INTO `messages` VALUES ('86','1','2','dam','2025-10-05 20:29:10','Sent');
INSERT INTO `messages` VALUES ('87','1','2','dam','2025-10-05 20:29:12','Sent');
INSERT INTO `messages` VALUES ('88','1','2','waley','2025-10-05 20:29:29','Sent');
INSERT INTO `messages` VALUES ('89','1','2','waley','2025-10-05 20:29:31','Sent');
INSERT INTO `messages` VALUES ('90','1','6','bat','2025-10-05 20:30:29','Read');
INSERT INTO `messages` VALUES ('91','1','6','bat','2025-10-05 20:30:31','Read');
INSERT INTO `messages` VALUES ('92','1','6','hey','2025-10-05 20:34:56','Read');
INSERT INTO `messages` VALUES ('93','1','6','hey','2025-10-05 20:34:59','Read');
INSERT INTO `messages` VALUES ('94','1','6','woi','2025-10-05 20:38:33','Read');
INSERT INTO `messages` VALUES ('96','4','1','hays','2025-10-05 20:44:32','Read');
INSERT INTO `messages` VALUES ('97','4','1','gaba gajud ni','2025-10-05 20:45:02','Read');
INSERT INTO `messages` VALUES ('98','1','4','kims','2025-10-05 20:45:39','Sent');
INSERT INTO `messages` VALUES ('99','4','1','yes','2025-10-05 20:45:49','Read');
INSERT INTO `messages` VALUES ('100','4','1','hi','2025-10-05 20:51:27','Read');
INSERT INTO `messages` VALUES ('101','4','1','hiii','2025-10-05 20:52:01','Read');
INSERT INTO `messages` VALUES ('102','1','6','REAL-TIME TEST MESSAGE 1759668852','2025-10-05 20:54:12','Read');
INSERT INTO `messages` VALUES ('103','1','6','API TEST MESSAGE 1759668852','2025-10-05 20:54:14','Read');
INSERT INTO `messages` VALUES ('104','4','1','yy','2025-10-05 20:54:59','Read');
INSERT INTO `messages` VALUES ('105','4','1','no\r\n','2025-10-05 20:55:27','Read');
INSERT INTO `messages` VALUES ('106','1','4','yesss','2025-10-07 09:23:58','Sent');
INSERT INTO `messages` VALUES ('107','1','2','hi','2025-10-07 09:24:26','Sent');
INSERT INTO `messages` VALUES ('108','1','2','hi','2025-10-07 09:24:56','Sent');
INSERT INTO `messages` VALUES ('109','1','4','huy dapat sa babaw ka','2025-10-07 09:25:34','Sent');
INSERT INTO `messages` VALUES ('112','1','6','boboerns','2025-10-07 09:26:18','Read');
INSERT INTO `messages` VALUES ('113','1','2','haystt','2025-10-07 09:26:33','Sent');
INSERT INTO `messages` VALUES ('114','1','2','nooo','2025-10-07 09:35:52','Sent');
INSERT INTO `messages` VALUES ('115','1','2','ye','2025-10-07 15:07:28','Sent');
INSERT INTO `messages` VALUES ('116','1','2','heyy','2025-10-08 23:08:54','Sent');
INSERT INTO `messages` VALUES ('118','1','2','Hello! This is a test message from the real messaging system.','2025-10-08 23:11:02','Sent');
INSERT INTO `messages` VALUES ('119','2','1','Hello! This is a test message from the real messaging system.','2025-10-08 23:17:38','Read');
INSERT INTO `messages` VALUES ('120','1','2','okays','2025-10-08 23:18:21','Sent');
INSERT INTO `messages` VALUES ('121','1','2','huhu','2025-10-08 23:21:57','Sent');
INSERT INTO `messages` VALUES ('122','2','1','Hello! This is a test message from the real messaging system.','2025-10-08 23:26:20','Read');
INSERT INTO `messages` VALUES ('123','1','6','huhuhu','2025-10-08 23:32:26','Read');
INSERT INTO `messages` VALUES ('124','1','6','huyyy','2025-10-08 23:40:41','Read');
INSERT INTO `messages` VALUES ('125','2','1','Hello! This is a test message from the real messaging system.','2025-10-08 23:40:54','Read');
INSERT INTO `messages` VALUES ('126','1','6','huyyy','2025-10-08 23:57:48','Read');
INSERT INTO `messages` VALUES ('127','2','1','Hello! This is a test message from the real messaging system.','2025-10-08 23:57:59','Read');
INSERT INTO `messages` VALUES ('128','2','1','Hello! This is a test message from the real messaging system.','2025-10-08 23:58:04','Read');
INSERT INTO `messages` VALUES ('129','2','1','Hello! This is a test message from the real messaging system.','2025-10-09 00:04:47','Read');
INSERT INTO `messages` VALUES ('130','2','1','Hello! This is a test message from the real messaging system.','2025-10-09 00:05:03','Read');
INSERT INTO `messages` VALUES ('131','2','1','Test notification message - should pop up!','2025-10-09 00:06:49','Read');
INSERT INTO `messages` VALUES ('132','1','2','we','2025-10-09 00:09:57','Sent');
INSERT INTO `messages` VALUES ('133','1','2','weeeee','2025-10-09 00:10:08','Sent');
INSERT INTO `messages` VALUES ('134','1','4','bay','2025-10-09 00:14:26','Sent');
INSERT INTO `messages` VALUES ('135','2','1','Hello! This is a test message from the real messaging system.','2025-10-09 00:15:25','Read');
INSERT INTO `messages` VALUES ('136','2','1','Hello! This is a test message from the real messaging system.','2025-10-09 00:16:11','Read');
INSERT INTO `messages` VALUES ('139','1','6','hagua mn ka','2025-10-09 00:21:00','Read');
INSERT INTO `messages` VALUES ('140','2','1','Hello! This is a test message from the real messaging system.','2025-10-09 00:27:48','Read');
INSERT INTO `messages` VALUES ('141','1','6','uy','2025-10-09 10:31:29','Read');
INSERT INTO `messages` VALUES ('142','1','6','dina lageh ka mogana notif','2025-10-09 10:31:37','Read');
INSERT INTO `messages` VALUES ('143','2','1','Hello! This is a test message from the real messaging system.','2025-10-09 10:36:35','Read');
INSERT INTO `messages` VALUES ('144','1','6','woyyy','2025-10-09 10:54:52','Read');
INSERT INTO `messages` VALUES ('145','2','1','Hello! This is a test message from the real messaging system.','2025-10-09 10:55:19','Read');
INSERT INTO `messages` VALUES ('146','2','1','Hello! This is a test message from the real messaging system.','2025-10-09 10:56:32','Read');
INSERT INTO `messages` VALUES ('147','4','2','Hello! This is a test message from the real messaging system.','2025-10-09 10:59:06','Sent');
INSERT INTO `messages` VALUES ('148','4','1','Hello! This is a test message from the real messaging system.','2025-10-09 10:59:23','Read');
INSERT INTO `messages` VALUES ('149','4','1','Hello! This is a test message from the real messaging system.','2025-10-09 10:59:33','Read');
INSERT INTO `messages` VALUES ('150','1','2','Hello! This is a test message from the real messaging system.','2025-10-09 11:01:30','Sent');
INSERT INTO `messages` VALUES ('151','2','1','Hello! This is a test message from the real messaging system.','2025-10-09 11:01:44','Read');
INSERT INTO `messages` VALUES ('152','2','1','Hello! This is a test message from the real messaging system.','2025-10-09 11:03:22','Read');
INSERT INTO `messages` VALUES ('153','2','1','Hello! This is a test message from the real messaging system.','2025-10-09 11:03:30','Read');
INSERT INTO `messages` VALUES ('154','2','1','Hello! This is a test message from the real messaging system.','2025-10-09 11:03:36','Read');
INSERT INTO `messages` VALUES ('156','5','1','Hello! This is a test message from the real messaging system.','2025-10-09 11:03:50','Read');
INSERT INTO `messages` VALUES ('157','2','1','Hello! This is a test message from the real messaging system.','2025-10-09 11:04:44','Read');
INSERT INTO `messages` VALUES ('158','2','1','we\r\n','2025-10-09 11:04:56','Read');
INSERT INTO `messages` VALUES ('159','2','1','we\r\n','2025-10-09 11:06:05','Read');
INSERT INTO `messages` VALUES ('160','2','1','wala ','2025-10-09 11:06:11','Read');
INSERT INTO `messages` VALUES ('161','2','29','hi','2025-10-12 12:07:20','Read');
INSERT INTO `messages` VALUES ('162','1','6','woyyy','2025-10-12 12:08:27','Read');
INSERT INTO `messages` VALUES ('163','2','29','hello','2025-10-12 12:24:07','Read');
INSERT INTO `messages` VALUES ('164','8','29','hello','2025-10-12 12:24:38','Read');
INSERT INTO `messages` VALUES ('165','29','8','hi','2025-10-12 12:25:01','Sent');
INSERT INTO `messages` VALUES ('166','29','2','huy','2025-10-12 12:25:29','Sent');
INSERT INTO `messages` VALUES ('167','29','6','hi','2025-10-12 12:25:34','Read');
INSERT INTO `messages` VALUES ('168','29','6','hello po','2025-10-12 12:30:13','Read');
INSERT INTO `messages` VALUES ('169','6','29','hello','2025-10-12 12:30:47','Read');
INSERT INTO `messages` VALUES ('170','24','29','hello','2025-10-12 12:31:36','Read');
INSERT INTO `messages` VALUES ('171','24','29','hoo','2025-10-12 12:32:01','Read');
INSERT INTO `messages` VALUES ('172','24','29','hoo','2025-10-12 13:10:43','Read');
INSERT INTO `messages` VALUES ('173','24','29','hupay','2025-10-12 13:39:12','Read');
INSERT INTO `messages` VALUES ('174','24','29','huhuhuhu\r\n\r\n','2025-10-12 13:39:32','Read');
INSERT INTO `messages` VALUES ('175','27','29','https://open.spotify.com/playlist/37i9dQZF1E36NC4j9YSysy\r\n\r\n','2025-10-12 13:40:09','Read');
INSERT INTO `messages` VALUES ('176','27','29','huhuhu','2025-10-12 13:43:29','Read');
INSERT INTO `messages` VALUES ('177','27','29','huhuhu','2025-10-12 13:43:42','Read');
INSERT INTO `messages` VALUES ('178','29','28','hi','2025-10-14 13:38:53','Read');
INSERT INTO `messages` VALUES ('179','28','29','yes?','2025-10-14 13:41:19','Read');
INSERT INTO `messages` VALUES ('180','28','29','hays','2025-10-14 13:50:52','Read');
INSERT INTO `messages` VALUES ('181','29','28','yes?','2025-10-14 13:59:59','Read');
INSERT INTO `messages` VALUES ('182','28','29','aw wala raman','2025-10-14 14:01:09','Read');
INSERT INTO `messages` VALUES ('183','28','29','huy','2025-10-14 14:01:33','Read');
INSERT INTO `messages` VALUES ('184','29','28','uy','2025-10-14 14:02:19','Read');
INSERT INTO `messages` VALUES ('185','28','29','huh','2025-10-14 14:07:35','Read');
INSERT INTO `messages` VALUES ('186','28','29','unsa','2025-10-14 14:12:19','Read');
INSERT INTO `messages` VALUES ('187','29','28','wala lagrh','2025-10-14 14:14:27','Read');
INSERT INTO `messages` VALUES ('188','28','29','noo','2025-10-14 14:32:48','Read');
INSERT INTO `messages` VALUES ('189','29','28','hey','2025-10-14 14:53:55','Read');
INSERT INTO `messages` VALUES ('190','29','28','okay','2025-10-14 15:19:58','Read');
INSERT INTO `messages` VALUES ('191','29','28','huyyyy','2025-10-14 15:29:00','Read');
INSERT INTO `messages` VALUES ('192','29','28','ha','2025-10-14 15:44:32','Read');
INSERT INTO `messages` VALUES ('193','28','29','wala','2025-10-14 15:47:57','Read');
INSERT INTO `messages` VALUES ('194','29','28','hays','2025-10-14 15:53:56','Read');
INSERT INTO `messages` VALUES ('195','29','28','haysh','2025-10-14 15:53:59','Read');
INSERT INTO `messages` VALUES ('196','29','28','hays','2025-10-14 15:54:03','Read');
INSERT INTO `messages` VALUES ('197','28','29','what happen','2025-10-14 16:14:33','Read');
INSERT INTO `messages` VALUES ('198','29','28','wala mannnn','2025-10-14 16:15:35','Read');
INSERT INTO `messages` VALUES ('199','28','29','sure ka?','2025-10-14 16:40:31','Read');
INSERT INTO `messages` VALUES ('200','28','29','sure ba','2025-10-14 16:56:48','Read');
INSERT INTO `messages` VALUES ('201','29','28','lagehhh','2025-10-14 16:58:21','Read');
INSERT INTO `messages` VALUES ('202','28','29','huy','2025-10-14 17:03:01','Read');
INSERT INTO `messages` VALUES ('203','28','29','jjj','2025-10-14 17:04:41','Read');
INSERT INTO `messages` VALUES ('204','28','29','jjjjjjjjj','2025-10-14 17:06:28','Read');
INSERT INTO `messages` VALUES ('205','28','29','hakdog','2025-10-14 17:06:43','Read');
INSERT INTO `messages` VALUES ('206','28','29','kk','2025-10-14 17:11:51','Read');
INSERT INTO `messages` VALUES ('207','28','29','hi','2025-10-14 17:18:43','Read');
INSERT INTO `messages` VALUES ('208','29','28','hello','2025-10-14 17:19:14','Read');
INSERT INTO `messages` VALUES ('209','28','29','yes?','2025-10-23 21:37:42','Read');
INSERT INTO `messages` VALUES ('210','29','28','b**o','2025-10-25 11:46:58','Read');
INSERT INTO `messages` VALUES ('211','29','28','t***a','2025-10-25 11:47:07','Read');
INSERT INTO `messages` VALUES ('212','29','28','f**k','2025-10-25 11:47:14','Read');
INSERT INTO `messages` VALUES ('213','29','28','s****d','2025-10-25 11:47:25','Read');
INSERT INTO `messages` VALUES ('214','29','28','t*****a','2025-10-25 11:47:46','Read');
INSERT INTO `messages` VALUES ('215','29','28','hi','2025-10-25 11:47:48','Read');
INSERT INTO `messages` VALUES ('216','29','28','boboha nimo','2025-10-25 11:49:40','Read');
INSERT INTO `messages` VALUES ('217','29','28','b**o','2025-10-25 11:49:45','Read');
INSERT INTO `messages` VALUES ('218','29','28','fucking s****d','2025-10-25 11:51:07','Read');
INSERT INTO `messages` VALUES ('219','29','28','your so f*****g s****d','2025-10-25 11:53:20','Read');
INSERT INTO `messages` VALUES ('220','29','28','s**t','2025-10-25 11:53:28','Read');
INSERT INTO `messages` VALUES ('221','29','28','f*****g','2025-10-25 12:51:40','Read');
INSERT INTO `messages` VALUES ('222','28','29','s**t','2025-10-25 12:52:54','Read');
INSERT INTO `messages` VALUES ('223','29','28','b******t','2025-10-25 16:06:12','Read');
INSERT INTO `messages` VALUES ('224','29','28','s**t','2025-10-27 09:29:41','Read');
INSERT INTO `messages` VALUES ('225','29','28','b******t','2025-10-27 09:29:51','Read');
INSERT INTO `messages` VALUES ('226','28','29','namz','2025-10-27 09:31:46','Read');
INSERT INTO `messages` VALUES ('227','29','28','kim your so s****d','2025-10-27 09:32:36','Read');
INSERT INTO `messages` VALUES ('228','28','29','i don\'t care','2025-10-27 09:33:14','Read');
INSERT INTO `messages` VALUES ('229','29','28','okay','2025-10-27 09:33:33','Read');
INSERT INTO `messages` VALUES ('230','29','28','s**t','2025-10-27 09:39:10','Read');
INSERT INTO `messages` VALUES ('231','29','28','b**o ka','2025-10-27 09:39:24','Read');
INSERT INTO `messages` VALUES ('232','29','28','hiii','2025-10-28 20:50:41','Read');
INSERT INTO `messages` VALUES ('233','29','28','hu','2025-10-28 20:51:13','Read');
INSERT INTO `messages` VALUES ('234','29','28','hiii','2025-10-28 20:52:03','Read');
INSERT INTO `messages` VALUES ('235','28','29','yes?','2025-10-28 20:53:32','Read');
INSERT INTO `messages` VALUES ('236','28','29','huyyy','2025-10-28 20:59:38','Read');
INSERT INTO `messages` VALUES ('237','29','28','hiiii','2025-10-28 21:19:32','Read');
INSERT INTO `messages` VALUES ('238','29','28','hiii','2025-10-31 14:55:38','Read');
INSERT INTO `messages` VALUES ('239','29','28','hi liz','2025-11-01 14:27:06','Read');
INSERT INTO `messages` VALUES ('240','28','29','yesdd hi???','2025-11-01 14:28:47','Read');
INSERT INTO `messages` VALUES ('241','28','29','s**t','2025-11-01 14:28:57','Read');
INSERT INTO `messages` VALUES ('242','29','28','hi','2025-11-01 14:31:41','Read');
INSERT INTO `messages` VALUES ('243','28','29','namz, u receive this message? hahaha','2025-11-05 20:08:32','Read');
INSERT INTO `messages` VALUES ('244','29','28','hi','2025-11-05 20:17:55','Read');
INSERT INTO `messages` VALUES ('245','29','28','hi kim','2025-11-13 13:34:04','Read');
INSERT INTO `messages` VALUES ('246','29','28','okay rana','2025-11-13 13:34:08','Read');
INSERT INTO `messages` VALUES ('247','29','59','h**lo','2025-11-13 13:34:30','Read');
INSERT INTO `messages` VALUES ('248','29','59','hi','2025-11-13 13:34:44','Read');
INSERT INTO `messages` VALUES ('249','29','59','ug','2025-11-13 13:35:52','Read');
INSERT INTO `messages` VALUES ('250','29','59','yuh','2025-11-13 13:35:59','Read');
INSERT INTO `messages` VALUES ('251','29','59','ily','2025-11-13 13:36:07','Read');
INSERT INTO `messages` VALUES ('252','29','59','euuudru','2025-11-13 13:41:02','Read');
INSERT INTO `messages` VALUES ('253','28','29','ouh','2025-11-13 13:41:56','Read');
INSERT INTO `messages` VALUES ('254','28','29','okay ra. bitaw ko','2025-11-13 13:42:09','Read');
INSERT INTO `messages` VALUES ('255','29','28','hahahaa','2025-11-13 14:04:47','Read');
INSERT INTO `messages` VALUES ('256','29','28','hi','2025-11-17 20:42:25','Read');
INSERT INTO `messages` VALUES ('257','29','28','h**lo','2025-11-17 20:52:53','Read');
INSERT INTO `messages` VALUES ('258','29','28','hii','2025-11-17 20:53:03','Read');
INSERT INTO `messages` VALUES ('259','35','29','hi','2025-11-17 20:56:46','Read');
INSERT INTO `messages` VALUES ('260','59','29','hi','2025-11-17 20:58:27','Read');
INSERT INTO `messages` VALUES ('261','29','59','hi!','2025-11-17 21:02:39','Read');
INSERT INTO `messages` VALUES ('262','59','29','hii?','2025-11-17 21:03:29','Read');
INSERT INTO `messages` VALUES ('263','59','29','low','2025-11-17 21:13:12','Read');
INSERT INTO `messages` VALUES ('264','29','35','hii','2025-11-17 21:14:48','Read');
INSERT INTO `messages` VALUES ('265','29','35','hi','2025-11-17 21:18:59','Read');
INSERT INTO `messages` VALUES ('266','59','29','hii','2025-11-17 21:19:47','Read');
INSERT INTO `messages` VALUES ('267','29','59','hi','2025-11-17 21:21:55','Read');
INSERT INTO `messages` VALUES ('268','29','59','hi','2025-11-17 21:22:45','Read');
INSERT INTO `messages` VALUES ('269','29','59','hi','2025-11-17 21:30:35','Read');
INSERT INTO `messages` VALUES ('270','29','59','hii','2025-11-17 21:31:09','Read');
INSERT INTO `messages` VALUES ('271','29','35','low','2025-11-17 21:34:14','Read');
INSERT INTO `messages` VALUES ('272','29','35','low','2025-11-17 21:34:45','Read');
INSERT INTO `messages` VALUES ('273','29','35','hi','2025-11-17 21:39:25','Read');
INSERT INTO `messages` VALUES ('274','29','35','hii','2025-11-17 21:42:04','Read');
INSERT INTO `messages` VALUES ('275','29','35','hii','2025-11-17 21:43:23','Read');
INSERT INTO `messages` VALUES ('276','29','59','hi','2025-11-17 21:43:40','Read');
INSERT INTO `messages` VALUES ('277','35','29','hi','2025-11-17 21:44:50','Read');
INSERT INTO `messages` VALUES ('278','35','29','hii','2025-11-17 21:47:24','Read');
INSERT INTO `messages` VALUES ('279','29','35','hi','2025-11-17 21:48:16','Read');
INSERT INTO `messages` VALUES ('280','29','35','hi','2025-11-17 21:48:25','Read');
INSERT INTO `messages` VALUES ('281','29','59','hi','2025-11-17 21:48:47','Read');
INSERT INTO `messages` VALUES ('282','59','29','hi','2025-11-17 21:55:02','Read');
INSERT INTO `messages` VALUES ('283','59','29','hi','2025-11-17 21:57:59','Read');
INSERT INTO `messages` VALUES ('284','59','29','hi','2025-11-17 21:59:28','Read');
INSERT INTO `messages` VALUES ('285','29','35','good eves','2025-11-17 22:03:09','Read');
INSERT INTO `messages` VALUES ('286','35','29','eves','2025-11-17 22:06:34','Read');
INSERT INTO `messages` VALUES ('287','29','35','eves pud','2025-11-17 22:07:32','Read');
INSERT INTO `messages` VALUES ('288','29','35','kumusta?','2025-11-17 22:07:44','Read');
INSERT INTO `messages` VALUES ('289','29','35','bitaw','2025-11-17 22:08:08','Read');
INSERT INTO `messages` VALUES ('290','35','29','aw maayu','2025-11-17 22:09:18','Read');
INSERT INTO `messages` VALUES ('291','29','35','nicest','2025-11-17 22:10:47','Read');
INSERT INTO `messages` VALUES ('292','35','29','okayssss','2025-11-17 22:19:17','Read');
INSERT INTO `messages` VALUES ('293','29','35','lakaw','2025-11-17 22:20:07','Read');
INSERT INTO `messages` VALUES ('294','29','35','lakwssss','2025-11-17 22:20:19','Read');
INSERT INTO `messages` VALUES ('295','29','35','okaysss','2025-11-17 22:20:29','Read');
INSERT INTO `messages` VALUES ('296','29','35','yes','2025-11-17 22:37:12','Sent');
INSERT INTO `messages` VALUES ('297','29','35','morning','2025-11-18 10:32:09','Sent');
INSERT INTO `messages` VALUES ('298','38','29','hi','2025-11-19 19:59:19','Read');
INSERT INTO `messages` VALUES ('299','38','28','hi','2025-11-19 19:59:49','Read');
INSERT INTO `messages` VALUES ('300','29','44','hissy','2025-11-19 20:43:03','');
INSERT INTO `messages` VALUES ('301','29','44','hi','2025-11-19 20:43:18','');
INSERT INTO `messages` VALUES ('302','29','44','kijj','2025-11-19 20:43:23','');
INSERT INTO `messages` VALUES ('303','38','29','hii','2025-11-19 20:58:11','Read');
INSERT INTO `messages` VALUES ('304','38','29','H**lo','2025-11-19 21:19:11','Read');
INSERT INTO `messages` VALUES ('305','38','29','yawa','2025-11-19 21:19:19','Read');
INSERT INTO `messages` VALUES ('306','38','29','b**o','2025-11-19 21:19:27','Read');
INSERT INTO `messages` VALUES ('307','38','29','b**o','2025-11-19 21:19:41','Read');
INSERT INTO `messages` VALUES ('308','38','29','jasjsjsjsjbssnbxbxxbxbxbfbfbfbfbfbffbffbfbfbfbfncnfnfnfnfnfnfnfnfnfnfnfnfnfnfnfnfnfnfnfnfnfnfdjdjdjdjrjrjrjrjrjrj','2025-11-19 21:31:50','Read');
INSERT INTO `messages` VALUES ('309','29','28','Hi','2025-11-19 21:57:51','Read');
INSERT INTO `messages` VALUES ('310','29','38','B**o ka','2025-11-20 13:16:06','Sent');
INSERT INTO `messages` VALUES ('311','29','38','Putangina mo','2025-11-20 13:16:54','Sent');
INSERT INTO `messages` VALUES ('312','29','38','P*******a mo','2025-11-20 13:17:17','Sent');
INSERT INTO `messages` VALUES ('313','29','38','Hi','2025-11-20 13:17:34','Sent');
INSERT INTO `messages` VALUES ('314','59','29','hi','2025-11-20 13:21:08','Sent');
INSERT INTO `messages` VALUES ('315','28','29','Hi','2025-11-20 14:47:43','Read');
INSERT INTO `messages` VALUES ('316','29','28','Hi','2025-11-20 14:48:33','Read');
INSERT INTO `messages` VALUES ('317','29','28','Hi','2025-11-20 14:49:14','Read');
INSERT INTO `messages` VALUES ('318','29','28','Hi','2025-11-20 14:53:26','Read');
INSERT INTO `messages` VALUES ('319','29','28','Hikims','2025-11-20 14:53:40','Read');
INSERT INTO `messages` VALUES ('320','29','28','Hiiihi po','2025-11-20 14:54:07','Read');
INSERT INTO `messages` VALUES ('321','29','28','Jhhh','2025-11-20 14:54:49','Read');
INSERT INTO `messages` VALUES ('322','28','29','hiii','2025-11-20 14:55:53','Read');
INSERT INTO `messages` VALUES ('323','29','28','His','2025-11-20 14:56:57','Read');
INSERT INTO `messages` VALUES ('324','28','29','Hi kimmmmm','2025-11-20 15:10:37','Read');
INSERT INTO `messages` VALUES ('325','29','28','uy hallo','2025-11-20 15:11:06','Read');
INSERT INTO `messages` VALUES ('326','28','29','Halo','2025-11-20 18:48:31','Read');
INSERT INTO `messages` VALUES ('327','29','28','Hrllo','2025-11-20 18:49:11','Read');
INSERT INTO `messages` VALUES ('328','28','29','Najsjsjs','2025-11-21 09:00:44','Read');
INSERT INTO `messages` VALUES ('329','29','28','Uyss','2025-11-21 09:01:03','Read');
/*!40000 ALTER TABLE `messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notification_templates`
--

DROP TABLE IF EXISTS `notification_templates`;
CREATE TABLE `notification_templates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `template_key` varchar(100) NOT NULL,
  `template_title` varchar(255) NOT NULL,
  `template_message` text NOT NULL,
  `notification_type` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `template_key` (`template_key`)
) ENGINE=InnoDB AUTO_INCREMENT=201 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notification_templates`
--

LOCK TABLES `notification_templates` WRITE;
/*!40000 ALTER TABLE `notification_templates` DISABLE KEYS */;
INSERT INTO `notification_templates` VALUES ('1','booking_created','New Booking Request','You have a new booking request from {tenant_name} for {room_name}','booking','2025-11-15 12:02:17','2025-11-15 13:09:51');
INSERT INTO `notification_templates` VALUES ('2','booking_approved','Booking Approved','Your booking request for {room_name} has been checked and approved!','booking','2025-11-15 12:02:17','2025-11-15 13:09:51');
INSERT INTO `notification_templates` VALUES ('3','booking_declined','Booking Declined','Your booking request for {room_name} has been declined.{reason}','booking','2025-11-15 12:02:17','2025-11-15 13:09:51');
INSERT INTO `notification_templates` VALUES ('4','booking_cancelled','Booking Cancelled','Booking for {room_name} has been cancelled.','booking','2025-11-15 12:02:17','2025-11-15 13:09:51');
INSERT INTO `notification_templates` VALUES ('5','payment_received','Payment Received','Payment of ₱{amount} has been received{description}','payment','2025-11-15 12:02:17','2025-11-15 13:09:51');
INSERT INTO `notification_templates` VALUES ('6','payment_created','New Payment Pending','A new payment of ₱{amount} is pending{description}','payment','2025-11-15 12:02:17','2025-11-15 13:09:51');
INSERT INTO `notification_templates` VALUES ('7','payment_status_updated','Payment Status Updated','Your payment of ₱{amount} status has been updated to: {status}','payment','2025-11-15 12:02:17','2025-11-15 13:09:51');
INSERT INTO `notification_templates` VALUES ('8','payment_overdue','Payment Overdue','Your payment of ₱{amount} is overdue. Please settle it as soon as possible.','payment','2025-11-15 12:02:17','2025-11-15 13:09:51');
INSERT INTO `notification_templates` VALUES ('9','maintenance_request','New Maintenance Request','{boarder_name} has submitted a maintenance request for {room_name}: {title}','maintenance','2025-11-15 12:02:17','2025-11-15 13:09:51');
INSERT INTO `notification_templates` VALUES ('10','maintenance_status_updated','Maintenance Status Updated','Maintenance request status updated to: {status}','maintenance','2025-11-15 12:02:17','2025-11-15 13:09:51');
INSERT INTO `notification_templates` VALUES ('11','maintenance_completed','Maintenance Completed','Your maintenance request has been completed.','maintenance','2025-11-15 12:02:17','2025-11-15 13:09:51');
INSERT INTO `notification_templates` VALUES ('12','maintenance_feedback','Maintenance Feedback','Feedback received for maintenance request.','maintenance','2025-11-15 12:02:17','2025-11-15 13:09:51');
INSERT INTO `notification_templates` VALUES ('13','announcement_new','New Announcement','{title}: {message}','announcement','2025-11-15 12:02:17','2025-11-15 13:09:51');
INSERT INTO `notification_templates` VALUES ('14','announcement_owner_response','Owner Response','Owner responded to your review.','announcement','2025-11-15 12:02:17','2025-11-15 13:09:51');
INSERT INTO `notification_templates` VALUES ('15','registration_approved','Registration Approved','Your registration has been approved! You can now login to your account.','registration','2025-11-15 12:02:17','2025-11-15 13:09:51');
INSERT INTO `notification_templates` VALUES ('16','registration_rejected','Registration Rejected','Your registration has been rejected. Please contact support for more information.','registration','2025-11-15 12:02:17','2025-11-15 13:09:51');
INSERT INTO `notification_templates` VALUES ('17','message_new','New Message','New message from {sender_name}: {message_preview}','message','2025-11-15 12:02:17','2025-11-15 13:09:51');
INSERT INTO `notification_templates` VALUES ('18','message_group','New Group Message','New message in {group_name} from {sender_name}','message','2025-11-15 12:02:17','2025-11-15 13:09:51');
INSERT INTO `notification_templates` VALUES ('19','security_password_changed','Password Changed','Your password has been successfully changed.','security','2025-11-15 12:02:17','2025-11-15 13:09:51');
INSERT INTO `notification_templates` VALUES ('20','security_email_changed','Email Changed','Your email address has been successfully changed.','security','2025-11-15 12:02:17','2025-11-15 13:09:51');
/*!40000 ALTER TABLE `notification_templates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
CREATE TABLE `notifications` (
  `notif_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `notif_title` varchar(150) NOT NULL,
  `notif_message` text NOT NULL,
  `notif_type` enum('booking','payment','announcement','maintenance','general') DEFAULT 'general',
  `notif_status` enum('unread','read') DEFAULT 'unread',
  `notif_created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`notif_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=731 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
INSERT INTO `notifications` VALUES ('164','28','Registration Approved','Congratulations! Your BH Owner account has been approved. You can now log in to BoardEase.','general','read','2025-11-10 07:38:39');
INSERT INTO `notifications` VALUES ('165','29','Registration Approved','Congratulations! Your BH Owner account has been approved. You can now log in to BoardEase.','general','read','2025-11-10 07:41:19');
INSERT INTO `notifications` VALUES ('166','6','Important Matters','will going to have a meeting in the afternoon','general','unread','2025-11-10 07:59:10');
INSERT INTO `notifications` VALUES ('167','27','Important Matters','will going to have a meeting in the afternoon','general','unread','2025-11-10 07:59:12');
INSERT INTO `notifications` VALUES ('168','24','Important Matters','will going to have a meeting in the afternoon','general','unread','2025-11-10 07:59:12');
INSERT INTO `notifications` VALUES ('169','29','Important Matters','will going to have a meeting in the afternoon','general','read','2025-11-10 07:59:13');
INSERT INTO `notifications` VALUES ('170','36','Important Matters','will going to have a meeting in the afternoon','general','unread','2025-11-10 07:59:13');
INSERT INTO `notifications` VALUES ('171','37','Important Matters','will going to have a meeting in the afternoon','general','unread','2025-11-10 07:59:14');
INSERT INTO `notifications` VALUES ('172','40','Important Matters','will going to have a meeting in the afternoon','general','unread','2025-11-10 07:59:14');
INSERT INTO `notifications` VALUES ('173','2','Meeting','will have a meeting afternoon','announcement','unread','2025-11-10 08:15:42');
INSERT INTO `notifications` VALUES ('174','1','Meeting','will have a meeting afternoon','announcement','unread','2025-11-10 08:15:42');
INSERT INTO `notifications` VALUES ('175','4','Meeting','will have a meeting afternoon','announcement','unread','2025-11-10 08:15:43');
INSERT INTO `notifications` VALUES ('176','58','Meeting','will have a meeting afternoon','announcement','unread','2025-11-10 08:15:43');
INSERT INTO `notifications` VALUES ('177','23','Meeting','will have a meeting afternoon','announcement','unread','2025-11-10 08:15:43');
INSERT INTO `notifications` VALUES ('178','28','Meeting','will have a meeting afternoon','announcement','read','2025-11-10 08:15:43');
INSERT INTO `notifications` VALUES ('179','35','Meeting','will have a meeting afternoon','announcement','read','2025-11-10 08:15:43');
INSERT INTO `notifications` VALUES ('180','38','Meeting','will have a meeting afternoon','announcement','read','2025-11-10 08:15:43');
INSERT INTO `notifications` VALUES ('181','44','Meeting','will have a meeting afternoon','announcement','read','2025-11-10 08:15:43');
INSERT INTO `notifications` VALUES ('182','45','New Booking Request','You have a new booking request from Ruel Cuas for single a','booking','unread','2025-11-10 08:18:40');
INSERT INTO `notifications` VALUES ('183','59','Registration Approved','Congratulations! Your User account has been approved. You can now log in to BoardEase.','','read','2025-11-10 08:31:11');
INSERT INTO `notifications` VALUES ('186','62','Registration Approved','Congratulations! Your User account has been approved. You can now log in to BoardEase.','','read','2025-11-10 09:18:08');
INSERT INTO `notifications` VALUES ('187','44','Booking Declined','Your booking request for single a has been declined. Reason: Declined by owner','booking','read','2025-11-10 09:25:56');
INSERT INTO `notifications` VALUES ('188','45','New Booking Request','You have a new booking request from Ruel Cuas for single a','booking','unread','2025-11-10 09:27:48');
INSERT INTO `notifications` VALUES ('189','44','Booking Approved','Your booking request for single a has been approved!','booking','read','2025-11-10 09:39:28');
INSERT INTO `notifications` VALUES ('190','45','New Booking Request','You have a new booking request from John Sagetarios for Room 2','booking','unread','2025-11-10 09:48:57');
INSERT INTO `notifications` VALUES ('191','59','Booking Declined','Your booking request for Room 2 has been declined. Reason: Declined by owner','booking','read','2025-11-10 09:49:45');
INSERT INTO `notifications` VALUES ('192','6','Meeting','meeting!','announcement','unread','2025-11-10 09:53:33');
INSERT INTO `notifications` VALUES ('193','27','Meeting','meeting!','announcement','unread','2025-11-10 09:53:34');
INSERT INTO `notifications` VALUES ('194','24','Meeting','meeting!','announcement','unread','2025-11-10 09:53:34');
INSERT INTO `notifications` VALUES ('195','29','Meeting','meeting!','announcement','read','2025-11-10 09:53:35');
INSERT INTO `notifications` VALUES ('196','36','Meeting','meeting!','announcement','unread','2025-11-10 09:53:36');
INSERT INTO `notifications` VALUES ('197','37','Meeting','meeting!','announcement','unread','2025-11-10 09:53:37');
INSERT INTO `notifications` VALUES ('198','40','Meeting','meeting!','announcement','unread','2025-11-10 09:53:37');
INSERT INTO `notifications` VALUES ('199','2','Meeting','meeting all!!!','announcement','unread','2025-11-10 09:53:58');
INSERT INTO `notifications` VALUES ('200','1','Meeting','meeting all!!!','announcement','unread','2025-11-10 09:53:58');
INSERT INTO `notifications` VALUES ('201','4','Meeting','meeting all!!!','announcement','unread','2025-11-10 09:53:59');
INSERT INTO `notifications` VALUES ('202','6','Meeting','meeting all!!!','announcement','unread','2025-11-10 09:53:59');
INSERT INTO `notifications` VALUES ('203','58','Meeting','meeting all!!!','announcement','unread','2025-11-10 09:54:00');
INSERT INTO `notifications` VALUES ('204','27','Meeting','meeting all!!!','announcement','unread','2025-11-10 09:54:00');
INSERT INTO `notifications` VALUES ('205','24','Meeting','meeting all!!!','announcement','unread','2025-11-10 09:54:00');
INSERT INTO `notifications` VALUES ('206','23','Meeting','meeting all!!!','announcement','unread','2025-11-10 09:54:01');
INSERT INTO `notifications` VALUES ('207','28','Meeting','meeting all!!!','announcement','read','2025-11-10 09:54:01');
INSERT INTO `notifications` VALUES ('208','29','Meeting','meeting all!!!','announcement','read','2025-11-10 09:54:01');
INSERT INTO `notifications` VALUES ('209','35','Meeting','meeting all!!!','announcement','read','2025-11-10 09:54:01');
INSERT INTO `notifications` VALUES ('210','36','Meeting','meeting all!!!','announcement','unread','2025-11-10 09:54:01');
INSERT INTO `notifications` VALUES ('211','37','Meeting','meeting all!!!','announcement','unread','2025-11-10 09:54:02');
INSERT INTO `notifications` VALUES ('212','38','Meeting','meeting all!!!','announcement','read','2025-11-10 09:54:02');
INSERT INTO `notifications` VALUES ('213','40','Meeting','meeting all!!!','announcement','unread','2025-11-10 09:54:02');
INSERT INTO `notifications` VALUES ('214','44','Meeting','meeting all!!!','announcement','read','2025-11-10 09:54:03');
INSERT INTO `notifications` VALUES ('215','59','Meeting','meeting all!!!','announcement','read','2025-11-10 09:54:03');
INSERT INTO `notifications` VALUES ('216','62','Meeting','meeting all!!!','announcement','unread','2025-11-10 09:54:03');
INSERT INTO `notifications` VALUES ('217','45','New Booking Request','You have a new booking request from John Sagetarios for Room 2','booking','unread','2025-11-10 09:59:17');
INSERT INTO `notifications` VALUES ('218','59','Booking Declined','Your booking request for Room 2 has been declined. Reason: Declined by owner','booking','read','2025-11-10 10:03:08');
INSERT INTO `notifications` VALUES ('219','45','New Booking Request','You have a new booking request from John Sagetarios for Room 2','booking','unread','2025-11-10 10:05:13');
INSERT INTO `notifications` VALUES ('220','59','Booking Declined','Your booking request for Room 2 has been declined. Reason: Declined by owner','booking','read','2025-11-10 10:14:30');
INSERT INTO `notifications` VALUES ('221','29','New Booking Request','You have a new booking request from John Sagetarios for Room 2','booking','read','2025-11-10 10:16:05');
INSERT INTO `notifications` VALUES ('222','59','Booking Declined','Your booking request for Room 2 has been declined. Reason: Declined by owner','booking','read','2025-11-10 10:17:03');
INSERT INTO `notifications` VALUES ('223','29','New Booking Request','You have a new booking request from Ruel Cuas for Room 2','booking','read','2025-11-10 11:53:04');
INSERT INTO `notifications` VALUES ('224','29','New Booking Request','You have a new booking request from John Sagetarios for Room 2','booking','read','2025-11-10 19:22:24');
INSERT INTO `notifications` VALUES ('225','29','New Booking Request','You have a new booking request from Ruel Cuas for Group A','booking','read','2025-11-10 19:58:59');
INSERT INTO `notifications` VALUES ('226','29','New Booking Request','You have a new booking request from Ruel Cuas for Private Room 01','booking','read','2025-11-13 09:41:12');
INSERT INTO `notifications` VALUES ('227','29','New Payment Pending','A new payment of ₱166.67 is pending for Payment for Private Room 01 at Kikyam BH','payment','read','2025-11-13 10:27:51');
INSERT INTO `notifications` VALUES ('228','29','New Booking Request','You have a new booking request from Ruel Cuas for Private Room 01','booking','read','2025-11-13 10:27:52');
INSERT INTO `notifications` VALUES ('229','35','Payment Status Updated','Your payment of ₱166.67 status has been updated to: Fully Paid','payment','read','2025-11-13 10:29:43');
INSERT INTO `notifications` VALUES ('230','29','Payment Received','Payment of ₱166.67 has been received for Payment for Private Room 01 at Kikyam BH','payment','read','2025-11-13 10:29:43');
INSERT INTO `notifications` VALUES ('231','35','Payment Status Updated','Your payment of ₱166.67 status has been updated to: Fully Paid','payment','read','2025-11-13 10:29:46');
INSERT INTO `notifications` VALUES ('232','29','Payment Received','Payment of ₱166.67 has been received for Payment for Private Room 01 at Kikyam BH','payment','read','2025-11-13 10:29:46');
INSERT INTO `notifications` VALUES ('233','35','Payment Status Updated','Your payment of ₱166.67 status has been updated to: Fully Paid','payment','read','2025-11-13 10:31:09');
INSERT INTO `notifications` VALUES ('234','29','Payment Received','Payment of ₱166.67 has been received for Payment for Private Room 01 at Kikyam BH','payment','read','2025-11-13 10:31:09');
INSERT INTO `notifications` VALUES ('235','35','Payment Status Updated','Your payment of ₱166.67 status has been updated to: Fully Paid','payment','read','2025-11-13 10:31:11');
INSERT INTO `notifications` VALUES ('236','29','Payment Received','Payment of ₱166.67 has been received for Payment for Private Room 01 at Kikyam BH','payment','read','2025-11-13 10:31:12');
INSERT INTO `notifications` VALUES ('237','35','Payment Status Updated','Your payment of ₱166.67 status has been updated to: Fully Paid','payment','read','2025-11-13 10:33:49');
INSERT INTO `notifications` VALUES ('238','29','Payment Received','Payment of ₱166.67 has been received for Payment for Private Room 01 at Kikyam BH','payment','read','2025-11-13 10:33:49');
INSERT INTO `notifications` VALUES ('239','35','Payment Status Updated','Your payment of ₱166.67 status has been updated to: Fully Paid','payment','read','2025-11-13 10:33:51');
INSERT INTO `notifications` VALUES ('240','29','Payment Received','Payment of ₱166.67 has been received for Payment for Private Room 01 at Kikyam BH','payment','read','2025-11-13 10:33:51');
INSERT INTO `notifications` VALUES ('241','29','New Payment Pending','A new payment of ₱166.67 is pending for Payment for Private Room 01 at Kikyam BH','payment','read','2025-11-13 10:42:53');
INSERT INTO `notifications` VALUES ('242','29','New Booking Request','You have a new booking request from Ruel Cuas for Private Room 01','booking','read','2025-11-13 10:42:54');
INSERT INTO `notifications` VALUES ('243','35','Booking Approved','Your booking request for Private Room 01 has been approved!','booking','read','2025-11-13 10:43:55');
INSERT INTO `notifications` VALUES ('244','35','Payment Status Updated','Your payment of ₱166.67 status has been updated to: Fully Paid','payment','read','2025-11-13 10:44:18');
INSERT INTO `notifications` VALUES ('245','29','Payment Received','Payment of ₱166.67 has been received for Payment for Private Room 01 at Kikyam BH','payment','read','2025-11-13 10:44:18');
INSERT INTO `notifications` VALUES ('246','35','Payment Status Updated','Your payment of ₱166.67 status has been updated to: Fully Paid','payment','read','2025-11-13 10:44:20');
INSERT INTO `notifications` VALUES ('247','29','Payment Received','Payment of ₱166.67 has been received for Payment for Private Room 01 at Kikyam BH','payment','read','2025-11-13 10:44:20');
INSERT INTO `notifications` VALUES ('248','29','New Payment Pending','A new payment of ₱166.67 is pending for Payment for Private Room 01 at Kikyam BH','payment','read','2025-11-13 10:54:00');
INSERT INTO `notifications` VALUES ('249','29','New Booking Request','You have a new booking request from Ruel Cuas for Private Room 01','booking','read','2025-11-13 10:54:01');
INSERT INTO `notifications` VALUES ('250','35','Booking Approved','Your booking request for Private Room 01 has been approved!','booking','read','2025-11-13 10:54:43');
INSERT INTO `notifications` VALUES ('251','35','Payment Received','Payment of ₱166.67 has been received for Payment for Private Room 01 at Kikyam BH','payment','read','2025-11-13 10:55:29');
INSERT INTO `notifications` VALUES ('252','29','Payment Received','Payment of ₱166.67 has been received for Payment for Private Room 01 at Kikyam BH','payment','read','2025-11-13 10:55:29');
INSERT INTO `notifications` VALUES ('253','35','Payment Received','Payment of ₱166.67 has been received for Payment for Private Room 01 at Kikyam BH','payment','read','2025-11-13 10:55:31');
INSERT INTO `notifications` VALUES ('254','29','Payment Received','Payment of ₱166.67 has been received for Payment for Private Room 01 at Kikyam BH','payment','read','2025-11-13 10:55:32');
INSERT INTO `notifications` VALUES ('255','1','New Payment Pending','A new payment of ₱510.00 is pending for Payment for Kim Hauz and Room at BH CUAS','payment','unread','2025-11-13 11:00:05');
INSERT INTO `notifications` VALUES ('256','1','New Booking Request','You have a new booking request from Liz Uy for Kim Hauz and Room','booking','unread','2025-11-13 11:00:14');
INSERT INTO `notifications` VALUES ('257','29','New Payment Pending','A new payment of ₱5,000.00 is pending for Payment for Private Room 01 at Kikyam BH','payment','read','2025-11-13 11:04:06');
INSERT INTO `notifications` VALUES ('258','29','New Booking Request','You have a new booking request from Liz Uy for Private Room 01','booking','read','2025-11-13 11:04:07');
INSERT INTO `notifications` VALUES ('259','29','New Payment Pending','A new payment of ₱166.67 is pending for Payment for Private Room 01 at Kikyam BH','payment','read','2025-11-13 11:04:26');
INSERT INTO `notifications` VALUES ('260','29','New Booking Request','You have a new booking request from Ruel Cuas for Private Room 01','booking','read','2025-11-13 11:04:26');
INSERT INTO `notifications` VALUES ('261','28','Booking Approved','Your booking request for Private Room 01 has been approved!','booking','read','2025-11-13 11:05:29');
INSERT INTO `notifications` VALUES ('262','35','Booking Approved','Your booking request for Private Room 01 has been approved!','booking','read','2025-11-13 11:05:50');
INSERT INTO `notifications` VALUES ('263','28','Payment Status Updated','Your payment of ₱5,000.00 status has been updated to: Completed/Partially','payment','read','2025-11-13 11:06:26');
INSERT INTO `notifications` VALUES ('264','29','Payment Received','Payment of ₱5,000.00 has been received for Payment for Private Room 01 at Kikyam BH','payment','read','2025-11-13 11:06:26');
INSERT INTO `notifications` VALUES ('265','28','Payment Status Updated','Your payment of ₱5,000.00 status has been updated to: Completed/Partially','payment','read','2025-11-13 11:06:28');
INSERT INTO `notifications` VALUES ('266','29','Payment Received','Payment of ₱5,000.00 has been received for Payment for Private Room 01 at Kikyam BH','payment','read','2025-11-13 11:06:28');
INSERT INTO `notifications` VALUES ('267','35','Payment Status Updated','Your payment of ₱166.67 status has been updated to: Fully Paid','payment','read','2025-11-13 11:15:44');
INSERT INTO `notifications` VALUES ('268','29','Payment Received','Payment of ₱166.67 has been received for Payment for Private Room 01 at Kikyam BH','payment','read','2025-11-13 11:15:44');
INSERT INTO `notifications` VALUES ('269','29','New Payment Pending','A new payment of ₱166.67 is pending for Payment for Private Room 01 at Kikyam BH','payment','read','2025-11-13 11:20:21');
INSERT INTO `notifications` VALUES ('270','29','New Booking Request','You have a new booking request from Ruel Cuas for Private Room 01','booking','read','2025-11-13 11:20:22');
INSERT INTO `notifications` VALUES ('271','35','Booking Approved','Your booking request for Private Room 01 has been approved!','booking','read','2025-11-13 11:22:59');
INSERT INTO `notifications` VALUES ('272','35','Payment Status Updated','Your payment of ₱166.67 status has been updated to: Fully Paid','payment','read','2025-11-13 11:23:26');
INSERT INTO `notifications` VALUES ('273','29','Payment Received','Payment of ₱166.67 has been received for Payment for Private Room 01 at Kikyam BH','payment','read','2025-11-13 11:23:27');
INSERT INTO `notifications` VALUES ('274','29','New Payment Pending','A new payment of ₱166.67 is pending for Payment for Private Room 01 at Kikyam BH','payment','read','2025-11-13 11:36:23');
INSERT INTO `notifications` VALUES ('275','29','New Booking Request','You have a new booking request from Ruel Cuas for Private Room 01','booking','read','2025-11-13 11:36:24');
INSERT INTO `notifications` VALUES ('276','35','Booking Approved','Your booking request for Private Room 01 has been approved!','booking','read','2025-11-13 11:37:25');
INSERT INTO `notifications` VALUES ('277','35','Payment Status Updated','Your payment of ₱166.67 status has been updated to: Fully Paid','payment','read','2025-11-13 11:37:48');
INSERT INTO `notifications` VALUES ('278','29','Payment Received','Payment of ₱166.67 has been received for Payment for Private Room 01 at Kikyam BH','payment','read','2025-11-13 11:37:48');
INSERT INTO `notifications` VALUES ('279','29','New Payment Pending','A new payment of ₱166.67 is pending for Payment for Private Room 01 at Kikyam BH','payment','read','2025-11-13 11:47:17');
INSERT INTO `notifications` VALUES ('280','29','New Booking Request','You have a new booking request from Ruel Cuas for Private Room 01','booking','read','2025-11-13 11:47:18');
INSERT INTO `notifications` VALUES ('281','35','Booking Approved','Your booking request for Private Room 01 has been approved!','booking','read','2025-11-13 11:48:22');
INSERT INTO `notifications` VALUES ('282','35','Payment Status Updated','Your payment of ₱166.67 status has been updated to: Fully Paid','payment','read','2025-11-13 11:48:39');
INSERT INTO `notifications` VALUES ('283','29','Payment Received','Payment of ₱166.67 has been received for Payment for Private Room 01 at Kikyam BH','payment','read','2025-11-13 11:48:39');
INSERT INTO `notifications` VALUES ('284','28','New Message from Namz Baer','hi kim','general','read','2025-11-13 13:34:04');
INSERT INTO `notifications` VALUES ('285','28','New Message from Namz Baer','okay rana','general','read','2025-11-13 13:34:08');
INSERT INTO `notifications` VALUES ('286','59','New Message from Namz Baer','h**lo','general','read','2025-11-13 13:34:30');
INSERT INTO `notifications` VALUES ('287','59','New Message from Namz Baer','hi','general','read','2025-11-13 13:34:44');
INSERT INTO `notifications` VALUES ('288','59','New Message from Namz Baer','ug','general','read','2025-11-13 13:35:52');
INSERT INTO `notifications` VALUES ('289','59','New Message from Namz Baer','yuh','general','read','2025-11-13 13:35:59');
INSERT INTO `notifications` VALUES ('290','59','New Message from Namz Baer','ily','general','read','2025-11-13 13:36:07');
INSERT INTO `notifications` VALUES ('291','28','Group b','Namz Baer: ????','general','read','2025-11-13 13:40:49');
INSERT INTO `notifications` VALUES ('292','1','Group b','Namz Baer: ????','general','unread','2025-11-13 13:40:49');
INSERT INTO `notifications` VALUES ('293','59','New Message from Namz Baer','euuudru','general','read','2025-11-13 13:41:02');
INSERT INTO `notifications` VALUES ('294','28','GG','Namz Baer: ehjrkydhh','general','read','2025-11-13 13:41:30');
INSERT INTO `notifications` VALUES ('295','1','GG','Namz Baer: ehjrkydhh','general','unread','2025-11-13 13:41:30');
INSERT INTO `notifications` VALUES ('296','29','New Message from Liz Uy','ouh','general','read','2025-11-13 13:41:56');
INSERT INTO `notifications` VALUES ('297','29','New Message from Liz Uy','ouh','general','read','2025-11-13 13:41:59');
INSERT INTO `notifications` VALUES ('298','29','New Message from Liz Uy','okay ra. bitaw ko','general','read','2025-11-13 13:42:09');
INSERT INTO `notifications` VALUES ('299','29','New Message from Liz Uy','okay ra. bitaw ko','general','read','2025-11-13 13:42:12');
INSERT INTO `notifications` VALUES ('300','28','New Message from Namz Baer','hahahaa','general','read','2025-11-13 14:04:47');
INSERT INTO `notifications` VALUES ('301','29','New Payment Pending','A new payment of ₱166.67 is pending for Payment for Private Room 01 at Kikyam BH','payment','read','2025-11-13 14:06:18');
INSERT INTO `notifications` VALUES ('302','29','New Booking Request','You have a new booking request from Liz Uy for Private Room 01','booking','read','2025-11-13 14:06:19');
INSERT INTO `notifications` VALUES ('303','28','Payment Status Updated','Your payment of ₱166.67 status has been updated to: Fully Paid','payment','read','2025-11-13 14:06:55');
INSERT INTO `notifications` VALUES ('304','29','Payment Received','Payment of ₱166.67 has been received for Payment for Private Room 01 at Kikyam BH','payment','read','2025-11-13 14:06:55');
INSERT INTO `notifications` VALUES ('305','29','New Payment Pending','A new payment of ₱166.67 is pending for Payment for Private Room 01 at Kikyam BH','payment','read','2025-11-13 14:16:27');
INSERT INTO `notifications` VALUES ('306','29','New Booking Request','You have a new booking request from Liz Uy for Private Room 01','booking','read','2025-11-13 14:16:28');
INSERT INTO `notifications` VALUES ('307','28','Booking Approved','Your booking request for Private Room 01 has been approved!','booking','read','2025-11-13 14:17:23');
INSERT INTO `notifications` VALUES ('308','28','Payment Status Updated','Your payment of ₱166.67 status has been updated to: Fully Paid','payment','read','2025-11-13 14:18:06');
INSERT INTO `notifications` VALUES ('309','29','Payment Received','Payment of ₱166.67 has been received for Payment for Private Room 01 at Kikyam BH','payment','read','2025-11-13 14:18:06');
INSERT INTO `notifications` VALUES ('310','2','Meeting','meeting!!!','announcement','unread','2025-11-15 10:44:03');
INSERT INTO `notifications` VALUES ('311','1','Meeting','meeting!!!','announcement','unread','2025-11-15 10:44:03');
INSERT INTO `notifications` VALUES ('312','4','Meeting','meeting!!!','announcement','unread','2025-11-15 10:44:04');
INSERT INTO `notifications` VALUES ('313','6','Meeting','meeting!!!','announcement','unread','2025-11-15 10:44:04');
INSERT INTO `notifications` VALUES ('314','58','Meeting','meeting!!!','announcement','unread','2025-11-15 10:44:05');
INSERT INTO `notifications` VALUES ('315','27','Meeting','meeting!!!','announcement','unread','2025-11-15 10:44:05');
INSERT INTO `notifications` VALUES ('316','24','Meeting','meeting!!!','announcement','unread','2025-11-15 10:44:05');
INSERT INTO `notifications` VALUES ('317','23','Meeting','meeting!!!','announcement','unread','2025-11-15 10:44:06');
INSERT INTO `notifications` VALUES ('318','28','Meeting','meeting!!!','announcement','read','2025-11-15 10:44:06');
INSERT INTO `notifications` VALUES ('319','29','Meeting','meeting!!!','announcement','read','2025-11-15 10:44:06');
INSERT INTO `notifications` VALUES ('320','35','Meeting','meeting!!!','announcement','read','2025-11-15 10:44:07');
INSERT INTO `notifications` VALUES ('321','36','Meeting','meeting!!!','announcement','unread','2025-11-15 10:44:07');
INSERT INTO `notifications` VALUES ('322','37','Meeting','meeting!!!','announcement','unread','2025-11-15 10:44:08');
INSERT INTO `notifications` VALUES ('323','38','Meeting','meeting!!!','announcement','read','2025-11-15 10:44:08');
INSERT INTO `notifications` VALUES ('324','40','Meeting','meeting!!!','announcement','unread','2025-11-15 10:44:08');
INSERT INTO `notifications` VALUES ('325','44','Meeting','meeting!!!','announcement','read','2025-11-15 10:44:09');
INSERT INTO `notifications` VALUES ('326','59','Meeting','meeting!!!','announcement','read','2025-11-15 10:44:09');
INSERT INTO `notifications` VALUES ('327','62','Meeting','meeting!!!','announcement','unread','2025-11-15 10:44:09');
INSERT INTO `notifications` VALUES ('328','29','New Payment Pending','A new payment of ₱166.67 is pending for Payment for Private Room 01 at Kikyam BH','payment','read','2025-11-15 19:23:27');
INSERT INTO `notifications` VALUES ('329','29','New Booking Request','You have a new booking request from John Sagetarios for Private Room 01','booking','read','2025-11-15 19:23:27');
INSERT INTO `notifications` VALUES ('330','59','Payment Status Updated','Your payment of ₱166.67 status has been updated to: Fully Paid','payment','read','2025-11-15 19:26:12');
INSERT INTO `notifications` VALUES ('331','29','Payment Received','Payment of ₱166.67 has been received for Payment for Private Room 01 at Kikyam BH','payment','read','2025-11-15 19:26:12');
INSERT INTO `notifications` VALUES ('332','29','New Payment Pending','A new payment of ₱166.67 is pending for Payment for Private Room 01 at Kikyam BH','payment','read','2025-11-15 19:31:00');
INSERT INTO `notifications` VALUES ('333','29','New Booking Request','You have a new booking request from John Sagetarios for Private Room 01','booking','read','2025-11-15 19:31:01');
INSERT INTO `notifications` VALUES ('334','59','Booking Approved','Your booking request for Private Room 01 has been checked and approved!','booking','read','2025-11-15 19:47:25');
INSERT INTO `notifications` VALUES ('335','59','Payment Status Updated','Your payment of ₱166.67 status has been updated to: Fully Paid','payment','read','2025-11-15 19:48:05');
INSERT INTO `notifications` VALUES ('336','29','Payment Received','Payment of ₱166.67 has been received for Payment for Private Room 01 at Kikyam BH','payment','read','2025-11-15 19:48:05');
INSERT INTO `notifications` VALUES ('337','29','New Payment Pending','A new payment of ₱166.67 is pending for Payment for Private Room 01 at Kikyam BH','payment','read','2025-11-15 21:09:44');
INSERT INTO `notifications` VALUES ('338','29','New Booking Request','You have a new booking request from Ruel Cuas for Private Room 01','booking','read','2025-11-15 21:09:45');
INSERT INTO `notifications` VALUES ('339','35','Booking Approved','Your booking request for Private Room 01 has been checked and approved!','booking','read','2025-11-15 21:28:20');
INSERT INTO `notifications` VALUES ('340','29','New Payment Pending','A new payment of ₱166.67 is pending for Payment for Private Room 01 at Kikyam BH','payment','read','2025-11-15 21:38:04');
INSERT INTO `notifications` VALUES ('341','29','New Booking Request','You have a new booking request from Ruel Cuas for Private Room 01','booking','read','2025-11-15 21:38:05');
INSERT INTO `notifications` VALUES ('342','35','Booking Approved','Your booking request for Private Room 01 has been checked and approved!','booking','read','2025-11-15 21:39:29');
INSERT INTO `notifications` VALUES ('343','29','New Payment Pending','A new payment of ₱166.67 is pending for Payment for Private Room 01 at Kikyam BH','payment','read','2025-11-15 21:55:19');
INSERT INTO `notifications` VALUES ('344','29','New Booking Request','You have a new booking request from Ruel Cuas for Private Room 01','booking','read','2025-11-15 21:55:20');
INSERT INTO `notifications` VALUES ('345','35','Booking Approved','Your booking request for Private Room 01 has been checked and approved!','booking','read','2025-11-15 21:56:04');
INSERT INTO `notifications` VALUES ('346','29','Payment Received','Payment of ₱166.67 has been received for from boarder for Private Room 01 at Kikyam BH. Status: Fully Paid','payment','read','2025-11-15 21:56:04');
INSERT INTO `notifications` VALUES ('347','35','Payment Status Updated','Your payment of ₱166.67 status has been updated to: Fully Paid','payment','read','2025-11-15 21:56:05');
INSERT INTO `notifications` VALUES ('348','29','New Payment Pending','A new payment of ₱5,000.00 is pending for Payment for Private Room 01 at Kikyam BH','payment','read','2025-11-16 10:47:37');
INSERT INTO `notifications` VALUES ('349','29','New Booking Request','You have a new booking request from John Mark Sagetarios for Private Room 01','booking','read','2025-11-16 10:47:38');
INSERT INTO `notifications` VALUES ('350','38','Booking Approved','Your booking request for Private Room 01 has been checked and approved!','booking','read','2025-11-16 12:28:02');
INSERT INTO `notifications` VALUES ('351','29','Payment Received','Payment of ₱7,500.00 has been received for from boarder for Private Room 01 at Kikyam BH. Status: Fully Paid','payment','read','2025-11-16 12:28:02');
INSERT INTO `notifications` VALUES ('352','38','Payment Status Updated','Your payment of ₱7,500.00 status has been updated to: Fully Paid','payment','read','2025-11-16 12:28:03');
INSERT INTO `notifications` VALUES ('353','29','New Payment Pending','A new payment of ₱5,000.00 is pending for Payment for Private Room 01 at Kikyam BH','payment','read','2025-11-16 12:32:59');
INSERT INTO `notifications` VALUES ('354','29','New Booking Request','You have a new booking request from John Mark Sagetarios for Private Room 01','booking','read','2025-11-16 12:33:00');
INSERT INTO `notifications` VALUES ('355','38','Booking Approved','Your booking request for Private Room 01 has been checked and approved!','booking','read','2025-11-16 12:34:21');
INSERT INTO `notifications` VALUES ('356','29','Payment Received','Payment of ₱12,666.67 has been received for from boarder for Private Room 01 at Kikyam BH. Status: Fully Paid','payment','read','2025-11-16 12:34:21');
INSERT INTO `notifications` VALUES ('357','38','Payment Status Updated','Your payment of ₱12,666.67 status has been updated to: Fully Paid','payment','read','2025-11-16 12:34:22');
INSERT INTO `notifications` VALUES ('358','29','New Payment Pending','A new payment of ₱5,000.00 is pending for Payment for Private Room 01 at Kikyam BH','payment','read','2025-11-16 12:40:50');
INSERT INTO `notifications` VALUES ('359','29','New Booking Request','You have a new booking request from John Mark Sagetarios for Private Room 01','booking','read','2025-11-16 12:40:51');
INSERT INTO `notifications` VALUES ('360','38','Payment Status Updated','Your payment of ₱5,000.00 status has been updated to: Partially Paid','payment','read','2025-11-16 12:41:28');
INSERT INTO `notifications` VALUES ('361','29','Payment Received','Payment of ₱5,000.00 has been received for Payment for Private Room 01 at Kikyam BH','payment','read','2025-11-16 12:41:28');
INSERT INTO `notifications` VALUES ('362','29','New Payment Pending','A new payment of ₱5,000.00 is pending for Payment for Private Room 01 at Kikyam BH','payment','read','2025-11-16 13:03:09');
INSERT INTO `notifications` VALUES ('363','29','New Booking Request','You have a new booking request from John Mark Sagetarios for Private Room 01','booking','read','2025-11-16 13:03:10');
INSERT INTO `notifications` VALUES ('364','38','Booking Approved','Your booking request for Private Room 01 has been checked and approved!','booking','read','2025-11-16 13:03:58');
INSERT INTO `notifications` VALUES ('365','29','Payment Received','Payment of ₱5,000.00 has been received for from boarder for Private Room 01 at Kikyam BH. Status: Partially Paid','payment','read','2025-11-16 13:03:58');
INSERT INTO `notifications` VALUES ('366','38','Payment Status Updated','Your payment of ₱5,000.00 status has been updated to: Partially Paid','payment','read','2025-11-16 13:03:59');
INSERT INTO `notifications` VALUES ('367','29','New Payment Pending','A new payment of ₱5,000.00 is pending for Payment for Private Room 01 at Kikyam BH','payment','read','2025-11-16 13:08:12');
INSERT INTO `notifications` VALUES ('368','29','New Booking Request','You have a new booking request from John Mark Sagetarios for Private Room 01','booking','read','2025-11-16 13:08:13');
INSERT INTO `notifications` VALUES ('369','38','Payment Status Updated','Your payment of ₱5,000.00 status has been updated to: Partially Paid','payment','read','2025-11-16 13:08:52');
INSERT INTO `notifications` VALUES ('370','29','Payment Received','Payment of ₱5,000.00 has been received for Payment for Private Room 01 at Kikyam BH','payment','read','2025-11-16 13:08:52');
INSERT INTO `notifications` VALUES ('371','29','New Payment Pending','A new payment of ₱5,000.00 is pending for Payment for Private Room 01 at Kikyam BH','payment','read','2025-11-16 13:14:37');
INSERT INTO `notifications` VALUES ('372','29','New Booking Request','You have a new booking request from Ruel Cuas for Private Room 01','booking','read','2025-11-16 13:14:38');
INSERT INTO `notifications` VALUES ('373','44','Booking Approved','Your booking request for Private Room 01 has been checked and approved!','booking','unread','2025-11-16 13:15:21');
INSERT INTO `notifications` VALUES ('374','29','Payment Received','Payment of ₱5,000.00 has been received for from boarder for Private Room 01 at Kikyam BH. Status: Partially Paid','payment','read','2025-11-16 13:15:21');
INSERT INTO `notifications` VALUES ('375','44','Payment Status Updated','Your payment of ₱5,000.00 status has been updated to: Partially Paid','payment','unread','2025-11-16 13:15:23');
INSERT INTO `notifications` VALUES ('376','29','New Payment Pending','A new payment of ₱166.67 is pending for Payment for Private Room 01 at Kikyam BH','payment','read','2025-11-17 20:31:09');
INSERT INTO `notifications` VALUES ('377','29','New Booking Request','You have a new booking request from Ruel Cuas for Private Room 01','booking','read','2025-11-17 20:31:10');
INSERT INTO `notifications` VALUES ('378','35','Booking Approved','Your booking request for Private Room 01 has been checked and approved!','booking','read','2025-11-17 20:33:32');
INSERT INTO `notifications` VALUES ('379','29','Payment Received','Payment of ₱166.67 has been received for from boarder for Private Room 01 at Kikyam BH. Status: Fully Paid','payment','read','2025-11-17 20:33:33');
INSERT INTO `notifications` VALUES ('380','35','Payment Status Updated','Your payment of ₱166.67 status has been updated to: Fully Paid','payment','read','2025-11-17 20:33:34');
INSERT INTO `notifications` VALUES ('381','28','New Message','New message from Namz Baer: hi','general','read','2025-11-17 20:42:25');
INSERT INTO `notifications` VALUES ('382','2','New Announcement','meeting: meeting','announcement','unread','2025-11-17 20:51:56');
INSERT INTO `notifications` VALUES ('383','1','New Announcement','meeting: meeting','announcement','unread','2025-11-17 20:51:56');
INSERT INTO `notifications` VALUES ('384','4','New Announcement','meeting: meeting','announcement','unread','2025-11-17 20:51:57');
INSERT INTO `notifications` VALUES ('385','6','New Announcement','meeting: meeting','announcement','unread','2025-11-17 20:51:57');
INSERT INTO `notifications` VALUES ('386','58','New Announcement','meeting: meeting','announcement','unread','2025-11-17 20:51:58');
INSERT INTO `notifications` VALUES ('387','27','New Announcement','meeting: meeting','announcement','unread','2025-11-17 20:51:58');
INSERT INTO `notifications` VALUES ('388','24','New Announcement','meeting: meeting','announcement','unread','2025-11-17 20:51:58');
INSERT INTO `notifications` VALUES ('389','23','New Announcement','meeting: meeting','announcement','unread','2025-11-17 20:51:59');
INSERT INTO `notifications` VALUES ('390','28','New Announcement','meeting: meeting','announcement','read','2025-11-17 20:51:59');
INSERT INTO `notifications` VALUES ('391','29','New Announcement','meeting: meeting','announcement','read','2025-11-17 20:51:59');
INSERT INTO `notifications` VALUES ('392','35','New Announcement','meeting: meeting','announcement','read','2025-11-17 20:52:00');
INSERT INTO `notifications` VALUES ('393','36','New Announcement','meeting: meeting','announcement','unread','2025-11-17 20:52:00');
INSERT INTO `notifications` VALUES ('394','37','New Announcement','meeting: meeting','announcement','unread','2025-11-17 20:52:02');
INSERT INTO `notifications` VALUES ('395','38','New Announcement','meeting: meeting','announcement','read','2025-11-17 20:52:02');
INSERT INTO `notifications` VALUES ('396','40','New Announcement','meeting: meeting','announcement','unread','2025-11-17 20:52:02');
INSERT INTO `notifications` VALUES ('397','44','New Announcement','meeting: meeting','announcement','unread','2025-11-17 20:52:03');
INSERT INTO `notifications` VALUES ('398','59','New Announcement','meeting: meeting','announcement','read','2025-11-17 20:52:03');
INSERT INTO `notifications` VALUES ('399','62','New Announcement','meeting: meeting','announcement','unread','2025-11-17 20:52:03');
INSERT INTO `notifications` VALUES ('400','28','New Message','New message from Namz Baer: h**lo','general','read','2025-11-17 20:52:53');
INSERT INTO `notifications` VALUES ('401','28','New Message','New message from Namz Baer: hii','general','read','2025-11-17 20:53:03');
INSERT INTO `notifications` VALUES ('402','6','New Announcement','Meeting: Meeting','announcement','unread','2025-11-17 20:54:12');
INSERT INTO `notifications` VALUES ('403','27','New Announcement','Meeting: Meeting','announcement','unread','2025-11-17 20:54:15');
INSERT INTO `notifications` VALUES ('404','24','New Announcement','Meeting: Meeting','announcement','unread','2025-11-17 20:54:15');
INSERT INTO `notifications` VALUES ('405','29','New Announcement','Meeting: Meeting','announcement','read','2025-11-17 20:54:17');
INSERT INTO `notifications` VALUES ('406','36','New Announcement','Meeting: Meeting','announcement','unread','2025-11-17 20:54:18');
INSERT INTO `notifications` VALUES ('407','37','New Announcement','Meeting: Meeting','announcement','unread','2025-11-17 20:54:20');
INSERT INTO `notifications` VALUES ('408','40','New Announcement','Meeting: Meeting','announcement','unread','2025-11-17 20:54:20');
INSERT INTO `notifications` VALUES ('409','29','New Message','New message from Ruel Cuas: hi','general','read','2025-11-17 20:56:46');
INSERT INTO `notifications` VALUES ('410','29','New Message','New message from Ruel Cuas: hi','general','read','2025-11-17 20:56:49');
INSERT INTO `notifications` VALUES ('411','29','New Message','New message from John Sagetarios: hi','general','read','2025-11-17 20:58:27');
INSERT INTO `notifications` VALUES ('412','29','New Message','New message from John Sagetarios: hi','general','read','2025-11-17 20:58:29');
INSERT INTO `notifications` VALUES ('413','59','New Message','New message from Namz Baer: hi!','general','read','2025-11-17 21:02:39');
INSERT INTO `notifications` VALUES ('414','2','New Announcement','meeting: meeting','announcement','unread','2025-11-17 21:04:06');
INSERT INTO `notifications` VALUES ('415','1','New Announcement','meeting: meeting','announcement','unread','2025-11-17 21:04:06');
INSERT INTO `notifications` VALUES ('416','4','New Announcement','meeting: meeting','announcement','unread','2025-11-17 21:04:07');
INSERT INTO `notifications` VALUES ('417','58','New Announcement','meeting: meeting','announcement','unread','2025-11-17 21:04:07');
INSERT INTO `notifications` VALUES ('418','23','New Announcement','meeting: meeting','announcement','unread','2025-11-17 21:04:07');
INSERT INTO `notifications` VALUES ('419','28','New Announcement','meeting: meeting','announcement','read','2025-11-17 21:04:07');
INSERT INTO `notifications` VALUES ('420','35','New Announcement','meeting: meeting','announcement','read','2025-11-17 21:04:07');
INSERT INTO `notifications` VALUES ('421','38','New Announcement','meeting: meeting','announcement','read','2025-11-17 21:04:08');
INSERT INTO `notifications` VALUES ('422','44','New Announcement','meeting: meeting','announcement','unread','2025-11-17 21:04:08');
INSERT INTO `notifications` VALUES ('423','59','New Announcement','meeting: meeting','announcement','read','2025-11-17 21:04:08');
INSERT INTO `notifications` VALUES ('424','62','New Announcement','meeting: meeting','announcement','unread','2025-11-17 21:04:08');
INSERT INTO `notifications` VALUES ('425','29','New Message','New message from John Sagetarios: low','general','read','2025-11-17 21:13:12');
INSERT INTO `notifications` VALUES ('426','29','New Message','New message from John Sagetarios: low','general','read','2025-11-17 21:13:15');
INSERT INTO `notifications` VALUES ('427','35','New Message','New message from Namz Baer: hii','general','read','2025-11-17 21:14:48');
INSERT INTO `notifications` VALUES ('428','35','New Message','New message from Namz Baer: hii','general','read','2025-11-17 21:14:51');
INSERT INTO `notifications` VALUES ('429','35','New Message','New message from Namz Baer: hi','general','read','2025-11-17 21:18:59');
INSERT INTO `notifications` VALUES ('430','35','New Message','New message from Namz Baer: hi','general','read','2025-11-17 21:19:02');
INSERT INTO `notifications` VALUES ('431','29','New Message','New message from John Sagetarios: hii','general','read','2025-11-17 21:19:47');
INSERT INTO `notifications` VALUES ('432','29','New Message','New message from John Sagetarios: hii','general','read','2025-11-17 21:19:49');
INSERT INTO `notifications` VALUES ('433','59','New Message','New message from Namz Baer: hi','general','read','2025-11-17 21:21:55');
INSERT INTO `notifications` VALUES ('434','59','New Message','New message from Namz Baer: hi','general','read','2025-11-17 21:21:57');
INSERT INTO `notifications` VALUES ('435','59','New Message','New message from Namz Baer: hi','general','read','2025-11-17 21:22:45');
INSERT INTO `notifications` VALUES ('436','59','New Message','New message from Namz Baer: hi','general','read','2025-11-17 21:22:48');
INSERT INTO `notifications` VALUES ('437','59','New Message','New message from Namz Baer: hi','general','read','2025-11-17 21:30:35');
INSERT INTO `notifications` VALUES ('438','59','New Message','New message from Namz Baer: hi','general','read','2025-11-17 21:30:37');
INSERT INTO `notifications` VALUES ('439','59','New Message','New message from Namz Baer: hii','general','read','2025-11-17 21:31:09');
INSERT INTO `notifications` VALUES ('440','59','New Message','New message from Namz Baer: hii','general','read','2025-11-17 21:31:12');
INSERT INTO `notifications` VALUES ('441','35','New Message','New message from Namz Baer: low','general','read','2025-11-17 21:34:14');
INSERT INTO `notifications` VALUES ('442','35','New Message','New message from Namz Baer: low','general','read','2025-11-17 21:34:16');
INSERT INTO `notifications` VALUES ('443','35','New Message','New message from Namz Baer: low','general','read','2025-11-17 21:34:45');
INSERT INTO `notifications` VALUES ('444','35','New Message','New message from Namz Baer: low','general','read','2025-11-17 21:34:48');
INSERT INTO `notifications` VALUES ('445','6','New Announcement','meeting: meeting','announcement','unread','2025-11-17 21:37:36');
INSERT INTO `notifications` VALUES ('446','27','New Announcement','meeting: meeting','announcement','unread','2025-11-17 21:37:37');
INSERT INTO `notifications` VALUES ('447','24','New Announcement','meeting: meeting','announcement','unread','2025-11-17 21:37:37');
INSERT INTO `notifications` VALUES ('448','29','New Announcement','meeting: meeting','announcement','read','2025-11-17 21:37:38');
INSERT INTO `notifications` VALUES ('449','36','New Announcement','meeting: meeting','announcement','unread','2025-11-17 21:37:39');
INSERT INTO `notifications` VALUES ('450','37','New Announcement','meeting: meeting','announcement','unread','2025-11-17 21:37:40');
INSERT INTO `notifications` VALUES ('451','40','New Announcement','meeting: meeting','announcement','unread','2025-11-17 21:37:40');
INSERT INTO `notifications` VALUES ('452','35','New Message','New message from Namz Baer: hi','general','read','2025-11-17 21:39:25');
INSERT INTO `notifications` VALUES ('453','35','New Message','New message from Namz Baer: hi','general','read','2025-11-17 21:39:28');
INSERT INTO `notifications` VALUES ('454','35','New Message','New message from Namz Baer: hii','general','read','2025-11-17 21:42:04');
INSERT INTO `notifications` VALUES ('455','35','New Message','New message from Namz Baer: hii','general','read','2025-11-17 21:42:06');
INSERT INTO `notifications` VALUES ('456','35','New Message','New message from Namz Baer: hii','general','read','2025-11-17 21:43:23');
INSERT INTO `notifications` VALUES ('457','35','New Message','New message from Namz Baer: hii','general','read','2025-11-17 21:43:25');
INSERT INTO `notifications` VALUES ('458','59','New Message','New message from Namz Baer: hi','general','read','2025-11-17 21:43:40');
INSERT INTO `notifications` VALUES ('459','59','New Message','New message from Namz Baer: hi','general','read','2025-11-17 21:43:43');
INSERT INTO `notifications` VALUES ('460','29','New Message','New message from Ruel Cuas: hi','general','read','2025-11-17 21:44:50');
INSERT INTO `notifications` VALUES ('461','29','New Message','New message from Ruel Cuas: hi','general','read','2025-11-17 21:44:53');
INSERT INTO `notifications` VALUES ('462','29','New Message','New message from Ruel Cuas: hii','general','read','2025-11-17 21:47:24');
INSERT INTO `notifications` VALUES ('463','29','New Message','New message from Ruel Cuas: hii','general','read','2025-11-17 21:47:27');
INSERT INTO `notifications` VALUES ('464','35','New Message','New message from Namz Baer: hi','general','read','2025-11-17 21:48:16');
INSERT INTO `notifications` VALUES ('465','35','New Message','New message from Namz Baer: hi','general','read','2025-11-17 21:48:19');
INSERT INTO `notifications` VALUES ('466','35','New Message','New message from Namz Baer: hi','general','read','2025-11-17 21:48:25');
INSERT INTO `notifications` VALUES ('467','35','New Message','New message from Namz Baer: hi','general','read','2025-11-17 21:48:28');
INSERT INTO `notifications` VALUES ('468','59','New Message','New message from Namz Baer: hi','general','read','2025-11-17 21:48:47');
INSERT INTO `notifications` VALUES ('469','59','New Message','New message from Namz Baer: hi','general','read','2025-11-17 21:48:50');
INSERT INTO `notifications` VALUES ('470','29','New Message','New message from John Sagetarios: hi','general','read','2025-11-17 21:55:02');
INSERT INTO `notifications` VALUES ('471','29','New Message','New message from John Sagetarios: hi','general','read','2025-11-17 21:55:04');
INSERT INTO `notifications` VALUES ('472','29','New Message','New message from John Sagetarios: hi','general','read','2025-11-17 21:57:59');
INSERT INTO `notifications` VALUES ('473','29','New Message','New message from John Sagetarios: hi','general','read','2025-11-17 21:58:02');
INSERT INTO `notifications` VALUES ('474','29','New Message','New message from John Sagetarios: hi','general','read','2025-11-17 21:59:28');
INSERT INTO `notifications` VALUES ('475','29','New Message','New message from John Sagetarios: hi','general','read','2025-11-17 21:59:31');
INSERT INTO `notifications` VALUES ('476','35','New Message','New message from Namz Baer: good eves','general','read','2025-11-17 22:03:09');
INSERT INTO `notifications` VALUES ('477','35','New Message','New message from Namz Baer: good eves','general','read','2025-11-17 22:03:12');
INSERT INTO `notifications` VALUES ('478','29','New Message','New message from Ruel Cuas: eves','general','read','2025-11-17 22:06:34');
INSERT INTO `notifications` VALUES ('479','29','New Message','New message from Ruel Cuas: eves','general','read','2025-11-17 22:06:36');
INSERT INTO `notifications` VALUES ('480','35','New Message','New message from Namz Baer: eves pud','general','read','2025-11-17 22:07:32');
INSERT INTO `notifications` VALUES ('481','35','New Message','New message from Namz Baer: eves pud','general','read','2025-11-17 22:07:35');
INSERT INTO `notifications` VALUES ('482','35','New Message','New message from Namz Baer: bitaw','general','read','2025-11-17 22:08:08');
INSERT INTO `notifications` VALUES ('483','35','New Message','New message from Namz Baer: bitaw','general','read','2025-11-17 22:08:11');
INSERT INTO `notifications` VALUES ('484','29','New Message','New message from Ruel Cuas: aw maayu','general','read','2025-11-17 22:09:18');
INSERT INTO `notifications` VALUES ('485','29','New Message','New message from Ruel Cuas: aw maayu','general','read','2025-11-17 22:09:20');
INSERT INTO `notifications` VALUES ('486','35','New Message','New message from Namz Baer: nicest','general','read','2025-11-17 22:10:47');
INSERT INTO `notifications` VALUES ('487','35','New Message','New message from Namz Baer: nicest','general','read','2025-11-17 22:10:49');
INSERT INTO `notifications` VALUES ('488','29','New Message','New message from Ruel Cuas: okayssss','general','read','2025-11-17 22:19:17');
INSERT INTO `notifications` VALUES ('489','29','New Message','New message from Ruel Cuas: okayssss','general','read','2025-11-17 22:19:20');
INSERT INTO `notifications` VALUES ('490','35','New Message','New message from Namz Baer: lakaw','general','read','2025-11-17 22:20:07');
INSERT INTO `notifications` VALUES ('491','35','New Message','New message from Namz Baer: lakaw','general','read','2025-11-17 22:20:09');
INSERT INTO `notifications` VALUES ('492','35','New Message','New message from Namz Baer: lakwssss','general','read','2025-11-17 22:20:19');
INSERT INTO `notifications` VALUES ('493','35','New Message','New message from Namz Baer: lakwssss','general','read','2025-11-17 22:20:21');
INSERT INTO `notifications` VALUES ('494','35','New Message','New message from Namz Baer: okaysss','general','read','2025-11-17 22:20:29');
INSERT INTO `notifications` VALUES ('495','35','New Message','New message from Namz Baer: okaysss','general','read','2025-11-17 22:20:31');
INSERT INTO `notifications` VALUES ('496','2','New Announcement','General: General','general','unread','2025-11-17 22:22:05');
INSERT INTO `notifications` VALUES ('497','1','New Announcement','General: General','general','unread','2025-11-17 22:22:05');
INSERT INTO `notifications` VALUES ('498','4','New Announcement','General: General','general','unread','2025-11-17 22:22:06');
INSERT INTO `notifications` VALUES ('499','6','New Announcement','General: General','general','unread','2025-11-17 22:22:06');
INSERT INTO `notifications` VALUES ('500','58','New Announcement','General: General','general','unread','2025-11-17 22:22:07');
INSERT INTO `notifications` VALUES ('501','27','New Announcement','General: General','general','unread','2025-11-17 22:22:07');
INSERT INTO `notifications` VALUES ('502','24','New Announcement','General: General','general','unread','2025-11-17 22:22:07');
INSERT INTO `notifications` VALUES ('503','23','New Announcement','General: General','general','unread','2025-11-17 22:22:08');
INSERT INTO `notifications` VALUES ('504','28','New Announcement','General: General','general','read','2025-11-17 22:22:08');
INSERT INTO `notifications` VALUES ('505','29','New Announcement','General: General','general','read','2025-11-17 22:22:08');
INSERT INTO `notifications` VALUES ('506','35','New Announcement','General: General','general','read','2025-11-17 22:22:09');
INSERT INTO `notifications` VALUES ('507','36','New Announcement','General: General','general','unread','2025-11-17 22:22:10');
INSERT INTO `notifications` VALUES ('508','37','New Announcement','General: General','general','unread','2025-11-17 22:22:11');
INSERT INTO `notifications` VALUES ('509','38','New Announcement','General: General','general','read','2025-11-17 22:22:11');
INSERT INTO `notifications` VALUES ('510','40','New Announcement','General: General','general','unread','2025-11-17 22:22:11');
INSERT INTO `notifications` VALUES ('511','44','New Announcement','General: General','general','unread','2025-11-17 22:22:11');
INSERT INTO `notifications` VALUES ('512','59','New Announcement','General: General','general','read','2025-11-17 22:22:12');
INSERT INTO `notifications` VALUES ('513','62','New Announcement','General: General','general','unread','2025-11-17 22:22:12');
INSERT INTO `notifications` VALUES ('514','6','New Announcement','Meeting: Meeting','announcement','unread','2025-11-17 22:22:43');
INSERT INTO `notifications` VALUES ('515','27','New Announcement','Meeting: Meeting','announcement','unread','2025-11-17 22:22:44');
INSERT INTO `notifications` VALUES ('516','24','New Announcement','Meeting: Meeting','announcement','unread','2025-11-17 22:22:45');
INSERT INTO `notifications` VALUES ('517','29','New Announcement','Meeting: Meeting','announcement','read','2025-11-17 22:22:45');
INSERT INTO `notifications` VALUES ('518','36','New Announcement','Meeting: Meeting','announcement','unread','2025-11-17 22:22:46');
INSERT INTO `notifications` VALUES ('519','37','New Announcement','Meeting: Meeting','announcement','unread','2025-11-17 22:22:47');
INSERT INTO `notifications` VALUES ('520','40','New Announcement','Meeting: Meeting','announcement','unread','2025-11-17 22:22:47');
INSERT INTO `notifications` VALUES ('521','35','New Message','New message from Namz Baer: yes','general','read','2025-11-17 22:37:12');
INSERT INTO `notifications` VALUES ('522','35','New Message','New message from Namz Baer: yes','general','read','2025-11-17 22:37:15');
INSERT INTO `notifications` VALUES ('523','35','New Message','New message from Namz Baer: morning','general','read','2025-11-18 10:32:09');
INSERT INTO `notifications` VALUES ('524','35','New Message','New message from Namz Baer: morning','general','read','2025-11-18 10:32:11');
INSERT INTO `notifications` VALUES ('525','38','Maintenance Status Updated','Maintenance request status updated to: In Progress','maintenance','read','2025-11-18 11:14:56');
INSERT INTO `notifications` VALUES ('526','38','Maintenance Status Updated','Maintenance request status updated to: In Progress','maintenance','read','2025-11-18 11:14:59');
INSERT INTO `notifications` VALUES ('527','35','Maintenance Status Updated','Maintenance request status updated to: Declined','maintenance','read','2025-11-18 11:29:44');
INSERT INTO `notifications` VALUES ('528','35','Maintenance Status Updated','Maintenance request status updated to: Declined','maintenance','read','2025-11-18 11:29:46');
INSERT INTO `notifications` VALUES ('529','59','Maintenance Status Updated','Maintenance request status updated to: In Progress','maintenance','read','2025-11-18 11:39:03');
INSERT INTO `notifications` VALUES ('530','35','Maintenance Status Updated','Maintenance request status updated to: Declined','maintenance','read','2025-11-18 11:39:16');
INSERT INTO `notifications` VALUES ('531','29','New Maintenance Request','A boarder has submitted a maintenance request for Room 2: Damage','maintenance','read','2025-11-18 11:54:11');
INSERT INTO `notifications` VALUES ('532','29','New Maintenance Request','A boarder has submitted a maintenance request for Room 2: Damage','maintenance','read','2025-11-18 11:54:13');
INSERT INTO `notifications` VALUES ('533','29','New Maintenance Request','A boarder has submitted a maintenance request for Room 2: Damage','maintenance','read','2025-11-18 11:54:37');
INSERT INTO `notifications` VALUES ('534','29','New Maintenance Request','A boarder has submitted a maintenance request for Room 2: Damage','maintenance','read','2025-11-18 11:54:40');
INSERT INTO `notifications` VALUES ('535','29','New Maintenance Request','A boarder has submitted a maintenance request for Private Room 01: Damage','maintenance','read','2025-11-18 11:59:12');
INSERT INTO `notifications` VALUES ('536','29','New Maintenance Request','A boarder has submitted a maintenance request for Private Room 01: Damage','maintenance','read','2025-11-18 11:59:15');
INSERT INTO `notifications` VALUES ('537','29','New Maintenance Request','Ruel Dalugdog Cuas has submitted a maintenance request for Private Room 01: Damage','maintenance','read','2025-11-18 12:14:02');
INSERT INTO `notifications` VALUES ('538','35','Maintenance Status Updated','Maintenance request status updated to: In Progress','maintenance','read','2025-11-18 12:15:59');
INSERT INTO `notifications` VALUES ('539','35','Maintenance Completed','Your maintenance request has been completed','maintenance','read','2025-11-18 12:55:37');
INSERT INTO `notifications` VALUES ('540','35','Maintenance Status Updated','Maintenance request status updated to: In Progress','maintenance','read','2025-11-18 13:55:59');
INSERT INTO `notifications` VALUES ('541','35','Maintenance Completed','Your maintenance request has been completed','maintenance','read','2025-11-18 13:56:28');
INSERT INTO `notifications` VALUES ('542','35','Maintenance Status Updated','Maintenance request status updated to: In Progress','maintenance','read','2025-11-18 14:03:45');
INSERT INTO `notifications` VALUES ('543','59','Maintenance Status Updated','Maintenance request status updated to: Declined','maintenance','read','2025-11-18 14:03:55');
INSERT INTO `notifications` VALUES ('544','35','Maintenance Completed','Your maintenance request has been completed','maintenance','read','2025-11-18 14:05:13');
INSERT INTO `notifications` VALUES ('545','59','Maintenance Status Updated','Maintenance request status updated to: Declined','maintenance','read','2025-11-18 14:09:44');
INSERT INTO `notifications` VALUES ('546','59','Maintenance Status Updated','Maintenance request status updated to: In Progress','maintenance','read','2025-11-18 14:09:58');
INSERT INTO `notifications` VALUES ('547','59','Maintenance Completed','Your maintenance request has been completed','maintenance','read','2025-11-18 14:10:13');
INSERT INTO `notifications` VALUES ('548','59','Maintenance Status Updated','Maintenance request status updated to: In Progress','maintenance','read','2025-11-18 14:14:20');
INSERT INTO `notifications` VALUES ('549','59','Maintenance Completed','Your maintenance request has been completed','maintenance','read','2025-11-18 14:14:32');
INSERT INTO `notifications` VALUES ('550','59','Maintenance Completed','Your maintenance request has been completed','maintenance','read','2025-11-18 14:18:32');
INSERT INTO `notifications` VALUES ('551','29','New Maintenance Request','Ruel Dalugdog Cuas has submitted a maintenance request for Private Room 01: damage','maintenance','read','2025-11-18 14:21:11');
INSERT INTO `notifications` VALUES ('552','35','Maintenance Status Updated','Maintenance request status updated to: In Progress','maintenance','read','2025-11-18 14:22:22');
INSERT INTO `notifications` VALUES ('553','35','Maintenance Status Updated','Maintenance request status updated to: Declined','maintenance','read','2025-11-18 14:22:40');
INSERT INTO `notifications` VALUES ('554','35','Maintenance Completed','Your maintenance request has been completed','maintenance','read','2025-11-18 14:27:19');
INSERT INTO `notifications` VALUES ('555','29','New Maintenance Request','Ruel Dalugdog Cuas has submitted a maintenance request for Private Room 01: DAMAGEE','maintenance','read','2025-11-18 14:30:16');
INSERT INTO `notifications` VALUES ('556','35','Maintenance Status Updated','Maintenance request status updated to: In Progress','maintenance','read','2025-11-18 14:30:47');
INSERT INTO `notifications` VALUES ('557','35','Maintenance Status Updated','Maintenance request status updated to: Declined','maintenance','read','2025-11-18 14:31:00');
INSERT INTO `notifications` VALUES ('558','35','Maintenance Completed','Your maintenance request has been completed','maintenance','read','2025-11-18 14:31:15');
INSERT INTO `notifications` VALUES ('559','29','New Maintenance Request','Ruel Dalugdog Cuas has submitted a maintenance request for Private Room 01: Damagee','maintenance','read','2025-11-18 14:40:13');
INSERT INTO `notifications` VALUES ('560','29','New Maintenance Request','Ruel Dalugdog Cuas has submitted a maintenance request for Private Room 01: Vahajaj','maintenance','read','2025-11-18 14:42:47');
INSERT INTO `notifications` VALUES ('561','29','New Maintenance Request','Ruel Dalugdog Cuas has submitted a maintenance request for Private Room 01: Damana','maintenance','read','2025-11-18 14:46:28');
INSERT INTO `notifications` VALUES ('562','29','New Maintenance Request','Ruel Dalugdog Cuas has submitted a maintenance request for Private Room 01: Ajjaaj','maintenance','read','2025-11-18 14:50:14');
INSERT INTO `notifications` VALUES ('563','29','New Maintenance Request','Ruel Dalugdog Cuas has submitted a maintenance request for Private Room 01: Sjhsuw','maintenance','read','2025-11-18 14:54:25');
INSERT INTO `notifications` VALUES ('564','35','Maintenance Status Updated','Maintenance request status updated to: In Progress','maintenance','read','2025-11-18 14:55:29');
INSERT INTO `notifications` VALUES ('565','35','Maintenance Status Updated','Maintenance request status updated to: Declined','maintenance','read','2025-11-18 14:55:42');
INSERT INTO `notifications` VALUES ('566','29','New Payment Pending','A new payment of ₱4,166.67 is pending for Payment for Private Room 01 at Kikyam BH','payment','read','2025-11-18 19:47:29');
INSERT INTO `notifications` VALUES ('567','29','New Booking Request','You have a new booking request from John Mark Sagetarios for Private Room 01','booking','read','2025-11-18 19:47:30');
INSERT INTO `notifications` VALUES ('568','63','Registration Approved','Your registration has been approved! You can now login to your account','','read','2025-11-19 10:28:54');
INSERT INTO `notifications` VALUES ('569','59','Payment Status Updated','Your payment of ₱3,166.67 has been updated to Fully Paid for Room 2','payment','read','2025-11-19 19:39:52');
INSERT INTO `notifications` VALUES ('570','59','Payment Status Updated','Your payment of ₱3,166.67 status has been updated to: Fully Paid','payment','read','2025-11-19 19:39:52');
INSERT INTO `notifications` VALUES ('571','29','Payment Received','Payment of ₱3,166.67 received for Room 2 at BH 1','payment','read','2025-11-19 19:39:53');
INSERT INTO `notifications` VALUES ('572','29','Payment Received','Payment of ₱3,166.67 has been received for Payment for Room 2 at BH 1','payment','read','2025-11-19 19:39:53');
INSERT INTO `notifications` VALUES ('573','38','Booking Approved','Your booking request for Private Room 01 has been checked and approved!','booking','read','2025-11-19 19:48:16');
INSERT INTO `notifications` VALUES ('574','29','Payment Received','Payment of ₱4,166.67 has been received for from boarder for Private Room 01 at Kikyam BH. Status: Fully Paid','payment','read','2025-11-19 19:48:16');
INSERT INTO `notifications` VALUES ('575','38','Payment Status Updated','Your payment of ₱4,166.67 status has been updated to: Fully Paid','payment','read','2025-11-19 19:48:17');
INSERT INTO `notifications` VALUES ('576','38','Payment Status Updated','Your payment of ₱5,000.00 status has been updated to: Partially Paid','payment','read','2025-11-19 19:56:06');
INSERT INTO `notifications` VALUES ('577','29','Payment Received','Payment of ₱5,000.00 has been received for Payment for Private Room 01 at Kikyam BH','payment','read','2025-11-19 19:56:06');
INSERT INTO `notifications` VALUES ('578','29','New Message','New message from John Mark Sagetarios: hi','general','read','2025-11-19 19:59:19');
INSERT INTO `notifications` VALUES ('579','29','New Message','New message from John Mark Sagetarios: hi','general','read','2025-11-19 19:59:21');
INSERT INTO `notifications` VALUES ('580','28','New Message','New message from John Mark Sagetarios: hi','general','read','2025-11-19 19:59:49');
INSERT INTO `notifications` VALUES ('581','35','Maintenance Status Updated','Maintenance request status updated to: In Progress','maintenance','unread','2025-11-19 20:03:20');
INSERT INTO `notifications` VALUES ('582','44','New Message','New message from Namz Baer: hissy','general','unread','2025-11-19 20:43:03');
INSERT INTO `notifications` VALUES ('583','44','New Message','New message from Namz Baer: hi','general','unread','2025-11-19 20:43:18');
INSERT INTO `notifications` VALUES ('584','44','New Message','New message from Namz Baer: kijj','general','unread','2025-11-19 20:43:23');
INSERT INTO `notifications` VALUES ('585','29','New Message','New message from John Mark Sagetarios: hii','general','read','2025-11-19 20:58:11');
INSERT INTO `notifications` VALUES ('586','29','New Message','New message from John Mark Sagetarios: hii','general','read','2025-11-19 20:58:13');
INSERT INTO `notifications` VALUES ('587','59','New Group Message','New message in GGGG from John Mark Sagetarios','general','read','2025-11-19 21:06:22');
INSERT INTO `notifications` VALUES ('588','28','New Group Message','New message in GGGG from John Mark Sagetarios','general','read','2025-11-19 21:06:24');
INSERT INTO `notifications` VALUES ('589','44','New Group Message','New message in GGGG from John Mark Sagetarios','general','unread','2025-11-19 21:06:24');
INSERT INTO `notifications` VALUES ('590','29','New Message','New message from John Mark Sagetarios: H**lo','general','read','2025-11-19 21:19:11');
INSERT INTO `notifications` VALUES ('591','29','New Message','New message from John Mark Sagetarios: H**lo','general','read','2025-11-19 21:19:13');
INSERT INTO `notifications` VALUES ('592','29','New Message','New message from John Mark Sagetarios: yawa','general','read','2025-11-19 21:19:19');
INSERT INTO `notifications` VALUES ('593','29','New Message','New message from John Mark Sagetarios: yawa','general','read','2025-11-19 21:19:22');
INSERT INTO `notifications` VALUES ('594','29','New Message','New message from John Mark Sagetarios: b**o','general','read','2025-11-19 21:19:27');
INSERT INTO `notifications` VALUES ('595','29','New Message','New message from John Mark Sagetarios: b**o','general','read','2025-11-19 21:19:29');
INSERT INTO `notifications` VALUES ('596','29','New Message','New message from John Mark Sagetarios: b**o','general','read','2025-11-19 21:19:41');
INSERT INTO `notifications` VALUES ('597','29','New Message','New message from John Mark Sagetarios: b**o','general','read','2025-11-19 21:19:43');
INSERT INTO `notifications` VALUES ('598','29','New Message','New message from John Mark Sagetarios: jasjsjsjsjbssnbxbxxbxbxbfbfbfbfbfbffbffbfbfbfbfncn','general','read','2025-11-19 21:31:50');
INSERT INTO `notifications` VALUES ('599','29','New Message','New message from John Mark Sagetarios: jasjsjsjsjbssnbxbxxbxbxbfbfbfbfbfbffbffbfbfbfbfncn','general','read','2025-11-19 21:31:53');
INSERT INTO `notifications` VALUES ('600','28','New Message','New message from Namz Baer: Hi','general','read','2025-11-19 21:57:51');
INSERT INTO `notifications` VALUES ('601','2','New Announcement','Meeting: meeting -admin','announcement','unread','2025-11-19 22:13:45');
INSERT INTO `notifications` VALUES ('602','1','New Announcement','Meeting: meeting -admin','announcement','unread','2025-11-19 22:13:45');
INSERT INTO `notifications` VALUES ('603','4','New Announcement','Meeting: meeting -admin','announcement','unread','2025-11-19 22:13:45');
INSERT INTO `notifications` VALUES ('604','6','New Announcement','Meeting: meeting -admin','announcement','unread','2025-11-19 22:13:45');
INSERT INTO `notifications` VALUES ('605','58','New Announcement','Meeting: meeting -admin','announcement','unread','2025-11-19 22:13:46');
INSERT INTO `notifications` VALUES ('606','27','New Announcement','Meeting: meeting -admin','announcement','unread','2025-11-19 22:13:46');
INSERT INTO `notifications` VALUES ('607','24','New Announcement','Meeting: meeting -admin','announcement','unread','2025-11-19 22:13:46');
INSERT INTO `notifications` VALUES ('608','23','New Announcement','Meeting: meeting -admin','announcement','unread','2025-11-19 22:13:47');
INSERT INTO `notifications` VALUES ('609','28','New Announcement','Meeting: meeting -admin','announcement','read','2025-11-19 22:13:47');
INSERT INTO `notifications` VALUES ('610','29','New Announcement','Meeting: meeting -admin','announcement','read','2025-11-19 22:13:47');
INSERT INTO `notifications` VALUES ('611','35','New Announcement','Meeting: meeting -admin','announcement','unread','2025-11-19 22:13:47');
INSERT INTO `notifications` VALUES ('612','36','New Announcement','Meeting: meeting -admin','announcement','unread','2025-11-19 22:13:48');
INSERT INTO `notifications` VALUES ('613','37','New Announcement','Meeting: meeting -admin','announcement','unread','2025-11-19 22:13:48');
INSERT INTO `notifications` VALUES ('614','38','New Announcement','Meeting: meeting -admin','announcement','unread','2025-11-19 22:13:48');
INSERT INTO `notifications` VALUES ('615','44','New Announcement','Meeting: meeting -admin','announcement','unread','2025-11-19 22:13:48');
INSERT INTO `notifications` VALUES ('616','59','New Announcement','Meeting: meeting -admin','announcement','read','2025-11-19 22:13:48');
INSERT INTO `notifications` VALUES ('617','62','New Announcement','Meeting: meeting -admin','announcement','unread','2025-11-19 22:13:49');
INSERT INTO `notifications` VALUES ('618','63','New Announcement','Meeting: meeting -admin','announcement','unread','2025-11-19 22:13:49');
INSERT INTO `notifications` VALUES ('619','38','New Message','New message from Namz Baer: B**o ka','general','unread','2025-11-20 13:16:06');
INSERT INTO `notifications` VALUES ('620','38','New Message','New message from Namz Baer: Putangina mo','general','unread','2025-11-20 13:16:54');
INSERT INTO `notifications` VALUES ('621','38','New Message','New message from Namz Baer: P*******a mo','general','unread','2025-11-20 13:17:17');
INSERT INTO `notifications` VALUES ('622','38','New Message','New message from Namz Baer: Hi','general','unread','2025-11-20 13:17:34');
INSERT INTO `notifications` VALUES ('623','38','New Message','New message from Namz Baer: Hi','general','unread','2025-11-20 13:17:36');
INSERT INTO `notifications` VALUES ('624','2','New Announcement','Meeting: meeting','announcement','unread','2025-11-20 13:19:59');
INSERT INTO `notifications` VALUES ('625','1','New Announcement','Meeting: meeting','announcement','unread','2025-11-20 13:19:59');
INSERT INTO `notifications` VALUES ('626','4','New Announcement','Meeting: meeting','announcement','unread','2025-11-20 13:20:00');
INSERT INTO `notifications` VALUES ('627','6','New Announcement','Meeting: meeting','announcement','unread','2025-11-20 13:20:00');
INSERT INTO `notifications` VALUES ('628','58','New Announcement','Meeting: meeting','announcement','unread','2025-11-20 13:20:01');
INSERT INTO `notifications` VALUES ('629','27','New Announcement','Meeting: meeting','announcement','unread','2025-11-20 13:20:01');
INSERT INTO `notifications` VALUES ('630','24','New Announcement','Meeting: meeting','announcement','unread','2025-11-20 13:20:01');
INSERT INTO `notifications` VALUES ('631','23','New Announcement','Meeting: meeting','announcement','unread','2025-11-20 13:20:02');
INSERT INTO `notifications` VALUES ('632','28','New Announcement','Meeting: meeting','announcement','read','2025-11-20 13:20:02');
INSERT INTO `notifications` VALUES ('633','29','New Announcement','Meeting: meeting','announcement','read','2025-11-20 13:20:02');
INSERT INTO `notifications` VALUES ('634','35','New Announcement','Meeting: meeting','announcement','unread','2025-11-20 13:20:03');
INSERT INTO `notifications` VALUES ('635','36','New Announcement','Meeting: meeting','announcement','unread','2025-11-20 13:20:04');
INSERT INTO `notifications` VALUES ('636','37','New Announcement','Meeting: meeting','announcement','unread','2025-11-20 13:20:05');
INSERT INTO `notifications` VALUES ('637','38','New Announcement','Meeting: meeting','announcement','unread','2025-11-20 13:20:05');
INSERT INTO `notifications` VALUES ('638','44','New Announcement','Meeting: meeting','announcement','unread','2025-11-20 13:20:05');
INSERT INTO `notifications` VALUES ('639','59','New Announcement','Meeting: meeting','announcement','read','2025-11-20 13:20:05');
INSERT INTO `notifications` VALUES ('640','62','New Announcement','Meeting: meeting','announcement','unread','2025-11-20 13:20:06');
INSERT INTO `notifications` VALUES ('641','63','New Announcement','Meeting: meeting','announcement','unread','2025-11-20 13:20:06');
INSERT INTO `notifications` VALUES ('642','29','New Message','New message from John Sagetarios: hi','general','read','2025-11-20 13:21:08');
INSERT INTO `notifications` VALUES ('643','29','New Message','New message from John Sagetarios: hi','general','read','2025-11-20 13:21:11');
INSERT INTO `notifications` VALUES ('644','29','New Message','New message from Liz Uy: Hi','general','read','2025-11-20 14:47:44');
INSERT INTO `notifications` VALUES ('645','29','New Message','New message from Liz Uy: Hi','general','read','2025-11-20 14:47:46');
INSERT INTO `notifications` VALUES ('646','28','New Message','New message from Namz Baer: Hi','general','read','2025-11-20 14:48:33');
INSERT INTO `notifications` VALUES ('647','28','New Message','New message from Namz Baer: Hi','general','read','2025-11-20 14:49:14');
INSERT INTO `notifications` VALUES ('648','28','New Message','New message from Namz Baer: Hi','general','read','2025-11-20 14:49:16');
INSERT INTO `notifications` VALUES ('649','28','New Message','New message from Namz Baer: Hi','general','read','2025-11-20 14:53:26');
INSERT INTO `notifications` VALUES ('650','28','New Message','New message from Namz Baer: Hi','general','read','2025-11-20 14:53:29');
INSERT INTO `notifications` VALUES ('651','28','New Message','New message from Namz Baer: Hikims','general','read','2025-11-20 14:53:40');
INSERT INTO `notifications` VALUES ('652','28','New Message','New message from Namz Baer: Hiiihi po','general','read','2025-11-20 14:54:07');
INSERT INTO `notifications` VALUES ('653','28','New Message','New message from Namz Baer: Hiiihi po','general','read','2025-11-20 14:54:10');
INSERT INTO `notifications` VALUES ('654','28','New Message','New message from Namz Baer: Jhhh','general','read','2025-11-20 14:54:49');
INSERT INTO `notifications` VALUES ('655','28','New Message','New message from Namz Baer: Jhhh','general','read','2025-11-20 14:54:52');
INSERT INTO `notifications` VALUES ('656','29','New Message','New message from Liz Uy: hiii','general','read','2025-11-20 14:55:53');
INSERT INTO `notifications` VALUES ('657','29','New Message','New message from Liz Uy: hiii','general','read','2025-11-20 14:55:56');
INSERT INTO `notifications` VALUES ('658','28','New Message','New message from Namz Baer: His','general','read','2025-11-20 14:56:58');
INSERT INTO `notifications` VALUES ('659','28','New Message','New message from Namz Baer: His','general','read','2025-11-20 14:57:01');
INSERT INTO `notifications` VALUES ('660','29','New Message','New message from Liz Uy: Hi kimmmmm','general','read','2025-11-20 15:10:37');
INSERT INTO `notifications` VALUES ('661','29','New Message','New message from Liz Uy: Hi kimmmmm','general','read','2025-11-20 15:10:40');
INSERT INTO `notifications` VALUES ('662','28','New Message','New message from Namz Baer: uy hallo','general','read','2025-11-20 15:11:06');
INSERT INTO `notifications` VALUES ('663','29','New Message','New message from Liz Uy: Halo','general','read','2025-11-20 18:48:31');
INSERT INTO `notifications` VALUES ('664','29','New Message','New message from Liz Uy: Halo','general','read','2025-11-20 18:48:33');
INSERT INTO `notifications` VALUES ('665','28','New Message','New message from Namz Baer: Hrllo','general','read','2025-11-20 18:49:11');
INSERT INTO `notifications` VALUES ('666','28','New Message','New message from Namz Baer: Hrllo','general','read','2025-11-20 18:49:14');
INSERT INTO `notifications` VALUES ('667','29','? Email Address Changed','Your email address has been successfully changed to christehannamae.cuas@bisu.edu.ph. If you didn\'t make this change, please contact support immediately.','general','read','2025-11-20 19:51:40');
INSERT INTO `notifications` VALUES ('668','29','Email Changed','Your email address has been successfully changed','general','read','2025-11-20 19:51:42');
INSERT INTO `notifications` VALUES ('669','29','? Email Address Changed','Your email address has been successfully changed to namzbaer@gmail.com. If you didn\'t make this change, please contact support immediately.','general','read','2025-11-20 20:02:23');
INSERT INTO `notifications` VALUES ('670','29','Email Changed','Your email address has been successfully changed','general','read','2025-11-20 20:02:25');
INSERT INTO `notifications` VALUES ('671','29','? Email Address Changed','Your email address has been successfully changed to christehannamae.cuas@bisu.edu.ph. If you didn\'t make this change, please contact support immediately.','general','read','2025-11-20 20:06:18');
INSERT INTO `notifications` VALUES ('672','29','Email Changed','Your email address has been successfully changed','general','read','2025-11-20 20:06:20');
INSERT INTO `notifications` VALUES ('673','29','? Email Address Changed','Your email address has been successfully changed to namzbaer@gmail.com. If you didn\'t make this change, please contact support immediately.','general','read','2025-11-20 20:11:15');
INSERT INTO `notifications` VALUES ('674','29','Email Changed','Your email address has been successfully changed','general','read','2025-11-20 20:11:17');
INSERT INTO `notifications` VALUES ('675','29','? Email Address Changed','Your email address has been successfully changed to christehannamae.cuas@bisu.edu.ph. If you didn\'t make this change, please contact support immediately.','general','read','2025-11-20 20:16:31');
INSERT INTO `notifications` VALUES ('676','29','Email Changed','Your email address has been successfully changed','general','read','2025-11-20 20:16:34');
INSERT INTO `notifications` VALUES ('677','29','? Password Changed','Your password has been successfully changed. If you didn\'t make this change, please contact support immediately and reset your password.','general','read','2025-11-20 20:20:52');
INSERT INTO `notifications` VALUES ('678','29','Password Changed','Your password has been successfully changed','general','read','2025-11-20 20:20:55');
INSERT INTO `notifications` VALUES ('679','29','? Password Changed','Your password has been successfully changed. If you didn\'t make this change, please contact support immediately and reset your password.','general','read','2025-11-20 20:24:37');
INSERT INTO `notifications` VALUES ('680','29','Password Changed','Your password has been successfully changed','general','read','2025-11-20 20:24:40');
INSERT INTO `notifications` VALUES ('681','29','? Password Changed','Your password has been successfully changed. If you didn\'t make this change, please contact support immediately and reset your password.','general','read','2025-11-20 20:26:47');
INSERT INTO `notifications` VALUES ('682','29','Password Changed','Your password has been successfully changed','general','read','2025-11-20 20:26:49');
INSERT INTO `notifications` VALUES ('683','29','? Password Changed','Your password has been successfully changed. If you didn\'t make this change, please contact support immediately and reset your password.','general','read','2025-11-20 20:27:20');
INSERT INTO `notifications` VALUES ('684','29','Password Changed','Your password has been successfully changed','general','read','2025-11-20 20:27:22');
INSERT INTO `notifications` VALUES ('685','29','? Email Address Changed','Your email address has been successfully changed to namzbaer@gmail.com. If you didn\'t make this change, please contact support immediately.','general','read','2025-11-20 20:28:02');
INSERT INTO `notifications` VALUES ('686','29','Email Changed','Your email address has been successfully changed','general','read','2025-11-20 20:28:04');
INSERT INTO `notifications` VALUES ('687','28','? Password Changed','Your password has been successfully changed. If you didn\'t make this change, please contact support immediately and reset your password.','general','read','2025-11-20 21:04:46');
INSERT INTO `notifications` VALUES ('688','28','Password Changed','Your password has been successfully changed','general','read','2025-11-20 21:04:49');
INSERT INTO `notifications` VALUES ('689','28','? Password Changed','Your password has been successfully changed. If you didn\'t make this change, please contact support immediately and reset your password.','general','read','2025-11-20 21:05:18');
INSERT INTO `notifications` VALUES ('690','28','Password Changed','Your password has been successfully changed','general','read','2025-11-20 21:05:20');
INSERT INTO `notifications` VALUES ('691','28','? Email Address Changed','Your email address has been successfully changed to christehannamae.cuas@bisu.edu.ph. If you didn\'t make this change, please contact support immediately.','general','read','2025-11-20 21:06:12');
INSERT INTO `notifications` VALUES ('692','28','Email Changed','Your email address has been successfully changed','general','read','2025-11-20 21:06:14');
INSERT INTO `notifications` VALUES ('693','28','? Email Address Changed','Your email address has been successfully changed to hannacuas536@gmail.com. If you didn\'t make this change, please contact support immediately.','general','read','2025-11-20 21:07:15');
INSERT INTO `notifications` VALUES ('694','28','Email Changed','Your email address has been successfully changed','general','read','2025-11-20 21:07:17');
INSERT INTO `notifications` VALUES ('695','28','? Password Changed','Your password has been successfully changed. If you didn\'t make this change, please contact support immediately and reset your password.','general','read','2025-11-20 22:54:10');
INSERT INTO `notifications` VALUES ('696','28','Password Changed','Your password has been successfully changed','general','read','2025-11-20 22:54:12');
INSERT INTO `notifications` VALUES ('697','29','? Email Address Changed','Your email address has been successfully changed to christehannamae.cuas@bisu.edu.ph. If you didn\'t make this change, please contact support immediately.','general','read','2025-11-20 22:58:19');
INSERT INTO `notifications` VALUES ('698','29','Email Changed','Your email address has been successfully changed','general','read','2025-11-20 22:58:21');
INSERT INTO `notifications` VALUES ('699','29','? Email Address Changed','Your email address has been successfully changed to namzbaer@gmail.com. If you didn\'t make this change, please contact support immediately.','general','read','2025-11-20 22:59:12');
INSERT INTO `notifications` VALUES ('700','29','Email Changed','Your email address has been successfully changed','general','read','2025-11-20 22:59:14');
INSERT INTO `notifications` VALUES ('701','28','? Email Address Changed','Your email address has been successfully changed to christehannamae.cuas@bisu.edu.ph. If you didn\'t make this change, please contact support immediately.','general','read','2025-11-20 23:03:24');
INSERT INTO `notifications` VALUES ('702','28','Email Changed','Your email address has been successfully changed','general','read','2025-11-20 23:03:27');
INSERT INTO `notifications` VALUES ('703','28','? Email Address Changed','Your email address has been successfully changed to hannacuas536@gmail.com. If you didn\'t make this change, please contact support immediately.','general','read','2025-11-20 23:04:19');
INSERT INTO `notifications` VALUES ('704','28','Email Changed','Your email address has been successfully changed','general','read','2025-11-20 23:04:21');
INSERT INTO `notifications` VALUES ('705','28','? Password Changed','Your password has been successfully changed. If you didn\'t make this change, please contact support immediately and reset your password.','general','read','2025-11-20 23:04:49');
INSERT INTO `notifications` VALUES ('706','28','Password Changed','Your password has been successfully changed','general','read','2025-11-20 23:04:51');
INSERT INTO `notifications` VALUES ('707','29','? Password Changed','Your password has been successfully changed. If you didn\'t make this change, please contact support immediately and reset your password.','general','read','2025-11-20 23:07:15');
INSERT INTO `notifications` VALUES ('708','29','Password Changed','Your password has been successfully changed','general','read','2025-11-20 23:07:17');
INSERT INTO `notifications` VALUES ('709','29','? Password Changed','Your password has been successfully changed. If you didn\'t make this change, please contact support immediately and reset your password.','general','read','2025-11-20 23:11:28');
INSERT INTO `notifications` VALUES ('710','29','Password Changed','Your password has been successfully changed','general','read','2025-11-20 23:11:30');
INSERT INTO `notifications` VALUES ('711','29','? Password Changed','Your password has been successfully changed. If you didn\'t make this change, please contact support immediately and reset your password.','general','read','2025-11-20 23:13:43');
INSERT INTO `notifications` VALUES ('712','29','Password Changed','Your password has been successfully changed','general','read','2025-11-20 23:13:45');
INSERT INTO `notifications` VALUES ('713','29','? Password Changed','Your password has been successfully changed. If you didn\'t make this change, please contact support immediately and reset your password.','general','read','2025-11-20 23:14:09');
INSERT INTO `notifications` VALUES ('714','29','Password Changed','Your password has been successfully changed','general','read','2025-11-20 23:14:11');
INSERT INTO `notifications` VALUES ('715','29','? Email Address Changed','Your email address has been successfully changed to christehannamae.cuas@bisu.edu.ph. If you didn\'t make this change, please contact support immediately.','general','read','2025-11-20 23:14:48');
INSERT INTO `notifications` VALUES ('716','29','Email Changed','Your email address has been successfully changed','general','read','2025-11-20 23:14:50');
INSERT INTO `notifications` VALUES ('717','29','? Email Address Changed','Your email address has been successfully changed to namzbaer@gmail.com. If you didn\'t make this change, please contact support immediately.','general','read','2025-11-20 23:15:33');
INSERT INTO `notifications` VALUES ('718','29','Email Changed','Your email address has been successfully changed','general','read','2025-11-20 23:15:35');
INSERT INTO `notifications` VALUES ('719','28','? Password Changed','Your password has been successfully changed. If you didn\'t make this change, please contact support immediately and reset your password.','general','read','2025-11-20 23:36:52');
INSERT INTO `notifications` VALUES ('720','28','Password Changed','Your password has been successfully changed','general','read','2025-11-20 23:36:54');
INSERT INTO `notifications` VALUES ('721','28','? Email Address Changed','Your email address has been successfully changed to christehannamae.cuas@bisu.edu.ph. If you didn\'t make this change, please contact support immediately.','general','read','2025-11-20 23:37:25');
INSERT INTO `notifications` VALUES ('722','28','Email Changed','Your email address has been successfully changed','general','read','2025-11-20 23:37:27');
INSERT INTO `notifications` VALUES ('723','28','? Password Changed','Your password has been successfully changed. If you didn\'t make this change, please contact support immediately and reset your password.','general','read','2025-11-20 23:37:46');
INSERT INTO `notifications` VALUES ('724','28','Password Changed','Your password has been successfully changed','general','read','2025-11-20 23:37:48');
INSERT INTO `notifications` VALUES ('725','28','? Email Address Changed','Your email address has been successfully changed to hannacuas536@gmail.com. If you didn\'t make this change, please contact support immediately.','general','read','2025-11-20 23:38:20');
INSERT INTO `notifications` VALUES ('726','28','Email Changed','Your email address has been successfully changed','general','read','2025-11-20 23:38:22');
INSERT INTO `notifications` VALUES ('727','29','New Message','New message from Lizz Uy: Najsjsjs','general','unread','2025-11-21 09:00:44');
INSERT INTO `notifications` VALUES ('728','29','New Message','New message from Lizz Uy: Najsjsjs','general','unread','2025-11-21 09:00:47');
INSERT INTO `notifications` VALUES ('729','28','New Message','New message from Namz Baer: Uyss','general','read','2025-11-21 09:01:03');
INSERT INTO `notifications` VALUES ('730','28','New Message','New message from Namz Baer: Uyss','general','read','2025-11-21 09:01:06');
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_resets`
--

DROP TABLE IF EXISTS `password_resets`;
CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `used` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`),
  KEY `idx_email` (`email`),
  KEY `idx_token` (`token`),
  KEY `idx_expires_at` (`expires_at`)
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `password_resets`
--

LOCK TABLES `password_resets` WRITE;
/*!40000 ALTER TABLE `password_resets` DISABLE KEYS */;
INSERT INTO `password_resets` VALUES ('17','cuasruel028@gmail.com','1d685d53e3d788beb7062671e6dc73fe07a09059321a2221ba1ef036124862ae','2025-11-19 03:02:45','2025-11-19 09:32:45','1');
INSERT INTO `password_resets` VALUES ('28','namzbaer@gmail.com','b1a8f768c02f6e54b0a73eda3d670c35709521b09b6dfd139ae294ec2713eec9','2025-11-20 07:03:09','2025-11-20 13:33:09','0');
/*!40000 ALTER TABLE `password_resets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payment_breakdowns`
--

DROP TABLE IF EXISTS `payment_breakdowns`;
CREATE TABLE `payment_breakdowns` (
  `breakdown_id` int(11) NOT NULL AUTO_INCREMENT,
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`breakdown_id`),
  KEY `booking_id` (`booking_id`),
  KEY `payment_id` (`payment_id`),
  KEY `is_selected` (`is_selected`),
  KEY `is_paid` (`is_paid`),
  KEY `payment_status` (`payment_status`),
  KEY `due_date` (`due_date`),
  KEY `idx_booking_selected` (`booking_id`,`is_selected`),
  KEY `idx_booking_paid` (`booking_id`,`is_paid`),
  KEY `idx_payment_status_due` (`payment_status`,`due_date`),
  KEY `idx_admin_dashboard` (`payment_status`,`due_date`,`is_selected`,`is_paid`),
  CONSTRAINT `fk_breakdown_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_breakdown_payment` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`payment_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=61 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payment_breakdowns`
--

LOCK TABLES `payment_breakdowns` WRITE;
/*!40000 ALTER TABLE `payment_breakdowns` DISABLE KEYS */;
INSERT INTO `payment_breakdowns` VALUES ('17','23','26','month','1','1st month','2025-11-11','2025-12-10','5000.00','1','1','2025-11-11','Paid','2025-11-10 19:22:21','2025-11-10 19:36:18');
INSERT INTO `payment_breakdowns` VALUES ('18','23','56','days','0','19 days','2025-12-11','2025-12-29','3166.67','0','1','2025-12-11','Paid','2025-11-10 19:22:21','2025-11-19 19:39:53');
INSERT INTO `payment_breakdowns` VALUES ('19','24','27','month','1','1st month','2025-11-11','2025-12-10','1000.00','1','1','2025-11-11','Paid','2025-11-10 19:58:57','2025-11-10 20:02:22');
INSERT INTO `payment_breakdowns` VALUES ('20','24','27','month','2','2nd month','2025-12-11','2026-01-09','1000.00','0','0','2025-12-11','Pending','2025-11-10 19:58:57','2025-11-10 19:58:57');
INSERT INTO `payment_breakdowns` VALUES ('21','24','27','days','0','22 days','2026-01-10','2026-01-31','733.33','0','0','2026-01-10','Pending','2025-11-10 19:58:57','2025-11-10 19:58:57');
INSERT INTO `payment_breakdowns` VALUES ('27','30','33','month','1','1st month','2025-11-14','2025-12-13','5000.00','1','1','2025-11-14','Paid','2025-11-13 11:04:04','2025-11-13 11:06:26');
INSERT INTO `payment_breakdowns` VALUES ('28','30','33','month','2','2nd month','2025-12-14','2026-01-12','5000.00','0','0','2025-12-14','Pending','2025-11-13 11:04:04','2025-11-13 11:04:04');
INSERT INTO `payment_breakdowns` VALUES ('29','30','33','month','3','3rd month','2026-01-13','2026-02-11','5000.00','0','0','2026-01-13','Pending','2025-11-13 11:04:04','2025-11-13 11:04:04');
INSERT INTO `payment_breakdowns` VALUES ('30','30','33','month','4','4th month','2026-02-12','2026-03-13','5000.00','0','0','2026-02-12','Pending','2025-11-13 11:04:04','2025-11-13 11:04:04');
INSERT INTO `payment_breakdowns` VALUES ('31','30','33','days','0','8 days','2026-03-14','2026-03-21','1333.33','0','0','2026-03-14','Pending','2025-11-13 11:04:04','2025-11-13 11:04:04');
INSERT INTO `payment_breakdowns` VALUES ('35','34','37','days','0','1 day','2025-11-14','2025-11-14','166.67','1','1','2025-11-14','Paid','2025-11-13 11:47:15','2025-11-13 11:48:39');
INSERT INTO `payment_breakdowns` VALUES ('37','36','39','days','0','1 day','2025-11-14','2025-11-14','166.67','1','1','2025-11-14','Paid','2025-11-13 14:16:25','2025-11-13 14:18:06');
INSERT INTO `payment_breakdowns` VALUES ('39','38','41','days','0','1 day','2025-11-16','2025-11-16','166.67','1','1','2025-11-16','Paid','2025-11-15 19:30:58','2025-11-15 19:48:05');
INSERT INTO `payment_breakdowns` VALUES ('42','41','44','days','0','1 day','2025-11-16','2025-11-16','166.67','1','1','2025-11-16','Paid','2025-11-15 21:55:17','2025-11-15 21:56:02');
INSERT INTO `payment_breakdowns` VALUES ('54','46','49','month','1','1st month','2025-11-17','2025-12-16','5000.00','1','1','2025-11-17','Paid','2025-11-16 13:08:10','2025-11-16 13:08:52');
INSERT INTO `payment_breakdowns` VALUES ('55','46','57','month','2','2nd month','2025-12-17','2026-01-15','5000.00','0','1','2025-12-17','Paid','2025-11-16 13:08:10','2025-11-19 19:56:07');
INSERT INTO `payment_breakdowns` VALUES ('56','46','49','days','0','16 days','2026-01-16','2026-01-31','2666.67','0','0','2026-01-16','Pending','2025-11-16 13:08:10','2025-11-16 13:08:10');
INSERT INTO `payment_breakdowns` VALUES ('59','48','51','days','0','1 day','2025-11-18','2025-11-18','166.67','1','1','2025-11-18','Paid','2025-11-17 20:31:06','2025-11-17 20:33:30');
INSERT INTO `payment_breakdowns` VALUES ('60','49','52','days','0','25 days','2025-11-19','2025-12-13','4166.67','1','1','2025-11-19','Paid','2025-11-18 19:47:27','2025-11-19 19:48:14');
/*!40000 ALTER TABLE `payment_breakdowns` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payments`
--

DROP TABLE IF EXISTS `payments`;
CREATE TABLE `payments` (
  `payment_id` int(11) NOT NULL AUTO_INCREMENT,
  `booking_id` int(11) DEFAULT NULL,
  `bill_id` int(11) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `owner_id` int(11) NOT NULL,
  `payment_amount` decimal(10,2) NOT NULL,
  `payment_method` enum('Cash','GCash','Bank Transfer','Check') NOT NULL DEFAULT 'Cash',
  `payment_proof` text DEFAULT NULL,
  `payment_status` enum('Pending','Partially Paid','Fully Paid','Failed','Refunded') NOT NULL DEFAULT 'Pending',
  `payment_date` datetime NOT NULL DEFAULT current_timestamp(),
  `receipt_url` varchar(500) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `payment_month` varchar(7) NOT NULL,
  `payment_year` int(4) NOT NULL,
  `payment_month_number` int(2) NOT NULL,
  `is_monthly_payment` tinyint(1) NOT NULL DEFAULT 1,
  `total_months_required` int(3) DEFAULT NULL,
  `months_paid` int(3) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`payment_id`),
  KEY `booking_id` (`booking_id`),
  KEY `bill_id` (`bill_id`),
  KEY `user_id` (`user_id`),
  KEY `owner_id` (`owner_id`),
  KEY `payment_status` (`payment_status`),
  KEY `payment_date` (`payment_date`),
  KEY `payment_month` (`payment_month`),
  KEY `payment_year` (`payment_year`),
  KEY `payment_month_number` (`payment_month_number`),
  KEY `idx_payments_user_owner` (`user_id`,`owner_id`),
  KEY `idx_payments_status_date` (`payment_status`,`payment_date`),
  KEY `idx_payments_method` (`payment_method`),
  KEY `idx_payments_monthly_tracking` (`user_id`,`payment_month`,`payment_status`),
  KEY `idx_payments_owner_month` (`owner_id`,`payment_month`,`payment_status`),
  CONSTRAINT `payments_ibfk_3` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `payments_ibfk_4` FOREIGN KEY (`owner_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=58 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

LOCK TABLES `payments` WRITE;
/*!40000 ALTER TABLE `payments` DISABLE KEYS */;
INSERT INTO `payments` VALUES ('26','23',NULL,'59','29','5000.00','GCash','uploads/payment_proofs/payment_proof_23_1762773741.jpg','Fully Paid','2025-11-10 19:22:21',NULL,'Marked as paid by owner','2025-11','2025','11','1',NULL,'1','2025-11-10 19:22:21','2025-11-19 19:39:53');
INSERT INTO `payments` VALUES ('27','24',NULL,'44','29','1000.00','GCash','uploads/payment_proofs/payment_proof_24_1762775937.jpg','Partially Paid','2025-11-10 19:58:57',NULL,'Marked as paid by owner','2025-11','2025','11','1',NULL,'1','2025-11-10 19:58:57','2025-11-16 10:25:31');
INSERT INTO `payments` VALUES ('28','25',NULL,'35','29','166.67','GCash','uploads/payment_proofs/payment_proof_25_1762998070.jpg','Fully Paid','2025-11-13 09:41:10',NULL,'Marked as paid by owner','2025-11','2025','11','0',NULL,'1','2025-11-13 09:41:10','2025-11-13 09:42:56');
INSERT INTO `payments` VALUES ('29','26',NULL,'35','29','166.67','GCash','uploads/payment_proofs/payment_proof_26_1763000868.jpg','Fully Paid','2025-11-13 10:27:49',NULL,'Marked as paid by owner','2025-11','2025','11','0',NULL,'1','2025-11-13 10:27:49','2025-11-13 10:33:49');
INSERT INTO `payments` VALUES ('30','27',NULL,'35','29','166.67','GCash','uploads/payment_proofs/payment_proof_27_1763001771.jpg','Fully Paid','2025-11-13 10:42:51',NULL,'Marked as paid by owner','2025-11','2025','11','0',NULL,'1','2025-11-13 10:42:51','2025-11-13 10:44:18');
INSERT INTO `payments` VALUES ('31','28',NULL,'35','29','166.67','GCash','uploads/payment_proofs/payment_proof_28_1763002438.jpg','Fully Paid','2025-11-13 10:53:58',NULL,'Marked as paid by owner','2025-11','2025','11','0',NULL,'1','2025-11-13 10:53:58','2025-11-13 10:55:29');
INSERT INTO `payments` VALUES ('32','29',NULL,'28','1','510.00','Cash','uploads/payment_proofs/payment_proof_29_1763002801.jpg','Pending','2025-11-13 11:00:01',NULL,NULL,'2025-11','2025','11','0',NULL,'1','2025-11-13 11:00:01','2025-11-13 11:00:01');
INSERT INTO `payments` VALUES ('33','30',NULL,'28','29','5000.00','Cash','uploads/payment_proofs/payment_proof_30_1763003044.jpg','Partially Paid','2025-11-13 11:04:04',NULL,'Marked as paid by owner','2025-11','2025','11','1',NULL,'1','2025-11-13 11:04:04','2025-11-16 10:25:22');
INSERT INTO `payments` VALUES ('34','31',NULL,'35','29','166.67','GCash','uploads/payment_proofs/payment_proof_31_1763003063.jpg','Fully Paid','2025-11-13 11:04:23',NULL,'Marked as paid by owner','2025-11','2025','11','0',NULL,'1','2025-11-13 11:04:23','2025-11-13 11:15:44');
INSERT INTO `payments` VALUES ('35','32',NULL,'35','29','166.67','GCash','uploads/payment_proofs/payment_proof_32_1763004019.jpg','Fully Paid','2025-11-13 11:20:19',NULL,'Marked as paid by owner','2025-11','2025','11','0',NULL,'1','2025-11-13 11:20:19','2025-11-13 11:23:27');
INSERT INTO `payments` VALUES ('36','33',NULL,'35','29','166.67','GCash','uploads/payment_proofs/payment_proof_33_1763004981.jpg','Fully Paid','2025-11-13 11:36:21',NULL,'Marked as paid by owner','2025-11','2025','11','0',NULL,'1','2025-11-13 11:36:21','2025-11-13 11:37:48');
INSERT INTO `payments` VALUES ('37','34',NULL,'35','29','166.67','GCash','uploads/payment_proofs/payment_proof_34_1763005635.jpg','Fully Paid','2025-11-13 11:47:15',NULL,'Marked as paid by owner','2025-11','2025','11','0',NULL,'1','2025-11-13 11:47:15','2025-11-13 11:48:39');
INSERT INTO `payments` VALUES ('38','35',NULL,'28','29','166.67','Cash','uploads/payment_proofs/payment_proof_35_1763013975.jpg','Fully Paid','2025-11-13 14:06:16',NULL,'Marked as paid by owner','2025-11','2025','11','0',NULL,'1','2025-11-13 14:06:16','2025-11-13 14:06:55');
INSERT INTO `payments` VALUES ('39','36',NULL,'28','29','166.67','GCash','uploads/payment_proofs/payment_proof_36_1763014585.jpg','Fully Paid','2025-11-13 14:16:25',NULL,'Marked as paid by owner','2025-11','2025','11','0',NULL,'1','2025-11-13 14:16:25','2025-11-13 14:18:06');
INSERT INTO `payments` VALUES ('40','37',NULL,'59','29','166.67','GCash','uploads/payment_proofs/payment_proof_37_1763205805.jpg','Fully Paid','2025-11-15 19:23:25',NULL,'Marked as paid by owner','2025-11','2025','11','0',NULL,'1','2025-11-15 19:23:25','2025-11-15 19:26:12');
INSERT INTO `payments` VALUES ('41','38',NULL,'59','29','166.67','GCash','uploads/payment_proofs/payment_proof_38_1763206258.jpg','Fully Paid','2025-11-15 19:30:58',NULL,'Marked as paid by owner','2025-11','2025','11','0',NULL,'1','2025-11-15 19:30:58','2025-11-15 19:48:05');
INSERT INTO `payments` VALUES ('44','41',NULL,'35','29','166.67','GCash','uploads/payment_proofs/payment_proof_41_1763214917.jpg','Fully Paid','2025-11-15 21:55:17',NULL,NULL,'2025-11','2025','11','0',NULL,'1','2025-11-15 21:55:17','2025-11-15 21:56:02');
INSERT INTO `payments` VALUES ('49','46',NULL,'38','29','5000.00','GCash','uploads/payment_proofs/payment_proof_46_1763269690.jpg','Partially Paid','2025-11-16 13:08:10',NULL,'Marked as paid by owner','2025-11','2025','11','1',NULL,'1','2025-11-16 13:08:10','2025-11-19 19:56:07');
INSERT INTO `payments` VALUES ('51','48',NULL,'35','29','166.67','GCash','uploads/payment_proofs/payment_proof_48_1763382666.jpg','Fully Paid','2025-11-17 20:31:06',NULL,NULL,'2025-11','2025','11','0',NULL,'1','2025-11-17 20:31:06','2025-11-17 20:33:30');
INSERT INTO `payments` VALUES ('52','49',NULL,'38','29','4166.67','GCash','uploads/payment_proofs/payment_proof_49_1763466447.jpg','Fully Paid','2025-11-18 19:47:27',NULL,NULL,'2025-11','2025','11','0',NULL,'1','2025-11-18 19:47:27','2025-11-19 19:48:14');
INSERT INTO `payments` VALUES ('56','23',NULL,'59','29','3166.67','GCash','uploads/payment_proofs/payment_proof_23_1763552260.jpg','Fully Paid','2025-11-19 19:37:40',NULL,'Marked as paid by owner','2025-11','2025','11','1',NULL,'1','2025-11-19 19:37:40','2025-11-19 19:39:53');
INSERT INTO `payments` VALUES ('57','46',NULL,'38','29','5000.00','GCash','uploads/payment_proofs/payment_proof_46_1763553280.jpg','Partially Paid','2025-11-19 19:54:40',NULL,'Marked as paid by owner','2025-11','2025','11','1',NULL,'1','2025-11-19 19:54:40','2025-11-19 19:56:07');
/*!40000 ALTER TABLE `payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `registration`
--

DROP TABLE IF EXISTS `registration`;
CREATE TABLE `registration` (
  `reg_id` int(11) NOT NULL AUTO_INCREMENT,
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
  `status` enum('Approved','Pending','Declined') NOT NULL DEFAULT 'Pending',
  PRIMARY KEY (`reg_id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=146 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `registration`
--

LOCK TABLES `registration` WRITE;
/*!40000 ALTER TABLE `registration` DISABLE KEYS */;
INSERT INTO `registration` VALUES ('1','Owner','John','Michael','Doe','1985-03-15','09123456789','123 Main Street, Cebu City','john.doe@example.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Driver License',NULL,NULL,'DL123456789',NULL,'09123456789','Approved');
INSERT INTO `registration` VALUES ('2','Owner','Namz','Mm','Baer','2004-09-10','09171234568','Calape, Bohol','namzbaer@gmail.com','$2y$10$Q.RNHpk7eHhoTHZTm2.11.RsRLhF/NbGeFVqUjI02MSTjLe9v9HTO','Passport','front_passport.jpg','back_passport.jpg','ID987654321','uploads/gcash_qr/gcash_qr_1_1759443376.jpg','09925311409','Approved');
INSERT INTO `registration` VALUES ('3','Boarder','Mike','James','Johnson','1998-11-08','09123456791','789 Pine Street, Cebu City','mike.johnson@example.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Student ID',NULL,NULL,'ST123456790',NULL,'09123456791','Approved');
INSERT INTO `registration` VALUES ('4','Owner','Sarah','Elizabeth','Wilson','1982-05-12','09123456792','321 Elm Street, Cebu City','sarah.wilson@example.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Driver License',NULL,NULL,'DL123456791',NULL,'09123456792','Approved');
INSERT INTO `registration` VALUES ('5','Boarder','David','Robert','Brown','1996-09-30','09123456793','654 Maple Avenue, Cebu City','david.brown@example.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Student ID',NULL,NULL,'ST123456792',NULL,'09123456793','Approved');
INSERT INTO `registration` VALUES ('6','Boarder','Lisa','Ann','Davis','1997-12-18','09123456794','987 Cedar Lane, Cebu City','lisa.davis@example.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Student ID',NULL,NULL,'ST123456793',NULL,'09123456794','Approved');
INSERT INTO `registration` VALUES ('7','Owner','Tom','William','Miller','1980-01-25','09123456795','147 Birch Road, Cebu City','tom.miller@example.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Driver License',NULL,NULL,'DL123456792',NULL,'09123456795','Approved');
INSERT INTO `registration` VALUES ('8','Boarder','Emma','Grace','Garcia','1999-04-03','09123456796','258 Spruce Drive, Cebu City','emma.garcia@example.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Student ID',NULL,NULL,'ST123456794',NULL,'09123456796','Approved');
INSERT INTO `registration` VALUES ('65','Owner','John','Michael','Doe','1985-03-15','09123456789','123 Main Street, Cebu City','mae.sam@example.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Driver License',NULL,NULL,'DL123456789',NULL,'09123456789','Approved');
INSERT INTO `registration` VALUES ('66','Boarder','Jane','Marie','Smith','1995-07-22','09123456790','456 Oak Avenue, Cebu City','jane.smith@example.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Student ID',NULL,NULL,'ST123456789',NULL,'09123456790','Approved');
INSERT INTO `registration` VALUES ('67','Boarder','Mike','James','Johnson','1998-11-08','09123456791','789 Pine Street, Cebu City','ru.john@example.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Student ID',NULL,NULL,'ST123456790',NULL,'09123456791','Approved');
INSERT INTO `registration` VALUES ('69','Boarder','David','Robert','Brown','1996-09-30','09123456793','654 Maple Avenue, Cebu City','hash.mon@example.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Student ID',NULL,NULL,'ST123456792',NULL,'09123456793','Approved');
INSERT INTO `registration` VALUES ('70','Boarder','Lisa','Ann','Davis','1997-12-18','09123456794','987 Cedar Lane, Cebu City','am.ko@example.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Student ID',NULL,NULL,'ST123456793',NULL,'09123456794','Approved');
INSERT INTO `registration` VALUES ('71','Owner','Tom','William','Miller','1980-01-25','09123456795','147 Birch Road, Cebu City','ho.lo@example.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Driver License',NULL,NULL,'DL123456792',NULL,'09123456795','Approved');
INSERT INTO `registration` VALUES ('72','Boarder','Emma','Grace','Garcia','1999-04-03','09123456796','258 Spruce Drive, Cebu City','wo.uy@example.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Student ID',NULL,NULL,'ST123456794',NULL,'09123456796','Approved');
INSERT INTO `registration` VALUES ('137','Owner','John','Michael','Doe','1985-03-15','09123456789','123 Main Street, Cebu City','chris.cuas@example.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Driver License',NULL,NULL,'DL123456789',NULL,'09123456789','Approved');
INSERT INTO `registration` VALUES ('138','Boarder','Jane','Marie','Smith','1995-07-22','09123456790','456 Oak Avenue, Cebu City','cam.phpr@example.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Student ID',NULL,NULL,'ST123456789',NULL,'09123456790','Approved');
INSERT INTO `registration` VALUES ('139','Boarder','Mike','James','Johnson','1998-11-08','09123456791','789 Pine Street, Cebu City','ruel.john@example.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Student ID',NULL,NULL,'ST123456790',NULL,'09123456791','Approved');
INSERT INTO `registration` VALUES ('140','Owner','Sarah','Elizabeth','Wilson','1982-05-12','09123456792','321 Elm Street, Cebu City','willy.lon@example.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Driver License',NULL,NULL,'DL123456791',NULL,'09123456792','Approved');
INSERT INTO `registration` VALUES ('142','Boarder','Lisa','Ann','Davis','1997-12-18','09123456794','987 Cedar Lane, Cebu City','amber.ko@example.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Student ID',NULL,NULL,'ST123456793',NULL,'09123456794','Approved');
INSERT INTO `registration` VALUES ('143','Owner','Tom','William','Miller','1980-01-25','09123456795','147 Birch Road, Cebu City','hole.lo@example.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Driver License',NULL,NULL,'DL123456792',NULL,'09123456795','Approved');
INSERT INTO `registration` VALUES ('144','Boarder','Emma','Grace','Garcia','1999-04-03','09123456796','258 Spruce Drive, Cebu City','wolo.uy@example.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Student ID',NULL,NULL,'ST123456794',NULL,'09123456796','Approved');
/*!40000 ALTER TABLE `registration` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `registrations`
--

DROP TABLE IF EXISTS `registrations`;
CREATE TABLE `registrations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `suffix` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_email` (`email`),
  KEY `idx_role` (`role`),
  KEY `idx_status` (`status`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_status_created` (`status`,`created_at`),
  KEY `idx_suffix` (`suffix`)
) ENGINE=InnoDB AUTO_INCREMENT=120 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `registrations`
--

LOCK TABLES `registrations` WRITE;
/*!40000 ALTER TABLE `registrations` DISABLE KEYS */;
INSERT INTO `registrations` VALUES ('1','Boarder','Test',NULL,'User',NULL,NULL,NULL,'test@example.com','test123',NULL,NULL,NULL,'0',NULL,NULL,NULL,'2025-10-06 06:08:09','2025-10-26 13:25:18','approved','1',NULL);
INSERT INTO `registrations` VALUES ('2','Boarder','Test',NULL,'User',NULL,NULL,NULL,'test2@example.com','test123',NULL,NULL,NULL,'0',NULL,NULL,NULL,'2025-10-06 06:16:52','2025-10-26 13:25:18','approved','1',NULL);
INSERT INTO `registrations` VALUES ('3','Boarder','Kimberly Jul','Binag','Mante','2025-10-06','09925311463','Lucob','kimjul@gmail.con','dhdjdkdk','2134546','Driver\'s License','123456789','0','uploads/68e2eed214c01_front.jpg','uploads/68e2eed215235_back.jpg','uploads/68e2eed2153c1_qr.jpg','2025-10-06 06:18:58','2025-10-26 13:25:18','approved','1',NULL);
INSERT INTO `registrations` VALUES ('5','BH Owner','Christe Hanna','Dalugdog','Cuas','2003-10-07','09123456789','Tinibgan, Calape, Bohol','christehanna@gmail.com','namie','09925311463','GSIS e-card','123456789','0','uploads/68e4f3b4e49ab_front.jpg','uploads/68e4f3b4e66be_back.jpg','uploads/68e4f3b4e86af_qr.jpg','2025-10-07 19:04:20','2025-10-26 13:25:18','approved','1',NULL);
INSERT INTO `registrations` VALUES ('8','Boarder','Flora','Oracion','Mante','2004-09-07','09925311463','Lucob, Calape, Bohol','floramante@gmail.com','flora','123456789','SSS ID','123456789','0','uploads/68e4f92302869_front.jpg','uploads/68e4f92304024_back.jpg','uploads/68e4f92305704_qr.jpg','2025-10-07 19:27:31','2025-10-26 13:25:18','approved','1',NULL);
INSERT INTO `registrations` VALUES ('31','BH Owner','Hanna','Dalu','Baer','0000-00-00','09925311409','tini','hanna@gmail.com','$2y$10$PGaMA3PAWMCB8zizQL9GNuML9moOOTo0W2FGHJ/MFeGUvhvn9DrnW','09925311409','PhilID (National ID)','12345678','0','uploads/registrations/68e671d0356d0_front.jpg','uploads/registrations/68e671d035d67_back.jpg','uploads/registrations/68e671d037dbd_qr.jpg','2025-10-08 22:14:40','2025-10-26 13:25:18','approved','1',NULL);
INSERT INTO `registrations` VALUES ('35','BH Owner','Mari','Dalu','Baer','0000-00-00','09925311409','tini','mari@gmail.com','$2y$10$00.1846IMH5PJixoF53O4u2B4lhsoG2gzqqVN0YraZayL/ywf4AB2','09925311409','PhilID (National ID)','12345678','0','uploads/registrations/68e6722a65d31_front.jpg','uploads/registrations/68e6722a664ab_back.jpg','uploads/registrations/68e6722a68582_qr.jpg','2025-10-08 22:16:10','2025-10-26 13:25:18','approved','1',NULL);
INSERT INTO `registrations` VALUES ('42','Boarder','Mama','Mo','Ko','2025-10-08','9929769150','tinibgan','mama@gmail.com','$2y$10$70UDp1ckqdUDq7imWw04u.XX8wYwOgbM3xT7OPaMDxuSwOOtmAfc6','09353549141','PhilID (National ID)','235689','0','uploads/registrations/68e675f4de651_front.jpg','uploads/registrations/68e675f4dedde_back.jpg','uploads/registrations/68e675f4df3f8_qr.jpg','2025-10-08 22:32:20','2025-10-26 13:25:18','approved','1',NULL);
INSERT INTO `registrations` VALUES ('51','Boarder','Lizz','Dela','Uy','2005-10-09','9929769150','Purok 2, Ubayon, Loon, Bohol','hannacuas536@gmail.com','$2y$10$ysgQ4YDI7.7BuzL3D6Wym.PkJHAR.T.cTQ43FM7VdTZEYAmxhovBm','09925314096','PhilID (National ID)','2356890','0','uploads/registrations/68e709409683a_front.jpg','uploads/registrations/68e70940980cc_back.jpg','uploads/registrations/68e709409a367_qr.jpg','2025-10-09 09:00:48','2025-11-20 23:39:53','approved','1',NULL);
INSERT INTO `registrations` VALUES ('53','BH Owner','Namz','Dalug','Baer','2025-10-09','09925311409','Purok 2, Tinibgan, Calape, Bohol','namzbaer@gmail.com','$2y$10$yDUR/8qwfefjwTIDYb9bZOrDTtIuKqFuagu10qfCTTjBluBPF0.tK','09925311409','PhilID (National ID)','2356890','0','uploads/registrations/68e70b7a1a08c_front.jpg','uploads/registrations/68e70b7a1bcd8_back.jpg','uploads/gcash_qr/gcash_qr_29_1762776552.jpg','2025-10-09 09:10:18','2025-11-20 23:15:31','approved','1',NULL);
INSERT INTO `registrations` VALUES ('79','Boarder','Ruel','Dalugdog','Cuas','2025-10-26','09925311409','jskska','cuasruel028@gmail.com','$2y$10$3UDeFprLCHzifOnYvseWie3Tf/GigM2xIY4bHEHQg9ks6HSLYovqm','09925311409','PhilID (National ID)','123456789','0','uploads/registrations/68fdb9b3ad6f2_front.jpg','uploads/registrations/68fdb9b3adcbe_back.jpg','uploads/registrations/68fdb9b3ae3e5_qr.jpg','2025-10-26 14:03:31','2025-11-19 09:33:48','approved','1',NULL);
INSERT INTO `registrations` VALUES ('84','BH Owner','Kimberly','Binag','Mante','2025-10-27','9925311409','lucob','kimjulmante@gmail.com','$2y$10$nibA1zDk6rc1YA0qRGqWjOFZT158iHkTz0hYjcB6nimatAqqCBLEa','09925311409','PhilID (National ID)','123456789','0','uploads/registrations/68feb9ecdee8d_front.jpg','uploads/registrations/68feb9ecdf784_back.jpg','uploads/registrations/68feb9ecdfe7e_qr.jpg','2025-10-27 08:16:44','2025-10-28 20:17:55','approved','1',NULL);
INSERT INTO `registrations` VALUES ('85','BH Owner','Shevic','Rulona','Tacatane','2025-10-27','09925311463','Bentig','mayettacatane@gmail.com','$2y$10$gnziH/TxdrRG8EEcC15Nvu1/QFmI5eAgGekP3KUTzW63MXVA4.g/q','09925311463','Driver\'s License','123456789','0','uploads/registrations/68fecdda9e8de_front.jpg','uploads/registrations/68fecdda9ec38_back.jpg','uploads/registrations/68fecdda9ee9f_qr.jpg','2025-10-27 09:41:46','2025-10-27 09:45:40','approved','1',NULL);
INSERT INTO `registrations` VALUES ('86','Boarder','John Mark','Marimon','Sagetarios','2025-10-27','9929769150','ubayon','johnmark.sagetarios@bisu.edu.ph','$2y$10$as8INj1J.ZXQdZYnR.jvPu7vuzASFr0KMpfLlyE8OqUxPA2ewHYRm','09925311409','PhilID (National ID)','123456789','0','uploads/registrations/68fecfb7dcae8_front.jpg','uploads/registrations/68fecfb7dce3f_back.jpg','uploads/registrations/68fecfb7dd119_qr.jpg','2025-10-27 09:49:43','2025-10-27 09:53:48','approved','1',NULL);
INSERT INTO `registrations` VALUES ('103','Boarder','Ruel','Dalugdog','Cuas','2002-10-31','09925311409','Patag, Tinibgan, Calape, Bohol','lizacuas975@gmail.com','$2y$10$OY/mpPzkbLpZW4v./vIqLe1QnLYGAevcJ9EDbz.Z15bboRV.0f/JG',NULL,'Driver\'s License','20-000299','0','uploads/registrations/69046410b4b70_front.jpg','uploads/registrations/69046410b6e12_back.jpg',NULL,'2025-10-31 15:24:00','2025-10-31 15:25:49','approved','1','Jr.');
INSERT INTO `registrations` VALUES ('105','Boarder','John','Marimon','Sagetarios','2001-11-10','09925311409','Purok 1, Ubayon, Loon, Bohol','johnmarksagetarios114@gmail.com','$2y$10$zf2d0LRgCvpDu8ro31dNbOkKT8FWq52UnFchA.uYLXoNtU1dEjLWO',NULL,'PhilID (National ID)','2938-6034-9840-8726','0','uploads/registrations/6911306302530_front.jpg','uploads/registrations/6911306303503_back.jpg',NULL,'2025-11-10 08:22:59','2025-11-10 08:31:05','approved','1',NULL);
INSERT INTO `registrations` VALUES ('108','Boarder','Liza','Dalugdog','Cuas','1993-11-10','09925311409','Patag, Tinibgan, Calape, Bohol','christecuas947@gmail.com','$2y$10$Qgvj7xVrqkZCVYFb0fX67eBwnMikkPw6d7J6vhVGER/sXpasJfSZ6',NULL,'PhilID (National ID)','2938-6034-9840-8726','0','uploads/registrations/69113cf53cb70_front.jpg','uploads/registrations/69113cf53faf4_back.jpg',NULL,'2025-11-10 09:16:37','2025-11-10 09:18:02','approved','1',NULL);
INSERT INTO `registrations` VALUES ('117','BH Owner','Kim','Ja','Ka','1997-11-19','09925311409','Purok 2, Tinibgan, Calape, Bohol','kikyamnarrates@gmail.com','$2y$10$mxRvRlPtj0e9kfe3ZfNF5OG9Jh3OnHFEfCuvfHrVYYP06QuJ9MFXu','09974593660','PhilID (National ID)','2938-6034-9840-8726','0','uploads/registrations/691d6a5034a5a_front.jpg','uploads/registrations/691d6a5036a4e_back.jpg','uploads/registrations/691d6a50383aa_qr.jpg','2025-11-19 14:57:26','2025-11-19 14:58:43','pending','1',NULL);
/*!40000 ALTER TABLE `registrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reviews`
--

DROP TABLE IF EXISTS `reviews`;
CREATE TABLE `reviews` (
  `review_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `bh_id` int(11) NOT NULL,
  `rating` int(11) NOT NULL CHECK (`rating` between 1 and 5),
  `comment` text DEFAULT NULL,
  `review_created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`review_id`),
  KEY `user_id` (`user_id`),
  KEY `bh_id` (`bh_id`),
  CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`bh_id`) REFERENCES `boarding_houses` (`bh_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reviews`
--

LOCK TABLES `reviews` WRITE;
/*!40000 ALTER TABLE `reviews` DISABLE KEYS */;
INSERT INTO `reviews` VALUES ('1','35','87','5','Goodie','2025-11-17 20:15:55');
INSERT INTO `reviews` VALUES ('2','35','87','4','Jssksjksnsnxbxnxnxbxbxxnxjjzjzzbznnxnznznznzbszbznzkzkzmmzznznznznznznznznznznznznnzznznznznsjajnsnsjssbsbzbzznjzkznnxnxnxnxnxnxnxnxnxnnx','2025-11-18 17:40:04');
/*!40000 ALTER TABLE `reviews` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `room_images`
--

DROP TABLE IF EXISTS `room_images`;
CREATE TABLE `room_images` (
  `image_id` int(11) NOT NULL AUTO_INCREMENT,
  `bhr_id` int(11) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`image_id`),
  KEY `bhr_id` (`bhr_id`),
  CONSTRAINT `room_images_ibfk_1` FOREIGN KEY (`bhr_id`) REFERENCES `boarding_house_rooms` (`bhr_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=48 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `room_images`
--

LOCK TABLES `room_images` WRITE;
/*!40000 ALTER TABLE `room_images` DISABLE KEYS */;
INSERT INTO `room_images` VALUES ('1','10','uploads/room_images/bhr_10_68d262f445462.jpg','2025-09-23 17:05:56');
INSERT INTO `room_images` VALUES ('2','10','uploads/room_images/bhr_10_68d262fa15cca.jpg','2025-09-23 17:06:02');
INSERT INTO `room_images` VALUES ('5','12','uploads/room_images/bhr_12_68d264500d2e7.jpg','2025-09-23 17:11:44');
INSERT INTO `room_images` VALUES ('6','12','uploads/room_images/bhr_12_68d2645213f54.jpg','2025-09-23 17:11:46');
INSERT INTO `room_images` VALUES ('7','13','uploads/room_images/bhr_13_68d2663baa88a.jpg','2025-09-23 17:19:55');
INSERT INTO `room_images` VALUES ('8','13','uploads/room_images/bhr_13_68d26641199f1.jpg','2025-09-23 17:20:01');
INSERT INTO `room_images` VALUES ('9','14','uploads/room_images/bhr_14_68d267b01e555.jpg','2025-09-23 17:26:08');
INSERT INTO `room_images` VALUES ('10','14','uploads/room_images/bhr_14_68d267b584fc2.jpg','2025-09-23 17:26:13');
INSERT INTO `room_images` VALUES ('11','15','uploads/room_images/bhr_15_68d613d60c007.jpg','2025-09-26 12:17:26');
INSERT INTO `room_images` VALUES ('12','15','uploads/room_images/bhr_15_68d613d9984a3.jpg','2025-09-26 12:17:29');
INSERT INTO `room_images` VALUES ('13','16','uploads/room_images/bhr_16_68d7e2cf8821a.jpg','2025-09-27 21:12:47');
INSERT INTO `room_images` VALUES ('14','16','uploads/room_images/bhr_16_68d7e2d424728.jpg','2025-09-27 21:12:52');
INSERT INTO `room_images` VALUES ('15','17','uploads/room_images/bhr_17_68d7e6b19bf68.jpg','2025-09-27 21:29:21');
INSERT INTO `room_images` VALUES ('16','18','uploads/room_images/bhr_18_68d88c5857f0a.jpg','2025-09-28 09:16:08');
INSERT INTO `room_images` VALUES ('17','18','uploads/room_images/bhr_18_68d88c5a94ade.jpg','2025-09-28 09:16:10');
INSERT INTO `room_images` VALUES ('18','19','uploads/room_images/bhr_19_68d88d8c4c62d.jpg','2025-09-28 09:21:16');
INSERT INTO `room_images` VALUES ('19','20','uploads/room_images/bhr_20_68d8c0c487e68.jpg','2025-09-28 12:59:48');
INSERT INTO `room_images` VALUES ('20','21','uploads/room_images/bhr_21_68db38f23eced.jpg','2025-09-30 09:57:06');
INSERT INTO `room_images` VALUES ('21','24','uploads/room_images/bhr_24_68db4eebdb7b1.jpg','2025-09-30 11:30:51');
INSERT INTO `room_images` VALUES ('22','26','uploads/room_images/bhr_26_68db53067ef57.jpg','2025-09-30 11:48:22');
INSERT INTO `room_images` VALUES ('23','24','uploads/room_images/bhr_24_68db58a501697.jpg','2025-09-30 12:12:21');
INSERT INTO `room_images` VALUES ('25','25','uploads/room_images/bhr_25_68db58e79bcc0.jpg','2025-09-30 12:13:27');
INSERT INTO `room_images` VALUES ('26','28','uploads/room_images/bhr_28_68db5bb8a14a3.jpg','2025-09-30 12:25:28');
INSERT INTO `room_images` VALUES ('27','36','uploads/room_images/bhr_36_68db6395ce2b3.jpg','2025-09-30 12:59:01');
INSERT INTO `room_images` VALUES ('28','37','uploads/room_images/bhr_37_68db63dcb314b.jpg','2025-09-30 13:00:12');
INSERT INTO `room_images` VALUES ('29','38','uploads/room_images/bhr_38_68def900cbf5a.jpg','2025-10-03 06:13:20');
INSERT INTO `room_images` VALUES ('30','39','uploads/room_images/bhr_39_68def9665ec5e.jpg','2025-10-03 06:15:02');
INSERT INTO `room_images` VALUES ('31','40','uploads/room_images/bhr_40_68df1e48ad236.jpg','2025-10-03 08:52:24');
INSERT INTO `room_images` VALUES ('32','40','uploads/room_images/bhr_40_68df1e7dacc4c.jpg','2025-10-03 08:53:17');
INSERT INTO `room_images` VALUES ('33','41','uploads/room_images/bhr_41_68df1fb133f47.jpg','2025-10-03 08:58:25');
INSERT INTO `room_images` VALUES ('34','42','uploads/room_images/bhr_42_68df225230698.jpg','2025-10-03 09:09:38');
INSERT INTO `room_images` VALUES ('35','42','uploads/room_images/bhr_42_68df2255d4045.jpg','2025-10-03 09:09:41');
INSERT INTO `room_images` VALUES ('36','42','uploads/room_images/bhr_42_68df22590d022.jpg','2025-10-03 09:09:45');
INSERT INTO `room_images` VALUES ('37','24','uploads/room_images/bhr_24_68e0c3f4a1f17.jpg','2025-10-04 14:51:33');
INSERT INTO `room_images` VALUES ('38','43','uploads/room_images/bhr_43_68e1e2693b73e.jpg','2025-10-05 11:13:45');
INSERT INTO `room_images` VALUES ('39','43','uploads/room_images/bhr_43_68e1e348e5635.jpg','2025-10-05 11:17:28');
INSERT INTO `room_images` VALUES ('40','44','uploads/room_images/bhr_44_68e695f80e080.jpg','2025-10-09 00:48:56');
INSERT INTO `room_images` VALUES ('41','45','uploads/room_images/bhr_45_68e71e33d82fa.jpg','2025-10-09 10:30:11');
INSERT INTO `room_images` VALUES ('42','46','uploads/room_images/bhr_46_68eb253cb2a48.jpg','2025-10-12 11:49:16');
INSERT INTO `room_images` VALUES ('43','47','uploads/room_images/bhr_47_68eb268fd47c6.jpg','2025-10-12 11:54:55');
INSERT INTO `room_images` VALUES ('44','48','uploads/room_images/bhr_48_68fb212184fb8.jpg','2025-10-24 14:48:01');
INSERT INTO `room_images` VALUES ('45','48','uploads/room_images/bhr_48_68fb212431eec.jpg','2025-10-24 14:48:04');
INSERT INTO `room_images` VALUES ('46','49','uploads/room_images/bhr_49_690029cf04c1d.jpg','2025-10-28 10:26:23');
INSERT INTO `room_images` VALUES ('47','46','uploads/room_images/bhr_46_690d556f9c807.jpg','2025-11-07 10:11:59');
/*!40000 ALTER TABLE `room_images` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `room_units`
--

DROP TABLE IF EXISTS `room_units`;
CREATE TABLE `room_units` (
  `room_id` int(11) NOT NULL AUTO_INCREMENT,
  `bhr_id` int(11) NOT NULL,
  `room_number` varchar(50) NOT NULL,
  `status` enum('Available','Occupied','Unavailable') NOT NULL DEFAULT 'Available',
  PRIMARY KEY (`room_id`),
  KEY `bhr_id` (`bhr_id`),
  CONSTRAINT `room_units_ibfk_1` FOREIGN KEY (`bhr_id`) REFERENCES `boarding_house_rooms` (`bhr_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=92 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `room_units`
--

LOCK TABLES `room_units` WRITE;
/*!40000 ALTER TABLE `room_units` DISABLE KEYS */;
INSERT INTO `room_units` VALUES ('1','4','SR-1','Available');
INSERT INTO `room_units` VALUES ('2','4','SR-2','Available');
INSERT INTO `room_units` VALUES ('3','4','SR-3','Available');
INSERT INTO `room_units` VALUES ('4','5','SR-1','Available');
INSERT INTO `room_units` VALUES ('5','5','SR-2','Available');
INSERT INTO `room_units` VALUES ('6','5','SR-3','Available');
INSERT INTO `room_units` VALUES ('7','6','D-1','Available');
INSERT INTO `room_units` VALUES ('8','6','D-2','Available');
INSERT INTO `room_units` VALUES ('9','6','D-3','Available');
INSERT INTO `room_units` VALUES ('10','6','D-4','Available');
INSERT INTO `room_units` VALUES ('11','7','S-1','Available');
INSERT INTO `room_units` VALUES ('12','7','S-2','Available');
INSERT INTO `room_units` VALUES ('13','7','S-3','Available');
INSERT INTO `room_units` VALUES ('14','7','S-4','Available');
INSERT INTO `room_units` VALUES ('15','8','S-1','Available');
INSERT INTO `room_units` VALUES ('16','8','S-2','Available');
INSERT INTO `room_units` VALUES ('17','8','S-3','Available');
INSERT INTO `room_units` VALUES ('18','8','S-4','Available');
INSERT INTO `room_units` VALUES ('19','9','GA-1','Available');
INSERT INTO `room_units` VALUES ('20','9','GA-2','Available');
INSERT INTO `room_units` VALUES ('21','9','GA-3','Available');
INSERT INTO `room_units` VALUES ('22','9','GA-4','Available');
INSERT INTO `room_units` VALUES ('23','9','GA-5','Available');
INSERT INTO `room_units` VALUES ('24','10','S-1','Available');
INSERT INTO `room_units` VALUES ('26','12','D-1','Available');
INSERT INTO `room_units` VALUES ('27','13','D-1','Available');
INSERT INTO `room_units` VALUES ('28','14','GB-1','Available');
INSERT INTO `room_units` VALUES ('29','15','FR-1','Available');
INSERT INTO `room_units` VALUES ('30','15','FR-2','Available');
INSERT INTO `room_units` VALUES ('31','16','S-1','Available');
INSERT INTO `room_units` VALUES ('32','16','S-2','Available');
INSERT INTO `room_units` VALUES ('33','17','SR-1','Available');
INSERT INTO `room_units` VALUES ('34','18','F-1','Available');
INSERT INTO `room_units` VALUES ('35','18','F-2','Available');
INSERT INTO `room_units` VALUES ('36','19','F-1','Available');
INSERT INTO `room_units` VALUES ('37','20','GC-1','Available');
INSERT INTO `room_units` VALUES ('38','21','S-1','Available');
INSERT INTO `room_units` VALUES ('39','22','S-1','Available');
INSERT INTO `room_units` VALUES ('40','23','S-1','Available');
INSERT INTO `room_units` VALUES ('41','24','S-1','Occupied');
INSERT INTO `room_units` VALUES ('42','25','GB-1','Available');
INSERT INTO `room_units` VALUES ('43','26','F-1','Available');
INSERT INTO `room_units` VALUES ('45','28','SA-1','Available');
INSERT INTO `room_units` VALUES ('46','29','S-1','Available');
INSERT INTO `room_units` VALUES ('47','33','S-1','Available');
INSERT INTO `room_units` VALUES ('48','34','S-1','Available');
INSERT INTO `room_units` VALUES ('50','36','S-1','Available');
INSERT INTO `room_units` VALUES ('51','37','S-1','Available');
INSERT INTO `room_units` VALUES ('52','28','SA-2','Available');
INSERT INTO `room_units` VALUES ('53','24','SA-2','Available');
INSERT INTO `room_units` VALUES ('54','24','SA-3','Occupied');
INSERT INTO `room_units` VALUES ('59','38','SR-1','Available');
INSERT INTO `room_units` VALUES ('60','38','SR-2','Available');
INSERT INTO `room_units` VALUES ('61','39','G-1','Available');
INSERT INTO `room_units` VALUES ('62','39','G-2','Available');
INSERT INTO `room_units` VALUES ('63','40','KHAR-1','Occupied');
INSERT INTO `room_units` VALUES ('64','40','KHAR-2','Available');
INSERT INTO `room_units` VALUES ('65','40','KHAR-3','Available');
INSERT INTO `room_units` VALUES ('66','40','KHAR-4','Available');
INSERT INTO `room_units` VALUES ('67','40','KHAR-5','Occupied');
INSERT INTO `room_units` VALUES ('68','40','KHAR-6','Available');
INSERT INTO `room_units` VALUES ('69','40','KHAR-7','Available');
INSERT INTO `room_units` VALUES ('70','40','KHAR-8','Available');
INSERT INTO `room_units` VALUES ('71','40','KHAR-9','Available');
INSERT INTO `room_units` VALUES ('72','40','KHAR-10','Available');
INSERT INTO `room_units` VALUES ('73','40','KHAR-11','Occupied');
INSERT INTO `room_units` VALUES ('74','40','KHAR-12','Available');
INSERT INTO `room_units` VALUES ('75','41','SA-1','Available');
INSERT INTO `room_units` VALUES ('76','42','FR-1','Available');
INSERT INTO `room_units` VALUES ('77','42','FR-2','Available');
INSERT INTO `room_units` VALUES ('78','43','S-1','Available');
INSERT INTO `room_units` VALUES ('79','44','SA-1','Available');
INSERT INTO `room_units` VALUES ('80','45','SA-1','Available');
INSERT INTO `room_units` VALUES ('81','46','SA-1','Occupied');
INSERT INTO `room_units` VALUES ('82','47','GA-1','Occupied');
INSERT INTO `room_units` VALUES ('83','48','R2-1','Occupied');
INSERT INTO `room_units` VALUES ('84','47','GA-2','Available');
INSERT INTO `room_units` VALUES ('85','49','PR0-1','Occupied');
INSERT INTO `room_units` VALUES ('86','49','PR0-2','Occupied');
INSERT INTO `room_units` VALUES ('87','49','PR0-3','Available');
INSERT INTO `room_units` VALUES ('88','49','PR0-4','Available');
INSERT INTO `room_units` VALUES ('89','49','PR0-5','Available');
INSERT INTO `room_units` VALUES ('90','49','PR0-6','Occupied');
INSERT INTO `room_units` VALUES ('91','49','PR0-7','Available');
/*!40000 ALTER TABLE `room_units` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `support_tickets`
--

DROP TABLE IF EXISTS `support_tickets`;
CREATE TABLE `support_tickets` (
  `ticket_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `st_subject` varchar(150) NOT NULL,
  `st_description` text NOT NULL,
  `st_status` enum('Pending','In Progress','Resolved','Closed') NOT NULL DEFAULT 'Pending',
  `st_created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`ticket_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `support_tickets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `support_tickets`
--

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `reg_id` int(11) NOT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `status` enum('Active','Inactive') NOT NULL,
  PRIMARY KEY (`user_id`),
  KEY `reg_id` (`reg_id`)
) ENGINE=InnoDB AUTO_INCREMENT=64 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES ('1','2','uploads/profile_pictures/owner_1_68df20de76361.jpg','Active');
INSERT INTO `users` VALUES ('2','1','profile_john.jpg','Active');
INSERT INTO `users` VALUES ('4','3','profile_mike.jpg','Active');
INSERT INTO `users` VALUES ('5','4','profile_sarah.jpg','Active');
INSERT INTO `users` VALUES ('6','5','profile_david.jpg','Active');
INSERT INTO `users` VALUES ('7','6','profile_lisa.jpg','Active');
INSERT INTO `users` VALUES ('8','7','profile_tom.jpg','Active');
INSERT INTO `users` VALUES ('23','42',NULL,'Active');
INSERT INTO `users` VALUES ('24','35',NULL,'Active');
INSERT INTO `users` VALUES ('25','10',NULL,'Active');
INSERT INTO `users` VALUES ('27','31',NULL,'Active');
INSERT INTO `users` VALUES ('28','51','uploads/profile_pictures/user_28_691dc860174ad.jpg','Active');
INSERT INTO `users` VALUES ('29','53','uploads/profile_pictures/user_29_690c8e63c984b.jpg','Active');
INSERT INTO `users` VALUES ('30','74',NULL,'Active');
INSERT INTO `users` VALUES ('31','75',NULL,'Active');
INSERT INTO `users` VALUES ('32','76',NULL,'Active');
INSERT INTO `users` VALUES ('33','77',NULL,'Active');
INSERT INTO `users` VALUES ('34','78',NULL,'Active');
INSERT INTO `users` VALUES ('35','79',NULL,'Active');
INSERT INTO `users` VALUES ('36','84',NULL,'Active');
INSERT INTO `users` VALUES ('37','85',NULL,'Active');
INSERT INTO `users` VALUES ('38','86',NULL,'Active');
INSERT INTO `users` VALUES ('39','88',NULL,'Active');
INSERT INTO `users` VALUES ('40','89',NULL,'Active');
INSERT INTO `users` VALUES ('41','94',NULL,'Active');
INSERT INTO `users` VALUES ('42','100',NULL,'Active');
INSERT INTO `users` VALUES ('43','101',NULL,'Active');
INSERT INTO `users` VALUES ('44','103',NULL,'Active');
INSERT INTO `users` VALUES ('45','29',NULL,'Active');
INSERT INTO `users` VALUES ('58','8',NULL,'Active');
INSERT INTO `users` VALUES ('59','105',NULL,'Active');
INSERT INTO `users` VALUES ('62','108',NULL,'Active');
INSERT INTO `users` VALUES ('63','113',NULL,'Active');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
COMMIT;
