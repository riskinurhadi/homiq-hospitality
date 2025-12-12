<?php
// daftar_reservasi.php
// Daftar semua reservasi dengan filter dan pencarian

require_once 'auth_check.php';

// Cek apakah user adalah admin atau front_office
if (!in_array($role_user, ['admin', 'front_office'])) {
    header("Location: dashboard.php");
    exit();
}

require_once 'koneksi.php';

// Filter dan pencarian
$filter_status = $_GET['status'] ?? '';
$filter_platform = $_GET['platform'] ?? '';
$filter_bulan = $_GET['bulan'] ?? date('Y-m');
$search = $_GET['search'] ?? '';

// Build query
$where_conditions = [];
$params = [];
$types = '';

if (!empty($filter_status)) {
    $where_conditions[] = "r.status_booking = ?";
    $params[] = $filter_status;
    $types .= 's';
}

if (!empty($filter_platform)) {
    $where_conditions[] = "r.platform_booking = ?";
    $params[] = $filter_platform;
    $types .= 's';
}

if (!empty($filter_bulan)) {
    $where_conditions[] = "DATE_FORMAT(r.tgl_checkin, '%Y-%m') = ?";
    $params[] = $filter_bulan;
    $types .= 's';
}

if (!empty($search)) {
    $where_conditions[] = "(t.nama_lengkap LIKE ? OR t.no_hp LIKE ? OR k.nama_kamar LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= 'sss';
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Query untuk ambil reservasi
$query = "SELECT r.*, 
          t.nama_lengkap, t.no_hp, t.email,
          k.nama_kamar, k.tipe_kamar,
          p.nama_properti,
          u.nama_lengkap as operator_nama
          FROM tbl_reservasi r
          JOIN tbl_tamu t ON r.id_tamu = t.id_tamu
          JOIN tbl_kamar k ON r.id_kamar = k.id_kamar
          JOIN tbl_properti p ON k.id_properti = p.id_properti
          LEFT JOIN tbl_users u ON r.dibuat_oleh_user = u.id_user
          $where_clause
          ORDER BY r.tgl_checkin DESC, r.dibuat_pada DESC
          LIMIT 100";

$stmt = $koneksi->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result_reservasi = $stmt->get_result();

// Ambil statistik untuk filter
$stat_total = $koneksi->query("SELECT COUNT(*) as total FROM tbl_reservasi")->fetch_assoc()['total'];
$stat_booking = $koneksi->query("SELECT COUNT(*) as total FROM tbl_reservasi WHERE status_booking = 'Booking'")->fetch_assoc()['total'];
$stat_checked_in = $koneksi->query("SELECT COUNT(*) as total FROM tbl_reservasi WHERE status_booking = 'Checked-in'")->fetch_assoc()['total'];

$koneksi->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Reservasi - CMS Guesthouse Adiputra</title>
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
            --primary-color: #3b82f6;
            --bg-light: #f8fafc;
            --bg-white: #ffffff;
            --text-dark: #1e293b;
            --text-muted: #64748b;
            --border-color: #e2e8f0;
            --success-color: #10b981;
            --danger-color: #ef4444;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: var(--bg-light);
            color: var(--text-dark);
            overflow-x: hidden;
        }

        /* SIDEBAR */
        .sidebar-modern.offcanvas {
            width: var(--sidebar-width);
            background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%);
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
            color: rgba(255, 255, 255, 0.75);
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
            background: rgba(255, 255, 255, 0.08);
            color: #ffffff;
            transform: translateX(4px);
        }

        .sidebar-modern .nav-link:hover::before {
            transform: scaleY(1);
        }

        .sidebar-modern .nav-link.active {
            background: #ffffff;
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

        .sidebar-modern .nav-link.active-submenu {
            background: rgba(255, 255, 255, 0.1);
            color: #ffffff;
            padding-left: 2.5rem;
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

        .sidebar-modern .collapse .nav-link {
            padding-left: 2.5rem;
            font-size: 0.9rem;
        }

        /* MAIN CONTENT */
        #main-content {
            margin-left: 0;
            padding: 1.5rem;
            transition: margin-left 0.3s ease;
            width: 100%;
            max-width: 100%;
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

        .main-header {
            background: var(--bg-white);
            padding: 1.25rem 1.5rem;
            border-radius: 1.25rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            margin-bottom: 1.5rem;
            border: 1px solid var(--border-color);
        }

        .content-card {
            background: var(--bg-white);
            border-radius: 1.25rem;
            padding: 1.25rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            border: 1px solid var(--border-color);
            margin-bottom: 1.5rem;
        }

        .filter-card {
            background: var(--bg-light);
            border-radius: 1rem;
            padding: 1rem;
            margin-bottom: 1.25rem;
        }

        .table-modern {
            width: 100%;
        }

        .table-modern thead th {
            background: var(--bg-light);
            font-weight: 600;
            font-size: 0.85rem;
            color: var(--text-muted);
            border-bottom: 2px solid var(--border-color);
            padding: 0.8rem;
        }

        .table-modern tbody td {
            padding: 0.8rem;
            vertical-align: middle;
            border-bottom: 1px solid var(--border-color);
        }

        .table-modern tbody tr:hover {
            background: var(--bg-light);
            cursor: pointer;
        }

        .badge-status {
            padding: 0.5rem 0.75rem;
            border-radius: 0.5rem;
            font-weight: 600;
            font-size: 0.85rem;
        }

        .badge-status.booking { background: #dbeafe; color: var(--primary-color); }
        .badge-status.checked-in { background: #d1fae5; color: var(--success-color); }
        .badge-status.checked-out { background: #e2e8f0; color: var(--text-muted); }
        .badge-status.canceled { background: #fee2e2; color: var(--danger-color); }

        .badge-payment.lunas { background: #d1fae5; color: var(--success-color); }
        .badge-payment.dp { background: #fef3c7; color: #f59e0b; }
        .badge-payment.belum-bayar { background: #fee2e2; color: var(--danger-color); }

        /* Laptop compact */
        @media (max-width: 1366px) {
            html, body { font-size: 14px; }
            #main-content { padding: 1.1rem; }
            .main-header { padding: 1rem 1.25rem; }
            .filter-card { padding: 0.75rem; }
            .table-modern thead th { padding: 0.65rem; font-size: 0.8rem; }
            .table-modern tbody td { padding: 0.65rem; }
            .btn, .form-select, .form-control { font-size: 0.9rem; }
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
                    <h5><i class="bi bi-calendar-check me-2"></i>Daftar Reservasi</h5>
                    <small class="text-muted">Kelola semua reservasi</small>
                </div>
                <div>
                    <a href="form_input_booking.php" class="btn btn-primary">
                        <i class="bi bi-plus-lg me-2"></i>Tambah Booking
                    </a>
                    <button class="btn btn-outline-secondary d-lg-none ms-2" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarMenu">
                        <i class="bi bi-list"></i>
                    </button>
                </div>
            </header>

            <!-- Filter Card -->
            <div class="filter-card">
                <form method="GET" action="" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label small text-muted">Status Booking</label>
                        <select class="form-select form-select-sm" name="status">
                            <option value="">Semua Status</option>
                            <option value="Booking" <?php echo $filter_status == 'Booking' ? 'selected' : ''; ?>>Booking</option>
                            <option value="Checked-in" <?php echo $filter_status == 'Checked-in' ? 'selected' : ''; ?>>Checked-in</option>
                            <option value="Checked-out" <?php echo $filter_status == 'Checked-out' ? 'selected' : ''; ?>>Checked-out</option>
                            <option value="Canceled" <?php echo $filter_status == 'Canceled' ? 'selected' : ''; ?>>Canceled</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small text-muted">Platform</label>
                        <select class="form-select form-select-sm" name="platform">
                            <option value="">Semua Platform</option>
                            <option value="OTS" <?php echo $filter_platform == 'OTS' ? 'selected' : ''; ?>>OTS</option>
                            <option value="Internal" <?php echo $filter_platform == 'Internal' ? 'selected' : ''; ?>>Internal</option>
                            <option value="Agoda" <?php echo $filter_platform == 'Agoda' ? 'selected' : ''; ?>>Agoda</option>
                            <option value="Booking.com" <?php echo $filter_platform == 'Booking.com' ? 'selected' : ''; ?>>Booking.com</option>
                            <option value="Traveloka" <?php echo $filter_platform == 'Traveloka' ? 'selected' : ''; ?>>Traveloka</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small text-muted">Bulan</label>
                        <input type="month" class="form-control form-control-sm" name="bulan" value="<?php echo htmlspecialchars($filter_bulan); ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small text-muted">Pencarian</label>
                        <div class="input-group input-group-sm">
                            <input type="text" class="form-control" name="search" placeholder="Nama, No HP, Kamar..." value="<?php echo htmlspecialchars($search); ?>">
                            <button class="btn btn-primary" type="submit">
                                <i class="bi bi-search"></i>
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Table Card -->
            <div class="content-card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="mb-0">Total: <?php echo $result_reservasi->num_rows; ?> reservasi</h6>
                    <div>
                        <span class="badge bg-primary me-2">Total: <?php echo $stat_total; ?></span>
                        <span class="badge bg-info me-2">Booking: <?php echo $stat_booking; ?></span>
                        <span class="badge bg-success">Checked-in: <?php echo $stat_checked_in; ?></span>
                    </div>
                </div>

                <?php if ($result_reservasi->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-modern">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Tamu</th>
                                    <th>Kamar</th>
                                    <th>Check-in</th>
                                    <th>Check-out</th>
                                    <th>Durasi</th>
                                    <th>Platform</th>
                                    <th>Status</th>
                                    <th>Pembayaran</th>
                                    <th>Harga</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $result_reservasi->fetch_assoc()): 
                                    $checkin = new DateTime($row['tgl_checkin']);
                                    $checkout = new DateTime($row['tgl_checkout']);
                                    $durasi = $checkin->diff($checkout)->days;
                                ?>
                                    <tr onclick="window.location='detail_reservasi.php?id=<?php echo $row['id_reservasi']; ?>'">
                                        <td><strong>#<?php echo $row['id_reservasi']; ?></strong></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($row['nama_lengkap']); ?></strong><br>
                                            <small class="text-muted"><?php echo htmlspecialchars($row['no_hp']); ?></small>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($row['nama_kamar']); ?><br>
                                            <small class="text-muted"><?php echo htmlspecialchars($row['nama_properti']); ?></small>
                                        </td>
                                        <td><?php echo date('d M Y', strtotime($row['tgl_checkin'])); ?></td>
                                        <td><?php echo date('d M Y', strtotime($row['tgl_checkout'])); ?></td>
                                        <td><?php echo $durasi; ?> malam</td>
                                        <td>
                                            <span class="badge bg-secondary"><?php echo htmlspecialchars($row['platform_booking']); ?></span>
                                        </td>
                                        <td>
                                            <span class="badge-status <?php echo strtolower(str_replace('-', '', $row['status_booking'])); ?>">
                                                <?php echo $row['status_booking']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge-payment <?php echo strtolower(str_replace(' ', '-', $row['status_pembayaran'])); ?>">
                                                <?php echo $row['status_pembayaran']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <strong>Rp <?php echo number_format($row['harga_total'], 0, ',', '.'); ?></strong>
                                        </td>
                                        <td>
                                            <a href="detail_reservasi.php?id=<?php echo $row['id_reservasi']; ?>" 
                                               class="btn btn-sm btn-outline-primary" 
                                               onclick="event.stopPropagation();">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="bi bi-inbox" style="font-size: 3rem; color: var(--text-muted); opacity: 0.3;"></i>
                        <p class="text-muted mt-3">Tidak ada reservasi ditemukan.</p>
                        <a href="form_input_booking.php" class="btn btn-primary mt-2">
                            <i class="bi bi-plus-lg me-2"></i>Tambah Booking Baru
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

