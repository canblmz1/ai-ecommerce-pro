<?php
require_once '../config/database.php';
require_once '../includes/User.php';
require_once '../includes/functions.php';

$user = new User($pdo);
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'])) {
        $error = 'Güvenlik hatası! Formu yeniden gönderiniz.';
    } else {
        $result = $user->register($_POST);
        if ($result['success']) {
            $success = $result['message'];
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
    <title>Kayıt Ol - AI Commerce Pro</title>
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
                    <h2 class="text-xl font-semibold text-primary mb-2">Hesap Oluştur</h2>
                    <p class="text-secondary">AI destekli e-ticaret dünyasına katılın</p>
                </div>

                <!-- Hata ve başarı mesajları -->
                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle mr-2"></i>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle mr-2"></i>
                        <?php echo htmlspecialchars($success); ?>
                        <div class="mt-4">
                            <a href="login.php" class="btn btn-primary">Giriş Yap</a>
                        </div>
                    </div>
                <?php else: ?>

                <!-- Kayıt formu -->
                <form method="POST" class="space-y-6">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="form-group">
                            <label for="username" class="form-label">
                                <i class="fas fa-user mr-2"></i>Kullanıcı Adı
                            </label>
                            <input type="text" 
                                   id="username" 
                                   name="username" 
                                   class="form-input" 
                                   required 
                                   minlength="3"
                                   maxlength="50"
                                   pattern="[a-zA-Z0-9_]+"
                                   title="Sadece harf, rakam ve alt çizgi kullanabilirsiniz"
                                   value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                        </div>

                        <div class="form-group">
                            <label for="full_name" class="form-label">
                                <i class="fas fa-id-card mr-2"></i>Ad Soyad
                            </label>
                            <input type="text" 
                                   id="full_name" 
                                   name="full_name" 
                                   class="form-input" 
                                   required 
                                   minlength="2"
                                   maxlength="100"
                                   value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="email" class="form-label">
                            <i class="fas fa-envelope mr-2"></i>E-posta Adresi
                        </label>
                        <input type="email" 
                               id="email" 
                               name="email" 
                               class="form-input" 
                               required 
                               maxlength="100"
                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="phone" class="form-label">
                            <i class="fas fa-phone mr-2"></i>Telefon (Opsiyonel)
                        </label>
                        <input type="tel" 
                               id="phone" 
                               name="phone" 
                               class="form-input" 
                               maxlength="20"
                               value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="form-group">
                            <label for="password" class="form-label">
                                <i class="fas fa-lock mr-2"></i>Şifre
                            </label>
                            <input type="password" 
                                   id="password" 
                                   name="password" 
                                   class="form-input" 
                                   required 
                                   minlength="8"
                                   maxlength="255">
                            <div class="text-xs text-secondary mt-1">
                                En az 8 karakter, büyük harf, küçük harf ve rakam içermelidir
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="confirm_password" class="form-label">
                                <i class="fas fa-lock mr-2"></i>Şifre Tekrar
                            </label>
                            <input type="password" 
                                   id="confirm_password" 
                                   name="confirm_password" 
                                   class="form-input" 
                                   required 
                                   minlength="8"
                                   maxlength="255">
                        </div>
                    </div>

                    <div class="flex items-center">
                        <input type="checkbox" 
                               id="terms" 
                               name="terms" 
                               class="mr-3" 
                               required>
                        <label for="terms" class="text-sm text-secondary">
                            <a href="#" class="text-primary hover:underline">Kullanım Şartları</a> ve 
                            <a href="#" class="text-primary hover:underline">Gizlilik Politikası</a>'nı kabul ediyorum
                        </label>
                    </div>

                    <button type="submit" class="btn btn-primary btn-full btn-lg">
                        <i class="fas fa-user-plus mr-2"></i>
                        Hesap Oluştur
                    </button>
                </form>

                <?php endif; ?>

                <!-- Giriş linki -->
                <div class="text-center mt-6 pt-6 border-t border-gray-200">
                    <p class="text-secondary">
                        Zaten hesabınız var mı? 
                        <a href="login.php" class="text-primary font-medium hover:underline">
                            Giriş Yapın
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Şifre eşleşme kontrolü
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirmPassword = this.value;
            
            if (password !== confirmPassword) {
                this.setCustomValidity('Şifreler eşleşmiyor!');
            } else {
                this.setCustomValidity('');
            }
        });

        // Form validasyonu
        document.querySelector('form').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Şifreler eşleşmiyor!');
                return false;
            }
            
            if (password.length < 8) {
                e.preventDefault();
                alert('Şifre en az 8 karakter olmalıdır!');
                return false;
            }
        });
    </script>
</body>
</html>