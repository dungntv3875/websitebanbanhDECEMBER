<?php
include 'db.php';
$id = $_GET['id'] ?? 0;
include 'header.php';

$user_id = $_SESSION['user']['id'] ?? null;
$user_fav_ids = [];

if ($user_id) {
    $stmtFav = $pdo->prepare("SELECT product_id FROM favorites WHERE user_id = ?");
    $stmtFav->execute([$user_id]);
    $user_fav_ids = $stmtFav->fetchAll(PDO::FETCH_COLUMN);
}

// lấy sản phẩm
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch();
// Tách chuỗi ảnh trong cột 'image' thành một mảng (array)
// Nếu trong DB là "a.jpg,b.jpg" thì $all_images sẽ là ['a.jpg', 'b.jpg']
$all_images = !empty($product['image']) ? explode(',', $product['image']) : [];
$main_image = $all_images[0] ?? 'default.jpg'; // Lấy ảnh đầu tiên làm ảnh chính

if(!$product){
    echo "Không tìm thấy sản phẩm";
    exit;
}

// sản phẩm liên quan
$stmt = $pdo->prepare("SELECT * FROM products 
WHERE category_id = ? AND id != ?");
$stmt->execute([$product['category_id'], $id]);
$related = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ----- ĐÁNH GIÁ -----
// Lấy tất cả đánh giá của sản phẩm
$stmt_reviews = $pdo->prepare("
    SELECT r.*, u.name as user_name
    FROM reviews r
    JOIN users u ON r.user_id = u.id
    WHERE r.product_id = ?
    ORDER BY r.created_at DESC
");
$stmt_reviews->execute([$id]);
$reviews = $stmt_reviews->fetchAll();

// Tính trung bình sao và đếm theo từng mức
$total_reviews = count($reviews);
$avg_rating    = 0;
$star_count    = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
foreach ($reviews as $rv) {
    $star_count[$rv['rating']]++;
    $avg_rating += $rv['rating'];
}
if ($total_reviews > 0) $avg_rating = round($avg_rating / $total_reviews, 1);

// Kiểm tra user đã mua sản phẩm này chưa (đơn status = done)
$can_review   = false;
$has_reviewed = false;
$review_order_id = null;

if ($user_id) {
    $stmt_bought = $pdo->prepare("
        SELECT o.id FROM orders o
        JOIN order_items oi ON o.id = oi.order_id
        WHERE o.user_id = ? AND oi.product_id = ? AND o.status = 'done'
        LIMIT 1
    ");
    $stmt_bought->execute([$user_id, $id]);
    $bought = $stmt_bought->fetch();

    if ($bought) {
        $can_review      = true;
        $review_order_id = $bought['id'];

        // Kiểm tra đã review chưa
        $stmt_checked = $pdo->prepare("SELECT id FROM reviews WHERE user_id = ? AND product_id = ? AND order_id = ?");
        $stmt_checked->execute([$user_id, $id, $review_order_id]);
        $has_reviewed = (bool)$stmt_checked->fetch();
    }
}
?>


<!DOCTYPE html>
<html>
<head>
    <title><?php echo $product['name']; ?></title>
    <meta name="product-id" content="<?php echo (int)$product['id']; ?>">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
</head>

<body>

<div class="container mt-5">

    <!-- PRODUCT DETAIL -->
    <div class="row product-detail">

        <div class="col-md-6 product-left">
    <!-- KHUNG ẢNH CHÍNH -->
<div class="main-image-container mb-3 text-center border rounded bg-light" style="overflow: hidden; aspect-ratio: 1 / 1;">
    <img src="uploads/<?php echo trim($main_image); ?>" 
         id="main-product-img" 
         class="img-fluid" 
         style="width:100%; height:100%; object-fit: contain;">
    </div>

    <!-- DANH SÁCH ẢNH PHỤ (THUMBNAILS) -->
    <div class="product-thumbnails d-flex gap-2 overflow-auto pb-2">
        <?php foreach($all_images as $index => $img): ?>
            <div class="thumb-item <?php echo $index === 0 ? 'active' : ''; ?>" 
                 onclick="changeImage('uploads/<?php echo trim($img); ?>', this)"
                 style="width: 80px; height: 80px; cursor: pointer; border: 1px solid #ddd; transition: 0.3s;">
                <img src="uploads/<?php echo trim($img); ?>" 
                     class="w-100 h-100" 
                     style="object-fit: cover;">
            </div>
        <?php endforeach; ?>
    </div>
</div>

        <div class="col-md-6 product-right">
            <h2><?php echo $product['name']; ?></h2>
            <p class="product-code">Mã sản phẩm: <?php echo $product['id']; ?></p>
            
            <?php
            $flash_error = $_SESSION['stock_error'] ?? '';
            unset($_SESSION['stock_error']);
            ?>
            <?php if($product['stock'] > 0){ ?>
                <p class="product-status">Tình trạng: Còn <strong><?php echo (int)$product['stock']; ?></strong> sản phẩm</p>
            <?php } else { ?>
                <p class="product-status">Tình trạng: Hết hàng</p>
            <?php } ?>
            <?php if ($flash_error): ?>
                <div class="alert alert-warning py-2 small"><?php echo htmlspecialchars($flash_error); ?></div>
            <?php endif; ?>

            <p class="price-big">
            <?php if (!empty($product['sale_price'])): ?>
            <span style="color:#355638;"><?php echo number_format($product['sale_price']); ?>đ</span>
            <span style="font-size:16px;color:#aaa;font-weight:400;text-decoration:line-through;margin-left:8px;">
            <?php echo number_format($product['price']); ?>đ
            </span>
            <span style="font-size:13px;background:#355638;color:#fff;border-radius:4px;padding:2px 8px;margin-left:8px;">
            -<?php echo round((1 - $product['sale_price'] / $product['price']) * 100); ?>%
            </span>
        <?php else: ?>
            <?php echo number_format($product['price']); ?>đ
        <?php endif; ?>
        </p>

            <p><?php echo $product['description'] ?? 'Chưa có mô tả'; ?></p>

            <!-- BỘ TĂNG GIẢM SỐ LƯỢNG -->
<div class="d-flex align-items-center mb-4">
    <span class="me-3 text-secondary">Số Lượng</span>
    <div class="input-group" style="width: 120px;">
        <button class="btn btn-outline-secondary" type="button" onclick="changeQty(-1)" style="border-radius: 0;">&minus;</button>
        <input type="text" id="quantity" class="form-control text-center border-secondary fw-bold" value="1" inputmode="numeric" maxlength="4" autocomplete="off"
               oninput="this.value=this.value.replace(/[^0-9]/g,'')" onchange="normalizeQty()" onblur="normalizeQty()"
               onkeydown="if(event.key==='Enter'){event.preventDefault();normalizeQty();}" style="border-radius: 0;">
        <button class="btn btn-outline-secondary" type="button" onclick="changeQty(1)" style="border-radius: 0;">&plus;</button>
    </div>
</div>
<div id="qty-warning" class="text-danger small mb-3" style="display:none;margin-top:-16px;"></div>

<!-- 4. NHÓM NÚT MUA HÀNG (NẰM CẠNH NHAU) -->
<?php if ($product['stock'] > 0): ?>
<div class="d-flex gap-3 mt-4">
    <button onclick="addToCart(<?php echo $product['id']; ?>, 'add')" 
            class="btn product-add-cart px-4 py-2 d-flex align-items-center justify-content-center flex-grow-1">
        <i class="fa-solid fa-cart-plus me-2"></i> Thêm Vào Giỏ Hàng
    </button>
    <button onclick="addToCart(<?php echo $product['id']; ?>, 'buy')" 
            class="btn product-buy-now text-white px-4 py-2 flex-grow-1 fw-bold">
        Mua Ngay
    </button>
</div>
<?php else: ?>
<div class="mt-4">
    <button class="btn w-100 py-3 fw-bold" style="background:#f0f0f0;color:#aaa;border:none;height:50px;cursor:not-allowed;" disabled>
        Hết hàng
    </button>
</div>
<?php endif; ?>

<!-- 5. NÚT YÊU THÍCH -->
<div class="mt-4 text-center"> <button class="btn btn-outline-danger px-3 border-0 shadow-none btn-favorite <?php echo in_array($product['id'], $user_fav_ids) ? 'active' : ''; ?>" 
        data-id="<?php echo $product['id']; ?>">
    <?php echo in_array($product['id'], $user_fav_ids) ? '❤️ Đã yêu thích' : ' ❤️ Yêu thích'; ?>
    </button>
</div>
</div>
</div>   <!-- RELATED -->
<div class="related-section">

        <div class="d-flex justify-content-center align-items-center mb-3">
           <h4 class="fw-bold related-title">Sản phẩm liên quan</h4>
        </div>

        <div class="related-slider">

            <?php foreach($related as $r){ ?>
                <div class="item">
                    <div class="card">

                        <?php 
                    // Tách chuỗi ảnh và chỉ lấy cái đầu tiên để hiện ở trang ngoài
                    $images = explode(',', $r['image']);

                    // loại bỏ khoảng trắng + phần tử rỗng
                    $images = array_filter(array_map('trim', $images));
                  
                    $first_image = !empty($images) ? reset($images) : 'no-image.jpg';
                    ?>
                <a href="product.php?id=<?php echo $r['id']; ?>">
                    <img src="uploads/<?php echo $first_image; ?>" class="card-img-top" alt="...">
                </a>

                        <div class="card-body">
                            <h6><?php echo $r['name']; ?></h6>
                            <p class="price">
                                <?php echo number_format($r['price']); ?>đ
                            </p>
                        </div>
                        <div class="favorite-wrapper">
                        <button class="btn btn-favorite border-0 p-0 <?php echo in_array($r['id'], $user_fav_ids) ? 'active' : ''; ?>" 
                            data-id="<?php echo $r['id']; ?>">
                            <?php echo in_array($r['id'], $user_fav_ids) ? '❤️ Đã yêu thích' : '❤️ Yêu thích'; ?>
                        </button>
                    </div>

                    </div>
                </div>
            <?php } ?>

        </div>

    </div>
<!-- ===== KHU VỰC ĐÁNH GIÁ ===== -->
<div id="reviews" class="review-section mt-5">
    <h4 class="fw-bold mb-4">Đánh giá sản phẩm</h4>

    <!-- TỔNG QUAN SAO -->
    <div class="review-summary d-flex align-items-center gap-5 mb-4">
        <div class="text-center">
            <div class="review-avg-number"><?php echo $avg_rating ?: '—'; ?></div>
            <div class="review-stars-display">
                <?php for ($i = 1; $i <= 5; $i++): ?>
                    <i class="fa-star <?php echo $i <= round($avg_rating) ? 'fa-solid' : 'fa-regular'; ?>" style="color:#f5a623;"></i>
                <?php endfor; ?>
            </div>
            <div style="font-size:13px;color:#888;"><?php echo $total_reviews; ?> đánh giá</div>
        </div>

        <div class="review-bars flex-grow-1">
            <?php for ($s = 5; $s >= 1; $s--): 
                $pct = $total_reviews ? round($star_count[$s] / $total_reviews * 100) : 0;
            ?>
            <div class="d-flex align-items-center gap-2 mb-1">
                <span style="font-size:13px;width:14px;"><?php echo $s; ?></span>
                <i class="fa-solid fa-star" style="color:#f5a623;font-size:12px;"></i>
                <div class="review-bar-track flex-grow-1">
                    <div class="review-bar-fill" style="width:<?php echo $pct; ?>%"></div>
                </div>
                <span style="font-size:12px;color:#888;width:24px;"><?php echo $star_count[$s]; ?></span>
            </div>
            <?php endfor; ?>
        </div>
    </div>

    <!-- FORM ĐÁNH GIÁ -->
    <?php if ($can_review && !$has_reviewed): ?>
    <div class="review-form-box mb-4">
        <h6 class="fw-bold mb-3">Viết đánh giá của bạn</h6>
        <form action="review_submit.php" method="POST">
            <input type="hidden" name="product_id" value="<?php echo $id; ?>">
            <input type="hidden" name="order_id" value="<?php echo $review_order_id; ?>">

            <!-- CHỌN SAO -->
            <div class="star-picker mb-3">
                <?php for ($i = 5; $i >= 1; $i--): ?>
                <input type="radio" name="rating" id="star<?php echo $i; ?>" value="<?php echo $i; ?>" required>
                <label for="star<?php echo $i; ?>"><i class="fa-solid fa-star"></i></label>
                <?php endfor; ?>
            </div>

            <textarea name="comment" class="form-control mb-3" rows="3" placeholder="Chia sẻ cảm nhận của bạn về sản phẩm..."></textarea>
            <button type="submit" class="btn-admin-primary">Gửi đánh giá</button>
        </form>
    </div>
    <?php elseif ($has_reviewed): ?>
        <div class="admin-alert success mb-4">Bạn đã đánh giá sản phẩm này rồi.</div>
    <?php elseif (!$user_id): ?>
        <div class="review-form-box mb-4" style="text-align:center;color:#888;">
            <a href="account.php?type=login" class="fw-bold" style="color:var(--rose);">Đăng nhập</a> để đánh giá sản phẩm.
        </div>
    <?php else: ?>
        <div class="review-form-box mb-4" style="text-align:center;color:#888;font-size:14px;">
            Chỉ khách hàng đã mua và nhận hàng mới có thể đánh giá.
        </div>
    <?php endif; ?>

    <!-- DANH SÁCH ĐÁNH GIÁ -->
    <?php if (empty($reviews)): ?>
        <p style="color:#aaa;font-size:14px;">Chưa có đánh giá nào.</p>
    <?php else: ?>
        <?php foreach ($reviews as $rv): ?>
        <div class="review-item">
            <div class="d-flex justify-content-between align-items-start mb-1">
                <div>
                    <span class="fw-bold" style="font-size:14px;"><?php echo htmlspecialchars($rv['user_name']); ?></span>
                    <span class="ms-2">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <i class="fa-star <?php echo $i <= $rv['rating'] ? 'fa-solid' : 'fa-regular'; ?>" style="color:#f5a623;font-size:13px;"></i>
                        <?php endfor; ?>
                    </span>
                </div>
                <span style="font-size:12px;color:#aaa;"><?php echo date('d/m/Y', strtotime($rv['created_at'])); ?></span>
            </div>
            <p style="font-size:14px;color:#444;margin-bottom:10px;"><?php echo htmlspecialchars($rv['comment']); ?></p>
            <?php if (!empty($rv['image'])): ?>
            <div class="rv-img-wrap">
                <img src="uploads/<?php echo htmlspecialchars($rv['image']); ?>"
                     class="rv-feedback-img"
                     onclick="this.classList.toggle('expanded')"
                     title="Nhấn để phóng to">
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
</div> <!-- 🔥 container đóng đúng chỗ -->

<script>
function scrollLeft(){
    document.querySelector('.related-slider')
        .scrollBy({ left: -300, behavior: 'smooth' });
}

function scrollRight(){
    document.querySelector('.related-slider')
        .scrollBy({ left: 300, behavior: 'smooth' });
}
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
function addToCart(productId, action = 'add') {
    normalizeQty();
    let qty   = document.getElementById('quantity')?.value       ?? 1;

    if (action === 'buy') {
        window.location.href = `cart.php?id=${productId}&qty=${qty}&action=add&redirect=checkout`;
    } else {
        window.location.href = `cart.php?id=${productId}&qty=${qty}&action=add`;
    }
}

// Khách tự nhập số lượng: ép về khoảng 1..tồn kho và báo nếu vượt quá
function normalizeQty() {
    let qtyInput = document.getElementById('quantity');
    let maxStock = <?php echo (int)$product['stock']; ?>;
    let warn = document.getElementById('qty-warning');
    let v = parseInt(qtyInput.value, 10);
    if (isNaN(v) || v < 1) v = 1;
    if (maxStock > 0 && v > maxStock) {
        v = maxStock;
        warn.textContent = 'Vượt quá số lượng trong kho! Sản phẩm này chỉ còn ' + maxStock + ' sản phẩm, đã chỉnh về ' + maxStock + '.';
        warn.style.display = 'block';
    } else {
        warn.style.display = 'none';
    }
    qtyInput.value = v;
}

function changeQty(amt) {
    let qtyInput = document.getElementById('quantity');
    let maxStock = <?php echo (int)$product['stock']; ?>;
    let warn = document.getElementById('qty-warning');
    let newVal = (parseInt(qtyInput.value, 10) || 1) + amt;
    if (newVal > maxStock) {
        warn.textContent = 'Vượt quá số lượng trong kho! Sản phẩm này chỉ còn ' + maxStock + ' sản phẩm.';
        warn.style.display = 'block';
        return;
    }
    warn.style.display = 'none';
    if (newVal >= 1) {
        qtyInput.value = newVal;
    }
}

function changeImage(src, element) {
    document.getElementById('main-product-img').src = src;
    document.querySelectorAll('.thumb-item').forEach(i => i.classList.remove('active'));
    element.classList.add('active');
}

function scrollLeft() {
    document.querySelector('.related-slider').scrollBy({ left: -300, behavior: 'smooth' });
}
function scrollRight() {
    document.querySelector('.related-slider').scrollBy({ left: 300, behavior: 'smooth' });
}
</script>


<?php include 'footer.php'; ?>

</body>
</html>