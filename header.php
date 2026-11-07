<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

<link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
<?php include 'db.php'; ?>

<nav class="navbar navbar-expand-lg bg-ivory">
<div class="container">

<a class="navbar-brand logo" href="index.php">December</a>

<button class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#menu">
☰
</button>

<div class="collapse navbar-collapse" id="menu">

<ul class="navbar-nav mx-auto">

<!-- TRANG CHỦ -->
<li class="nav-item">
<a class="nav-link" href="index.php">Trang chủ</a>
</li>

<li class="nav-item dropdown position-static">
    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
        Menu
    </a>

   <div class="dropdown-menu mega-menu mt-0 p-4">
        <div class="container">
            <div class="row">

                <?php
                $parents = $pdo->query("SELECT * FROM categories WHERE parent_id IS NULL");

                foreach($parents as $p){

                    $stmtChild = $pdo->prepare("SELECT * FROM categories WHERE parent_id = ?");
                    $stmtChild->execute([$p['id']]);
                    $children = $stmtChild->fetchAll(PDO::FETCH_ASSOC);
                ?>

                <div class="col-md-4">
                    <h6 class="fw-bold">
                <a href="category.php?id=<?php echo $p['id']; ?>" 
                    style="color:inherit;text-decoration:none;">
                    <?php echo $p['name']; ?>
                </a>
                </h6>

                    <?php foreach($children as $c){ ?>
                      <a class="dropdown-item text-start px-0" href="category.php?id=<?php echo $c['id']; ?>">
                        <?php echo $c['name']; ?>
                    </a>
                    <?php } ?>
                </div>

                <?php } ?>

            </div>
        </div>
    </div>
</li>
<!-- KHUYẾN MẠI -->
<li class="nav-item">
<a class="nav-link" href="sale.php">Khuyến mại</a>
</li>

<!-- TRỢ GIÚP -->
<li class="nav-item dropdown">
    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
        TRỢ GIÚP
    </a>
    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
        <li><a class="dropdown-item text-end" href="policy.php?type=about">Về Chúng Tôi</a></li>
        <li><a class="dropdown-item text-end" href="policy.php?type=care">Chăm Sóc Khách Hàng</a></li>
        
        <!-- Menu Chính Sách có Sub-menu sang trái -->
        <li class="dropdown-submenu">
            <a class="dropdown-item dropdown-toggle text-end" href="#">Chính Sách</a>
            <ul class="dropdown-menu sub-menu-left">
                <li><a class="dropdown-item text-end" href="policy.php?type=loyalty">Chính Sách KH Thân Thiết</a></li>
                <li><a class="dropdown-item text-end" href="policy.php?type=return">Chính Sách Đổi và Trả Hàng</a></li>
                <li><a class="dropdown-item text-end" href="policy.php?type=usage">Hướng Dẫn Sử Dụng</a></li>
            </ul>
        </li>
    </ul>
</li>

<!-- ICON RIGHT -->
<div class="d-flex">

<a href="favorite.php" class="icon">❤️</a>

<!-- SEARCH ICON -->
<a class="icon" href="#" onclick="toggleSearch(event)">🔍</a>

<div class="dropdown">
  <a class="icon dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
    👤
  </a>

  <ul class="dropdown-menu dropdown-menu-end">

    <?php if(!isset($_SESSION['user'])): ?>

      <li><a class="dropdown-item" href="account.php?type=login">Đăng nhập</a></li>
      <li><a class="dropdown-item" href="account.php?type=register">Đăng ký</a></li>

    <?php else: ?>

     <li>
    <a class="dropdown-item" href="profile.php">
        Xin chào, <?php echo htmlspecialchars($_SESSION['user']['name']); ?>
    </a>
    </li>
      <li><a class="dropdown-item" href="logout.php">Đăng xuất</a></li>

      <?php if($_SESSION['user']['role'] === 'admin'): ?>
        <li><a class="dropdown-item" href="admin.php">Trang quản trị</a></li>
      <?php endif; ?>

    <?php endif; ?>

  </ul>
</div>

<a href="cart.php" class="icon">🛒</a>
<?php if(isset($_SESSION['name'])): ?>
    <span class="ms-3">Xin chào, <?php echo htmlspecialchars($_SESSION['name']); ?></span>
  <?php endif; ?>
</div>

</div>
</div>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</nav>

<!-- SEARCH BAR (ẩn mặc định) -->
<div id="search-bar-wrap" style="display:none;background:#fff;border-bottom:1px solid #eee;padding:12px 0;">
    <div class="container">
        <form action="search.php" method="GET" class="d-flex gap-2">
            <input type="text" name="q" id="search-input" class="form-control"
                placeholder="Tìm kiếm sản phẩm..." autocomplete="off"
                value="<?php echo htmlspecialchars($_GET['q'] ?? ''); ?>">
            <button type="submit" class="btn btn-dark px-4">Tìm</button>
            <button type="button" class="btn btn-outline-secondary" onclick="toggleSearch(event)">✕</button>
        </form>
    </div>
</div>

<script>
function toggleSearch(e) {
    e.preventDefault();
    const bar = document.getElementById('search-bar-wrap');
    const isHidden = bar.style.display === 'none';
    bar.style.display = isHidden ? 'block' : 'none';
    if (isHidden) document.getElementById('search-input').focus();
}
// Tự mở search bar nếu đang ở trang search.php
<?php if (basename($_SERVER['PHP_SELF']) === 'search.php'): ?>
document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('search-bar-wrap').style.display = 'block';
});
<?php endif; ?>
</script>

<nav class="py-2" style="background-color: #f8f9f9; border-bottom: 1px solid #eee;">
    <div class="container">
        <ol class="breadcrumb mb-0" style="font-size: 13px; list-style: none; display: flex; padding: 0; align-items: center;">
            <!-- Luôn có Trang chủ -->
            <li class="breadcrumb-item">
                <a href="index.php" class="text-dark text-decoration-none">Trang chủ</a>
            </li>

            <?php
            $current_page = basename($_SERVER['PHP_SELF']);

            // XỬ LÝ CHO TRANG DANH MỤC & SẢN PHẨM (Dựa trên Database)
            if (($current_page == 'category.php' || $current_page == 'product.php') && isset($_GET['id'])) {
                $id = $_GET['id'];
                
                // Nếu là trang sản phẩm, lấy category_id của sản phẩm đó trước
                if ($current_page == 'product.php') {
                    $stmtProd = $pdo->prepare("SELECT category_id, name FROM products WHERE id = ?");
                    $stmtProd->execute([$id]);
                    $product = $stmtProd->fetch();
                    $cat_id = $product['category_id'];
                } else {
                    $cat_id = $id;
                }

                // Truy vấn ngược lên các cấp danh mục cha
                $breadcrumbs = [];
                while ($cat_id) {
                    $stmtCat = $pdo->prepare("SELECT id, name, parent_id FROM categories WHERE id = ?");
                    $stmtCat->execute([$cat_id]);
                    $cat = $stmtCat->fetch();
                    if ($cat) {
                        array_unshift($breadcrumbs, $cat); // Thêm vào đầu mảng
                        $cat_id = $cat['parent_id'];
                    } else {
                        break;
                    }
                }

                // Hiển thị danh mục phân cấp
                foreach ($breadcrumbs as $b) {
                    echo '<li class="px-2 text-muted" style="font-size: 10px;">></li>';
                    echo '<li class="breadcrumb-item"><a href="category.php?id='.$b['id'].'" class="text-dark text-decoration-none">'.$b['name'].'</a></li>';
                }

                // Nếu là trang sản phẩm thì hiện thêm tên sản phẩm cuối cùng
                if ($current_page == 'product.php') {
                    echo '<li class="px-2 text-muted" style="font-size: 10px;">></li>';
                    echo '<li class="breadcrumb-item active" style="color: #a31d1d; font-weight: 500;">'.$product['name'].'</li>';
                }
            } 
            
            // XỬ LÝ CHO CÁC TRANG TĨNH KHÁC
           else {
    $breadcrumb_name = "";
    
    // Xử lý riêng cho trang policy.php để hiện tên chi tiết
    if ($current_page == 'policy.php' && isset($_GET['type'])) {
        $policy_types = [
            'about'    => 'Về Chúng Tôi',
            'care'     => 'Chăm Sóc Khách Hàng',
            'loyalty'  => 'Chính Sách KH Thân Thiết',
            'return'   => 'Chính Sách Đổi và Trả Hàng',
            'usage'    => 'Hướng Dẫn Sử Dụng'
        ];
        
        // Hiện "Chính sách" là cấp cha, sau đó đến tên chính sách cụ thể
        echo '<li class="px-2 text-muted" style="font-size: 10px;"> > </li>';
        echo '<li class="breadcrumb-item"><a href="policy.php" class="text-dark text-decoration-none">Chính sách</a></li>';
        
        $breadcrumb_name = $policy_types[$_GET['type']] ?? "Chi tiết";
    } 
    else {
        // Danh sách tên cho các trang tĩnh khác
        $titles = [
            'account.php' => (isset($_GET['type']) && $_GET['type'] == 'register') ? "Đăng ký" : "Đăng nhập",
            'cart.php'    => "Giỏ hàng",
            'profile.php' => "Trang cá nhân",
            'favorite.php'=> "Yêu thích",
            'sale.php'    => "Khuyến mại",
            'search.php'  => "Tìm kiếm: " . htmlspecialchars($_GET['q'] ?? '')
        ];
        if (isset($titles[$current_page])) {
            $breadcrumb_name = $titles[$current_page];
        }
    }

    // Hiển thị phần tên trang cuối cùng (màu đỏ)[cite: 1, 2]
    if ($breadcrumb_name != "") {
        echo '<li class="px-2 text-muted" style="font-size: 10px;"> > </li>';
        echo '<li class="breadcrumb-item active" style="color: #a31d1d; font-weight: 500;">' . $breadcrumb_name . '</li>';
    }
}
            ?>
        </ol>
    </div>
</nav>