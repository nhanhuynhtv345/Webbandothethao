<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isAdmin()) {
    redirect(SITE_URL . '/admin/login.php');
}

$db = getDB();
$message = '';
$error = '';

// Handle delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    try {
        $db->prepare("DELETE FROM thuong_hieu WHERE id = ?")->execute([$id]);
        $message = 'Xóa thương hiệu thành công!';
    } catch (Exception $e) {
        $error = 'Không thể xóa thương hiệu này vì có sản phẩm liên quan.';
    }
}

// Handle add/edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id'] ?? 0);
    $ten = trim($_POST['ten_thuong_hieu']);
    $slug = generateSlug($ten);
    $moTa = trim($_POST['mo_ta'] ?? '');
    $active = isset($_POST['active']) ? 1 : 0;
    
    // Handle logo upload
    $logo = null;
    if (!empty($_FILES['logo']['name']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = UPLOAD_PATH . '/brands';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $ext = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
        $filename = $slug . '_' . time() . '.' . $ext;
        if (move_uploaded_file($_FILES['logo']['tmp_name'], $uploadDir . '/' . $filename)) {
            $logo = 'brands/' . $filename;
        }
    }
    
    try {
        if ($id > 0) {
            if ($logo) {
                $db->prepare("UPDATE thuong_hieu SET ten_thuong_hieu = ?, slug = ?, mo_ta = ?, logo = ?, active = ? WHERE id = ?")
                   ->execute([$ten, $slug, $moTa, $logo, $active, $id]);
            } else {
                $db->prepare("UPDATE thuong_hieu SET ten_thuong_hieu = ?, slug = ?, mo_ta = ?, active = ? WHERE id = ?")
                   ->execute([$ten, $slug, $moTa, $active, $id]);
            }
            $message = 'Cập nhật thương hiệu thành công!';
        } else {
            $db->prepare("INSERT INTO thuong_hieu (ten_thuong_hieu, slug, mo_ta, logo, active) VALUES (?, ?, ?, ?, ?)")
               ->execute([$ten, $slug, $moTa, $logo, $active]);
            $message = 'Thêm thương hiệu thành công!';
        }
    } catch (Exception $e) {
        $error = 'Lỗi: ' . $e->getMessage();
    }
}

// Get all brands
$brands = $db->query("
    SELECT th.*, (SELECT COUNT(*) FROM san_pham WHERE thuong_hieu_id = th.id) as product_count 
    FROM thuong_hieu th ORDER BY th.ten_thuong_hieu
")->fetchAll();

// Get brand for edit
$editBrand = null;
if (isset($_GET['edit'])) {
    $stmt = $db->prepare("SELECT * FROM thuong_hieu WHERE id = ?");
    $stmt->execute([intval($_GET['edit'])]);
    $editBrand = $stmt->fetch();
}

$pageTitle = 'Quản lý thương hiệu';
include __DIR__ . '/includes/header.php';
?>

<?php if ($message): ?>
<div class="bg-green-100 text-green-700 p-4 rounded mb-6"><?php echo $message; ?></div>
<?php endif; ?>

<?php if ($error): ?>
<div class="bg-red-100 text-red-700 p-4 rounded mb-6"><?php echo $error; ?></div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Form -->
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold mb-4"><?php echo $editBrand ? 'Sửa thương hiệu' : 'Thêm thương hiệu'; ?></h3>
        
        <form method="POST" enctype="multipart/form-data" class="space-y-4">
            <input type="hidden" name="id" value="<?php echo $editBrand['id'] ?? ''; ?>">
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tên thương hiệu *</label>
                <input type="text" name="ten_thuong_hieu" required
                       value="<?php echo htmlspecialchars($editBrand['ten_thuong_hieu'] ?? ''); ?>"
                       class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Mô tả</label>
                <textarea name="mo_ta" rows="3" class="w-full px-4 py-2 border rounded-lg"><?php echo htmlspecialchars($editBrand['mo_ta'] ?? ''); ?></textarea>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Logo</label>
                <?php if ($editBrand && $editBrand['logo']): ?>
                <img src="<?php echo UPLOAD_URL . '/' . $editBrand['logo']; ?>" class="w-20 h-20 object-contain mb-2 border rounded">
                <?php endif; ?>
                <input type="file" name="logo" accept="image/*" class="w-full px-4 py-2 border rounded-lg">
            </div>
            
            <div class="flex items-center gap-2">
                <input type="checkbox" name="active" id="active" value="1"
                       <?php echo ($editBrand['active'] ?? 1) ? 'checked' : ''; ?>
                       class="w-4 h-4 text-blue-600 rounded">
                <label for="active" class="text-sm text-gray-700">Kích hoạt</label>
            </div>
            
            <div class="flex gap-2">
                <button type="submit" class="flex-1 bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700">
                    <i class="fas fa-save mr-1"></i><?php echo $editBrand ? 'Cập nhật' : 'Thêm mới'; ?>
                </button>
                <?php if ($editBrand): ?>
                <a href="<?php echo SITE_URL; ?>/admin/brands.php" class="px-4 py-2 border rounded-lg hover:bg-gray-50">Hủy</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
    
    <!-- List -->
    <div class="lg:col-span-2 bg-white rounded-lg shadow overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-600">Logo</th>
                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-600">Tên thương hiệu</th>
                    <th class="px-4 py-3 text-center text-sm font-semibold text-gray-600">Sản phẩm</th>
                    <th class="px-4 py-3 text-center text-sm font-semibold text-gray-600">Trạng thái</th>
                    <th class="px-4 py-3 text-center text-sm font-semibold text-gray-600">Thao tác</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                <?php if (empty($brands)): ?>
                <tr>
                    <td colspan="5" class="px-4 py-8 text-center text-gray-500">Chưa có thương hiệu nào</td>
                </tr>
                <?php else: ?>
                <?php foreach ($brands as $brand): ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3">
                        <?php if ($brand['logo']): ?>
                        <img src="<?php echo UPLOAD_URL . '/' . $brand['logo']; ?>" class="w-10 h-10 object-contain">
                        <?php else: ?>
                        <div class="w-10 h-10 bg-gray-200 rounded flex items-center justify-center">
                            <i class="fas fa-image text-gray-400"></i>
                        </div>
                        <?php endif; ?>
                    </td>
                    <td class="px-4 py-3 font-medium"><?php echo htmlspecialchars($brand['ten_thuong_hieu']); ?></td>
                    <td class="px-4 py-3 text-center"><?php echo $brand['product_count']; ?></td>
                    <td class="px-4 py-3 text-center">
                        <span class="px-2 py-1 text-xs rounded <?php echo $brand['active'] ? 'bg-green-100 text-green-600' : 'bg-gray-100 text-gray-600'; ?>">
                            <?php echo $brand['active'] ? 'Hoạt động' : 'Ẩn'; ?>
                        </span>
                    </td>
                    <td class="px-4 py-3 text-center">
                        <a href="?edit=<?php echo $brand['id']; ?>" class="text-blue-600 hover:text-blue-800 mx-1">
                            <i class="fas fa-edit"></i>
                        </a>
                        <a href="?delete=<?php echo $brand['id']; ?>" onclick="return confirmDelete()" class="text-red-600 hover:text-red-800 mx-1">
                            <i class="fas fa-trash"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
