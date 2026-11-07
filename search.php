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

$q = trim($_GET['q'] ?? '');
$products = [];

if ($q !== '') {
    $stmt = $pdo->prepare("
        SELECT * FROM products
        WHERE name LIKE ? OR description LIKE ?
        ORDER BY
            CASE WHEN name LIKE ? THEN 0 ELSE 1 END,
            id DESC
    ");
    $like = "%$q%";
    $stmt->execute([$like, $like, $like]);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Tìm kiếm: <?php echo htmlspecialchars($q); ?> - December</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
</head>
<body>
<div class="container mt-4 product-list">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">
            Kết quả tìm kiếm: <span style="color:var(--rose);">"<?php echo htmlspecialchars($q); ?>"</span>
            <span style="font-size:14px;font-weight:400;color:#aaa;margin-left:8px;">
                (<?php echo count($products); ?> sản phẩm)
            </span>
        </h2>
    </div>

    <?php if ($q === ''): ?>
        <div style="text-align:center;padding:60px 0;color:#aaa;">
            Nhập từ khóa để tìm kiếm sản phẩm.
        </div>
    <?php elseif (empty($products)): ?>
        <div style="text-align:center;padding:60px 0;color:#aaa;">
            <i class="fa-regular fa-face-frown" style="font-size:40px;margin-bottom:12px;display:block;"></i>
            Không tìm thấy sản phẩm nào với từ khóa <strong>"<?php echo htmlspecialchars($q); ?>"</strong>.
            <div class="mt-3"><a href="index.php" style="color:var(--rose);">Về trang chủ</a></div>
        </div>
    <?php else: ?>
    <div class="row">
        <?php foreach ($products as $p):
            $images = array_filter(array_map('trim', explode(',', $p['image'])));
            $first_image = !empty($images) ? reset($images) : 'no-image.jpg';
            $discount = !empty($p['sale_price']) ? round((1 - $p['sale_price'] / $p['price']) * 100) : 0;
        ?>
        <div class="col-md-3 col-6 mb-5">
            <div class="card h-100 border-0 bg-transparent">
                <div style="position:relative;">
                    <a href="product.php?id=<?php echo $p['id']; ?>">
                        <img src="uploads/<?php echo $first_image; ?>" class="card-img-top">
                    </a>
                    <?php if (!empty($p['sale_price'])): ?>
                    <span style="position:absolute;top:8px;left:8px;background:#355638;color:#fff;font-size:11px;font-weight:700;padding:3px 8px;border-radius:4px;">
                        -<?php echo $discount; ?>%
                    </span>
                    <?php endif; ?>
                    <?php if ($p['stock'] <= 0): ?>
                    <span style="position:absolute;top:8px;right:8px;background:rgba(0,0,0,0.5);color:#fff;font-size:11px;font-weight:600;padding:3px 8px;border-radius:4px;">
                        Hết hàng
                    </span>
                    <?php endif; ?>
                </div>
                <div class="card-body text-center p-2">
                    <h6 class="fw-normal small text-uppercase mb-2" style="font-size:13px;">
                        <?php echo htmlspecialchars($p['name']); ?>
                    </h6>
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
                    <?php $isFav = in_array($p['id'], $user_fav_ids); ?>
                    <button class="btn btn-favorite border-0 p-0 <?php echo $isFav ? 'active' : ''; ?>"
                        data-id="<?php echo $p['id']; ?>"
                        style="background:none;color:#355638;font-size:14px;">
                        <?php echo $isFav ? 'Đã yêu thích ❤️' : 'Yêu thích ❤️'; ?>
                    </button>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

</div>
<?php include 'footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>