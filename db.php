<?php
// includes/db.php
$host = 'localhost';
$dbname = 'websitebanbanhDECEMBER-main';
$username = 'root'; // Mặc định của XAMPP
$password = '';     // Mặc định của XAMPP thường là trống

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    // Cấu hình báo lỗi để dễ debug
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Kết nối thất bại: " . $e->getMessage());
}
?>
