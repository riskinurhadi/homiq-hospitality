<?php
session_start();
include 'koneksi.php';
include 'auth_check.php';

// Ambil nama user untuk header (fallback jika belum diset)
$nama_lengkap = $_SESSION['nama_lengkap'] ?? 'User';

// Cek apakah id_kamar ada di URL
if (!isset($_GET['id_kamar']) || empty($_GET['id_kamar'])) {
    header("Location: checklist_kamar.php");
    exit();
}

$id_kamar = $_GET['id_kamar'];
$id_user = $_SESSION['user_id']; // Asumsi user_id disimpan di session saat login

// Ambil data kamar dan properti
$query_kamar = "SELECT k.nama_kamar, k.tipe_kamar, k.status, p.nama_properti 
                FROM tbl_kamar k
                JOIN tbl_properti p ON k.id_properti = p.id_properti
                WHERE k.id_kamar = ?";
$stmt_kamar = $koneksi->prepare($query_kamar);
$stmt_kamar->bind_param("i", $id_kamar);
$stmt_kamar->execute();
$result_kamar = $stmt_kamar->get_result();
if ($result_kamar->num_rows === 0) {
    echo "Kamar tidak ditemukan.";
    exit();
}
$kamar = $result_kamar->fetch_assoc();
$stmt_kamar->close();

// Definisikan item checklist
$checklist_items = [
    'ac' => 'AC Dingin',
    'kamar_mandi' => 'Kamar Mandi Bersih',
    'sprei' => 'Sprei & Bedding Bersih',
    'air' => 'Air Mengalir Normal',
    'lampu' => 'Semua Lampu Menyala',
    'tv' => 'TV Berfungsi',
    'extra_bed' => 'Extra Bed (Jika ada permintaan)'
];

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checklist Kamar <?php echo htmlspecialchars($kamar['nama_kamar']); ?> - Homiq</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        html { font-size: 85%; }
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
            --radius-md: 12px;
            --radius-lg: 16px;
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
        }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--body-bg);
            color: var(--text-main);
            min-height: 100vh;
        }
        .wrapper { display: flex; }
        #main-content { flex-grow: 1; min-width: 0; transition: margin-left 0.3s ease; }
        
        /* Mobile toggle */
        .mobile-toggle-btn { display: none; font-size: 1.5rem; color: var(--text-main); background: none; border: none; }
        @media (max-width: 991.98px) {
            #main-content { margin-left: 0; }
            .mobile-toggle-btn { display: block; }
        }

        /* Card Styles */
        .card-modern {
            background: var(--card-bg);
            border: none;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
        }
        
        .checklist-item {
            padding: 1.25rem;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            margin-bottom: 1rem;
            background-color: #fff;
            transition: border-color 0.2s;
        }
        .checklist-item:hover {
            border-color: var(--primary);
        }
        
        .form-check-input:checked {
            background-color: var(--primary);
            border-color: var(--primary);
        }
        
        .section-title {
            font-size: 1rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
    </style>
</head>
<body class="<?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'Housekeeping') { echo 'has-bottom-nav'; } ?>">
    <div class="wrapper">
        <?php include 'sidebar.php'; ?>

        <main id="main-content" class="p-3 p-md-4">
            <!-- Header -->
            <header class="d-flex justify-content-between align-items-center mb-4">
                <div class="d-flex align-items-center">
                    <button class="mobile-toggle-btn me-3 d-lg-none" id="mobile-sidebar-toggle">
                        <i class="bi bi-list"></i>
                    </button>
                    <div>
                        <div class="d-flex align-items-center gap-2">
                            <a href="checklist_kamar.php" class="text-decoration-none text-muted"><i class="bi bi-arrow-left"></i></a>
                            <h4 class="fw-bold mb-0 text-dark">Form Checklist</h4>
                        </div>
                        <p class="text-muted mb-0 ms-4 ps-1" style="font-size: 0.9rem;">
                            <?php echo htmlspecialchars($kamar['nama_properti']); ?> &bull; Kamar <?php echo htmlspecialchars($kamar['nama_kamar']); ?>
                        </p>
                    </div>
                </div>
            </header>

            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <form action="proses_update_checklist.php" method="POST">
                        <input type="hidden" name="id_kamar" value="<?php echo $id_kamar; ?>">
                        <input type="hidden" name="id_user" value="<?php echo $id_user; ?>">

                        <div class="card-modern p-4 mb-4">
                            <div class="section-title text-primary">
                                <i class="bi bi-list-check"></i> Daftar Pengecekan
                            </div>
                            <p class="text-muted small mb-4">Pastikan semua item dalam kondisi 'Baik' sebelum mengubah status kamar menjadi 'Tersedia'.</p>

                            <?php foreach ($checklist_items as $key => $label): ?>
                            <div class="checklist-item">
                                <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center mb-2">
                                    <label class="form-label fw-bold mb-2 mb-sm-0 text-dark"><?php echo $label; ?></label>
                                    <div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="status[<?php echo $key; ?>]" id="<?php echo $key; ?>_baik" value="Baik" required>
                                            <label class="form-check-label text-success fw-medium" for="<?php echo $key; ?>_baik">Baik</label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="status[<?php echo $key; ?>]" id="<?php echo $key; ?>_perbaikan" value="Perbaikan">
                                            <label class="form-check-label text-danger fw-medium" for="<?php echo $key; ?>_perbaikan">Perbaikan</label>
                                        </div>
                                    </div>
                                </div>
                                <input type="text" class="form-control form-control-sm bg-light border-0" name="catatan[<?php echo $key; ?>]" placeholder="Tambahkan catatan jika ada masalah...">
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="card-modern p-4 mb-4">
                            <div class="mb-4">
                                <label class="form-label fw-bold text-dark"><i class="bi bi-arrow-repeat me-2"></i>Update Status Kamar</label>
                                <select class="form-select form-select-lg" name="status_kamar" required>
                                    <option value="">-- Pilih Status Akhir --</option>
                                    <option value="Tersedia">Tersedia (Siap Jual)</option>
                                    <option value="Kotor">Kotor (Perlu Dibersihkan)</option>
                                    <option value="Maintenance">Maintenance (Butuh Perbaikan)</option>
                                </select>
                            </div>

                            <div class="mb-2">
                                <label class="form-label fw-bold text-dark"><i class="bi bi-journal-text me-2"></i>Catatan Umum</label>
                                <textarea class="form-control" name="catatan_umum" rows="3" placeholder="Tambahkan catatan umum terkait kondisi kamar secara keseluruhan..."></textarea>
                            </div>
                        </div>

                        <div class="d-flex gap-2 justify-content-end mb-5">
                            <a href="checklist_kamar.php" class="btn btn-light border px-4">Batal</a>
                            <button type="submit" class="btn btn-primary px-4 fw-semibold">
                                <i class="bi bi-save me-2"></i>Simpan Laporan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Mobile sidebar toggle
        document.addEventListener('DOMContentLoaded', function() {
            const mobileSidebarToggleBtn = document.getElementById('mobile-sidebar-toggle');
            const body = document.body;
            if (mobileSidebarToggleBtn) {
                mobileSidebarToggleBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    body.classList.toggle('sidebar-mobile-show');
                });
            }
            document.addEventListener('click', function(e) {
                if (body.classList.contains('sidebar-mobile-show') && !e.target.closest('#sidebarMenu') && !e.target.closest('#mobile-sidebar-toggle')) {
                    body.classList.remove('sidebar-mobile-show');
                }
            });
        });
    </script>
</body>
</html>
