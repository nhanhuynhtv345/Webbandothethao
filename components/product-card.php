<div class="product-card bg-white rounded-2xl shadow-sm overflow-hidden group border border-gray-100 hover:border-primary-200">
    <div class="relative overflow-hidden">
        <a href="<?php echo SITE_URL; ?>/pages/product-detail.php?id=<?php echo $product['id']; ?>">
            <?php if ($product['anh_dai_dien']): ?>
                <img src="<?php echo UPLOAD_URL . '/' . $product['anh_dai_dien']; ?>" 
                     alt="<?php echo htmlspecialchars($product['ten_san_pham']); ?>"
                     class="product-image w-full h-72 object-cover">
            <?php else: ?>
                <div class="w-full h-72 bg-gradient-to-br from-gray-100 to-gray-200 flex items-center justify-center">
                    <i class="fas fa-image text-gray-400 text-5xl"></i>
                </div>
            <?php endif; ?>
        </a>
        
        <!-- Badges -->
        <div class="absolute top-3 left-3 flex flex-col gap-2">
            <?php if ($product['san_pham_moi']): ?>
                <span class="bg-gradient-to-r from-green-500 to-green-600 text-white px-3 py-1 rounded-full text-xs font-bold shadow-lg">
                    <i class="fas fa-sparkles mr-1"></i>Mới
                </span>
            <?php endif; ?>
            <?php if ($product['gia_goc'] && $product['gia_goc'] > $product['gia_ban']): ?>
                <span class="bg-gradient-to-r from-red-500 to-red-600 text-white px-3 py-1 rounded-full text-xs font-bold shadow-lg">
                    -<?php echo calculateDiscount($product['gia_goc'], $product['gia_ban']); ?>%
                </span>
            <?php endif; ?>
        </div>
        
        <!-- Quick Actions -->
        <div class="absolute top-3 right-3 flex flex-col gap-2 opacity-0 group-hover:opacity-100 transform translate-x-4 group-hover:translate-x-0 transition-all duration-300">
            <button onclick="addToWishlist(<?php echo $product['id']; ?>)" 
                    class="w-10 h-10 bg-white/90 backdrop-blur rounded-full flex items-center justify-center shadow-lg hover:bg-red-500 hover:text-white transition-all duration-200"
                    title="Yêu thích">
                <i class="fas fa-heart"></i>
            </button>
            <button onclick="quickView(<?php echo $product['id']; ?>)" 
                    class="w-10 h-10 bg-white/90 backdrop-blur rounded-full flex items-center justify-center shadow-lg hover:bg-primary-600 hover:text-white transition-all duration-200"
                    title="Xem nhanh">
                <i class="fas fa-eye"></i>
            </button>
        </div>
        
        <!-- Add to Cart Overlay -->
        <div class="absolute bottom-0 left-0 right-0 p-4 bg-gradient-to-t from-black/60 to-transparent opacity-0 group-hover:opacity-100 transition-all duration-300 transform translate-y-4 group-hover:translate-y-0">
            <button onclick="addToCart(<?php echo $product['id']; ?>)" 
                    class="w-full bg-white text-gray-900 px-4 py-3 rounded-xl font-bold hover:bg-primary-600 hover:text-white transition-all duration-200 flex items-center justify-center gap-2 shadow-lg">
                <i class="fas fa-shopping-cart"></i>
                Thêm vào giỏ
            </button>
        </div>
    </div>
    
    <div class="p-5">
        <!-- Brand -->
        <?php if (isset($product['ten_thuong_hieu'])): ?>
            <p class="text-xs font-semibold text-primary-600 uppercase tracking-wider mb-2"><?php echo htmlspecialchars($product['ten_thuong_hieu']); ?></p>
        <?php endif; ?>
        
        <!-- Product Name -->
        <h3 class="font-bold text-gray-900 mb-2 line-clamp-2 min-h-[48px]">
            <a href="<?php echo SITE_URL; ?>/pages/product-detail.php?id=<?php echo $product['id']; ?>" 
               class="hover:text-primary-600 transition-colors">
                <?php echo htmlspecialchars($product['ten_san_pham']); ?>
            </a>
        </h3>
        
        <!-- Rating -->
        <div class="flex items-center gap-2 mb-3">
            <div class="star-rating flex">
                <?php 
                $rating = $product['diem_trung_binh'] ?? 0;
                for ($i = 1; $i <= 5; $i++): 
                    if ($i <= $rating): ?>
                        <i class="fas fa-star text-sm"></i>
                    <?php elseif ($i - 0.5 <= $rating): ?>
                        <i class="fas fa-star-half-alt text-sm"></i>
                    <?php else: ?>
                        <i class="far fa-star text-sm text-gray-300"></i>
                    <?php endif;
                endfor; ?>
            </div>
            <span class="text-xs text-gray-500">(<?php echo $product['so_luot_danh_gia'] ?? 0; ?> đánh giá)</span>
        </div>
        
        <!-- Price -->
        <div class="flex items-end gap-2">
            <span class="text-2xl font-extrabold text-primary-600"><?php echo formatCurrency($product['gia_ban']); ?></span>
            <?php if ($product['gia_goc'] && $product['gia_goc'] > $product['gia_ban']): ?>
                <span class="text-sm text-gray-400 line-through mb-1"><?php echo formatCurrency($product['gia_goc']); ?></span>
            <?php endif; ?>
        </div>
    </div>
</div>
