<?php
// Koneksi dengan MySQLi (Object-oriented)
$host = "localhost";
$user = "root";
$pass = "";
$db   = "loginmulti";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Koneksi MySQLi gagal: " . $conn->connect_error);
}

// Koneksi dengan PDO
$charset = 'utf8mb4';
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    die("Koneksi PDO gagal: " . $e->getMessage());
}
?>
