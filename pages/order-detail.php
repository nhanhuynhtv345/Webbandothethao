<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isLoggedIn()) {
    redirect(SITE_URL . '/pages/login.php');
}

$db = getDB();
$orderId = intval($_GET['id'] ?? 0);

// Get order
$stmt = $db->prepare("SELECT * FROM don_hang WHERE id = ? AND nguoi_dung_id = ?");
$stmt->execute([$orderId, $_SESSION['user_id']]);
$order = $stmt->fetch();

if (!$order) {
    redirect(SITE_URL . '/pages/orders.php');
}

// Get order items
$itemsStmt = $db->prepare("
    SELECT ctdh.*, 
           (SELECT url FROM hinh_anh_san_pham WHERE san_pham_id = ctdh.san_pham_id AND is_primary = 1 LIMIT 1) as anh
    FROM chi_tiet_don_hang ctdh 
    WHERE ctdh.don_hang_id = ?
");
$itemsStmt->execute([$orderId]);
$items = $itemsStmt->fetchAll();

$pageTitle = 'Chi tiết đơn hàng #' . $order['ma_don_hang'];

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

$orderStatus = $order['trang_thai'] ?? 'pending';
$statusInfo = $statusLabels[$orderStatus] ?? $statusLabels['pending'];

// Timeline steps
$timelineSteps = [
    'pending' => ['label' => 'Đặt hàng', 'icon' => 'fa-shopping-cart'],
    'confirmed' => ['label' => 'Xác nhận', 'icon' => 'fa-check'],
    'processing' => ['label' => 'Đang xử lý', 'icon' => 'fa-cog'],
    'shipping' => ['label' => 'Đang giao', 'icon' => 'fa-truck'],
    'completed' => ['label' => 'Hoàn thành', 'icon' => 'fa-check-circle'],
];

$statusOrder = ['pending', 'confirmed', 'processing', 'shipping', 'completed'];
$currentIndex = array_search($orderStatus, $statusOrder);
if ($currentIndex === false) $currentIndex = -1;

include __DIR__ . '/../components/header.php';
?>

<!-- Breadcrumb -->
<div class="bg-gray-100 py-4">
    <div class="container mx-auto px-4">
        <nav class="flex items-center gap-2 text-sm">
            <a href="<?php echo SITE_URL; ?>/index.php" class="text-gray-600 hover:text-primary-600">Trang chủ</a>
            <i class="fas fa-chevron-right text-gray-400 text-xs"></i>
            <a href="<?php echo SITE_URL; ?>/pages/orders.php" class="text-gray-600 hover:text-primary-600">Đơn hàng</a>
            <i class="fas fa-chevron-right text-gray-400 text-xs"></i>
            <span class="text-gray-900"><?php echo $order['ma_don_hang']; ?></span>
        </nav>
    </div>
</div>

<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Đơn hàng <?php echo $order['ma_don_hang']; ?></h1>
            <p class="text-gray-600">Đặt ngày <?php echo formatDateTime($order['created_at']); ?></p>
        </div>
        <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-semibold <?php echo $statusInfo['class']; ?>">
            <i class="fas <?php echo $statusInfo['icon']; ?>"></i>
            <?php echo $statusInfo['label']; ?>
        </span>
    </div>
    
    <?php if ($orderStatus !== 'cancelled' && $orderStatus !== 'refunded'): ?>
    <!-- Order Timeline -->
    <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
        <h2 class="font-semibold text-gray-800 mb-6">Trạng thái đơn hàng</h2>
        <div class="flex items-center justify-between relative">
            <!-- Progress Line -->
            <div class="absolute top-5 left-0 right-0 h-1 bg-gray-200 -z-10"></div>
            <div class="absolute top-5 left-0 h-1 bg-primary-500 -z-10" 
                 style="width: <?php echo $currentIndex >= 0 ? ($currentIndex / (count($timelineSteps) - 1) * 100) : 0; ?>%"></div>
            
            <?php foreach ($timelineSteps as $key => $step): 
                $stepIndex = array_search($key, $statusOrder);
                $isCompleted = $stepIndex <= $currentIndex;
                $isCurrent = $stepIndex === $currentIndex;
            ?>
            <div class="flex flex-col items-center z-10">
                <div class="w-10 h-10 rounded-full flex items-center justify-center <?php echo $isCompleted ? 'bg-primary-500 text-white' : 'bg-gray-200 text-gray-400'; ?> <?php echo $isCurrent ? 'ring-4 ring-primary-200' : ''; ?>">
                    <i class="fas <?php echo $step['icon']; ?>"></i>
                </div>
                <span class="text-sm mt-2 <?php echo $isCompleted ? 'text-primary-600 font-medium' : 'text-gray-400'; ?>">
                    <?php echo $step['label']; ?>
                </span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Order Items -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b">
                    <h2 class="font-semibold text-gray-800">Sản phẩm đã đặt</h2>
                </div>
                <div class="divide-y">
                    <?php foreach ($items as $item): ?>
                    <div class="p-6 flex items-center gap-4">
                        <?php if (!empty($item['anh'])): ?>
                        <img src="<?php echo UPLOAD_URL . '/' . $item['anh']; ?>" 
                             alt="<?php echo htmlspecialchars($item['ten_san_pham']); ?>"
                             class="w-24 h-24 object-cover rounded-lg">
                        <?php else: ?>
                        <div class="w-24 h-24 bg-gray-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-image text-gray-400 text-2xl"></i>
                        </div>
                        <?php endif; ?>
                        <div class="flex-1">
                            <h3 class="font-medium text-gray-900"><?php echo htmlspecialchars($item['ten_san_pham']); ?></h3>
                            <?php if (!empty($item['thong_tin_bien_the'])): ?>
                            <p class="text-sm text-gray-500"><?php echo htmlspecialchars($item['thong_tin_bien_the']); ?></p>
                            <?php endif; ?>
                            <p class="text-sm text-gray-600 mt-1">
                                <?php echo formatCurrency($item['gia_ban']); ?> x <?php echo $item['so_luong']; ?>
                            </p>
                        </div>
                        <div class="text-right">
                            <span class="font-bold text-primary-600"><?php echo formatCurrency($item['thanh_tien']); ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Summary -->
                <div class="px-6 py-4 bg-gray-50 border-t">
                    <div class="space-y-2">
                        <div class="flex justify-between text-gray-600">
                            <span>Tạm tính:</span>
                            <span><?php echo formatCurrency($order['tong_tien_san_pham']); ?></span>
                        </div>
                        <div class="flex justify-between text-gray-600">
                            <span>Phí vận chuyển:</span>
                            <span><?php echo $order['phi_van_chuyen'] > 0 ? formatCurrency($order['phi_van_chuyen']) : '<span class="text-green-600">Miễn phí</span>'; ?></span>
                        </div>
                        <?php if ($order['giam_gia'] > 0): ?>
                        <div class="flex justify-between text-green-600">
                            <span>Giảm giá:</span>
                            <span>-<?php echo formatCurrency($order['giam_gia']); ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="flex justify-between text-lg font-bold pt-2 border-t">
                            <span>Tổng cộng:</span>
                            <span class="text-primary-600"><?php echo formatCurrency($order['tong_thanh_toan']); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Order Info -->
        <div class="space-y-6">
            <!-- Shipping Info -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h3 class="font-semibold text-gray-800 mb-4">
                    <i class="fas fa-truck text-primary-600 mr-2"></i>Thông tin giao hàng
                </h3>
                <div class="space-y-2 text-sm">
                    <p><strong>Người nhận:</strong> <?php echo htmlspecialchars($order['ho_ten']); ?></p>
                    <p><strong>Số điện thoại:</strong> <?php echo $order['so_dien_thoai']; ?></p>
                    <p><strong>Địa chỉ:</strong> <?php echo htmlspecialchars($order['dia_chi']); ?></p>
                </div>
            </div>
            
            <!-- Payment Info -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h3 class="font-semibold text-gray-800 mb-4">
                    <i class="fas fa-credit-card text-primary-600 mr-2"></i>Thanh toán
                </h3>
                <div class="space-y-2 text-sm">
                    <p>
                        <strong>Phương thức:</strong> 
                        <?php echo strtolower($order['phuong_thuc_thanh_toan'] ?? 'cod') === 'cod' ? 'Thanh toán khi nhận hàng' : 'Chuyển khoản ngân hàng'; ?>
                    </p>
                    <p>
                        <strong>Trạng thái:</strong>
                        <?php 
                        $paymentStatus = $order['trang_thai_thanh_toan'] ?? 'pending';
                        // Nếu đơn hàng đã hoàn thành hoặc đã giao thì coi như đã thanh toán
                        if (in_array($orderStatus, ['completed', 'delivered'])) {
                            $paymentStatus = 'paid';
                        }
                        $paymentLabels = [
                            'pending' => '<span class="text-yellow-600">Chờ thanh toán</span>',
                            'paid' => '<span class="text-green-600">Đã thanh toán</span>',
                            'failed' => '<span class="text-red-600">Thanh toán thất bại</span>',
                            'refunded' => '<span class="text-gray-600">Đã hoàn tiền</span>'
                        ];
                        echo $paymentLabels[$paymentStatus] ?? $paymentStatus;
                        ?>
                    </p>
                </div>
            </div>
            
            <?php if (!empty($order['ghi_chu'])): ?>
            <!-- Notes -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h3 class="font-semibold text-gray-800 mb-4">
                    <i class="fas fa-sticky-note text-primary-600 mr-2"></i>Ghi chú
                </h3>
                <p class="text-sm text-gray-600"><?php echo nl2br(htmlspecialchars($order['ghi_chu'])); ?></p>
            </div>
            <?php endif; ?>
            
            <!-- Invoice -->
            <?php 
            // Check if invoice exists
            $invoiceStmt = $db->prepare("SELECT id, ma_hoa_don, status FROM hoa_don WHERE don_hang_id = ?");
            $invoiceStmt->execute([$orderId]);
            $invoice = $invoiceStmt->fetch();
            
            // Show invoice section if order is completed/delivered or invoice exists
            if ($invoice || in_array($orderStatus, ['completed', 'delivered'])):
            ?>
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h3 class="font-semibold text-gray-800 mb-4">
                    <i class="fas fa-file-invoice text-primary-600 mr-2"></i>Hóa đơn
                </h3>
                <?php if ($invoice): ?>
                <div class="space-y-3">
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-600">Mã hóa đơn:</span>
                        <span class="font-semibold"><?php echo $invoice['ma_hoa_don']; ?></span>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-600">Trạng thái:</span>
                        <?php 
                        $invStatus = $invoice['status'];
                        $invLabels = [
                            'unpaid' => '<span class="text-yellow-600">Chưa thanh toán</span>',
                            'paid' => '<span class="text-green-600">Đã thanh toán</span>',
                            'partially_paid' => '<span class="text-blue-600">Thanh toán một phần</span>',
                            'cancelled' => '<span class="text-red-600">Đã hủy</span>'
                        ];
                        echo $invLabels[$invStatus] ?? $invStatus;
                        ?>
                    </div>
                    <a href="<?php echo SITE_URL; ?>/pages/invoice-view.php?id=<?php echo $invoice['id']; ?>" 
                       target="_blank"
                       class="block w-full px-4 py-2 bg-green-600 text-white rounded-lg font-medium hover:bg-green-700 transition text-center mt-3">
                        <i class="fas fa-eye mr-2"></i>Xem hóa đơn
                    </a>
                </div>
                <?php else: ?>
                <p class="text-sm text-gray-500">Hóa đơn sẽ được tạo sau khi đơn hàng hoàn tất.</p>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <!-- Actions -->
            <div class="space-y-3">
                <?php if ($orderStatus === 'pending'): ?>
                <button onclick="cancelOrder(<?php echo $order['id']; ?>)" 
                        class="w-full px-4 py-3 border border-red-500 text-red-500 rounded-lg font-medium hover:bg-red-50 transition">
                    <i class="fas fa-times mr-2"></i>Hủy đơn hàng
                </button>
                <?php endif; ?>
                
                <a href="<?php echo SITE_URL; ?>/pages/orders.php" 
                   class="block w-full px-4 py-3 border border-gray-300 text-gray-700 rounded-lg font-medium hover:bg-gray-50 transition text-center">
                    <i class="fas fa-arrow-left mr-2"></i>Quay lại danh sách
                </a>
            </div>
        </div>
    </div>
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
