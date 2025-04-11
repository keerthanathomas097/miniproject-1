-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 11, 2025 at 05:06 AM
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
-- Database: `minipro1`
--

-- --------------------------------------------------------

--
-- Table structure for table `tbl_carts`
--

CREATE TABLE `tbl_carts` (
  `cart_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `status` enum('active','completed','abandoned') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_carts`
--

INSERT INTO `tbl_carts` (`cart_id`, `user_id`, `status`, `created_at`) VALUES
(1, 1, 'active', '2025-03-19 08:02:31'),
(2, 4, 'active', '2025-04-02 09:31:48');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_cart_items`
--

CREATE TABLE `tbl_cart_items` (
  `cart_item_id` int(11) NOT NULL,
  `cart_id` int(11) NOT NULL,
  `outfit_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_cart_items`
--

INSERT INTO `tbl_cart_items` (`cart_item_id`, `cart_id`, `outfit_id`, `created_at`) VALUES
(9, 1, 16, '2025-03-27 05:17:05'),
(10, 1, 17, '2025-03-27 05:45:51'),
(12, 1, 11, '2025-04-07 18:24:57'),
(13, 1, 14, '2025-04-07 18:32:20');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_category`
--

CREATE TABLE `tbl_category` (
  `id` int(11) NOT NULL,
  `category_name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_category`
--

INSERT INTO `tbl_category` (`id`, `category_name`) VALUES
(1, 'Type'),
(2, 'Brand'),
(3, 'Size'),
(4, 'Occasion'),
(5, 'Gender'),
(6, 'Price');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_description`
--

CREATE TABLE `tbl_description` (
  `id` int(11) NOT NULL,
  `description_text` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_description`
--

INSERT INTO `tbl_description` (`id`, `description_text`) VALUES
(20, 'Pista Kulfi Lahenga'),
(21, 'Naan Khatai Lahenga'),
(22, 'Red Son Patti Saree'),
(23, 'Naan Khatai Lahenga'),
(24, 'Red Son Patti Saree'),
(25, 'Rose Pink Lahenga'),
(26, 'Aam Panna Green Regal Lahenga'),
(27, 'Classic Mughal Jaal Embroidered Saree'),
(28, 'Rust Masakali  Fusion Lahenga'),
(29, 'Magentha Haat Phool Saree'),
(30, 'Navy Blue Polyster Emboidered Tuxedo Set'),
(31, 'Blue Dupion Kalamkari lahenga'),
(32, 'Regal Maroon Raw Silk Lahenga'),
(33, 'Ice Blue Pink Ogre Crystal Embroidered Lehenga '),
(34, 'Beaded Applique Embroidered Tuxedo Suite'),
(35, 'Red Raw Silk Embroidered Anarkali Set'),
(36, 'Mauve Net Thread & Sequins Work Saree Set'),
(37, 'Navy Blue Raw Silk Indowestern Suite'),
(38, 'Aqua and Lilac Mirror Net Lehenga Set'),
(39, 'Tulle powder Pink and Blue Chiffon Lahenga'),
(40, 'Midnight Blue Georgette Viscossee Sahara Set'),
(41, 'Cream Organza Indo Western Embroidered Suite '),
(42, 'Rose Gold Raw Silk Embroidered Indo Western Suite Set'),
(43, 'Lilac Organza Sequens Embroidered Draped Saree Set'),
(44, 'Green Dupion Printed Kalamkari Lahenga Set'),
(45, 'Pastel Blue and Turqouise Seqins Lahenga Set'),
(46, 'grey Organza and Lurex Chiffon Embroidered Anarkali Set'),
(47, 'Floral Sequence Nude Embroidered Lahenga Set'),
(48, 'Salmon Pink Embroidered Bridal lahenga'),
(49, 'Mustard Banarasi Silk Saree'),
(50, 'Mint Green Raw Silk Indo Western Suit');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_measurements`
--

CREATE TABLE `tbl_measurements` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `height` float DEFAULT NULL,
  `shoulder` float DEFAULT NULL,
  `bust` float DEFAULT NULL,
  `waist` float DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `outfit_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_measurements`
--

INSERT INTO `tbl_measurements` (`id`, `user_id`, `height`, `shoulder`, `bust`, `waist`, `start_date`, `end_date`, `outfit_id`) VALUES
(1, 1, 67, 36, 34, 28, '0000-00-00', '0000-00-00', 8),
(2, 1, 69, 36, 34, 28, '0000-00-00', '0000-00-00', 9),
(3, 1, 67, 36, 34, 30, '2025-03-26', '2025-03-30', 10),
(4, 1, 67, 36, 34, 30, '2025-03-27', '2025-03-31', 10),
(6, 1, 67, 36, 34, 30, '2025-04-07', '2025-04-11', 9),
(7, 1, 70, 34, 33, 23, '0000-00-00', '0000-00-00', 11),
(10, 1, 67, 34, 32, 29, '2025-04-13', '2025-04-19', 9),
(11, 2, 67, 36, 34, 27, '2025-03-29', '2025-04-02', 12),
(12, 2, 67, 34, 32, 27, '2025-04-08', '2025-04-12', 12),
(13, 1, 64, 36, 34, 29, '2025-04-09', '2025-04-13', 12),
(14, 1, 67, 36, 34, 29, '2025-04-16', '2025-04-20', 12),
(15, 1, 65, 36, 34, 29, '2025-04-03', '2025-04-07', 12),
(16, 1, 60, 34, 30, 25, '2025-03-29', '2025-04-02', 12),
(17, 1, 63, 36, 32, 27, '2025-03-29', '2025-04-02', 12),
(18, 1, 65, 36, 34, 30, '2025-03-29', '2025-04-02', 12),
(19, 1, 65, 36, 35, 30, '2025-04-14', '2025-04-18', 12),
(20, 1, 67, 36, 34, 27, '2025-04-08', '2025-04-12', 12),
(21, 1, 67, 36, 34, 28, '2025-03-26', '2025-03-30', 12),
(22, 1, 67, 36, 34, 29, '2025-03-27', '2025-03-31', 12),
(23, 1, 65, 36, 34, 29, '2025-03-29', '2025-04-02', 12),
(24, 1, 63, 36, 34, 29, '2025-03-29', '2025-04-02', 12),
(25, 1, 67, 36, 34, 30, '2025-03-29', '2025-04-02', 12),
(26, 1, 64, 36, 35, 30, '2025-04-02', '2025-04-06', 13),
(27, 1, 65, 36, 34, 30, '2025-04-16', '2025-04-20', 13),
(28, 1, 65, 36, 34, 27, '2025-04-15', '2025-04-19', 13),
(29, 1, 65, 34, 32, 27, '2025-04-09', '2025-04-13', 13),
(30, 1, 65, 38, 34, 29, '2025-04-09', '2025-04-13', 13),
(31, 1, 63, 35, 32, 26, '2025-04-15', '2025-04-19', 13),
(32, 1, 62, 36, 34, 27, '2025-03-30', '2025-04-03', 13),
(33, 1, 67, 34, 36, 25, '2025-04-18', '2025-04-22', 13),
(34, 1, 73, 36, 33, 23, '2025-04-07', '2025-04-11', 14),
(35, 1, 73, 36, 33, 23, '2025-04-07', '2025-04-11', 14),
(36, 1, 73, 36, 33, 23, '2025-04-07', '2025-04-11', 14),
(37, 1, 60, 36, 34, 26, '2025-03-30', '2025-04-03', 15),
(38, 1, 72, 40, 38, 30, '2025-04-02', '2025-04-06', 16),
(39, 1, 67, 36, 34, 27, '2025-03-29', '2025-04-04', 17),
(40, 1, 68, 35, 34, 29, '2025-03-29', '2025-04-02', 18),
(41, 3, 67, 34, 36, 29, '2025-03-29', '2025-04-02', 18),
(42, 1, 60, 37, 34, 29, '2025-03-30', '2025-04-03', 15),
(43, 1, 70, 36, 34, 27, '2025-03-30', '2025-04-01', 20),
(44, 1, 67, 36, 32, 27, '2025-03-31', '2025-04-02', 21),
(45, 4, 70, 36, 34, 27, '2025-03-31', '2025-04-02', 22),
(46, 2, 75, 35, 33, 23, '2025-04-08', '2025-04-12', 9),
(47, 1, 74, 38, 34, 22, '2025-04-07', '2025-04-11', 26),
(48, 1, 78, 36, 33, 23, '2025-04-21', '2025-04-25', 27),
(49, 1, 78, 38, 36, 25, '2025-04-09', '2025-04-13', 28),
(50, 1, 80, 37, 34, 29, '2025-04-07', '2025-04-09', 29),
(51, 1, 60, 36, 34, 27, '2025-04-09', '2025-04-13', 30),
(52, 1, 72, 36, 33, 27, '2025-04-08', '2025-04-12', 34),
(53, 1, 70, 36, 30, 23, '2025-04-09', '2025-04-13', 36),
(54, 1, 70, 36, 34, 23, '2025-04-21', '2025-04-25', 37),
(55, 1, 71, 36, 34, 30, '2025-04-09', '2025-04-13', 39);

-- --------------------------------------------------------

--
-- Table structure for table `tbl_orders`
--

CREATE TABLE `tbl_orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `outfit_id` int(11) NOT NULL,
  `order_reference` varchar(50) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `rental_rate` decimal(10,2) DEFAULT 0.00,
  `security_deposit` decimal(10,2) DEFAULT 0.00,
  `payment_method` varchar(20) NOT NULL,
  `order_status` varchar(20) NOT NULL,
  `payment_status` varchar(20) NOT NULL DEFAULT 'PENDING',
  `razorpay_order_id` varchar(100) DEFAULT NULL,
  `razorpay_payment_id` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_orders`
--

INSERT INTO `tbl_orders` (`id`, `user_id`, `outfit_id`, `order_reference`, `amount`, `rental_rate`, `security_deposit`, `payment_method`, `order_status`, `payment_status`, `razorpay_order_id`, `razorpay_payment_id`, `notes`, `created_at`, `updated_at`) VALUES
(2, 1, 12, 'CLV1A184FDE', 8919.00, 0.00, 0.00, 'credit_card', 'CONFIRMED', 'PENDING', NULL, NULL, ' [Security deposit refunded on 2025-03-31 03:25:03]', '2025-03-23 14:37:12', '2025-03-23 14:37:12'),
(12, 1, 16, 'ORD-5A98D5B5', 13279.00, 6000.00, 6000.00, 'credit_card', 'CONFIRMED', 'PAID', NULL, NULL, NULL, '2025-03-24 04:55:16', '2025-03-24 04:55:16'),
(14, 1, 17, 'ORD-11C0DD04', 15459.00, 7000.00, 7000.00, 'credit_card', 'CONFIRMED', 'PAID', NULL, NULL, NULL, '2025-03-27 07:03:25', '2025-03-27 07:03:25'),
(15, 3, 18, 'ORD-C9B6ED1B', 15459.00, 7000.00, 7000.00, 'credit_card', 'CONFIRMED', 'PAID', NULL, NULL, NULL, '2025-03-28 03:59:30', '2025-03-28 03:59:30'),
(17, 1, 15, 'ORD-7DA53A47', 11099.00, 5000.00, 5000.00, 'credit_card', 'CONFIRMED', 'PAID', NULL, NULL, ' [Security deposit refunded on 2025-04-09 02:54:23]', '2025-03-31 01:27:42', '2025-03-31 01:27:42'),
(21, 1, 20, 'ORD-D987C619', 13279.00, 6000.00, 6000.00, 'credit_card', 'CONFIRMED', 'PAID', NULL, NULL, ' [Security deposit refunded on 2025-04-01 07:06:15]', '2025-03-31 16:35:00', '2025-03-31 16:35:00'),
(23, 1, 21, 'ORD-AFD85234', 7829.00, 3500.00, 3500.00, 'credit_card', 'CONFIRMED', 'PAID', NULL, NULL, NULL, '2025-03-31 17:31:39', '2025-03-31 17:31:39'),
(25, 4, 22, 'ORD-287D987F', 130999.00, 60000.00, 60000.00, 'credit_card', 'CONFIRMED', 'PAID', NULL, NULL, NULL, '2025-04-01 07:31:59', '2025-04-01 07:31:59'),
(27, 1, 11, 'ORD-4F90A4CC', 65599.00, 30000.00, 30000.00, 'credit_card', 'CONFIRMED', 'PAID', NULL, NULL, NULL, '2025-04-08 18:51:09', '2025-04-08 18:51:09'),
(29, 1, 14, 'ORD-C6C4B7BA', 9028.00, 4050.00, 4050.00, 'credit_card', 'CONFIRMED', 'PAID', NULL, NULL, NULL, '2025-04-08 19:02:47', '2025-04-08 19:02:47'),
(36, 2, 9, 'ORD-801DE8E4', 15459.00, 7000.00, 7000.00, 'credit_card', 'CONFIRMED', 'PAID', NULL, NULL, NULL, '2025-04-08 19:14:27', '2025-04-08 19:14:27'),
(38, 1, 26, 'ORD-28FEC122', 15459.00, 7000.00, 7000.00, 'credit_card', 'CONFIRMED', 'PAID', NULL, NULL, NULL, '2025-04-08 19:33:28', '2025-04-08 19:33:28'),
(39, 1, 27, 'ORD-65A3A720', 11099.00, 5000.00, 5000.00, 'credit_card', 'CONFIRMED', 'PAID', NULL, NULL, NULL, '2025-04-08 19:48:25', '2025-04-08 19:48:25'),
(44, 1, 28, 'ORD-52EC7D8A', 13279.00, 6000.00, 6000.00, 'credit_card', 'CONFIRMED', 'PAID', NULL, NULL, NULL, '2025-04-08 20:10:34', '2025-04-08 20:10:34'),
(48, 1, 29, 'ORD-67B44872', 16549.00, 7500.00, 7500.00, 'credit_card', 'CONFIRMED', 'PAID', NULL, NULL, NULL, '2025-04-08 20:18:25', '2025-04-08 20:18:25'),
(49, 1, 30, 'ORD-FCCE6788', 13279.00, 6000.00, 6000.00, 'credit_card', 'CONFIRMED', 'PAID', NULL, NULL, NULL, '2025-04-08 20:50:38', '2025-04-08 20:50:38'),
(50, 1, 30, 'ORD-8928AF93', 13279.00, 6000.00, 6000.00, 'credit_card', 'CONFIRMED', 'PAID', NULL, NULL, NULL, '2025-04-08 20:50:39', '2025-04-08 20:50:39'),
(51, 1, 34, 'ORD-C5DAEB25', 7829.00, 3500.00, 3500.00, 'credit_card', 'CONFIRMED', 'PAID', NULL, NULL, NULL, '2025-04-09 00:21:04', '2025-04-09 00:21:04'),
(52, 1, 34, 'ORD-9420C3A2', 7829.00, 3500.00, 3500.00, 'credit_card', 'CONFIRMED', 'PAID', NULL, NULL, NULL, '2025-04-09 00:21:04', '2025-04-09 00:21:04'),
(53, 1, 36, 'ORD-60091796', 13279.00, 6000.00, 6000.00, 'credit_card', 'CONFIRMED', 'PAID', NULL, NULL, NULL, '2025-04-09 01:05:18', '2025-04-09 01:05:18'),
(54, 1, 36, 'ORD-E0027942', 13279.00, 6000.00, 6000.00, 'credit_card', 'CONFIRMED', 'PAID', NULL, NULL, NULL, '2025-04-09 01:05:19', '2025-04-09 01:05:19'),
(70, 1, 37, 'ORD-79FA8364', 10009.00, 4500.00, 4500.00, 'credit_card', 'CONFIRMED', 'PAID', NULL, NULL, NULL, '2025-04-09 01:22:00', '2025-04-09 01:22:00'),
(71, 1, 37, 'ORD-6E836071', 10009.00, 4500.00, 4500.00, 'credit_card', 'CONFIRMED', 'PAID', NULL, NULL, NULL, '2025-04-09 01:22:01', '2025-04-09 01:22:01'),
(72, 1, 39, 'ORD-D04F4497', 19819.00, 9000.00, 9000.00, 'credit_card', 'CONFIRMED', 'PAID', NULL, NULL, NULL, '2025-04-09 02:52:09', '2025-04-09 02:52:09'),
(73, 1, 39, 'ORD-377D7541', 19819.00, 9000.00, 9000.00, 'credit_card', 'CONFIRMED', 'PAID', NULL, NULL, NULL, '2025-04-09 02:52:09', '2025-04-09 02:52:09');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_orders_backup`
--

CREATE TABLE `tbl_orders_backup` (
  `id` int(11) NOT NULL DEFAULT 0,
  `user_id` int(11) NOT NULL,
  `outfit_id` int(11) NOT NULL,
  `order_reference` varchar(50) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `rental_rate` decimal(10,2) DEFAULT 0.00,
  `security_deposit` decimal(10,2) DEFAULT 0.00,
  `payment_method` varchar(20) NOT NULL,
  `order_status` varchar(20) NOT NULL,
  `payment_status` varchar(20) NOT NULL DEFAULT 'PENDING',
  `razorpay_order_id` varchar(100) DEFAULT NULL,
  `razorpay_payment_id` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_orders_backup`
--

INSERT INTO `tbl_orders_backup` (`id`, `user_id`, `outfit_id`, `order_reference`, `amount`, `rental_rate`, `security_deposit`, `payment_method`, `order_status`, `payment_status`, `razorpay_order_id`, `razorpay_payment_id`, `notes`, `created_at`, `updated_at`) VALUES
(2, 1, 12, 'CLV1A184FDE', 8919.00, 0.00, 0.00, 'credit_card', 'CONFIRMED', 'PENDING', NULL, NULL, ' [Security deposit refunded on 2025-03-31 03:25:03]', '2025-03-23 14:37:12', '2025-03-23 14:37:12'),
(10, 1, 15, 'DIR-898F9E9D', 11099.00, 0.00, 0.00, 'credit_card', 'PENDING', 'PENDING', NULL, NULL, NULL, '2025-03-24 04:31:46', '2025-03-24 04:31:46'),
(12, 1, 16, 'ORD-5A98D5B5', 13279.00, 6000.00, 6000.00, 'credit_card', 'CONFIRMED', 'PAID', NULL, NULL, NULL, '2025-03-24 04:55:16', '2025-03-24 04:55:16'),
(14, 1, 17, 'ORD-11C0DD04', 15459.00, 7000.00, 7000.00, 'credit_card', 'CONFIRMED', 'PAID', NULL, NULL, NULL, '2025-03-27 07:03:25', '2025-03-27 07:03:25'),
(15, 3, 18, 'ORD-C9B6ED1B', 15459.00, 7000.00, 7000.00, 'credit_card', 'CONFIRMED', 'PAID', NULL, NULL, NULL, '2025-03-28 03:59:30', '2025-03-28 03:59:30'),
(17, 1, 15, 'ORD-7DA53A47', 11099.00, 5000.00, 5000.00, 'credit_card', 'CONFIRMED', 'PAID', NULL, NULL, NULL, '2025-03-31 01:27:42', '2025-03-31 01:27:42'),
(21, 1, 20, 'ORD-D987C619', 13279.00, 6000.00, 6000.00, 'credit_card', 'CONFIRMED', 'PAID', NULL, NULL, ' [Security deposit refunded on 2025-04-01 07:06:15]', '2025-03-31 16:35:00', '2025-03-31 16:35:00'),
(23, 1, 21, 'ORD-AFD85234', 7829.00, 3500.00, 3500.00, 'credit_card', 'CONFIRMED', 'PAID', NULL, NULL, NULL, '2025-03-31 17:31:39', '2025-03-31 17:31:39'),
(25, 4, 22, 'ORD-287D987F', 130999.00, 60000.00, 60000.00, 'credit_card', 'CONFIRMED', 'PAID', NULL, NULL, NULL, '2025-04-01 07:31:59', '2025-04-01 07:31:59'),
(27, 1, 11, 'ORD-4F90A4CC', 65599.00, 30000.00, 30000.00, 'credit_card', 'CONFIRMED', 'PAID', NULL, NULL, NULL, '2025-04-08 18:51:09', '2025-04-08 18:51:09'),
(29, 1, 14, 'ORD-C6C4B7BA', 9028.00, 4050.00, 4050.00, 'credit_card', 'CONFIRMED', 'PAID', NULL, NULL, NULL, '2025-04-08 19:02:47', '2025-04-08 19:02:47'),
(31, 2, 9, 'ORD-54BDB418', 15459.00, 7000.00, 7000.00, 'credit_card', 'CONFIRMED', 'PAID', NULL, NULL, NULL, '2025-04-08 19:13:39', '2025-04-08 19:13:39'),
(33, 2, 9, 'ORD-63E7E978', 15459.00, 7000.00, 7000.00, 'credit_card', 'CONFIRMED', 'PAID', NULL, NULL, NULL, '2025-04-08 19:13:56', '2025-04-08 19:13:56'),
(35, 2, 9, 'ORD-540373CF', 15459.00, 7000.00, 7000.00, 'credit_card', 'CONFIRMED', 'PAID', NULL, NULL, NULL, '2025-04-08 19:14:26', '2025-04-08 19:14:26'),
(36, 2, 9, 'ORD-801DE8E4', 15459.00, 7000.00, 7000.00, 'credit_card', 'CONFIRMED', 'PAID', NULL, NULL, NULL, '2025-04-08 19:14:27', '2025-04-08 19:14:27'),
(37, 1, 26, 'ORD-8E2E772F', 15459.00, 7000.00, 7000.00, 'credit_card', 'CONFIRMED', 'PAID', NULL, NULL, NULL, '2025-04-08 19:33:27', '2025-04-08 19:33:27'),
(38, 1, 26, 'ORD-28FEC122', 15459.00, 7000.00, 7000.00, 'credit_card', 'CONFIRMED', 'PAID', NULL, NULL, NULL, '2025-04-08 19:33:28', '2025-04-08 19:33:28'),
(39, 1, 27, 'ORD-65A3A720', 11099.00, 5000.00, 5000.00, 'credit_card', 'CONFIRMED', 'PAID', NULL, NULL, NULL, '2025-04-08 19:48:25', '2025-04-08 19:48:25'),
(41, 1, 28, 'ORD-1CA183EA', 13279.00, 6000.00, 6000.00, 'credit_card', 'CONFIRMED', 'PAID', NULL, NULL, NULL, '2025-04-08 20:10:25', '2025-04-08 20:10:25'),
(44, 1, 28, 'ORD-52EC7D8A', 13279.00, 6000.00, 6000.00, 'credit_card', 'CONFIRMED', 'PAID', NULL, NULL, NULL, '2025-04-08 20:10:34', '2025-04-08 20:10:34'),
(47, 1, 29, 'ORD-9E60F9A6', 16549.00, 7500.00, 7500.00, 'credit_card', 'CONFIRMED', 'PAID', NULL, NULL, NULL, '2025-04-08 20:18:24', '2025-04-08 20:18:24'),
(48, 1, 29, 'ORD-67B44872', 16549.00, 7500.00, 7500.00, 'credit_card', 'CONFIRMED', 'PAID', NULL, NULL, NULL, '2025-04-08 20:18:25', '2025-04-08 20:18:25');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_outfit`
--

CREATE TABLE `tbl_outfit` (
  `outfit_id` int(11) NOT NULL,
  `description_id` int(11) DEFAULT NULL,
  `brand_id` int(11) DEFAULT NULL,
  `size_id` int(11) DEFAULT NULL,
  `gender_id` int(11) DEFAULT NULL,
  `mrp` decimal(10,2) NOT NULL,
  `image1` varchar(255) DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `email` varchar(255) NOT NULL,
  `purchase_year` int(11) NOT NULL,
  `city` varchar(100) NOT NULL,
  `image2` varchar(255) DEFAULT NULL,
  `image3` varchar(255) DEFAULT NULL,
  `proof_image` varchar(255) DEFAULT NULL,
  `address` text NOT NULL,
  `price_id` int(11) DEFAULT NULL,
  `type_id` int(11) DEFAULT NULL,
  `occasion_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_outfit`
--

INSERT INTO `tbl_outfit` (`outfit_id`, `description_id`, `brand_id`, `size_id`, `gender_id`, `mrp`, `image1`, `status`, `email`, `purchase_year`, `city`, `image2`, `image3`, `proof_image`, `address`, `price_id`, `type_id`, `occasion_id`, `created_at`, `updated_at`) VALUES
(8, 20, 31, 23, 9, 500000.00, '1742350004', 'rejected', 'keerthanathomas2027@mca.ajce.in', 2023, 'Kottayam', '1742350004', '1742350004_image3.jpg', '1742350004_proof.jpg', 'llll', 21, 1, NULL, '2025-03-19 02:06:44', '2025-03-24 04:03:11'),
(9, 23, 30, 23, 9, 70000.00, '1742372170', 'approved', 'keerthanathomas2027@mca.ajce.in', 2017, 'Kottayam', '1742372170', '1742372170_image3.jpg', '1742372170_proof.jpg', 'ppp', 17, 1, 11, '2025-03-19 08:16:10', '2025-04-07 17:26:21'),
(10, 24, 30, 24, 9, 100000.00, '1742406148', 'rejected', 'keerthanathomas2027@mca.ajce.in', 2018, 'Kottayam', '1742406148', '1742406148_image3.jpg', '1742406148_proof.jpg', 'purappanthanam(h), parathode P.O, Velichiyani,686512', 17, 2, NULL, '2025-03-19 17:42:28', '2025-03-24 04:03:26'),
(11, 25, 31, 23, 9, 300000.00, '1742459278', 'approved', 'keerthanathomas2027@mca.ajce.in', 2020, 'Kottayam', '1742459278', '1742459278_image3.jpg', '1742459278_proof.jpg', 'purappanthanam(h), parathode P.O, Velichiyani,686512', 21, 1, 11, '2025-03-20 08:27:58', '2025-04-07 17:26:21'),
(12, 26, 28, 26, 9, 40000.00, '1742542675', 'approved', 'keerthanathomas2027@mca.ajce.in', 2020, 'Kottayam', '1742542675', '1742542675_image3.jpg', '1742542675_proof.jpg', 'purappanthanam(h), parathode P.O, Velichiyani,686512', 17, 1, 11, '2025-03-21 07:37:55', '2025-04-07 17:26:21'),
(13, 27, 31, 24, 9, 30500.00, '1742738721', 'approved', 'keerthanathomas2027@mca.ajce.in', 2020, 'Kottayam', '1742738721', '1742738721_image3.jpg', '1742738721_proof.jpg', 'purappanthanam(h), parathode P.O, Velichiyani,686512', 17, 2, 11, '2025-03-23 14:05:21', '2025-04-07 17:26:21'),
(14, 28, 29, 24, 9, 40500.00, '1742750821', 'approved', 'keerthanathomas2027@mca.ajce.in', 2020, 'Kottayam', '1742750821', '1742750821_image3.jpg', '1742750821_proof.jpg', 'purappanthanam(h), parathode P.O, Velichiyani,686512', 17, 1, 11, '2025-03-23 17:27:01', '2025-04-07 17:26:21'),
(15, 29, 28, 26, 9, 50000.00, '1742786863', 'approved', 'keerthanathomas2027@mca.ajce.in', 2020, 'Kottayam', '1742786863', '1742786863_image3.jpg', '1742786863_proof.jpg', 'Malayil(H),Poojar, Pala', 17, 2, 11, '2025-03-24 03:27:43', '2025-04-07 17:26:21'),
(16, 30, 28, 26, 8, 60000.00, '1742788245', 'approved', 'keerthanathomas2027@mca.ajce.in', 2019, 'Kottayam', '1742788245', '1742788245_image3.jpg', '1742788245_proof.jpg', 'Purayidathil(h),Bharanganam, Pala', 17, 5, NULL, '2025-03-24 03:50:45', '2025-03-24 03:53:12'),
(17, 31, 29, 22, 9, 70000.00, '1743053498', 'approved', 'keerthanathomas2027@mca.ajce.in', 2019, 'Kottayam', '1743053498', '1743053498_image3.jpg', '1743053498_proof.jpg', 'Purayidathil(h),Bharanganam, Pala', 17, 1, 11, '2025-03-27 05:31:38', '2025-04-07 17:26:21'),
(18, 32, 30, 24, 9, 70000.00, '1743064766', 'approved', 'keerthanathomas2027@mca.ajce.in', 2019, 'Kottayam', '1743064766', '1743064766_image3.jpg', '1743064766_proof.jpg', 'purappanthanam(h), parathode P.O, Velichiyani,686512', 17, 1, 11, '2025-03-27 08:39:26', '2025-04-07 17:26:21'),
(19, 33, 28, 24, 9, 50000.00, '1743376922', 'approved', 'keerthanathomas2027@mca.ajce.in', 2019, 'Kottayam', '1743376922', '1743376922_image3.jpg', '1743376922_proof.jpg', 'Purayidathil(h),Bharanganam, Pala', 17, 1, 11, '2025-03-30 23:22:02', '2025-04-07 17:26:21'),
(20, 34, 28, 25, 8, 60000.00, '1743431327', 'approved', 'keerthanathomas2027@mca.ajce.in', 2023, 'Kottayam', '1743431328', '1743431328_image3.jpg', '1743431328_proof.jpg', 'Purayidathil(h),Bharanganam, Pala', 17, 5, NULL, '2025-03-31 14:28:48', '2025-03-31 14:31:13'),
(21, 35, 32, 22, 9, 35000.00, '1743434927', 'approved', 'keerthanathomas2027@mca.ajce.in', 2023, 'Kottayam', '1743434927', '1743434927_image3.jpg', '1743434927_proof.jpg', 'purappanthanam(h), parathode P.O, Velichiyani,686512', 17, 6, 11, '2025-03-31 15:28:47', '2025-04-07 17:26:21'),
(22, 36, 28, 24, 9, 600000.00, '1743485064', 'approved', 'keerthanathomas2027@mca.ajce.in', 2023, 'Kottayam', '1743485064', '1743485064_image3.jpg', '1743485064_proof.jpg', 'Parackal(h), parathode P.O, Kanjirapally,686512', 21, 2, 11, '2025-04-01 05:24:24', '2025-04-07 17:26:21'),
(23, 37, 31, 23, 8, 450000.00, '1743558082', 'approved', 'keerthanathomas2027@mca.ajce.in', 2023, 'Kottayam', '1743558082', '1743558082_image3.jpg', '1743558082_proof.jpg', 'Puthyaparambil(h) parathode p.o Velichiyani', 21, 4, NULL, '2025-04-02 01:41:22', '2025-04-02 02:58:25'),
(24, NULL, 30, 24, 9, 56000.00, '1743576552', 'rejected', 'thomaspeejay@gmail.com', 2023, 'Kottayam', '1743576552', '1743576552_image3.jpg', '1743576552_proof.jpg', 'Puthyaparambil(h) parathode p.o Velichiyani', 2, 2, NULL, '2025-04-02 06:49:12', '2025-04-02 06:52:06'),
(25, 38, 32, 24, 9, 80000.00, '1743579768', 'approved', 'thomaspeejay@gmail.com', 2022, 'Kottayam', '1743579768', '1743579768_image3.jpg', '1743579768_proof.jpg', 'Puthyaparambil(h) parathode p.o Velichiyani', 17, 1, 11, '2025-04-02 07:42:48', '2025-04-07 17:26:21'),
(26, 39, 29, 24, 9, 70000.00, '1744133454', 'approved', 'keerthanathomas2027@mca.ajce.in', 2023, 'Kottayam', '1744133454', '1744133454_image3.jpg', '1744133454_proof.png', 'Puthiyaparambil(H), Velichiyani', 17, 1, NULL, '2025-04-08 17:30:54', '2025-04-08 17:32:23'),
(27, 40, 30, 25, 9, 50000.00, '1744134328', 'approved', 'keerthanathomas2027@mca.ajce.in', 2023, 'Kottayam', '1744134328', '1744134328_image3.jpg', '1744134328_proof.png', 'Puthiyaparampil(H), Kottayam', 17, 6, NULL, '2025-04-08 17:45:28', '2025-04-08 17:47:51'),
(28, 41, 30, 23, 8, 60000.00, '1744135626', 'approved', 'keerthanathomas2027@mca.ajce.in', 2024, 'Kottayam', '1744135626', '1744135626_image3.jpg', '1744135626_proof.png', 'Kottayam', 18, 4, NULL, '2025-04-08 18:07:06', '2025-04-08 18:09:30'),
(29, 42, 29, 22, 8, 75000.00, '1744136146', 'approved', 'keerthanathomas2027@mca.ajce.in', 2019, 'Kottayam', '1744136146', '1744136146_image3.jpg', '1744136146_proof.png', 'Kottayam', 18, 4, NULL, '2025-04-08 18:15:46', '2025-04-08 18:17:26'),
(30, 43, 28, 23, 9, 60000.00, '1744138107', 'approved', 'thomaspeejay@gmail.com', 2023, 'Cochin', '1744138107', '1744138107_image3.jpg', '1744138107_proof.png', 'Kadavanthara, Cochin', 17, 2, NULL, '2025-04-08 18:48:27', '2025-04-08 18:50:00'),
(31, 44, 28, 24, 9, 50000.00, '1744148144', 'approved', 'keerthanathomas2027@mca.ajce.in', 2023, 'Cochin', '1744148144', '1744148144_image3.jpg', '1744148144_proof.png', 'Palluruthi, Cochin', 17, 1, NULL, '2025-04-08 21:35:44', '2025-04-08 21:37:37'),
(32, NULL, 29, 24, 9, 80000.00, NULL, 'rejected', 'thomaspeejay@gmail.com', 2023, 'Kottayam', NULL, NULL, '1744148819_proof.png', 'Bharanganam , Pala', 2, 1, NULL, '2025-04-08 21:46:59', '2025-04-08 21:48:40'),
(33, 45, 28, 23, 9, 55000.00, '1744149072', 'approved', 'keerthanathomas2027@mca.ajce.in', 2023, 'Kottayam', '1744149072', '1744149072_image3.jpg', '1744149072_proof.png', 'Purappanthanam H ,Parathode P.O,Velichiyani', 17, 1, NULL, '2025-04-08 21:51:12', '2025-04-08 21:52:46'),
(34, 46, 28, 24, 9, 35000.00, '1744149692', 'approved', 'thomaspeejay@gmail.com', 2024, 'Kottayam', '1744149692', '1744149692_image3.jpg', '1744149692_proof.png', 'Purappanthanam H ,Parathode P.O,Velichiyani', 17, 6, NULL, '2025-04-08 22:01:32', '2025-04-08 22:02:43'),
(35, 47, 28, 22, 9, 76000.00, '1744150009', 'approved', 'keerthanathomas2027@mca.ajce.in', 2020, 'Kottayam', '1744150009', '1744150009_image3.jpg', '1744150009_proof.png', 'Purappanthanam H ,Parathode P.O,Velichiyani', 17, 1, NULL, '2025-04-08 22:06:49', '2025-04-08 22:08:13'),
(36, 48, 28, 25, 9, 60000.00, '1744153336', 'approved', 'keerthanathomas2027@mca.ajce.in', 2022, 'Kottayam', '1744153336', '1744153336_image3.jpg', '1744153336_proof.png', 'Purappanthanam H ,Parathode P.O,Velichiyani', 17, 1, NULL, '2025-04-08 23:02:16', '2025-04-08 23:03:26'),
(37, 49, 28, 23, 9, 45000.00, '1744154395', 'approved', 'keerthanathomas2027@mca.ajce.in', 2021, 'Kottayam', '1744154395', '1744154395_image3.jpg', '1744154395_proof.png', 'Purappanthanam H ,Parathode P.O,Velichiyani', 17, 2, NULL, '2025-04-08 23:19:55', '2025-04-08 23:21:18'),
(39, 50, 31, 26, 8, 90000.00, '1744159752', 'approved', 'thomaspeejay@gmail.com', 2022, 'Cochin', '1744159752', '1744159752_image3.jpg', '1744159752_proof.jpg', 'Palluruthi, Cochin', 17, 4, NULL, '2025-04-09 00:49:12', '2025-04-09 00:51:02');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_outfit_images`
--

CREATE TABLE `tbl_outfit_images` (
  `id` int(11) NOT NULL,
  `outfit_id` int(11) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `uploaded_by` enum('admin','user') DEFAULT 'user',
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_outfit_images`
--

INSERT INTO `tbl_outfit_images` (`id`, `outfit_id`, `image_path`, `uploaded_by`, `uploaded_at`) VALUES
(1, 8, 'uploads/outfit_67da43c7aa1df.jpg', 'admin', '2025-03-19 04:10:47'),
(2, 8, 'uploads/outfit_67da43c7c3abf.jpg', 'admin', '2025-03-19 04:10:47'),
(3, 8, 'uploads/outfit_67da43c7cf261.jpg', 'admin', '2025-03-19 04:10:47'),
(4, 8, 'uploads/outfit_67da43c7eeb79.jpg', 'admin', '2025-03-19 04:10:47'),
(5, 9, 'uploads/outfit_67da7d9158b95.jpg', 'admin', '2025-03-19 08:17:21'),
(6, 9, 'uploads/outfit_67da7d9166547.jpg', 'admin', '2025-03-19 08:17:21'),
(7, 9, 'uploads/outfit_67da7d9171fcf.jpg', 'admin', '2025-03-19 08:17:21'),
(8, 9, 'uploads/outfit_67da7d91894d3.jpg', 'admin', '2025-03-19 08:17:21'),
(9, 10, 'uploads/outfit_67db024acb5ba.jpg', 'admin', '2025-03-19 17:43:38'),
(10, 10, 'uploads/outfit_67db024ad91f9.jpg', 'admin', '2025-03-19 17:43:38'),
(11, 10, 'uploads/outfit_67db024b007f7.jpg', 'admin', '2025-03-19 17:43:39'),
(12, 10, 'uploads/outfit_67db024b0c0e6.jpg', 'admin', '2025-03-19 17:43:39'),
(13, 11, 'uploads/outfit_67dbd1e7ef402.jpg', 'admin', '2025-03-20 08:29:27'),
(14, 11, 'uploads/outfit_67dbd1e8083fb.jpg', 'admin', '2025-03-20 08:29:28'),
(15, 11, 'uploads/outfit_67dbd1e813875.jpg', 'admin', '2025-03-20 08:29:28'),
(16, 11, 'uploads/outfit_67dbd1e82f516.jpg', 'admin', '2025-03-20 08:29:28'),
(17, 12, 'uploads/outfit_67dd17893bdf6.jpg', 'admin', '2025-03-21 07:38:49'),
(18, 12, 'uploads/outfit_67dd178986bd6.jpg', 'admin', '2025-03-21 07:38:49'),
(19, 12, 'uploads/outfit_67dd17899733d.jpg', 'admin', '2025-03-21 07:38:49'),
(20, 12, 'uploads/outfit_67dd17899fca6.jpg', 'admin', '2025-03-21 07:38:49'),
(21, 13, 'uploads/outfit_67e0156c79f1e.jpg', 'admin', '2025-03-23 14:06:36'),
(22, 13, 'uploads/outfit_67e0156c885c3.jpg', 'admin', '2025-03-23 14:06:36'),
(23, 13, 'uploads/outfit_67e0156ca6a32.jpg', 'admin', '2025-03-23 14:06:36'),
(24, 13, 'uploads/outfit_67e0156cc521a.jpg', 'admin', '2025-03-23 14:06:36'),
(25, 14, 'uploads/outfit_67e044a1d1c4b.jpg', 'admin', '2025-03-23 17:28:01'),
(26, 14, 'uploads/outfit_67e044a1ea09a.jpg', 'admin', '2025-03-23 17:28:01'),
(27, 14, 'uploads/outfit_67e044a21adc3.jpg', 'admin', '2025-03-23 17:28:02'),
(28, 14, 'uploads/outfit_67e044a2347c5.jpg', 'admin', '2025-03-23 17:28:02'),
(29, 15, 'uploads/outfit_67e0d17f3aafe.jpg', 'admin', '2025-03-24 03:29:03'),
(30, 15, 'uploads/outfit_67e0d17f664fd.jpg', 'admin', '2025-03-24 03:29:03'),
(31, 15, 'uploads/outfit_67e0d17f71ece.jpg', 'admin', '2025-03-24 03:29:03'),
(32, 15, 'uploads/outfit_67e0d1a38dbbc.jpg', 'admin', '2025-03-24 03:29:39'),
(33, 16, 'uploads/outfit_67e0d6fcb176e.jpg', 'admin', '2025-03-24 03:52:28'),
(34, 16, 'uploads/outfit_67e0d6fcc8065.jpg', 'admin', '2025-03-24 03:52:28'),
(35, 16, 'uploads/outfit_67e0d6fcdf78d.jpg', 'admin', '2025-03-24 03:52:28'),
(36, 16, 'uploads/outfit_67e0d6fce8358.jpg', 'admin', '2025-03-24 03:52:28'),
(37, 17, 'uploads/outfit_67e4e32ca7339.jpg', 'admin', '2025-03-27 05:33:32'),
(38, 17, 'uploads/outfit_67e4e32cb7d13.jpg', 'admin', '2025-03-27 05:33:32'),
(39, 17, 'uploads/outfit_67e4e32cc354d.jpg', 'admin', '2025-03-27 05:33:32'),
(40, 17, 'uploads/outfit_67e4e32cdc593.jpg', 'admin', '2025-03-27 05:33:32'),
(41, 18, 'uploads/outfit_67e50f1fbdeda.jpg', 'admin', '2025-03-27 08:41:03'),
(42, 18, 'uploads/outfit_67e50f1fd5c91.jpg', 'admin', '2025-03-27 08:41:03'),
(43, 18, 'uploads/outfit_67e50f2008c00.jpg', 'admin', '2025-03-27 08:41:04'),
(44, 18, 'uploads/outfit_67e50f202e79e.jpg', 'admin', '2025-03-27 08:41:04'),
(45, 19, 'uploads/outfit_67e9d2821e1df.jpg', 'admin', '2025-03-30 23:23:46'),
(46, 19, 'uploads/outfit_67e9d28229b26.jpg', 'admin', '2025-03-30 23:23:46'),
(47, 19, 'uploads/outfit_67e9d28242cb9.jpg', 'admin', '2025-03-30 23:23:46'),
(48, 19, 'uploads/outfit_67e9d2824e3e4.jpg', 'admin', '2025-03-30 23:23:46'),
(49, 20, 'uploads/outfit_67eaa6df25660.jpg', 'admin', '2025-03-31 14:29:51'),
(50, 20, 'uploads/outfit_67eaa6df325f1.jpg', 'admin', '2025-03-31 14:29:51'),
(51, 20, 'uploads/outfit_67eaa6df3dce0.jpg', 'admin', '2025-03-31 14:29:51'),
(52, 20, 'uploads/outfit_67eaa6df6d1f1.jpg', 'admin', '2025-03-31 14:29:51'),
(53, 21, 'uploads/outfit_67eab4e57540f.jpg', 'admin', '2025-03-31 15:29:41'),
(54, 21, 'uploads/outfit_67eab4e584e1c.jpg', 'admin', '2025-03-31 15:29:41'),
(55, 21, 'uploads/outfit_67eab4e5b7f33.jpg', 'admin', '2025-03-31 15:29:41'),
(56, 21, 'uploads/outfit_67eab4e5dadeb.jpg', 'admin', '2025-03-31 15:29:41'),
(57, 22, 'uploads/outfit_67eb793350736.jpg', 'admin', '2025-04-01 05:27:15'),
(58, 22, 'uploads/outfit_67eb79335d518.jpg', 'admin', '2025-04-01 05:27:15'),
(59, 22, 'uploads/outfit_67eb79337e8eb.jpg', 'admin', '2025-04-01 05:27:15'),
(60, 23, 'uploads/outfit_67eca79fd2926.jpg', 'admin', '2025-04-02 02:57:35'),
(61, 23, 'uploads/outfit_67eca7a031f72.jpg', 'admin', '2025-04-02 02:57:36'),
(62, 23, 'uploads/outfit_67eca7a07b9b2.jpg', 'admin', '2025-04-02 02:57:36'),
(63, 23, 'uploads/outfit_67eca7a091d95.jpg', 'admin', '2025-04-02 02:57:36'),
(64, 25, 'uploads/outfit_67eceab76cfea.jpg', 'admin', '2025-04-02 07:43:51'),
(65, 25, 'uploads/outfit_67eceab77ee67.jpg', 'admin', '2025-04-02 07:43:51'),
(66, 25, 'uploads/outfit_67eceab78d28e.jpg', 'admin', '2025-04-02 07:43:51'),
(67, 25, 'uploads/outfit_67eceab7a1465.jpg', 'admin', '2025-04-02 07:43:51'),
(68, 26, 'uploads/outfit_67f55d860e330.jpg', 'admin', '2025-04-08 17:31:50'),
(69, 26, 'uploads/outfit_67f55d8610558.jpg', 'admin', '2025-04-08 17:31:50'),
(70, 26, 'uploads/outfit_67f55d8611187.jpg', 'admin', '2025-04-08 17:31:50'),
(71, 26, 'uploads/outfit_67f55d8611c2b.jpg', 'admin', '2025-04-08 17:31:50'),
(72, 27, 'uploads/outfit_67f561146a010.jpg', 'admin', '2025-04-08 17:47:00'),
(73, 27, 'uploads/outfit_67f561146bf7d.jpg', 'admin', '2025-04-08 17:47:00'),
(74, 27, 'uploads/outfit_67f561146ceda.jpg', 'admin', '2025-04-08 17:47:00'),
(75, 28, 'uploads/outfit_67f566364a308.jpg', 'admin', '2025-04-08 18:08:54'),
(76, 28, 'uploads/outfit_67f566364d2bd.jpg', 'admin', '2025-04-08 18:08:54'),
(77, 28, 'uploads/outfit_67f566364de1d.jpg', 'admin', '2025-04-08 18:08:54'),
(78, 28, 'uploads/outfit_67f566364e788.jpg', 'admin', '2025-04-08 18:08:54'),
(79, 29, 'uploads/outfit_67f56813cf2ba.jpg', 'admin', '2025-04-08 18:16:51'),
(80, 29, 'uploads/outfit_67f56813cfddb.jpg', 'admin', '2025-04-08 18:16:51'),
(81, 29, 'uploads/outfit_67f56813d069c.jpg', 'admin', '2025-04-08 18:16:51'),
(82, 29, 'uploads/outfit_67f56813d0f97.jpg', 'admin', '2025-04-08 18:16:51'),
(83, 30, 'uploads/outfit_67f56fb490e36.jpg', 'admin', '2025-04-08 18:49:24'),
(84, 30, 'uploads/outfit_67f56fb4930c0.jpg', 'admin', '2025-04-08 18:49:24'),
(85, 30, 'uploads/outfit_67f56fb49476b.jpg', 'admin', '2025-04-08 18:49:24'),
(86, 30, 'uploads/outfit_67f56fb495078.jpg', 'admin', '2025-04-08 18:49:24'),
(87, 31, 'uploads/outfit_67f596ffd175f.jpg', 'admin', '2025-04-08 21:37:03'),
(88, 31, 'uploads/outfit_67f596ffd292d.jpg', 'admin', '2025-04-08 21:37:03'),
(89, 31, 'uploads/outfit_67f596ffd3684.jpg', 'admin', '2025-04-08 21:37:03'),
(90, 33, 'uploads/outfit_67f59a84f40a0.jpg', 'admin', '2025-04-08 21:52:05'),
(91, 33, 'uploads/outfit_67f59a8501aa8.jpg', 'admin', '2025-04-08 21:52:05'),
(92, 33, 'uploads/outfit_67f59a850404a.jpg', 'admin', '2025-04-08 21:52:05'),
(93, 34, 'uploads/outfit_67f59ce44d75a.jpg', 'admin', '2025-04-08 22:02:12'),
(94, 34, 'uploads/outfit_67f59ce44f2bb.jpg', 'admin', '2025-04-08 22:02:12'),
(95, 34, 'uploads/outfit_67f59ce44fdb3.jpg', 'admin', '2025-04-08 22:02:12'),
(96, 34, 'uploads/outfit_67f59ce4506f1.jpg', 'admin', '2025-04-08 22:02:12'),
(97, 35, 'uploads/outfit_67f59e2559084.jpg', 'admin', '2025-04-08 22:07:33'),
(98, 35, 'uploads/outfit_67f59e255a57c.jpg', 'admin', '2025-04-08 22:07:33'),
(99, 35, 'uploads/outfit_67f59e255b12d.jpg', 'admin', '2025-04-08 22:07:33'),
(100, 35, 'uploads/outfit_67f59e255b9ea.jpg', 'admin', '2025-04-08 22:07:33'),
(101, 36, 'uploads/outfit_67f5ab2900230.jpg', 'admin', '2025-04-08 23:03:05'),
(102, 36, 'uploads/outfit_67f5ab29014e8.jpg', 'admin', '2025-04-08 23:03:05'),
(103, 36, 'uploads/outfit_67f5ab2902362.jpg', 'admin', '2025-04-08 23:03:05'),
(104, 37, 'uploads/outfit_67f5af57bd529.jpg', 'admin', '2025-04-08 23:20:55'),
(105, 37, 'uploads/outfit_67f5af57bfb34.jpg', 'admin', '2025-04-08 23:20:55'),
(106, 37, 'uploads/outfit_67f5af57c16e4.jpg', 'admin', '2025-04-08 23:20:55'),
(107, 37, 'uploads/outfit_67f5af57c212e.jpg', 'admin', '2025-04-08 23:20:55'),
(108, 39, 'uploads/outfit_67f5c44c79070.jpg', 'admin', '2025-04-09 00:50:20'),
(109, 39, 'uploads/outfit_67f5c44c968f8.jpg', 'admin', '2025-04-09 00:50:20'),
(110, 39, 'uploads/outfit_67f5c44cafdc9.jpg', 'admin', '2025-04-09 00:50:20'),
(111, 39, 'uploads/outfit_67f5c44cbdc34.jpg', 'admin', '2025-04-09 00:50:20');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_outfit_occasion`
--

CREATE TABLE `tbl_outfit_occasion` (
  `id` int(11) NOT NULL,
  `outfit_id` int(11) NOT NULL,
  `occasion_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_outfit_occasion`
--

INSERT INTO `tbl_outfit_occasion` (`id`, `outfit_id`, `occasion_id`) VALUES
(1, 8, 12),
(2, 8, 14),
(3, 9, 11),
(4, 9, 12),
(6, 10, 11),
(7, 10, 14),
(8, 11, 13),
(9, 11, 14),
(10, 11, 15),
(11, 12, 11),
(12, 12, 14),
(13, 13, 11),
(14, 13, 14),
(15, 14, 12),
(16, 14, 14),
(17, 15, 11),
(18, 15, 16),
(19, 16, 13),
(20, 16, 14),
(21, 16, 15),
(22, 17, 12),
(23, 17, 14),
(24, 17, 16),
(25, 18, 11),
(26, 18, 14),
(27, 19, 11),
(28, 19, 13),
(29, 20, 13),
(30, 20, 15),
(31, 21, 12),
(32, 21, 14),
(33, 22, 13),
(34, 22, 15),
(35, 23, 11),
(36, 23, 12),
(37, 25, 12),
(38, 25, 14),
(39, 26, 14),
(40, 27, 12),
(41, 27, 14),
(42, 28, 11),
(43, 28, 12),
(44, 28, 14),
(45, 29, 11),
(46, 29, 12),
(47, 29, 14),
(48, 30, 12),
(49, 30, 13),
(50, 30, 16),
(51, 31, 12),
(52, 31, 14),
(53, 33, 12),
(54, 33, 14),
(55, 33, 16),
(56, 34, 12),
(57, 34, 14),
(58, 35, 11),
(59, 35, 12),
(60, 36, 11),
(61, 36, 14),
(62, 37, 11),
(63, 37, 12),
(64, 37, 14),
(65, 39, 12),
(66, 39, 14);

-- --------------------------------------------------------

--
-- Table structure for table `tbl_reviews`
--

CREATE TABLE `tbl_reviews` (
  `review_id` int(11) NOT NULL,
  `outfit_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `rating` int(11) NOT NULL CHECK (`rating` between 1 and 5),
  `review_text` text DEFAULT NULL,
  `review_date` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_reviews`
--

INSERT INTO `tbl_reviews` (`review_id`, `outfit_id`, `user_id`, `rating`, `review_text`, `review_date`) VALUES
(1, 16, 4, 4, 'Great service and affordable cost. The quality was also a lot better than what i have expected. Got the the security deposit as well. Totally recommend this .', '2025-03-27 05:10:26'),
(2, 18, 1, 3, 'Really liked the outfit. The quality was quite satisfactory. Great service as well', '2025-03-28 02:55:52'),
(3, 19, 4, 4, 'This is actually really good. One of the best outfits in this platform. Loved it  and totally recommend this one', '2025-04-02 08:00:01'),
(4, 19, 1, 5, 'Exceptional Outfit, really worthy of the rental price', '2025-04-07 13:16:27'),
(5, 9, 1, 5, 'Really loved the outfit.Was totally worthy of the price. Totally recommend this lahenga', '2025-04-07 13:31:57'),
(6, 23, 1, 3, 'One of the best outfits rented from this platform, there were some issues with the fitting. However the quality was top notch', '2025-04-07 13:44:29'),
(7, 25, 1, 3, 'The colour is brighter. Its slightly different than whats being shown. But the quality is really good', '2025-04-07 14:47:18'),
(8, 31, 1, 3, 'The quality is not that good, but it looks really beautiful, also pleased with the way the alterations were made', '2025-04-09 04:44:25');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_subcategory`
--

CREATE TABLE `tbl_subcategory` (
  `id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `subcategory_name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_subcategory`
--

INSERT INTO `tbl_subcategory` (`id`, `category_id`, `subcategory_name`) VALUES
(1, 1, 'Lehenga'),
(2, 1, 'Saree'),
(3, 1, 'Gown'),
(4, 1, 'Suits'),
(5, 1, 'Tuxedo'),
(6, 1, 'Salwar suits'),
(7, 1, 'Other'),
(8, 5, 'Male'),
(9, 5, 'Female'),
(10, 5, 'All'),
(11, 4, 'Wedding'),
(12, 4, 'Pre-wedding'),
(13, 4, 'Cocktail'),
(14, 4, 'Shoots'),
(15, 4, 'Blacktie'),
(16, 4, 'Other'),
(17, 6, 'Below 20000'),
(18, 6, 'Above 20000'),
(19, 6, 'Above 30000'),
(20, 6, 'Above 40000'),
(21, 6, 'Above 50000'),
(22, 3, 'XS'),
(23, 3, 'S'),
(24, 3, 'M'),
(25, 3, 'L'),
(26, 3, 'XL'),
(27, 3, 'XXL'),
(28, 2, 'Seema Gujral'),
(29, 2, 'Manish Malhotra'),
(30, 2, 'Sabyasachi'),
(31, 2, 'Tarun Tahiliani'),
(32, 2, 'Others');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_transactions`
--

CREATE TABLE `tbl_transactions` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `transaction_type` varchar(20) NOT NULL,
  `payment_method` varchar(20) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `transaction_id` varchar(100) DEFAULT NULL,
  `status` varchar(20) NOT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_transactions`
--

INSERT INTO `tbl_transactions` (`id`, `order_id`, `user_id`, `transaction_type`, `payment_method`, `amount`, `transaction_id`, `status`, `created_at`) VALUES
(1, 1, 1, 'PAYMENT', 'credit_card', 8919.00, NULL, 'COMPLETED', '2025-03-23 13:38:36'),
(2, 1, 1, 'PAYMENT', 'credit_card', 8919.00, NULL, 'COMPLETED', '2025-03-23 14:09:51'),
(3, 1, 1, 'PAYMENT', 'credit_card', 8919.00, NULL, 'COMPLETED', '2025-03-23 14:12:02'),
(4, 1, 1, 'PAYMENT', 'credit_card', 8919.00, NULL, 'COMPLETED', '2025-03-23 14:32:49'),
(5, 1, 1, 'PAYMENT', 'credit_card', 8919.00, NULL, 'COMPLETED', '2025-03-23 14:41:58'),
(6, 1, 1, 'PAYMENT', 'credit_card', 8919.00, NULL, 'COMPLETED', '2025-03-23 15:00:36'),
(7, 1, 1, 'PAYMENT', 'credit_card', 8919.00, NULL, 'COMPLETED', '2025-03-23 15:11:45'),
(8, 1, 1, 'PAYMENT', 'credit_card', 8919.00, NULL, 'COMPLETED', '2025-03-23 15:24:29'),
(9, 1, 1, 'PAYMENT', 'credit_card', 8919.00, NULL, 'COMPLETED', '2025-03-23 15:27:30'),
(10, 1, 1, 'PAYMENT', 'credit_card', 8919.00, NULL, 'COMPLETED', '2025-03-23 15:32:33'),
(11, 1, 1, 'PAYMENT', 'credit_card', 8919.00, NULL, 'COMPLETED', '2025-03-23 15:37:33'),
(12, 1, 1, 'PAYMENT', 'credit_card', 8919.00, NULL, 'COMPLETED', '2025-03-23 17:18:18'),
(13, 1, 1, 'PAYMENT', 'credit_card', 8919.00, NULL, 'COMPLETED', '2025-03-23 17:30:54'),
(14, 1, 1, 'PAYMENT', 'credit_card', 8919.00, NULL, 'COMPLETED', '2025-03-24 03:04:17'),
(15, 1, 1, 'PAYMENT', 'credit_card', 8919.00, NULL, 'COMPLETED', '2025-03-24 03:14:33'),
(16, 1, 1, 'PAYMENT', 'credit_card', 8919.00, NULL, 'COMPLETED', '2025-03-24 03:32:50'),
(17, 1, 1, 'PAYMENT', 'credit_card', 8919.00, NULL, 'COMPLETED', '2025-03-24 03:56:29'),
(18, 1, 1, 'PAYMENT', 'credit_card', 8919.00, NULL, 'COMPLETED', '2025-03-27 06:05:48');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_users`
--

CREATE TABLE `tbl_users` (
  `user_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `verification_token` varchar(64) DEFAULT NULL,
  `is_verified` tinyint(1) DEFAULT 0,
  `role` enum('user','admin','lender') DEFAULT 'user',
  `google_id` varchar(255) DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_users`
--

INSERT INTO `tbl_users` (`user_id`, `name`, `email`, `password`, `phone`, `verification_token`, `is_verified`, `role`, `google_id`, `profile_picture`) VALUES
(1, 'keerthanaUser', 'keerthanathomas097@gmail.com', '$2y$10$SyfGdzGBhZriHK53djiGLOLAiWyMIvznvUy8yGIyLEs2q2j0T/SM6', '8075643884', NULL, 1, 'user', 'BjR0YqxWS1Qdmxh8gCFQC4RNXzH3', 'https://lh3.googleusercontent.com/a/ACg8ocJTZ-TPQnvyarSGtEwAt5M0mZFKtX_IXrfqk7QS8jOZpkerdQ=s96-c'),
(2, 'Anna Thomas', 'keerthanathomas9697@gmail.com', '$2y$10$DxHPKlm.lNmv5gshSlO.ke5sTqKT8k08dJPcmB0262xJpQNtjttNy', '8075643889', NULL, 0, 'admin', NULL, NULL),
(3, 'keerthanacollege', 'keerthanathomas2027@mca.ajce.in', '$2y$10$FCBtJypaPQreE/U6sgahgeLWzxOp0R9WAFvptpk0SHKOywA.QDNO.', '8075643885', NULL, 1, 'lender', 'VL4veAESsJPBnxvKDWeVpS4E6lN2', 'https://lh3.googleusercontent.com/a/ACg8ocJrktsDC6t2zU0lFvwr3fXRlPYz0iED2m8C9gmMezHuW_Jxzg=s96-c'),
(4, 'ThomasPJ', 'thomaspeejay@gmail.com', '$2y$10$z57.37b1fxubacXKKn0i9OgxnqdPGehGqi0knG6OnCT9lpKANUL0y', '9446223569', NULL, 1, 'lender', NULL, NULL),
(5, 'Krishna', 'krishnapriyarajesh2027@mca.ajce.in', '$2y$10$4MeRx530lpOmtKLapB/iZ.3ptSp3HO9IdvkyGhZDfQT57Q2xC6k1S', '8590643203', '1d3c9ec2d131c22240c6c44f00dfda46cce0da899d2cbe8841e470b1dfcace26', 0, 'user', NULL, NULL),
(6, 'suluthana', 'sulthanafathima2027@mca.ajce.in', '$2y$10$9vfWkN1nukaQ42klzX4WO.LR1ZqDMGCh0UQD31euAPJ//ALKC83Ce', '8075643886', NULL, 1, 'lender', NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `tbl_carts`
--
ALTER TABLE `tbl_carts`
  ADD PRIMARY KEY (`cart_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `tbl_cart_items`
--
ALTER TABLE `tbl_cart_items`
  ADD PRIMARY KEY (`cart_item_id`),
  ADD KEY `cart_id` (`cart_id`),
  ADD KEY `outfit_id` (`outfit_id`);

--
-- Indexes for table `tbl_category`
--
ALTER TABLE `tbl_category`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tbl_description`
--
ALTER TABLE `tbl_description`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tbl_measurements`
--
ALTER TABLE `tbl_measurements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `outfit_id` (`outfit_id`);

--
-- Indexes for table `tbl_orders`
--
ALTER TABLE `tbl_orders`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tbl_outfit`
--
ALTER TABLE `tbl_outfit`
  ADD PRIMARY KEY (`outfit_id`),
  ADD KEY `description_id` (`description_id`),
  ADD KEY `brand_id` (`brand_id`),
  ADD KEY `size_id` (`size_id`),
  ADD KEY `gender_id` (`gender_id`),
  ADD KEY `price_id` (`price_id`),
  ADD KEY `type_id` (`type_id`),
  ADD KEY `occasion_id` (`occasion_id`);

--
-- Indexes for table `tbl_outfit_images`
--
ALTER TABLE `tbl_outfit_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `outfit_id` (`outfit_id`);

--
-- Indexes for table `tbl_outfit_occasion`
--
ALTER TABLE `tbl_outfit_occasion`
  ADD PRIMARY KEY (`id`),
  ADD KEY `outfit_id` (`outfit_id`),
  ADD KEY `occasion_id` (`occasion_id`);

--
-- Indexes for table `tbl_reviews`
--
ALTER TABLE `tbl_reviews`
  ADD PRIMARY KEY (`review_id`),
  ADD UNIQUE KEY `unique_review` (`outfit_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `tbl_subcategory`
--
ALTER TABLE `tbl_subcategory`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `tbl_transactions`
--
ALTER TABLE `tbl_transactions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tbl_users`
--
ALTER TABLE `tbl_users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `phone` (`phone`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `tbl_carts`
--
ALTER TABLE `tbl_carts`
  MODIFY `cart_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `tbl_cart_items`
--
ALTER TABLE `tbl_cart_items`
  MODIFY `cart_item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `tbl_category`
--
ALTER TABLE `tbl_category`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `tbl_description`
--
ALTER TABLE `tbl_description`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT for table `tbl_measurements`
--
ALTER TABLE `tbl_measurements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=56;

--
-- AUTO_INCREMENT for table `tbl_orders`
--
ALTER TABLE `tbl_orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=74;

--
-- AUTO_INCREMENT for table `tbl_outfit`
--
ALTER TABLE `tbl_outfit`
  MODIFY `outfit_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT for table `tbl_outfit_images`
--
ALTER TABLE `tbl_outfit_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=112;

--
-- AUTO_INCREMENT for table `tbl_outfit_occasion`
--
ALTER TABLE `tbl_outfit_occasion`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=67;

--
-- AUTO_INCREMENT for table `tbl_reviews`
--
ALTER TABLE `tbl_reviews`
  MODIFY `review_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `tbl_subcategory`
--
ALTER TABLE `tbl_subcategory`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `tbl_transactions`
--
ALTER TABLE `tbl_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `tbl_users`
--
ALTER TABLE `tbl_users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `tbl_carts`
--
ALTER TABLE `tbl_carts`
  ADD CONSTRAINT `tbl_carts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `tbl_users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `tbl_cart_items`
--
ALTER TABLE `tbl_cart_items`
  ADD CONSTRAINT `tbl_cart_items_ibfk_1` FOREIGN KEY (`cart_id`) REFERENCES `tbl_carts` (`cart_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tbl_cart_items_ibfk_2` FOREIGN KEY (`outfit_id`) REFERENCES `tbl_outfit` (`outfit_id`) ON DELETE CASCADE;

--
-- Constraints for table `tbl_measurements`
--
ALTER TABLE `tbl_measurements`
  ADD CONSTRAINT `tbl_measurements_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `tbl_users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tbl_measurements_ibfk_2` FOREIGN KEY (`outfit_id`) REFERENCES `tbl_outfit` (`outfit_id`);

--
-- Constraints for table `tbl_outfit`
--
ALTER TABLE `tbl_outfit`
  ADD CONSTRAINT `tbl_outfit_ibfk_1` FOREIGN KEY (`description_id`) REFERENCES `tbl_description` (`id`),
  ADD CONSTRAINT `tbl_outfit_ibfk_2` FOREIGN KEY (`brand_id`) REFERENCES `tbl_subcategory` (`id`),
  ADD CONSTRAINT `tbl_outfit_ibfk_3` FOREIGN KEY (`size_id`) REFERENCES `tbl_subcategory` (`id`),
  ADD CONSTRAINT `tbl_outfit_ibfk_4` FOREIGN KEY (`gender_id`) REFERENCES `tbl_subcategory` (`id`),
  ADD CONSTRAINT `tbl_outfit_ibfk_5` FOREIGN KEY (`price_id`) REFERENCES `tbl_subcategory` (`id`),
  ADD CONSTRAINT `tbl_outfit_ibfk_6` FOREIGN KEY (`type_id`) REFERENCES `tbl_subcategory` (`id`),
  ADD CONSTRAINT `tbl_outfit_ibfk_7` FOREIGN KEY (`occasion_id`) REFERENCES `tbl_subcategory` (`id`);

--
-- Constraints for table `tbl_outfit_images`
--
ALTER TABLE `tbl_outfit_images`
  ADD CONSTRAINT `tbl_outfit_images_ibfk_1` FOREIGN KEY (`outfit_id`) REFERENCES `tbl_outfit` (`outfit_id`) ON DELETE CASCADE;

--
-- Constraints for table `tbl_outfit_occasion`
--
ALTER TABLE `tbl_outfit_occasion`
  ADD CONSTRAINT `tbl_outfit_occasion_ibfk_1` FOREIGN KEY (`outfit_id`) REFERENCES `tbl_outfit` (`outfit_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tbl_outfit_occasion_ibfk_2` FOREIGN KEY (`occasion_id`) REFERENCES `tbl_subcategory` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tbl_reviews`
--
ALTER TABLE `tbl_reviews`
  ADD CONSTRAINT `tbl_reviews_ibfk_1` FOREIGN KEY (`outfit_id`) REFERENCES `tbl_outfit` (`outfit_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tbl_reviews_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `tbl_users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `tbl_subcategory`
--
ALTER TABLE `tbl_subcategory`
  ADD CONSTRAINT `tbl_subcategory_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `tbl_category` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
