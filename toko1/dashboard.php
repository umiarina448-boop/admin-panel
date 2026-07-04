<?php
session_start();
include "../config/database.php";

// Cek login dan role admin
if(!isset($_SESSION['user_id'])){
    header("Location: ../login.php");
    exit;
}

if($_SESSION['role'] != 'admin'){
    header("Location: ../customer/dashboard.php");
    exit;
}

$toko_id = 1;

// ==============================
// FILTER TANGGAL
// ==============================
$tanggal_mulai = isset($_GET['tanggal_mulai']) ? trim($_GET['tanggal_mulai']) : '';
$tanggal_selesai = isset($_GET['tanggal_selesai']) ? trim($_GET['tanggal_selesai']) : '';

$filter_tanggal_sql = "";
if($tanggal_mulai != '' && $tanggal_selesai != ''){
    $filter_tanggal_sql = " AND DATE(created_at) BETWEEN ? AND ? ";
}

// STATISTIK PRODUK (tidak ada filter tanggal, produk tidak punya tanggal transaksi)
$stmt_produk = $conn->prepare("SELECT COUNT(*) as total FROM products WHERE toko_id = ?");
$stmt_produk->bind_param("i", $toko_id);
$stmt_produk->execute();
$total_produk = $stmt_produk->get_result()->fetch_assoc()['total'];

// Total customer (unik) — ikut filter tanggal berdasarkan order
$sql_customer = "
    SELECT COUNT(DISTINCT u.id) as total 
    FROM users u
    JOIN orders o ON o.user_id = u.id
    WHERE u.role = 'customer' AND o.toko_id = ?" . $filter_tanggal_sql;
$stmt_customer = $conn->prepare($sql_customer);
if($filter_tanggal_sql != ""){
    $stmt_customer->bind_param("iss", $toko_id, $tanggal_mulai, $tanggal_selesai);
} else {
    $stmt_customer->bind_param("i", $toko_id);
}
$stmt_customer->execute();
$total_customer = $stmt_customer->get_result()->fetch_assoc()['total'];

// Total pesanan
$sql_pesanan = "SELECT COUNT(*) as total FROM orders WHERE toko_id = ?" . $filter_tanggal_sql;
$stmt_pesanan = $conn->prepare($sql_pesanan);
if($filter_tanggal_sql != ""){
    $stmt_pesanan->bind_param("iss", $toko_id, $tanggal_mulai, $tanggal_selesai);
} else {
    $stmt_pesanan->bind_param("i", $toko_id);
}
$stmt_pesanan->execute();
$total_pesanan = $stmt_pesanan->get_result()->fetch_assoc()['total'];

// Fungsi bantu untuk hitung jumlah pesanan per status (dengan filter tanggal)
function hitungStatus($conn, $toko_id, $status, $filter_tanggal_sql, $tanggal_mulai, $tanggal_selesai){
    $sql = "SELECT COUNT(*) as total FROM orders WHERE status = ? AND toko_id = ?" . $filter_tanggal_sql;
    $stmt = $conn->prepare($sql);
    if($filter_tanggal_sql != ""){
        $stmt->bind_param("siss", $status, $toko_id, $tanggal_mulai, $tanggal_selesai);
    } else {
        $stmt->bind_param("si", $status, $toko_id);
    }
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc()['total'];
}

$pesanan_pending = hitungStatus($conn, $toko_id, 'pending', $filter_tanggal_sql, $tanggal_mulai, $tanggal_selesai);
$pesanan_proses = hitungStatus($conn, $toko_id, 'proses', $filter_tanggal_sql, $tanggal_mulai, $tanggal_selesai);
$pesanan_dikirim = hitungStatus($conn, $toko_id, 'dikirim', $filter_tanggal_sql, $tanggal_mulai, $tanggal_selesai);
$pesanan_selesai = hitungStatus($conn, $toko_id, 'selesai', $filter_tanggal_sql, $tanggal_mulai, $tanggal_selesai);

// PENDAPATAN (hanya status selesai)
$sql_pendapatan = "SELECT SUM(total_harga) as total FROM orders WHERE status = 'selesai' AND toko_id = ?" . $filter_tanggal_sql;
$stmt_pendapatan = $conn->prepare($sql_pendapatan);
if($filter_tanggal_sql != ""){
    $stmt_pendapatan->bind_param("iss", $toko_id, $tanggal_mulai, $tanggal_selesai);
} else {
    $stmt_pendapatan->bind_param("i", $toko_id);
}
$stmt_pendapatan->execute();
$pendapatan = $stmt_pendapatan->get_result()->fetch_assoc();
$total_pendapatan = $pendapatan['total'] ?? 0;

// Pesanan terbaru (ikut filter tanggal)
$sql_recent = "
    SELECT o.*, u.nama as customer_name 
    FROM orders o
    JOIN users u ON o.user_id = u.id
    WHERE o.toko_id = ?" . $filter_tanggal_sql . "
    ORDER BY o.id DESC
    LIMIT 5
";
$stmt_recent = $conn->prepare($sql_recent);
if($filter_tanggal_sql != ""){
    $stmt_recent->bind_param("iss", $toko_id, $tanggal_mulai, $tanggal_selesai);
} else {
    $stmt_recent->bind_param("i", $toko_id);
}
$stmt_recent->execute();
$recent_orders = $stmt_recent->get_result();

// Produk terlaris (tidak difilter tanggal, tetap semua waktu)
$stmt_best = $conn->prepare("
    SELECT p.nama_produk, p.gambar, SUM(od.qty) as total_terjual
    FROM order_details od
    JOIN products p ON od.product_id = p.id
    WHERE p.toko_id = ?
    GROUP BY od.product_id
    ORDER BY total_terjual DESC
    LIMIT 5
");
$stmt_best->bind_param("i", $toko_id);
$stmt_best->execute();
$best_products = $stmt_best->get_result();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - AIC Fashion Metro</title>
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
            display: flex;
        }

        .sidebar {
            width: 260px;
            height: 100vh;
            background: #1e1e2f;
            color: white;
            position: fixed;
            left: 0;
            top: 0;
            overflow-y: auto;
            transition: 0.3s;
            z-index: 100;
        }

        .sidebar-header {
            padding: 25px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .logo {
            font-size: 22px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .logo i {
            font-size: 26px;
            color: #EE6C4D;
        }

        .sidebar-menu {
            padding: 20px;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            gap: 12px;
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            padding: 12px 15px;
            margin-bottom: 5px;
            border-radius: 12px;
            transition: 0.2s;
            font-size: 14px;
        }

        .sidebar-menu a i {
            width: 22px;
            font-size: 16px;
        }

        .sidebar-menu a:hover {
            background: rgba(238,108,77,0.2);
            color: #EE6C4D;
        }

        .sidebar-menu a.active {
            background: #EE6C4D;
            color: white;
        }

        .main {
            margin-left: 260px;
            padding: 25px 30px;
            width: 100%;
            min-height: 100vh;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .header h1 {
            font-size: 24px;
            color: #333;
            font-weight: 600;
        }

        .header h1 i {
            color: #EE6C4D;
            margin-right: 10px;
        }

        .admin-info {
            display: flex;
            align-items: center;
            gap: 12px;
            background: white;
            padding: 8px 20px;
            border-radius: 40px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .admin-info i {
            font-size: 16px;
            color: #EE6C4D;
        }

        .admin-info span {
            font-weight: 500;
            color: #555;
            font-size: 14px;
        }

        /* FILTER BAR */
        .filter-bar {
            background: white;
            padding: 18px 22px;
            border-radius: 16px;
            margin-bottom: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            display: flex;
            gap: 15px;
            align-items: flex-end;
            flex-wrap: wrap;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .filter-group label {
            font-size: 12px;
            font-weight: 600;
            color: #555;
        }

        .filter-group input {
            padding: 10px 15px;
            border: 1.5px solid #e0e0e0;
            border-radius: 12px;
            font-size: 13px;
            outline: none;
            font-family: 'Poppins', sans-serif;
        }

        .filter-group input:focus {
            border-color: #EE6C4D;
        }

        .btn-filter {
            background: #EE6C4D;
            color: white;
            border: none;
            padding: 11px 22px;
            border-radius: 12px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
        }

        .btn-reset-filter {
            background: #f0f0f0;
            color: #666;
            text-decoration: none;
            padding: 11px 18px;
            border-radius: 12px;
            font-size: 13px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
        }

        .btn-export {
            background: #2ecc71;
            color: white;
            text-decoration: none;
            padding: 11px 22px;
            border-radius: 12px;
            font-size: 13px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-export:hover {
            background: #27ae60;
        }

        .cards-row {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 25px;
        }

        .cards-row-2 {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .card {
            background: white;
            padding: 20px;
            border-radius: 16px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: 0.2s;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
        }

        .card-info p {
            color: #888;
            font-size: 13px;
            margin-bottom: 8px;
            font-weight: 500;
        }

        .card-info h2 {
            color: #333;
            font-size: 28px;
            font-weight: 700;
        }

        .card-icon {
            width: 50px;
            height: 50px;
            background: #fff5f2;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .card-icon i {
            font-size: 24px;
            color: #EE6C4D;
        }

        .card-pendapatan .card-info h2 {
            color: #2ecc71;
            font-size: 24px;
        }

        .section-wrapper {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
        }

        .box {
            background: white;
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .box-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 12px;
            border-bottom: 1px solid #eee;
        }

        .box-header h3 {
            font-size: 16px;
            color: #333;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .box-header h3 i {
            color: #EE6C4D;
        }

        .view-all {
            color: #EE6C4D;
            text-decoration: none;
            font-size: 12px;
        }

        .table-responsive {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        table th,
        table td {
            padding: 10px 8px;
            text-align: left;
            border-bottom: 1px solid #f0f0f0;
            font-size: 13px;
        }

        table th {
            color: #888;
            font-weight: 500;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 30px;
            font-size: 11px;
            font-weight: 600;
            text-transform: capitalize;
        }

        .status-pending {
            background: #fff3e0;
            color: #ff9800;
        }

        .status-proses {
            background: #e3f2fd;
            color: #2196f3;
        }

        .status-dikirim {
            background: #e8f5e9;
            color: #4caf50;
        }

        .status-selesai {
            background: #e8f5e9;
            color: #2ecc71;
        }

        .status-batal {
            background: #ffebee;
            color: #f44336;
        }

        .best-product-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .best-product-item:last-child {
            border-bottom: none;
        }

        .best-product-item img {
            width: 45px;
            height: 45px;
            object-fit: cover;
            border-radius: 10px;
            background: #f5f5f5;
        }

        .best-product-info {
            flex: 1;
        }

        .best-product-name {
            font-weight: 600;
            font-size: 13px;
            color: #333;
        }

        .best-product-sales {
            font-size: 11px;
            color: #888;
        }

        .best-product-qty {
            font-weight: 700;
            color: #EE6C4D;
            font-size: 14px;
        }

        .empty-state {
            text-align: center;
            padding: 30px;
            color: #999;
            font-size: 13px;
        }

        @media (max-width: 1100px) {
            .cards-row,
            .cards-row-2 {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 800px) {
            .sidebar {
                width: 70px;
            }
            .sidebar-header .logo span,
            .sidebar-menu a span {
                display: none;
            }
            .sidebar-menu a {
                justify-content: center;
            }
            .sidebar-menu a i {
                margin: 0;
            }
            .main {
                margin-left: 70px;
            }
            .section-wrapper {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 550px) {
            .cards-row,
            .cards-row-2 {
                grid-template-columns: 1fr;
            }
            .main {
                padding: 20px 15px;
            }
            .header h1 {
                font-size: 20px;
            }
            .filter-bar {
                flex-direction: column;
                align-items: stretch;
            }
        }
    </style>
</head>
<body>

<!-- SIDEBAR -->
<div class="sidebar">
    <div class="sidebar-header">
        <div class="logo">
            <i class="fa-solid fa-bag-shopping"></i>
            <span>AIC Fashion</span>
        </div>
    </div>
    <div class="sidebar-menu">
        <a href="dashboard.php" class="active">
            <i class="fa-solid fa-chart-line"></i>
            <span>Dashboard</span>
        </a>
        <a href="produk.php">
            <i class="fa-solid fa-box"></i>
            <span>Kelola Produk</span>
        </a>
        <a href="pesanan.php">
            <i class="fa-solid fa-truck"></i>
            <span>Pesanan</span>
        </a>
        <a href="customer.php">
            <i class="fa-solid fa-users"></i>
            <span>Customer</span>
        </a>
        <a href="admin.php">
            <i class="fa-solid fa-user-shield"></i>
            <span>Admin</span>
        </a>
        <a href="profil.php">
            <i class="fa-regular fa-user"></i>
            <span>Profil</span>
        </a>

        <hr style="border-color:rgba(255,255,255,0.1); margin:10px 0;">

        <a href="../dashboard.php" style="color: #ff9800;">
            <i class="fa-solid fa-arrow-left"></i>
            <span>Pilih Toko</span>
        </a>

        <a href="../logout.php">
            <i class="fa-solid fa-right-from-bracket"></i>
            <span>Logout</span>
        </a>
    </div>
</div>

<!-- MAIN CONTENT -->
<div class="main">
    <div class="header">
        <h1><i class="fa-solid fa-gauge-high"></i> Dashboard Admin</h1>
        <div class="admin-info">
            <i class="fa-regular fa-user"></i>
            <span><?php echo htmlspecialchars($_SESSION['nama']); ?></span>
            <i class="fa-regular fa-circle" style="font-size: 8px; color: #2ecc71;"></i>
            <span style="color: #2ecc71;">Admin</span>
        </div>
    </div>

    <!-- FILTER TANGGAL + EXPORT -->
    <form method="GET" class="filter-bar">
        <div class="filter-group">
            <label><i class="fa-regular fa-calendar"></i> Dari Tanggal</label>
            <input type="date" name="tanggal_mulai" value="<?php echo htmlspecialchars($tanggal_mulai); ?>">
        </div>
        <div class="filter-group">
            <label><i class="fa-regular fa-calendar"></i> Sampai Tanggal</label>
            <input type="date" name="tanggal_selesai" value="<?php echo htmlspecialchars($tanggal_selesai); ?>">
        </div>
        <button type="submit" class="btn-filter">
            <i class="fa-solid fa-filter"></i> Filter
        </button>
        <?php if($tanggal_mulai != '' || $tanggal_selesai != ''): ?>
            <a href="dashboard.php" class="btn-reset-filter">
                <i class="fa-solid fa-rotate-left"></i> Reset
            </a>
        <?php endif; ?>
        <a href="export_excel.php?tanggal_mulai=<?php echo urlencode($tanggal_mulai); ?>&tanggal_selesai=<?php echo urlencode($tanggal_selesai); ?>" class="btn-export">
            <i class="fa-solid fa-file-excel"></i> Export ke Excel
        </a>
    </form>

    <!-- BARIS 1: STATISTIK UTAMA -->
    <div class="cards-row">
        <div class="card">
            <div class="card-info">
                <p><i class="fa-regular fa-box"></i> Total Produk</p>
                <h2><?php echo number_format($total_produk); ?></h2>
            </div>
            <div class="card-icon">
                <i class="fa-solid fa-box"></i>
            </div>
        </div>
        <div class="card">
            <div class="card-info">
                <p><i class="fa-regular fa-user"></i> Total Customer</p>
                <h2><?php echo number_format($total_customer); ?></h2>
            </div>
            <div class="card-icon">
                <i class="fa-solid fa-users"></i>
            </div>
        </div>
        <div class="card">
            <div class="card-info">
                <p><i class="fa-regular fa-receipt"></i> Total Pesanan</p>
                <h2><?php echo number_format($total_pesanan); ?></h2>
            </div>
            <div class="card-icon">
                <i class="fa-solid fa-receipt"></i>
            </div>
        </div>
        <div class="card card-pendapatan">
            <div class="card-info">
                <p><i class="fa-regular fa-money-bill-1"></i> Total Pendapatan</p>
                <h2>Rp <?php echo number_format($total_pendapatan, 0, ',', '.'); ?></h2>
            </div>
            <div class="card-icon">
                <i class="fa-solid fa-money-bill-wave"></i>
            </div>
        </div>
    </div>

    <!-- BARIS 2: STATUS PESANAN -->
    <div class="cards-row-2">
        <div class="card">
            <div class="card-info">
                <p><i class="fa-regular fa-clock"></i> Menunggu</p>
                <h2><?php echo number_format($pesanan_pending); ?></h2>
            </div>
            <div class="card-icon" style="background: #fff3e0;">
                <i class="fa-solid fa-clock" style="color: #ff9800;"></i>
            </div>
        </div>
        <div class="card">
            <div class="card-info">
                <p><i class="fa-regular fa-spinner"></i> Diproses</p>
                <h2><?php echo number_format($pesanan_proses); ?></h2>
            </div>
            <div class="card-icon" style="background: #e3f2fd;">
                <i class="fa-solid fa-spinner" style="color: #2196f3;"></i>
            </div>
        </div>
        <div class="card">
            <div class="card-info">
                <p><i class="fa-regular fa-truck"></i> Dikirim</p>
                <h2><?php echo number_format($pesanan_dikirim); ?></h2>
            </div>
            <div class="card-icon" style="background: #e8f5e9;">
                <i class="fa-solid fa-truck" style="color: #4caf50;"></i>
            </div>
        </div>
        <div class="card">
            <div class="card-info">
                <p><i class="fa-regular fa-circle-check"></i> Selesai</p>
                <h2><?php echo number_format($pesanan_selesai); ?></h2>
            </div>
            <div class="card-icon" style="background: #e8f5e9;">
                <i class="fa-solid fa-circle-check" style="color: #2ecc71;"></i>
            </div>
        </div>
    </div>

    <!-- PESANAN TERBARU & PRODUK TERLARIS -->
    <div class="section-wrapper">
        <div class="box">
            <div class="box-header">
                <h3><i class="fa-solid fa-clock-rotate-left"></i> Pesanan Terbaru</h3>
                <a href="pesanan.php" class="view-all">Lihat Semua →</a>
            </div>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>ID Order</th>
                            <th>Customer</th>
                            <th>Total</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($recent_orders->num_rows > 0): ?>
                            <?php while($row = $recent_orders->fetch_assoc()): ?>
                                <tr>
                                    <td>#<?php echo str_pad($row['id'], 6, '0', STR_PAD_LEFT); ?></td>
                                    <td><?php echo htmlspecialchars(substr($row['customer_name'], 0, 15)); ?></td>
                                    <td>Rp <?php echo number_format($row['total_harga'], 0, ',', '.'); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $row['status']; ?>">
                                            <?php 
                                                $status_label = $row['status'];
                                                if($status_label == 'pending') echo 'Menunggu';
                                                elseif($status_label == 'proses') echo 'Diproses';
                                                elseif($status_label == 'dikirim') echo 'Dikirim';
                                                elseif($status_label == 'selesai') echo 'Selesai';
                                                else echo $status_label;
                                            ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="empty-state">Belum ada pesanan</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="box">
            <div class="box-header">
                <h3><i class="fa-solid fa-fire-flame-curved"></i> Produk Terlaris</h3>
                <a href="produk.php" class="view-all">Lihat Semua →</a>
            </div>
            <?php if($best_products->num_rows > 0): ?>
                <?php while($product = $best_products->fetch_assoc()): ?>
                    <div class="best-product-item">
                        <img src="../uploads/<?php echo htmlspecialchars($product['gambar']); ?>" 
                             alt="<?php echo htmlspecialchars($product['nama_produk']); ?>"
                             onerror="this.src='https://placehold.co/200x200?text=No+Image'">
                        <div class="best-product-info">
                            <div class="best-product-name"><?php echo htmlspecialchars($product['nama_produk']); ?></div>
                            <div class="best-product-sales">Total terjual</div>
                        </div>
                        <div class="best-product-qty">
                            <?php echo number_format($product['total_terjual']); ?> pcs
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fa-regular fa-chart-line" style="font-size: 30px; margin-bottom: 10px; display: block;"></i>
                    <p>Belum ada data penjualan</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

</body>
</html>
