<?php
ob_start();
session_start();
include 'db.php';

$error   = '';
$success = '';
$type    = $_GET['type'] ?? 'login';

/* ===== XỬ LÝ ĐĂNG KÝ ===== */
if (isset($_POST['register'])) {
    $name     = $_POST['name'];
    $email    = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $check = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $check->execute([$email]);

    if ($check->rowCount() > 0) {
        $error = 'Email đã tồn tại!';
    } else {
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $email, $password, 'user']);
        $user_id = $pdo->lastInsertId();

        $_SESSION['user'] = [
            'id'    => $user_id,
            'name'  => $name,
            'email' => $email,
            'role'  => 'user',
        ];

        header('Location: index.php');
        exit;
    }
}

/* ===== XỬ LÝ ĐĂNG NHẬP ===== */
if (isset($_POST['login'])) {
    $email    = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        // Kiểm tra tài khoản có bị khóa không
        if (isset($user['is_active']) && $user['is_active'] == 0) {
            $error = 'Tài khoản của bạn đã bị khóa!';
        } else {
            $_SESSION['user'] = $user;
            header($user['role'] === 'admin' ? 'Location: admin.php' : 'Location: index.php');
            exit;
        }
    } else {
        $error = 'Email hoặc mật khẩu không đúng!';
    }
}

include 'header.php';
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Tài khoản - December</title>
    <!-- Thêm Bootstrap để dùng UI đẹp hơn[cite: 1, 2] -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="./style.css?time=<?php echo time(); ?>">
</head>
<body class="bg-light">

<div class="container" style="max-width: 450px; margin: 80px auto;">
    <div class="card auth-card p-4 p-md-5 bg-white">
        
        <div class="text-center mb-4">
            <h3 class="text-uppercase fw-light" style="letter-spacing: 2px;">
                <?php echo ($type == 'login') ? 'Đăng nhập' : 'Đăng ký'; ?>
            </h3>
            <div style="width: 40px; height: 2px; background: #000; margin: 10px auto;"></div>
        </div>

        <!-- Hiển thị thông báo lỗi/thành công -->
        <?php if($error): ?>
            <div class="alert alert-danger small py-2 text-center border-0 rounded-0"><?php echo $error; ?></div>
        <?php endif; ?>
        <?php if($success): ?>
            <div class="alert alert-success small py-2 text-center border-0 rounded-0"><?php echo $success; ?></div>
        <?php endif; ?>

        <?php if($type == 'login'){ ?>
            <!-- FORM ĐĂNG NHẬP -->
            <form method="POST">
                <div class="mb-3">
                    <input type="email" name="email" class="form-control" placeholder="Email" required>
                </div>
                <div class="mb-4">
                    <input type="password" name="password" class="form-control" placeholder="Mật khẩu" required>
                </div>
                <button name="login" class="btn btn-auth w-100 mb-3">Đăng nhập</button>
            </form>
            <div class="text-center mt-3">
                <p class="small text-muted">Chưa có tài khoản? 
                    <a href="account.php?type=register" class="text-dark fw-bold text-decoration-none">Đăng ký ngay</a>
                </p>
            </div>

        <?php } else { ?>
            <!-- FORM ĐĂNG KÝ -->
            <form method="POST">
                <div class="mb-3">
                    <input type="text" name="name" class="form-control" placeholder="Họ và tên" required>
                </div>
                <div class="mb-3">
                    <input type="email" name="email" class="form-control" placeholder="Email" required>
                </div>
                <div class="mb-4">
                    <input type="password" name="password" class="form-control" placeholder="Mật khẩu" required>
                </div>
                <button name="register" class="btn btn-auth w-100 mb-3">Tạo tài khoản</button>
            </form>
            <div class="text-center mt-3">
                <p class="small text-muted">Đã có tài khoản? 
                    <a href="account.php?type=login" class="text-dark fw-bold text-decoration-none">Đăng nhập</a>
                </p>
            </div>
        <?php } ?>

    </div>
</div>

<?php include 'footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>