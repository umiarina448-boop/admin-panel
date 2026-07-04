<?php
session_start();
include "../config/database.php";

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin'){
    header("Location: ../login.php");
    exit;
}

/* =========================
   UPDATE STATUS
========================= */
if(isset($_GET['id']) && isset($_GET['status'])){

    $id = intval($_GET['id']);
    $status = $_GET['status'];

    $allowed = ['pending', 'proses', 'dikirim', 'selesai', 'batal'];

    if(in_array($status, $allowed)){
        $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ? AND toko_id = 2
        ");
        $stmt->bind_param("si", $status, $id);
        $stmt->execute();
    }

    header("Location: pesanan.php");
    exit;
}

/* =========================
   SEARCH & FILTER
========================= */
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status_filter']) ? $_GET['status_filter'] : '';

$sql = "
    SELECT o.*, u.nama AS nama_user, u.email, u.no_hp
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.id
    WHERE o.toko_id = 2
";

if($search != ''){
    $search_esc = mysqli_real_escape_string($conn, $search);
    $sql .= " AND (u.nama LIKE '%$search_esc%' OR o.id LIKE '%$search_esc%')";
}

if($status_filter != ''){
    $status_filter_esc = mysqli_real_escape_string($conn, $status_filter);
    $sql .= " AND o.status = '$status_filter_esc'";
}

$sql .= " ORDER BY o.id DESC";

$data = mysqli_query($conn, $sql);

/* =========================
   EXPORT KE EXCEL (jika ?export=1)
========================= */
if(isset($_GET['export']) && $_GET['export'] == '2'){

    $status_label_map = [
        'pending' => 'Menunggu',
        'proses' => 'Diproses',
        'dikirim' => 'Dikirim',
        'selesai' => 'Selesai',
        'batal' => 'Dibatalkan'
    ];

    $filter_label = $status_filter != '' ? "_" . $status_filter : "_semua_status";
    $filename = "laporan_pesanan_toko1" . $filter_label . ".xls";

    header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
    header("Content-Disposition: attachment; filename=\"$filename\"");
    header("Pragma: no-cache");
    header("Expires: 0");
    ?>
    <table border="1">
        <tr>
            <th colspan="6" style="font-size:16px; font-weight:bold;">
                Laporan Pesanan - Toko 1
                <?php if($search != ''): ?>
                    (Pencarian: <?php echo htmlspecialchars($search); ?>)
                <?php endif; ?>
                <?php if($status_filter != ''): ?>
                    (Status: <?php echo htmlspecialchars($status_label_map[$status_filter] ?? $status_filter); ?>)
                <?php endif; ?>
            </th>
        </tr>
        <tr><td colspan="6"></td></tr>
        <tr>
            <th>ID Order</th>
            <th>Customer</th>
            <th>Email</th>
            <th>No HP</th>
            <th>Total Harga</th>
            <th>Status</th>
        </tr>
        <?php if(mysqli_num_rows($data) > 0): ?>
            <?php while($row = mysqli_fetch_assoc($data)): 
                $status_export = $row['status'] ?? 'pending';
                $status_label_export = $status_label_map[$status_export] ?? ucfirst($status_export);
            ?>
            <tr>
                <td>#<?php echo str_pad($row['id'], 6, '0', STR_PAD_LEFT); ?></td>
                <td><?php echo htmlspecialchars($row['nama_user'] ?? 'Unknown'); ?></td>
                <td><?php echo htmlspecialchars($row['email'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($row['no_hp'] ?? ''); ?></td>
                <td><?php echo $row['total_harga']; ?></td>
                <td><?php echo $status_label_export; ?></td>
            </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="6">Tidak ada data</td></tr>
        <?php endif; ?>
    </table>
    <?php
    exit;
}

// Hitung statistik per status
$stats = [];
$status_list = ['pending', 'proses', 'dikirim', 'selesai', 'batal'];
foreach($status_list as $st){
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM orders WHERE status = ? AND toko_id = 2");
    $stmt->bind_param("s", $st);
    $stmt->execute();
    $result = $stmt->get_result();
    $stats[$st] = mysqli_fetch_assoc($result)['total'];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesanan - Admin AIC Fashion</title>
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
        }

        /* ========== TOP NAVBAR ========== */
        .navbar {
            background: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            position: sticky;
            top: 0;
            z-index: 100;
            flex-wrap: wrap;
            gap: 15px;
        }

        .logo {
            font-size: 22px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 8px;
            color: #EE6C4D;
        }

        .logo i {
            font-size: 24px;
        }

        .nav-menu {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .nav-menu a {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #555;
            text-decoration: none;
            padding: 10px 18px;
            border-radius: 40px;
            font-size: 14px;
            font-weight: 500;
            transition: 0.2s;
        }

        .nav-menu a i {
            font-size: 14px;
        }

        .nav-menu a:hover {
            background: #fff5f2;
            color: #EE6C4D;
        }

        .nav-menu a.active {
            background: #EE6C4D;
            color: white;
        }

        .admin-info {
            display: flex;
            align-items: center;
            gap: 10px;
            background: #f8f9fa;
            padding: 8px 18px;
            border-radius: 40px;
        }

        .admin-info i {
            color: #EE6C4D;
        }

        /* ========== MAIN CONTENT ========== */
        .container {
            padding: 25px 30px;
        }

        /* HEADER */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .header h2 {
            font-size: 24px;
            color: #333;
            font-weight: 600;
        }

        .header h2 i {
            color: #EE6C4D;
            margin-right: 10px;
        }

        /* STATS CARD */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 15px;
            margin-bottom: 25px;
        }

        .stat-card {
            background: white;
            padding: 15px;
            border-radius: 16px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-3px);
        }

        .stat-card .number {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .stat-card .label {
            font-size: 12px;
            color: #888;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
        }

        .stat-card.pending .number { color: #ff9800; }
        .stat-card.proses .number { color: #2196f3; }
        .stat-card.dikirim .number { color: #4caf50; }
        .stat-card.selesai .number { color: #2ecc71; }
        .stat-card.batal .number { color: #f44336; }

        /* FILTER BAR */
        .filter-bar {
            background: white;
            padding: 20px;
            border-radius: 16px;
            margin-bottom: 25px;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .search-box {
            flex: 1;
            min-width: 200px;
            position: relative;
        }

        .search-box i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
        }

        .search-box input {
            width: 100%;
            padding: 12px 15px 12px 45px;
            border: 1px solid #e0e0e0;
            border-radius: 40px;
            font-size: 14px;
            outline: none;
        }

        .search-box input:focus {
            border-color: #EE6C4D;
        }

        .filter-box select {
            padding: 12px 20px;
            border: 1px solid #e0e0e0;
            border-radius: 40px;
            font-size: 14px;
            background: white;
            cursor: pointer;
        }

        .filter-btn {
            background: #EE6C4D;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 40px;
            cursor: pointer;
            font-weight: 500;
        }

        .reset-btn {
            background: #f0f0f0;
            color: #666;
            text-decoration: none;
            padding: 12px 20px;
            border-radius: 40px;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .export-btn {
            background: #2ecc71;
            color: white;
            text-decoration: none;
            padding: 12px 22px;
            border-radius: 40px;
            font-size: 14px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .export-btn:hover {
            background: #27ae60;
        }

        /* TABLE */
        .table-container {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }

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
            background: #f8f9fa;
            color: #555;
            font-weight: 600;
            font-size: 13px;
        }

        td {
            font-size: 13px;
            color: #333;
        }

        .status-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 30px;
            font-size: 11px;
            font-weight: 600;
        }

        .status-pending { background: #fff3e0; color: #ff9800; }
        .status-proses { background: #e3f2fd; color: #2196f3; }
        .status-dikirim { background: #e8f5e9; color: #4caf50; }
        .status-selesai { background: #e8f5e9; color: #2ecc71; }
        .status-batal { background: #ffebee; color: #f44336; }

        .btn-action {
            padding: 6px 12px;
            border-radius: 30px;
            text-decoration: none;
            font-size: 11px;
            font-weight: 500;
            margin-right: 5px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .btn-detail {
            background: #6c757d;
            color: white;
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

        .empty-state {
            text-align: center;
            padding: 50px;
            color: #999;
        }

        .empty-state i {
            font-size: 50px;
            margin-bottom: 15px;
            color: #ddd;
        }

        /* RESPONSIVE */
        @media (max-width: 900px) {
            .stats-row {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media (max-width: 700px) {
            .navbar {
                flex-direction: column;
                text-align: center;
            }
            .container {
                padding: 20px 15px;
            }
            .stats-row {
                grid-template-columns: repeat(2, 1fr);
            }
            .filter-bar {
                flex-direction: column;
            }
            .search-box, .filter-box {
                width: 100%;
            }
        }
    </style>
</head>
<body>

<!-- TOP NAVBAR -->
<div class="navbar">
    <div class="logo">
        <i class="fa-solid fa-bag-shopping"></i>
        AIC Fashion Admin
    </div>
    <div class="nav-menu">
        <a href="dashboard.php">
            <i class="fa-solid fa-chart-line"></i> Dashboard
        </a>
        <a href="produk.php">
            <i class="fa-solid fa-box"></i> Produk
        </a>
        <a href="pesanan.php" class="active">
            <i class="fa-solid fa-truck"></i> Pesanan
        </a>
        <a href="customer.php">
            <i class="fa-solid fa-users"></i> Customer
        </a>
        <a href="admin.php">
            <i class="fa-solid fa-user-shield"></i> Admin
        </a>
        <a href="../logout.php">
            <i class="fa-solid fa-right-from-bracket"></i> Logout
        </a>
    </div>
    <div class="admin-info">
        <i class="fa-regular fa-user"></i>
        <span><?php echo htmlspecialchars($_SESSION['nama']); ?></span>
    </div>
</div>

<!-- MAIN CONTENT -->
<div class="container">
    <div class="header">
        <h2>
            <i class="fa-solid fa-truck"></i> Manajemen Pesanan
        </h2>
    </div>

    <!-- STATISTIK CARD -->
    <div class="stats-row">
        <div class="stat-card pending">
            <div class="number"><?php echo $stats['pending']; ?></div>
            <div class="label"><i class="fa-regular fa-clock"></i> Menunggu</div>
        </div>
        <div class="stat-card proses">
            <div class="number"><?php echo $stats['proses']; ?></div>
            <div class="label"><i class="fa-solid fa-spinner"></i> Diproses</div>
        </div>
        <div class="stat-card dikirim">
            <div class="number"><?php echo $stats['dikirim']; ?></div>
            <div class="label"><i class="fa-solid fa-truck"></i> Dikirim</div>
        </div>
        <div class="stat-card selesai">
            <div class="number"><?php echo $stats['selesai']; ?></div>
            <div class="label"><i class="fa-regular fa-circle-check"></i> Selesai</div>
        </div>
        <div class="stat-card batal">
            <div class="number"><?php echo $stats['batal']; ?></div>
            <div class="label"><i class="fa-solid fa-ban"></i> Dibatalkan</div>
        </div>
    </div>

    <!-- FILTER & SEARCH -->
    <form method="GET" class="filter-bar">
        <div class="search-box">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" name="search" placeholder="Cari pesanan (ID atau Customer)..." value="<?php echo htmlspecialchars($search); ?>">
        </div>
        <div class="filter-box">
            <select name="status_filter">
                <option value="">Semua Status</option>
                <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Menunggu</option>
                <option value="proses" <?php echo $status_filter == 'proses' ? 'selected' : ''; ?>>Diproses</option>
                <option value="dikirim" <?php echo $status_filter == 'dikirim' ? 'selected' : ''; ?>>Dikirim</option>
                <option value="selesai" <?php echo $status_filter == 'selesai' ? 'selected' : ''; ?>>Selesai</option>
                <option value="batal" <?php echo $status_filter == 'batal' ? 'selected' : ''; ?>>Dibatalkan</option>
            </select>
        </div>
        <button type="submit" class="filter-btn">
            <i class="fa-solid fa-filter"></i> Filter
        </button>
        <?php if($search != '' || $status_filter != ''): ?>
            <a href="pesanan.php" class="reset-btn">
                <i class="fa-solid fa-rotate-left"></i> Reset
            </a>
        <?php endif; ?>
        <a href="pesanan.php?export=1&search=<?php echo urlencode($search); ?>&status_filter=<?php echo urlencode($status_filter); ?>" class="export-btn">
            <i class="fa-solid fa-file-excel"></i> Export ke Excel
        </a>
    </form>

    <!-- TABLE PESANAN -->
    <div class="table-container">
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>ID Order</th>
                        <th>Customer</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(mysqli_num_rows($data) == 0): ?>
                        <tr>
                            <td colspan="5" class="empty-state">
                                <i class="fa-regular fa-inbox"></i>
                                <p>Belum ada pesanan</p>
                            </td>
                        </tr>
                    <?php endif; ?>

                    <?php while($row = mysqli_fetch_assoc($data)): 
                        $status = $row['status'] ?? 'pending';
                        $status_label = [
                            'pending' => 'Menunggu',
                            'proses' => 'Diproses',
                            'dikirim' => 'Dikirim',
                            'selesai' => 'Selesai',
                            'batal' => 'Dibatalkan'
                        ][$status] ?? ucfirst($status);
                    ?>
                        <tr>
                            <td>
                                <strong>#<?php echo str_pad($row['id'], 6, '0', STR_PAD_LEFT); ?></strong>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($row['nama_user'] ?? 'Unknown'); ?>
                                <br>
                                <small style="color:#999;"><?php echo htmlspecialchars($row['email'] ?? ''); ?></small>
                            </td>
                            <td>Rp <?php echo number_format($row['total_harga'], 0, ',', '.'); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo $status; ?>">
                                    <?php echo $status_label; ?>
                                </span>
                            </td>
                            <td>
                                <a class="btn-action btn-detail" href="detail_pesanan.php?id=<?php echo $row['id']; ?>">
                                    <i class="fa-regular fa-eye"></i> Detail
                                </a>
                                
                                <?php if($status == 'pending'): ?>
                                    <a class="btn-action btn-proses" href="?id=<?php echo $row['id']; ?>&status=proses">
                                        <i class="fa-solid fa-play"></i> Proses
                                    </a>
                                <?php endif; ?>
                                
                                <?php if($status == 'proses'): ?>
                                    <a class="btn-action btn-kirim" href="?id=<?php echo $row['id']; ?>&status=dikirim">
                                        <i class="fa-solid fa-truck"></i> Kirim
                                    </a>
                                <?php endif; ?>
                                
                                <?php if($status == 'dikirim'): ?>
                                    <a class="btn-action btn-selesai" href="?id=<?php echo $row['id']; ?>&status=selesai">
                                        <i class="fa-regular fa-circle-check"></i> Selesai
                                    </a>
                                <?php endif; ?>
                                
                                <?php if($status == 'pending' || $status == 'proses'): ?>
                                    <a class="btn-action btn-batal" href="?id=<?php echo $row['id']; ?>&status=batal" 
                                       onclick="return confirm('Yakin ingin membatalkan pesanan ini?')">
                                        <i class="fa-solid fa-ban"></i> Batal
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</body>
</html>
