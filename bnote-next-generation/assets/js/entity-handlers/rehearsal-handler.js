/**
 * BNote Next Generation - Rehearsal Handler
 *
 * Copyright (C) 2026 BNote Contributors
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * Rehearsal Handler
 * Renders rehearsal entity detail views
 */
class RehearsalHandler {
    /**
     * Render rehearsal detail view
     * @param {string} entityType - Entity type ('rehearsal')
     * @param {number} id - Rehearsal ID
     * @param {string} mode - View mode ('view', 'edit', 'create')
     * @param {HTMLElement} container - Container to render into
     */
    async renderDetail(entityType, id, mode, container) {
        try {
            // Load rehearsal data using RehearsalsApi (action is null, id in params)
            const rehearsal = typeof RehearsalsApi !== 'undefined' && RehearsalsApi.get
                ? await RehearsalsApi.get(id)
                : await api.get('rehearsals', null, { id });
            
            // Render detail view
            if (mode === 'view') {
                // Use EventDetail if available
                if (typeof EventDetail !== 'undefined') {
                    // Set up EventDetail state
                    EventDetail.currentEvent = rehearsal;
                    EventDetail.eventType = 'R';
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
                    container.innerHTML = this.renderRehearsalView(rehearsal);
                }
            } else if (mode === 'edit') {
                container.innerHTML = this.renderRehearsalEdit(rehearsal);
            } else if (mode === 'create') {
                container.innerHTML = this.renderRehearsalCreate();
            }
            
            // Initialize lucide icons
            if (typeof lucide !== 'undefined') {
                setTimeout(() => lucide.createIcons(), 100);
            }
        } catch (error) {
            console.error('RehearsalHandler: Failed to render detail:', error);
            container.innerHTML = `<div class="text-error">Failed to load rehearsal: ${error.message}</div>`;
        }
    }
    
    /**
     * Render rehearsal view (read-only)
     */
    renderRehearsalView(rehearsal) {
        // Use existing EventDetail rendering if available
        if (typeof EventDetail !== 'undefined') {
            // Store event data in EventDetail
            EventDetail.currentEvent = rehearsal;
            EventDetail.eventType = 'R';
            EventDetail.eventId = rehearsal.id || rehearsal.oid;
            
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
                <h1 class="text-2xl font-bold mb-4">Rehearsal ${rehearsal.id || rehearsal.oid || ''}</h1>
                <div class="bg-card rounded-lg border border-border p-6">
                    <p class="text-muted-foreground">Rehearsal detail view - to be implemented</p>
                </div>
            </div>
        `;
    }
    
    /**
     * Render rehearsal edit form
     */
    renderRehearsalEdit(rehearsal) {
        return `
            <div class="mx-auto max-w-4xl">
                <h1 class="text-2xl font-bold mb-4">Edit Rehearsal</h1>
                <div class="bg-card rounded-lg border border-border p-6">
                    <p class="text-muted-foreground">Edit mode not yet implemented</p>
                </div>
            </div>
        `;
    }
    
    /**
     * Render rehearsal create form
     */
    renderRehearsalCreate() {
        return `
            <div class="mx-auto max-w-4xl">
                <h1 class="text-2xl font-bold mb-4">Create Rehearsal</h1>
                <div class="bg-card rounded-lg border border-border p-6">
                    <p class="text-muted-foreground">Create mode not yet implemented</p>
                </div>
            </div>
        `;
    }
}

// Export for use in other scripts
window.RehearsalHandler = RehearsalHandler;
