<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

$pageTitle = 'Phương thức thanh toán';
include __DIR__ . '/../components/header.php';
?>

<!-- Breadcrumb -->
<div class="bg-gray-100 py-4">
    <div class="container mx-auto px-4">
        <nav class="flex items-center gap-2 text-sm">
            <a href="<?php echo SITE_URL; ?>/index.php" class="text-gray-600 hover:text-primary-600">Trang chủ</a>
            <i class="fas fa-chevron-right text-gray-400 text-xs"></i>
            <span class="text-gray-900">Phương thức thanh toán</span>
        </nav>
    </div>
</div>

<div class="container mx-auto px-4 py-12">
    <div class="max-w-4xl mx-auto">
        <div class="bg-white rounded-lg shadow-md p-8">
            <h1 class="text-3xl font-bold mb-6 text-primary-600">
                <i class="fas fa-credit-card mr-3"></i>Phương thức thanh toán
            </h1>
            
            <div class="prose max-w-none">
                <p class="text-lg mb-8">
                    <?php echo SITE_NAME; ?> hỗ trợ 2 phương thức thanh toán tiện lợi và an toàn: <strong>COD</strong> (thanh toán khi nhận hàng) và <strong>Chuyển khoản ngân hàng</strong>
                </p>

                <!-- COD -->
                <div class="border-2 border-green-200 rounded-lg p-6 mb-6 hover:shadow-lg transition">
                    <div class="flex items-center gap-4 mb-4">
                        <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-money-bill-wave text-green-600 text-3xl"></i>
                        </div>
                        <div>
                            <h2 class="text-2xl font-bold text-green-700">Thanh toán khi nhận hàng (COD)</h2>
                            <p class="text-sm text-gray-600">Phương thức phổ biến nhất</p>
                        </div>
                    </div>
                    
                    <div class="space-y-3">
                        <div class="flex items-start gap-2">
                            <i class="fas fa-check-circle text-green-500 mt-1"></i>
                            <p>Thanh toán trực tiếp cho shipper khi nhận hàng</p>
                        </div>
                        <div class="flex items-start gap-2">
                            <i class="fas fa-check-circle text-green-500 mt-1"></i>
                            <p>Được kiểm tra hàng trước khi thanh toán</p>
                        </div>
                        <div class="flex items-start gap-2">
                            <i class="fas fa-check-circle text-green-500 mt-1"></i>
                            <p>Phí ship: Miễn phí cho đơn từ 500.000đ</p>
                        </div>
                        <div class="flex items-start gap-2">
                            <i class="fas fa-check-circle text-green-500 mt-1"></i>
                            <p>Áp dụng: Toàn quốc</p>
                        </div>
                    </div>

                    <div class="mt-4 p-4 bg-yellow-50 rounded-lg">
                        <p class="text-sm">
                            <i class="fas fa-info-circle text-yellow-600 mr-2"></i>
                            <strong>Lưu ý:</strong> Vui lòng chuẩn bị đủ tiền mặt và kiểm tra kỹ hàng trước khi thanh toán
                        </p>
                    </div>
                </div>

                <!-- Chuyển khoản -->
                <div class="border-2 border-blue-200 rounded-lg p-6 mb-6 hover:shadow-lg transition">
                    <div class="flex items-center gap-4 mb-4">
                        <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-university text-blue-600 text-3xl"></i>
                        </div>
                        <div>
                            <h2 class="text-2xl font-bold text-blue-700">Chuyển khoản ngân hàng</h2>
                            <p class="text-sm text-gray-600">Nhanh chóng, an toàn</p>
                        </div>
                    </div>
                    
                    <p class="mb-4">Thông tin tài khoản ngân hàng:</p>
                    
                    <div class="space-y-4">
                        <div class="bg-gradient-to-r from-purple-50 to-purple-100 p-4 rounded-lg">
                            <div class="flex items-center gap-3 mb-3">
                                <img src="https://api.vietqr.io/img/MB.png" alt="MB Bank" class="w-12 h-12">
                                <h4 class="font-bold text-lg">Ngân hàng MB Bank</h4>
                            </div>
                            <div class="space-y-1 text-sm">
                                <p><strong>Số tài khoản:</strong> 677898888</p>
                                <p><strong>Chủ tài khoản:</strong> HUỲNH QUỐC NHÂN</p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 p-4 bg-orange-50 rounded-lg">
                        <p class="text-sm font-semibold mb-2">Nội dung chuyển khoản:</p>
                        <p class="font-mono bg-white px-3 py-2 rounded border text-center">
                            [Số điện thoại] [Họ tên]
                        </p>
                        <p class="text-xs text-gray-600 mt-2">Ví dụ: 0909123456 NGUYEN VAN A</p>
                    </div>

                    <div class="mt-4 space-y-2 text-sm">
                        <div class="flex items-start gap-2">
                            <i class="fas fa-check-circle text-blue-500 mt-1"></i>
                            <p>Đơn hàng được xác nhận ngay sau khi nhận được tiền</p>
                        </div>
                        <div class="flex items-start gap-2">
                            <i class="fas fa-check-circle text-blue-500 mt-1"></i>
                            <p>Giao hàng nhanh hơn 1-2 ngày so với COD</p>
                        </div>
                        <div class="flex items-start gap-2">
                            <i class="fas fa-check-circle text-blue-500 mt-1"></i>
                            <p>Phí ship: Miễn phí cho đơn từ 500.000đ</p>
                        </div>
                    </div>
                </div>

                <!-- So sánh các phương thức -->
                <div class="mt-8">
                    <h3 class="text-2xl font-bold mb-6 text-center">
                        <i class="fas fa-balance-scale text-primary-600 mr-2"></i>
                        So sánh các phương thức thanh toán
                    </h3>
                    
                    <div class="grid md:grid-cols-2 gap-6">
                        <!-- COD Card -->
                        <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-xl p-6 border-2 border-green-200 hover:shadow-lg transition">
                            <div class="flex items-center gap-3 mb-4">
                                <div class="w-12 h-12 bg-green-500 rounded-full flex items-center justify-center">
                                    <i class="fas fa-money-bill-wave text-white text-xl"></i>
                                </div>
                                <h4 class="text-xl font-bold text-green-700">COD</h4>
                            </div>
                            
                            <div class="space-y-3">
                                <div class="flex items-center justify-between py-2 border-b border-green-200">
                                    <span class="text-gray-600">Tốc độ xác nhận</span>
                                    <span class="text-yellow-500">⭐⭐⭐</span>
                                </div>
                                <div class="flex items-center justify-between py-2 border-b border-green-200">
                                    <span class="text-gray-600">Kiểm tra hàng trước</span>
                                    <span class="text-green-600 font-semibold"><i class="fas fa-check-circle mr-1"></i>Có</span>
                                </div>
                                <div class="flex items-center justify-between py-2 border-b border-green-200">
                                    <span class="text-gray-600">Phí ship</span>
                                    <span class="text-green-600 font-medium">Theo quy định</span>
                                </div>
                                <div class="flex items-center justify-between py-2 border-b border-green-200">
                                    <span class="text-gray-600">Bảo mật</span>
                                    <span class="text-yellow-500">⭐⭐⭐⭐</span>
                                </div>
                                <div class="flex items-center justify-between py-2">
                                    <span class="text-gray-600">Phù hợp với</span>
                                    <span class="text-green-700 font-medium">Mọi khách hàng</span>
                                </div>
                            </div>
                            
                            <div class="mt-4 p-3 bg-green-200 rounded-lg text-center">
                                <span class="text-green-800 font-semibold">
                                    <i class="fas fa-star mr-1"></i>Phổ biến nhất
                                </span>
                            </div>
                        </div>
                        
                        <!-- Bank Transfer Card -->
                        <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl p-6 border-2 border-blue-200 hover:shadow-lg transition">
                            <div class="flex items-center gap-3 mb-4">
                                <div class="w-12 h-12 bg-blue-500 rounded-full flex items-center justify-center">
                                    <i class="fas fa-university text-white text-xl"></i>
                                </div>
                                <h4 class="text-xl font-bold text-blue-700">Chuyển khoản</h4>
                            </div>
                            
                            <div class="space-y-3">
                                <div class="flex items-center justify-between py-2 border-b border-blue-200">
                                    <span class="text-gray-600">Tốc độ xác nhận</span>
                                    <span class="text-yellow-500">⭐⭐⭐⭐</span>
                                </div>
                                <div class="flex items-center justify-between py-2 border-b border-blue-200">
                                    <span class="text-gray-600">Kiểm tra hàng trước</span>
                                    <span class="text-red-500 font-semibold"><i class="fas fa-times-circle mr-1"></i>Không</span>
                                </div>
                                <div class="flex items-center justify-between py-2 border-b border-blue-200">
                                    <span class="text-gray-600">Phí ship</span>
                                    <span class="text-blue-600 font-medium">Theo quy định</span>
                                </div>
                                <div class="flex items-center justify-between py-2 border-b border-blue-200">
                                    <span class="text-gray-600">Bảo mật</span>
                                    <span class="text-yellow-500">⭐⭐⭐⭐</span>
                                </div>
                                <div class="flex items-center justify-between py-2">
                                    <span class="text-gray-600">Phù hợp với</span>
                                    <span class="text-blue-700 font-medium">Khách quen, đơn lớn</span>
                                </div>
                            </div>
                            
                            <div class="mt-4 p-3 bg-blue-200 rounded-lg text-center">
                                <span class="text-blue-800 font-semibold">
                                    <i class="fas fa-bolt mr-1"></i>Giao hàng nhanh hơn
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Summary Table -->
                    <div class="mt-8 overflow-x-auto">
                        <table class="w-full border-collapse rounded-lg overflow-hidden shadow-sm">
                            <thead>
                                <tr class="bg-primary-600 text-white">
                                    <th class="px-6 py-4 text-left font-semibold">Tiêu chí</th>
                                    <th class="px-6 py-4 text-center font-semibold">
                                        <i class="fas fa-money-bill-wave mr-2"></i>COD
                                    </th>
                                    <th class="px-6 py-4 text-center font-semibold">
                                        <i class="fas fa-university mr-2"></i>Chuyển khoản
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white">
                                <tr class="border-b border-gray-200 hover:bg-gray-50">
                                    <td class="px-6 py-4 font-medium text-gray-900">Tốc độ xác nhận đơn</td>
                                    <td class="px-6 py-4 text-center">Khi giao hàng</td>
                                    <td class="px-6 py-4 text-center text-blue-600 font-medium">Ngay lập tức</td>
                                </tr>
                                <tr class="border-b border-gray-200 hover:bg-gray-50 bg-gray-50">
                                    <td class="px-6 py-4 font-medium text-gray-900">Kiểm tra hàng trước</td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="inline-flex items-center px-3 py-1 rounded-full bg-green-100 text-green-700">
                                            <i class="fas fa-check mr-1"></i>Được
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="inline-flex items-center px-3 py-1 rounded-full bg-red-100 text-red-700">
                                            <i class="fas fa-times mr-1"></i>Không
                                        </span>
                                    </td>
                                </tr>
                                <tr class="border-b border-gray-200 hover:bg-gray-50">
                                    <td class="px-6 py-4 font-medium text-gray-900">Phí vận chuyển</td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="inline-flex items-center px-3 py-1 rounded-full bg-gray-100 text-gray-700">
                                            Theo quy định
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="inline-flex items-center px-3 py-1 rounded-full bg-gray-100 text-gray-700">
                                            Theo quy định
                                        </span>
                                    </td>
                                </tr>
                                <tr class="border-b border-gray-200 hover:bg-gray-50 bg-gray-50">
                                    <td class="px-6 py-4 font-medium text-gray-900">Thời gian giao hàng</td>
                                    <td class="px-6 py-4 text-center">2-5 ngày</td>
                                    <td class="px-6 py-4 text-center text-blue-600 font-medium">1-3 ngày</td>
                                </tr>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 font-medium text-gray-900">Độ an toàn</td>
                                    <td class="px-6 py-4 text-center text-yellow-500">⭐⭐⭐⭐</td>
                                    <td class="px-6 py-4 text-center text-yellow-500">⭐⭐⭐⭐</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Recommendation -->
                    <div class="mt-6 p-4 bg-amber-50 border border-amber-200 rounded-lg">
                        <div class="flex items-start gap-3">
                            <i class="fas fa-lightbulb text-amber-500 text-xl mt-1"></i>
                            <div>
                                <h4 class="font-semibold text-amber-800 mb-1">Gợi ý chọn phương thức</h4>
                                <ul class="text-sm text-amber-700 space-y-1">
                                    <li>• <strong>COD:</strong> Phù hợp nếu bạn muốn kiểm tra hàng trước khi thanh toán</li>
                                    <li>• <strong>Chuyển khoản:</strong> Phù hợp nếu bạn muốn nhận hàng nhanh hơn</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Shipping Fee Info -->
                    <div class="mt-4 p-4 bg-green-50 border border-green-200 rounded-lg">
                        <div class="flex items-start gap-3">
                            <i class="fas fa-truck text-green-500 text-xl mt-1"></i>
                            <div>
                                <h4 class="font-semibold text-green-800 mb-1">Chính sách phí vận chuyển</h4>
                                <ul class="text-sm text-green-700 space-y-1">
                                    <li>• Đơn hàng từ <strong>500.000đ</strong>: Miễn phí ship toàn quốc</li>
                                    <li>• Đơn hàng dưới 500.000đ: 30.000đ</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-8 p-6 bg-gradient-to-r from-primary-50 to-blue-50 rounded-lg">
                    <h3 class="text-xl font-bold mb-3">Cần hỗ trợ thanh toán?</h3>
                    <p class="mb-4">Liên hệ với chúng tôi nếu gặp vấn đề:</p>
                    <div class="flex flex-wrap gap-4">
                        <a href="tel:<?php echo SITE_PHONE; ?>" class="inline-flex items-center gap-2 bg-white px-4 py-2 rounded-lg hover:shadow-md transition">
                            <i class="fas fa-phone text-primary-600"></i>
                            <span><?php echo SITE_PHONE; ?></span>
                        </a>
                        <a href="/Web/pages/contact.php" class="inline-flex items-center gap-2 bg-primary-600 text-white px-4 py-2 rounded-lg hover:bg-primary-700 transition">
                            <i class="fas fa-comment"></i>
                            <span>Chat hỗ trợ</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../components/footer.php'; ?>
