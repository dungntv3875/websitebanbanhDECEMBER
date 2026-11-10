<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'db.php';

// ==========================================
// BACKEND XỬ LÝ POST
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Chỉ xử lý nếu là JSON request từ chatbot
    $raw = file_get_contents('php://input');
    $input = json_decode($raw, true);
    if (!is_array($input)) goto end_chat_post;

    while (ob_get_level()) ob_end_clean();
    header('Content-Type: application/json');

    try {
        // ---- ACTION: CHAT TRỰC TIẾP VỚI ADMIN ----
        $chat_action = $input['chat_action'] ?? '';

        if ($chat_action === 'send_to_admin') {
            $message = trim($input['message'] ?? '');
            $user_id = $_SESSION['user']['id'] ?? 0;
              if (!$message) {
                  echo json_encode(['ok' => false, 'error' => 'Thiếu tin nhắn']);
                  exit;
              }
              // Nếu chưa đăng nhập, dùng user_id = 0 (khách vãng lai)
              // hoặc báo lỗi thân thiện
              if (!$user_id) {
                  echo json_encode(['ok' => false, 'error' => 'Vui lòng đăng nhập để nhắn với nhân viên']);
                  exit;
              }
                $pdo->prepare("INSERT INTO admin_chats (user_id, sender, message) VALUES (?, 'user', ?)")
                    ->execute([$user_id, $message]);
                echo json_encode(['ok' => true]);
                exit;
            }

        if ($chat_action === 'get_admin_replies') {
            $user_id  = $_SESSION['user']['id'] ?? 0;
            $since_id = (int)($input['since_id'] ?? 0);
            if (!$user_id) { echo json_encode(['ok' => false]); exit; }
            $stmt = $pdo->prepare(
                "SELECT id, sender, message, created_at FROM admin_chats
                WHERE user_id = ? AND id > ? ORDER BY id ASC LIMIT 50"
            );
            $stmt->execute([$user_id, $since_id]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            // Đánh dấu tin nhắn admin là đã đọc
            $pdo->prepare("UPDATE admin_chats SET is_read=1 WHERE user_id=? AND sender='admin' AND is_read=0")
                ->execute([$user_id]);
            echo json_encode(['ok' => true, 'messages' => $rows]);
            exit;
        }
     
        $messages = is_array($input['messages'] ?? null) ? $input['messages'] : [];
        if (empty($messages)) { echo json_encode(['reply' => '', 'suggestions' => [], 'type' => 'text']); exit; }
        $rawMsg = trim(end($messages)['content'] ?? '');
        // Chuyển về chữ thường để so khớp dễ hơn
        $userMsg = mb_strtolower($rawMsg, 'UTF-8');
        $username = $_SESSION['user']['fullname'] ?? $_SESSION['user']['name'] ?? 'bạn';
        $product_id = isset($input['product_id']) ? (int)$input['product_id'] : 0; // ID sản phẩm đang xem
        
        // Lấy thông tin sản phẩm đang xem (nếu có)
        $current_product = null;
        if ($product_id > 0) {
            $stmtP = $pdo->prepare("SELECT id, name, stock, price, sale_price FROM products WHERE id = ?");
            $stmtP->execute([$product_id]);
            $current_product = $stmtP->fetch(PDO::FETCH_ASSOC);
        }

        $reply = "";
        $suggestions = [];
        $type = "text"; // text | product | contact

        // ---- KIỂM TRA TỒN KHO SẢN PHẨM ĐANG XEM ----
        if ($current_product && preg_match('/(còn hàng|hết hàng|còn không|có hàng|stock|tồn kho|in stock|còn sẵn|có sẵn|available)/ui', $userMsg)) {
            $stock = (int)$current_product['stock'];
            $pName = $current_product['name'];
            if ($stock > 0) {
                $alert = $stock < 5 ? "\n🔥 **Chỉ còn $stock cái cuối cùng — đặt hàng sớm để không bỏ lỡ!**" : "";
                $reply = "✅ Sản phẩm **$pName** hiện **còn $stock sản phẩm**.$alert";
                $suggestions = ["Kết nối nhân viên", "Chính sách đổi trả"];
            } else {
                $reply = "❌ Rất tiếc, sản phẩm **$pName** hiện đã **hết hàng**.\nBạn có muốn mình báo nhân viên đặt trước hoặc tìm mẫu tương tự không?";
                $suggestions = ["Kết nối nhân viên", "Tìm sản phẩm tương tự", "Xem hàng mới về"];
            }
            $type = "product";
        }

        // ---- CHÀO HỎI ----
        elseif (preg_match('/(^hi$|^hello$|chào|xin chào|bắt đầu|giúp gì)/ui', $userMsg)) {
            $reply = "Xin chào **$username**! Mình là trợ lý bánh ngọt của **December** ✨\nMình có thể giúp bạn:\n- Kiểm tra tồn kho sản phẩm\n- 🍩 Tư vấn hương vị và combo donut\n- 🚚 Tư vấn phí ship và giao hàng\n- 🔄 Chính sách đổi trả, bảo quản và đặt bánh\n\nBạn cần hỗ trợ gì ạ?";
            $suggestions = ["Hướng dẫn bảo quản", "Chính sách đổi trả", "Thanh toán COD"];
        }

        // ---- KẾT NỐI NGƯỜI BÁN ----
        elseif (preg_match('/(người bán|nhân viên|tổng đài|trực tiếp|chat với|tư vấn viên|quản lý|admin)/ui', $userMsg)) {
            $type = "contact";
            $reply = "Mình đã ghi nhận yêu cầu của **$username** và sẽ kết nối bạn với nhân viên December ngay bây giờ! 📞\nThời gian hỗ trợ: **8:00 - 22:00** hàng ngày.";
            $suggestions = ["Quay lại hỏi bot", "Chính sách đổi trả", "Kiểm tra đơn hàng"];
        }

        // ---- THANH TOÁN / VẬN CHUYỂN ----
        elseif (preg_match('/(cod|thanh toán|ship|vận chuyển|phí ship|giao hàng|chuyển khoản|momo|vnpay)/ui', $userMsg)) {
            $reply = "December hỗ trợ các hình thức thanh toán:\n- 💵 **COD** - Thanh toán khi nhận hàng\n- 🏦 **Chuyển khoản** ngân hàng\n- 📱 **VNPay**\n\n🚚 **Vận chuyển:**\n- Miễn phí ship đơn từ **500.000đ**\n- Giao nhanh nội thành Hà Nội\n- Toàn quốc: 1-3 ngày làm việc";
            $suggestions = ["Theo dõi đơn hàng", "Chính sách đổi trả", "Chính sách thanh toán"];
        }

        // ---- BẢO QUẢN BÁNH ----
        elseif (preg_match('/(bảo quản|bảo quản bánh|hướng dẫn bảo quản|cách bảo quản|giữ bánh|giữ lạnh|nhiệt độ|để tủ lạnh|tủ lạnh|bảo quản ở đâu|care)/ui', $userMsg)) {
            $reply = "🧁 **Chính sách bảo quản bánh của December**\n\n✅ **Bánh ngọt thông thường**\n- Bảo quản nơi khô ráo, thoáng mát, tránh ánh nắng trực tiếp\n- Nên ăn trong vòng 24 giờ sau khi nhận hàng nếu chưa dùng ngay\n- Tránh để nơi quá nóng hoặc ẩm mốc\n\n✅ **Bánh có nhân kem / cream / mousse**\n- Nên bảo quản trong tủ lạnh ở nhiệt độ 0–5°C\n- Nếu để ngoài tủ lạnh quá lâu, bánh dễ mềm, chảy nước hoặc mất hương vị\n- Khi dùng, nên để bánh ở nhiệt độ phòng khoảng 10–15 phút để bánh mềm hơn và thơm hơn\n\n✅ **Bánh có nhân lạnh / đông lạnh**\n- Bảo quản trong ngăn mát tủ lạnh\n- Không để trực tiếp dưới ánh nắng hoặc nơi quá nóng\n\n⚠️ **Lưu ý quan trọng**\n- Không để bánh gần mùi mạnh hoặc nơi ẩm ướt\n- Bánh nên được đặt trong hộp kín để giữ độ tươi và tránh mùi lạ\n- Nếu bánh bị mềm, chảy nước, hoặc có dấu hiệu ôi thiu, khách hàng nên không sử dụng nữa\n\n📌 December khuyến khích khách hàng giữ bánh đúng cách để đảm bảo hương vị và chất lượng tốt nhất.";
            $suggestions = ["Chính sách đổi trả", "Kết nối nhân viên", "Tìm donut mới"];
        }

        // ---- ĐỔI TRẢ / HOÀN TIỀN ----
        elseif (preg_match('/(đổi|trả|hoàn tiền|hoàn hàng|lỗi|defect|return)/ui', $userMsg)) {
            $reply = "Chính sách **Đổi Trả** của December:\n\n✅ **Được hỗ trợ đổi trả** nếu:\n- Bánh bị lỗi, hỏng trong quá trình vận chuyển\n- Giao sai loại bánh, sai số lượng\n- Sản phẩm không đúng như đơn đặt\n\n❌ **Không hỗ trợ** khi:\n- Khách hàng đổi ý, đặt nhầm hương vị\n- Bánh đã qua sử dụng, bảo quản sai cách\n\n📸 Vui lòng chụp ảnh/video khi nhận hàng để xử lý nhanh nhất!";
            $suggestions = ["Kết nối nhân viên", "Hướng dẫn bảo quản", "Theo dõi đơn hàng"];
        }

        // ---- THEO DÕI ĐƠN HÀNG ----
        elseif (preg_match('/(đơn hàng|order|theo dõi|tracking|tình trạng đơn|mã vận đơn)/ui', $userMsg)) {
            $reply = "📦 Để theo dõi đơn hàng, bạn có thể:\n\n1. **Đăng nhập** tài khoản December → Mục **Đơn hàng của tôi**\n2. **Kiểm tra tin nhắn/SMS** từ cửa hàng hoặc đơn vị vận chuyển\n3. **Liên hệ nhân viên** để tra cứu nhanh bằng mã đơn\n\nBạn có mã đơn hàng không? Mình hỗ trợ tra cứu ngay!";
            $suggestions = ["Kết nối nhân viên", "Chính sách đổi trả", "Tìm donut mới"];
        }

        // ---- ĐẶT HÀNG ----
        elseif (preg_match('/(đặt hàng|mua ngay|order|checkout)/ui', $userMsg)) {
        $type = "contact";
        $reply = "Để đặt bánh, bạn chọn sản phẩm yêu thích và bấm **Mua Ngay** trên trang sản phẩm nhé! 🛒\nHoặc mình có thể nối bạn với nhân viên để đặt hàng nhanh hơn.";
        $suggestions = ["Kết nối nhân viên", "Chính sách đổi trả"];
        }
        // ---- TÌM KIẾM SẢN PHẨM (FALLBACK THÔNG MINH) ----
        else {
            $stopwords = ['tìm', 'có', 'không', 'mẫu', 'cái', 'sản phẩm', 'còn hàng', 'check', 'kho', 'kiểm tra', 'xem', 'cho', 'mình', 'hỏi', '?', '"', 'bán', 'muốn', 'mua'];
            $search = str_replace($stopwords, '', $userMsg);
            $search = trim(preg_replace('/\s+/', ' ', $search));

            if (mb_strlen($search) > 1) {
                $stmt = $pdo->prepare("SELECT name, price, stock FROM products WHERE name LIKE :term LIMIT 5");
                $stmt->execute(['term' => "%$search%"]);
                $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if ($products) {
                    if (count($products) === 1) {
                        $p = $products[0];
                        $tonKho = (int)$p['stock'];
                        $statusIcon = $tonKho > 0 ? "✅" : "❌";
                        $statusText = $tonKho > 0 ? "còn $tonKho sản phẩm" : "hết hàng";
                        $alert = ($tonKho > 0 && $tonKho < 5) ? "\n🔥 **Chỉ còn $tonKho cái cuối cùng!**" : "";
                        $reply = "$statusIcon Mẫu **" . $p['name'] . "** - " . number_format($p['price'], 0, ',', '.') . "đ\nTình trạng: **$statusText**" . $alert;
                        $type = "product";
                        $suggestions = ["Đặt hàng mẫu này", "Kết nối nhân viên", "Tìm sản phẩm khác"];
                    } else {
                        $reply = "Mình tìm thấy **" . count($products) . " sản phẩm** phù hợp với \"$search\":\n\n";
                        foreach ($products as $p) {
                            $tonKho = (int)$p['stock'];
                            $icon = $tonKho > 0 ? "✅" : "❌";
                            $reply .= "$icon **" . $p['name'] . "** - " . number_format($p['price'], 0, ',', '.') . "đ - " . ($tonKho > 0 ? "còn $tonKho sản phẩm" : "hết hàng") . "\n";
                        }
                        $type = "product";
                        $suggestions = ["Xem thêm chi tiết", "Kết nối nhân viên"];
                    }
                } else {
                   $reply = "Mình chưa tìm thấy **\"$search\"** trong danh sách hiện tại. Mình đã ghi nhận và **nhân viên December sẽ liên hệ hỗ trợ bạn sớm nhất** nhé! 💌";
                   $suggestions = ["Kết nối nhân viên", "Chính sách đổi trả"];

                }
            } else {
                // Câu hỏi không khớp với bất kỳ chủ đề nào → thông báo người bán sẽ trả lời
                $reply = "Cảm ơn câu hỏi của **$username**! 📩\nMình chưa có câu trả lời chính xác cho vấn đề này. Mình đã ghi nhận và **người bán sẽ liên hệ trả lời bạn sớm nhất** nhé!\n\nBạn cũng có thể nhắn trực tiếp với nhân viên để được hỗ trợ ngay.";
                $suggestions = ["Kết nối nhân viên", "Chính sách đổi trả"];
            }
        }

        echo json_encode([
            'reply' => $reply,
            'suggestions' => $suggestions,
            'type' => $type
        ]);

    } catch (Exception $e) {
        echo json_encode(['reply' => "⚠️ Lỗi hệ thống: " . $e->getMessage(), 'suggestions' => [], 'type' => 'text']);
    }
    exit;
}
end_chat_post:
?>


<!-- Toggle button -->
<button id="rb-toggle" aria-label="Mở chat hỗ trợ">
  <svg width="24" height="24" fill="#fff" viewBox="0 0 24 24">
    <path d="M20 2H4a2 2 0 0 0-2 2v18l4-4h14a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2z"/>
  </svg>
  <span id="rb-badge" style="display:none">1</span>
</button>

<!-- Cửa sổ chat -->
<div id="rb-window" role="dialog" aria-label="Chat hỗ trợ December">
  <div class="rb-header">
<div class="rb-avatar">
      <svg width="20" height="20" fill="#fff" viewBox="0 0 24 24">
        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 14H9V8h2v8zm4 0h-2V8h2v8z" opacity=".4"/>
        <path d="M12 2a10 10 0 1 0 0 20A10 10 0 0 0 12 2zm0 18a8 8 0 1 1 0-16 8 8 0 0 1 0 16z"/>
      </svg>
    </div>
    <div class="rb-header-info">
      <div class="rb-header-name">December Assistant</div>
      <div class="rb-header-status"><span class="rb-status-dot"></span> Đang hoạt động</div>
    </div>
    <button class="rb-close" id="rb-close" aria-label="Đóng">
      <svg width="18" height="18" fill="currentColor" viewBox="0 0 24 24">
        <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
      </svg>
    </button>
  </div>
  <div class="rb-messages" id="rb-messages"></div>
  <div class="rb-footer">
    <textarea id="rb-input" rows="1" placeholder="Nhắn tin cho December..."></textarea>
    <button id="rb-send" aria-label="Gửi">
      <svg width="16" height="16" fill="#fff" viewBox="0 0 24 24"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg>
    </button>
  </div>
</div>

<script>
(function () {
  const CHATBOT_URL = 'chat.php';
  // Lấy product_id từ trang hiện tại (nếu đang xem sản phẩm)
  const PRODUCT_ID = (function() {
    const meta = document.querySelector('meta[name="product-id"]');
    if (meta) return parseInt(meta.content) || 0;
    const m = location.search.match(/[?&]id=(\d+)/);
    return m ? parseInt(m[1]) : 0;
  })();
  const win = document.getElementById('rb-window');
  const msgsEl = document.getElementById('rb-messages');
  const inputEl = document.getElementById('rb-input');
  const sendBtn = document.getElementById('rb-send');
  const badge = document.getElementById('rb-badge');
  const toggleBtn = document.getElementById('rb-toggle');
  let history = [];
  let isOpen = false;

  // ========== TOGGLE ==========
  function toggleChat() {
    isOpen = !isOpen;
    win.classList.toggle('rb-open', isOpen);
    if (isOpen) { badge.style.display = 'none'; inputEl.focus(); }
  }
  toggleBtn.addEventListener('click', toggleChat);
  document.getElementById('rb-close').addEventListener('click', () => { isOpen = false; win.classList.remove('rb-open'); });

  // ========== GỬI TIN NHẮN TỪ GỢI Ý ==========
  window.rbSend = function (text) {
    inputEl.value = text;
    send();
  };

  // ========== RENDER MARKDOWN ĐƠN GIẢN ==========
  function renderMarkdown(text) {
    return text
      .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
      .replace(/`(.+?)`/g, '<code style="background:#edf1e8;padding:1px 5px;border-radius:4px;font-family:monospace;font-size:12px">$1</code>')
      // Bảng markdown
      .replace(/\|(.+)\|\n\|[-| :]+\|\n((?:\|.+\|\n?)+)/g, function(_, header, rows) {
        const ths = header.split('|').filter(c => c.trim()).map(c => `<th>${c.trim()}</th>`).join('');
        const trs = rows.trim().split('\n').map(row => {
          const tds = row.split('|').filter(c => c.trim()).map(c => `<td>${c.trim()}</td>`).join('');
          return `<tr>${tds}</tr>`;
        }).join('');
return `<table><thead><tr>${ths}</tr></thead><tbody>${trs}</tbody></table>`;
      })
      // Bullet list
      .replace(/^- (.+)$/gm, '<li>$1</li>')
      .replace(/(<li>.*<\/li>)/s, '<ul>$1</ul>')
      .replace(/\n/g, '<br>');
  }

  // ========== THỜI GIAN ==========
  function getTime() {
    const d = new Date();
    return d.getHours().toString().padStart(2,'0') + ':' + d.getMinutes().toString().padStart(2,'0');
  }

  // ========== THÊM TIN NHẮN ==========
  function append(role, text, suggestions = [], type = 'text') {
    const div = document.createElement('div');
    div.className = `rb-msg rb-${role}`;
    const html = renderMarkdown(text);

    let content = `<div class="rb-bubble">${html}</div>`;
    content += `<div class="rb-time">${getTime()}</div>`;

    if (role === 'bot' && suggestions.length > 0) {
      const btns = suggestions.map(s =>
        `<button class="rb-suggestion" onclick="rbSend('${s.replace(/'/g,"\\'")}')">💬 ${s}</button>`
      ).join('');
      content += `<div class="rb-suggestions">${btns}</div>`;
    }

    if (type === 'contact') {
      content += `
        <div class="rb-contact-bar">
          <span>📞 Kết nối nhân viên trực tiếp</span>
          <button id="rb-contact-btn">Chat ngay</button>
        </div>`;
    }

    div.innerHTML = content;
    msgsEl.appendChild(div);
    msgsEl.scrollTop = msgsEl.scrollHeight;
  }

  // ========== TYPING INDICATOR ==========
  function showTyping() {
    const el = document.createElement('div');
    el.className = 'rb-typing'; el.id = 'rb-typing';
    el.innerHTML = `<div class="rb-typing-bubble"><div class="rb-dot"></div><div class="rb-dot"></div><div class="rb-dot"></div></div>`;
    msgsEl.appendChild(el);
    msgsEl.scrollTop = msgsEl.scrollHeight;
  }
  function hideTyping() {
    const el = document.getElementById('rb-typing');
    if (el) el.remove();
  }

  // ========== LỜI CHÀO BAN ĐẦU ==========
  append('bot', 'Xin chào! Mình là trợ lý December ✨ Bạn cần tư vấn gì hôm nay?', [
    'Còn hàng không?',
    'Chính sách đổi trả',
    'Chat với nhân viên'
]);
  // Hiện badge sau 2s
  setTimeout(() => { if (!isOpen) badge.style.display = 'flex'; }, 2000);

  // ========== GỬI ==========
  async function send() {
    const msg = inputEl.value.trim();
    if (!msg) return;
    append('user', msg);
    inputEl.value = '';
    inputEl.style.height = 'auto';
    history.push({ role: 'user', content: msg });

    showTyping();

    // Delay giả lập "bot đang soạn"
    const delay = 600 + Math.random() * 600;
    await new Promise(r => setTimeout(r, delay));

    try {
      const res = await fetch(CHATBOT_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ messages: history, product_id: PRODUCT_ID })
      });
      const data = await res.json();
      hideTyping();
append('bot', data.reply || 'Xin lỗi, mình chưa hiểu câu hỏi này!', data.suggestions || [], data.type || 'text');
      history.push({ role: 'assistant', content: data.reply });
    } catch (e) {
      hideTyping();
      append('bot', '❌ Lỗi kết nối. Vui lòng chạy qua Localhost (XAMPP)!', ['Thử lại', 'Kết nối nhân viên']);
    }
  }

  sendBtn.addEventListener('click', send);
  inputEl.addEventListener('keydown', (e) => {
    if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); send(); }
  });

    // Auto-resize textarea
  inputEl.addEventListener('input', function () {
    this.style.height = 'auto';
    this.style.height = Math.min(this.scrollHeight, 90) + 'px';
  });

  // ========== ADMIN CHAT PANEL ==========
  let adminChatOpen = false;
  let adminLastId   = 0;
  let adminPoll     = null;

  // Event delegation cho nút Chat ngay (sinh động bằng innerHTML)
  document.addEventListener('click', function(e) {
    if (e.target && e.target.id === 'rb-contact-btn') {
      window.rbOpenAdminChat();
    }
  });

  window.rbOpenAdminChat = function() {
    const panel = document.getElementById('rb-admin-chat');
    if (!panel) return;
    adminChatOpen = true;
    panel.style.display = 'flex';
    document.getElementById('rb-admin-messages').innerHTML = '';
    adminLastId = 0;
    loadAdminHistory();
    clearInterval(adminPoll);
    adminPoll = setInterval(pollAdminMessages, 4000);
    document.getElementById('rb-admin-input').focus();
  };

  function closeAdminChat() {
    adminChatOpen = false;
    document.getElementById('rb-admin-chat').style.display = 'none';
    clearInterval(adminPoll);
  }

  async function loadAdminHistory() {
    try {
      const res  = await fetch(CHATBOT_URL, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ chat_action: 'get_admin_replies', since_id: 0 })
      });
      const data = await res.json();
      if (data.ok && data.messages && data.messages.length) {
        data.messages.forEach(m => appendAdminMsg(m.sender, m.message, m.id, m.created_at));
      } else {
        appendAdminMsg('admin', '👋 Xin chào! Nhân viên December đang sẵn sàng hỗ trợ bạn. Hãy để lại tin nhắn nhé!', null, null);
      }
    } catch(e) {
      appendAdminMsg('admin', '👋 Xin chào! Nhân viên December đang sẵn sàng hỗ trợ bạn. Hãy để lại tin nhắn nhé!', null, null);
    }
  }
  // Poll tin nhắn mới từ admin
  async function pollAdminMessages() {
    if (!adminChatOpen) return;
    try {
      const res  = await fetch(CHATBOT_URL, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ chat_action: 'get_admin_replies', since_id: adminLastId })
      });
      const data = await res.json();
      if (data.ok && data.messages) data.messages.forEach(m => appendAdminMsg(m.sender, m.message, m.id, m.created_at));
    } catch(e) {}
  }

  function appendAdminMsg(sender, text, id, ts) {
    if (id && id <= adminLastId) return;
    if (id) adminLastId = Math.max(adminLastId, parseInt(id));
    const box  = document.getElementById('rb-admin-messages');
    const isMe = sender === 'user';
    const d    = ts ? new Date(ts) : new Date();
    const time = d.getHours().toString().padStart(2,'0') + ':' + d.getMinutes().toString().padStart(2,'0');
    const div  = document.createElement('div');
    div.className = `rb-msg ${isMe ? 'rb-user' : 'rb-bot'}`;
    div.innerHTML = `<div class="rb-bubble">${text.replace(/</g,'&lt;')}</div><div class="rb-time">${time}</div>`;
    box.appendChild(div);
    box.scrollTop = box.scrollHeight;
  }

  async function sendAdminMessage() {
    const inp = document.getElementById('rb-admin-input');
    const msg = inp.value.trim();
    if (!msg) return;

    inp.value = '';
    inp.style.height = 'auto';

    try {
      const res  = await fetch(CHATBOT_URL, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ chat_action: 'send_to_admin', message: msg })
      });
      const data = await res.json();
      if (data.ok) {
        // Không append ngay — để poll tự load từ DB tránh hiện 2 lần
        await pollAdminMessages();
      } else if (data.error === 'login_required') {
        appendAdminMsg('admin', '⚠️ Bạn cần <a href="account.php" style="color:#355638;">đăng nhập</a> để nhắn với nhân viên nhé!', null, null);
      } else {
        appendAdminMsg('admin', '❌ Gửi thất bại, vui lòng thử lại.', null, null);
      }
    } catch(e) {
      appendAdminMsg('admin', '❌ Lỗi kết nối.', null, null);
    }
}


  // Gắn event sau khi DOM load xong
  if (document.readyState === "loading") { document.addEventListener("DOMContentLoaded", initAdminChat); } else { initAdminChat(); } function initAdminChat() {
    var adminSendBtn  = document.getElementById("rb-admin-send");
    var adminInput    = document.getElementById("rb-admin-input");
    var adminCloseBtn = document.getElementById("rb-admin-close");
    if (adminSendBtn) adminSendBtn.addEventListener("click", sendAdminMessage);
    if (adminInput) {
      adminInput.addEventListener("keydown", function(e) {
        if (e.key === "Enter" && !e.shiftKey) { e.preventDefault(); sendAdminMessage(); }
      });
      adminInput.addEventListener("input", function() {
        this.style.height = "auto";
        this.style.height = Math.min(this.scrollHeight, 90) + "px";
      });
    }
    if (adminCloseBtn) adminCloseBtn.addEventListener("click", closeAdminChat);
  }

})();
</script>

<!-- ===== PANEL CHAT NHÂN VIÊN ===== -->
<div id="rb-admin-chat">
  <div class="rb-admin-header">
    <div class="rb-admin-avatar">
      <svg width="18" height="18" fill="#fff" viewBox="0 0 24 24">
        <path d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8v2.4h19.2v-2.4c0-3.2-6.4-4.8-9.6-4.8z"/>
      </svg>
    </div>
    <div class="rb-header-info">
      <div class="rb-header-name">Nhân viên December</div>
      <div class="rb-header-status">
        <span class="rb-status-dot"></span> Đang trực tuyến
      </div>
    </div>
    <button class="rb-close" id="rb-admin-close" aria-label="Đóng">
      <svg width="18" height="18" fill="currentColor" viewBox="0 0 24 24">
        <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
      </svg>
    </button>
  </div>
  <div id="rb-admin-messages" class="rb-messages"></div>
  <div class="rb-footer">
    <textarea id="rb-admin-input" rows="1" placeholder="Nhắn với nhân viên..."></textarea>
    <button id="rb-admin-send" aria-label="Gửi">
      <svg width="16" height="16" fill="#fff" viewBox="0 0 24 24">
        <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/>
      </svg>
    </button>
  </div>
</div>