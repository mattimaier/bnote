/**
 * Dashboard functionality for BNote Modern UI
 * Converts v0.dev React components to vanilla JavaScript
 */

const Dashboard = {
    // State
    sidebarOpen: true,
    sidebarCollapsed: false,
    session: null,
    dashboardData: null,
    filters: {
        'events-needing-response': new Set(),
        'events-timeline': new Set()
    },
    allEvents: {
        'events-needing-response': [],
        'events-timeline': []
    },
    displayedCounts: {
        'events-needing-response': 0,
        'events-timeline': 0
    },
    maxDisplayCounts: {
        'events-needing-response': null,
        'events-timeline': null
    },
    eventCounts: {
        'events-needing-response': {
            'rehearsal': 0,
            'performance': 0,
            'meeting': 0
        },
        'events-timeline': {
            'rehearsal': 0,
            'performance': 0,
            'meeting': 0
        }
    },

    /**
     * Initialize dashboard
     */
    async init(session) {
        this.session = session;
        
        // Initialize UI
        this.initSidebar();
        this.initWelcomeHeader();
        this.initQuickActions();
        this.initFilters();
        
        // Load dashboard data
        await this.loadDashboard();
    },

    /**
     * Initialize sidebar functionality
     */
    initSidebar() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebar-overlay');
        const collapseBtn = document.getElementById('sidebar-collapse-btn');

        // Collapse/expand functionality
        if (collapseBtn) {
            collapseBtn.addEventListener('click', () => {
                this.toggleSidebarCollapse();
            });
        }

        // Close sidebar on mobile when clicking overlay
        if (overlay) {
            overlay.addEventListener('click', () => {
                this.toggleSidebar();
            });
        }

        // Close sidebar on mobile when clicking outside
        document.addEventListener('click', (e) => {
            if (window.innerWidth < 1024 && this.sidebarOpen) {
                if (!sidebar.contains(e.target) && !document.getElementById('mobile-menu-btn')?.contains(e.target)) {
                    this.toggleSidebar();
                }
            }
        });
    },

    /**
     * Toggle sidebar open/closed (mobile)
     */
    toggleSidebar() {
        this.sidebarOpen = !this.sidebarOpen;
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebar-overlay');

        if (window.innerWidth < 1024) {
            if (this.sidebarOpen) {
                sidebar.classList.remove('-translate-x-full');
                overlay.classList.remove('hidden');
            } else {
                sidebar.classList.add('-translate-x-full');
                overlay.classList.add('hidden');
            }
        }
    },

    /**
     * Toggle sidebar collapse (desktop)
     */
    toggleSidebarCollapse() {
        this.sidebarCollapsed = !this.sidebarCollapsed;
        const sidebar = document.getElementById('sidebar');
        const collapseBtn = document.getElementById('sidebar-collapse-btn');
        const icon = collapseBtn?.querySelector('i[data-lucide]');
        const brand = document.getElementById('sidebar-brand');

        if (this.sidebarCollapsed) {
            sidebar.classList.remove('w-64');
            sidebar.classList.add('w-20');
            collapseBtn?.setAttribute('title', 'Expand sidebar');
            if (icon) {
                icon.setAttribute('data-lucide', 'chevron-right');
            }
            // Hide text in nav items and brand text
            sidebar.querySelectorAll('.sidebar-text, .sidebar-badge').forEach(el => {
                el.classList.add('hidden');
            });
            if (brand) {
                brand.querySelector('div:last-child')?.classList.add('hidden');
            }
        } else {
            sidebar.classList.remove('w-20');
            sidebar.classList.add('w-64');
            collapseBtn?.setAttribute('title', 'Collapse sidebar');
            if (icon) {
                icon.setAttribute('data-lucide', 'chevron-left');
            }
            // Show text in nav items and brand text
            sidebar.querySelectorAll('.sidebar-text, .sidebar-badge').forEach(el => {
                el.classList.remove('hidden');
            });
            if (brand) {
                brand.querySelector('div:last-child')?.classList.remove('hidden');
            }
        }

        // Reinitialize Lucide icons
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    },

    /**
     * Initialize welcome header
     */
    initWelcomeHeader() {
        if (!this.session?.user) return;

        const user = this.session.user;
        const firstName = user.name || 'User';
        const lastName = user.surname || '';
        const fullName = `${firstName} ${lastName}`.trim();

        // Set user name
        const userNameEl = document.getElementById('user-name');
        if (userNameEl) {
            userNameEl.textContent = firstName;
        }

        // Set user initials
        const initialsEl = document.getElementById('user-initials');
        if (initialsEl) {
            const initials = (firstName[0] || '') + (lastName[0] || '');
            initialsEl.textContent = initials || 'U';
        }

        // Set greeting
        const greetingEl = document.getElementById('welcome-greeting');
        if (greetingEl) {
            const greeting = this.getGreeting();
            greetingEl.textContent = `${greeting}, ${firstName}`;
        }
    },

    /**
     * Get time-based greeting
     */
    getGreeting() {
        const hour = new Date().getHours();
        if (hour < 12) return 'Good morning';
        if (hour < 18) return 'Good afternoon';
        return 'Good evening';
    },

    /**
     * Initialize quick actions
     */
    initQuickActions() {
        const actions = [
            {
                title: 'View Calendar',
                description: 'See all upcoming events',
                icon: 'calendar-days',
                colorClass: 'bg-primary/10 text-primary hover:bg-primary/20',
                href: '#'
            },
            {
                title: 'Contact Band',
                description: 'Message the band members',
                icon: 'message-square',
                colorClass: 'bg-accent/10 text-accent hover:bg-accent/20',
                href: '#'
            },
            {
                title: 'Band Directory',
                description: 'View all band members',
                icon: 'users',
                colorClass: 'bg-chart-3/10 text-chart-3 hover:bg-chart-3/20',
                href: '#'
            },
            {
                title: 'My Profile',
                description: 'Edit your band profile',
                icon: 'music',
                colorClass: 'bg-chart-4/10 text-chart-4 hover:bg-chart-4/20',
                href: '#'
            }
        ];

        const container = document.getElementById('quick-actions-content');
        if (!container) return;

        container.innerHTML = actions.map(action => `
            <a
                href="${action.href}"
                class="group relative h-auto flex flex-col items-center gap-3 p-4 rounded-lg border border-border/40 transition-all duration-200 hover:border-primary/30 hover:shadow-md hover:bg-primary/5 ${action.colorClass}"
            >
                <div class="p-2 rounded-lg bg-current/10 group-hover:bg-current/15 transition-colors">
                    <i data-lucide="${action.icon}" class="h-5 w-5"></i>
                </div>
                <div class="text-center">
                    <p class="font-semibold text-sm leading-tight">${action.title}</p>
                    <p class="text-xs text-muted-foreground/70 font-normal mt-1">${action.description}</p>
                </div>
            </a>
        `).join('');

        // Reinitialize Lucide icons
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    },

    /**
     * Load dashboard data from API
     */
    async loadDashboard() {
        try {
            // Show loading states
            this.showLoadingStates();

            // Fetch dashboard data and events needing response separately
            const [dashboardData, eventsNeedingResponse] = await Promise.all([
                DashboardApi.getDashboard(),
                DashboardApi.getEventsNeedingResponse()
            ]);
            
            this.dashboardData = dashboardData;

            // Store all events and config
            if (eventsNeedingResponse.events) {
                this.allEvents['events-needing-response'] = eventsNeedingResponse.events;
                this.maxDisplayCounts['events-needing-response'] = eventsNeedingResponse.config?.max_show || 5;
                this.eventCounts['events-needing-response'] = eventsNeedingResponse.counts || { rehearsal: 0, performance: 0, meeting: 0 };
                this.displayedCounts['events-needing-response'] = Math.min(
                    this.maxDisplayCounts['events-needing-response'],
                    eventsNeedingResponse.events.length
                );
            }

            if (dashboardData.inbox) {
                this.allEvents['events-timeline'] = dashboardData.inbox;
                this.maxDisplayCounts['events-timeline'] = dashboardData.config?.max_show || 5;
                this.eventCounts['events-timeline'] = dashboardData.counts || { rehearsal: 0, performance: 0, meeting: 0 };
                // Initialize displayed count to max_show, not the full length
                this.displayedCounts['events-timeline'] = this.maxDisplayCounts['events-timeline'];
            }

            // Update filter counts
            this.updateFilterCounts('events-needing-response', this.eventCounts['events-needing-response']);
            this.updateFilterCounts('events-timeline', this.eventCounts['events-timeline']);

            // Render components with events needing response
            this.renderEventsNeedingResponse(eventsNeedingResponse.events || eventsNeedingResponse);
            this.renderEventsTimeline(dashboardData);

            // Hide loading states
            this.hideLoadingStates();

        } catch (error) {
            console.error('Failed to load dashboard:', error);
            this.showError(error.message || 'Fehler beim Laden des Dashboards');
            this.hideLoadingStates();
        }
    },

    /**
     * Initialize filter event listeners
     */
    initFilters() {
        // Attach click handlers to filter bubbles
        document.querySelectorAll('.filter-bubble').forEach(button => {
            button.addEventListener('click', (e) => {
                const filterType = button.getAttribute('data-filter-type');
                const sectionId = button.getAttribute('data-section');
                this.toggleFilter(sectionId, filterType);
            });
        });

        // Attach click handlers to clear buttons
        document.querySelectorAll('.filter-clear-btn').forEach(button => {
            button.addEventListener('click', (e) => {
                const sectionId = button.getAttribute('data-section');
                this.clearFilters(sectionId);
            });
        });
    },

    /**
     * Toggle a filter type for a section
     */
    toggleFilter(sectionId, filterType) {
        const filterSet = this.filters[sectionId];
        if (!filterSet) return;

        // Toggle filter in Set
        if (filterSet.has(filterType)) {
            filterSet.delete(filterType);
        } else {
            filterSet.add(filterType);
        }

        // Update button visual state
        const button = document.querySelector(`[data-filter-type="${filterType}"][data-section="${sectionId}"]`);
        if (button) {
            if (filterSet.has(filterType)) {
                button.classList.add('selected');
            } else {
                button.classList.remove('selected');
            }
        }

        // Apply filtering to the section
        this.applyFiltersToSection(sectionId);
        
        // Update filter counts based on filtered events
        this.updateFilterCountsForSection(sectionId);
    },

    /**
     * Clear all filters for a section
     */
    clearFilters(sectionId) {
        const filterSet = this.filters[sectionId];
        if (!filterSet) return;

        // Clear the Set
        filterSet.clear();

        // Remove selected class from all filter bubbles in this section
        document.querySelectorAll(`[data-section="${sectionId}"].filter-bubble`).forEach(button => {
            button.classList.remove('selected');
        });

        // Apply filtering (show all events)
        this.applyFiltersToSection(sectionId);
        
        // Update filter counts to show all counts
        this.updateFilterCounts(sectionId, this.eventCounts[sectionId]);
    },

    /**
     * Apply filters to a section by re-rendering
     */
    applyFiltersToSection(sectionId) {
        const allEvents = this.allEvents[sectionId] || [];
        const filteredEvents = this.applyFilters(sectionId, allEvents);
        const maxDisplay = this.maxDisplayCounts[sectionId] || 5;
        
        // Reset displayed count to max if current is less than max, otherwise keep current
        const currentDisplayed = this.displayedCounts[sectionId] || maxDisplay;
        if (currentDisplayed <= maxDisplay) {
            // Reset to max when applying filters
            this.displayedCounts[sectionId] = Math.min(maxDisplay, filteredEvents.length);
        } else {
            // Keep current if user has loaded more, but cap at filtered length
            this.displayedCounts[sectionId] = Math.min(currentDisplayed, filteredEvents.length);
        }
        
        if (sectionId === 'events-needing-response') {
            // Re-render events needing response
            this.renderEventsNeedingResponse(allEvents);
        } else if (sectionId === 'events-timeline') {
            // Re-render events timeline
            this.renderEventsTimeline({ inbox: allEvents });
        }
    },

    /**
     * Load more events for a section
     */
    loadMoreEvents(sectionId) {
        const maxDisplay = this.maxDisplayCounts[sectionId] || 5;
        const currentDisplayed = this.displayedCounts[sectionId] || maxDisplay;
        const allEvents = this.allEvents[sectionId] || [];
        const filteredEvents = this.applyFilters(sectionId, allEvents);
        
        // Increase displayed count
        this.displayedCounts[sectionId] = Math.min(
            currentDisplayed + maxDisplay,
            filteredEvents.length
        );
        
        // Re-render section
        this.applyFiltersToSection(sectionId);
    },

    /**
     * Update filter counts display
     */
    updateFilterCounts(sectionId, counts) {
        ['rehearsal', 'performance', 'meeting'].forEach(type => {
            const button = document.querySelector(`[data-filter-type="${type}"][data-section="${sectionId}"]`);
            if (button) {
                let countSpan = button.querySelector('.filter-count');
                if (!countSpan) {
                    countSpan = document.createElement('span');
                    countSpan.className = 'filter-count text-xs opacity-70 ml-1';
                    button.appendChild(countSpan);
                }
                countSpan.textContent = `(${counts[type] || 0})`;
            }
        });
    },

    /**
     * Update filter counts based on filtered events
     */
    updateFilterCountsForSection(sectionId) {
        const allEvents = this.allEvents[sectionId] || [];
        const filterSet = this.filters[sectionId];
        
        if (!filterSet || filterSet.size === 0) {
            // No filters, show all counts
            this.updateFilterCounts(sectionId, this.eventCounts[sectionId]);
        } else {
            // Calculate filtered counts
            const filteredEvents = this.applyFilters(sectionId, allEvents);
            const filteredCounts = this.countEventsByType(filteredEvents);
            this.updateFilterCounts(sectionId, filteredCounts);
        }
    },

    /**
     * Count events by type
     */
    countEventsByType(events) {
        const counts = {
            'rehearsal': 0,
            'performance': 0,
            'meeting': 0
        };
        
        events.forEach(event => {
            const eventType = this.mapOtypeToEventType(event.otype);
            if (eventType === 'rehearsal') {
                counts.rehearsal++;
            } else if (eventType === 'performance') {
                counts.performance++;
            } else {
                counts.meeting++;
            }
        });
        
        return counts;
    },

    /**
     * Filter events based on selected types (OR logic)
     */
    applyFilters(sectionId, events) {
        const filterSet = this.filters[sectionId];
        if (!filterSet || filterSet.size === 0) {
            // No filters selected, return all events
            return events;
        }

        // Filter events where eventType matches any selected type (OR logic)
        return events.filter(event => {
            const eventType = this.mapOtypeToEventType(event.otype);
            return filterSet.has(eventType);
        });
    },

    /**
     * Refresh events sections after participation update
     * This ensures events appearing in both sections are updated
     * Uses animations to show what's happening
     */
    async refreshEventsSections() {
        try {
            // Small delay to let user see the widget update first
            await new Promise(resolve => setTimeout(resolve, 300));

            // Fetch fresh data for both sections
            const [dashboardData, eventsNeedingResponse] = await Promise.all([
                DashboardApi.getDashboard(),
                DashboardApi.getEventsNeedingResponse()
            ]);
            
            // Update stored dashboard data
            this.dashboardData = dashboardData;

            // Extract events from response (handle both old array format and new object format)
            const eventsNeedingResponseArray = Array.isArray(eventsNeedingResponse) 
                ? eventsNeedingResponse 
                : (eventsNeedingResponse.events || []);
            
            // Update stored events and counts
            this.allEvents['events-needing-response'] = eventsNeedingResponseArray;
            if (eventsNeedingResponse.counts) {
                this.eventCounts['events-needing-response'] = eventsNeedingResponse.counts;
            }
            if (eventsNeedingResponse.config) {
                this.maxDisplayCounts['events-needing-response'] = eventsNeedingResponse.config.max_show || 5;
            }

            // Update timeline events
            if (dashboardData.inbox) {
                this.allEvents['events-timeline'] = dashboardData.inbox;
                if (dashboardData.counts) {
                    this.eventCounts['events-timeline'] = dashboardData.counts;
                }
                if (dashboardData.config) {
                    this.maxDisplayCounts['events-timeline'] = dashboardData.config.max_show || 5;
                }
            }

            // Update filter counts
            this.updateFilterCounts('events-needing-response', this.eventCounts['events-needing-response']);
            this.updateFilterCounts('events-timeline', this.eventCounts['events-timeline']);

            // Re-render both sections with animations
            await this.renderEventsNeedingResponseWithAnimation(eventsNeedingResponseArray);
            await this.renderEventsTimelineWithAnimation(dashboardData);

        } catch (error) {
            console.error('Failed to refresh events sections:', error);
            // Don't show error toast for background refresh
        }
    },

    /**
     * Render events needing response with animation
     */
    async renderEventsNeedingResponseWithAnimation(eventsNeedingResponse) {
        const container = document.getElementById('events-needing-response-content');
        if (!container) return;

        // Handle both array and object format
        const events = Array.isArray(eventsNeedingResponse) 
            ? eventsNeedingResponse 
            : (eventsNeedingResponse.events || []);
        const currentEventIds = new Set(
            Array.from(container.querySelectorAll('[data-event-id]'))
                .map(el => el.getAttribute('data-event-id'))
        );
        const newEventIds = new Set(events.map(e => String(e.oid)));

        // Find events to remove (fade out)
        const toRemove = Array.from(container.children).filter(child => {
            const eventId = child.querySelector('[data-event-id]')?.getAttribute('data-event-id');
            return eventId && !newEventIds.has(eventId);
        });

        // Animate removal
        toRemove.forEach((item, index) => {
            setTimeout(() => {
                item.style.transition = 'opacity 300ms ease-out, transform 300ms ease-out, margin 300ms ease-out';
                item.style.opacity = '0';
                item.style.transform = 'translateX(-20px) scale(0.95)';
                item.style.marginTop = '0';
                item.style.marginBottom = '0';
                item.style.paddingTop = '0';
                item.style.paddingBottom = '0';
                item.style.maxHeight = item.offsetHeight + 'px';
                
                setTimeout(() => {
                    item.remove();
                }, 300);
            }, index * 50);
        });

        // Wait for removals to complete
        await new Promise(resolve => setTimeout(resolve, toRemove.length * 50 + 350));

        // Apply filters before comparing
        const filteredEvents = this.applyFilters('events-needing-response', events);
        const filteredEventIds = new Set(filteredEvents.map(e => String(e.oid)));

        // Find events to add (fade in) - use filtered events
        const toAdd = filteredEvents.filter(e => !currentEventIds.has(String(e.oid)));

        // Render all events (including existing ones) - already filtered
        this.renderEventsWidget(
            events,
            'events-needing-response-content',
            'events-needing-response-skeleton',
            'No events need your response at this time.',
            true,
            'events-needing-response' // Section ID for filtering
        );

        // Animate new items
        if (toAdd.length > 0) {
            const allItems = Array.from(container.children);
            toAdd.forEach((event, index) => {
                const item = allItems.find(el => {
                    const eventId = el.querySelector('[data-event-id]')?.getAttribute('data-event-id');
                    return eventId === String(event.oid);
                });
                if (item) {
                    item.style.opacity = '0';
                    item.style.transform = 'translateX(20px) scale(0.95)';
                    setTimeout(() => {
                        item.style.transition = 'opacity 300ms ease-out, transform 300ms ease-out';
                        item.style.opacity = '1';
                        item.style.transform = 'translateX(0) scale(1)';
                    }, index * 50);
                }
            });
        }
        
        // Re-initialize participation widgets after animation completes
        setTimeout(() => {
            this.initializeParticipationWidgets(container);
        }, toAdd.length * 50 + 500);
    },

    /**
     * Render events timeline with animation
     */
    async renderEventsTimelineWithAnimation(dashboardData) {
        const container = document.getElementById('events-timeline-content');
        if (!container) return;

        const inboxItems = dashboardData.inbox || [];
        const upcomingEvents = inboxItems
            .filter(item => item.eventBegin || item.dueDate)
            .sort((a, b) => {
                const dateA = new Date(a.eventBegin || a.dueDate);
                const dateB = new Date(b.eventBegin || b.dueDate);
                return dateA - dateB;
            })
            .slice(0, 5);

        const events = upcomingEvents;
        const currentEventIds = new Set(
            Array.from(container.querySelectorAll('[data-event-id]'))
                .map(el => el.getAttribute('data-event-id'))
        );
        const newEventIds = new Set(events.map(e => String(e.oid)));

        // Find events to remove (fade out)
        const toRemove = Array.from(container.children).filter(child => {
            const eventId = child.querySelector('[data-event-id]')?.getAttribute('data-event-id');
            return eventId && !newEventIds.has(eventId);
        });

        // Animate removal
        toRemove.forEach((item, index) => {
            setTimeout(() => {
                item.style.transition = 'opacity 300ms ease-out, transform 300ms ease-out, margin 300ms ease-out';
                item.style.opacity = '0';
                item.style.transform = 'translateX(-20px) scale(0.95)';
                item.style.marginTop = '0';
                item.style.marginBottom = '0';
                item.style.paddingTop = '0';
                item.style.paddingBottom = '0';
                item.style.maxHeight = item.offsetHeight + 'px';
                
                setTimeout(() => {
                    item.remove();
                }, 300);
            }, index * 50);
        });

        // Wait for removals to complete
        await new Promise(resolve => setTimeout(resolve, toRemove.length * 50 + 350));

        // Apply filters before comparing
        const filteredEvents = this.applyFilters('events-timeline', upcomingEvents);
        const filteredEventIds = new Set(filteredEvents.map(e => String(e.oid)));

        // Find events to add (fade in) - use filtered events
        const toAdd = filteredEvents.filter(e => !currentEventIds.has(String(e.oid)));

        // Render all events (including existing ones) - already filtered
        this.renderEventsWidget(
            upcomingEvents,
            'events-timeline-content',
            'events-timeline-skeleton',
            'No upcoming events scheduled.',
            true,
            'events-timeline' // Section ID for filtering
        );

        // Animate new items
        if (toAdd.length > 0) {
            const allItems = Array.from(container.children);
            toAdd.forEach((event, index) => {
                const item = allItems.find(el => {
                    const eventId = el.querySelector('[data-event-id]')?.getAttribute('data-event-id');
                    return eventId === String(event.oid);
                });
                if (item) {
                    item.style.opacity = '0';
                    item.style.transform = 'translateX(20px) scale(0.95)';
                    setTimeout(() => {
                        item.style.transition = 'opacity 300ms ease-out, transform 300ms ease-out';
                        item.style.opacity = '1';
                        item.style.transform = 'translateX(0) scale(1)';
                    }, index * 50);
                }
            });
        }
        
        // Re-initialize participation widgets after animation
        setTimeout(() => {
            this.initializeParticipationWidgets(container);
        }, 500);
    },

    /**
     * Show loading states
     */
    showLoadingStates() {
        const skeletons = document.querySelectorAll('[id$="-skeleton"]');
        skeletons.forEach(skeleton => {
            if (skeleton) {
                skeleton.style.display = 'block';
            }
        });
    },

    /**
     * Hide loading states
     */
    hideLoadingStates() {
        const skeletons = document.querySelectorAll('[id$="-skeleton"]');
        skeletons.forEach(skeleton => {
            if (skeleton) {
                skeleton.style.display = 'none';
            }
        });
    },

    /**
     * Reusable widget to render events list
     * @param {Array} events - Array of event objects
     * @param {string} contentId - ID of the content container element
     * @param {string} skeletonId - ID of the skeleton loader element
     * @param {string} emptyMessage - Message to show when no events
     * @param {boolean} showParticipation - Whether to show participation widget
     * @param {string} sectionId - Section ID for filtering ('events-needing-response' or 'events-timeline')
     */
    renderEventsWidget(events, contentId, skeletonId, emptyMessage, showParticipation = false, sectionId = null) {
        const container = document.getElementById(contentId);
        const skeleton = document.getElementById(skeletonId);
        if (!container) return;

        // Apply filters if sectionId is provided
        let eventsList = Array.isArray(events) ? events : [];
        if (sectionId) {
            eventsList = this.applyFilters(sectionId, eventsList);
        }

        // Hide skeleton, show content
        if (skeleton) skeleton.style.display = 'none';
        container.classList.remove('hidden');

        if (eventsList.length === 0) {
            container.innerHTML = `
                <div class="text-center py-8 text-muted-foreground text-sm">
                    ${this.escapeHtml(emptyMessage)}
                </div>
            `;
            return;
        }

        // Limit events to displayed count
        let currentDisplayed = 0;
        if (sectionId && this.displayedCounts[sectionId] !== undefined) {
            currentDisplayed = this.displayedCounts[sectionId];
        } else if (sectionId && this.maxDisplayCounts[sectionId]) {
            currentDisplayed = this.maxDisplayCounts[sectionId];
        } else {
            currentDisplayed = 5; // Default
        }
        
        const displayedEvents = eventsList.slice(0, currentDisplayed);
        const hasMore = eventsList.length > currentDisplayed;

        container.innerHTML = displayedEvents.map((event, index) => {
            const eventType = this.mapOtypeToEventType(event.otype);
            const typeConfig = this.getEventTypeConfig(eventType);
            const dateStr = this.formatEventDate(event.eventBegin || event.dueDate);
            const timeStr = this.formatEventTime(event.eventBegin || event.dueDate);
            // Use location from API response
            const location = event.location || event.locationData?.name || this.extractLocationFromTitle(event.title) || 'TBA';
            const isLast = index === eventsList.length - 1;

            // Generate participation widget HTML if needed
            // Only show widget if we have valid event ID and type (R or C for rehearsals/concerts)
            const hasValidEventData = event.oid && event.otype && (event.otype === 'R' || event.otype === 'C');
            const participationWidget = showParticipation && hasValidEventData ? `
                <div 
                    class="flex flex-col gap-2 shrink-0 items-end w-fit" 
                    data-participation-widget 
                    data-event-id="${event.oid}" 
                    data-event-type="${event.otype}"
                ></div>
            ` : '';

            return `
                <div class="relative flex gap-4" data-event-id="${event.oid}">
                    ${!isLast ? `<div class="absolute left-[15px] top-10 h-[calc(100%-16px)] w-0.5 timeline-connector"></div>` : ''}
                    <div class="relative z-10 mt-1 h-8 w-8 shrink-0 rounded-full ${typeConfig.dotClass} ring-4 ring-background shadow-sm flex items-center justify-center">
                        <i data-lucide="${typeConfig.icon}" class="h-3.5 w-3.5 text-white"></i>
                    </div>
                    <div class="flex-1 rounded-lg border border-border/40 bg-gradient-to-br from-muted/20 to-transparent p-4 transition-all duration-200 hover:shadow-md hover:border-primary/30 group">
                        <div class="flex items-start gap-4 mb-3">
                            <div class="flex-1 min-w-0">
                                <div class="mb-2">
                                    <p class="text-lg font-bold text-foreground leading-none">${dateStr}</p>
                                </div>
                                <div class="flex items-center gap-2 mb-2">
                                    <h3 class="font-semibold text-foreground group-hover:text-primary transition-colors">${this.escapeHtml(event.title || 'Event')}</h3>
                                    <span class="${typeConfig.badgeClass}">
                                        ${typeConfig.label}
                                    </span>
                                </div>
                                <div class="flex items-center gap-1.5 text-sm text-muted-foreground/80">
                                    <i data-lucide="clock" class="h-3.5 w-3.5 text-primary/60"></i>
                                    <span>${timeStr}</span>
                                </div>
                            </div>
                            ${participationWidget}
                        </div>
                        <div class="flex items-center justify-between text-xs text-muted-foreground/70 pt-3 border-t border-border/30">
                            <span class="flex items-center gap-1.5">
                                <i data-lucide="map-pin" class="h-3.5 w-3.5 text-primary/50"></i>
                                ${this.escapeHtml(location)}
                            </span>
                        </div>
                    </div>
                </div>
            `;
        }).join('') + (hasMore ? `
            <div class="flex justify-center mt-6">
                <button 
                    class="load-more-btn px-6 py-3 text-sm font-semibold text-primary bg-primary/10 hover:bg-primary/20 border-2 border-primary/30 hover:border-primary/50 rounded-lg transition-all duration-200 shadow-sm hover:shadow-md"
                    data-section="${sectionId}"
                    onclick="Dashboard.loadMoreEvents('${sectionId}')"
                >
                    Load More
                </button>
            </div>
        ` : '');

        // Reinitialize Lucide icons
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
        
        // Initialize participation widgets for all events
        // Wait a bit for DOM to be ready, then initialize widgets
        setTimeout(() => {
            this.initializeParticipationWidgets(container);
        }, 100);
    },
    
    /**
     * Initialize participation widgets in a container
     */
    initializeParticipationWidgets(container) {
        if (!container) {
            console.warn('initializeParticipationWidgets: container is null');
            return;
        }
        
        const widgets = container.querySelectorAll('[data-participation-widget]:not([data-initialized])');
        
        if (widgets.length === 0) {
            // No widgets to initialize
            return;
        }
        
        widgets.forEach(element => {
            const eventId = element.getAttribute('data-event-id');
            const eventType = element.getAttribute('data-event-type');
            
            if (!eventId || !eventType) {
                console.warn('ParticipationWidget: Missing eventId or eventType', {
                    eventId,
                    eventType,
                    element: element,
                    parent: element.parentElement
                });
                // Mark as initialized to prevent repeated warnings
                element.setAttribute('data-initialized', 'error');
                return;
            }
            
            if (!element.hasAttribute('data-initialized')) {
                try {
                    element.setAttribute('data-initialized', 'true');
                    const widget = new ParticipationWidget(element, eventId, eventType);
                    
                    // Verify widget was created successfully
                    if (!widget || !widget.container) {
                        console.error('ParticipationWidget: Widget creation failed', {
                            eventId,
                            eventType,
                            element: element
                        });
                        element.removeAttribute('data-initialized');
                    }
                } catch (error) {
                    console.error('Failed to initialize ParticipationWidget:', error, {
                        eventId,
                        eventType,
                        element: element,
                        errorMessage: error.message,
                        errorStack: error.stack
                    });
                    // Remove the initialized flag so we can try again
                    element.removeAttribute('data-initialized');
                }
            }
        });
    },

    /**
     * Render events needing response
     */
    renderEventsNeedingResponse(eventsNeedingResponse) {
        // Handle both old format (array) and new format (object with events property)
        const events = Array.isArray(eventsNeedingResponse) 
            ? eventsNeedingResponse 
            : (eventsNeedingResponse.events || []);
        
        this.renderEventsWidget(
            events,
            'events-needing-response-content',
            'events-needing-response-skeleton',
            'No events need your response at this time.',
            true, // Show participation widget
            'events-needing-response' // Section ID for filtering
        );
    },

    /**
     * Render events timeline
     */
    renderEventsTimeline(data) {
        const inboxItems = data.inbox || [];
        
        // Get upcoming events (all inbox items, sorted by date)
        // Include all events, not just those needing response
        const upcomingEvents = inboxItems
            .filter(item => item.eventBegin || item.dueDate)
            .sort((a, b) => {
                const dateA = new Date(a.eventBegin || a.dueDate);
                const dateB = new Date(b.eventBegin || b.dueDate);
                return dateA - dateB;
            });
            // No slice here - renderEventsWidget will handle the limit

        this.renderEventsWidget(
            upcomingEvents,
            'events-timeline-content',
            'events-timeline-skeleton',
            'No upcoming events scheduled.',
            true, // Show participation widget for all events
            'events-timeline' // Section ID for filtering
        );
    },

    /**
     * Map BNote otype to event type
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
     * Get event type configuration
     */
    getEventTypeConfig(type) {
        const configs = {
            rehearsal: {
                badgeClass: 'event-badge',
                dotClass: 'bg-primary',
                label: 'rehearsal',
                icon: 'music'
            },
            performance: {
                badgeClass: 'event-badge accent',
                dotClass: 'bg-accent',
                label: 'performance',
                icon: 'calendar'
            },
            meeting: {
                badgeClass: 'event-badge chart-3',
                dotClass: 'bg-chart-3',
                label: 'meeting',
                icon: 'users'
            }
        };
        return configs[type] || configs.meeting;
    },

    /**
     * Format event date
     */
    formatEventDate(dateStr) {
        if (!dateStr) return 'TBA';
        try {
            const date = new Date(dateStr);
            if (isNaN(date.getTime())) return 'TBA';
            
            // Format as "Jan 28" or "Feb 2"
            const month = date.toLocaleDateString('en-US', { month: 'short' });
            const day = date.getDate();
            return `${month} ${day}`;
        } catch (e) {
            return 'TBA';
        }
    },

    /**
     * Format event time
     */
    formatEventTime(dateStr) {
        if (!dateStr) return 'TBA';
        try {
            const date = new Date(dateStr);
            if (isNaN(date.getTime())) return 'TBA';
            
            // Format as "7:00 PM"
            return date.toLocaleTimeString('en-US', { 
                hour: 'numeric', 
                minute: '2-digit',
                hour12: true 
            });
        } catch (e) {
            return 'TBA';
        }
    },

    /**
     * Extract location from event title (fallback)
     */
    extractLocationFromTitle(title) {
        if (!title) return null;
        // Try to find location patterns in title
        // This is a simple heuristic - can be improved
        const locationPatterns = [
            /at\s+([A-Z][a-zA-Z\s]+)/i,
            /in\s+([A-Z][a-zA-Z\s]+)/i,
            /,\s+([A-Z][a-zA-Z\s]+)$/i
        ];
        
        for (const pattern of locationPatterns) {
            const match = title.match(pattern);
            if (match && match[1]) {
                return match[1].trim();
            }
        }
        return null;
    },

    /**
     * Respond to an event (accept/decline)
     */
    async respondToEvent(otype, oid, attending) {
        try {
            await DashboardApi.respondToEvent(otype, oid, attending);
            
            this.showToast(attending ? 'Event accepted' : 'Event declined', 'success');
            
            // Reload dashboard to update UI
            await this.loadDashboard();
        } catch (error) {
            console.error('Failed to respond to event:', error);
            this.showToast('Failed to update response', 'error');
        }
    },

    /**
     * Show toast notification
     */
    showToast(message, type = 'default') {
        const container = document.getElementById('toast-container');
        if (!container) return;

        const toast = document.createElement('div');
        const bgColor = type === 'error' ? 'bg-destructive' : type === 'success' ? 'bg-accent' : 'bg-background';
        const textColor = type === 'error' ? 'text-destructive-foreground' : type === 'success' ? 'text-accent-foreground' : 'text-foreground';
        
        toast.className = `group pointer-events-auto relative flex w-full items-center justify-between space-x-4 overflow-hidden rounded-md border p-6 pr-8 shadow-lg transition-all ${bgColor} ${textColor} border-border`;
        toast.innerHTML = `
            <div class="flex-1">
                <p class="text-sm font-semibold">${this.escapeHtml(message)}</p>
            </div>
            <button onclick="this.closest('.group').remove()" class="absolute right-2 top-2 rounded-md p-1 text-foreground/50 opacity-0 transition-opacity hover:text-foreground focus:opacity-100 group-hover:opacity-100">
                <i data-lucide="x" class="h-4 w-4"></i>
            </button>
        `;

        container.appendChild(toast);

        // Reinitialize Lucide icons
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }

        // Auto-remove after 3 seconds
        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateX(100%)';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    },

    /**
     * Show error message
     */
    showError(message) {
        this.showToast(message, 'error');
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
