<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

$pageTitle = 'Quản lý liên hệ';

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $db = getDB();
    
    if ($_POST['action'] === 'update_status') {
        $id = intval($_POST['id']);
        $status = $_POST['status'];
        $validStatuses = ['pending', 'processing', 'resolved'];
        
        if (in_array($status, $validStatuses)) {
            $stmt = $db->prepare("UPDATE lien_he SET status = ? WHERE id = ?");
            $stmt->execute([$status, $id]);
        }
    }
    
    if ($_POST['action'] === 'delete') {
        $id = intval($_POST['id']);
        $stmt = $db->prepare("DELETE FROM lien_he WHERE id = ?");
        $stmt->execute([$id]);
    }
    
    header('Location: ' . $_SERVER['PHP_SELF'] . '?' . http_build_query($_GET));
    exit;
}

// Filters
$status = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 15;

$db = getDB();

// Check if table exists
$tableCheck = $db->query("SHOW TABLES LIKE 'lien_he'");
$tableExists = $tableCheck->rowCount() > 0;

$contacts = [];
$totalContacts = 0;
$totalPages = 1;
$stats = ['pending' => 0, 'processing' => 0, 'resolved' => 0];

if ($tableExists) {
    // Ensure status column exists with correct type
    try {
        $columns = $db->query("SHOW COLUMNS FROM lien_he LIKE 'status'")->fetchAll();
        if (empty($columns)) {
            $db->exec("ALTER TABLE lien_he ADD COLUMN status VARCHAR(20) DEFAULT 'pending'");
        } else {
            // Check if column type needs to be fixed
            $colInfo = $columns[0];
            if (strpos($colInfo['Type'], 'enum') !== false || strpos($colInfo['Type'], 'ENUM') !== false) {
                $db->exec("ALTER TABLE lien_he MODIFY COLUMN status VARCHAR(20) DEFAULT 'pending'");
            }
        }
    } catch (Exception $e) {
        // Ignore error
    }
    
    // Build query
    $where = [];
    $params = [];
    
    if ($status) {
        $where[] = "status = ?";
        $params[] = $status;
    }
    
    if ($search) {
        $where[] = "(name LIKE ? OR email LIKE ? OR subject LIKE ? OR message LIKE ?)";
        $searchTerm = "%$search%";
        $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
    }
    
    $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';
    
    // Count total
    $countStmt = $db->prepare("SELECT COUNT(*) FROM lien_he $whereClause");
    $countStmt->execute($params);
    $totalContacts = $countStmt->fetchColumn();
    $totalPages = ceil($totalContacts / $perPage);
    
    // Get contacts
    $offset = ($page - 1) * $perPage;
    $stmt = $db->prepare("
        SELECT lh.*, nd.ho_ten as user_name, nd.avt as user_avatar
        FROM lien_he lh
        LEFT JOIN nguoi_dung nd ON lh.user_id = nd.id
        $whereClause
        ORDER BY lh.created_at DESC
        LIMIT $perPage OFFSET $offset
    ");
    $stmt->execute($params);
    $contacts = $stmt->fetchAll();
    
    // Get stats
    $statsStmt = $db->query("SELECT status, COUNT(*) as count FROM lien_he GROUP BY status");
    while ($row = $statsStmt->fetch()) {
        $stats[$row['status']] = $row['count'];
    }
}

include __DIR__ . '/includes/header.php';
?>

<!-- Stats Cards -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
    <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-yellow-500">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Chờ xử lý</p>
                <p class="text-3xl font-bold text-yellow-600"><?php echo $stats['pending']; ?></p>
            </div>
            <div class="w-12 h-12 bg-yellow-100 rounded-full flex items-center justify-center">
                <i class="fas fa-clock text-yellow-600 text-xl"></i>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-blue-500">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Đang xử lý</p>
                <p class="text-3xl font-bold text-blue-600"><?php echo $stats['processing']; ?></p>
            </div>
            <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                <i class="fas fa-spinner text-blue-600 text-xl"></i>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-green-500">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Đã xử lý</p>
                <p class="text-3xl font-bold text-green-600"><?php echo $stats['resolved']; ?></p>
            </div>
            <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                <i class="fas fa-check-circle text-green-600 text-xl"></i>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-purple-500">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Tổng liên hệ</p>
                <p class="text-3xl font-bold text-purple-600"><?php echo array_sum($stats); ?></p>
            </div>
            <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center">
                <i class="fas fa-envelope text-purple-600 text-xl"></i>
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
                   placeholder="Tên, email, chủ đề..."
                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500">
        </div>
        
        <div class="w-48">
            <label class="block text-sm font-medium text-gray-700 mb-2">Trạng thái</label>
            <select name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500">
                <option value="">Tất cả</option>
                <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Chờ xử lý</option>
                <option value="processing" <?php echo $status === 'processing' ? 'selected' : ''; ?>>Đang xử lý</option>
                <option value="resolved" <?php echo $status === 'resolved' ? 'selected' : ''; ?>>Đã xử lý</option>
            </select>
        </div>
        
        <button type="submit" class="px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700">
            <i class="fas fa-search mr-2"></i>Lọc
        </button>
        
        <a href="<?php echo SITE_URL; ?>/admin/contacts.php" class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
            <i class="fas fa-redo mr-2"></i>Reset
        </a>
    </form>
</div>

<!-- Contacts Table -->
<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <?php if (empty($contacts)): ?>
    <div class="p-12 text-center">
        <i class="fas fa-inbox text-6xl text-gray-300 mb-4"></i>
        <p class="text-gray-500 text-lg">Chưa có liên hệ nào</p>
    </div>
    <?php else: ?>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase">Khách hàng</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase">Chủ đề</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase">Nội dung</th>
                    <th class="px-6 py-4 text-center text-xs font-semibold text-gray-500 uppercase">File</th>
                    <th class="px-6 py-4 text-center text-xs font-semibold text-gray-500 uppercase">Trạng thái</th>
                    <th class="px-6 py-4 text-center text-xs font-semibold text-gray-500 uppercase">Ngày gửi</th>
                    <th class="px-6 py-4 text-center text-xs font-semibold text-gray-500 uppercase">Thao tác</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php foreach ($contacts as $contact): ?>
                <tr class="hover:bg-gray-50 <?php echo $contact['status'] === 'pending' ? 'bg-yellow-50' : ''; ?>">
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            <?php if ($contact['user_avatar']): ?>
                            <img src="<?php echo htmlspecialchars($contact['user_avatar']); ?>" 
                                 class="w-10 h-10 rounded-full object-cover"
                                 onerror="this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($contact['name']); ?>&background=random'">
                            <?php else: ?>
                            <div class="w-10 h-10 bg-primary-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-user text-primary-600"></i>
                            </div>
                            <?php endif; ?>
                            <div>
                                <p class="font-medium text-gray-900"><?php echo htmlspecialchars($contact['name']); ?></p>
                                <p class="text-sm text-gray-500"><?php echo htmlspecialchars($contact['email']); ?></p>
                                <?php if ($contact['phone']): ?>
                                <p class="text-xs text-gray-400"><?php echo htmlspecialchars($contact['phone']); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                            <?php echo htmlspecialchars($contact['subject']); ?>
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        <p class="text-sm text-gray-600 max-w-xs truncate" title="<?php echo htmlspecialchars($contact['message']); ?>">
                            <?php echo htmlspecialchars(mb_substr($contact['message'], 0, 80)) . (mb_strlen($contact['message']) > 80 ? '...' : ''); ?>
                        </p>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <?php 
                        $attachments = $contact['attachments'] ? json_decode($contact['attachments'], true) : [];
                        if (!empty($attachments)):
                        ?>
                        <button type="button" onclick="showAttachments(<?php echo htmlspecialchars(json_encode($attachments)); ?>)"
                                class="inline-flex items-center px-2 py-1 rounded bg-blue-100 text-blue-700 text-xs hover:bg-blue-200 transition cursor-pointer">
                            <i class="fas fa-paperclip mr-1"></i><?php echo count($attachments); ?>
                        </button>
                        <?php else: ?>
                        <span class="text-gray-400">-</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <form method="POST" class="inline">
                            <input type="hidden" name="action" value="update_status">
                            <input type="hidden" name="id" value="<?php echo $contact['id']; ?>">
                            <select name="status" onchange="this.form.submit()"
                                    class="text-xs px-2 py-1 rounded-full border-0 font-medium
                                    <?php 
                                    echo match($contact['status']) {
                                        'pending' => 'bg-yellow-100 text-yellow-700',
                                        'processing' => 'bg-blue-100 text-blue-700',
                                        'resolved' => 'bg-green-100 text-green-700',
                                        default => 'bg-gray-100 text-gray-700'
                                    };
                                    ?>">
                                <option value="pending" <?php echo $contact['status'] === 'pending' ? 'selected' : ''; ?>>Chờ xử lý</option>
                                <option value="processing" <?php echo $contact['status'] === 'processing' ? 'selected' : ''; ?>>Đang xử lý</option>
                                <option value="resolved" <?php echo $contact['status'] === 'resolved' ? 'selected' : ''; ?>>Đã xử lý</option>
                            </select>
                        </form>
                    </td>
                    <td class="px-6 py-4 text-center text-sm text-gray-500">
                        <?php echo date('d/m/Y H:i', strtotime($contact['created_at'])); ?>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <div class="flex items-center justify-center gap-2">
                            <a href="<?php echo SITE_URL; ?>/admin/contact-detail.php?id=<?php echo $contact['id']; ?>" 
                               class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg" title="Xem chi tiết">
                                <i class="fas fa-eye"></i>
                            </a>
                            <form method="POST" class="inline" onsubmit="return confirm('Bạn có chắc muốn xóa liên hệ này?')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo $contact['id']; ?>">
                                <button type="submit" class="p-2 text-red-600 hover:bg-red-50 rounded-lg" title="Xóa">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
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
        <p class="text-sm text-gray-500">
            Hiển thị <?php echo count($contacts); ?> / <?php echo $totalContacts; ?> liên hệ
        </p>
        <div class="flex gap-2">
            <?php if ($page > 1): ?>
            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" 
               class="px-3 py-1 border rounded hover:bg-gray-50">
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
            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" 
               class="px-3 py-1 border rounded hover:bg-gray-50">
                <i class="fas fa-chevron-right"></i>
            </a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</div>

<!-- Modal xem file đính kèm -->
<div id="attachmentsModal" class="hidden fixed inset-0 bg-black bg-opacity-75 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl max-w-4xl w-full max-h-[90vh] overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-900">
                <i class="fas fa-paperclip mr-2 text-primary-600"></i>
                File đính kèm
            </h3>
            <button onclick="closeAttachmentsModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <div id="attachmentsContent" class="p-6 overflow-y-auto max-h-[70vh]">
            <!-- Content will be loaded here -->
        </div>
    </div>
</div>

<script>
function showAttachments(files) {
    const modal = document.getElementById('attachmentsModal');
    const content = document.getElementById('attachmentsContent');
    
    let html = '<div class="grid grid-cols-1 md:grid-cols-2 gap-4">';
    
    files.forEach(file => {
        const ext = file.split('.').pop().toLowerCase();
        const isImage = ['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(ext);
        const fileName = file.split('/').pop();
        
        if (isImage) {
            html += `
                <div class="border border-gray-200 rounded-lg overflow-hidden">
                    <a href="${file}" target="_blank" class="block">
                        <img src="${file}" alt="${fileName}" 
                             class="w-full h-48 object-contain bg-gray-100 hover:opacity-90 transition"
                             onerror="this.parentElement.innerHTML='<div class=\\'p-8 text-center text-gray-500\\'><i class=\\'fas fa-image-slash text-4xl mb-2\\'></i><p>Không thể tải ảnh</p></div>'">
                    </a>
                    <div class="p-3 bg-gray-50 border-t border-gray-200">
                        <p class="text-sm text-gray-600 truncate">${fileName}</p>
                        <a href="${file}" target="_blank" class="text-xs text-primary-600 hover:underline">
                            <i class="fas fa-external-link-alt mr-1"></i>Mở trong tab mới
                        </a>
                    </div>
                </div>
            `;
        } else {
            const icon = ext === 'pdf' ? 'fa-file-pdf text-red-500' : 'fa-file-word text-blue-500';
            html += `
                <div class="border border-gray-200 rounded-lg p-4 flex items-center gap-4 hover:bg-gray-50 transition">
                    <i class="fas ${icon} text-4xl"></i>
                    <div class="flex-1 min-w-0">
                        <p class="font-medium text-gray-900 truncate">${fileName}</p>
                        <a href="${file}" target="_blank" class="text-sm text-primary-600 hover:underline">
                            <i class="fas fa-download mr-1"></i>Tải xuống
                        </a>
                    </div>
                </div>
            `;
        }
    });
    
    html += '</div>';
    content.innerHTML = html;
    modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeAttachmentsModal() {
    document.getElementById('attachmentsModal').classList.add('hidden');
    document.body.style.overflow = 'auto';
}

// Close modal on outside click
document.getElementById('attachmentsModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeAttachmentsModal();
    }
});

// Close modal on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeAttachmentsModal();
    }
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
