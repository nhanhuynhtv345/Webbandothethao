<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

if (isLoggedIn()) {
    redirect('/Web/index.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $hoTen = sanitize($_POST['ho_ten'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $soDienThoai = sanitize($_POST['so_dien_thoai'] ?? '');
    $diaChi = sanitize($_POST['dia_chi'] ?? '');
    $avt = null;
    
    // Handle avatar upload
    if (isset($_FILES['avt']) && $_FILES['avt']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/../assets/uploads/avatars/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $fileExtension = strtolower(pathinfo($_FILES['avt']['name'], PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
        
        if (in_array($fileExtension, $allowedExtensions)) {
            $fileName = uniqid('avatar_') . '.' . $fileExtension;
            $targetPath = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['avt']['tmp_name'], $targetPath)) {
                $avt = '/Web/assets/uploads/avatars/' . $fileName;
            }
        }
    }
    
    if (empty($hoTen) || empty($email) || empty($password)) {
        $error = 'Vui lòng nhập đầy đủ thông tin bắt buộc';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Email không hợp lệ';
    } elseif (strlen($password) < 6) {
        $error = 'Mật khẩu phải có ít nhất 6 ký tự';
    } elseif ($password !== $confirmPassword) {
        $error = 'Mật khẩu xác nhận không khớp';
    } else {
        $db = getDB();
        
        // Check if email exists
        $stmt = $db->prepare("SELECT id FROM nguoi_dung WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->fetch()) {
            $error = 'Email đã được sử dụng';
        } elseif (!empty($soDienThoai)) {
            // Check if phone exists
            $stmt = $db->prepare("SELECT id FROM nguoi_dung WHERE so_dien_thoai = ?");
            $stmt->execute([$soDienThoai]);
            if ($stmt->fetch()) {
                $error = 'Số điện thoại đã được sử dụng';
            }
        }
        
        if (empty($error)) {
            // Create account
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("
                INSERT INTO nguoi_dung (ho_ten, email, mat_khau, so_dien_thoai, dia_chi, avt)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            if ($stmt->execute([$hoTen, $email, $hashedPassword, $soDienThoai, $diaChi, $avt])) {
                $success = 'Đăng ký thành công! Đang chuyển hướng...';
                $_SESSION['user_id'] = $db->lastInsertId();
                $_SESSION['user_name'] = $hoTen;
                $_SESSION['user_email'] = $email;
                header("refresh:2;url=/Web/index.php");
            } else {
                $error = 'Có lỗi xảy ra, vui lòng thử lại';
            }
        }
    }
}

$pageTitle = 'Đăng ký';
include __DIR__ . '/../components/header.php';
?>

<div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8 bg-gray-50">
    <div class="max-w-md w-full">
        <div class="bg-white rounded-lg shadow-lg p-8">
            <!-- Header -->
            <div class="text-center mb-8">
                <div class="mx-auto h-16 w-16 bg-gradient-to-r from-green-600 to-green-700 rounded-full flex items-center justify-center mb-4 shadow-lg">
                    <i class="fas fa-user-plus text-white text-2xl"></i>
                </div>
                <h2 class="text-3xl font-bold text-gray-900">Đăng ký</h2>
                <p class="mt-2 text-sm text-gray-600">Tạo tài khoản mới</p>
            </div>
            
            <?php if ($error): ?>
            <div class="bg-red-50 border-l-4 border-red-500 text-red-700 px-4 py-3 rounded mb-6" role="alert">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    <span><?php echo $error; ?></span>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
            <div class="bg-green-50 border-l-4 border-green-500 text-green-700 px-4 py-3 rounded mb-6" role="alert">
                <div class="flex items-center">
                    <i class="fas fa-check-circle mr-2"></i>
                    <span><?php echo $success; ?></span>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Register Form -->
            <form method="POST" action="" enctype="multipart/form-data" class="space-y-4">
                <!-- Avatar Upload - Moved to top -->
                <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                    <label class="block text-sm font-medium text-gray-700 mb-3 text-center">
                        Ảnh đại diện
                    </label>
                    <div class="flex flex-col items-center gap-3">
                        <div id="avatarPreview" class="w-24 h-24 rounded-full bg-white flex items-center justify-center overflow-hidden border-4 border-gray-300 shadow-md">
                            <i class="fas fa-user text-gray-400 text-3xl"></i>
                        </div>
                        <label for="avt" class="cursor-pointer inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                            <i class="fas fa-upload mr-2"></i>
                            Chọn ảnh
                        </label>
                        <input type="file" id="avt" name="avt" accept="image/*" class="hidden" onchange="previewAvatar(this)">
                        <p class="text-xs text-gray-500 text-center">JPG, PNG hoặc GIF (Tối đa 2MB)</p>
                    </div>
                </div>
                
                <div>
                    <label for="ho_ten" class="block text-sm font-medium text-gray-700 mb-2">
                        Họ và tên <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-user text-gray-400"></i>
                        </div>
                        <input type="text" id="ho_ten" name="ho_ten" required
                               class="pl-10 w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all"
                               placeholder="Nguyễn Văn A"
                               value="<?php echo htmlspecialchars($_POST['ho_ten'] ?? ''); ?>">
                    </div>
                </div>
                
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                        Email <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-envelope text-gray-400"></i>
                        </div>
                        <input type="email" id="email" name="email" required
                               class="pl-10 w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all"
                               placeholder="your@email.com"
                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                    </div>
                </div>
                
                <div>
                    <label for="so_dien_thoai" class="block text-sm font-medium text-gray-700 mb-2">
                        Số điện thoại
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-phone text-gray-400"></i>
                        </div>
                        <input type="tel" id="so_dien_thoai" name="so_dien_thoai"
                               class="pl-10 w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all"
                               placeholder="0888 899 107"
                               value="<?php echo htmlspecialchars($_POST['so_dien_thoai'] ?? ''); ?>">
                    </div>
                </div>
                
                <div>
                    <label for="dia_chi" class="block text-sm font-medium text-gray-700 mb-2">
                        Địa chỉ
                    </label>
                    <div class="relative">
                        <div class="absolute top-3 left-0 pl-3 flex items-start pointer-events-none">
                            <i class="fas fa-map-marker-alt text-gray-400"></i>
                        </div>
                        <textarea id="dia_chi" name="dia_chi" rows="2"
                                  class="pl-10 w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all"
                                  placeholder="Số nhà, đường, phường/xã, quận/huyện, tỉnh/thành phố"><?php echo htmlspecialchars($_POST['dia_chi'] ?? ''); ?></textarea>
                    </div>
                </div>
                
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                        Mật khẩu <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-lock text-gray-400"></i>
                        </div>
                        <input type="password" id="password" name="password" required
                               class="pl-10 pr-10 w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all"
                               placeholder="••••••••">
                        <button type="button" onclick="togglePassword('password', 'toggleIcon1')" class="absolute inset-y-0 right-0 pr-3 flex items-center">
                            <i class="fas fa-eye text-gray-400 hover:text-gray-600" id="toggleIcon1"></i>
                        </button>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">Mật khẩu phải có ít nhất 6 ký tự</p>
                </div>
                
                <div>
                    <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-2">
                        Xác nhận mật khẩu <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-lock text-gray-400"></i>
                        </div>
                        <input type="password" id="confirm_password" name="confirm_password" required
                               class="pl-10 pr-10 w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all"
                               placeholder="••••••••">
                        <button type="button" onclick="togglePassword('confirm_password', 'toggleIcon2')" class="absolute inset-y-0 right-0 pr-3 flex items-center">
                            <i class="fas fa-eye text-gray-400 hover:text-gray-600" id="toggleIcon2"></i>
                        </button>
                    </div>
                </div>
                
                <div class="flex items-start">
                    <input type="checkbox" id="agree" name="agree" required class="h-4 w-4 mt-1 text-green-600 focus:ring-green-500 border-gray-300 rounded">
                    <label for="agree" class="ml-2 text-sm text-gray-600">
                        Tôi đồng ý với <a href="#" class="text-green-600 hover:text-green-500 font-medium">Điều khoản dịch vụ</a> 
                        và <a href="#" class="text-green-600 hover:text-green-500 font-medium">Chính sách bảo mật</a>
                    </label>
                </div>
                
                <button type="submit" class="w-full bg-gradient-to-r from-green-600 to-green-700 text-white py-3 rounded-lg font-semibold hover:from-green-700 hover:to-green-800 transition-all duration-200 shadow-md hover:shadow-lg">
                    <i class="fas fa-user-plus mr-2"></i>
                    Đăng ký
                </button>
            </form>
            
            <!-- Divider -->
            <div class="relative my-6">
                <div class="absolute inset-0 flex items-center">
                    <div class="w-full border-t border-gray-300"></div>
                </div>
                <div class="relative flex justify-center text-sm">
                    <span class="px-2 bg-white text-gray-500">Hoặc đăng ký bằng</span>
                </div>
            </div>
            
            <!-- Google Sign Up Button -->
            <div class="mb-6">
                <a href="<?php echo getGoogleAuthUrl(); ?>" 
                   class="w-full flex items-center justify-center gap-3 px-4 py-3 border-2 border-gray-300 rounded-lg hover:bg-gray-50 transition-colors text-gray-700 font-medium">
                    <svg class="w-5 h-5" viewBox="0 0 24 24">
                        <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                        <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                        <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                        <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                    </svg>
                    <span>Đăng ký với Google</span>
                </a>
            </div>
            
            <!-- Login Link -->
            <div class="mt-6 text-center">
                <p class="text-sm text-gray-600">
                    Đã có tài khoản? 
                    <a href="/Web/pages/login.php" class="font-medium text-green-600 hover:text-green-500">
                        Đăng nhập ngay
                    </a>
                </p>
            </div>
        </div>
        
        <!-- Back to Home -->
        <div class="text-center mt-6">
            <a href="/Web/index.php" class="text-sm text-gray-600 hover:text-gray-900">
                <i class="fas fa-arrow-left mr-2"></i>
                Quay lại trang chủ
            </a>
        </div>
    </div>
</div>

<script>
function previewAvatar(input) {
    const preview = document.getElementById('avatarPreview');
    
    if (input.files && input.files[0]) {
        const file = input.files[0];
        
        // Check file size (2MB max)
        if (file.size > 2 * 1024 * 1024) {
            alert('Kích thước ảnh không được vượt quá 2MB!');
            input.value = '';
            return;
        }
        
        // Check file type
        const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        if (!validTypes.includes(file.type)) {
            alert('Vui lòng chọn file ảnh (JPG, PNG hoặc GIF)!');
            input.value = '';
            return;
        }
        
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.innerHTML = `<img src="${e.target.result}" alt="Avatar Preview" class="w-full h-full object-cover">`;
        };
        reader.readAsDataURL(file);
    }
}

function togglePassword(fieldId, iconId) {
    const field = document.getElementById(fieldId);
    const icon = document.getElementById(iconId);
    
    if (field.type === 'password') {
        field.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        field.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

// No Google SDK needed - using OAuth 2.0 flow
</script>

<?php include __DIR__ . '/../components/footer.php'; ?>
