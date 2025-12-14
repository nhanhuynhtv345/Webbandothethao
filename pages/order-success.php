<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

$pageTitle = 'Đặt hàng thành công';

// Get order code from URL
$orderCode = $_GET['code'] ?? '';

$order = null;
$orderItems = [];

if ($orderCode && isLoggedIn()) {
    $db = getDB();
    
    // Get order info
    $stmt = $db->prepare("SELECT * FROM don_hang WHERE ma_don_hang = ? AND nguoi_dung_id = ?");
    $stmt->execute([$orderCode, $_SESSION['user_id']]);
    $order = $stmt->fetch();
    
    if ($order) {
        // Get order items
        $itemsStmt = $db->prepare("SELECT * FROM chi_tiet_don_hang WHERE don_hang_id = ?");
        $itemsStmt->execute([$order['id']]);
        $orderItems = $itemsStmt->fetchAll();
    }
}

include __DIR__ . '/../components/header.php';
?>

<div class="min-h-[60vh] flex items-center justify-center py-12">
    <div class="max-w-2xl w-full mx-auto px-4">
        <?php if ($order): ?>
        <!-- Success Card -->
        <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
            <!-- Header -->
            <div class="bg-gradient-to-r from-green-500 to-emerald-600 px-8 py-10 text-center text-white">
                <div class="w-20 h-20 bg-white/20 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-check text-4xl"></i>
                </div>
                <h1 class="text-3xl font-bold mb-2">Đặt hàng thành công!</h1>
                <p class="text-green-100">Cảm ơn bạn đã mua hàng tại <?php echo SITE_NAME; ?></p>
            </div>
            
            <!-- Order Info -->
            <div class="p-8">
                <div class="bg-gray-50 rounded-xl p-6 mb-6">
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-gray-600">Mã đơn hàng:</span>
                        <span class="text-xl font-bold text-primary-600"><?php echo $order['ma_don_hang']; ?></span>
                    </div>
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-gray-600">Ngày đặt:</span>
                        <span class="font-medium"><?php echo formatDateTime($order['created_at']); ?></span>
                    </div>
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-gray-600">Phương thức thanh toán:</span>
                        <span class="font-medium">
                            <?php echo strtolower($order['phuong_thuc_thanh_toan'] ?? 'cod') === 'cod' ? 'Thanh toán khi nhận hàng' : 'Chuyển khoản ngân hàng'; ?>
                        </span>
                    </div>
                    <div class="flex items-center justify-between pt-4 border-t">
                        <span class="text-gray-600">Tổng thanh toán:</span>
                        <span class="text-2xl font-bold text-primary-600"><?php echo formatCurrency($order['tong_thanh_toan'] ?? 0); ?></span>
                    </div>
                </div>
                
                <!-- Order Items -->
                <h3 class="font-semibold text-gray-800 mb-4">Chi tiết đơn hàng</h3>
                <div class="space-y-3 mb-6">
                    <?php foreach ($orderItems as $item): ?>
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                        <div>
                            <p class="font-medium text-gray-800"><?php echo htmlspecialchars($item['ten_san_pham']); ?></p>
                            <?php if (!empty($item['ten_bien_the'])): ?>
                            <p class="text-sm text-gray-500"><?php echo htmlspecialchars($item['ten_bien_the']); ?></p>
                            <?php endif; ?>
                            <p class="text-sm text-gray-500">Số lượng: <?php echo $item['so_luong']; ?></p>
                        </div>
                        <span class="font-semibold"><?php echo formatCurrency($item['thanh_tien'] ?? 0); ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Shipping Info -->
                <div class="bg-blue-50 rounded-xl p-4 mb-6">
                    <h4 class="font-semibold text-blue-800 mb-2">
                        <i class="fas fa-truck mr-2"></i>Thông tin giao hàng
                    </h4>
                    <p class="text-blue-700"><?php echo htmlspecialchars($order['ho_ten'] ?? ''); ?></p>
                    <p class="text-blue-600 text-sm"><?php echo $order['so_dien_thoai'] ?? ''; ?></p>
                    <p class="text-blue-600 text-sm"><?php echo htmlspecialchars($order['dia_chi'] ?? ''); ?></p>
                </div>
                
                <?php if (strtolower($order['phuong_thuc_thanh_toan'] ?? '') === 'bank_transfer'): ?>
                <!-- Bank Transfer Info -->
                <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 mb-6">
                    <h4 class="font-semibold text-amber-800 mb-2">
                        <i class="fas fa-university mr-2"></i>Thông tin chuyển khoản
                    </h4>
                    <p class="text-amber-700 text-sm mb-2">Vui lòng chuyển khoản theo thông tin sau:</p>
                    <div class="bg-white rounded-lg p-3 text-sm">
                        <p><strong>Ngân hàng:</strong> MB Bank</p>
                        <p><strong>Số tài khoản:</strong> 677898888</p>
                        <p><strong>Chủ tài khoản:</strong> Huỳnh Quốc Nhân</p>
                        <p><strong>Số tiền:</strong> <?php echo formatCurrency($order['tong_thanh_toan'] ?? 0); ?></p>
                        <p><strong>Nội dung:</strong> <?php echo $order['ma_don_hang']; ?></p>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Actions -->
                <div class="flex gap-4">
                    <a href="<?php echo SITE_URL; ?>/pages/account.php" 
                       class="flex-1 bg-primary-600 text-white py-3 rounded-lg font-semibold text-center hover:bg-primary-700 transition">
                        <i class="fas fa-user mr-2"></i>Xem đơn hàng
                    </a>
                    <a href="<?php echo SITE_URL; ?>/pages/products.php" 
                       class="flex-1 border border-gray-300 text-gray-700 py-3 rounded-lg font-semibold text-center hover:bg-gray-50 transition">
                        <i class="fas fa-shopping-bag mr-2"></i>Tiếp tục mua
                    </a>
                </div>
            </div>
        </div>
        <?php else: ?>
        <!-- Error/Not Found -->
        <div class="text-center py-12">
            <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-search text-gray-400 text-3xl"></i>
            </div>
            <h2 class="text-2xl font-bold text-gray-800 mb-2">Không tìm thấy đơn hàng</h2>
            <p class="text-gray-600 mb-6">Đơn hàng không tồn tại hoặc bạn không có quyền xem.</p>
            <a href="<?php echo SITE_URL; ?>" class="inline-block bg-primary-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-primary-700 transition">
                Về trang chủ
            </a>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../components/footer.php'; ?>
