<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/Product.php';

requireLogin();

// Slug parametresini al
$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';
if (empty($slug)) {
    header('Location: /ai-ecommerce/products/');
    exit();
}

$product = new Product($pdo);

// Ürünü getir
$result = $product->getProductBySlug($slug);
if (!$result['success']) {
    header('Location: /ai-ecommerce/products/');
    exit();
}

$prod = $result['product'];

// İlgili ürünleri getir
$related_result = $product->getRelatedProducts($prod['id'], $prod['category_id'], 4);
$related_products = $related_result['success'] ? $related_result['products'] : [];

// SEO meta bilgileri
$page_title = $prod['meta_title'] ?: $prod['name'];
$page_description = $prod['meta_description'] ?: $prod['short_description'];

include '../includes/header.php';
?>

<div class="container py-8">
    <!-- Breadcrumb -->
    <nav class="flex items-center space-x-2 text-sm text-gray-500 mb-8">
        <a href="/ai-ecommerce/" class="hover:text-primary">Ana Sayfa</a>
        <i class="fas fa-chevron-right text-xs"></i>
        <a href="/ai-ecommerce/products/" class="hover:text-primary">Ürünler</a>
        <?php if ($prod['category_name']): ?>
            <i class="fas fa-chevron-right text-xs"></i>
            <a href="/ai-ecommerce/products/?category=<?php echo $prod['category_id']; ?>" class="hover:text-primary">
                <?php echo htmlspecialchars($prod['category_name']); ?>
            </a>
        <?php endif; ?>
        <i class="fas fa-chevron-right text-xs"></i>
        <span class="text-primary"><?php echo htmlspecialchars($prod['name']); ?></span>
    </nav>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 mb-16">
        <!-- Ürün Resimleri -->
        <div class="space-y-4">
            <!-- Ana resim -->
            <div class="relative overflow-hidden rounded-lg bg-gray-100" style="aspect-ratio: 1/1;">
                <?php if ($prod['main_image']): ?>
                    <img id="mainImage" 
                         src="/ai-ecommerce/assets/images/products/<?php echo htmlspecialchars($prod['main_image']); ?>" 
                         alt="<?php echo htmlspecialchars($prod['name']); ?>"
                         class="w-full h-full object-cover cursor-zoom-in"
                         onclick="openImageModal(this.src)">
                <?php else: ?>
                    <div class="w-full h-full flex items-center justify-center">
                        <i class="fas fa-image text-gray-400 text-6xl"></i>
                    </div>
                <?php endif; ?>
                
                <!-- Zoom butonu -->
                <?php if ($prod['main_image']): ?>
                    <button class="absolute top-4 right-4 w-10 h-10 bg-white rounded-full shadow-lg flex items-center justify-center hover:bg-gray-50"
                            onclick="openImageModal('/ai-ecommerce/assets/images/products/<?php echo htmlspecialchars($prod['main_image']); ?>')">
                        <i class="fas fa-expand text-gray-600"></i>
                    </button>
                <?php endif; ?>
                
                <!-- Öne çıkan badge -->
                <?php if ($prod['is_featured']): ?>
                    <span class="absolute top-4 left-4 bg-warning text-white px-3 py-1 rounded-full text-sm font-medium">
                        <i class="fas fa-star mr-1"></i>Öne Çıkan
                    </span>
                <?php endif; ?>
                
                <!-- İndirim badge -->
                <?php if ($prod['sale_price'] && $prod['sale_price'] < $prod['price']): ?>
                    <?php $discount = round((($prod['price'] - $prod['sale_price']) / $prod['price']) * 100); ?>
                    <span class="absolute top-4 right-16 bg-error text-white px-3 py-1 rounded-full text-sm font-medium">
                        -%<?php echo $discount; ?>
                    </span>
                <?php endif; ?>
            </div>
            
            <!-- Galeri thumbnails -->
            <?php if (!empty($prod['gallery'])): ?>
                <div class="grid grid-cols-4 gap-2">
                    <!-- Ana resim thumbnail -->
                    <?php if ($prod['main_image']): ?>
                        <button class="gallery-thumb active aspect-square rounded-lg overflow-hidden border-2 border-primary"
                                onclick="changeMainImage('/ai-ecommerce/assets/images/products/<?php echo htmlspecialchars($prod['main_image']); ?>', this)">
                            <img src="/ai-ecommerce/assets/images/products/<?php echo htmlspecialchars($prod['main_image']); ?>" 
                                 alt="Ana resim" class="w-full h-full object-cover">
                        </button>
                    <?php endif; ?>
                    
                    <!-- Galeri resimleri -->
                    <?php foreach ($prod['gallery'] as $index => $image): ?>
                        <button class="gallery-thumb aspect-square rounded-lg overflow-hidden border-2 border-transparent hover:border-primary"
                                onclick="changeMainImage('/ai-ecommerce/assets/images/products/<?php echo htmlspecialchars($image); ?>', this)">
                            <img src="/ai-ecommerce/assets/images/products/<?php echo htmlspecialchars($image); ?>" 
                                 alt="Galeri <?php echo $index + 1; ?>" class="w-full h-full object-cover">
                        </button>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Ürün Bilgileri -->
        <div class="space-y-6">
            <!-- Kategori -->
            <?php if ($prod['category_name']): ?>
                <a href="/ai-ecommerce/products/?category=<?php echo $prod['category_id']; ?>" 
                   class="inline-block bg-primary-light text-primary px-3 py-1 rounded-full text-sm font-medium hover:bg-primary hover:text-white transition-colors">
                    <?php echo htmlspecialchars($prod['category_name']); ?>
                </a>
            <?php endif; ?>
            
            <!-- Ürün Adı -->
            <h1 class="text-3xl font-bold text-gray-900"><?php echo htmlspecialchars($prod['name']); ?></h1>
            
            <!-- Kısa Açıklama -->
            <?php if ($prod['short_description']): ?>
                <p class="text-lg text-gray-600"><?php echo htmlspecialchars($prod['short_description']); ?></p>
            <?php endif; ?>
            
            <!-- Fiyat -->
            <div class="space-y-2">
                <?php if ($prod['sale_price'] && $prod['sale_price'] < $prod['price']): ?>
                    <div class="flex items-center space-x-3">
                        <span class="text-3xl font-bold text-success"><?php echo formatPrice($prod['sale_price']); ?></span>
                        <span class="text-xl text-gray-400 line-through"><?php echo formatPrice($prod['price']); ?></span>
                        <span class="bg-error text-white px-2 py-1 rounded text-sm">
                            %<?php echo round((($prod['price'] - $prod['sale_price']) / $prod['price']) * 100); ?> İndirim
                        </span>
                    </div>
                <?php else: ?>
                    <span class="text-3xl font-bold text-gray-900"><?php echo formatPrice($prod['price']); ?></span>
                <?php endif; ?>
                <p class="text-sm text-gray-500">KDV Dahil</p>
            </div>
            
            <!-- Stok Durumu -->
            <div class="flex items-center space-x-2">
                <?php if ($prod['stock_quantity'] > 0): ?>
                    <i class="fas fa-check-circle text-success"></i>
                    <span class="text-success font-medium">Stokta (<?php echo $prod['stock_quantity']; ?> adet)</span>
                <?php else: ?>
                    <i class="fas fa-times-circle text-error"></i>
                    <span class="text-error font-medium">Stok Yok</span>
                <?php endif; ?>
            </div>
            
            <!-- Özellikler -->
            <?php if (!empty($prod['features'])): ?>
                <div class="bg-gray-50 rounded-lg p-4">
                    <h3 class="font-semibold mb-3 flex items-center">
                        <i class="fas fa-list-ul mr-2 text-primary"></i>
                        Özellikler
                    </h3>
                    <ul class="space-y-2">
                        <?php foreach ($prod['features'] as $feature): ?>
                            <li class="flex items-center text-sm">
                                <i class="fas fa-check text-success mr-2"></i>
                                <?php echo htmlspecialchars($feature); ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <!-- Satın Al Formu -->
            <form id="addToCartForm" class="space-y-4">
                <input type="hidden" name="product_id" value="<?php echo $prod['id']; ?>">
                
                <!-- Miktar -->
                <div class="flex items-center space-x-4">
                    <label class="font-medium">Miktar:</label>
                    <div class="flex items-center border rounded-lg">
                        <button type="button" class="px-3 py-2 hover:bg-gray-100" onclick="changeQuantity(-1)">
                            <i class="fas fa-minus text-sm"></i>
                        </button>
                        <input type="number" 
                               name="quantity" 
                               id="quantityInput"
                               value="1" 
                               min="1" 
                               max="<?php echo $prod['stock_quantity']; ?>"
                               class="w-16 text-center border-0 outline-none">
                        <button type="button" class="px-3 py-2 hover:bg-gray-100" onclick="changeQuantity(1)">
                            <i class="fas fa-plus text-sm"></i>
                        </button>
                    </div>
                </div>
                
                <!-- Eylem Butonları -->
                <div class="space-y-3">
                    <?php if ($prod['stock_quantity'] > 0): ?>
                        <button type="submit" class="btn btn-primary btn-full btn-lg">
                            <i class="fas fa-cart-plus mr-2"></i>
                            Sepete Ekle
                        </button>
                        <button type="button" class="btn btn-success btn-full btn-lg" onclick="buyNow()">
                            <i class="fas fa-bolt mr-2"></i>
                            Hemen Satın Al
                        </button>
                    <?php else: ?>
                        <button type="button" class="btn btn-secondary btn-full btn-lg opacity-50 cursor-not-allowed" disabled>
                            <i class="fas fa-times mr-2"></i>
                            Stok Yok
                        </button>
                    <?php endif; ?>
                    
                    <div class="flex space-x-2">
                        <button type="button" class="btn btn-secondary flex-1" onclick="toggleWishlist(<?php echo $prod['id']; ?>)">
                            <i class="fas fa-heart mr-2"></i>
                            Favorilere Ekle
                        </button>
                        <button type="button" class="btn btn-secondary flex-1" onclick="shareProduct()">
                            <i class="fas fa-share mr-2"></i>
                            Paylaş
                        </button>
                    </div>
                </div>
            </form>
            
            <!-- Güvenlik Garantileri -->
            <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                    <div class="flex items-center">
                        <i class="fas fa-shield-alt text-green-600 mr-2"></i>
                        <span>Güvenli Ödeme</span>
                    </div>
                    <div class="flex items-center">
                        <i class="fas fa-truck text-green-600 mr-2"></i>
                        <span>Hızlı Kargo</span>
                    </div>
                    <div class="flex items-center">
                        <i class="fas fa-undo text-green-600 mr-2"></i>
                        <span>Kolay İade</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Ürün Açıklaması -->
    <div class="mb-16">
        <div class="border-b border-gray-200 mb-8">
            <nav class="-mb-px flex space-x-8">
                <button class="tab-button active" onclick="showTab('description', this)">
                    Açıklama
                </button>
                <button class="tab-button" onclick="showTab('specifications', this)">
                    Özellikler
                </button>
                <button class="tab-button" onclick="showTab('reviews', this)">
                    Yorumlar (0)
                </button>
            </nav>
        </div>
        
        <!-- Açıklama Tab -->
        <div id="description-tab" class="tab-content active">
            <div class="prose max-w-none">
                <?php if ($prod['description']): ?>
                    <?php echo nl2br(htmlspecialchars($prod['description'])); ?>
                <?php else: ?>
                    <p class="text-gray-500">Bu ürün için detaylı açıklama henüz eklenmemiş.</p>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Özellikler Tab -->
        <div id="specifications-tab" class="tab-content">
            <?php if (!empty($prod['features'])): ?>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <?php foreach ($prod['features'] as $feature): ?>
                        <div class="flex items-center p-3 bg-gray-50 rounded-lg">
                            <i class="fas fa-check-circle text-success mr-3"></i>
                            <span><?php echo htmlspecialchars($feature); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-gray-500">Bu ürün için özellik listesi henüz eklenmemiş.</p>
            <?php endif; ?>
        </div>
        
        <!-- Yorumlar Tab -->
        <div id="reviews-tab" class="tab-content">
            <div class="text-center py-12">
                <i class="fas fa-comments text-gray-400 text-4xl mb-4"></i>
                <h3 class="text-lg font-semibold text-gray-600 mb-2">Henüz yorum yok</h3>
                <p class="text-gray-500 mb-6">Bu ürün için ilk yorumu siz yapın!</p>
                <button class="btn btn-primary">
                    <i class="fas fa-star mr-2"></i>
                    Yorum Yap
                </button>
            </div>
        </div>
    </div>

    <!-- İlgili Ürünler -->
    <?php if (!empty($related_products)): ?>
        <div class="mb-16">
            <h2 class="text-2xl font-bold mb-8 text-center">İlgili Ürünler</h2>
            <div class="product-grid">
                <?php foreach ($related_products as $related): ?>
                    <div class="product-card">
                        <div class="relative overflow-hidden">
                            <?php if ($related['main_image']): ?>
                                <img src="/ai-ecommerce/assets/images/products/<?php echo htmlspecialchars($related['main_image']); ?>" 
                                     alt="<?php echo htmlspecialchars($related['name']); ?>" 
                                     class="product-image">
                            <?php else: ?>
                                <div class="product-image bg-gray-200 flex items-center justify-center">
                                    <i class="fas fa-image text-gray-400 text-3xl"></i>
                                </div>
                            <?php endif; ?>
                            
                            <div class="absolute inset-0 bg-black bg-opacity-50 flex items-center justify-center opacity-0 hover:opacity-100 transition-opacity">
                                <a href="/ai-ecommerce/products/detail.php?slug=<?php echo urlencode($related['slug']); ?>" 
                                   class="btn btn-white btn-sm">
                                    <i class="fas fa-eye mr-1"></i>İncele
                                </a>
                            </div>
                        </div>
                        
                        <div class="product-info">
                            <h3 class="product-title">
                                <a href="/ai-ecommerce/products/detail.php?slug=<?php echo urlencode($related['slug']); ?>">
                                    <?php echo htmlspecialchars($related['name']); ?>
                                </a>
                            </h3>
                            <div class="product-price">
                                <?php if ($related['sale_price'] && $related['sale_price'] < $related['price']): ?>
                                    <span class="text-gray-400 line-through text-sm mr-2">
                                        <?php echo formatPrice($related['price']); ?>
                                    </span>
                                    <span class="text-success">
                                        <?php echo formatPrice($related['sale_price']); ?>
                                    </span>
                                <?php else: ?>
                                    <?php echo formatPrice($related['price']); ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Resim Modal -->
<div id="imageModal" class="modal">
    <div class="modal-overlay" onclick="closeImageModal()"></div>
    <div class="modal-content max-w-4xl">
        <button class="modal-close" onclick="closeImageModal()">
            <i class="fas fa-times"></i>
        </button>
        <img id="modalImage" src="" alt="" class="w-full h-auto">
    </div>
</div>

<script>
// Miktar değiştirme
function changeQuantity(change) {
    const input = document.getElementById('quantityInput');
    const currentValue = parseInt(input.value);
    const newValue = currentValue + change;
    const max = parseInt(input.getAttribute('max'));
    
    if (newValue >= 1 && newValue <= max) {
        input.value = newValue;
    }
}

// Ana resim değiştirme
function changeMainImage(imageSrc, thumbnail) {
    document.getElementById('mainImage').src = imageSrc;
    
    // Aktif thumbnail güncelle
    document.querySelectorAll('.gallery-thumb').forEach(thumb => {
        thumb.classList.remove('active', 'border-primary');
        thumb.classList.add('border-transparent');
    });
    
    thumbnail.classList.add('active', 'border-primary');
    thumbnail.classList.remove('border-transparent');
}

// Resim modal
function openImageModal(imageSrc) {
    document.getElementById('modalImage').src = imageSrc;
    document.getElementById('imageModal').classList.add('show');
    document.body.classList.add('modal-open');
}

function closeImageModal() {
    document.getElementById('imageModal').classList.remove('show');
    document.body.classList.remove('modal-open');
}

// Tab değiştirme
function showTab(tabName, button) {
    // Tüm tab içeriklerini gizle
    document.querySelectorAll('.tab-content').forEach(tab => {
        tab.classList.remove('active');
    });
    
    // Tüm butonları pasif yap
    document.querySelectorAll('.tab-button').forEach(btn => {
        btn.classList.remove('active');
    });
    
    // Seçili tab'ı göster
    document.getElementById(tabName + '-tab').classList.add('active');
    button.classList.add('active');
}

// Sepete ekleme
document.getElementById('addToCartForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const productId = formData.get('product_id');
    const quantity = formData.get('quantity');
    
    window.aiCommerce.addToCart(productId, quantity);
});

// Hemen satın al
function buyNow() {
    const productId = document.querySelector('input[name="product_id"]').value;
    const quantity = document.querySelector('input[name="quantity"]').value;
    
    // Önce sepete ekle, sonra checkout sayfasına yönlendir
    window.aiCommerce.addToCart(productId, quantity).then(() => {
        window.location.href = '/ai-ecommerce/checkout/';
    });
}

// Ürün paylaşma
function shareProduct() {
    if (navigator.share) {
        navigator.share({
            title: '<?php echo addslashes($prod['name']); ?>',
            text: '<?php echo addslashes($prod['short_description']); ?>',
            url: window.location.href
        });
    } else {
        // Fallback: URL'yi panoya kopyala
        navigator.clipboard.writeText(window.location.href).then(() => {
            window.aiCommerce.showNotification('Ürün linki panoya kopyalandı!', 'success');
        });
    }
}

// Wishlist toggle (ürün listesinden alınmış)
function toggleWishlist(productId) {
    fetch('/ai-ecommerce/api/wishlist.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'toggle',
            product_id: productId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.aiCommerce.showNotification(data.message, 'success');
        } else {
            window.aiCommerce.showNotification(data.message || 'Bir hata oluştu!', 'error');
        }
    })
    .catch(error => {
        console.error('Wishlist error:', error);
        window.aiCommerce.showNotification('Bir hata oluştu!', 'error');
    });
}

// Klavye kısayolları
document.addEventListener('keydown', function(e) {
    // ESC ile modal kapatma
    if (e.key === 'Escape') {
        closeImageModal();
    }
});
</script>

<style>
/* Tab styles */
.tab-button {
    padding: 12px 0;
    border-bottom: 2px solid transparent;
    color: #6b7280;
    font-weight: 500;
    transition: all 0.2s;
}

.tab-button.active {
    color: var(--primary);
    border-bottom-color: var(--primary);
}

.tab-button:hover {
    color: var(--primary);
}

.tab-content {
    display: none;
}

.tab-content.active {
    display: block;
}

/* Modal styles */
.modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 1000;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.modal.show {
    display: flex;
}

.modal-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.8);
}

.modal-content {
    position: relative;
    background: white;
    border-radius: 12px;
    overflow: hidden;
    max-height: 90vh;
    width: 100%;
}

.modal-close {
    position: absolute;
    top: 12px;
    right: 12px;
    z-index: 10;
    width: 40px;
    height: 40px;
    background: rgba(0, 0, 0, 0.5);
    color: white;
    border: none;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: background 0.2s;
}

.modal-close:hover {
    background: rgba(0, 0, 0, 0.7);
}

/* Gallery thumbnail active state */
.gallery-thumb {
    transition: all 0.2s ease;
}

.gallery-thumb:hover {
    transform: scale(1.05);
}

/* Product image zoom cursor */
.cursor-zoom-in {
    cursor: zoom-in;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .modal-content {
        margin: 10px;
        max-height: 80vh;
    }
    
    .grid.grid-cols-4 {
        grid-template-columns: repeat(3, 1fr);
    }
}
</style>

<?php include '../includes/footer.php'; ?>