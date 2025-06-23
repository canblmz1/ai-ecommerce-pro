<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

// Giriş yapmayan kullanıcıları yönlendir
requireLogin();

$page_title = 'Ana Sayfa';
$page_description = 'AI Commerce Pro ana sayfa - Yapay zeka destekli e-ticaret çözümleri';

// Öne çıkan ürünleri çek
try {
    $featured_products_query = "
        SELECT p.*, c.name as category_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        WHERE p.is_featured = 1 AND p.is_active = 1 
        ORDER BY p.created_at DESC 
        LIMIT 6
    ";
    $featured_products = $pdo->query($featured_products_query)->fetchAll();
} catch (PDOException $e) {
    $featured_products = [];
    logError("Featured products query error: " . $e->getMessage());
}

// İstatistikleri çek
try {
    $stats_query = "
        SELECT 
            (SELECT COUNT(*) FROM products WHERE is_active = 1) as total_products,
            (SELECT COUNT(*) FROM users WHERE user_type = 'customer') as total_customers,
            (SELECT COUNT(*) FROM orders) as total_orders,
            (SELECT COUNT(*) FROM categories WHERE is_active = 1) as total_categories
    ";
    $stats = $pdo->query($stats_query)->fetch();
} catch (PDOException $e) {
    $stats = ['total_products' => 0, 'total_customers' => 0, 'total_orders' => 0, 'total_categories' => 0];
    logError("Stats query error: " . $e->getMessage());
}

include 'includes/header.php';
?>

<!-- Hero Section -->
<section class="hero">
    <div class="container text-center">
        <div class="fade-in">
            <h1 class="text-4xl md:text-6xl font-extrabold mb-6">
                Hoş Geldin, <span class="text-primary"><?php echo htmlspecialchars($_SESSION['full_name'] ?: $_SESSION['username']); ?></span>!
            </h1>
            <p class="text-xl text-secondary mb-8 max-w-3xl mx-auto">
                AI destekli e-ticaret dünyasında en son teknoloji ürünleri ve hizmetleri keşfedin. 
                Yapay zeka ile güçlendirilmiş alışveriş deneyimini yaşayın.
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="/ai-ecommerce/products/" class="btn btn-primary btn-lg">
                    <i class="fas fa-shopping-bag mr-2"></i>
                    Ürünleri Keşfet
                </a>
                <a href="/ai-ecommerce/ai-assistant/" class="btn btn-secondary btn-lg">
                    <i class="fas fa-robot mr-2"></i>
                    AI Asistan ile Konuş
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Stats Section -->
<section class="py-16 bg-white">
    <div class="container">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-8">
            <div class="text-center">
                <div class="w-16 h-16 bg-primary rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-box text-white text-2xl"></i>
                </div>
                <h3 class="text-3xl font-bold text-primary mb-2"><?php echo number_format($stats['total_products']); ?></h3>
                <p class="text-secondary">Toplam Ürün</p>
            </div>
            <div class="text-center">
                <div class="w-16 h-16 bg-success rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-users text-white text-2xl"></i>
                </div>
                <h3 class="text-3xl font-bold text-success mb-2"><?php echo number_format($stats['total_customers']); ?></h3>
                <p class="text-secondary">Mutlu Müşteri</p>
            </div>
            <div class="text-center">
                <div class="w-16 h-16 bg-warning rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-shopping-cart text-white text-2xl"></i>
                </div>
                <h3 class="text-3xl font-bold text-warning mb-2"><?php echo number_format($stats['total_orders']); ?></h3>
                <p class="text-secondary">Tamamlanan Sipariş</p>
            </div>
            <div class="text-center">
                <div class="w-16 h-16 bg-secondary rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-tags text-white text-2xl"></i>
                </div>
                <h3 class="text-3xl font-bold text-secondary mb-2"><?php echo number_format($stats['total_categories']); ?></h3>
                <p class="text-secondary">Kategori</p>
            </div>
        </div>
    </div>
</section>

<!-- Featured Products -->
<section class="py-16">
    <div class="container">
        <div class="text-center mb-12">
            <h2 class="text-3xl font-bold mb-4">Öne Çıkan Ürünler</h2>
            <p class="text-lg text-secondary max-w-2xl mx-auto">
                AI teknolojisi ile desteklenmiş en popüler ürünlerimizi keşfedin
            </p>
        </div>

        <?php if (!empty($featured_products)): ?>
            <div class="product-grid">
                <?php foreach ($featured_products as $product): ?>
                    <div class="product-card">
                        <?php if ($product['main_image']): ?>
                            <img src="/ai-ecommerce/assets/images/products/<?php echo htmlspecialchars($product['main_image']); ?>" 
                                 alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                 class="product-image">
                        <?php else: ?>
                            <div class="product-image bg-gray-200 flex items-center justify-center">
                                <i class="fas fa-image text-gray-400 text-4xl"></i>
                            </div>
                        <?php endif; ?>
                        
                        <div class="product-info">
                            <?php if ($product['category_name']): ?>
                                <span class="inline-block bg-primary-light text-primary text-xs px-2 py-1 rounded-full mb-2">
                                    <?php echo htmlspecialchars($product['category_name']); ?>
                                </span>
                            <?php endif; ?>
                            
                            <h3 class="product-title">
                                <a href="/ai-ecommerce/products/detail.php?slug=<?php echo urlencode($product['slug']); ?>" 
                                   class="text-primary hover:text-primary-dark">
                                    <?php echo htmlspecialchars($product['name']); ?>
                                </a>
                            </h3>
                            
                            <div class="product-price">
                                <?php if ($product['sale_price'] && $product['sale_price'] < $product['price']): ?>
                                    <span class="text-gray-400 line-through text-sm mr-2">
                                        <?php echo formatPrice($product['price']); ?>
                                    </span>
                                    <span class="text-success">
                                        <?php echo formatPrice($product['sale_price']); ?>
                                    </span>
                                <?php else: ?>
                                    <?php echo formatPrice($product['price']); ?>
                                <?php endif; ?>
                            </div>
                            
                            <p class="product-description">
                                <?php echo htmlspecialchars(substr($product['short_description'], 0, 100)); ?>
                                <?php if (strlen($product['short_description']) > 100) echo '...'; ?>
                            </p>
                            
                            <div class="flex gap-2 mt-4">
                                <a href="/ai-ecommerce/products/detail.php?slug=<?php echo urlencode($product['slug']); ?>" 
                                   class="btn btn-primary btn-sm flex-1">
                                    <i class="fas fa-eye mr-1"></i>Detaylar
                                </a>
                                <button class="btn btn-secondary btn-sm" onclick="addToCart(<?php echo $product['id']; ?>)">
                                    <i class="fas fa-cart-plus"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="text-center mt-12">
                <a href="/ai-ecommerce/products/" class="btn btn-primary btn-lg">
                    <i class="fas fa-arrow-right mr-2"></i>
                    Tüm Ürünleri Görüntüle
                </a>
            </div>
        <?php else: ?>
            <div class="text-center py-12">
                <i class="fas fa-box-open text-gray-400 text-6xl mb-4"></i>
                <h3 class="text-xl font-semibold text-gray-600 mb-2">Henüz ürün eklenmemiş</h3>
                <p class="text-gray-500">Yakında harika ürünlerle karşınızda olacağız!</p>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- AI Features -->
<section class="py-16 bg-gradient-to-r from-blue-50 to-indigo-50">
    <div class="container">
        <div class="text-center mb-12">
            <h2 class="text-3xl font-bold mb-4">AI Destekli Özellikler</h2>
            <p class="text-lg text-secondary max-w-2xl mx-auto">
                Yapay zeka teknolojisi ile alışveriş deneyiminizi bir üst seviyeye taşıyın
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="card text-center p-8 hover:shadow-xl transition-shadow">
                <div class="w-16 h-16 bg-primary rounded-full flex items-center justify-center mx-auto mb-6">
                    <i class="fas fa-robot text-white text-2xl"></i>
                </div>
                <h3 class="text-xl font-semibold mb-4">AI Asistan</h3>
                <p class="text-secondary mb-6">
                    7/24 aktif AI asistanımız size en uygun ürünleri önerir ve sorularınızı yanıtlar.
                </p>
                <a href="/ai-ecommerce/ai-assistant/" class="btn btn-primary">
                    Hemen Dene
                </a>
            </div>

            <div class="card text-center p-8 hover:shadow-xl transition-shadow">
                <div class="w-16 h-16 bg-success rounded-full flex items-center justify-center mx-auto mb-6">
                    <i class="fas fa-search text-white text-2xl"></i>
                </div>
                <h3 class="text-xl font-semibold mb-4">Akıllı Arama</h3>
                <p class="text-secondary mb-6">
                    AI destekli arama sistemi ile istediğiniz ürünleri anında bulun.
                </p>
                <a href="/ai-ecommerce/products/" class="btn btn-success">
                    Keşfet
                </a>
            </div>

            <div class="card text-center p-8 hover:shadow-xl transition-shadow">
                <div class="w-16 h-16 bg-warning rounded-full flex items-center justify-center mx-auto mb-6">
                    <i class="fas fa-heart text-white text-2xl"></i>
                </div>
                <h3 class="text-xl font-semibold mb-4">Kişisel Öneriler</h3>
                <p class="text-secondary mb-6">
                    Alışveriş geçmişinize göre size özel ürün önerileri alın.
                </p>
                <a href="/ai-ecommerce/user/recommendations.php" class="btn btn-warning">
                    Önerilerim
                </a>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="py-16 bg-primary text-white">
    <div class="container text-center">
        <h2 class="text-3xl font-bold mb-4">AI Commerce Pro'ya Katılın</h2>
        <p class="text-xl opacity-90 mb-8 max-w-2xl mx-auto">
            Geleceğin alışveriş deneyimini bugünden yaşamaya başlayın. 
            AI destekli e-ticaret platformumuzla tanışın.
        </p>
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="/ai-ecommerce/products/" class="btn btn-white text-primary hover:bg-gray-100">
                <i class="fas fa-shopping-bag mr-2"></i>
                Alışverişe Başla
            </a>
            <a href="/ai-ecommerce/portfolio/" class="btn btn-secondary">
                <i class="fas fa-briefcase mr-2"></i>
                Portföyümüzü İncele
            </a>
        </div>
    </div>
</section>

<script>
// Sepete ekleme fonksiyonu
function addToCart(productId) {
    // AJAX ile sepete ekleme işlemi
    fetch('/ai-ecommerce/api/cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
            action: 'add',
            product_id: productId,
            quantity: 1
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Ürün sepete eklendi!');
            // Sepet sayısını güncelle
            updateCartCount();
        } else {
            alert('Hata: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Bir hata oluştu!');
    });
}

// Sayfa yüklenme animasyonları
document.addEventListener('DOMContentLoaded', function() {
    // Fade-in animasyonları
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('fade-in');
            }
        });
    }, observerOptions);

    // Tüm kartları gözlemle
    document.querySelectorAll('.product-card, .card').forEach(card => {
        observer.observe(card);
    });
});
</script>

<?php include 'includes/footer.php'; ?>