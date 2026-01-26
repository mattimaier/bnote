/**
 * BNote Modern UI App Initialization
 */
const App = {
    /**
     * Initialize the app
     */
    async init() {
        // Check if API client is loaded
        if (typeof api === 'undefined' || typeof AuthApi === 'undefined') {
            console.error('API client not loaded');
            return;
        }
        
        // Global error handler
        window.addEventListener('error', (event) => {
            console.error('Global error:', event.error);
        });
        
        // Handle unhandled promise rejections
        window.addEventListener('unhandledrejection', (event) => {
            console.error('Unhandled promise rejection:', event.reason);
        });
    },
    
    /**
     * Show loading state
     * @param {HTMLElement} container Container element
     */
    showLoading(container) {
        if (container) {
            container.innerHTML = `
                <div class="flex items-center justify-center p-8">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-600"></div>
                </div>
            `;
        }
    },
    
    /**
     * Show error state
     * @param {HTMLElement} container Container element
     * @param {string} message Error message
     */
    showError(container, message) {
        if (container) {
            container.innerHTML = `
                <div class="bg-red-50 border border-red-200 rounded-lg p-4 text-red-800">
                    <p>${message || 'An error occurred'}</p>
                </div>
            `;
        }
    },
    
    /**
     * Show success message (toast-style)
     * @param {string} message Success message
     */
    showSuccess(message) {
        const toast = document.createElement('div');
        toast.className = 'fixed top-4 right-4 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg shadow-lg z-50';
        toast.textContent = message;
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.remove();
        }, 3000);
    }
};

// Initialize on DOM ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => App.init());
} else {
    App.init();
}
