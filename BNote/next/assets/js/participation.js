/**
 * Participation Widget
 * Modern traffic light UI for event participation status
 */
class ParticipationWidget {
    constructor(containerElement, eventId, eventType) {
        if (!containerElement) {
            console.error('ParticipationWidget: containerElement is required');
            return;
        }
        
        if (!eventId || !eventType) {
            console.error('ParticipationWidget: eventId and eventType are required', { eventId, eventType });
            return;
        }
        
        this.container = containerElement;
        this.eventId = String(eventId);
        this.eventType = String(eventType); // 'R' for rehearsal, 'C' for concert
        this.currentStatus = 'undecided';
        this.allowMaybe = false;
        this.isLoading = false;
        this.pendingStatus = null;
        this.pendingReason = null;
        this.isLocked = false;
        
        this.init();
    }
    
    /**
     * Initialize widget
     */
    async init() {
        // Create widget HTML structure
        this.render();
        
        // Fetch current status
        await this.fetchStatus();
        
        // Attach event listeners
        this.attachEventListeners();
    }
    
    /**
     * Render widget HTML
     */
    render() {
        if (!this.container) {
            console.error('ParticipationWidget: Cannot render - container is null');
            return;
        }
        
        if (!this.eventId || !this.eventType) {
            console.error('ParticipationWidget: Cannot render - missing eventId or eventType', {
                eventId: this.eventId,
                eventType: this.eventType
            });
            this.container.innerHTML = `
                <div class="flex flex-col gap-2 items-end">
                    <p class="text-xs font-medium text-muted-foreground opacity-50">Teilnahme</p>
                    <p class="text-xs text-muted-foreground/50">Error</p>
                </div>
            `;
            return;
        }
        
        this.container.innerHTML = `
            <div class="flex flex-col gap-2 items-end">
                <p class="text-xs font-medium text-muted-foreground">Teilnahme</p>
                <div class="flex items-center gap-3 justify-end">
                    <button 
                        data-status="yes" 
                        class="participation-btn participation-btn-yes w-10 h-10 md:w-12 md:h-12 rounded-full border-2 flex items-center justify-center transition-all duration-300 ease-in-out hover:scale-110 hover:shadow-md disabled:opacity-50 disabled:cursor-not-allowed"
                        aria-label="Participate"
                    >
                        <svg class="w-5 h-5 md:w-6 md:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </button>
                    <button 
                        data-status="maybe" 
                        class="participation-btn participation-btn-maybe w-10 h-10 md:w-12 md:h-12 rounded-full border-2 flex items-center justify-center transition-all duration-300 ease-in-out hover:scale-110 hover:shadow-md disabled:opacity-50 disabled:cursor-not-allowed"
                        style="display: none;"
                        aria-label="Maybe participate"
                    >
                        <svg class="w-5 h-5 md:w-6 md:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </button>
                    <button 
                        data-status="no" 
                        class="participation-btn participation-btn-no w-10 h-10 md:w-12 md:h-12 rounded-full border-2 flex items-center justify-center transition-all duration-300 ease-in-out hover:scale-110 hover:shadow-md disabled:opacity-50 disabled:cursor-not-allowed"
                        aria-label="Do not participate"
                    >
                        <svg class="w-5 h-5 md:w-6 md:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>
        `;
    }
    
    /**
     * Fetch current participation status from API
     */
    async fetchStatus() {
        try {
            if (!this.eventId || !this.eventType) {
                console.error('ParticipationWidget: Cannot fetch status without eventId and eventType', {
                    eventId: this.eventId,
                    eventType: this.eventType
                });
                return;
            }
            
            const data = await ParticipationApi.getStatus(this.eventId, this.eventType);
            this.currentStatus = data.status || 'undecided';
            this.allowMaybe = data.allow_maybe || false;
            this.isLocked = data.is_locked || false;
            
            // Update button visibility for maybe button
            const maybeButton = this.container.querySelector('[data-status="maybe"]');
            if (maybeButton) {
                if (this.allowMaybe) {
                    maybeButton.style.display = 'flex';
                    maybeButton.style.width = '';
                    maybeButton.classList.remove('hidden', 'w-0');
                } else {
                    maybeButton.style.display = 'none';
                    maybeButton.style.width = '0';
                    maybeButton.classList.add('hidden', 'w-0');
                    // If maybe was selected but is now disabled, reset to undecided
                    if (this.currentStatus === 'maybe') {
                        this.currentStatus = 'undecided';
                    }
                }
            }
            
            // Lock widget if deadline passed
            if (this.isLocked) {
                this.lockWidget();
            }
            
            // Update button states (this will handle visibility animations)
            this.updateButtonStates();
        } catch (error) {
            console.error('Failed to fetch participation status:', error, {
                eventId: this.eventId,
                eventType: this.eventType
            });
            // Keep default state (undecided)
            // Show error state on widget
            if (this.container) {
                this.container.classList.add('opacity-50');
                this.container.title = 'Failed to load participation status';
            }
        }
    }
    
    /**
     * Update button visual states based on current status
     */
    updateButtonStates() {
        const buttons = this.container.querySelectorAll('.participation-btn');
        const buttonsContainer = this.container.querySelector('.flex.items-center');
        const isUndecided = this.currentStatus === 'undecided';
        
        buttons.forEach(button => {
            const status = button.getAttribute('data-status');
            const isActive = status === this.currentStatus;
            
            // Remove all state classes
            button.classList.remove(
                'bg-green-500', 'border-green-500', 'text-white',
                'bg-amber-400', 'border-amber-400',
                'bg-red-500', 'border-red-500',
                'bg-green-50', 'border-green-200', 'text-green-600',
                'bg-amber-50', 'border-amber-200', 'text-amber-600',
                'bg-red-50', 'border-red-200', 'text-red-600',
                'border-gray-300', 'text-gray-400', 'bg-transparent',
                'opacity-0', 'scale-0', 'pointer-events-none',
                'opacity-100', 'scale-100'
            );
            
            // Handle visibility based on status
            if (this.isLocked) {
                // Locked state: show only active button (if any), gray outline + gray icon
                if (isActive) {
                    button.classList.remove('opacity-0', 'scale-0', 'pointer-events-none', 'hidden', 'w-0', 'overflow-hidden');
                    button.classList.add('opacity-100', 'scale-100');
                    button.style.display = 'flex';
                    button.style.width = '';
                    button.disabled = true;
                    button.classList.add('border-gray-300', 'text-gray-400', 'bg-transparent');
                } else {
                    // Hide inactive buttons when locked - remove from layout
                    button.classList.add('opacity-0', 'scale-0', 'pointer-events-none', 'hidden', 'w-0', 'overflow-hidden');
                    button.style.display = 'none';
                    button.style.width = '0';
                    button.disabled = true;
                }
            } else if (isUndecided) {
                // Undecided: show all buttons with subtle colors
                button.classList.remove('opacity-0', 'scale-0', 'pointer-events-none', 'hidden', 'w-0', 'overflow-hidden');
                button.classList.add('opacity-100', 'scale-100');
                button.style.display = 'flex';
                button.style.width = '';
                button.disabled = false;
                
                // Inactive state: subtle hint at color (light background + colored border/text)
                if (status === 'yes') {
                    button.classList.add('bg-green-50', 'border-green-200', 'text-green-600');
                } else if (status === 'maybe') {
                    button.classList.add('bg-amber-50', 'border-amber-200', 'text-amber-600');
                } else if (status === 'no') {
                    button.classList.add('bg-red-50', 'border-red-200', 'text-red-600');
                }
            } else {
                // Status is selected: show only active button, hide others
                if (isActive) {
                    button.classList.remove('opacity-0', 'scale-0', 'pointer-events-none', 'hidden', 'w-0', 'overflow-hidden');
                    button.classList.add('opacity-100', 'scale-100');
                    button.style.display = 'flex';
                    button.style.width = '';
                    button.disabled = false;
                    
                    // Active state: filled color + white icon
                    if (status === 'yes') {
                        button.classList.add('bg-green-500', 'border-green-500', 'text-white');
                    } else if (status === 'maybe') {
                        button.classList.add('bg-amber-400', 'border-amber-400', 'text-white');
                    } else if (status === 'no') {
                        button.classList.add('bg-red-500', 'border-red-500', 'text-white');
                    }
                } else {
                    // Hide inactive buttons when a status is selected - remove from layout
                    button.classList.add('opacity-0', 'scale-0', 'pointer-events-none', 'hidden', 'w-0', 'overflow-hidden');
                    button.style.display = 'none';
                    button.style.width = '0';
                    button.disabled = false; // Keep enabled for potential future use
                }
            }
        });
        
        // Container is always right-aligned, no need to change alignment
    }
    
    /**
     * Lock widget when deadline has passed
     */
    lockWidget() {
        const buttons = this.container.querySelectorAll('.participation-btn');
        buttons.forEach(button => {
            button.disabled = true;
            button.classList.add('cursor-not-allowed');
            button.classList.remove('hover:scale-110', 'hover:shadow-md');
        });
    }
    
    /**
     * Attach event listeners to buttons
     */
    attachEventListeners() {
        if (!this.container) {
            console.error('ParticipationWidget: Cannot attach listeners - container is null');
            return;
        }
        
        const buttons = this.container.querySelectorAll('.participation-btn');
        
        if (buttons.length === 0) {
            console.warn('ParticipationWidget: No buttons found to attach listeners', {
                eventId: this.eventId,
                eventType: this.eventType,
                container: this.container
            });
            return;
        }
        
        buttons.forEach(button => {
            button.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                
                if (this.isLoading) {
                    console.log('ParticipationWidget: Ignoring click - widget is loading');
                    return;
                }
                
                const status = button.getAttribute('data-status');
                if (!status) {
                    console.warn('ParticipationWidget: Button missing data-status attribute', button);
                    return;
                }
                
                this.handleButtonClick(status);
            });
        });
    }
    
    /**
     * Handle button click
     */
    handleButtonClick(status) {
        // Don't allow clicks if locked
        if (this.isLocked) {
            return;
        }
        
        // If clicking the same button (filled bubble), reset to undecided with animation
        if (status === this.currentStatus && this.currentStatus !== 'undecided') {
            this.resetToUndecided();
            return;
        }
        
        // Yes: immediate update, no modal
        if (status === 'yes') {
            this.updateStatus('yes', '');
        } else {
            // Maybe/No: show modal for reason
            this.showModal(status);
        }
    }
    
    /**
     * Reset to undecided state with animation
     */
    resetToUndecided() {
        // Update status to undecided
        this.updateStatus('undecided', '');
    }
    
    /**
     * Show modal for reason input
     */
    showModal(status) {
        const modal = document.getElementById('participation-modal');
        const statusLabel = document.getElementById('modal-status-label');
        const reasonTextarea = document.getElementById('participation-reason');
        const cancelBtn = document.getElementById('modal-cancel');
        const confirmBtn = document.getElementById('modal-confirm');
        
        if (!modal) {
            console.error('Participation modal not found');
            return;
        }
        
        // Set status label
        const statusLabels = {
            'maybe': 'Maybe',
            'no': 'No'
        };
        if (statusLabel) {
            statusLabel.textContent = statusLabels[status] || status;
        }
        
        // Clear textarea
        if (reasonTextarea) {
            reasonTextarea.value = '';
            if (status === 'no') {
                reasonTextarea.placeholder = 'Reason (suggested)...';
            } else {
                reasonTextarea.placeholder = 'Optional reason...';
            }
        }
        
        // Update confirm button color based on status
        if (confirmBtn) {
            confirmBtn.classList.remove('bg-amber-400', 'bg-red-500', 'hover:bg-amber-500', 'hover:bg-red-600');
            if (status === 'maybe') {
                confirmBtn.classList.add('bg-amber-400', 'hover:bg-amber-500');
            } else {
                confirmBtn.classList.add('bg-red-500', 'hover:bg-red-600');
            }
        }
        
        // Store pending status
        this.pendingStatus = status;
        
        // Remove any existing handlers
        if (modal._cancelHandler) {
            cancelBtn?.removeEventListener('click', modal._cancelHandler);
        }
        if (modal._confirmHandler) {
            confirmBtn?.removeEventListener('click', modal._confirmHandler);
        }
        if (modal._backdropHandler) {
            modal.removeEventListener('click', modal._backdropHandler);
        }
        
        // Create new handlers
        modal._cancelHandler = () => this.hideModal();
        modal._confirmHandler = () => {
            const reason = reasonTextarea ? reasonTextarea.value.trim() : '';
            this.updateStatus(status, reason);
            this.hideModal();
        };
        modal._backdropHandler = (e) => {
            if (e.target === modal) {
                this.hideModal();
            }
        };
        
        // Attach event listeners
        cancelBtn?.addEventListener('click', modal._cancelHandler);
        confirmBtn?.addEventListener('click', modal._confirmHandler);
        modal.addEventListener('click', modal._backdropHandler);
        
        // Show modal
        modal.classList.remove('hidden');
        
        // Focus textarea
        if (reasonTextarea) {
            setTimeout(() => reasonTextarea.focus(), 100);
        }
    }
    
    /**
     * Hide modal
     */
    hideModal() {
        const modal = document.getElementById('participation-modal');
        const cancelBtn = document.getElementById('modal-cancel');
        const confirmBtn = document.getElementById('modal-confirm');
        
        if (modal) {
            modal.classList.add('hidden');
            
            // Clean up event listeners
            if (modal._cancelHandler && cancelBtn) {
                cancelBtn.removeEventListener('click', modal._cancelHandler);
                delete modal._cancelHandler;
            }
            if (modal._confirmHandler && confirmBtn) {
                confirmBtn.removeEventListener('click', modal._confirmHandler);
                delete modal._confirmHandler;
            }
            if (modal._backdropHandler) {
                modal.removeEventListener('click', modal._backdropHandler);
                delete modal._backdropHandler;
            }
        }
        
        this.pendingStatus = null;
        this.pendingReason = null;
    }
    
    /**
     * Update participation status via API
     */
    async updateStatus(status, reason = '') {
        if (this.isLoading) return;
        
        this.isLoading = true;
        this.setButtonsDisabled(true);
        
        try {
            // Only call API if not resetting to undecided (to avoid unnecessary API call)
            // Actually, we should still call API to persist the change
            await ParticipationApi.saveStatus(this.eventId, this.eventType, status, reason);
            
            // Update current status
            this.currentStatus = status;
            
            // Update button states with animation
            this.updateButtonStates();
            
            // Refresh dashboard events sections to update all instances of this event
            if (typeof Dashboard !== 'undefined' && Dashboard.refreshEventsSections) {
                // Use setTimeout to allow current widget update to complete first
                setTimeout(() => {
                    Dashboard.refreshEventsSections();
                }, 100);
            }
            
            // Show success toast
            if (typeof Dashboard !== 'undefined' && Dashboard.showToast) {
                const messages = {
                    'yes': 'Participation confirmed',
                    'maybe': 'Maybe status saved',
                    'no': 'Non-participation saved',
                    'undecided': 'Participation cleared'
                };
                Dashboard.showToast(messages[status] || 'Status updated', 'success');
            }
        } catch (error) {
            console.error('Failed to update participation:', error);
            
            // Show error toast
            if (typeof Dashboard !== 'undefined' && Dashboard.showToast) {
                Dashboard.showToast('Failed to update participation', 'error');
            }
        } finally {
            this.isLoading = false;
            this.setButtonsDisabled(false);
        }
    }
    
    /**
     * Set buttons disabled state
     */
    setButtonsDisabled(disabled) {
        const buttons = this.container.querySelectorAll('.participation-btn');
        buttons.forEach(button => {
            button.disabled = disabled;
        });
    }
}

// Initialize widgets on page load (only if not already initialized)
document.addEventListener('DOMContentLoaded', () => {
    const widgets = document.querySelectorAll('[data-participation-widget]:not([data-initialized])');
    widgets.forEach(element => {
        const eventId = element.getAttribute('data-event-id');
        const eventType = element.getAttribute('data-event-type');
        
        if (eventId && eventType && !element.hasAttribute('data-initialized')) {
            element.setAttribute('data-initialized', 'true');
            new ParticipationWidget(element, eventId, eventType);
        }
    });
});
