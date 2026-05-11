-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: e_inventory_csm_tmp
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Dumping data for table `csm_inventory`
--

LOCK TABLES `csm_inventory` WRITE;
/*!40000 ALTER TABLE `csm_inventory` DISABLE KEYS */;
REPLACE INTO `csm_inventory` VALUES (1,'CSM-0001-0001','Bond paper','2026-03-25',0.00,'box','','CSM0001',1,'{\"teaching\":[1,2,3],\"non_teaching\":[1,2,3]}',100,10,5,'2026-05-05','19',NULL,'2026-03-25 07:47:33','2026-05-05 05:51:55',NULL),(2,'CSM-0001-0002','Pens','2026-03-25',0.00,'pcs','','CSM0001',1,'{\"teaching\":[1,2,3],\"non_teaching\":[1,2,3]}',30,10,5,'2026-05-05','18',NULL,'2026-03-25 07:48:32','2026-05-05 05:51:55',NULL),(3,'CSM-0001-0003','Pencil','2026-03-25',0.00,'pcs','','CSM0001',2,'{\"teaching\":[1,2,3],\"non_teaching\":[1,2,3]}',50,10,10,'2026-05-05','13',NULL,'2026-03-25 07:49:32','2026-05-05 05:51:55',NULL),(4,'CSM-0001-0004','markers','2026-03-25',0.00,'pack','','CSM0001',2,'{\"teaching\":[1,2,3],\"non_teaching\":[1,2,3]}',90,10,10,'2026-05-05','14',NULL,'2026-03-25 07:51:23','2026-05-05 05:51:55',NULL),(5,'CSM-0001-0005','folder','2026-03-25',0.00,'pcs','','CSM0001',2,'{\"teaching\":[1,2,3],\"non_teaching\":[1,2,3]}',90,10,10,'2026-05-05','20',NULL,'2026-03-25 07:53:13','2026-05-05 05:51:55',NULL),(6,'CSM-0001-0006','envelops','2026-03-25',0.00,'pcs','','CSM0001',2,'{\"teaching\":[1,2,3],\"non_teaching\":[1,2,3]}',90,10,10,'2026-05-05','21',NULL,'2026-03-25 07:53:57','2026-05-05 05:51:55',NULL),(7,'CSM-0001-0007','stapler wires','2026-03-25',0.00,'box','','CSM0001',2,'{\"teaching\":[1,2,3],\"non_teaching\":[1,2,3]}',150,10,10,'2026-05-05','17',NULL,'2026-03-25 07:54:48','2026-05-05 05:51:55',NULL),(8,'CSM-0001-0008','sticky notes','2026-03-25',0.00,'box','','CSM0001',2,'{\"teaching\":[1,2,3],\"non_teaching\":[1,2,3]}',300,10,50,'2026-05-05','22',NULL,'2026-03-25 07:55:19','2026-05-05 05:51:55',NULL),(9,'CSM-0002-0001','chalk','2026-03-25',0.00,'pack','','CSM0002',2,'{\"teaching\":[1,2,3],\"non_teaching\":[1,2,3]}',100,10,10,'2026-05-05',NULL,NULL,'2026-03-25 07:56:57','2026-05-05 05:51:55',NULL),(10,'CSM-0002-0002','Whiteboard Markers','2026-03-25',0.00,'pack','','CSM0002',2,'{\"teaching\":[1,2,3],\"non_teaching\":[1,2,3]}',150,10,10,'2026-05-05',NULL,NULL,'2026-03-25 07:57:30','2026-05-05 05:51:55',NULL),(11,'CSM-0002-0003','Chalk Erasers','2026-03-25',0.00,'pcs','','CSM0002',2,'{\"teaching\":[1,2,3],\"non_teaching\":[1,2,3]}',50,10,10,'2026-05-05',NULL,NULL,'2026-03-25 07:58:25','2026-05-05 05:51:55',NULL),(12,'CSM-0002-0004','Whiteboard Erasers','2026-03-25',0.00,'pcs','','CSM0002',2,'{\"teaching\":[1,2,3],\"non_teaching\":[1,2,3]}',100,10,10,'2026-05-05',NULL,NULL,'2026-03-25 07:58:56','2026-05-05 05:51:55',NULL),(13,'CSM-0002-0005','Manila Papers','2026-03-25',0.00,'pack','','CSM0002',2,'{\"teaching\":[1,2,3],\"non_teaching\":[1,2,3]}',100,10,10,'2026-05-05',NULL,NULL,'2026-03-25 07:59:27','2026-05-05 05:51:55',NULL),(14,'CSM-0003-0001','Printer Ink','2026-03-25',0.00,'pack','','CSM0003',2,'{\"teaching\":[1,2,3],\"non_teaching\":[1,2,3]}',100,10,10,'2026-05-05',NULL,NULL,'2026-03-25 08:01:26','2026-05-05 05:51:55',NULL),(15,'CSM-0003-0002','Ink Bottles','2026-03-25',0.00,'box','','CSM0003',2,'{\"teaching\":[1,2,3],\"non_teaching\":[1,2,3]}',90,10,10,'2026-05-05',NULL,NULL,'2026-03-25 08:02:32','2026-05-05 05:51:55',NULL),(16,'CSM-0004-0001','Detergent','2026-03-25',0.00,'pcs','','CSM0004',2,'{\"teaching\":[1,2,3],\"non_teaching\":[1,2,3]}',40,10,10,'2026-05-05',NULL,NULL,'2026-03-25 08:04:10','2026-05-05 05:51:55',NULL),(17,'CSM-0004-0002','Bleach','2026-03-25',0.00,'pcs','','CSM0004',2,'{\"teaching\":[1,2,3],\"non_teaching\":[1,2,3]}',50,10,10,'2026-05-05',NULL,NULL,'2026-03-25 08:04:31','2026-05-05 05:51:55',NULL),(18,'CSM-0004-0003','Disinfectant','2026-03-25',0.00,'box','','CSM0004',2,'{\"teaching\":[1,2,3],\"non_teaching\":[1,2,3]}',90,10,10,'2026-05-05',NULL,NULL,'2026-03-25 08:23:04','2026-05-05 05:51:55',NULL),(19,'CSM-0004-0004','Trash Bags','2026-03-25',0.00,'pcs','','CSM0004',2,'{\"teaching\":[1,2,3],\"non_teaching\":[1,2,3]}',90,10,10,'2026-05-05',NULL,NULL,'2026-03-25 08:31:02','2026-05-05 05:51:55',NULL),(20,'CSM-0004-0005','Floor Cleaner','2026-03-25',0.00,'box','','CSM0004',2,'{\"teaching\":[1,2,3],\"non_teaching\":[1,2,3]}',100,10,10,'2026-05-05',NULL,NULL,'2026-03-25 08:33:05','2026-05-05 05:51:55',NULL),(21,'CSM-0004-0006','Air Freshener','2026-03-25',0.00,'pack','','CSM0004',2,'{\"teaching\":[1,2,3],\"non_teaching\":[1,2,3]}',90,10,10,'2026-05-05',NULL,NULL,'2026-03-25 08:51:15','2026-05-05 05:51:55',NULL),(22,'CSM-0004-0007','Gloves','2026-03-25',0.00,'pack','','CSM0004',2,'{\"teaching\":[1,2,3],\"non_teaching\":[1,2,3]}',20,10,10,'2026-05-05',NULL,NULL,'2026-03-25 08:54:05','2026-05-05 05:51:55',NULL),(23,'CSM-0005-0001','Tissue Roll','2026-03-25',0.00,'box','','CSM0005',2,'{\"teaching\":[1,2,3],\"non_teaching\":[1,2,3]}',30,10,10,'2026-05-05',NULL,NULL,'2026-03-25 08:55:13','2026-05-05 05:51:55',NULL),(24,'CSM-0005-0002','Hand Soap','2026-03-25',0.00,'pcs','','CSM0005',2,'{\"teaching\":[1,2,3],\"non_teaching\":[1,2,3]}',50,10,10,'2026-05-05',NULL,NULL,'2026-03-25 09:06:18','2026-05-05 05:51:55',NULL),(25,'CSM-0005-0003','Paper Towel Rolls','2026-03-25',0.00,'box','','CSM0005',2,'{\"teaching\":[1,2,3],\"non_teaching\":[1,2,3]}',70,10,10,'2026-05-05',NULL,NULL,'2026-03-25 09:06:42','2026-05-05 05:51:55',NULL),(26,'CSM-0005-0004','Disposal Bags','2026-03-25',0.00,'pack','','CSM0005',2,'{\"teaching\":[1,2,3],\"non_teaching\":[1,2,3]}',100,10,10,'2026-05-05',NULL,NULL,'2026-03-25 09:07:07','2026-05-05 05:51:55',NULL),(27,'CSM-0006-0001','Cotton','2026-03-25',0.00,'box','','CSM0006',2,'{\"teaching\":[1,2,3],\"non_teaching\":[1,2,3]}',40,10,10,'2026-05-05',NULL,NULL,'2026-03-25 09:13:28','2026-05-05 05:51:55',NULL),(28,'CSM-0006-0002','Gauze','2026-03-25',0.00,'box','','CSM0006',1,'{\"teaching\":[1,2,3],\"non_teaching\":[1,2,3]}',20,10,5,'2026-05-05',NULL,NULL,'2026-03-25 09:13:50','2026-05-05 05:51:55',NULL),(29,'CSM-0006-0003','Sanitized Alcohol','2026-03-25',0.00,'pcs','','CSM0006',1,'{\"teaching\":[1,2,3],\"non_teaching\":[1,2,3]}',33,10,5,'2026-05-05',NULL,NULL,'2026-03-25 09:14:21','2026-05-05 05:51:55',NULL),(30,'CSM-0006-0004','Sanitized Gloves','2026-03-25',0.00,'pack','','CSM0006',2,'{\"teaching\":[1,2,3],\"non_teaching\":[1,2,3]}',30,10,10,'2026-05-05',NULL,NULL,'2026-03-25 09:14:51','2026-05-05 05:51:55',NULL),(31,'CSM-0006-0005','Antiseptic Bottle','2026-03-25',0.00,'pcs','','CSM0006',2,'{\"teaching\":[1,2,3],\"non_teaching\":[1,2,3]}',35,10,10,'2026-05-05',NULL,NULL,'2026-03-25 09:15:18','2026-05-05 05:51:55',NULL),(32,'CSM-0007-0001','Batteries','2026-03-25',0.00,'pack','','CSM0007',2,'{\"teaching\":[1,2,3],\"non_teaching\":[1,2,3]}',90,10,10,'2026-05-05',NULL,NULL,'2026-03-25 09:22:30','2026-05-05 05:51:55',NULL),(33,'CSM-0007-0002','Electric Tape','2026-03-25',0.00,'pack','','CSM0007',2,'{\"teaching\":[1,2,3],\"non_teaching\":[1,2,3]}',90,10,10,'2026-05-05',NULL,NULL,'2026-03-25 09:24:39','2026-05-05 05:51:55',NULL),(34,'CSM-0007-0003','Cable Ties','2026-03-25',0.00,'pack','','CSM0007',2,'{\"teaching\":[1,2,3],\"non_teaching\":[1,2,3]}',30,10,10,'2026-05-05',NULL,NULL,'2026-03-25 09:25:21','2026-05-05 05:51:55',NULL),(35,'CSM-0007-0004','Screws','2026-03-25',0.00,'pack','','CSM0007',2,'{\"teaching\":[1,2,3],\"non_teaching\":[1,2,3]}',78,10,10,'2026-05-05',NULL,NULL,'2026-03-25 09:25:53','2026-05-05 05:51:55',NULL),(36,'CSM-0008-0001','Cleaning Rags','2026-03-25',0.00,'pcs','','CSM0008',1,'{\"teaching\":[1,2,3],\"non_teaching\":[1,2,3]}',10,10,2,'2026-05-05',NULL,NULL,'2026-03-25 09:26:32','2026-05-05 05:51:55',NULL),(37,'CSM-0008-0002','Mineral Water','2026-03-25',0.00,'pack','','CSM0008',1,'{\"teaching\":[1,2,3],\"non_teaching\":[1,2,3]}',22,10,5,'2026-05-05',NULL,NULL,'2026-03-25 09:27:34','2026-05-05 05:51:55',NULL),(38,'CSM-0008-0003','Disposable Cups','2026-03-25',0.00,'pack','','CSM0008',1,'{\"teaching\":[1,2,3],\"non_teaching\":[1,2,3]}',37,10,9,'2026-05-05',NULL,NULL,'2026-03-25 09:30:54','2026-05-05 05:51:55',NULL),(39,'CSM-0008-0004','Paper Plates','2026-03-25',0.00,'pack','','CSM0008',1,'{\"teaching\":[1,2,3],\"non_teaching\":[1,2,3]}',38,10,9,'2026-05-05',NULL,NULL,'2026-03-25 09:31:28','2026-05-05 05:51:55',NULL),(40,'CSM-0008-0005','Disposable Utensils','2026-03-25',0.00,'pack','','CSM0008',2,'{\"teaching\":[1,2,3],\"non_teaching\":[1,2,3]}',55,10,10,'2026-05-05',NULL,NULL,'2026-03-25 09:32:00','2026-05-05 05:51:55',NULL),(41,'CSM-0008-0006','Napkins','2026-03-25',0.00,'pack','','CSM0008',1,'{\"teaching\":[1,2,3],\"non_teaching\":[1,2,3]}',44,10,9,'2026-05-05',NULL,NULL,'2026-03-25 09:32:22','2026-05-05 05:51:55',NULL),(42,'CSM-0009-0001','Triple A Batteries','2026-03-25',0.00,'pack','','CSM0009',2,'{\"teaching\":[1,2,3],\"non_teaching\":[1,2,3]}',70,10,10,'2026-05-05',NULL,NULL,'2026-03-25 09:36:55','2026-05-05 05:51:55',NULL),(43,'CSM-0009-0002','blank DVDs','2026-03-26',0.00,'pcs','','CSM0009',2,'{\"teaching\":[1,2,3],\"non_teaching\":[1,2,3]}',50,10,10,'2026-05-05',NULL,NULL,'2026-03-26 05:12:17','2026-05-05 05:51:55',NULL),(44,'CSM-0002-0006','test','2026-03-26',0.00,'pack','','CSM0002',1,'{\"teaching\":[1,2,3],\"non_teaching\":[1,2,3]}',10,10,5,'2026-05-05',NULL,NULL,'2026-03-26 06:21:56','2026-05-05 05:51:55',NULL),(45,'CSM-0010-0001','test log 1','2026-05-05',555.00,'pcs','test log related','CSM0010',1,'{\"teaching\":[1,3],\"non_teaching\":[1,2]}',57,43,5,'2026-05-05',NULL,NULL,'2026-05-05 05:52:46','2026-05-05 07:07:52',NULL);
/*!40000 ALTER TABLE `csm_inventory` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `csm_inventory_category`
--

LOCK TABLES `csm_inventory_category` WRITE;
/*!40000 ALTER TABLE `csm_inventory_category` DISABLE KEYS */;
REPLACE INTO `csm_inventory_category` VALUES (1,'Office Supplies',NULL,'CSM0001',NULL,'2026-03-25 07:45:14','2026-03-25 07:45:14'),(2,'Classroom Supplies',NULL,'CSM0002',NULL,'2026-03-25 07:45:14','2026-03-25 07:45:14'),(3,'Printing Supplies',NULL,'CSM0003',NULL,'2026-03-25 07:45:14','2026-03-25 07:45:14'),(4,'Janitorial Supplies',NULL,'CSM0004',NULL,'2026-03-25 07:45:14','2026-03-25 07:45:14'),(5,'Restroom Supplies',NULL,'CSM0005',NULL,'2026-03-25 07:45:14','2026-03-25 07:45:14'),(6,'Clinic / First Aid Supplies',NULL,'CSM0006',NULL,'2026-03-25 07:45:15','2026-03-25 07:45:15'),(7,'Electrical / Maintenance Supplies',NULL,'CSM0007',NULL,'2026-03-25 07:45:15','2026-03-25 07:45:15'),(8,'Canteen / Pantry Supplies',NULL,'CSM0008',NULL,'2026-03-25 07:45:15','2026-03-25 07:45:15'),(9,'ICT / Computer Consumables',NULL,'CSM0009',NULL,'2026-03-25 07:45:15','2026-03-25 07:45:15'),(16,'test log 1',NULL,'CSM0010',NULL,'2026-05-05 05:39:13','2026-05-05 05:44:38'),(17,'test log 2',NULL,'CSM0011',NULL,'2026-05-05 05:44:24','2026-05-05 05:44:44');
/*!40000 ALTER TABLE `csm_inventory_category` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `csm_inventory_category_images`
--

LOCK TABLES `csm_inventory_category_images` WRITE;
/*!40000 ALTER TABLE `csm_inventory_category_images` DISABLE KEYS */;
REPLACE INTO `csm_inventory_category_images` VALUES (13,1,'cat_1_1774502433_0_shopping__1_.webp','upload/category/cat_1_1774502433_0_shopping__1_.webp',0,'2026-03-26 05:20:33'),(14,1,'cat_1_1774502436_0_shopping.webp','upload/category/cat_1_1774502436_0_shopping.webp',0,'2026-03-26 05:20:36'),(17,1,'cat_1_1774502462_0_23-15-scaled.jpg','upload/category/cat_1_1774502462_0_23-15-scaled.jpg',0,'2026-03-26 05:21:02'),(18,1,'cat_1_1774502584_0_109198_r_1.webp','upload/category/cat_1_1774502584_0_109198_r_1.webp',0,'2026-03-26 05:23:04'),(19,1,'cat_1_1774502634_0_1.jpg','upload/category/cat_1_1774502634_0_1.jpg',0,'2026-03-26 05:23:54'),(20,1,'cat_1_1774502637_0_8591_3586.jpg','upload/category/cat_1_1774502637_0_8591_3586.jpg',0,'2026-03-26 05:23:57'),(21,1,'cat_1_1774502671_0_envelops.jpg','upload/category/cat_1_1774502671_0_envelops.jpg',0,'2026-03-26 05:24:31'),(22,1,'cat_1_1774502710_0_5405_5405.jpg','upload/category/cat_1_1774502710_0_5405_5405.jpg',0,'2026-03-26 05:25:10'),(23,1,'cat_1_1774502740_0_office_supplies-min-1024x684.jpg','upload/category/cat_1_1774502740_0_office_supplies-min-1024x684.jpg',1,'2026-03-26 05:25:40'),(24,17,'cat_17_1777960075_0_xiseven.png','upload/category/cat_17_1777960075_0_xiseven.png',1,'2026-05-05 05:47:55');
/*!40000 ALTER TABLE `csm_inventory_category_images` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `csm_audit_sessions`
--

LOCK TABLES `csm_audit_sessions` WRITE;
/*!40000 ALTER TABLE `csm_audit_sessions` DISABLE KEYS */;
/*!40000 ALTER TABLE `csm_audit_sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `csm_audit_checks`
--

LOCK TABLES `csm_audit_checks` WRITE;
/*!40000 ALTER TABLE `csm_audit_checks` DISABLE KEYS */;
/*!40000 ALTER TABLE `csm_audit_checks` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-05-11  8:48:13
