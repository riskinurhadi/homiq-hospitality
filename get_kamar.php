<?php
// get_kamar.php
// API endpoint untuk mendapatkan daftar kamar yang tersedia berdasarkan properti dan rentang tanggal (AJAX)

header('Content-Type: application/json');

require_once 'koneksi.php';

// Ambil parameter dari GET request
$id_properti = isset($_GET['properti']) ? (int)$_GET['properti'] : 0;
$checkin_date = isset($_GET['checkin']) ? $_GET['checkin'] : null;
$checkout_date = isset($_GET['checkout']) ? $_GET['checkout'] : null;
$editing_id = isset($_GET['editing_id']) ? (int)$_GET['editing_id'] : 0;
$jenis_booking = isset($_GET['jenis_booking']) ? $_GET['jenis_booking'] : 'Harian';

if ($id_properti <= 0) {
    echo json_encode([]);
    exit();
}

// Untuk Guesthouse, tidak perlu cek ketersediaan, tampilkan semua kamar dari properti tsb
if ($jenis_booking === 'Guesthouse') {
    $stmt = $koneksi->prepare("SELECT id_kamar, nama_kamar, harga_default FROM tbl_kamar WHERE id_properti = ? ORDER BY nama_kamar");
    $stmt->bind_param("i", $id_properti);
    $stmt->execute();
    $result = $stmt->get_result();
    $kamar_list = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    echo json_encode($kamar_list);
    $koneksi->close();
    exit();
}

// Jika bukan guesthouse dan tanggal tidak lengkap (misal, hanya checkin), kembalikan array kosong
if ((strpos($jenis_booking, 'Transit') === false && (!$checkin_date || !$checkout_date)) || (strpos($jenis_booking, 'Transit') !== false && !$checkin_date)) {
    echo json_encode([]);
    $koneksi->close();
    exit();
}


try {
    // Set waktu default berdasarkan jenis booking untuk perbandingan yang akurat
    if (strpos($jenis_booking, 'Transit') !== false) {
        // Untuk transit, checkout di hari yang sama
        $checkout_date = $checkin_date;
        $checkin_sql = $checkin_date . ' ' . ($_GET['checkin_time'] ?? '00:00') . ':00';
        $checkout_sql = $checkout_date . ' ' . ($_GET['checkout_time'] ?? '23:59') . ':00';
    } else { // Harian
        $checkin_sql = $checkin_date . ' 14:00:00';
        $checkout_sql = $checkout_date . ' 12:00:00';
    }
    
    // Validasi dasar waktu
    if (new DateTime($checkin_sql) >= new DateTime($checkout_sql)) {
        echo json_encode([]); // Kembalikan kosong jika waktu tidak valid
        exit();
    }

    // 1. Dapatkan ID kamar yang tumpang tindih, KECUALI yang sedang diedit
    $sql_booked = "
        SELECT DISTINCT r.id_kamar FROM tbl_reservasi r
        WHERE r.id_kamar IS NOT NULL
          AND r.id_properti = ?
          AND r.status_booking != 'Canceled'
          AND r.tgl_checkin < ?
          AND r.tgl_checkout > ?
    ";
    
    $params = [$id_properti, $checkout_sql, $checkin_sql];
    $types = "iss";

    if ($editing_id > 0) {
        $sql_booked .= " AND r.id_reservasi != ?";
        $params[] = $editing_id;
        $types .= "i";
    }

    $stmt_booked = $koneksi->prepare($sql_booked);
    $stmt_booked->bind_param($types, ...$params);
    $stmt_booked->execute();
    $result_booked = $stmt_booked->get_result();
    
    $booked_kamar_ids = [];
    while ($row = $result_booked->fetch_assoc()) {
        $booked_kamar_ids[] = (int)$row['id_kamar'];
    }
    $stmt_booked->close();

    // 2. Ambil semua kamar di properti ini yang TIDAK ada di daftar booked
    $sql_available = "SELECT id_kamar, nama_kamar, harga_default FROM tbl_kamar WHERE id_properti = ?";
    $params_available = [$id_properti];
    $types_available = "i";

    if (!empty($booked_kamar_ids)) {
        $placeholders = implode(',', array_fill(0, count($booked_kamar_ids), '?'));
        $sql_available .= " AND id_kamar NOT IN ($placeholders)";
        $params_available = array_merge($params_available, $booked_kamar_ids);
        $types_available .= str_repeat('i', count($booked_kamar_ids));
    }
    $sql_available .= " ORDER BY nama_kamar";

    $stmt_available = $koneksi->prepare($sql_available);
    $stmt_available->bind_param($types_available, ...$params_available);
    $stmt_available->execute();
    $result_available = $stmt_available->get_result();
    $kamar_list = $result_available->fetch_all(MYSQLI_ASSOC);
    $stmt_available->close();

    // 3. Jika edit, pastikan kamar yang diedit selalu ada dalam daftar
    if ($editing_id > 0) {
        $stmt_current_kamar = $koneksi->prepare("
            SELECT k.id_kamar, k.nama_kamar, k.harga_default 
            FROM tbl_kamar k
            JOIN tbl_reservasi r ON k.id_kamar = r.id_kamar
            WHERE r.id_reservasi = ?
        ");
        $stmt_current_kamar->bind_param("i", $editing_id);
        $stmt_current_kamar->execute();
        $current_kamar_result = $stmt_current_kamar->get_result();

        if ($current_kamar_result->num_rows > 0) {
            $current_kamar = $current_kamar_result->fetch_assoc();
            $found = false;
            foreach ($kamar_list as $kamar) {
                if ($kamar['id_kamar'] == $current_kamar['id_kamar']) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $kamar_list[] = $current_kamar;
                usort($kamar_list, function($a, $b) {
                    return strcmp($a['nama_kamar'], $b['nama_kamar']);
                });
            }
        }
        $stmt_current_kamar->close();
    }

    echo json_encode($kamar_list);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}

$koneksi->close();
?>