<?php
session_start();
include "config/database.php";

$message = "";

if(isset($_POST['login'])){
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];

    // Menggunakan prepared statement untuk keamanan (mencegah SQL Injection)
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if($result->num_rows > 0){
        $user = $result->fetch_assoc();

        if(password_verify($password, $user['password'])){
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['nama'] = $user['nama'];
            $_SESSION['role'] = $user['role'];

            if($user['role'] == 'admin'){
                header("Location: dashboard.php");
                exit;
            } else {
                header("Location: ../customer/dashboard.php");
                exit;
            }
        } else {
            $message = "Password salah!";
        }
    } else {
        $message = "Email tidak ditemukan!";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - AIC Fashion</title>
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
            background: linear-gradient(135deg, #FFF8F5 0%, #FFE8E0 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .back-btn {
            position: absolute;
            top: 25px;
            left: 25px;
            width: 45px;
            height: 45px;
            background: white;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            text-decoration: none;
            color: #EE6C4D;
            box-shadow: 0 5px 15px rgba(0,0,0,.1);
            transition: 0.3s;
        }

        .back-btn:hover {
            background: #EE6C4D;
            color: white;
            transform: translateX(-3px);
        }

        .container {
            width: 420px;
            max-width: 100%;
            background: white;
            padding: 40px 35px;
            border-radius: 24px;
            box-shadow: 0 20px 40px rgba(0,0,0,.08);
            transition: 0.3s;
        }

        .logo {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo h1 {
            color: #EE6C4D;
            margin-bottom: 8px;
            font-size: 28px;
        }

        .logo p {
            color: #888;
            font-size: 14px;
        }

        .input-group {
            margin-bottom: 20px;
        }

        .input-group label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            font-weight: 500;
            color: #333;
        }

        .input-group input {
            width: 100%;
            padding: 14px 16px;
            border: 1.5px solid #e0e0e0;
            border-radius: 12px;
            outline: none;
            font-size: 14px;
            transition: 0.2s;
        }

        .input-group input:focus {
            border-color: #EE6C4D;
            box-shadow: 0 0 0 3px rgba(238,108,77,0.1);
        }

        .btn {
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 12px;
            background: #EE6C4D;
            color: white;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.2s;
        }

        .btn:hover {
            background: #d95a3c;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(238,108,77,0.3);
        }

        .error {
            background: #ffe3e3;
            color: #d32f2f;
            padding: 12px 16px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-size: 14px;
            border-left: 4px solid #d32f2f;
        }

        .register-link {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
            color: #666;
        }

        .register-link a {
            color: #EE6C4D;
            text-decoration: none;
            font-weight: 600;
            margin-left: 5px;
        }

        .register-link a:hover {
            text-decoration: underline;
        }

        @media (max-width: 480px) {
            .container {
                padding: 30px 25px;
            }
            .back-btn {
                top: 15px;
                left: 15px;
                width: 40px;
                height: 40px;
            }
        }
    </style>
</head>
<body>
    <a href="dashboard.php" class="back-btn">
        <i class="fa-solid fa-chevron-left"></i>
    </a>

    <div class="container">
        <div class="logo">
            <h1>🛍 AIC Fashion</h1>
            <p>Masuk ke akunmu</p>
        </div>

        <?php if($message != ""){ ?>
            <div class="error">
                <i class="fa-solid fa-circle-exclamation" style="margin-right: 8px;"></i>
                <?php echo $message; ?>
            </div>
        <?php } ?>

        <form method="POST">
            <div class="input-group">
                <label>Email</label>
                <input type="email" name="email" placeholder="contoh@email.com" required>
            </div>

            <div class="input-group">
                <label>Password</label>
                <input type="password" name="password" placeholder="Masukkan password" required>
            </div>

            <button type="submit" name="login" class="btn">
                <i class="fa-solid fa-arrow-right-to-bracket" style="margin-right: 8px;"></i>
                Masuk
            </button>
        </form>

        <div class="register-link">
            Belum punya akun?
            <a href="register.php">Daftar Sekarang</a>
        </div>
    </div>
</body>
</html>
