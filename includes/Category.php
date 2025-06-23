<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

class Category {
    private $conn;
    private $table = 'categories';
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // Tüm kategorileri getir
    public function getCategories($active_only = true) {
        try {
            $where_clause = $active_only ? "WHERE is_active = 1" : "";
            
            $query = "
                SELECT c.*, 
                       COUNT(p.id) as product_count
                FROM {$this->table} c
                LEFT JOIN products p ON c.id = p.category_id AND p.is_active = 1
                {$where_clause}
                GROUP BY c.id
                ORDER BY c.sort_order ASC, c.name ASC
            ";
            
            $stmt = $this->conn->query($query);
            return ['success' => true, 'categories' => $stmt->fetchAll()];
            
        } catch (PDOException $e) {
            logError("Categories fetch error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Kategoriler yüklenirken hata oluştu.'];
        }
    }
    
    // Kategori detayını getir (slug ile)
    public function getCategoryBySlug($slug) {
        try {
            $query = "
                SELECT c.*, 
                       COUNT(p.id) as product_count
                FROM {$this->table} c
                LEFT JOIN products p ON c.id = p.category_id AND p.is_active = 1
                WHERE c.slug = ? AND c.is_active = 1
                GROUP BY c.id
            ";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([sanitize($slug)]);
            
            $category = $stmt->fetch();
            
            if ($category) {
                return ['success' => true, 'category' => $category];
            } else {
                return ['success' => false, 'message' => 'Kategori bulunamadı.'];
            }
            
        } catch (PDOException $e) {
            logError("Category fetch error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Kategori yüklenirken hata oluştu.'];
        }
    }
    
    // Kategori ID ile getir
    public function getCategoryById($id) {
        try {
            $query = "SELECT * FROM {$this->table} WHERE id = ? AND is_active = 1";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$id]);
            
            $category = $stmt->fetch();
            
            if ($category) {
                return ['success' => true, 'category' => $category];
            } else {
                return ['success' => false, 'message' => 'Kategori bulunamadı.'];
            }
            
        } catch (PDOException $e) {
            logError("Category fetch error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Kategori yüklenirken hata oluştu.'];
        }
    }
    
    // Kategori oluştur
    public function createCategory($data) {
        try {
            // Slug oluştur
            $slug = createSlug($data['name']);
            
            // Slug benzersizlik kontrolü
            $slug_counter = 1;
            $original_slug = $slug;
            while ($this->slugExists($slug)) {
                $slug = $original_slug . '-' . $slug_counter;
                $slug_counter++;
            }
            
            $query = "
                INSERT INTO {$this->table} 
                (name, slug, description, image, sort_order)
                VALUES (?, ?, ?, ?, ?)
            ";
            
            $stmt = $this->conn->prepare($query);
            $result = $stmt->execute([
                sanitize($data['name']),
                $slug,
                sanitize($data['description']),
                $data['image'] ?: null,
                $data['sort_order'] ?: 0
            ]);
            
            if ($result) {
                $categoryId = $this->conn->lastInsertId();
                return ['success' => true, 'message' => 'Kategori başarıyla eklendi.', 'category_id' => $categoryId];
            } else {
                return ['success' => false, 'message' => 'Kategori eklenirken hata oluştu.'];
            }
            
        } catch (PDOException $e) {
            logError("Category creation error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Kategori eklenirken hata oluştu.'];
        }
    }
    
    // Kategori güncelle
    public function updateCategory($id, $data) {
        try {
            // Mevcut kategoriyi kontrol et
            $existing = $this->getCategoryById($id);
            if (!$existing['success']) {
                return ['success' => false, 'message' => 'Kategori bulunamadı.'];
            }
            
            // Slug güncelleme gerekli mi?
            $slug = $existing['category']['slug'];
            if ($data['name'] !== $existing['category']['name']) {
                $new_slug = createSlug($data['name']);
                if ($new_slug !== $slug && !$this->slugExists($new_slug, $id)) {
                    $slug = $new_slug;
                }
            }
            
            $query = "
                UPDATE {$this->table} SET
                    name = ?, slug = ?, description = ?, image = ?, sort_order = ?
                WHERE id = ?
            ";
            
            $stmt = $this->conn->prepare($query);
            $result = $stmt->execute([
                sanitize($data['name']),
                $slug,
                sanitize($data['description']),
                $data['image'] ?: null,
                $data['sort_order'] ?: 0,
                $id
            ]);
            
            if ($result) {
                return ['success' => true, 'message' => 'Kategori başarıyla güncellendi.'];
            } else {
                return ['success' => false, 'message' => 'Kategori güncellenirken hata oluştu.'];
            }
            
        } catch (PDOException $e) {
            logError("Category update error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Kategori güncellenirken hata oluştu.'];
        }
    }
    
    // Kategori sil (soft delete)
    public function deleteCategory($id) {
        try {
            $query = "UPDATE {$this->table} SET is_active = 0 WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            
            if ($stmt->execute([$id])) {
                return ['success' => true, 'message' => 'Kategori başarıyla silindi.'];
            } else {
                return ['success' => false, 'message' => 'Kategori silinirken hata oluştu.'];
            }
            
        } catch (PDOException $e) {
            logError("Category deletion error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Kategori silinirken hata oluştu.'];
        }
    }
    
    // Slug varlık kontrolü
    private function slugExists($slug, $excludeId = null) {
        $query = "SELECT id FROM {$this->table} WHERE slug = ?";
        $params = [$slug];
        
        if ($excludeId) {
            $query .= " AND id != ?";
            $params[] = $excludeId;
        }
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        
        return $stmt->rowCount() > 0;
    }
    
    // En popüler kategorileri getir
    public function getPopularCategories($limit = 5) {
        try {
            $query = "
                SELECT c.*, COUNT(p.id) as product_count
                FROM {$this->table} c
                LEFT JOIN products p ON c.id = p.category_id AND p.is_active = 1
                WHERE c.is_active = 1
                GROUP BY c.id
                HAVING product_count > 0
                ORDER BY product_count DESC, c.name ASC
                LIMIT ?
            ";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$limit]);
            
            return ['success' => true, 'categories' => $stmt->fetchAll()];
            
        } catch (PDOException $e) {
            logError("Popular categories error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Popüler kategoriler yüklenirken hata oluştu.'];
        }
    }
}
?>