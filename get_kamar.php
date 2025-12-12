<?php
// get_kamar.php
// API endpoint untuk mendapatkan daftar kamar berdasarkan properti (AJAX)

header('Content-Type: application/json');

require_once 'koneksi.php';

$id_properti = isset($_GET['properti']) ? (int)$_GET['properti'] : 0;

if ($id_properti > 0) {
    $query = "SELECT k.id_kamar, k.nama_kamar, k.harga_default, p.nama_properti 
              FROM tbl_kamar k 
              JOIN tbl_properti p ON k.id_properti = p.id_properti 
              WHERE k.id_properti = $id_properti AND k.status = 'Tersedia'
              ORDER BY k.nama_kamar";
    
    $result = $koneksi->query($query);
    $kamar_list = [];
    
    while ($row = $result->fetch_assoc()) {
        $kamar_list[] = $row;
    }
    
    echo json_encode($kamar_list);
} else {
    echo json_encode([]);
}

$koneksi->close();
?>

