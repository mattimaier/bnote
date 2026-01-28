/**
 * BNote Next Generation - Event Renderer (Shared Component)
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
 * Event Renderer - Shared component for rendering events consistently
 * Used by Dashboard and Search to ensure consistent event display
 */
const EventRenderer = {
    /**
     * Map otype to event type string (matching dashboard implementation)
     * @param {string} otype Event otype ('R', 'C', etc.)
     * @returns {string} Event type ('rehearsal', 'performance', 'meeting')
     */
    mapOtypeToEventType(otype) {
        const mapping = {
            'R': 'rehearsal',
            'C': 'performance',
            'A': 'meeting',
            'T': 'meeting',
            'V': 'meeting'
        };
        return mapping[otype] || 'meeting';
    },

    /**
     * Get event type configuration (matching dashboard implementation exactly)
     * @param {string} type Event type ('rehearsal', 'performance', 'meeting')
     * @returns {object} Type configuration with icon, colors, and label
     */
    getEventTypeConfig(type) {
        const getLabel = (key) => (typeof i18n !== 'undefined' && i18n.t ? i18n.t(key) : key);

        const configs = {
            rehearsal: {
                badgeClass: 'event-badge',
                dotClass: 'bg-primary',
                label: getLabel('js.event.rehearsal'),
                icon: 'music'
            },
            performance: {
                badgeClass: 'event-badge accent',
                dotClass: 'bg-accent',
                label: getLabel('js.event.performance'),
                icon: 'calendar'
            },
            meeting: {
                badgeClass: 'event-badge chart-3',
                dotClass: 'bg-chart-3',
                label: getLabel('js.event.meeting'),
                icon: 'users'
            }
        };
        return configs[type] || configs.meeting;
    },

    /**
     * Format event date (matching dashboard implementation)
     * Uses parseEventDate; returns TBA when no valid date.
     * @param {string} dateStr Date string
     * @returns {string} Formatted date
     */
    formatEventDate(dateStr) {
        const tbaText = typeof i18n !== 'undefined' && Object.keys(i18n.translations || {}).length > 0
            ? i18n.t('js.event.tba')
            : 'TBA';
        if (dateStr == null || typeof dateStr !== 'string') return tbaText;
        const date = typeof i18n !== 'undefined' && i18n.parseEventDate ? i18n.parseEventDate(dateStr) : null;
        if (!date) {
            if (typeof console !== 'undefined' && console.warn) console.warn('formatEventDate: invalid date', dateStr);
            return tbaText;
        }
        const locale = typeof i18n !== 'undefined' && i18n.getBrowserLocale
            ? i18n.getBrowserLocale(i18n.getLang())
            : (navigator.language || 'de-DE');
        return new Intl.DateTimeFormat(locale, {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric'
        }).format(date);
    },

    /**
     * Format event time (matching dashboard implementation)
     * Uses parseEventDate.
     * @param {string} dateStr Date string
     * @returns {string} Formatted time
     */
    formatEventTime(dateStr) {
        const tbaText = typeof i18n !== 'undefined' && Object.keys(i18n.translations || {}).length > 0
            ? i18n.t('js.event.tba')
            : 'TBA';
        if (dateStr == null || typeof dateStr !== 'string') return tbaText;
        const date = typeof i18n !== 'undefined' && i18n.parseEventDate ? i18n.parseEventDate(dateStr) : null;
        if (!date) return tbaText;
        const locale = typeof i18n !== 'undefined' && i18n.getBrowserLocale
            ? i18n.getBrowserLocale(i18n.getLang())
            : (navigator.language || 'de-DE');
        return new Intl.DateTimeFormat(locale, { hour: 'numeric', minute: '2-digit' }).format(date);
    },

    /**
     * Extract location from title (fallback)
     * @param {string} title Event title
     * @returns {string|null} Extracted location or null
     */
    extractLocationFromTitle(title) {
        if (!title) return null;
        // Simple extraction - look for common patterns
        const patterns = [
            /@\s*([^,]+)/i,
            /in\s+([^,]+)/i,
            /,\s*([^,]+)$/i
        ];
        for (const pattern of patterns) {
            const match = title.match(pattern);
            if (match && match[1]) {
                return match[1].trim();
            }
        }
        return null;
    },

    /**
     * Escape HTML to prevent XSS
     * @param {string} text Text to escape
     * @returns {string} Escaped text
     */
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    },

    /**
     * Render a single event item (desktop layout with timeline)
     * @param {object} event Event object
     * @param {object} options Rendering options
     * @param {boolean} options.showParticipation Show participation widget
     * @param {boolean} options.isLast Whether this is the last event
     * @param {boolean} options.isMobile Whether to use mobile layout
     * @param {string} options.moduleContext Module context for URL generation ('search' or 'dashboard')
     * @returns {string} HTML string
     */
    renderEventItem(event, options = {}) {
        const {
            showParticipation = false,
            isLast = false,
            isMobile = window.innerWidth < 768,
            moduleContext = null
        } = options;

        const eventType = this.mapOtypeToEventType(event.otype);
        const typeConfig = this.getEventTypeConfig(eventType);
        // Support both dashboard format (eventBegin/dueDate) and search format (begin)
        const dateStr = this.formatEventDate(event.eventBegin || event.dueDate || event.begin);
        const timeStr = this.formatEventTime(event.eventBegin || event.dueDate || event.begin);
        
        const t = (k) => (typeof i18n !== 'undefined' && i18n.t ? i18n.t(k) : k);
        const tbaText = t('js.event.tba') || 'TBA';
        const eventTitleFallback = t('js.event.event') || 'Event';
        // Support both dashboard format (locationData) and search format (location)
        const location = event.location || event.locationData?.name || 
            this.extractLocationFromTitle(event.title) || tbaText;
        const title = event.title || eventTitleFallback;
        const hideTitleWhenDuplicate = title === typeConfig.label;

        // Generate participation widget HTML if needed
        const hasValidEventData = event.oid && event.otype && (event.otype === 'R' || event.otype === 'C');
        const participationWidget = showParticipation && hasValidEventData ? `
            <div 
                class="flex flex-col gap-2 shrink-0 items-end w-fit" 
                data-participation-widget 
                data-event-id="${event.oid}" 
                data-event-type="${event.otype}"
            ></div>
        ` : '';

        // Only make clickable if it's a rehearsal or concert
        const isClickable = event.otype === 'R' || event.otype === 'C';
        const entityType = event.otype === 'C' ? 'concert' : 'rehearsal';
        const entityId = event.oid || event.id;
        
        // Generate entity detail URL
        // Use provided moduleContext, or detect from page if not provided
        const finalModuleContext = moduleContext || (window.location.pathname.includes('search.html') ? 'search' : 'dashboard');
        
        let eventUrl = '#';
        if (isClickable && entityId) {
            if (typeof EntityService !== 'undefined' && typeof EntityService.getEntityDetailUrl === 'function') {
                eventUrl = EntityService.getEntityDetailUrl(entityType, entityId, finalModuleContext, 'view');
            } else if (typeof NavigationService !== 'undefined' && typeof NavigationService.getEntityUrl === 'function') {
                eventUrl = NavigationService.getEntityUrl(entityType, entityId, finalModuleContext, 'view');
            }
        }
        
        const linkClass = isClickable ? 'block no-underline text-foreground hover:text-foreground' : '';
        const wrapperTag = isClickable ? 'a' : 'div';
        const wrapperAttrs = isClickable ? `href="${eventUrl}"` : '';

        if (isMobile) {
            // Mobile compact layout
            return `
                <${wrapperTag} class="relative ${!isLast ? 'border-b border-border/30 pb-2 mb-2' : ''} ${linkClass}" ${wrapperAttrs}>
                    <div class="px-1 transition-all duration-200 group" ${!isClickable ? 'onclick="return false;"' : ''}>
                        <div class="flex items-start gap-2 mb-1.5">
                            <div class="flex-1 min-w-0">
                                <div class="mb-1">
                                    <p class="text-sm font-bold text-foreground leading-tight">${dateStr}</p>
                                </div>
                                <div class="flex items-center gap-1.5 mb-1 flex-wrap">
                                    ${!hideTitleWhenDuplicate ? `<h3 class="text-xs font-semibold text-foreground group-hover:text-primary transition-colors">${this.escapeHtml(title)}</h3>` : ''}
                                    <span class="${typeConfig.badgeClass} text-[10px]">
                                        ${typeConfig.label}
                                    </span>
                                </div>
                                <div class="flex items-center gap-1 text-[10px] text-muted-foreground/80">
                                    <i data-lucide="clock" class="h-2.5 w-2.5 text-primary/60"></i>
                                    <span>${timeStr}</span>
                                </div>
                            </div>
                            ${participationWidget}
                        </div>
                        <div class="flex items-center text-[10px] text-muted-foreground/70 pt-1">
                            <span class="flex items-center gap-1">
                                <i data-lucide="map-pin" class="h-2.5 w-2.5 text-primary/50"></i>
                                ${this.escapeHtml(location)}
                            </span>
                        </div>
                    </div>
                </${wrapperTag}>
            `;
        }

        // Desktop layout with timeline
        return `
            <${wrapperTag} class="relative flex gap-3 ${linkClass}" ${wrapperAttrs}>
                ${!isLast ? `<div class="absolute left-[15px] top-9 h-[calc(100%-12px)] w-0.5 timeline-connector"></div>` : ''}
                <div class="relative z-10 mt-0.5 h-7 w-7 shrink-0 rounded-full ${typeConfig.dotClass} ring-3 ring-background shadow-sm flex items-center justify-center">
                    <i data-lucide="${typeConfig.icon}" class="h-3 w-3 text-white"></i>
                </div>
                <div class="flex-1 rounded-lg border border-border/40 bg-gradient-to-br from-muted/20 to-transparent p-3 transition-all duration-200 hover:shadow-md hover:border-primary/30 group">
                    <div class="flex items-start gap-3 mb-2">
                        <div class="flex-1 min-w-0">
                            <div class="mb-1.5">
                                <p class="text-base font-bold text-foreground leading-tight">${dateStr}</p>
                            </div>
                            <div class="flex items-center gap-2 mb-1.5">
                                ${!hideTitleWhenDuplicate ? `<h3 class="text-sm font-semibold text-foreground group-hover:text-primary transition-colors">${this.escapeHtml(title)}</h3>` : ''}
                                <span class="${typeConfig.badgeClass}">
                                    ${typeConfig.label}
                                </span>
                            </div>
                            <div class="flex items-center gap-1.5 text-xs text-muted-foreground/80">
                                <i data-lucide="clock" class="h-3 w-3 text-primary/60"></i>
                                <span>${timeStr}</span>
                            </div>
                        </div>
                        ${participationWidget}
                    </div>
                    <div class="flex items-center text-xs text-muted-foreground/70 pt-2 border-t border-border/30">
                        <span class="flex items-center gap-1.5">
                            <i data-lucide="map-pin" class="h-3 w-3 text-primary/50"></i>
                            ${this.escapeHtml(location)}
                        </span>
                    </div>
                </div>
            </${wrapperTag}>
        `;
    },

    /**
     * Render multiple event items
     * @param {array} events Array of event objects
     * @param {object} options Rendering options
     * @param {boolean} options.showParticipation Show participation widget
     * @param {boolean} options.isMobile Whether to use mobile layout
     * @returns {string} HTML string
     */
    renderEvents(events, options = {}) {
        if (!Array.isArray(events) || events.length === 0) {
            return '';
        }

        const items = events.map((event, index) => {
            return this.renderEventItem(event, {
                ...options,
                isLast: index === events.length - 1
            });
        }).join('');

        return `<div class="space-y-3">${items}</div>`;
    }
};

// Export for use in other scripts
window.EventRenderer = EventRenderer;
