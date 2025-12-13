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
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

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

        .card-modern {
            background: var(--card-bg);
            border: none;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
            overflow: hidden;
        }

        .table thead th {
            background-color: #f8fafc;
            font-weight: 600;
            color: var(--text-muted);
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid var(--border-color);
        }
        
        .table tbody tr:last-child td {
            border-bottom: none;
        }
        
        .table td {
             border-color: var(--border-color);
             vertical-align: middle;
        }
        
        .badge-role {
            font-size: 0.8rem;
            font-weight: 600;
            padding: 0.5em 0.8em;
        }

        .modal-header, .modal-footer {
            border-color: var(--border-color);
        }
        
        .form-control, .form-select {
            border-color: var(--border-color);
        }
        .form-control:focus, .form-select:focus {
            box-shadow: 0 0 0 0.25rem rgba(var(--primary-rgb), 0.25);
            border-color: var(--primary);
        }
        
        .btn-primary {
             background-color: var(--primary);
             border-color: var(--primary);
        }
        
        .swal2-popup {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

    </style>
</head>
<body style="overflow-x: hidden;">
    <div id="main-container">
        <?php include 'sidebar.php'; ?>
        <div id="main-content" class="flex-grow-1 p-3 p-md-4">
             <header class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h4 class="fw-bold mb-1 text-dark">Manajemen User</h4>
                    <p class="text-muted mb-0" style="font-size: 0.9rem;">Kelola akun dan peran pengguna sistem.</p>
                </div>
                <div>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#tambahUserModal">
                        <i class="bi bi-person-plus-fill me-2"></i>Tambah User
                    </button>
                </div>
            </header>

            <div class="card-modern">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th class="ps-4">ID</th>
                                <th>Nama Lengkap</th>
                                <th>Username</th>
                                <th>Role</th>
                                <th>Tanggal Dibuat</th>
                                <th class="pe-4">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result_users->num_rows > 0): ?>
                                <?php while ($u = $result_users->fetch_assoc()): ?>
                                    <tr>
                                        <td class="ps-4 text-muted">#<?php echo $u['id_user']; ?></td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="me-3">
                                                     <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($u['nama_lengkap']); ?>&background=random&color=fff&size=128&bold=true" 
                                                         alt="User" style="width: 40px; height: 40px; border-radius: 50%;">
                                                </div>
                                                <div>
                                                    <div class="fw-bold text-dark"><?php echo htmlspecialchars($u['nama_lengkap']); ?></div>
                                                    <small class="text-muted"><?php echo htmlspecialchars($u['username']); ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($u['username']); ?></td>
                                        <td>
                                            <?php 
                                                $clr = 'secondary';
                                                if ($u['role'] == 'admin') $clr = 'danger';
                                                if ($u['role'] == 'front_office') $clr = 'primary';
                                                if ($u['role'] == 'housekeeping') $clr = 'success';
                                            ?>
                                            <span class="badge bg-<?php echo $clr; ?>-soft text-<?php echo $clr; ?> border border-<?php echo $clr; ?>-light badge-role"><?php echo ucfirst(str_replace('_',' ', $u['role'])); ?></span>
                                        </td>
                                        <td class="text-muted"><?php echo date('d M Y, H:i', strtotime($u['dibuat_pada'])); ?></td>
                                        <td class="pe-4">
                                            <button class="btn btn-sm btn-outline-secondary" disabled><i class="bi bi-pencil-square"></i></button>
                                            <button class="btn btn-sm btn-outline-danger" disabled><i class="bi bi-trash"></i></button>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center py-5">
                                        <i class="bi bi-inbox fs-1 text-muted opacity-25"></i>
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
            <div class="modal-content" style="border-radius: var(--radius-lg);">
                <form method="POST" action="manajemen_user.php">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold" id="tambahUserModalLabel"><i class="bi bi-person-plus-fill me-2"></i>Form Tambah User Baru</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <input type="hidden" name="tambah_user" value="1">
                        <div class="mb-3">
                            <label for="nama_lengkap" class="form-label fw-medium">Nama Lengkap</label>
                            <input type="text" class="form-control" id="nama_lengkap" name="nama_lengkap" required>
                        </div>
                        <div class="mb-3">
                            <label for="username" class="form-label fw-medium">Username</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label fw-medium">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <div class="mb-3">
                            <label for="role" class="form-label fw-medium">Role</label>
                            <select class="form-select" id="role" name="role" required>
                                <option value="" disabled selected>-- Pilih Role --</option>
                                <option value="front_office">Front Office</option>
                                <option value="housekeeping">Housekeeping</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
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