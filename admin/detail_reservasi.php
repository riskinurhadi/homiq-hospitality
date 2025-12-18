<?php
// detail_reservasi.php
// Halaman detail reservasi lengkap

require_once 'auth_check.php';

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
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
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

        .info-item:last-child { border-bottom: none; }
        .info-label { font-weight: 500; color: var(--text-muted); flex-basis: 40%; }
        .info-value { flex-basis: 60%; text-align: right; font-weight: 600; }
        .badge-status, .badge-payment { padding: 0.5rem 0.75rem; border-radius: 0.5rem; font-weight: 600; font-size: 0.85rem; }
        .badge-status.booking { background: var(--primary-soft); color: var(--primary); }
        .badge-status.checked-in { background: #d1fae5; color: var(--success); }
        .badge-status.checked-out { background: #e2e8f0; color: var(--text-muted); }
        .badge-status.canceled { background: #ffe4e6; color: var(--danger); }
        .badge-payment.lunas { background: #d1fae5; color: var(--success); }
        .badge-payment.dp { background: #fef3c7; color: #f59e0b; }
        .badge-payment.belum-bayar { background: #ffe4e6; color: var(--danger); }

        .price-box {
            background: var(--primary-soft);
            color: var(--primary);
            padding: 1.5rem;
            border-radius: var(--radius-lg);
            text-align: center;
        }

        .price-box h3 {
            font-size: 2.25rem;
            font-weight: 800;
            margin: 0;
            color: var(--primary);
        }
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
                    <h4 class="fw-bold mb-1 text-dark">Detail Reservasi #<?php echo $id_reservasi; ?></h4>
                    <p class="text-muted mb-0" style="font-size: 0.9rem;">Informasi lengkap mengenai reservasi</p>
                </div>
            </div>
            <div>
                <a href="daftar_reservasi.php" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Kembali
                </a>
            </div>
        </header>

        <div class="row">
            <div class="col-lg-8">
                <div class="content-card">
                    <div class="info-section">
                        <h6><i class="bi bi-person me-2"></i>Data Tamu</h6>
                        <div class="info-item"><span class="info-label">Nama Lengkap</span><span class="info-value"><strong><?php echo htmlspecialchars($reservasi['nama_lengkap']); ?></strong></span></div>
                        <div class="info-item"><span class="info-label">No. HP</span><span class="info-value"><?php echo htmlspecialchars($reservasi['no_hp']); ?></span></div>
                        <?php if (!empty($reservasi['email'])): ?>
                        <div class="info-item"><span class="info-label">Email</span><span class="info-value"><?php echo htmlspecialchars($reservasi['email']); ?></span></div>
                        <?php endif; ?>
                        <?php if (!empty($reservasi['catatan_membership'])): ?>
                        <div class="info-item"><span class="info-label">Catatan Membership</span><span class="info-value"><?php echo htmlspecialchars($reservasi['catatan_membership']); ?></span></div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="content-card">
                    <div class="info-section">
                        <h6><i class="bi bi-calendar-range me-2"></i>Detail Reservasi</h6>
                        <div class="info-item"><span class="info-label">Properti & Kamar</span><span class="info-value"><strong><?php echo htmlspecialchars($reservasi['nama_kamar']); ?></strong><br><small class="text-muted"><?php echo htmlspecialchars($reservasi['nama_properti']); ?></small></span></div>
                        <div class="info-item"><span class="info-label">Check-in</span><span class="info-value"><strong><?php echo date('d M Y', strtotime($reservasi['tgl_checkin'])); ?></strong></span></div>
                        <div class="info-item"><span class="info-label">Check-out</span><span class="info-value"><strong><?php echo date('d M Y', strtotime($reservasi['tgl_checkout'])); ?></strong></span></div>
                        <div class="info-item"><span class="info-label">Durasi</span><span class="info-value"><strong><?php echo $durasi; ?> malam</strong></span></div>
                        <div class="info-item"><span class="info-label">Jumlah Tamu</span><span class="info-value"><?php echo $reservasi['jumlah_tamu']; ?> orang</span></div>
                        <div class="info-item"><span class="info-label">Platform</span><span class="info-value"><span class="badge bg-light text-dark fw-medium"><?php echo htmlspecialchars($reservasi['platform_booking']); ?></span></span></div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="price-box mb-3">
                    <div class="mb-2">Total Harga</div>
                    <h3>Rp <?php echo number_format($reservasi['harga_total'], 0, ',', '.'); ?></h3>
                </div>
                <div class="content-card">
                    <div class="info-item"><span class="info-label">Status Reservasi</span><span class="info-value"><span class="badge-status <?php echo strtolower(str_replace('-', '', $reservasi['status_booking'])); ?>"><?php echo $reservasi['status_booking']; ?></span></span></div>
                    <div class="info-item"><span class="info-label">Status Pembayaran</span><span class="info-value"><span class="badge-payment <?php echo strtolower(str_replace(' ', '-', $reservasi['status_pembayaran'])); ?>"><?php echo $reservasi['status_pembayaran']; ?></span></span></div>
                    <?php if ($reservasi['jumlah_dp'] > 0): ?>
                    <div class="info-item"><span class="info-label">Jumlah DP</span><span class="info-value">Rp <?php echo number_format($reservasi['jumlah_dp'], 0, ',', '.'); ?></span></div>
                    <?php if ($reservasi['status_pembayaran'] == 'DP'): ?>
                    <div class="info-item"><span class="info-label">Sisa Bayar</span><span class="info-value fw-bold text-danger">Rp <?php echo number_format($reservasi['harga_total'] - $reservasi['jumlah_dp'], 0, ',', '.'); ?></span></div>
                    <?php endif; ?>
                    <?php endif; ?>
                </div>
                <div class="content-card">
                    <h6 class="mb-3"><i class="bi bi-lightning me-2"></i>Quick Actions</h6>
                    <div class="d-grid gap-2">
                        <?php if ($reservasi['status_booking'] == 'Booking'): ?>
                            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalIdentitas"><i class="bi bi-check-circle me-2"></i>Check-in & Ambil Identitas</button>
                        <?php endif; ?>
                        <?php if ($reservasi['status_booking'] == 'Booking'): ?>
                            <button class="btn btn-danger" onclick="doUpdateStatus('cancel')"><i class="bi bi-x-circle me-2"></i>Batalkan Reservasi</button>
                        <?php endif; ?>
                        <?php if ($reservasi['status_booking'] == 'Checked-out'): ?>
                            <a href="cetak_struk.php?id=<?php echo $id_reservasi; ?>" class="btn btn-info" target="_blank"><i class="bi bi-printer me-2"></i>Download Struk</a>
                        <?php endif; ?>
                        <?php if ($reservasi['status_booking'] == 'Checked-in'): ?>
                            <button class="btn btn-primary" onclick="doUpdateStatus('checkout')"><i class="bi bi-box-arrow-right me-2"></i>Check-Out</button>
                        <?php endif; ?>
                         <a href="form_input_booking.php?edit=<?php echo $id_reservasi; ?>" class="btn btn-outline-secondary"><i class="bi bi-pencil-square me-2"></i>Edit Reservasi</a>
                    </div>
                </div>
            </div>
        </div>

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

                    <?php if ($reservasi['status_pembayaran'] == 'Belum Bayar'): ?>
                    <div class="mb-3 border-bottom pb-3">
                         <label for="status_pembayaran_update" class="form-label">Update Status Pembayaran</label>
                         <p class="small text-muted">Check-in tidak bisa dilakukan jika status belum bayar. Ubah status menjadi DP atau Lunas.</p>
                         <select class="form-select" name="status_pembayaran" id="status_pembayaran_update" required>
                             <option value="">Pilih Status Baru...</option>
                             <option value="DP">DP</option>
                             <option value="Lunas">Lunas</option>
                         </select>
                         <div class="mt-2 d-none" id="dp_field_modal">
                            <label for="jumlah_dp" class="form-label small">Jumlah DP</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text">Rp</span>
                                <input type="text" class="form-control" id="jumlah_dp" name="jumlah_dp">
                            </div>
                         </div>
                    </div>
                    <?php endif; ?>

                    <div class="mb-3">
                        <label class="form-label">Ambil/Upload Foto Identitas (KTP/SIM)</label>
                        <input type="file" class="form-control" name="foto" id="fotoInput" accept="image/*" capture="environment">
                    </div>
                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-primary" id="btnUpload"><i class="bi bi-cloud-upload"></i> Simpan & Check-in</button>
                    </div>
                </form>
              </div>
            </div>
          </div>
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

            // --- Page Specific Logic ---
            const idReservasi = <?php echo $id_reservasi; ?>;
            const statusPembayaranAwal = '<?php echo $reservasi['status_pembayaran']; ?>';
            const bootstrapModal = new bootstrap.Modal(document.getElementById('modalIdentitas'));
            const btnUpload = document.getElementById('btnUpload');
            const fotoInput = document.getElementById('fotoInput');
            const uploadForm = document.getElementById('uploadForm');
            const paymentUpdateSelect = document.getElementById('status_pembayaran_update');
            const dpFieldModal = document.getElementById('dp_field_modal');

            if (paymentUpdateSelect) {
                paymentUpdateSelect.addEventListener('change', () => {
                    dpFieldModal.classList.toggle('d-none', paymentUpdateSelect.value !== 'DP');
                });
            }

            if (btnUpload) {
                btnUpload.onclick = () => {
                    if (fotoInput.files.length === 0) {
                         Swal.fire('Oops...', 'Anda harus memilih atau mengambil foto identitas terlebih dahulu.', 'warning');
                         return;
                    }
                    
                    if (statusPembayaranAwal === 'Belum Bayar') {
                        if (!paymentUpdateSelect || paymentUpdateSelect.value === '') {
                            Swal.fire('Oops...', 'Silakan update status pembayaran menjadi DP atau Lunas.', 'warning');
                            return;
                        }
                        if (paymentUpdateSelect.value === 'DP') {
                            const dpAmountInput = document.querySelector('#dp_field_modal input');
                            if (!dpAmountInput || dpAmountInput.value.trim() === '' || parseFloat(dpAmountInput.value) <= 0) {
                                Swal.fire('Oops...', 'Silakan masukkan jumlah DP yang valid.', 'warning');
                                return;
                            }
                        }
                    }

                    const fd = new FormData(uploadForm);
                    
                    Swal.fire({
                        title: 'Memproses Check-in...',
                        text: 'Mohon tunggu, data sedang disimpan.',
                        allowOutsideClick: false,
                        didOpen: () => { Swal.showLoading(); }
                    });

                    fetch('proses_checkin.php', { method: 'POST', body: fd })
                        .then(response => response.json())
                        .then(res => {
                            Swal.close();
                            if (res.ok) {
                                bootstrapModal.hide();
                                Swal.fire({
                                    title: 'Berhasil!',
                                    text: 'Tamu berhasil check-in.',
                                    icon: 'success'
                                }).then(() => location.reload());
                            } else {
                                Swal.fire('Check-in Gagal', res.message || 'Terjadi kesalahan yang tidak diketahui.', 'error');
                            }
                        })
                        .catch((err) => {
                            Swal.fire('Error', 'Gagal terhubung ke server. Silakan coba lagi.', 'error');
                        });
                };
            }
.
        function doUpdateStatus(action) {
            const actionText = {
                cancel: 'membatalkan',
                checkout: 'check-out'
            };

            if (action === 'checkin') return;

            Swal.fire({
                title: 'Anda Yakin?',
                text: `Anda akan ${actionText[action] || action} reservasi ini.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: `Ya, ${actionText[action]}!`,
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Memproses...',
                        text: 'Mohon tunggu sebentar.',
                        allowOutsideClick: false,
                        didOpen: () => { Swal.showLoading(); }
                    });

                    const formData = new FormData();
                    formData.append('id', <?php echo $id_reservasi; ?>);
                    formData.append('action', action);

                    fetch('update_reservasi.php', { method: 'POST', body: formData })
                        .then(response => response.json())
                        .then(res => {
                            if (res.ok) {
                                Swal.fire('Berhasil!', `Reservasi telah berhasil di-${action}.`, 'success')
                                     .then(() => location.reload());
                            } else {
                                Swal.fire('Gagal', res.message || 'Gagal memperbarui status.', 'error');
                            }
                        })
                        .catch(err => {
                            Swal.fire('Error', 'Terjadi kesalahan saat menghubungi server.', 'error');
                        });
                }
            });
        }
    </script>
</body>
</html>