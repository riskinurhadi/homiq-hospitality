<?php
// manajemen_properti.php
// Halaman untuk mengelola properti (CRUD)

require_once 'auth_check.php';

// Cek apakah user adalah admin
if ($role_user != 'admin') {
    header("Location: dashboard.php");
    exit();
}

require_once 'koneksi.php';

$success_message = '';
$error_message = '';

// Proses Tambah/Edit/Delete
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
        
        // Cek apakah ada kamar yang menggunakan properti ini
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

// Ambil data properti untuk edit
$edit_data = null;
if (isset($_GET['edit'])) {
    $id_edit = (int)$_GET['edit'];
    $result = $koneksi->query("SELECT * FROM tbl_properti WHERE id_properti = $id_edit");
    if ($result->num_rows > 0) {
        $edit_data = $result->fetch_assoc();
    }
}

// Ambil semua properti
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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --sidebar-width: 280px;
            --sidebar-bg: linear-gradient(180deg, #1e293b 0%, #0f172a 100%);
            --sidebar-text: rgba(255, 255, 255, 0.75);
            --sidebar-active: #ffffff;
            --sidebar-hover: rgba(255, 255, 255, 0.08);
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

        /* SIDEBAR - Same as dashboard */
        .sidebar-modern.offcanvas {
            width: var(--sidebar-width);
            background: var(--sidebar-bg);
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
            color: var(--sidebar-text);
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
            background: var(--sidebar-hover);
            color: #ffffff;
            transform: translateX(4px);
        }

        .sidebar-modern .nav-link:hover::before {
            transform: scaleY(1);
        }

        .sidebar-modern .nav-link.active {
            background: var(--sidebar-active);
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

        /* MAIN CONTENT */
        #main-content {
            margin-left: 0;
            padding: 1.5rem;
            transition: margin-left 0.3s ease;
            width: 100%;
            max-width: 100%;
            overflow-x: hidden;
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

        /* HEADER */
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

        /* CARD */
        .content-card {
            background: var(--bg-white);
            border-radius: 1.25rem;
            padding: 1.25rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            border: 1px solid var(--border-color);
            margin-bottom: 1.5rem;
        }

        .card-header-custom {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--border-color);
        }

        .card-header-custom h5 {
            font-size: 1.25rem;
            font-weight: 700;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .card-header-custom h5 i {
            color: var(--primary-color);
        }

        /* TABLE */
        .table-modern {
            width: 100%;
        }

        .table-modern thead th {
            background: var(--bg-light);
            font-weight: 600;
            font-size: 0.85rem;
            color: var(--text-muted);
            border-bottom: 2px solid var(--border-color);
            padding: 0.8rem;
        }

        .table-modern tbody td {
            padding: 0.8rem;
            vertical-align: middle;
            border-bottom: 1px solid var(--border-color);
        }

        .table-modern tbody tr:hover {
            background: var(--bg-light);
        }

        /* BUTTONS */
        .btn-modern {
            border-radius: 0.75rem;
            font-weight: 600;
            padding: 0.45rem 0.9rem;
            transition: all 0.2s ease;
        }

        .btn-modern:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        /* FORM */
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

        /* ALERT */
        .alert-modern {
            border-radius: 0.75rem;
            border: none;
            padding: 0.85rem 1.2rem;
            margin-bottom: 1.25rem;
        }

        .badge-modern {
            padding: 0.5rem 0.75rem;
            border-radius: 0.5rem;
            font-weight: 600;
            font-size: 0.85rem;
        }

        /* Laptop compact */
        @media (max-width: 1366px) {
            html, body { font-size: 14px; }
            #main-content { padding: 1.1rem; }
            .main-header { padding: 1rem 1.25rem; }
            .content-card { padding: 1rem; }
            .table-modern thead th { padding: 0.65rem; font-size: 0.8rem; }
            .table-modern tbody td { padding: 0.65rem; }
            .btn, .form-select, .form-control { font-size: 0.9rem; }
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
                    <h5><i class="bi bi-building me-2"></i>Manajemen Properti</h5>
                    <small class="text-muted">Kelola data properti guesthouse</small>
                </div>
                <div>
                    <button class="btn btn-outline-secondary d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarMenu">
                        <i class="bi bi-list"></i>
                    </button>
                </div>
            </header>

            <!-- Alert Messages -->
            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success alert-modern" role="alert">
                    <i class="bi bi-check-circle me-2"></i><?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger alert-modern" role="alert">
                    <i class="bi bi-exclamation-circle me-2"></i><?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <div class="row">
                <!-- Form Card -->
                <div class="col-lg-4 mb-4">
                    <div class="content-card">
                        <div class="card-header-custom">
                            <h5>
                                <i class="bi bi-<?php echo $edit_data ? 'pencil' : 'plus-circle'; ?>"></i>
                                <?php echo $edit_data ? 'Edit' : 'Tambah'; ?> Properti
                            </h5>
                        </div>
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
                                <button type="submit" class="btn btn-primary btn-modern">
                                    <i class="bi bi-<?php echo $edit_data ? 'check-lg' : 'plus-lg'; ?> me-2"></i>
                                    <?php echo $edit_data ? 'Update' : 'Simpan'; ?>
                                </button>
                                <?php if ($edit_data): ?>
                                    <a href="manajemen_properti.php" class="btn btn-outline-secondary btn-modern">
                                        <i class="bi bi-x-lg me-2"></i>Batal
                                    </a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Table Card -->
                <div class="col-lg-8">
                    <div class="content-card">
                        <div class="card-header-custom">
                            <h5><i class="bi bi-list-ul"></i>Daftar Properti</h5>
                        </div>
                        
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
                                                <td>
                                                    <strong><?php echo htmlspecialchars($row['nama_properti']); ?></strong>
                                                </td>
                                                <td>
                                                    <?php echo htmlspecialchars($row['alamat'] ?: '-'); ?>
                                                </td>
                                                <td>
                                                    <span class="badge bg-primary badge-modern">
                                                        <?php echo $row['jumlah_kamar']; ?> Kamar
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        <a href="?edit=<?php echo $row['id_properti']; ?>" 
                                                           class="btn btn-sm btn-outline-primary" 
                                                           title="Edit">
                                                            <i class="bi bi-pencil"></i>
                                                        </a>
                                                        <button type="button" 
                                                                class="btn btn-sm btn-outline-danger" 
                                                                onclick="confirmDelete(<?php echo $row['id_properti']; ?>, '<?php echo htmlspecialchars(addslashes($row['nama_properti'])); ?>')"
                                                                title="Hapus">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
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
        </div>
    </div>

    <!-- Delete Form (Hidden) -->
    <form id="deleteForm" method="POST" style="display: none;">
        <input type="hidden" name="action" value="hapus">
        <input type="hidden" name="id_properti" id="delete_id">
    </form>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function confirmDelete(id, nama) {
            if (confirm('Apakah Anda yakin ingin menghapus properti "' + nama + '"?\n\nCatatan: Properti yang masih memiliki kamar tidak bisa dihapus.')) {
                document.getElementById('delete_id').value = id;
                document.getElementById('deleteForm').submit();
            }
        }

        // Auto hide alerts
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
    </script>
</body>
</html>

