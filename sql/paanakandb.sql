-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 28, 2025 at 01:36 PM
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

--
-- Dumping data for table `admissions`
--

INSERT INTO `admissions` (`admission_id`, `patient_id`, `admission_date`, `discharge_date`, `admitting_physician`, `admitting_diagnosis`, `discharge_diagnosis`, `discharge_condition`, `disposition`, `complications`, `surgical_procedure`, `pathological_report`) VALUES
(1, 1, '2025-01-25 00:52:00', '2025-01-25 00:52:00', 'Dr. Hakdog', 'Wait lang', 'Di pa alam', 'Improved', 'Discharged', NULL, NULL, NULL),
(2, 3, '2025-01-10 09:30:00', '2025-01-12 15:00:00', 'Dr. Henry Lopez', 'Severe Preeclampsia', 'Improved', 'Improved', 'Discharged', NULL, 'Cesarean Section', 'Normal post-surgery findings'),
(3, 4, '2025-01-18 11:00:00', '2025-01-21 10:00:00', 'Dr. Lucy Reyes', 'Pneumonia', 'Recovered', 'Recovered', 'Discharged', NULL, NULL, 'Pathological tests normal');

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

CREATE TABLE `appointments` (
  `appointment_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `scheduled_date` datetime NOT NULL,
  `completed_date` datetime DEFAULT NULL,
  `status` enum('Done','Ongoing','Missed') DEFAULT 'Ongoing',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `appointments`
--

INSERT INTO `appointments` (`appointment_id`, `patient_id`, `scheduled_date`, `completed_date`, `status`, `created_at`) VALUES
(1, 1, '2025-01-25 09:00:00', '2025-01-24 15:47:34', 'Done', '2025-01-23 02:19:19'),
(2, 2, '2025-01-26 10:00:00', '2025-01-24 15:47:34', 'Done', '2025-01-23 02:19:19'),
(3, 3, '2025-01-27 11:00:00', NULL, 'Missed', '2025-01-23 02:19:19'),
(4, 4, '2025-01-28 08:30:00', '2025-01-24 15:47:35', 'Done', '2025-01-23 02:19:19'),
(5, 5, '2025-01-29 14:00:00', NULL, 'Missed', '2025-01-23 02:19:19'),
(6, 1, '2025-01-10 15:12:00', '2025-01-24 08:39:32', 'Done', '2025-01-23 07:13:48'),
(7, 2, '2025-01-15 15:14:00', '2025-01-24 08:42:18', 'Done', '2025-01-23 07:14:12'),
(8, 3, '2025-01-23 15:14:00', NULL, 'Missed', '2025-01-23 07:14:38'),
(9, 4, '2025-01-24 15:14:00', '2025-01-24 08:43:41', 'Done', '2025-01-23 07:14:58'),
(10, 4, '2025-01-23 07:18:00', '2025-01-24 08:42:15', 'Done', '2025-01-23 07:17:33'),
(11, 3, '2025-01-23 07:25:00', '2025-01-24 08:42:20', 'Done', '2025-01-23 07:21:23'),
(12, 1, '2025-01-23 19:35:00', NULL, 'Missed', '2025-01-23 07:33:51'),
(13, 5, '2025-01-23 09:33:00', NULL, 'Missed', '2025-01-23 07:34:01'),
(15, 4, '2025-01-24 11:05:00', '2025-01-24 08:42:22', 'Done', '2025-01-24 03:05:02'),
(16, 4, '2025-01-24 11:05:00', NULL, 'Missed', '2025-01-24 03:05:07'),
(17, 2, '2025-01-24 11:05:00', NULL, 'Missed', '2025-01-24 03:05:13'),
(18, 4, '2025-01-24 11:05:00', '2025-01-24 08:42:12', 'Done', '2025-01-24 03:05:27'),
(19, 4, '2025-01-24 11:31:00', '2025-01-24 08:42:23', 'Done', '2025-01-24 03:31:14'),
(20, 7, '2025-01-24 12:17:00', NULL, 'Missed', '2025-01-24 04:17:08'),
(21, 1, '2025-01-24 13:18:00', NULL, 'Missed', '2025-01-24 05:18:34'),
(22, 7, '2025-01-24 13:21:00', NULL, 'Missed', '2025-01-24 05:21:20'),
(23, 2, '2025-01-24 13:21:00', '2025-01-24 08:42:26', 'Done', '2025-01-24 05:21:27'),
(27, 7, '2025-01-24 14:56:00', NULL, 'Missed', '2025-01-24 06:56:14'),
(28, 7, '2025-01-24 15:22:00', '2025-01-24 15:47:29', 'Done', '2025-01-24 07:22:51'),
(29, 7, '2025-01-24 15:22:00', '2025-01-24 15:47:23', 'Done', '2025-01-24 07:22:54'),
(30, 7, '2025-01-24 15:22:00', '2025-01-24 08:44:20', 'Done', '2025-01-24 07:22:58'),
(31, 7, '2025-01-24 15:23:00', '2025-01-24 15:47:33', 'Done', '2025-01-24 07:23:02'),
(32, 7, '2025-01-24 15:23:00', NULL, 'Missed', '2025-01-24 07:23:05'),
(33, 7, '2025-01-24 15:23:00', '2025-01-24 08:42:30', 'Done', '2025-01-24 07:23:08'),
(34, 7, '2025-01-24 15:23:00', '2025-01-24 15:47:30', 'Done', '2025-01-24 07:23:11'),
(35, 7, '2025-01-24 15:23:00', '2025-01-24 08:44:24', 'Done', '2025-01-24 07:23:15'),
(36, 7, '2025-01-24 15:48:00', '2025-01-25 09:16:58', 'Done', '2025-01-24 07:48:28'),
(37, 7, '2025-01-06 16:06:00', '2025-01-24 19:21:53', 'Done', '2025-01-24 08:06:35'),
(38, 4, '2025-01-24 16:07:00', NULL, 'Missed', '2025-01-24 08:07:42'),
(39, 2, '2025-01-24 16:07:00', NULL, 'Missed', '2025-01-24 08:07:50'),
(40, 5, '2025-01-24 16:08:00', '2025-01-25 09:17:03', 'Done', '2025-01-24 08:08:09'),
(41, 4, '2025-01-24 19:03:00', NULL, 'Ongoing', '2025-01-24 11:03:56'),
(42, 3, '2025-01-24 19:04:00', NULL, 'Ongoing', '2025-01-24 11:04:07'),
(43, 4, '2025-01-24 19:04:00', NULL, 'Ongoing', '2025-01-24 11:04:15'),
(44, 4, '2025-01-24 19:04:00', NULL, 'Ongoing', '2025-01-24 11:04:21'),
(45, 1, '2025-01-24 19:18:00', '2025-01-24 19:18:33', 'Done', '2025-01-24 11:18:26'),
(46, 1, '2025-01-24 19:21:00', NULL, 'Ongoing', '2025-01-24 11:21:22'),
(47, 7, '2025-01-25 09:15:00', NULL, 'Ongoing', '2025-01-25 01:15:35'),
(48, 7, '2025-01-25 09:16:00', NULL, 'Ongoing', '2025-01-25 01:16:26');

-- --------------------------------------------------------

--
-- Table structure for table `billing`
--

CREATE TABLE `billing` (
  `billing_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `services_rendered` text DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `payment_status` enum('Pending','Paid') DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `health_records`
--

CREATE TABLE `health_records` (
  `record_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `diagnosis` text NOT NULL,
  `results` text DEFAULT NULL,
  `prescribed_medicine` text DEFAULT NULL,
  `follow_up_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `health_records`
--

INSERT INTO `health_records` (`record_id`, `patient_id`, `diagnosis`, `results`, `prescribed_medicine`, `follow_up_date`, `created_at`) VALUES
(1, 1, 'Gestational Hypertension', 'Monitoring blood pressure, recommended bed rest', 'Labetalol', '2025-02-15', '2025-01-23 02:37:31'),
(2, 2, 'Type 2 Diabetes', 'Blood sugar control, diet and exercise', 'Metformin 500mg', '2025-03-01', '2025-01-23 02:37:31'),
(3, 3, 'Normal Pregnancy', 'Routine check-up, monitoring fetal heart rate', 'Prenatal vitamins', '2025-02-10', '2025-01-23 02:37:31'),
(4, 4, 'Bronchial Asthma', 'Use inhaler as prescribed, avoid triggers', 'Salbutamol Inhaler', '2025-02-20', '2025-01-23 02:37:31'),
(5, 5, 'No current diagnosis', 'Routine health check-up, no issues found', 'None', '2025-02-25', '2025-01-23 02:37:31'),
(6, 7, 'Buntis', 'asd', 'asda', '2025-01-25', '2025-01-24 04:05:05'),
(7, 7, 'asd', 'asd', 'asd', '2025-01-25', '2025-01-24 04:05:17'),
(8, 7, 'asd', 'asd', 'cvascxazczxc', '2025-01-04', '2025-01-24 04:19:31'),
(9, 1, 'Pogi si Sir Robin', '', '', '2025-01-24', '2025-01-24 05:19:01'),
(10, 7, 'Pogi si Sir Robin', 'Supot lang', '', NULL, '2025-01-24 05:21:45'),
(11, 7, 'fbdfghgfe', 'fkhkfkkhkf', 'fefgf', '2025-01-02', '2025-01-24 05:23:26');

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
(93, 1, 'Login Successful', '2025-01-27 04:43:55');

-- --------------------------------------------------------

--
-- Table structure for table `medicine_supplies`
--

CREATE TABLE `medicine_supplies` (
  `id` int(11) NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `unit` varchar(50) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `date_added` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `medicine_supplies`
--

INSERT INTO `medicine_supplies` (`id`, `item_name`, `category`, `unit`, `price`, `date_added`) VALUES
(1, 'Ciprofloxacin 500mg', 'Antibiotics', 'Box (10 pcs)	', 110.00, '2025-01-24 10:24:05');

-- --------------------------------------------------------

--
-- Table structure for table `patients`
--

CREATE TABLE `patients` (
  `patient_id` int(11) NOT NULL,
  `case_id` varchar(20) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `gender` enum('Male','Female') NOT NULL,
  `date_of_birth` date NOT NULL,
  `contact_number` varchar(15) NOT NULL,
  `address` text DEFAULT NULL,
  `medical_history` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `patients`
--

INSERT INTO `patients` (`patient_id`, `case_id`, `first_name`, `last_name`, `gender`, `date_of_birth`, `contact_number`, `address`, `medical_history`, `created_at`) VALUES
(1, 'C001', 'Jenlee', 'Hong', 'Female', '1990-05-15', '09171234567', '123 Main Street, Calapan', NULL, '2025-01-23 02:19:09'),
(2, 'C002', 'John', 'Smith', 'Male', '1985-03-22', '09183456789', '456 Elm Street, Calapan', NULL, '2025-01-23 02:19:09'),
(3, 'C003', 'Alice', 'Johnson', 'Female', '1992-07-30', '09176543210', '789 Oak Avenue, Calapan', NULL, '2025-01-23 02:19:09'),
(4, 'C004', 'Mark', 'Brown', 'Male', '1988-11-11', '09185678912', '101 Pine Road, Calapan', NULL, '2025-01-23 02:19:09'),
(5, 'C005', 'Emma', 'Wilson', 'Female', '1995-12-05', '09196789034', '202 Maple Lane, Calapan', NULL, '2025-01-23 02:19:09'),
(7, 'C006', 'John Paul', 'Baes', 'Male', '2025-01-24', '09123456789', 'as', '', '2025-01-24 03:55:03');

-- --------------------------------------------------------

--
-- Table structure for table `prenatal_records`
--

CREATE TABLE `prenatal_records` (
  `record_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
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

INSERT INTO `prenatal_records` (`record_id`, `patient_id`, `visit_date`, `attending_physician`, `gravida`, `para`, `ob_score`, `lmp`, `aog_by_lmp`, `edc_by_lmp`, `aog_by_usg`, `edc_by_usg`, `blood_pressure`, `weight`, `temperature`, `respiratory_rate`, `fundal_height`, `fetal_heart_tones`, `internal_examination`, `chief_complaint`, `history_of_present_illness`, `past_medical_history`, `past_social_history`, `family_history`, `tt_dose`, `plan`, `lab_results`) VALUES
(1, 1, '2025-01-05 10:00:00', 'Dr. Anna Cruz', 1, 0, 'G1P0', '2024-12-15', 6.50, '2025-09-22', 6.70, '2025-09-24', '120/80', 70.50, 37.20, 18, 28.00, 140, 'No abnormalities detected.', 'Routine check-up', 'No complaints.', NULL, NULL, 'No significant family history.', 'TT1', 'Continue prenatal vitamins.', 'Normal'),
(2, 3, '2025-01-07 09:00:00', 'Dr. Henry Lopez', 2, 1, 'G2P1', '2024-11-30', 8.20, '2025-08-15', 8.50, '2025-08-17', '130/85', 65.00, 37.50, 16, 30.00, 145, 'Pelvic exam normal.', 'Occasional nausea.', 'History of mild anemia.', NULL, NULL, 'Mother has diabetes.', 'TT2', 'Prescribe iron supplements.', 'Normal'),
(3, 4, '2025-01-11 14:30:00', 'Dr. Lucy Reyes', 3, 2, 'G3P2', '2024-10-25', 12.00, '2025-07-20', 12.20, '2025-07-22', '125/80', 75.20, 36.80, 17, 34.00, 150, 'No abnormalities detected.', 'Routine prenatal check-up.', 'History of asthma.', NULL, NULL, 'No significant family history.', 'TT3', 'Continue prenatal supplements.', 'Normal');

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
(5, 'midwife', 'midwife@gmail.com', '$2y$10$KbXMVUXyPFxaSmbQA6c5Le52iMyIrzjHuVUdIvixZdoDkerh1zEiS', '09123456789', 'Admin', '2025-01-23 07:45:34');

--
-- Indexes for dumped tables
--

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
-- Indexes for table `billing`
--
ALTER TABLE `billing`
  ADD PRIMARY KEY (`billing_id`),
  ADD KEY `patient_id` (`patient_id`);

--
-- Indexes for table `health_records`
--
ALTER TABLE `health_records`
  ADD PRIMARY KEY (`record_id`),
  ADD KEY `patient_id` (`patient_id`);

--
-- Indexes for table `logs`
--
ALTER TABLE `logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `medicine_supplies`
--
ALTER TABLE `medicine_supplies`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `patients`
--
ALTER TABLE `patients`
  ADD PRIMARY KEY (`patient_id`),
  ADD UNIQUE KEY `case_id` (`case_id`);

--
-- Indexes for table `prenatal_records`
--
ALTER TABLE `prenatal_records`
  ADD PRIMARY KEY (`record_id`),
  ADD KEY `patient_id` (`patient_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admissions`
--
ALTER TABLE `admissions`
  MODIFY `admission_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `appointment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT for table `billing`
--
ALTER TABLE `billing`
  MODIFY `billing_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `health_records`
--
ALTER TABLE `health_records`
  MODIFY `record_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `logs`
--
ALTER TABLE `logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=94;

--
-- AUTO_INCREMENT for table `medicine_supplies`
--
ALTER TABLE `medicine_supplies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `patients`
--
ALTER TABLE `patients`
  MODIFY `patient_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `prenatal_records`
--
ALTER TABLE `prenatal_records`
  MODIFY `record_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admissions`
--
ALTER TABLE `admissions`
  ADD CONSTRAINT `admissions_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`) ON DELETE CASCADE;

--
-- Constraints for table `appointments`
--
ALTER TABLE `appointments`
  ADD CONSTRAINT `appointments_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`);

--
-- Constraints for table `billing`
--
ALTER TABLE `billing`
  ADD CONSTRAINT `billing_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`);

--
-- Constraints for table `health_records`
--
ALTER TABLE `health_records`
  ADD CONSTRAINT `health_records_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`);

--
-- Constraints for table `logs`
--
ALTER TABLE `logs`
  ADD CONSTRAINT `logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `prenatal_records`
--
ALTER TABLE `prenatal_records`
  ADD CONSTRAINT `prenatal_records_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
