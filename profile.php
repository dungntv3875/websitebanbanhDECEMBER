<?php
session_start();
include 'db.php';
include 'header.php';
 
if (!isset($_SESSION['user'])) {
    header("Location: account.php");
    exit;
}
 
$user    = $_SESSION['user'];
$user_id = $user['id'];
$tab     = $_GET['tab'] ?? 'info';
$msg     = '';
$msg_type = '';
 
// ===== XỬ LÝ CẬP NHẬT THÔNG TIN =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_info'])) {
    $new_name  = trim($_POST['name']);
    $new_phone = trim($_POST['phone']);
    $new_email = trim($_POST['email']);
if ($new_name !== '' && $new_email !== '') {
    // Kiểm tra email chưa bị dùng bởi người khác
    $stmtChkEmail = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $stmtChkEmail->execute([$new_email, $user_id]);
    if ($stmtChkEmail->fetch()) {
        $msg = 'Email này đã được sử dụng bởi tài khoản khác!';
        $msg_type = 'danger';
    } else {
        $pdo->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?")
            ->execute([$new_name, $new_email, $user_id]);
        $_SESSION['user']['name']  = $new_name;
        $_SESSION['user']['email'] = $new_email;
        $user['name']  = $new_name;
        $user['email'] = $new_email;
        $msg = 'Cập nhật thông tin thành công!';
        $msg_type = 'success';
    }
}
    $tab = 'info';
}

// ===== XỬ LÝ YÊU CẦU HỦY ĐƠN =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_cancel'])) {
    $order_id = (int)$_POST['order_id'];
    $reason   = trim($_POST['reason']);
    // Chỉ cho hủy đơn pending hoặc confirmed
    $stmt = $pdo->prepare("SELECT status FROM orders WHERE id = ? AND user_id = ?");
    $stmt->execute([$order_id, $user_id]);
    $ord = $stmt->fetch();
    if ($ord && in_array($ord['status'], ['pending', 'confirmed']) && $reason !== '') {
        // Kiểm tra chưa có yêu cầu pending
        $stmtChk = $pdo->prepare("SELECT id FROM order_cancellations WHERE order_id = ? AND status = 'pending'");
        $stmtChk->execute([$order_id]);
        if (!$stmtChk->fetch()) {
            $pdo->prepare("INSERT INTO order_cancellations (order_id, user_id, reason) VALUES (?,?,?)")
                ->execute([$order_id, $user_id, $reason]);
            $msg = 'Đã gửi yêu cầu hủy đơn, vui lòng chờ admin xét duyệt.';
            $msg_type = 'success';
        } else {
            $msg = 'Bạn đã gửi yêu cầu hủy đơn này rồi, vui lòng chờ duyệt.';
            $msg_type = 'danger';
        }
    }
    $tab = 'orders';
}

// ===== XỬ LÝ YÊU CẦU HOÀN HÀNG =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_return'])) {
    $order_id = (int)$_POST['order_id'];
    $reason   = trim($_POST['reason']);
    $stmt = $pdo->prepare("SELECT id, status, created_at FROM orders WHERE id = ? AND user_id = ?");
    $stmt->execute([$order_id, $user_id]);
    $ord = $stmt->fetch();
    if ($ord && $ord['status'] === 'done' && $reason !== '') {
        $createdAt = new DateTime($ord['created_at'], new DateTimeZone('Asia/Ho_Chi_Minh'));
        $deadline = (clone $createdAt)->modify('+2 hours');
        $now = new DateTime('now', new DateTimeZone('Asia/Ho_Chi_Minh'));

        if ($now > $deadline) {
            $msg = 'Đã quá 2 giờ sau khi nhận hàng, nên không thể gửi yêu cầu hoàn hàng.';
            $msg_type = 'danger';
        } else {
            $stmtChk = $pdo->prepare("SELECT id FROM order_returns WHERE order_id = ? AND status = 'pending'");
            $stmtChk->execute([$order_id]);
            if (!$stmtChk->fetch()) {
                // Xử lý upload ảnh
                $return_image = null;
                if (!empty($_FILES['return_image']['name'])) {
                    $allowed = ['jpg','jpeg','png','webp'];
                    $ext = strtolower(pathinfo($_FILES['return_image']['name'], PATHINFO_EXTENSION));
                    if (in_array($ext, $allowed) && $_FILES['return_image']['size'] <= 5*1024*1024) {
                        $return_image = 'return_' . uniqid() . '.' . $ext;
                        move_uploaded_file($_FILES['return_image']['tmp_name'], 'uploads/' . $return_image);
                    }
                }
                $pdo->prepare("INSERT INTO order_returns (order_id, user_id, reason, image) VALUES (?,?,?,?)")
                    ->execute([$order_id, $user_id, $reason, $return_image]);
                $msg = 'Đã gửi yêu cầu hoàn hàng, vui lòng chờ admin xét duyệt.';
                $msg_type = 'success';
            } else {
                $msg = 'Bạn đã gửi yêu cầu hoàn hàng cho đơn này rồi.';
                $msg_type = 'danger';
            }
        }
    }
    $tab = 'orders';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $old_pass     = $_POST['old_password'] ?? '';
    $new_pass     = $_POST['new_password'] ?? '';
    $confirm_pass = $_POST['confirm_password'] ?? '';
 
    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $row = $stmt->fetch();
 
    if (!password_verify($old_pass, $row['password'])) {
        $msg = 'Mật khẩu hiện tại không đúng.';
        $msg_type = 'danger';
    } elseif (strlen($new_pass) < 6) {
        $msg = 'Mật khẩu mới phải có ít nhất 6 ký tự.';
        $msg_type = 'danger';
    } elseif ($new_pass !== $confirm_pass) {
        $msg = 'Xác nhận mật khẩu không khớp.';
        $msg_type = 'danger';
    } else {
        $hashed = password_hash($new_pass, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$hashed, $user_id]);
        $msg = 'Đổi mật khẩu thành công!';
        $msg_type = 'success';
    }
    $tab = 'password';
}
 
// ===== LẤY ĐƠN HÀNG =====
$orders = [];
$reviewed_map = [];
$cancel_map = []; // order_id => status yêu cầu hủy
$return_map = []; // order_id => status yêu cầu hoàn
if ($tab === 'orders') {
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$user_id]);
    $orders = $stmt->fetchAll();

    $stmt_rv = $pdo->prepare("SELECT product_id, order_id FROM reviews WHERE user_id = ?");
    $stmt_rv->execute([$user_id]);
    foreach ($stmt_rv->fetchAll() as $rv) {
        $reviewed_map[$rv['product_id'] . '_' . $rv['order_id']] = true;
    }

    // Lấy yêu cầu hủy
    $stmt_c = $pdo->prepare("SELECT order_id, status FROM order_cancellations WHERE user_id = ?");
    $stmt_c->execute([$user_id]);
    foreach ($stmt_c->fetchAll() as $c) {
        $cancel_map[$c['order_id']] = $c['status'];
    }

    // Lấy yêu cầu hoàn
    $stmt_r = $pdo->prepare("SELECT order_id, status FROM order_returns WHERE user_id = ?");
    $stmt_r->execute([$user_id]);
    foreach ($stmt_r->fetchAll() as $r) {
        $return_map[$r['order_id']] = $r['status'];
    }
}
 
function statusLabel($status) {
    switch ($status) {
        case 'pending':   return ['label' => 'Chờ xác nhận', 'color' => '#f39c12'];
        case 'confirmed': return ['label' => 'Đã xác nhận',  'color' => '#2980b9'];
        case 'shipping':  return ['label' => 'Đang giao',    'color' => '#8e44ad'];
        case 'done':      return ['label' => 'Hoàn thành',   'color' => '#27ae60'];
        case 'cancelled': return ['label' => 'Đã hủy',       'color' => '#e74c3c'];
        default:          return ['label' => $status,         'color' => '#999'];
    }
}

function canRequestReturnWithinWindow($orderCreatedAt) {
    if (!$orderCreatedAt) return false;
    $createdAt = new DateTime($orderCreatedAt, new DateTimeZone('Asia/Ho_Chi_Minh'));
    $deadline = (clone $createdAt)->modify('+2 hours');
    $now = new DateTime('now', new DateTimeZone('Asia/Ho_Chi_Minh'));
    return $now <= $deadline;
}

function returnWindowNote($orderCreatedAt) {
    if (!$orderCreatedAt) return 'Không xác định được thời hạn hoàn hàng.';
    $createdAt = new DateTime($orderCreatedAt, new DateTimeZone('Asia/Ho_Chi_Minh'));
    $deadline = (clone $createdAt)->modify('+2 hours');
    $now = new DateTime('now', new DateTimeZone('Asia/Ho_Chi_Minh'));
    if ($now > $deadline) {
        return 'Đã quá 2 giờ sau khi nhận hàng nên không thể hoàn hàng.';
    }
    return 'Còn thời gian hoàn hàng trong 2 giờ sau khi nhận hàng.';
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Trang cá nhân - December</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
</head>
<body>
 
<div class="profile-wrap">
    <div class="row g-4">
 
        <!-- SIDEBAR -->
        <div class="col-md-3">
            <div class="profile-sidebar">
                <div class="profile-avatar">
                    <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                </div>
                <div class="profile-name"><?php echo htmlspecialchars($user['name']); ?></div>
                <div class="profile-email"><?php echo htmlspecialchars($user['email']); ?></div>
                <div class="profile-menu">
                    <a href="profile.php?tab=info" class="<?php echo $tab === 'info' ? 'active' : ''; ?>">
                        <i class="fa-regular fa-user"></i> Thông tin tài khoản
                    </a>
                    <a href="profile.php?tab=orders" class="<?php echo $tab === 'orders' ? 'active' : ''; ?>">
                        <i class="fa-regular fa-rectangle-list"></i> Đơn hàng của bạn
                    </a>
                    <a href="profile.php?tab=password" class="<?php echo $tab === 'password' ? 'active' : ''; ?>">
                        <i class="fa-regular fa-lock"></i> Đổi mật khẩu
                    </a>
                </div>
            </div>
        </div>
 
        <!-- CONTENT -->
        <div class="col-md-9">
            <div class="profile-content">
 
                <?php if ($tab === 'info'): ?>
                <h4>Thông tin tài khoản</h4>
                <?php if ($msg): ?>
                <div class="alert alert-<?php echo $msg_type; ?> rounded-3 mb-4" style="font-size:14px;"><?php echo $msg; ?></div>
                <?php endif; ?>
                <?php
                $stmt_info = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                $stmt_info->execute([$user_id]);
                $user_info = $stmt_info->fetch();
                ?>
                <form method="POST" style="max-width:420px;">
                    <input type="hidden" name="update_info" value="1">
                    <div class="mb-3">
                        <label class="form-label" style="font-size:13px;font-weight:600;">Họ và tên</label>
                        <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($user_info['name']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" style="font-size:13px;font-weight:600;">Email</label>
                        <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user_info['email']); ?>" required>
                    </div>
                    <button type="submit" class="btn-save">Lưu thay đổi</button>
                </form>
 
                <?php elseif ($tab === 'orders'): ?>
                <h4>Đơn hàng của bạn</h4>
                <?php if (empty($orders)): ?>
                <div class="empty-state">
                    <i class="fa-regular fa-bag-shopping"></i>
                    <p>Bạn chưa có đơn hàng nào.</p>
                    <a href="index.php" class="btn-save text-decoration-none">Mua sắm ngay</a>
                </div>
                <?php else: ?>
                <?php foreach ($orders as $order):
                    $s = statusLabel($order['status']);
                    $stmtItems = $pdo->prepare("
                        SELECT oi.*, p.name, p.image
                        FROM order_items oi
                        JOIN products p ON oi.product_id = p.id
                        WHERE oi.order_id = ?
                    ");
                    $stmtItems->execute([$order['id']]);
                    $items = $stmtItems->fetchAll();
                ?>
                <div class="order-card">
                    <div class="order-header">
                        <div>
                            <span class="order-id">Đơn #<?php echo $order['id']; ?></span>
                            <span class="order-date"><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></span>
                        </div>
                        <span class="order-status-badge" style="background:<?php echo $s['color']; ?>">
                            <?php echo $s['label']; ?>
                        </span>
                    </div>
                    <div class="order-items">
                        <?php foreach ($items as $item):
                            $thumb = trim(explode(',', $item['image'])[0]);
                        ?>
                        <div class="order-item-row">
                            <img src="uploads/<?php echo $thumb; ?>" class="order-item-img">
                            <div class="flex-grow-1">
                                <div style="font-size:14px;font-weight:500;"><?php echo htmlspecialchars($item['name']); ?></div>
                            </div>
                            <div class="text-end ms-3" style="flex-shrink:0;">
                                <div style="font-size:12px;color:#bbb;">x<?php echo $item['quantity']; ?></div>
                                <div style="font-size:14px;font-weight:600;"><?php echo number_format($item['price'] * $item['quantity']); ?>đ</div>
                                <?php 
                                $rv_key = $item['product_id'] . '_' . $order['id'];
                                if ($order['status'] === 'done'): ?>
                                    <?php if (!empty($reviewed_map[$rv_key])): ?>
                                        <a class="btn-review-inline mt-2 text-decoration-none"
                                            href="product.php?id=<?php echo $item['product_id']; ?>#reviews">
                                            <i class="fa-regular fa-eye me-1"></i> Xem đánh giá
                                        </a>
                                    <?php else: ?>
                                        <button class="btn-review-inline mt-2"
                                            onclick="openReviewModal(<?php echo $item['product_id']; ?>, <?php echo $order['id']; ?>, '<?php echo htmlspecialchars($item['name'], ENT_QUOTES); ?>', 'uploads/<?php echo $thumb; ?>')">
                                            <i class="fa-regular fa-star me-1"></i> Đánh giá
                                        </button>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="order-footer">
                        <span style="color:#bbb;"><?php echo $order['payment_method'] === 'COD' ? 'Thanh toán khi nhận hàng' : 'Chuyển khoản ngân hàng'; ?></span>
                        <div>
                            <span style="color:#aaa;margin-right:6px;">Tổng tiền:</span>
                            <span class="order-total"><?php echo number_format($order['total']); ?>đ</span>
                        </div>
                    </div>

                    <!-- NÚT HỦY / HOÀN HÀNG -->
                    <div style="padding:10px 16px;border-top:1px solid #f5f5f5;display:flex;gap:8px;justify-content:flex-end;">
                        <?php if (in_array($order['status'], ['pending', 'confirmed'])): ?>
                            <?php if (!isset($cancel_map[$order['id']])): ?>
                                <button class="btn-cancel-order"
                                    onclick="openRequestModal('cancel', <?php echo $order['id']; ?>)">
                                    <i class="fa-solid fa-xmark me-1"></i> Yêu cầu hủy đơn
                                </button>
                            <?php elseif ($cancel_map[$order['id']] === 'pending'): ?>
                                <span style="font-size:12px;color:#f39c12;"><i class="fa-solid fa-clock me-1"></i>Đang chờ duyệt hủy</span>
                            <?php elseif ($cancel_map[$order['id']] === 'approved'): ?>
                                <span style="font-size:12px;color:#27ae60;"><i class="fa-solid fa-circle-check me-1"></i>Đã duyệt hủy</span>
                            <?php elseif ($cancel_map[$order['id']] === 'rejected'): ?>
                                <span style="font-size:12px;color:#e74c3c;"><i class="fa-solid fa-circle-xmark me-1"></i>Yêu cầu hủy bị từ chối</span>
                            <?php endif; ?>
                        <?php endif; ?>

                        <?php if ($order['status'] === 'done'): ?>
                            <?php
                                $returnAllowed = !isset($return_map[$order['id']]) && canRequestReturnWithinWindow($order['created_at']);
                                $returnWindowText = returnWindowNote($order['created_at']);
                            ?>
                            <?php if (!isset($return_map[$order['id']])): ?>
                                <?php if ($returnAllowed): ?>
                                    <button class="btn-return-order" style="border-color:#8e44ad;color:#8e44ad;"
                                        onclick="openRequestModal('return', <?php echo $order['id']; ?>)">
                                        <i class="fa-solid fa-rotate-left me-1"></i> Yêu cầu hoàn hàng
                                    </button>
                                <?php else: ?>
                                    <button class="btn-return-order" style="border-color:#d0d0d0;color:#a0a0a0;opacity:0.7;cursor:not-allowed;" disabled>
                                        <i class="fa-solid fa-rotate-left me-1"></i> Yêu cầu hoàn hàng
                                    </button>
                                <?php endif; ?>
                            <?php elseif ($return_map[$order['id']] === 'pending'): ?>
                                <span style="font-size:12px;color:#8e44ad;"><i class="fa-solid fa-clock me-1"></i>Đang chờ duyệt hoàn hàng</span>
                            <?php elseif ($return_map[$order['id']] === 'approved'): ?>
                                <span style="font-size:12px;color:#27ae60;"><i class="fa-solid fa-circle-check me-1"></i>Đã duyệt hoàn hàng</span>
                            <?php elseif ($return_map[$order['id']] === 'rejected'): ?>
                                <span style="font-size:12px;color:#e74c3c;"><i class="fa-solid fa-circle-xmark me-1"></i>Yêu cầu hoàn bị từ chối</span>
                            <?php endif; ?>
                            <?php if (!isset($return_map[$order['id']]) && !$returnAllowed): ?>
                                <div style="width:100%; text-align:right; margin-top:6px;">
                                    <small style="color:#d9534f; font-weight:600;">
                                        <i class="fa-solid fa-circle-exclamation me-1"></i><?php echo htmlspecialchars($returnWindowText); ?>
                                    </small>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
 
                <?php elseif ($tab === 'password'): ?>
                <h4>Đổi mật khẩu</h4>
                <?php if ($msg): ?>
                <div class="alert alert-<?php echo $msg_type; ?> rounded-3 mb-4" style="font-size:14px;">
                    <?php echo $msg; ?>
                </div>
                <?php endif; ?>
                <form method="POST" class="pass-form" style="max-width:400px;">
                    <input type="hidden" name="change_password" value="1">
                    <div class="mb-3">
                        <label>Mật khẩu hiện tại</label>
                        <input type="password" name="old_password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Mật khẩu mới</label>
                        <input type="password" name="new_password" class="form-control" required>
                    </div>
                    <div class="mb-4">
                        <label>Xác nhận mật khẩu mới</label>
                        <input type="password" name="confirm_password" class="form-control" required>
                    </div>
                    <button type="submit" class="btn-save">Lưu thay đổi</button>
                </form>
 
                <?php endif; ?>
            </div>
        </div>
 
    </div>
</div>
 
<!-- Modal yêu cầu hủy / hoàn hàng -->
<div class="modal fade" id="requestModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="border-radius:14px;border:none;">
      <div class="modal-header" style="border-bottom:1px solid #f0f0f0;padding:18px 24px;">
        <h6 class="modal-title fw-bold" id="req-modal-title"></h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" style="padding:24px;">
        <form method="POST" enctype="multipart/form-data" id="req-form">
          <input type="hidden" name="order_id" id="req-order-id">
          <input type="hidden" name="req-type" id="req-type">
          <div class="mb-3">
            <label style="font-size:13px;font-weight:600;margin-bottom:8px;display:block;">Lý do</label>
            <select id="req-reason-select" class="form-select mb-2" onchange="document.getElementById('req-reason').value=this.value==='other'?'':this.value">
                <option value="">-- Chọn lý do --</option>
                <option value="Đặt nhầm sản phẩm">Đặt nhầm sản phẩm</option>
                <option value="Muốn đổi size / màu khác">Muốn đổi size / màu khác</option>
                <option value="Sản phẩm bị lỗi / hư hỏng">Sản phẩm bị lỗi / hư hỏng</option>
                <option value="Không còn nhu cầu mua">Không còn nhu cầu mua</option>
                <option value="other">Lý do khác...</option>
            </select>
            <textarea id="req-reason" name="reason" class="form-control" rows="2" placeholder="Mô tả chi tiết lý do..." required></textarea>
            <!-- Ô upload ảnh (chỉ hiện khi là yêu cầu hoàn hàng) -->
        <div id="req-image-wrap" style="display:none;margin-top:12px;">
            <label style="font-size:13px;font-weight:600;margin-bottom:6px;display:block;">
            Ảnh minh chứng <span style="font-weight:400;color:#aaa;">(tùy chọn)</span>
            </label>
            <label class="rv-upload-label" for="req-image-input" style="width:90px;height:90px;">
                <i class="fa-regular fa-image" style="font-size:20px;color:#aaa;"></i>
                <span style="font-size:11px;color:#aaa;margin-top:4px;">Chọn ảnh</span>
                    <img id="req-img-preview" src="" style="display:none;width:100%;height:100%;object-fit:cover;border-radius:10px;position:absolute;inset:0;">
            </label>
        <input type="file" name="return_image" id="req-image-input" accept="image/*" style="display:none;"
        onchange="
            const f=this.files[0];
            if(f){
                const r=new FileReader();
                r.onload=e=>{
                    const p=document.getElementById('req-img-preview');
                    p.src=e.target.result;
                    p.style.display='block';
                };
                r.readAsDataURL(f);
            }
        ">
        </div>
          </div>
          <button type="submit" id="req-submit-btn" name="request_cancel" class="btn-admin-primary w-100">Gửi yêu cầu</button>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
function openRequestModal(type, orderId) {
    document.getElementById('req-order-id').value = orderId;
    document.getElementById('req-modal-title').textContent =
        type === 'cancel' ? 'Yêu cầu hủy đơn #' + orderId : 'Yêu cầu hoàn hàng đơn #' + orderId;
    document.getElementById('req-reason').value = '';
    document.getElementById('req-submit-btn').name = type === 'cancel' ? 'request_cancel' : 'request_return';
    // Hiện ô upload ảnh chỉ khi hoàn hàng
    document.getElementById('req-image-wrap').style.display = type === 'return' ? 'block' : 'none';
    document.getElementById('req-img-preview').style.display = 'none';
    document.getElementById('req-image-input').value = '';
    new bootstrap.Modal(document.getElementById('requestModal')).show();
}
</script>

<?php include 'footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- Modal đánh giá -->
<div class="modal fade" id="reviewModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="border-radius:14px;border:none;">
      <div class="modal-header" style="border-bottom:1px solid var(--gray-100);padding:18px 24px;">
        <div class="d-flex align-items-center gap-3">
          <img id="rv-product-img" src="" style="width:46px;height:58px;object-fit:cover;border-radius:8px;border:1px solid var(--gray-200);">
          <div>
            <div style="font-size:11px;color:var(--gray-500);text-transform:uppercase;letter-spacing:1px;">Đánh giá sản phẩm</div>
            <div id="rv-product-name" style="font-weight:700;font-size:14px;"></div>
          </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" style="padding:24px;">
        <form action="review_submit.php" method="POST" enctype="multipart/form-data">
          <input type="hidden" name="product_id" id="rv-product-id">
          <input type="hidden" name="order_id"   id="rv-order-id">

          <!-- CHỌN SAO -->
          <div class="mb-3 text-center">
            <div style="font-size:13px;font-weight:600;color:var(--gray-700);margin-bottom:10px;">Chọn số sao</div>
            <div class="star-picker justify-content-center">
              <?php for ($i = 5; $i >= 1; $i--): ?>
              <input type="radio" name="rating" id="mstar<?php echo $i; ?>" value="<?php echo $i; ?>" required>
              <label for="mstar<?php echo $i; ?>"><i class="fa-solid fa-star"></i></label>
              <?php endfor; ?>
            </div>
          </div>

          <!-- BÌNH LUẬN -->
          <div class="mb-3">
            <textarea name="comment" class="form-control" rows="3"
              placeholder="Chia sẻ cảm nhận của bạn về sản phẩm..."></textarea>
          </div>

          <!-- UPLOAD ẢNH -->
          <div class="mb-4">
            <label style="font-size:13px;font-weight:600;color:var(--gray-700);margin-bottom:8px;display:block;">
              Thêm ảnh <span style="font-weight:400;color:var(--gray-500);">(tối đa 1 ảnh)</span>
            </label>
            <label class="rv-upload-label" for="rv-image-input">
              <i class="fa-regular fa-image" style="font-size:22px;color:var(--gray-500);"></i>
              <span style="font-size:13px;color:var(--gray-500);margin-top:6px;">Chọn ảnh</span>
              <img id="rv-preview" src="" style="display:none;width:100%;height:100%;object-fit:cover;border-radius:10px;position:absolute;inset:0;">
            </label>
            <input type="file" name="image" id="rv-image-input" accept="image/*" style="display:none;" onchange="previewRvImage(this)">
          </div>

          <button type="submit" class="btn-admin-primary w-100">Gửi đánh giá</button>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
function openReviewModal(productId, orderId, productName, productImg) {
    document.getElementById('rv-product-id').value  = productId;
    document.getElementById('rv-order-id').value    = orderId;
    document.getElementById('rv-product-name').textContent = productName;
    document.getElementById('rv-product-img').src   = productImg;
    // Reset form
    document.getElementById('rv-preview').style.display = 'none';
    document.getElementById('rv-image-input').value = '';
    document.querySelectorAll('input[name="rating"]').forEach(r => r.checked = false);
    new bootstrap.Modal(document.getElementById('reviewModal')).show();
}

function previewRvImage(input) {
    const preview = document.getElementById('rv-preview');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            preview.src = e.target.result;
            preview.style.display = 'block';
        };
        reader.readAsDataURL(input.files[0]);
    }
}



</script>