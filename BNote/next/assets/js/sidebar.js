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
    init(currentPage = null) {
        this.currentPage = currentPage || this.detectCurrentPage();
        
        // Initialize sidebar functionality
        this.initSidebar();
        this.initCollapse();
        this.highlightCurrentPage();
        this.checkUserPermissions();
    },
    
    /**
     * Detect current page from URL
     */
    detectCurrentPage() {
        const path = window.location.pathname;
        if (path.includes('users.html')) return 'users';
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
        
        // Mobile menu button
        if (mobileMenuBtn) {
            mobileMenuBtn.addEventListener('click', () => {
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
        document.addEventListener('click', (e) => {
            if (window.innerWidth < 1280 && this.sidebarOpen) {
                if (!sidebar.contains(e.target) && 
                    !mobileMenuBtn?.contains(e.target)) {
                    this.toggleSidebar();
                }
            }
        });
        
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
     * Check user permissions and show/hide menu items
     */
    async checkUserPermissions() {
        try {
            // Check if user has access to User Management module
            await UsersApi.list();
            // If successful, show the menu item
            const usersMenuItem = document.getElementById('users-menu-item');
            if (usersMenuItem) {
                usersMenuItem.classList.remove('hidden');
            }
        } catch (error) {
            // If 403 or access denied, hide the menu item
            // Silently fail - user just doesn't have access
            if (error.status === 403 || error.code === 403 || error.message.includes('403') || error.message.includes('Access denied')) {
                const usersMenuItem = document.getElementById('users-menu-item');
                if (usersMenuItem) {
                    usersMenuItem.classList.add('hidden');
                }
            }
        }
    }
};
