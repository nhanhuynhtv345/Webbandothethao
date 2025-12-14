<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

try {
    // Check if user is logged in
    if (!isLoggedIn()) {
        echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập']);
        exit;
    }

    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validate required fields
    $required = ['ho_ten', 'dien_thoai', 'email', 'dia_chi', 'tinh_thanh', 'quan_huyen', 'phuong_xa', 'phuong_thuc_thanh_toan'];
    foreach ($required as $field) {
        if (empty($input[$field])) {
            echo json_encode(['success' => false, 'message' => 'Vui lòng điền đầy đủ thông tin']);
            exit;
        }
    }

    $db = getDB();
    $userId = $_SESSION['user_id'];
    
    // Get cart items
    $stmtCart = $db->prepare("
        SELECT gh.*, sp.ten_san_pham, sp.gia_ban, sp.so_luong_ton,
               bienthe.ten_bien_the, bienthe.gia_ban as bien_the_gia
        FROM gio_hang gh
        INNER JOIN san_pham sp ON gh.san_pham_id = sp.id
        LEFT JOIN bien_the_san_pham bienthe ON gh.bien_the_id = bienthe.id
        WHERE gh.nguoi_dung_id = ?
    ");
    $stmtCart->execute([$userId]);
    $cartItems = $stmtCart->fetchAll();
    
    if (empty($cartItems)) {
        echo json_encode(['success' => false, 'message' => 'Giỏ hàng trống']);
        exit;
    }
    
    // Check stock availability
    foreach ($cartItems as $item) {
        if ($item['so_luong'] > $item['so_luong_ton']) {
            echo json_encode([
                'success' => false, 
                'message' => "Sản phẩm '{$item['ten_san_pham']}' không đủ số lượng trong kho"
            ]);
            exit;
        }
    }
    
    // Calculate totals
    $subtotal = 0;
    foreach ($cartItems as $item) {
        $price = $item['bien_the_gia'] ?? $item['gia_ban'];
        $subtotal += $price * $item['so_luong'];
    }
    
    // Free shipping for orders over 500k
    $shippingFee = $subtotal >= 500000 ? 0 : 30000;
    
    // Apply coupon discount
    $discountAmount = 0;
    $couponId = null;
    $couponCode = null;
    
    if (!empty($input['coupon_id'])) {
        $couponId = intval($input['coupon_id']);
        $couponCode = $input['coupon_code'] ?? '';
        $discountAmount = floatval($input['discount_amount'] ?? 0);
        
        // Verify coupon is still valid
        $stmtCoupon = $db->prepare("SELECT * FROM khuyen_mai WHERE id = ? AND active = 1 AND start_at <= NOW() AND end_at >= NOW()");
        $stmtCoupon->execute([$couponId]);
        $coupon = $stmtCoupon->fetch();
        
        if ($coupon) {
            // Free shipping coupon
            if ($coupon['type'] === 'shipping') {
                $shippingFee = 0;
                $discountAmount = 0;
            }
        } else {
            $discountAmount = 0;
            $couponId = null;
        }
    }
    
    $total = $subtotal - $discountAmount + $shippingFee;
    if ($total < 0) $total = 0;
    
    // Generate order code
    $orderCode = 'DH' . date('Ymd') . rand(1000, 9999);
    
    // Build full address
    $fullAddress = $input['dia_chi'] . ', ' . $input['phuong_xa'] . ', ' . $input['quan_huyen'] . ', ' . $input['tinh_thanh'];
    
    // Start transaction
    $db->beginTransaction();
    
    try {
        // Create order - theo cấu trúc bảng don_hang
        $stmtOrder = $db->prepare("
            INSERT INTO don_hang (
                nguoi_dung_id, ma_don_hang, 
                ho_ten, so_dien_thoai, email, dia_chi,
                tinh_thanh, quan_huyen, phuong_xa,
                tong_tien_san_pham, phi_van_chuyen, giam_gia, tong_thanh_toan,
                phuong_thuc_thanh_toan, trang_thai, trang_thai_thanh_toan,
                ghi_chu, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $stmtOrder->execute([
            $userId,
            $orderCode,
            $input['ho_ten'],
            $input['dien_thoai'],
            $input['email'],
            $fullAddress,
            $input['tinh_thanh'],
            $input['quan_huyen'],
            $input['phuong_xa'],
            $subtotal,
            $shippingFee,
            $discountAmount,
            $total,
            $input['phuong_thuc_thanh_toan'],
            'pending',
            $input['phuong_thuc_thanh_toan'] === 'COD' ? 'pending' : 'pending',
            $input['ghi_chu'] ?? null
        ]);
        
        $orderId = $db->lastInsertId();
        
        // Create order details - theo cấu trúc bảng chi_tiet_don_hang
        $stmtDetail = $db->prepare("
            INSERT INTO chi_tiet_don_hang (
                don_hang_id, san_pham_id, bien_the_id,
                ten_san_pham, thong_tin_bien_the, gia_ban, so_luong, thanh_tien
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmtUpdateStock = $db->prepare("
            UPDATE san_pham SET so_luong_ton = so_luong_ton - ? WHERE id = ?
        ");
        
        foreach ($cartItems as $item) {
            $price = $item['bien_the_gia'] ?? $item['gia_ban'];
            $itemTotal = $price * $item['so_luong'];
            
            // Insert order detail
            $stmtDetail->execute([
                $orderId,
                $item['san_pham_id'],
                $item['bien_the_id'],
                $item['ten_san_pham'],
                $item['ten_bien_the'], // thong_tin_bien_the
                $price,
                $item['so_luong'],
                $itemTotal
            ]);
            
            // Update stock
            $stmtUpdateStock->execute([
                $item['so_luong'],
                $item['san_pham_id']
            ]);
        }
        
        // Clear cart
        $stmtClearCart = $db->prepare("DELETE FROM gio_hang WHERE nguoi_dung_id = ?");
        $stmtClearCart->execute([$userId]);
        
        // Update coupon used count
        if ($couponId) {
            $db->prepare("UPDATE khuyen_mai SET used_count = used_count + 1 WHERE id = ?")->execute([$couponId]);
        }
        
        // Commit transaction
        $db->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Đặt hàng thành công',
            'order_code' => $orderCode,
            'order_id' => $orderId
        ]);
        
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log('Checkout Error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
    ]);
}
