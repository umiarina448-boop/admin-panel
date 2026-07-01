<?php
include "../config/database.php";

$id = $_GET['id'];
$status = $_GET['status'];

mysqli_query($conn,"
UPDATE orders 
SET tracking_status='$status'
WHERE id='$id'
");

header("Location: orders.php");
exit;
?>