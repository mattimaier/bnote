/**
 * BNote Next Generation - App Shell
 *
 * Copyright (C) 2026 BNote Contributors
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * App Shell - Manages master-detail shell and module loading
 */
const AppShell = {
    modules: [],
    currentModule: null,
    currentModuleId: null,
    session: null,
    
    /**
     * Initialize app shell
     */
    async init() {
        console.log('AppShell: Initializing...');
        
        // Check authentication
        this.session = await Auth.checkSession();
        if (!this.session.authenticated) {
            window.location.replace('login.html');
            return;
        }
        
        // Mark body as visible after auth check
        document.body.classList.add('auth-checked');
        
        // Load module registry
        await this.loadModuleRegistry();
        
        // Render shell components
        this.renderShell();
        
        // Initialize sidebar
        if (typeof Sidebar !== 'undefined') {
            const route = Router.parseUrl();
            await Sidebar.init(route.module);
        }
        
        // Initialize mobile navigation
        if (typeof MobileNav !== 'undefined' && typeof MobileNav.init === 'function') {
            await MobileNav.init();
        }
        
        // Initialize search integration
        if (typeof SearchIntegration !== 'undefined' && typeof SearchIntegration.init === 'function') {
            // Delay search init to ensure topbar is rendered
            setTimeout(() => {
                SearchIntegration.init();
            }, 100);
        }
        
        // Load initial module based on URL
        await this.handleInitialRoute();
        
        // Ensure translations are applied after all content is loaded
        if (typeof i18n !== 'undefined' && typeof i18n.translatePage === 'function') {
            setTimeout(() => {
                i18n.translatePage();
            }, 200);
        }
    },
    
    /**
     * Load module registry from modules.json
     */
    async loadModuleRegistry() {
        try {
            const response = await fetch('modules.json');
            const data = await response.json();
            this.modules = data.modules || [];
            console.log('AppShell: Loaded modules:', this.modules);
        } catch (error) {
            console.error('AppShell: Failed to load modules.json:', error);
            // Fallback to default modules
            this.modules = [
                { id: 1, name: 'Start', route: 'dashboard', icon: 'layout-dashboard', i18n: 'js.sidebar.dashboard', html: 'modules/dashboard.html', js: 'dashboard.js' },
                { id: 3, name: 'Kontakte', route: 'contacts', icon: 'users', i18n: 'js.sidebar.contacts', html: 'modules/contacts.html', js: 'contacts.js' },
                { id: 4, name: 'Benutzer', route: 'users', icon: 'user', i18n: 'js.sidebar.users', html: 'modules/users.html', js: 'users.js' }
            ];
        }
    },
    
    /**
     * Render shell components (sidebar, topbar)
     */
    renderShell() {
        // Render topbar
        const topbarContainer = document.getElementById('topbar-container');
        if (topbarContainer && typeof Components !== 'undefined') {
            topbarContainer.innerHTML = Components.renderTopbar();
            // Initialize theme toggle
            if (typeof Components.initThemeToggle === 'function') {
                Components.initThemeToggle();
            }
        }
        
        // Render sidebar
        const sidebarContainer = document.getElementById('sidebar-container');
        if (sidebarContainer && typeof Components !== 'undefined' && typeof Components.renderSidebar === 'function') {
            sidebarContainer.innerHTML = Components.renderSidebar();
        }
        
        // Sidebar modules will be loaded by Sidebar.init()
    },
    
    /**
     * Handle initial route from URL
     */
    async handleInitialRoute() {
        const route = Router.parseUrl();
        console.log('AppShell: Handling initial route:', route);
        
        if (route.entity && route.id) {
            // Entity detail view - handled by entity-detail.html
            // This shouldn't happen in app.html, but handle gracefully
            console.warn('AppShell: Entity detail route detected in app.html, redirecting...');
            window.location.href = NavigationService.getEntityUrl(route.entity, route.id, route.module, route.mode);
            return;
        }
        
        // Load module
        await this.switchModule(route.module);
    },
    
    /**
     * Switch to a different module
     * @param {string} moduleRoute - Module route (e.g., 'dashboard', 'contacts')
     */
    async switchModule(moduleRoute) {
        console.log('AppShell: Switching to module:', moduleRoute);
        
        // Find module in registry
        const module = this.modules.find(m => m.route === moduleRoute);
        if (!module) {
            console.error('AppShell: Module not found:', moduleRoute);
            if (moduleRoute !== 'dashboard') {
                return await this.switchModule('dashboard');
            }
            return;
        }
        
        // Unload current module
        if (this.currentModule && typeof this.currentModule.cleanup === 'function') {
            this.currentModule.cleanup();
        }
        
        // Load module JavaScript
        await this.loadModule(module);
        
        // Get module class
        const moduleClassName = this.getModuleClassName(module);
        const ModuleClass = window[moduleClassName];
        
        if (!ModuleClass) {
            console.error('AppShell: Module class not found:', moduleClassName);
            return;
        }
        
        // Instantiate module
        this.currentModuleId = module.id;
        this.currentModule = new ModuleClass();
        
        // Initialize module
        const detailArea = document.getElementById('detail-area');
        if (detailArea) {
            // Clear existing content
            detailArea.innerHTML = '';
            
            await this.currentModule.init(this.session, detailArea);
        }
        
        // Update sidebar highlight
        if (typeof Sidebar !== 'undefined') {
            Sidebar.currentPage = moduleRoute;
            Sidebar.highlightCurrentPage();
        }
    },
    
    /**
     * Load module JavaScript file
     * @param {Object} module - Module configuration
     */
    async loadModule(module) {
        return new Promise((resolve, reject) => {
            // Check if already loaded
            const moduleClassName = this.getModuleClassName(module);
            if (window[moduleClassName]) {
                resolve();
                return;
            }
            
            // Load script
            const script = document.createElement('script');
            script.src = `assets/js/modules/${module.js}`;
            script.onload = () => resolve();
            script.onerror = () => reject(new Error(`Failed to load module: ${module.js}`));
            document.head.appendChild(script);
        });
    },
    
    /**
     * Get module class name from module config
     * @param {Object} module - Module configuration
     * @returns {string} Class name
     */
    getModuleClassName(module) {
        // Convert route to PascalCase: 'dashboard' -> 'DashboardModule'
        const route = module.route;
        const pascalCase = route.charAt(0).toUpperCase() + route.slice(1);
        return `${pascalCase}Module`;
    }
};

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => AppShell.init());
} else {
    AppShell.init();
}

// Export for use in other scripts
window.AppShell = AppShell;
