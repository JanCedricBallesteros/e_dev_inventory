-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: e-inventory
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `csm_inventory_category`
--

DROP TABLE IF EXISTS `csm_inventory_category`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `csm_inventory_category` (
  `category_id` int(11) NOT NULL AUTO_INCREMENT,
  `item_category_name` varchar(150) NOT NULL,
  `category_image` varchar(255) DEFAULT NULL,
  `item_category_code` varchar(50) NOT NULL,
  `category_photo` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`category_id`),
  UNIQUE KEY `item_category_code` (`item_category_code`),
  UNIQUE KEY `uniq_item_category_code` (`item_category_code`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `csm_inventory_category`
--

LOCK TABLES `csm_inventory_category` WRITE;
/*!40000 ALTER TABLE `csm_inventory_category` DISABLE KEYS */;
INSERT INTO `csm_inventory_category` VALUES (1,'Office Supplies',NULL,'CSM0001','cat_20260203_093756_ffc46e7579da.jpg','2026-02-02 05:08:46','2026-02-27 03:05:47'),(2,'Ballpoint Pen',NULL,'CSM0002',NULL,'2026-02-02 06:00:58','2026-02-27 03:05:51'),(3,'Pencils',NULL,'CSM0003','cat_20260203_093014_150cd0d36aa6.jpg','2026-02-03 00:58:28','2026-02-27 03:05:55'),(4,'Ink Cartridge',NULL,'CSM0004',NULL,'2026-02-03 02:52:31','2026-02-27 03:05:59');
/*!40000 ALTER TABLE `csm_inventory_category` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `csm_inventory_category_images`
--

DROP TABLE IF EXISTS `csm_inventory_category_images`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `csm_inventory_category_images` (
  `image_id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_url` varchar(500) NOT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`image_id`),
  KEY `idx_category_id` (`category_id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `csm_inventory_category_images`
--

LOCK TABLES `csm_inventory_category_images` WRITE;
/*!40000 ALTER TABLE `csm_inventory_category_images` DISABLE KEYS */;
INSERT INTO `csm_inventory_category_images` VALUES (6,1,'cat_1_1772155137_0_IMG20260216151809.jpg','',1,'2026-02-27 01:18:57'),(7,1,'cat_1_1772156511_0_IMG20260219143716.jpg','upload/category/cat_1_1772156511_0_IMG20260219143716.jpg',0,'2026-02-27 01:41:51'),(8,1,'cat_1_1772156514_0_IMG20260219122939.jpg','upload/category/cat_1_1772156514_0_IMG20260219122939.jpg',0,'2026-02-27 01:41:54');
/*!40000 ALTER TABLE `csm_inventory_category_images` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `csm_inventory`
--

DROP TABLE IF EXISTS `csm_inventory`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `csm_inventory` (
  `inventory_id` int(11) NOT NULL AUTO_INCREMENT,
  `inventory_system_item_code` varchar(100) NOT NULL,
  `item_description` text DEFAULT NULL,
  `acquisition_date` date NOT NULL,
  `item_cost` decimal(12,2) NOT NULL,
  `source_of_funds` varchar(150) DEFAULT NULL,
  `item_category_code` varchar(50) NOT NULL,
  `status` enum('available','currently used','out of stock','damaged','expired') NOT NULL DEFAULT 'available',
  `unit_quantity` int(11) NOT NULL COMMENT 'Original quantity received',
  `current_unit_quantity` int(11) NOT NULL COMMENT 'Remaining usable quantity',
  `unit_crit_level` int(11) NOT NULL COMMENT 'Critical stock threshold',
  `last_updated` date NOT NULL,
  `item_category_img` varchar(255) DEFAULT NULL,
  `qr_verification` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `category_image_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`inventory_id`),
  UNIQUE KEY `inventory_system_item_code` (`inventory_system_item_code`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `csm_inventory`
--

LOCK TABLES `csm_inventory` WRITE;
/*!40000 ALTER TABLE `csm_inventory` DISABLE KEYS */;
INSERT INTO `csm_inventory` VALUES (6,'CSM-0002-0001','test','2026-02-27',22.00,'test','CSM0002','available',22,23,2,'2026-02-27',NULL,NULL,'2026-02-26 21:24:28','2026-02-27 03:06:18',NULL),(7,'CSM-0001-0001','Example itemized description (full details/specs/notes)','2026-02-27',25.50,'General Fund','CSM0001','available',100,80,10,'2026-02-27','8',NULL,'2026-02-26 21:52:49','2026-02-27 04:01:18',NULL),(8,'CSM-0001-0003','Example itemized description (full details/specs/notes)','2026-02-27',25.50,'test','CSM0001','available',111,22,10,'2026-02-27','7',NULL,'2026-02-26 22:00:54','2026-02-27 04:01:15',NULL),(9,'CSM-0002-0004','test','2026-02-27',22.00,'','CSM0002','available',28,23,2,'2026-02-27',NULL,NULL,'2026-02-26 22:00:54','2026-02-26 22:04:28',NULL),(10,'CSM-0002-0005','test incr','2026-02-27',22.00,'test','CSM0002','available',22,22,2,'2026-02-27',NULL,NULL,'2026-02-27 03:06:44','2026-02-27 03:06:44',NULL);
/*!40000 ALTER TABLE `csm_inventory` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-03-02 11:41:40
