<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

requireLogin();

$page_title = 'AI Asistan';
$page_description = 'AI destekli alışveriş asistanınız ile konuşun';

// AI özelliği aktif mi kontrol et
$ai_enabled = getSiteSetting('enable_ai_chat', '1') === '1';
$api_key = getSiteSetting('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', '');

include '../includes/header.php';
?>

<div class="container py-8">
    <!-- Başlık -->
    <div class="text-center mb-12">
        <div class="w-20 h-20 bg-gradient-to-r from-blue-500 to-purple-600 rounded-full flex items-center justify-center mx-auto mb-6">
            <i class="fas fa-robot text-white text-3xl"></i>
        </div>
        <h1 class="text-4xl font-bold mb-4">AI Alışveriş Asistanı</h1>
        <p class="text-lg text-secondary max-w-2xl mx-auto">
            Yapay zeka destekli asistanımız size en uygun ürünleri önerir, 
            sorularınızı yanıtlar ve alışveriş deneyiminizi kişiselleştirir.
        </p>
    </div>

    <?php if ($ai_enabled): ?>
        <div class="max-w-4xl mx-auto">
            <!-- Chat Container -->
            <div class="card overflow-hidden">
                <!-- Chat Header -->
                <div class="bg-gradient-to-r from-blue-500 to-purple-600 text-white p-6">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="w-12 h-12 bg-white bg-opacity-20 rounded-full flex items-center justify-center mr-4">
                                <i class="fas fa-robot text-xl"></i>
                            </div>
                            <div>
                                <h2 class="text-xl font-semibold">AI Commerce Asistanı</h2>
                                <p class="text-blue-100">
                                    <span class="inline-flex items-center">
                                        <span class="w-2 h-2 bg-green-400 rounded-full mr-2 animate-pulse"></span>
                                        Çevrimiçi
                                    </span>
                                </p>
                            </div>
                        </div>
                        
                        <div class="flex items-center gap-3">
                            <button onclick="clearChat()" 
                                    class="w-10 h-10 bg-white bg-opacity-20 rounded-full flex items-center justify-center hover:bg-opacity-30 transition-colors"
                                    title="Sohbeti Temizle">
                                <i class="fas fa-trash text-sm"></i>
                            </button>
                            <button onclick="exportChat()" 
                                    class="w-10 h-10 bg-white bg-opacity-20 rounded-full flex items-center justify-center hover:bg-opacity-30 transition-colors"
                                    title="Sohbeti Dışa Aktar">
                                <i class="fas fa-download text-sm"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Chat Messages -->
                <div id="chatMessages" class="h-96 overflow-y-auto p-6 bg-gray-50">
                    <!-- Hoş geldin mesajı -->
                    <div class="ai-message mb-4">
                        <div class="flex items-start gap-3">
                            <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-robot text-white text-sm"></i>
                            </div>
                            <div class="bg-white rounded-lg p-4 shadow-sm max-w-md">
                                <p class="text-gray-800">
                                    Merhaba <strong><?php echo htmlspecialchars($_SESSION['full_name'] ?: $_SESSION['username']); ?></strong>! 👋
                                    <br><br>
                                    AI Commerce asistanınızım. Size nasıl yardımcı olabilirim?
                                    <br><br>
                                    • Ürün önerileri<br>
                                    • Fiyat karşılaştırması<br>
                                    • Teknik destek<br>
                                    • Sipariş takibi
                                </p>
                            </div>
                        </div>
                        <div class="text-xs text-gray-500 mt-2 ml-11">
                            Şimdi
                        </div>
                    </div>
                </div>

                <!-- Chat Input -->
                <div class="p-6 bg-white border-t">
                    <form id="chatForm" class="flex gap-3">
                        <div class="flex-1 relative">
                            <input type="text" 
                                   id="messageInput"
                                   placeholder="Mesajınızı yazın..."
                                   class="form-input pr-20"
                                   maxlength="500"
                                   autocomplete="off">
                            
                            <!-- Hızlı Eylemler -->
                            <div class="absolute right-3 top-1/2 transform -translate-y-1/2 flex items-center gap-2">
                                <button type="button" 
                                        onclick="insertQuickMessage('En popüler ürünler neler?')"
                                        class="text-gray-400 hover:text-primary text-sm"
                                        title="Hızlı mesaj">
                                    <i class="fas fa-star"></i>
                                </button>
                                <button type="button" 
                                        onclick="toggleVoiceInput()"
                                        class="text-gray-400 hover:text-primary text-sm"
                                        title="Sesli mesaj">
                                    <i class="fas fa-microphone"></i>
                                </button>
                            </div>
                        </div>
                        
                        <button type="submit" 
                                id="sendButton"
                                class="btn btn-primary px-6">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </form>
                    
                    <!-- Hızlı Mesajlar -->
                    <div class="mt-4">
                        <div class="flex flex-wrap gap-2">
                            <button onclick="sendQuickMessage('Bütçeme uygun ürün öner')" 
                                    class="quick-message-btn">
                                💰 Bütçeme uygun ürün öner
                            </button>
                            <button onclick="sendQuickMessage('En çok satan ürünler')" 
                                    class="quick-message-btn">
                                🔥 En çok satan ürünler
                            </button>
                            <button onclick="sendQuickMessage('Yeni ürünleri göster')" 
                                    class="quick-message-btn">
                                ✨ Yeni ürünler
                            </button>
                            <button onclick="sendQuickMessage('Kampanyalı ürünler')" 
                                    class="quick-message-btn">
                                🎯 Kampanyalar
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- AI Özellikleri -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-12">
                <div class="text-center p-6">
                    <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-brain text-blue-600 text-2xl"></i>
                    </div>
                    <h3 class="font-semibold mb-2">Akıllı Öneriler</h3>
                    <p class="text-sm text-gray-600">
                        Alışveriş geçmişiniz ve tercihlerinize göre kişisel öneriler
                    </p>
                </div>
                
                <div class="text-center p-6">
                    <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-comments text-green-600 text-2xl"></i>
                    </div>
                    <h3 class="font-semibold mb-2">Doğal Dil İşleme</h3>
                    <p class="text-sm text-gray-600">
                        Günlük konuşma dilinizle rahatlıkla iletişim kurun
                    </p>
                </div>
                
                <div class="text-center p-6">
                    <div class="w-16 h-16 bg-purple-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-clock text-purple-600 text-2xl"></i>
                    </div>
                    <h3 class="font-semibold mb-2">7/24 Destek</h3>
                    <p class="text-sm text-gray-600">
                        Her zaman yanınızda, anında yanıt veren AI asistan
                    </p>
                </div>
            </div>
        </div>

    <?php else: ?>
        <!-- AI Devre Dışı -->
        <div class="text-center py-16">
            <div class="w-24 h-24 bg-gray-200 rounded-full flex items-center justify-center mx-auto mb-6">
                <i class="fas fa-robot text-gray-400 text-3xl"></i>
            </div>
            <h2 class="text-2xl font-semibold text-gray-700 mb-4">AI Asistan Şu An Kullanılamıyor</h2>
            <p class="text-gray-500 mb-8 max-w-md mx-auto">
                AI asistan özelliği geçici olarak devre dışı. 
                Yakında daha güçlü özellikleriyle geri döneceğiz!
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="/ai-ecommerce/products/" class="btn btn-primary">
                    <i class="fas fa-shopping-bag mr-2"></i>
                    Ürünleri İncele
                </a>
                <a href="/ai-ecommerce/" class="btn btn-secondary">
                    <i class="fas fa-home mr-2"></i>
                    Ana Sayfaya Dön
                </a>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
class AIChat {
    constructor() {
        this.sessionId = this.generateSessionId();
        this.isTyping = false;
        this.recognition = null;
        this.initializeChat();
        this.initializeVoiceInput();
    }

    generateSessionId() {
        return 'chat_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
    }

    initializeChat() {
        const form = document.getElementById('chatForm');
        const input = document.getElementById('messageInput');
        
        form.addEventListener('submit', (e) => {
            e.preventDefault();
            this.sendMessage();
        });
        
        input.addEventListener('keypress', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                this.sendMessage();
            }
        });
        
        // Auto-scroll to bottom
        this.scrollToBottom();
    }

    async sendMessage() {
        const input = document.getElementById('messageInput');
        const message = input.value.trim();
        
        if (!message || this.isTyping) return;
        
        // Kullanıcı mesajını ekle
        this.addMessage(message, 'user');
        input.value = '';
        
        // AI yanıtını al
        await this.getAIResponse(message);
    }

    addMessage(text, sender) {
        const messagesContainer = document.getElementById('chatMessages');
        const messageDiv = document.createElement('div');
        messageDiv.className = `${sender}-message mb-4`;
        
        const time = new Date().toLocaleTimeString('tr-TR', {
            hour: '2-digit',
            minute: '2-digit'
        });
        
        if (sender === 'user') {
            messageDiv.innerHTML = `
                <div class="flex items-start gap-3 justify-end">
                    <div class="bg-blue-500 text-white rounded-lg p-4 shadow-sm max-w-md">
                        <p>${this.escapeHtml(text)}</p>
                    </div>
                    <div class="w-8 h-8 bg-gray-300 rounded-full flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-user text-gray-600 text-sm"></i>
                    </div>
                </div>
                <div class="text-xs text-gray-500 mt-2 mr-11 text-right">
                    ${time}
                </div>
            `;
        } else {
            messageDiv.innerHTML = `
                <div class="flex items-start gap-3">
                    <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-robot text-white text-sm"></i>
                    </div>
                    <div class="bg-white rounded-lg p-4 shadow-sm max-w-md">
                        <div class="ai-response">${text}</div>
                    </div>
                </div>
                <div class="text-xs text-gray-500 mt-2 ml-11">
                    ${time}
                </div>
            `;
        }
        
        messagesContainer.appendChild(messageDiv);
        this.scrollToBottom();
        
        // Animasyon
        messageDiv.style.opacity = '0';
        messageDiv.style.transform = 'translateY(10px)';
        
        setTimeout(() => {
            messageDiv.style.transition = 'all 0.3s ease-out';
            messageDiv.style.opacity = '1';
            messageDiv.style.transform = 'translateY(0)';
        }, 10);
    }

    async getAIResponse(userMessage) {
        this.isTyping = true;
        this.showTypingIndicator();
        
        try {
            const response = await fetch('/ai-ecommerce/api/ai-chat.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    message: userMessage,
                    session_id: this.sessionId
                })
            });
            
            const data = await response.json();
            
            this.hideTypingIndicator();
            
            if (data.success) {
                this.addMessage(data.response, 'ai');
                
                // Ürün önerileri varsa göster
                if (data.products && data.products.length > 0) {
                    this.showProductSuggestions(data.products);
                }
            } else {
                this.addMessage('Üzgünüm, şu anda yanıt veremiyorum. Lütfen daha sonra tekrar deneyin.', 'ai');
            }
            
        } catch (error) {
            console.error('AI Chat error:', error);
            this.hideTypingIndicator();
            this.addMessage('Bağlantı hatası oluştu. Lütfen daha sonra tekrar deneyin.', 'ai');
        }
        
        this.isTyping = false;
    }

    showTypingIndicator() {
        const indicator = document.createElement('div');
        indicator.id = 'typingIndicator';
        indicator.className = 'ai-message mb-4';
        indicator.innerHTML = `
            <div class="flex items-start gap-3">
                <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-robot text-white text-sm"></i>
                </div>
                <div class="bg-white rounded-lg p-4 shadow-sm">
                    <div class="flex items-center gap-1">
                        <div class="typing-dot"></div>
                        <div class="typing-dot"></div>
                        <div class="typing-dot"></div>
                    </div>
                </div>
            </div>
        `;
        
        document.getElementById('chatMessages').appendChild(indicator);
        this.scrollToBottom();
    }

    hideTypingIndicator() {
        const indicator = document.getElementById('typingIndicator');
        if (indicator) {
            indicator.remove();
        }
    }

    showProductSuggestions(products) {
        const suggestionsHtml = products.map(product => `
            <div class="product-suggestion bg-gray-50 rounded-lg p-3 mb-2">
                <div class="flex items-center gap-3">
                    <img src="/ai-ecommerce/assets/images/products/${product.main_image || 'placeholder.jpg'}" 
                         alt="${product.name}" 
                         class="w-12 h-12 object-cover rounded">
                    <div class="flex-1">
                        <h4 class="font-medium text-sm">${product.name}</h4>
                        <p class="text-primary font-bold text-sm">${product.formatted_price}</p>
                    </div>
                    <a href="/ai-ecommerce/products/detail.php?slug=${product.slug}" 
                       class="btn btn-primary btn-sm">
                        İncele
                    </a>
                </div>
            </div>
        `).join('');
        
        this.addMessage(`İşte sizin için bulduğum ürünler:<br><br>${suggestionsHtml}`, 'ai');
    }

    scrollToBottom() {
        const container = document.getElementById('chatMessages');
        setTimeout(() => {
            container.scrollTop = container.scrollHeight;
        }, 100);
    }

    initializeVoiceInput() {
        if ('webkitSpeechRecognition' in window || 'SpeechRecognition' in window) {
            this.recognition = new (window.SpeechRecognition || window.webkitSpeechRecognition)();
            this.recognition.lang = 'tr-TR';
            this.recognition.continuous = false;
            this.recognition.interimResults = false;
            
            this.recognition.onresult = (event) => {
                const transcript = event.results[0][0].transcript;
                document.getElementById('messageInput').value = transcript;
            };
            
            this.recognition.onerror = (event) => {
                console.error('Voice recognition error:', event.error);
                window.aiCommerce.showNotification('Ses tanıma hatası!', 'error');
            };
        }
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Hızlı mesaj fonksiyonları
function sendQuickMessage(message) {
    document.getElementById('messageInput').value = message;
    chat.sendMessage();
}

function insertQuickMessage(message) {
    document.getElementById('messageInput').value = message;
    document.getElementById('messageInput').focus();
}

function toggleVoiceInput() {
    if (chat.recognition) {
        chat.recognition.start();
        window.aiCommerce.showNotification('Konuşmaya başlayın...', 'info');
    } else {
        window.aiCommerce.showNotification('Ses tanıma desteklenmiyor!', 'error');
    }
}

function clearChat() {
    if (confirm('Sohbet geçmişini temizlemek istediğinizden emin misiniz?')) {
        location.reload();
    }
}

function exportChat() {
    const messages = document.querySelectorAll('#chatMessages .ai-message, #chatMessages .user-message');
    let chatText = 'AI Commerce - Sohbet Geçmişi\n';
    chatText += '================================\n\n';
    
    messages.forEach(message => {
        const sender = message.classList.contains('user-message') ? 'Ben' : 'AI Asistan';
        const text = message.querySelector('p, .ai-response').textContent;
        const time = message.querySelector('.text-xs').textContent;
        
        chatText += `[${time}] ${sender}: ${text}\n\n`;
    });
    
    const blob = new Blob([chatText], { type: 'text/plain' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `ai-chat-${new Date().toISOString().split('T')[0]}.txt`;
    a.click();
    URL.revokeObjectURL(url);
}

// Chat'i başlat
let chat;
document.addEventListener('DOMContentLoaded', function() {
    <?php if ($ai_enabled): ?>
        chat = new AIChat();
    <?php endif; ?>
});
</script>

<style>
/* Chat specific styles */
.quick-message-btn {
    @apply bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 py-2 rounded-full text-sm transition-colors cursor-pointer border-0;
}

.typing-dot {
    width: 8px;
    height: 8px;
    background: #6b7280;
    border-radius: 50%;
    animation: typing 1.4s infinite;
}

.typing-dot:nth-child(2) {
    animation-delay: 0.2s;
}

.typing-dot:nth-child(3) {
    animation-delay: 0.4s;
}

@keyframes typing {
    0%, 60%, 100% {
        transform: translateY(0);
        opacity: 0.4;
    }
    30% {
        transform: translateY(-10px);
        opacity: 1;
    }
}

.product-suggestion {
    animation: slideInUp 0.3s ease-out;
}

@keyframes slideInUp {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Scrollbar styling */
#chatMessages::-webkit-scrollbar {
    width: 6px;
}

#chatMessages::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 3px;
}

#chatMessages::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 3px;
}

#chatMessages::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    #chatMessages {
        height: 300px;
    }
    
    .quick-message-btn {
        font-size: 0.75rem;
        padding: 0.5rem 0.75rem;
    }
}
</style>

<?php include '../includes/footer.php'; ?>