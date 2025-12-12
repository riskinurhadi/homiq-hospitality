<?php
// auth_check.php
// File untuk mengecek apakah user sudah login
// Include file ini di setiap halaman yang memerlukan autentikasi

session_start();

// Cek apakah user sudah login
if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
    // Jika belum login, redirect ke halaman login
    header("Location: login.php");
    exit();
}

// Simpan data user dari session untuk digunakan di halaman
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$nama_lengkap = $_SESSION['nama_lengkap'] ?? 'User';
$role_user = $_SESSION['role'] ?? 'guest';

?>
