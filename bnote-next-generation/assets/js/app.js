/**
 * BNote Next Generation - App Initialization
 *
 * Copyright (C) 2026 BNote Contributors
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

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
        
        // Initialize i18n (internationalization)
        await this.initI18n();
        
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
     * Initialize internationalization
     * Uses system configuration language and country, not browser settings
     */
    async initI18n() {
        if (typeof i18n === 'undefined') {
            console.warn('i18n service not loaded');
            return;
        }
        
        try {
            // Get language and country from system configuration
            let langCode = 'de'; // Default to German
            let countryCode = null;
            
            try {
                const configResponse = await api.get('auth', 'getUserLang');
                langCode = configResponse.lang || 'de';
                countryCode = configResponse.country || null;
            } catch (error) {
                console.error('Failed to get system config language:', error);
                // Only fallback to browser if API completely fails
                const browserLang = navigator.language || navigator.userLanguage;
                if (browserLang) {
                    langCode = browserLang.split('-')[0];
                    const browserCountry = browserLang.split('-')[1];
                    if (browserCountry) {
                        countryCode = browserCountry;
                    }
                }
            }
            
            // Validate language code
            const validLanguages = ['de', 'en', 'es', 'fr'];
            if (!validLanguages.includes(langCode)) {
                langCode = 'de';
            }
            
            // Initialize i18n with system config language and country
            await i18n.init(langCode, countryCode);
            
            // Translate page after i18n is initialized
            if (typeof i18n.translatePage === 'function') {
                i18n.translatePage();
            }
        } catch (error) {
            console.error('Failed to initialize i18n:', error);
            // Initialize with default language
            await i18n.init('de');
            
            // Translate page even if initialization had errors
            if (typeof i18n.translatePage === 'function') {
                i18n.translatePage();
            }
        }
    },
    
    /**
     * Show loading state
     * @param {HTMLElement} container Container element
     */
    showLoading(container) {
        if (container) {
            container.innerHTML = `
                <div class="flex items-center justify-center p-8">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
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
                <div class="bg-danger-muted border border-danger-muted rounded-lg p-4 text-danger">
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
        toast.className = 'fixed top-4 right-4 bg-success-muted border border-success-muted text-success px-4 py-3 rounded-lg shadow-lg z-50';
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
