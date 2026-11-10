<?php
session_start();
include 'db.php';

// 1. Kiểm tra đăng nhập
if (!isset($_SESSION['user'])) {
    header("Location: account.php?type=login");
    exit;
}

$user_id = $_SESSION['user']['id'];
$user_email = $_SESSION['user']['email'] ?? '';
$user_name = $_SESSION['user']['fullname'] ?? $_SESSION['user']['name'] ?? '';

if (!empty($_SESSION['checkout_error'])) {
    $checkout_error = $_SESSION['checkout_error'];
    unset($_SESSION['checkout_error']);
} else {
    $checkout_error = '';
}

// Dữ liệu khách đã nhập (khi form bị trả lại do lỗi)
$old = $_SESSION['checkout_old'] ?? [];
unset($_SESSION['checkout_old']);

// 2. Xác định danh sách sản phẩm cần thanh toán
$checkout_items = [];
$total_all = 0;

if (isset($_SESSION['checkout_mode']) && $_SESSION['checkout_mode'] === 'buy_now' && isset($_SESSION['buy_now_item'])) {
    // Mua ngay
    $checkout_items = [$_SESSION['buy_now_item']];

} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['selected_items'])) {
    // THÊM MỚI: Lọc chỉ các sản phẩm được chọn từ giỏ hàng
    $selected_keys = $_POST['selected_items'];
    $full_cart = $_SESSION['cart'] ?? [];
    foreach ($selected_keys as $key) {
        if (isset($full_cart[$key])) {
            $checkout_items[$key] = $full_cart[$key];
        }
    }
    // Lưu vào session để process_order.php dùng
    $_SESSION['checkout_selected'] = $checkout_items;

} else {
    // Fallback: lấy toàn bộ giỏ (trường hợp vào thẳng URL)
    $checkout_items = $_SESSION['cart'] ?? [];
}

// 3. Nếu không có sản phẩm nào để thanh toán thì quay về trang chủ
if (empty($checkout_items)) {
    header("Location: index.php");
    exit;
}


// 3b. Kiểm tra tồn kho ngay khi vào trang thanh toán (chặn sớm nếu người khác đã mua mất hàng)
$stmtStockChk = $pdo->prepare("SELECT name, stock FROM products WHERE id = ?");
foreach ($checkout_items as $chk) {
    $stmtStockChk->execute([$chk['id']]);
    $pr = $stmtStockChk->fetch(PDO::FETCH_ASSOC);
    $left = $pr ? (int)$pr['stock'] : 0;
    if ($left < (int)$chk['qty']) {
        $pname = $pr['name'] ?? 'Sản phẩm';
        $m = $left <= 0
            ? 'Sản phẩm "' . $pname . '" đã hết hàng.'
            : 'Sản phẩm "' . $pname . '" chỉ còn ' . $left . ' sản phẩm (bạn chọn ' . (int)$chk['qty'] . '). Vui lòng giảm số lượng.';
        if (isset($_SESSION['checkout_mode']) && $_SESSION['checkout_mode'] === 'buy_now') {
            $_SESSION['stock_error'] = $m;
            header('Location: product.php?id=' . (int)$chk['id']);
        } else {
            $_SESSION['cart_error'] = $m;
            header('Location: cart.php');
        }
        exit;
    }
}

// 4. Tính tổng tiền
foreach ($checkout_items as $item) {
    $total_all += $item['price'] * $item['qty'];
}

// 5. Tính phí ship
$ship_fee = ($total_all > 500000) ? 0 : 30000;
$total_final = $total_all + $ship_fee;
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thanh toán - December</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
</head>
<body>

<div class="container-fluid checkout-wrapper">
    <form action="process_order.php" method="POST">
        <?php if (!empty($checkout_error)): ?>
            <div class="alert alert-warning mx-auto mt-3" style="max-width: 1100px;">
                <?php echo htmlspecialchars($checkout_error); ?>
            </div>
        <?php endif; ?>
        <div class="row">
            
            <div class="col-md-7 left-side">
                <div class="text-center mb-5">
                    <h1 class="logo-text">December</h1>
                    <p class="small text-secondary">est.2015</p>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <div class="d-flex justify-content-between">
                            <h5 class="section-title">Thông tin mua hàng</h5>
                            <a href="account.php" class="text-decoration-none small text-dark">Đăng nhập</a>
                        </div>
                        
                        <input type="email" class="form-control" value="<?php echo $user_email; ?>" readonly>
                        <input type="text" name="fullname" class="form-control" placeholder="Họ và tên" value="<?php echo htmlspecialchars($old['fullname'] ?? $user_name); ?>" required>
                        <input type="text" name="phone" class="form-control" placeholder="Số điện thoại (VD: 0912345678)"
                        value="<?php echo htmlspecialchars($old['phone'] ?? ''); ?>"
                        maxlength="10"
                        pattern="(03|05|07|08|09)[0-9]{8}"
                        title="Số điện thoại gồm 10 số, bắt đầu bằng 03, 05, 07, 08 hoặc 09"
                        oninput="this.value=this.value.replace(/[^0-9]/g,'')"
                         required>
                        <input type="text" name="address" class="form-control" placeholder="Địa chỉ (Số nhà, tên đường...)" value="<?php echo htmlspecialchars($old['address'] ?? ''); ?>" required>
                        <div class="small text-warning mt-2 mb-2">Chỉ hỗ trợ giao hàng trong nội thành Hà Nội.</div>
                        
                        <div class="row">
                            <div class="row">
    <div class="col-md-4">
        <select name="city" id="select-province" class="form-control" required>
            <option value="">-- Tỉnh / Thành phố --</option>
        </select>
    </div>
    <div class="col-md-4">
        <select name="district" id="select-district" class="form-control" required disabled>
            <option value="">-- Quận / Huyện --</option>
        </select>
    </div>
    <div class="col-md-4">
        <select name="ward" id="select-ward" class="form-control" required disabled>
            <option value="">-- Phường / Xã --</option>
        </select>
    </div>
</div>
                        </div>
                        
                        <textarea name="note" class="form-control" rows="3" placeholder="Ghi chú (tùy chọn)"><?php echo htmlspecialchars($old['note'] ?? ''); ?></textarea>
                    </div>
                </div>

                <div class="mt-4">
    <h5 class="section-title">Thanh toán</h5>
    
    <div class="payment-box mb-2 p-3 border rounded d-flex justify-content-between align-items-center">
        <label class="w-100" style="cursor: pointer;">
            <input type="radio" name="payment_method" value="COD" checked> 
            <span class="ms-2">Thanh toán khi giao hàng (COD)</span>
        </label>
        <img src="https://img.icons8.com/color/48/000000/money-box.png" width="24">
    </div>

    <div class="payment-box mb-2 p-3 border rounded d-flex justify-content-between align-items-center">
        <label class="w-100" style="cursor: pointer;">
            <input type="radio" name="payment_method" value="BANKING"> 
            <span class="ms-2">Chuyển khoản qua ngân hàng (VietQR)</span>
        </label>
        <img src="https://img.icons8.com/color/48/000000/bank-card-back-side.png" width="24">
    </div>

</div>
            </div>

            <div class="col-md-5 right-side">
              <h5 class="mb-4">Đơn hàng (<?php echo count($checkout_items); ?> sản phẩm)</h5>
                <hr>
                
                <div class="product-list-summary mb-4">
                    <?php foreach($checkout_items as $item): ?> 
                        <?php
                        $imgs = explode(',', $item['image']);
                        $thumb = trim($imgs[0]);
                        ?>
                    <div class="d-flex align-items-center mb-3">
                        <div class="thumb-wrapper me-3">
                            <img src="uploads/<?php echo $thumb; ?>" class="product-thumb">
                            <span class="qty-badge"><?php echo $item['qty']; ?></span>
                        </div>
                        <div class="flex-grow-1">
                            <div class="fw-bold small"><?php echo $item['name']; ?></div>
                        </div>
                        <div class="fw-bold small"><?php echo number_format($item['price'] * $item['qty']); ?>đ</div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <hr>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-secondary">Tạm tính</span>
                    <span><?php echo number_format($total_all); ?>đ</span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                <span class="text-secondary">Phí vận chuyển</span>
                <?php if ($ship_fee === 0): ?>
                <span class="text-success">0đ</span>
                <?php else: ?>
                <span><?php echo number_format($ship_fee); ?>đ</span>
                <?php endif; ?>
                </div>
                <hr>
                <div class="d-flex justify-content-between align-items-center mb-4">
                <span class="fs-5">Tổng cộng</span>
                <span class="fs-4 fw-bold text-danger"><?php echo number_format($total_final); ?>đ</span>
                </div>

                <div class="d-flex justify-content-between align-items-center">
                    <button type="button" class="back-cart-btn" onclick="window.location.href='cart.php'">
                    <i class="fa-solid fa-arrow-left me-2"></i>
                    Quay về giỏ hàng
                    </button>
                    <button type="submit" class="btn btn-order px-5">ĐẶT HÀNG</button>
                </div>
            </div>

        </div>
    </form>
</div>
<script>
const provinceSelect = document.getElementById('select-province');
const districtSelect = document.getElementById('select-district');
const wardSelect     = document.getElementById('select-ward');

provinceSelect.innerHTML = '<option value="Hà Nội">Hà Nội</option>';
provinceSelect.value = 'Hà Nội';
// Không disable để giá trị tỉnh vẫn được submit khi đặt hàng.

function resetDistrictWard() {
    districtSelect.innerHTML = '<option value="">-- Quận / Huyện --</option>';
    districtSelect.disabled = true;
    wardSelect.innerHTML = '<option value="">-- Phường / Xã --</option>';
    wardSelect.disabled = true;
}

function loadDistricts() {
    resetDistrictWard();
    fetch('https://provinces.open-api.vn/api/p/1?depth=2')
        .then(r => r.json())
        .then(data => {
            if (!data || !Array.isArray(data.districts)) return;

            data.districts.forEach(d => {
                const opt = document.createElement('option');
                opt.value = d.name;
                opt.dataset.code = d.code;
                opt.textContent = d.name;
                districtSelect.appendChild(opt);
            });

            districtSelect.disabled = false;
        })
        .catch(() => {
            resetDistrictWard();
        });
}

function loadWards(code) {
    wardSelect.innerHTML = '<option value="">-- Phường / Xã --</option>';
    wardSelect.disabled = true;

    if (!code) return;

    fetch(`https://provinces.open-api.vn/api/d/${code}?depth=2`)
        .then(r => r.json())
        .then(data => {
            if (!data || !Array.isArray(data.wards)) return;

            data.wards.forEach(w => {
                const opt = document.createElement('option');
                opt.value = w.name;
                opt.textContent = w.name;
                wardSelect.appendChild(opt);
            });

            wardSelect.disabled = false;
        })
        .catch(() => {
            wardSelect.innerHTML = '<option value="">-- Phường / Xã --</option>';
            wardSelect.disabled = true;
        });
}

loadDistricts();

districtSelect.addEventListener('change', function () {
    const selected = this.options[this.selectedIndex];
    const code = selected && selected.dataset.code ? selected.dataset.code : '';
    loadWards(code);
});
</script>
</body>
</html>