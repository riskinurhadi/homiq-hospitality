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
$stat_tamu_total = $koneksi->query("SELECT COUNT(*) as total FROM tbl_tamu")->fetch_assoc()['total'];
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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --sidebar-width: 280px;
            --sidebar-bg: linear-gradient(180deg, #1e293b 0%, #0f172a 100%);
            --sidebar-text: rgba(255, 255, 255, 0.75);
            --sidebar-active: #ffffff;
            --sidebar-hover: rgba(255, 255, 255, 0.08);
            --primary-color: #3b82f6;
            --primary-dark: #2563eb;
            --bg-light: #f8fafc;
            --bg-white: #ffffff;
            --text-dark: #1e293b;
            --text-muted: #64748b;
            --border-color: #e2e8f0;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: var(--bg-light);
            color: var(--text-dark);
            overflow-x: hidden;
            width: 100%;
            max-width: 100vw;
        }

        html {
            overflow-x: hidden;
            width: 100%;
            max-width: 100vw;
        }

        /* SIDEBAR */
        .sidebar-modern.offcanvas {
            width: var(--sidebar-width);
            background: var(--sidebar-bg);
            border-right: none;
            box-shadow: 4px 0 20px rgba(0, 0, 0, 0.1);
        }

        .sidebar-modern .offcanvas-header {
            display: none;
        }

        .sidebar-modern .offcanvas-body {
            padding: 1.75rem 1rem;
            display: flex;
            flex-direction: column;
        }

        .sidebar-modern .sidebar-nav {
            list-style: none;
            padding: 0;
            margin: 0;
            flex-grow: 1;
        }

        .sidebar-modern .nav-item {
            margin-bottom: 0.5rem;
        }

        .sidebar-modern .nav-link {
            display: flex;
            align-items: center;
            padding: 0.875rem 1.25rem;
            color: var(--sidebar-text);
            text-decoration: none;
            border-radius: 0.875rem;
            font-size: 0.95rem;
            font-weight: 500;
            transition: all 0.2s ease;
            position: relative;
        }

        .sidebar-modern .nav-link::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 3px;
            background: var(--primary-color);
            transform: scaleY(0);
            transition: transform 0.2s ease;
        }

        .sidebar-modern .nav-link i {
            font-size: 1.25rem;
            width: 24px;
            margin-right: 0.875rem;
        }

        .sidebar-modern .nav-link:hover {
            background: var(--sidebar-hover);
            color: #ffffff;
            transform: translateX(4px);
        }

        .sidebar-modern .nav-link:hover::before {
            transform: scaleY(1);
        }

        .sidebar-modern .nav-link.active {
            background: var(--sidebar-active);
            color: var(--text-dark);
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .sidebar-modern .nav-link.active::before {
            transform: scaleY(1);
            background: var(--primary-color);
        }

        .sidebar-modern .nav-link.active i {
            color: var(--primary-color);
        }

        .sidebar-logout {
            margin-top: auto;
            padding-top: 1rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar-modern .nav-link-logout {
            color: rgba(255, 255, 255, 0.6);
        }

        .sidebar-modern .nav-link-logout:hover {
            background: rgba(220, 53, 69, 0.15);
            color: #ef4444;
        }

        @media (min-width: 992px) {
            .sidebar-modern.offcanvas {
                position: fixed;
                top: 0;
                left: 0;
                bottom: 0;
                transform: none !important;
                visibility: visible !important;
            }
        }

        /* MAIN CONTENT */
        #main-content {
            margin-left: 0;
            padding: 1rem;
            transition: margin-left 0.3s ease;
            width: 100%;
            max-width: 100%;
            overflow-x: hidden;
        }

        @media (min-width: 992px) {
            #main-content {
                margin-left: var(--sidebar-width);
                width: calc(100% - var(--sidebar-width));
            }
        }

        @media (max-width: 991.98px) {
            #main-content {
                padding: 1rem;
            }
        }

        /* HEADER */
        .main-header {
            background: var(--bg-white);
            padding: 1.25rem 1.5rem;
            border-radius: 1.25rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            margin-bottom: 1.5rem;
            border: 1px solid var(--border-color);
            width: 100%;
            max-width: 100%;
        }

        .main-header h5 {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-dark);
            margin: 0;
        }

        @media (max-width: 768px) {
            .main-header {
                padding: 1rem 1.25rem;
            }

            .main-header h5 {
                font-size: 1.25rem;
            }

            .main-header .d-flex {
                flex-direction: column;
                align-items: flex-start !important;
                gap: 1rem;
            }
        }

        /* STAT CARDS */
        .stat-card {
            background: var(--bg-white);
            border-radius: 1.25rem;
            padding: 1.25rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            border: 1px solid var(--border-color);
            height: 100%;
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
        }

        .stat-icon-circle {
            width: 56px;
            height: 56px;
            border-radius: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }

        .stat-icon-circle.blue { background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%); color: var(--primary-color); }
        .stat-icon-circle.green { background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%); color: var(--success-color); }
        .stat-icon-circle.orange { background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); color: var(--warning-color); }
        .stat-icon-circle.purple { background: linear-gradient(135deg, #ede9fe 0%, #ddd6fe 100%); color: #8b5cf6; }

        .stat-card h3 {
            font-size: 1.75rem;
            font-weight: 800;
            margin: 0;
            color: var(--text-dark);
        }

        .stat-card p {
            font-size: 0.9rem;
            color: var(--text-muted);
            margin: 0.5rem 0 0 0;
            font-weight: 500;
        }

        /* CALENDAR VIEW */
        .calendar-container {
            background: var(--bg-white);
            border-radius: 1.25rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            border: 1px solid var(--border-color);
            overflow: hidden;
            margin-top: 2rem;
            width: 100%;
            max-width: 100%;
        }

        .calendar-wrapper {
            overflow-x: auto;
            overflow-y: auto;
            max-height: 70vh;
            -webkit-overflow-scrolling: touch;
        }

        .calendar-wrapper::-webkit-scrollbar {
            height: 8px;
            width: 8px;
        }

        .calendar-wrapper::-webkit-scrollbar-track {
            background: var(--bg-light);
            border-radius: 4px;
        }

        .calendar-wrapper::-webkit-scrollbar-thumb {
            background: var(--border-color);
            border-radius: 4px;
        }

        .calendar-wrapper::-webkit-scrollbar-thumb:hover {
            background: var(--text-muted);
        }

        .calendar-header {
            padding: 1.125rem 1.5rem;
            border-bottom: 2px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            flex-wrap: wrap;
            gap: 1rem;
        }

        @media (max-width: 768px) {
            .calendar-header {
                padding: 1rem;
                flex-direction: column;
                align-items: flex-start;
            }

            .calendar-header h4 {
                font-size: 1rem;
            }

            .calendar-nav {
                width: 100%;
                justify-content: space-between;
            }

            .calendar-nav .btn {
                flex: 1;
                font-size: 0.85rem;
                padding: 0.4rem 0.75rem;
            }
        }

        .calendar-header h4 {
            font-size: 1.25rem;
            font-weight: 700;
            margin: 0;
            color: var(--text-dark);
        }

        .calendar-nav {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }

        .calendar-nav .btn {
            border-radius: 0.5rem;
            border: 1px solid var(--border-color);
            padding: 0.5rem 1rem;
            font-weight: 600;
            transition: all 0.2s ease;
        }

        .calendar-nav .btn:hover {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }

        .calendar-table {
            width: max-content;
            min-width: 100%;
            border-collapse: collapse;
        }

        .calendar-table th {
            background: var(--bg-light);
            padding: 0.75rem;
            text-align: center;
            font-weight: 600;
            font-size: 0.85rem;
            color: var(--text-muted);
            border-bottom: 2px solid var(--border-color);
            position: sticky;
            top: 0;
            z-index: 10;
            white-space: nowrap;
        }

        .calendar-table th.room-name-cell {
            min-width: 200px;
            max-width: 200px;
        }

        .calendar-table td {
            padding: 0.5rem;
            border: 1px solid var(--border-color);
            vertical-align: top;
            min-width: 90px;
            max-width: 110px;
            width: 110px;
        }

        .room-name-cell {
            background: var(--bg-light);
            font-weight: 600;
            font-size: 0.9rem;
            padding: 0.75rem !important;
            position: sticky;
            left: 0;
            z-index: 15;
            border-right: 2px solid var(--border-color);
            box-shadow: 2px 0 4px rgba(0, 0, 0, 0.1);
            min-width: 180px;
            max-width: 180px;
            width: 180px;
        }

        .room-name-cell small {
            display: block;
            font-weight: 400;
            color: var(--text-muted);
            font-size: 0.75rem;
        }

        .calendar-day {
            min-height: 80px;
            position: relative;
            width: 100%;
        }

        @media (max-width: 768px) {
            .calendar-table td {
                min-width: 80px;
                max-width: 80px;
                width: 80px;
            }

            .calendar-day {
                min-height: 60px;
            }

            .booking-block {
                font-size: 0.65rem;
                padding: 0.2rem 0.4rem;
            }

            .calendar-day-number {
                font-size: 0.75rem;
            }

            .room-name-cell {
                min-width: 150px;
                max-width: 150px;
                width: 150px;
                font-size: 0.8rem;
            }
        }

        /* Laptop compact sizing */
        @media (max-width: 1440px) {
            #main-content { padding: 1.25rem; }
            .main-header { padding: 1rem 1.25rem; margin-bottom: 1.25rem; }
            .stat-card { padding: 1rem; }
            .stat-card h3 { font-size: 1.5rem; }
            .stat-icon-circle { width: 48px; height: 48px; font-size: 1.25rem; }
            .calendar-header { padding: 1rem 1.25rem; }
            .calendar-table td { min-width: 85px; max-width: 100px; width: 100px; }
            .room-name-cell { min-width: 160px; max-width: 160px; width: 160px; }
            .calendar-day { min-height: 70px; }
        }

        /* Very common laptop size */
        @media (max-width: 1366px) {
            html, body { font-size: 14px; }
            #main-content { padding: 1rem; }
            .main-header { padding: 0.875rem 1rem; margin-bottom: 1rem; }
            .main-header h5 { font-size: 1.1rem; }
            .stat-card { padding: 0.875rem; }
            .stat-icon-circle { width: 42px; height: 42px; font-size: 1.1rem; }
            .stat-card h3 { font-size: 1.35rem; }
            .calendar-header { padding: 0.75rem 1rem; }
            .calendar-header h4 { font-size: 1.05rem; }
            .calendar-nav .btn { padding: 0.35rem 0.6rem; font-size: 0.85rem; }
            .calendar-table th { padding: 0.5rem; }
            .calendar-table td { min-width: 80px; max-width: 95px; width: 95px; padding: 0.4rem; }
            .room-name-cell { min-width: 150px; max-width: 150px; width: 150px; }
            .booking-block { font-size: 0.7rem; padding: 0.2rem 0.4rem; }
        }

        .calendar-day-number {
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text-muted);
            margin-bottom: 0.25rem;
        }

        .booking-block {
            background: var(--primary-color);
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 0.375rem;
            font-size: 0.75rem;
            margin-bottom: 0.25rem;
            cursor: pointer;
            transition: all 0.2s ease;
            position: relative;
            overflow: hidden;
        }

        .booking-block:hover {
            transform: scale(1.02);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
        }

        .booking-block.checked-in {
            background: var(--success-color);
        }

        .booking-block.checked-out {
            background: var(--text-muted);
            opacity: 0.7;
        }

        .booking-block.lunas {
            border-left: 3px solid #10b981;
        }

        .booking-block.belum-bayar {
            border-left: 3px solid var(--danger-color);
        }

        .booking-block-name {
            font-weight: 600;
            display: block;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .booking-block-detail {
            font-size: 0.7rem;
            opacity: 0.9;
        }

        .empty-day {
            color: var(--text-muted);
            font-size: 0.75rem;
        }

        /* WIDGET CARDS */
        .widget-card {
            background: var(--bg-white);
            border-radius: 1.25rem;
            padding: 1.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            border: 1px solid var(--border-color);
            height: 100%;
        }

        .widget-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--border-color);
        }

        .widget-card-header h5 {
            font-size: 1.1rem;
            font-weight: 700;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .widget-card-header h5 i {
            color: var(--primary-color);
        }

        .reservation-item {
            padding: 0.75rem;
            border-radius: 0.5rem;
            margin-bottom: 0.5rem;
            background: var(--bg-light);
            transition: all 0.2s ease;
        }

        .reservation-item:hover {
            background: #e2e8f0;
            transform: translateX(4px);
        }

        .reservation-item strong {
            display: block;
            font-size: 0.9rem;
            color: var(--text-dark);
        }

        .reservation-item small {
            color: var(--text-muted);
            font-size: 0.8rem;
        }

        .badge-status {
            padding: 0.25rem 0.5rem;
            border-radius: 0.375rem;
            font-size: 0.7rem;
            font-weight: 600;
        }

        .badge-status.booking { background: #dbeafe; color: var(--primary-color); }
        .badge-status.checked-in { background: #d1fae5; color: var(--success-color); }

        /* RESPONSIVE STAT CARDS */
        @media (max-width: 768px) {
            .stat-card {
                padding: 1rem;
            }

            .stat-card h3 {
                font-size: 1.5rem;
            }

            .stat-icon-circle {
                width: 48px;
                height: 48px;
                font-size: 1.25rem;
            }

            .widget-card {
                padding: 1rem;
            }
        }

        /* FIX CONTAINER OVERFLOW */
        .row {
            margin-left: 0;
            margin-right: 0;
        }

        .row > * {
            padding-left: 0.75rem;
            padding-right: 0.75rem;
        }

        @media (max-width: 991.98px) {
            .row > * {
                padding-left: 0.5rem;
                padding-right: 0.5rem;
            }
        }

        /* ENSURE NO HORIZONTAL OVERFLOW */
        .d-flex {
            max-width: 100%;
        }

        .container-fluid,
        .container {
            max-width: 100%;
            overflow-x: hidden;
        }
    </style>
</head>
<body>
    <div class="d-flex">
        <!-- SIDEBAR -->
        <?php include 'sidebar.php'; ?>

        <!-- MAIN CONTENT -->
        <div id="main-content" style="flex: 1; width: 100%;">
            <!-- Header -->
            <header class="main-header d-flex justify-content-between align-items-center">
                <div>
                    <h5>Selamat Datang, <?php echo htmlspecialchars($nama_lengkap); ?>! 👋</h5>
                    <small class="text-muted">Dashboard - Ketersediaan Kamar</small>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <button class="btn btn-outline-secondary d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarMenu">
                        <i class="bi bi-list"></i>
                    </button>
                    <div class="dropdown">
                        <a class="dropdown-toggle d-flex align-items-center text-decoration-none text-dark" href="#" role="button" data-bs-toggle="dropdown">
                            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($nama_lengkap); ?>&background=3b82f6&color=fff&size=128&bold=true" 
                                 alt="User" style="width: 40px; height: 40px; border-radius: 50%;">
                            <span class="ms-2 d-none d-md-inline"><?php echo htmlspecialchars($nama_lengkap); ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i> Logout</a></li>
                        </ul>
                    </div>
                </div>
            </header>

            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="stat-card">
                        <div class="stat-icon-circle blue">
                            <i class="bi bi-calendar2-check"></i>
                        </div>
                        <h3><?php echo $stat_reservasi_aktif; ?></h3>
                        <p>Reservasi Aktif</p>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="stat-card">
                        <div class="stat-icon-circle green">
                            <i class="bi bi-door-open"></i>
                        </div>
                        <h3><?php echo $stat_kamar_tersedia; ?></h3>
                        <p>Kamar Tersedia</p>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="stat-card">
                        <div class="stat-icon-circle orange">
                            <i class="bi bi-people"></i>
                        </div>
                        <h3><?php echo $stat_tamu_total; ?></h3>
                        <p>Total Tamu</p>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="stat-card">
                        <div class="stat-icon-circle purple">
                            <i class="bi bi-graph-up"></i>
                        </div>
                        <h3><?php echo $okupansi_percent; ?>%</h3>
                        <p>Okupansi Bulan Ini</p>
                    </div>
                </div>
            </div>

            <!-- Main Content Row -->
            <div class="row">
                <!-- Calendar View -->
                <div class="col-12 col-lg-9 mb-4">
                    <div class="calendar-container">
                        <div class="calendar-header">
                            <h4>
                                <i class="bi bi-calendar3 me-2"></i>
                                <?php echo date('F Y', mktime(0, 0, 0, $current_month, 1, $current_year)); ?>
                            </h4>
                            <div class="calendar-nav">
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
                                <a href="?month=<?php echo date('n'); ?>&year=<?php echo date('Y'); ?>" class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-calendar-today"></i> Hari Ini
                                </a>
                                <a href="?month=<?php echo $prev_month; ?>&year=<?php echo $prev_year; ?>" class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-chevron-left"></i>
                                </a>
                                <a href="?month=<?php echo $next_month; ?>&year=<?php echo $next_year; ?>" class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-chevron-right"></i>
                                </a>
                            </div>
                        </div>

                        <div class="calendar-wrapper">
                            <table class="calendar-table">
                                <thead>
                                    <tr>
                                        <th class="room-name-cell" style="min-width: 200px;">Kamar</th>
                                        <?php for ($day = 1; $day <= $days_in_month; $day++): 
                                            $day_name = date('D', mktime(0, 0, 0, $current_month, $day, $current_year));
                                            $is_today = ($day == date('d') && $current_month == date('n') && $current_year == date('Y'));
                                        ?>
                                            <th class="<?php echo $is_today ? 'bg-primary text-white' : ''; ?>">
                                                <?php echo $day; ?><br>
                                                <small style="font-weight: 400;"><?php echo $day_name; ?></small>
                                            </th>
                                        <?php endfor; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($kamar_list)): ?>
                                        <tr>
                                            <td colspan="<?php echo $days_in_month + 1; ?>" class="text-center py-5 text-muted">
                                                <i class="bi bi-inbox" style="font-size: 3rem; opacity: 0.3;"></i>
                                                <p class="mt-3">Belum ada kamar yang terdaftar. Silakan tambahkan kamar di menu Pengaturan.</p>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($kamar_list as $kamar): 
                                            $kamar_id = $kamar['id_kamar'];
                                            $kamar_reservasi = $reservasi_map[$kamar_id] ?? [];
                                        ?>
                                            <tr>
                                                <td class="room-name-cell">
                                                    <?php echo htmlspecialchars($kamar['nama_kamar']); ?>
                                                    <small><?php echo htmlspecialchars($kamar['nama_properti']); ?></small>
                                                </td>
                                                <?php for ($day = 1; $day <= $days_in_month; $day++): 
                                                    $current_date = sprintf('%04d-%02d-%02d', $current_year, $current_month, $day);
                                                    $is_today = ($day == date('d') && $current_month == date('n') && $current_year == date('Y'));
                                                    
                                                    // Cari reservasi yang aktif di tanggal ini
                                                    $active_booking = null;
                                                    foreach ($kamar_reservasi as $res) {
                                                        if ($current_date >= $res['tgl_checkin'] && $current_date < $res['tgl_checkout']) {
                                                            $active_booking = $res;
                                                            break;
                                                        }
                                                    }
                                                ?>
                                                    <td class="calendar-day <?php echo $is_today ? 'bg-light' : ''; ?>">
                                                        <div class="calendar-day-number"><?php echo $day; ?></div>
                                                        <?php if ($active_booking): 
                                                            $status_class = '';
                                                            if ($active_booking['status_booking'] == 'Checked-in') $status_class = 'checked-in';
                                                            if ($active_booking['status_booking'] == 'Checked-out') $status_class = 'checked-out';
                                                            
                                                            $payment_class = '';
                                                            if ($active_booking['status_pembayaran'] == 'Lunas') $payment_class = 'lunas';
                                                            if ($active_booking['status_pembayaran'] == 'Belum Bayar') $payment_class = 'belum-bayar';
                                                        ?>
                                                            <div class="booking-block <?php echo $status_class . ' ' . $payment_class; ?>" 
                                                                 title="<?php echo htmlspecialchars($active_booking['nama_tamu']); ?> - <?php echo htmlspecialchars($active_booking['nama_kamar']); ?>">
                                                                <span class="booking-block-name"><?php echo htmlspecialchars(substr($active_booking['nama_tamu'], 0, 15)); ?></span>
                                                                <span class="booking-block-detail"><?php echo htmlspecialchars($active_booking['platform_booking'] ?? 'OTS'); ?></span>
                                                            </div>
                                                        <?php else: ?>
                                                            <div class="empty-day"></div>
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
                <div class="col-12 col-lg-3">
                    <div class="widget-card mb-4">
                        <div class="widget-card-header">
                            <h5><i class="bi bi-calendar-event"></i> Reservasi Mendatang</h5>
                        </div>
                        <div>
                            <?php if ($result_reservasi_terbaru->num_rows > 0): ?>
                                <?php while ($row = $result_reservasi_terbaru->fetch_assoc()): ?>
                                    <div class="reservation-item">
                                        <strong><?php echo htmlspecialchars($row['nama_lengkap']); ?></strong>
                                        <small>
                                            <i class="bi bi-door-open me-1"></i><?php echo htmlspecialchars($row['nama_kamar']); ?>
                                            <br>
                                            <i class="bi bi-calendar3 me-1"></i><?php echo date('d M Y', strtotime($row['tgl_checkin'])); ?>
                                        </small>
                                        <span class="badge-status <?php echo strtolower(str_replace('-', '', $row['status_booking'])); ?> mt-1">
                                            <?php echo $row['status_booking']; ?>
                                        </span>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <p class="text-muted text-center py-3">Tidak ada reservasi mendatang</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

