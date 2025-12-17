<?php
// sidebar.php
// Sidebar navigation untuk semua halaman

// Menentukan halaman aktif
$current_page = basename($_SERVER['PHP_SELF']);
$is_pengaturan_page = (strpos($current_page, 'manajemen_') === 0 || $current_page == 'pengaturan_properti.php');
?>
<div class="sidebar-modern shadow-sm" id="sidebarMenu">
    <div class="d-flex flex-column h-100">
        <div class="sidebar-header d-flex align-items-center justify-content-between py-3 mb-3 px-4">
            <a href="dashboard.php" class="text-decoration-none text-white fs-5 fw-bold d-flex align-items-center">
                <i class="bi bi-buildings-fill me-2"></i>
                <span class="menu-text">HomiQ</span>
            </a>
            <a href="#" class="sidebar-toggle-btn d-none d-lg-flex" id="sidebar-toggle">
                <i class="bi bi-chevron-left"></i>
            </a>
        </div>
        <ul class="nav flex-column sidebar-nav px-3">
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
                        <i class="bi bi-chevron-down ms-auto arrow-icon"></i>
                    </a>
                    <div class="collapse <?php echo $is_pengaturan_page ? 'show' : ''; ?>" id="submenu-pengaturan">
                        <ul class="nav flex-column">
                            <li class="nav-item">
                                <a class="nav-link submenu-link <?php echo ($current_page == 'manajemen_properti.php') ? 'active' : ''; ?>" href="manajemen_properti.php">
                                    <span class="menu-text">Properti</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link submenu-link <?php echo ($current_page == 'manajemen_kamar.php') ? 'active' : ''; ?>" href="manajemen_kamar.php">
                                    <span class="menu-text">Kamar</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link submenu-link <?php echo ($current_page == 'manajemen_user.php') ? 'active' : ''; ?>" href="manajemen_user.php">
                                    <span class="menu-text">User</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>
            <?php endif; ?>
        </ul>

        <div class="sidebar-footer mt-auto px-3 pb-3">
            <a class="nav-link nav-link-logout" href="logout.php">
                <i class="bi bi-box-arrow-right"></i>
                <span class="menu-text">Keluar</span>
            </a>
        </div>
    </div>
</div>

<style>
    .sidebar-modern {
        background-color: var(--sidebar-bg, #0f172a);
        width: var(--sidebar-width, 260px);
        position: fixed;
        top: 0;
        left: 0;
        bottom: 0;
        z-index: 1040;
        transition: width 0.3s ease-in-out;
        display: flex;
        flex-direction: column;
    }

    .sidebar-header {
        flex-shrink: 0;
    }
    .sidebar-header .menu-text {
        transition: opacity 0.2s ease-in-out;
    }

    .sidebar-nav {
        overflow-y: auto;
        flex-grow: 1;
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
        overflow: hidden;
    }

    .sidebar-nav .nav-link i {
        font-size: 1.2rem;
        margin-right: 0.85rem;
        width: 24px;
        text-align: center;
        transition: margin 0.3s ease-in-out;
    }
    
    .sidebar-nav .nav-link .menu-text,
    .sidebar-nav .nav-link .arrow-icon {
        transition: opacity 0.2s ease-in-out 0.05s;
    }

    .sidebar-nav .nav-link:hover {
        background-color: rgba(255, 255, 255, 0.1);
        color: #ffffff;
    }

    .sidebar-nav .nav-link.active {
        background: var(--primary, #4361ee);
        color: #ffffff;
        box-shadow: 0 4px 12px rgba(67, 97, 238, 0.3);
    }
    
    .sidebar-modern .collapse .nav-link.submenu-link {
        padding-left: 3.85rem; 
        font-size: 0.9rem;
        position: relative;
    }
    
    .sidebar-modern .collapse .nav-link.submenu-link::before {
        content: "";
        position: absolute;
        left: 1.7rem;
        top: 50%;
        transform: translateY(-50%);
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background-color: rgba(255,255,255,0.3);
        transition: all 0.2s ease;
    }

    .sidebar-modern .collapse .nav-link.submenu-link:hover::before {
        background-color: #fff;
    }

    .sidebar-modern .collapse .nav-link.submenu-link.active {
       color: #fff;
       font-weight: 600;
       background: transparent;
       box-shadow: none;
    }
    .sidebar-modern .collapse .nav-link.submenu-link.active::before {
       background-color: var(--primary, #4361ee);
       box-shadow: 0 0 8px var(--primary, #4361ee);
    }


    .sidebar-footer {
        flex-shrink: 0;
    }

    .sidebar-footer .nav-link-logout {
        background-color: rgba(255,255,255,0.05);
        color: rgba(255, 255, 255, 0.7);
        display: flex;
        align-items: center;
        padding: 0.8rem 1rem;
        border-radius: 8px;
        white-space: nowrap;
        overflow: hidden;
    }
    .sidebar-footer .nav-link-logout:hover {
        background-color: rgba(239, 71, 111, 0.2);
        color: #fff;
    }

    .sidebar-toggle-btn {
        background: rgba(255,255,255,0.1);
        color: #fff;
        border: none;
        border-radius: 8px;
        width: 38px;
        height: 38px;
        align-items-center;
        justify-content: center;
        font-size: 1.2rem;
        text-decoration: none;
        transition: all 0.3s ease;
        flex-shrink: 0;
    }
    .sidebar-toggle-btn:hover {
        background: var(--primary, #4361ee);
    }
    .sidebar-toggle-btn i {
        transition: transform 0.3s ease-in-out;
    }
    
    /* === Sidebar Minimized State === */
    body.sidebar-minimized .sidebar-modern {
        width: var(--sidebar-width-minimized, 90px);
    }

    body.sidebar-minimized .sidebar-header {
        justify-content: center !important;
        padding-left: 0;
        padding-right: 0;
    }
     body.sidebar-minimized .sidebar-header .sidebar-toggle-btn {
        display: none !important;
     }

    body.sidebar-minimized .menu-text,
    body.sidebar-minimized .arrow-icon {
        opacity: 0;
        width: 0;
        visibility: hidden;
        transition: opacity 0.1s ease-out;
    }
    
    body.sidebar-minimized .sidebar-nav {
        padding-left: 0.5rem;
        padding-right: 0.5rem;
    }

    body.sidebar-minimized .nav-link {
        justify-content: center;
        padding: 0.8rem;
    }
    
    body.sidebar-minimized .nav-link i {
        margin-right: 0;
    }

    body.sidebar-minimized .sidebar-footer {
        padding-left: 0.5rem;
        padding-right: 0.5rem;
    }
    
    body.sidebar-minimized .collapse {
        display: none !important;
    }
    
    body.sidebar-minimized .sidebar-toggle-btn i {
        transform: rotate(180deg);
    }

    /* Re-introduce toggle button at the bottom for minimized state */
    body.sidebar-minimized .sidebar-footer::before {
        content: '';
        display: block;
        height: 1px;
        width: 80%;
        background: rgba(255,255,255,0.1);
        margin: 0.5rem auto 1rem;
    }
    body.sidebar-minimized .sidebar-footer {
        display: flex;
        flex-direction: column;
        align-items: center;
    }
    body.sidebar-minimized .sidebar-footer .nav-link-logout {
         width: 42px;
         height: 42px;
         padding: 0;
         justify-content: center;
    }

    body.sidebar-minimized .sidebar-footer .sidebar-toggle-btn-bottom {
        display: flex;
        margin-top: 0.5rem;
    }
</style>


<script>
// This script is designed to be included once per page, preferably in dashboard.php or a global footer.
// If this sidebar.php is included on multiple pages, ensure this script is not duplicated.
document.addEventListener('DOMContentLoaded', function() {
    // --- Sidebar Toggle Logic ---
    const sidebarToggleBtn = document.getElementById('sidebar-toggle');
    const body = document.body;

    // A separate toggle button is needed for when the sidebar is minimized, placed in the footer
    let sidebarToggleBtnBottom = document.querySelector('.sidebar-toggle-btn-bottom');
    if (!sidebarToggleBtnBottom) {
        sidebarToggleBtnBottom = document.createElement('a');
        sidebarToggleBtnBottom.href = '#';
        sidebarToggleBtnBottom.className = 'sidebar-toggle-btn sidebar-toggle-btn-bottom';
        sidebarToggleBtnBottom.style.display = 'none'; // Initially hidden
        sidebarToggleBtnBottom.innerHTML = '<i class="bi bi-chevron-right"></i>';
        
        const footer = document.querySelector('.sidebar-footer');
        if (footer) {
            footer.appendChild(sidebarToggleBtnBottom);
        }
    }

    // Function to toggle sidebar state
    const toggleSidebar = (e) => {
        if (e) e.preventDefault();
        
        const isMinimized = body.classList.toggle('sidebar-minimized');
        localStorage.setItem('sidebarMinimized', isMinimized ? 'true' : 'false');
        
        updateSidebarState(isMinimized);
    };
    
    // Function to apply state on load and after toggle
    const updateSidebarState = (isMinimized) => {
        const toggleTop = document.getElementById('sidebar-toggle');
        const toggleBottom = document.querySelector('.sidebar-toggle-btn-bottom');

        if (isMinimized) {
            if (toggleTop) toggleTop.style.display = 'none';
            if (toggleBottom) {
                toggleBottom.style.display = 'flex';
                toggleBottom.innerHTML = '<i class="bi bi-chevron-right"></i>';
            }
        } else {
            if (toggleTop) toggleTop.style.display = 'flex';
            if (toggleBottom) toggleBottom.style.display = 'none';
        }
    };

    // --- Event Listeners ---
    if (sidebarToggleBtn) {
        sidebarToggleBtn.addEventListener('click', toggleSidebar);
    }
    if (sidebarToggleBtnBottom) {
        sidebarToggleBtnBottom.addEventListener('click', toggleSidebar);
    }

    // --- Initial State on Page Load ---
    const initialStateMinimized = localStorage.getItem('sidebarMinimized') === 'true';
    if (initialStateMinimized) {
        body.classList.add('sidebar-minimized');
    }
    updateSidebarState(initialStateMinimized);


    // --- Mobile Sidebar ---
    const mobileSidebarToggleBtn = document.getElementById('mobile-sidebar-toggle');
    if (mobileSidebarToggleBtn) {
        mobileSidebarToggleBtn.addEventListener('click', (e) => {
            e.preventDefault();
            body.classList.toggle('sidebar-mobile-show');
        });
    }

    // Close mobile sidebar when clicking on the overlay
    document.addEventListener('click', function(e) {
        if (body.classList.contains('sidebar-mobile-show') && e.target.tagName.toLowerCase() !== 'i' && !e.target.closest('#sidebarMenu') && !e.target.closest('#mobile-sidebar-toggle')) {
            body.classList.remove('sidebar-mobile-show');
        }
    });
});
</script>
