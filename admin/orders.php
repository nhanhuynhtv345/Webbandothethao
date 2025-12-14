<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isAdmin()) {
    redirect(SITE_URL . '/admin/login.php');
}

$db = getDB();
$message = '';

// Handle status update
if (isset($_POST['update_status'])) {
    $orderId = intval($_POST['order_id']);
    $status = $_POST['status'];
    
    // Kiểm tra trạng thái hiện tại của đơn hàng
    $checkStmt = $db->prepare("SELECT trang_thai FROM don_hang WHERE id = ?");
    $checkStmt->execute([$orderId]);
    $currentOrder = $checkStmt->fetch();
    
    // Không cho phép chỉnh sửa nếu đơn hàng đã hoàn thành hoặc đã hủy
    if ($currentOrder && in_array($currentOrder['trang_thai'], ['completed', 'cancelled', 'refunded'])) {
        $message = 'Không thể thay đổi trạng thái đơn hàng đã hoàn thành, đã hủy hoặc đã hoàn tiền!';
    } else {
        // Kiểm tra không cho phép quay lại trạng thái trước
        $statusOrderCheck = ['pending', 'confirmed', 'processing', 'shipping', 'delivered', 'completed'];
        $currentIndex = array_search($currentOrder['trang_thai'], $statusOrderCheck);
        $newIndex = array_search($status, $statusOrderCheck);
        
        // Nếu trạng thái mới không phải cancelled và có index nhỏ hơn trạng thái hiện tại
        if ($status !== 'cancelled' && $currentIndex !== false && $newIndex !== false && $newIndex < $currentIndex) {
            $message = 'Không thể quay lại trạng thái trước đó!';
        } else {
            // Nếu đơn hàng đã giao hoặc hoàn thành thì tự động cập nhật trạng thái thanh toán
            if (in_array($status, ['delivered', 'completed'])) {
                $db->prepare("UPDATE don_hang SET trang_thai = ?, trang_thai_thanh_toan = 'paid' WHERE id = ?")
                   ->execute([$status, $orderId]);
            } else {
                $db->prepare("UPDATE don_hang SET trang_thai = ? WHERE id = ?")
                   ->execute([$status, $orderId]);
            }
            $message = 'Cập nhật trạng thái đơn hàng thành công!';
        }
    }
}

// Filters
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 15;

// Build query
$where = ['1=1'];
$params = [];

if ($search) {
    $where[] = "(dh.ma_don_hang LIKE ? OR dh.ho_ten LIKE ? OR dh.so_dien_thoai LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($status) {
    $where[] = "dh.trang_thai = ?";
    $params[] = $status;
}

$whereClause = implode(' AND ', $where);

// Count total
try {
    $countStmt = $db->prepare("SELECT COUNT(*) FROM don_hang dh WHERE $whereClause");
    $countStmt->execute($params);
    $total = $countStmt->fetchColumn();
} catch (Exception $e) {
    $total = 0;
}
$totalPages = max(1, ceil($total / $perPage));
$offset = ($page - 1) * $perPage;

// Get orders - lấy thông tin từ bảng don_hang (đã có ho_ten, dien_thoai, email)
try {
    $sql = "SELECT dh.*
            FROM don_hang dh
            WHERE $whereClause
            ORDER BY dh.created_at DESC
            LIMIT $perPage OFFSET $offset";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $orders = $stmt->fetchAll();
} catch (Exception $e) {
    $orders = [];
}

$statusLabels = [
    'pending' => ['label' => 'Chờ xử lý', 'class' => 'bg-yellow-100 text-yellow-600'],
    'confirmed' => ['label' => 'Đã xác nhận', 'class' => 'bg-blue-100 text-blue-600'],
    'processing' => ['label' => 'Đang xử lý', 'class' => 'bg-indigo-100 text-indigo-600'],
    'shipping' => ['label' => 'Đang giao', 'class' => 'bg-purple-100 text-purple-600'],
    'delivered' => ['label' => 'Đã giao', 'class' => 'bg-teal-100 text-teal-600'],
    'completed' => ['label' => 'Hoàn thành', 'class' => 'bg-green-100 text-green-600'],
    'cancelled' => ['label' => 'Đã hủy', 'class' => 'bg-red-100 text-red-600'],
];

// Thứ tự trạng thái (chỉ được tiến lên, không được quay lại)
$statusOrder = ['pending', 'confirmed', 'processing', 'shipping', 'delivered', 'completed'];

// Hàm lấy các trạng thái có thể chuyển đến từ trạng thái hiện tại
function getAllowedStatuses($currentStatus, $statusLabels, $statusOrder) {
    $currentIndex = array_search($currentStatus, $statusOrder);
    if ($currentIndex === false) {
        // Nếu trạng thái hiện tại không trong flow chính (cancelled, refunded), không cho chỉnh sửa
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

$pageTitle = 'Quản lý đơn hàng';
include __DIR__ . '/includes/header.php';
?>

<?php if ($message): ?>
<div class="<?php echo strpos($message, 'Không thể') !== false ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700'; ?> p-4 rounded mb-6"><?php echo $message; ?></div>
<?php endif; ?>

<!-- Toolbar -->
<div class="bg-white rounded-lg shadow p-4 mb-6">
    <form method="GET" class="flex flex-wrap items-center gap-4">
        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
               placeholder="Tìm mã đơn, tên KH, SĐT..." class="px-4 py-2 border rounded-lg w-64">
        
        <select name="status" class="px-4 py-2 border rounded-lg">
            <option value="">Tất cả trạng thái</option>
            <?php foreach ($statusLabels as $key => $val): ?>
            <option value="<?php echo $key; ?>" <?php echo $status === $key ? 'selected' : ''; ?>>
                <?php echo $val['label']; ?>
            </option>
            <?php endforeach; ?>
        </select>
        
        <button type="submit" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700">
            <i class="fas fa-search mr-1"></i>Lọc
        </button>
    </form>
</div>

<!-- Orders Table -->
<div class="bg-white rounded-lg shadow overflow-hidden">
    <table class="w-full">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-600">Mã đơn</th>
                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-600">Khách hàng</th>
                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-600">Tổng tiền</th>
                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-600">Trạng thái</th>
                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-600">Ngày đặt</th>
                <th class="px-4 py-3 text-center text-sm font-semibold text-gray-600">Thao tác</th>
            </tr>
        </thead>
        <tbody class="divide-y">
            <?php if (empty($orders)): ?>
            <tr>
                <td colspan="6" class="px-4 py-8 text-center text-gray-500">Không có đơn hàng nào</td>
            </tr>
            <?php else: ?>
            <?php foreach ($orders as $order): ?>
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-3 font-semibold"><?php echo $order['ma_don_hang']; ?></td>
                <td class="px-4 py-3">
                    <div><?php echo htmlspecialchars($order['ho_ten'] ?? 'Khách vãng lai'); ?></div>
                    <div class="text-sm text-gray-500"><?php echo $order['so_dien_thoai'] ?? ''; ?></div>
                </td>
                <td class="px-4 py-3 font-semibold"><?php echo formatCurrency($order['tong_thanh_toan'] ?? 0); ?></td>
                <td class="px-4 py-3">
                    <?php $orderStatus = $order['trang_thai'] ?? 'pending'; ?>
                    <?php $isLocked = in_array($orderStatus, ['completed', 'cancelled', 'refunded']); ?>
                    <?php if ($isLocked): ?>
                    <span class="text-sm px-2 py-1 rounded <?php echo $statusLabels[$orderStatus]['class'] ?? 'bg-gray-100'; ?>">
                        <?php echo $statusLabels[$orderStatus]['label'] ?? $orderStatus; ?>
                    </span>
                    <?php else: ?>
                    <?php $allowedStatuses = getAllowedStatuses($orderStatus, $statusLabels, $statusOrder); ?>
                    <form method="POST" class="inline">
                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                        <select name="status" onchange="this.form.submit()" 
                                class="text-sm px-2 py-1 rounded border <?php echo $statusLabels[$orderStatus]['class'] ?? 'bg-gray-100'; ?>">
                            <?php foreach ($allowedStatuses as $key => $val): ?>
                            <option value="<?php echo $key; ?>" <?php echo $orderStatus === $key ? 'selected' : ''; ?>>
                                <?php echo $val['label']; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <input type="hidden" name="update_status" value="1">
                    </form>
                    <?php endif; ?>
                </td>
                <td class="px-4 py-3 text-sm"><?php echo formatDateTime($order['created_at']); ?></td>
                <td class="px-4 py-3 text-center">
                    <a href="<?php echo SITE_URL; ?>/admin/order-detail.php?id=<?php echo $order['id']; ?>" 
                       class="text-blue-600 hover:text-blue-800">
                        <i class="fas fa-eye"></i> Chi tiết
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Pagination -->
<?php if ($totalPages > 1): ?>
<div class="flex items-center justify-between mt-6">
    <p class="text-gray-600">Hiển thị <?php echo count($orders); ?> / <?php echo $total; ?> đơn hàng</p>
    <div class="flex gap-2">
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" 
           class="px-3 py-1 rounded <?php echo $i === $page ? 'bg-blue-600 text-white' : 'bg-white border hover:bg-gray-50'; ?>">
            <?php echo $i; ?>
        </a>
        <?php endfor; ?>
    </div>
</div>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
