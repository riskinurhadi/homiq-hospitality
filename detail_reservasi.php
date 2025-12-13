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
            font-weight: 500;
            color: var(--text-muted);
            flex-basis: 40%;
        }

        .info-value {
            flex-basis: 60%;
            text-align: right;
            font-weight: 600;
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
    <div id="main-container">
        <!-- SIDEBAR -->
        <?php include 'sidebar.php'; ?>

        <!-- MAIN CONTENT -->
        <div id="main-content" class="flex-grow-1 p-3 p-md-4">
            <!-- Header -->
            <header class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h4 class="fw-bold mb-1 text-dark">Detail Reservasi #<?php echo $id_reservasi; ?></h4>
                    <p class="text-muted mb-0" style="font-size: 0.9rem;">Informasi lengkap mengenai reservasi</p>
                </div>
                <div>
                    <a href="daftar_reservasi.php" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i>Kembali
                    </a>
                </div>
            </header>

            <div class="row">
                <!-- Left Column -->
                <div class="col-lg-8">
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
                                <span class="info-label">Properti & Kamar</span>
                                <span class="info-value">
                                    <strong><?php echo htmlspecialchars($reservasi['nama_kamar']); ?></strong>
                                    <br><small class="text-muted"><?php echo htmlspecialchars($reservasi['nama_properti']); ?></small>
                                </span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Check-in</span>
                                <span class="info-value">
                                    <strong><?php echo date('d M Y', strtotime($reservasi['tgl_checkin'])); ?></strong>
                                </span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Check-out</span>
                                <span class="info-value">
                                    <strong><?php echo date('d M Y', strtotime($reservasi['tgl_checkout'])); ?></strong>
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
                                <span class="info-label">Platform</span>
                                <span class="info-value">
                                    <span class="badge bg-light text-dark fw-medium"><?php echo htmlspecialchars($reservasi['platform_booking']); ?></span>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column -->
                <div class="col-lg-4">
                    <!-- Price Box -->
                    <div class="price-box mb-3">
                        <div class="mb-2">Total Harga</div>
                        <h3>Rp <?php echo number_format($reservasi['harga_total'], 0, ',', '.'); ?></h3>
                    </div>

                    <!-- Status -->
                    <div class="content-card">
                        <div class="info-item">
                            <span class="info-label">Status Reservasi</span>
                            <span class="info-value">
                                <span class="badge-status <?php echo strtolower(str_replace('-', '', $reservasi['status_booking'])); ?>">
                                    <?php echo $reservasi['status_booking']; ?>
                                </span>
                            </span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Status Pembayaran</span>
                            <span class="info-value">
                                <span class="badge-payment <?php echo strtolower(str_replace(' ', '-', $reservasi['status_pembayaran'])); ?>">
                                    <?php echo $reservasi['status_pembayaran']; ?>
                                </span>
                            </span>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="content-card">
                        <h6 class="mb-3"><i class="bi bi-lightning me-2"></i>Quick Actions</h6>
                        <div class="d-grid gap-2">
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
                            <?php if ($reservasi['status_booking'] == 'Checked-in'): ?>
                                <button class="btn btn-primary" onclick="doUpdateStatus('checkout')">
                                    <i class="bi bi-box-arrow-right me-2"></i>Check-Out
                                </button>
                            <?php endif; ?>
                             <a href="form_input_booking.php?edit=<?php echo $id_reservasi; ?>" class="btn btn-outline-secondary">
                                <i class="bi bi-pencil-square me-2"></i>Edit Reservasi
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Upload Identitas -->
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
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const idReservasi = <?php echo $id_reservasi; ?>;
        const bootstrapModal = new bootstrap.Modal(document.getElementById('modalIdentitas'));

        function doUpdateStatus(action) {
            const actionText = {
                checkin: 'check-in',
                cancel: 'membatalkan',
                checkout: 'check-out'
            };

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
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    const formData = new FormData();
                    formData.append('id', idReservasi);
                    formData.append('action', action);

                    fetch('update_reservasi.php', { method: 'POST', body: formData })
                        .then(response => response.json())
                        .then(res => {
                            if (res.ok) {
                                Swal.fire({
                                    title: 'Berhasil!',
                                    text: `Reservasi telah berhasil di-${actionText[action]}.`,
                                    icon: 'success'
                                }).then(() => location.reload());
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

        const btnUpload = document.getElementById('btnUpload');
        const uploadForm = document.getElementById('uploadForm');
        const fotoInput = document.getElementById('fotoInput');

        if (btnUpload) {
            btnUpload.onclick = () => {
                // Validasi sisi klien
                if (fotoInput.files.length === 0) {
                     Swal.fire('Oops...', 'Anda harus memilih atau mengambil foto identitas terlebih dahulu.', 'warning');
                     return;
                }

                const fd = new FormData(uploadForm);
                
                Swal.fire({
                    title: 'Mengunggah Identitas...',
                    text: 'Mohon tunggu, file sedang diunggah.',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                fetch('upload_identitas.php', { method: 'POST', body: fd })
                    .then(response => response.json())
                    .then(res => {
                        Swal.close();
                        if (res.ok) {
                            bootstrapModal.hide();
                            // Jika upload berhasil, lanjutkan ke proses check-in
                            doUpdateStatus('checkin');
                        } else {
                            // Menampilkan pesan error spesifik dari server
                            Swal.fire('Upload Gagal', res.message || 'Terjadi kesalahan yang tidak diketahui.', 'error');
                        }
                    })
                    .catch((err) => {
                        Swal.fire('Error', 'Gagal terhubung ke server saat mengunggah. Silakan coba lagi.', 'error');
                    });
            };
        }
    </script>
</body>
</html>