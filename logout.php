<?php
session_start();

/* HAPUS SEMUA SESSION */
session_unset();
session_destroy();

/* KEMBALI KE LOGIN */
header("Location: login.php");
exit;