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
        
        const t = (k) => (typeof i18n !== 'undefined' && i18n.t ? i18n.t(k) : k);
        const sections = [];
        
        const orgFields = [];
        if (eventData.groups && eventData.groups.length > 0) {
            const groupNames = eventData.groups.map(g => g.name).join(', ');
            orgFields.push(this.renderField(t('js.event.metadata.besetzung'), groupNames));
        }
        if (eventData.program) {
            orgFields.push(this.renderField(t('js.event.metadata.programm'), eventData.program.name));
        }
        if (eventData.outfit) {
            orgFields.push(this.renderField(t('js.event.metadata.outfit'), eventData.outfit.name));
        }
        if (eventData.equipment && eventData.equipment.length > 0) {
            const equipmentList = eventData.equipment.map(eq => eq.name).join(', ');
            orgFields.push(this.renderField(t('js.event.metadata.equipment'), equipmentList));
        }
        
        if (orgFields.length > 0) {
            sections.push(`
                <div class="metadata-section">
                    <h3 class="text-sm font-semibold text-foreground mb-3">${t('js.event.metadata.organisation')}</h3>
                    <div class="space-y-2">
                        ${orgFields.join('')}
                    </div>
                </div>
            `);
        }
        
        const detailFields = [];
        if (eventData.accommodation) {
            const acc = eventData.accommodation;
            const addr = (typeof i18n !== 'undefined' && i18n.formatAddress)
                ? i18n.formatAddress(acc.address || {})
                : [acc.address?.street, acc.address?.zip, acc.address?.city].filter(Boolean).join(', ');
            const accLines = [acc.name, addr].filter(Boolean).join('\n');
            detailFields.push(this.renderField(t('js.event.metadata.unterkunft'), accLines));
        }
        if (eventData.payment !== null && eventData.payment !== undefined) {
            detailFields.push(this.renderField(t('js.event.metadata.gage'), this.formatCurrency(eventData.payment)));
        }
        if (eventData.conditions) {
            detailFields.push(this.renderField(t('js.event.metadata.konditionen'), eventData.conditions));
        }
        if (eventData.meetingtime) {
            detailFields.push(this.renderField(t('js.event.metadata.treffpunkt'), this.formatDateTime(eventData.meetingtime)));
        }
        if (eventData.contact) {
            const contactInfo = [
                eventData.contact.name,
                eventData.contact.phone,
                eventData.contact.mobile,
                eventData.contact.email
            ].filter(Boolean).join(' | ');
            detailFields.push(this.renderField(t('js.event.metadata.kontakt'), contactInfo));
        }
        
        if (detailFields.length > 0) {
            sections.push(`
                <div class="metadata-section">
                    <h3 class="text-sm font-semibold text-foreground mb-3">${t('js.event.metadata.details')}</h3>
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
        if (value === undefined || value === null || value === '') return '';
        const str = String(value);
        const encoded = str.includes('\n')
            ? str.split('\n').map(l => this.escapeHtml(l)).join('<br>')
            : this.escapeHtml(str);
        return `
            <div class="metadata-field">
                <span class="metadata-label text-xs font-medium text-muted-foreground">${this.escapeHtml(label)}:</span>
                <span class="text-sm text-foreground ml-2">${encoded}</span>
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
     * Format date and time (short date, time without seconds). Uses i18n.parseEventDate.
     */
    formatDateTime(dateTimeStr) {
        if (dateTimeStr == null || typeof dateTimeStr !== 'string') return '';
        const date = typeof i18n !== 'undefined' && i18n.parseEventDate ? i18n.parseEventDate(dateTimeStr) : null;
        if (!date) return '';
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
     * Escape HTML to prevent XSS
     */
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
};
