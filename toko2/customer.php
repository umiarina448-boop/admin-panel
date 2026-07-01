<?php
session_start();
include "../config/database.php";

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin'){
    header("Location: ../login.php");
    exit;
}

/* =========================
   SEARCH & FILTER
========================= */
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

$sql = "
    SELECT * FROM users
    WHERE role = 'customer'
";

if($search != ''){
    $sql .= " AND (nama LIKE '%$search%' OR email LIKE '%$search%' OR no_hp LIKE '%$search%')";
}

$sql .= " ORDER BY id DESC";

$data = mysqli_query($conn, $sql);

// Hitung total customer
$total_customer = mysqli_num_rows($data);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Customer - Admin AIC Fashion Metro</title>
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
        .stats-card {
            background: white;
            padding: 20px;
            border-radius: 20px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .stats-info h3 {
            font-size: 14px;
            color: #888;
            font-weight: 500;
            margin-bottom: 5px;
        }

        .stats-info .number {
            font-size: 32px;
            font-weight: 700;
            color: #EE6C4D;
        }

        .stats-icon {
            width: 60px;
            height: 60px;
            background: #fff5f2;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .stats-icon i {
            font-size: 28px;
            color: #EE6C4D;
        }

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
            min-width: 250px;
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
            transition: 0.2s;
        }

        .search-box input:focus {
            border-color: #EE6C4D;
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

        .avatar {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #EE6C4D, #ff8a65);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 16px;
        }

        .badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 30px;
            font-size: 11px;
            font-weight: 600;
            background: #e8f5e9;
            color: #2ecc71;
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
        @media (max-width: 700px) {
            .navbar {
                flex-direction: column;
                text-align: center;
            }
            .container {
                padding: 20px 15px;
            }
            .filter-bar {
                flex-direction: column;
            }
            .search-box {
                width: 100%;
            }
            .header {
                flex-direction: column;
                text-align: center;
            }
        }
    </style>
</head>
<body>

<!-- TOP NAVBAR -->
<div class="navbar">
    <div class="logo">
        <i class="fa-solid fa-bag-shopping"></i>
        AIC Fashion Metro Admin
    </div>
    <div class="nav-menu">
        <a href="dashboard.php">
            <i class="fa-solid fa-chart-line"></i> Dashboard
        </a>
        <a href="produk.php">
            <i class="fa-solid fa-box"></i> Produk
        </a>
        <a href="pesanan.php">
            <i class="fa-solid fa-truck"></i> Pesanan
        </a>
        <a href="customer.php" class="active">
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
            <i class="fa-solid fa-users"></i> Data Customer
        </h2>
    </div>

    <!-- STATISTIK CARD -->
    <div class="stats-card">
        <div class="stats-info">
            <h3><i class="fa-regular fa-user"></i> Total Customer</h3>
            <div class="number"><?php echo $total_customer; ?></div>
        </div>
        <div class="stats-icon">
            <i class="fa-solid fa-users"></i>
        </div>
    </div>

    <!-- FILTER & SEARCH -->
    <form method="GET" class="filter-bar">
        <div class="search-box">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" name="search" placeholder="Cari customer (nama, email, atau no HP)..." value="<?php echo htmlspecialchars($search); ?>">
        </div>
        <button type="submit" class="filter-btn">
            <i class="fa-solid fa-filter"></i> Cari
        </button>
        <?php if($search != ''): ?>
            <a href="customer.php" class="reset-btn">
                <i class="fa-solid fa-rotate-left"></i> Reset
            </a>
        <?php endif; ?>
    </form>

    <!-- TABLE CUSTOMER -->
    <div class="table-container">
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Customer</th>
                        <th>Email</th>
                        <th>No. HP</th>
                        <th>Alamat</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($total_customer == 0): ?>
                        <tr>
                            <td colspan="6" class="empty-state">
                                <i class="fa-regular fa-user"></i>
                                <p>Belum ada customer</p>
                            </td>
                        </tr>
                    <?php endif; ?>

                    <?php while($row = mysqli_fetch_assoc($data)): ?>
                        <tr>
                            <td>
                                <strong>#<?php echo str_pad($row['id'], 4, '0', STR_PAD_LEFT); ?></strong>
                            </td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <div class="avatar">
                                        <?php echo strtoupper(substr($row['nama'], 0, 1)); ?>
                                    </div>
                                    <div>
                                        <strong><?php echo htmlspecialchars($row['nama']); ?></strong>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <i class="fa-regular fa-envelope" style="color: #888; margin-right: 5px;"></i>
                                <?php echo htmlspecialchars($row['email']); ?>
                            </td>
                            <td>
                                <i class="fa-solid fa-phone" style="color: #888; margin-right: 5px;"></i>
                                <?php echo htmlspecialchars($row['no_hp'] ?: '-'); ?>
                            </td>
                            <td>
                                <i class="fa-solid fa-location-dot" style="color: #888; margin-right: 5px;"></i>
                                <?php 
                                $alamat = $row['alamat'] ?? '-';
                                echo strlen($alamat) > 30 ? substr($alamat, 0, 30) . '...' : htmlspecialchars($alamat);
                                ?>
                            </td>
                            <td>
                                <span class="badge">
                                    <i class="fa-regular fa-circle-check"></i> Customer
                                </span>
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