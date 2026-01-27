/**
 * Reusable User Info Component
 * Displays user avatar, name, and role in the top-right corner
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
        const userRoleEl = document.getElementById('user-role');
        const userAvatarEl = document.getElementById('user-avatar');

        // Update user name
        if (userNameEl) {
            const fullName = [user.name, user.surname].filter(Boolean).join(' ') || 'User';
            userNameEl.textContent = fullName;
        }

        // Update user initials
        if (userInitialsEl) {
            const initials = this.getInitials(user.name, user.surname);
            userInitialsEl.textContent = initials;
        }

        // Role is already translated via data-i18n attribute, just ensure it's visible
        if (userRoleEl) {
            userRoleEl.style.display = '';
        }

        // Ensure avatar is visible
        if (userAvatarEl) {
            userAvatarEl.style.display = '';
        }
    },

    /**
     * Get initials from name and surname
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
            <div class="flex items-center gap-2 ml-auto">
                <div class="h-6 w-px bg-border/30 hidden sm:block"></div>
                <button
                    class="flex items-center gap-3 pl-3 pr-2 py-1 rounded-lg hover:bg-muted/50 transition-colors group"
                    onclick="Auth.logout()">
                    <div class="h-8 w-8 rounded-full bg-primary/10 text-primary font-semibold text-xs flex items-center justify-center ring-1.5 ring-primary/20 group-hover:ring-primary/40 transition-all"
                        id="user-avatar">
                        <span id="user-initials">U</span>
                    </div>
                    <div class="hidden sm:block text-left">
                        <p class="text-xs font-semibold leading-tight text-foreground" id="user-name">User</p>
                        <span
                            class="inline-flex items-center rounded-md border px-1.5 py-0.5 text-[10px] mt-0.5 bg-muted text-muted-foreground border-border"
                            id="user-role" data-i18n="js.common.member">Member</span>
                    </div>
                </button>
            </div>
        `;
    }
};
