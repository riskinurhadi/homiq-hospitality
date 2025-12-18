<?php
session_start();
include '../koneksi.php';
include '../auth_check.php';

// Ambil nama user untuk header (fallback jika belum diset)
$nama_lengkap = $_SESSION['nama_lengkap'] ?? 'User';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checklist Kamar - Homiq</title>
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
            transition: transform 0.2s, box-shadow 0.2s;
            height: 100%;
            overflow: hidden;
            text-decoration: none;
            display: block;
            color: inherit;
        }
        .card-modern:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-md);
            color: inherit;
        }
        .card-body { padding: 1.5rem; }
        
        .property-section { margin-bottom: 2rem; }
        .property-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .room-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: var(--primary-soft);
            color: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }
        
        .room-name {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 0.25rem;
        }
        
        .room-type {
            font-size: 0.85rem;
            color: var(--text-muted);
            margin-bottom: 1rem;
        }
        
        .status-badge {
            font-size: 0.75rem;
            padding: 6px 12px;
            border-radius: 30px;
            font-weight: 600;
            display: inline-block;
        }
        
        .status-Tersedia { background: rgba(6, 214, 160, 0.1); color: var(--success); }
        .status-Kotor { background: rgba(239, 71, 111, 0.1); color: var(--danger); }
        .status-Maintenance { background: rgba(255, 209, 102, 0.1); color: #ff9f1c; }
        .status-Tidak-Tersedia { background: #e2e8f0; color: var(--text-muted); }
    </style>
</head>
<body class="<?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'Housekeeping') { echo 'has-bottom-nav'; } ?>">
    <div class="wrapper">
        <?php include '../sidebar.php'; ?>

        <main id="main-content" class="p-3 p-md-4">
            <!-- Header -->
            <header class="d-flex justify-content-between align-items-center mb-4">
                <div class="d-flex align-items-center">
                    <button class="mobile-toggle-btn me-3 d-lg-none" id="mobile-sidebar-toggle">
                        <i class="bi bi-list"></i>
                    </button>
                    <div>
                        <h4 class="fw-bold mb-1 text-dark">Checklist Kamar</h4>
                        <p class="text-muted mb-0" style="font-size: 0.9rem;">Pilih kamar untuk inspeksi kebersihan</p>
                    </div>
                </div>
            </header>

            <?php if (isset($_SESSION['flash_message'])): ?>
                <div class="alert alert-<?php echo $_SESSION['flash_message']['type']; ?> alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
                    <i class="bi bi-info-circle me-2"></i>
                    <?php echo $_SESSION['flash_message']['message']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['flash_message']); ?>
            <?php endif; ?>

            <!-- Content -->
            <?php
            // Query Properti
            $query_properti = "SELECT * FROM tbl_properti ORDER BY nama_properti ASC";
            $result_properti = mysqli_query($koneksi, $query_properti);

            if (mysqli_num_rows($result_properti) > 0) {
                while ($properti = mysqli_fetch_assoc($result_properti)) {
                    ?>
                    <div class="property-section">
                        <div class="property-title">
                            <i class="bi bi-building"></i>
                            <?php echo htmlspecialchars($properti['nama_properti']); ?>
                        </div>
                        
                        <div class="row g-3">
                            <?php
                            $id_properti = $properti['id_properti'];
                            $query_kamar = "SELECT * FROM tbl_kamar WHERE id_properti = ? ORDER BY nama_kamar ASC";
                            $stmt = $koneksi->prepare($query_kamar);
                            $stmt->bind_param("i", $id_properti);
                            $stmt->execute();
                            $result_kamar = $stmt->get_result();

                            if ($result_kamar->num_rows > 0) {
                                while ($kamar = $result_kamar->fetch_assoc()) {
                                    $status_class = 'status-' . str_replace(' ', '-', $kamar['status']);
                                    ?>
                                    <div class="col-xl-3 col-lg-4 col-md-6">
                                        <a href="form_checklist.php?id_kamar=<?php echo $kamar['id_kamar']; ?>" class="card-modern">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <div class="room-icon">
                                                        <i class="bi bi-door-closed"></i>
                                                    </div>
                                                    <span class="status-badge <?php echo $status_class; ?>">
                                                        <?php echo htmlspecialchars($kamar['status']); ?>
                                                    </span>
                                                </div>
                                                <div class="room-name">Kamar <?php echo htmlspecialchars($kamar['nama_kamar']); ?></div>
                                                <div class="room-type"><?php echo htmlspecialchars($kamar['tipe_kamar']); ?></div>
                                                <div class="d-flex align-items-center text-primary small fw-semibold">
                                                    <span>Mulai Checklist</span>
                                                    <i class="bi bi-arrow-right ms-2"></i>
                                                </div>
                                            </div>
                                        </a>
                                    </div>
                                    <?php
                                }
                            } else {
                                echo '<div class="col-12"><div class="alert alert-light border-0 shadow-sm text-muted"><i class="bi bi-info-circle me-2"></i>Belum ada kamar di properti ini.</div></div>';
                            }
                            $stmt->close();
                            ?>
                        </div>
                    </div>
                    <?php
                }
            } else {
                echo '<div class="text-center py-5 text-muted"><i class="bi bi-building-slash fs-1 opacity-25"></i><p class="mt-3">Belum ada properti yang terdaftar.</p></div>';
            }
            ?>
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
