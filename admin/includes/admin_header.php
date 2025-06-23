<?php
// Admin yetkisi kontrolü
if (!isAdmin()) {
    header('Location: /ai-ecommerce/admin/');
    exit();
}

// Site ayarlarını çek
function getAdminSiteSetting($key, $default = '') {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT setting_value FROM site_settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $result = $stmt->fetch();
        return $result ? $result['setting_value'] : $default;
    } catch (PDOException $e) {
        return $default;
    }
}

$site_name = getAdminSiteSetting('site_name', 'AI Commerce Pro');
$current_page = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>Admin Panel - <?php echo $site_name; ?></title>
    <meta name="robots" content="noindex, nofollow">
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <!-- CSS -->
    <link rel="stylesheet" href="/ai-ecommerce/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Admin Custom CSS -->
    <style>
        :root {
            --admin-sidebar-width: 280px;
            --admin-header-height: 64px;
            --admin-bg: #f8fafc;
            --admin-sidebar-bg: #1e293b;
            --admin-sidebar-hover: #334155;
        }
        
        .admin-layout {
            display: flex;
            min-height: 100vh;
            background-color: var(--admin-bg);
        }
        
        .admin-sidebar {
            width: var(--admin-sidebar-width);
            background-color: var(--admin-sidebar-bg);
            color: white;
            position: fixed;
            left: 0;
            top: 0;
            height: 100vh;
            overflow-y: auto;
            z-index: 1000;
            transition: all 0.3s ease;
        }
        
        .admin-sidebar.collapsed {
            width: 70px;
        }
        
        .admin-main {
            flex: 1;
            margin-left: var(--admin-sidebar-width);
            transition: all 0.3s ease;
        }
        
        .admin-main.expanded {
            margin-left: 70px;
        }
        
        .admin-header {
            height: var(--admin-header-height);
            background: white;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: between;
            padding: 0 2rem;
            position: sticky;
            top: 0;
            z-index: 999;
        }
        
        .admin-content {
            padding: 2rem;
        }
        
        .sidebar-brand {
            padding: 1.5rem;
            border-bottom: 1px solid #334155;
            text-align: center;
        }
        
        .sidebar-nav {
            padding: 1rem 0;
        }
        
        .nav-item {
            margin: 0.25rem 0;
        }
        
        .nav-link {
            display: flex;
            align-items: center;
            padding: 0.75rem 1.5rem;
            color: #cbd5e1;
            text-decoration: none;
            transition: all 0.2s ease;
            border: none;
            background: none;
            width: 100%;
            text-align: left;
        }
        
        .nav-link:hover,
        .nav-link.active {
            background-color: var(--admin-sidebar-hover);
            color: white;
        }
        
        .nav-link.active {
            background-color: var(--primary);
            position: relative;
        }
        
        .nav-link.active::after {
            content: '';
            position: absolute;
            right: 0;
            top: 0;
            bottom: 0;
            width: 4px;
            background-color: white;
        }
        
        .nav-link i {
            width: 20px;
            margin-right: 0.75rem;
            text-align: center;
        }
        
        .collapsed .nav-link span {
            display: none;
        }
        
        .collapsed .nav-link {
            justify-content: center;
            padding: 0.75rem;
        }
        
        .collapsed .nav-link i {
            margin-right: 0;
        }
        
        .admin-breadcrumb {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #64748b;
            font-size: 0.875rem;
            margin-bottom: 1rem;
        }
        
        .admin-breadcrumb a {
            color: var(--primary);
            text-decoration: none;
        }
        
        .admin-breadcrumb a:hover {
            text-decoration: underline;
        }
        
        /* Mobile responsive */
        @media (max-width: 768px) {
            .admin-sidebar {
                transform: translateX(-100%);
            }
            
            .admin-sidebar.mobile-open {
                transform: translateX(0);
            }
            
            .admin-main {
                margin-left: 0;
            }
            
            .mobile-overlay {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0, 0, 0, 0.5);
                z-index: 999;
                display: none;
            }
            
            .mobile-overlay.show {
                display: block;
            }
        }
        
        /* Dropdown menu */
        .dropdown-menu {
            position: absolute;
            top: 100%;
            right: 0;
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            min-width: 200px;
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.2s ease;
        }
        
        .dropdown-menu.show {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }
        
        .dropdown-item {
            display: flex;
            align-items: center;
            padding: 0.75rem 1rem;
            color: #374151;
            text-decoration: none;
            font-size: 0.875rem;
            border-bottom: 1px solid #f3f4f6;
            transition: background-color 0.2s ease;
        }
        
        .dropdown-item:hover {
            background-color: #f9fafb;
        }
        
        .dropdown-item:last-child {
            border-bottom: none;
        }
        
        .dropdown-item i {
            margin-right: 0.5rem;
            width: 16px;
        }
        
        /* Notification badge */
        .notification-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background: #ef4444;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="admin-layout">
        <!-- Sidebar -->
        <aside class="admin-sidebar" id="adminSidebar">
            <!-- Brand -->
            <div class="sidebar-brand">
                <div class="flex items-center justify-center">
                    <div class="w-10 h-10 bg-primary rounded-lg flex items-center justify-center mr-3">
                        <i class="fas fa-robot text-white"></i>
                    </div>
                    <div class="sidebar-brand-text">
                        <h2 class="text-lg font-bold"><?php echo $site_name; ?></h2>
                        <p class="text-xs text-slate-400">Admin Panel</p>
                    </div>
                </div>
            </div>

            <!-- Navigation -->
            <nav class="sidebar-nav">
                <div class="nav-item">
                    <a href="/ai-ecommerce/admin/dashboard.php" 
                       class="nav-link <?php echo $current_page === 'dashboard' ? 'active' : ''; ?>">
                        <i class="fas fa-chart-pie"></i>
                        <span>Dashboard</span>
                    </a>
                </div>

                <!-- Ürün Yönetimi -->
                <div class="nav-item">
                    <button class="nav-link" onclick="toggleSubmenu('products')">
                        <i class="fas fa-box"></i>
                        <span>Ürünler</span>
                        <i class="fas fa-chevron-down ml-auto"></i>
                    </button>
                    <div class="submenu" id="products-submenu">
                        <a href="/ai-ecommerce/admin/products/" class="nav-link ml-6">
                            <i class="fas fa-list"></i>
                            <span>Tüm Ürünler</span>
                        </a>
                        <a href="/ai-ecommerce/admin/products/add.php" class="nav-link ml-6">
                            <i class="fas fa-plus"></i>
                            <span>Ürün Ekle</span>
                        </a>
                        <a href="/ai-ecommerce/admin/categories/" class="nav-link ml-6">
                            <i class="fas fa-tags"></i>
                            <span>Kategoriler</span>
                        </a>
                    </div>
                </div>

                <!-- Sipariş Yönetimi -->
                <div class="nav-item">
                    <a href="/ai-ecommerce/admin/orders/" 
                       class="nav-link <?php echo $current_page === 'orders' ? 'active' : ''; ?>">
                        <i class="fas fa-shopping-cart"></i>
                        <span>Siparişler</span>
                        <span class="notification-badge">3</span>
                    </a>
                </div>

                <!-- Kullanıcı Yönetimi -->
                <div class="nav-item">
                    <a href="/ai-ecommerce/admin/users/" 
                       class="nav-link <?php echo $current_page === 'users' ? 'active' : ''; ?>">
                        <i class="fas fa-users"></i>
                        <span>Kullanıcılar</span>
                    </a>
                </div>

                <!-- AI Yönetimi -->
                <div class="nav-item">
                    <button class="nav-link" onclick="toggleSubmenu('ai')">
                        <i class="fas fa-robot"></i>
                        <span>AI Sistemi</span>
                        <i class="fas fa-chevron-down ml-auto"></i>
                    </button>
                    <div class="submenu" id="ai-submenu">
                        <a href="/ai-ecommerce/admin/ai/conversations.php" class="nav-link ml-6">
                            <i class="fas fa-comments"></i>
                            <span>Konuşmalar</span>
                        </a>
                        <a href="/ai-ecommerce/admin/ai/settings.php" class="nav-link ml-6">
                            <i class="fas fa-cog"></i>
                            <span>AI Ayarları</span>
                        </a>
                    </div>
                </div>

                <!-- Blog Yönetimi -->
                <div class="nav-item">
                    <button class="nav-link" onclick="toggleSubmenu('blog')">
                        <i class="fas fa-blog"></i>
                        <span>Blog</span>
                        <i class="fas fa-chevron-down ml-auto"></i>
                    </button>
                    <div class="submenu" id="blog-submenu">
                        <a href="/ai-ecommerce/admin/blog/" class="nav-link ml-6">
                            <i class="fas fa-list"></i>
                            <span>Tüm Yazılar</span>
                        </a>
                        <a href="/ai-ecommerce/admin/blog/add.php" class="nav-link ml-6">
                            <i class="fas fa-plus"></i>
                            <span>Yazı Ekle</span>
                        </a>
                    </div>
                </div>

                <!-- Raporlar -->
                <div class="nav-item">
                    <a href="/ai-ecommerce/admin/reports/" 
                       class="nav-link <?php echo $current_page === 'reports' ? 'active' : ''; ?>">
                        <i class="fas fa-chart-bar"></i>
                        <span>Raporlar</span>
                    </a>
                </div>

                <!-- Ayarlar -->
                <div class="nav-item">
                    <a href="/ai-ecommerce/admin/settings/" 
                       class="nav-link <?php echo $current_page === 'settings' ? 'active' : ''; ?>">
                        <i class="fas fa-cog"></i>
                        <span>Site Ayarları</span>
                    </a>
                </div>

                <hr class="my-4 border-slate-600">

                <!-- Sistem -->
                <div class="nav-item">
                    <a href="/ai-ecommerce/admin/system/" 
                       class="nav-link <?php echo $current_page === 'system' ? 'active' : ''; ?>">
                        <i class="fas fa-server"></i>
                        <span>Sistem Bilgisi</span>
                    </a>
                </div>

                <!-- Site Görüntüle -->
                <div class="nav-item">
                    <a href="/ai-ecommerce/" target="_blank" class="nav-link">
                        <i class="fas fa-external-link-alt"></i>
                        <span>Siteyi Görüntüle</span>
                    </a>
                </div>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="admin-main" id="adminMain">
            <!-- Header -->
            <header class="admin-header">
                <div class="flex items-center justify-between w-full">
                    <!-- Left side -->
                    <div class="flex items-center">
                        <button onclick="toggleSidebar()" class="mr-4 p-2 rounded-lg hover:bg-gray-100">
                            <i class="fas fa-bars text-gray-600"></i>
                        </button>
                        
                        <!-- Mobile menu button -->
                        <button onclick="toggleMobileSidebar()" class="md:hidden mr-4 p-2 rounded-lg hover:bg-gray-100">
                            <i class="fas fa-bars text-gray-600"></i>
                        </button>

                        <!-- Breadcrumb -->
                        <nav class="admin-breadcrumb hidden md:flex">
                            <a href="/ai-ecommerce/admin/dashboard.php">
                                <i class="fas fa-home"></i>
                            </a>
                            <i class="fas fa-chevron-right text-xs"></i>
                            <span><?php echo $page_title ?? 'Admin Panel'; ?></span>
                        </nav>
                    </div>

                    <!-- Right side -->
                    <div class="flex items-center gap-4">
                        <!-- Search -->
                        <div class="relative hidden md:block">
                            <input type="text" 
                                   placeholder="Ara..." 
                                   class="form-input w-64 pl-10"
                                   id="adminSearch">
                            <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                        </div>

                        <!-- Notifications -->
                        <div class="relative">
                            <button onclick="toggleNotifications()" 
                                    class="relative p-2 rounded-lg hover:bg-gray-100">
                                <i class="fas fa-bell text-gray-600 text-lg"></i>
                                <span class="notification-badge">5</span>
                            </button>
                            
                            <!-- Notifications dropdown -->
                            <div class="dropdown-menu" id="notificationsMenu">
                                <div class="p-4 border-b">
                                    <h3 class="font-semibold">Bildirimler</h3>
                                </div>
                                <a href="#" class="dropdown-item">
                                    <i class="fas fa-shopping-cart text-blue-600"></i>
                                    <div>
                                        <p class="font-medium">Yeni sipariş #1234</p>
                                        <p class="text-xs text-gray-500">2 dakika önce</p>
                                    </div>
                                </a>
                                <a href="#" class="dropdown-item">
                                    <i class="fas fa-user text-green-600"></i>
                                    <div>
                                        <p class="font-medium">Yeni kullanıcı kaydı</p>
                                        <p class="text-xs text-gray-500">15 dakika önce</p>
                                    </div>
                                </a>
                                <div class="p-3 text-center border-t">
                                    <a href="#" class="text-primary text-sm">Tümünü görüntüle</a>
                                </div>
                            </div>
                        </div>

                        <!-- Admin Profile -->
                        <div class="relative">
                            <button onclick="toggleAdminMenu()" 
                                    class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-100">
                                <div class="w-8 h-8 bg-primary rounded-full flex items-center justify-center">
                                    <span class="text-white text-sm font-medium">
                                        <?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?>
                                    </span>
                                </div>
                                <span class="hidden md:block font-medium">
                                    <?php echo htmlspecialchars($_SESSION['full_name'] ?: $_SESSION['username']); ?>
                                </span>
                                <i class="fas fa-chevron-down text-xs"></i>
                            </button>
                            
                            <!-- Admin dropdown -->
                            <div class="dropdown-menu" id="adminMenu">
                                <a href="/ai-ecommerce/admin/profile.php" class="dropdown-item">
                                    <i class="fas fa-user"></i>
                                    Profilim
                                </a>
                                <a href="/ai-ecommerce/admin/settings/" class="dropdown-item">
                                    <i class="fas fa-cog"></i>
                                    Ayarlar
                                </a>
                                <a href="/ai-ecommerce/" target="_blank" class="dropdown-item">
                                    <i class="fas fa-eye"></i>
                                    Siteyi Görüntüle
                                </a>
                                <div class="border-t my-2"></div>
                                <a href="/ai-ecommerce/auth/logout.php" class="dropdown-item text-red-600">
                                    <i class="fas fa-sign-out-alt"></i>
                                    Çıkış Yap
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Content -->
            <div class="admin-content">
                <!-- Flash messages -->
                <?php 
                $success_message = getFlashMessage('success');
                $error_message = getFlashMessage('error');
                ?>
                
                <?php if ($success_message): ?>
                    <div class="alert alert-success mb-6">
                        <i class="fas fa-check-circle mr-2"></i>
                        <?php echo htmlspecialchars($success_message); ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($error_message): ?>
                    <div class="alert alert-error mb-6">
                        <i class="fas fa-exclamation-circle mr-2"></i>
                        <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>

    <!-- Mobile overlay -->
    <div class="mobile-overlay" id="mobileOverlay" onclick="toggleMobileSidebar()"></div>

    <script>
        // Sidebar toggle
        function toggleSidebar() {
            const sidebar = document.getElementById('adminSidebar');
            const main = document.getElementById('adminMain');
            
            sidebar.classList.toggle('collapsed');
            main.classList.toggle('expanded');
            
            // Save state
            localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
        }

        // Mobile sidebar toggle
        function toggleMobileSidebar() {
            const sidebar = document.getElementById('adminSidebar');
            const overlay = document.getElementById('mobileOverlay');
            
            sidebar.classList.toggle('mobile-open');
            overlay.classList.toggle('show');
        }

        // Submenu toggle
        function toggleSubmenu(menuId) {
            const submenu = document.getElementById(menuId + '-submenu');
            submenu.classList.toggle('show');
        }

        // Notifications toggle
        function toggleNotifications() {
            const menu = document.getElementById('notificationsMenu');
            closeAllDropdowns();
            menu.classList.toggle('show');
        }

        // Admin menu toggle
        function toggleAdminMenu() {
            const menu = document.getElementById('adminMenu');
            closeAllDropdowns();
            menu.classList.toggle('show');
        }

        // Close all dropdowns
        function closeAllDropdowns() {
            document.querySelectorAll('.dropdown-menu').forEach(menu => {
                menu.classList.remove('show');
            });
        }

        // Close dropdowns when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.relative')) {
                closeAllDropdowns();
            }
        });

        // Restore sidebar state
        document.addEventListener('DOMContentLoaded', function() {
            const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
            if (isCollapsed) {
                document.getElementById('adminSidebar').classList.add('collapsed');
                document.getElementById('adminMain').classList.add('expanded');
            }
        });

        // Admin search functionality
        document.getElementById('adminSearch')?.addEventListener('input', function(e) {
            const query = e.target.value.toLowerCase();
            // Implement search functionality here
            console.log('Searching for:', query);
        });
    </script>

    <style>
        .submenu {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease;
        }
        
        .submenu.show {
            max-height: 200px;
        }
        
        .sidebar-brand-text {
            transition: opacity 0.3s ease;
        }
        
        .collapsed .sidebar-brand-text {
            opacity: 0;
        }
    </style>