<?php
// Veritabanı ve fonksiyonları dahil et
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

// Site ayarlarını çek (fonksiyon zaten functions.php'de tanımlı)
$site_name = getSiteSetting('site_name', 'AI Commerce Pro');
$site_description = getSiteSetting('site_description', 'AI destekli e-ticaret platformu');

// Kullanıcı bilgilerini al
$user_name = '';
$user_avatar = '';
if (isLoggedIn()) {
    $user_name = $_SESSION['full_name'] ?? $_SESSION['username'];
    $user_avatar = $_SESSION['avatar'] ?? '';
}

// Sepet sayısını al
$cart_count = 0;
if (isLoggedIn()) {
    try {
        $stmt = $pdo->prepare("SELECT SUM(quantity) as total FROM cart WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $result = $stmt->fetch();
        $cart_count = $result['total'] ?? 0;
    } catch (PDOException $e) {
        $cart_count = 0;
    }
}

// Aktif sayfa kontrolü
$current_page = basename($_SERVER['PHP_SELF'], '.php');
$current_path = $_SERVER['REQUEST_URI'];
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?><?php echo htmlspecialchars($site_name); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($site_description); ?>">
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <!-- CSS -->
    <link rel="stylesheet" href="<?php echo asset('css/style.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?php echo asset('images/favicon.ico'); ?>">
    
    <?php if (isset($extra_css)): ?>
        <?php foreach ($extra_css as $css): ?>
            <link rel="stylesheet" href="<?php echo $css; ?>">
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="container">
            <div class="header-content">
                <!-- Logo -->
                <div class="logo">
                    <a href="<?php echo route(''); ?>">
                        <div class="logo-icon">
                            <i class="fas fa-robot"></i>
                        </div>
                        <span class="logo-text"><?php echo htmlspecialchars($site_name); ?></span>
                    </a>
                </div>

                <!-- Navigation -->
                <nav class="nav-menu">
                    <ul class="nav-list">
                        <li class="nav-item">
                            <a href="<?php echo route(''); ?>" 
                               class="nav-link <?php echo $current_page === 'index' ? 'active' : ''; ?>">
                                <i class="fas fa-home"></i>
                                Anasayfa
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?php echo route('products/'); ?>" 
                               class="nav-link <?php echo strpos($current_path, '/products') !== false ? 'active' : ''; ?>">
                                <i class="fas fa-box"></i>
                                Ürünler
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?php echo route('ai-assistant/'); ?>" 
                               class="nav-link <?php echo strpos($current_path, '/ai-assistant') !== false ? 'active' : ''; ?>">
                                <i class="fas fa-robot"></i>
                                AI Asistan
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?php echo route('blog/'); ?>" 
                               class="nav-link <?php echo strpos($current_path, '/blog') !== false ? 'active' : ''; ?>">
                                <i class="fas fa-blog"></i>
                                Blog
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?php echo route('contact.php'); ?>" 
                               class="nav-link <?php echo $current_page === 'contact' ? 'active' : ''; ?>">
                                <i class="fas fa-envelope"></i>
                                İletişim
                            </a>
                        </li>
                    </ul>
                </nav>

                <!-- Header Actions -->
                <div class="header-actions">
                    <!-- Search -->
                    <div class="search-container">
                        <form action="<?php echo route('search.php'); ?>" method="GET" class="search-form">
                            <input type="text" 
                                   name="q" 
                                   placeholder="Ürün ara..." 
                                   class="search-input"
                                   value="<?php echo htmlspecialchars($_GET['q'] ?? ''); ?>">
                            <button type="submit" class="search-btn">
                                <i class="fas fa-search"></i>
                            </button>
                        </form>
                    </div>

                    <!-- Cart -->
                    <div class="cart-container">
                        <a href="<?php echo route('cart/'); ?>" class="cart-link">
                            <i class="fas fa-shopping-cart"></i>
                            <?php if ($cart_count > 0): ?>
                                <span class="cart-badge"><?php echo $cart_count; ?></span>
                            <?php endif; ?>
                        </a>
                    </div>

                    <!-- User Menu -->
                    <div class="user-menu">
                        <?php if (isLoggedIn()): ?>
                            <div class="user-dropdown">
                                <button class="user-btn" onclick="toggleUserMenu()">
                                    <?php if ($user_avatar): ?>
                                        <img src="<?php echo htmlspecialchars($user_avatar); ?>" 
                                             alt="Avatar" class="user-avatar">
                                    <?php else: ?>
                                        <div class="user-avatar-placeholder">
                                            <i class="fas fa-user"></i>
                                        </div>
                                    <?php endif; ?>
                                    <span class="user-name"><?php echo htmlspecialchars($user_name); ?></span>
                                    <i class="fas fa-chevron-down"></i>
                                </button>
                                
                                <div class="user-dropdown-menu" id="userDropdownMenu">
                                    <a href="<?php echo route('profile/'); ?>" class="dropdown-item">
                                        <i class="fas fa-user"></i>
                                        Profilim
                                    </a>
                                    <a href="<?php echo route('orders/'); ?>" class="dropdown-item">
                                        <i class="fas fa-box"></i>
                                        Siparişlerim
                                    </a>
                                    <?php if (isAdmin()): ?>
                                        <div class="dropdown-divider"></div>
                                        <a href="<?php echo route('admin/'); ?>" class="dropdown-item">
                                            <i class="fas fa-cog"></i>
                                            Admin Panel
                                        </a>
                                    <?php endif; ?>
                                    <div class="dropdown-divider"></div>
                                    <a href="<?php echo route('auth/logout.php'); ?>" class="dropdown-item text-danger">
                                        <i class="fas fa-sign-out-alt"></i>
                                        Çıkış Yap
                                    </a>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="auth-buttons">
                                <a href="<?php echo route('auth/login.php'); ?>" class="btn btn-outline">
                                    <i class="fas fa-sign-in-alt"></i>
                                    Giriş
                                </a>
                                <a href="<?php echo route('auth/register.php'); ?>" class="btn btn-primary">
                                    <i class="fas fa-user-plus"></i>
                                    Kayıt Ol
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Mobile Menu Toggle -->
                    <button class="mobile-menu-toggle" onclick="toggleMobileMenu()">
                        <span></span>
                        <span></span>
                        <span></span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile Menu -->
        <div class="mobile-menu" id="mobileMenu">
            <div class="mobile-menu-content">
                <nav class="mobile-nav">
                    <a href="<?php echo route(''); ?>" class="mobile-nav-link">
                        <i class="fas fa-home"></i>
                        Anasayfa
                    </a>
                    <a href="<?php echo route('products/'); ?>" class="mobile-nav-link">
                        <i class="fas fa-box"></i>
                        Ürünler
                    </a>
                    <a href="<?php echo route('ai-assistant/'); ?>" class="mobile-nav-link">
                        <i class="fas fa-robot"></i>
                        AI Asistan
                    </a>
                    <a href="<?php echo route('blog/'); ?>" class="mobile-nav-link">
                        <i class="fas fa-blog"></i>
                        Blog
                    </a>
                    <a href="<?php echo route('contact.php'); ?>" class="mobile-nav-link">
                        <i class="fas fa-envelope"></i>
                        İletişim
                    </a>
                </nav>

                <?php if (!isLoggedIn()): ?>
                    <div class="mobile-auth">
                        <a href="<?php echo route('auth/login.php'); ?>" class="btn btn-outline btn-block">
                            <i class="fas fa-sign-in-alt"></i>
                            Giriş Yap
                        </a>
                        <a href="<?php echo route('auth/register.php'); ?>" class="btn btn-primary btn-block">
                            <i class="fas fa-user-plus"></i>
                            Kayıt Ol
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- Flash Messages -->
    <?php 
    $success_message = getFlashMessage('success');
    $error_message = getFlashMessage('error');
    ?>
    
    <?php if ($success_message): ?>
        <div class="alert alert-success alert-dismissible">
            <div class="container">
                <i class="fas fa-check-circle"></i>
                <?php echo htmlspecialchars($success_message); ?>
                <button type="button" class="alert-close" onclick="this.parentElement.parentElement.remove()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    <?php endif; ?>
    
    <?php if ($error_message): ?>
        <div class="alert alert-error alert-dismissible">
            <div class="container">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($error_message); ?>
                <button type="button" class="alert-close" onclick="this.parentElement.parentElement.remove()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    <?php endif; ?>

    <!-- Main Content -->
    <main class="main-content">