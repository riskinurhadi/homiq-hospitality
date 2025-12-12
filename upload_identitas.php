<?php
// upload_identitas.php
// Endpoint untuk upload/capture foto identitas tamu saat check-in

require_once 'auth_check.php';
require_once 'koneksi.php';

header('Content-Type: application/json');

if (!in_array($role_user, ['admin', 'front_office'])) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'message' => 'Forbidden']);
    exit();
}

$id_reservasi = isset($_POST['id_reservasi']) ? (int)$_POST['id_reservasi'] : 0;
if ($id_reservasi <= 0) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'id_reservasi invalid']);
    exit();
}

// Pastikan folder upload ada
$targetDir = __DIR__ . '/uploads/identitas/';
if (!is_dir($targetDir)) { @mkdir($targetDir, 0775, true); }

$savedFiles = [];

// Terima file dari input name="foto" (bisa multiple)
if (!empty($_FILES['foto']['name'])) {
    if (is_array($_FILES['foto']['name'])) {
        $count = count($_FILES['foto']['name']);
        for ($i=0; $i<$count; $i++) {
            $tmp = $_FILES['foto']['tmp_name'][$i];
            if (!is_uploaded_file($tmp)) { continue; }
            $ext = pathinfo($_FILES['foto']['name'][$i], PATHINFO_EXTENSION) ?: 'jpg';
            $name = 'resv_' . $id_reservasi . '_' . time() . '_' . $i . '.' . strtolower($ext);
            $dest = $targetDir . $name;
            if (move_uploaded_file($tmp, $dest)) { $savedFiles[] = 'uploads/identitas/' . $name; }
        }
    } else {
        $tmp = $_FILES['foto']['tmp_name'];
        if (is_uploaded_file($tmp)) {
            $ext = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION) ?: 'jpg';
            $name = 'resv_' . $id_reservasi . '_' . time() . '.' . strtolower($ext);
            $dest = $targetDir . $name;
            if (move_uploaded_file($tmp, $dest)) { $savedFiles[] = 'uploads/identitas/' . $name; }
        }
    }
}

// Support base64 (dari canvas)
if (empty($savedFiles) && !empty($_POST['image_base64'])) {
    $data = $_POST['image_base64'];
    if (preg_match('/^data:image\/(png|jpeg|jpg);base64,/', $data, $m)) {
        $data = substr($data, strpos($data, ',') + 1);
        $data = base64_decode($data);
        $ext = $m[1] === 'jpeg' ? 'jpg' : $m[1];
        $name = 'resv_' . $id_reservasi . '_' . time() . '.' . $ext;
        $dest = $targetDir . $name;
        if (file_put_contents($dest, $data) !== false) { $savedFiles[] = 'uploads/identitas/' . $name; }
    }
}

// Simpan metadata ke DB (tabel sederhana)
$koneksi->query("CREATE TABLE IF NOT EXISTS tbl_reservasi_identitas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_reservasi INT NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (id_reservasi)
) ENGINE=InnoDB;");

if (!empty($savedFiles)) {
    $stmt = $koneksi->prepare("INSERT INTO tbl_reservasi_identitas (id_reservasi, file_path) VALUES (?, ?)");
    foreach ($savedFiles as $fp) {
        $stmt->bind_param('is', $id_reservasi, $fp);
        $stmt->execute();
    }
    $stmt->close();
}

echo json_encode(['ok' => true, 'files' => $savedFiles]);
exit();
?>


