-- NC Garments ERP Database Backup
-- Generated on: 2026-04-16 17:42:48

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `activity_log`;
CREATE TABLE `activity_log` (
  `log_id` int(11) NOT NULL AUTO_INCREMENT,
  `admin_id` int(11) DEFAULT NULL,
  `action` varchar(20) NOT NULL,
  `target_table` varchar(50) NOT NULL,
  `target_id` int(11) DEFAULT NULL,
  `description` text NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`log_id`),
  KEY `admin_id` (`admin_id`),
  CONSTRAINT `1` FOREIGN KEY (`admin_id`) REFERENCES `admin` (`admin_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `admin`;
CREATE TABLE `admin` (
  `admin_id` int(11) NOT NULL AUTO_INCREMENT,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `username` varchar(30) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('admin','staff') DEFAULT 'staff',
  `status` enum('active','deactivated') DEFAULT 'active',
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_archived` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`admin_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO admin VALUES("1","Jezel Juanillo","example@gmail.com","Jezel1071","$2y$12$NY42Gx/LKvjON2fG3FJUwOElB2z9H4iu8zmHEkl0HnAJ93uU31aZm","admin","active","2026-04-16 17:31:44","2026-04-08 21:19:45","0");
INSERT INTO admin VALUES("2","Sherwin Samonte","kiriyaokazaki@gmail.com","kiriya56","$2y$12$RFxwnahkDGlGSNjNe7IPIu9tzWwgcX8Wm0TBdE8BISP9W.XHH.Jry","staff","deactivated",NULL,"2026-04-10 20:11:06","0");
INSERT INTO admin VALUES("3","Ana Lopez","ana.lopez@needleclass.ph","staff_ana","$2y$10$7.G3gVjR2D4G5jL8/5fD0eF2.7/1H1aA2B3c4D5e6F7g8H9i0J1K2","staff","active","2026-03-15 14:30:00","2026-04-11 11:22:12","0");
INSERT INTO admin VALUES("4","Carlos Reyes","carlos.reyes@needleclass.ph","staff_carlos","$2y$10$7.G3gVjR2D4G5jL8/5fD0eF2.7/1H1aA2B3c4D5e6F7g8H9i0J1K2","staff","active","2026-04-10 08:45:00","2026-04-11 11:22:12","0");
INSERT INTO admin VALUES("5","Diana Bautista","diana.b@needleclass.ph","admin_diana","$2y$10$7.G3gVjR2D4G5jL8/5fD0eF2.7/1H1aA2B3c4D5e6F7g8H9i0J1K2","admin","active","2026-04-11 09:15:00","2026-04-11 11:22:12","0");

DROP TABLE IF EXISTS `backup_log`;
CREATE TABLE `backup_log` (
  `log_id` int(11) NOT NULL AUTO_INCREMENT,
  `filename` varchar(255) NOT NULL,
  `file_size` varchar(50) NOT NULL,
  `action_type` enum('Export','Restore') NOT NULL,
  `admin_id` int(11) NOT NULL,
  `status` enum('Successful','Failed') DEFAULT 'Successful',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`log_id`),
  KEY `admin_id` (`admin_id`),
  CONSTRAINT `1` FOREIGN KEY (`admin_id`) REFERENCES `admin` (`admin_id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO backup_log VALUES("1","nc_garments_backup_20260416_092826.sql","17.41 KB","Export","1","Successful","2026-04-16 17:28:26");
INSERT INTO backup_log VALUES("2","nc_garments_backup_20260416_093111.sql","17.56 KB","Export","1","Successful","2026-04-16 17:31:11");
INSERT INTO backup_log VALUES("3","nc_garments_backup_20260416_093147.sql","17.70 KB","Export","1","Successful","2026-04-16 17:31:47");
INSERT INTO backup_log VALUES("4","nc_garments_backup_20260416_093254.sql","17.83 KB","Export","1","Successful","2026-04-16 17:32:54");
INSERT INTO backup_log VALUES("5","nc_garments_backup_20260416_173526.sql","17.96 KB","Export","1","Successful","2026-04-16 17:35:26");
INSERT INTO backup_log VALUES("6","nc_garments_backup_20260416_173555.sql","18.09 KB","Export","1","Successful","2026-04-16 17:35:55");
INSERT INTO backup_log VALUES("7","nc_garments_backup_20260416_173604.sql","18.23 KB","Export","1","Successful","2026-04-16 17:36:04");
INSERT INTO backup_log VALUES("8","nc_garments_backup_20260416_173856.sql","18.77 KB","Restore","1","Successful","2026-04-16 17:39:08");

DROP TABLE IF EXISTS `customer`;
CREATE TABLE `customer` (
  `customer_id` int(11) NOT NULL AUTO_INCREMENT,
  `full_name` varchar(75) NOT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `is_archived` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`customer_id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO customer VALUES("1","Hailynn Ganadin","09913312809","BLK 109 LOT 10B Bahay Pangarap Sampaloc IV Dasmarinas City, Cavite","2026-04-11 10:49:04","0");
INSERT INTO customer VALUES("2","Maria Santos","0917-555-0192","Dasmariñas High School, Dasmariñas City, Cavite","2026-04-11 11:08:50","0");
INSERT INTO customer VALUES("3","Hon. Juan Perez","0920-123-4567","LGU Municipal Hall, Dasmariñas City, Cavite","2026-04-11 11:08:50","0");
INSERT INTO customer VALUES("4","Elena Gomez","0998-765-4321","Salitran, Dasmariñas City, Cavite","2026-04-11 11:08:50","0");
INSERT INTO customer VALUES("5","St. Jude Academy","046-416-7890","Salawag, Dasmariñas City, Cavite","2026-04-11 11:08:50","0");
INSERT INTO customer VALUES("6","Walk-in Customer",NULL,NULL,"2026-04-11 11:08:50","0");
INSERT INTO customer VALUES("7","Roberto Villanueva","0919-876-5432","Imus City, Cavite","2026-04-11 11:08:50","0");

DROP TABLE IF EXISTS `payment`;
CREATE TABLE `payment` (
  `payment_id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `processed_by_admin` int(11) NOT NULL,
  `amount_paid` decimal(10,2) NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `payment_date` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`payment_id`),
  KEY `project_id` (`project_id`),
  CONSTRAINT `1` FOREIGN KEY (`project_id`) REFERENCES `project` (`project_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `premade_product`;
CREATE TABLE `premade_product` (
  `product_id` int(11) NOT NULL AUTO_INCREMENT,
  `sku` varchar(20) NOT NULL,
  `product_name` varchar(100) NOT NULL,
  `size` varchar(10) NOT NULL,
  `current_stock` int(11) NOT NULL,
  `min_stock_alert` int(11) NOT NULL,
  `selling_price` decimal(10,2) NOT NULL,
  `updated_at` timestamp NULL DEFAULT current_timestamp(),
  `is_archived` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`product_id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO premade_product VALUES("1","RTL-BLS-S","White Tetoron Blouse (Standard)","S","45","15","350.00","2026-04-11 11:14:31","0");
INSERT INTO premade_product VALUES("2","RTL-BLS-M","White Tetoron Blouse (Standard)","M","60","20","350.00","2026-04-11 11:14:31","0");
INSERT INTO premade_product VALUES("3","RTL-BLS-L","White Tetoron Blouse (Standard)","L","12","20","350.00","2026-04-11 11:14:31","0");
INSERT INTO premade_product VALUES("4","RTL-PE-DHS-M","DHS PE T-Shirt","M","150","50","250.00","2026-04-11 11:14:31","1");
INSERT INTO premade_product VALUES("5","RTL-PE-DHS-L","DHS PE T-Shirt","L","130","50","250.00","2026-04-11 11:14:31","0");
INSERT INTO premade_product VALUES("6","RTL-SLK-30","Navy Blue School Slacks","30","25","10","450.00","2026-04-11 11:14:31","0");
INSERT INTO premade_product VALUES("7","RTL-SLK-32","Navy Blue School Slacks","32","0","10","450.00","2026-04-11 11:14:31","0");
INSERT INTO premade_product VALUES("8","RTL-PBA-M","Generic Office Polo Barong","M","40","15","650.00","2026-04-11 11:14:31","0");
INSERT INTO premade_product VALUES("9","RTL-PBA-L","Generic Office Polo Barong","L","35","15","650.00","2026-04-11 11:14:31","0");
INSERT INTO premade_product VALUES("10","RTL-SKT-M","Pleated Checkered Skirt","M","28","15","380.00","2026-04-11 11:14:31","0");

DROP TABLE IF EXISTS `project`;
CREATE TABLE `project` (
  `project_id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) DEFAULT NULL,
  `produced_product_id` int(11) DEFAULT NULL,
  `created_by_admin` int(11) NOT NULL,
  `project_name` varchar(255) NOT NULL,
  `quantity` int(11) NOT NULL,
  `start_date` date DEFAULT curdate(),
  `due_date` date NOT NULL,
  `finish_date` date DEFAULT NULL,
  `agreed_price` decimal(10,2) NOT NULL,
  `status` enum('active','completed','cancelled') DEFAULT 'active',
  `progress` enum('not started','sampling','cutting','printing','sewing','quality check','finishing','packing','done','released','cancelled') DEFAULT 'not started',
  `overdue_notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `is_archived` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`project_id`),
  KEY `customer_id` (`customer_id`),
  KEY `produced_product_id` (`produced_product_id`),
  KEY `created_by_admin` (`created_by_admin`),
  CONSTRAINT `1` FOREIGN KEY (`customer_id`) REFERENCES `customer` (`customer_id`),
  CONSTRAINT `2` FOREIGN KEY (`produced_product_id`) REFERENCES `premade_product` (`product_id`),
  CONSTRAINT `3` FOREIGN KEY (`created_by_admin`) REFERENCES `admin` (`admin_id`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO project VALUES("1","2",NULL,"1","DHS PE Uniforms","45","2026-04-05","2026-10-25",NULL,"15500.00","active","not started",NULL,"2026-04-11 11:16:54","0");
INSERT INTO project VALUES("2","4",NULL,"1","Custom Blazer","1",NULL,"2026-04-09",NULL,"3500.00","active","not started",NULL,"2026-04-11 11:16:54","0");
INSERT INTO project VALUES("3","3",NULL,"2","LGU Polo Shirts","120",NULL,"2026-05-07",NULL,"42000.00","active","not started",NULL,"2026-04-11 11:16:54","0");
INSERT INTO project VALUES("4",NULL,"1","1","Internal Restock: White Blouse (S)","50",NULL,"2026-04-05",NULL,"0.00","active","not started",NULL,"2026-04-11 11:16:54","0");
INSERT INTO project VALUES("5","5",NULL,"2","St. Jude Faculty Uniforms","30",NULL,"2026-06-15",NULL,"25000.00","active","not started",NULL,"2026-04-11 11:16:54","0");
INSERT INTO project VALUES("6","4",NULL,"1","Testing Project","25","2026-04-11","2026-04-19",NULL,"1000.00","active","not started",NULL,"2026-04-11 12:52:40","1");
INSERT INTO project VALUES("7",NULL,"9","1","Testing 2","1","2026-04-12","2026-04-13",NULL,"0.00","active","not started",NULL,"2026-04-12 23:27:17","1");
INSERT INTO project VALUES("8","4",NULL,"1","Testing 3","1","2026-04-12","2026-04-14",NULL,"0.00","active","not started",NULL,"2026-04-12 23:27:50","1");
INSERT INTO project VALUES("9","1",NULL,"1","Testing product","53",NULL,"2026-04-22",NULL,"2000.00","active","not started",NULL,"2026-04-15 12:11:54","0");
INSERT INTO project VALUES("10",NULL,"5","1","Testing 2","32","2026-04-15","2026-04-16",NULL,"0.00","active","not started",NULL,"2026-04-15 21:01:29","0");
INSERT INTO project VALUES("11",NULL,"6","1","Testing Internal Stock","40","2026-04-15","2026-04-30",NULL,"18000.00","active","not started",NULL,"2026-04-15 21:32:01","0");
INSERT INTO project VALUES("12",NULL,"6","1","Testing Internal Stock","40","2026-04-15","2026-04-30",NULL,"18000.00","active","not started",NULL,"2026-04-15 21:32:08","0");
INSERT INTO project VALUES("13",NULL,"6","1","Testing Internal Stock","40","2026-04-15","2026-04-30",NULL,"18000.00","active","not started",NULL,"2026-04-15 21:32:25","0");
INSERT INTO project VALUES("14",NULL,"8","1","Testing 5","23","2026-04-15","2026-04-30",NULL,"14950.00","active","not started",NULL,"2026-04-15 21:32:45","0");
INSERT INTO project VALUES("15",NULL,"5","1","dsadas","12","2026-04-15","2026-04-27",NULL,"3000.00","active","not started",NULL,"2026-04-15 21:33:43","0");
INSERT INTO project VALUES("16","2",NULL,"1","dsadas","12","2026-04-15","2026-04-27",NULL,"0.00","active","not started",NULL,"2026-04-15 21:33:48","0");
INSERT INTO project VALUES("17","7",NULL,"1","dsadsa","42","2026-04-15","2026-04-19",NULL,"0.00","active","not started",NULL,"2026-04-15 21:39:11","0");
INSERT INTO project VALUES("18","7",NULL,"1","qtgdf","1","2026-04-15","2026-04-19",NULL,"0.00","active","not started",NULL,"2026-04-15 21:39:45","0");
INSERT INTO project VALUES("19",NULL,"7","1","afd","23","2026-04-15","2026-04-28",NULL,"0.00","active","not started",NULL,"2026-04-15 21:41:22","0");
INSERT INTO project VALUES("20",NULL,"6","1","Navy Blue School Slacks","22","2026-04-15","2026-04-21",NULL,"9900.00","active","not started",NULL,"2026-04-15 21:42:23","0");
INSERT INTO project VALUES("21",NULL,"6","1","Navy Blue School Slacks","22","2026-04-15","2026-04-21",NULL,"9900.00","active","not started",NULL,"2026-04-15 21:42:41","0");
INSERT INTO project VALUES("22","3",NULL,"1","Order 1","23","2026-04-15","2026-04-19",NULL,"200.00","active","not started",NULL,"2026-04-15 21:48:46","0");
INSERT INTO project VALUES("23",NULL,"4","1","Order 2","9","2026-04-15","2026-04-28",NULL,"2250.00","active","not started",NULL,"2026-04-15 21:49:13","0");
INSERT INTO project VALUES("24",NULL,"6","1","afdfdf","1","2026-04-15","2026-04-21",NULL,"450.00","active","not started",NULL,"2026-04-15 22:05:55","0");

DROP TABLE IF EXISTS `project_breakdown`;
CREATE TABLE `project_breakdown` (
  `breakdown_id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `material_id` int(11) NOT NULL,
  `quantity_used` int(11) NOT NULL,
  `unit_cost` decimal(10,2) NOT NULL,
  `total_cost` decimal(10,2) NOT NULL,
  PRIMARY KEY (`breakdown_id`),
  KEY `project_id` (`project_id`),
  KEY `material_id` (`material_id`),
  CONSTRAINT `1` FOREIGN KEY (`project_id`) REFERENCES `project` (`project_id`),
  CONSTRAINT `2` FOREIGN KEY (`material_id`) REFERENCES `raw_material` (`material_id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO project_breakdown VALUES("2","6","11","1","280.00","280.00");
INSERT INTO project_breakdown VALUES("3","6","14","3","120.00","360.00");
INSERT INTO project_breakdown VALUES("4","8","19","1","45.00","45.00");
INSERT INTO project_breakdown VALUES("5","8","7","1","65.00","65.00");
INSERT INTO project_breakdown VALUES("6","9","15","1","20.00","20.00");
INSERT INTO project_breakdown VALUES("7","9","18","5","180.00","900.00");
INSERT INTO project_breakdown VALUES("8","9","9","8","95.00","760.00");
INSERT INTO project_breakdown VALUES("9","10","18","1","180.00","180.00");
INSERT INTO project_breakdown VALUES("10","19","9","1","95.00","95.00");
INSERT INTO project_breakdown VALUES("11","22","18","1","180.00","180.00");

DROP TABLE IF EXISTS `project_measurement`;
CREATE TABLE `project_measurement` (
  `measurement_id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `body_part` varchar(50) NOT NULL,
  `measurement_value` decimal(5,2) NOT NULL,
  `unit` varchar(10) DEFAULT 'inches',
  PRIMARY KEY (`measurement_id`),
  KEY `project_id` (`project_id`),
  CONSTRAINT `1` FOREIGN KEY (`project_id`) REFERENCES `project` (`project_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `project_premade_sale`;
CREATE TABLE `project_premade_sale` (
  `sale_id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  PRIMARY KEY (`sale_id`),
  KEY `project_id` (`project_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `1` FOREIGN KEY (`project_id`) REFERENCES `project` (`project_id`),
  CONSTRAINT `2` FOREIGN KEY (`product_id`) REFERENCES `premade_product` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `project_sizing`;
CREATE TABLE `project_sizing` (
  `sizing_id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `size_label` varchar(20) NOT NULL,
  `quantity` int(11) NOT NULL,
  PRIMARY KEY (`sizing_id`),
  KEY `project_id` (`project_id`),
  CONSTRAINT `1` FOREIGN KEY (`project_id`) REFERENCES `project` (`project_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO project_sizing VALUES("1","22","Medium","23");

DROP TABLE IF EXISTS `raw_material`;
CREATE TABLE `raw_material` (
  `material_id` int(11) NOT NULL AUTO_INCREMENT,
  `sku` varchar(20) NOT NULL,
  `material_name` varchar(100) NOT NULL,
  `unit_of_measure` varchar(20) NOT NULL,
  `current_stock` int(11) NOT NULL,
  `min_stock_alert` int(11) NOT NULL,
  `current_price` decimal(10,2) NOT NULL,
  `last_price` decimal(10,2) NOT NULL,
  `updated_at` timestamp NULL DEFAULT current_timestamp(),
  `is_archived` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`material_id`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO raw_material VALUES("1","RAW-FAB-001","White Tetoron Fabric","Yards","150","20","85.00","80.00","2026-04-11 11:18:49","0");
INSERT INTO raw_material VALUES("2","RAW-THR-042","Signature Pink Thread","Cones","3","5","45.00","45.00","2026-04-11 11:18:49","0");
INSERT INTO raw_material VALUES("3","RAW-ACC-012","15mm Black Buttons","Pcs","850","200","2.50","2.25","2026-04-11 11:18:49","0");
INSERT INTO raw_material VALUES("4","RAW-FAB-002","Navy Blue Twill Fabric","Yards","80","30","120.00","115.00","2026-04-11 11:18:49","0");
INSERT INTO raw_material VALUES("5","RAW-ZIP-001","YKK 7-inch Nylon Zipper","Pcs","300","100","8.00","7.50","2026-04-11 11:18:49","0");
INSERT INTO raw_material VALUES("6","RAW-ACC-005","1-inch Woven Elastic Garter","Rolls","12","5","150.00","150.00","2026-04-11 11:18:49","0");
INSERT INTO raw_material VALUES("7","RAW-FAB-005","Fusible Interfacing (Pellon)","Yards","45","15","65.00","60.00","2026-04-11 11:18:49","0");
INSERT INTO raw_material VALUES("8","RAW-FAB-003","Katrina Fabric - Black (Slacks/Skirts)","Yards","200","50","65.00","65.00","2026-04-11 11:19:33","0");
INSERT INTO raw_material VALUES("9","RAW-FAB-006","Checkered Poly-Cotton (Green/White)","Yards","120","30","95.00","90.00","2026-04-11 11:19:33","0");
INSERT INTO raw_material VALUES("10","RAW-FAB-004","Lacoste Cotton - White (Polo Shirts)","Kilos","45","10","320.00","300.00","2026-04-11 11:19:33","0");
INSERT INTO raw_material VALUES("11","RAW-FAB-007","Cotton Spandex - Black","Kilos","30","10","280.00","280.00","2026-04-11 11:19:33","0");
INSERT INTO raw_material VALUES("12","RAW-THR-001","White Polyester Thread","Cones","25","10","40.00","40.00","2026-04-11 11:19:33","0");
INSERT INTO raw_material VALUES("13","RAW-THR-002","Navy Blue Polyester Thread","Cones","18","10","40.00","40.00","2026-04-11 11:19:33","0");
INSERT INTO raw_material VALUES("14","RAW-ACC-015","Hook and Eye Fasteners (Metal)","Boxes","8","3","120.00","110.00","2026-04-11 11:19:33","0");
INSERT INTO raw_material VALUES("15","RAW-ACC-020","Blazer Shoulder Pads (Standard)","Pairs","50","20","15.00","15.00","2026-04-11 11:19:33","0");
INSERT INTO raw_material VALUES("16","RAW-LBL-001","Woven Size Labels (Assorted)","Packs","15","5","250.00","250.00","2026-04-11 11:19:33","0");
INSERT INTO raw_material VALUES("17","RAW-LBL-002","NC Garments Brand Tags","Pcs","1500","500","3.50","3.00","2026-04-11 11:19:33","0");
INSERT INTO raw_material VALUES("18","RAW-PKG-001","Clear Polybags (10x14 for Shirts)","Packs","30","10","180.00","175.00","2026-04-11 11:19:33","0");
INSERT INTO raw_material VALUES("19","RAW-PKG-002","Garment Bags (For Blazers)","Pcs","40","15","45.00","42.00","2026-04-11 11:19:33","0");

SET FOREIGN_KEY_CHECKS = 1;
