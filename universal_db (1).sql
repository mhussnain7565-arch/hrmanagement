-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 17, 2026 at 07:50 AM
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
-- Database: `universal_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

CREATE TABLE `courses` (
  `id` int(11) NOT NULL,
  `department_id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `code` varchar(20) DEFAULT NULL,
  `credit_hours` int(11) DEFAULT 3,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `courses`
--

INSERT INTO `courses` (`id`, `department_id`, `name`, `code`, `credit_hours`, `created_at`, `deleted_at`) VALUES
(1, 1, 'Data Structures', 'CS-201', 3, '2026-02-25 19:41:56', NULL),
(2, 1, 'Artificial Intelligence', 'CS-401', 3, '2026-02-25 19:41:56', NULL),
(3, 2, 'Web Development', 'IT-305', 3, '2026-02-25 19:41:56', NULL),
(4, 3, 'Principles of Management', 'BBA-101', 3, '2026-02-25 19:41:56', NULL),
(5, 13, 'Principles of Management', 'MGT101', 3, '2026-03-06 04:08:58', NULL),
(6, 13, 'Marketing Management', 'MKT201', 3, '2026-03-06 04:08:58', NULL),
(7, 13, 'Financial Accounting', 'ACC101', 3, '2026-03-06 04:08:58', NULL),
(8, 13, 'Business Mathematics', 'MTH105', 3, '2026-03-06 04:08:58', NULL),
(9, 7, 'Macroeconomics', 'ECO201', 3, '2026-03-06 04:08:58', NULL),
(10, 13, 'Human Resource Management', 'HRM301', 3, '2026-03-06 04:08:58', NULL),
(11, 13, 'Business Law', 'LAW205', 3, '2026-03-06 04:08:58', '2026-03-06 04:09:48'),
(12, 13, 'Organizational Behavior', 'MGT210', 3, '2026-03-06 04:08:58', NULL),
(13, 13, 'Department of Chemistry', 'CHEMISTRY', 3, '2026-03-06 04:18:07', NULL),
(14, 15, 'ABC', NULL, 3, '2026-03-06 05:18:08', '2026-03-06 05:18:18'),
(15, 15, 'mind game', NULL, 3, '2026-03-24 03:20:48', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `code` varchar(10) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`id`, `name`, `code`, `created_at`, `deleted_at`) VALUES
(7, 'CS Department', 'CS', '2026-03-01 21:55:22', NULL),
(10, 'IT Department', 'IT', '2026-03-01 21:55:22', NULL),
(11, 'LAW Department', 'LAW', '2026-03-01 21:55:22', NULL),
(12, 'Physics Department', 'PHY', '2026-03-01 21:55:22', NULL),
(13, 'BBA Department', 'BBA', '2026-03-06 04:08:58', NULL),
(14, 'Department of Chemistry', 'CHEMISTRY', '2026-03-06 04:19:29', NULL),
(15, 'Department of psychology', 'PY10', '2026-03-06 05:17:12', NULL),
(16, 'Department of criminology', 'CRIMINOLOG', '2026-03-06 19:36:39', NULL),
(17, 'Department of comerce', 'CSD', '2026-04-06 04:46:11', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `employee_type` enum('Faculty','Staff','Contract','Visiting') NOT NULL,
  `designation` varchar(100) NOT NULL,
  `department` varchar(100) NOT NULL,
  `joining_date` date NOT NULL,
  `tenure_status` enum('Tenured','Non-Tenured','Not Applicable') DEFAULT 'Not Applicable',
  `confirmation_status` enum('Confirmed','Probation') DEFAULT 'Probation',
  `last_promotion_date` date DEFAULT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`id`, `name`, `email`, `employee_type`, `designation`, `department`, `joining_date`, `tenure_status`, `confirmation_status`, `last_promotion_date`, `status`, `created_at`) VALUES
(2, 'Muhammad Hussnain', 'mhussnain7565@gmail.com', 'Faculty', 'Hod', 'DBA', '2020-06-05', 'Not Applicable', 'Confirmed', '2026-02-12', 'Inactive', '2026-02-12 05:30:24'),
(3, 'MR Umair waqas', 'umairwaqas75@gmail.com', 'Visiting', 'Teachers', 'BBIS', '2025-01-12', 'Not Applicable', 'Confirmed', '2026-06-05', 'Active', '2026-02-12 05:39:51');

-- --------------------------------------------------------

--
-- Table structure for table `employee_shifts`
--

CREATE TABLE `employee_shifts` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `shift_id` int(11) NOT NULL,
  `effective_from` date NOT NULL,
  `effective_to` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employee_shifts`
--

INSERT INTO `employee_shifts` (`id`, `user_id`, `shift_id`, `effective_from`, `effective_to`, `created_at`) VALUES
(1, 1, 2, '2026-02-16', NULL, '2026-02-16 03:33:34');

-- --------------------------------------------------------

--
-- Table structure for table `faculty_subjects`
--

CREATE TABLE `faculty_subjects` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `faculty_subjects`
--

INSERT INTO `faculty_subjects` (`id`, `user_id`, `course_id`, `assigned_at`) VALUES
(1, 8, 8, '2026-03-24 03:19:37'),
(2, 8, 12, '2026-03-24 03:20:08'),
(4, 10, 12, '2026-04-06 04:51:06');

-- --------------------------------------------------------

--
-- Table structure for table `faculty_workload_config`
--

CREATE TABLE `faculty_workload_config` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `max_hours_per_week` int(11) DEFAULT 18,
  `current_hours` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `holidays`
--

CREATE TABLE `holidays` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `date` date NOT NULL,
  `is_recurring` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `holidays`
--

INSERT INTO `holidays` (`id`, `name`, `date`, `is_recurring`, `created_at`) VALUES
(1, 'Basant', '2026-02-06', 1, '2026-02-13 07:25:51'),
(2, 'independence day ', '2026-08-14', 1, '2026-02-16 03:30:38'),
(3, 'mini eid ', '2026-02-16', 1, '2026-02-16 03:31:07');

-- --------------------------------------------------------

--
-- Table structure for table `leave_applications`
--

CREATE TABLE `leave_applications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `reason` text DEFAULT NULL,
  `status` enum('Pending','Approved','Rejected') DEFAULT 'Pending',
  `applied_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `leave_applications`
--

INSERT INTO `leave_applications` (`id`, `user_id`, `category_id`, `start_date`, `end_date`, `reason`, `status`, `applied_at`) VALUES
(1, 8, 1, '2026-03-17', '2026-03-31', 'Severe viral infection requiring complete bed rest and medication for 14 days as prescribed by the hospital.', 'Approved', '2026-03-17 18:44:06'),
(2, 11, 3, '2026-03-18', '2026-03-19', 'Due to the relative death', 'Approved', '2026-03-17 19:06:24'),
(3, 14, 1, '2026-03-19', '2026-03-25', 'due to fever', 'Approved', '2026-03-17 19:13:27'),
(4, 7, 3, '2026-03-24', '2026-03-30', 'due to personal issues', 'Approved', '2026-03-24 03:17:08'),
(5, 7, 3, '2026-03-24', '2026-03-30', 'due to personal issues', 'Approved', '2026-03-24 03:17:38'),
(6, 7, 3, '2026-03-24', '2026-03-30', 'due to family issues', 'Rejected', '2026-03-24 11:05:54'),
(7, 11, 1, '2026-04-06', '2026-04-09', 'due to fever', 'Approved', '2026-04-06 04:52:00');

-- --------------------------------------------------------

--
-- Table structure for table `leave_categories`
--

CREATE TABLE `leave_categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `days_allowed` int(11) NOT NULL DEFAULT 0,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `leave_categories`
--

INSERT INTO `leave_categories` (`id`, `name`, `description`, `days_allowed`, `status`, `created_at`) VALUES
(1, 'Health/Medical Leave', 'Leave granted for recuperation from illness, injury, or medical appointments.', 14, 'active', '2026-03-17 18:29:27'),
(2, 'Travel/Vacation Leave', 'Paid time off for personal travel, rest, and recreation.', 20, 'active', '2026-03-17 18:29:27'),
(3, 'Emergency Leave', 'Unplanned leave for sudden, unforeseen personal or family emergencies.', 5, 'active', '2026-03-17 18:29:27'),
(4, 'Occasional/Casual Leave', 'Short-term leave for personal matters, errands, or occasional obligations.', 10, 'active', '2026-03-17 18:29:27'),
(5, 'Maternity/Paternity Leave', 'Extended leave granted to new parents for the birth or adoption of a child.', 90, 'active', '2026-03-17 18:29:27'),
(6, 'Study/Sabbatical Leave', 'Leave designated for higher education, research, or professional development.', 30, 'active', '2026-03-17 18:29:27');

-- --------------------------------------------------------

--
-- Table structure for table `lectures`
--

CREATE TABLE `lectures` (
  `id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `room_id` int(11) NOT NULL,
  `day_of_week` enum('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday') NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `semester` varchar(20) NOT NULL,
  `year` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lectures`
--

INSERT INTO `lectures` (`id`, `course_id`, `teacher_id`, `room_id`, `day_of_week`, `start_time`, `end_time`, `semester`, `year`, `created_at`, `deleted_at`) VALUES
(1, 1, 1, 1, 'Monday', '09:00:00', '11:00:00', 'Spring 2026', 2026, '2026-02-25 19:41:56', NULL),
(2, 3, 1, 2, 'Tuesday', '14:00:00', '16:00:00', 'Spring 2026', 2026, '2026-02-25 19:41:56', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `role_access`
--

CREATE TABLE `role_access` (
  `role_key` varchar(50) NOT NULL,
  `page_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `role_access`
--

INSERT INTO `role_access` (`role_key`, `page_id`) VALUES
('clerks', 1),
('clerks', 35),
('clerks', 36),
('clerks', 37),
('clerks', 41),
('clerks', 42),
('clerks', 43),
('clerks', 45),
('clerks', 46),
('clerks', 47),
('clerks', 50),
('staff', 1),
('staff', 36),
('staff', 37),
('staff', 45),
('staff', 46),
('staff', 47),
('super_admin', 1),
('super_admin', 2),
('super_admin', 3),
('super_admin', 4),
('super_admin', 5),
('super_admin', 41),
('super_admin', 42),
('super_admin', 43),
('super_admin', 45),
('super_admin', 46),
('super_admin', 47),
('super_admin', 48),
('super_admin', 49),
('super_admin', 50);

-- --------------------------------------------------------

--
-- Table structure for table `rooms`
--

CREATE TABLE `rooms` (
  `id` int(11) NOT NULL,
  `room_number` varchar(50) NOT NULL,
  `type` enum('Lab','Auditorium','Classroom') NOT NULL,
  `capacity` int(11) NOT NULL,
  `department_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rooms`
--

INSERT INTO `rooms` (`id`, `room_number`, `type`, `capacity`, `department_id`, `created_at`) VALUES
(1, 'Lab-101', 'Lab', 30, 1, '2026-02-25 19:41:56'),
(2, 'Lab-102', 'Lab', 25, 2, '2026-02-25 19:41:56'),
(3, 'Auditorium-A', 'Auditorium', 200, 3, '2026-02-25 19:41:56'),
(4, 'Room-301', 'Classroom', 50, NULL, '2026-02-25 19:41:56'),
(5, 'Room-302', 'Classroom', 40, NULL, '2026-02-25 19:41:56');

-- --------------------------------------------------------

--
-- Table structure for table `shifts`
--

CREATE TABLE `shifts` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `late_grace_period` int(11) DEFAULT 15,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `shifts`
--

INSERT INTO `shifts` (`id`, `name`, `start_time`, `end_time`, `late_grace_period`, `created_at`) VALUES
(1, 'General Shift', '09:00:00', '17:00:00', 15, '2026-02-13 05:00:35'),
(2, 'evening ', '16:32:00', '22:34:00', 15, '2026-02-16 03:32:18');

-- --------------------------------------------------------

--
-- Table structure for table `shift_swaps`
--

CREATE TABLE `shift_swaps` (
  `id` int(11) NOT NULL,
  `requester_id` int(11) NOT NULL,
  `requested_id` int(11) NOT NULL,
  `original_lecture_id` int(11) NOT NULL,
  `target_lecture_id` int(11) NOT NULL,
  `status` enum('Pending','Approved','Rejected','Cancelled') DEFAULT 'Pending',
  `reason` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`setting_key`, `setting_value`) VALUES
('footer_text', '© 2026 HR Management System. All rights reserved.'),
('system_logo', 'https://cdn-icons-png.flaticon.com/512/906/906343.png'),
('system_name', 'HR Management System'),
('theme_card_border', '#e2e8f0'),
('theme_font', '\'Outfit\', sans-serif'),
('theme_navbar_bg', '#ffffff'),
('theme_primary_color', '#2563eb'),
('theme_secondary_color', '#64748b'),
('theme_sidebar_accent', '#020617'),
('theme_sidebar_bg', '#ffffff');

-- --------------------------------------------------------

--
-- Table structure for table `sys_pages`
--

CREATE TABLE `sys_pages` (
  `id` int(11) NOT NULL,
  `parent_id` int(11) DEFAULT 0,
  `page_name` varchar(100) NOT NULL,
  `page_url` varchar(255) DEFAULT '#',
  `icon_class` varchar(50) DEFAULT 'bi bi-circle',
  `sort_order` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sys_pages`
--

INSERT INTO `sys_pages` (`id`, `parent_id`, `page_name`, `page_url`, `icon_class`, `sort_order`) VALUES
(1, 0, 'Dashboard', 'index.php', 'bi bi-speedometer2', 1),
(2, 0, 'System Management', '#', 'bi bi-gear-fill', 2),
(3, 2, 'Manage Users', 'dashboards/super_admin/manage_users.php', 'bi bi-people', 2),
(4, 2, 'Manage Roles', 'dashboards/super_admin/manage_roles.php', 'bi bi-shield-lock', 2),
(5, 2, 'Manage Pages', 'dashboards/super_admin/manage_pages.php', 'bi bi-file-earmark-text', 3),
(35, 0, 'Faculty Dashboard', 'dashboards/faculty/index.php', 'bi bi-speedometer2', 1),
(36, 0, 'Student Dashboard', 'dashboards/student/index.php', 'bi bi-speedometer2', 1),
(37, 0, 'Staff Dashboard', 'dashboards/staff/index.php', 'bi bi-speedometer2', 1),
(41, 0, 'Department Mangement', 'dashboards\\super_admin\\manage_departments.php', '', 0),
(42, 49, 'Manage Subjects', 'dashboards/super_admin/manage_subjects.php', 'bi bi-journal-text', 42),
(43, 0, 'Faculty Assignment', 'dashboards/super_admin/assign_faculty.php', 'bi bi-person-check', 43),
(45, 0, 'Leave Management', '#', 'bi bi-calendar3', 99),
(46, 45, 'Leave Category', 'dashboards/super_admin/leave_categories.php', 'bi bi-tags', 1),
(47, 45, 'Apply for Leave', 'dashboards/super_admin/apply_leave.php', 'bi bi-send-plus', 2),
(48, 45, 'Approval of Leave', 'dashboards/super_admin/approval_leave.php', 'bi bi-check2-all', 3),
(49, 0, 'Subject Management', '#', 'bi bi-book', 4),
(50, 49, 'Assign Departments', 'dashboards/super_admin/assign_departments.php', 'bi bi-person-lines-fill', 2);

-- --------------------------------------------------------

--
-- Table structure for table `sys_roles`
--

CREATE TABLE `sys_roles` (
  `id` int(11) NOT NULL,
  `role_name` varchar(50) NOT NULL,
  `role_key` varchar(50) NOT NULL,
  `is_system_role` tinyint(1) DEFAULT 0 COMMENT '1=Cannot Delete',
  `is_suspended` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sys_roles`
--

INSERT INTO `sys_roles` (`id`, `role_name`, `role_key`, `is_system_role`, `is_suspended`) VALUES
(1, 'Super Admin', 'super_admin', 1, 0),
(4, 'Suspended', 'suspended', 1, 0),
(7, 'staff', 'staff', 0, 0),
(10, 'faculty', 'faculty', 0, 0),
(11, 'Clerks', 'clerks', 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(50) NOT NULL,
  `identity_no` varchar(50) DEFAULT NULL,
  `registration_no` varchar(50) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `department_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `identity_no`, `registration_no`, `is_active`, `department_id`) VALUES
(1, 'Root Admin', 'admin@sys.com', '$2y$10$ilO4rzMV3dIvqBeyd8A8/OKDSY/8c.mbGw30pz2HKJybTu/UaooZ2', 'super_admin', '12345-1234567-1', 'ADM-001', 1, NULL),
(2, 'stuent', 'student@system.com', '$2y$10$GmDkYd/eioQE8pfU9oRKh.rzNnTR.tUEuUK1WQkELEHdP6VeDD7Bi', 'student', NULL, NULL, 1, NULL),
(3, 'staff', 'staff@system.com', '$2y$10$6u3f6qw3G8zJvmp/NveuduOq6lSgcpYaRayoiRuhA5mgQxx.bgNyu', 'staff', NULL, NULL, 1, NULL),
(4, 'faculty members', 'faculty@system.com', '$2y$10$ckrziK1oAfY54pMnLvM/XOd82s5QeXiEJM6si82sFhDv9rwySuPwW', 'faculty', NULL, NULL, 1, NULL),
(5, 'Umair Waqas', 'umairwaqas45@gmail.com', '$2y$10$jkbr3fMnd6AzjGtCF7JmWeS4jy99yQkVuUEhqpQSo1052MnznMB3C', 'faculty', NULL, NULL, 1, NULL),
(6, 'Clerks', 'clerk@system.com', '$2y$10$BHtD.VaZq/Qrwbpm9qzgBODHr1DQGgkTvvKADUG/nP9KaWBsu2NWu', 'clerks', NULL, NULL, 1, NULL),
(7, 'Dr Waris', 'waris@system.com', '$2y$10$N/RZZJw4ME9FcuFtLvXg/ejs/URHUqA8Hb/yUtE6ElNTGrd.UUYBO', 'staff', NULL, NULL, 1, NULL),
(8, 'Dr Atif Gill', 'atif@system.com', '$2y$10$N/RZZJw4ME9FcuFtLvXg/ejs/URHUqA8Hb/yUtE6ElNTGrd.UUYBO', 'staff', NULL, NULL, 1, 13),
(9, 'Mam Saira Aziz', 'saira@system.com', '$2y$10$N/RZZJw4ME9FcuFtLvXg/ejs/URHUqA8Hb/yUtE6ElNTGrd.UUYBO', 'staff', NULL, NULL, 1, NULL),
(10, 'Mam Amara Saleem', 'amara@system.com', '$2y$10$N/RZZJw4ME9FcuFtLvXg/ejs/URHUqA8Hb/yUtE6ElNTGrd.UUYBO', 'staff', NULL, NULL, 1, 12),
(11, 'Bilal', 'bilal@system.com', '$2y$10$LB6TUtKFpei/srl1CgflUuKoFg8KzQILmi1WriLNrZ/TBIOVj.NB6', 'clerks', '0000000000', 'emp 10', 1, NULL),
(13, 'MR Umair waqas', 'umairwaqas75@gmail.com', '$2y$10$zD6Rilz3LZlZtK8d4yL2Huom8xQLZVaxTRxxyAJJfhUY4XNOo.ppi', 'staff', NULL, NULL, 1, NULL),
(14, 'Qasim', 'qasim@sys.com', '$2y$10$9VX0KDb4XVaKgK2Ozw2aGOfH0yHh/T7H8JVxzVKPeSkTXPBPyWdLq', 'clerks', NULL, NULL, 1, NULL),
(15, 'Dr shafique', 'shafique@sys.com', '$2y$10$zToCwJk9vA/LC4JWEYUFaOW/VxcmygoL/S0hYuHRRLbZZvo5UCDWe', 'staff', NULL, NULL, 1, NULL),
(17, 'afifa', 'afifa@edu.com', '$2y$10$5zIawzfGGgWh1xDIaHMR6.qpIAh6EuzgIfLVG9AAwaqtV775VTkoy', 'staff', '1111111111', '11111', 1, 15);

-- --------------------------------------------------------

--
-- Table structure for table `user_profiles`
--

CREATE TABLE `user_profiles` (
  `user_id` int(11) NOT NULL,
  `designation` varchar(150) DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL,
  `joining_date` date DEFAULT NULL,
  `tenure_status` enum('Tenured','Non-Tenured','Not Applicable') DEFAULT 'Not Applicable',
  `confirmation_status` enum('Confirmed','Probation') DEFAULT 'Probation',
  `last_promotion_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_profiles`
--

INSERT INTO `user_profiles` (`user_id`, `designation`, `department_id`, `joining_date`, `tenure_status`, `confirmation_status`, `last_promotion_date`) VALUES
(4, '', NULL, NULL, 'Not Applicable', 'Probation', NULL),
(5, '', 13, NULL, 'Not Applicable', 'Probation', NULL),
(7, 'Hod', 13, NULL, 'Not Applicable', 'Probation', NULL),
(8, '', 13, NULL, 'Not Applicable', 'Probation', NULL),
(9, '', NULL, NULL, 'Not Applicable', 'Probation', NULL),
(10, '', 13, NULL, 'Not Applicable', 'Probation', NULL),
(13, 'Teachers', 7, '2025-01-12', 'Not Applicable', 'Confirmed', '2026-06-05'),
(14, 'clerk', 13, NULL, 'Not Applicable', 'Probation', NULL),
(15, 'Hod', 7, NULL, 'Not Applicable', 'Probation', NULL),
(17, 'professor', 15, NULL, 'Not Applicable', 'Probation', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`),
  ADD KEY `department_id` (`department_id`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `employee_shifts`
--
ALTER TABLE `employee_shifts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `faculty_subjects`
--
ALTER TABLE `faculty_subjects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_course` (`user_id`,`course_id`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `faculty_workload_config`
--
ALTER TABLE `faculty_workload_config`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `holidays`
--
ALTER TABLE `holidays`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `leave_applications`
--
ALTER TABLE `leave_applications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `leave_categories`
--
ALTER TABLE `leave_categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `lectures`
--
ALTER TABLE `lectures`
  ADD PRIMARY KEY (`id`),
  ADD KEY `course_id` (`course_id`),
  ADD KEY `teacher_id` (`teacher_id`),
  ADD KEY `room_id` (`room_id`);

--
-- Indexes for table `role_access`
--
ALTER TABLE `role_access`
  ADD PRIMARY KEY (`role_key`,`page_id`);

--
-- Indexes for table `rooms`
--
ALTER TABLE `rooms`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `room_number` (`room_number`),
  ADD KEY `department_id` (`department_id`);

--
-- Indexes for table `shifts`
--
ALTER TABLE `shifts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `shift_swaps`
--
ALTER TABLE `shift_swaps`
  ADD PRIMARY KEY (`id`),
  ADD KEY `requester_id` (`requester_id`),
  ADD KEY `requested_id` (`requested_id`),
  ADD KEY `original_lecture_id` (`original_lecture_id`),
  ADD KEY `target_lecture_id` (`target_lecture_id`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`setting_key`);

--
-- Indexes for table `sys_pages`
--
ALTER TABLE `sys_pages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sys_roles`
--
ALTER TABLE `sys_roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `role_key` (`role_key`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `idx_email` (`email`),
  ADD UNIQUE KEY `idx_identity` (`identity_no`),
  ADD UNIQUE KEY `idx_reg_no` (`registration_no`),
  ADD KEY `role` (`role`),
  ADD KEY `fk_user_department` (`department_id`);

--
-- Indexes for table `user_profiles`
--
ALTER TABLE `user_profiles`
  ADD PRIMARY KEY (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `courses`
--
ALTER TABLE `courses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `employee_shifts`
--
ALTER TABLE `employee_shifts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `faculty_subjects`
--
ALTER TABLE `faculty_subjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `faculty_workload_config`
--
ALTER TABLE `faculty_workload_config`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `holidays`
--
ALTER TABLE `holidays`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `leave_applications`
--
ALTER TABLE `leave_applications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `leave_categories`
--
ALTER TABLE `leave_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `lectures`
--
ALTER TABLE `lectures`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `rooms`
--
ALTER TABLE `rooms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `shifts`
--
ALTER TABLE `shifts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `shift_swaps`
--
ALTER TABLE `shift_swaps`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sys_pages`
--
ALTER TABLE `sys_pages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT for table `sys_roles`
--
ALTER TABLE `sys_roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `courses`
--
ALTER TABLE `courses`
  ADD CONSTRAINT `courses_ibfk_1` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `faculty_subjects`
--
ALTER TABLE `faculty_subjects`
  ADD CONSTRAINT `faculty_subjects_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `faculty_subjects_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `faculty_workload_config`
--
ALTER TABLE `faculty_workload_config`
  ADD CONSTRAINT `faculty_workload_config_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `leave_applications`
--
ALTER TABLE `leave_applications`
  ADD CONSTRAINT `leave_applications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `leave_applications_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `leave_categories` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `lectures`
--
ALTER TABLE `lectures`
  ADD CONSTRAINT `lectures_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `lectures_ibfk_2` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `lectures_ibfk_3` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `rooms`
--
ALTER TABLE `rooms`
  ADD CONSTRAINT `rooms_ibfk_1` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `shift_swaps`
--
ALTER TABLE `shift_swaps`
  ADD CONSTRAINT `shift_swaps_ibfk_1` FOREIGN KEY (`requester_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `shift_swaps_ibfk_2` FOREIGN KEY (`requested_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `shift_swaps_ibfk_3` FOREIGN KEY (`original_lecture_id`) REFERENCES `lectures` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `shift_swaps_ibfk_4` FOREIGN KEY (`target_lecture_id`) REFERENCES `lectures` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_user_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `user_profiles`
--
ALTER TABLE `user_profiles`
  ADD CONSTRAINT `user_profiles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
