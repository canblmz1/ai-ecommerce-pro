    <!-- Footer -->
    <footer class="bg-gray-900 text-white mt-20">
        <div class="container py-12">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <!-- Logo ve açıklama -->
                <div class="col-span-1 md:col-span-2">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 bg-primary rounded-full flex items-center justify-center">
                            <i class="fas fa-robot text-white"></i>
                        </div>
                        <h3 class="text-xl font-bold"><?php echo getSiteSetting('site_name', 'AI Commerce Pro'); ?></h3>
                    </div>
                    <p class="text-gray-400 mb-4 max-w-md">
                        <?php echo getSiteSetting('site_description', 'AI destekli e-ticaret ve teknoloji çözümleri ile geleceği bugünden yaşayın.'); ?>
                    </p>
                    <div class="flex gap-4">
                        <a href="#" class="w-10 h-10 bg-gray-800 rounded-full flex items-center justify-center hover:bg-primary transition-colors">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="#" class="w-10 h-10 bg-gray-800 rounded-full flex items-center justify-center hover:bg-primary transition-colors">
                            <i class="fab fa-facebook"></i>
                        </a>
                        <a href="#" class="w-10 h-10 bg-gray-800 rounded-full flex items-center justify-center hover:bg-primary transition-colors">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="#" class="w-10 h-10 bg-gray-800 rounded-full flex items-center justify-center hover:bg-primary transition-colors">
                            <i class="fab fa-linkedin"></i>
                        </a>
                    </div>
                </div>

                <!-- Hızlı linkler -->
                <div>
                    <h4 class="font-semibold mb-4">Hızlı Linkler</h4>
                    <ul class="space-y-2 text-gray-400">
                        <li><a href="/ai-ecommerce/" class="hover:text-white transition-colors">Ana Sayfa</a></li>
                        <li><a href="/ai-ecommerce/products/" class="hover:text-white transition-colors">Ürünler</a></li>
                        <li><a href="/ai-ecommerce/ai-assistant/" class="hover:text-white transition-colors">AI Asistan</a></li>
                        <li><a href="/ai-ecommerce/portfolio/" class="hover:text-white transition-colors">Portföy</a></li>
                        <li><a href="/ai-ecommerce/blog/" class="hover:text-white transition-colors">Blog</a></li>
                    </ul>
                </div>

                <!-- İletişim -->
                <div>
                    <h4 class="font-semibold mb-4">İletişim</h4>
                    <ul class="space-y-2 text-gray-400">
                        <li class="flex items-center gap-2">
                            <i class="fas fa-envelope text-primary"></i>
                            <?php echo getSiteSetting('contact_email', 'info@aicommerce.com'); ?>
                        </li>
                        <li class="flex items-center gap-2">
                            <i class="fas fa-phone text-primary"></i>
                            <?php echo getSiteSetting('contact_phone', '+90 555 123 4567'); ?>
                        </li>
                        <li class="flex items-center gap-2">
                            <i class="fas fa-map-marker-alt text-primary"></i>
                            İstanbul, Türkiye
                        </li>
                    </ul>
                </div>
            </div>

            <hr class="border-gray-800 my-8">

            <div class="flex flex-col md:flex-row justify-between items-center">
                <p class="text-gray-400 text-sm">
                    &copy; <?php echo date('Y'); ?> <?php echo getSiteSetting('site_name', 'AI Commerce Pro'); ?>. Tüm hakları saklıdır.
                </p>
                <div class="flex gap-6 mt-4 md:mt-0">
                    <a href="#" class="text-gray-400 hover:text-white text-sm transition-colors">Gizlilik Politikası</a>
                    <a href="#" class="text-gray-400 hover:text-white text-sm transition-colors">Kullanım Şartları</a>
                    <a href="#" class="text-gray-400 hover:text-white text-sm transition-colors">Çerez Politikası</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- JavaScript -->
    <script src="/ai-ecommerce/assets/js/main.js"></script>
    <?php if (isset($extra_js)): ?>
        <?php foreach ($extra_js as $js): ?>
            <script src="<?php echo $js; ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>