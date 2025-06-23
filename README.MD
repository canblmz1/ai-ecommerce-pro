# 🛍️ AI Commerce Pro

> Modern AI destekli e-ticaret platformu - Gelişmiş yapay zeka alışveriş asistanı ile

[![PHP](https://img.shields.io/badge/PHP-8.0+-777BB4?style=flat&logo=php&logoColor=white)](https://php.net)
[![MySQL](https://img.shields.io/badge/MySQL-8.0+-4479A1?style=flat&logo=mysql&logoColor=white)](https://mysql.com)
[![JavaScript](https://img.shields.io/badge/JavaScript-ES6+-F7DF1E?style=flat&logo=javascript&logoColor=black)](https://javascript.info)
[![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3+-7952B3?style=flat&logo=bootstrap&logoColor=white)](https://getbootstrap.com)

---

## ✨ Özellikler

### 🤖 **AI Alışveriş Asistanı**
- **Akıllı Ürün Önerileri** - Kullanıcı tercihlerine göre kişiselleştirilmiş öneriler
- **Gerçek Zamanlı Chat** - Anlık müşteri desteği ve ürün danışmanlığı  
- **Sipariş Takibi** - AI destekli sipariş durumu sorgulama
- **Kampanya Bildirimleri** - Otomatik indirim ve fırsat bildirimleri

### 🛒 **E-Ticaret Özellikleri**
- **Gelişmiş Ürün Kataloğu** - Kategori bazlı filtreleme ve arama
- **Akıllı Sepet Sistemi** - Ajax tabanlı sepet yönetimi
- **Güvenli Ödeme** - Çoklu ödeme yöntemi desteği
- **Sipariş Yönetimi** - Detaylı sipariş takip sistemi
- **Kullanıcı Profilleri** - Kişiselleştirilmiş kullanıcı deneyimi

### 🎨 **Modern Tasarım**
- **Responsive Design** - Mobil uyumlu responsive tasarım
- **Progressive Web App** - PWA desteği
- **Dark Mode** - Koyu tema desteği
- **Animasyonlar** - Akıcı CSS3 animasyonları

### ⚡ **Performans**
- **Cache Sistemi** - Redis/Memcached entegrasyonu
- **CDN Desteği** - Hızlı içerik dağıtımı
- **Lazy Loading** - Sayfa yükleme optimizasyonu
- **Image Optimization** - Otomatik görsel optimizasyonu

---

## 🚀 Kurulum

### Gereksinimler
- **PHP** 8.0 veya üzeri
- **MySQL** 8.0 veya üzeri  
- **Apache/Nginx** web sunucusu
- **Composer** (PHP paket yöneticisi)
- **Node.js** (frontend asset'leri için)

### Hızlı Kurulum

```bash
# Repository'yi klonla
git clone https://github.com/canblmz1/ai-ecommerce-pro.git
cd ai-ecommerce-pro

# Composer bağımlılıklarını yükle  
composer install

# NPM paketlerini yükle
npm install

# Veritabanı konfigürasyonu
cp config/database.example.php config/database.php

# Veritabanını oluştur
mysql -u root -p < database/ai_ecommerce.sql

# Asset'leri derle
npm run build

# Sunucuyu başlat
php -S localhost:8000
