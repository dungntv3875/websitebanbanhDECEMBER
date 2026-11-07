<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$user_id    = (int)$_SESSION['user']['id'];
$product_id = (int)$_POST['product_id'];
$order_id   = (int)$_POST['order_id'];
$rating     = (int)$_POST['rating'];
$comment    = trim($_POST['comment']);

// Kiểm tra rating hợp lệ
if ($rating < 1 || $rating > 5) {
    header("Location: profile.php?tab=orders");
    exit;
}

// Kiểm tra đã mua và đơn done chưa
$stmt = $pdo->prepare("
    SELECT o.id FROM orders o
    JOIN order_items oi ON o.id = oi.order_id
    WHERE o.id = ? AND o.user_id = ? AND oi.product_id = ? AND o.status = 'done'
");
$stmt->execute([$order_id, $user_id, $product_id]);
if (!$stmt->fetch()) {
    header("Location: profile.php?tab=orders");
    exit;
}

// Xử lý upload ảnh
$image_name = null;
if (!empty($_FILES['image']['name'])) {
    $allowed = ['jpg', 'jpeg', 'png', 'webp'];
    $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
    if (in_array($ext, $allowed) && $_FILES['image']['size'] <= 5 * 1024 * 1024) {
        $image_name = 'review_' . uniqid() . '.' . $ext;
        move_uploaded_file($_FILES['image']['tmp_name'], 'uploads/' . $image_name);
    }
}

// Lưu đánh giá
try {
    $pdo->prepare("INSERT INTO reviews (product_id, user_id, order_id, rating, comment, image) VALUES (?,?,?,?,?,?)")
        ->execute([$product_id, $user_id, $order_id, $rating, $comment, $image_name]);
} catch (Exception $e) {
    // Đã review rồi thì bỏ qua
}

header("Location: profile.php?tab=orders");
exit;