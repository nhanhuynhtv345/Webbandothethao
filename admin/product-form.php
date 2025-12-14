<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isAdmin()) {
    redirect(SITE_URL . '/admin/login.php');
}

$db = getDB();
$message = '';
$error = '';

$id = intval($_GET['id'] ?? 0);
$isEdit = $id > 0;

// Handle delete image
if (isset($_GET['delete_image']) && $id > 0) {
    $imageId = intval($_GET['delete_image']);
    try {
        // Lấy thông tin ảnh trước khi xóa
        $imgStmt = $db->prepare("SELECT * FROM hinh_anh_san_pham WHERE id = ? AND san_pham_id = ?");
        $imgStmt->execute([$imageId, $id]);
        $imageToDelete = $imgStmt->fetch();
        
        if ($imageToDelete) {
            // Xóa file ảnh trên server
            $filePath = UPLOAD_PATH . '/' . $imageToDelete['url'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            
            // Xóa record trong database
            $db->prepare("DELETE FROM hinh_anh_san_pham WHERE id = ?")->execute([$imageId]);
            
            // Nếu ảnh bị xóa là ảnh chính, đặt ảnh đầu tiên còn lại làm ảnh chính
            if ($imageToDelete['is_primary']) {
                $db->prepare("UPDATE hinh_anh_san_pham SET is_primary = 1 WHERE san_pham_id = ? ORDER BY id ASC LIMIT 1")
                   ->execute([$id]);
            }
            
            $message = 'Xóa ảnh thành công!';
        }
    } catch (Exception $e) {
        $error = 'Lỗi khi xóa ảnh: ' . $e->getMessage();
    }
    
    // Redirect để xóa parameter khỏi URL
    redirect(SITE_URL . '/admin/product-form.php?id=' . $id . ($message ? '&msg=deleted' : ''));
}

// Handle set primary image
if (isset($_GET['set_primary']) && $id > 0) {
    $imageId = intval($_GET['set_primary']);
    try {
        // Bỏ tất cả ảnh chính cũ
        $db->prepare("UPDATE hinh_anh_san_pham SET is_primary = 0 WHERE san_pham_id = ?")->execute([$id]);
        // Đặt ảnh mới làm ảnh chính
        $db->prepare("UPDATE hinh_anh_san_pham SET is_primary = 1 WHERE id = ? AND san_pham_id = ?")->execute([$imageId, $id]);
        $message = 'Đã đặt làm ảnh chính!';
    } catch (Exception $e) {
        $error = 'Lỗi: ' . $e->getMessage();
    }
    redirect(SITE_URL . '/admin/product-form.php?id=' . $id);
}

// Get product if editing
$product = null;
$productImages = [];
if ($isEdit) {
    $stmt = $db->prepare("SELECT * FROM san_pham WHERE id = ?");
    $stmt->execute([$id]);
    $product = $stmt->fetch();
    
    if (!$product) {
        redirect(SITE_URL . '/admin/products.php');
    }
    
    // Get images
    $imgStmt = $db->prepare("SELECT * FROM hinh_anh_san_pham WHERE san_pham_id = ? ORDER BY is_primary DESC, sort_order ASC");
    $imgStmt->execute([$id]);
    $productImages = $imgStmt->fetchAll();
}

// Check for message from redirect
if (isset($_GET['msg']) && $_GET['msg'] === 'deleted') {
    $message = 'Xóa ảnh thành công!';
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'ma_san_pham' => trim($_POST['ma_san_pham']),
        'ten_san_pham' => trim($_POST['ten_san_pham']),
        'slug' => generateSlug($_POST['ten_san_pham']),
        'danh_muc_id' => intval($_POST['danh_muc_id']) ?: null,
        'thuong_hieu_id' => intval($_POST['thuong_hieu_id']) ?: null,
        'gia_goc' => intval($_POST['gia_goc']) ?: 0,
        'gia_ban' => intval($_POST['gia_ban']),
        'so_luong_ton' => intval($_POST['so_luong_ton']),
        'mo_ta_ngan' => trim($_POST['mo_ta_ngan']),
        'mo_ta_chi_tiet' => trim($_POST['mo_ta_chi_tiet']),
        'chat_lieu' => trim($_POST['chat_lieu']),
        'mau_sac' => trim($_POST['mau_sac']),
        'kich_thuoc' => trim($_POST['kich_thuoc']),
        'trong_luong' => intval($_POST['trong_luong']) ?: null,
        'xuat_xu' => trim($_POST['xuat_xu']),
        'bao_hanh' => trim($_POST['bao_hanh']),
        'trang_thai' => $_POST['trang_thai'],
        'san_pham_moi' => isset($_POST['san_pham_moi']) ? 1 : 0,
        'noi_bat' => isset($_POST['noi_bat']) ? 1 : 0,
    ];
    
    try {
        if ($isEdit) {
            // Update
            $sql = "UPDATE san_pham SET 
                    ma_san_pham = ?, ten_san_pham = ?, slug = ?, danh_muc_id = ?, thuong_hieu_id = ?,
                    gia_goc = ?, gia_ban = ?, so_luong_ton = ?, mo_ta_ngan = ?, mo_ta_chi_tiet = ?,
                    chat_lieu = ?, mau_sac = ?, kich_thuoc = ?, trong_luong = ?, xuat_xu = ?,
                    bao_hanh = ?, trang_thai = ?, san_pham_moi = ?, noi_bat = ?, updated_at = NOW()
                    WHERE id = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute([
                $data['ma_san_pham'], $data['ten_san_pham'], $data['slug'], $data['danh_muc_id'], $data['thuong_hieu_id'],
                $data['gia_goc'], $data['gia_ban'], $data['so_luong_ton'], $data['mo_ta_ngan'], $data['mo_ta_chi_tiet'],
                $data['chat_lieu'], $data['mau_sac'], $data['kich_thuoc'], $data['trong_luong'], $data['xuat_xu'],
                $data['bao_hanh'], $data['trang_thai'], $data['san_pham_moi'], $data['noi_bat'], $id
            ]);
            $productId = $id;
            $message = 'Cập nhật sản phẩm thành công!';
        } else {
            // Insert
            $sql = "INSERT INTO san_pham (ma_san_pham, ten_san_pham, slug, danh_muc_id, thuong_hieu_id,
                    gia_goc, gia_ban, so_luong_ton, mo_ta_ngan, mo_ta_chi_tiet, chat_lieu, mau_sac,
                    kich_thuoc, trong_luong, xuat_xu, bao_hanh, trang_thai, san_pham_moi, noi_bat, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            $stmt = $db->prepare($sql);
            $stmt->execute([
                $data['ma_san_pham'], $data['ten_san_pham'], $data['slug'], $data['danh_muc_id'], $data['thuong_hieu_id'],
                $data['gia_goc'], $data['gia_ban'], $data['so_luong_ton'], $data['mo_ta_ngan'], $data['mo_ta_chi_tiet'],
                $data['chat_lieu'], $data['mau_sac'], $data['kich_thuoc'], $data['trong_luong'], $data['xuat_xu'],
                $data['bao_hanh'], $data['trang_thai'], $data['san_pham_moi'], $data['noi_bat']
            ]);
            $productId = $db->lastInsertId();
            $message = 'Thêm sản phẩm thành công!';
        }
        
        // Handle image upload
        if (!empty($_FILES['images']['name'][0])) {
            $uploadDir = UPLOAD_PATH . '/products';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            foreach ($_FILES['images']['tmp_name'] as $key => $tmpName) {
                if ($_FILES['images']['error'][$key] === UPLOAD_ERR_OK) {
                    $ext = pathinfo($_FILES['images']['name'][$key], PATHINFO_EXTENSION);
                    $filename = uniqid() . '_' . time() . '.' . $ext;
                    $filepath = $uploadDir . '/' . $filename;
                    
                    if (move_uploaded_file($tmpName, $filepath)) {
                        $isPrimary = empty($productImages) && $key === 0 ? 1 : 0;
                        $db->prepare("INSERT INTO hinh_anh_san_pham (san_pham_id, url, is_primary, sort_order) VALUES (?, ?, ?, ?)")
                           ->execute([$productId, 'products/' . $filename, $isPrimary, $key]);
                    }
                }
            }
        }
        
        // Redirect after success
        if (!$isEdit) {
            redirect(SITE_URL . '/admin/product-form.php?id=' . $productId . '&success=1');
        }
        
    } catch (Exception $e) {
        $error = 'Lỗi: ' . $e->getMessage();
    }
}

if (isset($_GET['success'])) {
    $message = 'Thêm sản phẩm thành công!';
}

// Get categories and brands
$categories = $db->query("SELECT * FROM danh_muc WHERE active = 1 ORDER BY ten_danh_muc")->fetchAll();
$brands = $db->query("SELECT * FROM thuong_hieu WHERE active = 1 ORDER BY ten_thuong_hieu")->fetchAll();

$pageTitle = $isEdit ? 'Sửa sản phẩm' : 'Thêm sản phẩm';
include __DIR__ . '/includes/header.php';
?>

<?php if ($message): ?>
<div class="bg-green-100 text-green-700 p-4 rounded mb-6"><?php echo $message; ?></div>
<?php endif; ?>

<?php if ($error): ?>
<div class="bg-red-100 text-red-700 p-4 rounded mb-6"><?php echo $error; ?></div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data" class="space-y-6">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Info -->
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold mb-4">Thông tin cơ bản</h3>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Mã sản phẩm *</label>
                        <input type="text" name="ma_san_pham" required
                               value="<?php echo htmlspecialchars($product['ma_san_pham'] ?? 'SP' . time()); ?>"
                               class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tên sản phẩm *</label>
                        <input type="text" name="ten_san_pham" required
                               value="<?php echo htmlspecialchars($product['ten_san_pham'] ?? ''); ?>"
                               class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>
                
                <div class="grid grid-cols-2 gap-4 mt-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Danh mục</label>
                        <select name="danh_muc_id" class="w-full px-4 py-2 border rounded-lg">
                            <option value="">-- Chọn danh mục --</option>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>" <?php echo ($product['danh_muc_id'] ?? '') == $cat['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['ten_danh_muc']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Thương hiệu</label>
                        <select name="thuong_hieu_id" class="w-full px-4 py-2 border rounded-lg">
                            <option value="">-- Chọn thương hiệu --</option>
                            <?php foreach ($brands as $brand): ?>
                            <option value="<?php echo $brand['id']; ?>" <?php echo ($product['thuong_hieu_id'] ?? '') == $brand['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($brand['ten_thuong_hieu']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="mt-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Mô tả ngắn</label>
                    <textarea name="mo_ta_ngan" rows="3" class="w-full px-4 py-2 border rounded-lg"><?php echo htmlspecialchars($product['mo_ta_ngan'] ?? ''); ?></textarea>
                </div>
                
                <div class="mt-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Mô tả chi tiết</label>
                    <textarea name="mo_ta_chi_tiet" rows="6" class="w-full px-4 py-2 border rounded-lg"><?php echo htmlspecialchars($product['mo_ta_chi_tiet'] ?? ''); ?></textarea>
                </div>
            </div>
            
            <!-- Specs -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold mb-4">Thông số kỹ thuật</h3>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Chất liệu</label>
                        <input type="text" name="chat_lieu" value="<?php echo htmlspecialchars($product['chat_lieu'] ?? ''); ?>"
                               class="w-full px-4 py-2 border rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Màu sắc</label>
                        <input type="text" name="mau_sac" value="<?php echo htmlspecialchars($product['mau_sac'] ?? ''); ?>"
                               class="w-full px-4 py-2 border rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Kích thước</label>
                        <input type="text" name="kich_thuoc" value="<?php echo htmlspecialchars($product['kich_thuoc'] ?? ''); ?>"
                               class="w-full px-4 py-2 border rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Trọng lượng (gram)</label>
                        <input type="number" name="trong_luong" value="<?php echo $product['trong_luong'] ?? ''; ?>"
                               class="w-full px-4 py-2 border rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Xuất xứ</label>
                        <input type="text" name="xuat_xu" value="<?php echo htmlspecialchars($product['xuat_xu'] ?? ''); ?>"
                               class="w-full px-4 py-2 border rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Bảo hành</label>
                        <input type="text" name="bao_hanh" value="<?php echo htmlspecialchars($product['bao_hanh'] ?? ''); ?>"
                               class="w-full px-4 py-2 border rounded-lg">
                    </div>
                </div>
            </div>
            
            <!-- Images -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold mb-4">Hình ảnh sản phẩm</h3>
                
                <?php if (!empty($productImages)): ?>
                <div class="grid grid-cols-6 gap-4 mb-4">
                    <?php foreach ($productImages as $img): ?>
                    <div class="relative group border rounded overflow-hidden">
                        <img src="<?php echo UPLOAD_URL . '/' . $img['url']; ?>" class="w-full h-24 object-cover">
                        
                        <!-- Nút xóa ảnh -->
                        <a href="?id=<?php echo $id; ?>&delete_image=<?php echo $img['id']; ?>" 
                           onclick="return confirm('Bạn có chắc muốn xóa ảnh này?')"
                           class="absolute top-1 right-1 bg-red-500 text-white w-6 h-6 rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity"
                           title="Xóa ảnh">
                            <i class="fas fa-times text-xs"></i>
                        </a>
                        
                        <?php if ($img['is_primary']): ?>
                        <!-- Badge ảnh chính -->
                        <span class="absolute bottom-1 left-1 bg-green-500 text-white text-xs px-2 py-0.5 rounded">Chính</span>
                        <?php else: ?>
                        <!-- Nút đặt làm ảnh chính -->
                        <a href="?id=<?php echo $id; ?>&set_primary=<?php echo $img['id']; ?>" 
                           class="absolute bottom-1 left-1 bg-blue-500 text-white text-xs px-2 py-0.5 rounded opacity-0 group-hover:opacity-100 transition-opacity"
                           title="Đặt làm ảnh chính">
                            Đặt chính
                        </a>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <p class="text-sm text-gray-500 mb-4">
                    <i class="fas fa-info-circle mr-1"></i>
                    Di chuột vào ảnh để xóa hoặc đặt làm ảnh chính
                </p>
                <?php endif; ?>
                
                <input type="file" name="images[]" multiple accept="image/*" class="w-full px-4 py-2 border rounded-lg">
                <p class="text-sm text-gray-500 mt-2">Có thể chọn nhiều ảnh. Định dạng: JPG, PNG, GIF, WebP</p>
            </div>
        </div>
        
        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Price & Stock -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold mb-4">Giá & Kho hàng</h3>
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Giá gốc</label>
                        <input type="number" name="gia_goc" value="<?php echo $product['gia_goc'] ?? ''; ?>"
                               class="w-full px-4 py-2 border rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Giá bán *</label>
                        <input type="number" name="gia_ban" required value="<?php echo $product['gia_ban'] ?? ''; ?>"
                               class="w-full px-4 py-2 border rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Số lượng tồn *</label>
                        <input type="number" name="so_luong_ton" required value="<?php echo $product['so_luong_ton'] ?? 0; ?>"
                               class="w-full px-4 py-2 border rounded-lg">
                    </div>
                </div>
            </div>
            
            <!-- Status -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold mb-4">Trạng thái</h3>
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Trạng thái</label>
                        <select name="trang_thai" class="w-full px-4 py-2 border rounded-lg">
                            <option value="active" <?php echo ($product['trang_thai'] ?? 'active') === 'active' ? 'selected' : ''; ?>>Đang bán</option>
                            <option value="inactive" <?php echo ($product['trang_thai'] ?? '') === 'inactive' ? 'selected' : ''; ?>>Ngừng bán</option>
                        </select>
                    </div>
                    
                    <div class="flex items-center gap-2">
                        <input type="checkbox" name="san_pham_moi" id="san_pham_moi" value="1"
                               <?php echo ($product['san_pham_moi'] ?? 0) ? 'checked' : ''; ?>
                               class="w-4 h-4 text-blue-600 rounded">
                        <label for="san_pham_moi" class="text-sm text-gray-700">Sản phẩm mới</label>
                    </div>
                    
                    <div class="flex items-center gap-2">
                        <input type="checkbox" name="noi_bat" id="noi_bat" value="1"
                               <?php echo ($product['noi_bat'] ?? 0) ? 'checked' : ''; ?>
                               class="w-4 h-4 text-blue-600 rounded">
                        <label for="noi_bat" class="text-sm text-gray-700">Sản phẩm nổi bật</label>
                    </div>
                </div>
            </div>
            
            <!-- Actions -->
            <div class="bg-white rounded-lg shadow p-6">
                <button type="submit" class="w-full bg-blue-600 text-white py-3 rounded-lg font-semibold hover:bg-blue-700 mb-3">
                    <i class="fas fa-save mr-2"></i><?php echo $isEdit ? 'Cập nhật' : 'Thêm sản phẩm'; ?>
                </button>
                <a href="<?php echo SITE_URL; ?>/admin/products.php" class="block text-center text-gray-600 hover:text-gray-800">
                    Hủy bỏ
                </a>
            </div>
        </div>
    </div>
</form>

<?php include __DIR__ . '/includes/footer.php'; ?>
