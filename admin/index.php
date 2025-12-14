<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

// Redirect to login if not admin
if (!isAdmin()) {
    redirect(SITE_URL . '/admin/login.php');
}

$db = getDB();

// Get statistics - sử dụng try-catch để xử lý các bảng có thể chưa tồn tại
try {
    $totalProducts = $db->query("SELECT COUNT(*) FROM san_pham")->fetchColumn();
} catch (Exception $e) {
    $totalProducts = 0;
}

try {
    $totalOrders = $db->query("SELECT COUNT(*) FROM don_hang")->fetchColumn();
    // Tính doanh thu từ đơn hàng đã hoàn thành (delivered hoặc completed)
    $totalRevenue = $db->query("SELECT COALESCE(SUM(tong_thanh_toan), 0) FROM don_hang WHERE trang_thai IN ('delivered', 'completed')")->fetchColumn();
    
    // Nếu không có đơn hoàn thành, tính từ tất cả đơn không bị hủy
    if ($totalRevenue == 0) {
        $totalRevenue = $db->query("SELECT COALESCE(SUM(tong_thanh_toan), 0) FROM don_hang WHERE trang_thai NOT IN ('cancelled', 'refunded')")->fetchColumn();
    }
} catch (Exception $e) {
    $totalOrders = 0;
    $totalRevenue = 0;
}

try {
    $totalUsers = $db->query("SELECT COUNT(*) FROM nguoi_dung")->fetchColumn();
} catch (Exception $e) {
    $totalUsers = 0;
}

// Recent orders
try {
    $recentOrders = $db->query("
        SELECT dh.*, nd.ho_ten 
        FROM don_hang dh 
        LEFT JOIN nguoi_dung nd ON dh.nguoi_dung_id = nd.id 
        ORDER BY dh.created_at DESC LIMIT 5
    ")->fetchAll();
} catch (Exception $e) {
    $recentOrders = [];
}

// Low stock products
try {
    $lowStock = $db->query("SELECT * FROM san_pham WHERE so_luong_ton <= 10 AND trang_thai = 'active' ORDER BY so_luong_ton ASC LIMIT 5")->fetchAll();
} catch (Exception $e) {
    $lowStock = [];
}

$pageTitle = 'Dashboard';
include __DIR__ . '/includes/header.php';
?>

<!-- Statistics Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <!-- Products Card -->
    <div class="card-hover stat-card bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl shadow-lg p-6 text-white">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-blue-100 text-sm font-medium">Sản phẩm</p>
                <p class="text-3xl font-bold mt-2"><?php echo number_format($totalProducts); ?></p>
                <p class="text-blue-200 text-xs mt-2">
                    <i class="fas fa-arrow-up mr-1"></i>Tổng số sản phẩm
                </p>
            </div>
            <div class="w-14 h-14 bg-white/20 rounded-xl flex items-center justify-center">
                <i class="fas fa-box text-2xl"></i>
            </div>
        </div>
    </div>
    
    <!-- Orders Card -->
    <div class="card-hover stat-card bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-2xl shadow-lg p-6 text-white">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-emerald-100 text-sm font-medium">Đơn hàng</p>
                <p class="text-3xl font-bold mt-2"><?php echo number_format($totalOrders); ?></p>
                <p class="text-emerald-200 text-xs mt-2">
                    <i class="fas fa-shopping-bag mr-1"></i>Tổng đơn hàng
                </p>
            </div>
            <div class="w-14 h-14 bg-white/20 rounded-xl flex items-center justify-center">
                <i class="fas fa-shopping-cart text-2xl"></i>
            </div>
        </div>
    </div>
    
    <!-- Customers Card -->
    <div class="card-hover stat-card bg-gradient-to-br from-violet-500 to-violet-600 rounded-2xl shadow-lg p-6 text-white">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-violet-100 text-sm font-medium">Khách hàng</p>
                <p class="text-3xl font-bold mt-2"><?php echo number_format($totalUsers); ?></p>
                <p class="text-violet-200 text-xs mt-2">
                    <i class="fas fa-user-plus mr-1"></i>Đã đăng ký
                </p>
            </div>
            <div class="w-14 h-14 bg-white/20 rounded-xl flex items-center justify-center">
                <i class="fas fa-users text-2xl"></i>
            </div>
        </div>
    </div>
    
    <!-- Revenue Card -->
    <div class="card-hover stat-card bg-gradient-to-br from-amber-500 to-orange-500 rounded-2xl shadow-lg p-6 text-white">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-amber-100 text-sm font-medium">Doanh thu</p>
                <p class="text-2xl font-bold mt-2"><?php echo formatCurrency($totalRevenue); ?></p>
                <p class="text-amber-200 text-xs mt-2">
                    <i class="fas fa-chart-line mr-1"></i>Tổng doanh thu
                </p>
            </div>
            <div class="w-14 h-14 bg-white/20 rounded-xl flex items-center justify-center">
                <i class="fas fa-coins text-2xl"></i>
            </div>
        </div>
    </div>
</div>

<!-- Content Grid -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Recent Orders -->
    <div class="card-hover bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="p-6 border-b border-slate-100 bg-gradient-to-r from-slate-50 to-white">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-emerald-100 rounded-xl flex items-center justify-center">
                        <i class="fas fa-receipt text-emerald-600"></i>
                    </div>
                    <h3 class="text-lg font-bold text-slate-800">Đơn hàng gần đây</h3>
                </div>
                <a href="<?php echo SITE_URL; ?>/admin/orders.php" class="text-sm text-primary-600 hover:text-primary-700 font-medium">
                    Xem tất cả <i class="fas fa-arrow-right ml-1"></i>
                </a>
            </div>
        </div>
        <div class="p-6">
            <?php if (empty($recentOrders)): ?>
                <div class="text-center py-8">
                    <div class="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-inbox text-slate-400 text-2xl"></i>
                    </div>
                    <p class="text-slate-500">Chưa có đơn hàng nào</p>
                </div>
            <?php else: ?>
                <div class="space-y-3">
                    <?php foreach ($recentOrders as $order): ?>
                    <div class="flex items-center justify-between p-4 bg-slate-50 hover:bg-slate-100 rounded-xl transition cursor-pointer">
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 bg-white rounded-lg shadow-sm flex items-center justify-center">
                                <i class="fas fa-shopping-bag text-slate-400"></i>
                            </div>
                            <div>
                                <p class="font-semibold text-slate-800"><?php echo $order['ma_don_hang']; ?></p>
                                <p class="text-sm text-slate-500"><?php echo $order['ho_ten'] ?? 'Khách hàng'; ?></p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="font-bold text-slate-800"><?php echo formatCurrency($order['tong_thanh_toan'] ?? $order['tong_tien'] ?? 0); ?></p>
                            <?php $status = $order['trang_thai_don_hang'] ?? $order['trang_thai'] ?? 'pending'; ?>
                            <?php 
                            $statusClass = match($status) {
                                'completed' => 'bg-emerald-100 text-emerald-700',
                                'shipping' => 'bg-blue-100 text-blue-700',
                                'cancelled' => 'bg-red-100 text-red-700',
                                default => 'bg-amber-100 text-amber-700'
                            };
                            ?>
                            <span class="text-xs px-2.5 py-1 rounded-full font-medium <?php echo $statusClass; ?>">
                                <?php echo $status; ?>
                            </span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Low Stock -->
    <div class="card-hover bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="p-6 border-b border-slate-100 bg-gradient-to-r from-slate-50 to-white">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-red-100 rounded-xl flex items-center justify-center">
                        <i class="fas fa-exclamation-triangle text-red-600"></i>
                    </div>
                    <h3 class="text-lg font-bold text-slate-800">Sắp hết hàng</h3>
                </div>
                <a href="<?php echo SITE_URL; ?>/admin/products.php" class="text-sm text-primary-600 hover:text-primary-700 font-medium">
                    Xem tất cả <i class="fas fa-arrow-right ml-1"></i>
                </a>
            </div>
        </div>
        <div class="p-6">
            <?php if (empty($lowStock)): ?>
                <div class="text-center py-8">
                    <div class="w-16 h-16 bg-emerald-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-check text-emerald-500 text-2xl"></i>
                    </div>
                    <p class="text-slate-500">Tất cả sản phẩm đều đủ hàng</p>
                </div>
            <?php else: ?>
                <div class="space-y-3">
                    <?php foreach ($lowStock as $product): ?>
                    <div class="flex items-center justify-between p-4 bg-slate-50 hover:bg-slate-100 rounded-xl transition">
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 bg-white rounded-lg shadow-sm flex items-center justify-center">
                                <i class="fas fa-box text-slate-400"></i>
                            </div>
                            <div>
                                <p class="font-semibold text-slate-800 line-clamp-1"><?php echo htmlspecialchars($product['ten_san_pham']); ?></p>
                                <p class="text-sm text-slate-500"><?php echo $product['ma_san_pham']; ?></p>
                            </div>
                        </div>
                        <span class="px-3 py-1.5 bg-red-100 text-red-700 rounded-full text-sm font-bold">
                            <?php echo $product['so_luong_ton']; ?> <span class="font-normal">còn</span>
                        </span>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
