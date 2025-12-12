<?php
// manajemen_user.php
// Halaman untuk mengelola pengguna (view + scaffold CRUD)

require_once 'auth_check.php';

if ($role_user != 'admin') {
    header("Location: dashboard.php");
    exit();
}

require_once 'koneksi.php';

$success_message = '';
$error_message = '';

// Ambil semua user
$result_users = $koneksi->query("SELECT id_user, username, nama_lengkap, role, dibuat_pada FROM tbl_users ORDER BY dibuat_pada DESC");

$koneksi->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen User - CMS Guesthouse Adiputra</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        :root {
            --sidebar-width: 280px; --primary-color: #3b82f6; --bg-light: #f8fafc; --bg-white: #ffffff; --text-dark: #1e293b; --text-muted: #64748b; --border-color: #e2e8f0;
        }
        body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: var(--bg-light); color: var(--text-dark); overflow-x: hidden; }
        #main-content { margin-left: 0; padding: 1.5rem; transition: margin-left 0.3s ease; width: 100%; max-width: 100%; }
        @media (min-width: 992px) { #main-content { margin-left: var(--sidebar-width); width: calc(100% - var(--sidebar-width)); } }
        .main-header { background: var(--bg-white); padding: 1.25rem 1.5rem; border-radius: 1.25rem; box-shadow: 0 4px 6px rgba(0,0,0,0.05); margin-bottom: 1.5rem; border: 1px solid var(--border-color); }
        .content-card { background: var(--bg-white); border-radius: 1.25rem; padding: 1.25rem; box-shadow: 0 4px 6px rgba(0,0,0,0.05); border: 1px solid var(--border-color); margin-bottom: 1.5rem; }
        .table-modern thead th { background: var(--bg-light); font-weight: 600; font-size: 0.85rem; color: var(--text-muted); border-bottom: 2px solid var(--border-color); padding: 0.8rem; }
        .table-modern tbody td { padding: 0.8rem; vertical-align: middle; border-bottom: 1px solid var(--border-color); }
        .badge-role { padding: 0.4rem 0.6rem; border-radius: 0.5rem; font-weight: 600; font-size: 0.8rem; }
        @media (max-width: 1366px) { html, body { font-size: 14px; } #main-content { padding: 1.1rem; } .content-card { padding: 1rem; } }
    </style>
    </head>
<body>
    <div class="d-flex">
        <?php include 'sidebar.php'; ?>
        <div id="main-content" style="flex:1; width:100%;">
            <header class="main-header d-flex justify-content-between align-items-center">
                <div>
                    <h5><i class="bi bi-people me-2"></i>Manajemen User</h5>
                    <small class="text-muted">Kelola akun dan peran pengguna</small>
                </div>
                <div>
                    <a href="register.php" class="btn btn-primary"><i class="bi bi-person-plus me-2"></i>Tambah User</a>
                    <button class="btn btn-outline-secondary d-lg-none ms-2" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarMenu"><i class="bi bi-list"></i></button>
                </div>
            </header>

            <div class="content-card">
                <?php if ($result_users->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-modern">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nama Lengkap</th>
                                    <th>Username</th>
                                    <th>Role</th>
                                    <th>Dibuat</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($u = $result_users->fetch_assoc()): ?>
                                    <tr>
                                        <td>#<?php echo $u['id_user']; ?></td>
                                        <td><strong><?php echo htmlspecialchars($u['nama_lengkap']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($u['username']); ?></td>
                                        <td>
                                            <?php 
                                                $clr = 'secondary';
                                                if ($u['role'] == 'admin') $clr = 'danger';
                                                if ($u['role'] == 'front_office') $clr = 'primary';
                                                if ($u['role'] == 'housekeeping') $clr = 'success';
                                            ?>
                                            <span class="badge bg-<?php echo $clr; ?> badge-role"><?php echo ucfirst(str_replace('_',' ', $u['role'])); ?></span>
                                        </td>
                                        <td><?php echo date('d M Y', strtotime($u['dibuat_pada'])); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="bi bi-inbox" style="font-size:3rem; color: var(--text-muted); opacity:0.3;"></i>
                        <p class="text-muted mt-3">Belum ada user terdaftar.</p>
                        <a href="register.php" class="btn btn-primary mt-2"><i class="bi bi-person-plus me-2"></i>Tambah User</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>


