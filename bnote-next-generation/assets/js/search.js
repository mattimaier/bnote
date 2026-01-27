/**
 * BNote Next Generation - Search Core Component
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
 * Search Core Component
 * Single Responsibility: Search state management, API calls, and coordination
 * No UI rendering - pure business logic
 */
const Search = {
    // State
    currentQuery: '',
    currentFilters: {},
    currentResults: null,
    isSearching: false,
    debounceTimer: null,
    debounceDelay: 300,
    
    // Callbacks
    onResultsUpdate: null,
    onSearchStart: null,
    onSearchError: null,
    
    /**
     * Initialize search functionality
     */
    init() {
        // State is already initialized above
        this.currentQuery = '';
        this.currentFilters = {};
        this.currentResults = null;
        this.isSearching = false;
        console.log('Search.init() called');
    },
    
    /**
     * Perform search API call
     * @param {string} query Search query
     * @param {object} filters Optional filters
     * @param {number} limit Optional limit (default 5 for overlay, 50 for full page)
     * @returns {Promise<object>} Search results
     */
    async performSearch(query, filters = {}, limit = 5) {
        console.log('Search.performSearch called:', { query, filters, limit });
        
        // Validate query length
        const trimmedQuery = query.trim();
        if (trimmedQuery.length < 2) {
            console.log('Query too short, clearing results');
            this.currentResults = null;
            if (this.onResultsUpdate) {
                this.onResultsUpdate(null); // Explicitly null to hide overlay
            }
            return null;
        }
        
        this.currentQuery = trimmedQuery;
        this.currentFilters = filters;
        this.isSearching = true;
        
        if (this.onSearchStart) {
            this.onSearchStart();
        }
        
        try {
            // Build query parameters
            const params = {
                q: trimmedQuery
            };
            
            // Add filters - use nested object format for filter parameters
            if (filters.date_year) {
                if (!params.filter) {
                    params.filter = {};
                }
                params.filter.date_year = filters.date_year;
            }
            if (filters.date_month) {
                if (!params.filter) {
                    params.filter = {};
                }
                params.filter.date_month = filters.date_month;
            }
            
            // Add module type filter (rehearsal/concert/etc)
            if (filters.module_type) {
                if (!params.filter) {
                    params.filter = {};
                }
                params.filter.module_type = filters.module_type;
            }
            
            // Add limit
            params.limit = limit;
            
            console.log('Making API call with params:', params);
            
            // Make API call
            // Note: api.get(module, action, params) - for search module, action is null
            console.log('Calling api.get with:', { module: 'search', action: null, params });
            const results = await api.get('search', null, params);
            
            console.log('Search API returned:', results);
            
            this.currentResults = results;
            this.isSearching = false;
            
            if (this.onResultsUpdate) {
                this.onResultsUpdate(results);
            }
            
            return results;
            
        } catch (error) {
            console.error('Search error:', error);
            this.isSearching = false;
            this.currentResults = null;
            
            if (this.onSearchError) {
                this.onSearchError(error);
            }
            
            throw error;
        }
    },
    
    /**
     * Debounced search - call performSearch after delay
     * @param {string} query Search query
     * @param {object} filters Optional filters
     */
    debouncedSearch(query, filters = {}) {
        console.log('Search.debouncedSearch called:', { query, filters });
        
        // Reset filters if query changed (new search)
        const trimmedQuery = query.trim();
        if (this.currentQuery && this.currentQuery !== trimmedQuery) {
            console.log('Query changed, resetting filters');
            filters = {}; // Reset filters for new search
            this.currentFilters = {};
            
            // Update filter UI if SearchFilters is available
            if (typeof SearchFilters !== 'undefined' && SearchFilters.onFilterChange) {
                // Trigger filter UI update with empty filters
                const filtersContainer = document.getElementById('search-filters-container');
                if (filtersContainer) {
                    SearchFilters.render(filtersContainer, {}).catch(err => {
                        console.error('Failed to render filters:', err);
                    });
                }
            }
        }
        
        // Clear existing timer
        if (this.debounceTimer) {
            clearTimeout(this.debounceTimer);
        }
        
        // Set new timer
        this.debounceTimer = setTimeout(() => {
            console.log('Debounce timer fired, calling performSearch');
            this.performSearch(query, filters);
        }, this.debounceDelay);
    },
    
    /**
     * Update active filters
     * @param {object} filters Filter object
     */
    setFilters(filters) {
        this.currentFilters = filters;
        
        // If we have a current query, re-search with new filters
        if (this.currentQuery && this.currentQuery.length >= 2) {
            this.performSearch(this.currentQuery, filters);
        }
    },
    
    /**
     * Clear all filters
     */
    clearFilters() {
        this.currentFilters = {};
        
        // Re-search with cleared filters
        if (this.currentQuery && this.currentQuery.length >= 2) {
            this.performSearch(this.currentQuery, {});
        }
    },
    
    /**
     * Handle keyboard shortcut (Cmd+K / Ctrl+K)
     * Returns true if handled, false otherwise
     */
    handleKeyboardShortcut(event) {
        // Check for Cmd+K (Mac) or Ctrl+K (Windows/Linux)
        const isMac = navigator.platform.toUpperCase().indexOf('MAC') >= 0;
        const modifierKey = isMac ? event.metaKey : event.ctrlKey;
        
        if (modifierKey && event.key === 'k') {
            event.preventDefault();
            return true;
        }
        
        return false;
    },
    
    /**
     * Get current search state
     * @returns {object} Current state
     */
    getState() {
        return {
            query: this.currentQuery,
            filters: this.currentFilters,
            results: this.currentResults,
            isSearching: this.isSearching
        };
    },
    
    /**
     * Clear search state
     */
    clear() {
        this.currentQuery = '';
        this.currentFilters = {};
        this.currentResults = null;
        this.isSearching = false;
        
        if (this.debounceTimer) {
            clearTimeout(this.debounceTimer);
            this.debounceTimer = null;
        }
        
        if (this.onResultsUpdate) {
            this.onResultsUpdate(null);
        }
    }
};

// Export for use in other scripts
window.Search = Search;
