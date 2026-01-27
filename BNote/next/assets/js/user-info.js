/**
 * Reusable User Info Component
 * Displays user avatar (initials) and name in the top-right corner.
 * Same layout and styling on every screen; initials match event-detail participant style.
 */
const UserInfo = {
    /**
     * Initialize user info component
     * @param {Object} session - Session object with user data
     */
    async init(session) {
        if (!session || !session.user) {
            console.warn('UserInfo: No session or user data provided');
            return;
        }

        const user = session.user;
        const userNameEl = document.getElementById('user-name');
        const userInitialsEl = document.getElementById('user-initials');
        const userAvatarEl = document.getElementById('user-avatar');

        if (userNameEl) {
            const fullName = [user.name, user.surname].filter(Boolean).join(' ') || 'User';
            userNameEl.textContent = fullName;
        }

        if (userInitialsEl) {
            const initials = this.getInitials(user.name, user.surname);
            userInitialsEl.textContent = initials;
        }

        if (userAvatarEl) {
            userAvatarEl.style.display = '';
        }
    },

    /**
     * Get initials from name and surname (same logic as event-detail participant overview)
     * @param {string} name - First name
     * @param {string} surname - Last name
     * @returns {string} Initials (e.g., "SK" for "Stefan Kreminski")
     */
    getInitials(name, surname) {
        const first = (name || '').charAt(0).toUpperCase();
        const last = (surname || '').charAt(0).toUpperCase();
        return (first + last) || 'U';
    },

    /**
     * Render user info HTML (for pages that don't have it)
     * @returns {string} HTML string
     */
    renderHTML() {
        return `
            <div id="user-info-container" class="flex items-center gap-2 ml-auto">
                <div class="h-6 w-px bg-border/30 hidden sm:block"></div>
                <button
                    class="user-info-btn flex items-center gap-3 pl-3 pr-2 py-1 rounded-lg hover:bg-muted/50 transition-colors group"
                    onclick="Auth.logout()">
                    <div id="user-avatar" class="user-info-initials h-8 w-8 rounded-full border-2 border-primary/20 bg-primary/10 text-primary text-xs font-semibold flex items-center justify-center shrink-0">
                        <span id="user-initials">U</span>
                    </div>
                    <div class="hidden sm:block text-left">
                        <p class="text-xs font-semibold leading-tight text-foreground" id="user-name">User</p>
                    </div>
                </button>
            </div>
        `;
    }
};
