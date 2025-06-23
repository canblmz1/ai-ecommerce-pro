<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

requireAdmin();

$page_title = 'Admin Dashboard';

// Dashboard istatistikleri
try {
    // Temel sayılar
    $stats_query = "
        SELECT 
            (SELECT COUNT(*) FROM users WHERE user_type = 'customer') as total_customers,
            (SELECT COUNT(*) FROM products WHERE is_active = 1) as total_products,
            (SELECT COUNT(*) FROM orders) as total_orders,
            (SELECT SUM(total_amount) FROM orders WHERE status != 'cancelled') as total_revenue,
            (SELECT COUNT(*) FROM orders WHERE DATE(created_at) = CURDATE()) as today_orders,
            (SELECT COUNT(*) FROM users WHERE DATE(created_at) = CURDATE()) as today_registrations
    ";
    $stats = $pdo->query($stats_query)->fetch();
    
    // Son siparişler
    $recent_orders_query = "
        SELECT o.*, u.full_name, u.email
        FROM orders o
        JOIN users u ON o.user_id = u.id
        ORDER BY o.created_at DESC
        LIMIT 5
    ";
    $recent_orders = $pdo->query($recent_orders_query)->fetchAll();
    
    // En çok satan ürünler
    $top_products_query = "
        SELECT p.name, p.main_image, SUM(oi.quantity) as total_sold, 
               SUM(oi.total_price) as total_revenue
        FROM products p
        JOIN order_items oi ON p.id = oi.product_id
        JOIN orders o ON oi.order_id = o.id
        WHERE o.status != 'cancelled'
        GROUP BY p.id
        ORDER BY total_sold DESC
        LIMIT 5
    ";
    $top_products = $pdo->query($top_products_query)->fetchAll();
    
    // Aylık satış grafiği için veri
    $monthly_sales_query = "
        SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as month,
            COUNT(*) as order_count,
            SUM(total_amount) as revenue
        FROM orders 
        WHERE status != 'cancelled' 
          AND created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY month ASC
    ";
    $monthly_sales = $pdo->query($monthly_sales_query)->fetchAll();
    
} catch (PDOException $e) {
    logError("Dashboard stats error: " . $e->getMessage());
    $stats = ['total_customers' => 0, 'total_products' => 0, 'total_orders' => 0, 'total_revenue' => 0];
    $recent_orders = [];
    $top_products = [];
    $monthly_sales = [];
}

include 'includes/admin_header.php';
?>

<!-- Dashboard Content -->
<div class="space-y-8">
    <!-- Welcome Message -->
    <div class="bg-gradient-to-r from-blue-500 to-purple-600 rounded-lg text-white p-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold mb-2">
                    Hoş Geldin, <?php echo htmlspecialchars($_SESSION['full_name'] ?: $_SESSION['username']); ?>! 👋
                </h1>
                <p class="text-blue-100">
                    AI Commerce Pro yönetim paneline hoş geldiniz. 
                    Son <?php echo date('d.m.Y'); ?> itibariyle işletmenizin durumu.
                </p>
            </div>
            <div class="hidden md:block">
                <i class="fas fa-chart-line text-6xl opacity-50"></i>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Toplam Müşteri -->
        <div class="card p-6">
            <div class="flex items-center">
                <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mr-4">
                    <i class="fas fa-users text-blue-600 text-xl"></i>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-600">Toplam Müşteri</p>
                    <p class="text-2xl font-bold text-gray-900"><?php echo number_format($stats['total_customers']); ?></p>
                    <p class="text-xs text-green-600">
                        <i class="fas fa-arrow-up mr-1"></i>
                        +<?php echo $stats['today_registrations']; ?> bugün
                    </p>
                </div>
            </div>
        </div>

        <!-- Toplam Ürün -->
        <div class="card p-6">
            <div class="flex items-center">
                <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mr-4">
                    <i class="fas fa-box text-green-600 text-xl"></i>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-600">Aktif Ürün</p>
                    <p class="text-2xl font-bold text-gray-900"><?php echo number_format($stats['total_products']); ?></p>
                    <p class="text-xs text-gray-500">
                        <i class="fas fa-tag mr-1"></i>
                        Katalogda
                    </p>
                </div>
            </div>
        </div>

        <!-- Toplam Sipariş -->
        <div class="card p-6">
            <div class="flex items-center">
                <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center mr-4">
                    <i class="fas fa-shopping-cart text-yellow-600 text-xl"></i>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-600">Toplam Sipariş</p>
                    <p class="text-2xl font-bold text-gray-900"><?php echo number_format($stats['total_orders']); ?></p>
                    <p class="text-xs text-green-600">
                        <i class="fas fa-arrow-up mr-1"></i>
                        +<?php echo $stats['today_orders']; ?> bugün
                    </p>
                </div>
            </div>
        </div>

        <!-- Toplam Gelir -->
        <div class="card p-6">
            <div class="flex items-center">
                <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center mr-4">
                    <i class="fas fa-lira-sign text-purple-600 text-xl"></i>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-600">Toplam Gelir</p>
                    <p class="text-2xl font-bold text-gray-900">
                        <?php echo formatPrice($stats['total_revenue'] ?: 0); ?>
                    </p>
                    <p class="text-xs text-green-600">
                        <i class="fas fa-chart-up mr-1"></i>
                        Bu ay %12 artış
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts and Tables Row -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Sales Chart -->
        <div class="card p-6">
            <h3 class="text-lg font-semibold mb-6 flex items-center">
                <i class="fas fa-chart-area mr-2 text-blue-600"></i>
                Aylık Satış Grafiği
            </h3>
            <div class="h-64 flex items-center justify-center bg-gray-50 rounded-lg">
                <canvas id="salesChart" width="400" height="200"></canvas>
            </div>
        </div>

        <!-- Top Products -->
        <div class="card p-6">
            <h3 class="text-lg font-semibold mb-6 flex items-center">
                <i class="fas fa-star mr-2 text-yellow-600"></i>
                En Çok Satan Ürünler
            </h3>
            <div class="space-y-4">
                <?php if (!empty($top_products)): ?>
                    <?php foreach ($top_products as $index => $product): ?>
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                            <div class="flex items-center">
                                <span class="w-6 h-6 bg-blue-500 text-white rounded-full flex items-center justify-center text-xs font-bold mr-3">
                                    <?php echo $index + 1; ?>
                                </span>
                                <?php if ($product['main_image']): ?>
                                    <img src="/ai-ecommerce/assets/images/products/<?php echo htmlspecialchars($product['main_image']); ?>" 
                                         alt="<?php echo htmlspecialchars($product['name']); ?>"
                                         class="w-8 h-8 object-cover rounded mr-3">
                                <?php endif; ?>
                                <div>
                                    <p class="font-medium text-sm"><?php echo htmlspecialchars($product['name']); ?></p>
                                    <p class="text-xs text-gray-500"><?php echo $product['total_sold']; ?> adet satıldı</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="font-bold text-sm text-green-600">
                                    <?php echo formatPrice($product['total_revenue']); ?>
                                </p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center py-8 text-gray-500">
                        <i class="fas fa-box-open text-4xl mb-4"></i>
                        <p>Henüz satış verisi yok</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Recent Orders -->
    <div class="card p-6">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-semibold flex items-center">
                <i class="fas fa-clock mr-2 text-green-600"></i>
                Son Siparişler
            </h3>
            <a href="orders.php" class="btn btn-primary btn-sm">
                <i class="fas fa-eye mr-1"></i>
                Tümünü Görüntüle
            </a>
        </div>
        
        <?php if (!empty($recent_orders)): ?>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-gray-200">
                            <th class="text-left py-3 px-4 font-medium text-gray-700">Sipariş No</th>
                            <th class="text-left py-3 px-4 font-medium text-gray-700">Müşteri</th>
                            <th class="text-left py-3 px-4 font-medium text-gray-700">Tutar</th>
                            <th class="text-left py-3 px-4 font-medium text-gray-700">Durum</th>
                            <th class="text-left py-3 px-4 font-medium text-gray-700">Tarih</th>
                            <th class="text-left py-3 px-4 font-medium text-gray-700">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_orders as $order): ?>
                            <tr class="border-b border-gray-100 hover:bg-gray-50">
                                <td class="py-3 px-4">
                                    <span class="font-mono text-sm">#<?php echo htmlspecialchars($order['order_number']); ?></span>
                                </td>
                                <td class="py-3 px-4">
                                    <div>
                                        <p class="font-medium text-sm"><?php echo htmlspecialchars($order['full_name']); ?></p>
                                        <p class="text-xs text-gray-500"><?php echo htmlspecialchars($order['email']); ?></p>
                                    </div>
                                </td>
                                <td class="py-3 px-4 font-bold text-green-600">
                                    <?php echo formatPrice($order['total_amount']); ?>
                                </td>
                                <td class="py-3 px-4">
                                    <?php
                                    $status_colors = [
                                        'pending' => 'bg-yellow-100 text-yellow-800',
                                        'confirmed' => 'bg-blue-100 text-blue-800',
                                        'shipped' => 'bg-purple-100 text-purple-800',
                                        'delivered' => 'bg-green-100 text-green-800',
                                        'cancelled' => 'bg-red-100 text-red-800'
                                    ];
                                    $status_names = [
                                        'pending' => 'Bekliyor',
                                        'confirmed' => 'Onaylandı',
                                        'shipped' => 'Kargoda',
                                        'delivered' => 'Teslim Edildi',
                                        'cancelled' => 'İptal'
                                    ];
                                    ?>
                                    <span class="px-2 py-1 rounded-full text-xs font-medium <?php echo $status_colors[$order['status']] ?? 'bg-gray-100 text-gray-800'; ?>">
                                        <?php echo $status_names[$order['status']] ?? $order['status']; ?>
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-sm text-gray-600">
                                    <?php echo formatDate($order['created_at']); ?>
                                </td>
                                <td class="py-3 px-4">
                                    <div class="flex gap-2">
                                        <button class="btn btn-secondary btn-sm" onclick="viewOrder(<?php echo $order['id']; ?>)">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn btn-primary btn-sm" onclick="editOrder(<?php echo $order['id']; ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-12 text-gray-500">
                <i class="fas fa-shopping-cart text-4xl mb-4"></i>
                <h4 class="text-lg font-semibold mb-2">Henüz sipariş yok</h4>
                <p>İlk siparişinizi bekliyoruz!</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Quick Actions -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <a href="products/add.php" class="card p-6 hover:shadow-lg transition-shadow text-center group">
            <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mx-auto mb-4 group-hover:bg-blue-200 transition-colors">
                <i class="fas fa-plus text-blue-600 text-xl"></i>
            </div>
            <h4 class="font-semibold mb-2">Ürün Ekle</h4>
            <p class="text-sm text-gray-600">Yeni ürün ekleyin</p>
        </a>

        <a href="orders.php" class="card p-6 hover:shadow-lg transition-shadow text-center group">
            <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mx-auto mb-4 group-hover:bg-green-200 transition-colors">
                <i class="fas fa-list text-green-600 text-xl"></i>
            </div>
            <h4 class="font-semibold mb-2">Siparişleri Yönet</h4>
            <p class="text-sm text-gray-600">Tüm siparişleri görüntüle</p>
        </a>

        <a href="users.php" class="card p-6 hover:shadow-lg transition-shadow text-center group">
            <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center mx-auto mb-4 group-hover:bg-purple-200 transition-colors">
                <i class="fas fa-users text-purple-600 text-xl"></i>
            </div>
            <h4 class="font-semibold mb-2">Kullanıcılar</h4>
            <p class="text-sm text-gray-600">Müşterileri yönet</p>
        </a>

        <a href="settings.php" class="card p-6 hover:shadow-lg transition-shadow text-center group">
            <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center mx-auto mb-4 group-hover:bg-yellow-200 transition-colors">
                <i class="fas fa-cog text-yellow-600 text-xl"></i>
            </div>
            <h4 class="font-semibold mb-2">Ayarlar</h4>
            <p class="text-sm text-gray-600">Site ayarlarını düzenle</p>
        </a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Sales Chart
const ctx = document.getElementById('salesChart').getContext('2d');
const salesData = <?php echo json_encode($monthly_sales); ?>;

const chart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: salesData.map(item => {
            const date = new Date(item.month + '-01');
            return date.toLocaleDateString('tr-TR', { month: 'short', year: 'numeric' });
        }),
        datasets: [{
            label: 'Sipariş Sayısı',
            data: salesData.map(item => item.order_count),
            borderColor: 'rgb(59, 130, 246)',
            backgroundColor: 'rgba(59, 130, 246, 0.1)',
            tension: 0.4,
            fill: true
        }, {
            label: 'Gelir (₺)',
            data: salesData.map(item => item.revenue),
            borderColor: 'rgb(16, 185, 129)',
            backgroundColor: 'rgba(16, 185, 129, 0.1)',
            tension: 0.4,
            fill: true,
            yAxisID: 'y1'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'top',
            }
        },
        scales: {
            y: {
                type: 'linear',
                display: true,
                position: 'left',
            },
            y1: {
                type: 'linear',
                display: true,
                position: 'right',
                grid: {
                    drawOnChartArea: false,
                },
            }
        }
    }
});

// Quick actions
function viewOrder(orderId) {
    window.location.href = `orders/view.php?id=${orderId}`;
}

function editOrder(orderId) {
    window.location.href = `orders/edit.php?id=${orderId}`;
}

// Real-time updates (her 30 saniyede bir)
setInterval(function() {
    // AJAX ile güncel istatistikleri al
    fetch('api/dashboard-stats.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // İstatistikleri güncelle
                updateStats(data.stats);
            }
        })
        .catch(error => console.error('Stats update error:', error));
}, 30000);

function updateStats(stats) {
    // DOM güncellemeleri burada yapılabilir
    console.log('Stats updated:', stats);
}

// Dashboard animations
document.addEventListener('DOMContentLoaded', function() {
    // Kartları sırayla animasyonla göster
    const cards = document.querySelectorAll('.card');
    cards.forEach((card, index) => {
        setTimeout(() => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            card.style.transition = 'all 0.3s ease-out';
            
            setTimeout(() => {
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, 50);
        }, index * 100);
    });
});
</script>

<?php include 'includes/admin_footer.php'; ?>