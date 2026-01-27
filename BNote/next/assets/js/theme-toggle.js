/**
 * Theme Toggle Utility
 * Handles dark mode switching with localStorage persistence
 */

const ThemeToggle = {
    /**
     * Initialize theme toggle
     * Respects system preference on first load, then remembers user choice
     */
    init() {
        // Check for saved theme preference or default to system preference
        const savedTheme = localStorage.getItem('theme');
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        
        if (savedTheme) {
            // Use saved preference
            this.setTheme(savedTheme);
        } else {
            // Use system preference
            this.setTheme(prefersDark ? 'dark' : 'light');
        }
        
        // Listen for system theme changes (only if no saved preference)
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
            if (!localStorage.getItem('theme')) {
                this.setTheme(e.matches ? 'dark' : 'light');
            }
        });
    },
    
    /**
     * Set theme
     * @param {string} theme - 'light' or 'dark'
     */
    setTheme(theme) {
        const html = document.documentElement;
        
        if (theme === 'dark') {
            html.classList.add('dark');
        } else {
            html.classList.remove('dark');
        }
        
        // Save preference
        localStorage.setItem('theme', theme);
        
        // Dispatch custom event for components that need to react to theme changes
        window.dispatchEvent(new CustomEvent('themechange', { detail: { theme } }));
    },
    
    /**
     * Toggle between light and dark themes
     */
    toggle() {
        const currentTheme = this.getCurrentTheme();
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        this.setTheme(newTheme);
        return newTheme;
    },
    
    /**
     * Get current theme
     * @returns {string} 'light' or 'dark'
     */
    getCurrentTheme() {
        return document.documentElement.classList.contains('dark') ? 'dark' : 'light';
    },
    
    /**
     * Check if dark mode is active
     * @returns {boolean}
     */
    isDark() {
        return this.getCurrentTheme() === 'dark';
    }
};

// Initialize on load
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => ThemeToggle.init());
} else {
    ThemeToggle.init();
}

// Export for use in other scripts
window.ThemeToggle = ThemeToggle;
