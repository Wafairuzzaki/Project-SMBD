<?php
$host = "localhost";
$user = "root"; // Sesuaikan dengan username DB Anda
$pass = "";     // Sesuaikan dengan password DB Anda
$dbname = "manajemen_bank1";

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}
?>