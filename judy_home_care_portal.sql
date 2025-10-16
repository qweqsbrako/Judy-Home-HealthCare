-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3307
-- Generation Time: Oct 16, 2025 at 12:05 PM
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
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cache`
--

INSERT INTO `cache` (`key`, `value`, `expiration`) VALUES
('judy-home-care-cache-illuminate:queue:restart', 'i:1760457561;', 2075817561),
('judy-home-healthcare-cache-illuminate:queue:restart', 'i:1760558023;', 2075918023);

-- --------------------------------------------------------

--
-- Table structure for table `cache_locks`
--

CREATE TABLE `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
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
  `status` enum('pending','nurse_review','accepted','declined','active','on_hold','completed','cancelled','reassigned') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `assignment_type` enum('single_nurse','dual_nurse','team_care','rotating_care','emergency_assignment') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'single_nurse',
  `assigned_at` timestamp NOT NULL,
  `start_date` timestamp NOT NULL,
  `end_date` timestamp NULL DEFAULT NULL,
  `accepted_at` timestamp NULL DEFAULT NULL,
  `declined_at` timestamp NULL DEFAULT NULL,
  `assignment_notes` text COLLATE utf8mb4_unicode_ci,
  `special_requirements` text COLLATE utf8mb4_unicode_ci,
  `nurse_qualifications_matched` json DEFAULT NULL,
  `nurse_response_notes` text COLLATE utf8mb4_unicode_ci,
  `decline_reason` text COLLATE utf8mb4_unicode_ci,
  `response_time_hours` int DEFAULT NULL,
  `patient_address` json DEFAULT NULL,
  `estimated_travel_time` decimal(5,2) DEFAULT NULL,
  `distance_km` decimal(8,2) DEFAULT NULL,
  `estimated_hours_per_day` int DEFAULT NULL,
  `total_estimated_hours` int DEFAULT NULL,
  `intensity_level` enum('light','moderate','intensive','critical') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'moderate',
  `skill_match_score` int DEFAULT NULL,
  `location_match_score` int DEFAULT NULL,
  `availability_match_score` int DEFAULT NULL,
  `workload_balance_score` int DEFAULT NULL,
  `admin_override` tinyint(1) NOT NULL DEFAULT '0',
  `admin_override_reason` text COLLATE utf8mb4_unicode_ci,
  `admin_override_at` timestamp NULL DEFAULT NULL,
  `overall_match_score` int DEFAULT NULL,
  `previous_assignment_id` bigint UNSIGNED DEFAULT NULL,
  `reassignment_count` int NOT NULL DEFAULT '0',
  `reassignment_reason` text COLLATE utf8mb4_unicode_ci,
  `actual_start_date` timestamp NULL DEFAULT NULL,
  `actual_end_date` timestamp NULL DEFAULT NULL,
  `completion_percentage` int NOT NULL DEFAULT '0',
  `performance_metrics` json DEFAULT NULL,
  `is_emergency` tinyint(1) NOT NULL DEFAULT '0',
  `priority_level` enum('low','medium','high','urgent') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'medium',
  `emergency_assigned_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `admin_override_by` bigint UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `care_plans`
--

CREATE TABLE `care_plans` (
  `id` bigint UNSIGNED NOT NULL,
  `patient_id` bigint UNSIGNED NOT NULL,
  `doctor_id` bigint UNSIGNED NOT NULL,
  `created_by` bigint UNSIGNED NOT NULL,
  `approved_by` bigint UNSIGNED DEFAULT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `care_type` enum('general_care','elderly_care','pediatric_care','chronic_disease_management','palliative_care','rehabilitation_care') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('draft','pending_approval','approved','active','on_hold','completed','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `frequency` enum('once_daily','twice_daily','three_times_daily','every_12_hours','every_8_hours','every_6_hours','every_4_hours','weekly','twice_weekly','as_needed','custom') COLLATE utf8mb4_unicode_ci NOT NULL,
  `custom_frequency_details` text COLLATE utf8mb4_unicode_ci,
  `care_tasks` json NOT NULL,
  `priority` enum('low','medium','high','critical') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'medium',
  `approved_at` timestamp NULL DEFAULT NULL,
  `completion_percentage` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `primary_nurse_id` bigint UNSIGNED DEFAULT NULL,
  `secondary_nurse_id` bigint UNSIGNED DEFAULT NULL,
  `assignment_notes` text COLLATE utf8mb4_unicode_ci,
  `estimated_hours_per_day` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `care_plans`
--

INSERT INTO `care_plans` (`id`, `patient_id`, `doctor_id`, `created_by`, `approved_by`, `title`, `description`, `care_type`, `status`, `start_date`, `end_date`, `frequency`, `custom_frequency_details`, `care_tasks`, `priority`, `approved_at`, `completion_percentage`, `created_at`, `updated_at`, `deleted_at`, `primary_nurse_id`, `secondary_nurse_id`, `assignment_notes`, `estimated_hours_per_day`) VALUES
(257, 5, 69, 69, NULL, 'Palliative Care Plan - Robert Ben Brown', 'Comfort-focused care emphasizing pain and symptom management, quality of life, and dignity for patients with serious illness. Holistic approach including physical, emotional, and spiritual support for patient and family.', 'palliative_care', 'draft', '2025-10-17', NULL, 'custom', 'Every other day at 9:00 AM and 3:00 PM', '[\"Provide comprehensive pain assessment and management\", \"Manage symptoms for maximum comfort (nausea, breathlessness, etc.)\", \"Assist with all personal care needs gently and respectfully\", \"Provide emotional and spiritual support\", \"Support family members and answer questions\", \"Coordinate with hospice or palliative care team\", \"Maintain patient dignity and respect wishes\", \"Create peaceful, comfortable environment\", \"Offer comfort measures (positioning, massage, music)\", \"Document comfort levels and symptom management\"]', 'medium', NULL, 0, '2025-10-07 00:00:00', '2025-10-14 23:09:06', NULL, NULL, NULL, NULL, NULL),
(258, 37, 75, 75, NULL, 'Palliative Care Plan - Adrienne Dickinson', 'Comfort-focused care emphasizing pain and symptom management, quality of life, and dignity for patients with serious illness. Holistic approach including physical, emotional, and spiritual support for patient and family.', 'palliative_care', 'draft', '2025-10-26', '2026-01-08', 'once_daily', NULL, '[\"Provide comprehensive pain assessment and management\", \"Manage symptoms for maximum comfort (nausea, breathlessness, etc.)\", \"Assist with all personal care needs gently and respectfully\", \"Provide emotional and spiritual support\", \"Support family members and answer questions\", \"Coordinate with hospice or palliative care team\", \"Maintain patient dignity and respect wishes\", \"Create peaceful, comfortable environment\", \"Offer comfort measures (positioning, massage, music)\", \"Document comfort levels and symptom management\"]', 'medium', NULL, 0, '2025-10-07 00:00:00', '2025-10-14 23:09:06', NULL, NULL, NULL, NULL, NULL),
(259, 52, 73, 2, NULL, 'General Home Care Plan - Murphy Nienow', 'Comprehensive general care including daily living assistance, medication management, and basic health monitoring. This plan focuses on maintaining patient independence while providing necessary support for activities of daily living.', 'general_care', 'draft', '2025-10-22', '2025-11-30', 'as_needed', NULL, '[\"Assist with personal hygiene and grooming\", \"Help with meal preparation and feeding as needed\", \"Administer prescribed medications on schedule\", \"Monitor and record vital signs daily\", \"Assist with mobility and safe transfers\", \"Provide companionship and emotional support\", \"Maintain clean and safe living environment\", \"Document daily activities and observations\"]', 'critical', NULL, 0, '2025-10-10 00:00:00', '2025-10-14 23:09:06', NULL, NULL, NULL, NULL, NULL),
(260, 19, 73, 73, NULL, 'Chronic Disease Management Plan - Ray Wintheiser', 'Comprehensive management of chronic conditions with focus on medication compliance, symptom monitoring, lifestyle modifications, and preventing complications. Coordinated care approach with healthcare team.', 'chronic_disease_management', 'draft', '2025-10-25', '2026-06-08', 'twice_weekly', NULL, '[\"Administer complex medication regimens accurately\", \"Monitor disease-specific symptoms and vital signs\", \"Record detailed health metrics and trends\", \"Assist with specialized medical equipment\", \"Support prescribed dietary modifications\", \"Coordinate with healthcare providers and specialists\", \"Provide patient education on disease self-management\", \"Monitor for medication side effects or complications\", \"Encourage adherence to treatment plans\", \"Document all health changes and communicate promptly\"]', 'medium', NULL, 0, '2025-10-08 00:00:00', '2025-10-14 23:09:06', NULL, NULL, NULL, NULL, NULL),
(261, 39, 80, 80, NULL, 'Elderly Care Plan - Jarvis Dicki', 'Specialized care for elderly patients focusing on dignity, independence, and quality of life. Includes comprehensive support for age-related needs, fall prevention, cognitive engagement, and social interaction.', 'elderly_care', 'draft', '2025-10-16', '2026-06-26', 'once_daily', NULL, '[\"Assist with bathing and personal care with dignity\", \"Help with dressing and grooming routines\", \"Prepare nutritious meals appropriate for dietary needs\", \"Administer medications and track medication schedule\", \"Provide mobility support and fall prevention measures\", \"Engage in cognitive stimulation activities\", \"Monitor for signs of confusion or changes in behavior\", \"Encourage social interaction and meaningful activities\", \"Regular position changes to prevent pressure sores\", \"Document any changes in condition immediately\"]', 'high', NULL, 0, '2025-10-09 00:00:00', '2025-10-14 23:09:06', NULL, NULL, NULL, NULL, NULL),
(262, 22, 69, 69, NULL, 'General Home Care Plan - Samara Bergnaum', 'Comprehensive general care including daily living assistance, medication management, and basic health monitoring. This plan focuses on maintaining patient independence while providing necessary support for activities of daily living.', 'general_care', 'pending_approval', '2025-10-15', '2025-12-25', 'weekly', NULL, '[\"Assist with personal hygiene and grooming\", \"Help with meal preparation and feeding as needed\", \"Administer prescribed medications on schedule\", \"Monitor and record vital signs daily\", \"Assist with mobility and safe transfers\", \"Provide companionship and emotional support\", \"Maintain clean and safe living environment\", \"Document daily activities and observations\"]', 'high', NULL, 0, '2025-10-07 00:00:00', '2025-10-14 23:09:06', NULL, NULL, NULL, NULL, NULL),
(263, 42, 80, 2, NULL, 'Rehabilitation Care Plan - Desiree Mraz', 'Goal-oriented care focused on recovering function, improving strength, and regaining independence after illness or injury. Progressive approach with therapeutic exercises, mobility training, and continuous progress monitoring.', 'rehabilitation_care', 'pending_approval', '2025-10-15', NULL, 'twice_daily', NULL, '[\"Assist with prescribed rehabilitation exercises\", \"Support physical therapy activities and goals\", \"Help with occupational therapy tasks\", \"Monitor progress toward specific recovery goals\", \"Encourage increasing independence in daily activities\", \"Prevent complications during recovery period\", \"Provide motivation and positive reinforcement\", \"Assist with adaptive equipment and techniques\", \"Document functional improvements and setbacks\", \"Coordinate with therapy team on progress\"]', 'low', NULL, 0, '2025-09-25 00:00:00', '2025-10-14 23:09:06', NULL, NULL, NULL, NULL, NULL),
(264, 26, 73, 81, NULL, 'Chronic Disease Management Plan - Felix Cassin', 'Comprehensive management of chronic conditions with focus on medication compliance, symptom monitoring, lifestyle modifications, and preventing complications. Coordinated care approach with healthcare team.', 'chronic_disease_management', 'pending_approval', '2025-10-20', '2026-03-18', 'three_times_daily', NULL, '[\"Administer complex medication regimens accurately\", \"Monitor disease-specific symptoms and vital signs\", \"Record detailed health metrics and trends\", \"Assist with specialized medical equipment\", \"Support prescribed dietary modifications\", \"Coordinate with healthcare providers and specialists\", \"Provide patient education on disease self-management\", \"Monitor for medication side effects or complications\", \"Encourage adherence to treatment plans\", \"Document all health changes and communicate promptly\"]', 'low', NULL, 0, '2025-10-03 00:00:00', '2025-10-14 23:09:06', NULL, NULL, NULL, NULL, NULL),
(265, 30, 77, 77, NULL, 'Elderly Care Plan - Loma Beatty', 'Specialized care for elderly patients focusing on dignity, independence, and quality of life. Includes comprehensive support for age-related needs, fall prevention, cognitive engagement, and social interaction.', 'elderly_care', 'pending_approval', '2025-10-26', '2026-09-09', 'twice_daily', NULL, '[\"Assist with bathing and personal care with dignity\", \"Help with dressing and grooming routines\", \"Prepare nutritious meals appropriate for dietary needs\", \"Administer medications and track medication schedule\", \"Provide mobility support and fall prevention measures\", \"Engage in cognitive stimulation activities\", \"Monitor for signs of confusion or changes in behavior\", \"Encourage social interaction and meaningful activities\", \"Regular position changes to prevent pressure sores\", \"Document any changes in condition immediately\"]', 'medium', NULL, 0, '2025-10-06 00:00:00', '2025-10-14 23:09:06', NULL, NULL, NULL, NULL, NULL),
(266, 45, 74, 74, NULL, 'Rehabilitation Care Plan - Felipe Schowalter', 'Goal-oriented care focused on recovering function, improving strength, and regaining independence after illness or injury. Progressive approach with therapeutic exercises, mobility training, and continuous progress monitoring.', 'rehabilitation_care', 'pending_approval', '2025-10-18', '2026-01-12', 'every_12_hours', NULL, '[\"Assist with prescribed rehabilitation exercises\", \"Support physical therapy activities and goals\", \"Help with occupational therapy tasks\", \"Monitor progress toward specific recovery goals\", \"Encourage increasing independence in daily activities\", \"Prevent complications during recovery period\", \"Provide motivation and positive reinforcement\", \"Assist with adaptive equipment and techniques\", \"Document functional improvements and setbacks\", \"Coordinate with therapy team on progress\"]', 'medium', NULL, 0, '2025-10-02 00:00:00', '2025-10-14 23:09:06', NULL, NULL, NULL, NULL, NULL),
(267, 34, 72, 72, 81, 'Chronic Disease Management Plan - Tod Huels', 'Comprehensive management of chronic conditions with focus on medication compliance, symptom monitoring, lifestyle modifications, and preventing complications. Coordinated care approach with healthcare team.', 'chronic_disease_management', 'active', '2025-09-22', NULL, 'once_daily', NULL, '[\"Administer complex medication regimens accurately\", \"Monitor disease-specific symptoms and vital signs\", \"Record detailed health metrics and trends\", \"Assist with specialized medical equipment\", \"Support prescribed dietary modifications\", \"Coordinate with healthcare providers and specialists\", \"Provide patient education on disease self-management\", \"Monitor for medication side effects or complications\", \"Encourage adherence to treatment plans\", \"Document all health changes and communicate promptly\"]', 'low', '2025-09-20 00:00:00', 45, '2025-09-13 00:00:00', '2025-10-14 23:09:06', NULL, 61, 56, 'Strict medication adherence is critical for disease management. Monitor symptoms closely and report any changes immediately to the healthcare team. Patient education ongoing.', NULL),
(268, 43, 75, 75, 2, 'General Home Care Plan - Katlyn Schneider', 'Comprehensive general care including daily living assistance, medication management, and basic health monitoring. This plan focuses on maintaining patient independence while providing necessary support for activities of daily living.', 'general_care', 'active', '2025-09-11', '2025-10-12', 'three_times_daily', NULL, '[\"Assist with personal hygiene and grooming\", \"Help with meal preparation and feeding as needed\", \"Administer prescribed medications on schedule\", \"Monitor and record vital signs daily\", \"Assist with mobility and safe transfers\", \"Provide companionship and emotional support\", \"Maintain clean and safe living environment\", \"Document daily activities and observations\"]', 'low', '2025-09-09 00:00:00', 67, '2025-08-30 00:00:00', '2025-10-14 23:09:06', NULL, 57, NULL, 'Patient requires gentle, patient-centered care. Please maintain consistent schedule and document any changes in condition or behavior.', NULL),
(269, 36, 77, 77, 1, 'General Home Care Plan - Mauricio Batz', 'Comprehensive general care including daily living assistance, medication management, and basic health monitoring. This plan focuses on maintaining patient independence while providing necessary support for activities of daily living.', 'general_care', 'active', '2025-09-29', '2025-11-05', 'every_8_hours', NULL, '[\"Assist with personal hygiene and grooming\", \"Help with meal preparation and feeding as needed\", \"Administer prescribed medications on schedule\", \"Monitor and record vital signs daily\", \"Assist with mobility and safe transfers\", \"Provide companionship and emotional support\", \"Maintain clean and safe living environment\", \"Document daily activities and observations\"]', 'critical', '2025-09-26 00:00:00', 31, '2025-09-11 00:00:00', '2025-10-14 23:09:06', NULL, 65, NULL, 'Patient requires gentle, patient-centered care. Please maintain consistent schedule and document any changes in condition or behavior.', NULL),
(270, 7, 74, 2, 2, 'General Home Care Plan - Granit Xhaka', 'Comprehensive general care including daily living assistance, medication management, and basic health monitoring. This plan focuses on maintaining patient independence while providing necessary support for activities of daily living.', 'general_care', 'active', '2025-09-07', '2025-11-11', 'as_needed', NULL, '[\"Assist with personal hygiene and grooming\", \"Help with meal preparation and feeding as needed\", \"Administer prescribed medications on schedule\", \"Monitor and record vital signs daily\", \"Assist with mobility and safe transfers\", \"Provide companionship and emotional support\", \"Maintain clean and safe living environment\", \"Document daily activities and observations\"]', 'high', '2025-09-05 00:00:00', 75, '2025-08-29 00:00:00', '2025-10-14 23:09:06', NULL, 66, 4, 'Patient requires gentle, patient-centered care. Please maintain consistent schedule and document any changes in condition or behavior.', NULL),
(271, 22, 80, 80, 1, 'General Home Care Plan - Samara Bergnaum', 'Comprehensive general care including daily living assistance, medication management, and basic health monitoring. This plan focuses on maintaining patient independence while providing necessary support for activities of daily living.', 'general_care', 'active', '2025-08-20', '2025-10-31', 'every_6_hours', NULL, '[\"Assist with personal hygiene and grooming\", \"Help with meal preparation and feeding as needed\", \"Administer prescribed medications on schedule\", \"Monitor and record vital signs daily\", \"Assist with mobility and safe transfers\", \"Provide companionship and emotional support\", \"Maintain clean and safe living environment\", \"Document daily activities and observations\"]', 'medium', '2025-08-18 00:00:00', 95, '2025-07-31 00:00:00', '2025-10-14 23:09:06', NULL, 61, 54, 'Patient requires gentle, patient-centered care. Please maintain consistent schedule and document any changes in condition or behavior.', NULL),
(272, 31, 71, 71, 1, 'Palliative Care Plan - Shaniya Goldner', 'Comfort-focused care emphasizing pain and symptom management, quality of life, and dignity for patients with serious illness. Holistic approach including physical, emotional, and spiritual support for patient and family.', 'palliative_care', 'active', '2025-09-17', '2025-11-11', 'every_4_hours', NULL, '[\"Provide comprehensive pain assessment and management\", \"Manage symptoms for maximum comfort (nausea, breathlessness, etc.)\", \"Assist with all personal care needs gently and respectfully\", \"Provide emotional and spiritual support\", \"Support family members and answer questions\", \"Coordinate with hospice or palliative care team\", \"Maintain patient dignity and respect wishes\", \"Create peaceful, comfortable environment\", \"Offer comfort measures (positioning, massage, music)\", \"Document comfort levels and symptom management\"]', 'medium', '2025-09-15 00:00:00', 55, '2025-09-07 00:00:00', '2025-10-14 23:09:06', NULL, 57, 56, 'Focus on comfort, dignity, and quality of life. Family involvement is important. Follow pain management protocol carefully. Respect patient and family wishes at all times.', NULL),
(273, 16, 77, 77, 81, 'Chronic Disease Management Plan - Micheal Asamoah', 'Comprehensive management of chronic conditions with focus on medication compliance, symptom monitoring, lifestyle modifications, and preventing complications. Coordinated care approach with healthcare team.', 'chronic_disease_management', 'active', '2025-08-21', '2026-06-25', 'every_12_hours', NULL, '[\"Administer complex medication regimens accurately\", \"Monitor disease-specific symptoms and vital signs\", \"Record detailed health metrics and trends\", \"Assist with specialized medical equipment\", \"Support prescribed dietary modifications\", \"Coordinate with healthcare providers and specialists\", \"Provide patient education on disease self-management\", \"Monitor for medication side effects or complications\", \"Encourage adherence to treatment plans\", \"Document all health changes and communicate promptly\"]', 'low', '2025-08-20 00:00:00', 95, '2025-08-07 00:00:00', '2025-10-14 23:09:06', NULL, 58, 79, 'Strict medication adherence is critical for disease management. Monitor symptoms closely and report any changes immediately to the healthcare team. Patient education ongoing.', NULL),
(274, 23, 69, 69, 2, 'General Home Care Plan - Trever Stamm', 'Comprehensive general care including daily living assistance, medication management, and basic health monitoring. This plan focuses on maintaining patient independence while providing necessary support for activities of daily living.', 'general_care', 'active', '2025-08-27', '2025-10-26', 'every_12_hours', NULL, '[\"Assist with personal hygiene and grooming\", \"Help with meal preparation and feeding as needed\", \"Administer prescribed medications on schedule\", \"Monitor and record vital signs daily\", \"Assist with mobility and safe transfers\", \"Provide companionship and emotional support\", \"Maintain clean and safe living environment\", \"Document daily activities and observations\"]', 'medium', '2025-08-26 00:00:00', 95, '2025-08-16 00:00:00', '2025-10-14 23:09:06', NULL, 59, NULL, 'Patient requires gentle, patient-centered care. Please maintain consistent schedule and document any changes in condition or behavior.', NULL),
(275, 16, 70, 70, 81, 'Palliative Care Plan - Micheal Asamoah', 'Comfort-focused care emphasizing pain and symptom management, quality of life, and dignity for patients with serious illness. Holistic approach including physical, emotional, and spiritual support for patient and family.', 'palliative_care', 'active', '2025-10-07', '2026-01-05', 'once_daily', NULL, '[\"Provide comprehensive pain assessment and management\", \"Manage symptoms for maximum comfort (nausea, breathlessness, etc.)\", \"Assist with all personal care needs gently and respectfully\", \"Provide emotional and spiritual support\", \"Support family members and answer questions\", \"Coordinate with hospice or palliative care team\", \"Maintain patient dignity and respect wishes\", \"Create peaceful, comfortable environment\", \"Offer comfort measures (positioning, massage, music)\", \"Document comfort levels and symptom management\"]', 'medium', '2025-10-06 00:00:00', 15, '2025-09-26 00:00:00', '2025-10-14 23:09:06', NULL, 61, NULL, 'Focus on comfort, dignity, and quality of life. Family involvement is important. Follow pain management protocol carefully. Respect patient and family wishes at all times.', NULL),
(276, 78, 73, 73, 2, 'Elderly Care Plan - Test Patient', 'Specialized care for elderly patients focusing on dignity, independence, and quality of life. Includes comprehensive support for age-related needs, fall prevention, cognitive engagement, and social interaction.', 'elderly_care', 'active', '2025-09-17', '2026-07-21', 'twice_daily', NULL, '[\"Assist with bathing and personal care with dignity\", \"Help with dressing and grooming routines\", \"Prepare nutritious meals appropriate for dietary needs\", \"Administer medications and track medication schedule\", \"Provide mobility support and fall prevention measures\", \"Engage in cognitive stimulation activities\", \"Monitor for signs of confusion or changes in behavior\", \"Encourage social interaction and meaningful activities\", \"Regular position changes to prevent pressure sores\", \"Document any changes in condition immediately\"]', 'medium', '2025-09-16 00:00:00', 55, '2025-09-10 00:00:00', '2025-10-14 23:09:06', NULL, 55, 62, 'Patient may have cognitive changes and requires patience and reassurance. Fall risk precautions must be maintained at all times. Encourage independence while ensuring safety.', NULL),
(277, 50, 72, 72, 1, 'Elderly Care Plan - Chester Shields', 'Specialized care for elderly patients focusing on dignity, independence, and quality of life. Includes comprehensive support for age-related needs, fall prevention, cognitive engagement, and social interaction.', 'elderly_care', 'active', '2025-09-12', '2026-02-24', 'every_12_hours', NULL, '[\"Assist with bathing and personal care with dignity\", \"Help with dressing and grooming routines\", \"Prepare nutritious meals appropriate for dietary needs\", \"Administer medications and track medication schedule\", \"Provide mobility support and fall prevention measures\", \"Engage in cognitive stimulation activities\", \"Monitor for signs of confusion or changes in behavior\", \"Encourage social interaction and meaningful activities\", \"Regular position changes to prevent pressure sores\", \"Document any changes in condition immediately\"]', 'critical', '2025-09-11 00:00:00', 65, '2025-08-24 00:00:00', '2025-10-14 23:09:06', NULL, 57, NULL, 'Patient may have cognitive changes and requires patience and reassurance. Fall risk precautions must be maintained at all times. Encourage independence while ensuring safety.', NULL),
(278, 5, 75, 75, 81, 'Pediatric Home Care Plan - Robert Ben Brown', 'Specialized care for children including developmental support, medication management, family education, and age-appropriate activities. Focus on child safety, comfort, and supporting normal development.', 'pediatric_care', 'active', '2025-09-30', '2025-11-06', 'every_6_hours', NULL, '[\"Administer age-appropriate medications safely\", \"Monitor growth, development, and vital signs\", \"Assist with feeding and ensure proper nutrition\", \"Provide age-appropriate play and learning activities\", \"Monitor for signs of illness, pain, or discomfort\", \"Educate parents on care routines and procedures\", \"Maintain clean, safe, and child-friendly environment\", \"Support developmental milestones and activities\", \"Provide comfort measures and emotional support\", \"Document feeding, elimination, and behavior patterns\"]', 'high', '2025-09-28 00:00:00', 29, '2025-09-15 00:00:00', '2025-10-14 23:09:06', NULL, 4, 54, 'Child requires age-appropriate communication and activities. Keep parents informed of all care activities, changes, and concerns. Maintain safe, child-friendly environment.', NULL),
(279, 19, 75, 75, 81, 'General Home Care Plan - Ray Wintheiser', 'Comprehensive general care including daily living assistance, medication management, and basic health monitoring. This plan focuses on maintaining patient independence while providing necessary support for activities of daily living.', 'general_care', 'active', '2025-08-31', '2025-10-08', 'twice_daily', NULL, '[\"Assist with personal hygiene and grooming\", \"Help with meal preparation and feeding as needed\", \"Administer prescribed medications on schedule\", \"Monitor and record vital signs daily\", \"Assist with mobility and safe transfers\", \"Provide companionship and emotional support\", \"Maintain clean and safe living environment\", \"Document daily activities and observations\"]', 'medium', '2025-08-29 00:00:00', 89, '2025-08-21 00:00:00', '2025-10-14 23:09:06', NULL, 65, 60, 'Patient requires gentle, patient-centered care. Please maintain consistent schedule and document any changes in condition or behavior.', NULL),
(280, 24, 71, 71, 1, 'Chronic Disease Management Plan - Aliza Pfannerstill', 'Comprehensive management of chronic conditions with focus on medication compliance, symptom monitoring, lifestyle modifications, and preventing complications. Coordinated care approach with healthcare team.', 'chronic_disease_management', 'active', '2025-08-30', '2026-02-10', 'three_times_daily', NULL, '[\"Administer complex medication regimens accurately\", \"Monitor disease-specific symptoms and vital signs\", \"Record detailed health metrics and trends\", \"Assist with specialized medical equipment\", \"Support prescribed dietary modifications\", \"Coordinate with healthcare providers and specialists\", \"Provide patient education on disease self-management\", \"Monitor for medication side effects or complications\", \"Encourage adherence to treatment plans\", \"Document all health changes and communicate promptly\"]', 'critical', '2025-08-27 00:00:00', 91, '2025-08-18 00:00:00', '2025-10-14 23:09:06', NULL, 53, 66, 'Strict medication adherence is critical for disease management. Monitor symptoms closely and report any changes immediately to the healthcare team. Patient education ongoing.', NULL),
(281, 8, 71, 71, 81, 'Chronic Disease Management Plan - Philip Gbeko', 'Comprehensive management of chronic conditions with focus on medication compliance, symptom monitoring, lifestyle modifications, and preventing complications. Coordinated care approach with healthcare team.', 'chronic_disease_management', 'active', '2025-09-21', NULL, 'twice_weekly', NULL, '[\"Administer complex medication regimens accurately\", \"Monitor disease-specific symptoms and vital signs\", \"Record detailed health metrics and trends\", \"Assist with specialized medical equipment\", \"Support prescribed dietary modifications\", \"Coordinate with healthcare providers and specialists\", \"Provide patient education on disease self-management\", \"Monitor for medication side effects or complications\", \"Encourage adherence to treatment plans\", \"Document all health changes and communicate promptly\"]', 'high', '2025-09-18 00:00:00', 47, '2025-09-08 00:00:00', '2025-10-14 23:09:06', NULL, 55, NULL, 'Strict medication adherence is critical for disease management. Monitor symptoms closely and report any changes immediately to the healthcare team. Patient education ongoing.', NULL),
(282, 49, 3, 3, 1, 'Palliative Care Plan - Ted Howell', 'Comfort-focused care emphasizing pain and symptom management, quality of life, and dignity for patients with serious illness. Holistic approach including physical, emotional, and spiritual support for patient and family.', 'palliative_care', 'active', '2025-08-28', '2025-11-04', 'every_8_hours', NULL, '[\"Provide comprehensive pain assessment and management\", \"Manage symptoms for maximum comfort (nausea, breathlessness, etc.)\", \"Assist with all personal care needs gently and respectfully\", \"Provide emotional and spiritual support\", \"Support family members and answer questions\", \"Coordinate with hospice or palliative care team\", \"Maintain patient dignity and respect wishes\", \"Create peaceful, comfortable environment\", \"Offer comfort measures (positioning, massage, music)\", \"Document comfort levels and symptom management\"]', 'medium', '2025-08-25 00:00:00', 95, '2025-08-17 00:00:00', '2025-10-14 23:09:06', NULL, 4, NULL, 'Focus on comfort, dignity, and quality of life. Family involvement is important. Follow pain management protocol carefully. Respect patient and family wishes at all times.', NULL),
(283, 39, 73, 73, 81, 'Rehabilitation Care Plan - Jarvis Dicki', 'Goal-oriented care focused on recovering function, improving strength, and regaining independence after illness or injury. Progressive approach with therapeutic exercises, mobility training, and continuous progress monitoring.', 'rehabilitation_care', 'active', '2025-08-16', NULL, 'every_8_hours', NULL, '[\"Assist with prescribed rehabilitation exercises\", \"Support physical therapy activities and goals\", \"Help with occupational therapy tasks\", \"Monitor progress toward specific recovery goals\", \"Encourage increasing independence in daily activities\", \"Prevent complications during recovery period\", \"Provide motivation and positive reinforcement\", \"Assist with adaptive equipment and techniques\", \"Document functional improvements and setbacks\", \"Coordinate with therapy team on progress\"]', 'high', '2025-08-14 00:00:00', 95, '2025-07-31 00:00:00', '2025-10-14 23:09:06', NULL, 56, NULL, 'Patient is motivated but may experience frustration during recovery. Provide encouragement and positive reinforcement. Follow therapy protocols precisely and document progress.', NULL),
(284, 31, 76, 76, 81, 'Rehabilitation Care Plan - Shaniya Goldner', 'Goal-oriented care focused on recovering function, improving strength, and regaining independence after illness or injury. Progressive approach with therapeutic exercises, mobility training, and continuous progress monitoring.', 'rehabilitation_care', 'active', '2025-10-11', '2025-12-03', 'once_daily', NULL, '[\"Assist with prescribed rehabilitation exercises\", \"Support physical therapy activities and goals\", \"Help with occupational therapy tasks\", \"Monitor progress toward specific recovery goals\", \"Encourage increasing independence in daily activities\", \"Prevent complications during recovery period\", \"Provide motivation and positive reinforcement\", \"Assist with adaptive equipment and techniques\", \"Document functional improvements and setbacks\", \"Coordinate with therapy team on progress\"]', 'critical', '2025-10-08 00:00:00', 10, '2025-10-04 00:00:00', '2025-10-14 23:09:06', NULL, 64, NULL, 'Patient is motivated but may experience frustration during recovery. Provide encouragement and positive reinforcement. Follow therapy protocols precisely and document progress.', NULL),
(285, 40, 71, 71, 1, 'Pediatric Home Care Plan - Juston Roob', 'Specialized care for children including developmental support, medication management, family education, and age-appropriate activities. Focus on child safety, comfort, and supporting normal development.', 'pediatric_care', 'active', '2025-09-17', '2025-10-12', 'custom', 'Three times weekly on alternate days', '[\"Administer age-appropriate medications safely\", \"Monitor growth, development, and vital signs\", \"Assist with feeding and ensure proper nutrition\", \"Provide age-appropriate play and learning activities\", \"Monitor for signs of illness, pain, or discomfort\", \"Educate parents on care routines and procedures\", \"Maintain clean, safe, and child-friendly environment\", \"Support developmental milestones and activities\", \"Provide comfort measures and emotional support\", \"Document feeding, elimination, and behavior patterns\"]', 'high', '2025-09-14 00:00:00', 55, '2025-09-04 00:00:00', '2025-10-14 23:09:06', NULL, 65, NULL, 'Child requires age-appropriate communication and activities. Keep parents informed of all care activities, changes, and concerns. Maintain safe, child-friendly environment.', NULL),
(286, 19, 69, 69, 2, 'Pediatric Home Care Plan - Ray Wintheiser', 'Specialized care for children including developmental support, medication management, family education, and age-appropriate activities. Focus on child safety, comfort, and supporting normal development.', 'pediatric_care', 'active', '2025-10-09', '2025-11-22', 'every_4_hours', NULL, '[\"Administer age-appropriate medications safely\", \"Monitor growth, development, and vital signs\", \"Assist with feeding and ensure proper nutrition\", \"Provide age-appropriate play and learning activities\", \"Monitor for signs of illness, pain, or discomfort\", \"Educate parents on care routines and procedures\", \"Maintain clean, safe, and child-friendly environment\", \"Support developmental milestones and activities\", \"Provide comfort measures and emotional support\", \"Document feeding, elimination, and behavior patterns\"]', 'high', '2025-10-08 00:00:00', 11, '2025-09-29 00:00:00', '2025-10-14 23:09:06', NULL, 58, 53, 'Child requires age-appropriate communication and activities. Keep parents informed of all care activities, changes, and concerns. Maintain safe, child-friendly environment.', NULL),
(287, 30, 80, 2, 1, 'Elderly Care Plan - Loma Beatty', 'Specialized care for elderly patients focusing on dignity, independence, and quality of life. Includes comprehensive support for age-related needs, fall prevention, cognitive engagement, and social interaction.', 'elderly_care', 'active', '2025-10-11', '2026-10-08', 'once_daily', NULL, '[\"Assist with bathing and personal care with dignity\", \"Help with dressing and grooming routines\", \"Prepare nutritious meals appropriate for dietary needs\", \"Administer medications and track medication schedule\", \"Provide mobility support and fall prevention measures\", \"Engage in cognitive stimulation activities\", \"Monitor for signs of confusion or changes in behavior\", \"Encourage social interaction and meaningful activities\", \"Regular position changes to prevent pressure sores\", \"Document any changes in condition immediately\"]', 'low', '2025-10-09 00:00:00', 10, '2025-09-30 00:00:00', '2025-10-14 23:09:06', NULL, 62, NULL, 'Patient may have cognitive changes and requires patience and reassurance. Fall risk precautions must be maintained at all times. Encourage independence while ensuring safety.', NULL),
(288, 8, 68, 68, 2, 'Rehabilitation Care Plan - Philip Gbeko', 'Goal-oriented care focused on recovering function, improving strength, and regaining independence after illness or injury. Progressive approach with therapeutic exercises, mobility training, and continuous progress monitoring.', 'rehabilitation_care', 'active', '2025-08-26', '2025-09-27', 'every_4_hours', NULL, '[\"Assist with prescribed rehabilitation exercises\", \"Support physical therapy activities and goals\", \"Help with occupational therapy tasks\", \"Monitor progress toward specific recovery goals\", \"Encourage increasing independence in daily activities\", \"Prevent complications during recovery period\", \"Provide motivation and positive reinforcement\", \"Assist with adaptive equipment and techniques\", \"Document functional improvements and setbacks\", \"Coordinate with therapy team on progress\"]', 'low', '2025-08-25 00:00:00', 95, '2025-08-12 00:00:00', '2025-10-14 23:09:06', NULL, 65, NULL, 'Patient is motivated but may experience frustration during recovery. Provide encouragement and positive reinforcement. Follow therapy protocols precisely and document progress.', NULL),
(289, 31, 69, 69, 1, 'Palliative Care Plan - Shaniya Goldner', 'Comfort-focused care emphasizing pain and symptom management, quality of life, and dignity for patients with serious illness. Holistic approach including physical, emotional, and spiritual support for patient and family.', 'palliative_care', 'active', '2025-09-15', NULL, 'weekly', NULL, '[\"Provide comprehensive pain assessment and management\", \"Manage symptoms for maximum comfort (nausea, breathlessness, etc.)\", \"Assist with all personal care needs gently and respectfully\", \"Provide emotional and spiritual support\", \"Support family members and answer questions\", \"Coordinate with hospice or palliative care team\", \"Maintain patient dignity and respect wishes\", \"Create peaceful, comfortable environment\", \"Offer comfort measures (positioning, massage, music)\", \"Document comfort levels and symptom management\"]', 'low', '2025-09-12 00:00:00', 59, '2025-08-30 00:00:00', '2025-10-14 23:09:06', NULL, 61, NULL, 'Focus on comfort, dignity, and quality of life. Family involvement is important. Follow pain management protocol carefully. Respect patient and family wishes at all times.', NULL),
(290, 43, 73, 73, 2, 'Chronic Disease Management Plan - Katlyn Schneider', 'Comprehensive management of chronic conditions with focus on medication compliance, symptom monitoring, lifestyle modifications, and preventing complications. Coordinated care approach with healthcare team.', 'chronic_disease_management', 'active', '2025-08-30', '2026-03-28', 'every_6_hours', NULL, '[\"Administer complex medication regimens accurately\", \"Monitor disease-specific symptoms and vital signs\", \"Record detailed health metrics and trends\", \"Assist with specialized medical equipment\", \"Support prescribed dietary modifications\", \"Coordinate with healthcare providers and specialists\", \"Provide patient education on disease self-management\", \"Monitor for medication side effects or complications\", \"Encourage adherence to treatment plans\", \"Document all health changes and communicate promptly\"]', 'medium', '2025-08-29 00:00:00', 91, '2025-08-16 00:00:00', '2025-10-14 23:09:06', NULL, 59, NULL, 'Strict medication adherence is critical for disease management. Monitor symptoms closely and report any changes immediately to the healthcare team. Patient education ongoing.', NULL),
(291, 26, 73, 73, 2, 'Palliative Care Plan - Felix Cassin', 'Comfort-focused care emphasizing pain and symptom management, quality of life, and dignity for patients with serious illness. Holistic approach including physical, emotional, and spiritual support for patient and family.', 'palliative_care', 'active', '2025-10-06', '2026-01-09', 'weekly', NULL, '[\"Provide comprehensive pain assessment and management\", \"Manage symptoms for maximum comfort (nausea, breathlessness, etc.)\", \"Assist with all personal care needs gently and respectfully\", \"Provide emotional and spiritual support\", \"Support family members and answer questions\", \"Coordinate with hospice or palliative care team\", \"Maintain patient dignity and respect wishes\", \"Create peaceful, comfortable environment\", \"Offer comfort measures (positioning, massage, music)\", \"Document comfort levels and symptom management\"]', 'medium', '2025-10-03 00:00:00', 17, '2025-09-21 00:00:00', '2025-10-14 23:09:06', NULL, 64, NULL, 'Focus on comfort, dignity, and quality of life. Family involvement is important. Follow pain management protocol carefully. Respect patient and family wishes at all times.', NULL),
(292, 49, 75, 75, 2, 'Pediatric Home Care Plan - Ted Howell', 'Specialized care for children including developmental support, medication management, family education, and age-appropriate activities. Focus on child safety, comfort, and supporting normal development.', 'pediatric_care', 'active', '2025-10-02', '2025-11-04', 'three_times_daily', NULL, '[\"Administer age-appropriate medications safely\", \"Monitor growth, development, and vital signs\", \"Assist with feeding and ensure proper nutrition\", \"Provide age-appropriate play and learning activities\", \"Monitor for signs of illness, pain, or discomfort\", \"Educate parents on care routines and procedures\", \"Maintain clean, safe, and child-friendly environment\", \"Support developmental milestones and activities\", \"Provide comfort measures and emotional support\", \"Document feeding, elimination, and behavior patterns\"]', 'high', '2025-10-01 00:00:00', 25, '2025-09-14 00:00:00', '2025-10-14 23:09:06', NULL, 79, 53, 'Child requires age-appropriate communication and activities. Keep parents informed of all care activities, changes, and concerns. Maintain safe, child-friendly environment.', NULL),
(293, 42, 70, 70, 81, 'Chronic Disease Management Plan - Desiree Mraz', 'Comprehensive management of chronic conditions with focus on medication compliance, symptom monitoring, lifestyle modifications, and preventing complications. Coordinated care approach with healthcare team.', 'chronic_disease_management', 'active', '2025-09-05', '2026-07-21', 'twice_daily', NULL, '[\"Administer complex medication regimens accurately\", \"Monitor disease-specific symptoms and vital signs\", \"Record detailed health metrics and trends\", \"Assist with specialized medical equipment\", \"Support prescribed dietary modifications\", \"Coordinate with healthcare providers and specialists\", \"Provide patient education on disease self-management\", \"Monitor for medication side effects or complications\", \"Encourage adherence to treatment plans\", \"Document all health changes and communicate promptly\"]', 'low', '2025-09-04 00:00:00', 79, '2025-08-17 00:00:00', '2025-10-14 23:09:06', NULL, 53, NULL, 'Strict medication adherence is critical for disease management. Monitor symptoms closely and report any changes immediately to the healthcare team. Patient education ongoing.', NULL),
(294, 31, 73, 81, 2, 'Chronic Disease Management Plan - Shaniya Goldner', 'Comprehensive management of chronic conditions with focus on medication compliance, symptom monitoring, lifestyle modifications, and preventing complications. Coordinated care approach with healthcare team.', 'chronic_disease_management', 'active', '2025-09-27', NULL, 'every_8_hours', NULL, '[\"Administer complex medication regimens accurately\", \"Monitor disease-specific symptoms and vital signs\", \"Record detailed health metrics and trends\", \"Assist with specialized medical equipment\", \"Support prescribed dietary modifications\", \"Coordinate with healthcare providers and specialists\", \"Provide patient education on disease self-management\", \"Monitor for medication side effects or complications\", \"Encourage adherence to treatment plans\", \"Document all health changes and communicate promptly\"]', 'low', '2025-09-25 00:00:00', 35, '2025-09-10 00:00:00', '2025-10-14 23:09:06', NULL, 59, NULL, 'Strict medication adherence is critical for disease management. Monitor symptoms closely and report any changes immediately to the healthcare team. Patient education ongoing.', NULL),
(295, 34, 71, 2, 81, 'Chronic Disease Management Plan - Tod Huels', 'Comprehensive management of chronic conditions with focus on medication compliance, symptom monitoring, lifestyle modifications, and preventing complications. Coordinated care approach with healthcare team.', 'chronic_disease_management', 'active', '2025-09-15', '2026-06-27', 'twice_weekly', NULL, '[\"Administer complex medication regimens accurately\", \"Monitor disease-specific symptoms and vital signs\", \"Record detailed health metrics and trends\", \"Assist with specialized medical equipment\", \"Support prescribed dietary modifications\", \"Coordinate with healthcare providers and specialists\", \"Provide patient education on disease self-management\", \"Monitor for medication side effects or complications\", \"Encourage adherence to treatment plans\", \"Document all health changes and communicate promptly\"]', 'critical', '2025-09-12 00:00:00', 59, '2025-09-05 00:00:00', '2025-10-14 23:09:06', NULL, 61, NULL, 'Strict medication adherence is critical for disease management. Monitor symptoms closely and report any changes immediately to the healthcare team. Patient education ongoing.', NULL),
(296, 5, 70, 70, 81, 'Chronic Disease Management Plan - Robert Ben Brown', 'Comprehensive management of chronic conditions with focus on medication compliance, symptom monitoring, lifestyle modifications, and preventing complications. Coordinated care approach with healthcare team.', 'chronic_disease_management', 'active', '2025-09-17', '2026-06-19', 'every_12_hours', NULL, '[\"Administer complex medication regimens accurately\", \"Monitor disease-specific symptoms and vital signs\", \"Record detailed health metrics and trends\", \"Assist with specialized medical equipment\", \"Support prescribed dietary modifications\", \"Coordinate with healthcare providers and specialists\", \"Provide patient education on disease self-management\", \"Monitor for medication side effects or complications\", \"Encourage adherence to treatment plans\", \"Document all health changes and communicate promptly\"]', 'low', '2025-09-16 00:00:00', 55, '2025-09-04 00:00:00', '2025-10-14 23:09:06', NULL, 53, NULL, 'Strict medication adherence is critical for disease management. Monitor symptoms closely and report any changes immediately to the healthcare team. Patient education ongoing.', NULL),
(297, 42, 3, 3, 1, 'Rehabilitation Care Plan - Desiree Mraz', 'Goal-oriented care focused on recovering function, improving strength, and regaining independence after illness or injury. Progressive approach with therapeutic exercises, mobility training, and continuous progress monitoring.', 'rehabilitation_care', 'completed', '2025-04-22', '2025-05-16', 'every_8_hours', NULL, '[\"Assist with prescribed rehabilitation exercises\", \"Support physical therapy activities and goals\", \"Help with occupational therapy tasks\", \"Monitor progress toward specific recovery goals\", \"Encourage increasing independence in daily activities\", \"Prevent complications during recovery period\", \"Provide motivation and positive reinforcement\", \"Assist with adaptive equipment and techniques\", \"Document functional improvements and setbacks\", \"Coordinate with therapy team on progress\"]', 'critical', '2025-04-19 00:00:00', 100, '2025-04-04 00:00:00', '2025-10-14 23:09:06', NULL, 66, NULL, 'Patient is motivated but may experience frustration during recovery. Provide encouragement and positive reinforcement. Follow therapy protocols precisely and document progress.', NULL),
(298, 39, 70, 1, 1, 'Palliative Care Plan - Jarvis Dicki', 'Comfort-focused care emphasizing pain and symptom management, quality of life, and dignity for patients with serious illness. Holistic approach including physical, emotional, and spiritual support for patient and family.', 'palliative_care', 'completed', '2025-04-30', NULL, 'twice_weekly', NULL, '[\"Provide comprehensive pain assessment and management\", \"Manage symptoms for maximum comfort (nausea, breathlessness, etc.)\", \"Assist with all personal care needs gently and respectfully\", \"Provide emotional and spiritual support\", \"Support family members and answer questions\", \"Coordinate with hospice or palliative care team\", \"Maintain patient dignity and respect wishes\", \"Create peaceful, comfortable environment\", \"Offer comfort measures (positioning, massage, music)\", \"Document comfort levels and symptom management\"]', 'high', '2025-04-29 00:00:00', 100, '2025-04-13 00:00:00', '2025-10-14 23:09:06', NULL, 53, 54, 'Focus on comfort, dignity, and quality of life. Family involvement is important. Follow pain management protocol carefully. Respect patient and family wishes at all times.', NULL),
(299, 45, 76, 76, 81, 'Elderly Care Plan - Felipe Schowalter', 'Specialized care for elderly patients focusing on dignity, independence, and quality of life. Includes comprehensive support for age-related needs, fall prevention, cognitive engagement, and social interaction.', 'elderly_care', 'completed', '2025-07-13', NULL, 'custom', 'Monday, Wednesday, and Friday mornings at 8:00 AM', '[\"Assist with bathing and personal care with dignity\", \"Help with dressing and grooming routines\", \"Prepare nutritious meals appropriate for dietary needs\", \"Administer medications and track medication schedule\", \"Provide mobility support and fall prevention measures\", \"Engage in cognitive stimulation activities\", \"Monitor for signs of confusion or changes in behavior\", \"Encourage social interaction and meaningful activities\", \"Regular position changes to prevent pressure sores\", \"Document any changes in condition immediately\"]', 'medium', '2025-07-12 00:00:00', 100, '2025-07-01 00:00:00', '2025-10-14 23:09:06', NULL, 4, 61, 'Patient may have cognitive changes and requires patience and reassurance. Fall risk precautions must be maintained at all times. Encourage independence while ensuring safety.', NULL),
(300, 47, 73, 73, 2, 'Elderly Care Plan - Larry Hill', 'Specialized care for elderly patients focusing on dignity, independence, and quality of life. Includes comprehensive support for age-related needs, fall prevention, cognitive engagement, and social interaction.', 'elderly_care', 'completed', '2025-05-22', '2026-02-02', 'every_8_hours', NULL, '[\"Assist with bathing and personal care with dignity\", \"Help with dressing and grooming routines\", \"Prepare nutritious meals appropriate for dietary needs\", \"Administer medications and track medication schedule\", \"Provide mobility support and fall prevention measures\", \"Engage in cognitive stimulation activities\", \"Monitor for signs of confusion or changes in behavior\", \"Encourage social interaction and meaningful activities\", \"Regular position changes to prevent pressure sores\", \"Document any changes in condition immediately\"]', 'high', '2025-05-20 00:00:00', 100, '2025-05-14 00:00:00', '2025-10-14 23:09:06', NULL, 56, NULL, 'Patient may have cognitive changes and requires patience and reassurance. Fall risk precautions must be maintained at all times. Encourage independence while ensuring safety.', NULL);
INSERT INTO `care_plans` (`id`, `patient_id`, `doctor_id`, `created_by`, `approved_by`, `title`, `description`, `care_type`, `status`, `start_date`, `end_date`, `frequency`, `custom_frequency_details`, `care_tasks`, `priority`, `approved_at`, `completion_percentage`, `created_at`, `updated_at`, `deleted_at`, `primary_nurse_id`, `secondary_nurse_id`, `assignment_notes`, `estimated_hours_per_day`) VALUES
(301, 41, 70, 70, 2, 'Palliative Care Plan - Callie Bergstrom', 'Comfort-focused care emphasizing pain and symptom management, quality of life, and dignity for patients with serious illness. Holistic approach including physical, emotional, and spiritual support for patient and family.', 'palliative_care', 'completed', '2025-05-17', '2025-10-15', 'custom', 'Every 4 hours during waking hours (7 AM - 11 PM)', '[\"Provide comprehensive pain assessment and management\", \"Manage symptoms for maximum comfort (nausea, breathlessness, etc.)\", \"Assist with all personal care needs gently and respectfully\", \"Provide emotional and spiritual support\", \"Support family members and answer questions\", \"Coordinate with hospice or palliative care team\", \"Maintain patient dignity and respect wishes\", \"Create peaceful, comfortable environment\", \"Offer comfort measures (positioning, massage, music)\", \"Document comfort levels and symptom management\"]', 'high', '2025-05-15 00:00:00', 100, '2025-04-30 00:00:00', '2025-10-14 23:09:06', NULL, 65, NULL, 'Focus on comfort, dignity, and quality of life. Family involvement is important. Follow pain management protocol carefully. Respect patient and family wishes at all times.', NULL),
(302, 30, 69, 69, 2, 'General Home Care Plan - Loma Beatty', 'Comprehensive general care including daily living assistance, medication management, and basic health monitoring. This plan focuses on maintaining patient independence while providing necessary support for activities of daily living.', 'general_care', 'completed', '2025-06-19', '2025-09-01', 'twice_daily', NULL, '[\"Assist with personal hygiene and grooming\", \"Help with meal preparation and feeding as needed\", \"Administer prescribed medications on schedule\", \"Monitor and record vital signs daily\", \"Assist with mobility and safe transfers\", \"Provide companionship and emotional support\", \"Maintain clean and safe living environment\", \"Document daily activities and observations\"]', 'medium', '2025-06-18 00:00:00', 100, '2025-06-06 00:00:00', '2025-10-14 23:09:06', NULL, 55, NULL, 'Patient requires gentle, patient-centered care. Please maintain consistent schedule and document any changes in condition or behavior.', NULL),
(303, 24, 76, 2, 81, 'Rehabilitation Care Plan - Aliza Pfannerstill', 'Goal-oriented care focused on recovering function, improving strength, and regaining independence after illness or injury. Progressive approach with therapeutic exercises, mobility training, and continuous progress monitoring.', 'rehabilitation_care', 'completed', '2025-05-22', NULL, 'every_4_hours', NULL, '[\"Assist with prescribed rehabilitation exercises\", \"Support physical therapy activities and goals\", \"Help with occupational therapy tasks\", \"Monitor progress toward specific recovery goals\", \"Encourage increasing independence in daily activities\", \"Prevent complications during recovery period\", \"Provide motivation and positive reinforcement\", \"Assist with adaptive equipment and techniques\", \"Document functional improvements and setbacks\", \"Coordinate with therapy team on progress\"]', 'critical', '2025-05-20 00:00:00', 100, '2025-05-04 00:00:00', '2025-10-14 23:09:06', NULL, 60, NULL, 'Patient is motivated but may experience frustration during recovery. Provide encouragement and positive reinforcement. Follow therapy protocols precisely and document progress.', NULL),
(304, 34, 74, 74, 1, 'Elderly Care Plan - Tod Huels', 'Specialized care for elderly patients focusing on dignity, independence, and quality of life. Includes comprehensive support for age-related needs, fall prevention, cognitive engagement, and social interaction.', 'elderly_care', 'completed', '2025-06-04', NULL, 'every_6_hours', NULL, '[\"Assist with bathing and personal care with dignity\", \"Help with dressing and grooming routines\", \"Prepare nutritious meals appropriate for dietary needs\", \"Administer medications and track medication schedule\", \"Provide mobility support and fall prevention measures\", \"Engage in cognitive stimulation activities\", \"Monitor for signs of confusion or changes in behavior\", \"Encourage social interaction and meaningful activities\", \"Regular position changes to prevent pressure sores\", \"Document any changes in condition immediately\"]', 'low', '2025-06-03 00:00:00', 100, '2025-05-16 00:00:00', '2025-10-14 23:09:06', NULL, 64, NULL, 'Patient may have cognitive changes and requires patience and reassurance. Fall risk precautions must be maintained at all times. Encourage independence while ensuring safety.', NULL),
(305, 8, 68, 81, 81, 'General Home Care Plan - Philip Gbeko', 'Comprehensive general care including daily living assistance, medication management, and basic health monitoring. This plan focuses on maintaining patient independence while providing necessary support for activities of daily living.', 'general_care', 'completed', '2025-06-30', '2025-09-12', 'twice_daily', NULL, '[\"Assist with personal hygiene and grooming\", \"Help with meal preparation and feeding as needed\", \"Administer prescribed medications on schedule\", \"Monitor and record vital signs daily\", \"Assist with mobility and safe transfers\", \"Provide companionship and emotional support\", \"Maintain clean and safe living environment\", \"Document daily activities and observations\"]', 'high', '2025-06-28 00:00:00', 100, '2025-06-21 00:00:00', '2025-10-14 23:09:06', NULL, 64, 61, 'Patient requires gentle, patient-centered care. Please maintain consistent schedule and document any changes in condition or behavior.', NULL),
(306, 34, 80, 80, 81, 'General Home Care Plan - Tod Huels', 'Comprehensive general care including daily living assistance, medication management, and basic health monitoring. This plan focuses on maintaining patient independence while providing necessary support for activities of daily living.', 'general_care', 'completed', '2025-04-26', '2025-07-09', 'as_needed', NULL, '[\"Assist with personal hygiene and grooming\", \"Help with meal preparation and feeding as needed\", \"Administer prescribed medications on schedule\", \"Monitor and record vital signs daily\", \"Assist with mobility and safe transfers\", \"Provide companionship and emotional support\", \"Maintain clean and safe living environment\", \"Document daily activities and observations\"]', 'low', '2025-04-24 00:00:00', 100, '2025-04-13 00:00:00', '2025-10-14 23:09:06', NULL, 54, NULL, 'Patient requires gentle, patient-centered care. Please maintain consistent schedule and document any changes in condition or behavior.', NULL),
(307, 37, 70, 2, NULL, 'Test', 'Test', 'general_care', 'pending_approval', '2025-10-17', '2025-10-23', 'twice_daily', NULL, '[\"Test game\"]', 'low', NULL, 0, '2025-10-14 23:10:49', '2025-10-14 23:12:56', '2025-10-14 23:12:56', NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `drivers`
--

CREATE TABLE `drivers` (
  `id` bigint UNSIGNED NOT NULL,
  `driver_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `first_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_of_birth` date NOT NULL,
  `avatar` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `average_rating` decimal(3,2) DEFAULT NULL,
  `total_trips` int NOT NULL DEFAULT '0',
  `completed_trips` int NOT NULL DEFAULT '0',
  `cancelled_trips` int NOT NULL DEFAULT '0',
  `is_suspended` tinyint(1) NOT NULL DEFAULT '0',
  `suspended_at` datetime DEFAULT NULL,
  `suspension_reason` text COLLATE utf8mb4_unicode_ci,
  `suspended_by` bigint UNSIGNED DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
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
(6, 'DRV60716', 'Felix', 'Amonu', '0244371117', NULL, '1974-01-01', NULL, 1, NULL, 0, 0, 0, 0, NULL, NULL, NULL, NULL, '2025-10-10 19:02:57', '2025-10-10 19:02:57', NULL);

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
  `assignment_notes` text COLLATE utf8mb4_unicode_ci,
  `unassignment_reason` text COLLATE utf8mb4_unicode_ci,
  `status` enum('active','inactive','temporary') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `effective_from` datetime DEFAULT NULL,
  `effective_until` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `driver_vehicle_assignments`
--

INSERT INTO `driver_vehicle_assignments` (`id`, `driver_id`, `vehicle_id`, `assigned_at`, `unassigned_at`, `is_active`, `assigned_by`, `unassigned_by`, `assignment_notes`, `unassignment_reason`, `status`, `effective_from`, `effective_until`, `created_at`, `updated_at`) VALUES
(1, 1, 2, '2025-10-01 12:24:04', NULL, 1, 2, NULL, NULL, NULL, 'active', NULL, NULL, '2025-10-01 12:24:04', '2025-10-01 12:24:04'),
(2, 2, 1, '2025-10-01 12:24:09', NULL, 1, 2, NULL, NULL, NULL, 'active', NULL, NULL, '2025-10-01 12:24:09', '2025-10-01 12:24:09'),
(3, 3, 3, '2025-10-10 19:03:19', NULL, 1, 2, NULL, NULL, NULL, 'active', NULL, NULL, '2025-10-10 19:03:19', '2025-10-10 19:03:19'),
(4, 4, 4, '2025-10-10 19:03:24', NULL, 1, 2, NULL, NULL, NULL, 'active', NULL, NULL, '2025-10-10 19:03:24', '2025-10-10 19:03:24'),
(5, 6, 5, '2025-10-10 19:03:30', NULL, 1, 2, NULL, NULL, NULL, 'active', NULL, NULL, '2025-10-10 19:03:30', '2025-10-10 19:03:30'),
(6, 5, 6, '2025-10-10 19:03:35', NULL, 1, 2, NULL, NULL, NULL, 'active', NULL, NULL, '2025-10-10 19:03:35', '2025-10-10 19:03:35');

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint UNSIGNED NOT NULL,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `failed_jobs`
--

INSERT INTO `failed_jobs` (`id`, `uuid`, `connection`, `queue`, `payload`, `exception`, `failed_at`) VALUES
(1, '9f188231-1871-4079-bf8d-0300e9466a04', 'database', 'default', '{\"uuid\":\"9f188231-1871-4079-bf8d-0300e9466a04\",\"displayName\":\"App\\\\Notifications\\\\ScheduleReminder\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Notifications\\\\SendQueuedNotifications\",\"command\":\"O:48:\\\"Illuminate\\\\Notifications\\\\SendQueuedNotifications\\\":3:{s:11:\\\"notifiables\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:15:\\\"App\\\\Models\\\\User\\\";s:2:\\\"id\\\";a:1:{i:0;i:54;}s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:12:\\\"notification\\\";O:34:\\\"App\\\\Notifications\\\\ScheduleReminder\\\":2:{s:11:\\\"\\u0000*\\u0000schedule\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:19:\\\"App\\\\Models\\\\Schedule\\\";s:2:\\\"id\\\";i:529;s:9:\\\"relations\\\";a:1:{i:0;s:5:\\\"nurse\\\";}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:2:\\\"id\\\";s:36:\\\"3601cb95-c491-4878-aff0-07e6cbb4c4b5\\\";}s:8:\\\"channels\\\";a:1:{i:0;s:4:\\\"mail\\\";}}\"},\"createdAt\":1760560109,\"delay\":null}', 'Symfony\\Component\\Mailer\\Exception\\UnexpectedResponseException: Expected response code \"250\" but got code \"421\", with message \"421 4.4.2 smtp.hostinger.com Error: timeout exceeded\". in /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/symfony/mailer/Transport/Smtp/SmtpTransport.php:342\nStack trace:\n#0 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/symfony/mailer/Transport/Smtp/SmtpTransport.php(198): Symfony\\Component\\Mailer\\Transport\\Smtp\\SmtpTransport->assertResponseCode(\'421 4.4.2 smtp....\', Array)\n#1 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/symfony/mailer/Transport/Smtp/EsmtpTransport.php(150): Symfony\\Component\\Mailer\\Transport\\Smtp\\SmtpTransport->executeCommand(\'MAIL FROM:<info...\', Array)\n#2 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/symfony/mailer/Transport/Smtp/SmtpTransport.php(263): Symfony\\Component\\Mailer\\Transport\\Smtp\\EsmtpTransport->executeCommand(\'MAIL FROM:<info...\', Array)\n#3 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/symfony/mailer/Transport/Smtp/SmtpTransport.php(215): Symfony\\Component\\Mailer\\Transport\\Smtp\\SmtpTransport->doMailFromCommand(\'info@junitsolut...\', false)\n#4 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/symfony/mailer/Transport/AbstractTransport.php(69): Symfony\\Component\\Mailer\\Transport\\Smtp\\SmtpTransport->doSend(Object(Symfony\\Component\\Mailer\\SentMessage))\n#5 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/symfony/mailer/Transport/Smtp/SmtpTransport.php(138): Symfony\\Component\\Mailer\\Transport\\AbstractTransport->send(Object(Symfony\\Component\\Mime\\Email), Object(Symfony\\Component\\Mailer\\DelayedEnvelope))\n#6 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Mail/Mailer.php(584): Symfony\\Component\\Mailer\\Transport\\Smtp\\SmtpTransport->send(Object(Symfony\\Component\\Mime\\Email), Object(Symfony\\Component\\Mailer\\DelayedEnvelope))\n#7 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Mail/Mailer.php(331): Illuminate\\Mail\\Mailer->sendSymfonyMessage(Object(Symfony\\Component\\Mime\\Email))\n#8 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Notifications/Channels/MailChannel.php(66): Illuminate\\Mail\\Mailer->send(Object(Closure), Array, Object(Closure))\n#9 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Notifications/NotificationSender.php(163): Illuminate\\Notifications\\Channels\\MailChannel->send(Object(App\\Models\\User), Object(App\\Notifications\\ScheduleReminder))\n#10 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Notifications/NotificationSender.php(118): Illuminate\\Notifications\\NotificationSender->sendToNotifiable(Object(App\\Models\\User), \'ba02bc88-2b2d-4...\', Object(App\\Notifications\\ScheduleReminder), \'mail\')\n#11 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Support/Traits/Localizable.php(19): Illuminate\\Notifications\\NotificationSender->Illuminate\\Notifications\\{closure}()\n#12 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Notifications/NotificationSender.php(113): Illuminate\\Notifications\\NotificationSender->withLocale(NULL, Object(Closure))\n#13 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Notifications/ChannelManager.php(54): Illuminate\\Notifications\\NotificationSender->sendNow(Object(Illuminate\\Database\\Eloquent\\Collection), Object(App\\Notifications\\ScheduleReminder), Array)\n#14 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Notifications/SendQueuedNotifications.php(118): Illuminate\\Notifications\\ChannelManager->sendNow(Object(Illuminate\\Database\\Eloquent\\Collection), Object(App\\Notifications\\ScheduleReminder), Array)\n#15 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Container/BoundMethod.php(36): Illuminate\\Notifications\\SendQueuedNotifications->handle(Object(Illuminate\\Notifications\\ChannelManager))\n#16 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Container/Util.php(43): Illuminate\\Container\\BoundMethod::Illuminate\\Container\\{closure}()\n#17 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Container/BoundMethod.php(96): Illuminate\\Container\\Util::unwrapIfClosure(Object(Closure))\n#18 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Container/BoundMethod.php(35): Illuminate\\Container\\BoundMethod::callBoundMethod(Object(Illuminate\\Foundation\\Application), Array, Object(Closure))\n#19 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Container/Container.php(836): Illuminate\\Container\\BoundMethod::call(Object(Illuminate\\Foundation\\Application), Array, Array, NULL)\n#20 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Bus/Dispatcher.php(132): Illuminate\\Container\\Container->call(Array)\n#21 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(180): Illuminate\\Bus\\Dispatcher->Illuminate\\Bus\\{closure}(Object(Illuminate\\Notifications\\SendQueuedNotifications))\n#22 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(137): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}(Object(Illuminate\\Notifications\\SendQueuedNotifications))\n#23 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Bus/Dispatcher.php(136): Illuminate\\Pipeline\\Pipeline->then(Object(Closure))\n#24 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Queue/CallQueuedHandler.php(134): Illuminate\\Bus\\Dispatcher->dispatchNow(Object(Illuminate\\Notifications\\SendQueuedNotifications), false)\n#25 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(180): Illuminate\\Queue\\CallQueuedHandler->Illuminate\\Queue\\{closure}(Object(Illuminate\\Notifications\\SendQueuedNotifications))\n#26 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(137): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}(Object(Illuminate\\Notifications\\SendQueuedNotifications))\n#27 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Queue/CallQueuedHandler.php(127): Illuminate\\Pipeline\\Pipeline->then(Object(Closure))\n#28 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Queue/CallQueuedHandler.php(68): Illuminate\\Queue\\CallQueuedHandler->dispatchThroughMiddleware(Object(Illuminate\\Queue\\Jobs\\DatabaseJob), Object(Illuminate\\Notifications\\SendQueuedNotifications))\n#29 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Queue/Jobs/Job.php(102): Illuminate\\Queue\\CallQueuedHandler->call(Object(Illuminate\\Queue\\Jobs\\DatabaseJob), Array)\n#30 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Queue/Worker.php(451): Illuminate\\Queue\\Jobs\\Job->fire()\n#31 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Queue/Worker.php(401): Illuminate\\Queue\\Worker->process(\'database\', Object(Illuminate\\Queue\\Jobs\\DatabaseJob), Object(Illuminate\\Queue\\WorkerOptions))\n#32 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Queue/Worker.php(187): Illuminate\\Queue\\Worker->runJob(Object(Illuminate\\Queue\\Jobs\\DatabaseJob), \'database\', Object(Illuminate\\Queue\\WorkerOptions))\n#33 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Queue/Console/WorkCommand.php(148): Illuminate\\Queue\\Worker->daemon(\'database\', \'default\', Object(Illuminate\\Queue\\WorkerOptions))\n#34 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Queue/Console/WorkCommand.php(131): Illuminate\\Queue\\Console\\WorkCommand->runWorker(\'database\', \'default\')\n#35 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Container/BoundMethod.php(36): Illuminate\\Queue\\Console\\WorkCommand->handle()\n#36 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Container/Util.php(43): Illuminate\\Container\\BoundMethod::Illuminate\\Container\\{closure}()\n#37 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Container/BoundMethod.php(96): Illuminate\\Container\\Util::unwrapIfClosure(Object(Closure))\n#38 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Container/BoundMethod.php(35): Illuminate\\Container\\BoundMethod::callBoundMethod(Object(Illuminate\\Foundation\\Application), Array, Object(Closure))\n#39 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Container/Container.php(836): Illuminate\\Container\\BoundMethod::call(Object(Illuminate\\Foundation\\Application), Array, Array, NULL)\n#40 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Console/Command.php(211): Illuminate\\Container\\Container->call(Array)\n#41 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/symfony/console/Command/Command.php(318): Illuminate\\Console\\Command->execute(Object(Symfony\\Component\\Console\\Input\\ArgvInput), Object(Illuminate\\Console\\OutputStyle))\n#42 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Console/Command.php(180): Symfony\\Component\\Console\\Command\\Command->run(Object(Symfony\\Component\\Console\\Input\\ArgvInput), Object(Illuminate\\Console\\OutputStyle))\n#43 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/symfony/console/Application.php(1110): Illuminate\\Console\\Command->run(Object(Symfony\\Component\\Console\\Input\\ArgvInput), Object(Symfony\\Component\\Console\\Output\\ConsoleOutput))\n#44 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/symfony/console/Application.php(359): Symfony\\Component\\Console\\Application->doRunCommand(Object(Illuminate\\Queue\\Console\\WorkCommand), Object(Symfony\\Component\\Console\\Input\\ArgvInput), Object(Symfony\\Component\\Console\\Output\\ConsoleOutput))\n#45 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/symfony/console/Application.php(194): Symfony\\Component\\Console\\Application->doRun(Object(Symfony\\Component\\Console\\Input\\ArgvInput), Object(Symfony\\Component\\Console\\Output\\ConsoleOutput))\n#46 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Foundation/Console/Kernel.php(197): Symfony\\Component\\Console\\Application->run(Object(Symfony\\Component\\Console\\Input\\ArgvInput), Object(Symfony\\Component\\Console\\Output\\ConsoleOutput))\n#47 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Foundation/Application.php(1235): Illuminate\\Foundation\\Console\\Kernel->handle(Object(Symfony\\Component\\Console\\Input\\ArgvInput), Object(Symfony\\Component\\Console\\Output\\ConsoleOutput))\n#48 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/artisan(16): Illuminate\\Foundation\\Application->handleCommand(Object(Symfony\\Component\\Console\\Input\\ArgvInput))\n#49 {main}', '2025-10-15 20:28:31'),
(2, '2fdb1049-d44b-4492-840f-bb1759d62bc1', 'database', 'default', '{\"uuid\":\"2fdb1049-d44b-4492-840f-bb1759d62bc1\",\"displayName\":\"App\\\\Notifications\\\\ScheduleReminder\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Notifications\\\\SendQueuedNotifications\",\"command\":\"O:48:\\\"Illuminate\\\\Notifications\\\\SendQueuedNotifications\\\":3:{s:11:\\\"notifiables\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:15:\\\"App\\\\Models\\\\User\\\";s:2:\\\"id\\\";a:1:{i:0;i:4;}s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:12:\\\"notification\\\";O:34:\\\"App\\\\Notifications\\\\ScheduleReminder\\\":2:{s:11:\\\"\\u0000*\\u0000schedule\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:19:\\\"App\\\\Models\\\\Schedule\\\";s:2:\\\"id\\\";i:485;s:9:\\\"relations\\\";a:1:{i:0;s:5:\\\"nurse\\\";}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:2:\\\"id\\\";s:36:\\\"ff3e29bb-d416-4296-a6e3-d81bc2da0ec9\\\";}s:8:\\\"channels\\\";a:1:{i:0;s:4:\\\"mail\\\";}}\"},\"createdAt\":1760560166,\"delay\":null}', 'Symfony\\Component\\Mailer\\Exception\\UnexpectedResponseException: Expected response code \"250\" but got code \"421\", with message \"421 4.4.2 smtp.hostinger.com Error: timeout exceeded\". in /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/symfony/mailer/Transport/Smtp/SmtpTransport.php:342\nStack trace:\n#0 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/symfony/mailer/Transport/Smtp/SmtpTransport.php(198): Symfony\\Component\\Mailer\\Transport\\Smtp\\SmtpTransport->assertResponseCode(\'421 4.4.2 smtp....\', Array)\n#1 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/symfony/mailer/Transport/Smtp/EsmtpTransport.php(150): Symfony\\Component\\Mailer\\Transport\\Smtp\\SmtpTransport->executeCommand(\'MAIL FROM:<info...\', Array)\n#2 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/symfony/mailer/Transport/Smtp/SmtpTransport.php(263): Symfony\\Component\\Mailer\\Transport\\Smtp\\EsmtpTransport->executeCommand(\'MAIL FROM:<info...\', Array)\n#3 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/symfony/mailer/Transport/Smtp/SmtpTransport.php(215): Symfony\\Component\\Mailer\\Transport\\Smtp\\SmtpTransport->doMailFromCommand(\'info@junitsolut...\', false)\n#4 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/symfony/mailer/Transport/AbstractTransport.php(69): Symfony\\Component\\Mailer\\Transport\\Smtp\\SmtpTransport->doSend(Object(Symfony\\Component\\Mailer\\SentMessage))\n#5 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/symfony/mailer/Transport/Smtp/SmtpTransport.php(138): Symfony\\Component\\Mailer\\Transport\\AbstractTransport->send(Object(Symfony\\Component\\Mime\\Email), Object(Symfony\\Component\\Mailer\\DelayedEnvelope))\n#6 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Mail/Mailer.php(584): Symfony\\Component\\Mailer\\Transport\\Smtp\\SmtpTransport->send(Object(Symfony\\Component\\Mime\\Email), Object(Symfony\\Component\\Mailer\\DelayedEnvelope))\n#7 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Mail/Mailer.php(331): Illuminate\\Mail\\Mailer->sendSymfonyMessage(Object(Symfony\\Component\\Mime\\Email))\n#8 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Notifications/Channels/MailChannel.php(66): Illuminate\\Mail\\Mailer->send(Object(Closure), Array, Object(Closure))\n#9 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Notifications/NotificationSender.php(163): Illuminate\\Notifications\\Channels\\MailChannel->send(Object(App\\Models\\User), Object(App\\Notifications\\ScheduleReminder))\n#10 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Notifications/NotificationSender.php(118): Illuminate\\Notifications\\NotificationSender->sendToNotifiable(Object(App\\Models\\User), \'39a22f91-6ba2-4...\', Object(App\\Notifications\\ScheduleReminder), \'mail\')\n#11 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Support/Traits/Localizable.php(19): Illuminate\\Notifications\\NotificationSender->Illuminate\\Notifications\\{closure}()\n#12 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Notifications/NotificationSender.php(113): Illuminate\\Notifications\\NotificationSender->withLocale(NULL, Object(Closure))\n#13 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Notifications/ChannelManager.php(54): Illuminate\\Notifications\\NotificationSender->sendNow(Object(Illuminate\\Database\\Eloquent\\Collection), Object(App\\Notifications\\ScheduleReminder), Array)\n#14 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Notifications/SendQueuedNotifications.php(118): Illuminate\\Notifications\\ChannelManager->sendNow(Object(Illuminate\\Database\\Eloquent\\Collection), Object(App\\Notifications\\ScheduleReminder), Array)\n#15 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Container/BoundMethod.php(36): Illuminate\\Notifications\\SendQueuedNotifications->handle(Object(Illuminate\\Notifications\\ChannelManager))\n#16 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Container/Util.php(43): Illuminate\\Container\\BoundMethod::Illuminate\\Container\\{closure}()\n#17 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Container/BoundMethod.php(96): Illuminate\\Container\\Util::unwrapIfClosure(Object(Closure))\n#18 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Container/BoundMethod.php(35): Illuminate\\Container\\BoundMethod::callBoundMethod(Object(Illuminate\\Foundation\\Application), Array, Object(Closure))\n#19 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Container/Container.php(836): Illuminate\\Container\\BoundMethod::call(Object(Illuminate\\Foundation\\Application), Array, Array, NULL)\n#20 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Bus/Dispatcher.php(132): Illuminate\\Container\\Container->call(Array)\n#21 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(180): Illuminate\\Bus\\Dispatcher->Illuminate\\Bus\\{closure}(Object(Illuminate\\Notifications\\SendQueuedNotifications))\n#22 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(137): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}(Object(Illuminate\\Notifications\\SendQueuedNotifications))\n#23 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Bus/Dispatcher.php(136): Illuminate\\Pipeline\\Pipeline->then(Object(Closure))\n#24 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Queue/CallQueuedHandler.php(134): Illuminate\\Bus\\Dispatcher->dispatchNow(Object(Illuminate\\Notifications\\SendQueuedNotifications), false)\n#25 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(180): Illuminate\\Queue\\CallQueuedHandler->Illuminate\\Queue\\{closure}(Object(Illuminate\\Notifications\\SendQueuedNotifications))\n#26 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(137): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}(Object(Illuminate\\Notifications\\SendQueuedNotifications))\n#27 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Queue/CallQueuedHandler.php(127): Illuminate\\Pipeline\\Pipeline->then(Object(Closure))\n#28 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Queue/CallQueuedHandler.php(68): Illuminate\\Queue\\CallQueuedHandler->dispatchThroughMiddleware(Object(Illuminate\\Queue\\Jobs\\DatabaseJob), Object(Illuminate\\Notifications\\SendQueuedNotifications))\n#29 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Queue/Jobs/Job.php(102): Illuminate\\Queue\\CallQueuedHandler->call(Object(Illuminate\\Queue\\Jobs\\DatabaseJob), Array)\n#30 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Queue/Worker.php(451): Illuminate\\Queue\\Jobs\\Job->fire()\n#31 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Queue/Worker.php(401): Illuminate\\Queue\\Worker->process(\'database\', Object(Illuminate\\Queue\\Jobs\\DatabaseJob), Object(Illuminate\\Queue\\WorkerOptions))\n#32 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Queue/Worker.php(187): Illuminate\\Queue\\Worker->runJob(Object(Illuminate\\Queue\\Jobs\\DatabaseJob), \'database\', Object(Illuminate\\Queue\\WorkerOptions))\n#33 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Queue/Console/WorkCommand.php(148): Illuminate\\Queue\\Worker->daemon(\'database\', \'default\', Object(Illuminate\\Queue\\WorkerOptions))\n#34 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Queue/Console/WorkCommand.php(131): Illuminate\\Queue\\Console\\WorkCommand->runWorker(\'database\', \'default\')\n#35 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Container/BoundMethod.php(36): Illuminate\\Queue\\Console\\WorkCommand->handle()\n#36 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Container/Util.php(43): Illuminate\\Container\\BoundMethod::Illuminate\\Container\\{closure}()\n#37 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Container/BoundMethod.php(96): Illuminate\\Container\\Util::unwrapIfClosure(Object(Closure))\n#38 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Container/BoundMethod.php(35): Illuminate\\Container\\BoundMethod::callBoundMethod(Object(Illuminate\\Foundation\\Application), Array, Object(Closure))\n#39 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Container/Container.php(836): Illuminate\\Container\\BoundMethod::call(Object(Illuminate\\Foundation\\Application), Array, Array, NULL)\n#40 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Console/Command.php(211): Illuminate\\Container\\Container->call(Array)\n#41 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/symfony/console/Command/Command.php(318): Illuminate\\Console\\Command->execute(Object(Symfony\\Component\\Console\\Input\\ArgvInput), Object(Illuminate\\Console\\OutputStyle))\n#42 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Console/Command.php(180): Symfony\\Component\\Console\\Command\\Command->run(Object(Symfony\\Component\\Console\\Input\\ArgvInput), Object(Illuminate\\Console\\OutputStyle))\n#43 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/symfony/console/Application.php(1110): Illuminate\\Console\\Command->run(Object(Symfony\\Component\\Console\\Input\\ArgvInput), Object(Symfony\\Component\\Console\\Output\\ConsoleOutput))\n#44 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/symfony/console/Application.php(359): Symfony\\Component\\Console\\Application->doRunCommand(Object(Illuminate\\Queue\\Console\\WorkCommand), Object(Symfony\\Component\\Console\\Input\\ArgvInput), Object(Symfony\\Component\\Console\\Output\\ConsoleOutput))\n#45 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/symfony/console/Application.php(194): Symfony\\Component\\Console\\Application->doRun(Object(Symfony\\Component\\Console\\Input\\ArgvInput), Object(Symfony\\Component\\Console\\Output\\ConsoleOutput))\n#46 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Foundation/Console/Kernel.php(197): Symfony\\Component\\Console\\Application->run(Object(Symfony\\Component\\Console\\Input\\ArgvInput), Object(Symfony\\Component\\Console\\Output\\ConsoleOutput))\n#47 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Foundation/Application.php(1235): Illuminate\\Foundation\\Console\\Kernel->handle(Object(Symfony\\Component\\Console\\Input\\ArgvInput), Object(Symfony\\Component\\Console\\Output\\ConsoleOutput))\n#48 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/artisan(16): Illuminate\\Foundation\\Application->handleCommand(Object(Symfony\\Component\\Console\\Input\\ArgvInput))\n#49 {main}', '2025-10-15 20:29:28'),
(3, '3d971d84-ad5a-4869-802c-2076ed3d229e', 'database', 'default', '{\"uuid\":\"3d971d84-ad5a-4869-802c-2076ed3d229e\",\"displayName\":\"App\\\\Notifications\\\\ScheduleReminder\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Notifications\\\\SendQueuedNotifications\",\"command\":\"O:48:\\\"Illuminate\\\\Notifications\\\\SendQueuedNotifications\\\":3:{s:11:\\\"notifiables\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:15:\\\"App\\\\Models\\\\User\\\";s:2:\\\"id\\\";a:1:{i:0;i:4;}s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:12:\\\"notification\\\";O:34:\\\"App\\\\Notifications\\\\ScheduleReminder\\\":2:{s:11:\\\"\\u0000*\\u0000schedule\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:19:\\\"App\\\\Models\\\\Schedule\\\";s:2:\\\"id\\\";i:435;s:9:\\\"relations\\\";a:1:{i:0;s:5:\\\"nurse\\\";}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:2:\\\"id\\\";s:36:\\\"9d0e72d1-6860-40de-8edb-565ba643e07f\\\";}s:8:\\\"channels\\\";a:1:{i:0;s:4:\\\"mail\\\";}}\"},\"createdAt\":1760561716,\"delay\":null}', 'Symfony\\Component\\Mailer\\Exception\\UnexpectedResponseException: Expected response code \"250\" but got code \"421\", with message \"421 4.4.2 smtp.hostinger.com Error: timeout exceeded\". in /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/symfony/mailer/Transport/Smtp/SmtpTransport.php:342\nStack trace:\n#0 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/symfony/mailer/Transport/Smtp/SmtpTransport.php(198): Symfony\\Component\\Mailer\\Transport\\Smtp\\SmtpTransport->assertResponseCode(\'421 4.4.2 smtp....\', Array)\n#1 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/symfony/mailer/Transport/Smtp/EsmtpTransport.php(150): Symfony\\Component\\Mailer\\Transport\\Smtp\\SmtpTransport->executeCommand(\'MAIL FROM:<info...\', Array)\n#2 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/symfony/mailer/Transport/Smtp/SmtpTransport.php(263): Symfony\\Component\\Mailer\\Transport\\Smtp\\EsmtpTransport->executeCommand(\'MAIL FROM:<info...\', Array)\n#3 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/symfony/mailer/Transport/Smtp/SmtpTransport.php(215): Symfony\\Component\\Mailer\\Transport\\Smtp\\SmtpTransport->doMailFromCommand(\'info@junitsolut...\', false)\n#4 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/symfony/mailer/Transport/AbstractTransport.php(69): Symfony\\Component\\Mailer\\Transport\\Smtp\\SmtpTransport->doSend(Object(Symfony\\Component\\Mailer\\SentMessage))\n#5 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/symfony/mailer/Transport/Smtp/SmtpTransport.php(138): Symfony\\Component\\Mailer\\Transport\\AbstractTransport->send(Object(Symfony\\Component\\Mime\\Email), Object(Symfony\\Component\\Mailer\\DelayedEnvelope))\n#6 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Mail/Mailer.php(584): Symfony\\Component\\Mailer\\Transport\\Smtp\\SmtpTransport->send(Object(Symfony\\Component\\Mime\\Email), Object(Symfony\\Component\\Mailer\\DelayedEnvelope))\n#7 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Mail/Mailer.php(331): Illuminate\\Mail\\Mailer->sendSymfonyMessage(Object(Symfony\\Component\\Mime\\Email))\n#8 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Notifications/Channels/MailChannel.php(66): Illuminate\\Mail\\Mailer->send(Object(Closure), Array, Object(Closure))\n#9 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Notifications/NotificationSender.php(163): Illuminate\\Notifications\\Channels\\MailChannel->send(Object(App\\Models\\User), Object(App\\Notifications\\ScheduleReminder))\n#10 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Notifications/NotificationSender.php(118): Illuminate\\Notifications\\NotificationSender->sendToNotifiable(Object(App\\Models\\User), \'3e2b1299-3f80-4...\', Object(App\\Notifications\\ScheduleReminder), \'mail\')\n#11 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Support/Traits/Localizable.php(19): Illuminate\\Notifications\\NotificationSender->Illuminate\\Notifications\\{closure}()\n#12 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Notifications/NotificationSender.php(113): Illuminate\\Notifications\\NotificationSender->withLocale(NULL, Object(Closure))\n#13 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Notifications/ChannelManager.php(54): Illuminate\\Notifications\\NotificationSender->sendNow(Object(Illuminate\\Database\\Eloquent\\Collection), Object(App\\Notifications\\ScheduleReminder), Array)\n#14 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Notifications/SendQueuedNotifications.php(118): Illuminate\\Notifications\\ChannelManager->sendNow(Object(Illuminate\\Database\\Eloquent\\Collection), Object(App\\Notifications\\ScheduleReminder), Array)\n#15 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Container/BoundMethod.php(36): Illuminate\\Notifications\\SendQueuedNotifications->handle(Object(Illuminate\\Notifications\\ChannelManager))\n#16 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Container/Util.php(43): Illuminate\\Container\\BoundMethod::Illuminate\\Container\\{closure}()\n#17 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Container/BoundMethod.php(96): Illuminate\\Container\\Util::unwrapIfClosure(Object(Closure))\n#18 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Container/BoundMethod.php(35): Illuminate\\Container\\BoundMethod::callBoundMethod(Object(Illuminate\\Foundation\\Application), Array, Object(Closure))\n#19 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Container/Container.php(836): Illuminate\\Container\\BoundMethod::call(Object(Illuminate\\Foundation\\Application), Array, Array, NULL)\n#20 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Bus/Dispatcher.php(132): Illuminate\\Container\\Container->call(Array)\n#21 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(180): Illuminate\\Bus\\Dispatcher->Illuminate\\Bus\\{closure}(Object(Illuminate\\Notifications\\SendQueuedNotifications))\n#22 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(137): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}(Object(Illuminate\\Notifications\\SendQueuedNotifications))\n#23 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Bus/Dispatcher.php(136): Illuminate\\Pipeline\\Pipeline->then(Object(Closure))\n#24 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Queue/CallQueuedHandler.php(134): Illuminate\\Bus\\Dispatcher->dispatchNow(Object(Illuminate\\Notifications\\SendQueuedNotifications), false)\n#25 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(180): Illuminate\\Queue\\CallQueuedHandler->Illuminate\\Queue\\{closure}(Object(Illuminate\\Notifications\\SendQueuedNotifications))\n#26 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(137): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}(Object(Illuminate\\Notifications\\SendQueuedNotifications))\n#27 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Queue/CallQueuedHandler.php(127): Illuminate\\Pipeline\\Pipeline->then(Object(Closure))\n#28 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Queue/CallQueuedHandler.php(68): Illuminate\\Queue\\CallQueuedHandler->dispatchThroughMiddleware(Object(Illuminate\\Queue\\Jobs\\DatabaseJob), Object(Illuminate\\Notifications\\SendQueuedNotifications))\n#29 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Queue/Jobs/Job.php(102): Illuminate\\Queue\\CallQueuedHandler->call(Object(Illuminate\\Queue\\Jobs\\DatabaseJob), Array)\n#30 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Queue/Worker.php(451): Illuminate\\Queue\\Jobs\\Job->fire()\n#31 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Queue/Worker.php(401): Illuminate\\Queue\\Worker->process(\'database\', Object(Illuminate\\Queue\\Jobs\\DatabaseJob), Object(Illuminate\\Queue\\WorkerOptions))\n#32 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Queue/Worker.php(187): Illuminate\\Queue\\Worker->runJob(Object(Illuminate\\Queue\\Jobs\\DatabaseJob), \'database\', Object(Illuminate\\Queue\\WorkerOptions))\n#33 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Queue/Console/WorkCommand.php(148): Illuminate\\Queue\\Worker->daemon(\'database\', \'default\', Object(Illuminate\\Queue\\WorkerOptions))\n#34 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Queue/Console/WorkCommand.php(131): Illuminate\\Queue\\Console\\WorkCommand->runWorker(\'database\', \'default\')\n#35 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Container/BoundMethod.php(36): Illuminate\\Queue\\Console\\WorkCommand->handle()\n#36 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Container/Util.php(43): Illuminate\\Container\\BoundMethod::Illuminate\\Container\\{closure}()\n#37 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Container/BoundMethod.php(96): Illuminate\\Container\\Util::unwrapIfClosure(Object(Closure))\n#38 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Container/BoundMethod.php(35): Illuminate\\Container\\BoundMethod::callBoundMethod(Object(Illuminate\\Foundation\\Application), Array, Object(Closure))\n#39 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Container/Container.php(836): Illuminate\\Container\\BoundMethod::call(Object(Illuminate\\Foundation\\Application), Array, Array, NULL)\n#40 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Console/Command.php(211): Illuminate\\Container\\Container->call(Array)\n#41 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/symfony/console/Command/Command.php(318): Illuminate\\Console\\Command->execute(Object(Symfony\\Component\\Console\\Input\\ArgvInput), Object(Illuminate\\Console\\OutputStyle))\n#42 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Console/Command.php(180): Symfony\\Component\\Console\\Command\\Command->run(Object(Symfony\\Component\\Console\\Input\\ArgvInput), Object(Illuminate\\Console\\OutputStyle))\n#43 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/symfony/console/Application.php(1110): Illuminate\\Console\\Command->run(Object(Symfony\\Component\\Console\\Input\\ArgvInput), Object(Symfony\\Component\\Console\\Output\\ConsoleOutput))\n#44 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/symfony/console/Application.php(359): Symfony\\Component\\Console\\Application->doRunCommand(Object(Illuminate\\Queue\\Console\\WorkCommand), Object(Symfony\\Component\\Console\\Input\\ArgvInput), Object(Symfony\\Component\\Console\\Output\\ConsoleOutput))\n#45 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/symfony/console/Application.php(194): Symfony\\Component\\Console\\Application->doRun(Object(Symfony\\Component\\Console\\Input\\ArgvInput), Object(Symfony\\Component\\Console\\Output\\ConsoleOutput))\n#46 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Foundation/Console/Kernel.php(197): Symfony\\Component\\Console\\Application->run(Object(Symfony\\Component\\Console\\Input\\ArgvInput), Object(Symfony\\Component\\Console\\Output\\ConsoleOutput))\n#47 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/vendor/laravel/framework/src/Illuminate/Foundation/Application.php(1235): Illuminate\\Foundation\\Console\\Kernel->handle(Object(Symfony\\Component\\Console\\Input\\ArgvInput), Object(Symfony\\Component\\Console\\Output\\ConsoleOutput))\n#48 /Users/qweqsbrako/Documents/Projects/JudyHomeCarePortal/artisan(16): Illuminate\\Foundation\\Application->handleCommand(Object(Symfony\\Component\\Console\\Input\\ArgvInput))\n#49 {main}', '2025-10-15 20:55:17');

-- --------------------------------------------------------

--
-- Table structure for table `incident_reports`
--

CREATE TABLE `incident_reports` (
  `id` bigint UNSIGNED NOT NULL,
  `report_date` date NOT NULL,
  `incident_date` date NOT NULL,
  `incident_time` time NOT NULL,
  `incident_location` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `incident_type` enum('fall','medication_error','equipment_failure','injury','other') COLLATE utf8mb4_unicode_ci NOT NULL,
  `incident_type_other` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `patient_id` bigint UNSIGNED DEFAULT NULL,
  `patient_age` int DEFAULT NULL,
  `patient_sex` enum('M','F') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `client_id_case_no` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `staff_family_involved` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `staff_family_role` enum('nurse','family','other') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `staff_family_role_other` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `incident_description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `first_aid_provided` tinyint(1) NOT NULL DEFAULT '0',
  `first_aid_description` text COLLATE utf8mb4_unicode_ci,
  `care_provider_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `transferred_to_hospital` tinyint(1) NOT NULL DEFAULT '0',
  `hospital_transfer_details` text COLLATE utf8mb4_unicode_ci,
  `witness_names` text COLLATE utf8mb4_unicode_ci,
  `witness_contacts` text COLLATE utf8mb4_unicode_ci,
  `reported_to_supervisor` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `corrective_preventive_actions` text COLLATE utf8mb4_unicode_ci,
  `reported_by` bigint UNSIGNED NOT NULL,
  `reported_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `reviewed_by` bigint UNSIGNED DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `status` enum('pending','under_review','investigated','resolved','closed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `severity` enum('low','medium','high','critical') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'medium',
  `follow_up_required` tinyint(1) NOT NULL DEFAULT '0',
  `follow_up_date` date DEFAULT NULL,
  `assigned_to` bigint UNSIGNED DEFAULT NULL,
  `last_updated_by` bigint UNSIGNED DEFAULT NULL,
  `closed_by` bigint UNSIGNED DEFAULT NULL,
  `closed_at` timestamp NULL DEFAULT NULL,
  `closure_reason` text COLLATE utf8mb4_unicode_ci,
  `resolved_by` bigint UNSIGNED DEFAULT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `attachments` json DEFAULT NULL COMMENT 'Array of file paths for photos, documents, etc.',
  `investigation_notes` text COLLATE utf8mb4_unicode_ci,
  `final_resolution` text COLLATE utf8mb4_unicode_ci,
  `prevention_measures` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `incident_reports`
--

INSERT INTO `incident_reports` (`id`, `report_date`, `incident_date`, `incident_time`, `incident_location`, `incident_type`, `incident_type_other`, `patient_id`, `patient_age`, `patient_sex`, `client_id_case_no`, `staff_family_involved`, `staff_family_role`, `staff_family_role_other`, `incident_description`, `first_aid_provided`, `first_aid_description`, `care_provider_name`, `transferred_to_hospital`, `hospital_transfer_details`, `witness_names`, `witness_contacts`, `reported_to_supervisor`, `corrective_preventive_actions`, `reported_by`, `reported_at`, `reviewed_by`, `reviewed_at`, `status`, `severity`, `follow_up_required`, `follow_up_date`, `assigned_to`, `last_updated_by`, `closed_by`, `closed_at`, `closure_reason`, `resolved_by`, `resolved_at`, `attachments`, `investigation_notes`, `final_resolution`, `prevention_measures`, `created_at`, `updated_at`) VALUES
(1, '2025-10-09', '2025-10-09', '21:52:00', 'Patient’s Living Room', 'equipment_failure', NULL, 8, 34, 'M', '389239', 'Ronald Tagoe', 'family', NULL, 'Patient fell and equipment failed', 1, 'Paracetamol', 'Nurse Lisa', 1, 'Transferred Korle-Bu Teaching Hospital', 'James Manor', NULL, 'Selasi Alazo', NULL, 4, '2025-10-09 21:55:56', NULL, NULL, 'pending', 'critical', 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-09 21:55:56', '2025-10-09 21:55:56');

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `id` bigint UNSIGNED NOT NULL,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint UNSIGNED NOT NULL,
  `reserved_at` int UNSIGNED DEFAULT NULL,
  `available_at` int UNSIGNED NOT NULL,
  `created_at` int UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_batches`
--

CREATE TABLE `job_batches` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_unicode_ci,
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
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `successful` tinyint(1) NOT NULL,
  `failure_reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `attempted_at` timestamp NOT NULL,
  `country` varchar(2) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
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
  `physical_address` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `occupation` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `religion` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `emergency_contact_1_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `emergency_contact_1_relationship` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `emergency_contact_1_phone` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `emergency_contact_2_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `emergency_contact_2_relationship` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `emergency_contact_2_phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `presenting_condition` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `past_medical_history` text COLLATE utf8mb4_unicode_ci,
  `allergies` text COLLATE utf8mb4_unicode_ci,
  `current_medications` text COLLATE utf8mb4_unicode_ci,
  `special_needs` text COLLATE utf8mb4_unicode_ci,
  `general_condition` enum('stable','unstable') COLLATE utf8mb4_unicode_ci NOT NULL,
  `hydration_status` enum('adequate','dehydrated') COLLATE utf8mb4_unicode_ci NOT NULL,
  `nutrition_status` enum('adequate','malnourished') COLLATE utf8mb4_unicode_ci NOT NULL,
  `mobility_status` enum('independent','assisted','bedridden') COLLATE utf8mb4_unicode_ci NOT NULL,
  `has_wounds` tinyint(1) NOT NULL DEFAULT '0',
  `wound_description` text COLLATE utf8mb4_unicode_ci,
  `pain_level` int NOT NULL DEFAULT '0' COMMENT 'Pain scale 0-10',
  `initial_vitals` json NOT NULL,
  `initial_nursing_impression` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `assessment_status` enum('draft','completed','reviewed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'completed',
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `medical_assessments`
--

INSERT INTO `medical_assessments` (`id`, `patient_id`, `nurse_id`, `physical_address`, `occupation`, `religion`, `emergency_contact_1_name`, `emergency_contact_1_relationship`, `emergency_contact_1_phone`, `emergency_contact_2_name`, `emergency_contact_2_relationship`, `emergency_contact_2_phone`, `presenting_condition`, `past_medical_history`, `allergies`, `current_medications`, `special_needs`, `general_condition`, `hydration_status`, `nutrition_status`, `mobility_status`, `has_wounds`, `wound_description`, `pain_level`, `initial_vitals`, `initial_nursing_impression`, `assessment_status`, `completed_at`, `created_at`, `updated_at`, `deleted_at`) VALUES
(7, 16, 4, 'Dome Pillar 2', 'Software Engineer', 'Christian', 'Emmanuel Armah', 'Father', '0208110620', 'Damian Armah', 'Brother', '0244371117', 'Condition Present', 'History Present', 'Allergies Present', 'Current Medication Present', 'Special needs present', 'unstable', 'adequate', 'adequate', 'assisted', 1, 'Bloody', 8, '{\"spo2\": 90, \"pulse\": 72, \"weight\": 70, \"temperature\": 37, \"blood_pressure\": \"122/94\", \"respiratory_rate\": 16}', 'Patient needs so much care now', 'completed', '2025-10-09 20:53:19', '2025-10-09 20:53:19', '2025-10-09 20:53:19', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int UNSIGNED NOT NULL,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
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
(31, '2025_10_07_121829_add_address_to_users_table', 2);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `notifiable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `notifiable_id` bigint UNSIGNED NOT NULL,
  `data` text COLLATE utf8mb4_unicode_ci NOT NULL,
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
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` bigint UNSIGNED NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL,
  `expires_at` timestamp NOT NULL,
  `used_at` timestamp NULL DEFAULT NULL,
  `used_ip` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `patient_feedback`
--

CREATE TABLE `patient_feedback` (
  `id` bigint UNSIGNED NOT NULL,
  `patient_id` bigint UNSIGNED NOT NULL,
  `nurse_id` bigint UNSIGNED NOT NULL,
  `rating` int NOT NULL COMMENT '1-5 star rating',
  `feedback_text` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `admin_response` text COLLATE utf8mb4_unicode_ci,
  `response_date` timestamp NULL DEFAULT NULL,
  `responded_by` bigint UNSIGNED DEFAULT NULL,
  `status` enum('pending','responded') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `would_recommend` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

CREATE TABLE `permissions` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `display_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `category` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `subcategory` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
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
  `tokenable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` bigint UNSIGNED NOT NULL,
  `name` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text COLLATE utf8mb4_unicode_ci,
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
(99, 'App\\Models\\User', 4, 'auth-token', '08f40d4c00a6d6383119844ee81945ac00aec744802846535f7b11b489533377', '[\"*\"]', '2025-10-13 15:46:56', '2025-10-20 15:46:55', '2025-10-13 15:46:55', '2025-10-13 15:46:56');

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
  `general_condition` enum('improved','stable','deteriorating') COLLATE utf8mb4_unicode_ci NOT NULL,
  `pain_level` int NOT NULL DEFAULT '0' COMMENT 'Pain scale 0-10',
  `wound_status` text COLLATE utf8mb4_unicode_ci,
  `other_observations` text COLLATE utf8mb4_unicode_ci,
  `education_provided` text COLLATE utf8mb4_unicode_ci,
  `family_concerns` text COLLATE utf8mb4_unicode_ci,
  `next_steps` text COLLATE utf8mb4_unicode_ci,
  `signed_at` timestamp NULL DEFAULT NULL,
  `signature_method` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'digital',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `progress_notes`
--

INSERT INTO `progress_notes` (`id`, `patient_id`, `nurse_id`, `visit_date`, `visit_time`, `vitals`, `interventions`, `general_condition`, `pain_level`, `wound_status`, `other_observations`, `education_provided`, `family_concerns`, `next_steps`, `signed_at`, `signature_method`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 5, 4, '2025-10-07', '13:10:00', '{\"spo2\": 98, \"pulse\": 72, \"respiration\": 16, \"temperature\": 36.5, \"blood_pressure\": \"120/80\"}', '{\"other\": false, \"wound_care\": true, \"hygiene_care\": false, \"other_details\": null, \"physiotherapy\": true, \"hygiene_details\": null, \"nutrition_details\": null, \"nutrition_support\": false, \"counseling_details\": null, \"medication_details\": \"Paracetamol\", \"wound_care_details\": null, \"counseling_education\": false, \"physiotherapy_details\": null, \"medication_administered\": true}', 'stable', 5, 'No wound', 'Nothing observed', 'Family eduacated on how to administer the drugs every morning at 8am', 'Sleepless night', 'Follow up on vitals', '2025-10-07 13:12:35', 'digital', '2025-10-07 13:12:35', '2025-10-07 13:12:35', NULL),
(2, 5, 4, '2025-10-07', '17:59:00', '{\"spo2\": 98, \"pulse\": 72, \"respiration\": 19, \"temperature\": 36, \"blood_pressure\": \"122/82\"}', '{\"counseling\": false, \"wound_care\": true, \"hygiene_care\": true, \"physiotherapy\": true, \"nutrition_support\": true, \"medication_details\": \"paracetamol adminstered\", \"other_interventions\": false, \"medication_administered\": true}', 'improved', 8, 'There are no wounds recorded', 'Patient bleeds in the nose', 'Parents of the patients have been educated on how to administer drugs every morning at 8am', 'Patient night rest is improving', 'Make sure patient health has improved', '2025-10-07 18:02:20', 'digital', '2025-10-07 18:02:20', '2025-10-07 18:02:20', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `display_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
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
  `shift_type` enum('morning_shift','afternoon_shift','evening_shift','night_shift','custom_shift') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'morning_shift',
  `status` enum('scheduled','confirmed','cancelled','completed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'scheduled',
  `nurse_confirmed_at` timestamp NULL DEFAULT NULL,
  `last_reminder_sent` timestamp NULL DEFAULT NULL,
  `shift_notes` text COLLATE utf8mb4_unicode_ci,
  `location` text COLLATE utf8mb4_unicode_ci,
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
(366, 62, 2, '2025-10-16', '23:00:00', '07:00:00', -960, 0, 287, 'night_shift', 'scheduled', NULL, NULL, 'Vital signs monitoring', 'Patient Home - Osu', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(367, 64, 2, '2025-10-16', '09:00:00', '17:00:00', 480, 0, 284, 'custom_shift', 'scheduled', NULL, NULL, NULL, 'Patient Home - Labone', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(368, 59, 2, '2025-10-16', '23:00:00', '07:00:00', -960, 0, 294, 'night_shift', 'scheduled', NULL, NULL, 'Elderly care assistance', NULL, NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(369, 53, 2, '2025-10-16', '23:00:00', '07:00:00', -960, 0, 292, 'night_shift', 'scheduled', NULL, NULL, 'Follow-up care visit', 'Patient Home - Airport Residential', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(370, 63, 2, '2025-10-17', '13:00:00', '21:00:00', 480, 0, 276, 'afternoon_shift', 'scheduled', NULL, NULL, 'Medication administration required', 'Patient Home - East Legon', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(371, 63, 2, '2025-10-17', '23:00:00', '07:00:00', -960, 0, 290, 'night_shift', 'scheduled', NULL, NULL, 'Medication administration required', NULL, NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(372, 53, 2, '2025-10-17', '09:00:00', '17:00:00', 480, 0, 293, 'custom_shift', 'scheduled', NULL, NULL, 'Patient assessment and documentation', 'Patient Home - Airport Residential', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(373, 56, 2, '2025-10-18', '13:00:00', '21:00:00', 480, 0, 272, 'afternoon_shift', 'scheduled', NULL, NULL, 'Wound care and dressing', 'Patient Home - Airport Residential', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(374, 62, 2, '2025-10-18', '13:00:00', '21:00:00', 480, 0, 276, 'afternoon_shift', 'scheduled', NULL, NULL, 'Post-operative care', 'Patient Home - Airport Residential', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(375, 54, 2, '2025-10-20', '23:00:00', '07:00:00', -960, 0, 271, 'night_shift', 'scheduled', NULL, NULL, 'Post-operative care', 'Patient Home - Airport Residential', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(376, 57, 2, '2025-10-20', '13:00:00', '21:00:00', 480, 0, 268, 'afternoon_shift', 'scheduled', NULL, NULL, NULL, 'Patient Home - Tema', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(377, 64, 2, '2025-10-20', '23:00:00', '07:00:00', -960, 0, 291, 'night_shift', 'scheduled', NULL, NULL, NULL, 'Patient Home - Labone', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(378, 79, 2, '2025-10-20', '09:00:00', '17:00:00', 480, 0, 273, 'custom_shift', 'scheduled', NULL, NULL, NULL, 'Patient Home - Tema', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(379, 60, 2, '2025-10-21', '15:00:00', '23:00:00', 480, 0, 279, 'evening_shift', 'scheduled', NULL, NULL, 'Regular home care visit', 'Patient Home - Labone', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(380, 56, 2, '2025-10-21', '15:00:00', '23:00:00', 480, 0, 267, 'evening_shift', 'scheduled', NULL, NULL, 'Elderly care assistance', 'Patient Home - East Legon', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(381, 79, 2, '2025-10-22', '15:00:00', '23:00:00', 480, 0, 292, 'evening_shift', 'scheduled', NULL, NULL, 'Patient assessment and documentation', 'Patient Home - Airport Residential', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(382, 65, 2, '2025-10-22', '07:00:00', '15:00:00', 480, 0, 285, 'morning_shift', 'scheduled', NULL, NULL, 'Follow-up care visit', 'Patient Home - Tema', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(383, 79, 2, '2025-10-22', '07:00:00', '15:00:00', 480, 0, 273, 'morning_shift', 'scheduled', NULL, NULL, 'Elderly care assistance', 'Patient Home - Osu', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(384, 59, 2, '2025-10-23', '23:00:00', '07:00:00', -960, 0, 294, 'night_shift', 'scheduled', NULL, NULL, 'Physical therapy session', 'Patient Home - Tema', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(385, 60, 2, '2025-10-23', '23:00:00', '07:00:00', -960, 0, 279, 'night_shift', 'scheduled', NULL, NULL, 'Post-operative care', 'Patient Home - Kumasi', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(386, 61, 2, '2025-10-23', '15:00:00', '23:00:00', 480, 0, 267, 'evening_shift', 'scheduled', NULL, NULL, 'Physical therapy session', NULL, NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(387, 56, 2, '2025-10-24', '23:00:00', '07:00:00', -960, 0, 267, 'night_shift', 'scheduled', NULL, NULL, 'Physical therapy session', 'Patient Home - East Legon', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(388, 53, 2, '2025-10-24', '23:00:00', '07:00:00', -960, 0, 293, 'night_shift', 'scheduled', NULL, NULL, NULL, 'Patient Home - Accra', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
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
(400, 4, 2, '2025-10-28', '09:00:00', '17:00:00', 480, 0, 270, 'custom_shift', 'scheduled', NULL, NULL, 'Vital signs monitoring', 'Patient Home - Tema', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 21:03:59', NULL),
(401, 66, 2, '2025-10-28', '09:00:00', '17:00:00', 480, 0, 280, 'custom_shift', 'scheduled', NULL, NULL, 'Medication administration required', 'Patient Home - Osu', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(402, 64, 2, '2025-10-29', '09:00:00', '17:00:00', 480, 0, 284, 'custom_shift', 'scheduled', NULL, NULL, NULL, 'Patient Home - Airport Residential', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(403, 79, 2, '2025-10-29', '23:00:00', '07:00:00', -960, 0, 273, 'night_shift', 'scheduled', NULL, NULL, 'Patient assessment and documentation', NULL, NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(404, 67, 2, '2025-10-29', '15:00:00', '23:00:00', 480, 0, 272, 'evening_shift', 'scheduled', NULL, NULL, 'Elderly care assistance', 'Patient Home - Labone', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(405, 55, 2, '2025-10-29', '23:00:00', '07:00:00', -960, 0, 276, 'night_shift', 'scheduled', NULL, NULL, 'Medication administration required', 'Patient Home - Kumasi', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(406, 60, 2, '2025-10-29', '09:00:00', '17:00:00', 480, 0, 279, 'custom_shift', 'scheduled', NULL, NULL, 'Patient assessment and documentation', 'Patient Home - Kumasi', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(407, 67, 2, '2025-10-30', '09:00:00', '17:00:00', 480, 0, 295, 'custom_shift', 'scheduled', NULL, NULL, 'Regular home care visit', NULL, NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(408, 65, 2, '2025-10-30', '13:00:00', '21:00:00', 480, 0, 279, 'afternoon_shift', 'scheduled', NULL, NULL, 'Medication administration required', 'Patient Home - Airport Residential', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(409, 60, 2, '2025-10-30', '23:00:00', '07:00:00', -960, 0, 279, 'night_shift', 'scheduled', NULL, NULL, 'Medication administration required', 'Patient Home - Osu', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(410, 55, 2, '2025-10-31', '13:00:00', '21:00:00', 480, 0, 276, 'afternoon_shift', 'scheduled', NULL, NULL, 'Follow-up care visit', NULL, NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(411, 64, 2, '2025-10-31', '15:00:00', '23:00:00', 480, 0, 291, 'evening_shift', 'scheduled', NULL, NULL, 'Follow-up care visit', 'Patient Home - Kumasi', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(412, 56, 2, '2025-10-31', '09:00:00', '17:00:00', 480, 0, 283, 'custom_shift', 'scheduled', NULL, NULL, 'Patient assessment and documentation', 'Patient Home - Labone', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(413, 59, 2, '2025-11-01', '15:00:00', '23:00:00', 480, 0, 294, 'evening_shift', 'scheduled', NULL, NULL, 'Post-operative care', 'Patient Home - East Legon', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(414, 62, 2, '2025-11-01', '13:00:00', '21:00:00', 480, 0, 287, 'afternoon_shift', 'scheduled', NULL, NULL, 'Medication administration required', 'Patient Home - Labone', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(415, 67, 2, '2025-11-01', '23:00:00', '07:00:00', -960, 0, 267, 'night_shift', 'scheduled', NULL, NULL, 'Vital signs monitoring', NULL, NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(416, 53, 2, '2025-11-03', '15:00:00', '23:00:00', 480, 0, 296, 'evening_shift', 'scheduled', NULL, NULL, 'Elderly care assistance', 'Patient Home - Cantonments', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(417, 64, 2, '2025-11-03', '13:00:00', '21:00:00', 480, 0, 284, 'afternoon_shift', 'scheduled', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(418, 59, 2, '2025-11-03', '09:00:00', '17:00:00', 480, 0, 290, 'custom_shift', 'scheduled', NULL, NULL, 'Post-operative care', 'Patient Home - Osu', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(419, 61, 2, '2025-11-04', '13:00:00', '21:00:00', 480, 0, 289, 'afternoon_shift', 'scheduled', NULL, NULL, NULL, 'Patient Home - East Legon', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(420, 67, 2, '2025-11-05', '13:00:00', '21:00:00', 480, 0, 273, 'afternoon_shift', 'scheduled', NULL, NULL, NULL, 'Patient Home - Osu', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(421, 64, 2, '2025-11-05', '09:00:00', '17:00:00', 480, 0, 291, 'custom_shift', 'scheduled', NULL, NULL, 'Medication administration required', 'Patient Home - East Legon', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(422, 53, 2, '2025-11-05', '23:00:00', '07:00:00', -960, 0, 280, 'night_shift', 'scheduled', NULL, NULL, 'Follow-up care visit', 'Patient Home - Tema', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(423, 60, 2, '2025-11-05', '09:00:00', '17:00:00', 480, 0, 279, 'custom_shift', 'scheduled', NULL, NULL, 'Wound care and dressing', 'Patient Home - Tema', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(424, 58, 2, '2025-11-06', '13:00:00', '21:00:00', 480, 0, 286, 'afternoon_shift', 'scheduled', NULL, NULL, 'Wound care and dressing', 'Patient Home - East Legon', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(425, 65, 2, '2025-11-06', '09:00:00', '17:00:00', 480, 0, 288, 'custom_shift', 'scheduled', NULL, NULL, 'Patient assessment and documentation', 'Patient Home - East Legon', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(426, 54, 2, '2025-11-06', '23:00:00', '07:00:00', -960, 0, 278, 'night_shift', 'scheduled', NULL, NULL, 'Patient assessment and documentation', 'Patient Home - Accra', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(427, 64, 2, '2025-11-06', '23:00:00', '07:00:00', -960, 0, 284, 'night_shift', 'scheduled', NULL, NULL, NULL, 'Patient Home - Tema', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(428, 59, 2, '2025-11-06', '09:00:00', '17:00:00', 480, 0, 274, 'custom_shift', 'scheduled', NULL, NULL, 'Vital signs monitoring', 'Patient Home - Labone', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(429, 53, 2, '2025-11-07', '07:00:00', '15:00:00', 480, 0, 280, 'morning_shift', 'scheduled', NULL, NULL, 'Regular home care visit', 'Patient Home - Airport Residential', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(430, 58, 2, '2025-11-07', '09:00:00', '17:00:00', 480, 0, 273, 'custom_shift', 'scheduled', NULL, NULL, 'Physical therapy session', NULL, NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(431, 55, 2, '2025-11-07', '09:00:00', '17:00:00', 480, 0, 281, 'custom_shift', 'scheduled', NULL, NULL, 'Post-operative care', 'Patient Home - East Legon', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(432, 64, 2, '2025-11-07', '07:00:00', '15:00:00', 480, 0, 291, 'morning_shift', 'scheduled', NULL, NULL, 'Patient assessment and documentation', 'Patient Home - Kumasi', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(433, 54, 2, '2025-11-07', '09:00:00', '17:00:00', 480, 0, 271, 'custom_shift', 'scheduled', NULL, NULL, 'Vital signs monitoring', 'Patient Home - Labone', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(434, 54, 2, '2025-11-08', '07:00:00', '15:00:00', 480, 0, 271, 'morning_shift', 'scheduled', NULL, NULL, 'Follow-up care visit', 'Patient Home - East Legon', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(435, 4, 2, '2025-11-08', '23:00:00', '07:00:00', -960, 0, 270, 'night_shift', 'scheduled', NULL, NULL, NULL, 'Patient Home - Labone', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 20:55:17', NULL),
(436, 64, 2, '2025-11-10', '13:00:00', '21:00:00', 480, 0, 291, 'afternoon_shift', 'scheduled', NULL, NULL, 'Vital signs monitoring', 'Patient Home - Kumasi', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(437, 54, 2, '2025-11-10', '13:00:00', '21:00:00', 480, 0, 271, 'afternoon_shift', 'scheduled', NULL, NULL, 'Medication administration required', 'Patient Home - Airport Residential', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(438, 67, 2, '2025-11-10', '23:00:00', '07:00:00', -960, 0, 268, 'night_shift', 'scheduled', NULL, NULL, 'Vital signs monitoring', 'Patient Home - Accra', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(439, 63, 2, '2025-11-10', '13:00:00', '21:00:00', 480, 0, 270, 'afternoon_shift', 'scheduled', NULL, NULL, 'Follow-up care visit', 'Patient Home - Accra', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(440, 60, 2, '2025-11-11', '23:00:00', '07:00:00', -960, 0, 279, 'night_shift', 'scheduled', NULL, NULL, 'Patient assessment and documentation', 'Patient Home - Accra', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(441, 4, 2, '2025-11-11', '15:00:00', '23:00:00', 480, 0, 270, 'evening_shift', 'scheduled', NULL, NULL, 'Elderly care assistance', 'Patient Home - East Legon', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 20:09:21', NULL),
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
(457, 64, 2, '2025-11-17', '23:00:00', '07:00:00', -960, 0, 284, 'night_shift', 'scheduled', NULL, NULL, 'Follow-up care visit', 'Patient Home - Cantonments', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(458, 64, 2, '2025-11-18', '13:00:00', '21:00:00', 480, 0, 291, 'afternoon_shift', 'scheduled', NULL, NULL, 'Wound care and dressing', 'Patient Home - Accra', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(459, 57, 2, '2025-11-18', '07:00:00', '15:00:00', 480, 0, 277, 'morning_shift', 'scheduled', NULL, NULL, 'Regular home care visit', 'Patient Home - Tema', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(460, 4, 2, '2025-11-19', '15:00:00', '23:00:00', 480, 0, 278, 'evening_shift', 'scheduled', NULL, NULL, 'Elderly care assistance', NULL, NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 20:53:42', NULL),
(461, 66, 2, '2025-11-19', '15:00:00', '23:00:00', 480, 0, 280, 'evening_shift', 'scheduled', NULL, NULL, 'Follow-up care visit', 'Patient Home - Airport Residential', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(462, 57, 2, '2025-11-19', '23:00:00', '07:00:00', -960, 0, 277, 'night_shift', 'scheduled', NULL, NULL, 'Regular home care visit', NULL, NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(463, 64, 2, '2025-11-20', '09:00:00', '17:00:00', 480, 0, 291, 'custom_shift', 'scheduled', NULL, NULL, NULL, 'Patient Home - Tema', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(464, 56, 2, '2025-11-20', '07:00:00', '15:00:00', 480, 0, 272, 'morning_shift', 'scheduled', NULL, NULL, 'Post-operative care', 'Patient Home - Accra', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(465, 59, 2, '2025-11-20', '23:00:00', '07:00:00', -960, 0, 290, 'night_shift', 'scheduled', NULL, NULL, 'Regular home care visit', 'Patient Home - Accra', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(466, 61, 2, '2025-11-20', '09:00:00', '17:00:00', 480, 0, 289, 'custom_shift', 'scheduled', NULL, NULL, 'Follow-up care visit', 'Patient Home - Kumasi', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(467, 55, 2, '2025-11-21', '15:00:00', '23:00:00', 480, 0, 281, 'evening_shift', 'scheduled', NULL, NULL, 'Patient assessment and documentation', 'Patient Home - Osu', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(468, 65, 2, '2025-11-21', '07:00:00', '15:00:00', 480, 0, 285, 'morning_shift', 'scheduled', NULL, NULL, 'Elderly care assistance', 'Patient Home - Labone', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(469, 60, 2, '2025-11-21', '09:00:00', '17:00:00', 480, 0, 279, 'custom_shift', 'scheduled', NULL, NULL, 'Patient assessment and documentation', 'Patient Home - East Legon', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(470, 53, 2, '2025-11-21', '07:00:00', '15:00:00', 480, 0, 293, 'morning_shift', 'scheduled', NULL, NULL, NULL, 'Patient Home - Airport Residential', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(471, 56, 2, '2025-11-22', '07:00:00', '15:00:00', 480, 0, 272, 'morning_shift', 'scheduled', NULL, NULL, 'Medication administration required', 'Patient Home - East Legon', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(472, 58, 2, '2025-11-22', '23:00:00', '07:00:00', -960, 0, 286, 'night_shift', 'scheduled', NULL, NULL, 'Follow-up care visit', 'Patient Home - Airport Residential', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(473, 62, 2, '2025-11-24', '23:00:00', '07:00:00', -960, 0, 276, 'night_shift', 'scheduled', NULL, NULL, 'Patient assessment and documentation', 'Patient Home - Kumasi', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(474, 55, 2, '2025-11-24', '23:00:00', '07:00:00', -960, 0, 281, 'night_shift', 'scheduled', NULL, NULL, 'Wound care and dressing', 'Patient Home - East Legon', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(475, 64, 2, '2025-11-24', '13:00:00', '21:00:00', 480, 0, 291, 'afternoon_shift', 'scheduled', NULL, NULL, 'Patient assessment and documentation', NULL, NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(476, 58, 2, '2025-11-25', '15:00:00', '23:00:00', 480, 0, 286, 'evening_shift', 'scheduled', NULL, NULL, 'Wound care and dressing', 'Patient Home - East Legon', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(477, 54, 2, '2025-11-25', '09:00:00', '17:00:00', 480, 0, 278, 'custom_shift', 'scheduled', NULL, NULL, 'Vital signs monitoring', 'Patient Home - Accra', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(478, 59, 2, '2025-11-25', '07:00:00', '15:00:00', 480, 0, 290, 'morning_shift', 'scheduled', NULL, NULL, 'Post-operative care', 'Patient Home - Labone', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(479, 63, 2, '2025-11-26', '23:00:00', '07:00:00', -960, 0, 295, 'night_shift', 'scheduled', NULL, NULL, 'Elderly care assistance', 'Patient Home - Tema', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(480, 67, 2, '2025-11-26', '13:00:00', '21:00:00', 480, 0, 290, 'afternoon_shift', 'scheduled', NULL, NULL, 'Patient assessment and documentation', 'Patient Home - Cantonments', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(481, 59, 2, '2025-11-27', '13:00:00', '21:00:00', 480, 0, 274, 'afternoon_shift', 'scheduled', NULL, NULL, 'Medication administration required', 'Patient Home - East Legon', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(482, 64, 2, '2025-11-27', '09:00:00', '17:00:00', 480, 0, 291, 'custom_shift', 'scheduled', NULL, NULL, 'Physical therapy session', 'Patient Home - East Legon', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(483, 79, 2, '2025-11-27', '23:00:00', '07:00:00', -960, 0, 273, 'night_shift', 'scheduled', NULL, NULL, 'Post-operative care', NULL, NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(484, 57, 2, '2025-11-28', '09:00:00', '17:00:00', 480, 0, 277, 'custom_shift', 'scheduled', NULL, NULL, 'Follow-up care visit', 'Patient Home - Kumasi', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(485, 4, 2, '2025-11-28', '09:00:00', '17:00:00', 480, 0, 282, 'custom_shift', 'scheduled', NULL, NULL, 'Medication administration required', 'Patient Home - Tema', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 20:29:27', NULL),
(486, 59, 2, '2025-11-28', '07:00:00', '15:00:00', 480, 0, 290, 'morning_shift', 'scheduled', NULL, NULL, NULL, 'Patient Home - Tema', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(487, 60, 2, '2025-11-28', '15:00:00', '23:00:00', 480, 0, 279, 'evening_shift', 'scheduled', NULL, NULL, 'Vital signs monitoring', 'Patient Home - East Legon', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(488, 62, 2, '2025-11-29', '13:00:00', '21:00:00', 480, 0, 287, 'afternoon_shift', 'scheduled', NULL, NULL, 'Regular home care visit', 'Patient Home - Labone', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(489, 79, 2, '2025-11-29', '13:00:00', '21:00:00', 480, 0, 273, 'afternoon_shift', 'scheduled', NULL, NULL, 'Medication administration required', 'Patient Home - Cantonments', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(490, 4, 2, '2025-11-29', '13:00:00', '21:00:00', 480, 1, 278, 'afternoon_shift', 'scheduled', NULL, '2025-10-15 21:13:00', 'Vital signs monitoring', NULL, NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 21:13:02', NULL),
(491, 54, 2, '2025-12-01', '07:00:00', '15:00:00', 480, 0, 278, 'morning_shift', 'scheduled', NULL, NULL, 'Wound care and dressing', 'Patient Home - East Legon', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(492, 56, 2, '2025-12-01', '09:00:00', '17:00:00', 480, 0, 272, 'custom_shift', 'scheduled', NULL, NULL, 'Follow-up care visit', 'Patient Home - Cantonments', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(493, 61, 2, '2025-12-01', '15:00:00', '23:00:00', 480, 0, 271, 'evening_shift', 'scheduled', NULL, NULL, 'Medication administration required', 'Patient Home - Kumasi', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(494, 54, 2, '2025-12-02', '15:00:00', '23:00:00', 480, 0, 271, 'evening_shift', 'scheduled', NULL, NULL, 'Wound care and dressing', 'Patient Home - Labone', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(495, 56, 2, '2025-12-02', '13:00:00', '21:00:00', 480, 0, 272, 'afternoon_shift', 'scheduled', NULL, NULL, 'Regular home care visit', 'Patient Home - Osu', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(496, 61, 2, '2025-12-02', '23:00:00', '07:00:00', -960, 0, 275, 'night_shift', 'scheduled', NULL, NULL, 'Medication administration required', 'Patient Home - Tema', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(497, 54, 2, '2025-12-02', '23:00:00', '07:00:00', -960, 0, 271, 'night_shift', 'scheduled', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
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
(513, 64, 2, '2025-12-09', '23:00:00', '07:00:00', -960, 0, 291, 'night_shift', 'scheduled', NULL, NULL, NULL, 'Patient Home - Osu', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(514, 63, 2, '2025-12-10', '15:00:00', '23:00:00', 480, 0, 272, 'evening_shift', 'scheduled', NULL, NULL, 'Wound care and dressing', 'Patient Home - Cantonments', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(515, 61, 2, '2025-12-10', '13:00:00', '21:00:00', 480, 0, 295, 'afternoon_shift', 'scheduled', NULL, NULL, 'Wound care and dressing', 'Patient Home - East Legon', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(516, 57, 2, '2025-12-10', '07:00:00', '15:00:00', 480, 0, 268, 'morning_shift', 'scheduled', NULL, NULL, 'Wound care and dressing', NULL, NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 21:06:39', NULL),
(517, 53, 2, '2025-12-10', '07:00:00', '15:00:00', 480, 0, 280, 'morning_shift', 'scheduled', NULL, NULL, 'Elderly care assistance', 'Patient Home - Airport Residential', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(518, 66, 2, '2025-12-10', '07:00:00', '15:00:00', 480, 0, 280, 'morning_shift', 'scheduled', NULL, NULL, 'Patient assessment and documentation', 'Patient Home - Cantonments', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(519, 62, 2, '2025-12-11', '09:00:00', '17:00:00', 480, 0, 276, 'custom_shift', 'scheduled', NULL, NULL, 'Follow-up care visit', 'Patient Home - Kumasi', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(520, 58, 2, '2025-12-11', '15:00:00', '23:00:00', 480, 0, 273, 'evening_shift', 'scheduled', NULL, NULL, 'Vital signs monitoring', 'Patient Home - Airport Residential', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(521, 61, 2, '2025-12-11', '23:00:00', '07:00:00', -960, 0, 289, 'night_shift', 'scheduled', NULL, NULL, 'Regular home care visit', 'Patient Home - Airport Residential', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(522, 54, 2, '2025-12-11', '07:00:00', '15:00:00', 480, 0, 278, 'morning_shift', 'scheduled', NULL, NULL, NULL, 'Patient Home - East Legon', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(523, 61, 2, '2025-12-12', '13:00:00', '21:00:00', 480, 0, 289, 'afternoon_shift', 'scheduled', NULL, NULL, 'Vital signs monitoring', 'Patient Home - Tema', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(524, 64, 2, '2025-12-12', '15:00:00', '23:00:00', 480, 0, 291, 'evening_shift', 'scheduled', NULL, NULL, 'Vital signs monitoring', NULL, NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 20:29:04', NULL),
(525, 54, 2, '2025-12-12', '09:00:00', '17:00:00', 480, 0, 278, 'custom_shift', 'scheduled', NULL, NULL, NULL, 'Patient Home - Cantonments', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(526, 63, 2, '2025-12-12', '15:00:00', '23:00:00', 480, 0, 291, 'evening_shift', 'scheduled', NULL, NULL, 'Elderly care assistance', 'Patient Home - Labone', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 20:41:44', NULL),
(527, 57, 2, '2025-12-12', '23:00:00', '07:00:00', -960, 0, 277, 'night_shift', 'scheduled', NULL, NULL, 'Medication administration required', 'Patient Home - Labone', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(528, 79, 2, '2025-12-13', '13:00:00', '21:00:00', 480, 0, 292, 'afternoon_shift', 'scheduled', NULL, NULL, 'Wound care and dressing', 'Patient Home - Labone Danta', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 20:27:11', NULL),
(529, 54, 2, '2025-12-13', '15:00:00', '23:00:00', 480, 0, 271, 'evening_shift', 'scheduled', NULL, NULL, 'Medication administration required', 'Patient Home - Cantonments', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 20:28:30', NULL),
(530, 4, 2, '2025-10-15', '08:00:00', '16:00:00', 480, 0, 270, 'morning_shift', 'scheduled', NULL, NULL, 'Today\'s scheduled visit - for testing', 'Patient Home - Test Location', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(531, 53, 2, '2025-10-15', '10:00:00', '18:00:00', 480, 0, 286, 'morning_shift', 'completed', NULL, NULL, 'Today\'s scheduled visit - for testing', 'Patient Home - Test Location', '2025-10-15 21:56:25', '2025-10-15 22:32:46', 36, '2025-10-15 19:47:35', '2025-10-15 22:32:46', NULL),
(532, 54, 2, '2025-10-15', '14:00:00', '22:00:00', 480, 0, 271, 'morning_shift', 'scheduled', NULL, NULL, 'Today\'s scheduled visit - for testing', 'Patient Home - Test Location', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(533, 4, 2, '2025-10-16', '07:00:00', '15:00:00', 480, 0, 278, 'morning_shift', 'scheduled', NULL, NULL, 'Tomorrow\'s visit - ready for reminders', 'Patient Home - Cantonments', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:53:22', NULL),
(534, 54, 2, '2025-10-16', '07:00:00', '15:00:00', 480, 0, 271, 'morning_shift', 'scheduled', NULL, NULL, 'Tomorrow\'s visit - ready for reminders', 'Patient Home - Kumasi', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(535, 55, 2, '2025-10-16', '07:00:00', '15:00:00', 480, 0, 281, 'morning_shift', 'scheduled', NULL, NULL, 'Tomorrow\'s visit - ready for reminders', 'Patient Home - Labone', NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL),
(536, 56, 2, '2025-10-16', '07:00:00', '15:00:00', 480, 0, 283, 'morning_shift', 'scheduled', NULL, NULL, 'Tomorrow\'s visit - ready for reminders', NULL, NULL, NULL, NULL, '2025-10-15 19:47:35', '2025-10-15 19:47:35', NULL);

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
  `status` enum('active','paused','completed','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `session_type` enum('scheduled_shift','emergency_call','overtime','break_coverage') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'scheduled_shift',
  `clock_in_location` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `clock_out_location` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `clock_in_latitude` decimal(10,8) DEFAULT NULL,
  `clock_in_longitude` decimal(11,8) DEFAULT NULL,
  `clock_out_latitude` decimal(10,8) DEFAULT NULL,
  `clock_out_longitude` decimal(11,8) DEFAULT NULL,
  `work_notes` text COLLATE utf8mb4_unicode_ci,
  `pause_reason` text COLLATE utf8mb4_unicode_ci,
  `activities_performed` json DEFAULT NULL,
  `break_count` int NOT NULL DEFAULT '0',
  `total_break_minutes` int NOT NULL DEFAULT '0',
  `requires_approval` tinyint(1) NOT NULL DEFAULT '0',
  `approved_by` bigint UNSIGNED DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `approval_notes` text COLLATE utf8mb4_unicode_ci,
  `clock_in_ip` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `clock_out_ip` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `device_info` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `time_trackings`
--

INSERT INTO `time_trackings` (`id`, `nurse_id`, `schedule_id`, `patient_id`, `care_plan_id`, `start_time`, `end_time`, `paused_at`, `total_duration_minutes`, `total_pause_duration_minutes`, `status`, `session_type`, `clock_in_location`, `clock_out_location`, `clock_in_latitude`, `clock_in_longitude`, `clock_out_latitude`, `clock_out_longitude`, `work_notes`, `pause_reason`, `activities_performed`, `break_count`, `total_break_minutes`, `requires_approval`, `approved_by`, `approved_at`, `approval_notes`, `clock_in_ip`, `clock_out_ip`, `device_info`, `created_at`, `updated_at`, `deleted_at`) VALUES
(11, 53, 531, 19, 286, '2025-10-15 21:56:25', '2025-10-15 22:32:46', NULL, 36, 0, 'completed', 'scheduled_shift', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, NULL, NULL, NULL, '127.0.0.1', '127.0.0.1', NULL, '2025-10-15 21:56:25', '2025-10-15 22:32:46', NULL),
(12, 4, 533, 5, 278, '2025-10-16 09:49:44', NULL, NULL, 0, 0, 'active', 'scheduled_shift', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, NULL, NULL, NULL, '127.0.0.1', NULL, NULL, '2025-10-16 09:49:44', '2025-10-16 09:49:44', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `transport_requests`
--

CREATE TABLE `transport_requests` (
  `id` bigint UNSIGNED NOT NULL,
  `patient_id` bigint UNSIGNED NOT NULL,
  `requested_by_id` bigint UNSIGNED NOT NULL,
  `transport_type` enum('ambulance','regular') COLLATE utf8mb4_unicode_ci NOT NULL,
  `priority` enum('emergency','urgent','routine') COLLATE utf8mb4_unicode_ci NOT NULL,
  `scheduled_time` datetime DEFAULT NULL,
  `pickup_location` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `pickup_address` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `destination_location` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `destination_address` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `reason` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `special_requirements` text COLLATE utf8mb4_unicode_ci,
  `contact_person` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `driver_id` bigint UNSIGNED DEFAULT NULL,
  `status` enum('requested','assigned','in_progress','completed','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'requested',
  `actual_pickup_time` datetime DEFAULT NULL,
  `actual_arrival_time` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `cancelled_at` datetime DEFAULT NULL,
  `cancellation_reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `estimated_cost` decimal(10,2) DEFAULT NULL,
  `actual_cost` decimal(10,2) DEFAULT NULL,
  `distance_km` decimal(8,2) DEFAULT NULL,
  `rating` int DEFAULT NULL,
  `feedback` text COLLATE utf8mb4_unicode_ci,
  `metadata` json DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `transport_requests`
--

INSERT INTO `transport_requests` (`id`, `patient_id`, `requested_by_id`, `transport_type`, `priority`, `scheduled_time`, `pickup_location`, `pickup_address`, `destination_location`, `destination_address`, `reason`, `special_requirements`, `contact_person`, `driver_id`, `status`, `actual_pickup_time`, `actual_arrival_time`, `completed_at`, `cancelled_at`, `cancellation_reason`, `estimated_cost`, `actual_cost`, `distance_km`, `rating`, `feedback`, `metadata`, `notes`, `created_at`, `updated_at`, `deleted_at`) VALUES
(4, 5, 4, 'regular', 'routine', NULL, '4 Infinite Loop, Cupertino, CA 95014, USA', '4 Infinite Loop, Cupertino, CA 95014, USA', 'Pantang Hospital Administration', 'PR86+2RR, Adenta Municipality, Ghana', 'Scheduled patient transport to medical facility', NULL, NULL, 1, 'assigned', NULL, NULL, NULL, NULL, NULL, 20.00, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-10 18:53:58', '2025-10-10 18:53:58', NULL),
(5, 5, 4, 'regular', 'routine', NULL, '4 Infinite Loop, Cupertino, CA 95014, USA', '4 Infinite Loop, Cupertino, CA 95014, USA', 'Taifa Junction bus stop', 'JPXW+WVJ, Taifa, Ghana', 'Scheduled patient transport to medical facility', NULL, NULL, 3, 'assigned', NULL, NULL, NULL, NULL, NULL, 20.00, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-10 20:50:14', '2025-10-10 20:50:14', NULL),
(6, 8, 4, 'regular', 'routine', NULL, '25 Squash St, Accra, Ghana', '25 Squash St, Accra, Ghana', 'Family Health Hospital', 'Teshie Rd, Accra, Ghana', 'Scheduled patient transport to medical facility', NULL, NULL, 5, 'assigned', NULL, NULL, NULL, NULL, NULL, 20.00, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-10 20:54:15', '2025-10-10 20:54:15', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `two_factor_codes`
--

CREATE TABLE `two_factor_codes` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED NOT NULL,
  `code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `method` enum('email','sms','voice') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'email',
  `expires_at` timestamp NOT NULL,
  `used_at` timestamp NULL DEFAULT NULL,
  `verified_ip` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `login_attempt_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
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
  `first_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('patient','nurse','doctor','admin','superadmin') COLLATE utf8mb4_unicode_ci NOT NULL,
  `role_id` bigint UNSIGNED DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `is_verified` tinyint(1) NOT NULL DEFAULT '0',
  `verification_status` enum('pending','verified','rejected','suspended') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `verification_notes` text COLLATE utf8mb4_unicode_ci,
  `verified_by` bigint UNSIGNED DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('male','female','other') COLLATE utf8mb4_unicode_ci NOT NULL,
  `ghana_card_number` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `avatar` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `license_number` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `specialization` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `years_experience` int DEFAULT NULL,
  `emergency_contact_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `emergency_contact_phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `medical_conditions` json DEFAULT NULL,
  `allergies` json DEFAULT NULL,
  `current_medications` json DEFAULT NULL,
  `two_factor_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `two_factor_method` enum('email','sms','voice') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `two_factor_enabled_at` timestamp NULL DEFAULT NULL,
  `two_factor_disabled_at` timestamp NULL DEFAULT NULL,
  `two_factor_verified_at` timestamp NULL DEFAULT NULL,
  `security_question` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `security_answer_hash` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_login_at` timestamp NULL DEFAULT NULL,
  `last_login_ip` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password_changed_at` timestamp NULL DEFAULT NULL,
  `force_password_change` tinyint(1) NOT NULL DEFAULT '0',
  `password_reset_token` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password_reset_expires` timestamp NULL DEFAULT NULL,
  `registered_ip` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `preferences` json DEFAULT NULL,
  `timezone` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'UTC',
  `locale` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'en',
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `first_name`, `last_name`, `email`, `email_verified_at`, `password`, `phone`, `role`, `role_id`, `is_active`, `is_verified`, `verification_status`, `verification_notes`, `verified_by`, `verified_at`, `date_of_birth`, `gender`, `ghana_card_number`, `address`, `avatar`, `license_number`, `specialization`, `years_experience`, `emergency_contact_name`, `emergency_contact_phone`, `medical_conditions`, `allergies`, `current_medications`, `two_factor_enabled`, `two_factor_method`, `two_factor_enabled_at`, `two_factor_disabled_at`, `two_factor_verified_at`, `security_question`, `security_answer_hash`, `last_login_at`, `last_login_ip`, `password_changed_at`, `force_password_change`, `password_reset_token`, `password_reset_expires`, `registered_ip`, `preferences`, `timezone`, `locale`, `remember_token`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'Super', 'Admin', 'admin@judyhomecare.com', '2025-09-30 20:20:52', '$2y$12$/ZmSVtyNwKvOUGwQV1vnwe0hdefgs1ipgzmp4L9jf/JZygjxdCRYu', '+233501234567', 'superadmin', NULL, 1, 1, 'verified', NULL, NULL, '2025-09-30 20:20:52', '1980-01-01', 'other', 'GHA-000000000-0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 'UTC', 'en', NULL, '2025-09-30 20:20:52', '2025-09-30 20:20:52', NULL),
(2, 'John', 'Manager', 'theophilusboateng7@gmail.com', '2025-09-30 20:20:53', '$2y$12$darmo812XFW8M9GFmHNkXuRiMI3xM1tWO2wVkcPanbBRAFNJYhSie', '+233507654321', 'admin', NULL, 1, 1, 'verified', NULL, NULL, '2025-09-30 20:20:53', '1985-05-15', 'male', 'GHA-111111111-1', NULL, 'avatars/WpkK9FuoeIUG4SIpSkz60l6X67YOHcOwMJgz2NRz.png', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-14 16:03:08', '127.0.0.1', NULL, 0, NULL, NULL, NULL, NULL, 'UTC', 'en', NULL, '2025-09-30 20:20:53', '2025-10-14 16:03:08', NULL),
(3, 'Dr. Sarah', 'Wilson', 'doctor@judyhomecare.com', '2025-09-30 20:20:54', '$2y$12$/bi1wpoi4uVw5afkp0pWxeG9qwnSAj6JX7uJClptffxVx1VxSe..6', '+233509876543', 'doctor', NULL, 1, 1, 'verified', NULL, NULL, '2025-09-30 20:20:54', '1978-03-20', 'female', 'GHA-222222222-2', NULL, 'avatars/hZhwM66lHDyoSko6iDIxoNAJBJD7K2EDRQMM685J.jpg', 'MD-12345-GH', 'internal_medicine', 15, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, '2025-09-30 23:46:53', '127.0.0.1', NULL, 0, NULL, NULL, NULL, NULL, 'UTC', 'en', NULL, '2025-09-30 20:20:54', '2025-09-30 23:56:23', NULL),
(4, 'Lisa', 'Johnson', 'theophilusbrakoboateng@gmail.com', '2025-09-30 20:20:55', '$2y$12$ukrG83f3fzJRkkumMiDsk.3Q9/DKiCplgPU1sX/bu89KWvh6SpGz.', '+233557447800', 'nurse', NULL, 1, 1, 'verified', NULL, NULL, '2025-09-30 20:20:55', '1990-07-10', 'female', 'GHA-333333333-3', NULL, 'avatars/9r13qWV4hUM02aCPDg70PrdXkpWuFHrxu5bIvnUc.png', 'RN-67890-GH', 'general_care', 8, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-13 15:46:55', '127.0.0.1', '2025-10-06 21:46:13', 1, NULL, NULL, NULL, NULL, 'UTC', 'en', NULL, '2025-09-30 20:20:55', '2025-10-14 16:03:20', NULL),
(5, 'Robert Ben', 'Brown', 'patient@judyhomecare.com', '2025-09-30 20:20:56', '$2y$12$q8Not0ddlGgBKc5/T0F7jO8TzMHZx9QMqkpe8dbUHzCxSnRR4w00i', '+233503692581', 'patient', NULL, 1, 1, 'verified', NULL, NULL, '2025-09-30 20:20:56', '1965-12-05', 'male', 'GHA-444444444-4', 'Dome-Pillar 2, Estate', NULL, NULL, NULL, NULL, 'Jane Brown', '+233504567890', '[\"diabetes\", \"hypertension\"]', '[\"penicillin\"]', '[\"metformin\", \"lisinopril\"]', 0, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-01 11:34:31', '127.0.0.1', NULL, 0, NULL, NULL, NULL, NULL, 'UTC', 'en', NULL, '2025-09-30 20:20:56', '2025-10-01 11:59:41', NULL),
(6, 'Priscilla', 'Boateng', 'theophilus.boateng@gtnllc.com', NULL, '$2y$12$3WLe5HV1OuLgPJZBxJzuk.xB.EraVeigGO.MNuyrVvd0VeqrBrUXG', '0557447802', 'nurse', NULL, 0, 1, 'suspended', NULL, 2, '2025-09-30 20:24:39', '1995-01-11', 'female', 'GHA-13892323-1', NULL, NULL, 'NUR-238923', 'general_care', 5, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, '$2y$12$6FHKQ0uiwWJyY8NoQ/h0qeSQP30ISlZYZqImBmnsiv04hIH6/ib8i', '2025-10-06 19:48:07', '127.0.0.1', NULL, 'UTC', 'en', NULL, '2025-09-30 20:23:41', '2025-10-13 20:52:16', '2025-10-13 20:52:16'),
(7, 'Granit', 'Xhaka', 'granit@gmail.com', NULL, '$2y$12$6IBxOh34EUpUt5r2RP74DOnvb.gSY.e9N6LDVeU6AbZ6brNRAEqK2', '0208110620', 'patient', NULL, 1, 1, 'verified', NULL, NULL, NULL, '1997-10-01', 'male', 'GHA-1389023232-1', NULL, 'avatars/1760476087_nZDcQ7tE70.webp', NULL, NULL, NULL, 'Emmanuel Bansah', '+233 24 37117', '[\"Diabetes\", \"Hypertension\", \"Asthma\"]', '[\"Penicillin\", \"Peanuts\", \"Latex\"]', '[\"Metformin 500mg\", \"Lisinopril 10mg\"]', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, '127.0.0.1', NULL, 'UTC', 'en', NULL, '2025-10-07 21:00:46', '2025-10-14 21:08:07', NULL),
(8, 'Philip', 'Gbeko', 'philip@ansah.com', NULL, '$2y$12$JvYKSRbTxdJWJ189b9MnhOtYwh6eRlYYjdl2zeVB.dclCQIz7JNDO', '055748949', 'patient', NULL, 1, 1, 'verified', NULL, NULL, NULL, '1990-11-01', 'male', 'GHA-283492834-1', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '[\"Diabetes\", \"Hypertension\", \"Asthma\"]', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, '127.0.0.1', NULL, 'UTC', 'en', NULL, '2025-10-07 21:20:56', '2025-10-07 21:20:56', NULL),
(16, 'Micheal', 'Asamoah', 'micheal.asamoah@patient.judyhomecare.com', NULL, '$2y$12$ENyMVmvDjpCahRO7eOT4sOHkyBzdQccYWQd04CF5ZN89LYp0hdoDu', '0557447888', 'patient', NULL, 1, 1, 'verified', NULL, 4, '2025-10-09 20:53:19', '1995-10-17', 'male', 'GHA-238928232-1', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 'UTC', 'en', NULL, '2025-10-09 20:53:19', '2025-10-09 20:53:19', NULL),
(17, 'Ben', 'Carson', 'theo.boateng@gtnllc.com', NULL, '$2y$12$gckW09tW5jY4YUs1LgEU5.0lK4MlsN2TwHALny9zvLRQ/eMsxxmy2', '0557447800', 'patient', NULL, 1, 0, 'verified', NULL, NULL, NULL, '1995-10-13', 'male', 'GHA-28238923-1', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, '127.0.0.1', NULL, 'UTC', 'en', NULL, '2025-10-13 21:22:14', '2025-10-14 16:23:40', NULL),
(18, 'Tianna', 'Considine', 'tianna.considine1@patient.com', NULL, '$2y$12$FK43kweA0phC84UquF4TxeQ6Ftkwi8R441gbQ2jJqSaDcahJDMcCm', '0508923688', 'patient', NULL, 1, 0, 'verified', NULL, 1, '2025-09-28 21:39:09', '1996-04-25', 'female', 'GHA-501544260-3', NULL, NULL, NULL, NULL, NULL, 'Wiley Rippin', '0277697377', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, '113.28.181.13', NULL, 'UTC', 'en', NULL, '2025-09-06 21:39:09', '2025-09-25 21:39:09', NULL),
(19, 'Ray', 'Wintheiser', 'ray.wintheiser2@patient.com', NULL, '$2y$12$Uk5LM5kg1GDQVOt93tbT4.dcTjFZGecFZjT..RpEOTT.iUN7PseWq', '0268873360', 'patient', NULL, 1, 1, 'verified', NULL, NULL, '2025-10-12 21:39:10', '1977-01-13', 'male', 'GHA-627215864-7', NULL, NULL, NULL, NULL, NULL, 'Stacy Bartell', '0267502361', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-10 21:39:10', NULL, NULL, 0, NULL, NULL, '81.62.172.105', NULL, 'UTC', 'en', NULL, '2025-10-04 21:39:10', '2025-09-30 21:39:10', NULL),
(20, 'Alyce', 'Miller', 'alyce.miller3@patient.com', NULL, '$2y$12$fS3FGHiZaKMVAdFnC76DE.GGK9Iq38cL0eBJSm.0vK1R7sKo6xbEC', '0205790133', 'patient', NULL, 1, 0, 'pending', NULL, 1, NULL, '1993-12-13', 'female', 'GHA-558736024-9', NULL, NULL, NULL, NULL, NULL, 'Travon Pfannerstill', '0266509063', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, '217.249.121.61', NULL, 'UTC', 'en', NULL, '2025-09-04 21:39:11', '2025-09-26 21:39:11', NULL),
(21, 'Adrianna', 'Leannon', 'adrianna.leannon4@patient.com', NULL, '$2y$12$Cpkw7w0Xyc5h1LFhgAtOd.qDYhbwqKxyMzTQ7NQCzjyE985dap7eW', '0570224298', 'patient', NULL, 1, 0, 'verified', NULL, NULL, '2025-10-07 21:39:12', '1986-08-22', 'male', 'GHA-643100968-9', NULL, NULL, NULL, NULL, NULL, 'Mazie Kuhn', '0506332310', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-10 21:39:12', NULL, NULL, 0, NULL, NULL, '232.219.158.86', NULL, 'UTC', 'en', NULL, '2025-10-10 21:39:12', '2025-10-09 21:39:12', NULL),
(22, 'Samara', 'Bergnaum', 'samara.bergnaum5@patient.com', NULL, '$2y$12$w4jSAswNp02re0hzRaBbLeC1uSkRlOxoiBWWUiNj11wUDL3x/J4DO', '0541661034', 'patient', NULL, 1, 1, 'pending', NULL, NULL, '2025-09-29 21:39:12', '1974-12-14', 'male', 'GHA-572801218-3', NULL, NULL, NULL, NULL, NULL, 'Dr. Kiley Bernhard Sr.', '0561503255', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-07 21:39:12', NULL, NULL, 0, NULL, NULL, '77.173.141.26', NULL, 'UTC', 'en', NULL, '2025-08-13 21:39:12', '2025-09-24 21:39:12', NULL),
(23, 'Trever', 'Stamm', 'trever.stamm6@patient.com', NULL, '$2y$12$dj/zaQPGJCoXIGraQdMg0OYKToDsG4RPaC/fMsyf/2voJz/PSk9P.', '0204675421', 'patient', NULL, 1, 1, 'pending', NULL, 1, '2025-09-20 21:39:13', '1989-04-01', 'female', 'GHA-259172079-0', NULL, NULL, NULL, NULL, NULL, 'Caroline Schulist', '0261630861', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-11 21:39:13', NULL, NULL, 0, NULL, NULL, '66.188.58.13', NULL, 'UTC', 'en', NULL, '2025-09-10 21:39:13', '2025-10-03 21:39:13', NULL),
(24, 'Aliza', 'Pfannerstill', 'aliza.pfannerstill7@patient.com', NULL, '$2y$12$cVekCsLHSmuJ3HaTbmBuxewGIyGiQswfYOkTFCXbLiWED/sfpnhjG', '0572170613', 'patient', NULL, 1, 1, 'verified', NULL, 2, '2025-10-14 20:54:45', '1972-10-15', 'male', 'GHA-990800896-6', NULL, NULL, NULL, NULL, NULL, 'Dr. Allie Reichert V', '0579969660', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-13 21:39:14', NULL, NULL, 0, NULL, NULL, '70.159.205.241', NULL, 'UTC', 'en', NULL, '2025-07-28 21:39:14', '2025-10-14 20:54:45', NULL),
(25, 'Prudence', 'Swift', 'prudence.swift8@patient.com', NULL, '$2y$12$/P9dk9P1IZfbgIWOkka8xOmt/hX9SdrjyEer3upI5xaxCA/ki6bEq', '0505742612', 'patient', NULL, 1, 0, 'pending', NULL, NULL, '2025-09-15 21:39:15', '1978-04-20', 'male', 'GHA-032579897-0', NULL, NULL, NULL, NULL, NULL, 'Sam Jast PhD', '0563167992', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-06 21:39:15', NULL, NULL, 0, NULL, NULL, '230.164.203.206', NULL, 'UTC', 'en', NULL, '2025-09-20 21:39:15', '2025-10-03 21:39:15', NULL),
(26, 'Felix', 'Cassin', 'felix.cassin9@patient.com', NULL, '$2y$12$gdNRForW0K1aXboQIhLqNuWtELax8Ov8axqqDoBTLZNAhCS5Ov4Ua', '0541331931', 'patient', NULL, 1, 1, 'pending', NULL, 1, '2025-10-09 21:39:16', '1974-10-12', 'male', 'GHA-204056358-9', NULL, NULL, NULL, NULL, NULL, 'Mr. Napoleon Johnson PhD', '0246529031', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-06 21:39:16', NULL, NULL, 0, NULL, NULL, '161.14.43.123', NULL, 'UTC', 'en', NULL, '2025-10-12 21:39:16', '2025-09-17 21:39:16', NULL),
(27, 'Roel', 'Lemke', 'roel.lemke10@patient.com', NULL, '$2y$12$gxyhLiBG0XEviOZC6guU2O2qVuAqVQiIU7PlcRsnEAOb79WFlbFlS', '0506316245', 'patient', NULL, 1, 1, 'verified', NULL, 1, NULL, '1999-09-25', 'female', 'GHA-017821726-5', NULL, NULL, NULL, NULL, NULL, 'Vidal Koch', '0269459887', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-12 21:39:17', NULL, NULL, 0, NULL, NULL, '157.225.8.139', NULL, 'UTC', 'en', NULL, '2025-09-05 21:39:17', '2025-10-04 21:39:17', NULL),
(28, 'Heloise', 'Dach', 'heloise.dach11@patient.com', NULL, '$2y$12$cp424UirUB1cvv.2ciaSCukSVQT.t4IKettfKn0iIL1dUfN09gAcK', '0244983217', 'patient', NULL, 0, 0, 'rejected', 'We regret to inform you that your application has been declined after careful review. Please feel free to reapply in the future once the necessary requirements are met.', 2, '2025-10-14 20:54:07', '1989-07-20', 'male', 'GHA-792754025-3', NULL, NULL, NULL, NULL, NULL, 'Mr. Eladio Purdy I', '0569985400', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, '64.4.85.63', NULL, 'UTC', 'en', NULL, '2025-07-15 21:39:18', '2025-10-14 20:54:07', NULL),
(29, 'Tony', 'Ullrich', 'tony.ullrich12@patient.com', NULL, '$2y$12$8e5ZRhYT7ZVzzoHWETj1kOUcUfN1R7EtH749556MCXcLYUNyganES', '0541812640', 'patient', NULL, 1, 0, 'verified', NULL, 1, '2025-10-05 21:39:19', '1979-01-15', 'male', 'GHA-893689778-8', NULL, NULL, NULL, NULL, NULL, 'Wilhelm Bashirian', '0273037347', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-13 21:39:19', NULL, NULL, 0, NULL, NULL, '119.45.165.238', NULL, 'UTC', 'en', NULL, '2025-08-14 21:39:19', '2025-10-04 21:39:19', NULL),
(30, 'Loma', 'Beatty', 'loma.beatty13@patient.com', NULL, '$2y$12$UlEV7wpEH7dgaY0d.ap9meW59GHYvuZn154i2EZ0gagECsl28VEsK', '0501215484', 'patient', NULL, 1, 1, 'pending', NULL, NULL, '2025-10-03 21:39:19', '1972-07-09', 'female', 'GHA-692891512-0', NULL, NULL, NULL, NULL, NULL, 'Delfina Rice', '0209170639', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-08 21:39:19', NULL, NULL, 0, NULL, NULL, '219.72.204.133', NULL, 'UTC', 'en', NULL, '2025-08-09 21:39:19', '2025-09-26 21:39:19', NULL),
(31, 'Shaniya', 'Goldner', 'shaniya.goldner14@patient.com', NULL, '$2y$12$F2mGndy6lZO1m3/OdJoVx.9Wt5KE6qBYvG6nJL9SZ.O4B3KD.v3/u', '0545455662', 'patient', NULL, 1, 1, 'pending', NULL, 1, '2025-09-24 21:39:20', '1987-05-14', 'male', 'GHA-745808165-7', NULL, NULL, NULL, NULL, NULL, 'Nathaniel Oberbrunner', '0557121572', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-10 21:39:20', NULL, NULL, 0, NULL, NULL, '129.165.218.252', NULL, 'UTC', 'en', NULL, '2025-08-14 21:39:20', '2025-10-12 21:39:20', NULL),
(32, 'Pascale', 'Block', 'pascale.block15@patient.com', NULL, '$2y$12$eKMU.rN2ie/r3ZhN.LgBTOAMU5dl/rEB8nL0aWczu.pfvJxXEloZ6', '0575255747', 'patient', NULL, 1, 0, 'verified', NULL, 1, NULL, '1979-12-10', 'male', 'GHA-816945153-8', NULL, NULL, NULL, NULL, NULL, 'Cassandre Cremin PhD', '0500054672', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-10 21:39:21', NULL, NULL, 0, NULL, NULL, '123.152.204.97', NULL, 'UTC', 'en', NULL, '2025-08-10 21:39:21', '2025-10-01 21:39:21', NULL),
(33, 'Davin', 'Daugherty', 'davin.daugherty16@patient.com', NULL, '$2y$12$It8chJpMtbnXE8CfbC1Zwuy6GhmOkhdQ1WAqJfU1YfsWQSFdBFViu', '0261234889', 'patient', NULL, 1, 1, 'pending', NULL, NULL, '2025-10-12 21:39:22', '1973-01-02', 'male', 'GHA-449432628-6', NULL, NULL, NULL, NULL, NULL, 'Bruce Grady', '0503255356', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-08 21:39:22', NULL, NULL, 0, NULL, NULL, '227.212.229.216', NULL, 'UTC', 'en', NULL, '2025-09-25 21:39:22', '2025-09-26 21:39:22', NULL),
(34, 'Tod', 'Huels', 'tod.huels17@patient.com', NULL, '$2y$12$BWPDv4kFfSkPNVrAAlMJMuEcoGk41HYBh2oOJLqtorttpKYwRZKAi', '0567606671', 'patient', NULL, 1, 1, 'pending', NULL, 1, '2025-10-03 21:39:23', '1979-04-07', 'female', 'GHA-084156455-2', NULL, NULL, NULL, NULL, NULL, 'Jeffery Heathcote', '0265377815', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-13 21:39:23', NULL, NULL, 0, NULL, NULL, '102.112.27.175', NULL, 'UTC', 'en', NULL, '2025-08-14 21:39:23', '2025-09-17 21:39:23', NULL),
(35, 'Talon', 'Schoen', 'talon.schoen18@patient.com', NULL, '$2y$12$67n5T3iSGzsUkH6V4g0ceuYbh.V96AIbfK8QRqqV0HlL3kEPtQ68q', '0202976165', 'patient', NULL, 1, 0, 'verified', NULL, 1, '2025-10-03 21:39:24', '1984-02-17', 'female', 'GHA-982887807-0', NULL, NULL, NULL, NULL, NULL, 'Mac Powlowski MD', '0568785740', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-12 21:39:24', NULL, NULL, 0, NULL, NULL, '212.77.238.87', NULL, 'UTC', 'en', NULL, '2025-10-12 21:39:24', '2025-10-09 21:39:24', NULL),
(36, 'Mauricio', 'Batz', 'mauricio.batz19@patient.com', NULL, '$2y$12$LYvQJAgppqaS60qtceAhpuEbxWLfBLOD7uKq4mwMEspxLYenEIUIC', '0265952940', 'patient', NULL, 1, 1, 'verified', NULL, 1, '2025-09-13 21:39:25', '1981-06-17', 'female', 'GHA-567416225-6', NULL, NULL, NULL, NULL, NULL, 'Prof. Yasmeen Beahan', '0260603279', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-07 21:39:25', NULL, NULL, 0, NULL, NULL, '170.4.83.174', NULL, 'UTC', 'en', NULL, '2025-09-15 21:39:25', '2025-09-30 21:39:25', NULL),
(37, 'Adrienne', 'Dickinson', 'adrienne.dickinson20@patient.com', NULL, '$2y$12$02gVg9rzlsc.VuqYEMj/0eg1qBKt2axWWImcCR3YOcQU3beU.0.Ve', '0558964176', 'patient', NULL, 1, 1, 'pending', NULL, 1, NULL, '1983-06-05', 'male', 'GHA-099094949-7', NULL, NULL, NULL, NULL, NULL, 'Kevon Ward', '0272686564', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-13 21:39:26', NULL, NULL, 0, NULL, NULL, '154.129.184.247', NULL, 'UTC', 'en', NULL, '2025-10-05 21:39:26', '2025-09-14 21:39:26', NULL),
(38, 'Jaylon', 'Deckow', 'jaylon.deckow21@patient.com', NULL, '$2y$12$R2nhS3NpttVREYQ5uoIx1uPiP.ndF1Y6v6zjK8WMULt21qla58FfC', '0576115798', 'patient', NULL, 1, 1, 'pending', NULL, 1, '2025-09-27 21:39:27', '1994-09-06', 'male', 'GHA-438069198-9', NULL, NULL, NULL, NULL, NULL, 'Ms. Nedra Nolan', '0509201178', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, '111.184.231.247', NULL, 'UTC', 'en', NULL, '2025-10-05 21:39:27', '2025-09-30 21:39:27', NULL),
(39, 'Jarvis', 'Dicki', 'jarvis.dicki22@patient.com', NULL, '$2y$12$Z.fRGF0cLpmZB9Mxt16dxO4T02zTMUp7QUKYFtF1AYEJarj6sdpry', '0204674835', 'patient', NULL, 1, 1, 'verified', NULL, 1, '2025-10-03 21:39:27', '1982-02-21', 'female', 'GHA-607509478-3', NULL, NULL, NULL, NULL, NULL, 'Dr. Scottie Johns', '0562646956', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-13 21:39:27', NULL, NULL, 0, NULL, NULL, '125.13.245.97', NULL, 'UTC', 'en', NULL, '2025-10-05 21:39:27', '2025-09-30 21:39:27', NULL),
(40, 'Juston', 'Roob', 'juston.roob23@patient.com', NULL, '$2y$12$.LpTIUw6vZBAMr0w5o9T1epW6i3851Sa7.g0lLdkr8Q7EfbIoK.5W', '0553427251', 'patient', NULL, 1, 1, 'pending', NULL, 1, '2025-10-03 21:39:28', '2000-01-19', 'female', 'GHA-041078673-1', NULL, NULL, NULL, NULL, NULL, 'Dave Hilpert', '0205743265', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-11 21:39:28', NULL, NULL, 0, NULL, NULL, '87.216.36.188', NULL, 'UTC', 'en', NULL, '2025-08-19 21:39:28', '2025-10-07 21:39:28', NULL),
(41, 'Callie', 'Bergstrom', 'callie.bergstrom24@patient.com', NULL, '$2y$12$6STbZC9FR6t/QHPLnUclbO0R7cwKAu/0gUneccf1coa/FYwaJfVVS', '0266762352', 'patient', NULL, 1, 1, 'pending', NULL, NULL, '2025-09-27 21:39:29', '1992-11-14', 'female', 'GHA-381095728-0', NULL, NULL, NULL, NULL, NULL, 'Miss Annette Hills', '0565087466', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-07 21:39:29', NULL, NULL, 0, NULL, NULL, '103.54.67.218', NULL, 'UTC', 'en', NULL, '2025-09-29 21:39:29', '2025-09-21 21:39:29', NULL),
(42, 'Desiree', 'Mraz', 'desiree.mraz25@patient.com', NULL, '$2y$12$Igei2D7PLy3YSm9pGCdVgO9OZwHa1gtJnd4koWelOe5E67xzu59UG', '0270280158', 'patient', NULL, 1, 1, 'pending', NULL, 1, '2025-10-10 21:39:30', '1997-08-18', 'male', 'GHA-273337692-8', NULL, NULL, NULL, NULL, NULL, 'Prof. Gene Streich MD', '0501775387', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-13 21:39:30', NULL, NULL, 0, NULL, NULL, '222.54.62.211', NULL, 'UTC', 'en', NULL, '2025-08-19 21:39:30', '2025-09-17 21:39:30', NULL),
(43, 'Katlyn', 'Schneider', 'katlyn.schneider26@patient.com', NULL, '$2y$12$TLXM8IQXGtK3hXOp/M0gXOqHlSQRBW93n26DqY1PAv9Z2463M3Vei', '0549684463', 'patient', NULL, 1, 1, 'pending', NULL, 1, '2025-10-01 21:39:31', '1996-11-18', 'female', 'GHA-612335454-7', NULL, NULL, NULL, NULL, NULL, 'Cicero Hintz', '0269744510', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-06 21:39:31', NULL, NULL, 0, NULL, NULL, '241.121.20.57', NULL, 'UTC', 'en', NULL, '2025-09-08 21:39:31', '2025-09-19 21:39:31', NULL),
(44, 'Rossie', 'Buckridge', 'rossie.buckridge27@patient.com', NULL, '$2y$12$FWLzbuL7o5Nl9qXXdCpwsu260tb2FShQFNIr/MbIRL8Xn2iaslJxm', '0276446507', 'patient', NULL, 0, 0, 'rejected', 'We regret to inform you that your application has been declined after careful review. Please feel free to reapply in the future once the necessary requirements are met.', 2, '2025-10-14 20:54:31', '1985-02-11', 'male', 'GHA-014158869-8', NULL, NULL, NULL, NULL, NULL, 'Keira Sipes', '0209298558', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-11 21:39:32', NULL, NULL, 0, NULL, NULL, '107.210.135.64', NULL, 'UTC', 'en', NULL, '2025-07-17 21:39:32', '2025-10-14 20:54:31', NULL),
(45, 'Felipe', 'Schowalter', 'felipe.schowalter28@patient.com', NULL, '$2y$12$8C/B9R1Jjz.WS6OPHp1Vx.cr78EVf2n.nlCdzCxhQDLN3eVGp3Dja', '0500946032', 'patient', NULL, 1, 1, 'pending', NULL, 1, '2025-09-14 21:39:33', '1997-11-22', 'male', 'GHA-528658301-9', NULL, NULL, NULL, NULL, NULL, 'Karley Haley', '0540455270', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, '192.217.242.115', NULL, 'UTC', 'en', NULL, '2025-08-27 21:39:33', '2025-09-14 21:39:33', NULL),
(46, 'Will', 'Dicki', 'will.dicki29@patient.com', NULL, '$2y$12$LEGz8xTJBxp7wflkk8GmpONeRFFTe1Qd8c0EZHgTIbJBJCBzOZ5Ne', '0248663045', 'patient', NULL, 1, 1, 'pending', NULL, 1, '2025-09-28 21:39:34', '1970-10-31', 'female', 'GHA-188324145-7', NULL, NULL, NULL, NULL, NULL, 'Horace Will IV', '0503944989', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-07 21:39:34', NULL, NULL, 0, NULL, NULL, '165.56.74.55', NULL, 'UTC', 'en', NULL, '2025-08-06 21:39:34', '2025-09-21 21:39:34', NULL),
(47, 'Larry', 'Hill', 'larry.hill30@patient.com', NULL, '$2y$12$fx5xsmi5pQA/CaGh2D/tPeGNgLKJE.PWKwwgZF/2UXYhfqfj8KYmC', '0509885080', 'patient', NULL, 1, 1, 'pending', NULL, 1, NULL, '1994-08-16', 'female', 'GHA-928602871-6', NULL, NULL, NULL, NULL, NULL, 'Meta Hand', '0272737829', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-12 21:39:35', NULL, NULL, 0, NULL, NULL, '175.201.65.115', NULL, 'UTC', 'en', NULL, '2025-10-06 21:39:35', '2025-09-15 21:39:35', NULL),
(48, 'Christa', 'Bradtke', 'christa.bradtke31@patient.com', NULL, '$2y$12$iinfW6EHw8/.DTwU03kN9uKSOV72KhWOcoCYCg/2dkgXx2ddVO.C2', '0560153157', 'patient', NULL, 1, 1, 'verified', NULL, NULL, '2025-09-21 21:39:36', '1977-05-18', 'female', 'GHA-275769855-8', NULL, NULL, NULL, NULL, NULL, 'Sadie Thompson IV', '0549686737', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-09 21:39:36', NULL, NULL, 0, NULL, NULL, '1.151.147.122', NULL, 'UTC', 'en', NULL, '2025-09-17 21:39:36', '2025-09-23 21:39:36', NULL),
(49, 'Ted', 'Howell', 'ted.howell32@patient.com', NULL, '$2y$12$BTJLYl2CIH.FhqAh3kNhies7FiHqV61odXqhBTEyY7m9b4GiXDgdK', '0540351520', 'patient', NULL, 1, 1, 'pending', NULL, 1, '2025-09-25 21:39:37', '1971-04-23', 'male', 'GHA-114235569-5', NULL, NULL, NULL, NULL, NULL, 'Prof. Lavern Beatty II', '0559447410', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-11 21:39:37', NULL, NULL, 0, NULL, NULL, '33.215.247.77', NULL, 'UTC', 'en', NULL, '2025-08-18 21:39:37', '2025-09-18 21:39:37', NULL),
(50, 'Chester', 'Shields', 'chester.shields33@patient.com', NULL, '$2y$12$j7fkf4KEEv4Ie/y.M3KJZuWahgi6uhWwxuCUBWck9faR3mnu0qy0u', '0203484819', 'patient', NULL, 1, 1, 'pending', NULL, 1, '2025-09-21 21:39:37', '1976-07-31', 'male', 'GHA-223544335-8', NULL, NULL, NULL, NULL, NULL, 'Miss Willa Cole', '0209144544', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, '165.91.227.11', NULL, 'UTC', 'en', NULL, '2025-10-10 21:39:37', '2025-10-12 21:39:37', NULL),
(51, 'Bessie', 'Dooley', 'bessie.dooley34@patient.com', NULL, '$2y$12$ic4wQ4D97OSamjJQxyAAO.wOOkGkTzPKmOzj2ZJKg9ClA4z4d3cqO', '0561863938', 'patient', NULL, 1, 1, 'verified', NULL, 1, NULL, '1970-05-02', 'male', 'GHA-692410977-5', NULL, NULL, NULL, NULL, NULL, 'Malinda Jakubowski', '0249479910', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-08 21:39:38', NULL, NULL, 0, NULL, NULL, '190.220.214.252', NULL, 'UTC', 'en', NULL, '2025-09-25 21:39:38', '2025-10-03 21:39:38', NULL),
(52, 'Murphy', 'Nienow', 'murphy.nienow35@patient.com', NULL, '$2y$12$7whiHrqTmTNLmY0.mzG1I.0VippvFFEFZlBnFr7K1O9EcAzjOpWUa', '0551665156', 'patient', NULL, 1, 1, 'verified', NULL, 1, NULL, '1975-10-25', 'male', 'GHA-907182618-1', NULL, NULL, NULL, NULL, NULL, 'Danial Wiegand', '0246238047', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, '214.32.45.215', NULL, 'UTC', 'en', NULL, '2025-09-29 21:39:39', '2025-10-05 21:39:39', NULL),
(53, 'Alex', 'Gislason', 'alex.gislason1@nurse.com', NULL, '$2y$12$VxMhU6iLXiPzJBIUoPOxl.gF5/5hpvKx6o1zGcatKBbXhEADTt9oa', '0279909709', 'nurse', NULL, 1, 1, 'verified', NULL, 1, '2025-09-21 21:39:40', '1986-02-12', 'male', 'GHA-625863292-7', NULL, NULL, 'NUR-433751', 'pediatric_care', 9, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-13 21:39:40', NULL, NULL, 0, NULL, NULL, '69.158.110.127', NULL, 'UTC', 'en', NULL, '2025-08-29 21:39:40', '2025-10-11 21:39:40', NULL),
(54, 'Parker', 'Koelpin', 'parker.koelpin2@nurse.com', NULL, '$2y$12$NYgEncwnt3pLDrYgvEEBmO4H.aR7AGuMXUc0twZcIwhENKmawAy1G', '0549779940', 'nurse', NULL, 1, 1, 'verified', NULL, 1, '2025-10-12 21:39:41', '1989-01-06', 'female', 'GHA-880522180-8', NULL, NULL, 'NUR-759514', 'geriatric_care', 11, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, '86.155.147.135', NULL, 'UTC', 'en', NULL, '2025-08-01 21:39:41', '2025-09-30 21:39:41', NULL),
(55, 'Assunta', 'Grady', 'assunta.grady3@nurse.com', NULL, '$2y$12$mqZH2MsCBW.e7sgR6afeReTBZ9wijP6YzmzTlenXiHErFa2/LdnIO', '0568970074', 'nurse', NULL, 1, 1, 'verified', NULL, 1, '2025-09-17 21:39:42', '1973-01-11', 'female', 'GHA-193694166-4', NULL, NULL, 'NUR-490344', 'general_care', 9, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-12 21:39:42', NULL, NULL, 0, NULL, NULL, '48.199.86.171', NULL, 'UTC', 'en', NULL, '2025-09-12 21:39:42', '2025-10-04 21:39:42', NULL),
(56, 'Ethyl', 'Durgan', 'ethyl.durgan4@nurse.com', NULL, '$2y$12$eel8iYx9nL7bxHbQUh99A.V3kUjhQdW6MelPSCMyn9cgOodk4EBSK', '0500718760', 'nurse', NULL, 1, 1, 'verified', NULL, 1, '2025-09-29 21:39:43', '1990-03-12', 'male', 'GHA-498164665-7', NULL, NULL, 'NUR-563274', 'critical_care', 6, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, '191.138.253.98', NULL, 'UTC', 'en', NULL, '2025-09-08 21:39:43', '2025-10-02 21:39:43', NULL),
(57, 'Frances', 'Enchia', 'erica.roob5@nurse.com', NULL, '$2y$12$Llud7tPMV7ky0xuifPBxAe./7P6CM.EhGPy75632w0nI5Qo5zrIwu', '+233504315723', 'nurse', NULL, 1, 1, 'verified', NULL, 1, '2025-09-24 21:39:44', '1972-12-22', 'male', 'GHA-277356727-1', NULL, NULL, 'NUR-802714', 'critical_care', 9, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-11 21:39:44', NULL, NULL, 0, NULL, NULL, '85.96.203.218', NULL, 'UTC', 'en', NULL, '2025-07-11 21:39:44', '2025-10-05 21:39:44', NULL),
(58, 'Narciso', 'Moen', 'narciso.moen6@nurse.com', NULL, '$2y$12$taevugUEn/wHaImOXx1yD.GovKpcm53fA4ifTJ/KiCQCE3U13RdOa', '0560860749', 'nurse', NULL, 1, 1, 'verified', NULL, 1, '2025-10-05 21:39:45', '1982-09-10', 'female', 'GHA-063445699-4', NULL, NULL, 'NUR-397860', 'emergency_care', 4, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-10 21:39:45', NULL, NULL, 0, NULL, NULL, '170.154.37.219', NULL, 'UTC', 'en', NULL, '2025-05-17 21:39:45', '2025-09-30 21:39:45', NULL),
(59, 'Sonia', 'Dickens', 'sonia.dickens7@nurse.com', NULL, '$2y$12$ORAniIS7L92km5.bXdNXM.6RY/rtnE/VuoW0VEKKzWB6ynhGJrd8K', '0205071436', 'nurse', NULL, 1, 1, 'verified', NULL, 1, '2025-09-29 21:39:46', '1976-02-26', 'male', 'GHA-410039152-4', NULL, NULL, 'NUR-597676', 'emergency_care', 13, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-13 21:39:46', NULL, NULL, 0, NULL, NULL, '154.90.73.74', NULL, 'UTC', 'en', NULL, '2025-07-07 21:39:46', '2025-10-11 21:39:46', NULL),
(60, 'Triston', 'Strosin', 'triston.strosin8@nurse.com', NULL, '$2y$12$3ecNjlmcmqbRL4DtBznWW.w2w31PMpm/YmieveoXsFcSDhU6s9Dey', '0566268311', 'nurse', NULL, 1, 1, 'verified', NULL, 1, '2025-10-08 21:39:46', '1973-04-19', 'female', 'GHA-590646780-4', NULL, NULL, 'NUR-103974', 'pediatric_care', 8, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, '81.74.150.2', NULL, 'UTC', 'en', NULL, '2025-04-23 21:39:46', '2025-10-13 21:39:46', NULL),
(61, 'Bethel', 'Kuvalis', 'bethel.kuvalis9@nurse.com', NULL, '$2y$12$g2h/KEQ8w6GxFQGxLZKdOumdx5w6g3PW3Sv1rI4XW7Fky5biecvgm', '0271544044', 'nurse', NULL, 1, 1, 'verified', NULL, 1, '2025-10-08 21:39:47', '1982-04-20', 'male', 'GHA-543015845-5', NULL, NULL, 'NUR-244476', 'emergency_care', 13, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-10 21:39:47', NULL, NULL, 0, NULL, NULL, '184.204.21.105', NULL, 'UTC', 'en', NULL, '2025-05-31 21:39:47', '2025-10-01 21:39:47', NULL),
(62, 'Eryn', 'Langosh', 'eryn.langosh10@nurse.com', NULL, '$2y$12$PaA8SuSY67SjdJFMesYeT.jwlSD6JtCyaagsOdEHULCsdZqHtqDfG', '0543083961', 'nurse', NULL, 1, 1, 'verified', NULL, 1, '2025-09-30 21:39:48', '1989-11-04', 'male', 'GHA-316220428-2', NULL, NULL, 'NUR-280582', 'pediatric_care', 8, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-11 21:39:48', NULL, NULL, 0, NULL, NULL, '183.105.7.18', NULL, 'UTC', 'en', NULL, '2025-05-03 21:39:48', '2025-10-02 21:39:48', NULL),
(63, 'Trey', 'Cronin', 'trey.cronin11@nurse.com', NULL, '$2y$12$klID5kRz6Cf4gfadjA6YYOYaJMWITpv8t4uFOHkXnROX917uLlPQ2', '0572108492', 'nurse', NULL, 1, 1, 'verified', NULL, 1, '2025-10-01 21:39:49', '1990-07-02', 'male', 'GHA-086050610-4', NULL, NULL, 'NUR-028532', 'general_care', 13, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-10 21:39:49', NULL, NULL, 0, NULL, NULL, '78.140.66.225', NULL, 'UTC', 'en', NULL, '2025-07-08 21:39:49', '2025-10-08 21:39:49', NULL),
(64, 'Jaden', 'Schultz', 'jaden.schultz12@nurse.com', NULL, '$2y$12$ZfF2OCqfhFHeQEsEcW7cQux1x7NhI20l3lilrw59r1iM/Srp0Heiy', '0560139498', 'nurse', NULL, 1, 1, 'verified', NULL, 1, '2025-09-20 21:39:50', '1972-02-26', 'female', 'GHA-185938978-7', NULL, NULL, 'NUR-895102', 'palliative_care', 11, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-12 21:39:50', NULL, NULL, 0, NULL, NULL, '133.241.109.235', NULL, 'UTC', 'en', NULL, '2025-05-03 21:39:50', '2025-09-30 21:39:50', NULL),
(65, 'Victoria', 'Jacobs', 'victoria.jacobs13@nurse.com', NULL, '$2y$12$JbeS.WySIScA64YRWk7zKeWv.kNmhLPGeLYmoa/Fy1IUI6FR6p.D.', '0546845121', 'nurse', NULL, 1, 1, 'verified', NULL, 1, '2025-09-20 21:39:51', '1990-04-27', 'female', 'GHA-147014703-3', NULL, NULL, 'NUR-676930', 'critical_care', 9, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-13 21:39:51', NULL, NULL, 0, NULL, NULL, '196.12.118.232', NULL, 'UTC', 'en', NULL, '2025-04-24 21:39:51', '2025-10-12 21:39:51', NULL),
(66, 'Jonas', 'Towne', 'jonas.towne14@nurse.com', NULL, '$2y$12$Z4SfYQGIBIcCYbtWE9Lal.SOX2YVA2F/SVTmstDFS80X9TMu5Crz.', '0551272414', 'nurse', NULL, 1, 1, 'verified', NULL, 1, '2025-09-30 21:39:52', '1973-08-07', 'male', 'GHA-585857222-5', NULL, NULL, 'NUR-471285', 'critical_care', 5, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, '254.76.223.234', NULL, 'UTC', 'en', NULL, '2025-09-09 21:39:52', '2025-10-04 21:39:52', NULL),
(67, 'Helmer', 'Hand', 'helmer.hand15@nurse.com', NULL, '$2y$12$eGG7coREzTXTVMeTl64cNuHSDWwcRITdIvVLYZKKNiDq7Ui7N5dhG', '0270980892', 'nurse', NULL, 1, 1, 'verified', NULL, 1, '2025-09-16 21:39:53', '1986-07-22', 'male', 'GHA-097598827-9', NULL, NULL, 'NUR-890986', 'palliative_care', 2, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-10 21:39:53', NULL, NULL, 0, NULL, NULL, '65.150.100.240', NULL, 'UTC', 'en', NULL, '2025-08-08 21:39:53', '2025-09-29 21:39:53', NULL),
(68, 'Nelle', 'Lakin', 'dr.nelle.lakin1@doctor.com', NULL, '$2y$12$MH/JxP.eBk9sl.4QzShcbOJRXzDQt5cVBjXcNaTh1JGqPkepAA4.2', '0579421535', 'doctor', NULL, 1, 1, 'verified', NULL, 1, '2025-09-13 21:39:54', '1985-01-25', 'female', 'GHA-098875100-7', NULL, NULL, 'MDC-590748', 'emergency_medicine', 16, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, '171.94.3.17', NULL, 'UTC', 'en', NULL, '2025-02-04 21:39:54', '2025-10-13 21:39:54', NULL),
(69, 'Carolina', 'Kessler', 'dr.carolina.kessler2@doctor.com', NULL, '$2y$12$LOBZCnGNhXytTNGrGepANuHKTTag4q2e5wmZMZCGDnxkGEOAIPEAe', '0547794300', 'doctor', NULL, 1, 1, 'verified', NULL, 1, '2025-10-09 21:39:55', '1980-12-25', 'male', 'GHA-309315034-3', NULL, NULL, 'MDC-321724', 'orthopedics', 18, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-11 21:39:55', NULL, NULL, 0, NULL, NULL, '90.81.141.32', NULL, 'UTC', 'en', NULL, '2025-04-22 21:39:55', '2025-10-05 21:39:55', NULL),
(70, 'Aletha', 'Treutel', 'dr.aletha.treutel3@doctor.com', NULL, '$2y$12$YyirXLo8A7BbQUwEcq0XGO0QtMZt1u5STCbOQc387inigtEsY6PBO', '0558392531', 'doctor', NULL, 1, 1, 'verified', NULL, 1, '2025-09-20 21:39:56', '1977-06-14', 'male', 'GHA-504152713-5', NULL, NULL, 'MDC-568192', 'general_medicine', 19, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-12 21:39:56', NULL, NULL, 0, NULL, NULL, '131.42.27.113', NULL, 'UTC', 'en', NULL, '2025-04-22 21:39:56', '2025-10-04 21:39:56', NULL),
(71, 'Katlyn', 'King', 'dr.katlyn.king4@doctor.com', NULL, '$2y$12$fCja2weXc.CuqWIPj8hLJuIRARePjJL5Aen0e3L4JHYlQdt5sg.2S', '0573349498', 'doctor', NULL, 1, 1, 'verified', NULL, 1, '2025-09-18 21:39:56', '1970-06-10', 'male', 'GHA-857075441-5', NULL, NULL, 'MDC-102447', 'general_medicine', 24, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-12 21:39:56', NULL, NULL, 0, NULL, NULL, '171.168.130.18', NULL, 'UTC', 'en', NULL, '2025-01-16 21:39:56', '2025-10-06 21:39:56', NULL),
(72, 'Patsy', 'Cummings', 'dr.patsy.cummings5@doctor.com', NULL, '$2y$12$nunpPYae8Qu2h6Vx0d7wAeDCrx4/u8N44RH2qpL7mLXj8zSH0PrgS', '0567546499', 'doctor', NULL, 1, 1, 'verified', NULL, 1, '2025-09-17 21:39:57', '1974-07-04', 'male', 'GHA-033608658-6', NULL, NULL, 'MDC-357277', 'oncology', 11, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-12 21:39:57', NULL, NULL, 0, NULL, NULL, '203.226.251.173', NULL, 'UTC', 'en', NULL, '2024-11-18 21:39:57', '2025-10-03 21:39:57', NULL),
(73, 'Seamus', 'Swaniawski', 'dr.seamus.swaniawski6@doctor.com', NULL, '$2y$12$Ovpi1g4UMHRdboSNozDL7OS3kA/kruC3fci7gZjKmhQ8JY5W/dIdS', '0275735400', 'doctor', NULL, 1, 1, 'verified', NULL, 1, '2025-09-27 21:39:58', '1978-11-09', 'female', 'GHA-168185789-2', NULL, NULL, 'MDC-950419', 'orthopedics', 10, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-11 21:39:58', NULL, NULL, 0, NULL, NULL, '168.234.108.179', NULL, 'UTC', 'en', NULL, '2024-11-13 21:39:58', '2025-10-05 21:39:58', NULL),
(74, 'Hayden', 'Abbott', 'dr.hayden.abbott7@doctor.com', NULL, '$2y$12$82qhV70FSpG8LZMvMZKxs.xbs.qR1vOHi7qC6GT0heO7ex94D9AUq', '0565332834', 'doctor', NULL, 1, 1, 'verified', NULL, 1, '2025-10-03 21:39:59', '1983-08-04', 'female', 'GHA-566275551-6', NULL, NULL, 'MDC-553315', 'neurology', 19, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-11 21:39:59', NULL, NULL, 0, NULL, NULL, '60.198.206.80', NULL, 'UTC', 'en', NULL, '2025-02-23 21:39:59', '2025-10-06 21:39:59', NULL),
(75, 'Darwin', 'Hill', 'dr.darwin.hill8@doctor.com', NULL, '$2y$12$fTlm4.Yvm7vicxy9fUBMJuCmRD2D/RggiRKqwZjjUfMVoyOtnsTZi', '0546782002', 'doctor', NULL, 1, 1, 'verified', NULL, 1, '2025-10-05 21:40:00', '1977-10-24', 'male', 'GHA-637261433-1', NULL, NULL, 'MDC-847820', 'pediatric_care', 9, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-11 21:40:00', NULL, NULL, 0, NULL, NULL, '158.224.89.41', NULL, 'UTC', 'en', NULL, '2025-04-23 21:40:00', '2025-10-10 21:40:00', NULL),
(76, 'Therese', 'Baumbach', 'dr.therese.baumbach9@doctor.com', NULL, '$2y$12$uDDlPVjqJClwsJW067hOVuTAvXZPO7Vy4LuZaIFtzi1sIouvahB32', '0249851319', 'doctor', NULL, 1, 1, 'verified', NULL, 1, '2025-09-15 21:40:01', '1979-11-20', 'male', 'GHA-293334517-5', NULL, NULL, 'MDC-614055', 'pediatric_care', 13, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-13 21:40:01', NULL, NULL, 0, NULL, NULL, '52.232.183.244', NULL, 'UTC', 'en', NULL, '2024-11-21 21:40:01', '2025-10-06 21:40:01', NULL),
(77, 'Vergie', 'Kassulke', 'dr.vergie.kassulke10@doctor.com', NULL, '$2y$12$9YOj826FbLF9ETe7b1iYp.HpoOC5k9pukWaxdIMIZFaPnS7tZbkVu', '0558452536', 'doctor', NULL, 1, 1, 'verified', NULL, 1, '2025-10-01 21:40:02', '1980-04-15', 'male', 'GHA-383643589-3', NULL, NULL, 'MDC-771622', 'oncology', 19, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-11 21:40:02', NULL, NULL, 0, NULL, NULL, '54.8.141.119', NULL, 'UTC', 'en', NULL, '2025-03-19 21:40:02', '2025-10-03 21:40:02', NULL),
(78, 'Test', 'Patient', 'testpatient@gmail.com', NULL, '$2y$12$4ZjkroWV.eiJJxc0fod6vujQs3fDsH/A55hG7kktD.YMOBRvkKSlq', '0000000000', 'patient', NULL, 1, 1, 'verified', NULL, 2, '2025-10-14 16:34:14', '1995-11-01', 'male', NULL, NULL, NULL, NULL, NULL, NULL, 'Hello', '0000000000', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, '127.0.0.1', NULL, 'UTC', 'en', NULL, '2025-10-14 16:34:14', '2025-10-14 16:34:14', NULL),
(79, 'Test', 'Nurse', 'testnurse@gmail.com', NULL, '$2y$12$ITj8dAZ/4Tis6jRx12LYxOk2FhgvnmEZ2hs7krGgKAeEYFKJP1o4G', '0000000001', 'nurse', NULL, 1, 1, 'verified', NULL, 2, '2025-10-14 16:35:18', NULL, 'male', NULL, NULL, 'avatars/1760468531_AtiuLnXuWi.webp', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, '127.0.0.1', NULL, 'UTC', 'en', NULL, '2025-10-14 16:35:18', '2025-10-14 19:02:11', NULL),
(80, 'Test', 'Doctor', 'testdoctor@gmail.com', NULL, '$2y$12$6.b9y7AGjIJWp/mbNIbBa.3.SNQsU46vpa6bSk0J.RA9nzP4mJ38O', '0000000002', 'doctor', NULL, 1, 1, 'verified', NULL, 2, '2025-10-14 16:40:22', NULL, 'male', NULL, NULL, NULL, NULL, NULL, 6, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, '127.0.0.1', NULL, 'UTC', 'en', NULL, '2025-10-14 16:40:22', '2025-10-14 16:40:22', NULL),
(81, 'Test', 'Admin', 'testadmin@gmail.com', NULL, '$2y$12$v/Plf0SkARqIICV.fKMZh.7eQOLz4W76iJakTX0rRzUfLn0zSu99m', '0000000003', 'admin', NULL, 1, 1, 'verified', NULL, 2, '2025-10-14 16:41:12', NULL, 'male', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, '127.0.0.1', NULL, 'UTC', 'en', NULL, '2025-10-14 16:41:12', '2025-10-14 16:41:12', NULL),
(82, 'Lisah', 'Kobinah', 'lisa@gmail.com', NULL, '$2y$12$ZYwvsreZzhaico3bjN/nw.kZxP10IV70HvyF1./v/vZe4eBR6E3K2', '0000000009', 'patient', NULL, 1, 1, 'verified', NULL, 2, '2025-10-14 17:08:08', NULL, 'male', NULL, NULL, 'avatars/1760463009_jsYxqDRnZF.webp', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, '127.0.0.1', NULL, 'UTC', 'en', NULL, '2025-10-14 17:08:08', '2025-10-14 17:30:09', NULL),
(83, 'sdfa', 'asdfas', 'asdfa@gmi.com', NULL, '$2y$12$LjZMEB.Vz7YqaYtSj.Cr8etNk8ohNG7jXx41KDJZhCbH7Wx6xXYeS', '000000008', 'patient', NULL, 1, 1, 'verified', NULL, 2, '2025-10-14 20:15:08', '1995-10-14', 'male', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, '127.0.0.1', NULL, 'UTC', 'en', NULL, '2025-10-14 20:15:08', '2025-10-14 20:15:13', '2025-10-14 20:15:13');

-- --------------------------------------------------------

--
-- Table structure for table `user_sessions`
--

CREATE TABLE `user_sessions` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED NOT NULL,
  `token_id` bigint UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `device_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
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
  `vehicle_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `vehicle_type` enum('ambulance','regular') COLLATE utf8mb4_unicode_ci NOT NULL,
  `registration_number` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `vehicle_color` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `make` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `model` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `year` int DEFAULT NULL,
  `vin_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `is_available` tinyint(1) NOT NULL DEFAULT '1',
  `status` enum('available','in_use','maintenance','out_of_service') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'available',
  `last_service_date` date DEFAULT NULL,
  `next_service_date` date DEFAULT NULL,
  `mileage` decimal(10,2) DEFAULT NULL,
  `insurance_policy` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `insurance_expiry` date DEFAULT NULL,
  `registration_expiry` date DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `vehicles`
--

INSERT INTO `vehicles` (`id`, `vehicle_id`, `vehicle_type`, `registration_number`, `vehicle_color`, `make`, `model`, `year`, `vin_number`, `is_active`, `is_available`, `status`, `last_service_date`, `next_service_date`, `mileage`, `insurance_policy`, `insurance_expiry`, `registration_expiry`, `notes`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'VEH53179', 'ambulance', 'GR-3489-25', 'White', 'Nissan', 'Sentra', NULL, NULL, 1, 1, 'available', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-01 12:23:28', '2025-10-01 12:23:28', NULL),
(2, 'VEH15895', 'regular', 'GR-1888-23', 'Black', 'Toyota', 'Vitz', NULL, NULL, 1, 1, 'available', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-01 12:23:47', '2025-10-01 12:23:47', NULL),
(3, 'VEH97389', 'regular', 'GE-3892-1', 'Blue', 'Honda', 'Civic', 2020, NULL, 1, 1, 'available', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-10 18:59:53', '2025-10-10 18:59:53', NULL),
(4, 'VEH43151', 'regular', 'GR-388-24', 'White', 'Hyundai', 'Sonata', 2020, NULL, 1, 1, 'available', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-10 19:00:25', '2025-10-10 19:00:25', NULL),
(5, 'VEH27973', 'regular', 'GR-1119-25', 'Black', 'Toyota', 'Yaris', 2025, NULL, 1, 1, 'available', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-10 19:00:48', '2025-10-10 19:00:48', NULL),
(6, 'VEH16903', 'regular', 'GC-550-21', 'White', 'Toyota', 'Camry', 2019, NULL, 1, 1, 'available', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-10 19:01:11', '2025-10-10 19:01:11', NULL);

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
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `notifications_notifiable_type_notifiable_id_index` (`notifiable_type`,`notifiable_id`);

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
  ADD KEY `patient_feedback_status_created_at_index` (`status`,`created_at`);

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
-- AUTO_INCREMENT for table `care_plans`
--
ALTER TABLE `care_plans`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=308;

--
-- AUTO_INCREMENT for table `drivers`
--
ALTER TABLE `drivers`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `driver_vehicle_assignments`
--
ALTER TABLE `driver_vehicle_assignments`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `incident_reports`
--
ALTER TABLE `incident_reports`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=53;

--
-- AUTO_INCREMENT for table `login_history`
--
ALTER TABLE `login_history`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `medical_assessments`
--
ALTER TABLE `medical_assessments`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `patient_feedback`
--
ALTER TABLE `patient_feedback`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=57;

--
-- AUTO_INCREMENT for table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=100;

--
-- AUTO_INCREMENT for table `progress_notes`
--
ALTER TABLE `progress_notes`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

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
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=537;

--
-- AUTO_INCREMENT for table `time_trackings`
--
ALTER TABLE `time_trackings`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `transport_requests`
--
ALTER TABLE `transport_requests`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `two_factor_codes`
--
ALTER TABLE `two_factor_codes`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=84;

--
-- AUTO_INCREMENT for table `user_sessions`
--
ALTER TABLE `user_sessions`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `vehicles`
--
ALTER TABLE `vehicles`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

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
-- Constraints for table `patient_feedback`
--
ALTER TABLE `patient_feedback`
  ADD CONSTRAINT `patient_feedback_nurse_id_foreign` FOREIGN KEY (`nurse_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `patient_feedback_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `patient_feedback_responded_by_foreign` FOREIGN KEY (`responded_by`) REFERENCES `users` (`id`);

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
