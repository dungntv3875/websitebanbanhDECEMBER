-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Máy chủ: 127.0.0.1
-- Thời gian đã tạo: Th9 07, 2026 lúc 09:36 AM
-- Phiên bản máy phục vụ: 10.4.32-MariaDB
-- Phiên bản PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Cơ sở dữ liệu: `donut_december`
--

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `admin_chats`
--

CREATE TABLE `admin_chats` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `sender` enum('user','admin') NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `cart`
--

CREATE TABLE `cart` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `quantity` int(11) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `parent_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Danh mục bánh
--

INSERT INTO `categories` (`id`, `name`, `parent_id`) VALUES
(1, 'october', NULL),
(2, 'march', NULL),
(3, 'june', NULL),
(4, 'special', NULL),
(5, 'september', NULL);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `favorites`
--

CREATE TABLE `favorites` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `fullname` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `note` text DEFAULT NULL,
  `total` int(11) DEFAULT NULL,
  `payment_method` varchar(20) DEFAULT 'COD',
  `status` varchar(50) DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `order_cancellations`
--

CREATE TABLE `order_cancellations` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `reason` varchar(255) NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `admin_note` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `price` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `order_returns`
--

CREATE TABLE `order_returns` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `reason` varchar(255) NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `admin_note` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `price` int(11) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `stock` int(11) DEFAULT 50,
  `sale_price` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

INSERT INTO `products` (`id`, `category_id`, `name`, `price`, `image`, `description`) VALUES
(1, 1, 'Creme Brulee', 39000, '1.jpg', 'Bánh donut nhân kem custard, phủ đường khò giòn thơm mùi caramel.'),
(2, 1, 'Bơ Già Dừa Non', 42000, '2.jpg', 'Bánh donut nhân kem bơ, kết hợp kem dừa và vụn dừa khô.'),
(3, 1, 'Mango Bloom', 42000, '3.jpg', 'Bánh donut nhân kem xoài, kết hợp xoài tươi và xoài sấy dẻo.'),
(4, 1, 'Milky Matcha', 45000, '4.jpg', 'Bánh donut nhân kem sữa matcha, thơm vị trà xanh.'),
(5, 1, 'Mojito Donut', 42000, '5.jpg', 'Bánh donut nhân kem chanh bạc hà, kết hợp cream cheese.'),
(6, 1, 'Golden Crumble', 39000, '6.jpg', 'Bánh donut phủ sốt bơ, kết hợp vụn phủ giòn thơm.'),
(7, 1, 'Oreo Mochi', 45000, '7.jpg', 'Bánh donut nhân kem Oreo, kết hợp mochi dẻo và cream cheese.'),
(8, 1, 'Tiramisu', 39000, '8.jpg', 'Bánh donut nhân kem phô mai, phủ socola và bột cacao.'),
(9, 1, 'Ham & Cajun', 42000, '9.jpg', 'Bánh donut mặn phủ sốt mayonnaise, thịt nguội và sốt Cajun.'),
(10, 1, 'Cốm Sữa Chua', 45000, '10.jpg', 'Bánh donut nhân kem sữa chua, kết hợp cốm dẻo.'),
(11, 1, 'Eggspresso', 42000, '11.jpg', 'Bánh donut nhân kem trứng, kết hợp kem cà phê.'),
(12, 1, 'Choco Divo', 42000, '12.jpg', 'Bánh donut nhân kem chocolate, phủ socola đen và vụn brownie.'),
(13, 5, 'Garlic Donuts', 39000, '13.jpg', 'Bánh vòng phủ sốt bơ tỏi, cream cheese ăn kèm.'),
(14, 5, 'Figgy Jam', 42000, '14.jpg', 'Bánh nhân kem custard sung, mứt sung và sung sấy.'),
(15, 5, 'Cosy Bites', 39000, '15.jpg', 'Bánh nhân kem Cosy, phủ vụn bánh quy.'),
(16, 5, 'Matcha Brownies', 45000, '16.jpg', 'Bánh vòng phủ kem mascarpone, vụn brownie matcha.'),
(17, 5, 'Berry Mochi', 47000, '17.jpg', 'Bánh nhân cream cheese, kem mứt dâu, bọc mochi dẻo.'),
(18, 5, 'Choco Brownies', 37000, '18.jpg', 'Bánh vòng phủ kem chocolate, vụn brownie.'),
(19, 5, 'Banana Caramel', 39000, '19.jpg', 'Bánh vòng phủ kem chuối, sốt caramel và chuối tươi.'),
(20, 5, 'Chicken Floss', 45000, '20.jpg', 'Bánh nhân kem phô mai, ăn kèm chà bông gà.'),
(21, 5, 'Black Sesame', 39000, '21.jpg', 'Bánh nhân kem mè đen, phủ sốt mè đen.'),
(22, 4, 'Phô Mai Lắc', 32000, '22.jpg', 'Bánh vòng phủ phô mai lắc.'),
(23, 4, 'Trà Thái Xanh', 37000, '23.jpg', 'Bánh nhân kem trà Thái xanh.'),
(24, 4, 'Miso Caramel', 37000, '24.jpg', 'Bánh nhân kem tươi, phủ miso caramel mặn ngọt.'),
(25, 4, 'Matchareo', 45000, '25.jpg', 'Bánh vòng nhân cream cheese matcha Nhật, trang trí Oreo.'),
(26, 4, 'Kem Trà Mặn', 42000, '26.jpg', 'Bánh nhân kem hồng trà, phủ sốt cheese mặn.'),
(27, 4, 'Milo Volcano', 42000, '27.jpg', 'Bánh nhân kem Milo, phủ vụn crumble Milo.'),
(28, 4, 'Biscoff Donuts', 37000, '28.jpg', 'Bánh vòng phủ sốt Lotus và vụn bánh quy.'),
(29, 4, 'Obay Sausage', 45000, '29.jpg', 'Bánh mặn phủ sốt phô mai, xúc xích và sốt Obay.'),
(30, 4, 'Blueberry Bliss', 42000, '30.jpg', 'Bánh vòng nhân kem cheese, kem việt quất và việt quất tươi.'),
(31, 4, 'Cheesy Lemon', 39000, '31.jpg', 'Bánh nhân kem phô mai chanh.'),
(32, 3, 'Plum Donuts', 39000, '32.jpg', 'Bánh nhân kem tươi, mứt mận và mận tươi.'),
(33, 3, 'Coconut Lime', 42000, '33.jpg', 'Bánh nhân kem dừa, custard chanh và mứt xoài.'),
(34, 3, 'Ham Deluxe', 39000, '34.jpg', 'Bánh mặn phủ phô mai lát và dăm bông.'),
(35, 3, 'Cheddar BBQ', 42000, '35.jpg', 'Bánh mặn phủ sốt phô mai, thịt nguội khô, BBQ và cheddar bào.'),
(36, 3, 'Jasmine Tea', 39000, '36.jpg', 'Bánh vòng nhân kem trà nhài, nhãn tươi trang trí.'),
(37, 3, 'Tropical Duo', 42000, '37.jpg', 'Bánh nhân kem chanh leo và mứt xoài.'),
(38, 2, 'Mango Plum', 35000, '38.jpg', 'Bánh vòng phủ sốt thanh trà.'),
(39, 2, 'Hojicha Brownies', 37000, '39.jpg', 'Bánh vòng nhân kem trà hojicha, phủ socola và vụn brownie hojicha.'),
(40, 2, 'Cheezy BBQ', 45000, '40.jpg', 'Bánh vòng phủ phô mai, thịt nguội ướp sốt BBQ.'),
(41, 2, 'Matcha with Love', 42000, '41.jpg', 'Bánh nhân custard matcha, brownie matcha.'),
(42, 2, 'Lemon Custard', 39000, '42.jpg', 'Bánh nhân custard chanh vàng.'),
(43, 2, 'Salted Caramel', 35000, '43.jpg', 'Bánh vòng phủ caramel muối.'),
(44, 2, 'Injeolmi Cream', 37000, '44.jpg', 'Bánh nhân kem đậu nành rang.'),
(45, 2, 'Pink Heart', 39000, '45.jpg', 'Bánh vòng nhân cream cheese dâu, phủ socola hồng và bánh quy.'),
(46, 2, 'Biscoff Crumbs', 39000, '46.jpg', 'Bánh vòng phủ sốt bánh quy Lotus.'),
(47, 2, 'Hazel S''more', 39000, '47.jpg', 'Bánh nhân kem hạt phỉ, phủ socola và marshmallow khô.');


--
-- Cấu trúc bảng cho bảng `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `rating` tinyint(4) NOT NULL,
  `comment` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `role` enum('admin','user') DEFAULT 'user',
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `is_active`) VALUES
(4, 'Trần Ngọc Ánh', 'anh1826@gmail.com', '$2y$10$aoQqFxeFgoCVZ9Zc31a1a.51SHoY17dieaJXuvmFWdqZHgvvShTNO', 'admin', 1),
(5, 'Quản trị viên', 'admin@gmail.com', '$2y$10$DYotty.tGEY6DtqXs/VOuuohfLJxK4D2puNVm.rRCe8rdc1GMHJFS', 'admin', 1),
(6, 'Ánh Trần Ngọc', 'tranngocanh@gmail.com', '$2y$10$aFGhFcz8nm91TV56qrsMQeoVLXCxJDPCWOyokOf3/r25M/YpLaxHW', 'user', 1),
(7, 'Hưng', 'nqh@gmail.com', '$2y$10$3RiQLk36mX7kdiyVoUyoxOzaqjMqtmL1PzxDMAeB5nNS/b4yb2zaG', 'user', 1),
(8, 'Hưng', 'nqh1234@gmail.com', '$2y$10$5Jgz6ocUmjD4VQ6foQyw7.xCEqrXlhnUJEPN5T/kpJ23LZMav9gVq', 'user', 1),
(9, 'Ngọc Ánh', 'tranngocanh123@gmail.com', '$2y$10$RzXUaqh0QlBBah34p.XrmuoZ/d8.aOGw7.jVrfsSiNiUatGlCLUK2', 'user', 1),
(10, 'Ngọc Ánh', 'tranngocanh1234@gmail.com', '$2y$10$UGMOUjTNTekIIDpGj6EW.OlggQqhwekyRHfhrfOJr5wP47vf0nu1q', 'user', 1);

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `admin_chats`
--
ALTER TABLE `admin_chats`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_created` (`created_at`);

--
-- Chỉ mục cho bảng `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_parent` (`parent_id`);

--
-- Chỉ mục cho bảng `favorites`
--
ALTER TABLE `favorites`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `order_cancellations`
--
ALTER TABLE `order_cancellations`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `order_returns`
--
ALTER TABLE `order_returns`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_review` (`product_id`,`user_id`,`order_id`);

--
-- Chỉ mục cho bảng `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT cho các bảng đã đổ
--

--
-- AUTO_INCREMENT cho bảng `admin_chats`
--
ALTER TABLE `admin_chats`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `cart`
--
ALTER TABLE `cart`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT cho bảng `favorites`
--
ALTER TABLE `favorites`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `order_cancellations`
--
ALTER TABLE `order_cancellations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `order_returns`
--
ALTER TABLE `order_returns`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Các ràng buộc cho các bảng đã đổ
--

--
-- Các ràng buộc cho bảng `categories`
--
ALTER TABLE `categories`
  ADD CONSTRAINT `fk_parent` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
