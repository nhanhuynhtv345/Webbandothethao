<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isAdmin()) {
    redirect(SITE_URL . '/admin/login.php');
}

$pageTitle = 'Quản lý hóa đơn';
$db = getDB();

// Ensure tables exist with correct structure
try {
    // Check if hoa_don table exists
    $tableCheck = $db->query("SHOW TABLES LIKE 'hoa_don'");
    if ($tableCheck->rowCount() == 0) {
        $db->exec("
            CREATE TABLE hoa_don (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                don_hang_id BIGINT UNIQUE NOT NULL,
                nguoi_dung_id BIGINT NULL,
                ma_hoa_don VARCHAR(100) UNIQUE,
                tong_tien_san_pham DECIMAL(14,2) NOT NULL DEFAULT 0,
                phi_van_chuyen DECIMAL(14,2) NOT NULL DEFAULT 0,
                giam_gia DECIMAL(14,2) NOT NULL DEFAULT 0,
                tong_thanh_toan DECIMAL(14,2) NOT NULL,
                status VARCHAR(20) DEFAULT 'unpaid',
                ghi_chu TEXT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_status (status),
                INDEX idx_ma_hoa_don (ma_hoa_don)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    } else {
        // Add missing columns if table exists
        $columns = $db->query("SHOW COLUMNS FROM hoa_don")->fetchAll(PDO::FETCH_COLUMN);
        
        if (!in_array('tong_tien_san_pham', $columns)) {
            $db->exec("ALTER TABLE hoa_don ADD COLUMN tong_tien_san_pham DECIMAL(14,2) NOT NULL DEFAULT 0 AFTER ma_hoa_don");
        }
        if (!in_array('phi_van_chuyen', $columns)) {
            $db->exec("ALTER TABLE hoa_don ADD COLUMN phi_van_chuyen DECIMAL(14,2) NOT NULL DEFAULT 0 AFTER tong_tien_san_pham");
        }
        if (!in_array('giam_gia', $columns)) {
            $db->exec("ALTER TABLE hoa_don ADD COLUMN giam_gia DECIMAL(14,2) NOT NULL DEFAULT 0 AFTER phi_van_chuyen");
        }
        if (!in_array('ghi_chu', $columns)) {
            $db->exec("ALTER TABLE hoa_don ADD COLUMN ghi_chu TEXT NULL AFTER status");
        }
    }
    
    // Check chi_tiet_hoa_don table - recreate if structure is wrong
    $tableCheck2 = $db->query("SHOW TABLES LIKE 'chi_tiet_hoa_don'");
    if ($tableCheck2->rowCount() > 0) {
        // Check if old structure (has 'amount' column instead of 'don_gia')
        $columns2 = $db->query("SHOW COLUMNS FROM chi_tiet_hoa_don")->fetchAll(PDO::FETCH_COLUMN);
        if (in_array('amount', $columns2) && !in_array('don_gia', $columns2)) {
            // Drop old table and recreate
            $db->exec("DROP TABLE chi_tiet_hoa_don");
            $tableCheck2 = $db->query("SHOW TABLES LIKE 'chi_tiet_hoa_don'"); // recheck
        }
    }
    
    if ($tableCheck2->rowCount() == 0) {
        $db->exec("
            CREATE TABLE chi_tiet_hoa_don (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                hoa_don_id BIGINT NOT NULL,
                san_pham_id BIGINT NULL,
                ten_san_pham VARCHAR(500),
                thong_tin_bien_the VARCHAR(255) NULL,
                don_gia DECIMAL(12,2) NOT NULL DEFAULT 0,
                so_luong INT DEFAULT 1,
                thanh_tien DECIMAL(14,2) NOT NULL DEFAULT 0,
                INDEX idx_hoa_don (hoa_don_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    }
} catch (Exception $e) {
    error_log('Invoice table setup error: ' . $e->getMessage());
}

// Handle create invoice from order
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_invoice'])) {
    $orderId = intval($_POST['order_id']);
    
    // Check if invoice already exists
    $checkStmt = $db->prepare("SELECT id FROM hoa_don WHERE don_hang_id = ?");
    $checkStmt->execute([$orderId]);
    
    if (!$checkStmt->fetch()) {
        // Get order data
        $orderStmt = $db->prepare("SELECT * FROM don_hang WHERE id = ?");
        $orderStmt->execute([$orderId]);
        $order = $orderStmt->fetch();
        
        if ($order) {
            // Generate invoice code
            $invoiceCode = 'HD' . date('Ymd') . str_pad($orderId, 4, '0', STR_PAD_LEFT);
            
            // Create invoice
            $insertStmt = $db->prepare("
                INSERT INTO hoa_don (don_hang_id, nguoi_dung_id, ma_hoa_don, tong_tien_san_pham, phi_van_chuyen, giam_gia, tong_thanh_toan, status, ghi_chu)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $status = in_array($order['trang_thai'], ['completed', 'delivered']) ? 'paid' : 'unpaid';
            
            $insertStmt->execute([
                $orderId,
                $order['nguoi_dung_id'],
                $invoiceCode,
                $order['tong_tien_san_pham'],
                $order['phi_van_chuyen'],
                $order['giam_gia'] ?? 0,
                $order['tong_thanh_toan'],
                $status,
                $order['ghi_chu']
            ]);
            
            $invoiceId = $db->lastInsertId();
            
            // Copy order items to invoice
            $itemsStmt = $db->prepare("SELECT * FROM chi_tiet_don_hang WHERE don_hang_id = ?");
            $itemsStmt->execute([$orderId]);
            $items = $itemsStmt->fetchAll();
            
            $insertItemStmt = $db->prepare("
                INSERT INTO chi_tiet_hoa_don (hoa_don_id, san_pham_id, ten_san_pham, thong_tin_bien_the, don_gia, so_luong, thanh_tien)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            foreach ($items as $item) {
                $insertItemStmt->execute([
                    $invoiceId,
                    $item['san_pham_id'],
                    $item['ten_san_pham'],
                    $item['thong_tin_bien_the'],
                    $item['gia_ban'],
                    $item['so_luong'],
                    $item['thanh_tien']
                ]);
            }
            
            header('Location: ' . SITE_URL . '/admin/invoice-detail.php?id=' . $invoiceId);
            exit;
        }
    }
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $invoiceId = intval($_POST['invoice_id']);
    $status = $_POST['status'];
    $validStatuses = ['unpaid', 'paid', 'partially_paid', 'cancelled'];
    
    if (in_array($status, $validStatuses)) {
        $stmt = $db->prepare("UPDATE hoa_don SET status = ? WHERE id = ?");
        $stmt->execute([$status, $invoiceId]);
    }
    
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
}

// Filters
$status = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 15;

// Build query
$where = [];
$params = [];

if ($status) {
    $where[] = "hd.status = ?";
    $params[] = $status;
}

if ($search) {
    $where[] = "(hd.ma_hoa_don LIKE ? OR dh.ma_don_hang LIKE ? OR dh.ho_ten LIKE ? OR dh.so_dien_thoai LIKE ?)";
    $searchTerm = "%$search%";
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
}

$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Count total
$countStmt = $db->prepare("
    SELECT COUNT(*) FROM hoa_don hd
    LEFT JOIN don_hang dh ON hd.don_hang_id = dh.id
    $whereClause
");
$countStmt->execute($params);
$totalInvoices = $countStmt->fetchColumn();
$totalPages = ceil($totalInvoices / $perPage);

// Get invoices
$offset = ($page - 1) * $perPage;
$stmt = $db->prepare("
    SELECT hd.*, dh.ma_don_hang, dh.ho_ten, dh.so_dien_thoai, dh.email, dh.trang_thai as order_status
    FROM hoa_don hd
    LEFT JOIN don_hang dh ON hd.don_hang_id = dh.id
    $whereClause
    ORDER BY hd.created_at DESC
    LIMIT $perPage OFFSET $offset
");
$stmt->execute($params);
$invoices = $stmt->fetchAll();

// Get stats
$stats = ['unpaid' => 0, 'paid' => 0, 'cancelled' => 0];
$statsStmt = $db->query("SELECT status, COUNT(*) as count, SUM(tong_thanh_toan) as total FROM hoa_don GROUP BY status");
while ($row = $statsStmt->fetch()) {
    $stats[$row['status']] = ['count' => $row['count'], 'total' => $row['total']];
}

$statusLabels = [
    'unpaid' => ['label' => 'Chưa thanh toán', 'class' => 'bg-yellow-100 text-yellow-700', 'icon' => 'fa-clock'],
    'paid' => ['label' => 'Đã thanh toán', 'class' => 'bg-green-100 text-green-700', 'icon' => 'fa-check-circle'],
    'cancelled' => ['label' => 'Đã hủy', 'class' => 'bg-red-100 text-red-700', 'icon' => 'fa-times-circle'],
];

include __DIR__ . '/includes/header.php';
?>

<!-- Stats Cards -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
    <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-yellow-500">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Chưa thanh toán</p>
                <p class="text-2xl font-bold text-yellow-600"><?php echo $stats['unpaid']['count'] ?? 0; ?></p>
                <p class="text-xs text-gray-400"><?php echo formatCurrency($stats['unpaid']['total'] ?? 0); ?></p>
            </div>
            <div class="w-12 h-12 bg-yellow-100 rounded-full flex items-center justify-center">
                <i class="fas fa-clock text-yellow-600 text-xl"></i>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-green-500">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Đã thanh toán</p>
                <p class="text-2xl font-bold text-green-600"><?php echo $stats['paid']['count'] ?? 0; ?></p>
                <p class="text-xs text-gray-400"><?php echo formatCurrency($stats['paid']['total'] ?? 0); ?></p>
            </div>
            <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                <i class="fas fa-check-circle text-green-600 text-xl"></i>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-red-500">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Đã hủy</p>
                <p class="text-2xl font-bold text-red-600"><?php echo $stats['cancelled']['count'] ?? 0; ?></p>
                <p class="text-xs text-gray-400"><?php echo formatCurrency($stats['cancelled']['total'] ?? 0); ?></p>
            </div>
            <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center">
                <i class="fas fa-times-circle text-red-600 text-xl"></i>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-purple-500">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Tổng doanh thu</p>
                <p class="text-2xl font-bold text-purple-600"><?php echo formatCurrency($stats['paid']['total'] ?? 0); ?></p>
                <p class="text-xs text-gray-400"><?php echo $stats['paid']['count'] ?? 0; ?> hóa đơn</p>
            </div>
            <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center">
                <i class="fas fa-dollar-sign text-purple-600 text-xl"></i>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="bg-white rounded-xl shadow-sm p-6 mb-6">
    <form method="GET" class="flex flex-wrap gap-4 items-end">
        <div class="flex-1 min-w-[200px]">
            <label class="block text-sm font-medium text-gray-700 mb-2">Tìm kiếm</label>
            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>"
                   placeholder="Mã hóa đơn, mã đơn hàng, tên KH..."
                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500">
        </div>
        
        <div class="w-48">
            <label class="block text-sm font-medium text-gray-700 mb-2">Trạng thái</label>
            <select name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                <option value="">Tất cả</option>
                <?php foreach ($statusLabels as $key => $val): ?>
                <option value="<?php echo $key; ?>" <?php echo $status === $key ? 'selected' : ''; ?>><?php echo $val['label']; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <button type="submit" class="px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700">
            <i class="fas fa-search mr-2"></i>Lọc
        </button>
        
        <a href="<?php echo SITE_URL; ?>/admin/invoices.php" class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
            <i class="fas fa-redo mr-2"></i>Reset
        </a>
    </form>
</div>

<!-- Invoices Table -->
<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <?php if (empty($invoices)): ?>
    <div class="p-12 text-center">
        <i class="fas fa-file-invoice text-6xl text-gray-300 mb-4"></i>
        <p class="text-gray-500 text-lg">Chưa có hóa đơn nào</p>
        <p class="text-gray-400 text-sm mt-2">Hóa đơn sẽ được tạo từ đơn hàng đã hoàn thành</p>
    </div>
    <?php else: ?>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase">Mã hóa đơn</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase">Đơn hàng</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase">Khách hàng</th>
                    <th class="px-6 py-4 text-right text-xs font-semibold text-gray-500 uppercase">Tổng tiền</th>
                    <th class="px-6 py-4 text-center text-xs font-semibold text-gray-500 uppercase">Trạng thái</th>
                    <th class="px-6 py-4 text-center text-xs font-semibold text-gray-500 uppercase">Ngày tạo</th>
                    <th class="px-6 py-4 text-center text-xs font-semibold text-gray-500 uppercase">Thao tác</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php foreach ($invoices as $invoice): ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4">
                        <a href="<?php echo SITE_URL; ?>/admin/invoice-detail.php?id=<?php echo $invoice['id']; ?>" 
                           class="font-semibold text-primary-600 hover:underline">
                            <?php echo htmlspecialchars($invoice['ma_hoa_don']); ?>
                        </a>
                    </td>
                    <td class="px-6 py-4">
                        <a href="<?php echo SITE_URL; ?>/admin/order-detail.php?id=<?php echo $invoice['don_hang_id']; ?>" 
                           class="text-gray-600 hover:text-primary-600">
                            <?php echo htmlspecialchars($invoice['ma_don_hang']); ?>
                        </a>
                    </td>
                    <td class="px-6 py-4">
                        <p class="font-medium text-gray-900"><?php echo htmlspecialchars($invoice['ho_ten']); ?></p>
                        <p class="text-sm text-gray-500"><?php echo htmlspecialchars($invoice['so_dien_thoai']); ?></p>
                    </td>
                    <td class="px-6 py-4 text-right font-semibold text-gray-900">
                        <?php echo formatCurrency($invoice['tong_thanh_toan']); ?>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <?php $st = $statusLabels[$invoice['status']] ?? $statusLabels['unpaid']; ?>
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium <?php echo $st['class']; ?>">
                            <i class="fas <?php echo $st['icon']; ?> mr-1"></i>
                            <?php echo $st['label']; ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 text-center text-sm text-gray-500">
                        <?php echo date('d/m/Y H:i', strtotime($invoice['created_at'])); ?>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <div class="flex items-center justify-center gap-2">
                            <a href="<?php echo SITE_URL; ?>/admin/invoice-detail.php?id=<?php echo $invoice['id']; ?>" 
                               class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg" title="Xem chi tiết">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="<?php echo SITE_URL; ?>/admin/invoice-print.php?id=<?php echo $invoice['id']; ?>" 
                               target="_blank" class="p-2 text-green-600 hover:bg-green-50 rounded-lg" title="In hóa đơn">
                                <i class="fas fa-print"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <div class="px-6 py-4 border-t border-gray-200 flex items-center justify-between">
        <p class="text-sm text-gray-500">Hiển thị <?php echo count($invoices); ?> / <?php echo $totalInvoices; ?> hóa đơn</p>
        <div class="flex gap-2">
            <?php if ($page > 1): ?>
            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" class="px-3 py-1 border rounded hover:bg-gray-50">
                <i class="fas fa-chevron-left"></i>
            </a>
            <?php endif; ?>
            
            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" 
               class="px-3 py-1 border rounded <?php echo $i === $page ? 'bg-primary-600 text-white' : 'hover:bg-gray-50'; ?>">
                <?php echo $i; ?>
            </a>
            <?php endfor; ?>
            
            <?php if ($page < $totalPages): ?>
            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" class="px-3 py-1 border rounded hover:bg-gray-50">
                <i class="fas fa-chevron-right"></i>
            </a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
