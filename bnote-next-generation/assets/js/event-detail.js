/**
 * BNote Next Generation - Event Detail Component
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
 * Event Detail Component
 * Main orchestrator for detail view (shared between rehearsals and concerts)
 */
const EventDetail = {
    currentEvent: null,
    eventType: null,
    eventId: null,

    /**
     * Initialize detail view
     * @param {string} eventType - Event type ('R' for rehearsal, 'C' for concert)
     * @param {number} eventId - Event ID
     * @param {boolean} fromPopstate - If true, we're navigating via popstate (don't push new state)
     */
    async init(eventType, eventId, fromPopstate = false) {
        this.eventType = eventType;
        this.eventId = eventId;

        // Check if we came from search results (check internal flag or current state's previousView)
        // When fromPopstate=true, history.state already has the correct state with previousView
        const currentState = history.state;
        const cameFromSearch = (this._fromSearch && this._searchQuery) ||
            (currentState && currentState.previousView === 'search-results') ||
            (currentState && currentState.view === 'search-results');
        const searchQuery = this._searchQuery || (currentState && currentState.searchQuery) || (currentState && currentState.query) || null;
        const searchFilters = this._searchFilters || (currentState && currentState.searchFilters) || (currentState && currentState.filters) || null;

        // Push state to history for browser back button support
        // Only push if URL doesn't already have the correct parameter AND we're not navigating via popstate
        const param = eventType === 'R' ? 'rehearsal' : 'concert';
        const urlParams = new URLSearchParams(window.location.search);
        const currentParamValue = urlParams.get(param);

        // Only push/replace state if:
        // 1. Not navigating via popstate (popstate already has the correct state)
        // 2. URL doesn't match or we're not already in event detail view
        if (!fromPopstate && (currentParamValue !== String(eventId) || !history.state || history.state.view !== 'event-detail')) {
            const state = {
                view: 'event-detail',
                eventType,
                eventId,
                previousView: cameFromSearch ? 'search-results' : (currentState?.view || 'dashboard'),
                searchQuery: searchQuery,
                searchFilters: searchFilters
            };
            const url = `?${param}=${eventId}`;
            history.pushState(state, '', url);
        }

        // Clear internal flags after use
        this._fromSearch = false;
        this._searchQuery = null;
        this._searchFilters = null;

        // Show detail container, hide dashboard and search results
        const detailContainer = document.getElementById('event-detail-container');
        const dashboardContainer = document.getElementById('dashboard-container');
        const searchResultsContainer = document.getElementById('search-results-container');

        if (detailContainer) detailContainer.classList.remove('hidden');
        if (dashboardContainer) dashboardContainer.classList.add('hidden');
        if (searchResultsContainer) searchResultsContainer.classList.add('hidden');

        // Show loading state
        this.showLoading();

        try {
            // Load event data
            await this.loadEvent();

            // Render detail view
            this.render();

            // Reinitialize Lucide icons
            if (typeof lucide !== 'undefined') {
                setTimeout(() => lucide.createIcons(), 100);
            }
        } catch (error) {
            console.error('Failed to load event detail:', error);
            // showError will handle translation mapping
            this.showError(error.message || 'Failed to load event details.');
        }
    },

    /**
     * Load event data from API
     */
    async loadEvent() {
        let eventData;

        if (this.eventType === 'R') {
            eventData = await RehearsalsApi.get(this.eventId);
        } else if (this.eventType === 'C') {
            eventData = await ConcertsApi.get(this.eventId);
        } else {
            const t = (k) => (typeof i18n !== 'undefined' && i18n.t ? i18n.t(k) : k);
            throw new Error(t('js.error.invalidEventType'));
        }

        this.currentEvent = eventData;
    },

    /**
     * Render complete detail view
     */
    render() {
        const container = document.getElementById('event-detail-content');
        if (!container) return;

        const event = this.currentEvent;
        if (!event) return;

        // Render header with badge and icon
        const headerHtml = this.renderHeader(event);

        // Render basic info
        const basicInfoHtml = this.renderBasicInfo(event);

        // Render participation widget
        const participationWidgetHtml = this.renderParticipationWidget();

        // Render participation diagram
        const diagramContainer = document.createElement('div');
        ParticipationDiagram.render(diagramContainer, event.participationStats);

        // Render participant overview (with container ID for re-rendering)
        const participantContainer = document.createElement('div');
        participantContainer.id = 'participant-overview-container';
        ParticipantOverview.render(participantContainer, event.participantsByInstrument);

        // Render metadata (concert-specific)
        const metadataContainer = document.createElement('div');
        EventMetadata.render(metadataContainer, event);

        const t = (k) => (typeof i18n !== 'undefined' && i18n.t ? i18n.t(k) : k);

        // Use requestAnimationFrame to batch DOM updates and prevent flashing
        requestAnimationFrame(() => {
            // Fade out slightly before update to make transition smoother
            const currentContent = container.querySelector('.event-detail-content');
            if (currentContent) {
                currentContent.style.opacity = '0.7';
                currentContent.style.transition = 'opacity 0.15s ease';
            }

            // Update content
            requestAnimationFrame(() => {
                container.innerHTML = `
                    <div class="event-detail-content space-y-6" style="opacity: 0; transition: opacity 0.2s ease;">
                        ${headerHtml}
                        ${basicInfoHtml}
                        ${participationWidgetHtml}
                        <div class="participation-section">
                            <h2 class="text-lg font-semibold text-foreground mb-4">${t('js.event.detail.participationOverview')}</h2>
                            ${diagramContainer.innerHTML}
                        </div>
                        <div class="participants-section">
                            <h2 class="text-lg font-semibold text-foreground mb-4">${t('js.event.detail.participants')}</h2>
                            <div id="participant-overview-container">
                                ${participantContainer.innerHTML}
                            </div>
                        </div>
                        ${metadataContainer.innerHTML ? `
                            <div class="metadata-section-wrapper">
                                <h2 class="text-lg font-semibold text-foreground mb-4">${t('js.event.detail.additionalInfo')}</h2>
                                ${metadataContainer.innerHTML}
                            </div>
                        ` : ''}
                    </div>
                `;

                // Fade in new content
                const newContent = container.querySelector('.event-detail-content');
                if (newContent) {
                    requestAnimationFrame(() => {
                        newContent.style.opacity = '1';
                    });
                }

                // Reinitialize icons after render
                if (typeof lucide !== 'undefined') {
                    setTimeout(() => lucide.createIcons(), 50);
                }

                // Reinitialize participation widget after render
                setTimeout(() => {
                    this.initializeParticipationWidget();
                }, 100);

                // Initialize address click handlers after DOM is updated
                setTimeout(() => {
                    this.initializeAddressClickHandlers();
                }, 150);
            });
        });

        // Reinitialize Lucide icons (outside requestAnimationFrame for initial render)
        if (typeof lucide !== 'undefined') {
            setTimeout(() => lucide.createIcons(), 200);
        }

        // Browser handles back navigation - no custom back button needed
    },

    /**
     * Render header with event type badge and icon
     * Order: Icon Text Badge
     */
    renderHeader(event) {
        const t = (k) => (typeof i18n !== 'undefined' && i18n.t ? i18n.t(k) : k);
        const eventTypeConfig = this.getEventTypeConfig(event.type);
        const title = event.type === 'C' ? event.title : t('js.event.rehearsal');
        const eventTypeBadge = Badge.render(
            eventTypeConfig.label,
            event.type === 'C' ? 'accent' : 'primary'
        );

        // Browser handles back navigation - no custom back button needed

        return `
            <div class="event-detail-header flex items-center gap-3 mb-4">
                <div class="h-8 w-8 rounded-full ${eventTypeConfig.dotClass} ring-2 ring-background shadow-sm flex items-center justify-center">
                    <i data-lucide="${eventTypeConfig.icon}" class="h-4 w-4 text-white"></i>
                </div>
                <h1 class="text-2xl font-bold text-foreground">${this.escapeHtml(title)}</h1>
                ${eventTypeBadge}
            </div>
        `;
    },

    /**
     * Get event type configuration (matching dashboard)
     */
    getEventTypeConfig(type) {
        const t = (k) => (typeof i18n !== 'undefined' && i18n.t ? i18n.t(k) : k);

        // Map single letter codes to full type names
        const typeMap = {
            'C': 'performance',
            'R': 'rehearsal'
        };
        const eventType = typeMap[type] || type;

        // Use EntityConfig if available
        if (typeof EntityConfig !== 'undefined' && EntityConfig.getEventConfig) {
            return EntityConfig.getEventConfig(eventType, t);
        }

        // Fallback to hardcoded configs
        if (type === 'C' || eventType === 'performance') {
            return { badgeClass: 'event-badge accent', dotClass: 'bg-accent', label: t('js.event.performance'), icon: 'calendar' };
        }
        return { badgeClass: 'event-badge', dotClass: 'bg-primary', label: t('js.event.rehearsal'), icon: 'music' };
    },

    /**
     * Render participation widget container
     */
    renderParticipationWidget() {
        const t = (k) => (typeof i18n !== 'undefined' && i18n.t ? i18n.t(k) : k);
        return `
            <div class="participation-widget-section bg-card border border-border/40 rounded-lg p-4 shadow-sm">
                <div class="flex items-center justify-between">
                    <h3 class="text-base font-semibold text-foreground">${t('js.event.detail.yourParticipation')}</h3>
                    <div 
                        class="flex flex-col gap-2 items-end" 
                        data-participation-widget 
                        data-event-id="${this.eventId}" 
                        data-event-type="${this.eventType}"
                    ></div>
                </div>
            </div>
        `;
    },

    /**
     * Initialize participation widget
     */
    initializeParticipationWidget() {
        // Query widget container from the event detail content area
        const eventDetailContent = document.getElementById('event-detail-content');
        if (!eventDetailContent) {
            console.warn('Event detail content container not found');
            return;
        }

        const widgetContainer = eventDetailContent.querySelector('[data-participation-widget]');
        if (!widgetContainer) {
            console.warn('Participation widget container not found');
            return;
        }

        if (typeof ParticipationWidget === 'undefined') {
            console.error('ParticipationWidget class not available');
            return;
        }

        // Check if already initialized
        if (widgetContainer.hasAttribute('data-initialized')) {
            return; // Already initialized
        }

        widgetContainer.setAttribute('data-initialized', 'true');

        try {
            const widget = new ParticipationWidget(widgetContainer, this.eventId, this.eventType);

            // Store reference to EventDetail for refresh callback
            const self = this;

            // Hook into the widget's updateStatus by wrapping it
            const originalUpdateStatus = widget.updateStatus.bind(widget);
            widget.updateStatus = async function (status, reason) {
                await originalUpdateStatus(status, reason);

                // Refresh event detail after participation update
                // Use the centralized refresh method which has debouncing
                if (self && self.eventId && self.eventType) {
                    // Use the refresh method which already has debouncing
                    await self.refresh();
                }
            };
        } catch (error) {
            console.error('Failed to initialize participation widget:', error);
            widgetContainer.removeAttribute('data-initialized'); // Allow retry
        }
    },

    /**
     * Refresh event detail (called after participation update)
     * Uses debouncing to prevent multiple rapid refreshes
     * Only updates changed parts to minimize flashing
     */
    async refresh() {
        // Clear any pending refresh
        if (this._refreshTimeout) {
            clearTimeout(this._refreshTimeout);
        }

        // Debounce: wait a bit to allow multiple rapid updates to batch together
        this._refreshTimeout = setTimeout(async () => {
            try {
                // Load fresh data
                await this.loadEvent();

                // Re-render with smooth fade transition (handled in render method)
                this.render();
            } catch (error) {
                console.error('Failed to refresh event detail:', error);
            }
        }, 400);
    },

    /**
     * Detect if text contains markdown syntax patterns
     * @param {string} text - Text to check
     * @returns {boolean} - True if markdown patterns detected
     */
    isMarkdown(text) {
        if (!text || typeof text !== 'string') return false;

        // Common markdown patterns
        const markdownPatterns = [
            /^#{1,6}\s+.+/m,                    // Headers: #, ##, ###, etc.
            /\*\*[^*]+\*\*/,                    // Bold: **text**
            /__[^_]+__/,                        // Bold: __text__
            /\*[^*]+\*/,                        // Italic: *text*
            /_[^_]+_/,                          // Italic: _text_
            /^[-*+]\s+.+/m,                     // Unordered lists: -, *, +
            /^\d+\.\s+.+/m,                     // Ordered lists: 1., 2., etc.
            /\[.+\]\(.+\)/,                     // Links: [text](url)
            /`[^`]+`/,                          // Inline code: `code`
            /```[\s\S]+```/,                    // Code blocks: ```code```
            /^>\s+.+/m,                         // Blockquotes: > text
            /^\s*\|.+\|/m,                      // Tables: | col1 | col2 |
            /^---+$/m,                          // Horizontal rules: ---
            /^\*\*\*+$/m                        // Horizontal rules: ***
        ];

        return markdownPatterns.some(pattern => pattern.test(text));
    },

    /**
     * Render markdown text to HTML with security configuration
     * @param {string} markdown - Markdown text to render
     * @returns {string} - Rendered HTML
     */
    renderMarkdown(markdown) {
        if (!markdown || typeof markdown !== 'string') return '';

        // Check if marked is available
        if (typeof marked === 'undefined') {
            console.warn('marked.js not available, falling back to plain text');
            return this.escapeHtml(markdown);
        }

        try {
            // Configure marked with security options
            // Use modern API if available, fallback to setOptions for older versions
            if (typeof marked.use === 'function') {
                marked.use({
                    breaks: true,           // Convert line breaks to <br>
                    gfm: true,              // GitHub Flavored Markdown
                    silent: true            // Suppress warnings
                });
            } else if (typeof marked.setOptions === 'function') {
                marked.setOptions({
                    breaks: true,
                    gfm: true,
                    sanitize: false,        // We'll sanitize manually
                    silent: true
                });
            }

            // Parse markdown to HTML
            let html = marked.parse(markdown);

            // Basic XSS protection: remove script tags and event handlers
            const div = document.createElement('div');
            div.innerHTML = html;

            // Remove script tags
            const scripts = div.querySelectorAll('script');
            scripts.forEach(script => script.remove());

            // Remove event handlers from all elements
            const allElements = div.querySelectorAll('*');
            allElements.forEach(el => {
                // Remove all event handler attributes
                Array.from(el.attributes).forEach(attr => {
                    if (attr.name.startsWith('on')) {
                        el.removeAttribute(attr.name);
                    }
                });
            });

            return div.innerHTML;
        } catch (error) {
            console.error('Error rendering markdown:', error);
            // Fallback to escaped plain text
            return this.escapeHtml(markdown);
        }
    },

    /**
     * Render basic information section (shared between rehearsals and concerts)
     */
    renderBasicInfo(event) {
        // Check if event is in the past
        const isEventPast = this.isEventPast(event.begin);
        const beginDate = this.formatDate(event.begin, isEventPast);
        const beginTime = this.formatTime(event.begin);
        const endTime = event.end ? this.formatTime(event.end) : null;
        const statusBadge = this.renderStatusBadge(event.status);
        const deadlineHtml = this.renderDeadline(event.approve_until);

        // Location info
        const tbaText = typeof i18n !== 'undefined' && Object.keys(i18n.translations).length > 0
            ? i18n.t('js.event.tba')
            : 'TBA';
        let locationHtml = `<span class="text-muted-foreground">${tbaText}</span>`;
        let locationQueryLine = '';

        if (event.location) {
            const loc = event.location;
            const addr = (typeof i18n !== 'undefined' && i18n.formatAddress)
                ? i18n.formatAddress(loc.address || {})
                : [loc.address?.street, loc.address?.zip, loc.address?.city].filter(Boolean).join(', ');
            const locationLines = [loc.name, addr].filter(Boolean).join('\n');
            const locationEncoded = locationLines.includes('\n')
                ? locationLines.split('\n').map(l => this.escapeHtml(l)).join('<br>')
                : this.escapeHtml(locationLines);
            locationQueryLine = [loc.name, addr].filter(Boolean).join(', ').replace(/\n/g, ', ');
            if (locationQueryLine) {
                locationHtml = `<span class="address-clickable cursor-pointer hover:text-primary transition-colors" data-address-query="${this.escapeHtml(locationQueryLine)}">${locationEncoded}</span>`;
            } else {
                locationHtml = `<span>${locationEncoded}</span>`;
            }
        }

        // Conductor (rehearsals only)
        let conductorHtml = '';
        const t = (k) => (typeof i18n !== 'undefined' && i18n.t ? i18n.t(k) : k);
        if (event.type === 'R' && event.conductor) {
            conductorHtml = `
                <div class="info-item">
                    <span class="info-label">${t('js.event.detail.conductor')}:</span>
                    <span class="info-value">${this.escapeHtml(event.conductor.name)}</span>
                </div>
            `;
        }

        let songsHtml = '';
        if (event.type === 'R' && event.songsToPractice && event.songsToPractice.length > 0) {
            const songsListItems = event.songsToPractice.map(song => {
                const notesHtml = song.notes && song.notes.trim()
                    ? ` <span class="text-xs text-muted-foreground">(${this.escapeHtml(song.notes)})</span>`
                    : '';
                return `<li class="text-sm">${this.escapeHtml(song.title)}${notesHtml}</li>`;
            }).join('');
            songsHtml = `
                <div class="info-item md:col-span-2">
                    <span class="info-label">${t('js.event.detail.songsToPractice')}:</span>
                    <ul class="info-value list-disc list-inside space-y-1 mt-1">
                        ${songsListItems}
                    </ul>
                </div>
            `;
        }

        let meetingTimeHtml = '';
        let concertNotesHtml = '';
        if (event.type === 'C') {
            meetingTimeHtml = event.meetingtime ? `
                <div class="info-item">
                    <span class="info-label">${t('js.event.detail.meetingTime')}:</span>
                    <span class="info-value">${this.formatDateTime(event.meetingtime)}</span>
                </div>
            ` : '';

            // Render concert notes with markdown support
            if (event.notes) {
                const isMarkdownContent = this.isMarkdown(event.notes);
                const notesContent = isMarkdownContent
                    ? this.renderMarkdown(event.notes)
                    : this.escapeHtml(event.notes);
                const notesClass = isMarkdownContent
                    ? 'info-value prose prose-sm max-w-none'
                    : 'info-value whitespace-pre-wrap';

                concertNotesHtml = `
                    <div class="info-item md:col-span-2">
                        <span class="info-label">${t('js.event.detail.notes')}:</span>
                        <div class="${notesClass}">${notesContent}</div>
                    </div>
                `;
            }
        }

        return `
            <div class="basic-info-section bg-card border border-border/40 rounded-lg p-6 shadow-sm">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="info-item">
                        <span class="info-label">${t('js.event.detail.date')}:</span>
                        <span class="info-value">${beginDate}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">${t('js.event.detail.time')}:</span>
                        <span class="info-value">${beginTime}${endTime ? ` - ${endTime}` : ''}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">${t('js.event.detail.status')}:</span>
                        <span class="info-value">${statusBadge}</span>
                    </div>
                    ${deadlineHtml}
                    ${conductorHtml}
                    ${meetingTimeHtml}
                    <div class="info-item md:col-span-2">
                        <span class="info-label">${t('js.event.detail.location')}:</span>
                        <div class="flex items-center gap-2 flex-wrap">
                            ${locationHtml}
                        </div>
                    </div>
                    ${songsHtml}
                    ${concertNotesHtml}
                </div>
            </div>
        `;
    },

    /**
     * Render status badge using Badge component
     */
    renderStatusBadge(status) {
        const t = (k) => (typeof i18n !== 'undefined' && i18n.t ? i18n.t(k) : k);
        const statusMap = {
            'planned': { text: t('js.event.status.planned'), color: 'info' },
            'confirmed': { text: t('js.event.status.confirmed'), color: 'success' },
            'cancelled': { text: t('js.event.status.cancelled'), color: 'destructive' },
            'hidden': { text: t('js.event.status.hidden'), color: 'warning' }
        };
        const statusInfo = statusMap[status] || { text: status, color: 'primary' };
        return Badge.render(statusInfo.text, statusInfo.color);
    },

    renderDeadline(approveUntil) {
        if (!approveUntil) return '';
        const t = (k) => (typeof i18n !== 'undefined' && i18n.t ? i18n.t(k) : k);
        const deadlineDate = this.formatDateTime(approveUntil);
        const d = (typeof i18n !== 'undefined' && i18n.parseEventDate ? i18n.parseEventDate(approveUntil) : null) || new Date(approveUntil);
        const isPast = !isNaN(d.getTime()) && d < new Date();
        return `
            <div class="info-item">
                <span class="info-label">${t('js.event.detail.deadline')}:</span>
                <span class="info-value ${isPast ? 'text-destructive' : ''}">
                    ${deadlineDate}
                    ${isPast ? ` <span class="text-xs">(${t('js.event.detail.past')})</span>` : ''}
                </span>
            </div>
        `;
    },

    /**
     * Format date and time together (short date, time without seconds). Uses parseEventDate.
     */
    formatDateTime(dateStr) {
        const tbaText = typeof i18n !== 'undefined' && Object.keys(i18n.translations || {}).length > 0
            ? i18n.t('js.event.tba')
            : 'TBA';
        if (dateStr == null || typeof dateStr !== 'string') return tbaText;
        const date = typeof i18n !== 'undefined' && i18n.parseEventDate ? i18n.parseEventDate(dateStr) : null;
        if (!date) return tbaText;
        const locale = typeof i18n !== 'undefined' && i18n.getBrowserLocale
            ? i18n.getBrowserLocale(i18n.getLang())
            : (navigator.language || 'en-US');
        return new Intl.DateTimeFormat(locale, {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: 'numeric',
            minute: '2-digit'
        }).format(date);
    },

    /**
     * Check if event is in the past based on begin date
     */
    isEventPast(beginDateStr) {
        if (!beginDateStr || typeof beginDateStr !== 'string') return false;
        const date = typeof i18n !== 'undefined' && i18n.parseEventDate ? i18n.parseEventDate(beginDateStr) : null;
        if (!date) return false;
        return date < new Date();
    },

    /**
     * Format date (short format: DD.MM.YYYY or MM/DD/YYYY). Uses parseEventDate.
     * @param {string} dateStr - Date string to format
     * @param {boolean} isPast - Whether to append "(Vergangen)" for past events
     */
    formatDate(dateStr, isPast = false) {
        const tbaText = typeof i18n !== 'undefined' && Object.keys(i18n.translations || {}).length > 0
            ? i18n.t('js.event.tba')
            : 'TBA';
        if (dateStr == null || typeof dateStr !== 'string') return tbaText;
        const date = typeof i18n !== 'undefined' && i18n.parseEventDate ? i18n.parseEventDate(dateStr) : null;
        if (!date) return tbaText;
        const locale = typeof i18n !== 'undefined' && i18n.getBrowserLocale
            ? i18n.getBrowserLocale(i18n.getLang())
            : (navigator.language || 'en-US');
        const formattedDate = new Intl.DateTimeFormat(locale, {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric'
        }).format(date);

        // Add "(Vergangen)" if event is in the past
        if (isPast) {
            const t = (k) => (typeof i18n !== 'undefined' && i18n.t ? i18n.t(k) : k);
            return `${formattedDate} (${t('js.event.detail.past')})`;
        }

        return formattedDate;
    },

    /**
     * Format time (hours and minutes only, no seconds). Uses parseEventDate.
     */
    formatTime(dateStr) {
        const tbaText = typeof i18n !== 'undefined' && Object.keys(i18n.translations || {}).length > 0
            ? i18n.t('js.event.tba')
            : 'TBA';
        if (dateStr == null || typeof dateStr !== 'string') return tbaText;
        const date = typeof i18n !== 'undefined' && i18n.parseEventDate ? i18n.parseEventDate(dateStr) : null;
        if (!date) return tbaText;
        const locale = typeof i18n !== 'undefined' && i18n.getBrowserLocale
            ? i18n.getBrowserLocale(i18n.getLang())
            : (navigator.language || 'en-US');
        return new Intl.DateTimeFormat(locale, { hour: 'numeric', minute: '2-digit' }).format(date);
    },

    /**
     * Format status
     */
    formatStatus(status) {
        const t = (k) => (typeof i18n !== 'undefined' && i18n.t ? i18n.t(k) : k);
        const statusMap = {
            planned: t('js.event.status.planned'),
            confirmed: t('js.event.status.confirmed'),
            cancelled: t('js.event.status.cancelled'),
            hidden: t('js.event.status.hidden')
        };
        return statusMap[status] || status;
    },

    /**
     * Show loading state
     */
    showLoading() {
        const container = document.getElementById('event-detail-content');
        if (container) {
            container.innerHTML = `
                <div class="flex items-center justify-center py-12">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
                </div>
            `;
        }
    },

    /**
     * Map API error messages to translation keys
     * @param {string} errorMessage - API error message
     * @returns {string} Translation key or original message
     */
    mapErrorToTranslation(errorMessage) {
        if (!errorMessage || typeof errorMessage !== 'string') {
            return 'js.error.eventDetailLoadFailed';
        }

        const lowerMessage = errorMessage.toLowerCase();

        // Map common API error messages to translation keys
        if (lowerMessage.includes('invalid') && (lowerMessage.includes('concert id') || lowerMessage.includes('rehearsal id') || lowerMessage.includes('event id'))) {
            return 'js.error.invalidEventId';
        }
        if (lowerMessage.includes('invalid') && lowerMessage.includes('event type')) {
            return 'js.error.invalidEventType';
        }
        if (lowerMessage.includes('not found') && (lowerMessage.includes('concert') || lowerMessage.includes('rehearsal') || lowerMessage.includes('event'))) {
            return 'js.error.eventNotFound';
        }
        if (lowerMessage.includes('access denied') || lowerMessage.includes('forbidden')) {
            return 'js.error.eventAccessDenied';
        }

        // Default fallback
        return 'js.error.eventDetailLoadFailed';
    },

    /**
     * Show error state
     */
    showError(message) {
        const container = document.getElementById('event-detail-content');
        if (container) {
            const t = (k) => (typeof i18n !== 'undefined' && i18n.t ? i18n.t(k) : k);
            const errLabel = t('js.common.error');

            // Try to map API error message to translation key
            const translationKey = this.mapErrorToTranslation(message);
            const translatedMessage = translationKey.startsWith('js.error.')
                ? t(translationKey)
                : this.escapeHtml(message);

            container.innerHTML = `
                <div class="bg-destructive/10 border border-destructive/20 rounded-lg p-4 text-destructive">
                    <p class="font-semibold">${this.escapeHtml(errLabel)}</p>
                    <p class="text-sm mt-1">${translatedMessage}</p>
                </div>
            `;
        }
    },

    /**
     * Navigate back to dashboard or search results
     */
    navigateBack() {
        // Always use browser history - popstate handler will show the appropriate view
        if (history.length > 1) {
            history.back();
        } else {
            // No history - show dashboard
            this.showDashboard();
        }
    },

    /**
     * Show dashboard (called from history popstate or direct navigation)
     */
    showDashboard() {
        const detailContainer = document.getElementById('event-detail-container');
        const dashboardContainer = document.getElementById('dashboard-container');

        if (detailContainer) detailContainer.classList.add('hidden');
        if (dashboardContainer) dashboardContainer.classList.remove('hidden');

        // Clean URL when showing dashboard (remove event parameters)
        if (typeof Routing !== 'undefined') {
            Routing.cleanUrl();
        }

        // Reinitialize icons
        if (typeof lucide !== 'undefined') {
            setTimeout(() => lucide.createIcons(), 50);
        }

        // Reset state
        this.currentEvent = null;
        this.eventType = null;
        this.eventId = null;
    },

    /**
     * Initialize address click handlers for context menu
     * Uses event delegation to handle dynamically added elements
     */
    initializeAddressClickHandlers() {
        // Remove any existing listener to avoid duplicates
        if (this._addressClickHandler) {
            const container = document.getElementById('event-detail-content');
            if (container) {
                container.removeEventListener('click', this._addressClickHandler);
            }
        }

        // Create a bound handler function
        this._addressClickHandler = (e) => {
            // Check if clicked element or its parent has the address-clickable class
            const addressElement = e.target.closest('.address-clickable');
            if (addressElement) {
                e.preventDefault();
                e.stopPropagation();
                const query = addressElement.getAttribute('data-address-query');
                if (query) {
                    // Pass both the original event and the address element for proper positioning
                    this.showMapContextMenu(e, query, addressElement);
                }
            }
        };

        // Use event delegation on the container
        const container = document.getElementById('event-detail-content');
        if (container) {
            container.addEventListener('click', this._addressClickHandler);
        }
    },

    /**
     * Show context menu for map selection
     * @param {Event} event - The click event
     * @param {string} query - The address query string
     * @param {HTMLElement} addressElement - The address element (for positioning)
     */
    showMapContextMenu(event, query, addressElement = null) {
        // Remove any existing context menu
        const existingMenu = document.getElementById('map-context-menu');
        if (existingMenu) {
            existingMenu.remove();
        }

        const t = (k) => (typeof i18n !== 'undefined' && i18n.t ? i18n.t(k) : k);

        // Create context menu
        const menu = document.createElement('div');
        menu.id = 'map-context-menu';
        menu.className = 'map-context-menu';
        menu.innerHTML = `
            <div class="map-context-menu-item" data-map-type="google">
                <i data-lucide="map-pin" class="h-4 w-4"></i>
                <span>${t('js.event.detail.openInGoogleMaps')}</span>
            </div>
            <div class="map-context-menu-item" data-map-type="apple">
                <i data-lucide="map-pin" class="h-4 w-4"></i>
                <span>${t('js.event.detail.openInAppleMaps')}</span>
            </div>
        `;

        document.body.appendChild(menu);

        // Position menu near click - use addressElement if available, otherwise event.target
        const targetElement = addressElement || event.target;
        const rect = targetElement.getBoundingClientRect();
        menu.style.left = `${rect.left}px`;
        menu.style.top = `${rect.bottom + 4}px`;

        // Initialize Lucide icons in menu
        if (typeof lucide !== 'undefined') {
            setTimeout(() => lucide.createIcons(), 10);
        }

        // Handle menu item clicks
        menu.querySelectorAll('.map-context-menu-item').forEach(item => {
            item.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                const mapType = item.getAttribute('data-map-type');
                this.openInMaps(query, mapType);
                menu.remove();
            });
        });

        // Close menu on outside click
        const closeMenu = (e) => {
            if (!menu.contains(e.target) && !event.target.contains(e.target)) {
                menu.remove();
                document.removeEventListener('click', closeMenu);
            }
        };
        setTimeout(() => {
            document.addEventListener('click', closeMenu);
        }, 0);
    },

    /**
     * Open address in selected map service
     */
    openInMaps(query, mapType) {
        let url;
        if (mapType === 'google') {
            url = `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(query)}`;
        } else if (mapType === 'apple') {
            url = `https://maps.apple.com/?q=${encodeURIComponent(query)}`;
        } else {
            return;
        }
        window.open(url, '_blank', 'noopener,noreferrer');
    },

    /**
     * Escape HTML to prevent XSS
     */
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
};

// Export to window for global access
if (typeof window !== 'undefined') {
    window.EventDetail = EventDetail;
}
