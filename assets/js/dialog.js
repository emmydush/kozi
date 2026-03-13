/**
 * Custom Dialog Box System
 * Replaces browser alerts with modern, customizable dialogs
 */

class DialogManager {
    constructor() {
        this.activeDialog = null;
        this.init();
    }

    init() {
        // Create dialog container if it doesn't exist
        if (!document.getElementById('dialog-container')) {
            const container = document.createElement('div');
            container.id = 'dialog-container';
            document.body.appendChild(container);
        }
    }

    /**
     * Show a dialog box
     * @param {Object} options - Dialog options
     * @param {string} options.title - Dialog title
     * @param {string} options.message - Dialog message
     * @param {string} options.type - Dialog type (success, error, warning, info)
     * @param {Array} options.buttons - Array of button objects
     * @param {boolean} options.closable - Whether dialog can be closed by clicking outside
     * @param {Function} options.onClose - Callback when dialog is closed
     */
    show(options = {}) {
        const defaults = {
            title: 'Notification',
            message: '',
            type: 'info',
            buttons: [{ text: 'OK', type: 'primary', action: () => this.close() }],
            closable: true,
            onClose: null
        };

        const config = { ...defaults, ...options };

        // Close any existing dialog
        if (this.activeDialog) {
            this.close();
        }

        // Create dialog HTML
        const dialogHTML = this.createDialogHTML(config);
        const container = document.getElementById('dialog-container');
        container.innerHTML = dialogHTML;

        // Store reference
        this.activeDialog = {
            element: container.querySelector('.dialog-overlay'),
            config: config
        };

        // Setup event listeners
        this.setupEventListeners();

        // Show dialog with animation
        requestAnimationFrame(() => {
            this.activeDialog.element.classList.add('show');
        });

        return new Promise((resolve) => {
            this.activeDialog.resolve = resolve;
        });
    }

    /**
     * Create dialog HTML
     */
    createDialogHTML(config) {
        const iconClass = this.getIconClass(config.type);
        
        return `
            <div class="dialog-overlay">
                <div class="dialog-box">
                    <div class="dialog-header">
                        <h3 class="dialog-title">
                            <div class="dialog-icon ${config.type}">
                                <i class="fas ${iconClass}"></i>
                            </div>
                            ${config.title}
                        </h3>
                        <button class="dialog-close" aria-label="Close">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="dialog-body dialog-content">
                        ${config.message}
                    </div>
                    <div class="dialog-footer">
                        ${config.buttons.map(btn => `
                            <button class="dialog-btn dialog-btn-${btn.type || 'secondary'}" 
                                    data-action="${btn.action ? 'custom' : 'close'}">
                                ${btn.text}
                            </button>
                        `).join('')}
                    </div>
                </div>
            </div>
        `;
    }

    /**
     * Get icon class based on dialog type
     */
    getIconClass(type) {
        const icons = {
            success: 'fa-check',
            error: 'fa-times',
            warning: 'fa-exclamation-triangle',
            info: 'fa-info-circle'
        };
        return icons[type] || icons.info;
    }

    /**
     * Setup event listeners for dialog
     */
    setupEventListeners() {
        const dialog = this.activeDialog.element;
        const config = this.activeDialog.config;

        // Close button
        const closeBtn = dialog.querySelector('.dialog-close');
        closeBtn.addEventListener('click', () => this.close());

        // Click outside to close (if enabled)
        if (config.closable) {
            dialog.addEventListener('click', (e) => {
                if (e.target === dialog) {
                    this.close();
                }
            });
        }

        // Button actions
        const buttons = dialog.querySelectorAll('.dialog-btn');
        buttons.forEach((btn, index) => {
            btn.addEventListener('click', () => {
                const buttonConfig = config.buttons[index];
                if (buttonConfig.action) {
                    buttonConfig.action();
                } else {
                    this.close();
                }
            });
        });

        // Escape key to close
        const escapeHandler = (e) => {
            if (e.key === 'Escape') {
                this.close();
                document.removeEventListener('keydown', escapeHandler);
            }
        };
        document.addEventListener('keydown', escapeHandler);
    }

    /**
     * Close the dialog
     */
    close() {
        if (!this.activeDialog) return;

        const dialog = this.activeDialog.element;
        dialog.classList.remove('show');

        setTimeout(() => {
            if (this.activeDialog && this.activeDialog.resolve) {
                this.activeDialog.resolve();
            }
            
            const config = this.activeDialog.config;
            if (config.onClose) {
                config.onClose();
            }

            // Clean up
            const container = document.getElementById('dialog-container');
            container.innerHTML = '';
            this.activeDialog = null;
        }, 300);
    }

    /**
     * Convenience methods for different dialog types
     */
    success(message, title = 'Success') {
        return this.show({
            title: title,
            message: message,
            type: 'success'
        });
    }

    error(message, title = 'Error') {
        return this.show({
            title: title,
            message: message,
            type: 'error',
            buttons: [{ text: 'OK', type: 'primary', action: () => this.close() }]
        });
    }

    warning(message, title = 'Warning') {
        return this.show({
            title: title,
            message: message,
            type: 'warning',
            buttons: [
                { text: 'Cancel', type: 'secondary', action: () => this.close() },
                { text: 'Continue', type: 'primary', action: () => this.close() }
            ]
        });
    }

    info(message, title = 'Information') {
        return this.show({
            title: title,
            message: message,
            type: 'info'
        });
    }

    confirm(message, title = 'Confirm Action') {
        return this.show({
            title: title,
            message: message,
            type: 'warning',
            buttons: [
                { text: 'Cancel', type: 'secondary', action: () => this.close() },
                { text: 'Confirm', type: 'primary', action: () => this.close() }
            ]
        });
    }

    custom(options) {
        return this.show(options);
    }
}

// Initialize dialog manager
const dialog = new DialogManager();

// Replace default alert with custom dialog
window.showAlert = function(message, type = 'info', title = null) {
    const titles = {
        success: 'Success',
        error: 'Error', 
        warning: 'Warning',
        info: 'Information',
        danger: 'Error'
    };
    
    return dialog[type](message, title || titles[type] || 'Notification');
};

// Replace default confirm with custom dialog
window.showConfirm = function(message, onConfirm, onCancel = null) {
    dialog.show({
        title: 'Confirm Action',
        message: message,
        type: 'warning',
        buttons: [
            { 
                text: 'Cancel', 
                type: 'secondary', 
                action: () => {
                    dialog.close();
                    if (onCancel) onCancel();
                }
            },
            { 
                text: 'Confirm', 
                type: 'primary', 
                action: () => {
                    dialog.close();
                    if (onConfirm) onConfirm();
                }
            }
        ]
    });
};

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = DialogManager;
}
