<?php
// Session ve güvenlik ayarları
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ============================================
// GÜVENLİK FONKSİYONLARI
// ============================================

// CSRF token oluştur
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// CSRF token doğrula
function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// XSS koruması
function sanitize($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// ============================================
// KULLANICI YETKİ FONKSİYONLARI
// ============================================

// Kullanıcı giriş kontrolü
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Admin kontrolü
function isAdmin() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin';
}

// Giriş zorunlu sayfalar için yönlendirme
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /ai-ecommerce/auth/login.php');
        exit();
    }
}

// Admin yetkisi zorunlu sayfalar için yönlendirme
function requireAdmin() {
    if (!isAdmin()) {
        header('Location: /ai-ecommerce/admin/');
        exit();
    }
}

// ============================================
// MESAJ YÖNETİMİ FONKSİYONLARI
// ============================================

// Flash mesaj ayarla
function setFlashMessage($type, $message) {
    if (!isset($_SESSION['flash_messages'])) {
        $_SESSION['flash_messages'] = [];
    }
    $_SESSION['flash_messages'][$type] = $message;
}

// Flash mesaj al ve temizle
function getFlashMessage($type) {
    if (isset($_SESSION['flash_messages'][$type])) {
        $message = $_SESSION['flash_messages'][$type];
        unset($_SESSION['flash_messages'][$type]);
        return $message;
    }
    return null;
}

// Başarı mesajı (eski versiyon ile uyumluluk için)
function setSuccessMessage($message) {
    setFlashMessage('success', $message);
}

// Hata mesajı (eski versiyon ile uyumluluk için)
function setErrorMessage($message) {
    setFlashMessage('error', $message);
}

// ============================================
// VERİTABANI VE SİTE AYARLARI
// ============================================

// Site ayarı getir
function getSiteSetting($key, $default = '') {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT setting_value FROM site_settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $result = $stmt->fetch();
        
        return $result ? $result['setting_value'] : $default;
    } catch (PDOException $e) {
        logError("getSiteSetting error: " . $e->getMessage());
        return $default;
    }
}

// Site ayarı güncelle
function updateSiteSetting($key, $value) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO site_settings (setting_key, setting_value) 
            VALUES (?, ?) 
            ON DUPLICATE KEY UPDATE 
            setting_value = VALUES(setting_value), 
            updated_at = CURRENT_TIMESTAMP
        ");
        
        return $stmt->execute([$key, $value]);
    } catch (PDOException $e) {
        logError("updateSiteSetting error: " . $e->getMessage());
        return false;
    }
}

// Tüm site ayarlarını getir
function getAllSiteSettings() {
    global $pdo;
    
    try {
        $stmt = $pdo->query("SELECT setting_key, setting_value FROM site_settings");
        $settings = [];
        
        while ($row = $stmt->fetch()) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        
        return $settings;
    } catch (PDOException $e) {
        logError("getAllSiteSettings error: " . $e->getMessage());
        return [];
    }
}

// ============================================
// FORMATLAMA FONKSİYONLARI
// ============================================

// Fiyat formatla
function formatPrice($price) {
    return number_format($price, 2, ',', '.') . ' ₺';
}

// Tarih formatla
function formatDate($date, $format = 'd.m.Y H:i') {
    return date($format, strtotime($date));
}

// URL slug oluştur
function createSlug($text) {
    // Türkçe karakterleri dönüştür
    $turkish = ['ş', 'Ş', 'ı', 'İ', 'ğ', 'Ğ', 'ü', 'Ü', 'ö', 'Ö', 'ç', 'Ç'];
    $english = ['s', 's', 'i', 'i', 'g', 'g', 'u', 'u', 'o', 'o', 'c', 'c'];
    $text = str_replace($turkish, $english, $text);
    
    // Küçük harfe çevir ve özel karakterleri temizle
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    $text = preg_replace('/-+/', '-', $text);
    return trim($text, '-');
}

// ============================================
// DOĞRULAMA FONKSİYONLARI
// ============================================

// E-posta doğrulama
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// Güçlü şifre kontrolü
function isStrongPassword($password) {
    return strlen($password) >= 8 && 
           preg_match('/[A-Z]/', $password) && 
           preg_match('/[a-z]/', $password) && 
           preg_match('/[0-9]/', $password);
}

// ============================================
// DOSYA YÖNETİMİ FONKSİYONLARI
// ============================================

// Gelişmiş dosya yükleme
function uploadFile($file, $upload_dir, $allowed_types = ['jpg', 'jpeg', 'png', 'gif']) {
    if (!isset($file['error']) || is_array($file['error'])) {
        return ['success' => false, 'message' => 'Geçersiz dosya parametresi'];
    }
    
    // Hata kontrolü
    switch ($file['error']) {
        case UPLOAD_ERR_OK:
            break;
        case UPLOAD_ERR_NO_FILE:
            return ['success' => false, 'message' => 'Dosya seçilmedi'];
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            return ['success' => false, 'message' => 'Dosya çok büyük'];
        default:
            return ['success' => false, 'message' => 'Bilinmeyen hata'];
    }
    
    // Dosya boyutu kontrolü (5MB)
    if ($file['size'] > 5 * 1024 * 1024) {
        return ['success' => false, 'message' => 'Dosya 5MB\'dan büyük olamaz'];
    }
    
    // Dosya tipi kontrolü
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime_type = $finfo->file($file['tmp_name']);
    
    $allowed_mimes = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif'
    ];
    
    $extension = array_search($mime_type, $allowed_mimes, true);
    
    if (!$extension || !in_array($extension, $allowed_types)) {
        return ['success' => false, 'message' => 'Geçersiz dosya tipi'];
    }
    
    // Dosya adı oluştur
    $filename = sprintf('%s.%s', uniqid(), $extension);
    $filepath = $upload_dir . '/' . $filename;
    
    // Upload klasörü yoksa oluştur
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    // Dosyayı taşı
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        return ['success' => false, 'message' => 'Dosya yüklenemedi'];
    }
    
    return ['success' => true, 'filename' => $filename, 'filepath' => $filepath];
}

// Basit resim upload (eski versiyon ile uyumluluk)
function uploadImage($file, $directory, $allowed_types = ['jpg', 'jpeg', 'png', 'webp']) {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }
    
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($file_extension, $allowed_types)) {
        return false;
    }
    
    $new_filename = uniqid() . '.' . $file_extension;
    $upload_path = $directory . '/' . $new_filename;
    
    // Upload klasörü yoksa oluştur
    if (!is_dir($directory)) {
        mkdir($directory, 0755, true);
    }
    
    if (move_uploaded_file($file['tmp_name'], $upload_path)) {
        return $new_filename;
    }
    
    return false;
}

// Resim yeniden boyutlandırma
function resizeImage($source, $destination, $max_width = 800, $max_height = 600, $quality = 85) {
    $info = getimagesize($source);
    if (!$info) {
        return false;
    }
    
    list($orig_width, $orig_height, $type) = $info;
    
    // Yeni boyutları hesapla
    $ratio = min($max_width / $orig_width, $max_height / $orig_height);
    $new_width = round($orig_width * $ratio);
    $new_height = round($orig_height * $ratio);
    
    // Kaynak resmi oluştur
    switch ($type) {
        case IMAGETYPE_JPEG:
            $source_image = imagecreatefromjpeg($source);
            break;
        case IMAGETYPE_PNG:
            $source_image = imagecreatefrompng($source);
            break;
        case IMAGETYPE_GIF:
            $source_image = imagecreatefromgif($source);
            break;
        default:
            return false;
    }
    
    // Yeni resim oluştur
    $new_image = imagecreatetruecolor($new_width, $new_height);
    
    // PNG ve GIF için şeffaflık koruması
    if ($type == IMAGETYPE_PNG || $type == IMAGETYPE_GIF) {
        imagealphablending($new_image, false);
        imagesavealpha($new_image, true);
        $transparent = imagecolorallocatealpha($new_image, 255, 255, 255, 127);
        imagefilledrectangle($new_image, 0, 0, $new_width, $new_height, $transparent);
    }
    
    // Resmi yeniden boyutlandır
    imagecopyresampled($new_image, $source_image, 0, 0, 0, 0, $new_width, $new_height, $orig_width, $orig_height);
    
    // Resmi kaydet
    switch ($type) {
        case IMAGETYPE_JPEG:
            $result = imagejpeg($new_image, $destination, $quality);
            break;
        case IMAGETYPE_PNG:
            $result = imagepng($new_image, $destination);
            break;
        case IMAGETYPE_GIF:
            $result = imagegif($new_image, $destination);
            break;
    }
    
    // Belleği temizle
    imagedestroy($source_image);
    imagedestroy($new_image);
    
    return $result;
}

// ============================================
// YARDIMCI FONKSİYONLAR
// ============================================

// Pagination fonksiyonu
function getPagination($current_page, $total_items, $items_per_page = 10) {
    $total_pages = ceil($total_items / $items_per_page);
    $offset = ($current_page - 1) * $items_per_page;
    
    return [
        'current_page' => $current_page,
        'total_pages' => $total_pages,
        'total_items' => $total_items,
        'items_per_page' => $items_per_page,
        'offset' => $offset,
        'has_prev' => $current_page > 1,
        'has_next' => $current_page < $total_pages,
        'prev_page' => max(1, $current_page - 1),
        'next_page' => min($total_pages, $current_page + 1)
    ];
}

// Error logging
function logError($message, $file = '', $line = '') {
    $log_file = dirname(__DIR__) . '/logs/error.log';
    $log_dir = dirname($log_file);
    
    // Log klasörü yoksa oluştur
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $log_message = "[{$timestamp}] {$message}";
    
    if ($file) $log_message .= " in $file";
    if ($line) $log_message .= " on line $line";
    
    $log_message .= PHP_EOL;
    
    file_put_contents($log_file, $log_message, FILE_APPEND | LOCK_EX);
}

// Debug için (geliştirme aşamasında)
function dd($data) {
    echo '<pre>';
    var_dump($data);
    echo '</pre>';
    die();
}

// Array helper
function array_get($array, $key, $default = null) {
    return isset($array[$key]) ? $array[$key] : $default;
}

// ============================================
// URL VE YÖNLENDİRME FONKSİYONLARI
// ============================================

// Base URL getir
function getBaseUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'];
    $path = '/ai-ecommerce';
    return $protocol . $host . $path;
}

// Asset URL getir
function asset($path) {
    return getBaseUrl() . '/assets/' . $path;
}

// Route URL getir
function route($path) {
    return getBaseUrl() . '/' . ltrim($path, '/');
}

// JSON response gönder
function jsonResponse($data, $status_code = 200) {
    http_response_code($status_code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit();
}

// ============================================
// VERİ DOĞRULAMA FONKSİYONLARI
// ============================================

// Türkiye telefon numarası kontrolü
function isValidTurkishPhone($phone) {
    $phone = preg_replace('/[^0-9]/', '', $phone);
    return preg_match('/^(?:\+90|90|0)?5[0-9]{9}$/', $phone);
}

// TC Kimlik numarası kontrolü (basit)
function isValidTCKN($tckn) {
    if (strlen($tckn) != 11 || !ctype_digit($tckn)) {
        return false;
    }
    
    if ($tckn[0] == '0') {
        return false;
    }
    
    $odd = $tckn[0] + $tckn[2] + $tckn[4] + $tckn[6] + $tckn[8];
    $even = $tckn[1] + $tckn[3] + $tckn[5] + $tckn[7];
    
    $tenth = (($odd * 7) - $even) % 10;
    $eleventh = ($odd + $even + $tenth) % 10;
    
    return ($tenth == $tckn[9] && $eleventh == $tckn[10]);
}

// IP adres getir
function getUserIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        return $_SERVER['REMOTE_ADDR'];
    }
}

// User agent getir
function getUserAgent() {
    return $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
}

// ============================================
// CACHE FONKSİYONLARI
// ============================================

// Basit dosya cache
function setCache($key, $data, $expiration = 3600) {
    $cache_dir = dirname(__DIR__) . '/cache';
    if (!is_dir($cache_dir)) {
        mkdir($cache_dir, 0755, true);
    }
    
    $cache_file = $cache_dir . '/' . md5($key) . '.cache';
    $cache_data = [
        'data' => $data,
        'expires' => time() + $expiration
    ];
    
    file_put_contents($cache_file, serialize($cache_data));
}

function getCache($key) {
    $cache_dir = dirname(__DIR__) . '/cache';
    $cache_file = $cache_dir . '/' . md5($key) . '.cache';
    
    if (!file_exists($cache_file)) {
        return null;
    }
    
    $cache_data = unserialize(file_get_contents($cache_file));
    
    if ($cache_data['expires'] < time()) {
        unlink($cache_file);
        return null;
    }
    
    return $cache_data['data'];
}

function clearCache($key = null) {
    $cache_dir = dirname(__DIR__) . '/cache';
    
    if ($key) {
        $cache_file = $cache_dir . '/' . md5($key) . '.cache';
        if (file_exists($cache_file)) {
            unlink($cache_file);
        }
    } else {
        $files = glob($cache_dir . '/*.cache');
        foreach ($files as $file) {
            unlink($file);
        }
    }
}

?>