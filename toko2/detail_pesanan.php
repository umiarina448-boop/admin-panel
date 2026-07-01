<?php
session_start();
include "../config/database.php";

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin'){
    header("Location: ../login.php");
    exit;
}

$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if($order_id == 0){
    header("Location: pesanan.php");
    exit;
}

// Ambil data order dengan prepared statement
$stmt_order = $conn->prepare("
    SELECT o.*, u.nama, u.email, u.no_hp, u.alamat
    FROM orders o
    JOIN users u ON o.user_id = u.id
    WHERE o.id = ?
");
$stmt_order->bind_param("i", $order_id);
$stmt_order->execute();
$order = $stmt_order->get_result()->fetch_assoc();

if(!$order){
    header("Location: pesanan.php");
    exit;
}

// Ambil detail item dengan prepared statement
$stmt_items = $conn->prepare("
    SELECT od.*, p.nama_produk, p.gambar, p.harga as product_price
    FROM order_details od
    JOIN products p ON od.product_id = p.id
    WHERE od.order_id = ?
");
$stmt_items->bind_param("i", $order_id);
$stmt_items->execute();
$items = $stmt_items->get_result();

// Hitung subtotal
$subtotal = 0;
while($item = $items->fetch_assoc()){
    $subtotal += $item['harga'] * $item['qty'];
}
$items->data_seek(0); // Reset pointer

$status_badge = [
    'pending' => ['label' => 'Menunggu', 'color' => '#ff9800', 'icon' => 'fa-clock'],
    'proses' => ['label' => 'Diproses', 'color' => '#2196f3', 'icon' => 'fa-spinner'],
    'dikirim' => ['label' => 'Dikirim', 'color' => '#4caf50', 'icon' => 'fa-truck'],
    'selesai' => ['label' => 'Selesai', 'color' => '#2ecc71', 'icon' => 'fa-circle-check'],
    'batal' => ['label' => 'Dibatalkan', 'color' => '#f44336', 'icon' => 'fa-ban']
];
$status_info = $status_badge[$order['status']] ?? $status_badge['pending'];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Pesanan #<?php echo str_pad($order_id, 6, '0', STR_PAD_LEFT); ?> - Admin AIC Fashion Metro</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: #f0f2f5;
            padding: 20px;
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
        }

        /* BACK BUTTON */
        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #EE6C4D;
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 40px;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 20px;
            transition: 0.2s;
        }

        .back-btn:hover {
            background: #d95a3c;
            transform: translateX(-3px);
        }

        /* CARD */
        .card {
            background: white;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            margin-bottom: 25px;
        }

        .card-header {
            background: #f8f9fa;
            padding: 18px 25px;
            border-bottom: 1px solid #eee;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card-header i {
            font-size: 20px;
            color: #EE6C4D;
        }

        .card-header h3 {
            font-size: 18px;
            font-weight: 600;
            color: #333;
        }

        .card-body {
            padding: 25px;
        }

        /* ORDER INFO GRID */
        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }

        .info-item {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .info-label {
            font-size: 12px;
            color: #888;
            font-weight: 500;
        }

        .info-value {
            font-size: 15px;
            font-weight: 600;
            color: #333;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 16px;
            border-radius: 30px;
            font-size: 13px;
            font-weight: 600;
            width: fit-content;
        }

        /* TABLE */
        .table-responsive {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 15px 12px;
            text-align: left;
            border-bottom: 1px solid #f0f0f0;
        }

        th {
            background: #fafafa;
            color: #555;
            font-weight: 600;
            font-size: 13px;
        }

        td {
            font-size: 13px;
            color: #333;
        }

        .product-img {
            width: 55px;
            height: 55px;
            object-fit: cover;
            border-radius: 10px;
            background: #f5f5f5;
        }

        /* SUMMARY */
        .summary {
            background: #f8f9fa;
            border-radius: 16px;
            padding: 20px;
            margin-top: 10px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
        }

        .summary-row.total {
            border-top: 1px solid #e0e0e0;
            margin-top: 10px;
            padding-top: 15px;
            font-size: 18px;
            font-weight: 700;
        }

        .summary-row.total .value {
            color: #EE6C4D;
            font-size: 22px;
        }

        /* ACTION BUTTONS */
        .action-buttons {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 20px;
        }

        .btn-action {
            padding: 10px 24px;
            border-radius: 40px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: 0.2s;
            border: none;
            cursor: pointer;
        }

        .btn-proses {
            background: #2196f3;
            color: white;
        }

        .btn-kirim {
            background: #4caf50;
            color: white;
        }

        .btn-selesai {
            background: #2ecc71;
            color: white;
        }

        .btn-batal {
            background: #f44336;
            color: white;
        }

        .btn-action:hover {
            transform: translateY(-2px);
            filter: brightness(0.95);
        }

        hr {
            margin: 20px 0;
            border: none;
            border-top: 1px solid #eee;
        }

        @media (max-width: 700px) {
            .info-grid {
                grid-template-columns: 1fr;
                gap: 12px;
            }
            .card-body {
                padding: 20px;
            }
            .action-buttons {
                flex-direction: column;
            }
            .btn-action {
                text-align: center;
                justify-content: center;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <a href="pesanan.php" class="back-btn">
        <i class="fa-solid fa-arrow-left"></i> Kembali ke Pesanan
    </a>

    <!-- CARD: INFORMASI PESANAN -->
    <div class="card">
        <div class="card-header">
            <i class="fa-solid fa-receipt"></i>
            <h3>Informasi Pesanan</h3>
        </div>
        <div class="card-body">
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">Nomor Pesanan</div>
                    <div class="info-value">#<?php echo str_pad($order_id, 6, '0', STR_PAD_LEFT); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Status</div>
                    <div class="info-value">
                        <span class="status-badge" style="background: <?php echo $status_info['color']; ?>20; color: <?php echo $status_info['color']; ?>">
                            <i class="fa-solid <?php echo $status_info['icon']; ?>"></i>
                            <?php echo $status_info['label']; ?>
                        </span>
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-label">Tanggal Pesanan</div>
                    <div class="info-value">
                        <?php echo date('d F Y, H:i', strtotime($order['created_at'] ?? 'now')); ?>
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-label">Metode Pembayaran</div>
                    <div class="info-value"><?php echo htmlspecialchars($order['metode_pembayaran'] ?? 'COD'); ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- CARD: INFORMASI CUSTOMER -->
    <div class="card">
        <div class="card-header">
            <i class="fa-regular fa-user"></i>
            <h3>Informasi Customer</h3>
        </div>
        <div class="card-body">
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">Nama Lengkap</div>
                    <div class="info-value"><?php echo htmlspecialchars($order['nama']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Email</div>
                    <div class="info-value"><?php echo htmlspecialchars($order['email']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Nomor HP</div>
                    <div class="info-value"><?php echo htmlspecialchars($order['no_hp'] ?? '-'); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Alamat Pengiriman</div>
                    <div class="info-value"><?php echo nl2br(htmlspecialchars($order['alamat'] ?? '-')); ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- CARD: PRODUK YANG DIPESAN -->
    <div class="card">
        <div class="card-header">
            <i class="fa-solid fa-bag-shopping"></i>
            <h3>Produk yang Dipesan</h3>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Produk</th>
                            <th>Size</th>
                            <th>Qty</th>
                            <th>Harga</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $total_items = 0;
                        while($item = $items->fetch_assoc()): 
                            $subtotal_item = $item['harga'] * $item['qty'];
                            $total_items += $item['qty'];
                        ?>
                            <tr>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 12px;">
                                        <img class="product-img" src="../uploads/<?php echo htmlspecialchars($item['gambar']); ?>" 
                                             alt="<?php echo htmlspecialchars($item['nama_produk']); ?>"
                                             onerror="this.src='https://placehold.co/200x200?text=No+Image'">
                                        <strong><?php echo htmlspecialchars($item['nama_produk']); ?></strong>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($item['size'] ?? 'Free'); ?></td>
                                <td><?php echo $item['qty']; ?></td>
                                <td>Rp <?php echo number_format($item['harga'], 0, ',', '.'); ?></td>
                                <td>Rp <?php echo number_format($subtotal_item, 0, ',', '.'); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <!-- RINGKASAN -->
            <div class="summary">
                <div class="summary-row">
                    <span>Subtotal (<?php echo $total_items; ?> item)</span>
                    <span>Rp <?php echo number_format($subtotal, 0, ',', '.'); ?></span>
                </div>
                <div class="summary-row">
                    <span>Ongkos Kirim</span>
                    <span>Rp <?php echo number_format($order['ongkir'] ?? 0, 0, ',', '.'); ?></span>
                </div>
                <div class="summary-row total">
                    <span>Total Pembayaran</span>
                    <span class="value">Rp <?php echo number_format($order['total_harga'], 0, ',', '.'); ?></span>
                </div>
            </div>

            <!-- ACTION BUTTONS (Update Status) -->
            <?php if($order['status'] != 'selesai' && $order['status'] != 'batal'): ?>
                <div class="action-buttons">
                    <?php if($order['status'] == 'pending'): ?>
                        <a href="?id=<?php echo $order_id; ?>&status=proses" class="btn-action btn-proses">
                            <i class="fa-solid fa-play"></i> Proses Pesanan
                        </a>
                    <?php endif; ?>
                    
                    <?php if($order['status'] == 'proses'): ?>
                        <a href="?id=<?php echo $order_id; ?>&status=dikirim" class="btn-action btn-kirim">
                            <i class="fa-solid fa-truck"></i> Kirim Pesanan
                        </a>
                    <?php endif; ?>
                    
                    <?php if($order['status'] == 'dikirim'): ?>
                        <a href="?id=<?php echo $order_id; ?>&status=selesai" class="btn-action btn-selesai">
                            <i class="fa-regular fa-circle-check"></i> Selesai
                        </a>
                    <?php endif; ?>
                    
                    <?php if($order['status'] == 'pending' || $order['status'] == 'proses'): ?>
                        <a href="?id=<?php echo $order_id; ?>&status=batal" class="btn-action btn-batal" 
                           onclick="return confirm('Yakin ingin membatalkan pesanan ini?')">
                            <i class="fa-solid fa-ban"></i> Batalkan Pesanan
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

</body>
</html>