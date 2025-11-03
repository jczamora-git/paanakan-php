-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 09, 2025 at 11:08 PM
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
-- Database: `paanakandb`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_log`
--

CREATE TABLE `activity_log` (
  `log_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action` text NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_log`
--

INSERT INTO `activity_log` (`log_id`, `user_id`, `action`, `timestamp`) VALUES
(197, 1, 'Transaction for Case ID: C006 – Transvaginal Ultrasound, ₱300.00, Payment: Pending', '2025-05-09 19:04:46'),
(198, 1, 'Transaction for Case ID: C006 – Transvaginal Ultrasound, ₱300.00, Payment: Pending', '2025-05-09 19:04:51'),
(199, 1, 'Scheduled appointment for Case ID: C006 – Pre-Natal Checkup on May 12, 2025', '2025-05-09 19:09:48'),
(200, 1, 'Scheduled appointment for Case ID: C006 – Post-Natal Checkup on May 12, 2025', '2025-05-09 19:10:08'),
(201, 1, 'Transaction for Case ID: C006 – OB Ultrasound, ₱250.00, Payment: Pending', '2025-05-09 19:12:57'),
(202, 1, 'Transaction for Case ID: C006 – Prenatal Checkup, ₱100.00, Payment: Pending', '2025-05-09 19:17:08'),
(203, 1, 'Transaction for Case ID: C006 – Prenatal Checkup, ₱100.00, Payment: Pending', '2025-05-09 19:17:12'),
(204, 1, 'Transaction for Case ID: C006 – Postnatal Checkup, ₱120.00, Payment: Pending', '2025-05-09 19:25:28'),
(205, 1, 'Transaction for Case ID: C006 – Pap Smear, ₱150.00, Payment: Pending', '2025-05-09 19:32:51'),
(206, 1, 'Transaction for Case ID: C006 – Urinalysis, ₱50.00, Payment: Pending', '2025-05-09 19:32:57'),
(207, 1, 'Transaction for Case ID: C006 – Hemoglobin Test, ₱70.00, Payment: Pending', '2025-05-09 19:33:01'),
(208, 1, 'Transaction for Case ID: C006 – Circumcision, ₱200.00, Payment: Pending', '2025-05-09 19:40:52'),
(209, 1, 'Transaction for Case ID: C006 – Vaccination for Newborn, ₱80.00, Payment: Pending', '2025-05-09 19:41:35'),
(210, 1, 'Scheduled appointment for Case ID: C006 – Vaccination on May 12, 2025', '2025-05-09 19:50:35'),
(211, 1, 'Scheduled appointment for Case ID: C006 – Vaccination on May 12, 2025', '2025-05-09 19:55:05'),
(212, 1, 'Transaction for Case ID: C006 – Vaccination for Newborn, ₱80.00, Payment: Pending', '2025-05-09 19:57:38'),
(213, 1, 'Transaction for Case ID: C006 – Vaccination for Newborn, ₱80.00, Payment: Pending', '2025-05-09 20:02:45'),
(214, 1, 'Transaction for Case ID: C006 – Vaccination for Newborn, ₱80.00, Payment: Pending', '2025-05-09 20:03:39'),
(215, 1, 'Scheduled appointment for Case ID: C006 – Medical Consultation on May 12, 2025', '2025-05-09 20:16:59'),
(216, 1, 'Added billing record for John Paul Baes - Prenatal Checkup with net amount of ₱238.00', '2025-05-09 20:43:54'),
(217, 1, 'Scheduled appointment for Case ID: C006 – Follow-up on May 12, 2025', '2025-05-09 20:58:37');

-- --------------------------------------------------------

--
-- Table structure for table `admissions`
--

CREATE TABLE `admissions` (
  `admission_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `admission_date` datetime NOT NULL,
  `discharge_date` datetime DEFAULT NULL,
  `admitting_physician` varchar(100) DEFAULT NULL,
  `admitting_diagnosis` text DEFAULT NULL,
  `discharge_diagnosis` text DEFAULT NULL,
  `discharge_condition` enum('Recovered','Improved','Unimproved','Died') DEFAULT NULL,
  `disposition` enum('Discharged','Transferred','Home Against Medical Advice','Absconded') DEFAULT NULL,
  `complications` text DEFAULT NULL,
  `surgical_procedure` text DEFAULT NULL,
  `pathological_report` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

CREATE TABLE `appointments` (
  `appointment_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `appointment_type` varchar(50) NOT NULL DEFAULT '''General''',
  `scheduled_date` datetime NOT NULL,
  `completed_date` datetime DEFAULT NULL,
  `status` enum('Done','Missed','Approved','Pending') DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `appointments`
--

INSERT INTO `appointments` (`appointment_id`, `patient_id`, `appointment_type`, `scheduled_date`, `completed_date`, `status`, `created_at`) VALUES
(216, 7, 'Pre-Natal Checkup', '2025-05-12 07:00:00', NULL, 'Done', '2025-05-09 19:09:48'),
(217, 7, 'Post-Natal Checkup', '2025-05-12 08:00:00', NULL, 'Done', '2025-05-09 19:10:08'),
(218, 7, 'Vaccination', '2025-05-12 09:00:00', NULL, 'Done', '2025-05-09 19:50:35'),
(219, 7, 'Vaccination', '2025-05-12 10:00:00', NULL, 'Done', '2025-05-09 19:55:05'),
(220, 7, 'Medical Consultation', '2025-05-12 11:00:00', NULL, 'Done', '2025-05-09 20:16:59'),
(221, 7, 'Follow-up', '2025-05-12 12:00:00', NULL, 'Done', '2025-05-09 20:58:37');

-- --------------------------------------------------------

--
-- Table structure for table `billing_header`
--

CREATE TABLE `billing_header` (
  `billing_id` int(11) NOT NULL,
  `case_id` varchar(20) NOT NULL,
  `transaction_id` int(11) NOT NULL,
  `service_amount` decimal(10,2) NOT NULL,
  `total_items` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_professional_fees` decimal(10,2) NOT NULL DEFAULT 0.00,
  `billing_date` datetime NOT NULL DEFAULT current_timestamp(),
  `total_discounts` decimal(10,2) NOT NULL DEFAULT 0.00,
  `net_amount` decimal(10,2) GENERATED ALWAYS AS (`service_amount` + `total_professional_fees` + `total_items` - `total_discounts`) STORED
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `billing_header`
--

INSERT INTO `billing_header` (`billing_id`, `case_id`, `transaction_id`, `service_amount`, `total_items`, `total_professional_fees`, `billing_date`, `total_discounts`) VALUES
(28, 'C006', 66, 100.00, 38.00, 100.00, '2025-05-09 22:43:54', 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `billing_items`
--

CREATE TABLE `billing_items` (
  `billing_item_id` int(11) NOT NULL,
  `billing_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `item_price` decimal(10,2) NOT NULL,
  `item_amount` decimal(10,2) GENERATED ALWAYS AS (`quantity` * `item_price`) STORED
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `billing_items`
--

INSERT INTO `billing_items` (`billing_item_id`, `billing_id`, `item_id`, `quantity`, `item_price`) VALUES
(56, 28, 11, 1, 5.00),
(57, 28, 17, 1, 25.00),
(58, 28, 13, 1, 8.00);

-- --------------------------------------------------------

--
-- Table structure for table `circumcision_consent`
--

CREATE TABLE `circumcision_consent` (
  `id` int(11) NOT NULL,
  `case_id` varchar(255) NOT NULL,
  `transaction_id` int(11) NOT NULL,
  `child_name` varchar(255) NOT NULL,
  `child_age` int(11) NOT NULL,
  `child_birthdate` date NOT NULL,
  `parent_name` varchar(255) NOT NULL,
  `parent_relationship` varchar(50) NOT NULL,
  `parent_contact` varchar(50) DEFAULT NULL,
  `parent_address` text DEFAULT NULL,
  `consent_date` date NOT NULL,
  `witness_name` varchar(255) DEFAULT NULL,
  `doctor_name` varchar(255) NOT NULL,
  `scheduled_date` date DEFAULT NULL,
  `medical_conditions` text DEFAULT NULL,
  `allergies` text DEFAULT NULL,
  `medications` text DEFAULT NULL,
  `acknowledge_procedure` tinyint(1) DEFAULT 0,
  `acknowledge_risks` tinyint(1) DEFAULT 0,
  `acknowledge_aftercare` tinyint(1) DEFAULT 0,
  `acknowledge_questions` tinyint(1) DEFAULT 0,
  `special_instructions` text DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `parent_signature` text DEFAULT NULL,
  `doctor_signature` text DEFAULT NULL,
  `witness_signature` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `circumcision_consent`
--

INSERT INTO `circumcision_consent` (`id`, `case_id`, `transaction_id`, `child_name`, `child_age`, `child_birthdate`, `parent_name`, `parent_relationship`, `parent_contact`, `parent_address`, `consent_date`, `witness_name`, `doctor_name`, `scheduled_date`, `medical_conditions`, `allergies`, `medications`, `acknowledge_procedure`, `acknowledge_risks`, `acknowledge_aftercare`, `acknowledge_questions`, `special_instructions`, `remarks`, `parent_signature`, `doctor_signature`, `witness_signature`, `created_at`, `updated_at`) VALUES
(2, 'C006', 71, 'John Paul Baes', 23, '2001-08-10', 'asdasdad', 'Mother', 'qweqwe', 'qweqwe', '2025-05-09', 'qwe', 'Dr. Idol Bondoc', '2025-05-10', 'qwe', 'qwe', 'qwe', 1, 1, 1, 1, 'qwewqe', NULL, 'qwe', 'qwe', 'qwe', '2025-05-09 19:41:20', '2025-05-09 19:41:20');

-- --------------------------------------------------------

--
-- Table structure for table `follow_up_records`
--

CREATE TABLE `follow_up_records` (
  `record_id` int(11) NOT NULL,
  `appointment_id` int(11) NOT NULL,
  `previous_diagnosis` text DEFAULT NULL,
  `progress_notes` text DEFAULT NULL,
  `current_status` text DEFAULT NULL,
  `recommendations` text DEFAULT NULL,
  `next_followup_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `follow_up_records`
--

INSERT INTO `follow_up_records` (`record_id`, `appointment_id`, `previous_diagnosis`, `progress_notes`, `current_status`, `recommendations`, `next_followup_date`, `created_at`) VALUES
(1, 221, 'test muna ', 'asdasds', 'asdasd', 'asdasd', '2025-05-12', '2025-05-09 21:05:39');

-- --------------------------------------------------------

--
-- Table structure for table `health_records`
--

CREATE TABLE `health_records` (
  `record_id` int(11) NOT NULL,
  `case_id` varchar(20) NOT NULL,
  `admission_id` int(11) DEFAULT NULL,
  `prenatal_record_id` int(11) DEFAULT NULL,
  `transaction_id` int(11) DEFAULT NULL,
  `appointment_id` int(11) DEFAULT NULL,
  `service_id` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `hemoglobin`
--

CREATE TABLE `hemoglobin` (
  `id` int(11) NOT NULL,
  `case_id` varchar(255) NOT NULL,
  `transaction_id` int(11) NOT NULL,
  `hemoglobin` decimal(5,2) DEFAULT NULL,
  `hematocrit` decimal(5,2) DEFAULT NULL,
  `rbc` decimal(5,2) DEFAULT NULL,
  `wbc` decimal(5,2) DEFAULT NULL,
  `neutrophils` decimal(5,2) DEFAULT NULL,
  `lymphocytes` decimal(5,2) DEFAULT NULL,
  `monocytes` decimal(5,2) DEFAULT NULL,
  `eosinophils` decimal(5,2) DEFAULT NULL,
  `basophils` decimal(5,2) DEFAULT NULL,
  `platelet_count` decimal(5,2) DEFAULT NULL,
  `others` text DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `medical_technologist` varchar(255) DEFAULT NULL,
  `pathologist` varchar(255) DEFAULT NULL,
  `report_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `hemoglobin`
--

INSERT INTO `hemoglobin` (`id`, `case_id`, `transaction_id`, `hemoglobin`, `hematocrit`, `rbc`, `wbc`, `neutrophils`, `lymphocytes`, `monocytes`, `eosinophils`, `basophils`, `platelet_count`, `others`, `remarks`, `medical_technologist`, `pathologist`, `report_date`, `created_at`, `updated_at`) VALUES
(2, 'C006', 70, 123.00, 123.00, 123.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-05-09', '2025-05-09 19:33:25', '2025-05-09 19:40:16');

-- --------------------------------------------------------

--
-- Table structure for table `inventory`
--

CREATE TABLE `inventory` (
  `item_id` int(10) NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `category` varchar(50) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit` varchar(50) NOT NULL,
  `price` decimal(10,2) DEFAULT 0.00,
  `supplier` varchar(255) DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `expiry_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory`
--

INSERT INTO `inventory` (`item_id`, `item_name`, `category`, `quantity`, `unit`, `price`, `supplier`, `status`, `expiry_date`) VALUES
(1, 'Stethoscope', 'Medical Equipment', 105, 'Pieces', 350.00, 'MedTech Inc.', 'In Stock', NULL),
(2, 'Blood Pressure Monitor', 'Medical Equipment', 32, 'Units', 1500.00, 'HealthPro', 'Medium Stock', NULL),
(3, 'Nebulizer', 'Medical Equipment', 22, 'Units', 2000.00, 'HealthPro', 'Medium Stock', NULL),
(4, 'Oxygen Tank', 'Medical Equipment', 10, 'Units', 5500.00, 'Oxygen Supplies', 'Low Stock', NULL),
(5, 'Infusion Pump', 'Medical Equipment', 68, 'Units', 25000.00, 'MedTech Inc.', 'In Stock', NULL),
(6, 'Paracetamol 500mg', 'Medications', 505, 'Tablets', 2.00, 'PharmaCare', 'In Stock', '2026-05-10'),
(7, 'Amoxicillin 500mg', 'Medications', 300, 'Capsules', 7.00, 'MediPharma', 'In Stock', '2025-11-01'),
(8, 'Salbutamol Inhaler', 'Medications', 150, 'Inhalers', 400.00, 'PharmaCare', 'In Stock', '2026-03-15'),
(9, 'Ibuprofen 400mg', 'Medications', 400, 'Tablets', 3.00, 'PharmaCare', 'In Stock', '2026-04-25'),
(10, 'Vitamin C 1000mg', 'Medications', 600, 'Tablets', 1.50, 'MedSupply', 'In Stock', '2026-08-30'),
(11, 'Cetirizine 10mg', 'Medications', 250, 'Tablets', 5.00, 'MediPharma', 'In Stock', '2026-07-12'),
(12, 'Metformin 500mg', 'Medications', 350, 'Tablets', 6.50, 'PharmaCare', 'In Stock', '2026-06-20'),
(13, 'Loratadine 10mg', 'Medications', 275, 'Tablets', 8.00, 'MedSupply', 'In Stock', '2026-09-15'),
(14, 'Omeprazole 20mg', 'Medications', 400, 'Capsules', 10.00, 'HealthPro', 'In Stock', '2026-05-18'),
(15, 'Aspirin 81mg', 'Medications', 500, 'Tablets', 3.00, 'MedTech Inc.', 'In Stock', '2026-10-05'),
(16, 'Surgical Gloves', 'Medical Supply', 1000, 'Pairs', 5.00, 'MedSupply', 'In Stock', NULL),
(17, 'Face Masks (N95)', 'Medical Equipment', 100, 'Pieces', 25.00, 'HealthPro', 'In Stock', NULL),
(18, 'Sterile Gauze Pads', 'Medical Supply', 750, 'Packs', 15.00, 'PharmaCare', 'In Stock', NULL),
(19, 'Disposable Syringes 5ml', 'Medical Supply', 600, 'Pieces', 7.00, 'MediPharma', 'In Stock', NULL),
(20, 'Alcohol 70% Solution 500ml', 'Medical Supply', 400, 'Bottles', 90.00, 'MedTech Inc.', 'In Stock', NULL);

--
-- Triggers `inventory`
--
DELIMITER $$
CREATE TRIGGER `set_status_on_insert` BEFORE INSERT ON `inventory` FOR EACH ROW BEGIN
    IF NEW.quantity <= 0 THEN
        SET NEW.status = 'No Stock';
    ELSEIF NEW.quantity <= 10 THEN
        SET NEW.status = 'Low Stock';
    ELSEIF NEW.quantity <= 50 THEN
        SET NEW.status = 'Medium Stock';
    ELSE
        SET NEW.status = 'In Stock';
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `update_status_based_on_quantity` BEFORE UPDATE ON `inventory` FOR EACH ROW BEGIN
    IF NEW.quantity <= 0 THEN
        SET NEW.status = 'No Stock';
    ELSEIF NEW.quantity <= 10 THEN
        SET NEW.status = 'Low Stock';
    ELSEIF NEW.quantity <= 50 THEN
        SET NEW.status = 'Medium Stock';
    ELSE
        SET NEW.status = 'In Stock';
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `logs`
--

CREATE TABLE `logs` (
  `log_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` text NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `logs`
--

INSERT INTO `logs` (`log_id`, `user_id`, `action`, `timestamp`) VALUES
(1, NULL, 'Login Successful', '2025-01-23 06:14:39'),
(2, 1, 'Login Successful', '2025-01-23 06:17:35'),
(3, 1, 'Login Failed: Invalid Role', '2025-01-23 06:17:35'),
(4, 1, 'Login Successful', '2025-01-23 06:19:06'),
(5, 1, 'Login Failed: Invalid Role', '2025-01-23 06:19:06'),
(6, 1, 'Login Successful', '2025-01-23 06:20:25'),
(7, 1, 'Login Successful', '2025-01-23 06:21:40'),
(8, 1, 'User Logged Out', '2025-01-23 06:21:42'),
(9, 1, 'Login Successful', '2025-01-23 06:21:59'),
(10, 1, 'Admin Logged Out', '2025-01-23 06:23:04'),
(11, 1, 'Login Successful', '2025-01-23 06:23:21'),
(12, 1, 'Admin Logged Out', '2025-01-23 06:44:02'),
(13, 1, 'Login Successful', '2025-01-23 06:44:04'),
(14, 1, 'Admin Logged Out', '2025-01-23 06:48:45'),
(15, 1, 'Login Successful', '2025-01-23 06:48:48'),
(16, 1, 'Admin Logged Out', '2025-01-23 06:50:12'),
(17, 1, 'Login Successful', '2025-01-23 06:50:18'),
(18, 1, 'Admin Logged Out', '2025-01-23 06:50:47'),
(19, 1, 'Login Successful', '2025-01-23 06:51:17'),
(20, 1, 'Login Successful', '2025-01-23 06:52:19'),
(21, 1, 'Login Successful', '2025-01-23 06:55:24'),
(22, 1, 'Login Successful', '2025-01-23 06:57:09'),
(23, 1, 'Admin Logged Out', '2025-01-23 07:21:05'),
(24, 1, 'Login Successful', '2025-01-23 07:21:07'),
(25, 1, 'Admin Logged Out', '2025-01-23 07:24:58'),
(26, 1, 'Login Successful', '2025-01-23 07:25:02'),
(27, 1, 'Admin Logged Out', '2025-01-23 07:25:39'),
(28, 1, 'Login Successful', '2025-01-23 07:26:38'),
(29, 1, 'Login Successful', '2025-01-23 07:44:33'),
(30, 1, 'Admin Logged Out', '2025-01-23 07:49:00'),
(31, 5, 'Login Successful', '2025-01-23 07:49:06'),
(32, 5, 'Admin Logged Out', '2025-01-23 07:49:11'),
(33, 5, 'Login Successful', '2025-01-23 07:49:19'),
(34, 5, 'Admin Logged Out', '2025-01-23 07:51:21'),
(35, 1, 'Login Successful', '2025-01-23 07:51:24'),
(36, 1, 'Admin Logged Out', '2025-01-23 07:52:24'),
(37, 5, 'Login Successful', '2025-01-23 07:52:30'),
(38, 1, 'Login Successful', '2025-01-23 08:10:03'),
(39, 1, 'Admin Logged Out', '2025-01-23 11:08:43'),
(40, 5, 'Login Successful', '2025-01-23 11:08:48'),
(41, 5, 'Login Successful', '2025-01-23 11:09:54'),
(42, 5, 'Login Successful', '2025-01-23 11:10:34'),
(43, 5, 'User Logged Out', '2025-01-23 11:12:49'),
(44, 1, 'Login Successful', '2025-01-23 11:12:51'),
(45, 1, 'Admin Logged Out', '2025-01-23 11:33:38'),
(46, 1, 'Login Successful', '2025-01-23 11:33:41'),
(47, 1, 'Login Successful', '2025-01-24 01:22:03'),
(48, 1, 'Admin Logged Out', '2025-01-24 02:19:43'),
(49, 1, 'Login Successful', '2025-01-24 02:19:45'),
(50, 1, 'Admin Logged Out', '2025-01-24 02:43:16'),
(51, 1, 'Login Successful', '2025-01-24 02:43:36'),
(52, 1, 'Admin Logged Out', '2025-01-24 02:46:59'),
(53, 1, 'Login Successful', '2025-01-24 02:47:01'),
(54, 1, 'Admin Logged Out', '2025-01-24 02:48:22'),
(55, 1, 'Login Successful', '2025-01-24 02:48:24'),
(56, 1, 'Admin Logged Out', '2025-01-24 02:48:37'),
(57, 1, 'Login Successful', '2025-01-24 02:48:40'),
(58, 1, 'Admin Logged Out', '2025-01-24 03:50:09'),
(59, 1, 'Login Failed: Invalid Password', '2025-01-24 03:51:25'),
(60, 1, 'Login Successful', '2025-01-24 03:51:28'),
(61, 1, 'Admin Logged Out', '2025-01-24 03:51:35'),
(62, 1, 'Login Successful', '2025-01-24 03:51:40'),
(63, 1, 'Admin Logged Out', '2025-01-24 03:51:45'),
(64, 1, 'Login Successful', '2025-01-24 03:51:57'),
(65, 1, 'Admin Logged Out', '2025-01-24 04:12:46'),
(66, 5, 'Login Successful', '2025-01-24 04:12:50'),
(67, 5, 'User Logged Out', '2025-01-24 04:13:06'),
(68, 1, 'Login Successful', '2025-01-24 04:13:16'),
(69, 1, 'Admin Logged Out', '2025-01-24 05:16:08'),
(70, 1, 'Login Failed: Invalid Password', '2025-01-24 05:16:30'),
(71, 1, 'Login Successful', '2025-01-24 05:16:32'),
(72, 1, 'Admin Logged Out', '2025-01-24 05:17:05'),
(73, 1, 'Login Successful', '2025-01-24 05:17:17'),
(74, 1, 'Admin Logged Out', '2025-01-24 05:18:02'),
(75, 1, 'Login Successful', '2025-01-24 05:18:20'),
(76, 1, 'Admin Logged Out', '2025-01-24 05:20:24'),
(77, 1, 'Login Successful', '2025-01-24 05:20:32'),
(78, 1, 'Admin Logged Out', '2025-01-24 05:20:48'),
(79, 1, 'Login Successful', '2025-01-24 05:21:07'),
(80, 1, 'Admin Logged Out', '2025-01-24 05:22:01'),
(81, 1, 'Login Successful', '2025-01-24 05:22:46'),
(82, 1, 'Login Successful', '2025-01-24 10:39:23'),
(83, 1, 'Admin Logged Out', '2025-01-24 11:17:24'),
(84, 1, 'Login Successful', '2025-01-24 11:17:29'),
(85, 1, 'Admin Logged Out', '2025-01-24 11:17:44'),
(86, 1, 'Login Successful', '2025-01-24 11:17:48'),
(87, 1, 'Login Successful', '2025-01-24 13:28:35'),
(88, 1, 'Login Failed: Invalid Password', '2025-01-24 14:48:53'),
(89, 1, 'Login Successful', '2025-01-24 14:48:57'),
(90, 1, 'Admin Logged Out', '2025-01-24 16:20:37'),
(91, 1, 'Login Successful', '2025-01-24 16:20:40'),
(92, 1, 'Login Successful', '2025-01-25 01:09:33'),
(93, 1, 'Login Successful', '2025-01-27 04:43:55'),
(94, 1, 'Login Successful', '2025-01-30 07:51:22'),
(95, 1, 'Login Successful', '2025-01-31 15:09:46'),
(96, 1, 'Login Successful', '2025-02-01 08:53:38'),
(97, 1, 'Login Failed: Invalid Password', '2025-02-01 09:38:12'),
(98, 1, 'Login Successful', '2025-02-01 09:38:15'),
(99, 1, 'Login Successful', '2025-02-01 10:08:38'),
(100, 1, 'Login Successful', '2025-02-01 10:22:59'),
(101, 1, 'Admin Logged Out', '2025-02-01 10:55:34'),
(102, 1, 'Login Successful', '2025-02-01 10:56:03'),
(103, 1, 'Login Successful', '2025-02-01 10:57:03'),
(104, 1, 'Admin Logged Out', '2025-02-01 10:57:25'),
(105, 1, 'Login Successful', '2025-02-01 10:58:06'),
(106, 1, 'Login Successful', '2025-02-01 11:11:36'),
(107, 1, 'Admin Logged Out', '2025-02-01 11:15:35'),
(108, 1, 'Login Successful', '2025-02-01 11:15:40'),
(109, 1, 'Login Successful', '2025-02-01 11:16:07'),
(110, 1, 'Admin Logged Out', '2025-02-01 13:09:44'),
(111, 1, 'Login Successful', '2025-02-01 13:09:47'),
(112, 1, 'Admin Logged Out', '2025-02-01 13:10:18'),
(113, 1, 'Login Successful', '2025-02-01 13:10:20'),
(114, 1, 'Login Successful', '2025-02-02 12:52:28'),
(115, 1, 'Admin Logged Out', '2025-02-02 13:11:34'),
(116, 1, 'Login Successful', '2025-02-02 13:11:43'),
(117, 1, 'Login Successful', '2025-02-05 05:35:18'),
(118, 1, 'Admin Logged Out', '2025-02-05 05:41:10'),
(119, 1, 'Login Successful', '2025-02-05 05:41:13'),
(120, 1, 'Admin Logged Out', '2025-02-05 07:02:06'),
(121, 1, 'Login Successful', '2025-02-05 07:02:11'),
(122, 1, 'Admin Logged Out', '2025-02-05 10:10:57'),
(123, 1, 'Login Successful', '2025-02-05 10:11:00'),
(124, 1, 'Admin Logged Out', '2025-02-05 10:59:51'),
(125, 1, 'Login Failed: Invalid Password', '2025-02-05 10:59:54'),
(126, 1, 'Login Successful', '2025-02-05 11:00:01'),
(127, 1, 'Login Successful', '2025-02-05 11:10:51'),
(128, 1, 'Login Successful', '2025-02-06 04:25:52'),
(129, 1, 'Admin Logged Out', '2025-02-06 05:36:35'),
(130, 1, 'Login Successful', '2025-02-06 05:36:38'),
(131, 1, 'Admin Logged Out', '2025-02-06 09:13:05'),
(132, 1, 'Login Successful', '2025-02-06 09:13:07'),
(133, 1, 'Admin Logged Out', '2025-02-06 09:49:22'),
(134, 1, 'Login Successful', '2025-02-06 09:49:25'),
(135, 1, 'Admin Logged Out', '2025-02-06 14:21:56'),
(136, 1, 'Login Successful', '2025-02-06 14:22:00'),
(137, 1, 'Admin Logged Out', '2025-02-07 06:12:22'),
(138, 1, 'Login Successful', '2025-02-07 06:12:25'),
(139, 1, 'Admin Logged Out', '2025-02-07 06:31:41'),
(140, 1, 'Login Successful', '2025-02-07 06:31:44'),
(141, 1, 'Login Successful', '2025-02-07 06:34:57'),
(142, 1, 'Admin Logged Out', '2025-02-07 06:47:13'),
(143, 1, 'Login Successful', '2025-02-07 06:53:11'),
(144, 1, 'Admin Logged Out', '2025-02-07 06:53:21'),
(145, 1, 'Login Successful', '2025-02-07 06:53:24'),
(146, 1, 'Login Successful', '2025-02-07 07:14:24'),
(147, 1, 'Admin Logged Out', '2025-02-07 07:14:29'),
(148, 1, 'Login Successful', '2025-02-07 07:17:33'),
(149, 1, 'Admin Logged Out', '2025-02-07 07:23:13'),
(150, 1, 'Login Successful', '2025-02-07 07:23:15'),
(151, 1, 'Login Successful', '2025-02-07 07:34:52'),
(152, 1, 'Admin Logged Out', '2025-02-07 08:04:29'),
(153, 1, 'Login Successful', '2025-02-07 08:04:33'),
(154, 1, 'Admin Logged Out', '2025-02-07 08:08:49'),
(155, 1, 'Login Successful', '2025-02-07 08:08:53'),
(156, 1, 'Admin Logged Out', '2025-02-07 08:10:59'),
(157, 1, 'Login Successful', '2025-02-07 08:11:02'),
(158, 1, 'Admin Logged Out', '2025-02-07 10:04:05'),
(159, 1, 'Login Successful', '2025-02-07 10:04:08'),
(160, 1, 'Login Successful', '2025-02-07 10:36:38'),
(161, 1, 'Admin Logged Out', '2025-02-07 12:55:32'),
(162, 1, 'Login Successful', '2025-02-07 12:55:36'),
(163, 1, 'Login Successful', '2025-02-09 11:31:13'),
(164, 1, 'Admin Logged Out', '2025-02-09 14:05:52'),
(165, 1, 'Login Successful', '2025-02-09 14:05:54'),
(166, 1, 'Admin Logged Out', '2025-02-09 17:45:44'),
(167, 1, 'Login Successful', '2025-02-09 17:45:47'),
(168, 1, 'Login Failed: Invalid Password', '2025-02-10 03:10:45'),
(169, 1, 'Login Successful', '2025-02-10 03:10:48'),
(170, 1, 'Admin Logged Out', '2025-02-10 05:08:34'),
(171, 1, 'Login Successful', '2025-02-14 03:53:04'),
(172, 1, 'Login Successful', '2025-02-14 14:44:16'),
(173, 1, 'Login Successful', '2025-02-19 15:37:45'),
(174, 1, 'Admin Logged Out', '2025-02-19 16:16:44'),
(175, 1, 'Login Successful', '2025-02-19 16:17:23'),
(176, 1, 'Login Successful', '2025-02-20 17:26:45'),
(177, 1, 'Login Successful', '2025-02-22 00:46:49'),
(178, 1, 'Admin Logged Out', '2025-02-22 00:47:53'),
(179, 1, 'Login Successful', '2025-02-22 01:21:50'),
(180, 1, 'Admin Logged Out', '2025-02-22 09:10:10'),
(181, 1, 'Login Successful', '2025-02-22 09:10:13'),
(182, 1, 'Login Successful', '2025-02-22 09:37:33'),
(183, 1, 'Admin Logged Out', '2025-02-22 09:38:30'),
(184, 1, 'Login Successful', '2025-02-22 09:53:52'),
(185, 1, 'Admin Logged Out', '2025-02-22 09:56:54'),
(186, 1, 'Login Successful', '2025-02-22 09:57:30'),
(187, 1, 'Admin Logged Out', '2025-02-22 09:57:34'),
(188, 1, 'Login Successful', '2025-02-23 11:36:02'),
(189, 1, 'Admin Logged Out', '2025-02-23 11:38:56'),
(190, 1, 'Login Successful', '2025-02-23 11:42:35'),
(191, 1, 'Admin Logged Out', '2025-02-23 12:14:42'),
(192, 1, 'Login Successful', '2025-02-23 12:30:36'),
(193, 1, 'Admin Logged Out', '2025-02-23 12:46:34'),
(194, 1, 'Login Successful', '2025-02-23 12:46:42'),
(195, 1, 'Admin Logged Out', '2025-02-23 12:46:58'),
(196, 1, 'Login Successful', '2025-02-23 13:02:12'),
(197, 1, 'Admin Logged Out', '2025-02-23 13:03:51'),
(198, 1, 'Login Successful', '2025-03-01 01:55:49'),
(199, 1, 'Admin Logged Out', '2025-03-01 02:26:52'),
(200, 1, 'Login Successful', '2025-03-01 02:30:22'),
(201, 1, 'Login Successful', '2025-03-01 11:02:50'),
(202, 1, 'Admin Logged Out', '2025-03-01 11:02:54'),
(203, 1, 'Login Successful', '2025-03-01 11:03:01'),
(204, 1, 'Admin Logged Out', '2025-03-01 11:03:05'),
(205, 1, 'Login Successful', '2025-03-01 12:44:42'),
(206, 1, 'Admin Logged Out', '2025-03-01 12:44:57'),
(207, 1, 'Login Successful', '2025-03-01 12:47:29'),
(208, 1, 'Admin Logged Out', '2025-03-01 14:16:14'),
(209, 6, 'Login Successful', '2025-03-01 14:16:24'),
(210, 1, 'Login Successful', '2025-03-01 14:17:15'),
(211, 1, 'Admin Logged Out', '2025-03-01 14:17:19'),
(212, 6, 'Login Successful', '2025-03-01 14:19:58'),
(213, 6, 'User Logged Out', '2025-03-01 14:20:28'),
(214, 6, 'Login Successful', '2025-03-01 14:21:18'),
(215, 6, 'User Logged Out', '2025-03-01 14:26:11'),
(216, 6, 'Login Successful', '2025-03-01 14:26:16'),
(217, 6, 'Login Successful', '2025-03-01 14:37:05'),
(218, 6, 'Login Successful', '2025-03-01 14:37:13'),
(219, 6, 'User Logged Out', '2025-03-01 14:37:21'),
(220, 1, 'Login Successful', '2025-03-01 15:14:03'),
(221, 1, 'Admin Logged Out', '2025-03-01 15:23:26'),
(222, 6, 'Login Successful', '2025-03-01 15:28:11'),
(223, 6, 'Login Successful', '2025-03-01 15:32:20'),
(224, 6, 'Login Successful', '2025-03-01 15:37:28'),
(225, 6, 'Login Successful', '2025-03-01 15:38:52'),
(226, 6, 'User Logged Out', '2025-03-01 15:39:29'),
(227, 1, 'Login Successful', '2025-03-01 15:54:43'),
(228, 1, 'Admin Logged Out', '2025-03-01 15:54:47'),
(229, 9, 'Login Successful', '2025-03-01 16:30:08'),
(230, 6, 'Login Successful', '2025-03-01 16:47:29'),
(231, 6, 'Login Successful', '2025-03-01 16:47:53'),
(232, 6, 'Login Successful', '2025-03-01 16:49:11'),
(233, 9, 'User Logged Out', '2025-03-01 17:24:42'),
(234, 1, 'Login Successful', '2025-03-01 17:24:49'),
(235, 1, 'Admin Logged Out', '2025-03-01 17:25:17'),
(236, 6, 'Login Successful', '2025-03-01 17:25:21'),
(237, 6, 'User Logged Out', '2025-03-01 17:38:31'),
(238, 1, 'Login Successful', '2025-03-01 17:38:36'),
(239, 1, 'Admin Logged Out', '2025-03-01 17:39:06'),
(240, 6, 'Login Successful', '2025-03-01 17:39:11'),
(241, 6, 'User Logged Out', '2025-03-01 17:41:45'),
(242, 10, 'Login Successful', '2025-03-01 17:53:23'),
(243, 1, 'Login Successful', '2025-03-01 17:55:56'),
(244, 6, 'Login Successful', '2025-03-01 18:01:13'),
(245, 6, 'User Logged Out', '2025-03-01 18:01:20'),
(246, 10, 'Login Successful', '2025-03-01 18:01:25'),
(247, 10, 'User Logged Out', '2025-03-01 18:03:47'),
(248, 1, 'Login Successful', '2025-03-01 18:03:52'),
(249, 1, 'Admin Logged Out', '2025-03-01 18:04:01'),
(250, 1, 'Login Successful', '2025-03-01 18:04:07'),
(251, 1, 'Admin Logged Out', '2025-03-01 18:04:33'),
(252, 10, 'Login Successful', '2025-03-01 18:04:41'),
(253, 10, 'User Logged Out', '2025-03-01 18:05:04'),
(254, 6, 'Login Successful', '2025-03-02 03:54:26'),
(255, 6, 'User Logged Out', '2025-03-02 04:00:02'),
(256, 6, 'Login Successful', '2025-03-02 04:01:57'),
(257, 1, 'Login Successful', '2025-03-02 04:04:47'),
(258, 10, 'Login Successful', '2025-03-02 04:29:20'),
(259, 1, 'Login Successful', '2025-03-02 04:30:00'),
(260, 6, 'Login Successful', '2025-03-02 04:40:41'),
(261, 1, 'Login Successful', '2025-03-02 04:41:50'),
(262, 6, 'Login Successful', '2025-03-02 04:42:04'),
(263, 6, 'User Logged Out', '2025-03-02 04:42:13'),
(264, 10, 'Login Successful', '2025-03-02 04:42:20'),
(265, 1, 'Login Successful', '2025-03-02 04:43:59'),
(266, 10, 'Login Failed: Invalid Password', '2025-03-02 04:47:26'),
(267, 10, 'Login Successful', '2025-03-02 04:47:30'),
(268, 1, 'Login Successful', '2025-03-02 04:57:44'),
(269, 10, 'Login Successful', '2025-03-02 04:58:58'),
(270, 10, 'User Logged Out', '2025-03-02 05:14:14'),
(271, 10, 'Login Successful', '2025-03-02 05:14:22'),
(272, 10, 'User Logged Out', '2025-03-02 05:51:13'),
(273, 1, 'Login Successful', '2025-03-02 05:51:18'),
(274, 1, 'Admin Logged Out', '2025-03-02 12:22:26'),
(275, 10, 'Login Successful', '2025-03-02 12:23:03'),
(276, 10, 'User Logged Out', '2025-03-02 12:24:17'),
(277, 11, 'Login Successful', '2025-03-02 13:50:07'),
(278, 11, 'User Logged Out', '2025-03-02 13:50:53'),
(279, 13, 'Login Successful', '2025-03-02 13:56:21'),
(280, 13, 'User Logged Out', '2025-03-02 13:56:30'),
(281, NULL, 'Login Failed: Username/Email Not Found', '2025-03-02 14:05:00'),
(282, 13, 'Login Successful', '2025-03-02 14:05:03'),
(283, 13, 'User Logged Out', '2025-03-02 14:05:13'),
(284, 16, 'Login Successful', '2025-03-02 14:54:06'),
(285, 16, 'User Logged Out', '2025-03-02 14:54:13'),
(286, 18, 'Login Successful', '2025-03-02 15:11:40'),
(287, 18, 'User Logged Out', '2025-03-02 15:11:45'),
(288, 19, 'Login Successful', '2025-03-02 15:19:51'),
(289, 19, 'User Logged Out', '2025-03-02 15:20:03'),
(290, 20, 'Login Successful', '2025-03-02 15:22:42'),
(291, 20, 'User Logged Out', '2025-03-02 15:29:05'),
(292, 21, 'Login Successful', '2025-03-02 15:30:02'),
(293, 21, 'User Logged Out', '2025-03-02 15:35:08'),
(294, 1, 'Login Successful', '2025-03-02 15:38:00'),
(295, 1, 'Admin Logged Out', '2025-03-02 15:38:02'),
(296, NULL, 'Login Failed: Username/Email Not Found', '2025-03-02 15:38:05'),
(297, 11, 'Login Failed: Invalid Password', '2025-03-02 15:38:08'),
(298, 10, 'Login Failed: Invalid Password', '2025-03-02 15:38:14'),
(299, 10, 'Login Successful', '2025-03-02 15:38:18'),
(300, 10, 'User Logged Out', '2025-03-02 15:38:19'),
(301, 1, 'Login Successful', '2025-03-02 15:39:11'),
(302, 1, 'Admin Logged Out', '2025-03-02 15:51:03'),
(303, 1, 'Login Successful', '2025-03-03 16:02:21'),
(304, 1, 'Admin Logged Out', '2025-03-03 16:15:04'),
(305, NULL, 'Login Failed: Username/Email Not Found', '2025-03-03 16:15:08'),
(306, NULL, 'Login Failed: Username/Email Not Found', '2025-03-03 16:15:14'),
(307, 11, 'Login Successful', '2025-03-03 16:15:18'),
(308, 11, 'User Logged Out', '2025-03-03 16:15:34'),
(309, 8, 'Login Failed: Invalid Password', '2025-03-03 16:15:55'),
(310, 8, 'Login Failed: Invalid Password', '2025-03-03 16:16:00'),
(311, 10, 'Login Successful', '2025-03-03 16:16:09'),
(312, 10, 'User Logged Out', '2025-03-03 16:16:25'),
(313, 1, 'Login Successful', '2025-03-03 16:16:28'),
(314, 1, 'Admin Logged Out', '2025-03-03 16:17:37'),
(315, 10, 'Login Successful', '2025-03-03 16:17:43'),
(316, 10, 'User Logged Out', '2025-03-03 16:25:49'),
(317, 1, 'Login Failed: Invalid Password', '2025-03-03 16:25:52'),
(318, 1, 'Login Successful', '2025-03-03 16:25:55'),
(319, 1, 'Admin Logged Out', '2025-03-03 16:56:30'),
(320, 10, 'Login Successful', '2025-03-03 16:56:34'),
(321, 10, 'User Logged Out', '2025-03-03 16:56:48'),
(322, 1, 'Login Successful', '2025-03-03 16:56:51'),
(323, 1, 'Login Successful', '2025-04-07 09:20:56'),
(324, 1, 'Admin Logged Out', '2025-04-07 10:33:51'),
(325, 1, 'Login Successful', '2025-04-07 10:33:54'),
(326, 1, 'Admin Logged Out', '2025-04-07 10:49:47'),
(327, 1, 'Login Successful', '2025-04-07 10:49:49'),
(328, 1, 'Admin Logged Out', '2025-04-07 10:55:09'),
(329, 10, 'Login Failed: Invalid Password', '2025-04-07 10:55:16'),
(330, 1, 'Login Successful', '2025-04-07 10:55:20'),
(331, 1, 'Admin Logged Out', '2025-04-07 10:55:24'),
(332, 1, 'Login Failed: Invalid Password', '2025-04-07 10:55:43'),
(333, 1, 'Login Successful', '2025-04-07 10:55:46'),
(334, 1, 'Admin Logged Out', '2025-04-07 10:56:14'),
(335, 10, 'Login Successful', '2025-04-07 10:56:18'),
(336, 10, 'User Logged Out', '2025-04-07 10:59:02'),
(337, 10, 'Login Successful', '2025-04-07 10:59:06'),
(338, 10, 'User Logged Out', '2025-04-07 11:02:07'),
(339, 10, 'Login Successful', '2025-04-07 11:02:11'),
(340, 10, 'User Logged Out', '2025-04-07 11:02:21'),
(341, 1, 'Login Successful', '2025-04-07 11:02:23'),
(342, 1, 'Admin Logged Out', '2025-04-07 13:35:35'),
(343, 10, 'Login Successful', '2025-04-07 13:35:38'),
(344, 10, 'User Logged Out', '2025-04-07 13:38:03'),
(345, 1, 'Login Successful', '2025-04-07 13:38:07'),
(346, 1, 'Login Successful', '2025-04-25 03:27:34'),
(347, 1, 'Login Successful', '2025-04-25 03:42:19'),
(348, 1, 'Admin Logged Out', '2025-04-26 09:15:11'),
(349, 10, 'Login Failed: Invalid Password', '2025-04-26 09:15:17'),
(350, 10, 'Login Successful', '2025-04-26 09:15:20'),
(351, 10, 'User Logged Out', '2025-04-26 09:15:44'),
(352, 1, 'Login Successful', '2025-04-26 09:15:47'),
(353, 1, 'Login Successful', '2025-04-27 04:05:59'),
(354, 1, 'Login Successful', '2025-04-27 06:35:29'),
(355, 1, 'Admin Logged Out', '2025-04-27 06:44:08'),
(356, 1, 'Login Successful', '2025-04-27 06:44:11'),
(357, 1, 'Admin Logged Out', '2025-04-27 06:48:03'),
(358, 1, 'Login Successful', '2025-04-27 06:48:06'),
(359, 1, 'Admin Logged Out', '2025-04-27 06:48:34'),
(360, 1, 'Login Successful', '2025-04-27 06:48:37'),
(361, 1, 'Login Failed: Invalid Password', '2025-04-29 06:34:58'),
(362, 1, 'Login Successful', '2025-04-29 06:35:01'),
(363, 1, 'Login Failed: Invalid Password', '2025-04-29 06:51:07'),
(364, 1, 'Login Successful', '2025-04-29 06:51:10'),
(365, 1, 'Admin Logged Out', '2025-04-29 08:03:23'),
(366, 1, 'Login Successful', '2025-04-29 08:03:26'),
(367, 1, 'Admin Logged Out', '2025-04-29 08:39:53'),
(368, 10, 'Login Successful', '2025-04-29 08:39:56'),
(369, 10, 'User Logged Out', '2025-04-29 08:40:07'),
(370, 1, 'Login Successful', '2025-04-29 08:40:10'),
(371, 1, 'Admin Logged Out', '2025-04-29 09:36:34'),
(372, 10, 'Login Successful', '2025-04-29 09:36:39'),
(373, 10, 'User Logged Out', '2025-04-29 09:37:01'),
(374, 1, 'Login Successful', '2025-04-29 09:37:04'),
(375, 1, 'Admin Logged Out', '2025-04-29 09:38:50'),
(376, 10, 'Login Failed: Invalid Password', '2025-04-29 09:38:55'),
(377, 10, 'Login Successful', '2025-04-29 09:38:59'),
(378, 10, 'User Logged Out', '2025-04-29 09:42:51'),
(379, 1, 'Login Successful', '2025-04-29 09:42:54'),
(380, 1, 'Admin Logged Out', '2025-04-29 09:43:19'),
(381, 10, 'Login Successful', '2025-04-29 09:43:25'),
(382, 10, 'User Logged Out', '2025-04-29 09:45:30'),
(383, 1, 'Login Successful', '2025-04-29 09:46:06'),
(384, 1, 'Login Successful', '2025-04-29 09:46:38'),
(385, 1, 'Login Successful', '2025-04-29 09:49:51'),
(386, 1, 'Admin Logged Out', '2025-04-29 09:54:05'),
(387, 10, 'Login Successful', '2025-04-29 09:54:08'),
(388, 10, 'User Logged Out', '2025-04-29 09:54:10'),
(389, 10, 'Login Successful', '2025-04-29 09:54:14'),
(390, 1, 'Login Failed: Invalid Password', '2025-05-04 03:25:38'),
(391, 1, 'Login Successful', '2025-05-04 03:25:46'),
(392, 1, 'Admin Logged Out', '2025-05-04 03:26:01'),
(393, 10, 'Login Successful', '2025-05-04 03:26:05'),
(394, 10, 'User Logged Out', '2025-05-04 03:26:43'),
(395, 1, 'Login Successful', '2025-05-04 03:26:47'),
(396, 1, 'Login Successful', '2025-05-09 16:38:34');

-- --------------------------------------------------------

--
-- Table structure for table `medical_consultation_records`
--

CREATE TABLE `medical_consultation_records` (
  `record_id` int(11) NOT NULL,
  `appointment_id` int(11) DEFAULT NULL,
  `chief_complaint` text DEFAULT NULL,
  `history_of_present_illness` text DEFAULT NULL,
  `physical_exam` text DEFAULT NULL,
  `diagnosis` text DEFAULT NULL,
  `treatment` text DEFAULT NULL,
  `prescription` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `medical_consultation_records`
--

INSERT INTO `medical_consultation_records` (`record_id`, `appointment_id`, `chief_complaint`, `history_of_present_illness`, `physical_exam`, `diagnosis`, `treatment`, `prescription`, `created_at`) VALUES
(1, NULL, 'test', 'wat', '', '', 'tset', 'test', '2025-04-28 16:06:10'),
(2, 220, 'yesy', 'seysey', 'seysey', 'seysey', 'sey', 'sey', '2025-05-09 20:27:39');

-- --------------------------------------------------------

--
-- Table structure for table `medical_services`
--

CREATE TABLE `medical_services` (
  `service_id` int(11) NOT NULL,
  `service_name` varchar(255) NOT NULL,
  `category` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `medical_services`
--

INSERT INTO `medical_services` (`service_id`, `service_name`, `category`, `description`, `price`) VALUES
(1, 'Transvaginal Ultrasound', 'Ultrasound', 'A detailed internal ultrasound exam', 300.00),
(2, 'OB Ultrasound', 'Ultrasound', 'Routine pregnancy ultrasound', 250.00),
(3, 'Prenatal Checkup', 'Maternity Checkup', 'Routine checkup during pregnancy', 100.00),
(4, 'Postnatal Checkup', 'Maternity Checkup', 'Routine checkup after delivery', 120.00),
(5, 'Pap Smear', 'Laboratory', 'Routine gynecological test to screen for cervical abnormalities', 150.00),
(6, 'Circumcision', 'Surgical Procedure', 'Surgical removal of the foreskin of the penis', 200.00),
(7, 'Urinalysis', 'Laboratory', 'Test to check for signs of infections or other health conditions', 50.00),
(8, 'Hemoglobin Test', 'Laboratory', 'Test to measure the level of hemoglobin in the blood', 70.00),
(9, 'Vaccination for Newborn', 'Vaccination', 'Vaccinations required for newborn babies', 80.00);

-- --------------------------------------------------------

--
-- Table structure for table `medical_transactions`
--

CREATE TABLE `medical_transactions` (
  `transaction_id` int(11) NOT NULL,
  `case_id` varchar(20) NOT NULL,
  `service_id` int(11) NOT NULL,
  `transaction_date` datetime NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_status` text NOT NULL DEFAULT 'Pending',
  `diagnosis` text DEFAULT NULL,
  `results` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `medical_transactions`
--

INSERT INTO `medical_transactions` (`transaction_id`, `case_id`, `service_id`, `transaction_date`, `amount`, `payment_status`, `diagnosis`, `results`) VALUES
(62, 'C006', 1, '2025-05-10 03:04:00', 300.00, 'Pending', NULL, NULL),
(63, 'C006', 1, '2025-05-10 03:04:00', 300.00, 'Pending', NULL, NULL),
(64, 'C006', 2, '2025-05-10 03:12:00', 250.00, 'Pending', NULL, NULL),
(65, 'C006', 3, '2025-05-10 03:17:00', 100.00, 'Pending', NULL, NULL),
(66, 'C006', 3, '2025-05-10 03:17:00', 100.00, 'Paid', NULL, NULL),
(67, 'C006', 4, '2025-05-10 03:25:00', 120.00, 'Pending', NULL, NULL),
(68, 'C006', 5, '2025-05-10 03:32:00', 150.00, 'Pending', NULL, NULL),
(69, 'C006', 7, '2025-05-10 03:32:00', 50.00, 'Pending', NULL, NULL),
(70, 'C006', 8, '2025-05-10 03:32:00', 70.00, 'Pending', NULL, NULL),
(71, 'C006', 6, '2025-05-10 03:40:00', 200.00, 'Pending', NULL, NULL),
(74, 'C006', 9, '2025-05-10 04:02:00', 80.00, 'Pending', NULL, NULL),
(75, 'C006', 9, '2025-05-10 04:03:00', 80.00, 'Pending', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `ob_ultrasound`
--

CREATE TABLE `ob_ultrasound` (
  `id` int(11) NOT NULL,
  `case_id` varchar(20) NOT NULL COMMENT 'Foreign key to patient cases',
  `transaction_id` int(11) NOT NULL COMMENT 'Foreign key to medical transactions',
  `ob_score` varchar(20) DEFAULT NULL COMMENT 'Gravida_Para format (G_P_)',
  `lmp` date DEFAULT NULL COMMENT 'Last Menstrual Period',
  `aog` varchar(20) DEFAULT NULL COMMENT 'Age of Gestation (weeks+days)',
  `edg` date DEFAULT NULL COMMENT 'Estimated Delivery Date',
  `fetus_count` int(11) DEFAULT 1 COMMENT 'Number of fetuses',
  `fetal_presentation` enum('cephalic','breech','transverse','unknown') DEFAULT 'unknown',
  `fetal_heart_rate` varchar(10) DEFAULT NULL COMMENT 'FHR in bpm',
  `amniotic_fluid_index` varchar(20) DEFAULT NULL COMMENT 'AFI measurement',
  `bpd` decimal(5,2) DEFAULT NULL COMMENT 'Biparietal Diameter (cm)',
  `hc` decimal(5,2) DEFAULT NULL COMMENT 'Head Circumference (cm)',
  `fl` decimal(5,2) DEFAULT NULL COMMENT 'Femur Length (cm)',
  `ac` decimal(5,2) DEFAULT NULL COMMENT 'Abdominal Circumference (cm)',
  `usg_gestational_age` varchar(20) DEFAULT NULL COMMENT 'USG-calculated AOG',
  `estimated_fetal_weight` decimal(6,2) DEFAULT NULL COMMENT 'EFW in grams',
  `placenta_position` varchar(100) DEFAULT NULL,
  `cord_coil` varchar(100) DEFAULT NULL,
  `diagnosis` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `report_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Obstetric Ultrasound Reports';

--
-- Dumping data for table `ob_ultrasound`
--

INSERT INTO `ob_ultrasound` (`id`, `case_id`, `transaction_id`, `ob_score`, `lmp`, `aog`, `edg`, `fetus_count`, `fetal_presentation`, `fetal_heart_rate`, `amniotic_fluid_index`, `bpd`, `hc`, `fl`, `ac`, `usg_gestational_age`, `estimated_fetal_weight`, `placenta_position`, `cord_coil`, `diagnosis`, `notes`, `report_date`, `created_at`, `updated_at`) VALUES
(3, 'C006', 64, '123', NULL, '123', '2025-05-10', 1, 'breech', '123', '12213', 123.00, 123.00, 123.00, 123.00, '123', 123.00, '213', '123', NULL, NULL, '2025-05-09', '2025-05-09 19:13:17', '2025-05-09 19:13:17');

-- --------------------------------------------------------

--
-- Table structure for table `pap_smear`
--

CREATE TABLE `pap_smear` (
  `id` int(11) NOT NULL,
  `case_id` varchar(255) NOT NULL,
  `transaction_id` int(11) NOT NULL,
  `specimen_type` varchar(255) DEFAULT NULL,
  `interpretation_result` text DEFAULT NULL,
  `specimen_adequacy` text DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `processed_by` varchar(255) DEFAULT NULL,
  `pathologist` varchar(255) DEFAULT NULL,
  `report_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pap_smear`
--

INSERT INTO `pap_smear` (`id`, `case_id`, `transaction_id`, `specimen_type`, `interpretation_result`, `specimen_adequacy`, `remarks`, `processed_by`, `pathologist`, `report_date`, `created_at`, `updated_at`) VALUES
(2, 'C006', 68, 'Liquid base', NULL, NULL, NULL, NULL, NULL, '2025-05-09', '2025-05-09 19:33:07', '2025-05-09 19:33:07');

-- --------------------------------------------------------

--
-- Table structure for table `patients`
--

CREATE TABLE `patients` (
  `patient_id` int(11) NOT NULL,
  `case_id` varchar(20) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `first_name` varchar(50) NOT NULL,
  `middle_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) NOT NULL,
  `gender` enum('Male','Female') NOT NULL,
  `date_of_birth` date NOT NULL,
  `email` varchar(50) DEFAULT NULL,
  `contact_number` varchar(15) NOT NULL,
  `philhealth_no` varchar(20) DEFAULT NULL,
  `religion` varchar(50) DEFAULT NULL,
  `civil_status` enum('Single','Married','Widowed','Separated','Divorced') DEFAULT NULL,
  `nationality` varchar(50) DEFAULT NULL,
  `occupation` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `patient_status` enum('Admitted','Discharged') DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `patients`
--

INSERT INTO `patients` (`patient_id`, `case_id`, `user_id`, `first_name`, `middle_name`, `last_name`, `gender`, `date_of_birth`, `email`, `contact_number`, `philhealth_no`, `religion`, `civil_status`, `nationality`, `occupation`, `address`, `patient_status`, `created_at`) VALUES
(1, 'C001', 10, 'Jenlee', NULL, 'Hong', 'Female', '1990-05-15', NULL, '09171234567', NULL, NULL, NULL, NULL, NULL, '123 Main Street, Calapan', NULL, '2025-01-23 02:19:09'),
(2, 'C002', 16, 'John', NULL, 'Smith', 'Male', '1985-03-22', NULL, '09183456789', NULL, NULL, NULL, NULL, NULL, '456 Elm Street, Calapan', NULL, '2025-01-23 02:19:09'),
(3, 'C003', NULL, 'Alice', NULL, 'Johnson', 'Female', '1992-07-30', NULL, '09176543210', NULL, NULL, NULL, NULL, NULL, '789 Oak Avenue, Calapan', NULL, '2025-01-23 02:19:09'),
(4, 'C004', 22, 'Mark', NULL, 'Brown', 'Male', '1988-11-11', NULL, '09185678912', NULL, NULL, NULL, NULL, NULL, '101 Pine Road, Calapan', NULL, '2025-01-23 02:19:09'),
(5, 'C005', NULL, 'Emma', NULL, 'Wilson', 'Female', '1995-12-05', NULL, '09196789034', NULL, NULL, NULL, NULL, NULL, '202 Maple Lane, Calapan', NULL, '2025-01-23 02:19:09'),
(7, 'C006', 12, 'John Paul', NULL, 'Baes', 'Male', '2001-08-10', NULL, '09123456789', '', '', 'Single', '', '', '', NULL, '2025-01-24 03:55:03'),
(9, 'C008', NULL, 'Jose', NULL, 'Rizal', 'Male', '1998-02-05', NULL, '09120999241', '30275182391', 'Catholic', 'Single', 'Filipino', 'Actor', '', NULL, '2025-02-05 05:37:04'),
(10, 'C009', 18, 'James', NULL, 'Reid', 'Male', '1990-09-24', NULL, '09883123415', '30275182391', 'Catholic', 'Single', 'Filipino', 'Actor', 'ca', NULL, '2025-02-09 12:28:10'),
(11, 'C010', NULL, 'Daniel', NULL, 'Padilla', 'Male', '1990-09-08', NULL, '09883123415', '30275182391', '', 'Single', 'Filipino', 'Actor', 'N/A', NULL, '2025-02-09 12:48:21'),
(12, 'C011', NULL, 'Kathryn', NULL, 'Bernardo', 'Female', '1990-11-20', NULL, '09120999241', '30275182391', 'Catholic', 'Single', 'Filipino', 'Actor', '', NULL, '2025-02-09 12:48:48'),
(13, 'C012', NULL, 'Belle', NULL, 'Mariano', 'Female', '2002-09-22', NULL, '09883123415', '30275182391', 'Catholic', 'Single', 'Filipino', '', '', NULL, '2025-02-09 12:49:16'),
(14, 'C013', NULL, 'Juan ', NULL, 'Dela Cruz', 'Male', '1990-04-12', NULL, '09120999241', '30275182391', 'Catholic', 'Single', 'Filipino', '', '', NULL, '2025-02-10 03:27:39'),
(15, 'C014', 13, 'Maria', NULL, 'Santos', 'Female', '1999-03-21', NULL, '09883123415', '30275182391', 'Catholic', 'Single', 'Filipino', '', '', NULL, '2025-02-10 03:29:57'),
(16, 'C015', NULL, 'Pedro ', NULL, 'Ramirez', 'Male', '1987-11-24', NULL, '09120999241', '30275182391', 'Catholic', 'Single', 'Filipino', '', '', NULL, '2025-02-10 03:30:34'),
(18, 'C016', NULL, 'Belle', NULL, 'Padilla', 'Female', '1996-02-17', NULL, '09883123415', '30275182391', 'Catholic', 'Single', '', '', '', NULL, '2025-02-22 06:41:38'),
(19, 'C017', NULL, 'test', NULL, 'user', 'Female', '2011-03-22', NULL, '09120999241', '30275182391', 'Catholic', 'Single', '', '', '', NULL, '2025-02-22 10:11:36'),
(20, 'C018', NULL, 'Killua', NULL, 'Zoldyck', 'Male', '1990-09-13', NULL, '09883123415', '30275182391', 'Catholic', 'Single', '', '', '', NULL, '2025-02-23 10:51:53'),
(21, 'C019', 21, 'Tessttt', NULL, 'User', 'Female', '1990-02-03', NULL, '09883123415', NULL, NULL, 'Single', 'Filipino', '', '', NULL, '2025-02-23 10:58:56'),
(22, 'C020', NULL, 'Anya', NULL, 'Forger', 'Female', '2001-02-03', NULL, '09120999241', '30275182391', 'Catholic', 'Single', '', '', '', NULL, '2025-02-23 11:02:51'),
(23, 'C021', 15, 'JAMES', NULL, 'Bernardo', 'Male', '2001-02-13', NULL, '09120999241', '30275182391', 'Catholic', 'Single', 'Filipino', 'Actor', 'masipit, calapan city', NULL, '2025-02-23 11:13:12'),
(24, 'C022', NULL, 'JAMES', NULL, 'RIZAL', 'Male', '2003-01-02', NULL, '09883123415', '', '', 'Single', '', '', '', NULL, '2025-02-23 11:24:25'),
(25, 'C023', NULL, 'JAMES', NULL, 'Padilla', 'Male', '2001-01-01', NULL, '09883123415', '', '', 'Single', '', '', '', NULL, '2025-02-23 12:47:54'),
(26, 'C024', NULL, 'JAMES', NULL, 'Reid', 'Female', '2000-02-23', NULL, '09883123415', '', '', 'Single', '', '', '', NULL, '2025-03-01 12:41:00'),
(27, 'C025', NULL, 'Sofia ', NULL, 'Mendoza', 'Female', '2001-02-11', NULL, '09883123415', '', '', 'Single', '', '', '', NULL, '2025-03-01 14:45:18'),
(30, 'C026', NULL, 'JAMES', NULL, 'Padilla', 'Male', '2001-02-02', NULL, '09883123415', '', '', 'Single', '', '', '', NULL, '2025-03-01 14:47:26'),
(32, 'C027', NULL, 'Jose', NULL, 'Padilla', 'Male', '2002-02-22', NULL, '09120999241', '', '', 'Single', '', '', '', NULL, '2025-03-01 14:48:00'),
(33, 'C028', NULL, 'John', 'Pom', 'Manalo', 'Male', '2001-01-03', 'helloworld@gmail.com', '09123456789', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-03-01 15:12:19'),
(36, 'C029', 6, 'Daniel', 'AMSOCAS ', 'RIZAL', 'Male', '2001-01-02', 'email@gmail.com', '09883123415', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-03-01 15:23:49'),
(37, 'C030', NULL, 'jajab', NULL, 'iwiqyb', 'Female', '2001-03-05', NULL, '09123456789', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-03-01 15:25:17'),
(38, 'C031', 7, 'JAMES', 'EMINME', 'Reid', 'Female', '2001-02-02', 'test@gmail.com', 'ascasc', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-03-01 16:19:25'),
(39, 'C032', 8, 'test1', 't', 't', 'Male', '2001-01-02', 'Test1@gmail.com', '091231241241', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-03-01 16:23:08'),
(40, 'C033', 9, 'John ', 'Test', 'Ulit', 'Male', '2000-02-10', 'testing@gmail.com', '09123456789', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-03-01 16:25:00'),
(41, 'C034', 19, 'John', 'Michael', 'Doe', 'Male', '1995-05-15', 'johndoe@example.com', '09123456789', NULL, NULL, NULL, NULL, NULL, NULL, 'Admitted', '2025-03-02 15:19:43'),
(42, 'C035', 20, 'Sarah', 'Anne', 'Smith', 'Female', '1995-08-12', 'sarah.smith@example.com', '+639876543210', NULL, NULL, NULL, NULL, NULL, NULL, 'Admitted', '2025-03-02 15:22:36');

-- --------------------------------------------------------

--
-- Table structure for table `postnatal_records`
--

CREATE TABLE `postnatal_records` (
  `record_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `appointment_id` int(11) DEFAULT NULL,
  `transaction_id` int(11) DEFAULT NULL,
  `visit_date` datetime DEFAULT NULL,
  `attending_physician` varchar(255) DEFAULT NULL,
  `delivery_date` date DEFAULT NULL,
  `delivery_type` varchar(100) DEFAULT NULL,
  `birth_weight` decimal(5,2) DEFAULT NULL,
  `birth_length` decimal(5,2) DEFAULT NULL,
  `apgar_score` varchar(20) DEFAULT NULL,
  `maternal_complications` text DEFAULT NULL,
  `neonatal_complications` text DEFAULT NULL,
  `breastfeeding_initiated` varchar(10) DEFAULT NULL,
  `postpartum_bleeding` varchar(100) DEFAULT NULL,
  `uterine_involution` varchar(100) DEFAULT NULL,
  `perineal_healing` varchar(100) DEFAULT NULL,
  `contraceptive_counseling` varchar(100) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `postnatal_records`
--

INSERT INTO `postnatal_records` (`record_id`, `patient_id`, `appointment_id`, `transaction_id`, `visit_date`, `attending_physician`, `delivery_date`, `delivery_type`, `birth_weight`, `birth_length`, `apgar_score`, `maternal_complications`, `neonatal_complications`, `breastfeeding_initiated`, `postpartum_bleeding`, `uterine_involution`, `perineal_healing`, `contraceptive_counseling`, `remarks`, `created_at`) VALUES
(3, 7, 217, NULL, '2025-05-10 03:11:00', 'Dr. Idol Bondoc', '2025-05-10', 'test', 123.00, 123.00, '123', NULL, NULL, 'Yes', NULL, NULL, NULL, NULL, NULL, '2025-05-09 19:12:00'),
(4, 7, NULL, 67, '2025-05-10 03:25:00', 'Dr. Idol Bondoc', '2025-05-10', 'test', 123.00, 123.00, '123', NULL, NULL, 'Yes', NULL, NULL, NULL, NULL, NULL, '2025-05-09 19:25:43');

-- --------------------------------------------------------

--
-- Table structure for table `prenatal_records`
--

CREATE TABLE `prenatal_records` (
  `record_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `appointment_id` int(11) DEFAULT NULL,
  `transaction_id` int(11) DEFAULT NULL,
  `visit_date` datetime NOT NULL,
  `attending_physician` varchar(100) DEFAULT NULL,
  `gravida` int(11) DEFAULT NULL,
  `para` int(11) DEFAULT NULL,
  `ob_score` varchar(20) DEFAULT NULL,
  `lmp` date DEFAULT NULL,
  `aog_by_lmp` decimal(5,2) DEFAULT NULL,
  `edc_by_lmp` date DEFAULT NULL,
  `aog_by_usg` decimal(5,2) DEFAULT NULL,
  `edc_by_usg` date DEFAULT NULL,
  `blood_pressure` varchar(10) DEFAULT NULL,
  `weight` decimal(5,2) DEFAULT NULL,
  `temperature` decimal(4,2) DEFAULT NULL,
  `respiratory_rate` int(11) DEFAULT NULL,
  `fundal_height` decimal(5,2) DEFAULT NULL,
  `fetal_heart_tones` int(11) DEFAULT NULL,
  `internal_examination` text DEFAULT NULL,
  `chief_complaint` text DEFAULT NULL,
  `history_of_present_illness` text DEFAULT NULL,
  `past_medical_history` text DEFAULT NULL,
  `past_social_history` text DEFAULT NULL,
  `family_history` text DEFAULT NULL,
  `tt_dose` varchar(100) DEFAULT NULL,
  `plan` text DEFAULT NULL,
  `lab_results` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `prenatal_records`
--

INSERT INTO `prenatal_records` (`record_id`, `patient_id`, `appointment_id`, `transaction_id`, `visit_date`, `attending_physician`, `gravida`, `para`, `ob_score`, `lmp`, `aog_by_lmp`, `edc_by_lmp`, `aog_by_usg`, `edc_by_usg`, `blood_pressure`, `weight`, `temperature`, `respiratory_rate`, `fundal_height`, `fetal_heart_tones`, `internal_examination`, `chief_complaint`, `history_of_present_illness`, `past_medical_history`, `past_social_history`, `family_history`, `tt_dose`, `plan`, `lab_results`) VALUES
(16, 7, 216, NULL, '2025-05-10 03:10:00', 'Dr. Idol Bondoc', 123, 123, '123', '2025-05-10', 123.00, '2025-05-10', NULL, '2025-05-10', '123', 123.00, 99.99, 123, 123.00, 123, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(17, 7, NULL, 65, '2025-05-09 03:17:00', 'Dr. Idol Bondoc', 123, 123, '123', '2025-05-10', 123.00, '2025-05-10', NULL, '2025-05-10', '123', 123.00, 99.99, 123, 213.00, 123, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(18, 7, NULL, 66, '2025-05-08 03:17:00', 'Dr. Idol Bondoc', 123, 123, '123', '2025-05-10', 123.00, '2025-05-10', NULL, '2025-05-10', '123', 123.00, 99.99, 123, 123.00, 123, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `professional_fees`
--

CREATE TABLE `professional_fees` (
  `professional_fee_id` int(11) NOT NULL,
  `billing_id` int(11) NOT NULL,
  `professional_name` varchar(100) NOT NULL,
  `service_description` varchar(255) NOT NULL,
  `fee_amount` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `professional_fees`
--

INSERT INTO `professional_fees` (`professional_fee_id`, `billing_id`, `professional_name`, `service_description`, `fee_amount`) VALUES
(1, 17, 'Dr. Idol L. Bondoc', 'Postnatal Checkup', 500.00),
(2, 18, 'Dr. Idol L. Bondoc', 'Vaccination for Newborn', 500.00),
(3, 19, 'Dr. Idol L. Bondoc', 'Circumcision', 500.00),
(4, 20, 'Dr. Idol L. Bondoc', 'OB Ultrasound', 500.00),
(5, 22, 'Dr. Idol L. Bondoc', 'Transvaginal Ultrasound', 2000.00),
(6, 23, 'Dr. Idol L. Bondoc', 'Transvaginal Ultrasound', 1500.00),
(7, 24, 'Dr. Idol L. Bondoc', 'Vaccination for Newborn', 1000.00),
(8, 25, 'Dr. Idol L. Bondoc', 'Prenatal Checkup', 200.00),
(9, 26, 'Dr. Idol L. Bondoc', 'Transvaginal Ultrasound', 200.00),
(10, 28, 'Dr. Idol L. Bondoc', 'Prenatal Checkup', 100.00);

-- --------------------------------------------------------

--
-- Table structure for table `regular_checkup_records`
--

CREATE TABLE `regular_checkup_records` (
  `record_id` int(11) NOT NULL,
  `appointment_id` int(11) DEFAULT NULL,
  `blood_pressure` varchar(20) DEFAULT NULL,
  `temperature` decimal(4,1) DEFAULT NULL,
  `pulse_rate` int(11) DEFAULT NULL,
  `respiratory_rate` int(11) DEFAULT NULL,
  `weight` decimal(5,2) DEFAULT NULL,
  `height` decimal(5,2) DEFAULT NULL,
  `bmi` decimal(5,2) DEFAULT NULL,
  `chief_complaint` text DEFAULT NULL,
  `diagnosis` text DEFAULT NULL,
  `treatment_plan` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `regular_checkup_records`
--

INSERT INTO `regular_checkup_records` (`record_id`, `appointment_id`, `blood_pressure`, `temperature`, `pulse_rate`, `respiratory_rate`, `weight`, `height`, `bmi`, `chief_complaint`, `diagnosis`, `treatment_plan`, `created_at`) VALUES
(1, NULL, '120/80', 35.8, 75, 16, 65.50, 165.00, 24.00, 'idk', 'test', 'wait', '2025-04-28 13:35:18'),
(2, NULL, '120/80', 33.7, 75, 16, 65.60, 165.00, 24.00, 'idk', 'test', 'wait', '2025-04-28 16:04:57'),
(3, NULL, '120/39', 3.0, 53, 333, 333.00, 33.00, 33.00, '3333', 'dsgfdshfhydfy', 'erwrwrtetetgfdfsdsgsg', '2025-05-04 03:45:02'),
(4, NULL, '120/80', 123.0, 123, 123, 123.00, 123.00, 123.00, '123', '123', '123', '2025-05-09 17:37:31'),
(5, NULL, '120/80', 123.0, 123, 123, 123.00, 123.00, 123.00, '123', '123', '123', '2025-05-09 17:46:40');

-- --------------------------------------------------------

--
-- Table structure for table `rooms`
--

CREATE TABLE `rooms` (
  `room_id` int(11) NOT NULL,
  `room_number` varchar(50) NOT NULL,
  `case_id` varchar(20) DEFAULT NULL,
  `status` enum('Available','Occupied','Under Maintenance') DEFAULT 'Available'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rooms`
--

INSERT INTO `rooms` (`room_id`, `room_number`, `case_id`, `status`) VALUES
(1, '101', 'C006', 'Occupied'),
(2, '102', 'C011', 'Occupied'),
(3, '103', 'C004', 'Occupied'),
(4, '104', 'C025', 'Occupied'),
(5, '105', NULL, 'Available'),
(6, '106', NULL, 'Available');

-- --------------------------------------------------------

--
-- Table structure for table `staff`
--

CREATE TABLE `staff` (
  `id` int(11) NOT NULL,
  `staff_id` varchar(50) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) NOT NULL,
  `role` enum('Doctor','Midwife') NOT NULL,
  `specialization` varchar(100) DEFAULT NULL,
  `license_number` varchar(50) DEFAULT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('Male','Female','Other') NOT NULL,
  `date_hired` date NOT NULL,
  `status` enum('Active','Inactive','On Leave') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `stocks`
--

CREATE TABLE `stocks` (
  `stocks_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `date` date NOT NULL,
  `actions` enum('restock','withdrawal') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `stocks`
--

INSERT INTO `stocks` (`stocks_id`, `item_id`, `user_id`, `quantity`, `date`, `actions`) VALUES
(1, 1, 1, 1, '2025-03-01', 'restock'),
(2, 1, 1, 8, '2025-03-01', 'restock'),
(3, 2, 1, 7, '2025-03-01', 'restock'),
(4, 5, 1, 4, '2025-04-07', 'restock'),
(5, 6, 1, 5, '2025-04-07', 'restock'),
(6, 5, 1, 34, '2025-04-07', 'restock'),
(7, 1, 1, 2, '2025-04-29', 'restock'),
(8, 2, 1, 2, '2025-04-29', 'restock');

-- --------------------------------------------------------

--
-- Table structure for table `tv_ultrasound`
--

CREATE TABLE `tv_ultrasound` (
  `id` int(11) NOT NULL,
  `case_id` varchar(20) NOT NULL,
  `transaction_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `referred_by` varchar(255) DEFAULT NULL,
  `lmp` varchar(50) DEFAULT NULL,
  `g` varchar(10) DEFAULT NULL,
  `p` varchar(10) DEFAULT NULL,
  `uterus_measurement` varchar(50) DEFAULT NULL,
  `uterus_position` varchar(50) DEFAULT NULL,
  `uterus_abnormalities` text DEFAULT NULL,
  `endometrium_thickness` varchar(50) DEFAULT NULL,
  `endometrium_type` varchar(50) DEFAULT NULL,
  `menstrual_phase` varchar(100) DEFAULT NULL,
  `endometrium_abnormalities` text DEFAULT NULL,
  `right_ovary_measurements` varchar(50) DEFAULT NULL,
  `right_ovary_location` varchar(50) DEFAULT NULL,
  `right_ovary_follicle` varchar(50) DEFAULT NULL,
  `right_ovary_abnormalities` text DEFAULT NULL,
  `left_ovary_measurements` varchar(50) DEFAULT NULL,
  `left_ovary_location` varchar(50) DEFAULT NULL,
  `left_ovary_follicle` varchar(50) DEFAULT NULL,
  `left_ovary_abnormalities` text DEFAULT NULL,
  `cervix_measurements` varchar(50) DEFAULT NULL,
  `nabothian_cyst` varchar(20) DEFAULT NULL,
  `others` text DEFAULT NULL,
  `diagnosis` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tv_ultrasound`
--

INSERT INTO `tv_ultrasound` (`id`, `case_id`, `transaction_id`, `date`, `referred_by`, `lmp`, `g`, `p`, `uterus_measurement`, `uterus_position`, `uterus_abnormalities`, `endometrium_thickness`, `endometrium_type`, `menstrual_phase`, `endometrium_abnormalities`, `right_ovary_measurements`, `right_ovary_location`, `right_ovary_follicle`, `right_ovary_abnormalities`, `left_ovary_measurements`, `left_ovary_location`, `left_ovary_follicle`, `left_ovary_abnormalities`, `cervix_measurements`, `nabothian_cyst`, `others`, `diagnosis`, `created_at`) VALUES
(11, 'C006', 62, '2025-05-10', 'Dr. Idol Bondoc', '88', 'g', '1', NULL, 'Anteverted', NULL, 'Thin', 'hypo', NULL, NULL, NULL, 'Lateral', NULL, NULL, NULL, 'Lateral', NULL, NULL, NULL, 'none', NULL, NULL, '2025-05-09 19:05:01'),
(12, 'C006', 63, '2025-05-10', 'Sofia  Mendoza', '88', 'g', '1', NULL, 'Anteverted', NULL, 'Thin', 'hypo', NULL, NULL, NULL, 'Lateral', NULL, NULL, NULL, 'Lateral', NULL, NULL, NULL, 'none', NULL, NULL, '2025-05-09 19:05:41');

-- --------------------------------------------------------

--
-- Table structure for table `under_observation_records`
--

CREATE TABLE `under_observation_records` (
  `record_id` int(11) NOT NULL,
  `appointment_id` int(11) DEFAULT NULL,
  `observation_notes` text DEFAULT NULL,
  `vital_signs` text DEFAULT NULL,
  `duration_observed` varchar(50) DEFAULT NULL,
  `outcome` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `under_observation_records`
--

INSERT INTO `under_observation_records` (`record_id`, `appointment_id`, `observation_notes`, `vital_signs`, `duration_observed`, `outcome`, `created_at`) VALUES
(1, NULL, 'test', 'update', '', '', '2025-04-28 15:09:09'),
(2, NULL, 'test', '', '', '', '2025-04-28 16:07:08');

-- --------------------------------------------------------

--
-- Table structure for table `urinalysis`
--

CREATE TABLE `urinalysis` (
  `id` int(11) NOT NULL,
  `case_id` varchar(255) NOT NULL,
  `transaction_id` int(11) NOT NULL,
  `color` varchar(50) DEFAULT NULL,
  `transparency` varchar(50) DEFAULT NULL,
  `ph` decimal(4,2) DEFAULT NULL,
  `specific_gravity` decimal(4,3) DEFAULT NULL,
  `protein` varchar(50) DEFAULT NULL,
  `glucose` varchar(50) DEFAULT NULL,
  `leukocyte_esterase` varchar(50) DEFAULT NULL,
  `nitrite` varchar(50) DEFAULT NULL,
  `urobilinogen` varchar(50) DEFAULT NULL,
  `blood` varchar(50) DEFAULT NULL,
  `ketone` varchar(50) DEFAULT NULL,
  `bilirubin` varchar(50) DEFAULT NULL,
  `rbc` varchar(50) DEFAULT NULL,
  `wbc` varchar(50) DEFAULT NULL,
  `epithelial_cells` varchar(50) DEFAULT NULL,
  `mucus_threads` varchar(50) DEFAULT NULL,
  `bacteria` varchar(50) DEFAULT NULL,
  `amorphous_urates` varchar(50) DEFAULT NULL,
  `calcium_oxalate` varchar(50) DEFAULT NULL,
  `triple_phosphate` varchar(50) DEFAULT NULL,
  `others` text DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `medical_technologist` varchar(255) DEFAULT NULL,
  `pathologist` varchar(255) DEFAULT NULL,
  `report_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `urinalysis`
--

INSERT INTO `urinalysis` (`id`, `case_id`, `transaction_id`, `color`, `transparency`, `ph`, `specific_gravity`, `protein`, `glucose`, `leukocyte_esterase`, `nitrite`, `urobilinogen`, `blood`, `ketone`, `bilirubin`, `rbc`, `wbc`, `epithelial_cells`, `mucus_threads`, `bacteria`, `amorphous_urates`, `calcium_oxalate`, `triple_phosphate`, `others`, `remarks`, `medical_technologist`, `pathologist`, `report_date`, `created_at`, `updated_at`) VALUES
(2, 'C006', 69, 'asd', 'asdasd', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-05-09', '2025-05-09 19:33:17', '2025-05-09 19:40:29');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `contact_number` varchar(15) NOT NULL,
  `role` enum('Admin','Midwife','Patient') NOT NULL DEFAULT 'Patient',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `email`, `password`, `contact_number`, `role`, `created_at`) VALUES
(1, 'admin', '', '$2y$10$pngv0WVBkkBZ.RH1HnEste5XJ611FXy2blUvXIEZMGocl7haPE7D.', '', 'Admin', '2025-01-20 11:20:46'),
(5, 'midwife', 'midwife@gmail.com', '$2y$10$KbXMVUXyPFxaSmbQA6c5Le52iMyIrzjHuVUdIvixZdoDkerh1zEiS', '09123456789', 'Admin', '2025-01-23 07:45:34'),
(6, 'user', 'email@gmail.com', '$2y$10$Plx0hsqlzHCfQLpTB05YGeCE/Ota11fgopRHz6QKF6Zc/ZWvpLaTG', '09883123415', 'Patient', '2025-03-01 14:16:09'),
(7, 'ascasc', 'test@gmail.com', '$2y$10$TUQQxGhho4UgU/r2ISfRY.F.kjfsMoimumcqxH87IDkoTh7gA/DY.', 'ascasc', 'Patient', '2025-03-01 16:19:25'),
(8, 'Test1', 'Test1@gmail.com', '$2y$10$twBThCFx7Ecu3QEsLI4aJ.bHS5G5u0TqeywWUdQXCijEJmazlF5p2', '091231241241', 'Patient', '2025-03-01 16:23:08'),
(9, 'TestUlit', 'testing@gmail.com', '$2y$10$a/DFi.pHujh3wHSuJVZqeuvdCNm1KLOflSppvQLG./TFfbpfBxL2u', '09123456789', 'Patient', '2025-03-01 16:25:00'),
(10, 'jenlee', 'hong@gmail.com', '$2y$10$.mqlZ0Cndz3DxSvQwUKM7Oe88je.U.gOsqOxkNAQeXhSCRi39TZyS', '0912312412', 'Patient', '2025-03-01 17:53:15'),
(11, 'testuser', 'usertest@gmail.com', '$2y$10$hK73kRB1YzSOJOYu/wbbhOVEcoAWOjm9w6I0uK/DctT9KVUAJPqHu', '09123456789', 'Patient', '2025-03-02 13:50:02'),
(12, 'testuser2', 'usertest2@gmail.com', '$2y$10$vLOnDGGVpmxi4vx/sPJ6ieY8u4EviYE3k.v58JSVCfroQRssHBBBW', '09123456789', 'Patient', '2025-03-02 13:55:23'),
(13, 'testuser3', 'usertest4@gmail.com', '$2y$10$529NT7uU9QuZp.TcE/B5U.UWUlxeiTJUERRmETEfEYjFrlVmAV9eG', '09123456789', 'Patient', '2025-03-02 13:56:16'),
(14, 'sophia1', 'sophia@gmail.com', '$2y$10$EitkgTBKgYoclqMH3V346OyssYWfzlo4SXFKVURP76fIFRnNT1i7q', '09123456789', 'Patient', '2025-03-02 14:19:06'),
(15, 'albert', 'albert2@gmail.com', '$2y$10$IwvCA3IYLAwUNfhZNf2Jb.qqZsNQtgoxET3pP4ZvSfX.WOKbxXKzy', '09123456789', 'Patient', '2025-03-02 14:26:51'),
(16, 'gray', 'gray@gmail.com', '$2y$10$pUBZZGWxae6/MURnyGoh8OdTi.nU8lF/xYNW5zeiDyqJeW3TK85yW', '09123456789', 'Patient', '2025-03-02 14:53:48'),
(17, 'jordan', 'jordan@gmail.com', '$2y$10$YhORNfD.argY9uQ6cQIOquXqgbGsBGmEDpXHNdWhLiJaO2R.23dd.', '09123456789', 'Patient', '2025-03-02 14:59:13'),
(18, 'jordy', 'jordyy@gmail.com', '$2y$10$aGb9krYuf4ieAgf2uBDpKuV75UnR5zG/JQg6HQCvHpqJ4uDplYt6a', '09123456789', 'Patient', '2025-03-02 15:08:09'),
(19, 'johndoe123', 'johndoe@example.com', '$2y$10$v3lJPKccPfe1ZeGpPIHlKOvvvPTtQoMuPvAJ7G0DvYXeOXQhiSw5W', '09123456789', 'Patient', '2025-03-02 15:19:22'),
(20, 'sarahsmith22', 'sarah.smith@example.com', '$2y$10$z/7N3tGU/bHD8/YmBb9jg.LRsgqdLJzfCEs3ncIv69tW8gHlgiipS', '+639876543210', 'Patient', '2025-03-02 15:22:14'),
(21, 'mikejohnson89', 'mike.johnson@example.com', '$2y$10$EP01GGzpNLl/V8bVVHgNjOh.bqPVwVgqGHHucZvbhF7RwPuT0FiWa', '+639234567890', 'Patient', '2025-03-02 15:29:44'),
(22, 'emilybrown77', 'emily.brown@example.com', '$2y$10$OoziWNZB2Ayu5ERAQNw0l.JS2mDyAR3rdFybGZApSC9pCKXdbyC1u', '+639567890123', 'Patient', '2025-03-02 15:35:39');

-- --------------------------------------------------------

--
-- Table structure for table `vaccination_records`
--

CREATE TABLE `vaccination_records` (
  `record_id` int(11) NOT NULL,
  `appointment_id` int(11) DEFAULT NULL,
  `transaction_id` int(11) DEFAULT NULL,
  `case_id` varchar(20) DEFAULT NULL,
  `vaccine_name` varchar(100) DEFAULT NULL,
  `dose_number` int(11) DEFAULT NULL,
  `batch_number` varchar(50) DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `site_of_injection` varchar(100) DEFAULT NULL,
  `adverse_reactions` text DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `vaccination_records`
--

INSERT INTO `vaccination_records` (`record_id`, `appointment_id`, `transaction_id`, `case_id`, `vaccine_name`, `dose_number`, `batch_number`, `expiry_date`, `site_of_injection`, `adverse_reactions`, `remarks`, `created_at`) VALUES
(2, 218, NULL, NULL, 'test', 123, '123', '2028-11-18', '123', '123', '123', '2025-05-09 19:54:23'),
(3, 219, NULL, 'C006', 'test', 123, '123', '2026-06-10', '123', 'qwe', 'qwe', '2025-05-09 19:56:52'),
(6, NULL, 74, 'C006', 'test-may10', 12, '123', '2027-01-02', 'asd', 'asd', 'asd', '2025-05-09 20:03:08'),
(7, NULL, 75, 'C006', 'testngakung nagana', 123, '123', '2029-12-23', 'asd', 'asd', 'asdqweqwe', '2025-05-09 20:53:01');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `admissions`
--
ALTER TABLE `admissions`
  ADD PRIMARY KEY (`admission_id`),
  ADD KEY `patient_id` (`patient_id`);

--
-- Indexes for table `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`appointment_id`),
  ADD KEY `patient_id` (`patient_id`);

--
-- Indexes for table `billing_header`
--
ALTER TABLE `billing_header`
  ADD PRIMARY KEY (`billing_id`),
  ADD KEY `idx_case_id` (`case_id`),
  ADD KEY `idx_transaction_id` (`transaction_id`);

--
-- Indexes for table `billing_items`
--
ALTER TABLE `billing_items`
  ADD PRIMARY KEY (`billing_item_id`),
  ADD KEY `idx_billing_id` (`billing_id`),
  ADD KEY `idx_item_id` (`item_id`);

--
-- Indexes for table `circumcision_consent`
--
ALTER TABLE `circumcision_consent`
  ADD PRIMARY KEY (`id`),
  ADD KEY `circumcision_consent_ibfk_1` (`case_id`),
  ADD KEY `circumcision_consent_ibfk_2` (`transaction_id`);

--
-- Indexes for table `follow_up_records`
--
ALTER TABLE `follow_up_records`
  ADD PRIMARY KEY (`record_id`),
  ADD KEY `appointment_id` (`appointment_id`);

--
-- Indexes for table `health_records`
--
ALTER TABLE `health_records`
  ADD PRIMARY KEY (`record_id`),
  ADD KEY `case_id` (`case_id`),
  ADD KEY `admission_id` (`admission_id`),
  ADD KEY `prenatal_record_id` (`prenatal_record_id`),
  ADD KEY `transaction_id` (`transaction_id`),
  ADD KEY `appointment_id` (`appointment_id`),
  ADD KEY `service_id` (`service_id`);

--
-- Indexes for table `hemoglobin`
--
ALTER TABLE `hemoglobin`
  ADD PRIMARY KEY (`id`),
  ADD KEY `hemoglobin_ibfk_1` (`case_id`),
  ADD KEY `hemoglobin_ibfk_2` (`transaction_id`);

--
-- Indexes for table `inventory`
--
ALTER TABLE `inventory`
  ADD PRIMARY KEY (`item_id`);

--
-- Indexes for table `logs`
--
ALTER TABLE `logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `medical_consultation_records`
--
ALTER TABLE `medical_consultation_records`
  ADD PRIMARY KEY (`record_id`),
  ADD KEY `appointment_id` (`appointment_id`);

--
-- Indexes for table `medical_services`
--
ALTER TABLE `medical_services`
  ADD PRIMARY KEY (`service_id`);

--
-- Indexes for table `medical_transactions`
--
ALTER TABLE `medical_transactions`
  ADD PRIMARY KEY (`transaction_id`),
  ADD KEY `fk_case_id_medical_transactions` (`case_id`),
  ADD KEY `FK_service_id` (`service_id`);

--
-- Indexes for table `ob_ultrasound`
--
ALTER TABLE `ob_ultrasound`
  ADD PRIMARY KEY (`id`),
  ADD KEY `case_id` (`case_id`),
  ADD KEY `transaction_id` (`transaction_id`);

--
-- Indexes for table `pap_smear`
--
ALTER TABLE `pap_smear`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pap_smear_ibfk_1` (`case_id`),
  ADD KEY `pap_smear_ibfk_2` (`transaction_id`);

--
-- Indexes for table `patients`
--
ALTER TABLE `patients`
  ADD PRIMARY KEY (`patient_id`),
  ADD UNIQUE KEY `case_id` (`case_id`),
  ADD KEY `fk_patients_users` (`user_id`);

--
-- Indexes for table `postnatal_records`
--
ALTER TABLE `postnatal_records`
  ADD PRIMARY KEY (`record_id`),
  ADD KEY `postnatal_records_ibfk_1` (`patient_id`),
  ADD KEY `fk_postnatal_transaction` (`transaction_id`),
  ADD KEY `fk_postnatal_appointment` (`appointment_id`);

--
-- Indexes for table `prenatal_records`
--
ALTER TABLE `prenatal_records`
  ADD PRIMARY KEY (`record_id`),
  ADD KEY `patient_id` (`patient_id`),
  ADD KEY `appointment_id` (`appointment_id`),
  ADD KEY `transaction_id` (`transaction_id`);

--
-- Indexes for table `professional_fees`
--
ALTER TABLE `professional_fees`
  ADD PRIMARY KEY (`professional_fee_id`);

--
-- Indexes for table `regular_checkup_records`
--
ALTER TABLE `regular_checkup_records`
  ADD PRIMARY KEY (`record_id`),
  ADD KEY `appointment_id` (`appointment_id`);

--
-- Indexes for table `rooms`
--
ALTER TABLE `rooms`
  ADD PRIMARY KEY (`room_id`),
  ADD KEY `fk_room_case` (`case_id`);

--
-- Indexes for table `staff`
--
ALTER TABLE `staff`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `staff_id` (`staff_id`),
  ADD KEY `idx_staff_staff_id` (`staff_id`),
  ADD KEY `idx_staff_role` (`role`),
  ADD KEY `idx_staff_status` (`status`);

--
-- Indexes for table `stocks`
--
ALTER TABLE `stocks`
  ADD PRIMARY KEY (`stocks_id`),
  ADD KEY `item_id` (`item_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `tv_ultrasound`
--
ALTER TABLE `tv_ultrasound`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_ultrasound_case` (`case_id`),
  ADD KEY `fk_ultrasound_transaction` (`transaction_id`);

--
-- Indexes for table `under_observation_records`
--
ALTER TABLE `under_observation_records`
  ADD PRIMARY KEY (`record_id`),
  ADD KEY `appointment_id` (`appointment_id`);

--
-- Indexes for table `urinalysis`
--
ALTER TABLE `urinalysis`
  ADD PRIMARY KEY (`id`),
  ADD KEY `urinalysis_ibfk_1` (`case_id`),
  ADD KEY `urinalysis_ibfk_2` (`transaction_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `vaccination_records`
--
ALTER TABLE `vaccination_records`
  ADD PRIMARY KEY (`record_id`),
  ADD KEY `appointment_id` (`appointment_id`),
  ADD KEY `transaction_id` (`transaction_id`),
  ADD KEY `case_id` (`case_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_log`
--
ALTER TABLE `activity_log`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=218;

--
-- AUTO_INCREMENT for table `admissions`
--
ALTER TABLE `admissions`
  MODIFY `admission_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `appointment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=222;

--
-- AUTO_INCREMENT for table `billing_header`
--
ALTER TABLE `billing_header`
  MODIFY `billing_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `billing_items`
--
ALTER TABLE `billing_items`
  MODIFY `billing_item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=59;

--
-- AUTO_INCREMENT for table `circumcision_consent`
--
ALTER TABLE `circumcision_consent`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `follow_up_records`
--
ALTER TABLE `follow_up_records`
  MODIFY `record_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `health_records`
--
ALTER TABLE `health_records`
  MODIFY `record_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `hemoglobin`
--
ALTER TABLE `hemoglobin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `inventory`
--
ALTER TABLE `inventory`
  MODIFY `item_id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `logs`
--
ALTER TABLE `logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=397;

--
-- AUTO_INCREMENT for table `medical_consultation_records`
--
ALTER TABLE `medical_consultation_records`
  MODIFY `record_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `medical_services`
--
ALTER TABLE `medical_services`
  MODIFY `service_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `medical_transactions`
--
ALTER TABLE `medical_transactions`
  MODIFY `transaction_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=76;

--
-- AUTO_INCREMENT for table `ob_ultrasound`
--
ALTER TABLE `ob_ultrasound`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `pap_smear`
--
ALTER TABLE `pap_smear`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `patients`
--
ALTER TABLE `patients`
  MODIFY `patient_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT for table `postnatal_records`
--
ALTER TABLE `postnatal_records`
  MODIFY `record_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `prenatal_records`
--
ALTER TABLE `prenatal_records`
  MODIFY `record_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `professional_fees`
--
ALTER TABLE `professional_fees`
  MODIFY `professional_fee_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `regular_checkup_records`
--
ALTER TABLE `regular_checkup_records`
  MODIFY `record_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `rooms`
--
ALTER TABLE `rooms`
  MODIFY `room_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `staff`
--
ALTER TABLE `staff`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `stocks`
--
ALTER TABLE `stocks`
  MODIFY `stocks_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `tv_ultrasound`
--
ALTER TABLE `tv_ultrasound`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `under_observation_records`
--
ALTER TABLE `under_observation_records`
  MODIFY `record_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `urinalysis`
--
ALTER TABLE `urinalysis`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `vaccination_records`
--
ALTER TABLE `vaccination_records`
  MODIFY `record_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD CONSTRAINT `activity_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `admissions`
--
ALTER TABLE `admissions`
  ADD CONSTRAINT `admissions_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `appointments`
--
ALTER TABLE `appointments`
  ADD CONSTRAINT `appointments_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `billing_header`
--
ALTER TABLE `billing_header`
  ADD CONSTRAINT `fk_billing_header_case` FOREIGN KEY (`case_id`) REFERENCES `patients` (`case_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_billing_header_transaction` FOREIGN KEY (`transaction_id`) REFERENCES `medical_transactions` (`transaction_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `billing_items`
--
ALTER TABLE `billing_items`
  ADD CONSTRAINT `fk_billing_items_billing` FOREIGN KEY (`billing_id`) REFERENCES `billing_header` (`billing_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_billing_items_inventory` FOREIGN KEY (`item_id`) REFERENCES `inventory` (`item_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `circumcision_consent`
--
ALTER TABLE `circumcision_consent`
  ADD CONSTRAINT `circumcision_consent_ibfk_1` FOREIGN KEY (`case_id`) REFERENCES `patients` (`case_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `circumcision_consent_ibfk_2` FOREIGN KEY (`transaction_id`) REFERENCES `medical_transactions` (`transaction_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `follow_up_records`
--
ALTER TABLE `follow_up_records`
  ADD CONSTRAINT `follow_up_records_ibfk_1` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`appointment_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `health_records`
--
ALTER TABLE `health_records`
  ADD CONSTRAINT `health_records_ibfk_1` FOREIGN KEY (`case_id`) REFERENCES `patients` (`case_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `health_records_ibfk_2` FOREIGN KEY (`admission_id`) REFERENCES `admissions` (`admission_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `health_records_ibfk_3` FOREIGN KEY (`prenatal_record_id`) REFERENCES `prenatal_records` (`record_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `health_records_ibfk_4` FOREIGN KEY (`transaction_id`) REFERENCES `medical_transactions` (`transaction_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `health_records_ibfk_5` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`appointment_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `health_records_ibfk_6` FOREIGN KEY (`service_id`) REFERENCES `medical_services` (`service_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `hemoglobin`
--
ALTER TABLE `hemoglobin`
  ADD CONSTRAINT `hemoglobin_ibfk_1` FOREIGN KEY (`case_id`) REFERENCES `patients` (`case_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `hemoglobin_ibfk_2` FOREIGN KEY (`transaction_id`) REFERENCES `medical_transactions` (`transaction_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `logs`
--
ALTER TABLE `logs`
  ADD CONSTRAINT `logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `medical_consultation_records`
--
ALTER TABLE `medical_consultation_records`
  ADD CONSTRAINT `medical_consultation_records_ibfk_1` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`appointment_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `medical_transactions`
--
ALTER TABLE `medical_transactions`
  ADD CONSTRAINT `FK_service_id` FOREIGN KEY (`service_id`) REFERENCES `medical_services` (`service_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_case_id_medical_transactions` FOREIGN KEY (`case_id`) REFERENCES `patients` (`case_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `ob_ultrasound`
--
ALTER TABLE `ob_ultrasound`
  ADD CONSTRAINT `ob_ultrasound_ibfk_1` FOREIGN KEY (`case_id`) REFERENCES `patients` (`case_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `ob_ultrasound_ibfk_2` FOREIGN KEY (`transaction_id`) REFERENCES `medical_transactions` (`transaction_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `pap_smear`
--
ALTER TABLE `pap_smear`
  ADD CONSTRAINT `pap_smear_ibfk_1` FOREIGN KEY (`case_id`) REFERENCES `patients` (`case_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `pap_smear_ibfk_2` FOREIGN KEY (`transaction_id`) REFERENCES `medical_transactions` (`transaction_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `patients`
--
ALTER TABLE `patients`
  ADD CONSTRAINT `fk_patients_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `postnatal_records`
--
ALTER TABLE `postnatal_records`
  ADD CONSTRAINT `fk_postnatal_appointment` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`appointment_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_postnatal_transaction` FOREIGN KEY (`transaction_id`) REFERENCES `medical_transactions` (`transaction_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `postnatal_records_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `prenatal_records`
--
ALTER TABLE `prenatal_records`
  ADD CONSTRAINT `prenatal_records_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `prenatal_records_ibfk_2` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`appointment_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `prenatal_records_ibfk_3` FOREIGN KEY (`transaction_id`) REFERENCES `medical_transactions` (`transaction_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `regular_checkup_records`
--
ALTER TABLE `regular_checkup_records`
  ADD CONSTRAINT `regular_checkup_records_ibfk_1` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`appointment_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `rooms`
--
ALTER TABLE `rooms`
  ADD CONSTRAINT `fk_room_case` FOREIGN KEY (`case_id`) REFERENCES `patients` (`case_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `stocks`
--
ALTER TABLE `stocks`
  ADD CONSTRAINT `stocks_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `inventory` (`item_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `stocks_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `tv_ultrasound`
--
ALTER TABLE `tv_ultrasound`
  ADD CONSTRAINT `fk_ultrasound_case` FOREIGN KEY (`case_id`) REFERENCES `patients` (`case_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ultrasound_transaction` FOREIGN KEY (`transaction_id`) REFERENCES `medical_transactions` (`transaction_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `under_observation_records`
--
ALTER TABLE `under_observation_records`
  ADD CONSTRAINT `under_observation_records_ibfk_1` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`appointment_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `urinalysis`
--
ALTER TABLE `urinalysis`
  ADD CONSTRAINT `urinalysis_ibfk_1` FOREIGN KEY (`case_id`) REFERENCES `patients` (`case_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `urinalysis_ibfk_2` FOREIGN KEY (`transaction_id`) REFERENCES `medical_transactions` (`transaction_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `vaccination_records`
--
ALTER TABLE `vaccination_records`
  ADD CONSTRAINT `vaccination_records_ibfk_1` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`appointment_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `vaccination_records_ibfk_2` FOREIGN KEY (`transaction_id`) REFERENCES `medical_transactions` (`transaction_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `vaccination_records_ibfk_3` FOREIGN KEY (`case_id`) REFERENCES `patients` (`case_id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
