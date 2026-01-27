/**
 * Participant Overview Component
 * Displays participants grouped by instruments or categories with color-coded status
 * Supports grouping toggle and shows participation diagrams
 */
const ParticipantOverview = {
    groupingMode: 'category', // 'category' or 'instrument'

    /**
     * Render participant overview with grouping support
     * @param {HTMLElement} container - Container element to render into
     * @param {Array} participantsByInstrument - Array of instrument groups with participants
     * @param {string} groupingMode - 'category' or 'instrument' (default: 'category')
     */
    render(container, participantsByInstrument, groupingMode = 'category') {
        if (!container) return;

        this.groupingMode = groupingMode;

        // Group participants based on mode first
        const grouped = participantsByInstrument && participantsByInstrument.length > 0
            ? InstrumentGrouping.group(participantsByInstrument, groupingMode)
            : [];

        // Check if there are actually any participants after grouping
        const hasParticipants = grouped.some(group =>
            group.participants && group.participants.length > 0
        );

        if (!hasParticipants) {
            const t = (k) => (typeof i18n !== 'undefined' && i18n.t ? i18n.t(k) : k);
            container.innerHTML = `
                <div class="text-center py-8 text-muted-foreground text-sm">
                    ${t('js.participants.noParticipantsYet')}
                </div>
            `;
            return;
        }

        // Render grouping toggle
        const toggleHtml = this.renderGroupingToggle();

        // Render groups in a grid layout
        const groupsHtml = grouped.map((group, index) => {
            return this.renderGroup(group, groupingMode, index);
        }).join('');

        container.innerHTML = `
            <div class="participant-overview">
                ${toggleHtml}
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mt-4">
                    ${groupsHtml}
                </div>
            </div>
        `;

        // Attach toggle handler after DOM is updated
        // Query from the actual DOM element that contains the buttons
        setTimeout(() => {
            // Find the actual container in the DOM (might be different from the passed container)
            const actualContainer = document.getElementById('participant-overview-container');
            if (actualContainer) {
                this.attachToggleHandler(actualContainer);
            } else {
                // Fallback to passed container
                this.attachToggleHandler(container);
            }
        }, 100);
    },

    /**
     * Render grouping toggle buttons (matching dashboard filter bubble style)
     */
    renderGroupingToggle() {
        const categoryActive = this.groupingMode === 'category';
        const instrumentActive = this.groupingMode === 'instrument';

        const t = (k) => (typeof i18n !== 'undefined' && i18n.t ? i18n.t(k) : k);
        return `
            <div class="flex items-center gap-3 mb-4">
                <span class="text-sm font-medium text-muted-foreground">${t('js.participants.groupBy')}</span>
                <button 
                    class="filter-bubble ${categoryActive ? 'selected' : ''}"
                    data-mode="category"
                    data-grouping-toggle="true"
                >
                    ${t('js.participants.category')}
                </button>
                <button 
                    class="filter-bubble ${instrumentActive ? 'selected' : ''}"
                    data-mode="instrument"
                    data-grouping-toggle="true"
                >
                    ${t('js.participants.instrument')}
                </button>
            </div>
        `;
    },

    /**
     * Attach toggle handler
     */
    attachToggleHandler(container) {
        // Always query from document to ensure we get the actual buttons in the DOM
        const buttons = document.querySelectorAll('#participant-overview-container [data-grouping-toggle="true"]');

        if (buttons.length === 0) {
            // Try querying from anywhere as fallback
            const fallbackButtons = document.querySelectorAll('[data-grouping-toggle="true"]');
            if (fallbackButtons.length === 0) {
                return;
            }
            // Use fallback buttons
            fallbackButtons.forEach(button => this.attachButtonHandler(button));
            return;
        }

        buttons.forEach(button => this.attachButtonHandler(button));
    },

    /**
     * Attach click handler to a single button
     */
    attachButtonHandler(button) {
        // Remove any existing listeners by cloning and replacing
        const newButton = button.cloneNode(true);
        if (button.parentNode) {
            button.parentNode.replaceChild(newButton, button);
        }

        newButton.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();

            const mode = newButton.getAttribute('data-mode');

            if (mode && typeof EventDetail !== 'undefined' && EventDetail.currentEvent) {
                // Update grouping mode
                this.groupingMode = mode;

                // Re-render with new grouping mode
                const participantContainer = document.getElementById('participant-overview-container');
                if (participantContainer && EventDetail.currentEvent.participantsByInstrument) {
                    this.render(participantContainer, EventDetail.currentEvent.participantsByInstrument, mode);

                    // Reinitialize Lucide icons
                    if (typeof lucide !== 'undefined') {
                        setTimeout(() => lucide.createIcons(), 100);
                    }
                }
            }
        });
    },

    /**
     * Render a single group (category or instrument)
     * @param {Object} group - Group object (category or instrument)
     * @param {string} mode - 'category' or 'instrument'
     */
    renderGroup(group, mode, index) {
        const groupName = InstrumentGrouping.getGroupName(group, mode);
        const { participants, stats } = group;
        const t = (k) => (typeof i18n !== 'undefined' && i18n.t ? i18n.t(k) : k);

        // Calculate total if not present in stats
        const yes = stats?.yes || 0;
        const maybe = stats?.maybe || 0;
        const no = stats?.no || 0;
        const pending = stats?.pending || 0;
        const calculatedTotal = yes + maybe + no + pending;

        // Always create stats object with total for diagram
        const statsWithTotal = stats ? {
            ...stats,
            total: stats.total || calculatedTotal,
            yes: yes,
            maybe: maybe,
            no: no,
            pending: pending
        } : {
            yes: 0,
            maybe: 0,
            no: 0,
            pending: 0,
            total: 0
        };

        // Check if we have participants or stats
        const hasParticipants = participants && participants.length > 0;
        const hasStats = calculatedTotal > 0;

        // Render participation diagram for this group if stats exist
        let diagramHtml = '';
        if (hasStats && statsWithTotal.total > 0) {
            const diagramContainer = document.createElement('div');
            ParticipationDiagram.render(diagramContainer, statsWithTotal);
            diagramHtml = diagramContainer.innerHTML || '';
        }

        // Render participants
        const participantsHtml = hasParticipants
            ? participants.map(participant => this.renderParticipant(participant)).join('')
            : '';

        return `
            <div class="participant-group bg-card border border-border/40 rounded-lg p-4 shadow-sm">
                <div class="mb-3">
                    <h3 class="text-base font-semibold text-foreground mb-2 text-center">${this.escapeHtml(groupName)}</h3>
                    ${diagramHtml}
                </div>
                ${hasParticipants ? `
                    <div class="space-y-2">
                        ${participantsHtml}
                    </div>
                ` : `
                    <div class="text-center py-4 text-muted-foreground text-sm">
                        ${t('js.participants.noParticipantsYet')}
                    </div>
                `}
            </div>
        `;
    },

    /**
     * Render a single participant
     * @param {Object} participant - Participant data
     */
    renderParticipant(participant) {
        const { name, participate, reason } = participant;
        const colors = ParticipationConstants.getColors(participate);

        // Get initials for badge (like filter UI)
        const initials = this.getInitials(name);

        // Determine status icon style - match participation widget traffic light buttons EXACTLY
        // Use the same SVG structure and classes as ParticipationWidget
        let statusIconHtml = '';
        if (participate === null || participate === undefined || participate < 0) {
            // Pending: gray background with white question mark (same SVG as ParticipationWidget)
            statusIconHtml = `
                <div class="h-8 w-8 rounded-full bg-gray-400 border-2 border-gray-400 flex items-center justify-center shrink-0 text-white">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            `;
        } else {
            const participateNum = parseInt(participate);
            if (participateNum === 1) {
                // Yes: green background with white checkmark (same SVG as ParticipationWidget)
                statusIconHtml = `
                    <div class="h-8 w-8 rounded-full bg-green-500 border-2 border-green-500 flex items-center justify-center shrink-0 text-white">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                `;
            } else if (participateNum === 2) {
                // Maybe: amber background with white question mark (same SVG as ParticipationWidget)
                statusIconHtml = `
                    <div class="h-8 w-8 rounded-full bg-amber-400 border-2 border-amber-400 flex items-center justify-center shrink-0 text-white">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                `;
            } else {
                // No: red background with white X (same SVG as ParticipationWidget)
                statusIconHtml = `
                    <div class="h-8 w-8 rounded-full bg-red-500 border-2 border-red-500 flex items-center justify-center shrink-0 text-white">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </div>
                `;
            }
        }

        // Render reason if present
        const reasonHtml = reason && reason.trim() ? `
            <div class="text-xs text-muted-foreground italic mt-1">
                ${this.escapeHtml(reason)}
            </div>
        ` : '';

        return `
            <div class="participant-item flex items-start gap-3 py-2 px-2 rounded-md hover:bg-muted/50 transition-colors">
                <div class="h-8 w-8 rounded-full ${colors.bgLight} ${colors.borderLight} border-2 text-xs font-semibold flex items-center justify-center shrink-0 ${colors.text}">
                    ${this.escapeHtml(initials)}
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-sm text-foreground font-medium">${this.escapeHtml(name)}</span>
                        ${statusIconHtml}
                    </div>
                    ${reasonHtml}
                </div>
            </div>
        `;
    },

    /**
     * Get initials from name (for badge)
     */
    getInitials(name) {
        if (!name) return '?';
        const parts = name.trim().split(/\s+/);
        if (parts.length >= 2) {
            return (parts[0][0] || '') + (parts[parts.length - 1][0] || '');
        }
        return name.substring(0, 2).toUpperCase() || '?';
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
