/**
 * Contacts Management Module
 * Handles contact CRUD operations, group management, integration, printing, vcard, and GDPR
 */

const Contacts = {
    // State
    contacts: [],
    groups: [],
    instruments: [],
    selectedGroup: null,
    filters: {
        search: '',
        group: null,
        instrument: null,
        status: null,
        city: '',
        hasEmail: null
    },
    table: null,
    currentMode: 'list', // 'list', 'integration', 'groups', 'print', 'vcard', 'privacy'
    editingContactId: null,
    addContactForm: null,
    editContactForm: null,
    groupForm: null,
    integrationForm: null,
    printForm: null,
    vcardForm: null,

    /**
     * Initialize module
     */
    async init(session) {
        this.session = session;

        // Check permissions
        if (!await this.checkPermissions()) {
            window.location.href = 'dashboard.html';
            return;
        }

        // Load initial data
        await this.loadGroups();
        await this.loadContacts();

        // Initialize UI
        this.initEventListeners();
        this.renderContactList();
    },

    /**
     * Check if user has permission to access contacts
     */
    async checkPermissions() {
        try {
            await ContactsApi.list();
            return true;
        } catch (error) {
            const is403 = error.status === 403 ||
                error.code === 403 ||
                error.message.includes('403') ||
                error.message.includes('Access denied') ||
                (error.message && error.message.includes('forbidden'));

            if (is403) {
                const msg = typeof i18n !== 'undefined' && i18n.t ? i18n.t('js.error.contactsAccessDenied') : 'You do not have permission to access Contacts';
                this.showToast(msg, 'error');
                return false;
            }
            throw error;
        }
    },

    /**
     * Load contacts from API
     */
    async loadContacts(groupId = null) {
        try {
            this.showLoading();
            const contacts = await ContactsApi.list(groupId);
            this.contacts = contacts;
            this.renderContactTable();
            this.hideLoading();
        } catch (error) {
            console.error('Failed to load contacts:', error);
            this.showToast('Failed to load contacts: ' + error.message, 'error');
            this.hideLoading();
        }
    },

    /**
     * Load groups from API
     */
    async loadGroups() {
        try {
            this.groups = await ContactsApi.getGroups();
            // Add "All" option
            this.groups = [{ id: 'all', name: 'All' }, ...this.groups];
        } catch (error) {
            console.error('Failed to load groups:', error);
        }
    },

    /**
     * Load instruments (for filters and forms)
     */
    async loadInstruments() {
        // TODO: Add instruments API endpoint or get from dashboard
        // For now, we'll skip this and add it later if needed
        this.instruments = [];
    },

    /**
     * Initialize event listeners
     */
    initEventListeners() {
        // Add User button
        const addBtn = document.getElementById('add-contact-btn');
        if (addBtn) {
            addBtn.addEventListener('click', () => this.showAddContactModal());
        }

        // Mode buttons
        const integrationBtn = document.getElementById('integration-btn');
        if (integrationBtn) {
            integrationBtn.addEventListener('click', () => this.showIntegrationMode());
        }

        const groupsBtn = document.getElementById('groups-btn');
        if (groupsBtn) {
            groupsBtn.addEventListener('click', () => this.showGroupsMode());
        }

        const printBtn = document.getElementById('print-btn');
        if (printBtn) {
            printBtn.addEventListener('click', () => this.showPrintMode());
        }

        const vcardBtn = document.getElementById('vcard-btn');
        if (vcardBtn) {
            vcardBtn.addEventListener('click', () => this.showVCardMode());
        }

        const privacyBtn = document.getElementById('privacy-btn');
        if (privacyBtn) {
            privacyBtn.addEventListener('click', () => this.showPrivacyMode());
        }

        // Back to list button
        const backBtn = document.getElementById('back-to-list-btn');
        if (backBtn) {
            backBtn.addEventListener('click', () => this.showListMode());
        }
    },

    /**
     * Render contact list view
     */
    renderContactList() {
        this.currentMode = 'list';
        this.showMode('list');
        this.renderGroupTabs();
        this.renderContactTable();
    },

    /**
     * Render group tabs
     */
    renderGroupTabs() {
        const container = document.getElementById('group-tabs-container');
        if (!container) return;

        let html = '<div class="flex gap-2 overflow-x-auto pb-2">';
        for (const group of this.groups) {
            const isActive = this.selectedGroup === group.id || 
                           (this.selectedGroup === null && group.id === 'all');
            html += `
                <button 
                    class="px-4 py-2 rounded-lg font-medium transition-colors whitespace-nowrap ${
                        isActive 
                            ? 'bg-primary text-white' 
                            : 'bg-muted hover:bg-muted/80 text-foreground'
                    }"
                    data-group-id="${group.id}"
                >
                    ${this.escapeHtml(group.name)}
                </button>
            `;
        }
        html += '</div>';

        container.innerHTML = html;

        // Attach event listeners
        container.querySelectorAll('button[data-group-id]').forEach(btn => {
            btn.addEventListener('click', () => {
                const groupId = btn.getAttribute('data-group-id');
                this.selectGroup(groupId === 'all' ? null : groupId);
            });
        });
    },

    /**
     * Select group and reload contacts
     */
    async selectGroup(groupId) {
        this.selectedGroup = groupId;
        this.renderGroupTabs();
        await this.loadContacts(groupId);
    },

    /**
     * Render contact table
     */
    renderContactTable() {
        const container = document.getElementById('contacts-table-container');
        if (!container) return;

        // Define columns with i18n keys
        const t = (k) => (typeof i18n !== 'undefined' && i18n.t ? i18n.t(k) : k);
        const columns = [
            { key: 'id', label: t('js.table.id') || 'ID', i18n: 'js.table.id', sortable: true },
            { key: 'name', label: t('js.contacts.firstName') || 'First Name', i18n: 'js.contacts.firstName', sortable: true },
            { key: 'surname', label: t('js.contacts.lastName') || 'Last Name', i18n: 'js.contacts.lastName', sortable: true },
            { key: 'nickname', label: t('js.contacts.nickname') || 'Nickname', i18n: 'js.contacts.nickname', sortable: true },
            { key: 'instrumentname', label: t('js.contacts.instrument') || 'Instrument', i18n: 'js.contacts.instrument', sortable: true },
            { key: 'email', label: t('js.contacts.email') || 'Email', i18n: 'js.contacts.email', sortable: true },
            { key: 'phone', label: t('js.contacts.phone') || 'Phone', i18n: 'js.contacts.phone', sortable: true },
            { key: 'mobile', label: t('js.contacts.mobile') || 'Mobile', i18n: 'js.contacts.mobile', sortable: true },
            { key: 'city', label: t('js.contacts.city') || 'City', i18n: 'js.contacts.city', sortable: true },
            { 
                key: 'status', 
                label: t('js.contacts.status') || 'Status',
                i18n: 'js.contacts.status',
                sortable: true,
                render: (value) => value ? Badge.render(value, 'primary') : ''
            }
        ];

        // Define actions
        const actions = [
            {
                label: 'Edit',
                onClick: (row) => this.showEditContactModal(row.id)
            },
            {
                label: 'Delete',
                onClick: (row) => this.handleDeleteContact(row.id),
                className: 'text-destructive'
            }
        ];

        // Create table
        this.table = new Table(container.id, {
            columns,
            data: this.contacts,
            actions,
            searchable: true,
            searchInputContainer: 'contacts-search-container',
            defaultSort: 'id',
            defaultSortDirection: 'asc',
            onRowClick: (row) => this.showEditContactModal(row.id)
        });

        this.table.render();
    },

    /**
     * Show add contact modal
     */
    async showAddContactModal() {
        const modal = document.getElementById('add-contact-modal');
        if (!modal) return;

        // Get groups and instruments for form
        await this.loadGroups();
        await this.loadInstruments();

        const groupOptions = this.groups
            .filter(g => g.id !== 'all')
            .map(g => ({ value: g.id, label: g.name }));

        const instrumentOptions = this.instruments.map(i => ({
            value: i.id,
            label: i.name
        }));

        const fields = [
            { key: 'name', label: 'First Name', type: 'text', required: false },
            { key: 'surname', label: 'Last Name', type: 'text', required: false },
            { key: 'nickname', label: 'Nickname', type: 'text', required: false },
            { key: 'company', label: 'Company', type: 'text', required: false },
            { key: 'email', label: 'Email', type: 'email', required: false },
            { key: 'phone', label: 'Phone', type: 'text', required: false },
            { key: 'mobile', label: 'Mobile', type: 'text', required: false },
            { key: 'business', label: 'Business', type: 'text', required: false },
            { key: 'web', label: 'Website', type: 'text', required: false },
            { 
                key: 'instrument', 
                label: 'Instrument', 
                type: 'select', 
                required: false,
                options: [{ value: '', label: 'None' }, ...instrumentOptions]
            },
            { key: 'birthday', label: 'Birthday', type: 'text', required: false },
            { key: 'status', label: 'Status', type: 'text', required: false },
            { key: 'street', label: 'Street', type: 'text', required: false },
            { key: 'zip', label: 'ZIP', type: 'text', required: false },
            { key: 'city', label: 'City', type: 'text', required: false },
            { key: 'notes', label: 'Notes', type: 'textarea', required: false },
            {
                key: 'groups',
                label: (typeof i18n !== 'undefined' && i18n.t ? i18n.t('js.contacts.groups') : 'Groups'),
                type: 'privilege-checkbox',
                options: groupOptions
            }
        ];

        const t = (k) => (typeof i18n !== 'undefined' && i18n.t ? i18n.t(k) : k);
        this.addContactForm = new Form('add-contact-form-container', {
            fields,
            title: t('js.contacts.addContactModalTitle'),
            onSubmit: (data) => this.handleAddContact(data),
            onCancel: () => this.closeModal('add-contact-modal')
        });

        this.addContactForm.render();
        modal.classList.remove('hidden');
    },

    /**
     * Show edit contact modal
     */
    async showEditContactModal(contactId) {
        const modal = document.getElementById('edit-contact-modal');
        if (!modal) return;

        try {
            this.showLoading();
            const contact = await ContactsApi.get(contactId);
            this.hideLoading();

            await this.loadGroups();
            await this.loadInstruments();

            const groupOptions = this.groups
                .filter(g => g.id !== 'all')
                .map(g => ({ value: g.id, label: g.name }));

            const instrumentOptions = this.instruments.map(i => ({
                value: i.id,
                label: i.name
            }));

            const fields = [
                { key: 'name', label: 'First Name', type: 'text', required: false },
                { key: 'surname', label: 'Last Name', type: 'text', required: false },
                { key: 'nickname', label: 'Nickname', type: 'text', required: false },
                { key: 'company', label: 'Company', type: 'text', required: false },
                { key: 'email', label: 'Email', type: 'email', required: false },
                { key: 'phone', label: 'Phone', type: 'text', required: false },
                { key: 'mobile', label: 'Mobile', type: 'text', required: false },
                { key: 'business', label: 'Business', type: 'text', required: false },
                { key: 'web', label: 'Website', type: 'text', required: false },
                { 
                    key: 'instrument', 
                    label: 'Instrument', 
                    type: 'select', 
                    required: false,
                    options: [{ value: '', label: 'None' }, ...instrumentOptions]
                },
                { key: 'birthday', label: 'Birthday', type: 'text', required: false },
                { key: 'status', label: 'Status', type: 'text', required: false },
                { key: 'street', label: 'Street', type: 'text', required: false },
                { key: 'zip', label: 'ZIP', type: 'text', required: false },
                { key: 'city', label: 'City', type: 'text', required: false },
                { key: 'notes', label: 'Notes', type: 'textarea', required: false },
                {
                    key: 'groups',
                    label: (typeof i18n !== 'undefined' && i18n.t ? i18n.t('js.contacts.groups') : 'Groups'),
                    type: 'privilege-checkbox',
                    options: groupOptions
                }
            ];

            // Set initial data
            const initialData = {
                ...contact,
                groups: contact.groups || []
            };

            const t = (k) => (typeof i18n !== 'undefined' && i18n.t ? i18n.t(k) : k);
            this.editContactForm = new Form('edit-contact-form-container', {
                fields,
                title: t('js.contacts.editContactModalTitle'),
                data: initialData,
                onSubmit: (data) => this.handleUpdateContact(contactId, data),
                onCancel: () => this.closeModal('edit-contact-modal')
            });

            this.editContactForm.render();
            modal.classList.remove('hidden');
        } catch (error) {
            console.error('Failed to load contact:', error);
            this.showToast('Failed to load contact: ' + error.message, 'error');
            this.hideLoading();
        }
    },

    /**
     * Handle add contact
     */
    async handleAddContact(data) {
        try {
            this.addContactForm.setLoading(true);
            await ContactsApi.create(data);
            this.addContactForm.setLoading(false);
            this.closeModal('add-contact-modal');
            this.showToast('Contact created successfully', 'success');
            await this.loadContacts(this.selectedGroup);
        } catch (error) {
            console.error('Failed to create contact:', error);
            this.addContactForm.setLoading(false);
            this.showToast('Failed to create contact: ' + error.message, 'error');
        }
    },

    /**
     * Handle update contact
     */
    async handleUpdateContact(contactId, data) {
        try {
            this.editContactForm.setLoading(true);
            await ContactsApi.update(contactId, data);
            this.editContactForm.setLoading(false);
            this.closeModal('edit-contact-modal');
            this.showToast('Contact updated successfully', 'success');
            await this.loadContacts(this.selectedGroup);
        } catch (error) {
            console.error('Failed to update contact:', error);
            this.editContactForm.setLoading(false);
            this.showToast('Failed to update contact: ' + error.message, 'error');
        }
    },

    /**
     * Handle delete contact
     */
    async handleDeleteContact(contactId) {
        if (!confirm('Are you sure you want to delete this contact?')) {
            return;
        }

        try {
            await ContactsApi.delete(contactId);
            this.showToast('Contact deleted successfully', 'success');
            await this.loadContacts(this.selectedGroup);
        } catch (error) {
            console.error('Failed to delete contact:', error);
            this.showToast('Failed to delete contact: ' + error.message, 'error');
        }
    },

    /**
     * Show integration mode
     */
    showIntegrationMode() {
        this.currentMode = 'integration';
        this.showMode('integration');
        // TODO: Implement integration UI
        const msg = typeof i18n !== 'undefined' && i18n.t ? i18n.t('js.contacts.integrationComingSoon') : 'Integration mode - coming soon';
        this.showToast(msg, 'info');
    },

    /**
     * Show groups management mode
     */
    showGroupsMode() {
        this.currentMode = 'groups';
        this.showMode('groups');
        this.renderGroupsTable();
    },

    /**
     * Show print mode
     */
    showPrintMode() {
        this.currentMode = 'print';
        this.showMode('print');
        // TODO: Implement print UI
        const msg = typeof i18n !== 'undefined' && i18n.t ? i18n.t('js.contacts.printComingSoon') : 'Print mode - coming soon';
        this.showToast(msg, 'info');
    },

    /**
     * Show vCard mode
     */
    showVCardMode() {
        this.currentMode = 'vcard';
        this.showMode('vcard');
        // TODO: Implement vCard UI
        const msg = typeof i18n !== 'undefined' && i18n.t ? i18n.t('js.contacts.vcardComingSoon') : 'vCard mode - coming soon';
        this.showToast(msg, 'info');
    },

    /**
     * Show privacy/GDPR mode
     */
    showPrivacyMode() {
        this.currentMode = 'privacy';
        this.showMode('privacy');
        this.renderGdprTable();
    },

    /**
     * Show list mode
     */
    showListMode() {
        this.renderContactList();
    },

    /**
     * Show/hide mode sections
     */
    showMode(mode) {
        const sections = ['list', 'integration', 'groups', 'print', 'vcard', 'privacy'];
        sections.forEach(section => {
            const el = document.getElementById(`${section}-mode`);
            if (el) {
                el.classList.toggle('hidden', section !== mode);
            }
        });
    },

    /**
     * Render groups table
     */
    async renderGroupsTable() {
        const container = document.getElementById('groups-table-container');
        if (!container) return;

        try {
            const groups = await ContactsApi.listGroups();

            const columns = [
                { key: 'id', label: 'ID', sortable: true },
                { key: 'name', label: 'Name', sortable: true },
                { 
                    key: 'is_active', 
                    label: 'Active', 
                    sortable: true,
                    render: (value) => Badge.renderStatus(value)
                },
                { key: 'memberCount', label: 'Members', sortable: true }
            ];

            const actions = [
                {
                    label: 'View Members',
                    onClick: (row) => this.showGroupMembers(row.id)
                },
                {
                    label: 'Edit',
                    onClick: (row) => this.showEditGroupModal(row.id)
                },
                {
                    label: 'Delete',
                    onClick: (row) => this.handleDeleteGroup(row.id),
                    className: 'text-destructive'
                }
            ];

            const table = new Table(container.id, {
                columns,
                data: groups,
                actions,
                searchable: true,
                defaultSort: 'id',
                defaultSortDirection: 'asc'
            });

            table.render();
        } catch (error) {
            console.error('Failed to load groups:', error);
            this.showToast('Failed to load groups: ' + error.message, 'error');
        }
    },

    /**
     * Render GDPR table
     */
    async renderGdprTable() {
        const container = document.getElementById('gdpr-table-container');
        if (!container) return;

        try {
            const contacts = await ContactsApi.getGdprStatus();

            const t = (k) => (typeof i18n !== 'undefined' && i18n.t ? i18n.t(k) : k);
            const columns = [
                { key: 'id', label: t('js.table.id') || 'ID', i18n: 'js.table.id', sortable: true },
                { key: 'name', label: t('js.contacts.name') || 'Name', i18n: 'js.contacts.name', sortable: true },
                { key: 'email', label: t('js.contacts.email') || 'Email', i18n: 'js.contacts.email', sortable: true },
                { 
                    key: 'gdpr_ok', 
                    label: t('js.contacts.gdprOk') || 'GDPR OK',
                    i18n: 'js.contacts.gdprOk',
                    sortable: true,
                    render: (value) => Badge.renderStatus(value)
                }
            ];

            const table = new Table(container.id, {
                columns,
                data: contacts,
                searchable: true,
                defaultSort: 'id',
                defaultSortDirection: 'asc'
            });

            table.render();
        } catch (error) {
            console.error('Failed to load GDPR status:', error);
            this.showToast('Failed to load GDPR status: ' + error.message, 'error');
        }
    },

    /**
     * Close modal
     */
    closeModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('hidden');
        }
    },

    /**
     * Show loading indicator
     */
    showLoading() {
        const loader = document.getElementById('loading-indicator');
        if (loader) {
            loader.classList.remove('hidden');
        }
    },

    /**
     * Hide loading indicator
     */
    hideLoading() {
        const loader = document.getElementById('loading-indicator');
        if (loader) {
            loader.classList.add('hidden');
        }
    },

    /**
     * Show toast notification
     */
    showToast(message, type = 'info') {
        // Simple toast implementation
        const toast = document.createElement('div');
        toast.className = `fixed top-4 right-4 px-4 py-2 rounded-lg shadow-lg z-50 ${
            type === 'error' ? 'bg-destructive text-white' :
            type === 'success' ? 'bg-success text-white' :
            'bg-primary text-white'
        }`;
        toast.textContent = message;
        document.body.appendChild(toast);

        setTimeout(() => {
            toast.remove();
        }, 3000);
    },

    /**
     * Escape HTML
     */
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
};
