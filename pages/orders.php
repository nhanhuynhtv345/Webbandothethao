<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isLoggedIn()) {
    redirect(SITE_URL . '/pages/login.php');
}

$pageTitle = 'Đơn hàng của tôi';
$db = getDB();
$user = getCurrentUser();

// Filters
$status = $_GET['status'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 10;

// Build query
$where = ['nguoi_dung_id = ?'];
$params = [$_SESSION['user_id']];

if ($status) {
    $where[] = "trang_thai = ?";
    $params[] = $status;
}

$whereClause = implode(' AND ', $where);

// Count total
$countStmt = $db->prepare("SELECT COUNT(*) FROM don_hang WHERE $whereClause");
$countStmt->execute($params);
$total = $countStmt->fetchColumn();
$totalPages = max(1, ceil($total / $perPage));
$offset = ($page - 1) * $perPage;

// Get orders
$sql = "SELECT * FROM don_hang WHERE $whereClause ORDER BY created_at DESC LIMIT $perPage OFFSET $offset";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();

$statusLabels = [
    'pending' => ['label' => 'Chờ xác nhận', 'class' => 'bg-yellow-100 text-yellow-700', 'icon' => 'fa-clock'],
    'confirmed' => ['label' => 'Đã xác nhận', 'class' => 'bg-blue-100 text-blue-700', 'icon' => 'fa-check'],
    'processing' => ['label' => 'Đang xử lý', 'class' => 'bg-indigo-100 text-indigo-700', 'icon' => 'fa-cog'],
    'shipping' => ['label' => 'Đang giao hàng', 'class' => 'bg-purple-100 text-purple-700', 'icon' => 'fa-truck'],
    'delivered' => ['label' => 'Đã giao hàng', 'class' => 'bg-teal-100 text-teal-700', 'icon' => 'fa-box'],
    'completed' => ['label' => 'Hoàn thành', 'class' => 'bg-green-100 text-green-700', 'icon' => 'fa-check-circle'],
    'cancelled' => ['label' => 'Đã hủy', 'class' => 'bg-red-100 text-red-700', 'icon' => 'fa-times-circle'],
    'refunded' => ['label' => 'Đã hoàn tiền', 'class' => 'bg-gray-100 text-gray-700', 'icon' => 'fa-undo'],
];

include __DIR__ . '/../components/header.php';
?>

<!-- Breadcrumb -->
<div class="bg-gray-100 py-4">
    <div class="container mx-auto px-4">
        <nav class="flex items-center gap-2 text-sm">
            <a href="<?php echo SITE_URL; ?>/index.php" class="text-gray-600 hover:text-primary-600">Trang chủ</a>
            <i class="fas fa-chevron-right text-gray-400 text-xs"></i>
            <a href="<?php echo SITE_URL; ?>/pages/account.php" class="text-gray-600 hover:text-primary-600">Tài khoản</a>
            <i class="fas fa-chevron-right text-gray-400 text-xs"></i>
            <span class="text-gray-900">Đơn hàng của tôi</span>
        </nav>
    </div>
</div>

<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold text-gray-900 mb-6">Đơn hàng của tôi</h1>
    
    <!-- Filter Tabs -->
    <div class="bg-white rounded-lg shadow-sm mb-6 overflow-x-auto">
        <div class="flex border-b">
            <a href="?status=" class="px-6 py-4 font-medium whitespace-nowrap <?php echo !$status ? 'text-primary-600 border-b-2 border-primary-600' : 'text-gray-600 hover:text-primary-600'; ?>">
                Tất cả
            </a>
            <a href="?status=pending" class="px-6 py-4 font-medium whitespace-nowrap <?php echo $status === 'pending' ? 'text-primary-600 border-b-2 border-primary-600' : 'text-gray-600 hover:text-primary-600'; ?>">
                Chờ xác nhận
            </a>
            <a href="?status=shipping" class="px-6 py-4 font-medium whitespace-nowrap <?php echo $status === 'shipping' ? 'text-primary-600 border-b-2 border-primary-600' : 'text-gray-600 hover:text-primary-600'; ?>">
                Đang giao
            </a>
            <a href="?status=completed" class="px-6 py-4 font-medium whitespace-nowrap <?php echo $status === 'completed' ? 'text-primary-600 border-b-2 border-primary-600' : 'text-gray-600 hover:text-primary-600'; ?>">
                Hoàn thành
            </a>
            <a href="?status=cancelled" class="px-6 py-4 font-medium whitespace-nowrap <?php echo $status === 'cancelled' ? 'text-primary-600 border-b-2 border-primary-600' : 'text-gray-600 hover:text-primary-600'; ?>">
                Đã hủy
            </a>
        </div>
    </div>
    
    <!-- Orders List -->
    <?php if (empty($orders)): ?>
    <div class="bg-white rounded-lg shadow-sm p-12 text-center">
        <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <i class="fas fa-shopping-bag text-gray-400 text-4xl"></i>
        </div>
        <h3 class="text-xl font-bold text-gray-900 mb-2">Chưa có đơn hàng nào</h3>
        <p class="text-gray-600 mb-6">Hãy bắt đầu mua sắm ngay!</p>
        <a href="<?php echo SITE_URL; ?>/pages/products.php" class="inline-block bg-primary-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-primary-700 transition">
            <i class="fas fa-shopping-cart mr-2"></i>Mua sắm ngay
        </a>
    </div>
    <?php else: ?>
    <div class="space-y-4">
        <?php foreach ($orders as $order): 
            $orderStatus = $order['trang_thai'] ?? 'pending';
            $statusInfo = $statusLabels[$orderStatus] ?? $statusLabels['pending'];
            
            // Get order items
            $itemsStmt = $db->prepare("
                SELECT ctdh.*, 
                       (SELECT url FROM hinh_anh_san_pham WHERE san_pham_id = ctdh.san_pham_id AND is_primary = 1 LIMIT 1) as anh
                FROM chi_tiet_don_hang ctdh 
                WHERE ctdh.don_hang_id = ? 
                LIMIT 3
            ");
            $itemsStmt->execute([$order['id']]);
            $items = $itemsStmt->fetchAll();
            
            // Count total items
            $countItemsStmt = $db->prepare("SELECT COUNT(*) FROM chi_tiet_don_hang WHERE don_hang_id = ?");
            $countItemsStmt->execute([$order['id']]);
            $totalItems = $countItemsStmt->fetchColumn();
        ?>
        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
            <!-- Order Header -->
            <div class="px-6 py-4 bg-gray-50 border-b flex items-center justify-between flex-wrap gap-4">
                <div class="flex items-center gap-4">
                    <span class="font-bold text-gray-800"><?php echo $order['ma_don_hang']; ?></span>
                    <span class="text-sm text-gray-500"><?php echo formatDateTime($order['created_at']); ?></span>
                </div>
                <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-sm font-medium <?php echo $statusInfo['class']; ?>">
                    <i class="fas <?php echo $statusInfo['icon']; ?>"></i>
                    <?php echo $statusInfo['label']; ?>
                </span>
            </div>
            
            <!-- Order Items -->
            <div class="p-6">
                <div class="space-y-4">
                    <?php foreach ($items as $item): ?>
                    <div class="flex items-center gap-4">
                        <?php if (!empty($item['anh'])): ?>
                        <img src="<?php echo UPLOAD_URL . '/' . $item['anh']; ?>" 
                             alt="<?php echo htmlspecialchars($item['ten_san_pham']); ?>"
                             class="w-20 h-20 object-cover rounded-lg">
                        <?php else: ?>
                        <div class="w-20 h-20 bg-gray-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-image text-gray-400 text-2xl"></i>
                        </div>
                        <?php endif; ?>
                        <div class="flex-1 min-w-0">
                            <h4 class="font-medium text-gray-900 truncate"><?php echo htmlspecialchars($item['ten_san_pham']); ?></h4>
                            <?php if (!empty($item['thong_tin_bien_the'])): ?>
                            <p class="text-sm text-gray-500"><?php echo htmlspecialchars($item['thong_tin_bien_the']); ?></p>
                            <?php endif; ?>
                            <p class="text-sm text-gray-600">x<?php echo $item['so_luong']; ?></p>
                        </div>
                        <div class="text-right">
                            <span class="font-semibold text-primary-600"><?php echo formatCurrency($item['thanh_tien']); ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    
                    <?php if ($totalItems > 3): ?>
                    <p class="text-sm text-gray-500 text-center">
                        và <?php echo $totalItems - 3; ?> sản phẩm khác...
                    </p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Order Footer -->
            <div class="px-6 py-4 bg-gray-50 border-t flex items-center justify-between flex-wrap gap-4">
                <div>
                    <span class="text-gray-600">Tổng tiền:</span>
                    <span class="text-xl font-bold text-primary-600 ml-2"><?php echo formatCurrency($order['tong_thanh_toan']); ?></span>
                </div>
                <div class="flex gap-3">
                    <a href="<?php echo SITE_URL; ?>/pages/order-detail.php?id=<?php echo $order['id']; ?>" 
                       class="px-4 py-2 border border-primary-600 text-primary-600 rounded-lg font-medium hover:bg-primary-50 transition">
                        Xem chi tiết
                    </a>
                    <?php if ($orderStatus === 'pending'): ?>
                    <button onclick="cancelOrder(<?php echo $order['id']; ?>)" 
                            class="px-4 py-2 border border-red-500 text-red-500 rounded-lg font-medium hover:bg-red-50 transition">
                        Hủy đơn
                    </button>
                    <?php endif; ?>
                    <?php if ($orderStatus === 'completed'): ?>
                    <button class="px-4 py-2 bg-primary-600 text-white rounded-lg font-medium hover:bg-primary-700 transition">
                        Mua lại
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <div class="flex items-center justify-center gap-2 mt-8">
        <?php if ($page > 1): ?>
        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" 
           class="px-4 py-2 border rounded-lg hover:bg-gray-50">
            <i class="fas fa-chevron-left"></i>
        </a>
        <?php endif; ?>
        
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" 
           class="px-4 py-2 rounded-lg <?php echo $i === $page ? 'bg-primary-600 text-white' : 'border hover:bg-gray-50'; ?>">
            <?php echo $i; ?>
        </a>
        <?php endfor; ?>
        
        <?php if ($page < $totalPages): ?>
        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" 
           class="px-4 py-2 border rounded-lg hover:bg-gray-50">
            <i class="fas fa-chevron-right"></i>
        </a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</div>

<script>
function cancelOrder(orderId) {
    if (confirm('Bạn có chắc muốn hủy đơn hàng này?')) {
        fetch('<?php echo SITE_URL; ?>/api/cancel-order.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ order_id: orderId })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert('Đã hủy đơn hàng thành công!');
                location.reload();
            } else {
                alert('Lỗi: ' + data.message);
            }
        });
    }
}
</script>

<?php include __DIR__ . '/../components/footer.php'; ?>
