/**
 * BNote Next Generation - Search Integration Component
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
 * Search Integration Component
 * Single Responsibility: Orchestrating search components and topbar integration
 * Glue code that connects all search components together
 */
const SearchIntegration = {
    // DOM elements
    searchInput: null,
    searchContainer: null,
    resultsContainer: null,
    filtersContainer: null,
    clearButton: null,
    
    // State
    isVisible: false,
    initialized: false,
    
    /**
     * Initialize all search components
     */
    init() {
        // Prevent double initialization
        if (this.initialized) return;
        
        // Wait for DOM to be ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.setup());
        } else {
            this.setup();
        }
    },
    
    /**
     * Setup search integration
     */
    setup() {
        // Prevent double setup
        if (this.initialized) return;
        
        // Find search input in topbar (retry if not found yet)
        this.searchInput = document.querySelector('#search-input');
        if (!this.searchInput) {
            // Retry after a short delay (topbar might not be rendered yet)
            setTimeout(() => {
                this.searchInput = document.querySelector('#search-input');
                if (this.searchInput) {
                    this.setup();
                } else {
                    console.warn('Search input not found in topbar after retry');
                }
            }, 200);
            return;
        }
        
        // Mark as initialized
        this.initialized = true;
        
        // Create results container (will be positioned absolutely below search bar)
        this.createResultsContainer();
        
        // Create filters container
        this.createFiltersContainer();
        
        // Setup event listeners
        this.setupEventListeners();
        
        // Setup keyboard shortcut
        this.setupKeyboardShortcut();
        
        // Initialize Search component
        if (typeof Search !== 'undefined') {
            Search.init();
            console.log('Search component initialized');
        } else {
            console.error('Search component not found - make sure search.js is loaded before search-integration.js');
        }
        
        // Wire up Search component callbacks
        this.wireSearchCallbacks();
        
        // Wire up SearchFilters callbacks
        this.wireFilterCallbacks();
        
        // Initialize search button visibility based on current input value
        if (this.searchInput && this.searchInput.value.trim().length > 0) {
            const searchButton = document.getElementById('search-button');
            if (searchButton) {
                searchButton.classList.remove('hidden');
            }
        }
        
        console.log('Search integration setup complete', {
            searchInput: !!this.searchInput,
            resultsContainer: !!this.resultsContainer,
            filtersContainer: !!this.filtersContainer
        });
    },
    
    /**
     * Create results container
     */
    createResultsContainer() {
        // Check if overlay container already exists (use different ID from full-page container)
        let container = document.getElementById('search-results-overlay');
        if (!container) {
            container = document.createElement('div');
            container.id = 'search-results-overlay';
            container.className = 'hidden fixed z-50 bg-card border border-border/40 rounded-lg shadow-lg overflow-y-auto';
            
            // Position below search input (will be positioned dynamically)
            document.body.appendChild(container);
        }
        
        this.resultsContainer = container;
        
        // Create inner container for results if it doesn't exist
        let innerContainer = document.getElementById('search-results-overlay-content');
        if (!innerContainer) {
            innerContainer = document.createElement('div');
            innerContainer.id = 'search-results-overlay-content';
            container.appendChild(innerContainer);
        }
    },
    
    /**
     * Position results container below search input
     */
    positionResultsContainer() {
        if (!this.searchInput || !this.resultsContainer) return;
        
        const rect = this.searchInput.getBoundingClientRect();
        const topbarHeight = rect.bottom;
        
        // Match search bar width and make it very tall
        this.resultsContainer.style.top = (rect.bottom + window.scrollY + 4) + 'px';
        this.resultsContainer.style.left = rect.left + 'px';
        this.resultsContainer.style.width = rect.width + 'px';
        this.resultsContainer.style.maxWidth = rect.width + 'px';
        this.resultsContainer.style.maxHeight = 'calc(100vh - ' + (topbarHeight + 4) + 'px)';
        this.resultsContainer.style.right = 'auto';
    },
    
    /**
     * Create filters container
     */
    createFiltersContainer() {
        if (!this.resultsContainer) return;
        
        // Check if filters container already exists
        let container = document.getElementById('search-filters-overlay');
        if (!container) {
            container = document.createElement('div');
            container.id = 'search-filters-overlay';
            container.className = 'hidden';
            
            // Insert before results content
            const resultsContent = document.getElementById('search-results-overlay-content');
            if (resultsContent && resultsContent.parentNode) {
                resultsContent.parentNode.insertBefore(container, resultsContent);
            } else {
                this.resultsContainer.appendChild(container);
            }
        }
        
        this.filtersContainer = container;
    },
    
    /**
     * Setup event listeners
     */
    setupEventListeners() {
        if (!this.searchInput) return;
        
        // Input change handler
        this.searchInput.addEventListener('input', (e) => {
            console.log('Search input changed:', e.target.value);
            this.handleInputChange(e.target.value);
        });
        
        // Enter key handler - open search results page
        this.searchInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && this.searchInput.value.trim().length >= 2) {
                e.preventDefault();
                this.openSearchResultsPage();
            }
        });
        
        // Focus handler
        this.searchInput.addEventListener('focus', () => {
            if (typeof Search !== 'undefined' && Search.currentResults) {
                this.showResults();
            }
        });
        
        // Reposition on scroll
        window.addEventListener('scroll', () => {
            if (this.isVisible) {
                this.positionResultsContainer();
            }
        }, true);
        
        // Blur handler (with delay to allow clicks)
        this.searchInput.addEventListener('blur', () => {
            setTimeout(() => {
                // Check if focus moved to results container
                const activeElement = document.activeElement;
                if (!this.resultsContainer || !this.resultsContainer.contains(activeElement)) {
                    this.hideResults();
                }
            }, 200);
        });
        
        // Clear button
        const clearBtn = this.searchInput.parentElement?.querySelector('#search-clear-btn');
        if (clearBtn) {
            this.clearButton = clearBtn;
            clearBtn.addEventListener('click', () => {
                this.clearSearch();
            });
        }
        
        // Search button
        const searchBtn = document.getElementById('search-button');
        if (searchBtn) {
            searchBtn.addEventListener('click', () => {
                if (this.searchInput.value.trim().length >= 2) {
                    this.openSearchResultsPage();
                } else {
                    this.searchInput.focus();
                }
            });
        }
        
        // Click outside to close
        document.addEventListener('click', (e) => {
            if (this.isVisible && 
                !this.searchInput.contains(e.target) && 
                !this.resultsContainer.contains(e.target) &&
                !searchBtn?.contains(e.target)) {
                this.hideResults();
            }
        });
    },
    
    /**
     * Open search results page (right column)
     */
    openSearchResultsPage() {
        const query = this.searchInput ? this.searchInput.value.trim() : '';
        if (query.length < 2) {
            // Focus input if query is too short
            if (this.searchInput) {
                this.searchInput.focus();
            }
            return;
        }
        
        // Hide the live results overlay
        this.hideResults();
        
        const filters = typeof Search !== 'undefined' ? (Search.currentFilters || {}) : {};
        
        if (typeof SearchResultsPage !== 'undefined') {
            SearchResultsPage.init(query, filters);
        } else {
            console.error('SearchResultsPage component not available');
        }
    },
    
    /**
     * Setup keyboard shortcut (Cmd+K / Ctrl+K)
     */
    setupKeyboardShortcut() {
        document.addEventListener('keydown', (e) => {
            if (typeof Search !== 'undefined' && Search.handleKeyboardShortcut(e)) {
                this.searchInput?.focus();
                e.preventDefault();
            }
        });
    },
    
    /**
     * Wire up Search component callbacks
     */
    wireSearchCallbacks() {
        if (typeof Search === 'undefined') {
            console.error('Search component not available');
            return;
        }
        
        Search.onSearchStart = () => {
            if (this.resultsContainer) {
                const content = document.getElementById('search-results-content');
                if (content) {
                    const t = (k) => (typeof i18n !== 'undefined' && i18n.t ? i18n.t(k) : k);
                    content.innerHTML = `
                        <div class="text-center py-8 text-muted-foreground text-sm">
                            ${this.escapeHtml(t('js.search.loading') || 'Searching...')}
                        </div>
                    `;
                }
            }
        };
        
        Search.onResultsUpdate = (results) => {
            // Always show overlay when we have results (even if empty or total is 0)
            // This ensures the overlay appears while typing
            console.log('Search.onResultsUpdate called with:', results);
            if (results !== null && results !== undefined) {
                // Show overlay regardless of total count
                console.log('Showing results overlay');
                this.showResults(results);
            } else {
                // Only hide if explicitly null (query too short)
                console.log('Hiding results overlay (null results)');
                this.hideResults();
            }
        };
        
        Search.onSearchError = (error) => {
            if (this.resultsContainer) {
                const content = document.getElementById('search-results-overlay-content');
                if (content && typeof SearchResults !== 'undefined') {
                    SearchResults.renderErrorState(content, error);
                    this.showResults();
                }
            }
        };
    },
    
    /**
     * Wire up SearchFilters callbacks
     */
    wireFilterCallbacks() {
        if (typeof SearchFilters === 'undefined' || typeof Search === 'undefined') {
            console.error('Search or SearchFilters component not available');
            return;
        }
        
        SearchFilters.onFilterChange = (type, value) => {
            const filters = Search.currentFilters || {};
            
            if (value === null) {
                // Remove filter
                if (type === 'module_type') {
                    delete filters.module_type;
                } else {
                    delete filters['date_' + type];
                }
            } else {
                // Set filter
                if (type === 'module_type') {
                    filters.module_type = value;
                } else {
                    filters['date_' + type] = value;
                }
            }
            
            Search.setFilters(filters);
            
            // Reload search if we have a current query
            if (Search.currentQuery && Search.currentQuery.length >= 2) {
                Search.debouncedSearch(Search.currentQuery, filters);
            }
        };
        
        SearchFilters.onClearAll = () => {
            Search.clearFilters();
            
            // Reload search if we have a current query
            if (Search.currentQuery && Search.currentQuery.length >= 2) {
                Search.debouncedSearch(Search.currentQuery, {});
            }
        };
    },
    
    /**
     * Handle search input changes
     * @param {string} value Input value
     */
    handleInputChange(value) {
        const trimmedValue = value ? value.trim() : '';
        const hasValue = trimmedValue.length > 0;
        const hasMinLength = trimmedValue.length >= 2;
        
        // Show/hide clear button
        if (this.clearButton) {
            if (hasValue) {
                this.clearButton.classList.remove('hidden');
            } else {
                this.clearButton.classList.add('hidden');
            }
        }
        
        // Show/hide search button (only when there's a search term)
        const searchButton = document.getElementById('search-button');
        if (searchButton) {
            if (hasValue) {
                searchButton.classList.remove('hidden');
            } else {
                searchButton.classList.add('hidden');
            }
        }
        
        // Clear results if input is empty or too short
        if (!hasMinLength) {
            this.hideResults();
            if (typeof Search !== 'undefined') {
                Search.currentResults = null;
                // Reset filters when clearing search
                Search.currentFilters = {};
                
                // Update filter UI (filters are hidden in overlay anyway)
                // No need to update filter UI when clearing search
            }
            return;
        }
        
        // Show overlay immediately when user starts typing (before API results)
        // This provides instant feedback like the initial implementation
        console.log('handleInputChange: hasMinLength =', hasMinLength, 'isVisible =', this.isVisible, 'resultsContainer =', !!this.resultsContainer);
        if (hasMinLength && !this.isVisible && this.resultsContainer) {
            console.log('Showing overlay immediately');
            this.showResults(null); // Show overlay with loading/empty state
        }
        
        // Debounced search for live results overlay - always trigger search
        if (typeof Search !== 'undefined') {
            // Filters will be reset in debouncedSearch if query changed
            const filters = Search.currentFilters || {};
            // Always perform search to show overlay (even if empty results)
            Search.debouncedSearch(trimmedValue, filters);
        } else {
            console.error('Search component not available');
        }
    },
    
    /**
     * Show search results
     * @param {object} results Optional results to display
     */
    showResults(results = null) {
        console.log('showResults called with:', results, 'resultsContainer:', !!this.resultsContainer);
        if (!this.resultsContainer) {
            console.error('showResults: resultsContainer is null!');
            return;
        }
        
        const displayResults = results || (typeof Search !== 'undefined' ? Search.currentResults : null);
        console.log('displayResults:', displayResults);
        
        // Position container
        this.positionResultsContainer();
        
        // Hide filters in live overlay - filters only appear in full page after headline
        if (this.filtersContainer) {
            this.filtersContainer.classList.add('hidden');
        }
        
        // Always show overlay - display results or loading/empty state
        const content = document.getElementById('search-results-overlay-content');
        console.log('content element:', !!content, 'SearchResults:', typeof SearchResults);
        if (content && typeof SearchResults !== 'undefined') {
            if (displayResults) {
                // We have results - render them (even if total is 0)
                if (displayResults.total > 0) {
                    console.log('Rendering results with total:', displayResults.total);
                    SearchResults.render(displayResults, content);
                    // Attach click handlers for navigation
                    this.attachResultClickHandlers(content);
                } else {
                    // Results object exists but empty - show empty state
                    console.log('Rendering empty state (results.total = 0)');
                    SearchResults.renderEmptyState(content);
                }
            } else {
                // No results yet - show loading or empty state
                // This allows overlay to appear immediately while waiting for API
                console.log('Rendering empty state (no results yet)');
                SearchResults.renderEmptyState(content);
            }
        } else {
            console.error('showResults: content or SearchResults not available');
        }
        
        console.log('Removing hidden class from resultsContainer');
        this.resultsContainer.classList.remove('hidden');
        this.isVisible = true;
        
        // Reposition on window resize (remove old listener first)
        const resizeHandler = this.positionResultsContainer.bind(this);
        window.removeEventListener('resize', resizeHandler);
        window.addEventListener('resize', resizeHandler);
    },
    
    /**
     * Attach click handlers to search result items
     * @param {HTMLElement} container Results container
     */
    attachResultClickHandlers(container) {
        // Use event delegation to handle all clicks in the overlay
        container.addEventListener('click', (e) => {
            // Don't handle clicks on filter chips or filter buttons
            if (e.target.closest('.filter-chip-btn') || e.target.closest('.filter-bubble') || e.target.closest('.filter-clear-btn')) {
                return; // Let filter handlers deal with these
            }
            
            // Intercept event clicks from search overlay to pass search context
            const eventElement = e.target.closest('[data-event-id][data-event-type]');
            if (eventElement) {
                const eventType = eventElement.getAttribute('data-event-type');
                const eventId = eventElement.getAttribute('data-event-id');
                
                if (eventType && eventId && typeof Dashboard !== 'undefined' && Dashboard.openEventDetail) {
                    e.preventDefault();
                    e.stopPropagation();
                    // Hide overlay first
                    this.hideResults();
                    // Get current search query and filters from Search component
                    const searchQuery = typeof Search !== 'undefined' ? Search.currentQuery : '';
                    const searchFilters = typeof Search !== 'undefined' ? Search.currentFilters : {};
                    // Pass search context when opening event detail (await async method)
                    Dashboard.openEventDetail(eventType, parseInt(eventId), true, searchQuery, searchFilters).catch(err => {
                        console.error('Failed to open event detail:', err);
                    });
                    return;
                }
            }
            
            // Handle non-event result items
            const resultItem = e.target.closest('.search-result-item');
            if (resultItem) {
                const itemType = resultItem.getAttribute('data-item-type');
                const itemId = resultItem.getAttribute('data-item-id');
                
                if (itemType === 'user') {
                    // Navigate to user detail (if users page supports it)
                    window.location.href = `users.html?id=${itemId}`;
                } else if (itemType === 'contact') {
                    // Navigate to contact detail (if contacts page supports it)
                    window.location.href = `contacts.html?id=${itemId}`;
                } else if (itemType === 'task') {
                    // Navigate to task detail (if tasks page exists)
                    // For now, just close search
                    this.hideResults();
                } else if (itemType === 'repertoire') {
                    // Navigate to repertoire detail
                    // For now, just close search
                    this.hideResults();
                } else if (itemType === 'location') {
                    // Navigate to location detail
                    // For now, just close search
                    this.hideResults();
                }
            }
        });
    },
    
    /**
     * Hide search results
     */
    hideResults() {
        if (this.resultsContainer) {
            this.resultsContainer.classList.add('hidden');
        }
        this.isVisible = false;
    },
    
    /**
     * Clear search
     */
    clearSearch() {
        if (this.searchInput) {
            this.searchInput.value = '';
        }
        
        if (this.clearButton) {
            this.clearButton.classList.add('hidden');
        }
        
        // Hide search button when cleared
        const searchButton = document.getElementById('search-button');
        if (searchButton) {
            searchButton.classList.add('hidden');
        }
        
        if (typeof Search !== 'undefined') {
            Search.clear();
        }
        this.hideResults();
    },
    
    /**
     * Escape HTML to prevent XSS
     * @param {string} text Text to escape
     * @returns {string} Escaped text
     */
    escapeHtml(text) {
        if (text === null || text === undefined) return '';
        const div = document.createElement('div');
        div.textContent = String(text);
        return div.innerHTML;
    }
};

// Export for use in other scripts
window.SearchIntegration = SearchIntegration;

// Auto-initialize when script loads (with delay to ensure topbar is rendered)
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        // Wait a bit for topbar to be rendered
        setTimeout(() => SearchIntegration.init(), 100);
    });
} else {
    // Wait a bit for topbar to be rendered
    setTimeout(() => SearchIntegration.init(), 100);
}
