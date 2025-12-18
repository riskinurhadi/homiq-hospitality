<?php
// form_input_booking.php
// Form untuk input booking baru (Internal/OTS) atau edit

require_once '../auth_check.php';

// Cek apakah user adalah admin atau front_office
if (!in_array($role_user, ['admin', 'front_office'])) {
    header("Location: ../dashboard.php");
    exit();
}

require_once '../koneksi.php';

$is_edit_mode = isset($_GET['edit']) && !empty($_GET['edit']);
$id_reservasi_edit = $is_edit_mode ? (int)$_GET['edit'] : 0;
$reservasi_data = [];

$page_title = "Input Booking Baru";
$page_sub_title = "Form untuk booking internal/OTS";

if ($is_edit_mode) {
    $page_title = "Edit Reservasi #$id_reservasi_edit";
    $page_sub_title = "Form untuk mengubah data reservasi";

    $stmt = $koneksi->prepare("SELECT r.*, t.nama_lengkap, t.no_hp, t.email, k.id_properti FROM tbl_reservasi r JOIN tbl_tamu t ON r.id_tamu = t.id_tamu JOIN tbl_kamar k ON r.id_kamar = k.id_kamar WHERE r.id_reservasi = ?");
    $stmt->bind_param("i", $id_reservasi_edit);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $reservasi_data = $result->fetch_assoc();
    } else {
        header("Location: daftar_reservasi.php");
        exit();
    }
    $stmt->close();
}


$success_message = '';
$error_message = '';

// Proses simpan atau update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Ambil semua data dari form
    $id_kamar = (int)($_POST['id_kamar'] ?? 0);
    $nama_tamu = trim($_POST['nama_tamu'] ?? '');
    $no_hp = trim($_POST['no_hp'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $jenis_booking = $_POST['jenis_booking'] ?? 'Harian';
    $tgl_checkin_date = $_POST['tgl_checkin'] ?? date('Y-m-d');
    
    $tgl_checkout_date = $_POST['tgl_checkout'] ?? '';
    $jam_checkin_time = $_POST['jam_checkin'] ?? '';
    $jam_checkout_time = $_POST['jam_checkout'] ?? '';

    $jumlah_tamu = (int)($_POST['jumlah_tamu'] ?? 1);
    $harga_total = (float)str_replace(['.', ','], '', $_POST['harga_total'] ?? '0');
    $platform_booking = $_POST['platform_booking'] ?? 'OTS';
    $status_pembayaran = $_POST['status_pembayaran'] ?? 'Belum Bayar';
    $jumlah_dp = isset($_POST['jumlah_dp']) ? (float)str_replace(['.', ','], '', $_POST['jumlah_dp']) : null;
    $catatan_operator = trim($_POST['catatan_operator'] ?? '');
    
    if ($status_pembayaran !== 'DP') {
        $jumlah_dp = null;
    }
    
    if (strpos($jenis_booking, 'Transit') !== false) {
        $tgl_checkin = "$tgl_checkin_date $jam_checkin_time:00";
        $tgl_checkout = "$tgl_checkin_date $jam_checkout_time:00";
    } else {
        $tgl_checkin = "$tgl_checkin_date 14:00:00";
        $tgl_checkout = "$tgl_checkout_date 12:00:00";
    }


    if (empty($nama_tamu) || empty($no_hp) || empty($tgl_checkin_date) || empty($id_kamar)) {
        $error_message = 'Semua field yang ditandai * tidak boleh kosong!';
    } elseif (($jenis_booking == 'Harian' || $jenis_booking == 'Guesthouse') && empty($tgl_checkout_date)) {
        $error_message = 'Tanggal checkout harus diisi untuk booking harian atau guesthouse!';
    } elseif (new DateTime($tgl_checkout) <= new DateTime($tgl_checkin)) {
        $error_message = 'Waktu checkout harus setelah waktu check-in!';
    } else {
        $koneksi->begin_transaction();
        try {
            $stmt_tamu = $koneksi->prepare("SELECT id_tamu FROM tbl_tamu WHERE no_hp = ?");
            $stmt_tamu->bind_param("s", $no_hp);
            $stmt_tamu->execute();
            $result_tamu = $stmt_tamu->get_result();
            if ($result_tamu->num_rows > 0) {
                $id_tamu = $result_tamu->fetch_assoc()['id_tamu'];
            } else {
                $stmt_insert_tamu = $koneksi->prepare("INSERT INTO tbl_tamu (nama_lengkap, no_hp, email) VALUES (?, ?, ?)");
                $stmt_insert_tamu->bind_param("sss", $nama_tamu, $no_hp, $email);
                $stmt_insert_tamu->execute();
                $id_tamu = $koneksi->insert_id;
                $stmt_insert_tamu->close();
            }
            $stmt_tamu->close();

            $id_reservasi_utama = 0;

            if ($is_edit_mode) {
                $stmt_reservasi = $koneksi->prepare("
                    UPDATE tbl_reservasi 
                    SET id_kamar = ?, id_tamu = ?, tgl_checkin = ?, tgl_checkout = ?, harga_total = ?, 
                        jumlah_tamu = ?, platform_booking = ?, status_pembayaran = ?, jumlah_dp = ?, catatan_operator = ?
                    WHERE id_reservasi = ?
                ");
                $stmt_reservasi->bind_param("iissdissdsi", $id_kamar, $id_tamu, $tgl_checkin, $tgl_checkout, 
                                           $harga_total, $jumlah_tamu, $platform_booking, $status_pembayaran, 
                                           $jumlah_dp, $catatan_operator, $id_reservasi_edit);
                $stmt_reservasi->execute();
                $id_reservasi_utama = $id_reservasi_edit;
                $success_message = "Reservasi berhasil diupdate!";

            } else {
                $stmt_reservasi = $koneksi->prepare("
                    INSERT INTO tbl_reservasi 
                    (id_kamar, id_tamu, tgl_checkin, tgl_checkout, harga_total, jumlah_tamu, 
                     platform_booking, status_booking, status_pembayaran, jumlah_dp, catatan_operator, dibuat_oleh_user, jenis_booking) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, 'Booking', ?, ?, ?, ?, ?)
                ");
                 $stmt_reservasi->bind_param("iissdisssdis", $id_kamar, $id_tamu, $tgl_checkin, $tgl_checkout, 
                                           $harga_total, $jumlah_tamu, $platform_booking, $status_pembayaran, 
                                           $jumlah_dp, $catatan_operator, $user_id, $jenis_booking);
                $stmt_reservasi->execute();
                $id_reservasi_utama = $koneksi->insert_id;
                $success_message = "Booking berhasil dibuat! ID Reservasi: " . $id_reservasi_utama;
            }
            
            $koneksi->commit();
            header("refresh:2;url=detail_reservasi.php?id=" . $id_reservasi_utama);

        } catch (Exception $e) {
            $koneksi->rollback();
            $error_message = 'Terjadi kesalahan: ' . $e->getMessage();
        }
    }
}

$result_properti = $koneksi->query("SELECT * FROM tbl_properti ORDER BY nama_properti");

$koneksi->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - CMS Guesthouse Adiputra</title>
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
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--body-bg);
            color: var(--text-main);
            display: flex;
            min-height: 100vh;
        }

        #main-content {
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
            padding: 2rem;
            box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
        }

        .form-label { font-weight: 600; }
        .form-control, .form-select {
            border-radius: var(--radius-md);
            border-color: var(--border-color);
            padding: 0.8rem 1rem;
        }
        .form-control:focus, .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px var(--primary-soft);
        }
        
        .section-divider {
            border-top: 1px solid var(--border-color);
            margin: 2rem 0;
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
                    <h4 class="fw-bold mb-1 text-dark"><?php echo $page_title; ?></h4>
                    <p class="text-muted mb-0" style="font-size: 0.9rem;"><?php echo $page_sub_title; ?></p>
                </div>
            </div>
            <div>
                <a href="daftar_reservasi.php" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Kembali
                </a>
            </div>
        </header>

        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger" role="alert">
                <i class="bi bi-exclamation-circle me-2"></i><?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success" role="alert">
                <i class="bi bi-check-circle me-2"></i><?php echo htmlspecialchars($success_message); ?>
                <small class="d-block">Mengalihkan ke halaman detail...</small>
            </div>
        <?php endif; ?>

        <div class="content-card">
            <form method="POST" action="" id="bookingForm">
                <h5 class="mb-4">Informasi Tamu</h5>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="no_hp" class="form-label">No. HP <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="no_hp" name="no_hp" 
                               value="<?php echo htmlspecialchars($reservasi_data['no_hp'] ?? ''); ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="nama_tamu" class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="nama_tamu" name="nama_tamu" 
                               value="<?php echo htmlspecialchars($reservasi_data['nama_lengkap'] ?? ''); ?>" required>
                    </div>
                </div>
                <div class="mb-4">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" 
                           value="<?php echo htmlspecialchars($reservasi_data['email'] ?? ''); ?>">
                </div>

                <div class="section-divider"></div>

                <h5 class="mb-4">Detail Reservasi</h5>
                <div class="row">
                    <div class="col-md-12 mb-3">
                        <label for="jenis_booking" class="form-label">Jenis Booking <span class="text-danger">*</span></label>
                        <select class="form-select" id="jenis_booking" name="jenis_booking" required onchange="handleBookingTypeChange()">
                            <option value="Harian" selected>Harian</option>
                            <option value="Guesthouse">Guesthouse</option>
                            <option value="Transit 3 Jam">Transit 3 Jam</option>
                            <option value="Transit 6 Jam">Transit 6 Jam</option>
                        </select>
                    </div>
                </div>

                <div class="row" id="harian-guesthouse-fields">
                    <div class="col-md-6 mb-3">
                        <label for="tgl_checkin" class="form-label">Tgl Check-in <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="tgl_checkin" name="tgl_checkin" 
                               value="<?php echo htmlspecialchars($reservasi_data['tgl_checkin'] ?? date('Y-m-d')); ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="tgl_checkout" class="form-label">Tgl Check-out <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="tgl_checkout" name="tgl_checkout" 
                               value="<?php echo htmlspecialchars($reservasi_data['tgl_checkout'] ?? ''); ?>" required>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="id_properti" class="form-label">Properti <span class="text-danger">*</span></label>
                        <select class="form-select" id="id_properti" name="id_properti" required onchange="loadKamar()">
                            <option value="">Pilih Properti</option>
                            <?php 
                            $selected_properti = $reservasi_data['id_properti'] ?? 0;
                            $result_properti->data_seek(0);
                            while ($prop = $result_properti->fetch_assoc()): 
                            ?>
                                <option value="<?php echo $prop['id_properti']; ?>" <?php echo ($selected_properti == $prop['id_properti']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($prop['nama_properti']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="id_kamar" class="form-label">Kamar <span class="text-danger">*</span></label>
                        <select class="form-select" id="id_kamar" name="id_kamar" required>
                            <option value="">Pilih Properti dahulu</option>
                        </select>
                    </div>
                </div>
                 <div class="row d-none" id="transit-fields">
                    <div class="col-md-6 mb-3">
                        <label for="jam_checkin" class="form-label">Jam Check-in <span class="text-danger">*</span></label>
                        <input type="time" class="form-control" id="jam_checkin" name="jam_checkin">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="jam_checkout" class="form-label">Jam Check-out</label>
                        <input type="time" class="form-control" id="jam_checkout" name="jam_checkout" readonly>
                    </div>
                </div>
                 <div class="row">
                    <div class="col-md-6 mb-4">
                        <label for="jumlah_tamu" class="form-label">Jumlah Tamu <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="jumlah_tamu" name="jumlah_tamu" 
                               value="<?php echo htmlspecialchars($reservasi_data['jumlah_tamu'] ?? '1'); ?>" required min="1">
                    </div>
                    <div class="col-md-6 mb-4">
                        <label for="platform_booking" class="form-label">Platform</label>
                        <select class="form-select" id="platform_booking" name="platform_booking">
                            <?php $platform = $reservasi_data['platform_booking'] ?? 'OTS'; ?>
                            <option value="OTS" <?php echo ($platform == 'OTS') ? 'selected' : ''; ?>>OTS</option>
                            <option value="Internal" <?php echo ($platform == 'Internal') ? 'selected' : ''; ?>>Internal</option>
                            <option value="Agoda" <?php echo ($platform == 'Agoda') ? 'selected' : ''; ?>>Agoda</option>
                            <option value="Booking.com" <?php echo ($platform == 'Booking.com') ? 'selected' : ''; ?>>Booking.com</option>
                            <option value="Traveloka" <?php echo ($platform == 'Traveloka') ? 'selected' : ''; ?>>Traveloka</option>
                        </select>
                    </div>
                </div>

                <div class="section-divider"></div>
                 <h5 class="mb-4">Pembayaran & Catatan</h5>
                <div class="row align-items-center">
                    <div class="col-md-4 mb-3">
                        <label for="status_pembayaran" class="form-label">Status Pembayaran</label>
                        <select class="form-select" id="status_pembayaran" name="status_pembayaran">
                            <?php $status_pembayaran = $reservasi_data['status_pembayaran'] ?? 'Belum Bayar'; ?>
                            <option value="Belum Bayar" <?php echo ($status_pembayaran == 'Belum Bayar') ? 'selected' : ''; ?>>Belum Bayar</option>
                            <option value="DP" <?php echo ($status_pembayaran == 'DP') ? 'selected' : ''; ?>>DP</option>
                            <option value="Lunas" <?php echo ($status_pembayaran == 'Lunas') ? 'selected' : ''; ?>>Lunas</option>
                        </select>
                    </div>
                     <div class="col-md-8 mb-3">
                        <label for="harga_total" class="form-label">Total Harga</label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="text" class="form-control" id="harga_total" name="harga_total" value="<?php echo htmlspecialchars($reservasi_data['harga_total'] ?? '0'); ?>">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12 mb-3 d-none" id="dp_field">
                         <label for="jumlah_dp" class="form-label">Jumlah DP</label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="text" class="form-control" id="jumlah_dp" name="jumlah_dp" value="<?php echo htmlspecialchars($reservasi_data['jumlah_dp'] ?? '0'); ?>">
                        </div>
                    </div>
                </div>

                <div class="mb-4">
                    <label for="catatan_operator" class="form-label">Catatan</label>
                    <textarea class="form-control" id="catatan_operator" name="catatan_operator" rows="3" placeholder="Catatan untuk reservasi..."><?php echo htmlspecialchars($reservasi_data['catatan_operator'] ?? ''); ?></textarea>
                </div>

                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <button type="submit" class="btn btn-primary px-4 py-2">
                        <i class="bi bi-check-lg me-2"></i><?php echo $is_edit_mode ? 'Update Reservasi' : 'Simpan Booking'; ?>
                    </button>
                </div>
            </form>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // --- Sidebar Toggle Logic ---
            const mobileSidebarToggleBtn = document.getElementById('mobile-sidebar-toggle');
            const body = document.body;

            const toggleMobileSidebar = () => {
                body.classList.toggle('sidebar-mobile-show');
            };
            
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

            // --- Form Specific Logic ---
            const isEditMode = <?php echo $is_edit_mode ? 'true' : 'false'; ?>;
            const editingId = <?php echo $is_edit_mode ? $id_reservasi_edit : '0'; ?>;
            const initialSelectedKamar = <?php echo json_encode($is_edit_mode ? [(int)$reservasi_data['id_kamar']] : []); ?>;
            
            const today = new Date().toISOString().split('T')[0];
            const checkinInput = document.getElementById('tgl_checkin');
            checkinInput.min = today;

            checkinInput.addEventListener('change', function() {
                const checkinDate = this.value;
                if (checkinDate) {
                    const nextDay = new Date(checkinDate);
                    nextDay.setDate(nextDay.getDate() + 1);
                    document.getElementById('tgl_checkout').min = nextDay.toISOString().split('T')[0];
                }
                loadKamar();
            });
            
            document.getElementById('tgl_checkout').addEventListener('change', () => loadKamar());
            document.getElementById('jam_checkin').addEventListener('change', () => {
                updateTransitCheckoutTime();
                loadKamar();
            });

            handleBookingTypeChange();
            
            if (isEditMode && document.getElementById('id_properti').value) {
                loadKamar(initialSelectedKamar);
            } else if (document.getElementById('id_properti').value) {
                loadKamar();
            }

            const statusPembayaranSelect = document.getElementById('status_pembayaran');
            const dpField = document.getElementById('dp_field');

            function toggleDpField() {
                if (statusPembayaranSelect.value === 'DP') {
                    dpField.classList.remove('d-none');
                } else {
                    dpField.classList.add('d-none');
                }
            }

            statusPembayaranSelect.addEventListener('change', toggleDpField);
            toggleDpField();
        });

        function updateTransitCheckoutTime() {
             const checkinTime = document.getElementById('jam_checkin').value;
             const bookingType = document.getElementById('jenis_booking').value;
             if (!checkinTime || !bookingType.includes('Transit')) return;

             const [hours, minutes] = checkinTime.split(':').map(Number);
             const checkinDate = new Date();
             checkinDate.setHours(hours, minutes, 0);

             const duration = (bookingType === 'Transit 3 Jam') ? 3 : 6;
             checkinDate.setHours(checkinDate.getHours() + duration);

             const checkoutHours = String(checkinDate.getHours()).padStart(2, '0');
             const checkoutMinutes = String(checkinDate.getMinutes()).padStart(2, '0');
             
             document.getElementById('jam_checkout').value = `${checkoutHours}:${checkoutMinutes}`;
        }

        function handleBookingTypeChange() {
            const bookingType = document.getElementById('jenis_booking').value;
            const harianGuesthouseFields = document.getElementById('harian-guesthouse-fields');
            const transitFields = document.getElementById('transit-fields');
            const tglCheckoutInput = document.getElementById('tgl_checkout');
            const jamCheckinInput = document.getElementById('jam_checkin');
            const kamarSelect = document.getElementById('id_kamar');

            kamarSelect.multiple = false;
            kamarSelect.size = 1;

            if (bookingType.includes('Transit')) {
                harianGuesthouseFields.classList.add('d-none');
                transitFields.classList.remove('d-none');
                tglCheckoutInput.required = false;
                jamCheckinInput.required = true;
            } else { 
                harianGuesthouseFields.classList.remove('d-none');
                transitFields.classList.add('d-none');
                tglCheckoutInput.required = true;
                jamCheckinInput.required = false;
            }
            
            updateTransitCheckoutTime();
            loadKamar(isEditMode ? [<?php echo (int)($reservasi_data['id_kamar'] ?? 0); ?>] : []);
        }

        function loadKamar(selectedKamarIds = []) {
            const idProperti = document.getElementById('id_properti').value;
            const kamarSelect = document.getElementById('id_kamar');
            
            const bookingType = document.getElementById('jenis_booking').value;
            const checkinDate = document.getElementById('tgl_checkin').value;
            const checkoutDate = document.getElementById('tgl_checkout').value;
            const checkinTime = document.getElementById('jam_checkin').value;
            const checkoutTime = document.getElementById('jam_checkout').value;

            kamarSelect.innerHTML = '<option value="">Memuat kamar...</option>';
            
            if (!idProperti) {
                kamarSelect.innerHTML = '<option value="">Pilih Properti</option>';
                return;
            }

            let queryString = `properti=${idProperti}&jenis_booking=${bookingType}&editing_id=${isEditMode ? <?php echo $id_reservasi_edit; ?> : 0}`;
            
            let canFetch = false;
            if (bookingType.includes('Transit')) {
                if (checkinDate && checkinTime && checkoutTime) {
                    queryString += `&checkin=${checkinDate}&checkin_time=${checkinTime}&checkout_time=${checkoutTime}`;
                    canFetch = true;
                } else {
                    kamarSelect.innerHTML = '<option value="">Pilih tanggal & jam transit</option>';
                }
            } else { 
                if (checkinDate && checkoutDate) {
                    queryString += `&checkin=${checkinDate}&checkout=${checkoutDate}`;
                    canFetch = true;
                } else {
                    kamarSelect.innerHTML = '<option value="">Pilih tgl check-in & out</option>';
                }
            }

            if (!canFetch) return;
            
            fetch(`../get_kamar.php?${queryString}`)
                .then(response => response.ok ? response.json() : Promise.reject('Gagal mengambil data'))
                .then(data => {
                    if (data.error) throw new Error(data.error);
                    
                    kamarSelect.innerHTML = '<option value="">Silakan Pilih Kamar</option>';
                    if (data.length === 0) {
                        kamarSelect.innerHTML = '<option value="">Tidak ada kamar tersedia</option>';
                        return;
                    }

                    data.forEach(kamar => {
                        const option = document.createElement('option');
                        const kamarIdInt = parseInt(kamar.id_kamar, 10);
                        option.value = kamarIdInt;
                        option.textContent = `${kamar.nama_kamar} - Rp ${parseInt(kamar.harga_default).toLocaleString('id-ID')}`;
                        
                        if (selectedKamarIds && selectedKamarIds.includes(kamarIdInt)) {
                            option.selected = true;
                        }
                        
                        kamarSelect.appendChild(option);
                    });
                    
                    kamarSelect.disabled = false;
                })
                .catch(error => {
                    console.error('Error fetching rooms:', error);
                    kamarSelect.innerHTML = '<option value="">Gagal memuat kamar</option>';
                });
        }
    </script>
</body>
</html>
