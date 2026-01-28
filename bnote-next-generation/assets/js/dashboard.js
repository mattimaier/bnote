/**
 * BNote Next Generation - Dashboard Module
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
 * Dashboard functionality for BNote Modern UI
 * Converts v0.dev React components to vanilla JavaScript
 */

const Dashboard = {
    // State
    sidebarOpen: true,
    sidebarCollapsed: false,
    session: null,
    dashboardData: null,
    eventClickHandlerInitialized: false,
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
        this.initWelcomeHeader();
        this.initQuickActions();
        this.initFilters();

        // Initialize event click handlers early (before loading dashboard)
        this.initGlobalEventClickHandlers();

        // Load dashboard data
        await this.loadDashboard();
    },

    /**
     * Initialize welcome header
     */
    initWelcomeHeader() {
        if (!this.session?.user) return;

        const user = this.session.user;
        const firstName = user.name || (typeof i18n !== 'undefined' && i18n.t ? i18n.t('js.common.user') : 'User');
        const lastName = user.surname || '';
        const fullName = `${firstName} ${lastName}`.trim();

        // User name and initials in header are set by UserInfo.init(); do not overwrite here.

        // Set greeting - use translations if available, otherwise will be updated by translatePage()
        const greetingEl = document.getElementById('welcome-greeting');
        if (greetingEl) {
            // Check if translations are loaded
            const translationsLoaded = typeof i18n !== 'undefined' && Object.keys(i18n.translations || {}).length > 0;
            
            if (translationsLoaded) {
                // Use translated welcome text if available
                const welcomeText = i18n.t('banner_Logout.welcome');
                if (welcomeText && welcomeText !== 'banner_Logout.welcome') {
                    greetingEl.textContent = `${welcomeText}, ${firstName}`;
                } else {
                    // Fallback to time-based greeting
                    const greeting = this.getGreeting();
                    greetingEl.textContent = `${greeting}, ${firstName}`;
                }
            } else {
                // Translations not loaded yet - set placeholder (will be updated by translatePage())
                greetingEl.textContent = 'Welcome';
            }
        }

        // Set dashboard subtitle with company name (will be updated after dashboard loads)
        // Only update if translations are loaded
        if (typeof i18n !== 'undefined' && Object.keys(i18n.translations || {}).length > 0) {
            this.updateDashboardSubtitle();
        }
    },

    /**
     * Update dashboard subtitle with band/company name (from config).
     * %p in js.dashboard.subtitle is the Band Name. No hardcoded fallback.
     */
    updateDashboardSubtitle() {
        if (typeof i18n === 'undefined' || Object.keys(i18n.translations || {}).length === 0) {
            return;
        }

        const subtitleEl = document.getElementById('dashboard-subtitle');
        if (!subtitleEl) return;

        const raw = this.dashboardData?.company;
        const companyName = (typeof i18n.normalizeCompany === 'function')
            ? i18n.normalizeCompany(raw)
            : (typeof raw === 'string' ? raw : '');
        const fallback = companyName || (typeof i18n.t === 'function' ? i18n.t('js.common.appName') : 'BNote');

        const translated = i18n.t('js.dashboard.subtitle', [fallback]);
        if (translated && translated !== 'js.dashboard.subtitle') {
            subtitleEl.textContent = translated;
        }
    },

    /**
     * Translate page elements
     */
    translatePage() {
        if (typeof i18n === 'undefined' || Object.keys(i18n.translations || {}).length === 0) {
            return;
        }

        i18n.translatePage();

        // Update welcome greeting with translated welcome text + name
        const greetingEl = document.getElementById('welcome-greeting');
        if (greetingEl && this.session?.user) {
            const firstName = this.session.user.name || (typeof i18n !== 'undefined' && i18n.t ? i18n.t('js.common.user') : 'User');
            const welcomeText = i18n.t('banner_Logout.welcome');
            // Use translated welcome text if available, otherwise use time-based greeting
            if (welcomeText && welcomeText !== 'banner_Logout.welcome') {
                greetingEl.textContent = `${welcomeText}, ${firstName}`;
            } else {
                // Fallback to time-based greeting
                const greeting = this.getGreeting();
                greetingEl.textContent = `${greeting}, ${firstName}`;
            }
        }
    },

    /**
     * Get time-based greeting (localized)
     */
    getGreeting() {
        const hour = new Date().getHours();
        if (typeof i18n !== 'undefined' && Object.keys(i18n.translations || {}).length > 0) {
            if (hour < 12) return i18n.t('js.common.greeting.morning');
            if (hour < 18) return i18n.t('js.common.greeting.afternoon');
            return i18n.t('js.common.greeting.evening');
        }
        if (hour < 12) return 'js.common.greeting.morning';
        if (hour < 18) return 'js.common.greeting.afternoon';
        return 'js.common.greeting.evening';
    },

    /**
     * Initialize quick actions
     */
    initQuickActions() {
        const t = (k) => (typeof i18n !== 'undefined' && i18n.t ? i18n.t(k) : k);
        const actions = [
            { titleKey: 'js.dashboard.quickAction.viewCalendar', descKey: 'js.dashboard.quickAction.viewCalendarDesc', icon: 'calendar-days', colorClass: 'bg-primary/10 text-primary hover:bg-primary/20', href: '#' },
            { titleKey: 'js.dashboard.quickAction.contactBand', descKey: 'js.dashboard.quickAction.contactBandDesc', icon: 'message-square', colorClass: 'bg-accent/10 text-accent hover:bg-accent/20', href: '#' },
            { titleKey: 'js.dashboard.quickAction.bandDirectory', descKey: 'js.dashboard.quickAction.bandDirectoryDesc', icon: 'users', colorClass: 'bg-chart-3/10 text-chart-3 hover:bg-chart-3/20', href: '#' },
            { titleKey: 'js.dashboard.quickAction.myProfile', descKey: 'js.dashboard.quickAction.myProfileDesc', icon: 'music', colorClass: 'bg-chart-4/10 text-chart-4 hover:bg-chart-4/20', href: '#' }
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
                    <p class="font-semibold text-sm leading-tight">${t(action.titleKey)}</p>
                    <p class="text-xs text-muted-foreground/70 font-normal mt-1">${t(action.descKey)}</p>
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

            // Translate page elements (will update subtitle if translations loaded)
            this.translatePage();
            this.updateDashboardSubtitle();

            // Listen for i18n loaded event and re-translate everything
            const handleI18nLoaded = () => {
                this.translatePage();
                this.updateDashboardSubtitle();
                // Re-render events to update labels and dates
                if (this.allEvents['events-needing-response']?.length > 0) {
                    this.renderEventsNeedingResponse(this.allEvents['events-needing-response']);
                }
                if (this.allEvents['events-timeline']?.length > 0) {
                    this.renderEventsTimeline({ inbox: this.allEvents['events-timeline'] });
                }
            };

            // Add listener (remove after first call to avoid duplicates)
            window.addEventListener('i18n:loaded', handleI18nLoaded, { once: true });

            // Also check if translations are already loaded
            if (typeof i18n !== 'undefined' && Object.keys(i18n.translations).length > 0) {
                // Translations already loaded, translate now
                setTimeout(handleI18nLoaded, 100);
            }

            // Hide loading states
            this.hideLoadingStates();

        } catch (error) {
            console.error('Failed to load dashboard:', error);
            const msg = (typeof i18n !== 'undefined' && i18n.t ? i18n.t('js.error.dashboardLoadFailed') : 'Failed to load dashboard.');
            this.showError(error.message || msg);
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
     * Uses a lightweight update that only refreshes counts, avoiding full re-renders
     */
    async refreshEventsSections() {
        try {
            // Debounce: only refresh if not already refreshing
            if (this._refreshing) return;
            this._refreshing = true;

            // Small delay to let user see the widget update first
            await new Promise(resolve => setTimeout(resolve, 200));

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

            // Check if event list actually changed (not just participation status)
            const oldEventIds = new Set((this.allEvents['events-needing-response'] || []).map(e => String(e.oid)));
            const newEventIds = new Set(eventsNeedingResponseArray.map(e => String(e.oid)));
            const eventListChanged = oldEventIds.size !== newEventIds.size || 
                Array.from(oldEventIds).some(id => !newEventIds.has(id)) ||
                Array.from(newEventIds).some(id => !oldEventIds.has(id));

            // Update stored events and counts
            this.allEvents['events-needing-response'] = eventsNeedingResponseArray;
            if (eventsNeedingResponse.counts) {
                this.eventCounts['events-needing-response'] = eventsNeedingResponse.counts;
            }
            if (eventsNeedingResponse.config) {
                this.maxDisplayCounts['events-needing-response'] = eventsNeedingResponse.config.max_show || 5;
            }
            
            // Reset displayed count to match available events (after filtering)
            // This ensures we don't show "Load More" when there are no more events
            const filteredCount = this.applyFilters('events-needing-response', eventsNeedingResponseArray).length;
            this.displayedCounts['events-needing-response'] = Math.min(
                this.maxDisplayCounts['events-needing-response'] || 5,
                filteredCount
            );

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

            // Always update filter counts (lightweight, no re-render)
            this.updateFilterCounts('events-needing-response', this.eventCounts['events-needing-response']);
            this.updateFilterCounts('events-timeline', this.eventCounts['events-timeline']);

            // Always re-render "events-needing-response" section because participation changes
            // can affect which events appear in this section (e.g., if participation is removed,
            // the event might no longer need a response)
            await this.renderEventsNeedingResponseWithAnimation(eventsNeedingResponseArray);
            
            // Only re-render timeline if event list changed (events added/removed)
            // Timeline events are less affected by participation status changes
            if (eventListChanged) {
                await this.renderEventsTimelineWithAnimation(dashboardData);
            } else {
                // Just re-initialize participation widgets in timeline section
                const container2 = document.getElementById('events-timeline-content');
                if (container2) {
                    this.initializeParticipationWidgets(container2);
                }
            }

        } catch (error) {
            console.error('Failed to refresh events sections:', error);
            // Don't show error toast for background refresh
        } finally {
            this._refreshing = false;
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
        
        // Apply filters first to get the actual events that should be displayed
        const filteredEvents = this.applyFilters('events-needing-response', events);
        
        // Reset displayed count to match filtered events (but don't exceed max)
        // This ensures we don't show "Load More" when there are no more events
        const maxDisplay = this.maxDisplayCounts['events-needing-response'] || 5;
        this.displayedCounts['events-needing-response'] = Math.min(maxDisplay, filteredEvents.length);
        
        // Get currently displayed event IDs from DOM
        const currentEventIds = new Set(
            Array.from(container.querySelectorAll('[data-event-id]'))
                .map(el => el.getAttribute('data-event-id'))
        );
        
        // Get new event IDs from filtered events (these are the events that should be displayed)
        const newEventIds = new Set(filteredEvents.map(e => String(e.oid)));

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

        // Find events to add (fade in) - use filtered events
        const toAdd = filteredEvents.filter(e => !currentEventIds.has(String(e.oid)));

        // Render all events (including existing ones) - already filtered
        // This will use the updated displayedCounts we set above
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

        // Use all events from stored data (already updated in refreshEventsSections)
        // This ensures filters work correctly - we need ALL events, not just first 5
        const allInboxItems = this.allEvents['events-timeline'] || dashboardData.inbox || [];
        const upcomingEvents = allInboxItems
            .filter(item => item.eventBegin || item.dueDate)
            .sort((a, b) => {
                const dateA = new Date(a.eventBegin || a.dueDate);
                const dateB = new Date(b.eventBegin || b.dueDate);
                return dateA - dateB;
            });
        // Don't slice here - renderEventsWidget will handle filtering and limiting

        // Apply filters to get the events that should be displayed
        const filteredEvents = this.applyFilters('events-timeline', upcomingEvents);

        const currentEventIds = new Set(
            Array.from(container.querySelectorAll('[data-event-id]'))
                .map(el => el.getAttribute('data-event-id'))
        );
        const newEventIds = new Set(filteredEvents.map(e => String(e.oid)));

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

        // Find events to add (fade in) - use filtered events
        const toAdd = filteredEvents.filter(e => !currentEventIds.has(String(e.oid)));

        // Render all events (including existing ones) - renderEventsWidget will apply filters and limit
        this.renderEventsWidget(
            upcomingEvents, // Pass all events, renderEventsWidget will filter and limit
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
    renderEventsWidget(events, contentId, skeletonId, emptyMessage, showParticipation = false, sectionId = null, showLoadMore = true) {
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

        // Limit events to displayed count (unless showLoadMore is false, then show all)
        let currentDisplayed = 0;
        if (!showLoadMore) {
            // Show all events when showLoadMore is false (e.g., for search results page)
            currentDisplayed = eventsList.length;
        } else if (sectionId && this.displayedCounts[sectionId] !== undefined) {
            currentDisplayed = this.displayedCounts[sectionId];
        } else if (sectionId && this.maxDisplayCounts[sectionId]) {
            currentDisplayed = this.maxDisplayCounts[sectionId];
        } else {
            currentDisplayed = 5; // Default
        }

        const displayedEvents = eventsList.slice(0, currentDisplayed);
        const hasMore = showLoadMore && eventsList.length > currentDisplayed;

        // Use shared EventRenderer for consistent rendering
        let eventsHtml = '';
        if (typeof EventRenderer !== 'undefined' && EventRenderer.renderEventItem) {
            eventsHtml = displayedEvents.map((event, index) => {
                return EventRenderer.renderEventItem(event, {
                    showParticipation: showParticipation,
                    isLast: index === displayedEvents.length - 1,
                    isMobile: window.innerWidth < 768
                });
            }).join('');
        } else {
            // Fallback to original implementation if EventRenderer not available
            eventsHtml = displayedEvents.map((event, index) => {
                const eventType = this.mapOtypeToEventType(event.otype);
                const typeConfig = this.getEventTypeConfig(eventType);
                const dateStr = this.formatEventDate(event.eventBegin || event.dueDate);
                const timeStr = this.formatEventTime(event.eventBegin || event.dueDate);
                const tbaText = typeof i18n !== 'undefined' && Object.keys(i18n.translations || {}).length > 0
                    ? i18n.t('js.event.tba')
                    : 'TBA';
                const eventTitleFallback = typeof i18n !== 'undefined' && i18n.t ? i18n.t('js.event.event') : 'Event';
                const location = event.location || event.locationData?.name || 
                    (typeof EventRenderer !== 'undefined' && EventRenderer.extractLocationFromTitle ? EventRenderer.extractLocationFromTitle(event.title) : null) || 
                    this.extractLocationFromTitle(event.title) || tbaText;
                const isLast = index === eventsList.length - 1;
                const title = event.title || eventTitleFallback;
                const hideTitleWhenDuplicate = title === typeConfig.label;
                const hasValidEventData = event.oid && event.otype && (event.otype === 'R' || event.otype === 'C');
                const participationWidget = showParticipation && hasValidEventData ? `
                    <div 
                        class="flex flex-col gap-2 shrink-0 items-end w-fit" 
                        data-participation-widget 
                        data-event-id="${event.oid}" 
                        data-event-type="${event.otype}"
                    ></div>
                ` : '';
                const isMobile = window.innerWidth < 768;
                const isClickable = event.otype === 'R' || event.otype === 'C';
                const entityType = event.otype === 'C' ? 'concert' : 'rehearsal';
                const entityId = event.oid;
                
                // Generate entity detail URL
                // Dashboard always uses 'dashboard' as module context
                const moduleContext = 'dashboard';
                
                let eventUrl = '#';
                if (isClickable && entityId) {
                    if (typeof EntityService !== 'undefined' && typeof EntityService.getEntityDetailUrl === 'function') {
                        eventUrl = EntityService.getEntityDetailUrl(entityType, entityId, moduleContext, 'view');
                    } else if (typeof NavigationService !== 'undefined' && typeof NavigationService.getEntityUrl === 'function') {
                        eventUrl = NavigationService.getEntityUrl(entityType, entityId, moduleContext, 'view');
                    }
                }
                
                const linkClass = isClickable ? 'block no-underline text-foreground hover:text-foreground' : '';
                const wrapperTag = isClickable ? 'a' : 'div';
                const wrapperAttrs = isClickable ? `href="${eventUrl}"` : '';

                if (isMobile) {
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
            }).join('');
        }

        container.innerHTML = eventsHtml + (showLoadMore && sectionId && hasMore ? `
            <div class="flex justify-center mt-6">
                <button 
                    class="load-more-btn px-6 py-3 text-sm font-semibold text-primary bg-primary/10 hover:bg-primary/20 border-2 border-primary/30 hover:border-primary/50 rounded-lg transition-all duration-200 shadow-sm hover:shadow-md"
                    data-section="${sectionId}"
                    onclick="Dashboard.loadMoreEvents('${sectionId}')"
                >
                    ${typeof i18n !== 'undefined' && i18n.t ? i18n.t('js.common.loadMore') : 'Load More'}
                </button>
            </div>
        ` : '');

        // Re-initialize Lucide icons after rendering
        setTimeout(() => {
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        }, 0);

        // Initialize participation widgets for all events
        // Wait a bit for DOM to be ready, then initialize widgets
        setTimeout(() => {
            this.initializeParticipationWidgets(container);
        }, 100);
    },

    /**
     * Initialize global click handlers for event items (set up once)
     */
    initGlobalEventClickHandlers() {
        if (this.eventClickHandlerInitialized) {
            return;
        }

        const self = this;

        // Use event delegation on document level - only handle participation widget clicks
        // Browser handles navigation via native <a> tags
        document.addEventListener('click', function (e) {
            // Don't trigger if clicking on participation widget or its children
            // Participation widgets need special handling (modal, etc.)
            if (e.target.closest('[data-participation-widget]')) {
                return; // Let participation widget handle its own clicks
            }

            // Don't intercept clicks on links - let browser handle navigation
            if (e.target.closest('a[href]')) {
                return; // Browser handles <a> tag navigation
            }

            // Only handle clicks on old-style event-clickable divs (for backward compatibility)
            // These should be migrated to <a> tags eventually
            const eventElement = e.target.closest('.event-clickable[data-event-type][data-event-id]');
            if (eventElement) {
                const eventType = eventElement.getAttribute('data-event-type');
                const eventId = eventElement.getAttribute('data-event-id');

                if (eventType && eventId && (eventType === 'R' || eventType === 'C')) {
                    // Check if click came from search results
                    const searchResultsContainer = document.getElementById('search-results-container');
                    const searchResultsOverlay = document.getElementById('search-results-overlay');
                    const isFromSearch = (searchResultsContainer && searchResultsContainer.contains(eventElement)) ||
                                       (searchResultsOverlay && searchResultsOverlay.contains(eventElement));
                    
                    let searchQuery = null;
                    let searchFilters = null;
                    
                    if (isFromSearch) {
                        // Get search context from SearchResultsPage or Search component
                        if (typeof SearchResultsPage !== 'undefined' && SearchResultsPage.currentQuery) {
                            searchQuery = SearchResultsPage.currentQuery;
                            searchFilters = SearchResultsPage.currentFilters || {};
                        } else if (typeof Search !== 'undefined' && Search.currentQuery) {
                            searchQuery = Search.currentQuery;
                            searchFilters = Search.currentFilters || {};
                        }
                    }
                    
                    e.preventDefault();
                    e.stopPropagation();
                    self.openEventDetail(eventType, parseInt(eventId), isFromSearch, searchQuery, searchFilters).catch(err => {
                        console.error('Failed to open event detail:', err);
                    });
                }
            }
        });

        this.eventClickHandlerInitialized = true;
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
    /**
     * Map otype to event type (delegates to EventRenderer)
     */
    mapOtypeToEventType(otype) {
        if (typeof EventRenderer !== 'undefined' && EventRenderer.mapOtypeToEventType) {
            return EventRenderer.mapOtypeToEventType(otype);
        }
        // Fallback
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
     * Get event type configuration (delegates to EventRenderer)
     */
    getEventTypeConfig(type) {
        if (typeof EventRenderer !== 'undefined' && EventRenderer.getEventTypeConfig) {
            return EventRenderer.getEventTypeConfig(type);
        }
        // Fallback
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
     * Format event date (delegates to EventRenderer)
     */
    formatEventDate(dateStr) {
        if (typeof EventRenderer !== 'undefined' && EventRenderer.formatEventDate) {
            return EventRenderer.formatEventDate(dateStr);
        }
        // Fallback
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
     * Format event time (delegates to EventRenderer)
     */
    formatEventTime(dateStr) {
        if (typeof EventRenderer !== 'undefined' && EventRenderer.formatEventTime) {
            return EventRenderer.formatEventTime(dateStr);
        }
        // Fallback
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

            const okMsg = (typeof i18n !== 'undefined' && i18n.t ? i18n.t('js.event.accepted') : 'Event accepted');
            const noMsg = (typeof i18n !== 'undefined' && i18n.t ? i18n.t('js.event.declined') : 'Event declined');
            this.showToast(attending ? okMsg : noMsg, 'success');

            // Reload dashboard to update UI
            await this.loadDashboard();
        } catch (error) {
            console.error('Failed to respond to event:', error);
            this.showToast((typeof i18n !== 'undefined' && i18n.t ? i18n.t('js.error.updateResponseFailed') : 'Failed to update response'), 'error');
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
     * Open event detail view
     * @param {string} eventType - Event type ('R' or 'C')
     * @param {number} eventId - Event ID
     */
    async openEventDetail(eventType, eventId, fromSearch = false, searchQuery = null, searchFilters = null) {
        // Check for EventDetail - try both global scope and window
        let EventDetailComponent = null;
        
        // Try to access EventDetail - it might be in global scope or window
        try {
            EventDetailComponent = typeof EventDetail !== 'undefined' ? EventDetail : null;
        } catch (e) {
            // EventDetail not in global scope, try window
        }
        
        if (!EventDetailComponent && typeof window !== 'undefined') {
            EventDetailComponent = window.EventDetail || null;
        }
        
        // Wait for EventDetail to load if not available yet
        if (!EventDetailComponent) {
            let attempts = 0;
            const maxAttempts = 40; // 2 seconds
            while (!EventDetailComponent && attempts < maxAttempts) {
                await new Promise(resolve => setTimeout(resolve, 50));
                
                // Try both ways again
                try {
                    EventDetailComponent = typeof EventDetail !== 'undefined' ? EventDetail : null;
                } catch (e) {
                    // Continue
                }
                
                if (!EventDetailComponent && typeof window !== 'undefined') {
                    EventDetailComponent = window.EventDetail || null;
                }
                
                attempts++;
            }
        }
        
        if (EventDetailComponent && typeof EventDetailComponent.init === 'function') {
            // Store search context if coming from search
            if (fromSearch && searchQuery) {
                // Set a flag that EventDetail can check
                EventDetailComponent._fromSearch = true;
                EventDetailComponent._searchQuery = searchQuery;
                EventDetailComponent._searchFilters = searchFilters || {};
            } else {
                EventDetailComponent._fromSearch = false;
                EventDetailComponent._searchQuery = null;
                EventDetailComponent._searchFilters = null;
            }
            EventDetailComponent.init(eventType, eventId);
        } else {
            console.error('EventDetail component not loaded after waiting');
            console.error('EventDetail type:', typeof EventDetail);
            console.error('window.EventDetail type:', typeof window !== 'undefined' ? typeof window.EventDetail : 'window undefined');
            console.error('All window properties:', Object.keys(window).slice(0, 50));
            this.showToast((typeof i18n !== 'undefined' && i18n.t ? i18n.t('js.error.eventDetailUnavailable') : 'Event detail view not available. Please refresh the page.'), 'error');
        }
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
