/**
 * Event Metadata Component
 * Displays concert-specific metadata fields
 */
const EventMetadata = {
    /**
     * Render event metadata
     * @param {HTMLElement} container - Container element to render into
     * @param {Object} eventData - Event data (concert-specific fields)
     */
    render(container, eventData) {
        if (!container) return;
        
        // Only render for concerts
        if (eventData.type !== 'C') {
            container.innerHTML = '';
            return;
        }
        
        const sections = [];
        
        // Organization section
        const orgFields = [];
        if (eventData.groups && eventData.groups.length > 0) {
            const groupNames = eventData.groups.map(g => g.name).join(', ');
            orgFields.push(this.renderField('Besetzung', groupNames));
        }
        if (eventData.program) {
            orgFields.push(this.renderField('Programm', eventData.program.name));
        }
        if (eventData.outfit) {
            orgFields.push(this.renderField('Outfit', eventData.outfit.name));
        }
        if (eventData.equipment && eventData.equipment.length > 0) {
            const equipmentList = eventData.equipment.map(eq => eq.name).join(', ');
            orgFields.push(this.renderField('Equipment', equipmentList));
        }
        
        if (orgFields.length > 0) {
            sections.push(`
                <div class="metadata-section">
                    <h3 class="text-sm font-semibold text-foreground mb-3">Organisation</h3>
                    <div class="space-y-2">
                        ${orgFields.join('')}
                    </div>
                </div>
            `);
        }
        
        // Details section
        const detailFields = [];
        if (eventData.accommodation) {
            const accAddress = eventData.accommodation.address;
            const accAddressStr = [
                eventData.accommodation.name,
                accAddress.street,
                accAddress.zip && accAddress.city ? `${accAddress.zip} ${accAddress.city}` : accAddress.city
            ].filter(Boolean).join(', ');
            detailFields.push(this.renderField('Unterkunft', accAddressStr));
        }
        if (eventData.payment !== null && eventData.payment !== undefined) {
            const formattedPayment = this.formatCurrency(eventData.payment);
            detailFields.push(this.renderField('Gage', formattedPayment));
        }
        if (eventData.conditions) {
            detailFields.push(this.renderField('Konditionen', eventData.conditions));
        }
        if (eventData.meetingtime) {
            const formattedTime = this.formatDateTime(eventData.meetingtime);
            detailFields.push(this.renderField('Treffpunkt', formattedTime));
        }
        if (eventData.contact) {
            const contactInfo = [
                eventData.contact.name,
                eventData.contact.phone,
                eventData.contact.mobile,
                eventData.contact.email
            ].filter(Boolean).join(' | ');
            detailFields.push(this.renderField('Kontakt', contactInfo));
        }
        
        if (detailFields.length > 0) {
            sections.push(`
                <div class="metadata-section">
                    <h3 class="text-sm font-semibold text-foreground mb-3">Details</h3>
                    <div class="space-y-2">
                        ${detailFields.join('')}
                    </div>
                </div>
            `);
        }
        
        if (sections.length === 0) {
            container.innerHTML = '';
            return;
        }
        
        container.innerHTML = `
            <div class="event-metadata space-y-6">
                ${sections.join('')}
            </div>
        `;
    },
    
    /**
     * Render a metadata field
     */
    renderField(label, value) {
        if (!value || value === '' || value === null) return '';
        
        return `
            <div class="metadata-field">
                <span class="metadata-label text-xs font-medium text-muted-foreground">${this.escapeHtml(label)}:</span>
                <span class="text-sm text-foreground ml-2">${this.escapeHtml(value)}</span>
            </div>
        `;
    },
    
    /**
     * Format currency
     */
    formatCurrency(amount) {
        if (amount === null || amount === undefined) return '';
        return new Intl.NumberFormat('de-DE', {
            style: 'currency',
            currency: 'EUR'
        }).format(amount);
    },
    
    /**
     * Format date and time
     */
    formatDateTime(dateTimeStr) {
        if (!dateTimeStr) return '';
        try {
            const date = new Date(dateTimeStr);
            return date.toLocaleString('de-DE', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        } catch (e) {
            return dateTimeStr;
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
