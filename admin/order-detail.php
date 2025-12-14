<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isAdmin()) {
    redirect(SITE_URL . '/admin/login.php');
}

$db = getDB();
$id = intval($_GET['id'] ?? 0);

if (!$id) {
    redirect(SITE_URL . '/admin/orders.php');
}

// Get order
$stmt = $db->prepare("SELECT * FROM don_hang WHERE id = ?");
$stmt->execute([$id]);
$order = $stmt->fetch();

if (!$order) {
    redirect(SITE_URL . '/admin/orders.php');
}

// Get order items
$itemsStmt = $db->prepare("
    SELECT ctdh.*, sp.ma_san_pham,
           (SELECT url FROM hinh_anh_san_pham WHERE san_pham_id = sp.id AND is_primary = 1 LIMIT 1) as anh
    FROM chi_tiet_don_hang ctdh
    LEFT JOIN san_pham sp ON ctdh.san_pham_id = sp.id
    WHERE ctdh.don_hang_id = ?
");
$itemsStmt->execute([$id]);
$items = $itemsStmt->fetchAll();

$statusLabels = [
    'pending' => ['label' => 'Chờ xử lý', 'class' => 'bg-yellow-100 text-yellow-600'],
    'confirmed' => ['label' => 'Đã xác nhận', 'class' => 'bg-blue-100 text-blue-600'],
    'processing' => ['label' => 'Đang xử lý', 'class' => 'bg-indigo-100 text-indigo-600'],
    'shipping' => ['label' => 'Đang giao', 'class' => 'bg-purple-100 text-purple-600'],
    'delivered' => ['label' => 'Đã giao', 'class' => 'bg-teal-100 text-teal-600'],
    'completed' => ['label' => 'Hoàn thành', 'class' => 'bg-green-100 text-green-600'],
    'cancelled' => ['label' => 'Đã hủy', 'class' => 'bg-red-100 text-red-600'],
    'refunded' => ['label' => 'Đã hoàn tiền', 'class' => 'bg-gray-100 text-gray-600'],
];

// Thứ tự trạng thái (chỉ được tiến lên, không được quay lại)
$statusOrder = ['pending', 'confirmed', 'processing', 'shipping', 'delivered', 'completed'];

// Hàm lấy các trạng thái có thể chuyển đến từ trạng thái hiện tại
function getAllowedStatuses($currentStatus, $statusLabels, $statusOrder) {
    $currentIndex = array_search($currentStatus, $statusOrder);
    if ($currentIndex === false) {
        return [];
    }
    
    $allowed = [];
    foreach ($statusOrder as $index => $status) {
        if ($index >= $currentIndex) {
            $allowed[$status] = $statusLabels[$status];
        }
    }
    // Luôn cho phép hủy đơn (trừ khi đã giao hoặc hoàn thành)
    if (!in_array($currentStatus, ['delivered', 'completed'])) {
        $allowed['cancelled'] = $statusLabels['cancelled'];
    }
    return $allowed;
}

$pageTitle = 'Chi tiết đơn hàng #' . $order['ma_don_hang'];
include __DIR__ . '/includes/header.php';
?>

<?php
// Check if invoice exists for this order
$invoiceStmt = $db->prepare("SELECT id, ma_hoa_don FROM hoa_don WHERE don_hang_id = ?");
$invoiceStmt->execute([$id]);
$existingInvoice = $invoiceStmt->fetch();
?>

<div class="mb-4 flex items-center justify-between">
    <a href="<?php echo SITE_URL; ?>/admin/orders.php" class="text-blue-600 hover:text-blue-800">
        <i class="fas fa-arrow-left mr-1"></i>Quay lại danh sách
    </a>
    <div class="flex gap-2">
        <?php if ($existingInvoice): ?>
        <a href="<?php echo SITE_URL; ?>/admin/invoice-detail.php?id=<?php echo $existingInvoice['id']; ?>" 
           class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
            <i class="fas fa-file-invoice mr-2"></i>Xem hóa đơn <?php echo $existingInvoice['ma_hoa_don']; ?>
        </a>
        <?php else: ?>
        <form method="POST" action="<?php echo SITE_URL; ?>/admin/invoices.php">
            <input type="hidden" name="create_invoice" value="1">
            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                <i class="fas fa-file-invoice mr-2"></i>Tạo hóa đơn
            </button>
        </form>
        <?php endif; ?>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Order Info -->
    <div class="lg:col-span-2 space-y-6">
        <!-- Items -->
        <div class="bg-white rounded-lg shadow">
            <div class="p-4 border-b">
                <h3 class="font-semibold">Sản phẩm đặt hàng</h3>
            </div>
            <div class="p-4">
                <table class="w-full">
                    <thead>
                        <tr class="text-left text-sm text-gray-500">
                            <th class="pb-3">Sản phẩm</th>
                            <th class="pb-3 text-center">SL</th>
                            <th class="pb-3 text-right">Đơn giá</th>
                            <th class="pb-3 text-right">Thành tiền</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <?php foreach ($items as $item): ?>
                        <tr>
                            <td class="py-3">
                                <div class="flex items-center gap-3">
                                    <?php if (!empty($item['anh'])): ?>
                                    <img src="<?php echo UPLOAD_URL . '/' . $item['anh']; ?>" class="w-12 h-12 object-cover rounded">
                                    <?php else: ?>
                                    <div class="w-12 h-12 bg-gray-200 rounded flex items-center justify-center">
                                        <i class="fas fa-image text-gray-400"></i>
                                    </div>
                                    <?php endif; ?>
                                    <div>
                                        <p class="font-medium"><?php echo htmlspecialchars($item['ten_san_pham']); ?></p>
                                        <?php if (!empty($item['thong_tin_bien_the'])): ?>
                                        <p class="text-sm text-gray-500"><?php echo htmlspecialchars($item['thong_tin_bien_the']); ?></p>
                                        <?php endif; ?>
                                        <p class="text-sm text-gray-400"><?php echo $item['ma_san_pham'] ?? ''; ?></p>
                                    </div>
                                </div>
                            </td>
                            <td class="py-3 text-center"><?php echo $item['so_luong']; ?></td>
                            <td class="py-3 text-right"><?php echo formatCurrency($item['gia_ban']); ?></td>
                            <td class="py-3 text-right font-semibold"><?php echo formatCurrency($item['thanh_tien']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Summary -->
        <div class="bg-white rounded-lg shadow p-4">
            <div class="space-y-2">
                <div class="flex justify-between">
                    <span class="text-gray-600">Tạm tính:</span>
                    <span><?php echo formatCurrency($order['tong_tien_san_pham']); ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Phí vận chuyển:</span>
                    <span><?php echo formatCurrency($order['phi_van_chuyen']); ?></span>
                </div>
                <?php if ($order['giam_gia'] > 0): ?>
                <div class="flex justify-between text-green-600">
                    <span>Giảm giá:</span>
                    <span>-<?php echo formatCurrency($order['giam_gia']); ?></span>
                </div>
                <?php endif; ?>
                <div class="flex justify-between text-lg font-bold pt-2 border-t">
                    <span>Tổng cộng:</span>
                    <span class="text-blue-600"><?php echo formatCurrency($order['tong_thanh_toan']); ?></span>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Sidebar -->
    <div class="space-y-6">
        <!-- Status -->
        <div class="bg-white rounded-lg shadow p-4">
            <h3 class="font-semibold mb-3">Trạng thái đơn hàng</h3>
            <?php $currentStatus = $order['trang_thai'] ?? 'pending'; ?>
            <?php $isLocked = in_array($currentStatus, ['completed', 'cancelled', 'refunded']); ?>
            <span class="px-3 py-1 rounded text-sm <?php echo $statusLabels[$currentStatus]['class'] ?? 'bg-gray-100'; ?>">
                <?php echo $statusLabels[$currentStatus]['label'] ?? $currentStatus; ?>
            </span>
            
            <?php if ($isLocked): ?>
            <div class="mt-4 p-3 bg-gray-100 rounded-lg text-sm text-gray-600">
                <i class="fas fa-lock mr-1"></i>
                Đơn hàng đã <?php echo $currentStatus === 'completed' ? 'hoàn thành' : ($currentStatus === 'cancelled' ? 'hủy' : 'hoàn tiền'); ?>, không thể chỉnh sửa trạng thái.
            </div>
            <?php else: ?>
            <?php $allowedStatuses = getAllowedStatuses($currentStatus, $statusLabels, $statusOrder); ?>
            <form method="POST" action="<?php echo SITE_URL; ?>/admin/orders.php" class="mt-4">
                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                <input type="hidden" name="update_status" value="1">
                <select name="status" class="w-full px-3 py-2 border rounded-lg mb-2">
                    <?php foreach ($allowedStatuses as $key => $val): ?>
                    <option value="<?php echo $key; ?>" <?php echo $currentStatus === $key ? 'selected' : ''; ?>>
                        <?php echo $val['label']; ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700">
                    Cập nhật trạng thái
                </button>
            </form>
            <?php endif; ?>
        </div>
        
        <!-- Customer -->
        <div class="bg-white rounded-lg shadow p-4">
            <h3 class="font-semibold mb-3">Thông tin khách hàng</h3>
            <div class="space-y-2 text-sm">
                <p><strong>Họ tên:</strong> <?php echo htmlspecialchars($order['ho_ten']); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($order['email'] ?? '-'); ?></p>
                <p><strong>SĐT:</strong> <?php echo $order['so_dien_thoai']; ?></p>
            </div>
        </div>
        
        <!-- Shipping -->
        <div class="bg-white rounded-lg shadow p-4">
            <h3 class="font-semibold mb-3">Địa chỉ giao hàng</h3>
            <p class="text-sm"><?php echo htmlspecialchars($order['dia_chi']); ?></p>
            <?php if ($order['phuong_xa'] || $order['quan_huyen'] || $order['tinh_thanh']): ?>
            <p class="text-sm text-gray-500 mt-1">
                <?php echo implode(', ', array_filter([$order['phuong_xa'], $order['quan_huyen'], $order['tinh_thanh']])); ?>
            </p>
            <?php endif; ?>
        </div>
        
        <!-- Payment -->
        <div class="bg-white rounded-lg shadow p-4">
            <h3 class="font-semibold mb-3">Thanh toán</h3>
            <p class="text-sm">
                <strong>Phương thức:</strong> 
                <?php 
                $paymentMethod = strtolower($order['phuong_thuc_thanh_toan'] ?? 'cod');
                echo $paymentMethod === 'cod' ? 'Thanh toán khi nhận hàng (COD)' : 'Chuyển khoản ngân hàng'; 
                ?>
            </p>
            <p class="text-sm mt-1">
                <strong>Trạng thái:</strong> 
                <?php 
                $paymentStatus = $order['trang_thai_thanh_toan'] ?? 'pending';
                $orderStatus = $order['trang_thai'] ?? 'pending';
                // Nếu đơn hàng đã hoàn thành hoặc đã giao thì coi như đã thanh toán
                if (in_array($orderStatus, ['completed', 'delivered'])) {
                    $paymentStatus = 'paid';
                }
                $paymentLabels = [
                    'pending' => 'Chờ thanh toán',
                    'paid' => 'Đã thanh toán',
                    'failed' => 'Thanh toán thất bại',
                    'refunded' => 'Đã hoàn tiền'
                ];
                echo $paymentLabels[$paymentStatus] ?? $paymentStatus;
                ?>
            </p>
        </div>
        
        <!-- Notes -->
        <?php if (!empty($order['ghi_chu'])): ?>
        <div class="bg-white rounded-lg shadow p-4">
            <h3 class="font-semibold mb-3">Ghi chú</h3>
            <p class="text-sm text-gray-600"><?php echo nl2br(htmlspecialchars($order['ghi_chu'])); ?></p>
        </div>
        <?php endif; ?>
        
        <!-- Timeline -->
        <div class="bg-white rounded-lg shadow p-4">
            <h3 class="font-semibold mb-3">Thời gian</h3>
            <div class="space-y-2 text-sm">
                <p><strong>Ngày đặt:</strong> <?php echo formatDateTime($order['created_at']); ?></p>
                <?php if ($order['updated_at']): ?>
                <p><strong>Cập nhật:</strong> <?php echo formatDateTime($order['updated_at']); ?></p>
                <?php endif; ?>
                <?php if ($order['ngay_giao_thanh_cong']): ?>
                <p><strong>Giao thành công:</strong> <?php echo formatDateTime($order['ngay_giao_thanh_cong']); ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
