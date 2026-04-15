-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
<<<<<<< HEAD
-- Generation Time: Apr 14, 2026 at 01:59 PM
=======
-- Generation Time: Apr 14, 2026 at 04:55 AM
>>>>>>> recovery-restore
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
<<<<<<< HEAD
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `work_schedule_id` int(11) NOT NULL,
  `attendance_date` date NOT NULL,
  `scheduled_shift_start` time NOT NULL,
  `scheduled_shift_end` time NOT NULL,
  `clock_in_time` datetime DEFAULT NULL,
  `clock_out_time` datetime DEFAULT NULL,
  `status` enum('present','absent','late','half_day','excused','on_leave') DEFAULT 'absent',
  `is_late` tinyint(1) DEFAULT 0,
  `late_minutes` int(11) DEFAULT 0,
  `is_early_out` tinyint(1) DEFAULT 0,
  `early_out_minutes` int(11) DEFAULT 0,
  `total_hours_worked` decimal(5,2) DEFAULT 0.00,
  `remarks` text DEFAULT NULL,
  `excuse_reason` text DEFAULT NULL,
  `requires_approval` tinyint(1) DEFAULT 0,
  `approved_by` int(11) DEFAULT NULL,
  `approval_status` enum('pending','approved','rejected') DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `attendance_summary`
--

CREATE TABLE `attendance_summary` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `work_schedule_id` int(11) NOT NULL,
  `summary_period` enum('weekly','monthly') NOT NULL,
  `period_start_date` date NOT NULL,
  `period_end_date` date NOT NULL,
  `total_days_scheduled` int(11) DEFAULT 0,
  `total_days_present` int(11) DEFAULT 0,
  `total_days_absent` int(11) DEFAULT 0,
  `total_days_late` int(11) DEFAULT 0,
  `total_days_excused` int(11) DEFAULT 0,
  `total_hours_worked` decimal(8,2) DEFAULT 0.00,
  `attendance_rate` decimal(5,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(27, 16, 'Dave', 'Dela Cerna', '2004-03-14', 1, 'Davao Central college', 'enrollment_certificate_1776158127.pdf', 'valid', '', 1, 1, 1, 'recommendation_letter_1776158132.pdf', 'valid', '', 1, 'medical_certificate_1776158137.pdf', 'valid', '', 1, 'parental_consent_1776158142.pdf', 'valid', '', 1, 'approved', '2026-04-14', '480 hours', 'Monday - Friday', '8:00 am - 5:00 pm', 'Bring Phone', 'main', 'toril davao city', 'Jenny', '115645656', 'hr@gmai.com', 1, 1, '2026-04-14', '17:31:00', 'online', '', 'https://www.facebook.com/messenger_media?attachment_id=1657052178637779&message_id=mid.%24gAANwqRoOqUuju72blWdinpZYwAlu&thread_id=968296519084363', 'HAHAH', '2026-04-14 09:15:15', '2026-04-14 09:29:14');

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
(8, 8, 16, 27, 'completed', NULL, NULL, '2026-04-14 11:56:20', '2026-04-14 11:56:37');

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
(7, 8, 16, 12, 5, '', 5, '', 5, '', 5, '', 5, '', 5, '', 5, NULL, 5, '', 5, '', 5, '', 5.00, '0', 'accepted', 1, '2026-04-20', 'Department: Pharmacy Operations\r\nShift: 11 PM - 7 AM\r\n\r\nWeekly Schedule:\r\n- Monday: 11 PM - 7 AM\r\n- Tuesday: 11 PM - 7 AM\r\n- Wednesday: 11 PM - 7 AM\r\n- Thursday: 11 PM - 7 AM\r\n- Friday: 11 PM - 7 AM\r\n- Saturday: 11 PM - 7 AM\r\n- Sunday: 11 PM - 7 AM\r\n\r\nSupervisor: dr ERWINM\r\nLocation: Downtown Branch\r\n\r\nNotes: bE ON TIME', '2026-04-14 11:56:51', '2026-04-14 11:57:19');

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
(8, 1, '2026-04-15', '09:56:00', 'online', '', 'https://www.facebook.com/messages/t/764186356463229', '31231232', '12312312', 15, 1, 'scheduled', 'hehe', 12, '2026-04-14 11:56:14', '2026-04-14 11:56:20');

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
(6, 6, 16, 'MEMORANDUM OF AGREEMENT\n\nThis agreement is entered into on April 14, 2026\nBetween: MediCare Pharmacy and  \n\nDepartment: Pharmacy Operations\nSupervisor: dr ERWINM\nLocation: Downtown Branch\nStart Date: April 20, 2026\n\nWork Schedule:\nDepartment: Pharmacy Operations\r\nShift: 11 PM - 7 AM\r\n\r\nWeekly Schedule:\r\n- Monday: 11 PM - 7 AM\r\n- Tuesday: 11 PM - 7 AM\r\n- Wednesday: 11 PM - 7 AM\r\n- Thursday: 11 PM - 7 AM\r\n- Friday: 11 PM - 7 AM\r\n- Saturday: 11 PM - 7 AM\r\n- Sunday: 11 PM - 7 AM\r\n\r\nSupervisor: dr ERWINM\r\nLocation: Downtown Branch\r\n\r\nNotes: bE ON TIME', '1.0', '../../uploads/internship_documents/16/moa_6_1776167862.pdf', 'moa.pdf', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-14', '2026-04-20', 'Pharmacy Operations', 'dr ERWINM', 'Downtown Branch', 'Dave Vismanos Dela Cerna', ' ', 'davedelacerna09@gmail.com', '2026-04-14 11:57:42', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', 1, 1, 1, 'active', NULL, NULL, '2026-04-14 11:57:42', '2026-04-14 11:57:42');

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
(16, 16, 'work_schedule_assigned', 'Your work schedule has been assigned! Start Date: April 20, 2026. Please review and sign the MOA to confirm.', '6', NULL, 1, '2026-04-14 11:57:19');

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
=======
>>>>>>> recovery-restore
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
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `middle_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) NOT NULL,
<<<<<<< HEAD
  `birth_date` date DEFAULT NULL,
=======
>>>>>>> recovery-restore
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

<<<<<<< HEAD
INSERT INTO `users` (`id`, `first_name`, `middle_name`, `last_name`, `birth_date`, `phone_number`, `email`, `password`, `role_id`, `is_active`, `created_at`, `updated_at`) VALUES
(12, 'Joanna', 'Rolida', 'wew', NULL, '09251242131', 'joanna@gmail.com', '$2y$10$DGBkL4HIvepYzS9b5ARMS.7Cw4LfvDDO.zFl2GVxJ3s.vGBi9/gaG', 2, 1, '2026-04-14 03:29:03', '2026-04-14 03:29:17'),
(16, 'Dave', 'Vismanos', 'Dela Cerna', '2004-03-14', '09120738886', 'davedelacerna09@gmail.com', '$2y$10$hvmw4/09MXh8Rj.BBZ6XM.QjzsZqm7/nZo5e1E3yVB0HIrzNyw8H2', 1, 1, '2026-04-14 06:59:02', '2026-04-14 06:59:16');

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
  `status` enum('pending','sent','acknowledged','active','completed','cancelled') DEFAULT 'pending',
  `sent_at` timestamp NULL DEFAULT NULL,
  `acknowledged_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `work_schedules`
--

INSERT INTO `work_schedules` (`id`, `evaluation_id`, `user_id`, `created_by`, `start_date`, `department`, `shift_type`, `shift_time`, `working_days`, `supervisor_name`, `location`, `special_instructions`, `formatted_schedule`, `status`, `sent_at`, `acknowledged_at`, `created_at`, `updated_at`) VALUES
(6, 7, 16, 12, '2026-04-20', 'Pharmacy Operations', 'night', '11 PM - 7 AM', 'Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday', 'dr ERWINM', 'Downtown Branch', 'bE ON TIME', 'Department: Pharmacy Operations\r\nShift: 11 PM - 7 AM\r\n\r\nWeekly Schedule:\r\n- Monday: 11 PM - 7 AM\r\n- Tuesday: 11 PM - 7 AM\r\n- Wednesday: 11 PM - 7 AM\r\n- Thursday: 11 PM - 7 AM\r\n- Friday: 11 PM - 7 AM\r\n- Saturday: 11 PM - 7 AM\r\n- Sunday: 11 PM - 7 AM\r\n\r\nSupervisor: dr ERWINM\r\nLocation: Downtown Branch\r\n\r\nNotes: bE ON TIME', 'acknowledged', '2026-04-14 11:57:19', '2026-04-14 11:57:42', '2026-04-14 11:57:19', '2026-04-14 11:57:42');
=======
INSERT INTO `users` (`id`, `first_name`, `middle_name`, `last_name`, `phone_number`, `email`, `password`, `role_id`, `is_active`, `created_at`, `updated_at`) VALUES
(11, 'Dave', 'Dela', 'cerna', '09120738886', 'davedelacerna09@gmail.com', '$2y$10$0BqzzJfgTMihAmGS/W/sAu.oZEG218Z2GZ7DVNEdAyscnSx/qoahu', 1, 1, '2026-04-14 02:52:52', '2026-04-14 02:53:34');
>>>>>>> recovery-restore

--
-- Indexes for dumped tables
--

--
<<<<<<< HEAD
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_attendance` (`user_id`,`attendance_date`),
  ADD KEY `work_schedule_id` (`work_schedule_id`),
  ADD KEY `approved_by` (`approved_by`),
  ADD KEY `idx_user_date` (`user_id`,`attendance_date`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_attendance_date` (`attendance_date`);

--
-- Indexes for table `attendance_summary`
--
ALTER TABLE `attendance_summary`
  ADD PRIMARY KEY (`id`),
  ADD KEY `work_schedule_id` (`work_schedule_id`),
  ADD KEY `idx_user_period` (`user_id`,`period_start_date`,`period_end_date`),
  ADD KEY `idx_period` (`summary_period`);

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
=======
>>>>>>> recovery-restore
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `role_name` (`role_name`);

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
<<<<<<< HEAD
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
=======
>>>>>>> recovery-restore
-- AUTO_INCREMENT for dumped tables
--

--
<<<<<<< HEAD
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `attendance_summary`
--
ALTER TABLE `attendance_summary`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employee_profile`
--
ALTER TABLE `employee_profile`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `internship_applications`
--
ALTER TABLE `internship_applications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `internship_records`
--
ALTER TABLE `internship_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `interview_assignments`
--
ALTER TABLE `interview_assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `interview_evaluations`
--
ALTER TABLE `interview_evaluations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `interview_records`
--
ALTER TABLE `interview_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `interview_schedule`
--
ALTER TABLE `interview_schedule`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `moa_agreements`
--
ALTER TABLE `moa_agreements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `pharmacy_business_document`
--
ALTER TABLE `pharmacy_business_document`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
=======
>>>>>>> recovery-restore
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
<<<<<<< HEAD
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `work_schedules`
--
ALTER TABLE `work_schedules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;
=======
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;
>>>>>>> recovery-restore

--
-- Constraints for dumped tables
--

--
<<<<<<< HEAD
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `attendance_ibfk_2` FOREIGN KEY (`work_schedule_id`) REFERENCES `work_schedules` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `attendance_ibfk_3` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `attendance_summary`
--
ALTER TABLE `attendance_summary`
  ADD CONSTRAINT `attendance_summary_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `attendance_summary_ibfk_2` FOREIGN KEY (`work_schedule_id`) REFERENCES `work_schedules` (`id`) ON DELETE CASCADE;

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
=======
>>>>>>> recovery-restore
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;
<<<<<<< HEAD

--
-- Constraints for table `work_schedules`
--
ALTER TABLE `work_schedules`
  ADD CONSTRAINT `work_schedules_ibfk_1` FOREIGN KEY (`evaluation_id`) REFERENCES `interview_evaluations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `work_schedules_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `work_schedules_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;
=======
>>>>>>> recovery-restore
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
