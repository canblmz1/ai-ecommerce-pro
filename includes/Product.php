<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

class Product {
    private $conn;
    private $table = 'products';
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // Tüm ürünleri getir (sayfalama ile)
    public function getProducts($filters = [], $page = 1, $limit = 12) {
        try {
            $offset = ($page - 1) * $limit;
            
            $where_conditions = ["p.is_active = 1"];
            $params = [];
            
            // Kategori filtresi
            if (!empty($filters['category_id'])) {
                $where_conditions[] = "p.category_id = ?";
                $params[] = $filters['category_id'];
            }
            
            // Fiyat aralığı filtresi
            if (!empty($filters['min_price'])) {
                $where_conditions[] = "COALESCE(p.sale_price, p.price) >= ?";
                $params[] = $filters['min_price'];
            }
            
            if (!empty($filters['max_price'])) {
                $where_conditions[] = "COALESCE(p.sale_price, p.price) <= ?";
                $params[] = $filters['max_price'];
            }
            
            // Arama filtresi
            if (!empty($filters['search'])) {
                $where_conditions[] = "(p.name LIKE ? OR p.description LIKE ? OR p.short_description LIKE ?)";
                $search_term = '%' . $filters['search'] . '%';
                $params[] = $search_term;
                $params[] = $search_term;
                $params[] = $search_term;
            }
            
            // Öne çıkanlar filtresi
            if (!empty($filters['featured'])) {
                $where_conditions[] = "p.is_featured = 1";
            }
            
            $where_clause = implode(' AND ', $where_conditions);
            
            // Sıralama
            $order_by = "p.created_at DESC";
            if (!empty($filters['sort'])) {
                switch ($filters['sort']) {
                    case 'price_asc':
                        $order_by = "COALESCE(p.sale_price, p.price) ASC";
                        break;
                    case 'price_desc':
                        $order_by = "COALESCE(p.sale_price, p.price) DESC";
                        break;
                    case 'name_asc':
                        $order_by = "p.name ASC";
                        break;
                    case 'name_desc':
                        $order_by = "p.name DESC";
                        break;
                    case 'newest':
                        $order_by = "p.created_at DESC";
                        break;
                    case 'oldest':
                        $order_by = "p.created_at ASC";
                        break;
                }
            }
            
            $query = "
                SELECT p.*, c.name as category_name, c.slug as category_slug
                FROM {$this->table} p
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE {$where_clause}
                ORDER BY {$order_by}
                LIMIT ? OFFSET ?
            ";
            
            $params[] = $limit;
            $params[] = $offset;
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute($params);
            
            $products = $stmt->fetchAll();
            
            // Toplam sayı için ayrı sorgu
            $count_query = "
                SELECT COUNT(*) as total
                FROM {$this->table} p
                WHERE {$where_clause}
            ";
            
            $count_params = array_slice($params, 0, -2); // Son iki parametreyi (limit, offset) çıkar
            $count_stmt = $this->conn->prepare($count_query);
            $count_stmt->execute($count_params);
            $total = $count_stmt->fetch()['total'];
            
            return [
                'success' => true,
                'products' => $products,
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'total_pages' => ceil($total / $limit)
            ];
            
        } catch (PDOException $e) {
            logError("Products fetch error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Ürünler yüklenirken hata oluştu.'];
        }
    }
    
    // Tekil ürün getir (slug ile)
    public function getProductBySlug($slug) {
        try {
            $query = "
                SELECT p.*, c.name as category_name, c.slug as category_slug
                FROM {$this->table} p
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE p.slug = ? AND p.is_active = 1
            ";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([sanitize($slug)]);
            
            $product = $stmt->fetch();
            
            if ($product) {
                // JSON alanları decode et
                $product['gallery'] = $product['gallery'] ? json_decode($product['gallery'], true) : [];
                $product['features'] = $product['features'] ? json_decode($product['features'], true) : [];
                
                return ['success' => true, 'product' => $product];
            } else {
                return ['success' => false, 'message' => 'Ürün bulunamadı.'];
            }
            
        } catch (PDOException $e) {
            logError("Product fetch error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Ürün yüklenirken hata oluştu.'];
        }
    }
    
    // Ürün ID ile getir
    public function getProductById($id) {
        try {
            $query = "
                SELECT p.*, c.name as category_name, c.slug as category_slug
                FROM {$this->table} p
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE p.id = ? AND p.is_active = 1
            ";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$id]);
            
            $product = $stmt->fetch();
            
            if ($product) {
                $product['gallery'] = $product['gallery'] ? json_decode($product['gallery'], true) : [];
                $product['features'] = $product['features'] ? json_decode($product['features'], true) : [];
                
                return ['success' => true, 'product' => $product];
            } else {
                return ['success' => false, 'message' => 'Ürün bulunamadı.'];
            }
            
        } catch (PDOException $e) {
            logError("Product fetch error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Ürün yüklenirken hata oluştu.'];
        }
    }
    
    // Öne çıkan ürünleri getir
    public function getFeaturedProducts($limit = 6) {
        try {
            $query = "
                SELECT p.*, c.name as category_name, c.slug as category_slug
                FROM {$this->table} p
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE p.is_featured = 1 AND p.is_active = 1
                ORDER BY p.created_at DESC
                LIMIT ?
            ";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$limit]);
            
            return ['success' => true, 'products' => $stmt->fetchAll()];
            
        } catch (PDOException $e) {
            logError("Featured products error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Öne çıkan ürünler yüklenirken hata oluştu.'];
        }
    }
    
    // İlgili ürünleri getir
    public function getRelatedProducts($productId, $categoryId, $limit = 4) {
        try {
            $query = "
                SELECT p.*, c.name as category_name, c.slug as category_slug
                FROM {$this->table} p
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE p.category_id = ? AND p.id != ? AND p.is_active = 1
                ORDER BY p.created_at DESC
                LIMIT ?
            ";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$categoryId, $productId, $limit]);
            
            return ['success' => true, 'products' => $stmt->fetchAll()];
            
        } catch (PDOException $e) {
            logError("Related products error: " . $e->getMessage());
            return ['success' => false, 'message' => 'İlgili ürünler yüklenirken hata oluştu.'];
        }
    }
    
    // Ürün arama
    public function searchProducts($query, $limit = 10) {
        try {
            $search_term = '%' . sanitize($query) . '%';
            
            $sql = "
                SELECT p.*, c.name as category_name,
                       CASE 
                           WHEN p.name LIKE ? THEN 3
                           WHEN p.short_description LIKE ? THEN 2
                           WHEN p.description LIKE ? THEN 1
                           ELSE 0
                       END as relevance
                FROM {$this->table} p
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE (p.name LIKE ? OR p.description LIKE ? OR p.short_description LIKE ?)
                  AND p.is_active = 1
                ORDER BY relevance DESC, p.created_at DESC
                LIMIT ?
            ";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                $search_term, $search_term, $search_term,
                $search_term, $search_term, $search_term,
                $limit
            ]);
            
            return ['success' => true, 'products' => $stmt->fetchAll()];
            
        } catch (PDOException $e) {
            logError("Product search error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Arama sırasında hata oluştu.'];
        }
    }
    
    // Ürün ekleme (Admin)
    public function createProduct($data) {
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
                (name, slug, description, short_description, price, sale_price, 
                 stock_quantity, category_id, main_image, gallery, features, 
                 is_featured, meta_title, meta_description)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ";
            
            $stmt = $this->conn->prepare($query);
            $result = $stmt->execute([
                sanitize($data['name']),
                $slug,
                sanitize($data['description']),
                sanitize($data['short_description']),
                $data['price'],
                $data['sale_price'] ?: null,
                $data['stock_quantity'] ?: 0,
                $data['category_id'] ?: null,
                $data['main_image'] ?: null,
                json_encode($data['gallery'] ?: []),
                json_encode($data['features'] ?: []),
                !empty($data['is_featured']) ? 1 : 0,
                sanitize($data['meta_title'] ?: $data['name']),
                sanitize($data['meta_description'] ?: $data['short_description'])
            ]);
            
            if ($result) {
                $productId = $this->conn->lastInsertId();
                return ['success' => true, 'message' => 'Ürün başarıyla eklendi.', 'product_id' => $productId];
            } else {
                return ['success' => false, 'message' => 'Ürün eklenirken hata oluştu.'];
            }
            
        } catch (PDOException $e) {
            logError("Product creation error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Ürün eklenirken hata oluştu.'];
        }
    }
    
    // Ürün güncelleme
    public function updateProduct($id, $data) {
        try {
            // Mevcut ürünü kontrol et
            $existing = $this->getProductById($id);
            if (!$existing['success']) {
                return ['success' => false, 'message' => 'Ürün bulunamadı.'];
            }
            
            // Slug güncelleme gerekli mi?
            $slug = $existing['product']['slug'];
            if ($data['name'] !== $existing['product']['name']) {
                $new_slug = createSlug($data['name']);
                if ($new_slug !== $slug && !$this->slugExists($new_slug, $id)) {
                    $slug = $new_slug;
                }
            }
            
            $query = "
                UPDATE {$this->table} SET
                    name = ?, slug = ?, description = ?, short_description = ?,
                    price = ?, sale_price = ?, stock_quantity = ?, category_id = ?,
                    main_image = ?, gallery = ?, features = ?, is_featured = ?,
                    meta_title = ?, meta_description = ?, updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ";
            
            $stmt = $this->conn->prepare($query);
            $result = $stmt->execute([
                sanitize($data['name']),
                $slug,
                sanitize($data['description']),
                sanitize($data['short_description']),
                $data['price'],
                $data['sale_price'] ?: null,
                $data['stock_quantity'] ?: 0,
                $data['category_id'] ?: null,
                $data['main_image'] ?: null,
                json_encode($data['gallery'] ?: []),
                json_encode($data['features'] ?: []),
                !empty($data['is_featured']) ? 1 : 0,
                sanitize($data['meta_title'] ?: $data['name']),
                sanitize($data['meta_description'] ?: $data['short_description']),
                $id
            ]);
            
            if ($result) {
                return ['success' => true, 'message' => 'Ürün başarıyla güncellendi.'];
            } else {
                return ['success' => false, 'message' => 'Ürün güncellenirken hata oluştu.'];
            }
            
        } catch (PDOException $e) {
            logError("Product update error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Ürün güncellenirken hata oluştu.'];
        }
    }
    
    // Ürün silme (soft delete)
    public function deleteProduct($id) {
        try {
            $query = "UPDATE {$this->table} SET is_active = 0 WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            
            if ($stmt->execute([$id])) {
                return ['success' => true, 'message' => 'Ürün başarıyla silindi.'];
            } else {
                return ['success' => false, 'message' => 'Ürün silinirken hata oluştu.'];
            }
            
        } catch (PDOException $e) {
            logError("Product deletion error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Ürün silinirken hata oluştu.'];
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
    
    // Stok güncelleme
    public function updateStock($productId, $quantity) {
        try {
            $query = "UPDATE {$this->table} SET stock_quantity = stock_quantity - ? WHERE id = ? AND stock_quantity >= ?";
            $stmt = $this->conn->prepare($query);
            
            if ($stmt->execute([$quantity, $productId, $quantity])) {
                return ['success' => true, 'message' => 'Stok güncellendi.'];
            } else {
                return ['success' => false, 'message' => 'Yetersiz stok!'];
            }
            
        } catch (PDOException $e) {
            logError("Stock update error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Stok güncellenirken hata oluştu.'];
        }
    }
}
?>