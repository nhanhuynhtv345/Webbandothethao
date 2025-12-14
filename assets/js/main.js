// Main JavaScript for Sports Shop

// Add to cart function
function addToCart(productId, variantId = null, quantity = 1) {
    fetch(`${window.location.origin}/Web/api/cart.php`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'add',
            product_id: productId,
            variant_id: variantId,
            quantity: quantity
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('success', 'Đã thêm sản phẩm vào giỏ hàng!');
            updateCartCount();
        } else {
            showNotification('error', data.message || 'Có lỗi xảy ra!');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('error', 'Có lỗi xảy ra!');
    });
}

// Add to wishlist function
function addToWishlist(productId) {
    fetch(`${window.location.origin}/Web/api/wishlist.php`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'add',
            product_id: productId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('success', 'Đã thêm vào danh sách yêu thích!');
            updateWishlistCount();
        } else {
            showNotification('error', data.message || 'Vui lòng đăng nhập!');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('error', 'Có lỗi xảy ra!');
    });
}

// Update cart count
function updateCartCount() {
    fetch(`${window.location.origin}/Web/api/cart.php?action=count`)
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.querySelectorAll('.cart-count').forEach(el => {
                el.textContent = data.count;
            });
        }
    });
}

// Update wishlist count
function updateWishlistCount() {
    fetch(`${window.location.origin}/Web/api/wishlist.php?action=count`)
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.querySelectorAll('.wishlist-count').forEach(el => {
                el.textContent = data.count;
            });
        }
    });
}

// Quick view modal
function quickView(productId) {
    // TODO: Implement quick view modal
    console.log('Quick view:', productId);
    showNotification('info', 'Chức năng đang phát triển');
}

// Show notification
function showNotification(type, message) {
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 z-50 px-6 py-4 rounded-lg shadow-lg text-white transform translate-x-full transition-transform duration-300`;
    
    switch(type) {
        case 'success':
            notification.classList.add('bg-green-500');
            break;
        case 'error':
            notification.classList.add('bg-red-500');
            break;
        case 'info':
            notification.classList.add('bg-blue-500');
            break;
        case 'warning':
            notification.classList.add('bg-yellow-500');
            break;
    }
    
    notification.innerHTML = `
        <div class="flex items-center gap-3">
            <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'times-circle' : 'info-circle'} text-xl"></i>
            <span>${message}</span>
            <button onclick="this.parentElement.parentElement.remove()" class="ml-4">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.classList.remove('translate-x-full');
    }, 100);
    
    setTimeout(() => {
        notification.classList.add('translate-x-full');
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// Format currency
function formatCurrency(amount) {
    return new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND'
    }).format(amount);
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    // Update cart count on page load
    updateCartCount();
    
    // Update wishlist count on page load
    updateWishlistCount();
    
    // Handle search form
    const searchForm = document.querySelector('form[action*="products.php"]');
    if (searchForm) {
        searchForm.addEventListener('submit', function(e) {
            const searchInput = this.querySelector('input[name="search"]');
            if (!searchInput.value.trim()) {
                e.preventDefault();
                showNotification('warning', 'Vui lòng nhập từ khóa tìm kiếm');
            }
        });
    }
});

// Smooth scroll to top
function scrollToTop() {
    window.scrollTo({
        top: 0,
        behavior: 'smooth'
    });
}

// Show scroll to top button
window.addEventListener('scroll', function() {
    const scrollBtn = document.getElementById('scrollToTop');
    if (scrollBtn) {
        if (window.pageYOffset > 300) {
            scrollBtn.classList.remove('hidden');
        } else {
            scrollBtn.classList.add('hidden');
        }
    }
});
