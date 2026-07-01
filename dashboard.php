<?php
session_start();
include "config/database.php";

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin'){
    header("Location: login.php");
    exit;
}

// Ambil statistik toko 1 (app_id=1)
$toko1_produk = 0;
$toko1_pesanan = 0;
$toko1_customer = 0;

// Cek apakah kolom app_id sudah ada
$cek_kolom = mysqli_query($conn, "SHOW COLUMNS FROM products LIKE 'app_id'");
if(mysqli_num_rows($cek_kolom) > 0){
    $toko1_produk = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM products WHERE app_id = 1"));
    $toko1_pesanan = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM orders WHERE app_id = 1"));
    $toko1_customer = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM users WHERE app_id = 1 AND role='customer'"));
} else {
    $toko1_produk = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM products"));
    $toko1_pesanan = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM orders"));
    $toko1_customer = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM users WHERE role='customer'"));
}

// Ambil statistik toko 2 (app_id=2)
$toko2_produk = 0;
$toko2_pesanan = 0;
$toko2_customer = 0;

if(mysqli_num_rows($cek_kolom) > 0){
    $toko2_produk = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM products WHERE app_id = 2"));
    $toko2_pesanan = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM orders WHERE app_id = 2"));
    $toko2_customer = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM users WHERE app_id = 2 AND role='customer'"));
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Pilih Toko</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #e9edf5 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 30px;
        }

        .container {
            max-width: 1000px;
            width: 100%;
        }

        /* ===== HEADER ===== */
        .header {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
            padding: 25px 35px;
            border-radius: 24px;
            margin-bottom: 35px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.15);
        }

        .header h1 {
            font-size: 26px;
            font-weight: 700;
            color: white;
        }

        .header h1 i {
            color: #EE6C4D;
            margin-right: 12px;
        }

        .header .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .header .user-info span {
            color: rgba(255,255,255,0.85);
            font-weight: 500;
            font-size: 14px;
        }

        .logout-btn {
            background: rgba(255,255,255,0.12);
            color: white;
            padding: 8px 20px;
            border-radius: 40px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
            transition: 0.3s;
            border: 1px solid rgba(255,255,255,0.1);
        }

        .logout-btn:hover {
            background: #f44336;
            border-color: #f44336;
            transform: translateY(-2px);
        }

        /* ===== SUBTITLE ===== */
        .subtitle {
            text-align: center;
            color: #555;
            margin-bottom: 30px;
            font-size: 16px;
            font-weight: 500;
        }

        .subtitle i {
            color: #EE6C4D;
            margin-right: 8px;
        }

        /* ===== TOKO GRID ===== */
        .toko-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }

        /* ===== TOKO CARD ===== */
        .toko-card {
            background: white;
            border-radius: 24px;
            padding: 35px 30px 30px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.06);
            transition: all 0.35s ease;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .toko-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
        }

        .toko-card.toko1::before {
            background: linear-gradient(90deg, #EE6C4D, #ff9a76);
        }

        .toko-card.toko2::before {
            background: linear-gradient(90deg, #2196f3, #64b5f6);
        }

        .toko-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 50px rgba(0,0,0,0.12);
        }

        /* ===== TOKO ICON ===== */
        .toko-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 34px;
            margin: 0 auto 15px;
            transition: 0.3s;
        }

        .toko1 .toko-icon {
            background: linear-gradient(135deg, #fff3e0, #ffe0cc);
            color: #EE6C4D;
        }

        .toko2 .toko-icon {
            background: linear-gradient(135deg, #e3f2fd, #bbdefb);
            color: #2196f3;
        }

        .toko-card:hover .toko-icon {
            transform: scale(1.08);
        }

        .toko-title {
            font-size: 22px;
            font-weight: 700;
            color: #1a1a2e;
        }

        .toko-subtitle {
            font-size: 13px;
            color: #999;
            margin-bottom: 15px;
            font-weight: 400;
        }

        /* ===== STATS ===== */
        .toko-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            margin: 20px 0 25px;
        }

        .stat-item {
            background: #f8f9fc;
            padding: 14px 10px;
            border-radius: 16px;
            transition: 0.3s;
        }

        .stat-item:hover {
            background: #f0f2f8;
        }

        .stat-item .number {
            font-size: 24px;
            font-weight: 700;
            color: #1a1a2e;
            line-height: 1.2;
        }

        .stat-item .label {
            font-size: 11px;
            color: #999;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* ===== BUTTON KELOLA ===== */
        .btn-kelola {
            display: inline-block;
            padding: 12px 40px;
            color: white;
            text-decoration: none;
            border-radius: 50px;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
            margin-top: 5px;
            border: none;
            cursor: pointer;
            letter-spacing: 0.3px;
        }

        .toko1 .btn-kelola {
            background: linear-gradient(135deg, #EE6C4D, #e85d3a);
            box-shadow: 0 4px 15px rgba(238,108,77,0.35);
        }

        .toko1 .btn-kelola:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(238,108,77,0.45);
        }

        .toko2 .btn-kelola {
            background: linear-gradient(135deg, #2196f3, #1976d2);
            box-shadow: 0 4px 15px rgba(33,150,243,0.35);
        }

        .toko2 .btn-kelola:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(33,150,243,0.45);
        }

        .btn-kelola i {
            margin-left: 8px;
            transition: 0.3s;
        }

        .btn-kelola:hover i {
            transform: translateX(4px);
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 768px) {
            .toko-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .header {
                flex-direction: column;
                text-align: center;
                padding: 20px;
            }

            .header h1 {
                font-size: 22px;
            }

            .toko-stats {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media (max-width: 480px) {
            body {
                padding: 15px;
            }

            .toko-card {
                padding: 25px 20px;
            }

            .toko-title {
                font-size: 18px;
            }

            .stat-item .number {
                font-size: 20px;
            }

            .btn-kelola {
                padding: 10px 28px;
                font-size: 13px;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h1>
            <i class="fa-solid fa-gauge-high"></i> Admin Panel
        </h1>
        <div class="user-info">
            <span>👋 Halo, <?php echo htmlspecialchars($_SESSION['nama']); ?></span>
            <a href="logout.php" class="logout-btn">
                <i class="fa-solid fa-right-from-bracket"></i> Logout
            </a>
        </div>
    </div>

    <p class="subtitle">
        <i class="fa-regular fa-compass"></i> Pilih toko yang ingin Anda kelola
    </p>

    <div class="toko-grid">
        <!-- TOKO 1 -->
        <div class="toko-card toko1">
            <div class="toko-icon">🛍</div>
            <div class="toko-title">AIC Fashion</div>
            <div class="toko-subtitle">Toko 1</div>

            <div class="toko-stats">
                <div class="stat-item">
                    <div class="number"><?php echo $toko1_produk; ?></div>
                    <div class="label">Produk</div>
                </div>
                <div class="stat-item">
                    <div class="number"><?php echo $toko1_pesanan; ?></div>
                    <div class="label">Pesanan</div>
                </div>
                <div class="stat-item">
                    <div class="number"><?php echo $toko1_customer; ?></div>
                    <div class="label">Customer</div>
                </div>
            </div>

            <a href="toko1/dashboard.php" class="btn-kelola">
                Kelola Toko <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>

        <!-- TOKO 2 -->
        <div class="toko-card toko2">
            <div class="toko-icon">🏬</div>
            <div class="toko-title">AIC Fashion Metro</div>
            <div class="toko-subtitle">Toko 2</div>

            <div class="toko-stats">
                <div class="stat-item">
                    <div class="number"><?php echo $toko2_produk; ?></div>
                    <div class="label">Produk</div>
                </div>
                <div class="stat-item">
                    <div class="number"><?php echo $toko2_pesanan; ?></div>
                    <div class="label">Pesanan</div>
                </div>
                <div class="stat-item">
                    <div class="number"><?php echo $toko2_customer; ?></div>
                    <div class="label">Customer</div>
                </div>
            </div>

            <a href="toko2/dashboard.php" class="btn-kelola">
                Kelola Toko <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>
    </div>
</div>

</body>
</html>