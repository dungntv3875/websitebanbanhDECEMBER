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
-- Cơ sở dữ liệu: `websitebanbanhDECEMBER`
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

--
-- Đang đổ dữ liệu cho bảng `admin_chats`
--

INSERT INTO `admin_chats` (`id`, `user_id`, `sender`, `message`, `is_read`, `created_at`) VALUES
(1, 3, 'user', 'Shop ơi, Creme Brulee còn hàng không?', 1, NOW() - INTERVAL 18 MINUTE),
(2, 3, 'admin', 'Dạ sản phẩm hiện còn hàng ạ.', 1, NOW() - INTERVAL 17 MINUTE),
(3, 4, 'user', 'Bánh có giao trong ngày không shop?', 1, NOW() - INTERVAL 16 MINUTE),
(4, 4, 'admin', 'Dạ shop có hỗ trợ giao hàng trong ngày ở khu vực nội thành.', 1, NOW() - INTERVAL 15 MINUTE),
(5, 3, 'user', 'Mình muốn hỏi vị Matcha.', 1, NOW() - INTERVAL 14 MINUTE),
(6, 3, 'admin', 'Matcha có vị trà thơm nhẹ, không quá ngọt ạ.', 1, NOW() - INTERVAL 13 MINUTE),
(7, 4, 'user', 'Shop có chương trình khuyến mãi không?', 1, NOW() - INTERVAL 12 MINUTE),
(8, 4, 'admin', 'Dạ bạn xem mục Khuyến mãi trên website để cập nhật ưu đãi mới nhất nhé.', 1, NOW() - INTERVAL 11 MINUTE),
(9, 3, 'user', 'Bánh được đóng gói như thế nào?', 1, NOW() - INTERVAL 10 MINUTE),
(10, 3, 'admin', 'Bánh được đóng hộp riêng để giữ hình thức khi giao ạ.', 1, NOW() - INTERVAL 9 MINUTE),
(11, 4, 'user', 'Mình có thể đặt nhiều vị trong một đơn không?', 1, NOW() - INTERVAL 8 MINUTE),
(12, 4, 'admin', 'Dạ được, bạn thêm từng vị vào giỏ hàng rồi đặt chung nhé.', 1, NOW() - INTERVAL 7 MINUTE),
(13, 3, 'user', 'Bánh có thành phần sữa không shop?', 1, NOW() - INTERVAL 6 MINUTE),
(14, 3, 'admin', 'Một số loại có thành phần sữa, bạn nhắn tên bánh để shop kiểm tra cụ thể nhé.', 1, NOW() - INTERVAL 5 MINUTE),
(15, 4, 'user', 'Mình muốn đổi địa chỉ nhận hàng.', 1, NOW() - INTERVAL 4 MINUTE),
(16, 4, 'admin', 'Bạn gửi mã đơn và địa chỉ mới để shop hỗ trợ nhé.', 1, NOW() - INTERVAL 3 MINUTE),
(17, 3, 'user', 'Shop tư vấn giúp mình vị bán chạy với.', 0, NOW() - INTERVAL 2 MINUTE),
(18, 3, 'admin', 'Dạ Creme Brulee và Tiramisu là hai vị được nhiều khách yêu thích ạ.', 0, NOW() - INTERVAL 1 MINUTE);


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

--
-- Đang đổ dữ liệu cho bảng `cart`
--

INSERT INTO `cart` (`id`, `user_id`, `product_id`, `quantity`) VALUES
(1, 4, 4, 2);


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
-- Đang đổ dữ liệu cho bảng `categories`
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

--
-- Đang đổ dữ liệu cho bảng `favorites`
--

INSERT INTO `favorites` (`id`, `user_id`, `product_id`) VALUES
(1, 3, 1),
(2, 4, 3),
(3, 3, 2),
(4, 4, 4),
(5, 3, 5),
(6, 4, 6),
(7, 3, 7),
(8, 4, 8),
(9, 3, 9),
(10, 4, 10),
(11, 3, 11),
(12, 4, 12),
(13, 3, 13),
(14, 4, 14),
(15, 3, 15),
(16, 4, 16),
(17, 3, 17),
(18, 4, 18),
(19, 3, 19),
(20, 4, 20);


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
  `payment_status` varchar(20) NOT NULL DEFAULT 'unpaid',
  `status` varchar(50) DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `orders`
--

INSERT INTO `orders`
(`id`, `user_id`, `fullname`, `phone`, `address`, `note`, `total`, `payment_method`, `payment_status`, `status`, `created_at`) VALUES
(1, 3, 'Mai Nguyễn Thùy Dương', '0933658922', '79 Hồ Tùng Mậu, phường Mai Dịch, quận Cầu Giấy, Hà Nội', 'Gọi trước khi giao giúp mình.', 108000, 'COD', 'paid', 'done', NOW() - INTERVAL 31 DAY),
(2, 4, 'Nguyễn Thị Mai Hoa', '0337812339', '130 Xuân Thủy, phường Dịch Vọng Hậu, quận Cầu Giấy, Hà Nội', '', 72000, 'COD', 'unpaid', 'pending', NOW() - INTERVAL 30 DAY),
(3, 1, 'Nguyễn Thị Vân Dung', '0338766389', '80 Trần Duy Hưng, phường Trung Hòa, quận Cầu Giấy, Hà Nội', '', 114000, 'BANKING', 'paid', 'done', NOW() - INTERVAL 29 DAY),
(4, 3, 'Mai Nguyễn Thùy Dương', '0933658922', '79 Hồ Tùng Mậu, phường Mai Dịch, quận Cầu Giấy, Hà Nội', '', 75000, 'COD', 'unpaid', 'confirmed', NOW() - INTERVAL 28 DAY),
(5, 4, 'Nguyễn Thị Mai Hoa', '0337812339', '130 Xuân Thủy, phường Dịch Vọng Hậu, quận Cầu Giấy, Hà Nội', 'Giao trước 12 giờ nếu được.', 156000, 'BANKING', 'paid', 'pending', NOW() - INTERVAL 27 DAY),
(6, 1, 'Nguyễn Thị Vân Dung', '0338766389', '80 Trần Duy Hưng, phường Trung Hòa, quận Cầu Giấy, Hà Nội', '', 114000, 'COD', 'paid', 'shipping', NOW() - INTERVAL 26 DAY),
(7, 3, 'Mai Nguyễn Thùy Dương', '0933658922', '79 Hồ Tùng Mậu, phường Mai Dịch, quận Cầu Giấy, Hà Nội', '', 69000, 'COD', 'unpaid', 'pending', NOW() - INTERVAL 25 DAY),
(8, 4, 'Nguyễn Thị Mai Hoa', '0337812339', '130 Xuân Thủy, phường Dịch Vọng Hậu, quận Cầu Giấy, Hà Nội', '', 72000, 'BANKING', 'paid', 'pending', NOW() - INTERVAL 24 DAY),
(9, 1, 'Nguyễn Thị Vân Dung', '0338766389', '80 Trần Duy Hưng, phường Trung Hòa, quận Cầu Giấy, Hà Nội', '', 120000, 'COD', 'unpaid', 'pending', NOW() - INTERVAL 23 DAY),
(10, 3, 'Mai Nguyễn Thùy Dương', '0933658922', '79 Hồ Tùng Mậu, phường Mai Dịch, quận Cầu Giấy, Hà Nội', 'Ghi lời chúc sinh nhật giúp mình.', 72000, 'COD', 'paid', 'done', NOW() - INTERVAL 22 DAY),
(11, 4, 'Nguyễn Thị Mai Hoa', '0337812339', '130 Xuân Thủy, phường Dịch Vọng Hậu, quận Cầu Giấy, Hà Nội', '', 111000, 'BANKING', 'paid', 'confirmed', NOW() - INTERVAL 21 DAY),
(12, 1, 'Nguyễn Thị Vân Dung', '0338766389', '80 Trần Duy Hưng, phường Trung Hòa, quận Cầu Giấy, Hà Nội', '', 69000, 'COD', 'paid', 'done', NOW() - INTERVAL 20 DAY),
(13, 3, 'Mai Nguyễn Thùy Dương', '0933658922', '79 Hồ Tùng Mậu, phường Mai Dịch, quận Cầu Giấy, Hà Nội', '', 72000, 'COD', 'paid', 'done', NOW() - INTERVAL 19 DAY),
(14, 4, 'Nguyễn Thị Mai Hoa', '0337812339', '130 Xuân Thủy, phường Dịch Vọng Hậu, quận Cầu Giấy, Hà Nội', '', 69000, 'COD', 'unpaid', 'cancelled', NOW() - INTERVAL 18 DAY),
(15, 1, 'Nguyễn Thị Vân Dung', '0338766389', '80 Trần Duy Hưng, phường Trung Hòa, quận Cầu Giấy, Hà Nội', '', 120000, 'BANKING', 'paid', 'done', NOW() - INTERVAL 17 DAY),
(16, 3, 'Mai Nguyễn Thùy Dương', '0933658922', '79 Hồ Tùng Mậu, phường Mai Dịch, quận Cầu Giấy, Hà Nội', 'Không thêm dao nĩa dùng một lần.', 77000, 'COD', 'paid', 'done', NOW() - INTERVAL 16 DAY),
(17, 4, 'Nguyễn Thị Mai Hoa', '0337812339', '130 Xuân Thủy, phường Dịch Vọng Hậu, quận Cầu Giấy, Hà Nội', '', 67000, 'COD', 'unpaid', 'pending', NOW() - INTERVAL 15 DAY),
(18, 1, 'Nguyễn Thị Vân Dung', '0338766389', '80 Trần Duy Hưng, phường Trung Hòa, quận Cầu Giấy, Hà Nội', '', 114000, 'BANKING', 'paid', 'cancelled', NOW() - INTERVAL 14 DAY),
(19, 3, 'Mai Nguyễn Thùy Dương', '0933658922', '79 Hồ Tùng Mậu, phường Mai Dịch, quận Cầu Giấy, Hà Nội', '', 108000, 'COD', 'unpaid', 'pending', NOW() - INTERVAL 13 DAY),
(20, 4, 'Nguyễn Thị Mai Hoa', '0337812339', '130 Xuân Thủy, phường Dịch Vọng Hậu, quận Cầu Giấy, Hà Nội', '', 62000, 'COD', 'unpaid', 'cancelled', NOW() - INTERVAL 12 DAY),
(21, 1, 'Nguyễn Thị Vân Dung', '0338766389', '80 Trần Duy Hưng, phường Trung Hòa, quận Cầu Giấy, Hà Nội', '', 67000, 'BANKING', 'paid', 'pending', NOW() - INTERVAL 11 DAY),
(22, 3, 'Mai Nguyễn Thùy Dương', '0933658922', '79 Hồ Tùng Mậu, phường Mai Dịch, quận Cầu Giấy, Hà Nội', '', 67000, 'COD', 'unpaid', 'shipping', NOW() - INTERVAL 10 DAY),
(23, 4, 'Nguyễn Thị Mai Hoa', '0337812339', '130 Xuân Thủy, phường Dịch Vọng Hậu, quận Cầu Giấy, Hà Nội', 'Giao sau 17 giờ.', 75000, 'COD', 'paid', 'done', NOW() - INTERVAL 9 DAY),
(24, 1, 'Nguyễn Thị Vân Dung', '0338766389', '80 Trần Duy Hưng, phường Trung Hòa, quận Cầu Giấy, Hà Nội', '', 114000, 'BANKING', 'paid', 'done', NOW() - INTERVAL 8 DAY),
(25, 3, 'Mai Nguyễn Thùy Dương', '0933658922', '79 Hồ Tùng Mậu, phường Mai Dịch, quận Cầu Giấy, Hà Nội', '', 72000, 'COD', 'paid', 'done', NOW() - INTERVAL 7 DAY),
(26, 4, 'Nguyễn Thị Mai Hoa', '0337812339', '130 Xuân Thủy, phường Dịch Vọng Hậu, quận Cầu Giấy, Hà Nội', '', 67000, 'COD', 'paid', 'done', NOW() - INTERVAL 6 DAY),
(27, 1, 'Nguyễn Thị Vân Dung', '0338766389', '80 Trần Duy Hưng, phường Trung Hòa, quận Cầu Giấy, Hà Nội', '', 75000, 'BANKING', 'paid', 'confirmed', NOW() - INTERVAL 5 DAY),
(28, 3, 'Mai Nguyễn Thùy Dương', '0933658922', '79 Hồ Tùng Mậu, phường Mai Dịch, quận Cầu Giấy, Hà Nội', '', 72000, 'BANKING', 'paid', 'confirmed', NOW() - INTERVAL 4 DAY),
(29, 4, 'Nguyễn Thị Mai Hoa', '0337812339', '130 Xuân Thủy, phường Dịch Vọng Hậu, quận Cầu Giấy, Hà Nội', '', 69000, 'COD', 'unpaid', 'pending', NOW() - INTERVAL 3 DAY),
(30, 1, 'Nguyễn Thị Vân Dung', '0338766389', '80 Trần Duy Hưng, phường Trung Hòa, quận Cầu Giấy, Hà Nội', '', 108000, 'COD', 'paid', 'done', NOW() - INTERVAL 2 DAY),
(31, 3, 'Mai Nguyễn Thùy Dương', '0933658922', '79 Hồ Tùng Mậu, phường Mai Dịch, quận Cầu Giấy, Hà Nội', 'Giao giờ nghỉ trưa giúp mình.', 72000, 'COD', 'unpaid', 'pending', NOW() - INTERVAL 1 DAY);


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

--
-- Đang đổ dữ liệu cho bảng `order_cancellations`
--

INSERT INTO `order_cancellations` (`id`, `order_id`, `user_id`, `reason`, `status`) VALUES
(1, 2, 4, 'khách đổi ý về đơn hàng.', 'pending'),
(2, 14, 4, 'đặt nhầm sản phẩm.', 'approved'),
(3, 18, 1, 'khách muốn thay đổi kế hoạch.', 'approved'),
(4, 19, 3, 'khách muốn hủy đơn.', 'rejected');


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

--
-- Đang đổ dữ liệu cho bảng `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `quantity`, `price`) VALUES
(1, 1, 1, 2, 39000),
(2, 2, 2, 1, 42000),
(3, 3, 3, 2, 42000),
(4, 4, 4, 1, 45000),
(5, 5, 5, 3, 42000),
(6, 6, 6, 1, 39000),
(7, 6, 7, 1, 45000),
(8, 7, 8, 1, 39000),
(9, 8, 9, 1, 42000),
(10, 9, 10, 2, 45000),
(11, 10, 11, 1, 42000),
(12, 11, 12, 1, 42000),
(13, 11, 1, 1, 39000),
(14, 12, 13, 1, 39000),
(15, 13, 14, 1, 42000),
(16, 14, 15, 1, 39000),
(17, 15, 16, 2, 45000),
(18, 16, 17, 1, 47000),
(19, 17, 18, 1, 37000),
(20, 18, 19, 1, 39000),
(21, 18, 20, 1, 45000),
(22, 19, 21, 2, 39000),
(23, 20, 22, 1, 32000),
(24, 21, 23, 1, 37000),
(25, 22, 24, 1, 37000),
(26, 23, 25, 1, 45000),
(27, 24, 26, 2, 42000),
(28, 25, 27, 1, 42000),
(29, 26, 28, 1, 37000),
(30, 27, 29, 1, 45000),
(31, 28, 30, 1, 42000),
(32, 29, 31, 1, 39000),
(33, 30, 32, 2, 39000),
(34, 31, 33, 1, 42000);


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

--
-- Đang đổ dữ liệu cho bảng `order_returns`
--

INSERT INTO `order_returns` (`id`, `order_id`, `user_id`, `reason`, `status`) VALUES
(1, 10, 3, 'sản phẩm cần được kiểm tra.', 'approved'),
(2, 12, 1, 'khách gửi yêu cầu kiểm tra.', 'rejected'),
(3, 13, 3, 'sản phẩm cần được kiểm tra.', 'pending'),
(4, 15, 1, 'khách muốn đổi sản phẩm.', 'approved'),
(5, 16, 3, 'sản phẩm cần được kiểm tra.', 'rejected'),
(6, 23, 4, 'khách gửi yêu cầu hoàn hàng.', 'pending'),
(7, 24, 1, 'sản phẩm cần được kiểm tra.', 'approved');


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
  `stock` int(11) NOT NULL DEFAULT 30,
  `sale_price` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tạo tồn kho của tất cả sản phẩm tự đặt lại thành 30 vào ngày mới
SET GLOBAL event_scheduler = ON;

CREATE EVENT IF NOT EXISTS reset_product_stock_daily
ON SCHEDULE EVERY 1 DAY
STARTS TIMESTAMP(CURRENT_DATE + INTERVAL 1 DAY)
DO
  UPDATE products SET stock = 30;

-- ----------------------------------------------------
--
-- Đang đổ dữ liệu cho bảng `products`
--

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

--
-- Đang đổ dữ liệu cho bảng `reviews`
--

INSERT INTO `reviews` (`id`, `product_id`, `user_id`, `order_id`, `rating`, `comment`) VALUES
(1, 1, 3, 1, 5, 'Bánh ngon, giao hàng nhanh.'),
(2, 3, 1, 3, 5, 'Vị xoài thơm, bánh mềm.'),
(3, 11, 3, 10, 4, 'Vị kem trứng đậm đà.'),
(4, 14, 3, 13, 5, 'Bánh tươi và vừa vị.'),
(5, 17, 3, 16, 5, 'Mochi dẻo, nhân kem ngon.'),
(6, 25, 4, 23, 4, 'Hương vị dễ ăn, đóng gói cẩn thận.');


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
(1, 'Nguyễn Thị Vân Dung', 'ntvdung826@gmail.com', '$2y$10$aoQqFxeFgoCVZ9Zc31a1a.51SHoY17dieaJXuvmFWdqZHgvvShTNO', 'admin', 1),
(2, 'Quản trị viên', 'admin@gmail.com', '$2y$10$DYotty.tGEY6DtqXs/VOuuohfLJxK4D2puNVm.rRCe8rdc1GMHJFS', 'admin', 1),
(3, 'Mai Nguyễn Thùy Dương', 'duongmnt@gmail.com', '$2y$10$aFGhFcz8nm91TV56qrsMQeoVLXCxJDPCWOyokOf3/r25M/YpLaxHW', 'user', 1),
(4, 'Nguyễn Thị Mai Hoa', 'nmh@gmail.com', '$2y$10$3RiQLk36mX7kdiyVoUyoxOzaqjMqtmL1PzxDMAeB5nNS/b4yb2zaG', 'user', 1);

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
