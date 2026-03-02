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
-- Table structure for table `employment_status`
--

DROP TABLE IF EXISTS `employment_status`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `employment_status` (
  `employment_status_id` int(11) NOT NULL AUTO_INCREMENT,
  `status_code` varchar(50) NOT NULL,
  `status_name` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`employment_status_id`),
  UNIQUE KEY `status_code` (`status_code`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employment_status`
--

LOCK TABLES `employment_status` WRITE;
/*!40000 ALTER TABLE `employment_status` DISABLE KEYS */;
INSERT INTO `employment_status` VALUES (1,'Permanent','Permanent','Permanent Employment','2026-01-30 06:24:37','2026-01-30 06:24:37'),(2,'COS','Contract of Service','COS - Contractual Position','2026-01-30 06:24:37','2026-01-30 06:24:37'),(3,'JO','Job Order','JO - Job Order Position','2026-01-30 06:24:37','2026-01-30 06:24:37');
/*!40000 ALTER TABLE `employment_status` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `requisition_items`
--

DROP TABLE IF EXISTS `requisition_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `requisition_items` (
  `requisition_id` int(11) NOT NULL AUTO_INCREMENT,
  `module_type` enum('AST','CSM') NOT NULL,
  `item_code` varchar(100) NOT NULL,
  `item_description` text DEFAULT NULL,
  `qty_requested` int(11) NOT NULL DEFAULT 1,
  `requester_user_id` int(11) NOT NULL,
  `status` enum('pending','reviewed','approved','disapproved') NOT NULL DEFAULT 'pending',
  `reason` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`requisition_id`),
  KEY `idx_requisition_module_status` (`module_type`,`status`),
  KEY `idx_requisition_requester` (`requester_user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `requisition_items`
--

LOCK TABLES `requisition_items` WRITE;
/*!40000 ALTER TABLE `requisition_items` DISABLE KEYS */;
INSERT INTO `requisition_items` VALUES (1,'AST','AST-0000-0011','sfgsdfg',1,5,'approved',NULL,'2026-02-12 07:21:04','2026-02-12 08:23:25');
/*!40000 ALTER TABLE `requisition_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_access`
--

DROP TABLE IF EXISTS `user_access`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_access` (
  `user_access_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `access_code` varchar(50) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`user_access_id`),
  UNIQUE KEY `user_access_unique` (`user_id`,`access_code`),
  KEY `user_access_user_id` (`user_id`),
  CONSTRAINT `user_access_fk_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_access`
--

LOCK TABLES `user_access` WRITE;
/*!40000 ALTER TABLE `user_access` DISABLE KEYS */;
INSERT INTO `user_access` VALUES (2,3,'AST',1,'2026-02-09 08:02:48','2026-02-09 08:02:48'),(3,2,'PO',1,'2026-02-09 08:02:52','2026-02-09 08:02:52'),(7,4,'CSM',1,'2026-02-23 01:12:55','2026-02-23 01:12:55');
/*!40000 ALTER TABLE `user_access` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_log_events`
--

DROP TABLE IF EXISTS `user_log_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_log_events` (
  `event_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `action` varchar(20) NOT NULL,
  `event_time` datetime NOT NULL,
  `ip_address` varchar(45) NOT NULL DEFAULT '',
  `device` text DEFAULT NULL,
  `session_token` varchar(255) NOT NULL DEFAULT '',
  `user_level` varchar(100) NOT NULL DEFAULT '',
  `source` varchar(50) NOT NULL DEFAULT 'system',
  PRIMARY KEY (`event_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_event_time` (`event_time`),
  KEY `idx_action` (`action`)
) ENGINE=InnoDB AUTO_INCREMENT=45 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_log_events`
--

LOCK TABLES `user_log_events` WRITE;
/*!40000 ALTER TABLE `user_log_events` DISABLE KEYS */;
INSERT INTO `user_log_events` VALUES (1,4,'LOGIN','2026-02-24 09:37:41','::1','{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}','fa4802a9c6cdbbe89c10da9be72ae92df74abb01033087d4eb747e3dd62a6f2e','3','system'),(2,4,'LOGIN','2026-02-24 09:38:07','::1','{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}','a466662736b9178563f9492641d0ee042fe5506616689d61532efefa71e474fa','3','system'),(3,4,'LOGIN','2026-02-24 09:41:40','::1','{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}','96de45e53fe0a89451f8620aa70acaca6cf8a5fc7f037df463b23a3884a7f8aa','3','system'),(4,4,'LOGOUT','2026-02-24 09:41:42','::1','{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}','96de45e53fe0a89451f8620aa70acaca6cf8a5fc7f037df463b23a3884a7f8aa','ADMIN_STAFF','system'),(5,4,'LOGIN','2026-02-24 09:42:01','::1','{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}','51d5d11267077e9c6639c4b7c94334b4b940016f6051d6b3144fa95c0fb5470b','3','system'),(6,1,'LOGIN','2026-02-24 10:36:17','::1','{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}','dda9f33fdb9726d9666bf3c4f4a9dbb5fcc97b7628473d96301e9b54d4e763fa','1','system'),(7,1,'LOGOUT','2026-02-24 11:39:11','::1','{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}','dda9f33fdb9726d9666bf3c4f4a9dbb5fcc97b7628473d96301e9b54d4e763fa','SUPER_ADMIN','system'),(8,1,'LOGIN','2026-02-24 11:39:29','::1','{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}','a4d01a63089ad89654a68f6c87e6588cac7285b03762b00ecbae3692d66a972d','2','system'),(9,1,'LOGOUT','2026-02-24 13:22:57','::1','{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}','a4d01a63089ad89654a68f6c87e6588cac7285b03762b00ecbae3692d66a972d','ADMIN','system'),(10,1,'LOGIN','2026-02-24 13:23:21','::1','{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}','84cda3da6f4983011cc22b270e49f6024ab209be8faf6ab05a1efb2db35325f4','1','system'),(11,1,'LOGIN','2026-02-24 13:25:47','::1','{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}','d033259ec35ab3d49c3473d58d07dd78b5949962f797ffb0cee596fa3be7c840','1','system'),(12,1,'LOGIN','2026-02-24 13:26:01','::1','{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}','c5aba65acf0ff1cb28dd096302d9032f817c26eab3ccb299faee151c7986af09','1','system'),(13,1,'LOGIN','2026-02-24 14:24:54','::1','{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}','caa6a5bc9aaa925352f9e138073c0faa675d9337ff61917a47fb15ea9b8ea3d9','1','system'),(14,1,'LOGOUT','2026-02-24 14:25:25','127.0.0.1','{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}','caa6a5bc9aaa925352f9e138073c0faa675d9337ff61917a47fb15ea9b8ea3d9','SUPER_ADMIN','system'),(15,3,'LOGIN','2026-02-24 14:25:37','::1','{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}','94da5d2ff0bf7bd6da19004636569f2d9123afad524440ab09bef3869329493e','3','system'),(16,1,'LOGIN','2026-02-24 14:25:53','::1','{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}','1f0f1bc3b8c9a3d67f307c0f049daa237002a5359dbf2c76a565000246261a4d','1','system'),(17,4,'LOGIN','2026-02-24 14:26:34','::1','{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}','e18780195f9328b364d1fa3727631fd5c96dedeb5d7f887837c2cad493e6bc90','3','system'),(18,4,'LOGOUT','2026-02-24 14:26:39','127.0.0.1','{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}','e18780195f9328b364d1fa3727631fd5c96dedeb5d7f887837c2cad493e6bc90','ADMIN_STAFF','system'),(19,1,'LOGIN','2026-02-24 14:26:49','::1','{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}','dbc510cd3891d5c32b6a27fd160abcac8337ddfa94a5bbe55b8ca70da1f95542','1','system'),(20,1,'LOGOUT','2026-02-24 16:15:32','::1','{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}','dbc510cd3891d5c32b6a27fd160abcac8337ddfa94a5bbe55b8ca70da1f95542','SUPER_ADMIN','system'),(21,1,'LOGIN','2026-02-24 16:15:44','::1','{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}','30c4b893b1d1ae66a37c4616c3c81bddd7b0f3aa565da8193f8e41c3203d857a','1','system'),(22,1,'LOGOUT','2026-02-24 16:22:13','::1','{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}','30c4b893b1d1ae66a37c4616c3c81bddd7b0f3aa565da8193f8e41c3203d857a','SUPER_ADMIN','system'),(23,1,'LOGIN','2026-02-24 16:22:24','::1','{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}','747ff8fa80b7ad621d5331b903c7245edf25dc00f498c77dfb1be1e9b2882642','2','system'),(24,1,'LOGOUT','2026-02-24 16:45:17','::1','{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}','747ff8fa80b7ad621d5331b903c7245edf25dc00f498c77dfb1be1e9b2882642','ADMIN','system'),(25,5,'LOGIN','2026-02-24 16:45:28','::1','{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}','0287d64e4e32d87783cfc8f72cc7a07eece52248f9ec8e57c25a644557949ea5','4','system'),(26,1,'LOGIN','2026-02-26 08:34:23','::1','{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}','5e4ade248ed3b7985dbada6155f2882d3b5930e03c12020605ad17958ccc81a8','3','system'),(27,1,'LOGIN','2026-02-26 08:34:32','::1','{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}','cc2a361e67b9b2cd56e5d4217f8160b67b52c9935861ca613bfc2460ca688f10','1','system'),(28,1,'LOGIN','2026-02-26 11:24:32','::1','{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}','6c2b1380f3194e138d1efd660bbe5ec0f61db8885c42950792bf25df773a6a75','2','system'),(29,1,'LOGIN','2026-02-26 11:30:11','10.10.10.46','{\"device\":\"Chrome Mobile\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":32,\"family\":\"Android\",\"version\":\"10\"},\"description\":\"Chrome Mobile 145.0.0.0 on K (Android 10)\"}','21a98e1c1fca942aaf2fe848511ee317d75e4424f352e466c262c59054be1ea9','2','system'),(30,1,'LOGOUT','2026-02-26 11:41:31','::1','{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}','6c2b1380f3194e138d1efd660bbe5ec0f61db8885c42950792bf25df773a6a75','ADMIN','system'),(31,1,'LOGIN','2026-02-26 11:41:35','::1','{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}','6a97804baea3e61308a45cdf4bbeef31881145b8d7d0021a83ef3507ce6bf762','1','system'),(32,1,'LOGOUT','2026-02-26 11:51:59','10.10.10.49','{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}','6a97804baea3e61308a45cdf4bbeef31881145b8d7d0021a83ef3507ce6bf762','SUPER_ADMIN','system'),(33,1,'LOGIN','2026-02-26 11:52:03','::1','{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}','af414c85720a69c3d7b6aa655c3336a4ec0c0285d3889f0f44fd2f11a01438b0','2','system'),(34,1,'LOGIN','2026-02-26 18:02:05','10.10.10.49','{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}','441a0d10575b4253ab40fb6b3ff247b4698bb5e49d7a244a942b42577846f363','2','system'),(35,1,'LOGIN','2026-02-26 18:04:36','10.10.10.49','{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}','e39f4da055508d3c02a042a4b36e3b47c45adc1623a57d326c1ea02641197392','2','system'),(36,1,'LOGIN','2026-02-26 18:05:09','10.10.10.46','{\"device\":\"Chrome Mobile\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":32,\"family\":\"Android\",\"version\":\"10\"},\"description\":\"Chrome Mobile 145.0.0.0 on K (Android 10)\"}','dfa2533130a5297f420b596f8ad00983abb299afbd128ae93b11d30489251285','2','system'),(37,1,'LOGIN','2026-02-26 18:05:43','10.10.10.49','{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}','fdbe8acde3bafae0dfce06f285f82fd9384942be6e15f3e2dd54d5de8649516a','2','system'),(38,1,'LOGIN','2026-02-27 08:20:13','::1','{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}','af1092c6ea9feadf826e87efc9f928002308ae9acd5ad811984083d283fd9ef7','2','system'),(39,1,'LOGIN','2026-02-27 10:22:56','::1','{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}','f31599ed217cc046fb18b077141d0856f1eb05f0ebd5868c4558d7a04a23e510','1','system'),(40,1,'LOGIN','2026-02-27 11:20:37','::1','{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}','ecf68ec957be12971883399f80381ecdfec47712e80ccea9c82320022038450c','2','system'),(41,1,'LOGIN','2026-02-27 11:21:06','::1','{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}','55f85baf9664aa7aeca0d1f06e5297dd22e9f5d67d33e28ccc699dcd0a2c611a','1','system'),(42,1,'LOGIN','2026-03-02 08:56:14','::1','{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}','ed5ed4c8453b2e5bc0ca382aa027a6b59669665f4215621d8b64248739507ce5','1','system'),(43,1,'LOGOUT','2026-03-02 09:31:03','::1','{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}','ed5ed4c8453b2e5bc0ca382aa027a6b59669665f4215621d8b64248739507ce5','SUPER_ADMIN','system'),(44,1,'LOGIN','2026-03-02 09:31:08','::1','{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}','b26a1cbc4c17250071007880bed747bc891528bd6318fe75b58267a7db58c73c','2','system');
/*!40000 ALTER TABLE `user_log_events` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-03-02 11:41:41
