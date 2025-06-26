<?php
session_start();
require 'koneksi.php'; // pastikan koneksi menggunakan mysqli dan variabel $conn

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    // Cek apakah email terdaftar
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        // Gunakan password_verify untuk membandingkan password
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['email'] = $user['email'];
            echo "<script>alert('Login berhasil!'); window.location.href='ProjectDengarku.html';</script>";
        } else {
            echo "<script>alert('Password salah!'); window.location.href='LoginDengarku.html';</script>";
        }
    } else {
        echo "<script>alert('Email tidak ditemukan!'); window.location.href='LoginDengarku.html';</script>";
    }

    $stmt->close();
    $conn->close();
}
?>
