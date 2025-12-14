<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

$pageTitle = 'Chính sách đổi trả';
include __DIR__ . '/../components/header.php';
?>

<!-- Breadcrumb -->
<div class="bg-gray-100 py-4">
    <div class="container mx-auto px-4">
        <nav class="flex items-center gap-2 text-sm">
            <a href="<?php echo SITE_URL; ?>/index.php" class="text-gray-600 hover:text-primary-600">Trang chủ</a>
            <i class="fas fa-chevron-right text-gray-400 text-xs"></i>
            <span class="text-gray-900">Chính sách đổi trả</span>
        </nav>
    </div>
</div>

<div class="container mx-auto px-4 py-12">
    <div class="max-w-4xl mx-auto">
        <div class="bg-white rounded-lg shadow-md p-8">
            <h1 class="text-3xl font-bold mb-6 text-primary-600">
                <i class="fas fa-exchange-alt mr-3"></i>Chính sách đổi trả
            </h1>
            
            <div class="prose max-w-none">
                <h2 class="text-2xl font-bold mt-8 mb-4">1. Điều kiện đổi trả</h2>
                <p class="mb-4">Sản phẩm được đổi/trả trong vòng <strong>7 ngày</strong> kể từ ngày nhận hàng khi đáp ứng các điều kiện sau:</p>
                <ul class="list-disc pl-6 mb-4 space-y-2">
                    <li>Sản phẩm còn nguyên tem mác, chưa qua sử dụng</li>
                    <li>Sản phẩm không bị dơ bẩn, hư hỏng do lỗi người dùng</li>
                    <li>Có đầy đủ hóa đơn, phiếu bảo hành (nếu có)</li>
                    <li>Sản phẩm còn đầy đủ phụ kiện, quà tặng kèm theo</li>
                </ul>

                <h2 class="text-2xl font-bold mt-8 mb-4">2. Trường hợp được đổi trả</h2>
                <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-4">
                    <p class="font-semibold text-green-800 mb-2">Lỗi từ nhà sản xuất hoặc vận chuyển:</p>
                    <ul class="list-disc pl-6 space-y-1 text-gray-700">
                        <li>Sản phẩm bị lỗi, hư hỏng do nhà sản xuất</li>
                        <li>Sản phẩm bị hư hại trong quá trình vận chuyển</li>
                        <li>Giao sai mẫu mã, size, màu sắc</li>
                        <li>Thiếu số lượng hoặc thiếu phụ kiện</li>
                    </ul>
                </div>

                <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-4">
                    <p class="font-semibold text-blue-800 mb-2">Khách hàng muốn đổi size/màu:</p>
                    <ul class="list-disc pl-6 space-y-1 text-gray-700">
                        <li>Đổi size trong vòng 7 ngày (miễn phí lần đầu)</li>
                        <li>Đổi màu sắc theo sản phẩm có sẵn</li>
                        <li>Phí ship đổi hàng: 30.000đ (nếu đổi lần 2)</li>
                    </ul>
                </div>

                <h2 class="text-2xl font-bold mt-8 mb-4">3. Trường hợp KHÔNG được đổi trả</h2>
                <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-4">
                    <ul class="list-disc pl-6 space-y-1 text-gray-700">
                        <li>Sản phẩm đã qua sử dụng, giặt tẩy</li>
                        <li>Sản phẩm bị rách, bẩn, mất tem mác</li>
                        <li>Quá thời gian đổi trả (7 ngày)</li>
                        <li>Không có hóa đơn mua hàng</li>
                        <li>Sản phẩm khuyến mãi, giảm giá trên 50%</li>
                    </ul>
                </div>

                <h2 class="text-2xl font-bold mt-8 mb-4">4. Quy trình đổi trả</h2>
                <div class="space-y-4">
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 bg-primary-100 rounded-full flex items-center justify-center flex-shrink-0">
                            <span class="text-primary-600 font-bold text-lg">1</span>
                        </div>
                        <div>
                            <h4 class="font-semibold mb-1">Liên hệ với chúng tôi</h4>
                            <p class="text-gray-600">Gọi hotline <?php echo SITE_PHONE; ?> hoặc inbox fanpage để thông báo đổi/trả hàng</p>
                        </div>
                    </div>

                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 bg-primary-100 rounded-full flex items-center justify-center flex-shrink-0">
                            <span class="text-primary-600 font-bold text-lg">2</span>
                        </div>
                        <div>
                            <h4 class="font-semibold mb-1">Gửi sản phẩm về</h4>
                            <p class="text-gray-600">Đóng gói sản phẩm cẩn thận và gửi về địa chỉ: 123 Trần Hưng Đạo, Quận 1, TPHCM</p>
                        </div>
                    </div>

                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 bg-primary-100 rounded-full flex items-center justify-center flex-shrink-0">
                            <span class="text-primary-600 font-bold text-lg">3</span>
                        </div>
                        <div>
                            <h4 class="font-semibold mb-1">Kiểm tra sản phẩm</h4>
                            <p class="text-gray-600">Chúng tôi sẽ kiểm tra trong vòng 24 giờ và thông báo kết quả</p>
                        </div>
                    </div>

                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 bg-primary-100 rounded-full flex items-center justify-center flex-shrink-0">
                            <span class="text-primary-600 font-bold text-lg">4</span>
                        </div>
                        <div>
                            <h4 class="font-semibold mb-1">Đổi hàng/Hoàn tiền</h4>
                            <p class="text-gray-600">Gửi sản phẩm mới hoặc hoàn tiền trong vòng 2-3 ngày làm việc</p>
                        </div>
                    </div>
                </div>

                <h2 class="text-2xl font-bold mt-8 mb-4">5. Chi phí đổi trả</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full border border-gray-300 mb-4">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="border border-gray-300 px-4 py-2 text-left">Trường hợp</th>
                                <th class="border border-gray-300 px-4 py-2 text-left">Phí vận chuyển</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="border border-gray-300 px-4 py-2">Lỗi từ shop</td>
                                <td class="border border-gray-300 px-4 py-2 text-green-600 font-semibold">Miễn phí 100%</td>
                            </tr>
                            <tr>
                                <td class="border border-gray-300 px-4 py-2">Đổi size lần 1</td>
                                <td class="border border-gray-300 px-4 py-2 text-green-600 font-semibold">Miễn phí</td>
                            </tr>
                            <tr>
                                <td class="border border-gray-300 px-4 py-2">Đổi size lần 2 trở đi</td>
                                <td class="border border-gray-300 px-4 py-2">30.000đ/lượt</td>
                            </tr>
                            <tr>
                                <td class="border border-gray-300 px-4 py-2">Trả hàng do khách đổi ý</td>
                                <td class="border border-gray-300 px-4 py-2">Khách chịu phí 2 chiều</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="bg-yellow-50 border-l-4 border-yellow-500 p-4 mt-6">
                    <p class="text-sm text-gray-700">
                        <strong class="text-yellow-800">Lưu ý:</strong> Thời gian hoàn tiền: 3-7 ngày làm việc tùy theo phương thức thanh toán ban đầu
                    </p>
                </div>

                <div class="mt-8 p-6 bg-gradient-to-r from-primary-50 to-blue-50 rounded-lg">
                    <h3 class="text-xl font-bold mb-3">Cần hỗ trợ?</h3>
                    <p class="mb-4">Liên hệ với chúng tôi qua:</p>
                    <div class="flex flex-wrap gap-4">
                        <a href="tel:<?php echo SITE_PHONE; ?>" class="inline-flex items-center gap-2 bg-white px-4 py-2 rounded-lg hover:shadow-md transition">
                            <i class="fas fa-phone text-primary-600"></i>
                            <span><?php echo SITE_PHONE; ?></span>
                        </a>
                        <a href="mailto:NTHsport@gmail.com" class="inline-flex items-center gap-2 bg-white px-4 py-2 rounded-lg hover:shadow-md transition">
                            <i class="fas fa-envelope text-primary-600"></i>
                            <span>NTHsport@gmail.com</span>
                        </a>
                        <a href="/Web/pages/contact.php" class="inline-flex items-center gap-2 bg-primary-600 text-white px-4 py-2 rounded-lg hover:bg-primary-700 transition">
                            <i class="fas fa-comment"></i>
                            <span>Gửi yêu cầu</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../components/footer.php'; ?>
