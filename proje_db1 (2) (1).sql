-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Anamakine: 127.0.0.1
-- Üretim Zamanı: 22 Ara 2025, 10:47:00
-- Sunucu sürümü: 10.4.32-MariaDB
-- PHP Sürümü: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Veritabanı: `proje_db1`
--

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `favorites`
--

CREATE TABLE `favorites` (
  `id` int(11) UNSIGNED NOT NULL,
  `user_id` int(11) UNSIGNED NOT NULL,
  `market_price_id` int(11) UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `favorites`
--

INSERT INTO `favorites` (`id`, `user_id`, `market_price_id`, `created_at`) VALUES
(1, 3, 5, '2025-12-16 13:30:14'),
(2, 3, 3, '2025-12-16 13:30:19');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `markets`
--

CREATE TABLE `markets` (
  `id` int(11) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `markets`
--

INSERT INTO `markets` (`id`, `name`, `created_at`) VALUES
(1, 'Migros', '2025-12-16 15:58:48'),
(2, 'BİM', '2025-12-16 15:58:48'),
(3, 'Şok Market', '2025-12-16 15:58:48'),
(4, 'A101', '2025-12-16 15:58:48');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `market_prices`
--

CREATE TABLE `market_prices` (
  `id` int(11) UNSIGNED NOT NULL,
  `market_id` int(11) UNSIGNED NOT NULL,
  `product_id` int(11) UNSIGNED NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `discount_rate` decimal(5,2) DEFAULT 0.00,
  `stock` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `market_prices`
--

INSERT INTO `market_prices` (`id`, `market_id`, `product_id`, `price`, `discount_rate`, `stock`, `created_at`) VALUES
(3, 1, 2, 45.00, 0.00, 80, '2025-12-16 15:59:03'),
(4, 3, 2, 89.95, 0.00, 25, '2025-12-16 15:59:03'),
(5, 4, 3, 19.99, 0.00, 50, '2025-12-16 15:59:03'),
(6, 2, 4, 150.00, 10.00, 6, '2025-12-16 15:59:03'),
(9, 4, 7, 59.95, 0.00, 25, '2025-12-16 17:54:25'),
(10, 1, 7, 60.99, 0.00, 30, '2025-12-16 17:54:38'),
(11, 1, 6, 159.90, 0.00, 40, '2025-12-16 17:55:56'),
(12, 2, 6, 139.95, 0.00, 18, '2025-12-16 17:56:18'),
(13, 4, 2, 95.00, 16.00, 15, '2025-12-16 18:00:20'),
(14, 1, 8, 99.95, 0.00, 14, '2025-12-16 18:02:50'),
(15, 3, 8, 79.96, 0.00, 15, '2025-12-16 18:03:12'),
(16, 2, 9, 39.50, 0.00, 30, '2025-12-16 18:05:45'),
(17, 1, 9, 42.99, 0.00, 20, '2025-12-16 18:05:58'),
(18, 3, 9, 40.99, 0.00, 25, '2025-12-16 18:06:08'),
(19, 4, 9, 39.99, 0.00, 20, '2025-12-16 18:06:19'),
(20, 2, 10, 100.99, 0.00, 50, '2025-12-16 18:07:36'),
(21, 1, 10, 109.99, 0.00, 40, '2025-12-16 18:07:48'),
(22, 4, 10, 102.99, 0.00, 35, '2025-12-16 18:08:02'),
(23, 1, 11, 89.95, 0.00, 25, '2025-12-16 18:09:24'),
(24, 2, 11, 88.99, 0.00, 9, '2025-12-16 18:09:45'),
(25, 4, 11, 89.99, 0.00, 15, '2025-12-16 18:10:05'),
(26, 4, 12, 34.90, 0.00, 13, '2025-12-16 18:14:30'),
(27, 1, 12, 50.95, 0.00, 20, '2025-12-16 18:14:48'),
(28, 3, 12, 42.99, 0.00, 20, '2025-12-16 18:15:01'),
(29, 4, 13, 3.50, 0.00, 100, '2025-12-16 18:17:11'),
(30, 2, 13, 2.99, 0.00, 150, '2025-12-16 18:17:21'),
(31, 1, 13, 9.99, 0.00, 120, '2025-12-16 18:17:33'),
(32, 3, 13, 5.99, 0.00, 131, '2025-12-16 18:17:41'),
(33, 1, 14, 66.00, 10.00, 75, '2025-12-16 18:19:14'),
(34, 2, 14, 59.99, 0.00, 65, '2025-12-16 18:19:22'),
(35, 4, 14, 65.00, 0.00, 58, '2025-12-16 18:19:33'),
(36, 3, 14, 70.00, 20.00, 8, '2025-12-16 18:19:57'),
(37, 1, 15, 74.50, 0.00, 60, '2025-12-16 18:21:37'),
(38, 2, 15, 52.45, 0.00, 23, '2025-12-16 18:21:49'),
(39, 3, 15, 55.50, 10.00, 20, '2025-12-16 18:22:06'),
(40, 4, 15, 55.00, 0.00, 15, '2025-12-16 18:22:24'),
(41, 1, 16, 164.90, 0.00, 24, '2025-12-16 18:23:35'),
(42, 2, 16, 200.00, 15.00, 17, '2025-12-16 18:23:51'),
(43, 4, 16, 169.99, 0.00, 20, '2025-12-16 18:24:05'),
(44, 3, 16, 180.00, 10.00, 55, '2025-12-16 18:24:46'),
(45, 1, 17, 64.95, 0.00, 25, '2025-12-16 18:26:30'),
(46, 2, 17, 70.00, 15.00, 30, '2025-12-16 18:26:59'),
(47, 3, 17, 65.95, 0.00, 18, '2025-12-16 18:27:13'),
(48, 4, 17, 75.00, 25.00, 10, '2025-12-16 18:28:16'),
(49, 1, 18, 54.99, 0.00, 45, '2025-12-16 18:29:37'),
(50, 3, 18, 67.00, 25.00, 0, '2025-12-16 18:30:21'),
(51, 4, 18, 60.99, 0.00, 50, '2025-12-16 18:30:36'),
(52, 1, 19, 101.20, 0.00, 30, '2025-12-16 18:31:53'),
(53, 2, 19, 100.00, 15.00, 8, '2025-12-16 18:32:25'),
(54, 3, 19, 88.95, 0.00, 15, '2025-12-16 18:32:36'),
(55, 4, 19, 90.99, 0.00, 20, '2025-12-16 18:32:54');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `products`
--

CREATE TABLE `products` (
  `id` int(11) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `category` varchar(100) NOT NULL,
  `barcode` varchar(50) DEFAULT NULL,
  `image_url` varchar(255) DEFAULT 'default.jpg',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `products`
--

INSERT INTO `products` (`id`, `name`, `category`, `barcode`, `image_url`, `created_at`) VALUES
(2, 'Yumurta (10lu)', 'Kahvaltılık', '869000000002', 'uploads/products/prod_694173827e2a1.jpg', '2025-12-16 15:58:55'),
(3, 'Domates 1KG', 'Sebze Meyve', '869000000003', 'uploads/products/prod_6941706c56caa.png', '2025-12-16 15:58:55'),
(4, 'Tuvalet Kağıdı (32&#039;li)', 'Temizlik', '869000000004', 'uploads/products/prod_694179e0c1219.jpg', '2025-12-16 15:58:55'),
(6, 'İçim Tam Yağlı Beyaz Peynir (500g)', 'Süt Ürünleri', '8690011110022', 'uploads/products/prod_694172eb0f478.jpg', '2025-12-16 17:53:50'),
(7, 'Pınar Tam Yağlı Süt (1L)', 'Süt Ürünleri', '8690011110015', 'uploads/products/prod_6941729743174.jpg', '2025-12-16 17:54:15'),
(8, 'Tat Çilek Reçeli(380g)', 'Kahvaltılık', '8690011110046', 'uploads/products/prod_694174867b466.jpg', '2025-12-16 18:02:30'),
(9, 'Kristal Toz Şeker (1kg)', 'Temel Gıda', '8690011110053', 'uploads/products/prod_6941752b436bb.jpg', '2025-12-16 18:04:27'),
(10, 'Kırmızı Elma (1kg)', 'Meyve', '8690011110060', 'uploads/products/prod_6941759ea5883.jpg', '2025-12-16 18:06:46'),
(11, 'Muz (1kg)', 'Meyve', '8690011110077', 'uploads/products/prod_6941761662eba.jpg', '2025-12-16 18:08:43'),
(12, 'Göbek Marul(Adet)', 'Sebze', '8690011110091', 'uploads/products/prod_694177479dba7.jpg', '2025-12-16 18:10:40'),
(13, 'Doğal Kaynak Suyu (500ml)', 'İçecek', '8690011110107', 'uploads/products/prod_694177ecb1b31.jpg', '2025-12-16 18:15:38'),
(14, 'Coca Cola(1Lt)', 'İçecek', '8690011110114', 'uploads/products/prod_69417858e87c3.jpg', '2025-12-16 18:18:35'),
(15, 'Cips (Büyük Boy)', 'Atıştırmalık', '8690011110121', 'uploads/products/prod_694178ee17954.jpg', '2025-12-16 18:20:48'),
(16, 'Çamaşır Deterjanı (1.5kg)', 'Temizlik', '8690011110183', 'uploads/products/prod_69417961a66b6.jpg', '2025-12-16 18:22:55'),
(17, 'Yüzey Temizleyici (1L)', 'Temizlik', '8690011110190', 'uploads/products/prod_69417a07b6148.jpg', '2025-12-16 18:25:59'),
(18, 'Diş Fırçası (Tekli)', 'Kişisel Bakım', '8690011110206', 'uploads/products/prod_69417ad3c3843.jpg', '2025-12-16 18:29:23'),
(19, 'Sıvı Sabun (500ml)', 'Kişisel Bakım', '8690011110213', 'uploads/products/prod_69417b544ba23.jpeg', '2025-12-16 18:31:32');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `shopping_list`
--

CREATE TABLE `shopping_list` (
  `id` int(11) UNSIGNED NOT NULL,
  `user_id` int(11) UNSIGNED NOT NULL,
  `market_price_id` int(11) UNSIGNED NOT NULL,
  `quantity` int(11) NOT NULL,
  `status` enum('pending','completed') DEFAULT 'pending',
  `bought_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `shopping_list`
--

INSERT INTO `shopping_list` (`id`, `user_id`, `market_price_id`, `quantity`, `status`, `bought_at`, `created_at`) VALUES
(1, 3, 6, 3, 'completed', '2025-12-16 16:47:53', '2025-12-16 16:47:50'),
(2, 3, 32, 1, 'completed', '2025-12-20 16:50:44', '2025-12-20 16:50:30'),
(3, 3, 6, 1, 'completed', '2025-12-20 16:51:26', '2025-12-20 16:51:24'),
(4, 3, 24, 1, 'completed', '2025-12-22 12:45:43', '2025-12-22 12:45:40');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `users`
--

CREATE TABLE `users` (
  `id` int(11) UNSIGNED NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
  `is_admin` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `users`
--

INSERT INTO `users` (`id`, `password`, `email`, `name`, `is_admin`, `created_at`) VALUES
(2, '$2y$10$KTwEpMwnCwMWm13DEYtE0uyc3pqzpWsEykfUfQoe9dJ3ssPQKMf7S', 'm.emir16@outlook.com', 'Mehmet Emir Yurt', 0, '2025-12-16 16:00:37'),
(3, '$2y$10$xYaUBTa6hncmM5B01Poaqu29sHwcDnJ69DkrmnNcxqG.KzF.JfP66', 'admin@admin.com', 'admin', 1, '2025-12-16 16:04:14');

--
-- Dökümü yapılmış tablolar için indeksler
--

--
-- Tablo için indeksler `favorites`
--
ALTER TABLE `favorites`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_favorite_mp` (`user_id`,`market_price_id`),
  ADD KEY `market_price_id` (`market_price_id`);

--
-- Tablo için indeksler `markets`
--
ALTER TABLE `markets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Tablo için indeksler `market_prices`
--
ALTER TABLE `market_prices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_market_product` (`market_id`,`product_id`),
  ADD KEY `fk_product_id` (`product_id`);

--
-- Tablo için indeksler `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `barcode` (`barcode`);

--
-- Tablo için indeksler `shopping_list`
--
ALTER TABLE `shopping_list`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_sl_user_id` (`user_id`),
  ADD KEY `fk_sl_market_price_id` (`market_price_id`);

--
-- Tablo için indeksler `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Dökümü yapılmış tablolar için AUTO_INCREMENT değeri
--

--
-- Tablo için AUTO_INCREMENT değeri `favorites`
--
ALTER TABLE `favorites`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Tablo için AUTO_INCREMENT değeri `markets`
--
ALTER TABLE `markets`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Tablo için AUTO_INCREMENT değeri `market_prices`
--
ALTER TABLE `market_prices`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=56;

--
-- Tablo için AUTO_INCREMENT değeri `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- Tablo için AUTO_INCREMENT değeri `shopping_list`
--
ALTER TABLE `shopping_list`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Tablo için AUTO_INCREMENT değeri `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Dökümü yapılmış tablolar için kısıtlamalar
--

--
-- Tablo kısıtlamaları `favorites`
--
ALTER TABLE `favorites`
  ADD CONSTRAINT `favorites_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `favorites_ibfk_2` FOREIGN KEY (`market_price_id`) REFERENCES `market_prices` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `market_prices`
--
ALTER TABLE `market_prices`
  ADD CONSTRAINT `fk_market_id` FOREIGN KEY (`market_id`) REFERENCES `markets` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_product_id` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `shopping_list`
--
ALTER TABLE `shopping_list`
  ADD CONSTRAINT `fk_sl_market_price_id` FOREIGN KEY (`market_price_id`) REFERENCES `market_prices` (`id`),
  ADD CONSTRAINT `fk_sl_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
