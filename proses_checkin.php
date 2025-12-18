<?php
// Error logging setup
ini_set('display_errors', 0); // Do not display errors on screen
ini_set('log_errors', 1); // Log errors to a file
ini_set('error_log', 'C:/Users/RISKI NURHADI/.gemini/tmp/ab2ba117f30514462c49772eb8acb08566770f7cc4a34fa6c264df13a5ef6d37/proses_checkin_error.log');
error_reporting(E_ALL);

function customErrorHandler($errno, $errstr, $errfile, $errline) {
    error_log("PHP Error: [$errno] $errstr in $errfile on line $errline");
    // Return true to prevent PHP's default error handler from running
    return true; 
}
set_error_handler("customErrorHandler");

function customExceptionHandler($exception) {
    error_log("PHP Uncaught Exception: " . $exception->getMessage() . " in " . $exception->getFile() . " on line " . $exception->getLine());
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'An unexpected server error occurred. Check logs.']);
    exit();
}
set_exception_handler("customExceptionHandler");

// Start output buffering (already present, keep it)
ob_start();

// proses_checkin.php
// Meng-handle upload identitas, update status pembayaran, dan check-in

header('Content-Type: application/json');
ob_clean(); // Clean any output before sending JSON header

require_once 'auth_check.php';
require_once 'koneksi.php';

// Check for database connection error immediately
if ($koneksi->connect_error) {
    error_log("Database Connection Error: " . $koneksi->connect_error);
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'Database connection failed. Check logs.']);
    exit();
}

$response = ['ok' => false, 'message' => 'Terjadi kesalahan tidak diketahui.'];

// Cek hak akses
if (!in_array($role_user, ['admin', 'front_office'])) {
    $response['message'] = 'Anda tidak memiliki hak akses untuk melakukan tindakan ini.';
    echo json_encode($response);
    exit();
}

// Validasi input
$id_reservasi = isset($_POST['id_reservasi']) ? (int)$_POST['id_reservasi'] : 0;
$status_pembayaran_baru = isset($_POST['status_pembayaran']) ? $_POST['status_pembayaran'] : null;

if ($id_reservasi <= 0) {
    $response['message'] = 'ID Reservasi tidak valid.';
    echo json_encode($response);
    exit();
}

// Validasi file upload
if (!isset($_FILES['foto']) || $_FILES['foto']['error'] !== UPLOAD_ERR_OK) {
    $response['message'] = 'Upload file identitas gagal atau file tidak ditemukan.';
    echo json_encode($response);
    exit();
}

$file = $_FILES['foto'];
$allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
if (!in_array($file['type'], $allowed_types)) {
    $response['message'] = 'Format file tidak valid. Hanya JPG, PNG, GIF, WEBP yang diizinkan.';
    echo json_encode($response);
    exit();
}

// Tentukan path upload
$upload_dir = 'uploads/identitas/';
$file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
$file_name = 'identitas_' . $id_reservasi . '_' . time() . '.' . $file_extension;
$upload_path = $upload_dir . $file_name;

// Mulai transaksi database
$koneksi->begin_transaction();

try {
    // 1. Pindahkan file yang di-upload
    if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
        throw new Exception('Gagal memindahkan file yang diunggah.');
    }

    // 2. Ambil status reservasi saat ini
    $stmt = $koneksi->prepare("SELECT status_booking, status_pembayaran FROM tbl_reservasi WHERE id_reservasi = ?");
    $stmt->bind_param("i", $id_reservasi);
    $stmt->execute();
    $reservasi = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$reservasi) {
         throw new Exception('Reservasi tidak ditemukan.');
    }

    if ($reservasi['status_booking'] !== 'Booking') {
        throw new Exception('Reservasi ini tidak dalam status "Booking" dan tidak bisa di check-in.');
    }
    
    // 3. Update status pembayaran jika diperlukan
    if ($reservasi['status_pembayaran'] == 'Belum Bayar' && $status_pembayaran_baru) {
        if (!in_array($status_pembayaran_baru, ['DP', 'Lunas'])) {
            throw new Exception('Status pembayaran baru tidak valid.');
        }

        $jumlah_dp = null;
        if ($status_pembayaran_baru == 'DP') {
            $jumlah_dp = isset($_POST['jumlah_dp']) ? (float)str_replace(['.', ','], '', $_POST['jumlah_dp']) : 0;
            if ($jumlah_dp <= 0) {
                throw new Exception('Jumlah DP harus lebih besar dari 0.');
            }
            $stmt_pay = $koneksi->prepare("UPDATE tbl_reservasi SET status_pembayaran = ?, jumlah_dp = ? WHERE id_reservasi = ?");
            $stmt_pay->bind_param("sdi", $status_pembayaran_baru, $jumlah_dp, $id_reservasi);
        } else { // Lunas
            $stmt_pay = $koneksi->prepare("UPDATE tbl_reservasi SET status_pembayaran = ? WHERE id_reservasi = ?");
            $stmt_pay->bind_param("si", $status_pembayaran_baru, $id_reservasi);
        }
        
        $stmt_pay->execute();
        $stmt_pay->close();

    } else if ($reservasi['status_pembayaran'] == 'Belum Bayar' && !$status_pembayaran_baru) {
        throw new Exception('Status pembayaran harus diubah (DP/Lunas) untuk check-in.');
    }


    // 4. Update status booking menjadi 'Checked-in' dan simpan path identitas
    $new_status_booking = 'Checked-in';
    $stmt_checkin = $koneksi->prepare("UPDATE tbl_reservasi SET status_booking = ?, path_identitas = ? WHERE id_reservasi = ?");
    $stmt_checkin->bind_param("ssi", $new_status_booking, $upload_path, $id_reservasi);
    $stmt_checkin->execute();
    $stmt_checkin->close();

    // Jika semua berhasil, commit transaksi
    $koneksi->commit();
    $response['ok'] = true;
    $response['message'] = 'Check-in berhasil!';

} catch (Exception $e) {
    // Jika ada error, rollback transaksi
    $koneksi->rollback();
    // Hapus file yang mungkin sudah ter-upload jika terjadi error DB
    if (isset($upload_path) && file_exists($upload_path)) {
        unlink($upload_path);
    }
    $response['message'] = 'Terjadi kesalahan: ' . $e->getMessage();
}

$koneksi->close();
echo json_encode($response);
?>
