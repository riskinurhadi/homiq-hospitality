<?php
// manajemen_user.php
// Halaman untuk mengelola pengguna (view + scaffold CRUD)

require_once 'auth_check.php';

// Hanya admin yang dapat mengakses halaman ini
if ($role_user != 'admin') {
    header("Location: dashboard.php");
    exit();
}

require_once 'koneksi.php';

$success_message = '';
$error_message = '';

// Logika untuk menambah user baru
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_user'])) {
    $nama_lengkap = $_POST['nama_lengkap'];
    $username = $_POST['username'];
    $password = $_POST['password'];
    $role = $_POST['role'];

    // Validasi dasar
    if (empty($nama_lengkap) || empty($username) || empty($password) || empty($role)) {
        $error_message = "Semua kolom harus diisi.";
    } else {
        // Cek apakah username sudah ada
        $stmt_check = $koneksi->prepare("SELECT id_user FROM tbl_users WHERE username = ?");
        $stmt_check->bind_param("s", $username);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();

        if ($result_check->num_rows > 0) {
            $error_message = "Username sudah digunakan. Silakan pilih username lain.";
        } else {
            // Hash password sebelum disimpan
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $koneksi->prepare("INSERT INTO tbl_users (nama_lengkap, username, password, role) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $nama_lengkap, $username, $hashed_password, $role);

            if ($stmt->execute()) {
                $success_message = "User baru berhasil ditambahkan.";
            } else {
                $error_message = "Gagal menambahkan user: " . $stmt->error;
            }
            $stmt->close();
        }
        $stmt_check->close();
    }
}

// Ambil semua user untuk ditampilkan
$result_users = $koneksi->query("SELECT id_user, username, nama_lengkap, role, dibuat_pada FROM tbl_users ORDER BY dibuat_pada DESC");

$koneksi->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen User - CIGS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

    <style>
        :root {
            --primary-color: #0d6efd;
            --secondary-color: #6c757d;
            --success-color: #198754;
            --danger-color: #dc3545;
            --light-color: #f8f9fa;
            --dark-color: #212529;
            --font-family-sans-serif: 'Inter', sans-serif;
            --sidebar-width: 280px;
        }

        body {
            font-family: var(--font-family-sans-serif);
            background-color: var(--light-color);
            color: var(--dark-color);
        }

        #main-content {
            transition: margin-left 0.3s;
            padding: 1.5rem;
            width: 100%;
        }

        @media (min-width: 992px) {
            #main-content {
                margin-left: var(--sidebar-width);
                width: calc(100% - var(--sidebar-width));
            }
        }

        .main-header {
            background: #fff;
            padding: 1.25rem 1.5rem;
            border-radius: 0.75rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            margin-bottom: 1.5rem;
            border: 1px solid #dee2e6;
        }

        .content-card {
            background: #fff;
            border-radius: 0.75rem;
            padding: 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            border: 1px solid #dee2e6;
        }

        .table-modern thead th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: #6c757d;
            font-size: 0.875rem;
            border-bottom: 2px solid #dee2e6;
        }

        .table-modern tbody tr:last-child td {
            border-bottom: none;
        }

        .badge-role {
            font-size: 0.8rem;
            font-weight: 600;
        }

        .modal-header, .modal-footer {
            border: none;
        }

    </style>
</head>
<body>
    <div class="d-flex">
        <?php include 'sidebar.php'; ?>
        <div id="main-content">
            <header class="main-header d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0"><i class="bi bi-people-fill me-2"></i>Manajemen User</h5>
                    <small class="text-muted">Kelola akun dan peran pengguna sistem.</small>
                </div>
                <div>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#tambahUserModal">
                        <i class="bi bi-person-plus-fill me-2"></i>Tambah User
                    </button>
                    <button class="btn btn-outline-secondary d-lg-none ms-2" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarMenu">
                        <i class="bi bi-list"></i>
                    </button>
                </div>
            </header>

            <div class="content-card">
                <div class="table-responsive">
                    <table class="table table-hover table-modern">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nama Lengkap</th>
                                <th>Username</th>
                                <th>Role</th>
                                <th>Tanggal Dibuat</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result_users->num_rows > 0): ?>
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
                                        <td><?php echo date('d M Y, H:i', strtotime($u['dibuat_pada'])); ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-secondary" disabled><i class="bi bi-pencil-square"></i></button>
                                            <button class="btn btn-sm btn-outline-danger" disabled><i class="bi bi-trash"></i></button>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center py-5">
                                        <i class="bi bi-inbox fs-1 text-muted"></i>
                                        <p class="mt-3 text-muted">Belum ada user terdaftar.</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Tambah User -->
    <div class="modal fade" id="tambahUserModal" tabindex="-1" aria-labelledby="tambahUserModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" action="manajemen_user.php">
                    <div class="modal-header">
                        <h5 class="modal-title" id="tambahUserModalLabel"><i class="bi bi-person-plus-fill me-2"></i>Form Tambah User Baru</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="tambah_user" value="1">
                        <div class="mb-3">
                            <label for="nama_lengkap" class="form-label">Nama Lengkap</label>
                            <input type="text" class="form-control" id="nama_lengkap" name="nama_lengkap" required>
                        </div>
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <div class="mb-3">
                            <label for="role" class="form-label">Role</label>
                            <select class="form-select" id="role" name="role" required>
                                <option value="" disabled selected>-- Pilih Role --</option>
                                <option value="front_office">Front Office</option>
                                <option value="housekeeping">Housekeeping</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        <?php if (!empty($success_message)): ?>
            Swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                text: '<?php echo $success_message; ?>',
                timer: 2000,
                showConfirmButton: false
            }).then(() => {
                // Refresh halaman untuk menampilkan data baru
                window.location.href = 'manajemen_user.php';
            });
        <?php endif; ?>

        <?php if (!empty($error_message)): ?>
            Swal.fire({
                icon: 'error',
                title: 'Oops...',
                text: '<?php echo $error_message; ?>'
            });
        <?php endif; ?>
    });
    </script>
</body>
</html>