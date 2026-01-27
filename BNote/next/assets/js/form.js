/**
 * Reusable Form Component
 * Provides a modern, responsive form UI for editing data
 */
class Form {
    constructor(containerId, options = {}) {
        this.containerId = containerId;
        this.container = document.getElementById(containerId);
        if (!this.container) {
            console.error(`Form container not found: ${containerId}`);
            return;
        }
        
        // Configuration
        this.fields = options.fields || [];
        this.onSubmit = options.onSubmit || null;
        this.onCancel = options.onCancel || null;
        this.title = options.title || '';
        this.submitLabel = options.submitLabel || 'Submit';
        this.cancelLabel = options.cancelLabel || 'Cancel';
        this.showCancel = options.showCancel !== false;
        this.layout = options.layout || 'vertical'; // 'vertical', 'horizontal', 'grid'
        this.data = options.data || {};
        this.errors = {};
        this.loading = false;
        this.showTitle = options.showTitle !== false;
        
        // Initialize form data from fields
        this.initializeData();
        
        // Render form
        this.render();
    }
    
    /**
     * Initialize form data from fields and provided data
     */
    initializeData() {
        this.fields.forEach(field => {
            if (this.data[field.key] !== undefined) {
                // Use provided data
                return;
            }
            if (field.value !== undefined) {
                this.data[field.key] = field.value;
            } else if (field.type === 'checkbox' || field.type === 'privilege-checkbox') {
                this.data[field.key] = field.checked || false;
            } else if (field.type === 'select' && field.options && field.options.length > 0) {
                // Default to first option or empty
                this.data[field.key] = '';
            } else {
                this.data[field.key] = '';
            }
        });
    }
    
    /**
     * Render the form
     */
    render() {
        let html = '';
        
        // Form header (title)
        if (this.showTitle && this.title) {
            html += `<div class="mb-6">`;
            html += `<h3 class="text-xl font-semibold text-card-foreground">${this.escapeHtml(this.title)}</h3>`;
            html += `</div>`;
        }
        
        // Form body
        html += `<form id="${this.containerId}-form" class="space-y-6">`;
        
        // Render fields based on layout
        if (this.layout === 'grid') {
            html += `<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">`;
            this.fields.forEach(field => {
                const cols = field.gridCols || 1;
                html += `<div class="${this.getGridColClass(cols)}">`;
                html += this.renderField(field);
                html += `</div>`;
            });
            html += `</div>`;
        } else if (this.layout === 'horizontal') {
            this.fields.forEach(field => {
                html += `<div class="flex items-center gap-4">`;
                html += this.renderField(field, true);
                html += `</div>`;
            });
        } else {
            // Vertical layout (default)
            this.fields.forEach(field => {
                html += this.renderField(field);
            });
        }
        
        // Form footer (buttons)
        if (this.showCancel || this.onSubmit) {
            html += `<div class="flex gap-3 justify-end pt-4 border-t border-border/40">`;
            if (this.showCancel) {
                html += `<button type="button" id="${this.containerId}-cancel" class="px-4 py-2 border border-border rounded-md hover:bg-muted transition-colors text-foreground">${this.escapeHtml(this.cancelLabel)}</button>`;
            }
            if (this.onSubmit) {
                html += `<button type="submit" id="${this.containerId}-submit" class="px-4 py-2 bg-primary text-primary-foreground rounded-md hover:bg-primary/90 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">${this.escapeHtml(this.submitLabel)}</button>`;
            }
            html += `</div>`;
        }
        
        html += `</form>`;
        
        this.container.innerHTML = html;
        
        // Attach event listeners
        this.attachEventListeners();
        
        // Initialize searchable dropdowns
        this.initializeSearchableDropdowns();
        
        // Initialize Lucide icons
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    }
    
    /**
     * Initialize searchable dropdowns with typeahead
     */
    initializeSearchableDropdowns() {
        this.fields.forEach(field => {
            if (field.type === 'select' && field.searchable !== false && field.options && field.options.length > 5) {
                const fieldId = `${this.containerId}-${field.key}`;
                const searchInput = document.getElementById(`${fieldId}-search`);
                const dropdown = document.getElementById(`${fieldId}-dropdown`);
                const select = document.getElementById(fieldId);
                
                if (!searchInput || !dropdown || !select) return;
                
                // Get all options
                const options = Array.from(select.options).map(opt => ({
                    value: opt.value,
                    label: opt.textContent,
                    selected: opt.selected
                }));
                
                // Set initial value
                const selectedOption = options.find(opt => opt.selected);
                let currentValue = selectedOption ? selectedOption.value : '';
                if (selectedOption) {
                    searchInput.value = selectedOption.label;
                }
                
                // Filter and render options
                const renderOptions = (filteredOptions) => {
                    if (filteredOptions.length === 0) {
                        dropdown.innerHTML = `<div class="px-3 py-2 text-sm text-muted-foreground">No results found</div>`;
                        return;
                    }
                    
                    dropdown.innerHTML = filteredOptions.map(opt => `
                        <div class="px-3 py-2 text-sm text-foreground hover:bg-muted/50 cursor-pointer ${opt.value === currentValue ? 'bg-primary/10' : ''}" 
                             data-value="${this.escapeHtml(opt.value)}">
                            ${this.escapeHtml(opt.label)}
                        </div>
                    `).join('');
                    
                    // Attach click handlers
                    dropdown.querySelectorAll('[data-value]').forEach(item => {
                        item.addEventListener('click', () => {
                            const value = item.getAttribute('data-value');
                            select.value = value;
                            currentValue = value;
                            searchInput.value = item.textContent.trim();
                            dropdown.classList.add('hidden');
                            this.updateFieldValue(field.key);
                        });
                    });
                };
                
                // Initial render
                renderOptions(options);
                
                // Track if user is actively typing
                let isTyping = false;
                let originalValue = searchInput.value;
                
                // Search input handler
                searchInput.addEventListener('input', (e) => {
                    isTyping = true;
                    const query = e.target.value.toLowerCase().trim();
                    
                    // Clear selection when user starts typing
                    if (query !== '' && currentValue) {
                        select.value = '';
                        currentValue = '';
                        this.updateFieldValue(field.key);
                    }
                    
                    if (query === '') {
                        renderOptions(options);
                        if (!document.activeElement === searchInput) {
                            dropdown.classList.add('hidden');
                        }
                        return;
                    }
                    
                    const filtered = options.filter(opt => 
                        opt.label.toLowerCase().includes(query)
                    );
                    
                    renderOptions(filtered);
                    dropdown.classList.remove('hidden');
                });
                
                // Focus handler - clear field when focused
                searchInput.addEventListener('focus', () => {
                    originalValue = searchInput.value;
                    // Clear the input when focused so user can start typing fresh
                    searchInput.value = '';
                    isTyping = false;
                    renderOptions(options);
                    dropdown.classList.remove('hidden');
                });
                
                // Blur handler
                searchInput.addEventListener('blur', () => {
                    // Wait a bit to allow click events on dropdown to fire first
                    setTimeout(() => {
                        // If user didn't select anything and didn't type, restore original
                        if (!isTyping && searchInput.value.trim() === '' && currentValue) {
                            const selectedOption = options.find(opt => opt.value === currentValue);
                            if (selectedOption) {
                                searchInput.value = selectedOption.label;
                            }
                        } else if (!isTyping && searchInput.value.trim() === '' && !currentValue) {
                            // If no value selected and input is empty, restore original
                            searchInput.value = originalValue;
                        } else if (currentValue) {
                            // If we have a value, make sure input shows the label
                            const selectedOption = options.find(opt => opt.value === currentValue);
                            if (selectedOption) {
                                searchInput.value = selectedOption.label;
                            }
                        }
                        isTyping = false;
                        dropdown.classList.add('hidden');
                    }, 200);
                });
                
                // Click outside to close
                document.addEventListener('click', (e) => {
                    if (!searchInput.contains(e.target) && !dropdown.contains(e.target)) {
                        dropdown.classList.add('hidden');
                    }
                });
            }
        });
    }
    
    /**
     * Get grid column class
     */
    getGridColClass(cols) {
        const colMap = {
            1: 'col-span-1',
            2: 'col-span-2',
            3: 'col-span-3',
            4: 'col-span-4',
            6: 'col-span-6',
            12: 'col-span-12'
        };
        return colMap[cols] || 'col-span-1';
    }
    
    /**
     * Render a single field
     */
    renderField(field, horizontal = false) {
        if (field.render && typeof field.render === 'function') {
            return field.render(field, this.data[field.key], this);
        }
        
        switch (field.type) {
            case 'text':
            case 'email':
            case 'number':
                return this.renderTextInput(field, horizontal);
            case 'password':
                return this.renderPasswordInput(field, horizontal);
            case 'select':
                return this.renderSelect(field, horizontal);
            case 'checkbox':
                return this.renderCheckbox(field, horizontal);
            case 'privilege-checkbox':
                return this.renderPrivilegeCheckbox(field);
            case 'textarea':
                return this.renderTextarea(field, horizontal);
            default:
                return this.renderTextInput(field, horizontal);
        }
    }
    
    /**
     * Render text input field
     */
    renderTextInput(field, horizontal = false) {
        const value = this.data[field.key] || '';
        const error = this.errors[field.key];
        const fieldId = `${this.containerId}-${field.key}`;
        
        let html = '';
        
        if (horizontal) {
            html += `<label for="${fieldId}" class="flex-shrink-0 w-32 text-sm font-medium text-foreground">${this.escapeHtml(field.label)}${field.required ? ' <span class="text-destructive">*</span>' : ''}</label>`;
            html += `<div class="flex-1">`;
        } else {
            html += `<div class="form-field">`;
            html += `<label for="${fieldId}" class="block text-sm font-medium text-foreground mb-1">${this.escapeHtml(field.label)}${field.required ? ' <span class="text-destructive">*</span>' : ''}</label>`;
        }
        
        html += `<input type="${field.type}" id="${fieldId}" name="${field.key}" value="${this.escapeHtml(value)}"`;
        html += ` class="w-full px-3 py-2 border ${error ? 'border-destructive' : 'border-border'} rounded-md text-foreground bg-background focus:outline-none focus:ring-2 focus:ring-primary/30 ${field.className || ''}"`;
        if (field.placeholder) html += ` placeholder="${this.escapeHtml(field.placeholder)}"`;
        if (field.required) html += ` required`;
        if (field.disabled) html += ` disabled`;
        if (field.readonly) html += ` readonly`;
        html += ` />`;
        
        if (field.help && !error) {
            html += `<p class="mt-1 text-xs text-muted-foreground">${this.escapeHtml(field.help)}</p>`;
        }
        
        if (error) {
            html += `<p class="mt-1 text-xs text-destructive">${this.escapeHtml(error)}</p>`;
        }
        
        if (horizontal) {
            html += `</div>`;
        } else {
            html += `</div>`;
        }
        
        return html;
    }
    
    /**
     * Render password input field
     */
    renderPasswordInput(field, horizontal = false) {
        const value = this.data[field.key] || '';
        const error = this.errors[field.key];
        const fieldId = `${this.containerId}-${field.key}`;
        
        let html = '';
        
        if (horizontal) {
            html += `<label for="${fieldId}" class="flex-shrink-0 w-32 text-sm font-medium text-foreground">${this.escapeHtml(field.label)}${field.required ? ' <span class="text-destructive">*</span>' : ''}</label>`;
            html += `<div class="flex-1">`;
        } else {
            html += `<div class="form-field">`;
            html += `<label for="${fieldId}" class="block text-sm font-medium text-foreground mb-1">${this.escapeHtml(field.label)}${field.required ? ' <span class="text-destructive">*</span>' : ''}</label>`;
        }
        
        html += `<input type="password" id="${fieldId}" name="${field.key}" value="${this.escapeHtml(value)}"`;
        html += ` class="w-full px-3 py-2 border ${error ? 'border-destructive' : 'border-border'} rounded-md text-foreground bg-background focus:outline-none focus:ring-2 focus:ring-primary/30 ${field.className || ''}"`;
        if (field.placeholder) html += ` placeholder="${this.escapeHtml(field.placeholder)}"`;
        if (field.required) html += ` required`;
        if (field.disabled) html += ` disabled`;
        html += ` />`;
        
        if (field.help && !error) {
            html += `<p class="mt-1 text-xs text-muted-foreground">${this.escapeHtml(field.help)}</p>`;
        }
        
        if (error) {
            html += `<p class="mt-1 text-xs text-destructive">${this.escapeHtml(error)}</p>`;
        }
        
        if (horizontal) {
            html += `</div>`;
        } else {
            html += `</div>`;
        }
        
        return html;
    }
    
    /**
     * Render select/dropdown field with optional typeahead/search
     */
    renderSelect(field, horizontal = false) {
        const value = this.data[field.key] || '';
        const error = this.errors[field.key];
        const fieldId = `${this.containerId}-${field.key}`;
        const searchable = field.searchable !== false && field.options && field.options.length > 5; // Auto-enable for 5+ options
        
        let html = '';
        
        if (horizontal) {
            html += `<label for="${fieldId}" class="flex-shrink-0 w-32 text-sm font-medium text-foreground">${this.escapeHtml(field.label)}${field.required ? ' <span class="text-destructive">*</span>' : ''}</label>`;
            html += `<div class="flex-1">`;
        } else {
            html += `<div class="form-field">`;
            html += `<label for="${fieldId}" class="block text-sm font-medium text-foreground mb-1">${this.escapeHtml(field.label)}${field.required ? ' <span class="text-destructive">*</span>' : ''}</label>`;
        }
        
        if (searchable) {
            // Searchable dropdown with typeahead
            html += `<div class="relative">`;
            html += `<input type="text" id="${fieldId}-search" placeholder="Type to search..."`;
            html += ` class="w-full px-3 py-2 border ${error ? 'border-destructive' : 'border-border'} rounded-md text-foreground bg-background focus:outline-none focus:ring-2 focus:ring-primary/30 ${field.className || ''}"`;
            html += ` autocomplete="off" />`;
            html += `<i data-lucide="chevron-down" class="absolute right-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground pointer-events-none"></i>`;
            html += `<div id="${fieldId}-dropdown" class="hidden absolute z-50 w-full mt-1 bg-card border border-border rounded-md shadow-lg max-h-60 overflow-auto">`;
            html += `</div>`;
            html += `<select id="${fieldId}" name="${field.key}" class="hidden">`;
        } else {
            // Standard select
            html += `<select id="${fieldId}" name="${field.key}"`;
            html += ` class="w-full px-3 py-2 border ${error ? 'border-destructive' : 'border-border'} rounded-md text-foreground bg-background focus:outline-none focus:ring-2 focus:ring-primary/30 ${field.className || ''}"`;
            if (field.required) html += ` required`;
            if (field.disabled) html += ` disabled`;
            html += `>`;
        }
        
        // Empty option
        if (field.emptyOption !== false) {
            html += `<option value="">${field.emptyLabel || '-- Select --'}</option>`;
        }
        
        // Options
        if (field.options && Array.isArray(field.options)) {
            field.options.forEach(option => {
                const optValue = typeof option === 'object' ? option.value : option;
                const optLabel = typeof option === 'object' ? option.label : option;
                const selected = String(optValue) === String(value) ? ' selected' : '';
                html += `<option value="${this.escapeHtml(optValue)}"${selected}>${this.escapeHtml(optLabel)}</option>`;
            });
        }
        
        html += `</select>`;
        
        if (searchable) {
            html += `</div>`;
        }
        
        if (field.help && !error) {
            html += `<p class="mt-1 text-xs text-muted-foreground">${this.escapeHtml(field.help)}</p>`;
        }
        
        if (error) {
            html += `<p class="mt-1 text-xs text-destructive">${this.escapeHtml(error)}</p>`;
        }
        
        if (horizontal) {
            html += `</div>`;
        } else {
            html += `</div>`;
        }
        
        return html;
    }
    
    /**
     * Render checkbox field
     */
    renderCheckbox(field, horizontal = false) {
        const checked = this.data[field.key] || false;
        const error = this.errors[field.key];
        const fieldId = `${this.containerId}-${field.key}`;
        
        let html = '';
        
        if (horizontal) {
            html += `<div class="flex items-center gap-2">`;
        } else {
            html += `<div class="form-field">`;
        }
        
        html += `<div class="flex items-center gap-2">`;
        html += `<div class="relative inline-flex items-center">`;
        html += `<input type="checkbox" id="${fieldId}" name="${field.key}" value="1"`;
        if (checked) html += ` checked`;
        if (field.disabled) html += ` disabled`;
        html += ` class="peer h-4 w-4 rounded border-2 border-border bg-background focus:ring-2 focus:ring-primary/30 focus:ring-offset-0 cursor-pointer transition-colors checked:bg-primary checked:border-primary ${field.className || ''}" />`;
        html += `<svg class="absolute left-0 top-0 h-4 w-4 pointer-events-none opacity-0 peer-checked:opacity-100 transition-opacity text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">`;
        html += `<path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />`;
        html += `</svg>`;
        html += `</div>`;
        html += `<label for="${fieldId}" class="text-sm text-foreground cursor-pointer select-none">${this.escapeHtml(field.label)}</label>`;
        html += `</div>`;
        
        if (error) {
            html += `<p class="mt-1 text-xs text-destructive">${this.escapeHtml(error)}</p>`;
        }
        
        html += `</div>`;
        
        return html;
    }
    
    /**
     * Render privilege checkbox (simple list, no boxes)
     */
    renderPrivilegeCheckbox(field) {
        const selectedValues = this.data[field.key] || [];
        const isArray = Array.isArray(selectedValues);
        const error = this.errors[field.key];
        
        let html = '';
        
        html += `<div class="form-field">`;
        if (field.label) {
            html += `<label class="block text-sm font-medium text-foreground mb-3">${this.escapeHtml(field.label)}${field.required ? ' <span class="text-destructive">*</span>' : ''}</label>`;
        }
        
        html += `<div class="space-y-2">`;
        
        if (field.options && Array.isArray(field.options)) {
            field.options.forEach(option => {
                const optValue = typeof option === 'object' ? option.value : option;
                const optLabel = typeof option === 'object' ? option.label : option;
                const optDescription = typeof option === 'object' ? option.description : null;
                const isChecked = isArray 
                    ? selectedValues.includes(optValue) 
                    : (typeof option === 'object' && option.checked) || false;
                
                const fieldId = `${this.containerId}-${field.key}-${optValue}`;
                
                html += `<label for="${fieldId}" class="flex items-center gap-3 py-2 cursor-pointer hover:bg-muted/30 rounded-md px-1 transition-colors">`;
                html += `<div class="relative inline-flex items-center flex-shrink-0">`;
                html += `<input type="checkbox" id="${fieldId}" name="${field.key}[]" value="${this.escapeHtml(optValue)}"`;
                if (isChecked) html += ` checked`;
                if (field.disabled) html += ` disabled`;
                html += ` class="peer h-4 w-4 rounded border-2 border-border bg-background focus:ring-2 focus:ring-primary/30 focus:ring-offset-0 cursor-pointer transition-colors checked:bg-primary checked:border-primary" />`;
                html += `<svg class="absolute left-0 top-0 h-4 w-4 pointer-events-none opacity-0 peer-checked:opacity-100 transition-opacity text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">`;
                html += `<path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />`;
                html += `</svg>`;
                html += `</div>`;
                
                html += `<div class="flex-1">`;
                html += `<span class="text-sm text-foreground">${this.escapeHtml(optLabel)}</span>`;
                if (optDescription) {
                    html += `<p class="text-xs text-muted-foreground mt-0.5">${this.escapeHtml(optDescription)}</p>`;
                }
                html += `</div>`;
                html += `</label>`;
            });
        }
        
        html += `</div>`;
        
        if (error) {
            html += `<p class="mt-2 text-xs text-destructive">${this.escapeHtml(error)}</p>`;
        }
        
        html += `</div>`;
        
        return html;
    }
    
    /**
     * Render textarea field
     */
    renderTextarea(field, horizontal = false) {
        const value = this.data[field.key] || '';
        const error = this.errors[field.key];
        const fieldId = `${this.containerId}-${field.key}`;
        
        let html = '';
        
        if (horizontal) {
            html += `<label for="${fieldId}" class="flex-shrink-0 w-32 text-sm font-medium text-foreground">${this.escapeHtml(field.label)}${field.required ? ' <span class="text-destructive">*</span>' : ''}</label>`;
            html += `<div class="flex-1">`;
        } else {
            html += `<div class="form-field">`;
            html += `<label for="${fieldId}" class="block text-sm font-medium text-foreground mb-1">${this.escapeHtml(field.label)}${field.required ? ' <span class="text-destructive">*</span>' : ''}</label>`;
        }
        
        html += `<textarea id="${fieldId}" name="${field.key}"`;
        html += ` class="w-full px-3 py-2 border ${error ? 'border-destructive' : 'border-border'} rounded-md text-foreground bg-background focus:outline-none focus:ring-2 focus:ring-primary/30 resize-y min-h-[100px] ${field.className || ''}"`;
        if (field.placeholder) html += ` placeholder="${this.escapeHtml(field.placeholder)}"`;
        if (field.required) html += ` required`;
        if (field.disabled) html += ` disabled`;
        if (field.rows) html += ` rows="${field.rows}"`;
        html += `>${this.escapeHtml(value)}</textarea>`;
        
        if (field.help && !error) {
            html += `<p class="mt-1 text-xs text-muted-foreground">${this.escapeHtml(field.help)}</p>`;
        }
        
        if (error) {
            html += `<p class="mt-1 text-xs text-destructive">${this.escapeHtml(error)}</p>`;
        }
        
        if (horizontal) {
            html += `</div>`;
        } else {
            html += `</div>`;
        }
        
        return html;
    }
    
    /**
     * Attach event listeners
     */
    attachEventListeners() {
        const form = document.getElementById(`${this.containerId}-form`);
        if (!form) return;
        
        // Form submit
        form.addEventListener('submit', (e) => {
            e.preventDefault();
            this.handleSubmit();
        });
        
        // Cancel button
        const cancelBtn = document.getElementById(`${this.containerId}-cancel`);
        if (cancelBtn && this.onCancel) {
            cancelBtn.addEventListener('click', () => {
                this.onCancel();
            });
        }
        
        // Field value updates (for real-time data sync)
        this.fields.forEach(field => {
            if (field.type === 'privilege-checkbox') {
                // Handle privilege checkboxes (multiple inputs with same name)
                const checkboxes = this.container.querySelectorAll(`input[name="${field.key}[]"]`);
                checkboxes.forEach(checkbox => {
                    checkbox.addEventListener('change', () => {
                        this.updateFieldValue(field.key);
                    });
                });
            } else if (field.type === 'select' && field.searchable !== false) {
                // Searchable selects are handled in initializeSearchableDropdowns
                // The hidden select will be updated when option is selected
                const select = document.getElementById(`${this.containerId}-${field.key}`);
                if (select) {
                    select.addEventListener('change', () => {
                        this.updateFieldValue(field.key);
                    });
                }
            } else {
                const fieldElement = document.getElementById(`${this.containerId}-${field.key}`);
                if (!fieldElement) return;
                
                if (field.type === 'checkbox') {
                    fieldElement.addEventListener('change', () => {
                        this.updateFieldValue(field.key);
                    });
                } else {
                    fieldElement.addEventListener('input', () => {
                        this.updateFieldValue(field.key);
                    });
                }
            }
        });
    }
    
    /**
     * Update field value from DOM
     */
    updateFieldValue(fieldKey) {
        const field = this.fields.find(f => f.key === fieldKey);
        if (!field) return;
        
        if (field.type === 'privilege-checkbox') {
            // Get all checked values
            const checkboxes = this.container.querySelectorAll(`input[name="${fieldKey}[]"]:checked`);
            this.data[fieldKey] = Array.from(checkboxes).map(cb => cb.value);
        } else if (field.type === 'checkbox') {
            const checkbox = document.getElementById(`${this.containerId}-${fieldKey}`);
            this.data[fieldKey] = checkbox ? checkbox.checked : false;
        } else if (field.type === 'select' && field.searchable !== false) {
            // For searchable selects, get value from hidden select
            const select = document.getElementById(`${this.containerId}-${fieldKey}`);
            this.data[fieldKey] = select ? select.value : '';
        } else {
            const input = document.getElementById(`${this.containerId}-${fieldKey}`);
            this.data[fieldKey] = input ? input.value : '';
        }
    }
    
    /**
     * Handle form submission
     */
    async handleSubmit() {
        // Clear previous errors
        this.errors = {};
        
        // Validate form
        if (!this.validate()) {
            this.render(); // Re-render to show errors
            return;
        }
        
        // Get form data
        const formData = this.getData();
        
        // Set loading state
        this.setLoading(true);
        
        try {
            // Call onSubmit callback
            if (this.onSubmit) {
                await this.onSubmit(formData);
            }
        } catch (error) {
            console.error('Form submission error:', error);
            // Handle error (could set field-specific errors)
            if (error.fieldErrors) {
                this.setErrors(error.fieldErrors);
                this.render();
            }
        } finally {
            this.setLoading(false);
        }
    }
    
    /**
     * Validate form
     */
    validate() {
        let isValid = true;
        this.errors = {};
        
        this.fields.forEach(field => {
            const value = this.data[field.key];
            const fieldError = this.validateField(field, value);
            
            if (fieldError) {
                this.errors[field.key] = fieldError;
                isValid = false;
            }
        });
        
        return isValid;
    }
    
    /**
     * Validate a single field
     */
    validateField(field, value) {
        // Required validation
        if (field.required) {
            if (field.type === 'checkbox' || field.type === 'privilege-checkbox') {
                if (field.type === 'privilege-checkbox') {
                    if (!Array.isArray(value) || value.length === 0) {
                        return field.errorMessage || `${field.label} is required`;
                    }
                } else if (!value) {
                    return field.errorMessage || `${field.label} is required`;
                }
            } else {
                const strValue = String(value || '').trim();
                if (strValue === '') {
                    return field.errorMessage || `${field.label} is required`;
                }
            }
        }
        
        // Skip other validations if field is empty and not required
        // Special case: password fields that are not required should allow empty (for "keep current password")
        if (field.type === 'password' && !field.required) {
            // Empty password is allowed for optional password fields
            if (!value || (typeof value === 'string' && value.trim() === '')) {
                return null;
            }
        } else if (!value || (typeof value === 'string' && value.trim() === '')) {
            return null;
        }
        
        // Email validation
        if (field.type === 'email') {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(value)) {
                return field.errorMessage || 'Please enter a valid email address';
            }
        }
        
        // Min length validation
        if (field.minLength && String(value).length < field.minLength) {
            return field.errorMessage || `${field.label} must be at least ${field.minLength} characters`;
        }
        
        // Max length validation
        if (field.maxLength && String(value).length > field.maxLength) {
            return field.errorMessage || `${field.label} must be no more than ${field.maxLength} characters`;
        }
        
        // Pattern validation
        if (field.pattern) {
            const regex = new RegExp(field.pattern);
            if (!regex.test(value)) {
                return field.errorMessage || field.patternMessage || `${field.label} format is invalid`;
            }
        }
        
        // Custom validation
        if (field.validation && typeof field.validation === 'function') {
            const customError = field.validation(value, this.data);
            if (customError) {
                return customError;
            }
        }
        
        return null;
    }
    
    /**
     * Get form data
     */
    getData() {
        // Update all field values from DOM
        this.fields.forEach(field => {
            this.updateFieldValue(field.key);
        });
        
        // Return data object
        // For optional password fields, exclude empty values (means "keep current")
        const result = {};
        this.fields.forEach(field => {
            const value = this.data[field.key];
            // Skip empty optional password fields (they mean "keep current password")
            if (field.type === 'password' && !field.required && (!value || String(value).trim() === '')) {
                // Don't include empty optional passwords
                return;
            }
            result[field.key] = value;
        });
        
        return result;
    }
    
    /**
     * Set field value
     */
    setValue(fieldKey, value) {
        this.data[fieldKey] = value;
        const fieldElement = document.getElementById(`${this.containerId}-${fieldKey}`);
        if (fieldElement) {
            if (fieldElement.type === 'checkbox') {
                fieldElement.checked = !!value;
            } else {
                fieldElement.value = value;
            }
        }
    }
    
    /**
     * Get field value
     */
    getValue(fieldKey) {
        this.updateFieldValue(fieldKey);
        return this.data[fieldKey];
    }
    
    /**
     * Set errors
     */
    setErrors(errors) {
        this.errors = errors || {};
        this.render();
    }
    
    /**
     * Set loading state
     */
    setLoading(loading) {
        this.loading = loading;
        const submitBtn = document.getElementById(`${this.containerId}-submit`);
        if (submitBtn) {
            submitBtn.disabled = loading;
            if (loading) {
                submitBtn.innerHTML = `
                    <span class="inline-flex items-center gap-2">
                        <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-primary-foreground"></div>
                        ${this.escapeHtml(this.submitLabel)}
                    </span>
                `;
            } else {
                submitBtn.innerHTML = this.escapeHtml(this.submitLabel);
            }
        }
    }
    
    /**
     * Escape HTML
     */
    escapeHtml(text) {
        if (text == null) return '';
        const div = document.createElement('div');
        div.textContent = String(text);
        return div.innerHTML;
    }
}
