/**
 * BNote Next Generation - Users Module
 *
 * Copyright (C) 2026 BNote Contributors
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * Users Module
 * Extends ModuleBase - pure UI container
 */
class UsersModule extends ModuleBase {
    constructor() {
        super();
        this.route = 'users';
        this.users = null;
    }
    
    /**
     * Get module HTML template (embedded in JS to prevent flashing)
     */
    getTemplate() {
        return `
            <div id="users-container">
                <!-- Page title -->
                <div class="mb-6">
                    <h1 class="text-2xl font-semibold text-foreground" data-i18n="js.users.title">User Management</h1>
                    <p class="text-sm text-muted-foreground mt-1" data-i18n="js.users.subtitle">Manage users, permissions, and access</p>
                </div>

                <!-- Action buttons -->
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center gap-3">
                        <button id="add-user-btn"
                            class="flex items-center gap-2 px-4 py-2.5 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 transition-colors text-sm font-medium shadow-sm hover:shadow">
                            <i data-lucide="plus" class="h-4 w-4"></i>
                            <span data-i18n="js.users.addUser">Add User</span>
                        </button>
                        <button id="gdpr-btn"
                            class="flex items-center gap-2 px-4 py-2.5 border border-border rounded-lg hover:bg-muted/50 transition-colors text-foreground text-sm font-medium">
                            <i data-lucide="shield-alert" class="h-4 w-4"></i>
                            <span data-i18n="js.users.gdpr">GDPR</span>
                        </button>
                    </div>
                </div>

                <!-- Search input -->
                <div id="users-search-container" class="mb-4"></div>

                <!-- Users table -->
                <div class="bg-card rounded-xl border border-border/40 shadow-sm">
                    <div class="p-6">
                        <div id="users-table-container"></div>
                    </div>
                </div>
            </div>

            <!-- Toast Container -->
            <div id="toast-container" class="fixed top-4 right-4 z-[100] flex flex-col gap-2 max-w-[420px]"></div>

            <!-- Add User Modal -->
            <div id="add-user-modal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
                <div class="bg-card rounded-lg shadow-xl p-6 max-w-md w-full border border-border">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-xl font-semibold text-card-foreground" data-i18n="js.users.addUserModalTitle">Add User</h3>
                        <button data-modal-close="add-user-modal" class="text-muted-foreground hover:text-foreground">
                            <i data-lucide="x" class="h-5 w-5"></i>
                        </button>
                    </div>
                    <div id="add-user-form-container"></div>
                </div>
            </div>

            <!-- Edit User Modal -->
            <div id="edit-user-modal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
                <div class="bg-card rounded-lg shadow-xl p-6 max-w-md w-full border border-border">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-xl font-semibold text-card-foreground" data-i18n="js.users.editUserModalTitle">Edit User</h3>
                        <button data-modal-close="edit-user-modal" class="text-muted-foreground hover:text-foreground">
                            <i data-lucide="x" class="h-5 w-5"></i>
                        </button>
                    </div>
                    <div id="edit-user-form-container"></div>
                </div>
            </div>

            <!-- User Details Modal -->
            <div id="user-details-modal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
                <div class="bg-card rounded-lg shadow-xl p-6 max-w-md w-full border border-border">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-xl font-semibold text-card-foreground" data-i18n="js.users.userDetailsModalTitle">User Details</h3>
                        <button data-modal-close="user-details-modal" class="text-muted-foreground hover:text-foreground">
                            <i data-lucide="x" class="h-5 w-5"></i>
                        </button>
                    </div>
                    <div id="user-details-content" class="mb-4"></div>
                    <div class="flex gap-3 justify-end pt-4 border-t border-border/40">
                        <button id="user-details-activate-btn" type="button"
                            class="px-4 py-2 border border-border rounded-md hover:bg-muted transition-colors text-foreground"
                            data-i18n="js.users.activate">
                            Activate
                        </button>
                        <button data-modal-close="user-details-modal"
                            class="px-4 py-2 bg-primary text-primary-foreground rounded-md hover:bg-primary/90 transition-colors"
                            data-i18n="js.common.close">
                            Close
                        </button>
                    </div>
                </div>
            </div>

            <!-- Privileges Modal -->
            <div id="privileges-modal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
                <div class="bg-card rounded-lg shadow-xl p-6 max-w-2xl w-full border border-border max-h-[80vh] flex flex-col">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-xl font-semibold text-card-foreground" data-i18n="js.users.managePrivilegesModalTitle">Manage Privileges</h3>
                        <button data-modal-close="privileges-modal" class="text-muted-foreground hover:text-foreground">
                            <i data-lucide="x" class="h-5 w-5"></i>
                        </button>
                    </div>
                    <div id="privileges-form-container" class="flex-1 overflow-y-auto"></div>
                </div>
            </div>

            <!-- GDPR Modal -->
            <div id="gdpr-modal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
                <div class="bg-card rounded-lg shadow-xl p-6 max-w-2xl w-full border border-border max-h-[80vh] flex flex-col">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-xl font-semibold text-card-foreground" data-i18n="js.users.gdprModalTitle">GDPR - Delete Inactive Users</h3>
                        <button data-modal-close="gdpr-modal" class="text-muted-foreground hover:text-foreground">
                            <i data-lucide="x" class="h-5 w-5"></i>
                        </button>
                    </div>
                    <div class="mb-4">
                        <p class="text-sm text-muted-foreground" data-i18n="js.users.gdprModalDescription">
                            Users who have not logged in for 24+ months. Select users to permanently delete with all associated data.
                        </p>
                    </div>
                    <form id="gdpr-form" class="flex-1 overflow-y-auto">
                        <div id="gdpr-users-list" class="space-y-2"></div>
                        <div class="flex gap-3 justify-end pt-4 mt-4 border-t border-border/40">
                            <button type="button" data-modal-close="gdpr-modal"
                                class="px-4 py-2 border border-border rounded-md hover:bg-muted transition-colors text-foreground"
                                data-i18n="js.common.cancel">
                                Cancel
                            </button>
                            <button type="button" onclick="Users.handleGDPRDelete()"
                                class="px-4 py-2 bg-destructive text-destructive-foreground rounded-md hover:bg-destructive/90 transition-colors"
                                data-i18n="js.users.gdprDeleteSelected">
                                Delete Selected
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        `;
    }
    
    /**
     * Initialize module (called by ModuleBase.init)
     */
    async onInit(session, container) {
        // Initialize users using existing Users object
        if (typeof Users !== 'undefined') {
            this.users = Users;
            
            // Initialize users module
            await this.users.init(session);
            
            // Initialize icons after content is rendered
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
            
            // Translate page after content is rendered
            if (typeof i18n !== 'undefined' && typeof i18n.translatePage === 'function') {
                setTimeout(() => {
                    i18n.translatePage();
                }, 100);
            }
        } else {
            console.error('UsersModule: Users object not found');
        }
    }
    
    /**
     * Cleanup module
     */
    onCleanup() {
        // Cleanup if needed
    }
}

// Export for use in other scripts
window.UsersModule = UsersModule;
