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

// Lấy tất cả sản phẩm có sale_price
$products = $pdo->query("SELECT * FROM products WHERE sale_price IS NOT NULL AND sale_price > 0 ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Khuyến mại - December</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
</head>
<body>

<div class="container mt-4 product-list">

    <div class="text-center mb-5">
        <h3 class="text-uppercase fw-light" style="letter-spacing: 3px;">Khuyến mại</h3>
        <div style="width: 50px; height: 2px; background: #355638; margin: 10px auto;"></div>
        <p class="text-muted" style="font-size:14px;"><?php echo count($products); ?> sản phẩm đang được giảm giá</p>
    </div>

    <?php if (empty($products)): ?>
        <div class="text-center py-5">
            <p class="text-muted">Hiện chưa có sản phẩm khuyến mại.</p>
            <a href="index.php" class="btn btn-outline-dark mt-2">Tiếp tục mua sắm</a>
        </div>
    <?php else: ?>
    <div class="row">
        <?php foreach ($products as $p):
            $images = array_filter(array_map('trim', explode(',', $p['image'])));
            $first_image = !empty($images) ? reset($images) : 'no-image.jpg';
            $discount = round((1 - $p['sale_price'] / $p['price']) * 100);
        ?>
        <div class="col-md-3 col-6 mb-5">
            <div class="card h-100 border-0 bg-transparent">
                <div style="position:relative;">
                    <a href="product.php?id=<?php echo $p['id']; ?>">
                        <img src="uploads/<?php echo $first_image; ?>" class="card-img-top">
                    </a>
                    <span style="position:absolute;top:8px;left:8px;background:#355638;color:#fff;font-size:11px;font-weight:700;padding:3px 8px;border-radius:4px;">
                        -<?php echo $discount; ?>%
                    </span>
                </div>
                <div class="card-body text-center p-2">
                    <h6 class="fw-normal small text-uppercase mb-2" style="font-size:13px;">
                        <?php echo htmlspecialchars($p['name']); ?>
                    </h6>
                    <p class="small fw-bold mb-2">
                        <span style="color:#355638;"><?php echo number_format($p['sale_price']); ?>đ</span>
                        <span style="color:#aaa;font-weight:400;text-decoration:line-through;margin-left:6px;font-size:12px;">
                            <?php echo number_format($p['price']); ?>đ
                        </span>
                    </p>
                    <p class="small mb-2" style="font-size:12px;color:#687363;">
                        <?php echo ((int)$p['stock'] > 0) ? 'Còn ' . (int)$p['stock'] . ' sản phẩm' : 'Hết hàng'; ?>
                    </p>
                    <button class="btn btn-favorite border-0 p-0 <?php echo in_array($p['id'], $user_fav_ids) ? 'active' : ''; ?>"
                        data-id="<?php echo $p['id']; ?>"
                        style="background:none;color:#355638;font-size:14px;">
                        <?php echo in_array($p['id'], $user_fav_ids) ? 'Đã yêu thích ❤️' : 'Yêu thích ❤️'; ?>
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