-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: pendingwithsarim
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
-- Current Database: `pendingwithsarim`
--

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `pendingwithsarim` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci */;

USE `pendingwithsarim`;

--
-- Table structure for table `delivery_log`
--

DROP TABLE IF EXISTS `delivery_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `delivery_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `qty_delivered` int(11) NOT NULL DEFAULT 0,
  `dc_no` varchar(100) DEFAULT NULL,
  `vehicle_no` varchar(50) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `delivery_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  CONSTRAINT `delivery_log_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `delivery_log`
--

LOCK TABLES `delivery_log` WRITE;
/*!40000 ALTER TABLE `delivery_log` DISABLE KEYS */;
INSERT INTO `delivery_log` VALUES (7,27,5,'TEST','1','x','2026-09-10','2026-09-10 05:46:33'),(8,27,100,'9u131','1233','ahmed','2026-11-10','2026-09-10 05:46:59');
/*!40000 ALTER TABLE `delivery_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `items`
--

DROP TABLE IF EXISTS `items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `unit_price` decimal(10,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `pcs_per_plate` int(11) NOT NULL DEFAULT 36,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `items`
--

LOCK TABLES `items` WRITE;
/*!40000 ALTER TABLE `items` DISABLE KEYS */;
INSERT INTO `items` VALUES (1,'Basmati Rice 5kg','Rice',1500.00,'2026-09-08 10:59:45',36),(2,'Basmati Rice 20kg','Rice',5500.00,'2026-09-08 10:59:45',20),(3,'IRRI Rice 10kg','Rice',2000.00,'2026-09-08 10:59:45',36),(4,'Wheat Flour 10kg','Flour',1200.00,'2026-09-08 10:59:45',36),(5,'Sugar 5kg','Sugar',650.00,'2026-09-08 10:59:45',36),(6,'Cooking Oil 1L','Oil',450.00,'2026-09-08 10:59:45',44),(7,'Cooking Oil 5L','Oil',2100.00,'2026-09-08 10:59:45',36),(8,'TestItem999','Test',0.00,'2026-09-10 08:03:15',36),(9,'nokia','',0.00,'2026-09-10 08:03:59',36),(10,'iphone','',0.00,'2026-09-10 09:16:08',55);
/*!40000 ALTER TABLE `items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `party_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `booked_qty` int(11) NOT NULL DEFAULT 0,
  `dispatched_qty` int(11) NOT NULL DEFAULT 0,
  `status` enum('PENDING','PARTIAL','COMPLETED','CANCELLED') DEFAULT 'PENDING',
  `ref_no` varchar(100) DEFAULT NULL,
  `order_date` date DEFAULT NULL,
  `item_condition` varchar(50) DEFAULT 'Fresh',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `party_id` (`party_id`),
  KEY `item_id` (`item_id`),
  CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`party_id`) REFERENCES `parties` (`id`) ON DELETE CASCADE,
  CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `orders`
--

LOCK TABLES `orders` WRITE;
/*!40000 ALTER TABLE `orders` DISABLE KEYS */;
INSERT INTO `orders` VALUES (27,9,5,200,105,'PARTIAL','21212','2026-09-09','FRESH','2026-09-09 10:34:40'),(28,12,2,2000,0,'CANCELLED','23232','2026-09-09','FRESH','2026-09-09 13:50:25'),(29,11,6,500,0,'CANCELLED','46677','2026-09-11','DAMAGED','2026-09-10 05:41:31'),(30,4,2,200,0,'CANCELLED','9993','2026-09-10','FRESH','2026-09-10 06:10:41'),(31,4,2,200,0,'CANCELLED','321','2026-09-10','DAMAGED','2026-09-10 06:11:28');
/*!40000 ALTER TABLE `orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `parties`
--

DROP TABLE IF EXISTS `parties`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `parties` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `parties`
--

LOCK TABLES `parties` WRITE;
/*!40000 ALTER TABLE `parties` DISABLE KEYS */;
INSERT INTO `parties` VALUES (1,'Ahmed Traders','0300-1234567','Lahore','2026-09-08 10:59:45'),(2,'Sarim Enterprises','0321-9876543','Karachi','2026-09-08 10:59:45'),(3,'Hassan & Sons','0333-5551234','Islamabad','2026-09-08 10:59:45'),(4,'Ali Brothers','0345-7778888','Faisalabad','2026-09-08 10:59:45'),(5,'ABC',NULL,NULL,'2026-09-08 12:00:36'),(6,'XYZ',NULL,NULL,'2026-09-08 12:20:36'),(7,'FlowTest',NULL,NULL,'2026-09-08 12:23:28'),(8,'FinalTest',NULL,NULL,'2026-09-08 12:26:03'),(9,'BugTest',NULL,NULL,'2026-09-08 12:27:17'),(10,'FlowFinal',NULL,NULL,'2026-09-08 12:30:13'),(11,'CompleteTest',NULL,NULL,'2026-09-08 12:32:36'),(12,'FixTest',NULL,NULL,'2026-09-08 12:34:16'),(13,'talal',NULL,NULL,'2026-09-08 12:42:36'),(14,'hamza',NULL,NULL,'2026-09-08 12:45:35'),(15,'CancelTest',NULL,NULL,'2026-09-08 12:52:13'),(16,'StockTest',NULL,NULL,'2026-09-08 13:09:41'),(17,'CleanupTest',NULL,NULL,'2026-09-08 13:14:26'),(18,'ali',NULL,NULL,'2026-09-08 13:20:11'),(19,'ApiTest',NULL,NULL,'2026-09-08 13:28:59'),(20,'testpartiesx','123','','2026-09-09 09:22:22');
/*!40000 ALTER TABLE `parties` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stock_in`
--

DROP TABLE IF EXISTS `stock_in`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stock_in` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `item_id` int(11) NOT NULL,
  `qty` int(11) NOT NULL DEFAULT 0,
  `supplier` varchar(255) DEFAULT NULL,
  `in_date` date DEFAULT NULL,
  `condition` varchar(50) DEFAULT 'fresh',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `item_id` (`item_id`),
  CONSTRAINT `stock_in_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=211 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stock_in`
--

LOCK TABLES `stock_in` WRITE;
/*!40000 ALTER TABLE `stock_in` DISABLE KEYS */;
INSERT INTO `stock_in` VALUES (47,2,20,'Pallet','2026-09-09','fresh','Pallet:diwan 1','2026-09-09 13:44:46'),(48,2,20,'Pallet','2026-09-09','fresh','Pallet:diwan 3','2026-09-09 13:44:46'),(49,2,20,'Pallet','2026-09-09','fresh','Pallet:diwan 2','2026-09-09 13:44:46'),(50,2,20,'Pallet','2026-09-09','fresh','Pallet:diwan 4','2026-09-09 13:44:46'),(51,2,20,'Pallet','2026-09-09','fresh','Pallet:diwan 7','2026-09-09 13:44:46'),(52,2,20,'Pallet','2026-09-09','fresh','Pallet:diwan 8','2026-09-09 13:44:46'),(53,2,20,'Pallet','2026-09-09','fresh','Pallet:diwan 9','2026-09-09 13:44:46'),(54,2,20,'Pallet','2026-09-09','fresh','Pallet:diwan 10','2026-09-09 13:44:46'),(55,2,20,'Pallet','2026-09-09','fresh','Pallet:diwan 11','2026-09-09 13:44:46'),(56,2,20,'Pallet','2026-09-09','fresh','Pallet:diwan 12','2026-09-09 13:44:46'),(57,2,20,'Pallet','2026-09-09','fresh','Pallet:diwan 13','2026-09-09 13:44:46'),(58,2,20,'Pallet','2026-09-09','fresh','Pallet:diwan 6','2026-09-09 13:44:46'),(59,2,20,'Pallet','2026-09-09','fresh','Pallet:diwan 14','2026-09-09 13:44:46'),(60,2,20,'Pallet','2026-09-09','fresh','Pallet:diwan 15','2026-09-09 13:44:46'),(61,2,20,'Pallet','2026-09-09','fresh','Pallet:diwan 17','2026-09-09 13:44:46'),(62,2,20,'Pallet','2026-09-09','fresh','Pallet:diwan 5','2026-09-09 13:44:46'),(63,2,20,'Pallet','2026-09-09','fresh','Pallet:diwan 18','2026-09-09 13:44:46'),(64,2,20,'Pallet','2026-09-09','fresh','Pallet:diwan 16','2026-09-09 13:44:46'),(65,2,20,'Pallet','2026-09-09','fresh','Pallet:diwan 19','2026-09-09 13:44:46'),(66,2,20,'Pallet','2026-09-09','fresh','Pallet:diwan 20','2026-09-09 13:44:46'),(67,2,20,'Pallet','2026-09-09','fresh','Pallet:diwan 1','2026-09-09 13:45:18'),(68,2,20,'Pallet','2026-09-09','fresh','Pallet:diwan 2','2026-09-09 13:45:18'),(69,2,20,'Pallet','2026-09-09','fresh','Pallet:diwan 7','2026-09-09 13:45:18'),(70,2,20,'Pallet','2026-09-09','fresh','Pallet:diwan 4','2026-09-09 13:45:18'),(71,2,20,'Pallet','2026-09-09','fresh','Pallet:diwan 6','2026-09-09 13:45:18'),(72,2,20,'Pallet','2026-09-09','fresh','Pallet:diwan 3','2026-09-09 13:45:18'),(73,2,20,'Pallet','2026-09-09','fresh','Pallet:diwan 8','2026-09-09 13:45:18'),(74,2,20,'Pallet','2026-09-09','fresh','Pallet:diwan 5','2026-09-09 13:45:18'),(75,2,20,'Pallet','2026-09-09','fresh','Pallet:diwan 11','2026-09-09 13:45:18'),(76,2,20,'Pallet','2026-09-09','fresh','Pallet:diwan 10','2026-09-09 13:45:18'),(77,2,20,'Pallet','2026-09-09','fresh','Pallet:diwan 9','2026-09-09 13:45:18'),(78,2,20,'Pallet','2026-09-09','fresh','Pallet:diwan 12','2026-09-09 13:45:18'),(79,2,20,'Pallet','2026-09-09','fresh','Pallet:diwan 13','2026-09-09 13:45:18'),(80,2,20,'Pallet','2026-09-09','fresh','Pallet:diwan 14','2026-09-09 13:45:18'),(81,2,20,'Pallet','2026-09-09','fresh','Pallet:diwan 16','2026-09-09 13:45:18'),(82,2,20,'Pallet','2026-09-09','fresh','Pallet:diwan 17','2026-09-09 13:45:18'),(83,2,20,'Pallet','2026-09-09','fresh','Pallet:diwan 18','2026-09-09 13:45:18'),(84,2,20,'Pallet','2026-09-09','fresh','Pallet:diwan 15','2026-09-09 13:45:18'),(85,2,20,'Pallet','2026-09-09','fresh','Pallet:diwan 19','2026-09-09 13:45:18'),(86,2,20,'Pallet','2026-09-09','fresh','Pallet:diwan 20','2026-09-09 13:45:18'),(87,2,20,'Pallet','2026-09-09','fresh','Pallet:diwan 3','2026-09-09 13:46:28'),(88,2,20,'Pallet','2026-09-09','fresh','Pallet:diwan 4','2026-09-09 13:46:28'),(89,2,20,'Pallet','2026-09-09','fresh','Pallet:diwan 1','2026-09-09 13:46:28'),(90,2,20,'Pallet','2026-09-09','fresh','Pallet:diwan 2','2026-09-09 13:46:28'),(91,2,20,'Pallet','2026-09-09','fresh','Pallet:diwan 9','2026-09-09 13:46:28'),(92,2,20,'Pallet','2026-09-09','fresh','Pallet:diwan 7','2026-09-09 13:46:28'),(93,2,20,'Pallet','2026-09-09','fresh','Pallet:diwan 8','2026-09-09 13:46:28'),(94,2,20,'Pallet','2026-09-09','fresh','Pallet:diwan 5','2026-09-09 13:46:28'),(95,2,20,'Pallet','2026-09-09','fresh','Pallet:diwan 10','2026-09-09 13:46:28'),(96,2,20,'Pallet','2026-09-09','fresh','Pallet:diwan 11','2026-09-09 13:46:28'),(97,2,20,'Pallet','2026-09-09','fresh','Pallet:diwan 6','2026-09-09 13:46:28'),(98,2,20,'Pallet','2026-09-09','fresh','Pallet:diwan 12','2026-09-09 13:46:28'),(99,2,20,'Pallet','2026-09-09','fresh','Pallet:diwan 13','2026-09-09 13:46:28'),(100,2,20,'Pallet','2026-09-09','fresh','Pallet:diwan 14','2026-09-09 13:46:28'),(101,2,20,'Pallet','2026-09-09','fresh','Pallet:diwan 17','2026-09-09 13:46:28'),(102,2,20,'Pallet','2026-09-09','fresh','Pallet:diwan 16','2026-09-09 13:46:28'),(103,2,20,'Pallet','2026-09-09','fresh','Pallet:diwan 18','2026-09-09 13:46:28'),(104,2,20,'Pallet','2026-09-09','fresh','Pallet:diwan 15','2026-09-09 13:46:28'),(105,2,20,'Pallet','2026-09-09','fresh','Pallet:diwan 20','2026-09-09 13:46:28'),(106,2,20,'Pallet','2026-09-09','fresh','Pallet:diwan 19','2026-09-09 13:46:28'),(107,2,20,'Pallet','2026-09-09','fresh','Pallet:diwan 1','2026-09-09 13:47:23'),(108,2,20,'Pallet','2026-09-09','fresh','Pallet:diwan 5','2026-09-09 13:47:23'),(109,2,20,'Pallet','2026-09-09','fresh','Pallet:diwan 7','2026-09-09 13:47:23'),(110,2,20,'Pallet','2026-09-09','fresh','Pallet:diwan 3','2026-09-09 13:47:23'),(111,2,20,'Pallet','2026-09-09','fresh','Pallet:diwan 6','2026-09-09 13:47:23'),(112,2,20,'Pallet','2026-09-09','fresh','Pallet:diwan 4','2026-09-09 13:47:23'),(113,2,20,'Pallet','2026-09-09','fresh','Pallet:diwan 8','2026-09-09 13:47:23'),(114,2,20,'Pallet','2026-09-09','fresh','Pallet:diwan 9','2026-09-09 13:47:23'),(115,2,20,'Pallet','2026-09-09','fresh','Pallet:diwan 2','2026-09-09 13:47:23'),(116,2,20,'Pallet','2026-09-09','fresh','Pallet:diwan 10','2026-09-09 13:47:23'),(117,2,20,'Pallet','2026-09-09','fresh','Pallet:diwan 12','2026-09-09 13:47:23'),(118,2,20,'Pallet','2026-09-09','fresh','Pallet:diwan 11','2026-09-09 13:47:23'),(119,2,20,'Pallet','2026-09-09','fresh','Pallet:diwan 14','2026-09-09 13:47:23'),(120,2,20,'Pallet','2026-09-09','fresh','Pallet:diwan 13','2026-09-09 13:47:23'),(121,2,20,'Pallet','2026-09-09','fresh','Pallet:diwan 16','2026-09-09 13:47:23'),(122,2,20,'Pallet','2026-09-09','fresh','Pallet:diwan 15','2026-09-09 13:47:23'),(123,2,20,'Pallet','2026-09-09','fresh','Pallet:diwan 18','2026-09-09 13:47:23'),(124,2,20,'Pallet','2026-09-09','fresh','Pallet:diwan 17','2026-09-09 13:47:23'),(125,2,20,'Pallet','2026-09-09','fresh','Pallet:diwan 19','2026-09-09 13:47:23'),(126,2,20,'Pallet','2026-09-09','fresh','Pallet:diwan 20','2026-09-09 13:47:23'),(127,2,23,'Pallet','2026-09-09','fresh','Pallet:TESTCHECK1','2026-09-09 13:48:20'),(128,2,20,'Pallet','2026-09-09','fresh','Pallet:diwan 1','2026-09-09 13:48:41'),(129,2,20,'Pallet','2026-09-09','fresh','Pallet:diwan 3','2026-09-09 13:48:41'),(130,2,20,'Pallet','2026-09-09','fresh','Pallet:diwan 7','2026-09-09 13:48:41'),(131,2,20,'Pallet','2026-09-09','fresh','Pallet:diwan 8','2026-09-09 13:48:41'),(132,2,20,'Pallet','2026-09-09','fresh','Pallet:diwan 2','2026-09-09 13:48:41'),(133,2,20,'Pallet','2026-09-09','fresh','Pallet:diwan 5','2026-09-09 13:48:41'),(134,2,20,'Pallet','2026-09-09','fresh','Pallet:diwan 6','2026-09-09 13:48:41'),(135,2,20,'Pallet','2026-09-09','fresh','Pallet:diwan 4','2026-09-09 13:48:41'),(136,2,20,'Pallet','2026-09-09','fresh','Pallet:diwan 9','2026-09-09 13:48:41'),(137,2,20,'Pallet','2026-09-09','fresh','Pallet:diwan 10','2026-09-09 13:48:41'),(138,2,20,'Pallet','2026-09-09','fresh','Pallet:diwan 11','2026-09-09 13:48:41'),(139,2,20,'Pallet','2026-09-09','fresh','Pallet:diwan 12','2026-09-09 13:48:41'),(140,2,20,'Pallet','2026-09-09','fresh','Pallet:diwan 13','2026-09-09 13:48:41'),(141,2,20,'Pallet','2026-09-09','fresh','Pallet:diwan 15','2026-09-09 13:48:41'),(142,2,20,'Pallet','2026-09-09','fresh','Pallet:diwan 17','2026-09-09 13:48:41'),(143,2,20,'Pallet','2026-09-09','fresh','Pallet:diwan 18','2026-09-09 13:48:41'),(144,2,20,'Pallet','2026-09-09','fresh','Pallet:diwan 19','2026-09-09 13:48:41'),(145,2,20,'Pallet','2026-09-09','fresh','Pallet:diwan 14','2026-09-09 13:48:41'),(146,2,20,'Pallet','2026-09-09','fresh','Pallet:diwan 16','2026-09-09 13:48:41'),(147,2,20,'Pallet','2026-09-09','fresh','Pallet:diwan 20','2026-09-09 13:48:41'),(148,2,200,'hamza22','2026-09-09','fresh','hamza22','2026-09-09 13:48:44'),(149,2,200,'hamza22','2026-09-09','damaged','hamza22','2026-09-09 13:48:44'),(150,6,44,'Pallet','2026-09-10','fresh','Pallet:huwvi 1','2026-09-10 05:38:34'),(151,6,44,'Pallet','2026-09-10','fresh','Pallet:huwvi 2','2026-09-10 05:38:34'),(152,6,44,'Pallet','2026-09-10','fresh','Pallet:huwvi 3','2026-09-10 05:38:34'),(153,6,44,'Pallet','2026-09-10','fresh','Pallet:huwvi 6','2026-09-10 05:38:34'),(154,6,44,'Pallet','2026-09-10','fresh','Pallet:huwvi 4','2026-09-10 05:38:34'),(155,6,44,'Pallet','2026-09-10','fresh','Pallet:huwvi 5','2026-09-10 05:38:34'),(156,6,44,'Pallet','2026-09-10','fresh','Pallet:huwvi 7','2026-09-10 05:38:34'),(157,6,44,'Pallet','2026-09-10','fresh','Pallet:huwvi 8','2026-09-10 05:38:34'),(158,6,44,'Pallet','2026-09-10','fresh','Pallet:huwvi 10','2026-09-10 05:38:34'),(159,6,44,'Pallet','2026-09-10','fresh','Pallet:huwvi 11','2026-09-10 05:38:34'),(160,6,44,'Pallet','2026-09-10','fresh','Pallet:huwvi 9','2026-09-10 05:38:34'),(161,6,44,'Pallet','2026-09-10','fresh','Pallet:huwvi 13','2026-09-10 05:38:34'),(162,6,44,'Pallet','2026-09-10','fresh','Pallet:huwvi 12','2026-09-10 05:38:34'),(163,6,44,'Pallet','2026-09-10','fresh','Pallet:huwvi 14','2026-09-10 05:38:34'),(164,6,44,'Pallet','2026-09-10','fresh','Pallet:huwvi 16','2026-09-10 05:38:34'),(165,6,44,'Pallet','2026-09-10','fresh','Pallet:huwvi 15','2026-09-10 05:38:34'),(166,6,44,'Pallet','2026-09-10','fresh','Pallet:huwvi 17','2026-09-10 05:38:34'),(167,6,44,'Pallet','2026-09-10','fresh','Pallet:huwvi 20','2026-09-10 05:38:34'),(168,6,44,'Pallet','2026-09-10','fresh','Pallet:huwvi 18','2026-09-10 05:38:34'),(169,6,44,'Pallet','2026-09-10','fresh','Pallet:huwvi 19','2026-09-10 05:38:34'),(170,6,44,'Pallet','2026-09-10','fresh','Pallet:huwvi 21','2026-09-10 05:38:34'),(171,6,44,'Pallet','2026-09-10','fresh','Pallet:huwvi 22','2026-09-10 05:38:34'),(172,6,44,'Pallet','2026-09-10','fresh','Pallet:huwvi 23','2026-09-10 05:38:34'),(173,6,44,'Pallet','2026-09-10','fresh','Pallet:huwvi 1','2026-09-10 05:38:39'),(174,6,44,'Pallet','2026-09-10','fresh','Pallet:huwvi 2','2026-09-10 05:38:39'),(175,6,44,'Pallet','2026-09-10','fresh','Pallet:huwvi 6','2026-09-10 05:38:39'),(176,6,44,'Pallet','2026-09-10','fresh','Pallet:huwvi 3','2026-09-10 05:38:39'),(177,6,44,'Pallet','2026-09-10','fresh','Pallet:huwvi 4','2026-09-10 05:38:39'),(178,6,44,'Pallet','2026-09-10','fresh','Pallet:huwvi 7','2026-09-10 05:38:39'),(179,6,44,'Pallet','2026-09-10','fresh','Pallet:huwvi 8','2026-09-10 05:38:39'),(180,6,44,'Pallet','2026-09-10','fresh','Pallet:huwvi 5','2026-09-10 05:38:39'),(181,6,44,'Pallet','2026-09-10','fresh','Pallet:huwvi 9','2026-09-10 05:38:39'),(182,6,44,'Pallet','2026-09-10','fresh','Pallet:huwvi 10','2026-09-10 05:38:39'),(183,6,44,'Pallet','2026-09-10','fresh','Pallet:huwvi 11','2026-09-10 05:38:39'),(184,6,44,'Pallet','2026-09-10','fresh','Pallet:huwvi 12','2026-09-10 05:38:39'),(185,6,44,'Pallet','2026-09-10','fresh','Pallet:huwvi 14','2026-09-10 05:38:39'),(186,6,44,'Pallet','2026-09-10','fresh','Pallet:huwvi 13','2026-09-10 05:38:39'),(187,6,44,'Pallet','2026-09-10','fresh','Pallet:huwvi 16','2026-09-10 05:38:39'),(188,6,44,'Pallet','2026-09-10','fresh','Pallet:huwvi 15','2026-09-10 05:38:39'),(189,6,44,'Pallet','2026-09-10','fresh','Pallet:huwvi 17','2026-09-10 05:38:39'),(190,6,44,'Pallet','2026-09-10','fresh','Pallet:huwvi 18','2026-09-10 05:38:39'),(191,6,44,'Pallet','2026-09-10','fresh','Pallet:huwvi 20','2026-09-10 05:38:39'),(192,6,44,'Pallet','2026-09-10','fresh','Pallet:huwvi 19','2026-09-10 05:38:39'),(193,6,44,'Pallet','2026-09-10','fresh','Pallet:huwvi 21','2026-09-10 05:38:39'),(194,6,44,'Pallet','2026-09-10','fresh','Pallet:huwvi 22','2026-09-10 05:38:39'),(195,6,44,'Pallet','2026-09-10','fresh','Pallet:huwvi 23','2026-09-10 05:38:39'),(196,6,32,'977','2026-09-10','fresh','977','2026-09-10 05:38:57'),(197,6,1000,'977','2026-09-10','damaged','977','2026-09-10 05:38:57'),(198,2,1,'CANCEL','2026-09-10','fresh','Cancel return Order #28','2026-09-10 05:50:29'),(199,2,1,'CANCEL','2026-09-10','damaged','Cancel return Order #28','2026-09-10 05:50:29'),(200,6,500,'CANCEL','2026-09-10','fresh','Cancel return Order #29','2026-09-10 06:05:09'),(201,2,200,'CANCEL','2026-09-10','damaged','Cancel return Order #30','2026-09-10 06:11:01'),(202,2,200,'CANCEL','2026-09-10','fresh','Cancel return Order #31','2026-09-10 06:11:43'),(203,10,55,'Pallet','2026-09-10','fresh','Pallet:uu3 2','2026-09-10 10:40:30'),(204,10,55,'Pallet','2026-09-10','fresh','Pallet:uu3 3','2026-09-10 10:40:30'),(205,10,55,'Pallet','2026-09-10','fresh','Pallet:uu3 1','2026-09-10 10:40:30'),(206,10,55,'Pallet','2026-09-10','fresh','Pallet:uu3 4','2026-09-10 10:40:30'),(207,10,55,'Pallet','2026-09-10','fresh','Pallet:uu3 5','2026-09-10 10:40:30'),(208,10,55,'Pallet','2026-09-10','fresh','Pallet:uu3 6','2026-09-10 10:40:30'),(209,10,299,'2324','2026-09-10','fresh','2324','2026-09-10 10:40:37'),(210,10,44,'2324','2026-09-10','damaged','2324','2026-09-10 10:40:37');
/*!40000 ALTER TABLE `stock_in` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stock_out`
--

DROP TABLE IF EXISTS `stock_out`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stock_out` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `item_id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `qty` int(11) NOT NULL DEFAULT 0,
  `plates` int(11) NOT NULL DEFAULT 0,
  `pcs_per_plate` int(11) NOT NULL DEFAULT 0,
  `customer_name` varchar(255) DEFAULT NULL,
  `delivery_number` varchar(100) DEFAULT NULL,
  `dc_no` varchar(100) DEFAULT NULL,
  `vehicle_no` varchar(50) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `out_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `item_condition` varchar(20) NOT NULL DEFAULT 'FRESH',
  PRIMARY KEY (`id`),
  KEY `item_id` (`item_id`),
  CONSTRAINT `stock_out_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stock_out`
--

LOCK TABLES `stock_out` WRITE;
/*!40000 ALTER TABLE `stock_out` DISABLE KEYS */;
INSERT INTO `stock_out` VALUES (2,2,2,16,0,0,NULL,NULL,NULL,NULL,NULL,'2026-09-08','2026-09-08 12:06:04','FRESH'),(3,2,10,25,0,0,'XYZ','DN-099','','','','2026-09-08','2026-09-08 12:20:36','FRESH'),(10,2,17,100,0,0,'talal','33131','3388','00013','','2026-09-08','2026-09-08 12:42:36','FRESH'),(11,5,18,50,0,0,'hamza','23112','1313','3141','','2026-09-08','2026-09-08 12:45:35','FRESH'),(13,5,20,30,0,0,'StockTest','DN-T1','DC-T1','','','2026-09-09','2026-09-08 13:09:41','FRESH'),(18,1,25,10,0,0,'Ahmed Traders',NULL,NULL,NULL,'Plates: 0 (36 pcs/plate)','2026-09-09','2026-09-09 09:21:19','FRESH'),(19,1,26,200,5,36,'Ahmed Traders',NULL,NULL,NULL,'Plates: 5 (36 pcs/plate)','2026-09-09','2026-09-09 09:23:42','FRESH'),(20,5,27,200,5,36,'BugTest',NULL,NULL,NULL,'21212','2026-09-09','2026-09-09 10:34:40','0'),(21,2,28,2000,100,20,'FixTest',NULL,NULL,NULL,'Plates: 100 (20 pcs/plate)','2026-09-09','2026-09-09 13:50:25','FRESH'),(22,6,29,500,11,44,'CompleteTest',NULL,NULL,NULL,'Plates: 11 (44 pcs/plate)','2026-09-11','2026-09-10 05:41:31','DAMAGED'),(23,2,30,200,10,20,'Ali Brothers',NULL,NULL,NULL,'Plates: 10 (20 pcs/plate)','2026-09-10','2026-09-10 06:10:41','FRESH'),(24,2,31,200,10,20,'Ali Brothers',NULL,NULL,NULL,'Plates: 10 (20 pcs/plate)','2026-09-10','2026-09-10 06:11:28','DAMAGED');
/*!40000 ALTER TABLE `stock_out` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `reset_code` varchar(10) DEFAULT NULL,
  `reset_expiry` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Admin','admin@gmail.com','$2y$10$HogjNFfXqupIo27AxdeywuJRa8MmYA9Yn.IXRLgVnLQBVoBypRMrO',NULL,NULL,'2026-09-10 06:38:45'),(2,'talal','talal2504a@aptechsite.net','$2y$10$N7WS70aR9ZKsBD5wOoRKa.EFYk1f2tLATSpQCmTZoJGmjqGC4PBf6',NULL,NULL,'2026-09-10 07:52:40'),(3,'sarim','sarim@gmail.com','$2y$10$qror88mlyXTDkY.GPjBGMuq33fHk1iqx23r6oYnyPdrS1PrJeCiFq',NULL,NULL,'2026-09-10 08:09:50');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-09-10 16:32:38
