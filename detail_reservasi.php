<?php
// detail_reservasi.php
// Halaman detail reservasi lengkap

require_once 'auth_check.php';

// Cek apakah user adalah admin atau front_office
if (!in_array($role_user, ['admin', 'front_office'])) {
    header("Location: dashboard.php");
    exit();
}

require_once 'koneksi.php';

$id_reservasi = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id_reservasi == 0) {
    header("Location: daftar_reservasi.php");
    exit();
}

// Ambil data reservasi lengkap
$query = "SELECT r.*, 
          t.nama_lengkap, t.no_hp, t.email, t.catatan_membership,
          k.nama_kamar, k.tipe_kamar, k.harga_default,
          p.nama_properti, p.alamat as alamat_properti,
          u.nama_lengkap as operator_nama
          FROM tbl_reservasi r
          JOIN tbl_tamu t ON r.id_tamu = t.id_tamu
          JOIN tbl_kamar k ON r.id_kamar = k.id_kamar
          JOIN tbl_properti p ON k.id_properti = p.id_properti
          LEFT JOIN tbl_users u ON r.dibuat_oleh_user = u.id_user
          WHERE r.id_reservasi = ?";

$stmt = $koneksi->prepare($query);
$stmt->bind_param("i", $id_reservasi);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header("Location: daftar_reservasi.php");
    exit();
}

$reservasi = $result->fetch_assoc();
$stmt->close();

// Hitung durasi
$checkin = new DateTime($reservasi['tgl_checkin']);
$checkout = new DateTime($reservasi['tgl_checkout']);
$durasi = $checkin->diff($checkout)->days;

$koneksi->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Reservasi #<?php echo $id_reservasi; ?> - CMS Guesthouse Adiputra</title>
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
            padding: 2rem;
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
            padding: 1.5rem 2rem;
            border-radius: 1.25rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
            border: 1px solid var(--border-color);
        }

        .content-card {
            background: var(--bg-white);
            border-radius: 1.25rem;
            padding: 2rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            border: 1px solid var(--border-color);
            margin-bottom: 2rem;
        }

        .info-section {
            margin-bottom: 2rem;
        }

        .info-section h6 {
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--border-color);
        }

        .info-item {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem 0;
            border-bottom: 1px solid var(--border-color);
        }

        .info-item:last-child {
            border-bottom: none;
        }

        .info-label {
            font-weight: 600;
            color: var(--text-muted);
            flex: 1;
        }

        .info-value {
            flex: 2;
            text-align: right;
        }

        .badge-modern {
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

        .highlight-box {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            border-left: 4px solid var(--primary-color);
            padding: 1.5rem;
            border-radius: 0.75rem;
            margin-bottom: 2rem;
        }

        .price-box {
            background: linear-gradient(135deg, var(--primary-color) 0%, #2563eb 100%);
            color: white;
            padding: 2rem;
            border-radius: 1rem;
            text-align: center;
        }

        .price-box h3 {
            font-size: 2.5rem;
            font-weight: 800;
            margin: 0;
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
                    <h5><i class="bi bi-file-text me-2"></i>Detail Reservasi #<?php echo $id_reservasi; ?></h5>
                    <small class="text-muted">Informasi lengkap reservasi</small>
                </div>
                <div>
                    <a href="daftar_reservasi.php" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-2"></i>Kembali ke Daftar
                    </a>
                    <button class="btn btn-outline-secondary d-lg-none ms-2" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarMenu">
                        <i class="bi bi-list"></i>
                    </button>
                </div>
            </header>

            <div class="row">
                <!-- Left Column -->
                <div class="col-lg-8">
                    <!-- Status Highlight -->
                    <div class="highlight-box">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <h6 class="mb-2">Status Reservasi</h6>
                                <span class="badge-status <?php echo strtolower(str_replace('-', '', $reservasi['status_booking'])); ?> badge-modern">
                                    <?php echo $reservasi['status_booking']; ?>
                                </span>
                            </div>
                            <div class="col-md-6">
                                <h6 class="mb-2">Status Pembayaran</h6>
                                <span class="badge-payment <?php echo strtolower(str_replace(' ', '-', $reservasi['status_pembayaran'])); ?> badge-modern">
                                    <?php echo $reservasi['status_pembayaran']; ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Data Tamu -->
                    <div class="content-card">
                        <div class="info-section">
                            <h6><i class="bi bi-person me-2"></i>Data Tamu</h6>
                            <div class="info-item">
                                <span class="info-label">Nama Lengkap</span>
                                <span class="info-value"><strong><?php echo htmlspecialchars($reservasi['nama_lengkap']); ?></strong></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">No. HP</span>
                                <span class="info-value"><?php echo htmlspecialchars($reservasi['no_hp']); ?></span>
                            </div>
                            <?php if (!empty($reservasi['email'])): ?>
                            <div class="info-item">
                                <span class="info-label">Email</span>
                                <span class="info-value"><?php echo htmlspecialchars($reservasi['email']); ?></span>
                            </div>
                            <?php endif; ?>
                            <?php if (!empty($reservasi['catatan_membership'])): ?>
                            <div class="info-item">
                                <span class="info-label">Catatan Membership</span>
                                <span class="info-value"><?php echo htmlspecialchars($reservasi['catatan_membership']); ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Detail Reservasi -->
                    <div class="content-card">
                        <div class="info-section">
                            <h6><i class="bi bi-calendar-range me-2"></i>Detail Reservasi</h6>
                            <div class="info-item">
                                <span class="info-label">Kamar</span>
                                <span class="info-value">
                                    <strong><?php echo htmlspecialchars($reservasi['nama_kamar']); ?></strong>
                                    <?php if ($reservasi['tipe_kamar']): ?>
                                        <br><small class="text-muted"><?php echo htmlspecialchars($reservasi['tipe_kamar']); ?></small>
                                    <?php endif; ?>
                                </span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Properti</span>
                                <span class="info-value"><?php echo htmlspecialchars($reservasi['nama_properti']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Tanggal Check-in</span>
                                <span class="info-value">
                                    <strong><?php echo date('d M Y', strtotime($reservasi['tgl_checkin'])); ?></strong>
                                    <br><small class="text-muted"><?php echo date('l', strtotime($reservasi['tgl_checkin'])); ?></small>
                                </span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Tanggal Check-out</span>
                                <span class="info-value">
                                    <strong><?php echo date('d M Y', strtotime($reservasi['tgl_checkout'])); ?></strong>
                                    <br><small class="text-muted"><?php echo date('l', strtotime($reservasi['tgl_checkout'])); ?></small>
                                </span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Durasi</span>
                                <span class="info-value"><strong><?php echo $durasi; ?> malam</strong></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Jumlah Tamu</span>
                                <span class="info-value"><?php echo $reservasi['jumlah_tamu']; ?> orang</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Platform Booking</span>
                                <span class="info-value">
                                    <span class="badge bg-secondary"><?php echo htmlspecialchars($reservasi['platform_booking']); ?></span>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Catatan -->
                    <?php if (!empty($reservasi['catatan_operator'])): ?>
                    <div class="content-card">
                        <div class="info-section">
                            <h6><i class="bi bi-sticky me-2"></i>Catatan Operator</h6>
                            <p class="mb-0"><?php echo nl2br(htmlspecialchars($reservasi['catatan_operator'])); ?></p>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Right Column -->
                <div class="col-lg-4">
                    <!-- Price Box -->
                    <div class="price-box mb-3">
                        <div class="mb-2">Total Harga</div>
                        <h3>Rp <?php echo number_format($reservasi['harga_total'], 0, ',', '.'); ?></h3>
                        <small>Durasi: <?php echo $durasi; ?> malam × Rp <?php echo number_format($reservasi['harga_default'], 0, ',', '.'); ?>/malam</small>
                    </div>

                    <!-- Informasi Sistem -->
                    <div class="content-card">
                        <div class="info-section">
                            <h6><i class="bi bi-info-circle me-2"></i>Informasi Sistem</h6>
                            <div class="info-item">
                                <span class="info-label">ID Reservasi</span>
                                <span class="info-value"><strong>#<?php echo $id_reservasi; ?></strong></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Dibuat Oleh</span>
                                <span class="info-value"><?php echo htmlspecialchars($reservasi['operator_nama'] ?: '-'); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Tanggal Dibuat</span>
                                <span class="info-value">
                                    <?php echo date('d M Y', strtotime($reservasi['dibuat_pada'])); ?>
                                    <br><small class="text-muted"><?php echo date('H:i', strtotime($reservasi['dibuat_pada'])); ?></small>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="content-card">
                        <h6 class="mb-3"><i class="bi bi-lightning me-2"></i>Quick Actions</h6>
                        <div class="d-grid gap-2">
                            <a href="daftar_reservasi.php" class="btn btn-outline-primary">
                                <i class="bi bi-list-ul me-2"></i>Kembali ke Daftar
                            </a>
                            <?php if ($reservasi['status_booking'] == 'Booking'): ?>
                                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalIdentitas">
                                    <i class="bi bi-check-circle me-2"></i>Check-in & Ambil Identitas
                                </button>
                            <?php endif; ?>
                            <?php if (in_array($reservasi['status_booking'], ['Booking', 'Checked-in'])): ?>
                                <button class="btn btn-danger" onclick="doUpdateStatus('cancel')">
                                    <i class="bi bi-x-circle me-2"></i>Batalkan Reservasi
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Modal Upload Identitas (Mobile-first) -->
                    <div class="modal fade" id="modalIdentitas" tabindex="-1" aria-hidden="true">
                      <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                          <div class="modal-header">
                            <h5 class="modal-title"><i class="bi bi-camera me-2"></i>Upload Foto Identitas</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                          </div>
                          <div class="modal-body">
                            <form id="uploadForm" enctype="multipart/form-data">
                                <input type="hidden" name="id_reservasi" value="<?php echo $id_reservasi; ?>">
                                <div class="mb-3">
                                    <label class="form-label">Ambil/Upload Foto Identitas (KTP/SIM)</label>
                                    <input type="file" class="form-control" name="foto" accept="image/*" capture="environment" required>
                                    <div class="form-text">Di ponsel, tombol ini akan membuka kamera langsung.</div>
                                </div>
                                <div class="d-grid gap-2">
                                    <button type="button" class="btn btn-primary" id="btnUpload"><i class="bi bi-cloud-upload"></i> Simpan & Check-in</button>
                                </div>
                            </form>
                          </div>
                        </div>
                      </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function doUpdateStatus(action) {
            if (!confirm('Lanjutkan tindakan ini?')) return;
            const formData = new FormData();
            formData.append('id', <?php echo $id_reservasi; ?>);
            formData.append('action', action);
            fetch('update_reservasi.php', { method: 'POST', body: formData })
                .then(r => r.json())
                .then(res => {
                    if (res.ok) { location.reload(); }
                    else { alert(res.message || 'Gagal memperbarui status'); }
                })
                .catch(() => alert('Gagal memperbarui status'));
        }

        // Upload-only (mobile-first)
        const btnUpload = document.getElementById('btnUpload');
        const uploadForm = document.getElementById('uploadForm');
        if (btnUpload) {
            btnUpload.onclick = async () => {
                const fd = new FormData(uploadForm);
                const res = await fetch('upload_identitas.php', { method: 'POST', body: fd });
                const js = await res.json();
                if (!js.ok) { alert(js.message || 'Gagal upload identitas'); return; }
                doUpdateStatus('checkin');
            };
        }
    </script>
    <style>
        /* Mobile-friendly tweaks: buat tombol penuh dan spacing lega di layar kecil */
        @media (max-width: 576px) {
            .content-card { padding: 1rem; }
            .price-box { padding: 1.25rem; }
            .d-grid > .btn, .modal .btn { padding: 0.85rem 1rem; font-weight: 600; }
            .modal-dialog { margin: 0.75rem; }
        }
    </style>
</body>
</html>

