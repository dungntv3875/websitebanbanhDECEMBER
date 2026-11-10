<?php
session_start();
include 'db.php';

// Xóa khỏi giỏ hàng đúng những sản phẩm đã đặt trong đơn này
function clear_cart_for_order(int $order_id): void {
    if (!isset($_SESSION['order_cart_keys'][$order_id])) return;
    $keys = $_SESSION['order_cart_keys'][$order_id];
    if ($keys === 'ALL') {
        unset($_SESSION['cart']);
    } elseif (is_array($keys)) {
        foreach ($keys as $k) {
            unset($_SESSION['cart'][$k]);
        }
    }
    unset($_SESSION['order_cart_keys'][$order_id]);
}

// Ngoại lệ riêng cho trường hợp không đủ hàng (kèm id sản phẩm để quay lại đúng trang)
class StockException extends Exception {
    public $product_id;
    public function __construct($message, $product_id) {
        parent::__construct($message);
        $this->product_id = $product_id;
    }
}

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

    if (!isset($_SESSION['user'])) {
        header('Location: account.php?type=login');
        exit;
    }
    $user_id = $_SESSION['user']['id'];

    $fullname = trim($_POST['fullname'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $district = trim($_POST['district'] ?? '');
    $ward = trim($_POST['ward'] ?? '');
    $note = trim($_POST['note'] ?? '');
    $payment_method = $_POST['payment_method'] ?? 'COD';

    // Giữ lại dữ liệu khách đã nhập để điền lại form nếu có lỗi
    $_SESSION['checkout_old'] = [
        'fullname' => $fullname, 'phone' => $phone, 'address' => $address, 'note' => $note,
    ];

    // ===== KIỂM TRA FORM (phía server) =====
    $form_error = '';
    if ($fullname === '' || mb_strlen($fullname) > 100) {
        $form_error = 'Họ và tên không được để trống (tối đa 100 ký tự).';
    } elseif (!preg_match('/^(03|05|07|08|09)\d{8}$/', $phone)) {
        $form_error = 'Số điện thoại không đúng định dạng (VD: 0912345678).';
    } elseif ($address === '' || mb_strlen($address) > 255) {
        $form_error = 'Địa chỉ không được để trống (tối đa 255 ký tự).';
    } elseif ($district === '' || $ward === '') {
        $form_error = 'Vui lòng chọn đầy đủ Quận / Huyện và Phường / Xã.';
    } elseif (!in_array($payment_method, ['COD', 'BANKING'], true)) {
        $form_error = 'Hình thức thanh toán không hợp lệ.';
    }
    if ($form_error !== '') {
        $_SESSION['checkout_error'] = $form_error;
        header('Location: checkout.php');
        exit;
    }

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
    $total = 0;
    foreach ($checkout_items as $item) {
        $total += $item['price'] * $item['qty'];
    }

    // cộng phí ship (giống checkout.php)
    $ship_fee = ($total > 500000) ? 0 : 30000;
    $total += $ship_fee;

    // Sắp xếp theo id sản phẩm để các đơn đặt cùng lúc khóa dòng theo cùng thứ tự,
    // tránh deadlock khi 2 khách đặt nhiều sản phẩm giống nhau.
    uasort($checkout_items, function ($a, $b) {
        return (int)$a['id'] <=> (int)$b['id'];
    });

    // Ghi nhớ sản phẩm nào của giỏ hàng thuộc đơn này, để chỉ xóa khỏi giỏ khi đặt/thanh toán thành công
    if (isset($_SESSION['checkout_mode']) && $_SESSION['checkout_mode'] === 'buy_now') {
        $cart_keys = [];
    } elseif (isset($_SESSION['checkout_selected'])) {
        $cart_keys = array_keys($checkout_items);
    } else {
        $cart_keys = 'ALL';
    }

    // Toàn bộ: kiểm tra kho + tạo đơn + trừ kho nằm trong 1 transaction.
    // Nếu 1 sản phẩm hết hàng thì hủy cả đơn, không bị trừ kho nửa chừng.
    try {
        $pdo->beginTransaction();

        // lưu orders
        $stmt = $pdo->prepare("
            INSERT INTO orders
            (user_id, fullname, phone, address, note, total, payment_method, payment_status, status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'unpaid', 'pending', NOW())
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
        $order_id = $pdo->lastInsertId();

        $stmtItem = $pdo->prepare("
            INSERT INTO order_items (order_id, product_id, quantity, price)
            VALUES (?, ?, ?, ?)
        ");
        // Trừ kho nguyên tử: chỉ trừ khi còn đủ hàng (stock >= số lượng đặt)
        $stmtStock = $pdo->prepare("
            UPDATE products SET stock = stock - ?
            WHERE id = ? AND stock >= ?
        ");

        foreach ($checkout_items as $item) {
            $product_id = (int)$item['id'];
            $quantity   = (int)$item['qty'];
            $price      = $item['price'];

            if ($quantity < 1) {
                throw new Exception('Số lượng không hợp lệ.');
            }

            $stmtStock->execute([$quantity, $product_id, $quantity]);
            if ($stmtStock->rowCount() === 0) {
                // Người đặt trước đã lấy hết/bớt hàng: đơn này bị từ chối
                $n = $pdo->prepare("SELECT name, stock FROM products WHERE id = ?");
                $n->execute([$product_id]);
                $row = $n->fetch(PDO::FETCH_ASSOC);
                $pname = $row['name'] ?? 'đã chọn';
                $left  = (int)($row['stock'] ?? 0);
                if ($left <= 0) {
                    $m = 'Rất tiếc, sản phẩm "' . $pname . '" vừa hết hàng. Đơn của bạn chưa được đặt.';
                } else {
                    $m = 'Rất tiếc, sản phẩm "' . $pname . '" chỉ còn ' . $left . ' sản phẩm (bạn chọn ' . $quantity . '). Đơn của bạn chưa được đặt, vui lòng giảm số lượng.';
                }
                throw new StockException($m, $product_id);
            }

            $stmtItem->execute([$order_id, $product_id, $quantity, $price]);
        }

        $pdo->commit();
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        if ($e instanceof StockException) {
            $error_msg = $e->getMessage();
            if (isset($_SESSION['checkout_mode']) && $_SESSION['checkout_mode'] === 'buy_now') {
                $_SESSION['stock_error'] = $error_msg;
                header('Location: product.php?id=' . (int)$e->product_id);
            } else {
                $_SESSION['cart_error'] = $error_msg;
                header('Location: cart.php');
            }
        } else {
            // Lỗi khác (deadlock, mất kết nối...): không lộ chi tiết kỹ thuật
            $_SESSION['checkout_error'] = 'Hệ thống đang bận, vui lòng bấm đặt hàng lại.';
            header('Location: checkout.php');
        }
        exit;
    }

    // Đặt hàng xong
    $order_id = (int)$order_id;
    $_SESSION['order_cart_keys'][$order_id] = $cart_keys;
    unset($_SESSION['checkout_selected']);
    unset($_SESSION['checkout_mode']);
    unset($_SESSION['buy_now_item']);
    unset($_SESSION['checkout_old']);

    // Chuyển trang theo hình thức thanh toán
    clear_cart_for_order($order_id);

    if ($payment_method === 'BANKING') {
        header('Location: pay_qr.php?order_id=' . $order_id);
    } else {
        header('Location: thanks.php?order_id=' . $order_id);
    }
    exit;
}
?>