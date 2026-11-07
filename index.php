<?php 
include 'db.php'; 
session_start(); // Đảm bảo đã có session_start để lấy user_id

$user_id = $_SESSION['user']['id'] ?? null;
$user_fav_ids = [];

// Lấy danh sách ID các sản phẩm đã yêu thích để kiểm tra nút trái tim
if ($user_id) {
    $stmtFav = $pdo->prepare("SELECT product_id FROM favorites WHERE user_id = ?");
    $stmtFav->execute([$user_id]);
    $user_fav_ids = $stmtFav->fetchAll(PDO::FETCH_COLUMN);
}
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<!-- Nếu file style.css nằm cùng cấp với index.php -->
<link rel="stylesheet" href="./style.css?time=<?php echo time(); ?>">
</head>

<body>

<?php include 'header.php'; ?>




<div class="container mt-5">

    <!-- PHẦN 1: SẢN PHẨM BÁN CHẠY (Dạng Slider) -->
    <div class="product-section mb-5">
        <div class="text-center mb-4">
            <h3 class="text-uppercase fw-light" style="letter-spacing: 3px;">Sản phẩm Bán Chạy</h3>
            <div style="width: 50px; height: 2px; background: #355638; margin: 10px auto;"></div>
        </div>

        <!-- Khung chứa Slider -->
        <div class="product-slider">
            <?php
            $stmtBest = $pdo->query("SELECT * FROM products ORDER BY id ASC LIMIT 10");
            $best_seller_ids = []; 
            
            while($row = $stmtBest->fetch(PDO::FETCH_ASSOC)){
                $best_seller_ids[] = $row['id'];
                $images = explode(',', $row['image']); 
                $first_image = trim($images[0]); 
                $discount = !empty($row['sale_price']) ? round((1 - $row['sale_price'] / $row['price']) * 100) : 0;
            ?>
            <div class="product-item">
                <div class="card h-100 border-0">
                    <div style="position:relative;">
                <a href="product.php?id=<?php echo $row['id']; ?>">
                    <img src="uploads/<?php echo $first_image; ?>" class="card-img-top">
                </a>
                <?php if (!empty($row['sale_price'])): ?>
                <span style="position:absolute;top:8px;left:8px;background:#355638;color:#fff;font-size:11px;font-weight:700;padding:3px 8px;border-radius:4px;">
                        -<?php echo $discount; ?>%
                </span>
                <?php endif; ?>
            </div>
                    <div class="card-body text-center p-2">
                        <h6 class="fw-normal small"><?php echo $row['name']; ?></h6>
                    <?php if (!empty($row['sale_price'])): ?>
                        <p class="price small fw-bold">
                            <?php echo number_format($row['sale_price']); ?>đ
                            <span style="font-size:11px;color:#aaa;font-weight:400;text-decoration:line-through;margin-left:4px;">
                            <?php echo number_format($row['price']); ?>đ
                            </span>
                         </p>
                    <?php else: ?>
                        <p class="price small fw-bold"><?php echo number_format($row['price']); ?>đ</p>
                    <?php endif; ?>
                    <p class="small mb-2" style="font-size:12px;color:#687363;">
                        <?php echo ((int)$row['stock'] > 0) ? 'Còn ' . (int)$row['stock'] . ' sản phẩm' : 'Hết hàng'; ?>
                    </p>
                        <button class="btn btn-outline-danger btn-sm btn-favorite border-0 <?php echo in_array($row['id'], $user_fav_ids) ? 'active' : ''; ?>" 
                        data-id="<?php echo $row['id']; ?>">
                        <?php echo in_array($row['id'], $user_fav_ids) ? '❤️ Đã yêu thích' : 'Yêu thích❤️'; ?>
                        </button>
                            
                    </div>
                </div>
            </div>
            <?php } ?>
        </div>
    </div>

    <!-- PHẦN 2: SẢN PHẨM MỚI (Dạng Slider) -->
    <div class="product-section mb-5">
        <div class="text-center mb-4">
            <h3 class="text-uppercase fw-light" style="letter-spacing: 3px;">Sản phẩm Mới</h3>
            <div style="width: 50px; height: 2px; background: #000; margin: 10px auto;"></div>
        </div>

        <div class="product-slider">
            <?php
            $exclude_ids = !empty($best_seller_ids) ? implode(',', $best_seller_ids) : '0';
            $stmtNew = $pdo->query("SELECT * FROM products WHERE id NOT IN ($exclude_ids) ORDER BY id DESC LIMIT 10");
            
            while($row = $stmtNew->fetch(PDO::FETCH_ASSOC)){
            $images = explode(',', $row['image']); 
            $first_image = trim($images[0]);
            $discount = !empty($row['sale_price']) ? round((1 - $row['sale_price'] / $row['price']) * 100) : 0;
            ?>
            <div class="product-item">
                <div class="card h-100 border-0">
                    <div style="position:relative;">
                <a href="product.php?id=<?php echo $row['id']; ?>">
                    <img src="uploads/<?php echo $first_image; ?>" class="card-img-top">
                </a>
                <?php if (!empty($row['sale_price'])): ?>
                    <span style="position:absolute;top:8px;left:8px;background:#355638;color:#fff;font-size:11px;font-weight:700;padding:3px 8px;border-radius:4px;">
                        -<?php echo $discount; ?>%
                    </span>
                <?php endif; ?>
            </div>
                    <div class="card-body text-center p-2">
                        <h6 class="fw-normal small"><?php echo $row['name']; ?></h6>
                        <p class="price small fw-bold"><?php echo number_format($row['price']); ?>đ</p>
                    <p class="small mb-2" style="font-size:12px;color:#687363;">
                        <?php echo ((int)$row['stock'] > 0) ? 'Còn ' . (int)$row['stock'] . ' sản phẩm' : 'Hết hàng'; ?>
                    </p>
                        <button class="btn btn-outline-danger btn-sm btn-favorite border-0" data-id="<?php echo $row['id']; ?>">
                            Yêu thích❤️
                        </button>
                    </div>
                </div>
            </div>
            <?php } ?>
        </div>
    </div>

</div>
<?php include 'footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>