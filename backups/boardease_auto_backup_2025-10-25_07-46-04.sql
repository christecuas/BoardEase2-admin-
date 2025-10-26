-- Database Backup
-- Generated on: 2025-10-25 07:46:04
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
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `active_boarders`
--

LOCK TABLES `active_boarders` WRITE;
/*!40000 ALTER TABLE `active_boarders` DISABLE KEYS */;
INSERT INTO `active_boarders` VALUES ('5','1','Active','82','85');
INSERT INTO `active_boarders` VALUES ('6','28','Active','81','85');
/*!40000 ALTER TABLE `active_boarders` ENABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=82 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
) ENGINE=InnoDB AUTO_INCREMENT=49 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
) ENGINE=InnoDB AUTO_INCREMENT=86 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
INSERT INTO `boarding_houses` VALUES ('85','29','BH 1','tinibgan,.calape','yy','yy','2','10.00','2023','Active','2025-10-12 11:48:57');
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bookings`
--

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
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `device_tokens`
--

LOCK TABLES `device_tokens` WRITE;
/*!40000 ALTER TABLE `device_tokens` DISABLE KEYS */;
INSERT INTO `device_tokens` VALUES ('10','1','doIZWxHNRkqo_lVUVcNn6a:APA91bGvBwcxisdLz9oNw6CJB1gKSaqz0HmNSLqgOfua9_R_X97IWRIas6HSV0CS4m1LoSMwI2bX959PyMn-vDmxy2K8yIkptrFx8nyzNyaWib5IYH3-0PM','android','1.0.0','0','2025-10-09 10:53:46','2025-10-09 11:02:48');
INSERT INTO `device_tokens` VALUES ('11','1','cfE4VW8eRFeGZjIiX1nWoi:APA91bFpYILFXsXlM5oOcoDbaAPtoUsFq2ylML7OG4kOajLO72qOziZY5jscHR5VDAkpmM8FTZUhdbitQxUaYFPqdBcUQPB-slJWrrz5thBNus6J380csCQ','android','1.0.0','0','2025-10-09 11:02:48','2025-10-09 11:05:51');
INSERT INTO `device_tokens` VALUES ('12','1','cvivWukjRtuy1HWtqnBvZC:APA91bG-4_hUVl1_ElHRbEthGqwOuuGMUwTveK3bYNG-GXYPxXQQeRoQ2SJxmM_coHNE7YCJXRiiLGJyaKcMwYsbxmzxbIRbblxWsOpwSdnU3oAukVHG45I','android','1.0.0','1','2025-10-09 11:05:51','2025-10-09 11:05:51');
INSERT INTO `device_tokens` VALUES ('13','29','cvivWukjRtuy1HWtqnBvZC:APA91bG-4_hUVl1_ElHRbEthGqwOuuGMUwTveK3bYNG-GXYPxXQQeRoQ2SJxmM_coHNE7YCJXRiiLGJyaKcMwYsbxmzxbIRbblxWsOpwSdnU3oAukVHG45I','android','1.0.0','0','2025-10-12 11:33:02','2025-10-12 13:10:07');
INSERT INTO `device_tokens` VALUES ('14','24','cvivWukjRtuy1HWtqnBvZC:APA91bG-4_hUVl1_ElHRbEthGqwOuuGMUwTveK3bYNG-GXYPxXQQeRoQ2SJxmM_coHNE7YCJXRiiLGJyaKcMwYsbxmzxbIRbblxWsOpwSdnU3oAukVHG45I','android','1.0.0','0','2025-10-12 12:36:07','2025-10-14 10:24:32');
INSERT INTO `device_tokens` VALUES ('15','6','cvivWukjRtuy1HWtqnBvZC:APA91bG-4_hUVl1_ElHRbEthGqwOuuGMUwTveK3bYNG-GXYPxXQQeRoQ2SJxmM_coHNE7YCJXRiiLGJyaKcMwYsbxmzxbIRbblxWsOpwSdnU3oAukVHG45I','android','1.0.0','1','2025-10-12 12:37:15','2025-10-12 12:37:15');
INSERT INTO `device_tokens` VALUES ('16','29','f4s7iqzjRtiPhdh0hIia0t:APA91bEhK5oDk51TwRrtatuoJ1kRW7yPve8zhJ-Fi1NAhFwXJfPv-uVQ76rCTe1SPUxbWdahWG6Pz1WsiOZlB1cbvAgaG4m-tmlRGmNmQGSKBSIhjPDHOiI','android','1.0.0','0','2025-10-12 13:10:07','2025-10-12 13:25:49');
INSERT INTO `device_tokens` VALUES ('17','29','cLsLWCccSKKVeX-J0jNLY2:APA91bHs8noetyjaDSli4BhNW1-d6_IjUBjxg2p4sIc5yonRjsh8llOelWp50fiAo__dToRGpm6hDiTTAaGONxqi7vD3fP8qcEFiMxwpCZjtJbvhNqptlhU','android','1.0.0','0','2025-10-12 13:25:49','2025-10-12 13:38:13');
INSERT INTO `device_tokens` VALUES ('18','29','dAXDgbwuQLyxAEpSsU24Am:APA91bHtj93rIkmbpb5x7f5WszdR1eM5929L-cTWkwrk_d4Qkpq8ZR939K48_ruM07BTmIhYscW6_r4xSvYi-3iOo2ehnXWcV0HBbQ9usaRwV1bbXxxS1Ak','android','1.0.0','1','2025-10-12 13:38:13','2025-10-25 08:04:19');
INSERT INTO `device_tokens` VALUES ('19','24','dAXDgbwuQLyxAEpSsU24Am:APA91bHtj93rIkmbpb5x7f5WszdR1eM5929L-cTWkwrk_d4Qkpq8ZR939K48_ruM07BTmIhYscW6_r4xSvYi-3iOo2ehnXWcV0HBbQ9usaRwV1bbXxxS1Ak','android','1.0.0','1','2025-10-14 10:24:32','2025-10-14 10:24:32');
INSERT INTO `device_tokens` VALUES ('20','29','f7SS5GQyRL6yFRqlf10SZ9:APA91bHDlsLELpVloaU2Dz97xSIgK2wJnUihuPhwGGCAgTSQSPXZdKOvyHmVkMbIcQj-ETALUG_cJLhiJzQ302Xf4sZFvWT_TtoOnWJQSRedsHJj0Zkl-zw','android','1.0.0','1','2025-10-24 15:10:22','2025-10-25 07:23:35');
INSERT INTO `device_tokens` VALUES ('21','29','eLd7YhTVRHqp7J75n5t0y3:APA91bF4ovvMnFaHY7IeMoxWGjJRiR4tYAPL-jEDDTh2kGClJLkKH6OZISQeb5YEbtpyLAx_0mWIzpDfVfkWtLxeGUusP8ShvKkVMmaS3WBkxplNaTFSP2c','android','1.0.0','1','2025-10-25 07:14:44','2025-10-25 07:14:44');
/*!40000 ALTER TABLE `device_tokens` ENABLE KEYS */;
UNLOCK TABLES;

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
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `group_members`
--

LOCK TABLES `group_members` WRITE;
/*!40000 ALTER TABLE `group_members` DISABLE KEYS */;
INSERT INTO `group_members` VALUES ('1','11','28','','2025-10-14 11:58:45');
INSERT INTO `group_members` VALUES ('2','11','1','','2025-10-14 11:58:45');
INSERT INTO `group_members` VALUES ('3','11','29','','2025-10-14 11:58:45');
INSERT INTO `group_members` VALUES ('4','12','28','','2025-10-14 12:00:05');
INSERT INTO `group_members` VALUES ('5','12','1','','2025-10-14 12:00:05');
INSERT INTO `group_members` VALUES ('6','12','29','','2025-10-14 12:00:05');
INSERT INTO `group_members` VALUES ('7','13','28','','2025-10-14 15:24:42');
INSERT INTO `group_members` VALUES ('8','13','1','','2025-10-14 15:24:42');
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
) ENGINE=InnoDB AUTO_INCREMENT=34 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
/*!40000 ALTER TABLE `group_messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `maintenance_requests`
--

DROP TABLE IF EXISTS `maintenance_requests`;
CREATE TABLE `maintenance_requests` (
  `request_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `mr_description` text NOT NULL,
  `mr_status` enum('Pending','In Progress','Resolved') NOT NULL DEFAULT 'Pending',
  `mr_created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`request_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `maintenance_requests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `maintenance_requests`
--

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
) ENGINE=InnoDB AUTO_INCREMENT=223 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
INSERT INTO `messages` VALUES ('222','28','29','s**t','2025-10-25 12:52:54','Sent');
/*!40000 ALTER TABLE `messages` ENABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=164 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
INSERT INTO `notifications` VALUES ('1','1','Test Notification','This is a test notification to verify the system is working.','general','read','2025-10-05 11:32:22');
INSERT INTO `notifications` VALUES ('2','1','Test Notification','This is a test notification to verify the system is working.','general','read','2025-10-05 11:33:01');
INSERT INTO `notifications` VALUES ('3','1','New Booking Request','You have a new booking request from Jane Smith for Room 205','booking','read','2025-10-05 11:33:20');
INSERT INTO `notifications` VALUES ('4','1','Payment Overdue','Your payment of ₱3,000.00 for Monthly Rent - December 2024 is overdue.','payment','read','2025-10-05 11:33:21');
INSERT INTO `notifications` VALUES ('5','1','Maintenance Completed','Maintenance for Elevator has been completed.','maintenance','read','2025-10-05 11:33:21');
INSERT INTO `notifications` VALUES ('6','1','? URGENT: Fire Drill','Fire drill will be conducted tomorrow at 2:00 PM. Please evacuate the building when the alarm sounds.','announcement','read','2025-10-05 11:33:22');
INSERT INTO `notifications` VALUES ('7','1','Test Notification','This is a test notification to verify the system is working.','general','read','2025-10-05 11:59:21');
INSERT INTO `notifications` VALUES ('8','1','? Badge Test Notification','This notification should show a badge count on your app! Check the notification icon.','general','read','2025-10-05 12:05:59');
INSERT INTO `notifications` VALUES ('9','1','? Badge Test Notification','This notification should show a badge count on your app! Check the notification icon.','general','read','2025-10-05 12:06:46');
INSERT INTO `notifications` VALUES ('10','1','? Badge Test Notification','This notification should show a badge count on your app! Check the notification icon.','general','read','2025-10-05 12:11:39');
INSERT INTO `notifications` VALUES ('11','1','? Badge Test Notification','This notification should show a badge count on your app! Check the notification icon.','general','read','2025-10-05 12:20:49');
INSERT INTO `notifications` VALUES ('12','1','New Test Notification','This is a new test notification to verify the badge system','general','read','2025-10-05 12:24:21');
INSERT INTO `notifications` VALUES ('13','1','New Booking Request','John Doe wants to book Room 101 for next month','booking','read','2025-10-05 12:26:26');
INSERT INTO `notifications` VALUES ('14','1','Payment Received','Payment of ₱3,500 received from Jane Smith','payment','read','2025-10-05 12:26:26');
INSERT INTO `notifications` VALUES ('15','1','Maintenance Alert','Elevator maintenance scheduled for tomorrow','maintenance','read','2025-10-05 12:26:26');
INSERT INTO `notifications` VALUES ('16','1','Important Announcement','Fire drill will be conducted next week','announcement','read','2025-10-05 12:26:26');
INSERT INTO `notifications` VALUES ('17','1','Welcome Message','Welcome to BoardEase! Your account is ready','general','read','2025-10-05 12:26:26');
INSERT INTO `notifications` VALUES ('18','1','? Badge Test Notification','This notification should show a badge count on your app! Check the notification icon.','general','read','2025-10-05 12:28:08');
INSERT INTO `notifications` VALUES ('19','1','? Badge Test Notification','This notification should show a badge count on your app! Check the notification icon.','general','read','2025-10-05 12:28:24');
INSERT INTO `notifications` VALUES ('20','1','New Booking Request','John Doe wants to book Room 101 for next month','booking','read','2025-10-05 12:33:23');
INSERT INTO `notifications` VALUES ('21','1','Payment Received','Payment of ₱3,500 received from Jane Smith','payment','read','2025-10-05 12:33:23');
INSERT INTO `notifications` VALUES ('22','1','Maintenance Alert','Elevator maintenance scheduled for tomorrow','maintenance','read','2025-10-05 12:33:23');
INSERT INTO `notifications` VALUES ('23','1','Important Announcement','Fire drill will be conducted next week','announcement','read','2025-10-05 12:33:23');
INSERT INTO `notifications` VALUES ('24','1','Welcome Message','Welcome to BoardEase! Your account is ready','general','read','2025-10-05 12:33:23');
INSERT INTO `notifications` VALUES ('25','1','Test Notification 1','This is a test notification','general','read','2025-10-05 12:34:21');
INSERT INTO `notifications` VALUES ('26','1','Test Notification 2','Another test notification','booking','read','2025-10-05 12:34:21');
INSERT INTO `notifications` VALUES ('27','1','Test Notification 3','Third test notification','payment','read','2025-10-05 12:34:21');
INSERT INTO `notifications` VALUES ('28','1','? Badge Test Notification','This notification should show a badge count on your app! Check the notification icon.','general','read','2025-10-05 12:35:21');
INSERT INTO `notifications` VALUES ('29','1','New Booking Request','John Doe wants to book Room 101 for next month','booking','read','2025-10-05 12:40:42');
INSERT INTO `notifications` VALUES ('30','1','Payment Received','Payment of ₱3,500 received from Jane Smith','payment','read','2025-10-05 12:40:42');
INSERT INTO `notifications` VALUES ('31','1','Maintenance Alert','Elevator maintenance scheduled for tomorrow','maintenance','read','2025-10-05 12:40:42');
INSERT INTO `notifications` VALUES ('32','1','Important Announcement','Fire drill will be conducted next week','announcement','read','2025-10-05 12:40:42');
INSERT INTO `notifications` VALUES ('33','1','Welcome Message','Welcome to BoardEase! Your account is ready','general','read','2025-10-05 12:40:42');
INSERT INTO `notifications` VALUES ('34','1','? Badge Test Notification','This notification should show a badge count on your app! Check the notification icon.','general','read','2025-10-05 12:41:32');
INSERT INTO `notifications` VALUES ('35','1','New Booking Request','John Doe wants to book Room 101 for next month','booking','read','2025-10-05 12:42:29');
INSERT INTO `notifications` VALUES ('36','1','Payment Received','Payment of ₱3,500 received from Jane Smith','payment','read','2025-10-05 12:42:29');
INSERT INTO `notifications` VALUES ('37','1','Maintenance Alert','Elevator maintenance scheduled for tomorrow','maintenance','read','2025-10-05 12:42:29');
INSERT INTO `notifications` VALUES ('38','1','Important Announcement','Fire drill will be conducted next week','announcement','read','2025-10-05 12:42:29');
INSERT INTO `notifications` VALUES ('39','1','Welcome Message','Welcome to BoardEase! Your account is ready','general','read','2025-10-05 12:42:29');
INSERT INTO `notifications` VALUES ('40','1','? REAL-TIME TEST','This notification was just created to test real-time badge updates!','general','read','2025-10-05 12:48:37');
INSERT INTO `notifications` VALUES ('41','1','New Booking','Someone wants to book your room','booking','read','2025-10-05 12:49:10');
INSERT INTO `notifications` VALUES ('42','1','Payment Alert','Payment received from tenant','payment','read','2025-10-05 12:49:10');
INSERT INTO `notifications` VALUES ('43','1','Maintenance','Elevator needs repair','maintenance','read','2025-10-05 12:49:10');
INSERT INTO `notifications` VALUES ('44','1','? URGENT: Fire Drill','Fire drill scheduled for tomorrow at 2:00 PM','announcement','read','2025-10-05 12:50:16');
INSERT INTO `notifications` VALUES ('45','1','? Payment Received','Payment of ₱5,000 received from John Doe','payment','read','2025-10-05 12:50:16');
INSERT INTO `notifications` VALUES ('46','1','? Maintenance Alert','Elevator maintenance completed','maintenance','read','2025-10-05 12:50:17');
INSERT INTO `notifications` VALUES ('47','1','? New Booking','Jane Smith wants to book Room 201','booking','read','2025-10-05 12:50:17');
INSERT INTO `notifications` VALUES ('48','1','? General Notice','Water supply will be interrupted tomorrow','general','read','2025-10-05 12:50:18');
INSERT INTO `notifications` VALUES ('49','1','? URGENT: Fire Drill','Fire drill scheduled for tomorrow at 2:00 PM','announcement','read','2025-10-05 12:53:21');
INSERT INTO `notifications` VALUES ('50','1','? Payment Received','Payment of ₱5,000 received from John Doe','payment','read','2025-10-05 12:53:21');
INSERT INTO `notifications` VALUES ('51','1','? Maintenance Alert','Elevator maintenance completed','maintenance','read','2025-10-05 12:53:22');
INSERT INTO `notifications` VALUES ('52','1','? New Booking','Jane Smith wants to book Room 201','booking','read','2025-10-05 12:53:22');
INSERT INTO `notifications` VALUES ('53','1','? General Notice','Water supply will be interrupted tomorrow','general','read','2025-10-05 12:53:23');
INSERT INTO `notifications` VALUES ('54','1','? New Message','You have a new message from tenant','general','read','2025-10-05 12:56:04');
INSERT INTO `notifications` VALUES ('55','1','? Payment Alert','Rent payment received','payment','read','2025-10-05 12:56:04');
INSERT INTO `notifications` VALUES ('56','1','? Booking Request','New booking request received','booking','read','2025-10-05 12:56:04');
INSERT INTO `notifications` VALUES ('57','1','✅ Maintenance Complete','Elevator maintenance completed','maintenance','read','2025-10-05 12:56:04');
INSERT INTO `notifications` VALUES ('58','1','? Announcement','Monthly meeting scheduled','announcement','read','2025-10-05 12:56:04');
INSERT INTO `notifications` VALUES ('59','1','? Payment Processed','Utility bill payment processed','payment','read','2025-10-05 12:56:04');
INSERT INTO `notifications` VALUES ('60','1','? Test Notification 1','This is a test notification for debugging','general','read','2025-10-05 13:00:37');
INSERT INTO `notifications` VALUES ('61','1','? Test Payment','Test payment notification','payment','read','2025-10-05 13:00:37');
INSERT INTO `notifications` VALUES ('62','1','? Test Booking','Test booking notification','booking','read','2025-10-05 13:00:37');
INSERT INTO `notifications` VALUES ('63','1','? Test Maintenance','Test maintenance notification','maintenance','read','2025-10-05 13:00:37');
INSERT INTO `notifications` VALUES ('64','1','? Test Announcement','Test announcement notification','announcement','read','2025-10-05 13:00:37');
INSERT INTO `notifications` VALUES ('65','1','? Test Notification 1','This is a test notification for debugging','general','read','2025-10-05 13:03:49');
INSERT INTO `notifications` VALUES ('66','1','? Test Payment','Test payment notification','payment','read','2025-10-05 13:03:49');
INSERT INTO `notifications` VALUES ('67','1','? Test Booking','Test booking notification','booking','read','2025-10-05 13:03:49');
INSERT INTO `notifications` VALUES ('68','1','? Test Maintenance','Test maintenance notification','maintenance','read','2025-10-05 13:03:49');
INSERT INTO `notifications` VALUES ('69','1','? Test Announcement','Test announcement notification','announcement','read','2025-10-05 13:03:49');
INSERT INTO `notifications` VALUES ('70','1','? Test Notification 1','This is a test notification for debugging','general','read','2025-10-05 13:06:51');
INSERT INTO `notifications` VALUES ('71','1','? Test Payment','Test payment notification','payment','read','2025-10-05 13:06:51');
INSERT INTO `notifications` VALUES ('72','1','? Test Booking','Test booking notification','booking','read','2025-10-05 13:06:51');
INSERT INTO `notifications` VALUES ('73','1','? Test Maintenance','Test maintenance notification','maintenance','read','2025-10-05 13:06:51');
INSERT INTO `notifications` VALUES ('74','1','? Test Announcement','Test announcement notification','announcement','read','2025-10-05 13:06:51');
INSERT INTO `notifications` VALUES ('75','1','? Badge Test Notification','This notification should show a badge count on your app! Check the notification icon.','general','read','2025-10-05 13:09:43');
INSERT INTO `notifications` VALUES ('76','1','? NOTIFICATION WITH SOUND','This notification includes sound and FCM push notification! Check your device.','general','read','2025-10-05 13:12:05');
INSERT INTO `notifications` VALUES ('77','1','? Payment Alert with Sound','Payment received! This notification has sound enabled.','payment','read','2025-10-05 13:12:06');
INSERT INTO `notifications` VALUES ('78','1','? Booking Request with Sound','New booking request received with sound notification.','booking','read','2025-10-05 13:12:06');
INSERT INTO `notifications` VALUES ('79','1','? Maintenance Alert with Sound','Maintenance completed with sound notification.','maintenance','read','2025-10-05 13:12:06');
INSERT INTO `notifications` VALUES ('80','1','? Announcement with Sound','Important announcement with sound notification.','announcement','read','2025-10-05 13:12:07');
INSERT INTO `notifications` VALUES ('81','1','? NOTIFICATION WITH SOUND','This notification includes sound and FCM push notification! Check your device.','general','read','2025-10-05 13:14:53');
INSERT INTO `notifications` VALUES ('82','1','? Payment Alert with Sound','Payment received! This notification has sound enabled.','payment','read','2025-10-05 13:14:53');
INSERT INTO `notifications` VALUES ('83','1','? Booking Request with Sound','New booking request received with sound notification.','booking','read','2025-10-05 13:14:54');
INSERT INTO `notifications` VALUES ('84','1','? Maintenance Alert with Sound','Maintenance completed with sound notification.','maintenance','read','2025-10-05 13:14:54');
INSERT INTO `notifications` VALUES ('85','1','? Announcement with Sound','Important announcement with sound notification.','announcement','read','2025-10-05 13:14:54');
INSERT INTO `notifications` VALUES ('86','1','? NOTIFICATION WITH SOUND','This notification includes sound and FCM push notification! Check your device.','general','read','2025-10-05 13:16:41');
INSERT INTO `notifications` VALUES ('87','1','? Payment Alert with Sound','Payment received! This notification has sound enabled.','payment','read','2025-10-05 13:16:41');
INSERT INTO `notifications` VALUES ('88','1','? Booking Request with Sound','New booking request received with sound notification.','booking','read','2025-10-05 13:16:42');
INSERT INTO `notifications` VALUES ('89','1','? Maintenance Alert with Sound','Maintenance completed with sound notification.','maintenance','read','2025-10-05 13:16:42');
INSERT INTO `notifications` VALUES ('90','1','? Announcement with Sound','Important announcement with sound notification.','announcement','read','2025-10-05 13:16:42');
INSERT INTO `notifications` VALUES ('91','1','? NOTIFICATION WITH SOUND','This notification includes sound and FCM push notification! Check your device.','general','read','2025-10-05 13:16:45');
INSERT INTO `notifications` VALUES ('92','1','? Payment Alert with Sound','Payment received! This notification has sound enabled.','payment','read','2025-10-05 13:16:45');
INSERT INTO `notifications` VALUES ('93','1','? Booking Request with Sound','New booking request received with sound notification.','booking','read','2025-10-05 13:16:46');
INSERT INTO `notifications` VALUES ('94','1','? Maintenance Alert with Sound','Maintenance completed with sound notification.','maintenance','read','2025-10-05 13:16:46');
INSERT INTO `notifications` VALUES ('95','1','? Announcement with Sound','Important announcement with sound notification.','announcement','read','2025-10-05 13:16:46');
INSERT INTO `notifications` VALUES ('96','1','? NOTIFICATION WITH SOUND','This notification includes sound and FCM push notification! Check your device.','general','read','2025-10-05 13:17:24');
INSERT INTO `notifications` VALUES ('97','1','? Payment Alert with Sound','Payment received! This notification has sound enabled.','payment','read','2025-10-05 13:17:25');
INSERT INTO `notifications` VALUES ('98','1','? Booking Request with Sound','New booking request received with sound notification.','booking','read','2025-10-05 13:17:25');
INSERT INTO `notifications` VALUES ('99','1','? Maintenance Alert with Sound','Maintenance completed with sound notification.','maintenance','read','2025-10-05 13:17:25');
INSERT INTO `notifications` VALUES ('100','1','? Announcement with Sound','Important announcement with sound notification.','announcement','read','2025-10-05 13:17:26');
INSERT INTO `notifications` VALUES ('101','1','? NOTIFICATION WITH SOUND','This notification includes sound and FCM push notification! Check your device.','general','read','2025-10-05 13:19:01');
INSERT INTO `notifications` VALUES ('102','1','? Payment Alert with Sound','Payment received! This notification has sound enabled.','payment','read','2025-10-05 13:19:02');
INSERT INTO `notifications` VALUES ('103','1','? Booking Request with Sound','New booking request received with sound notification.','booking','read','2025-10-05 13:19:02');
INSERT INTO `notifications` VALUES ('104','1','? Maintenance Alert with Sound','Maintenance completed with sound notification.','maintenance','read','2025-10-05 13:19:03');
INSERT INTO `notifications` VALUES ('105','1','? Announcement with Sound','Important announcement with sound notification.','announcement','read','2025-10-05 13:19:03');
INSERT INTO `notifications` VALUES ('106','1','? Test Badge Display','This notification is to test if the badge displays correctly in real-time.','general','read','2025-10-05 13:19:59');
INSERT INTO `notifications` VALUES ('107','1','? Test Badge Display','This notification is to test if the badge displays correctly in real-time.','general','read','2025-10-05 13:22:10');
INSERT INTO `notifications` VALUES ('108','1','? Test Badge Display','This notification is to test if the badge displays correctly in real-time.','general','read','2025-10-05 13:22:52');
INSERT INTO `notifications` VALUES ('109','1','? Test Badge Display','This notification is to test if the badge displays correctly in real-time.','general','read','2025-10-05 13:23:17');
INSERT INTO `notifications` VALUES ('110','1','? Test Badge Display','This notification is to test if the badge displays correctly in real-time.','general','read','2025-10-05 13:23:49');
INSERT INTO `notifications` VALUES ('111','1','? Test Badge Display','This notification is to test if the badge displays correctly in real-time.','general','read','2025-10-05 13:27:33');
INSERT INTO `notifications` VALUES ('112','1','? Test Badge Display','This notification is to test if the badge displays correctly in real-time.','general','read','2025-10-05 13:28:44');
INSERT INTO `notifications` VALUES ('113','1','? NOTIFICATION WITH SOUND','This notification includes sound and FCM push notification! Check your device.','general','read','2025-10-05 13:29:05');
INSERT INTO `notifications` VALUES ('114','1','? Payment Alert with Sound','Payment received! This notification has sound enabled.','payment','read','2025-10-05 13:29:05');
INSERT INTO `notifications` VALUES ('115','1','? Booking Request with Sound','New booking request received with sound notification.','booking','read','2025-10-05 13:29:05');
INSERT INTO `notifications` VALUES ('116','1','? Maintenance Alert with Sound','Maintenance completed with sound notification.','maintenance','read','2025-10-05 13:29:06');
INSERT INTO `notifications` VALUES ('117','1','? Announcement with Sound','Important announcement with sound notification.','announcement','read','2025-10-05 13:29:06');
INSERT INTO `notifications` VALUES ('118','1','? NOTIFICATION WITH SOUND','This notification includes sound and FCM push notification! Check your device.','general','read','2025-10-05 13:29:36');
INSERT INTO `notifications` VALUES ('119','1','? Payment Alert with Sound','Payment received! This notification has sound enabled.','payment','read','2025-10-05 13:29:37');
INSERT INTO `notifications` VALUES ('120','1','? Booking Request with Sound','New booking request received with sound notification.','booking','read','2025-10-05 13:29:37');
INSERT INTO `notifications` VALUES ('121','1','? Maintenance Alert with Sound','Maintenance completed with sound notification.','maintenance','read','2025-10-05 13:29:37');
INSERT INTO `notifications` VALUES ('122','1','? Announcement with Sound','Important announcement with sound notification.','announcement','read','2025-10-05 13:29:38');
INSERT INTO `notifications` VALUES ('123','1','Test Notification','This is a test notification to verify the system is working.','general','read','2025-10-05 13:30:33');
INSERT INTO `notifications` VALUES ('124','1','Test Notification','This is a test notification to verify the system is working.','general','read','2025-10-05 13:31:07');
INSERT INTO `notifications` VALUES ('125','1','? NOTIFICATION WITH SOUND','This notification includes sound and FCM push notification! Check your device.','general','read','2025-10-05 18:19:20');
INSERT INTO `notifications` VALUES ('126','1','? Payment Alert with Sound','Payment received! This notification has sound enabled.','payment','read','2025-10-05 18:19:25');
INSERT INTO `notifications` VALUES ('127','1','? Booking Request with Sound','New booking request received with sound notification.','booking','read','2025-10-05 18:19:35');
INSERT INTO `notifications` VALUES ('128','1','? Maintenance Alert with Sound','Maintenance completed with sound notification.','maintenance','read','2025-10-05 18:19:47');
INSERT INTO `notifications` VALUES ('129','1','? Announcement with Sound','Important announcement with sound notification.','announcement','read','2025-10-05 18:19:59');
INSERT INTO `notifications` VALUES ('130','1','? NOTIFICATION WITH SOUND','This notification includes sound and FCM push notification! Check your device.','general','read','2025-10-05 18:20:14');
INSERT INTO `notifications` VALUES ('131','1','? Payment Alert with Sound','Payment received! This notification has sound enabled.','payment','read','2025-10-05 18:20:21');
INSERT INTO `notifications` VALUES ('132','1','? Booking Request with Sound','New booking request received with sound notification.','booking','read','2025-10-05 18:20:34');
INSERT INTO `notifications` VALUES ('133','1','? Maintenance Alert with Sound','Maintenance completed with sound notification.','maintenance','read','2025-10-05 18:20:49');
INSERT INTO `notifications` VALUES ('134','1','? Announcement with Sound','Important announcement with sound notification.','announcement','read','2025-10-05 18:21:01');
INSERT INTO `notifications` VALUES ('135','1','Test Notification','This is a test notification to verify the system is working.','general','read','2025-10-05 20:48:45');
INSERT INTO `notifications` VALUES ('136','1','Test Notification','This is a test notification to verify the system is working.','general','read','2025-10-05 20:49:15');
INSERT INTO `notifications` VALUES ('137','1','Test Notification','This is a test notification to verify the system is working.','general','read','2025-10-05 20:56:41');
INSERT INTO `notifications` VALUES ('138','1','New Booking Request','You have a new booking request from Jane Smith for Room 205','booking','read','2025-10-08 23:20:47');
INSERT INTO `notifications` VALUES ('139','1','Payment Overdue','Your payment of ₱3,000.00 for Monthly Rent - December 2024 is overdue.','payment','read','2025-10-08 23:20:47');
INSERT INTO `notifications` VALUES ('140','1','Maintenance Completed','Maintenance for Elevator has been completed.','maintenance','read','2025-10-08 23:20:47');
INSERT INTO `notifications` VALUES ('141','1','? URGENT: Fire Drill','Fire drill will be conducted tomorrow at 2:00 PM. Please evacuate the building when the alarm sounds.','announcement','read','2025-10-08 23:20:47');
INSERT INTO `notifications` VALUES ('142','1','Test Notification','This is a test notification to verify the system is working.','general','read','2025-10-08 23:21:01');
INSERT INTO `notifications` VALUES ('143','1','Test Notification','This is a test notification to verify the system is working.','general','read','2025-10-08 23:22:22');
INSERT INTO `notifications` VALUES ('144','1','Test Notification','This is a test notification to verify the system is working.','general','read','2025-10-08 23:22:30');
INSERT INTO `notifications` VALUES ('145','1','System Test','Testing notification system after fix','general','read','2025-10-08 23:22:55');
INSERT INTO `notifications` VALUES ('146','1','System Fixed! ?','Your notification system is now working properly. This is a test notification to confirm everything is working.','general','read','2025-10-08 23:23:37');
INSERT INTO `notifications` VALUES ('147','1','System Fixed! ?','Your notification system is now working properly.','general','read','2025-10-08 23:24:03');
INSERT INTO `notifications` VALUES ('148','1','System Fixed! ?','Your notification system is now working properly.','general','read','2025-10-08 23:25:05');
INSERT INTO `notifications` VALUES ('149','1','System Status Check','Notification system is working properly','general','read','2025-10-08 23:25:42');
INSERT INTO `notifications` VALUES ('150','1','? Test Popup Notification','This notification should appear as a popup on your device. If you can see this, the system is working!','general','read','2025-10-08 23:27:00');
INSERT INTO `notifications` VALUES ('151','1','? Test Notification','This is a test notification with sound and popup. Check your device!','general','read','2025-10-08 23:46:34');
INSERT INTO `notifications` VALUES ('152','1','? Notification Test','This notification should appear with sound and popup!','general','read','2025-10-08 23:52:00');
INSERT INTO `notifications` VALUES ('153','1','? POPUP TEST','This should pop up on your screen with sound!','general','read','2025-10-09 00:04:19');
INSERT INTO `notifications` VALUES ('154','1','Welcome to BoardEase!','Your account has been successfully set up. Start exploring our features!','general','read','2025-10-09 11:10:08');
INSERT INTO `notifications` VALUES ('155','1','New Booking Request','You have received a new booking request for \"Cozy Studio Apartment\" from Mike Johnson.','booking','read','2025-10-09 11:10:10');
INSERT INTO `notifications` VALUES ('156','1','Payment Reminder','Your monthly payment of ₱3,500 is due in 3 days. Please make your payment to avoid late fees.','payment','read','2025-10-09 11:10:11');
INSERT INTO `notifications` VALUES ('157','1','Maintenance Update','Your maintenance request for \"Broken faucet in bathroom\" has been completed. Please check and confirm.','maintenance','read','2025-10-09 11:10:15');
INSERT INTO `notifications` VALUES ('158','1','System Announcement','BoardEase will be undergoing scheduled maintenance on Sunday, 10:00 PM - 11:00 PM. Some features may be temporarily unavailable.','announcement','read','2025-10-09 11:10:17');
INSERT INTO `notifications` VALUES ('159','29','Welcome to BoardEase!','Your account has been successfully activated. You can now start exploring boarding houses and managing your bookings.','','read','2025-10-12 13:24:32');
INSERT INTO `notifications` VALUES ('160','29','Welcome to BoardEase!','Your account has been successfully activated. You can now start exploring boarding houses and managing your bookings.','','read','2025-10-12 13:26:07');
INSERT INTO `notifications` VALUES ('161','29','Welcome to BoardEase!','Your account has been successfully activated. You can now start exploring boarding houses and managing your bookings.','','read','2025-10-12 13:28:14');
INSERT INTO `notifications` VALUES ('162','29','Welcome to BoardEase!','Your account has been successfully activated. You can now start exploring boarding houses and managing your bookings.','','read','2025-10-12 13:38:59');
INSERT INTO `notifications` VALUES ('163','29','Welcome to BoardEase!','Your account has been successfully activated. You can now start exploring boarding houses and managing your bookings.','','read','2025-10-12 13:39:04');
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
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
  `payment_status` enum('Pending','Completed','Failed','Refunded') NOT NULL DEFAULT 'Pending',
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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

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
  `status` enum('pending','approved','rejected') DEFAULT 'pending' COMMENT 'Registration status',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_email` (`email`),
  KEY `idx_role` (`role`),
  KEY `idx_status` (`status`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=54 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `registrations`
--

LOCK TABLES `registrations` WRITE;
/*!40000 ALTER TABLE `registrations` DISABLE KEYS */;
INSERT INTO `registrations` VALUES ('1','Boarder','Test',NULL,'User',NULL,NULL,NULL,'test@example.com','test123',NULL,NULL,NULL,'0',NULL,NULL,NULL,'2025-10-06 06:08:09','2025-10-09 00:49:23','approved');
INSERT INTO `registrations` VALUES ('2','Boarder','Test',NULL,'User',NULL,NULL,NULL,'test2@example.com','test123',NULL,NULL,NULL,'0',NULL,NULL,NULL,'2025-10-06 06:16:52','2025-10-09 00:49:36','approved');
INSERT INTO `registrations` VALUES ('3','Boarder','Kimberly Jul','Binag','Mante','2025-10-06','09925311463','Lucob','kimjul@gmail.con','dhdjdkdk','2134546','Driver\'s License','123456789','0','uploads/68e2eed214c01_front.jpg','uploads/68e2eed215235_back.jpg','uploads/68e2eed2153c1_qr.jpg','2025-10-06 06:18:58','2025-10-09 07:01:51','approved');
INSERT INTO `registrations` VALUES ('5','BH Owner','Christe Hanna','Dalugdog','Cuas','2003-10-07','09123456789','Tinibgan, Calape, Bohol','christehanna@gmail.com','namie','09925311463','GSIS e-card','123456789','0','uploads/68e4f3b4e49ab_front.jpg','uploads/68e4f3b4e66be_back.jpg','uploads/68e4f3b4e86af_qr.jpg','2025-10-07 19:04:20','2025-10-09 07:45:12','approved');
INSERT INTO `registrations` VALUES ('8','Boarder','Flora','Oracion','Mante','2004-09-07','09925311463','Lucob, Calape, Bohol','floramante@gmail.com','flora','123456789','SSS ID','123456789','0','uploads/68e4f92302869_front.jpg','uploads/68e4f92304024_back.jpg','uploads/68e4f92305704_qr.jpg','2025-10-07 19:27:31','2025-10-09 07:45:12','approved');
INSERT INTO `registrations` VALUES ('31','BH Owner','Hanna','Dalu','Baer','0000-00-00','09925311409','tini','hanna@gmail.com','$2y$10$PGaMA3PAWMCB8zizQL9GNuML9moOOTo0W2FGHJ/MFeGUvhvn9DrnW','09925311409','PhilID (National ID)','12345678','0','uploads/registrations/68e671d0356d0_front.jpg','uploads/registrations/68e671d035d67_back.jpg','uploads/registrations/68e671d037dbd_qr.jpg','2025-10-08 22:14:40','2025-10-09 07:58:31','approved');
INSERT INTO `registrations` VALUES ('35','BH Owner','Mari','Dalu','Baer','0000-00-00','09925311409','tini','mari@gmail.com','$2y$10$00.1846IMH5PJixoF53O4u2B4lhsoG2gzqqVN0YraZayL/ywf4AB2','09925311409','PhilID (National ID)','12345678','0','uploads/registrations/68e6722a65d31_front.jpg','uploads/registrations/68e6722a664ab_back.jpg','uploads/registrations/68e6722a68582_qr.jpg','2025-10-08 22:16:10','2025-10-09 06:43:32','approved');
INSERT INTO `registrations` VALUES ('37','Boarder','John','Mo','Ko','0000-00-00','09353549141','tinibgan','john@gmail.com','$2y$10$sn4panMBG7rFKTvN.lBD3eYMnypZWMQUXn89o1okHQKVdzJ0i9BMC','09353549141','SSS ID','23456789','0','uploads/registrations/68e67340adcb2_front.jpg','uploads/registrations/68e67340ae3f8_back.jpg','uploads/registrations/68e67340aebda_qr.jpg','2025-10-08 22:20:48','2025-10-08 22:20:48','pending');
INSERT INTO `registrations` VALUES ('39','Boarder','Maries','Ma','Mo','0000-00-00','9929769150','tibi','maries@gmail.com','$2y$10$XSVjvbUeXSWzo6G9iHIQS.oiCsGxMyBAiENSm/NciANJf8X.KnfjO','093535491941','Philippine Passport','235689','0','uploads/registrations/68e6747e9ba5b_front.jpg','uploads/registrations/68e6747e9be6c_back.jpg','uploads/registrations/68e6747e9c2fe_qr.jpg','2025-10-08 22:26:06','2025-10-08 22:26:06','pending');
INSERT INTO `registrations` VALUES ('40','Boarder','Momo','','Ko','2025-10-08','9929769150','tinibgan','momo@gmail.com','$2y$10$wsGEPDbYQGSNNjXnYSt4g.EMoHnCxu0PuDYAIrCsIU4nX7V.6ECf.','09353549141','PhilID (National ID)','123156486','0','uploads/registrations/68e675c579cf3_front.jpg','uploads/registrations/68e675c57a47a_back.jpg','uploads/registrations/68e675c57a9d5_qr.jpg','2025-10-08 22:31:33','2025-10-08 22:31:33','pending');
INSERT INTO `registrations` VALUES ('42','Boarder','Mama','Mo','Ko','2025-10-08','9929769150','tinibgan','mama@gmail.com','$2y$10$70UDp1ckqdUDq7imWw04u.XX8wYwOgbM3xT7OPaMDxuSwOOtmAfc6','09353549141','PhilID (National ID)','235689','0','uploads/registrations/68e675f4de651_front.jpg','uploads/registrations/68e675f4dedde_back.jpg','uploads/registrations/68e675f4df3f8_qr.jpg','2025-10-08 22:32:20','2025-10-09 06:42:12','approved');
INSERT INTO `registrations` VALUES ('51','Boarder','Liz','','Uy','2025-10-09','9929769150','calaoe','hannacuas536@gmail.com','$2y$10$eM50WpC0TRIMpS28fpc7O.QnaScJXcf1vQejdDFRDmPYqdT3u8.Dm','09925314096','PhilID (National ID)','2356890','0','uploads/registrations/68e709409683a_front.jpg','uploads/registrations/68e70940980cc_back.jpg','uploads/registrations/68e709409a367_qr.jpg','2025-10-09 09:00:48','2025-10-09 09:06:30','approved');
INSERT INTO `registrations` VALUES ('53','BH Owner','Namz','Dalu','Baer','2025-10-09','09925311409','calape','namzbaer@gmail.com','$2y$10$D1L2DMM4L1LNrYYmuMS7huUlDifWQF3jU.7bfYXmqyIthGffluzD6','09925311409','PhilID (National ID)','2356890','0','uploads/registrations/68e70b7a1a08c_front.jpg','uploads/registrations/68e70b7a1bcd8_back.jpg','uploads/gcash_qr/gcash_qr_29_1761296107.jpg','2025-10-09 09:10:18','2025-10-24 16:55:07','approved');
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reviews`
--

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
) ENGINE=InnoDB AUTO_INCREMENT=46 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
) ENGINE=InnoDB AUTO_INCREMENT=85 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
INSERT INTO `room_units` VALUES ('41','24','S-1','Available');
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
INSERT INTO `room_units` VALUES ('54','24','SA-3','Available');
INSERT INTO `room_units` VALUES ('59','38','SR-1','Available');
INSERT INTO `room_units` VALUES ('60','38','SR-2','Available');
INSERT INTO `room_units` VALUES ('61','39','G-1','Available');
INSERT INTO `room_units` VALUES ('62','39','G-2','Available');
INSERT INTO `room_units` VALUES ('63','40','KHAR-1','Available');
INSERT INTO `room_units` VALUES ('64','40','KHAR-2','Available');
INSERT INTO `room_units` VALUES ('65','40','KHAR-3','Available');
INSERT INTO `room_units` VALUES ('66','40','KHAR-4','Available');
INSERT INTO `room_units` VALUES ('67','40','KHAR-5','Available');
INSERT INTO `room_units` VALUES ('68','40','KHAR-6','Available');
INSERT INTO `room_units` VALUES ('69','40','KHAR-7','Available');
INSERT INTO `room_units` VALUES ('70','40','KHAR-8','Available');
INSERT INTO `room_units` VALUES ('71','40','KHAR-9','Available');
INSERT INTO `room_units` VALUES ('72','40','KHAR-10','Available');
INSERT INTO `room_units` VALUES ('73','40','KHAR-11','Available');
INSERT INTO `room_units` VALUES ('74','40','KHAR-12','Available');
INSERT INTO `room_units` VALUES ('75','41','SA-1','Available');
INSERT INTO `room_units` VALUES ('76','42','FR-1','Available');
INSERT INTO `room_units` VALUES ('77','42','FR-2','Available');
INSERT INTO `room_units` VALUES ('78','43','S-1','Available');
INSERT INTO `room_units` VALUES ('79','44','SA-1','Available');
INSERT INTO `room_units` VALUES ('80','45','SA-1','Available');
INSERT INTO `room_units` VALUES ('81','46','SA-1','Available');
INSERT INTO `room_units` VALUES ('82','47','GA-1','Occupied');
INSERT INTO `room_units` VALUES ('83','48','R2-1','Available');
INSERT INTO `room_units` VALUES ('84','47','GA-2','Available');
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
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
INSERT INTO `users` VALUES ('28','51',NULL,'Active');
INSERT INTO `users` VALUES ('29','53','uploads/profile_pictures/user_29_68eb24c9b4c44.jpg','Active');
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
