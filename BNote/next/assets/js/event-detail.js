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
            this.showError(error.message || 'Failed to load event details');
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
        
        container.innerHTML = `
            <div class="event-detail-content space-y-6">
                ${headerHtml}
                
                ${basicInfoHtml}
                
                ${participationWidgetHtml}
                
                <div class="participation-section">
                    <h2 class="text-lg font-semibold text-foreground mb-4">Participation Overview</h2>
                    ${diagramContainer.innerHTML}
                </div>
                
                <div class="participants-section">
                    <h2 class="text-lg font-semibold text-foreground mb-4">Participants</h2>
                    <div id="participant-overview-container">
                        ${participantContainer.innerHTML}
                    </div>
                </div>
                
                ${metadataContainer.innerHTML ? `
                    <div class="metadata-section-wrapper">
                        <h2 class="text-lg font-semibold text-foreground mb-4">Additional Information</h2>
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
        const eventTypeConfig = this.getEventTypeConfig(event.type);
        const title = event.type === 'C' ? event.title : 'Probe';
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
        if (type === 'C') {
            return {
                badgeClass: 'event-badge accent',
                dotClass: 'bg-accent',
                label: 'performance',
                icon: 'calendar'
            };
        } else {
            return {
                badgeClass: 'event-badge',
                dotClass: 'bg-primary',
                label: 'rehearsal',
                icon: 'music'
            };
        }
    },
    
    /**
     * Render participation widget container
     */
    renderParticipationWidget() {
        return `
            <div class="participation-widget-section bg-card border border-border/40 rounded-lg p-4 shadow-sm">
                <div class="flex items-center justify-between">
                    <h3 class="text-base font-semibold text-foreground">Your Participation</h3>
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
        let locationHtml = '<span class="text-muted-foreground">TBA</span>';
        let mapLinkHtml = '';
        
        if (event.location) {
            const location = event.location;
            const addressParts = [
                location.address?.street,
                location.address?.zip && location.address?.city 
                    ? `${location.address.zip} ${location.address.city}` 
                    : location.address?.city
            ].filter(Boolean);
            
            const locationText = [
                location.name,
                ...addressParts
            ].filter(Boolean).join(', ');
            
            locationHtml = `<span>${this.escapeHtml(locationText)}</span>`;
            
            // Google Maps link
            if (addressParts.length > 0) {
                const addressQuery = encodeURIComponent(addressParts.join(', '));
                const mapsUrl = `https://www.google.com/maps/search/?api=1&query=${addressQuery}`;
                mapLinkHtml = `
                    <a 
                        href="${mapsUrl}" 
                        target="_blank" 
                        rel="noopener noreferrer"
                        class="inline-flex items-center gap-1 text-primary hover:text-primary/80 text-sm font-medium"
                    >
                        <i data-lucide="map-pin" class="h-4 w-4"></i>
                        Open in Google Maps
                    </a>
                `;
            }
        }
        
        // Conductor (rehearsals only)
        let conductorHtml = '';
        if (event.type === 'R' && event.conductor) {
            conductorHtml = `
                <div class="info-item">
                    <span class="info-label">Conductor:</span>
                    <span class="info-value">${this.escapeHtml(event.conductor.name)}</span>
                </div>
            `;
        }
        
        // Songs to practice (rehearsals only) - display as list
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
                    <span class="info-label">Songs to practice:</span>
                    <ul class="info-value list-disc list-inside space-y-1 mt-1">
                        ${songsListItems}
                    </ul>
                </div>
            `;
        }
        
        // Concert-specific fields
        let meetingTimeHtml = '';
        let concertNotesHtml = '';
        if (event.type === 'C') {
            meetingTimeHtml = event.meetingtime ? `
                <div class="info-item">
                    <span class="info-label">Meeting Time:</span>
                    <span class="info-value">${this.formatDateTime(event.meetingtime)}</span>
                </div>
            ` : '';
            
            concertNotesHtml = event.notes ? `
                <div class="info-item md:col-span-2">
                    <span class="info-label">Notes:</span>
                    <div class="info-value whitespace-pre-wrap">${this.escapeHtml(event.notes)}</div>
                </div>
            ` : '';
        }
        
        return `
            <div class="basic-info-section bg-card border border-border/40 rounded-lg p-6 shadow-sm">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="info-item">
                        <span class="info-label">Date:</span>
                        <span class="info-value">${beginDate}</span>
                    </div>
                    
                    <div class="info-item">
                        <span class="info-label">Time:</span>
                        <span class="info-value">
                            ${beginTime}${endTime ? ` - ${endTime}` : ''}
                        </span>
                    </div>
                    
                    <div class="info-item">
                        <span class="info-label">Status:</span>
                        <span class="info-value">${statusBadge}</span>
                    </div>
                    
                    ${deadlineHtml}
                    
                    ${conductorHtml}
                    
                    ${meetingTimeHtml}
                    
                    <div class="info-item md:col-span-2">
                        <span class="info-label">Location:</span>
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
        const statusMap = {
            'planned': { text: 'Geplant', color: 'info' },
            'confirmed': { text: 'Bestätigt', color: 'success' },
            'cancelled': { text: 'Abgesagt', color: 'destructive' },
            'hidden': { text: 'Versteckt', color: 'warning' }
        };
        
        const statusInfo = statusMap[status] || { text: status, color: 'primary' };
        return Badge.render(statusInfo.text, statusInfo.color);
    },
    
    /**
     * Render deadline display
     */
    renderDeadline(approveUntil) {
        if (!approveUntil) return '';
        
        const deadlineDate = this.formatDateTime(approveUntil);
        const isPast = new Date(approveUntil) < new Date();
        
        return `
            <div class="info-item">
                <span class="info-label">Deadline:</span>
                <span class="info-value ${isPast ? 'text-destructive' : ''}">
                    ${deadlineDate}
                    ${isPast ? ' <span class="text-xs">(Past)</span>' : ''}
                </span>
            </div>
        `;
    },
    
    /**
     * Format date and time together
     */
    formatDateTime(dateStr) {
        if (!dateStr) return 'TBA';
        try {
            const date = new Date(dateStr);
            return date.toLocaleString('de-DE', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        } catch (e) {
            return dateStr;
        }
    },
    
    /**
     * Format date
     */
    formatDate(dateStr) {
        if (!dateStr) return 'TBA';
        try {
            const date = new Date(dateStr);
            return date.toLocaleDateString('de-DE', {
                weekday: 'long',
                day: '2-digit',
                month: '2-digit',
                year: 'numeric'
            });
        } catch (e) {
            return dateStr;
        }
    },
    
    /**
     * Format time
     */
    formatTime(dateStr) {
        if (!dateStr) return 'TBA';
        try {
            const date = new Date(dateStr);
            return date.toLocaleTimeString('de-DE', {
                hour: '2-digit',
                minute: '2-digit'
            });
        } catch (e) {
            return dateStr;
        }
    },
    
    /**
     * Format status
     */
    formatStatus(status) {
        const statusMap = {
            'planned': 'Geplant',
            'confirmed': 'Bestätigt',
            'cancelled': 'Abgesagt',
            'hidden': 'Versteckt'
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
            container.innerHTML = `
                <div class="bg-destructive/10 border border-destructive/20 rounded-lg p-4 text-destructive">
                    <p class="font-semibold">Error</p>
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
