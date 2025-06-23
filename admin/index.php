<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

// Zaten admin girişi yapmış ise dashboard'a yönlendir
if (isAdmin()) {
    header('Location: dashboard.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (!validateCSRFToken($_POST['csrf_token'])) {
        $error = 'Güvenlik hatası! Formu yeniden gönderiniz.';
    } elseif (empty($username) || empty($password)) {
        $error = 'Kullanıcı adı ve şifre gereklidir.';
    } else {
        try {
            $query = "
                SELECT id, username, email, password, full_name, user_type 
                FROM users 
                WHERE (username = ? OR email = ?) AND user_type = 'admin' AND is_active = 1
            ";
            
            $stmt = $pdo->prepare($query);
            $stmt->execute([$username, $username]);
            
            if ($stmt->rowCount() === 1) {
                $admin = $stmt->fetch();
                
                if (password_verify($password, $admin['password'])) {
                    // Admin session bilgilerini ayarla
                    $_SESSION['user_id'] = $admin['id'];
                    $_SESSION['username'] = $admin['username'];
                    $_SESSION['email'] = $admin['email'];
                    $_SESSION['full_name'] = $admin['full_name'];
                    $_SESSION['user_type'] = $admin['user_type'];
                    $_SESSION['admin_login_time'] = time();
                    
                    header('Location: dashboard.php');
                    exit();
                } else {
                    $error = 'Şifre hatalı!';
                }
            } else {
                $error = 'Admin kullanıcısı bulunamadı!';
            }
            
        } catch (PDOException $e) {
            logError("Admin login error: " . $e->getMessage());
            $error = 'Sistem hatası! Lütfen tekrar deneyiniz.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Girişi - AI Commerce Pro</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-900 via-purple-900 to-slate-900">
    <!-- Background Effects -->
    <div class="absolute inset-0 overflow-hidden">
        <div class="absolute -inset-10 opacity-50">
            <div class="absolute top-0 left-1/4 w-72 h-72 bg-purple-500 rounded-full mix-blend-multiply filter blur-xl animate-pulse"></div>
            <div class="absolute top-0 right-1/4 w-72 h-72 bg-blue-500 rounded-full mix-blend-multiply filter blur-xl animate-pulse delay-1000"></div>
            <div class="absolute bottom-0 left-1/3 w-72 h-72 bg-pink-500 rounded-full mix-blend-multiply filter blur-xl animate-pulse delay-2000"></div>
        </div>
    </div>

    <div class="relative flex items-center justify-center min-h-screen p-4">
        <div class="card w-full max-w-md">
            <!-- Header -->
            <div class="text-center p-8 pb-6">
                <div class="w-20 h-20 bg-gradient-to-r from-blue-500 to-purple-600 rounded-full flex items-center justify-center mx-auto mb-6 shadow-lg">
                    <i class="fas fa-shield-alt text-white text-2xl"></i>
                </div>
                <h1 class="text-2xl font-bold text-gray-900 mb-2">Admin Panel</h1>
                <p class="text-gray-600">AI Commerce Pro Yönetim Paneli</p>
            </div>

            <!-- Login Form -->
            <div class="p-8 pt-0">
                <?php if ($error): ?>
                    <div class="alert alert-error mb-6">
                        <i class="fas fa-exclamation-circle mr-2"></i>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="space-y-6">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    
                    <div class="form-group">
                        <label for="username" class="form-label flex items-center">
                            <i class="fas fa-user mr-2 text-gray-500"></i>
                            Admin Kullanıcı Adı
                        </label>
                        <input type="text" 
                               id="username" 
                               name="username" 
                               class="form-input" 
                               required 
                               autocomplete="username"
                               value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="password" class="form-label flex items-center">
                            <i class="fas fa-lock mr-2 text-gray-500"></i>
                            Şifre
                        </label>
                        <div class="relative">
                            <input type="password" 
                                   id="password" 
                                   name="password" 
                                   class="form-input pr-12" 
                                   required 
                                   autocomplete="current-password">
                            <button type="button" 
                                    class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600"
                                    onclick="togglePassword()">
                                <i class="fas fa-eye" id="passwordToggle"></i>
                            </button>
                        </div>
                    </div>

                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <input type="checkbox" 
                                   id="remember" 
                                   name="remember" 
                                   class="mr-2">
                            <label for="remember" class="text-sm text-gray-600">
                                Beni hatırla
                            </label>
                        </div>
                        <a href="#" class="text-sm text-primary hover:underline">
                            Şifremi unuttum
                        </a>
                    </div>

                    <button type="submit" class="btn btn-primary btn-full btn-lg">
                        <i class="fas fa-sign-in-alt mr-2"></i>
                        Admin Girişi
                    </button>
                </form>

                <!-- Demo Bilgi -->
                <div class="mt-8 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                    <h3 class="text-sm font-semibold text-blue-800 mb-2 flex items-center">
                        <i class="fas fa-info-circle mr-2"></i>
                        Demo Admin Hesabı
                    </h3>
                    <div class="text-xs text-blue-600">
                        <div><strong>Kullanıcı Adı:</strong> admin</div>
                        <div><strong>Şifre:</strong> admin123</div>
                    </div>
                </div>

                <!-- Site Linki -->
                <div class="text-center mt-6 pt-6 border-t border-gray-200">
                    <a href="../" class="text-gray-500 hover:text-primary text-sm flex items-center justify-center">
                        <i class="fas fa-arrow-left mr-2"></i>
                        Ana Siteye Dön
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Şifre göster/gizle
        function togglePassword() {
            const passwordField = document.getElementById('password');
            const toggleIcon = document.getElementById('passwordToggle');
            
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                toggleIcon.className = 'fas fa-eye-slash';
            } else {
                passwordField.type = 'password';
                toggleIcon.className = 'fas fa-eye';
            }
        }

        // Form validasyonu
        document.querySelector('form').addEventListener('submit', function(e) {
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value;
            
            if (!username || !password) {
                e.preventDefault();
                alert('Lütfen tüm alanları doldurun!');
                return false;
            }
            
            // Güvenlik uyarısı
            if (username === 'admin' && password === 'admin123') {
                if (!confirm('Demo hesabıyla giriş yapıyorsunuz. Gerçek verilerle çalışmayın. Devam etmek istiyor musunuz?')) {
                    e.preventDefault();
                    return false;
                }
            }
        });

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Alt + A ile admin girişi
            if (e.altKey && e.key === 'a') {
                document.getElementById('username').value = 'admin';
                document.getElementById('password').value = 'admin123';
                document.getElementById('password').focus();
            }
        });

        // Security monitoring
        let attemptCount = 0;
        document.querySelector('form').addEventListener('submit', function() {
            attemptCount++;
            if (attemptCount > 3) {
                console.warn('Multiple login attempts detected');
            }
        });
    </script>

    <style>
        /* Custom animations for admin login */
        .card {
            animation: slideInUp 0.6s ease-out;
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.95);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Floating elements animation */
        .animate-pulse {
            animation: pulse 4s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }

        .delay-1000 {
            animation-delay: 1s;
        }

        .delay-2000 {
            animation-delay: 2s;
        }

        /* Focus states */
        .form-input:focus {
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
            border-color: #3b82f6;
        }

        /* Button hover effects */
        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 10px 20px rgba(59, 130, 246, 0.3);
        }

        /* Background gradient animation */
        body {
            background-size: 400% 400%;
            animation: gradientShift 15s ease infinite;
        }

        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
    </style>
</body>
</html>