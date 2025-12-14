<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

// Only logged in users can checkout
if (!isLoggedIn()) {
    redirect('/Web/pages/login.php?redirect=' . urlencode('/Web/pages/checkout.php'));
}

$pageTitle = 'Thanh toán';
$db = getDB();
$user = getCurrentUser();

// Get cart items
$stmt = $db->prepare("
    SELECT gh.*, sp.ten_san_pham, sp.gia_ban, sp.so_luong_ton, sp.slug,
           bienthe.ten_bien_the, bienthe.gia_ban as bien_the_gia,
           (SELECT url FROM hinh_anh_san_pham WHERE san_pham_id = sp.id AND is_primary = 1 LIMIT 1) as anh_dai_dien
    FROM gio_hang gh
    INNER JOIN san_pham sp ON gh.san_pham_id = sp.id
    LEFT JOIN bien_the_san_pham bienthe ON gh.bien_the_id = bienthe.id
    WHERE gh.nguoi_dung_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$cartItems = $stmt->fetchAll();

// If cart is empty, redirect to cart page
if (empty($cartItems)) {
    redirect('/Web/pages/cart.php');
}

// Calculate totals
$subtotal = 0;
foreach ($cartItems as $item) {
    $price = $item['bien_the_gia'] ?? $item['gia_ban'];
    $subtotal += $price * $item['so_luong'];
}

// Free shipping for orders over 500k
$shippingFee = $subtotal >= 500000 ? 0 : 30000;
$total = $subtotal + $shippingFee;

include __DIR__ . '/../components/header.php';
?>

<!-- Breadcrumb -->
<div class="bg-gray-100 py-4">
    <div class="container mx-auto px-4">
        <nav class="flex items-center gap-2 text-sm">
            <a href="<?php echo SITE_URL; ?>/index.php" class="text-gray-600 hover:text-primary-600">Trang chủ</a>
            <i class="fas fa-chevron-right text-gray-400 text-xs"></i>
            <a href="<?php echo SITE_URL; ?>/pages/cart.php" class="text-gray-600 hover:text-primary-600">Giỏ hàng</a>
            <i class="fas fa-chevron-right text-gray-400 text-xs"></i>
            <span class="text-gray-900">Thanh toán</span>
        </nav>
    </div>
</div>

<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold text-gray-900 mb-8">Thanh toán</h1>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Left: Shipping Info Form -->
        <div class="lg:col-span-2">
            <form id="checkoutForm" class="space-y-6">
                <!-- Customer Info -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">
                        <i class="fas fa-user text-primary-600 mr-2"></i>
                        Thông tin người nhận
                    </h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Họ tên <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="ho_ten" required
                                   value="<?php echo htmlspecialchars($user['ho_ten'] ?? ''); ?>"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Số điện thoại <span class="text-red-500">*</span>
                            </label>
                            <input type="tel" name="dien_thoai" required
                                   value="<?php echo htmlspecialchars($user['dien_thoai'] ?? ''); ?>"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Email <span class="text-red-500">*</span>
                        </label>
                        <input type="email" name="email" required
                               value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                    </div>
                </div>

                <!-- Shipping Address -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">
                        <i class="fas fa-map-marker-alt text-primary-600 mr-2"></i>
                        Địa chỉ giao hàng
                    </h2>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Địa chỉ cụ thể <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="dia_chi" required
                               placeholder="Số nhà, tên đường"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Tỉnh/Thành phố <span class="text-red-500">*</span>
                            </label>
                            <select name="tinh_thanh" required
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                                <option value="">Chọn tỉnh/thành</option>
                                <option value="An Giang">An Giang</option>
                                <option value="Bà Rịa - Vũng Tàu">Bà Rịa - Vũng Tàu</option>
                                <option value="Bắc Giang">Bắc Giang</option>
                                <option value="Bắc Kạn">Bắc Kạn</option>
                                <option value="Bạc Liêu">Bạc Liêu</option>
                                <option value="Bắc Ninh">Bắc Ninh</option>
                                <option value="Bến Tre">Bến Tre</option>
                                <option value="Bình Định">Bình Định</option>
                                <option value="Bình Dương">Bình Dương</option>
                                <option value="Bình Phước">Bình Phước</option>
                                <option value="Bình Thuận">Bình Thuận</option>
                                <option value="Cà Mau">Cà Mau</option>
                                <option value="Cần Thơ">Cần Thơ</option>
                                <option value="Cao Bằng">Cao Bằng</option>
                                <option value="Đà Nẵng">Đà Nẵng</option>
                                <option value="Đắk Lắk">Đắk Lắk</option>
                                <option value="Đắk Nông">Đắk Nông</option>
                                <option value="Điện Biên">Điện Biên</option>
                                <option value="Đồng Nai">Đồng Nai</option>
                                <option value="Đồng Tháp">Đồng Tháp</option>
                                <option value="Gia Lai">Gia Lai</option>
                                <option value="Hà Giang">Hà Giang</option>
                                <option value="Hà Nam">Hà Nam</option>
                                <option value="Hà Nội">Hà Nội</option>
                                <option value="Hà Tĩnh">Hà Tĩnh</option>
                                <option value="Hải Dương">Hải Dương</option>
                                <option value="Hải Phòng">Hải Phòng</option>
                                <option value="Hậu Giang">Hậu Giang</option>
                                <option value="Hòa Bình">Hòa Bình</option>
                                <option value="Hưng Yên">Hưng Yên</option>
                                <option value="Khánh Hòa">Khánh Hòa</option>
                                <option value="Kiên Giang">Kiên Giang</option>
                                <option value="Kon Tum">Kon Tum</option>
                                <option value="Lai Châu">Lai Châu</option>
                                <option value="Lâm Đồng">Lâm Đồng</option>
                                <option value="Lạng Sơn">Lạng Sơn</option>
                                <option value="Lào Cai">Lào Cai</option>
                                <option value="Long An">Long An</option>
                                <option value="Nam Định">Nam Định</option>
                                <option value="Nghệ An">Nghệ An</option>
                                <option value="Ninh Bình">Ninh Bình</option>
                                <option value="Ninh Thuận">Ninh Thuận</option>
                                <option value="Phú Thọ">Phú Thọ</option>
                                <option value="Phú Yên">Phú Yên</option>
                                <option value="Quảng Bình">Quảng Bình</option>
                                <option value="Quảng Nam">Quảng Nam</option>
                                <option value="Quảng Ngãi">Quảng Ngãi</option>
                                <option value="Quảng Ninh">Quảng Ninh</option>
                                <option value="Quảng Trị">Quảng Trị</option>
                                <option value="Sóc Trăng">Sóc Trăng</option>
                                <option value="Sơn La">Sơn La</option>
                                <option value="Tây Ninh">Tây Ninh</option>
                                <option value="Thái Bình">Thái Bình</option>
                                <option value="Thái Nguyên">Thái Nguyên</option>
                                <option value="Thanh Hóa">Thanh Hóa</option>
                                <option value="Thừa Thiên Huế">Thừa Thiên Huế</option>
                                <option value="Tiền Giang">Tiền Giang</option>
                                <option value="TP. Hồ Chí Minh">TP. Hồ Chí Minh</option>
                                <option value="Trà Vinh">Trà Vinh</option>
                                <option value="Tuyên Quang">Tuyên Quang</option>
                                <option value="Vĩnh Long">Vĩnh Long</option>
                                <option value="Vĩnh Phúc">Vĩnh Phúc</option>
                                <option value="Yên Bái">Yên Bái</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Quận/Huyện <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="quan_huyen" required
                                   placeholder="Quận/Huyện"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Phường/Xã <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="phuong_xa" required
                                   placeholder="Phường/Xã"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                        </div>
                    </div>
                </div>

                <!-- Note -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-sticky-note text-primary-600 mr-2"></i>
                        Ghi chú đơn hàng (không bắt buộc)
                    </label>
                    <textarea name="ghi_chu" rows="3"
                              placeholder="Ví dụ: Giao hàng giờ hành chính..."
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"></textarea>
                </div>
            </form>
        </div>

        <!-- Right: Order Summary -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 sticky top-4">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Đơn hàng của bạn</h2>
                
                <!-- Cart Items -->
                <div class="space-y-3 mb-4 max-h-64 overflow-y-auto">
                    <?php foreach ($cartItems as $item): 
                        $price = $item['bien_the_gia'] ?? $item['gia_ban'];
                        $itemTotal = $price * $item['so_luong'];
                    ?>
                    <div class="flex gap-3 pb-3 border-b border-gray-200">
                        <img src="<?php echo $item['anh_dai_dien'] ? UPLOAD_URL . '/' . $item['anh_dai_dien'] : 'https://via.placeholder.com/80'; ?>" 
                             alt="<?php echo htmlspecialchars($item['ten_san_pham']); ?>"
                             class="w-16 h-16 object-cover rounded">
                        <div class="flex-1 min-w-0">
                            <h4 class="font-medium text-sm text-gray-900 truncate">
                                <?php echo htmlspecialchars($item['ten_san_pham']); ?>
                            </h4>
                            <?php if ($item['ten_bien_the']): ?>
                                <p class="text-xs text-gray-500"><?php echo htmlspecialchars($item['ten_bien_the']); ?></p>
                            <?php endif; ?>
                            <p class="text-xs text-gray-600 mt-1">
                                <?php echo number_format($price); ?>đ x <?php echo $item['so_luong']; ?>
                            </p>
                        </div>
                        <div class="text-right">
                            <p class="font-semibold text-primary-600"><?php echo number_format($itemTotal); ?>đ</p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Coupon Code -->
                <div class="py-4 border-t border-gray-200">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-ticket-alt text-orange-500 mr-1"></i>Mã giảm giá
                    </label>
                    <div class="flex gap-2">
                        <input type="text" id="couponCode" placeholder="Nhập mã giảm giá"
                               class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent uppercase text-sm">
                        <button type="button" onclick="applyCoupon()" 
                                class="px-4 py-2 bg-orange-500 text-white rounded-lg hover:bg-orange-600 transition text-sm font-medium">
                            Áp dụng
                        </button>
                    </div>
                    <div id="couponMessage" class="mt-2 text-sm hidden"></div>
                    <div id="appliedCoupon" class="mt-2 hidden">
                        <div class="flex items-center justify-between bg-green-50 border border-green-200 rounded-lg px-3 py-2">
                            <div class="flex items-center gap-2">
                                <i class="fas fa-check-circle text-green-500"></i>
                                <span class="text-sm text-green-700" id="couponTitle"></span>
                            </div>
                            <button type="button" onclick="removeCoupon()" class="text-red-500 hover:text-red-700">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Summary -->
                <div class="space-y-2 py-4 border-t border-gray-200">
                    <div class="flex justify-between text-gray-600">
                        <span>Tạm tính:</span>
                        <span class="font-medium"><?php echo number_format($subtotal); ?>đ</span>
                    </div>
                    <div id="discountRow" class="flex justify-between text-green-600 hidden">
                        <span>Giảm giá:</span>
                        <span class="font-medium" id="discountAmount">-0đ</span>
                    </div>
                    <div class="flex justify-between text-gray-600">
                        <span>Phí vận chuyển:</span>
                        <span class="font-medium" id="shippingDisplay"><?php echo $shippingFee > 0 ? number_format($shippingFee) . 'đ' : '<span class="text-green-600">Miễn phí</span>'; ?></span>
                    </div>
                    <?php if ($subtotal < 500000 && $subtotal > 0): ?>
                    <div class="text-xs text-amber-600 mt-1">
                        <i class="fas fa-gift mr-1"></i>
                        Mua thêm <?php echo number_format(500000 - $subtotal); ?>đ để được miễn phí vận chuyển
                    </div>
                    <?php endif; ?>
                    <div class="flex justify-between text-lg font-bold text-gray-900 pt-2 border-t border-gray-200">
                        <span>Tổng cộng:</span>
                        <span class="text-primary-600" id="totalDisplay"><?php echo number_format($total); ?>đ</span>
                    </div>
                </div>

                <!-- Place Order Button -->
                <button type="button" onclick="showPaymentModal()" 
                        class="w-full bg-primary-600 text-white py-3 rounded-lg font-semibold hover:bg-primary-700 transition-colors mt-4">
                    <i class="fas fa-check-circle mr-2"></i>
                    Đặt hàng
                </button>

                <p class="text-xs text-gray-500 text-center mt-3">
                    Bằng việc đặt hàng, bạn đồng ý với 
                    <a href="<?php echo SITE_URL; ?>/pages/chinh-sach-doi-tra.php" class="text-primary-600 hover:underline">điều khoản</a> 
                    của chúng tôi
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Payment Method Modal -->
<div id="paymentModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg max-w-md w-full p-6 relative">
        <button onclick="closePaymentModal()" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600">
            <i class="fas fa-times text-xl"></i>
        </button>
        
        <h3 class="text-xl font-bold text-gray-900 mb-4">
            <i class="fas fa-credit-card text-primary-600 mr-2"></i>
            Chọn phương thức thanh toán
        </h3>
        
        <div class="space-y-3 mb-6">
            <label class="flex items-start gap-3 p-4 border-2 border-gray-200 rounded-lg cursor-pointer hover:border-primary-500 transition payment-option">
                <input type="radio" name="payment_method" value="COD" checked
                       class="mt-1 text-primary-600 focus:ring-primary-500">
                <div class="flex-1">
                    <div class="font-semibold text-gray-900">Thanh toán khi nhận hàng (COD)</div>
                    <div class="text-sm text-gray-600 mt-1">Thanh toán bằng tiền mặt khi nhận hàng</div>
                </div>
            </label>
            
            <label class="flex items-start gap-3 p-4 border-2 border-gray-200 rounded-lg cursor-pointer hover:border-primary-500 transition payment-option">
                <input type="radio" name="payment_method" value="bank_transfer"
                       class="mt-1 text-primary-600 focus:ring-primary-500">
                <div class="flex-1">
                    <div class="font-semibold text-gray-900">Chuyển khoản ngân hàng</div>
                    <div class="text-sm text-gray-600 mt-1">
                        STK: <strong>677898888</strong> - MB Bank<br>
                        <span class="text-xs">Chủ TK: Huỳnh Quốc Nhân</span><br>
                        <span class="text-xs text-primary-600">✓ Tự động tạo mã QR thanh toán</span>
                    </div>
                </div>
            </label>
        </div>
        
        <!-- QR Code Display (Hidden by default) -->
        <div id="qrCodeSection" class="hidden mt-4 p-4 bg-gray-50 rounded-lg border border-gray-200">
            <div class="text-center">
                <h4 class="font-semibold text-gray-900 mb-3">Quét mã QR để thanh toán</h4>
                <div class="bg-white p-4 rounded-lg inline-block shadow-sm">
                    <img id="qrCodeImage" src="" alt="QR Code" class="w-64 h-64 mx-auto">
                </div>
                <div class="mt-3 text-sm text-gray-600">
                    <p class="font-semibold">Số tiền chuyển khoản: <span class="text-primary-600"><?php echo number_format($subtotal); ?>đ</span></p>
                    <p class="text-xs mt-1 text-amber-600">
                        <i class="fas fa-info-circle mr-1"></i>
                        Phí ship sẽ được tính khi giao hàng (<?php echo $shippingFee > 0 ? number_format($shippingFee) . 'đ' : 'Miễn phí'; ?>)
                    </p>
                    <p class="text-xs mt-1 text-gray-500">Nội dung chuyển khoản sẽ tự động điền</p>
                </div>
            </div>
        </div>
        
        <div class="flex gap-3">
            <button onclick="closePaymentModal()" 
                    class="flex-1 px-4 py-3 border border-gray-300 rounded-lg font-semibold hover:bg-gray-50 transition">
                Hủy
            </button>
            <button onclick="confirmOrder()" 
                    class="flex-1 px-4 py-3 bg-primary-600 text-white rounded-lg font-semibold hover:bg-primary-700 transition">
                Xác nhận đặt hàng
            </button>
        </div>
    </div>
</div>

<script>
let orderSubtotal = <?php echo $subtotal; ?>; // Subtotal without shipping
let orderTotal = <?php echo $total; ?>; // Total with shipping
let shippingFee = <?php echo $shippingFee; ?>;
let appliedCoupon = null;
let discountAmount = 0;
let userInfo = {
    name: '',
    phone: ''
};

function applyCoupon() {
    const code = document.getElementById('couponCode').value.trim();
    if (!code) {
        showCouponMessage('Vui lòng nhập mã giảm giá', 'error');
        return;
    }
    
    fetch('<?php echo SITE_URL; ?>/api/apply-coupon.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ code: code, subtotal: orderSubtotal })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            appliedCoupon = data.coupon;
            discountAmount = data.coupon.discount;
            
            // Update UI
            document.getElementById('couponCode').disabled = true;
            document.getElementById('appliedCoupon').classList.remove('hidden');
            document.getElementById('couponTitle').textContent = data.coupon.title + ' (-' + formatCurrency(discountAmount) + ')';
            document.getElementById('discountRow').classList.remove('hidden');
            document.getElementById('discountAmount').textContent = '-' + formatCurrency(discountAmount);
            
            // Update shipping if free shipping coupon
            if (data.coupon.type === 'shipping') {
                shippingFee = 0;
                document.getElementById('shippingDisplay').innerHTML = '<span class="text-green-600">Miễn phí</span>';
            }
            
            updateTotal();
            showCouponMessage(data.message, 'success');
        } else {
            showCouponMessage(data.message, 'error');
        }
    })
    .catch(err => {
        showCouponMessage('Có lỗi xảy ra', 'error');
    });
}

function removeCoupon() {
    appliedCoupon = null;
    discountAmount = 0;
    shippingFee = <?php echo $shippingFee; ?>;
    
    document.getElementById('couponCode').value = '';
    document.getElementById('couponCode').disabled = false;
    document.getElementById('appliedCoupon').classList.add('hidden');
    document.getElementById('discountRow').classList.add('hidden');
    document.getElementById('shippingDisplay').innerHTML = shippingFee > 0 ? formatCurrency(shippingFee) : '<span class="text-green-600">Miễn phí</span>';
    document.getElementById('couponMessage').classList.add('hidden');
    
    updateTotal();
}

function updateTotal() {
    orderTotal = orderSubtotal - discountAmount + shippingFee;
    if (orderTotal < 0) orderTotal = 0;
    document.getElementById('totalDisplay').textContent = formatCurrency(orderTotal);
}

function showCouponMessage(msg, type) {
    const el = document.getElementById('couponMessage');
    el.textContent = msg;
    el.className = 'mt-2 text-sm ' + (type === 'success' ? 'text-green-600' : 'text-red-600');
    el.classList.remove('hidden');
}

function formatCurrency(amount) {
    return new Intl.NumberFormat('vi-VN').format(amount) + 'đ';
}

function showPaymentModal() {
    const form = document.getElementById('checkoutForm');
    
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    // Store user info for QR code
    userInfo.name = form.querySelector('input[name="ho_ten"]').value;
    userInfo.phone = form.querySelector('input[name="dien_thoai"]').value;
    
    document.getElementById('paymentModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closePaymentModal() {
    document.getElementById('paymentModal').classList.add('hidden');
    document.getElementById('qrCodeSection').classList.add('hidden');
    document.body.style.overflow = 'auto';
}

function generateQRCode() {
    // Bank info
    const bankId = 'MB'; // MB Bank
    const accountNo = '677898888';
    const accountName = 'HUYNH QUOC NHAN';
    const amount = orderSubtotal; // Use subtotal only, no shipping fee
    const description = userInfo.name.replace(/\s+/g, '') + ' ' + userInfo.phone;
    
    // Using VietQR API
    const qrUrl = `https://img.vietqr.io/image/${bankId}-${accountNo}-compact2.png?amount=${amount}&addInfo=${encodeURIComponent(description)}&accountName=${encodeURIComponent(accountName)}`;
    
    document.getElementById('qrCodeImage').src = qrUrl;
    document.getElementById('qrCodeSection').classList.remove('hidden');
}

function confirmOrder() {
    const form = document.getElementById('checkoutForm');
    const formData = new FormData(form);
    const data = Object.fromEntries(formData);
    
    // Get selected payment method from modal
    const paymentMethod = document.querySelector('input[name="payment_method"]:checked').value;
    data.phuong_thuc_thanh_toan = paymentMethod;
    
    // Add coupon info
    if (appliedCoupon) {
        data.coupon_id = appliedCoupon.id;
        data.coupon_code = appliedCoupon.code;
        data.discount_amount = discountAmount;
    }
    
    // Close modal and show loading
    closePaymentModal();
    
    const btn = document.querySelector('button[onclick="showPaymentModal()"]');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Đang xử lý...';
    
    fetch('<?php echo SITE_URL; ?>/api/checkout.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            alert('Đặt hàng thành công! Mã đơn hàng: ' + result.order_code);
            window.location.href = '<?php echo SITE_URL; ?>/pages/account.php';
        } else {
            alert('Lỗi: ' + result.message);
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-check-circle mr-2"></i>Đặt hàng';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Có lỗi xảy ra. Vui lòng thử lại!');
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-check-circle mr-2"></i>Đặt hàng';
    });
}

// Highlight selected payment option
document.addEventListener('DOMContentLoaded', function() {
    const paymentOptions = document.querySelectorAll('.payment-option');
    
    paymentOptions.forEach(option => {
        option.addEventListener('click', function() {
            paymentOptions.forEach(opt => {
                opt.classList.remove('border-primary-500', 'bg-primary-50');
                opt.classList.add('border-gray-200');
            });
            
            if (this.querySelector('input[type="radio"]').checked) {
                this.classList.remove('border-gray-200');
                this.classList.add('border-primary-500', 'bg-primary-50');
            }
        });
        
        option.querySelector('input[type="radio"]').addEventListener('change', function() {
            if (this.checked) {
                paymentOptions.forEach(opt => {
                    opt.classList.remove('border-primary-500', 'bg-primary-50');
                    opt.classList.add('border-gray-200');
                });
                option.classList.remove('border-gray-200');
                option.classList.add('border-primary-500', 'bg-primary-50');
                
                // Show QR code if bank transfer is selected
                if (this.value === 'bank_transfer') {
                    generateQRCode();
                } else {
                    document.getElementById('qrCodeSection').classList.add('hidden');
                }
            }
        });
    });
    
    // Set initial selected state
    const checkedOption = document.querySelector('.payment-option input[type="radio"]:checked');
    if (checkedOption) {
        checkedOption.closest('.payment-option').classList.add('border-primary-500', 'bg-primary-50');
        checkedOption.closest('.payment-option').classList.remove('border-gray-200');
    }
});

// Close modal on outside click
document.getElementById('paymentModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closePaymentModal();
    }
});
</script>

<?php include __DIR__ . '/../components/footer.php'; ?>
