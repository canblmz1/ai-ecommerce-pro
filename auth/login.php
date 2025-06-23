<?php
require_once '../config/database.php';
require_once '../includes/User.php';
require_once '../includes/functions.php';

// Zaten giriş yapmış ise ana sayfaya yönlendir
if (isLoggedIn()) {
    header('Location: ../index.php');
    exit();
}

$user = new User($pdo);
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'])) {
        $error = 'Güvenlik hatası! Formu yeniden gönderiniz.';
    } else {
        $result = $user->login($_POST['username'], $_POST['password']);
        if ($result['success']) {
            header('Location: ../index.php');
            exit();
        } else {
            $error = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giriş Yap - AI Commerce Pro</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="min-h-screen flex items-center justify-center bg-gradient-to-br from-blue-50 to-indigo-100">
    <div class="container container-sm">
        <div class="card fade-in">
            <div class="card-body p-8">
                <!-- Logo ve başlık -->
                <div class="text-center mb-8">
                    <div class="flex items-center justify-center gap-3 mb-4">
                        <div class="w-12 h-12 bg-primary rounded-full flex items-center justify-center">
                            <i class="fas fa-robot text-white text-xl"></i>
                        </div>
                        <h1 class="text-2xl font-bold text-primary">AI Commerce Pro</h1>
                    </div>
                    <h2 class="text-xl font-semibold text-primary mb-2">Hoş Geldiniz</h2>
                    <p class="text-secondary">Hesabınıza giriş yapın</p>
                </div>

                <!-- Hata mesajı -->
                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle mr-2"></i>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <!-- Giriş formu -->
                <form method="POST" class="space-y-6">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    
                    <div class="form-group">
                        <label for="username" class="form-label">
                            <i class="fas fa-user mr-2"></i>Kullanıcı Adı veya E-posta
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
                        <label for="password" class="form-label">
                            <i class="fas fa-lock mr-2"></i>Şifre
                        </label>
                        <input type="password" 
                               id="password" 
                               name="password" 
                               class="form-input" 
                               required 
                               autocomplete="current-password">
                    </div>

                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <input type="checkbox" 
                                   id="remember" 
                                   name="remember" 
                                   class="mr-2">
                            <label for="remember" class="text-sm text-secondary">
                                Beni hatırla
                            </label>
                        </div>
                        <a href="#" class="text-sm text-primary hover:underline">
                            Şifremi unuttum
                        </a>
                    </div>

                    <button type="submit" class="btn btn-primary btn-full btn-lg">
                        <i class="fas fa-sign-in-alt mr-2"></i>
                        Giriş Yap
                    </button>
                </form>

                <!-- Demo hesaplar -->
                <div class="mt-6 p-4 bg-blue-50 rounded-lg border border-blue-200">
                    <h3 class="text-sm font-semibold text-blue-800 mb-2">Demo Hesaplar</h3>
                    <div class="text-xs text-blue-600 space-y-1">
                        <div><strong>Admin:</strong> admin / admin123</div>
                        <div><strong>Müşteri:</strong> demo@test.com / demo123</div>
                    </div>
                </div>

                <!-- Kayıt linki -->
                <div class="text-center mt-6 pt-6 border-t border-gray-200">
                    <p class="text-secondary">
                        Hesabınız yok mu? 
                        <a href="register.php" class="text-primary font-medium hover:underline">
                            Kayıt Olun
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Form odaklanma animasyonu
        document.querySelectorAll('.form-input').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.classList.add('focused');
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.classList.remove('focused');
            });
        });
    </script>
</body>
</html>