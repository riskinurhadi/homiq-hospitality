<?php
// manajemen_kamar.php
// Halaman untuk mengelola kamar (CRUD)

require_once 'auth_check.php';

if ($role_user != 'admin') {
    header("Location: dashboard.php");
    exit();
}

require_once 'koneksi.php';

$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action == 'tambah') {
        $id_properti = (int)$_POST['id_properti'];
        $nama_kamar = trim($_POST['nama_kamar'] ?? '');
        $tipe_kamar = trim($_POST['tipe_kamar'] ?? '');
        $harga_default = (float)$_POST['harga_default'];
        $status = $_POST['status'] ?? 'Tersedia';
        
        if (empty($nama_kamar) || empty($id_properti)) {
            $error_message = 'Nama kamar dan properti wajib diisi!';
        } else {
            $stmt = $koneksi->prepare("INSERT INTO tbl_kamar (id_properti, nama_kamar, tipe_kamar, harga_default, status) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("issds", $id_properti, $nama_kamar, $tipe_kamar, $harga_default, $status);
            
            if ($stmt->execute()) {
                $success_message = 'Kamar berhasil ditambahkan!';
            } else {
                $error_message = 'Gagal menambahkan kamar. Silakan coba lagi.';
            }
            $stmt->close();
        }
    } elseif ($action == 'edit') {
        $id_kamar = (int)$_POST['id_kamar'];
        $id_properti = (int)$_POST['id_properti'];
        $nama_kamar = trim($_POST['nama_kamar'] ?? '');
        $tipe_kamar = trim($_POST['tipe_kamar'] ?? '');
        $harga_default = (float)$_POST['harga_default'];
        $status = $_POST['status'] ?? 'Tersedia';
        
        if (empty($nama_kamar) || empty($id_properti)) {
            $error_message = 'Nama kamar dan properti wajib diisi!';
        } else {
            $stmt = $koneksi->prepare("UPDATE tbl_kamar SET id_properti = ?, nama_kamar = ?, tipe_kamar = ?, harga_default = ?, status = ? WHERE id_kamar = ?");
            $stmt->bind_param("issdsi", $id_properti, $nama_kamar, $tipe_kamar, $harga_default, $status, $id_kamar);
            
            if ($stmt->execute()) {
                $success_message = 'Kamar berhasil diupdate!';
            } else {
                $error_message = 'Gagal mengupdate kamar. Silakan coba lagi.';
            }
            $stmt->close();
        }
    } elseif ($action == 'hapus') {
        $id_kamar = (int)$_POST['id_kamar'];
        
        $check_reservasi = $koneksi->query("SELECT COUNT(*) as total FROM tbl_reservasi WHERE id_kamar = $id_kamar");
        $reservasi_count = $check_reservasi->fetch_assoc()['total'];
        
        if ($reservasi_count > 0) {
            $error_message = "Tidak bisa menghapus kamar! Masih ada $reservasi_count reservasi yang menggunakan kamar ini.";
        } else {
            $stmt = $koneksi->prepare("DELETE FROM tbl_kamar WHERE id_kamar = ?");
            $stmt->bind_param("i", $id_kamar);
            
            if ($stmt->execute()) {
                $success_message = 'Kamar berhasil dihapus!';
            } else {
                $error_message = 'Gagal menghapus kamar. Silakan coba lagi.';
            }
            $stmt->close();
        }
    }
}

$edit_data = null;
if (isset($_GET['edit'])) {
    $id_edit = (int)$_GET['edit'];
    $result = $koneksi->query("SELECT * FROM tbl_kamar WHERE id_kamar = $id_edit");
    if ($result->num_rows > 0) {
        $edit_data = $result->fetch_assoc();
    }
}

$filter_properti = isset($_GET['properti']) ? (int)$_GET['properti'] : 0;
$result_properti = $koneksi->query("SELECT * FROM tbl_properti ORDER BY nama_properti");

$query_kamar = "SELECT k.*, p.nama_properti, 
                (SELECT COUNT(*) FROM tbl_reservasi WHERE id_kamar = k.id_kamar AND status_booking IN ('Booking', 'Checked-in')) as reservasi_aktif
                FROM tbl_kamar k 
                JOIN tbl_properti p ON k.id_properti = p.id_properti";
if ($filter_properti > 0) {
    $query_kamar .= " WHERE k.id_properti = $filter_properti";
}
$query_kamar .= " ORDER BY p.nama_properti, k.nama_kamar";
$result_kamar = $koneksi->query($query_kamar);

$koneksi->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Kamar - CMS Guesthouse Adiputra</title>
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
        }
        .badge-modern {
            padding: 0.5rem 0.75rem;
            border-radius: 0.5rem;
            font-weight: 600;
            font-size: 0.85rem;
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
                    <h4 class="fw-bold mb-1 text-dark">Manajemen Kamar</h4>
                    <p class="text-muted mb-0" style="font-size: 0.9rem;">Kelola data kamar untuk setiap properti</p>
                </div>
            </div>
        </header>

        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success" role="alert">
                <i class="bi bi-check-circle me-2"></i><?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger" role="alert">
                <i class="bi bi-exclamation-circle me-2"></i><?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-lg-4 mb-4">
                <div class="content-card">
                    <h5 class="mb-4 fw-bold"><?php echo $edit_data ? 'Edit' : 'Tambah'; ?> Kamar</h5>
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="<?php echo $edit_data ? 'edit' : 'tambah'; ?>">
                        <?php if ($edit_data): ?>
                            <input type="hidden" name="id_kamar" value="<?php echo $edit_data['id_kamar']; ?>">
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <label for="id_properti" class="form-label">Properti <span class="text-danger">*</span></label>
                            <select class="form-select" id="id_properti" name="id_properti" required>
                                <option value="">Pilih Properti</option>
                                <?php 
                                $result_properti->data_seek(0);
                                while ($prop = $result_properti->fetch_assoc()): 
                                ?>
                                    <option value="<?php echo $prop['id_properti']; ?>" 
                                            <?php echo (($edit_data['id_properti'] ?? 0) == $prop['id_properti']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($prop['nama_properti']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="nama_kamar" class="form-label">Nama Kamar <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="nama_kamar" name="nama_kamar" 
                                   value="<?php echo htmlspecialchars($edit_data['nama_kamar'] ?? ''); ?>" 
                                   required placeholder="Contoh: 101, 102, Twin 1">
                        </div>
                        
                        <div class="mb-3">
                            <label for="tipe_kamar" class="form-label">Tipe Kamar</label>
                            <select class="form-select" id="tipe_kamar" name="tipe_kamar">
                                <option value="">Pilih Tipe</option>
                                <option value="Single" <?php echo (($edit_data['tipe_kamar'] ?? '') == 'Single') ? 'selected' : ''; ?>>Single</option>
                                <option value="Double" <?php echo (($edit_data['tipe_kamar'] ?? '') == 'Double') ? 'selected' : ''; ?>>Double</option>
                                <option value="Twin" <?php echo (($edit_data['tipe_kamar'] ?? '') == 'Twin') ? 'selected' : ''; ?>>Twin</option>
                                <option value="Suite" <?php echo (($edit_data['tipe_kamar'] ?? '') == 'Suite') ? 'selected' : ''; ?>>Suite</option>
                                <option value="Family" <?php echo (($edit_data['tipe_kamar'] ?? '') == 'Family') ? 'selected' : ''; ?>>Family</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="harga_default" class="form-label">Harga Default (Rp) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="harga_default" name="harga_default" 
                                   value="<?php echo $edit_data['harga_default'] ?? '0'; ?>" 
                                   required min="0" step="1000" placeholder="0">
                        </div>
                        
                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="Tersedia" <?php echo (($edit_data['status'] ?? 'Tersedia') == 'Tersedia') ? 'selected' : ''; ?>>Tersedia</option>
                                <option value="Rusak" <?php echo (($edit_data['status'] ?? '') == 'Rusak') ? 'selected' : ''; ?>>Rusak</option>
                            </select>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-<?php echo $edit_data ? 'check-lg' : 'plus-lg'; ?> me-2">
                                </i>
                                <?php echo $edit_data ? 'Update' : 'Simpan'; ?>
                            </button>
                            <?php if ($edit_data): ?>
                                <a href="manajemen_kamar.php" class="btn btn-outline-secondary">
                                    <i class="bi bi-x-lg me-2"></i>Batal
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="content-card">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0 fw-bold">Daftar Kamar</h5>
                        <div>
                            <select class="form-select form-select-sm" onchange="window.location.href='?properti='+this.value">
                                <option value="0" <?php echo $filter_properti == 0 ? 'selected' : ''; ?>>Semua Properti</option>
                                <?php 
                                $result_properti->data_seek(0);
                                while ($prop = $result_properti->fetch_assoc()): 
                                ?>
                                    <option value="<?php echo $prop['id_properti']; ?>" <?php echo $filter_properti == $prop['id_properti'] ? 'selected' : ''; ?> >
                                        <?php echo htmlspecialchars($prop['nama_properti']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                    
                    <?php if ($result_kamar->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-modern">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Properti</th>
                                        <th>Nama Kamar</th>
                                        <th>Tipe</th>
                                        <th>Harga Default</th>
                                        <th>Status</th>
                                        <th>Reservasi Aktif</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $no = 1;
                                    $result_kamar->data_seek(0);
                                    while ($row = $result_kamar->fetch_assoc()): 
                                    ?>
                                        <tr>
                                            <td><?php echo $no++; ?></td>
                                            <td><strong><?php echo htmlspecialchars($row['nama_properti']); ?></strong></td>
                                            <td><strong><?php echo htmlspecialchars($row['nama_kamar']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($row['tipe_kamar'] ?: '-'); ?></td>
                                            <td><strong>Rp <?php echo number_format($row['harga_default'], 0, ',', '.'); ?></strong></td>
                                            <td>
                                                <?php if ($row['status'] == 'Tersedia'): ?>
                                                    <span class="badge bg-success badge-modern">Tersedia</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger badge-modern">Rusak</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($row['reservasi_aktif'] > 0): ?>
                                                    <span class="badge bg-warning badge-modern"><?php echo $row['reservasi_aktif']; ?></span>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="?edit=<?php echo $row['id_kamar']; ?>" class="btn btn-sm btn-outline-primary" title="Edit"><i class="bi bi-pencil"></i></a>
                                                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="confirmDelete(<?php echo $row['id_kamar']; ?>, '<?php echo htmlspecialchars(addslashes($row['nama_kamar'])); ?>')" title="Hapus"><i class="bi bi-trash"></i></button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="bi bi-inbox" style="font-size: 3rem; color: var(--text-muted); opacity: 0.3;"></i>
                            <p class="text-muted mt-3">
                                <?php if ($filter_properti > 0): ?>
                                    Belum ada kamar untuk properti ini.
                                <?php else: ?>
                                    Belum ada kamar. Silakan tambahkan kamar baru.
                                <?php endif; ?>
                            </p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <form id="deleteForm" method="POST" style="display: none;">
        <input type="hidden" name="action" value="hapus">
        <input type="hidden" name="id_kamar" id="delete_id">
    </form>

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
            setTimeout(function() {
                const alerts = document.querySelectorAll('.alert');
                alerts.forEach(function(alert) {
                    alert.style.transition = 'opacity 0.5s';
                    alert.style.opacity = '0';
                    setTimeout(function() {
                        alert.remove();
                    }, 500);
                });
            }, 5000);
        });

        function confirmDelete(id, nama) {
            if (confirm('Apakah Anda yakin ingin menghapus kamar "' + nama + '"?\n\nCatatan: Kamar yang masih memiliki reservasi tidak bisa dihapus.')) {
                document.getElementById('delete_id').value = id;
                document.getElementById('deleteForm').submit();
            }
        }
    </script>
</body>
</html>
