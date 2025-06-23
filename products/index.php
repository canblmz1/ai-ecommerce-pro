<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/Product.php';
require_once '../includes/Category.php';

requireLogin();

$page_title = 'Ürünler';
$page_description = 'AI Commerce Pro ürün kataloğu - En son teknoloji ürünlerini keşfedin';

$product = new Product($pdo);
$category = new Category($pdo);

// Filtreleri al
$filters = [
    'category_id' => isset($_GET['category']) ? (int)$_GET['category'] : null,
    'search' => isset($_GET['search']) ? trim($_GET['search']) : '',
    'min_price' => isset($_GET['min_price']) ? (float)$_GET['min_price'] : null,
    'max_price' => isset($_GET['max_price']) ? (float)$_GET['max_price'] : null,
    'sort' => isset($_GET['sort']) ? $_GET['sort'] : 'newest',
    'featured' => isset($_GET['featured']) ? true : false
];

// Sayfalama
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 12;

// Ürünleri getir
$result = $product->getProducts($filters, $page, $limit);
$products = $result['success'] ? $result['products'] : [];
$total_pages = $result['success'] ? $result['total_pages'] : 1;
$total_products = $result['success'] ? $result['total'] : 0;

// Kategorileri getir
$categories_result = $category->getCategories();
$categories = $categories_result['success'] ? $categories_result['categories'] : [];

// Aktif kategori bilgisi
$active_category = null;
if ($filters['category_id']) {
    $active_category_result = $category->getCategoryById($filters['category_id']);
    $active_category = $active_category_result['success'] ? $active_category_result['category'] : null;
}

include '../includes/header.php';
?>

<div class="container py-8">
    <!-- Sayfa başlığı -->
    <div class="mb-8">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-3xl font-bold mb-2">
                    <?php if ($active_category): ?>
                        <?php echo htmlspecialchars($active_category['name']); ?> Ürünleri
                    <?php elseif ($filters['search']): ?>
                        "<?php echo htmlspecialchars($filters['search']); ?>" için Arama Sonuçları
                    <?php else: ?>
                        Tüm Ürünler
                    <?php endif; ?>
                </h1>
                <p class="text-secondary">
                    <?php echo number_format($total_products); ?> ürün bulundu
                    <?php if ($active_category && $active_category['description']): ?>
                        <br><span class="text-sm"><?php echo htmlspecialchars($active_category['description']); ?></span>
                    <?php endif; ?>
                </p>
            </div>
            
            <!-- Sıralama -->
            <div class="mt-4 md:mt-0">
                <select id="sortSelect" class="form-input w-auto" onchange="updateSort()">
                    <option value="newest" <?php echo $filters['sort'] === 'newest' ? 'selected' : ''; ?>>En Yeni</option>
                    <option value="oldest" <?php echo $filters['sort'] === 'oldest' ? 'selected' : ''; ?>>En Eski</option>
                    <option value="price_asc" <?php echo $filters['sort'] === 'price_asc' ? 'selected' : ''; ?>>Fiyat (Düşük → Yüksek)</option>
                    <option value="price_desc" <?php echo $filters['sort'] === 'price_desc' ? 'selected' : ''; ?>>Fiyat (Yüksek → Düşük)</option>
                    <option value="name_asc" <?php echo $filters['sort'] === 'name_asc' ? 'selected' : ''; ?>>İsim (A → Z)</option>
                    <option value="name_desc" <?php echo $filters['sort'] === 'name_desc' ? 'selected' : ''; ?>>İsim (Z → A)</option>
                </select>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
        <!-- Filtreler (Sidebar) -->
        <div class="lg:col-span-1">
            <div class="card p-6 sticky top-24">
                <h3 class="font-semibold mb-4 flex items-center">
                    <i class="fas fa-filter mr-2 text-primary"></i>
                    Filtreler
                </h3>

                <form id="filterForm" method="GET">
                    <!-- Arama kutusu -->
                    <div class="mb-6">
                        <label class="form-label">Ürün Ara</label>
                        <div class="relative">
                            <input type="text" 
                                   name="search" 
                                   id="searchInput"
                                   class="form-input pl-10" 
                                   placeholder="Ürün adı..."
                                   value="<?php echo htmlspecialchars($filters['search']); ?>">
                            <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                        </div>
                    </div>

                    <!-- Kategoriler -->
                    <div class="mb-6">
                        <label class="form-label">Kategori</label>
                        <div class="space-y-2">
                            <label class="flex items-center">
                                <input type="radio" name="category" value="" class="mr-2" 
                                       <?php echo !$filters['category_id'] ? 'checked' : ''; ?>>
                                <span class="text-sm">Tümü</span>
                                <span class="ml-auto text-xs text-gray-500"><?php echo number_format($total_products); ?></span>
                            </label>
                            
                            <?php foreach ($categories as $cat): ?>
                                <label class="flex items-center">
                                    <input type="radio" name="category" value="<?php echo $cat['id']; ?>" class="mr-2"
                                           <?php echo $filters['category_id'] == $cat['id'] ? 'checked' : ''; ?>>
                                    <span class="text-sm"><?php echo htmlspecialchars($cat['name']); ?></span>
                                    <span class="ml-auto text-xs text-gray-500"><?php echo number_format($cat['product_count']); ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Fiyat aralığı -->
                    <div class="mb-6">
                        <label class="form-label">Fiyat Aralığı</label>
                        <div class="grid grid-cols-2 gap-2">
                            <input type="number" 
                                   name="min_price" 
                                   class="form-input" 
                                   placeholder="Min ₺"
                                   value="<?php echo $filters['min_price'] ?: ''; ?>">
                            <input type="number" 
                                   name="max_price" 
                                   class="form-input" 
                                   placeholder="Max ₺"
                                   value="<?php echo $filters['max_price'] ?: ''; ?>">
                        </div>
                    </div>

                    <!-- Öne çıkanlar -->
                    <div class="mb-6">
                        <label class="flex items-center">
                            <input type="checkbox" name="featured" value="1" class="mr-2"
                                   <?php echo $filters['featured'] ? 'checked' : ''; ?>>
                            <span class="text-sm">Sadece Öne Çıkanlar</span>
                        </label>
                    </div>

                    <!-- Butonlar -->
                    <div class="space-y-2">
                        <button type="submit" class="btn btn-primary btn-full">
                            <i class="fas fa-search mr-2"></i>Filtrele
                        </button>
                        <a href="/ai-ecommerce/products/" class="btn btn-secondary btn-full">
                            <i class="fas fa-undo mr-2"></i>Temizle
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Ürün listesi -->
        <div class="lg:col-span-3">
            <?php if (!empty($products)): ?>
                <!-- Ürün grid -->
                <div class="product-grid" id="productGrid">
                    <?php foreach ($products as $prod): ?>
                        <div class="product-card fade-in" data-product-id="<?php echo $prod['id']; ?>">
                            <!-- Ürün resmi -->
                            <div class="relative overflow-hidden">
                                <?php if ($prod['main_image']): ?>
                                    <img src="/ai-ecommerce/assets/images/products/<?php echo htmlspecialchars($prod['main_image']); ?>" 
                                         alt="<?php echo htmlspecialchars($prod['name']); ?>" 
                                         class="product-image">
                                <?php else: ?>
                                    <div class="product-image bg-gray-200 flex items-center justify-center">
                                        <i class="fas fa-image text-gray-400 text-4xl"></i>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Öne çıkan badge -->
                                <?php if ($prod['is_featured']): ?>
                                    <span class="absolute top-3 left-3 bg-warning text-white px-2 py-1 rounded-full text-xs font-medium">
                                        <i class="fas fa-star mr-1"></i>Öne Çıkan
                                    </span>
                                <?php endif; ?>
                                
                                <!-- İndirim badge -->
                                <?php if ($prod['sale_price'] && $prod['sale_price'] < $prod['price']): ?>
                                    <?php $discount = round((($prod['price'] - $prod['sale_price']) / $prod['price']) * 100); ?>
                                    <span class="absolute top-3 right-3 bg-error text-white px-2 py-1 rounded-full text-xs font-medium">
                                        -%<?php echo $discount; ?>
                                    </span>
                                <?php endif; ?>
                                
                                <!-- Hızlı eylemler -->
                                <div class="absolute inset-0 bg-black bg-opacity-50 flex items-center justify-center opacity-0 hover:opacity-100 transition-opacity">
                                    <div class="flex gap-2">
                                        <a href="/ai-ecommerce/products/detail.php?slug=<?php echo urlencode($prod['slug']); ?>" 
                                           class="btn btn-white btn-sm">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <button class="btn btn-primary btn-sm add-to-cart" 
                                                data-product-id="<?php echo $prod['id']; ?>"
                                                data-tooltip="Sepete Ekle">
                                            <i class="fas fa-cart-plus"></i>
                                        </button>
                                        <button class="btn btn-secondary btn-sm" 
                                                onclick="toggleWishlist(<?php echo $prod['id']; ?>)"
                                                data-tooltip="Favorilere Ekle">
                                            <i class="fas fa-heart"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Ürün bilgileri -->
                            <div class="product-info">
                                <!-- Kategori -->
                                <?php if ($prod['category_name']): ?>
                                    <a href="/ai-ecommerce/products/?category=<?php echo $prod['category_id']; ?>" 
                                       class="inline-block bg-primary-light text-primary text-xs px-2 py-1 rounded-full mb-2 hover:bg-primary hover:text-white transition-colors">
                                        <?php echo htmlspecialchars($prod['category_name']); ?>
                                    </a>
                                <?php endif; ?>
                                
                                <!-- Ürün adı -->
                                <h3 class="product-title">
                                    <a href="/ai-ecommerce/products/detail.php?slug=<?php echo urlencode($prod['slug']); ?>" 
                                       class="text-primary hover:text-primary-dark transition-colors">
                                        <?php echo htmlspecialchars($prod['name']); ?>
                                    </a>
                                </h3>
                                
                                <!-- Fiyat -->
                                <div class="product-price">
                                    <?php if ($prod['sale_price'] && $prod['sale_price'] < $prod['price']): ?>
                                        <span class="text-gray-400 line-through text-sm mr-2">
                                            <?php echo formatPrice($prod['price']); ?>
                                        </span>
                                        <span class="text-success font-bold">
                                            <?php echo formatPrice($prod['sale_price']); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="font-bold">
                                            <?php echo formatPrice($prod['price']); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Açıklama -->
                                <p class="product-description">
                                    <?php echo htmlspecialchars(substr($prod['short_description'], 0, 120)); ?>
                                    <?php if (strlen($prod['short_description']) > 120) echo '...'; ?>
                                </p>
                                
                                <!-- Stok durumu -->
                                <div class="flex items-center justify-between mt-3 text-xs text-gray-500">
                                    <span class="flex items-center">
                                        <i class="fas fa-box mr-1"></i>
                                        <?php if ($prod['stock_quantity'] > 0): ?>
                                            <span class="text-success">Stokta (<?php echo $prod['stock_quantity']; ?>)</span>
                                        <?php else: ?>
                                            <span class="text-error">Stok yok</span>
                                        <?php endif; ?>
                                    </span>
                                    <span class="flex items-center">
                                        <i class="fas fa-calendar mr-1"></i>
                                        <?php echo date('d.m.Y', strtotime($prod['created_at'])); ?>
                                    </span>
                                </div>
                                
                                <!-- Eylemler -->
                                <div class="flex gap-2 mt-4">
                                    <a href="/ai-ecommerce/products/detail.php?slug=<?php echo urlencode($prod['slug']); ?>" 
                                       class="btn btn-primary btn-sm flex-1">
                                        <i class="fas fa-eye mr-1"></i>Detaylar
                                    </a>
                                    <?php if ($prod['stock_quantity'] > 0): ?>
                                        <button class="btn btn-secondary btn-sm add-to-cart" 
                                                data-product-id="<?php echo $prod['id']; ?>">
                                            <i class="fas fa-cart-plus"></i>
                                        </button>
                                    <?php else: ?>
                                        <button class="btn btn-secondary btn-sm opacity-50 cursor-not-allowed" disabled>
                                            <i class="fas fa-times"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Sayfalama -->
                <?php if ($total_pages > 1): ?>
                    <div class="flex justify-center mt-12">
                        <nav class="flex items-center gap-2">
                            <!-- Önceki sayfa -->
                            <?php if ($page > 1): ?>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" 
                                   class="btn btn-secondary btn-sm">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            <?php endif; ?>
                            
                            <!-- Sayfa numaraları -->
                            <?php 
                            $start = max(1, $page - 2);
                            $end = min($total_pages, $page + 2);
                            
                            for ($i = $start; $i <= $end; $i++): 
                            ?>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" 
                                   class="btn <?php echo $i == $page ? 'btn-primary' : 'btn-secondary'; ?> btn-sm">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>
                            
                            <!-- Sonraki sayfa -->
                            <?php if ($page < $total_pages): ?>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" 
                                   class="btn btn-secondary btn-sm">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            <?php endif; ?>
                        </nav>
                    </div>
                    
                    <!-- Sayfa bilgisi -->
                    <div class="text-center mt-4 text-sm text-gray-500">
                        Sayfa <?php echo $page; ?> / <?php echo $total_pages; ?> 
                        (Toplam <?php echo number_format($total_products); ?> ürün)
                    </div>
                <?php endif; ?>

            <?php else: ?>
                <!-- Ürün bulunamadı -->
                <div class="text-center py-16">
                    <div class="w-24 h-24 mx-auto mb-6 bg-gray-200 rounded-full flex items-center justify-center">
                        <i class="fas fa-search text-gray-400 text-3xl"></i>
                    </div>
                    <h3 class="text-2xl font-semibold text-gray-700 mb-2">Ürün Bulunamadı</h3>
                    <p class="text-gray-500 mb-6">
                        <?php if ($filters['search']): ?>
                            "<?php echo htmlspecialchars($filters['search']); ?>" için sonuç bulunamadı.
                        <?php elseif ($active_category): ?>
                            Bu kategoride henüz ürün bulunmuyor.
                        <?php else: ?>
                            Filtre kriterlerinize uygun ürün bulunamadı.
                        <?php endif; ?>
                    </p>
                    <div class="flex flex-col sm:flex-row gap-4 justify-center">
                        <a href="/ai-ecommerce/products/" class="btn btn-primary">
                            <i class="fas fa-undo mr-2"></i>Filtreleri Temizle
                        </a>
                        <a href="/ai-ecommerce/" class="btn btn-secondary">
                            <i class="fas fa-home mr-2"></i>Ana Sayfaya Dön
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Sıralama değişimi
function updateSort() {
    const sortValue = document.getElementById('sortSelect').value;
    const url = new URL(window.location);
    url.searchParams.set('sort', sortValue);
    window.location.href = url.toString();
}

// Wishlist toggle
function toggleWishlist(productId) {
    // AJAX ile wishlist ekleme/çıkarma
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

// Filtre formu otomatik gönderimi
document.getElementById('filterForm').addEventListener('change', function(e) {
    if (e.target.type === 'radio') {
        this.submit();
    }
});

// Arama debounce
let searchTimeout;
document.getElementById('searchInput').addEventListener('input', function() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        document.getElementById('filterForm').submit();
    }, 1000);
});

// Lazy loading animation
document.addEventListener('DOMContentLoaded', function() {
    const cards = document.querySelectorAll('.product-card');
    cards.forEach((card, index) => {
        setTimeout(() => {
            card.classList.add('animate-in');
        }, index * 100);
    });
});
</script>

<style>
/* Product grid responsive */
.product-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 1.5rem;
}

@media (max-width: 768px) {
    .product-grid {
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 1rem;
    }
}

/* Animation classes */
.fade-in {
    opacity: 0;
    transform: translateY(20px);
    transition: all 0.3s ease-out;
}

.fade-in.animate-in {
    opacity: 1;
    transform: translateY(0);
}

/* Product card hover effects */
.product-card {
    transition: transform 0.2s ease-out;
}

.product-card:hover {
    transform: translateY(-4px);
}

/* Filter sidebar on mobile */
@media (max-width: 1024px) {
    .lg\:col-span-1 {
        order: 2;
    }
    .lg\:col-span-3 {
        order: 1;
    }
}
</style>

<?php include '../includes/footer.php'; ?>