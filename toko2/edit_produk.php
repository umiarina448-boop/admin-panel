<?php
session_start();
include "../config/database.php";

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin'){
    header("Location: ../login.php");
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if($id == 0){
    header("Location: produk.php");
    exit;
}

// Ambil data produk dengan prepared statement
$stmt_produk = $conn->prepare("SELECT * FROM products WHERE id = ?");
$stmt_produk->bind_param("i", $id);
$stmt_produk->execute();
$produk = $stmt_produk->get_result()->fetch_assoc();

if(!$produk){
    header("Location: produk.php");
    exit;
}

// Ambil kategori
$kategori = mysqli_query($conn, "SELECT * FROM categories ORDER BY nama_kategori");

// Ambil size lama
$size_data = [];
$stmt_size = $conn->prepare("SELECT ukuran FROM product_sizes WHERE product_id = ?");
$stmt_size->bind_param("i", $id);
$stmt_size->execute();
$sizes = $stmt_size->get_result();

while($s = $sizes->fetch_assoc()){
    $size_data[] = $s['ukuran'];
}

$error = '';
$success = '';

// Proses update
if(isset($_POST['update'])){

    $nama = trim($_POST['nama']);
    $harga = (int)$_POST['harga'];
    $stok = (int)$_POST['stok'];
    $category_id = (int)$_POST['category_id'];
    $deskripsi = trim($_POST['deskripsi']);
    
    // Validasi
    if(empty($nama)){
        $error = "Nama produk tidak boleh kosong";
    } elseif($harga <= 0){
        $error = "Harga harus lebih dari 0";
    } elseif($stok < 0){
        $error = "Stok tidak boleh negatif";
    } elseif($category_id <= 0){
        $error = "Pilih kategori terlebih dahulu";
    } else {
        
        // Update gambar jika ada file baru
        $gambar = $produk['gambar'];
        
        if($_FILES['gambar']['name'] != ""){
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];
            $file_ext = strtolower(pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION));
            
            if(!in_array($file_ext, $allowed)){
                $error = "Format gambar harus JPG, JPEG, PNG, atau WEBP";
            } elseif($_FILES['gambar']['size'] > 2000000){
                $error = "Ukuran gambar maksimal 2MB";
            } else {
                // Hapus gambar lama
                if($gambar && file_exists("/app/uploads/".$gambar)){
                    unlink("/app/uploads/".$gambar);
                }
                
                $gambar = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $_FILES['gambar']['name']);
                move_uploaded_file($_FILES['gambar']['tmp_name'], "/app/uploads/".$gambar);
            }
        }
        
        if(empty($error)){
            // Update produk dengan prepared statement
            $stmt_update = $conn->prepare("
                UPDATE products SET 
                    nama_produk = ?,
                    harga = ?,
                    stok = ?,
                    category_id = ?,
                    deskripsi = ?,
                    gambar = ?
                WHERE id = ?
            ");
            $stmt_update->bind_param("siiissi", $nama, $harga, $stok, $category_id, $deskripsi, $gambar, $id);
            
            if($stmt_update->execute()){
                // Reset size
                $stmt_del = $conn->prepare("DELETE FROM product_sizes WHERE product_id = ?");
                $stmt_del->bind_param("i", $id);
                $stmt_del->execute();
                
                // Insert size baru
                if(!empty($_POST['size'])){
                    $stmt_insert = $conn->prepare("INSERT INTO product_sizes (product_id, ukuran) VALUES (?, ?)");
                    foreach($_POST['size'] as $ukuran){
                        $stmt_insert->bind_param("is", $id, $ukuran);
                        $stmt_insert->execute();
                    }
                }
                
                $success = "Produk berhasil diupdate!";
                // Refresh data
                $stmt_produk->bind_param("i", $id);
                $stmt_produk->execute();
                $produk = $stmt_produk->get_result()->fetch_assoc();
                
                // Refresh size data
                $size_data = [];
                $stmt_size->bind_param("i", $id);
                $stmt_size->execute();
                $sizes = $stmt_size->get_result();
                while($s = $sizes->fetch_assoc()){
                    $size_data[] = $s['ukuran'];
                }
            } else {
                $error = "Gagal mengupdate data";
            }
        }
    }
}

$list_size = ["S", "M", "L", "XL", "XXL"];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Produk - Admin AIC Fashion</title>
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
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .container {
            max-width: 600px;
            width: 100%;
            background: white;
            border-radius: 24px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            overflow: hidden;
            animation: fadeInUp 0.4s ease;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .header {
            background: linear-gradient(135deg, #EE6C4D, #ff8a65);
            padding: 25px 30px;
            color: white;
        }

        .header h2 {
            font-size: 22px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .header p {
            font-size: 13px;
            opacity: 0.9;
            margin-top: 5px;
        }

        .form-body {
            padding: 30px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #333;
            font-size: 14px;
        }

        .form-group label i {
            color: #EE6C4D;
            width: 20px;
            margin-right: 5px;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 1.5px solid #e0e0e0;
            border-radius: 12px;
            font-size: 14px;
            outline: none;
            transition: 0.2s;
            font-family: 'Poppins', sans-serif;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: #EE6C4D;
            box-shadow: 0 0 0 3px rgba(238,108,77,0.1);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }

        .size-group {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            margin-top: 10px;
        }

        .size-option {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
        }

        .size-option input {
            width: 18px;
            height: 18px;
            accent-color: #EE6C4D;
            cursor: pointer;
        }

        .size-option span {
            font-size: 14px;
            font-weight: 500;
            color: #555;
        }

        .current-image {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }

        .current-image img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 12px;
            background: #f5f5f5;
        }

        .current-image p {
            font-size: 13px;
            color: #666;
        }

        .file-upload {
            border: 2px dashed #e0e0e0;
            border-radius: 16px;
            padding: 15px;
            text-align: center;
            cursor: pointer;
            transition: 0.2s;
            background: #fafafa;
        }

        .file-upload:hover {
            border-color: #EE6C4D;
            background: #fff5f2;
        }

        .file-upload i {
            font-size: 30px;
            color: #ccc;
            margin-bottom: 8px;
        }

        .file-upload p {
            color: #999;
            font-size: 12px;
        }

        .file-upload .file-name {
            margin-top: 8px;
            font-size: 12px;
            color: #EE6C4D;
        }

        input[type="file"] {
            display: none;
        }

        .alert-error {
            background: #ffebee;
            color: #c62828;
            padding: 12px 16px;
            border-radius: 12px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 13px;
        }

        .alert-success {
            background: #e8f5e9;
            color: #2e7d32;
            padding: 12px 16px;
            border-radius: 12px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 13px;
        }

        .btn-submit {
            width: 100%;
            padding: 14px;
            background: #EE6C4D;
            color: white;
            border: none;
            border-radius: 40px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-submit:hover {
            background: #d95a3c;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(238,108,77,0.3);
        }

        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: none;
            border: none;
            color: #EE6C4D;
            text-decoration: none;
            font-size: 14px;
            margin-top: 15px;
            cursor: pointer;
        }

        .btn-back:hover {
            text-decoration: underline;
        }

        hr {
            margin: 20px 0;
            border: none;
            border-top: 1px solid #eee;
        }

        @media (max-width: 550px) {
            .form-body {
                padding: 20px;
            }
            .header {
                padding: 20px;
            }
            .size-group {
                gap: 10px;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h2>
            <i class="fa-solid fa-pen-to-square"></i> 
            Edit Produk
        </h2>
        <p>Ubah informasi produk yang ingin diperbarui</p>
    </div>

    <div class="form-body">
        <?php if($error != ''): ?>
            <div class="alert-error">
                <i class="fa-solid fa-circle-exclamation"></i>
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <?php if($success != ''): ?>
            <div class="alert-success">
                <i class="fa-regular fa-circle-check"></i>
                <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" id="productForm">
            <div class="form-group">
                <label><i class="fa-regular fa-tag"></i> Nama Produk</label>
                <input type="text" name="nama" value="<?php echo htmlspecialchars($produk['nama_produk']); ?>" required>
            </div>

            <div class="form-group">
                <label><i class="fa-regular fa-align-left"></i> Deskripsi</label>
                <textarea name="deskripsi" placeholder="Deskripsikan produk secara lengkap..."><?php echo htmlspecialchars($produk['deskripsi'] ?? ''); ?></textarea>
            </div>

            <div class="form-group">
                <label><i class="fa-regular fa-money-bill-1"></i> Harga (Rp)</label>
                <input type="number" name="harga" value="<?php echo $produk['harga']; ?>" required>
            </div>

            <div class="form-group">
                <label><i class="fa-regular fa-box"></i> Stok</label>
                <input type="number" name="stok" value="<?php echo $produk['stok']; ?>" required>
            </div>

            <div class="form-group">
                <label><i class="fa-regular fa-ruler"></i> Ukuran (Size)</label>
                <div class="size-group">
                    <?php foreach($list_size as $sz): ?>
                        <label class="size-option">
                            <input type="checkbox" name="size[]" value="<?php echo $sz; ?>"
                                <?php if(in_array($sz, $size_data)) echo "checked"; ?>>
                            <span><?php echo $sz; ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
                <small style="color:#999; font-size:11px;">Centang ukuran yang tersedia</small>
            </div>

            <div class="form-group">
                <label><i class="fa-regular fa-folder"></i> Kategori</label>
                <select name="category_id" required>
                    <?php while($row = mysqli_fetch_assoc($kategori)){ ?>
                        <option value="<?php echo $row['id']; ?>"
                            <?php if($row['id'] == $produk['category_id']) echo "selected"; ?>>
                            <?php echo htmlspecialchars($row['nama_kategori']); ?>
                        </option>
                    <?php } ?>
                </select>
            </div>

            <div class="form-group">
                <label><i class="fa-regular fa-image"></i> Gambar Saat Ini</label>
                <div class="current-image">
                    <img src="../uploads/<?php echo htmlspecialchars($produk['gambar']); ?>" 
                         alt="<?php echo htmlspecialchars($produk['nama_produk']); ?>"
                         onerror="this.src='https://placehold.co/200x200?text=No+Image'">
                    <p><i class="fa-regular fa-info-circle"></i> Kosongkan jika tidak ingin mengubah gambar</p>
                </div>
            </div>

            <div class="form-group">
                <label><i class="fa-regular fa-cloud-arrow-up"></i> Ganti Gambar (Opsional)</label>
                <div class="file-upload" onclick="document.getElementById('gambarInput').click()">
                    <i class="fa-regular fa-cloud-arrow-up"></i>
                    <p>Klik untuk upload gambar baru</p>
                    <p style="font-size: 11px;">Format: JPG, PNG, WEBP (Max 2MB)</p>
                    <div class="file-name" id="fileName"></div>
                </div>
                <input type="file" name="gambar" id="gambarInput" accept="image/jpeg,image/png,image/webp">
            </div>

            <button type="submit" name="update" class="btn-submit">
                <i class="fa-regular fa-floppy-disk"></i> Update Produk
            </button>

            <hr>

            <a href="produk.php" class="btn-back">
                <i class="fa-solid fa-arrow-left"></i> Kembali ke Kelola Produk
            </a>
        </form>
    </div>
</div>

<script>
const fileInput = document.getElementById('gambarInput');
const fileName = document.getElementById('fileName');

fileInput.addEventListener('change', function() {
    if(this.files && this.files[0]) {
        fileName.innerHTML = '<i class="fa-regular fa-image"></i> ' + this.files[0].name;
    } else {
        fileName.innerHTML = '';
    }
});

document.getElementById('productForm').addEventListener('submit', function(e) {
    const harga = document.querySelector('input[name="harga"]').value;
    const stok = document.querySelector('input[name="stok"]').value;
    
    if(harga <= 0) {
        e.preventDefault();
        alert('Harga harus lebih dari 0');
    }
    
    if(stok < 0) {
        e.preventDefault();
        alert('Stok tidak boleh negatif');
    }
});
</script>

</body>
</html>
