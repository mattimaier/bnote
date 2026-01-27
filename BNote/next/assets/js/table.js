/**
 * Reusable Table Component
 * Provides a modern, interactive table with sorting, actions, and inline editing
 */
class Table {
    constructor(containerId, options = {}) {
        this.containerId = containerId;
        this.container = document.getElementById(containerId);
        if (!this.container) {
            console.error(`Table container not found: ${containerId}`);
            return;
        }
        
        // Store instance globally for event handlers
        if (!window.tableInstances) {
            window.tableInstances = {};
        }
        window.tableInstances[containerId] = this;
        
        // Configuration
        this.columns = options.columns || [];
        this.data = options.data || [];
        this.onRowClick = options.onRowClick || null;
        this.onEdit = options.onEdit || null;
        this.onDelete = options.onDelete || null;
        this.onAction = options.onAction || null;
        this.inlineEdit = options.inlineEdit || false;
        this.sortable = options.sortable !== false; // Default true
        this.emptyMessage = options.emptyMessage || 'No data available';
        this.loading = false;
        this.searchable = options.searchable !== false; // Default true
        this.searchInputContainer = options.searchInputContainer || null;
        
        // State
        this.sortColumn = options.defaultSort || 'id'; // Default sort by ID
        this.sortDirection = options.defaultSortDirection || 'asc';
        this.editingCell = null;
        this.searchTerm = '';
        this.openMenuRowId = null;
        
        // Render search input if container provided
        if (this.searchable && this.searchInputContainer) {
            this.renderSearchInput();
        }
        
        // Render table
        this.render();
    }
    
    /**
     * Render the table
     */
    render() {
        if (this.loading) {
            this.container.innerHTML = this.getLoadingHTML();
            return;
        }
        
        // Apply filtering first
        let filteredData = this.filterData([...this.data]);
        
        if (filteredData.length === 0) {
            if (this.searchTerm) {
                this.container.innerHTML = this.getEmptyHTML('No results found');
            } else {
                this.container.innerHTML = this.getEmptyHTML();
            }
            return;
        }
        
        // Apply sorting
        const sortedData = this.sortData(filteredData);
        
        // Build table HTML
        let html = '<div class="overflow-x-auto">';
        html += '<table class="w-full border-collapse">';
        html += '<thead>';
        html += '<tr class="border-b border-border/40">';
        
        // Render headers
        this.columns.forEach((col, index) => {
            const sortable = this.sortable && col.sortable !== false;
            const sortIcon = sortable && this.sortColumn === col.key 
                ? (this.sortDirection === 'asc' ? '↑' : '↓') 
                : '';
            
            const thId = `th-${this.containerId}-${col.key}`;
            html += `<th id="${thId}" class="px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider ${sortable ? 'cursor-pointer hover:bg-muted/50' : ''}"`;
            html += `>${col.label} ${sortIcon}</th>`;
        });
        
        // Actions column if needed
        if (this.onEdit || this.onDelete || this.onAction) {
            html += '<th class="px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider">Actions</th>';
        }
        
        html += '</tr>';
        html += '</thead>';
        html += '<tbody>';
        
        // Render rows
        sortedData.forEach((row, rowIndex) => {
            html += this.renderRow(row, rowIndex);
        });
        
        // Store reference for row click handlers
        this._sortedData = sortedData;
        
        html += '</tbody>';
        html += '</table>';
        html += '</div>';
        
        this.container.innerHTML = html;
        
        // Initialize Lucide icons if available
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
        
        // Attach sort event listeners to headers
        this.columns.forEach((col) => {
            const sortable = this.sortable && col.sortable !== false;
            if (sortable) {
                const th = document.getElementById(`th-${this.containerId}-${col.key}`);
                if (th) {
                    th.addEventListener('click', () => {
                        this.sort(col.key);
                    });
                }
            }
        });
        
        // Attach row click listeners (use event listeners instead of onclick)
        if (this.onRowClick) {
            const rows = this.container.querySelectorAll('tbody tr[data-row-id]');
            rows.forEach(row => {
                // Remove any existing onclick attributes
                row.removeAttribute('onclick');
                
                const rowId = row.getAttribute('data-row-id');
                row.addEventListener('click', (e) => {
                    // Don't trigger if clicking on action button or editable cell
                    if (e.target.closest('[data-actions-btn]') || 
                        e.target.closest('[data-editable]') ||
                        e.target.closest('button') ||
                        e.target.closest('input') ||
                        e.target.closest('select') ||
                        e.target.closest('td[onclick]')) {
                        return;
                    }
                    e.preventDefault();
                    e.stopPropagation();
                    this.handleRowClick(parseInt(rowId));
                });
            });
        }
        
        // Attach action menu button listeners
        if (this.onEdit || this.onDelete || this.onAction) {
            const actionButtons = this.container.querySelectorAll(`[data-actions-btn]`);
            actionButtons.forEach(btn => {
                const rowId = btn.getAttribute('data-actions-btn');
                btn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    this.toggleActionsMenu(parseInt(rowId), e);
                });
            });
        }
        
        // Note: Don't close menus here as it interferes with menu interactions
        // Menus will close on outside click or escape key
    }
    
    /**
     * Render a single row
     */
    renderRow(row, rowIndex) {
        const rowId = row.id || rowIndex;
        let html = `<tr class="border-b border-border/20 hover:bg-muted/30 transition-colors" data-row-id="${rowId}"`;
        
        if (this.onRowClick) {
            html += ` style="cursor: pointer;"`;
        }
        html += '>';
        
        // Render cells
        this.columns.forEach(col => {
            const value = this.getCellValue(row, col);
            const editable = this.inlineEdit && col.editable;
            
            html += `<td class="px-4 py-3 text-sm text-foreground"`;
            if (editable) {
                html += ` data-editable="true" data-field="${col.key}" data-row-id="${rowId}"`;
                html += ` onclick="event.stopPropagation(); window.tableInstances['${this.containerId}'].startEdit(${rowId}, '${col.key}')"`;
                html += ' style="cursor: text;"';
            }
            html += '>';
            
            if (col.render) {
                html += col.render(value, row, rowIndex);
            } else {
                html += this.escapeHtml(value || '-');
            }
            
            html += '</td>';
        });
        
        // Actions cell - 3-dots menu
        if (this.onEdit || this.onDelete || this.onAction) {
            html += `<td class="px-4 py-3 text-sm" onclick="event.stopPropagation();">`;
            html += `<div class="relative" data-actions-cell="${rowId}">`;
            html += `<button data-actions-btn="${rowId}" class="text-muted-foreground hover:text-foreground p-1 rounded hover:bg-muted/50" title="Actions">`;
            html += '<i data-lucide="more-vertical" class="h-4 w-4"></i>';
            html += '</button>';
            html += `</div>`;
            html += '</td>';
        }
        
        html += '</tr>';
        return html;
    }
    
    /**
     * Get cell value from row data
     */
    getCellValue(row, col) {
        if (col.value) {
            return typeof col.value === 'function' ? col.value(row) : row[col.value];
        }
        return row[col.key];
    }
    
    /**
     * Filter data based on search term
     */
    filterData(data) {
        if (!this.searchTerm || !this.searchable) {
            return data;
        }
        
        const searchLower = this.searchTerm.toLowerCase().trim();
        if (!searchLower) {
            return data;
        }
        
        return data.filter(row => {
            // Search across all visible columns
            return this.columns.some(col => {
                const value = this.getCellValue(row, col);
                const valueStr = String(value || '').toLowerCase();
                return valueStr.includes(searchLower);
            });
        });
    }
    
    /**
     * Sort data
     */
    sortData(data) {
        // Default sort by ID if no sort column specified
        if (!this.sortColumn) {
            this.sortColumn = 'id';
            this.sortDirection = 'asc';
        }
        
        const col = this.columns.find(c => c.key === this.sortColumn);
        if (!col || col.sortable === false) {
            return data;
        }
        
        return [...data].sort((a, b) => {
            const aVal = this.getCellValue(a, col);
            const bVal = this.getCellValue(b, col);
            
            // Handle null/undefined
            if (aVal == null && bVal == null) return 0;
            if (aVal == null) return 1;
            if (bVal == null) return -1;
            
            // Date sorting
            if (col.type === 'date') {
                const aDate = new Date(aVal);
                const bDate = new Date(bVal);
                
                // Handle invalid dates
                if (isNaN(aDate.getTime()) && isNaN(bDate.getTime())) return 0;
                if (isNaN(aDate.getTime())) return 1;
                if (isNaN(bDate.getTime())) return -1;
                
                const aTime = aDate.getTime();
                const bTime = bDate.getTime();
                return this.sortDirection === 'asc' ? aTime - bTime : bTime - aTime;
            }
            
            // Custom sort function
            if (col.sort) {
                return col.sort(aVal, bVal, this.sortDirection);
            }
            
            // Default comparison
            if (typeof aVal === 'number' && typeof bVal === 'number') {
                return this.sortDirection === 'asc' ? aVal - bVal : bVal - aVal;
            }
            
            const aStr = String(aVal).toLowerCase();
            const bStr = String(bVal).toLowerCase();
            
            if (this.sortDirection === 'asc') {
                return aStr.localeCompare(bStr);
            } else {
                return bStr.localeCompare(aStr);
            }
        });
    }
    
    /**
     * Sort by column
     */
    sort(columnKey) {
        if (this.sortColumn === columnKey) {
            this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            this.sortColumn = columnKey;
            this.sortDirection = 'asc';
        }
        this.render();
    }
    
    /**
     * Start inline editing
     */
    startEdit(rowId, fieldKey) {
        if (this.editingCell) {
            this.cancelEdit();
        }
        
        const cell = this.container.querySelector(`td[data-row-id="${rowId}"][data-field="${fieldKey}"]`);
        if (!cell) return;
        
        const col = this.columns.find(c => c.key === fieldKey);
        if (!col || !col.editable) return;
        
        const currentValue = cell.textContent.trim();
        this.editingCell = { cell, rowId, fieldKey, originalValue: currentValue };
        
        // Create input based on column type
        let input;
        if (col.editType === 'select' && col.editOptions) {
            input = document.createElement('select');
            input.className = 'w-full px-2 py-1 border border-border rounded-md text-foreground bg-background focus:outline-none focus:ring-2 focus:ring-primary/30';
            col.editOptions.forEach(opt => {
                const option = document.createElement('option');
                option.value = opt.value;
                option.textContent = opt.label;
                if (opt.value == currentValue) {
                    option.selected = true;
                }
                input.appendChild(option);
            });
        } else {
            input = document.createElement('input');
            input.type = col.editType || 'text';
            input.value = currentValue;
            input.className = 'w-full px-2 py-1 border border-border rounded-md text-foreground bg-background focus:outline-none focus:ring-2 focus:ring-primary/30';
        }
        
        cell.innerHTML = '';
        cell.appendChild(input);
        input.focus();
        
        // Handle save/cancel
        const handleKeyDown = (e) => {
            if (e.key === 'Enter') {
                this.saveEdit();
            } else if (e.key === 'Escape') {
                this.cancelEdit();
            }
        };
        
        input.addEventListener('blur', () => {
            // Small delay to allow click events to fire first
            setTimeout(() => {
                if (this.editingCell && this.editingCell.fieldKey === fieldKey) {
                    this.saveEdit();
                }
            }, 200);
        });
        
        input.addEventListener('keydown', handleKeyDown);
    }
    
    /**
     * Save inline edit
     */
    async saveEdit() {
        if (!this.editingCell) return;
        
        const { cell, rowId, fieldKey, originalValue } = this.editingCell;
        const input = cell.querySelector('input, select');
        if (!input) return;
        
        const newValue = input.value;
        const col = this.columns.find(c => c.key === fieldKey);
        
        // If value didn't change, just cancel
        if (newValue === originalValue) {
            this.cancelEdit();
            return;
        }
        
        // Call onEdit callback if provided
        if (col && col.onEdit) {
            try {
                await col.onEdit(rowId, fieldKey, newValue);
                // Update data
                const row = this.data.find(r => (r.id || r) === rowId);
                if (row) {
                    row[fieldKey] = newValue;
                }
                this.editingCell = null; // Clear editing state
                // Re-render to show updated value
                this.render();
            } catch (error) {
                console.error('Edit failed:', error);
                // Show error, keep editing
                input.classList.add('border-destructive');
                setTimeout(() => input.classList.remove('border-destructive'), 2000);
                // Don't cancel edit on error
            }
        } else {
            // No callback, just update display
            const row = this.data.find(r => (r.id || r) === rowId);
            if (row) {
                row[fieldKey] = newValue;
            }
            this.editingCell = null; // Clear editing state
            this.render();
        }
    }
    
    /**
     * Cancel inline edit
     */
    cancelEdit() {
        if (!this.editingCell) return;
        
        const { cell, originalValue } = this.editingCell;
        cell.textContent = originalValue;
        this.editingCell = null;
    }
    
    /**
     * Handle row click
     */
    handleRowClick(rowId) {
        if (this.onRowClick) {
            // Try to find row in sorted data first, then fall back to original data
            const row = (this._sortedData || this.data).find(r => {
                const rId = r.id !== undefined ? r.id : r;
                return rId == rowId;
            });
            if (row) {
                this.onRowClick(row);
            }
        }
    }
    
    /**
     * Handle edit action
     */
    handleEdit(rowId) {
        if (this.onEdit) {
            const row = this.data.find(r => (r.id || r) === rowId);
            if (row) {
                this.onEdit(row);
            }
        }
    }
    
    /**
     * Handle delete action
     */
    handleDelete(rowId) {
        if (this.onDelete) {
            const row = this.data.find(r => (r.id || r) === rowId);
            if (row) {
                this.onDelete(row);
            }
        }
    }
    
    /**
     * Toggle actions menu for a row
     */
    toggleActionsMenu(rowId, event) {
        event.stopPropagation();
        
        // Close if clicking the same row
        if (this.openMenuRowId === rowId) {
            this.closeActionsMenu();
            return;
        }
        
        // Close any other open menu
        this.closeActionsMenu();
        
        // Find the button that was clicked
        const button = event.target.closest('button');
        if (!button) return;
        
        const buttonRect = button.getBoundingClientRect();
        this.openMenuRowId = rowId;
        
        // Get row data
        const row = this.data.find(r => (r.id || r) === rowId);
        if (!row) return;
        
        // Build menu items
        const menuItems = [];
        
        if (this.onEdit) {
            menuItems.push({
                label: 'Edit',
                icon: 'edit',
                onClick: () => this.handleEdit(rowId)
            });
        }
        
        if (this.onAction) {
            const actions = this.onAction(row);
            if (Array.isArray(actions)) {
                actions.forEach(action => {
                    menuItems.push({
                        label: action.title || action.key,
                        icon: action.icon || 'more-horizontal',
                        onClick: () => {
                            if (action.onClick) {
                                action.onClick(action.row || row);
                            } else {
                                this.handleCustomAction(action.key, rowId);
                            }
                            this.closeActionsMenu();
                        }
                    });
                });
            }
        }
        
        if (this.onDelete) {
            menuItems.push({
                label: 'Delete',
                icon: 'trash-2',
                onClick: () => {
                    this.handleDelete(rowId);
                    this.closeActionsMenu();
                },
                destructive: true
            });
        }
        
        if (menuItems.length === 0) return;
        
        // Create menu
        const menu = document.createElement('div');
        menu.className = 'absolute right-0 mt-1 w-48 bg-card border border-border rounded-lg shadow-lg z-50 py-1';
        menu.style.top = '100%';
        menu.style.left = 'auto';
        menu.style.right = '0';
        menu.setAttribute('data-menu-row-id', rowId);
        
        // Create menu items with direct event listeners
        menuItems.forEach((item, index) => {
            const button = document.createElement('button');
            button.className = `w-full flex items-center gap-2 px-4 py-2 text-sm ${item.destructive ? 'text-destructive hover:bg-destructive/10' : 'text-foreground hover:bg-muted/50'} transition-colors`;
            button.innerHTML = `<i data-lucide="${item.icon}" class="h-4 w-4"></i><span>${this.escapeHtml(item.label)}</span>`;
            
            // Add click handler directly
            button.addEventListener('click', (e) => {
                e.stopPropagation();
                if (item.onClick) {
                    item.onClick();
                }
                this.closeActionsMenu();
            });
            
            menu.appendChild(button);
        });
        
        // Insert menu after button
        button.parentElement.style.position = 'relative';
        button.parentElement.appendChild(menu);
        
        // Initialize Lucide icons
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
        
        // Close on outside click (but not on the button that opened it)
        // Use a small delay to avoid immediate closure
        setTimeout(() => {
            this._menuCloseHandler = (e) => {
                // Don't close if clicking inside the menu, the button, or any action cell
                const clickedCell = e.target.closest('[data-actions-cell]');
                const clickedBtn = e.target.closest('[data-actions-btn]');
                if (!menu.contains(e.target) && 
                    !button.contains(e.target) && 
                    clickedCell?.getAttribute('data-actions-cell') !== String(rowId) &&
                    clickedBtn?.getAttribute('data-actions-btn') !== String(rowId)) {
                    this.closeActionsMenu();
                }
            };
            // Use capture phase to catch events before they bubble
            document.addEventListener('click', this._menuCloseHandler, true);
        }, 100);
        
        // Close on escape
        this._menuEscapeHandler = (e) => {
            if (e.key === 'Escape') {
                this.closeActionsMenu();
            }
        };
        document.addEventListener('keydown', this._menuEscapeHandler);
    }
    
    /**
     * Close actions menu
     */
    closeActionsMenu() {
        if (this.openMenuRowId !== null) {
            const menu = this.container.querySelector(`[data-menu-row-id="${this.openMenuRowId}"]`);
            if (menu) {
                menu.remove();
            }
            this.openMenuRowId = null;
        }
        
        // Remove event listeners
        if (this._menuCloseHandler) {
            document.removeEventListener('click', this._menuCloseHandler);
            this._menuCloseHandler = null;
        }
        if (this._menuEscapeHandler) {
            document.removeEventListener('keydown', this._menuEscapeHandler);
            this._menuEscapeHandler = null;
        }
    }
    
    /**
     * Handle custom action
     */
    handleCustomAction(actionKey, rowId) {
        if (this.onAction) {
            const row = this.data.find(r => (r.id || r) === rowId);
            if (row) {
                const actions = this.onAction(row);
                const action = Array.isArray(actions) ? actions.find(a => a.key === actionKey) : null;
                if (action) {
                    if (action.onClick) {
                        action.onClick(action.row || row);
                    } else if (actionKey === 'activate' && window.Users) {
                        window.Users.handleActivateUser(rowId);
                    } else if (actionKey === 'privileges' && window.Users) {
                        window.Users.showPrivilegesModal(rowId);
                    }
                }
            }
        }
    }
    
    /**
     * Update table data
     */
    updateData(newData) {
        this.data = newData;
        this.render();
    }
    
    /**
     * Set loading state
     */
    setLoading(loading) {
        this.loading = loading;
        this.render();
    }
    
    /**
     * Get loading HTML
     */
    getLoadingHTML() {
        return `
            <div class="flex items-center justify-center py-12">
                <div class="text-center">
                    <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
                    <p class="mt-4 text-sm text-muted-foreground">Loading...</p>
                </div>
            </div>
        `;
    }
    
    /**
     * Render search input
     */
    renderSearchInput() {
        const container = document.getElementById(this.searchInputContainer);
        if (!container) return;
        
        // Check if input already exists to preserve focus
        const existingInput = document.getElementById(`table-search-${this.containerId}`);
        const wasFocused = document.activeElement === existingInput;
        
        container.innerHTML = `
            <div class="relative w-full">
                <i data-lucide="search" class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground/60"></i>
                <input type="text" 
                    id="table-search-${this.containerId}"
                    placeholder="Search..."
                    value="${this.escapeHtml(this.searchTerm)}"
                    class="pl-9 pr-9 h-9 bg-muted/40 border-transparent text-sm focus:border-input focus:bg-muted/60 focus:ring-1 focus:ring-primary/30 rounded-md w-full px-3" />
                ${this.searchTerm ? `
                    <button id="table-search-clear-${this.containerId}"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground/60 hover:text-foreground">
                        <i data-lucide="x" class="h-4 w-4"></i>
                    </button>
                ` : ''}
            </div>
        `;
        
        // Initialize Lucide icons
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
        
        // Add event listeners
        const input = document.getElementById(`table-search-${this.containerId}`);
        if (input) {
            // Store reference to avoid losing focus
            this._searchInput = input;
            
            // Debounce search - update immediately but don't re-render input
            let timeout;
            input.addEventListener('input', (e) => {
                // Update search term immediately for instant feedback
                this.searchTerm = e.target.value;
                
                clearTimeout(timeout);
                timeout = setTimeout(() => {
                    // Only re-render table, not the search input
                    this.render();
                }, 300);
            });
            
            // Restore focus if it was focused before
            if (wasFocused) {
                setTimeout(() => input.focus(), 0);
            }
        }
        
        // Add clear button listener
        const clearBtn = document.getElementById(`table-search-clear-${this.containerId}`);
        if (clearBtn) {
            clearBtn.addEventListener('click', () => {
                this.clearSearch();
            });
        }
    }
    
    /**
     * Set search term and re-render
     */
    setSearchTerm(term) {
        this.searchTerm = term;
        // Update input value if it exists without recreating it
        if (this._searchInput) {
            this._searchInput.value = term;
        } else if (this.searchInputContainer) {
            this.renderSearchInput();
        }
        this.render();
    }
    
    /**
     * Clear search
     */
    clearSearch() {
        this.searchTerm = '';
        if (this._searchInput) {
            this._searchInput.value = '';
            this._searchInput.focus();
        }
        this.render();
    }
    
    /**
     * Get empty state HTML
     */
    getEmptyHTML(message = null) {
        const msg = message || this.emptyMessage;
        return `
            <div class="flex items-center justify-center py-12">
                <div class="text-center">
                    <i data-lucide="inbox" class="h-12 w-12 text-muted-foreground/50 mx-auto mb-4"></i>
                    <p class="text-sm text-muted-foreground">${this.escapeHtml(msg)}</p>
                </div>
            </div>
        `;
    }
    
    /**
     * Escape HTML
     */
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Store table instances globally for event handlers
if (!window.tableInstances) {
    window.tableInstances = {};
}
