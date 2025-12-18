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
} else {
    // Tampilkan semua status kecuali 'Checked-out' dan 'Canceled' jika tidak ada filter
    $where_conditions[] = "r.status_booking NOT IN ('Checked-out', 'Canceled')";
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
            --sidebar-width-minimized: 90px;
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
            display: flex;
            min-height: 100vh;
        }

        #main-content {
            transition: margin-left 0.3s ease, width 0.3s ease;
            width: 100%;
            margin-left: var(--sidebar-width);
        }
        
        body.sidebar-minimized #sidebarMenu {
            width: var(--sidebar-width-minimized);
        }

        body.sidebar-minimized #main-content {
            margin-left: var(--sidebar-width-minimized);
        }

        body.sidebar-minimized #sidebarMenu .menu-text,
        body.sidebar-minimized #sidebarMenu .nav-link .bi-chevron-down {
            opacity: 0;
            width: 0;
            visibility: hidden;
        }

        body.sidebar-minimized #sidebarMenu .sidebar-header {
            justify-content: center !important;
        }
        
        body.sidebar-minimized #sidebarMenu .sidebar-header .bi {
             margin-right: 0 !important;
        }
        
        body.sidebar-minimized #sidebarMenu .nav-link {
            justify-content: center;
        }
        
        body.sidebar-minimized #sidebarMenu .nav-link i {
            margin-right: 0;
        }
        
        body.sidebar-minimized #sidebarMenu .collapse {
            display: none !important;
        }
        
        body.sidebar-minimized #sidebarMenu .sidebar-footer {
            flex-direction: column;
            gap: 0.5rem;
        }

        body.sidebar-minimized #sidebar-toggle i {
            transform: rotate(180deg);
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

            #sidebarMenu {
                transform: translateX(-100%);
                transition: transform 0.3s ease-in-out;
                z-index: 1045;
            }

            body.sidebar-mobile-show #sidebarMenu {
                transform: translateX(0);
            }
            
            body.sidebar-mobile-show::before {
                content: '';
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0,0,0,0.5);
                z-index: 1040;
            }

            .mobile-toggle-btn {
                display: block;
            }
        }
        
        .content-card {
            background: var(--card-bg);
            border-radius: var(--radius-lg);
            padding: 1.5rem;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-color);
            margin-bottom: 1.5rem;
        }

        .filter-card {
            background: var(--card-bg);
            border-radius: var(--radius-lg);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-color);
        }

        .table-modern {
            width: 100%;
        }

        .table-modern thead th {
            background: #f8fafc;
            font-weight: 600;
            font-size: 0.85rem;
            color: var(--text-muted);
            border-bottom: 2px solid var(--border-color);
            padding: 1rem 1.25rem;
        }

        .table-modern tbody td {
            padding: 1rem 1.25rem;
            vertical-align: middle;
            border-bottom: 1px solid var(--border-color);
        }

        .table-modern tbody tr:hover {
            background: #f8fafc;
            cursor: pointer;
        }

        .badge-status {
            padding: 0.5rem 0.75rem;
            border-radius: 0.5rem;
            font-weight: 600;
            font-size: 0.85rem;
        }

        .badge-status.booking { background: #e0e7ff; color: var(--primary); }
        .badge-status.checked-in { background: #d1fae5; color: var(--success); }
        .badge-status.checked-out { background: #e2e8f0; color: var(--text-muted); }
        .badge-status.canceled { background: #ffe4e6; color: var(--danger); }

        .badge-payment.lunas { background: #d1fae5; color: var(--success); }
        .badge-payment.dp { background: #fef3c7; color: #f59e0b; }
        .badge-payment.belum-bayar { background: #ffe4e6; color: var(--danger); }
    </style>
</head>
<body style="overflow-x: hidden;">
    
    <?php include 'sidebar.php'; ?>

    <main id="main-content" class="flex-grow-1 p-3 p-md-4">
        <header class="d-flex justify-content-between align-items-center mb-4">
             <div class="d-flex align-items-center">
                <button class="mobile-toggle-btn me-3" id="mobile-sidebar-toggle">
                    <i class="bi bi-list"></i>
                </button>
                <div>
                    <h4 class="fw-bold mb-1 text-dark">Daftar Reservasi</h4>
                    <p class="text-muted mb-0" style="font-size: 0.9rem;">Kelola semua data reservasi</p>
                </div>
            </div>
            <div>
                <a href="form_input_booking.php" class="btn btn-primary shadow-sm">
                    <i class="bi bi-plus-lg me-1"></i>Tambah Booking
                </a>
            </div>
        </header>

        <!-- Filter Card -->
        <div class="filter-card">
            <form method="GET" action="" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small fw-medium">Status Booking</label>
                    <select class="form-select" name="status">
                        <option value="">Semua Status</option>
                        <option value="Booking" <?php echo $filter_status == 'Booking' ? 'selected' : ''; ?>>Booking</option>
                        <option value="Checked-in" <?php echo $filter_status == 'Checked-in' ? 'selected' : ''; ?>>Checked-in</option>
                        <option value="Checked-out" <?php echo $filter_status == 'Checked-out' ? 'selected' : ''; ?>>Checked-out</option>
                        <option value="Canceled" <?php echo $filter_status == 'Canceled' ? 'selected' : ''; ?>>Canceled</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-medium">Platform</label>
                    <select class="form-select" name="platform">
                        <option value="">Semua Platform</option>
                        <option value="OTS" <?php echo $filter_platform == 'OTS' ? 'selected' : ''; ?>>OTS</option>
                        <option value="Internal" <?php echo $filter_platform == 'Internal' ? 'selected' : ''; ?>>Internal</option>
                        <option value="Agoda" <?php echo $filter_platform == 'Agoda' ? 'selected' : ''; ?>>Agoda</option>
                        <option value="Booking.com" <?php echo $filter_platform == 'Booking.com' ? 'selected' : ''; ?>>Booking.com</option>
                        <option value="Traveloka" <?php echo $filter_platform == 'Traveloka' ? 'selected' : ''; ?>>Traveloka</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-medium">Bulan</label>
                    <input type="month" class="form-control" name="bulan" value="<?php echo htmlspecialchars($filter_bulan); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-medium">Pencarian</label>
                    <div class="input-group">
                        <input type="text" class="form-control" name="search" placeholder="Nama, No HP, Kamar..." value="<?php echo htmlspecialchars($search); ?>">
                        <button class="btn btn-primary" type="submit">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <div class="content-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="mb-0">Total: <span class="text-primary fw-bold"><?php echo $result_reservasi->num_rows; ?></span> reservasi ditemukan</h6>
            </div>

            <?php if ($result_reservasi->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-modern">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Tamu</th>
                                <th>Kamar</th>
                                <th>Check-in / out</th>
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
                                    <td><span class="fw-bold text-primary">#<?php echo $row['id_reservasi']; ?></span></td>
                                    <td>
                                        <strong class="d-block"><?php echo htmlspecialchars($row['nama_lengkap']); ?></strong>
                                        <small class="text-muted"><?php echo htmlspecialchars($row['no_hp']); ?></small>
                                    </td>
                                    <td>
                                        <span class="d-block"><?php echo htmlspecialchars($row['nama_kamar']); ?></span>
                                        <small class="text-muted"><?php echo htmlspecialchars($row['nama_properti']); ?></small>
                                    </td>
                                    <td>
                                        <span class="d-block"><?php echo date('d M Y', strtotime($row['tgl_checkin'])); ?></span>
                                        <small class="text-muted"><?php echo date('d M Y', strtotime($row['tgl_checkout'])); ?></small>
                                    </td>
                                    <td><?php echo $durasi; ?> malam</td>
                                    <td>
                                        <span class="badge bg-light text-dark fw-medium"><?php echo htmlspecialchars($row['platform_booking']); ?></span>
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
                    <p class="text-muted mt-3">Tidak ada reservasi yang cocok dengan filter Anda.</p>
                    <a href="form_input_booking.php" class="btn btn-primary mt-2">
                        <i class="bi bi-plus-lg me-1"></i>Tambah Booking Baru
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // --- Sidebar Toggle Logic ---
            const sidebarToggleBtn = document.getElementById('sidebar-toggle');
            const mobileSidebarToggleBtn = document.getElementById('mobile-sidebar-toggle');
            const body = document.body;

            const toggleSidebar = () => {
                body.classList.toggle('sidebar-minimized');
                const isMinimized = body.classList.contains('sidebar-minimized');
                localStorage.setItem('sidebarMinimized', isMinimized ? 'true' : 'false');
            };

            const toggleMobileSidebar = () => {
                body.classList.toggle('sidebar-mobile-show');
            };
            
            if (localStorage.getItem('sidebarMinimized') === 'true') {
                body.classList.add('sidebar-minimized');
            }

            if (sidebarToggleBtn) {
                sidebarToggleBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    toggleSidebar();
                });
            }
            if (mobileSidebarToggleBtn) {
                mobileSidebarToggleBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    toggleMobileSidebar();
                });
            }
            
            document.addEventListener('click', function(e) {
                if (body.classList.contains('sidebar-mobile-show') && e.target.tagName.toLowerCase() !== 'i' && !e.target.closest('#sidebarMenu') && !e.target.closest('#mobile-sidebar-toggle')) {
                    body.classList.remove('sidebar-mobile-show');
                }
            });
        });
    </script>
</body>
</html>
