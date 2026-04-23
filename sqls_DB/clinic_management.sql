-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 23, 2026 at 03:55 PM
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
-- Database: `clinic_management`
--

-- --------------------------------------------------------

--
-- Table structure for table `ai_logs`
--

CREATE TABLE `ai_logs` (
  `id` int(11) NOT NULL,
  `session_id` varchar(255) DEFAULT NULL,
  `action` varchar(50) DEFAULT NULL,
  `input_text` longtext DEFAULT NULL,
  `output_text` longtext DEFAULT NULL,
  `confidence_score` decimal(3,2) DEFAULT NULL,
  `processing_time_ms` int(11) DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

CREATE TABLE `appointments` (
  `appointment_id` int(11) NOT NULL,
  `patient_id` int(11) DEFAULT NULL,
  `doctor_id` int(11) DEFAULT NULL,
  `appointment_date` date NOT NULL,
  `appointment_time` time NOT NULL,
  `appointment_serial` int(11) DEFAULT NULL,
  `status` enum('scheduled','completed','cancelled','no-show') DEFAULT 'scheduled',
  `payment_status` enum('pending','paid','partial','refunded') DEFAULT 'pending',
  `payment_method` varchar(50) DEFAULT NULL,
  `amount_paid` decimal(10,2) DEFAULT 0.00,
  `payment_notes` text DEFAULT NULL,
  `payment_date` datetime DEFAULT NULL,
  `consultation_fee` decimal(10,2) DEFAULT 0.00,
  `urgency_level` enum('low','normal','high','emergency') DEFAULT 'normal',
  `estimated_duration` int(11) DEFAULT 30,
  `diagnosis` text DEFAULT NULL,
  `prescription` text DEFAULT NULL,
  `is_admitted` tinyint(1) DEFAULT 0,
  `admission_notes` text DEFAULT NULL,
  `admission_date` datetime DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `consultation_type` enum('general','follow-up','emergency','routine') DEFAULT 'general',
  `symptoms` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `appointments`
--

INSERT INTO `appointments` (`appointment_id`, `patient_id`, `doctor_id`, `appointment_date`, `appointment_time`, `appointment_serial`, `status`, `payment_status`, `payment_method`, `amount_paid`, `payment_notes`, `payment_date`, `consultation_fee`, `urgency_level`, `estimated_duration`, `diagnosis`, `prescription`, `is_admitted`, `admission_notes`, `admission_date`, `notes`, `consultation_type`, `symptoms`, `created_at`, `updated_at`) VALUES
(2, 2, 2, '2024-01-16', '10:30:00', NULL, 'scheduled', 'pending', NULL, 0.00, NULL, NULL, 0.00, 'normal', 30, NULL, NULL, 0, NULL, NULL, 'Skin allergy consultation', 'general', NULL, '2025-11-23 06:48:28', '2025-11-23 09:16:12'),
(3, 3, 3, '2024-01-17', '14:00:00', NULL, 'completed', 'pending', NULL, 0.00, NULL, NULL, 0.00, 'normal', 30, NULL, NULL, 0, NULL, NULL, 'Child vaccination', 'routine', NULL, '2025-11-23 06:48:28', '2025-11-23 09:16:12'),
(4, 4, 3, '2025-11-24', '10:00:00', NULL, 'scheduled', 'pending', NULL, 0.00, NULL, NULL, 0.00, 'normal', 30, NULL, NULL, 0, NULL, NULL, 'none', '', 'pain', '2025-11-23 09:47:56', '2025-11-23 09:47:56'),
(5, 4, 3, '2025-11-25', '09:00:00', NULL, 'scheduled', 'pending', NULL, 0.00, NULL, NULL, 0.00, 'normal', 30, NULL, NULL, 0, NULL, NULL, 'dg', '', 'tgfhdg', '2025-11-23 10:48:26', '2025-11-23 10:48:26'),
(6, 4, 1, '2025-11-26', '10:00:00', NULL, 'scheduled', 'pending', NULL, 0.00, NULL, NULL, 0.00, 'normal', 30, NULL, NULL, 0, NULL, NULL, 'Cold issues', 'emergency', 'Back Pain', '2025-11-24 06:04:26', '2025-11-24 06:04:26'),
(9, 5, 4, '2025-11-28', '10:00:00', NULL, 'scheduled', 'pending', NULL, 0.00, NULL, NULL, 0.00, 'normal', 30, NULL, NULL, 0, NULL, NULL, 'None', 'emergency', 'Back pain', '2025-11-24 10:55:26', '2025-11-24 10:55:26'),
(11, 5, 4, '2025-12-05', '10:00:00', NULL, 'scheduled', 'pending', NULL, 0.00, NULL, NULL, 0.00, 'normal', 30, NULL, NULL, 0, NULL, NULL, 'none', '', 'pain', '2025-11-24 11:34:29', '2025-11-24 11:34:29'),
(12, 4, 1, '2026-04-29', '10:15:00', NULL, 'scheduled', 'pending', NULL, 0.00, NULL, NULL, 0.00, 'normal', 30, NULL, NULL, 0, NULL, NULL, '', '', 'https://github.com/ziabul2/Clinic-Appointment-System', '2026-04-23 13:27:01', '2026-04-23 13:27:01');

-- --------------------------------------------------------

--
-- Table structure for table `appointment_counters`
--

CREATE TABLE `appointment_counters` (
  `id` int(11) NOT NULL,
  `date` date NOT NULL,
  `last_serial` int(11) NOT NULL DEFAULT 0,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `chat_messages`
--

CREATE TABLE `chat_messages` (
  `id` int(11) NOT NULL,
  `session_id` varchar(255) NOT NULL,
  `message` longtext NOT NULL,
  `sender` varchar(20) DEFAULT NULL,
  `message_type` varchar(50) DEFAULT NULL,
  `confidence_score` decimal(3,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `chat_messages`
--

INSERT INTO `chat_messages` (`id`, `session_id`, `message`, `sender`, `message_type`, `confidence_score`, `created_at`) VALUES
(1, 'mca2h92luu275pog8qjgagmqg1', '', 'user', NULL, NULL, '2025-11-25 07:34:10'),
(2, 'mca2h92luu275pog8qjgagmqg1', 'Upload Prescription', 'user', NULL, NULL, '2025-11-25 07:34:19'),
(3, 'mca2h92luu275pog8qjgagmqg1', 'hi', 'user', NULL, NULL, '2025-11-25 07:34:22'),
(4, 'mca2h92luu275pog8qjgagmqg1', 'Health Tips', 'user', NULL, NULL, '2025-11-25 07:34:33'),
(5, 'mca2h92luu275pog8qjgagmqg1', '', 'user', NULL, NULL, '2025-11-25 07:36:49'),
(6, 'mca2h92luu275pog8qjgagmqg1', 'Upload Prescription', 'user', NULL, NULL, '2025-11-25 07:36:53'),
(7, 'mca2h92luu275pog8qjgagmqg1', 'Schedule Follow-up', 'user', NULL, NULL, '2025-11-25 07:36:57'),
(8, 'mca2h92luu275pog8qjgagmqg1', 'Health Tips', 'user', NULL, NULL, '2025-11-25 07:36:58');

-- --------------------------------------------------------

--
-- Table structure for table `chat_sessions`
--

CREATE TABLE `chat_sessions` (
  `id` int(11) NOT NULL,
  `session_id` varchar(100) DEFAULT NULL,
  `patient_id` int(11) DEFAULT NULL,
  `current_step` varchar(50) DEFAULT NULL,
  `symptoms_data` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `chat_sessions`
--

INSERT INTO `chat_sessions` (`id`, `session_id`, `patient_id`, `current_step`, `symptoms_data`, `created_at`, `updated_at`) VALUES
(1, 'mca2h92luu275pog8qjgagmqg1', NULL, 'followup', '[{\"symptom\":\"Headache\",\"type\":\"main\",\"confidence\":0.95,\"severity\":\"\\ud83d\\udea8 Emergency - Need immediate care\"},{\"symptom\":\"Sore throat\",\"type\":\"additional\",\"confidence\":0.7}]', '2025-11-25 05:42:53', '2025-11-25 07:29:13');

-- --------------------------------------------------------

--
-- Table structure for table `consultation_history`
--

CREATE TABLE `consultation_history` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) DEFAULT NULL,
  `doctor_id` int(11) DEFAULT NULL,
  `main_symptom` varchar(255) DEFAULT NULL,
  `additional_symptoms` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`additional_symptoms`)),
  `severity` varchar(20) DEFAULT NULL,
  `recommended_specialty` varchar(100) DEFAULT NULL,
  `ai_confidence` decimal(3,2) DEFAULT NULL,
  `consultation_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `doctors`
--

CREATE TABLE `doctors` (
  `doctor_id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `specialization` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `license_number` varchar(50) DEFAULT NULL,
  `available_days` set('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday') DEFAULT NULL,
  `consultation_fee` decimal(8,2) DEFAULT NULL,
  `available_time_start` time DEFAULT NULL,
  `available_time_end` time DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `doctors`
--

INSERT INTO `doctors` (`doctor_id`, `first_name`, `last_name`, `specialization`, `email`, `phone`, `license_number`, `available_days`, `consultation_fee`, `available_time_start`, `available_time_end`, `profile_picture`, `created_at`) VALUES
(1, 'Dr. Sarah', 'Wilson', 'Cardiology', 'sarah.wilson@clinic.com', '111-222-3333', 'MED12345', 'Monday,Wednesday,Friday', 50.00, '09:00:00', '17:00:00', NULL, '2025-11-23 06:48:28'),
(2, 'Dr. David', 'Brown', 'Dermatology', 'david.brown@clinic.com', '111-222-4444', 'MED12346', 'Tuesday,Thursday,Saturday', 45.00, '10:00:00', '16:00:00', NULL, '2025-11-23 06:48:28'),
(3, 'Dr. Emily', 'Davis', 'Pediatrics', 'emily.davis@clinic.com', '111-222-5555', 'MED12347', 'Monday,Tuesday,Wednesday,Thursday,Friday', 40.00, '08:00:00', '15:00:00', NULL, '2025-11-23 06:48:28'),
(4, 'Ziabul islam', 'zim', 'Pain', 'ziabulislam2222@gmail.com', '01581205088', 'BD2217', 'Friday', 12000.00, '09:00:00', '17:00:00', 'doc_1763975495_6f7aba421a77.jpg', '2025-11-24 09:11:35');

-- --------------------------------------------------------

--
-- Table structure for table `doctor_specialties`
--

CREATE TABLE `doctor_specialties` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `doctor_specialties`
--

INSERT INTO `doctor_specialties` (`id`, `name`, `description`) VALUES
(1, 'Cardiology', 'Specialist in heart and blood vessels'),
(2, 'Dermatology', 'Specialist in skin conditions'),
(3, 'Pediatrics', 'Specialist in children health'),
(4, 'Pain Management', 'Specialist in pain management'),
(5, 'General Physician', 'Primary care for general health issues'),
(6, 'Neurology', 'Specialist in nervous system'),
(7, 'Orthopedics', 'Specialist in bones and joints'),
(8, 'Gastroenterology', 'Specialist in digestive system'),
(9, 'Cardiology', 'Specialist in heart and blood vessels'),
(10, 'Dermatology', 'Specialist in skin conditions'),
(11, 'Pediatrics', 'Specialist in children health'),
(12, 'Pain Management', 'Specialist in pain management'),
(13, 'General Physician', 'Primary care for general health issues'),
(14, 'Neurology', 'Specialist in nervous system'),
(15, 'Orthopedics', 'Specialist in bones and joints'),
(16, 'Gastroenterology', 'Specialist in digestive system');

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` varchar(128) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `password_reset_tokens`
--

INSERT INTO `password_reset_tokens` (`id`, `user_id`, `token`, `expires_at`, `used_at`, `created_at`) VALUES
(1, 4, '7b22ba49812ec608ce4b80e88e84790d3be70f4f1f157c58839a0423564f7dac', '2025-11-25 11:55:31', NULL, '2025-11-24 05:55:31'),
(2, 5, '584e663fabce06baea30610c58207ccdb78a71c733b050777f370b9c93e9410d', '2025-11-25 11:55:36', NULL, '2025-11-24 05:55:36'),
(3, 6, '5218920178e483dab7c328cb80dc911cd1bcc7fefe547e13c6378ca82477d143', '2025-11-25 11:55:41', NULL, '2025-11-24 05:55:41'),
(4, 7, '5faffa4772e4af44299c96f9d440f204ca7e23553109d8448ac8fc46e30b63cf', '2025-11-25 12:01:54', NULL, '2025-11-24 06:01:54'),
(5, 8, '2ee381349b7740d3d8569da4343cc36fb67c1d79e98d9f856a5b2018ca71de36', '2025-11-25 14:11:20', NULL, '2025-11-24 08:11:20'),
(6, 9, '446c1c4f87f7a1ad4df21b4c96ed0df148dcd3de66f34fe2ab73b86ad429bac1', '2025-11-25 15:11:35', '2025-11-24 16:09:00', '2025-11-24 09:11:35'),
(7, 3, '4c088d0aa456955c271448909f45c728050aa50b09515fa6dc6011d38b12d60c', '2025-11-25 15:25:17', NULL, '2025-11-24 09:25:17'),
(8, 8, '698c6edd1d53ae2356eb238f7fd849a6d79984fe48a4c19053ecffc99c512b38', '2025-11-24 17:07:15', NULL, '2025-11-24 10:07:15');

-- --------------------------------------------------------

--
-- Table structure for table `patients`
--

CREATE TABLE `patients` (
  `patient_id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('Male','Female','Other') DEFAULT NULL,
  `emergency_contact` varchar(15) DEFAULT NULL,
  `allergies` text DEFAULT NULL,
  `medical_history` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `patients`
--

INSERT INTO `patients` (`patient_id`, `first_name`, `last_name`, `email`, `phone`, `address`, `date_of_birth`, `gender`, `emergency_contact`, `allergies`, `medical_history`, `created_at`, `updated_at`) VALUES
(2, 'Jane', 'Smith', 'jane.smith@email.com', '123-456-7891', '456 Oak Ave, City, State', '1990-08-22', 'Female', '123-456-7892', NULL, NULL, '2025-11-23 06:48:28', '2025-11-23 06:48:28'),
(3, 'Mike', 'Johnson', 'mike.johnson@email.com', '123-456-7892', '789 Pine Rd, City, State', '1978-12-10', 'Male', '123-456-7893', NULL, NULL, '2025-11-23 06:48:28', '2025-11-23 06:48:28'),
(4, 'Ziabul islam', 'zim', 'zim@google.com', '0152154911', 'Thanapara, Lalmonirhat', '2025-11-24', 'Male', '015584236455', NULL, 'Back pain', '2025-11-23 07:51:18', '2025-11-23 07:51:18'),
(5, 'ziabul', 'islam', 'ziabulislam2222@gmail.com', '01581205088', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-24 08:11:20', '2025-11-24 08:11:20');

-- --------------------------------------------------------

--
-- Table structure for table `prescriptions`
--

CREATE TABLE `prescriptions` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `extracted_text` longtext DEFAULT NULL,
  `analysis_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`analysis_data`)),
  `notes` text DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `analysis_date` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `symptoms`
--

CREATE TABLE `symptoms` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `symptoms`
--

INSERT INTO `symptoms` (`id`, `name`, `description`) VALUES
(1, 'Headache', 'Pain in head or neck area'),
(2, 'Fever', 'Elevated body temperature'),
(3, 'Cough', 'Respiratory symptom with throat irritation'),
(4, 'Chest pain', 'Pain in chest area'),
(5, 'Stomach pain', 'Abdominal discomfort'),
(6, 'Skin rash', 'Skin irritation or redness'),
(7, 'Joint pain', 'Pain in joints'),
(8, 'Back pain', 'Pain in back area'),
(9, 'Sore throat', 'Pain or irritation in throat'),
(10, 'Shortness of breath', 'Difficulty breathing'),
(11, 'Dizziness', 'Feeling lightheaded or unsteady'),
(12, 'Nausea', 'Feeling of sickness with urge to vomit'),
(13, 'Fatigue', 'Extreme tiredness'),
(14, 'Muscle pain', 'Pain in muscles'),
(15, 'Headache', 'Pain in head or neck area'),
(16, 'Fever', 'Elevated body temperature'),
(17, 'Cough', 'Respiratory symptom with throat irritation'),
(18, 'Chest pain', 'Pain in chest area'),
(19, 'Stomach pain', 'Abdominal discomfort'),
(20, 'Skin rash', 'Skin irritation or redness'),
(21, 'Joint pain', 'Pain in joints'),
(22, 'Back pain', 'Pain in back area'),
(23, 'Sore throat', 'Pain or irritation in throat'),
(24, 'Shortness of breath', 'Difficulty breathing'),
(25, 'Dizziness', 'Feeling lightheaded or unsteady'),
(26, 'Nausea', 'Feeling of sickness with urge to vomit'),
(27, 'Fatigue', 'Extreme tiredness'),
(28, 'Muscle pain', 'Pain in muscles');

-- --------------------------------------------------------

--
-- Table structure for table `symptom_specialty_mapping`
--

CREATE TABLE `symptom_specialty_mapping` (
  `id` int(11) NOT NULL,
  `symptom_id` int(11) DEFAULT NULL,
  `specialty_id` int(11) DEFAULT NULL,
  `priority` int(11) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `symptom_specialty_mapping`
--

INSERT INTO `symptom_specialty_mapping` (`id`, `symptom_id`, `specialty_id`, `priority`) VALUES
(1, 4, 1, 1),
(2, 10, 1, 1),
(3, 11, 1, 2),
(4, 6, 2, 1),
(5, 2, 3, 2),
(6, 3, 3, 2),
(7, 8, 4, 1),
(8, 7, 4, 1),
(9, 14, 4, 1),
(10, 1, 4, 2),
(11, 2, 5, 1),
(12, 3, 5, 1),
(13, 1, 5, 1),
(14, 9, 5, 1),
(15, 12, 5, 1),
(16, 13, 5, 1),
(17, 1, 6, 1),
(18, 11, 6, 1),
(19, 7, 7, 1),
(20, 8, 7, 2),
(21, 14, 7, 2),
(22, 5, 8, 1),
(23, 12, 8, 1),
(24, 4, 1, 1),
(25, 4, 9, 1),
(26, 18, 1, 1),
(27, 18, 9, 1),
(31, 10, 1, 1),
(32, 10, 9, 1),
(33, 24, 1, 1),
(34, 24, 9, 1),
(38, 11, 1, 2),
(39, 11, 9, 2),
(40, 25, 1, 2),
(41, 25, 9, 2),
(45, 6, 2, 1),
(46, 6, 10, 1),
(47, 20, 2, 1),
(48, 20, 10, 1),
(52, 2, 3, 2),
(53, 2, 11, 2),
(54, 16, 3, 2),
(55, 16, 11, 2),
(59, 3, 3, 2),
(60, 3, 11, 2),
(61, 17, 3, 2),
(62, 17, 11, 2),
(66, 8, 4, 1),
(67, 8, 12, 1),
(68, 22, 4, 1),
(69, 22, 12, 1),
(73, 7, 4, 1),
(74, 7, 12, 1),
(75, 21, 4, 1),
(76, 21, 12, 1),
(80, 14, 4, 1),
(81, 14, 12, 1),
(82, 28, 4, 1),
(83, 28, 12, 1),
(87, 1, 4, 2),
(88, 1, 12, 2),
(89, 15, 4, 2),
(90, 15, 12, 2),
(94, 2, 5, 1),
(95, 2, 13, 1),
(96, 16, 5, 1),
(97, 16, 13, 1),
(101, 3, 5, 1),
(102, 3, 13, 1),
(103, 17, 5, 1),
(104, 17, 13, 1),
(108, 1, 5, 1),
(109, 1, 13, 1),
(110, 15, 5, 1),
(111, 15, 13, 1),
(115, 9, 5, 1),
(116, 9, 13, 1),
(117, 23, 5, 1),
(118, 23, 13, 1),
(122, 12, 5, 1),
(123, 12, 13, 1),
(124, 26, 5, 1),
(125, 26, 13, 1),
(129, 13, 5, 1),
(130, 13, 13, 1),
(131, 27, 5, 1),
(132, 27, 13, 1),
(136, 1, 6, 1),
(137, 1, 14, 1),
(138, 15, 6, 1),
(139, 15, 14, 1),
(143, 11, 6, 1),
(144, 11, 14, 1),
(145, 25, 6, 1),
(146, 25, 14, 1),
(150, 7, 7, 1),
(151, 7, 15, 1),
(152, 21, 7, 1),
(153, 21, 15, 1),
(157, 8, 7, 2),
(158, 8, 15, 2),
(159, 22, 7, 2),
(160, 22, 15, 2),
(164, 14, 7, 2),
(165, 14, 15, 2),
(166, 28, 7, 2),
(167, 28, 15, 2),
(171, 5, 8, 1),
(172, 5, 16, 1),
(173, 19, 8, 1),
(174, 19, 16, 1),
(178, 12, 8, 1),
(179, 12, 16, 1),
(180, 26, 8, 1),
(181, 26, 16, 1);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `first_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `role` enum('admin','receptionist','doctor','patient') DEFAULT 'receptionist',
  `doctor_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password`, `email`, `first_name`, `last_name`, `profile_picture`, `role`, `doctor_id`, `created_at`) VALUES
(1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@clinic.com', NULL, NULL, 'usr_1776952354_06c435db3ae3.jpg', 'admin', NULL, '2025-11-23 06:48:28'),
(2, 'reception', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'reception@clinic.com', NULL, NULL, NULL, 'receptionist', NULL, '2025-11-23 06:48:28'),
(3, 'zim', '121212', 'zim@me.com', NULL, NULL, NULL, 'receptionist', NULL, '2025-11-23 07:25:28'),
(4, 'jdoe', '$2y$10$V6VIqskRVBwsuWzn5I21/e8IsmZXTcUTp6Ccm6hWC.k6oDrSPvJkW', 'john.doe@example.com', NULL, NULL, NULL, 'doctor', 1, '2025-11-24 05:55:31'),
(5, 'asmith', '$2y$10$uhv/1FApBCKVbYRGVdXKMOt4lUg0OGZ082Na6RW3vUgQdWQXRM0ey', 'anna.smith@example.com', NULL, NULL, NULL, 'receptionist', NULL, '2025-11-24 05:55:36'),
(6, 'drbrown', '$2y$10$gJrXnXCdo.Xn.PLn63Oge.yEtQeHEg6hjGvCcyeXhHL1JI2I7IQPi', 'dr.brown@example.com', NULL, NULL, NULL, 'doctor', 2, '2025-11-24 05:55:41'),
(7, 'drziabul', '$2y$10$atHpAweyVy8VDauEleb69./QEKN.41BBVqGE0fS2G0.J46xIGQyma', 'ziabul@duck.com', NULL, NULL, NULL, 'doctor', 3, '2025-11-24 06:01:54'),
(8, 'ziabulislam222292', '$2y$10$821VfnI1y0a8gNS5rvqZTuq65GlbKPwVoRUPJu086gkUUL4beBs/S', 'ziabulislam2222@gmail.com', NULL, NULL, NULL, 'patient', NULL, '2025-11-24 08:11:20'),
(9, 'ziabulislam222243', '$2y$10$6jqOmy0wSYtLoh763E/Fl.6SwS72vD8Df.xV6I6s0MDS2z2xCAdD6', 'ziabulislam2222@gmail.com', NULL, NULL, NULL, 'doctor', 4, '2025-11-24 09:11:35');

-- --------------------------------------------------------

--
-- Table structure for table `waiting_list`
--

CREATE TABLE `waiting_list` (
  `waiting_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `status` enum('waiting','taken','processed','cancelled') DEFAULT 'waiting',
  `requested_at` datetime NOT NULL,
  `taken_by` int(11) DEFAULT NULL,
  `appointment_id` int(11) DEFAULT NULL,
  `token` varchar(128) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `ai_logs`
--
ALTER TABLE `ai_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_session_id` (`session_id`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`appointment_id`),
  ADD UNIQUE KEY `unique_doctor_timeslot` (`doctor_id`,`appointment_date`,`appointment_time`),
  ADD KEY `patient_id` (`patient_id`);

--
-- Indexes for table `appointment_counters`
--
ALTER TABLE `appointment_counters`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `date` (`date`);

--
-- Indexes for table `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_session_id` (`session_id`),
  ADD KEY `idx_sender` (`sender`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `chat_sessions`
--
ALTER TABLE `chat_sessions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `session_id` (`session_id`);

--
-- Indexes for table `consultation_history`
--
ALTER TABLE `consultation_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_patient_id` (`patient_id`),
  ADD KEY `idx_doctor_id` (`doctor_id`),
  ADD KEY `idx_consultation_date` (`consultation_date`);

--
-- Indexes for table `doctors`
--
ALTER TABLE `doctors`
  ADD PRIMARY KEY (`doctor_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `license_number` (`license_number`);

--
-- Indexes for table `doctor_specialties`
--
ALTER TABLE `doctor_specialties`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `token` (`token`);

--
-- Indexes for table `patients`
--
ALTER TABLE `patients`
  ADD PRIMARY KEY (`patient_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `prescriptions`
--
ALTER TABLE `prescriptions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_patient_id` (`patient_id`),
  ADD KEY `idx_uploaded_at` (`uploaded_at`);

--
-- Indexes for table `symptoms`
--
ALTER TABLE `symptoms`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `symptom_specialty_mapping`
--
ALTER TABLE `symptom_specialty_mapping`
  ADD PRIMARY KEY (`id`),
  ADD KEY `symptom_id` (`symptom_id`),
  ADD KEY `specialty_id` (`specialty_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `fk_users_doctor` (`doctor_id`);

--
-- Indexes for table `waiting_list`
--
ALTER TABLE `waiting_list`
  ADD PRIMARY KEY (`waiting_id`),
  ADD KEY `patient_id` (`patient_id`),
  ADD KEY `status` (`status`),
  ADD KEY `requested_at` (`requested_at`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `ai_logs`
--
ALTER TABLE `ai_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `appointment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `appointment_counters`
--
ALTER TABLE `appointment_counters`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `chat_messages`
--
ALTER TABLE `chat_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `chat_sessions`
--
ALTER TABLE `chat_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `consultation_history`
--
ALTER TABLE `consultation_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `doctors`
--
ALTER TABLE `doctors`
  MODIFY `doctor_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `doctor_specialties`
--
ALTER TABLE `doctor_specialties`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `patients`
--
ALTER TABLE `patients`
  MODIFY `patient_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `prescriptions`
--
ALTER TABLE `prescriptions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `symptoms`
--
ALTER TABLE `symptoms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `symptom_specialty_mapping`
--
ALTER TABLE `symptom_specialty_mapping`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=182;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `waiting_list`
--
ALTER TABLE `waiting_list`
  MODIFY `waiting_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `appointments`
--
ALTER TABLE `appointments`
  ADD CONSTRAINT `appointments_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `appointments_ibfk_2` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`doctor_id`) ON DELETE CASCADE;

--
-- Constraints for table `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD CONSTRAINT `chat_messages_ibfk_1` FOREIGN KEY (`session_id`) REFERENCES `chat_sessions` (`session_id`);

--
-- Constraints for table `symptom_specialty_mapping`
--
ALTER TABLE `symptom_specialty_mapping`
  ADD CONSTRAINT `symptom_specialty_mapping_ibfk_1` FOREIGN KEY (`symptom_id`) REFERENCES `symptoms` (`id`),
  ADD CONSTRAINT `symptom_specialty_mapping_ibfk_2` FOREIGN KEY (`specialty_id`) REFERENCES `doctor_specialties` (`id`);

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`doctor_id`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
