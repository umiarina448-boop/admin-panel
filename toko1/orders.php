<?php
session_start();
include "../config/database.php";

if($_SESSION['role'] != 'admin'){
    header("Location: ../login.php");
    exit;
}

$data = mysqli_query($conn,"
SELECT orders.*, users.nama 
FROM orders 
JOIN users ON orders.user_id = users.id
ORDER BY orders.id DESC
");
?>

<!DOCTYPE html>
<html>
<head>
<title>Admin Orders</title>

<style>
body{
font-family:Poppins;
background:#f5f5f5;
margin:0;
}

.container{
max-width:1000px;
margin:30px auto;
}

.card{
background:white;
padding:15px;
margin-bottom:15px;
border-radius:12px;
box-shadow:0 3px 10px rgba(0,0,0,.08);
}

.btn{
padding:6px 10px;
border:none;
border-radius:6px;
cursor:pointer;
margin-right:5px;
}

.proses{background:orange;color:white;}
.kirim{background:blue;color:white;}
.selesai{background:green;color:white;}

.badge{
padding:5px 10px;
border-radius:6px;
color:white;
font-size:12px;
}

</style>

</head>
<body>

<div class="container">

<h2>📦 Admin Orders</h2>

<?php while($row = mysqli_fetch_assoc($data)){ ?>

<div class="card">

<h3>Order #<?php echo $row['id']; ?> - <?php echo $row['nama']; ?></h3>

<p>Total: Rp <?php echo number_format($row['total_harga']); ?></p>

<p>
Status: 
<span class="badge" style="background:#EE6C4D;">
<?php echo $row['tracking_status']; ?>
</span>
</p>

<!-- BUTTON STATUS -->
<a href="update_status.php?id=<?php echo $row['id']; ?>&status=diproses">
<button class="btn proses">Diproses</button>
</a>

<a href="update_status.php?id=<?php echo $row['id']; ?>&status=dikirim">
<button class="btn kirim">Dikirim</button>
</a>

<a href="update_status.php?id=<?php echo $row['id']; ?>&status=selesai">
<button class="btn selesai">Selesai</button>
</a>

</div>

<?php } ?>

</div>

</body>
</html>