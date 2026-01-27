/**
 * BNote Next Generation - Search Filter Component
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
 * Search Filter Component
 * Single Responsibility: Filter UI rendering and interaction only
 * No search logic - emits events/callbacks for parent to handle
 */
const SearchFilters = {
    // Callbacks
    onFilterChange: null,
    onClearAll: null,
    
    // Cache for available years
    availableYears: null,
    
    /**
     * Render filter bar
     * @param {HTMLElement} container Container element
     * @param {object} activeFilters Active filter values
     */
    async render(container, activeFilters = {}) {
        if (!container) return;
        
        const t = (k) => (typeof i18n !== 'undefined' && i18n.t ? i18n.t(k) : k);
        
        // Load available years if not already loaded
        if (this.availableYears === null) {
            await this.loadAvailableYears();
        }
        
        let html = '<div class="flex items-center gap-3 flex-wrap px-4 py-2 border-b border-border/30 bg-muted/20">';
        html += `<span class="text-xs font-medium text-muted-foreground">${this.escapeHtml(t('js.common.filter') || 'Filter')}</span>`;
        
        // Render module type filter bubbles (like dashboard)
        html += this.renderModuleTypeFilters(activeFilters.module_type);
        
        // Render active filter chips (but NOT module_type - it's already shown as active button above)
        if (activeFilters.date_year) {
            html += this.renderFilterChip('year', activeFilters.date_year, activeFilters);
        }
        
        if (activeFilters.date_month) {
            html += this.renderFilterChip('month', activeFilters.date_month, activeFilters);
        }
        
        // Don't render module_type as chip - it's already shown as an active button in renderModuleTypeFilters()
        
        // Year dropdown
        html += this.renderYearDropdown(activeFilters.date_year);
        
        // Month dropdown
        html += this.renderMonthDropdown(activeFilters.date_month);
        
        // Clear all button
        if (activeFilters.date_year || activeFilters.date_month || activeFilters.module_type) {
            html += `<button class="filter-clear-btn text-xs text-muted-foreground hover:text-foreground px-2 py-1 rounded-md hover:bg-muted/50 transition-colors" data-action="clear-all">`;
            html += `${this.escapeHtml(t('js.search.filter.clearAll') || 'Clear all filters')}`;
            html += `</button>`;
        }
        
        html += '</div>';
        
        container.innerHTML = html;
        
        // Attach event listeners
        this.attachEventListeners(container);
    },
    
    /**
     * Load available years from API
     */
    async loadAvailableYears() {
        try {
            if (typeof api !== 'undefined') {
                this.availableYears = await api.get('search', 'years');
            } else {
                // Fallback to current year ± 5 if API not available
                const currentYear = new Date().getFullYear();
                this.availableYears = [];
                for (let i = currentYear - 5; i <= currentYear + 5; i++) {
                    this.availableYears.push(i);
                }
            }
        } catch (error) {
            console.error('Failed to load available years:', error);
            // Fallback to current year ± 5 on error
            const currentYear = new Date().getFullYear();
            this.availableYears = [];
            for (let i = currentYear - 5; i <= currentYear + 5; i++) {
                this.availableYears.push(i);
            }
        }
    },
    
    /**
     * Render module type filter bubbles (rehearsal/concert/etc) matching dashboard style
     * @param {string|null} activeType Currently active module type
     * @returns {string} HTML string
     */
    renderModuleTypeFilters(activeType = null) {
        const t = (k) => (typeof i18n !== 'undefined' && i18n.t ? i18n.t(k) : k);
        
        // Use same pattern as dashboard - simple buttons with CSS classes, not Badge component
        const moduleTypes = [
            { key: 'rehearsal', label: t('js.event.rehearsal') || 'Rehearsal', class: 'filter-bubble-rehearsal' },
            { key: 'performance', label: t('js.event.performance') || 'Performance', class: 'filter-bubble-performance' }
        ];
        
        let html = '';
        moduleTypes.forEach(type => {
            const isActive = activeType === type.key;
            // Add 'selected' class when active (matching dashboard pattern)
            const selectedClass = isActive ? 'selected' : '';
            html += `<button class="filter-bubble ${type.class} ${selectedClass}" data-filter-type="module_type" data-filter-value="${this.escapeHtml(type.key)}" title="${isActive ? 'Click to remove filter' : 'Click to filter'}">`;
            html += `${this.escapeHtml(type.label)}`;
            html += `</button>`;
        });
        
        return html;
    },
    
    /**
     * Render filter chip badge
     * @param {string} type Filter type
     * @param {string|number} value Filter value
     * @param {object} activeFilters All active filters
     * @returns {string} HTML string
     */
    renderFilterChip(type, value, activeFilters) {
        const t = (k) => (typeof i18n !== 'undefined' && i18n.t ? i18n.t(k) : k);
        
        let label = String(value);
        if (type === 'month') {
            const monthNames = [
                t('js.search.month.january') || 'January',
                t('js.search.month.february') || 'February',
                t('js.search.month.march') || 'March',
                t('js.search.month.april') || 'April',
                t('js.search.month.may') || 'May',
                t('js.search.month.june') || 'June',
                t('js.search.month.july') || 'July',
                t('js.search.month.august') || 'August',
                t('js.search.month.september') || 'September',
                t('js.search.month.october') || 'October',
                t('js.search.month.november') || 'November',
                t('js.search.month.december') || 'December'
            ];
            const monthIndex = parseInt(value) - 1;
            if (monthIndex >= 0 && monthIndex < 12) {
                label = monthNames[monthIndex];
            }
        } else if (type === 'module_type') {
            // Map module type to label
            const moduleTypeLabels = {
                'rehearsal': t('js.event.rehearsal') || 'Rehearsal',
                'performance': t('js.event.performance') || 'Performance',
                'concert': t('js.event.performance') || 'Concert'
            };
            label = moduleTypeLabels[value] || value;
        }
        
        // Use gray filter bubble style (like traffic light buttons) with "x" inside
        // Match the filter-bubble style but with gray colors
        return `<button class="filter-chip-btn filter-bubble filter-bubble-gray" data-filter-type="${type}" data-filter-value="${this.escapeHtml(String(value))}" title="Click to remove">
            <span class="filter-chip-label">${this.escapeHtml(label)}</span>
            <span class="filter-chip-remove">×</span>
        </button>`;
    },
    
    /**
     * Render year dropdown
     * @param {number|null} selectedYear Currently selected year
     * @returns {string} HTML string
     */
    renderYearDropdown(selectedYear = null) {
        const t = (k) => (typeof i18n !== 'undefined' && i18n.t ? i18n.t(k) : k);
        
        // Use available years from events if loaded, otherwise fallback
        const years = this.availableYears || [];
        if (years.length === 0) {
            // Fallback: current year ± 5 years
            const currentYear = new Date().getFullYear();
            for (let i = currentYear - 5; i <= currentYear + 5; i++) {
                years.push(i);
            }
        }
        
        let html = `<select class="search-filter-select border border-border rounded-md bg-background text-foreground text-xs px-2 py-1 focus:ring-2 focus:ring-primary" data-filter-type="year">`;
        html += `<option value="">${this.escapeHtml(t('js.search.filter.year') || 'Year')}</option>`;
        
        years.forEach(year => {
            const selected = selectedYear === year ? 'selected' : '';
            html += `<option value="${year}" ${selected}>${year}</option>`;
        });
        
        html += `</select>`;
        return html;
    },
    
    /**
     * Render month dropdown
     * @param {number|null} selectedMonth Currently selected month (1-12)
     * @returns {string} HTML string
     */
    renderMonthDropdown(selectedMonth = null) {
        const t = (k) => (typeof i18n !== 'undefined' && i18n.t ? i18n.t(k) : k);
        
        const monthNames = [
            t('js.search.month.january') || 'January',
            t('js.search.month.february') || 'February',
            t('js.search.month.march') || 'March',
            t('js.search.month.april') || 'April',
            t('js.search.month.may') || 'May',
            t('js.search.month.june') || 'June',
            t('js.search.month.july') || 'July',
            t('js.search.month.august') || 'August',
            t('js.search.month.september') || 'September',
            t('js.search.month.october') || 'October',
            t('js.search.month.november') || 'November',
            t('js.search.month.december') || 'December'
        ];
        
        let html = `<select class="search-filter-select border border-border rounded-md bg-background text-foreground text-xs px-2 py-1 focus:ring-2 focus:ring-primary" data-filter-type="month">`;
        html += `<option value="">${this.escapeHtml(t('js.search.filter.month') || 'Month')}</option>`;
        
        monthNames.forEach((name, index) => {
            const monthValue = index + 1;
            const selected = selectedMonth === monthValue ? 'selected' : '';
            html += `<option value="${monthValue}" ${selected}>${name}</option>`;
        });
        
        html += `</select>`;
        return html;
    },
    
    /**
     * Attach event listeners to filter controls
     * @param {HTMLElement} container Container element
     */
    attachEventListeners(container) {
        // Dropdown change handlers
        const selects = container.querySelectorAll('.search-filter-select');
        selects.forEach(select => {
            select.addEventListener('change', (e) => {
                const type = e.target.getAttribute('data-filter-type');
                const value = e.target.value ? parseInt(e.target.value) : null;
                
                if (this.onFilterChange) {
                    this.onFilterChange(type, value);
                }
            });
        });
        
        // Module type filter bubble handlers - use data attribute selector to match dashboard style buttons
        const moduleTypeButtons = container.querySelectorAll('.filter-bubble[data-filter-type="module_type"]');
        moduleTypeButtons.forEach(button => {
            button.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                const type = e.currentTarget.getAttribute('data-filter-type');
                const value = e.currentTarget.getAttribute('data-filter-value');
                // Check if button has 'selected' class (dashboard pattern)
                const isActive = e.currentTarget.classList.contains('selected');
                
                // Toggle: if active, remove filter; if not active, set filter
                if (this.onFilterChange) {
                    this.onFilterChange(type, isActive ? null : value);
                }
            });
        });
        
        // Filter chip remove handlers
        const chipButtons = container.querySelectorAll('.filter-chip-btn');
        chipButtons.forEach(button => {
            button.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                const type = e.currentTarget.getAttribute('data-filter-type');
                
                if (this.onFilterChange) {
                    this.onFilterChange(type, null);
                }
            });
        });
        
        // Clear all button
        const clearButton = container.querySelector('[data-action="clear-all"]');
        if (clearButton) {
            clearButton.addEventListener('click', () => {
                if (this.onClearAll) {
                    this.onClearAll();
                }
            });
        }
    },
    
    /**
     * Clear all filters UI
     * @param {HTMLElement} container Container element
     */
    clearAll(container) {
        if (!container) return;
        
        const selects = container.querySelectorAll('.search-filter-select');
        selects.forEach(select => {
            select.value = '';
        });
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
window.SearchFilters = SearchFilters;
