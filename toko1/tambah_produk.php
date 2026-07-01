<?php
session_start();
include "../config/database.php";

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin'){
    header("Location: ../login.php");
    exit;
}

// AMBIL KATEGORI
$kategori = mysqli_query($conn, "SELECT * FROM categories ORDER BY nama_kategori");

if(isset($_POST['simpan'])){

    // Sanitasi input
    $nama = trim($_POST['nama']);
    $deskripsi = trim($_POST['deskripsi']);
    $harga = (int)$_POST['harga'];
    $stok = (int)$_POST['stok'];
    $category_id = (int)$_POST['category_id'];
    
    $error = '';
    $success = '';

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
        
        // Upload gambar
        $gambar = "";
        
        if($_FILES['gambar']['name'] != ""){
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];
            $file_ext = strtolower(pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION));
            
            if(!in_array($file_ext, $allowed)){
                $error = "Format gambar harus JPG, JPEG, PNG, atau WEBP";
            } elseif($_FILES['gambar']['size'] > 2000000){
                $error = "Ukuran gambar maksimal 2MB";
            } else {
                $gambar = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $_FILES['gambar']['name']);
                $tmp = $_FILES['gambar']['tmp_name'];
                $folder = "../uploads/";
                
                if(!file_exists($folder)){
                    mkdir($folder, 0777, true);
                }
                
                if(move_uploaded_file($tmp, $folder . $gambar)){
                    // Sukses upload
                } else {
                    $error = "Gagal mengupload gambar";
                }
            }
        } else {
            $error = "Gambar produk wajib diisi";
        }
        
        // Jika tidak ada error, simpan ke database
        if(empty($error)){
            // Gunakan prepared statement
            $stmt = $conn->prepare("
                INSERT INTO products (category_id, nama_produk, deskripsi, harga, stok, gambar, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->bind_param("issiis", $category_id, $nama, $deskripsi, $harga, $stok, $gambar);
            
            if($stmt->execute()){
                $success = "Produk berhasil ditambahkan!";
                // Redirect setelah 2 detik
                header("refresh:2; url=produk.php");
            } else {
                $error = "Gagal menyimpan data: " . $conn->error;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Produk - Admin AIC Fashion</title>
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
            max-width: 550px;
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

        /* HEADER */
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

        /* FORM BODY */
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

        /* FILE UPLOAD */
        .file-upload {
            border: 2px dashed #e0e0e0;
            border-radius: 16px;
            padding: 20px;
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
            font-size: 40px;
            color: #ccc;
            margin-bottom: 10px;
        }

        .file-upload p {
            color: #999;
            font-size: 13px;
        }

        .file-upload .file-name {
            margin-top: 10px;
            font-size: 12px;
            color: #EE6C4D;
        }

        input[type="file"] {
            display: none;
        }

        /* ALERT */
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

        /* BUTTON */
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
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h2>
            <i class="fa-solid fa-plus-circle"></i> 
            Tambah Produk Baru
        </h2>
        <p>Lengkapi form di bawah untuk menambahkan produk</p>
    </div>

    <div class="form-body">
        <?php if(isset($error) && $error != ''): ?>
            <div class="alert-error">
                <i class="fa-solid fa-circle-exclamation"></i>
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <?php if(isset($success) && $success != ''): ?>
            <div class="alert-success">
                <i class="fa-regular fa-circle-check"></i>
                <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" id="productForm">
            <!-- Nama Produk -->
            <div class="form-group">
                <label><i class="fa-regular fa-tag"></i> Nama Produk</label>
                <input type="text" name="nama" placeholder="Contoh: Hijab Pashmina Premium" required>
            </div>

            <!-- Deskripsi -->
            <div class="form-group">
                <label><i class="fa-regular fa-align-left"></i> Deskripsi</label>
                <textarea name="deskripsi" placeholder="Deskripsikan produk secara lengkap..."></textarea>
            </div>

            <!-- Harga -->
            <div class="form-group">
                <label><i class="fa-regular fa-money-bill-1"></i> Harga (Rp)</label>
                <input type="number" name="harga" placeholder="100000" required>
            </div>

            <!-- Stok -->
            <div class="form-group">
                <label><i class="fa-regular fa-box"></i> Stok</label>
                <input type="number" name="stok" placeholder="Jumlah stok tersedia" required>
            </div>

            <!-- Kategori -->
            <div class="form-group">
                <label><i class="fa-regular fa-folder"></i> Kategori</label>
                <select name="category_id" required>
                    <option value="">-- Pilih Kategori --</option>
                    <?php while($row = mysqli_fetch_assoc($kategori)){ ?>
                        <option value="<?php echo $row['id']; ?>">
                            <?php echo htmlspecialchars($row['nama_kategori']); ?>
                        </option>
                    <?php } ?>
                </select>
            </div>

            <!-- Upload Gambar -->
            <div class="form-group">
                <label><i class="fa-regular fa-image"></i> Gambar Produk</label>
                <div class="file-upload" onclick="document.getElementById('gambarInput').click()">
                    <i class="fa-regular fa-cloud-arrow-up"></i>
                    <p>Klik untuk upload gambar</p>
                    <p style="font-size: 11px;">Format: JPG, PNG, WEBP (Max 2MB)</p>
                    <div class="file-name" id="fileName"></div>
                </div>
                <input type="file" name="gambar" id="gambarInput" accept="image/jpeg,image/png,image/webp" required>
            </div>

            <button type="submit" name="simpan" class="btn-submit">
                <i class="fa-regular fa-floppy-disk"></i> Simpan Produk
            </button>

            <hr>

            <a href="produk.php" class="btn-back">
                <i class="fa-solid fa-arrow-left"></i> Kembali ke Kelola Produk
            </a>
        </form>
    </div>
</div>

<script>
// Tampilkan nama file yang dipilih
const fileInput = document.getElementById('gambarInput');
const fileName = document.getElementById('fileName');

fileInput.addEventListener('change', function() {
    if(this.files && this.files[0]) {
        fileName.innerHTML = '<i class="fa-regular fa-image"></i> ' + this.files[0].name;
    } else {
        fileName.innerHTML = '';
    }
});

// Validasi harga tidak boleh 0
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