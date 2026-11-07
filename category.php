<?php
include 'db.php';
include 'header.php';
$user_id = $_SESSION['user']['id'] ?? null;
$user_fav_ids = [];
if ($user_id) {
    $stmtFav = $pdo->prepare("SELECT product_id FROM favorites WHERE user_id = ?");
    $stmtFav->execute([$user_id]);
    $user_fav_ids = $stmtFav->fetchAll(PDO::FETCH_COLUMN);
}
$category_id = $_GET['id'] ?? 0;

/* ===== LẤY THÔNG TIN CATEGORY ===== */
$stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
$stmt->execute([$category_id]);
$category = $stmt->fetch();

/* ===== KIỂM TRA CÓ CATEGORY CON KHÔNG ===== */
$stmt = $pdo->prepare("SELECT id FROM categories WHERE parent_id = ?");
$stmt->execute([$category_id]);
$child_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

/* ===== QUERY SẢN PHẨM ===== */
if(count($child_ids) > 0){
    // 👉 là category CHA → lấy tất cả sản phẩm của con
    $placeholders = implode(',', array_fill(0, count($child_ids), '?'));

    $stmt = $pdo->prepare("SELECT * FROM products WHERE category_id IN ($placeholders)");
    $stmt->execute($child_ids);
}else{
    // 👉 là category CON → lấy trực tiếp
    $stmt = $pdo->prepare("SELECT * FROM products WHERE category_id = ?");
    $stmt->execute([$category_id]);
}

$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<div class="container mt-4 product-list">

<h2>Danh sách sản phẩm</h2>

<div class="row">
<?php foreach($products as $p){ ?>
    <!-- Mỗi sản phẩm nằm trong col-md-3 để hiện ngang 4 cái/hàng -->
    <div class="col-md-3 col-6 mb-5">
        <div class="card h-100 border-0 bg-transparent">
    <?php
    // Xử lý chuỗi ảnh: tách dấu phẩy, xóa khoảng trắng và lấy ảnh đầu tiên
    $images = explode(',', $p['image']);
    $images = array_filter(array_map('trim', $images));
    $first_image = !empty($images) ? reset($images) : 'no-image.jpg';
    $discount = !empty($p['sale_price']) ? round((1 - $p['sale_price'] / $p['price']) * 100) : 0;
    ?>

    <div style="position:relative;">
    <a href="product.php?id=<?php echo $p['id']; ?>">
        <img src="uploads/<?php echo $first_image; ?>" class="card-img-top">
    </a>
    <?php if (!empty($p['sale_price'])): ?>
    <span style="position:absolute;top:8px;left:8px;background:#355638;color:#fff;font-size:11px;font-weight:700;padding:3px 8px;border-radius:4px;">
        -<?php echo $discount; ?>%
    </span>
    <?php endif; ?>
    </div>

            <div class="card-body text-center p-2">
                <!-- Tên sản phẩm viết hoa, font nhỏ -->
                <h6 class="fw-normal small text-uppercase mb-2" style="font-size: 13px;">
                    <?php echo htmlspecialchars($p['name']); ?>
                </h6>
                
                <!-- Giá sản phẩm màu hồng đậm -->
                <p class="small fw-bold mb-2">
                    <?php if (!empty($p['sale_price'])): ?>
                        <span style="color:#355638;"><?php echo number_format($p['sale_price']); ?>đ</span>
                        <span style="color:#aaa;font-weight:400;text-decoration:line-through;margin-left:4px;font-size:12px;">
                            <?php echo number_format($p['price']); ?>đ
                        </span>
                    <?php else: ?>
                        <span style="color:#355638;"><?php echo number_format($p['price']); ?>đ</span>
                    <?php endif; ?>
                </p>
                <p class="small mb-2" style="font-size:12px;color:#687363;">
                    <?php echo ((int)$p['stock'] > 0) ? 'Còn ' . (int)$p['stock'] . ' sản phẩm' : 'Hết hàng'; ?>
                </p>

                <!-- Nút yêu thích -->
                <?php 
                $isFav = in_array($p['id'], $user_fav_ids); 
                ?>
                <button class="btn btn-favorite border-0 p-0 <?php echo $isFav ? 'active' : ''; ?>" 
                data-id="<?php echo $p['id']; ?>" 
                style="background: none; color: #355638; font-size: 14px;">
                <?php echo $isFav ? 'Đã yêu thích ❤️' : 'Yêu thích ❤️'; ?>
                </button>
            </div>
        </div> 
    </div> 
<?php } ?>
</div>
</div>
</div>
<?php include 'footer.php'; ?>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>