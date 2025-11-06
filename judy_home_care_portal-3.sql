-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3307
-- Generation Time: Nov 06, 2025 at 02:41 PM
-- Server version: 8.0.40
-- PHP Version: 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `judy_home_care_portal`
--

-- --------------------------------------------------------

--
-- Table structure for table `cache`
--

CREATE TABLE `cache` (
  `key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cache`
--

INSERT INTO `cache` (`key`, `value`, `expiration`) VALUES
('judy-home-healthcare-cache-geocode_short_5.6647_-0.2196', 's:11:\"Dome, Accra\";', 1764345052),
('judy-home-healthcare-cache-geocode_short_5.6648_-0.2196', 's:11:\"Dome, Accra\";', 1764526813);

-- --------------------------------------------------------

--
-- Table structure for table `cache_locks`
--

CREATE TABLE `cache_locks` (
  `key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `care_assignments`
--

CREATE TABLE `care_assignments` (
  `id` bigint UNSIGNED NOT NULL,
  `care_plan_id` bigint UNSIGNED NOT NULL,
  `patient_id` bigint UNSIGNED NOT NULL,
  `primary_nurse_id` bigint UNSIGNED NOT NULL,
  `secondary_nurse_id` bigint UNSIGNED DEFAULT NULL,
  `assigned_by` bigint UNSIGNED NOT NULL,
  `approved_by` bigint UNSIGNED DEFAULT NULL,
  `status` enum('pending','nurse_review','accepted','declined','active','on_hold','completed','cancelled','reassigned') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `assignment_type` enum('single_nurse','dual_nurse','team_care','rotating_care','emergency_assignment') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'single_nurse',
  `assigned_at` timestamp NOT NULL,
  `start_date` timestamp NOT NULL,
  `end_date` timestamp NULL DEFAULT NULL,
  `accepted_at` timestamp NULL DEFAULT NULL,
  `declined_at` timestamp NULL DEFAULT NULL,
  `assignment_notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `special_requirements` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `nurse_qualifications_matched` json DEFAULT NULL,
  `nurse_response_notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `decline_reason` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `response_time_hours` int DEFAULT NULL,
  `patient_address` json DEFAULT NULL,
  `estimated_travel_time` decimal(5,2) DEFAULT NULL,
  `distance_km` decimal(8,2) DEFAULT NULL,
  `estimated_hours_per_day` int DEFAULT NULL,
  `total_estimated_hours` int DEFAULT NULL,
  `intensity_level` enum('light','moderate','intensive','critical') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'moderate',
  `skill_match_score` int DEFAULT NULL,
  `location_match_score` int DEFAULT NULL,
  `availability_match_score` int DEFAULT NULL,
  `workload_balance_score` int DEFAULT NULL,
  `admin_override` tinyint(1) NOT NULL DEFAULT '0',
  `admin_override_reason` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `admin_override_at` timestamp NULL DEFAULT NULL,
  `overall_match_score` int DEFAULT NULL,
  `previous_assignment_id` bigint UNSIGNED DEFAULT NULL,
  `reassignment_count` int NOT NULL DEFAULT '0',
  `reassignment_reason` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `actual_start_date` timestamp NULL DEFAULT NULL,
  `actual_end_date` timestamp NULL DEFAULT NULL,
  `completion_percentage` int NOT NULL DEFAULT '0',
  `performance_metrics` json DEFAULT NULL,
  `is_emergency` tinyint(1) NOT NULL DEFAULT '0',
  `priority_level` enum('low','medium','high','urgent') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'medium',
  `emergency_assigned_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `admin_override_by` bigint UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `care_fee_structure`
--

CREATE TABLE `care_fee_structure` (
  `id` bigint UNSIGNED NOT NULL,
  `fee_type` enum('assessment_fee','hourly_rate','daily_rate','package') COLLATE utf8mb4_unicode_ci NOT NULL,
  `care_type` enum('general_nursing','elderly_care','post_surgical','chronic_disease','palliative_care','rehabilitation','wound_care','medication_management','all') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'all',
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `base_amount` decimal(10,2) NOT NULL,
  `currency` varchar(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'GHS',
  `tax_percentage` decimal(5,2) NOT NULL DEFAULT '0.00',
  `min_hours` int DEFAULT NULL,
  `max_hours` int DEFAULT NULL,
  `duration_days` int DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `valid_from` date DEFAULT NULL,
  `valid_until` date DEFAULT NULL,
  `region_overrides` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `care_fee_structure`
--

INSERT INTO `care_fee_structure` (`id`, `fee_type`, `care_type`, `name`, `description`, `base_amount`, `currency`, `tax_percentage`, `min_hours`, `max_hours`, `duration_days`, `is_active`, `valid_from`, `valid_until`, `region_overrides`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'assessment_fee', 'all', 'Home Care Assessment Fee', 'One-time fee for initial home care assessment by a qualified nurse', 150.00, 'GHS', 0.00, NULL, NULL, NULL, 1, NULL, NULL, NULL, '2025-11-04 12:59:28', '2025-11-04 12:59:28', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `care_payments`
--

CREATE TABLE `care_payments` (
  `id` bigint UNSIGNED NOT NULL,
  `care_request_id` bigint UNSIGNED NOT NULL,
  `patient_id` bigint UNSIGNED NOT NULL,
  `payment_type` enum('assessment_fee','care_fee') COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `currency` varchar(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'GHS',
  `tax_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total_amount` decimal(10,2) NOT NULL,
  `payment_method` enum('mobile_money','card','bank_transfer','cash','insurance') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_provider` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `transaction_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reference_number` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `provider_reference` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('pending','processing','completed','failed','refunded','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `description` text COLLATE utf8mb4_unicode_ci,
  `failure_reason` text COLLATE utf8mb4_unicode_ci,
  `metadata` json DEFAULT NULL,
  `paid_at` timestamp NULL DEFAULT NULL,
  `refunded_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `care_payments`
--

INSERT INTO `care_payments` (`id`, `care_request_id`, `patient_id`, `payment_type`, `amount`, `currency`, `tax_amount`, `total_amount`, `payment_method`, `payment_provider`, `transaction_id`, `reference_number`, `provider_reference`, `status`, `description`, `failure_reason`, `metadata`, `paid_at`, `refunded_at`, `expires_at`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 3, 5, 'assessment_fee', 150.00, 'GHS', 0.00, 150.00, NULL, NULL, NULL, 'PAY-WGKS3T2WENVG', NULL, 'pending', 'Home Care Assessment Fee', NULL, NULL, NULL, NULL, '2025-11-04 16:34:03', '2025-11-04 16:04:03', '2025-11-04 16:04:03', NULL),
(2, 4, 5, 'assessment_fee', 150.00, 'GHS', 0.00, 150.00, NULL, NULL, NULL, 'PAY-5EPS4T6BLA5S', NULL, 'pending', 'Home Care Assessment Fee', NULL, NULL, NULL, NULL, '2025-11-04 16:34:17', '2025-11-04 16:04:17', '2025-11-04 16:04:17', NULL),
(3, 5, 5, 'assessment_fee', 150.00, 'GHS', 0.00, 150.00, NULL, NULL, NULL, 'PAY-IPZT9XXWQXDE', NULL, 'pending', 'Home Care Assessment Fee', NULL, NULL, NULL, NULL, '2025-11-04 16:38:53', '2025-11-04 16:08:53', '2025-11-04 16:08:53', NULL),
(4, 6, 5, 'assessment_fee', 150.00, 'GHS', 0.00, 150.00, NULL, NULL, NULL, 'PAY-SZLEODGKGEKL', NULL, 'pending', 'Home Care Assessment Fee', NULL, NULL, NULL, NULL, '2025-11-04 16:39:00', '2025-11-04 16:09:00', '2025-11-04 16:09:00', NULL),
(5, 7, 5, 'assessment_fee', 150.00, 'GHS', 0.00, 150.00, NULL, NULL, NULL, 'PAY-OM8OWBR6XPEN', NULL, 'pending', 'Home Care Assessment Fee', NULL, NULL, NULL, NULL, '2025-11-04 16:39:16', '2025-11-04 16:09:16', '2025-11-04 16:09:16', NULL),
(6, 8, 5, 'assessment_fee', 150.00, 'GHS', 0.00, 150.00, NULL, NULL, NULL, 'PAY-HP6DMBL2GXCG', NULL, 'pending', 'Home Care Assessment Fee', NULL, NULL, NULL, NULL, '2025-11-04 16:39:28', '2025-11-04 16:09:28', '2025-11-04 16:09:28', NULL),
(7, 9, 5, 'assessment_fee', 150.00, 'GHS', 0.00, 150.00, NULL, NULL, NULL, 'PAY-TUDNMSJAMQZN', NULL, 'pending', 'Home Care Assessment Fee', NULL, NULL, NULL, NULL, '2025-11-04 19:30:28', '2025-11-04 19:00:28', '2025-11-04 19:00:28', NULL),
(8, 10, 5, 'assessment_fee', 150.00, 'GHS', 0.00, 150.00, NULL, NULL, NULL, 'PAY-BW7HINCIOM1Q', NULL, 'pending', 'Home Care Assessment Fee', NULL, NULL, NULL, NULL, '2025-11-04 19:37:38', '2025-11-04 19:07:38', '2025-11-04 19:07:38', NULL),
(9, 11, 5, 'assessment_fee', 150.00, 'GHS', 0.00, 150.00, NULL, NULL, NULL, 'PAY-9XIDY1PYFEWR', NULL, 'pending', 'Home Care Assessment Fee', NULL, NULL, NULL, NULL, '2025-11-04 19:49:03', '2025-11-04 19:19:03', '2025-11-04 19:19:03', NULL),
(10, 12, 5, 'assessment_fee', 150.00, 'GHS', 0.00, 150.00, NULL, NULL, NULL, 'PAY-KPZDR94CHHUV', NULL, 'pending', 'Home Care Assessment Fee', NULL, NULL, NULL, NULL, '2025-11-04 20:09:00', '2025-11-04 19:39:00', '2025-11-04 19:39:00', NULL),
(11, 13, 5, 'assessment_fee', 150.00, 'GHS', 0.00, 150.00, NULL, NULL, '5500467445', 'PAY-Z5BGQIYKRUY3', 'behoeu044uc77or', 'completed', 'Home Care Assessment Fee', NULL, '{\"channel\": \"card\", \"paid_at\": \"2025-11-04T19:41:00.000Z\", \"verified_at\": \"2025-11-04T19:41:03.024262Z\", \"customer_code\": \"CUS_6p0wtyagwokoavy\"}', '2025-11-04 19:41:03', NULL, '2025-11-04 20:10:36', '2025-11-04 19:40:36', '2025-11-04 19:41:03', NULL),
(12, 14, 5, 'assessment_fee', 150.00, 'GHS', 0.00, 150.00, NULL, NULL, NULL, 'PAY-CLKVKNBNA0AI', 'lwkjzsfmxm8gqz6', 'processing', 'Home Care Assessment Fee', NULL, NULL, NULL, NULL, '2025-11-05 02:04:44', '2025-11-05 01:34:44', '2025-11-05 01:41:11', NULL),
(13, 15, 5, 'assessment_fee', 150.00, 'GHS', 0.00, 150.00, NULL, NULL, NULL, 'PAY-N8PPGAKOASQK', 'gx0v57soor7rxd6', 'processing', 'Home Care Assessment Fee', NULL, NULL, NULL, NULL, '2025-11-05 02:12:22', '2025-11-05 01:42:22', '2025-11-05 01:47:59', NULL),
(14, 16, 5, 'assessment_fee', 150.00, 'GHS', 0.00, 150.00, NULL, NULL, NULL, 'PAY-LKOMDCRA421F', NULL, 'pending', 'Home Care Assessment Fee', NULL, NULL, NULL, NULL, '2025-11-05 02:18:14', '2025-11-05 01:48:14', '2025-11-05 01:48:14', NULL),
(15, 17, 5, 'assessment_fee', 150.00, 'GHS', 0.00, 150.00, NULL, NULL, '5501107019', 'PAY-LQRSZLFNV61D', 'jepf3ipgin2995h', 'completed', 'Home Care Assessment Fee', NULL, '{\"channel\": \"card\", \"paid_at\": \"2025-11-05T02:01:48.000Z\", \"verified_at\": \"2025-11-05T02:01:50.575263Z\", \"customer_code\": \"CUS_6p0wtyagwokoavy\"}', '2025-11-05 02:01:50', NULL, '2025-11-05 02:31:24', '2025-11-05 02:01:24', '2025-11-05 02:01:50', NULL),
(16, 18, 5, 'assessment_fee', 150.00, 'GHS', 0.00, 150.00, NULL, NULL, '5501109543', 'PAY-MWP6DKXMS8MH', '2a8fhlhm02j7wh5', 'completed', 'Home Care Assessment Fee', NULL, '{\"channel\": \"card\", \"paid_at\": \"2025-11-05T02:05:12.000Z\", \"verified_at\": \"2025-11-05T02:05:14.815175Z\", \"customer_code\": \"CUS_6p0wtyagwokoavy\"}', '2025-11-05 02:05:14', NULL, '2025-11-05 02:32:47', '2025-11-05 02:02:47', '2025-11-05 02:05:14', NULL),
(17, 19, 5, 'assessment_fee', 150.00, 'GHS', 0.00, 150.00, NULL, NULL, NULL, 'PAY-U0US7U93VFJG', 'xqpy9lpf0izupv1', 'processing', 'Home Care Assessment Fee', NULL, NULL, NULL, NULL, '2025-11-05 02:37:24', '2025-11-05 02:07:24', '2025-11-05 02:07:37', NULL),
(18, 20, 5, 'assessment_fee', 150.00, 'GHS', 0.00, 150.00, NULL, NULL, NULL, 'PAY-9RPYLR7IXABB', 'w2ro7jiamkqkx8w', 'processing', 'Home Care Assessment Fee', NULL, NULL, NULL, NULL, '2025-11-05 02:38:47', '2025-11-05 02:08:47', '2025-11-05 02:08:57', NULL),
(19, 21, 5, 'assessment_fee', 150.00, 'GHS', 0.00, 150.00, NULL, NULL, NULL, 'PAY-GBJZWB44IND2', 'apuqy855ysaci9k', 'processing', 'Home Care Assessment Fee', NULL, NULL, NULL, NULL, '2025-11-05 03:09:22', '2025-11-05 02:39:22', '2025-11-05 02:40:07', NULL),
(20, 22, 5, 'assessment_fee', 150.00, 'GHS', 0.00, 150.00, NULL, NULL, NULL, 'PAY-SIXOEJAE00LL', 'jpyo5n8z28gsvni', 'processing', 'Home Care Assessment Fee', NULL, NULL, NULL, NULL, '2025-11-05 03:21:36', '2025-11-05 02:51:36', '2025-11-05 02:52:12', NULL),
(21, 23, 5, 'assessment_fee', 150.00, 'GHS', 0.00, 150.00, NULL, NULL, '5501147525', 'PAY-M9KA52KFVC3K', '331ik1b0m2vf4kz', 'completed', 'Home Care Assessment Fee', NULL, '{\"channel\": \"card\", \"paid_at\": \"2025-11-05T02:54:47.000Z\", \"verified_at\": \"2025-11-05T02:54:50.134989Z\", \"customer_code\": \"CUS_6p0wtyagwokoavy\"}', '2025-11-05 02:54:50', NULL, '2025-11-05 03:22:24', '2025-11-05 02:52:24', '2025-11-05 02:54:50', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `care_plans`
--

CREATE TABLE `care_plans` (
  `id` bigint UNSIGNED NOT NULL,
  `patient_id` bigint UNSIGNED NOT NULL,
  `doctor_id` bigint UNSIGNED DEFAULT NULL,
  `created_by` bigint UNSIGNED NOT NULL,
  `approved_by` bigint UNSIGNED DEFAULT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `care_type` enum('general_care','elderly_care','pediatric_care','post_surgery_care','chronic_disease_management','palliative_care','rehabilitation_care') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('draft','pending_approval','approved','active','on_hold','completed','cancelled') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `frequency` enum('once_daily','twice_daily','three_times_daily','monthly','every_12_hours','every_8_hours','every_6_hours','every_4_hours','weekly','twice_weekly','as_needed','custom') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `custom_frequency_details` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `care_tasks` json NOT NULL,
  `completed_tasks` json DEFAULT NULL,
  `priority` enum('low','medium','high','critical') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'medium',
  `approved_at` timestamp NULL DEFAULT NULL,
  `completion_percentage` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `primary_nurse_id` bigint UNSIGNED DEFAULT NULL,
  `secondary_nurse_id` bigint UNSIGNED DEFAULT NULL,
  `assignment_notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `estimated_hours_per_day` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `care_plans`
--

INSERT INTO `care_plans` (`id`, `patient_id`, `doctor_id`, `created_by`, `approved_by`, `title`, `description`, `care_type`, `status`, `start_date`, `end_date`, `frequency`, `custom_frequency_details`, `care_tasks`, `completed_tasks`, `priority`, `approved_at`, `completion_percentage`, `created_at`, `updated_at`, `deleted_at`, `primary_nurse_id`, `secondary_nurse_id`, `assignment_notes`, `estimated_hours_per_day`) VALUES
(257, 5, 69, 69, NULL, 'Palliative Care Plan - Robert Ben Brown', 'Comfort-focused care emphasizing pain and symptom management, quality of life, and dignity for patients with serious illness. Holistic approach including physical, emotional, and spiritual support for patient and family.', 'palliative_care', 'draft', '2025-10-17', NULL, 'custom', 'Every other day at 9:00 AM and 3:00 PM', '[\"Provide comprehensive pain assessment and management\", \"Manage symptoms for maximum comfort (nausea, breathlessness, etc.)\", \"Assist with all personal care needs gently and respectfully\", \"Provide emotional and spiritual support\", \"Support family members and answer questions\", \"Coordinate with hospice or palliative care team\", \"Maintain patient dignity and respect wishes\", \"Create peaceful, comfortable environment\", \"Offer comfort measures (positioning, massage, music)\", \"Document comfort levels and symptom management\"]', NULL, 'medium', NULL, 0, '2025-10-07 00:00:00', '2025-10-14 23:09:06', NULL, NULL, NULL, NULL, NULL),
(258, 37, 75, 75, NULL, 'Palliative Care Plan - Adrienne Dickinson', 'Comfort-focused care emphasizing pain and symptom management, quality of life, and dignity for patients with serious illness. Holistic approach including physical, emotional, and spiritual support for patient and family.', 'palliative_care', 'draft', '2025-10-26', '2026-01-08', 'once_daily', NULL, '[\"Provide comprehensive pain assessment and management\", \"Manage symptoms for maximum comfort (nausea, breathlessness, etc.)\", \"Assist with all personal care needs gently and respectfully\", \"Provide emotional and spiritual support\", \"Support family members and answer questions\", \"Coordinate with hospice or palliative care team\", \"Maintain patient dignity and respect wishes\", \"Create peaceful, comfortable environment\", \"Offer comfort measures (positioning, massage, music)\", \"Document comfort levels and symptom management\"]', NULL, 'medium', NULL, 0, '2025-10-07 00:00:00', '2025-10-14 23:09:06', NULL, NULL, NULL, NULL, NULL),
(259, 52, 73, 2, NULL, 'General Home Care Plan - Murphy Nienow', 'Comprehensive general care including daily living assistance, medication management, and basic health monitoring. This plan focuses on maintaining patient independence while providing necessary support for activities of daily living.', 'general_care', 'draft', '2025-10-22', '2025-11-30', 'as_needed', NULL, '[\"Assist with personal hygiene and grooming\", \"Help with meal preparation and feeding as needed\", \"Administer prescribed medications on schedule\", \"Monitor and record vital signs daily\", \"Assist with mobility and safe transfers\", \"Provide companionship and emotional support\", \"Maintain clean and safe living environment\", \"Document daily activities and observations\"]', NULL, 'critical', NULL, 0, '2025-10-10 00:00:00', '2025-10-14 23:09:06', NULL, NULL, NULL, NULL, NULL),
(260, 19, 73, 73, NULL, 'Chronic Disease Management Plan - Ray Wintheiser', 'Comprehensive management of chronic conditions with focus on medication compliance, symptom monitoring, lifestyle modifications, and preventing complications. Coordinated care approach with healthcare team.', 'chronic_disease_management', 'draft', '2025-10-25', '2026-06-08', 'twice_weekly', NULL, '[\"Administer complex medication regimens accurately\", \"Monitor disease-specific symptoms and vital signs\", \"Record detailed health metrics and trends\", \"Assist with specialized medical equipment\", \"Support prescribed dietary modifications\", \"Coordinate with healthcare providers and specialists\", \"Provide patient education on disease self-management\", \"Monitor for medication side effects or complications\", \"Encourage adherence to treatment plans\", \"Document all health changes and communicate promptly\"]', NULL, 'medium', NULL, 0, '2025-10-08 00:00:00', '2025-10-14 23:09:06', NULL, NULL, NULL, NULL, NULL),
(261, 39, 80, 80, NULL, 'Elderly Care Plan - Jarvis Dicki', 'Specialized care for elderly patients focusing on dignity, independence, and quality of life. Includes comprehensive support for age-related needs, fall prevention, cognitive engagement, and social interaction.', 'elderly_care', 'draft', '2025-10-16', '2026-06-26', 'once_daily', NULL, '[\"Assist with bathing and personal care with dignity\", \"Help with dressing and grooming routines\", \"Prepare nutritious meals appropriate for dietary needs\", \"Administer medications and track medication schedule\", \"Provide mobility support and fall prevention measures\", \"Engage in cognitive stimulation activities\", \"Monitor for signs of confusion or changes in behavior\", \"Encourage social interaction and meaningful activities\", \"Regular position changes to prevent pressure sores\", \"Document any changes in condition immediately\"]', NULL, 'high', NULL, 0, '2025-10-09 00:00:00', '2025-10-14 23:09:06', NULL, NULL, NULL, NULL, NULL),
(262, 22, 69, 69, NULL, 'General Home Care Plan - Samara Bergnaum', 'Comprehensive general care including daily living assistance, medication management, and basic health monitoring. This plan focuses on maintaining patient independence while providing necessary support for activities of daily living.', 'general_care', 'pending_approval', '2025-10-15', '2025-12-25', 'weekly', NULL, '[\"Assist with personal hygiene and grooming\", \"Help with meal preparation and feeding as needed\", \"Administer prescribed medications on schedule\", \"Monitor and record vital signs daily\", \"Assist with mobility and safe transfers\", \"Provide companionship and emotional support\", \"Maintain clean and safe living environment\", \"Document daily activities and observations\"]', NULL, 'high', NULL, 0, '2025-10-07 00:00:00', '2025-10-14 23:09:06', NULL, NULL, NULL, NULL, NULL),
(263, 42, 80, 2, NULL, 'Rehabilitation Care Plan - Desiree Mraz', 'Goal-oriented care focused on recovering function, improving strength, and regaining independence after illness or injury. Progressive approach with therapeutic exercises, mobility training, and continuous progress monitoring.', 'rehabilitation_care', 'pending_approval', '2025-10-15', NULL, 'twice_daily', NULL, '[\"Assist with prescribed rehabilitation exercises\", \"Support physical therapy activities and goals\", \"Help with occupational therapy tasks\", \"Monitor progress toward specific recovery goals\", \"Encourage increasing independence in daily activities\", \"Prevent complications during recovery period\", \"Provide motivation and positive reinforcement\", \"Assist with adaptive equipment and techniques\", \"Document functional improvements and setbacks\", \"Coordinate with therapy team on progress\"]', NULL, 'low', NULL, 0, '2025-09-25 00:00:00', '2025-10-14 23:09:06', NULL, NULL, NULL, NULL, NULL),
(264, 26, 73, 81, NULL, 'Chronic Disease Management Plan - Felix Cassin', 'Comprehensive management of chronic conditions with focus on medication compliance, symptom monitoring, lifestyle modifications, and preventing complications. Coordinated care approach with healthcare team.', 'chronic_disease_management', 'pending_approval', '2025-10-20', '2026-03-18', 'three_times_daily', NULL, '[\"Administer complex medication regimens accurately\", \"Monitor disease-specific symptoms and vital signs\", \"Record detailed health metrics and trends\", \"Assist with specialized medical equipment\", \"Support prescribed dietary modifications\", \"Coordinate with healthcare providers and specialists\", \"Provide patient education on disease self-management\", \"Monitor for medication side effects or complications\", \"Encourage adherence to treatment plans\", \"Document all health changes and communicate promptly\"]', NULL, 'low', NULL, 0, '2025-10-03 00:00:00', '2025-10-14 23:09:06', NULL, NULL, NULL, NULL, NULL),
(265, 30, 77, 77, NULL, 'Elderly Care Plan - Loma Beatty', 'Specialized care for elderly patients focusing on dignity, independence, and quality of life. Includes comprehensive support for age-related needs, fall prevention, cognitive engagement, and social interaction.', 'elderly_care', 'pending_approval', '2025-10-26', '2026-09-09', 'twice_daily', NULL, '[\"Assist with bathing and personal care with dignity\", \"Help with dressing and grooming routines\", \"Prepare nutritious meals appropriate for dietary needs\", \"Administer medications and track medication schedule\", \"Provide mobility support and fall prevention measures\", \"Engage in cognitive stimulation activities\", \"Monitor for signs of confusion or changes in behavior\", \"Encourage social interaction and meaningful activities\", \"Regular position changes to prevent pressure sores\", \"Document any changes in condition immediately\"]', NULL, 'medium', NULL, 0, '2025-10-06 00:00:00', '2025-10-14 23:09:06', NULL, NULL, NULL, NULL, NULL),
(266, 45, 74, 74, NULL, 'Rehabilitation Care Plan - Felipe Schowalter', 'Goal-oriented care focused on recovering function, improving strength, and regaining independence after illness or injury. Progressive approach with therapeutic exercises, mobility training, and continuous progress monitoring.', 'rehabilitation_care', 'pending_approval', '2025-10-18', '2026-01-12', 'every_12_hours', NULL, '[\"Assist with prescribed rehabilitation exercises\", \"Support physical therapy activities and goals\", \"Help with occupational therapy tasks\", \"Monitor progress toward specific recovery goals\", \"Encourage increasing independence in daily activities\", \"Prevent complications during recovery period\", \"Provide motivation and positive reinforcement\", \"Assist with adaptive equipment and techniques\", \"Document functional improvements and setbacks\", \"Coordinate with therapy team on progress\"]', NULL, 'medium', NULL, 0, '2025-10-02 00:00:00', '2025-10-14 23:09:06', NULL, NULL, NULL, NULL, NULL),
(267, 34, 72, 72, 81, 'Chronic Disease Management Plan - Tod Huels', 'Comprehensive management of chronic conditions with focus on medication compliance, symptom monitoring, lifestyle modifications, and preventing complications. Coordinated care approach with healthcare team.', 'chronic_disease_management', 'active', '2025-09-22', NULL, 'once_daily', NULL, '[\"Administer complex medication regimens accurately\", \"Monitor disease-specific symptoms and vital signs\", \"Record detailed health metrics and trends\", \"Assist with specialized medical equipment\", \"Support prescribed dietary modifications\", \"Coordinate with healthcare providers and specialists\", \"Provide patient education on disease self-management\", \"Monitor for medication side effects or complications\", \"Encourage adherence to treatment plans\", \"Document all health changes and communicate promptly\"]', NULL, 'low', '2025-09-20 00:00:00', 45, '2025-09-13 00:00:00', '2025-10-14 23:09:06', NULL, 61, 56, 'Strict medication adherence is critical for disease management. Monitor symptoms closely and report any changes immediately to the healthcare team. Patient education ongoing.', NULL),
(268, 43, 75, 75, 2, 'General Home Care Plan - Katlyn Schneider', 'Comprehensive general care including daily living assistance, medication management, and basic health monitoring. This plan focuses on maintaining patient independence while providing necessary support for activities of daily living.', 'general_care', 'active', '2025-09-11', '2025-10-12', 'three_times_daily', NULL, '[\"Assist with personal hygiene and grooming\", \"Help with meal preparation and feeding as needed\", \"Administer prescribed medications on schedule\", \"Monitor and record vital signs daily\", \"Assist with mobility and safe transfers\", \"Provide companionship and emotional support\", \"Maintain clean and safe living environment\", \"Document daily activities and observations\"]', NULL, 'low', '2025-09-09 00:00:00', 67, '2025-08-30 00:00:00', '2025-10-14 23:09:06', NULL, 57, NULL, 'Patient requires gentle, patient-centered care. Please maintain consistent schedule and document any changes in condition or behavior.', NULL),
(269, 36, 77, 77, 1, 'General Home Care Plan - Mauricio Batz', 'Comprehensive general care including daily living assistance, medication management, and basic health monitoring. This plan focuses on maintaining patient independence while providing necessary support for activities of daily living.', 'general_care', 'active', '2025-09-29', '2025-11-05', 'every_8_hours', NULL, '[\"Assist with personal hygiene and grooming\", \"Help with meal preparation and feeding as needed\", \"Administer prescribed medications on schedule\", \"Monitor and record vital signs daily\", \"Assist with mobility and safe transfers\", \"Provide companionship and emotional support\", \"Maintain clean and safe living environment\", \"Document daily activities and observations\"]', NULL, 'critical', '2025-09-26 00:00:00', 31, '2025-09-11 00:00:00', '2025-10-14 23:09:06', NULL, 65, NULL, 'Patient requires gentle, patient-centered care. Please maintain consistent schedule and document any changes in condition or behavior.', NULL),
(270, 7, 74, 2, 2, 'General Home Care Plan - Granit Xhaka', 'Comprehensive general care including daily living assistance, medication management, and basic health monitoring. This plan focuses on maintaining patient independence while providing necessary support for activities of daily living.', 'general_care', 'active', '2025-09-07', '2025-11-11', 'as_needed', NULL, '[\"Assist with personal hygiene and grooming\", \"Help with meal preparation and feeding as needed\", \"Administer prescribed medications on schedule\", \"Monitor and record vital signs daily\", \"Assist with mobility and safe transfers\", \"Provide companionship and emotional support\", \"Maintain clean and safe living environment\", \"Document daily activities and observations\"]', '[6, 2, 4, 0]', 'high', '2025-09-05 00:00:00', 50, '2025-08-29 00:00:00', '2025-11-02 12:20:14', NULL, 66, 4, 'Patient requires gentle, patient-centered care. Please maintain consistent schedule and document any changes in condition or behavior.', NULL),
(271, 22, 80, 80, 1, 'General Home Care Plan - Samara Bergnaum', 'Comprehensive general care including daily living assistance, medication management, and basic health monitoring. This plan focuses on maintaining patient independence while providing necessary support for activities of daily living.', 'general_care', 'active', '2025-08-20', '2025-10-31', 'every_6_hours', NULL, '[\"Assist with personal hygiene and grooming\", \"Help with meal preparation and feeding as needed\", \"Administer prescribed medications on schedule\", \"Monitor and record vital signs daily\", \"Assist with mobility and safe transfers\", \"Provide companionship and emotional support\", \"Maintain clean and safe living environment\", \"Document daily activities and observations\"]', NULL, 'medium', '2025-08-18 00:00:00', 95, '2025-07-31 00:00:00', '2025-10-14 23:09:06', NULL, 61, 54, 'Patient requires gentle, patient-centered care. Please maintain consistent schedule and document any changes in condition or behavior.', NULL),
(272, 31, 71, 71, 1, 'Palliative Care Plan - Shaniya Goldner', 'Comfort-focused care emphasizing pain and symptom management, quality of life, and dignity for patients with serious illness. Holistic approach including physical, emotional, and spiritual support for patient and family.', 'palliative_care', 'active', '2025-09-17', '2025-11-11', 'every_4_hours', NULL, '[\"Provide comprehensive pain assessment and management\", \"Manage symptoms for maximum comfort (nausea, breathlessness, etc.)\", \"Assist with all personal care needs gently and respectfully\", \"Provide emotional and spiritual support\", \"Support family members and answer questions\", \"Coordinate with hospice or palliative care team\", \"Maintain patient dignity and respect wishes\", \"Create peaceful, comfortable environment\", \"Offer comfort measures (positioning, massage, music)\", \"Document comfort levels and symptom management\"]', NULL, 'medium', '2025-09-15 00:00:00', 55, '2025-09-07 00:00:00', '2025-10-14 23:09:06', NULL, 57, 56, 'Focus on comfort, dignity, and quality of life. Family involvement is important. Follow pain management protocol carefully. Respect patient and family wishes at all times.', NULL),
(273, 16, 77, 77, 81, 'Chronic Disease Management Plan - Micheal Asamoah', 'Comprehensive management of chronic conditions with focus on medication compliance, symptom monitoring, lifestyle modifications, and preventing complications. Coordinated care approach with healthcare team.', 'chronic_disease_management', 'active', '2025-08-21', '2026-06-25', 'every_12_hours', NULL, '[\"Administer complex medication regimens accurately\", \"Monitor disease-specific symptoms and vital signs\", \"Record detailed health metrics and trends\", \"Assist with specialized medical equipment\", \"Support prescribed dietary modifications\", \"Coordinate with healthcare providers and specialists\", \"Provide patient education on disease self-management\", \"Monitor for medication side effects or complications\", \"Encourage adherence to treatment plans\", \"Document all health changes and communicate promptly\"]', NULL, 'low', '2025-08-20 00:00:00', 95, '2025-08-07 00:00:00', '2025-10-14 23:09:06', NULL, 58, 79, 'Strict medication adherence is critical for disease management. Monitor symptoms closely and report any changes immediately to the healthcare team. Patient education ongoing.', NULL),
(274, 23, 69, 69, 2, 'General Home Care Plan - Trever Stamm', 'Comprehensive general care including daily living assistance, medication management, and basic health monitoring. This plan focuses on maintaining patient independence while providing necessary support for activities of daily living.', 'general_care', 'active', '2025-08-27', '2025-10-26', 'every_12_hours', NULL, '[\"Assist with personal hygiene and grooming\", \"Help with meal preparation and feeding as needed\", \"Administer prescribed medications on schedule\", \"Monitor and record vital signs daily\", \"Assist with mobility and safe transfers\", \"Provide companionship and emotional support\", \"Maintain clean and safe living environment\", \"Document daily activities and observations\"]', NULL, 'medium', '2025-08-26 00:00:00', 95, '2025-08-16 00:00:00', '2025-10-14 23:09:06', NULL, 59, NULL, 'Patient requires gentle, patient-centered care. Please maintain consistent schedule and document any changes in condition or behavior.', NULL),
(275, 16, 70, 70, 81, 'Palliative Care Plan - Micheal Asamoah', 'Comfort-focused care emphasizing pain and symptom management, quality of life, and dignity for patients with serious illness. Holistic approach including physical, emotional, and spiritual support for patient and family.', 'palliative_care', 'active', '2025-10-07', '2026-01-05', 'once_daily', NULL, '[\"Provide comprehensive pain assessment and management\", \"Manage symptoms for maximum comfort (nausea, breathlessness, etc.)\", \"Assist with all personal care needs gently and respectfully\", \"Provide emotional and spiritual support\", \"Support family members and answer questions\", \"Coordinate with hospice or palliative care team\", \"Maintain patient dignity and respect wishes\", \"Create peaceful, comfortable environment\", \"Offer comfort measures (positioning, massage, music)\", \"Document comfort levels and symptom management\"]', NULL, 'medium', '2025-10-06 00:00:00', 15, '2025-09-26 00:00:00', '2025-10-14 23:09:06', NULL, 61, NULL, 'Focus on comfort, dignity, and quality of life. Family involvement is important. Follow pain management protocol carefully. Respect patient and family wishes at all times.', NULL),
(276, 78, 73, 73, 2, 'Elderly Care Plan - Test Patient', 'Specialized care for elderly patients focusing on dignity, independence, and quality of life. Includes comprehensive support for age-related needs, fall prevention, cognitive engagement, and social interaction.', 'elderly_care', 'active', '2025-09-17', '2026-07-21', 'twice_daily', NULL, '[\"Assist with bathing and personal care with dignity\", \"Help with dressing and grooming routines\", \"Prepare nutritious meals appropriate for dietary needs\", \"Administer medications and track medication schedule\", \"Provide mobility support and fall prevention measures\", \"Engage in cognitive stimulation activities\", \"Monitor for signs of confusion or changes in behavior\", \"Encourage social interaction and meaningful activities\", \"Regular position changes to prevent pressure sores\", \"Document any changes in condition immediately\"]', NULL, 'medium', '2025-09-16 00:00:00', 55, '2025-09-10 00:00:00', '2025-10-14 23:09:06', NULL, 55, 62, 'Patient may have cognitive changes and requires patience and reassurance. Fall risk precautions must be maintained at all times. Encourage independence while ensuring safety.', NULL),
(277, 50, 72, 72, 1, 'Elderly Care Plan - Chester Shields', 'Specialized care for elderly patients focusing on dignity, independence, and quality of life. Includes comprehensive support for age-related needs, fall prevention, cognitive engagement, and social interaction.', 'elderly_care', 'active', '2025-09-12', '2026-02-24', 'every_12_hours', NULL, '[\"Assist with bathing and personal care with dignity\", \"Help with dressing and grooming routines\", \"Prepare nutritious meals appropriate for dietary needs\", \"Administer medications and track medication schedule\", \"Provide mobility support and fall prevention measures\", \"Engage in cognitive stimulation activities\", \"Monitor for signs of confusion or changes in behavior\", \"Encourage social interaction and meaningful activities\", \"Regular position changes to prevent pressure sores\", \"Document any changes in condition immediately\"]', NULL, 'critical', '2025-09-11 00:00:00', 65, '2025-08-24 00:00:00', '2025-10-14 23:09:06', NULL, 57, NULL, 'Patient may have cognitive changes and requires patience and reassurance. Fall risk precautions must be maintained at all times. Encourage independence while ensuring safety.', NULL),
(278, 49, 74, 75, 81, 'Pediatric Home Care Plan - Robert Ben Brown', 'Specialized care for children including developmental support, medication management, family education, and age-appropriate activities. Focus on child safety, comfort, and supporting normal development.', 'pediatric_care', 'active', '2025-09-30', '2025-11-06', 'monthly', NULL, '[\"Administer age-appropriate medications safely\", \"Monitor growth, development, and vital signs\", \"Assist with feeding and ensure proper nutrition\", \"Provide age-appropriate play and learning activities\", \"Monitor for signs of illness, pain, or discomfort\", \"Educate parents on care routines and procedures\", \"Maintain clean, safe, and child-friendly environment\", \"Support developmental milestones and activities\", \"Provide comfort measures and emotional support\", \"Document feeding, elimination, and behavior patterns\"]', '[0, 1, 2, 3]', 'high', '2025-09-28 00:00:00', 40, '2025-09-15 00:00:00', '2025-11-02 15:49:45', NULL, 4, 54, 'Child requires age-appropriate communication and activities. Keep parents informed of all care activities, changes, and concerns. Maintain safe, child-friendly environment.', NULL),
(279, 19, 75, 75, 81, 'General Home Care Plan - Ray Wintheiser', 'Comprehensive general care including daily living assistance, medication management, and basic health monitoring. This plan focuses on maintaining patient independence while providing necessary support for activities of daily living.', 'general_care', 'active', '2025-08-31', '2025-10-08', 'twice_daily', NULL, '[\"Assist with personal hygiene and grooming\", \"Help with meal preparation and feeding as needed\", \"Administer prescribed medications on schedule\", \"Monitor and record vital signs daily\", \"Assist with mobility and safe transfers\", \"Provide companionship and emotional support\", \"Maintain clean and safe living environment\", \"Document daily activities and observations\"]', NULL, 'medium', '2025-08-29 00:00:00', 89, '2025-08-21 00:00:00', '2025-10-14 23:09:06', NULL, 65, 60, 'Patient requires gentle, patient-centered care. Please maintain consistent schedule and document any changes in condition or behavior.', NULL),
(280, 24, 71, 71, 1, 'Chronic Disease Management Plan - Aliza Pfannerstill', 'Comprehensive management of chronic conditions with focus on medication compliance, symptom monitoring, lifestyle modifications, and preventing complications. Coordinated care approach with healthcare team.', 'chronic_disease_management', 'active', '2025-08-30', '2026-02-10', 'three_times_daily', NULL, '[\"Administer complex medication regimens accurately\", \"Monitor disease-specific symptoms and vital signs\", \"Record detailed health metrics and trends\", \"Assist with specialized medical equipment\", \"Support prescribed dietary modifications\", \"Coordinate with healthcare providers and specialists\", \"Provide patient education on disease self-management\", \"Monitor for medication side effects or complications\", \"Encourage adherence to treatment plans\", \"Document all health changes and communicate promptly\"]', NULL, 'critical', '2025-08-27 00:00:00', 91, '2025-08-18 00:00:00', '2025-10-14 23:09:06', NULL, 53, 66, 'Strict medication adherence is critical for disease management. Monitor symptoms closely and report any changes immediately to the healthcare team. Patient education ongoing.', NULL),
(281, 8, 71, 71, 81, 'Chronic Disease Management Plan - Philip Gbeko', 'Comprehensive management of chronic conditions with focus on medication compliance, symptom monitoring, lifestyle modifications, and preventing complications. Coordinated care approach with healthcare team.', 'chronic_disease_management', 'active', '2025-09-21', NULL, 'twice_weekly', NULL, '[\"Administer complex medication regimens accurately\", \"Monitor disease-specific symptoms and vital signs\", \"Record detailed health metrics and trends\", \"Assist with specialized medical equipment\", \"Support prescribed dietary modifications\", \"Coordinate with healthcare providers and specialists\", \"Provide patient education on disease self-management\", \"Monitor for medication side effects or complications\", \"Encourage adherence to treatment plans\", \"Document all health changes and communicate promptly\"]', NULL, 'high', '2025-09-18 00:00:00', 47, '2025-09-08 00:00:00', '2025-10-14 23:09:06', NULL, 55, NULL, 'Strict medication adherence is critical for disease management. Monitor symptoms closely and report any changes immediately to the healthcare team. Patient education ongoing.', NULL),
(282, 49, 3, 3, 1, 'Palliative Care Plan - Ted Howell', 'Comfort-focused care emphasizing pain and symptom management, quality of life, and dignity for patients with serious illness. Holistic approach including physical, emotional, and spiritual support for patient and family.', 'palliative_care', 'active', '2025-08-28', '2025-11-04', 'every_8_hours', NULL, '[\"Provide comprehensive pain assessment and management\", \"Manage symptoms for maximum comfort (nausea, breathlessness, etc.)\", \"Assist with all personal care needs gently and respectfully\", \"Provide emotional and spiritual support\", \"Support family members and answer questions\", \"Coordinate with hospice or palliative care team\", \"Maintain patient dignity and respect wishes\", \"Create peaceful, comfortable environment\", \"Offer comfort measures (positioning, massage, music)\", \"Document comfort levels and symptom management\"]', '[0, 1]', 'medium', '2025-08-25 00:00:00', 20, '2025-08-17 00:00:00', '2025-11-02 11:26:03', NULL, 4, NULL, 'Focus on comfort, dignity, and quality of life. Family involvement is important. Follow pain management protocol carefully. Respect patient and family wishes at all times.', NULL),
(283, 39, 73, 73, 81, 'Rehabilitation Care Plan - Jarvis Dicki', 'Goal-oriented care focused on recovering function, improving strength, and regaining independence after illness or injury. Progressive approach with therapeutic exercises, mobility training, and continuous progress monitoring.', 'rehabilitation_care', 'active', '2025-08-16', NULL, 'every_8_hours', NULL, '[\"Assist with prescribed rehabilitation exercises\", \"Support physical therapy activities and goals\", \"Help with occupational therapy tasks\", \"Monitor progress toward specific recovery goals\", \"Encourage increasing independence in daily activities\", \"Prevent complications during recovery period\", \"Provide motivation and positive reinforcement\", \"Assist with adaptive equipment and techniques\", \"Document functional improvements and setbacks\", \"Coordinate with therapy team on progress\"]', NULL, 'high', '2025-08-14 00:00:00', 95, '2025-07-31 00:00:00', '2025-10-14 23:09:06', NULL, 56, NULL, 'Patient is motivated but may experience frustration during recovery. Provide encouragement and positive reinforcement. Follow therapy protocols precisely and document progress.', NULL),
(284, 31, 76, 76, 81, 'Rehabilitation Care Plan - Shaniya Goldner', 'Goal-oriented care focused on recovering function, improving strength, and regaining independence after illness or injury. Progressive approach with therapeutic exercises, mobility training, and continuous progress monitoring.', 'rehabilitation_care', 'active', '2025-10-11', '2025-12-03', 'once_daily', NULL, '[\"Assist with prescribed rehabilitation exercises\", \"Support physical therapy activities and goals\", \"Help with occupational therapy tasks\", \"Monitor progress toward specific recovery goals\", \"Encourage increasing independence in daily activities\", \"Prevent complications during recovery period\", \"Provide motivation and positive reinforcement\", \"Assist with adaptive equipment and techniques\", \"Document functional improvements and setbacks\", \"Coordinate with therapy team on progress\"]', NULL, 'critical', '2025-10-08 00:00:00', 10, '2025-10-04 00:00:00', '2025-10-14 23:09:06', NULL, 64, NULL, 'Patient is motivated but may experience frustration during recovery. Provide encouragement and positive reinforcement. Follow therapy protocols precisely and document progress.', NULL),
(285, 40, 71, 71, 1, 'Pediatric Home Care Plan - Juston Roob', 'Specialized care for children including developmental support, medication management, family education, and age-appropriate activities. Focus on child safety, comfort, and supporting normal development.', 'pediatric_care', 'active', '2025-09-17', '2025-10-12', 'custom', 'Three times weekly on alternate days', '[\"Administer age-appropriate medications safely\", \"Monitor growth, development, and vital signs\", \"Assist with feeding and ensure proper nutrition\", \"Provide age-appropriate play and learning activities\", \"Monitor for signs of illness, pain, or discomfort\", \"Educate parents on care routines and procedures\", \"Maintain clean, safe, and child-friendly environment\", \"Support developmental milestones and activities\", \"Provide comfort measures and emotional support\", \"Document feeding, elimination, and behavior patterns\"]', NULL, 'high', '2025-09-14 00:00:00', 55, '2025-09-04 00:00:00', '2025-10-14 23:09:06', NULL, 65, NULL, 'Child requires age-appropriate communication and activities. Keep parents informed of all care activities, changes, and concerns. Maintain safe, child-friendly environment.', NULL),
(286, 19, 69, 69, 2, 'Pediatric Home Care Plan - Ray Wintheiser', 'Specialized care for children including developmental support, medication management, family education, and age-appropriate activities. Focus on child safety, comfort, and supporting normal development.', 'pediatric_care', 'active', '2025-10-09', '2025-11-22', 'every_4_hours', NULL, '[\"Administer age-appropriate medications safely\", \"Monitor growth, development, and vital signs\", \"Assist with feeding and ensure proper nutrition\", \"Provide age-appropriate play and learning activities\", \"Monitor for signs of illness, pain, or discomfort\", \"Educate parents on care routines and procedures\", \"Maintain clean, safe, and child-friendly environment\", \"Support developmental milestones and activities\", \"Provide comfort measures and emotional support\", \"Document feeding, elimination, and behavior patterns\"]', NULL, 'high', '2025-10-08 00:00:00', 11, '2025-09-29 00:00:00', '2025-10-14 23:09:06', NULL, 58, 53, 'Child requires age-appropriate communication and activities. Keep parents informed of all care activities, changes, and concerns. Maintain safe, child-friendly environment.', NULL),
(287, 30, 80, 2, 1, 'Elderly Care Plan - Loma Beatty', 'Specialized care for elderly patients focusing on dignity, independence, and quality of life. Includes comprehensive support for age-related needs, fall prevention, cognitive engagement, and social interaction.', 'elderly_care', 'completed', '2025-10-11', '2026-10-08', 'once_daily', NULL, '[\"Assist with bathing and personal care with dignity\", \"Help with dressing and grooming routines\", \"Prepare nutritious meals appropriate for dietary needs\", \"Administer medications and track medication schedule\", \"Provide mobility support and fall prevention measures\", \"Engage in cognitive stimulation activities\", \"Monitor for signs of confusion or changes in behavior\", \"Encourage social interaction and meaningful activities\", \"Regular position changes to prevent pressure sores\", \"Document any changes in condition immediately\"]', NULL, 'low', '2025-10-09 00:00:00', 100, '2025-09-30 00:00:00', '2025-10-17 10:58:01', NULL, 62, NULL, 'Patient may have cognitive changes and requires patience and reassurance. Fall risk precautions must be maintained at all times. Encourage independence while ensuring safety.', NULL),
(288, 8, 68, 68, 2, 'Rehabilitation Care Plan - Philip Gbeko', 'Goal-oriented care focused on recovering function, improving strength, and regaining independence after illness or injury. Progressive approach with therapeutic exercises, mobility training, and continuous progress monitoring.', 'rehabilitation_care', 'active', '2025-08-26', '2025-09-27', 'every_4_hours', NULL, '[\"Assist with prescribed rehabilitation exercises\", \"Support physical therapy activities and goals\", \"Help with occupational therapy tasks\", \"Monitor progress toward specific recovery goals\", \"Encourage increasing independence in daily activities\", \"Prevent complications during recovery period\", \"Provide motivation and positive reinforcement\", \"Assist with adaptive equipment and techniques\", \"Document functional improvements and setbacks\", \"Coordinate with therapy team on progress\"]', NULL, 'low', '2025-08-25 00:00:00', 95, '2025-08-12 00:00:00', '2025-10-14 23:09:06', NULL, 65, NULL, 'Patient is motivated but may experience frustration during recovery. Provide encouragement and positive reinforcement. Follow therapy protocols precisely and document progress.', NULL),
(289, 31, 69, 69, 1, 'Palliative Care Plan - Shaniya Goldner', 'Comfort-focused care emphasizing pain and symptom management, quality of life, and dignity for patients with serious illness. Holistic approach including physical, emotional, and spiritual support for patient and family.', 'palliative_care', 'active', '2025-09-15', NULL, 'weekly', NULL, '[\"Provide comprehensive pain assessment and management\", \"Manage symptoms for maximum comfort (nausea, breathlessness, etc.)\", \"Assist with all personal care needs gently and respectfully\", \"Provide emotional and spiritual support\", \"Support family members and answer questions\", \"Coordinate with hospice or palliative care team\", \"Maintain patient dignity and respect wishes\", \"Create peaceful, comfortable environment\", \"Offer comfort measures (positioning, massage, music)\", \"Document comfort levels and symptom management\"]', NULL, 'low', '2025-09-12 00:00:00', 59, '2025-08-30 00:00:00', '2025-10-14 23:09:06', NULL, 61, NULL, 'Focus on comfort, dignity, and quality of life. Family involvement is important. Follow pain management protocol carefully. Respect patient and family wishes at all times.', NULL),
(290, 43, 73, 73, 2, 'Chronic Disease Management Plan - Katlyn Schneider', 'Comprehensive management of chronic conditions with focus on medication compliance, symptom monitoring, lifestyle modifications, and preventing complications. Coordinated care approach with healthcare team.', 'chronic_disease_management', 'active', '2025-08-30', '2026-03-28', 'every_6_hours', NULL, '[\"Administer complex medication regimens accurately\", \"Monitor disease-specific symptoms and vital signs\", \"Record detailed health metrics and trends\", \"Assist with specialized medical equipment\", \"Support prescribed dietary modifications\", \"Coordinate with healthcare providers and specialists\", \"Provide patient education on disease self-management\", \"Monitor for medication side effects or complications\", \"Encourage adherence to treatment plans\", \"Document all health changes and communicate promptly\"]', NULL, 'medium', '2025-08-29 00:00:00', 91, '2025-08-16 00:00:00', '2025-10-14 23:09:06', NULL, 59, NULL, 'Strict medication adherence is critical for disease management. Monitor symptoms closely and report any changes immediately to the healthcare team. Patient education ongoing.', NULL),
(291, 26, 73, 73, 2, 'Palliative Care Plan - Felix Cassin', 'Comfort-focused care emphasizing pain and symptom management, quality of life, and dignity for patients with serious illness. Holistic approach including physical, emotional, and spiritual support for patient and family.', 'palliative_care', 'active', '2025-10-06', '2026-01-09', 'weekly', NULL, '[\"Provide comprehensive pain assessment and management\", \"Manage symptoms for maximum comfort (nausea, breathlessness, etc.)\", \"Assist with all personal care needs gently and respectfully\", \"Provide emotional and spiritual support\", \"Support family members and answer questions\", \"Coordinate with hospice or palliative care team\", \"Maintain patient dignity and respect wishes\", \"Create peaceful, comfortable environment\", \"Offer comfort measures (positioning, massage, music)\", \"Document comfort levels and symptom management\"]', NULL, 'medium', '2025-10-03 00:00:00', 17, '2025-09-21 00:00:00', '2025-10-14 23:09:06', NULL, 64, NULL, 'Focus on comfort, dignity, and quality of life. Family involvement is important. Follow pain management protocol carefully. Respect patient and family wishes at all times.', NULL),
(292, 49, 75, 75, 2, 'Pediatric Home Care Plan - Ted Howell', 'Specialized care for children including developmental support, medication management, family education, and age-appropriate activities. Focus on child safety, comfort, and supporting normal development.', 'pediatric_care', 'active', '2025-10-02', '2025-11-04', 'three_times_daily', NULL, '[\"Administer age-appropriate medications safely\", \"Monitor growth, development, and vital signs\", \"Assist with feeding and ensure proper nutrition\", \"Provide age-appropriate play and learning activities\", \"Monitor for signs of illness, pain, or discomfort\", \"Educate parents on care routines and procedures\", \"Maintain clean, safe, and child-friendly environment\", \"Support developmental milestones and activities\", \"Provide comfort measures and emotional support\", \"Document feeding, elimination, and behavior patterns\"]', NULL, 'high', '2025-10-01 00:00:00', 25, '2025-09-14 00:00:00', '2025-10-14 23:09:06', NULL, 79, 53, 'Child requires age-appropriate communication and activities. Keep parents informed of all care activities, changes, and concerns. Maintain safe, child-friendly environment.', NULL),
(293, 42, 70, 70, 81, 'Chronic Disease Management Plan - Desiree Mraz', 'Comprehensive management of chronic conditions with focus on medication compliance, symptom monitoring, lifestyle modifications, and preventing complications. Coordinated care approach with healthcare team.', 'chronic_disease_management', 'active', '2025-09-05', '2026-07-21', 'twice_daily', NULL, '[\"Administer complex medication regimens accurately\", \"Monitor disease-specific symptoms and vital signs\", \"Record detailed health metrics and trends\", \"Assist with specialized medical equipment\", \"Support prescribed dietary modifications\", \"Coordinate with healthcare providers and specialists\", \"Provide patient education on disease self-management\", \"Monitor for medication side effects or complications\", \"Encourage adherence to treatment plans\", \"Document all health changes and communicate promptly\"]', NULL, 'low', '2025-09-04 00:00:00', 79, '2025-08-17 00:00:00', '2025-10-14 23:09:06', NULL, 53, NULL, 'Strict medication adherence is critical for disease management. Monitor symptoms closely and report any changes immediately to the healthcare team. Patient education ongoing.', NULL),
(294, 31, 73, 81, 2, 'Chronic Disease Management Plan - Shaniya Goldner', 'Comprehensive management of chronic conditions with focus on medication compliance, symptom monitoring, lifestyle modifications, and preventing complications. Coordinated care approach with healthcare team.', 'chronic_disease_management', 'active', '2025-09-27', NULL, 'every_8_hours', NULL, '[\"Administer complex medication regimens accurately\", \"Monitor disease-specific symptoms and vital signs\", \"Record detailed health metrics and trends\", \"Assist with specialized medical equipment\", \"Support prescribed dietary modifications\", \"Coordinate with healthcare providers and specialists\", \"Provide patient education on disease self-management\", \"Monitor for medication side effects or complications\", \"Encourage adherence to treatment plans\", \"Document all health changes and communicate promptly\"]', NULL, 'low', '2025-09-25 00:00:00', 35, '2025-09-10 00:00:00', '2025-10-14 23:09:06', NULL, 59, NULL, 'Strict medication adherence is critical for disease management. Monitor symptoms closely and report any changes immediately to the healthcare team. Patient education ongoing.', NULL),
(295, 34, 71, 2, 81, 'Chronic Disease Management Plan - Tod Huels', 'Comprehensive management of chronic conditions with focus on medication compliance, symptom monitoring, lifestyle modifications, and preventing complications. Coordinated care approach with healthcare team.', 'chronic_disease_management', 'active', '2025-09-15', '2026-06-27', 'twice_weekly', NULL, '[\"Administer complex medication regimens accurately\", \"Monitor disease-specific symptoms and vital signs\", \"Record detailed health metrics and trends\", \"Assist with specialized medical equipment\", \"Support prescribed dietary modifications\", \"Coordinate with healthcare providers and specialists\", \"Provide patient education on disease self-management\", \"Monitor for medication side effects or complications\", \"Encourage adherence to treatment plans\", \"Document all health changes and communicate promptly\"]', NULL, 'critical', '2025-09-12 00:00:00', 59, '2025-09-05 00:00:00', '2025-10-14 23:09:06', NULL, 61, NULL, 'Strict medication adherence is critical for disease management. Monitor symptoms closely and report any changes immediately to the healthcare team. Patient education ongoing.', NULL),
(296, 5, 70, 70, 81, 'Chronic Disease Management Plan - Robert Ben Brown', 'Comprehensive management of chronic conditions with focus on medication compliance, symptom monitoring, lifestyle modifications, and preventing complications. Coordinated care approach with healthcare team.', 'chronic_disease_management', 'active', '2025-09-17', '2026-06-19', 'every_12_hours', NULL, '[\"Administer complex medication regimens accurately\", \"Monitor disease-specific symptoms and vital signs\", \"Record detailed health metrics and trends\", \"Assist with specialized medical equipment\", \"Support prescribed dietary modifications\", \"Coordinate with healthcare providers and specialists\", \"Provide patient education on disease self-management\", \"Monitor for medication side effects or complications\", \"Encourage adherence to treatment plans\", \"Document all health changes and communicate promptly\"]', NULL, 'low', '2025-09-16 00:00:00', 55, '2025-09-04 00:00:00', '2025-10-14 23:09:06', NULL, 53, NULL, 'Strict medication adherence is critical for disease management. Monitor symptoms closely and report any changes immediately to the healthcare team. Patient education ongoing.', NULL),
(297, 42, 3, 3, 1, 'Rehabilitation Care Plan - Desiree Mraz', 'Goal-oriented care focused on recovering function, improving strength, and regaining independence after illness or injury. Progressive approach with therapeutic exercises, mobility training, and continuous progress monitoring.', 'rehabilitation_care', 'completed', '2025-04-22', '2025-05-16', 'every_8_hours', NULL, '[\"Assist with prescribed rehabilitation exercises\", \"Support physical therapy activities and goals\", \"Help with occupational therapy tasks\", \"Monitor progress toward specific recovery goals\", \"Encourage increasing independence in daily activities\", \"Prevent complications during recovery period\", \"Provide motivation and positive reinforcement\", \"Assist with adaptive equipment and techniques\", \"Document functional improvements and setbacks\", \"Coordinate with therapy team on progress\"]', NULL, 'critical', '2025-04-19 00:00:00', 100, '2025-04-04 00:00:00', '2025-10-14 23:09:06', NULL, 66, NULL, 'Patient is motivated but may experience frustration during recovery. Provide encouragement and positive reinforcement. Follow therapy protocols precisely and document progress.', NULL),
(298, 39, 70, 1, 1, 'Palliative Care Plan - Jarvis Dicki', 'Comfort-focused care emphasizing pain and symptom management, quality of life, and dignity for patients with serious illness. Holistic approach including physical, emotional, and spiritual support for patient and family.', 'palliative_care', 'completed', '2025-04-30', NULL, 'twice_weekly', NULL, '[\"Provide comprehensive pain assessment and management\", \"Manage symptoms for maximum comfort (nausea, breathlessness, etc.)\", \"Assist with all personal care needs gently and respectfully\", \"Provide emotional and spiritual support\", \"Support family members and answer questions\", \"Coordinate with hospice or palliative care team\", \"Maintain patient dignity and respect wishes\", \"Create peaceful, comfortable environment\", \"Offer comfort measures (positioning, massage, music)\", \"Document comfort levels and symptom management\"]', NULL, 'high', '2025-04-29 00:00:00', 100, '2025-04-13 00:00:00', '2025-10-14 23:09:06', NULL, 53, 54, 'Focus on comfort, dignity, and quality of life. Family involvement is important. Follow pain management protocol carefully. Respect patient and family wishes at all times.', NULL),
(299, 45, 76, 76, 81, 'Elderly Care Plan - Felipe Schowalter', 'Specialized care for elderly patients focusing on dignity, independence, and quality of life. Includes comprehensive support for age-related needs, fall prevention, cognitive engagement, and social interaction.', 'elderly_care', 'completed', '2025-07-13', NULL, 'custom', 'Monday, Wednesday, and Friday mornings at 8:00 AM', '[\"Assist with bathing and personal care with dignity\", \"Help with dressing and grooming routines\", \"Prepare nutritious meals appropriate for dietary needs\", \"Administer medications and track medication schedule\", \"Provide mobility support and fall prevention measures\", \"Engage in cognitive stimulation activities\", \"Monitor for signs of confusion or changes in behavior\", \"Encourage social interaction and meaningful activities\", \"Regular position changes to prevent pressure sores\", \"Document any changes in condition immediately\"]', '[1]', 'medium', '2025-07-12 00:00:00', 10, '2025-07-01 00:00:00', '2025-11-02 15:17:33', NULL, 4, 61, 'Patient may have cognitive changes and requires patience and reassurance. Fall risk precautions must be maintained at all times. Encourage independence while ensuring safety.', NULL),
(300, 47, 73, 73, 2, 'Elderly Care Plan - Larry Hill', 'Specialized care for elderly patients focusing on dignity, independence, and quality of life. Includes comprehensive support for age-related needs, fall prevention, cognitive engagement, and social interaction.', 'elderly_care', 'completed', '2025-05-22', '2026-02-02', 'every_8_hours', NULL, '[\"Assist with bathing and personal care with dignity\", \"Help with dressing and grooming routines\", \"Prepare nutritious meals appropriate for dietary needs\", \"Administer medications and track medication schedule\", \"Provide mobility support and fall prevention measures\", \"Engage in cognitive stimulation activities\", \"Monitor for signs of confusion or changes in behavior\", \"Encourage social interaction and meaningful activities\", \"Regular position changes to prevent pressure sores\", \"Document any changes in condition immediately\"]', NULL, 'high', '2025-05-20 00:00:00', 100, '2025-05-14 00:00:00', '2025-10-14 23:09:06', NULL, 56, NULL, 'Patient may have cognitive changes and requires patience and reassurance. Fall risk precautions must be maintained at all times. Encourage independence while ensuring safety.', NULL);
INSERT INTO `care_plans` (`id`, `patient_id`, `doctor_id`, `created_by`, `approved_by`, `title`, `description`, `care_type`, `status`, `start_date`, `end_date`, `frequency`, `custom_frequency_details`, `care_tasks`, `completed_tasks`, `priority`, `approved_at`, `completion_percentage`, `created_at`, `updated_at`, `deleted_at`, `primary_nurse_id`, `secondary_nurse_id`, `assignment_notes`, `estimated_hours_per_day`) VALUES
(301, 41, 70, 70, 2, 'Palliative Care Plan - Callie Bergstrom', 'Comfort-focused care emphasizing pain and symptom management, quality of life, and dignity for patients with serious illness. Holistic approach including physical, emotional, and spiritual support for patient and family.', 'palliative_care', 'completed', '2025-05-17', '2025-10-15', 'custom', 'Every 4 hours during waking hours (7 AM - 11 PM)', '[\"Provide comprehensive pain assessment and management\", \"Manage symptoms for maximum comfort (nausea, breathlessness, etc.)\", \"Assist with all personal care needs gently and respectfully\", \"Provide emotional and spiritual support\", \"Support family members and answer questions\", \"Coordinate with hospice or palliative care team\", \"Maintain patient dignity and respect wishes\", \"Create peaceful, comfortable environment\", \"Offer comfort measures (positioning, massage, music)\", \"Document comfort levels and symptom management\"]', NULL, 'high', '2025-05-15 00:00:00', 100, '2025-04-30 00:00:00', '2025-10-14 23:09:06', NULL, 65, NULL, 'Focus on comfort, dignity, and quality of life. Family involvement is important. Follow pain management protocol carefully. Respect patient and family wishes at all times.', NULL),
(302, 30, 69, 69, 2, 'General Home Care Plan - Loma Beatty', 'Comprehensive general care including daily living assistance, medication management, and basic health monitoring. This plan focuses on maintaining patient independence while providing necessary support for activities of daily living.', 'general_care', 'completed', '2025-06-19', '2025-09-01', 'twice_daily', NULL, '[\"Assist with personal hygiene and grooming\", \"Help with meal preparation and feeding as needed\", \"Administer prescribed medications on schedule\", \"Monitor and record vital signs daily\", \"Assist with mobility and safe transfers\", \"Provide companionship and emotional support\", \"Maintain clean and safe living environment\", \"Document daily activities and observations\"]', NULL, 'medium', '2025-06-18 00:00:00', 100, '2025-06-06 00:00:00', '2025-10-14 23:09:06', NULL, 55, NULL, 'Patient requires gentle, patient-centered care. Please maintain consistent schedule and document any changes in condition or behavior.', NULL),
(303, 24, 76, 2, 81, 'Rehabilitation Care Plan - Aliza Pfannerstill', 'Goal-oriented care focused on recovering function, improving strength, and regaining independence after illness or injury. Progressive approach with therapeutic exercises, mobility training, and continuous progress monitoring.', 'rehabilitation_care', 'completed', '2025-05-22', NULL, 'every_4_hours', NULL, '[\"Assist with prescribed rehabilitation exercises\", \"Support physical therapy activities and goals\", \"Help with occupational therapy tasks\", \"Monitor progress toward specific recovery goals\", \"Encourage increasing independence in daily activities\", \"Prevent complications during recovery period\", \"Provide motivation and positive reinforcement\", \"Assist with adaptive equipment and techniques\", \"Document functional improvements and setbacks\", \"Coordinate with therapy team on progress\"]', NULL, 'critical', '2025-05-20 00:00:00', 100, '2025-05-04 00:00:00', '2025-10-14 23:09:06', NULL, 60, NULL, 'Patient is motivated but may experience frustration during recovery. Provide encouragement and positive reinforcement. Follow therapy protocols precisely and document progress.', NULL),
(304, 34, 74, 74, 1, 'Elderly Care Plan - Tod Huels', 'Specialized care for elderly patients focusing on dignity, independence, and quality of life. Includes comprehensive support for age-related needs, fall prevention, cognitive engagement, and social interaction.', 'elderly_care', 'completed', '2025-06-04', NULL, 'every_6_hours', NULL, '[\"Assist with bathing and personal care with dignity\", \"Help with dressing and grooming routines\", \"Prepare nutritious meals appropriate for dietary needs\", \"Administer medications and track medication schedule\", \"Provide mobility support and fall prevention measures\", \"Engage in cognitive stimulation activities\", \"Monitor for signs of confusion or changes in behavior\", \"Encourage social interaction and meaningful activities\", \"Regular position changes to prevent pressure sores\", \"Document any changes in condition immediately\"]', NULL, 'low', '2025-06-03 00:00:00', 100, '2025-05-16 00:00:00', '2025-10-14 23:09:06', NULL, 64, NULL, 'Patient may have cognitive changes and requires patience and reassurance. Fall risk precautions must be maintained at all times. Encourage independence while ensuring safety.', NULL),
(305, 8, 68, 81, 81, 'General Home Care Plan - Philip Gbeko', 'Comprehensive general care including daily living assistance, medication management, and basic health monitoring. This plan focuses on maintaining patient independence while providing necessary support for activities of daily living.', 'general_care', 'completed', '2025-06-30', '2025-09-12', 'twice_daily', NULL, '[\"Assist with personal hygiene and grooming\", \"Help with meal preparation and feeding as needed\", \"Administer prescribed medications on schedule\", \"Monitor and record vital signs daily\", \"Assist with mobility and safe transfers\", \"Provide companionship and emotional support\", \"Maintain clean and safe living environment\", \"Document daily activities and observations\"]', NULL, 'high', '2025-06-28 00:00:00', 100, '2025-06-21 00:00:00', '2025-10-14 23:09:06', NULL, 64, 61, 'Patient requires gentle, patient-centered care. Please maintain consistent schedule and document any changes in condition or behavior.', NULL),
(306, 34, 80, 80, 81, 'General Home Care Plan - Tod Huels', 'Comprehensive general care including daily living assistance, medication management, and basic health monitoring. This plan focuses on maintaining patient independence while providing necessary support for activities of daily living.', 'general_care', 'completed', '2025-04-26', '2025-07-09', 'as_needed', NULL, '[\"Assist with personal hygiene and grooming\", \"Help with meal preparation and feeding as needed\", \"Administer prescribed medications on schedule\", \"Monitor and record vital signs daily\", \"Assist with mobility and safe transfers\", \"Provide companionship and emotional support\", \"Maintain clean and safe living environment\", \"Document daily activities and observations\"]', NULL, 'low', '2025-04-24 00:00:00', 100, '2025-04-13 00:00:00', '2025-10-14 23:09:06', NULL, 54, NULL, 'Patient requires gentle, patient-centered care. Please maintain consistent schedule and document any changes in condition or behavior.', NULL),
(307, 37, 70, 2, NULL, 'Test', 'Test', 'general_care', 'pending_approval', '2025-10-17', '2025-10-23', 'twice_daily', NULL, '[\"Test game\"]', NULL, 'low', NULL, 0, '2025-10-14 23:10:49', '2025-10-14 23:12:56', '2025-10-14 23:12:56', NULL, NULL, NULL, NULL),
(308, 49, 76, 4, NULL, 'Test Plan Settings', 'Test Plan Srettings', 'pediatric_care', 'draft', '2025-10-17', '2025-10-31', 'weekly', NULL, '[\"Test Plan\", \"Test Plan\"]', '[]', 'low', NULL, 0, '2025-10-17 00:13:28', '2025-11-02 11:28:36', NULL, 4, NULL, NULL, NULL),
(309, 45, NULL, 4, NULL, 'Control Game', 'Test game', 'post_surgery_care', 'draft', '2025-10-31', '2025-11-01', 'as_needed', NULL, '[\"Help Task\"]', NULL, 'low', NULL, 0, '2025-10-31 15:39:20', '2025-10-31 15:47:16', '2025-10-31 15:47:16', 4, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `care_requests`
--

CREATE TABLE `care_requests` (
  `id` bigint UNSIGNED NOT NULL,
  `patient_id` bigint UNSIGNED NOT NULL,
  `assigned_nurse_id` bigint UNSIGNED DEFAULT NULL,
  `medical_assessment_id` bigint UNSIGNED DEFAULT NULL,
  `care_type` enum('general_nursing','elderly_care','post_surgical','chronic_disease','palliative_care','rehabilitation','wound_care','medication_management') COLLATE utf8mb4_unicode_ci NOT NULL,
  `urgency_level` enum('routine','urgent','emergency') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'routine',
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `special_requirements` text COLLATE utf8mb4_unicode_ci,
  `preferred_language` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `preferred_start_date` date DEFAULT NULL,
  `preferred_time` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `service_address` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `city` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `region` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `status` enum('pending_payment','payment_received','nurse_assigned','assessment_scheduled','assessment_completed','under_review','care_plan_created','awaiting_care_payment','care_payment_received','care_active','care_completed','cancelled','rejected') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending_payment',
  `rejection_reason` text COLLATE utf8mb4_unicode_ci,
  `admin_notes` text COLLATE utf8mb4_unicode_ci,
  `assessment_scheduled_at` timestamp NULL DEFAULT NULL,
  `assessment_completed_at` timestamp NULL DEFAULT NULL,
  `care_started_at` timestamp NULL DEFAULT NULL,
  `care_ended_at` timestamp NULL DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `care_requests`
--

INSERT INTO `care_requests` (`id`, `patient_id`, `assigned_nurse_id`, `medical_assessment_id`, `care_type`, `urgency_level`, `description`, `special_requirements`, `preferred_language`, `preferred_start_date`, `preferred_time`, `service_address`, `city`, `region`, `latitude`, `longitude`, `status`, `rejection_reason`, `admin_notes`, `assessment_scheduled_at`, `assessment_completed_at`, `care_started_at`, `care_ended_at`, `cancelled_at`, `created_at`, `updated_at`, `deleted_at`) VALUES
(3, 5, NULL, NULL, 'general_nursing', 'routine', 'TEstfsdfsdfasdfasfasads', NULL, NULL, NULL, NULL, 'Test', NULL, NULL, NULL, NULL, 'pending_payment', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-04 16:04:03', '2025-11-04 16:04:03', NULL),
(4, 5, NULL, NULL, 'general_nursing', 'routine', 'TEstfsdfsdfasdfasfasads', NULL, NULL, NULL, NULL, 'Test', NULL, NULL, NULL, NULL, 'pending_payment', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-04 16:04:17', '2025-11-04 16:04:17', NULL),
(5, 5, NULL, NULL, 'general_nursing', 'routine', 'test case heresdfsfsdfasfsdfa', NULL, NULL, NULL, NULL, 'asdfsdfsdfasfa', NULL, NULL, NULL, NULL, 'pending_payment', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-04 16:08:53', '2025-11-04 16:08:53', NULL),
(6, 5, NULL, NULL, 'general_nursing', 'routine', 'test case heresdfsfsdfasfsdfa', NULL, NULL, NULL, NULL, 'asdfsdfsdfasfa', NULL, NULL, NULL, NULL, 'pending_payment', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-04 16:09:00', '2025-11-04 16:09:00', NULL),
(7, 5, NULL, NULL, 'general_nursing', 'routine', 'test case heresdfsfsdfasfsdfa', NULL, NULL, NULL, NULL, 'asdfsdfsdfasfa', NULL, NULL, NULL, NULL, 'pending_payment', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-04 16:09:16', '2025-11-04 16:09:16', NULL),
(8, 5, NULL, NULL, 'general_nursing', 'routine', 'test case heresdfsfsdfasfsdfa', NULL, NULL, NULL, NULL, 'asdfsdfsdfasfa', NULL, NULL, NULL, NULL, 'pending_payment', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-04 16:09:28', '2025-11-04 16:09:28', NULL),
(9, 5, NULL, NULL, 'general_nursing', 'routine', 'Testing payment section', NULL, NULL, '2025-11-05', 'morning', 'Accra', NULL, NULL, NULL, NULL, 'pending_payment', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-04 19:00:28', '2025-11-04 19:00:28', NULL),
(10, 5, NULL, NULL, 'general_nursing', 'routine', 'Testing paystack payment', NULL, NULL, '2025-11-05', 'morning', 'Accra', 'Accra', 'Greater Accra', NULL, NULL, 'pending_payment', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-04 19:07:38', '2025-11-04 19:07:38', NULL),
(11, 5, NULL, NULL, 'general_nursing', 'routine', 'Testing paystack payment', 'TEst', NULL, '2025-11-05', 'morning', 'Accra', NULL, NULL, NULL, NULL, 'pending_payment', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-04 19:19:03', '2025-11-04 19:19:03', NULL),
(12, 5, NULL, NULL, 'general_nursing', 'routine', 'TEsting paystack payment', 'Test', NULL, '2025-11-05', 'morning', 'Accra', NULL, NULL, NULL, NULL, 'pending_payment', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-04 19:39:00', '2025-11-04 19:39:00', NULL),
(13, 5, NULL, NULL, 'general_nursing', 'routine', 'TEsting paystack payment', 'Test', NULL, '2025-11-05', 'morning', 'Accra', NULL, NULL, NULL, NULL, 'payment_received', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-04 19:40:36', '2025-11-04 19:41:03', NULL),
(14, 5, NULL, NULL, 'general_nursing', 'routine', 'Testing payment again selection', NULL, NULL, NULL, NULL, 'Accra', 'Accra', NULL, NULL, NULL, 'pending_payment', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-05 01:34:44', '2025-11-05 01:34:44', NULL),
(15, 5, NULL, NULL, 'general_nursing', 'routine', 'Testing payment again selection', NULL, NULL, NULL, NULL, 'Accra', 'Accra', NULL, NULL, NULL, 'pending_payment', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-05 01:42:22', '2025-11-05 01:42:22', NULL),
(16, 5, NULL, NULL, 'general_nursing', 'routine', 'Testing payment again selection', NULL, NULL, NULL, NULL, 'Accra', 'Accra', NULL, NULL, NULL, 'pending_payment', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-05 01:48:14', '2025-11-05 01:48:14', NULL),
(17, 5, NULL, NULL, 'general_nursing', 'routine', 'Testing payment section here', 'TEst', NULL, '2025-11-14', 'evening', 'Accra', 'Accra', NULL, NULL, NULL, 'payment_received', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-05 02:01:24', '2025-11-05 02:01:50', NULL),
(18, 5, 4, NULL, 'general_nursing', 'routine', 'Test again here for test', 'Test', NULL, '2025-11-25', 'morning', 'Acccra', 'Accra', NULL, NULL, NULL, 'assessment_scheduled', NULL, NULL, '2025-11-06 02:06:00', NULL, NULL, NULL, NULL, '2025-11-05 02:02:47', '2025-11-05 02:06:06', NULL),
(19, 5, NULL, NULL, 'general_nursing', 'routine', 'Test care into the details of the care', 'Test', NULL, NULL, NULL, 'Test', NULL, NULL, NULL, NULL, 'pending_payment', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-05 02:07:24', '2025-11-05 02:07:24', NULL),
(20, 5, NULL, NULL, 'general_nursing', 'routine', 'Test care into the details of the care', 'Test', NULL, NULL, NULL, 'Test', NULL, NULL, NULL, NULL, 'pending_payment', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-05 02:08:47', '2025-11-05 02:08:47', NULL),
(21, 5, NULL, NULL, 'general_nursing', 'routine', 'Test care into the details of the care', 'Test', NULL, NULL, NULL, 'Test', NULL, NULL, NULL, NULL, 'pending_payment', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-05 02:39:22', '2025-11-05 02:39:22', NULL),
(22, 5, NULL, NULL, 'general_nursing', 'routine', 'general curse nurse test', 'special needs', NULL, '2025-11-10', 'morning', 'Accra', 'arcade', NULL, NULL, NULL, 'pending_payment', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-05 02:51:36', '2025-11-05 02:51:36', NULL),
(23, 5, 53, NULL, 'general_nursing', 'routine', 'general curse nurse test', 'special needs', NULL, '2025-11-10', 'morning', 'Accra', 'arcade', NULL, NULL, NULL, 'assessment_scheduled', NULL, NULL, '2025-11-07 10:00:00', NULL, NULL, NULL, NULL, '2025-11-05 02:52:24', '2025-11-05 10:16:31', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `drivers`
--

CREATE TABLE `drivers` (
  `id` bigint UNSIGNED NOT NULL,
  `driver_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `first_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_of_birth` date NOT NULL,
  `avatar` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `average_rating` decimal(3,2) DEFAULT NULL,
  `total_trips` int NOT NULL DEFAULT '0',
  `completed_trips` int NOT NULL DEFAULT '0',
  `cancelled_trips` int NOT NULL DEFAULT '0',
  `is_suspended` tinyint(1) NOT NULL DEFAULT '0',
  `suspended_at` datetime DEFAULT NULL,
  `suspension_reason` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `suspended_by` bigint UNSIGNED DEFAULT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `drivers`
--

INSERT INTO `drivers` (`id`, `driver_id`, `first_name`, `last_name`, `phone`, `email`, `date_of_birth`, `avatar`, `is_active`, `average_rating`, `total_trips`, `completed_trips`, `cancelled_trips`, `is_suspended`, `suspended_at`, `suspension_reason`, `suspended_by`, `notes`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'DRV25386', 'Gideon', 'Twum Barimah', '0557447833', NULL, '1995-01-11', 'drivers/avatars/Fngh0Ewbaft7FysWGFo1Bq2M6OUhpKeWH2ZSibSB.png', 1, NULL, 1, 1, 0, 0, NULL, NULL, NULL, NULL, '2025-10-01 12:17:30', '2025-10-01 15:59:29', NULL),
(2, 'DRV91914', 'Philip', 'Ansah', '0203775656', NULL, '1995-12-01', NULL, 1, NULL, 0, 0, 0, 0, NULL, NULL, NULL, NULL, '2025-10-01 12:17:56', '2025-10-01 12:17:56', NULL),
(3, 'DRV44977', 'Derrick', 'Quaye', '0557447811', NULL, '1993-10-10', NULL, 1, NULL, 0, 0, 0, 0, NULL, NULL, NULL, NULL, '2025-10-10 19:01:41', '2025-10-10 19:01:41', NULL),
(4, 'DRV90419', 'Selasi', 'Brooks', '0208110682', NULL, '1995-11-01', NULL, 1, NULL, 0, 0, 0, 0, NULL, NULL, NULL, NULL, '2025-10-10 19:02:04', '2025-10-10 19:02:04', NULL),
(5, 'DRV30921', 'Clement', 'Boateng', '0208182191', NULL, '1995-01-01', NULL, 1, NULL, 0, 0, 0, 0, NULL, NULL, NULL, NULL, '2025-10-10 19:02:24', '2025-10-10 19:02:24', NULL),
(6, 'DRV60716', 'Felix', 'Amonu', '0244371117', NULL, '1974-01-01', NULL, 1, NULL, 0, 0, 0, 0, NULL, NULL, NULL, NULL, '2025-10-10 19:02:57', '2025-10-10 19:02:57', NULL),
(7, 'DRV98760', 'Kendrick', 'Block', '+233956275938', 'ashlynn.wisozk@example.com', '1983-07-19', NULL, 1, 4.84, 286, 268, 18, 0, NULL, NULL, NULL, 'Eaque voluptatem cupiditate quis eum vitae.', '2025-10-16 20:55:24', '2025-10-16 20:55:24', NULL),
(8, 'DRV29880', 'Jerrell', 'Ryan', '+233397348515', 'nratke@example.com', '1984-09-04', NULL, 1, 4.27, 288, 273, 15, 0, NULL, NULL, NULL, 'Est amet quidem eos ut non soluta alias.', '2025-10-16 20:55:24', '2025-10-16 20:55:24', NULL),
(9, 'DRV71290', 'Brianne', 'Lesch', '+233933155542', 'edgardo.rippin@example.org', '1999-02-02', NULL, 1, 4.19, 272, 263, 9, 0, NULL, NULL, NULL, NULL, '2025-10-16 20:55:24', '2025-10-16 20:55:24', NULL),
(10, 'DRV53329', 'May', 'Reinger', '+233319448784', 'schumm.trudie@example.com', '1997-11-04', NULL, 1, 4.79, 68, 58, 10, 0, NULL, NULL, NULL, 'Velit minus error eum illum et sit.', '2025-10-16 20:55:24', '2025-10-16 20:55:24', NULL),
(11, 'DRV17906', 'Reid', 'Gusikowski', '+233083479311', 'bruce56@example.net', '1976-10-20', NULL, 1, 5.00, 143, 131, 12, 0, NULL, NULL, NULL, NULL, '2025-10-16 20:55:24', '2025-10-17 00:47:07', NULL),
(12, 'DRV33187', 'Ezekiel', 'Stehr', '+233029890470', 'parmstrong@example.org', '1972-10-09', NULL, 1, 4.56, 470, 451, 19, 0, NULL, NULL, NULL, 'Enim ut vero voluptas.', '2025-10-16 20:55:24', '2025-10-16 20:55:24', NULL),
(13, 'DRV37418', 'Diego', 'Kerluke', '+233990328106', 'nikita47@example.com', '1989-04-11', NULL, 1, 3.84, 309, 293, 16, 0, NULL, NULL, NULL, 'Voluptatem consectetur debitis dolorem quod laudantium voluptas quod.', '2025-10-16 20:55:24', '2025-10-16 20:55:24', NULL),
(14, 'DRV36465', 'King', 'Prohaska', '+233942863088', 'domingo.bosco@example.com', '1976-03-04', NULL, 1, 3.50, 75, 67, 8, 0, NULL, NULL, NULL, NULL, '2025-10-16 20:55:24', '2025-10-16 20:55:24', NULL),
(15, 'DRV85994', 'Zachary', 'Breitenberg', '+233480401280', 'adrain.goldner@example.org', '1988-05-21', NULL, 1, 3.98, 317, 291, 26, 0, NULL, NULL, NULL, NULL, '2025-10-16 20:55:24', '2025-10-16 20:55:24', NULL),
(16, 'DRV64551', 'Rubie', 'Labadie', '+233064777221', 'lleffler@example.com', '1973-12-24', NULL, 1, 4.61, 399, 371, 28, 0, NULL, NULL, NULL, NULL, '2025-10-16 20:55:24', '2025-10-16 20:55:24', NULL),
(17, 'DRV44297', 'Tyson', 'Dare', '+233731130813', 'jedediah07@example.org', '1983-08-06', NULL, 1, 4.53, 346, 304, 42, 0, NULL, NULL, NULL, NULL, '2025-10-16 20:55:24', '2025-10-16 20:55:24', NULL),
(18, 'DRV26151', 'Presley', 'Homenick', '+233053789420', 'niko.witting@example.com', '1990-03-26', NULL, 1, 4.80, 72, 67, 5, 0, NULL, NULL, NULL, NULL, '2025-10-16 20:55:24', '2025-10-16 20:55:24', NULL),
(19, 'DRV44780', 'Alexandria', 'DuBuque', '+233326868302', 'wwehner@example.com', '1981-03-06', NULL, 1, 4.67, 401, 360, 42, 0, NULL, NULL, NULL, NULL, '2025-10-16 20:55:24', '2025-10-17 00:19:33', NULL),
(20, 'DRV78747', 'Joshuah', 'Champlin', '+233430429090', 'ebba.ondricka@example.org', '1979-10-12', NULL, 1, 4.52, 235, 211, 24, 0, NULL, NULL, NULL, NULL, '2025-10-16 20:55:24', '2025-10-16 20:55:24', NULL),
(21, 'DRV30717', 'Earline', 'Boyle', '+233826593913', 'pmraz@example.org', '1972-05-23', NULL, 1, 4.06, 350, 304, 46, 0, NULL, NULL, NULL, 'Sit ut beatae ut dolores blanditiis dignissimos.', '2025-10-16 20:55:24', '2025-10-16 20:55:24', NULL),
(22, 'DRV81077', 'Eugenia', 'Bergnaum', '+233677394486', 'yasmine76@example.org', '1993-12-09', NULL, 1, 4.42, 190, 174, 16, 0, NULL, NULL, NULL, NULL, '2025-10-16 20:55:24', '2025-10-16 20:55:24', NULL),
(23, 'DRV72729', 'Kirsten', 'Ferry', '+233022039675', 'nels39@example.net', '1975-10-24', NULL, 1, 4.44, 479, 411, 68, 0, NULL, NULL, NULL, NULL, '2025-10-16 20:55:24', '2025-10-16 20:55:24', NULL),
(24, 'DRV98586', 'Dakota', 'Bauch', '+233476099945', 'eleanore.williamson@example.com', '1990-08-15', NULL, 1, 4.74, 289, 280, 9, 0, NULL, NULL, NULL, NULL, '2025-10-16 20:55:24', '2025-10-16 20:55:24', NULL),
(25, 'DRV08915', 'Itzel', 'Stanton', '+233019880761', 'legros.jazmyne@example.org', '1979-07-08', NULL, 1, 3.94, 73, 62, 11, 0, NULL, NULL, NULL, NULL, '2025-10-16 20:55:24', '2025-10-16 20:55:24', NULL),
(26, 'DRV03217', 'Carmelo', 'Luettgen', '+233647134055', 'vella42@example.net', '1980-11-22', NULL, 1, 3.54, 268, 254, 14, 0, NULL, NULL, NULL, NULL, '2025-10-16 20:55:24', '2025-10-16 20:55:24', NULL),
(27, 'DRV82536', 'Braxton', 'Nienow', '+233956379685', 'syble.lesch@example.com', '1977-08-10', NULL, 0, 4.08, 41, 30, 11, 1, '2025-10-10 14:33:36', 'Unprofessional conduct', NULL, 'Neque enim corrupti est et autem qui ex.', '2025-10-16 20:55:24', '2025-10-16 20:55:24', NULL),
(28, 'DRV71622', 'Jamal', 'Wuckert', '+233115739090', 'murazik.clarissa@example.org', '1993-12-18', NULL, 0, 4.04, 21, 17, 4, 1, '2025-10-06 10:29:58', 'Multiple customer complaints', NULL, 'Quis ut occaecati quia molestiae atque ratione.', '2025-10-16 20:55:24', '2025-10-16 20:55:24', NULL),
(29, 'DRV56315', 'Jaylin', 'Zemlak', '+233536748854', 'eryn.beer@example.com', '1999-07-29', NULL, 0, 2.22, 79, 65, 14, 1, '2025-10-14 18:06:44', 'Unprofessional conduct', NULL, 'Quia omnis est neque ratione.', '2025-10-16 20:55:24', '2025-10-16 20:55:24', NULL),
(30, 'DRV13679', 'Adolphus', 'Ullrich', '+233073424468', 'msporer@example.net', '1972-11-19', NULL, 0, 2.86, 96, 79, 17, 1, '2025-09-20 05:45:14', 'Unprofessional conduct', NULL, 'Sunt consequatur nihil necessitatibus delectus.', '2025-10-16 20:55:24', '2025-10-16 20:55:24', NULL),
(31, 'DRV55778', 'Boyd', 'Littel', '+233981356113', 'jett65@example.net', '1993-01-07', NULL, 0, 4.38, 96, 77, 19, 1, '2025-10-14 08:09:31', 'Safety violation', NULL, 'Laboriosam rerum cumque voluptatem fugiat sed non.', '2025-10-16 20:55:24', '2025-10-16 20:55:24', NULL),
(32, 'DRV50025', 'Estelle', 'Jakubowski', '+233270144723', 'ioberbrunner@example.net', '1990-05-26', NULL, 1, NULL, 0, 0, 0, 0, NULL, NULL, NULL, 'New driver - pending first assignment', '2025-10-16 20:55:24', '2025-10-16 20:55:24', NULL),
(33, 'DRV69209', 'Lonie', 'Greenfelder', '+233674356015', 'don.block@example.com', '1998-07-26', NULL, 1, NULL, 0, 0, 0, 0, NULL, NULL, NULL, 'New driver - pending first assignment', '2025-10-16 20:55:24', '2025-10-16 20:55:24', NULL),
(34, 'DRV90482', 'Tyler', 'Nolan', '+233794920706', 'kevin.hamill@example.com', '1983-01-08', NULL, 1, NULL, 0, 0, 0, 0, NULL, NULL, NULL, 'New driver - pending first assignment', '2025-10-16 20:55:24', '2025-10-16 20:55:24', NULL),
(35, 'DRV18951', 'Drake', 'Kunze', '+233622390300', 'willa.schneider@example.com', '1989-09-17', NULL, 1, NULL, 0, 0, 0, 0, NULL, NULL, NULL, 'New driver - pending first assignment', '2025-10-16 20:55:24', '2025-10-16 20:55:24', NULL),
(36, 'DRV40190', 'Clifford', 'Willms', '+233191827345', 'mpfeffer@example.org', '2000-02-27', NULL, 1, NULL, 0, 0, 0, 0, NULL, NULL, NULL, 'New driver - pending first assignment', '2025-10-16 20:55:24', '2025-10-16 20:55:24', NULL),
(37, 'DRV87979', 'Roderick', 'Torp', '+233464960449', 'xfritsch@example.org', '1984-04-23', NULL, 1, 4.92, 872, 845, 27, 0, NULL, NULL, NULL, 'Top performer - excellent track record', '2025-10-16 20:55:24', '2025-10-16 20:55:24', NULL),
(38, 'DRV10569', 'Veronica', 'Johns', '+233925213392', 'alanna07@example.com', '1995-09-02', NULL, 1, 4.97, 917, 880, 37, 0, NULL, NULL, NULL, 'Top performer - excellent track record', '2025-10-16 20:55:24', '2025-10-16 20:55:24', NULL),
(39, 'DRV64221', 'Lillian', 'Erdman', '+233994441054', 'myrna16@example.net', '1992-12-01', NULL, 1, 4.80, 922, 885, 37, 0, NULL, NULL, NULL, 'Top performer - excellent track record', '2025-10-16 20:55:24', '2025-10-16 20:55:24', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `driver_vehicle_assignments`
--

CREATE TABLE `driver_vehicle_assignments` (
  `id` bigint UNSIGNED NOT NULL,
  `driver_id` bigint UNSIGNED NOT NULL,
  `vehicle_id` bigint UNSIGNED NOT NULL,
  `assigned_at` datetime NOT NULL,
  `unassigned_at` datetime DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `assigned_by` bigint UNSIGNED NOT NULL,
  `unassigned_by` bigint UNSIGNED DEFAULT NULL,
  `assignment_notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `unassignment_reason` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `status` enum('active','inactive','temporary') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `effective_from` datetime DEFAULT NULL,
  `effective_until` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `driver_vehicle_assignments`
--

INSERT INTO `driver_vehicle_assignments` (`id`, `driver_id`, `vehicle_id`, `assigned_at`, `unassigned_at`, `is_active`, `assigned_by`, `unassigned_by`, `assignment_notes`, `unassignment_reason`, `status`, `effective_from`, `effective_until`, `created_at`, `updated_at`) VALUES
(9, 1, 34, '2025-10-04 21:51:46', NULL, 1, 1, NULL, 'Permanent assignment for regular duties', NULL, 'active', '2025-09-28 21:51:46', NULL, '2025-10-16 21:51:46', '2025-10-16 21:51:46'),
(10, 2, 35, '2025-10-13 21:51:46', NULL, 1, 1, NULL, 'Permanent assignment for regular duties', NULL, 'active', '2025-10-09 21:51:46', NULL, '2025-10-16 21:51:46', '2025-10-16 21:51:46'),
(11, 3, 36, '2025-10-08 21:51:46', NULL, 1, 1, NULL, 'Permanent assignment for regular duties', NULL, 'active', '2025-09-19 21:51:46', NULL, '2025-10-16 21:51:46', '2025-10-16 21:51:46'),
(12, 4, 37, '2025-09-28 21:51:46', NULL, 1, 1, NULL, 'Permanent assignment for regular duties', NULL, 'active', '2025-10-02 21:51:46', NULL, '2025-10-16 21:51:46', '2025-10-16 21:51:46'),
(13, 5, 38, '2025-10-08 21:51:46', NULL, 1, 1, NULL, 'Permanent assignment for regular duties', NULL, 'active', '2025-10-10 21:51:46', NULL, '2025-10-16 21:51:46', '2025-10-16 21:51:46'),
(14, 6, 39, '2025-09-24 21:51:46', NULL, 1, 1, NULL, 'Permanent assignment for regular duties', NULL, 'active', '2025-09-21 21:51:46', NULL, '2025-10-16 21:51:46', '2025-10-16 21:51:46'),
(15, 7, 40, '2025-09-27 21:51:46', NULL, 1, 1, NULL, 'Permanent assignment for regular duties', NULL, 'active', '2025-10-13 21:51:46', NULL, '2025-10-16 21:51:46', '2025-10-16 21:51:46'),
(16, 8, 41, '2025-09-26 21:51:46', NULL, 1, 1, NULL, 'Permanent assignment for regular duties', NULL, 'active', '2025-09-22 21:51:46', NULL, '2025-10-16 21:51:46', '2025-10-16 21:51:46'),
(17, 9, 42, '2025-09-22 21:51:46', NULL, 1, 1, NULL, 'Permanent assignment for regular duties', NULL, 'active', '2025-09-28 21:51:46', NULL, '2025-10-16 21:51:46', '2025-10-16 21:51:46'),
(18, 10, 43, '2025-10-12 21:51:46', NULL, 1, 1, NULL, 'Permanent assignment for regular duties', NULL, 'active', '2025-10-12 21:51:46', NULL, '2025-10-16 21:51:46', '2025-10-16 21:51:46'),
(19, 11, 44, '2025-10-06 21:51:46', NULL, 1, 1, NULL, 'Permanent assignment for regular duties', NULL, 'active', '2025-10-12 21:51:46', NULL, '2025-10-16 21:51:46', '2025-10-16 21:51:46'),
(20, 12, 45, '2025-10-13 21:51:46', NULL, 1, 1, NULL, 'Permanent assignment for regular duties', NULL, 'active', '2025-10-12 21:51:46', NULL, '2025-10-16 21:51:46', '2025-10-16 21:51:46'),
(21, 13, 46, '2025-10-15 21:51:46', NULL, 1, 1, NULL, 'Permanent assignment for regular duties', NULL, 'active', '2025-10-11 21:51:46', NULL, '2025-10-16 21:51:46', '2025-10-16 21:51:46'),
(22, 14, 47, '2025-09-22 21:51:46', NULL, 1, 1, NULL, 'Permanent assignment for regular duties', NULL, 'active', '2025-10-01 21:51:46', NULL, '2025-10-16 21:51:46', '2025-10-16 21:51:46'),
(23, 15, 48, '2025-09-29 21:51:46', NULL, 1, 1, NULL, 'Permanent assignment for regular duties', NULL, 'active', '2025-10-09 21:51:46', NULL, '2025-10-16 21:51:46', '2025-10-16 21:51:46'),
(24, 16, 49, '2025-10-13 21:51:46', NULL, 1, 1, NULL, 'Permanent assignment for regular duties', NULL, 'active', '2025-10-09 21:51:46', NULL, '2025-10-16 21:51:46', '2025-10-16 21:51:46'),
(25, 17, 50, '2025-09-30 21:51:46', NULL, 1, 1, NULL, 'Permanent assignment for regular duties', NULL, 'active', '2025-09-18 21:51:46', NULL, '2025-10-16 21:51:46', '2025-10-16 21:51:46'),
(26, 18, 51, '2025-09-19 21:51:46', NULL, 1, 1, NULL, 'Permanent assignment for regular duties', NULL, 'active', '2025-10-14 21:51:46', NULL, '2025-10-16 21:51:46', '2025-10-16 21:51:46'),
(27, 19, 52, '2025-10-07 21:51:46', NULL, 1, 1, NULL, 'Permanent assignment for regular duties', NULL, 'active', '2025-10-14 21:51:46', NULL, '2025-10-16 21:51:46', '2025-10-16 21:51:46'),
(28, 20, 53, '2025-10-06 21:51:46', NULL, 1, 1, NULL, 'Permanent assignment for regular duties', NULL, 'active', '2025-09-20 21:51:46', NULL, '2025-10-16 21:51:46', '2025-10-16 21:51:46');

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint UNSIGNED NOT NULL,
  `uuid` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `failed_jobs`
--

INSERT INTO `failed_jobs` (`id`, `uuid`, `connection`, `queue`, `payload`, `exception`, `failed_at`) VALUES
(7, '4fc42de0-e380-4653-afa5-f15d19c54546', 'database', 'default', '{\"uuid\":\"4fc42de0-e380-4653-afa5-f15d19c54546\",\"displayName\":\"App\\\\Notifications\\\\TwoFactorAuthNotification\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Notifications\\\\SendQueuedNotifications\",\"command\":\"O:48:\\\"Illuminate\\\\Notifications\\\\SendQueuedNotifications\\\":3:{s:11:\\\"notifiables\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:15:\\\"App\\\\Models\\\\User\\\";s:2:\\\"id\\\";a:1:{i:0;i:5;}s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:12:\\\"notification\\\";O:43:\\\"App\\\\Notifications\\\\TwoFactorAuthNotification\\\":3:{s:6:\\\"\\u0000*\\u0000otp\\\";s:6:\\\"426286\\\";s:12:\\\"\\u0000*\\u0000expiresAt\\\";O:13:\\\"Carbon\\\\Carbon\\\":3:{s:4:\\\"date\\\";s:26:\\\"2025-11-04 01:25:09.221992\\\";s:13:\\\"timezone_type\\\";i:3;s:8:\\\"timezone\\\";s:3:\\\"UTC\\\";}s:2:\\\"id\\\";s:36:\\\"e978e496-fe6e-4f2f-80af-b3802f26972f\\\";}s:8:\\\"channels\\\";a:1:{i:0;s:4:\\\"mail\\\";}}\"},\"createdAt\":1762218910,\"delay\":null}', 'Symfony\\Component\\Mailer\\Exception\\UnexpectedResponseException: Expected response code \"250\" but got code \"421\", with message \"421 4.4.2 smtp.hostinger.com Error: timeout exceeded\". in /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/symfony/mailer/Transport/Smtp/SmtpTransport.php:342\nStack trace:\n#0 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/symfony/mailer/Transport/Smtp/SmtpTransport.php(198): Symfony\\Component\\Mailer\\Transport\\Smtp\\SmtpTransport->assertResponseCode(\'421 4.4.2 smtp....\', Array)\n#1 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/symfony/mailer/Transport/Smtp/EsmtpTransport.php(150): Symfony\\Component\\Mailer\\Transport\\Smtp\\SmtpTransport->executeCommand(\'MAIL FROM:<info...\', Array)\n#2 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/symfony/mailer/Transport/Smtp/SmtpTransport.php(263): Symfony\\Component\\Mailer\\Transport\\Smtp\\EsmtpTransport->executeCommand(\'MAIL FROM:<info...\', Array)\n#3 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/symfony/mailer/Transport/Smtp/SmtpTransport.php(215): Symfony\\Component\\Mailer\\Transport\\Smtp\\SmtpTransport->doMailFromCommand(\'info@junitsolut...\', false)\n#4 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/symfony/mailer/Transport/AbstractTransport.php(69): Symfony\\Component\\Mailer\\Transport\\Smtp\\SmtpTransport->doSend(Object(Symfony\\Component\\Mailer\\SentMessage))\n#5 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/symfony/mailer/Transport/Smtp/SmtpTransport.php(138): Symfony\\Component\\Mailer\\Transport\\AbstractTransport->send(Object(Symfony\\Component\\Mime\\Email), Object(Symfony\\Component\\Mailer\\DelayedEnvelope))\n#6 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Mail/Mailer.php(584): Symfony\\Component\\Mailer\\Transport\\Smtp\\SmtpTransport->send(Object(Symfony\\Component\\Mime\\Email), Object(Symfony\\Component\\Mailer\\DelayedEnvelope))\n#7 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Mail/Mailer.php(331): Illuminate\\Mail\\Mailer->sendSymfonyMessage(Object(Symfony\\Component\\Mime\\Email))\n#8 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Notifications/Channels/MailChannel.php(66): Illuminate\\Mail\\Mailer->send(Object(Closure), Array, Object(Closure))\n#9 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Notifications/NotificationSender.php(163): Illuminate\\Notifications\\Channels\\MailChannel->send(Object(App\\Models\\User), Object(App\\Notifications\\TwoFactorAuthNotification))\n#10 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Notifications/NotificationSender.php(118): Illuminate\\Notifications\\NotificationSender->sendToNotifiable(Object(App\\Models\\User), \'78ad089c-2897-4...\', Object(App\\Notifications\\TwoFactorAuthNotification), \'mail\')\n#11 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Support/Traits/Localizable.php(19): Illuminate\\Notifications\\NotificationSender->Illuminate\\Notifications\\{closure}()\n#12 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Notifications/NotificationSender.php(113): Illuminate\\Notifications\\NotificationSender->withLocale(NULL, Object(Closure))\n#13 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Notifications/ChannelManager.php(54): Illuminate\\Notifications\\NotificationSender->sendNow(Object(Illuminate\\Database\\Eloquent\\Collection), Object(App\\Notifications\\TwoFactorAuthNotification), Array)\n#14 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Notifications/SendQueuedNotifications.php(118): Illuminate\\Notifications\\ChannelManager->sendNow(Object(Illuminate\\Database\\Eloquent\\Collection), Object(App\\Notifications\\TwoFactorAuthNotification), Array)\n#15 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Container/BoundMethod.php(36): Illuminate\\Notifications\\SendQueuedNotifications->handle(Object(Illuminate\\Notifications\\ChannelManager))\n#16 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Container/Util.php(43): Illuminate\\Container\\BoundMethod::Illuminate\\Container\\{closure}()\n#17 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Container/BoundMethod.php(96): Illuminate\\Container\\Util::unwrapIfClosure(Object(Closure))\n#18 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Container/BoundMethod.php(35): Illuminate\\Container\\BoundMethod::callBoundMethod(Object(Illuminate\\Foundation\\Application), Array, Object(Closure))\n#19 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Container/Container.php(836): Illuminate\\Container\\BoundMethod::call(Object(Illuminate\\Foundation\\Application), Array, Array, NULL)\n#20 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Bus/Dispatcher.php(132): Illuminate\\Container\\Container->call(Array)\n#21 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(180): Illuminate\\Bus\\Dispatcher->Illuminate\\Bus\\{closure}(Object(Illuminate\\Notifications\\SendQueuedNotifications))\n#22 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(137): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}(Object(Illuminate\\Notifications\\SendQueuedNotifications))\n#23 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Bus/Dispatcher.php(136): Illuminate\\Pipeline\\Pipeline->then(Object(Closure))\n#24 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Queue/CallQueuedHandler.php(134): Illuminate\\Bus\\Dispatcher->dispatchNow(Object(Illuminate\\Notifications\\SendQueuedNotifications), false)\n#25 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(180): Illuminate\\Queue\\CallQueuedHandler->Illuminate\\Queue\\{closure}(Object(Illuminate\\Notifications\\SendQueuedNotifications))\n#26 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(137): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}(Object(Illuminate\\Notifications\\SendQueuedNotifications))\n#27 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Queue/CallQueuedHandler.php(127): Illuminate\\Pipeline\\Pipeline->then(Object(Closure))\n#28 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Queue/CallQueuedHandler.php(68): Illuminate\\Queue\\CallQueuedHandler->dispatchThroughMiddleware(Object(Illuminate\\Queue\\Jobs\\DatabaseJob), Object(Illuminate\\Notifications\\SendQueuedNotifications))\n#29 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Queue/Jobs/Job.php(102): Illuminate\\Queue\\CallQueuedHandler->call(Object(Illuminate\\Queue\\Jobs\\DatabaseJob), Array)\n#30 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Queue/Worker.php(451): Illuminate\\Queue\\Jobs\\Job->fire()\n#31 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Queue/Worker.php(401): Illuminate\\Queue\\Worker->process(\'database\', Object(Illuminate\\Queue\\Jobs\\DatabaseJob), Object(Illuminate\\Queue\\WorkerOptions))\n#32 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Queue/Worker.php(187): Illuminate\\Queue\\Worker->runJob(Object(Illuminate\\Queue\\Jobs\\DatabaseJob), \'database\', Object(Illuminate\\Queue\\WorkerOptions))\n#33 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Queue/Console/WorkCommand.php(148): Illuminate\\Queue\\Worker->daemon(\'database\', \'default\', Object(Illuminate\\Queue\\WorkerOptions))\n#34 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Queue/Console/WorkCommand.php(131): Illuminate\\Queue\\Console\\WorkCommand->runWorker(\'database\', \'default\')\n#35 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Container/BoundMethod.php(36): Illuminate\\Queue\\Console\\WorkCommand->handle()\n#36 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Container/Util.php(43): Illuminate\\Container\\BoundMethod::Illuminate\\Container\\{closure}()\n#37 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Container/BoundMethod.php(96): Illuminate\\Container\\Util::unwrapIfClosure(Object(Closure))\n#38 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Container/BoundMethod.php(35): Illuminate\\Container\\BoundMethod::callBoundMethod(Object(Illuminate\\Foundation\\Application), Array, Object(Closure))\n#39 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Container/Container.php(836): Illuminate\\Container\\BoundMethod::call(Object(Illuminate\\Foundation\\Application), Array, Array, NULL)\n#40 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Console/Command.php(211): Illuminate\\Container\\Container->call(Array)\n#41 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/symfony/console/Command/Command.php(318): Illuminate\\Console\\Command->execute(Object(Symfony\\Component\\Console\\Input\\ArgvInput), Object(Illuminate\\Console\\OutputStyle))\n#42 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Console/Command.php(180): Symfony\\Component\\Console\\Command\\Command->run(Object(Symfony\\Component\\Console\\Input\\ArgvInput), Object(Illuminate\\Console\\OutputStyle))\n#43 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/symfony/console/Application.php(1110): Illuminate\\Console\\Command->run(Object(Symfony\\Component\\Console\\Input\\ArgvInput), Object(Symfony\\Component\\Console\\Output\\ConsoleOutput))\n#44 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/symfony/console/Application.php(359): Symfony\\Component\\Console\\Application->doRunCommand(Object(Illuminate\\Queue\\Console\\WorkCommand), Object(Symfony\\Component\\Console\\Input\\ArgvInput), Object(Symfony\\Component\\Console\\Output\\ConsoleOutput))\n#45 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/symfony/console/Application.php(194): Symfony\\Component\\Console\\Application->doRun(Object(Symfony\\Component\\Console\\Input\\ArgvInput), Object(Symfony\\Component\\Console\\Output\\ConsoleOutput))\n#46 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Foundation/Console/Kernel.php(197): Symfony\\Component\\Console\\Application->run(Object(Symfony\\Component\\Console\\Input\\ArgvInput), Object(Symfony\\Component\\Console\\Output\\ConsoleOutput))\n#47 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Foundation/Application.php(1235): Illuminate\\Foundation\\Console\\Kernel->handle(Object(Symfony\\Component\\Console\\Input\\ArgvInput), Object(Symfony\\Component\\Console\\Output\\ConsoleOutput))\n#48 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/artisan(16): Illuminate\\Foundation\\Application->handleCommand(Object(Symfony\\Component\\Console\\Input\\ArgvInput))\n#49 {main}', '2025-11-04 01:15:11');

-- --------------------------------------------------------

--
-- Table structure for table `incident_reports`
--

CREATE TABLE `incident_reports` (
  `id` bigint UNSIGNED NOT NULL,
  `report_date` date NOT NULL,
  `incident_date` date NOT NULL,
  `incident_time` time NOT NULL,
  `incident_location` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `incident_type` enum('fall','medication_error','equipment_failure','injury','other') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `incident_type_other` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `patient_id` bigint UNSIGNED DEFAULT NULL,
  `patient_age` int DEFAULT NULL,
  `patient_sex` enum('M','F') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `client_id_case_no` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `staff_family_involved` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `staff_family_role` enum('nurse','family','other') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `staff_family_role_other` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `incident_description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `first_aid_provided` tinyint(1) NOT NULL DEFAULT '0',
  `first_aid_description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `care_provider_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `transferred_to_hospital` tinyint(1) NOT NULL DEFAULT '0',
  `hospital_transfer_details` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `witness_names` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `witness_contacts` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `reported_to_supervisor` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `corrective_preventive_actions` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `reported_by` bigint UNSIGNED NOT NULL,
  `reported_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `reviewed_by` bigint UNSIGNED DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `status` enum('pending','under_review','investigated','resolved','closed') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `severity` enum('low','medium','high','critical') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'medium',
  `follow_up_required` tinyint(1) NOT NULL DEFAULT '0',
  `follow_up_date` date DEFAULT NULL,
  `assigned_to` bigint UNSIGNED DEFAULT NULL,
  `last_updated_by` bigint UNSIGNED DEFAULT NULL,
  `closed_by` bigint UNSIGNED DEFAULT NULL,
  `closed_at` timestamp NULL DEFAULT NULL,
  `closure_reason` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `resolved_by` bigint UNSIGNED DEFAULT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `attachments` json DEFAULT NULL COMMENT 'Array of file paths for photos, documents, etc.',
  `investigation_notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `final_resolution` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `prevention_measures` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `incident_reports`
--

INSERT INTO `incident_reports` (`id`, `report_date`, `incident_date`, `incident_time`, `incident_location`, `incident_type`, `incident_type_other`, `patient_id`, `patient_age`, `patient_sex`, `client_id_case_no`, `staff_family_involved`, `staff_family_role`, `staff_family_role_other`, `incident_description`, `first_aid_provided`, `first_aid_description`, `care_provider_name`, `transferred_to_hospital`, `hospital_transfer_details`, `witness_names`, `witness_contacts`, `reported_to_supervisor`, `corrective_preventive_actions`, `reported_by`, `reported_at`, `reviewed_by`, `reviewed_at`, `status`, `severity`, `follow_up_required`, `follow_up_date`, `assigned_to`, `last_updated_by`, `closed_by`, `closed_at`, `closure_reason`, `resolved_by`, `resolved_at`, `attachments`, `investigation_notes`, `final_resolution`, `prevention_measures`, `created_at`, `updated_at`) VALUES
(1, '2025-10-09', '2025-10-09', '21:52:00', 'Patient’s Living Room', 'equipment_failure', NULL, 8, 34, 'M', '389239', 'Ronald Tagoe', 'family', NULL, 'Patient fell and equipment failed', 1, 'Paracetamol', 'Nurse Lisa', 1, 'Transferred Korle-Bu Teaching Hospital', 'James Manor', NULL, 'Selasi Alazo', NULL, 4, '2025-10-09 21:55:56', NULL, NULL, 'pending', 'critical', 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-09 21:55:56', '2025-10-09 21:55:56'),
(2, '2025-10-28', '2025-10-28', '15:23:00', 'East Cantonments', 'fall', NULL, 7, 28, 'M', 'WA84893', 'Bernard Quaye', 'family', NULL, 'Patient hit the head to the ground', 1, 'Yes, First Aid provided', 'Lisa Johnson', 1, 'Transfer by Judy health driver to Ashongman Community Hospital', NULL, NULL, 'Frances', 'Yes, many taken', 4, '2025-10-28 16:14:49', 2, '2025-10-28 16:15:43', 'resolved', 'high', 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-28 16:14:49', '2025-10-28 16:16:08'),
(3, '2025-10-28', '2025-10-28', '15:23:00', 'East Cantonments', 'fall', NULL, 7, 28, 'M', 'WA84893', 'Bernard Quaye', 'family', NULL, 'Patient hit the head to the ground', 1, 'Yes, First Aid provided', 'Lisa Johnson', 1, 'Transfer by Judy health driver to Ashongman Community Hospital', NULL, NULL, 'Frances', 'Yes, many taken', 4, '2025-10-28 16:14:49', 2, '2025-10-28 16:15:43', 'resolved', 'high', 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-28 16:14:49', '2025-10-28 16:16:08'),
(4, '2025-10-28', '2025-10-28', '15:23:00', 'East Cantonments', 'fall', NULL, 7, 28, 'M', 'WA84893', 'Bernard Quaye', 'family', NULL, 'Patient hit the head to the ground', 1, 'Yes, First Aid provided', 'Lisa Johnson', 1, 'Transfer by Judy health driver to Ashongman Community Hospital', NULL, NULL, 'Frances', 'Yes, many taken', 4, '2025-10-28 16:14:49', 2, '2025-10-28 16:15:43', 'resolved', 'high', 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-28 16:14:49', '2025-10-28 16:16:08'),
(5, '2025-10-28', '2025-10-28', '15:23:00', 'East Cantonments', 'fall', NULL, 7, 28, 'M', 'WA84893', 'Bernard Quaye', 'family', NULL, 'Patient hit the head to the ground', 1, 'Yes, First Aid provided', 'Lisa Johnson', 1, 'Transfer by Judy health driver to Ashongman Community Hospital', NULL, NULL, 'Frances', 'Yes, many taken', 4, '2025-10-28 16:14:49', 2, '2025-10-28 16:15:43', 'resolved', 'high', 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-28 16:14:49', '2025-10-28 16:16:08'),
(6, '2025-10-28', '2025-10-28', '15:23:00', 'East Cantonments', 'fall', NULL, 7, 28, 'M', 'WA84893', 'Bernard Quaye', 'family', NULL, 'Patient hit the head to the ground', 1, 'Yes, First Aid provided', 'Lisa Johnson', 1, 'Transfer by Judy health driver to Ashongman Community Hospital', NULL, NULL, 'Frances', 'Yes, many taken', 4, '2025-10-28 16:14:49', 2, '2025-10-28 16:15:43', 'resolved', 'high', 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-28 16:14:49', '2025-10-28 16:16:08'),
(7, '2025-10-28', '2025-10-28', '15:23:00', 'East Cantonments', 'fall', NULL, 7, 28, 'M', 'WA84893', 'Bernard Quaye', 'family', NULL, 'Patient hit the head to the ground', 1, 'Yes, First Aid provided', 'Lisa Johnson', 1, 'Transfer by Judy health driver to Ashongman Community Hospital', NULL, NULL, 'Frances', 'Yes, many taken', 4, '2025-10-28 16:14:49', 2, '2025-10-28 16:15:43', 'resolved', 'high', 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-28 16:14:49', '2025-10-28 16:16:08'),
(8, '2025-10-28', '2025-10-28', '15:23:00', 'East Cantonments', 'fall', NULL, 7, 28, 'M', 'WA84893', 'Bernard Quaye', 'family', NULL, 'Patient hit the head to the ground', 1, 'Yes, First Aid provided', 'Lisa Johnson', 1, 'Transfer by Judy health driver to Ashongman Community Hospital', NULL, NULL, 'Frances', 'Yes, many taken', 4, '2025-10-28 16:14:49', 2, '2025-10-28 16:15:43', 'resolved', 'high', 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-28 16:14:49', '2025-10-28 16:16:08'),
(9, '2025-10-28', '2025-10-28', '15:23:00', 'East Cantonments', 'fall', NULL, 7, 28, 'M', 'WA84893', 'Bernard Quaye', 'family', NULL, 'Patient hit the head to the ground', 1, 'Yes, First Aid provided', 'Lisa Johnson', 1, 'Transfer by Judy health driver to Ashongman Community Hospital', NULL, NULL, 'Frances', 'Yes, many taken', 4, '2025-10-28 16:14:49', 2, '2025-10-28 16:15:43', 'resolved', 'high', 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-28 16:14:49', '2025-10-28 16:16:08'),
(10, '2025-10-28', '2025-10-28', '15:23:00', 'East Cantonments', 'fall', NULL, 7, 28, 'M', 'WA84893', 'Bernard Quaye', 'family', NULL, 'Patient hit the head to the ground', 1, 'Yes, First Aid provided', 'Lisa Johnson', 1, 'Transfer by Judy health driver to Ashongman Community Hospital', NULL, NULL, 'Frances', 'Yes, many taken', 4, '2025-10-28 16:14:49', 2, '2025-10-28 16:15:43', 'resolved', 'high', 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-28 16:14:49', '2025-10-28 16:16:08'),
(11, '2025-10-28', '2025-10-28', '15:23:00', 'East Cantonments', 'fall', NULL, 7, 28, 'M', 'WA84893', 'Bernard Quaye', 'family', NULL, 'Patient hit the head to the ground', 1, 'Yes, First Aid provided', 'Lisa Johnson', 1, 'Transfer by Judy health driver to Ashongman Community Hospital', NULL, NULL, 'Frances', 'Yes, many taken', 4, '2025-10-28 16:14:49', 2, '2025-10-28 16:15:43', 'resolved', 'high', 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-28 16:14:49', '2025-10-28 16:16:08'),
(12, '2025-10-28', '2025-10-28', '15:23:00', 'East Cantonments', 'fall', NULL, 7, 28, 'M', 'WA84893', 'Bernard Quaye', 'family', NULL, 'Patient hit the head to the ground', 1, 'Yes, First Aid provided', 'Lisa Johnson', 1, 'Transfer by Judy health driver to Ashongman Community Hospital', NULL, NULL, 'Frances', 'Yes, many taken', 4, '2025-10-28 16:14:49', 2, '2025-10-28 16:15:43', 'resolved', 'high', 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-28 16:14:49', '2025-10-28 16:16:08'),
(13, '2025-10-28', '2025-10-28', '15:23:00', 'East Cantonments', 'fall', NULL, 7, 28, 'M', 'WA84893', 'Bernard Quaye', 'family', NULL, 'Patient hit the head to the ground', 1, 'Yes, First Aid provided', 'Lisa Johnson', 1, 'Transfer by Judy health driver to Ashongman Community Hospital', NULL, NULL, 'Frances', 'Yes, many taken', 4, '2025-10-28 16:14:49', 2, '2025-10-28 16:15:43', 'resolved', 'high', 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-28 16:14:49', '2025-10-28 16:16:08'),
(14, '2025-10-28', '2025-10-28', '15:23:00', 'East Cantonments', 'fall', NULL, 7, 28, 'M', 'WA84893', 'Bernard Quaye', 'family', NULL, 'Patient hit the head to the ground', 1, 'Yes, First Aid provided', 'Lisa Johnson', 1, 'Transfer by Judy health driver to Ashongman Community Hospital', NULL, NULL, 'Frances', 'Yes, many taken', 4, '2025-10-28 16:14:49', 2, '2025-10-28 16:15:43', 'resolved', 'high', 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-28 16:14:49', '2025-10-28 16:16:08'),
(15, '2025-10-28', '2025-10-28', '15:23:00', 'East Cantonments', 'fall', NULL, 7, 28, 'M', 'WA84893', 'Bernard Quaye', 'family', NULL, 'Patient hit the head to the ground', 1, 'Yes, First Aid provided', 'Lisa Johnson', 1, 'Transfer by Judy health driver to Ashongman Community Hospital', NULL, NULL, 'Frances', 'Yes, many taken', 4, '2025-10-28 16:14:49', 2, '2025-10-28 16:15:43', 'resolved', 'high', 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-28 16:14:49', '2025-10-28 16:16:08'),
(16, '2025-10-28', '2025-10-27', '15:23:00', 'East Cantonments', 'injury', NULL, 7, 28, 'M', 'WA84893', 'Bernard Quaye', 'family', NULL, 'Patient hit the head to the ground', 1, 'Yes, First Aid provided', 'Lisa Johnson', 1, 'Transfer by Judy health driver to Ashongman Community Hospital', NULL, NULL, 'Frances', 'Yes, many taken', 4, '2025-10-28 16:14:49', 2, '2025-10-28 16:15:43', 'resolved', 'high', 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-28 16:14:49', '2025-10-28 16:16:08'),
(17, '2025-10-28', '2025-10-28', '15:23:00', 'East Cantonments', 'medication_error', NULL, 7, 28, 'M', 'WA84893', 'Bernard Quaye', 'family', NULL, 'Patient hit the head to the ground', 1, 'Yes, First Aid provided', 'Lisa Johnson', 1, 'Transfer by Judy health driver to Ashongman Community Hospital', NULL, NULL, 'Frances', 'Yes, many taken', 4, '2025-10-28 16:14:49', 2, '2025-10-28 16:15:43', 'resolved', 'high', 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-28 16:14:49', '2025-10-28 16:16:08'),
(18, '2025-10-28', '2025-10-28', '15:23:00', 'East Cantonments', 'equipment_failure', NULL, 7, 28, 'M', 'WA84893', 'Bernard Quaye', 'family', NULL, 'Patient hit the head to the ground', 1, 'Yes, First Aid provided', 'Lisa Johnson', 1, 'Transfer by Judy health driver to Ashongman Community Hospital', NULL, NULL, 'Frances', 'Yes, many taken', 4, '2025-10-28 16:14:49', 2, '2025-10-28 16:15:43', 'resolved', 'high', 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-28 16:14:49', '2025-10-28 16:16:08'),
(19, '2025-11-02', '2025-11-02', '14:13:00', NULL, 'fall', NULL, 7, 28, 'M', '2378322', 'Derrick', 'family', NULL, 'Patient fell of the stairs and broke his neck', 0, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 4, '2025-11-02 14:14:23', NULL, NULL, 'pending', 'medium', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-02 14:14:23', '2025-11-02 14:14:23');

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `id` bigint UNSIGNED NOT NULL,
  `queue` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint UNSIGNED NOT NULL,
  `reserved_at` int UNSIGNED DEFAULT NULL,
  `available_at` int UNSIGNED NOT NULL,
  `created_at` int UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `jobs`
--

INSERT INTO `jobs` (`id`, `queue`, `payload`, `attempts`, `reserved_at`, `available_at`, `created_at`) VALUES
(63, 'emails', '{\"uuid\":\"afb23d86-02ec-4df2-9620-2e4b631e65d1\",\"displayName\":\"App\\\\Mail\\\\UserInvitationMail\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Mail\\\\SendQueuedMailable\",\"command\":\"O:34:\\\"Illuminate\\\\Mail\\\\SendQueuedMailable\\\":16:{s:8:\\\"mailable\\\";O:27:\\\"App\\\\Mail\\\\UserInvitationMail\\\":5:{s:4:\\\"user\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:15:\\\"App\\\\Models\\\\User\\\";s:2:\\\"id\\\";i:90;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:17:\\\"temporaryPassword\\\";s:12:\\\"myReoCzfUemJ\\\";s:2:\\\"to\\\";a:1:{i:0;a:2:{s:4:\\\"name\\\";N;s:7:\\\"address\\\";s:38:\\\"philipa.baafi@patient.judyhomecare.com\\\";}}s:6:\\\"mailer\\\";s:4:\\\"smtp\\\";s:5:\\\"queue\\\";s:6:\\\"emails\\\";}s:5:\\\"tries\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"maxExceptions\\\";N;s:17:\\\"shouldBeEncrypted\\\";b:0;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";s:6:\\\"emails\\\";s:12:\\\"messageGroup\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;s:3:\\\"job\\\";N;}\"},\"createdAt\":1762091450,\"delay\":null}', 0, NULL, 1762091450, 1762091450);

-- --------------------------------------------------------

--
-- Table structure for table `job_batches`
--

CREATE TABLE `job_batches` (
  `id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `login_history`
--

CREATE TABLE `login_history` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED DEFAULT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_agent` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `successful` tinyint(1) NOT NULL,
  `failure_reason` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `attempted_at` timestamp NOT NULL,
  `country` varchar(2) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `device_info` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `medical_assessments`
--

CREATE TABLE `medical_assessments` (
  `id` bigint UNSIGNED NOT NULL,
  `patient_id` bigint UNSIGNED NOT NULL,
  `nurse_id` bigint UNSIGNED NOT NULL,
  `physical_address` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `occupation` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `religion` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `emergency_contact_1_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `emergency_contact_1_relationship` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `emergency_contact_1_phone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `emergency_contact_2_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `emergency_contact_2_relationship` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `emergency_contact_2_phone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `presenting_condition` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `past_medical_history` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `allergies` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `current_medications` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `special_needs` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `general_condition` enum('stable','unstable') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `hydration_status` enum('adequate','dehydrated') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `nutrition_status` enum('adequate','malnourished') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `mobility_status` enum('independent','assisted','bedridden') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `has_wounds` tinyint(1) NOT NULL DEFAULT '0',
  `wound_description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `pain_level` int NOT NULL DEFAULT '0' COMMENT 'Pain scale 0-10',
  `initial_vitals` json NOT NULL,
  `initial_nursing_impression` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `assessment_status` enum('draft','completed','reviewed') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'completed',
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `medical_assessments`
--

INSERT INTO `medical_assessments` (`id`, `patient_id`, `nurse_id`, `physical_address`, `occupation`, `religion`, `emergency_contact_1_name`, `emergency_contact_1_relationship`, `emergency_contact_1_phone`, `emergency_contact_2_name`, `emergency_contact_2_relationship`, `emergency_contact_2_phone`, `presenting_condition`, `past_medical_history`, `allergies`, `current_medications`, `special_needs`, `general_condition`, `hydration_status`, `nutrition_status`, `mobility_status`, `has_wounds`, `wound_description`, `pain_level`, `initial_vitals`, `initial_nursing_impression`, `assessment_status`, `completed_at`, `created_at`, `updated_at`, `deleted_at`) VALUES
(7, 16, 4, 'Dome Pillar 2', 'Software Engineer', 'Christian', 'Emmanuel Armah', 'Father', '0208110620', 'Damian Armah', 'Brother', '0244371117', 'Condition Present', 'History Present', 'Allergies Present', 'Current Medication Present', 'Special needs present', 'unstable', 'adequate', 'adequate', 'assisted', 1, 'Bloody', 8, '{\"spo2\": 90, \"pulse\": 72, \"weight\": 70, \"temperature\": 37, \"blood_pressure\": \"122/94\", \"respiratory_rate\": 16}', 'Patient needs so much care now', 'completed', '2025-10-09 20:53:19', '2025-10-09 20:53:19', '2025-10-16 19:43:30', '2025-10-16 19:43:30'),
(8, 35, 57, 'House No. 212, AS Close, Madina, Sunyani, Ghana', 'Driver', 'Hindu', 'Patience Addo', 'Spouse', '0504470468', 'Afia Addo', 'Spouse', '0279970247', 'Advanced stage cancer palliative care', 'Hypertension for 10 years, controlled with medication. Previous myocardial infarction 5 years ago.', 'Iodine contrast dye - causes allergic reaction', 'Albuterol inhaler as needed, Advair 250/50 twice daily', 'Hearing impaired - uses hearing aids', 'stable', 'adequate', 'adequate', 'assisted', 1, 'Venous stasis ulcer located on right lower leg, measuring approximately 1.5cm x 2cm. Showing signs of healing, minimal drainage.', 4, '{\"spo2\": 96, \"pulse\": 57, \"weight\": 59, \"temperature\": 35.5, \"blood_pressure\": \"128/82\", \"respiratory_rate\": 15}', 'Patient experiencing increased confusion today compared to previous visits. Family reports this is a change from baseline. Vital signs show low-grade fever of 37.8°C. Possible urinary tract infection suspected. Recommend physician evaluation and possible urinalysis. Family educated on signs of delirium and when to seek emergency care.', 'reviewed', '2025-10-11 19:43:36', '2025-08-30 19:43:36', '2025-09-19 19:43:36', NULL),
(9, 19, 54, 'House No. 289, TG Crescent, Cantonments, Sunyani, Ghana', 'Retired', 'Christian', 'Samuel Yeboah', 'Cousin', '0272095301', 'Mensah Ofori', 'Father', '0245560633', 'Terminal illness end-of-life care', NULL, 'No known drug allergies', 'Levothyroxine 100mcg daily, Calcium with Vitamin D twice daily', 'Wound vac dressing changes twice weekly', 'unstable', 'adequate', 'malnourished', 'independent', 0, NULL, 8, '{\"spo2\": 88, \"pulse\": 112, \"weight\": 82.7, \"temperature\": 37.8, \"blood_pressure\": \"147/79\", \"respiratory_rate\": 25}', 'Patient appears comfortable at rest but reports moderate pain with movement. Vital signs are within acceptable limits. Wound shows signs of healing with no signs of infection. Patient demonstrates good understanding of medication regimen. Family members are supportive and engaged in care. Recommend continued monitoring of vital signs and pain management. Will reassess wound care needs at next visit.', 'completed', '2025-09-23 19:43:36', '2025-08-27 19:43:36', '2025-09-30 19:43:36', NULL),
(10, 8, 4, 'House No. 226, QI Close, Adenta, Ho, Ghana', 'Artisan', 'Muslim (Islam)', 'Kofi Opoku', 'Mother', '0260504107', 'Kojo Darko', 'Spouse', '0268312372', 'Terminal illness end-of-life care', NULL, 'Codeine - causes severe nausea', 'Metformin 500mg twice daily, Lisinopril 10mg daily, Atorvastatin 20mg at bedtime', NULL, 'stable', 'dehydrated', 'adequate', 'bedridden', 0, NULL, 8, '{\"spo2\": 98, \"pulse\": 93, \"weight\": 80.5, \"temperature\": 36.6, \"blood_pressure\": \"167/91\", \"respiratory_rate\": 17}', 'Patient appears withdrawn and reports feeling depressed. Vital signs stable. Family reports patient is sleeping more during the day and less engaged in activities. Recommend evaluation for depression and possible medication adjustment. Social support appears adequate but patient may benefit from counseling services.', 'reviewed', '2025-10-02 19:43:36', '2025-10-07 19:43:36', '2025-09-17 19:43:36', NULL),
(11, 39, 67, 'House No. 460, DN Street, Sakaman, Obuasi, Ghana', 'Farmer', 'Buddhist', 'Nana Sarpong', 'Brother', '0202644113', 'Mercy Opoku', 'Caregiver', '0279924723', 'Congestive heart failure management', 'History of deep vein thrombosis. On anticoagulation therapy.', 'Iodine contrast dye - causes allergic reaction', 'Aspirin 81mg daily, Clopidogrel 75mg daily, Furosemide 40mg daily', NULL, 'unstable', 'adequate', 'adequate', 'assisted', 1, 'Stage III pressure injury located on right heel, measuring approximately 2cm x 3cm. Redness and warmth around edges, possible infection.', 10, '{\"spo2\": 99, \"pulse\": 76, \"weight\": 88.1, \"temperature\": 38.6, \"blood_pressure\": \"141/65\", \"respiratory_rate\": 27}', 'Patient experiencing increased confusion today compared to previous visits. Family reports this is a change from baseline. Vital signs show low-grade fever of 37.8°C. Possible urinary tract infection suspected. Recommend physician evaluation and possible urinalysis. Family educated on signs of delirium and when to seek emergency care.', 'completed', '2025-10-12 19:43:36', '2025-09-24 19:43:36', '2025-09-23 19:43:36', NULL),
(12, 20, 56, 'House No. 437, UA Road, Teshie, Obuasi, Ghana', 'Teacher', 'Buddhist', 'Kwabena Yeboah', 'Spouse', '0260636401', 'Emmanuel Acheampong', 'Cousin', '0555212647', 'Post-COVID-19 recovery and rehabilitation', 'Osteoarthritis affecting both knees. Previous right knee replacement.', 'No known allergies', 'Insulin Glargine 20 units at bedtime, Metoprolol 50mg twice daily', NULL, 'stable', 'adequate', 'adequate', 'bedridden', 1, 'Diabetic foot ulcer located on sacrum, measuring approximately 5cm x 6cm. Dry with eschar formation.', 0, '{\"spo2\": 97, \"pulse\": 118, \"weight\": 112.9, \"temperature\": 37.5, \"blood_pressure\": \"145/78\", \"respiratory_rate\": 14}', 'Patient appears comfortable at rest but reports moderate pain with movement. Vital signs are within acceptable limits. Wound shows signs of healing with no signs of infection. Patient demonstrates good understanding of medication regimen. Family members are supportive and engaged in care. Recommend continued monitoring of vital signs and pain management. Will reassess wound care needs at next visit.', 'completed', '2025-08-26 19:43:36', '2025-08-24 19:43:36', '2025-09-16 19:43:36', NULL),
(13, 23, 54, 'House No. 60, RP Avenue, Nungua, Cape Coast, Ghana', 'Teacher', 'Christian', 'Mary Yeboah', 'Cousin', '0554355458', NULL, NULL, NULL, 'Post-operative care following hip replacement surgery', NULL, 'Latex - causes skin irritation', 'Amlodipine 5mg daily, Losartan 50mg daily, Hydrochlorothiazide 25mg daily', NULL, 'stable', 'adequate', 'adequate', 'independent', 0, NULL, 7, '{\"spo2\": 88, \"pulse\": 62, \"weight\": 75.6, \"temperature\": 37.2, \"blood_pressure\": \"178/79\", \"respiratory_rate\": 12}', 'Patient appears comfortable at rest but reports moderate pain with movement. Vital signs are within acceptable limits. Wound shows signs of healing with no signs of infection. Patient demonstrates good understanding of medication regimen. Family members are supportive and engaged in care. Recommend continued monitoring of vital signs and pain management. Will reassess wound care needs at next visit.', 'completed', '2025-09-07 19:43:36', '2025-08-24 19:43:36', '2025-09-18 19:43:36', NULL),
(14, 33, 79, 'House No. 151, EA Close, Dzorwulu, Ho, Ghana', 'Civil Servant', 'Hindu', 'Osei Opoku', 'Grandchild', '0569589779', 'Kojo Frimpong', NULL, NULL, 'Chronic kidney disease monitoring', 'Previous hip fracture following a fall. Now healed.', 'Aspirin - causes stomach upset', 'Levothyroxine 100mcg daily, Calcium with Vitamin D twice daily', 'Feeding tube for nutrition - G-tube feedings every 4 hours', 'unstable', 'adequate', 'malnourished', 'assisted', 0, NULL, 10, '{\"spo2\": 91, \"pulse\": 106, \"weight\": 80.4, \"temperature\": 35.7, \"blood_pressure\": \"143/60\", \"respiratory_rate\": 28}', 'Pain management appears inadequate with current regimen. Patient reports pain level 8/10 most of the day. Interfering with sleep and activities. Recommend pain management consultation and possible adjustment of analgesics. Patient appears anxious about pain and need for reassurance and support.', 'reviewed', '2025-09-30 19:43:36', '2025-09-03 19:43:36', '2025-10-02 19:43:36', NULL),
(15, 21, 56, 'House No. 580, VE Close, Dansoman, Tamale, Ghana', 'Trader', 'Muslim (Islam)', 'Daniel Gyasi', 'Brother', '0275720566', 'Grace Addo', NULL, '0551982538', 'Post-surgical wound care and monitoring', NULL, 'Codeine - causes severe nausea', 'Omeprazole 20mg daily, Multivitamin daily', 'Visually impaired - requires large print materials', 'stable', 'adequate', 'adequate', 'independent', 0, NULL, 4, '{\"spo2\": 93, \"pulse\": 97, \"weight\": 104, \"temperature\": 38.4, \"blood_pressure\": \"95/96\", \"respiratory_rate\": 22}', 'Patient appears withdrawn and reports feeling depressed. Vital signs stable. Family reports patient is sleeping more during the day and less engaged in activities. Recommend evaluation for depression and possible medication adjustment. Social support appears adequate but patient may benefit from counseling services.', 'reviewed', '2025-09-06 19:43:36', '2025-08-17 19:43:36', '2025-09-21 19:43:36', NULL),
(16, 30, 56, 'House No. 461, RJ Road, Nhyiaeso, Ho, Ghana', 'Retired', 'Traditional African Religion', 'Esi Sarpong', 'Spouse', '0242920247', 'Osei Bonsu', 'Sister', NULL, 'Chronic kidney disease monitoring', NULL, 'Eggs, milk products - causes digestive issues', 'None', NULL, 'stable', 'adequate', 'adequate', 'assisted', 0, NULL, 7, '{\"spo2\": 98, \"pulse\": 82, \"weight\": 49.2, \"temperature\": 36.7, \"blood_pressure\": \"155/74\", \"respiratory_rate\": 12}', 'Pain management appears inadequate with current regimen. Patient reports pain level 8/10 most of the day. Interfering with sleep and activities. Recommend pain management consultation and possible adjustment of analgesics. Patient appears anxious about pain and need for reassurance and support.', 'completed', '2025-08-22 19:43:36', '2025-10-04 19:43:36', '2025-09-24 19:43:36', NULL),
(17, 38, 56, 'House No. 221, AP Avenue, Sakaman, Koforidua, Ghana', 'Trader', 'Christian', 'Ama Yeboah', 'Sister', '0264412450', 'Abena Acheampong', 'Grandchild', '0273450302', 'Post-chemotherapy care and support', 'History of breast cancer, underwent mastectomy 3 years ago. Currently in remission.', 'Sulfa drugs - causes hives', 'Omeprazole 20mg daily, Multivitamin daily', 'Diabetic diet required, blood glucose monitoring four times daily', 'stable', 'adequate', 'malnourished', 'bedridden', 0, NULL, 6, '{\"spo2\": 94, \"pulse\": 57, \"weight\": 69.4, \"temperature\": 37.4, \"blood_pressure\": \"126/60\", \"respiratory_rate\": 12}', 'New patient assessment completed. Patient demonstrates good overall health status despite chronic conditions. Home environment is clean and safe with good family support. All necessary medical equipment is in place and functioning properly. Patient and family educated on care plan and emergency procedures. Follow-up visit scheduled for one week.', 'completed', '2025-10-09 19:43:36', '2025-09-28 19:43:36', '2025-10-08 19:43:36', NULL),
(18, 49, 58, 'House No. 684, MS Crescent, Kanda, Sunyani, Ghana', 'Nurse', 'Traditional African Religion', 'Samuel Acheampong', 'Caregiver', '0244937198', 'Kwame Agyei', 'Cousin', '0267736243', 'Multiple sclerosis symptom management', NULL, 'Codeine - causes severe nausea', 'Tramadol 50mg three times daily, Gabapentin 300mg three times daily', 'Wheelchair bound, requires assistance with all activities of daily living', 'stable', 'adequate', 'malnourished', 'independent', 0, NULL, 3, '{\"spo2\": 94, \"pulse\": 90, \"weight\": 47.1, \"temperature\": 36.6, \"blood_pressure\": \"138/74\", \"respiratory_rate\": 16}', 'New patient assessment completed. Patient demonstrates good overall health status despite chronic conditions. Home environment is clean and safe with good family support. All necessary medical equipment is in place and functioning properly. Patient and family educated on care plan and emergency procedures. Follow-up visit scheduled for one week.', 'completed', '2025-08-18 19:43:36', '2025-08-21 19:43:36', '2025-09-19 19:43:36', NULL),
(19, 31, 64, 'House No. 482, FD Crescent, Cantonments, Tema, Ghana', 'Civil Servant', 'Christian', 'Kofi Frimpong', 'Caregiver', '0557150260', NULL, 'Cousin', '0272338211', 'Terminal illness end-of-life care', 'Type 2 Diabetes Mellitus for 15 years. History of diabetic neuropathy.', 'No known drug allergies', 'Levothyroxine 100mcg daily, Calcium with Vitamin D twice daily', 'Requires pressure-relieving mattress and frequent repositioning', 'stable', 'adequate', 'adequate', 'bedridden', 0, NULL, 9, '{\"spo2\": 90, \"pulse\": 79, \"weight\": 94.2, \"temperature\": 38.7, \"blood_pressure\": \"123/105\", \"respiratory_rate\": 17}', 'New patient assessment completed. Patient demonstrates good overall health status despite chronic conditions. Home environment is clean and safe with good family support. All necessary medical equipment is in place and functioning properly. Patient and family educated on care plan and emergency procedures. Follow-up visit scheduled for one week.', 'completed', '2025-08-30 19:43:36', '2025-09-05 19:43:36', '2025-09-17 19:43:36', NULL),
(20, 38, 67, 'House No. 890, PF Crescent, Tema Community, Accra, Ghana', 'Trader', 'Other Religion', 'Michael Osei', 'Spouse', '0590992160', NULL, NULL, '0598311754', 'Stroke recovery and rehabilitation', NULL, 'Penicillin - causes severe rash and difficulty breathing', 'Insulin Glargine 20 units at bedtime, Metoprolol 50mg twice daily', 'Requires pressure-relieving mattress and frequent repositioning', 'unstable', 'adequate', 'malnourished', 'bedridden', 0, NULL, 6, '{\"spo2\": 92, \"pulse\": 58, \"weight\": 110.4, \"temperature\": 38.3, \"blood_pressure\": \"144/103\", \"respiratory_rate\": 25}', 'Patient reports increased shortness of breath with minimal exertion. Oxygen saturation adequate on current oxygen flow rate. Bilateral lower extremity edema noted. Weight increased by 3 kg since last visit. Recommend follow-up with physician regarding possible fluid overload. Patient needs reinforcement of low sodium diet instructions.', 'reviewed', '2025-08-29 19:43:36', '2025-08-27 19:43:36', '2025-09-28 19:43:36', NULL),
(21, 5, 56, 'House No. 91, UJ Road, Teshie, Takoradi, Ghana', 'Teacher', 'Christian', 'Mensah Amoah', 'Niece', '0262411183', NULL, 'Caregiver', '0554779271', 'Advanced stage cancer palliative care', NULL, 'Iodine contrast dye - causes allergic reaction', 'Omeprazole 20mg daily, Multivitamin daily', NULL, 'unstable', 'adequate', 'malnourished', 'independent', 0, NULL, 1, '{\"spo2\": 92, \"pulse\": 95, \"weight\": 98.7, \"temperature\": 38.3, \"blood_pressure\": \"135/74\", \"respiratory_rate\": 24}', 'New patient assessment completed. Patient demonstrates good overall health status despite chronic conditions. Home environment is clean and safe with good family support. All necessary medical equipment is in place and functioning properly. Patient and family educated on care plan and emergency procedures. Follow-up visit scheduled for one week.', 'completed', '2025-10-05 19:43:36', '2025-10-11 19:43:36', '2025-10-14 19:43:36', NULL),
(22, 78, 65, 'House No. 212, GE Close, East Legon, Kumasi, Ghana', 'Driver', 'Buddhist', 'Patience Sarpong', 'Spouse', '0568762273', 'Comfort Appiah', 'Brother', '0566289811', 'Arthritis pain management and mobility support', 'Chronic asthma since childhood. No recent hospitalizations.', 'Codeine - causes severe nausea', 'Omeprazole 20mg daily, Multivitamin daily', 'Uses walker for ambulation, needs help with bathing', 'stable', 'adequate', 'adequate', 'independent', 0, NULL, 2, '{\"spo2\": 100, \"pulse\": 83, \"weight\": 118.6, \"temperature\": 36.3, \"blood_pressure\": \"140/79\", \"respiratory_rate\": 12}', 'Pain management appears inadequate with current regimen. Patient reports pain level 8/10 most of the day. Interfering with sleep and activities. Recommend pain management consultation and possible adjustment of analgesics. Patient appears anxious about pain and need for reassurance and support.', 'reviewed', '2025-10-09 19:43:36', '2025-08-28 19:43:36', '2025-09-23 19:43:36', NULL),
(23, 38, 61, 'House No. 904, OZ Road, Tema Community, Tamale, Ghana', 'Accountant', 'Hindu', 'Yaw Opoku', 'Spouse', '0565526903', NULL, 'Sister', '0593141974', 'Post-hospitalization recovery from pneumonia', 'History of tuberculosis, completed treatment 10 years ago.', 'No known drug allergies', 'Levothyroxine 100mcg daily, Calcium with Vitamin D twice daily', 'Requires oxygen therapy 2L per minute via nasal cannula', 'unstable', 'adequate', 'malnourished', 'assisted', 1, 'Venous stasis ulcer located on left buttock, measuring approximately 1.5cm x 2cm. Showing signs of healing, minimal drainage.', 1, '{\"spo2\": 92, \"pulse\": 104, \"weight\": 79.5, \"temperature\": 36.8, \"blood_pressure\": \"159/107\", \"respiratory_rate\": 26}', 'Patient appears withdrawn and reports feeling depressed. Vital signs stable. Family reports patient is sleeping more during the day and less engaged in activities. Recommend evaluation for depression and possible medication adjustment. Social support appears adequate but patient may benefit from counseling services.', 'reviewed', '2025-10-02 19:43:36', '2025-10-09 19:43:36', '2025-09-20 19:43:36', NULL),
(24, 18, 65, 'House No. 692, LF Close, Spintex, Ho, Ghana', 'Accountant', 'Buddhist', 'Patience Ntim', 'Friend', '0500649481', NULL, NULL, NULL, 'Post-chemotherapy care and support', 'COPD diagnosed 5 years ago. Former smoker.', 'Iodine contrast dye - causes allergic reaction', 'Warfarin 5mg daily, Digoxin 0.25mg daily', NULL, 'stable', 'dehydrated', 'adequate', 'bedridden', 0, NULL, 9, '{\"spo2\": 96, \"pulse\": 91, \"weight\": 100, \"temperature\": 37.9, \"blood_pressure\": \"126/93\", \"respiratory_rate\": 24}', 'New patient assessment completed. Patient demonstrates good overall health status despite chronic conditions. Home environment is clean and safe with good family support. All necessary medical equipment is in place and functioning properly. Patient and family educated on care plan and emergency procedures. Follow-up visit scheduled for one week.', 'reviewed', '2025-08-20 19:43:36', '2025-09-03 19:43:36', '2025-09-27 19:43:36', NULL),
(25, 30, 53, 'House No. 10, YC Crescent, Tema Community, Takoradi, Ghana', 'Farmer', 'Hindu', 'Osei Ansah', 'Father', '0570436243', 'Comfort Mensah', 'Sister', NULL, 'Post-chemotherapy care and support', 'COPD diagnosed 5 years ago. Former smoker.', 'Latex - causes skin irritation', 'Amlodipine 5mg daily, Losartan 50mg daily, Hydrochlorothiazide 25mg daily', 'Feeding tube for nutrition - G-tube feedings every 4 hours', 'stable', 'adequate', 'adequate', 'independent', 1, 'Surgical wound dehiscence located on right heel, measuring approximately 2.5cm x 3.5cm. Clean, granulating well with no signs of infection.', 9, '{\"spo2\": 93, \"pulse\": 70, \"weight\": 80.1, \"temperature\": 37.6, \"blood_pressure\": \"126/69\", \"respiratory_rate\": 21}', 'Patient experiencing increased confusion today compared to previous visits. Family reports this is a change from baseline. Vital signs show low-grade fever of 37.8°C. Possible urinary tract infection suspected. Recommend physician evaluation and possible urinalysis. Family educated on signs of delirium and when to seek emergency care.', 'reviewed', '2025-10-07 19:43:36', '2025-09-19 19:43:36', '2025-10-02 19:43:36', NULL),
(26, 21, 65, 'House No. 749, JC Close, Nungua, Koforidua, Ghana', 'Trader', 'Hindu', 'Comfort Acheampong', 'Brother', '0593030063', 'Nana Owusu', 'Sister', NULL, 'Chronic diabetes management with wound care', 'History of depression and anxiety. On antidepressant medication.', 'Latex - causes skin irritation', 'Amlodipine 5mg daily, Losartan 50mg daily, Hydrochlorothiazide 25mg daily', NULL, 'stable', 'adequate', 'adequate', 'independent', 0, NULL, 0, '{\"spo2\": 97, \"pulse\": 70, \"weight\": 51.7, \"temperature\": 35.9, \"blood_pressure\": \"105/107\", \"respiratory_rate\": 22}', 'Patient recovering well from recent hospitalization. Ambulating with walker independently. Appetite improved and patient is maintaining adequate hydration. Medication reconciliation completed with patient and family. No current concerns identified. Patient demonstrates good understanding of when to contact healthcare provider.', 'completed', '2025-10-16 19:43:36', '2025-09-30 19:43:36', '2025-10-06 19:43:36', NULL),
(27, 43, 58, 'House No. 679, BR Avenue, Roman Hill, Tema, Ghana', 'Retired', 'Muslim (Islam)', 'Daniel Mensah', 'Father', '0595810309', 'Mensah Adjei', 'Caregiver', '0565529764', 'Advanced stage cancer palliative care', 'Type 2 Diabetes Mellitus for 15 years. History of diabetic neuropathy.', 'No known drug allergies', 'Currently taking no medications', NULL, 'stable', 'dehydrated', 'adequate', 'independent', 0, NULL, 0, '{\"spo2\": 100, \"pulse\": 89, \"weight\": 97, \"temperature\": 38, \"blood_pressure\": \"112/96\", \"respiratory_rate\": 22}', 'Patient reports increased shortness of breath with minimal exertion. Oxygen saturation adequate on current oxygen flow rate. Bilateral lower extremity edema noted. Weight increased by 3 kg since last visit. Recommend follow-up with physician regarding possible fluid overload. Patient needs reinforcement of low sodium diet instructions.', 'completed', '2025-09-09 19:43:36', '2025-09-24 19:43:36', '2025-10-11 19:43:36', NULL),
(28, 16, 67, 'House No. 560, WW Crescent, Dansoman, Kumasi, Ghana', 'Student', 'Traditional African Religion', 'Ama Agyei', 'Mother', '0249015631', 'Yaw Amoah', 'Friend', '0599313844', 'Post-operative care following hip replacement surgery', 'History of depression and anxiety. On antidepressant medication.', 'Eggs, milk products - causes digestive issues', 'Levothyroxine 100mcg daily, Calcium with Vitamin D twice daily', 'Hearing impaired - uses hearing aids', 'unstable', 'adequate', 'adequate', 'bedridden', 0, NULL, 7, '{\"spo2\": 96, \"pulse\": 102, \"weight\": 66.6, \"temperature\": 36, \"blood_pressure\": \"152/77\", \"respiratory_rate\": 27}', 'Patient appears withdrawn and reports feeling depressed. Vital signs stable. Family reports patient is sleeping more during the day and less engaged in activities. Recommend evaluation for depression and possible medication adjustment. Social support appears adequate but patient may benefit from counseling services.', 'reviewed', '2025-08-29 19:43:36', '2025-09-29 19:43:36', '2025-10-02 19:43:36', NULL),
(29, 26, 64, 'House No. 705, JB Street, Adenta, Accra, Ghana', 'Driver', 'Other Religion', 'David Kusi', 'Brother', '0265857839', 'Kwabena Bonsu', NULL, NULL, 'Spinal cord injury rehabilitation', 'Chronic renal failure on dialysis three times weekly.', 'Eggs, milk products - causes digestive issues', 'Omeprazole 20mg daily, Multivitamin daily', NULL, 'unstable', 'adequate', 'adequate', 'independent', 0, NULL, 5, '{\"spo2\": 90, \"pulse\": 56, \"weight\": 70.3, \"temperature\": 39, \"blood_pressure\": \"173/82\", \"respiratory_rate\": 16}', 'Patient appears comfortable at rest but reports moderate pain with movement. Vital signs are within acceptable limits. Wound shows signs of healing with no signs of infection. Patient demonstrates good understanding of medication regimen. Family members are supportive and engaged in care. Recommend continued monitoring of vital signs and pain management. Will reassess wound care needs at next visit.', 'completed', '2025-10-09 19:43:36', '2025-08-23 19:43:36', '2025-10-04 19:43:36', NULL),
(30, 25, 57, 'House No. 604, NN Crescent, Adenta, Kumasi, Ghana', 'Nurse', 'Traditional African Religion', 'Joseph Kusi', 'Cousin', '0558495998', 'Afia Gyasi', NULL, '0503132918', 'Chronic diabetes management with wound care', NULL, 'No known allergies', 'None', 'Uses walker for ambulation, needs help with bathing', 'stable', 'adequate', 'adequate', 'assisted', 1, 'Stage II pressure injury located on left buttock, measuring approximately 4cm x 5cm. Showing signs of healing, minimal drainage.', 3, '{\"spo2\": 93, \"pulse\": 93, \"weight\": 68.1, \"temperature\": 38.2, \"blood_pressure\": \"142/89\", \"respiratory_rate\": 13}', 'New patient assessment completed. Patient demonstrates good overall health status despite chronic conditions. Home environment is clean and safe with good family support. All necessary medical equipment is in place and functioning properly. Patient and family educated on care plan and emergency procedures. Follow-up visit scheduled for one week.', 'reviewed', '2025-09-19 19:43:36', '2025-10-03 19:43:36', '2025-10-09 19:43:36', NULL),
(31, 8, 4, 'House No. 100, DC Street, Madina, Cape Coast, Ghana', 'Teacher', 'Christian', 'Kwasi Amoah', 'Sister', '0543401299', 'David Ansah', 'Caregiver', NULL, 'Arthritis pain management and mobility support', NULL, 'No known allergies', 'Currently taking no medications', 'Foley catheter in place, requires catheter care', 'unstable', 'adequate', 'adequate', 'bedridden', 0, NULL, 6, '{\"spo2\": 89, \"pulse\": 110, \"weight\": 79.3, \"temperature\": 35.6, \"blood_pressure\": \"172/83\", \"respiratory_rate\": 12}', 'New patient assessment completed. Patient demonstrates good overall health status despite chronic conditions. Home environment is clean and safe with good family support. All necessary medical equipment is in place and functioning properly. Patient and family educated on care plan and emergency procedures. Follow-up visit scheduled for one week.', 'completed', '2025-08-23 19:43:36', '2025-08-23 19:43:36', '2025-10-12 19:43:36', NULL),
(32, 45, 63, 'House No. 505, DK Avenue, Kwashieman, Koforidua, Ghana', 'Trader', 'Other Religion', 'Patience Mensah', 'Neighbor', '0570047118', 'Appiah Amoah', 'Cousin', '0554443584', 'Pressure ulcer treatment and prevention', NULL, 'Eggs, milk products - causes digestive issues', 'Amlodipine 5mg daily, Losartan 50mg daily, Hydrochlorothiazide 25mg daily', 'Colostomy care required, bag changes as needed', 'unstable', 'adequate', 'adequate', 'independent', 1, 'Stage II pressure injury located on left heel, measuring approximately 2.5cm x 3.5cm. Some slough present, moderate drainage.', 0, '{\"spo2\": 88, \"pulse\": 82, \"weight\": 48.4, \"temperature\": 35.9, \"blood_pressure\": \"132/107\", \"respiratory_rate\": 24}', 'Patient appears withdrawn and reports feeling depressed. Vital signs stable. Family reports patient is sleeping more during the day and less engaged in activities. Recommend evaluation for depression and possible medication adjustment. Social support appears adequate but patient may benefit from counseling services.', 'reviewed', '2025-09-11 19:43:36', '2025-09-16 19:43:36', '2025-10-15 19:43:36', NULL),
(33, 21, 64, 'House No. 380, CQ Road, Teshie, Kumasi, Ghana', 'Driver', 'Traditional African Religion', 'Akosua Opoku', 'Friend', '0560269771', NULL, 'Spouse', '0264436721', 'Multiple sclerosis symptom management', 'History of depression and anxiety. On antidepressant medication.', 'Sulfa drugs - causes hives', 'Omeprazole 20mg daily, Multivitamin daily', 'Uses walker for ambulation, needs help with bathing', 'stable', 'adequate', 'malnourished', 'bedridden', 0, NULL, 4, '{\"spo2\": 97, \"pulse\": 76, \"weight\": 115.9, \"temperature\": 37.7, \"blood_pressure\": \"104/89\", \"respiratory_rate\": 23}', 'New patient assessment completed. Patient demonstrates good overall health status despite chronic conditions. Home environment is clean and safe with good family support. All necessary medical equipment is in place and functioning properly. Patient and family educated on care plan and emergency procedures. Follow-up visit scheduled for one week.', 'completed', '2025-09-01 19:43:36', '2025-09-08 19:43:36', '2025-10-13 19:43:36', NULL),
(34, 16, 56, 'House No. 350, TF Avenue, Adenta, Obuasi, Ghana', 'Driver', 'Buddhist', 'Esi Acheampong', 'Sister', '0209168991', 'Mary Acheampong', NULL, NULL, 'Post-COVID-19 recovery and rehabilitation', 'History of depression and anxiety. On antidepressant medication.', 'No known drug allergies', 'Amlodipine 5mg daily, Losartan 50mg daily, Hydrochlorothiazide 25mg daily', 'Colostomy care required, bag changes as needed', 'unstable', 'adequate', 'malnourished', 'bedridden', 0, NULL, 9, '{\"spo2\": 90, \"pulse\": 84, \"weight\": 97.9, \"temperature\": 35.9, \"blood_pressure\": \"136/93\", \"respiratory_rate\": 13}', 'Patient demonstrates significant improvement in mobility compared to last assessment. Vital signs stable and within normal limits. Patient and family demonstrate good understanding of rehabilitation exercises. Continue current care plan with focus on increasing independence in activities of daily living. Patient motivated and engaged in recovery process.', 'reviewed', '2025-10-02 19:43:36', '2025-09-24 19:43:36', '2025-09-30 19:43:36', NULL),
(35, 23, 67, 'House No. 638, AE Crescent, Nungua, Obuasi, Ghana', 'Lawyer', 'Traditional African Religion', 'Kwasi Ntim', 'Friend', '0599731730', NULL, 'Father', '0542530692', 'Alzheimer\'s disease care and monitoring', 'History of deep vein thrombosis. On anticoagulation therapy.', 'No known drug allergies', 'Omeprazole 20mg daily, Multivitamin daily', 'Low sodium diet for heart failure management', 'stable', 'adequate', 'adequate', 'assisted', 0, NULL, 3, '{\"spo2\": 89, \"pulse\": 105, \"weight\": 107.4, \"temperature\": 35.9, \"blood_pressure\": \"104/107\", \"respiratory_rate\": 17}', 'Patient demonstrates significant improvement in mobility compared to last assessment. Vital signs stable and within normal limits. Patient and family demonstrate good understanding of rehabilitation exercises. Continue current care plan with focus on increasing independence in activities of daily living. Patient motivated and engaged in recovery process.', 'completed', '2025-08-30 19:43:36', '2025-09-14 19:43:36', '2025-10-15 19:43:36', NULL),
(36, 49, 64, 'House No. 459, EM Street, Labone, Kumasi, Ghana', 'Banker', 'Muslim (Islam)', 'Kojo Mensah', 'Caregiver', '0243560220', 'Emmanuel Boateng', 'Daughter', NULL, 'Alzheimer\'s disease care and monitoring', NULL, 'Codeine - causes severe nausea', 'Currently taking no medications', 'Foley catheter in place, requires catheter care', 'unstable', 'dehydrated', 'adequate', 'bedridden', 0, NULL, 4, '{\"spo2\": 90, \"pulse\": 84, \"weight\": 89.3, \"temperature\": 35.6, \"blood_pressure\": \"145/94\", \"respiratory_rate\": 19}', 'New patient assessment completed. Patient demonstrates good overall health status despite chronic conditions. Home environment is clean and safe with good family support. All necessary medical equipment is in place and functioning properly. Patient and family educated on care plan and emergency procedures. Follow-up visit scheduled for one week.', 'completed', '2025-09-27 19:43:36', '2025-08-31 19:43:36', '2025-09-26 19:43:36', NULL),
(37, 47, 57, 'House No. 847, RN Crescent, Asokwa, Tema, Ghana', 'Nurse', 'Other Religion', 'Kofi Addo', 'Nephew', '0279585623', 'Afia Asante', 'Spouse', '0590657103', 'Spinal cord injury rehabilitation', 'Osteoarthritis affecting both knees. Previous right knee replacement.', 'Iodine contrast dye - causes allergic reaction', 'Currently taking no medications', NULL, 'stable', 'adequate', 'adequate', 'assisted', 0, NULL, 1, '{\"spo2\": 96, \"pulse\": 60, \"weight\": 64.5, \"temperature\": 36.4, \"blood_pressure\": \"102/70\", \"respiratory_rate\": 22}', 'Patient appears withdrawn and reports feeling depressed. Vital signs stable. Family reports patient is sleeping more during the day and less engaged in activities. Recommend evaluation for depression and possible medication adjustment. Social support appears adequate but patient may benefit from counseling services.', 'reviewed', '2025-08-25 19:43:36', '2025-09-28 19:43:36', '2025-10-11 19:43:36', NULL),
(38, 52, 79, 'House No. 204, PC Avenue, Bantama, Tema, Ghana', 'Student', 'Other Religion', 'Kwasi Osei', 'Neighbor', '0563480867', 'Abena Bonsu', 'Mother', '0502399425', 'Congestive heart failure management', 'COPD diagnosed 5 years ago. Former smoker.', 'Peanuts, shellfish - causes anaphylaxis', 'Warfarin 5mg daily, Digoxin 0.25mg daily', NULL, 'stable', 'adequate', 'adequate', 'independent', 0, NULL, 4, '{\"spo2\": 93, \"pulse\": 87, \"weight\": 98.8, \"temperature\": 36.2, \"blood_pressure\": \"158/85\", \"respiratory_rate\": 16}', 'Patient is alert and oriented but appears fatigued. Reports difficulty sleeping due to pain. Blood pressure is slightly elevated today. Patient expresses concerns about managing care at home. Recommend discussion with physician regarding pain management optimization. Family education provided regarding medication administration and signs of complications to watch for.', 'reviewed', '2025-08-18 19:43:36', '2025-08-27 19:43:36', '2025-10-07 19:43:36', NULL),
(39, 31, 59, 'House No. 275, YM Avenue, Adenta, Obuasi, Ghana', 'Teacher', 'Other Religion', 'Comfort Owusu', 'Father', '0599511531', 'Yaw Asante', 'Mother', '0577874640', 'Pressure ulcer treatment and prevention', 'No significant past medical history. Generally healthy.', 'Sulfa drugs - causes hives', 'None', 'Feeding tube for nutrition - G-tube feedings every 4 hours', 'unstable', 'dehydrated', 'adequate', 'assisted', 0, NULL, 5, '{\"spo2\": 89, \"pulse\": 58, \"weight\": 83, \"temperature\": 37.8, \"blood_pressure\": \"114/98\", \"respiratory_rate\": 28}', 'Patient appears withdrawn and reports feeling depressed. Vital signs stable. Family reports patient is sleeping more during the day and less engaged in activities. Recommend evaluation for depression and possible medication adjustment. Social support appears adequate but patient may benefit from counseling services.', 'completed', '2025-08-27 19:43:36', '2025-09-27 19:43:36', '2025-10-14 19:43:36', NULL),
(40, 23, 64, 'House No. 543, WR Crescent, Kanda, Cape Coast, Ghana', 'Student', 'Buddhist', 'Akua Asante', 'Friend', '0509084345', 'Mensah Owusu', NULL, '0271689410', 'Advanced stage cancer palliative care', 'Type 2 Diabetes Mellitus for 15 years. History of diabetic neuropathy.', 'Peanuts, shellfish - causes anaphylaxis', 'Aspirin 81mg daily, Clopidogrel 75mg daily, Furosemide 40mg daily', 'Requires oxygen therapy 2L per minute via nasal cannula', 'unstable', 'adequate', 'adequate', 'assisted', 0, NULL, 7, '{\"spo2\": 90, \"pulse\": 102, \"weight\": 78.7, \"temperature\": 39, \"blood_pressure\": \"119/66\", \"respiratory_rate\": 19}', 'Patient appears comfortable at rest but reports moderate pain with movement. Vital signs are within acceptable limits. Wound shows signs of healing with no signs of infection. Patient demonstrates good understanding of medication regimen. Family members are supportive and engaged in care. Recommend continued monitoring of vital signs and pain management. Will reassess wound care needs at next visit.', 'completed', '2025-08-17 19:43:36', '2025-09-26 19:43:36', '2025-09-27 19:43:36', NULL),
(41, 82, 4, 'House No. 593, CI Street, Teshie, Obuasi, Ghana', 'Nurse', 'Muslim (Islam)', 'Mercy Frimpong', 'Sister', '0574507847', 'Nana Frimpong', 'Grandchild', NULL, 'Advanced stage cancer palliative care', NULL, 'No known allergies', 'None', NULL, 'unstable', 'adequate', 'adequate', 'bedridden', 0, NULL, 8, '{\"spo2\": 88, \"pulse\": 80, \"weight\": 67, \"temperature\": 37, \"blood_pressure\": \"172/71\", \"respiratory_rate\": 17}', 'Wound assessment reveals good granulation tissue with no signs of infection. Patient tolerating dressing changes well. Blood glucose levels have been variable, ranging from 120-280 mg/dL. Recommend diabetes education reinforcement and possible medication adjustment. Patient demonstrates compliance with treatment regimen.', 'completed', '2025-09-29 19:43:36', '2025-10-04 19:43:36', '2025-09-20 19:43:36', NULL),
(42, 18, 59, 'House No. 585, BJ Close, Nhyiaeso, Ho, Ghana', 'Driver', 'Other Religion', 'Kwaku Frimpong', 'Nephew', '0261521606', NULL, 'Neighbor', '0576250556', 'Chronic pain management', 'No significant past medical history. Generally healthy.', 'No known drug allergies', 'Currently taking no medications', 'Requires pressure-relieving mattress and frequent repositioning', 'stable', 'adequate', 'adequate', 'bedridden', 1, 'Surgical wound dehiscence located on right heel, measuring approximately 3cm x 4cm. Clean, granulating well with no signs of infection.', 0, '{\"spo2\": 96, \"pulse\": 97, \"weight\": 76.3, \"temperature\": 36.3, \"blood_pressure\": \"112/95\", \"respiratory_rate\": 16}', 'Patient appears comfortable at rest but reports moderate pain with movement. Vital signs are within acceptable limits. Wound shows signs of healing with no signs of infection. Patient demonstrates good understanding of medication regimen. Family members are supportive and engaged in care. Recommend continued monitoring of vital signs and pain management. Will reassess wound care needs at next visit.', 'completed', '2025-08-30 19:43:36', '2025-09-12 19:43:36', '2025-10-09 19:43:36', NULL),
(43, 17, 65, 'House No. 558, AC Close, Nungua, Tema, Ghana', 'Trader', 'Muslim (Islam)', 'Kojo Amoah', 'Neighbor', '0272906969', 'Ama Mensah', 'Son', '0244920900', 'Stroke recovery and rehabilitation', 'History of breast cancer, underwent mastectomy 3 years ago. Currently in remission.', 'Penicillin - causes severe rash and difficulty breathing', 'Currently taking no medications', NULL, 'unstable', 'dehydrated', 'malnourished', 'assisted', 0, NULL, 0, '{\"spo2\": 100, \"pulse\": 64, \"weight\": 67.9, \"temperature\": 38.1, \"blood_pressure\": \"136/104\", \"respiratory_rate\": 24}', 'Patient reports increased shortness of breath with minimal exertion. Oxygen saturation adequate on current oxygen flow rate. Bilateral lower extremity edema noted. Weight increased by 3 kg since last visit. Recommend follow-up with physician regarding possible fluid overload. Patient needs reinforcement of low sodium diet instructions.', 'reviewed', '2025-09-01 19:43:36', '2025-08-22 19:43:36', '2025-09-18 19:43:36', NULL),
(44, 39, 61, 'House No. 582, HC Close, Kwashieman, Tema, Ghana', 'Doctor', 'Hindu', 'Abena Darko', 'Cousin', '0207448647', NULL, 'Grandchild', '0508567226', 'Chronic kidney disease monitoring', NULL, 'Eggs, milk products - causes digestive issues', 'None', 'Visually impaired - requires large print materials', 'stable', 'adequate', 'adequate', 'independent', 0, NULL, 9, '{\"spo2\": 95, \"pulse\": 118, \"weight\": 47.2, \"temperature\": 35.5, \"blood_pressure\": \"145/68\", \"respiratory_rate\": 23}', 'Patient reports increased shortness of breath with minimal exertion. Oxygen saturation adequate on current oxygen flow rate. Bilateral lower extremity edema noted. Weight increased by 3 kg since last visit. Recommend follow-up with physician regarding possible fluid overload. Patient needs reinforcement of low sodium diet instructions.', 'completed', '2025-09-07 19:43:36', '2025-08-17 19:43:36', '2025-09-27 19:43:36', NULL),
(45, 45, 67, 'House No. 730, HN Crescent, Labone, Takoradi, Ghana', 'Artisan', 'Traditional African Religion', 'Kwame Kusi', 'Grandchild', '0246099097', NULL, 'Brother', NULL, 'Alzheimer\'s disease care and monitoring', 'History of depression and anxiety. On antidepressant medication.', 'Aspirin - causes stomach upset', 'Levothyroxine 100mcg daily, Calcium with Vitamin D twice daily', 'Low sodium diet for heart failure management', 'stable', 'adequate', 'malnourished', 'independent', 0, NULL, 5, '{\"spo2\": 90, \"pulse\": 51, \"weight\": 87, \"temperature\": 35.5, \"blood_pressure\": \"175/83\", \"respiratory_rate\": 22}', 'Patient appears comfortable at rest but reports moderate pain with movement. Vital signs are within acceptable limits. Wound shows signs of healing with no signs of infection. Patient demonstrates good understanding of medication regimen. Family members are supportive and engaged in care. Recommend continued monitoring of vital signs and pain management. Will reassess wound care needs at next visit.', 'reviewed', '2025-10-01 19:43:36', '2025-09-15 19:43:36', '2025-10-07 19:43:36', NULL),
(46, 50, 60, 'House No. 449, YU Crescent, Adenta, Tema, Ghana', 'Lawyer', 'Muslim (Islam)', 'Patience Bonsu', 'Mother', '0500724666', NULL, NULL, '0243832974', 'Post-hospitalization recovery from pneumonia', 'History of breast cancer, underwent mastectomy 3 years ago. Currently in remission.', 'Iodine contrast dye - causes allergic reaction', 'Metformin 500mg twice daily, Lisinopril 10mg daily, Atorvastatin 20mg at bedtime', 'Visually impaired - requires large print materials', 'stable', 'adequate', 'adequate', 'assisted', 0, NULL, 2, '{\"spo2\": 99, \"pulse\": 74, \"weight\": 64.7, \"temperature\": 37.9, \"blood_pressure\": \"101/94\", \"respiratory_rate\": 28}', 'Patient appears withdrawn and reports feeling depressed. Vital signs stable. Family reports patient is sleeping more during the day and less engaged in activities. Recommend evaluation for depression and possible medication adjustment. Social support appears adequate but patient may benefit from counseling services.', 'reviewed', '2025-10-13 19:43:36', '2025-09-27 19:43:36', '2025-09-22 19:43:36', NULL),
(47, 52, 64, 'House No. 735, WK Close, Asokwa, Sunyani, Ghana', 'Student', 'Christian', 'Joseph Gyasi', 'Daughter', '0505312027', 'Yaw Adjei', 'Brother', '0541759638', 'Alzheimer\'s disease care and monitoring', 'Chronic renal failure on dialysis three times weekly.', 'Peanuts, shellfish - causes anaphylaxis', 'Metformin 500mg twice daily, Lisinopril 10mg daily, Atorvastatin 20mg at bedtime', NULL, 'stable', 'dehydrated', 'malnourished', 'assisted', 1, 'Stage III pressure injury located on right lower leg, measuring approximately 3cm x 4cm. Showing signs of healing, minimal drainage.', 5, '{\"spo2\": 99, \"pulse\": 54, \"weight\": 101.4, \"temperature\": 38, \"blood_pressure\": \"92/110\", \"respiratory_rate\": 21}', 'Patient appears comfortable at rest but reports moderate pain with movement. Vital signs are within acceptable limits. Wound shows signs of healing with no signs of infection. Patient demonstrates good understanding of medication regimen. Family members are supportive and engaged in care. Recommend continued monitoring of vital signs and pain management. Will reassess wound care needs at next visit.', 'reviewed', '2025-09-05 19:43:36', '2025-08-24 19:43:36', '2025-10-03 19:43:36', NULL),
(48, 78, 67, 'House No. 457, IK Street, Roman Hill, Ho, Ghana', 'Accountant', 'Christian', 'Mercy Boateng', 'Spouse', '0557186016', 'Kwaku Kusi', NULL, '0544090713', 'Arthritis pain management and mobility support', 'History of tuberculosis, completed treatment 10 years ago.', 'Peanuts, shellfish - causes anaphylaxis', 'Albuterol inhaler as needed, Advair 250/50 twice daily', NULL, 'stable', 'adequate', 'adequate', 'independent', 0, NULL, 3, '{\"spo2\": 95, \"pulse\": 99, \"weight\": 93.7, \"temperature\": 38.7, \"blood_pressure\": \"153/72\", \"respiratory_rate\": 24}', 'Patient appears withdrawn and reports feeling depressed. Vital signs stable. Family reports patient is sleeping more during the day and less engaged in activities. Recommend evaluation for depression and possible medication adjustment. Social support appears adequate but patient may benefit from counseling services.', 'reviewed', '2025-09-19 19:43:36', '2025-09-28 19:43:36', '2025-10-07 19:43:36', NULL),
(49, 16, 57, 'House No. 810, CE Street, Nhyiaeso, Koforidua, Ghana', 'Teacher', 'Traditional African Religion', 'Nana Kusi', 'Brother', '0560728246', 'Osei Addo', 'Nephew', '0591101464', 'Post-COVID-19 recovery and rehabilitation', 'History of depression and anxiety. On antidepressant medication.', 'Aspirin - causes stomach upset', 'Metformin 500mg twice daily, Lisinopril 10mg daily, Atorvastatin 20mg at bedtime', 'No special needs at this time', 'stable', 'dehydrated', 'adequate', 'independent', 1, 'Venous stasis ulcer located on left buttock, measuring approximately 2cm x 3cm. Showing signs of healing, minimal drainage.', 4, '{\"spo2\": 97, \"pulse\": 65, \"weight\": 76.4, \"temperature\": 35.6, \"blood_pressure\": \"91/84\", \"respiratory_rate\": 28}', 'Patient recovering well from recent hospitalization. Ambulating with walker independently. Appetite improved and patient is maintaining adequate hydration. Medication reconciliation completed with patient and family. No current concerns identified. Patient demonstrates good understanding of when to contact healthcare provider.', 'completed', '2025-09-07 19:43:36', '2025-10-06 19:43:36', '2025-10-12 19:43:36', NULL),
(50, 41, 4, 'House No. 267, NX Road, East Legon, Obuasi, Ghana', 'Retired', 'Traditional African Religion', 'Joseph Frimpong', 'Daughter', '0269574417', 'Kwaku Opoku', 'Nephew', '0202639917', 'Chronic diabetes management with wound care', 'Osteoarthritis affecting both knees. Previous right knee replacement.', 'Penicillin - causes severe rash and difficulty breathing', 'None', NULL, 'stable', 'adequate', 'adequate', 'independent', 1, 'Stage III pressure injury located on sacrum, measuring approximately 3cm x 4cm. Dry with eschar formation.', 2, '{\"spo2\": 88, \"pulse\": 104, \"weight\": 74.6, \"temperature\": 38, \"blood_pressure\": \"159/83\", \"respiratory_rate\": 25}', 'Patient appears withdrawn and reports feeling depressed. Vital signs stable. Family reports patient is sleeping more during the day and less engaged in activities. Recommend evaluation for depression and possible medication adjustment. Social support appears adequate but patient may benefit from counseling services.', 'reviewed', '2025-08-30 19:43:36', '2025-09-08 19:43:36', '2025-10-08 19:43:36', NULL),
(51, 26, 60, 'House No. 385, VO Avenue, Dansoman, Cape Coast, Ghana', 'Banker', 'Muslim (Islam)', 'Mercy Owusu', 'Spouse', '0563843289', 'Patience Darko', NULL, '0590608370', 'Post-hospitalization recovery from pneumonia', 'Chronic renal failure on dialysis three times weekly.', 'No known allergies', 'Omeprazole 20mg daily, Multivitamin daily', 'Requires pressure-relieving mattress and frequent repositioning', 'unstable', 'adequate', 'adequate', 'assisted', 0, NULL, 3, '{\"spo2\": 99, \"pulse\": 60, \"weight\": 97.2, \"temperature\": 36.4, \"blood_pressure\": \"161/93\", \"respiratory_rate\": 19}', 'Patient experiencing increased confusion today compared to previous visits. Family reports this is a change from baseline. Vital signs show low-grade fever of 37.8°C. Possible urinary tract infection suspected. Recommend physician evaluation and possible urinalysis. Family educated on signs of delirium and when to seek emergency care.', 'reviewed', '2025-09-25 19:43:36', '2025-08-18 19:43:36', '2025-09-28 19:43:36', NULL),
(52, 16, 4, 'House No. 228, YD Avenue, Madina, Takoradi, Ghana', 'Retired', 'Hindu', 'Kwasi Adjei', 'Father', '0551842113', 'Comfort Kusi', 'Neighbor', NULL, 'Congestive heart failure management', 'History of tuberculosis, completed treatment 10 years ago.', 'Sulfa drugs - causes hives', 'None', NULL, 'unstable', 'adequate', 'adequate', 'assisted', 0, NULL, 0, '{\"spo2\": 96, \"pulse\": 68, \"weight\": 68.8, \"temperature\": 38.4, \"blood_pressure\": \"172/91\", \"respiratory_rate\": 18}', 'Pain management appears inadequate with current regimen. Patient reports pain level 8/10 most of the day. Interfering with sleep and activities. Recommend pain management consultation and possible adjustment of analgesics. Patient appears anxious about pain and need for reassurance and support.', 'completed', '2025-10-12 19:43:36', '2025-09-03 19:43:36', '2025-09-26 19:43:36', NULL),
(53, 27, 62, 'House No. 876, ZO Road, East Legon, Tema, Ghana', 'Artisan', 'Buddhist', 'Mensah Darko', 'Spouse', '0266063874', NULL, 'Mother', '0549996467', 'Post-operative care following hip replacement surgery', 'History of tuberculosis, completed treatment 10 years ago.', 'Aspirin - causes stomach upset', 'Omeprazole 20mg daily, Multivitamin daily', NULL, 'unstable', 'adequate', 'adequate', 'independent', 0, NULL, 2, '{\"spo2\": 99, \"pulse\": 78, \"weight\": 58.8, \"temperature\": 37, \"blood_pressure\": \"167/96\", \"respiratory_rate\": 17}', 'Patient reports increased shortness of breath with minimal exertion. Oxygen saturation adequate on current oxygen flow rate. Bilateral lower extremity edema noted. Weight increased by 3 kg since last visit. Recommend follow-up with physician regarding possible fluid overload. Patient needs reinforcement of low sodium diet instructions.', 'reviewed', '2025-09-10 19:43:36', '2025-09-09 19:43:36', '2025-09-28 19:43:36', NULL);
INSERT INTO `medical_assessments` (`id`, `patient_id`, `nurse_id`, `physical_address`, `occupation`, `religion`, `emergency_contact_1_name`, `emergency_contact_1_relationship`, `emergency_contact_1_phone`, `emergency_contact_2_name`, `emergency_contact_2_relationship`, `emergency_contact_2_phone`, `presenting_condition`, `past_medical_history`, `allergies`, `current_medications`, `special_needs`, `general_condition`, `hydration_status`, `nutrition_status`, `mobility_status`, `has_wounds`, `wound_description`, `pain_level`, `initial_vitals`, `initial_nursing_impression`, `assessment_status`, `completed_at`, `created_at`, `updated_at`, `deleted_at`) VALUES
(54, 8, 55, 'House No. 760, SU Street, Madina, Tema, Ghana', 'Doctor', 'Buddhist', 'Kwaku Darko', 'Spouse', '0245055165', 'Samuel Ofori', 'Sister', NULL, 'Post-operative care following hip replacement surgery', 'Chronic renal failure on dialysis three times weekly.', 'Peanuts, shellfish - causes anaphylaxis', 'Currently taking no medications', 'Uses cane for walking, requires assistance with stairs', 'stable', 'adequate', 'adequate', 'bedridden', 0, NULL, 8, '{\"spo2\": 94, \"pulse\": 112, \"weight\": 91.1, \"temperature\": 38.6, \"blood_pressure\": \"172/89\", \"respiratory_rate\": 17}', 'Patient demonstrates significant improvement in mobility compared to last assessment. Vital signs stable and within normal limits. Patient and family demonstrate good understanding of rehabilitation exercises. Continue current care plan with focus on increasing independence in activities of daily living. Patient motivated and engaged in recovery process.', 'reviewed', '2025-09-03 19:43:36', '2025-10-08 19:43:36', '2025-09-18 19:43:36', NULL),
(55, 36, 65, 'House No. 426, AM Road, Airport Residential, Tamale, Ghana', 'Retired', 'Muslim (Islam)', 'David Opoku', 'Niece', '0557335496', 'Ama Kusi', NULL, '0260645937', 'Dementia care and behavioral management', 'Previous stroke 2 years ago with residual left-sided weakness. Hypertension.', 'Sulfa drugs - causes hives', 'Currently taking no medications', 'Requires pressure-relieving mattress and frequent repositioning', 'unstable', 'dehydrated', 'adequate', 'bedridden', 1, 'Stage III pressure injury located on left buttock, measuring approximately 4cm x 5cm. Pink and healing well, no drainage.', 1, '{\"spo2\": 90, \"pulse\": 82, \"weight\": 68.4, \"temperature\": 37.9, \"blood_pressure\": \"169/83\", \"respiratory_rate\": 14}', 'Patient demonstrates significant improvement in mobility compared to last assessment. Vital signs stable and within normal limits. Patient and family demonstrate good understanding of rehabilitation exercises. Continue current care plan with focus on increasing independence in activities of daily living. Patient motivated and engaged in recovery process.', 'completed', '2025-08-27 19:43:36', '2025-09-16 19:43:36', '2025-10-13 19:43:36', NULL),
(56, 38, 58, 'House No. 412, JY Crescent, Sakaman, Koforidua, Ghana', 'Trader', 'Buddhist', 'Joyce Gyasi', 'Mother', '0245864232', 'Mensah Adjei', NULL, '0568967168', 'Chronic diabetes management with wound care', 'Hypertension for 10 years, controlled with medication. Previous myocardial infarction 5 years ago.', 'Peanuts, shellfish - causes anaphylaxis', 'Insulin Glargine 20 units at bedtime, Metoprolol 50mg twice daily', 'Wheelchair bound, requires assistance with all activities of daily living', 'stable', 'adequate', 'adequate', 'bedridden', 1, 'Post-surgical incision located on left lateral malleolus, measuring approximately 1.5cm x 2cm. Clean, granulating well with no signs of infection.', 6, '{\"spo2\": 90, \"pulse\": 115, \"weight\": 71.2, \"temperature\": 38.3, \"blood_pressure\": \"116/104\", \"respiratory_rate\": 25}', 'New patient assessment completed. Patient demonstrates good overall health status despite chronic conditions. Home environment is clean and safe with good family support. All necessary medical equipment is in place and functioning properly. Patient and family educated on care plan and emergency procedures. Follow-up visit scheduled for one week.', 'reviewed', '2025-09-04 19:43:36', '2025-09-23 19:43:36', '2025-09-26 19:43:36', NULL),
(57, 49, 54, 'House No. 205, KC Close, Dansoman, Ho, Ghana', 'Nurse', 'Other Religion', 'Joseph Frimpong', 'Cousin', '0591302627', 'Kojo Adjei', NULL, NULL, 'Spinal cord injury rehabilitation', 'Chronic renal failure on dialysis three times weekly.', 'Iodine contrast dye - causes allergic reaction', 'Insulin Glargine 20 units at bedtime, Metoprolol 50mg twice daily', NULL, 'unstable', 'adequate', 'adequate', 'assisted', 0, NULL, 4, '{\"spo2\": 88, \"pulse\": 119, \"weight\": 109.8, \"temperature\": 35.5, \"blood_pressure\": \"175/92\", \"respiratory_rate\": 23}', 'Pain management appears inadequate with current regimen. Patient reports pain level 8/10 most of the day. Interfering with sleep and activities. Recommend pain management consultation and possible adjustment of analgesics. Patient appears anxious about pain and need for reassurance and support.', 'reviewed', '2025-09-25 19:43:36', '2025-10-03 19:43:36', '2025-10-15 19:43:36', NULL),
(58, 84, 4, 'Williams Street, Avenue', 'HR', 'Christian', 'Theophilus', 'Husband', '0557447800', 'Gabby Appiah', 'Brother', '0208110620', 'No Conditions', 'No past medical history', 'No allergies', 'Dracula', 'Needs assistance', 'unstable', 'dehydrated', 'malnourished', 'bedridden', 1, 'Heavy sore', 10, '{\"spo2\": 90, \"pulse\": 72, \"weight\": 90, \"temperature\": 34, \"blood_pressure\": \"120/80\", \"respiratory_rate\": 16}', 'Needs a lot of care', 'completed', '2025-10-28 10:28:36', '2025-10-28 10:28:36', '2025-10-28 11:47:59', '2025-10-28 11:47:59'),
(59, 85, 4, 'Williams Avenue, Ga East, Ga, Greater Accra', 'HR', 'Christian', 'Theophilus Brako Boateng', 'Husband', '0557447800', 'Gabby Appiah', 'Brother', '0208110620', 'No Conditions', 'No past record', 'No allergies', 'No current medications', 'Needs assistance with feeding', 'unstable', 'dehydrated', 'adequate', 'assisted', 1, 'Heavy wounds & Sores', 10, '{\"spo2\": 90, \"pulse\": 72, \"weight\": 90, \"temperature\": 34, \"blood_pressure\": \"122/90\", \"respiratory_rate\": 16}', 'Patient needs heavy care', 'completed', '2025-10-28 11:52:13', '2025-10-28 11:52:13', '2025-10-28 11:52:13', NULL),
(60, 90, 4, 'Williams Avenue, Ga East, Ga, Greater Accra', 'Trader', 'Muslim', 'Emmanuel Boateng', 'Father', '0557447801', 'Hanna Boateng', 'Mother', '024377117', 'No conditions', 'No past record', NULL, NULL, NULL, 'stable', 'adequate', 'adequate', 'independent', 0, NULL, 9, '{\"spo2\": 90, \"pulse\": 72, \"weight\": 80, \"temperature\": 34, \"blood_pressure\": \"120/83\", \"respiratory_rate\": 16}', 'Patient needs extra attention', 'completed', '2025-11-02 13:50:50', '2025-11-02 13:50:50', '2025-11-02 13:50:50', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int UNSIGNED NOT NULL,
  `migration` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '0001_01_01_000000_create_users_table', 1),
(2, '0001_01_01_000001_create_cache_table', 1),
(3, '0001_01_01_000002_create_jobs_table', 1),
(4, '2025_09_23_223039_create_personal_access_tokens_table', 1),
(5, '2025_09_23_225321_create_user_sessions_table', 1),
(6, '2025_09_23_225357_create_login_history_table', 1),
(7, '2025_09_23_230508_create_password_resets_table', 1),
(8, '2025_09_23_230603_create_two_factor_codes_table', 1),
(9, '2025_09_24_160359_create_roles_table', 1),
(10, '2025_09_24_160452_create_permissions_table', 1),
(11, '2025_09_24_160559_create_role_permission_table', 1),
(12, '2025_09_24_160656_add_role_id_to_users_table', 1),
(13, '2025_09_25_220940_user_verification_mail', 1),
(14, '2025_09_26_090618_create_care_plans_table', 1),
(15, '2025_09_26_091039_create_care_assignments_table', 1),
(16, '2025_09_26_091810_create_schedules_table', 1),
(17, '2025_09_26_133906_add_admin_overide_to_care_assigments_table', 1),
(18, '2025_09_26_185347_add_fields_to_care_plans_table', 1),
(19, '2025_09_26_230147_create_time_trackings_table', 1),
(20, '2025_09_28_160401_create_progress_notes_table', 1),
(21, '2025_09_28_171052_create_medical_assessments_table', 1),
(22, '2025_09_28_192301_create_drivers_table', 1),
(23, '2025_09_28_192429_create_transport_requests_table', 1),
(24, '2025_09_28_213549_create_patient_feedback_table', 1),
(25, '2025_09_28_213726_create_incident_responses_table', 1),
(26, '2025_09_29_180957_create_notifications_table', 1),
(27, '2025_09_29_213531_create_vehicles_table', 1),
(28, '2025_09_29_213841_create_driver_vehicle_assignments_table', 1),
(29, '2025_09_30_141215_add_fields_to_patient_feedback_table', 1),
(30, '2025_09_30_143216_add_fields_to_incident_reports_table', 1),
(31, '2025_10_07_121829_add_address_to_users_table', 2),
(32, '2025_10_17_090444_add_location_name_to_time_trackings_table', 3),
(35, '2025_10_21_115949_create_mobile_notifications_table', 4),
(36, '2025_10_21_123648_add_fields_to_mobile_notifications_table', 4),
(37, '2025_10_29_145548_add_completed_tasks_to_care_plans_table', 5),
(38, '2025_10_31_162812_add_extra_two_factor_fields_to_users_table', 6),
(39, '2025_10_31_165150_add_extra_three_factor_fields_to_users_table', 7),
(40, '2025_11_03_142012_add_care_date_to_patient_feedback_table', 8),
(44, '2025_11_04_014255_add_patient_notification_columns_to_notification_preferences_table', 9),
(45, '2025_11_04_123525_create_care_requests_table', 10),
(46, '2025_11_04_123735_create_care_payments_table', 10),
(47, '2025_11_04_123817_create_care_fee_structure_table', 10),
(48, '2025_11_05_022519_create_mobile_money_otps_table', 11),
(49, '2025_11_05_120836_create_notification_logs_table', 12),
(50, '2025_11_05_121532_add_fcm_token_to_users_table', 12);

-- --------------------------------------------------------

--
-- Table structure for table `mobile_money_otps`
--

CREATE TABLE `mobile_money_otps` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED NOT NULL,
  `phone_number` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `network` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `otp_code` varchar(6) COLLATE utf8mb4_unicode_ci NOT NULL,
  `verified` tinyint(1) NOT NULL DEFAULT '0',
  `expires_at` timestamp NOT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `attempts` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `mobile_money_otps`
--

INSERT INTO `mobile_money_otps` (`id`, `user_id`, `phone_number`, `network`, `otp_code`, `verified`, `expires_at`, `verified_at`, `attempts`, `created_at`, `updated_at`) VALUES
(1, 5, '0557447800', 'MTN Mobile Money', '301049', 1, '2025-11-05 02:44:34', '2025-11-05 02:39:58', 1, '2025-11-05 02:39:34', '2025-11-05 02:39:58'),
(2, 5, '0557447800', 'MTN Mobile Money', '925754', 1, '2025-11-05 02:56:54', '2025-11-05 02:52:07', 0, '2025-11-05 02:51:54', '2025-11-05 02:52:07'),
(3, 5, '0557447800', 'MTN Mobile Money', '670037', 0, '2025-11-05 02:58:16', NULL, 0, '2025-11-05 02:53:16', '2025-11-05 02:53:16'),
(4, 5, '0557447800', 'MTN Mobile Money', '901760', 1, '2025-11-05 02:59:27', '2025-11-05 02:54:40', 0, '2025-11-05 02:54:27', '2025-11-05 02:54:40');

-- --------------------------------------------------------

--
-- Table structure for table `mobile_notifications`
--

CREATE TABLE `mobile_notifications` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED NOT NULL,
  `email_notifications` tinyint(1) NOT NULL DEFAULT '1',
  `login_alerts` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `mobile_notifications`
--

INSERT INTO `mobile_notifications` (`id`, `user_id`, `email_notifications`, `login_alerts`, `created_at`, `updated_at`) VALUES
(1, 4, 1, 1, '2025-10-21 12:51:48', '2025-10-30 17:48:35'),
(2, 5, 1, 1, '2025-11-04 01:17:23', '2025-11-04 12:02:17');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `notifiable_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `notifiable_id` bigint UNSIGNED NOT NULL,
  `data` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `type`, `notifiable_type`, `notifiable_id`, `data`, `read_at`, `created_at`, `updated_at`) VALUES
('08467c15-a717-4085-b463-e724a1daa1b3', 'App\\Notifications\\ScheduleReminder', 'App\\Models\\User', 4, '{\"schedule_id\":441,\"schedule_date\":\"2025-11-11\",\"start_time\":\"2025-10-15T15:00:00.000000Z\",\"end_time\":\"2025-10-15T23:00:00.000000Z\",\"shift_type\":\"evening_shift\",\"patient_name\":\"Granit Xhaka\",\"location\":\"Patient Home - East Legon\",\"message\":\"Schedule reminder for Nov 11, 2025\"}', NULL, '2025-10-15 20:09:24', '2025-10-15 20:09:24'),
('11987505-95ed-4546-a2a4-5e037508acc1', 'App\\Notifications\\ScheduleReminder', 'App\\Models\\User', 4, '{\"schedule_id\":400,\"schedule_date\":\"2025-10-28\",\"start_time\":\"2025-10-15T09:00:00.000000Z\",\"end_time\":\"2025-10-15T17:00:00.000000Z\",\"shift_type\":\"custom_shift\",\"patient_name\":\"Granit Xhaka\",\"location\":\"Patient Home - Tema\",\"message\":\"Schedule reminder for Oct 28, 2025\"}', NULL, '2025-10-15 21:04:06', '2025-10-15 21:04:06'),
('3601cb95-c491-4878-aff0-07e6cbb4c4b5', 'App\\Notifications\\ScheduleReminder', 'App\\Models\\User', 54, '{\"schedule_id\":529,\"schedule_date\":\"2025-12-13\",\"start_time\":\"2025-10-15T15:00:00.000000Z\",\"end_time\":\"2025-10-15T23:00:00.000000Z\",\"shift_type\":\"evening_shift\",\"patient_name\":\"Samara Bergnaum\",\"location\":\"Patient Home - Cantonments\",\"message\":\"Schedule reminder for Dec 13, 2025\"}', NULL, '2025-10-15 20:28:31', '2025-10-15 20:28:31'),
('4a645f43-2513-464b-b830-dd092d30e74e', 'App\\Notifications\\ScheduleReminder', 'App\\Models\\User', 4, '{\"schedule_id\":490,\"schedule_date\":\"2025-11-29\",\"start_time\":\"2025-10-15T13:00:00.000000Z\",\"end_time\":\"2025-10-15T21:00:00.000000Z\",\"shift_type\":\"afternoon_shift\",\"patient_name\":\"Robert Ben Brown\",\"location\":null,\"message\":\"Schedule reminder for Nov 29, 2025\"}', NULL, '2025-10-15 20:18:52', '2025-10-15 20:18:52'),
('4b3e8144-9c4d-4762-8f19-357206afa81a', 'App\\Notifications\\ScheduleReminder', 'App\\Models\\User', 4, '{\"schedule_id\":435,\"schedule_date\":\"2025-11-08\",\"start_time\":\"2025-10-15T23:00:00.000000Z\",\"end_time\":\"2025-10-15T07:00:00.000000Z\",\"shift_type\":\"night_shift\",\"patient_name\":\"Granit Xhaka\",\"location\":\"Patient Home - Labone\",\"message\":\"Schedule reminder for Nov 08, 2025\"}', NULL, '2025-10-15 19:59:27', '2025-10-15 19:59:27'),
('67043c49-3b37-4f73-97ec-dca4fb26446e', 'App\\Notifications\\ScheduleReminder', 'App\\Models\\User', 63, '{\"schedule_id\":526,\"schedule_date\":\"2025-12-12\",\"start_time\":\"2025-10-15T15:00:00.000000Z\",\"end_time\":\"2025-10-15T23:00:00.000000Z\",\"shift_type\":\"evening_shift\",\"patient_name\":\"Felix Cassin\",\"location\":\"Patient Home - Labone\",\"message\":\"Schedule reminder for Dec 12, 2025\"}', NULL, '2025-10-15 20:41:51', '2025-10-15 20:41:51'),
('6ab776be-dc9a-44c4-a3a9-9f3b3a62310b', 'App\\Notifications\\ScheduleReminder', 'App\\Models\\User', 4, '{\"schedule_id\":400,\"schedule_date\":\"2025-10-28\",\"start_time\":\"2025-10-15T09:00:00.000000Z\",\"end_time\":\"2025-10-15T17:00:00.000000Z\",\"shift_type\":\"custom_shift\",\"patient_name\":\"Granit Xhaka\",\"location\":\"Patient Home - Tema\",\"message\":\"Schedule reminder for Oct 28, 2025\"}', NULL, '2025-10-15 19:55:13', '2025-10-15 19:55:13'),
('6c6a3f80-3003-4b6f-a68e-ac542f7833c4', 'App\\Notifications\\ScheduleReminder', 'App\\Models\\User', 64, '{\"schedule_id\":524,\"schedule_date\":\"2025-12-12\",\"start_time\":\"2025-10-15T15:00:00.000000Z\",\"end_time\":\"2025-10-15T23:00:00.000000Z\",\"shift_type\":\"evening_shift\",\"patient_name\":\"Felix Cassin\",\"location\":null,\"message\":\"Schedule reminder for Dec 12, 2025\"}', NULL, '2025-10-15 20:29:07', '2025-10-15 20:29:07'),
('9a4c64d2-912f-4fd6-9214-6de22aaac234', 'App\\Notifications\\ScheduleReminder', 'App\\Models\\User', 79, '{\"schedule_id\":528,\"schedule_date\":\"2025-12-13\",\"start_time\":\"2025-10-15T13:00:00.000000Z\",\"end_time\":\"2025-10-15T21:00:00.000000Z\",\"shift_type\":\"afternoon_shift\",\"patient_name\":\"Ted Howell\",\"location\":\"Patient Home - Labone Danta\",\"message\":\"Schedule reminder for Dec 13, 2025\"}', NULL, '2025-10-15 20:27:16', '2025-10-15 20:27:16'),
('9d0e72d1-6860-40de-8edb-565ba643e07f', 'App\\Notifications\\ScheduleReminder', 'App\\Models\\User', 4, '{\"schedule_id\":435,\"schedule_date\":\"2025-11-08\",\"start_time\":\"2025-10-15T23:00:00.000000Z\",\"end_time\":\"2025-10-15T07:00:00.000000Z\",\"shift_type\":\"night_shift\",\"patient_name\":\"Granit Xhaka\",\"location\":\"Patient Home - Labone\",\"message\":\"Schedule reminder for Nov 08, 2025\"}', NULL, '2025-10-15 20:55:17', '2025-10-15 20:55:17'),
('b573bc08-4341-4a4a-9bf9-385483a00bf8', 'App\\Notifications\\ScheduleReminder', 'App\\Models\\User', 4, '{\"schedule_id\":490,\"schedule_date\":\"2025-11-29\",\"start_time\":\"2025-10-15T13:00:00.000000Z\",\"end_time\":\"2025-10-15T21:00:00.000000Z\",\"shift_type\":\"afternoon_shift\",\"patient_name\":\"Robert Ben Brown\",\"location\":null,\"message\":\"Schedule reminder for Nov 29, 2025\"}', NULL, '2025-10-15 20:45:45', '2025-10-15 20:45:45'),
('bdedfa8b-0839-4d0c-afbc-6ad06da3d848', 'App\\Notifications\\ScheduleReminder', 'App\\Models\\User', 57, '{\"schedule_id\":516,\"schedule_date\":\"2025-12-10\",\"start_time\":\"2025-10-15T07:00:00.000000Z\",\"end_time\":\"2025-10-15T15:00:00.000000Z\",\"shift_type\":\"morning_shift\",\"patient_name\":\"Katlyn Schneider\",\"location\":null,\"message\":\"Schedule reminder for Dec 10, 2025\"}', NULL, '2025-10-15 21:06:44', '2025-10-15 21:06:44'),
('cc305fb3-4f80-4646-ae02-d61b1767846a', 'App\\Notifications\\ScheduleReminder', 'App\\Models\\User', 4, '{\"schedule_id\":460,\"schedule_date\":\"2025-11-19\",\"start_time\":\"2025-10-15T15:00:00.000000Z\",\"end_time\":\"2025-10-15T23:00:00.000000Z\",\"shift_type\":\"evening_shift\",\"patient_name\":\"Robert Ben Brown\",\"location\":null,\"message\":\"Schedule reminder for Nov 19, 2025\"}', NULL, '2025-10-15 20:22:19', '2025-10-15 20:22:19'),
('ef42ba6f-cd11-45eb-82a6-c6b152f87ecf', 'App\\Notifications\\ScheduleReminder', 'App\\Models\\User', 4, '{\"schedule_id\":490,\"schedule_date\":\"2025-11-29\",\"start_time\":\"2025-10-15T13:00:00.000000Z\",\"end_time\":\"2025-10-15T21:00:00.000000Z\",\"shift_type\":\"afternoon_shift\",\"patient_name\":\"Robert Ben Brown\",\"location\":null,\"message\":\"Schedule reminder for Nov 29, 2025\"}', NULL, '2025-10-15 21:13:04', '2025-10-15 21:13:04'),
('f55570bc-ee80-404d-87df-96f8e40fd61c', 'App\\Notifications\\ScheduleReminder', 'App\\Models\\User', 4, '{\"schedule_id\":460,\"schedule_date\":\"2025-11-19\",\"start_time\":\"2025-10-15T15:00:00.000000Z\",\"end_time\":\"2025-10-15T23:00:00.000000Z\",\"shift_type\":\"evening_shift\",\"patient_name\":\"Robert Ben Brown\",\"location\":null,\"message\":\"Schedule reminder for Nov 19, 2025\"}', NULL, '2025-10-15 20:53:47', '2025-10-15 20:53:47'),
('ff3e29bb-d416-4296-a6e3-d81bc2da0ec9', 'App\\Notifications\\ScheduleReminder', 'App\\Models\\User', 4, '{\"schedule_id\":485,\"schedule_date\":\"2025-11-28\",\"start_time\":\"2025-10-15T09:00:00.000000Z\",\"end_time\":\"2025-10-15T17:00:00.000000Z\",\"shift_type\":\"custom_shift\",\"patient_name\":\"Ted Howell\",\"location\":\"Patient Home - Tema\",\"message\":\"Schedule reminder for Nov 28, 2025\"}', NULL, '2025-10-15 20:29:28', '2025-10-15 20:29:28');

-- --------------------------------------------------------

--
-- Table structure for table `notification_logs`
--

CREATE TABLE `notification_logs` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED NOT NULL,
  `user_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'patient',
  `notification_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `body` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `data` json DEFAULT NULL,
  `notifiable_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notifiable_id` bigint UNSIGNED DEFAULT NULL,
  `sent_via_push` tinyint(1) NOT NULL DEFAULT '0',
  `sent_via_email` tinyint(1) NOT NULL DEFAULT '0',
  `sent_via_sms` tinyint(1) NOT NULL DEFAULT '0',
  `status` enum('pending','sent','delivered','failed','read') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `failure_reason` text COLLATE utf8mb4_unicode_ci,
  `fcm_message_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fcm_response` json DEFAULT NULL,
  `sent_at` timestamp NULL DEFAULT NULL,
  `delivered_at` timestamp NULL DEFAULT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `failed_at` timestamp NULL DEFAULT NULL,
  `priority` enum('low','normal','high','urgent') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'normal',
  `scheduled_for` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notification_preferences`
--

CREATE TABLE `notification_preferences` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED NOT NULL,
  `all_notifications` tinyint(1) NOT NULL DEFAULT '1',
  `new_patient_assignment` tinyint(1) NOT NULL DEFAULT '1',
  `careplan_updates` tinyint(1) NOT NULL DEFAULT '1',
  `patient_vitals_alert` tinyint(1) NOT NULL DEFAULT '1',
  `vitals_tracking` tinyint(1) NOT NULL DEFAULT '1',
  `health_tips` tinyint(1) NOT NULL DEFAULT '1',
  `medication_reminders` tinyint(1) NOT NULL DEFAULT '1',
  `appointment_reminders` tinyint(1) NOT NULL DEFAULT '1',
  `shift_reminders` tinyint(1) NOT NULL DEFAULT '1',
  `shift_changes` tinyint(1) NOT NULL DEFAULT '1',
  `clock_in_reminders` tinyint(1) NOT NULL DEFAULT '1',
  `transport_requests` tinyint(1) NOT NULL DEFAULT '1',
  `incident_reports` tinyint(1) NOT NULL DEFAULT '0',
  `system_updates` tinyint(1) NOT NULL DEFAULT '0',
  `security_alerts` tinyint(1) NOT NULL DEFAULT '1',
  `email_notifications` tinyint(1) NOT NULL DEFAULT '1',
  `sms_notifications` tinyint(1) NOT NULL DEFAULT '0',
  `quiet_hours_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `quiet_hours_start` time DEFAULT '22:00:00',
  `quiet_hours_end` time DEFAULT '07:00:00',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notification_preferences`
--

INSERT INTO `notification_preferences` (`id`, `user_id`, `all_notifications`, `new_patient_assignment`, `careplan_updates`, `patient_vitals_alert`, `vitals_tracking`, `health_tips`, `medication_reminders`, `appointment_reminders`, `shift_reminders`, `shift_changes`, `clock_in_reminders`, `transport_requests`, `incident_reports`, `system_updates`, `security_alerts`, `email_notifications`, `sms_notifications`, `quiet_hours_enabled`, `quiet_hours_start`, `quiet_hours_end`, `created_at`, `updated_at`) VALUES
(1, 4, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, '21:00:00', '05:00:00', '2025-10-21 12:53:03', '2025-10-21 13:03:38'),
(2, 5, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 0, 1, 1, 1, 1, 1, '22:00:00', '07:00:00', '2025-11-04 01:44:46', '2025-11-04 02:02:32');

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` bigint UNSIGNED NOT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL,
  `expires_at` timestamp NOT NULL,
  `used_at` timestamp NULL DEFAULT NULL,
  `used_ip` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_agent` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `patient_feedback`
--

CREATE TABLE `patient_feedback` (
  `id` bigint UNSIGNED NOT NULL,
  `patient_id` bigint UNSIGNED NOT NULL,
  `nurse_id` bigint UNSIGNED NOT NULL,
  `schedule_id` bigint UNSIGNED DEFAULT NULL,
  `rating` int NOT NULL COMMENT '1-5 star rating',
  `feedback_text` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `admin_response` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `response_date` timestamp NULL DEFAULT NULL,
  `responded_by` bigint UNSIGNED DEFAULT NULL,
  `responded_at` timestamp NULL DEFAULT NULL,
  `status` enum('pending','responded') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `response_text` text COLLATE utf8mb4_unicode_ci,
  `would_recommend` tinyint(1) NOT NULL DEFAULT '1',
  `care_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `patient_feedback`
--

INSERT INTO `patient_feedback` (`id`, `patient_id`, `nurse_id`, `schedule_id`, `rating`, `feedback_text`, `admin_response`, `response_date`, `responded_by`, `responded_at`, `status`, `response_text`, `would_recommend`, `care_date`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 5, 53, NULL, 4, 'Alex is a great nurse. I loved his professionalism', NULL, NULL, NULL, NULL, 'pending', NULL, 1, '2025-11-03', '2025-11-03 16:16:08', '2025-11-03 16:36:59', NULL),
(2, 5, 53, 416, 4, 'Great nurse', NULL, NULL, NULL, NULL, 'pending', NULL, 1, '2025-11-03', '2025-11-03 16:35:38', '2025-11-03 16:35:38', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

CREATE TABLE `permissions` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `display_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `category` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `subcategory` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sort_order` int NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `permissions`
--

INSERT INTO `permissions` (`id`, `name`, `display_name`, `description`, `category`, `subcategory`, `sort_order`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'user_management.view', 'View User Management', 'Can view user management section', 'user_management', NULL, 1, 1, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(2, 'user_management.users.view', 'View All Users', 'Can view users list and details', 'user_management', 'users', 1, 1, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(3, 'user_management.users.create', 'Create Users', 'Can create new user accounts', 'user_management', 'users', 2, 1, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(4, 'user_management.users.edit', 'Edit Users', 'Can edit user profiles and information', 'user_management', 'users', 3, 1, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(5, 'user_management.users.delete', 'Delete Users', 'Can delete user accounts', 'user_management', 'users', 4, 1, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(6, 'user_management.users.verify', 'Verify Users', 'Can verify user applications and credentials', 'user_management', 'users', 5, 1, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(7, 'user_management.nurses.view', 'View Nurses', 'Can view nurses list and details', 'user_management', 'nurses', 1, 1, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(8, 'user_management.nurses.create', 'Create Nurses', 'Can create new nurse accounts', 'user_management', 'nurses', 2, 1, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(9, 'user_management.nurses.edit', 'Edit Nurses', 'Can edit nurse profiles and information', 'user_management', 'nurses', 3, 1, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(10, 'user_management.nurses.delete', 'Delete Nurses', 'Can delete nurse accounts', 'user_management', 'nurses', 4, 1, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(11, 'user_management.nurses.verify', 'Verify Nurses', 'Can verify nurse applications and credentials', 'user_management', 'nurses', 5, 1, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(12, 'user_management.patients.view', 'View Patients', 'Can view patients list and details', 'user_management', 'patients', 1, 1, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(13, 'user_management.patients.create', 'Create Patients', 'Can create new patient accounts', 'user_management', 'patients', 2, 1, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(14, 'user_management.patients.edit', 'Edit Patients', 'Can edit patient profiles and information', 'user_management', 'patients', 3, 1, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(15, 'user_management.patients.delete', 'Delete Patients', 'Can delete patient accounts', 'user_management', 'patients', 4, 1, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(16, 'user_management.doctors.view', 'View Doctors', 'Can view doctors list and details', 'user_management', 'doctors', 1, 1, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(17, 'user_management.doctors.create', 'Create Doctors', 'Can create new doctor accounts', 'user_management', 'doctors', 2, 1, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(18, 'user_management.doctors.edit', 'Edit Doctors', 'Can edit doctor profiles and information', 'user_management', 'doctors', 3, 1, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(19, 'user_management.doctors.delete', 'Delete Doctors', 'Can delete doctor accounts', 'user_management', 'doctors', 4, 1, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(20, 'user_management.doctors.verify', 'Verify Doctors', 'Can verify doctor applications and credentials', 'user_management', 'doctors', 5, 1, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(21, 'user_management.pending.verification', 'Verify Pending Users', 'Can verify pending applications', 'user_management', 'users', 5, 1, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(22, 'care_management.view', 'View Care Management', 'Can view care management section', 'care_management', NULL, 1, 1, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(23, 'care_management.care_plans.view', 'View Care Plans', 'Can view patient care plans', 'care_management', 'care_plans', 1, 1, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(24, 'care_management.care_plans.create', 'Create Care Plans', 'Can create new care plans for patients', 'care_management', 'care_plans', 2, 1, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(25, 'care_management.care_plans.edit', 'Edit Care Plans', 'Can modify existing care plans', 'care_management', 'care_plans', 3, 1, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(26, 'care_management.care_plans.delete', 'Delete Care Plans', 'Can delete care plans', 'care_management', 'care_plans', 4, 1, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(27, 'care_management.care_plans.approve', 'Approve Care Plans', 'Can approve care plans', 'care_management', 'care_plans', 5, 1, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(28, 'care_management.schedules.view', 'View Schedules', 'Can view care schedules', 'care_management', 'schedules', 1, 1, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(29, 'care_management.schedules.create', 'Create Schedules', 'Can create care schedules', 'care_management', 'schedules', 2, 1, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(30, 'care_management.schedules.edit', 'Edit Schedules', 'Can modify care schedules', 'care_management', 'schedules', 3, 1, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(31, 'care_management.schedules.delete', 'Delete Schedules', 'Can delete care schedules', 'care_management', 'schedules', 4, 1, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(32, 'care_management.schedules.approve', 'Approve Schedules', 'Can approve care schedules', 'care_management', 'schedules', 4, 1, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(33, 'time_tracking.view', 'View Time Tracking', 'Can view time tracking data', 'time_tracking', NULL, 1, 1, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(34, 'time_tracking.clock_in_out', 'Clock In/Out', 'Can clock in and out for shifts', 'time_tracking', NULL, 2, 1, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(35, 'time_tracking.edit_own', 'Edit Own Time', 'Can edit own time entries', 'time_tracking', NULL, 3, 1, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(36, 'time_tracking.edit_others', 'Edit Others Time', 'Can edit time entries for other users', 'time_tracking', NULL, 4, 1, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(37, 'time_tracking.approve', 'Approve Time', 'Can approve time entries for payroll', 'time_tracking', NULL, 5, 1, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(38, 'time_tracking.reports', 'View Time Reports', 'Can view time tracking reports', 'time_tracking', NULL, 6, 1, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(39, 'daily_progress.view', 'View Daily Progress', 'Can view patient daily progress data', 'daily_progress', NULL, 1, 1, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(40, 'daily_progress.create', 'Create Daily Progress', 'Can create patient daily progress', 'daily_progress', NULL, 1, 1, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(41, 'daily_progress.edit', 'Edit Daily Progress', 'Can edit patient daily progress', 'daily_progress', NULL, 2, 1, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(42, 'daily_progress.delete', 'Delete Daily Progress', 'Can delete patient daily progress', 'daily_progress', NULL, 3, 1, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(43, 'payments_billing.view', 'View Payments & Billing', 'Can view payment and billing information', 'payments_billing', NULL, 1, 1, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(44, 'payments_billing.process_payments', 'Process Payments', 'Can process patient payments', 'payments_billing', NULL, 2, 1, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(45, 'payments_billing.create_invoices', 'Create Invoices', 'Can create and send invoices', 'payments_billing', NULL, 3, 1, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(46, 'payments_billing.manage_pricing', 'Manage Pricing', 'Can manage service pricing and rates', 'payments_billing', NULL, 4, 1, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(47, 'payments_billing.financial_reports', 'View Financial Reports', 'Can view financial reports and analytics', 'payments_billing', NULL, 5, 1, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(48, 'transportation.drivers.manage', 'Manage Transportation Drivers', 'Can manage transportation drivers', 'transportation', NULL, 1, 1, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(49, 'transportation.vehicles.manage', 'Manage Transportation Vehicles', 'Can manage transportation vehicles', 'transportation', NULL, 2, 1, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(50, 'transportation.requests', 'Manage Transportation Requests', 'Can manage transport requests', 'transportation', NULL, 3, 1, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(51, 'reports_analytics.quality_assurance', 'View Quality Assurance Report ', 'Can view quality assurance reports', 'reports_analytics', NULL, 1, 1, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(52, 'reports_analytics.users', 'View User Report', 'Can view user reports', 'reports_analytics', NULL, 2, 1, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(53, 'reports_analytics.health', 'View Health Report', 'Can view health reports', 'reports_analytics', NULL, 3, 1, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(54, 'reports_analytics.care_nurse', 'View Care & Nurse Reports', 'Can view care & nurse reports', 'reports_analytics', NULL, 4, 1, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(55, 'reports_analytics.financial', 'View Financial Reports', 'Can view  financial reports', 'reports_analytics', NULL, 5, 1, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(56, 'reports_analytics.transport', 'View Transport Reports', 'Can view  transport reports', 'reports_analytics', NULL, 6, 1, '2025-09-30 20:20:56', '2025-09-30 20:20:56');

-- --------------------------------------------------------

--
-- Table structure for table `personal_access_tokens`
--

CREATE TABLE `personal_access_tokens` (
  `id` bigint UNSIGNED NOT NULL,
  `tokenable_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` bigint UNSIGNED NOT NULL,
  `name` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `personal_access_tokens`
--

INSERT INTO `personal_access_tokens` (`id`, `tokenable_type`, `tokenable_id`, `name`, `token`, `abilities`, `last_used_at`, `expires_at`, `created_at`, `updated_at`) VALUES
(13, 'App\\Models\\User', 4, 'auth-token', '144d17bcba56e86c6e5282e8118c40aee29292f775e7ecb054f2c4ec1dfb7ecf', '[\"*\"]', '2025-10-09 14:35:05', '2025-10-13 23:33:22', '2025-10-06 23:33:22', '2025-10-09 14:35:05'),
(14, 'App\\Models\\User', 4, 'auth-token', '260d3a3594d95017a4a80e3ae5c6b45b4b703edc004212f806d895d4ede6d2a1', '[\"*\"]', '2025-10-07 00:00:04', '2025-10-13 23:35:35', '2025-10-06 23:35:35', '2025-10-07 00:00:04'),
(15, 'App\\Models\\User', 4, 'auth-token', 'e688653aec6513478c4c826306321a4c7a2e6370d0ac46da181676054775f003', '[\"*\"]', '2025-10-07 00:07:24', '2025-10-14 00:07:24', '2025-10-07 00:07:24', '2025-10-07 00:07:24'),
(16, 'App\\Models\\User', 4, 'auth-token', 'd42f04adaea991458d04cbc7c75cadf5dddec5ad29bd7a17e8f47bb84752b3d1', '[\"*\"]', '2025-10-07 00:13:50', '2025-10-14 00:13:50', '2025-10-07 00:13:50', '2025-10-07 00:13:50'),
(17, 'App\\Models\\User', 4, 'auth-token', '867539618538efb05cc8059bf4f8259a3d3c97b80f94dfc585f88be9d01fef7f', '[\"*\"]', '2025-10-07 09:00:00', '2025-10-14 02:02:43', '2025-10-07 02:02:43', '2025-10-07 09:00:00'),
(18, 'App\\Models\\User', 4, 'auth-token', '92f58c3403776342343e634fa7ea5d5821ed47c86a4ca7b32cbb1aa28aa72d8c', '[\"*\"]', '2025-10-07 09:16:03', '2025-10-14 09:15:53', '2025-10-07 09:15:53', '2025-10-07 09:16:03'),
(19, 'App\\Models\\User', 4, 'auth-token', '219dbb93ba784300a70a5936dbdfdce51251150fa98e11be14b6273a5a726277', '[\"*\"]', '2025-10-07 09:17:00', '2025-10-14 09:16:41', '2025-10-07 09:16:41', '2025-10-07 09:17:00'),
(20, 'App\\Models\\User', 4, 'auth-token', 'b09aab47a399c5af24813fde201651204e0526241ea21db079e7267a8ca0ff39', '[\"*\"]', '2025-10-07 09:31:50', '2025-10-14 09:19:42', '2025-10-07 09:19:42', '2025-10-07 09:31:50'),
(21, 'App\\Models\\User', 4, 'auth-token', '37cc261b593f1b9009204c6b008224ca8f51972742c97b675797a3a43b74d5a3', '[\"*\"]', '2025-10-07 12:14:10', '2025-10-14 10:17:51', '2025-10-07 10:17:51', '2025-10-07 12:14:10'),
(22, 'App\\Models\\User', 4, 'auth-token', 'a4562e7ce0fc352b61cca7ef0bbaac19417797755ff9fd626918c30d0880f82f', '[\"*\"]', NULL, '2025-10-14 12:20:10', '2025-10-07 12:20:10', '2025-10-07 12:20:10'),
(23, 'App\\Models\\User', 4, 'auth-token', 'e9720bef0d9490617cc3fc9474c2846c3d0deac815e592eba09ec3c2c5663df8', '[\"*\"]', '2025-10-08 19:34:56', '2025-10-14 12:21:31', '2025-10-07 12:21:31', '2025-10-08 19:34:56'),
(24, 'App\\Models\\User', 4, 'auth-token', '879d7ad5309aa8dd0bd7e9009395ba6924c9853e1df97bcb790fbfb4c2b7eee0', '[\"*\"]', '2025-10-07 13:12:55', '2025-10-14 12:51:39', '2025-10-07 12:51:39', '2025-10-07 13:12:55'),
(25, 'App\\Models\\User', 4, 'auth-token', '497b3f1b2bc52ad89e5a402b9ca6115fcd72dc72abe18b289352132d0cca0a9c', '[\"*\"]', '2025-10-07 13:24:22', '2025-10-14 13:13:33', '2025-10-07 13:13:33', '2025-10-07 13:24:22'),
(26, 'App\\Models\\User', 4, 'auth-token', 'b632f836307946ae90ba2c6f23f7ee07f5c54153120dc7a9885cb573ce3bdd23', '[\"*\"]', '2025-10-07 15:03:45', '2025-10-14 13:58:20', '2025-10-07 13:58:20', '2025-10-07 15:03:45'),
(27, 'App\\Models\\User', 4, 'auth-token', '1e1cc04c54ee9abbbbbc646ecd6c84e101b4f7f32e754f5b6a2db424512121a8', '[\"*\"]', '2025-10-07 15:56:43', '2025-10-14 15:12:44', '2025-10-07 15:12:44', '2025-10-07 15:56:43'),
(28, 'App\\Models\\User', 4, 'auth-token', '358379a49db3e140b803224c5c1f64d7fce0154c7eda3e57f2cad7fc987fa0ea', '[\"*\"]', '2025-10-07 18:05:43', '2025-10-14 16:08:54', '2025-10-07 16:08:54', '2025-10-07 18:05:43'),
(29, 'App\\Models\\User', 4, 'auth-token', '8148b3a04f00377bfd57900323278585dfaf1b7205495d9dc0b5372c3538b006', '[\"*\"]', '2025-10-07 18:28:22', '2025-10-14 18:06:57', '2025-10-07 18:06:57', '2025-10-07 18:28:22'),
(30, 'App\\Models\\User', 4, 'auth-token', '26dddd1edb94365b0a1feddfc44b997ef5d0ab62db02d826048c5d93c9d38723', '[\"*\"]', '2025-10-07 18:17:52', '2025-10-14 18:09:12', '2025-10-07 18:09:12', '2025-10-07 18:17:52'),
(31, 'App\\Models\\User', 4, 'auth-token', 'ed9b2b24c7e279012c387246add66a23d383b3619d581290eeb72980b58fc822', '[\"*\"]', '2025-10-07 20:10:53', '2025-10-14 19:32:21', '2025-10-07 19:32:21', '2025-10-07 20:10:53'),
(32, 'App\\Models\\User', 4, 'auth-token', 'e26ecbb781cb5d8b0f6183d1cfee4aed5a1da015769eb6e7d2c31c228139ff0f', '[\"*\"]', '2025-10-07 19:49:48', '2025-10-14 19:47:10', '2025-10-07 19:47:10', '2025-10-07 19:49:48'),
(33, 'App\\Models\\User', 4, 'auth-token', '9d865dba1f579a3f59c8d3c0f6272b1caf4cbb4773c0f88af3a5a2b038cb262c', '[\"*\"]', '2025-10-07 19:51:36', '2025-10-14 19:50:36', '2025-10-07 19:50:36', '2025-10-07 19:51:36'),
(34, 'App\\Models\\User', 4, 'auth-token', '1243543eaff1266104102fd6fee856ea947cbf053f2d51e572478eea76216b65', '[\"*\"]', '2025-10-07 21:45:32', '2025-10-14 20:53:57', '2025-10-07 20:53:57', '2025-10-07 21:45:32'),
(35, 'App\\Models\\User', 4, 'auth-token', '637b7e11f0a623c3f2d8e2f73c1a82f9158059d4ce87f445af7e139f78ba4dd0', '[\"*\"]', '2025-10-08 12:29:04', '2025-10-14 21:47:50', '2025-10-07 21:47:50', '2025-10-08 12:29:04'),
(36, 'App\\Models\\User', 4, 'auth-token', '1c9a9a098ad3f701118a550b0beedf979da2dc8277b0ec48606ba22ed076d05e', '[\"*\"]', '2025-10-07 22:22:01', '2025-10-14 22:18:45', '2025-10-07 22:18:45', '2025-10-07 22:22:01'),
(37, 'App\\Models\\User', 4, 'auth-token', '995ad6823b1d46b25e914405b5d87339eaff6c2cafa0de54cd4d2d78f2ff49b4', '[\"*\"]', '2025-10-07 22:34:01', '2025-10-14 22:24:32', '2025-10-07 22:24:32', '2025-10-07 22:34:01'),
(38, 'App\\Models\\User', 4, 'auth-token', 'cce1d2caa13ab7e3d6512b1df01011f594d2021d89769c87dfdd443a6e4acab5', '[\"*\"]', '2025-10-08 07:43:48', '2025-10-15 00:18:13', '2025-10-08 00:18:13', '2025-10-08 07:43:48'),
(39, 'App\\Models\\User', 4, 'auth-token', 'd3945ad0873b3b5c060b645087cbad18a4b6dcef1d1156404d4fed5d89951c84', '[\"*\"]', '2025-10-08 10:17:20', '2025-10-15 10:17:08', '2025-10-08 10:17:08', '2025-10-08 10:17:20'),
(40, 'App\\Models\\User', 4, 'auth-token', '22057e855c22778fe9c673425d4b221b4459b2076c5a141ab52cf649b8ff3b23', '[\"*\"]', '2025-10-08 13:15:35', '2025-10-15 12:29:44', '2025-10-08 12:29:44', '2025-10-08 13:15:35'),
(41, 'App\\Models\\User', 4, 'auth-token', '54793e71ab09c5fd38f7e2202db661f4603f296c739ae669e1fbacac884bf1e9', '[\"*\"]', '2025-10-08 13:19:34', '2025-10-15 13:18:11', '2025-10-08 13:18:11', '2025-10-08 13:19:34'),
(42, 'App\\Models\\User', 4, 'auth-token', '8fbf0837af7873fa82d6c64bcb36abada962526d65399b4cc9006f518f843b34', '[\"*\"]', '2025-10-08 13:22:38', '2025-10-15 13:22:09', '2025-10-08 13:22:09', '2025-10-08 13:22:38'),
(43, 'App\\Models\\User', 4, 'auth-token', '7fd829009ada0bb82379207bf10ebfcb7a26d4013df67d6cef2b8328ea0f4f0f', '[\"*\"]', '2025-10-08 13:29:52', '2025-10-15 13:29:33', '2025-10-08 13:29:33', '2025-10-08 13:29:52'),
(44, 'App\\Models\\User', 4, 'auth-token', 'bad14055584aedee2aeb67915fef14784df3bb2d66303b59da8577b7af9d7155', '[\"*\"]', '2025-10-08 13:44:58', '2025-10-15 13:44:57', '2025-10-08 13:44:57', '2025-10-08 13:44:58'),
(45, 'App\\Models\\User', 4, 'auth-token', '7650a8493f663798c9f257876c5e64c99ec59a75dbb17604929b2792f15f8664', '[\"*\"]', '2025-10-08 14:06:34', '2025-10-15 13:57:49', '2025-10-08 13:57:49', '2025-10-08 14:06:34'),
(46, 'App\\Models\\User', 4, 'auth-token', '738b14d9f3c3405e4cbf34004129075bfd9e2b01af65a2465f0ff394d84282af', '[\"*\"]', '2025-10-08 16:51:41', '2025-10-15 14:33:44', '2025-10-08 14:33:44', '2025-10-08 16:51:41'),
(47, 'App\\Models\\User', 4, 'auth-token', 'e8508a15b197f9298d8d585a897b0effd1b7596c912dff82003745925b289b31', '[\"*\"]', '2025-10-08 19:28:40', '2025-10-15 16:55:13', '2025-10-08 16:55:13', '2025-10-08 19:28:40'),
(48, 'App\\Models\\User', 4, 'auth-token', 'a05fcf206433b0cb5acfc06a6df8274baa5ce8c41dcef0b5159714ccf68e28cb', '[\"*\"]', '2025-10-08 17:43:29', '2025-10-15 17:34:09', '2025-10-08 17:34:09', '2025-10-08 17:43:29'),
(49, 'App\\Models\\User', 4, 'auth-token', 'b00c6b4562216120e54219c2fcf4178409afe75fcabfec1af055facf27267b95', '[\"*\"]', '2025-10-08 21:32:05', '2025-10-15 19:46:24', '2025-10-08 19:46:24', '2025-10-08 21:32:05'),
(50, 'App\\Models\\User', 4, 'auth-token', '28105bbff6b64cb871ffbdb416c3f2ba1829d12dcb8626c251435ce61765e896', '[\"*\"]', '2025-10-08 21:48:14', '2025-10-15 21:32:50', '2025-10-08 21:32:50', '2025-10-08 21:48:14'),
(51, 'App\\Models\\User', 4, 'auth-token', 'ee0bbb66fe2703a23483b5999c22d1a446dec09ac2dd74535558cae1c8278112', '[\"*\"]', '2025-10-08 22:22:34', '2025-10-15 21:49:45', '2025-10-08 21:49:45', '2025-10-08 22:22:34'),
(52, 'App\\Models\\User', 4, 'auth-token', '03b40c4c43dc2b9475015f3f2d5dfbb761cb9ad238e582fa6d193bc5b6667da7', '[\"*\"]', '2025-10-08 22:32:43', '2025-10-15 22:32:41', '2025-10-08 22:32:41', '2025-10-08 22:32:43'),
(53, 'App\\Models\\User', 4, 'auth-token', 'c803ced60418020d68912f5b2fc99bb9e75a495bda3e4ba4bffff5ce30241bcf', '[\"*\"]', '2025-10-09 00:11:00', '2025-10-15 22:33:18', '2025-10-08 22:33:18', '2025-10-09 00:11:00'),
(54, 'App\\Models\\User', 4, 'auth-token', '4f41979f631fd6c50da9a5460ae23f13b592886752e51cc58c87f1a893fa82b1', '[\"*\"]', '2025-10-08 23:44:17', '2025-10-15 22:52:15', '2025-10-08 22:52:15', '2025-10-08 23:44:17'),
(55, 'App\\Models\\User', 4, 'auth-token', 'a80ef88ac829a6d887f84de67146ce72ef78bad1e4d20af241b9e420fb6249c4', '[\"*\"]', '2025-10-09 00:16:42', '2025-10-16 00:15:18', '2025-10-09 00:15:18', '2025-10-09 00:16:42'),
(56, 'App\\Models\\User', 4, 'auth-token', 'fc8122f1a08047d664513cb68db16f0398b8154bf86e0c54cb2d6e401599ce05', '[\"*\"]', '2025-10-09 00:46:41', '2025-10-16 00:21:36', '2025-10-09 00:21:36', '2025-10-09 00:46:41'),
(57, 'App\\Models\\User', 4, 'auth-token', '7e3be46552728d2847eaef7a1637017a960867910981b74da227c9bb45195016', '[\"*\"]', '2025-10-09 03:11:55', '2025-10-16 01:23:49', '2025-10-09 01:23:49', '2025-10-09 03:11:55'),
(58, 'App\\Models\\User', 4, 'auth-token', '660aba2bed602d66a1b5bac4d01a8c62b7cdf28bd071c87e6b8393ea862840d0', '[\"*\"]', '2025-10-09 01:33:18', '2025-10-16 01:27:48', '2025-10-09 01:27:48', '2025-10-09 01:33:18'),
(59, 'App\\Models\\User', 4, 'auth-token', '3cdc07ae86a721513227e83c7bfeb31253a8d1a6e630ec3e0516b8b6c5e9a24d', '[\"*\"]', '2025-10-09 02:01:16', '2025-10-16 01:36:25', '2025-10-09 01:36:25', '2025-10-09 02:01:16'),
(60, 'App\\Models\\User', 4, 'auth-token', '18e3f808250b9ab2384cb2d154364ac0a9b128ed8196cb08c26268b113163716', '[\"*\"]', '2025-10-09 02:14:00', '2025-10-16 02:09:56', '2025-10-09 02:09:56', '2025-10-09 02:14:00'),
(61, 'App\\Models\\User', 4, 'auth-token', 'd2ab99e985f0e16521fadc31c2deae1d5f16afd818d2ba5a2a785b1aa33c4c03', '[\"*\"]', '2025-10-09 03:11:44', '2025-10-16 03:01:54', '2025-10-09 03:01:54', '2025-10-09 03:11:44'),
(62, 'App\\Models\\User', 4, 'auth-token', '4d65d9c624dd91753b8916dc83467350b6eab5da6b1d6e75b45cae9f2497b388', '[\"*\"]', '2025-10-09 03:16:09', '2025-10-16 03:15:09', '2025-10-09 03:15:09', '2025-10-09 03:16:09'),
(63, 'App\\Models\\User', 4, 'auth-token', '3b82bc1a2b60c00d344991fe53cc7c7b95d61feba838dd33750580fa1ac6954f', '[\"*\"]', '2025-10-09 03:19:12', '2025-10-16 03:16:41', '2025-10-09 03:16:41', '2025-10-09 03:19:12'),
(64, 'App\\Models\\User', 4, 'auth-token', '7e8b8bc406e0be353820be3e8bbbd197a8815b8e4098af4879dc550f2edaf27d', '[\"*\"]', '2025-10-09 03:24:21', '2025-10-16 03:21:06', '2025-10-09 03:21:06', '2025-10-09 03:24:21'),
(65, 'App\\Models\\User', 4, 'auth-token', '4f7ca330024d2507197209d65436a30f9340b1f6263bf24881bc46fbdc2b3446', '[\"*\"]', '2025-10-09 03:29:58', '2025-10-16 03:25:08', '2025-10-09 03:25:08', '2025-10-09 03:29:58'),
(66, 'App\\Models\\User', 4, 'auth-token', '3ac51775d4799a1c40aced8b52ce6509209bbcee168aeeee3d6939a377688889', '[\"*\"]', '2025-10-09 03:39:28', '2025-10-16 03:32:39', '2025-10-09 03:32:39', '2025-10-09 03:39:28'),
(67, 'App\\Models\\User', 4, 'auth-token', '33b2d174e327c8569a74aef49a1d16d8e0b1426fedac1dbf87db82efe23d029b', '[\"*\"]', '2025-10-09 03:44:50', '2025-10-16 03:43:48', '2025-10-09 03:43:48', '2025-10-09 03:44:50'),
(68, 'App\\Models\\User', 4, 'auth-token', '5e04232914fc479812a50d3befca9b8502887247cb455a39e118bf3126edbdbe', '[\"*\"]', '2025-10-09 11:35:58', '2025-10-16 11:33:37', '2025-10-09 11:33:37', '2025-10-09 11:35:58'),
(69, 'App\\Models\\User', 4, 'auth-token', 'd8f84c32b1e23ebd6c70265107f2d3eefb588199e88a8ddbd0321f66c106e61b', '[\"*\"]', '2025-10-10 09:54:03', '2025-10-16 14:04:42', '2025-10-09 14:04:42', '2025-10-10 09:54:03'),
(70, 'App\\Models\\User', 4, 'auth-token', '8e96d49becde4088ab56ae5b40a64d6cef237b8c6b480d64a293c109a5910d61', '[\"*\"]', '2025-10-09 17:55:55', '2025-10-16 17:40:57', '2025-10-09 17:40:57', '2025-10-09 17:55:55'),
(71, 'App\\Models\\User', 4, 'auth-token', 'c17072048328b3dc681005a800d66ce19bd7685f8565626ab500f2739f0afa5b', '[\"*\"]', '2025-10-09 17:59:14', '2025-10-16 17:57:01', '2025-10-09 17:57:01', '2025-10-09 17:59:14'),
(72, 'App\\Models\\User', 4, 'auth-token', '8c869387921e65b70e065b3becf5e252bb83c24707aafde4fa50fc137b04033e', '[\"*\"]', '2025-10-09 18:02:05', '2025-10-16 18:00:08', '2025-10-09 18:00:08', '2025-10-09 18:02:05'),
(73, 'App\\Models\\User', 4, 'auth-token', '9e62d3ec8e3bbaa5f210bffc09411f97ff6ac9146a92d3bb986f8b0e6a33ec15', '[\"*\"]', '2025-10-09 23:32:50', '2025-10-16 19:43:39', '2025-10-09 19:43:40', '2025-10-09 23:32:50'),
(74, 'App\\Models\\User', 4, 'auth-token', '59e0b1ca92c189e357b29d234dcb2df19dc92a3fc2fe61bd5e0911ff2de29b34', '[\"*\"]', '2025-10-10 10:13:40', '2025-10-17 09:56:09', '2025-10-10 09:56:09', '2025-10-10 10:13:40'),
(75, 'App\\Models\\User', 4, 'auth-token', '728472a2154cdc8af6ae49edadb273aee1a26fba07a98aaa04b5381db1539eb1', '[\"*\"]', '2025-10-10 10:35:17', '2025-10-17 10:06:11', '2025-10-10 10:06:11', '2025-10-10 10:35:17'),
(76, 'App\\Models\\User', 4, 'auth-token', 'c039c85783c4824fc3c2ff562145e56fdeb57391c0d1d91f979cfeb13c2755ce', '[\"*\"]', '2025-10-10 10:25:55', '2025-10-17 10:20:21', '2025-10-10 10:20:21', '2025-10-10 10:25:55'),
(77, 'App\\Models\\User', 4, 'auth-token', '7f022b9ba970e1c601ebbb998e7b802fb0bf155b0d3d6fba4e023cf011c8e96c', '[\"*\"]', '2025-10-10 11:30:23', '2025-10-17 11:28:34', '2025-10-10 11:28:34', '2025-10-10 11:30:23'),
(78, 'App\\Models\\User', 4, 'auth-token', 'f440189869e8c6fce686824586beedaae03f60e4b3b742330e40d72a3abf5baa', '[\"*\"]', '2025-10-10 11:31:19', '2025-10-17 11:31:15', '2025-10-10 11:31:15', '2025-10-10 11:31:19'),
(79, 'App\\Models\\User', 4, 'auth-token', 'ddcdf5dbe05bca1d2ba34eec39a30c0299b5cff9c307e76f1e216aecda0f87d9', '[\"*\"]', '2025-10-10 12:13:05', '2025-10-17 11:45:44', '2025-10-10 11:45:44', '2025-10-10 12:13:05'),
(80, 'App\\Models\\User', 4, 'auth-token', '7c31db8b08dfddf5b9f0edc32f1bda9721a841616d51f98edbc9c9da7fc5a57f', '[\"*\"]', '2025-10-10 12:31:28', '2025-10-17 12:29:10', '2025-10-10 12:29:10', '2025-10-10 12:31:28'),
(81, 'App\\Models\\User', 4, 'auth-token', '87f019ce0e291bca55d530a1a8d266a89a7928b6a59288d466f145d27fcd7681', '[\"*\"]', '2025-10-10 12:56:29', '2025-10-17 12:56:20', '2025-10-10 12:56:20', '2025-10-10 12:56:29'),
(82, 'App\\Models\\User', 4, 'auth-token', '3bd12990c4fbba90d5ba430a3e653d55b646d63a5f1edb0f336adb5b75077a6b', '[\"*\"]', '2025-10-10 13:17:37', '2025-10-17 13:16:33', '2025-10-10 13:16:33', '2025-10-10 13:17:37'),
(83, 'App\\Models\\User', 4, 'auth-token', '6b74e7b8014cbc29739406bfb57f7b4200b4a9c9242042c20d2e41b3efd64578', '[\"*\"]', '2025-10-10 13:25:25', '2025-10-17 13:24:04', '2025-10-10 13:24:04', '2025-10-10 13:25:25'),
(84, 'App\\Models\\User', 4, 'auth-token', '7ea6ea9fcf3f2b2796bb94ae2d265795b4ae117509385987a7544cb43d21f574', '[\"*\"]', '2025-10-10 13:43:45', '2025-10-17 13:43:19', '2025-10-10 13:43:19', '2025-10-10 13:43:45'),
(85, 'App\\Models\\User', 4, 'auth-token', '5db1e3de880dd6bf7fb28bbf90e891bc6a8f37940a8f41285bfc967a44f13a22', '[\"*\"]', '2025-10-10 13:49:34', '2025-10-17 13:49:13', '2025-10-10 13:49:13', '2025-10-10 13:49:34'),
(86, 'App\\Models\\User', 4, 'auth-token', 'bdc27e539cd5b8635f242852062b93cf14a32d039a7a25583ebca5fb330f2c9f', '[\"*\"]', '2025-10-10 14:06:36', '2025-10-17 13:57:30', '2025-10-10 13:57:30', '2025-10-10 14:06:36'),
(87, 'App\\Models\\User', 4, 'auth-token', '94133a8f6efc67e1fb9403daa7ad191d57b30d260fdb94027143097010df2469', '[\"*\"]', '2025-10-10 14:33:12', '2025-10-17 14:19:17', '2025-10-10 14:19:17', '2025-10-10 14:33:12'),
(88, 'App\\Models\\User', 4, 'auth-token', 'e17f0463602657bbe0b857d7513ae27ca1b0aa51620d3cfda278b680bd8b7be8', '[\"*\"]', '2025-10-10 14:47:18', '2025-10-17 14:44:39', '2025-10-10 14:44:39', '2025-10-10 14:47:18'),
(89, 'App\\Models\\User', 4, 'auth-token', '509b4abcd931c01e4f78847b98dbe50cb1bf532f47cbd0c96b4f0800ee9fc350', '[\"*\"]', '2025-10-10 18:53:58', '2025-10-17 14:59:28', '2025-10-10 14:59:28', '2025-10-10 18:53:58'),
(90, 'App\\Models\\User', 4, 'auth-token', 'bb124182edb383d3aa3af1ef9fb35f7fcf7ee0ec9db209eb0dd8ca3ecac7f8c6', '[\"*\"]', '2025-10-10 15:38:26', '2025-10-17 15:37:18', '2025-10-10 15:37:18', '2025-10-10 15:38:26'),
(91, 'App\\Models\\User', 4, 'auth-token', '08840a1719600952d42b01a24360ba265e2758bf54e17bac6abd9b81590f8a0a', '[\"*\"]', '2025-10-10 21:28:06', '2025-10-17 18:54:16', '2025-10-10 18:54:16', '2025-10-10 21:28:06'),
(92, 'App\\Models\\User', 4, 'auth-token', 'e784fcfb0de4a660abc025222618b5386795a0a3a096767be187901d19835571', '[\"*\"]', '2025-10-10 19:17:03', '2025-10-17 19:16:48', '2025-10-10 19:16:48', '2025-10-10 19:17:03'),
(93, 'App\\Models\\User', 4, 'auth-token', '0efa93c2bdaa8572768d219995c6bdcb8c37df167ee9a133e3792cca2ed98139', '[\"*\"]', '2025-10-10 20:54:18', '2025-10-17 20:53:28', '2025-10-10 20:53:28', '2025-10-10 20:54:18'),
(94, 'App\\Models\\User', 4, 'auth-token', 'ce7f57ddd7d3c1488d02d15f7c5dd3322cc144853b7e448b5be50ac00c86981a', '[\"*\"]', '2025-10-10 21:34:03', '2025-10-17 21:34:01', '2025-10-10 21:34:01', '2025-10-10 21:34:03'),
(95, 'App\\Models\\User', 4, 'auth-token', 'cea77548953beb26efa16df273d63805bd04597d8655dc8e17a50b206aec448c', '[\"*\"]', '2025-10-13 14:28:52', '2025-10-20 14:27:45', '2025-10-13 14:27:45', '2025-10-13 14:28:52'),
(96, 'App\\Models\\User', 4, 'auth-token', 'cdb2d1f77c0fb045fbf4f140c389502c69336c92e3dd3b43c18fd8a2cf88e8dd', '[\"*\"]', '2025-10-13 15:05:41', '2025-10-20 15:05:21', '2025-10-13 15:05:21', '2025-10-13 15:05:41'),
(97, 'App\\Models\\User', 4, 'auth-token', '8d6fffd61655391bcd8ef2d672e261b2416e6053960d99be61b1481c9bfce433', '[\"*\"]', '2025-10-13 15:08:15', '2025-10-20 15:07:52', '2025-10-13 15:07:52', '2025-10-13 15:08:15'),
(98, 'App\\Models\\User', 4, 'auth-token', '9a33742655fa7df745f68462d6f897fc97068cde68402ad9dcd1013d05a31115', '[\"*\"]', '2025-10-13 15:18:33', '2025-10-20 15:18:24', '2025-10-13 15:18:24', '2025-10-13 15:18:33'),
(99, 'App\\Models\\User', 4, 'auth-token', '08f40d4c00a6d6383119844ee81945ac00aec744802846535f7b11b489533377', '[\"*\"]', '2025-10-13 15:46:56', '2025-10-20 15:46:55', '2025-10-13 15:46:55', '2025-10-13 15:46:56'),
(100, 'App\\Models\\User', 4, 'auth-token', '4c37f924526a26c29947491161c39875897ca710a7edc901fa17d3ea54683b22', '[\"*\"]', '2025-10-16 23:44:56', '2025-10-23 23:44:55', '2025-10-16 23:44:55', '2025-10-16 23:44:56'),
(101, 'App\\Models\\User', 4, 'auth-token', '5b7111598884ad399a0634dc21d4c7d2c60cabe25165503820ec4470eb86d3ee', '[\"*\"]', '2025-10-17 09:53:48', '2025-10-23 23:47:33', '2025-10-16 23:47:33', '2025-10-17 09:53:48'),
(102, 'App\\Models\\User', 4, 'auth-token', '9b9bc159efad0cbc4f39f6be65fb3bbc569a903a138f165b87b879d7ed451b9f', '[\"*\"]', '2025-10-17 09:48:39', '2025-10-24 09:32:44', '2025-10-17 09:32:44', '2025-10-17 09:48:39'),
(103, 'App\\Models\\User', 4, 'auth-token', 'c28499ef47b1db6aa667a3f2cd9655f51a30806143bf4f1cafd75e4fd0db424f', '[\"*\"]', '2025-10-17 09:57:49', '2025-10-24 09:57:48', '2025-10-17 09:57:48', '2025-10-17 09:57:49'),
(104, 'App\\Models\\User', 4, 'auth-token', '68f00af1394f68ac1e79a7d68ed759cb49ff5586a689c089773cd7f9ca41fdca', '[\"*\"]', '2025-10-19 18:46:44', '2025-10-24 11:12:09', '2025-10-17 11:12:09', '2025-10-19 18:46:44'),
(105, 'App\\Models\\User', 4, 'auth-token', 'd7db5b7bb86505e6b048b8c8b4a667d5c0ae868c23a083909541c8ddeb75d39e', '[\"*\"]', '2025-10-19 18:56:56', '2025-10-26 18:49:44', '2025-10-19 18:49:44', '2025-10-19 18:56:56'),
(106, 'App\\Models\\User', 4, 'auth-token', 'eff168fa81f083c3445fbf6ae5a2cfb28880bb6956a000127727e845fd04b669', '[\"*\"]', '2025-10-20 17:36:26', '2025-10-26 19:01:25', '2025-10-19 19:01:25', '2025-10-20 17:36:26'),
(107, 'App\\Models\\User', 4, 'auth-token', 'f51d6130c6af16693b42168feac1584da0fe637ed0635c76b44151932d045915', '[\"*\"]', '2025-10-20 17:42:30', '2025-10-27 17:41:58', '2025-10-20 17:41:58', '2025-10-20 17:42:30'),
(108, 'App\\Models\\User', 4, 'auth-token', '9d78ec0cc565f1410594e85999265ec3d3eef33d3f368c1cd54dbe577eb83aba', '[\"*\"]', '2025-10-20 17:48:42', '2025-10-27 17:43:37', '2025-10-20 17:43:37', '2025-10-20 17:48:42'),
(109, 'App\\Models\\User', 4, 'auth-token', 'e30a93af4959517a2165266ecaa8aa9382a1a7af7676c64da29aa4d7693f60e7', '[\"*\"]', '2025-10-21 09:43:05', '2025-10-27 17:49:01', '2025-10-20 17:49:01', '2025-10-21 09:43:05'),
(110, 'App\\Models\\User', 4, 'auth-token', '0dc84e43af9f4b9f708ccbbd86d8b8aee75bfbec5334d5651ceff618f026f3f5', '[\"*\"]', '2025-10-21 09:43:45', '2025-10-28 09:43:38', '2025-10-21 09:43:38', '2025-10-21 09:43:45'),
(111, 'App\\Models\\User', 4, 'auth-token', '8e9f79df2267244cc639f9298ccc69c0e7ce67578b52a8dd6d78151c2c547b39', '[\"*\"]', '2025-10-21 10:02:44', '2025-10-28 09:47:43', '2025-10-21 09:47:43', '2025-10-21 10:02:44'),
(112, 'App\\Models\\User', 4, 'auth-token', '7ab4e0b53512d38d32d28d28dd1e4c65befe7c03013970af74ced5c1fe84a9d2', '[\"*\"]', '2025-10-21 10:22:38', '2025-10-28 10:07:37', '2025-10-21 10:07:37', '2025-10-21 10:22:38'),
(113, 'App\\Models\\User', 4, 'auth-token', '084df727bbae88aefa09b1dcebf58f1eb66778707e76d39f4dc7dc0fba6c1c2b', '[\"*\"]', '2025-10-21 10:40:39', '2025-10-28 10:25:39', '2025-10-21 10:25:39', '2025-10-21 10:40:39'),
(114, 'App\\Models\\User', 4, 'auth-token', 'c39c89cf4f48508d87c6f1d305de23dd0e41076cf45e687a7aa2814f8c16c0be', '[\"*\"]', '2025-10-21 10:51:33', '2025-10-28 10:45:48', '2025-10-21 10:45:48', '2025-10-21 10:51:33'),
(115, 'App\\Models\\User', 4, 'auth-token', 'cafde936a4a682f7dc2701c481694e9fd785c539864941993b2d8982af979e51', '[\"*\"]', '2025-10-21 10:53:20', '2025-10-28 10:53:19', '2025-10-21 10:53:19', '2025-10-21 10:53:20'),
(116, 'App\\Models\\User', 4, 'auth-token', '592de1cf8412e3e0201d4d1b351d8851875e7d1b121b4e876f696081c56b057f', '[\"*\"]', '2025-10-21 10:56:00', '2025-10-28 10:55:59', '2025-10-21 10:55:59', '2025-10-21 10:56:00'),
(117, 'App\\Models\\User', 4, 'auth-token', '81d82b472738ca9efcf22c0d9b1d0ecca2600bac03facd34c8e719f20507b601', '[\"*\"]', '2025-10-21 10:57:30', '2025-10-28 10:57:29', '2025-10-21 10:57:29', '2025-10-21 10:57:30'),
(118, 'App\\Models\\User', 4, 'auth-token', 'b5c1d9edcd77de82455a5a07aa065907d78ee7adc84aa6d4ce30d754a7120ad2', '[\"*\"]', '2025-10-21 11:05:35', '2025-10-28 10:59:31', '2025-10-21 10:59:31', '2025-10-21 11:05:35'),
(119, 'App\\Models\\User', 4, 'auth-token', 'b173e22d61674de4aa05e3378c1563acaa92fbe37cd796c2576c83d289337e07', '[\"*\"]', '2025-10-21 11:07:29', '2025-10-28 11:07:28', '2025-10-21 11:07:28', '2025-10-21 11:07:29'),
(120, 'App\\Models\\User', 4, 'auth-token', 'd9758be3267c28881b42d922fcdb51021742616f1689471e2d427b5926747b5d', '[\"*\"]', '2025-10-21 11:33:54', '2025-10-28 11:08:53', '2025-10-21 11:08:53', '2025-10-21 11:33:54'),
(121, 'App\\Models\\User', 4, 'auth-token', '6d2193b46c8e252f4a8d236a88f3b8d6382bb7fc0126bda4274485994aa967a5', '[\"*\"]', '2025-10-21 11:35:21', '2025-10-28 11:35:11', '2025-10-21 11:35:11', '2025-10-21 11:35:21'),
(122, 'App\\Models\\User', 4, 'auth-token', '072c0e63342be1585109a5a13b185689109542e0dbb2a56ebeec7c6af001a576', '[\"*\"]', '2025-10-21 11:40:57', '2025-10-28 11:35:56', '2025-10-21 11:35:56', '2025-10-21 11:40:57'),
(123, 'App\\Models\\User', 4, 'auth-token', '179cc23438f0797d0ce4430284b64f8ce8ce3ec2162d51c3456f92c38b9a49bf', '[\"*\"]', '2025-10-21 12:15:30', '2025-10-28 11:44:35', '2025-10-21 11:44:35', '2025-10-21 12:15:30'),
(124, 'App\\Models\\User', 4, 'auth-token', '420e0ce9c52cda1fd40ffd40315717cc8a55e1fbfd0399435e15febe76632eed', '[\"*\"]', '2025-10-21 12:40:22', '2025-10-28 12:20:21', '2025-10-21 12:20:21', '2025-10-21 12:40:22'),
(125, 'App\\Models\\User', 4, 'auth-token', '94245071206a9790075bf6e8e15a575b89eb645de1a5f314a3badbcf945b33da', '[\"*\"]', '2025-10-21 13:01:56', '2025-10-28 12:41:56', '2025-10-21 12:41:56', '2025-10-21 13:01:56'),
(126, 'App\\Models\\User', 4, 'auth-token', 'a94006ff65b8509273cc193eec0c81d6511a58bfb981ca2d0a6ce3975c49f5e9', '[\"*\"]', '2025-10-21 15:29:23', '2025-10-28 13:02:46', '2025-10-21 13:02:46', '2025-10-21 15:29:23'),
(127, 'App\\Models\\User', 4, 'auth-token', 'a085218ca8b89d8aa1748c8c39fec8c9e8f18866d74c22746a7bb73de1610559', '[\"*\"]', '2025-10-23 15:19:50', '2025-10-28 15:29:49', '2025-10-21 15:29:49', '2025-10-23 15:19:50'),
(128, 'App\\Models\\User', 4, 'auth-token', '28fd326d2f50e12de01ae64b3b0cbc232fd611e01145c39e73bb8f05c4650b53', '[\"*\"]', '2025-10-23 17:51:39', '2025-10-30 17:31:38', '2025-10-23 17:31:38', '2025-10-23 17:51:39'),
(129, 'App\\Models\\User', 4, 'auth-token', '319859133f5070fa9d1628d888cf1c20febe75ac685c05bd8978cba89cc13c77', '[\"*\"]', '2025-10-23 17:56:20', '2025-10-30 17:56:19', '2025-10-23 17:56:19', '2025-10-23 17:56:20'),
(130, 'App\\Models\\User', 4, 'auth-token', 'c0f8766aa07b1b6b077277a5946a17065dc17b5299a346dc9061f180668e7bf9', '[\"*\"]', '2025-10-23 18:17:37', '2025-10-30 18:17:36', '2025-10-23 18:17:36', '2025-10-23 18:17:37'),
(131, 'App\\Models\\User', 4, 'auth-token', 'db5211c2ae7e9b0d4050c6670abad4b1a376ea934925a7cadde7cc8e6001ab73', '[\"*\"]', '2025-10-24 22:33:44', '2025-10-31 22:30:47', '2025-10-24 22:30:47', '2025-10-24 22:33:44'),
(132, 'App\\Models\\User', 4, 'auth-token', '9061675f3d801b9874c44d1ff9799e61d488ad708861c7fa95400d1d0ac61d0f', '[\"*\"]', '2025-10-24 23:00:12', '2025-10-31 22:58:09', '2025-10-24 22:58:09', '2025-10-24 23:00:12'),
(133, 'App\\Models\\User', 4, 'auth-token', '420f489c88ef57279a3350f2e2aa68dad790353f4d5705f28f6b5f5cb023e262', '[\"*\"]', '2025-10-25 20:18:34', '2025-11-01 20:14:01', '2025-10-25 20:14:01', '2025-10-25 20:18:34'),
(134, 'App\\Models\\User', 4, 'auth-token', '19b934511d5b6feb4f7198aeafe1c845bd284261f253f624b98b777afea67753', '[\"*\"]', '2025-10-26 06:20:23', '2025-11-02 06:20:14', '2025-10-26 06:20:14', '2025-10-26 06:20:23'),
(135, 'App\\Models\\User', 4, 'auth-token', 'bc5c867307c1c0a8cbf5900ec3aec9a1f23d4c29fba09049d088bcfdaf50e9b3', '[\"*\"]', '2025-10-27 11:40:34', '2025-11-03 11:37:51', '2025-10-27 11:37:51', '2025-10-27 11:40:34'),
(136, 'App\\Models\\User', 4, 'auth-token', 'a7cd39b9bbd384cc93ea1ba17ebaa068b3948df8b295c7074ec18fcff2f9f9ec', '[\"*\"]', '2025-10-27 11:41:00', '2025-11-03 11:40:59', '2025-10-27 11:40:59', '2025-10-27 11:41:00'),
(137, 'App\\Models\\User', 4, 'auth-token', '6883ee25c6919196fc5b32c9cead20c1b7e7758f110494ebafded5d6dc068fde', '[\"*\"]', '2025-10-28 09:14:10', '2025-11-04 09:09:22', '2025-10-28 09:09:22', '2025-10-28 09:14:10'),
(138, 'App\\Models\\User', 4, 'auth-token', '7a8aa916db93387048062e71fa3c10de86ba915e72797e470d7ae866347bbfad', '[\"*\"]', '2025-10-28 09:53:00', '2025-11-04 09:23:58', '2025-10-28 09:23:58', '2025-10-28 09:53:00'),
(139, 'App\\Models\\User', 4, 'auth-token', '9d6096d4263bbc8b66df43aa766d874f4ebd03ef0c1e0211e3eaac25163ea277', '[\"*\"]', '2025-10-28 10:32:15', '2025-11-04 09:53:59', '2025-10-28 09:53:59', '2025-10-28 10:32:15'),
(140, 'App\\Models\\User', 4, 'auth-token', 'cff61e508498e11fea6bda248664b80caa48e139224fe9353cd4e499fb7d9f20', '[\"*\"]', '2025-10-28 11:07:47', '2025-11-04 11:07:35', '2025-10-28 11:07:35', '2025-10-28 11:07:47'),
(141, 'App\\Models\\User', 4, 'auth-token', '54c46429e59678420ab5809ddb8b1ea23dd0686c830ddfae58fcdf191c2b702a', '[\"*\"]', '2025-10-28 11:11:35', '2025-11-04 11:11:31', '2025-10-28 11:11:31', '2025-10-28 11:11:35'),
(142, 'App\\Models\\User', 4, 'auth-token', 'b68c2cef27a6ed783b9af4cb8f4b26b3f31f5c70e59f7a7245938d1ac3713ca6', '[\"*\"]', '2025-10-28 11:17:52', '2025-11-04 11:17:48', '2025-10-28 11:17:48', '2025-10-28 11:17:52'),
(143, 'App\\Models\\User', 4, 'auth-token', '874119c72b14e6c3213067e98649a85835b6799f096c1b4ad422939f08504fd1', '[\"*\"]', '2025-10-28 12:43:08', '2025-11-04 11:46:58', '2025-10-28 11:46:58', '2025-10-28 12:43:08'),
(144, 'App\\Models\\User', 4, 'auth-token', '337f5142929e5f9eec66f163d5d471148760d1c14dc9bfb47502e974a79f7a97', '[\"*\"]', '2025-11-03 16:47:23', '2025-11-04 11:55:19', '2025-10-28 11:55:19', '2025-11-03 16:47:23'),
(145, 'App\\Models\\User', 4, 'auth-token', '86af09281a139f9a2b94e8716ba310c2b3f2234ddc89d1f6b757b08a1e096c3a', '[\"*\"]', '2025-10-28 12:49:03', '2025-11-04 12:48:07', '2025-10-28 12:48:07', '2025-10-28 12:49:03'),
(146, 'App\\Models\\User', 4, 'auth-token', '1988682c42708352be4fbdfe49f95d31d39b713dfa5814a958ea05f465564a39', '[\"*\"]', '2025-10-28 13:18:35', '2025-11-04 12:55:01', '2025-10-28 12:55:01', '2025-10-28 13:18:35'),
(147, 'App\\Models\\User', 4, 'auth-token', '23dcd652a0c8c37ffe75a920b63c86be30e84e6ede44da4c2741a4735bbe0dfd', '[\"*\"]', '2025-10-28 13:31:19', '2025-11-04 13:11:49', '2025-10-28 13:11:49', '2025-10-28 13:31:19'),
(148, 'App\\Models\\User', 4, 'auth-token', '3e978f073e6f5ca5625f6f1eaad2f9d2a23804231b7725a0d7db56dac4ad26c0', '[\"*\"]', '2025-10-28 14:04:26', '2025-11-04 13:43:23', '2025-10-28 13:43:23', '2025-10-28 14:04:26'),
(149, 'App\\Models\\User', 4, 'auth-token', '98db929c768a8341cfb9284bc40be2cee0120664553c1163d2687d74719b557a', '[\"*\"]', '2025-10-28 14:19:41', '2025-11-04 14:08:35', '2025-10-28 14:08:35', '2025-10-28 14:19:41'),
(150, 'App\\Models\\User', 4, 'auth-token', 'e82221ac71b9394ea2e8f00744c587f37d30bf6df9c9902e524195804d9c540e', '[\"*\"]', '2025-10-28 14:49:49', '2025-11-04 14:20:18', '2025-10-28 14:20:18', '2025-10-28 14:49:49'),
(151, 'App\\Models\\User', 4, 'auth-token', '5b5b12e282d4b79ff86836e96042bb8307d817d3ae3d235e8404e05d726c000d', '[\"*\"]', '2025-10-28 16:17:48', '2025-11-04 15:22:52', '2025-10-28 15:22:52', '2025-10-28 16:17:48'),
(152, 'App\\Models\\User', 4, 'auth-token', '3ea7a13202e891896bbcc55f03cc16f81079b3968265b737ac926ae9aee302be', '[\"*\"]', '2025-10-28 16:30:26', '2025-11-04 16:20:50', '2025-10-28 16:20:50', '2025-10-28 16:30:26'),
(153, 'App\\Models\\User', 4, 'auth-token', 'b644a96d7d0d9fd83d229be1e9402d18f8eee4910910906e0925ddbeb26dfdc1', '[\"*\"]', '2025-10-28 16:37:55', '2025-11-04 16:31:35', '2025-10-28 16:31:35', '2025-10-28 16:37:55'),
(154, 'App\\Models\\User', 4, 'auth-token', 'ce1dec4d680d1aea0c09db6a57e33995c76a4aa269b6b8ff8c35bc9eb6bb2482', '[\"*\"]', '2025-10-28 16:58:33', '2025-11-04 16:53:41', '2025-10-28 16:53:41', '2025-10-28 16:58:33'),
(155, 'App\\Models\\User', 4, 'auth-token', '750e682558734080e0d9bf67796b91dd44b59617c6db1b83b6d52cf48990121e', '[\"*\"]', '2025-10-28 17:11:13', '2025-11-04 17:03:39', '2025-10-28 17:03:39', '2025-10-28 17:11:13'),
(156, 'App\\Models\\User', 4, 'auth-token', 'f0d1802da42b10e80269bb099a5d168116df49b29d11aa7747b77e9c2b6ae2d0', '[\"*\"]', '2025-10-28 17:13:48', '2025-11-04 17:12:45', '2025-10-28 17:12:45', '2025-10-28 17:13:48'),
(157, 'App\\Models\\User', 4, 'auth-token', 'ea5c4569c4a8f81b9086635bb8e0eb8fc94a4df791e758cce07a5c6238e15862', '[\"*\"]', '2025-10-28 17:27:09', '2025-11-04 17:21:55', '2025-10-28 17:21:55', '2025-10-28 17:27:09'),
(158, 'App\\Models\\User', 4, 'auth-token', '82d2c4888b845a0f76d99e291fff205fad34cb6c585c881ef0c8a84763463ba0', '[\"*\"]', '2025-10-28 18:14:52', '2025-11-04 17:37:19', '2025-10-28 17:37:19', '2025-10-28 18:14:52'),
(159, 'App\\Models\\User', 4, 'auth-token', 'e34f927a8774604634e7905f011a511dfba976bf4c2de9270517356f1e3f7569', '[\"*\"]', '2025-10-28 17:45:41', '2025-11-04 17:43:41', '2025-10-28 17:43:41', '2025-10-28 17:45:41'),
(160, 'App\\Models\\User', 4, 'auth-token', 'f54f61d81b7af459f93ce677707c7365a18c8ba92cd2e3f805cb22a26adc318b', '[\"*\"]', '2025-10-28 18:48:16', '2025-11-04 18:18:49', '2025-10-28 18:18:49', '2025-10-28 18:48:16'),
(161, 'App\\Models\\User', 4, 'auth-token', 'f3507c98e108b7685144d6f4096c7d32510438307e7ca9fb2cf852f012e4a8c7', '[\"*\"]', '2025-10-28 19:48:26', '2025-11-04 19:30:09', '2025-10-28 19:30:09', '2025-10-28 19:48:26'),
(162, 'App\\Models\\User', 4, 'auth-token', 'ac28782d6e556c3aec9196d18d7e724f3d521c42c3d7d7aaee1dabcc076b00e9', '[\"*\"]', '2025-11-02 15:49:31', '2025-11-04 19:42:23', '2025-10-28 19:42:23', '2025-11-02 15:49:31'),
(163, 'App\\Models\\User', 4, 'auth-token', 'f390f5292ad64f9f700bfc320cbcf8d14c51dd51c080ed54059ec6678aadf242', '[\"*\"]', '2025-10-28 19:49:58', '2025-11-04 19:49:43', '2025-10-28 19:49:43', '2025-10-28 19:49:58'),
(164, 'App\\Models\\User', 4, 'auth-token', '6d7bd19da7a9ad08cf7446c0f5607311f7fc3271a5706c0c63abd50c644eba39', '[\"*\"]', '2025-10-29 08:37:28', '2025-11-04 19:59:38', '2025-10-28 19:59:38', '2025-10-29 08:37:28'),
(165, 'App\\Models\\User', 4, 'auth-token', 'd05cb0c4d922a0bc14ef47e733cbb5b333830cd9c3d3db42e2a1046d6aeeda8d', '[\"*\"]', '2025-10-29 09:50:21', '2025-11-05 08:38:25', '2025-10-29 08:38:25', '2025-10-29 09:50:21'),
(166, 'App\\Models\\User', 4, 'auth-token', '061a4120018cc00ff8b09519322ecf70a9d2f7b832bb15a7448295d0e5226c8f', '[\"*\"]', '2025-10-29 10:21:59', '2025-11-05 09:54:20', '2025-10-29 09:54:20', '2025-10-29 10:21:59'),
(167, 'App\\Models\\User', 4, 'auth-token', 'd058fbc2c6abd24a9a458f901540affa3ef41534e4f6ec21c2c382a90d0d0c2c', '[\"*\"]', '2025-10-29 10:29:31', '2025-11-05 10:27:00', '2025-10-29 10:27:00', '2025-10-29 10:29:31'),
(168, 'App\\Models\\User', 4, 'auth-token', 'b26994c6f28d26115986efb73f8a4292e66a387ba0f02ab23b04f31295ef353f', '[\"*\"]', '2025-10-29 11:05:39', '2025-11-05 10:43:45', '2025-10-29 10:43:45', '2025-10-29 11:05:39'),
(169, 'App\\Models\\User', 4, 'auth-token', 'f1bace6d11861eb3472663c8559a7e05d8a812a4c548ddfa64a3c9cd010e6d81', '[\"*\"]', '2025-10-29 14:48:58', '2025-11-05 14:46:38', '2025-10-29 14:46:38', '2025-10-29 14:48:58'),
(170, 'App\\Models\\User', 4, 'auth-token', '02857ad0acea46c3f2ed25084cb15a4879fe513b20d6729a657d010bf8a9a1f0', '[\"*\"]', '2025-10-29 15:24:23', '2025-11-05 15:14:53', '2025-10-29 15:14:53', '2025-10-29 15:24:23'),
(171, 'App\\Models\\User', 4, 'auth-token', '08116d4656bbe71e71ccd382fd16271eefce81f41b6acd13b0a78a327d43c733', '[\"*\"]', '2025-10-29 16:32:00', '2025-11-05 15:29:42', '2025-10-29 15:29:42', '2025-10-29 16:32:00'),
(172, 'App\\Models\\User', 4, 'auth-token', '543b26859dd236acd7e0a721abf692398cda7af1c004e5a0f6d9ccb4c2fc36b9', '[\"*\"]', '2025-10-29 22:05:19', '2025-11-05 21:59:10', '2025-10-29 21:59:10', '2025-10-29 22:05:19'),
(173, 'App\\Models\\User', 4, 'auth-token', '5e6aca7ae50c701c8442b5f449ea9414d3bbe582c94de43d5200c1f1d0eb7317', '[\"*\"]', '2025-10-29 22:08:15', '2025-11-05 22:08:13', '2025-10-29 22:08:13', '2025-10-29 22:08:15'),
(174, 'App\\Models\\User', 4, 'auth-token', '15ea31b10b034989782f542d0e5f4c7068246974de6672bae2691b8781626196', '[\"*\"]', '2025-10-29 22:15:30', '2025-11-05 22:13:24', '2025-10-29 22:13:24', '2025-10-29 22:15:30'),
(175, 'App\\Models\\User', 4, 'auth-token', 'ac79e778eb6f5248d327f1baf8238a5c8374032f35a3dd35ef56d93a44b8d110', '[\"*\"]', '2025-10-30 01:02:56', '2025-11-06 01:02:54', '2025-10-30 01:02:54', '2025-10-30 01:02:56'),
(176, 'App\\Models\\User', 4, 'auth-token', '982a95079779e0461d949a3035dc741e6fe34d0e399ec3d371ecd9215b1055cd', '[\"*\"]', '2025-10-30 14:37:10', '2025-11-06 14:37:09', '2025-10-30 14:37:09', '2025-10-30 14:37:10'),
(177, 'App\\Models\\User', 4, 'auth-token', '2c256992ff655c37015971977a476a6468cb782296e36d2e0ff025c8e430712e', '[\"*\"]', '2025-10-30 14:44:07', '2025-11-06 14:43:59', '2025-10-30 14:43:59', '2025-10-30 14:44:07'),
(178, 'App\\Models\\User', 4, 'auth-token', 'af561a8f0bd2a8bf02217c6dfee3af97d40fae6277c5421f82f83a412cf4bdd1', '[\"*\"]', '2025-10-30 14:53:55', '2025-11-06 14:53:54', '2025-10-30 14:53:54', '2025-10-30 14:53:55'),
(179, 'App\\Models\\User', 4, 'auth-token', '08da385b0c1f574528b1285517b21c985f385251b778e17d4888cd3de8493740', '[\"*\"]', '2025-10-30 15:05:52', '2025-11-06 14:57:26', '2025-10-30 14:57:26', '2025-10-30 15:05:52'),
(180, 'App\\Models\\User', 4, 'auth-token', '57bed88ca9210af984ae999c7dd46e0a8c95727fb6949caa1170599b124bff04', '[\"*\"]', NULL, '2025-11-06 15:03:03', '2025-10-30 15:03:03', '2025-10-30 15:03:03'),
(181, 'App\\Models\\User', 4, 'auth-token', '7f1a1ad5d1935efba8feb773caae3ceabb0f3425d9f61225a35b548387e3a50c', '[\"*\"]', '2025-10-30 15:21:55', '2025-11-06 15:21:43', '2025-10-30 15:21:43', '2025-10-30 15:21:55'),
(182, 'App\\Models\\User', 4, 'auth-token', '3559376ba03b6bd224b39737b0fdb58cdc042eb7d2a39ea92d560e6a4ffe311b', '[\"*\"]', '2025-10-30 15:49:06', '2025-11-06 15:31:57', '2025-10-30 15:31:57', '2025-10-30 15:49:06'),
(183, 'App\\Models\\User', 4, 'auth-token', '78fcc5b6bdfe1575555a8360b8d521b5969c614b1e195511909ffaec510bf90d', '[\"*\"]', '2025-10-30 15:37:14', '2025-11-06 15:37:01', '2025-10-30 15:37:01', '2025-10-30 15:37:14'),
(184, 'App\\Models\\User', 4, 'auth-token', 'd5494b13b06acc269915c9b200bb6e49474c004524ae024a2460a4f06691a872', '[\"*\"]', '2025-10-30 15:54:11', '2025-11-06 15:37:57', '2025-10-30 15:37:57', '2025-10-30 15:54:11'),
(185, 'App\\Models\\User', 4, 'auth-token', '82368f25429ef9e1e08ffce9c8106ef1b7a0bec683a8c5c5bbc9f91a43503e1e', '[\"*\"]', '2025-10-30 17:39:19', '2025-11-06 16:17:03', '2025-10-30 16:17:03', '2025-10-30 17:39:19'),
(186, 'App\\Models\\User', 4, 'auth-token', 'af08e4b26948a93fa284b8f922328b5fa291d74640e86121c0744ce19416045f', '[\"*\"]', '2025-10-30 18:11:40', '2025-11-06 17:40:19', '2025-10-30 17:40:20', '2025-10-30 18:11:40'),
(187, 'App\\Models\\User', 4, 'auth-token', 'b419507c621c8d5d46bdba720b9e4ddc05ac63c41f9995d60caad7e2e1fb13bc', '[\"*\"]', '2025-10-30 18:23:23', '2025-11-06 18:13:59', '2025-10-30 18:13:59', '2025-10-30 18:23:23'),
(188, 'App\\Models\\User', 4, 'auth-token', 'd8f6869f9f038ec837a36dfe408aef611b2548164d5b57468615a04dea038cd3', '[\"*\"]', '2025-10-30 22:32:46', '2025-11-06 19:23:35', '2025-10-30 19:23:35', '2025-10-30 22:32:46'),
(189, 'App\\Models\\User', 4, 'auth-token', '1618b86a060e4484305cee513d6570047e907ef803e2ae439a64fc5028cb5593', '[\"*\"]', '2025-10-30 19:30:42', '2025-11-06 19:29:28', '2025-10-30 19:29:28', '2025-10-30 19:30:42'),
(190, 'App\\Models\\User', 4, 'auth-token', 'f65f4e07e53c2681d9f256c91e1c9bd54bf262134bf9d1980250c423936bd5f4', '[\"*\"]', '2025-10-30 20:37:25', '2025-11-06 20:35:30', '2025-10-30 20:35:30', '2025-10-30 20:37:25'),
(191, 'App\\Models\\User', 4, 'auth-token', 'd7b77dc294018e68048ad6cc155dbc64b3d72e7183156264e547afc2e32c485a', '[\"*\"]', '2025-10-30 22:39:34', '2025-11-06 22:39:33', '2025-10-30 22:39:33', '2025-10-30 22:39:34'),
(192, 'App\\Models\\User', 4, 'auth-token', '3570fa53a9d03c12f43b896fb62831ff5bfa0da909c765ae5a1693ee4b90f2bf', '[\"*\"]', '2025-10-30 22:45:33', '2025-11-06 22:45:32', '2025-10-30 22:45:32', '2025-10-30 22:45:33'),
(193, 'App\\Models\\User', 4, 'auth-token', '6fbf0c510e4ef1e5097723c018cdf5c61c74e0220e9e957739d649fd2bfce8db', '[\"*\"]', '2025-10-30 22:51:06', '2025-11-06 22:48:11', '2025-10-30 22:48:11', '2025-10-30 22:51:06'),
(194, 'App\\Models\\User', 4, 'auth-token', '5f832aa449e6a33ac74ae61a3cdc4303dbacd1585df8e287db064bd7f4283c6b', '[\"*\"]', '2025-10-31 15:06:27', '2025-11-06 22:54:06', '2025-10-30 22:54:06', '2025-10-31 15:06:27'),
(195, 'App\\Models\\User', 4, 'auth-token', '309e066b66d22ac55b5ea6457c279e10885273fe76b2ef283d727b596f257a19', '[\"*\"]', '2025-10-31 01:55:34', '2025-11-07 00:23:03', '2025-10-31 00:23:03', '2025-10-31 01:55:34'),
(196, 'App\\Models\\User', 4, 'auth-token', 'fb8645ea5a62ba29c80f6e2117af5ca54a72687c6131a701188bf1d0e0fba134', '[\"*\"]', '2025-10-31 15:14:44', '2025-11-07 15:10:04', '2025-10-31 15:10:04', '2025-10-31 15:14:44'),
(197, 'App\\Models\\User', 4, 'auth-token', 'efbedf243ceae197497c848a97573a1781de24e090624bbc1b58f27243161869', '[\"*\"]', '2025-10-31 15:53:40', '2025-11-07 15:27:23', '2025-10-31 15:27:23', '2025-10-31 15:53:40'),
(198, 'App\\Models\\User', 4, 'auth-token', '9e04036b8c00f19f58d1811f7cdc4aa390b3a70f9968023cd5bc79b388b78fa3', '[\"*\"]', '2025-10-31 16:09:01', '2025-11-07 16:08:42', '2025-10-31 16:08:42', '2025-10-31 16:09:01'),
(199, 'App\\Models\\User', 4, 'auth-token', '919fe370bf5c5c2695d38390993a7e832653c799470243047e5856bcd2be8038', '[\"*\"]', '2025-10-31 16:38:32', '2025-11-07 16:12:00', '2025-10-31 16:12:00', '2025-10-31 16:38:32'),
(200, 'App\\Models\\User', 4, 'auth-token', '62c675a2476518b4eb1aa23029e0c703a18cb3c6ea3ce9ae22e7898e7eee85a1', '[\"*\"]', '2025-10-31 18:53:12', '2025-11-07 17:26:31', '2025-10-31 17:26:31', '2025-10-31 18:53:12'),
(201, 'App\\Models\\User', 4, 'auth-token', '52b5a69f463e519e1b739cc2d0b03e3d045c9c3a07381a1cba42e133702e7729', '[\"*\"]', '2025-10-31 17:31:44', '2025-11-07 17:30:12', '2025-10-31 17:30:12', '2025-10-31 17:31:44'),
(202, 'App\\Models\\User', 4, 'auth-token', '48c55d23c35beaf2a682c3913dbcde5204ac23be2931697d25dce0c1f91c0e00', '[\"*\"]', '2025-10-31 17:32:56', '2025-11-07 17:32:54', '2025-10-31 17:32:54', '2025-10-31 17:32:56'),
(203, 'App\\Models\\User', 4, 'auth-token', 'a69ae5085e270e2d5472c8a31c84d1be1c78b81bc2081408d9ae817baacab1ed', '[\"*\"]', '2025-10-31 18:20:13', '2025-11-07 18:17:17', '2025-10-31 18:17:17', '2025-10-31 18:20:13'),
(204, 'App\\Models\\User', 4, 'auth-token', '553bf887342134a6a674c1c9ee625ba487aedc7543878354498640910773b5b3', '[\"*\"]', '2025-10-31 18:54:25', '2025-11-07 18:22:06', '2025-10-31 18:22:06', '2025-10-31 18:54:25'),
(205, 'App\\Models\\User', 4, 'auth-token', '8904063a433745836a0575cb84eefe67a4630a13ef9752fbc606e8c637808e21', '[\"*\"]', '2025-10-31 20:03:37', '2025-11-07 18:55:01', '2025-10-31 18:55:01', '2025-10-31 20:03:37'),
(206, 'App\\Models\\User', 4, 'auth-token', 'e8fb3714bd37110fc70dcca33805e49c3b24323483fdf33dbc9be688bae75c31', '[\"*\"]', '2025-10-31 20:13:17', '2025-11-07 20:03:17', '2025-10-31 20:03:17', '2025-10-31 20:13:17'),
(207, 'App\\Models\\User', 4, 'auth-token', '90b3ef8892ff695befc91795c7d5ccb614aa4bac7aaad7ac81c2babe14b947b1', '[\"*\"]', '2025-10-31 22:50:59', '2025-11-07 22:48:14', '2025-10-31 22:48:14', '2025-10-31 22:50:59'),
(208, 'App\\Models\\User', 4, 'auth-token', '8f19ca243163e8915c089f30cdbec5a176e99dc0f9d410f70278a0380ffe1a4d', '[\"*\"]', '2025-10-31 23:12:30', '2025-11-07 23:05:47', '2025-10-31 23:05:47', '2025-10-31 23:12:30'),
(209, 'App\\Models\\User', 4, 'auth-token', '128228fc6d0b1c15e68fe7b916e5a7fc0e73cd0ce8da0e9aa786ab9e4bf4ab6f', '[\"*\"]', '2025-11-01 23:10:25', '2025-11-08 23:08:04', '2025-11-01 23:08:04', '2025-11-01 23:10:25'),
(210, 'App\\Models\\User', 4, 'auth-token', 'cb1a2d00304451d5cda65f29ce3103321865a8417ec625644481d9db0fe6cc56', '[\"*\"]', '2025-11-02 10:19:30', '2025-11-09 10:05:19', '2025-11-02 10:05:19', '2025-11-02 10:19:30'),
(211, 'App\\Models\\User', 4, 'auth-token', 'f75a7c96bbf5be8db4a2ada0efe7e8f9f03c30ed93724afb6feac05c4342a3cf', '[\"*\"]', '2025-11-02 10:45:16', '2025-11-09 10:33:50', '2025-11-02 10:33:50', '2025-11-02 10:45:16'),
(212, 'App\\Models\\User', 4, 'auth-token', 'a423e281a9f7ae8d081676d9e706ad11187cf2d6a63f5260e5f63e2d6dda176a', '[\"*\"]', '2025-11-02 10:47:54', '2025-11-09 10:47:39', '2025-11-02 10:47:39', '2025-11-02 10:47:54'),
(213, 'App\\Models\\User', 4, 'auth-token', 'a76a07a560b7725adfd03fc2319d9c8e82f824bc9b9568f18c5cba003407d6eb', '[\"*\"]', '2025-11-02 11:02:05', '2025-11-09 10:54:50', '2025-11-02 10:54:50', '2025-11-02 11:02:05'),
(214, 'App\\Models\\User', 4, 'auth-token', '6ee3aea0d1ea597ae5411522338ef0f1a933f9f56a3bc196fd2956dc5e8365da', '[\"*\"]', '2025-11-02 12:59:18', '2025-11-09 11:10:23', '2025-11-02 11:10:23', '2025-11-02 12:59:18'),
(215, 'App\\Models\\User', 4, 'auth-token', '085e327580e74569fab258d269825fa4a732f129f06032a66928b473f60a3aea', '[\"*\"]', '2025-11-02 12:36:03', '2025-11-09 12:12:18', '2025-11-02 12:12:18', '2025-11-02 12:36:03'),
(216, 'App\\Models\\User', 5, 'auth-token', 'ebda9f3665cca92b8a3dc481cc38b17b89e8077dffb21c3f9d4f4857519151e2', '[\"*\"]', NULL, '2025-11-09 13:07:21', '2025-11-02 13:07:21', '2025-11-02 13:07:21'),
(217, 'App\\Models\\User', 4, 'auth-token', 'c22a1618d1c4185f56070f933ec6d139a6d622c6cf6dea5e2be390d47ffa3c14', '[\"*\"]', '2025-11-02 14:07:14', '2025-11-09 13:32:32', '2025-11-02 13:32:32', '2025-11-02 14:07:14'),
(218, 'App\\Models\\User', 4, 'auth-token', '04bccb9dae0c81c0606d57ed29fb4b92d2766b3c8b81bdfdc92b32ea0d67e65f', '[\"*\"]', '2025-11-03 10:03:07', '2025-11-09 14:08:19', '2025-11-02 14:08:19', '2025-11-03 10:03:07'),
(219, 'App\\Models\\User', 4, 'auth-token', 'f23bd74cc7e88ed8f784bdff69d5918ddd39edf7c336cc1be9c3546022983bb7', '[\"*\"]', '2025-11-03 10:08:13', '2025-11-10 10:08:01', '2025-11-03 10:08:01', '2025-11-03 10:08:13'),
(220, 'App\\Models\\User', 5, 'auth-token', '58a2eae9a97bce617ceb3a315ff11ba28d598c17f4937c41ae0afd4052ae348a', '[\"*\"]', '2025-11-03 10:39:59', '2025-11-10 10:34:38', '2025-11-03 10:34:38', '2025-11-03 10:39:59'),
(221, 'App\\Models\\User', 5, 'auth-token', 'd197332f0b1cafb328eadf5787561da4c5ff5617bd39937c96741c486ac316c1', '[\"*\"]', '2025-11-03 11:25:29', '2025-11-10 11:05:23', '2025-11-03 11:05:23', '2025-11-03 11:25:29'),
(222, 'App\\Models\\User', 4, 'auth-token', '352420054f82b2bb2312f1f4ce8b623f03f2df4c53470ff53c549d8fb2e0c9ba', '[\"*\"]', '2025-11-03 11:08:15', '2025-11-10 11:08:06', '2025-11-03 11:08:06', '2025-11-03 11:08:15'),
(223, 'App\\Models\\User', 5, 'auth-token', '0746a8304468e6d1ab4715e9b04f21df79521dfed8c31a846b5490b0ed1618e0', '[\"*\"]', '2025-11-03 11:30:25', '2025-11-10 11:08:36', '2025-11-03 11:08:36', '2025-11-03 11:30:25'),
(224, 'App\\Models\\User', 4, 'auth-token', 'c71213b2236009742c8bef7c5f42438368c9d78fa6337f29a0f2c4a69bd2a612', '[\"*\"]', '2025-11-03 11:31:02', '2025-11-10 11:30:59', '2025-11-03 11:30:59', '2025-11-03 11:31:02'),
(225, 'App\\Models\\User', 5, 'auth-token', '30262e7707ac635502e3a4734c9a13cf2c7feabd2cbc28410792a83732c1b0f3', '[\"*\"]', '2025-11-03 12:12:01', '2025-11-10 11:42:40', '2025-11-03 11:42:40', '2025-11-03 12:12:01'),
(226, 'App\\Models\\User', 5, 'auth-token', '23475ee48067aed9d2372a35036df5099fd90a38b3c5e33050b8628bfd120b68', '[\"*\"]', '2025-11-03 12:17:29', '2025-11-10 12:12:54', '2025-11-03 12:12:54', '2025-11-03 12:17:29'),
(227, 'App\\Models\\User', 5, 'auth-token', 'b2aa27867eb388b7b93b9e7b33d1f2ba6cc94ee8c2a5c3a68a39faa9cdebfb78', '[\"*\"]', '2025-11-03 15:53:53', '2025-11-10 12:19:04', '2025-11-03 12:19:04', '2025-11-03 15:53:53'),
(228, 'App\\Models\\User', 53, 'auth-token', '4db58e4d8726701457bca4cdd9708d55cbcda1915bcedfd7c91a6f9e8f318765', '[\"*\"]', '2025-11-03 16:07:23', '2025-11-10 16:01:49', '2025-11-03 16:01:49', '2025-11-03 16:07:23'),
(229, 'App\\Models\\User', 5, 'auth-token', '83ee1e084e4e243bd18b0b8b34264d5dad251344c3e613007fe2e36a7bf83502', '[\"*\"]', '2025-11-03 18:23:58', '2025-11-10 16:07:48', '2025-11-03 16:07:48', '2025-11-03 18:23:58'),
(230, 'App\\Models\\User', 5, 'auth-token', '6dcf36d4dcc0dd89f81f84b5725198a332b33d5fdba814bd610c86fdbe0045f7', '[\"*\"]', '2025-11-04 00:00:47', '2025-11-10 16:47:44', '2025-11-03 16:47:44', '2025-11-04 00:00:47'),
(231, 'App\\Models\\User', 4, 'auth-token', '438282bbd60cadc7bb29bf5127aeffd133c9b441e05386aeb1521275b44cfb70', '[\"*\"]', '2025-11-03 19:16:49', '2025-11-10 18:28:11', '2025-11-03 18:28:11', '2025-11-03 19:16:49'),
(232, 'App\\Models\\User', 5, 'auth-token', '4288674f3a4fe460b07fdf35a8d9f6115f9a8e109afb2a9b5383615eb62f7f66', '[\"*\"]', '2025-11-03 23:15:23', '2025-11-10 19:26:22', '2025-11-03 19:26:22', '2025-11-03 23:15:23'),
(233, 'App\\Models\\User', 5, 'auth-token', '92dc8c4586cff5c4359a066a2e4ebe204fb4841193e95db1c9cff95c1a49eca2', '[\"*\"]', '2025-11-03 23:42:43', '2025-11-10 23:38:00', '2025-11-03 23:38:00', '2025-11-03 23:42:43'),
(234, 'App\\Models\\User', 4, 'auth-token', 'a642c5a00409c0be5a0a5df6962de6a50bdfd09a0cf093889c0a8cd0c1ec01d3', '[\"*\"]', '2025-11-03 23:46:46', '2025-11-10 23:46:32', '2025-11-03 23:46:32', '2025-11-03 23:46:46'),
(235, 'App\\Models\\User', 53, 'auth-token', '03420b8b24862e76dd8ec992e0df131a2c8eb5f6b6ad4bdf29ae38c688ba1990', '[\"*\"]', '2025-11-03 23:50:48', '2025-11-10 23:47:24', '2025-11-03 23:47:24', '2025-11-03 23:50:48'),
(236, 'App\\Models\\User', 5, 'auth-token', '1997834e7af569288f52b15f6c9ab01e65e489876200c780fcdd05dafd34552c', '[\"*\"]', '2025-11-04 00:23:33', '2025-11-10 23:56:45', '2025-11-03 23:56:45', '2025-11-04 00:23:33'),
(237, 'App\\Models\\User', 4, 'auth-token', '562d59f3fd24780e1fa6bf258f248a57dba85bde2f7883a5ffc1791aced3888a', '[\"*\"]', '2025-11-04 00:27:22', '2025-11-11 00:26:27', '2025-11-04 00:26:27', '2025-11-04 00:27:22'),
(238, 'App\\Models\\User', 53, 'auth-token', 'a0a959a753035f1779e53ed13224c7ab8ecc98d7da7fe4dfb807ebc67bbe323c', '[\"*\"]', '2025-11-04 00:29:47', '2025-11-11 00:28:49', '2025-11-04 00:28:49', '2025-11-04 00:29:47'),
(239, 'App\\Models\\User', 5, 'auth-token', '09d5a964a5601479ad6143edbf5fee80b0c28288f2379d1d93fb5be1c5adf6d1', '[\"*\"]', '2025-11-04 01:27:01', '2025-11-11 00:30:04', '2025-11-04 00:30:04', '2025-11-04 01:27:01'),
(240, 'App\\Models\\User', 5, 'auth-token', '1dbb1f927e304dffd1e13ab179b2904ec1a4ae95c37144aa38ca69432b0064f0', '[\"*\"]', '2025-11-04 01:28:14', '2025-11-11 01:28:14', '2025-11-04 01:28:14', '2025-11-04 01:28:14'),
(241, 'App\\Models\\User', 5, 'auth-token', 'c2bdb97fb393ddef39639a73d8ba665bd6ef64443c5c5bf8ed75968aa57f21d1', '[\"*\"]', '2025-11-04 02:03:20', '2025-11-11 01:33:45', '2025-11-04 01:33:45', '2025-11-04 02:03:20'),
(242, 'App\\Models\\User', 5, 'auth-token', 'f01690501dc90f26d9434d79fb25e2271ec98b89676fca9322b450c6e25d316c', '[\"*\"]', '2025-11-04 02:25:17', '2025-11-11 02:25:17', '2025-11-04 02:25:17', '2025-11-04 02:25:17'),
(243, 'App\\Models\\User', 5, 'auth-token', 'd3502b8da4a452775866b153f0de445a59dd00f458b3bf88b6a3eb072df3e0f5', '[\"*\"]', '2025-11-04 02:33:23', '2025-11-11 02:32:05', '2025-11-04 02:32:05', '2025-11-04 02:33:23'),
(244, 'App\\Models\\User', 5, 'auth-token', 'ce9b11af7cd03cefb99f59a4a21024bced48e3d03fcc1a3fa53e82831462a839', '[\"*\"]', '2025-11-04 12:05:15', '2025-11-11 11:06:35', '2025-11-04 11:06:35', '2025-11-04 12:05:15'),
(245, 'App\\Models\\User', 5, 'auth-token', 'c5a5342dd2ab5c43eda1e4abfad0f8cda4d64018e47694179c5f63ba7b885710', '[\"*\"]', '2025-11-04 13:49:44', '2025-11-11 13:48:41', '2025-11-04 13:48:41', '2025-11-04 13:49:44');
INSERT INTO `personal_access_tokens` (`id`, `tokenable_type`, `tokenable_id`, `name`, `token`, `abilities`, `last_used_at`, `expires_at`, `created_at`, `updated_at`) VALUES
(246, 'App\\Models\\User', 5, 'auth-token', 'd14ddc890440d000e4c30b14576ae96b5e03c6b072fb0034ec6b820a967cec62', '[\"*\"]', '2025-11-04 13:51:50', '2025-11-11 13:51:49', '2025-11-04 13:51:49', '2025-11-04 13:51:50'),
(247, 'App\\Models\\User', 5, 'auth-token', 'd13ad70070700e3e5ffae74d2a3aea531e8c54d3a36d98d3af56a3fed97ffff0', '[\"*\"]', '2025-11-04 13:52:59', '2025-11-11 13:52:58', '2025-11-04 13:52:58', '2025-11-04 13:52:59'),
(248, 'App\\Models\\User', 5, 'auth-token', '71e3b3587dca11f2dcc62fedd338caf8003ad4478dbd5b7949ab543a0682654b', '[\"*\"]', '2025-11-04 16:09:36', '2025-11-11 13:57:37', '2025-11-04 13:57:37', '2025-11-04 16:09:36'),
(249, 'App\\Models\\User', 5, 'auth-token', 'e7195f5433dbce95023859045baccebc58504db0bf31811eff31b57e63494a99', '[\"*\"]', '2025-11-04 19:00:28', '2025-11-11 19:00:05', '2025-11-04 19:00:05', '2025-11-04 19:00:28'),
(250, 'App\\Models\\User', 5, 'auth-token', '4f66dc1382510930ed79b02603914aae884a190c1940b54e0a24adb2c7edc847', '[\"*\"]', '2025-11-05 01:48:14', '2025-11-11 19:07:06', '2025-11-04 19:07:06', '2025-11-05 01:48:14'),
(251, 'App\\Models\\User', 5, 'auth-token', 'b0ba84df8c83e4436469b008257b71226b367af9136af6f7862ee420af8f16ce', '[\"*\"]', '2025-11-05 02:40:06', '2025-11-12 02:00:26', '2025-11-05 02:00:26', '2025-11-05 02:40:06'),
(252, 'App\\Models\\User', 5, 'auth-token', '7b9c1da76c43f824eb68ad45ea790acf082c9003eda9969689168cb663513b68', '[\"*\"]', '2025-11-05 03:17:43', '2025-11-12 02:50:04', '2025-11-05 02:50:04', '2025-11-05 03:17:43'),
(253, 'App\\Models\\User', 5, 'auth-token', '2095875dcdc9d1bb20377bda95a514822dfeae6ec5df84d08e56f1bc48748ae4', '[\"*\"]', '2025-11-05 12:18:50', '2025-11-12 08:47:04', '2025-11-05 08:47:04', '2025-11-05 12:18:50'),
(254, 'App\\Models\\User', 5, 'auth-token', 'bb417cf6e98a327dc72ee729841b0d8add28162d78c905c9d4d69fe06564d9a5', '[\"*\"]', '2025-11-05 19:02:10', '2025-11-12 19:02:10', '2025-11-05 19:02:10', '2025-11-05 19:02:10');

-- --------------------------------------------------------

--
-- Table structure for table `progress_notes`
--

CREATE TABLE `progress_notes` (
  `id` bigint UNSIGNED NOT NULL,
  `patient_id` bigint UNSIGNED NOT NULL,
  `nurse_id` bigint UNSIGNED NOT NULL,
  `visit_date` date NOT NULL,
  `visit_time` time NOT NULL,
  `vitals` json DEFAULT NULL,
  `interventions` json DEFAULT NULL,
  `general_condition` enum('improved','stable','deteriorating') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `pain_level` int NOT NULL DEFAULT '0' COMMENT 'Pain scale 0-10',
  `wound_status` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `other_observations` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `education_provided` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `family_concerns` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `next_steps` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `signed_at` timestamp NULL DEFAULT NULL,
  `signature_method` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'digital',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `progress_notes`
--

INSERT INTO `progress_notes` (`id`, `patient_id`, `nurse_id`, `visit_date`, `visit_time`, `vitals`, `interventions`, `general_condition`, `pain_level`, `wound_status`, `other_observations`, `education_provided`, `family_concerns`, `next_steps`, `signed_at`, `signature_method`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 5, 4, '2025-10-07', '13:10:00', '{\"spo2\": 98, \"pulse\": 72, \"respiration\": 16, \"temperature\": 36.5, \"blood_pressure\": \"120/80\"}', '{\"other\": false, \"wound_care\": true, \"hygiene_care\": false, \"other_details\": null, \"physiotherapy\": true, \"hygiene_details\": null, \"nutrition_details\": null, \"nutrition_support\": false, \"counseling_details\": null, \"medication_details\": \"Paracetamol\", \"wound_care_details\": null, \"counseling_education\": false, \"physiotherapy_details\": null, \"medication_administered\": true}', 'stable', 9, 'No wound', 'Nothing observed', 'Family eduacated on how to administer the drugs every morning at 8am', 'Sleepless night', 'Follow up on vitals', '2025-10-07 13:12:35', 'digital', '2025-10-07 13:12:35', '2025-10-16 19:10:46', '2025-10-16 19:10:46'),
(2, 5, 4, '2025-10-07', '17:59:00', '{\"spo2\": 98, \"pulse\": 72, \"respiration\": 19, \"temperature\": 36, \"blood_pressure\": \"122/82\"}', '{\"other\": false, \"wound_care\": true, \"hygiene_care\": true, \"other_details\": null, \"physiotherapy\": true, \"hygiene_details\": null, \"nutrition_details\": null, \"nutrition_support\": true, \"counseling_details\": null, \"medication_details\": \"paracetamol adminstered\", \"wound_care_details\": null, \"counseling_education\": false, \"physiotherapy_details\": null, \"medication_administered\": true}', 'improved', 8, 'There are no wounds recorded', 'Patient bleeds in the nose', 'Parents of the patients have been educated on how to administer drugs every morning at 8am', 'Patient night rest is improving', 'Make sure patient health has improved', '2025-10-07 18:02:20', 'digital', '2025-10-07 18:02:20', '2025-10-16 19:10:42', '2025-10-16 19:10:42'),
(6, 82, 4, '2025-10-16', '19:10:00', '{\"spo2\": 98, \"pulse\": 72, \"respiration\": 16, \"temperature\": 30, \"blood_pressure\": \"120/80\"}', '{\"other\": false, \"wound_care\": true, \"hygiene_care\": false, \"other_details\": null, \"physiotherapy\": false, \"hygiene_details\": null, \"nutrition_details\": null, \"nutrition_support\": false, \"counseling_details\": null, \"medication_details\": null, \"wound_care_details\": null, \"counseling_education\": false, \"physiotherapy_details\": null, \"medication_administered\": true}', 'stable', 7, NULL, NULL, NULL, NULL, NULL, '2025-10-16 19:11:13', 'digital', '2025-10-16 19:11:13', '2025-10-16 19:11:13', NULL),
(7, 39, 55, '2025-08-17', '12:20:00', '{\"spo2\": 97, \"pulse\": 108, \"respiration\": 25, \"temperature\": 35.7, \"blood_pressure\": \"126/80\"}', '{\"other\": true, \"other_details\": \"Coordinated with family members regarding care schedule\", \"physiotherapy\": true, \"counseling_education\": true, \"physiotherapy_details\": \"Breathing exercises completed to improve lung capacity\", \"medication_administered\": true, \"counseling_education_details\": \"Discussed importance of regular exercise and diet\", \"medication_administered_details\": \"Administered prescribed medications as per care plan\"}', 'deteriorating', 6, 'Minor abrasion on left elbow, cleaned and dressed, healing normally', 'Pain levels increasing despite medication adjustments', NULL, 'Family concerned about patient\'s decreased appetite', 'Notify family of changes and discuss care plan adjustments', '2025-10-16 19:14:42', 'digital', '2025-08-17 20:14:42', '2025-08-17 20:14:42', NULL),
(8, 24, 55, '2025-10-10', '09:49:00', '{\"spo2\": 92, \"pulse\": 86, \"respiration\": 13, \"temperature\": 37.8, \"blood_pressure\": \"121/86\"}', '{\"wound_care\": true, \"wound_care_details\": \"Cleaned and dressed surgical wound on right leg\", \"counseling_education\": true, \"medication_administered\": true, \"counseling_education_details\": \"Educated family on signs of complications to watch for\", \"medication_administered_details\": \"Administered prescribed medications as per care plan\"}', 'deteriorating', 5, NULL, 'Increased confusion noted, patient less responsive to verbal cues', 'Reviewed medication schedule and importance of adherence', 'Family requesting additional support services or respite care', 'Increase frequency of nursing visits for closer monitoring', '2025-10-16 19:14:42', 'digital', '2025-10-10 21:14:42', '2025-10-10 21:14:42', NULL),
(9, 47, 66, '2025-10-14', '15:26:00', '{\"spo2\": 94, \"pulse\": 73, \"respiration\": 18, \"temperature\": 38.1, \"blood_pressure\": \"137/76\"}', '{\"other\": true, \"hygiene_care\": true, \"other_details\": \"Emotional support provided during difficult period\", \"hygiene_care_details\": \"Oral care completed - teeth brushed and mouth rinsed\"}', 'deteriorating', 5, 'Pressure ulcer on sacrum - Stage 2, measuring 3cm x 2cm, showing improvement', 'Increased confusion noted, patient less responsive to verbal cues', NULL, NULL, 'Increase frequency of nursing visits for closer monitoring', '2025-10-16 19:14:42', 'digital', '2025-10-14 21:14:42', '2025-10-14 21:14:42', NULL),
(10, 8, 55, '2025-09-09', '13:03:00', '{\"spo2\": 92, \"pulse\": 56, \"respiration\": 25, \"temperature\": 37.3, \"blood_pressure\": \"126/88\"}', '{\"counseling_education\": true, \"medication_administered\": true, \"counseling_education_details\": \"Discussed importance of regular exercise and diet\", \"medication_administered_details\": \"Administered prescribed medications as per care plan\"}', 'improved', 3, NULL, 'Patient expressing positive outlook and improved mood', 'Provided information on nutrition and hydration requirements', 'Concerns raised about patient\'s mental state and mood changes', 'Continue current medication regimen and monitor progress', '2025-10-16 19:14:42', 'digital', '2025-09-09 22:14:42', '2025-09-09 22:14:42', NULL),
(11, 22, 54, '2025-09-09', '17:43:00', '{\"spo2\": 93, \"pulse\": 62, \"respiration\": 24, \"temperature\": 36, \"blood_pressure\": \"105/65\"}', '{\"other\": true, \"other_details\": \"Assisted with household chores and light cleaning\", \"physiotherapy\": true, \"physiotherapy_details\": \"Assisted with range of motion exercises for 30 minutes\"}', 'improved', 2, NULL, 'Patient expressing positive outlook and improved mood', 'Explained exercises to be performed between nursing visits', NULL, 'Schedule follow-up visit in 3 days to reassess condition', '2025-10-16 19:14:42', 'digital', '2025-09-09 20:14:42', '2025-09-09 20:14:42', NULL),
(12, 30, 79, '2025-08-19', '08:55:00', '{\"spo2\": 100, \"pulse\": 92, \"respiration\": 16, \"temperature\": 37.7, \"blood_pressure\": \"115/71\"}', '{\"physiotherapy\": true, \"counseling_education\": true, \"physiotherapy_details\": \"Walking exercise completed - patient walked 50 meters with walker\", \"counseling_education_details\": \"Reviewed wound care instructions with patient\"}', 'deteriorating', 10, NULL, 'Increased confusion noted, patient less responsive to verbal cues', NULL, 'Family concerned about patient\'s decreased appetite', 'Notify family of changes and discuss care plan adjustments', '2025-10-16 19:14:42', 'digital', '2025-08-19 22:14:42', '2025-08-19 22:14:42', NULL),
(13, 82, 57, '2025-08-29', '09:21:00', '{\"spo2\": 93, \"pulse\": 110, \"respiration\": 21, \"temperature\": 38.1, \"blood_pressure\": \"107/63\"}', '{\"wound_care\": true, \"wound_care_details\": \"Cleaned and dressed surgical wound on right leg\", \"counseling_education\": true, \"counseling_education_details\": \"Educated family on signs of complications to watch for\"}', 'deteriorating', 8, 'Surgical wound healing well, no signs of infection, sutures intact', 'Patient appears more fatigued than previous visit', NULL, 'Concerns raised about patient\'s mental state and mood changes', 'Consider referral to specialist for further evaluation', '2025-10-16 19:14:42', 'digital', '2025-08-29 20:14:42', '2025-08-29 20:14:42', NULL),
(14, 48, 60, '2025-10-01', '12:31:00', '{\"spo2\": 94, \"pulse\": 90, \"respiration\": 17, \"temperature\": 39.5, \"blood_pressure\": \"121/86\"}', '{\"other\": true, \"other_details\": \"Emotional support provided during difficult period\", \"medication_administered\": true, \"medication_administered_details\": \"Administered prescribed medications as per care plan\"}', 'improved', 3, 'Minor abrasion on left elbow, cleaned and dressed, healing normally', 'Patient expressing positive outlook and improved mood', 'Explained exercises to be performed between nursing visits', NULL, 'Continue current medication regimen and monitor progress', '2025-10-16 19:14:42', 'digital', '2025-10-01 21:14:42', '2025-10-01 21:14:42', NULL),
(15, 16, 55, '2025-08-28', '10:20:00', '{\"spo2\": 97, \"pulse\": 58, \"respiration\": 24, \"temperature\": 38.5, \"blood_pressure\": \"120/81\"}', '{\"other\": true, \"other_details\": \"Coordinated with family members regarding care schedule\", \"nutrition_support\": true, \"counseling_education\": true, \"nutrition_support_details\": \"Assisted with meal preparation and feeding\", \"counseling_education_details\": \"Educated family on signs of complications to watch for\"}', 'improved', 2, NULL, 'Mental alertness and cognitive function noticeably better', NULL, 'Spouse questions effectiveness of current pain management', 'Consider reducing frequency of visits if improvement continues', '2025-10-16 19:14:42', 'digital', '2025-08-28 21:14:42', '2025-08-28 21:14:42', NULL),
(16, 22, 55, '2025-08-30', '08:03:00', '{\"spo2\": 98, \"pulse\": 65, \"respiration\": 14, \"temperature\": 35.9, \"blood_pressure\": \"124/75\"}', '{\"other\": true, \"wound_care\": true, \"other_details\": \"Provided companionship and social interaction\", \"physiotherapy\": true, \"wound_care_details\": \"Removed old dressing, cleaned with saline, applied new sterile dressing\", \"physiotherapy_details\": \"Mobility exercises: assisted transfers from bed to chair\", \"medication_administered\": true, \"medication_administered_details\": \"Insulin injection administered - 10 units subcutaneously\"}', 'improved', 2, 'Surgical wound healing well, no signs of infection, sutures intact', 'Pain levels decreased, patient more comfortable and resting well', NULL, 'Family concerned about patient\'s decreased appetite', 'Continue current medication regimen and monitor progress', '2025-10-16 19:14:42', 'digital', '2025-08-30 22:14:42', '2025-08-30 22:14:42', NULL),
(17, 26, 58, '2025-10-13', '11:04:00', '{\"spo2\": 100, \"pulse\": 78, \"respiration\": 22, \"temperature\": 37.9, \"blood_pressure\": \"124/60\"}', '{\"physiotherapy\": true, \"nutrition_support\": true, \"physiotherapy_details\": \"Mobility exercises: assisted transfers from bed to chair\", \"nutrition_support_details\": \"Monitored food intake - patient ate 75% of meals\"}', 'deteriorating', 7, 'Pressure ulcer on sacrum - Stage 2, measuring 3cm x 2cm, showing improvement', 'Patient expressing increased anxiety and discomfort', NULL, 'Family asking about progression of condition and prognosis', 'Increase frequency of nursing visits for closer monitoring', '2025-10-16 19:14:42', 'digital', '2025-10-13 21:14:42', '2025-10-13 21:14:42', NULL),
(18, 50, 61, '2025-09-09', '15:20:00', '{\"spo2\": 92, \"pulse\": 67, \"respiration\": 14, \"temperature\": 38.8, \"blood_pressure\": \"129/67\"}', '{\"other\": true, \"wound_care\": true, \"other_details\": \"Accompanied patient to medical appointment\", \"wound_care_details\": \"Applied topical antibiotic ointment to minor abrasion\"}', 'improved', 2, 'Diabetic foot wound being monitored closely, no signs of infection currently', 'Pain levels decreased, patient more comfortable and resting well', NULL, NULL, 'Gradually increase activity level as tolerated by patient', '2025-10-16 19:14:42', 'digital', '2025-09-09 20:14:42', '2025-09-09 20:14:42', NULL),
(19, 7, 57, '2025-09-14', '09:29:00', '{\"spo2\": 95, \"pulse\": 100, \"respiration\": 24, \"temperature\": 36.8, \"blood_pressure\": \"113/66\"}', '{\"nutrition_support\": true, \"counseling_education\": true, \"medication_administered\": true, \"nutrition_support_details\": \"Ensured adequate fluid intake - 1.5 liters consumed\", \"counseling_education_details\": \"Discussed importance of regular exercise and diet\", \"medication_administered_details\": \"Blood pressure medication: Amlodipine 5mg taken with water\"}', 'improved', 1, NULL, 'Pain levels decreased, patient more comfortable and resting well', NULL, NULL, 'Encourage family to maintain current level of support', '2025-10-16 19:14:42', 'digital', '2025-09-14 21:14:42', '2025-09-14 21:14:42', NULL),
(20, 51, 65, '2025-10-10', '12:52:00', '{\"spo2\": 98, \"pulse\": 69, \"respiration\": 13, \"temperature\": 38.5, \"blood_pressure\": \"133/89\"}', '{\"wound_care\": true, \"hygiene_care\": true, \"wound_care_details\": \"Changed dressing on pressure ulcer - showing signs of healing\", \"hygiene_care_details\": \"Oral care completed - teeth brushed and mouth rinsed\"}', 'stable', 5, NULL, 'No new concerns reported, continuing with current care plan', 'Educated on fall prevention measures in the home', 'Concerns raised about patient\'s mental state and mood changes', 'Next visit scheduled for routine assessment and care', '2025-10-16 19:14:42', 'digital', '2025-10-10 20:14:42', '2025-10-10 20:14:42', NULL),
(21, 40, 59, '2025-08-21', '18:49:00', '{\"spo2\": 96, \"pulse\": 79, \"respiration\": 14, \"temperature\": 35.9, \"blood_pressure\": \"101/69\"}', '{\"other\": true, \"hygiene_care\": true, \"other_details\": \"Assisted with household chores and light cleaning\", \"nutrition_support\": true, \"hygiene_care_details\": \"Helped patient with shower and hair washing\", \"nutrition_support_details\": \"Nutritional counseling provided regarding diabetic diet\"}', 'stable', 5, NULL, 'Patient managing activities of daily living with minimal assistance', NULL, NULL, 'Monitor for any changes in condition and report immediately', '2025-10-16 19:14:42', 'digital', '2025-08-21 22:14:42', '2025-08-21 22:14:42', NULL),
(22, 34, 54, '2025-09-16', '11:10:00', '{\"spo2\": 95, \"pulse\": 109, \"respiration\": 12, \"temperature\": 39.4, \"blood_pressure\": \"111/80\"}', '{\"other\": true, \"other_details\": \"Assisted with household chores and light cleaning\", \"counseling_education\": true, \"medication_administered\": true, \"counseling_education_details\": \"Provided education on medication management\", \"medication_administered_details\": \"Blood pressure medication: Amlodipine 5mg taken with water\"}', 'stable', 6, 'Post-operative wound dry and clean, no drainage or redness observed', 'No new concerns reported, continuing with current care plan', 'Explained exercises to be performed between nursing visits', 'Daughter worried about mother\'s mobility and risk of falls', 'Monitor for any changes in condition and report immediately', '2025-10-16 19:14:42', 'digital', '2025-09-16 22:14:42', '2025-09-16 22:14:42', NULL),
(23, 25, 61, '2025-09-24', '12:47:00', '{\"spo2\": 93, \"pulse\": 79, \"respiration\": 16, \"temperature\": 36.1, \"blood_pressure\": \"121/82\"}', '{\"wound_care\": true, \"physiotherapy\": true, \"nutrition_support\": true, \"wound_care_details\": \"Applied topical antibiotic ointment to minor abrasion\", \"physiotherapy_details\": \"Assisted with range of motion exercises for 30 minutes\", \"medication_administered\": true, \"nutrition_support_details\": \"Ensured adequate fluid intake - 1.5 liters consumed\", \"medication_administered_details\": \"Insulin injection administered - 10 units subcutaneously\"}', 'improved', 0, NULL, 'Patient expressing positive outlook and improved mood', 'Explained exercises to be performed between nursing visits', NULL, 'Consider reducing frequency of visits if improvement continues', '2025-10-16 19:14:42', 'digital', '2025-09-24 20:14:42', '2025-09-24 20:14:42', NULL),
(24, 49, 60, '2025-09-28', '09:34:00', '{\"spo2\": 97, \"pulse\": 77, \"respiration\": 21, \"temperature\": 36.3, \"blood_pressure\": \"120/85\"}', '{\"other\": true, \"wound_care\": true, \"other_details\": \"Assisted with household chores and light cleaning\", \"physiotherapy\": true, \"wound_care_details\": \"Changed dressing on pressure ulcer - showing signs of healing\", \"counseling_education\": true, \"physiotherapy_details\": \"Assisted with range of motion exercises for 30 minutes\", \"counseling_education_details\": \"Discussed importance of regular exercise and diet\"}', 'improved', 0, 'Minor abrasion on left elbow, cleaned and dressed, healing normally', 'Patient showing significant improvement in mobility and energy levels', 'Educated on fall prevention measures in the home', 'Spouse questions effectiveness of current pain management', 'Encourage family to maintain current level of support', '2025-10-16 19:14:42', 'digital', '2025-09-28 21:14:42', '2025-09-28 21:14:42', NULL),
(25, 41, 59, '2025-09-25', '12:56:00', '{\"spo2\": 93, \"pulse\": 94, \"respiration\": 25, \"temperature\": 37.4, \"blood_pressure\": \"115/80\"}', '{\"hygiene_care\": true, \"physiotherapy\": true, \"nutrition_support\": true, \"counseling_education\": true, \"hygiene_care_details\": \"Changed bed linens and patient gown\", \"physiotherapy_details\": \"Breathing exercises completed to improve lung capacity\", \"nutrition_support_details\": \"Nutritional counseling provided regarding diabetic diet\", \"counseling_education_details\": \"Counseled on fall prevention strategies\"}', 'stable', 6, 'Leg ulcer showing signs of healing, reduced exudate, granulation tissue present', 'No new concerns reported, continuing with current care plan', NULL, 'Concerns raised about patient\'s mental state and mood changes', 'Monitor for any changes in condition and report immediately', '2025-10-16 19:14:42', 'digital', '2025-09-25 20:14:42', '2025-09-25 20:14:42', NULL),
(26, 24, 60, '2025-09-30', '18:38:00', '{\"spo2\": 100, \"pulse\": 113, \"respiration\": 23, \"temperature\": 38.1, \"blood_pressure\": \"140/87\"}', '{\"hygiene_care\": true, \"nutrition_support\": true, \"hygiene_care_details\": \"Skin care routine completed - moisturizer applied\", \"medication_administered\": true, \"nutrition_support_details\": \"High protein supplement provided as prescribed\", \"medication_administered_details\": \"Administered prescribed medications as per care plan\"}', 'deteriorating', 7, NULL, 'Mobility has decreased, patient requiring more assistance', 'Reviewed diabetes management including blood sugar monitoring', NULL, 'Implement additional safety measures to prevent complications', '2025-10-16 19:14:42', 'digital', '2025-09-30 21:14:42', '2025-09-30 21:14:42', NULL),
(27, 25, 62, '2025-09-22', '15:37:00', '{\"spo2\": 94, \"pulse\": 69, \"respiration\": 12, \"temperature\": 37.6, \"blood_pressure\": \"133/63\"}', '{\"other\": true, \"hygiene_care\": true, \"other_details\": \"Provided companionship and social interaction\", \"physiotherapy\": true, \"hygiene_care_details\": \"Skin care routine completed - moisturizer applied\", \"physiotherapy_details\": \"Mobility exercises: assisted transfers from bed to chair\"}', 'stable', 3, NULL, 'No new concerns reported, continuing with current care plan', 'Demonstrated proper wound care techniques to family members', NULL, 'Continue with prescribed medications and exercises', '2025-10-16 19:14:42', 'digital', '2025-09-22 20:14:42', '2025-09-22 20:14:42', NULL),
(28, 40, 56, '2025-10-08', '13:23:00', '{\"spo2\": 94, \"pulse\": 107, \"respiration\": 23, \"temperature\": 38.1, \"blood_pressure\": \"114/73\"}', '{\"other\": true, \"wound_care\": true, \"other_details\": \"Provided companionship and social interaction\", \"nutrition_support\": true, \"wound_care_details\": \"Wound inspection completed - no signs of infection\", \"medication_administered\": true, \"nutrition_support_details\": \"High protein supplement provided as prescribed\", \"medication_administered_details\": \"Antibiotic course continued - Amoxicillin 500mg\"}', 'improved', 1, 'Minor abrasion on left elbow, cleaned and dressed, healing normally', 'Patient showing significant improvement in mobility and energy levels', 'Explained exercises to be performed between nursing visits', NULL, 'Consider reducing frequency of visits if improvement continues', '2025-10-16 19:14:42', 'digital', '2025-10-08 22:14:42', '2025-10-08 22:14:42', NULL),
(29, 35, 54, '2025-09-22', '15:30:00', '{\"spo2\": 99, \"pulse\": 86, \"respiration\": 13, \"temperature\": 35.8, \"blood_pressure\": \"110/80\"}', '{\"wound_care\": true, \"physiotherapy\": true, \"nutrition_support\": true, \"wound_care_details\": \"Changed dressing on pressure ulcer - showing signs of healing\", \"counseling_education\": true, \"physiotherapy_details\": \"Assisted with range of motion exercises for 30 minutes\", \"nutrition_support_details\": \"Monitored food intake - patient ate 75% of meals\", \"counseling_education_details\": \"Counseled on fall prevention strategies\"}', 'deteriorating', 7, NULL, 'Patient expressing increased anxiety and discomfort', 'Explained exercises to be performed between nursing visits', 'Family requesting additional support services or respite care', 'Notify family of changes and discuss care plan adjustments', '2025-10-16 19:14:42', 'digital', '2025-09-22 21:14:42', '2025-09-22 21:14:42', NULL),
(30, 25, 55, '2025-08-28', '15:21:00', '{\"spo2\": 94, \"pulse\": 101, \"respiration\": 21, \"temperature\": 35.5, \"blood_pressure\": \"119/68\"}', '{\"other\": true, \"wound_care\": true, \"hygiene_care\": true, \"other_details\": \"Emotional support provided during difficult period\", \"wound_care_details\": \"Removed old dressing, cleaned with saline, applied new sterile dressing\", \"hygiene_care_details\": \"Assisted with bed bath and personal hygiene\", \"medication_administered\": true, \"medication_administered_details\": \"Antibiotic course continued - Amoxicillin 500mg\"}', 'improved', 3, 'Diabetic foot wound being monitored closely, no signs of infection currently', 'Patient showing significant improvement in mobility and energy levels', NULL, 'Caregiver expressing feeling overwhelmed with care responsibilities', 'Consider reducing frequency of visits if improvement continues', '2025-10-16 19:14:42', 'digital', '2025-08-28 21:14:42', '2025-08-28 21:14:42', NULL),
(31, 45, 57, '2025-10-16', '08:06:00', '{\"spo2\": 95, \"pulse\": 64, \"respiration\": 13, \"temperature\": 37.3, \"blood_pressure\": \"102/84\"}', '{\"wound_care\": true, \"physiotherapy\": true, \"wound_care_details\": \"Cleaned and dressed surgical wound on right leg\", \"physiotherapy_details\": \"Mobility exercises: assisted transfers from bed to chair\", \"medication_administered\": true, \"medication_administered_details\": \"Blood pressure medication: Amlodipine 5mg taken with water\"}', 'stable', 2, 'Post-operative wound dry and clean, no drainage or redness observed', 'Sleep pattern regular, patient well-rested', NULL, NULL, 'Monitor for any changes in condition and report immediately', '2025-10-16 19:14:42', 'digital', '2025-10-16 21:14:42', '2025-10-16 21:14:42', NULL),
(32, 39, 62, '2025-09-18', '14:16:00', '{\"spo2\": 96, \"pulse\": 80, \"respiration\": 16, \"temperature\": 37.2, \"blood_pressure\": \"131/81\"}', '{\"other\": true, \"wound_care\": true, \"hygiene_care\": true, \"other_details\": \"Assisted with household chores and light cleaning\", \"wound_care_details\": \"Applied topical antibiotic ointment to minor abrasion\", \"hygiene_care_details\": \"Helped patient with shower and hair washing\", \"medication_administered\": true, \"medication_administered_details\": \"Insulin injection administered - 10 units subcutaneously\"}', 'deteriorating', 7, NULL, 'Patient appears more fatigued than previous visit', NULL, 'Family requesting additional support services or respite care', 'Consult with physician regarding medication adjustments', '2025-10-16 19:14:42', 'digital', '2025-09-18 22:14:42', '2025-09-18 22:14:42', NULL),
(33, 26, 79, '2025-09-27', '13:46:00', '{\"spo2\": 92, \"pulse\": 87, \"respiration\": 20, \"temperature\": 36.6, \"blood_pressure\": \"112/74\"}', '{\"other\": true, \"wound_care\": true, \"hygiene_care\": true, \"other_details\": \"Coordinated with family members regarding care schedule\", \"physiotherapy\": true, \"wound_care_details\": \"Removed old dressing, cleaned with saline, applied new sterile dressing\", \"hygiene_care_details\": \"Helped patient with shower and hair washing\", \"physiotherapy_details\": \"Mobility exercises: assisted transfers from bed to chair\"}', 'deteriorating', 7, 'Surgical wound healing well, no signs of infection, sutures intact', 'Patient appears more fatigued than previous visit', 'Provided information on nutrition and hydration requirements', NULL, 'Schedule urgent follow-up within 24-48 hours', '2025-10-16 19:14:42', 'digital', '2025-09-27 21:14:42', '2025-09-27 21:14:42', NULL),
(34, 40, 59, '2025-08-26', '10:58:00', '{\"spo2\": 93, \"pulse\": 102, \"respiration\": 23, \"temperature\": 39.1, \"blood_pressure\": \"110/65\"}', '{\"wound_care\": true, \"wound_care_details\": \"Applied topical antibiotic ointment to minor abrasion\", \"medication_administered\": true, \"medication_administered_details\": \"Given pain medication: Paracetamol 500mg, 2 tablets\"}', 'improved', 1, NULL, 'Mental alertness and cognitive function noticeably better', NULL, NULL, 'Consider reducing frequency of visits if improvement continues', '2025-10-16 19:14:42', 'digital', '2025-08-26 21:14:42', '2025-08-26 21:14:42', NULL),
(35, 20, 55, '2025-10-13', '18:27:00', '{\"spo2\": 97, \"pulse\": 105, \"respiration\": 12, \"temperature\": 36.1, \"blood_pressure\": \"100/76\"}', '{\"other\": true, \"other_details\": \"Provided companionship and social interaction\", \"physiotherapy\": true, \"physiotherapy_details\": \"Walking exercise completed - patient walked 50 meters with walker\"}', 'improved', 1, 'Post-operative wound dry and clean, no drainage or redness observed', 'Patient expressing positive outlook and improved mood', 'Educated on fall prevention measures in the home', NULL, 'Gradually increase activity level as tolerated by patient', '2025-10-16 19:14:42', 'digital', '2025-10-13 21:14:42', '2025-10-13 21:14:42', NULL),
(36, 46, 57, '2025-09-25', '12:09:00', '{\"spo2\": 95, \"pulse\": 70, \"respiration\": 12, \"temperature\": 36.7, \"blood_pressure\": \"131/60\"}', '{\"wound_care\": true, \"hygiene_care\": true, \"physiotherapy\": true, \"wound_care_details\": \"Changed dressing on pressure ulcer - showing signs of healing\", \"hygiene_care_details\": \"Skin care routine completed - moisturizer applied\", \"physiotherapy_details\": \"Breathing exercises completed to improve lung capacity\", \"medication_administered\": true, \"medication_administered_details\": \"Antibiotic course continued - Amoxicillin 500mg\"}', 'improved', 2, 'Leg ulcer showing signs of healing, reduced exudate, granulation tissue present', 'Patient showing significant improvement in mobility and energy levels', NULL, NULL, 'Consider reducing frequency of visits if improvement continues', '2025-10-16 19:14:42', 'digital', '2025-09-25 22:14:42', '2025-09-25 22:14:42', NULL),
(37, 20, 66, '2025-09-04', '17:37:00', '{\"spo2\": 95, \"pulse\": 87, \"respiration\": 14, \"temperature\": 37.6, \"blood_pressure\": \"114/65\"}', '{\"other\": true, \"wound_care\": true, \"other_details\": \"Coordinated with family members regarding care schedule\", \"physiotherapy\": true, \"nutrition_support\": true, \"wound_care_details\": \"Changed dressing on pressure ulcer - showing signs of healing\", \"physiotherapy_details\": \"Leg strengthening exercises performed as per physio plan\", \"nutrition_support_details\": \"Monitored food intake - patient ate 75% of meals\"}', 'deteriorating', 9, 'Leg ulcer showing signs of healing, reduced exudate, granulation tissue present', 'Patient appears more fatigued than previous visit', 'Demonstrated proper wound care techniques to family members', 'Family asking about progression of condition and prognosis', 'Consider referral to specialist for further evaluation', '2025-10-16 19:14:42', 'digital', '2025-09-04 21:14:42', '2025-09-04 21:14:42', NULL),
(38, 16, 64, '2025-08-24', '14:02:00', '{\"spo2\": 92, \"pulse\": 58, \"respiration\": 13, \"temperature\": 39.1, \"blood_pressure\": \"126/88\"}', '{\"other\": true, \"other_details\": \"Emotional support provided during difficult period\", \"counseling_education\": true, \"medication_administered\": true, \"counseling_education_details\": \"Discussed importance of regular exercise and diet\", \"medication_administered_details\": \"Antibiotic course continued - Amoxicillin 500mg\"}', 'deteriorating', 8, NULL, 'Pain levels increasing despite medication adjustments', 'Demonstrated proper wound care techniques to family members', NULL, 'Increase frequency of nursing visits for closer monitoring', '2025-10-16 19:14:42', 'digital', '2025-08-24 22:14:42', '2025-08-24 22:14:42', NULL),
(39, 16, 60, '2025-09-29', '16:07:00', '{\"spo2\": 94, \"pulse\": 62, \"respiration\": 21, \"temperature\": 38.2, \"blood_pressure\": \"109/78\"}', '{\"other\": true, \"hygiene_care\": true, \"other_details\": \"Assisted with household chores and light cleaning\", \"nutrition_support\": true, \"counseling_education\": true, \"hygiene_care_details\": \"Oral care completed - teeth brushed and mouth rinsed\", \"nutrition_support_details\": \"Ensured adequate fluid intake - 1.5 liters consumed\", \"counseling_education_details\": \"Educated family on signs of complications to watch for\"}', 'deteriorating', 10, NULL, 'Patient appears more fatigued than previous visit', NULL, 'Concerns raised about patient\'s mental state and mood changes', 'Implement additional safety measures to prevent complications', '2025-10-16 19:14:42', 'digital', '2025-09-29 21:14:42', '2025-09-29 21:14:42', NULL),
(40, 21, 79, '2025-10-08', '18:03:00', '{\"spo2\": 97, \"pulse\": 91, \"respiration\": 13, \"temperature\": 39.3, \"blood_pressure\": \"120/65\"}', '{\"hygiene_care\": true, \"physiotherapy\": true, \"nutrition_support\": true, \"hygiene_care_details\": \"Assisted with bed bath and personal hygiene\", \"physiotherapy_details\": \"Leg strengthening exercises performed as per physio plan\", \"medication_administered\": true, \"nutrition_support_details\": \"Monitored food intake - patient ate 75% of meals\", \"medication_administered_details\": \"Antibiotic course continued - Amoxicillin 500mg\"}', 'stable', 5, 'Diabetic foot wound being monitored closely, no signs of infection currently', 'Vital signs within normal limits, patient comfortable', NULL, 'Caregiver expressing feeling overwhelmed with care responsibilities', 'Regular follow-up visits as scheduled, no changes needed', '2025-10-16 19:14:42', 'digital', '2025-10-08 22:14:42', '2025-10-08 22:14:42', NULL),
(41, 44, 59, '2025-08-24', '08:55:00', '{\"spo2\": 100, \"pulse\": 94, \"respiration\": 12, \"temperature\": 38.5, \"blood_pressure\": \"106/86\"}', '{\"other\": true, \"hygiene_care\": true, \"other_details\": \"Emotional support provided during difficult period\", \"nutrition_support\": true, \"hygiene_care_details\": \"Oral care completed - teeth brushed and mouth rinsed\", \"medication_administered\": true, \"nutrition_support_details\": \"High protein supplement provided as prescribed\", \"medication_administered_details\": \"Given pain medication: Paracetamol 500mg, 2 tablets\"}', 'stable', 3, 'Minor abrasion on left elbow, cleaned and dressed, healing normally', 'Sleep pattern regular, patient well-rested', NULL, 'Family concerned about patient\'s decreased appetite', 'Monitor for any changes in condition and report immediately', '2025-10-16 19:14:42', 'digital', '2025-08-24 22:14:42', '2025-08-24 22:14:42', NULL),
(42, 37, 63, '2025-09-26', '12:05:00', '{\"spo2\": 92, \"pulse\": 119, \"respiration\": 23, \"temperature\": 38, \"blood_pressure\": \"121/77\"}', '{\"hygiene_care\": true, \"nutrition_support\": true, \"counseling_education\": true, \"hygiene_care_details\": \"Helped patient with shower and hair washing\", \"nutrition_support_details\": \"High protein supplement provided as prescribed\", \"counseling_education_details\": \"Educated family on signs of complications to watch for\"}', 'stable', 4, NULL, 'Sleep pattern regular, patient well-rested', 'Demonstrated proper wound care techniques to family members', 'Caregiver expressing feeling overwhelmed with care responsibilities', 'Monitor for any changes in condition and report immediately', '2025-10-16 19:14:42', 'digital', '2025-09-26 20:14:42', '2025-09-26 20:14:42', NULL),
(43, 27, 55, '2025-09-11', '17:46:00', '{\"spo2\": 99, \"pulse\": 103, \"respiration\": 12, \"temperature\": 38, \"blood_pressure\": \"140/83\"}', '{\"other\": true, \"other_details\": \"Coordinated with family members regarding care schedule\", \"physiotherapy\": true, \"physiotherapy_details\": \"Breathing exercises completed to improve lung capacity\", \"medication_administered\": true, \"medication_administered_details\": \"Administered prescribed medications as per care plan\"}', 'improved', 1, 'Post-operative wound dry and clean, no drainage or redness observed', 'Mental alertness and cognitive function noticeably better', 'Reviewed medication schedule and importance of adherence', NULL, 'Gradually increase activity level as tolerated by patient', '2025-10-16 19:14:42', 'digital', '2025-09-11 20:14:42', '2025-09-11 20:14:42', NULL),
(44, 39, 59, '2025-09-26', '15:44:00', '{\"spo2\": 98, \"pulse\": 100, \"respiration\": 23, \"temperature\": 37.5, \"blood_pressure\": \"114/69\"}', '{\"other\": true, \"other_details\": \"Emotional support provided during difficult period\", \"physiotherapy\": true, \"physiotherapy_details\": \"Breathing exercises completed to improve lung capacity\"}', 'deteriorating', 10, 'Post-operative wound dry and clean, no drainage or redness observed', 'Mobility has decreased, patient requiring more assistance', 'Educated on fall prevention measures in the home', 'Family concerned about patient\'s decreased appetite', 'Schedule urgent follow-up within 24-48 hours', '2025-10-16 19:14:42', 'digital', '2025-09-26 20:14:42', '2025-09-26 20:14:42', NULL),
(45, 19, 55, '2025-09-29', '17:02:00', '{\"spo2\": 99, \"pulse\": 69, \"respiration\": 19, \"temperature\": 37.6, \"blood_pressure\": \"113/77\"}', '{\"wound_care\": true, \"hygiene_care\": true, \"wound_care_details\": \"Changed dressing on pressure ulcer - showing signs of healing\", \"counseling_education\": true, \"hygiene_care_details\": \"Helped patient with shower and hair washing\", \"counseling_education_details\": \"Reviewed wound care instructions with patient\"}', 'improved', 3, NULL, 'Pain levels decreased, patient more comfortable and resting well', 'Reviewed medication schedule and importance of adherence', 'Daughter worried about mother\'s mobility and risk of falls', 'Encourage family to maintain current level of support', '2025-10-16 19:14:42', 'digital', '2025-09-29 22:14:42', '2025-09-29 22:14:42', NULL),
(46, 29, 60, '2025-08-21', '16:26:00', '{\"spo2\": 97, \"pulse\": 56, \"respiration\": 19, \"temperature\": 36.1, \"blood_pressure\": \"107/76\"}', '{\"other\": true, \"other_details\": \"Emotional support provided during difficult period\", \"medication_administered\": true, \"medication_administered_details\": \"Antibiotic course continued - Amoxicillin 500mg\"}', 'improved', 3, NULL, 'Mental alertness and cognitive function noticeably better', 'Explained exercises to be performed between nursing visits', 'Spouse questions effectiveness of current pain management', 'Continue current medication regimen and monitor progress', '2025-10-16 19:14:42', 'digital', '2025-08-21 20:14:42', '2025-08-21 20:14:42', NULL),
(47, 21, 64, '2025-10-14', '14:32:00', '{\"spo2\": 93, \"pulse\": 58, \"respiration\": 14, \"temperature\": 37.3, \"blood_pressure\": \"121/90\"}', '{\"wound_care\": true, \"hygiene_care\": true, \"nutrition_support\": true, \"wound_care_details\": \"Cleaned and dressed surgical wound on right leg\", \"hygiene_care_details\": \"Assisted with bed bath and personal hygiene\", \"medication_administered\": true, \"nutrition_support_details\": \"Ensured adequate fluid intake - 1.5 liters consumed\", \"medication_administered_details\": \"Insulin injection administered - 10 units subcutaneously\"}', 'improved', 0, NULL, 'Patient expressing positive outlook and improved mood', NULL, 'Concerns raised about patient\'s mental state and mood changes', 'Continue current medication regimen and monitor progress', '2025-10-16 19:14:42', 'digital', '2025-10-14 21:14:42', '2025-10-14 21:14:42', NULL),
(48, 8, 66, '2025-10-08', '14:21:00', '{\"spo2\": 99, \"pulse\": 79, \"respiration\": 12, \"temperature\": 37.2, \"blood_pressure\": \"123/69\"}', '{\"hygiene_care\": true, \"physiotherapy\": true, \"counseling_education\": true, \"hygiene_care_details\": \"Skin care routine completed - moisturizer applied\", \"physiotherapy_details\": \"Mobility exercises: assisted transfers from bed to chair\", \"counseling_education_details\": \"Discussed importance of regular exercise and diet\"}', 'deteriorating', 8, 'Post-operative wound dry and clean, no drainage or redness observed', 'Mobility has decreased, patient requiring more assistance', 'Reviewed diabetes management including blood sugar monitoring', 'Spouse questions effectiveness of current pain management', 'Notify family of changes and discuss care plan adjustments', '2025-10-16 19:14:42', 'digital', '2025-10-08 21:14:42', '2025-10-08 21:14:42', NULL),
(49, 43, 67, '2025-10-01', '17:27:00', '{\"spo2\": 99, \"pulse\": 87, \"respiration\": 22, \"temperature\": 39.1, \"blood_pressure\": \"100/84\"}', '{\"wound_care\": true, \"hygiene_care\": true, \"physiotherapy\": true, \"wound_care_details\": \"Removed old dressing, cleaned with saline, applied new sterile dressing\", \"hygiene_care_details\": \"Skin care routine completed - moisturizer applied\", \"physiotherapy_details\": \"Walking exercise completed - patient walked 50 meters with walker\"}', 'deteriorating', 5, 'Surgical wound healing well, no signs of infection, sutures intact', 'Pain levels increasing despite medication adjustments', 'Discussed signs and symptoms that require immediate medical attention', NULL, 'Implement additional safety measures to prevent complications', '2025-10-16 19:14:42', 'digital', '2025-10-01 22:14:42', '2025-10-01 22:14:42', NULL),
(50, 38, 53, '2025-09-21', '10:58:00', '{\"spo2\": 94, \"pulse\": 97, \"respiration\": 16, \"temperature\": 38.4, \"blood_pressure\": \"128/85\"}', '{\"other\": true, \"hygiene_care\": true, \"other_details\": \"Emotional support provided during difficult period\", \"physiotherapy\": true, \"hygiene_care_details\": \"Skin care routine completed - moisturizer applied\", \"physiotherapy_details\": \"Assisted with range of motion exercises for 30 minutes\"}', 'stable', 2, NULL, 'Patient condition remains stable with no significant changes', 'Discussed signs and symptoms that require immediate medical attention', 'Family requesting additional support services or respite care', 'Regular follow-up visits as scheduled, no changes needed', '2025-10-16 19:14:42', 'digital', '2025-09-21 21:14:42', '2025-09-21 21:14:42', NULL),
(51, 25, 60, '2025-08-21', '08:29:00', '{\"spo2\": 92, \"pulse\": 112, \"respiration\": 14, \"temperature\": 37.7, \"blood_pressure\": \"125/81\"}', '{\"other\": true, \"other_details\": \"Provided companionship and social interaction\", \"medication_administered\": true, \"medication_administered_details\": \"Administered prescribed medications as per care plan\"}', 'stable', 5, NULL, 'Vital signs within normal limits, patient comfortable', NULL, 'Daughter worried about mother\'s mobility and risk of falls', 'Monitor for any changes in condition and report immediately', '2025-10-16 19:14:42', 'digital', '2025-08-21 20:14:42', '2025-08-21 20:14:42', NULL),
(52, 47, 56, '2025-10-14', '15:36:00', '{\"spo2\": 100, \"pulse\": 119, \"respiration\": 15, \"temperature\": 38.5, \"blood_pressure\": \"110/75\"}', '{\"wound_care\": true, \"physiotherapy\": true, \"nutrition_support\": true, \"wound_care_details\": \"Removed old dressing, cleaned with saline, applied new sterile dressing\", \"counseling_education\": true, \"physiotherapy_details\": \"Walking exercise completed - patient walked 50 meters with walker\", \"nutrition_support_details\": \"Assisted with meal preparation and feeding\", \"counseling_education_details\": \"Discussed importance of regular exercise and diet\"}', 'deteriorating', 9, 'Post-operative wound dry and clean, no drainage or redness observed', 'Pain levels increasing despite medication adjustments', NULL, 'Family concerned about patient\'s decreased appetite', 'Consult with physician regarding medication adjustments', '2025-10-16 19:14:42', 'digital', '2025-10-14 22:14:42', '2025-10-14 22:14:42', NULL),
(53, 35, 62, '2025-09-27', '18:44:00', '{\"spo2\": 94, \"pulse\": 113, \"respiration\": 24, \"temperature\": 35.5, \"blood_pressure\": \"101/80\"}', '{\"physiotherapy\": true, \"nutrition_support\": true, \"counseling_education\": true, \"physiotherapy_details\": \"Mobility exercises: assisted transfers from bed to chair\", \"medication_administered\": true, \"nutrition_support_details\": \"Monitored food intake - patient ate 75% of meals\", \"counseling_education_details\": \"Provided education on medication management\", \"medication_administered_details\": \"Blood pressure medication: Amlodipine 5mg taken with water\"}', 'improved', 3, NULL, 'Pain levels decreased, patient more comfortable and resting well', 'Reviewed diabetes management including blood sugar monitoring', 'Family requesting additional support services or respite care', 'Gradually increase activity level as tolerated by patient', '2025-10-16 19:14:42', 'digital', '2025-09-27 22:14:42', '2025-09-27 22:14:42', NULL),
(54, 41, 63, '2025-09-02', '13:09:00', '{\"spo2\": 98, \"pulse\": 100, \"respiration\": 15, \"temperature\": 38.1, \"blood_pressure\": \"109/65\"}', '{\"wound_care\": true, \"hygiene_care\": true, \"wound_care_details\": \"Cleaned and dressed surgical wound on right leg\", \"hygiene_care_details\": \"Assisted with bed bath and personal hygiene\"}', 'improved', 1, 'Leg ulcer showing signs of healing, reduced exudate, granulation tissue present', 'Patient showing significant improvement in mobility and energy levels', NULL, 'Family concerned about patient\'s decreased appetite', 'Encourage family to maintain current level of support', '2025-10-16 19:14:42', 'digital', '2025-09-02 21:14:42', '2025-09-02 21:14:42', NULL),
(55, 47, 57, '2025-09-21', '08:11:00', '{\"spo2\": 95, \"pulse\": 119, \"respiration\": 25, \"temperature\": 36.4, \"blood_pressure\": \"132/79\"}', '{\"wound_care\": true, \"physiotherapy\": true, \"nutrition_support\": true, \"wound_care_details\": \"Wound inspection completed - no signs of infection\", \"counseling_education\": true, \"physiotherapy_details\": \"Assisted with range of motion exercises for 30 minutes\", \"nutrition_support_details\": \"High protein supplement provided as prescribed\", \"counseling_education_details\": \"Educated family on signs of complications to watch for\"}', 'deteriorating', 6, NULL, 'Pain levels increasing despite medication adjustments', 'Reviewed medication schedule and importance of adherence', NULL, 'Consider referral to specialist for further evaluation', '2025-10-16 19:14:42', 'digital', '2025-09-21 20:14:42', '2025-09-21 20:14:42', NULL),
(56, 36, 57, '2025-10-08', '15:59:00', '{\"spo2\": 96, \"pulse\": 85, \"respiration\": 14, \"temperature\": 36.7, \"blood_pressure\": \"106/81\"}', '{\"other\": true, \"wound_care\": true, \"other_details\": \"Accompanied patient to medical appointment\", \"nutrition_support\": true, \"wound_care_details\": \"Wound inspection completed - no signs of infection\", \"nutrition_support_details\": \"Assisted with meal preparation and feeding\"}', 'deteriorating', 6, 'Pressure ulcer on sacrum - Stage 2, measuring 3cm x 2cm, showing improvement', 'Patient appears more fatigued than previous visit', NULL, NULL, 'Increase frequency of nursing visits for closer monitoring', '2025-10-16 19:14:42', 'digital', '2025-10-08 21:14:42', '2025-10-08 21:14:42', NULL),
(57, 82, 57, '2025-10-16', '19:16:00', '{\"spo2\": 93, \"pulse\": 110, \"respiration\": 21, \"temperature\": 38.1, \"blood_pressure\": \"107/63\"}', '{\"wound_care\": true, \"wound_care_details\": \"Cleaned and dressed surgical wound on right leg\", \"counseling_education\": true, \"counseling_education_details\": \"Educated family on signs of complications to watch for\"}', 'deteriorating', 8, NULL, NULL, NULL, NULL, 'Consider referral to specialist for further evaluation', '2025-10-16 19:16:21', 'digital', '2025-10-16 19:16:21', '2025-10-16 19:16:21', NULL),
(58, 49, 4, '2025-10-17', '00:07:00', '{\"spo2\": 98, \"pulse\": 72, \"respiration\": 16, \"temperature\": 36, \"blood_pressure\": \"120/88\"}', '{\"counseling\": false, \"wound_care\": false, \"hygiene_care\": false, \"physiotherapy\": false, \"nutrition_support\": false, \"medication_details\": \"Test\", \"other_interventions\": false, \"medication_administered\": true}', 'stable', 3, 'No wounds discovered', 'N/A', NULL, 'Sleepless nights', 'Administer new medications', '2025-10-17 00:08:31', 'digital', '2025-10-17 00:08:31', '2025-10-17 00:08:31', NULL),
(59, 7, 4, '2025-10-28', '21:10:00', '{\"spo2\": 98, \"pulse\": 72, \"respiration\": 16, \"temperature\": 34, \"blood_pressure\": \"122/82\"}', NULL, 'stable', 0, NULL, NULL, NULL, NULL, NULL, '2025-10-28 21:10:36', 'digital', '2025-10-28 21:10:36', '2025-10-28 21:10:36', NULL),
(60, 7, 4, '2025-10-29', '08:35:00', '{\"spo2\": 100, \"pulse\": 75, \"respiration\": 16, \"temperature\": 35, \"blood_pressure\": \"123/83\"}', '{\"counseling\": false, \"wound_care\": true, \"hygiene_care\": false, \"physiotherapy\": true, \"nutrition_support\": false, \"medication_details\": \"Paracetamol given\", \"wound_care_details\": \"Wound first aid applied\", \"other_interventions\": false, \"medication_administered\": true}', 'deteriorating', 10, 'Wound not healing yet. Just started administration', 'Leg swollen', 'Yes, Family educated', 'Sleepless nights', 'To be provided', '2025-10-29 08:37:00', 'digital', '2025-10-29 08:37:00', '2025-10-29 08:37:00', NULL),
(64, 45, 4, '2025-11-02', '15:16:00', '{\"spo2\": 100, \"pulse\": 74, \"respiration\": 16, \"temperature\": 36, \"blood_pressure\": \"124/84\"}', '{\"counseling\": false, \"wound_care\": false, \"hygiene_care\": false, \"physiotherapy\": false, \"nutrition_support\": false, \"medication_details\": \"Fellister offered\", \"other_interventions\": false, \"medication_administered\": true}', 'improved', 4, 'Wound is now healing after applying fellister', 'Patient is able to walk quite better', NULL, NULL, 'Care plan to be followed', '2025-11-02 15:17:18', 'digital', '2025-11-02 15:17:18', '2025-11-02 15:17:18', NULL),
(65, 5, 53, '2025-11-03', '23:49:00', '{\"spo2\": 99, \"pulse\": 74, \"respiration\": 18, \"temperature\": 36, \"blood_pressure\": \"122/81\"}', '{\"counseling\": false, \"wound_care\": true, \"hygiene_care\": false, \"physiotherapy\": false, \"nutrition_details\": \"Water provided\", \"nutrition_support\": true, \"medication_details\": \"Wounds covered with fellista\", \"wound_care_details\": \"Wound healing\", \"other_interventions\": false, \"medication_administered\": true}', 'stable', 7, 'Wound is currently healing', 'No oberservations', 'How to administer', 'Sleepless nights', 'Follow care plan', '2025-11-03 23:50:41', 'digital', '2025-11-03 23:50:41', '2025-11-03 23:50:41', NULL),
(66, 5, 53, '2025-11-04', '00:29:00', '{\"spo2\": 98, \"pulse\": 72, \"respiration\": 18, \"temperature\": 33, \"blood_pressure\": \"120/80\"}', '{\"counseling\": true, \"wound_care\": false, \"hygiene_care\": false, \"physiotherapy\": false, \"nutrition_details\": \"Offerend nutrition support\", \"nutrition_support\": true, \"counseling_details\": \"Offered education\", \"other_interventions\": false, \"medication_administered\": false}', 'improved', 4, 'No wounds', NULL, NULL, NULL, 'Care plan test', '2025-11-04 00:29:47', 'digital', '2025-11-04 00:29:47', '2025-11-04 00:29:47', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `display_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `is_system_role` tinyint(1) NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `name`, `display_name`, `description`, `is_system_role`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'superadmin', 'Super Administrator', 'Full system access with all permissions', 1, 1, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(2, 'admin', 'Administrator', 'Administrative access to most system features', 1, 1, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(3, 'nurse', 'Nurse', 'Access for nurses to manage patient care', 1, 1, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(4, 'doctor', 'Doctor', 'Access for doctors to manage patient care and medical decisions', 1, 1, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(5, 'patient', 'Patient', 'Limited access for patients to view their own data', 1, 1, '2025-09-30 20:20:56', '2025-09-30 20:20:56');

-- --------------------------------------------------------

--
-- Table structure for table `role_permission`
--

CREATE TABLE `role_permission` (
  `id` bigint UNSIGNED NOT NULL,
  `role_id` bigint UNSIGNED NOT NULL,
  `permission_id` bigint UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `role_permission`
--

INSERT INTO `role_permission` (`id`, `role_id`, `permission_id`, `created_at`, `updated_at`) VALUES
(1, 1, 27, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(2, 1, 24, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(3, 1, 26, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(4, 1, 25, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(5, 1, 23, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(6, 1, 32, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(7, 1, 29, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(8, 1, 31, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(9, 1, 30, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(10, 1, 28, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(11, 1, 22, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(12, 1, 40, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(13, 1, 42, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(14, 1, 41, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(15, 1, 39, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(16, 1, 45, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(17, 1, 47, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(18, 1, 46, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(19, 1, 44, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(20, 1, 43, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(21, 1, 54, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(22, 1, 55, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(23, 1, 53, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(24, 1, 51, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(25, 1, 56, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(26, 1, 52, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(27, 1, 37, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(28, 1, 34, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(29, 1, 36, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(30, 1, 35, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(31, 1, 38, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(32, 1, 33, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(33, 1, 48, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(34, 1, 50, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(35, 1, 49, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(36, 1, 17, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(37, 1, 19, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(38, 1, 18, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(39, 1, 20, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(40, 1, 16, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(41, 1, 8, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(42, 1, 10, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(43, 1, 9, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(44, 1, 11, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(45, 1, 7, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(46, 1, 13, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(47, 1, 15, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(48, 1, 14, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(49, 1, 12, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(50, 1, 21, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(51, 1, 3, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(52, 1, 5, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(53, 1, 4, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(54, 1, 6, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(55, 1, 2, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(56, 1, 1, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(57, 2, 27, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(58, 2, 24, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(59, 2, 26, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(60, 2, 25, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(61, 2, 23, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(62, 2, 32, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(63, 2, 29, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(64, 2, 31, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(65, 2, 30, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(66, 2, 28, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(67, 2, 22, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(68, 2, 40, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(69, 2, 42, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(70, 2, 41, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(71, 2, 39, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(72, 2, 45, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(73, 2, 47, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(74, 2, 46, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(75, 2, 44, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(76, 2, 43, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(77, 2, 54, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(78, 2, 55, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(79, 2, 53, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(80, 2, 51, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(81, 2, 56, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(82, 2, 52, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(83, 2, 37, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(84, 2, 34, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(85, 2, 36, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(86, 2, 35, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(87, 2, 38, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(88, 2, 33, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(89, 2, 48, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(90, 2, 50, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(91, 2, 49, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(92, 2, 17, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(93, 2, 19, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(94, 2, 18, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(95, 2, 20, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(96, 2, 16, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(97, 2, 8, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(98, 2, 10, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(99, 2, 9, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(100, 2, 11, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(101, 2, 7, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(102, 2, 13, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(103, 2, 15, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(104, 2, 14, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(105, 2, 12, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(106, 2, 21, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(107, 2, 3, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(108, 2, 5, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(109, 2, 4, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(110, 2, 6, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(111, 2, 2, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(112, 2, 1, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(113, 3, 24, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(114, 3, 25, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(115, 3, 23, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(116, 3, 28, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(117, 3, 22, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(118, 3, 40, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(119, 3, 41, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(120, 3, 39, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(121, 3, 54, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(122, 3, 53, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(123, 3, 51, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(124, 3, 34, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(125, 3, 36, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(126, 3, 35, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(127, 3, 38, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(128, 3, 33, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(129, 3, 50, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(130, 3, 7, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(131, 4, 27, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(132, 4, 24, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(133, 4, 26, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(134, 4, 25, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(135, 4, 23, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(136, 4, 32, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(137, 4, 29, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(138, 4, 31, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(139, 4, 30, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(140, 4, 28, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(141, 4, 22, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(142, 4, 40, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(143, 4, 42, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(144, 4, 41, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(145, 4, 39, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(146, 4, 43, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(147, 4, 54, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(148, 4, 55, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(149, 4, 53, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(150, 4, 51, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(151, 4, 56, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(152, 4, 52, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(155, 4, 50, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(156, 4, 13, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(157, 4, 15, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(158, 4, 14, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(159, 4, 12, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(160, 5, 28, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(161, 5, 22, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(162, 5, 39, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(163, 5, 54, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(164, 5, 53, '2025-09-30 20:20:56', '2025-09-30 20:20:56'),
(165, 5, 50, '2025-09-30 20:20:56', '2025-09-30 20:20:56');

-- --------------------------------------------------------

--
-- Table structure for table `schedules`
--

CREATE TABLE `schedules` (
  `id` bigint UNSIGNED NOT NULL,
  `nurse_id` bigint UNSIGNED NOT NULL,
  `created_by` bigint UNSIGNED NOT NULL,
  `schedule_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `duration_minutes` int NOT NULL,
  `reminder_count` int NOT NULL DEFAULT '0',
  `care_plan_id` bigint UNSIGNED DEFAULT NULL,
  `shift_type` enum('morning_shift','afternoon_shift','evening_shift','night_shift','custom_shift') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'morning_shift',
  `status` enum('scheduled','confirmed','in_progress','cancelled','completed') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'scheduled',
  `nurse_confirmed_at` timestamp NULL DEFAULT NULL,
  `last_reminder_sent` timestamp NULL DEFAULT NULL,
  `shift_notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `location` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `actual_start_time` timestamp NULL DEFAULT NULL,
  `actual_end_time` timestamp NULL DEFAULT NULL,
  `actual_duration_minutes` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `schedules`
--

INSERT INTO `schedules` (`id`, `nurse_id`, `created_by`, `schedule_date`, `start_time`, `end_time`, `duration_minutes`, `reminder_count`, `care_plan_id`, `shift_type`, `status`, `nurse_confirmed_at`, `last_reminder_sent`, `shift_notes`, `location`, `actual_start_time`, `actual_end_time`, `actual_duration_minutes`, `created_at`, `updated_at`, `deleted_at`) VALUES
(366, 62, 2, '2025-10-16', '23:00:00', '07:00:00', 960, 0, 287, 'night_shift', 'scheduled', NULL, NULL, 'Vital signs monitoring', 'Patient Home - Osu', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(367, 64, 2, '2025-10-16', '09:00:00', '17:00:00', 480, 0, 284, 'custom_shift', 'scheduled', NULL, NULL, NULL, 'Patient Home - Labone', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(368, 59, 2, '2025-10-16', '23:00:00', '07:00:00', 960, 0, 294, 'night_shift', 'scheduled', NULL, NULL, 'Elderly care assistance', NULL, NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(369, 53, 2, '2025-10-16', '23:00:00', '07:00:00', 960, 0, 292, 'night_shift', 'scheduled', NULL, NULL, 'Follow-up care visit', 'Patient Home - Airport Residential', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(370, 63, 2, '2025-10-17', '13:00:00', '21:00:00', 480, 0, 276, 'afternoon_shift', 'scheduled', NULL, NULL, 'Medication administration required', 'Patient Home - East Legon', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(371, 63, 2, '2025-10-17', '23:00:00', '07:00:00', 960, 0, 290, 'night_shift', 'scheduled', NULL, NULL, 'Medication administration required', NULL, NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(372, 53, 2, '2025-10-17', '09:00:00', '17:00:00', 480, 0, 293, 'custom_shift', 'completed', NULL, NULL, 'Patient assessment and documentation', 'Patient Home - Airport Residential', '2025-10-17 11:08:16', '2025-10-19 18:46:24', 3338, '2025-10-15 19:47:35', '2025-10-19 18:46:24', NULL),
(373, 56, 2, '2025-10-18', '13:00:00', '21:00:00', 480, 0, 272, 'afternoon_shift', 'scheduled', NULL, NULL, 'Wound care and dressing', 'Patient Home - Airport Residential', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(374, 62, 2, '2025-10-18', '13:00:00', '21:00:00', 480, 0, 276, 'afternoon_shift', 'scheduled', NULL, NULL, 'Post-operative care', 'Patient Home - Airport Residential', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(375, 54, 2, '2025-10-20', '23:00:00', '07:00:00', 960, 0, 271, 'night_shift', 'scheduled', NULL, NULL, 'Post-operative care', 'Patient Home - Airport Residential', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(376, 57, 2, '2025-10-20', '13:00:00', '21:00:00', 480, 0, 268, 'afternoon_shift', 'scheduled', NULL, NULL, NULL, 'Patient Home - Tema', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(377, 64, 2, '2025-10-20', '23:00:00', '07:00:00', 960, 0, 291, 'night_shift', 'scheduled', NULL, NULL, NULL, 'Patient Home - Labone', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(378, 79, 2, '2025-10-20', '09:00:00', '17:00:00', 480, 0, 273, 'custom_shift', 'scheduled', NULL, NULL, NULL, 'Patient Home - Tema', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(379, 60, 2, '2025-10-21', '15:00:00', '23:00:00', 480, 0, 279, 'evening_shift', 'scheduled', NULL, NULL, 'Regular home care visit', 'Patient Home - Labone', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(380, 56, 2, '2025-10-21', '15:00:00', '23:00:00', 480, 0, 267, 'evening_shift', 'scheduled', NULL, NULL, 'Elderly care assistance', 'Patient Home - East Legon', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(381, 79, 2, '2025-10-22', '15:00:00', '23:00:00', 480, 0, 292, 'evening_shift', 'scheduled', NULL, NULL, 'Patient assessment and documentation', 'Patient Home - Airport Residential', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(382, 65, 2, '2025-10-22', '07:00:00', '15:00:00', 480, 0, 285, 'morning_shift', 'scheduled', NULL, NULL, 'Follow-up care visit', 'Patient Home - Tema', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(383, 79, 2, '2025-10-22', '07:00:00', '15:00:00', 480, 0, 273, 'morning_shift', 'scheduled', NULL, NULL, 'Elderly care assistance', 'Patient Home - Osu', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(384, 59, 2, '2025-10-23', '23:00:00', '07:00:00', 960, 0, 294, 'night_shift', 'scheduled', NULL, NULL, 'Physical therapy session', 'Patient Home - Tema', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(385, 60, 2, '2025-10-23', '23:00:00', '07:00:00', 960, 0, 279, 'night_shift', 'scheduled', NULL, NULL, 'Post-operative care', 'Patient Home - Kumasi', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(386, 61, 2, '2025-10-23', '15:00:00', '23:00:00', 480, 0, 267, 'evening_shift', 'scheduled', NULL, NULL, 'Physical therapy session', NULL, NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(387, 56, 2, '2025-10-24', '23:00:00', '07:00:00', 960, 0, 267, 'night_shift', 'scheduled', NULL, NULL, 'Physical therapy session', 'Patient Home - East Legon', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(388, 53, 2, '2025-10-24', '23:00:00', '07:00:00', 960, 0, 293, 'night_shift', 'scheduled', NULL, NULL, NULL, 'Patient Home - Accra', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(389, 58, 2, '2025-10-24', '13:00:00', '21:00:00', 480, 0, 273, 'afternoon_shift', 'scheduled', NULL, NULL, 'Medication administration required', 'Patient Home - Osu', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(390, 67, 2, '2025-10-25', '09:00:00', '17:00:00', 480, 0, 293, 'custom_shift', 'scheduled', NULL, NULL, 'Wound care and dressing', 'Patient Home - East Legon', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(391, 65, 2, '2025-10-25', '15:00:00', '23:00:00', 480, 0, 269, 'evening_shift', 'scheduled', NULL, NULL, 'Wound care and dressing', 'Patient Home - Cantonments', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(392, 57, 2, '2025-10-25', '13:00:00', '21:00:00', 480, 0, 277, 'afternoon_shift', 'scheduled', NULL, NULL, 'Regular home care visit', 'Patient Home - Airport Residential', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(393, 54, 2, '2025-10-25', '07:00:00', '15:00:00', 480, 0, 278, 'morning_shift', 'scheduled', NULL, NULL, 'Follow-up care visit', 'Patient Home - Kumasi', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(394, 79, 2, '2025-10-25', '07:00:00', '15:00:00', 480, 0, 292, 'morning_shift', 'scheduled', NULL, NULL, 'Physical therapy session', 'Patient Home - Labone', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(395, 66, 2, '2025-10-27', '07:00:00', '15:00:00', 480, 0, 270, 'morning_shift', 'scheduled', NULL, NULL, 'Post-operative care', 'Patient Home - Airport Residential', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(396, 54, 2, '2025-10-27', '09:00:00', '17:00:00', 480, 0, 271, 'custom_shift', 'scheduled', NULL, NULL, 'Vital signs monitoring', 'Patient Home - Cantonments', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(397, 63, 2, '2025-10-27', '15:00:00', '23:00:00', 480, 0, 272, 'evening_shift', 'scheduled', NULL, NULL, 'Elderly care assistance', 'Patient Home - Osu', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(398, 60, 2, '2025-10-27', '09:00:00', '17:00:00', 480, 0, 279, 'custom_shift', 'scheduled', NULL, NULL, NULL, 'Patient Home - Tema', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(399, 79, 2, '2025-10-28', '09:00:00', '17:00:00', 480, 0, 292, 'custom_shift', 'scheduled', NULL, NULL, 'Patient assessment and documentation', 'Patient Home - Cantonments', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(401, 66, 2, '2025-10-28', '09:00:00', '17:00:00', 480, 0, 280, 'custom_shift', 'scheduled', NULL, NULL, 'Medication administration required', 'Patient Home - Osu', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(402, 64, 2, '2025-10-29', '09:00:00', '17:00:00', 480, 0, 284, 'custom_shift', 'scheduled', NULL, NULL, NULL, 'Patient Home - Airport Residential', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(403, 79, 2, '2025-10-29', '23:00:00', '07:00:00', 960, 0, 273, 'night_shift', 'scheduled', NULL, NULL, 'Patient assessment and documentation', NULL, NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(404, 67, 2, '2025-10-29', '15:00:00', '23:00:00', 480, 0, 272, 'evening_shift', 'scheduled', NULL, NULL, 'Elderly care assistance', 'Patient Home - Labone', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(405, 55, 2, '2025-10-29', '23:00:00', '07:00:00', 960, 0, 276, 'night_shift', 'scheduled', NULL, NULL, 'Medication administration required', 'Patient Home - Kumasi', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(406, 60, 2, '2025-10-29', '09:00:00', '17:00:00', 480, 0, 279, 'custom_shift', 'scheduled', NULL, NULL, 'Patient assessment and documentation', 'Patient Home - Kumasi', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(407, 67, 2, '2025-10-30', '09:00:00', '17:00:00', 480, 0, 295, 'custom_shift', 'scheduled', NULL, NULL, 'Regular home care visit', NULL, NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(408, 65, 2, '2025-10-30', '13:00:00', '21:00:00', 480, 0, 279, 'afternoon_shift', 'scheduled', NULL, NULL, 'Medication administration required', 'Patient Home - Airport Residential', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(409, 60, 2, '2025-10-30', '23:00:00', '07:00:00', 960, 0, 279, 'night_shift', 'scheduled', NULL, NULL, 'Medication administration required', 'Patient Home - Osu', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(410, 55, 2, '2025-10-31', '13:00:00', '21:00:00', 480, 0, 276, 'afternoon_shift', 'scheduled', NULL, NULL, 'Follow-up care visit', NULL, NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(411, 64, 2, '2025-10-31', '15:00:00', '23:00:00', 480, 0, 291, 'evening_shift', 'scheduled', NULL, NULL, 'Follow-up care visit', 'Patient Home - Kumasi', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(412, 56, 2, '2025-10-31', '09:00:00', '17:00:00', 480, 0, 283, 'custom_shift', 'scheduled', NULL, NULL, 'Patient assessment and documentation', 'Patient Home - Labone', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(413, 59, 2, '2025-11-01', '15:00:00', '23:00:00', 480, 0, 294, 'evening_shift', 'scheduled', NULL, NULL, 'Post-operative care', 'Patient Home - East Legon', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(414, 62, 2, '2025-11-01', '13:00:00', '21:00:00', 480, 0, 287, 'afternoon_shift', 'scheduled', NULL, NULL, 'Medication administration required', 'Patient Home - Labone', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(415, 67, 2, '2025-11-01', '23:00:00', '07:00:00', 960, 0, 267, 'night_shift', 'scheduled', NULL, NULL, 'Vital signs monitoring', NULL, NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(416, 53, 2, '2025-11-03', '15:00:00', '23:00:00', 480, 0, 296, 'evening_shift', 'completed', NULL, NULL, 'Elderly care assistance', 'Patient Home - Cantonments', '2025-11-03 16:03:24', '2025-11-03 16:07:23', NULL, '2025-10-15 19:47:35', '2025-11-03 16:07:23', NULL),
(417, 64, 2, '2025-11-03', '13:00:00', '21:00:00', 480, 0, 284, 'afternoon_shift', 'scheduled', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(418, 59, 2, '2025-11-03', '09:00:00', '17:00:00', 480, 0, 290, 'custom_shift', 'scheduled', NULL, NULL, 'Post-operative care', 'Patient Home - Osu', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(419, 61, 2, '2025-11-04', '13:00:00', '21:00:00', 480, 0, 289, 'afternoon_shift', 'scheduled', NULL, NULL, NULL, 'Patient Home - East Legon', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(420, 67, 2, '2025-11-05', '13:00:00', '21:00:00', 480, 0, 273, 'afternoon_shift', 'scheduled', NULL, NULL, NULL, 'Patient Home - Osu', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(421, 64, 2, '2025-11-05', '09:00:00', '17:00:00', 480, 0, 291, 'custom_shift', 'scheduled', NULL, NULL, 'Medication administration required', 'Patient Home - East Legon', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(422, 53, 2, '2025-11-05', '23:00:00', '07:00:00', 960, 0, 280, 'night_shift', 'scheduled', NULL, NULL, 'Follow-up care visit', 'Patient Home - Tema', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(423, 60, 2, '2025-11-05', '09:00:00', '17:00:00', 480, 0, 279, 'custom_shift', 'scheduled', NULL, NULL, 'Wound care and dressing', 'Patient Home - Tema', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(424, 58, 2, '2025-11-06', '13:00:00', '21:00:00', 480, 0, 286, 'afternoon_shift', 'scheduled', NULL, NULL, 'Wound care and dressing', 'Patient Home - East Legon', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(425, 65, 2, '2025-11-06', '09:00:00', '17:00:00', 480, 0, 288, 'custom_shift', 'scheduled', NULL, NULL, 'Patient assessment and documentation', 'Patient Home - East Legon', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(426, 54, 2, '2025-11-06', '23:00:00', '07:00:00', 960, 0, 278, 'night_shift', 'scheduled', NULL, NULL, 'Patient assessment and documentation', 'Patient Home - Accra', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(427, 64, 2, '2025-11-06', '23:00:00', '07:00:00', 960, 0, 284, 'night_shift', 'scheduled', NULL, NULL, NULL, 'Patient Home - Tema', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(428, 59, 2, '2025-11-06', '09:00:00', '17:00:00', 480, 0, 274, 'custom_shift', 'scheduled', NULL, NULL, 'Vital signs monitoring', 'Patient Home - Labone', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(429, 53, 2, '2025-11-07', '07:00:00', '15:00:00', 480, 0, 280, 'morning_shift', 'scheduled', NULL, NULL, 'Regular home care visit', 'Patient Home - Airport Residential', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(430, 58, 2, '2025-11-07', '09:00:00', '17:00:00', 480, 0, 273, 'custom_shift', 'scheduled', NULL, NULL, 'Physical therapy session', NULL, NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(431, 55, 2, '2025-11-07', '09:00:00', '17:00:00', 480, 0, 281, 'custom_shift', 'scheduled', NULL, NULL, 'Post-operative care', 'Patient Home - East Legon', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(432, 64, 2, '2025-11-07', '07:00:00', '15:00:00', 480, 0, 291, 'morning_shift', 'scheduled', NULL, NULL, 'Patient assessment and documentation', 'Patient Home - Kumasi', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(433, 54, 2, '2025-11-07', '09:00:00', '17:00:00', 480, 0, 271, 'custom_shift', 'scheduled', NULL, NULL, 'Vital signs monitoring', 'Patient Home - Labone', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(434, 54, 2, '2025-11-08', '07:00:00', '15:00:00', 480, 0, 271, 'morning_shift', 'scheduled', NULL, NULL, 'Follow-up care visit', 'Patient Home - East Legon', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(435, 4, 2, '2025-10-17', '08:00:00', '10:00:00', 120, 0, 270, 'morning_shift', 'completed', NULL, NULL, NULL, 'Patient Home - Labone', '2025-10-17 09:12:59', '2025-10-17 09:16:47', NULL, '2025-10-15 19:47:35', '2025-10-17 09:16:47', NULL),
(436, 64, 2, '2025-11-10', '13:00:00', '21:00:00', 480, 0, 291, 'afternoon_shift', 'scheduled', NULL, NULL, 'Vital signs monitoring', 'Patient Home - Kumasi', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(437, 54, 2, '2025-11-10', '13:00:00', '21:00:00', 480, 0, 271, 'afternoon_shift', 'scheduled', NULL, NULL, 'Medication administration required', 'Patient Home - Airport Residential', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(438, 67, 2, '2025-11-10', '23:00:00', '07:00:00', 960, 0, 268, 'night_shift', 'scheduled', NULL, NULL, 'Vital signs monitoring', 'Patient Home - Accra', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(439, 63, 2, '2025-11-10', '13:00:00', '21:00:00', 480, 0, 270, 'afternoon_shift', 'scheduled', NULL, NULL, 'Follow-up care visit', 'Patient Home - Accra', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(440, 60, 2, '2025-11-11', '23:00:00', '07:00:00', 960, 0, 279, 'night_shift', 'scheduled', NULL, NULL, 'Patient assessment and documentation', 'Patient Home - Accra', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(441, 4, 2, '2025-10-17', '11:00:00', '12:00:00', 60, 0, 270, 'morning_shift', 'completed', NULL, NULL, 'Elderly care assistance', 'Patient Home - East Legon', '2025-10-17 09:22:08', '2025-10-17 09:24:22', NULL, '2025-10-15 19:47:35', '2025-10-17 09:24:22', NULL),
(442, 58, 2, '2025-11-11', '15:00:00', '23:00:00', 480, 0, 273, 'evening_shift', 'scheduled', NULL, NULL, 'Post-operative care', 'Patient Home - Labone', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(443, 66, 2, '2025-11-11', '07:00:00', '15:00:00', 480, 0, 270, 'morning_shift', 'scheduled', NULL, NULL, 'Physical therapy session', 'Patient Home - Osu', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(444, 53, 2, '2025-11-11', '09:00:00', '17:00:00', 480, 0, 296, 'custom_shift', 'scheduled', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(445, 60, 2, '2025-11-12', '13:00:00', '21:00:00', 480, 0, 279, 'afternoon_shift', 'scheduled', NULL, NULL, 'Physical therapy session', 'Patient Home - Osu', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(446, 58, 2, '2025-11-12', '13:00:00', '21:00:00', 480, 0, 286, 'afternoon_shift', 'scheduled', NULL, NULL, 'Elderly care assistance', 'Patient Home - Airport Residential', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(447, 79, 2, '2025-11-13', '15:00:00', '23:00:00', 480, 0, 273, 'evening_shift', 'scheduled', NULL, NULL, 'Elderly care assistance', 'Patient Home - Tema', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(448, 57, 2, '2025-11-13', '09:00:00', '17:00:00', 480, 0, 272, 'custom_shift', 'scheduled', NULL, NULL, 'Follow-up care visit', 'Patient Home - Airport Residential', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(449, 61, 2, '2025-11-14', '09:00:00', '17:00:00', 480, 0, 289, 'custom_shift', 'scheduled', NULL, NULL, 'Patient assessment and documentation', 'Patient Home - Cantonments', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(450, 79, 2, '2025-11-14', '13:00:00', '21:00:00', 480, 0, 292, 'afternoon_shift', 'scheduled', NULL, NULL, 'Post-operative care', 'Patient Home - Airport Residential', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(451, 55, 2, '2025-11-14', '07:00:00', '15:00:00', 480, 0, 276, 'morning_shift', 'scheduled', NULL, NULL, 'Physical therapy session', 'Patient Home - Cantonments', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(452, 57, 2, '2025-11-14', '15:00:00', '23:00:00', 480, 0, 277, 'evening_shift', 'scheduled', NULL, NULL, 'Wound care and dressing', NULL, NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(453, 53, 2, '2025-11-15', '13:00:00', '21:00:00', 480, 0, 286, 'afternoon_shift', 'scheduled', NULL, NULL, 'Physical therapy session', 'Patient Home - Cantonments', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(454, 58, 2, '2025-11-15', '07:00:00', '15:00:00', 480, 0, 286, 'morning_shift', 'scheduled', NULL, NULL, 'Patient assessment and documentation', 'Patient Home - East Legon', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(455, 59, 2, '2025-11-15', '07:00:00', '15:00:00', 480, 0, 290, 'morning_shift', 'scheduled', NULL, NULL, 'Medication administration required', 'Patient Home - Kumasi', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(456, 62, 2, '2025-11-17', '07:00:00', '15:00:00', 480, 0, 276, 'morning_shift', 'scheduled', NULL, NULL, 'Vital signs monitoring', 'Patient Home - Airport Residential', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(457, 64, 2, '2025-11-17', '23:00:00', '07:00:00', 960, 0, 284, 'night_shift', 'scheduled', NULL, NULL, 'Follow-up care visit', 'Patient Home - Cantonments', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(458, 64, 2, '2025-11-18', '13:00:00', '21:00:00', 480, 0, 291, 'afternoon_shift', 'scheduled', NULL, NULL, 'Wound care and dressing', 'Patient Home - Accra', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(459, 57, 2, '2025-11-18', '07:00:00', '15:00:00', 480, 0, 277, 'morning_shift', 'scheduled', NULL, NULL, 'Regular home care visit', 'Patient Home - Tema', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(460, 4, 2, '2025-10-17', '13:00:00', '14:00:00', 60, 0, 278, 'afternoon_shift', 'completed', NULL, NULL, 'Elderly care assistance', 'East Legon', '2025-10-17 09:26:04', '2025-10-17 09:27:10', NULL, '2025-10-15 19:47:35', '2025-10-17 09:27:10', NULL),
(461, 66, 2, '2025-11-19', '15:00:00', '23:00:00', 480, 0, 280, 'evening_shift', 'scheduled', NULL, NULL, 'Follow-up care visit', 'Patient Home - Airport Residential', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(462, 57, 2, '2025-11-19', '23:00:00', '07:00:00', 960, 0, 277, 'night_shift', 'scheduled', NULL, NULL, 'Regular home care visit', NULL, NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(463, 64, 2, '2025-11-20', '09:00:00', '17:00:00', 480, 0, 291, 'custom_shift', 'scheduled', NULL, NULL, NULL, 'Patient Home - Tema', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(464, 56, 2, '2025-11-20', '07:00:00', '15:00:00', 480, 0, 272, 'morning_shift', 'scheduled', NULL, NULL, 'Post-operative care', 'Patient Home - Accra', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(465, 59, 2, '2025-11-20', '23:00:00', '07:00:00', 960, 0, 290, 'night_shift', 'scheduled', NULL, NULL, 'Regular home care visit', 'Patient Home - Accra', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(466, 61, 2, '2025-11-20', '09:00:00', '17:00:00', 480, 0, 289, 'custom_shift', 'scheduled', NULL, NULL, 'Follow-up care visit', 'Patient Home - Kumasi', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(467, 55, 2, '2025-11-21', '15:00:00', '23:00:00', 480, 0, 281, 'evening_shift', 'scheduled', NULL, NULL, 'Patient assessment and documentation', 'Patient Home - Osu', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(468, 65, 2, '2025-11-21', '07:00:00', '15:00:00', 480, 0, 285, 'morning_shift', 'scheduled', NULL, NULL, 'Elderly care assistance', 'Patient Home - Labone', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(469, 60, 2, '2025-11-21', '09:00:00', '17:00:00', 480, 0, 279, 'custom_shift', 'scheduled', NULL, NULL, 'Patient assessment and documentation', 'Patient Home - East Legon', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(470, 53, 2, '2025-11-21', '07:00:00', '15:00:00', 480, 0, 293, 'morning_shift', 'scheduled', NULL, NULL, NULL, 'Patient Home - Airport Residential', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(471, 56, 2, '2025-11-22', '07:00:00', '15:00:00', 480, 0, 272, 'morning_shift', 'scheduled', NULL, NULL, 'Medication administration required', 'Patient Home - East Legon', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(472, 58, 2, '2025-11-22', '23:00:00', '07:00:00', 960, 0, 286, 'night_shift', 'scheduled', NULL, NULL, 'Follow-up care visit', 'Patient Home - Airport Residential', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(473, 62, 2, '2025-11-24', '23:00:00', '07:00:00', 960, 0, 276, 'night_shift', 'scheduled', NULL, NULL, 'Patient assessment and documentation', 'Patient Home - Kumasi', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(474, 55, 2, '2025-11-24', '23:00:00', '07:00:00', 960, 0, 281, 'night_shift', 'scheduled', NULL, NULL, 'Wound care and dressing', 'Patient Home - East Legon', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(475, 64, 2, '2025-11-24', '13:00:00', '21:00:00', 480, 0, 291, 'afternoon_shift', 'scheduled', NULL, NULL, 'Patient assessment and documentation', NULL, NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(476, 58, 2, '2025-11-25', '15:00:00', '23:00:00', 480, 0, 286, 'evening_shift', 'scheduled', NULL, NULL, 'Wound care and dressing', 'Patient Home - East Legon', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(477, 54, 2, '2025-11-25', '09:00:00', '17:00:00', 480, 0, 278, 'custom_shift', 'scheduled', NULL, NULL, 'Vital signs monitoring', 'Patient Home - Accra', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(478, 59, 2, '2025-11-25', '07:00:00', '15:00:00', 480, 0, 290, 'morning_shift', 'scheduled', NULL, NULL, 'Post-operative care', 'Patient Home - Labone', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(479, 63, 2, '2025-11-26', '23:00:00', '07:00:00', 960, 0, 295, 'night_shift', 'scheduled', NULL, NULL, 'Elderly care assistance', 'Patient Home - Tema', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(480, 67, 2, '2025-11-26', '13:00:00', '21:00:00', 480, 0, 290, 'afternoon_shift', 'scheduled', NULL, NULL, 'Patient assessment and documentation', 'Patient Home - Cantonments', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(481, 59, 2, '2025-11-27', '13:00:00', '21:00:00', 480, 0, 274, 'afternoon_shift', 'scheduled', NULL, NULL, 'Medication administration required', 'Patient Home - East Legon', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(482, 64, 2, '2025-11-27', '09:00:00', '17:00:00', 480, 0, 291, 'custom_shift', 'scheduled', NULL, NULL, 'Physical therapy session', 'Patient Home - East Legon', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(483, 79, 2, '2025-11-27', '23:00:00', '07:00:00', 960, 0, 273, 'night_shift', 'scheduled', NULL, NULL, 'Post-operative care', NULL, NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(484, 57, 2, '2025-11-28', '09:00:00', '17:00:00', 480, 0, 277, 'custom_shift', 'scheduled', NULL, NULL, 'Follow-up care visit', 'Patient Home - Kumasi', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(485, 4, 2, '2025-10-17', '15:00:00', '17:00:00', 120, 0, 282, 'custom_shift', 'completed', NULL, NULL, 'Medication administration required', 'Patient Home - Tema', '2025-10-17 09:46:59', '2025-10-17 09:47:15', NULL, '2025-10-15 19:47:35', '2025-10-17 09:47:15', NULL),
(486, 59, 2, '2025-11-28', '07:00:00', '15:00:00', 480, 0, 290, 'morning_shift', 'scheduled', NULL, NULL, NULL, 'Patient Home - Tema', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(487, 60, 2, '2025-11-28', '15:00:00', '23:00:00', 480, 0, 279, 'evening_shift', 'scheduled', NULL, NULL, 'Vital signs monitoring', 'Patient Home - East Legon', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(488, 62, 2, '2025-11-29', '13:00:00', '21:00:00', 480, 0, 287, 'afternoon_shift', 'scheduled', NULL, NULL, 'Regular home care visit', 'Patient Home - Labone', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(489, 79, 2, '2025-11-29', '13:00:00', '21:00:00', 480, 0, 273, 'afternoon_shift', 'scheduled', NULL, NULL, 'Medication administration required', 'Patient Home - Cantonments', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(490, 4, 2, '2025-10-28', '09:00:00', '14:00:00', 300, 1, 278, 'morning_shift', 'completed', NULL, '2025-10-15 21:13:00', 'Vital signs monitoring', NULL, '2025-10-28 10:04:53', '2025-10-28 10:31:37', 27, '2025-10-15 19:47:35', '2025-10-28 10:31:37', NULL),
(491, 54, 2, '2025-12-01', '07:00:00', '15:00:00', 480, 0, 278, 'morning_shift', 'scheduled', NULL, NULL, 'Wound care and dressing', 'Patient Home - East Legon', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(492, 56, 2, '2025-12-01', '09:00:00', '17:00:00', 480, 0, 272, 'custom_shift', 'scheduled', NULL, NULL, 'Follow-up care visit', 'Patient Home - Cantonments', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(493, 61, 2, '2025-12-01', '15:00:00', '23:00:00', 480, 0, 271, 'evening_shift', 'scheduled', NULL, NULL, 'Medication administration required', 'Patient Home - Kumasi', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(494, 54, 2, '2025-12-02', '15:00:00', '23:00:00', 480, 0, 271, 'evening_shift', 'scheduled', NULL, NULL, 'Wound care and dressing', 'Patient Home - Labone', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(495, 56, 2, '2025-12-02', '13:00:00', '21:00:00', 480, 0, 272, 'afternoon_shift', 'scheduled', NULL, NULL, 'Regular home care visit', 'Patient Home - Osu', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(496, 61, 2, '2025-12-02', '23:00:00', '07:00:00', 960, 0, 275, 'night_shift', 'scheduled', NULL, NULL, 'Medication administration required', 'Patient Home - Tema', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(497, 54, 2, '2025-12-02', '23:00:00', '07:00:00', 960, 0, 271, 'night_shift', 'scheduled', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(498, 64, 2, '2025-12-03', '15:00:00', '23:00:00', 480, 0, 291, 'evening_shift', 'scheduled', NULL, NULL, 'Vital signs monitoring', 'Patient Home - Accra', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(499, 62, 2, '2025-12-03', '15:00:00', '23:00:00', 480, 0, 276, 'evening_shift', 'scheduled', NULL, NULL, NULL, 'Patient Home - Airport Residential', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(500, 60, 2, '2025-12-03', '15:00:00', '23:00:00', 480, 0, 279, 'evening_shift', 'scheduled', NULL, NULL, 'Follow-up care visit', 'Patient Home - Accra', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(501, 67, 2, '2025-12-04', '09:00:00', '17:00:00', 480, 0, 271, 'custom_shift', 'scheduled', NULL, NULL, 'Post-operative care', 'Patient Home - Cantonments', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(502, 60, 2, '2025-12-04', '07:00:00', '15:00:00', 480, 0, 279, 'morning_shift', 'scheduled', NULL, NULL, 'Regular home care visit', 'Patient Home - Osu', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(503, 62, 2, '2025-12-05', '09:00:00', '17:00:00', 480, 0, 287, 'custom_shift', 'scheduled', NULL, NULL, 'Patient assessment and documentation', 'Patient Home - Tema', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(504, 59, 2, '2025-12-05', '07:00:00', '15:00:00', 480, 0, 294, 'morning_shift', 'scheduled', NULL, NULL, 'Medication administration required', 'Patient Home - Labone', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(505, 58, 2, '2025-12-05', '07:00:00', '15:00:00', 480, 0, 286, 'morning_shift', 'scheduled', NULL, NULL, 'Patient assessment and documentation', 'Patient Home - Tema', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(506, 55, 2, '2025-12-06', '09:00:00', '17:00:00', 480, 0, 276, 'custom_shift', 'scheduled', NULL, NULL, 'Medication administration required', 'Patient Home - Tema', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(507, 62, 2, '2025-12-06', '15:00:00', '23:00:00', 480, 0, 287, 'evening_shift', 'scheduled', NULL, NULL, 'Regular home care visit', 'Patient Home - Osu', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(508, 65, 2, '2025-12-08', '13:00:00', '21:00:00', 480, 0, 269, 'afternoon_shift', 'scheduled', NULL, NULL, 'Patient assessment and documentation', 'Patient Home - Tema', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(509, 54, 2, '2025-12-08', '07:00:00', '15:00:00', 480, 0, 271, 'morning_shift', 'scheduled', NULL, NULL, 'Wound care and dressing', 'Patient Home - East Legon', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(510, 62, 2, '2025-12-08', '13:00:00', '21:00:00', 480, 0, 287, 'afternoon_shift', 'scheduled', NULL, NULL, 'Post-operative care', 'Patient Home - Airport Residential', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(511, 56, 2, '2025-12-08', '15:00:00', '23:00:00', 480, 0, 283, 'evening_shift', 'scheduled', NULL, NULL, 'Post-operative care', NULL, NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(512, 62, 2, '2025-12-09', '15:00:00', '23:00:00', 480, 0, 287, 'evening_shift', 'scheduled', NULL, NULL, 'Wound care and dressing', 'Patient Home - Accra', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(513, 64, 2, '2025-12-09', '23:00:00', '07:00:00', 960, 0, 291, 'night_shift', 'scheduled', NULL, NULL, NULL, 'Patient Home - Osu', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(514, 63, 2, '2025-12-10', '15:00:00', '23:00:00', 480, 0, 272, 'evening_shift', 'scheduled', NULL, NULL, 'Wound care and dressing', 'Patient Home - Cantonments', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(515, 61, 2, '2025-12-10', '13:00:00', '21:00:00', 480, 0, 295, 'afternoon_shift', 'scheduled', NULL, NULL, 'Wound care and dressing', 'Patient Home - East Legon', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(516, 57, 2, '2025-12-10', '07:00:00', '15:00:00', 480, 0, 268, 'morning_shift', 'scheduled', NULL, NULL, 'Wound care and dressing', NULL, NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 21:06:39', NULL),
(517, 53, 2, '2025-12-10', '07:00:00', '15:00:00', 480, 0, 280, 'morning_shift', 'scheduled', NULL, NULL, 'Elderly care assistance', 'Patient Home - Airport Residential', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(518, 66, 2, '2025-12-10', '07:00:00', '15:00:00', 480, 0, 280, 'morning_shift', 'scheduled', NULL, NULL, 'Patient assessment and documentation', 'Patient Home - Cantonments', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(519, 62, 2, '2025-12-11', '09:00:00', '17:00:00', 480, 0, 276, 'custom_shift', 'scheduled', NULL, NULL, 'Follow-up care visit', 'Patient Home - Kumasi', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(520, 58, 2, '2025-12-11', '15:00:00', '23:00:00', 480, 0, 273, 'evening_shift', 'scheduled', NULL, NULL, 'Vital signs monitoring', 'Patient Home - Airport Residential', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(521, 61, 2, '2025-12-11', '23:00:00', '07:00:00', 960, 0, 289, 'night_shift', 'scheduled', NULL, NULL, 'Regular home care visit', 'Patient Home - Airport Residential', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(522, 54, 2, '2025-12-11', '07:00:00', '15:00:00', 480, 0, 278, 'morning_shift', 'scheduled', NULL, NULL, NULL, 'Patient Home - East Legon', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(523, 61, 2, '2025-12-12', '13:00:00', '21:00:00', 480, 0, 289, 'afternoon_shift', 'scheduled', NULL, NULL, 'Vital signs monitoring', 'Patient Home - Tema', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(524, 64, 2, '2025-12-12', '15:00:00', '23:00:00', 480, 0, 291, 'evening_shift', 'scheduled', NULL, NULL, 'Vital signs monitoring', NULL, NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 20:29:04', NULL),
(525, 54, 2, '2025-12-12', '09:00:00', '17:00:00', 480, 0, 278, 'custom_shift', 'scheduled', NULL, NULL, NULL, 'Patient Home - Cantonments', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(526, 63, 2, '2025-12-12', '15:00:00', '23:00:00', 480, 0, 291, 'evening_shift', 'scheduled', NULL, NULL, 'Elderly care assistance', 'Patient Home - Labone', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 20:41:44', NULL),
(527, 57, 2, '2025-12-12', '23:00:00', '07:00:00', 960, 0, 277, 'night_shift', 'scheduled', NULL, NULL, 'Medication administration required', 'Patient Home - Labone', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(528, 79, 2, '2025-12-13', '13:00:00', '21:00:00', 480, 0, 292, 'afternoon_shift', 'scheduled', NULL, NULL, 'Wound care and dressing', 'Patient Home - Labone Danta', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 20:27:11', NULL),
(529, 54, 2, '2025-12-13', '15:00:00', '23:00:00', 480, 0, 271, 'evening_shift', 'scheduled', NULL, NULL, 'Medication administration required', 'Patient Home - Cantonments', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 20:28:30', NULL),
(530, 4, 2, '2025-10-15', '08:00:00', '16:00:00', 480, 0, 270, 'morning_shift', 'scheduled', NULL, NULL, 'Today\'s scheduled visit - for testing', 'Patient Home - Test Location', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(531, 53, 2, '2025-10-15', '10:00:00', '18:00:00', 480, 0, 286, 'morning_shift', 'completed', NULL, NULL, 'Today\'s scheduled visit - for testing', 'Patient Home - Test Location', '2025-10-15 21:56:25', '2025-10-15 22:32:46', 36, '2025-10-15 19:47:35', '2025-10-15 22:32:46', NULL),
(532, 54, 2, '2025-10-15', '14:00:00', '22:00:00', 480, 0, 271, 'morning_shift', 'scheduled', NULL, NULL, 'Today\'s scheduled visit - for testing', 'Patient Home - Test Location', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(533, 4, 2, '2025-10-16', '07:00:00', '15:00:00', 480, 0, 278, 'morning_shift', 'completed', NULL, NULL, 'Tomorrow\'s visit - ready for reminders', 'Patient Home - Cantonments', '2025-10-16 09:49:44', '2025-10-16 18:25:59', 516, '2025-10-15 19:47:35', '2025-10-16 18:25:59', NULL),
(534, 54, 2, '2025-10-16', '07:00:00', '15:00:00', 480, 0, 271, 'morning_shift', 'scheduled', NULL, NULL, 'Tomorrow\'s visit - ready for reminders', 'Patient Home - Kumasi', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(535, 55, 2, '2025-10-16', '07:00:00', '15:00:00', 480, 0, 281, 'morning_shift', 'scheduled', NULL, NULL, 'Tomorrow\'s visit - ready for reminders', 'Patient Home - Labone', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(536, 56, 2, '2025-10-16', '07:00:00', '15:00:00', 480, 0, 283, 'morning_shift', 'scheduled', NULL, NULL, 'Tomorrow\'s visit - ready for reminders', NULL, NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(537, 4, 2, '2025-10-17', '18:30:00', '19:30:00', 60, 0, 282, 'evening_shift', 'completed', NULL, NULL, NULL, 'Labone Street', '2025-10-17 11:25:51', '2025-10-19 18:46:29', 3321, '2025-10-17 09:52:11', '2025-10-19 18:46:29', NULL),
(538, 4, 2, '2025-10-20', '08:00:00', '11:30:00', 210, 0, 282, 'morning_shift', 'completed', NULL, NULL, 'Patient needs to serious attention', 'Labone Street', '2025-10-20 17:43:42', '2025-10-27 11:40:27', 9717, '2025-10-17 09:53:34', '2025-10-27 11:40:27', NULL),
(539, 4, 2, '2025-10-19', '22:00:00', '23:00:00', 60, 0, 282, 'night_shift', 'completed', NULL, NULL, 'Shift notes', 'WestLands', '2025-10-19 18:51:56', '2025-10-20 17:42:50', 1371, '2025-10-17 10:59:19', '2025-10-20 17:42:50', NULL),
(540, 4, 2, '2025-10-30', '19:00:00', '23:30:00', 270, 0, 270, 'night_shift', 'completed', NULL, NULL, NULL, 'Adenta', '2025-10-30 22:54:56', '2025-10-30 23:03:44', NULL, '2025-10-19 18:53:31', '2025-10-30 23:03:44', NULL),
(541, 4, 2, '2025-10-28', '18:00:00', '22:00:00', 240, 0, 278, 'evening_shift', 'completed', NULL, NULL, NULL, 'Spintex', '2025-10-28 09:24:22', '2025-10-28 09:54:27', 30, '2025-10-19 18:55:46', '2025-10-28 09:54:27', NULL),
(542, 4, 2, '2025-10-29', '08:00:00', '11:30:00', 210, 0, 282, 'morning_shift', 'completed', NULL, NULL, 'This is an instruction', 'East Cantonments', '2025-10-29 15:50:52', '2025-10-29 16:06:43', NULL, '2025-10-29 15:50:02', '2025-10-29 16:06:43', NULL),
(543, 4, 2, '2025-10-31', '09:00:00', '12:00:00', 180, 0, 282, 'morning_shift', 'completed', NULL, NULL, 'Special notes from the doctor', 'EAST LEGON', '2025-10-31 18:20:13', '2025-10-31 20:03:30', NULL, '2025-10-30 22:41:51', '2025-10-31 20:03:30', NULL),
(544, 4, 2, '2025-11-03', '14:00:00', '17:00:00', 180, 0, 270, 'afternoon_shift', 'scheduled', NULL, NULL, NULL, 'Cantonments', NULL, NULL, NULL, '2025-11-02 10:06:07', '2025-11-02 10:12:49', NULL),
(545, 4, 2, '2025-11-02', '18:00:00', '22:00:00', 240, 0, 278, 'evening_shift', 'completed', NULL, NULL, NULL, 'Adenta Housing Estate, Pantang Road', '2025-11-02 14:33:14', '2025-11-03 10:08:05', NULL, '2025-11-02 14:19:51', '2025-11-03 10:08:05', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `time_trackings`
--

CREATE TABLE `time_trackings` (
  `id` bigint UNSIGNED NOT NULL,
  `nurse_id` bigint UNSIGNED NOT NULL,
  `schedule_id` bigint UNSIGNED DEFAULT NULL,
  `patient_id` bigint UNSIGNED DEFAULT NULL,
  `care_plan_id` bigint UNSIGNED DEFAULT NULL,
  `start_time` timestamp NULL DEFAULT NULL,
  `end_time` timestamp NULL DEFAULT NULL,
  `paused_at` timestamp NULL DEFAULT NULL,
  `total_duration_minutes` int NOT NULL DEFAULT '0',
  `total_pause_duration_minutes` int NOT NULL DEFAULT '0',
  `status` enum('active','paused','completed','cancelled') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `session_type` enum('scheduled_shift','emergency_call','overtime','break_coverage') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'scheduled_shift',
  `clock_in_location` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `clock_out_location` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `clock_in_latitude` decimal(10,8) DEFAULT NULL,
  `clock_in_longitude` decimal(11,8) DEFAULT NULL,
  `clock_out_latitude` decimal(10,8) DEFAULT NULL,
  `clock_out_longitude` decimal(11,8) DEFAULT NULL,
  `work_notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `pause_reason` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `activities_performed` json DEFAULT NULL,
  `break_count` int NOT NULL DEFAULT '0',
  `total_break_minutes` int NOT NULL DEFAULT '0',
  `requires_approval` tinyint(1) NOT NULL DEFAULT '0',
  `approved_by` bigint UNSIGNED DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `approval_notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `clock_in_ip` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `clock_out_ip` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `device_info` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `time_trackings`
--

INSERT INTO `time_trackings` (`id`, `nurse_id`, `schedule_id`, `patient_id`, `care_plan_id`, `start_time`, `end_time`, `paused_at`, `total_duration_minutes`, `total_pause_duration_minutes`, `status`, `session_type`, `clock_in_location`, `clock_out_location`, `clock_in_latitude`, `clock_in_longitude`, `clock_out_latitude`, `clock_out_longitude`, `work_notes`, `pause_reason`, `activities_performed`, `break_count`, `total_break_minutes`, `requires_approval`, `approved_by`, `approved_at`, `approval_notes`, `clock_in_ip`, `clock_out_ip`, `device_info`, `created_at`, `updated_at`, `deleted_at`) VALUES
(11, 53, 531, 19, 286, '2025-10-15 21:56:25', '2025-10-15 22:32:46', NULL, 36, 0, 'completed', 'scheduled_shift', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, NULL, NULL, NULL, '127.0.0.1', '127.0.0.1', NULL, '2025-10-15 21:56:25', '2025-10-15 22:32:46', NULL),
(12, 4, 533, 5, 278, '2025-10-16 09:49:44', '2025-10-16 18:25:59', NULL, 516, 0, 'completed', 'scheduled_shift', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, NULL, NULL, NULL, '127.0.0.1', '127.0.0.1', NULL, '2025-10-16 09:49:44', '2025-10-16 18:25:59', NULL),
(14, 4, 435, 7, 270, '2025-10-17 09:12:59', '2025-10-17 09:16:47', NULL, 4, 0, 'completed', 'scheduled_shift', 'Lat: 5.664704, Long: -0.219627', 'Lat: 5.664704, Long: -0.219627', 5.66470400, -0.21962664, 5.66470400, -0.21962664, NULL, NULL, NULL, 0, 0, 0, NULL, NULL, NULL, '127.0.0.1', '127.0.0.1', 'Flutter Mobile App', '2025-10-17 09:12:59', '2025-10-17 09:16:47', NULL),
(15, 4, 441, 7, 270, '2025-10-17 09:22:08', '2025-10-17 09:24:22', NULL, 2, 0, 'completed', 'scheduled_shift', 'Lat: 5.664704, Long: -0.219627', 'Dome, Accra', 5.66470400, -0.21962664, 5.66470400, -0.21962664, NULL, NULL, NULL, 0, 0, 0, NULL, NULL, NULL, '127.0.0.1', '127.0.0.1', 'Flutter Mobile App', '2025-10-17 09:22:08', '2025-10-17 09:24:22', NULL),
(16, 4, 460, 5, 278, '2025-10-17 09:26:04', '2025-10-17 09:27:10', NULL, 1, 0, 'completed', 'scheduled_shift', 'Dome, Accra', 'Dome, Accra', 5.66470400, -0.21962664, 5.66470400, -0.21962664, NULL, NULL, NULL, 0, 0, 0, NULL, NULL, NULL, '127.0.0.1', '127.0.0.1', 'Flutter Mobile App', '2025-10-17 09:26:04', '2025-10-17 09:27:10', NULL),
(17, 4, 485, 49, 282, '2025-10-17 09:46:59', '2025-10-17 09:47:15', NULL, 0, 0, 'completed', 'scheduled_shift', 'Dome, Accra', 'Dome, Accra', 5.66470400, -0.21962664, 5.66470400, -0.21962664, NULL, NULL, NULL, 0, 0, 0, NULL, NULL, NULL, '127.0.0.1', '127.0.0.1', 'Flutter Mobile App', '2025-10-17 09:46:59', '2025-10-17 09:47:15', NULL),
(18, 53, 372, 42, 293, '2025-10-17 11:08:16', '2025-10-19 18:46:24', NULL, 3338, 0, 'completed', 'scheduled_shift', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, NULL, NULL, NULL, '127.0.0.1', '127.0.0.1', NULL, '2025-10-17 11:08:16', '2025-10-19 18:46:24', NULL),
(19, 4, 537, 49, 282, '2025-10-17 11:25:51', '2025-10-19 18:46:29', NULL, 3321, 0, 'completed', 'scheduled_shift', 'Dome, Accra', NULL, 5.66470400, -0.21962664, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, NULL, NULL, NULL, '127.0.0.1', '127.0.0.1', 'Flutter Mobile App', '2025-10-17 11:25:51', '2025-10-19 18:46:29', NULL),
(20, 4, 539, 49, 282, '2025-10-19 18:51:56', '2025-10-20 17:42:50', NULL, 1371, 0, 'completed', 'scheduled_shift', 'Dome, Accra', NULL, 5.66470400, -0.21962664, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, NULL, NULL, NULL, '127.0.0.1', '127.0.0.1', 'Flutter Mobile App', '2025-10-19 18:51:56', '2025-10-20 17:42:50', NULL),
(21, 4, 538, 49, 282, '2025-10-20 17:43:42', '2025-10-27 11:40:27', NULL, 9717, 0, 'completed', 'scheduled_shift', 'Dome, Accra', NULL, 5.66470400, -0.21962664, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, NULL, NULL, NULL, '127.0.0.1', '127.0.0.1', 'Flutter Mobile App', '2025-10-20 17:43:42', '2025-10-27 11:40:27', NULL),
(22, 4, 541, 5, 278, '2025-10-28 09:24:22', '2025-10-28 09:54:27', NULL, 30, 0, 'completed', 'scheduled_shift', 'Dome, Accra', NULL, 5.66470400, -0.21962664, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, NULL, NULL, NULL, '127.0.0.1', '127.0.0.1', 'Flutter Mobile App', '2025-10-28 09:24:22', '2025-10-28 09:54:27', NULL),
(23, 4, 490, 5, 278, '2025-10-28 10:04:53', '2025-10-28 10:31:37', NULL, 27, 0, 'completed', 'scheduled_shift', 'Dome, Accra', NULL, 5.66470400, -0.21962664, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, NULL, NULL, NULL, '127.0.0.1', '127.0.0.1', 'Flutter Mobile App', '2025-10-28 10:04:53', '2025-10-28 10:31:37', NULL),
(24, 4, 542, 49, 282, '2025-10-29 15:50:52', '2025-10-29 16:06:43', NULL, 16, 0, 'completed', 'scheduled_shift', 'Dome, Accra', 'Dome, Accra', 5.66470400, -0.21962664, 5.66470400, -0.21962664, NULL, NULL, NULL, 0, 0, 0, NULL, NULL, NULL, '127.0.0.1', '127.0.0.1', 'Flutter Mobile App', '2025-10-29 15:50:52', '2025-10-29 16:06:43', NULL),
(29, 4, 540, 7, 270, '2025-10-30 22:54:56', '2025-10-30 23:03:44', NULL, 9, 0, 'completed', 'scheduled_shift', 'Dome, Accra', 'Dome, Accra', 5.66470400, -0.21962664, 5.66470400, -0.21962664, NULL, NULL, NULL, 0, 0, 0, NULL, NULL, NULL, '127.0.0.1', '127.0.0.1', 'Flutter Mobile App', '2025-10-30 22:54:56', '2025-10-30 23:03:44', NULL),
(30, 4, 543, 49, 282, '2025-10-31 18:20:13', '2025-10-31 20:03:30', NULL, 103, 0, 'completed', 'scheduled_shift', 'Dome, Accra', 'Dome, Accra', 5.66482364, -0.21960251, 5.66482364, -0.21960251, NULL, NULL, NULL, 0, 0, 0, NULL, NULL, NULL, '127.0.0.1', '127.0.0.1', 'Flutter Mobile App', '2025-10-31 18:20:13', '2025-10-31 20:03:30', NULL),
(31, 4, 545, 49, 278, '2025-11-02 14:33:14', '2025-11-03 10:08:05', NULL, 1175, 0, 'completed', 'scheduled_shift', 'Dome, Accra', 'Dome, Accra', 5.66470400, -0.21962664, 5.66470400, -0.21962664, NULL, NULL, NULL, 0, 0, 0, NULL, NULL, NULL, '127.0.0.1', NULL, NULL, '2025-11-02 14:33:14', '2025-11-03 10:08:05', NULL),
(32, 53, 416, 5, 296, '2025-11-03 16:03:24', '2025-11-03 16:07:23', NULL, 4, 0, 'completed', 'scheduled_shift', 'Dome, Accra', 'Dome, Accra', 5.66470400, -0.21962664, 5.66470400, -0.21962664, NULL, NULL, NULL, 0, 0, 0, NULL, NULL, NULL, '127.0.0.1', NULL, NULL, '2025-11-03 16:03:24', '2025-11-03 16:07:23', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `transport_requests`
--

CREATE TABLE `transport_requests` (
  `id` bigint UNSIGNED NOT NULL,
  `patient_id` bigint UNSIGNED NOT NULL,
  `requested_by_id` bigint UNSIGNED NOT NULL,
  `transport_type` enum('ambulance','regular') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `priority` enum('emergency','urgent','routine') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `scheduled_time` datetime DEFAULT NULL,
  `pickup_location` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `pickup_address` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `destination_location` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `destination_address` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `reason` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `special_requirements` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `contact_person` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `driver_id` bigint UNSIGNED DEFAULT NULL,
  `status` enum('requested','assigned','in_progress','completed','cancelled') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'requested',
  `actual_pickup_time` datetime DEFAULT NULL,
  `actual_arrival_time` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `cancelled_at` datetime DEFAULT NULL,
  `cancellation_reason` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `estimated_cost` decimal(10,2) DEFAULT NULL,
  `actual_cost` decimal(10,2) DEFAULT NULL,
  `distance_km` decimal(8,2) DEFAULT NULL,
  `rating` int DEFAULT NULL,
  `feedback` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `metadata` json DEFAULT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `transport_requests`
--

INSERT INTO `transport_requests` (`id`, `patient_id`, `requested_by_id`, `transport_type`, `priority`, `scheduled_time`, `pickup_location`, `pickup_address`, `destination_location`, `destination_address`, `reason`, `special_requirements`, `contact_person`, `driver_id`, `status`, `actual_pickup_time`, `actual_arrival_time`, `completed_at`, `cancelled_at`, `cancellation_reason`, `estimated_cost`, `actual_cost`, `distance_km`, `rating`, `feedback`, `metadata`, `notes`, `created_at`, `updated_at`, `deleted_at`) VALUES
(7, 50, 62, 'ambulance', 'urgent', '2025-10-20 10:09:56', 'Patient\'s Home - East Legon', 'House No. 12, American House Street, East Legon, Accra', '37 Military Hospital', 'Liberation Avenue, Airport Residential Area, Accra', 'Scheduled surgery appointment', 'Family member accompanying patient', NULL, NULL, 'requested', NULL, NULL, NULL, NULL, NULL, 147.00, NULL, 24.00, NULL, NULL, NULL, NULL, '2025-10-17 10:09:56', '2025-10-16 22:09:56', NULL),
(8, 5, 53, 'regular', 'urgent', '2025-10-19 09:09:56', '37 Military Hospital - Ward 2B', 'Liberation Avenue, 37 Military Hospital, Accra', 'Tema General Hospital', 'Community 2, Tema, Greater Accra', 'Respiratory therapy appointment', NULL, 'Miss Abena Boateng - 0261234567', 7, 'assigned', NULL, NULL, NULL, NULL, NULL, 45.00, NULL, 5.00, NULL, NULL, NULL, NULL, '2025-10-16 09:09:56', '2025-10-16 22:09:56', NULL),
(9, 19, 79, 'regular', 'emergency', '2025-10-16 21:32:56', 'Patient\'s Home - Dzorwulu', '15 Dzorwulu Street, Dzorwulu, Accra', 'Trust Hospital', 'Osu Badu Street, Osu, Accra', 'Post-operative check-up', 'Diabetic patient - glucose monitoring needed', NULL, 37, 'in_progress', '2025-10-16 21:40:56', NULL, NULL, NULL, NULL, 84.00, NULL, 11.00, NULL, NULL, NULL, NULL, '2025-10-15 21:32:56', '2025-10-16 22:09:56', NULL),
(10, 24, 65, 'ambulance', 'urgent', '2025-10-01 11:09:56', 'Patient\'s Home - Osu', 'Liberation Road, Osu RE, Accra', 'Korle-Bu Teaching Hospital', 'Guggisberg Avenue, Korle-Bu, Accra', 'Emergency diabetes management', NULL, 'Mr. Kwame Asante - 0201234567', 2, 'completed', '2025-10-01 11:19:56', '2025-10-01 12:00:56', '2025-10-01 12:00:56', NULL, NULL, 120.00, 112.80, 15.00, 5, 'Professional driver. Handled emergency situation well.', NULL, NULL, '2025-09-30 11:09:56', '2025-10-16 22:09:56', NULL),
(11, 29, 62, 'ambulance', 'urgent', '2025-10-03 12:09:56', 'Patient\'s Home - Dzorwulu', '15 Dzorwulu Street, Dzorwulu, Accra', '37 Military Hospital', 'Liberation Avenue, Airport Residential Area, Accra', 'Emergency admission for chest pain', 'Family member accompanying patient', 'Mr. Yaw Addo - 0202345678', NULL, 'cancelled', NULL, NULL, NULL, '2025-10-02 16:09:56', 'Patient admitted to nearby facility', 207.00, NULL, 44.00, NULL, NULL, NULL, NULL, '2025-10-01 12:09:56', '2025-10-16 22:09:56', NULL),
(12, 26, 64, 'ambulance', 'routine', '2025-10-18 10:09:56', 'Patient\'s Home - Osu', 'Liberation Road, Osu RE, Accra', 'Ridge Hospital', 'Castle Road, Ridge, Accra', 'Follow-up consultation with specialist', NULL, 'Miss Abena Boateng - 0261234567', NULL, 'requested', NULL, NULL, NULL, NULL, NULL, 70.00, NULL, 10.00, NULL, NULL, NULL, NULL, '2025-10-16 10:09:56', '2025-10-16 22:09:56', NULL),
(13, 51, 65, 'regular', 'routine', '2025-10-20 06:09:56', 'Patient\'s Home - Airport Residential', '67 Airport West, Airport Residential Area, Accra', 'Legon Hospital', 'University of Ghana, Legon, Accra', 'Cardiac stress test', 'Oxygen support needed throughout transport', NULL, 4, 'assigned', NULL, NULL, NULL, NULL, NULL, 52.00, NULL, 16.00, NULL, NULL, NULL, NULL, '2025-10-17 06:09:56', '2025-10-16 22:09:56', NULL),
(14, 49, 2, 'ambulance', 'urgent', '2025-10-16 21:00:56', 'Patient\'s Home - Osu', 'Liberation Road, Osu RE, Accra', 'Trust Hospital', 'Osu Badu Street, Osu, Accra', 'Geriatric care consultation', NULL, 'Miss Abena Boateng - 0261234567', 17, 'in_progress', '2025-10-16 21:03:56', NULL, NULL, NULL, NULL, 120.00, NULL, 15.00, NULL, NULL, NULL, NULL, '2025-10-15 21:00:56', '2025-10-16 22:09:56', NULL),
(15, 7, 55, 'ambulance', 'routine', '2025-09-22 09:09:56', 'Patient\'s Home - Labone', '23 Labone Crescent, Labone, Accra', 'Trust Hospital', 'Osu Badu Street, Osu, Accra', 'Chemotherapy session', 'Wheelchair access required', 'Mrs. Efua Darko - 0542345678', 17, 'completed', '2025-09-22 09:01:56', '2025-09-22 10:02:56', '2025-09-22 10:02:56', NULL, NULL, 90.00, 84.60, 20.00, 4, 'Excellent care during transport. Very satisfied.', NULL, NULL, '2025-09-20 09:09:56', '2025-10-16 22:09:56', NULL),
(16, 35, 67, 'regular', 'urgent', '2025-10-05 11:09:56', '37 Military Hospital - Ward 2B', 'Liberation Avenue, 37 Military Hospital, Accra', 'Trust Hospital', 'Osu Badu Street, Osu, Accra', 'Follow-up consultation with specialist', 'Patient has mobility issues - assistance needed', 'Mrs. Efua Darko - 0542345678', NULL, 'cancelled', NULL, NULL, NULL, '2025-10-04 17:09:56', 'Patient condition deteriorated - ambulance called', 153.00, NULL, 41.00, NULL, NULL, NULL, NULL, '2025-10-03 11:09:56', '2025-10-16 22:09:56', NULL),
(17, 33, 61, 'ambulance', 'routine', '2025-10-19 08:09:56', 'Patient\'s Home - Osu', 'Liberation Road, Osu RE, Accra', 'Trust Hospital', 'Osu Badu Street, Osu, Accra', 'Diagnostic imaging (MRI/CT Scan)', 'Patient has mobility issues - assistance needed', 'Mrs. Efua Darko - 0542345678', NULL, 'requested', NULL, NULL, NULL, NULL, NULL, 148.00, NULL, 49.00, NULL, NULL, NULL, NULL, '2025-10-16 08:09:56', '2025-10-16 22:09:56', NULL),
(18, 47, 54, 'regular', 'emergency', '2025-10-18 14:09:56', 'Patient\'s Home - Labone', '23 Labone Crescent, Labone, Accra', 'Police Hospital', 'Cantonments Road, Cantonments, Accra', 'Maternity check-up', NULL, 'Mr. Yaw Addo - 0202345678', 8, 'assigned', NULL, NULL, NULL, NULL, NULL, 140.00, NULL, 25.00, NULL, NULL, NULL, NULL, '2025-10-16 14:09:56', '2025-10-16 22:09:56', NULL),
(19, 18, 57, 'ambulance', 'emergency', '2025-10-16 21:28:56', 'Patient\'s Home - Cantonments', 'Plot 45, Independence Avenue, Cantonments, Accra', 'Legon Hospital', 'University of Ghana, Legon, Accra', 'Maternity check-up', 'Patient requires stretcher - cannot sit upright', NULL, 18, 'in_progress', '2025-10-16 21:33:56', NULL, NULL, NULL, NULL, 184.00, NULL, 21.00, NULL, NULL, NULL, NULL, '2025-10-14 21:28:56', '2025-10-16 22:09:56', NULL),
(20, 24, 63, 'ambulance', 'routine', '2025-10-10 09:09:56', 'Patient\'s Home - Dzorwulu', '15 Dzorwulu Street, Dzorwulu, Accra', '37 Military Hospital', 'Liberation Avenue, Airport Residential Area, Accra', 'Emergency admission for chest pain', 'Wheelchair access required', 'Mrs. Akosua Frimpong - 0551234567', 9, 'completed', '2025-10-10 09:11:56', '2025-10-10 10:34:56', '2025-10-10 10:34:56', NULL, NULL, 88.00, 92.40, 19.00, 5, 'Good service overall. Driver knew the routes well.', NULL, NULL, '2025-10-09 09:09:56', '2025-10-16 22:09:56', NULL),
(21, 45, 60, 'regular', 'urgent', '2025-10-16 10:09:56', 'Patient\'s Home - Osu', 'Liberation Road, Osu RE, Accra', 'Police Hospital', 'Cantonments Road, Cantonments, Accra', 'Wound care and dressing', 'Family member accompanying patient', 'Mrs. Akosua Frimpong - 0551234567', NULL, 'cancelled', NULL, NULL, NULL, '2025-10-16 06:09:56', 'Family arranged alternative transport', 69.00, NULL, 13.00, NULL, NULL, NULL, NULL, '2025-10-13 10:09:56', '2025-10-16 22:09:56', NULL),
(22, 19, 58, 'regular', 'urgent', '2025-10-18 12:09:56', 'Patient\'s Home - East Legon', 'House No. 12, American House Street, East Legon, Accra', 'Police Hospital', 'Cantonments Road, Cantonments, Accra', 'Post-operative check-up', 'Family member accompanying patient', 'Miss Abena Boateng - 0261234567', NULL, 'requested', NULL, NULL, NULL, NULL, NULL, 114.00, NULL, 28.00, NULL, NULL, NULL, NULL, '2025-10-15 12:09:56', '2025-10-16 22:09:56', NULL),
(23, 34, 2, 'regular', 'emergency', '2025-10-20 10:09:56', 'Patient\'s Home - Adabraka', 'Farrar Avenue, Adabraka, Accra', 'Police Hospital', 'Cantonments Road, Cantonments, Accra', 'Chemotherapy session', NULL, 'Mrs. Efua Darko - 0542345678', 26, 'assigned', NULL, NULL, NULL, NULL, NULL, 76.00, NULL, 9.00, NULL, NULL, NULL, NULL, '2025-10-19 10:09:56', '2025-10-16 22:09:56', NULL),
(24, 44, 64, 'ambulance', 'emergency', '2025-10-16 20:50:56', 'Patient\'s Home - Airport Residential', '67 Airport West, Airport Residential Area, Accra', '37 Military Hospital', 'Liberation Avenue, Airport Residential Area, Accra', 'Wound care and dressing', NULL, 'Mr. Kofi Owusu - 0241234567', 35, 'in_progress', '2025-10-16 20:46:56', NULL, NULL, NULL, NULL, 300.00, NULL, 50.00, NULL, NULL, NULL, NULL, '2025-10-14 20:50:56', '2025-10-16 22:09:56', NULL),
(25, 17, 63, 'ambulance', 'emergency', '2025-09-29 13:09:56', 'Patient\'s Home - Osu', 'Liberation Road, Osu RE, Accra', 'Tema General Hospital', 'Community 2, Tema, Greater Accra', 'Orthopedic consultation', 'Patient requires stretcher - cannot sit upright', NULL, 4, 'completed', '2025-09-29 13:04:56', '2025-09-29 13:32:56', '2025-09-29 13:32:56', NULL, NULL, 188.00, 180.48, 22.00, 3, 'Transport was comfortable. Would recommend this service.', NULL, NULL, '2025-09-27 13:09:56', '2025-10-16 22:09:56', NULL),
(26, 51, 66, 'regular', 'emergency', '2025-10-06 10:09:56', 'Patient\'s Home - Dzorwulu', '15 Dzorwulu Street, Dzorwulu, Accra', 'Greater Accra Regional Hospital', 'Ridge Hospital Road, Ridge, Accra', 'Post-operative check-up', 'Medical escort required', NULL, NULL, 'cancelled', NULL, NULL, NULL, '2025-10-05 19:09:56', 'Patient condition deteriorated - ambulance called', 68.00, NULL, 7.00, NULL, NULL, NULL, NULL, '2025-10-04 10:09:56', '2025-10-16 22:09:56', NULL),
(28, 49, 4, 'regular', 'routine', NULL, '25 Squash St, Accra, Ghana', '25 Squash St, Accra, Ghana', 'Ashongman Community Hospital-The Community Hospital', 'MQP9+497, Kwabenya, Ghana', 'Scheduled patient transport to medical facility', NULL, NULL, 11, 'completed', '2025-10-17 00:45:20', '2025-10-17 00:47:07', '2025-10-17 00:47:07', NULL, NULL, 20.00, NULL, NULL, 5, NULL, NULL, NULL, '2025-10-17 00:20:26', '2025-10-17 00:47:07', NULL),
(29, 49, 4, 'regular', 'routine', NULL, '25 Squash St, Accra, Ghana', '25 Squash St, Accra, Ghana', 'Korle Bu Teaching Hospital', 'Guggisberg Ave, Accra, Ghana', 'Scheduled patient transport to medical facility', NULL, NULL, 11, 'assigned', NULL, NULL, NULL, NULL, NULL, 20.00, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-17 11:32:14', '2025-10-17 11:32:14', NULL),
(30, 7, 4, 'regular', 'routine', NULL, '25 Squash St, Accra, Ghana', '25 Squash St, Accra, Ghana', 'Ashongman Community Hospital-The Community Hospital', 'MQP9+497, Kwabenya, Ghana', 'Scheduled patient transport to medical facility', NULL, NULL, 19, 'assigned', NULL, NULL, NULL, NULL, NULL, 20.00, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-24 22:32:33', '2025-10-24 22:32:33', NULL),
(31, 7, 4, 'regular', 'routine', NULL, '25 Squash St, Accra, Ghana', '25 Squash St, Accra, Ghana', 'Ashongman Community Hospital-The Community Hospital', 'MQP9+497, Kwabenya, Ghana', 'Scheduled patient transport to medical facility', NULL, NULL, 16, 'assigned', NULL, NULL, NULL, NULL, NULL, 20.00, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-30 22:56:46', '2025-10-30 22:56:46', NULL),
(32, 7, 4, 'regular', 'routine', NULL, '25 Squash St, Accra, Ghana', '25 Squash St, Accra, Ghana', 'Ashongman Community Hospital-The Community Hospital', 'MQP9+497, Kwabenya, Ghana', 'Scheduled patient transport to medical facility', NULL, NULL, 12, 'assigned', NULL, NULL, NULL, NULL, NULL, 20.00, NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-03 10:03:07', '2025-11-03 10:03:07', NULL),
(33, 5, 5, 'regular', 'routine', NULL, '25 Squash St, Accra, Ghana', '25 Squash St, Accra, Ghana', 'LuccaHealth MSC', 'Plot No. 27 Maseru Street, Accra, Ghana', 'Scheduled patient transport to medical facility', NULL, NULL, 20, 'assigned', NULL, NULL, NULL, NULL, NULL, 20.00, NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-03 12:12:01', '2025-11-03 12:12:01', NULL),
(34, 5, 5, 'regular', 'routine', NULL, '25 Squash St, Accra, Ghana', '25 Squash St, Accra, Ghana', 'Rabito Clinic East Legon', 'Living Room, La-Bawaleshi Rd, Accra, Ghana', 'Scheduled patient transport to medical facility', NULL, NULL, 15, 'assigned', NULL, NULL, NULL, NULL, NULL, 20.00, NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-03 12:17:29', '2025-11-03 12:17:29', NULL),
(35, 5, 5, 'regular', 'routine', NULL, '25 Squash St, Accra, Ghana', '25 Squash St, Accra, Ghana', 'Atomic Hospital', 'MQ99+CC8, Ghana', 'Scheduled patient transport to medical facility', NULL, NULL, 13, 'assigned', NULL, NULL, NULL, NULL, NULL, 20.00, NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-03 12:29:16', '2025-11-03 12:29:16', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `two_factor_codes`
--

CREATE TABLE `two_factor_codes` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED NOT NULL,
  `code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `method` enum('email','sms','voice') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'email',
  `expires_at` timestamp NOT NULL,
  `used_at` timestamp NULL DEFAULT NULL,
  `verified_ip` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_agent` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `login_attempt_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `failed_attempts` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint UNSIGNED NOT NULL,
  `first_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('patient','nurse','doctor','admin','superadmin') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `role_id` bigint UNSIGNED DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `is_verified` tinyint(1) NOT NULL DEFAULT '0',
  `verification_status` enum('pending','verified','rejected','suspended') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `verification_notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `verified_by` bigint UNSIGNED DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('male','female','other') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `ghana_card_number` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `avatar` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `license_number` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `specialization` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `years_experience` int DEFAULT NULL,
  `emergency_contact_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `emergency_contact_phone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `medical_conditions` json DEFAULT NULL,
  `allergies` json DEFAULT NULL,
  `current_medications` json DEFAULT NULL,
  `two_factor_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `two_factor_method` enum('email','sms','biometric') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `two_factor_temp_token` text COLLATE utf8mb4_unicode_ci,
  `two_factor_temp_method` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `two_factor_temp_expires` timestamp NULL DEFAULT NULL,
  `login_verification_token` text COLLATE utf8mb4_unicode_ci,
  `login_verification_expires` timestamp NULL DEFAULT NULL,
  `login_session_token` text COLLATE utf8mb4_unicode_ci,
  `login_session_expires` timestamp NULL DEFAULT NULL,
  `two_factor_enabled_at` timestamp NULL DEFAULT NULL,
  `two_factor_disabled_at` timestamp NULL DEFAULT NULL,
  `two_factor_verified_at` timestamp NULL DEFAULT NULL,
  `security_question` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `security_answer_hash` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_login_at` timestamp NULL DEFAULT NULL,
  `last_login_ip` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password_changed_at` timestamp NULL DEFAULT NULL,
  `force_password_change` tinyint(1) NOT NULL DEFAULT '0',
  `password_reset_token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password_reset_expires` timestamp NULL DEFAULT NULL,
  `registered_ip` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `preferences` json DEFAULT NULL,
  `timezone` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'UTC',
  `locale` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'en',
  `remember_token` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fcm_token` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `fcm_token_updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `first_name`, `last_name`, `email`, `email_verified_at`, `password`, `phone`, `role`, `role_id`, `is_active`, `is_verified`, `verification_status`, `verification_notes`, `verified_by`, `verified_at`, `date_of_birth`, `gender`, `ghana_card_number`, `address`, `avatar`, `license_number`, `specialization`, `years_experience`, `emergency_contact_name`, `emergency_contact_phone`, `medical_conditions`, `allergies`, `current_medications`, `two_factor_enabled`, `two_factor_method`, `two_factor_temp_token`, `two_factor_temp_method`, `two_factor_temp_expires`, `login_verification_token`, `login_verification_expires`, `login_session_token`, `login_session_expires`, `two_factor_enabled_at`, `two_factor_disabled_at`, `two_factor_verified_at`, `security_question`, `security_answer_hash`, `last_login_at`, `last_login_ip`, `password_changed_at`, `force_password_change`, `password_reset_token`, `password_reset_expires`, `registered_ip`, `preferences`, `timezone`, `locale`, `remember_token`, `fcm_token`, `created_at`, `updated_at`, `deleted_at`, `fcm_token_updated_at`) VALUES
(1, 'Super', 'Admin', 'admin@judyhomecare.com', '2025-09-30 20:20:52', '$2y$12$/ZmSVtyNwKvOUGwQV1vnwe0hdefgs1ipgzmp4L9jf/JZygjxdCRYu', '+233501234567', 'superadmin', NULL, 1, 1, 'verified', NULL, NULL, '2025-09-30 20:20:52', '1980-01-01', 'other', 'GHA-000000000-0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 'UTC', 'en', NULL, NULL, '2025-09-30 20:20:52', '2025-09-30 20:20:52', NULL, NULL),
(2, 'John', 'Manager', 'theophilusboateng7@gmail.com', '2025-09-30 20:20:53', '$2y$12$fb7oIlBhL/bSzpFzj8QrrOQ79QnYthP.BCxelUmueAL.PMYPo7w3e', '+233507654321', 'admin', NULL, 1, 1, 'verified', NULL, NULL, '2025-09-30 20:20:53', '1985-05-15', 'male', 'GHA-111111111-1', NULL, 'avatars/cZoeiLSiFpcAthWS8SblptV4Vsj08ON86we97jlB.jpg', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-06 00:05:10', '127.0.0.1', NULL, 0, NULL, NULL, NULL, NULL, 'UTC', 'en', NULL, NULL, '2025-09-30 20:20:53', '2025-11-06 00:05:10', NULL, NULL),
(3, 'Dr. Sarah', 'Wilson', 'doctor@judyhomecare.com', '2025-09-30 20:20:54', '$2y$12$/bi1wpoi4uVw5afkp0pWxeG9qwnSAj6JX7uJClptffxVx1VxSe..6', '+233509876543', 'doctor', NULL, 1, 1, 'verified', NULL, NULL, '2025-09-30 20:20:54', '1978-03-20', 'female', 'GHA-222222222-2', NULL, 'avatars/hZhwM66lHDyoSko6iDIxoNAJBJD7K2EDRQMM685J.jpg', 'MD-12345-GH', 'internal_medicine', 15, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-09-30 23:46:53', '127.0.0.1', NULL, 0, NULL, NULL, NULL, NULL, 'UTC', 'en', NULL, NULL, '2025-09-30 20:20:54', '2025-09-30 23:56:23', NULL, NULL),
(4, 'Lisa', 'Adjei Johnson', 'theophilusbrakoboateng@gmail.com', '2025-09-30 20:20:55', '$2y$12$fb7oIlBhL/bSzpFzj8QrrOQ79QnYthP.BCxelUmueAL.PMYPo7w3e', '+233557447800', 'nurse', NULL, 1, 1, 'verified', NULL, NULL, '2025-09-30 20:20:55', '1995-11-01', 'female', 'GHA-883333333-3', NULL, 'avatars/1760690929_jCzBtjiWFC.png', 'RN-67890-GH', 'geriatric_care', 7, NULL, NULL, NULL, NULL, NULL, 1, 'sms', NULL, NULL, NULL, NULL, NULL, '$2y$12$rpleQ4gW3xL87ccZ4KvPEO3aDW72zdgk4l9bd/f4d0WjKeynCQO1W', '2025-10-31 18:58:53', '2025-10-31 18:54:26', NULL, NULL, NULL, NULL, '2025-11-04 00:26:27', '127.0.0.1', '2025-10-06 21:46:13', 0, NULL, NULL, NULL, NULL, 'UTC', 'en', NULL, NULL, '2025-09-30 20:20:55', '2025-11-04 00:26:27', NULL, NULL),
(5, 'Robert Ben', 'Brown', 'theo.boateng@gtnllc.com', '2025-09-30 20:20:56', '$2y$12$fb7oIlBhL/bSzpFzj8QrrOQ79QnYthP.BCxelUmueAL.PMYPo7w3e', '+233503692581', 'patient', NULL, 1, 1, 'verified', NULL, NULL, '2025-09-30 20:20:56', '1965-12-05', 'male', 'GHA-222222-2', 'Dome-Pillar 2, Estate', NULL, NULL, NULL, 0, 'Jane Brown', '+233504567890', '[\"diabetes\", \"hypertension\"]', '[\"penicillin\"]', '[\"metformin\", \"lisinopril\"]', 1, 'email', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-04 12:02:55', NULL, NULL, NULL, NULL, '2025-11-05 19:02:10', '127.0.0.1', NULL, 0, NULL, NULL, NULL, NULL, 'UTC', 'en', NULL, NULL, '2025-09-30 20:20:56', '2025-11-05 19:02:10', NULL, NULL),
(6, 'Priscilla', 'Boateng', 'theophilus.boateng@gtnllc.coms', NULL, '$2y$12$3WLe5HV1OuLgPJZBxJzuk.xB.EraVeigGO.MNuyrVvd0VeqrBrUXG', '0557447802', 'nurse', NULL, 0, 1, 'suspended', NULL, 2, '2025-09-30 20:24:39', '1995-01-11', 'female', 'GHA-13892323-1', NULL, NULL, 'NUR-238923', 'general_care', 5, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, '$2y$12$6FHKQ0uiwWJyY8NoQ/h0qeSQP30ISlZYZqImBmnsiv04hIH6/ib8i', '2025-10-06 19:48:07', '127.0.0.1', NULL, 'UTC', 'en', NULL, NULL, '2025-09-30 20:23:41', '2025-10-13 20:52:16', '2025-10-13 20:52:16', NULL),
(7, 'Granit', 'Xhaka', 'granit@gmail.com', NULL, '$2y$12$6IBxOh34EUpUt5r2RP74DOnvb.gSY.e9N6LDVeU6AbZ6brNRAEqK2', '0208110620', 'patient', NULL, 1, 1, 'verified', NULL, NULL, NULL, '1997-10-01', 'male', 'GHA-1389023232-1', NULL, 'avatars/1760476087_nZDcQ7tE70.webp', NULL, NULL, NULL, 'Emmanuel Bansah', '+233 24 37117', '[\"Diabetes\", \"Hypertension\", \"Asthma\"]', '[\"Penicillin\", \"Peanuts\", \"Latex\"]', '[\"Metformin 500mg\", \"Lisinopril 10mg\"]', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, '127.0.0.1', NULL, 'UTC', 'en', NULL, NULL, '2025-10-07 21:00:46', '2025-10-14 21:08:07', NULL, NULL),
(8, 'Philip', 'Gbeko', 'philip@ansah.com', NULL, '$2y$12$JvYKSRbTxdJWJ189b9MnhOtYwh6eRlYYjdl2zeVB.dclCQIz7JNDO', '055748949', 'patient', NULL, 1, 1, 'verified', NULL, NULL, NULL, '1990-11-01', 'male', 'GHA-283492834-1', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '[\"Diabetes\", \"Hypertension\", \"Asthma\"]', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, '127.0.0.1', NULL, 'UTC', 'en', NULL, NULL, '2025-10-07 21:20:56', '2025-10-07 21:20:56', NULL, NULL),
(16, 'Micheal', 'Asamoah', 'micheal.asamoah@patient.judyhomecare.com', NULL, '$2y$12$ENyMVmvDjpCahRO7eOT4sOHkyBzdQccYWQd04CF5ZN89LYp0hdoDu', '0557447888', 'patient', NULL, 1, 1, 'verified', NULL, 4, '2025-10-09 20:53:19', '1995-10-17', 'male', 'GHA-238928232-1', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 'UTC', 'en', NULL, NULL, '2025-10-09 20:53:19', '2025-10-09 20:53:19', NULL, NULL),
(17, 'Ben', 'Carson', 'theo.boateng@gtnllc.coms', NULL, '$2y$12$gckW09tW5jY4YUs1LgEU5.0lK4MlsN2TwHALny9zvLRQ/eMsxxmy2', '05574478001', 'patient', NULL, 1, 0, 'verified', NULL, NULL, NULL, '1995-10-13', 'male', 'GHA-28238923-1', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, '127.0.0.1', NULL, 'UTC', 'en', NULL, NULL, '2025-10-13 21:22:14', '2025-10-14 16:23:40', NULL, NULL),
(18, 'Tianna', 'Considine', 'tianna.considine1@patient.com', NULL, '$2y$12$FK43kweA0phC84UquF4TxeQ6Ftkwi8R441gbQ2jJqSaDcahJDMcCm', '0508923688', 'patient', NULL, 1, 0, 'verified', NULL, 1, '2025-09-28 21:39:09', '1996-04-25', 'female', 'GHA-501544260-3', NULL, NULL, NULL, NULL, NULL, 'Wiley Rippin', '0277697377', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, '113.28.181.13', NULL, 'UTC', 'en', NULL, NULL, '2025-09-06 21:39:09', '2025-09-25 21:39:09', NULL, NULL),
(19, 'Ray', 'Wintheiser', 'ray.wintheiser2@patient.com', NULL, '$2y$12$Uk5LM5kg1GDQVOt93tbT4.dcTjFZGecFZjT..RpEOTT.iUN7PseWq', '0268873360', 'patient', NULL, 1, 1, 'verified', NULL, NULL, '2025-10-12 21:39:10', '1977-01-13', 'male', 'GHA-627215864-7', NULL, NULL, NULL, NULL, NULL, 'Stacy Bartell', '0267502361', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-10 21:39:10', NULL, NULL, 0, NULL, NULL, '81.62.172.105', NULL, 'UTC', 'en', NULL, NULL, '2025-10-04 21:39:10', '2025-09-30 21:39:10', NULL, NULL),
(20, 'Alyce', 'Miller', 'alyce.miller3@patient.com', NULL, '$2y$12$fS3FGHiZaKMVAdFnC76DE.GGK9Iq38cL0eBJSm.0vK1R7sKo6xbEC', '0205790133', 'patient', NULL, 1, 0, 'pending', NULL, 1, NULL, '1993-12-13', 'female', 'GHA-558736024-9', NULL, NULL, NULL, NULL, NULL, 'Travon Pfannerstill', '0266509063', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, '217.249.121.61', NULL, 'UTC', 'en', NULL, NULL, '2025-09-04 21:39:11', '2025-09-26 21:39:11', NULL, NULL),
(21, 'Adrianna', 'Leannon', 'adrianna.leannon4@patient.com', NULL, '$2y$12$Cpkw7w0Xyc5h1LFhgAtOd.qDYhbwqKxyMzTQ7NQCzjyE985dap7eW', '0570224298', 'patient', NULL, 1, 0, 'verified', NULL, NULL, '2025-10-07 21:39:12', '1986-08-22', 'male', 'GHA-643100968-9', NULL, NULL, NULL, NULL, NULL, 'Mazie Kuhn', '0506332310', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-10 21:39:12', NULL, NULL, 0, NULL, NULL, '232.219.158.86', NULL, 'UTC', 'en', NULL, NULL, '2025-10-10 21:39:12', '2025-10-09 21:39:12', NULL, NULL),
(22, 'Samara', 'Bergnaum', 'samara.bergnaum5@patient.com', NULL, '$2y$12$w4jSAswNp02re0hzRaBbLeC1uSkRlOxoiBWWUiNj11wUDL3x/J4DO', '0541661034', 'patient', NULL, 1, 1, 'pending', NULL, NULL, '2025-09-29 21:39:12', '1974-12-14', 'male', 'GHA-572801218-3', NULL, NULL, NULL, NULL, NULL, 'Dr. Kiley Bernhard Sr.', '0561503255', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-07 21:39:12', NULL, NULL, 0, NULL, NULL, '77.173.141.26', NULL, 'UTC', 'en', NULL, NULL, '2025-08-13 21:39:12', '2025-09-24 21:39:12', NULL, NULL),
(23, 'Trever', 'Stamm', 'trever.stamm6@patient.com', NULL, '$2y$12$dj/zaQPGJCoXIGraQdMg0OYKToDsG4RPaC/fMsyf/2voJz/PSk9P.', '0204675421', 'patient', NULL, 1, 1, 'pending', NULL, 1, '2025-09-20 21:39:13', '1989-04-01', 'female', 'GHA-259172079-0', NULL, NULL, NULL, NULL, NULL, 'Caroline Schulist', '0261630861', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-11 21:39:13', NULL, NULL, 0, NULL, NULL, '66.188.58.13', NULL, 'UTC', 'en', NULL, NULL, '2025-09-10 21:39:13', '2025-10-03 21:39:13', NULL, NULL),
(24, 'Aliza', 'Pfannerstill', 'aliza.pfannerstill7@patient.com', NULL, '$2y$12$cVekCsLHSmuJ3HaTbmBuxewGIyGiQswfYOkTFCXbLiWED/sfpnhjG', '0572170613', 'patient', NULL, 1, 1, 'verified', NULL, 2, '2025-10-14 20:54:45', '1972-10-15', 'male', 'GHA-990800896-6', NULL, NULL, NULL, NULL, NULL, 'Dr. Allie Reichert V', '0579969660', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-13 21:39:14', NULL, NULL, 0, NULL, NULL, '70.159.205.241', NULL, 'UTC', 'en', NULL, NULL, '2025-07-28 21:39:14', '2025-10-14 20:54:45', NULL, NULL),
(25, 'Prudence', 'Swift', 'prudence.swift8@patient.com', NULL, '$2y$12$/P9dk9P1IZfbgIWOkka8xOmt/hX9SdrjyEer3upI5xaxCA/ki6bEq', '0505742612', 'patient', NULL, 1, 0, 'pending', NULL, NULL, '2025-09-15 21:39:15', '1978-04-20', 'male', 'GHA-032579897-0', NULL, NULL, NULL, NULL, NULL, 'Sam Jast PhD', '0563167992', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-06 21:39:15', NULL, NULL, 0, NULL, NULL, '230.164.203.206', NULL, 'UTC', 'en', NULL, NULL, '2025-09-20 21:39:15', '2025-10-03 21:39:15', NULL, NULL),
(26, 'Felix', 'Cassin', 'felix.cassin9@patient.com', NULL, '$2y$12$gdNRForW0K1aXboQIhLqNuWtELax8Ov8axqqDoBTLZNAhCS5Ov4Ua', '0541331931', 'patient', NULL, 1, 1, 'verified', NULL, 2, '2025-10-17 10:46:15', '1974-10-12', 'male', 'GHA-204056358-9', NULL, NULL, NULL, NULL, NULL, 'Mr. Napoleon Johnson PhD', '0246529031', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-06 21:39:16', NULL, NULL, 0, NULL, NULL, '161.14.43.123', NULL, 'UTC', 'en', NULL, NULL, '2025-10-12 21:39:16', '2025-10-17 10:46:15', NULL, NULL),
(27, 'Roel', 'Lemke', 'roel.lemke10@patient.com', NULL, '$2y$12$gxyhLiBG0XEviOZC6guU2O2qVuAqVQiIU7PlcRsnEAOb79WFlbFlS', '0506316245', 'patient', NULL, 1, 1, 'verified', NULL, 1, NULL, '1999-09-25', 'female', 'GHA-017821726-5', NULL, NULL, NULL, NULL, NULL, 'Vidal Koch', '0269459887', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-12 21:39:17', NULL, NULL, 0, NULL, NULL, '157.225.8.139', NULL, 'UTC', 'en', NULL, NULL, '2025-09-05 21:39:17', '2025-10-04 21:39:17', NULL, NULL),
(28, 'Heloise', 'Dach', 'heloise.dach11@patient.com', NULL, '$2y$12$cp424UirUB1cvv.2ciaSCukSVQT.t4IKettfKn0iIL1dUfN09gAcK', '0244983217', 'patient', NULL, 0, 0, 'rejected', 'We regret to inform you that your application has been declined after careful review. Please feel free to reapply in the future once the necessary requirements are met.', 2, '2025-10-14 20:54:07', '1989-07-20', 'male', 'GHA-792754025-3', NULL, NULL, NULL, NULL, NULL, 'Mr. Eladio Purdy I', '0569985400', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, '64.4.85.63', NULL, 'UTC', 'en', NULL, NULL, '2025-07-15 21:39:18', '2025-10-14 20:54:07', NULL, NULL),
(29, 'Tony', 'Ullrich', 'tony.ullrich12@patient.com', NULL, '$2y$12$8e5ZRhYT7ZVzzoHWETj1kOUcUfN1R7EtH749556MCXcLYUNyganES', '0541812640', 'patient', NULL, 1, 0, 'verified', NULL, 1, '2025-10-05 21:39:19', '1979-01-15', 'male', 'GHA-893689778-8', NULL, NULL, NULL, NULL, NULL, 'Wilhelm Bashirian', '0273037347', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-13 21:39:19', NULL, NULL, 0, NULL, NULL, '119.45.165.238', NULL, 'UTC', 'en', NULL, NULL, '2025-08-14 21:39:19', '2025-10-04 21:39:19', NULL, NULL),
(30, 'Loma', 'Beatty', 'loma.beatty13@patient.com', NULL, '$2y$12$UlEV7wpEH7dgaY0d.ap9meW59GHYvuZn154i2EZ0gagECsl28VEsK', '0501215484', 'patient', NULL, 1, 1, 'verified', NULL, 2, '2025-10-16 22:43:03', '1972-07-09', 'female', 'GHA-692891512-0', NULL, NULL, NULL, NULL, NULL, 'Delfina Rice', '0209170639', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-08 21:39:19', NULL, NULL, 0, NULL, NULL, '219.72.204.133', NULL, 'UTC', 'en', NULL, NULL, '2025-08-09 21:39:19', '2025-10-16 22:43:03', NULL, NULL),
(31, 'Shaniya', 'Goldner', 'shaniya.goldner14@patient.com', NULL, '$2y$12$F2mGndy6lZO1m3/OdJoVx.9Wt5KE6qBYvG6nJL9SZ.O4B3KD.v3/u', '0545455662', 'patient', NULL, 1, 1, 'pending', NULL, 1, '2025-09-24 21:39:20', '1987-05-14', 'male', 'GHA-745808165-7', NULL, NULL, NULL, NULL, NULL, 'Nathaniel Oberbrunner', '0557121572', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-10 21:39:20', NULL, NULL, 0, NULL, NULL, '129.165.218.252', NULL, 'UTC', 'en', NULL, NULL, '2025-08-14 21:39:20', '2025-10-12 21:39:20', NULL, NULL),
(32, 'Pascale', 'Block', 'pascale.block15@patient.com', NULL, '$2y$12$eKMU.rN2ie/r3ZhN.LgBTOAMU5dl/rEB8nL0aWczu.pfvJxXEloZ6', '0575255747', 'patient', NULL, 1, 0, 'verified', NULL, 1, NULL, '1979-12-10', 'male', 'GHA-816945153-8', NULL, NULL, NULL, NULL, NULL, 'Cassandre Cremin PhD', '0500054672', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-10 21:39:21', NULL, NULL, 0, NULL, NULL, '123.152.204.97', NULL, 'UTC', 'en', NULL, NULL, '2025-08-10 21:39:21', '2025-10-01 21:39:21', NULL, NULL),
(33, 'Davin', 'Daugherty', 'davin.daugherty16@patient.com', NULL, '$2y$12$It8chJpMtbnXE8CfbC1Zwuy6GhmOkhdQ1WAqJfU1YfsWQSFdBFViu', '0261234889', 'patient', NULL, 1, 1, 'pending', NULL, NULL, '2025-10-12 21:39:22', '1973-01-02', 'male', 'GHA-449432628-6', NULL, NULL, NULL, NULL, NULL, 'Bruce Grady', '0503255356', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-08 21:39:22', NULL, NULL, 0, NULL, NULL, '227.212.229.216', NULL, 'UTC', 'en', NULL, NULL, '2025-09-25 21:39:22', '2025-09-26 21:39:22', NULL, NULL),
(34, 'Tod', 'Huels', 'tod.huels17@patient.com', NULL, '$2y$12$BWPDv4kFfSkPNVrAAlMJMuEcoGk41HYBh2oOJLqtorttpKYwRZKAi', '0567606671', 'patient', NULL, 1, 1, 'pending', NULL, 1, '2025-10-03 21:39:23', '1979-04-07', 'female', 'GHA-084156455-2', NULL, NULL, NULL, NULL, NULL, 'Jeffery Heathcote', '0265377815', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-13 21:39:23', NULL, NULL, 0, NULL, NULL, '102.112.27.175', NULL, 'UTC', 'en', NULL, NULL, '2025-08-14 21:39:23', '2025-09-17 21:39:23', NULL, NULL),
(35, 'Talon', 'Schoen', 'talon.schoen18@patient.com', NULL, '$2y$12$67n5T3iSGzsUkH6V4g0ceuYbh.V96AIbfK8QRqqV0HlL3kEPtQ68q', '0202976165', 'patient', NULL, 1, 0, 'verified', NULL, 1, '2025-10-03 21:39:24', '1984-02-17', 'female', 'GHA-982887807-0', NULL, NULL, NULL, NULL, NULL, 'Mac Powlowski MD', '0568785740', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-12 21:39:24', NULL, NULL, 0, NULL, NULL, '212.77.238.87', NULL, 'UTC', 'en', NULL, NULL, '2025-10-12 21:39:24', '2025-10-09 21:39:24', NULL, NULL),
(36, 'Mauricio', 'Batz', 'mauricio.batz19@patient.com', NULL, '$2y$12$LYvQJAgppqaS60qtceAhpuEbxWLfBLOD7uKq4mwMEspxLYenEIUIC', '0265952940', 'patient', NULL, 1, 1, 'verified', NULL, 1, '2025-09-13 21:39:25', '1981-06-17', 'female', 'GHA-567416225-6', NULL, NULL, NULL, NULL, NULL, 'Prof. Yasmeen Beahan', '0260603279', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-07 21:39:25', NULL, NULL, 0, NULL, NULL, '170.4.83.174', NULL, 'UTC', 'en', NULL, NULL, '2025-09-15 21:39:25', '2025-09-30 21:39:25', NULL, NULL),
(37, 'Adrienne', 'Dickinson', 'adrienne.dickinson20@patient.com', NULL, '$2y$12$02gVg9rzlsc.VuqYEMj/0eg1qBKt2axWWImcCR3YOcQU3beU.0.Ve', '0558964176', 'patient', NULL, 1, 1, 'pending', NULL, 1, NULL, '1983-06-05', 'male', 'GHA-099094949-7', NULL, NULL, NULL, NULL, NULL, 'Kevon Ward', '0272686564', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-13 21:39:26', NULL, NULL, 0, NULL, NULL, '154.129.184.247', NULL, 'UTC', 'en', NULL, NULL, '2025-10-05 21:39:26', '2025-09-14 21:39:26', NULL, NULL),
(38, 'Jaylon', 'Deckow', 'jaylon.deckow21@patient.com', NULL, '$2y$12$R2nhS3NpttVREYQ5uoIx1uPiP.ndF1Y6v6zjK8WMULt21qla58FfC', '0576115798', 'patient', NULL, 1, 1, 'pending', NULL, 1, '2025-09-27 21:39:27', '1994-09-06', 'male', 'GHA-438069198-9', NULL, NULL, NULL, NULL, NULL, 'Ms. Nedra Nolan', '0509201178', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, '111.184.231.247', NULL, 'UTC', 'en', NULL, NULL, '2025-10-05 21:39:27', '2025-09-30 21:39:27', NULL, NULL),
(39, 'Jarvis', 'Dicki', 'jarvis.dicki22@patient.com', NULL, '$2y$12$Z.fRGF0cLpmZB9Mxt16dxO4T02zTMUp7QUKYFtF1AYEJarj6sdpry', '0204674835', 'patient', NULL, 1, 1, 'verified', NULL, 1, '2025-10-03 21:39:27', '1982-02-21', 'female', 'GHA-607509478-3', NULL, NULL, NULL, NULL, NULL, 'Dr. Scottie Johns', '0562646956', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-13 21:39:27', NULL, NULL, 0, NULL, NULL, '125.13.245.97', NULL, 'UTC', 'en', NULL, NULL, '2025-10-05 21:39:27', '2025-09-30 21:39:27', NULL, NULL),
(40, 'Juston', 'Roob', 'juston.roob23@patient.com', NULL, '$2y$12$.LpTIUw6vZBAMr0w5o9T1epW6i3851Sa7.g0lLdkr8Q7EfbIoK.5W', '0553427251', 'patient', NULL, 1, 1, 'pending', NULL, 1, '2025-10-03 21:39:28', '2000-01-19', 'female', 'GHA-041078673-1', NULL, NULL, NULL, NULL, NULL, 'Dave Hilpert', '0205743265', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-11 21:39:28', NULL, NULL, 0, NULL, NULL, '87.216.36.188', NULL, 'UTC', 'en', NULL, NULL, '2025-08-19 21:39:28', '2025-10-07 21:39:28', NULL, NULL),
(41, 'Callie', 'Bergstrom', 'callie.bergstrom24@patient.com', NULL, '$2y$12$6STbZC9FR6t/QHPLnUclbO0R7cwKAu/0gUneccf1coa/FYwaJfVVS', '0266762352', 'patient', NULL, 1, 1, 'pending', NULL, NULL, '2025-09-27 21:39:29', '1992-11-14', 'female', 'GHA-381095728-0', NULL, NULL, NULL, NULL, NULL, 'Miss Annette Hills', '0565087466', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-07 21:39:29', NULL, NULL, 0, NULL, NULL, '103.54.67.218', NULL, 'UTC', 'en', NULL, NULL, '2025-09-29 21:39:29', '2025-09-21 21:39:29', NULL, NULL),
(42, 'Desiree', 'Mraz', 'desiree.mraz25@patient.com', NULL, '$2y$12$Igei2D7PLy3YSm9pGCdVgO9OZwHa1gtJnd4koWelOe5E67xzu59UG', '0270280158', 'patient', NULL, 1, 1, 'pending', NULL, 1, '2025-10-10 21:39:30', '1997-08-18', 'male', 'GHA-273337692-8', NULL, NULL, NULL, NULL, NULL, 'Prof. Gene Streich MD', '0501775387', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-13 21:39:30', NULL, NULL, 0, NULL, NULL, '222.54.62.211', NULL, 'UTC', 'en', NULL, NULL, '2025-08-19 21:39:30', '2025-09-17 21:39:30', NULL, NULL),
(43, 'Katlyn', 'Schneider', 'katlyn.schneider26@patient.com', NULL, '$2y$12$TLXM8IQXGtK3hXOp/M0gXOqHlSQRBW93n26DqY1PAv9Z2463M3Vei', '0549684463', 'patient', NULL, 1, 1, 'pending', NULL, 1, '2025-10-01 21:39:31', '1996-11-18', 'female', 'GHA-612335454-7', NULL, NULL, NULL, NULL, NULL, 'Cicero Hintz', '0269744510', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-06 21:39:31', NULL, NULL, 0, NULL, NULL, '241.121.20.57', NULL, 'UTC', 'en', NULL, NULL, '2025-09-08 21:39:31', '2025-09-19 21:39:31', NULL, NULL),
(44, 'Rossie', 'Buckridge', 'rossie.buckridge27@patient.com', NULL, '$2y$12$FWLzbuL7o5Nl9qXXdCpwsu260tb2FShQFNIr/MbIRL8Xn2iaslJxm', '0276446507', 'patient', NULL, 0, 0, 'rejected', 'We regret to inform you that your application has been declined after careful review. Please feel free to reapply in the future once the necessary requirements are met.', 2, '2025-10-14 20:54:31', '1985-02-11', 'male', 'GHA-014158869-8', NULL, NULL, NULL, NULL, NULL, 'Keira Sipes', '0209298558', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-11 21:39:32', NULL, NULL, 0, NULL, NULL, '107.210.135.64', NULL, 'UTC', 'en', NULL, NULL, '2025-07-17 21:39:32', '2025-10-14 20:54:31', NULL, NULL),
(45, 'Felipe', 'Schowalter', 'felipe.schowalter28@patient.com', NULL, '$2y$12$8C/B9R1Jjz.WS6OPHp1Vx.cr78EVf2n.nlCdzCxhQDLN3eVGp3Dja', '0500946032', 'patient', NULL, 1, 1, 'pending', NULL, 1, '2025-09-14 21:39:33', '1997-11-22', 'male', 'GHA-528658301-9', NULL, NULL, NULL, NULL, NULL, 'Karley Haley', '0540455270', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, '192.217.242.115', NULL, 'UTC', 'en', NULL, NULL, '2025-08-27 21:39:33', '2025-09-14 21:39:33', NULL, NULL),
(46, 'Will', 'Dicki', 'will.dicki29@patient.com', NULL, '$2y$12$LEGz8xTJBxp7wflkk8GmpONeRFFTe1Qd8c0EZHgTIbJBJCBzOZ5Ne', '0248663045', 'patient', NULL, 1, 1, 'verified', NULL, 2, '2025-10-16 22:43:01', '1970-10-31', 'female', 'GHA-188324145-7', NULL, NULL, NULL, NULL, NULL, 'Horace Will IV', '0503944989', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-07 21:39:34', NULL, NULL, 0, NULL, NULL, '165.56.74.55', NULL, 'UTC', 'en', NULL, NULL, '2025-08-06 21:39:34', '2025-10-16 22:43:01', NULL, NULL),
(47, 'Larry', 'Hill', 'larry.hill30@patient.com', NULL, '$2y$12$fx5xsmi5pQA/CaGh2D/tPeGNgLKJE.PWKwwgZF/2UXYhfqfj8KYmC', '0509885080', 'patient', NULL, 1, 1, 'pending', NULL, 1, NULL, '1994-08-16', 'female', 'GHA-928602871-6', NULL, NULL, NULL, NULL, NULL, 'Meta Hand', '0272737829', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-12 21:39:35', NULL, NULL, 0, NULL, NULL, '175.201.65.115', NULL, 'UTC', 'en', NULL, NULL, '2025-10-06 21:39:35', '2025-09-15 21:39:35', NULL, NULL),
(48, 'Christa', 'Bradtke', 'christa.bradtke31@patient.com', NULL, '$2y$12$iinfW6EHw8/.DTwU03kN9uKSOV72KhWOcoCYCg/2dkgXx2ddVO.C2', '0560153157', 'patient', NULL, 1, 1, 'verified', NULL, NULL, '2025-09-21 21:39:36', '1977-05-18', 'female', 'GHA-275769855-8', NULL, NULL, NULL, NULL, NULL, 'Sadie Thompson IV', '0549686737', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-09 21:39:36', NULL, NULL, 0, NULL, NULL, '1.151.147.122', NULL, 'UTC', 'en', NULL, NULL, '2025-09-17 21:39:36', '2025-09-23 21:39:36', NULL, NULL),
(49, 'Ted', 'Howell', 'ted.howell32@patient.com', NULL, '$2y$12$BTJLYl2CIH.FhqAh3kNhies7FiHqV61odXqhBTEyY7m9b4GiXDgdK', '0540351520', 'patient', NULL, 1, 1, 'pending', NULL, 1, '2025-09-25 21:39:37', '1971-04-23', 'male', 'GHA-114235569-5', NULL, NULL, NULL, NULL, NULL, 'Prof. Lavern Beatty II', '0559447410', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-11 21:39:37', NULL, NULL, 0, NULL, NULL, '33.215.247.77', NULL, 'UTC', 'en', NULL, NULL, '2025-08-18 21:39:37', '2025-09-18 21:39:37', NULL, NULL),
(50, 'Chester', 'Shields', 'chester.shields33@patient.com', NULL, '$2y$12$j7fkf4KEEv4Ie/y.M3KJZuWahgi6uhWwxuCUBWck9faR3mnu0qy0u', '0203484819', 'patient', NULL, 1, 1, 'pending', NULL, 1, '2025-09-21 21:39:37', '1976-07-31', 'male', 'GHA-223544335-8', NULL, NULL, NULL, NULL, NULL, 'Miss Willa Cole', '0209144544', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, '165.91.227.11', NULL, 'UTC', 'en', NULL, NULL, '2025-10-10 21:39:37', '2025-10-12 21:39:37', NULL, NULL),
(51, 'Bessie', 'Dooley', 'bessie.dooley34@patient.com', NULL, '$2y$12$ic4wQ4D97OSamjJQxyAAO.wOOkGkTzPKmOzj2ZJKg9ClA4z4d3cqO', '0561863938', 'patient', NULL, 1, 1, 'verified', NULL, 1, NULL, '1970-05-02', 'male', 'GHA-692410977-5', NULL, NULL, NULL, NULL, NULL, 'Malinda Jakubowski', '0249479910', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-08 21:39:38', NULL, NULL, 0, NULL, NULL, '190.220.214.252', NULL, 'UTC', 'en', NULL, NULL, '2025-09-25 21:39:38', '2025-10-03 21:39:38', NULL, NULL),
(52, 'Murphy', 'Nienow', 'murphy.nienow35@patient.com', NULL, '$2y$12$7whiHrqTmTNLmY0.mzG1I.0VippvFFEFZlBnFr7K1O9EcAzjOpWUa', '0551665156', 'patient', NULL, 1, 1, 'verified', NULL, 1, NULL, '1975-10-25', 'male', 'GHA-907182618-1', NULL, NULL, NULL, NULL, NULL, 'Danial Wiegand', '0246238047', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, '214.32.45.215', NULL, 'UTC', 'en', NULL, NULL, '2025-09-29 21:39:39', '2025-10-05 21:39:39', NULL, NULL),
(53, 'Alex', 'Gislason', 'alex.gislason1@nurse.com', NULL, '$2y$12$fb7oIlBhL/bSzpFzj8QrrOQ79QnYthP.BCxelUmueAL.PMYPo7w3e', '0279909709', 'nurse', NULL, 1, 1, 'verified', NULL, 1, '2025-09-21 21:39:40', '1986-02-12', 'male', 'GHA-625863292-7', NULL, NULL, 'NUR-433751', 'pediatric_care', 9, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-04 00:28:49', '127.0.0.1', NULL, 0, NULL, NULL, '69.158.110.127', NULL, 'UTC', 'en', NULL, NULL, '2025-08-29 21:39:40', '2025-11-04 00:28:49', NULL, NULL),
(54, 'Parker', 'Koelpin', 'parker.koelpin2@nurse.com', NULL, '$2y$12$NYgEncwnt3pLDrYgvEEBmO4H.aR7AGuMXUc0twZcIwhENKmawAy1G', '0549779940', 'nurse', NULL, 1, 1, 'verified', NULL, 1, '2025-10-12 21:39:41', '1989-01-06', 'female', 'GHA-880522180-8', NULL, NULL, 'NUR-759514', 'geriatric_care', 11, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, '86.155.147.135', NULL, 'UTC', 'en', NULL, NULL, '2025-08-01 21:39:41', '2025-09-30 21:39:41', NULL, NULL),
(55, 'Assunta', 'Grady', 'assunta.grady3@nurse.com', NULL, '$2y$12$mqZH2MsCBW.e7sgR6afeReTBZ9wijP6YzmzTlenXiHErFa2/LdnIO', '0568970074', 'nurse', NULL, 1, 1, 'verified', NULL, 1, '2025-09-17 21:39:42', '1973-01-11', 'female', 'GHA-193694166-4', NULL, NULL, 'NUR-490344', 'general_care', 9, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-12 21:39:42', NULL, NULL, 0, NULL, NULL, '48.199.86.171', NULL, 'UTC', 'en', NULL, NULL, '2025-09-12 21:39:42', '2025-10-04 21:39:42', NULL, NULL),
(56, 'Ethyl', 'Durgan', 'ethyl.durgan4@nurse.com', NULL, '$2y$12$eel8iYx9nL7bxHbQUh99A.V3kUjhQdW6MelPSCMyn9cgOodk4EBSK', '0500718760', 'nurse', NULL, 1, 1, 'verified', NULL, 1, '2025-09-29 21:39:43', '1990-03-12', 'male', 'GHA-498164665-7', NULL, NULL, 'NUR-563274', 'critical_care', 6, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, '191.138.253.98', NULL, 'UTC', 'en', NULL, NULL, '2025-09-08 21:39:43', '2025-10-02 21:39:43', NULL, NULL),
(57, 'Frances', 'Enchia', 'erica.roob5@nurse.com', NULL, '$2y$12$Llud7tPMV7ky0xuifPBxAe./7P6CM.EhGPy75632w0nI5Qo5zrIwu', '+233504315723', 'nurse', NULL, 1, 1, 'verified', NULL, 1, '2025-09-24 21:39:44', '1972-12-22', 'male', 'GHA-277356727-1', NULL, NULL, 'NUR-802714', 'critical_care', 9, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-11 21:39:44', NULL, NULL, 0, NULL, NULL, '85.96.203.218', NULL, 'UTC', 'en', NULL, NULL, '2025-07-11 21:39:44', '2025-10-05 21:39:44', NULL, NULL),
(58, 'Narciso', 'Moen', 'narciso.moen6@nurse.com', NULL, '$2y$12$taevugUEn/wHaImOXx1yD.GovKpcm53fA4ifTJ/KiCQCE3U13RdOa', '0560860749', 'nurse', NULL, 1, 1, 'verified', NULL, 1, '2025-10-05 21:39:45', '1982-09-10', 'female', 'GHA-063445699-4', NULL, NULL, 'NUR-397860', 'emergency_care', 4, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-10 21:39:45', NULL, NULL, 0, NULL, NULL, '170.154.37.219', NULL, 'UTC', 'en', NULL, NULL, '2025-05-17 21:39:45', '2025-09-30 21:39:45', NULL, NULL),
(59, 'Sonia', 'Dickens', 'sonia.dickens7@nurse.com', NULL, '$2y$12$ORAniIS7L92km5.bXdNXM.6RY/rtnE/VuoW0VEKKzWB6ynhGJrd8K', '0205071436', 'nurse', NULL, 1, 1, 'verified', NULL, 1, '2025-09-29 21:39:46', '1976-02-26', 'male', 'GHA-410039152-4', NULL, NULL, 'NUR-597676', 'emergency_care', 13, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-13 21:39:46', NULL, NULL, 0, NULL, NULL, '154.90.73.74', NULL, 'UTC', 'en', NULL, NULL, '2025-07-07 21:39:46', '2025-10-11 21:39:46', NULL, NULL),
(60, 'Triston', 'Strosin', 'triston.strosin8@nurse.com', NULL, '$2y$12$3ecNjlmcmqbRL4DtBznWW.w2w31PMpm/YmieveoXsFcSDhU6s9Dey', '0566268311', 'nurse', NULL, 1, 1, 'verified', NULL, 1, '2025-10-08 21:39:46', '1973-04-19', 'female', 'GHA-590646780-4', NULL, NULL, 'NUR-103974', 'pediatric_care', 8, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, '81.74.150.2', NULL, 'UTC', 'en', NULL, NULL, '2025-04-23 21:39:46', '2025-10-13 21:39:46', NULL, NULL),
(61, 'Bethel', 'Kuvalis', 'bethel.kuvalis9@nurse.com', NULL, '$2y$12$g2h/KEQ8w6GxFQGxLZKdOumdx5w6g3PW3Sv1rI4XW7Fky5biecvgm', '0271544044', 'nurse', NULL, 1, 1, 'verified', NULL, 1, '2025-10-08 21:39:47', '1982-04-20', 'male', 'GHA-543015845-5', NULL, NULL, 'NUR-244476', 'emergency_care', 13, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-10 21:39:47', NULL, NULL, 0, NULL, NULL, '184.204.21.105', NULL, 'UTC', 'en', NULL, NULL, '2025-05-31 21:39:47', '2025-10-01 21:39:47', NULL, NULL),
(62, 'Eryn', 'Langosh', 'eryn.langosh10@nurse.com', NULL, '$2y$12$PaA8SuSY67SjdJFMesYeT.jwlSD6JtCyaagsOdEHULCsdZqHtqDfG', '0543083961', 'nurse', NULL, 1, 1, 'verified', NULL, 1, '2025-09-30 21:39:48', '1989-11-04', 'male', 'GHA-316220428-2', NULL, NULL, 'NUR-280582', 'pediatric_care', 8, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-11 21:39:48', NULL, NULL, 0, NULL, NULL, '183.105.7.18', NULL, 'UTC', 'en', NULL, NULL, '2025-05-03 21:39:48', '2025-10-02 21:39:48', NULL, NULL),
(63, 'Trey', 'Cronin', 'trey.cronin11@nurse.com', NULL, '$2y$12$klID5kRz6Cf4gfadjA6YYOYaJMWITpv8t4uFOHkXnROX917uLlPQ2', '0572108492', 'nurse', NULL, 1, 1, 'verified', NULL, 1, '2025-10-01 21:39:49', '1990-07-02', 'male', 'GHA-086050610-4', NULL, NULL, 'NUR-028532', 'general_care', 13, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-10 21:39:49', NULL, NULL, 0, NULL, NULL, '78.140.66.225', NULL, 'UTC', 'en', NULL, NULL, '2025-07-08 21:39:49', '2025-10-08 21:39:49', NULL, NULL),
(64, 'Jaden', 'Schultz', 'jaden.schultz12@nurse.com', NULL, '$2y$12$ZfF2OCqfhFHeQEsEcW7cQux1x7NhI20l3lilrw59r1iM/Srp0Heiy', '0560139498', 'nurse', NULL, 1, 1, 'verified', NULL, 1, '2025-09-20 21:39:50', '1972-02-26', 'female', 'GHA-185938978-7', NULL, NULL, 'NUR-895102', 'palliative_care', 11, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-12 21:39:50', NULL, NULL, 0, NULL, NULL, '133.241.109.235', NULL, 'UTC', 'en', NULL, NULL, '2025-05-03 21:39:50', '2025-09-30 21:39:50', NULL, NULL),
(65, 'Victoria', 'Jacobs', 'victoria.jacobs13@nurse.com', NULL, '$2y$12$JbeS.WySIScA64YRWk7zKeWv.kNmhLPGeLYmoa/Fy1IUI6FR6p.D.', '0546845121', 'nurse', NULL, 1, 1, 'verified', NULL, 1, '2025-09-20 21:39:51', '1990-04-27', 'female', 'GHA-147014703-3', NULL, NULL, 'NUR-676930', 'critical_care', 9, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-13 21:39:51', NULL, NULL, 0, NULL, NULL, '196.12.118.232', NULL, 'UTC', 'en', NULL, NULL, '2025-04-24 21:39:51', '2025-10-12 21:39:51', NULL, NULL),
(66, 'Jonas', 'Towne', 'jonas.towne14@nurse.com', NULL, '$2y$12$Z4SfYQGIBIcCYbtWE9Lal.SOX2YVA2F/SVTmstDFS80X9TMu5Crz.', '0551272414', 'nurse', NULL, 1, 1, 'verified', NULL, 1, '2025-09-30 21:39:52', '1973-08-07', 'male', 'GHA-585857222-5', NULL, NULL, 'NUR-471285', 'critical_care', 5, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, '254.76.223.234', NULL, 'UTC', 'en', NULL, NULL, '2025-09-09 21:39:52', '2025-10-04 21:39:52', NULL, NULL),
(67, 'Helmer', 'Hand', 'helmer.hand15@nurse.com', NULL, '$2y$12$eGG7coREzTXTVMeTl64cNuHSDWwcRITdIvVLYZKKNiDq7Ui7N5dhG', '0270980892', 'nurse', NULL, 1, 1, 'verified', NULL, 1, '2025-09-16 21:39:53', '1986-07-22', 'male', 'GHA-097598827-9', NULL, NULL, 'NUR-890986', 'palliative_care', 2, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-10 21:39:53', NULL, NULL, 0, NULL, NULL, '65.150.100.240', NULL, 'UTC', 'en', NULL, NULL, '2025-08-08 21:39:53', '2025-09-29 21:39:53', NULL, NULL),
(68, 'Nelle', 'Lakin', 'dr.nelle.lakin1@doctor.com', NULL, '$2y$12$MH/JxP.eBk9sl.4QzShcbOJRXzDQt5cVBjXcNaTh1JGqPkepAA4.2', '0579421535', 'doctor', NULL, 1, 1, 'verified', NULL, 1, '2025-09-13 21:39:54', '1985-01-25', 'female', 'GHA-098875100-7', NULL, NULL, 'MDC-590748', 'emergency_medicine', 16, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, '171.94.3.17', NULL, 'UTC', 'en', NULL, NULL, '2025-02-04 21:39:54', '2025-10-13 21:39:54', NULL, NULL),
(69, 'Carolina', 'Kessler', 'dr.carolina.kessler2@doctor.com', NULL, '$2y$12$LOBZCnGNhXytTNGrGepANuHKTTag4q2e5wmZMZCGDnxkGEOAIPEAe', '0547794300', 'doctor', NULL, 1, 1, 'verified', NULL, 1, '2025-10-09 21:39:55', '1980-12-25', 'male', 'GHA-309315034-3', NULL, NULL, 'MDC-321724', 'orthopedics', 18, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-11 21:39:55', NULL, NULL, 0, NULL, NULL, '90.81.141.32', NULL, 'UTC', 'en', NULL, NULL, '2025-04-22 21:39:55', '2025-10-05 21:39:55', NULL, NULL),
(70, 'Aletha', 'Treutel', 'dr.aletha.treutel3@doctor.com', NULL, '$2y$12$YyirXLo8A7BbQUwEcq0XGO0QtMZt1u5STCbOQc387inigtEsY6PBO', '0558392531', 'doctor', NULL, 1, 1, 'verified', NULL, 1, '2025-09-20 21:39:56', '1977-06-14', 'male', 'GHA-504152713-5', NULL, NULL, 'MDC-568192', 'general_medicine', 19, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-12 21:39:56', NULL, NULL, 0, NULL, NULL, '131.42.27.113', NULL, 'UTC', 'en', NULL, NULL, '2025-04-22 21:39:56', '2025-10-04 21:39:56', NULL, NULL),
(71, 'Katlyn', 'King', 'dr.katlyn.king4@doctor.com', NULL, '$2y$12$fCja2weXc.CuqWIPj8hLJuIRARePjJL5Aen0e3L4JHYlQdt5sg.2S', '0573349498', 'doctor', NULL, 1, 1, 'verified', NULL, 1, '2025-09-18 21:39:56', '1970-06-10', 'male', 'GHA-857075441-5', NULL, NULL, 'MDC-102447', 'general_medicine', 24, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-12 21:39:56', NULL, NULL, 0, NULL, NULL, '171.168.130.18', NULL, 'UTC', 'en', NULL, NULL, '2025-01-16 21:39:56', '2025-10-06 21:39:56', NULL, NULL),
(72, 'Patsy', 'Cummings', 'dr.patsy.cummings5@doctor.com', NULL, '$2y$12$nunpPYae8Qu2h6Vx0d7wAeDCrx4/u8N44RH2qpL7mLXj8zSH0PrgS', '0567546499', 'doctor', NULL, 1, 1, 'verified', NULL, 1, '2025-09-17 21:39:57', '1974-07-04', 'male', 'GHA-033608658-6', NULL, NULL, 'MDC-357277', 'oncology', 11, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-12 21:39:57', NULL, NULL, 0, NULL, NULL, '203.226.251.173', NULL, 'UTC', 'en', NULL, NULL, '2024-11-18 21:39:57', '2025-10-03 21:39:57', NULL, NULL),
(73, 'Seamus', 'Swaniawski', 'dr.seamus.swaniawski6@doctor.com', NULL, '$2y$12$Ovpi1g4UMHRdboSNozDL7OS3kA/kruC3fci7gZjKmhQ8JY5W/dIdS', '0275735400', 'doctor', NULL, 1, 1, 'verified', NULL, 1, '2025-09-27 21:39:58', '1978-11-09', 'female', 'GHA-168185789-2', NULL, NULL, 'MDC-950419', 'orthopedics', 10, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-11 21:39:58', NULL, NULL, 0, NULL, NULL, '168.234.108.179', NULL, 'UTC', 'en', NULL, NULL, '2024-11-13 21:39:58', '2025-10-05 21:39:58', NULL, NULL),
(74, 'Hayden', 'Abbott', 'dr.hayden.abbott7@doctor.com', NULL, '$2y$12$82qhV70FSpG8LZMvMZKxs.xbs.qR1vOHi7qC6GT0heO7ex94D9AUq', '0565332834', 'doctor', NULL, 1, 1, 'verified', NULL, 1, '2025-10-03 21:39:59', '1983-08-04', 'female', 'GHA-566275551-6', NULL, NULL, 'MDC-553315', 'neurology', 19, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-11 21:39:59', NULL, NULL, 0, NULL, NULL, '60.198.206.80', NULL, 'UTC', 'en', NULL, NULL, '2025-02-23 21:39:59', '2025-10-06 21:39:59', NULL, NULL),
(75, 'Darwin', 'Hill', 'dr.darwin.hill8@doctor.com', NULL, '$2y$12$fTlm4.Yvm7vicxy9fUBMJuCmRD2D/RggiRKqwZjjUfMVoyOtnsTZi', '0546782002', 'doctor', NULL, 1, 1, 'verified', NULL, 1, '2025-10-05 21:40:00', '1977-10-24', 'male', 'GHA-637261433-1', NULL, NULL, 'MDC-847820', 'pediatric_care', 9, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-11 21:40:00', NULL, NULL, 0, NULL, NULL, '158.224.89.41', NULL, 'UTC', 'en', NULL, NULL, '2025-04-23 21:40:00', '2025-10-10 21:40:00', NULL, NULL),
(76, 'Therese', 'Baumbach', 'dr.therese.baumbach9@doctor.com', NULL, '$2y$12$uDDlPVjqJClwsJW067hOVuTAvXZPO7Vy4LuZaIFtzi1sIouvahB32', '0249851319', 'doctor', NULL, 1, 1, 'verified', NULL, 1, '2025-09-15 21:40:01', '1979-11-20', 'male', 'GHA-293334517-5', NULL, NULL, 'MDC-614055', 'pediatric_care', 13, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-13 21:40:01', NULL, NULL, 0, NULL, NULL, '52.232.183.244', NULL, 'UTC', 'en', NULL, NULL, '2024-11-21 21:40:01', '2025-10-06 21:40:01', NULL, NULL),
(77, 'Vergie', 'Kassulke', 'dr.vergie.kassulke10@doctor.com', NULL, '$2y$12$9YOj826FbLF9ETe7b1iYp.HpoOC5k9pukWaxdIMIZFaPnS7tZbkVu', '0558452536', 'doctor', NULL, 1, 1, 'verified', NULL, 1, '2025-10-01 21:40:02', '1980-04-15', 'male', 'GHA-383643589-3', NULL, NULL, 'MDC-771622', 'oncology', 19, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-11 21:40:02', NULL, NULL, 0, NULL, NULL, '54.8.141.119', NULL, 'UTC', 'en', NULL, NULL, '2025-03-19 21:40:02', '2025-10-03 21:40:02', NULL, NULL),
(78, 'Test', 'Patient', 'testpatient@gmail.com', NULL, '$2y$12$4ZjkroWV.eiJJxc0fod6vujQs3fDsH/A55hG7kktD.YMOBRvkKSlq', '0000000000', 'patient', NULL, 1, 1, 'verified', NULL, 2, '2025-10-14 16:34:14', '1995-11-01', 'male', NULL, NULL, NULL, NULL, NULL, NULL, 'Hello', '0000000000', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, '127.0.0.1', NULL, 'UTC', 'en', NULL, NULL, '2025-10-14 16:34:14', '2025-10-14 16:34:14', NULL, NULL),
(79, 'Test', 'Nurse', 'testnurse@gmail.com', NULL, '$2y$12$ITj8dAZ/4Tis6jRx12LYxOk2FhgvnmEZ2hs7krGgKAeEYFKJP1o4G', '0000000001', 'nurse', NULL, 1, 1, 'verified', NULL, 2, '2025-10-14 16:35:18', NULL, 'male', NULL, NULL, 'avatars/1760468531_AtiuLnXuWi.webp', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, '127.0.0.1', NULL, 'UTC', 'en', NULL, NULL, '2025-10-14 16:35:18', '2025-10-14 19:02:11', NULL, NULL),
(80, 'Test', 'Doctor', 'testdoctor@gmail.com', NULL, '$2y$12$6.b9y7AGjIJWp/mbNIbBa.3.SNQsU46vpa6bSk0J.RA9nzP4mJ38O', '0000000002', 'doctor', NULL, 1, 1, 'verified', NULL, 2, '2025-10-14 16:40:22', NULL, 'male', NULL, NULL, NULL, NULL, NULL, 6, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, '127.0.0.1', NULL, 'UTC', 'en', NULL, NULL, '2025-10-14 16:40:22', '2025-10-14 16:40:22', NULL, NULL),
(81, 'Test', 'Admin', 'testadmin@gmail.com', NULL, '$2y$12$v/Plf0SkARqIICV.fKMZh.7eQOLz4W76iJakTX0rRzUfLn0zSu99m', '0000000003', 'admin', NULL, 1, 1, 'verified', NULL, 2, '2025-10-14 16:41:12', NULL, 'male', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, '127.0.0.1', NULL, 'UTC', 'en', NULL, NULL, '2025-10-14 16:41:12', '2025-10-14 16:41:12', NULL, NULL),
(82, 'Lisah', 'Kobinah', 'lisa@gmail.com', NULL, '$2y$12$ZYwvsreZzhaico3bjN/nw.kZxP10IV70HvyF1./v/vZe4eBR6E3K2', '0000000009', 'patient', NULL, 1, 1, 'verified', NULL, 2, '2025-10-14 17:08:08', NULL, 'male', NULL, NULL, 'avatars/1760463009_jsYxqDRnZF.webp', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, '127.0.0.1', NULL, 'UTC', 'en', NULL, NULL, '2025-10-14 17:08:08', '2025-10-14 17:30:09', NULL, NULL),
(83, 'sdfa', 'asdfas', 'asdfa@gmi.com', NULL, '$2y$12$LjZMEB.Vz7YqaYtSj.Cr8etNk8ohNG7jXx41KDJZhCbH7Wx6xXYeS', '000000008', 'patient', NULL, 1, 1, 'verified', NULL, 2, '2025-10-14 20:15:08', '1995-10-14', 'male', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, '127.0.0.1', NULL, 'UTC', 'en', NULL, NULL, '2025-10-14 20:15:08', '2025-10-14 20:15:13', '2025-10-14 20:15:13', NULL),
(84, 'Clarabelle', 'Nana Akua Brako Boateng', 'clarabelle.nana akua brako boateng@patient.judyhomecare.com', NULL, '$2y$12$qu1giWDKUCd8Qm6q/HmhIe0Zma5Xpeb8hpIjl/zmpQzoFlCEpP9RC', '0203775949', 'patient', NULL, 1, 1, 'verified', NULL, 4, '2025-10-28 10:28:36', '1998-10-09', 'female', 'GHA-2138288232-1', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 'UTC', 'en', NULL, NULL, '2025-10-28 10:28:36', '2025-10-28 10:28:36', NULL, NULL),
(85, 'Clarabelle', 'Brako Boateng', 'clarabelle.brako boateng@patient.judyhomecare.com', NULL, '$2y$12$qSVucgBuYrNxzBdD0wJ7seEWNTuJOinpfwBG8dwgPiOJGzqQhC1ly', '0203775944', 'patient', NULL, 1, 1, 'verified', NULL, 4, '2025-10-28 11:52:13', '1998-09-10', 'female', 'GHA-183892320-1', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 'UTC', 'en', NULL, NULL, '2025-10-28 11:52:13', '2025-10-31 20:50:50', '2025-10-31 20:50:50', NULL),
(88, 'Dominic', 'Asare', 'junitsolutionlms@gmail.com', NULL, '$2y$12$eaGE31WrBq.KoVrTxtyjj.OrGmIb.Pj/EjoaSXR6nq9LDXSB4pIxC', '0557447800', 'nurse', NULL, 1, 1, 'verified', NULL, 2, '2025-10-31 21:09:54', NULL, 'male', NULL, NULL, NULL, 'LIC-AS8329', 'emergency_care', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, '127.0.0.1', NULL, 'UTC', 'en', NULL, NULL, '2025-10-31 21:02:33', '2025-10-31 21:09:54', NULL, NULL),
(89, 'Nana', 'Akua Brako', 'clarabellenanaakua1@gmail.com', NULL, '$2y$12$doJphlacLCm/vskLoAgIUeP99Cy3rBnBs5/QeyuwxEqHjUmLIpQyW', '0203775949', 'nurse', NULL, 1, 0, 'pending', NULL, NULL, NULL, NULL, 'male', NULL, NULL, NULL, 'LUC-14576', 'oncology', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, '127.0.0.1', NULL, 'UTC', 'en', NULL, NULL, '2025-10-31 22:53:05', '2025-10-31 22:53:05', NULL, NULL),
(90, 'Philipa', 'Baafi', 'philipa.baafi@patient.judyhomecare.com', NULL, '$2y$12$hf417vFo6kzjcbaNGETAluUbfOdKtxZ6acqOdRwuBf7fP9nPanEZi', '0208110620', 'patient', NULL, 1, 1, 'verified', NULL, 4, '2025-11-02 13:50:50', '1995-11-10', 'male', 'GHA-382392238-1', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 'UTC', 'en', NULL, NULL, '2025-11-02 13:50:50', '2025-11-02 13:50:50', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_sessions`
--

CREATE TABLE `user_sessions` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED NOT NULL,
  `token_id` bigint UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_agent` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `device_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `logged_in_at` timestamp NOT NULL,
  `logged_out_at` timestamp NULL DEFAULT NULL,
  `last_activity` timestamp NOT NULL,
  `is_current` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `vehicles`
--

CREATE TABLE `vehicles` (
  `id` bigint UNSIGNED NOT NULL,
  `vehicle_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `vehicle_type` enum('ambulance','regular') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `registration_number` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `vehicle_color` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `make` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `model` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `year` int DEFAULT NULL,
  `vin_number` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `is_available` tinyint(1) NOT NULL DEFAULT '1',
  `status` enum('available','in_use','maintenance','out_of_service') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'available',
  `last_service_date` date DEFAULT NULL,
  `next_service_date` date DEFAULT NULL,
  `mileage` decimal(10,2) DEFAULT NULL,
  `insurance_policy` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `insurance_expiry` date DEFAULT NULL,
  `registration_expiry` date DEFAULT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `vehicles`
--

INSERT INTO `vehicles` (`id`, `vehicle_id`, `vehicle_type`, `registration_number`, `vehicle_color`, `make`, `model`, `year`, `vin_number`, `is_active`, `is_available`, `status`, `last_service_date`, `next_service_date`, `mileage`, `insurance_policy`, `insurance_expiry`, `registration_expiry`, `notes`, `created_at`, `updated_at`, `deleted_at`) VALUES
(34, 'VEH66109', 'ambulance', 'GR-1001-24', 'White', 'Toyota', 'Hiace', 2024, 'JTDKB20U197001001', 1, 1, 'available', '2025-09-16', '2025-12-16', 12450.00, 'AMB-2024-101', '2026-08-16', '2026-10-16', 'Advanced life support ambulance - Primary emergency response', '2025-10-16 21:51:46', '2025-10-16 21:51:46', NULL),
(35, 'VEH42000', 'ambulance', 'GR-1002-24', 'White', 'Mercedes-Benz', 'Sprinter', 2024, 'WDB9063221N001002', 1, 1, 'available', '2025-09-26', '2025-12-16', 8750.50, 'AMB-2024-102', '2026-09-16', '2026-10-16', 'ICU equipped ambulance with ventilator support', '2025-10-16 21:51:46', '2025-10-16 21:51:46', NULL),
(36, 'VEH15159', 'ambulance', 'GR-1003-24', 'White', 'Ford', 'Transit', 2024, '1FTBW3XM5GKA01003', 1, 1, 'available', '2025-10-01', '2025-12-16', 15680.25, 'AMB-2024-103', '2026-07-16', '2026-09-16', 'Basic life support ambulance for routine transfers', '2025-10-16 21:51:46', '2025-10-16 21:51:46', NULL),
(37, 'VEH07067', 'ambulance', 'GR-1004-24', 'White', 'Toyota', 'Hiace', 2023, 'JTDKB20U197001004', 1, 1, 'available', '2025-09-21', '2025-12-16', 24350.75, 'AMB-2023-104', '2026-06-16', '2026-08-16', 'Standard emergency ambulance with defibrillator', '2025-10-16 21:51:46', '2025-10-16 21:51:46', NULL),
(38, 'VEH29889', 'ambulance', 'GR-1005-24', 'White', 'Volkswagen', 'Crafter', 2024, 'WV1ZZZ2KZLH001005', 1, 1, 'available', '2025-10-06', '2026-01-16', 6890.00, 'AMB-2024-105', '2026-09-16', '2026-10-16', 'Bariatric ambulance with heavy-duty equipment', '2025-10-16 21:51:46', '2025-10-16 21:51:46', NULL),
(39, 'VEH96418', 'ambulance', 'GR-1006-23', 'White', 'Mercedes-Benz', 'Sprinter', 2023, 'WDB9063221N001006', 1, 1, 'available', '2025-09-16', '2025-12-16', 32450.50, 'AMB-2023-106', '2026-05-16', '2026-07-16', 'Neonatal ambulance with incubator', '2025-10-16 21:51:46', '2025-10-16 21:51:46', NULL),
(40, 'VEH70714', 'ambulance', 'GR-1007-24', 'White', 'Chevrolet', 'Express', 2024, '1GCWGBFG4K1001007', 1, 1, 'available', '2025-09-28', '2025-12-16', 9560.25, 'AMB-2024-107', '2026-08-16', '2026-09-16', 'Mobile intensive care unit', '2025-10-16 21:51:46', '2025-10-16 21:51:46', NULL),
(41, 'VEH14278', 'ambulance', 'GR-1008-24', 'White', 'Ford', 'Transit', 2024, '1FTBW3XM5GKA01008', 1, 1, 'available', '2025-09-24', '2025-12-16', 11240.00, 'AMB-2024-108', '2026-09-16', '2026-10-16', 'Cardiac ambulance with ECG monitor', '2025-10-16 21:51:46', '2025-10-16 21:51:46', NULL),
(42, 'VEH40208', 'ambulance', 'GR-1009-23', 'White', 'Toyota', 'Hiace', 2023, 'JTDKB20U197001009', 1, 1, 'available', '2025-09-16', '2025-12-16', 28790.75, 'AMB-2023-109', '2026-04-16', '2026-06-16', 'General purpose emergency ambulance', '2025-10-16 21:51:46', '2025-10-16 21:51:46', NULL),
(43, 'VEH48900', 'ambulance', 'GR-1010-24', 'White', 'Volkswagen', 'Crafter', 2024, 'WV1ZZZ2KZLH001010', 1, 1, 'available', '2025-10-04', '2026-01-16', 7350.50, 'AMB-2024-110', '2026-09-16', '2026-10-16', 'Trauma response ambulance with surgical equipment', '2025-10-16 21:51:46', '2025-10-16 21:51:46', NULL),
(44, 'VEH62870', 'regular', 'GR-2001-24', 'Silver', 'Toyota', 'Camry', 2024, '4T1B11HK5KU002001', 1, 1, 'available', '2025-09-26', '2025-12-16', 5680.00, 'REG-2024-201', '2026-08-16', '2026-09-16', 'Executive transport for patient families', '2025-10-16 21:51:46', '2025-10-16 21:51:46', NULL),
(45, 'VEH99777', 'regular', 'GR-2002-24', 'Black', 'Honda', 'Accord', 2024, '1HGCV1F16KA002002', 1, 1, 'available', '2025-10-01', '2025-12-16', 8920.50, 'REG-2024-202', '2026-09-16', '2026-10-16', 'Nurse home visit vehicle', '2025-10-16 21:51:46', '2025-10-16 21:51:46', NULL),
(46, 'VEH28737', 'regular', 'GR-2003-24', 'White', 'Toyota', 'Corolla', 2024, '2T1BURHE5KC002003', 1, 1, 'available', '2025-09-21', '2025-12-16', 12450.25, 'REG-2024-203', '2026-07-16', '2026-08-16', 'Medical supplies delivery vehicle', '2025-10-16 21:51:46', '2025-10-16 21:51:46', NULL),
(47, 'VEH73605', 'regular', 'GR-2004-24', 'Blue', 'Nissan', 'Sentra', 2024, '3N1AB7AP8KY002004', 1, 1, 'available', '2025-09-28', '2025-12-16', 6780.75, 'REG-2024-204', '2026-08-16', '2026-09-16', 'Staff transport vehicle', '2025-10-16 21:51:46', '2025-10-16 21:51:46', NULL),
(48, 'VEH36319', 'regular', 'GR-2005-24', 'Grey', 'Honda', 'Civic', 2024, '2HGFC2F59KH002005', 1, 1, 'available', '2025-10-04', '2026-01-16', 9340.00, 'REG-2024-205', '2026-09-16', '2026-10-16', 'General purpose transport', '2025-10-16 21:51:46', '2025-10-16 21:51:46', NULL),
(49, 'VEH76664', 'regular', 'GR-2006-24', 'Black', 'Volkswagen', 'Passat', 2024, '1VWSA7A39KC002006', 1, 1, 'available', '2025-09-26', '2025-12-16', 11560.50, 'REG-2024-206', '2026-08-16', '2026-09-16', 'Executive patient transport', '2025-10-16 21:51:46', '2025-10-16 21:51:46', NULL),
(50, 'VEH81086', 'regular', 'GR-2007-24', 'White', 'Hyundai', 'Elantra', 2024, '5NPD84LF1KH002007', 1, 1, 'available', '2025-10-08', '2026-01-16', 4230.25, 'REG-2024-207', '2026-09-16', '2026-10-16', 'Non-emergency patient transport', '2025-10-16 21:51:46', '2025-10-16 21:51:46', NULL),
(51, 'VEH78295', 'regular', 'GR-2008-24', 'Silver', 'Mazda', 'Mazda3', 2024, '3MZBM1U77KM002008', 1, 1, 'available', '2025-09-24', '2025-12-16', 7890.75, 'REG-2024-208', '2026-07-16', '2026-08-16', 'Home care nurse vehicle', '2025-10-16 21:51:46', '2025-10-16 21:51:46', NULL),
(52, 'VEH85733', 'regular', 'GR-2009-24', 'Red', 'Toyota', 'Camry', 2024, '4T1B11HK5KU002009', 1, 1, 'available', '2025-09-30', '2025-12-16', 10120.00, 'REG-2024-209', '2026-08-16', '2026-09-16', 'Administrative staff vehicle', '2025-10-16 21:51:46', '2025-10-16 21:51:46', NULL),
(53, 'VEH51433', 'regular', 'GR-2010-24', 'Blue', 'Honda', 'Accord', 2024, '1HGCV1F16KA002010', 1, 1, 'available', '2025-10-02', '2025-12-16', 13670.50, 'REG-2024-210', '2026-09-16', '2026-10-16', 'Patient consultation transport', '2025-10-16 21:51:46', '2025-10-16 21:51:46', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cache`
--
ALTER TABLE `cache`
  ADD PRIMARY KEY (`key`);

--
-- Indexes for table `cache_locks`
--
ALTER TABLE `cache_locks`
  ADD PRIMARY KEY (`key`);

--
-- Indexes for table `care_assignments`
--
ALTER TABLE `care_assignments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `care_assignments_secondary_nurse_id_foreign` (`secondary_nurse_id`),
  ADD KEY `care_assignments_assigned_by_foreign` (`assigned_by`),
  ADD KEY `care_assignments_approved_by_foreign` (`approved_by`),
  ADD KEY `care_assignments_previous_assignment_id_foreign` (`previous_assignment_id`),
  ADD KEY `care_assignments_care_plan_id_status_index` (`care_plan_id`,`status`),
  ADD KEY `care_assignments_primary_nurse_id_status_start_date_index` (`primary_nurse_id`,`status`,`start_date`),
  ADD KEY `care_assignments_patient_id_status_index` (`patient_id`,`status`),
  ADD KEY `care_assignments_assigned_at_status_index` (`assigned_at`,`status`),
  ADD KEY `care_assignments_is_emergency_priority_level_index` (`is_emergency`,`priority_level`),
  ADD KEY `care_assignments_overall_match_score_index` (`overall_match_score`),
  ADD KEY `care_assignments_admin_override_by_foreign` (`admin_override_by`);

--
-- Indexes for table `care_fee_structure`
--
ALTER TABLE `care_fee_structure`
  ADD PRIMARY KEY (`id`),
  ADD KEY `care_fee_structure_fee_type_care_type_is_active_index` (`fee_type`,`care_type`,`is_active`);

--
-- Indexes for table `care_payments`
--
ALTER TABLE `care_payments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `care_payments_reference_number_unique` (`reference_number`),
  ADD UNIQUE KEY `care_payments_transaction_id_unique` (`transaction_id`),
  ADD KEY `care_payments_care_request_id_foreign` (`care_request_id`),
  ADD KEY `care_payments_patient_id_status_index` (`patient_id`,`status`),
  ADD KEY `care_payments_reference_number_index` (`reference_number`),
  ADD KEY `care_payments_transaction_id_index` (`transaction_id`);

--
-- Indexes for table `care_plans`
--
ALTER TABLE `care_plans`
  ADD PRIMARY KEY (`id`),
  ADD KEY `care_plans_created_by_foreign` (`created_by`),
  ADD KEY `care_plans_approved_by_foreign` (`approved_by`),
  ADD KEY `care_plans_patient_id_status_index` (`patient_id`,`status`),
  ADD KEY `care_plans_doctor_id_created_at_index` (`doctor_id`,`created_at`),
  ADD KEY `care_plans_care_type_status_index` (`care_type`,`status`),
  ADD KEY `care_plans_start_date_end_date_index` (`start_date`,`end_date`),
  ADD KEY `care_plans_priority_index` (`priority`),
  ADD KEY `care_plans_primary_nurse_id_index` (`primary_nurse_id`),
  ADD KEY `care_plans_secondary_nurse_id_index` (`secondary_nurse_id`);

--
-- Indexes for table `care_requests`
--
ALTER TABLE `care_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `care_requests_assigned_nurse_id_foreign` (`assigned_nurse_id`),
  ADD KEY `care_requests_medical_assessment_id_foreign` (`medical_assessment_id`),
  ADD KEY `care_requests_patient_id_status_index` (`patient_id`,`status`),
  ADD KEY `care_requests_created_at_index` (`created_at`);

--
-- Indexes for table `drivers`
--
ALTER TABLE `drivers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `drivers_driver_id_unique` (`driver_id`),
  ADD UNIQUE KEY `drivers_phone_unique` (`phone`),
  ADD KEY `drivers_suspended_by_foreign` (`suspended_by`),
  ADD KEY `drivers_is_active_is_suspended_index` (`is_active`,`is_suspended`),
  ADD KEY `drivers_average_rating_index` (`average_rating`);

--
-- Indexes for table `driver_vehicle_assignments`
--
ALTER TABLE `driver_vehicle_assignments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_active_vehicle_assignment` (`vehicle_id`),
  ADD KEY `driver_vehicle_assignments_assigned_by_foreign` (`assigned_by`),
  ADD KEY `driver_vehicle_assignments_unassigned_by_foreign` (`unassigned_by`),
  ADD KEY `driver_vehicle_assignments_driver_id_is_active_index` (`driver_id`,`is_active`),
  ADD KEY `driver_vehicle_assignments_vehicle_id_is_active_index` (`vehicle_id`,`is_active`),
  ADD KEY `driver_vehicle_assignments_assigned_at_unassigned_at_index` (`assigned_at`,`unassigned_at`);

--
-- Indexes for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Indexes for table `incident_reports`
--
ALTER TABLE `incident_reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `incident_reports_assigned_to_foreign` (`assigned_to`),
  ADD KEY `incident_reports_patient_id_incident_date_index` (`patient_id`,`incident_date`),
  ADD KEY `incident_reports_reported_by_reported_at_index` (`reported_by`,`reported_at`),
  ADD KEY `incident_reports_status_severity_index` (`status`,`severity`),
  ADD KEY `incident_reports_incident_type_incident_date_index` (`incident_type`,`incident_date`),
  ADD KEY `incident_reports_incident_date_index` (`incident_date`),
  ADD KEY `incident_reports_follow_up_date_index` (`follow_up_date`),
  ADD KEY `incident_reports_reviewed_by_reviewed_at_index` (`reviewed_by`,`reviewed_at`),
  ADD KEY `incident_reports_last_updated_by_foreign` (`last_updated_by`),
  ADD KEY `incident_reports_status_closed_at_index` (`status`,`closed_at`),
  ADD KEY `incident_reports_resolved_by_resolved_at_index` (`resolved_by`,`resolved_at`),
  ADD KEY `incident_reports_closed_by_closed_at_index` (`closed_by`,`closed_at`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jobs_queue_index` (`queue`);

--
-- Indexes for table `job_batches`
--
ALTER TABLE `job_batches`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `login_history`
--
ALTER TABLE `login_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `login_history_user_id_attempted_at_index` (`user_id`,`attempted_at`),
  ADD KEY `login_history_email_attempted_at_index` (`email`,`attempted_at`),
  ADD KEY `login_history_ip_address_attempted_at_index` (`ip_address`,`attempted_at`),
  ADD KEY `login_history_successful_attempted_at_index` (`successful`,`attempted_at`);

--
-- Indexes for table `medical_assessments`
--
ALTER TABLE `medical_assessments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `medical_assessments_patient_id_created_at_index` (`patient_id`,`created_at`),
  ADD KEY `medical_assessments_nurse_id_created_at_index` (`nurse_id`,`created_at`),
  ADD KEY `medical_assessments_assessment_status_index` (`assessment_status`),
  ADD KEY `medical_assessments_general_condition_index` (`general_condition`),
  ADD KEY `medical_assessments_completed_at_index` (`completed_at`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mobile_money_otps`
--
ALTER TABLE `mobile_money_otps`
  ADD PRIMARY KEY (`id`),
  ADD KEY `mobile_money_otps_user_id_foreign` (`user_id`),
  ADD KEY `mobile_money_otps_phone_number_verified_expires_at_index` (`phone_number`,`verified`,`expires_at`);

--
-- Indexes for table `mobile_notifications`
--
ALTER TABLE `mobile_notifications`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `mobile_notifications_user_id_unique` (`user_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `notifications_notifiable_type_notifiable_id_index` (`notifiable_type`,`notifiable_id`);

--
-- Indexes for table `notification_logs`
--
ALTER TABLE `notification_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `notification_logs_notifiable_type_notifiable_id_index` (`notifiable_type`,`notifiable_id`),
  ADD KEY `notification_logs_user_id_notification_type_index` (`user_id`,`notification_type`),
  ADD KEY `notification_logs_status_created_at_index` (`status`,`created_at`),
  ADD KEY `notification_logs_scheduled_for_index` (`scheduled_for`);

--
-- Indexes for table `notification_preferences`
--
ALTER TABLE `notification_preferences`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `notification_preferences_user_id_unique` (`user_id`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `password_resets_email_used_at_index` (`email`,`used_at`),
  ADD KEY `password_resets_expires_at_index` (`expires_at`),
  ADD KEY `password_resets_created_at_index` (`created_at`);

--
-- Indexes for table `patient_feedback`
--
ALTER TABLE `patient_feedback`
  ADD PRIMARY KEY (`id`),
  ADD KEY `patient_feedback_patient_id_created_at_index` (`patient_id`,`created_at`),
  ADD KEY `patient_feedback_nurse_id_rating_index` (`nurse_id`,`rating`),
  ADD KEY `patient_feedback_nurse_id_created_at_index` (`nurse_id`,`created_at`),
  ADD KEY `patient_feedback_responded_by_foreign` (`responded_by`),
  ADD KEY `patient_feedback_status_created_at_index` (`status`,`created_at`),
  ADD KEY `patient_feedback_schedule_id_index` (`schedule_id`);

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `permissions_name_unique` (`name`),
  ADD KEY `permissions_category_subcategory_index` (`category`,`subcategory`);

--
-- Indexes for table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  ADD KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`),
  ADD KEY `personal_access_tokens_expires_at_index` (`expires_at`);

--
-- Indexes for table `progress_notes`
--
ALTER TABLE `progress_notes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `progress_notes_patient_id_visit_date_index` (`patient_id`,`visit_date`),
  ADD KEY `progress_notes_nurse_id_visit_date_index` (`nurse_id`,`visit_date`),
  ADD KEY `progress_notes_visit_date_index` (`visit_date`),
  ADD KEY `progress_notes_general_condition_index` (`general_condition`),
  ADD KEY `progress_notes_created_at_index` (`created_at`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `roles_name_unique` (`name`);

--
-- Indexes for table `role_permission`
--
ALTER TABLE `role_permission`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `role_permission_role_id_permission_id_unique` (`role_id`,`permission_id`),
  ADD KEY `role_permission_permission_id_foreign` (`permission_id`);

--
-- Indexes for table `schedules`
--
ALTER TABLE `schedules`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `schedules_nurse_id_schedule_date_start_time_unique` (`nurse_id`,`schedule_date`,`start_time`),
  ADD KEY `schedules_created_by_foreign` (`created_by`),
  ADD KEY `schedules_care_plan_id_index` (`care_plan_id`),
  ADD KEY `schedules_schedule_date_nurse_id_index` (`schedule_date`,`nurse_id`),
  ADD KEY `schedules_nurse_id_status_index` (`nurse_id`,`status`),
  ADD KEY `schedules_schedule_date_status_index` (`schedule_date`,`status`);

--
-- Indexes for table `time_trackings`
--
ALTER TABLE `time_trackings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `time_trackings_care_plan_id_foreign` (`care_plan_id`),
  ADD KEY `time_trackings_approved_by_foreign` (`approved_by`),
  ADD KEY `time_trackings_nurse_id_start_time_index` (`nurse_id`,`start_time`),
  ADD KEY `time_trackings_status_nurse_id_index` (`status`,`nurse_id`),
  ADD KEY `time_trackings_schedule_id_status_index` (`schedule_id`,`status`),
  ADD KEY `time_trackings_patient_id_start_time_index` (`patient_id`,`start_time`),
  ADD KEY `time_trackings_created_at_nurse_id_index` (`created_at`,`nurse_id`);

--
-- Indexes for table `transport_requests`
--
ALTER TABLE `transport_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `transport_requests_patient_id_status_index` (`patient_id`,`status`),
  ADD KEY `transport_requests_driver_id_status_index` (`driver_id`,`status`),
  ADD KEY `transport_requests_scheduled_time_status_index` (`scheduled_time`,`status`),
  ADD KEY `transport_requests_transport_type_priority_index` (`transport_type`,`priority`),
  ADD KEY `transport_requests_requested_by_id_index` (`requested_by_id`);

--
-- Indexes for table `two_factor_codes`
--
ALTER TABLE `two_factor_codes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `two_factor_codes_user_id_used_at_index` (`user_id`,`used_at`),
  ADD KEY `two_factor_codes_expires_at_index` (`expires_at`),
  ADD KEY `two_factor_codes_created_at_index` (`created_at`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`),
  ADD UNIQUE KEY `users_ghana_card_number_unique` (`ghana_card_number`),
  ADD UNIQUE KEY `users_license_number_unique` (`license_number`),
  ADD KEY `users_verified_by_foreign` (`verified_by`),
  ADD KEY `users_role_is_active_index` (`role`,`is_active`),
  ADD KEY `users_verification_status_is_verified_index` (`verification_status`,`is_verified`),
  ADD KEY `users_email_verified_at_index` (`email_verified_at`),
  ADD KEY `users_last_login_at_index` (`last_login_at`),
  ADD KEY `users_license_number_role_index` (`license_number`,`role`),
  ADD KEY `users_role_id_index` (`role_id`);

--
-- Indexes for table `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_sessions_user_id_logged_out_at_index` (`user_id`,`logged_out_at`),
  ADD KEY `user_sessions_token_id_index` (`token_id`),
  ADD KEY `user_sessions_last_activity_index` (`last_activity`),
  ADD KEY `user_sessions_is_current_index` (`is_current`);

--
-- Indexes for table `vehicles`
--
ALTER TABLE `vehicles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `vehicles_vehicle_id_unique` (`vehicle_id`),
  ADD UNIQUE KEY `vehicles_registration_number_unique` (`registration_number`),
  ADD KEY `vehicles_vehicle_type_is_active_index` (`vehicle_type`,`is_active`),
  ADD KEY `vehicles_status_is_available_index` (`status`,`is_available`),
  ADD KEY `vehicles_insurance_expiry_index` (`insurance_expiry`),
  ADD KEY `vehicles_registration_expiry_index` (`registration_expiry`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `care_assignments`
--
ALTER TABLE `care_assignments`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `care_fee_structure`
--
ALTER TABLE `care_fee_structure`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `care_payments`
--
ALTER TABLE `care_payments`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `care_plans`
--
ALTER TABLE `care_plans`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=310;

--
-- AUTO_INCREMENT for table `care_requests`
--
ALTER TABLE `care_requests`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `drivers`
--
ALTER TABLE `drivers`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT for table `driver_vehicle_assignments`
--
ALTER TABLE `driver_vehicle_assignments`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `incident_reports`
--
ALTER TABLE `incident_reports`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=78;

--
-- AUTO_INCREMENT for table `login_history`
--
ALTER TABLE `login_history`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `medical_assessments`
--
ALTER TABLE `medical_assessments`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=61;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT for table `mobile_money_otps`
--
ALTER TABLE `mobile_money_otps`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `mobile_notifications`
--
ALTER TABLE `mobile_notifications`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `notification_logs`
--
ALTER TABLE `notification_logs`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notification_preferences`
--
ALTER TABLE `notification_preferences`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `patient_feedback`
--
ALTER TABLE `patient_feedback`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=57;

--
-- AUTO_INCREMENT for table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=255;

--
-- AUTO_INCREMENT for table `progress_notes`
--
ALTER TABLE `progress_notes`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=67;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `role_permission`
--
ALTER TABLE `role_permission`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=166;

--
-- AUTO_INCREMENT for table `schedules`
--
ALTER TABLE `schedules`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=546;

--
-- AUTO_INCREMENT for table `time_trackings`
--
ALTER TABLE `time_trackings`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `transport_requests`
--
ALTER TABLE `transport_requests`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `two_factor_codes`
--
ALTER TABLE `two_factor_codes`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=91;

--
-- AUTO_INCREMENT for table `user_sessions`
--
ALTER TABLE `user_sessions`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `vehicles`
--
ALTER TABLE `vehicles`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=54;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `care_assignments`
--
ALTER TABLE `care_assignments`
  ADD CONSTRAINT `care_assignments_admin_override_by_foreign` FOREIGN KEY (`admin_override_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `care_assignments_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `care_assignments_assigned_by_foreign` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `care_assignments_care_plan_id_foreign` FOREIGN KEY (`care_plan_id`) REFERENCES `care_plans` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `care_assignments_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `care_assignments_previous_assignment_id_foreign` FOREIGN KEY (`previous_assignment_id`) REFERENCES `care_assignments` (`id`),
  ADD CONSTRAINT `care_assignments_primary_nurse_id_foreign` FOREIGN KEY (`primary_nurse_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `care_assignments_secondary_nurse_id_foreign` FOREIGN KEY (`secondary_nurse_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `care_payments`
--
ALTER TABLE `care_payments`
  ADD CONSTRAINT `care_payments_care_request_id_foreign` FOREIGN KEY (`care_request_id`) REFERENCES `care_requests` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `care_payments_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `care_plans`
--
ALTER TABLE `care_plans`
  ADD CONSTRAINT `care_plans_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `care_plans_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `care_plans_doctor_id_foreign` FOREIGN KEY (`doctor_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `care_plans_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `care_plans_primary_nurse_id_foreign` FOREIGN KEY (`primary_nurse_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `care_plans_secondary_nurse_id_foreign` FOREIGN KEY (`secondary_nurse_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `care_requests`
--
ALTER TABLE `care_requests`
  ADD CONSTRAINT `care_requests_assigned_nurse_id_foreign` FOREIGN KEY (`assigned_nurse_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `care_requests_medical_assessment_id_foreign` FOREIGN KEY (`medical_assessment_id`) REFERENCES `medical_assessments` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `care_requests_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `drivers`
--
ALTER TABLE `drivers`
  ADD CONSTRAINT `drivers_suspended_by_foreign` FOREIGN KEY (`suspended_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `driver_vehicle_assignments`
--
ALTER TABLE `driver_vehicle_assignments`
  ADD CONSTRAINT `driver_vehicle_assignments_assigned_by_foreign` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `driver_vehicle_assignments_driver_id_foreign` FOREIGN KEY (`driver_id`) REFERENCES `drivers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `driver_vehicle_assignments_unassigned_by_foreign` FOREIGN KEY (`unassigned_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `driver_vehicle_assignments_vehicle_id_foreign` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `incident_reports`
--
ALTER TABLE `incident_reports`
  ADD CONSTRAINT `incident_reports_assigned_to_foreign` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `incident_reports_closed_by_foreign` FOREIGN KEY (`closed_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `incident_reports_last_updated_by_foreign` FOREIGN KEY (`last_updated_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `incident_reports_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `incident_reports_reported_by_foreign` FOREIGN KEY (`reported_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `incident_reports_resolved_by_foreign` FOREIGN KEY (`resolved_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `incident_reports_reviewed_by_foreign` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `login_history`
--
ALTER TABLE `login_history`
  ADD CONSTRAINT `login_history_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `medical_assessments`
--
ALTER TABLE `medical_assessments`
  ADD CONSTRAINT `medical_assessments_nurse_id_foreign` FOREIGN KEY (`nurse_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `medical_assessments_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `mobile_money_otps`
--
ALTER TABLE `mobile_money_otps`
  ADD CONSTRAINT `mobile_money_otps_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `mobile_notifications`
--
ALTER TABLE `mobile_notifications`
  ADD CONSTRAINT `mobile_notifications_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notification_logs`
--
ALTER TABLE `notification_logs`
  ADD CONSTRAINT `notification_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notification_preferences`
--
ALTER TABLE `notification_preferences`
  ADD CONSTRAINT `notification_preferences_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `patient_feedback`
--
ALTER TABLE `patient_feedback`
  ADD CONSTRAINT `patient_feedback_nurse_id_foreign` FOREIGN KEY (`nurse_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `patient_feedback_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `patient_feedback_responded_by_foreign` FOREIGN KEY (`responded_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `patient_feedback_schedule_id_foreign` FOREIGN KEY (`schedule_id`) REFERENCES `schedules` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `progress_notes`
--
ALTER TABLE `progress_notes`
  ADD CONSTRAINT `progress_notes_nurse_id_foreign` FOREIGN KEY (`nurse_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `progress_notes_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `role_permission`
--
ALTER TABLE `role_permission`
  ADD CONSTRAINT `role_permission_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `role_permission_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `schedules`
--
ALTER TABLE `schedules`
  ADD CONSTRAINT `schedules_care_plan_id_foreign` FOREIGN KEY (`care_plan_id`) REFERENCES `care_plans` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `schedules_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `schedules_nurse_id_foreign` FOREIGN KEY (`nurse_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `time_trackings`
--
ALTER TABLE `time_trackings`
  ADD CONSTRAINT `time_trackings_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `time_trackings_care_plan_id_foreign` FOREIGN KEY (`care_plan_id`) REFERENCES `care_plans` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `time_trackings_nurse_id_foreign` FOREIGN KEY (`nurse_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `time_trackings_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `time_trackings_schedule_id_foreign` FOREIGN KEY (`schedule_id`) REFERENCES `schedules` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `transport_requests`
--
ALTER TABLE `transport_requests`
  ADD CONSTRAINT `transport_requests_driver_id_foreign` FOREIGN KEY (`driver_id`) REFERENCES `drivers` (`id`),
  ADD CONSTRAINT `transport_requests_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `transport_requests_requested_by_id_foreign` FOREIGN KEY (`requested_by_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `two_factor_codes`
--
ALTER TABLE `two_factor_codes`
  ADD CONSTRAINT `two_factor_codes_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `users_verified_by_foreign` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD CONSTRAINT `user_sessions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
