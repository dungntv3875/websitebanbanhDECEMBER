<?php
session_start();
include 'db.php';
include 'header.php';

// lấy order_id
$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

if ($order_id === 0) {
    die("Mã đơn hàng không hợp lệ.");
}

// lấy đơn hàng
$stmt = $pdo->prepare("SELECT total, fullname FROM orders WHERE id = ?");
$stmt->execute([$order_id]);

$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    die("Không tìm thấy đơn hàng.");
}

// cấu hình QR
$BANK_ID      = "MB";
$ACCOUNT_NO   = "0986448310";
$ACCOUNT_NAME = "TRAN NGOC ANH";

$AMOUNT       = $order['total'];
$DESCRIPTION  = "DECEMBER" . $order_id;

// tạo QR
$qr_url = "https://img.vietqr.io/image/{$BANK_ID}-{$ACCOUNT_NO}-template.png?amount={$AMOUNT}&addInfo={$DESCRIPTION}&accountName={$ACCOUNT_NAME}";
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thanh toán đơn hàng #<?php echo $order_id; ?> - December</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <link rel="stylesheet" href="style_qr.css">
</head>
<body>

<div class="container">
    <div class="card qr-card text-center">
        <h3 class="fw-bold mb-1">Thanh toán</h3>
        <p class="text-muted mb-4">Mã đơn hàng: #<?php echo $order_id; ?></p>

        <div class="alert alert-warning qr-alert mb-4">
            Vui lòng <strong>không sửa số tiền</strong> và <strong>nội dung</strong> để được xác nhận tự động.
        </div>

        <div class="mb-4">
            <img src="<?php echo $qr_url; ?>" alt="Mã QR VietQR" class="qr-image">
        </div>

        <div class="payment-info text-start mb-4">
            <div class="info-row">
                <span class="label">Chủ tài khoản:</span>
                <span class="value"><?php echo $ACCOUNT_NAME; ?></span>
            </div>
            <div class="info-row">
                <span class="label">Số tiền:</span>
                <span class="value text-danger fs-5"><?php echo number_format($AMOUNT); ?>đ</span>
            </div>
            <div class="info-row">
                <span class="label">Nội dung:</span>
                <span class="value text-primary"><?php echo $DESCRIPTION; ?></span>
            </div>
        </div>

        <div class="d-grid gap-2">
            <a href="index.php" class="btn btn-success-custom">Tôi đã chuyển khoản xong</a>
            <a href="checkout.php" class="text-secondary small mt-2 text-decoration-none">Quay lại trang thanh toán</a>
        </div>
    </div>
</div>
<?php include 'footer.php'; ?>
</body>
</html>