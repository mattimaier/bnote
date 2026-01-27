/**
 * Reusable Badge Component
 * Provides flexible badge rendering with arbitrary text and colors
 */
const Badge = {
    /**
     * Render a badge with custom text and color
     * @param {string} text - Display text
     * @param {string} color - Color name ('success', 'destructive', 'primary', 'accent', 'warning', etc.)
     * @param {string|null} variant - Optional variant class (overrides color)
     * @returns {string} HTML string for badge
     */
    render(text, color = 'primary', variant = null) {
        // Map color names to CSS variables and variant classes
        const colorMap = {
            'success': { cssVar: 'var(--accent)', variant: 'accent' },
            'active': { cssVar: 'var(--accent)', variant: 'accent' },
            'destructive': { cssVar: 'var(--destructive)', variant: null, useInline: true },
            'inactive': { cssVar: 'var(--destructive)', variant: null, useInline: true },
            'primary': { cssVar: 'var(--primary)', variant: null },
            'accent': { cssVar: 'var(--accent)', variant: 'accent' },
            'warning': { cssVar: 'var(--chart-3)', variant: 'chart-3' },
            'info': { cssVar: 'var(--primary)', variant: null }
        };

        // Use variant if provided, otherwise map from color
        let badgeVariant = variant;
        let badgeColor = color;
        let useInline = false;

        if (!badgeVariant && colorMap[color]) {
            badgeVariant = colorMap[color].variant;
            badgeColor = colorMap[color].cssVar;
            useInline = colorMap[color].useInline || false;
        }

        // Build class string
        let classes = 'event-badge';
        if (badgeVariant) {
            classes += ` ${badgeVariant}`;
        }

        // If no variant and color needs inline style (like destructive), use inline style
        let style = '';
        if (useInline || (!badgeVariant && !colorMap[color])) {
            // Use inline styles for destructive or unknown colors
            const colorValue = badgeColor || color;
            if (colorValue.startsWith('var(') || colorValue.startsWith('#')) {
                style = ` style="color: ${colorValue}; border-color: color-mix(in oklab, ${colorValue} 20%, transparent); background-color: color-mix(in oklab, ${colorValue} 10%, transparent);"`;
            } else if (!colorMap[color]) {
                // Default to primary if unknown
                style = '';
            }
        }

        return `<span class="${classes}"${style}>${this.escapeHtml(text)}</span>`;
    },

    /**
     * Convenience method for status badges (active/inactive)
     * @param {boolean} isActive - Whether the status is active
     * @returns {string} HTML string for status badge
     */
    renderStatus(isActive) {
        if (isActive) {
            return this.render('Active', 'success');
        } else {
            return this.render('Inactive', 'destructive');
        }
    },

    /**
     * Escape HTML to prevent XSS
     * @param {string} text - Text to escape
     * @returns {string} Escaped text
     */
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
};
