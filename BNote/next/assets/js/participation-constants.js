/**
 * Participation Constants
 * Centralized colors and icons for participation status throughout the app
 * Ensures consistent UX across dashboard, event details, and all components
 */

const ParticipationConstants = {
    /**
     * Traffic light colors for participation status
     * Used consistently across all components
     */
    colors: {
        yes: {
            bg: 'bg-green-500',
            bgLight: 'bg-green-50',
            border: 'border-green-500',
            borderLight: 'border-green-200',
            text: 'text-green-600',
            textWhite: 'text-white',
            // CSS variable fallback
            hex: '#22c55e', // green-500
            hexLight: '#f0fdf4' // green-50
        },
        maybe: {
            bg: 'bg-amber-400',
            bgLight: 'bg-amber-50',
            border: 'border-amber-400',
            borderLight: 'border-amber-200',
            text: 'text-amber-600',
            textWhite: 'text-white',
            hex: '#fbbf24', // amber-400
            hexLight: '#fffbeb' // amber-50
        },
        no: {
            bg: 'bg-red-500',
            bgLight: 'bg-red-50',
            border: 'border-red-500',
            borderLight: 'border-red-200',
            text: 'text-red-600',
            textWhite: 'text-white',
            hex: '#ef4444', // red-500
            hexLight: '#fef2f2' // red-50
        },
        pending: {
            bg: 'bg-gray-400',
            bgLight: 'bg-gray-50',
            border: 'border-gray-400',
            borderLight: 'border-gray-200',
            text: 'text-gray-400',
            textWhite: 'text-gray-600',
            hex: '#9ca3af', // gray-400
            hexLight: '#f9fafb' // gray-50
        }
    },

    /**
     * Icon names for Lucide icons
     * Used consistently across all components
     */
    icons: {
        yes: 'check-circle',
        maybe: 'help-circle',
        no: 'x-circle',
        pending: 'help-circle'
    },

    /**
     * Get color configuration for a participation status
     * @param {number|null} participate - Participation status (1=yes, 0=no, 2=maybe, null=pending)
     * @returns {Object} Color configuration object
     */
    getColors(participate) {
        if (participate === null || participate === undefined || participate < 0) {
            return this.colors.pending;
        }
        
        switch (parseInt(participate)) {
            case 1:
                return this.colors.yes;
            case 2:
                return this.colors.maybe;
            case 0:
                return this.colors.no;
            default:
                return this.colors.pending;
        }
    },

    /**
     * Get icon name for a participation status
     * @param {number|null} participate - Participation status (1=yes, 0=no, 2=maybe, null=pending)
     * @returns {string} Icon name for Lucide
     */
    getIcon(participate) {
        if (participate === null || participate === undefined || participate < 0) {
            return this.icons.pending;
        }
        
        switch (parseInt(participate)) {
            case 1:
                return this.icons.yes;
            case 2:
                return this.icons.maybe;
            case 0:
                return this.icons.no;
            default:
                return this.icons.pending;
        }
    },

    /**
     * Get status label for a participation status
     * @param {number|null} participate - Participation status
     * @returns {string} Human-readable label
     */
    getLabel(participate) {
        if (participate === null || participate === undefined || participate < 0) {
            return 'Pending';
        }
        
        switch (parseInt(participate)) {
            case 1:
                return 'Yes';
            case 2:
                return 'Maybe';
            case 0:
                return 'No';
            default:
                return 'Pending';
        }
    },

    /**
     * Map status string to participation number
     * Used for API calls
     * @param {string} status - Status string ('yes', 'maybe', 'no', 'undecided')
     * @returns {number|null} Participation number or null for undecided
     */
    mapStatusToNumber(status) {
        switch (status) {
            case 'yes':
                return 1;
            case 'maybe':
                return 2;
            case 'no':
                return 0;
            case 'undecided':
            default:
                return null;
        }
    }
};
