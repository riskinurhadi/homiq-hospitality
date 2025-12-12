<?php
// register.php
// Halaman registrasi untuk setup admin pertama kali
// Setelah ada admin, halaman ini bisa di-disable atau hanya bisa diakses oleh admin

session_start();

// Jika sudah login, redirect ke dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

require_once 'koneksi.php';

$error_message = '';
$success_message = '';

// Cek apakah sudah ada admin (optional: bisa di-disable setelah ada admin)
$check_admin = $koneksi->query("SELECT COUNT(*) as total FROM tbl_users WHERE role = 'admin'");
$admin_exists = $check_admin->fetch_assoc()['total'] > 0;

// Proses registrasi
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    $nama_lengkap = trim($_POST['nama_lengkap'] ?? '');
    $role = $_POST['role'] ?? 'admin';
    
    // Validasi
    if (empty($username) || empty($password) || empty($nama_lengkap)) {
        $error_message = 'Semua field wajib diisi!';
    } elseif (strlen($username) < 3) {
        $error_message = 'Username minimal 3 karakter!';
    } elseif (strlen($password) < 6) {
        $error_message = 'Password minimal 6 karakter!';
    } elseif ($password !== $password_confirm) {
        $error_message = 'Password dan konfirmasi password tidak sama!';
    } else {
        // Cek apakah username sudah ada
        $stmt = $koneksi->prepare("SELECT id_user FROM tbl_users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error_message = 'Username sudah digunakan!';
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert user baru
            $stmt = $koneksi->prepare("INSERT INTO tbl_users (username, password, nama_lengkap, role) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $username, $hashed_password, $nama_lengkap, $role);
            
            if ($stmt->execute()) {
                $success_message = 'Akun berhasil dibuat! Silakan login.';
                // Clear form (optional)
                $_POST = array();
            } else {
                $error_message = 'Terjadi kesalahan saat membuat akun. Silakan coba lagi.';
            }
        }
        
        $stmt->close();
    }
}

$koneksi->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrasi - CMS Guesthouse Adiputra</title>
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
            --primary-color: #3b82f6;
            --primary-dark: #2563eb;
            --bg-light: #f8fafc;
            --text-dark: #1e293b;
            --text-muted: #64748b;
            --border-color: #e2e8f0;
            --success-color: #10b981;
            --danger-color: #ef4444;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }

        .register-container {
            width: 100%;
            max-width: 480px;
        }

        .register-card {
            background: #ffffff;
            border-radius: 1.5rem;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            animation: slideUp 0.5s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .register-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            padding: 2rem 2rem 1.5rem;
            text-align: center;
            color: white;
        }

        .register-header .logo-icon {
            width: 56px;
            height: 56px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 1rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.75rem;
            margin-bottom: 0.75rem;
            backdrop-filter: blur(10px);
        }

        .register-header h1 {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }

        .register-header p {
            font-size: 0.9rem;
            opacity: 0.9;
        }

        .register-body {
            padding: 2rem;
        }

        .form-label {
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }

        .form-control, .form-select {
            border: 2px solid var(--border-color);
            border-radius: 0.75rem;
            padding: 0.875rem 1rem;
            font-size: 0.95rem;
            transition: all 0.2s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .input-group-text {
            background: var(--bg-light);
            border: 2px solid var(--border-color);
            border-right: none;
            border-radius: 0.75rem 0 0 0.75rem;
            color: var(--text-muted);
        }

        .form-control.with-icon {
            border-left: none;
            border-radius: 0 0.75rem 0.75rem 0;
        }

        .btn-register {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            border: none;
            border-radius: 0.75rem;
            padding: 0.875rem;
            font-weight: 600;
            font-size: 1rem;
            color: white;
            width: 100%;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }

        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(59, 130, 246, 0.4);
        }

        .btn-register:active {
            transform: translateY(0);
        }

        .alert {
            border-radius: 0.75rem;
            border: none;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }

        .alert-danger {
            background: #fef2f2;
            color: var(--danger-color);
        }

        .alert-success {
            background: #f0fdf4;
            color: var(--success-color);
        }

        .alert-info {
            background: #eff6ff;
            color: var(--primary-color);
        }

        .register-footer {
            text-align: center;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--border-color);
        }

        .register-footer a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            font-size: 0.9rem;
        }

        .register-footer a:hover {
            text-decoration: underline;
        }

        .password-toggle {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--text-muted);
            cursor: pointer;
            z-index: 10;
        }

        .password-toggle:hover {
            color: var(--primary-color);
        }

        .input-wrapper {
            position: relative;
        }

        .password-strength {
            font-size: 0.8rem;
            margin-top: 0.25rem;
            padding: 0.25rem 0;
        }

        .password-strength.weak {
            color: var(--danger-color);
        }

        .password-strength.medium {
            color: #f59e0b;
        }

        .password-strength.strong {
            color: var(--success-color);
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-card">
            <!-- Header -->
            <div class="register-header">
                <div class="logo-icon">
                    <i class="bi bi-person-plus"></i>
                </div>
                <h1>Buat Akun Admin</h1>
                <p>Setup akun pertama kali</p>
            </div>

            <!-- Body -->
            <div class="register-body">
                <?php if ($admin_exists): ?>
                    <div class="alert alert-info" role="alert">
                        <i class="bi bi-info-circle me-2"></i>
                        Admin sudah ada. Silakan <a href="login.php" class="alert-link">login</a> atau hubungi admin untuk membuat akun baru.
                    </div>
                <?php endif; ?>

                <!-- Alert Messages -->
                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-danger" role="alert">
                        <i class="bi bi-exclamation-circle me-2"></i>
                        <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($success_message)): ?>
                    <div class="alert alert-success" role="alert">
                        <i class="bi bi-check-circle me-2"></i>
                        <?php echo htmlspecialchars($success_message); ?>
                        <br>
                        <a href="login.php" class="alert-link mt-2 d-inline-block">Klik di sini untuk login</a>
                    </div>
                <?php endif; ?>

                <!-- Register Form -->
                <?php if (!$admin_exists || empty($success_message)): ?>
                <form method="POST" action="" id="registerForm">
                    <!-- Nama Lengkap -->
                    <div class="mb-3">
                        <label for="nama_lengkap" class="form-label">
                            <i class="bi bi-person me-1"></i> Nama Lengkap
                        </label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="bi bi-person-badge"></i>
                            </span>
                            <input 
                                type="text" 
                                class="form-control with-icon" 
                                id="nama_lengkap" 
                                name="nama_lengkap" 
                                placeholder="Masukkan nama lengkap"
                                required 
                                autofocus
                                value="<?php echo htmlspecialchars($_POST['nama_lengkap'] ?? ''); ?>"
                            >
                        </div>
                    </div>

                    <!-- Username -->
                    <div class="mb-3">
                        <label for="username" class="form-label">
                            <i class="bi bi-person me-1"></i> Username
                        </label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="bi bi-person"></i>
                            </span>
                            <input 
                                type="text" 
                                class="form-control with-icon" 
                                id="username" 
                                name="username" 
                                placeholder="Masukkan username"
                                required 
                                minlength="3"
                                value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                            >
                        </div>
                        <small class="text-muted">Minimal 3 karakter</small>
                    </div>

                    <!-- Role -->
                    <div class="mb-3">
                        <label for="role" class="form-label">
                            <i class="bi bi-shield-check me-1"></i> Role
                        </label>
                        <select class="form-select" id="role" name="role" required>
                            <option value="admin" <?php echo (($_POST['role'] ?? 'admin') == 'admin') ? 'selected' : ''; ?>>Admin</option>
                            <option value="front_office" <?php echo (($_POST['role'] ?? '') == 'front_office') ? 'selected' : ''; ?>>Front Office</option>
                            <option value="housekeeping" <?php echo (($_POST['role'] ?? '') == 'housekeeping') ? 'selected' : ''; ?>>Housekeeping</option>
                        </select>
                    </div>

                    <!-- Password -->
                    <div class="mb-3">
                        <label for="password" class="form-label">
                            <i class="bi bi-lock me-1"></i> Password
                        </label>
                        <div class="input-wrapper">
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="bi bi-lock-fill"></i>
                                </span>
                                <input 
                                    type="password" 
                                    class="form-control with-icon" 
                                    id="password" 
                                    name="password" 
                                    placeholder="Masukkan password"
                                    required
                                    minlength="6"
                                    onkeyup="checkPasswordStrength()"
                                >
                            </div>
                            <button 
                                type="button" 
                                class="password-toggle" 
                                onclick="togglePassword('password', 'password-icon')"
                                aria-label="Toggle password visibility"
                            >
                                <i class="bi bi-eye" id="password-icon"></i>
                            </button>
                        </div>
                        <div id="password-strength" class="password-strength"></div>
                        <small class="text-muted">Minimal 6 karakter</small>
                    </div>

                    <!-- Confirm Password -->
                    <div class="mb-4">
                        <label for="password_confirm" class="form-label">
                            <i class="bi bi-lock me-1"></i> Konfirmasi Password
                        </label>
                        <div class="input-wrapper">
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="bi bi-lock-fill"></i>
                                </span>
                                <input 
                                    type="password" 
                                    class="form-control with-icon" 
                                    id="password_confirm" 
                                    name="password_confirm" 
                                    placeholder="Konfirmasi password"
                                    required
                                    onkeyup="checkPasswordMatch()"
                                >
                            </div>
                            <button 
                                type="button" 
                                class="password-toggle" 
                                onclick="togglePassword('password_confirm', 'password-confirm-icon')"
                                aria-label="Toggle password visibility"
                            >
                                <i class="bi bi-eye" id="password-confirm-icon"></i>
                            </button>
                        </div>
                        <div id="password-match" class="password-strength"></div>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" class="btn btn-register">
                        <i class="bi bi-person-plus me-2"></i>
                        Buat Akun
                    </button>
                </form>
                <?php endif; ?>

                <!-- Footer -->
                <div class="register-footer">
                    <p class="text-muted mb-0" style="font-size: 0.85rem;">
                        Sudah punya akun? 
                        <a href="login.php">Login di sini</a>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle password visibility
        function togglePassword(inputId, iconId) {
            const passwordInput = document.getElementById(inputId);
            const passwordIcon = document.getElementById(iconId);
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                passwordIcon.classList.remove('bi-eye');
                passwordIcon.classList.add('bi-eye-slash');
            } else {
                passwordInput.type = 'password';
                passwordIcon.classList.remove('bi-eye-slash');
                passwordIcon.classList.add('bi-eye');
            }
        }

        // Check password strength
        function checkPasswordStrength() {
            const password = document.getElementById('password').value;
            const strengthDiv = document.getElementById('password-strength');
            
            if (password.length === 0) {
                strengthDiv.textContent = '';
                strengthDiv.className = 'password-strength';
                return;
            }
            
            let strength = 0;
            if (password.length >= 6) strength++;
            if (password.length >= 8) strength++;
            if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^a-zA-Z0-9]/.test(password)) strength++;
            
            if (strength <= 2) {
                strengthDiv.textContent = 'Kekuatan: Lemah';
                strengthDiv.className = 'password-strength weak';
            } else if (strength <= 3) {
                strengthDiv.textContent = 'Kekuatan: Sedang';
                strengthDiv.className = 'password-strength medium';
            } else {
                strengthDiv.textContent = 'Kekuatan: Kuat';
                strengthDiv.className = 'password-strength strong';
            }
        }

        // Check password match
        function checkPasswordMatch() {
            const password = document.getElementById('password').value;
            const passwordConfirm = document.getElementById('password_confirm').value;
            const matchDiv = document.getElementById('password-match');
            
            if (passwordConfirm.length === 0) {
                matchDiv.textContent = '';
                matchDiv.className = 'password-strength';
                return;
            }
            
            if (password === passwordConfirm) {
                matchDiv.textContent = '✓ Password cocok';
                matchDiv.className = 'password-strength strong';
            } else {
                matchDiv.textContent = '✗ Password tidak cocok';
                matchDiv.className = 'password-strength weak';
            }
        }

        // Form validation
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const passwordConfirm = document.getElementById('password_confirm').value;
            
            if (password !== passwordConfirm) {
                e.preventDefault();
                alert('Password dan konfirmasi password tidak sama!');
                return false;
            }
        });
    </script>
</body>
</html>

