<?php
session_start();

if(!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'admin'){
    header("Location: ../index.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Admin - December Shop</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<div class="container-fluid">
    <div class="row">

        <!-- Sidebar -->
        <div class="col-md-2 bg-dark text-white min-vh-100 p-3">

            <h4>December Admin</h4>
            <hr>

            <ul class="nav flex-column">

                <li class="nav-item mb-2">
                    <a href="index.php" class="nav-link text-white">
                        Dashboard
                    </a>
                </li>

                <li class="nav-item mb-2">
                    <a href="products/index.php" class="nav-link text-white">
                        Quản lý sản phẩm
                    </a>
                </li>

                <li class="nav-item mb-2">
                    <a href="categories/index.php" class="nav-link text-white">
                        Quản lý danh mục
                    </a>
                </li>

                <li class="nav-item mb-2">
                    <a href="orders/index.php" class="nav-link text-white">
                        Quản lý đơn hàng
                    </a>
                </li>

                <li class="nav-item mb-2">
                    <a href="users/index.php" class="nav-link text-white">
                        Quản lý người dùng
                    </a>
                </li>

                <li class="nav-item mb-2">
                    <a href="../index.php" class="nav-link text-warning">
                        Về website
                    </a>
                </li>

            </ul>

        </div>

        <!-- Content -->
        <div class="col-md-10 p-4">

            <h2>Dashboard</h2>

            <div class="row mt-4">

                <div class="col-md-3">
                    <div class="card shadow">
                        <div class="card-body">
                            <h5>Sản phẩm</h5>
                            <h3>100</h3>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card shadow">
                        <div class="card-body">
                            <h5>Đơn hàng</h5>
                            <h3>25</h3>
                        </div>
                    </div>
                </div>

            </div>

        </div>

    </div>
</div>

</body>
</html>