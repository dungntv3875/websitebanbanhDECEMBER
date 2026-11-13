<?php
session_start();
include 'db.php';
include 'header.php';

// lấy order_id
$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
$show_success = isset($_GET['success']) && $_GET['success'] == '1';

if ($order_id === 0) {
    die("Mã đơn hàng không hợp lệ.");
}

// lấy đơn hàng
$stmt = $pdo->prepare("SELECT total, fullname, payment_method, status, created_at FROM orders WHERE id = ?");
$stmt->execute([$order_id]);

$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    die("Không tìm thấy đơn hàng.");
}

// tổng số sản phẩm và thời gian tạo đơn
$itemStmt = $pdo->prepare("SELECT COUNT(*) AS item_count FROM order_items WHERE order_id = ?");
$itemStmt->execute([$order_id]);
$itemCount = (int) $itemStmt->fetchColumn();

// cấu hình QR
$BANK_ID      = "MB";
$ACCOUNT_NO   = "0986448310";
$ACCOUNT_NAME = "Tiem Banh December";

$AMOUNT       = $order['total'];
$DESCRIPTION  = "DECEMBER" . $order_id;

// tạo QR
$qr_url = "https://img.vietqr.io/image/{$BANK_ID}-{$ACCOUNT_NO}-template.png?amount={$AMOUNT}&addInfo={$DESCRIPTION}&accountName={$ACCOUNT_NAME}";

$statusText = [
    'pending' => 'Đang chờ xác nhận',
    'confirmed' => 'Đã xác nhận',
    'shipping' => 'Đang giao hàng',
    'done' => 'Hoàn thành',
    'cancelled' => 'Đã hủy',
];

$paymentText = [
    'COD' => 'Thanh toán khi nhận hàng',
    'BANKING' => 'Chuyển khoản ngân hàng',
];
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
        <?php if ($show_success): ?>
            <div class="alert alert-success qr-alert mb-4 text-start" style="display:flex; align-items:center; gap:12px; padding:16px 18px; border-radius:14px; border:none; background:linear-gradient(135deg, #eafaf1 0%, #dff7e8 100%); box-shadow:0 8px 18px rgba(25, 135, 84, 0.08);">
                <span style="display:inline-flex; align-items:center; justify-content:center; width:42px; height:42px; border-radius:50%; background:#1bbf6a; color:#fff; font-size:22px; box-shadow:0 8px 18px rgba(27, 191, 106, 0.2);">✓</span>
                <div>
                    <strong style="font-size:1.4rem; display:block; color:#166534; line-height:1.2;">Đặt hàng thành công</strong>
                </div>
            </div>

            <div class="success-header text-start mb-4" style="padding:0 4px;">
                <h3 class="fw-bold mb-2" style="display:flex; align-items:center; gap:10px; margin:0; color:#2b2b2b; font-size:1.15rem;">
                    <span style="display:inline-flex; align-items:center; justify-content:center; width:28px; height:28px; border-radius:50%; background:#edfdf5; color:#1bbf6a; font-size:16px;">✓</span>
                    Đơn hàng đang được tiếp nhận
                </h3>
                <p class="text-muted mb-0" style="margin-top:8px; padding-left:38px; font-size:0.96rem;">Đơn hàng của bạn đang được xử lý và chờ xác nhận từ December.</p>
            </div>

            <div class="payment-info text-start mb-4" style="padding:0 4px;">
                <div class="info-row" style="display:flex; justify-content:space-between; align-items:center; gap:18px; padding:10px 0; border-bottom:1px solid #f0f0f0;">
                    <span class="label" style="color:#666;">Mã đơn hàng:</span>
                    <span class="value text-primary fw-bold">#<?php echo $order_id; ?></span>
                </div>
                <div class="info-row" style="display:flex; justify-content:space-between; align-items:center; gap:18px; padding:10px 0; border-bottom:1px solid #f0f0f0;">
                    <span class="label" style="color:#666;">Trạng thái:</span>
                    <span class="value"><?php echo $statusText[$order['status']] ?? 'Đang chờ xác nhận'; ?></span>
                </div>
                <div class="info-row" style="display:flex; justify-content:space-between; align-items:center; gap:18px; padding:10px 0; border-bottom:1px solid #f0f0f0;">
                    <span class="label" style="color:#666;">Thanh toán:</span>
                    <span class="value"><?php echo $paymentText[$order['payment_method']] ?? 'Chuyển khoản ngân hàng'; ?></span>
                </div>
                <div class="info-row" style="display:flex; justify-content:space-between; align-items:center; gap:18px; padding:10px 0; border-bottom:1px solid #f0f0f0;">
                    <span class="label" style="color:#666;">Tổng tiền:</span>
                    <span class="value text-danger fs-5"><?php echo number_format($AMOUNT); ?>đ</span>
                </div>
                <div class="info-row" style="display:flex; justify-content:space-between; align-items:center; gap:18px; padding:10px 0; border-bottom:1px solid #f0f0f0;">
                    <span class="label" style="color:#666;">Thời gian:</span>
                    <span class="value"><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></span>
                </div>
                <div class="info-row" style="display:flex; justify-content:space-between; align-items:center; gap:18px; padding:10px 0; border-bottom:1px solid #f0f0f0;">
                    <span class="label" style="color:#666;">Đơn:</span>
                    <span class="value"><?php echo $itemCount; ?> sản phẩm</span>
                </div>
            </div>

            <div class="alert alert-warning qr-alert mb-4 text-start" style="border-left:4px solid #f0ad4e; border-radius:12px; margin-top:8px; background:#fffaf0; color:#8a5b00;">
                Đơn hàng sẽ được xác nhận sau khi December nhận được tiền chuyển khoản.
            </div>

            <div class="mb-3" style="display:flex; justify-content:center;">
                <a href="pay_qr.php?order_id=<?php echo $order_id; ?>" target="_blank" class="btn btn-outline-primary btn-sm" style="border-radius:999px; padding:8px 18px; font-weight:600; border-color:#3a7bd5; color:#3a7bd5; background:#fff;">
                    <i class="fa-solid fa-qrcode me-2"></i>Xem QR tại đây
                </a>
            </div>

            <div class="d-flex gap-2 justify-content-center flex-wrap" style="max-width:430px; margin:0 auto;">
                <a href="profile.php?tab=orders" class="btn btn-success-custom flex-fill" style="border-radius:10px; font-weight:600; background:linear-gradient(135deg, #1bbf6a 0%, #16a34a 100%); border:none; color:#fff; box-shadow:0 12px 20px rgba(27,191,106,0.18); min-width:180px;">Xem đơn hàng</a>
                <a href="index.php" class="btn btn-outline-secondary flex-fill" style="border-radius:10px; font-weight:600; border-color:#d0d5dd; color:#475467; background:#fff; min-width:180px;">Quay lại trang chủ</a>
            </div>

        <?php else: ?>
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
                <a href="pay_qr.php?order_id=<?php echo $order_id; ?>&success=1" class="btn btn-success-custom">Tôi đã chuyển khoản xong</a>
                <a href="checkout.php" class="text-secondary small mt-2 text-decoration-none">Quay lại trang thanh toán</a>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php include 'footer.php'; ?>
</body>
</html>