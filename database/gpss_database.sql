-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 09, 2026 at 09:02 PM
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
-- Database: `gpss_database`
--

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `booking_id` int(10) UNSIGNED NOT NULL,
  `service_id` int(10) UNSIGNED NOT NULL,
  `client_id` int(10) UNSIGNED NOT NULL,
  `booking_date` date NOT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `status` enum('pending','confirmed','completed','cancelled','payment_failed','conflict') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `active_flag` tinyint(4) GENERATED ALWAYS AS (if(`status` in ('pending','confirmed'),`service_id`,NULL)) VIRTUAL,
  `active_key` varchar(20) GENERATED ALWAYS AS (if(`status` in ('pending','confirmed'),concat(`service_id`,'-',`client_id`),NULL)) VIRTUAL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `category_id` int(10) UNSIGNED NOT NULL,
  `category_key` varchar(60) NOT NULL,
  `category_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`category_id`, `category_key`, `category_name`) VALUES
(1, 'tutoring', 'Tutoring'),
(2, 'photography', 'Photography'),
(3, 'web_design', 'Web Design'),
(4, 'electrical', 'Electrical Repair'),
(5, 'plumbing', 'Plumbing'),
(6, 'graphic_design', 'Graphic Design'),
(7, 'writing', 'Content Writing'),
(8, 'event_services', 'Event Services');

-- --------------------------------------------------------

--
-- Table structure for table `freelancer_profiles`
--

CREATE TABLE `freelancer_profiles` (
  `profile_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `skill_category` varchar(100) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `verified_badge` tinyint(1) NOT NULL DEFAULT 0,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `freelancer_profiles`
--

INSERT INTO `freelancer_profiles` (`profile_id`, `user_id`, `skill_category`, `bio`, `verified_badge`, `latitude`, `longitude`, `created_at`, `updated_at`) VALUES
(1, 101, 'Tutoring', 'Experienced tutoring professional based in Kandy, Sri Lanka, ready to help with your next project.', 1, 7.3165669, 80.6378481, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(2, 2, 'Photography', 'Experienced photography professional based in Kandy, Sri Lanka, ready to help with your next project.', 1, 7.2933545, 80.6500420, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(3, 3, 'Web Design', 'Experienced web design professional based in Kandy, Sri Lanka, ready to help with your next project.', 1, 7.2777493, 80.6075880, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(4, 4, 'Electrical Repair', 'Experienced electrical repair professional based in Kandy, Sri Lanka, ready to help with your next project.', 0, 7.3189020, 80.6510467, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(5, 5, 'Plumbing', 'Experienced plumbing professional based in Kandy, Sri Lanka, ready to help with your next project.', 1, 7.3106556, 80.6375303, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(6, 6, 'Graphic Design', 'Experienced graphic design professional based in Kandy, Sri Lanka, ready to help with your next project.', 1, 7.2341884, 80.6317857, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(7, 7, 'Content Writing', 'Experienced content writing professional based in Kandy, Sri Lanka, ready to help with your next project.', 1, 7.3065198, 80.6818948, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(8, 8, 'Event Services', 'Experienced event services professional based in Kandy, Sri Lanka, ready to help with your next project.', 0, 7.2233325, 80.6140247, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(9, 9, 'Tutoring', 'Experienced tutoring professional based in Kandy, Sri Lanka, ready to help with your next project.', 1, 7.2427560, 80.6034010, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(10, 10, 'Photography', 'Experienced photography professional based in Kandy, Sri Lanka, ready to help with your next project.', 1, 7.3656096, 80.6367895, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(11, 11, 'Web Design', 'Experienced web design professional based in Kandy, Sri Lanka, ready to help with your next project.', 1, 7.2463755, 80.5017505, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(12, 12, 'Electrical Repair', 'Experienced electrical repair professional based in Kandy, Sri Lanka, ready to help with your next project.', 0, 7.3538260, 80.7281031, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(13, 13, 'Plumbing', 'Experienced plumbing professional based in Kandy, Sri Lanka, ready to help with your next project.', 1, 7.2148508, 80.7599253, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(14, 14, 'Graphic Design', 'Experienced graphic design professional based in Kandy, Sri Lanka, ready to help with your next project.', 1, 7.3723752, 80.6910367, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(15, 15, 'Content Writing', 'Experienced content writing professional based in Kandy, Sri Lanka, ready to help with your next project.', 1, 7.1789729, 80.5478414, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(16, 16, 'Event Services', 'Experienced event services professional based in Colombo, Sri Lanka, ready to help with your next project.', 0, 6.9167207, 79.7795425, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(17, 17, 'Tutoring', 'Experienced tutoring professional based in Colombo, Sri Lanka, ready to help with your next project.', 1, 6.9836020, 79.8514931, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(18, 18, 'Photography', 'Experienced photography professional based in Colombo, Sri Lanka, ready to help with your next project.', 1, 6.8862730, 79.8472515, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(19, 19, 'Web Design', 'Experienced web design professional based in Colombo, Sri Lanka, ready to help with your next project.', 1, 6.8655329, 79.8040356, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(20, 20, 'Electrical Repair', 'Experienced electrical repair professional based in Colombo, Sri Lanka, ready to help with your next project.', 0, 6.8504927, 79.8204320, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(21, 21, 'Plumbing', 'Experienced plumbing professional based in Colombo, Sri Lanka, ready to help with your next project.', 1, 6.9966002, 79.8819340, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(22, 22, 'Graphic Design', 'Experienced graphic design professional based in Colombo, Sri Lanka, ready to help with your next project.', 1, 6.9198637, 79.8900510, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(23, 23, 'Content Writing', 'Experienced content writing professional based in Colombo, Sri Lanka, ready to help with your next project.', 1, 6.9288480, 79.8774213, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(24, 24, 'Event Services', 'Experienced event services professional based in Colombo, Sri Lanka, ready to help with your next project.', 0, 6.9239336, 79.8791604, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(25, 25, 'Tutoring', 'Experienced tutoring professional based in Colombo, Sri Lanka, ready to help with your next project.', 1, 6.8833221, 79.9113279, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(26, 26, 'Photography', 'Experienced photography professional based in Colombo, Sri Lanka, ready to help with your next project.', 1, 6.9377612, 79.9024965, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(27, 27, 'Web Design', 'Experienced web design professional based in Colombo, Sri Lanka, ready to help with your next project.', 1, 6.9575768, 79.8482918, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(28, 28, 'Electrical Repair', 'Experienced electrical repair professional based in Negombo, Sri Lanka, ready to help with your next project.', 0, 7.1561409, 79.7927878, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(29, 29, 'Plumbing', 'Experienced plumbing professional based in Negombo, Sri Lanka, ready to help with your next project.', 1, 7.2051056, 79.8113899, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(30, 30, 'Graphic Design', 'Experienced graphic design professional based in Negombo, Sri Lanka, ready to help with your next project.', 1, 7.1910573, 79.8522332, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(31, 31, 'Content Writing', 'Experienced content writing professional based in Negombo, Sri Lanka, ready to help with your next project.', 1, 7.1457334, 79.7595677, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(32, 32, 'Event Services', 'Experienced event services professional based in Negombo, Sri Lanka, ready to help with your next project.', 0, 7.1846634, 79.7811079, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(33, 33, 'Tutoring', 'Experienced tutoring professional based in Gampaha, Sri Lanka, ready to help with your next project.', 1, 7.1011146, 79.9299440, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(34, 34, 'Photography', 'Experienced photography professional based in Gampaha, Sri Lanka, ready to help with your next project.', 1, 7.1163431, 80.0203842, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(35, 35, 'Web Design', 'Experienced web design professional based in Gampaha, Sri Lanka, ready to help with your next project.', 1, 7.0831365, 80.0518823, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(36, 36, 'Electrical Repair', 'Experienced electrical repair professional based in Gampaha, Sri Lanka, ready to help with your next project.', 0, 7.1135332, 80.0044887, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(37, 37, 'Plumbing', 'Experienced plumbing professional based in Gampaha, Sri Lanka, ready to help with your next project.', 1, 7.0525299, 80.0958193, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(38, 38, 'Graphic Design', 'Experienced graphic design professional based in Kurunegala, Sri Lanka, ready to help with your next project.', 1, 7.4323588, 80.4065559, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(39, 39, 'Content Writing', 'Experienced content writing professional based in Kurunegala, Sri Lanka, ready to help with your next project.', 1, 7.3979371, 80.3882694, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(40, 40, 'Event Services', 'Experienced event services professional based in Kurunegala, Sri Lanka, ready to help with your next project.', 0, 7.4869965, 80.3978473, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(41, 41, 'Tutoring', 'Experienced tutoring professional based in Kurunegala, Sri Lanka, ready to help with your next project.', 1, 7.4815350, 80.4246025, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(42, 42, 'Photography', 'Experienced photography professional based in Kurunegala, Sri Lanka, ready to help with your next project.', 1, 7.5356951, 80.3274521, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(43, 43, 'Web Design', 'Experienced web design professional based in Kurunegala, Sri Lanka, ready to help with your next project.', 1, 7.4949190, 80.4092374, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(44, 44, 'Electrical Repair', 'Experienced electrical repair professional based in Dambulla, Sri Lanka, ready to help with your next project.', 0, 7.7743998, 80.7658289, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(45, 45, 'Plumbing', 'Experienced plumbing professional based in Dambulla, Sri Lanka, ready to help with your next project.', 1, 7.8895509, 80.7768653, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(46, 46, 'Graphic Design', 'Experienced graphic design professional based in Dambulla, Sri Lanka, ready to help with your next project.', 1, 7.8599515, 80.7581120, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(47, 47, 'Content Writing', 'Experienced content writing professional based in Dambulla, Sri Lanka, ready to help with your next project.', 1, 7.8021545, 80.8099173, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(48, 48, 'Event Services', 'Experienced event services professional based in Anuradhapura, Sri Lanka, ready to help with your next project.', 0, 8.3005586, 80.4137810, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(49, 49, 'Tutoring', 'Experienced tutoring professional based in Anuradhapura, Sri Lanka, ready to help with your next project.', 1, 8.2142980, 80.3855455, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(50, 50, 'Photography', 'Experienced photography professional based in Anuradhapura, Sri Lanka, ready to help with your next project.', 1, 8.3732708, 80.3288667, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(51, 51, 'Web Design', 'Experienced web design professional based in Anuradhapura, Sri Lanka, ready to help with your next project.', 1, 8.3095628, 80.3937213, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(52, 52, 'Electrical Repair', 'Experienced electrical repair professional based in Anuradhapura, Sri Lanka, ready to help with your next project.', 0, 8.2428671, 80.3873157, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(53, 53, 'Plumbing', 'Experienced plumbing professional based in Jaffna, Sri Lanka, ready to help with your next project.', 1, 9.6405891, 79.9995416, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(54, 54, 'Graphic Design', 'Experienced graphic design professional based in Jaffna, Sri Lanka, ready to help with your next project.', 1, 9.6440199, 80.0332042, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(55, 55, 'Content Writing', 'Experienced content writing professional based in Jaffna, Sri Lanka, ready to help with your next project.', 1, 9.7092994, 80.0110212, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(56, 56, 'Event Services', 'Experienced event services professional based in Jaffna, Sri Lanka, ready to help with your next project.', 0, 9.6541128, 80.1143642, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(57, 57, 'Tutoring', 'Experienced tutoring professional based in Jaffna, Sri Lanka, ready to help with your next project.', 1, 9.6849514, 80.0749620, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(58, 58, 'Photography', 'Experienced photography professional based in Trincomalee, Sri Lanka, ready to help with your next project.', 1, 8.6500660, 81.1481500, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(59, 59, 'Web Design', 'Experienced web design professional based in Trincomalee, Sri Lanka, ready to help with your next project.', 1, 8.5643370, 81.1873815, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(60, 60, 'Electrical Repair', 'Experienced electrical repair professional based in Trincomalee, Sri Lanka, ready to help with your next project.', 0, 8.6240148, 81.2681277, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(61, 61, 'Plumbing', 'Experienced plumbing professional based in Trincomalee, Sri Lanka, ready to help with your next project.', 1, 8.5120627, 81.1959539, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(62, 62, 'Graphic Design', 'Experienced graphic design professional based in Trincomalee, Sri Lanka, ready to help with your next project.', 1, 8.5096797, 81.2000249, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(63, 63, 'Content Writing', 'Experienced content writing professional based in Batticaloa, Sri Lanka, ready to help with your next project.', 1, 7.7269295, 81.6828689, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(64, 64, 'Event Services', 'Experienced event services professional based in Batticaloa, Sri Lanka, ready to help with your next project.', 0, 7.7407131, 81.6700194, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(65, 65, 'Tutoring', 'Experienced tutoring professional based in Batticaloa, Sri Lanka, ready to help with your next project.', 1, 7.7742840, 81.5971774, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(66, 66, 'Photography', 'Experienced photography professional based in Batticaloa, Sri Lanka, ready to help with your next project.', 1, 7.7653082, 81.6878887, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(67, 67, 'Web Design', 'Experienced web design professional based in Nuwara Eliya, Sri Lanka, ready to help with your next project.', 1, 7.0329594, 80.7600584, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(68, 68, 'Electrical Repair', 'Experienced electrical repair professional based in Nuwara Eliya, Sri Lanka, ready to help with your next project.', 0, 6.9330392, 80.7905812, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(69, 69, 'Plumbing', 'Experienced plumbing professional based in Nuwara Eliya, Sri Lanka, ready to help with your next project.', 1, 6.9507148, 80.7737769, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(70, 70, 'Graphic Design', 'Experienced graphic design professional based in Nuwara Eliya, Sri Lanka, ready to help with your next project.', 1, 7.0036687, 80.8458361, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(71, 71, 'Content Writing', 'Experienced content writing professional based in Matale, Sri Lanka, ready to help with your next project.', 1, 7.4181896, 80.6073089, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(72, 72, 'Event Services', 'Experienced event services professional based in Matale, Sri Lanka, ready to help with your next project.', 0, 7.4903772, 80.5995705, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(73, 73, 'Tutoring', 'Experienced tutoring professional based in Matale, Sri Lanka, ready to help with your next project.', 1, 7.4787041, 80.6695700, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(74, 74, 'Photography', 'Experienced photography professional based in Matale, Sri Lanka, ready to help with your next project.', 1, 7.4602568, 80.5657743, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(75, 75, 'Web Design', 'Experienced web design professional based in Badulla, Sri Lanka, ready to help with your next project.', 1, 6.9831411, 81.0803046, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(76, 76, 'Electrical Repair', 'Experienced electrical repair professional based in Badulla, Sri Lanka, ready to help with your next project.', 0, 6.9353467, 80.9746272, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(77, 77, 'Plumbing', 'Experienced plumbing professional based in Badulla, Sri Lanka, ready to help with your next project.', 1, 6.9452178, 81.0496174, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(78, 78, 'Graphic Design', 'Experienced graphic design professional based in Badulla, Sri Lanka, ready to help with your next project.', 1, 6.9965520, 81.0748066, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(79, 79, 'Content Writing', 'Experienced content writing professional based in Ratnapura, Sri Lanka, ready to help with your next project.', 1, 6.6492539, 80.3782655, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(80, 80, 'Event Services', 'Experienced event services professional based in Ratnapura, Sri Lanka, ready to help with your next project.', 0, 6.6883328, 80.4286208, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(81, 81, 'Tutoring', 'Experienced tutoring professional based in Ratnapura, Sri Lanka, ready to help with your next project.', 1, 6.6723328, 80.3878203, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(82, 82, 'Photography', 'Experienced photography professional based in Ratnapura, Sri Lanka, ready to help with your next project.', 1, 6.7073539, 80.3824942, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(83, 83, 'Web Design', 'Experienced web design professional based in Galle, Sri Lanka, ready to help with your next project.', 1, 6.1109262, 80.2542384, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(84, 84, 'Electrical Repair', 'Experienced electrical repair professional based in Galle, Sri Lanka, ready to help with your next project.', 0, 6.0180591, 80.1900624, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(85, 85, 'Plumbing', 'Experienced plumbing professional based in Galle, Sri Lanka, ready to help with your next project.', 1, 6.0519775, 80.2378326, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(86, 86, 'Graphic Design', 'Experienced graphic design professional based in Galle, Sri Lanka, ready to help with your next project.', 1, 5.9487512, 80.1763026, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(87, 87, 'Content Writing', 'Experienced content writing professional based in Galle, Sri Lanka, ready to help with your next project.', 1, 6.0440340, 80.1661432, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(88, 88, 'Event Services', 'Experienced event services professional based in Matara, Sri Lanka, ready to help with your next project.', 0, 5.9848007, 80.6315054, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(89, 89, 'Tutoring', 'Experienced tutoring professional based in Matara, Sri Lanka, ready to help with your next project.', 1, 5.9387975, 80.5624882, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(90, 90, 'Photography', 'Experienced photography professional based in Matara, Sri Lanka, ready to help with your next project.', 1, 5.9087396, 80.5647559, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(91, 91, 'Web Design', 'Experienced web design professional based in Matara, Sri Lanka, ready to help with your next project.', 1, 5.9203087, 80.4884422, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(92, 92, 'Electrical Repair', 'Experienced electrical repair professional based in Hambantota, Sri Lanka, ready to help with your next project.', 0, 6.2224855, 81.1781491, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(93, 93, 'Plumbing', 'Experienced plumbing professional based in Hambantota, Sri Lanka, ready to help with your next project.', 1, 6.1188049, 81.1597595, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(94, 94, 'Graphic Design', 'Experienced graphic design professional based in Hambantota, Sri Lanka, ready to help with your next project.', 1, 6.1436314, 81.2083344, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(95, 95, 'Content Writing', 'Experienced content writing professional based in Kegalle, Sri Lanka, ready to help with your next project.', 1, 7.2265060, 80.3547624, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(96, 96, 'Event Services', 'Experienced event services professional based in Kegalle, Sri Lanka, ready to help with your next project.', 0, 7.2429125, 80.3930352, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(97, 97, 'Tutoring', 'Experienced tutoring professional based in Kegalle, Sri Lanka, ready to help with your next project.', 1, 7.2792217, 80.3316700, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(98, 98, 'Photography', 'Experienced photography professional based in Kalutara, Sri Lanka, ready to help with your next project.', 1, 6.6169081, 79.9230153, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(99, 99, 'Web Design', 'Experienced web design professional based in Kalutara, Sri Lanka, ready to help with your next project.', 1, 6.6410531, 79.9791321, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(100, 100, 'Electrical Repair', 'Experienced electrical repair professional based in Puttalam, Sri Lanka, ready to help with your next project.', 0, 8.0917620, 79.7536410, '2026-09-09 18:24:20', '2026-09-09 18:24:20');

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `message_id` int(10) UNSIGNED NOT NULL,
  `sender_id` int(10) UNSIGNED NOT NULL,
  `receiver_id` int(10) UNSIGNED NOT NULL,
  `content` text NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `notification_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `type` varchar(50) NOT NULL,
  `message` varchar(255) NOT NULL,
  `related_id` int(10) UNSIGNED DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`notification_id`, `user_id`, `type`, `message`, `related_id`, `is_read`, `created_at`) VALUES
(1, 101, 'new_booking_request', 'New booking request for \"One-on-One Tutoring Session\" from Client 01.', 1, 0, '2026-09-09 18:50:09'),
(2, 102, 'booking_submitted', 'You booked \"One-on-One Tutoring Session\" with Nadeesha Perera for 2026-09-10. Complete payment to confirm it.', 1, 0, '2026-09-09 18:50:09');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `payment_id` int(10) UNSIGNED NOT NULL,
  `booking_id` int(10) UNSIGNED NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `gateway` enum('payhere','geniepay','stripe') NOT NULL,
  `payment_status` enum('pending','completed','failed','refunded') NOT NULL DEFAULT 'pending',
  `gateway_ref` varchar(150) DEFAULT NULL,
  `paid_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `refund_requests`
--

CREATE TABLE `refund_requests` (
  `refund_id` int(10) UNSIGNED NOT NULL,
  `booking_id` int(10) UNSIGNED NOT NULL,
  `payment_id` int(10) UNSIGNED NOT NULL,
  `client_id` int(10) UNSIGNED NOT NULL,
  `freelancer_id` int(10) UNSIGNED NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `reason` text DEFAULT NULL,
  `account_number` varchar(50) DEFAULT NULL,
  `status` enum('pending','refunded','rejected') NOT NULL DEFAULT 'pending',
  `processed_by` enum('freelancer','admin') DEFAULT NULL,
  `stripe_refund_id` varchar(150) DEFAULT NULL,
  `requested_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `decided_at` timestamp NULL DEFAULT NULL,
  `refunded_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `review_id` int(10) UNSIGNED NOT NULL,
  `booking_id` int(10) UNSIGNED NOT NULL,
  `rating` tinyint(3) UNSIGNED NOT NULL,
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ;

-- --------------------------------------------------------

--
-- Table structure for table `services`
--

CREATE TABLE `services` (
  `service_id` int(10) UNSIGNED NOT NULL,
  `profile_id` int(10) UNSIGNED NOT NULL,
  `category_id` int(10) UNSIGNED NOT NULL,
  `title` varchar(150) NOT NULL,
  `language` enum('tamil','english','sinhala') NOT NULL DEFAULT 'english',
  `price` decimal(10,2) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `services`
--

INSERT INTO `services` (`service_id`, `profile_id`, `category_id`, `title`, `language`, `price`, `description`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'One-on-One Tutoring Session', 'english', 2500.00, 'One-on-one tutoring for school and professional subjects.', 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(2, 2, 2, 'උත්සව සහ ඡායාරූප සේවාව', 'sinhala', 15000.00, 'Event, portrait and product photography services.', 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(3, 3, 3, 'தனிப்பயன் இணையதள வடிவமைப்பு', 'tamil', 35000.00, 'Custom responsive website design and development.', 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(4, 4, 4, 'Home Electrical Repair', 'english', 4000.00, 'Home electrical inspection, installation and repair.', 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(5, 5, 5, 'ජල නල සවි කිරීම සහ අලුත්වැඩියා', 'sinhala', 3500.00, 'Plumbing installation, maintenance and repair.', 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(6, 6, 6, 'லோகோ மற்றும் பிராண்டிங் வடிவமைப்பு', 'tamil', 8000.00, 'Logo, branding and marketing graphic design.', 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(7, 7, 7, 'SEO Content Writing', 'english', 3000.00, 'SEO-friendly website and marketing content writing.', 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(8, 8, 8, 'සම්පූර්ණ උත්සව සම්බන්ධීකරණය', 'sinhala', 25000.00, 'Event planning, coordination and on-site support.', 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(9, 9, 1, 'தனிநபர் பயிற்சி வகுப்பு', 'tamil', 2500.00, 'One-on-one tutoring for school and professional subjects.', 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(10, 10, 2, 'Event & Portrait Photography', 'english', 15000.00, 'Event, portrait and product photography services.', 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(11, 11, 3, 'අභිරුචි වෙබ් අඩවි නිර්මාණය', 'sinhala', 35000.00, 'Custom responsive website design and development.', 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(12, 12, 4, 'வீட்டு மின் பழுது', 'tamil', 4000.00, 'Home electrical inspection, installation and repair.', 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(13, 13, 5, 'Plumbing Installation & Repair', 'english', 3500.00, 'Plumbing installation, maintenance and repair.', 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(14, 14, 6, 'ලාංඡන සහ බ්‍රෑන්ඩින් නිර්මාණය', 'sinhala', 8000.00, 'Logo, branding and marketing graphic design.', 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(15, 15, 7, 'SEO உள்ளடக்க எழுத்து', 'tamil', 3000.00, 'SEO-friendly website and marketing content writing.', 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(16, 16, 8, 'Full Event Coordination', 'english', 25000.00, 'Event planning, coordination and on-site support.', 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(17, 17, 1, 'පුද්ගලික ටියුෂන් පන්තිය', 'sinhala', 2500.00, 'One-on-one tutoring for school and professional subjects.', 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(18, 18, 2, 'நிகழ்வு மற்றும் உருவப்பட புகைப்படம்', 'tamil', 15000.00, 'Event, portrait and product photography services.', 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(19, 19, 3, 'Custom Website Design', 'english', 35000.00, 'Custom responsive website design and development.', 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(20, 20, 4, 'නිවාස විදුලි අලුත්වැඩියා', 'sinhala', 4000.00, 'Home electrical inspection, installation and repair.', 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(21, 21, 5, 'குழாய் பொருத்துதல் மற்றும் பழுது', 'tamil', 3500.00, 'Plumbing installation, maintenance and repair.', 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(22, 22, 6, 'Logo & Branding Design', 'english', 8000.00, 'Logo, branding and marketing graphic design.', 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(23, 23, 7, 'SEO අන්තර්ගත රචනය', 'sinhala', 3000.00, 'SEO-friendly website and marketing content writing.', 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(24, 24, 8, 'முழு நிகழ்வு ஒருங்கிணைப்பு', 'tamil', 25000.00, 'Event planning, coordination and on-site support.', 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(25, 25, 1, 'One-on-One Tutoring Session', 'english', 2500.00, 'One-on-one tutoring for school and professional subjects.', 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(26, 26, 2, 'උත්සව සහ ඡායාරූප සේවාව', 'sinhala', 15000.00, 'Event, portrait and product photography services.', 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(27, 27, 3, 'தனிப்பயன் இணையதள வடிவமைப்பு', 'tamil', 35000.00, 'Custom responsive website design and development.', 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(28, 28, 4, 'Home Electrical Repair', 'english', 4000.00, 'Home electrical inspection, installation and repair.', 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(29, 29, 5, 'ජල නල සවි කිරීම සහ අලුත්වැඩියා', 'sinhala', 3500.00, 'Plumbing installation, maintenance and repair.', 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(30, 30, 6, 'லோகோ மற்றும் பிராண்டிங் வடிவமைப்பு', 'tamil', 8000.00, 'Logo, branding and marketing graphic design.', 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(31, 31, 7, 'SEO Content Writing', 'english', 3000.00, 'SEO-friendly website and marketing content writing.', 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(32, 32, 8, 'සම්පූර්ණ උත්සව සම්බන්ධීකරණය', 'sinhala', 25000.00, 'Event planning, coordination and on-site support.', 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(33, 33, 1, 'தனிநபர் பயிற்சி வகுப்பு', 'tamil', 2500.00, 'One-on-one tutoring for school and professional subjects.', 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(34, 34, 2, 'Event & Portrait Photography', 'english', 15000.00, 'Event, portrait and product photography services.', 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(35, 35, 3, 'අභිරුචි වෙබ් අඩවි නිර්මාණය', 'sinhala', 35000.00, 'Custom responsive website design and development.', 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(36, 36, 4, 'வீட்டு மின் பழுது', 'tamil', 4000.00, 'Home electrical inspection, installation and repair.', 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(37, 37, 5, 'Plumbing Installation & Repair', 'english', 3500.00, 'Plumbing installation, maintenance and repair.', 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(38, 38, 6, 'ලාංඡන සහ බ්‍රෑන්ඩින් නිර්මාණය', 'sinhala', 8000.00, 'Logo, branding and marketing graphic design.', 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(39, 39, 7, 'SEO உள்ளடக்க எழுத்து', 'tamil', 3000.00, 'SEO-friendly website and marketing content writing.', 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(40, 40, 8, 'Full Event Coordination', 'english', 25000.00, 'Event planning, coordination and on-site support.', 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(41, 41, 1, 'පුද්ගලික ටියුෂන් පන්තිය', 'sinhala', 2500.00, 'One-on-one tutoring for school and professional subjects.', 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(42, 42, 2, 'நிகழ்வு மற்றும் உருவப்பட புகைப்படம்', 'tamil', 15000.00, 'Event, portrait and product photography services.', 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(43, 43, 3, 'Custom Website Design', 'english', 35000.00, 'Custom responsive website design and development.', 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(44, 44, 4, 'නිවාස විදුලි අලුත්වැඩියා', 'sinhala', 4000.00, 'Home electrical inspection, installation and repair.', 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(45, 45, 5, 'குழாய் பொருத்துதல் மற்றும் பழுது', 'tamil', 3500.00, 'Plumbing installation, maintenance and repair.', 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(46, 46, 6, 'Logo & Branding Design', 'english', 8000.00, 'Logo, branding and marketing graphic design.', 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(47, 47, 7, 'SEO අන්තර්ගත රචනය', 'sinhala', 3000.00, 'SEO-friendly website and marketing content writing.', 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(48, 48, 8, 'முழு நிகழ்வு ஒருங்கிணைப்பு', 'tamil', 25000.00, 'Event planning, coordination and on-site support.', 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(49, 49, 1, 'One-on-One Tutoring Session', 'english', 2500.00, 'One-on-one tutoring for school and professional subjects.', 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(50, 50, 2, 'උත්සව සහ ඡායාරූප සේවාව', 'sinhala', 15000.00, 'Event, portrait and product photography services.', 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(51, 51, 3, 'தனிப்பயன் இணையதள வடிவமைப்பு', 'tamil', 35000.00, 'Custom responsive website design and development.', 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(52, 52, 4, 'Home Electrical Repair', 'english', 4000.00, 'Home electrical inspection, installation and repair.', 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(53, 53, 5, 'ජල නල සවි කිරීම සහ අලුත්වැඩියා', 'sinhala', 3500.00, 'Plumbing installation, maintenance and repair.', 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(54, 54, 6, 'லோகோ மற்றும் பிராண்டிங் வடிவமைப்பு', 'tamil', 8000.00, 'Logo, branding and marketing graphic design.', 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(55, 55, 7, 'SEO Content Writing', 'english', 3000.00, 'SEO-friendly website and marketing content writing.', 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(56, 56, 8, 'සම්පූර්ණ උත්සව සම්බන්ධීකරණය', 'sinhala', 25000.00, 'Event planning, coordination and on-site support.', 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(57, 57, 1, 'தனிநபர் பயிற்சி வகுப்பு', 'tamil', 2500.00, 'One-on-one tutoring for school and professional subjects.', 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(58, 58, 2, 'Event & Portrait Photography', 'english', 15000.00, 'Event, portrait and product photography services.', 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(59, 59, 3, 'අභිරුචි වෙබ් අඩවි නිර්මාණය', 'sinhala', 35000.00, 'Custom responsive website design and development.', 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(60, 60, 4, 'வீட்டு மின் பழுது', 'tamil', 4000.00, 'Home electrical inspection, installation and repair.', 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(61, 61, 5, 'Plumbing Installation & Repair', 'english', 3500.00, 'Plumbing installation, maintenance and repair.', 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(62, 62, 6, 'ලාංඡන සහ බ්‍රෑන්ඩින් නිර්මාණය', 'sinhala', 8000.00, 'Logo, branding and marketing graphic design.', 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(63, 63, 7, 'SEO உள்ளடக்க எழுத்து', 'tamil', 3000.00, 'SEO-friendly website and marketing content writing.', 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(64, 64, 8, 'Full Event Coordination', 'english', 25000.00, 'Event planning, coordination and on-site support.', 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(65, 65, 1, 'පුද්ගලික ටියුෂන් පන්තිය', 'sinhala', 2500.00, 'One-on-one tutoring for school and professional subjects.', 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(66, 66, 2, 'நிகழ்வு மற்றும் உருவப்பட புகைப்படம்', 'tamil', 15000.00, 'Event, portrait and product photography services.', 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(67, 67, 3, 'Custom Website Design', 'english', 35000.00, 'Custom responsive website design and development.', 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(68, 68, 4, 'නිවාස විදුලි අලුත්වැඩියා', 'sinhala', 4000.00, 'Home electrical inspection, installation and repair.', 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(69, 69, 5, 'குழாய் பொருத்துதல் மற்றும் பழுது', 'tamil', 3500.00, 'Plumbing installation, maintenance and repair.', 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(70, 70, 6, 'Logo & Branding Design', 'english', 8000.00, 'Logo, branding and marketing graphic design.', 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(71, 71, 7, 'SEO අන්තර්ගත රචනය', 'sinhala', 3000.00, 'SEO-friendly website and marketing content writing.', 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(72, 72, 8, 'முழு நிகழ்வு ஒருங்கிணைப்பு', 'tamil', 25000.00, 'Event planning, coordination and on-site support.', 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(73, 73, 1, 'One-on-One Tutoring Session', 'english', 2500.00, 'One-on-one tutoring for school and professional subjects.', 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(74, 74, 2, 'උත්සව සහ ඡායාරූප සේවාව', 'sinhala', 15000.00, 'Event, portrait and product photography services.', 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(75, 75, 3, 'தனிப்பயன் இணையதள வடிவமைப்பு', 'tamil', 35000.00, 'Custom responsive website design and development.', 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(76, 76, 4, 'Home Electrical Repair', 'english', 4000.00, 'Home electrical inspection, installation and repair.', 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(77, 77, 5, 'ජල නල සවි කිරීම සහ අලුත්වැඩියා', 'sinhala', 3500.00, 'Plumbing installation, maintenance and repair.', 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(78, 78, 6, 'லோகோ மற்றும் பிராண்டிங் வடிவமைப்பு', 'tamil', 8000.00, 'Logo, branding and marketing graphic design.', 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(79, 79, 7, 'SEO Content Writing', 'english', 3000.00, 'SEO-friendly website and marketing content writing.', 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(80, 80, 8, 'සම්පූර්ණ උත්සව සම්බන්ධීකරණය', 'sinhala', 25000.00, 'Event planning, coordination and on-site support.', 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(81, 81, 1, 'தனிநபர் பயிற்சி வகுப்பு', 'tamil', 2500.00, 'One-on-one tutoring for school and professional subjects.', 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(82, 82, 2, 'Event & Portrait Photography', 'english', 15000.00, 'Event, portrait and product photography services.', 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(83, 83, 3, 'අභිරුචි වෙබ් අඩවි නිර්මාණය', 'sinhala', 35000.00, 'Custom responsive website design and development.', 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(84, 84, 4, 'வீட்டு மின் பழுது', 'tamil', 4000.00, 'Home electrical inspection, installation and repair.', 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(85, 85, 5, 'Plumbing Installation & Repair', 'english', 3500.00, 'Plumbing installation, maintenance and repair.', 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(86, 86, 6, 'ලාංඡන සහ බ්‍රෑන්ඩින් නිර්මාණය', 'sinhala', 8000.00, 'Logo, branding and marketing graphic design.', 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(87, 87, 7, 'SEO உள்ளடக்க எழுத்து', 'tamil', 3000.00, 'SEO-friendly website and marketing content writing.', 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(88, 88, 8, 'Full Event Coordination', 'english', 25000.00, 'Event planning, coordination and on-site support.', 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(89, 89, 1, 'පුද්ගලික ටියුෂන් පන්තිය', 'sinhala', 2500.00, 'One-on-one tutoring for school and professional subjects.', 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(90, 90, 2, 'நிகழ்வு மற்றும் உருவப்பட புகைப்படம்', 'tamil', 15000.00, 'Event, portrait and product photography services.', 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(91, 91, 3, 'Custom Website Design', 'english', 35000.00, 'Custom responsive website design and development.', 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(92, 92, 4, 'නිවාස විදුලි අලුත්වැඩියා', 'sinhala', 4000.00, 'Home electrical inspection, installation and repair.', 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(93, 93, 5, 'குழாய் பொருத்துதல் மற்றும் பழுது', 'tamil', 3500.00, 'Plumbing installation, maintenance and repair.', 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(94, 94, 6, 'Logo & Branding Design', 'english', 8000.00, 'Logo, branding and marketing graphic design.', 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(95, 95, 7, 'SEO අන්තර්ගත රචනය', 'sinhala', 3000.00, 'SEO-friendly website and marketing content writing.', 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(96, 96, 8, 'முழு நிகழ்வு ஒருங்கிணைப்பு', 'tamil', 25000.00, 'Event planning, coordination and on-site support.', 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(97, 97, 1, 'One-on-One Tutoring Session', 'english', 2500.00, 'One-on-one tutoring for school and professional subjects.', 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(98, 98, 2, 'උත්සව සහ ඡායාරූප සේවාව', 'sinhala', 15000.00, 'Event, portrait and product photography services.', 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(99, 99, 3, 'தனிப்பயன் இணையதள வடிவமைப்பு', 'tamil', 35000.00, 'Custom responsive website design and development.', 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(100, 100, 4, 'Home Electrical Repair', 'english', 4000.00, 'Home electrical inspection, installation and repair.', 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(10) UNSIGNED NOT NULL,
  `full_name` varchar(120) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('freelancer','client','admin') NOT NULL,
  `language_pref` enum('tamil','english','sinhala') NOT NULL DEFAULT 'english',
  `phone` varchar(20) DEFAULT NULL,
  `last_seen` timestamp NULL DEFAULT NULL,
  `photo_path` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `full_name`, `email`, `password_hash`, `role`, `language_pref`, `phone`, `last_seen`, `photo_path`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'guruaprashath', 'Guru@gmail.com', '$2y$12$NkkUdLUDenobLX2i0fniLOWtRcZUDFqIEenxryeAqWOn96xm/JuPe', 'admin', 'english', NULL, NULL, NULL, 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(2, 'Kavindu Silva', 'kavindu.silva.2@example.com', '$2y$10$92k1w8x7WqvV6zZbYhF9UOe1s0m1r0F0m4Kk2m8G0m2wq3fD1P2Nu', 'freelancer', 'sinhala', '+94710000002', NULL, NULL, 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(3, 'Abirami Raj', 'abirami.raj.3@example.com', '$2y$10$92k1w8x7WqvV6zZbYhF9UOe1s0m1r0F0m4Kk2m8G0m2wq3fD1P2Nu', 'freelancer', 'tamil', '+94710000003', NULL, NULL, 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(4, 'Dilshan Fernando', 'dilshan.fernando.4@example.com', '$2y$10$92k1w8x7WqvV6zZbYhF9UOe1s0m1r0F0m4Kk2m8G0m2wq3fD1P2Nu', 'freelancer', 'english', '+94710000004', NULL, NULL, 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(5, 'Sanduni Wickrama', 'sanduni.wickrama.5@example.com', '$2y$10$92k1w8x7WqvV6zZbYhF9UOe1s0m1r0F0m4Kk2m8G0m2wq3fD1P2Nu', 'freelancer', 'sinhala', '+94710000005', NULL, NULL, 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(6, 'Karthik Selvam', 'karthik.selvam.6@example.com', '$2y$10$92k1w8x7WqvV6zZbYhF9UOe1s0m1r0F0m4Kk2m8G0m2wq3fD1P2Nu', 'freelancer', 'tamil', '+94710000006', NULL, NULL, 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(7, 'Ruwan Jayasuriya', 'ruwan.jayasuriya.7@example.com', '$2y$10$92k1w8x7WqvV6zZbYhF9UOe1s0m1r0F0m4Kk2m8G0m2wq3fD1P2Nu', 'freelancer', 'english', '+94710000007', NULL, NULL, 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(8, 'Ishara Bandara', 'ishara.bandara.8@example.com', '$2y$10$92k1w8x7WqvV6zZbYhF9UOe1s0m1r0F0m4Kk2m8G0m2wq3fD1P2Nu', 'freelancer', 'sinhala', '+94710000008', NULL, NULL, 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(9, 'Priya Kumaran', 'priya.kumaran.9@example.com', '$2y$10$92k1w8x7WqvV6zZbYhF9UOe1s0m1r0F0m4Kk2m8G0m2wq3fD1P2Nu', 'freelancer', 'tamil', '+94710000009', NULL, NULL, 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(10, 'Chamara Gunawardena', 'chamara.gunawardena.10@example.com', '$2y$10$92k1w8x7WqvV6zZbYhF9UOe1s0m1r0F0m4Kk2m8G0m2wq3fD1P2Nu', 'freelancer', 'english', '+94710000010', NULL, NULL, 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(11, 'Nuwan Rathnayake', 'nuwan.rathnayake.11@example.com', '$2y$10$92k1w8x7WqvV6zZbYhF9UOe1s0m1r0F0m4Kk2m8G0m2wq3fD1P2Nu', 'freelancer', 'sinhala', '+94710000011', NULL, NULL, 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(12, 'Suresh Kandiah', 'suresh.kandiah.12@example.com', '$2y$10$92k1w8x7WqvV6zZbYhF9UOe1s0m1r0F0m4Kk2m8G0m2wq3fD1P2Nu', 'freelancer', 'tamil', '+94710000012', NULL, NULL, 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(13, 'Lasantha Peiris', 'lasantha.peiris.13@example.com', '$2y$10$92k1w8x7WqvV6zZbYhF9UOe1s0m1r0F0m4Kk2m8G0m2wq3fD1P2Nu', 'freelancer', 'english', '+94710000013', NULL, NULL, 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(14, 'Tharindu Madushan', 'tharindu.madushan.14@example.com', '$2y$10$92k1w8x7WqvV6zZbYhF9UOe1s0m1r0F0m4Kk2m8G0m2wq3fD1P2Nu', 'freelancer', 'sinhala', '+94710000014', NULL, NULL, 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(15, 'Vignesh Ramasamy', 'vignesh.ramasamy.15@example.com', '$2y$10$92k1w8x7WqvV6zZbYhF9UOe1s0m1r0F0m4Kk2m8G0m2wq3fD1P2Nu', 'freelancer', 'tamil', '+94710000015', NULL, NULL, 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(16, 'Yasodha Wijeratne', 'yasodha.wijeratne.16@example.com', '$2y$10$92k1w8x7WqvV6zZbYhF9UOe1s0m1r0F0m4Kk2m8G0m2wq3fD1P2Nu', 'freelancer', 'english', '+94710000016', NULL, NULL, 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(17, 'Hasitha Karunaratne', 'hasitha.karunaratne.17@example.com', '$2y$10$92k1w8x7WqvV6zZbYhF9UOe1s0m1r0F0m4Kk2m8G0m2wq3fD1P2Nu', 'freelancer', 'sinhala', '+94710000017', NULL, NULL, 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(18, 'Divya Chandran', 'divya.chandran.18@example.com', '$2y$10$92k1w8x7WqvV6zZbYhF9UOe1s0m1r0F0m4Kk2m8G0m2wq3fD1P2Nu', 'freelancer', 'tamil', '+94710000018', NULL, NULL, 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(19, 'Ashan Dissanayake', 'ashan.dissanayake.19@example.com', '$2y$10$92k1w8x7WqvV6zZbYhF9UOe1s0m1r0F0m4Kk2m8G0m2wq3fD1P2Nu', 'freelancer', 'english', '+94710000019', NULL, NULL, 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(20, 'Menaka Rajapaksha', 'menaka.rajapaksha.20@example.com', '$2y$10$92k1w8x7WqvV6zZbYhF9UOe1s0m1r0F0m4Kk2m8G0m2wq3fD1P2Nu', 'freelancer', 'sinhala', '+94710000020', NULL, NULL, 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(21, 'Deepan Murugesan', 'deepan.murugesan.21@example.com', '$2y$10$92k1w8x7WqvV6zZbYhF9UOe1s0m1r0F0m4Kk2m8G0m2wq3fD1P2Nu', 'freelancer', 'tamil', '+94710000021', NULL, NULL, 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(22, 'Chathura Amarasinghe', 'chathura.amarasinghe.22@example.com', '$2y$10$92k1w8x7WqvV6zZbYhF9UOe1s0m1r0F0m4Kk2m8G0m2wq3fD1P2Nu', 'freelancer', 'english', '+94710000022', NULL, NULL, 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(23, 'Iresha Senanayake', 'iresha.senanayake.23@example.com', '$2y$10$92k1w8x7WqvV6zZbYhF9UOe1s0m1r0F0m4Kk2m8G0m2wq3fD1P2Nu', 'freelancer', 'sinhala', '+94710000023', NULL, NULL, 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(24, 'Kirushanth Pillai', 'kirushanth.pillai.24@example.com', '$2y$10$92k1w8x7WqvV6zZbYhF9UOe1s0m1r0F0m4Kk2m8G0m2wq3fD1P2Nu', 'freelancer', 'tamil', '+94710000024', NULL, NULL, 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(25, 'Malith Samarasinghe', 'malith.samarasinghe.25@example.com', '$2y$10$92k1w8x7WqvV6zZbYhF9UOe1s0m1r0F0m4Kk2m8G0m2wq3fD1P2Nu', 'freelancer', 'english', '+94710000025', NULL, NULL, 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(26, 'Hiruni Herath', 'hiruni.herath.26@example.com', '$2y$10$92k1w8x7WqvV6zZbYhF9UOe1s0m1r0F0m4Kk2m8G0m2wq3fD1P2Nu', 'freelancer', 'sinhala', '+94710000026', NULL, NULL, 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(27, 'Dinesh Gunasekara', 'dinesh.gunasekara.27@example.com', '$2y$10$92k1w8x7WqvV6zZbYhF9UOe1s0m1r0F0m4Kk2m8G0m2wq3fD1P2Nu', 'freelancer', 'tamil', '+94710000027', NULL, NULL, 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(28, 'Sachini Ekanayake', 'sachini.ekanayake.28@example.com', '$2y$10$92k1w8x7WqvV6zZbYhF9UOe1s0m1r0F0m4Kk2m8G0m2wq3fD1P2Nu', 'freelancer', 'english', '+94710000028', NULL, NULL, 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(29, 'Kasun Weerasinghe', 'kasun.weerasinghe.29@example.com', '$2y$10$92k1w8x7WqvV6zZbYhF9UOe1s0m1r0F0m4Kk2m8G0m2wq3fD1P2Nu', 'freelancer', 'sinhala', '+94710000029', NULL, NULL, 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(30, 'Piumi Jayawardena', 'piumi.jayawardena.30@example.com', '$2y$10$92k1w8x7WqvV6zZbYhF9UOe1s0m1r0F0m4Kk2m8G0m2wq3fD1P2Nu', 'freelancer', 'tamil', '+94710000030', NULL, NULL, 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(31, 'Tharuka De Silva', 'tharuka.de.silva.31@example.com', '$2y$10$92k1w8x7WqvV6zZbYhF9UOe1s0m1r0F0m4Kk2m8G0m2wq3fD1P2Nu', 'freelancer', 'english', '+94710000031', NULL, NULL, 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(32, 'Imesha Karunathilaka', 'imesha.karunathilaka.32@example.com', '$2y$10$92k1w8x7WqvV6zZbYhF9UOe1s0m1r0F0m4Kk2m8G0m2wq3fD1P2Nu', 'freelancer', 'sinhala', '+94710000032', NULL, NULL, 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(33, 'Supun Pathirana', 'supun.pathirana.33@example.com', '$2y$10$92k1w8x7WqvV6zZbYhF9UOe1s0m1r0F0m4Kk2m8G0m2wq3fD1P2Nu', 'freelancer', 'tamil', '+94710000033', NULL, NULL, 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(34, 'Nimasha Abeysekera', 'nimasha.abeysekera.34@example.com', '$2y$10$92k1w8x7WqvV6zZbYhF9UOe1s0m1r0F0m4Kk2m8G0m2wq3fD1P2Nu', 'freelancer', 'english', '+94710000034', NULL, NULL, 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(35, 'Roshan Wijesinghe', 'roshan.wijesinghe.35@example.com', '$2y$10$92k1w8x7WqvV6zZbYhF9UOe1s0m1r0F0m4Kk2m8G0m2wq3fD1P2Nu', 'freelancer', 'sinhala', '+94710000035', NULL, NULL, 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(36, 'Amaya Hettiarachchi', 'amaya.hettiarachchi.36@example.com', '$2y$10$92k1w8x7WqvV6zZbYhF9UOe1s0m1r0F0m4Kk2m8G0m2wq3fD1P2Nu', 'freelancer', 'tamil', '+94710000036', NULL, NULL, 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(37, 'Janith Dias', 'janith.dias.37@example.com', '$2y$10$92k1w8x7WqvV6zZbYhF9UOe1s0m1r0F0m4Kk2m8G0m2wq3fD1P2Nu', 'freelancer', 'english', '+94710000037', NULL, NULL, 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(38, 'Shenali Mendis', 'shenali.mendis.38@example.com', '$2y$10$92k1w8x7WqvV6zZbYhF9UOe1s0m1r0F0m4Kk2m8G0m2wq3fD1P2Nu', 'freelancer', 'sinhala', '+94710000038', NULL, NULL, 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(39, 'Pasindu Fonseka', 'pasindu.fonseka.39@example.com', '$2y$10$92k1w8x7WqvV6zZbYhF9UOe1s0m1r0F0m4Kk2m8G0m2wq3fD1P2Nu', 'freelancer', 'tamil', '+94710000039', NULL, NULL, 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(40, 'Ayesha Fernando', 'ayesha.fernando.40@example.com', '$2y$10$92k1w8x7WqvV6zZbYhF9UOe1s0m1r0F0m4Kk2m8G0m2wq3fD1P2Nu', 'freelancer', 'english', '+94710000040', NULL, NULL, 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(41, 'Ravindu Arul', 'ravindu.arul.41@example.com', '$2y$10$92k1w8x7WqvV6zZbYhF9UOe1s0m1r0F0m4Kk2m8G0m2wq3fD1P2Nu', 'freelancer', 'sinhala', '+94710000041', NULL, NULL, 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(42, 'Madhavi Nadarajah', 'madhavi.nadarajah.42@example.com', '$2y$10$92k1w8x7WqvV6zZbYhF9UOe1s0m1r0F0m4Kk2m8G0m2wq3fD1P2Nu', 'freelancer', 'tamil', '+94710000042', NULL, NULL, 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(43, 'Dulshan Thiruchelvam', 'dulshan.thiruchelvam.43@example.com', '$2y$10$92k1w8x7WqvV6zZbYhF9UOe1s0m1r0F0m4Kk2m8G0m2wq3fD1P2Nu', 'freelancer', 'english', '+94710000043', NULL, NULL, 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(44, 'Anjali Rajan', 'anjali.rajan.44@example.com', '$2y$10$92k1w8x7WqvV6zZbYhF9UOe1s0m1r0F0m4Kk2m8G0m2wq3fD1P2Nu', 'freelancer', 'sinhala', '+94710000044', NULL, NULL, 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(45, 'Sahan Kumar', 'sahan.kumar.45@example.com', '$2y$10$92k1w8x7WqvV6zZbYhF9UOe1s0m1r0F0m4Kk2m8G0m2wq3fD1P2Nu', 'freelancer', 'tamil', '+94710000045', NULL, NULL, 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(46, 'Dinuka Sivakumar', 'dinuka.sivakumar.46@example.com', '$2y$10$92k1w8x7WqvV6zZbYhF9UOe1s0m1r0F0m4Kk2m8G0m2wq3fD1P2Nu', 'freelancer', 'english', '+94710000046', NULL, NULL, 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(47, 'Isuru Manoharan', 'isuru.manoharan.47@example.com', '$2y$10$92k1w8x7WqvV6zZbYhF9UOe1s0m1r0F0m4Kk2m8G0m2wq3fD1P2Nu', 'freelancer', 'sinhala', '+94710000047', NULL, NULL, 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(48, 'Thilini Sureshkumar', 'thilini.sureshkumar.48@example.com', '$2y$10$92k1w8x7WqvV6zZbYhF9UOe1s0m1r0F0m4Kk2m8G0m2wq3fD1P2Nu', 'freelancer', 'tamil', '+94710000048', NULL, NULL, 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(49, 'Gihan Jegan', 'gihan.jegan.49@example.com', '$2y$10$92k1w8x7WqvV6zZbYhF9UOe1s0m1r0F0m4Kk2m8G0m2wq3fD1P2Nu', 'freelancer', 'english', '+94710000049', NULL, NULL, 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(50, 'Navoda Balasingam', 'navoda.balasingam.50@example.com', '$2y$10$92k1w8x7WqvV6zZbYhF9UOe1s0m1r0F0m4Kk2m8G0m2wq3fD1P2Nu', 'freelancer', 'sinhala', '+94710000050', NULL, NULL, 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(51, 'Sewmini Perera', 'sewmini.perera.51@example.com', '$2y$10$92k1w8x7WqvV6zZbYhF9UOe1s0m1r0F0m4Kk2m8G0m2wq3fD1P2Nu', 'freelancer', 'tamil', '+94710000051', NULL, NULL, 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(52, 'Akila Silva', 'akila.silva.52@example.com', '$2y$10$92k1w8x7WqvV6zZbYhF9UOe1s0m1r0F0m4Kk2m8G0m2wq3fD1P2Nu', 'freelancer', 'english', '+94710000052', NULL, NULL, 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(53, 'Harini Raj', 'harini.raj.53@example.com', '$2y$10$92k1w8x7WqvV6zZbYhF9UOe1s0m1r0F0m4Kk2m8G0m2wq3fD1P2Nu', 'freelancer', 'sinhala', '+94710000053', NULL, NULL, 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(54, 'Lakshan Fernando', 'lakshan.fernando.54@example.com', '$2y$10$92k1w8x7WqvV6zZbYhF9UOe1s0m1r0F0m4Kk2m8G0m2wq3fD1P2Nu', 'freelancer', 'tamil', '+94710000054', NULL, NULL, 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(55, 'Yuvani Wickrama', 'yuvani.wickrama.55@example.com', '$2y$10$92k1w8x7WqvV6zZbYhF9UOe1s0m1r0F0m4Kk2m8G0m2wq3fD1P2Nu', 'freelancer', 'english', '+94710000055', NULL, NULL, 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(56, 'Prabath Selvam', 'prabath.selvam.56@example.com', '$2y$10$92k1w8x7WqvV6zZbYhF9UOe1s0m1r0F0m4Kk2m8G0m2wq3fD1P2Nu', 'freelancer', 'sinhala', '+94710000056', NULL, NULL, 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(57, 'Sandaru Jayasuriya', 'sandaru.jayasuriya.57@example.com', '$2y$10$92k1w8x7WqvV6zZbYhF9UOe1s0m1r0F0m4Kk2m8G0m2wq3fD1P2Nu', 'freelancer', 'tamil', '+94710000057', NULL, NULL, 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(58, 'Nethmi Bandara', 'nethmi.bandara.58@example.com', '$2y$10$92k1w8x7WqvV6zZbYhF9UOe1s0m1r0F0m4Kk2m8G0m2wq3fD1P2Nu', 'freelancer', 'english', '+94710000058', NULL, NULL, 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(59, 'Vihanga Kumaran', 'vihanga.kumaran.59@example.com', '$2y$10$92k1w8x7WqvV6zZbYhF9UOe1s0m1r0F0m4Kk2m8G0m2wq3fD1P2Nu', 'freelancer', 'sinhala', '+94710000059', NULL, NULL, 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(60, 'Rashmi Gunawardena', 'rashmi.gunawardena.60@example.com', '$2y$10$92k1w8x7WqvV6zZbYhF9UOe1s0m1r0F0m4Kk2m8G0m2wq3fD1P2Nu', 'freelancer', 'tamil', '+94710000060', NULL, NULL, 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(61, 'Chathurika Rathnayake', 'chathurika.rathnayake.61@example.com', '$2y$10$92k1w8x7WqvV6zZbYhF9UOe1s0m1r0F0m4Kk2m8G0m2wq3fD1P2Nu', 'freelancer', 'english', '+94710000061', NULL, NULL, 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(62, 'Sajith Kandiah', 'sajith.kandiah.62@example.com', '$2y$10$92k1w8x7WqvV6zZbYhF9UOe1s0m1r0F0m4Kk2m8G0m2wq3fD1P2Nu', 'freelancer', 'sinhala', '+94710000062', NULL, NULL, 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(63, 'Udara Peiris', 'udara.peiris.63@example.com', '$2y$10$92k1w8x7WqvV6zZbYhF9UOe1s0m1r0F0m4Kk2m8G0m2wq3fD1P2Nu', 'freelancer', 'tamil', '+94710000063', NULL, NULL, 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(64, 'Heshan Madushan', 'heshan.madushan.64@example.com', '$2y$10$92k1w8x7WqvV6zZbYhF9UOe1s0m1r0F0m4Kk2m8G0m2wq3fD1P2Nu', 'freelancer', 'english', '+94710000064', NULL, NULL, 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(65, 'Nadeeka Ramasamy', 'nadeeka.ramasamy.65@example.com', '$2y$10$92k1w8x7WqvV6zZbYhF9UOe1s0m1r0F0m4Kk2m8G0m2wq3fD1P2Nu', 'freelancer', 'sinhala', '+94710000065', NULL, NULL, 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(66, 'Rukshan Wijeratne', 'rukshan.wijeratne.66@example.com', '$2y$10$92k1w8x7WqvV6zZbYhF9UOe1s0m1r0F0m4Kk2m8G0m2wq3fD1P2Nu', 'freelancer', 'tamil', '+94710000066', NULL, NULL, 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(67, 'Shalini Karunaratne', 'shalini.karunaratne.67@example.com', '$2y$10$92k1w8x7WqvV6zZbYhF9UOe1s0m1r0F0m4Kk2m8G0m2wq3fD1P2Nu', 'freelancer', 'english', '+94710000067', NULL, NULL, 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(68, 'Arjun Chandran', 'arjun.chandran.68@example.com', '$2y$10$92k1w8x7WqvV6zZbYhF9UOe1s0m1r0F0m4Kk2m8G0m2wq3fD1P2Nu', 'freelancer', 'sinhala', '+94710000068', NULL, NULL, 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(69, 'Fathima Dissanayake', 'fathima.dissanayake.69@example.com', '$2y$10$92k1w8x7WqvV6zZbYhF9UOe1s0m1r0F0m4Kk2m8G0m2wq3fD1P2Nu', 'freelancer', 'tamil', '+94710000069', NULL, NULL, 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(70, 'Mohan Rajapaksha', 'mohan.rajapaksha.70@example.com', '$2y$10$92k1w8x7WqvV6zZbYhF9UOe1s0m1r0F0m4Kk2m8G0m2wq3fD1P2Nu', 'freelancer', 'english', '+94710000070', NULL, NULL, 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(71, 'Sivani Murugesan', 'sivani.murugesan.71@example.com', '$2y$10$92k1w8x7WqvV6zZbYhF9UOe1s0m1r0F0m4Kk2m8G0m2wq3fD1P2Nu', 'freelancer', 'sinhala', '+94710000071', NULL, NULL, 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(72, 'Tharshan Amarasinghe', 'tharshan.amarasinghe.72@example.com', '$2y$10$92k1w8x7WqvV6zZbYhF9UOe1s0m1r0F0m4Kk2m8G0m2wq3fD1P2Nu', 'freelancer', 'tamil', '+94710000072', NULL, NULL, 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(73, 'Vimal Senanayake', 'vimal.senanayake.73@example.com', '$2y$10$92k1w8x7WqvV6zZbYhF9UOe1s0m1r0F0m4Kk2m8G0m2wq3fD1P2Nu', 'freelancer', 'english', '+94710000073', NULL, NULL, 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(74, 'Keshan Pillai', 'keshan.pillai.74@example.com', '$2y$10$92k1w8x7WqvV6zZbYhF9UOe1s0m1r0F0m4Kk2m8G0m2wq3fD1P2Nu', 'freelancer', 'sinhala', '+94710000074', NULL, NULL, 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(75, 'Rithika Samarasinghe', 'rithika.samarasinghe.75@example.com', '$2y$10$92k1w8x7WqvV6zZbYhF9UOe1s0m1r0F0m4Kk2m8G0m2wq3fD1P2Nu', 'freelancer', 'tamil', '+94710000075', NULL, NULL, 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(76, 'Pradeep Herath', 'pradeep.herath.76@example.com', '$2y$10$92k1w8x7WqvV6zZbYhF9UOe1s0m1r0F0m4Kk2m8G0m2wq3fD1P2Nu', 'freelancer', 'english', '+94710000076', NULL, NULL, 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(77, 'Nirasha Gunasekara', 'nirasha.gunasekara.77@example.com', '$2y$10$92k1w8x7WqvV6zZbYhF9UOe1s0m1r0F0m4Kk2m8G0m2wq3fD1P2Nu', 'freelancer', 'sinhala', '+94710000077', NULL, NULL, 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(78, 'Amith Ekanayake', 'amith.ekanayake.78@example.com', '$2y$10$92k1w8x7WqvV6zZbYhF9UOe1s0m1r0F0m4Kk2m8G0m2wq3fD1P2Nu', 'freelancer', 'tamil', '+94710000078', NULL, NULL, 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(79, 'Bhagya Weerasinghe', 'bhagya.weerasinghe.79@example.com', '$2y$10$92k1w8x7WqvV6zZbYhF9UOe1s0m1r0F0m4Kk2m8G0m2wq3fD1P2Nu', 'freelancer', 'english', '+94710000079', NULL, NULL, 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(80, 'Ramesh Jayawardena', 'ramesh.jayawardena.80@example.com', '$2y$10$92k1w8x7WqvV6zZbYhF9UOe1s0m1r0F0m4Kk2m8G0m2wq3fD1P2Nu', 'freelancer', 'sinhala', '+94710000080', NULL, NULL, 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(81, 'Tharindu2 De Silva', 'tharindu2.de.silva.81@example.com', '$2y$10$92k1w8x7WqvV6zZbYhF9UOe1s0m1r0F0m4Kk2m8G0m2wq3fD1P2Nu', 'freelancer', 'tamil', '+94710000081', NULL, NULL, 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(82, 'Gayani Karunathilaka', 'gayani.karunathilaka.82@example.com', '$2y$10$92k1w8x7WqvV6zZbYhF9UOe1s0m1r0F0m4Kk2m8G0m2wq3fD1P2Nu', 'freelancer', 'english', '+94710000082', NULL, NULL, 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(83, 'Chandima Pathirana', 'chandima.pathirana.83@example.com', '$2y$10$92k1w8x7WqvV6zZbYhF9UOe1s0m1r0F0m4Kk2m8G0m2wq3fD1P2Nu', 'freelancer', 'sinhala', '+94710000083', NULL, NULL, 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(84, 'Maneesha Abeysekera', 'maneesha.abeysekera.84@example.com', '$2y$10$92k1w8x7WqvV6zZbYhF9UOe1s0m1r0F0m4Kk2m8G0m2wq3fD1P2Nu', 'freelancer', 'tamil', '+94710000084', NULL, NULL, 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(85, 'Kusal Wijesinghe', 'kusal.wijesinghe.85@example.com', '$2y$10$92k1w8x7WqvV6zZbYhF9UOe1s0m1r0F0m4Kk2m8G0m2wq3fD1P2Nu', 'freelancer', 'english', '+94710000085', NULL, NULL, 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(86, 'Nuwangi Hettiarachchi', 'nuwangi.hettiarachchi.86@example.com', '$2y$10$92k1w8x7WqvV6zZbYhF9UOe1s0m1r0F0m4Kk2m8G0m2wq3fD1P2Nu', 'freelancer', 'sinhala', '+94710000086', NULL, NULL, 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(87, 'Praveen Dias', 'praveen.dias.87@example.com', '$2y$10$92k1w8x7WqvV6zZbYhF9UOe1s0m1r0F0m4Kk2m8G0m2wq3fD1P2Nu', 'freelancer', 'tamil', '+94710000087', NULL, NULL, 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(88, 'Ishani Mendis', 'ishani.mendis.88@example.com', '$2y$10$92k1w8x7WqvV6zZbYhF9UOe1s0m1r0F0m4Kk2m8G0m2wq3fD1P2Nu', 'freelancer', 'english', '+94710000088', NULL, NULL, 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(89, 'Roshan2 Fonseka', 'roshan2.fonseka.89@example.com', '$2y$10$92k1w8x7WqvV6zZbYhF9UOe1s0m1r0F0m4Kk2m8G0m2wq3fD1P2Nu', 'freelancer', 'sinhala', '+94710000089', NULL, NULL, 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(90, 'Tharushi Fernando', 'tharushi.fernando.90@example.com', '$2y$10$92k1w8x7WqvV6zZbYhF9UOe1s0m1r0F0m4Kk2m8G0m2wq3fD1P2Nu', 'freelancer', 'tamil', '+94710000090', NULL, NULL, 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(91, 'Dhanush Arul', 'dhanush.arul.91@example.com', '$2y$10$92k1w8x7WqvV6zZbYhF9UOe1s0m1r0F0m4Kk2m8G0m2wq3fD1P2Nu', 'freelancer', 'english', '+94710000091', NULL, NULL, 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(92, 'Sithum Nadarajah', 'sithum.nadarajah.92@example.com', '$2y$10$92k1w8x7WqvV6zZbYhF9UOe1s0m1r0F0m4Kk2m8G0m2wq3fD1P2Nu', 'freelancer', 'sinhala', '+94710000092', NULL, NULL, 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(93, 'Kavisha Thiruchelvam', 'kavisha.thiruchelvam.93@example.com', '$2y$10$92k1w8x7WqvV6zZbYhF9UOe1s0m1r0F0m4Kk2m8G0m2wq3fD1P2Nu', 'freelancer', 'tamil', '+94710000093', NULL, NULL, 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(94, 'Dineth Rajan', 'dineth.rajan.94@example.com', '$2y$10$92k1w8x7WqvV6zZbYhF9UOe1s0m1r0F0m4Kk2m8G0m2wq3fD1P2Nu', 'freelancer', 'english', '+94710000094', NULL, NULL, 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(95, 'Mihiri Kumar', 'mihiri.kumar.95@example.com', '$2y$10$92k1w8x7WqvV6zZbYhF9UOe1s0m1r0F0m4Kk2m8G0m2wq3fD1P2Nu', 'freelancer', 'sinhala', '+94710000095', NULL, NULL, 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(96, 'Sanjaya Sivakumar', 'sanjaya.sivakumar.96@example.com', '$2y$10$92k1w8x7WqvV6zZbYhF9UOe1s0m1r0F0m4Kk2m8G0m2wq3fD1P2Nu', 'freelancer', 'tamil', '+94710000096', NULL, NULL, 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(97, 'Yasiru Manoharan', 'yasiru.manoharan.97@example.com', '$2y$10$92k1w8x7WqvV6zZbYhF9UOe1s0m1r0F0m4Kk2m8G0m2wq3fD1P2Nu', 'freelancer', 'english', '+94710000097', NULL, NULL, 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(98, 'Lakmali Sureshkumar', 'lakmali.sureshkumar.98@example.com', '$2y$10$92k1w8x7WqvV6zZbYhF9UOe1s0m1r0F0m4Kk2m8G0m2wq3fD1P2Nu', 'freelancer', 'sinhala', '+94710000098', NULL, NULL, 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(99, 'Rashan Jegan', 'rashan.jegan.99@example.com', '$2y$10$92k1w8x7WqvV6zZbYhF9UOe1s0m1r0F0m4Kk2m8G0m2wq3fD1P2Nu', 'freelancer', 'tamil', '+94710000099', NULL, NULL, 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(100, 'Oshini Balasingam', 'oshini.balasingam.100@example.com', '$2y$10$92k1w8x7WqvV6zZbYhF9UOe1s0m1r0F0m4Kk2m8G0m2wq3fD1P2Nu', 'freelancer', 'english', '+94710000100', NULL, NULL, 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(101, 'Nadeesha Perera', 'nadeesha.perera.101@example.com', '$2y$10$92k1w8x7WqvV6zZbYhF9UOe1s0m1r0F0m4Kk2m8G0m2wq3fD1P2Nu', 'freelancer', 'english', '+94710000101', NULL, NULL, 1, '2026-09-09 18:24:20', '2026-09-09 18:24:20'),
(102, 'Client 01', 'Client01@gmail.com', '$2y$10$YftS.q/T9wY4Mf6wM.urue40BbqqYm1Ov3ITcPV9mjweWANijru8K', 'client', 'english', NULL, '2026-09-09 18:50:10', NULL, 1, '2026-09-09 18:49:23', '2026-09-09 18:50:10');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`booking_id`),
  ADD UNIQUE KEY `uniq_client_service_active` (`active_key`),
  ADD KEY `idx_bookings_service` (`service_id`),
  ADD KEY `idx_bookings_client` (`client_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`category_id`),
  ADD UNIQUE KEY `uq_category_key` (`category_key`);

--
-- Indexes for table `freelancer_profiles`
--
ALTER TABLE `freelancer_profiles`
  ADD PRIMARY KEY (`profile_id`),
  ADD UNIQUE KEY `uq_freelancer_user` (`user_id`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`message_id`),
  ADD KEY `idx_messages_sender` (`sender_id`),
  ADD KEY `idx_messages_receiver` (`receiver_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `idx_notifications_user` (`user_id`,`is_read`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`payment_id`),
  ADD UNIQUE KEY `uq_payment_booking` (`booking_id`);

--
-- Indexes for table `refund_requests`
--
ALTER TABLE `refund_requests`
  ADD PRIMARY KEY (`refund_id`),
  ADD KEY `idx_refund_booking` (`booking_id`),
  ADD KEY `idx_refund_client` (`client_id`),
  ADD KEY `idx_refund_freelancer` (`freelancer_id`),
  ADD KEY `fk_refund_payment` (`payment_id`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`review_id`),
  ADD UNIQUE KEY `uq_review_booking` (`booking_id`);

--
-- Indexes for table `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`service_id`),
  ADD KEY `idx_services_profile` (`profile_id`),
  ADD KEY `idx_services_category` (`category_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `uq_users_email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `booking_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `category_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `freelancer_profiles`
--
ALTER TABLE `freelancer_profiles`
  MODIFY `profile_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=101;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `message_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `payment_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `refund_requests`
--
ALTER TABLE `refund_requests`
  MODIFY `refund_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `review_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `services`
--
ALTER TABLE `services`
  MODIFY `service_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=101;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=103;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `fk_bookings_client` FOREIGN KEY (`client_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `fk_bookings_service` FOREIGN KEY (`service_id`) REFERENCES `services` (`service_id`);

--
-- Constraints for table `freelancer_profiles`
--
ALTER TABLE `freelancer_profiles`
  ADD CONSTRAINT `fk_freelancer_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `fk_messages_receiver` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_messages_sender` FOREIGN KEY (`sender_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `fk_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `fk_payments_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`) ON DELETE CASCADE;

--
-- Constraints for table `refund_requests`
--
ALTER TABLE `refund_requests`
  ADD CONSTRAINT `fk_refund_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_refund_client` FOREIGN KEY (`client_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_refund_freelancer` FOREIGN KEY (`freelancer_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_refund_payment` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`payment_id`) ON DELETE CASCADE;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `fk_reviews_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`) ON DELETE CASCADE;

--
-- Constraints for table `services`
--
ALTER TABLE `services`
  ADD CONSTRAINT `fk_services_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`),
  ADD CONSTRAINT `fk_services_profile` FOREIGN KEY (`profile_id`) REFERENCES `freelancer_profiles` (`profile_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
