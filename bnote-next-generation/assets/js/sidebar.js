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
    currentPage: null,
    
    /**
     * Initialize sidebar
     * @param {string} currentPage - Current page identifier (e.g., 'dashboard', 'users')
     */
    async init(currentPage = null) {
        this.currentPage = currentPage || this.detectCurrentPage();
        
        // Listen for i18n loaded event to retranslate sidebar
        window.addEventListener('i18n:loaded', () => {
            this.translateSidebar();
        });
        
        // Load and render modules dynamically
        await this.loadModules();
        
        // Highlight current page after modules are rendered
        this.highlightCurrentPage();
        
        // Translate sidebar after modules are rendered
        this.translateSidebar();
    },
    
    /**
     * Translate sidebar elements
     */
    translateSidebar() {
        const nav = document.getElementById('sidebar-nav');
        if (!nav) return;
        
        if (typeof i18n !== 'undefined' && i18n.t && Object.keys(i18n.translations).length > 0) {
            const sidebarElements = nav.querySelectorAll('[data-i18n]');
            sidebarElements.forEach(el => {
                const key = el.getAttribute('data-i18n');
                if (key) {
                    const translated = i18n.t(key);
                    if (translated && translated !== key) {
                        // Find the span element with the text
                        const span = el.querySelector('span.sidebar-text');
                        if (span) {
                            span.textContent = translated;
                        }
                    }
                }
            });
        }
    },
    
    /**
     * Detect current page from URL
     */
    detectCurrentPage() {
        // Check query parameter first (new routing)
        const urlParams = new URLSearchParams(window.location.search);
        const moduleParam = urlParams.get('module');
        if (moduleParam) return moduleParam;
        
        // Check hash (backward compatibility: #/dashboard)
        const hash = window.location.hash;
        if (hash && hash.startsWith('#/')) {
            const route = hash.substring(2).split('?')[0];
            if (route) return route;
        }
        
        // Fallback to pathname (old routing)
        const path = window.location.pathname;
        if (path.includes('users.html')) return 'users';
        if (path.includes('contacts.html')) return 'contacts';
        if (path.includes('dashboard.html')) return 'dashboard';
        if (path.includes('app.html')) return 'dashboard'; // app.html defaults to dashboard
        return 'dashboard'; // Default
    },
    
    
    /**
     * Highlight current page in sidebar
     */
    highlightCurrentPage() {
        // Get current route from query parameter or hash if not set
        if (!this.currentPage) {
            const urlParams = new URLSearchParams(window.location.search);
            this.currentPage = urlParams.get('module') || 'dashboard';
            
            // Check hash for backward compatibility
            const hash = window.location.hash;
            if (hash && hash.startsWith('#/')) {
                this.currentPage = hash.substring(2).split('?')[0] || 'dashboard';
            }
        }
        
        // Remove active state from all menu items
        const allMenuItems = document.querySelectorAll('#sidebar nav a');
        allMenuItems.forEach(item => {
            // Remove active classes
            item.classList.remove('bg-primary/12', 'text-primary', 'font-semibold', 'shadow-sm');
            // Add inactive classes
            item.classList.add('text-sidebar-foreground/70', 'text-sm', 'font-medium');
        });
        
        // Add active state to current page (check both data-module-route and data-page)
        const currentMenuItem = document.querySelector(`#sidebar nav a[data-module-route="${this.currentPage}"]`) || 
                               document.querySelector(`#sidebar nav a[data-page="${this.currentPage}"]`);
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
                    route: 'dashboard',
                    icon: 'layout-dashboard',
                    i18n: 'js.sidebar.dashboard'
                }]);
            } else {
                // Map API modules to our format (remove .html extension from routes)
                const mappedModules = modules.map(m => ({
                    id: m.id,
                    name: m.name,
                    route: m.route ? m.route.replace('.html', '') : m.name.toLowerCase(),
                    icon: m.icon || 'circle',
                    i18n: m.i18n || `js.sidebar.${m.name.toLowerCase()}`
                }));
                this.renderModules(mappedModules);
            }
        } catch (error) {
            console.error('Sidebar: Failed to load modules:', error);
            console.error('Sidebar: Error details:', {
                message: error.message,
                status: error.status,
                code: error.code
            });
            // Fallback: use modules.json if API fails
            try {
                const response = await fetch('modules.json');
                const data = await response.json();
                if (data.modules && data.modules.length > 0) {
                    this.renderModules(data.modules);
                    return;
                }
            } catch (fetchError) {
                console.error('Sidebar: Failed to load modules.json:', fetchError);
            }
            // Final fallback: show at least dashboard
            this.renderModules([{
                id: 1,
                name: 'Start',
                route: 'dashboard',
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
        
        // Determine current page route for highlighting (check query param first)
        const urlParams = new URLSearchParams(window.location.search);
        const moduleParam = urlParams.get('module');
        const currentRoute = moduleParam || window.location.pathname.split('/').pop() || 'dashboard';
        
        const modulesHtml = modules.map(module => {
            // Extract route without .html extension
            const moduleRoute = module.route.replace('.html', '');
            const isActive = moduleRoute === currentRoute || 
                           (moduleRoute === 'dashboard' && currentRoute === 'index.html');
            const activeClasses = isActive 
                ? 'bg-primary/12 text-primary font-semibold shadow-sm' 
                : 'text-sidebar-foreground/70 hover:text-sidebar-foreground hover:bg-sidebar-accent/60 text-sm font-medium';
            
            const label = t(module.i18n) || module.name;
            const pageId = moduleRoute;
            
            console.log(`Sidebar: Rendering module ${module.name} (${moduleRoute})`);
            
            // Use NavigationService for module URLs (app.html?module=route)
            const moduleUrl = (typeof NavigationService !== 'undefined' && NavigationService.getModuleUrl) 
                ? NavigationService.getModuleUrl(moduleRoute)
                : `app.html?module=${moduleRoute}`;
            
            return `
                <a href="${moduleUrl}" data-module-route="${moduleRoute}" data-page="${pageId}"
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

        // Translate sidebar after rendering
        this.translateSidebar();
        
        console.log('Sidebar: Modules rendered successfully');
    },
    
    /**
     * Cleanup event listeners (for page navigation)
     */
    cleanup() {
        // No cleanup needed for simple responsive sidebar
    }
};
