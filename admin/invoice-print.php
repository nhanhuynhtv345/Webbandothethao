<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isAdmin()) {
    redirect(SITE_URL . '/admin/login.php');
}

$db = getDB();
$id = intval($_GET['id'] ?? 0);

if (!$id) {
    redirect(SITE_URL . '/admin/invoices.php');
}

// Get invoice
$stmt = $db->prepare("
    SELECT hd.*, dh.ma_don_hang, dh.ho_ten, dh.so_dien_thoai, dh.email, dh.dia_chi,
           dh.phuong_xa, dh.quan_huyen, dh.tinh_thanh, dh.phuong_thuc_thanh_toan
    FROM hoa_don hd
    LEFT JOIN don_hang dh ON hd.don_hang_id = dh.id
    WHERE hd.id = ?
");
$stmt->execute([$id]);
$invoice = $stmt->fetch();

if (!$invoice) {
    redirect(SITE_URL . '/admin/invoices.php');
}

// Get invoice items
$itemsStmt = $db->prepare("SELECT * FROM chi_tiet_hoa_don WHERE hoa_don_id = ?");
$itemsStmt->execute([$id]);
$items = $itemsStmt->fetchAll();

$statusLabels = [
    'unpaid' => 'Chưa thanh toán',
    'paid' => 'Đã thanh toán',
    'cancelled' => 'Đã hủy',
];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hóa đơn <?php echo $invoice['ma_hoa_don']; ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Arial, sans-serif; font-size: 14px; line-height: 1.5; color: #333; background: #f5f5f5; }
        .invoice-container { max-width: 800px; margin: 20px auto; background: white; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .invoice-header { background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%); color: white; padding: 30px; }
        .invoice-header h1 { font-size: 28px; margin-bottom: 5px; }
        .invoice-header p { opacity: 0.9; }
        .invoice-body { padding: 30px; }
        .invoice-info { display: flex; justify-content: space-between; margin-bottom: 30px; }
        .invoice-info-box { width: 48%; }
        .invoice-info-box h3 { font-size: 12px; text-transform: uppercase; color: #666; margin-bottom: 10px; letter-spacing: 1px; }
        .invoice-info-box p { margin-bottom: 5px; }
        .invoice-info-box .name { font-size: 18px; font-weight: bold; color: #0284c7; }
        .invoice-meta { background: #f8fafc; padding: 20px; border-radius: 8px; margin-bottom: 30px; display: flex; justify-content: space-between; }
        .invoice-meta-item { text-align: center; }
        .invoice-meta-item label { display: block; font-size: 11px; text-transform: uppercase; color: #666; margin-bottom: 5px; }
        .invoice-meta-item span { font-size: 16px; font-weight: bold; color: #333; }
        .invoice-meta-item .status { padding: 5px 15px; border-radius: 20px; font-size: 12px; }
        .status-paid { background: #dcfce7; color: #166534; }
        .status-unpaid { background: #fef3c7; color: #92400e; }
        .status-cancelled { background: #fee2e2; color: #991b1b; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        th { background: #f1f5f9; padding: 12px 15px; text-align: left; font-size: 12px; text-transform: uppercase; color: #64748b; border-bottom: 2px solid #e2e8f0; }
        td { padding: 15px; border-bottom: 1px solid #e2e8f0; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .summary { max-width: 300px; margin-left: auto; }
        .summary-row { display: flex; justify-content: space-between; padding: 8px 0; }
        .summary-row.total { border-top: 2px solid #0284c7; margin-top: 10px; padding-top: 15px; font-size: 18px; font-weight: bold; color: #0284c7; }
        .invoice-footer { background: #f8fafc; padding: 20px 30px; text-align: center; border-top: 1px solid #e2e8f0; }
        .invoice-footer p { color: #64748b; font-size: 12px; }
        .print-btn { position: fixed; bottom: 20px; right: 20px; background: #0284c7; color: white; border: none; padding: 15px 30px; border-radius: 8px; cursor: pointer; font-size: 16px; box-shadow: 0 4px 15px rgba(2,132,199,0.3); }
        .print-btn:hover { background: #0369a1; }
        @media print {
            body { background: white; }
            .invoice-container { box-shadow: none; margin: 0; }
            .print-btn { display: none; }
        }
    </style>
</head>
<body>
    <div class="invoice-container">
        <div class="invoice-header">
            <h1><?php echo SITE_NAME; ?></h1>
            <p>123 Trần Hưng Đạo, Quận 1, TP.HCM | <?php echo SITE_PHONE; ?> | NTHsport@gmail.com</p>
        </div>
        
        <div class="invoice-body">
            <div class="invoice-meta">
                <div class="invoice-meta-item">
                    <label>Mã hóa đơn</label>
                    <span><?php echo $invoice['ma_hoa_don']; ?></span>
                </div>
                <div class="invoice-meta-item">
                    <label>Ngày tạo</label>
                    <span><?php echo date('d/m/Y', strtotime($invoice['created_at'])); ?></span>
                </div>
                <div class="invoice-meta-item">
                    <label>Mã đơn hàng</label>
                    <span><?php echo $invoice['ma_don_hang']; ?></span>
                </div>
                <div class="invoice-meta-item">
                    <label>Trạng thái</label>
                    <span class="status status-<?php echo $invoice['status']; ?>">
                        <?php echo $statusLabels[$invoice['status']] ?? $invoice['status']; ?>
                    </span>
                </div>
            </div>
            
            <div class="invoice-info">
                <div class="invoice-info-box">
                    <h3>Từ</h3>
                    <p class="name"><?php echo SITE_NAME; ?></p>
                    <p>123 Trần Hưng Đạo, Quận 1</p>
                    <p>TP. Hồ Chí Minh</p>
                    <p>SĐT: <?php echo SITE_PHONE; ?></p>
                </div>
                <div class="invoice-info-box">
                    <h3>Đến</h3>
                    <p class="name"><?php echo htmlspecialchars($invoice['ho_ten']); ?></p>
                    <p><?php echo htmlspecialchars($invoice['dia_chi']); ?></p>
                    <p><?php echo implode(', ', array_filter([$invoice['phuong_xa'], $invoice['quan_huyen'], $invoice['tinh_thanh']])); ?></p>
                    <p>SĐT: <?php echo $invoice['so_dien_thoai']; ?></p>
                    <?php if ($invoice['email']): ?>
                    <p>Email: <?php echo htmlspecialchars($invoice['email']); ?></p>
                    <?php endif; ?>
                </div>
            </div>
            
            <table>
                <thead>
                    <tr>
                        <th style="width: 50%">Sản phẩm</th>
                        <th class="text-center">Số lượng</th>
                        <th class="text-right">Đơn giá</th>
                        <th class="text-right">Thành tiền</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                    <tr>
                        <td>
                            <strong><?php echo htmlspecialchars($item['ten_san_pham']); ?></strong>
                            <?php if (!empty($item['thong_tin_bien_the'])): ?>
                            <br><small style="color: #666;"><?php echo htmlspecialchars($item['thong_tin_bien_the']); ?></small>
                            <?php endif; ?>
                        </td>
                        <td class="text-center"><?php echo $item['so_luong']; ?></td>
                        <td class="text-right"><?php echo number_format($item['don_gia']); ?>đ</td>
                        <td class="text-right"><strong><?php echo number_format($item['thanh_tien']); ?>đ</strong></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <div class="summary">
                <div class="summary-row">
                    <span>Tạm tính:</span>
                    <span><?php echo number_format($invoice['tong_tien_san_pham']); ?>đ</span>
                </div>
                <div class="summary-row">
                    <span>Phí vận chuyển:</span>
                    <span><?php echo number_format($invoice['phi_van_chuyen']); ?>đ</span>
                </div>
                <?php if ($invoice['giam_gia'] > 0): ?>
                <div class="summary-row" style="color: #16a34a;">
                    <span>Giảm giá:</span>
                    <span>-<?php echo number_format($invoice['giam_gia']); ?>đ</span>
                </div>
                <?php endif; ?>
                <div class="summary-row total">
                    <span>Tổng cộng:</span>
                    <span><?php echo number_format($invoice['tong_thanh_toan']); ?>đ</span>
                </div>
            </div>
        </div>
        
        <div class="invoice-footer">
            <p><strong>Cảm ơn quý khách đã mua hàng tại <?php echo SITE_NAME; ?>!</strong></p>
            <p>Mọi thắc mắc xin liên hệ: <?php echo SITE_PHONE; ?> | NTHsport@gmail.com</p>
        </div>
    </div>
    
    <button class="print-btn" onclick="window.print()">
        🖨️ In hóa đơn
    </button>
</body>
</html>
