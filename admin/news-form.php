<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isAdmin()) {
    redirect(SITE_URL . '/admin/login.php');
}

$db = getDB();
$message = '';
$error = '';
$news = null;

// Edit mode
$editId = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($editId) {
    $stmt = $db->prepare("SELECT * FROM tin_tuc WHERE id = ?");
    $stmt->execute([$editId]);
    $news = $stmt->fetch();
    if (!$news) {
        redirect(SITE_URL . '/admin/news.php');
    }
}

// Handle form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $summary = trim($_POST['summary'] ?? '');
    $content = $_POST['content'] ?? '';
    $tags = trim($_POST['tags'] ?? '');
    $status = $_POST['status'] ?? 'draft';
    
    // Validate
    if (empty($title)) {
        $error = 'Vui lòng nhập tiêu đề tin tức';
    } elseif (empty($content)) {
        $error = 'Vui lòng nhập nội dung tin tức';
    } else {
        // Generate slug
        $slug = createSlug($title);
        
        // Handle image upload
        $cover_image = $news['cover_image'] ?? null;
        if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = UPLOAD_PATH . '/news/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $ext = pathinfo($_FILES['cover_image']['name'], PATHINFO_EXTENSION);
            $filename = 'news_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
            
            if (move_uploaded_file($_FILES['cover_image']['tmp_name'], $uploadDir . $filename)) {
                $cover_image = 'news/' . $filename;
            }
        }
        
        // Set published_at
        $published_at = null;
        if ($status === 'published') {
            $published_at = $news['published_at'] ?? date('Y-m-d H:i:s');
        }
        
        try {
            if ($editId) {
                // Update
                $stmt = $db->prepare("
                    UPDATE tin_tuc SET title = ?, slug = ?, summary = ?, content = ?, cover_image = ?, tags = ?, status = ?, published_at = ?
                    WHERE id = ?
                ");
                $stmt->execute([$title, $slug, $summary, $content, $cover_image, $tags, $status, $published_at, $editId]);
                $message = 'Cập nhật tin tức thành công!';
                
                // Reload data
                $stmt = $db->prepare("SELECT * FROM tin_tuc WHERE id = ?");
                $stmt->execute([$editId]);
                $news = $stmt->fetch();
            } else {
                // Insert
                $stmt = $db->prepare("
                    INSERT INTO tin_tuc (admin_id, title, slug, summary, content, cover_image, tags, status, published_at, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ");
                $stmt->execute([$_SESSION['admin_id'] ?? null, $title, $slug, $summary, $content, $cover_image, $tags, $status, $published_at]);
                $message = 'Thêm tin tức thành công!';
                redirect(SITE_URL . '/admin/news.php');
            }
        } catch (Exception $e) {
            $error = 'Lỗi: ' . $e->getMessage();
        }
    }
}

// Helper function to create slug
function createSlug($str) {
    $str = trim(mb_strtolower($str));
    $str = preg_replace('/(à|á|ạ|ả|ã|â|ầ|ấ|ậ|ẩ|ẫ|ă|ằ|ắ|ặ|ẳ|ẵ)/', 'a', $str);
    $str = preg_replace('/(è|é|ẹ|ẻ|ẽ|ê|ề|ế|ệ|ể|ễ)/', 'e', $str);
    $str = preg_replace('/(ì|í|ị|ỉ|ĩ)/', 'i', $str);
    $str = preg_replace('/(ò|ó|ọ|ỏ|õ|ô|ồ|ố|ộ|ổ|ỗ|ơ|ờ|ớ|ợ|ở|ỡ)/', 'o', $str);
    $str = preg_replace('/(ù|ú|ụ|ủ|ũ|ư|ừ|ứ|ự|ử|ữ)/', 'u', $str);
    $str = preg_replace('/(ỳ|ý|ỵ|ỷ|ỹ)/', 'y', $str);
    $str = preg_replace('/(đ)/', 'd', $str);
    $str = preg_replace('/[^a-z0-9-\s]/', '', $str);
    $str = preg_replace('/([\s]+)/', '-', $str);
    return $str . '-' . time();
}

$pageTitle = $editId ? 'Sửa tin tức' : 'Thêm tin tức';
include __DIR__ . '/includes/header.php';
?>

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

<form method="POST" enctype="multipart/form-data" class="space-y-6">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <div class="card-hover bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
                <h3 class="text-lg font-bold text-slate-800 mb-4">Thông tin tin tức</h3>
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Tiêu đề <span class="text-red-500">*</span></label>
                        <input type="text" name="title" value="<?php echo htmlspecialchars($news['title'] ?? ''); ?>" 
                               class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:border-primary-500 focus:ring-2 focus:ring-primary-100 outline-none transition"
                               placeholder="Nhập tiêu đề tin tức" required>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Tóm tắt</label>
                        <textarea name="summary" rows="3" 
                                  class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:border-primary-500 focus:ring-2 focus:ring-primary-100 outline-none transition resize-none"
                                  placeholder="Mô tả ngắn gọn về tin tức"><?php echo htmlspecialchars($news['summary'] ?? ''); ?></textarea>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Nội dung <span class="text-red-500">*</span></label>
                        <textarea name="content" rows="15" id="content"
                                  class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:border-primary-500 focus:ring-2 focus:ring-primary-100 outline-none transition"
                                  placeholder="Nội dung chi tiết tin tức" required><?php echo htmlspecialchars($news['content'] ?? ''); ?></textarea>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Tags</label>
                        <input type="text" name="tags" value="<?php echo htmlspecialchars($news['tags'] ?? ''); ?>" 
                               class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:border-primary-500 focus:ring-2 focus:ring-primary-100 outline-none transition"
                               placeholder="Nhập tags, phân cách bởi dấu phẩy (VD: thể thao, bóng đá, tin mới)">
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Status -->
            <div class="card-hover bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
                <h3 class="text-lg font-bold text-slate-800 mb-4">Trạng thái</h3>
                <select name="status" class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:border-primary-500 outline-none bg-white">
                    <option value="draft" <?php echo ($news['status'] ?? 'draft') === 'draft' ? 'selected' : ''; ?>>Bản nháp</option>
                    <option value="published" <?php echo ($news['status'] ?? '') === 'published' ? 'selected' : ''; ?>>Xuất bản</option>
                    <option value="archived" <?php echo ($news['status'] ?? '') === 'archived' ? 'selected' : ''; ?>>Lưu trữ</option>
                </select>
            </div>
            
            <!-- Image -->
            <div class="card-hover bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
                <h3 class="text-lg font-bold text-slate-800 mb-4">Ảnh bìa</h3>
                
                <?php if (!empty($news['cover_image'])): ?>
                <div class="mb-4">
                    <img src="<?php echo UPLOAD_URL . '/' . $news['cover_image']; ?>" class="w-full h-40 object-cover rounded-xl" id="preview-img">
                </div>
                <?php endif; ?>
                
                <label class="block">
                    <div class="border-2 border-dashed border-slate-200 rounded-xl p-6 text-center hover:border-primary-400 transition cursor-pointer">
                        <i class="fas fa-cloud-upload-alt text-3xl text-slate-400 mb-2"></i>
                        <p class="text-sm text-slate-500">Click để chọn ảnh</p>
                        <p class="text-xs text-slate-400 mt-1">PNG, JPG, WEBP (Max 5MB)</p>
                    </div>
                    <input type="file" name="cover_image" accept="image/*" class="hidden" id="cover-input">
                </label>
            </div>
            
            <!-- Actions -->
            <div class="flex gap-3">
                <a href="<?php echo SITE_URL; ?>/admin/news.php" 
                   class="flex-1 bg-slate-100 hover:bg-slate-200 text-slate-700 px-5 py-3 rounded-xl font-medium text-center transition">
                    <i class="fas fa-arrow-left mr-2"></i>Quay lại
                </a>
                <button type="submit" 
                        class="flex-1 bg-gradient-to-r from-primary-500 to-primary-600 hover:from-primary-600 hover:to-primary-700 text-white px-5 py-3 rounded-xl font-medium shadow-lg shadow-primary-500/30 transition">
                    <i class="fas fa-save mr-2"></i><?php echo $editId ? 'Cập nhật' : 'Thêm mới'; ?>
                </button>
            </div>
        </div>
    </div>
</form>

<!-- TinyMCE Editor (Self-hosted CDN - không cần API key) -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.8.2/tinymce.min.js"></script>
<script>
tinymce.init({
    selector: '#content',
    height: 500,
    language: 'vi',
    plugins: [
        'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
        'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
        'insertdatetime', 'media', 'table', 'help', 'wordcount'
    ],
    toolbar: 'undo redo | blocks | bold italic forecolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | removeformat | image media link | code fullscreen',
    menubar: 'file edit view insert format tools table help',
    content_style: 'body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; font-size: 16px; line-height: 1.6; }',
    
    // Cho phép paste ảnh trực tiếp
    paste_data_images: true,
    automatic_uploads: true,
    
    // Upload ảnh khi paste hoặc drag
    images_upload_handler: function (blobInfo, progress) {
        return new Promise((resolve, reject) => {
            const formData = new FormData();
            formData.append('file', blobInfo.blob(), blobInfo.filename());
            
            fetch('<?php echo SITE_URL; ?>/api/upload-news-image.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    resolve(result.location);
                } else {
                    reject(result.message || 'Upload failed');
                }
            })
            .catch(error => {
                reject('Upload failed: ' + error.message);
            });
        });
    },
    
    // Cấu hình cho phép paste HTML
    paste_as_text: false,
    paste_enable_default_filters: true
});

// Preview cover image
document.getElementById('cover-input').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            let preview = document.getElementById('preview-img');
            if (!preview) {
                preview = document.createElement('img');
                preview.id = 'preview-img';
                preview.className = 'w-full h-40 object-cover rounded-xl mb-4';
                const container = document.getElementById('cover-input').closest('.card-hover');
                container.querySelector('h3').after(preview);
            }
            preview.src = e.target.result;
        };
        reader.readAsDataURL(file);
    }
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
