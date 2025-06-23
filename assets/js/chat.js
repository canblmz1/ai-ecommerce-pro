class AIChat {
    constructor() {
        this.sessionId = this.generateSessionId();
        this.isTyping = false;
        this.init();
    }

    init() {
        this.bindEvents();
        this.showWelcomeMessage();
    }

    bindEvents() {
        // Mesaj gönderme butonu
        const sendBtn = document.getElementById('sendMessage');
        const messageInput = document.getElementById('messageInput');
        
        if (sendBtn) {
            sendBtn.addEventListener('click', () => this.sendMessage());
        }
        
        if (messageInput) {
            messageInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    this.sendMessage();
                }
            });
            
            // Auto-resize textarea
            messageInput.addEventListener('input', () => {
                messageInput.style.height = 'auto';
                messageInput.style.height = messageInput.scrollHeight + 'px';
            });
        }

        // Quick action buttons
        document.querySelectorAll('.quick-action').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const action = e.target.dataset.action;
                this.handleQuickAction(action);
            });
        });
    }

    generateSessionId() {
        return 'session_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
    }

    showWelcomeMessage() {
        const welcomeMessage = `
            Merhaba canblmz1! 👋
            
            AI Commerce asistanınızım. Size nasıl yardımcı olabilirim?
            
            • Ürün önerileri
            • Fiyat karşılaştırması  
            • Sipariş takibi
            • Teknik destek
        `;
        
        this.addMessage(welcomeMessage, 'ai', true);
    }

    async sendMessage() {
        const messageInput = document.getElementById('messageInput');
        const message = messageInput.value.trim();
        
        if (!message || this.isTyping) {
            return;
        }
        
        // Kullanıcı mesajını göster
        this.addMessage(message, 'user');
        messageInput.value = '';
        messageInput.style.height = 'auto';
        
        // Typing indicator göster
        this.showTypingIndicator();
        
        try {
            const response = await fetch('/ai-ecommerce/ai-assistant/api/chat.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    message: message,
                    session_id: this.sessionId
                })
            });
            
            const data = await response.json();
            
            // Typing indicator'ı kaldır
            this.hideTypingIndicator();
            
            if (data.success) {
                // AI yanıtını göster
                this.addMessage(data.response, 'ai');
                
                // Ürün önerisi varsa göster
                if (data.products && data.products.length > 0) {
                    this.showProducts(data.products);
                }
            } else {
                this.addMessage('Üzgünüm, bir hata oluştu: ' + (data.message || data.error || 'Bilinmeyen hata'), 'ai', false, true);
            }
            
        } catch (error) {
            console.error('Chat error:', error);
            this.hideTypingIndicator();
            this.addMessage('Bağlantı hatası oluştu. Lütfen daha sonra tekrar deneyin.', 'ai', false, true);
        }
    }

    addMessage(text, sender, isWelcome = false, isError = false) {
        const chatMessages = document.getElementById('chatMessages');
        const messageDiv = document.createElement('div');
        
        messageDiv.className = `message ${sender}-message`;
        if (isError) messageDiv.classList.add('error-message');
        
        const timestamp = new Date().toLocaleTimeString('tr-TR', {
            hour: '2-digit',
            minute: '2-digit'
        });
        
        if (sender === 'ai') {
            messageDiv.innerHTML = `
                <div class="message-content">
                    <div class="message-header">
                        <div class="bot-avatar">
                            <i class="fas fa-robot"></i>
                        </div>
                        <div class="message-info">
                            <span class="sender-name">AI Commerce Asistanı</span>
                            <span class="message-time">${timestamp}</span>
                        </div>
                    </div>
                    <div class="message-text">${this.formatMessage(text)}</div>
                </div>
            `;
        } else {
            messageDiv.innerHTML = `
                <div class="message-content">
                    <div class="message-text">${this.escapeHtml(text)}</div>
                    <div class="message-time">${timestamp}</div>
                </div>
            `;
        }
        
        chatMessages.appendChild(messageDiv);
        this.scrollToBottom();
        
        // Animasyon
        setTimeout(() => {
            messageDiv.classList.add('message-show');
        }, 100);
    }

    showProducts(products) {
        const chatMessages = document.getElementById('chatMessages');
        const productsDiv = document.createElement('div');
        productsDiv.className = 'products-container';
        
        let productsHTML = '<div class="products-grid">';
        
        products.forEach(product => {
            productsHTML += `
                <div class="product-card">
                    <div class="product-image">
                        <img src="/ai-ecommerce/assets/images/products/${product.main_image || 'placeholder.jpg'}" 
                             alt="${this.escapeHtml(product.name)}"
                             onerror="this.src='/ai-ecommerce/assets/images/placeholder.jpg'">
                    </div>
                    <div class="product-info">
                        <h4 class="product-name">${this.escapeHtml(product.name)}</h4>
                        <div class="product-price">${product.formatted_price}</div>
                        <a href="/ai-ecommerce/product-detail.php?slug=${product.slug}" 
                           class="btn btn-primary btn-sm">
                            <i class="fas fa-eye"></i> İncele
                        </a>
                    </div>
                </div>
            `;
        });
        
        productsHTML += '</div>';
        productsDiv.innerHTML = productsHTML;
        
        chatMessages.appendChild(productsDiv);
        this.scrollToBottom();
    }

    showTypingIndicator() {
        this.isTyping = true;
        const chatMessages = document.getElementById('chatMessages');
        
        const typingDiv = document.createElement('div');
        typingDiv.className = 'message ai-message typing-message';
        typingDiv.id = 'typingIndicator';
        
        typingDiv.innerHTML = `
            <div class="message-content">
                <div class="message-header">
                    <div class="bot-avatar">
                        <i class="fas fa-robot"></i>
                    </div>
                    <div class="message-info">
                        <span class="sender-name">AI Commerce Asistanı</span>
                        <span class="typing-status">yazıyor...</span>
                    </div>
                </div>
                <div class="typing-indicator">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>
            </div>
        `;
        
        chatMessages.appendChild(typingDiv);
        this.scrollToBottom();
    }

    hideTypingIndicator() {
        this.isTyping = false;
        const typingIndicator = document.getElementById('typingIndicator');
        if (typingIndicator) {
            typingIndicator.remove();
        }
    }

    handleQuickAction(action) {
        const actions = {
            'product_recommend': 'Bana ürün önerir misin?',
            'price_compare': 'Fiyat karşılaştırması yapar mısın?',
            'tech_support': 'Teknik destek alabilir miyim?',
            'order_track': 'Sipariş takibi yapmak istiyorum'
        };
        
        if (actions[action]) {
            document.getElementById('messageInput').value = actions[action];
            this.sendMessage();
        }
    }

    formatMessage(text) {
        // Basit markdown desteği
        return text
            .replace(/\n/g, '<br>')
            .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
            .replace(/\*(.*?)\*/g, '<em>$1</em>')
            .replace(/`(.*?)`/g, '<code>$1</code>');
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    scrollToBottom() {
        const chatMessages = document.getElementById('chatMessages');
        setTimeout(() => {
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }, 100);
    }

    // Sohbet geçmişini temizle
    clearChat() {
        const chatMessages = document.getElementById('chatMessages');
        chatMessages.innerHTML = '';
        this.showWelcomeMessage();
    }

    // Chat'i minimize/maximize et
    toggleChat() {
        const chatContainer = document.querySelector('.chat-container');
        chatContainer.classList.toggle('minimized');
    }
}

// Chat'i başlat
document.addEventListener('DOMContentLoaded', function() {
    window.aiChat = new AIChat();
});

// Global fonksiyonlar
function clearChat() {
    if (window.aiChat) {
        window.aiChat.clearChat();
    }
}

function toggleChat() {
    if (window.aiChat) {
        window.aiChat.toggleChat();
    }
}