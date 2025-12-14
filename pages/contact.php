<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

$pageTitle = 'Liên hệ';
$success = '';
$error = '';
$currentUser = isLoggedIn() ? getCurrentUser() : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if user is logged in
    if (!isLoggedIn()) {
        $error = 'Bạn phải đăng nhập tài khoản để gửi liên hệ!';
    } else {
        $name = sanitize($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = sanitize($_POST['phone'] ?? '');
        $subject = sanitize($_POST['subject'] ?? '');
        $message = sanitize($_POST['message'] ?? '');
        $attachments = [];
    
    // Handle file uploads (multiple files)
    if (isset($_FILES['attachments']) && $_FILES['attachments']['error'][0] !== UPLOAD_ERR_NO_FILE) {
        $uploadDir = __DIR__ . '/../assets/uploads/contact/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
        $maxFileSize = 5 * 1024 * 1024; // 5MB
        
        foreach ($_FILES['attachments']['tmp_name'] as $key => $tmpName) {
            if ($_FILES['attachments']['error'][$key] === UPLOAD_ERR_OK) {
                $fileSize = $_FILES['attachments']['size'][$key];
                $fileType = $_FILES['attachments']['type'][$key];
                $fileName = $_FILES['attachments']['name'][$key];
                
                if ($fileSize > $maxFileSize) {
                    $error = "File {$fileName} vượt quá 5MB!";
                    break;
                }
                
                if (!in_array($fileType, $allowedTypes)) {
                    $error = "File {$fileName} không đúng định dạng cho phép!";
                    break;
                }
                
                $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                $newFileName = uniqid('contact_') . '.' . $fileExtension;
                $targetPath = $uploadDir . $newFileName;
                
                if (move_uploaded_file($tmpName, $targetPath)) {
                    $attachments[] = '/Web/assets/uploads/contact/' . $newFileName;
                }
            }
        }
    }
    
    if (empty($error)) {
        if (empty($name) || empty($email) || empty($subject) || empty($message)) {
            $error = 'Vui lòng điền đầy đủ thông tin bắt buộc';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Email không hợp lệ';
        } elseif (strlen($message) < 10) {
            $error = 'Nội dung tin nhắn phải có ít nhất 10 ký tự';
        } else {
            try {
                $db = getDB();
                $userId = isLoggedIn() ? $_SESSION['user_id'] : null;
                $attachmentsJson = !empty($attachments) ? json_encode($attachments) : null;
                
                // Check if table exists
                $tableCheck = $db->query("SHOW TABLES LIKE 'lien_he'");
                if ($tableCheck->rowCount() === 0) {
                    // Create table if not exists
                    try {
                        $db->exec("
                            CREATE TABLE lien_he (
                                id INT AUTO_INCREMENT PRIMARY KEY,
                                user_id INT NULL,
                                name VARCHAR(255) NOT NULL,
                                email VARCHAR(255) NOT NULL,
                                phone VARCHAR(20) NULL,
                                subject VARCHAR(500) NOT NULL,
                                message TEXT NOT NULL,
                                attachments TEXT NULL,
                                status VARCHAR(20) DEFAULT 'pending',
                                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                                INDEX idx_user_id (user_id),
                                INDEX idx_email (email),
                                INDEX idx_status (status)
                            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                        ");
                    } catch (Exception $createError) {
                        error_log('Create table lien_he error: ' . $createError->getMessage());
                    }
                } else {
                    // Check if attachments column exists
                    $columns = $db->query("SHOW COLUMNS FROM lien_he LIKE 'attachments'")->fetchAll();
                    if (empty($columns)) {
                        try {
                            $db->exec("ALTER TABLE lien_he ADD COLUMN attachments TEXT NULL AFTER message");
                            error_log('Added attachments column to lien_he table');
                        } catch (Exception $alterError) {
                            error_log('Alter table lien_he error: ' . $alterError->getMessage());
                        }
                    }
                    
                    // Check if status column exists
                    $statusColumns = $db->query("SHOW COLUMNS FROM lien_he LIKE 'status'")->fetchAll();
                    if (empty($statusColumns)) {
                        try {
                            $db->exec("ALTER TABLE lien_he ADD COLUMN status ENUM('pending', 'processing', 'resolved') DEFAULT 'pending' AFTER attachments");
                        } catch (Exception $alterError) {
                            error_log('Add status column error: ' . $alterError->getMessage());
                        }
                    }
                }
                
                $stmt = $db->prepare("
                    INSERT INTO lien_he (user_id, name, email, phone, subject, message, attachments, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
                ");
                
                if ($stmt->execute([$userId, $name, $email, $phone, $subject, $message, $attachmentsJson])) {
                    $success = 'Cảm ơn bạn đã liên hệ! Chúng tôi sẽ phản hồi trong vòng 24 giờ.';
                    // Clear form data
                    $_POST = [];
                } else {
                    $errorInfo = $stmt->errorInfo();
                    error_log('Insert contact error: ' . print_r($errorInfo, true));
                    $error = 'Có lỗi xảy ra khi gửi tin nhắn. Vui lòng thử lại!';
                }
            } catch (Exception $e) {
                error_log('Contact form exception: ' . $e->getMessage());
                $error = 'Lỗi: ' . $e->getMessage();
            }
        }
    }
    }
}

include __DIR__ . '/../components/header.php';
?>

<!-- Breadcrumb -->
<div class="bg-gray-100 py-4">
    <div class="container mx-auto px-4">
        <nav class="flex items-center gap-2 text-sm">
            <a href="<?php echo SITE_URL; ?>/index.php" class="text-gray-600 hover:text-primary-600">Trang chủ</a>
            <i class="fas fa-chevron-right text-gray-400 text-xs"></i>
            <span class="text-gray-900">Liên hệ</span>
        </nav>
    </div>
</div>

<div class="container mx-auto px-4 py-12">
    <div class="max-w-6xl mx-auto">
        <div class="text-center mb-12">
            <h1 class="text-4xl font-bold mb-4">Liên hệ với chúng tôi</h1>
            <p class="text-lg text-gray-600">Chúng tôi luôn sẵn sàng hỗ trợ bạn</p>
        </div>
        
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Contact Form -->
            <div class="bg-white rounded-lg shadow-md p-8">
                <h2 class="text-2xl font-bold mb-6">Gửi tin nhắn</h2>
                
                <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <?php echo $error; ?>
                </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    <?php echo $success; ?>
                </div>
                <?php endif; ?>
                
                <?php if (!isLoggedIn()): ?>
                    <!-- Not Logged In Message -->
                    <div class="bg-yellow-50 border-2 border-yellow-300 rounded-lg p-8 text-center">
                        <i class="fas fa-lock text-yellow-500 text-5xl mb-4"></i>
                        <h3 class="text-2xl font-bold text-gray-900 mb-3">Yêu cầu đăng nhập</h3>
                        <p class="text-gray-600 mb-6">Bạn phải đăng nhập tài khoản để gửi liên hệ với chúng tôi.</p>
                        <div class="flex gap-4 justify-center">
                            <a href="/Web/pages/login.php?redirect=<?php echo urlencode('/Web/pages/contact.php'); ?>" 
                               class="bg-primary-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-primary-700 transition-all">
                                <i class="fas fa-sign-in-alt mr-2"></i>
                                Đăng nhập ngay
                            </a>
                            <a href="/Web/pages/register.php" 
                               class="bg-green-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-green-700 transition-all">
                                <i class="fas fa-user-plus mr-2"></i>
                                Đăng ký tài khoản
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                
                <form method="POST" enctype="multipart/form-data" class="space-y-4" id="contactForm">
                    <!-- User Info Display -->
                    <div class="bg-primary-50 border border-primary-200 rounded-lg p-4 mb-4">
                        <div class="flex items-center gap-3">
                            <?php if (!empty($currentUser['avt'])): ?>
                                <img src="<?php echo htmlspecialchars($currentUser['avt']); ?>" 
                                     alt="Avatar" 
                                     class="w-12 h-12 rounded-full object-cover border-2 border-primary-300"
                                     onerror="this.style.display='none'">
                            <?php endif; ?>
                            <div>
                                <p class="text-sm text-gray-600">Gửi từ tài khoản:</p>
                                <p class="font-semibold text-gray-900"><?php echo htmlspecialchars($currentUser['ho_ten']); ?></p>
                                <p class="text-sm text-gray-600"><?php echo htmlspecialchars($currentUser['email']); ?></p>
                            </div>
                        </div>
                    </div>

                    <input type="hidden" name="name" value="<?php echo htmlspecialchars($currentUser['ho_ten']); ?>">
                    <input type="hidden" name="email" value="<?php echo htmlspecialchars($currentUser['email']); ?>">
                    <input type="hidden" name="phone" value="<?php echo htmlspecialchars($currentUser['so_dien_thoai'] ?? ''); ?>">
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Chủ đề <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-tag text-gray-400"></i>
                            </div>
                            <select name="subject" required
                                    class="pl-10 w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                                <option value="">-- Chọn chủ đề --</option>
                                <option value="Hỏi về sản phẩm">Hỏi về sản phẩm</option>
                                <option value="Hỗ trợ đơn hàng">Hỗ trợ đơn hàng</option>
                                <option value="Khiếu nại">Khiếu nại</option>
                                <option value="Góp ý">Góp ý</option>
                                <option value="Hợp tác">Hợp tác</option>
                                <option value="Khác">Khác</option>
                            </select>
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Nội dung <span class="text-red-500">*</span>
                        </label>
                        <textarea name="message" rows="5" required
                                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                                  placeholder="Nhập nội dung tin nhắn của bạn..."><?php echo htmlspecialchars($_POST['message'] ?? ''); ?></textarea>
                        <p class="text-xs text-gray-500 mt-1">Tối thiểu 10 ký tự</p>
                    </div>
                    
                    <!-- File Upload -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Đính kèm file (tùy chọn)
                        </label>
                        <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-primary-500 transition-colors">
                            <input type="file" 
                                   name="attachments[]" 
                                   id="attachments"
                                   multiple
                                   accept="image/jpeg,image/png,image/gif,application/pdf,.doc,.docx"
                                   class="hidden"
                                   onchange="previewFiles(this)">
                            <label for="attachments" class="cursor-pointer">
                                <i class="fas fa-cloud-upload-alt text-4xl text-gray-400 mb-2"></i>
                                <p class="text-sm text-gray-600 mb-1">
                                    <span class="text-primary-600 font-medium">Click để chọn file</span> hoặc kéo thả vào đây
                                </p>
                                <p class="text-xs text-gray-500">
                                    JPG, PNG, GIF, PDF, DOC (tối đa 5MB mỗi file, tối đa 3 file)
                                </p>
                            </label>
                        </div>
                        <div id="filePreview" class="mt-3 space-y-2"></div>
                    </div>
                    
                    <button type="submit" class="w-full bg-gradient-to-r from-primary-600 to-primary-700 text-white px-6 py-3 rounded-lg font-semibold hover:from-primary-700 hover:to-primary-800 transition-all duration-200 shadow-md hover:shadow-lg">
                        <i class="fas fa-paper-plane mr-2"></i>
                        Gửi tin nhắn
                    </button>
                </form>
                <?php endif; ?>
            </div>
            
            <!-- Contact Info -->
            <div class="space-y-6">
                <div class="bg-white rounded-lg shadow-md p-8">
                    <h3 class="text-xl font-bold mb-6">Thông tin liên hệ</h3>
                    
                    <div class="space-y-4">
                        <div class="flex items-start gap-4">
                            <div class="w-12 h-12 bg-primary-100 rounded-full flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-map-marker-alt text-primary-600 text-xl"></i>
                            </div>
                            <div>
                                <h4 class="font-semibold mb-1">Địa chỉ</h4>
                                <p class="text-gray-600">123 Trần Hưng Đạo, Quận 1, TPHCM</p>
                            </div>
                        </div>
                        
                        <div class="flex items-start gap-4">
                            <div class="w-12 h-12 bg-primary-100 rounded-full flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-phone text-primary-600 text-xl"></i>
                            </div>
                            <div>
                                <h4 class="font-semibold mb-1">Điện thoại</h4>
                                <p class="text-gray-600"><?php echo SITE_PHONE; ?></p>
                            </div>
                        </div>
                        
                        <div class="flex items-start gap-4">
                            <div class="w-12 h-12 bg-primary-100 rounded-full flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-envelope text-primary-600 text-xl"></i>
                            </div>
                            <div>
                                <h4 class="font-semibold mb-1">Email</h4>
                                <p class="text-gray-600">NTHsport@gmail.com</p>
                            </div>
                        </div>
                        
                        <div class="flex items-start gap-4">
                            <div class="w-12 h-12 bg-primary-100 rounded-full flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-clock text-primary-600 text-xl"></i>
                            </div>
                            <div>
                                <h4 class="font-semibold mb-1">Giờ làm việc</h4>
                                <p class="text-gray-600">8:00 - 22:00 (Hàng ngày)</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-lg shadow-md p-8">
                    <h3 class="text-xl font-bold mb-6">Theo dõi chúng tôi</h3>
                    <div class="flex gap-4">
                        <a href="https://www.facebook.com/share/1CzvTxYMWw/?mibextid=wwXIfr" target="_blank" class="w-12 h-12 bg-blue-600 rounded-full flex items-center justify-center text-white hover:bg-blue-700 transition">
                            <i class="fab fa-facebook-f text-xl"></i>
                        </a>
                        <a href="https://youtube.com/@nhanhuynh134?si=v38rh7OhLRBhkU3T" target="_blank" class="w-12 h-12 bg-red-600 rounded-full flex items-center justify-center text-white hover:bg-red-700 transition">
                            <i class="fab fa-youtube text-xl"></i>
                        </a>
                        <a href="https://www.tiktok.com/@thugicungco6" target="_blank" class="w-12 h-12 bg-gray-900 rounded-full flex items-center justify-center text-white hover:bg-gray-800 transition">
                            <i class="fab fa-tiktok text-xl"></i>
                        </a>
                    </div>
                </div>
                
                <!-- Map -->
                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3919.4547534473846!2d106.69252607583862!3d10.776829489373135!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x31752f462e1bb2cd%3A0x97da2863b7ca0679!2zMTIzIFRy4bqnbiBIxrBuZyDEkOG6oW8sIEPhuqd1IE_hurrLjyDEkMO0LCBRdeG6rW4gMSwgSOG7kyBDaMOtIE1pbmgsIFZp4buHdCBOYW0!5e0!3m2!1svi!2s!4v1732401234567!5m2!1svi!2s" 
                            width="100%" height="300" style="border:0;" allowfullscreen="" loading="lazy"></iframe>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let selectedFiles = [];

function previewFiles(input) {
    const preview = document.getElementById('filePreview');
    const files = Array.from(input.files);
    
    // Limit to 3 files
    if (files.length > 3) {
        alert('Chỉ được chọn tối đa 3 file!');
        input.value = '';
        return;
    }
    
    selectedFiles = files;
    preview.innerHTML = '';
    
    files.forEach((file, index) => {
        // Check file size
        if (file.size > 5 * 1024 * 1024) {
            alert(`File "${file.name}" vượt quá 5MB!`);
            input.value = '';
            preview.innerHTML = '';
            return;
        }
        
        const fileItem = document.createElement('div');
        fileItem.className = 'flex items-center justify-between bg-gray-50 p-3 rounded-lg border border-gray-200';
        
        const fileInfo = document.createElement('div');
        fileInfo.className = 'flex items-center gap-3';
        
        const icon = getFileIcon(file.type);
        const size = formatFileSize(file.size);
        
        fileInfo.innerHTML = `
            <i class="${icon} text-2xl text-primary-600"></i>
            <div>
                <p class="text-sm font-medium text-gray-700">${file.name}</p>
                <p class="text-xs text-gray-500">${size}</p>
            </div>
        `;
        
        const removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.className = 'text-red-500 hover:text-red-700';
        removeBtn.innerHTML = '<i class="fas fa-times"></i>';
        removeBtn.onclick = () => removeFile(index);
        
        fileItem.appendChild(fileInfo);
        fileItem.appendChild(removeBtn);
        preview.appendChild(fileItem);
    });
}

function removeFile(index) {
    const input = document.getElementById('attachments');
    const dt = new DataTransfer();
    const files = Array.from(input.files);
    
    files.splice(index, 1);
    
    files.forEach(file => dt.items.add(file));
    input.files = dt.files;
    
    previewFiles(input);
}

function getFileIcon(type) {
    if (type.startsWith('image/')) return 'fas fa-image';
    if (type === 'application/pdf') return 'fas fa-file-pdf';
    if (type.includes('word')) return 'fas fa-file-word';
    return 'fas fa-file';
}

function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
}

// Drag and drop
const dropZone = document.querySelector('[for="attachments"]').parentElement;
dropZone.addEventListener('dragover', (e) => {
    e.preventDefault();
    dropZone.classList.add('border-primary-500', 'bg-primary-50');
});

dropZone.addEventListener('dragleave', () => {
    dropZone.classList.remove('border-primary-500', 'bg-primary-50');
});

dropZone.addEventListener('drop', (e) => {
    e.preventDefault();
    dropZone.classList.remove('border-primary-500', 'bg-primary-50');
    
    const input = document.getElementById('attachments');
    input.files = e.dataTransfer.files;
    previewFiles(input);
});

// Form validation
document.getElementById('contactForm').addEventListener('submit', function(e) {
    const message = document.querySelector('textarea[name="message"]').value;
    if (message.length < 10) {
        e.preventDefault();
        alert('Nội dung tin nhắn phải có ít nhất 10 ký tự!');
    }
});
</script>

<?php include __DIR__ . '/../components/footer.php'; ?>
