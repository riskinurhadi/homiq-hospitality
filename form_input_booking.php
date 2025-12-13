<?php
// form_input_booking.php
// Form untuk input booking baru (Internal/OTS) atau edit

require_once 'auth_check.php';

// Cek apakah user adalah admin atau front_office
if (!in_array($role_user, ['admin', 'front_office'])) {
    header("Location: dashboard.php");
    exit();
}

require_once 'koneksi.php';

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
        // Redirect jika reservasi tidak ditemukan
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
    $id_kamar = (int)$_POST['id_kamar'];
    $nama_tamu = trim($_POST['nama_tamu'] ?? '');
    $no_hp = trim($_POST['no_hp'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $tgl_checkin = $_POST['tgl_checkin'] ?? '';
    $tgl_checkout = $_POST['tgl_checkout'] ?? '';
    $jumlah_tamu = (int)$_POST['jumlah_tamu'];
    $harga_total = (float)str_replace('.', '', $_POST['harga_total']);
    $platform_booking = $_POST['platform_booking'] ?? 'OTS';
    $status_pembayaran = $_POST['status_pembayaran'] ?? 'Belum Bayar';
    $catatan_operator = trim($_POST['catatan_operator'] ?? '');
    
    // Validasi dasar
    if (empty($nama_tamu) || empty($no_hp) || empty($tgl_checkin) || empty($tgl_checkout) || empty($id_kamar)) {
        $error_message = 'Semua field yang ditandai * tidak boleh kosong!';
    } elseif (new DateTime($tgl_checkout) <= new DateTime($tgl_checkin)) {
        $error_message = 'Tanggal checkout harus setelah tanggal check-in!';
    } else {
        $koneksi->begin_transaction();
        try {
            // 1. Urus data tamu (cari atau buat baru)
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

            if ($is_edit_mode) {
                // 2. Update reservasi yang ada
                $stmt_reservasi = $koneksi->prepare("
                    UPDATE tbl_reservasi 
                    SET id_kamar = ?, id_tamu = ?, tgl_checkin = ?, tgl_checkout = ?, harga_total = ?, 
                        jumlah_tamu = ?, platform_booking = ?, status_pembayaran = ?, catatan_operator = ?
                    WHERE id_reservasi = ?
                ");
                $stmt_reservasi->bind_param("iissdisssi", $id_kamar, $id_tamu, $tgl_checkin, $tgl_checkout, 
                                           $harga_total, $jumlah_tamu, $platform_booking, $status_pembayaran, 
                                           $catatan_operator, $id_reservasi_edit);
                $stmt_reservasi->execute();
                $success_message = "Reservasi berhasil diupdate!";
                header("refresh:2;url=detail_reservasi.php?id=" . $id_reservasi_edit);

            } else {
                // 3. Insert reservasi baru
                $stmt_reservasi = $koneksi->prepare("
                    INSERT INTO tbl_reservasi 
                    (id_kamar, id_tamu, tgl_checkin, tgl_checkout, harga_total, jumlah_tamu, 
                     platform_booking, status_booking, status_pembayaran, catatan_operator, dibuat_oleh_user) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, 'Booking', ?, ?, ?)
                ");
                $stmt_reservasi->bind_param("iissdisssi", $id_kamar, $id_tamu, $tgl_checkin, $tgl_checkout, 
                                           $harga_total, $jumlah_tamu, $platform_booking, $status_pembayaran, 
                                           $catatan_operator, $user_id);
                $stmt_reservasi->execute();
                $id_reservasi_baru = $koneksi->insert_id;
                $success_message = "Booking berhasil dibuat! ID Reservasi: $id_reservasi_baru";
                header("refresh:2;url=detail_reservasi.php?id=" . $id_reservasi_baru);
            }
            
            $stmt_reservasi->close();
            $koneksi->commit();

        } catch (Exception $e) {
            $koneksi->rollback();
            $error_message = 'Terjadi kesalahan: ' . $e->getMessage();
        }
    }
}

// Ambil semua properti untuk dropdown
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
            --sidebar-bg: #0f172a;
            --radius-md: 12px;
            --radius-lg: 16px;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--body-bg);
            color: var(--text-main);
        }

        #main-container { display: flex; min-height: 100vh; }
        #main-content {
            width: 100%;
            transition: width 0.3s ease;
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
        .price-display {
            background-color: var(--primary-soft);
            border-radius: var(--radius-md);
            padding: 1rem;
            text-align: center;
        }
        .price-display h4 {
            color: var(--primary);
            font-weight: 800;
            margin: 0;
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
                    <h4 class="fw-bold mb-1 text-dark"><?php echo $page_title; ?></h4>
                    <p class="text-muted mb-0" style="font-size: 0.9rem;"><?php echo $page_sub_title; ?></p>
                </div>
                <div>
                    <a href="daftar_reservasi.php" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i>Kembali
                    </a>
                </div>
            </header>

            <!-- Alert Messages -->
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
                            <select class="form-select" id="id_kamar" name="id_kamar" required onchange="calculatePrice()">
                                <option value="">Pilih Properti dahulu</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="tgl_checkin" class="form-label">Tgl Check-in <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="tgl_checkin" name="tgl_checkin" 
                                   value="<?php echo htmlspecialchars($reservasi_data['tgl_checkin'] ?? date('Y-m-d')); ?>" required onchange="calculatePrice()">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="tgl_checkout" class="form-label">Tgl Check-out <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="tgl_checkout" name="tgl_checkout" 
                                   value="<?php echo htmlspecialchars($reservasi_data['tgl_checkout'] ?? ''); ?>" required onchange="calculatePrice()">
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
                            <div class="price-display">
                                 <h4>Rp <span id="harga_text">0</span></h4>
                            </div>
                            <input type="hidden" id="harga_total" name="harga_total" value="<?php echo $reservasi_data['harga_total'] ?? 0; ?>">
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
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Set min date untuk checkin
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('tgl_checkin').min = today;

            document.getElementById('tgl_checkin').addEventListener('change', function() {
                const checkinDate = this.value;
                if (checkinDate) {
                    const nextDay = new Date(checkinDate);
                    nextDay.setDate(nextDay.getDate() + 1);
                    document.getElementById('tgl_checkout').min = nextDay.toISOString().split('T')[0];
                }
            });

            // Trigger loadKamar if in edit mode and properti is selected
            if (<?php echo $is_edit_mode ? 'true' : 'false'; ?> && document.getElementById('id_properti').value) {
                loadKamar(<?php echo $reservasi_data['id_kamar'] ?? 'null'; ?>);
            }
        });

        function loadKamar(selectedKamarId = null) {
            const idProperti = document.getElementById('id_properti').value;
            const kamarSelect = document.getElementById('id_kamar');
            
            kamarSelect.innerHTML = '<option value="">Memuat kamar...</option>';
            
            if (!idProperti) {
                kamarSelect.innerHTML = '<option value="">Pilih Properti</option>';
                return;
            }
            
            fetch(`get_kamar.php?properti=${idProperti}`)
                .then(response => response.json())
                .then(data => {
                    kamarSelect.innerHTML = '<option value="">Pilih Kamar</option>';
                    data.forEach(kamar => {
                        const option = document.createElement('option');
                        option.value = kamar.id_kamar;
                        option.textContent = `${kamar.nama_kamar} - Rp ${parseInt(kamar.harga_default).toLocaleString('id-ID')}`;
                        option.dataset.harga = kamar.harga_default;
                        if(selectedKamarId && kamar.id_kamar == selectedKamarId) {
                            option.selected = true;
                        }
                        kamarSelect.appendChild(option);
                    });
                    calculatePrice();
                })
                .catch(error => {
                    console.error('Error fetching rooms:', error);
                    kamarSelect.innerHTML = '<option value="">Gagal memuat</option>';
                });
        }

        function calculatePrice() {
            const kamarSelect = document.getElementById('id_kamar');
            const checkinVal = document.getElementById('tgl_checkin').value;
            const checkoutVal = document.getElementById('tgl_checkout').value;
            const hargaText = document.getElementById('harga_text');
            const hargaInput = document.getElementById('harga_total');
            
            const selectedOption = kamarSelect.options[kamarSelect.selectedIndex];
            const hargaPerMalam = selectedOption ? parseFloat(selectedOption.dataset.harga) : 0;
            
            if (!checkinVal || !checkoutVal || !hargaPerMalam) {
                hargaText.textContent = "0";
                hargaInput.value = 0;
                return;
            }
            
            const checkinDate = new Date(checkinVal);
            const checkoutDate = new Date(checkoutVal);
            
            if (checkoutDate <= checkinDate) {
                hargaText.textContent = "0";
                hargaInput.value = 0;
                return;
            }
            
            const diffTime = Math.abs(checkoutDate - checkinDate);
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
            
            const total = hargaPerMalam * diffDays;
            hargaText.textContent = total.toLocaleString('id-ID');
            hargaInput.value = total;
        }
    </script>
</body>
</html>

