/**
 * Entity Configuration Loader
 * 
 * Loads entity colors and icons from config/entity-config.json
 * Provides helper functions to get entity configurations with auto-generated variants
 */

const EntityConfig = {
    _config: null,
    _loading: false,
    _loadPromise: null,

    /**
     * Load entity configuration from JSON file
     * @returns {Promise<Object>} Configuration object
     */
    async load() {
        if (this._config) {
            return this._config;
        }

        if (this._loading) {
            return this._loadPromise;
        }

        this._loading = true;
        this._loadPromise = fetch('config/entity-config.json')
            .then(response => {
                if (!response.ok) {
                    throw new Error(`Failed to load entity config: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                this._config = data;
                this._loading = false;
                return this._config;
            })
            .catch(error => {
                console.error('Error loading entity config:', error);
                this._loading = false;
                // Return empty config as fallback
                this._config = { entities: {} };
                return this._config;
            });

        return this._loadPromise;
    },

    /**
     * Get entity configuration
     * @param {string} entityType - Entity type (e.g., 'rehearsal', 'contact')
     * @returns {Object|null} Entity configuration with color and icon, or null if not found
     */
    getEntityConfig(entityType) {
        if (!this._config || !this._config.entities) {
            console.warn('Entity config not loaded. Call load() first or ensure config is loaded.');
            return null;
        }

        return this._config.entities[entityType] || null;
    },

    /**
     * Get icon name for an entity
     * @param {string} entityType - Entity type
     * @returns {string} Icon name for Lucide icons, or 'circle' as fallback
     */
    getIcon(entityType) {
        const config = this.getEntityConfig(entityType);
        return config?.icon || 'circle';
    },

    /**
     * Get color for an entity
     * @param {string} entityType - Entity type
     * @returns {string} Color value (hex or oklch), or null if not found
     */
    getColor(entityType) {
        const config = this.getEntityConfig(entityType);
        return config?.color || null;
    },

    /**
     * Convert hex color to RGB for color-mix calculations
     * @param {string} hex - Hex color string
     * @returns {string} RGB color string
     */
    _hexToRgb(hex) {
        const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
        return result ?
            `rgb(${parseInt(result[1], 16)}, ${parseInt(result[2], 16)}, ${parseInt(result[3], 16)})` :
            hex;
    },

    /**
     * Generate light variant color using color-mix
     * @param {string} color - Base color (hex or oklch)
     * @param {number} opacity - Opacity percentage (default: 10)
     * @returns {string} Color-mix string for CSS
     */
    getLightColor(color, opacity = 10) {
        if (!color) return 'transparent';

        // If it's already an oklch color, use it directly
        if (color.startsWith('oklch')) {
            return `color-mix(in oklab, ${color} ${opacity}%, transparent)`;
        }

        // Convert hex to RGB for color-mix
        const rgbColor = this._hexToRgb(color);
        return `color-mix(in oklab, ${rgbColor} ${opacity}%, transparent)`;
    },

    /**
     * Generate muted variant color using color-mix
     * @param {string} color - Base color (hex or oklch)
     * @param {number} opacity - Opacity percentage (default: 20)
     * @returns {string} Color-mix string for CSS
     */
    getMutedColor(color, opacity = 20) {
        if (!color) return 'transparent';

        // If it's already an oklch color, use it directly
        if (color.startsWith('oklch')) {
            return `color-mix(in oklab, ${color} ${opacity}%, transparent)`;
        }

        // Convert hex to RGB for color-mix
        const rgbColor = this._hexToRgb(color);
        return `color-mix(in oklab, ${rgbColor} ${opacity}%, transparent)`;
    },

    /**
     * Get Tailwind-compatible color classes for an entity
     * Note: This is a helper that returns approximate Tailwind classes.
     * For exact color matching, use inline styles with getColor() and getLightColor().
     * 
     * @param {string} entityType - Entity type
     * @returns {Object} Object with iconBg, iconColor, badgeClass properties
     */
    getTailwindClasses(entityType) {
        const config = this.getEntityConfig(entityType);
        if (!config) {
            return {
                iconBg: 'bg-muted',
                iconColor: 'text-muted-foreground',
                badgeClass: 'bg-muted text-muted-foreground'
            };
        }

        const color = config.color;

        // Map colors to approximate Tailwind classes
        // This is a fallback - for exact colors, use inline styles
        let colorClass = 'blue';
        if (color.includes('#3399FF') || color.includes('primary')) {
            colorClass = 'blue';
        } else if (color.includes('#A855F7') || color.includes('purple')) {
            colorClass = 'purple';
        } else if (color.includes('#F97316') || color.includes('orange')) {
            colorClass = 'orange';
        } else if (color.includes('#25A65A') || color.includes('green') || color.includes('success')) {
            colorClass = 'green';
        } else if (color.includes('#6366F1') || color.includes('indigo')) {
            colorClass = 'indigo';
        } else if (color.includes('#6B7280') || color.includes('gray')) {
            colorClass = 'gray';
        }

        return {
            iconBg: `bg-${colorClass}-500/20`,
            iconColor: `text-${colorClass}-600 dark:text-${colorClass}-400`,
            badgeClass: `bg-${colorClass}-500/20 text-${colorClass}-700 dark:text-${colorClass}-300`
        };
    },

    /**
     * Get complete configuration for list items (matching search-results.js format)
     * @param {string} entityType - Entity type
     * @param {Function} t - Translation function (optional)
     * @returns {Object} Complete configuration object
     */
    getListItemConfig(entityType, t) {
        const config = this.getEntityConfig(entityType);
        if (!config) {
            return {
                icon: 'circle',
                iconBg: 'bg-muted',
                iconColor: 'text-muted-foreground',
                badgeClass: 'bg-muted text-muted-foreground',
                label: entityType
            };
        }

        const tailwindClasses = this.getTailwindClasses(entityType);
        const label = t ? t(`js.search.results.${entityType}`) || entityType : entityType;

        return {
            icon: config.icon,
            iconBg: tailwindClasses.iconBg,
            iconColor: tailwindClasses.iconColor,
            badgeClass: tailwindClasses.badgeClass,
            label: label
        };
    },

    /**
     * Get event type configuration (matching event-renderer.js format)
     * @param {string} eventType - Event type ('rehearsal', 'performance', 'meeting')
     * @param {Function} getLabel - Label function (optional)
     * @returns {Object} Event configuration object
     */
    getEventConfig(eventType, getLabel) {
        // Map event types to entity types
        const entityTypeMap = {
            'rehearsal': 'rehearsal',
            'performance': 'concert',
            'meeting': 'meeting'
        };

        const entityType = entityTypeMap[eventType] || eventType;
        const config = this.getEntityConfig(entityType);

        if (!config) {
            // Fallback to meeting config
            const meetingConfig = this.getEntityConfig('meeting');
            return {
                badgeClass: 'event-badge chart-3',
                dotClass: 'bg-chart-3',
                label: getLabel ? getLabel('js.event.meeting') : 'Meeting',
                icon: meetingConfig?.icon || 'users'
            };
        }

        const color = config.color;

        // Determine badge and dot classes based on color
        let badgeClass = 'event-badge';
        let dotClass = 'bg-primary';

        if (color.includes('#3399FF') || color.includes('primary')) {
            badgeClass = 'event-badge';
            dotClass = 'bg-primary';
        } else if (color.includes('oklch(0.68 0.20 80)') || color.includes('accent')) {
            badgeClass = 'event-badge accent';
            dotClass = 'bg-accent';
        } else if (color.includes('oklch(0.62 0.18 150)') || color.includes('chart-3')) {
            badgeClass = 'event-badge chart-3';
            dotClass = 'bg-chart-3';
        } else {
            // Use inline style for custom colors
            badgeClass = 'event-badge';
            dotClass = ''; // Will need inline style
        }

        const label = getLabel ? getLabel(`js.event.${eventType}`) : eventType;

        return {
            badgeClass: badgeClass,
            dotClass: dotClass,
            label: label,
            icon: config.icon,
            color: color // Include color for inline styles if needed
        };
    }
};

// Auto-load config when module loads (non-blocking)
EntityConfig.load().catch(err => {
    console.warn('Entity config will be loaded on first use:', err);
});
