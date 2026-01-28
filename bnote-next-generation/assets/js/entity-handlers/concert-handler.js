/**
 * BNote Next Generation - Concert Handler
 *
 * Copyright (C) 2026 BNote Contributors
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * Concert Handler
 * Renders concert entity detail views
 */
class ConcertHandler {
    /**
     * Render concert detail view
     * @param {string} entityType - Entity type ('concert')
     * @param {number} id - Concert ID
     * @param {string} mode - View mode ('view', 'edit', 'create')
     * @param {HTMLElement} container - Container to render into
     */
    async renderDetail(entityType, id, mode, container) {
        try {
            // Load concert data using ConcertsApi (action is null, id in params)
            const concert = typeof ConcertsApi !== 'undefined' && ConcertsApi.get
                ? await ConcertsApi.get(id)
                : await api.get('concerts', null, { id });
            
            // Render detail view
            if (mode === 'view') {
                // Use EventDetail if available
                if (typeof EventDetail !== 'undefined') {
                    // Set up EventDetail state
                    EventDetail.currentEvent = concert;
                    EventDetail.eventType = 'C';
                    EventDetail.eventId = id;
                    
                    // Create container structure (browser handles back navigation)
                    container.innerHTML = `
                        <div id="event-detail-content" class="mx-auto max-w-4xl"></div>
                    `;
                    
                    // Wait for DOM to update
                    await new Promise(resolve => setTimeout(resolve, 10));
                    
                    // Render using EventDetail (it will find #event-detail-content)
                    EventDetail.render();
                    
                    // Wait for EventDetail to finish rendering
                    await new Promise(resolve => setTimeout(resolve, 200));
                    
                    // Reinitialize participation widget
                    if (typeof EventDetail !== 'undefined' && typeof EventDetail.initializeParticipationWidget === 'function') {
                        EventDetail.initializeParticipationWidget();
                    }
                } else {
                    // Fallback
                    container.innerHTML = this.renderConcertView(concert);
                }
            } else if (mode === 'edit') {
                container.innerHTML = this.renderConcertEdit(concert);
            } else if (mode === 'create') {
                container.innerHTML = this.renderConcertCreate();
            }
            
            // Initialize lucide icons
            if (typeof lucide !== 'undefined') {
                setTimeout(() => lucide.createIcons(), 100);
            }
        } catch (error) {
            console.error('ConcertHandler: Failed to render detail:', error);
            container.innerHTML = `<div class="text-error">Failed to load concert: ${error.message}</div>`;
        }
    }
    
    /**
     * Render concert view (read-only)
     */
    renderConcertView(concert) {
        // Use existing EventDetail rendering if available
        if (typeof EventDetail !== 'undefined') {
            // Store event data in EventDetail
            EventDetail.currentEvent = concert;
            EventDetail.eventType = 'C';
            EventDetail.eventId = concert.id || concert.oid;
            
            // Create temporary container to capture EventDetail.render() output
            const tempDiv = document.createElement('div');
            tempDiv.id = 'event-detail-content';
            document.body.appendChild(tempDiv);
            
            // Render using EventDetail
            EventDetail.render();
            
            // Get rendered HTML
            const renderedHTML = tempDiv.innerHTML;
            
            // Remove temporary container
            document.body.removeChild(tempDiv);
            
            // Browser handles back navigation
            return `
                <div id="event-detail-content" class="mx-auto max-w-4xl">
                    ${renderedHTML}
                </div>
            `;
        }
        
        // Fallback simple view
        return `
            <div class="mx-auto max-w-4xl">
                <h1 class="text-2xl font-bold mb-4">Concert ${concert.id || concert.oid || ''}</h1>
                <div class="bg-card rounded-lg border border-border p-6">
                    <p class="text-muted-foreground">Concert detail view - to be implemented</p>
                </div>
            </div>
        `;
    }
    
    /**
     * Render concert edit form
     */
    renderConcertEdit(concert) {
        return `
            <div class="mx-auto max-w-4xl">
                <h1 class="text-2xl font-bold mb-4">Edit Concert</h1>
                <div class="bg-card rounded-lg border border-border p-6">
                    <p class="text-muted-foreground">Concert edit form - to be implemented</p>
                </div>
            </div>
        `;
    }
    
    /**
     * Render concert create form
     */
    renderConcertCreate() {
        return `
            <div class="mx-auto max-w-4xl">
                <h1 class="text-2xl font-bold mb-4">Create Concert</h1>
                <div class="bg-card rounded-lg border border-border p-6">
                    <p class="text-muted-foreground">Concert create form - to be implemented</p>
                </div>
            </div>
        `;
    }
}

// Export for use in other scripts
window.ConcertHandler = ConcertHandler;
