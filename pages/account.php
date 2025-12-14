<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isLoggedIn()) {
    redirect(SITE_URL . '/pages/login.php');
}

$pageTitle = 'Tài khoản của tôi';
$db = getDB();
$user = getCurrentUser();

// Get user's orders
$stmtOrders = $db->prepare("
    SELECT * FROM don_hang 
    WHERE nguoi_dung_id = ? 
    ORDER BY created_at DESC 
    LIMIT 5
");
$stmtOrders->execute([$_SESSION['user_id']]);
$orders = $stmtOrders->fetchAll();

include __DIR__ . '/../components/header.php';
?>

<!-- Breadcrumb -->
<div class="bg-gray-100 py-4">
    <div class="container mx-auto px-4">
        <nav class="flex items-center gap-2 text-sm">
            <a href="<?php echo SITE_URL; ?>/index.php" class="text-gray-600 hover:text-primary-600">Trang chủ</a>
            <i class="fas fa-chevron-right text-gray-400 text-xs"></i>
            <span class="text-gray-900">Tài khoản</span>
        </nav>
    </div>
</div>

<div class="container mx-auto px-4 py-8">
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
        
        <!-- Sidebar -->
        <aside class="lg:col-span-1">
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="text-center mb-6">
                    <div class="w-24 h-24 bg-gradient-to-br from-primary-400 to-primary-600 rounded-full mx-auto mb-4 flex items-center justify-center overflow-hidden">
                        <?php if (!empty($user['avt'])): ?>
                            <img src="<?php echo htmlspecialchars($user['avt']); ?>" 
                                 alt="Avatar" 
                                 class="w-full h-full object-cover"
                                 onerror="this.onerror=null; this.parentElement.innerHTML='<i class=\'fas fa-user text-white text-4xl\'></i>';">
                        <?php else: ?>
                            <i class="fas fa-user text-white text-4xl"></i>
                        <?php endif; ?>
                    </div>
                    <h3 class="font-bold text-lg"><?php echo htmlspecialchars($user['ho_ten']); ?></h3>
                    <p class="text-sm text-gray-600"><?php echo htmlspecialchars($user['email']); ?></p>
                </div>
                
                <nav class="space-y-2">
                    <a href="<?php echo SITE_URL; ?>/pages/account.php" class="flex items-center gap-3 px-4 py-3 bg-primary-50 text-primary-600 rounded-lg font-medium">
                        <i class="fas fa-user w-5"></i>
                        <span>Thông tin tài khoản</span>
                    </a>
                    <a href="<?php echo SITE_URL; ?>/pages/orders.php" class="flex items-center gap-3 px-4 py-3 text-gray-700 hover:bg-gray-50 rounded-lg">
                        <i class="fas fa-shopping-bag w-5"></i>
                        <span>Đơn hàng của tôi</span>
                    </a>
                    <a href="<?php echo SITE_URL; ?>/pages/wishlist.php" class="flex items-center gap-3 px-4 py-3 text-gray-700 hover:bg-gray-50 rounded-lg">
                        <i class="fas fa-heart w-5"></i>
                        <span>Sản phẩm yêu thích</span>
                    </a>
                    <a href="<?php echo SITE_URL; ?>/pages/addresses.php" class="flex items-center gap-3 px-4 py-3 text-gray-700 hover:bg-gray-50 rounded-lg">
                        <i class="fas fa-map-marker-alt w-5"></i>
                        <span>Địa chỉ giao hàng</span>
                    </a>
                    <a href="<?php echo SITE_URL; ?>/pages/logout.php" class="flex items-center gap-3 px-4 py-3 text-red-600 hover:bg-red-50 rounded-lg">
                        <i class="fas fa-sign-out-alt w-5"></i>
                        <span>Đăng xuất</span>
                    </a>
                </nav>
            </div>
        </aside>
        
        <!-- Main Content -->
        <div class="lg:col-span-3">
            <!-- Account Info -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h2 class="text-2xl font-bold mb-6">Thông tin tài khoản</h2>
                
                <!-- Success/Error Messages -->
                <div id="messageContainer"></div>
                
                <form id="updateProfileForm" enctype="multipart/form-data">
                    <!-- Avatar Upload -->
                    <div class="mb-6 pb-6 border-b border-gray-200">
                        <label class="block text-sm font-medium text-gray-700 mb-3">Ảnh đại diện</label>
                        <div class="flex items-center gap-6">
                            <div id="avatarContainer" class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center overflow-hidden border-2 border-gray-200">
                                <?php if (!empty($user['avt'])): ?>
                                    <img id="avatarPreview" 
                                         src="<?php echo htmlspecialchars($user['avt']); ?>" 
                                         alt="Avatar Preview" 
                                         class="w-full h-full object-cover"
                                         onerror="this.onerror=null; this.parentElement.innerHTML='<i class=\'fas fa-user text-gray-400 text-4xl\'></i>';">
                                <?php else: ?>
                                    <i class="fas fa-user text-gray-400 text-4xl" id="avatarPlaceholder"></i>
                                <?php endif; ?>
                            </div>
                            <div class="flex-1">
                                <input type="file" 
                                       name="avatar"
                                       id="avatarInput" 
                                       accept="image/jpeg,image/png,image/gif" 
                                       class="hidden"
                                       onchange="previewAvatar(this)">
                                <button type="button" 
                                        onclick="document.getElementById('avatarInput').click()"
                                        class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg font-medium hover:bg-gray-200 transition-all">
                                    <i class="fas fa-upload mr-2"></i>
                                    Chọn ảnh mới
                                </button>
                                <p class="text-xs text-gray-500 mt-2">JPG, PNG hoặc GIF. Tối đa 2MB.</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Họ và tên <span class="text-red-500">*</span></label>
                            <input type="text" 
                                   name="ho_ten"
                                   id="ho_ten"
                                   value="<?php echo htmlspecialchars($user['ho_ten']); ?>" 
                                   required
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                            <input type="email" value="<?php echo htmlspecialchars($user['email']); ?>" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50" readonly>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Số điện thoại</label>
                            <input type="tel" 
                                   name="so_dien_thoai"
                                   id="so_dien_thoai"
                                   value="<?php echo htmlspecialchars($user['so_dien_thoai'] ?? ''); ?>" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Ngày tham gia</label>
                            <input type="text" value="<?php echo formatDate($user['created_at']); ?>" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50" readonly>
                        </div>
                        
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Địa chỉ</label>
                            <textarea name="dia_chi" 
                                      id="dia_chi"
                                      rows="3" 
                                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"><?php echo htmlspecialchars($user['dia_chi'] ?? ''); ?></textarea>
                        </div>
                    </div>
                    
                    <div class="mt-6">
                        <button type="submit" id="submitBtn" class="bg-primary-600 text-white px-6 py-2 rounded-lg font-medium hover:bg-primary-700 active:bg-primary-800 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed">
                            <i class="fas fa-save mr-2"></i>
                            <span id="submitText">Cập nhật thông tin</span>
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Recent Orders -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-2xl font-bold">Đơn hàng gần đây</h2>
                    <a href="<?php echo SITE_URL; ?>/pages/orders.php" class="text-primary-600 hover:text-primary-700 font-medium">
                        Xem tất cả <i class="fas fa-arrow-right ml-1"></i>
                    </a>
                </div>
                
                <?php if (empty($orders)): ?>
                    <div class="text-center py-12">
                        <i class="fas fa-shopping-bag text-gray-400 text-6xl mb-4"></i>
                        <h3 class="text-xl font-bold text-gray-900 mb-2">Chưa có đơn hàng nào</h3>
                        <p class="text-gray-600 mb-4">Hãy bắt đầu mua sắm ngay!</p>
                        <a href="<?php echo SITE_URL; ?>/pages/products.php" class="inline-flex items-center justify-center bg-primary-600 text-white px-6 py-3 rounded-lg font-medium hover:bg-primary-700 transition-all duration-200">
                            <i class="fas fa-shopping-cart mr-2"></i>
                            Mua sắm ngay
                        </a>
                    </div>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($orders as $order): ?>
                        <div class="border border-gray-200 rounded-lg p-4 hover:border-primary-300 transition-colors">
                            <div class="flex items-center justify-between mb-3">
                                <div>
                                    <h4 class="font-semibold text-lg"><?php echo htmlspecialchars($order['ma_don_hang']); ?></h4>
                                    <p class="text-sm text-gray-600"><?php echo formatDateTime($order['created_at']); ?></p>
                                </div>
                                <span class="px-3 py-1 bg-blue-100 text-blue-800 text-sm font-medium rounded-full">
                                    <?php echo ucfirst($order['trang_thai']); ?>
                                </span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-2xl font-bold text-primary-600"><?php echo formatCurrency($order['tong_thanh_toan']); ?></span>
                                <a href="<?php echo SITE_URL; ?>/pages/order-detail.php?id=<?php echo $order['id']; ?>" 
                                   class="text-primary-600 hover:text-primary-700 font-medium">
                                    Xem chi tiết <i class="fas fa-arrow-right ml-1"></i>
                                </a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
// Preview avatar when file is selected (không upload ngay)
function previewAvatar(input) {
    const container = document.getElementById('avatarContainer');
    
    if (input.files && input.files[0]) {
        const file = input.files[0];
        
        // Check file size (2MB max)
        if (file.size > 2 * 1024 * 1024) {
            alert('Kích thước ảnh không được vượt quá 2MB!');
            input.value = '';
            return;
        }
        
        // Check file type
        const allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        if (!allowedTypes.includes(file.type)) {
            alert('Chỉ chấp nhận file JPG, PNG hoặc GIF!');
            input.value = '';
            return;
        }
        
        // Show preview
        const reader = new FileReader();
        reader.onload = function(e) {
            // Replace container content with new image
            container.innerHTML = `<img id="avatarPreview" src="${e.target.result}" alt="Avatar Preview" class="w-full h-full object-cover">`;
        };
        reader.readAsDataURL(file);
    }
}

// Handle form submission (lưu cả ảnh + thông tin)
document.getElementById('updateProfileForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const submitBtn = document.getElementById('submitBtn');
    const submitText = document.getElementById('submitText');
    const messageContainer = document.getElementById('messageContainer');
    
    // Disable button
    submitBtn.disabled = true;
    submitText.textContent = 'Đang cập nhật...';
    
    try {
        const formData = new FormData(this);
        
        const response = await fetch('/Web/api/update-profile.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Show success message
            messageContainer.innerHTML = `
                <div class="bg-green-50 border-l-4 border-green-500 text-green-700 px-4 py-3 rounded mb-6">
                    <div class="flex items-center">
                        <i class="fas fa-check-circle mr-2"></i>
                        <span>${data.message}</span>
                    </div>
                </div>
            `;
            
            // Update avatar if changed
            if (data.user.avt) {
                const timestamp = Date.now();
                const avatarUrl = data.user.avt + '?t=' + timestamp;
                
                // Update form preview
                const container = document.getElementById('avatarContainer');
                container.innerHTML = `<img id="avatarPreview" src="${avatarUrl}" alt="Avatar" class="w-full h-full object-cover">`;
                
                // Update sidebar avatar
                const sidebarAvatarContainer = document.querySelector('aside .w-24.h-24');
                if (sidebarAvatarContainer) {
                    sidebarAvatarContainer.innerHTML = `<img src="${avatarUrl}" alt="Avatar" class="w-full h-full object-cover">`;
                }
                
                // Update header avatar
                const headerAvatar = document.querySelector('header img[alt="Avatar"]');
                if (headerAvatar) {
                    headerAvatar.src = avatarUrl;
                }
            }
            
            // Update sidebar name
            const sidebarName = document.querySelector('aside h3.font-bold');
            if (sidebarName) {
                sidebarName.textContent = data.user.ho_ten;
            }
            
            // Scroll to message
            messageContainer.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            
            // Clear file input
            document.getElementById('avatarInput').value = '';
            
            // Hide message after 5 seconds
            setTimeout(() => {
                messageContainer.innerHTML = '';
            }, 5000);
            
        } else {
            // Show error message
            messageContainer.innerHTML = `
                <div class="bg-red-50 border-l-4 border-red-500 text-red-700 px-4 py-3 rounded mb-6">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-circle mr-2"></i>
                        <span>${data.message}</span>
                    </div>
                </div>
            `;
            
            messageContainer.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    } catch (error) {
        messageContainer.innerHTML = `
            <div class="bg-red-50 border-l-4 border-red-500 text-red-700 px-4 py-3 rounded mb-6">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    <span>Đã xảy ra lỗi. Vui lòng thử lại!</span>
                </div>
            </div>
        `;
        console.error('Error:', error);
    } finally {
        // Re-enable button
        submitBtn.disabled = false;
        submitText.textContent = 'Cập nhật thông tin';
    }
});
</script>

<?php include __DIR__ . '/../components/footer.php'; ?>
