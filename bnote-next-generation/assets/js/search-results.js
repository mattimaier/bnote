/**
 * BNote Next Generation - Search Results UI Component
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
 * Search Results UI Component
 * Single Responsibility: Rendering search results UI only
 * No API calls - receives data as parameters
 */
const SearchResults = {
    /**
     * Render search results into container
     * @param {object} results Search results object with categories
     * @param {HTMLElement} container Container element to render into
     */
    render(results, container) {
        if (!container) return;
        
        if (!results || results.total === 0) {
            this.renderEmptyState(container);
            return;
        }
        
        const t = (k) => (typeof i18n !== 'undefined' && i18n.t ? i18n.t(k) : k);
        
        let html = '<div class="space-y-0">';
        
        // Render each category group
        const categories = [
            { key: 'rehearsals', label: t('js.search.results.rehearsals') || 'Rehearsals', type: 'events' },
            { key: 'concerts', label: t('js.search.results.concerts') || 'Concerts', type: 'events' },
            { key: 'users', label: t('js.search.results.users') || 'Users', type: 'list' },
            { key: 'contacts', label: t('js.search.results.contacts') || 'Contacts', type: 'list' },
            { key: 'tasks', label: t('js.search.results.tasks') || 'Tasks', type: 'list' },
            { key: 'repertoire', label: t('js.search.results.repertoire') || 'Repertoire', type: 'list' },
            { key: 'locations', label: t('js.search.results.locations') || 'Locations', type: 'list' }
        ];
        
        categories.forEach((category, index) => {
            const items = results[category.key] || [];
            // Get total count for this category (from _totals if available, otherwise use items.length)
            const totalCount = (results._totals && results._totals[category.key]) ? results._totals[category.key] : items.length;
            
            if (items.length > 0 || totalCount > 0) {
                // Pass totalCount to renderGroup so it displays the total
                html += this.renderGroup(category, items, index === categories.length - 1, totalCount);
            }
        });
        
        html += '</div>';
        container.innerHTML = html;
        
        // Re-initialize Lucide icons
        setTimeout(() => {
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        }, 0);
    },
    
    /**
     * Render single category group
     * @param {object} category Category info
     * @param {array} items Items in category
     * @param {boolean} isLast Whether this is the last group
     * @param {number} totalCount Total count (if showing limited results)
     * @param {boolean} showAllButton Whether to show "Show all" button
     * @returns {string} HTML string
     */
    renderGroup(category, items, isLast = false, totalCount = null, showAllButton = false) {
        const t = (k) => (typeof i18n !== 'undefined' && i18n.t ? i18n.t(k) : k);
        
        let html = `<div class="search-result-group ${!isLast ? 'mb-6' : ''}" data-category="${this.escapeHtml(category.key)}">`;
        html += `<div class="px-4 py-3 bg-muted/20 flex items-center justify-between border-b border-border/30">`;
        // Always show total count if available, otherwise show items.length
        const displayCount = totalCount !== null && totalCount !== undefined ? totalCount : items.length;
        html += `<h3 class="text-base font-semibold text-foreground">${this.escapeHtml(category.label)} <span class="text-sm text-muted-foreground font-normal">(${displayCount})</span></h3>`;
        if (showAllButton) {
            // Show button if we have exactly 5 or 50 items (might have more)
            html += `<button data-action="show-all" data-category="${this.escapeHtml(category.key)}" class="text-xs text-primary hover:text-primary/80 font-medium px-2 py-1 rounded-md hover:bg-primary/10 transition-colors">`;
            html += `${this.escapeHtml(t('js.search.results.showAll') || 'Show all')}`;
            html += `</button>`;
        }
        html += `</div>`;
        html += `<div class="px-4 py-4">`;
        
        if (category.type === 'events') {
            // Reuse dashboard event cells for rehearsals/concerts
            html += this.renderEventItems(items);
        } else {
            // Render list items for other types
            html += this.renderListItems(items, category.key);
        }
        
        html += `</div>`;
        html += `</div>`;
        
        return html;
    },
    
    /**
     * Render search results with "Show all" support
     * @param {object} results Search results object with categories
     * @param {HTMLElement} container Container element to render into
     * @param {object} expandedCategories Object tracking which categories are expanded
     */
    renderWithShowAll(results, container, expandedCategories = {}) {
        if (!container) return;
        
        if (!results || results.total === 0) {
            this.renderEmptyState(container);
            return;
        }
        
        const t = (k) => (typeof i18n !== 'undefined' && i18n.t ? i18n.t(k) : k);
        
        let html = '<div class="space-y-0">';
        
        // Render each category group
        const categories = [
            { key: 'rehearsals', label: t('js.search.results.rehearsals') || 'Rehearsals', type: 'events' },
            { key: 'concerts', label: t('js.search.results.concerts') || 'Concerts', type: 'events' },
            { key: 'users', label: t('js.search.results.users') || 'Users', type: 'list' },
            { key: 'contacts', label: t('js.search.results.contacts') || 'Contacts', type: 'list' },
            { key: 'tasks', label: t('js.search.results.tasks') || 'Tasks', type: 'list' },
            { key: 'repertoire', label: t('js.search.results.repertoire') || 'Repertoire', type: 'list' },
            { key: 'locations', label: t('js.search.results.locations') || 'Locations', type: 'list' }
        ];
        
        categories.forEach((category, index) => {
            const items = results[category.key] || [];
            // Get total count for this category (from _totals if available, otherwise use items.length)
            const totalCount = (results._totals && results._totals[category.key]) ? results._totals[category.key] : items.length;
            
            if (items.length > 0 || totalCount > 0) {
                // Only show "Show all" button for overlay (5 items limit) when there are more results
                const showAllButton = items.length === 5 && totalCount > 5 && !expandedCategories[category.key];
                // Pass totalCount to renderGroup so it displays the total, not just items.length
                html += this.renderGroup(category, items, index === categories.length - 1, totalCount, showAllButton);
            }
        });
        
        html += '</div>';
        container.innerHTML = html;
        
        // Re-initialize Lucide icons
        setTimeout(() => {
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        }, 0);
    },
    
    /**
     * Render event items (rehearsals/concerts) using dashboard event cells
     * @param {array} events Event items
     * @returns {string} HTML string
     */
    renderEventItems(events) {
        if (typeof Dashboard === 'undefined' || !Dashboard.renderEventsWidget) {
            // Fallback if Dashboard not available - render simple list
            return this.renderListItems(events, 'events');
        }
        
        // Convert events to dashboard format
        const dashboardEvents = events.map(event => ({
            otype: event.otype,
            oid: event.oid || event.id,
            title: event.title,
            eventBegin: event.begin,
            dueDate: event.begin,
            location: event.location,
            locationData: event.location ? { name: event.location } : null
        }));
        
        // Create temporary container and render using dashboard
        const tempContainer = document.createElement('div');
        tempContainer.id = 'search-results-temp-content';
        tempContainer.style.position = 'absolute';
        tempContainer.style.left = '-9999px';
        tempContainer.style.visibility = 'hidden';
        document.body.appendChild(tempContainer);
        
        // Use dashboard rendering (it will populate the container)
        // Pass showLoadMore=false to prevent "Load more" button in search results
        Dashboard.renderEventsWidget(
            dashboardEvents,
            'search-results-temp-content',
            null,
            '',
            false, // showParticipation
            null,  // sectionId
            false  // showLoadMore - don't show "Load more" button in search results
        );
        
        let html = tempContainer.innerHTML;
        document.body.removeChild(tempContainer);
        
        // Add spacing between event items by wrapping in a container with spacing
        if (html.trim()) {
            html = `<div class="space-y-3">${html}</div>`;
        }
        
        return html;
    },
    
    /**
     * Render list items for non-event types
     * @param {array} items Items to render
     * @param {string} type Item type
     * @returns {string} HTML string
     */
    renderListItems(items, type) {
        let html = '<div class="space-y-3">';
        
        items.forEach(item => {
            html += this.renderListItem(item, type);
        });
        
        html += '</div>';
        return html;
    },
    
    /**
     * Render single list item
     * @param {object} item Item data
     * @param {string} type Item type
     * @returns {string} HTML string
     */
    renderListItem(item, type) {
        const t = (k) => (typeof i18n !== 'undefined' && i18n.t ? i18n.t(k) : k);
        
        let html = `<div class="search-result-item p-2 rounded-md hover:bg-muted/50 cursor-pointer transition-colors" data-item-id="${item.id}" data-item-type="${type}">`;
        
        // Title/Name
        const title = item.name || item.title || `Item ${item.id}`;
        html += `<div class="font-semibold text-sm text-foreground mb-1">${this.escapeHtml(title)}</div>`;
        
        // Additional info based on type
        if (type === 'users' || type === 'contacts') {
            if (item.email) {
                html += `<div class="text-xs text-muted-foreground flex items-center gap-1">`;
                html += `<i data-lucide="mail" class="h-3 w-3"></i>`;
                html += `<span>${this.escapeHtml(item.email)}</span>`;
                html += `</div>`;
            }
            if (item.phone || item.mobile) {
                html += `<div class="text-xs text-muted-foreground flex items-center gap-1">`;
                html += `<i data-lucide="phone" class="h-3 w-3"></i>`;
                html += `<span>${this.escapeHtml(item.phone || item.mobile)}</span>`;
                html += `</div>`;
            }
            if (item.instrument) {
                html += `<div class="text-xs text-muted-foreground">${this.escapeHtml(item.instrument)}</div>`;
            }
        } else if (type === 'tasks') {
            if (item.dueAt) {
                html += `<div class="text-xs text-muted-foreground">Due: ${this.formatDate(item.dueAt)}</div>`;
            }
            if (item.assignee) {
                html += `<div class="text-xs text-muted-foreground">Assigned to: ${this.escapeHtml(item.assignee)}</div>`;
            }
        } else if (type === 'repertoire') {
            if (item.composer) {
                html += `<div class="text-xs text-muted-foreground">Composer: ${this.escapeHtml(item.composer)}</div>`;
            }
            if (item.genre) {
                html += `<div class="text-xs text-muted-foreground">Genre: ${this.escapeHtml(item.genre)}</div>`;
            }
        } else if (type === 'locations') {
            if (item.city || item.street) {
                html += `<div class="text-xs text-muted-foreground">${this.escapeHtml([item.street, item.city].filter(Boolean).join(', '))}</div>`;
            }
        }
        
        html += `</div>`;
        return html;
    },
    
    /**
     * Render empty state
     * @param {HTMLElement} container Container element
     */
    renderEmptyState(container) {
        const t = (k) => (typeof i18n !== 'undefined' && i18n.t ? i18n.t(k) : k);
        // Try js.search.results.noResults first, fallback to js.search.noResults
        const message = t('js.search.results.noResults') || t('js.search.noResults') || 'No results found';
        
        container.innerHTML = `
            <div class="text-center py-8 text-muted-foreground text-sm">
                ${this.escapeHtml(message)}
            </div>
        `;
    },
    
    /**
     * Render error state
     * @param {HTMLElement} container Container element
     * @param {Error} error Error object
     */
    renderErrorState(container, error) {
        const t = (k) => (typeof i18n !== 'undefined' && i18n.t ? i18n.t(k) : k);
        const errorMessage = error?.message || error || (t('js.search.error') || 'Search failed. Please try again.');
        
        container.innerHTML = `
            <div class="text-center py-8 text-destructive text-sm">
                ${this.escapeHtml(errorMessage)}
            </div>
        `;
    },
    
    /**
     * Format date for display
     * @param {string} dateString Date string
     * @returns {string} Formatted date
     */
    formatDate(dateString) {
        if (!dateString) return '';
        const date = new Date(dateString);
        return date.toLocaleDateString();
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
window.SearchResults = SearchResults;
