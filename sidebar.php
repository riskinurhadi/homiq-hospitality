<?php
// sidebar.php
// Sidebar navigation untuk semua halaman

// Menentukan halaman aktif
$current_page = basename($_SERVER['PHP_SELF']);
$is_pengaturan_page = (strpos($current_page, 'manajemen_') === 0 || $current_page == 'pengaturan_properti.php');
?>
<div class="offcanvas offcanvas-start offcanvas-lg sidebar-modern" data-bs-scroll="true" tabindex="-1" id="sidebarMenu">
    <div class="offcanvas-body">
        <ul class="nav flex-column sidebar-nav">
            <li class="nav-item">
                <a class="nav-link <?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>" href="dashboard.php">
                    <i class="bi bi-grid-3x3-gap"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            
            <?php if (in_array($role_user, ['admin', 'front_office'])): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo (in_array($current_page, ['daftar_reservasi.php', 'form_input_booking.php', 'detail_reservasi.php'])) ? 'active' : ''; ?>" href="daftar_reservasi.php">
                        <i class="bi bi-calendar-check"></i>
                        <span>Reservasi</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#">
                        <i class="bi bi-people"></i>
                        <span>Data Tamu</span>
                    </a>
                </li>
            <?php endif; ?>
            
            <?php if (in_array($role_user, ['admin', 'housekeeping'])): ?>
                <li class="nav-item">
                    <a class="nav-link" href="#">
                        <i class="bi bi-house-check"></i>
                        <span>Status Kamar</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#">
                        <i class="bi bi-wrench-adjustable"></i>
                        <span>Maintenance</span>
                    </a>
                </li>
            <?php endif; ?>
            
            <?php if ($role_user == 'admin'): ?>
                <li class="nav-item">
                    <a class="nav-link" href="#">
                        <i class="bi bi-journal-text"></i>
                        <span>Laporan</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $is_pengaturan_page ? 'active' : ''; ?>" 
                       href="#submenu-pengaturan" 
                       data-bs-toggle="collapse" 
                       role="button" 
                       aria-expanded="<?php echo $is_pengaturan_page ? 'true' : 'false'; ?>" 
                       aria-controls="submenu-pengaturan">
                        <i class="bi bi-gear-wide-connected"></i>
                        <span>Pengaturan</span>
                        <i class="bi bi-chevron-down ms-auto"></i>
                    </a>
                    <div class="collapse <?php echo $is_pengaturan_page ? 'show' : ''; ?>" id="submenu-pengaturan">
                        <ul class="nav flex-column">
                            <li class="nav-item">
                                <a class="nav-link <?php echo ($current_page == 'manajemen_properti.php') ? 'active-submenu' : ''; ?>" href="manajemen_properti.php">
                                    Properti
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo ($current_page == 'manajemen_kamar.php') ? 'active-submenu' : ''; ?>" href="manajemen_kamar.php">
                                    Kamar
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo ($current_page == 'manajemen_user.php') ? 'active-submenu' : ''; ?>" href="manajemen_user.php">
                                    User
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>
            <?php endif; ?>
        </ul>

        <div class="sidebar-logout">
            <ul class="nav flex-column sidebar-nav">
                <li class="nav-item">
                    <a class="nav-link nav-link-logout" href="logout.php">
                        <i class="bi bi-box-arrow-right"></i>
                        <span>Keluar</span>
                    </a>
                </li>
            </ul>
        </div>
    </div>
</div>

<style>
    .sidebar-modern .nav-link.active-submenu {
        background: rgba(255, 255, 255, 0.1);
        color: #ffffff;
        padding-left: 2.5rem;
        font-size: 0.9rem;
    }

    .sidebar-modern .nav-link.active-submenu:hover {
        background: rgba(255, 255, 255, 0.15);
    }

    .sidebar-modern .collapse .nav-link {
        padding-left: 2.5rem;
        font-size: 0.9rem;
    }
</style>

