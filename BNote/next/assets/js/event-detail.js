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
     */
    async init(eventType, eventId) {
        this.eventType = eventType;
        this.eventId = eventId;
        
        // Show detail container, hide dashboard
        const detailContainer = document.getElementById('event-detail-container');
        const dashboardContainer = document.getElementById('dashboard-container');
        
        if (detailContainer) detailContainer.classList.remove('hidden');
        if (dashboardContainer) dashboardContainer.classList.add('hidden');
        
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
            const msg = (typeof i18n !== 'undefined' && i18n.t ? i18n.t('js.error.eventDetailLoadFailed') : 'Failed to load event details.');
            this.showError(error.message || msg);
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
            throw new Error('Invalid event type');
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
        container.innerHTML = `
            <div class="event-detail-content space-y-6">
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
        
        // Initialize participation widget after DOM is ready
        // Use requestAnimationFrame to ensure DOM is fully rendered
        requestAnimationFrame(() => {
            setTimeout(() => {
                this.initializeParticipationWidget();
            }, 50);
        });
        
        // Reinitialize Lucide icons
        if (typeof lucide !== 'undefined') {
            setTimeout(() => lucide.createIcons(), 200);
        }
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
        if (type === 'C') {
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
            widget.updateStatus = async function(status, reason) {
                await originalUpdateStatus(status, reason);
                
                // Refresh event detail after participation update
                if (self && self.eventId && self.eventType) {
                    setTimeout(async () => {
                        try {
                            await self.loadEvent();
                            self.render();
                        } catch (error) {
                            console.error('Failed to refresh event detail:', error);
                        }
                    }, 500);
                }
            };
        } catch (error) {
            console.error('Failed to initialize participation widget:', error);
            widgetContainer.removeAttribute('data-initialized'); // Allow retry
        }
    },
    
    /**
     * Refresh event detail (called after participation update)
     */
    async refresh() {
        try {
            await this.loadEvent();
            this.render();
        } catch (error) {
            console.error('Failed to refresh event detail:', error);
        }
    },
    
    /**
     * Render basic information section (shared between rehearsals and concerts)
     */
    renderBasicInfo(event) {
        const beginDate = this.formatDate(event.begin);
        const beginTime = this.formatTime(event.begin);
        const endTime = event.end ? this.formatTime(event.end) : null;
        const statusBadge = this.renderStatusBadge(event.status);
        const deadlineHtml = this.renderDeadline(event.approve_until);
        
        // Location info
        const tbaText = typeof i18n !== 'undefined' && Object.keys(i18n.translations).length > 0 
            ? i18n.t('js.event.tba') 
            : 'TBA';
        let locationHtml = `<span class="text-muted-foreground">${tbaText}</span>`;
        let mapLinkHtml = '';
        
        if (event.location) {
            const loc = event.location;
            const addr = (typeof i18n !== 'undefined' && i18n.formatAddress)
                ? i18n.formatAddress(loc.address || {})
                : [loc.address?.street, loc.address?.zip, loc.address?.city].filter(Boolean).join(', ');
            const locationLines = [loc.name, addr].filter(Boolean).join('\n');
            const locationEncoded = locationLines.includes('\n')
                ? locationLines.split('\n').map(l => this.escapeHtml(l)).join('<br>')
                : this.escapeHtml(locationLines);
            locationHtml = `<span>${locationEncoded}</span>`;
            const queryLine = [loc.name, addr].filter(Boolean).join(', ').replace(/\n/g, ', ');
            if (queryLine) {
                const mapsUrl = `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(queryLine)}`;
                mapLinkHtml = `
                    <a 
                        href="${mapsUrl}" 
                        target="_blank" 
                        rel="noopener noreferrer"
                        class="inline-flex items-center gap-1 text-primary hover:text-primary/80 text-sm font-medium"
                    >
                        <i data-lucide="map-pin" class="h-4 w-4"></i>
                        ${(typeof i18n !== 'undefined' && i18n.t ? i18n.t('js.event.detail.openInMaps') : 'Open in Google Maps')}
                    </a>
                `;
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
            concertNotesHtml = event.notes ? `
                <div class="info-item md:col-span-2">
                    <span class="info-label">${t('js.event.detail.notes')}:</span>
                    <div class="info-value whitespace-pre-wrap">${this.escapeHtml(event.notes)}</div>
                </div>
            ` : '';
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
                            ${mapLinkHtml}
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
     * Format date (short format: DD.MM.YYYY or MM/DD/YYYY). Uses parseEventDate.
     */
    formatDate(dateStr) {
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
            year: 'numeric'
        }).format(date);
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
     * Show error state
     */
    showError(message) {
        const container = document.getElementById('event-detail-content');
        if (container) {
            const errLabel = (typeof i18n !== 'undefined' && i18n.t ? i18n.t('js.common.error') : 'Error');
            container.innerHTML = `
                <div class="bg-destructive/10 border border-destructive/20 rounded-lg p-4 text-destructive">
                    <p class="font-semibold">${this.escapeHtml(errLabel)}</p>
                    <p class="text-sm mt-1">${this.escapeHtml(message)}</p>
                </div>
            `;
        }
    },
    
    /**
     * Navigate back to dashboard
     */
    navigateBack() {
        const detailContainer = document.getElementById('event-detail-container');
        const dashboardContainer = document.getElementById('dashboard-container');
        
        if (detailContainer) detailContainer.classList.add('hidden');
        if (dashboardContainer) dashboardContainer.classList.remove('hidden');
        
        // Reset state
        this.currentEvent = null;
        this.eventType = null;
        this.eventId = null;
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
