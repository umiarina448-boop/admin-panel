<?php
session_start();
include "../config/database.php";

if(!isset($_SESSION['user_id'])){
    header("Location: ../login.php");
    exit;
}

if($_SESSION['role'] != 'admin'){
    header("Location: ../customer/dashboard.php");
    exit;
}

/* =========================
   TOKO AKTIF
   Sesuaikan dengan toko admin panel ini
========================= */
$toko_id = 2; // ganti sesuai toko (1 atau 2), atau ambil dari $_SESSION['toko_id'] kalau sistemnya sudah session-based

/* =========================
   HAPUS PRODUK (FIX FK ERROR)
========================= */
if(isset($_GET['hapus'])){
    $id = (int)$_GET['hapus'];

    // Pastikan produk yang dihapus memang milik toko aktif
    $stmt_check = $conn->prepare("SELECT id FROM products WHERE id = ? AND toko_id = ?");
    $stmt_check->bind_param("ii", $id, $toko_id);
    $stmt_check->execute();
    $cek = $stmt_check->get_result();

    if($cek->num_rows > 0){
        // Gunakan prepared statement
        $stmt_size = $conn->prepare("DELETE FROM product_sizes WHERE product_id = ?");
        $stmt_size->bind_param("i", $id);
        $stmt_size->execute();

        // ambil gambar
        $stmt_img = $conn->prepare("SELECT gambar FROM products WHERE id = ?");
        $stmt_img->bind_param("i", $id);
        $stmt_img->execute();
        $dataImg = $stmt_img->get_result()->fetch_assoc();

        if($dataImg && $dataImg['gambar']){
            if(file_exists("../uploads/".$dataImg['gambar'])){
                unlink("../uploads/".$dataImg['gambar']);
            }
        }

        // hapus produk
        $stmt_del = $conn->prepare("DELETE FROM products WHERE id = ?");
        $stmt_del->bind_param("i", $id);
        $stmt_del->execute();
    }

    header("Location: produk.php");
    exit;
}

/* =========================
   SEARCH & FILTER
========================= */
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category_filter = isset($_GET['category']) ? (int)$_GET['category'] : 0;

// Query dengan prepared statement, difilter berdasarkan toko_id
$sql = "
    SELECT p.*, c.nama_kategori AS kategori
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE p.toko_id = ?
";

$params = [$toko_id];
$types = "i";

if($search != ''){
    $sql .= " AND p.nama_produk LIKE ?";
    $params[] = "%$search%";
    $types .= "s";
}

if($category_filter > 0){
    $sql .= " AND p.category_id = ?";
    $params[] = $category_filter;
    $types .= "i";
}

$sql .= " ORDER BY p.id DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$data = $stmt->get_result();

// Ambil kategori untuk filter
$categories = mysqli_query($conn, "SELECT * FROM categories ORDER BY nama_kategori");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Produk - Admin AIC Fashion</title>
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

        .container {
            padding: 25px 30px;
        }

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

        .btn-add {
            background: #2ecc71;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 40px;
            font-weight: 600;
            font-size: 14px;
            transition: 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-add:hover {
            background: #27ae60;
            transform: translateY(-2px);
        }

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
            transition: 0.2s;
        }

        .search-box input:focus {
            border-color: #EE6C4D;
        }

        .filter-box select {
            padding: 12px 20px;
            border: 1px solid #e0e0e0;
            border-radius: 40px;
            font-size: 14px;
            outline: none;
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
            transition: 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .reset-btn:hover {
            background: #e0e0e0;
        }

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

        .product-img {
            width: 55px;
            height: 55px;
            object-fit: cover;
            border-radius: 10px;
            background: #f5f5f5;
        }

        .stock-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 30px;
            font-size: 11px;
            font-weight: 600;
        }

        .stock-high {
            background: #e8f5e9;
            color: #2ecc71;
        }

        .stock-low {
            background: #fff3e0;
            color: #ff9800;
        }

        .stock-out {
            background: #ffebee;
            color: #f44336;
        }

        .btn-edit {
            background: #3498db;
            color: white;
            padding: 6px 14px;
            border-radius: 30px;
            text-decoration: none;
            font-size: 12px;
            font-weight: 500;
            margin-right: 8px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-edit:hover {
            background: #2980b9;
        }

        .btn-delete {
            background: #e74c3c;
            color: white;
            padding: 6px 14px;
            border-radius: 30px;
            text-decoration: none;
            font-size: 12px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-delete:hover {
            background: #c0392b;
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
            .search-box, .filter-box {
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

<!-- TOP NAVBAR (tanpa sidebar) -->
<div class="navbar">
    <div class="logo">
        <i class="fa-solid fa-bag-shopping"></i>
        AIC Fashion Admin
    </div>
    <div class="nav-menu">
        <a href="dashboard.php">
            <i class="fa-solid fa-chart-line"></i> Dashboard
        </a>
        <a href="produk.php" class="active">
            <i class="fa-solid fa-box"></i> Produk
        </a>
        <a href="pesanan.php">
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
            <i class="fa-solid fa-box"></i> Kelola Produk
        </h2>
        <a href="tambah_produk.php" class="btn-add">
            <i class="fa-solid fa-plus"></i> Tambah Produk
        </a>
    </div>

    <!-- FILTER & SEARCH -->
    <form method="GET" class="filter-bar">
        <div class="search-box">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" name="search" placeholder="Cari produk..." value="<?php echo htmlspecialchars($search); ?>">
        </div>
        <div class="filter-box">
            <select name="category">
                <option value="0">Semua Kategori</option>
                <?php while($cat = mysqli_fetch_assoc($categories)){ ?>
                    <option value="<?php echo $cat['id']; ?>" <?php echo ($category_filter == $cat['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($cat['nama_kategori']); ?>
                    </option>
                <?php } ?>
            </select>
        </div>
        <button type="submit" class="filter-btn">
            <i class="fa-solid fa-filter"></i> Filter
        </button>
        <?php if($search != '' || $category_filter > 0){ ?>
            <a href="produk.php" class="reset-btn">
                <i class="fa-solid fa-rotate-left"></i> Reset
            </a>
        <?php } ?>
    </form>

    <!-- TABLE PRODUK -->
    <div class="table-container">
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Gambar</th>
                        <th>Nama Produk</th>
                        <th>Kategori</th>
                        <th>Harga</th>
                        <th>Stok</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(mysqli_num_rows($data) == 0){ ?>
                        <tr>
                            <td colspan="6" class="empty-state">
                                <i class="fa-regular fa-box"></i>
                                <p>Belum ada produk</p>
                                <a href="tambah_produk.php" style="color:#EE6C4D; text-decoration:none;">+ Tambah produk sekarang</a>
                            </td>
                        </tr>
                    <?php } ?>

                    <?php while($row = mysqli_fetch_assoc($data)){ 
                        $stok = $row['stok'] ?? 0;
                        if($stok > 10){
                            $stock_class = 'stock-high';
                            $stock_text = 'Tersedia';
                        } elseif($stok > 0){
                            $stock_class = 'stock-low';
                            $stock_text = $stok;
                        } else {
                            $stock_class = 'stock-out';
                            $stock_text = 'Habis';
                        }
                    ?>
                        <tr>
                            <td>
                                <img class="product-img" src="../uploads/<?php echo htmlspecialchars($row['gambar']); ?>" 
                                     alt="<?php echo htmlspecialchars($row['nama_produk']); ?>"
                                     onerror="this.src='https://placehold.co/200x200?text=No+Image'">
                            </td>
                            <td><strong><?php echo htmlspecialchars($row['nama_produk']); ?></strong></td>
                            <td><?php echo htmlspecialchars($row['kategori'] ?? 'Tanpa Kategori'); ?></td>
                            <td>Rp <?php echo number_format($row['harga'], 0, ',', '.'); ?></td>
                            <td>
                                <span class="stock-badge <?php echo $stock_class; ?>">
                                    <?php echo $stock_text; ?>
                                </span>
                            </td>
                            <td>
                                <a href="edit_produk.php?id=<?php echo $row['id']; ?>" class="btn-edit">
                                    <i class="fa-solid fa-pen"></i> Edit
                                </a>
                                <a href="?hapus=<?php echo $row['id']; ?>" class="btn-delete"
                                   onclick="return confirm('Yakin ingin menghapus produk <?php echo addslashes($row['nama_produk']); ?>?')">
                                    <i class="fa-solid fa-trash"></i> Hapus
                                </a>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</body>
</html>
