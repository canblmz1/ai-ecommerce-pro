<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/Product.php';

header('Content-Type: application/json');

// Sadece giriş yapmış kullanıcılar
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Giriş yapmanız gerekiyor.']);
    exit();
}

$user_id = $_SESSION['user_id'];
$product = new Product($pdo);

// GET request - sepet sayısını getir
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['action']) && $_GET['action'] === 'count') {
        try {
            $query = "SELECT SUM(quantity) as total_count FROM cart WHERE user_id = ?";
            $stmt = $pdo->prepare($query);
            $stmt->execute([$user_id]);
            $result = $stmt->fetch();
            
            echo json_encode([
                'success' => true,
                'count' => (int)($result['total_count'] ?: 0)
            ]);
        } catch (PDOException $e) {
            logError("Cart count error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Sepet sayısı alınamadı.']);
        }
        exit();
    }
    
    // Sepet içeriğini getir
    try {
        $query = "
            SELECT c.*, p.name, p.price, p.sale_price, p.main_image, p.stock_quantity,
                   COALESCE(p.sale_price, p.price) as current_price,
                   (c.quantity * COALESCE(p.sale_price, p.price)) as item_total
            FROM cart c
            JOIN products p ON c.product_id = p.id
            WHERE c.user_id = ? AND p.is_active = 1
            ORDER BY c.created_at DESC
        ";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([$user_id]);
        $cart_items = $stmt->fetchAll();
        
        $total_amount = 0;
        $total_quantity = 0;
        
        foreach ($cart_items as &$item) {
            $total_amount += $item['item_total'];
            $total_quantity += $item['quantity'];
        }
        
        echo json_encode([
            'success' => true,
            'items' => $cart_items,
            'total_amount' => $total_amount,
            'total_quantity' => $total_quantity,
            'formatted_total' => formatPrice($total_amount)
        ]);
        
    } catch (PDOException $e) {
        logError("Cart fetch error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Sepet yüklenemedi.']);
    }
    exit();
}

// POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        echo json_encode(['success' => false, 'message' => 'Geçersiz veri.']);
        exit();
    }
    
    $action = $input['action'] ?? '';
    
    switch ($action) {
        case 'add':
            $product_id = (int)($input['product_id'] ?? 0);
            $quantity = (int)($input['quantity'] ?? 1);
            
            if ($product_id <= 0 || $quantity <= 0) {
                echo json_encode(['success' => false, 'message' => 'Geçersiz ürün veya miktar.']);
                exit();
            }
            
            // Ürün kontrolü
            $product_result = $product->getProductById($product_id);
            if (!$product_result['success']) {
                echo json_encode(['success' => false, 'message' => 'Ürün bulunamadı.']);
                exit();
            }
            
            $prod = $product_result['product'];
            
            // Stok kontrolü
            if ($prod['stock_quantity'] < $quantity) {
                echo json_encode(['success' => false, 'message' => 'Yetersiz stok!']);
                exit();
            }
            
            try {
                // Sepette zaten var mı kontrol et
                $check_query = "SELECT * FROM cart WHERE user_id = ? AND product_id = ?";
                $check_stmt = $pdo->prepare($check_query);
                $check_stmt->execute([$user_id, $product_id]);
                $existing = $check_stmt->fetch();
                
                if ($existing) {
                    // Miktarı güncelle
                    $new_quantity = $existing['quantity'] + $quantity;
                    
                    if ($new_quantity > $prod['stock_quantity']) {
                        echo json_encode(['success' => false, 'message' => 'Toplam miktar stok miktarını aşıyor!']);
                        exit();
                    }
                    
                    $update_query = "UPDATE cart SET quantity = ? WHERE id = ?";
                    $update_stmt = $pdo->prepare($update_query);
                    $update_stmt->execute([$new_quantity, $existing['id']]);
                    
                    echo json_encode(['success' => true, 'message' => 'Sepet güncellendi.']);
                } else {
                    // Yeni ürün ekle
                    $insert_query = "INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)";
                    $insert_stmt = $pdo->prepare($insert_query);
                    $insert_stmt->execute([$user_id, $product_id, $quantity]);
                    
                    echo json_encode(['success' => true, 'message' => 'Ürün sepete eklendi.']);
                }
                
            } catch (PDOException $e) {
                logError("Cart add error: " . $e->getMessage());
                echo json_encode(['success' => false, 'message' => 'Ürün sepete eklenemedi.']);
            }
            break;
            
        case 'remove':
            $item_id = (int)($input['item_id'] ?? 0);
            
            if ($item_id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Geçersiz öğe ID.']);
                exit();
            }
            
            try {
                $delete_query = "DELETE FROM cart WHERE id = ? AND user_id = ?";
                $delete_stmt = $pdo->prepare($delete_query);
                
                if ($delete_stmt->execute([$item_id, $user_id])) {
                    echo json_encode(['success' => true, 'message' => 'Ürün sepetten çıkarıldı.']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Ürün çıkarılamadı.']);
                }
                
            } catch (PDOException $e) {
                logError("Cart remove error: " . $e->getMessage());
                echo json_encode(['success' => false, 'message' => 'Ürün çıkarılamadı.']);
            }
            break;
            
        case 'update':
            $item_id = (int)($input['item_id'] ?? 0);
            $quantity = (int)($input['quantity'] ?? 1);
            
            if ($item_id <= 0 || $quantity <= 0) {
                echo json_encode(['success' => false, 'message' => 'Geçersiz veri.']);
                exit();
            }
            
            try {
                // Önce cart item'ı kontrol et
                $check_query = "
                    SELECT c.*, p.stock_quantity 
                    FROM cart c 
                    JOIN products p ON c.product_id = p.id 
                    WHERE c.id = ? AND c.user_id = ?
                ";
                $check_stmt = $pdo->prepare($check_query);
                $check_stmt->execute([$item_id, $user_id]);
                $cart_item = $check_stmt->fetch();
                
                if (!$cart_item) {
                    echo json_encode(['success' => false, 'message' => 'Sepet öğesi bulunamadı.']);
                    exit();
                }
                
                if ($quantity > $cart_item['stock_quantity']) {
                    echo json_encode(['success' => false, 'message' => 'Yetersiz stok!']);
                    exit();
                }
                
                $update_query = "UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?";
                $update_stmt = $pdo->prepare($update_query);
                
                if ($update_stmt->execute([$quantity, $item_id, $user_id])) {
                    echo json_encode(['success' => true, 'message' => 'Sepet güncellendi.']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Sepet güncellenemedi.']);
                }
                
            } catch (PDOException $e) {
                logError("Cart update error: " . $e->getMessage());
                echo json_encode(['success' => false, 'message' => 'Sepet güncellenemedi.']);
            }
            break;
            
        case 'clear':
            try {
                $clear_query = "DELETE FROM cart WHERE user_id = ?";
                $clear_stmt = $pdo->prepare($clear_query);
                
                if ($clear_stmt->execute([$user_id])) {
                    echo json_encode(['success' => true, 'message' => 'Sepet temizlendi.']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Sepet temizlenemedi.']);
                }
                
            } catch (PDOException $e) {
                logError("Cart clear error: " . $e->getMessage());
                echo json_encode(['success' => false, 'message' => 'Sepet temizlenemedi.']);
            }
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Geçersiz işlem.']);
            break;
    }
    exit();
}

// Diğer HTTP methodları
http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
?>