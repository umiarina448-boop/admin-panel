<?php
session_start();
include "../config/database.php";

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin'){
    header("Location: ../login.php");
    exit;
}

$message = "";
$error = "";
$success = "";

if(isset($_POST['simpan'])){

    $nama = trim($_POST['nama']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validasi
    if(empty($nama)){
        $error = "Nama tidak boleh kosong";
    } elseif(empty($email)){
        $error = "Email tidak boleh kosong";
    } elseif(empty($password)){
        $error = "Password tidak boleh kosong";
    } elseif(strlen($password) < 6){
        $error = "Password minimal 6 karakter";
    } elseif($password != $confirm_password){
        $error = "Konfirmasi password tidak cocok";
    } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)){
        $error = "Format email tidak valid";
    } else {
        
        // Cek email sudah ada atau belum
        $stmt_cek = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt_cek->bind_param("s", $email);
        $stmt_cek->execute();
        $cek_result = $stmt_cek->get_result();
        
        if($cek_result->num_rows > 0){
            $error = "Email sudah digunakan! Silakan gunakan email lain.";
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert admin baru
            $stmt_insert = $conn->prepare("
                INSERT INTO users (nama, email, password, role, created_at) 
                VALUES (?, ?, ?, 'admin', NOW())
            ");
            $stmt_insert->bind_param("sss", $nama, $email, $hashed_password);
            
            if($stmt_insert->execute()){
                $success = "Admin baru berhasil ditambahkan!";
                // Redirect setelah 2 detik
                header("refresh:2; url=admin.php");
            } else {
                $error = "Gagal menambahkan admin. Silakan coba lagi.";
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
    <title>Tambah Admin - AIC Fashion</title>
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
            max-width: 500px;
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

        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 1.5px solid #e0e0e0;
            border-radius: 12px;
            font-size: 14px;
            outline: none;
            transition: 0.2s;
        }

        .form-group input:focus {
            border-color: #EE6C4D;
            box-shadow: 0 0 0 3px rgba(238,108,77,0.1);
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

        .info-text {
            font-size: 12px;
            color: #888;
            margin-top: 5px;
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
            <i class="fa-solid fa-user-plus"></i> 
            Tambah Admin Baru
        </h2>
        <p>Isi form di bawah untuk menambahkan admin baru</p>
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

        <form method="POST">
            <!-- Nama -->
            <div class="form-group">
                <label><i class="fa-regular fa-user"></i> Nama Lengkap</label>
                <input type="text" name="nama" placeholder="Masukkan nama lengkap" value="<?php echo isset($_POST['nama']) ? htmlspecialchars($_POST['nama']) : ''; ?>" required>
            </div>

            <!-- Email -->
            <div class="form-group">
                <label><i class="fa-regular fa-envelope"></i> Email</label>
                <input type="email" name="email" placeholder="admin@example.com" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
            </div>

            <!-- Password -->
            <div class="form-group">
                <label><i class="fa-solid fa-lock"></i> Password</label>
                <input type="password" name="password" placeholder="Minimal 6 karakter" required>
                <div class="info-text">
                    <i class="fa-regular fa-circle-info"></i> Password minimal 6 karakter
                </div>
            </div>

            <!-- Konfirmasi Password -->
            <div class="form-group">
                <label><i class="fa-solid fa-check-circle"></i> Konfirmasi Password</label>
                <input type="password" name="confirm_password" placeholder="Ulangi password" required>
            </div>

            <button type="submit" name="simpan" class="btn-submit">
                <i class="fa-regular fa-floppy-disk"></i> Simpan Admin
            </button>

            <hr>

            <a href="admin.php" class="btn-back">
                <i class="fa-solid fa-arrow-left"></i> Kembali ke Data Admin
            </a>
        </form>
    </div>
</div>

</body>
</html>