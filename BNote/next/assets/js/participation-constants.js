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
            bg: 'bg-success',
            bgLight: 'bg-success-muted',
            border: 'border-success',
            borderLight: 'border-success-muted',
            text: 'text-success',
            textWhite: 'text-white',
            // CSS variable fallback
            hex: '#25A65A', // success
            hexLight: '#CFEBDD' // success-muted
        },
        maybe: {
            bg: 'bg-warning',
            bgLight: 'bg-warning-muted',
            border: 'border-warning',
            borderLight: 'border-warning-muted',
            text: 'text-warning',
            textWhite: 'text-white',
            hex: '#FFAA1A', // warning
            hexLight: '#FFE4BF' // warning-muted
        },
        no: {
            bg: 'bg-danger',
            bgLight: 'bg-danger-muted',
            border: 'border-danger',
            borderLight: 'border-danger-muted',
            text: 'text-danger',
            textWhite: 'text-white',
            hex: '#E52B3C', // danger
            hexLight: '#FAD5DC' // danger-muted
        },
        pending: {
            bg: 'bg-gray-300',
            bgLight: 'bg-gray-50',
            border: 'border-gray-300',
            borderLight: 'border-gray-200',
            text: 'text-gray-500',
            textWhite: 'text-gray-600',
            hex: '#d1d5db', // gray-300 (lighter gray)
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
        pending: 'clock'
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
