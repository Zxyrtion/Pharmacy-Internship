-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 16, 2026 at 03:12 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `internship`
--

-- --------------------------------------------------------

--
-- Table structure for table `employee_profile`
--

CREATE TABLE `employee_profile` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `interview_id` int(11) NOT NULL,
  `employee_id` varchar(50) NOT NULL,
  `position` varchar(100) NOT NULL DEFAULT 'Intern',
  `department` varchar(100) NOT NULL DEFAULT 'Pharmacy',
  `start_date` date DEFAULT NULL,
  `status` enum('Active','On Leave','Terminated','Completed') NOT NULL DEFAULT 'Active',
  `supervisor_id` int(11) DEFAULT NULL,
  `salary` decimal(10,2) DEFAULT NULL,
  `performance_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `internship_applications`
--

CREATE TABLE `internship_applications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `application_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('pending','under_review','approved','rejected') NOT NULL DEFAULT 'pending',
  `review_notes` text DEFAULT NULL,
  `reviewed_by` int(11) DEFAULT NULL,
  `review_date` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `internship_applications`
--

INSERT INTO `internship_applications` (`id`, `user_id`, `application_date`, `status`, `review_notes`, `reviewed_by`, `review_date`, `created_at`, `updated_at`) VALUES
(6, 16, '2026-04-15 06:33:25', 'approved', 'Approved for internship position', NULL, NULL, '2026-04-15 06:33:25', '2026-04-15 06:33:25');

-- --------------------------------------------------------

--
-- Table structure for table `internship_records`
--

CREATE TABLE `internship_records` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `first_name` varchar(255) NOT NULL,
  `last_name` varchar(255) NOT NULL,
  `date_of_birth` date DEFAULT NULL,
  `is_enrolled_higher_ed` tinyint(1) DEFAULT 0,
  `higher_ed_institution` varchar(255) DEFAULT NULL,
  `enrollment_certificate_path` varchar(255) DEFAULT NULL,
  `enrollment_certificate_status` enum('pending','valid','invalid') DEFAULT 'pending',
  `enrollment_certificate_remarks` text DEFAULT NULL,
  `is_enrolled_internship_subject` tinyint(1) DEFAULT 0,
  `is_at_least_18` tinyint(1) DEFAULT 0,
  `has_passed_pre_internship` tinyint(1) DEFAULT 0,
  `recommendation_letter_path` varchar(255) DEFAULT NULL,
  `recommendation_letter_status` enum('pending','valid','invalid') DEFAULT 'pending',
  `recommendation_letter_remarks` text DEFAULT NULL,
  `medical_certificate_submitted` tinyint(1) DEFAULT 0,
  `medical_certificate_path` varchar(255) DEFAULT NULL,
  `medical_certificate_status` enum('pending','valid','invalid') DEFAULT 'pending',
  `medical_certificate_remarks` text DEFAULT NULL,
  `parental_consent_submitted` tinyint(1) DEFAULT 0,
  `parental_consent_path` varchar(255) DEFAULT NULL,
  `parental_consent_status` enum('pending','valid','invalid') DEFAULT 'pending',
  `parental_consent_remarks` text DEFAULT NULL,
  `is_eligible` tinyint(1) DEFAULT 0,
  `application_status` enum('pending','submitted','under_review','approved','rejected') DEFAULT 'pending',
  `internship_start_date` date DEFAULT NULL,
  `internship_duration` varchar(100) DEFAULT NULL,
  `working_days` varchar(100) DEFAULT NULL,
  `working_hours` varchar(100) DEFAULT NULL,
  `special_instructions` text DEFAULT NULL,
  `pharmacy_name` varchar(255) DEFAULT NULL,
  `pharmacy_address` text DEFAULT NULL,
  `contact_person` varchar(255) DEFAULT NULL,
  `contact_number` varchar(50) DEFAULT NULL,
  `contact_email` varchar(255) DEFAULT NULL,
  `schedule_sent` tinyint(1) DEFAULT 0,
  `interview_scheduled` tinyint(1) DEFAULT 0,
  `interview_date` date DEFAULT NULL,
  `interview_time` time DEFAULT NULL,
  `interview_type` enum('personal','online') DEFAULT NULL,
  `interview_location` varchar(500) DEFAULT NULL,
  `interview_meeting_link` varchar(500) DEFAULT NULL,
  `interview_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `internship_records`
--

INSERT INTO `internship_records` (`id`, `user_id`, `first_name`, `last_name`, `date_of_birth`, `is_enrolled_higher_ed`, `higher_ed_institution`, `enrollment_certificate_path`, `enrollment_certificate_status`, `enrollment_certificate_remarks`, `is_enrolled_internship_subject`, `is_at_least_18`, `has_passed_pre_internship`, `recommendation_letter_path`, `recommendation_letter_status`, `recommendation_letter_remarks`, `medical_certificate_submitted`, `medical_certificate_path`, `medical_certificate_status`, `medical_certificate_remarks`, `parental_consent_submitted`, `parental_consent_path`, `parental_consent_status`, `parental_consent_remarks`, `is_eligible`, `application_status`, `internship_start_date`, `internship_duration`, `working_days`, `working_hours`, `special_instructions`, `pharmacy_name`, `pharmacy_address`, `contact_person`, `contact_number`, `contact_email`, `schedule_sent`, `interview_scheduled`, `interview_date`, `interview_time`, `interview_type`, `interview_location`, `interview_meeting_link`, `interview_notes`, `created_at`, `updated_at`) VALUES
(27, 16, 'Dave', 'Dela Cerna', '2004-03-14', 1, 'Davao Central college', 'enrollment_certificate_1776158127.pdf', 'valid', '', 1, 1, 1, 'recommendation_letter_1776158132.pdf', 'valid', '', 1, 'medical_certificate_1776158137.pdf', 'valid', '', 1, 'parental_consent_1776158142.pdf', 'valid', '', 1, 'approved', '2026-04-14', '480 hours', 'Monday - Friday', '8:00 am - 5:00 pm', 'Bring Phone', 'main', 'toril davao city', 'Jenny', '115645656', 'hr@gmai.com', 1, 1, '2026-04-14', '17:31:00', 'online', '', 'https://www.facebook.com/messenger_media?attachment_id=1657052178637779&message_id=mid.%24gAANwqRoOqUuju72blWdinpZYwAlu&thread_id=968296519084363', 'HAHAH', '2026-04-14 09:15:15', '2026-04-14 09:29:14'),
(31, 22, 'jenny', 'Mahinay', '2004-08-22', 1, 'dafadas', 'enrollment_certificate_1776245203.pdf', 'valid', '', 1, 1, 1, 'recommendation_letter_1776245208.pdf', 'valid', '', 1, 'medical_certificate_1776245213.pdf', 'valid', '', 1, 'parental_consent_1776245218.png', 'valid', '', 1, 'approved', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 1, '2026-04-20', '08:27:00', 'online', '', 'https://www.facebook.com/', 'be sadasd', '2026-04-15 09:26:30', '2026-04-15 09:27:32'),
(41, 27, 'Linda', 'Gwapa', '2004-03-16', 1, 'Davao Central college', 'enrollment_certificate_1776300061.jpg', 'valid', '', 1, 1, 1, 'recommendation_letter_1776300068.jpg', 'valid', '', 1, 'medical_certificate_1776300074.jpg', 'valid', '', 1, 'parental_consent_1776300083.jpg', 'valid', '', 1, 'approved', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 1, '2026-04-20', '08:00:00', 'online', '', 'https://www.facebook.com/?should_open_composer=false&wp_discovery_redirect=true&_rdc=1&_rdr#', 'Be on time', '2026-04-16 00:40:51', '2026-04-16 00:43:21');

-- --------------------------------------------------------

--
-- Table structure for table `internship_routine`
--

CREATE TABLE `internship_routine` (
  `id` int(11) NOT NULL,
  `assigned_to` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `duties` text NOT NULL,
  `date_from` date NOT NULL,
  `date_to` date NOT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `user_id` int(11) DEFAULT NULL,
  `assigned_to_user_id` int(11) DEFAULT NULL,
  `report_type` varchar(50) NOT NULL DEFAULT 'task',
  `notes` text DEFAULT NULL,
  `file_original_name` varchar(255) DEFAULT NULL,
  `file_stored_name` varchar(255) DEFAULT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'pending',
  `assigned_by_user_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `internship_routine`
--

INSERT INTO `internship_routine` (`id`, `assigned_to`, `title`, `duties`, `date_from`, `date_to`, `file_path`, `created_at`, `user_id`, `assigned_to_user_id`, `report_type`, `notes`, `file_original_name`, `file_stored_name`, `status`, `assigned_by_user_id`) VALUES
(1, 16, 'orientation', 'hdsbfhdsbf', '2026-04-14', '2026-04-15', '13_69de0cd24af8c0.43599948_final_najud.pdf', '2026-04-14 09:45:54', NULL, NULL, 'task', NULL, NULL, NULL, 'finished', 12),
(2, 16, 'blahblah', 'kjsbckhjabds', '2026-02-07', '2026-02-08', '13_69de0f222c13b3.59056794_final_najud.pdf', '2026-04-14 09:55:46', NULL, NULL, 'task', NULL, NULL, NULL, 'finished', 12),
(3, 16, 'blabla', 'jaafajbf', '2026-04-14', '2026-04-15', '13_69de10dcd7e537.12673749_final_najud.pdf', '2026-04-14 10:03:08', NULL, NULL, 'task', NULL, NULL, NULL, 'finished', 12),
(9, 16, 'Internship Report', 'Tig bantay', '2026-04-13', '2026-04-17', '12_69df328fa26026.96979108_Lesson_4_-_Activity.pdf', '2026-04-15 06:39:11', NULL, NULL, 'task', NULL, NULL, NULL, 'finished', 12),
(10, 16, 'BWHAHAH', 'Tarunga ug wowororrkrok', '2026-04-20', '2026-04-24', '12_69df3d3c215d26.77145562_Successfully_merge.png', '2026-04-15 07:24:44', NULL, NULL, 'task', NULL, NULL, NULL, 'pending', 12),
(11, 16, 'QWeqqwe', 'qweqeqw', '2026-04-15', '2026-04-21', '12_69df3e8d2c7461.62012855_SP101_SemiFinal.pdf', '2026-04-15 07:30:21', NULL, NULL, 'task', NULL, NULL, NULL, 'finished', 12),
(16, 22, 'DASD', 'adasdasdsa', '2026-04-15', '2026-04-30', '12_69df66324fb181.02032665_IAS_102_Final_Requirements.pdf', '2026-04-15 10:19:30', NULL, NULL, 'task', NULL, NULL, NULL, 'finished', 12),
(19, 22, 'adasdadasda', 'asdasdasda', '2026-04-15', '2026-04-28', '12_69df69024a0d33.06929624_DAVE_TDP_Application-Form_-Annex-2__1_.pdf', '2026-04-15 10:31:30', NULL, NULL, 'task', NULL, NULL, NULL, 'finished', NULL),
(20, 23, 'Inventory Report', 'Make it complete and accurate the Inventory', '2026-04-20', '2026-04-24', '12_69dfa681aa7de5.63856159_667565690_1443807717226264_1150848593725429407_n.png', '2026-04-15 14:53:53', NULL, NULL, 'task', NULL, NULL, NULL, 'finished', NULL),
(21, 23, 'DASDAS', 'DASDASD', '2026-04-20', '2026-04-24', '12_69dfa93a6d3521.68868968_665822421_899049549781957_8338108312539498404_n.png', '2026-04-15 15:05:30', NULL, NULL, 'task', NULL, NULL, NULL, 'finished', NULL),
(22, 25, 'dadasd', 'adada', '2026-04-20', '2026-04-29', '12_69dfcda9f1d6f9.70450205_666628166_1477947863721133_5469913423277630833_n.jpg', '2026-04-15 17:40:58', NULL, NULL, 'task', NULL, NULL, NULL, 'finished', NULL),
(23, 27, 'Inventory Sytem', 'DAPAT COMPLETE', '2026-05-04', '2026-05-08', '12_69e032124afd23.00255687_665244865_811163901553238_7512995985228001793_n.webp', '2026-04-16 00:49:22', NULL, NULL, 'task', NULL, NULL, NULL, 'finished', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `intern_tasks`
--

CREATE TABLE `intern_tasks` (
  `id` int(11) NOT NULL,
  `intern_id` int(11) NOT NULL,
  `task_title` varchar(200) NOT NULL,
  `task_description` text DEFAULT NULL,
  `assigned_by` int(11) NOT NULL,
  `assigned_date` datetime NOT NULL DEFAULT current_timestamp(),
  `due_date` datetime NOT NULL,
  `completed_date` datetime DEFAULT NULL,
  `status` enum('Pending','In Progress','Completed','Late') NOT NULL DEFAULT 'Pending',
  `priority` enum('Low','Medium','High','Urgent') DEFAULT 'Medium',
  `category` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `intern_tasks`
--

INSERT INTO `intern_tasks` (`id`, `intern_id`, `task_title`, `task_description`, `assigned_by`, `assigned_date`, `due_date`, `completed_date`, `status`, `priority`, `category`, `notes`, `created_at`, `updated_at`) VALUES
(10, 16, 'Complete Pharmacy Orientation', 'Attend and complete the pharmacy orientation program', 12, '2026-04-16 09:01:17', '2026-04-19 09:01:17', NULL, 'Pending', 'High', 'Training', NULL, '2026-04-16 01:01:17', '2026-04-16 01:01:17'),
(11, 16, 'Shadow Senior Pharmacist', 'Observe and learn from senior pharmacist for 2 days', 12, '2026-04-16 09:01:17', '2026-04-23 09:01:17', NULL, 'Pending', 'Medium', 'Training', NULL, '2026-04-16 01:01:17', '2026-04-16 01:01:17'),
(12, 16, 'Learn Inventory System', 'Complete training on pharmacy inventory management system', 12, '2026-04-16 09:01:17', '2026-04-21 09:01:17', NULL, 'In Progress', 'High', 'Training', NULL, '2026-04-16 01:01:17', '2026-04-16 01:01:17'),
(13, 16, 'Customer Service Training', 'Complete customer service and communication training module', 12, '2026-04-16 09:01:17', '2026-04-14 09:01:17', '2026-04-16 09:01:17', 'Completed', 'Medium', 'Training', NULL, '2026-04-16 01:01:17', '2026-04-16 01:01:17'),
(14, 16, 'Submit Weekly Report', 'Submit your first week internship report', 12, '2026-04-16 09:01:17', '2026-04-15 09:01:17', NULL, 'Pending', 'Urgent', 'Documentation', NULL, '2026-04-16 01:01:17', '2026-04-16 01:01:17'),
(15, 22, 'Complete Pharmacy Orientation', 'Attend and complete the pharmacy orientation program', 12, '2026-04-16 09:01:17', '2026-04-19 09:01:17', NULL, 'Pending', 'High', 'Training', NULL, '2026-04-16 01:01:17', '2026-04-16 01:01:17'),
(16, 22, 'Shadow Senior Pharmacist', 'Observe and learn from senior pharmacist for 2 days', 12, '2026-04-16 09:01:17', '2026-04-23 09:01:17', NULL, 'Pending', 'Medium', 'Training', NULL, '2026-04-16 01:01:17', '2026-04-16 01:01:17'),
(17, 22, 'Learn Inventory System', 'Complete training on pharmacy inventory management system', 12, '2026-04-16 09:01:17', '2026-04-21 09:01:17', NULL, 'In Progress', 'High', 'Training', NULL, '2026-04-16 01:01:17', '2026-04-16 01:01:17'),
(18, 22, 'Customer Service Training', 'Complete customer service and communication training module', 12, '2026-04-16 09:01:17', '2026-04-14 09:01:17', '2026-04-16 09:01:17', 'Completed', 'Medium', 'Training', NULL, '2026-04-16 01:01:17', '2026-04-16 01:01:17'),
(19, 22, 'Submit Weekly Report', 'Submit your first week internship report', 12, '2026-04-16 09:01:17', '2026-04-15 09:01:17', NULL, 'Pending', 'Urgent', 'Documentation', NULL, '2026-04-16 01:01:17', '2026-04-16 01:01:17'),
(20, 27, 'Complete Pharmacy Orientation', 'Attend and complete the pharmacy orientation program', 12, '2026-04-16 09:01:17', '2026-04-19 09:01:17', NULL, 'Pending', 'High', 'Training', NULL, '2026-04-16 01:01:17', '2026-04-16 01:01:17'),
(21, 27, 'Shadow Senior Pharmacist', 'Observe and learn from senior pharmacist for 2 days', 12, '2026-04-16 09:01:17', '2026-04-23 09:01:17', NULL, 'Pending', 'Medium', 'Training', NULL, '2026-04-16 01:01:17', '2026-04-16 01:01:17'),
(22, 27, 'Learn Inventory System', 'Complete training on pharmacy inventory management system', 12, '2026-04-16 09:01:17', '2026-04-21 09:01:17', NULL, 'In Progress', 'High', 'Training', NULL, '2026-04-16 01:01:17', '2026-04-16 01:01:17'),
(23, 27, 'Customer Service Training', 'Complete customer service and communication training module', 12, '2026-04-16 09:01:17', '2026-04-14 09:01:17', '2026-04-16 09:01:17', 'Completed', 'Medium', 'Training', NULL, '2026-04-16 01:01:17', '2026-04-16 01:01:17'),
(24, 27, 'Submit Weekly Report', 'Submit your first week internship report', 12, '2026-04-16 09:01:17', '2026-04-15 09:01:17', NULL, 'Pending', 'Urgent', 'Documentation', NULL, '2026-04-16 01:01:17', '2026-04-16 01:01:17');

-- --------------------------------------------------------

--
-- Table structure for table `interview_assignments`
--

CREATE TABLE `interview_assignments` (
  `id` int(11) NOT NULL,
  `schedule_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `internship_record_id` int(11) NOT NULL,
  `assignment_status` enum('assigned','confirmed','completed','no_show','cancelled') DEFAULT 'assigned',
  `confirmation_date` timestamp NULL DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `interview_assignments`
--

INSERT INTO `interview_assignments` (`id`, `schedule_id`, `user_id`, `internship_record_id`, `assignment_status`, `confirmation_date`, `notes`, `created_at`, `updated_at`) VALUES
(9, 9, 16, 27, 'completed', NULL, NULL, '2026-04-15 06:44:59', '2026-04-15 07:06:35'),
(10, 9, 22, 31, 'completed', NULL, NULL, '2026-04-15 09:27:58', '2026-04-15 09:28:08'),
(14, 11, 27, 41, 'completed', NULL, NULL, '2026-04-16 00:44:16', '2026-04-16 00:44:39');

-- --------------------------------------------------------

--
-- Table structure for table `interview_evaluations`
--

CREATE TABLE `interview_evaluations` (
  `id` int(11) NOT NULL,
  `interview_assignment_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `evaluated_by` int(11) NOT NULL,
  `education_rating` int(11) DEFAULT NULL CHECK (`education_rating` between 1 and 5),
  `education_comments` text DEFAULT NULL,
  `training_rating` int(11) DEFAULT NULL CHECK (`training_rating` between 1 and 5),
  `training_comments` text DEFAULT NULL,
  `work_experience_rating` int(11) DEFAULT NULL CHECK (`work_experience_rating` between 1 and 5),
  `work_experience_comments` text DEFAULT NULL,
  `company_knowledge_rating` int(11) DEFAULT NULL CHECK (`company_knowledge_rating` between 1 and 5),
  `company_knowledge_comments` text DEFAULT NULL,
  `technical_skills_rating` int(11) DEFAULT NULL CHECK (`technical_skills_rating` between 1 and 5),
  `technical_skills_comments` text DEFAULT NULL,
  `multitasking_skills_rating` int(11) DEFAULT NULL CHECK (`multitasking_skills_rating` between 1 and 5),
  `multitasking_skills_comments` text DEFAULT NULL,
  `communication_skills_rating` int(11) DEFAULT NULL CHECK (`communication_skills_rating` between 1 and 5),
  `communication_skills_comments` text DEFAULT NULL,
  `teamwork_rating` int(11) DEFAULT NULL CHECK (`teamwork_rating` between 1 and 5),
  `teamwork_comments` text DEFAULT NULL,
  `stress_tolerance_rating` int(11) DEFAULT NULL CHECK (`stress_tolerance_rating` between 1 and 5),
  `stress_tolerance_comments` text DEFAULT NULL,
  `culture_fit_rating` int(11) DEFAULT NULL CHECK (`culture_fit_rating` between 1 and 5),
  `culture_fit_comments` text DEFAULT NULL,
  `average_rating` decimal(3,2) DEFAULT NULL,
  `overall_evaluation` text DEFAULT NULL,
  `final_decision` enum('pending','accepted','rejected') DEFAULT 'pending',
  `work_schedule_sent` tinyint(1) DEFAULT 0,
  `work_start_date` date DEFAULT NULL,
  `work_schedule_details` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `interview_evaluations`
--

INSERT INTO `interview_evaluations` (`id`, `interview_assignment_id`, `user_id`, `evaluated_by`, `education_rating`, `education_comments`, `training_rating`, `training_comments`, `work_experience_rating`, `work_experience_comments`, `company_knowledge_rating`, `company_knowledge_comments`, `technical_skills_rating`, `technical_skills_comments`, `multitasking_skills_rating`, `multitasking_skills_comments`, `communication_skills_rating`, `communication_skills_comments`, `teamwork_rating`, `teamwork_comments`, `stress_tolerance_rating`, `stress_tolerance_comments`, `culture_fit_rating`, `culture_fit_comments`, `average_rating`, `overall_evaluation`, `final_decision`, `work_schedule_sent`, `work_start_date`, `work_schedule_details`, `created_at`, `updated_at`) VALUES
(8, 9, 16, 12, 5, '', 5, '', 5, '', 5, '', 5, '', 5, '', 5, NULL, 5, '', 5, '', 5, '', 5.00, '0', 'accepted', 1, '2026-04-20', 'Department: Pharmacy Operations\r\nShift: 7 AM - 3 PM\r\n\r\nWeekly Schedule:\r\n- Monday: 7 AM - 3 PM\r\n- Tuesday: 7 AM - 3 PM\r\n- Wednesday: 7 AM - 3 PM\r\n- Thursday: 7 AM - 3 PM\r\n- Friday: 7 AM - 3 PM\r\n- Saturday: OFF\r\n- Sunday: OFF\r\n\r\nSupervisor: Dr jasmine\r\nLocation: Retail Pharmacy\r\n\r\nNotes: Be on time', '2026-04-15 07:06:50', '2026-04-15 07:07:14'),
(12, 10, 22, 12, 5, '', 5, '', 5, '', 5, '', 5, '', 5, '', 5, NULL, 5, '', 5, '', 5, '', 5.00, '0', 'accepted', 1, '2026-04-20', 'Department: Pharmacy Operations\r\nShift: 11 PM - 7 AM\r\n\r\nWeekly Schedule:\r\n- Monday: 11 PM - 7 AM\r\n- Tuesday: 11 PM - 7 AM\r\n- Wednesday: 11 PM - 7 AM\r\n- Thursday: 11 PM - 7 AM\r\n- Friday: 11 PM - 7 AM\r\n- Saturday: 11 PM - 7 AM\r\n- Sunday: 11 PM - 7 AM\r\n\r\nSupervisor: Dr jasmine\r\nLocation: Main Pharmacy Branch\r\n\r\nNotes: adasdasda', '2026-04-15 10:36:06', '2026-04-15 10:36:57'),
(19, 14, 27, 12, 5, 'Excellent', 5, 'Excellent', 5, 'Excellent', 5, 'Excellent', 5, 'Excellent', 5, 'Excellent', 5, NULL, 5, 'Excellent', 5, 'Excellent', 5, 'Excellent', 5.00, '0', 'accepted', 1, '2026-04-27', 'Department: Inventory Management\r\nShift: 7 AM - 3 PM\r\n\r\nWeekly Schedule:\r\n- Monday: 7 AM - 3 PM\r\n- Tuesday: 7 AM - 3 PM\r\n- Wednesday: 7 AM - 3 PM\r\n- Thursday: 7 AM - 3 PM\r\n- Friday: 7 AM - 3 PM\r\n- Saturday: OFF\r\n- Sunday: OFF\r\n\r\nSupervisor: Dr. Maria Santos\r\nLocation: Main Pharmacy Branch\r\n\r\nNotes: Redo the first instructions', '2026-04-16 00:45:17', '2026-04-16 00:47:47');

-- --------------------------------------------------------

--
-- Table structure for table `interview_records`
--

CREATE TABLE `interview_records` (
  `id` int(11) NOT NULL,
  `application_id` int(11) NOT NULL,
  `hr_id` int(11) NOT NULL,
  `interview_date` datetime NOT NULL DEFAULT current_timestamp(),
  `interview_type` varchar(100) NOT NULL DEFAULT 'Initial Interview',
  `questions` text DEFAULT NULL,
  `answers` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `rating` int(11) DEFAULT NULL,
  `outcome` enum('Pending','Recommended','Not Recommended','Hired') NOT NULL DEFAULT 'Pending',
  `feedback` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `interview_schedule`
--

CREATE TABLE `interview_schedule` (
  `id` int(11) NOT NULL,
  `batch_number` int(11) NOT NULL,
  `interview_date` date NOT NULL,
  `interview_time` time NOT NULL,
  `interview_type` enum('personal','online') DEFAULT 'personal',
  `location` varchar(255) DEFAULT NULL,
  `online_meeting_link` varchar(500) DEFAULT NULL,
  `online_meeting_id` varchar(255) DEFAULT NULL,
  `online_meeting_password` varchar(255) DEFAULT NULL,
  `max_slots` int(11) DEFAULT 15,
  `filled_slots` int(11) DEFAULT 0,
  `status` enum('scheduled','ongoing','completed','cancelled') DEFAULT 'scheduled',
  `notes` text DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `interview_schedule`
--

INSERT INTO `interview_schedule` (`id`, `batch_number`, `interview_date`, `interview_time`, `interview_type`, `location`, `online_meeting_link`, `online_meeting_id`, `online_meeting_password`, `max_slots`, `filled_slots`, `status`, `notes`, `created_by`, `created_at`, `updated_at`) VALUES
(9, 1, '2026-04-16', '14:44:00', 'personal', 'Bago Gallera Talomo Davao City', '', '', '', 15, 3, 'scheduled', 'DAADA', 12, '2026-04-15 06:44:55', '2026-04-15 17:53:30'),
(11, 2, '2026-04-20', '08:00:00', 'personal', 'MediCare, Main Office', '', '', '', 4, 2, 'scheduled', 'Davao Central College Students', 12, '2026-04-15 12:47:15', '2026-04-16 00:44:16');

-- --------------------------------------------------------

--
-- Table structure for table `inventory_movements`
--

CREATE TABLE `inventory_movements` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `movement_type` enum('Stock In','Stock Out','Adjustment','Transfer','Expired','Damaged') NOT NULL,
  `quantity` int(11) NOT NULL,
  `previous_quantity` int(11) NOT NULL,
  `new_quantity` int(11) NOT NULL,
  `unit_cost` decimal(10,2) DEFAULT NULL,
  `total_cost` decimal(10,2) DEFAULT NULL,
  `reference_number` varchar(50) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `medicines`
--

CREATE TABLE `medicines` (
  `id` int(11) NOT NULL,
  `medicine_name` varchar(200) NOT NULL,
  `dosage` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `manufacturer` varchar(100) DEFAULT NULL,
  `unit_price` decimal(10,2) DEFAULT 0.00,
  `stock_quantity` int(11) DEFAULT 0,
  `reorder_level` int(11) DEFAULT 10,
  `expiry_date` date DEFAULT NULL,
  `status` enum('Active','Inactive','Discontinued') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `medicines`
--

INSERT INTO `medicines` (`id`, `medicine_name`, `dosage`, `description`, `category`, `manufacturer`, `unit_price`, `stock_quantity`, `reorder_level`, `expiry_date`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Paracetamol', '500mg', 'Pain reliever and fever reducer', 'Analgesic', NULL, 15.50, 100, 20, NULL, 'Active', '2026-04-15 15:30:35', '2026-04-15 15:30:35'),
(2, 'Amoxicillin', '250mg', 'Antibiotic for bacterial infections', 'Antibiotic', NULL, 45.75, 50, 15, NULL, 'Active', '2026-04-15 15:30:35', '2026-04-15 15:30:35'),
(3, 'Ibuprofen', '400mg', 'Anti-inflammatory pain reliever', 'Analgesic', NULL, 25.00, 75, 20, NULL, 'Active', '2026-04-15 15:30:35', '2026-04-15 15:30:35'),
(4, 'Loperamide', '2mg', 'Anti-diarrheal medication', 'Gastrointestinal', NULL, 35.25, 30, 10, NULL, 'Active', '2026-04-15 15:30:35', '2026-04-15 15:30:35'),
(5, 'Salbutamol', '100mcg', 'Bronchodilator for asthma', 'Respiratory', NULL, 120.00, 25, 10, NULL, 'Active', '2026-04-15 15:30:35', '2026-04-15 15:30:35');

-- --------------------------------------------------------

--
-- Table structure for table `moa_agreements`
--

CREATE TABLE `moa_agreements` (
  `id` int(11) NOT NULL,
  `work_schedule_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `moa_content` text NOT NULL,
  `moa_version` varchar(20) DEFAULT '1.0',
  `moa_document_path` varchar(500) DEFAULT NULL,
  `moa_document_name` varchar(255) DEFAULT NULL,
  `signature_file_path` varchar(500) DEFAULT NULL,
  `signature_file_name` varchar(255) DEFAULT NULL,
  `moa_uploaded_at` timestamp NULL DEFAULT NULL,
  `moa_uploaded_by` int(11) DEFAULT NULL,
  `lawyer_name` varchar(255) DEFAULT NULL,
  `lawyer_license_number` varchar(100) DEFAULT NULL,
  `approval_date` date DEFAULT NULL,
  `approval_notes` text DEFAULT NULL,
  `agreement_date` date NOT NULL,
  `start_date` date NOT NULL,
  `department` varchar(100) NOT NULL,
  `supervisor_name` varchar(100) NOT NULL,
  `location` varchar(100) NOT NULL,
  `intern_signature` varchar(255) NOT NULL,
  `intern_full_name` varchar(255) NOT NULL,
  `intern_email` varchar(255) NOT NULL,
  `accepted_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `ip_address` varchar(45) NOT NULL,
  `user_agent` text DEFAULT NULL,
  `agreed_terms` tinyint(1) DEFAULT 1,
  `agreed_confidentiality` tinyint(1) DEFAULT 1,
  `agreed_schedule` tinyint(1) DEFAULT 1,
  `status` enum('active','completed','terminated','cancelled') DEFAULT 'active',
  `termination_date` date DEFAULT NULL,
  `termination_reason` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `moa_agreements`
--

INSERT INTO `moa_agreements` (`id`, `work_schedule_id`, `user_id`, `moa_content`, `moa_version`, `moa_document_path`, `moa_document_name`, `signature_file_path`, `signature_file_name`, `moa_uploaded_at`, `moa_uploaded_by`, `lawyer_name`, `lawyer_license_number`, `approval_date`, `approval_notes`, `agreement_date`, `start_date`, `department`, `supervisor_name`, `location`, `intern_signature`, `intern_full_name`, `intern_email`, `accepted_at`, `ip_address`, `user_agent`, `agreed_terms`, `agreed_confidentiality`, `agreed_schedule`, `status`, `termination_date`, `termination_reason`, `created_at`, `updated_at`) VALUES
(9, 11, 22, 'MEMORANDUM OF AGREEMENT\n\nThis agreement is entered into on April 15, 2026\nBetween: MediCare Pharmacy and jenny Mahinay\n\nDepartment: Pharmacy Operations\nSupervisor: Dr jasmine\nLocation: Main Pharmacy Branch\nStart Date: April 20, 2026\n\nWork Schedule:\nDepartment: Pharmacy Operations\r\nShift: 11 PM - 7 AM\r\n\r\nWeekly Schedule:\r\n- Monday: 11 PM - 7 AM\r\n- Tuesday: 11 PM - 7 AM\r\n- Wednesday: 11 PM - 7 AM\r\n- Thursday: 11 PM - 7 AM\r\n- Friday: 11 PM - 7 AM\r\n- Saturday: 11 PM - 7 AM\r\n- Sunday: 11 PM - 7 AM\r\n\r\nSupervisor: Dr jasmine\r\nLocation: Main Pharmacy Branch\r\n\r\nNotes: adasdasda', '1.0', '../../uploads/internship_documents/22/moa_11_1776249448.pdf', 'moa.pdf', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-15', '2026-04-20', 'Pharmacy Operations', 'Dr jasmine', 'Main Pharmacy Branch', 'jenny mahinay', 'jenny Mahinay', 'jenny@gmail.com', '2026-04-15 10:37:28', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', 1, 1, 1, 'active', NULL, NULL, '2026-04-15 10:37:28', '2026-04-15 10:37:28'),
(11, 17, 27, 'MEMORANDUM OF AGREEMENT\n\nThis agreement is entered into on April 16, 2026\nBetween: MediCare Pharmacy and Linda Gwapa\n\nDepartment: Inventory Management\nSupervisor: Dr. Maria Santos\nLocation: Main Pharmacy Branch\nStart Date: April 27, 2026\n\nWork Schedule:\nDepartment: Inventory Management\r\nShift: 7 AM - 3 PM\r\n\r\nWeekly Schedule:\r\n- Monday: 7 AM - 3 PM\r\n- Tuesday: 7 AM - 3 PM\r\n- Wednesday: 7 AM - 3 PM\r\n- Thursday: 7 AM - 3 PM\r\n- Friday: 7 AM - 3 PM\r\n- Saturday: OFF\r\n- Sunday: OFF\r\n\r\nSupervisor: Dr. Maria Santos\r\nLocation: Main Pharmacy Branch\r\n\r\nNotes: Redo the first instructions', '1.0', '../../uploads/moa_documents/moa_27_1776300404.pdf', '0.1 LechGo DFDpdf.pdf', NULL, NULL, NULL, 12, NULL, NULL, NULL, NULL, '2026-04-16', '2026-04-27', 'Inventory Management', 'Dr. Maria Santos', 'Main Pharmacy Branch', 'Linda watapampa', 'Linda Gwapa', 'Linda@gmail.com', '2026-04-16 00:48:24', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', 1, 1, 1, 'active', NULL, NULL, '2026-04-16 00:48:24', '2026-04-16 00:48:24');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` varchar(50) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `related_id` int(11) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `type`, `title`, `message`, `related_id`, `is_read`, `created_at`) VALUES
(1, 16, 'internship_schedule', 'Internship Schedule & Location Details', 'Your internship schedule has been set! Start Date: April 15, 2026 at MediCare- Main branch. Click to view full details.', 25, 1, '2026-04-14 08:42:31'),
(2, 16, 'internship_schedule', 'Internship Schedule & Location Details', 'Your internship schedule has been set! Start Date: April 15, 2026 at MediCare- Main branch. Click to view full details.', 25, 1, '2026-04-14 08:46:33'),
(3, 16, 'internship_schedule', 'Internship Schedule & Location Details', 'Your internship schedule has been set! Start Date: April 14, 2026 at main. Click to view full details.', 27, 1, '2026-04-14 09:18:48'),
(4, 16, 'internship_schedule', 'Internship Schedule & Location Details', 'Your internship schedule has been set! Start Date: April 14, 2026 at main. Click to view full details.', 27, 1, '2026-04-14 09:22:44'),
(5, 16, 'internship_schedule', 'Internship Schedule & Location Details', 'Your internship schedule has been set! Start Date: April 14, 2026 at main. Click to view full details.', 27, 1, '2026-04-14 09:23:49'),
(6, 16, 'internship_schedule', 'Internship Schedule & Location Details', 'Your internship schedule has been set! Start Date: April 14, 2026 at main. Click to view full details.', 27, 1, '2026-04-14 09:25:26'),
(7, 16, 'internship_schedule', 'Internship Schedule & Location Details', 'Your internship schedule has been set! Start Date: April 14, 2026 at main. Click to view full details.', 27, 1, '2026-04-14 09:25:29'),
(8, 16, 'internship_schedule', 'Internship Schedule & Location Details', 'Your internship schedule has been set! Start Date: April 14, 2026 at main. Click to view full details.', 27, 1, '2026-04-14 09:25:30'),
(9, 16, 'interview_scheduled', 'Interview Scheduled!', 'Your interview has been scheduled for April 14, 2026 at 5:31 PM. Type: Online Interview. Click to view details.', 27, 1, '2026-04-14 09:29:14'),
(11, 16, 'work_schedule_assigned', 'Your work schedule has been assigned! Start Date: April 20, 2026. Please review and sign the MOA to confirm.', '3', NULL, 1, '2026-04-14 11:25:27'),
(12, 16, 'work_schedule_assigned', 'Your work schedule has been assigned! Start Date: April 20, 2026. Please review and sign the MOA to confirm.', '4', NULL, 1, '2026-04-14 11:33:44'),
(13, 16, 'interview_scheduled', 'You have been scheduled for an interview on April 15, 2026 at 09:35 PM. This is an online interview. Check your dashboard for meeting details.', '7', NULL, 1, '2026-04-14 11:36:15'),
(14, 16, 'work_schedule_assigned', 'Your work schedule has been assigned! Start Date: April 15, 2026. Please review and sign the MOA to confirm.', '5', NULL, 1, '2026-04-14 11:37:47'),
(15, 16, 'interview_scheduled', 'You have been scheduled for an interview on April 15, 2026 at 09:56 AM. This is an online interview. Check your dashboard for meeting details.', '8', NULL, 1, '2026-04-14 11:56:20'),
(16, 16, 'work_schedule_assigned', 'Your work schedule has been assigned! Start Date: April 20, 2026. Please review and sign the MOA to confirm.', '6', NULL, 1, '2026-04-14 11:57:19'),
(17, 16, 'interview_scheduled', 'You have been scheduled for an interview on April 16, 2026 at 02:44 PM. Location: Bago Gallera Talomo Davao City', '9', NULL, 1, '2026-04-15 06:44:59'),
(18, 16, 'work_schedule_assigned', 'Your work schedule has been assigned! Start Date: April 20, 2026. Please review and sign the MOA to confirm.', '7', NULL, 1, '2026-04-15 07:07:14'),
(19, 16, 'task_assigned', 'New task assigned', 'HR assigned you a task: QWeqqwe (2026-04-15 to 2026-04-21).', NULL, 1, '2026-04-15 07:30:21'),
(20, 16, 'task_assigned', 'New task assigned', 'HR assigned you a task: BWHAAHAH (2026-04-30 to 2026-05-29).', NULL, 1, '2026-04-15 07:31:17'),
(21, 16, 'task_status_updated', 'Task Status Updated', 'Your task status has been updated to: Late', NULL, 1, '2026-04-15 07:31:33'),
(22, 16, 'task_status_updated', 'Task Status Updated', 'Your task status has been updated to: Finished', NULL, 1, '2026-04-15 07:31:37'),
(23, 22, 'interview_scheduled', 'Interview Scheduled!', 'Your interview has been scheduled for April 17, 2026 at 8:12 AM. Type: Online Interview. Click to view details.', 28, 1, '2026-04-15 09:12:34'),
(25, 22, 'interview_scheduled', 'Interview Scheduled!', 'Your interview has been scheduled for April 20, 2026 at 8:20 AM. Type: Online Interview. Click to view details.', 29, 1, '2026-04-15 09:21:17'),
(26, 22, 'interview_scheduled', 'Interview Scheduled!', 'Your interview has been scheduled for April 20, 2026 at 8:20 AM. Type: Online Interview. Click to view details.', 29, 1, '2026-04-15 09:22:43'),
(27, 22, 'interview_scheduled', 'Interview Scheduled!', 'Your interview has been scheduled for April 20, 2026 at 8:20 AM. Type: Online Interview. Click to view details.', 29, 1, '2026-04-15 09:22:45'),
(28, 22, 'interview_scheduled', 'Interview Scheduled!', 'Your interview has been scheduled for April 16, 2026 at 5:27 PM. Type: Online Interview. Click to view details.', 30, 1, '2026-04-15 09:24:35'),
(29, 22, 'interview_scheduled', 'Interview Scheduled!', 'Your interview has been scheduled for April 20, 2026 at 8:27 AM. Type: Online Interview. Click to view details.', 31, 1, '2026-04-15 09:27:32'),
(30, 22, 'interview_scheduled', 'You have been scheduled for an interview on April 16, 2026 at 02:44 PM. Location: Bago Gallera Talomo Davao City', '9', NULL, 1, '2026-04-15 09:27:58'),
(31, 22, 'work_schedule_assigned', 'Your work schedule has been assigned! Start Date: April 20, 2026. Please review and sign the MOA to confirm.', '8', NULL, 1, '2026-04-15 09:28:45'),
(32, 22, 'task_assigned', 'New task assigned', 'HR assigned you a task: Weekly Report (2026-04-16 to 2026-04-30).', NULL, 1, '2026-04-15 09:36:06'),
(33, 22, 'task_status_updated', 'Task Status Updated', 'Your task status has been updated to: Finished', NULL, 1, '2026-04-15 09:36:58'),
(34, 22, 'task_status_updated', 'Task Status Updated', 'Your task status has been updated to: Finished', NULL, 1, '2026-04-15 09:37:31'),
(35, 22, 'work_schedule_assigned', 'Your work schedule has been assigned! Start Date: April 24, 2026. Please review and sign the MOA to confirm.', '9', NULL, 1, '2026-04-15 09:59:00'),
(36, 22, 'work_schedule_assigned', 'Your work schedule has been assigned! Start Date: April 30, 2026. Please review and sign the MOA to confirm.', '10', NULL, 1, '2026-04-15 10:06:40'),
(37, 12, 'schedule_rejected', 'Work Schedule Rejected', 'jenny Mahinay has rejected their work schedule for Pharmacy Operations. Reason: wala lng. Please edit and resend the schedule.', 10, 1, '2026-04-15 10:07:04'),
(38, 22, 'work_schedule_updated', 'Work Schedule Updated', 'Your work schedule has been updated by HR. Start Date: April 30, 2026. Please review and sign the MOA to confirm.', 10, 1, '2026-04-15 10:08:12'),
(39, 12, 'schedule_accepted', 'Work Schedule Accepted', 'jenny Mahinay has accepted their work schedule and signed the MOA for Pharmacy Operations starting April 30, 2026.', 10, 1, '2026-04-15 10:08:39'),
(40, 22, 'task_assigned', 'New task assigned', 'HR assigned you a task: Tarung (2026-04-21 to 2026-04-29).', NULL, 1, '2026-04-15 10:13:34'),
(41, 22, 'task_assigned', 'New task assigned', 'HR assigned you a task: aweaerdf (2026-04-16 to 2026-04-26).', NULL, 1, '2026-04-15 10:16:52'),
(42, 22, 'task_assigned', 'New task assigned', 'HR assigned you a task: DASD (2026-04-15 to 2026-04-30).', NULL, 1, '2026-04-15 10:19:30'),
(43, 22, 'task_assigned', 'New task assigned', 'HR assigned you a task: dadasd (2026-04-15 to 2026-04-29).', NULL, 1, '2026-04-15 10:20:24'),
(44, 22, 'task_assigned', 'New task assigned', 'HR assigned you a task: dasdasd (2026-04-21 to 2026-04-28).', NULL, 1, '2026-04-15 10:25:52'),
(45, 22, 'task_assigned', 'New task assigned', 'HR assigned you a task: adasdadasda (2026-04-15 to 2026-04-28).', NULL, 1, '2026-04-15 10:31:30'),
(46, 12, 'task_completed', 'Test Task Completed', 'This is a test notification', NULL, 1, '2026-04-15 10:34:26'),
(47, 22, 'work_schedule_assigned', 'Your work schedule has been assigned! Start Date: April 20, 2026. Please review and sign the MOA to confirm.', '11', NULL, 1, '2026-04-15 10:36:24'),
(48, 12, 'schedule_rejected', 'Work Schedule Rejected', 'jenny Mahinay has rejected their work schedule for Pharmacy Operations. Reason: kapoy mana. Please edit and resend the schedule.', 11, 1, '2026-04-15 10:36:34'),
(49, 22, 'work_schedule_updated', 'Work Schedule Updated', 'Your work schedule has been updated by HR. Start Date: April 20, 2026. Please review and sign the MOA to confirm.', 11, 1, '2026-04-15 10:36:57'),
(50, 12, 'schedule_accepted', 'Work Schedule Accepted', 'jenny Mahinay has accepted their work schedule and signed the MOA for Pharmacy Operations starting April 20, 2026.', 11, 1, '2026-04-15 10:37:28'),
(51, 12, 'task_completed', 'Task Completed', 'jenny Mahinay has completed the task: adasdadasda', NULL, 1, '2026-04-15 10:39:36'),
(54, 12, 'new_application', 'New Internship Application', 'Linda Chronicles has submitted a new internship application. Click to review.', 37, 1, '2026-04-15 12:44:14'),
(62, 12, 'schedule_rejected', 'Work Schedule Rejected', '  has rejected their work schedule for Customer Service. Reason: I have classes that schedule. Please edit and resend the schedule.', 16, 1, '2026-04-15 14:37:33'),
(64, 12, 'schedule_accepted', 'Work Schedule Accepted', '  has accepted their work schedule and signed the MOA for Customer Service starting April 20, 2026.', 16, 1, '2026-04-15 14:44:06'),
(66, 12, 'task_completed', 'Task Completed', '  has completed the task: Inventory Report', NULL, 1, '2026-04-15 14:54:13'),
(68, 12, 'task_completed', 'Task Completed', '  has completed the task: DASDAS', NULL, 1, '2026-04-15 15:05:43'),
(69, 12, 'new_application', 'New Internship Application', 'Linda Gwapa has submitted a new internship application. Click to review.', 39, 1, '2026-04-15 16:55:55'),
(70, 12, 'new_application', 'New Internship Application', 'Linda Gwapa has submitted a new internship application. Click to review.', 39, 1, '2026-04-15 17:24:02'),
(72, 12, 'new_application', 'New Internship Application', 'Linda Gwapa has submitted a new internship application. Click to review.', 39, 1, '2026-04-15 17:35:57'),
(74, 12, 'task_completed', 'Task Completed', 'Linda Gwapa has completed the task: dadasd', NULL, 1, '2026-04-15 17:41:21'),
(75, 12, 'new_application', 'New Internship Application', 'Linda Gwapa has submitted a new internship application. Click to review.', 40, 1, '2026-04-15 17:51:53'),
(78, 12, 'new_application', 'New Internship Application', 'Linda Gwapa has submitted a new internship application. Click to review.', 41, 1, '2026-04-16 00:41:25'),
(79, 27, 'interview_scheduled', 'Interview Scheduled!', 'Your interview has been scheduled for April 20, 2026 at 8:00 AM. Type: Online Interview. Click to view details.', 41, 1, '2026-04-16 00:43:21'),
(80, 27, 'interview_scheduled', 'You have been scheduled for an interview on April 20, 2026 at 08:00 AM. Location: MediCare, Main Office', '11', NULL, 1, '2026-04-16 00:44:16'),
(81, 27, 'work_schedule_assigned', 'Your work schedule has been assigned! Start Date: April 27, 2026. Please review and sign the MOA to confirm.', '17', NULL, 1, '2026-04-16 00:46:44'),
(82, 12, 'schedule_rejected', 'Work Schedule Rejected', 'Linda Gwapa has rejected their work schedule for Inventory Management. Reason: Not available on that schedule. Please edit and resend the schedule.', 17, 1, '2026-04-16 00:47:08'),
(83, 27, 'work_schedule_updated', 'Work Schedule Updated', 'Your work schedule has been updated by HR. Start Date: April 27, 2026. Please review and sign the MOA to confirm.', 17, 1, '2026-04-16 00:47:47'),
(84, 12, 'schedule_accepted', 'Work Schedule Accepted', 'Linda Gwapa has accepted their work schedule and signed the MOA for Inventory Management starting April 27, 2026.', 17, 1, '2026-04-16 00:48:24'),
(85, 27, 'task_assigned', 'New task assigned', 'HR assigned you a task: Inventory Sytem (2026-05-04 to 2026-05-08).', NULL, 1, '2026-04-16 00:49:22'),
(86, 12, 'task_completed', 'Task Completed', 'Linda Gwapa has completed the task: Inventory Sytem', NULL, 1, '2026-04-16 00:50:17');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `order_id` varchar(20) NOT NULL,
  `prescription_id` int(11) DEFAULT NULL,
  `customer_id` int(11) NOT NULL,
  `customer_name` varchar(100) NOT NULL,
  `order_type` enum('Prescription','Over-the-Counter') DEFAULT 'Prescription',
  `total_amount` decimal(10,2) DEFAULT 0.00,
  `status` enum('Pending','Processing','Ready','Completed','Cancelled') DEFAULT 'Pending',
  `pharmacist_id` int(11) DEFAULT NULL,
  `order_date` datetime DEFAULT current_timestamp(),
  `ready_date` datetime DEFAULT NULL,
  `completed_date` datetime DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `medicine_name` varchar(200) NOT NULL,
  `dosage` varchar(50) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) DEFAULT 0.00,
  `total_price` decimal(10,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pharmacy_business_document`
--

CREATE TABLE `pharmacy_business_document` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `document_type` enum('policy','guideline','procedure','manual','regulation','other') NOT NULL DEFAULT 'policy',
  `category` varchar(100) NOT NULL DEFAULT 'general',
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size` int(11) NOT NULL,
  `file_type` varchar(100) NOT NULL,
  `uploaded_by` int(11) NOT NULL,
  `upload_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pharmacy_business_document`
--

INSERT INTO `pharmacy_business_document` (`id`, `title`, `description`, `document_type`, `category`, `file_name`, `file_path`, `file_size`, `file_type`, `uploaded_by`, `upload_date`, `is_active`, `last_updated`) VALUES
(1, 'Intern Policy Guidelines', 'TABANG', 'guideline', 'Emergency Procedures', 'IAS 102 Final Requirements.pdf', 'C:\\xampp\\htdocs\\Pharmacy-Internship\\controllers/../uploads/pharmacy_documents/pharmacy_doc_1776142350_69ddc80edbf52.pdf', 236625, 'application/pdf', 12, '2026-04-14 04:52:30', 1, '2026-04-14 04:52:30'),
(2, 'Policy and guidelines', 'For new intern applicants', 'policy', 'Staff Training', 'DELA CERNA Pitch Content (1).pdf', 'C:\\xampp\\htdocs\\Pharmacy-Internship\\controllers/../uploads/pharmacy_documents/pharmacy_doc_1776148316_69dddf5c91ac1.pdf', 578425, 'application/pdf', 12, '2026-04-14 06:31:56', 1, '2026-04-14 06:31:56');

-- --------------------------------------------------------

--
-- Table structure for table `prescriptions`
--

CREATE TABLE `prescriptions` (
  `id` int(11) NOT NULL,
  `prescription_id` varchar(20) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `patient_name` varchar(100) NOT NULL,
  `medicine_name` varchar(200) NOT NULL,
  `dosage` varchar(50) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `instructions` text DEFAULT NULL,
  `doctor_name` varchar(100) DEFAULT NULL,
  `date_prescribed` date NOT NULL,
  `status` enum('Pending','Processing','Dispensed','Cancelled') DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `prescriptions`
--

INSERT INTO `prescriptions` (`id`, `prescription_id`, `patient_id`, `patient_name`, `medicine_name`, `dosage`, `quantity`, `instructions`, `doctor_name`, `date_prescribed`, `status`, `created_at`, `updated_at`) VALUES
(1, 'PRX001', 1, 'Juan Dela Cruz', 'Paracetamol', '500mg', 20, 'Take 1 tablet every 6 hours as needed for fever', 'Dr. Smith', '2024-04-14', 'Pending', '2026-04-15 15:30:35', '2026-04-15 15:30:35'),
(2, 'PRX002', 2, 'Maria Santos', 'Amoxicillin', '250mg', 30, 'Take 1 capsule every 8 hours for 7 days', 'Dr. Johnson', '2024-04-14', 'Dispensed', '2026-04-15 15:30:35', '2026-04-15 15:30:35');

-- --------------------------------------------------------

--
-- Stand-in structure for view `product_availability`
-- (See below for the actual view)
--
CREATE TABLE `product_availability` (
`id` int(11)
,`medicine_name` varchar(200)
,`dosage` varchar(50)
,`stock_quantity` int(11)
,`reorder_level` int(11)
,`availability_status` varchar(12)
,`stock_level` varchar(8)
,`unit_price` decimal(10,2)
,`manufacturer` varchar(100)
,`category` varchar(100)
,`expiry_date` date
,`updated_at` timestamp
);

-- --------------------------------------------------------

--
-- Table structure for table `product_inventory`
--

CREATE TABLE `product_inventory` (
  `id` int(11) NOT NULL,
  `product_name` varchar(200) NOT NULL,
  `barcode` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `manufacturer` varchar(100) DEFAULT NULL,
  `quantity` int(11) DEFAULT 0,
  `unit` varchar(20) DEFAULT 'pcs',
  `price` decimal(10,2) DEFAULT 0.00,
  `expiry_date` date DEFAULT NULL,
  `batch_number` varchar(50) DEFAULT NULL,
  `supplier_id` int(11) DEFAULT NULL,
  `total_price` decimal(10,2) DEFAULT 0.00,
  `reorder_level` int(11) DEFAULT 10,
  `max_stock_level` int(11) DEFAULT 100,
  `location` varchar(100) DEFAULT NULL,
  `status` enum('Active','Inactive','Expired','Discontinued') DEFAULT 'Active',
  `last_restocked` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `product_logs`
--

CREATE TABLE `product_logs` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `prescription_id` int(11) NOT NULL,
  `medicine_id` int(11) DEFAULT NULL,
  `medicine_name` varchar(200) NOT NULL,
  `dosage` varchar(50) DEFAULT NULL,
  `quantity_dispensed` int(11) NOT NULL,
  `unit_price` decimal(10,2) DEFAULT 0.00,
  `total_price` decimal(10,2) DEFAULT 0.00,
  `pharmacist_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `patient_name` varchar(100) NOT NULL,
  `action` enum('Dispensed','Returned','Exchanged','Refunded') DEFAULT 'Dispensed',
  `notes` text DEFAULT NULL,
  `log_date` datetime DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `purchase_orders`
--

CREATE TABLE `purchase_orders` (
  `id` int(11) NOT NULL,
  `purchase_order_id` varchar(20) NOT NULL,
  `requisition_id` int(11) DEFAULT NULL,
  `supplier_name` varchar(100) NOT NULL,
  `supplier_contact` varchar(100) DEFAULT NULL,
  `order_date` date NOT NULL,
  `expected_delivery_date` date DEFAULT NULL,
  `delivery_date` date DEFAULT NULL,
  `status` enum('Pending','Confirmed','Shipped','Delivered','Cancelled') DEFAULT 'Pending',
  `total_amount` decimal(10,2) DEFAULT 0.00,
  `payment_terms` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `purchase_order_items`
--

CREATE TABLE `purchase_order_items` (
  `id` int(11) NOT NULL,
  `purchase_order_id` int(11) NOT NULL,
  `medicine_id` int(11) DEFAULT NULL,
  `medicine_name` varchar(200) NOT NULL,
  `dosage` varchar(50) DEFAULT NULL,
  `quantity_ordered` int(11) NOT NULL,
  `quantity_received` int(11) DEFAULT 0,
  `unit_price` decimal(10,2) DEFAULT 0.00,
  `total_price` decimal(10,2) DEFAULT 0.00,
  `batch_number` varchar(50) DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `requisitions`
--

CREATE TABLE `requisitions` (
  `id` int(11) NOT NULL,
  `requisition_id` varchar(20) NOT NULL,
  `pharmacist_id` int(11) NOT NULL,
  `pharmacist_name` varchar(100) NOT NULL,
  `department` varchar(100) DEFAULT 'Pharmacy',
  `requisition_date` date NOT NULL,
  `date_required` date DEFAULT NULL,
  `urgency` enum('Normal','Urgent','Critical') DEFAULT 'Normal',
  `status` enum('Draft','Submitted','Approved','Rejected','Processed') DEFAULT 'Draft',
  `total_amount` decimal(10,2) DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `requisitions`
--

INSERT INTO `requisitions` (`id`, `requisition_id`, `pharmacist_id`, `pharmacist_name`, `department`, `requisition_date`, `date_required`, `urgency`, `status`, `total_amount`, `notes`, `created_at`, `updated_at`) VALUES
(1, 'REQ001', 1, 'Dr. Smith', 'Pharmacy', '2024-04-14', NULL, 'Normal', 'Approved', 3100.00, NULL, '2026-04-15 15:33:07', '2026-04-15 15:33:07');

-- --------------------------------------------------------

--
-- Table structure for table `requisition_items`
--

CREATE TABLE `requisition_items` (
  `id` int(11) NOT NULL,
  `requisition_id` int(11) NOT NULL,
  `medicine_id` int(11) DEFAULT NULL,
  `medicine_name` varchar(200) NOT NULL,
  `dosage` varchar(50) DEFAULT NULL,
  `current_stock` int(11) DEFAULT 0,
  `reorder_level` int(11) DEFAULT 0,
  `requested_quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) DEFAULT 0.00,
  `total_price` decimal(10,2) DEFAULT 0.00,
  `supplier` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `requisition_items`
--

INSERT INTO `requisition_items` (`id`, `requisition_id`, `medicine_id`, `medicine_name`, `dosage`, `current_stock`, `reorder_level`, `requested_quantity`, `unit_price`, `total_price`, `supplier`, `created_at`) VALUES
(1, 1, NULL, 'Paracetamol', '500mg', 15, 20, 100, 15.50, 1550.00, 'MediSupply Philippines', '2026-04-15 15:33:07'),
(2, 1, NULL, 'Amoxicillin', '250mg', 10, 15, 50, 45.75, 2287.50, 'PharmaLink Inc.', '2026-04-15 15:33:07'),
(3, 1, NULL, 'Ibuprofen', '400mg', 20, 20, 50, 25.00, 1250.00, 'MediSupply Philippines', '2026-04-15 15:33:07');

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `role_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `role_name`) VALUES
(4, 'Customer'),
(2, 'HR Personnel'),
(1, 'Intern'),
(6, 'Pharmacist'),
(5, 'Pharmacy Assistant'),
(3, 'Pharmacy Technician');

-- --------------------------------------------------------

--
-- Table structure for table `stock_alerts`
--

CREATE TABLE `stock_alerts` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `alert_type` enum('Low Stock','Out of Stock','Expiring Soon','Expired') NOT NULL,
  `current_quantity` int(11) NOT NULL,
  `reorder_level` int(11) NOT NULL,
  `expiry_date` date DEFAULT NULL,
  `days_to_expiry` int(11) DEFAULT NULL,
  `is_acknowledged` tinyint(1) DEFAULT 0,
  `acknowledged_by` int(11) DEFAULT NULL,
  `acknowledged_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

CREATE TABLE `suppliers` (
  `id` int(11) NOT NULL,
  `supplier_name` varchar(100) NOT NULL,
  `contact_person` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `payment_terms` varchar(100) DEFAULT NULL,
  `delivery_time` int(11) DEFAULT 7,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `suppliers`
--

INSERT INTO `suppliers` (`id`, `supplier_name`, `contact_person`, `phone`, `email`, `address`, `payment_terms`, `delivery_time`, `status`, `created_at`, `updated_at`) VALUES
(1, 'MediSupply Philippines', 'Juan Reyes', '+632-555-0001', 'orders@medisupply.ph', 'Manila, Philippines', 'Net 30', 5, 'Active', '2026-04-15 15:33:07', '2026-04-15 15:33:07'),
(2, 'PharmaLink Inc.', 'Maria Santos', '+632-555-0002', 'sales@pharmalink.com', 'Quezon City, Philippines', 'Net 15', 7, 'Active', '2026-04-15 15:33:07', '2026-04-15 15:33:07'),
(3, 'HealthCare Distributors', 'Roberto Cruz', '+632-555-0003', 'info@healthcare-dist.ph', 'Makati, Philippines', 'COD', 3, 'Active', '2026-04-15 15:33:07', '2026-04-15 15:33:07'),
(4, 'MediSupply Corp', 'John Smith', '09123456789', 'john@medisupply.com', '123 Medical Ave, Manila', NULL, 7, 'Active', '2026-04-16 00:01:56', '2026-04-16 00:01:56'),
(5, 'PharmaCorp Inc', 'Jane Doe', '09987654321', 'jane@pharmacorp.com', '456 Health St, Quezon City', NULL, 7, 'Active', '2026-04-16 00:01:56', '2026-04-16 00:01:56'),
(6, 'Global Pharma', 'Mike Johnson', '09111222333', 'mike@globalpharma.com', '789 Medicine Blvd, Makati', NULL, 7, 'Active', '2026-04-16 00:01:56', '2026-04-16 00:01:56');

-- --------------------------------------------------------

--
-- Table structure for table `task_attachments`
--

CREATE TABLE `task_attachments` (
  `id` int(11) NOT NULL,
  `task_id` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size` int(11) DEFAULT NULL,
  `uploaded_by` int(11) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `task_comments`
--

CREATE TABLE `task_comments` (
  `id` int(11) NOT NULL,
  `task_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `comment` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `middle_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) NOT NULL,
  `birth_date` date DEFAULT NULL,
  `phone_number` varchar(20) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role_id` int(11) NOT NULL DEFAULT 4,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `first_name`, `middle_name`, `last_name`, `birth_date`, `phone_number`, `email`, `password`, `role_id`, `is_active`, `created_at`, `updated_at`) VALUES
(12, 'Joanna', 'Rolida', 'wew', NULL, '09251242131', 'joanna@gmail.com', '$2y$10$DGBkL4HIvepYzS9b5ARMS.7Cw4LfvDDO.zFl2GVxJ3s.vGBi9/gaG', 2, 1, '2026-04-14 03:29:03', '2026-04-14 03:29:17'),
(16, 'Dave', 'Vismanos', 'Dela Cerna', '2004-03-14', '09120738886', 'davedelacerna09@gmail.com', '$2y$10$hvmw4/09MXh8Rj.BBZ6XM.QjzsZqm7/nZo5e1E3yVB0HIrzNyw8H2', 1, 1, '2026-04-14 06:59:02', '2026-04-14 06:59:16'),
(22, 'jenny', 'nioda', 'Mahinay', '2004-08-22', '09927886336', 'jenny@gmail.com', '$2y$10$qwYTOd4stgezOAe6BkKWIOeMJyp7Obt/uLMqba5mSuEI.ASfw1./6', 1, 1, '2026-04-15 09:06:49', '2026-04-15 09:06:52'),
(27, 'Linda', 'watapampa', 'Gwapa', '2004-03-16', '09263209117', 'Linda@gmail.com', '$2y$10$DNpIyyGsQ3ZKzjgkhpCE3upxpO1aWv.Dlo0pg//k.nSqK1ZpIEQF6', 1, 1, '2026-04-16 00:40:44', '2026-04-16 00:40:47');

-- --------------------------------------------------------

--
-- Table structure for table `work_schedules`
--

CREATE TABLE `work_schedules` (
  `id` int(11) NOT NULL,
  `evaluation_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_by` int(11) NOT NULL,
  `start_date` date NOT NULL,
  `department` varchar(100) NOT NULL,
  `shift_type` enum('morning','afternoon','night','full_day') NOT NULL,
  `shift_time` varchar(50) NOT NULL,
  `working_days` text NOT NULL,
  `supervisor_name` varchar(100) NOT NULL,
  `location` varchar(100) NOT NULL,
  `special_instructions` text DEFAULT NULL,
  `formatted_schedule` text NOT NULL,
  `moa_document_path` varchar(500) DEFAULT NULL,
  `moa_document_name` varchar(255) DEFAULT NULL,
  `status` enum('pending','sent','acknowledged','active','completed','cancelled') DEFAULT 'pending',
  `sent_at` timestamp NULL DEFAULT NULL,
  `acknowledged_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `work_schedules`
--

INSERT INTO `work_schedules` (`id`, `evaluation_id`, `user_id`, `created_by`, `start_date`, `department`, `shift_type`, `shift_time`, `working_days`, `supervisor_name`, `location`, `special_instructions`, `formatted_schedule`, `moa_document_path`, `moa_document_name`, `status`, `sent_at`, `acknowledged_at`, `created_at`, `updated_at`) VALUES
(11, 12, 22, 12, '2026-04-20', 'Pharmacy Operations', 'night', '11 PM - 7 AM', 'Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday', 'Dr jasmine', 'Main Pharmacy Branch', 'adasdasda', 'Department: Pharmacy Operations\r\nShift: 11 PM - 7 AM\r\n\r\nWeekly Schedule:\r\n- Monday: 11 PM - 7 AM\r\n- Tuesday: 11 PM - 7 AM\r\n- Wednesday: 11 PM - 7 AM\r\n- Thursday: 11 PM - 7 AM\r\n- Friday: 11 PM - 7 AM\r\n- Saturday: 11 PM - 7 AM\r\n- Sunday: 11 PM - 7 AM\r\n\r\nSupervisor: Dr jasmine\r\nLocation: Main Pharmacy Branch\r\n\r\nNotes: adasdasda', NULL, NULL, 'acknowledged', '2026-04-15 10:36:57', '2026-04-15 10:37:28', '2026-04-15 10:36:24', '2026-04-15 10:37:28'),
(17, 19, 27, 12, '2026-04-27', 'Inventory Management', 'morning', '7 AM - 3 PM', 'Monday,Tuesday,Wednesday,Thursday,Friday', 'Dr. Maria Santos', 'Main Pharmacy Branch', 'Redo the first instructions', 'Department: Inventory Management\r\nShift: 7 AM - 3 PM\r\n\r\nWeekly Schedule:\r\n- Monday: 7 AM - 3 PM\r\n- Tuesday: 7 AM - 3 PM\r\n- Wednesday: 7 AM - 3 PM\r\n- Thursday: 7 AM - 3 PM\r\n- Friday: 7 AM - 3 PM\r\n- Saturday: OFF\r\n- Sunday: OFF\r\n\r\nSupervisor: Dr. Maria Santos\r\nLocation: Main Pharmacy Branch\r\n\r\nNotes: Redo the first instructions', '../../uploads/moa_documents/moa_27_1776300404.pdf', '0.1 LechGo DFDpdf.pdf', 'acknowledged', '2026-04-16 00:47:47', '2026-04-16 00:48:24', '2026-04-16 00:46:44', '2026-04-16 00:48:24');

-- --------------------------------------------------------

--
-- Structure for view `product_availability`
--
DROP TABLE IF EXISTS `product_availability`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `product_availability`  AS SELECT `m`.`id` AS `id`, `m`.`medicine_name` AS `medicine_name`, `m`.`dosage` AS `dosage`, `m`.`stock_quantity` AS `stock_quantity`, `m`.`reorder_level` AS `reorder_level`, CASE WHEN `m`.`stock_quantity` <= 0 THEN 'Out of Stock' WHEN `m`.`stock_quantity` <= `m`.`reorder_level` THEN 'Low Stock' WHEN `m`.`stock_quantity` <= `m`.`reorder_level` * 1.5 THEN 'Medium Stock' ELSE 'Available' END AS `availability_status`, CASE WHEN `m`.`stock_quantity` <= 0 THEN 'Critical' WHEN `m`.`stock_quantity` <= `m`.`reorder_level` THEN 'Warning' WHEN `m`.`stock_quantity` <= `m`.`reorder_level` * 1.5 THEN 'Caution' ELSE 'Adequate' END AS `stock_level`, `m`.`unit_price` AS `unit_price`, `m`.`manufacturer` AS `manufacturer`, `m`.`category` AS `category`, `m`.`expiry_date` AS `expiry_date`, `m`.`updated_at` AS `updated_at` FROM `medicines` AS `m` WHERE `m`.`status` = 'Active' ORDER BY `m`.`stock_quantity` ASC ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `employee_profile`
--
ALTER TABLE `employee_profile`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `employee_id` (`employee_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `interview_id` (`interview_id`),
  ADD KEY `supervisor_id` (`supervisor_id`);

--
-- Indexes for table `internship_applications`
--
ALTER TABLE `internship_applications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `status` (`status`),
  ADD KEY `application_date` (`application_date`),
  ADD KEY `internship_applications_ibfk_2` (`reviewed_by`);

--
-- Indexes for table `internship_records`
--
ALTER TABLE `internship_records`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `internship_routine`
--
ALTER TABLE `internship_routine`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `intern_tasks`
--
ALTER TABLE `intern_tasks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `intern_id` (`intern_id`),
  ADD KEY `assigned_by` (`assigned_by`),
  ADD KEY `status` (`status`),
  ADD KEY `due_date` (`due_date`);

--
-- Indexes for table `interview_assignments`
--
ALTER TABLE `interview_assignments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_schedule` (`user_id`,`schedule_id`),
  ADD KEY `internship_record_id` (`internship_record_id`),
  ADD KEY `idx_schedule_id` (`schedule_id`),
  ADD KEY `idx_user_id` (`user_id`);

--
-- Indexes for table `interview_evaluations`
--
ALTER TABLE `interview_evaluations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_evaluation` (`interview_assignment_id`),
  ADD KEY `evaluated_by` (`evaluated_by`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_final_decision` (`final_decision`);

--
-- Indexes for table `interview_records`
--
ALTER TABLE `interview_records`
  ADD PRIMARY KEY (`id`),
  ADD KEY `application_id` (`application_id`),
  ADD KEY `hr_id` (`hr_id`),
  ADD KEY `interview_date` (`interview_date`),
  ADD KEY `outcome` (`outcome`);

--
-- Indexes for table `interview_schedule`
--
ALTER TABLE `interview_schedule`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_interview_date` (`interview_date`),
  ADD KEY `idx_batch_number` (`batch_number`),
  ADD KEY `idx_interview_type` (`interview_type`);

--
-- Indexes for table `inventory_movements`
--
ALTER TABLE `inventory_movements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `movement_type` (`movement_type`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `created_at` (`created_at`);

--
-- Indexes for table `medicines`
--
ALTER TABLE `medicines`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `medicine_name` (`medicine_name`,`dosage`),
  ADD KEY `category` (`category`),
  ADD KEY `status` (`status`),
  ADD KEY `idx_medicine_stock` (`stock_quantity`,`reorder_level`),
  ADD KEY `idx_medicine_status` (`status`);

--
-- Indexes for table `moa_agreements`
--
ALTER TABLE `moa_agreements`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_work_schedule` (`work_schedule_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_accepted_at` (`accepted_at`),
  ADD KEY `moa_uploaded_by` (`moa_uploaded_by`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_read` (`user_id`,`is_read`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_id` (`order_id`),
  ADD KEY `prescription_id` (`prescription_id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `pharmacist_id` (`pharmacist_id`),
  ADD KEY `status` (`status`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `pharmacy_business_document`
--
ALTER TABLE `pharmacy_business_document`
  ADD PRIMARY KEY (`id`),
  ADD KEY `document_type` (`document_type`),
  ADD KEY `category` (`category`),
  ADD KEY `uploaded_by` (`uploaded_by`),
  ADD KEY `upload_date` (`upload_date`),
  ADD KEY `is_active` (`is_active`);

--
-- Indexes for table `prescriptions`
--
ALTER TABLE `prescriptions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `prescription_id` (`prescription_id`),
  ADD KEY `patient_id` (`patient_id`),
  ADD KEY `status` (`status`);

--
-- Indexes for table `product_inventory`
--
ALTER TABLE `product_inventory`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `barcode` (`barcode`),
  ADD KEY `product_name` (`product_name`),
  ADD KEY `category` (`category`),
  ADD KEY `status` (`status`),
  ADD KEY `idx_product_inventory_expiry` (`expiry_date`),
  ADD KEY `idx_product_inventory_status` (`status`),
  ADD KEY `idx_product_inventory_reorder` (`quantity`,`reorder_level`);

--
-- Indexes for table `product_logs`
--
ALTER TABLE `product_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `prescription_id` (`prescription_id`),
  ADD KEY `medicine_id` (`medicine_id`),
  ADD KEY `log_date` (`log_date`),
  ADD KEY `action` (`action`),
  ADD KEY `pharmacist_id` (`pharmacist_id`),
  ADD KEY `patient_id` (`patient_id`);

--
-- Indexes for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `purchase_order_id` (`purchase_order_id`),
  ADD KEY `requisition_id` (`requisition_id`),
  ADD KEY `supplier_name` (`supplier_name`),
  ADD KEY `status` (`status`);

--
-- Indexes for table `purchase_order_items`
--
ALTER TABLE `purchase_order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `purchase_order_id` (`purchase_order_id`),
  ADD KEY `medicine_id` (`medicine_id`);

--
-- Indexes for table `requisitions`
--
ALTER TABLE `requisitions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `requisition_id` (`requisition_id`),
  ADD KEY `pharmacist_id` (`pharmacist_id`),
  ADD KEY `status` (`status`);

--
-- Indexes for table `requisition_items`
--
ALTER TABLE `requisition_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `requisition_id` (`requisition_id`),
  ADD KEY `medicine_id` (`medicine_id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `role_name` (`role_name`);

--
-- Indexes for table `stock_alerts`
--
ALTER TABLE `stock_alerts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `alert_type` (`alert_type`),
  ADD KEY `is_acknowledged` (`is_acknowledged`),
  ADD KEY `idx_stock_alerts_unacknowledged` (`is_acknowledged`,`created_at`);

--
-- Indexes for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `supplier_name` (`supplier_name`),
  ADD KEY `status` (`status`);

--
-- Indexes for table `task_attachments`
--
ALTER TABLE `task_attachments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `task_id` (`task_id`),
  ADD KEY `uploaded_by` (`uploaded_by`);

--
-- Indexes for table `task_comments`
--
ALTER TABLE `task_comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `task_id` (`task_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `phone_number` (`phone_number`),
  ADD KEY `role_id` (`role_id`),
  ADD KEY `is_active` (`is_active`),
  ADD KEY `idx_full_name` (`last_name`,`first_name`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `work_schedules`
--
ALTER TABLE `work_schedules`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_evaluation` (`evaluation_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_start_date` (`start_date`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `employee_profile`
--
ALTER TABLE `employee_profile`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `internship_applications`
--
ALTER TABLE `internship_applications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `internship_records`
--
ALTER TABLE `internship_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT for table `internship_routine`
--
ALTER TABLE `internship_routine`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `intern_tasks`
--
ALTER TABLE `intern_tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `interview_assignments`
--
ALTER TABLE `interview_assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `interview_evaluations`
--
ALTER TABLE `interview_evaluations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `interview_records`
--
ALTER TABLE `interview_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `interview_schedule`
--
ALTER TABLE `interview_schedule`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `inventory_movements`
--
ALTER TABLE `inventory_movements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `medicines`
--
ALTER TABLE `medicines`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `moa_agreements`
--
ALTER TABLE `moa_agreements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=87;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pharmacy_business_document`
--
ALTER TABLE `pharmacy_business_document`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `prescriptions`
--
ALTER TABLE `prescriptions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `product_inventory`
--
ALTER TABLE `product_inventory`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `product_logs`
--
ALTER TABLE `product_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `purchase_order_items`
--
ALTER TABLE `purchase_order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `requisitions`
--
ALTER TABLE `requisitions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `requisition_items`
--
ALTER TABLE `requisition_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `stock_alerts`
--
ALTER TABLE `stock_alerts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `task_attachments`
--
ALTER TABLE `task_attachments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `task_comments`
--
ALTER TABLE `task_comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `work_schedules`
--
ALTER TABLE `work_schedules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `employee_profile`
--
ALTER TABLE `employee_profile`
  ADD CONSTRAINT `employee_profile_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `employee_profile_ibfk_2` FOREIGN KEY (`interview_id`) REFERENCES `interview_records` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `employee_profile_ibfk_3` FOREIGN KEY (`supervisor_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `internship_applications`
--
ALTER TABLE `internship_applications`
  ADD CONSTRAINT `internship_applications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `internship_applications_ibfk_2` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `internship_records`
--
ALTER TABLE `internship_records`
  ADD CONSTRAINT `internship_records_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `intern_tasks`
--
ALTER TABLE `intern_tasks`
  ADD CONSTRAINT `intern_tasks_ibfk_1` FOREIGN KEY (`intern_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `intern_tasks_ibfk_2` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `interview_assignments`
--
ALTER TABLE `interview_assignments`
  ADD CONSTRAINT `interview_assignments_ibfk_1` FOREIGN KEY (`schedule_id`) REFERENCES `interview_schedule` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `interview_assignments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `interview_assignments_ibfk_3` FOREIGN KEY (`internship_record_id`) REFERENCES `internship_records` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `interview_evaluations`
--
ALTER TABLE `interview_evaluations`
  ADD CONSTRAINT `interview_evaluations_ibfk_1` FOREIGN KEY (`interview_assignment_id`) REFERENCES `interview_assignments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `interview_evaluations_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `interview_evaluations_ibfk_3` FOREIGN KEY (`evaluated_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `interview_records`
--
ALTER TABLE `interview_records`
  ADD CONSTRAINT `interview_records_ibfk_1` FOREIGN KEY (`application_id`) REFERENCES `internship_applications` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `interview_records_ibfk_2` FOREIGN KEY (`hr_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `interview_schedule`
--
ALTER TABLE `interview_schedule`
  ADD CONSTRAINT `interview_schedule_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `moa_agreements`
--
ALTER TABLE `moa_agreements`
  ADD CONSTRAINT `moa_agreements_ibfk_1` FOREIGN KEY (`work_schedule_id`) REFERENCES `work_schedules` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `moa_agreements_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `moa_agreements_ibfk_3` FOREIGN KEY (`moa_uploaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `pharmacy_business_document`
--
ALTER TABLE `pharmacy_business_document`
  ADD CONSTRAINT `pharmacy_business_document_ibfk_1` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_logs`
--
ALTER TABLE `product_logs`
  ADD CONSTRAINT `product_logs_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`),
  ADD CONSTRAINT `product_logs_ibfk_2` FOREIGN KEY (`prescription_id`) REFERENCES `prescriptions` (`id`),
  ADD CONSTRAINT `product_logs_ibfk_3` FOREIGN KEY (`medicine_id`) REFERENCES `medicines` (`id`),
  ADD CONSTRAINT `product_logs_ibfk_4` FOREIGN KEY (`pharmacist_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `product_logs_ibfk_5` FOREIGN KEY (`patient_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `task_attachments`
--
ALTER TABLE `task_attachments`
  ADD CONSTRAINT `task_attachments_ibfk_1` FOREIGN KEY (`task_id`) REFERENCES `intern_tasks` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `task_attachments_ibfk_2` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `task_comments`
--
ALTER TABLE `task_comments`
  ADD CONSTRAINT `task_comments_ibfk_1` FOREIGN KEY (`task_id`) REFERENCES `intern_tasks` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `task_comments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `work_schedules`
--
ALTER TABLE `work_schedules`
  ADD CONSTRAINT `work_schedules_ibfk_1` FOREIGN KEY (`evaluation_id`) REFERENCES `interview_evaluations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `work_schedules_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `work_schedules_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
