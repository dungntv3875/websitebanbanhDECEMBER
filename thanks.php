<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user'])) {
    header('Location: account.php?type=login');
    exit;
}

$order_id = (int)($_GET['order_id'] ?? 0);
$st = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
$st->execute([$order_id, $_SESSION['user']['id']]);
$order = $st->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    header('Location: index.php');
    exit;
}

$st = $pdo->prepare("
    SELECT p.name, oi.quantity, oi.price
    FROM order_items oi LEFT JOIN products p ON p.id = oi.product_id
    WHERE oi.order_id = ?
");
$st->execute([$order_id]);
$items = $st->fetchAll(PDO::FETCH_ASSOC);

$status_labels = [
    'pending'   => 'Chờ xác nhận',
    'confirmed' => 'Đã xác nhận',
    'shipping'  => 'Đang giao',
    'done'      => 'Hoàn thành',
    'cancelled' => 'Đã hủy',
];
$method = $order['payment_method'];

if ($method === 'BANKING') {
    $pay_label = 'Chuyển khoản ngân hàng (VietQR)';
} else {
    $pay_label = 'Thanh toán khi nhận hàng (COD)';
}

include 'header.php';
?>
<style>
.thank-page-wrapper { padding: 3rem 1rem; display:flex; justify-content:center; }
.thank-card { background:#fff; border:1px solid #e5e7eb; border-radius:.5rem; max-width:600px; width:100%; overflow:hidden; animation: thankFade .6s ease-out; }
@keyframes thankFade { from {opacity:0; transform:translateY(20px);} to {opacity:1; transform:translateY(0);} }
.thank-header { background:#355638; padding:2.5rem 2rem; text-align:center; }
.success-icon-wrapper { width:4rem; height:4rem; background:#10b981; border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 1.25rem; }
.checkmark-svg { width:2.5rem; height:2.5rem; stroke:#fff; stroke-width:3; fill:none; stroke-dasharray:100; stroke-dashoffset:100; animation: thankDraw .6s ease-out .3s forwards; }
@keyframes thankDraw { to { stroke-dashoffset:0; } }
.thank-title { color:#fff; font-size:1.45rem; font-weight:600; margin:0 0 .5rem; }
.thank-subtitle { color:#d6e0d3; font-size:.95rem; margin:0; }
.thank-body { padding:2rem; }
.thank-info { background:#f9fafb; border:1px solid #e5e7eb; border-radius:.5rem; padding:1.25rem 1.5rem; margin-bottom:1.25rem; }
.info-row { display:flex; justify-content:space-between; gap:1rem; padding:.65rem 0; border-bottom:1px solid #e5e7eb; }
.info-row:last-child { border-bottom:none; }
.info-label { color:#6b7280; font-size:.8rem; font-weight:500; text-transform:uppercase; letter-spacing:.025em; }
.info-value { color:#111827; font-size:.95rem; font-weight:600; text-align:right; }
.thank-items { font-size:.9rem; margin-bottom:1.25rem; }
.thank-items .row-item { display:flex; justify-content:space-between; padding:.35rem 0; }
.thank-buttons { display:grid; grid-template-columns:1fr 1fr; gap:.75rem; }
.thank-btn { display:inline-flex; align-items:center; justify-content:center; padding:.75rem 1rem; border-radius:.375rem; font-size:.95rem; font-weight:500; text-decoration:none; border:1px solid; }
.thank-btn-primary { background:#355638; border-color:#355638; color:#fff; }
.thank-btn-primary:hover { background:#243c28; color:#fff; }
.thank-btn-secondary { background:#fff; border-color:#e5e7eb; color:#374151; }
.thank-btn-secondary:hover { background:#f9fafb; color:#111827; }
.thank-footer { background:#f9fafb; border-top:1px solid #e5e7eb; padding:1.25rem 2rem; text-align:center; color:#6b7280; font-size:.875rem; }
@media (max-width:640px) { .thank-buttons { grid-template-columns:1fr; } .info-row { flex-direction:column; gap:.2rem; } .info-value { text-align:left; } }
</style>

<div class="thank-page-wrapper">
    <div class="thank-card">
        <div class="thank-header">
            <div class="success-icon-wrapper">
                <svg class="checkmark-svg" viewBox="0 0 52 52"><path d="M14 27l7 7 16-16" /></svg>
            </div>
            <h3 class="thank-title">Đặt hàng thành công</h3>
            <p class="thank-subtitle">Đơn hàng của bạn đã được tiếp nhận và đang xử lý</p>
        </div>

        <div class="thank-body">
            <div class="thank-info">
                <div class="info-row">
                    <span class="info-label">Mã đơn hàng</span>
                    <span class="info-value">#<?php echo $order_id; ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Trạng thái</span>
                    <span class="info-value"><?php echo $status_labels[$order['status']] ?? htmlspecialchars($order['status']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Thanh toán</span>
                    <span class="info-value"><?php echo $pay_label; ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Tổng tiền</span>
                    <span class="info-value" style="color:#355638;"><?php echo number_format($order['total']); ?>đ</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Thời gian</span>
                    <span class="info-value"><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></span>
                </div>
            </div>

            <?php if ($items): ?>
            <div class="thank-items">
                <?php foreach ($items as $it): ?>
                <div class="row-item">
                    <span><?php echo htmlspecialchars($it['name'] ?? 'Sản phẩm'); ?> <span class="text-muted">x<?php echo (int)$it['quantity']; ?></span></span>
                    <span><?php echo number_format($it['price'] * $it['quantity']); ?>đ</span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <?php if ($method === 'BANKING'): ?>
            <div class="alert alert-warning small">
                Đơn sẽ được xác nhận sau khi December nhận được tiền chuyển khoản.
                <a href="pay_qr.php?order_id=<?php echo $order_id; ?>">Xem lại mã QR</a>
            </div>
            <?php endif; ?>

            <div class="thank-buttons">
                <a href="profile.php?tab=orders" class="thank-btn thank-btn-primary">Xem đơn hàng</a>
                <a href="index.php" class="thank-btn thank-btn-secondary">Về trang chủ</a>
            </div>
        </div>

        <div class="thank-footer">
            Cảm ơn quý khách đã tin tưởng December.<br>
            Chúng tôi sẽ liên hệ trong thời gian sớm nhất.
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>