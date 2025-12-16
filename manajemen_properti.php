<?php
// manajemen_properti.php
// Halaman untuk mengelola properti (CRUD)

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
        $nama_properti = trim($_POST['nama_properti'] ?? '');
        $alamat = trim($_POST['alamat'] ?? '');
        
        if (empty($nama_properti)) {
            $error_message = 'Nama properti wajib diisi!';
        } else {
            $stmt = $koneksi->prepare("INSERT INTO tbl_properti (nama_properti, alamat) VALUES (?, ?)");
            $stmt->bind_param("ss", $nama_properti, $alamat);
            
            if ($stmt->execute()) {
                $success_message = 'Properti berhasil ditambahkan!';
            } else {
                $error_message = 'Gagal menambahkan properti. Silakan coba lagi.';
            }
            $stmt->close();
        }
    } elseif ($action == 'edit') {
        $id_properti = (int)$_POST['id_properti'];
        $nama_properti = trim($_POST['nama_properti'] ?? '');
        $alamat = trim($_POST['alamat'] ?? '');
        
        if (empty($nama_properti)) {
            $error_message = 'Nama properti wajib diisi!';
        } else {
            $stmt = $koneksi->prepare("UPDATE tbl_properti SET nama_properti = ?, alamat = ? WHERE id_properti = ?");
            $stmt->bind_param("ssi", $nama_properti, $alamat, $id_properti);
            
            if ($stmt->execute()) {
                $success_message = 'Properti berhasil diupdate!';
            } else {
                $error_message = 'Gagal mengupdate properti. Silakan coba lagi.';
            }
            $stmt->close();
        }
    } elseif ($action == 'hapus') {
        $id_properti = (int)$_POST['id_properti'];
        
        $check_kamar = $koneksi->query("SELECT COUNT(*) as total FROM tbl_kamar WHERE id_properti = $id_properti");
        $kamar_count = $check_kamar->fetch_assoc()['total'];
        
        if ($kamar_count > 0) {
            $error_message = "Tidak bisa menghapus properti! Masih ada $kamar_count kamar yang menggunakan properti ini.";
        } else {
            $stmt = $koneksi->prepare("DELETE FROM tbl_properti WHERE id_properti = ?");
            $stmt->bind_param("i", $id_properti);
            
            if ($stmt->execute()) {
                $success_message = 'Properti berhasil dihapus!';
            } else {
                $error_message = 'Gagal menghapus properti. Silakan coba lagi.';
            }
            $stmt->close();
        }
    }
}

$edit_data = null;
if (isset($_GET['edit'])) {
    $id_edit = (int)$_GET['edit'];
    $result = $koneksi->query("SELECT * FROM tbl_properti WHERE id_properti = $id_edit");
    if ($result->num_rows > 0) {
        $edit_data = $result->fetch_assoc();
    }
}

$query = "SELECT p.*, 
          (SELECT COUNT(*) FROM tbl_kamar WHERE id_properti = p.id_properti) as jumlah_kamar
          FROM tbl_properti p 
          ORDER BY p.nama_properti";
$result_properti = $koneksi->query($query);

$koneksi->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Properti - CMS Guesthouse Adiputra</title>
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
                    <h4 class="fw-bold mb-1 text-dark">Manajemen Properti</h4>
                    <p class="text-muted mb-0" style="font-size: 0.9rem;">Kelola data properti guesthouse</p>
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
                    <h5 class="mb-4 fw-bold"><?php echo $edit_data ? 'Edit' : 'Tambah'; ?> Properti</h5>
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="<?php echo $edit_data ? 'edit' : 'tambah'; ?>">
                        <?php if ($edit_data): ?>
                            <input type="hidden" name="id_properti" value="<?php echo $edit_data['id_properti']; ?>">
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <label for="nama_properti" class="form-label">Nama Properti <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="nama_properti" name="nama_properti" 
                                   value="<?php echo htmlspecialchars($edit_data['nama_properti'] ?? ''); ?>" 
                                   required placeholder="Contoh: GH 1, Villa Adiputra">
                        </div>
                        
                        <div class="mb-3">
                            <label for="alamat" class="form-label">Alamat</label>
                            <textarea class="form-control" id="alamat" name="alamat" rows="3" 
                                      placeholder="Alamat lengkap properti"><?php echo htmlspecialchars($edit_data['alamat'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-<?php echo $edit_data ? 'check-lg' : 'plus-lg'; ?> me-2"></i>
                                <?php echo $edit_data ? 'Update' : 'Simpan'; ?>
                            </button>
                            <?php if ($edit_data): ?>
                                <a href="manajemen_properti.php" class="btn btn-outline-secondary">
                                    <i class="bi bi-x-lg me-2"></i>Batal
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="content-card">
                    <h5 class="mb-3 fw-bold">Daftar Properti</h5>
                    <?php if ($result_properti->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-modern">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Nama Properti</th>
                                        <th>Alamat</th>
                                        <th>Jumlah Kamar</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $no = 1;
                                    while ($row = $result_properti->fetch_assoc()): 
                                    ?>
                                        <tr>
                                            <td><?php echo $no++; ?></td>
                                            <td><strong><?php echo htmlspecialchars($row['nama_properti']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($row['alamat'] ?: '-'); ?></td>
                                            <td><span class="badge bg-primary badge-modern"><?php echo $row['jumlah_kamar']; ?> Kamar</span></td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="?edit=<?php echo $row['id_properti']; ?>" class="btn btn-sm btn-outline-primary" title="Edit"><i class="bi bi-pencil"></i></a>
                                                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="confirmDelete(<?php echo $row['id_properti']; ?>, '<?php echo htmlspecialchars(addslashes($row['nama_properti'])); ?>')" title="Hapus"><i class="bi bi-trash"></i></button>
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
                            <p class="text-muted mt-3">Belum ada properti. Silakan tambahkan properti baru.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <form id="deleteForm" method="POST" style="display: none;">
        <input type="hidden" name="action" value="hapus">
        <input type="hidden" name="id_properti" id="delete_id">
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
            if (confirm('Apakah Anda yakin ingin menghapus properti "' + nama + '"?\n\nCatatan: Properti yang masih memiliki kamar tidak bisa dihapus.')) {
                document.getElementById('delete_id').value = id;
                document.getElementById('deleteForm').submit();
            }
        }
    </script>
</body>
</html>
