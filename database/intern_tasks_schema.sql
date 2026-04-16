-- --------------------------------------------------------
-- Intern Task Management System
-- --------------------------------------------------------

--
-- Table structure for table `intern_tasks`
--

CREATE TABLE IF NOT EXISTS `intern_tasks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `intern_id` (`intern_id`),
  KEY `assigned_by` (`assigned_by`),
  KEY `status` (`status`),
  KEY `due_date` (`due_date`),
  CONSTRAINT `intern_tasks_ibfk_1` FOREIGN KEY (`intern_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `intern_tasks_ibfk_2` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `task_comments`
--

CREATE TABLE IF NOT EXISTS `task_comments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `task_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `comment` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `task_id` (`task_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `task_comments_ibfk_1` FOREIGN KEY (`task_id`) REFERENCES `intern_tasks` (`id`) ON DELETE CASCADE,
  CONSTRAINT `task_comments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `task_attachments`
--

CREATE TABLE IF NOT EXISTS `task_attachments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `task_id` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size` int(11) DEFAULT NULL,
  `uploaded_by` int(11) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `task_id` (`task_id`),
  KEY `uploaded_by` (`uploaded_by`),
  CONSTRAINT `task_attachments_ibfk_1` FOREIGN KEY (`task_id`) REFERENCES `intern_tasks` (`id`) ON DELETE CASCADE,
  CONSTRAINT `task_attachments_ibfk_2` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Sample tasks for testing
-- Note: Update intern_id and assigned_by values based on your actual user IDs
-- Run check_users_for_tasks.php to see available user IDs
--

-- Uncomment and update the following INSERT statements with actual user IDs from your database:

-- Example for intern_id=16 (Dave Dela Cerna), assigned_by=12 (Joanna - HR Personnel):
-- INSERT INTO `intern_tasks` (`intern_id`, `task_title`, `task_description`, `assigned_by`, `due_date`, `status`, `priority`, `category`) VALUES
-- (16, 'Complete Pharmacy Orientation', 'Attend and complete the pharmacy orientation program', 12, DATE_ADD(NOW(), INTERVAL 3 DAY), 'Pending', 'High', 'Training'),
-- (16, 'Shadow Senior Pharmacist', 'Observe and learn from senior pharmacist for 2 days', 12, DATE_ADD(NOW(), INTERVAL 7 DAY), 'Pending', 'Medium', 'Training'),
-- (16, 'Learn Inventory System', 'Complete training on pharmacy inventory management system', 12, DATE_ADD(NOW(), INTERVAL 5 DAY), 'In Progress', 'High', 'Training');
