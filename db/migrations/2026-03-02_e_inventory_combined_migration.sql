-- Migration bundle for e_inventory
-- Source: e-inventory
SET FOREIGN_KEY_CHECKS=0;
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
-- Table structure for table `ast_inventory_category`
--

DROP TABLE IF EXISTS `ast_inventory_category`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ast_inventory_category` (
  `category_id` int(11) NOT NULL AUTO_INCREMENT,
  `item_category_name` varchar(150) NOT NULL,
  `category_photo` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`category_id`)
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ast_inventory_category`
--

LOCK TABLES `ast_inventory_category` WRITE;
/*!40000 ALTER TABLE `ast_inventory_category` DISABLE KEYS */;
INSERT INTO `ast_inventory_category` VALUES (1,'test','cat_20260204_141348_6982e39c7cb28.png','2026-02-03 22:13:22','2026-02-03 22:13:48'),(2,'upuang naka italic','cat_20260204_144034_6982e9e27ddba.jpg','2026-02-03 22:40:34','2026-02-03 23:02:44'),(3,'Computer na nag aapoy','cat_20260204_152808_6982f508820bb.jpg','2026-02-03 23:28:08','2026-02-03 23:28:08'),(4,'Ben 10 Computer','cat_20260204_152951_6982f56f446c0.jpg','2026-02-03 23:29:51','2026-02-03 23:29:51'),(5,'james na natutulog','cat_20260204_162615_698302a745a84.jpg','2026-02-04 00:26:15','2026-02-04 00:26:15'),(6,'qrgentest','cat_20260205_094051_6983f52398757.jpg','2026-02-04 17:40:51','2026-02-04 17:40:51'),(7,'testing lang','cat_20260205_143039_6984390fb6579.png','2026-02-04 22:30:39','2026-02-04 22:30:39'),(12,'1','cat_20260205_155715_69844d5b9c705.png','2026-02-04 23:57:15','2026-02-04 23:57:15'),(13,'2','cat_20260205_155725_69844d650c535.png','2026-02-04 23:57:25','2026-02-04 23:57:25'),(14,'3','cat_20260205_155738_69844d72a90fc.png','2026-02-04 23:57:38','2026-02-04 23:57:38'),(16,'4',NULL,'2026-02-05 00:00:04','2026-02-05 00:00:04'),(17,'5',NULL,'2026-02-05 00:00:10','2026-02-05 00:00:10'),(18,'6',NULL,'2026-02-05 00:00:15','2026-02-05 00:00:15'),(19,'7',NULL,'2026-02-05 00:00:20','2026-02-05 00:00:20'),(20,'8','cat_20260212_101558_698d37de208cc.png','2026-02-05 00:00:25','2026-02-12 02:15:58'),(21,'Computer set 1','cat_20260210_090604_698a847c48f79.jpg','2026-02-10 01:06:04','2026-02-10 01:06:04'),(22,'Furnitures',NULL,'2026-02-13 02:29:45','2026-02-13 02:29:45'),(23,'bulk_test_1','cat_20260216_160708_6992d02ca7246.png','2026-02-16 08:07:08','2026-02-16 08:07:08'),(24,'bulk_test_2','cat_20260216_160708_6992d02cb3022.png','2026-02-16 08:07:08','2026-02-16 08:07:08'),(25,'bulk_test_3','cat_20260216_160708_6992d02cbb95b.png','2026-02-16 08:07:08','2026-02-16 08:07:08'),(26,'PC set 2',NULL,'2026-02-19 06:20:52','2026-02-19 06:20:52'),(27,'New UI test','cat_20260219_152205_6996ba1d85d38.png','2026-02-19 07:22:05','2026-02-19 07:22:05'),(28,'No Cat code','cat_20260219_161943_6996c79fb8941.png','2026-02-19 08:19:43','2026-02-19 08:19:43'),(29,'Asong call center','cat_20260223_080414_699b997e8fcea.png','2026-02-23 00:04:14','2026-02-23 00:04:14'),(30,'Asong nag ba bike','cat_20260223_080414_699b997e9aebf.png','2026-02-23 00:04:14','2026-02-23 00:04:14'),(31,'bulk 1','cat_20260226_112506_699fbd12a9a10.png','2026-02-26 03:25:06','2026-02-26 03:25:06'),(32,'bulk2',NULL,'2026-02-26 03:25:06','2026-02-26 03:25:06'),(33,'bulk3',NULL,'2026-02-26 03:25:07','2026-02-26 03:25:07'),(34,'Serialnotest','cat_20260226_170447_69a00caf91d21.png','2026-02-26 09:04:47','2026-02-26 09:04:47'),(35,'RevampedAddtest','cat_20260226_174032_69a01510bd5c5.png','2026-02-26 09:40:32','2026-02-26 09:40:32');
/*!40000 ALTER TABLE `ast_inventory_category` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ast_inventory`
--

DROP TABLE IF EXISTS `ast_inventory`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ast_inventory` (
  `item_id` int(11) NOT NULL AUTO_INCREMENT,
  `property_number` varchar(100) NOT NULL COMMENT 'User-provided property number base',
  `property_series` int(11) NOT NULL DEFAULT 1 COMMENT 'Incremental series per property number',
  `property_code` varchar(150) NOT NULL COMMENT 'AST-[PROPERTY_NUMBER]-[SERIES] (padded)',
  `category_id` int(11) NOT NULL,
  `item_description` text NOT NULL,
  `serial_number` varchar(150) DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT 0,
  `available_qty` int(11) DEFAULT NULL,
  `unit` varchar(50) NOT NULL DEFAULT '',
  `source_of_fund` varchar(150) DEFAULT NULL,
  `cost_value` decimal(12,2) DEFAULT NULL,
  `qr_image` varchar(255) DEFAULT NULL,
  `is_available` tinyint(1) NOT NULL DEFAULT 1,
  `allowed_employment_status` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`item_id`),
  UNIQUE KEY `uniq_property_code` (`property_code`),
  UNIQUE KEY `uniq_property_series` (`property_number`,`property_series`),
  KEY `idx_category_id` (`category_id`)
) ENGINE=InnoDB AUTO_INCREMENT=48 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ast_inventory`
--

LOCK TABLES `ast_inventory` WRITE;
/*!40000 ALTER TABLE `ast_inventory` DISABLE KEYS */;
INSERT INTO `ast_inventory` VALUES (1,'0001',1,'AST-0001-0001',1,'pc set',NULL,1,1,'set',NULL,NULL,NULL,1,'[1]','2026-02-03 22:37:54','2026-02-19 09:31:31'),(2,'6767',1,'AST-6767-0001',2,'upuan na naka italic',NULL,10,1,'pcs',NULL,NULL,NULL,1,'[1]','2026-02-03 22:41:42','2026-02-19 09:31:35'),(3,'0001',2,'AST-0001-0002',3,'computer na nag aapoy',NULL,1,1,'set',NULL,NULL,NULL,1,'[1]','2026-02-04 00:07:09','2026-02-19 09:31:40'),(4,'0000',1,'AST-0000-0001',6,'asdfas',NULL,1,1,'pcs',NULL,NULL,'ast_qr_20260205_094149_6983f55d1623b.png',1,'[1]','2026-02-04 17:41:49','2026-02-19 09:31:44'),(6,'0000',2,'AST-0000-0002',12,'hssdfg',NULL,1,1,'pcs',NULL,NULL,'ast_qr_20260205_155812_69844d948cde3.png',1,'[1]','2026-02-04 23:58:12','2026-02-19 09:31:49'),(7,'0000',3,'AST-0000-0003',13,'23752',NULL,1,1,'pcs',NULL,NULL,'ast_qr_20260205_155825_69844da16bdb7.png',1,'[1]','2026-02-04 23:58:25','2026-02-19 09:31:54'),(8,'0000',4,'AST-0000-0004',14,'5205025',NULL,1,1,'pcs',NULL,NULL,'ast_qr_20260205_155834_69844daa4c6c8.png',1,'[1]','2026-02-04 23:58:34','2026-02-19 09:32:04'),(10,'0000',6,'AST-0000-0006',16,'000',NULL,1,1,'pcs',NULL,NULL,'ast_qr_20260205_160040_69844e289283f.png',1,'[1]','2026-02-05 00:00:40','2026-02-19 09:31:59'),(11,'0000',7,'AST-0000-0007',17,'0000',NULL,1,1,'pcs',NULL,NULL,'ast_qr_20260205_160141_69844e659a0a1.png',1,'[1]','2026-02-05 00:01:41','2026-02-19 09:31:20'),(12,'0000',8,'AST-0000-0008',18,'000',NULL,1,1,'pcs',NULL,NULL,'ast_qr_20260205_160148_69844e6ce01b6.png',1,'[1]','2026-02-05 00:01:48','2026-02-19 09:31:13'),(13,'0000',9,'AST-0000-0009',19,'0000',NULL,1,1,'pcs',NULL,NULL,'ast_qr_20260205_160207_69844e7f1f718.png',1,'[1]','2026-02-05 00:02:07','2026-02-19 09:31:06'),(14,'0000',10,'AST-0000-0010',20,'0000',NULL,1,1,'pcs',NULL,NULL,'ast_qr_20260205_160219_69844e8beb83f.png',1,'[1]','2026-02-05 00:02:19','2026-02-19 09:30:58'),(15,'0001',3,'AST-0001-0003',21,'keyboard',NULL,1,1,'pcs',NULL,NULL,'ast_qr_20260210_090718_698a84c68c82b.png',1,'[1]','2026-02-10 01:07:18','2026-02-19 09:30:42'),(16,'0000',11,'AST-0000-0011',12,'sfgsdfg',NULL,1,1,'pcs',NULL,NULL,'ast_qr_20260211_101500_698be624e6391.png',1,'[1]','2026-02-11 02:15:00','2026-02-19 09:30:35'),(17,'0001',4,'AST-0001-0004',21,'Malaking monitor',NULL,1,1,'pcs',NULL,NULL,'ast_qr_20260213_093625_698e80191d63a.png',1,'[1]','2026-02-13 01:36:25','2026-02-19 09:30:12'),(18,'0001',5,'AST-0001-0005',21,'mouse',NULL,1,1,'pcs',NULL,NULL,'ast_qr_20260213_093840_698e80a0ebccf.png',1,'[1]','2026-02-13 01:38:40','2026-02-19 09:30:06'),(19,'0001',6,'AST-0001-0006',21,'desktop computer',NULL,1,1,'pcs',NULL,NULL,'ast_qr_20260213_094143_698e815734cdb.png',1,'[1]','2026-02-13 01:41:43','2026-02-19 09:30:02'),(20,'0000',12,'AST-0000-0012',22,'Sofa',NULL,1,1,'set','Rozz Opena',1000000.00,'ast_qr_20260213_103059_698e8ce3e66b7.png',1,'[1]','2026-02-13 02:30:59','2026-02-19 09:29:56'),(21,'0001',7,'AST-0001-0007',22,'upuan',NULL,100,50,'pcs','ako',10000.00,'ast_qr_20260213_143646_698ec67e2d4aa.png',1,'[1]','2026-02-13 06:36:46','2026-02-19 09:29:16'),(22,'0000',13,'AST-0000-0013',12,'Testing lang ng ui',NULL,1,1,'pcs',NULL,NULL,'ast_qr_20260219_145953_6996b4e994a25.png',1,'[1]','2026-02-19 06:59:53','2026-02-19 09:46:57'),(23,'0000',14,'AST-0000-0014',12,'testing lang ulit ng ui hahahahahaha labyu',NULL,1,1,'pcs',NULL,NULL,'ast_qr_20260219_150550_6996b64e452c2.png',1,'[1]','2026-02-19 07:05:50','2026-02-19 09:47:01'),(24,'0000',15,'AST-0000-0015',27,'pusa',NULL,1,1,'pcs',NULL,NULL,'ast_qr_20260219_152230_6996ba363aaf4.png',1,'[1]','2026-02-19 07:22:30','2026-02-19 09:29:39'),(25,'0000',16,'AST-0000-0016',27,'test',NULL,1,1,'pcs',NULL,NULL,'ast_qr_20260219_161344_6996c638ca11d.png',1,'[1]','2026-02-19 08:13:44','2026-02-19 09:29:34'),(26,'0000',17,'AST-0000-0017',28,'pusa haha',NULL,1,1,'pcs',NULL,NULL,'ast_qr_20260219_162032_6996c7d01d635.png',1,'[1]','2026-02-19 08:20:32','2026-02-23 00:56:00'),(27,'0654',1,'AST-0654-0001',29,'asong call center',NULL,2,0,'pcs',NULL,NULL,'ast_qr_20260223_080445_699b999d20958.png',0,'[1]','2026-02-23 00:04:45','2026-02-23 05:40:19'),(28,'8946',1,'AST-8946-0001',30,'naka bike hahaha',NULL,2,0,'pcs',NULL,NULL,'ast_qr_20260223_080502_699b99ae26a27.png',0,'{\"teaching\":[1],\"non_teaching\":[1]}','2026-02-23 00:05:02','2026-02-23 05:45:13'),(29,'3452',1,'AST-3452-0001',2,'wgsg',NULL,1,0,'pcs',NULL,NULL,'ast_qr_20260223_134453_699be95580748.png',0,NULL,'2026-02-23 05:44:53','2026-02-23 05:44:53'),(30,'1111',1,'AST-1111-0001',30,'test aso',NULL,1,0,'pcs',NULL,NULL,'ast_qr_20260223_152826_699c019a0d5e7.png',0,'{\"teaching\":[1],\"non_teaching\":[1]}','2026-02-23 07:28:26','2026-02-26 03:35:15'),(31,'0000',18,'AST-0000-0018',31,'test',NULL,1,0,'pcs',NULL,NULL,'ast_qr_20260226_113225_699fbec9023d6.png',0,'{\"teaching\":[1],\"non_teaching\":[1]}','2026-02-26 03:32:25','2026-02-26 03:35:15'),(32,'0000',19,'AST-0000-0019',32,'test',NULL,5,0,'pcs',NULL,NULL,'ast_qr_20260226_113328_699fbf087a734.png',0,'{\"teaching\":[1],\"non_teaching\":[1]}','2026-02-26 03:33:28','2026-02-26 03:35:15'),(33,'HAHAHATESTINGLANGPO123456789',1,'AST-HAHAHATESTINGLANGPO123456789-0001',12,'new property number test',NULL,1,0,'pcs',NULL,NULL,'ast_qr_20260226_135345_699fdfe968384.png',0,NULL,'2026-02-26 05:53:45','2026-02-26 05:53:45'),(34,'UNITTEST',1,'AST-UNITTEST-0001',12,'asdfas',NULL,1,0,'set',NULL,NULL,'ast_qr_20260226_135845_699fe11539479.png',0,NULL,'2026-02-26 05:58:45','2026-02-26 05:58:45'),(35,'UNITTEST',2,'AST-UNITTEST-0002',12,'asdfa',NULL,1,0,'unittest',NULL,NULL,'ast_qr_20260226_135901_699fe125a00aa.png',0,NULL,'2026-02-26 05:59:01','2026-02-26 05:59:01'),(36,'UNITTEST',3,'AST-UNITTEST-0003',12,'asdf',NULL,1,0,'unittest',NULL,NULL,'ast_qr_20260226_135916_699fe13498a0c.png',0,NULL,'2026-02-26 05:59:16','2026-02-26 05:59:16'),(37,'0321',1,'AST-0321-0001',12,'testing ng mahabang description testing ng mahabang description testing ng mahabang description testing ng mahabang description testing ng mahabang description testing ng mahabang description',NULL,1,0,'pcs',NULL,NULL,'ast_qr_20260226_141905_699fe5d9999c9.png',0,NULL,'2026-02-26 06:19:05','2026-02-26 06:19:05'),(38,'ASDF351',1,'AST-ASDF351-0001',34,'asdfadsf','SN123456789',1,0,'pcs',NULL,NULL,'ast_qr_20260226_170537_69a00ce18880f.png',0,NULL,'2026-02-26 09:05:37','2026-02-26 09:05:37'),(39,'JIPRE',1,'AST-JIPRE-0001',35,'Jipre Baluyot','1',1,1,'pcs','ako',10000.00,'ast_qr_20260226_174122_69a0154288c9a.png',1,NULL,'2026-02-26 09:41:22','2026-02-26 09:41:22'),(40,'JIPRE',2,'AST-JIPRE-0002',35,'Jipre Baluyot','2',1,1,'pcs','ako',10000.00,'ast_qr_20260226_174122_69a0154292ee0.png',1,NULL,'2026-02-26 09:41:22','2026-02-26 09:41:22'),(41,'JIPRE',3,'AST-JIPRE-0003',35,'Jipre Baluyot','3',1,1,'pcs','ako',10000.00,'ast_qr_20260226_174122_69a0154294e1a.png',1,NULL,'2026-02-26 09:41:22','2026-02-26 09:41:22'),(42,'1234',1,'AST-1234-0001',35,'asdfasdf',NULL,1,1,'pcs',NULL,NULL,'ast_qr_20260226_182239_69a01eef68e3a.png',1,NULL,'2026-02-26 10:22:39','2026-02-26 10:22:39'),(43,'1234',2,'AST-1234-0002',35,'asdfasdf',NULL,1,1,'pcs',NULL,NULL,'ast_qr_20260226_182239_69a01eef72388.png',1,NULL,'2026-02-26 10:22:39','2026-02-26 10:22:39'),(44,'1234',3,'AST-1234-0003',35,'asdfasdf',NULL,1,1,'pcs',NULL,NULL,'ast_qr_20260226_182239_69a01eef88099.png',1,NULL,'2026-02-26 10:22:39','2026-02-26 10:22:39'),(45,'TEST',1,'AST-TEST-0001',35,'test',NULL,1,1,'pcs',NULL,NULL,'ast_qr_20260227_093702_69a0f53e3295a.png',1,NULL,'2026-02-27 01:37:02','2026-02-27 01:37:02'),(46,'TEST',2,'AST-TEST-0002',35,'test',NULL,1,1,'pcs',NULL,NULL,'ast_qr_20260227_093702_69a0f53e3788d.png',1,NULL,'2026-02-27 01:37:02','2026-02-27 01:37:02'),(47,'TEST',3,'AST-TEST-0003',35,'test',NULL,1,1,'pcs',NULL,NULL,'ast_qr_20260227_093702_69a0f53e3a5e4.png',1,NULL,'2026-02-27 01:37:02','2026-02-27 01:37:02');
/*!40000 ALTER TABLE `ast_inventory` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ast_audit_sessions`
--

DROP TABLE IF EXISTS `ast_audit_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ast_audit_sessions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `series_code` varchar(50) NOT NULL,
  `audit_name` varchar(255) DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `status` enum('Pending','Active','Closed') NOT NULL DEFAULT 'Pending',
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_series_code` (`series_code`),
  KEY `idx_audit_status` (`status`),
  KEY `idx_audit_created_by` (`created_by`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ast_audit_sessions`
--

LOCK TABLES `ast_audit_sessions` WRITE;
/*!40000 ALTER TABLE `ast_audit_sessions` DISABLE KEYS */;
INSERT INTO `ast_audit_sessions` VALUES (1,'AST-PC-2026-001','Test 1','2026-02-18','2026-02-20','Active',1,'2026-02-19 03:28:21');
/*!40000 ALTER TABLE `ast_audit_sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ast_audit_checks`
--

DROP TABLE IF EXISTS `ast_audit_checks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ast_audit_checks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `session_id` int(11) NOT NULL,
  `property_id` int(11) NOT NULL,
  `property_code` varchar(150) NOT NULL,
  `property_number` varchar(100) NOT NULL,
  `item_description` text DEFAULT NULL,
  `serial_number` varchar(150) DEFAULT NULL,
  `quantity_checked` int(11) NOT NULL DEFAULT 1,
  `unit` varchar(50) NOT NULL DEFAULT '',
  `date_stock` datetime DEFAULT NULL,
  `date_issued` date DEFAULT NULL,
  `status_at_check` varchar(100) NOT NULL DEFAULT '',
  `facility` varchar(150) NOT NULL DEFAULT '',
  `accountable` varchar(150) NOT NULL DEFAULT '',
  `issued_to` varchar(150) NOT NULL DEFAULT '',
  `managed_by` varchar(150) NOT NULL DEFAULT '',
  `condition` varchar(50) NOT NULL DEFAULT '',
  `remarks` text DEFAULT NULL,
  `checked_by` int(11) NOT NULL,
  `checked_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_session_property` (`session_id`,`property_code`),
  KEY `idx_audit_session` (`session_id`),
  KEY `idx_audit_property` (`property_id`),
  KEY `idx_audit_checked_by` (`checked_by`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ast_audit_checks`
--

LOCK TABLES `ast_audit_checks` WRITE;
/*!40000 ALTER TABLE `ast_audit_checks` DISABLE KEYS */;
INSERT INTO `ast_audit_checks` VALUES (1,1,21,'AST-0001-0007','0001','upuan','',1,'pcs','2026-02-13 14:36:46',NULL,'','Misd','Ako','Ako','Ako','Good','All gooods',1,'2026-02-19 14:01:53'),(2,1,17,'AST-0001-0004','0001','Malaking monitor','',1,'pcs','2026-02-13 09:36:25',NULL,'','','','','','Good','',1,'2026-02-19 11:33:39'),(3,1,20,'AST-0000-0012','0000','Sofa','',1,'set','2026-02-13 10:30:59',NULL,'','JMC','Ako','Ako','Ako','Good','All goods',1,'2026-02-19 11:35:55'),(4,1,3,'AST-0001-0002','0001','computer na nag aapoy','',1,'set','2026-02-04 08:07:09',NULL,'','','','','','Good','',1,'2026-02-19 14:02:39');
/*!40000 ALTER TABLE `ast_audit_checks` ENABLE KEYS */;
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
SET FOREIGN_KEY_CHECKS=1;
