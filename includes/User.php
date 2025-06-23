<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

class User {
    private $conn;
    private $table = 'users';
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // Kullanıcı kaydı
    public function register($data) {
        try {
            // E-posta ve kullanıcı adı kontrolü
            if ($this->emailExists($data['email'])) {
                return ['success' => false, 'message' => 'Bu e-posta adresi zaten kullanılıyor!'];
            }
            
            if ($this->usernameExists($data['username'])) {
                return ['success' => false, 'message' => 'Bu kullanıcı adı zaten alınmış!'];
            }
            
            // Şifre kontrolü
            if (!isStrongPassword($data['password'])) {
                return ['success' => false, 'message' => 'Şifre en az 8 karakter olmalı ve büyük harf, küçük harf, rakam içermelidir!'];
            }
            
            // E-posta formatı kontrolü
            if (!isValidEmail($data['email'])) {
                return ['success' => false, 'message' => 'Geçerli bir e-posta adresi giriniz!'];
            }
            
            $query = "INSERT INTO " . $this->table . " 
                     (username, email, password, full_name, phone) 
                     VALUES (?, ?, ?, ?, ?)";
            
            $stmt = $this->conn->prepare($query);
            $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
            
            $result = $stmt->execute([
                sanitize($data['username']),
                sanitize($data['email']),
                $hashedPassword,
                sanitize($data['full_name']),
                sanitize($data['phone'] ?? '')
            ]);
            
            if ($result) {
                return ['success' => true, 'message' => 'Kayıt başarılı! Giriş yapabilirsiniz.'];
            } else {
                return ['success' => false, 'message' => 'Kayıt işlemi başarısız!'];
            }
            
        } catch (PDOException $e) {
            logError("User registration error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Sistem hatası! Lütfen tekrar deneyiniz.'];
        }
    }
    
    // Kullanıcı girişi
    public function login($username, $password) {
        try {
            $query = "SELECT id, username, email, password, full_name, user_type, is_active 
                     FROM " . $this->table . " 
                     WHERE (username = ? OR email = ?) AND is_active = 1";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([sanitize($username), sanitize($username)]);
            
            if ($stmt->rowCount() === 1) {
                $user = $stmt->fetch();
                
                if (password_verify($password, $user['password'])) {
                    // Session bilgilerini ayarla
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['full_name'] = $user['full_name'];
                    $_SESSION['user_type'] = $user['user_type'];
                    $_SESSION['login_time'] = time();
                    
                    // Son giriş tarihini güncelle
                    $this->updateLastLogin($user['id']);
                    
                    return ['success' => true, 'message' => 'Giriş başarılı!', 'user' => $user];
                } else {
                    return ['success' => false, 'message' => 'Şifre hatalı!'];
                }
            } else {
                return ['success' => false, 'message' => 'Kullanıcı bulunamadı!'];
            }
            
        } catch (PDOException $e) {
            logError("User login error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Sistem hatası! Lütfen tekrar deneyiniz.'];
        }
    }
    
    // Kullanıcı çıkışı
    public function logout() {
        session_destroy();
        return ['success' => true, 'message' => 'Çıkış yapıldı!'];
    }
    
    // E-posta kontrolü
    private function emailExists($email) {
        $query = "SELECT id FROM " . $this->table . " WHERE email = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([sanitize($email)]);
        return $stmt->rowCount() > 0;
    }
    
    // Kullanıcı adı kontrolü
    private function usernameExists($username) {
        $query = "SELECT id FROM " . $this->table . " WHERE username = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([sanitize($username)]);
        return $stmt->rowCount() > 0;
    }
    
    // Son giriş tarihini güncelle
    private function updateLastLogin($userId) {
        $query = "UPDATE " . $this->table . " SET updated_at = CURRENT_TIMESTAMP WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$userId]);
    }
    
    // Kullanıcı bilgilerini getir
    public function getUserById($id) {
        $query = "SELECT id, username, email, full_name, phone, address, user_type, created_at 
                 FROM " . $this->table . " WHERE id = ? AND is_active = 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    // Profil güncelleme
    public function updateProfile($userId, $data) {
        try {
            $query = "UPDATE " . $this->table . " 
                     SET full_name = ?, phone = ?, address = ?, updated_at = CURRENT_TIMESTAMP 
                     WHERE id = ?";
            
            $stmt = $this->conn->prepare($query);
            $result = $stmt->execute([
                sanitize($data['full_name']),
                sanitize($data['phone']),
                sanitize($data['address']),
                $userId
            ]);
            
            if ($result) {
                return ['success' => true, 'message' => 'Profil başarıyla güncellendi!'];
            } else {
                return ['success' => false, 'message' => 'Profil güncellemesi başarısız!'];
            }
            
        } catch (PDOException $e) {
            logError("Profile update error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Sistem hatası!'];
        }
    }
    
    // Şifre değiştirme
    public function changePassword($userId, $currentPassword, $newPassword) {
        try {
            // Mevcut şifreyi kontrol et
            $query = "SELECT password FROM " . $this->table . " WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            
            if (!password_verify($currentPassword, $user['password'])) {
                return ['success' => false, 'message' => 'Mevcut şifre hatalı!'];
            }
            
            if (!isStrongPassword($newPassword)) {
                return ['success' => false, 'message' => 'Yeni şifre güvenlik kriterlerini karşılamıyor!'];
            }
            
            // Yeni şifreyi güncelle
            $updateQuery = "UPDATE " . $this->table . " 
                           SET password = ?, updated_at = CURRENT_TIMESTAMP 
                           WHERE id = ?";
            
            $updateStmt = $this->conn->prepare($updateQuery);
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            
            if ($updateStmt->execute([$hashedPassword, $userId])) {
                return ['success' => true, 'message' => 'Şifre başarıyla değiştirildi!'];
            } else {
                return ['success' => false, 'message' => 'Şifre değiştirme başarısız!'];
            }
            
        } catch (PDOException $e) {
            logError("Password change error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Sistem hatası!'];
        }
    }
}
?>