<?php
// sidebar.php
// Sidebar navigation untuk semua halaman

// Menentukan halaman aktif
$current_page = basename($_SERVER['PHP_SELF']);
$is_pengaturan_page = (strpos($current_page, 'manajemen_') === 0 || $current_page == 'pengaturan_properti.php');
?>
<div class="sidebar-modern shadow-sm d-none d-lg-block" id="sidebarMenu">
    <div class="offcanvas-body">
        <div class="d-flex flex-column h-100">
            <div class="sidebar-header d-flex align-items-center justify-content-center py-3 mb-3">
                <a href="dashboard.php" class="text-decoration-none text-white fs-5 fw-bold">
                    <i class="bi bi-buildings-fill me-2"></i>
                    <span class="menu-text">HomiQ</span>
                </a>
            </div>
        <ul class="nav flex-column sidebar-nav">
            <li class="nav-item">
                <a class="nav-link <?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>" href="dashboard.php">
                    <i class="bi bi-grid-3x3-gap"></i>
                    <span class="menu-text">Dashboard</span>
                </a>
            </li>
            
            <?php if (in_array($role_user, ['admin', 'front_office'])): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo (in_array($current_page, ['daftar_reservasi.php', 'form_input_booking.php', 'detail_reservasi.php'])) ? 'active' : ''; ?>" href="daftar_reservasi.php">
                        <i class="bi bi-calendar-check"></i>
                        <span class="menu-text">Reservasi</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page == 'daftar_tamu_checkout.php') ? 'active' : ''; ?>" href="daftar_tamu_checkout.php">
                        <i class="bi bi-person-check"></i>
                        <span class="menu-text">Tamu Checkout</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#">
                        <i class="bi bi-people"></i>
                        <span class="menu-text">Data Tamu</span>
                    </a>
                </li>
            <?php endif; ?>
            
            <?php if (in_array($role_user, ['admin', 'housekeeping'])): ?>
                <li class="nav-item">
                    <a class="nav-link" href="#">
                        <i class="bi bi-house-check"></i>
                        <span class="menu-text">Status Kamar</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#">
                        <i class="bi bi-wrench-adjustable"></i>
                        <span class="menu-text">Maintenance</span>
                    </a>
                </li>
            <?php endif; ?>
            
            <?php if ($role_user == 'admin'): ?>
                <li class="nav-item">
                    <a class="nav-link" href="#">
                        <i class="bi bi-journal-text"></i>
                        <span class="menu-text">Laporan</span>
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
                        <span class="menu-text">Pengaturan</span>
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

        <div class="sidebar-footer">
            <a class="nav-link nav-link-logout" href="logout.php">
                <i class="bi bi-box-arrow-right"></i>
                <span class="menu-text">Keluar</span>
            </a>
        </div>
        </div>
    </div>
</div>

<style>
    .sidebar-modern {
        background-color: var(--sidebar-bg);
        width: var(--sidebar-width);
        position: fixed;
        top: 0;
        left: 0;
        bottom: 0;
        z-index: 1040;
        transition: width 0.3s ease;
    }
    .sidebar-header .menu-text {
        transition: opacity 0.3s ease;
    }

    .sidebar-nav .nav-link {
        color: rgba(255, 255, 255, 0.7);
        border-radius: 8px;
        margin-bottom: 4px;
        padding: 0.8rem 1rem;
        display: flex;
        align-items: center;
        font-weight: 500;
        transition: background-color 0.2s, color 0.2s;
        white-space: nowrap;
    }

    .sidebar-nav .nav-link i {
        font-size: 1.2rem;
        margin-right: 0.85rem;
        width: 24px;
        text-align: center;
        transition: all 0.2s ease-in-out;
    }
    
    .sidebar-nav .nav-link .menu-text {
        transition: opacity 0.2s ease-in-out;
    }

    .sidebar-nav .nav-link:hover {
        background-color: rgba(255, 255, 255, 0.1);
        color: #ffffff;
    }

    .sidebar-nav .nav-link.active {
        background: var(--primary);
        color: #ffffff;
        box-shadow: 0 4px 12px rgba(67, 97, 238, 0.3);
    }
    
    .sidebar-modern .collapse .nav-link {
        padding-left: 3rem; 
        font-size: 0.9rem;
    }
    .sidebar-modern .collapse .nav-link.active-submenu{
        color: #fff;
        font-weight: 600;
    }

    .sidebar-footer {
        margin-top: auto;
        padding: 1rem;
        border-top: 1px solid rgba(255, 255, 255, 0.1);
        display: flex;
    }

    .sidebar-footer .nav-link-logout {
        flex-grow: 1;
        background-color: rgba(255,255,255,0.05);
        color: rgba(255, 255, 255, 0.7);
        display: flex;
        align-items: center;
        padding: 0.7rem 1rem;
        border-radius: 8px;
        white-space: nowrap;
    }
    .sidebar-footer .nav-link-logout:hover {
        background-color: rgba(239, 71, 111, 0.2);
        color: #fff;
    }
</style>