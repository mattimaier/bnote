/**
 * BNote Next Generation - Search Results Page Component
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
 * Search Results Page Component
 * Similar to EventDetail - opens search results in right column
 */
const SearchResultsPage = {
    // State
    currentQuery: '',
    currentFilters: {},
    currentResults: null,
    
    /**
     * Initialize and show search results page
     * @param {string} query Search query
     * @param {object} filters Optional filters
     * @param {boolean} fromPopstate If true, we're navigating back via popstate (use replaceState)
     */
    async init(query, filters = {}, fromPopstate = false) {
        console.log('SearchResultsPage.init called with:', { query, filters, fromPopstate });
        
        // If query is empty, try reading from URL (for bookmark/shared link support)
        let trimmedQuery = query ? query.trim() : '';
        if (!trimmedQuery || trimmedQuery.length < 2) {
            const urlData = this.readUrlParams();
            console.log('Read URL params:', urlData);
            if (urlData.query && urlData.query.length >= 2) {
                trimmedQuery = urlData.query;
                // Use filters from URL if query came from URL
                if (Object.keys(filters).length === 0) {
                    filters = urlData.filters;
                }
            } else {
                console.error('Search query too short and no URL params found');
                return;
            }
        }
        
        // Validate query length
        if (trimmedQuery.length < 2) {
            console.error('Search query too short:', trimmedQuery);
            return;
        }
        
        console.log('Using query:', trimmedQuery, 'filters:', filters);
        
        // Reset filters if query changed (new search)
        if (this.currentQuery && this.currentQuery !== trimmedQuery) {
            console.log('Query changed, resetting filters');
            filters = {};
        }
        
        // Always reset filters for new search (when query is different)
        // This ensures filters are cleared when starting a completely new search
        if (!this.currentQuery || this.currentQuery !== trimmedQuery) {
            filters = {};
        }
        
        this.currentQuery = trimmedQuery;
        this.currentFilters = filters || {};
        
        // Update URL with query (filters not in URL)
        // Use replaceState on initial load since URL is already correct from navigation
        // Only update URL if NOT navigating via popstate (popstate already restored the URL and state)
        if (!fromPopstate) {
            this.updateUrl(true); // Use replaceState to avoid creating duplicate history entry
        }
        
        // Show search container, hide other main content containers - check elements exist
        // On search.html, the container is search-results-content directly
        let searchContainer = document.getElementById('search-results-container');
        if (!searchContainer) {
            // Fallback: check if we're on search.html (has search-results-content directly)
            const contentContainer = document.getElementById('search-results-content');
            if (contentContainer) {
                // Create wrapper if it doesn't exist
                searchContainer = contentContainer;
            } else {
                console.error('search-results-container or search-results-content not found in DOM');
                return;
            }
        }
        
        // Hide all main content containers (works on any page)
        // On dashboard: dashboard-container is inside main, search-results-container is also inside main
        // On contacts/users: main has id, search-results-container is outside main
        const containersToHide = [
            'dashboard-container',
            'event-detail-container',
            'contacts-main-content', // contacts.html main content
            'users-main-content'     // users.html main content
        ];
        
        // Also hide event-detail-container if it exists (should be hidden when showing search results)
        
        containersToHide.forEach(containerId => {
            const container = document.getElementById(containerId);
            if (container) {
                container.classList.add('hidden');
            }
        });
        
        // Hide main content if it exists (for contacts/users pages where search-results-container is outside main)
        // On dashboard.html and search.html, search-results-container is INSIDE main, so we don't hide main
        const mainContent = document.querySelector('main');
        if (mainContent) {
            // Check if search-results-container is inside this main element
            const isSearchContainerInsideMain = mainContent.contains(searchContainer);
            
            // Only hide main if search-results-container is NOT inside it (contacts/users pages)
            if (!isSearchContainerInsideMain) {
                mainContent.classList.add('hidden');
            }
        }
        
        // Show container (remove hidden class if it exists)
        if (searchContainer.classList.contains('hidden')) {
            searchContainer.classList.remove('hidden');
        }
        
        // Ensure container is visible
        searchContainer.style.display = '';
        searchContainer.style.visibility = '';
        
        console.log('Container shown, calling showLoading');
        
        // Show loading state
        this.showLoading();
        
        try {
            // Perform search - ensure it completes before rendering
            await this.loadSearchResults();
            
            // Render results (async - loads years from API)
            // render() will handle empty results case (currentResults can be null or have total: 0)
            await this.render();
            
            // Reinitialize Lucide icons
            if (typeof lucide !== 'undefined') {
                setTimeout(() => lucide.createIcons(), 100);
            }
        } catch (error) {
            console.error('Failed to load search results:', error);
            this.showError(error.message || 'Failed to load search results.');
        }
    },
    
    /**
     * Load search results from API
     */
    async loadSearchResults() {
        console.log('loadSearchResults called with query:', this.currentQuery, 'filters:', this.currentFilters);
        
        // Always call API directly for full page (don't rely on Search component state)
        // Don't send limit parameter - API will return all results when limit is not provided
        const params = {
            q: this.currentQuery
        };
        
        if (this.currentFilters.date_year) {
            if (!params.filter) params.filter = {};
            params.filter.date_year = this.currentFilters.date_year;
        }
        if (this.currentFilters.date_month) {
            if (!params.filter) params.filter = {};
            params.filter.date_month = this.currentFilters.date_month;
        }
        if (this.currentFilters.module_type) {
            if (!params.filter) params.filter = {};
            params.filter.module_type = this.currentFilters.module_type;
        }
        
        console.log('Calling API with params:', params);
        try {
            this.currentResults = await api.get('search', null, params);
            console.log('API returned results:', this.currentResults);
        } catch (error) {
            console.error('API call failed:', error);
            throw error;
        }
        
        // Also update Search component state if available (for consistency)
        if (typeof Search !== 'undefined') {
            Search.currentQuery = this.currentQuery;
            Search.currentFilters = this.currentFilters;
            Search.currentResults = this.currentResults;
        }
    },
    
    /**
     * Render search results page
     */
    async render() {
        const container = document.getElementById('search-results-content');
        if (!container) {
            console.error('render: search-results-content container not found');
            return;
        }
        
        console.log('render: Rendering with results:', this.currentResults);
        
        const t = (k) => (typeof i18n !== 'undefined' && i18n.t ? i18n.t(k) : k);
        
        // Render header
        const headerHtml = this.renderHeader();
        
        // Render filters (async - loads years from API)
        const filtersHtml = await this.renderFilters();
        
        // Render results
        const resultsHtml = this.renderResults();
        
        console.log('render: Setting innerHTML, header length:', headerHtml.length, 'filters length:', filtersHtml.length, 'results length:', resultsHtml.length);
        container.innerHTML = headerHtml + filtersHtml + resultsHtml;
        
        // Wire up filter callbacks and attach event listeners (must be after HTML is inserted)
        this.wireFilterCallbacks();
        
        // Attach event listeners
        this.attachEventListeners();
        
        // Reinitialize Lucide icons
        if (typeof lucide !== 'undefined') {
            setTimeout(() => lucide.createIcons(), 100);
        }
    },
    
    /**
     * Render header
     */
    renderHeader() {
        const t = (k) => (typeof i18n !== 'undefined' && i18n.t ? i18n.t(k) : k);
        
        return `
            <div class="mb-6 pb-4 border-b border-border/30">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold tracking-tight text-foreground">
                            ${this.escapeHtml(t('js.search.results.title') || 'Search Results')}
                        </h1>
                        <p class="text-sm text-muted-foreground mt-1">
                            ${this.escapeHtml(t('js.search.results.query') || 'Query')}: <span class="font-medium text-foreground">"${this.escapeHtml(this.currentQuery)}"</span>
                            ${this.currentResults && this.currentResults.total !== undefined ? 
                                ` • ${this.currentResults.total} ${this.escapeHtml(t('js.search.results.results') || 'results')}` : ''}
                        </p>
                    </div>
                </div>
            </div>
        `;
    },
    
    /**
     * Render filters
     */
    async renderFilters() {
        const t = (k) => (typeof i18n !== 'undefined' && i18n.t ? i18n.t(k) : k);
        
        if (typeof SearchFilters === 'undefined') {
            return '<div id="search-results-page-filters"></div>';
        }
        
        // Create temporary container to render filters
        const tempContainer = document.createElement('div');
        tempContainer.id = 'search-results-page-filters';
        
        // Render filters HTML (without attaching listeners yet) - await async render
        await SearchFilters.render(tempContainer, this.currentFilters);
        
        // Return HTML - listeners will be attached in attachEventListeners()
        return tempContainer.outerHTML;
    },
    
    /**
     * Wire up filter callbacks (called after filters are inserted into DOM)
     */
    wireFilterCallbacks() {
        if (typeof SearchFilters === 'undefined') return;
        
        // Wire up filter callbacks BEFORE attaching event listeners
        SearchFilters.onFilterChange = (type, value) => {
            const filters = { ...this.currentFilters };
            
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
            
            this.currentFilters = filters;
            
            // Don't update URL when filters change - keep browser history clean
            // Filters are local state only, not part of URL
            
            // Reload search with new filters
            this.showLoading();
            this.loadSearchResults().then(async () => {
                await this.render();
                if (typeof lucide !== 'undefined') {
                    setTimeout(() => lucide.createIcons(), 100);
                }
            }).catch(error => {
                console.error('Failed to reload search:', error);
                this.showError(error.message || 'Failed to reload search results.');
            });
        };
        
        SearchFilters.onClearAll = () => {
            this.currentFilters = {};
            
            // Don't update URL when clearing filters - keep browser history clean
            // Filters are local state only, not part of URL
            
            // Reload search without filters
            this.showLoading();
            this.loadSearchResults().then(async () => {
                await this.render();
                if (typeof lucide !== 'undefined') {
                    setTimeout(() => lucide.createIcons(), 100);
                }
            }).catch(error => {
                console.error('Failed to reload search:', error);
                this.showError(error.message || 'Failed to reload search results.');
            });
        };
        
        // Attach event listeners to the actual DOM elements (after HTML is inserted)
        const filtersContainer = document.getElementById('search-results-page-filters');
        if (filtersContainer && typeof SearchFilters !== 'undefined' && SearchFilters.attachEventListeners) {
            console.log('Attaching filter event listeners to:', filtersContainer);
            SearchFilters.attachEventListeners(filtersContainer);
        } else {
            console.error('Could not attach filter listeners:', {
                filtersContainer: !!filtersContainer,
                SearchFilters: typeof SearchFilters,
                attachEventListeners: typeof SearchFilters !== 'undefined' && typeof SearchFilters.attachEventListeners
            });
        }
    },
    
    /**
     * Update URL with current query only (filters are not stored in URL)
     * @param {boolean} useReplaceState If true, use replaceState instead of pushState (for navigation back)
     */
    updateUrl(useReplaceState = false) {
        const urlParams = new URLSearchParams();
        urlParams.set('search', encodeURIComponent(this.currentQuery));
        // Filters are not stored in URL - only query parameter
        
        const url = '?' + urlParams.toString();
        const state = { view: 'search-results', query: this.currentQuery, filters: this.currentFilters };
        
        // Use replaceState if:
        // 1. Already on search results (filter change)
        // 2. Navigating back from event detail (to avoid creating new history entry)
        if (useReplaceState || (history.state && history.state.view === 'search-results')) {
            history.replaceState(state, '', url);
        } else {
            history.pushState(state, '', url);
        }
    },
    
    /**
     * Read URL parameters and restore query (filters are not stored in URL)
     * @returns {object} Object with query and empty filters
     */
    readUrlParams() {
        const urlParams = new URLSearchParams(window.location.search);
        // Support both 'q' and 'search' parameters (q is more common, search is legacy)
        const query = urlParams.get('q') || urlParams.get('search') || '';
        // Filters are not stored in URL - always start with empty filters
        const filters = {};
        
        return { query, filters };
    },
    
    /**
     * Render results
     */
    renderResults() {
        if (!this.currentResults || this.currentResults.total === 0) {
            const t = (k) => (typeof i18n !== 'undefined' && i18n.t ? i18n.t(k) : k);
            return `
                <div class="text-center py-12">
                    <p class="text-muted-foreground">${this.escapeHtml(t('js.search.results.noResults') || 'No results found')}</p>
                </div>
            `;
        }
        
        // Use SearchResults component - full page shows all results, no "Show all" button needed
        const resultsContainer = document.createElement('div');
        resultsContainer.id = 'search-results-page-content';
        
        if (typeof SearchResults !== 'undefined') {
            // Use regular render since we're showing all results (no "Show all" button needed)
            SearchResults.render(this.currentResults, resultsContainer);
        } else {
            // Fallback
            resultsContainer.innerHTML = '<p class="text-muted-foreground">Search results component not available</p>';
        }
        
        return resultsContainer.outerHTML;
    },
    
    /**
     * Attach event listeners
     */
    attachEventListeners() {
        // Browser handles navigation via native <a> tags - no need to intercept clicks
        // Only handle non-link items (users, contacts) that need special navigation
        const container = document.getElementById('search-results-content');
        if (container) {
            container.addEventListener('click', (e) => {
                // Don't handle clicks on filter chips or filter buttons
                if (e.target.closest('.filter-chip-btn') || e.target.closest('.filter-bubble') || e.target.closest('.filter-clear-btn')) {
                    return; // Let filter handlers deal with these
                }
                
                // Don't intercept clicks on links - let browser handle them
                if (e.target.closest('a[href]')) {
                    return;
                }
                
                // Handle result item clicks for non-link items (users, contacts)
                const resultItem = e.target.closest('.search-result-item');
                if (resultItem) {
                    const itemType = resultItem.getAttribute('data-item-type');
                    const itemId = resultItem.getAttribute('data-item-id');
                    
                    if (itemType === 'user') {
                        e.preventDefault();
                        window.location.href = `users.html?id=${itemId}`;
                    } else if (itemType === 'contact') {
                        e.preventDefault();
                        window.location.href = `contacts.html?id=${itemId}`;
                    }
                    // Events are handled by browser via <a> tags
                }
            });
        }
    },
    
    /**
     * Show loading state
     */
    showLoading() {
        const container = document.getElementById('search-results-content');
        if (!container) return;
        
        const t = (k) => (typeof i18n !== 'undefined' && i18n.t ? i18n.t(k) : k);
        container.innerHTML = `
            <div class="text-center py-12">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary mx-auto"></div>
                <p class="mt-4 text-muted-foreground">${this.escapeHtml(t('js.search.loading') || 'Searching...')}</p>
            </div>
        `;
    },
    
    /**
     * Show error state
     */
    showError(message) {
        const container = document.getElementById('search-results-content');
        if (!container) return;
        
        container.innerHTML = `
            <div class="text-center py-12">
                <p class="text-destructive">${this.escapeHtml(message)}</p>
            </div>
        `;
    },
    
    /**
     * Navigate back (uses browser history or shows dashboard)
     */
    navigateBack() {
        // Always use browser back if we have history (popstate handler will take care of showing the right view)
        // Only show dashboard directly if there's no history to go back to
        if (history.length > 1) {
            // Go back in browser history - popstate handler will show the appropriate view
            history.back();
        } else {
            // No history to go back to - show dashboard directly
            this.showDashboard();
        }
    },
    
    /**
     * Show dashboard (called from history popstate or when no history available)
     */
    showDashboard() {
        // Hide search container, show main content (works on any page)
        const searchContainer = document.getElementById('search-results-container');
        
        if (searchContainer) searchContainer.classList.add('hidden');
        
        // Show main content containers (works on any page)
        const containersToShow = [
            'dashboard-container',
            'contacts-main-content',
            'users-main-content'
        ];
        
        containersToShow.forEach(containerId => {
            const container = document.getElementById(containerId);
            if (container) {
                container.classList.remove('hidden');
            }
        });
        
        // Show main content if it exists (for contacts/users pages)
        // On dashboard.html, main contains dashboard-container, so it's already shown above
        const mainContent = document.querySelector('main');
        if (mainContent && mainContent.id !== 'search-results-content') {
            // Only show main if it's not already shown (i.e., for contacts/users pages)
            // On dashboard, main contains dashboard-container which is already shown
            const hasDashboardContainer = document.getElementById('dashboard-container');
            if (!hasDashboardContainer || !mainContent.contains(hasDashboardContainer)) {
                mainContent.classList.remove('hidden');
            }
        }
        
        // Hide event detail if it exists
        const eventDetailContainer = document.getElementById('event-detail-container');
        if (eventDetailContainer) {
            eventDetailContainer.classList.add('hidden');
        }
        
        // Clear search input field
        if (typeof SearchIntegration !== 'undefined' && SearchIntegration.searchInput) {
            SearchIntegration.searchInput.value = '';
            // Hide search overlay if visible
            if (typeof SearchIntegration !== 'undefined' && SearchIntegration.hideResults) {
                SearchIntegration.hideResults();
            }
        }
        // Also clear Search component state
        if (typeof Search !== 'undefined') {
            Search.currentQuery = '';
            Search.currentFilters = {};
            Search.currentResults = null;
        }
        
        // Update URL - remove search parameters
        const urlParams = new URLSearchParams(window.location.search);
        urlParams.delete('search');
        urlParams.delete('year');
        urlParams.delete('month');
        urlParams.delete('module_type');
        const newUrl = urlParams.toString() ? `?${urlParams.toString()}` : window.location.pathname;
        history.replaceState({ view: 'dashboard' }, '', newUrl);
        
        // Clear search state
        this.currentQuery = '';
        this.currentFilters = {};
        this.currentResults = null;
    },
    
    /**
     * Escape HTML to prevent XSS
     */
    escapeHtml(text) {
        if (text === null || text === undefined) return '';
        const div = document.createElement('div');
        div.textContent = String(text);
        return div.innerHTML;
    }
};

// Export for use in other scripts
window.SearchResultsPage = SearchResultsPage;
