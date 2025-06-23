            </div>
        </main>
    </div>

    <!-- Admin JavaScript -->
    <script src="/ai-ecommerce/assets/js/main.js"></script>
    <script>
        // Admin-specific JavaScript
        class AdminPanel {
            constructor() {
                this.initializeComponents();
                this.setupEventListeners();
            }

            initializeComponents() {
                // Auto-save functionality
                this.setupAutoSave();
                
                // Keyboard shortcuts
                this.setupKeyboardShortcuts();
                
                // Real-time notifications
                this.startNotificationPolling();
                
                // Dashboard auto-refresh
                this.setupAutoRefresh();
            }

            setupEventListeners() {
                // Global admin event listeners
                document.addEventListener('keydown', (e) => {
                    // Escape key closes modals
                    if (e.key === 'Escape') {
                        this.closeAllModals();
                    }
                });

                // Auto-resize textareas
                document.querySelectorAll('textarea').forEach(textarea => {
                    textarea.addEventListener('input', this.autoResizeTextarea);
                });

                // Confirm dangerous actions
                document.querySelectorAll('[data-confirm]').forEach(element => {
                    element.addEventListener('click', (e) => {
                        const message = element.getAttribute('data-confirm');
                        if (!confirm(message)) {
                            e.preventDefault();
                        }
                    });
                });
            }

            setupAutoSave() {
                const forms = document.querySelectorAll('[data-autosave]');
                forms.forEach(form => {
                    let timeout;
                    const inputs = form.querySelectorAll('input, textarea, select');
                    
                    inputs.forEach(input => {
                        input.addEventListener('input', () => {
                            clearTimeout(timeout);
                            timeout = setTimeout(() => {
                                this.autoSaveForm(form);
                            }, 2000);
                        });
                    });
                });
            }

            autoSaveForm(form) {
                const formData = new FormData(form);
                formData.append('auto_save', '1');
                
                fetch(form.action, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        this.showAutoSaveIndicator();
                    }
                })
                .catch(error => {
                    console.error('Auto-save error:', error);
                });
            }

            showAutoSaveIndicator() {
                // Create or update auto-save indicator
                let indicator = document.getElementById('autoSaveIndicator');
                if (!indicator) {
                    indicator = document.createElement('div');
                    indicator.id = 'autoSaveIndicator';
                    indicator.className = 'fixed bottom-4 right-4 bg-green-500 text-white px-4 py-2 rounded-lg text-sm opacity-0 transition-opacity';
                    indicator.innerHTML = '<i class="fas fa-check mr-2"></i>Otomatik kaydedildi';
                    document.body.appendChild(indicator);
                }
                
                indicator.style.opacity = '1';
                setTimeout(() => {
                    indicator.style.opacity = '0';
                }, 2000);
            }

            setupKeyboardShortcuts() {
                document.addEventListener('keydown', (e) => {
                    // Ctrl/Cmd + S for save
                    if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                        e.preventDefault();
                        const saveButton = document.querySelector('button[type="submit"], .btn-save');
                        if (saveButton) {
                            saveButton.click();
                        }
                    }
                    
                    // Ctrl/Cmd + K for search
                    if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                        e.preventDefault();
                        const searchInput = document.getElementById('adminSearch');
                        if (searchInput) {
                            searchInput.focus();
                        }
                    }
                    
                    // Alt + N for new item
                    if (e.altKey && e.key === 'n') {
                        e.preventDefault();
                        const newButton = document.querySelector('.btn-new, [href*="add.php"]');
                        if (newButton) {
                            newButton.click();
                        }
                    }
                });
            }

            startNotificationPolling() {
                // Poll for new notifications every 30 seconds
                setInterval(() => {
                    this.checkNewNotifications();
                }, 30000);
            }

            checkNewNotifications() {
                fetch('/ai-ecommerce/admin/api/notifications.php')
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && data.count > 0) {
                            this.updateNotificationBadge(data.count);
                        }
                    })
                    .catch(error => {
                        console.error('Notification check error:', error);
                    });
            }

            updateNotificationBadge(count) {
                const badge = document.querySelector('.notification-badge');
                if (badge) {
                    badge.textContent = count;
                    badge.style.display = count > 0 ? 'flex' : 'none';
                }
            }

            setupAutoRefresh() {
                // Auto-refresh dashboard every 5 minutes
                if (window.location.pathname.includes('dashboard.php')) {
                    setInterval(() => {
                        this.refreshDashboardStats();
                    }, 300000);
                }
            }

            refreshDashboardStats() {
                fetch('/ai-ecommerce/admin/api/dashboard-stats.php')
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            this.updateDashboardStats(data.stats);
                        }
                    })
                    .catch(error => {
                        console.error('Dashboard refresh error:', error);
                    });
            }

            updateDashboardStats(stats) {
                // Update dashboard statistics without page reload
                Object.keys(stats).forEach(key => {
                    const element = document.querySelector(`[data-stat="${key}"]`);
                    if (element) {
                        element.textContent = stats[key];
                    }
                });
            }

            autoResizeTextarea(e) {
                const textarea = e.target;
                textarea.style.height = 'auto';
                textarea.style.height = textarea.scrollHeight + 'px';
            }

            closeAllModals() {
                document.querySelectorAll('.modal.show').forEach(modal => {
                    modal.classList.remove('show');
                });
                document.body.classList.remove('modal-open');
            }

            // Data table functionality
            initializeDataTables() {
                document.querySelectorAll('.data-table').forEach(table => {
                    this.enhanceDataTable(table);
                });
            }

            enhanceDataTable(table) {
                // Add sorting functionality
                const headers = table.querySelectorAll('th[data-sort]');
                headers.forEach(header => {
                    header.style.cursor = 'pointer';
                    header.addEventListener('click', () => {
                        this.sortTable(table, header.dataset.sort);
                    });
                });

                // Add filtering
                const filterInput = table.parentElement.querySelector('.table-filter');
                if (filterInput) {
                    filterInput.addEventListener('input', (e) => {
                        this.filterTable(table, e.target.value);
                    });
                }
            }

            sortTable(table, column) {
                // Implement table sorting
                console.log('Sorting table by:', column);
            }

            filterTable(table, query) {
                const rows = table.querySelectorAll('tbody tr');
                query = query.toLowerCase();

                rows.forEach(row => {
                    const text = row.textContent.toLowerCase();
                    row.style.display = text.includes(query) ? '' : 'none';
                });
            }

            // Bulk actions
            setupBulkActions() {
                const selectAll = document.getElementById('selectAll');
                const bulkActions = document.getElementById('bulkActions');
                
                if (selectAll) {
                    selectAll.addEventListener('change', (e) => {
                        const checkboxes = document.querySelectorAll('.bulk-checkbox');
                        checkboxes.forEach(checkbox => {
                            checkbox.checked = e.target.checked;
                        });
                        this.updateBulkActions();
                    });
                }

                document.querySelectorAll('.bulk-checkbox').forEach(checkbox => {
                    checkbox.addEventListener('change', () => {
                        this.updateBulkActions();
                    });
                });
            }

            updateBulkActions() {
                const checkedBoxes = document.querySelectorAll('.bulk-checkbox:checked');
                const bulkActions = document.getElementById('bulkActions');
                
                if (bulkActions) {
                    bulkActions.style.display = checkedBoxes.length > 0 ? 'block' : 'none';
                }
            }

            // Image upload preview
            setupImageUpload() {
                document.querySelectorAll('input[type="file"][accept*="image"]').forEach(input => {
                    input.addEventListener('change', (e) => {
                        this.previewImage(e.target);
                    });
                });
            }

            previewImage(input) {
                const file = input.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        let preview = input.nextElementSibling;
                        if (!preview || !preview.classList.contains('image-preview')) {
                            preview = document.createElement('img');
                            preview.className = 'image-preview w-32 h-32 object-cover rounded-lg mt-2';
                            input.parentNode.appendChild(preview);
                        }
                        preview.src = e.target.result;
                    };
                    reader.readAsDataURL(file);
                }
            }

            // Success/Error toast notifications
            showToast(message, type = 'success') {
                const toast = document.createElement('div');
                toast.className = `toast toast-${type} fixed top-4 right-4 p-4 rounded-lg shadow-lg z-50 transform translate-x-full transition-transform`;
                
                const icon = type === 'success' ? 'check-circle' : 'exclamation-circle';
                toast.innerHTML = `
                    <div class="flex items-center">
                        <i class="fas fa-${icon} mr-3"></i>
                        <span>${message}</span>
                        <button onclick="this.parentElement.parentElement.remove()" class="ml-4">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                `;
                
                document.body.appendChild(toast);
                
                // Animate in
                setTimeout(() => {
                    toast.style.transform = 'translateX(0)';
                }, 100);
                
                // Auto remove after 5 seconds
                setTimeout(() => {
                    toast.style.transform = 'translateX(full)';
                    setTimeout(() => toast.remove(), 300);
                }, 5000);
            }
        }

        // Initialize admin panel
        const adminPanel = new AdminPanel();
        
        // Global admin functions
        window.showToast = adminPanel.showToast.bind(adminPanel);
        window.closeAllModals = adminPanel.closeAllModals.bind(adminPanel);
    </script>

    <style>
        /* Toast styles */
        .toast-success {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
        }
        
        .toast-error {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
        }
        
        /* Image preview styles */
        .image-preview {
            border: 2px dashed #d1d5db;
            transition: border-color 0.2s ease;
        }
        
        .image-preview:hover {
            border-color: var(--primary);
        }
        
        /* Data table enhancements */
        .data-table th[data-sort]:hover {
            background-color: #f3f4f6;
        }
        
        .data-table th[data-sort]::after {
            content: '\f0dc';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            margin-left: 0.5rem;
            opacity: 0.5;
        }
        
        /* Auto-save indicator */
        #autoSaveIndicator {
            transition: opacity 0.3s ease;
        }
        
        /* Bulk actions */
        #bulkActions {
            display: none;
            position: fixed;
            bottom: 2rem;
            left: 50%;
            transform: translateX(-50%);
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 0.5rem;
            padding: 1rem;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            z-index: 1000;
        }
        
        /* Loading states */
        .btn.loading {
            position: relative;
            pointer-events: none;
            opacity: 0.7;
        }
        
        .btn.loading::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 1rem;
            height: 1rem;
            border: 2px solid transparent;
            border-top: 2px solid currentColor;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: translate(-50%, -50%) rotate(0deg); }
            100% { transform: translate(-50%, -50%) rotate(360deg); }
        }
        
        /* Responsive improvements */
        @media (max-width: 768px) {
            .admin-content {
                padding: 1rem;
            }
            
            .card {
                margin-bottom: 1rem;
            }
            
            .grid {
                grid-template-columns: 1fr;
            }
        }
        
        /* Dark mode support for admin */
        @media (prefers-color-scheme: dark) {
            .admin-header {
                background: #1f2937;
                border-color: #374151;
                color: white;
            }
            
            .admin-content {
                background: #111827;
            }
            
            .card {
                background: #1f2937;
                border-color: #374151;
                color: white;
            }
        }
    </style>

    <?php if (isset($extra_js)): ?>
        <?php foreach ($extra_js as $js): ?>
            <script src="<?php echo $js; ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>