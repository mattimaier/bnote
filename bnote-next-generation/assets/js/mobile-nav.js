/**
 * BNote Next Generation - Mobile Navigation Module
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
 * Mobile Navigation Module
 * Full-screen modal navigation for mobile devices
 */
const MobileNav = {
    // State
    isOpen: false,
    modules: null,
    currentPage: null,
    
    /**
     * Initialize mobile navigation
     */
    async init() {
        this.currentPage = this.detectCurrentPage();
        
        // Render the modal structure
        this.render();
        
        // Set up event listeners
        this.initEventListeners();
        
        // Load modules
        await this.loadModules();
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
     * Render the mobile navigation modal structure
     */
    render() {
        // Get logo path using BNoteConfig if available
        let logoPath;
        if (typeof BNoteConfig !== 'undefined' && BNoteConfig && typeof BNoteConfig.getBNoteResource === 'function') {
            logoPath = BNoteConfig.getBNoteResource('style/images/BNote_Logo_white_transparent.svg');
        } else {
            // Fallback: calculate absolute path manually
            // From /bnote/bnote-next-generation/*.html, go up one level to /bnote/, then into BNote/
            const pathname = window.location.pathname;
            const bnoteNextGenIndex = pathname.indexOf('/bnote-next-generation');
            if (bnoteNextGenIndex !== -1) {
                const basePath = pathname.substring(0, bnoteNextGenIndex);
                logoPath = basePath + (basePath.endsWith('/') ? '' : '/') + 'BNote/style/images/BNote_Logo_white_transparent.svg';
            } else {
                // Try other folder names for backward compatibility
                const bnoteNextIndex = pathname.indexOf('/BNoteNext');
                const nextIndex = pathname.indexOf('/next');
                if (bnoteNextIndex !== -1) {
                    const basePath = pathname.substring(0, bnoteNextIndex);
                    logoPath = basePath + (basePath.endsWith('/') ? '' : '/') + 'BNote/style/images/BNote_Logo_white_transparent.svg';
                } else if (nextIndex !== -1) {
                    const basePath = pathname.substring(0, nextIndex);
                    logoPath = basePath + (basePath.endsWith('/') ? '' : '/') + 'BNote/style/images/BNote_Logo_white_transparent.svg';
                } else {
                    // Last resort: use absolute path from root
                    logoPath = '/BNote/style/images/BNote_Logo_white_transparent.svg';
                }
            }
        }
        
        // Debug: log the calculated path
        console.log('MobileNav.render: logoPath =', logoPath);
        
        // Check if modal already exists
        let modal = document.getElementById('mobile-nav-modal');
        if (modal) {
            // Update logo src if modal already exists
            const logoImg = document.getElementById('mobile-nav-logo');
            if (logoImg) {
                logoImg.src = logoPath;
                console.log('MobileNav.render: Updated existing logo src to', logoPath);
            }
            return; // Already rendered
        }
        
        // Create modal container
        modal = document.createElement('div');
        modal.id = 'mobile-nav-modal';
        modal.className = 'fixed inset-0 z-[100] hidden';
        modal.innerHTML = `
            <!-- Backdrop -->
            <div id="mobile-nav-backdrop" class="fixed inset-0 bg-black/50 backdrop-blur-sm"></div>
            
            <!-- Fullscreen Modal Content -->
            <div class="fixed inset-0 bg-background flex flex-col">
                <!-- Header with BNote Logo -->
                <div class="flex items-center justify-between h-16 px-4 lg:px-6 border-b border-border/40">
                    <div id="mobile-nav-brand" class="flex items-center gap-3">
                        <div class="h-9 w-9 rounded-lg bg-gradient-to-br from-primary/30 to-primary/10 flex items-center justify-center ring-1 ring-primary/20">
                            <img id="mobile-nav-logo" src="${logoPath}" alt="BNote" class="h-5 w-5" style="filter: brightness(0) saturate(100%) invert(58%) sepia(95%) saturate(2878%) hue-rotate(195deg) brightness(102%) contrast(101%);" />
                        </div>
                        <div class="flex flex-col">
                            <span class="font-semibold text-foreground text-sm">BNote</span>
                        </div>
                    </div>
                    <button id="mobile-nav-close-btn" 
                        class="h-9 w-9 text-muted-foreground hover:text-foreground hover:bg-muted/60 rounded-md flex items-center justify-center transition-colors"
                        title="Close" aria-label="Close">
                        <i data-lucide="x" class="h-5 w-5"></i>
                    </button>
                </div>
                
                <!-- Modules List -->
                <div id="mobile-nav-modules" class="flex-1 overflow-y-auto p-4 space-y-2">
                    <!-- Modules will be loaded here -->
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        
        // Initialize Lucide icons
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    },
    
    /**
     * Initialize event listeners
     */
    initEventListeners() {
        const modal = document.getElementById('mobile-nav-modal');
        const backdrop = document.getElementById('mobile-nav-backdrop');
        const closeBtn = document.getElementById('mobile-nav-close-btn');
        const hamburgerBtn = document.getElementById('mobile-menu-btn');
        
        // Hamburger button click
        if (hamburgerBtn) {
            hamburgerBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                this.open();
            });
        }
        
        // Close button click
        if (closeBtn) {
            closeBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                this.close();
            });
        }
        
        // Backdrop click
        if (backdrop) {
            backdrop.addEventListener('click', () => {
                this.close();
            });
        }
        
        // Escape key to close
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.isOpen) {
                this.close();
            }
        });

        // Close on resize to desktop; always restore body scroll
        window.addEventListener('resize', () => {
            if (window.innerWidth >= 768 && this.isOpen) {
                this.close();
            }
        });
    },
    
    /**
     * Open mobile navigation modal
     */
    open() {
        // Only open on small screens
        if (window.innerWidth >= 768) {
            return;
        }
        
        const modal = document.getElementById('mobile-nav-modal');
        if (!modal) {
            this.render();
        }
        
        this.isOpen = true;
        modal.classList.remove('hidden');
        
        // Prevent body scroll
        document.body.style.overflow = 'hidden';
        
        // Reinitialize Lucide icons
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
        
        // Re-render modules if they're already loaded
        if (this.modules) {
            this.renderModules(this.modules);
        }
    },
    
    /**
     * Close mobile navigation modal
     */
    close() {
        const modal = document.getElementById('mobile-nav-modal');
        this.isOpen = false;
        if (modal) {
            modal.classList.add('hidden');
        }
        // Always restore body scroll (critical for mobile vertical scroll)
        document.body.style.overflow = '';
    },
    
    /**
     * Load modules from API
     */
    async loadModules() {
        try {
            // Reuse the same API call as Sidebar
            let response;
            if (typeof AuthApi !== 'undefined' && typeof AuthApi.getModules === 'function') {
                response = await AuthApi.getModules();
            } else if (typeof api !== 'undefined' && typeof api.get === 'function') {
                response = await api.get('auth', 'getModules');
            } else {
                throw new Error('Neither AuthApi.getModules nor api.get is available');
            }
            
            // Handle both array response and object with modules property
            let modules = null;
            if (Array.isArray(response)) {
                modules = response;
            } else if (response && Array.isArray(response.modules)) {
                modules = response.modules;
            } else if (response && response.data && Array.isArray(response.data.modules)) {
                modules = response.data.modules;
            }
            
            if (!modules || modules.length === 0) {
                // Fallback: show at least dashboard
                modules = [{
                    id: 1,
                    name: 'Start',
                    route: 'dashboard.html',
                    icon: 'layout-dashboard',
                    i18n: 'js.sidebar.dashboard'
                }];
            }
            
            this.modules = modules;
            this.renderModules(modules);
        } catch (error) {
            console.error('MobileNav: Failed to load modules:', error);
            // Fallback: show at least dashboard
            this.modules = [{
                id: 1,
                name: 'Start',
                route: 'dashboard.html',
                icon: 'layout-dashboard',
                i18n: 'js.sidebar.dashboard'
            }];
            this.renderModules(this.modules);
        }
    },
    
    /**
     * Render modules in mobile navigation
     * @param {Array} modules Array of module objects with {id, name, route, icon, i18n}
     */
    renderModules(modules) {
        const container = document.getElementById('mobile-nav-modules');
        if (!container) {
            console.error('MobileNav: modules container not found!');
            return;
        }
        
        if (!modules || modules.length === 0) {
            container.innerHTML = '<p class="text-muted-foreground text-center py-4">No modules available</p>';
            return;
        }
        
        const t = (k) => (typeof i18n !== 'undefined' && i18n.t ? i18n.t(k) : k);
        
        // Determine current page route for highlighting
        const currentRoute = window.location.pathname.split('/').pop() || 'dashboard.html';
        
        const modulesHtml = modules.map(module => {
            const isActive = module.route === currentRoute || 
                           (module.route === 'dashboard.html' && currentRoute === 'index.html');
            const activeClasses = isActive 
                ? 'bg-primary/12 text-primary border-primary/20' 
                : 'bg-card hover:bg-muted/50 text-foreground border-border/40';
            
            const label = t(module.i18n) || module.name;
            const pageId = module.route.replace('.html', '');
            
            return `
                <a href="${module.route}" data-page="${pageId}"
                    class="flex items-center gap-4 px-4 py-4 rounded-xl border transition-all duration-200 ${activeClasses}"
                    onclick="MobileNav.navigate('${module.route}')">
                    <div class="h-12 w-12 rounded-lg bg-primary/10 flex items-center justify-center shrink-0">
                        <i data-lucide="${module.icon}" class="h-6 w-6"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="font-semibold text-base" data-i18n="${module.i18n}">${label}</div>
                    </div>
                    <i data-lucide="chevron-right" class="h-5 w-5 text-muted-foreground shrink-0"></i>
                </a>
            `;
        }).join('');
        
        container.innerHTML = modulesHtml;
        
        // Reinitialize Lucide icons
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
        
        // Translate page elements
        if (typeof i18n !== 'undefined' && typeof i18n.translatePage === 'function') {
            i18n.translatePage();
        }
    },
    
    /**
     * Handle navigation when module is clicked
     * @param {string} route - Route to navigate to
     */
    navigate(route) {
        // Close modal
        this.close();
        
        // Navigate to route
        window.location.href = route;
    }
};

// Export for use in other scripts
window.MobileNav = MobileNav;
