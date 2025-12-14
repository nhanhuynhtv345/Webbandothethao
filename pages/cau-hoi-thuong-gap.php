<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

$pageTitle = 'Câu hỏi thường gặp';
include __DIR__ . '/../components/header.php';
?>

<!-- Breadcrumb -->
<div class="bg-gray-100 py-4">
    <div class="container mx-auto px-4">
        <nav class="flex items-center gap-2 text-sm">
            <a href="<?php echo SITE_URL; ?>/index.php" class="text-gray-600 hover:text-primary-600">Trang chủ</a>
            <i class="fas fa-chevron-right text-gray-400 text-xs"></i>
            <span class="text-gray-900">Câu hỏi thường gặp</span>
        </nav>
    </div>
</div>

<div class="container mx-auto px-4 py-12">
    <div class="max-w-4xl mx-auto">
        <div class="text-center mb-12">
            <h1 class="text-4xl font-bold mb-4 text-primary-600">
                <i class="fas fa-question-circle mr-3"></i>Câu hỏi thường gặp
            </h1>
            <p class="text-lg text-gray-600">Giải đáp các thắc mắc phổ biến của khách hàng</p>
        </div>

        <!-- Search FAQ -->
        <div class="mb-8">
            <div class="relative">
                <input type="text" 
                       id="searchFAQ" 
                       placeholder="Tìm kiếm câu hỏi..." 
                       class="w-full px-4 py-3 pl-12 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
            </div>
        </div>

        <!-- FAQ Categories -->
        <div class="grid md:grid-cols-4 gap-4 mb-8">
            <button onclick="filterFAQ('all')" class="faq-category active py-3 px-4 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition">
                <i class="fas fa-th mr-2"></i>Tất cả
            </button>
            <button onclick="filterFAQ('order')" class="faq-category py-3 px-4 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition">
                <i class="fas fa-shopping-bag mr-2"></i>Đặt hàng
            </button>
            <button onclick="filterFAQ('payment')" class="faq-category py-3 px-4 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition">
                <i class="fas fa-credit-card mr-2"></i>Thanh toán
            </button>
            <button onclick="filterFAQ('shipping')" class="faq-category py-3 px-4 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition">
                <i class="fas fa-truck mr-2"></i>Vận chuyển
            </button>
        </div>

        <!-- FAQ Items -->
        <div class="space-y-4" id="faqContainer">
            
            <!-- Đặt hàng -->
            <div class="faq-item bg-white rounded-lg shadow-md overflow-hidden" data-category="order">
                <button class="faq-question w-full text-left px-6 py-4 flex items-center justify-between hover:bg-gray-50 transition">
                    <span class="font-semibold text-gray-900 pr-4">Làm thế nào để đặt hàng trên website?</span>
                    <i class="fas fa-chevron-down text-primary-600 transform transition-transform"></i>
                </button>
                <div class="faq-answer hidden px-6 pb-4 text-gray-600">
                    <p class="mb-3">Bạn có thể đặt hàng dễ dàng qua 5 bước:</p>
                    <ol class="list-decimal pl-5 space-y-1">
                        <li>Tìm kiếm sản phẩm cần mua</li>
                        <li>Chọn size, màu sắc và thêm vào giỏ hàng</li>
                        <li>Vào giỏ hàng và bấm "Thanh toán"</li>
                        <li>Điền thông tin giao hàng</li>
                        <li>Chọn phương thức thanh toán và hoàn tất</li>
                    </ol>
                    <p class="mt-3"><a href="/Web/pages/huong-dan-mua-hang.php" class="text-primary-600 hover:underline">Xem hướng dẫn chi tiết →</a></p>
                </div>
            </div>

            <div class="faq-item bg-white rounded-lg shadow-md overflow-hidden" data-category="order">
                <button class="faq-question w-full text-left px-6 py-4 flex items-center justify-between hover:bg-gray-50 transition">
                    <span class="font-semibold text-gray-900 pr-4">Tôi có cần đăng ký tài khoản để mua hàng không?</span>
                    <i class="fas fa-chevron-down text-primary-600 transform transition-transform"></i>
                </button>
                <div class="faq-answer hidden px-6 pb-4 text-gray-600">
                    <p>Không bắt buộc, bạn có thể mua hàng với tư cách khách vãng lai. Tuy nhiên, việc đăng ký tài khoản sẽ giúp bạn:</p>
                    <ul class="list-disc pl-5 mt-2 space-y-1">
                        <li>Theo dõi đơn hàng dễ dàng</li>
                        <li>Lưu địa chỉ giao hàng</li>
                        <li>Xem lịch sử mua hàng</li>
                        <li>Nhận thông báo ưu đãi đặc biệt</li>
                    </ul>
                </div>
            </div>

            <div class="faq-item bg-white rounded-lg shadow-md overflow-hidden" data-category="order">
                <button class="faq-question w-full text-left px-6 py-4 flex items-center justify-between hover:bg-gray-50 transition">
                    <span class="font-semibold text-gray-900 pr-4">Làm sao để kiểm tra trạng thái đơn hàng?</span>
                    <i class="fas fa-chevron-down text-primary-600 transform transition-transform"></i>
                </button>
                <div class="faq-answer hidden px-6 pb-4 text-gray-600">
                    <p>Có 3 cách để kiểm tra:</p>
                    <ol class="list-decimal pl-5 mt-2 space-y-1">
                        <li><strong>Đăng nhập tài khoản</strong> → Vào mục "Đơn hàng của tôi"</li>
                        <li><strong>Kiểm tra email</strong> → Chúng tôi sẽ gửi email cập nhật trạng thái</li>
                        <li><strong>Gọi hotline</strong> → <?php echo SITE_PHONE; ?> với mã đơn hàng</li>
                    </ol>
                </div>
            </div>

            <!-- Thanh toán -->
            <div class="faq-item bg-white rounded-lg shadow-md overflow-hidden" data-category="payment">
                <button class="faq-question w-full text-left px-6 py-4 flex items-center justify-between hover:bg-gray-50 transition">
                    <span class="font-semibold text-gray-900 pr-4">Website hỗ trợ những phương thức thanh toán nào?</span>
                    <i class="fas fa-chevron-down text-primary-600 transform transition-transform"></i>
                </button>
                <div class="faq-answer hidden px-6 pb-4 text-gray-600">
                    <p class="mb-2">Chúng tôi hỗ trợ 2 phương thức:</p>
                    <ul class="list-disc pl-5 space-y-1">
                        <li><strong>COD</strong> - Thanh toán khi nhận hàng (phổ biến nhất)</li>
                        <li><strong>Chuyển khoản ngân hàng</strong> - MB Bank (giao hàng nhanh hơn)</li>
                    </ul>
                    <p class="mt-3"><a href="/Web/pages/phuong-thuc-thanh-toan.php" class="text-primary-600 hover:underline">Xem chi tiết →</a></p>
                </div>
            </div>

            <div class="faq-item bg-white rounded-lg shadow-md overflow-hidden" data-category="payment">
                <button class="faq-question w-full text-left px-6 py-4 flex items-center justify-between hover:bg-gray-50 transition">
                    <span class="font-semibold text-gray-900 pr-4">Tôi có thể thanh toán một phần bằng thẻ quà tặng không?</span>
                    <i class="fas fa-chevron-down text-primary-600 transform transition-transform"></i>
                </button>
                <div class="faq-answer hidden px-6 pb-4 text-gray-600">
                    <p>Có, bạn có thể sử dụng mã giảm giá/voucher khi thanh toán. Nhập mã vào ô "Mã giảm giá" trong giỏ hàng trước khi đặt hàng. Số tiền còn lại bạn có thể thanh toán bằng các phương thức khác.</p>
                </div>
            </div>

            <div class="faq-item bg-white rounded-lg shadow-md overflow-hidden" data-category="payment">
                <button class="faq-question w-full text-left px-6 py-4 flex items-center justify-between hover:bg-gray-50 transition">
                    <span class="font-semibold text-gray-900 pr-4">Thanh toán online có an toàn không?</span>
                    <i class="fas fa-chevron-down text-primary-600 transform transition-transform"></i>
                </button>
                <div class="faq-answer hidden px-6 pb-4 text-gray-600">
                    <p>Hoàn toàn an toàn! Chúng tôi sử dụng:</p>
                    <ul class="list-disc pl-5 mt-2 space-y-1">
                        <li>Mã hóa SSL 256-bit cho tất cả giao dịch</li>
                        <li>Chuyển khoản qua ngân hàng uy tín (MB Bank)</li>
                        <li>Không lưu trữ thông tin thẻ của khách hàng</li>
                        <li>Chính sách bảo mật thông tin nghiêm ngặt</li>
                    </ul>
                </div>
            </div>

            <!-- Vận chuyển -->
            <div class="faq-item bg-white rounded-lg shadow-md overflow-hidden" data-category="shipping">
                <button class="faq-question w-full text-left px-6 py-4 flex items-center justify-between hover:bg-gray-50 transition">
                    <span class="font-semibold text-gray-900 pr-4">Thời gian giao hàng mất bao lâu?</span>
                    <i class="fas fa-chevron-down text-primary-600 transform transition-transform"></i>
                </button>
                <div class="faq-answer hidden px-6 pb-4 text-gray-600">
                    <p class="mb-2">Thời gian giao hàng phụ thuộc vào khu vực:</p>
                    <ul class="list-disc pl-5 space-y-1">
                        <li><strong>Nội thành TP.HCM:</strong> 1-2 ngày</li>
                        <li><strong>Tỉnh thành lân cận:</strong> 2-3 ngày</li>
                        <li><strong>Các tỉnh thành khác:</strong> 3-5 ngày</li>
                        <li><strong>Vùng xa, hải đảo:</strong> 5-7 ngày</li>
                    </ul>
                    <p class="mt-2 text-sm text-gray-500"><em>*Thời gian có thể kéo dài hơn trong dịp lễ, Tết</em></p>
                </div>
            </div>

            <div class="faq-item bg-white rounded-lg shadow-md overflow-hidden" data-category="shipping">
                <button class="faq-question w-full text-left px-6 py-4 flex items-center justify-between hover:bg-gray-50 transition">
                    <span class="font-semibold text-gray-900 pr-4">Phí vận chuyển được tính như thế nào?</span>
                    <i class="fas fa-chevron-down text-primary-600 transform transition-transform"></i>
                </button>
                <div class="faq-answer hidden px-6 pb-4 text-gray-600">
                    <p class="mb-2">Phí ship phụ thuộc vào giá trị đơn hàng và khu vực:</p>
                    <ul class="list-disc pl-5 space-y-1">
                        <li><strong>Đơn từ 500.000đ:</strong> Miễn phí toàn quốc</li>
                        <li><strong>Đơn dưới 500.000đ:</strong> 30.000đ</li>
                    </ul>
                </div>
            </div>

            <div class="faq-item bg-white rounded-lg shadow-md overflow-hidden" data-category="shipping">
                <button class="faq-question w-full text-left px-6 py-4 flex items-center justify-between hover:bg-gray-50 transition">
                    <span class="font-semibold text-gray-900 pr-4">Tôi có thể thay đổi địa chỉ giao hàng sau khi đặt không?</span>
                    <i class="fas fa-chevron-down text-primary-600 transform transition-transform"></i>
                </button>
                <div class="faq-answer hidden px-6 pb-4 text-gray-600">
                    <p>Có thể, nhưng chỉ khi đơn hàng chưa được giao cho đơn vị vận chuyển. Vui lòng liên hệ ngay với chúng tôi qua hotline <?php echo SITE_PHONE; ?> để được hỗ trợ thay đổi địa chỉ.</p>
                </div>
            </div>

            <!-- Đổi trả & Bảo hành -->
            <div class="faq-item bg-white rounded-lg shadow-md overflow-hidden" data-category="all">
                <button class="faq-question w-full text-left px-6 py-4 flex items-center justify-between hover:bg-gray-50 transition">
                    <span class="font-semibold text-gray-900 pr-4">Chính sách đổi trả hàng như thế nào?</span>
                    <i class="fas fa-chevron-down text-primary-600 transform transition-transform"></i>
                </button>
                <div class="faq-answer hidden px-6 pb-4 text-gray-600">
                    <p>Bạn có thể đổi/trả hàng trong vòng <strong>7 ngày</strong> kể từ ngày nhận hàng nếu:</p>
                    <ul class="list-disc pl-5 mt-2 space-y-1">
                        <li>Sản phẩm còn nguyên tem mác, chưa qua sử dụng</li>
                        <li>Sản phẩm lỗi do nhà sản xuất</li>
                        <li>Giao sai size, màu, sản phẩm</li>
                        <li>Có đầy đủ hóa đơn mua hàng</li>
                    </ul>
                    <p class="mt-3"><a href="/Web/pages/chinh-sach-doi-tra.php" class="text-primary-600 hover:underline">Xem chính sách đầy đủ →</a></p>
                </div>
            </div>

            <div class="faq-item bg-white rounded-lg shadow-md overflow-hidden" data-category="all">
                <button class="faq-question w-full text-left px-6 py-4 flex items-center justify-between hover:bg-gray-50 transition">
                    <span class="font-semibold text-gray-900 pr-4">Sản phẩm có được bảo hành không?</span>
                    <i class="fas fa-chevron-down text-primary-600 transform transition-transform"></i>
                </button>
                <div class="faq-answer hidden px-6 pb-4 text-gray-600">
                    <p class="mb-2">Có, tất cả sản phẩm đều được bảo hành theo quy định:</p>
                    <ul class="list-disc pl-5 space-y-1">
                        <li><strong>Giày dép:</strong> 3 tháng (lỗi keo, đường chỉ)</li>
                        <li><strong>Quần áo:</strong> 1 tháng (lỗi đường may, vải)</li>
                        <li><strong>Phụ kiện điện tử:</strong> 6 tháng</li>
                        <li><strong>Túi xách, balo:</strong> 6 tháng (khóa kéo, quai)</li>
                    </ul>
                    <p class="mt-3"><a href="/Web/pages/chinh-sach-bao-hanh.php" class="text-primary-600 hover:underline">Xem chi tiết bảo hành →</a></p>
                </div>
            </div>

            <div class="faq-item bg-white rounded-lg shadow-md overflow-hidden" data-category="all">
                <button class="faq-question w-full text-left px-6 py-4 flex items-center justify-between hover:bg-gray-50 transition">
                    <span class="font-semibold text-gray-900 pr-4">Sản phẩm có chính hãng 100% không?</span>
                    <i class="fas fa-chevron-down text-primary-600 transform transition-transform"></i>
                </button>
                <div class="faq-answer hidden px-6 pb-4 text-gray-600">
                    <p>Cam kết 100% hàng chính hãng! Tất cả sản phẩm tại shop đều:</p>
                    <ul class="list-disc pl-5 mt-2 space-y-1">
                        <li>Nhập khẩu từ nhà phân phối chính thức</li>
                        <li>Có đầy đủ tem nhãn, phiếu bảo hành</li>
                        <li>Hoàn tiền 200% nếu phát hiện hàng giả</li>
                        <li>Kiểm định chất lượng trước khi giao</li>
                    </ul>
                </div>
            </div>

        </div>

        <!-- Still have questions -->
        <div class="mt-12 p-8 bg-gradient-to-r from-primary-600 to-blue-600 rounded-lg text-white text-center">
            <i class="fas fa-headset text-5xl mb-4"></i>
            <h3 class="text-2xl font-bold mb-3">Vẫn còn thắc mắc?</h3>
            <p class="mb-6 text-lg">Đội ngũ hỗ trợ của chúng tôi luôn sẵn sàng giúp đỡ bạn 24/7</p>
            <div class="flex flex-wrap justify-center gap-4">
                <a href="tel:<?php echo SITE_PHONE; ?>" class="inline-flex items-center gap-2 bg-white text-primary-600 px-6 py-3 rounded-lg font-semibold hover:bg-gray-100 transition">
                    <i class="fas fa-phone"></i>
                    <span><?php echo SITE_PHONE; ?></span>
                </a>
                <a href="/Web/pages/contact.php" class="inline-flex items-center gap-2 bg-white text-primary-600 px-6 py-3 rounded-lg font-semibold hover:bg-gray-100 transition">
                    <i class="fas fa-comment"></i>
                    <span>Gửi câu hỏi</span>
                </a>
            </div>
        </div>

    </div>
</div>

<script>
// Toggle FAQ
document.querySelectorAll('.faq-question').forEach(question => {
    question.addEventListener('click', function() {
        const answer = this.nextElementSibling;
        const icon = this.querySelector('i');
        
        // Close all other answers
        document.querySelectorAll('.faq-answer').forEach(item => {
            if (item !== answer && !item.classList.contains('hidden')) {
                item.classList.add('hidden');
                item.previousElementSibling.querySelector('i').classList.remove('rotate-180');
            }
        });
        
        // Toggle current answer
        answer.classList.toggle('hidden');
        icon.classList.toggle('rotate-180');
    });
});

// Filter FAQ
function filterFAQ(category) {
    const items = document.querySelectorAll('.faq-item');
    const buttons = document.querySelectorAll('.faq-category');
    
    // Update button styles
    buttons.forEach(btn => {
        btn.classList.remove('bg-primary-600', 'text-white', 'active');
        btn.classList.add('bg-gray-100', 'text-gray-700');
    });
    event.target.classList.remove('bg-gray-100', 'text-gray-700');
    event.target.classList.add('bg-primary-600', 'text-white', 'active');
    
    // Filter items
    items.forEach(item => {
        if (category === 'all' || item.dataset.category === category) {
            item.style.display = 'block';
        } else {
            item.style.display = 'none';
        }
    });
}

// Search FAQ
document.getElementById('searchFAQ').addEventListener('input', function(e) {
    const searchTerm = e.target.value.toLowerCase();
    const items = document.querySelectorAll('.faq-item');
    
    items.forEach(item => {
        const question = item.querySelector('.faq-question span').textContent.toLowerCase();
        const answer = item.querySelector('.faq-answer').textContent.toLowerCase();
        
        if (question.includes(searchTerm) || answer.includes(searchTerm)) {
            item.style.display = 'block';
        } else {
            item.style.display = 'none';
        }
    });
});
</script>

<?php include __DIR__ . '/../components/footer.php'; ?>
