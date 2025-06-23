<?php
// Hata raporlamayı aç
error_reporting(E_ALL);
ini_set('display_errors', 1);

// CORS ayarları
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

// OPTIONS request için
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit();
}

try {
    // Dosya yollarını kontrol et
    require_once '../../config/database.php';
    require_once '../../includes/functions.php';
    
    // Session kontrolü
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Debug için
    error_log("Chat.php çağrıldı - User ID: " . ($_SESSION['user_id'] ?? 'yok'));
    
    // Giriş kontrolü
    if (!isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['error' => 'Giriş yapmanız gerekiyor']);
        exit();
    }
    
    // POST verisi kontrolü
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    error_log("Gelen veri: " . $input);
    
    if (!$data || !isset($data['message'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Geçersiz mesaj formatı']);
        exit();
    }
    
    $user_message = trim($data['message']);
    $user_id = $_SESSION['user_id'];
    $session_id = $data['session_id'] ?? uniqid();
    
    if (empty($user_message)) {
        http_response_code(400);
        echo json_encode(['error' => 'Boş mesaj gönderilemez']);
        exit();
    }
    
    // Basit AI cevap oluştur
    $ai_response = generateSimpleResponse($user_message);
    
    // Veritabanına kaydet
    $stmt = $pdo->prepare("
        INSERT INTO ai_conversations (user_id, session_id, user_message, ai_response, created_at) 
        VALUES (?, ?, ?, ?, NOW())
    ");
    
    $result = $stmt->execute([$user_id, $session_id, $user_message, $ai_response]);
    
    if (!$result) {
        error_log("Veritabanı kayıt hatası");
    }
    
    // Başarılı yanıt
    echo json_encode([
        'success' => true,
        'response' => $ai_response,
        'session_id' => $session_id,
        'timestamp' => date('Y-m-d H:i:s'),
        'debug' => 'Chat.php çalışıyor!'
    ]);
    
} catch (Exception $e) {
    error_log("Chat.php hatası: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'Sunucu hatası: ' . $e->getMessage(),
        'debug' => 'Exception yakalandı'
    ]);
}

function generateSimpleResponse($message) {
    $message_lower = mb_strtolower($message, 'UTF-8');
    
    if (strpos($message_lower, 'merhaba') !== false || strpos($message_lower, 'selam') !== false) {
        return "Merhaba! AI Commerce Pro'ya hoş geldiniz! Size nasıl yardımcı olabilirim? 🛍️";
    }
    
    if (strpos($message_lower, 'ürün') !== false) {
        return "Harika! Size ürün önerebilirim. Hangi kategoride arama yapıyorsunuz? (Teknoloji, Bilgisayar, Telefon, Oyun) 📱💻";
    }
    
    if (strpos($message_lower, 'fiyat') !== false) {
        return "Fiyat konusunda size yardımcı olabilirim! Hangi ürünün fiyatını merak ediyorsunuz? 💰";
    }
    
    return "Size nasıl yardımcı olabilirim? Ürün önerileri, fiyat bilgileri veya genel sorularınız için buradayım! 😊";
}
?>