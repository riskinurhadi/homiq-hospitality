<?php
// sidebar.php (v2.0 - Modern, Fixed-Width)
$current_page = basename($_SERVER['PHP_SELF']);
$is_manajemen_page = strpos($current_page, 'manajemen_') === 0;
?>
<nav class="sidebar" id="sidebarMenu">
    <div class="sidebar-header">
        <a href="dashboard.php" class="sidebar-brand">
            <i class="bi bi-buildings-fill"></i>
            <span>HomiQ</span>
        </a>
    </div>

    <div class="sidebar-body">
        <ul class="sidebar-nav">
            <li class="sidebar-item-header">Menu Utama</li>
            
            <li class="sidebar-item">
                <a class="sidebar-link <?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>" href="dashboard.php">
                    <i class="bi bi-grid"></i>
                    <span>Dashboard</span>
                </a>
            </li>

            <?php if (in_array($role_user, ['admin', 'front_office'])): ?>
            <li class="sidebar-item">
                <a class="sidebar-link <?php echo (in_array($current_page, ['daftar_reservasi.php', 'form_input_booking.php', 'detail_reservasi.php'])) ? 'active' : ''; ?>" href="daftar_reservasi.php">
                    <i class="bi bi-calendar-check"></i>
                    <span>Reservasi</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a class="sidebar-link <?php echo ($current_page == 'daftar_tamu_checkout.php') ? 'active' : ''; ?>" href="daftar_tamu_checkout.php">
                    <i class="bi bi-person-check"></i>
                    <span>Tamu Checkout</span>
                </a>
            </li>
            <?php endif; ?>

            <?php if ($role_user == 'admin'): ?>
            <li class="sidebar-item-header">Administrasi</li>
            <li class="sidebar-item">
                <a href="#submenu-manajemen" data-bs-toggle="collapse" class="sidebar-link <?php echo $is_manajemen_page ? '' : 'collapsed'; ?>" aria-expanded="<?php echo $is_manajemen_page ? 'true' : 'false'; ?>">
                    <i class="bi bi-gear"></i>
                    <span>Manajemen</span>
                </a>
                <div class="sidebar-submenu collapse <?php echo $is_manajemen_page ? 'show' : ''; ?>" id="submenu-manajemen">
                    <ul class="sidebar-nav">
                        <li class="sidebar-item">
                             <a href="manajemen_properti.php" class="sidebar-link submenu-link <?php echo ($current_page == 'manajemen_properti.php') ? 'active' : ''; ?>">
                                <span>Properti</span>
                            </a>
                        </li>
                        <li class="sidebar-item">
                             <a href="manajemen_kamar.php" class="sidebar-link submenu-link <?php echo ($current_page == 'manajemen_kamar.php') ? 'active' : ''; ?>">
                                <span>Kamar</span>
                            </a>
                        </li>
                        <li class="sidebar-item">
                            <a href="manajemen_user.php" class="sidebar-link submenu-link <?php echo ($current_page == 'manajemen_user.php') ? 'active' : ''; ?>">
                                <span>User</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </li>
            <li class="sidebar-item">
                <a class="sidebar-link" href="#">
                    <i class="bi bi-journal-text"></i>
                    <span>Laporan</span>
                </a>
            </li>
            <?php endif; ?>
        </ul>
    </div>

    <div class="sidebar-footer">
        <a href="logout.php" class="sidebar-link logout-link">
            <i class="bi bi-box-arrow-left"></i>
            <span>Keluar</span>
        </a>
    </div>
</nav>

<style>
    :root {
        /* Re-define here as a fallback */
        --primary: #4361ee;
        --sidebar-bg: #0f172a;
        --sidebar-width: 260px;
        --sidebar-width-minimized: 90px; /* Added from dashboard */
        --text-main: #334155;
        --text-muted: #64748b;
    }

    .sidebar {
        width: var(--sidebar-width);
        position: fixed;
        top: 0;
        left: 0;
        bottom: 0;
        background-color: var(--sidebar-bg);
        color: #e2e8f0;
        display: flex;
        flex-direction: column;
        z-index: 1030;
        border-right: 1px solid rgba(255, 255, 255, 0.05);
        transition: width 0.3s ease, margin-left 0.35s ease-in-out;
    }

    .sidebar-header {
        padding: 1.25rem;
        border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        display: flex; /* Added for alignment */
        align-items: center; /* Added for alignment */
    }

    .sidebar-brand {
        display: flex;
        align-items: center;
        text-decoration: none;
        color: #fff;
        font-size: 1.5rem;
        font-weight: 700;
        overflow: hidden; /* Prevent text wrap */
        white-space: nowrap; /* Prevent text wrap */
    }
    .sidebar-brand i {
        font-size: 1.5rem;
        margin-right: 0.75rem;
        color: var(--primary);
        flex-shrink: 0; /* Prevent icon from shrinking */
    }

    .sidebar-body {
        flex-grow: 1;
        overflow-y: auto;
        padding: 1rem 0;
    }
    .sidebar-body::-webkit-scrollbar {
        width: 6px;
    }
    .sidebar-body::-webkit-scrollbar-thumb {
        background-color: rgba(255,255,255,0.1);
        border-radius: 3px;
    }

    .sidebar-nav {
        list-style: none;
        padding-left: 0;
    }

    .sidebar-item-header {
        padding: 0.75rem 1.5rem;
        font-size: 0.75rem;
        font-weight: 600;
        color: var(--text-muted);
        text-transform: uppercase;
        letter-spacing: 0.5px;
        white-space: nowrap;
    }

    .sidebar-item {
        padding: 0 1rem;
    }
    
    .sidebar-link {
        display: flex;
        align-items: center;
        padding: 0.8rem 1rem;
        border-radius: 8px;
        color: #cbd5e1;
        text-decoration: none;
        transition: background-color 0.2s ease, color 0.2s ease;
        white-space: nowrap;
        font-weight: 500;
    }
    .sidebar-link:hover {
        background-color: rgba(255, 255, 255, 0.05);
        color: #fff;
    }
    .sidebar-link.active {
        background-color: var(--primary);
        color: #fff;
        font-weight: 600;
        box-shadow: 0 4px 15px -3px rgba(67, 97, 238, 0.4);
    }
    .sidebar-link i {
        margin-right: 1rem;
        font-size: 1.1rem;
        width: 20px;
        flex-shrink: 0; /* Prevent icon from shrinking */
        transition: margin-right 0.3s ease;
    }

    /* Submenu */
    .sidebar-link[data-bs-toggle="collapse"]::after {
        content: '\F282'; /* Bootstrap Icons chevron-down */
        font-family: 'bootstrap-icons';
        margin-left: auto;
        transition: transform 0.2s ease-in-out, opacity 0.2s ease;
    }
    .sidebar-link[data-bs-toggle="collapse"].collapsed::after {
        transform: rotate(-90deg);
    }
    .sidebar-submenu {
        padding-left: 1rem;
    }
    .sidebar-submenu .sidebar-link {
        padding: 0.6rem 1rem;
        font-size: 0.9rem;
    }
    .sidebar-submenu .sidebar-link.active {
        background: transparent;
        box-shadow: none;
        color: #fff;
        font-weight: 500;
    }
    .sidebar-submenu .sidebar-link.active span::before {
        content: '';
        display: inline-block;
        width: 6px;
        height: 6px;
        background-color: var(--primary);
        border-radius: 50%;
        margin-right: 0.75rem;
        box-shadow: 0 0 10px var(--primary);
    }
    
    .sidebar-footer {
        padding: 1rem;
        border-top: 1px solid rgba(255, 255, 255, 0.05);
        display: flex; /* Added for alignment */
        transition: all 0.3s ease;
    }
    .sidebar-footer .logout-link {
        justify-content: center;
        background-color: rgba(239, 71, 111, 0.1);
        color: #f78da7;
        width: 100%;
    }
    .sidebar-footer .logout-link:hover {
        background-color: rgba(239, 71, 111, 0.2);
        color: #fff;
    }

    /* --- Styles moved from dashboard.php --- */

    /* Minimized State Base */
    body.sidebar-minimized .sidebar {
        width: var(--sidebar-width-minimized);
        overflow: visible; /* Allow tooltips to show */
    }

    body.sidebar-minimized .sidebar-link span,
    body.sidebar-minimized .sidebar-item-header,
    body.sidebar-minimized .sidebar-brand span,
    body.sidebar-minimized .sidebar-link[data-bs-toggle="collapse"]::after {
        opacity: 0;
        visibility: hidden;
        transition: opacity 0.1s ease;
        width: 0;
        overflow: hidden;
        white-space: nowrap;
    }
    
    body.sidebar-minimized .sidebar-header {
        justify-content: center;
    }
    
    body.sidebar-minimized .sidebar-brand i {
         margin-right: 0;
    }
    
    body.sidebar-minimized .sidebar-link {
        justify-content: center;
    }
    
    body.sidebar-minimized .sidebar-link i {
        margin-right: 0;
    }
    
    body.sidebar-minimized .collapse {
        display: none !important;
    }

    /* Floating Tooltip on Hover for minimized */
    body.sidebar-minimized .sidebar-item {
        position: relative;
    }

    body.sidebar-minimized .sidebar-item .sidebar-link span {
        pointer-events: none; /* Prevent the hidden span from interfering */
    }

    body.sidebar-minimized .sidebar-item .sidebar-link:hover span {
        position: absolute;
        left: 100%;
        top: 50%;
        transform: translateY(-50%);
        margin-left: 1rem; /* Space from the sidebar edge */
        background-color: #1c273e;
        padding: 0.75rem 1.25rem;
        border-radius: 8px;
        white-space: nowrap;
        box-shadow: 0 8px 16px rgba(0,0,0,0.3);
        border: 1px solid rgba(255, 255, 255, 0.1);
        opacity: 1;
        visibility: visible;
        transition: opacity 0.2s ease, transform 0.2s ease;
        z-index: 1050;
        pointer-events: auto; /* Make the visible tooltip interactable if needed */
    }
    
    body.sidebar-minimized .sidebar-footer {
        flex-direction: column;
        gap: 0.5rem;
        align-items: center;
    }
    
    body.sidebar-minimized .sidebar-footer span {
        display: none;
    }

    /* Responsive: Mobile view */
    @media (max-width: 991.98px) {
        .sidebar {
            margin-left: calc(var(--sidebar-width) * -1);
            z-index: 1045;
        }
        body.sidebar-mobile-show .sidebar {
            margin-left: 0;
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
        
        /* On mobile, minimized state has no effect */
        body.sidebar-minimized .sidebar {
            width: var(--sidebar-width);
        }
        body.sidebar-minimized .sidebar-link span,
        body.sidebar-minimized .sidebar-item-header,
        body.sidebar-minimized .sidebar-brand span,
        body.sidebar-minimized .sidebar-link[data-bs-toggle="collapse"]::after {
             opacity: 1;
             visibility: visible;
        }
         body.sidebar-minimized .sidebar-header {
            justify-content: flex-start;
        }
         body.sidebar-minimized .sidebar-brand i {
            margin-right: 0.75rem;
        }
        body.sidebar-minimized .sidebar-link {
            justify-content: flex-start;
        }
        body.sidebar-minimized .sidebar-link i {
            margin-right: 1rem;
        }
    }
</style>
