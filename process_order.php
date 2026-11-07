<?php
session_start();
include 'db.php';

function normalize_vi_text($value) {
    $value = trim($value);
    $value = mb_strtolower($value, 'UTF-8');
    $value = str_replace(
        ['à','á','ạ','ả','ã','â','ầ','ấ','ậ','ẩ','ẫ','ă','ằ','ắ','ặ','ẳ','ẵ',
         'è','é','ẹ','ẻ','ẽ','ê','ề','ế','ệ','ể','ễ',
         'ì','í','ị','ỉ','ĩ',
         'ò','ó','ọ','ỏ','õ','ô','ồ','ố','ộ','ổ','ỗ','ơ','ờ','ớ','ợ','ở','ỡ',
         'ù','ú','ụ','ủ','ũ','ư','ừ','ứ','ự','ử','ữ',
         'ỳ','ý','ỵ','ỷ','ỹ',
         'đ',
         'À','Á','Ạ','Ả','Ã','Â','Ầ','Ấ','Ậ','Ẩ','Ẫ','Ă','Ằ','Ắ','Ặ','Ẳ','Ẵ',
         'È','É','Ẹ','Ẻ','Ẽ','Ê','Ề','Ế','Ệ','Ể','Ễ',
         'Ì','Í','Ị','Ỉ','Ĩ',
         'Ò','Ó','Ọ','Ỏ','Õ','Ô','Ồ','Ố','Ộ','Ổ','Ỗ','Ơ','Ờ','Ớ','Ợ','Ở','Ỡ',
         'Ù','Ú','Ụ','Ủ','Ũ','Ư','Ừ','Ứ','Ự','Ử','Ữ',
         'Ỳ','Ý','Ỵ','Ỷ','Ỹ',
         'Đ'],
        ['a','a','a','a','a','a','a','a','a','a','a','a','a','a','a','a','a',
         'e','e','e','e','e','e','e','e','e','e','e',
         'i','i','i','i','i',
         'o','o','o','o','o','o','o','o','o','o','o','o','o','o','o','o','o',
         'u','u','u','u','u','u','u','u','u','u','u',
         'y','y','y','y','y',
         'd',
         'A','A','A','A','A','A','A','A','A','A','A','A','A','A','A','A','A',
         'E','E','E','E','E','E','E','E','E','E','E',
         'I','I','I','I','I',
         'O','O','O','O','O','O','O','O','O','O','O','O','O','O','O','O','O',
         'U','U','U','U','U','U','U','U','U','U','U',
         'Y','Y','Y','Y','Y',
         'D'],
        $value
    );
    $value = preg_replace('/[^a-z0-9\s]/i', '', $value);
    $value = preg_replace('/\s+/', ' ', $value);
    return trim($value);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $user_id = $_SESSION['user']['id'];

    $fullname = trim($_POST['fullname']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $city = trim($_POST['city']);
    $district = trim($_POST['district']);
    $ward = trim($_POST['ward']);
    $note = trim($_POST['note']);
    $payment_method = $_POST['payment_method'];

    if (normalize_vi_text($city) !== 'ha noi' && normalize_vi_text($city) !== 'hanoi') {
        $_SESSION['checkout_error'] = 'Hiện tại cửa hàng chỉ hỗ trợ giao hàng trong nội thành Hà Nội.';
        header('Location: checkout.php');
        exit;
    }

    $full_address = $address . ", " . $ward . ", " . $district . ", " . $city;

    // sản phẩm checkout
    $checkout_items = [];

if (
    isset($_SESSION['checkout_mode']) &&
    $_SESSION['checkout_mode'] === 'buy_now'
) {

    $checkout_items = [$_SESSION['buy_now_item']];

} elseif (isset($_SESSION['checkout_selected'])) {

    $checkout_items = $_SESSION['checkout_selected'];

} else {

    $checkout_items = $_SESSION['cart'] ?? [];
}

    if (empty($checkout_items)) {
        header("Location: index.php");
        exit;
    }

    // tính tổng
// Kiểm tra tồn kho trước khi đặt hàng
foreach ($checkout_items as $item) {
    $stmtCheck = $pdo->prepare("SELECT stock, name FROM products WHERE id = ?");
    $stmtCheck->execute([$item['id']]);
    $productCheck = $stmtCheck->fetch();
    if (!$productCheck || $productCheck['stock'] < $item['qty']) {
        echo "<script>alert('Sản phẩm \"" . addslashes($productCheck['name']) . "\" không đủ hàng! Vui lòng kiểm tra lại giỏ hàng.'); window.location.href='cart.php';</script>";
        exit;
    }
}

// tính tổng
$total = 0;
foreach ($checkout_items as $item) {
    $total += $item['price'] * $item['qty'];
}

    // cộng phí ship (giống checkout.php)
    $ship_fee = ($total > 500000) ? 0 : 30000;
    $total += $ship_fee;

    // lưu orders
    $stmt = $pdo->prepare("
        INSERT INTO orders
        (user_id, fullname, phone, address, note, total, payment_method, status, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
    ");

    $stmt->execute([
        $user_id,
        $fullname,
        $phone,
        $full_address,
        $note,
        $total,
        $payment_method
    ]);


    // lấy order id
    $order_id = $pdo->lastInsertId();

    // lưu order_items
    foreach ($checkout_items as $item) {

        $product_id = $item['id'];
        $quantity = $item['qty'];
        $price = $item['price'];

        $stmtItem = $pdo->prepare("
            INSERT INTO order_items
            (order_id, product_id, quantity, price)
            VALUES (?, ?, ?, ?)
        ");

        $stmtItem->execute([
            $order_id,
            $product_id,
            $quantity,
            $price
        ]);
       $stmtStock = $pdo->prepare("
    UPDATE products
    SET stock = stock - ?
    WHERE id = ?
    ");

    $stmtStock->execute([$quantity, $product_id]);

    }

    // xóa session
    unset($_SESSION['checkout_selected']);
    unset($_SESSION['checkout_mode']);
    unset($_SESSION['buy_now_item']);

    // chuyển trang
if ($payment_method == 'BANKING') {

    header("Location: pay_qr.php?order_id=" . $order_id);
    exit;

} else {

    echo "
    <script>
        alert('Đặt hàng thành công!');
        window.location.href = 'index.php';
    </script>
    ";
    exit;
}

}
?>