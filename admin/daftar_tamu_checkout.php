<?php
// daftar_tamu_checkout.php
// Daftar semua tamu yang sudah checkout

require_once '../auth_check.php';

// Cek apakah user adalah admin atau front_office
if (!in_array($role_user, ['admin', 'front_office'])) {
    header("Location: ../dashboard.php");
    exit();
}

require_once '../koneksi.php';

// Filter dan pencarian
$filter_platform = $_GET['platform'] ?? '';
$filter_bulan = $_GET['bulan'] ?? '';
$search = $_GET['search'] ?? '';

// Build query
$where_conditions = ["r.status_booking = 'Checked-out'"];
$params = [];
$types = '';

if (!empty($filter_platform)) {
    $where_conditions[] = "r.platform_booking = ?";
    $params[] = $filter_platform;
    $types .= 's';
}

if (!empty($filter_bulan)) {
    $where_conditions[] = "DATE_FORMAT(r.tgl_checkout, '%Y-%m') = ?";
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

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : "WHERE r.status_booking = 'Checked-out'";

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
          ORDER BY r.tgl_checkout DESC, r.dibuat_pada DESC
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
    <title>Daftar Tamu Checkout - CMS Guesthouse Adiputra</title>
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
        }

        #main-container {
            display: flex;
            min-height: 100vh;
        }

        #main-content {
            transition: width 0.3s ease;
            width: 100%;
        }

        @media (min-width: 992px) {
            #main-content {
                margin-left: var(--sidebar-width);
                width: calc(100% - var(--sidebar-width));
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
    </style>
</head>
<body style="overflow-x: hidden;">
    <div id="main-container">
        <!-- SIDEBAR -->
        <?php include 'sidebar.php'; ?>

        <!-- MAIN CONTENT -->
        <div id="main-content" class="flex-grow-1 p-3 p-md-4">
            <!-- Header -->
            <header class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h4 class="fw-bold mb-1 text-dark">Daftar Tamu Checkout</h4>
                    <p class="text-muted mb-0" style="font-size: 0.9rem;">Riwayat tamu yang telah selesai menginap.</p>
                </div>
            </header>

            <!-- Filter Card -->
            <div class="filter-card">
                <form method="GET" action="" class="row g-3 align-items-end">
                    <div class="col-md-4">
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
                    <div class="col-md-4">
                        <label class="form-label small fw-medium">Bulan Checkout</label>
                        <input type="month" class="form-control" name="bulan" value="<?php echo htmlspecialchars($filter_bulan); ?>">
                    </div>
                    <div class="col-md-4">
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

            <!-- Table Card -->
            <div class="content-card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="mb-0">Total: <span class="text-primary fw-bold"><?php echo $result_reservasi->num_rows; ?></span> tamu ditemukan</h6>
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
                        <p class="text-muted mt-3">Tidak ada data tamu checkout yang cocok dengan filter Anda.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
