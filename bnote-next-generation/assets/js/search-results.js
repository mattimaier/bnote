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
        // "Show all" button removed - users can use the main "Show all results" button instead
        html += `</div>`;
        html += `<div class="px-4 py-4">`;

        if (category.type === 'events') {
            // Render event items (rehearsals/concerts) with icons
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
                // Pass totalCount to renderGroup so it displays the total, not just items.length
                // showAllButton parameter removed - always pass false
                html += this.renderGroup(category, items, index === categories.length - 1, totalCount, false);
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
     * Render event items (rehearsals/concerts) - uses shared EventRenderer
     * @param {array} events Event items
     * @returns {string} HTML string
     */
    renderEventItems(events) {
        // Use shared EventRenderer component (same as dashboard)
        // Always use 'search' as module context since this is only called for search results
        if (typeof EventRenderer !== 'undefined' && EventRenderer.renderEvents) {
            return EventRenderer.renderEvents(events, {
                showParticipation: false,
                isMobile: window.innerWidth < 768,
                moduleContext: 'search'
            });
        }
        // Fallback if EventRenderer not available
        return this.renderEventItemsFallback(events);
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
     * Render single list item with icon (matching dashboard style)
     * @param {object} item Item data
     * @param {string} type Item type
     * @returns {string} HTML string
     */
    renderListItem(item, type) {
        const t = (k) => (typeof i18n !== 'undefined' && i18n.t ? i18n.t(k) : k);

        // Get icon and color for each type (matching dashboard event style)
        const typeConfig = this.getListItemTypeConfig(type);

        let html = `<div class="search-result-item flex items-start gap-3 p-3 rounded-lg border border-border/40 hover:bg-muted/50 hover:border-primary/30 cursor-pointer transition-colors" data-item-id="${item.id}" data-item-type="${type}">`;

        // Icon circle (matching dashboard event icon style)
        html += `<div class="relative z-10 mt-0.5 h-7 w-7 shrink-0 rounded-full ${typeConfig.iconBg} ring-3 ring-background shadow-sm flex items-center justify-center">`;
        html += `<i data-lucide="${typeConfig.icon}" class="h-4 w-4 ${typeConfig.iconColor}"></i>`;
        html += `</div>`;

        // Content
        html += `<div class="flex-1 min-w-0">`;

        // Title/Name with badge (matching dashboard style)
        const title = item.name || item.title || `Item ${item.id}`;
        html += `<div class="flex items-center gap-1.5 mb-1 flex-wrap">`;
        html += `<h3 class="text-sm font-semibold text-foreground">${this.escapeHtml(title)}</h3>`;
        html += `<span class="${typeConfig.badgeClass} text-[10px] px-1.5 py-0.5 rounded">${typeConfig.label}</span>`;
        html += `</div>`;

        // Additional info based on type (with icons matching dashboard style)
        if (type === 'users' || type === 'contacts') {
            if (item.email) {
                html += `<div class="text-xs text-muted-foreground flex items-center gap-1.5 mb-0.5">`;
                html += `<i data-lucide="mail" class="h-3 w-3 text-primary/60"></i>`;
                html += `<span>${this.escapeHtml(item.email)}</span>`;
                html += `</div>`;
            }
            if (item.phone || item.mobile) {
                html += `<div class="text-xs text-muted-foreground flex items-center gap-1.5 mb-0.5">`;
                html += `<i data-lucide="phone" class="h-3 w-3 text-primary/60"></i>`;
                html += `<span>${this.escapeHtml(item.phone || item.mobile)}</span>`;
                html += `</div>`;
            }
            if (item.instrument) {
                html += `<div class="text-xs text-muted-foreground flex items-center gap-1.5">`;
                html += `<i data-lucide="music" class="h-3 w-3 text-primary/60"></i>`;
                html += `<span>${this.escapeHtml(item.instrument)}</span>`;
                html += `</div>`;
            }
        } else if (type === 'tasks') {
            if (item.dueAt) {
                html += `<div class="text-xs text-muted-foreground flex items-center gap-1.5 mb-0.5">`;
                html += `<i data-lucide="calendar" class="h-3 w-3 text-primary/60"></i>`;
                html += `<span>Due: ${this.formatDate(item.dueAt)}</span>`;
                html += `</div>`;
            }
            if (item.assignee) {
                html += `<div class="text-xs text-muted-foreground flex items-center gap-1.5">`;
                html += `<i data-lucide="user" class="h-3 w-3 text-primary/60"></i>`;
                html += `<span>Assigned to: ${this.escapeHtml(item.assignee)}</span>`;
                html += `</div>`;
            }
        } else if (type === 'repertoire') {
            if (item.composer) {
                html += `<div class="text-xs text-muted-foreground flex items-center gap-1.5 mb-0.5">`;
                html += `<i data-lucide="user" class="h-3 w-3 text-primary/60"></i>`;
                html += `<span>Composer: ${this.escapeHtml(item.composer)}</span>`;
                html += `</div>`;
            }
            if (item.genre) {
                html += `<div class="text-xs text-muted-foreground flex items-center gap-1.5">`;
                html += `<i data-lucide="tag" class="h-3 w-3 text-primary/60"></i>`;
                html += `<span>Genre: ${this.escapeHtml(item.genre)}</span>`;
                html += `</div>`;
            }
        } else if (type === 'locations') {
            if (item.city || item.street) {
                html += `<div class="text-xs text-muted-foreground flex items-center gap-1.5">`;
                html += `<i data-lucide="map-pin" class="h-3 w-3 text-primary/60"></i>`;
                html += `<span>${this.escapeHtml([item.street, item.city].filter(Boolean).join(', '))}</span>`;
                html += `</div>`;
            }
        }

        html += `</div>`; // Close content div
        html += `</div>`; // Close item div
        return html;
    },

    /**
     * Get type configuration for list items (matching dashboard event style)
     * @param {string} type Item type
     * @returns {object} Type configuration with icon, colors, and label
     */
    getListItemTypeConfig(type) {
        const t = (k) => (typeof i18n !== 'undefined' && i18n.t ? i18n.t(k) : k);

        // Use EntityConfig if available, otherwise fallback to hardcoded values
        if (typeof EntityConfig !== 'undefined' && EntityConfig.getListItemConfig) {
            return EntityConfig.getListItemConfig(type, t);
        }

        // Fallback to hardcoded configs if EntityConfig not loaded
        const configs = {
            'users': {
                icon: 'user',
                iconBg: 'bg-blue-500/20',
                iconColor: 'text-blue-600 dark:text-blue-400',
                badgeClass: 'bg-blue-500/20 text-blue-700 dark:text-blue-300',
                label: t('js.search.results.users') || 'User'
            },
            'contacts': {
                icon: 'user-circle',
                iconBg: 'bg-purple-500/20',
                iconColor: 'text-purple-600 dark:text-purple-400',
                badgeClass: 'bg-purple-500/20 text-purple-700 dark:text-purple-300',
                label: t('js.search.results.contacts') || 'Contact'
            },
            'tasks': {
                icon: 'check-square',
                iconBg: 'bg-green-500/20',
                iconColor: 'text-green-600 dark:text-green-400',
                badgeClass: 'bg-green-500/20 text-green-700 dark:text-green-300',
                label: t('js.search.results.tasks') || 'Task'
            },
            'repertoire': {
                icon: 'music',
                iconBg: 'bg-indigo-500/20',
                iconColor: 'text-indigo-600 dark:text-indigo-400',
                badgeClass: 'bg-indigo-500/20 text-indigo-700 dark:text-indigo-300',
                label: t('js.search.results.repertoire') || 'Repertoire'
            },
            'locations': {
                icon: 'map-pin',
                iconBg: 'bg-orange-500/20',
                iconColor: 'text-orange-600 dark:text-orange-400',
                badgeClass: 'bg-orange-500/20 text-orange-700 dark:text-orange-300',
                label: t('js.search.results.locations') || 'Location'
            }
        };

        return configs[type] || {
            icon: 'circle',
            iconBg: 'bg-muted',
            iconColor: 'text-muted-foreground',
            badgeClass: 'bg-muted text-muted-foreground',
            label: type
        };
    },

    /**
     * Fallback rendering for events when EventRenderer is not available
     * @param {array} events Event items
     * @returns {string} HTML string
     */
    renderEventItemsFallback(events) {
        // Use EventRenderer if available, otherwise render simple list
        if (typeof EventRenderer !== 'undefined' && EventRenderer.renderEvents) {
            return EventRenderer.renderEvents(events, {
                showParticipation: false,
                isMobile: window.innerWidth < 768
            });
        }

        // Last resort fallback - simple rendering with native links
        // Always use 'search' as module context since this is only used for search results
        const t = (k) => (typeof i18n !== 'undefined' && i18n.t ? i18n.t(k) : k);
        let html = '<div class="space-y-3">';
        const moduleContext = 'search';

        events.forEach(event => {
            const isClickable = event.otype === 'R' || event.otype === 'C';
            const entityType = event.otype === 'C' ? 'concert' : 'rehearsal';
            const entityId = event.oid || event.id;

            // Generate URL for clickable events
            let eventUrl = '#';
            if (isClickable && entityId) {
                if (typeof EntityService !== 'undefined' && typeof EntityService.getEntityDetailUrl === 'function') {
                    eventUrl = EntityService.getEntityDetailUrl(entityType, entityId, moduleContext, 'view');
                } else if (typeof NavigationService !== 'undefined' && typeof NavigationService.getEntityUrl === 'function') {
                    eventUrl = NavigationService.getEntityUrl(entityType, entityId, moduleContext, 'view');
                } else {
                    eventUrl = `entity-detail.html?module=${moduleContext}&entity=${entityType}&id=${entityId}&mode=view`;
                }
            }

            const dateStr = event.begin ? new Date(event.begin).toLocaleDateString('de-DE') : '';
            const timeStr = event.begin ? new Date(event.begin).toLocaleTimeString('de-DE', { hour: '2-digit', minute: '2-digit' }) : '';
            const location = event.location || event.locationData?.name || (t('js.event.tba') || 'TBA');
            const title = event.title || (t('js.event.event') || 'Event');

            const wrapperTag = isClickable ? 'a' : 'div';
            const wrapperAttrs = isClickable ? `href="${eventUrl}" class="block no-underline text-foreground hover:text-foreground"` : 'class="relative flex gap-3"';

            html += `<${wrapperTag} ${wrapperAttrs}>`;
            html += `<div class="relative flex gap-3">`;
            html += `<div class="flex-1 rounded-lg border border-border/40 bg-gradient-to-br from-muted/20 to-transparent p-3">`;
            html += `<div class="mb-1.5"><p class="text-base font-bold text-foreground">${this.escapeHtml(dateStr)}</p></div>`;
            html += `<h3 class="text-sm font-semibold text-foreground mb-1">${this.escapeHtml(title)}</h3>`;
            html += `<div class="text-xs text-muted-foreground">${this.escapeHtml(timeStr)} - ${this.escapeHtml(location)}</div>`;
            html += `</div></div>`;
            html += `</${wrapperTag}>`;
        });

        html += '</div>';
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
