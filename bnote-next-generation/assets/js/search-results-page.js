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
     */
    async init(query, filters = {}) {
        // If query is empty, try reading from URL (for bookmark/shared link support)
        let trimmedQuery = query ? query.trim() : '';
        if (!trimmedQuery || trimmedQuery.length < 2) {
            const urlData = this.readUrlParams();
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
            console.error('Search query too short');
            return;
        }
        
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
        
        // Update URL with query and filters
        this.updateUrl();
        
        // Show search container, hide dashboard and event detail - check elements exist
        const searchContainer = document.getElementById('search-results-container');
        const dashboardContainer = document.getElementById('dashboard-container');
        const eventDetailContainer = document.getElementById('event-detail-container');
        
        if (!searchContainer) {
            console.error('search-results-container not found in DOM');
            return;
        }
        
        if (dashboardContainer) {
            dashboardContainer.classList.add('hidden');
        }
        if (eventDetailContainer) {
            eventDetailContainer.classList.add('hidden');
        }
        
        searchContainer.classList.remove('hidden');
        
        // Show loading state
        this.showLoading();
        
        try {
            // Perform search - ensure it completes before rendering
            await this.loadSearchResults();
            
            // Ensure we have results before rendering
            if (!this.currentResults) {
                this.showError('No results found');
                return;
            }
            
            // Render results (async - loads years from API)
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
        
        this.currentResults = await api.get('search', null, params);
        
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
        if (!container) return;
        
        const t = (k) => (typeof i18n !== 'undefined' && i18n.t ? i18n.t(k) : k);
        
        // Render header
        const headerHtml = this.renderHeader();
        
        // Render filters (async - loads years from API)
        const filtersHtml = await this.renderFilters();
        
        // Render results
        const resultsHtml = this.renderResults();
        
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
            
            // Update URL with new filters
            this.updateUrl();
            
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
            
            // Update URL to remove filters
            this.updateUrl();
            
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
     * Update URL with current query and filters
     */
    updateUrl() {
        const urlParams = new URLSearchParams();
        urlParams.set('search', encodeURIComponent(this.currentQuery));
        
        if (this.currentFilters.date_year) {
            urlParams.set('year', this.currentFilters.date_year);
        }
        if (this.currentFilters.date_month) {
            urlParams.set('month', this.currentFilters.date_month);
        }
        if (this.currentFilters.module_type) {
            urlParams.set('module_type', this.currentFilters.module_type);
        }
        
        const url = '?' + urlParams.toString();
        const state = { view: 'search-results', query: this.currentQuery, filters: this.currentFilters };
        history.pushState(state, '', url);
    },
    
    /**
     * Read URL parameters and restore query and filters
     * @returns {object} Object with query and filters
     */
    readUrlParams() {
        const urlParams = new URLSearchParams(window.location.search);
        const query = urlParams.get('search') || '';
        const filters = {};
        
        const year = urlParams.get('year');
        if (year) {
            filters.date_year = parseInt(year);
        }
        
        const month = urlParams.get('month');
        if (month) {
            filters.date_month = parseInt(month);
        }
        
        const moduleType = urlParams.get('module_type');
        if (moduleType) {
            filters.module_type = moduleType;
        }
        
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
        // Handle clicks on search results
        const container = document.getElementById('search-results-content');
        if (container) {
            container.addEventListener('click', async (e) => {
                // Don't handle clicks on filter chips or filter buttons
                if (e.target.closest('.filter-chip-btn') || e.target.closest('.filter-bubble') || e.target.closest('.filter-clear-btn')) {
                    return; // Let filter handlers deal with these
                }
                
                // Intercept event clicks from search results to pass search context
                const eventElement = e.target.closest('[data-event-id][data-event-type]');
                if (eventElement) {
                    const eventType = eventElement.getAttribute('data-event-type');
                    const eventId = eventElement.getAttribute('data-event-id');
                    
                    if (eventType && eventId && typeof Dashboard !== 'undefined' && Dashboard.openEventDetail) {
                        e.preventDefault();
                        e.stopPropagation();
                        // Pass search context when opening event detail (await async method)
                        Dashboard.openEventDetail(eventType, parseInt(eventId), true, this.currentQuery, this.currentFilters).catch(err => {
                            console.error('Failed to open event detail:', err);
                        });
                    }
                }
            });
        }
        
        // Handle result item clicks (for navigation)
        const resultItems = document.querySelectorAll('.search-result-item');
        resultItems.forEach(item => {
            item.addEventListener('click', (e) => {
                const itemType = item.getAttribute('data-item-type');
                const itemId = item.getAttribute('data-item-id');
                
                if (itemType === 'user') {
                    window.location.href = `users.html?id=${itemId}`;
                } else if (itemType === 'contact') {
                    window.location.href = `contacts.html?id=${itemId}`;
                }
                // Events are handled above via event delegation
            });
        });
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
     * Navigate back to dashboard
     */
    navigateBack() {
        // Hide search container, show dashboard, hide event detail
        const searchContainer = document.getElementById('search-results-container');
        const dashboardContainer = document.getElementById('dashboard-container');
        const eventDetailContainer = document.getElementById('event-detail-container');
        
        if (searchContainer) searchContainer.classList.add('hidden');
        if (dashboardContainer) dashboardContainer.classList.remove('hidden');
        if (eventDetailContainer) eventDetailContainer.classList.add('hidden');
        
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
        history.pushState({ view: 'dashboard' }, '', newUrl);
        
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
