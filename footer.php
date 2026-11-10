<footer class="bg-forest text-white pt-5 pb-4 mt-5">
    <div class="container text-md-left">
        <div class="row text-md-left">
            
            <!-- Cột 1: Thông tin Studio -->
            <div class="col-md-3 col-lg-3 col-xl-3 mx-auto mt-3">
                <h6 class="text-uppercase mb-4 font-weight-bold">DECEMBER STUDIO</h6>
                <p class="small">79 Hồ Tùng Mậu, Từ Liêm, Hà Nội</p>
                <p class="small">Điện thoại: 0103456789</p>
                <p class="small">Email: decembervn@gmail.com</p>
                <div class="mt-3">
                    <a href="https://www.facebook.com/decemberin2015" class="text-white me-3 text-decoration-none" target="_blank">
                    <i class="fab fa-facebook"></i>
                    </a>
                    <a href="https://www.instagram.com/december.sg?igsh=ZmphMjBrM3J0Mm1t" class="text-white me-3 text-decoration-none" target="_blank">
                    <i class="fab fa-instagram"></i>
                    </a>
                    <a href="https://www.youtube.com/@decembershop" class="text-white me-3 text-decoration-none" target="_blank">
                    <i class="fab fa-youtube"></i>
                    </a>
                   <a href="https://www.tiktok.com/@decemberin2015?_r=1&_t=ZS-95xmykhnpWT" class="text-white me-3 text-decoration-none" target="_blank">
                    <i class="fab fa-tiktok"></i>
                    </a>
                </div>
            </div>

            <!-- Cột 2: Công ty -->
            <div class="col-md-3 col-lg-3 col-xl-3 mx-auto mt-3">
                <h6 class="text-uppercase mb-4 font-weight-bold">CÔNG TY</h6>
                <p class="small"><a href="#" class="text-white text-decoration-none">• DECEMBER</a></p>
                <p class="small">• Tuyển Dụng & Việc Làm</a></p>
                <p class="small">• Tin Tức</a></p>
                <p class="small">• Chăm Sóc Khách Hàng</a></p>
            </div>

            <!-- Cột 3: Chính sách -->
            <div class="col-md-3 col-lg-3 col-xl-3 mx-auto mt-3">
                <h6 class="text-uppercase mb-4 font-weight-bold">CHÍNH SÁCH KHÁCH HÀNG</h6>
            <p class="mb-2">
                <a href="policy.php?type=loyalty" class="text-white text-decoration-none small">• Chính Sách KH Thân Thiết</a>
            </p>
            <p class="mb-2">
                <a href="policy.php?type=return" class="text-white text-decoration-none small">• Chính Sách Đổi và Trả Hàng</a>
            </p>
            <p class="mb-2">
                <a href="policy.php?type=usage" class="text-white text-decoration-none small">• Hướng Dẫn Sử Dụng</a>
            </p>
            <p class="mb-2">
                <a href="policy.php?type=care" class="text-white text-decoration-none small">• Chăm Sóc Khách Hàng</a>
            </p>
            </div>

            <!-- Cột 4: Thông tin cửa hàng -->
            <div class="col-md-3 col-lg-3 col-xl-3 mx-auto mt-3">
                <h6 class="text-uppercase mb-4 font-weight-bold">THÔNG TIN CỬA HÀNG</h6>
                <p class="small fw-bold">CỬA HÀNG SỐ 1</p>
                <p class="small">79 Hồ Tùng Mậu, Từ Liêm, Hà Nội</p>
            </div>

        </div>

        <hr class="mb-4 mt-5">

        <div class="row align-items-center">
            <div class="col-md-12 text-center">
                <p class="small mb-0">© Bản quyền thuộc về December | Cung cấp bởi Nhom 5 thuc hanh PHP </p>
            </div>
        </div>
    </div>
</footer>

<!-- Link thư viện Icon (FontAwesome) để hiện icon FB, IG -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">


<?php
// Kiểm tra Session từ PHP để tạo biến JS
$is_logged_in = isset($_SESSION['user']) ? 'true' : 'false';
?>

<script>
// Lấy trạng thái đăng nhập từ PHP (true nếu đã đăng nhập, false nếu chưa)
isLoggedIn = <?php echo isset($_SESSION['user']) ? 'true' : 'false'; ?>;

document.querySelectorAll('.btn-favorite').forEach(btn => {
    btn.addEventListener('click', function(e){
        e.preventDefault();

        // 1. NẾU CHƯA ĐĂNG NHẬP -> HIỂN THỊ THÔNG BÁO 
        if (!isLoggedIn) {
            // Dùng hàm confirm để hiện hộp thoại có nút OK/Cancel
            if(confirm("Bạn cần đăng nhập để thêm sản phẩm vào danh sách yêu thích. Chuyển đến trang đăng nhập ngay?")) {
                window.location.href = 'account.php?type=login';
            }
            return; // Dừng lại, không gửi request xuống server nữa
        }

        // 2. NẾU ĐÃ ĐĂNG NHẬP -> XỬ LÝ AJAX BÌNH THƯỜNG
        let button = this;
        let productId = button.dataset.id;

        fetch('favorite.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: 'product_id=' + productId
        })
        .then(res => res.text())
        .then(data => {
            if(data.trim() === 'added'){
                button.classList.add('active');
                button.innerHTML = "❤️ Đã yêu thích";
            }
            else if(data.trim() === 'removed'){
                button.classList.remove('active');
                button.innerHTML = "❤️ Yêu thích";
            }
        });
    });
});
</script>

<?php include 'chat.php'; ?>

<script>
document.addEventListener('click', function(e) {
    // Nếu bấm vào nút "Sản phẩm này có sẵn không?"
    if (e.target.innerText && e.target.innerText.includes('có sẵn không')) {
        
        // Tự động lấy tên từ thẻ H1 của trang web
        let productName = document.querySelector('h1')?.innerText || "sản phẩm";
        productName = productName.trim();

        // Ép Bot gửi lệnh Check kho kèm tên thật của sản phẩm
        const smartMsg = `Check kho mẫu ${productName}`;
        
        e.preventDefault();
        e.stopPropagation();

        if (typeof window.sendDirect === 'function') {
            window.sendDirect(smartMsg);
        }
    }
});
</script>