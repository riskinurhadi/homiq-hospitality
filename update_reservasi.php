<?php
// update_reservasi.php
// Endpoint AJAX untuk update status reservasi (check-in, cancel)

require_once 'auth_check.php';
require_once 'koneksi.php';

header('Content-Type: application/json');

if (!in_array($role_user, ['admin', 'front_office'])) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'message' => 'Forbidden']);
    exit();
}

$id_reservasi = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$action = $_POST['action'] ?? '';

if ($id_reservasi <= 0 || !in_array($action, ['checkin', 'cancel'])) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'Invalid request']);
    exit();
}

// Ambil status sekarang
$stmt = $koneksi->prepare("SELECT status_booking FROM tbl_reservasi WHERE id_reservasi = ?");
$stmt->bind_param('i', $id_reservasi);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows === 0) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'message' => 'Reservasi tidak ditemukan']);
    exit();
}
$current = $res->fetch_assoc();
$stmt->close();

$newStatus = $current['status_booking'];
if ($action === 'checkin') { $newStatus = 'Checked-in'; }
if ($action === 'cancel') { $newStatus = 'Canceled'; }

$stmtU = $koneksi->prepare("UPDATE tbl_reservasi SET status_booking = ? WHERE id_reservasi = ?");
$stmtU->bind_param('si', $newStatus, $id_reservasi);
$ok = $stmtU->execute();
$stmtU->close();

echo json_encode(['ok' => $ok, 'newStatus' => $newStatus]);
exit();
?>


