<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

requireLogin();

$page_title = 'Sepetim';
$page_description = 'Alışveriş sepetinizi görüntüleyin ve yönetin';

$user_id = $_SESSION['user_id'];

// Sepet verilerini çek
try {
    $query = "
        SELECT c.*, p.name, p.slug, p.price, p.sale_price, p.main_image, p.stock_quantity,
               COALESCE(p.sale_price, p.price) as current_price,
               (c.quantity * COALESCE(p.sale_price, p.price)) as item_total,
               cat.name as category_name
        FROM cart c
        JOIN products p ON c.product_id = p.id
        LEFT JOIN categories cat ON p.category_id = cat.id
        WHERE c.user_id = ? AND p.is_active = 1
        ORDER BY c.created_at DESC
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([$user_id]);
    $cart_items = $stmt->fetchAll();
    
    $total_amount = 0;
    $total_quantity = 0;
    $total_savings = 0;
    
    foreach ($cart_items as &$item) {
        $total_amount += $item['item_total'];
        $total_quantity += $item['quantity'];
        
        // İndirim hesaplama
        if ($item['sale_price'] && $item['sale_price'] < $item['price']) {
            $savings_per_item = ($item['price'] - $item['sale_price']) * $item['quantity'];
            $total_savings += $savings_per_item;
            $item['savings'] = $savings_per_item;
        } else {
            $item['savings'] = 0;
        }
    }
    
} catch (PDOException $e) {
    logError("Cart fetch error: " . $e->getMessage());
    $cart_items = [];
    $total_amount = 0;
    $total_quantity = 0;
    $total_savings = 0;
}

include '../includes/header.php';
?>

<div class="container py-8">
    <!-- Başlık -->
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-3xl font-bold">Sepetim</h1>
            <p class="text-secondary">
                <?php echo count($cart_items); ?> ürün 
                (<?php echo $total_quantity; ?> adet)
            </p>
        </div>
        
        <?php if (!empty($cart_items)): ?>
            <button onclick="clearCart()" class="btn btn-secondary">
                <i class="fas fa-trash mr-2"></i>
                Sepeti Temizle
            </button>
        <?php endif; ?>
    </div>

    <?php if (!empty($cart_items)): ?>
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Sepet Ürünleri -->
            <div class="lg:col-span-2">
                <div class="space-y-4" id="cartItems">
                    <?php foreach ($cart_items as $item): ?>
                        <div class="cart-item card p-6" data-item-id="<?php echo $item['id']; ?>">
                            <div class="flex flex-col sm:flex-row gap-4">
                                <!-- Ürün Resmi -->
                                <div class="flex-shrink-0">
                                    <a href="/ai-ecommerce/products/detail.php?slug=<?php echo urlencode($item['slug']); ?>">
                                        <?php if ($item['main_image']): ?>
                                            <img src="/ai-ecommerce/assets/images/products/<?php echo htmlspecialchars($item['main_image']); ?>" 
                                                 alt="<?php echo htmlspecialchars($item['name']); ?>"
                                                 class="w-24 h-24 object-cover rounded-lg">
                                        <?php else: ?>
                                            <div class="w-24 h-24 bg-gray-200 rounded-lg flex items-center justify-center">
                                                <i class="fas fa-image text-gray-400"></i>
                                            </div>
                                        <?php endif; ?>
                                    </a>
                                </div>
                                
                                <!-- Ürün Bilgileri -->
                                <div class="flex-1">
                                    <div class="flex flex-col sm:flex-row sm:justify-between">
                                        <div class="flex-1">
                                            <!-- Kategori -->
                                            <?php if ($item['category_name']): ?>
                                                <span class="inline-block bg-primary-light text-primary text-xs px-2 py-1 rounded-full mb-2">
                                                    <?php echo htmlspecialchars($item['category_name']); ?>
                                                </span>
                                            <?php endif; ?>
                                            
                                            <!-- Ürün Adı -->
                                            <h3 class="font-semibold text-lg mb-2">
                                                <a href="/ai-ecommerce/products/detail.php?slug=<?php echo urlencode($item['slug']); ?>" 
                                                   class="text-primary hover:text-primary-dark">
                                                    <?php echo htmlspecialchars($item['name']); ?>
                                                </a>
                                            </h3>
                                            
                                            <!-- Fiyat -->
                                            <div class="mb-3">
                                                <?php if ($item['sale_price'] && $item['sale_price'] < $item['price']): ?>
                                                    <div class="flex items-center gap-2">
                                                        <span class="text-lg font-bold text-success">
                                                            <?php echo formatPrice($item['sale_price']); ?>
                                                        </span>
                                                        <span class="text-sm text-gray-400 line-through">
                                                            <?php echo formatPrice($item['price']); ?>
                                                        </span>
                                                        <span class="text-xs bg-error text-white px-2 py-1 rounded">
                                                            <?php echo formatPrice($item['savings']); ?> tasarruf
                                                        </span>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-lg font-bold">
                                                        <?php echo formatPrice($item['price']); ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <!-- Stok Durumu -->
                                            <div class="flex items-center text-sm mb-3">
                                                <?php if ($item['stock_quantity'] >= $item['quantity']): ?>
                                                    <i class="fas fa-check-circle text-success mr-1"></i>
                                                    <span class="text-success">Stokta</span>
                                                <?php else: ?>
                                                    <i class="fas fa-exclamation-triangle text-warning mr-1"></i>
                                                    <span class="text-warning">Sınırlı stok (<?php echo $item['stock_quantity']; ?> kaldı)</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        
                                        <!-- Miktar ve Eylemler -->
                                        <div class="flex flex-col items-end gap-3">
                                            <!-- Toplam Fiyat -->
                                            <div class="text-right">
                                                <div class="text-xl font-bold text-primary">
                                                    <?php echo formatPrice($item['item_total']); ?>
                                                </div>
                                                <div class="text-sm text-gray-500">
                                                    <?php echo $item['quantity']; ?> x <?php echo formatPrice($item['current_price']); ?>
                                                </div>
                                            </div>
                                            
                                            <!-- Miktar Kontrolü -->
                                            <div class="flex items-center border rounded-lg">
                                                <button type="button" 
                                                        class="px-3 py-2 hover:bg-gray-100 transition-colors"
                                                        onclick="updateQuantity(<?php echo $item['id']; ?>, <?php echo $item['quantity'] - 1; ?>)"
                                                        <?php echo $item['quantity'] <= 1 ? 'disabled' : ''; ?>>
                                                    <i class="fas fa-minus text-sm"></i>
                                                </button>
                                                
                                                <input type="number" 
                                                       value="<?php echo $item['quantity']; ?>"
                                                       min="1"
                                                       max="<?php echo $item['stock_quantity']; ?>"
                                                       class="w-16 text-center border-0 outline-none"
                                                       onchange="updateQuantity(<?php echo $item['id']; ?>, this.value)">
                                                
                                                <button type="button" 
                                                        class="px-3 py-2 hover:bg-gray-100 transition-colors"
                                                        onclick="updateQuantity(<?php echo $item['id']; ?>, <?php echo $item['quantity'] + 1; ?>)"
                                                        <?php echo $item['quantity'] >= $item['stock_quantity'] ? 'disabled' : ''; ?>>
                                                    <i class="fas fa-plus text-sm"></i>
                                                </button>
                                            </div>
                                            
                                            <!-- Kaldır Butonu -->
                                            <button onclick="removeFromCart(<?php echo $item['id']; ?>)" 
                                                    class="text-error hover:bg-error hover:text-white px-3 py-1 rounded text-sm transition-colors">
                                                <i class="fas fa-trash mr-1"></i>
                                                Kaldır
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Alışverişe Devam Et -->
                <div class="mt-8">
                    <a href="/ai-ecommerce/products/" class="btn btn-secondary">
                        <i class="fas fa-arrow-left mr-2"></i>
                        Alışverişe Devam Et
                    </a>
                </div>
            </div>
            
            <!-- Sipariş Özeti -->
            <div class="lg:col-span-1">
                <div class="card p-6 sticky top-24" id="orderSummary">
                    <h3 class="text-xl font-semibold mb-6">Sipariş Özeti</h3>
                    
                    <div class="space-y-3 mb-6">
                        <div class="flex justify-between">
                            <span>Ara Toplam (<?php echo $total_quantity; ?> ürün)</span>
                            <span id="subtotal"><?php echo formatPrice($total_amount + $total_savings); ?></span>
                        </div>
                        
                        <?php if ($total_savings > 0): ?>
                            <div class="flex justify-between text-success">
                                <span>İndirim</span>
                                <span id="savings">-<?php echo formatPrice($total_savings); ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <div class="flex justify-between">
                            <span>Kargo</span>
                            <span class="text-success">Ücretsiz</span>
                        </div>
                        
                        <hr>
                        
                        <div class="flex justify-between text-lg font-bold">
                            <span>Toplam</span>
                            <span id="total" class="text-primary"><?php echo formatPrice($total_amount); ?></span>
                        </div>
                    </div>
                    
                    <!-- Kupon Kodu -->
                    <div class="mb-6">
                        <div class="flex gap-2">
                            <input type="text" 
                                   id="couponCode"
                                   placeholder="Kupon kodu"
                                   class="form-input flex-1">
                            <button onclick="applyCoupon()" class="btn btn-secondary">
                                Uygula
                            </button>
                        </div>
                    </div>
                    
                    <!-- Güvenlik Belirtileri -->
                    <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
                        <div class="space-y-2 text-sm">
                            <div class="flex items-center text-green-700">
                                <i class="fas fa-shield-alt mr-2"></i>
                                <span>SSL ile güvenli ödeme</span>
                            </div>
                            <div class="flex items-center text-green-700">
                                <i class="fas fa-truck mr-2"></i>
                                <span>Ücretsiz kargo</span>
                            </div>
                            <div class="flex items-center text-green-700">
                                <i class="fas fa-undo mr-2"></i>
                                <span>14 gün iade garantisi</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Ödeme Butonu -->
                    <button onclick="proceedToCheckout()" class="btn btn-primary btn-full btn-lg">
                        <i class="fas fa-credit-card mr-2"></i>
                        Ödemeye Geç
                    </button>
                    
                    <!-- Ödeme Yöntemleri -->
                    <div class="mt-4 text-center">
                        <p class="text-sm text-gray-500 mb-2">Kabul edilen ödeme yöntemleri:</p>
                        <div class="flex justify-center gap-2">
                            <i class="fab fa-cc-visa text-2xl text-blue-600"></i>
                            <i class="fab fa-cc-mastercard text-2xl text-red-600"></i>
                            <i class="fab fa-cc-paypal text-2xl text-blue-500"></i>
                            <i class="fas fa-university text-2xl text-gray-600"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
    <?php else: ?>
        <!-- Boş Sepet -->
        <div class="text-center py-16">
            <div class="w-32 h-32 mx-auto mb-6 bg-gray-200 rounded-full flex items-center justify-center">
                <i class="fas fa-shopping-cart text-gray-400 text-4xl"></i>
            </div>
            <h2 class="text-2xl font-semibold text-gray-700 mb-4">Sepetiniz Boş</h2>
            <p class="text-gray-500 mb-8 max-w-md mx-auto">
                Henüz sepetinize ürün eklememişsiniz. 
                Harika ürünlerimizi keşfetmek için alışverişe başlayın!
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="/ai-ecommerce/products/" class="btn btn-primary btn-lg">
                    <i class="fas fa-shopping-bag mr-2"></i>
                    Alışverişe Başla
                </a>
                <a href="/ai-ecommerce/ai-assistant/" class="btn btn-secondary btn-lg">
                    <i class="fas fa-robot mr-2"></i>
                    AI Önerilerini Gör
                </a>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
// Miktar güncelleme
function updateQuantity(itemId, newQuantity) {
    if (newQuantity < 1) return;
    
    fetch('/ai-ecommerce/api/cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'update',
            item_id: itemId,
            quantity: parseInt(newQuantity)
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload(); // Sayfayı yenile
        } else {
            window.aiCommerce.showNotification(data.message || 'Güncelleme başarısız!', 'error');
        }
    })
    .catch(error => {
        console.error('Update error:', error);
        window.aiCommerce.showNotification('Bir hata oluştu!', 'error');
    });
}

// Sepetten çıkarma
function removeFromCart(itemId) {
    if (!confirm('Bu ürünü sepetinizden çıkarmak istediğinizden emin misiniz?')) {
        return;
    }
    
    fetch('/ai-ecommerce/api/cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'remove',
            item_id: itemId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Ürünü DOM'dan kaldır
            const item = document.querySelector(`[data-item-id="${itemId}"]`);
            item.style.opacity = '0';
            item.style.transform = 'translateX(-100%)';
            
            setTimeout(() => {
                location.reload();
            }, 300);
        } else {
            window.aiCommerce.showNotification(data.message || 'Silme başarısız!', 'error');
        }
    })
    .catch(error => {
        console.error('Remove error:', error);
        window.aiCommerce.showNotification('Bir hata oluştu!', 'error');
    });
}

// Sepeti temizle
function clearCart() {
    if (!confirm('Sepetinizdeki tüm ürünleri silmek istediğinizden emin misiniz?')) {
        return;
    }
    
    fetch('/ai-ecommerce/api/cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'clear'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.aiCommerce.showNotification('Sepet temizlendi!', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            window.aiCommerce.showNotification(data.message || 'Temizleme başarısız!', 'error');
        }
    })
    .catch(error => {
        console.error('Clear error:', error);
        window.aiCommerce.showNotification('Bir hata oluştu!', 'error');
    });
}

// Kupon uygula
function applyCoupon() {
    const couponCode = document.getElementById('couponCode').value.trim();
    
    if (!couponCode) {
        window.aiCommerce.showNotification('Kupon kodu giriniz!', 'warning');
        return;
    }
    
    // TODO: Kupon API'si
    window.aiCommerce.showNotification('Kupon sistemi yakında aktif olacak!', 'info');
}

// Ödemeye geç
function proceedToCheckout() {
    window.location.href = '/ai-ecommerce/checkout/';
}

// Sayfa yüklendiğinde animasyonlar
document.addEventListener('DOMContentLoaded', function() {
    const cartItems = document.querySelectorAll('.cart-item');
    cartItems.forEach((item, index) => {
        setTimeout(() => {
            item.classList.add('fade-in');
        }, index * 100);
    });
});
</script>

<style>
/* Cart item animations */
.cart-item {
    opacity: 0;
    transform: translateY(20px);
    transition: all 0.3s ease-out;
}

.cart-item.fade-in {
    opacity: 1;
    transform: translateY(0);
}

.cart-item:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-lg);
}

/* Quantity input styling */
input[type="number"]::-webkit-outer-spin-button,
input[type="number"]::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}

input[type="number"] {
    -moz-appearance: textfield;
}

/* Sticky sidebar on scroll */
@media (min-width: 1024px) {
    .sticky {
        position: sticky;
        top: 6rem;
    }
}

/* Mobile responsive adjustments */
@media (max-width: 640px) {
    .cart-item .flex-col {
        align-items: stretch;
    }
    
    .cart-item .items-end {
        align-items: stretch;
        text-align: center;
    }
}

/* Loading state for buttons */
.btn.loading {
    position: relative;
    pointer-events: none;
}

.btn.loading::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 20px;
    height: 20px;
    border: 2px solid transparent;
    border-top: 2px solid currentColor;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: translate(-50%, -50%) rotate(0deg); }
    100% { transform: translate(-50%, -50%) rotate(360deg); }
}
</style>

<?php include '../includes/footer.php'; ?>