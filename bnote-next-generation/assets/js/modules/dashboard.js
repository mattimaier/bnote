/**
 * BNote Next Generation - Dashboard Module
 *
 * Copyright (C) 2026 BNote Contributors
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * Dashboard Module
 * Extends ModuleBase - pure UI container
 */
class DashboardModule extends ModuleBase {
    constructor() {
        super();
        this.route = 'dashboard';
        this.dashboard = null;
    }
    
    /**
     * Get module HTML template (embedded in JS to prevent flashing)
     */
    getTemplate() {
        return `
            <div id="dashboard-container">
                <!-- Welcome message -->
                <div class="mb-6 pb-4 border-b border-border/30">
                    <div class="flex flex-col gap-2">
                        <h1 class="text-3xl font-bold tracking-tight text-foreground" id="welcome-greeting"></h1>
                        <p class="text-muted-foreground text-sm font-medium" id="dashboard-subtitle" data-i18n="js.dashboard.subtitle"></p>
                    </div>
                </div>
                <div class="mx-auto max-w-4xl">
                    <div class="space-y-4">
                        <!-- Events Needing Response -->
                        <div id="events-needing-response-card" class="md:bg-card md:text-card-foreground flex flex-col gap-4 md:rounded-xl md:border md:border-border/40 py-2 md:py-4 md:shadow-sm md:hover:shadow-md transition-shadow">
                            <div class="px-1 md:px-4 lg:px-5 pb-2 md:border-b md:border-border/30">
                                <h2 class="text-sm md:text-base font-semibold text-foreground" data-i18n="js.dashboard.responseNeeded">Your Response Needed</h2>
                            </div>
                            <div class="px-1 md:px-4 lg:px-5 pb-2 md:pb-3">
                                <div class="flex items-center gap-3 flex-wrap">
                                    <span class="text-xs font-medium text-muted-foreground" data-i18n="js.common.filter">Filter:</span>
                                    <button class="filter-bubble filter-bubble-rehearsal" data-filter-type="rehearsal" data-section="events-needing-response" data-i18n-label="js.event.rehearsal">
                                        rehearsal <span class="filter-count text-xs opacity-70 ml-1">(0)</span>
                                    </button>
                                    <button class="filter-bubble filter-bubble-performance" data-filter-type="performance" data-section="events-needing-response" data-i18n-label="js.event.performance">
                                        performance <span class="filter-count text-xs opacity-70 ml-1">(0)</span>
                                    </button>
                                    <button class="filter-clear-btn text-xs text-muted-foreground hover:text-foreground px-2 py-1 rounded-md hover:bg-muted/50 transition-colors" data-section="events-needing-response" data-i18n="js.common.clear">Clear</button>
                                </div>
                            </div>
                            <div class="px-1 md:px-4 lg:px-5">
                                <div id="events-needing-response-skeleton" class="relative space-y-2 md:space-y-3">
                                    <div class="relative md:hidden px-1 pb-2 mb-2 border-b border-border/30">
                                        <div class="h-3 bg-muted animate-pulse rounded w-1/4 mb-1.5"></div>
                                        <div class="h-3 bg-muted animate-pulse rounded w-3/4 mb-1.5"></div>
                                        <div class="h-2.5 bg-muted animate-pulse rounded w-1/2"></div>
                                    </div>
                                    <div class="hidden md:flex relative gap-3">
                                        <div class="relative z-10 mt-0.5 h-7 w-7 shrink-0 rounded-full bg-muted animate-pulse ring-3 ring-background shadow-sm"></div>
                                        <div class="flex-1 rounded-lg border border-border/40 bg-gradient-to-br from-muted/20 to-transparent p-3">
                                            <div class="h-4 bg-muted animate-pulse rounded w-1/4 mb-1.5"></div>
                                            <div class="h-4 bg-muted animate-pulse rounded w-3/4 mb-1.5"></div>
                                            <div class="h-3 bg-muted animate-pulse rounded w-1/2"></div>
                                        </div>
                                    </div>
                                </div>
                                <div id="events-needing-response-content" class="relative space-y-2 md:space-y-3 hidden"></div>
                            </div>
                        </div>

                        <!-- Events Timeline -->
                        <div id="events-timeline-card" class="md:bg-card md:text-card-foreground flex flex-col gap-4 md:rounded-xl md:border md:border-border/40 py-2 md:py-4 md:shadow-sm md:hover:shadow-md transition-shadow">
                            <div class="px-1 md:px-4 lg:px-5 pb-2 md:border-b md:border-border/30">
                                <h2 class="text-sm md:text-base font-semibold text-foreground" data-i18n="js.dashboard.upcomingEvents">Upcoming Events</h2>
                            </div>
                            <div class="px-1 md:px-4 lg:px-5 pb-2 md:pb-3">
                                <div class="flex items-center gap-3 flex-wrap">
                                    <span class="text-xs font-medium text-muted-foreground" data-i18n="js.common.filter">Filter:</span>
                                    <button class="filter-bubble filter-bubble-rehearsal" data-filter-type="rehearsal" data-section="events-timeline" data-i18n-label="js.event.rehearsal">
                                        rehearsal <span class="filter-count text-xs opacity-70 ml-1">(0)</span>
                                    </button>
                                    <button class="filter-bubble filter-bubble-performance" data-filter-type="performance" data-section="events-timeline" data-i18n-label="js.event.performance">
                                        performance <span class="filter-count text-xs opacity-70 ml-1">(0)</span>
                                    </button>
                                    <button class="filter-clear-btn text-xs text-muted-foreground hover:text-foreground px-2 py-1 rounded-md hover:bg-muted/50 transition-colors" data-section="events-timeline" data-i18n="js.common.clear">Clear</button>
                                </div>
                            </div>
                            <div class="px-1 md:px-4 lg:px-5">
                                <div id="events-timeline-skeleton" class="relative space-y-2 md:space-y-3">
                                    <div class="relative md:hidden px-1 pb-2 mb-2 border-b border-border/30">
                                        <div class="h-3 bg-muted animate-pulse rounded w-1/4 mb-1.5"></div>
                                        <div class="h-3 bg-muted animate-pulse rounded w-3/4 mb-1.5"></div>
                                        <div class="h-2.5 bg-muted animate-pulse rounded w-1/2"></div>
                                    </div>
                                    <div class="hidden md:flex relative gap-3">
                                        <div class="relative z-10 mt-0.5 h-7 w-7 shrink-0 rounded-full bg-muted animate-pulse ring-3 ring-background shadow-sm"></div>
                                        <div class="flex-1 rounded-lg border border-border/40 bg-gradient-to-br from-muted/20 to-transparent p-3">
                                            <div class="h-4 bg-muted animate-pulse rounded w-1/4 mb-1.5"></div>
                                            <div class="h-4 bg-muted animate-pulse rounded w-3/4 mb-1.5"></div>
                                            <div class="h-3 bg-muted animate-pulse rounded w-1/2"></div>
                                        </div>
                                    </div>
                                </div>
                                <div id="events-timeline-content" class="relative space-y-2 md:space-y-3 hidden"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }
    
    /**
     * Initialize module (called by ModuleBase.init)
     */
    async onInit(session, container) {
        // Initialize dashboard using existing Dashboard object
        if (typeof Dashboard !== 'undefined') {
            this.dashboard = Dashboard;
            
            // Override openEventDetail to use NavigationService
            this.overrideEventNavigation();
            
            // Initialize dashboard
            await this.dashboard.init(session);
        } else {
            console.error('DashboardModule: Dashboard object not found');
        }
    }
    
    /**
     * Override Dashboard.openEventDetail to use NavigationService
     */
    overrideEventNavigation() {
        const self = this;
        const originalOpenEventDetail = this.dashboard.openEventDetail;
        
        // Override openEventDetail to navigate to entity detail page
        this.dashboard.openEventDetail = function(eventType, eventId, fromSearch = false, searchQuery = null, searchFilters = null) {
            // Convert event type to entity type
            // eventType is 'R' for rehearsal or 'C' for concert (from data-event-type attribute)
            const entityType = eventType === 'C' ? 'concert' : 'rehearsal';
            
            // Navigate to entity detail page (full page load)
            const url = self.getEntityLink(entityType, eventId);
            window.location.href = url;
        };
    }
    
    /**
     * Cleanup module
     */
    onCleanup() {
        // Cleanup if needed
    }
}

// Export for use in other scripts
window.DashboardModule = DashboardModule;
