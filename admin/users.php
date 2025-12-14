<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isAdmin()) {
    redirect(SITE_URL . '/admin/login.php');
}

$db = getDB();
$message = '';
$error = '';

// Handle unlock user
if (isset($_GET['unlock'])) {
    $id = intval($_GET['unlock']);
    try {
        $db->prepare("UPDATE nguoi_dung SET is_locked = 0, failed_attempts = 0, locked_at = NULL WHERE id = ?")->execute([$id]);
        $message = 'Đã mở khóa tài khoản thành công!';
    } catch (Exception $e) {
        $error = 'Lỗi: ' . $e->getMessage();
    }
}

// Handle delete user
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    try {
        $db->beginTransaction();
        
        // Xóa chi tiết đơn hàng của user
        $db->prepare("DELETE FROM chi_tiet_don_hang WHERE don_hang_id IN (SELECT id FROM don_hang WHERE nguoi_dung_id = ?)")->execute([$id]);
        // Xóa đơn hàng của user
        $db->prepare("DELETE FROM don_hang WHERE nguoi_dung_id = ?")->execute([$id]);
        // Xóa giỏ hàng
        $db->prepare("DELETE FROM gio_hang WHERE nguoi_dung_id = ?")->execute([$id]);
        // Xóa wishlist
        $db->prepare("DELETE FROM yeu_thich WHERE nguoi_dung_id = ?")->execute([$id]);
        // Xóa user
        $db->prepare("DELETE FROM nguoi_dung WHERE id = ?")->execute([$id]);
        
        $db->commit();
        $message = 'Xóa khách hàng và tất cả dữ liệu liên quan thành công!';
    } catch (Exception $e) {
        $db->rollBack();
        $error = 'Lỗi khi xóa: ' . $e->getMessage();
    }
}

// Đếm số tài khoản bị khóa
$lockedCount = 0;
try {
    $stmt = $db->query("SELECT COUNT(*) FROM nguoi_dung WHERE is_locked = 1");
    $lockedCount = $stmt->fetchColumn();
} catch (Exception $e) {
    // Cột chưa tồn tại
}

// Filters
$search = $_GET['search'] ?? '';
$sort = $_GET['sort'] ?? 'newest';
$filter = $_GET['filter'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 15;

// Build query
$where = ['1=1'];
$params = [];

if ($search) {
    // Tìm chính xác theo ID, email, SĐT - tìm gần đúng theo tên
    $where[] = "(id = ? OR ho_ten LIKE ? OR email = ? OR so_dien_thoai = ?)";
    $params[] = is_numeric($search) ? intval($search) : 0;
    $params[] = "%$search%";
    $params[] = $search;
    $params[] = $search;
}

// Filter tài khoản bị khóa
if ($filter === 'locked') {
    $where[] = "is_locked = 1";
}

$whereClause = implode(' AND ', $where);

// Sort
$orderBy = match($sort) {
    'name_asc' => 'ho_ten ASC',
    'name_desc' => 'ho_ten DESC',
    'oldest' => 'created_at ASC',
    default => 'created_at DESC'
};

// Count total
$countStmt = $db->prepare("SELECT COUNT(*) FROM nguoi_dung WHERE $whereClause");
$countStmt->execute($params);
$total = $countStmt->fetchColumn();
$totalPages = max(1, ceil($total / $perPage));
$offset = ($page - 1) * $perPage;

// Get users
$sql = "SELECT * FROM nguoi_dung WHERE $whereClause ORDER BY $orderBy LIMIT $perPage OFFSET $offset";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

// Get order info for each user
foreach ($users as $key => $user) {
    try {
        $orderStmt = $db->prepare("SELECT COUNT(*) as order_count, COALESCE(SUM(tong_thanh_toan), 0) as total_spent FROM don_hang WHERE nguoi_dung_id = ?");
        $orderStmt->execute([$user['id']]);
        $orderInfo = $orderStmt->fetch();
        $users[$key]['order_count'] = $orderInfo['order_count'] ?? 0;
        $users[$key]['total_spent'] = $orderInfo['total_spent'] ?? 0;
    } catch (Exception $e) {
        $users[$key]['order_count'] = 0;
        $users[$key]['total_spent'] = 0;
    }
}

$pageTitle = 'Quản lý khách hàng';
include __DIR__ . '/includes/header.php';
?>

<?php if ($lockedCount > 0): ?>
<div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl mb-6 flex items-center justify-between">
    <div class="flex items-center gap-3">
        <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center flex-shrink-0">
            <i class="fas fa-lock text-red-500"></i>
        </div>
        <div>
            <p class="font-semibold">Có <?php echo $lockedCount; ?> tài khoản bị khóa vĩnh viễn</p>
            <p class="text-sm">Do đăng nhập sai quá 5 lần. Bạn có thể mở khóa hoặc xóa tài khoản.</p>
        </div>
    </div>
    <a href="?filter=locked" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-sm font-medium">
        Xem danh sách
    </a>
</div>
<?php endif; ?>

<?php if ($message): ?>
<div class="bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-xl mb-6 flex items-center gap-3">
    <div class="w-10 h-10 bg-emerald-100 rounded-full flex items-center justify-center flex-shrink-0">
        <i class="fas fa-check text-emerald-500"></i>
    </div>
    <p><?php echo $message; ?></p>
</div>
<?php endif; ?>

<?php if ($error): ?>
<div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl mb-6 flex items-center gap-3">
    <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center flex-shrink-0">
        <i class="fas fa-exclamation-circle text-red-500"></i>
    </div>
    <p><?php echo $error; ?></p>
</div>
<?php endif; ?>

<!-- Toolbar -->
<div class="card-hover bg-white rounded-2xl shadow-sm border border-slate-200 p-5 mb-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <form method="GET" class="flex flex-wrap items-center gap-3">
            <div class="relative">
                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">
                    <i class="fas fa-search"></i>
                </span>
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                       placeholder="Tìm ID, tên, email, SĐT..." 
                       class="pl-10 pr-4 py-2.5 border border-slate-200 rounded-xl focus:border-primary-500 focus:ring-2 focus:ring-primary-100 outline-none transition w-64">
            </div>
            
            <select name="sort" class="px-4 py-2.5 border border-slate-200 rounded-xl focus:border-primary-500 outline-none bg-white">
                <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Mới nhất</option>
                <option value="oldest" <?php echo $sort === 'oldest' ? 'selected' : ''; ?>>Cũ nhất</option>
                <option value="name_asc" <?php echo $sort === 'name_asc' ? 'selected' : ''; ?>>Tên A-Z</option>
                <option value="name_desc" <?php echo $sort === 'name_desc' ? 'selected' : ''; ?>>Tên Z-A</option>
            </select>
            
            <button type="submit" class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-5 py-2.5 rounded-xl font-medium transition">
                <i class="fas fa-filter mr-2"></i>Lọc
            </button>
        </form>
        
        <a href="<?php echo SITE_URL; ?>/admin/user-form.php" 
           class="bg-gradient-to-r from-primary-500 to-primary-600 hover:from-primary-600 hover:to-primary-700 text-white px-5 py-2.5 rounded-xl font-medium shadow-lg shadow-primary-500/30 transition transform hover:-translate-y-0.5">
            <i class="fas fa-plus mr-2"></i>Thêm khách hàng
        </a>
    </div>
</div>

<!-- Users Table -->
<div class="card-hover bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
    <table class="w-full">
        <thead>
            <tr class="bg-slate-50 border-b border-slate-200">
                <th class="px-5 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">ID</th>
                <th class="px-5 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Khách hàng</th>
                <th class="px-5 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Email</th>
                <th class="px-5 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">SĐT</th>
                <th class="px-5 py-4 text-center text-xs font-semibold text-slate-500 uppercase tracking-wider">Trạng thái</th>
                <th class="px-5 py-4 text-center text-xs font-semibold text-slate-500 uppercase tracking-wider">Đơn hàng</th>
                <th class="px-5 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Tổng chi tiêu</th>
                <th class="px-5 py-4 text-center text-xs font-semibold text-slate-500 uppercase tracking-wider">Thao tác</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            <?php if (empty($users)): ?>
            <tr>
                <td colspan="9" class="px-5 py-12 text-center">
                    <div class="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-users text-slate-400 text-2xl"></i>
                    </div>
                    <p class="text-slate-500">Không có khách hàng nào</p>
                </td>
            </tr>
            <?php else: ?>
            <?php foreach ($users as $user): ?>
            <tr class="hover:bg-slate-50 transition">
                <td class="px-5 py-4">
                    <span class="text-sm font-mono bg-slate-100 px-2 py-1 rounded text-slate-600"><?php echo $user['id']; ?></span>
                </td>
                <td class="px-5 py-4">
                    <div class="flex items-center gap-4">
                        <?php 
                        $avatarUrl = $user['avt'] ?? '';
                        $firstChar = strtoupper(mb_substr($user['ho_ten'], 0, 1));
                        if (!empty($avatarUrl)): 
                        ?>
                        <img src="<?php echo htmlspecialchars($avatarUrl); ?>" 
                             class="w-10 h-10 rounded-full object-cover"
                             onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                        <div class="w-10 h-10 bg-gradient-to-br from-primary-400 to-primary-600 rounded-full items-center justify-center text-white font-bold" style="display:none;">
                            <?php echo $firstChar; ?>
                        </div>
                        <?php else: ?>
                        <div class="w-10 h-10 bg-gradient-to-br from-primary-400 to-primary-600 rounded-full flex items-center justify-center text-white font-bold">
                            <?php echo $firstChar; ?>
                        </div>
                        <?php endif; ?>
                        <span class="font-semibold text-slate-800"><?php echo htmlspecialchars($user['ho_ten']); ?></span>
                    </div>
                </td>
                <td class="px-5 py-4 text-sm text-slate-600"><?php echo htmlspecialchars($user['email']); ?></td>
                <td class="px-5 py-4 text-sm text-slate-600"><?php echo $user['so_dien_thoai'] ?? '-'; ?></td>
                <td class="px-5 py-4 text-center">
                    <?php $isLocked = $user['is_locked'] ?? 0; ?>
                    <?php if ($isLocked): ?>
                    <span class="inline-flex items-center gap-1 px-2 py-1 bg-red-100 text-red-700 rounded-full text-xs font-semibold">
                        <i class="fas fa-lock"></i> Bị khóa
                    </span>
                    <?php else: ?>
                    <span class="inline-flex items-center gap-1 px-2 py-1 bg-green-100 text-green-700 rounded-full text-xs font-semibold">
                        <i class="fas fa-check"></i> Hoạt động
                    </span>
                    <?php endif; ?>
                </td>
                <td class="px-5 py-4 text-center">
                    <span class="inline-flex items-center justify-center w-8 h-8 bg-blue-100 text-blue-700 rounded-full text-sm font-semibold">
                        <?php echo $user['order_count']; ?>
                    </span>
                </td>
                <td class="px-5 py-4 font-bold text-slate-800"><?php echo formatCurrency($user['total_spent']); ?></td>
                <td class="px-5 py-4">
                    <div class="flex items-center justify-center gap-1">
                        <?php if ($isLocked): ?>
                        <a href="?unlock=<?php echo $user['id']; ?>&<?php echo http_build_query($_GET); ?>" 
                           onclick="return confirm('Mở khóa tài khoản này?')"
                           class="w-9 h-9 flex items-center justify-center rounded-lg bg-green-50 text-green-600 hover:bg-green-100 transition" title="Mở khóa">
                            <i class="fas fa-unlock"></i>
                        </a>
                        <?php else: ?>
                        <a href="<?php echo SITE_URL; ?>/admin/user-detail.php?id=<?php echo $user['id']; ?>" 
                           class="w-9 h-9 flex items-center justify-center rounded-lg bg-slate-100 text-slate-600 hover:bg-slate-200 transition" title="Xem chi tiết">
                            <i class="fas fa-eye"></i>
                        </a>
                        <a href="<?php echo SITE_URL; ?>/admin/user-form.php?id=<?php echo $user['id']; ?>" 
                           class="w-9 h-9 flex items-center justify-center rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-100 transition" title="Sửa">
                            <i class="fas fa-edit"></i>
                        </a>
                        <?php endif; ?>
                        <a href="?delete=<?php echo $user['id']; ?>&<?php echo http_build_query($_GET); ?>" 
                           onclick="return confirmDelete('Bạn có chắc muốn xóa khách hàng này?')"
                           class="w-9 h-9 flex items-center justify-center rounded-lg bg-red-50 text-red-600 hover:bg-red-100 transition" title="Xóa">
                            <i class="fas fa-trash"></i>
                        </a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Pagination -->
<?php if ($totalPages > 1): ?>
<div class="flex items-center justify-between mt-6 bg-white rounded-xl p-4 border border-slate-200">
    <p class="text-slate-500 text-sm">
        Hiển thị <span class="font-semibold text-slate-700"><?php echo count($users); ?></span> / 
        <span class="font-semibold text-slate-700"><?php echo $total; ?></span> khách hàng
    </p>
    <div class="flex items-center gap-1">
        <?php if ($page > 1): ?>
        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" 
           class="w-9 h-9 flex items-center justify-center rounded-lg bg-slate-100 text-slate-600 hover:bg-slate-200 transition">
            <i class="fas fa-chevron-left text-sm"></i>
        </a>
        <?php endif; ?>
        
        <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" 
           class="w-9 h-9 flex items-center justify-center rounded-lg font-medium transition <?php echo $i === $page ? 'bg-primary-500 text-white shadow-lg shadow-primary-500/30' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'; ?>">
            <?php echo $i; ?>
        </a>
        <?php endfor; ?>
        
        <?php if ($page < $totalPages): ?>
        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" 
           class="w-9 h-9 flex items-center justify-center rounded-lg bg-slate-100 text-slate-600 hover:bg-slate-200 transition">
            <i class="fas fa-chevron-right text-sm"></i>
        </a>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
