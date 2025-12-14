<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isAdmin()) {
    redirect(SITE_URL . '/admin/login.php');
}

$db = getDB();
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$id) {
    redirect(SITE_URL . '/admin/users.php');
}

// Get user info
$stmt = $db->prepare("SELECT * FROM nguoi_dung WHERE id = ?");
$stmt->execute([$id]);
$user = $stmt->fetch();

if (!$user) {
    redirect(SITE_URL . '/admin/users.php');
}

// Get user orders
$orderStmt = $db->prepare("SELECT * FROM don_hang WHERE nguoi_dung_id = ? ORDER BY created_at DESC LIMIT 10");
$orderStmt->execute([$id]);
$orders = $orderStmt->fetchAll();

// Get statistics
$statsStmt = $db->prepare("
    SELECT 
        COUNT(*) as total_orders,
        COALESCE(SUM(tong_thanh_toan), 0) as total_spent,
        COALESCE(AVG(tong_thanh_toan), 0) as avg_order
    FROM don_hang WHERE nguoi_dung_id = ?
");
$statsStmt->execute([$id]);
$stats = $statsStmt->fetch();

$pageTitle = 'Chi tiết khách hàng';
include __DIR__ . '/includes/header.php';
?>

<div class="mb-6">
    <a href="<?php echo SITE_URL; ?>/admin/users.php" class="text-slate-600 hover:text-primary-600 transition">
        <i class="fas fa-arrow-left mr-2"></i>Quay lại danh sách
    </a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- User Info -->
    <div class="lg:col-span-1 space-y-6">
        <div class="card-hover bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
            <div class="text-center mb-6">
                <?php if (!empty($user['avt'])): ?>
                <img src="<?php echo UPLOAD_URL . '/' . $user['avt']; ?>" class="w-24 h-24 rounded-full object-cover mx-auto mb-4">
                <?php else: ?>
                <div class="w-24 h-24 bg-gradient-to-br from-primary-400 to-primary-600 rounded-full flex items-center justify-center mx-auto mb-4 text-white text-3xl font-bold">
                    <?php echo strtoupper(mb_substr($user['ho_ten'], 0, 1)); ?>
                </div>
                <?php endif; ?>
                <h2 class="text-xl font-bold text-slate-800"><?php echo htmlspecialchars($user['ho_ten']); ?></h2>
                <p class="text-slate-500 text-sm">ID: <?php echo $user['id']; ?></p>
            </div>
            
            <div class="space-y-4">
                <div class="flex items-center gap-3 p-3 bg-slate-50 rounded-xl">
                    <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-envelope text-blue-600"></i>
                    </div>
                    <div>
                        <p class="text-xs text-slate-500">Email</p>
                        <p class="text-sm font-medium text-slate-800"><?php echo htmlspecialchars($user['email']); ?></p>
                    </div>
                </div>
                
                <div class="flex items-center gap-3 p-3 bg-slate-50 rounded-xl">
                    <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-phone text-green-600"></i>
                    </div>
                    <div>
                        <p class="text-xs text-slate-500">Số điện thoại</p>
                        <p class="text-sm font-medium text-slate-800"><?php echo $user['so_dien_thoai'] ?: 'Chưa cập nhật'; ?></p>
                    </div>
                </div>
                
                <div class="flex items-center gap-3 p-3 bg-slate-50 rounded-xl">
                    <div class="w-10 h-10 bg-amber-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-map-marker-alt text-amber-600"></i>
                    </div>
                    <div>
                        <p class="text-xs text-slate-500">Địa chỉ</p>
                        <p class="text-sm font-medium text-slate-800"><?php echo $user['dia_chi'] ?: 'Chưa cập nhật'; ?></p>
                    </div>
                </div>
                
                <div class="flex items-center gap-3 p-3 bg-slate-50 rounded-xl">
                    <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-calendar text-purple-600"></i>
                    </div>
                    <div>
                        <p class="text-xs text-slate-500">Ngày đăng ký</p>
                        <p class="text-sm font-medium text-slate-800"><?php echo date('d/m/Y H:i', strtotime($user['created_at'])); ?></p>
                    </div>
                </div>
            </div>
            
            <div class="mt-6 flex gap-3">
                <a href="<?php echo SITE_URL; ?>/admin/user-form.php?id=<?php echo $user['id']; ?>" 
                   class="flex-1 bg-blue-50 hover:bg-blue-100 text-blue-600 px-4 py-2.5 rounded-xl font-medium text-center transition">
                    <i class="fas fa-edit mr-2"></i>Sửa
                </a>
                <a href="<?php echo SITE_URL; ?>/admin/users.php?delete=<?php echo $user['id']; ?>" 
                   onclick="return confirmDelete('Xóa khách hàng này và tất cả dữ liệu liên quan?')"
                   class="flex-1 bg-red-50 hover:bg-red-100 text-red-600 px-4 py-2.5 rounded-xl font-medium text-center transition">
                    <i class="fas fa-trash mr-2"></i>Xóa
                </a>
            </div>
        </div>
    </div>
    
    <!-- Stats & Orders -->
    <div class="lg:col-span-2 space-y-6">
        <!-- Statistics -->
        <div class="grid grid-cols-3 gap-4">
            <div class="card-hover bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl p-5 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-blue-100 text-sm">Tổng đơn hàng</p>
                        <p class="text-3xl font-bold mt-1"><?php echo number_format($stats['total_orders']); ?></p>
                    </div>
                    <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center">
                        <i class="fas fa-shopping-bag text-xl"></i>
                    </div>
                </div>
            </div>
            
            <div class="card-hover bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-2xl p-5 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-emerald-100 text-sm">Tổng chi tiêu</p>
                        <p class="text-2xl font-bold mt-1"><?php echo formatCurrency($stats['total_spent']); ?></p>
                    </div>
                    <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center">
                        <i class="fas fa-coins text-xl"></i>
                    </div>
                </div>
            </div>
            
            <div class="card-hover bg-gradient-to-br from-amber-500 to-orange-500 rounded-2xl p-5 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-amber-100 text-sm">TB/đơn hàng</p>
                        <p class="text-2xl font-bold mt-1"><?php echo formatCurrency($stats['avg_order']); ?></p>
                    </div>
                    <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center">
                        <i class="fas fa-chart-line text-xl"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Recent Orders -->
        <div class="card-hover bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="p-6 border-b border-slate-100">
                <h3 class="text-lg font-bold text-slate-800">Đơn hàng gần đây</h3>
            </div>
            
            <?php if (empty($orders)): ?>
            <div class="p-12 text-center">
                <div class="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-shopping-bag text-slate-400 text-2xl"></i>
                </div>
                <p class="text-slate-500">Chưa có đơn hàng nào</p>
            </div>
            <?php else: ?>
            <table class="w-full">
                <thead>
                    <tr class="bg-slate-50">
                        <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Mã đơn</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Ngày đặt</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Tổng tiền</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Trạng thái</th>
                        <th class="px-5 py-3 text-center text-xs font-semibold text-slate-500 uppercase">Chi tiết</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($orders as $order): ?>
                    <tr class="hover:bg-slate-50 transition">
                        <td class="px-5 py-4">
                            <span class="font-mono text-sm bg-slate-100 px-2 py-1 rounded"><?php echo $order['ma_don_hang']; ?></span>
                        </td>
                        <td class="px-5 py-4 text-sm text-slate-600">
                            <?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?>
                        </td>
                        <td class="px-5 py-4 font-bold text-slate-800">
                            <?php echo formatCurrency($order['tong_thanh_toan']); ?>
                        </td>
                        <td class="px-5 py-4">
                            <?php 
                            $status = $order['trang_thai'] ?? 'pending';
                            $statusClass = match($status) {
                                'delivered' => 'bg-emerald-100 text-emerald-700',
                                'shipping' => 'bg-blue-100 text-blue-700',
                                'cancelled' => 'bg-red-100 text-red-700',
                                'confirmed' => 'bg-cyan-100 text-cyan-700',
                                default => 'bg-amber-100 text-amber-700'
                            };
                            $statusText = match($status) {
                                'delivered' => 'Đã giao',
                                'shipping' => 'Đang giao',
                                'cancelled' => 'Đã hủy',
                                'confirmed' => 'Đã xác nhận',
                                default => 'Chờ xử lý'
                            };
                            ?>
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold <?php echo $statusClass; ?>">
                                <?php echo $statusText; ?>
                            </span>
                        </td>
                        <td class="px-5 py-4 text-center">
                            <a href="<?php echo SITE_URL; ?>/admin/order-detail.php?id=<?php echo $order['id']; ?>" 
                               class="w-9 h-9 inline-flex items-center justify-center rounded-lg bg-slate-100 text-slate-600 hover:bg-slate-200 transition">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
