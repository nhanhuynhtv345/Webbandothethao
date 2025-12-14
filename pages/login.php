<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

if (isLoggedIn()) {
    if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1) {
        redirect('/Web/admin/');
    } else {
        redirect('/Web/pages/account.php');
    }
}

$error = '';
$warning = '';
$showUnlockRequest = false;
$lockedEmail = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Vui lòng nhập đầy đủ thông tin';
    } else {
        $db = getDB();
        
        // Kiểm tra đăng nhập admin trước
        $stmt = $db->prepare("SELECT * FROM admin WHERE username = ?");
        $stmt->execute([$email]);
        $admin = $stmt->fetch();
        
        if ($admin && password_verify($password, $admin['password'])) {
            $_SESSION['user_id'] = $admin['id'];
            $_SESSION['user_name'] = $admin['full_name'] ?? $admin['username'];
            $_SESSION['user_email'] = $admin['username'] . '@admin';
            $_SESSION['is_admin'] = 1;
            
            $redirect = $_GET['redirect'] ?? '/Web/admin/';
            redirect($redirect);
        } else {
            // Kiểm tra đăng nhập khách hàng
            $stmt = $db->prepare("SELECT * FROM nguoi_dung WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user) {
                // Kiểm tra tài khoản bị khóa
                $isLocked = $user['is_locked'] ?? 0;
                $failedAttempts = $user['failed_attempts'] ?? 0;
                
                if ($isLocked == 1) {
                    $error = 'Tài khoản của bạn đã bị khóa vĩnh viễn do đăng nhập sai quá 5 lần.';
                    $showUnlockRequest = true;
                    $lockedEmail = $email;
                } elseif (password_verify($password, $user['mat_khau'])) {
                    // Đăng nhập thành công - reset failed_attempts
                    try {
                        $db->prepare("UPDATE nguoi_dung SET failed_attempts = 0 WHERE id = ?")->execute([$user['id']]);
                    } catch (Exception $e) {
                        // Cột chưa tồn tại, bỏ qua
                    }
                    
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['ho_ten'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['is_admin'] = 0;
                    
                    $redirect = $_GET['redirect'] ?? '/Web/index.php';
                    redirect($redirect);
                } else {
                    // Sai mật khẩu - tăng failed_attempts
                    $newAttempts = $failedAttempts + 1;
                    $error = 'Mật khẩu không đúng!';
                    
                    try {
                        if ($newAttempts >= 5) {
                            // Khóa tài khoản vĩnh viễn
                            $db->prepare("UPDATE nguoi_dung SET failed_attempts = ?, is_locked = 1, locked_at = NOW() WHERE id = ?")->execute([$newAttempts, $user['id']]);
                            $error = 'Tài khoản của bạn đã bị khóa vĩnh viễn do đăng nhập sai quá 5 lần.';
                            $showUnlockRequest = true;
                            $lockedEmail = $email;
                        } else {
                            $db->prepare("UPDATE nguoi_dung SET failed_attempts = ? WHERE id = ?")->execute([$newAttempts, $user['id']]);
                            $remaining = 5 - $newAttempts;
                            $warning = "Cảnh báo: Bạn còn $remaining lần thử. Sau 5 lần đăng nhập sai, tài khoản sẽ bị khóa vĩnh viễn.";
                        }
                    } catch (PDOException $e) {
                        // Cột chưa tồn tại - bỏ qua tính năng khóa tài khoản
                    }
                }
            } else {
                $error = 'Email không tồn tại trong hệ thống';
            }
        }
    }
}

$pageTitle = 'Đăng nhập';
include __DIR__ . '/../components/header.php';
?>

<div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8 bg-gray-50">
    <div class="max-w-md w-full">
        <div class="bg-white rounded-lg shadow-lg p-8">
            <!-- Header -->
            <div class="text-center mb-8">
                <div class="mx-auto h-16 w-16 bg-gradient-to-r from-primary-600 to-primary-700 rounded-full flex items-center justify-center mb-4 shadow-lg">
                    <i class="fas fa-user text-white text-2xl"></i>
                </div>
                <h2 class="text-3xl font-bold text-gray-900">Đăng nhập</h2>
                <p class="mt-2 text-sm text-gray-600">Chào mừng bạn quay trở lại!</p>
            </div>
            
            <!-- Thông báo cảnh báo -->
            <div class="bg-amber-50 border border-amber-200 text-amber-800 px-4 py-3 rounded-lg mb-6">
                <div class="flex items-start gap-3">
                    <i class="fas fa-exclamation-triangle text-amber-500 mt-0.5"></i>
                    <div class="text-sm">
                        <p class="font-semibold">Lưu ý bảo mật:</p>
                        <p>Đăng nhập sai quá 5 lần sẽ bị khóa tài khoản vĩnh viễn.</p>
                    </div>
                </div>
            </div>
            
            <?php if ($error): ?>
            <div class="bg-red-50 border-l-4 border-red-500 text-red-700 px-4 py-3 rounded mb-4" role="alert">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    <span><?php echo $error; ?></span>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if ($showUnlockRequest): ?>
            <div class="bg-blue-50 border border-blue-200 text-blue-800 px-4 py-4 rounded-lg mb-4">
                <div class="flex items-start gap-3">
                    <i class="fas fa-info-circle text-blue-500 mt-0.5"></i>
                    <div class="flex-1">
                        <p class="font-semibold mb-2">Bạn muốn mở lại tài khoản?</p>
                        <p class="text-sm mb-3">Gửi yêu cầu đến Admin để được xem xét mở khóa tài khoản.</p>
                        <button type="button" onclick="showUnlockModal()" 
                                class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-700 transition">
                            <i class="fas fa-envelope mr-2"></i>Liên hệ Admin
                        </button>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if ($warning): ?>
            <div class="bg-orange-50 border-l-4 border-orange-500 text-orange-700 px-4 py-3 rounded mb-4" role="alert">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    <span><?php echo $warning; ?></span>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Login Form -->
            <form method="POST" action="" class="space-y-5">
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                        Email hoặc Tên đăng nhập <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-envelope text-gray-400"></i>
                        </div>
                        <input type="text" id="email" name="email" required
                               class="pl-10 w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all"
                               placeholder="admin hoặc your@email.com"
                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
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
                               class="pl-10 pr-10 w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all"
                               placeholder="••••••••">
                        <button type="button" onclick="togglePassword()" class="absolute inset-y-0 right-0 pr-3 flex items-center">
                            <i class="fas fa-eye text-gray-400 hover:text-gray-600" id="toggleIcon"></i>
                        </button>
                    </div>
                </div>
                
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <input type="checkbox" id="remember" name="remember" class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded">
                        <label for="remember" class="ml-2 block text-sm text-gray-700">
                            Ghi nhớ đăng nhập
                        </label>
                    </div>
                    <a href="/Web/pages/forgot-password.php" class="text-sm font-medium text-primary-600 hover:text-primary-500">
                        Quên mật khẩu?
                    </a>
                </div>
                
                <button type="submit" class="w-full bg-gradient-to-r from-primary-600 to-primary-700 text-white py-3 rounded-lg font-semibold hover:from-primary-700 hover:to-primary-800 transition-all duration-200 shadow-md hover:shadow-lg">
                    <i class="fas fa-sign-in-alt mr-2"></i>
                    Đăng nhập
                </button>
            </form>
            
            <!-- Divider -->
            <div class="relative my-6">
                <div class="absolute inset-0 flex items-center">
                    <div class="w-full border-t border-gray-300"></div>
                </div>
                <div class="relative flex justify-center text-sm">
                    <span class="px-2 bg-white text-gray-500">Hoặc đăng nhập bằng</span>
                </div>
            </div>
            
            <!-- Google Login Button -->
            <div class="mb-6">
                <a href="<?php echo getGoogleAuthUrl(); ?>" 
                   class="w-full flex items-center justify-center gap-3 px-4 py-3 border-2 border-gray-300 rounded-lg hover:bg-gray-50 transition-colors text-gray-700 font-medium">
                    <svg class="w-5 h-5" viewBox="0 0 24 24">
                        <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                        <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                        <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                        <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                    </svg>
                    <span>Đăng nhập với Google</span>
                </a>
            </div>
            
            <!-- Register Link -->
            <div class="mt-6 text-center">
                <p class="text-sm text-gray-600">
                    Chưa có tài khoản? 
                    <a href="/Web/pages/register.php" class="font-medium text-primary-600 hover:text-primary-500">
                        Đăng ký ngay
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

<!-- Modal yêu cầu mở khóa tài khoản -->
<div id="unlockModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg max-w-md w-full p-6 relative">
        <button onclick="closeUnlockModal()" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600">
            <i class="fas fa-times text-xl"></i>
        </button>
        
        <h3 class="text-xl font-bold text-gray-900 mb-4">
            <i class="fas fa-unlock-alt text-blue-600 mr-2"></i>
            Yêu cầu mở khóa tài khoản
        </h3>
        
        <form id="unlockRequestForm" class="space-y-4">
            <input type="hidden" name="email" value="<?php echo htmlspecialchars($lockedEmail); ?>">
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Email tài khoản bị khóa
                </label>
                <input type="text" value="<?php echo htmlspecialchars($lockedEmail); ?>" disabled
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-100 text-gray-600">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Họ tên của bạn <span class="text-red-500">*</span>
                </label>
                <input type="text" name="ho_ten" required
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                       placeholder="Nhập họ tên">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Số điện thoại <span class="text-red-500">*</span>
                </label>
                <input type="tel" name="dien_thoai" required
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                       placeholder="Nhập số điện thoại">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Lý do yêu cầu mở khóa <span class="text-red-500">*</span>
                </label>
                <textarea name="ly_do" rows="3" required
                          class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                          placeholder="Giải thích lý do bạn muốn mở lại tài khoản..."></textarea>
            </div>
            
            <div class="flex gap-3 pt-2">
                <button type="button" onclick="closeUnlockModal()" 
                        class="flex-1 px-4 py-2 border border-gray-300 rounded-lg font-medium hover:bg-gray-50 transition">
                    Hủy
                </button>
                <button type="submit" 
                        class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 transition">
                    <i class="fas fa-paper-plane mr-2"></i>Gửi yêu cầu
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function togglePassword() {
    const password = document.getElementById('password');
    const icon = document.getElementById('toggleIcon');
    
    if (password.type === 'password') {
        password.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        password.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

function showUnlockModal() {
    document.getElementById('unlockModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeUnlockModal() {
    document.getElementById('unlockModal').classList.add('hidden');
    document.body.style.overflow = 'auto';
}

document.getElementById('unlockRequestForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const data = Object.fromEntries(formData);
    
    const btn = this.querySelector('button[type="submit"]');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Đang gửi...';
    
    fetch('<?php echo SITE_URL; ?>/api/unlock-request.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(res => res.json())
    .then(result => {
        if (result.success) {
            alert('Yêu cầu đã được gửi thành công!\\n\\nAdmin sẽ xem xét và phản hồi qua email: ' + document.querySelector('input[name="email"]').value + '\\n\\nVui lòng kiểm tra hộp thư (bao gồm cả thư rác) trong 24-48 giờ tới.');
            closeUnlockModal();
        } else {
            alert('Lỗi: ' + result.message);
        }
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-paper-plane mr-2"></i>Gửi yêu cầu';
    })
    .catch(err => {
        alert('Có lỗi xảy ra. Vui lòng thử lại!');
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-paper-plane mr-2"></i>Gửi yêu cầu';
    });
});
</script>

<?php include __DIR__ . '/../components/footer.php'; ?>
