    </main>
    
    <!-- Footer -->
    <footer class="bg-gray-900 text-gray-300">
        <div class="container mx-auto px-4 py-16">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-10">
                <!-- About -->
                <div>
                    <div class="mb-6">
                        <img src="<?php echo SITE_URL; ?>/img/logofooter.jpg" alt="<?php echo SITE_NAME; ?>" class="h-20 w-auto rounded-lg">
                    </div>
                    <p class="text-sm mb-6 leading-relaxed">
                        Cửa hàng chuyên cung cấp đồ thể thao chính hãng, chất lượng cao với giá tốt nhất thị trường.
                    </p>
                    <div class="flex gap-3">
                        <a href="https://www.facebook.com/share/1CzvTxYMWw/?mibextid=wwXIfr" target="_blank" 
                           class="w-11 h-11 bg-gray-800 rounded-xl flex items-center justify-center hover:bg-blue-600 hover:scale-110 transition-all duration-300">
                            <i class="fab fa-facebook-f text-lg"></i>
                        </a>
                        <a href="https://youtube.com/@nhanhuynh134?si=v38rh7OhLRBhkU3T" target="_blank" 
                           class="w-11 h-11 bg-gray-800 rounded-xl flex items-center justify-center hover:bg-red-600 hover:scale-110 transition-all duration-300">
                            <i class="fab fa-youtube text-lg"></i>
                        </a>
                        <a href="https://www.tiktok.com/@thugicungco6" target="_blank" 
                           class="w-11 h-11 bg-gray-800 rounded-xl flex items-center justify-center hover:bg-black hover:scale-110 transition-all duration-300">
                            <i class="fab fa-tiktok text-lg"></i>
                        </a>
                    </div>
                </div>
                
                <!-- Quick Links -->
                <div>
                    <h3 class="text-white text-lg font-bold mb-6 relative inline-block">
                        Liên kết nhanh
                        <span class="absolute -bottom-2 left-0 w-12 h-1 bg-primary-500 rounded-full"></span>
                    </h3>
                    <ul class="space-y-3 text-sm">
                        <li><a href="<?php echo SITE_URL; ?>/" class="hover:text-primary-400 hover:pl-2 transition-all duration-300 flex items-center gap-2"><i class="fas fa-chevron-right text-xs text-primary-500"></i>Trang chủ</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/pages/products.php" class="hover:text-primary-400 hover:pl-2 transition-all duration-300 flex items-center gap-2"><i class="fas fa-chevron-right text-xs text-primary-500"></i>Sản phẩm</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/pages/news.php" class="hover:text-primary-400 hover:pl-2 transition-all duration-300 flex items-center gap-2"><i class="fas fa-chevron-right text-xs text-primary-500"></i>Tin tức</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/pages/contact.php" class="hover:text-primary-400 hover:pl-2 transition-all duration-300 flex items-center gap-2"><i class="fas fa-chevron-right text-xs text-primary-500"></i>Liên hệ</a></li>
                    </ul>
                </div>
                
                <!-- Customer Service -->
                <div>
                    <h3 class="text-white text-lg font-bold mb-6 relative inline-block">
                        Chăm sóc khách hàng
                        <span class="absolute -bottom-2 left-0 w-12 h-1 bg-primary-500 rounded-full"></span>
                    </h3>
                    <ul class="space-y-3 text-sm">
                        <li><a href="<?php echo SITE_URL; ?>/pages/chinh-sach-doi-tra.php" class="hover:text-primary-400 hover:pl-2 transition-all duration-300 flex items-center gap-2"><i class="fas fa-chevron-right text-xs text-primary-500"></i>Chính sách đổi trả</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/pages/chinh-sach-bao-hanh.php" class="hover:text-primary-400 hover:pl-2 transition-all duration-300 flex items-center gap-2"><i class="fas fa-chevron-right text-xs text-primary-500"></i>Chính sách bảo hành</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/pages/huong-dan-mua-hang.php" class="hover:text-primary-400 hover:pl-2 transition-all duration-300 flex items-center gap-2"><i class="fas fa-chevron-right text-xs text-primary-500"></i>Hướng dẫn mua hàng</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/pages/phuong-thuc-thanh-toan.php" class="hover:text-primary-400 hover:pl-2 transition-all duration-300 flex items-center gap-2"><i class="fas fa-chevron-right text-xs text-primary-500"></i>Phương thức thanh toán</a></li>
                    </ul>
                </div>
                
                <!-- Contact Info -->
                <div>
                    <h3 class="text-white text-lg font-bold mb-6 relative inline-block">
                        Thông tin liên hệ
                        <span class="absolute -bottom-2 left-0 w-12 h-1 bg-primary-500 rounded-full"></span>
                    </h3>
                    <ul class="space-y-4 text-sm">
                        <li class="flex items-start gap-3 group">
                            <div class="w-10 h-10 bg-gray-800 rounded-lg flex items-center justify-center group-hover:bg-primary-600 transition-colors">
                                <i class="fas fa-map-marker-alt text-primary-400 group-hover:text-white"></i>
                            </div>
                            <span class="pt-2">123 Trần Hưng Đạo, Quận 1, TPHCM</span>
                        </li>
                        <li class="flex items-center gap-3 group">
                            <div class="w-10 h-10 bg-gray-800 rounded-lg flex items-center justify-center group-hover:bg-primary-600 transition-colors">
                                <i class="fas fa-phone text-primary-400 group-hover:text-white"></i>
                            </div>
                            <a href="tel:0888899107" class="hover:text-primary-400">0888 899 107</a>
                        </li>
                        <li class="flex items-center gap-3 group">
                            <div class="w-10 h-10 bg-gray-800 rounded-lg flex items-center justify-center group-hover:bg-primary-600 transition-colors">
                                <i class="fas fa-envelope text-primary-400 group-hover:text-white"></i>
                            </div>
                            <a href="mailto:NTHsport@gmail.com" class="hover:text-primary-400">NTHsport@gmail.com</a>
                        </li>
                        <li class="flex items-center gap-3 group">
                            <div class="w-10 h-10 bg-gray-800 rounded-lg flex items-center justify-center group-hover:bg-primary-600 transition-colors">
                                <i class="fas fa-clock text-primary-400 group-hover:text-white"></i>
                            </div>
                            <span>8:00 - 22:00 (Hàng ngày)</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        
        <!-- Bottom Bar -->
        <div class="border-t border-gray-800">
            <div class="container mx-auto px-4 py-6">
                <div class="flex flex-col md:flex-row items-center justify-between gap-4 text-sm">
                    <p>&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. Bản quyền thuộc về công ty.</p>
                    <p>Thiết kế bởi <span class="text-primary-400 font-semibold">NTH Team</span> với <i class="fas fa-heart text-red-500"></i></p>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- Back to Top Button -->
    <button id="backToTop" onclick="window.scrollTo({top: 0, behavior: 'smooth'})"
            class="fixed bottom-6 right-6 w-12 h-12 bg-primary-600 text-white rounded-full shadow-lg hover:bg-primary-700 transition-all duration-300 opacity-0 invisible z-50">
        <i class="fas fa-arrow-up"></i>
    </button>
    
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
    <script src="<?php echo SITE_URL; ?>/assets/js/main.js"></script>
    
    <script>
        // Back to Top Button
        window.addEventListener('scroll', function() {
            const btn = document.getElementById('backToTop');
            if (window.scrollY > 300) {
                btn.classList.remove('opacity-0', 'invisible');
                btn.classList.add('opacity-100', 'visible');
            } else {
                btn.classList.add('opacity-0', 'invisible');
                btn.classList.remove('opacity-100', 'visible');
            }
        });
    </script>
</body>
</html>
