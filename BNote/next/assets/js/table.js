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
        
        // State
        this.sortColumn = null;
        this.sortDirection = 'asc';
        this.editingCell = null;
        
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
        
        if (this.data.length === 0) {
            this.container.innerHTML = this.getEmptyHTML();
            return;
        }
        
        // Apply sorting
        const sortedData = this.sortData([...this.data]);
        
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
            
            html += `<th class="px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider ${sortable ? 'cursor-pointer hover:bg-muted/50' : ''}"`;
            if (sortable) {
                html += ` onclick="window.tableInstances['${this.containerId}'].sort('${col.key}')"`;
            }
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
        
        html += '</tbody>';
        html += '</table>';
        html += '</div>';
        
        this.container.innerHTML = html;
        
        // Initialize Lucide icons if available
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    }
    
    /**
     * Render a single row
     */
    renderRow(row, rowIndex) {
        const rowId = row.id || rowIndex;
        let html = `<tr class="border-b border-border/20 hover:bg-muted/30 transition-colors" data-row-id="${rowId}"`;
        
        if (this.onRowClick) {
            html += ` onclick="window.tableInstances['${this.containerId}'].handleRowClick(${rowId})" style="cursor: pointer;"`;
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
        
        // Actions cell
        if (this.onEdit || this.onDelete || this.onAction) {
            html += '<td class="px-4 py-3 text-sm" onclick="event.stopPropagation();">';
            html += '<div class="flex items-center gap-2">';
            
            if (this.onEdit) {
                html += `<button onclick="window.tableInstances['${this.containerId}'].handleEdit(${rowId})" class="text-primary hover:text-primary/80 p-1 rounded hover:bg-primary/10" title="Edit">`;
                html += '<i data-lucide="edit" class="h-4 w-4"></i>';
                html += '</button>';
            }
            
            if (this.onDelete) {
                html += `<button onclick="window.tableInstances['${this.containerId}'].handleDelete(${rowId})" class="text-destructive hover:text-destructive/80 p-1 rounded hover:bg-destructive/10" title="Delete">`;
                html += '<i data-lucide="trash-2" class="h-4 w-4"></i>';
                html += '</button>';
            }
            
            if (this.onAction) {
                const actions = this.onAction(row, rowIndex);
                if (Array.isArray(actions)) {
                    actions.forEach(action => {
                        html += `<button onclick="window.tableInstances['${this.containerId}'].handleCustomAction('${action.key}', ${rowId})" class="${action.class || 'text-muted-foreground hover:text-foreground'} p-1 rounded hover:bg-muted/50" title="${action.title || ''}">`;
                        html += `<i data-lucide="${action.icon || 'more-horizontal'}" class="h-4 w-4"></i>`;
                        html += '</button>';
                    });
                }
            }
            
            html += '</div>';
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
     * Sort data
     */
    sortData(data) {
        if (!this.sortColumn) {
            return data;
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
            const row = this.data.find(r => (r.id || r) === rowId);
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
     * Get empty state HTML
     */
    getEmptyHTML() {
        return `
            <div class="flex items-center justify-center py-12">
                <div class="text-center">
                    <i data-lucide="inbox" class="h-12 w-12 text-muted-foreground/50 mx-auto mb-4"></i>
                    <p class="text-sm text-muted-foreground">${this.escapeHtml(this.emptyMessage)}</p>
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
