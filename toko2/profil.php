<?php
session_start();
include "../config/database.php";

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin'){
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Ambil data admin
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$admin = $stmt->get_result()->fetch_assoc();

// Profil update
if(isset($_POST['update_profil'])){
    $nama = trim($_POST['nama']);
    
    if(empty($nama)){
        $error = "Nama tidak boleh kosong";
    } else {
        $stmt_update = $conn->prepare("UPDATE users SET nama = ? WHERE id = ?");
        $stmt_update->bind_param("si", $nama, $user_id);
        if($stmt_update->execute()){
            $_SESSION['nama'] = $nama;
            $success = "Profil berhasil diperbarui!";
            // Refresh data
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $admin = $stmt->get_result()->fetch_assoc();
        } else {
            $error = "Gagal memperbarui profil";
        }
    }
}

// Ganti password
if(isset($_POST['ganti_password'])){
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if(empty($current_password) || empty($new_password) || empty($confirm_password)){
        $error = "Semua field password harus diisi";
    } elseif(strlen($new_password) < 6){
        $error = "Password baru minimal 6 karakter";
    } elseif($new_password != $confirm_password){
        $error = "Password baru dan konfirmasi tidak cocok";
    } else {
        if(password_verify($current_password, $admin['password'])){
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt_pass = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt_pass->bind_param("si", $hashed_password, $user_id);
            if($stmt_pass->execute()){
                $success = "Password berhasil diubah!";
            } else {
                $error = "Gagal mengubah password";
            }
        } else {
            $error = "Password saat ini salah";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Admin - AIC Fashion Metro</title>
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

        .avatar {
            width: 60px;
            height: 60px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            font-weight: 700;
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

        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 1.5px solid #e0e0e0;
            border-radius: 12px;
            font-size: 14px;
            outline: none;
        }

        .form-group input:focus {
            border-color: #EE6C4D;
        }

        .btn-submit {
            width: 100%;
            padding: 12px;
            background: #EE6C4D;
            color: white;
            border: none;
            border-radius: 40px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 10px;
        }

        .alert-error {
            background: #ffebee;
            color: #c62828;
            padding: 12px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-size: 13px;
        }

        .alert-success {
            background: #e8f5e9;
            color: #2e7d32;
            padding: 12px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-size: 13px;
        }

        hr {
            margin: 20px 0;
            border: none;
            border-top: 1px solid #eee;
        }

        .btn-back {
            display: block;
            text-align: center;
            color: #EE6C4D;
            text-decoration: none;
            margin-top: 15px;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <div class="avatar">
            <?php echo strtoupper(substr($admin['nama'], 0, 1)); ?>
        </div>
        <h2>
            <i class="fa-regular fa-user"></i> 
            Profil Admin
        </h2>
        <p><?php echo htmlspecialchars($admin['email']); ?></p>
    </div>

    <div class="form-body">
        <?php if($error): ?>
            <div class="alert-error"><i class="fa-solid fa-circle-exclamation"></i> <?php echo $error; ?></div>
        <?php endif; ?>
        <?php if($success): ?>
            <div class="alert-success"><i class="fa-regular fa-circle-check"></i> <?php echo $success; ?></div>
        <?php endif; ?>

        <!-- Edit Profil -->
        <form method="POST">
            <div class="form-group">
                <label><i class="fa-regular fa-user"></i> Nama</label>
                <input type="text" name="nama" value="<?php echo htmlspecialchars($admin['nama']); ?>" required>
            </div>
            <button type="submit" name="update_profil" class="btn-submit">Update Profil</button>
        </form>

        <hr>

        <!-- Ganti Password -->
        <form method="POST">
            <div class="form-group">
                <label><i class="fa-solid fa-lock"></i> Password Saat Ini</label>
                <input type="password" name="current_password" placeholder="Masukkan password lama" required>
            </div>
            <div class="form-group">
                <label><i class="fa-solid fa-key"></i> Password Baru</label>
                <input type="password" name="new_password" placeholder="Minimal 6 karakter" required>
            </div>
            <div class="form-group">
                <label><i class="fa-solid fa-check-circle"></i> Konfirmasi Password Baru</label>
                <input type="password" name="confirm_password" placeholder="Ulangi password baru" required>
            </div>
            <button type="submit" name="ganti_password" class="btn-submit">Ganti Password</button>
        </form>

        <hr>

        <a href="dashboard.php" class="btn-back">
            <i class="fa-solid fa-arrow-left"></i> Kembali ke Dashboard
        </a>
    </div>
</div>

</body>
</html>