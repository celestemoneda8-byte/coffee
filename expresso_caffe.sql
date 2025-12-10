-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 10, 2025 at 02:57 AM
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
-- Database: `expresso_caffe`
--

-- --------------------------------------------------------

--
-- Table structure for table `addons`
--

CREATE TABLE `addons` (
  `addon_id` int(11) NOT NULL,
  `addon_name` varchar(150) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `addons`
--

INSERT INTO `addons` (`addon_id`, `addon_name`, `price`, `image`) VALUES
(1, 'Strawberry Sandwich', 80.00, 'images/strawberry-sandwich.jpg'),
(2, 'Lemonade Cheesecake', 80.00, 'images/mini-lemonade-cheesecake.jpg'),
(3, 'Japanese Cake Roll', 80.00, 'images/japanese-cake-roll.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `cart_addons`
--

CREATE TABLE `cart_addons` (
  `cart_addon_id` int(11) NOT NULL,
  `cart_id` int(11) NOT NULL,
  `addon_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cart_items`
--

CREATE TABLE `cart_items` (
  `cart_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `category_id` int(11) NOT NULL,
  `category_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`category_id`, `category_name`) VALUES
(1, 'Cappuccino'),
(2, 'Espresso'),
(3, 'Mocha'),
(4, 'Latte'),
(5, 'Ice Coffee'),
(6, 'Americano');

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `customer_id` int(11) NOT NULL,
  `fullname` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`customer_id`, `fullname`, `email`, `password`, `created_at`) VALUES
(1, 'rejie rosario', 'rej@gmail.com', '$2y$10$Yw67OeAC.745oRlMALIrqurc0F7H1P4syd2QwEnNDtFUDNsC6UtNW', '2025-12-06 08:32:42');

-- --------------------------------------------------------

--
-- Table structure for table `customer_accounts`
--

CREATE TABLE `customer_accounts` (
  `account_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customer_accounts`
--

INSERT INTO `customer_accounts` (`account_id`, `customer_id`, `address`, `phone`, `updated_at`) VALUES
(1, 1, '442, sitio sipit Brgy. Malacañang San Carlos City, Pangasinan', '09277070626', '2025-12-06 09:18:46');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `order_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `order_status` enum('Pending','On-Delivery','Completed','Cancelled') DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`order_id`, `customer_id`, `total_amount`, `order_status`, `created_at`) VALUES
(1, 1, 0.00, 'Pending', '2025-12-07 14:02:51'),
(2, 1, 0.00, 'Pending', '2025-12-07 14:02:55'),
(3, 1, 0.00, 'Pending', '2025-12-07 14:05:36'),
(4, 1, 0.00, 'Pending', '2025-12-07 14:13:01'),
(5, 1, 0.00, 'Pending', '2025-12-07 14:34:24'),
(6, 1, 0.00, 'Pending', '2025-12-08 13:57:25'),
(7, 1, 0.00, 'Pending', '2025-12-09 03:50:43'),
(8, 1, 0.00, 'Pending', '2025-12-09 09:18:59'),
(9, 1, 0.00, 'Pending', '2025-12-09 09:21:49'),
(10, 1, 230.00, 'Pending', '2025-12-09 10:23:04'),
(11, 1, 230.00, 'Pending', '2025-12-09 10:44:52'),
(12, 1, 230.00, 'Pending', '2025-12-09 10:46:36'),
(13, 1, 150.00, 'Pending', '2025-12-09 10:49:20'),
(14, 1, 150.00, 'Pending', '2025-12-09 10:53:41'),
(15, 1, 150.00, 'Pending', '2025-12-09 10:53:53'),
(16, 1, 150.00, 'Pending', '2025-12-09 10:55:32'),
(17, 1, 150.00, 'Pending', '2025-12-09 10:56:36'),
(18, 1, 150.00, 'Pending', '2025-12-09 11:03:01'),
(19, 1, 230.00, 'Pending', '2025-12-09 11:18:43'),
(20, 1, 230.00, 'Pending', '2025-12-09 11:19:58'),
(21, 1, 150.00, 'Pending', '2025-12-09 11:44:59'),
(22, 1, 150.00, 'Pending', '2025-12-09 16:29:10'),
(23, 1, 230.00, 'Pending', '2025-12-10 01:49:39');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `order_item_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`order_item_id`, `order_id`, `product_id`, `quantity`, `price`) VALUES
(1, 1, 2, 1, 150.00),
(2, 2, 2, 1, 150.00),
(3, 3, 2, 1, 150.00),
(4, 4, 2, 1, 150.00),
(5, 5, 2, 1, 150.00),
(6, 6, 1, 1, 100.00),
(7, 7, 1, 1, 100.00),
(8, 8, 2, 1, 150.00),
(9, 9, 3, 1, 150.00),
(10, 10, 2, 1, 150.00),
(11, 11, 2, 1, 150.00),
(12, 12, 2, 1, 150.00),
(13, 13, 2, 1, 150.00),
(14, 14, 2, 1, 150.00),
(15, 15, 2, 1, 150.00),
(16, 16, 2, 1, 150.00),
(17, 17, 2, 1, 150.00),
(18, 18, 2, 1, 150.00),
(19, 19, 2, 1, 150.00),
(20, 20, 3, 1, 150.00),
(21, 21, 2, 1, 150.00),
(22, 22, 2, 1, 150.00),
(23, 23, 2, 1, 150.00);

-- --------------------------------------------------------

--
-- Table structure for table `order_item_addons`
--

CREATE TABLE `order_item_addons` (
  `order_item_addon_id` int(11) NOT NULL,
  `order_item_id` int(11) NOT NULL,
  `addon_id` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `order_riders`
--

CREATE TABLE `order_riders` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `rider_id` int(11) NOT NULL,
  `status` varchar(64) NOT NULL,
  `assigned_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL,
  `note` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `product_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `product_name` varchar(150) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`product_id`, `category_id`, `product_name`, `price`, `image`) VALUES
(1, 1, 'Classic Cappuccino', 100.00, 'images/classic-cappuccino.jpg'),
(2, 4, 'Eggnog Latte', 150.00, 'images/Eggnog-Latte.jpg'),
(3, 1, 'Hazelnut Cappuccino', 150.00, 'images/Hazelnut-cappuccino.png'),
(4, 4, 'Ice Chai Latte', 100.00, 'images/ice-chai-latte.jpg'),
(5, 5, 'Ice Coffee', 100.00, 'images/ice-coffee.jpg'),
(6, 4, 'Ice Pumpkin Spiced Latte', 150.00, 'images/ice-coffee.jpg'),
(7, 2, 'Ice Shaken Espresso', 100.00, 'images/ice-shaken-espresso.jpg'),
(8, 2, 'Layered Espresso', 100.00, 'images/layered-espresso-drink.jpg'),
(9, 3, 'Mexican Mocha', 150.00, 'images/mexican-mocha.jpg'),
(10, 4, 'Whipped Cream Latte', 150.00, 'images/whipped-cream-latte.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `rider_accounts`
--

CREATE TABLE `rider_accounts` (
  `rider_id` int(11) NOT NULL,
  `rider_name` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `rider_accounts`
--

INSERT INTO `rider_accounts` (`rider_id`, `rider_name`, `email`, `password`, `phone`, `created_at`) VALUES
(1, 'Mariel De Vera', 'mariel@gmail.com', '$2y$10$/KrqNzCibMvgGZgDpeyxI.qEFmkff0UUwdc2cHmkgmWyKXbLhj4ma', '09277070626', '2025-12-08 12:47:13');

-- --------------------------------------------------------

--
-- Table structure for table `rider_orders`
--

CREATE TABLE `rider_orders` (
  `rider_order_id` int(11) NOT NULL,
  `rider_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `order_status` enum('Assigned','Picked Up','Delivered','Cancelled') DEFAULT 'Assigned',
  `dropoff_address` varchar(255) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `addons`
--
ALTER TABLE `addons`
  ADD PRIMARY KEY (`addon_id`);

--
-- Indexes for table `cart_addons`
--
ALTER TABLE `cart_addons`
  ADD PRIMARY KEY (`cart_addon_id`),
  ADD KEY `cart_id` (`cart_id`),
  ADD KEY `addon_id` (`addon_id`);

--
-- Indexes for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD PRIMARY KEY (`cart_id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`category_id`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`customer_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `customer_accounts`
--
ALTER TABLE `customer_accounts`
  ADD PRIMARY KEY (`account_id`),
  ADD KEY `customer_id` (`customer_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`order_id`),
  ADD KEY `customer_id` (`customer_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`order_item_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `order_item_addons`
--
ALTER TABLE `order_item_addons`
  ADD PRIMARY KEY (`order_item_addon_id`),
  ADD KEY `order_item_id` (`order_item_id`),
  ADD KEY `addon_id` (`addon_id`);

--
-- Indexes for table `order_riders`
--
ALTER TABLE `order_riders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id_idx` (`order_id`),
  ADD KEY `rider_id_idx` (`rider_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`product_id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `rider_accounts`
--
ALTER TABLE `rider_accounts`
  ADD PRIMARY KEY (`rider_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `rider_orders`
--
ALTER TABLE `rider_orders`
  ADD PRIMARY KEY (`rider_order_id`),
  ADD KEY `rider_id` (`rider_id`),
  ADD KEY `order_id` (`order_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `addons`
--
ALTER TABLE `addons`
  MODIFY `addon_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `cart_addons`
--
ALTER TABLE `cart_addons`
  MODIFY `cart_addon_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cart_items`
--
ALTER TABLE `cart_items`
  MODIFY `cart_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `customer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `customer_accounts`
--
ALTER TABLE `customer_accounts`
  MODIFY `account_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `order_item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `order_item_addons`
--
ALTER TABLE `order_item_addons`
  MODIFY `order_item_addon_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `order_riders`
--
ALTER TABLE `order_riders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `rider_accounts`
--
ALTER TABLE `rider_accounts`
  MODIFY `rider_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `rider_orders`
--
ALTER TABLE `rider_orders`
  MODIFY `rider_order_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cart_addons`
--
ALTER TABLE `cart_addons`
  ADD CONSTRAINT `cart_addons_ibfk_1` FOREIGN KEY (`cart_id`) REFERENCES `cart_items` (`cart_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_addons_ibfk_2` FOREIGN KEY (`addon_id`) REFERENCES `addons` (`addon_id`) ON DELETE CASCADE;

--
-- Constraints for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD CONSTRAINT `cart_items_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE;

--
-- Constraints for table `customer_accounts`
--
ALTER TABLE `customer_accounts`
  ADD CONSTRAINT `customer_accounts_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE CASCADE;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE;

--
-- Constraints for table `order_item_addons`
--
ALTER TABLE `order_item_addons`
  ADD CONSTRAINT `order_item_addons_ibfk_1` FOREIGN KEY (`order_item_id`) REFERENCES `order_items` (`order_item_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_item_addons_ibfk_2` FOREIGN KEY (`addon_id`) REFERENCES `addons` (`addon_id`) ON DELETE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`) ON DELETE CASCADE;

--
-- Constraints for table `rider_orders`
--
ALTER TABLE `rider_orders`
  ADD CONSTRAINT `rider_orders_ibfk_1` FOREIGN KEY (`rider_id`) REFERENCES `rider_accounts` (`rider_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `rider_orders_ibfk_2` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
