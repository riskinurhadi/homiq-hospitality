<?php
// form_input_booking.php
// Form untuk input booking baru (Internal/OTS)

require_once 'auth_check.php';

// Cek apakah user adalah admin atau front_office
if (!in_array($role_user, ['admin', 'front_office'])) {
    header("Location: dashboard.php");
    exit();
}

require_once 'koneksi.php';

$success_message = '';
$error_message = '';

// Proses simpan booking
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_kamar = (int)$_POST['id_kamar'];
    $nama_tamu = trim($_POST['nama_tamu'] ?? '');
    $no_hp = trim($_POST['no_hp'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $tgl_checkin = $_POST['tgl_checkin'] ?? '';
    $tgl_checkout = $_POST['tgl_checkout'] ?? '';
    $jumlah_tamu = (int)$_POST['jumlah_tamu'];
    $harga_total = (float)$_POST['harga_total'];
    $platform_booking = $_POST['platform_booking'] ?? 'OTS';
    $status_pembayaran = $_POST['status_pembayaran'] ?? 'Belum Bayar';
    $catatan_operator = trim($_POST['catatan_operator'] ?? '');
    
    // Validasi
    if (empty($nama_tamu) || empty($no_hp) || empty($tgl_checkin) || empty($tgl_checkout) || empty($id_kamar)) {
        $error_message = 'Data wajib tidak boleh kosong!';
    } elseif ($tgl_checkout <= $tgl_checkin) {
        $error_message = 'Tanggal checkout harus setelah tanggal check-in!';
    } else {
        // Cek ketersediaan kamar
        $check_available = $koneksi->prepare("
            SELECT COUNT(*) as total 
            FROM tbl_reservasi 
            WHERE id_kamar = ? 
            AND status_booking != 'Canceled'
            AND (
                (tgl_checkin <= ? AND tgl_checkout > ?) OR
                (tgl_checkin < ? AND tgl_checkout >= ?) OR
                (tgl_checkin >= ? AND tgl_checkout > ?)
            )
        ");
        $check_available->bind_param("issssss", $id_kamar, $tgl_checkin, $tgl_checkin, $tgl_checkout, $tgl_checkout, $tgl_checkin, $tgl_checkout);
        $check_available->execute();
        $result_check = $check_available->get_result();
        $available = $result_check->fetch_assoc()['total'];
        $check_available->close();
        
        if ($available > 0) {
            $error_message = 'Kamar tidak tersedia pada tanggal yang dipilih!';
        } else {
            // Mulai transaksi
            $koneksi->begin_transaction();
            
            try {
                // Cari atau buat tamu
                $stmt_tamu = $koneksi->prepare("SELECT id_tamu FROM tbl_tamu WHERE no_hp = ?");
                $stmt_tamu->bind_param("s", $no_hp);
                $stmt_tamu->execute();
                $result_tamu = $stmt_tamu->get_result();
                
                if ($result_tamu->num_rows > 0) {
                    $id_tamu = $result_tamu->fetch_assoc()['id_tamu'];
                    // Update email jika ada
                    if (!empty($email)) {
                        $stmt_update = $koneksi->prepare("UPDATE tbl_tamu SET email = ? WHERE id_tamu = ?");
                        $stmt_update->bind_param("si", $email, $id_tamu);
                        $stmt_update->execute();
                        $stmt_update->close();
                    }
                } else {
                    // Buat tamu baru
                    $stmt_insert = $koneksi->prepare("INSERT INTO tbl_tamu (nama_lengkap, no_hp, email) VALUES (?, ?, ?)");
                    $stmt_insert->bind_param("sss", $nama_tamu, $no_hp, $email);
                    $stmt_insert->execute();
                    $id_tamu = $koneksi->insert_id;
                    $stmt_insert->close();
                }
                $stmt_tamu->close();
                
                // Insert reservasi
                $stmt_reservasi = $koneksi->prepare("
                    INSERT INTO tbl_reservasi 
                    (id_kamar, id_tamu, tgl_checkin, tgl_checkout, harga_total, jumlah_tamu, 
                     platform_booking, status_booking, status_pembayaran, catatan_operator, dibuat_oleh_user) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, 'Booking', ?, ?, ?)
                ");
                $stmt_reservasi->bind_param("iissdisssi", $id_kamar, $id_tamu, $tgl_checkin, $tgl_checkout, 
                                           $harga_total, $jumlah_tamu, $platform_booking, $status_pembayaran, 
                                           $catatan_operator, $user_id);
                
                if ($stmt_reservasi->execute()) {
                    $id_reservasi = $koneksi->insert_id;
                    $koneksi->commit();
                    $success_message = 'Booking berhasil dibuat! ID Reservasi: ' . $id_reservasi;
                    // Redirect ke detail setelah 2 detik
                    header("refresh:2;url=detail_reservasi.php?id=" . $id_reservasi);
                } else {
                    throw new Exception("Gagal menyimpan reservasi");
                }
                $stmt_reservasi->close();
                
            } catch (Exception $e) {
                $koneksi->rollback();
                $error_message = 'Terjadi kesalahan: ' . $e->getMessage();
            }
        }
    }
}

// Ambil semua properti dan kamar untuk dropdown
$result_properti = $koneksi->query("SELECT * FROM tbl_properti ORDER BY nama_properti");

// Jika ada properti yang dipilih, ambil kamarnya
$selected_properti = isset($_POST['id_properti']) ? (int)$_POST['id_properti'] : 0;
$kamar_list = [];
if ($selected_properti > 0) {
    $result_kamar = $koneksi->query("
        SELECT k.*, p.nama_properti 
        FROM tbl_kamar k 
        JOIN tbl_properti p ON k.id_properti = p.id_properti 
        WHERE k.id_properti = $selected_properti AND k.status = 'Tersedia'
        ORDER BY k.nama_kamar
    ");
    $kamar_list = [];
    while ($row = $result_kamar->fetch_assoc()) {
        $kamar_list[] = $row;
    }
}

$koneksi->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Input Booking Baru - CMS Guesthouse Adiputra</title>
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

        .main-header h5 {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-dark);
            margin: 0;
        }

        .content-card {
            background: var(--bg-white);
            border-radius: 1.25rem;
            padding: 1.25rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            border: 1px solid var(--border-color);
            margin-bottom: 1.5rem;
        }

        .form-label {
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
        }

        .form-control, .form-select {
            border: 2px solid var(--border-color);
            border-radius: 0.75rem;
            padding: 0.75rem 1rem;
            transition: all 0.2s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .btn-modern {
            border-radius: 0.75rem;
            font-weight: 600;
            padding: 0.6rem 1.2rem;
            transition: all 0.2s ease;
        }

        .btn-modern:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .alert-modern {
            border-radius: 0.75rem;
            border: none;
            padding: 0.85rem 1.2rem;
            margin-bottom: 1.25rem;
        }

        .section-divider {
            border-top: 2px solid var(--border-color);
            margin: 1.5rem 0;
            padding-top: 1.5rem;
        }

        .info-box {
            background: var(--bg-light);
            padding: 0.75rem;
            border-radius: 0.75rem;
            margin-bottom: 0.75rem;
        }

        .info-box strong {
            color: var(--primary-color);
        }

        /* Laptop compact */
        @media (max-width: 1366px) {
            html, body { font-size: 14px; }
            #main-content { padding: 1.1rem; }
            .main-header { padding: 1rem 1.25rem; }
            .content-card { padding: 1rem; }
            .form-control, .form-select { padding: 0.6rem 0.85rem; }
            .btn-modern { padding: 0.55rem 1rem; font-size: 0.95rem; }
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
                    <h5><i class="bi bi-calendar-plus me-2"></i>Input Booking Baru</h5>
                    <small class="text-muted">Form untuk booking internal/OTS</small>
                </div>
                <div>
                    <a href="daftar_reservasi.php" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-2"></i>Kembali
                    </a>
                    <button class="btn btn-outline-secondary d-lg-none ms-2" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarMenu">
                        <i class="bi bi-list"></i>
                    </button>
                </div>
            </header>

            <!-- Alert Messages -->
            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger alert-modern" role="alert">
                    <i class="bi bi-exclamation-circle me-2"></i><?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success alert-modern" role="alert">
                    <i class="bi bi-check-circle me-2"></i><?php echo htmlspecialchars($success_message); ?>
                    <br><small>Sedang mengalihkan ke halaman detail...</small>
                </div>
            <?php endif; ?>

            <div class="row">
                <div class="col-lg-8 mx-auto">
                    <div class="content-card">
                        <form method="POST" action="" id="bookingForm">
                            <!-- Data Tamu -->
                            <h6 class="mb-3"><i class="bi bi-person me-2"></i>Data Tamu</h6>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="nama_tamu" class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="nama_tamu" name="nama_tamu" 
                                           value="<?php echo htmlspecialchars($_POST['nama_tamu'] ?? ''); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="no_hp" class="form-label">No. HP <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="no_hp" name="no_hp" 
                                           value="<?php echo htmlspecialchars($_POST['no_hp'] ?? ''); ?>" required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                            </div>

                            <div class="section-divider">
                                <h6 class="mb-3"><i class="bi bi-calendar-range me-2"></i>Detail Reservasi</h6>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="id_properti" class="form-label">Properti <span class="text-danger">*</span></label>
                                    <select class="form-select" id="id_properti" name="id_properti" required onchange="loadKamar()">
                                        <option value="">Pilih Properti</option>
                                        <?php 
                                        $result_properti->data_seek(0);
                                        while ($prop = $result_properti->fetch_assoc()): 
                                        ?>
                                            <option value="<?php echo $prop['id_properti']; ?>" 
                                                    <?php echo ($selected_properti == $prop['id_properti']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($prop['nama_properti']); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="id_kamar" class="form-label">Kamar <span class="text-danger">*</span></label>
                                    <select class="form-select" id="id_kamar" name="id_kamar" required>
                                        <option value="">Pilih Properti terlebih dahulu</option>
                                        <?php foreach ($kamar_list as $kamar): ?>
                                            <option value="<?php echo $kamar['id_kamar']; ?>" 
                                                    data-harga="<?php echo $kamar['harga_default']; ?>">
                                                <?php echo htmlspecialchars($kamar['nama_kamar']); ?> 
                                                (<?php echo htmlspecialchars($kamar['nama_properti']); ?>) - 
                                                Rp <?php echo number_format($kamar['harga_default'], 0, ',', '.'); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="tgl_checkin" class="form-label">Tanggal Check-in <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="tgl_checkin" name="tgl_checkin" 
                                           value="<?php echo htmlspecialchars($_POST['tgl_checkin'] ?? ''); ?>" 
                                           required min="<?php echo date('Y-m-d'); ?>" onchange="calculatePrice()">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="tgl_checkout" class="form-label">Tanggal Check-out <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="tgl_checkout" name="tgl_checkout" 
                                           value="<?php echo htmlspecialchars($_POST['tgl_checkout'] ?? ''); ?>" 
                                           required min="<?php echo date('Y-m-d'); ?>" onchange="calculatePrice()">
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="jumlah_tamu" class="form-label">Jumlah Tamu <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" id="jumlah_tamu" name="jumlah_tamu" 
                                           value="<?php echo htmlspecialchars($_POST['jumlah_tamu'] ?? '1'); ?>" 
                                           required min="1">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="platform_booking" class="form-label">Platform Booking</label>
                                    <select class="form-select" id="platform_booking" name="platform_booking">
                                        <option value="OTS" <?php echo (($_POST['platform_booking'] ?? 'OTS') == 'OTS') ? 'selected' : ''; ?>>OTS (Off The Street)</option>
                                        <option value="Internal" <?php echo (($_POST['platform_booking'] ?? '') == 'Internal') ? 'selected' : ''; ?>>Internal</option>
                                        <option value="Agoda">Agoda</option>
                                        <option value="Booking.com">Booking.com</option>
                                        <option value="Traveloka">Traveloka</option>
                                        <option value="Tiket.com">Tiket.com</option>
                                        <option value="Lainnya">Lainnya</option>
                                    </select>
                                </div>
                            </div>

                            <div class="info-box">
                                <label for="harga_total" class="form-label">Harga Total (Rp) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control form-control-lg" id="harga_total" name="harga_total" 
                                       value="<?php echo htmlspecialchars($_POST['harga_total'] ?? '0'); ?>" 
                                       required min="0" step="1000" readonly style="font-weight: 700; font-size: 1.25rem;">
                                <small class="text-muted">Harga akan otomatis terhitung berdasarkan kamar dan durasi</small>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="status_pembayaran" class="form-label">Status Pembayaran</label>
                                    <select class="form-select" id="status_pembayaran" name="status_pembayaran">
                                        <option value="Belum Bayar" <?php echo (($_POST['status_pembayaran'] ?? 'Belum Bayar') == 'Belum Bayar') ? 'selected' : ''; ?>>Belum Bayar</option>
                                        <option value="DP" <?php echo (($_POST['status_pembayaran'] ?? '') == 'DP') ? 'selected' : ''; ?>>DP (Down Payment)</option>
                                        <option value="Lunas" <?php echo (($_POST['status_pembayaran'] ?? '') == 'Lunas') ? 'selected' : ''; ?>>Lunas</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="catatan_operator" class="form-label">Catatan</label>
                                <textarea class="form-control" id="catatan_operator" name="catatan_operator" rows="3" 
                                          placeholder="Catatan khusus untuk reservasi ini..."><?php echo htmlspecialchars($_POST['catatan_operator'] ?? ''); ?></textarea>
                            </div>

                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="daftar_reservasi.php" class="btn btn-outline-secondary btn-modern">
                                    <i class="bi bi-x-lg me-2"></i>Batal
                                </a>
                                <button type="submit" class="btn btn-primary btn-modern">
                                    <i class="bi bi-check-lg me-2"></i>Simpan Booking
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function loadKamar() {
            const idProperti = document.getElementById('id_properti').value;
            const kamarSelect = document.getElementById('id_kamar');
            
            kamarSelect.innerHTML = '<option value="">Loading...</option>';
            
            if (!idProperti) {
                kamarSelect.innerHTML = '<option value="">Pilih Properti terlebih dahulu</option>';
                return;
            }
            
            fetch('get_kamar.php?properti=' + idProperti)
                .then(response => response.json())
                .then(data => {
                    kamarSelect.innerHTML = '<option value="">Pilih Kamar</option>';
                    data.forEach(kamar => {
                        const option = document.createElement('option');
                        option.value = kamar.id_kamar;
                        option.textContent = kamar.nama_kamar + ' - Rp ' + parseInt(kamar.harga_default).toLocaleString('id-ID');
                        option.setAttribute('data-harga', kamar.harga_default);
                        kamarSelect.appendChild(option);
                    });
                    calculatePrice();
                })
                .catch(error => {
                    console.error('Error:', error);
                    kamarSelect.innerHTML = '<option value="">Error loading kamar</option>';
                });
        }

        function calculatePrice() {
            const kamarSelect = document.getElementById('id_kamar');
            const tglCheckin = document.getElementById('tgl_checkin').value;
            const tglCheckout = document.getElementById('tgl_checkout').value;
            const hargaTotal = document.getElementById('harga_total');
            
            if (!kamarSelect.value || !tglCheckin || !tglCheckout) {
                hargaTotal.value = 0;
                return;
            }
            
            const selectedOption = kamarSelect.options[kamarSelect.selectedIndex];
            const hargaPerMalam = parseFloat(selectedOption.getAttribute('data-harga')) || 0;
            
            if (tglCheckout <= tglCheckin) {
                hargaTotal.value = 0;
                return;
            }
            
            const checkin = new Date(tglCheckin);
            const checkout = new Date(tglCheckout);
            const diffTime = Math.abs(checkout - checkin);
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
            
            const total = hargaPerMalam * diffDays;
            hargaTotal.value = Math.round(total);
        }

        // Event listeners
        document.getElementById('id_kamar').addEventListener('change', calculatePrice);
        
        // Set min date untuk checkout
        document.getElementById('tgl_checkin').addEventListener('change', function() {
            const checkin = this.value;
            if (checkin) {
                const nextDay = new Date(checkin);
                nextDay.setDate(nextDay.getDate() + 1);
                document.getElementById('tgl_checkout').min = nextDay.toISOString().split('T')[0];
            }
        });
    </script>
</body>
</html>

