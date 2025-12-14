# Sports Shop E-commerce Website

Website bán đồ thể thao được xây dựng bằng PHP và Tailwind CSS.

## Thông tin dự án

- **Địa chỉ**: 123 Trần Hưng Đạo, Quận 1, TPHCM
- **Điện thoại**: 0888 899 107
- **Email**: NTHsport@gmail.com
- **Facebook**: https://www.facebook.com/share/1CzvTxYMWw/
- **YouTube**: https://youtube.com/@nhanhuynh134
- **TikTok**: https://www.tiktok.com/@thugicungco6

## Yêu cầu hệ thống

- PHP >= 7.4
- MySQL >= 5.7
- Apache/Nginx (XAMPP hoặc WAMP)

## Cài đặt

### 1. Cấu hình Database

```sql
-- Import file database SQL đã được cung cấp
mysql -u root -p < database.sql
```

### 2. Cấu hình Environment

Sao chép file `.env.example` thành `.env` và cập nhật thông tin:

```bash
cp .env.example .env
```

Chỉnh sửa file `.env`:
```
DB_HOST=localhost
DB_PORT=3306
DB_NAME=QL_CuaHangTheThao
DB_USER=root
DB_PASS=your_password
```

### 3. Chạy Website

Đảm bảo document root trỏ đến thư mục `Web/` hoặc truy cập qua:
```
http://localhost/Web/
```

**Lưu ý:** Website sử dụng Tailwind CSS qua CDN, không cần build hay compile CSS.

## Cấu trúc thư mục

```
Web/
├── api/                           # API endpoints
│   ├── cart.php                  # Giỏ hàng API (add, update, remove, count)
│   ├── checkout.php              # API xử lý đặt hàng
│   ├── wishlist.php              # Yêu thích API
│   └── google-login.php          # Google OAuth API
├── assets/                        # Tài nguyên tĩnh
│   ├── css/
│   │   └── input.css             # Custom CSS
│   ├── js/
│   │   └── main.js               # JavaScript chính
│   └── uploads/                  # Thư mục upload
│       ├── avatars/              # Avatar người dùng
│       └── contact/              # Ảnh liên hệ
├── components/                    # Component tái sử dụng
│   ├── header.php                # Header (menu, search, cart)
│   ├── footer.php                # Footer (links, social)
│   └── product-card.php          # Card sản phẩm
├── config/                        # Cấu hình
│   ├── config.php                # Cấu hình chung (SITE_PHONE, SITE_ADDRESS, etc.)
│   ├── database.php              # Kết nối database
│   └── env.php                   # Load environment variables
├── includes/                      # Helper functions
│   └── functions.php             # Các hàm tiện ích
├── pages/                         # Các trang
│   ├── products.php              # Danh sách sản phẩm (filter, search)
│   ├── product-detail.php        # Chi tiết sản phẩm
│   ├── cart.php                  # Giỏ hàng
│   ├── checkout.php              # Thanh toán (63 tỉnh thành)
│   ├── login.php                 # Đăng nhập
│   ├── register.php              # Đăng ký
│   ├── logout.php                # Đăng xuất
│   ├── account.php               # Tài khoản
│   ├── wishlist.php              # Danh sách yêu thích
│   ├── contact.php               # Liên hệ (form + Google Maps)
│   ├── news.php                  # Tin tức
│   ├── news-detail.php           # Chi tiết tin tức
│   ├── promotions.php            # Khuyến mãi
│   ├── cau-hoi-thuong-gap.php   # FAQ
│   ├── chinh-sach-doi-tra.php   # Chính sách đổi trả
│   ├── chinh-sach-bao-hanh.php  # Chính sách bảo hành
│   ├── huong-dan-mua-hang.php   # Hướng dẫn mua hàng
│   └── phuong-thuc-thanh-toan.php # Phương thức thanh toán
├── img/                           # Hình ảnh tĩnh
├── admin/                         # Trang quản trị (Admin Panel)
│   ├── index.php                  # Dashboard
│   ├── products.php               # Quản lý sản phẩm
│   ├── orders.php                 # Quản lý đơn hàng
│   ├── order-detail.php           # Chi tiết đơn hàng
│   ├── customers.php              # Quản lý khách hàng
│   ├── customer-detail.php        # Chi tiết khách hàng
│   ├── categories.php             # Quản lý danh mục
│   ├── brands.php                 # Quản lý thương hiệu
│   ├── news.php                   # Quản lý tin tức
│   ├── sidebar.php                # Component sidebar
│   ├── setup-admin.sql            # SQL tạo tài khoản admin
│   └── README.md                  # Hướng dẫn admin panel
├── google-callback.php            # Google OAuth callback
├── google-setup.php               # Google OAuth setup
├── GOOGLE_OAUTH_SETUP.md         # Hướng dẫn Google OAuth
├── .env.example                   # Mẫu environment config
└── index.php                      # Trang chủ
```

## Tính năng

### Người dùng
- [x] Trang chủ với banner thương hiệu (Nike, Adidas, Puma, Under Armour, New Balance, Reebok, Converse)
- [x] Danh sách sản phẩm với filter (danh mục, thương hiệu, giá), tìm kiếm, phân trang
- [x] Chi tiết sản phẩm với gallery, variants
- [x] Giỏ hàng (session + database)
- [x] Đăng ký / Đăng nhập (thường + Google OAuth)
- [x] Thanh toán với form đầy đủ (63 tỉnh thành, COD/Chuyển khoản)
- [x] Quản lý tài khoản và đơn hàng
- [x] Yêu thích sản phẩm
- [x] Trang liên hệ (form + Google Maps)
- [x] Tin tức
- [x] Trang khuyến mãi
- [x] 5 trang chính sách (FAQ, đổi trả, bảo hành, hướng dẫn, thanh toán)

### Quản trị (Admin)
- [x] Dashboard thống kê (Doanh thu, Đơn hàng, Sản phẩm, Khách hàng)
- [x] Quản lý sản phẩm (Danh sách, Lọc, Xóa)
- [x] Quản lý đơn hàng (Danh sách, Chi tiết, Cập nhật trạng thái)
- [x] Quản lý khách hàng (Danh sách, Chi tiết, Thống kê)
- [x] Quản lý danh mục (Thêm/Sửa/Xóa)
- [x] Quản lý thương hiệu (Thêm/Sửa/Xóa)
- [x] Quản lý tin tức (Danh sách, Xóa)
- [ ] Form thêm/sửa sản phẩm (product-edit.php)
- [ ] Form thêm/sửa tin tức (news-edit.php)
- [ ] Báo cáo doanh thu chi tiết

**Truy cập Admin Panel:**
- URL: `http://localhost/Web/admin/`
- Tài khoản mặc định: `admin@nthsport.com` / `admin123`
- Xem thêm: [admin/README.md](admin/README.md)

## API Endpoints

### Cart API (`/api/cart.php`)

**Thêm vào giỏ:**
```javascript
POST /api/cart.php
{
    "action": "add",
    "product_id": 1,
    "variant_id": null,
    "quantity": 1
}
```

**Cập nhật số lượng:**
```javascript
POST /api/cart.php
{
    "action": "update",
    "product_id": 1,
    "quantity": 2
}
```

**Xóa khỏi giỏ:**
```javascript
POST /api/cart.php
{
    "action": "remove",
    "product_id": 1
}
```

**Lấy số lượng:**
```
GET /api/cart.php?action=count
```

### Checkout API (`/api/checkout.php`)

**Đặt hàng:**
```javascript
POST /api/checkout.php
{
    "ho_ten": "Nguyễn Văn A",
    "dien_thoai": "0888899107",
    "email": "email@example.com",
    "dia_chi": "123 Đường ABC",
    "tinh_thanh": "TP. Hồ Chí Minh",
    "quan_huyen": "Quận 1",
    "phuong_xa": "Phường Bến Nghé",
    "phuong_thuc_thanh_toan": "COD",
    "ghi_chu": "Giao giờ hành chính"
}
```

## Database Tables

- `nguoi_dung` - Người dùng (hỗ trợ Google OAuth)
- `san_pham` - Sản phẩm
- `danh_muc` - Danh mục
- `thuong_hieu` - Thương hiệu
- `gio_hang` - Giỏ hàng
- `don_hang` - Đơn hàng
- `chi_tiet_don_hang` - Chi tiết đơn hàng
- `tin_tuc` - Tin tức
- `hinh_anh_san_pham` - Hình ảnh sản phẩm
- `bien_the_san_pham` - Biến thể (size, màu)

## Công nghệ

- **Backend**: PHP 7.4+
- **Database**: MySQL / MariaDB
- **Frontend**: HTML5, Tailwind CSS (CDN), JavaScript (Vanilla)
- **Icons**: Font Awesome
- **Authentication**: Session + Google OAuth 2.0

## Bảo mật

- Password hash với `password_hash()`
- Prepared Statements chống SQL Injection
- XSS protection với `htmlspecialchars()`
- Session-based authentication

## License

MIT License

## Hỗ trợ

Để được hỗ trợ, vui lòng liên hệ: support@example.com
