<?php
// upload_identitas.php
// Endpoint untuk upload/capture foto identitas tamu saat check-in

require_once 'auth_check.php';
require_once 'koneksi.php';

header('Content-Type: application/json');

// Fungsi untuk kirim response error dan exit
function sendError($message, $code = 400) {
    http_response_code($code);
    echo json_encode(['ok' => false, 'message' => $message]);
    exit();
}

if (!in_array($role_user, ['admin', 'front_office'])) {
    sendError('Akses ditolak. Anda tidak memiliki izin untuk melakukan aksi ini.', 403);
}

$id_reservasi = isset($_POST['id_reservasi']) ? (int)$_POST['id_reservasi'] : 0;
if ($id_reservasi <= 0) {
    sendError('ID Reservasi tidak valid.');
}

// Persiapan direktori upload
$targetDir = __DIR__ . '/uploads/identitas/';
if (!is_dir($targetDir)) {
    if (!mkdir($targetDir, 0775, true)) {
        sendError('Gagal membuat direktori upload. Periksa perizinan folder.');
    }
}
if (!is_writable($targetDir)) {
    sendError('Direktori upload tidak dapat ditulis. Periksa perizinan folder.');
}

$savedFiles = [];

// Cek apakah ada file yang di-upload
if (empty($_FILES['foto']) && empty($_POST['image_base64'])) {
    sendError('Tidak ada file atau gambar yang terdeteksi untuk diunggah.');
}

// Handle file upload dari input name="foto"
if (!empty($_FILES['foto']['name'])) {
    // Normalisasi array file untuk kasus single dan multiple upload
    $files = [];
    if (is_array($_FILES['foto']['name'])) {
        for ($i = 0; $i < count($_FILES['foto']['name']); $i++) {
            $files[] = [
                'name' => $_FILES['foto']['name'][$i],
                'tmp_name' => $_FILES['foto']['tmp_name'][$i],
                'error' => $_FILES['foto']['error'][$i],
            ];
        }
    } else {
        $files[] = $_FILES['foto'];
    }

    foreach ($files as $file) {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            // Bisa ditambahkan penanganan error upload yang lebih spesifik jika perlu
            continue; 
        }
        $tmp = $file['tmp_name'];
        if (!is_uploaded_file($tmp)) {
            continue;
        }

        $ext = pathinfo($file['name'], PATHINFO_EXTENSION) ?: 'jpg';
        $name = 'resv_' . $id_reservasi . '_' . time() . '_' . uniqid() . '.' . strtolower($ext);
        $dest = $targetDir . $name;

        if (move_uploaded_file($tmp, $dest)) {
            $savedFiles[] = 'uploads/identitas/' . $name;
        } else {
            // Jika satu file gagal, kita bisa berhenti atau lanjut. 
            // Untuk saat ini kita kirim error dan berhenti.
            sendError('Gagal memindahkan file yang diunggah. Proses dibatalkan.');
        }
    }
}

// Handle gambar dari Base64 (misal: hasil capture kamera di browser)
if (empty($savedFiles) && !empty($_POST['image_base64'])) {
    $data = $_POST['image_base64'];
    if (preg_match('/^data:image\/(png|jpeg|jpg);base64,/', $data, $m)) {
        $data = substr($data, strpos($data, ',') + 1);
        $data = base64_decode($data);
        if ($data === false) {
            sendError('Data Base64 tidak valid.');
        }
        $ext = $m[1] === 'jpeg' ? 'jpg' : $m[1];
        $name = 'resv_' . $id_reservasi . '_' . time() . '.' . $ext;
        $dest = $targetDir . $name;
        if (file_put_contents($dest, $data) !== false) {
            $savedFiles[] = 'uploads/identitas/' . $name;
        } else {
            sendError('Gagal menyimpan gambar dari data Base64.');
        }
    } else {
        sendError('Format data URI Base64 tidak dikenali.');
    }
}

// Jika setelah semua proses, tidak ada file yang berhasil disimpan
if (empty($savedFiles)) {
    sendError('Tidak ada file yang berhasil diunggah atau disimpan.');
}

// Buat tabel jika belum ada
$koneksi->query("CREATE TABLE IF NOT EXISTS tbl_reservasi_identitas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_reservasi INT NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (id_reservasi)
) ENGINE=InnoDB;");

// Simpan path file ke database
$stmt = $koneksi->prepare("INSERT INTO tbl_reservasi_identitas (id_reservasi, file_path) VALUES (?, ?)");
if ($stmt === false) {
    sendError('Gagal mempersiapkan statement database.', 500);
}
foreach ($savedFiles as $fp) {
    $stmt->bind_param('is', $id_reservasi, $fp);
    if (!$stmt->execute()) {
        sendError('Gagal menyimpan metadata file ke database.', 500);
    }
}
$stmt->close();
$koneksi->close();

echo json_encode(['ok' => true, 'files' => $savedFiles, 'message' => 'Identitas berhasil diunggah.']);
exit();
?>


