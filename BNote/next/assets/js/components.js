/**
 * BNote Next Generation - Component Loader
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
 * Component Loader
 * Handles loading and rendering reusable components
 */
const Components = {
    /**
     * Render topbar component
     * @param {Object} options - Configuration options
     * @param {string} options.searchPlaceholder - Placeholder text for search input
     * @param {string} options.searchPlaceholderI18n - i18n key for search placeholder
     */
    renderTopbar(options = {}) {
        const t = (k) => (typeof i18n !== 'undefined' && i18n.t ? i18n.t(k) : k);
        const searchPlaceholderI18n = options.searchPlaceholderI18n || '';
        // Use translation if available, otherwise use provided placeholder
        const searchPlaceholder = searchPlaceholderI18n && typeof i18n !== 'undefined' && i18n.t 
            ? i18n.t(searchPlaceholderI18n) 
            : (options.searchPlaceholder || 'Search...');
        
        return `
        <!-- Top Header Bar -->
        <header
            class="sticky top-0 z-50 w-full border-b border-border/40 bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/60">
            <div class="flex h-16 items-center justify-between px-4 lg:px-6 gap-4">
                <!-- Hamburger button - visible only on small screens -->
                <button id="mobile-menu-btn"
                    class="md:hidden h-9 w-9 text-muted-foreground hover:text-foreground hover:bg-muted/60 rounded-md flex items-center justify-center"
                    title="Menu" aria-label="Menu">
                    <i data-lucide="menu" class="h-5 w-5"></i>
                </button>

                <!-- Left: Search -->
                <div class="flex flex-1 max-w-sm">
                    <div class="relative w-full">
                        <i data-lucide="search"
                            class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground/60"></i>
                        <input type="text" ${searchPlaceholderI18n ? `data-i18n-placeholder="${searchPlaceholderI18n}"` : ''} placeholder="${this.escapeHtml(searchPlaceholder)}"
                            class="pl-9 h-9 bg-muted/40 border border-border/40 text-sm text-foreground placeholder:text-muted-foreground/60 focus:border-input focus:bg-muted/60 focus:ring-1 focus:ring-primary/30 rounded-md w-full px-3" />
                    </div>
                </div>

                <!-- Right side actions (reusable user component) -->
                <div id="user-info-container" class="flex items-center gap-2 ml-auto">
                    <!-- Theme toggle button -->
                    <button id="theme-toggle-btn" class="h-9 w-9 p-0 hover:bg-muted/60 text-muted-foreground hover:text-foreground transition-colors rounded-md flex items-center justify-center"
                        title="Toggle theme" aria-label="Toggle theme">
                        <i data-lucide="sun" class="h-5 w-5 hidden dark:block"></i>
                        <i data-lucide="moon" class="h-5 w-5 block dark:hidden"></i>
                    </button>
                    <div class="h-6 w-px bg-border/30 hidden sm:block"></div>
                    <button
                        class="user-info-btn flex items-center gap-3 pl-3 pr-2 py-1 rounded-lg hover:bg-muted/50 transition-colors group"
                        onclick="Auth.logout()">
                        <div id="user-avatar" class="user-info-initials h-8 w-8 rounded-full border-2 border-primary/20 bg-primary/10 text-primary text-xs font-semibold flex items-center justify-center shrink-0">
                            <span id="user-initials">U</span>
                        </div>
                        <div class="hidden sm:block text-left">
                            <p class="text-xs font-semibold leading-tight text-foreground" id="user-name">User</p>
                        </div>
                    </button>
                </div>
            </div>
        </header>
        `;
    },

    /**
     * Render sidebar component
     */
    renderSidebar() {
        return `
        <!-- Left module sidebar - Hidden on small screens, visible on larger screens -->
        <aside id="sidebar"
            class="hidden md:flex flex-col h-full bg-sidebar border-r border-sidebar-border w-64 shrink-0"
            style="position: relative !important; z-index: auto !important;">
            <!-- Header with brand -->
            <div id="sidebar-header" class="flex items-center h-16 px-4 lg:px-6">
                <div id="sidebar-brand" class="flex items-center gap-3">
                    <div
                        class="h-9 w-9 rounded-lg bg-gradient-to-br from-primary/30 to-primary/10 flex items-center justify-center ring-1 ring-primary/20">
                        <img src="../style/images/BNote_Logo_white_transparent.svg" alt="BNote" class="h-5 w-5" style="filter: brightness(0) saturate(100%) invert(58%) sepia(95%) saturate(2878%) hue-rotate(195deg) brightness(102%) contrast(101%);" />
                    </div>
                    <div class="flex flex-col">
                        <span class="font-semibold text-sidebar-foreground text-sm">BNote</span>
                    </div>
                </div>
            </div>

            <!-- Modules list -->
            <nav id="sidebar-nav" class="flex-1 overflow-y-auto p-3 space-y-1.5">
                <!-- Modules will be dynamically loaded here -->
            </nav>
        </aside>
        `;
    },

    /**
     * Initialize theme toggle button
     */
    initThemeToggle() {
        const themeToggleBtn = document.getElementById('theme-toggle-btn');
        if (themeToggleBtn && typeof ThemeToggle !== 'undefined') {
            themeToggleBtn.addEventListener('click', () => {
                ThemeToggle.toggle();
                // Update icons
                if (typeof lucide !== 'undefined') {
                    lucide.createIcons();
                }
            });
        }
    },
    
    /**
     * Escape HTML to prevent XSS
     */
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
};

// Export for use in other scripts
window.Components = Components;
