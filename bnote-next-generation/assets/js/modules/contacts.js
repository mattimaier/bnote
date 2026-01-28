/**
 * BNote Next Generation - Contacts Module
 *
 * Copyright (C) 2026 BNote Contributors
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * Contacts Module
 * Extends ModuleBase - pure UI container
 */
class ContactsModule extends ModuleBase {
    constructor() {
        super();
        this.route = 'contacts';
        this.contacts = null;
    }
    
    /**
     * Get module HTML template (embedded in JS to prevent flashing)
     */
    getTemplate() {
        return `
            <div id="contacts-container">
                <!-- Page title -->
                <div class="mb-6">
                    <h1 class="text-2xl font-semibold text-foreground" data-i18n="js.contacts.title">Contacts</h1>
                    <p class="text-sm text-muted-foreground mt-1" data-i18n="js.contacts.subtitle">Manage contacts, groups, and integrations</p>
                </div>

                <!-- Action buttons -->
                <div class="flex items-center justify-between mb-6 flex-wrap gap-3">
                    <div class="flex items-center gap-3 flex-wrap">
                        <button id="add-contact-btn"
                            class="flex items-center gap-2 px-4 py-2.5 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 transition-colors text-sm font-medium shadow-sm hover:shadow">
                            <i data-lucide="plus" class="h-4 w-4"></i>
                            <span data-i18n="js.contacts.addContact">Add Contact</span>
                        </button>
                        <button id="integration-btn"
                            class="flex items-center gap-2 px-4 py-2.5 border border-border rounded-lg hover:bg-muted/50 transition-colors text-foreground text-sm font-medium">
                            <i data-lucide="box-arrow-in-up-right" class="h-4 w-4"></i>
                            <span data-i18n="js.contacts.integration">Integration</span>
                        </button>
                        <button id="groups-btn"
                            class="flex items-center gap-2 px-4 py-2.5 border border-border rounded-lg hover:bg-muted/50 transition-colors text-foreground text-sm font-medium">
                            <i data-lucide="people" class="h-4 w-4"></i>
                            <span data-i18n="js.contacts.groups">Groups</span>
                        </button>
                        <button id="print-btn"
                            class="flex items-center gap-2 px-4 py-2.5 border border-border rounded-lg hover:bg-muted/50 transition-colors text-foreground text-sm font-medium">
                            <i data-lucide="printer" class="h-4 w-4"></i>
                            <span data-i18n="js.contacts.print">Print</span>
                        </button>
                        <button id="vcard-btn"
                            class="flex items-center gap-2 px-4 py-2.5 border border-border rounded-lg hover:bg-muted/50 transition-colors text-foreground text-sm font-medium">
                            <i data-lucide="file-text" class="h-4 w-4"></i>
                            <span data-i18n="js.contacts.vcard">vCard</span>
                        </button>
                        <button id="privacy-btn"
                            class="flex items-center gap-2 px-4 py-2.5 border border-border rounded-lg hover:bg-muted/50 transition-colors text-foreground text-sm font-medium">
                            <i data-lucide="shield-alert" class="h-4 w-4"></i>
                            <span data-i18n="js.contacts.privacy">Privacy</span>
                        </button>
                    </div>
                    <button id="back-to-list-btn"
                        class="hidden flex items-center gap-2 px-4 py-2.5 border border-border rounded-lg hover:bg-muted/50 transition-colors text-foreground text-sm font-medium">
                        <i data-lucide="arrow-left" class="h-4 w-4"></i>
                        <span data-i18n="js.contacts.backToList">Back to List</span>
                    </button>
                </div>

                <!-- List Mode -->
                <div id="list-mode">
                    <!-- Group tabs -->
                    <div id="group-tabs-container" class="mb-4"></div>

                    <!-- Search input -->
                    <div id="contacts-search-container" class="mb-4"></div>

                    <!-- Contacts table -->
                    <div class="bg-card rounded-xl border border-border/40 shadow-sm">
                        <div class="p-6">
                            <div id="contacts-table-container"></div>
                        </div>
                    </div>
                </div>

                <!-- Integration Mode -->
                <div id="integration-mode" class="hidden">
                    <div class="bg-card rounded-xl border border-border/40 shadow-sm p-6">
                        <h2 class="text-xl font-semibold mb-4" data-i18n="js.contacts.integration">Integration</h2>
                        <p class="text-muted-foreground" data-i18n="js.contacts.integrationComingSoon">Integration mode - coming soon</p>
                    </div>
                </div>

                <!-- Groups Management Mode -->
                <div id="groups-mode" class="hidden">
                    <div class="bg-card rounded-xl border border-border/40 shadow-sm">
                        <div class="p-6">
                            <div id="groups-table-container"></div>
                        </div>
                    </div>
                </div>

                <!-- Print Mode -->
                <div id="print-mode" class="hidden">
                    <div class="bg-card rounded-xl border border-border/40 shadow-sm p-6">
                        <h2 class="text-xl font-semibold mb-4" data-i18n="js.contacts.print">Print</h2>
                        <p class="text-muted-foreground" data-i18n="js.contacts.printComingSoon">Print mode - coming soon</p>
                    </div>
                </div>

                <!-- vCard Mode -->
                <div id="vcard-mode" class="hidden">
                    <div class="bg-card rounded-xl border border-border/40 shadow-sm p-6">
                        <h2 class="text-xl font-semibold mb-4" data-i18n="js.contacts.vcardImportExport">vCard Import/Export</h2>
                        <p class="text-muted-foreground" data-i18n="js.contacts.vcardComingSoon">vCard mode - coming soon</p>
                    </div>
                </div>

                <!-- Privacy/GDPR Mode -->
                <div id="privacy-mode" class="hidden">
                    <div class="bg-card rounded-xl border border-border/40 shadow-sm">
                        <div class="p-6">
                            <div class="flex items-center justify-between mb-4">
                                <h2 class="text-xl font-semibold" data-i18n="js.contacts.gdprStatus">GDPR Status</h2>
                                <div class="flex gap-2">
                                    <button id="generate-gdpr-codes-btn"
                                        class="px-4 py-2 border border-border rounded-lg hover:bg-muted/50 transition-colors text-foreground text-sm font-medium"
                                        data-i18n="js.contacts.generateCodes">
                                        Generate Codes
                                    </button>
                                    <button id="send-gdpr-mail-btn"
                                        class="px-4 py-2 border border-border rounded-lg hover:bg-muted/50 transition-colors text-foreground text-sm font-medium"
                                        data-i18n="js.contacts.sendMail">
                                        Send Mail
                                    </button>
                                    <button id="delete-gdpr-nok-btn"
                                        class="px-4 py-2 bg-destructive text-destructive-foreground rounded-lg hover:bg-destructive/90 transition-colors text-sm font-medium"
                                        data-i18n="js.contacts.deleteNonConsenting">
                                        Delete Non-Consenting
                                    </button>
                                </div>
                            </div>
                            <div id="gdpr-table-container"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Loading indicator -->
            <div id="loading-indicator" class="hidden fixed inset-0 bg-black/20 z-50 flex items-center justify-center">
                <div class="bg-card rounded-lg p-6 shadow-xl">
                    <div class="flex items-center gap-3">
                        <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-primary"></div>
                        <span class="text-foreground" data-i18n="js.common.loading">Loading...</span>
                    </div>
                </div>
            </div>

            <!-- Add Contact Modal -->
            <div id="add-contact-modal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
                <div class="bg-card rounded-lg shadow-xl p-6 max-w-2xl w-full border border-border max-h-[90vh] overflow-y-auto">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-xl font-semibold text-card-foreground" data-i18n="js.contacts.addContactModalTitle">Add Contact</h3>
                        <button data-modal-close="add-contact-modal" class="text-muted-foreground hover:text-foreground">
                            <i data-lucide="x" class="h-5 w-5"></i>
                        </button>
                    </div>
                    <div id="add-contact-form-container"></div>
                </div>
            </div>

            <!-- Edit Contact Modal -->
            <div id="edit-contact-modal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
                <div class="bg-card rounded-lg shadow-xl p-6 max-w-2xl w-full border border-border max-h-[90vh] overflow-y-auto">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-xl font-semibold text-card-foreground" data-i18n="js.contacts.editContactModalTitle">Edit Contact</h3>
                        <button data-modal-close="edit-contact-modal" class="text-muted-foreground hover:text-foreground">
                            <i data-lucide="x" class="h-5 w-5"></i>
                        </button>
                    </div>
                    <div id="edit-contact-form-container"></div>
                </div>
            </div>

            <!-- Group Form Modal -->
            <div id="group-form-modal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
                <div class="bg-card rounded-lg shadow-xl p-6 max-w-md w-full border border-border">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-xl font-semibold text-card-foreground" data-i18n="js.contacts.groupFormModalTitle">Group</h3>
                        <button data-modal-close="group-form-modal" class="text-muted-foreground hover:text-foreground">
                            <i data-lucide="x" class="h-5 w-5"></i>
                        </button>
                    </div>
                    <div id="group-form-container"></div>
                </div>
            </div>

            <!-- Integration Form Modal -->
            <div id="integration-form-modal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
                <div class="bg-card rounded-lg shadow-xl p-6 max-w-2xl w-full border border-border max-h-[80vh] flex flex-col">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-xl font-semibold text-card-foreground" data-i18n="js.contacts.integrationModalTitle">Integration</h3>
                        <button data-modal-close="integration-form-modal" class="text-muted-foreground hover:text-foreground">
                            <i data-lucide="x" class="h-5 w-5"></i>
                        </button>
                    </div>
                    <div id="integration-form-container" class="flex-1 overflow-y-auto"></div>
                </div>
            </div>

            <!-- Print Form Modal -->
            <div id="print-form-modal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
                <div class="bg-card rounded-lg shadow-xl p-6 max-w-2xl w-full border border-border max-h-[80vh] flex flex-col">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-xl font-semibold text-card-foreground" data-i18n="js.contacts.printModalTitle">Print</h3>
                        <button data-modal-close="print-form-modal" class="text-muted-foreground hover:text-foreground">
                            <i data-lucide="x" class="h-5 w-5"></i>
                        </button>
                    </div>
                    <div id="print-form-container" class="flex-1 overflow-y-auto"></div>
                </div>
            </div>

            <!-- vCard Form Modal -->
            <div id="vcard-form-modal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
                <div class="bg-card rounded-lg shadow-xl p-6 max-w-2xl w-full border border-border max-h-[80vh] flex flex-col">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-xl font-semibold text-card-foreground" data-i18n="js.contacts.vcardModalTitle">vCard Import/Export</h3>
                        <button data-modal-close="vcard-form-modal" class="text-muted-foreground hover:text-foreground">
                            <i data-lucide="x" class="h-5 w-5"></i>
                        </button>
                    </div>
                    <div id="vcard-form-container" class="flex-1 overflow-y-auto"></div>
                </div>
            </div>
        `;
    }
    
    /**
     * Initialize module (called by ModuleBase.init)
     */
    async onInit(session, container) {
        // Initialize contacts using existing Contacts object
        if (typeof Contacts !== 'undefined') {
            this.contacts = Contacts;
            
            // Initialize contacts module
            await this.contacts.init(session);
            
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
            console.error('ContactsModule: Contacts object not found');
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
window.ContactsModule = ContactsModule;
