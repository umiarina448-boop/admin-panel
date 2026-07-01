<?php

$conn = mysqli_connect("localhost","root","","aic_fashion");

if(!$conn){
    die("Koneksi gagal : " . mysqli_connect_error());
}

?>