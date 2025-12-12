<?php
// koneksi.php
// Konfigurasi koneksi database untuk CMS Guesthouse Adiputra

// Konfigurasi Database
define('DB_HOST', 'localhost');
define('DB_USER', 'adiputra');
define('DB_PASS', 'Aloevera21.');
define('DB_NAME', 'adiputra');

// Membuat koneksi
$koneksi = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Cek koneksi
if ($koneksi->connect_error) {
    die("Koneksi database gagal: " . $koneksi->connect_error);
}

// Set charset ke utf8mb4 untuk support emoji dan karakter khusus
$koneksi->set_charset("utf8mb4");

// Set timezone ke Asia/Jakarta
date_default_timezone_set('Asia/Jakarta');

?>
