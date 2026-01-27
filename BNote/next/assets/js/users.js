/**
 * User Management Module
 * Handles user CRUD operations, privilege management, and GDPR functionality
 */

const Users = {
    // State
    users: [],
    contacts: [],
    modules: [],
    session: null,
    isSuperUser: false,
    table: null,
    editingUserId: null,
    selectedUserId: null,
    addUserForm: null,
    editUserForm: null,
    privilegesForm: null,

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
        await this.loadContacts();
        await this.loadUsers();

        // Initialize UI
        this.initEventListeners();
    },

    /**
     * Check if user has permission to access user management
     */
    async checkPermissions() {
        try {
            // Try to list users - if we get 403, no permission
            await UsersApi.list();
            return true;
        } catch (error) {
            // Check for 403 status in various ways
            const is403 = error.status === 403 ||
                error.code === 403 ||
                error.message.includes('403') ||
                error.message.includes('Access denied') ||
                (error.message && error.message.includes('forbidden'));

            if (is403) {
                this.showToast('You do not have permission to access User Management', 'error');
                return false;
            }
            throw error;
        }
    },

    /**
     * Load users from API
     */
    async loadUsers() {
        try {
            this.showLoading();
            const users = await UsersApi.list();
            this.users = users;
            this.renderUserTable();
            this.hideLoading();
        } catch (error) {
            console.error('Failed to load users:', error);
            this.showToast('Failed to load users: ' + error.message, 'error');
            this.hideLoading();
        }
    },

    /**
     * Load contacts for dropdown
     */
    async loadContacts() {
        try {
            this.contacts = await UsersApi.getContacts();
        } catch (error) {
            console.error('Failed to load contacts:', error);
        }
    },

    /**
     * Render user table
     */
    renderUserTable() {
        const container = document.getElementById('users-table-container');
        if (!container) return;

        // Define columns
        const columns = [
            {
                key: 'id',
                label: 'ID',
                sortable: true
            },
            {
                key: 'login',
                label: 'Login',
                sortable: true,
                editable: false // Login should not be changed after creation
            },
            {
                key: 'firstName',
                label: 'First Name',
                sortable: true
            },
            {
                key: 'lastName',
                label: 'Last Name',
                sortable: true
            },
            {
                key: 'isActive',
                label: 'Status',
                sortable: true,
                render: (value) => {
                    return Badge.renderStatus(value);
                }
            },
            {
                key: 'lastlogin',
                label: 'Last Login',
                sortable: true,
                type: 'date',
                render: (value) => {
                    if (!value) return '-';
                    const date = new Date(value);
                    return date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                }
            }
        ];

        // Create table instance
        window.Users = this; // Make available globally for table actions
        this.table = new Table('users-table-container', {
            columns,
            data: this.users,
            defaultSort: 'id',
            defaultSortDirection: 'asc',
            searchable: true,
            searchInputContainer: 'users-search-container',
            onRowClick: (row) => {
                this.showEditUserModal(row.id);
            },
            onEdit: (row) => {
                this.showEditUserModal(row.id);
            },
            onDelete: (row) => {
                this.handleDeleteUser(row.id);
            },
            onAction: (row) => {
                const actions = [
                    {
                        key: 'activate',
                        icon: row.isActive ? 'x-circle' : 'check-circle',
                        title: row.isActive ? 'Deactivate' : 'Activate',
                        class: 'text-muted-foreground hover:text-foreground',
                        onClick: () => this.handleActivateUser(row.id)
                    },
                    {
                        key: 'privileges',
                        icon: 'key',
                        title: 'Manage Privileges',
                        class: 'text-muted-foreground hover:text-foreground',
                        onClick: () => this.showPrivilegesModal(row.id)
                    }
                ];
                // Store row for action handlers
                actions.forEach(action => {
                    action.row = row;
                });
                return actions;
            },
            inlineEdit: true,
            emptyMessage: 'No users found'
        });
    },

    /**
     * Initialize event listeners
     */
    initEventListeners() {
        // Add User button
        const addBtn = document.getElementById('add-user-btn');
        if (addBtn) {
            addBtn.addEventListener('click', () => this.showAddUserModal());
        }

        // GDPR button (if super user)
        const gdprBtn = document.getElementById('gdpr-btn');
        if (gdprBtn) {
            gdprBtn.addEventListener('click', () => this.showGDPRModal());
        }

        // Modal close buttons
        document.querySelectorAll('[data-modal-close]').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const modalId = btn.getAttribute('data-modal-close');
                this.closeModal(modalId);
            });
        });

        // Forms are now handled by Form component, no need for manual event listeners
    },

    /**
     * Show add user modal
     */
    showAddUserModal() {
        const modal = document.getElementById('add-user-modal');
        if (!modal) return;

        const formContainer = document.getElementById('add-user-form-container');
        if (!formContainer) return;

        // Create or recreate form
        if (this.addUserForm) {
            // Reset form data
            this.addUserForm.data = {};
            this.addUserForm.errors = {};
        }

        this.addUserForm = new Form('add-user-form-container', {
            title: '',
            showTitle: false,
            fields: [
                {
                    key: 'login',
                    label: 'Login',
                    type: 'text',
                    required: true,
                    placeholder: 'Enter username'
                },
                {
                    key: 'password',
                    label: 'Password',
                    type: 'password',
                    required: true,
                    placeholder: 'Enter password'
                },
                {
                    key: 'contact',
                    label: 'Contact',
                    type: 'select',
                    required: false,
                    emptyLabel: 'No contact',
                    options: this.contacts.map(c => ({
                        value: c.id,
                        label: c.label
                    }))
                },
                {
                    key: 'isActive',
                    label: 'Active',
                    type: 'checkbox',
                    value: true
                }
            ],
            submitLabel: 'Create',
            cancelLabel: 'Cancel',
            onSubmit: async (data) => {
                await this.handleAddUser(data);
            },
            onCancel: () => {
                this.closeModal('add-user-modal');
            }
        });

        modal.classList.remove('hidden');
    },

    /**
     * Show edit user modal
     */
    async showEditUserModal(userId) {
        const modal = document.getElementById('edit-user-modal');
        if (!modal) return;

        const formContainer = document.getElementById('edit-user-form-container');
        if (!formContainer) return;

        try {
            const user = await UsersApi.get(userId);
            this.editingUserId = userId;

            // Create or recreate form with user data
            this.editUserForm = new Form('edit-user-form-container', {
                title: '',
                showTitle: false,
                fields: [
                    {
                        key: 'login',
                        label: 'Login',
                        type: 'text',
                        required: true,
                        value: user.login || '',
                        disabled: true,
                        className: 'bg-muted cursor-not-allowed'
                    },
                    {
                        key: 'password',
                        label: 'New Password',
                        type: 'password',
                        required: false,
                        placeholder: 'Leave empty to keep current password',
                        help: 'Leave empty to keep current password',
                        value: '' // Always start empty for edit form
                    },
                    {
                        key: 'contact',
                        label: 'Contact',
                        type: 'select',
                        required: false,
                        emptyLabel: 'No contact',
                        value: user.contact || '0',
                        options: this.contacts.map(c => ({
                            value: c.id,
                            label: c.label
                        }))
                    },
                    {
                        key: 'isActive',
                        label: 'Active',
                        type: 'checkbox',
                        value: user.isActive || false
                    }
                ],
                data: {
                    login: user.login || '',
                    password: '',
                    contact: user.contact || '0',
                    isActive: user.isActive || false
                },
                submitLabel: 'Save',
                cancelLabel: 'Cancel',
                onSubmit: async (data) => {
                    await this.handleUpdateUser(data);
                },
                onCancel: () => {
                    this.closeModal('edit-user-modal');
                }
            });

            modal.classList.remove('hidden');
        } catch (error) {
            console.error('Failed to load user:', error);
            this.showToast('Failed to load user: ' + error.message, 'error');
        }
    },

    /**
     * Show user details
     */
    async showUserDetails(userId) {
        const modal = document.getElementById('user-details-modal');
        if (!modal) return;

        try {
            const user = await UsersApi.get(userId);
            this.selectedUserId = userId;

            // Populate details
            const container = document.getElementById('user-details-content');
            if (container) {
                container.innerHTML = `
                    <div class="space-y-4">
                        <div>
                            <label class="text-xs font-semibold text-muted-foreground uppercase">Login</label>
                            <p class="text-sm text-foreground mt-1">${this.escapeHtml(user.login || '-')}</p>
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-muted-foreground uppercase">Name</label>
                            <p class="text-sm text-foreground mt-1">${this.escapeHtml(user.contactName || '-')}</p>
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-muted-foreground uppercase">Status</label>
                            <p class="text-sm text-foreground mt-1">
                                ${user.isActive
                        ? '<span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-accent/10 text-accent">Active</span>'
                        : '<span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-muted text-muted-foreground">Inactive</span>'}
                            </p>
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-muted-foreground uppercase">Last Login</label>
                            <p class="text-sm text-foreground mt-1">${user.lastlogin ? new Date(user.lastlogin).toLocaleString() : '-'}</p>
                        </div>
                    </div>
                `;
            }

            // Update action buttons
            const activateBtn = document.getElementById('user-details-activate-btn');
            if (activateBtn) {
                activateBtn.textContent = user.isActive ? 'Deactivate' : 'Activate';
                activateBtn.onclick = () => {
                    this.handleActivateUser(userId);
                    this.closeModal('user-details-modal');
                };
            }

            modal.classList.remove('hidden');
        } catch (error) {
            console.error('Failed to load user details:', error);
            this.showToast('Failed to load user details: ' + error.message, 'error');
        }
    },

    /**
     * Show privileges modal
     */
    async showPrivilegesModal(userId) {
        const modal = document.getElementById('privileges-modal');
        if (!modal) return;

        const formContainer = document.getElementById('privileges-form-container');
        if (!formContainer) return;

        try {
            const data = await UsersApi.getPrivileges(userId);
            this.editingUserId = userId;

            // Get selected module IDs
            const selectedModules = data.modules
                .filter(m => m.hasAccess)
                .map(m => m.id.toString());

            // Create form with privilege checkboxes
            this.privilegesForm = new Form('privileges-form-container', {
                title: '',
                showTitle: false,
                fields: [
                {
                    key: 'modules',
                    label: 'Module Access',
                    type: 'privilege-checkbox',
                    required: false,
                    options: data.modules.map(module => ({
                        value: module.id.toString(),
                        label: module.name,
                        checked: module.hasAccess
                    }))
                }
                ],
                data: {
                    modules: selectedModules
                },
                layout: 'vertical',
                submitLabel: 'Save',
                cancelLabel: 'Cancel',
                onSubmit: async (formData) => {
                    await this.handleUpdatePrivileges(formData);
                },
                onCancel: () => {
                    this.closeModal('privileges-modal');
                }
            });

            modal.classList.remove('hidden');
        } catch (error) {
            console.error('Failed to load privileges:', error);
            this.showToast('Failed to load privileges: ' + error.message, 'error');
        }
    },

    /**
     * Show GDPR modal
     */
    async showGDPRModal() {
        const modal = document.getElementById('gdpr-modal');
        if (!modal) return;

        try {
            const users = await UsersApi.getLongInactiveUsers();

            const container = document.getElementById('gdpr-users-list');
            if (container) {
                if (users.length === 0) {
                    container.innerHTML = '<p class="text-sm text-muted-foreground">No inactive users found.</p>';
                } else {
                    container.innerHTML = users.map(user => `
                        <label class="flex items-center gap-3 p-3 rounded-lg border border-border/40 hover:bg-muted/30 cursor-pointer">
                            <input type="checkbox" name="gdpr_user_${user.id}" value="${user.id}" class="rounded border-border">
                            <div class="flex-1">
                                <span class="text-sm font-medium text-foreground">${this.escapeHtml(user.login)}</span>
                                <p class="text-xs text-muted-foreground">Last login: ${user.lastlogin ? new Date(user.lastlogin).toLocaleDateString() : 'Never'}</p>
                            </div>
                        </label>
                    `).join('');
                }
            }

            modal.classList.remove('hidden');
        } catch (error) {
            console.error('Failed to load inactive users:', error);
            this.showToast('Failed to load inactive users: ' + error.message, 'error');
        }
    },

    // Removed populateContactDropdown - now handled by Form component

    /**
     * Handle add user
     */
    async handleAddUser(data) {
        try {
            const submitData = {
                login: data.login,
                password: data.password,
                contact: data.contact || '0',
                isActive: data.isActive || false
            };

            await UsersApi.create(submitData);
            this.showToast('User created successfully', 'success');
            this.closeModal('add-user-modal');
            await this.loadUsers();
        } catch (error) {
            console.error('Failed to create user:', error);
            this.showToast('Failed to create user: ' + error.message, 'error');
            throw error; // Re-throw so form can handle it
        }
    },

    /**
     * Handle update user
     */
    async handleUpdateUser(data) {
        if (!this.editingUserId) return;

        try {
            const submitData = {
                contact: data.contact || '0',
                isActive: data.isActive || false
            };

            // Only include password if provided and not empty
            // Empty string means "keep current password"
            if (data.password !== undefined && data.password !== null && data.password.trim() !== '') {
                submitData.password = data.password.trim();
            }
            // If password is empty/undefined, don't include it in the request at all

            await UsersApi.update(this.editingUserId, submitData);
            this.showToast('User updated successfully', 'success');
            this.closeModal('edit-user-modal');
            this.editingUserId = null;
            await this.loadUsers();
        } catch (error) {
            console.error('Failed to update user:', error);
            this.showToast('Failed to update user: ' + error.message, 'error');
            throw error; // Re-throw so form can handle it
        }
    },

    /**
     * Handle delete user
     */
    async handleDeleteUser(userId) {
        if (!confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
            return;
        }

        try {
            await UsersApi.delete(userId);
            this.showToast('User deleted successfully', 'success');
            await this.loadUsers();
        } catch (error) {
            console.error('Failed to delete user:', error);
            this.showToast('Failed to delete user: ' + error.message, 'error');
        }
    },

    /**
     * Handle activate/deactivate user
     */
    async handleActivateUser(userId) {
        try {
            const result = await UsersApi.activate(userId);
            this.showToast(result.message, 'success');
            await this.loadUsers();
        } catch (error) {
            console.error('Failed to activate/deactivate user:', error);
            this.showToast('Failed to update user status: ' + error.message, 'error');
        }
    },

    /**
     * Handle update privileges
     */
    async handleUpdatePrivileges(formData) {
        if (!this.editingUserId) return;

        try {
            // Get selected module IDs from form data
            const selectedModules = formData.modules || [];
            const privileges = selectedModules.map(id => parseInt(id));

            await UsersApi.updatePrivileges(this.editingUserId, privileges);
            this.showToast('Privileges updated successfully', 'success');
            this.closeModal('privileges-modal');
            this.editingUserId = null;
        } catch (error) {
            console.error('Failed to update privileges:', error);
            this.showToast('Failed to update privileges: ' + error.message, 'error');
            throw error; // Re-throw so form can handle it
        }
    },

    /**
     * Handle GDPR delete
     */
    async handleGDPRDelete() {
        const form = document.getElementById('gdpr-form');
        if (!form) return;

        const formData = new FormData(form);
        const userIds = [];

        formData.forEach((value, key) => {
            if (key.startsWith('gdpr_user_')) {
                userIds.push(parseInt(value));
            }
        });

        if (userIds.length === 0) {
            this.showToast('Please select at least one user to delete', 'error');
            return;
        }

        if (!confirm(`Are you sure you want to permanently delete ${userIds.length} user(s)? This will delete all associated data and cannot be undone.`)) {
            return;
        }

        try {
            await UsersApi.deleteUsersFull(userIds);
            this.showToast(`${userIds.length} user(s) deleted successfully`, 'success');
            this.closeModal('gdpr-modal');
            await this.loadUsers();
        } catch (error) {
            console.error('Failed to delete users:', error);
            this.showToast('Failed to delete users: ' + error.message, 'error');
        }
    },

    /**
     * Inline edit field
     */
    async inlineEditField(userId, field, value) {
        try {
            const data = {};
            data[field] = value;
            await UsersApi.update(userId, data);
            this.showToast('Field updated successfully', 'success');
            await this.loadUsers();
        } catch (error) {
            console.error('Failed to update field:', error);
            this.showToast('Failed to update field: ' + error.message, 'error');
            throw error; // Re-throw so table can handle it
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
     * Show loading state
     */
    showLoading() {
        const container = document.getElementById('users-table-container');
        if (container && this.table) {
            this.table.setLoading(true);
        }
    },

    /**
     * Hide loading state
     */
    hideLoading() {
        const container = document.getElementById('users-table-container');
        if (container && this.table) {
            this.table.setLoading(false);
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
     * Escape HTML
     */
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
};
