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

try {
    // Logika untuk Guesthouse: Cek apakah seluruh properti tersedia.
    if ($jenis_booking === 'Guesthouse') {
        if (!$checkin_date || !$checkout_date) {
            echo json_encode([]);
            exit();
        }

        $checkin_sql = $checkin_date . ' 14:00:00';
        $checkout_sql = $checkout_date . ' 12:00:00';
         if (new DateTime($checkin_sql) >= new DateTime($checkout_sql)) {
            echo json_encode([]);
            exit();
        }

        // Cek apakah ada SATU SAJA booking yang tumpang tindih untuk properti ini
        $sql_conflict = "
            SELECT r.id_reservasi FROM tbl_reservasi r
            JOIN tbl_kamar k ON r.id_kamar = k.id_kamar
            WHERE k.id_properti = ?
              AND r.status_booking != 'Canceled'
              AND r.tgl_checkin < ?
              AND r.tgl_checkout > ?
            LIMIT 1
        ";
        
        $params_conflict = [$id_properti, $checkout_sql, $checkin_sql];
        $types_conflict = "iss";

        if ($editing_id > 0) {
            $sql_conflict .= " AND r.id_reservasi != ?";
            $params_conflict[] = $editing_id;
            $types_conflict .= "i";
        }
        
        $stmt_conflict = $koneksi->prepare($sql_conflict);
        $stmt_conflict->bind_param($types_conflict, ...$params_conflict);
        $stmt_conflict->execute();
        
        if ($stmt_conflict->get_result()->num_rows > 0) {
            // Ada konflik, guesthouse tidak bisa dibooking. Kembalikan array kosong.
            echo json_encode([]);
        } else {
            // Tidak ada konflik, kembalikan semua kamar di properti ini.
            $stmt_all = $koneksi->prepare("SELECT id_kamar, nama_kamar, harga_default FROM tbl_kamar WHERE id_properti = ? ORDER BY nama_kamar");
            $stmt_all->bind_param("i", $id_properti);
            $stmt_all->execute();
            $kamar_list = $stmt_all->get_result()->fetch_all(MYSQLI_ASSOC);
            echo json_encode($kamar_list);
            $stmt_all->close();
        }
        $stmt_conflict->close();
        exit();
    }

    // Logika untuk booking Harian dan Transit
    if ((strpos($jenis_booking, 'Transit') === false && (!$checkin_date || !$checkout_date)) || (strpos($jenis_booking, 'Transit') !== false && !$checkin_date)) {
        // Jika tanggal tidak disediakan untuk Harian/Transit, kembalikan semua kamar untuk properti tersebut.
        $stmt = $koneksi->prepare("SELECT id_kamar, nama_kamar, harga_default FROM tbl_kamar WHERE id_properti = ? ORDER BY nama_kamar");
        $stmt->bind_param("i", $id_properti);
        $stmt->execute();
        $kamar_list = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        echo json_encode($kamar_list);
        $stmt->close();
        $koneksi->close();
        exit();
    }

    if (strpos($jenis_booking, 'Transit') !== false) {
        $checkout_date = $checkin_date;
        $checkin_sql = $checkin_date . ' ' . ($_GET['checkin_time'] ?? '00:00') . ':00';
        $checkout_sql = $checkout_date . ' ' . ($_GET['checkout_time'] ?? '23:59') . ':00';
    } else { // Harian
        $checkin_sql = $checkin_date . ' 14:00:00';
        $checkout_sql = $checkout_date . ' 12:00:00';
    }

    if (new DateTime($checkin_sql) >= new DateTime($checkout_sql)) {
        echo json_encode([]);
        exit();
    }

    // 1. Dapatkan ID kamar yang tumpang tindih, KECUALI yang sedang diedit
    $sql_booked = "
        SELECT DISTINCT r.id_kamar FROM tbl_reservasi r
        JOIN tbl_kamar k ON r.id_kamar = k.id_kamar
        WHERE k.id_properti = ?
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
    $booked_kamar_ids = array_map(fn($item) => $item['id_kamar'], $stmt_booked->get_result()->fetch_all(MYSQLI_ASSOC));
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
    $kamar_list = $stmt_available->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_available->close();

    // 3. Jika edit, pastikan kamar yang diedit selalu ada dalam daftar
    if ($editing_id > 0) {
        $stmt_current = $koneksi->prepare("SELECT k.id_kamar, k.nama_kamar, k.harga_default FROM tbl_kamar k JOIN tbl_reservasi r ON k.id_kamar=r.id_kamar WHERE r.id_reservasi=?");
        $stmt_current->bind_param("i", $editing_id);
        $stmt_current->execute();
        $current_kamar_res = $stmt_current->get_result();
        if ($current_kamar_res->num_rows > 0) {
            $current_kamar = $current_kamar_res->fetch_assoc();
            if (!in_array($current_kamar['id_kamar'], array_column($kamar_list, 'id_kamar'))) {
                $kamar_list[] = $current_kamar;
                usort($kamar_list, fn($a, $b) => strcmp($a['nama_kamar'], $b['nama_kamar']));
            }
        }
        $stmt_current->close();
    }

    echo json_encode($kamar_list);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
} finally {
    $koneksi->close();
}
?>