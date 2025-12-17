<?php
// dashboard.php
// Dashboard utama dengan Calendar View untuk ketersediaan kamar

require_once 'auth_check.php';
require_once 'koneksi.php';

// Ambil parameter bulan dan tahun (default: bulan dan tahun saat ini)
$current_month = isset($_GET['month']) ? (int)$_GET['month'] : date('n');
$current_year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');

// Validasi bulan dan tahun
if ($current_month < 1 || $current_month > 12) $current_month = date('n');
if ($current_year < 2020 || $current_year > 2100) $current_year = date('Y');

// Hitung jumlah hari dalam bulan (tanpa extension calendar)
$days_in_month = date('t', mktime(0, 0, 0, $current_month, 1, $current_year));
$first_day = date('N', mktime(0, 0, 0, $current_month, 1, $current_year)); // 1=Monday, 7=Sunday

// Format untuk query
$start_date = sprintf('%04d-%02d-01', $current_year, $current_month);
$end_date = sprintf('%04d-%02d-%02d', $current_year, $current_month, $days_in_month);

// Ambil semua kamar yang tersedia (urutkan berdasarkan nama)
$query_kamar = "SELECT k.id_kamar, k.nama_kamar, k.tipe_kamar, p.nama_properti 
                 FROM tbl_kamar k 
                 JOIN tbl_properti p ON k.id_properti = p.id_properti 
                 WHERE k.status = 'Tersedia'
                 ORDER BY p.nama_properti, k.nama_kamar";
$result_kamar = $koneksi->query($query_kamar);
$kamar_list = [];
while ($row = $result_kamar->fetch_assoc()) {
    $kamar_list[] = $row;
}

// Ambil semua reservasi dalam rentang bulan ini
$query_reservasi = "SELECT r.id_reservasi, r.id_kamar, r.tgl_checkin, r.tgl_checkout, 
                           r.status_booking, r.status_pembayaran, r.platform_booking,
                           t.nama_lengkap as nama_tamu, t.no_hp,
                           k.nama_kamar, p.nama_properti
                    FROM tbl_reservasi r
                    JOIN tbl_kamar k ON r.id_kamar = k.id_kamar
                    JOIN tbl_properti p ON k.id_properti = p.id_properti
                    JOIN tbl_tamu t ON r.id_tamu = t.id_tamu
                    WHERE r.status_booking != 'Canceled'
                    AND (
                        (r.tgl_checkin <= ? AND r.tgl_checkout >= ?) OR
                        (r.tgl_checkin >= ? AND r.tgl_checkin <= ?) OR
                        (r.tgl_checkout >= ? AND r.tgl_checkout <= ?)
                    )
                    ORDER BY r.tgl_checkin";
$stmt = $koneksi->prepare($query_reservasi);
$stmt->bind_param("ssssss", $end_date, $start_date, $start_date, $end_date, $start_date, $end_date);
$stmt->execute();
$result_reservasi = $stmt->get_result();
$reservasi_list = [];
while ($row = $result_reservasi->fetch_assoc()) {
    $reservasi_list[] = $row;
}
$stmt->close();

// Buat mapping reservasi per kamar
$reservasi_map = [];
foreach ($reservasi_list as $res) {
    $kamar_id = $res['id_kamar'];
    if (!isset($reservasi_map[$kamar_id])) {
        $reservasi_map[$kamar_id] = [];
    }
    $reservasi_map[$kamar_id][] = $res;
}

// Ambil statistik untuk cards
$stat_reservasi_aktif = $koneksi->query("SELECT COUNT(*) as total FROM tbl_reservasi WHERE status_booking IN ('Booking', 'Checked-in')")->fetch_assoc()['total'];
$stat_kamar_tersedia = $koneksi->query("SELECT COUNT(*) as total FROM tbl_kamar WHERE status = 'Tersedia'")->fetch_assoc()['total'];
$today = date('Y-m-d');
$stat_tamu_hari_ini = $koneksi->query("
    SELECT COUNT(DISTINCT r.id_tamu) as total 
    FROM tbl_reservasi r
    WHERE r.status_booking = 'Checked-in'
    AND DATE(r.tgl_checkin) = '$today'
")->fetch_assoc()['total'];
$stat_okupansi_bulan = $koneksi->query("
    SELECT 
        COUNT(DISTINCT r.id_kamar) as kamar_terisi,
        (SELECT COUNT(*) FROM tbl_kamar WHERE status = 'Tersedia') as total_kamar
    FROM tbl_reservasi r
    WHERE r.status_booking IN ('Booking', 'Checked-in')
    AND r.tgl_checkin <= '$end_date' 
    AND r.tgl_checkout >= '$start_date'
")->fetch_assoc();
$okupansi_percent = $stat_kamar_tersedia > 0 ? round(($stat_okupansi_bulan['kamar_terisi'] / $stat_kamar_tersedia) * 100, 1) : 0;

// Ambil reservasi terbaru (untuk widget)
$query_reservasi_terbaru = "SELECT r.tgl_checkin, t.nama_lengkap, k.nama_kamar, p.nama_properti, r.status_booking
                            FROM tbl_reservasi r
                            JOIN tbl_tamu t ON r.id_tamu = t.id_tamu
                            JOIN tbl_kamar k ON r.id_kamar = k.id_kamar
                            JOIN tbl_properti p ON k.id_properti = p.id_properti
                            WHERE r.status_booking IN ('Booking', 'Checked-in')
                            ORDER BY r.tgl_checkin ASC
                            LIMIT 5";
$result_reservasi_terbaru = $koneksi->query($query_reservasi_terbaru);

$koneksi->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - CMS Guesthouse Adiputra</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        html {
            font-size: 85%;
        }
        :root {
            --primary: #4361ee;
            --primary-soft: #eef2ff;
            --secondary: #3f37c9;
            --success: #06d6a0;
            --warning: #ffd166;
            --danger: #ef476f;
            --dark: #1e293b;
            --light: #f8f9fa;
            --body-bg: #f1f5f9;
            --card-bg: #ffffff;
            --text-main: #334155;
            --text-muted: #64748b;
            --border-color: #e2e8f0;
            --sidebar-width: 260px;
            --sidebar-bg: #0f172a;
            --radius-md: 12px;
            --radius-lg: 16px;
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--body-bg);
            color: var(--text-main);
            min-height: 100vh;
        }

        .wrapper {
            display: flex;
        }

        #main-content {
            flex-grow: 1;
            margin-left: var(--sidebar-width);
            transition: margin-left 0.35s ease-in-out;
        }

        .mobile-toggle-btn {
            display: none;
            font-size: 1.5rem;
            color: var(--text-main);
            background: none;
            border: none;
        }

        @media (max-width: 991.98px) {
            #main-content {
                margin-left: 0;
            }
            .mobile-toggle-btn {
                display: block;
            }
        }

        /* CARDS */
        .card-modern {
            background: var(--card-bg);
            border: none;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
            transition: transform 0.2s, box-shadow 0.2s;
            height: 100%;
            overflow: hidden;
        }

        .card-modern:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .stat-card-body {
            padding: 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .stat-icon.blue { background: rgba(67, 97, 238, 0.1); color: var(--primary); }
        .stat-icon.green { background: rgba(6, 214, 160, 0.1); color: var(--success); }
        .stat-icon.orange { background: rgba(255, 209, 102, 0.1); color: #ff9f1c; }
        .stat-icon.purple { background: rgba(114, 9, 183, 0.1); color: #7209b7; }

        .stat-value {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--dark);
            line-height: 1.2;
        }

        .stat-label {
            color: var(--text-muted);
            font-size: 0.875rem;
            font-weight: 500;
        }

        /* CALENDAR */
        .calendar-wrapper {
            background: white;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
            overflow: hidden;
            border: 1px solid var(--border-color);
        }

        .calendar-header {
            padding: 1.25rem;
            background: #fff;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .calendar-scroll {
            overflow-x: auto;
            max-height: 75vh;
        }

        .calendar-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        .calendar-table th, .calendar-table td {
            border-right: 1px solid var(--border-color);
            border-bottom: 1px solid var(--border-color);
        }

        .calendar-table th {
            background: #f8fafc;
            padding: 1rem 0.5rem;
            text-align: center;
            font-weight: 600;
            color: var(--text-main);
            position: sticky;
            top: 0;
            z-index: 10;
            min-width: 60px;
        }

        .calendar-table th.room-col {
            position: sticky;
            left: 0;
            z-index: 20;
            background: #fff;
            text-align: left;
            padding-left: 1.5rem;
            min-width: 220px;
            box-shadow: 2px 0 5px rgba(0,0,0,0.05);
        }

        .calendar-table td.room-col {
            position: sticky;
            left: 0;
            z-index: 15;
            background: #fff;
            padding: 1rem 1.5rem;
            font-weight: 600;
            color: var(--dark);
            box-shadow: 2px 0 5px rgba(0,0,0,0.05);
        }

        .calendar-day-cell {
            padding: 0.25rem;
            vertical-align: top;
            height: 100px;
            background: #fff;
            transition: background 0.2s;
        }

        .calendar-day-cell:hover {
            background: #f8fafc;
        }

        .calendar-day-cell.today {
            background: rgba(67, 97, 238, 0.03);
        }

        .calendar-day-number {
            font-size: 0.75rem;
            color: var(--text-muted);
            margin-bottom: 4px;
            text-align: right;
            padding-right: 4px;
        }

        .booking-pill {
            font-size: 0.7rem;
            padding: 4px 8px;
            border-radius: 6px;
            margin-bottom: 4px;
            cursor: pointer;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            font-weight: 500;
            box-shadow: 0 1px 2px rgba(0,0,0,0.1);
            transition: transform 0.1s;
            border-left: 3px solid transparent;
        }

        .booking-pill:hover {
            transform: scale(1.02);
            z-index: 5;
        }

        .booking-pill.booking { background: #e0e7ff; color: #4361ee; border-left-color: #4361ee; }
        .booking-pill.checked-in { background: #d1fae5; color: #059669; border-left-color: #059669; }
        .booking-pill.checked-out { background: #f1f5f9; color: #64748b; border-left-color: #64748b; }
        
        .booking-pill.lunas { border-left-style: solid; }
        .booking-pill.belum-bayar { border-left-color: #ef476f; background: #ffe4e6; color: #e11d48; }

        /* WIDGET */
        .widget-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.25rem;
        }
        
        .widget-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--dark);
            margin: 0;
        }

        .res-item {
            display: flex;
            align-items: center;
            padding: 1rem;
            background: #f8fafc;
            border-radius: 12px;
            margin-bottom: 0.75rem;
            transition: background 0.2s;
        }

        .res-item:hover {
            background: #f1f5f9;
        }

        .res-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            color: var(--text-muted);
            font-weight: 600;
        }

        .res-info h6 {
            margin: 0;
            font-size: 0.95rem;
            font-weight: 600;
            color: var(--dark);
        }

        .res-info p {
            margin: 0;
            font-size: 0.8rem;
            color: var(--text-muted);
        }

        .res-status {
            margin-left: auto;
            font-size: 0.75rem;
            padding: 4px 8px;
            border-radius: 6px;
            font-weight: 600;
        }
        
        .res-status.booking { background: #e0e7ff; color: #4361ee; }
        .res-status.checked-in { background: #d1fae5; color: #059669; }
    </style>
</head>
<body style="overflow-x: hidden;">
    <div class="wrapper">
        <!-- SIDEBAR -->
        <?php include 'sidebar.php'; ?>

        <!-- MAIN CONTENT -->
        <main id="main-content" class="p-3 p-md-4">
            <!-- Header -->
            <header class="d-flex justify-content-between align-items-center mb-4">
                <div class="d-flex align-items-center">
                    <button class="mobile-toggle-btn me-3" id="mobile-sidebar-toggle">
                        <i class="bi bi-list"></i>
                    </button>
                    <div>
                        <h4 class="fw-bold mb-1 text-dark">Dashboard</h4>
                        <p class="text-muted mb-0" style="font-size: 0.9rem;">Selamat datang kembali, <?php echo htmlspecialchars($nama_lengkap); ?>! 👋</p>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <div class="d-none d-md-block">
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0 rounded-start-pill ps-3"><i class="bi bi-search text-muted"></i></span>
                            <input type="text" class="form-control border-start-0 rounded-end-pill" placeholder="Cari reservasi..." style="max-width: 200px;">
                        </div>
                    </div>

                    <div class="dropdown">
                        <a class="d-flex align-items-center text-decoration-none" href="#" role="button" data-bs-toggle="dropdown">
                            <div class="bg-white p-1 rounded-circle shadow-sm">
                                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($nama_lengkap); ?>&background=4361ee&color=fff&size=128&bold=true" 
                                     alt="User" style="width: 38px; height: 38px; border-radius: 50%;">
                            </div>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 rounded-3 mt-2">
                            <li><h6 class="dropdown-header">Akun Saya</h6></li>
                            <li><a class="dropdown-item" href="#"><i class="bi bi-person me-2"></i> Profil</a></li>
                            <li><a class="dropdown-item" href="#"><i class="bi bi-gear me-2"></i> Pengaturan</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i> Logout</a></li>
                        </ul>
                    </div>
                </div>
            </header>

            <!-- Statistics Cards -->
            <div class="row g-4 mb-4">
                <div class="col-xl-3 col-md-6">
                    <div class="card-modern">
                        <div class="stat-card-body">
                            <div>
                                <div class="stat-value"><?php echo $stat_reservasi_aktif; ?></div>
                                <div class="stat-label">Reservasi Aktif</div>
                            </div>
                            <div class="stat-icon blue">
                                <i class="bi bi-calendar-check"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card-modern">
                        <div class="stat-card-body">
                            <div>
                                <div class="stat-value"><?php echo $stat_kamar_tersedia; ?></div>
                                <div class="stat-label">Kamar Tersedia</div>
                            </div>
                            <div class="stat-icon green">
                                <i class="bi bi-door-open"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card-modern">
                        <div class="stat-card-body">
                            <div>
                                <div class="stat-value"><?php echo $stat_tamu_hari_ini; ?></div>
                                <div class="stat-label">Tamu Check-in Hari Ini</div>
                            </div>
                            <div class="stat-icon orange">
                                <i class="bi bi-people"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card-modern">
                        <div class="stat-card-body">
                            <div>
                                <div class="stat-value"><?php echo $okupansi_percent; ?>%</div>
                                <div class="stat-label">Okupansi Bulan Ini</div>
                            </div>
                            <div class="stat-icon purple">
                                <i class="bi bi-graph-up-arrow"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content Row -->
            <div class="row g-4">
                <!-- Calendar View -->
                <div class="col-12 col-lg-7 col-xl-8">
                    <div class="calendar-wrapper">
                        <div class="calendar-header">
                            <div class="d-flex align-items-center">
                                <div class="bg-light rounded-circle p-2 me-3 text-primary">
                                    <i class="bi bi-calendar-range fs-5"></i>
                                </div>
                                <div>
                                    <h5 class="mb-0 fw-bold"><?php echo date('F Y', mktime(0, 0, 0, $current_month, 1, $current_year)); ?></h5>
                                    <small class="text-muted">Overview Ketersediaan</small>
                                </div>
                            </div>
                            <div class="btn-group shadow-sm" role="group">
                                <?php
                                $prev_month = $current_month - 1;
                                $prev_year = $current_year;
                                if ($prev_month < 1) {
                                    $prev_month = 12;
                                    $prev_year--;
                                }
                                $next_month = $current_month + 1;
                                $next_year = $current_year;
                                if ($next_month > 12) {
                                    $next_month = 1;
                                    $next_year++;
                                }
                                ?>
                                <a href="?month=<?php echo $prev_month; ?>&year=<?php echo $prev_year; ?>" class="btn btn-outline-light text-dark border-end-0">
                                    <i class="bi bi-chevron-left"></i>
                                </a>
                                <a href="?month=<?php echo date('n'); ?>&year=<?php echo date('Y'); ?>" class="btn btn-outline-light text-dark fw-medium">
                                    Hari Ini
                                </a>
                                <a href="?month=<?php echo $next_month; ?>&year=<?php echo $next_year; ?>" class="btn btn-outline-light text-dark border-start-0">
                                    <i class="bi bi-chevron-right"></i>
                                </a>
                            </div>
                        </div>

                        <div class="calendar-scroll">
                            <table class="calendar-table">
                                <thead>
                                    <tr>
                                        <th class="room-col">Kamar</th>
                                        <?php for ($day = 1; $day <= $days_in_month; $day++): 
                                            $day_name = date('D', mktime(0, 0, 0, $current_month, $day, $current_year));
                                            $is_today = ($day == date('d') && $current_month == date('n') && $current_year == date('Y'));
                                        ?>
                                            <th class="<?php echo $is_today ? 'text-primary' : ''; ?>" style="min-width: 60px;">
                                                <div style="font-size: 1.1rem;"><?php echo $day; ?></div>
                                                <small class="text-muted fw-normal" style="font-size: 0.7rem; text-transform: uppercase;"><?php echo $day_name; ?></small>
                                            </th>
                                        <?php endfor; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($kamar_list)): ?>
                                        <tr>
                                            <td colspan="<?php echo $days_in_month + 1; ?>" class="text-center py-5 text-muted">
                                                <i class="bi bi-inbox fs-1 opacity-25"></i>
                                                <p class="mt-3">Belum ada kamar yang terdaftar. Silakan tambahkan kamar di menu Pengaturan.</p>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($kamar_list as $kamar): 
                                            $kamar_id = $kamar['id_kamar'];
                                            $kamar_reservasi = $reservasi_map[$kamar_id] ?? [];
                                        ?>
                                            <tr>
                                                <td class="room-col">
                                                    <div class="d-flex flex-column">
                                                        <span><?php echo htmlspecialchars($kamar['nama_kamar']); ?></span>
                                                        <small class="text-muted fw-normal" style="font-size: 0.75rem;"><?php echo htmlspecialchars($kamar['nama_properti']); ?></small>
                                                    </div>
                                                </td>
                                                <?php for ($day = 1; $day <= $days_in_month; $day++): 
                                                    $current_date = sprintf('%04d-%02d-%02d', $current_year, $current_month, $day);
                                                    $is_today = ($day == date('d') && $current_month == date('n') && $current_year == date('Y'));
                                                    
                                                    // Cari reservasi yang aktif di tanggal ini
                                                    $active_booking = null;
                                                    foreach ($kamar_reservasi as $res) {
                                                        // Bandingkan hanya bagian tanggal (YYYY-MM-DD)
                                                        $checkin_date_part = substr($res['tgl_checkin'], 0, 10);
                                                        $checkout_date_part = substr($res['tgl_checkout'], 0, 10);
                                                        
                                                        if (($current_date >= $checkin_date_part && $current_date < $checkout_date_part) || ($checkin_date_part === $checkout_date_part && $current_date === $checkin_date_part)) {
                                                            $active_booking = $res;
                                                            break;
                                                        }
                                                    }
                                                ?>
                                                    <td class="calendar-day-cell <?php echo $is_today ? 'today' : ''; ?>">
                                                        <!-- <div class="calendar-day-number"><?php echo $day; ?></div> -->
                                                        <?php if ($active_booking): 
                                                            $status_class = '';
                                                            if ($active_booking['status_booking'] == 'Checked-in') $status_class = 'checked-in';
                                                            elseif ($active_booking['status_booking'] == 'Booking') $status_class = 'booking';
                                                            if ($active_booking['status_booking'] == 'Checked-out') $status_class = 'checked-out';
                                                            
                                                            $payment_class = '';
                                                            if ($active_booking['status_pembayaran'] == 'Lunas') $payment_class = 'lunas';
                                                            if ($active_booking['status_pembayaran'] == 'Belum Bayar') $payment_class = 'belum-bayar';
                                                        ?>
                                                            <div class="booking-pill <?php echo $status_class . ' ' . $payment_class; ?>" 
                                                                 title="<?php echo htmlspecialchars($active_booking['nama_tamu']); ?> - <?php echo htmlspecialchars($active_booking['nama_kamar']); ?>">
                                                                <?php echo htmlspecialchars(substr($active_booking['nama_tamu'], 0, 12)); ?>..
                                                            </div>
                                                        <?php else: ?>
                                                            
                                                        <?php endif; ?>
                                                    </td>
                                                <?php endfor; ?>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Sidebar Widget -->
                <div class="col-12 col-lg-5 col-xl-4">
                    <div class="card-modern p-4">
                        <div class="widget-header">
                            <h5 class="widget-title">Reservasi Mendatang</h5>
                            <a href="#" class="text-decoration-none text-primary small fw-bold">Lihat Semua</a>
                        </div>
                        <div>
                            <?php if ($result_reservasi_terbaru->num_rows > 0): ?>
                                <?php while ($row = $result_reservasi_terbaru->fetch_assoc()): ?>
                                    <div class="res-item">
                                        <div class="res-avatar">
                                            <?php echo strtoupper(substr($row['nama_lengkap'], 0, 1)); ?>
                                        </div>
                                        <div class="res-info">
                                            <h6><?php echo htmlspecialchars(substr($row['nama_lengkap'], 0, 15)); ?></h6>
                                            <p><?php echo htmlspecialchars($row['nama_kamar']); ?></p>
                                            <p class="text-primary mt-1" style="font-size: 0.7rem;">
                                                <i class="bi bi-calendar-event me-1"></i>
                                                <?php echo date('d M', strtotime($row['tgl_checkin'])); ?>
                                            </p>
                                        </div>
                                        <span class="res-status <?php echo strtolower(str_replace('-', '', $row['status_booking'])); ?>">
                                            <?php echo $row['status_booking']; ?>
                                        </span>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <div class="text-center py-4">
                                    <i class="bi bi-calendar-x text-muted fs-1 opacity-25"></i>
                                    <p class="text-muted small mt-2">Tidak ada reservasi mendatang</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const mobileSidebarToggleBtn = document.getElementById('mobile-sidebar-toggle');
            if (mobileSidebarToggleBtn) {
                mobileSidebarToggleBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    document.body.classList.toggle('sidebar-mobile-show');
                });
            }
            
            // Add a listener to close the sidebar when clicking on the overlay
            document.addEventListener('click', function(e) {
                if (document.body.classList.contains('sidebar-mobile-show') && !e.target.closest('#sidebarMenu') && !e.target.closest('#mobile-sidebar-toggle')) {
                    document.body.classList.remove('sidebar-mobile-show');
                }
            });
        });
    </script>
</body>
</html>
