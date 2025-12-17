<?php
session_start();
include 'koneksi.php';
include 'auth_check.php';

// Pastikan ini adalah request POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: checklist_kamar.php');
    exit();
}

// Ambil data dari form
$id_kamar = $_POST['id_kamar'];
$id_user = $_POST['id_user'];
$status_kamar = $_POST['status_kamar'];
$catatan_umum = $_POST['catatan_umum'];
$checklist_statuses = $_POST['status'];
$checklist_catatan = $_POST['catatan'];

// Validasi dasar
if (empty($id_kamar) || empty($id_user) || empty($status_kamar) || empty($checklist_statuses)) {
    // Simpan pesan error di session dan redirect
    $_SESSION['flash_message'] = [
        'type' => 'danger',
        'message' => 'Data tidak lengkap. Silakan coba lagi.'
    ];
    header('Location: form_checklist.php?id_kamar=' . $id_kamar);
    exit();
}

// Mulai transaksi
mysqli_begin_transaction($conn);

try {
    // 1. Insert ke tbl_checklist_runs
    $query_run = "INSERT INTO tbl_checklist_runs (id_kamar, id_user, final_status_kamar, catatan_umum) VALUES (?, ?, ?, ?)";
    $stmt_run = mysqli_prepare($conn, $query_run);
    mysqli_stmt_bind_param($stmt_run, 'iiss', $id_kamar, $id_user, $status_kamar, $catatan_umum);
    
    if (!mysqli_stmt_execute($stmt_run)) {
        throw new Exception("Gagal menyimpan data checklist utama: " . mysqli_stmt_error($stmt_run));
    }
    
    $id_run = mysqli_insert_id($conn);
    mysqli_stmt_close($stmt_run);

    // 2. Insert setiap item ke tbl_checklist_run_items
    $query_item = "INSERT INTO tbl_checklist_run_items (id_run, item_key, item_status, catatan_item) VALUES (?, ?, ?, ?)";
    $stmt_item = mysqli_prepare($conn, $query_item);

    foreach ($checklist_statuses as $key => $status) {
        $catatan = isset($checklist_catatan[$key]) ? $checklist_catatan[$key] : '';
        mysqli_stmt_bind_param($stmt_item, 'isss', $id_run, $key, $status, $catatan);
        
        if (!mysqli_stmt_execute($stmt_item)) {
            throw new Exception("Gagal menyimpan item checklist '$key': " . mysqli_stmt_error($stmt_item));
        }
    }
    mysqli_stmt_close($stmt_item);
    
    // 3. Update status di tbl_kamar
    $query_update_kamar = "UPDATE tbl_kamar SET status = ? WHERE id_kamar = ?";
    $stmt_update_kamar = mysqli_prepare($conn, $query_update_kamar);
    mysqli_stmt_bind_param($stmt_update_kamar, 'si', $status_kamar, $id_kamar);

    if (!mysqli_stmt_execute($stmt_update_kamar)) {
        throw new Exception("Gagal mengupdate status kamar: " . mysqli_stmt_error($stmt_update_kamar));
    }
    mysqli_stmt_close($stmt_update_kamar);

    // Jika semua berhasil, commit transaksi
    mysqli_commit($conn);

    $_SESSION['flash_message'] = [
        'type' => 'success',
        'message' => 'Checklist untuk kamar berhasil disimpan dan status kamar telah diperbarui.'
    ];

} catch (Exception $e) {
    // Jika ada error, rollback transaksi
    mysqli_rollback($conn);

    $_SESSION['flash_message'] = [
        'type' => 'danger',
        'message' => 'Terjadi kesalahan: ' . $e->getMessage()
    ];
    
    // Redirect kembali ke form jika ada error
    header('Location: form_checklist.php?id_kamar=' . $id_kamar);
    exit();
}

// Redirect ke halaman utama checklist
header('Location: checklist_kamar.php');
exit();

?>
