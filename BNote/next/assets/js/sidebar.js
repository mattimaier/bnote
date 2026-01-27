/**
 * BNote Next Generation - Sidebar Module
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
 * Sidebar Module
 * Handles sidebar functionality across all pages
 * Always visible, independent of dashboard
 */
const Sidebar = {
    // State
    sidebarOpen: true,
    sidebarCollapsed: false,
    currentPage: null,
    
    /**
     * Initialize sidebar
     * @param {string} currentPage - Current page identifier (e.g., 'dashboard', 'users')
     */
    async init(currentPage = null) {
        this.currentPage = currentPage || this.detectCurrentPage();
        
        // Initialize sidebar functionality
        this.initSidebar();
        this.initCollapse();
        
        // Load and render modules dynamically
        await this.loadModules();
        
        // Highlight current page after modules are rendered
        this.highlightCurrentPage();
    },
    
    /**
     * Detect current page from URL
     */
    detectCurrentPage() {
        const path = window.location.pathname;
        if (path.includes('users.html')) return 'users';
        if (path.includes('contacts.html')) return 'contacts';
        if (path.includes('dashboard.html')) return 'dashboard';
        return 'dashboard'; // Default
    },
    
    /**
     * Initialize sidebar functionality
     */
    initSidebar() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebar-overlay');
        const mobileMenuBtn = document.getElementById('mobile-menu-btn');
        
        if (!sidebar) return;
        
        // Mobile menu button - use both onclick (from HTML) and event listener as fallback
        if (mobileMenuBtn) {
            // Remove any existing onclick to avoid double-triggering
            mobileMenuBtn.onclick = null;
            mobileMenuBtn.addEventListener('click', (e) => {
                e.stopPropagation(); // Prevent document click handler from firing
                this.toggleSidebar();
            });
        }
        
        // Close sidebar on mobile when clicking overlay
        if (overlay) {
            overlay.addEventListener('click', () => {
                this.toggleSidebar();
            });
        }
        
        // Close sidebar on mobile when clicking outside
        // Use a separate handler that doesn't interfere with other click handlers
        this._sidebarCloseHandler = (e) => {
            if (window.innerWidth < 1280 && this.sidebarOpen) {
                // Don't close if clicking the mobile menu button (it will toggle itself)
                if (mobileMenuBtn && mobileMenuBtn.contains(e.target)) {
                    return; // Let the button's own handler toggle it
                }
                // Don't close if clicking inside sidebar
                if (sidebar.contains(e.target)) {
                    return;
                }
                // Close if clicking outside
                this.toggleSidebar();
            }
        };
        document.addEventListener('click', this._sidebarCloseHandler);
        
        // Handle window resize
        let resizeTimeout;
        window.addEventListener('resize', () => {
            clearTimeout(resizeTimeout);
            resizeTimeout = setTimeout(() => {
                if (window.innerWidth >= 1280) {
                    // Desktop: sidebar is always visible (xl:translate-x-0)
                    // Ensure overlay is hidden
                    if (overlay) overlay.classList.add('hidden');
                    this.sidebarOpen = true;
                } else {
                    // Mobile/Tablet: hide sidebar if it was open
                    if (this.sidebarOpen) {
                        sidebar.classList.add('-translate-x-full');
                        if (overlay) overlay.classList.add('hidden');
                        this.sidebarOpen = false;
                    }
                }
            }, 150);
        });
        
        // Initialize sidebar state based on screen size
        // Desktop: xl:translate-x-0 makes it always visible
        // Mobile: -translate-x-full hides it by default
        if (window.innerWidth >= 1280) {
            // Desktop: sidebar is always visible (xl:translate-x-0)
            this.sidebarOpen = true;
        } else {
            // Mobile/Tablet: sidebar is hidden by default
            this.sidebarOpen = false;
        }
    },
    
    /**
     * Initialize collapse functionality
     */
    initCollapse() {
        const collapseBtn = document.getElementById('sidebar-collapse-btn');
        if (!collapseBtn) return;
        
        collapseBtn.addEventListener('click', () => {
            this.toggleSidebarCollapse();
        });
    },
    
    /**
     * Toggle sidebar open/closed (mobile/tablet)
     */
    toggleSidebar() {
        this.sidebarOpen = !this.sidebarOpen;
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebar-overlay');
        
        if (!sidebar) return;
        
        // Only toggle on mobile/tablet
        if (window.innerWidth < 1280) {
            if (this.sidebarOpen) {
                sidebar.classList.remove('-translate-x-full');
                if (overlay) overlay.classList.remove('hidden');
            } else {
                sidebar.classList.add('-translate-x-full');
                if (overlay) overlay.classList.add('hidden');
            }
        }
    },
    
    /**
     * Toggle sidebar collapse (desktop)
     */
    toggleSidebarCollapse() {
        this.sidebarCollapsed = !this.sidebarCollapsed;
        const sidebar = document.getElementById('sidebar');
        const collapseBtn = document.getElementById('sidebar-collapse-btn');
        const icon = collapseBtn?.querySelector('i[data-lucide]');
        const brand = document.getElementById('sidebar-brand');
        
        if (!sidebar) return;
        
        if (this.sidebarCollapsed) {
            sidebar.classList.remove('w-64');
            sidebar.classList.add('w-20');
            if (collapseBtn) collapseBtn.setAttribute('title', 'Expand sidebar');
            if (icon) {
                icon.setAttribute('data-lucide', 'chevron-right');
            }
            // Hide text in nav items and brand text
            sidebar.querySelectorAll('.sidebar-text, .sidebar-badge').forEach(el => {
                el.classList.add('hidden');
            });
            if (brand) {
                brand.querySelector('div:last-child')?.classList.add('hidden');
            }
        } else {
            sidebar.classList.remove('w-20');
            sidebar.classList.add('w-64');
            if (collapseBtn) collapseBtn.setAttribute('title', 'Collapse sidebar');
            if (icon) {
                icon.setAttribute('data-lucide', 'chevron-left');
            }
            // Show text in nav items and brand text
            sidebar.querySelectorAll('.sidebar-text, .sidebar-badge').forEach(el => {
                el.classList.remove('hidden');
            });
            if (brand) {
                brand.querySelector('div:last-child')?.classList.remove('hidden');
            }
        }
        
        // Reinitialize Lucide icons
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    },
    
    /**
     * Highlight current page in sidebar
     */
    highlightCurrentPage() {
        if (!this.currentPage) return;
        
        // Remove active state from all menu items
        const allMenuItems = document.querySelectorAll('#sidebar nav a');
        allMenuItems.forEach(item => {
            // Remove active classes
            item.classList.remove('bg-primary/12', 'text-primary', 'font-semibold', 'shadow-sm');
            // Add inactive classes (but preserve existing classes if they're already there)
            if (!item.classList.contains('bg-primary/12')) {
                item.classList.add('text-sidebar-foreground/70', 'text-sm', 'font-medium');
            }
        });
        
        // Add active state to current page
        const currentMenuItem = document.querySelector(`#sidebar nav a[data-page="${this.currentPage}"]`);
        if (currentMenuItem) {
            currentMenuItem.classList.remove('text-sidebar-foreground/70', 'text-sm', 'font-medium');
            currentMenuItem.classList.add('bg-primary/12', 'text-primary', 'font-semibold', 'shadow-sm');
        }
    },
    
    /**
     * Load modules from API and render them
     */
    async loadModules() {
        try {
            console.log('Sidebar: Loading modules from API...');
            console.log('Sidebar: AuthApi available?', typeof AuthApi !== 'undefined');
            console.log('Sidebar: AuthApi.getModules available?', typeof AuthApi !== 'undefined' && typeof AuthApi.getModules === 'function');
            
            // Fallback: use api directly if AuthApi.getModules doesn't exist
            let response;
            if (typeof AuthApi !== 'undefined' && typeof AuthApi.getModules === 'function') {
                response = await AuthApi.getModules();
            } else if (typeof api !== 'undefined' && typeof api.get === 'function') {
                console.log('Sidebar: Using api.get directly as fallback');
                response = await api.get('auth', 'getModules');
            } else {
                throw new Error('Neither AuthApi.getModules nor api.get is available');
            }
            
            console.log('Sidebar: Received response:', response);
            
            // Handle both array response and object with modules property
            let modules = null;
            if (Array.isArray(response)) {
                modules = response;
            } else if (response && Array.isArray(response.modules)) {
                modules = response.modules;
            } else if (response && response.data && Array.isArray(response.data.modules)) {
                modules = response.data.modules;
            }
            
            console.log('Sidebar: Extracted modules:', modules);
            console.log('Sidebar: Module count:', modules ? modules.length : 0);
            
            if (!modules || modules.length === 0) {
                console.warn('Sidebar: No modules returned from API, using fallback');
                // Fallback: show at least dashboard
                this.renderModules([{
                    id: 1,
                    name: 'Start',
                    route: 'dashboard.html',
                    icon: 'layout-dashboard',
                    i18n: 'js.sidebar.dashboard'
                }]);
            } else {
                this.renderModules(modules);
            }
        } catch (error) {
            console.error('Sidebar: Failed to load modules:', error);
            console.error('Sidebar: Error details:', {
                message: error.message,
                status: error.status,
                code: error.code
            });
            // Fallback: show at least dashboard
            this.renderModules([{
                id: 1,
                name: 'Start',
                route: 'dashboard.html',
                icon: 'layout-dashboard',
                i18n: 'js.sidebar.dashboard'
            }]);
        }
    },

    /**
     * Render modules in the sidebar navigation
     * @param {Array} modules Array of module objects with {id, name, route, icon, i18n}
     */
    renderModules(modules) {
        console.log('Sidebar: renderModules called with:', modules);
        
        const nav = document.getElementById('sidebar-nav');
        if (!nav) {
            console.error('Sidebar: nav container not found!');
            return;
        }

        if (!modules || modules.length === 0) {
            console.warn('Sidebar: No modules to render');
            nav.innerHTML = '<!-- No modules available -->';
            return;
        }

        const t = (k) => (typeof i18n !== 'undefined' && i18n.t ? i18n.t(k) : k);
        
        // Determine current page route for highlighting
        const currentRoute = window.location.pathname.split('/').pop() || 'dashboard.html';
        
        const modulesHtml = modules.map(module => {
            const isActive = module.route === currentRoute || 
                           (module.route === 'dashboard.html' && currentRoute === 'index.html');
            const activeClasses = isActive 
                ? 'bg-primary/12 text-primary font-semibold shadow-sm' 
                : 'text-sidebar-foreground/70 hover:text-sidebar-foreground hover:bg-sidebar-accent/60 text-sm font-medium';
            
            const label = t(module.i18n) || module.name;
            const pageId = module.route.replace('.html', '');
            
            console.log(`Sidebar: Rendering module ${module.name} (${module.route})`);
            
            return `
                <a href="${module.route}" data-page="${pageId}"
                    class="relative flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all duration-200 ${activeClasses}">
                    <i data-lucide="${module.icon}" class="h-5 w-5 shrink-0"></i>
                    <span class="flex-1 truncate sidebar-text" data-i18n="${module.i18n}">${label}</span>
                </a>
            `;
        }).join('');

        console.log('Sidebar: Generated HTML length:', modulesHtml.length);
        nav.innerHTML = modulesHtml;

        // Reinitialize Lucide icons for the new elements
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }

        // Translate page to update i18n labels
        if (typeof i18n !== 'undefined' && typeof i18n.translatePage === 'function') {
            i18n.translatePage();
        }
        
        console.log('Sidebar: Modules rendered successfully');
    },
    
    /**
     * Cleanup event listeners (for page navigation)
     */
    cleanup() {
        if (this._sidebarCloseHandler) {
            document.removeEventListener('click', this._sidebarCloseHandler);
            this._sidebarCloseHandler = null;
        }
    }
};
