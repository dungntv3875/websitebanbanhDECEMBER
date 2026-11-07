<?php
ob_start(); 
session_start();
include 'db.php';

$total_all = 0; // Khởi tạo biến bằng 0 để tránh lỗi báo biến chưa tồn tại

if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        // Cộng dồn: giá * số lượng
        $total_all += $item['price'] * $item['qty'];
    }
}


// --- 1. PHẦN XỬ LÝ LOGIC  ---
$action = $_GET['action'] ?? '';

// Xử lý thêm
if ($action == 'add') {
    $id = (int)($_GET['id'] ?? 0);
    $qty = max(1, (int)($_GET['qty'] ?? 1));

    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $p = $stmt->fetch();

    if ($p) {
        // ===== KIỂM TRA SỐ LƯỢNG TỒN KHO =====
        $stock = (int)$p['stock'];
        $is_buy_now = isset($_GET['redirect']) && $_GET['redirect'] === 'checkout';
        $already = $is_buy_now ? 0 : (int)($_SESSION['cart'][$id]['qty'] ?? 0);

        if ($stock <= 0) {
            $_SESSION['stock_error'] = 'Sản phẩm "' . $p['name'] . '" hiện đã hết hàng.';
            header("Location: product.php?id=" . $id);
            exit;
        }
        if ($already + $qty > $stock) {
            $msg = 'Vượt quá số lượng trong kho! "' . $p['name'] . '" chỉ còn ' . $stock . ' sản phẩm';
            if ($already > 0) {
                $msg .= ' (trong giỏ bạn đã có ' . $already . ')';
            }
            $_SESSION['stock_error'] = $msg . '.';
            header("Location: product.php?id=" . $id);
            exit;
        }

        $item_data = [
            'id' => $id, 
            'name' => $p['name'], 
            'price' => !empty($p['sale_price']) ? $p['sale_price'] : $p['price'],
            'image' => explode(',', $p['image'])[0], 
            'qty' => $qty
        ];

        // KIỂM TRA NẾU LÀ MUA NGAY
        if (isset($_GET['redirect']) && $_GET['redirect'] === 'checkout') {
            $_SESSION['checkout_mode'] = 'buy_now';
            $_SESSION['buy_now_item'] = $item_data; // Lưu riêng sản phẩm mua ngay
            header("Location: checkout.php");
            exit;
        } else {
            // THÊM VÀO GIỎ HÀNG BÌNH THƯỜNG
            unset($_SESSION['checkout_mode']); // Xóa chế độ mua ngay
            $cart_id = $id;
            if (isset($_SESSION['cart'][$cart_id])) {
                $_SESSION['cart'][$cart_id]['qty'] += $qty;
            } else {
                $_SESSION['cart'][$cart_id] = $item_data;
            }
            header("Location: cart.php");
            exit;
        }
    }
}

// Xử lý xóa
if ($action == 'remove') {
    $key = $_GET['key'] ?? '';
    if (isset($_SESSION['cart'][$key])) {
        unset($_SESSION['cart'][$key]);
    }
    header("Location: cart.php");
    exit;
}

// Xử lý Cập nhật số lượng
if (isset($_GET['action']) && $_GET['action'] == 'update') {
    $key = $_GET['key'] ?? '';
    $new_qty = (int)($_GET['qty'] ?? 1);
    
    if (isset($_SESSION['cart'][$key])) {
        // Kiểm tra tồn kho khi tăng số lượng
        $stmtS = $pdo->prepare("SELECT stock, name FROM products WHERE id = ?");
        $stmtS->execute([$_SESSION['cart'][$key]['id']]);
        $prod = $stmtS->fetch();
        $max_stock = $prod ? (int)$prod['stock'] : 0;

        if ($new_qty > $max_stock) {
            $_SESSION['cart_error'] = 'Vượt quá số lượng trong kho! "' . ($prod['name'] ?? 'Sản phẩm') . '" chỉ còn ' . $max_stock . ' sản phẩm.';
        } elseif ($new_qty >= 1) {
            $_SESSION['cart'][$key]['qty'] = $new_qty;
        } else {
            unset($_SESSION['cart'][$key]); // Nếu giảm về 0 thì xóa luôn
        }
    }
    header("Location: cart.php");
    exit;
}

// --- 2. SAU KHI XỬ LÝ XONG MỚI GỌI HEADER VÀ HIỂN THỊ ---
include 'header.php'; 
$cart = $_SESSION['cart'] ?? [];

$cart_error = $_SESSION['cart_error'] ?? '';
unset($_SESSION['cart_error']);

// Lấy tồn kho hiện tại của các sản phẩm trong giỏ để hiển thị
$stock_map = [];
if (!empty($cart)) {
    $ids = array_values(array_unique(array_map(fn($i) => (int)$i['id'], $cart))); // array_values: đánh số lại 0,1,2... cho PDO
    $ph = implode(',', array_fill(0, count($ids), '?'));
    $stmtSt = $pdo->prepare("SELECT id, stock FROM products WHERE id IN ($ph)");
    $stmtSt->execute($ids);
    foreach ($stmtSt->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $stock_map[$r['id']] = (int)$r['stock'];
    }
}
?>
<div class="container mt-5">
    <h3 class="fw-bold mb-4">GIỎ HÀNG CỦA BẠN</h3>

    <?php if ($cart_error): ?>
        <div class="alert alert-warning"><?= htmlspecialchars($cart_error) ?></div>
    <?php endif; ?>
    
    <?php if (empty($cart)): ?>
        <p>Giỏ hàng đang trống. <a href="index.php">Tiếp tục mua sắm</a></p>
    <?php else: ?>
    
    <!-- Bọc toàn bộ trong form POST -->
    <form action="checkout.php" method="POST" id="cart-form">
        <div class="row">
            <div class="col-md-8">
            
                <!-- Checkbox chọn tất cả -->
                <div class="mb-2 d-flex align-items-center gap-2">
                    <input type="checkbox" id="check-all" style="width:16px;height:16px;">
                    <label for="check-all" class="mb-0 text-secondary" style="font-size:14px; cursor:pointer;">
                        Chọn tất cả
                    </label>
                </div>
            
                <table class="table align-middle">
                    <tbody>
    <?php 
    $total = 0; 
    foreach ($cart as $key => $item): 
        $subtotal = $item['price'] * $item['qty']; 
        $total += $subtotal; 
    ?>
    <tr class="align-middle">
        <!-- THÊM: checkbox cho mỗi sản phẩm -->
        <td style="width:36px;">
            <input type="checkbox" 
                   name="selected_items[]" 
                   value="<?= $key ?>" 
                   class="item-checkbox"
                   data-price="<?= $item['price'] * $item['qty'] ?>"
                   style="width:16px;height:16px;">
        </td>
        
        <td>
            <div class="d-flex align-items-center">
                <img src="uploads/<?= trim($item['image']) ?>" width="64" height="64" style="object-fit: cover;" class="me-3 border">
                <div>
                    <div class="fw-bold"><?= $item['name'] ?></div>
                    <div class="small text-secondary">Còn <?= $stock_map[$item['id']] ?? 0 ?> sản phẩm</div>
                </div>
            </div>
        </td>
        <td><?= number_format($item['price']) ?>đ</td>
        <td style="width: 130px;">
            <div class="input-group input-group-sm border" style="width: 100px;">
                <a href="cart.php?action=update&key=<?= $key ?>&qty=<?= $item['qty'] - 1 ?>" 
                   class="btn btn-light border-0" style="line-height: 1.5;">-</a>
                <input type="text" class="form-control text-center border-0 fw-bold p-0 qty-input" 
                       value="<?= $item['qty'] ?>" data-key="<?= $key ?>" data-old="<?= $item['qty'] ?>"
                       inputmode="numeric" maxlength="4" autocomplete="off"
                       style="background: transparent; font-size: 14px;">
                <a href="cart.php?action=update&key=<?= $key ?>&qty=<?= $item['qty'] + 1 ?>" 
                   class="btn btn-light border-0" style="line-height: 1.5;">+</a>
            </div>
        </td>
        <td class="fw-bold"><?= number_format($subtotal) ?>đ</td>
        <td>
            <a href="cart.php?action=remove&key=<?= $key ?>" class="text-danger text-decoration-none">
                <i class="fa-solid fa-trash-can"></i> Xóa
            </a>
        </td>
    </tr>
    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="col-md-4">
                <div class="card p-3 bg-light border-0">
                    <h5 class="mb-1">Tổng tiền đã chọn:</h5>
                    <h4 class="text-danger fw-bold mb-3" id="selected-total">0đ</h4>
                    <p class="text-secondary small mb-3" id="selected-count">Chưa chọn sản phẩm nào</p>
                    
                    <button type="submit" class="btn btn-checkout w-100 py-3" id="checkout-btn">
                        THANH TOÁN NGAY
                    </button>
                </div>
            </div>
        </div>
    </form>
    
    <?php endif; ?>
</div>

<!-- Script xử lý checkbox + tính tổng động -->
<script>
const checkAll = document.getElementById('check-all');
const itemCheckboxes = document.querySelectorAll('.item-checkbox');
const selectedTotal = document.getElementById('selected-total');
const selectedCount = document.getElementById('selected-count');
const checkoutBtn = document.getElementById('checkout-btn');

function updateSummary() {
    let total = 0, count = 0;
    itemCheckboxes.forEach(cb => {
        if (cb.checked) {
            total += parseInt(cb.dataset.price);
            count++;
        }
    });
    selectedTotal.textContent = total.toLocaleString('vi-VN') + 'đ';
    selectedCount.textContent = count > 0 
        ? `Đã chọn ${count} sản phẩm` 
        : 'Chưa chọn sản phẩm nào';
    checkoutBtn.disabled = count === 0;
    checkAll.indeterminate = count > 0 && count < itemCheckboxes.length;
    checkAll.checked = count === itemCheckboxes.length;
}

checkAll.addEventListener('change', () => {
    itemCheckboxes.forEach(cb => cb.checked = checkAll.checked);
    updateSummary();
});

itemCheckboxes.forEach(cb => cb.addEventListener('change', updateSummary));

// Cho khách tự nhập số lượng: nhập xong (Enter hoặc rời ô) thì cập nhật.
// Server kiểm tra tồn kho và báo "vượt quá" nếu nhập quá số lượng còn.
document.querySelectorAll('.qty-input').forEach(inp => {
    inp.addEventListener('input', () => { inp.value = inp.value.replace(/[^0-9]/g, ''); });
    const apply = () => {
        let v = parseInt(inp.value, 10);
        if (isNaN(v) || v < 1) v = parseInt(inp.dataset.old, 10); // không cho nhập 0 để tránh xóa nhầm
        if (v === parseInt(inp.dataset.old, 10)) { inp.value = inp.dataset.old; return; }
        window.location.href = 'cart.php?action=update&key=' + encodeURIComponent(inp.dataset.key) + '&qty=' + v;
    };
    inp.addEventListener('keydown', e => {
        if (e.key === 'Enter') { e.preventDefault(); apply(); }
    });
    inp.addEventListener('change', apply);
});

// Chặn submit nếu không chọn gì
document.getElementById('cart-form').addEventListener('submit', function(e) {
    const anyChecked = [...itemCheckboxes].some(cb => cb.checked);
    if (!anyChecked) {
        e.preventDefault();
        alert('Vui lòng chọn ít nhất 1 sản phẩm để thanh toán!');
    }
});
</script>
<?php include 'footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>