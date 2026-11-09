<?php
session_start();
include 'db.php';
 
// ===== KIỂM TRA QUYỀN ADMIN =====
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: account.php");
    exit;
}
 
$user = $_SESSION['user'];  // thông tin admin đang đăng nhập
$tab  = $_GET['tab'] ?? 'dashboard';  // tab hiện tại (dashboard/order

$msg  = '';
$msg_type = '';
 
// ============================================================
// XỬ LÝ ACTION
// ============================================================
 
// ----- ĐƠN HÀNG: đổi trạng thái -----
if (isset($_GET['action']) && $_GET['action'] === 'update_status') {
    $order_id  = (int)$_GET['id'];
    $new_status = $_GET['status'] ?? '';
    $allowed = ['pending','confirmed','shipping','done','cancelled'];
    if (in_array($new_status, $allowed)) {
        $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?")->execute([$new_status, $order_id]);
        $msg = 'Cập nhật trạng thái thành công!';
        $msg_type = 'success';
    }
    $tab = 'orders';
}

// ----- YÊU CẦU HỦY: duyệt/từ chối -----
if (isset($_GET['action']) && $_GET['action'] === 'review_cancel') {
    $cid    = (int)$_GET['id'];
    $result = $_GET['result'] ?? '';
    if (in_array($result, ['approved', 'rejected'])) {
        $pdo->prepare("UPDATE order_cancellations SET status = ? WHERE id = ?")->execute([$result, $cid]);
        if ($result === 'approved') {
            $row = $pdo->prepare("SELECT order_id FROM order_cancellations WHERE id = ?");
            $row->execute([$cid]);
            $r = $row->fetch();
            if ($r) $pdo->prepare("UPDATE orders SET status = 'cancelled' WHERE id = ?")->execute([$r['order_id']]);
        }
        $msg = $result === 'approved' ? 'Đã duyệt hủy đơn.' : 'Đã từ chối yêu cầu hủy.';
        $msg_type = 'success';
    }
    $tab = 'requests';
}

// ----- YÊU CẦU HOÀN: duyệt/từ chối -----
if (isset($_GET['action']) && $_GET['action'] === 'review_return') {
    $rid    = (int)$_GET['id'];
    $result = $_GET['result'] ?? '';
    if (in_array($result, ['approved', 'rejected'])) {
        $pdo->prepare("UPDATE order_returns SET status = ? WHERE id = ?")->execute([$result, $rid]);
        $msg = $result === 'approved' ? 'Đã duyệt hoàn hàng.' : 'Đã từ chối yêu cầu hoàn.';
        $msg_type = 'success';
    }
    $tab = 'requests';
}
 
// ----- SẢN PHẨM: xóa -----
if (isset($_GET['action']) && $_GET['action'] === 'delete_product') {
    $pdo->prepare("DELETE FROM products WHERE id = ?")->execute([(int)$_GET['id']]);
    $msg = 'Đã xóa sản phẩm.';
    $msg_type = 'success';
    $tab = 'products';
}
 
// ----- SẢN PHẨM: thêm mới -----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
    $name        = trim($_POST['name']);
    $category_id = (int)$_POST['category_id'];
    $price       = (int)$_POST['price'];
    $sale_price  = !empty($_POST['sale_price']) ? (int)$_POST['sale_price'] : null;
    $stock       = isset($_POST['stock']) && $_POST['stock'] !== '' ? (int)$_POST['stock'] : 50;
    $description = trim($_POST['description']);
    $image_names = [];
    if (!empty($_FILES['images']['name'][0])) {
        foreach ($_FILES['images']['tmp_name'] as $i => $tmp) {
            if ($tmp && $_FILES['images']['error'][$i] === 0) {
                $ext = pathinfo($_FILES['images']['name'][$i], PATHINFO_EXTENSION);
                $fname = uniqid('product_') . '.' . $ext;
                move_uploaded_file($tmp, 'uploads/' . $fname);
                $image_names[] = $fname;
            }
        }
    }
    $image_name = implode(',', $image_names);
 
    $pdo->prepare("INSERT INTO products (category_id, name, price, sale_price, stock, description, image) VALUES (?,?,?,?,?,?,?)")
    ->execute([$category_id, $name, $price, $sale_price, $stock, $description, $image_name]);
    $msg = 'Thêm sản phẩm thành công!';
    $msg_type = 'success';
    $tab = 'products';
}
 
// ----- SẢN PHẨM: sửa -----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_product'])) {
    $id          = (int)$_POST['product_id'];
    $name        = trim($_POST['name']);
    $category_id = (int)$_POST['category_id'];
    $price       = (int)$_POST['price'];
    $sale_price  = !empty($_POST['sale_price']) ? (int)$_POST['sale_price'] : null;
    $stock       = (int)$_POST['stock'];
    $description = trim($_POST['description']);
 
    // Lấy ảnh cũ
    $old = $pdo->prepare("SELECT image FROM products WHERE id = ?");
    $old->execute([$id]);
    $old_img = $old->fetchColumn();
    $old_imgs = array_filter(explode(',', $old_img));

    // Xóa ảnh được đánh dấu
    $delete_imgs = $_POST['delete_images'] ?? [];
    foreach ($delete_imgs as $del) {
        $del = basename($del);
        $old_imgs = array_filter($old_imgs, fn($x) => trim($x) !== $del);
        if (file_exists('uploads/' . $del)) unlink('uploads/' . $del);
    }

    // Thêm ảnh mới
    if (!empty($_FILES['images']['name'][0])) {
        foreach ($_FILES['images']['tmp_name'] as $i => $tmp) {
            if ($tmp && $_FILES['images']['error'][$i] === 0) {
                $ext = pathinfo($_FILES['images']['name'][$i], PATHINFO_EXTENSION);
                $fname = uniqid('product_') . '.' . $ext;
                move_uploaded_file($tmp, 'uploads/' . $fname);
                $old_imgs[] = $fname;
            }
        }
    }
    $image_name = implode(',', array_filter($old_imgs));
 
    $pdo->prepare("UPDATE products SET category_id=?, name=?, price=?, sale_price=?, stock=?, description=?, image=? WHERE id=?")
    ->execute([$category_id, $name, $price, $sale_price, $stock, $description, $image_name, $id]);
    $msg = 'Cập nhật sản phẩm thành công!';
    $msg_type = 'success';
    $tab = 'products';
}
 
// ----- DANH MỤC: thêm -----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    $name      = trim($_POST['cat_name']);
    $parent_id = $_POST['parent_id'] !== '' ? (int)$_POST['parent_id'] : null;
    $pdo->prepare("INSERT INTO categories (name, parent_id) VALUES (?,?)")->execute([$name, $parent_id]);
    $msg = 'Thêm danh mục thành công!';
    $msg_type = 'success';
    $tab = 'categories';
}
 
// ----- DANH MỤC: xóa -----
if (isset($_GET['action']) && $_GET['action'] === 'delete_category') {
    $pdo->prepare("DELETE FROM categories WHERE id = ?")->execute([(int)$_GET['id']]);
    $msg = 'Đã xóa danh mục.';
    $msg_type = 'success';
    $tab = 'categories';
}
 
// ----- DANH MỤC: sửa -----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_category'])) {
    $id        = (int)$_POST['cat_id'];
    $name      = trim($_POST['cat_name']);
    $parent_id = $_POST['parent_id'] !== '' ? (int)$_POST['parent_id'] : null;
    $pdo->prepare("UPDATE categories SET name=?, parent_id=? WHERE id=?")->execute([$name, $parent_id, $id]);
    $msg = 'Cập nhật danh mục thành công!';
    $msg_type = 'success';
    $tab = 'categories';
}

// ----- NGƯỜI DÙNG: khóa / mở tài khoản -----
if (isset($_GET['action']) && $_GET['action'] === 'toggle_active') {
    $uid = (int)$_GET['id'];
    if ((int)$uid !== (int)$user['id']) {
        $stmt = $pdo->prepare("SELECT is_active FROM users WHERE id = ?");
        $stmt->execute([$uid]);
        $current = $stmt->fetchColumn();
        $new_active = $current ? 0 : 1;
        $pdo->prepare("UPDATE users SET is_active = ? WHERE id = ?")->execute([$new_active, $uid]);
        $msg = $new_active ? 'Đã mở khóa tài khoản.' : 'Đã khóa tài khoản.';
        $msg_type = 'success';
    } else {
        $msg = 'Không thể khóa tài khoản đang đăng nhập!';
        $msg_type = 'danger';
    }
    $tab = 'users';
}

// ----- NGƯỜI DÙNG: đổi vai trò -----
if (isset($_GET['action']) && $_GET['action'] === 'update_role') {
    $uid      = (int)$_GET['id'];
    $new_role = $_GET['role'] ?? '';
    if (in_array($new_role, ['admin', 'user']) && $uid !== $user['id']) {
        $pdo->prepare("UPDATE users SET role = ? WHERE id = ?")->execute([$new_role, $uid]);
        $msg = 'Đã cập nhật vai trò.';
        $msg_type = 'success';
    }
    $tab = 'users';
}

// ============================================================
// LẤY DỮ LIỆU THEO TAB
// ============================================================
$orders = $products = $categories = $users_list = [];
$order_items_map = [];

if ($tab === 'dashboard' || $tab === 'orders') {
    $orders = $pdo->query("SELECT o.*, u.name as user_name FROM orders o LEFT JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC")->fetchAll();

    $order_items_map = [];
    $items_raw = $pdo->query("
        SELECT oi.*, p.name as product_name, p.image as product_image
        FROM order_items oi
        LEFT JOIN products p ON oi.product_id = p.id
    ")->fetchAll();
    foreach ($items_raw as $item) {
        $order_items_map[$item['order_id']][] = $item;
    }
}
if ($tab === 'products' || $tab === 'dashboard' || $tab === 'add_product' || $tab === 'edit_product') {
    $products = $pdo->query("SELECT p.*, c.name as cat_name FROM products p LEFT JOIN categories c ON p.category_id = c.id ORDER BY p.id DESC")->fetchAll();
}
$categories = $pdo->query("SELECT * FROM categories ORDER BY parent_id, name")->fetchAll();
 
if ($tab === 'users') {
    $users_list = $pdo->query("SELECT * FROM users ORDER BY id DESC")->fetchAll();
}
 
// Dashboard stats
$stat_orders   = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$stat_revenue  = $pdo->query("SELECT SUM(total) FROM orders WHERE status != 'cancelled'")->fetchColumn() ?? 0;
$stat_products = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$stat_users    = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$stat_pending  = $pdo->query("SELECT COUNT(*) FROM orders WHERE status='pending'")->fetchColumn();
 
// Sản phẩm cần sửa
$edit_product_data = null;
if ($tab === 'edit_product' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([(int)$_GET['id']]);
    $edit_product_data = $stmt->fetch();
}
 
$edit_category_data = null;
if ($tab === 'edit_category' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([(int)$_GET['id']]);
    $edit_category_data = $stmt->fetch();
}

// ===== statusLabel(): chuyển status sang nhãn tiếng Việt + màu badge =====
// Dùng ở tab dashboard và orders

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
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - December</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="./style.css?time=<?php echo time(); ?>">    
</head>
<body>
 
<div class="admin-wrap">
 
    <!-- ===== SIDEBAR ===== -->
    <div class="admin-sidebar">
        <div class="sidebar-logo">
            <h2>DECEMBER</h2>
            <span>ADMIN PANEL</span>
        </div>
 
        <div class="sidebar-nav">
            <div class="nav-section">Tổng quan</div>
            <a href="admin.php?tab=dashboard" class="<?php echo $tab === 'dashboard' ? 'active' : ''; ?>">
                <i class="fa-regular fa-chart-bar"></i> Dashboard
            </a>
 
            <div class="nav-section">Quản lý</div>
            <a href="admin.php?tab=orders" class="<?php echo $tab === 'orders' ? 'active' : ''; ?>">
                <i class="fa-regular fa-rectangle-list"></i> Đơn hàng
                <?php if ($stat_pending > 0): ?>
                <span style="margin-left:auto;background:#e74c3c;color:#fff;font-size:11px;padding:1px 7px;border-radius:10px;"><?php echo $stat_pending; ?></span>
                <?php endif; ?>
            </a>
            <a href="admin.php?tab=products" class="<?php echo in_array($tab, ['products','add_product','edit_product']) ? 'active' : ''; ?>">
                <i class="fa-regular fa-shirt"></i> Sản phẩm
            </a>
            <a href="admin.php?tab=categories" class="<?php echo in_array($tab, ['categories','edit_category']) ? 'active' : ''; ?>">
                <i class="fa-regular fa-folder"></i> Danh mục
            </a>
            <a href="admin.php?tab=users" class="<?php echo $tab === 'users' ? 'active' : ''; ?>">
                <i class="fa-regular fa-users"></i> Người dùng
            </a>
            <?php
            $stat_requests = $pdo->query("SELECT (SELECT COUNT(*) FROM order_cancellations WHERE status='pending') + (SELECT COUNT(*) FROM order_returns WHERE status='pending')")->fetchColumn();
            ?>
            <a href="admin.php?tab=requests" class="<?php echo $tab === 'requests' ? 'active' : ''; ?>">
                <i class="fa-regular fa-hand"></i> Yêu cầu hủy/hoàn
                    <?php if ($stat_requests > 0): ?>
                    <span style="margin-left:auto;background:#e74c3c;color:#fff;font-size:11px;padding:1px 7px;border-radius:10px;"><?php echo $stat_requests; ?></span>
                <?php endif; ?>
            </a>
            <a href="admin.php?tab=livechat" class="<?php echo $tab === 'livechat' ? 'active' : ''; ?>" id="sidebar-chat-link">
                <i class="fa-regular fa-message"></i> Chat với khách
                <span id="sidebar-unread-badge" ...></span>
            </a>
        </div>
 
        <div class="sidebar-footer">
            <a href="index.php" target="_blank">
                <i class="fa-regular fa-arrow-up-right-from-square"></i> Xem cửa hàng
            </a>
            <a href="logout.php">
                <i class="fa-regular fa-right-from-bracket"></i> Đăng xuất
            </a>
        </div>
    </div>
 
    <!-- ===== MAIN ===== -->
    <div class="admin-main">
 
        <!-- TOPBAR -->
        <div class="admin-topbar">
            <h5>
                <?php
                $titles = [
                    'dashboard'     => 'Dashboard',
                    'orders'        => 'Quản lý đơn hàng',
                    'products'      => 'Quản lý sản phẩm',
                    'add_product'   => 'Thêm sản phẩm',
                    'edit_product'  => 'Sửa sản phẩm',
                    'categories'    => 'Quản lý danh mục',
                    'edit_category' => 'Sửa danh mục',
                    'users'         => 'Quản lý người dùng',
                    'livechat'      => 'Chat với khách hàng',
                ];
                echo $titles[$tab] ?? 'Admin';
                ?>
            </h5>
            <div class="admin-user">
                <i class="fa-regular fa-user me-1"></i> <?php echo htmlspecialchars($user['name']); ?>
            </div>
        </div>
 
        <!-- BODY -->
        <div class="admin-body">
 
            <?php if ($msg): ?>
            <div class="admin-alert <?php echo $msg_type; ?>">
                <i class="fa-solid fa-circle-check me-2"></i><?php echo $msg; ?>
            </div>
            <?php endif; ?>
 
            <!-- =============================== DASHBOARD =============================== -->
            <?php if ($tab === 'dashboard'): ?>
 
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon" style="background:#fff3e0;">
                            <i class="fa-solid fa-bag-shopping" style="color:#f39c12;"></i>
                        </div>
                        <div>
                            <h3><?php echo $stat_orders; ?></h3>
                            <p>Tổng đơn hàng</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon" style="background:#e8f8f5;">
                            <i class="fa-solid fa-money-bill-wave" style="color:#27ae60;"></i>
                        </div>
                        <div>
                            <h3 style="font-size:18px;"><?php echo number_format($stat_revenue); ?>đ</h3>
                            <p>Doanh thu</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon" style="background:#eaf4fb;">
                            <i class="fa-solid fa-shirt" style="color:#2980b9;"></i>
                        </div>
                        <div>
                            <h3><?php echo $stat_products; ?></h3>
                            <p>Sản phẩm</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon" style="background:#f5eef8;">
                            <i class="fa-solid fa-users" style="color:#8e44ad;"></i>
                        </div>
                        <div>
                            <h3><?php echo $stat_users; ?></h3>
                            <p>Người dùng</p>
                        </div>
                    </div>
                </div>
            </div>
 
            <!-- Đơn hàng gần đây -->
            <div class="admin-card">
                <div class="admin-card-header">
                    <h5>Đơn hàng gần đây</h5>
                    <a href="admin.php?tab=orders" class="btn-admin-secondary" style="padding:6px 14px;font-size:13px;">Xem tất cả</a>
                </div>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>#</th><th>Khách hàng</th><th>Tổng tiền</th><th>Thanh toán</th><th>Trạng thái</th><th>Ngày</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach (array_slice($orders, 0, 8) as $o):
                        $s = statusLabel($o['status']); ?>
                    <tr>
                        <td>#<?php echo $o['id']; ?></td>
                        <td><?php echo htmlspecialchars($o['fullname']); ?></td>
                        <td class="fw-bold"><?php echo number_format($o['total']); ?>đ</td>
                        <td><?php echo $o['payment_method']; ?></td>
                        <td><span class="status-badge" style="background:<?php echo $s['color']; ?>"><?php echo $s['label']; ?></span></td>
                        <td style="color:#aaa;"><?php echo date('d/m H:i', strtotime($o['created_at'])); ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
 
            <!-- =============================== ĐƠN HÀNG =============================== -->
            <?php elseif ($tab === 'orders'): ?>
 
            <div class="admin-card">
                <div class="admin-card-header">
                    <h5>Tất cả đơn hàng (<?php echo count($orders); ?>)</h5>
                </div>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>#</th><th>Khách hàng</th><th>SĐT</th><th>Địa chỉ</th><th>Sản phẩm</th><th>Tổng tiền</th><th>Thanh toán</th><th>Trạng thái</th><th>Ngày</th><th></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($orders as $o):
                        $s = statusLabel($o['status']); ?>
                    <tr>
                        <td><strong>#<?php echo $o['id']; ?></strong></td>
                        <td>
                            <div><?php echo htmlspecialchars($o['fullname']); ?></div>
                            <div style="font-size:12px;color:#aaa;"><?php echo htmlspecialchars($o['user_name'] ?? ''); ?></div>
                        </td>
                        <td><?php echo $o['phone']; ?></td>

                        <td style="max-width:180px;font-size:12px;color:#666;"><?php echo htmlspecialchars($o['address']); ?></td>
                        <td>
                        <?php $items = $order_items_map[$o['id']] ?? []; ?>
                        <?php if (empty($items)): ?>
                        <span style="color:#bbb;font-size:12px;">—</span>
                    <?php else: ?>
                    <button type="button"
                    class="btn-admin-secondary"
                    style="padding:4px 12px;font-size:12px;cursor:pointer;"
                    onclick="showOrderItems(<?php echo $o['id']; ?>)">
                    <i class="fa-solid fa-box-open" style="margin-right:4px;"></i><?php echo count($items); ?> SP
                    </button>
                <?php endif; ?>
                    </td>
                <td class="fw-bold"><?php echo number_format($o['total']); ?>đ</td>


                        <td><?php echo $o['payment_method']; ?></td>
                        <td><span class="status-badge" style="background:<?php echo $s['color']; ?>"><?php echo $s['label']; ?></span></td>
                        <td style="color:#aaa;font-size:12px;"><?php echo date('d/m/Y H:i', strtotime($o['created_at'])); ?></td>
                        <td>
                            <select class="status-select" onchange="if(this.value) window.location='admin.php?tab=orders&action=update_status&id=<?php echo $o['id']; ?>&status='+this.value">
                                <option value="">Đổi trạng thái</option>
                                <option value="pending">Chờ xác nhận</option>
                                <option value="confirmed">Đã xác nhận</option>
                                <option value="shipping">Đang giao</option>
                                <option value="done">Hoàn thành</option>
                                <option value="cancelled">Đã hủy</option>
                            </select>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
 
            <!-- =============================== SẢN PHẨM =============================== -->
            <?php elseif ($tab === 'products'): ?>
 
            <div class="d-flex justify-content-between align-items-center mb-3">
                <span class="page-title" style="margin:0;">Sản phẩm (<?php echo count($products); ?>)</span>
                <a href="admin.php?tab=add_product" class="btn-admin-primary">
                    <i class="fa-solid fa-plus me-2"></i>Thêm sản phẩm
                </a>
            </div>
 
            <div class="admin-card">
                <table class="admin-table">
                    <thead>
                        <tr><th>Ảnh</th><th>Tên sản phẩm</th><th>Danh mục</th><th>Giá</th><th>Kho</th><th></th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($products as $p):
                        $imgs = explode(',', $p['image']);
                        $thumb = trim($imgs[0]);
                    ?>
                    <tr>
                        <td>
                            <?php if ($thumb): ?>
                            <img src="uploads/<?php echo $thumb; ?>" class="product-thumb">
                            <?php else: ?>
                            <div class="product-thumb d-flex align-items-center justify-content-center bg-light text-secondary" style="font-size:11px;">No img</div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="fw-bold"><?php echo htmlspecialchars($p['name']); ?></div>
                            <div style="font-size:12px;color:#aaa;">ID: <?php echo $p['id']; ?></div>
                        </td>
                        <td style="font-size:13px;"><?php echo htmlspecialchars($p['cat_name'] ?? '—'); ?></td>
                        <td class="fw-bold"><?php echo number_format($p['price']); ?>đ</td>
                        <td><?php echo $p['stock']; ?></td>
                        <td>
                            <a href="admin.php?tab=edit_product&id=<?php echo $p['id']; ?>" class="btn-icon btn-edit-soft"><i class="fa-solid fa-pen"></i></a>
                            <a href="admin.php?tab=products&action=delete_product&id=<?php echo $p['id']; ?>"
                               class="btn-icon btn-danger-soft"
                               onclick="return confirm('Xóa sản phẩm này?')">
                               <i class="fa-solid fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
 
            <!-- =============================== THÊM SẢN PHẨM =============================== -->
            <?php elseif ($tab === 'add_product'): ?>
 
            <div class="admin-form-card">
                <h5>Thêm sản phẩm mới</h5>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="add_product" value="1">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Tên sản phẩm</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Danh mục</label>
                            <select name="category_id" class="form-select" required>
                                <option value="">-- Chọn danh mục --</option>
                                <?php foreach ($categories as $c): ?>
                                <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                       <div class="col-md-6">
                            <label class="form-label">Giá gốc (đ)</label>
                            <input type="number" name="price" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Giá sale (đ) <span style="font-size:11px;color:#aaa;">— để trống nếu không giảm</span></label>
                            <input type="number" name="sale_price" class="form-control" placeholder="VD: 299000">
                        </div>
                            
                        <div class="col-md-4">
                            <label class="form-label">Tồn kho</label>
                            <input type="number" name="stock" class="form-control" value="50">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Mô tả</label>
                            <textarea name="description" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Ảnh sản phẩm</label>
                            <input type="file" name="images[]" class="form-control" accept="image/*" multiple>
                        <div id="preview-add" style="display:flex;flex-wrap:wrap;gap:8px;margin-top:10px;"></div>
                        </div>
                        <div class="col-12 d-flex gap-2 mt-2">
                            <button type="submit" class="btn-admin-primary">Thêm sản phẩm</button>
                            <a href="admin.php?tab=products" class="btn-admin-secondary">Hủy</a>
                        </div>
                    </div>
                </form>
            </div>
 
            <!-- =============================== SỬA SẢN PHẨM =============================== -->
            <?php elseif ($tab === 'edit_product' && $edit_product_data): ?>
 
            <div class="admin-form-card">
                <h5>Sửa sản phẩm #<?php echo $edit_product_data['id']; ?></h5>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="edit_product" value="1">
                    <input type="hidden" name="product_id" value="<?php echo $edit_product_data['id']; ?>">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Tên sản phẩm</label>
                            <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($edit_product_data['name']); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Danh mục</label>
                            <select name="category_id" class="form-select">
                                <?php foreach ($categories as $c): ?>
                                <option value="<?php echo $c['id']; ?>" <?php echo $c['id'] == $edit_product_data['category_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($c['name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Giá gốc (đ)</label>
                            <input type="number" name="price" class="form-control" value="<?php echo $edit_product_data['price']; ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Giá sale (đ) <span style="font-size:11px;color:#aaa;">— để trống nếu không giảm</span></label>
                            <input type="number" name="sale_price" class="form-control" value="<?php echo $edit_product_data['sale_price']; ?>" placeholder="VD: 299000">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Tồn kho</label>
                            <input type="number" name="stock" class="form-control" value="<?php echo $edit_product_data['stock']; ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Mô tả</label>
                            <textarea name="description" class="form-control" rows="3"><?php echo htmlspecialchars($edit_product_data['description']); ?></textarea>
                        </div>
                        <div class="col-12">
                        <label class="form-label">Ảnh hiện tại</label>
                        <?php
                        $current_imgs = array_filter(explode(',', $edit_product_data['image']));
                        if ($current_imgs): ?>
                        <div style="display:flex;flex-wrap:wrap;gap:10px;margin-bottom:12px;">
                            <?php foreach ($current_imgs as $img): $img = trim($img); ?>
                            <div style="position:relative;">
                                <img src="uploads/<?php echo htmlspecialchars($img); ?>"
                                    style="width:80px;height:100px;object-fit:cover;border-radius:8px;border:1px solid #eee;">
                                <label style="position:absolute;top:4px;right:4px;background:rgba(220,38,38,.85);border-radius:50%;width:22px;height:22px;display:flex;align-items:center;justify-content:center;cursor:pointer;" title="Xóa ảnh này">
                                    <input type="checkbox" name="delete_images[]" value="<?php echo htmlspecialchars($img); ?>" style="display:none;" onchange="this.closest('div[style]').style.opacity=this.checked?'0.4':'1'">
                                    <svg width="12" height="12" fill="#fff" viewBox="0 0 24 24"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
                                </label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <p style="font-size:12px;color:#aaa;margin-bottom:8px;">Click icon ✕ đỏ để đánh dấu xóa ảnh</p>
                        <?php endif; ?>
                        <label class="form-label">Thêm ảnh mới</label>
                        <input type="file" name="images[]" class="form-control" accept="image/*" multiple>
                        <div id="preview-edit" style="display:flex;flex-wrap:wrap;gap:8px;margin-top:10px;"></div>
                    </div>
                        <div class="col-12 d-flex gap-2 mt-2">
                            <button type="submit" class="btn-admin-primary">Lưu thay đổi</button>
                            <a href="admin.php?tab=products" class="btn-admin-secondary">Hủy</a>
                        </div>
                    </div>
                </form>
            </div>
 
            <!-- =============================== DANH MỤC =============================== -->
            <?php elseif ($tab === 'categories'): ?>
 
            <div class="row g-4">
                <!-- Danh sách -->
                <div class="col-md-7">
                    <div class="admin-card">
                        <div class="admin-card-header">
                            <h5>Danh sách danh mục</h5>
                        </div>
                        <table class="admin-table">
                            <thead><tr><th>ID</th><th>Tên</th><th>Danh mục cha</th><th></th></tr></thead>
                            <tbody>
                            <?php foreach ($categories as $c):
                                $parent_name = '—';
                                foreach ($categories as $cc) {
                                    if ($cc['id'] == $c['parent_id']) { $parent_name = $cc['name']; break; }
                                }
                            ?>
                            <tr>
                                <td style="color:#aaa;"><?php echo $c['id']; ?></td>
                                <td class="fw-bold"><?php echo htmlspecialchars($c['name']); ?></td>
                                <td style="font-size:13px;color:#888;"><?php echo $parent_name; ?></td>
                                <td>
                                    <a href="admin.php?tab=edit_category&id=<?php echo $c['id']; ?>" class="btn-icon btn-edit-soft"><i class="fa-solid fa-pen"></i></a>
                                    <a href="admin.php?tab=categories&action=delete_category&id=<?php echo $c['id']; ?>"
                                       class="btn-icon btn-danger-soft"
                                       onclick="return confirm('Xóa danh mục này?')">
                                       <i class="fa-solid fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
 
                <!-- Form thêm -->
                <div class="col-md-5">
                    <div class="admin-form-card" style="max-width:100%;">
                        <h5>Thêm danh mục mới</h5>
                        <form method="POST">
                            <input type="hidden" name="add_category" value="1">
                            <div class="mb-3">
                                <label class="form-label">Tên danh mục</label>
                                <input type="text" name="cat_name" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Danh mục cha (nếu có)</label>
                                <select name="parent_id" class="form-select">
                                    <option value="">-- Không có (đây là danh mục cha) --</option>
                                <?php foreach ($categories as $c): ?>
                                <?php if ($c['parent_id'] === null || $c['parent_id'] === ''): ?>
                                <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['name']); ?></option>
                                <?php endif; ?>
                                <?php endforeach; ?>
                                </select>
                            </div>
                            <button type="submit" class="btn-admin-primary">Thêm danh mục</button>
                        </form>
                    </div>
                </div>
            </div>
 
            <!-- =============================== SỬA DANH MỤC =============================== -->
            <?php elseif ($tab === 'edit_category' && $edit_category_data): ?>
 
            <div class="admin-form-card">
                <h5>Sửa danh mục</h5>
                <form method="POST">
                    <input type="hidden" name="edit_category" value="1">
                    <input type="hidden" name="cat_id" value="<?php echo $edit_category_data['id']; ?>">
                    <div class="mb-3">
                        <label class="form-label">Tên danh mục</label>
                        <input type="text" name="cat_name" class="form-control" value="<?php echo htmlspecialchars($edit_category_data['name']); ?>" required>
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Danh mục cha</label>
                        <select name="parent_id" class="form-select">
                            <option value="">-- Không có --</option>
                            <?php foreach ($categories as $c):
                                if ($c['id'] == $edit_category_data['id']) continue; ?>
                            <option value="<?php echo $c['id']; ?>" <?php echo $c['id'] == $edit_category_data['parent_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($c['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn-admin-primary">Lưu thay đổi</button>
                        <a href="admin.php?tab=categories" class="btn-admin-secondary">Hủy</a>
                    </div>
                </form>
            </div>
            
            <!-- =============================== YÊU CẦU HỦY / HOÀN =============================== -->
<?php elseif ($tab === 'requests'): ?>

<?php
$cancels = $pdo->query("
    SELECT oc.*, o.fullname, o.total, u.name as user_name
    FROM order_cancellations oc
    JOIN orders o ON oc.order_id = o.id
    JOIN users u ON oc.user_id = u.id
    ORDER BY oc.created_at DESC
")->fetchAll();
$returns = $pdo->query("
    SELECT or2.*, o.fullname, o.total, u.name as user_name
    FROM order_returns or2
    JOIN orders o ON or2.order_id = o.id
    JOIN users u ON or2.user_id = u.id
    ORDER BY or2.created_at DESC
")->fetchAll();
$colors = ['pending'=>'#f39c12','approved'=>'#27ae60','rejected'=>'#e74c3c'];
$labels = ['pending'=>'Chờ duyệt','approved'=>'Đã duyệt','rejected'=>'Từ chối'];
?>

<div class="admin-card mb-4">
    <div class="admin-card-header"><h5>Yêu cầu hủy đơn (<?php echo count($cancels); ?>)</h5></div>
    <table class="admin-table">
        <thead><tr><th>Đơn #</th><th>Khách hàng</th><th>Lý do</th><th>Tổng tiền</th><th>Ngày gửi</th><th>Trạng thái</th><th></th></tr></thead>
        <?php foreach ($cancels as $c): ?>
        <tr>
            <td><strong>#<?php echo $c['order_id']; ?></strong></td>
            <td><?php echo htmlspecialchars($c['fullname']); ?><br><span style="font-size:12px;color:#aaa;"><?php echo htmlspecialchars($c['user_name']); ?></span></td>
            <td style="font-size:13px;"><?php echo htmlspecialchars($c['reason']); ?></td>
            <td><?php echo number_format($c['total']); ?>đ</td>
            <td style="font-size:12px;color:#aaa;"><?php echo date('d/m/Y H:i', strtotime($c['created_at'])); ?></td>
            <td><span class="status-badge" style="background:<?php echo $colors[$c['status']]; ?>"><?php echo $labels[$c['status']]; ?></span></td>
            <td>
                <?php if ($c['status'] === 'pending'): ?>
                <a href="admin.php?tab=requests&action=review_cancel&id=<?php echo $c['id']; ?>&result=approved"
                   class="btn-icon btn-edit-soft" title="Duyệt"
                   onclick="return confirm('Duyệt hủy đơn #<?php echo $c['order_id']; ?>?')">
                   <i class="fa-solid fa-check"></i>
                </a>
                <a href="admin.php?tab=requests&action=review_cancel&id=<?php echo $c['id']; ?>&result=rejected"
                   class="btn-icon btn-danger-soft" title="Từ chối"
                   onclick="return confirm('Từ chối yêu cầu hủy này?')">
                   <i class="fa-solid fa-xmark"></i>
                </a>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($cancels)): ?>
        <tr><td colspan="7" style="text-align:center;color:#aaa;padding:24px;">Chưa có yêu cầu hủy nào.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="admin-card">
    <div class="admin-card-header"><h5>Yêu cầu hoàn hàng (<?php echo count($returns); ?>)</h5></div>
    <table class="admin-table">
        <thead><tr><th>Đơn #</th><th>Khách hàng</th><th>Lý do</th><th>Ảnh</th><th>Tổng tiền</th><th>Ngày gửi</th><th>Trạng thái</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($returns as $r): ?>
        <tr>
            <td><strong>#<?php echo $r['order_id']; ?></strong></td>
            <td><?php echo htmlspecialchars($r['fullname']); ?><br><span style="font-size:12px;color:#aaa;"><?php echo htmlspecialchars($r['user_name']); ?></span></td>
            <td style="font-size:13px;"><?php echo htmlspecialchars($r['reason']); ?></td>
            <td>
                    <?php if (!empty($r['image'])): ?>
                    <img src="uploads/<?php echo htmlspecialchars($r['image']); ?>"
                    style="width:48px;height:48px;object-fit:cover;border-radius:6px;border:1px solid #eee;cursor:zoom-in;"
                    onclick="window.open('uploads/<?php echo htmlspecialchars($r['image']); ?>')">
                <?php else: ?>
                <span style="color:#bbb;font-size:12px;">—</span>
                <?php endif; ?>
            </td>
            <td><?php echo number_format($r['total']); ?>đ</td>
            <td style="font-size:12px;color:#aaa;"><?php echo date('d/m/Y H:i', strtotime($r['created_at'])); ?></td>
            <td><span class="status-badge" style="background:<?php echo $colors[$r['status']]; ?>"><?php echo $labels[$r['status']]; ?></span></td>
            <td>
                <?php if ($r['status'] === 'pending'): ?>
                <a href="admin.php?tab=requests&action=review_return&id=<?php echo $r['id']; ?>&result=approved"
                   class="btn-icon btn-edit-soft" title="Duyệt"
                   onclick="return confirm('Duyệt hoàn hàng đơn #<?php echo $r['order_id']; ?>?')">
                   <i class="fa-solid fa-check"></i>
                </a>
                <a href="admin.php?tab=requests&action=review_return&id=<?php echo $r['id']; ?>&result=rejected"
                   class="btn-icon btn-danger-soft" title="Từ chối"
                   onclick="return confirm('Từ chối yêu cầu hoàn hàng này?')">
                   <i class="fa-solid fa-xmark"></i>
                </a>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($returns)): ?>
        <tr><td colspan="7" style="text-align:center;color:#aaa;padding:24px;">Chưa có yêu cầu hoàn hàng nào.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>
            <?php elseif ($tab === 'livechat'): ?>
<!-- =============================== LIVE CHAT =============================== -->
<div class="livechat-wrap">
    <!-- Danh sách user bên trái -->
    <div class="livechat-users" id="chat-user-list">
        <div class="livechat-users-header">
            <i class="fa-solid fa-users" style="color:#355638;"></i> Khách hàng
        </div>
        <div id="chat-users-body">
            <div style="text-align:center;padding:32px 0;color:#bbb;font-size:13px;">
                <i class="fa-solid fa-spinner fa-spin"></i><br>Đang tải...
            </div>
        </div>
    </div>
    <!-- Khu vực chat bên phải -->
    <div class="livechat-main" id="chat-main">
        <div class="livechat-empty" id="chat-empty-state">
            <i class="fa-regular fa-comment-dots" style="font-size:48px;color:#ddd;display:block;margin-bottom:14px;"></i>
            Chọn một khách hàng để bắt đầu trả lời
        </div>
        <div id="chat-active" style="display:none;height:100%;flex-direction:column;">
            <div class="livechat-chat-header" id="chat-header-name">—</div>
            <div class="livechat-messages" id="chat-messages"></div>
            <div class="livechat-input-row">
                <textarea id="admin-chat-input" placeholder="Nhập tin nhắn..." rows="1"></textarea>
                <button id="admin-chat-send">
                    <i class="fa-solid fa-paper-plane"></i>
                </button>
            </div>
        </div>
    </div>
</div>


<script>
// ===== LIVE CHAT ADMIN =====
// Gọi API: chat_api.php
// loadUsers()    → lấy danh sách user đã nhắn, tự reload mỗi 8 giây
// selectUser()   → chọn user để chat, bắt đầu poll tin nhắn mỗi 3 giây
// loadMessages() → lấy tin nhắn mới (dùng since_id để chỉ lấy tin chưa có)
// sendMessage()  → gửi tin nhắn qua POST JSON
// Badge sidebar  → cộng tổng unread của tất cả user, hiển thị số đỏ
(function() {
    const API = 'chat_api.php';
    let activeUserId   = null;
    let activeUserName = '';
    let lastMsgId      = 0;
    let pollTimer      = null;
// ── Tải danh sách user ─────────────────────────────
    async function loadUsers() {
        const res  = await fetch(`${API}?action=get_users`);
        const data = await res.json();
        if (!data.ok) return;
        const body = document.getElementById('chat-users-body');
        if (!data.users.length) {
            body.innerHTML = '<div style="text-align:center;padding:32px 0;color:#bbb;font-size:13px;">Chưa có cuộc trò chuyện nào</div>';
            return;
        }
        let totalUnread = 0;
        body.innerHTML = data.users.map(u => {
            const unread  = parseInt(u.unread) || 0;
            totalUnread  += unread;
            const initial = (u.name || '?')[0].toUpperCase();
            const preview = u.last_msg ? u.last_msg.substring(0, 36) + (u.last_msg.length > 36 ? '…' : '') : '';
            const time    = u.last_time ? formatTime(u.last_time) : '';
            const isActive = u.id == activeUserId ? 'active' : '';
            const badge   = unread > 0 ? `<span class="chat-user-unread">${unread}</span>` : '';
            return `
                <div class="chat-user-item ${isActive}" onclick="selectUser(${u.id}, '${escHtml(u.name)}')">
                    <div class="chat-user-avatar">${initial}</div>
                    <div style="flex:1;min-width:0;">
                        <div class="chat-user-name">${escHtml(u.name)}</div>
                        <div class="chat-user-preview">${escHtml(preview)}</div>
                    </div>
                    ${badge}
                    <span class="chat-user-time">${time}</span>
                </div>`;
        }).join('');

         // Cập nhật badge sidebar
        const badge = document.getElementById('sidebar-unread-badge');
        if (badge) {
            badge.textContent   = totalUnread;
            badge.style.display = totalUnread > 0 ? 'inline-block' : 'none';
        }
    }
            
        // ── Chọn user để chat ─────────────────────────
    window.selectUser = async function(userId, userName) {
        activeUserId   = userId;
        activeUserName = userName;
        lastMsgId      = 0;
        document.getElementById('chat-empty-state').style.display = 'none';
        const chatActive = document.getElementById('chat-active');
        chatActive.style.display = 'flex';
        document.getElementById('chat-header-name').innerHTML =
            `<div class="chat-user-avatar" style="width:30px;height:30px;font-size:12px;">${userName[0].toUpperCase()}</div> ${escHtml(userName)}`;
        document.getElementById('chat-messages').innerHTML = '';
        document.getElementById('admin-chat-input').focus();
        await loadMessages();
        loadUsers();
        clearInterval(pollTimer);
        pollTimer = setInterval(loadMessages, 3000);
    };
         // ── Load messages ────────────────────────────
    async function loadMessages() {
        if (!activeUserId) return;
        const res  = await fetch(`${API}?action=get_messages&user_id=${activeUserId}&since_id=${lastMsgId}`);
        const data = await res.json();
        if (!data.ok || !data.messages.length) return;
        const box = document.getElementById('chat-messages');
        data.messages.forEach(m => {
            const isAdmin = m.sender === 'admin';
            const div     = document.createElement('div');
            div.className = `lc-msg ${isAdmin ? 'lc-admin' : 'lc-user'}`;
            div.innerHTML = `
                <div class="lc-sender-label">${isAdmin ? 'Bạn (Admin)' : escHtml(activeUserName)}</div>
                <div class="lc-bubble">${escHtml(m.message)}</div>
                <div class="lc-time">${formatTime(m.created_at)}</div>`;
            box.appendChild(div);
            lastMsgId = Math.max(lastMsgId, parseInt(m.id));
        });
        box.scrollTop = box.scrollHeight;
        loadUsers();
    }

    async function sendMessage() {
        const input = document.getElementById('admin-chat-input');
        const msg   = input.value.trim();
        if (!msg || !activeUserId) return;
        input.value = '';
        input.style.height = 'auto';
        await fetch(API + '?action=send', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ message: msg, user_id: activeUserId })
        });
        await loadMessages();
    }

    document.getElementById('admin-chat-send').addEventListener('click', sendMessage);
    document.getElementById('admin-chat-input').addEventListener('keydown', e => {
        if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendMessage(); }
    });
    document.getElementById('admin-chat-input').addEventListener('input', function() {
        this.style.height = 'auto';
        this.style.height = Math.min(this.scrollHeight, 100) + 'px';
    });

    function escHtml(str) {
        return String(str||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }
    function formatTime(dt) {
        const d   = new Date(dt);
        const now = new Date();
        const hm  = d.getHours().toString().padStart(2,'0') + ':' + d.getMinutes().toString().padStart(2,'0');
        return d.toDateString() === now.toDateString() ? hm : `${d.getDate()}/${d.getMonth()+1} ${hm}`;
    }

    loadUsers();
    setInterval(loadUsers, 8000);
})();
</script>

            <!-- =============================== NGƯỜI DÙNG =============================== -->
            <?php elseif ($tab === 'users'): ?>
 
            <div class="admin-card">
                <div class="admin-card-header">
                    <h5>Người dùng (<?php echo count($users_list); ?>)</h5>
                </div>
                <table class="admin-table">
                    <thead><tr><th>ID</th><th>Tên</th><th>Email</th><th>Vai trò</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($users_list as $u): ?>
                    <tr>
                        <td style="color:#aaa;"><?php echo $u['id']; ?></td>
                        <td class="fw-bold"><?php echo htmlspecialchars($u['name']); ?></td>
                        <td><?php echo htmlspecialchars($u['email']); ?></td>
                        <td>
            <select class="status-select"
                onchange="if(this.value) window.location='admin.php?tab=users&action=update_role&id=<?php echo $u['id']; ?>&role='+this.value">
                <option value="">
                    <?php echo $u['role'] === 'admin' ? 'Admin' : '👤 User'; ?>
                </option>
                <?php if ($u['role'] !== 'admin'): ?>
                    <option value="admin">→ Admin</option>
                <?php else: ?>
                    <option value="user">→ User</option>
                <?php endif; ?>
            </select>
        </td>
        <td>
    <?php if ((int)$u['id'] !== (int)$user['id']): ?>
        <?php if ($u['is_active']): ?>
        <a href="admin.php?tab=users&action=toggle_active&id=<?php echo $u['id']; ?>"
           class="btn-icon btn-danger-soft"
           title="Khóa tài khoản"
           onclick="return confirm('Khóa tài khoản <?php echo htmlspecialchars($u['name']); ?>?')">
           <i class="fa-solid fa-lock"></i>
        </a>
        <?php else: ?>
        <a href="admin.php?tab=users&action=toggle_active&id=<?php echo $u['id']; ?>"
           class="btn-icon btn-edit-soft"
           title="Mở khóa tài khoản"
           onclick="return confirm('Mở khóa tài khoản <?php echo htmlspecialchars($u['name']); ?>?')">
           <i class="fa-solid fa-lock-open"></i>
        </a>
        <?php endif; ?>
    <?php else: ?>
        <span style="font-size:11px;color:#bbb;">Bạn</span>
    <?php endif; ?>
</td>
    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
 
            <?php endif; ?>
 
        </div><!-- /admin-body -->
    </div><!-- /admin-main -->
</div><!-- /admin-wrap -->
 <!-- Modal chi tiết sản phẩm -->
<div class="modal fade" id="orderItemsModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h6 class="modal-title fw-bold">Chi tiết đơn hàng <span id="modal-order-id"></span></h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-0">
        <table class="admin-table">
          <thead>
            <tr>
              <th>Ảnh</th>
              <th>Tên sản phẩm</th>
              <th>Đơn giá</th>
              <th>SL</th>
              <th>Thành tiền</th>
            </tr>
          </thead>
          <tbody id="modal-items-body"></tbody>
          <tfoot id="modal-items-foot"></tfoot>
        </table>
      </div>
    </div>
  </div>
</div>

<script>
// ===== MODAL CHI TIẾT ĐƠN HÀNG =====
const orderItemsData = <?php echo json_encode($order_items_map); ?>;

function showOrderItems(orderId) {
    const items = orderItemsData[orderId] || [];
    document.getElementById('modal-order-id').textContent = '#' + orderId;

    let rows = '';
    let grandTotal = 0;

    items.forEach(item => {
        const imgs = (item.product_image || '').split(',');
        const thumb = imgs[0].trim();
        const imgHtml = thumb
            ? `<img src="uploads/${thumb}" class="product-thumb">`
            : `<div class="product-thumb d-flex align-items-center justify-content-center bg-light text-secondary" style="font-size:10px;">No img</div>`;

        const price = parseInt(item.price) || 0;
        const qty   = parseInt(item.quantity) || 0;
        const sub   = price * qty;
        grandTotal += sub;

        rows += `<tr>
            <td>${imgHtml}</td>
            <td><span class="fw-bold">${item.product_name || '—'}</span></td>
            <td>${price.toLocaleString('vi-VN')}đ</td>
            <td class="fw-bold">${qty}</td>
            <td class="fw-bold">${sub.toLocaleString('vi-VN')}đ</td>
        </tr>`;
    });

    document.getElementById('modal-items-body').innerHTML = rows;
    document.getElementById('modal-items-foot').innerHTML = `
        <tr>
            <td colspan="4">Tổng cộng</td>
            <td>${grandTotal.toLocaleString('vi-VN')}đ</td>
        </tr>`;

    new bootstrap.Modal(document.getElementById('orderItemsModal')).show();
}
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// ===== PREVIEW ẢNH TRƯỚC KHI UPLOAD =====
document.addEventListener('change', function(e) {
    if (e.target && e.target.name === 'images[]') {
        const preview = e.target.closest('form').querySelector('[id^="preview-"]');
        if (!preview) return;
        preview.innerHTML = '';
        Array.from(e.target.files).forEach(file => {
            const reader = new FileReader();
            reader.onload = function(ev) {
                const img = document.createElement('img');
                img.src = ev.target.result;
                img.style.cssText = 'width:80px;height:100px;object-fit:cover;border-radius:8px;border:1px solid #eee;';
                preview.appendChild(img);
            };
            reader.readAsDataURL(file);
        });
    }
});
</script>
</body>
</html>