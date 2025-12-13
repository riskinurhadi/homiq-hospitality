<?php
// upload_identitas.php
// Endpoint untuk upload/capture foto identitas tamu saat check-in

// Selalu mulai dengan output buffering untuk menangkap error tak terduga
ob_start();

require_once 'auth_check.php';
require_once 'koneksi.php';

// Atur header default sebagai JSON
header('Content-Type: application/json');

// Fungsi untuk kirim response dan exit
function sendResponse($isOk, $message, $data = [], $httpCode = 200) {
    // Hapus semua output yang mungkin sudah ada sebelum response ini
    ob_end_clean();

    // Atur kode status HTTP
    http_response_code($httpCode);

    // Kirim response JSON
    echo json_encode([
        'ok' => $isOk,
        'message' => $message,
        'data' => $data
    ]);
    exit();
}

// Global error handler untuk menangkap fatal error
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR])) {
        // Jika header belum terkirim, kirim response error
        if (!headers_sent()) {
            // Hapus output yang setengah jalan
            ob_end_clean();
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode([
                'ok' => false,
                'message' => 'Terjadi error internal pada server. Silakan hubungi administrator.',
                'detail' => $error['message'] // Hanya untuk debug, bisa dihapus di produksi
            ]);
        }
    }
});


// 1. Validasi Role User
if (!in_array($role_user, ['admin', 'front_office'])) {
    sendResponse(false, 'Akses ditolak. Anda tidak memiliki izin.', [], 403);
}

// 2. Validasi Input POST
$id_reservasi = isset($_POST['id_reservasi']) ? (int)$_POST['id_reservasi'] : 0;
if ($id_reservasi <= 0) {
    sendResponse(false, 'ID Reservasi tidak valid atau tidak ditemukan.', [], 400);
}

// 3. Validasi File Upload
if (!isset($_FILES['foto']) || !is_uploaded_file($_FILES['foto']['tmp_name'])) {
    sendResponse(false, 'Tidak ada file yang diunggah atau terjadi kesalahan upload.', [], 400);
}

// Cek error spesifik dari PHP
$uploadError = $_FILES['foto']['error'];
if ($uploadError !== UPLOAD_ERR_OK) {
    $errorMessages = [
        UPLOAD_ERR_INI_SIZE   => 'File terlalu besar (melebihi setting server).',
        UPLOAD_ERR_FORM_SIZE  => 'File terlalu besar (melebihi setting form).',
        UPLOAD_ERR_PARTIAL    => 'File hanya terunggah sebagian.',
        UPLOAD_ERR_NO_FILE    => 'Tidak ada file yang diunggah.',
        UPLOAD_ERR_NO_TMP_DIR => 'Folder temporary tidak ditemukan di server.',
        UPLOAD_ERR_CANT_WRITE => 'Gagal menulis file ke disk.',
        UPLOAD_ERR_EXTENSION  => 'Ekstensi PHP menghentikan proses upload file.',
    ];
    $message = $errorMessages[$uploadError] ?? 'Terjadi kesalahan upload yang tidak diketahui.';
    sendResponse(false, $message, [], 500);
}


// 4. Persiapan Direktori Upload
$targetDir = 'uploads/identitas/';

// Coba buat direktori jika belum ada
if (!is_dir($targetDir)) {
    //
    if (!mkdir($targetDir, 0775, true)) {
        // Jika gagal, kirim pesan error yang jelas
        sendResponse(false, 'Gagal membuat direktori penyimpanan. Hubungi administrator.', [], 500);
    }
}

// Cek lagi apakah direktori bisa ditulis
if (!is_writable($targetDir)) {
    sendResponse(false, 'Direktori penyimpanan tidak bisa ditulis. Hubungi administrator.', [], 500);
}


// 5. Proses Pemindahan File
$fileInfo = pathinfo($_FILES['foto']['name']);
$extension = strtolower($fileInfo['extension'] ?? 'jpg');
$allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];

if (!in_array($extension, $allowedExtensions)) {
    sendResponse(false, 'Format file tidak diizinkan. Hanya (JPG, PNG, WEBP, PDF).', [], 415);
}

$newFileName = 'reservasi_' . $id_reservasi . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
$destination = $targetDir . $newFileName;
$relativePath = 'uploads/identitas/' . $newFileName;


if (!move_uploaded_file($_FILES['foto']['tmp_name'], $destination)) {
    sendResponse(false, 'Gagal memindahkan file yang diunggah. Periksa perizinan server.', [], 500);
}

// 6. Buat tabel jika belum ada (opsional, tapi baik untuk robust-ness)
$koneksi->query("CREATE TABLE IF NOT EXISTS `tbl_reservasi_identitas` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `id_reservasi` INT NOT NULL,
    `file_path` VARCHAR(255) NOT NULL,
    `uploaded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (`id_reservasi`)
) ENGINE=InnoDB;");


// 7. Simpan Path ke Database
$stmt = $koneksi->prepare("INSERT INTO tbl_reservasi_identitas (id_reservasi, file_path) VALUES (?, ?)");
if ($stmt === false) {
    // Hapus file yang sudah terlanjur di-upload jika db-insert gagal
    unlink($destination); 
    sendResponse(false, 'Gagal mempersiapkan statement database.', [], 500);
}

$stmt->bind_param('is', $id_reservasi, $relativePath);
if (!$stmt->execute()) {
    // Hapus file yang sudah terlanjur di-upload jika db-insert gagal
    unlink($destination);
    sendResponse(false, 'Gagal menyimpan data file ke database.', [], 500);
}

$stmt->close();
$koneksi->close();


// 8. Kirim Response Sukses
sendResponse(true, 'File identitas berhasil diunggah.', ['filePath' => $relativePath]);
?>