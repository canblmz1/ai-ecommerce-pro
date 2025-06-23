/**
 * AI Commerce Pro - Main JavaScript File
 * Modern, responsive and interactive features
 */

class AiCommerce {
    constructor() {
        this.init();
        this.bindEvents();
        this.loadComponents();
    }

    init() {
        console.log('🚀 AI Commerce Pro initialized');
        this.showLoadingComplete();
        this.initTheme();
        this.initAnimations();
    }

    // Loading complete göstergesi
    showLoadingComplete() {
        document.body.classList.add('loaded');
        
        // Sayfa yüklenme animasyonu
        setTimeout(() => {
            const loader = document.querySelector('.page-loader');
            if (loader) {
                loader.style.opacity = '0';
                setTimeout(() => loader.remove(), 300);
            }
        }, 500);
    }

    // Tema sistemi
    initTheme() {
        const savedTheme = localStorage.getItem('theme') || 'light';
        document.documentElement.setAttribute('data-theme', savedTheme);
        
        const themeToggle = document.querySelector('#themeToggle');
        if (themeToggle) {
            themeToggle.addEventListener('click', () => this.toggleTheme());
        }
    }

    toggleTheme() {
        const currentTheme = document.documentElement.getAttribute('data-theme');
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        
        document.documentElement.setAttribute('data-theme', newTheme);
        localStorage.setItem('theme', newTheme);
        
        // Smooth transition
        document.body.style.transition = 'background-color 0.3s ease, color 0.3s ease';
        setTimeout(() => {
            document.body.style.transition = '';
        }, 300);
    }

    // Animasyon sistemi
    initAnimations() {
        // Intersection Observer for scroll animations
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate-in');
                    observer.unobserve(entry.target);
                }
            });
        }, observerOptions);

        // Observe elements with animation classes
        document.querySelectorAll('.fade-in, .slide-up, .scale-in').forEach(el => {
            observer.observe(el);
        });

        // Parallax effect for hero sections
        this.initParallax();
    }

    initParallax() {
        const parallaxElements = document.querySelectorAll('.parallax');
        
        window.addEventListener('scroll', () => {
            const scrolled = window.pageYOffset;
            
            parallaxElements.forEach(element => {
                const rate = scrolled * -0.5;
                element.style.transform = `translateY(${rate}px)`;
            });
        });
    }

    // Event binding
    bindEvents() {
        // Mobile menu toggle
        this.initMobileMenu();
        
        // Search functionality
        this.initSearch();
        
        // Cart functionality
        this.initCart();
        
        // Form enhancements
        this.initForms();
        
        // Smooth scrolling
        this.initSmoothScroll();
        
        // Tooltips
        this.initTooltips();
        
        // Modal system
        this.initModals();
    }

    // Mobile menu
    initMobileMenu() {
        const menuBtn = document.querySelector('#mobileMenuBtn');
        const menu = document.querySelector('#navbarNav');
        
        if (menuBtn && menu) {
            menuBtn.addEventListener('click', () => {
                menu.classList.toggle('active');
                menuBtn.classList.toggle('active');
                
                // Hamburger animation
                const icon = menuBtn.querySelector('i');
                if (menu.classList.contains('active')) {
                    icon.className = 'fas fa-times';
                } else {
                    icon.className = 'fas fa-bars';
                }
            });
        }
    }

    // Search functionality
    initSearch() {
        const searchInput = document.querySelector('#searchInput');
        const searchResults = document.querySelector('#searchResults');
        
        if (searchInput) {
            let searchTimeout;
            
            searchInput.addEventListener('input', (e) => {
                clearTimeout(searchTimeout);
                const query = e.target.value.trim();
                
                if (query.length < 2) {
                    this.hideSearchResults();
                    return;
                }
                
                searchTimeout = setTimeout(() => {
                    this.performSearch(query);
                }, 300);
            });
            
            // Close search on outside click
            document.addEventListener('click', (e) => {
                if (!e.target.closest('.search-container')) {
                    this.hideSearchResults();
                }
            });
        }
    }

    async performSearch(query) {
        try {
            const response = await fetch('/ai-ecommerce/api/search.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ query: query })
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.showSearchResults(data.results);
            }
        } catch (error) {
            console.error('Search error:', error);
        }
    }

    showSearchResults(results) {
        const container = document.querySelector('#searchResults');
        if (!container) return;
        
        container.innerHTML = '';
        
        if (results.length === 0) {
            container.innerHTML = '<div class="p-4 text-center text-gray-500">Sonuç bulunamadı</div>';
        } else {
            results.forEach(item => {
                const div = document.createElement('div');
                div.className = 'search-result-item p-3 hover:bg-gray-50 cursor-pointer';
                div.innerHTML = `
                    <div class="flex items-center gap-3">
                        <img src="/ai-ecommerce/assets/images/products/${item.image || 'placeholder.jpg'}" 
                             alt="${item.name}" class="w-12 h-12 object-cover rounded">
                        <div>
                            <h4 class="font-medium">${item.name}</h4>
                            <p class="text-sm text-gray-500">${item.price}</p>
                        </div>
                    </div>
                `;
                
                div.addEventListener('click', () => {
                    window.location.href = `/ai-ecommerce/products/detail.php?slug=${item.slug}`;
                });
                
                container.appendChild(div);
            });
        }
        
        container.classList.add('show');
    }

    hideSearchResults() {
        const container = document.querySelector('#searchResults');
        if (container) {
            container.classList.remove('show');
        }
    }

    // Cart functionality
    initCart() {
        // Cart count update
        this.updateCartCount();
        
        // Add to cart buttons
        document.addEventListener('click', (e) => {
            if (e.target.closest('.add-to-cart')) {
                e.preventDefault();
                const btn = e.target.closest('.add-to-cart');
                const productId = btn.dataset.productId;
                const quantity = btn.dataset.quantity || 1;
                
                this.addToCart(productId, quantity);
            }
            
            if (e.target.closest('.remove-from-cart')) {
                e.preventDefault();
                const btn = e.target.closest('.remove-from-cart');
                const itemId = btn.dataset.itemId;
                
                this.removeFromCart(itemId);
            }
        });
    }

    async addToCart(productId, quantity = 1) {
        try {
            const response = await fetch('/ai-ecommerce/api/cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    action: 'add',
                    product_id: productId,
                    quantity: parseInt(quantity)
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.showNotification('Ürün sepete eklendi!', 'success');
                this.updateCartCount();
                this.updateCartDropdown();
            } else {
                this.showNotification(data.message || 'Bir hata oluştu!', 'error');
            }
        } catch (error) {
            console.error('Cart error:', error);
            this.showNotification('Bir hata oluştu!', 'error');
        }
    }

    async removeFromCart(itemId) {
        try {
            const response = await fetch('/ai-ecommerce/api/cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'remove',
                    item_id: itemId
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.showNotification('Ürün sepetten çıkarıldı!', 'success');
                this.updateCartCount();
                this.updateCartDropdown();
            }
        } catch (error) {
            console.error('Cart error:', error);
        }
    }

    async updateCartCount() {
        try {
            const response = await fetch('/ai-ecommerce/api/cart.php?action=count');
            const data = await response.json();
            
            const countElement = document.querySelector('#cartCount');
            if (countElement && data.count !== undefined) {
                countElement.textContent = data.count;
                countElement.style.display = data.count > 0 ? 'block' : 'none';
            }
        } catch (error) {
            console.error('Cart count error:', error);
        }
    }

    // Form enhancements
    initForms() {
        // Floating labels
        document.querySelectorAll('.form-input').forEach(input => {
            this.initFloatingLabel(input);
        });
        
        // Form validation
        document.querySelectorAll('form').forEach(form => {
            this.initFormValidation(form);
        });
        
        // File upload preview
        document.querySelectorAll('input[type="file"]').forEach(input => {
            this.initFilePreview(input);
        });
    }

    initFloatingLabel(input) {
        const handleFloat = () => {
            const label = input.previousElementSibling;
            if (label && label.tagName === 'LABEL') {
                if (input.value || input === document.activeElement) {
                    label.classList.add('floating');
                } else {
                    label.classList.remove('floating');
                }
            }
        };
        
        input.addEventListener('focus', handleFloat);
        input.addEventListener('blur', handleFloat);
        input.addEventListener('input', handleFloat);
        
        // Initial state
        handleFloat();
    }

    initFormValidation(form) {
        form.addEventListener('submit', (e) => {
            if (!this.validateForm(form)) {
                e.preventDefault();
            }
        });
    }

    validateForm(form) {
        let isValid = true;
        const inputs = form.querySelectorAll('input[required], textarea[required], select[required]');
        
        inputs.forEach(input => {
            if (!input.value.trim()) {
                this.showFieldError(input, 'Bu alan zorunludur');
                isValid = false;
            } else {
                this.clearFieldError(input);
            }
        });
        
        return isValid;
    }

    showFieldError(input, message) {
        input.classList.add('error');
        
        let errorEl = input.parentNode.querySelector('.field-error');
        if (!errorEl) {
            errorEl = document.createElement('div');
            errorEl.className = 'field-error text-error text-xs mt-1';
            input.parentNode.appendChild(errorEl);
        }
        errorEl.textContent = message;
    }

    clearFieldError(input) {
        input.classList.remove('error');
        const errorEl = input.parentNode.querySelector('.field-error');
        if (errorEl) {
            errorEl.remove();
        }
    }

    // Smooth scrolling
    initSmoothScroll() {
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', (e) => {
                e.preventDefault();
                const target = document.querySelector(anchor.getAttribute('href'));
                
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    }

    // Tooltips
    initTooltips() {
        document.querySelectorAll('[data-tooltip]').forEach(element => {
            element.addEventListener('mouseenter', this.showTooltip.bind(this));
            element.addEventListener('mouseleave', this.hideTooltip.bind(this));
        });
    }

    showTooltip(e) {
        const text = e.target.getAttribute('data-tooltip');
        if (!text) return;
        
        const tooltip = document.createElement('div');
        tooltip.className = 'tooltip';
        tooltip.textContent = text;
        document.body.appendChild(tooltip);
        
        const rect = e.target.getBoundingClientRect();
        tooltip.style.left = rect.left + (rect.width / 2) - (tooltip.offsetWidth / 2) + 'px';
        tooltip.style.top = rect.top - tooltip.offsetHeight - 8 + 'px';
        
        setTimeout(() => tooltip.classList.add('show'), 10);
    }

    hideTooltip() {
        const tooltip = document.querySelector('.tooltip');
        if (tooltip) {
            tooltip.remove();
        }
    }

    // Modal system
    initModals() {
        document.addEventListener('click', (e) => {
            if (e.target.hasAttribute('data-modal')) {
                e.preventDefault();
                const modalId = e.target.getAttribute('data-modal');
                this.openModal(modalId);
            }
            
            if (e.target.classList.contains('modal-close') || e.target.classList.contains('modal-overlay')) {
                this.closeModal();
            }
        });
        
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.closeModal();
            }
        });
    }

    openModal(modalId) {
        const modal = document.querySelector(`#${modalId}`);
        if (modal) {
            modal.classList.add('show');
            document.body.classList.add('modal-open');
        }
    }

    closeModal() {
        document.querySelectorAll('.modal.show').forEach(modal => {
            modal.classList.remove('show');
        });
        document.body.classList.remove('modal-open');
    }

    // Notification system
    showNotification(message, type = 'info', duration = 5000) {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        
        const icon = this.getNotificationIcon(type);
        notification.innerHTML = `
            <div class="flex items-center gap-3">
                <i class="${icon}"></i>
                <span>${message}</span>
                <button class="ml-auto text-lg" onclick="this.parentElement.parentElement.remove()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;
        
        const container = this.getNotificationContainer();
        container.appendChild(notification);
        
        // Auto remove
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, duration);
        
        // Animate in
        setTimeout(() => notification.classList.add('show'), 10);
    }

    getNotificationIcon(type) {
        const icons = {
            success: 'fas fa-check-circle text-success',
            error: 'fas fa-exclamation-circle text-error',
            warning: 'fas fa-exclamation-triangle text-warning',
            info: 'fas fa-info-circle text-primary'
        };
        return icons[type] || icons.info;
    }

    getNotificationContainer() {
        let container = document.querySelector('#notificationContainer');
        if (!container) {
            container = document.createElement('div');
            container.id = 'notificationContainer';
            container.className = 'notification-container';
            document.body.appendChild(container);
        }
        return container;
    }

    // Component loading
    loadComponents() {
        this.loadLazyImages();
        this.initCounters();
        this.initProgressBars();
    }

    // Lazy loading images
    loadLazyImages() {
        const imageObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.classList.remove('lazy');
                    imageObserver.unobserve(img);
                }
            });
        });
        
        document.querySelectorAll('img[data-src]').forEach(img => {
            imageObserver.observe(img);
        });
    }

    // Counter animation
    initCounters() {
        const counters = document.querySelectorAll('.counter');
        
        const counterObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    this.animateCounter(entry.target);
                    counterObserver.unobserve(entry.target);
                }
            });
        });
        
        counters.forEach(counter => counterObserver.observe(counter));
    }

    animateCounter(element) {
        const target = parseInt(element.getAttribute('data-count'));
        const duration = 2000;
        const start = performance.now();
        
        const updateCounter = (currentTime) => {
            const elapsed = currentTime - start;
            const progress = Math.min(elapsed / duration, 1);
            const current = Math.floor(progress * target);
            
            element.textContent = current.toLocaleString();
            
            if (progress < 1) {
                requestAnimationFrame(updateCounter);
            }
        };
        
        requestAnimationFrame(updateCounter);
    }

    // Progress bars
    initProgressBars() {
        const progressBars = document.querySelectorAll('.progress-bar');
        
        const progressObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const bar = entry.target.querySelector('.progress-fill');
                    const percentage = bar.getAttribute('data-percentage');
                    bar.style.width = percentage + '%';
                    progressObserver.unobserve(entry.target);
                }
            });
        });
        
        progressBars.forEach(bar => progressObserver.observe(bar));
    }

    // Utility methods
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    throttle(func, limit) {
        let inThrottle;
        return function() {
            const args = arguments;
            const context = this;
            if (!inThrottle) {
                func.apply(context, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    }

    // API helper
    async apiCall(url, options = {}) {
        try {
            const response = await fetch(url, {
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    ...options.headers
                },
                ...options
            });
            
            return await response.json();
        } catch (error) {
            console.error('API call error:', error);
            throw error;
        }
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    window.aiCommerce = new AiCommerce();
});

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = AiCommerce;
}