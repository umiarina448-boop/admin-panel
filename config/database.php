<?php

$mysql_url = $_ENV['MYSQL_URL'] ?? getenv('MYSQL_URL');

if ($mysql_url) {
    $parts = parse_url($mysql_url);
    $host  = $parts['host'];
    $user  = $parts['user'];
    $pass  = $parts['pass'];
    $db    = ltrim($parts['path'], '/');
    $port  = $parts['port'] ?? 3306;
} else {
    $host = 'localhost';
    $user = 'root';
    $pass = '';
    $db   = 'aic_fashion';
    $port = 3306;
}

$conn = new mysqli($host, $user, $pass, $db, (int)$port);

if ($conn->connect_error) {
    die('Koneksi gagal: ' . $conn->connect_error);
}

$conn->set_charset('utf8mb4');
