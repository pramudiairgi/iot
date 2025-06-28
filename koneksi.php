<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "iot_tampilan";

$conn = new mysqli($host, $user, $pass, $db);

// Cek koneksi
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Set charset UTF-8
$conn->set_charset("utf8");
?>
