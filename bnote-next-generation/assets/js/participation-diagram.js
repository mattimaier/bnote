/**
 * BNote Next Generation - Participation Diagram Component
 *
 * Copyright (C) 2026 BNote Contributors
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * Participation Diagram Component
 * Renders horizontal bar chart showing participation statistics
 * Uses centralized colors from ParticipationConstants
 */
const ParticipationDiagram = {
    /**
     * Render participation diagram
     * @param {HTMLElement} container - Container element to render into
     * @param {Object} stats - Participation statistics
     * @param {number} stats.yes - Number of yes responses
     * @param {number} stats.maybe - Number of maybe responses
     * @param {number} stats.no - Number of no responses
     * @param {number} stats.pending - Number of pending responses
     * @param {number} stats.total - Total number of participants
     */
    render(container, stats) {
        if (!container) return;

        const { yes = 0, maybe = 0, no = 0, pending = 0, total = 0 } = stats;

        if (total === 0) {
            container.innerHTML = `
                <div class="text-center py-4 text-muted-foreground text-sm">
                    No participants yet
                </div>
            `;
            return;
        }

        // Calculate percentages
        const percentages = {
            yes: total > 0 ? (yes / total) * 100 : 0,
            maybe: total > 0 ? (maybe / total) * 100 : 0,
            no: total > 0 ? (no / total) * 100 : 0,
            pending: total > 0 ? (pending / total) * 100 : 0
        };

        // Build segments array (order: yes, maybe, no, pending)
        // Use centralized colors from ParticipationConstants
        const segments = [];
        if (yes > 0) {
            segments.push({
                color: ParticipationConstants.colors.yes.bg,
                count: yes,
                percentage: percentages.yes,
                label: 'Yes'
            });
        }
        if (maybe > 0) {
            segments.push({
                color: ParticipationConstants.colors.maybe.bg,
                count: maybe,
                percentage: percentages.maybe,
                label: 'Maybe'
            });
        }
        if (no > 0) {
            segments.push({
                color: ParticipationConstants.colors.no.bg,
                count: no,
                percentage: percentages.no,
                label: 'No'
            });
        }
        if (pending > 0) {
            segments.push({
                color: ParticipationConstants.colors.pending.bg,
                count: pending,
                percentage: percentages.pending,
                label: 'Pending'
            });
        }

        // Render diagram
        // Fix: Only first segment has rounded left, only last segment has rounded right
        // Internal boundaries are straight (no rounding)
        const segmentsHtml = segments.map((segment, index) => {
            const isFirst = index === 0;
            const isLast = index === segments.length - 1;

            // Calculate minimum percentage width needed to display the number comfortably
            // For single-digit numbers, need at least ~4% width; for double-digit, ~5%
            // This ensures numbers are readable without causing layout issues
            const minWidthPercent = segment.count >= 10 ? 5 : 4;

            // Only apply rounded corners to outer edges
            let roundedClasses = '';
            if (isFirst && isLast) {
                // Single segment: round both sides
                roundedClasses = 'rounded-l-lg rounded-r-lg';
            } else if (isFirst) {
                // First segment: round left only
                roundedClasses = 'rounded-l-lg';
            } else if (isLast) {
                // Last segment: round right only
                roundedClasses = 'rounded-r-lg';
            }
            // Middle segments: no rounding

            // Only show number if segment is wide enough to display it comfortably
            // Always show if it's the only segment or if percentage is sufficient
            const showNumber = segments.length === 1 || segment.percentage >= minWidthPercent;

            return `
                <div 
                    class="${segment.color} h-8 flex items-center justify-center text-white text-xs font-semibold ${roundedClasses}"
                    style="width: ${segment.percentage}%; flex-shrink: 0;"
                    title="${segment.label}: ${segment.count}"
                >
                    ${showNumber ? `<span class="whitespace-nowrap leading-none">${segment.count}</span>` : ''}
                </div>
            `;
        }).join('');

        container.innerHTML = `
            <div class="participation-diagram">
                <div class="flex items-center gap-0 rounded-lg overflow-hidden bg-muted shadow-sm">
                    ${segmentsHtml || '<div class="h-8 w-full flex items-center justify-center text-muted-foreground text-xs">No responses</div>'}
                </div>
            </div>
        `;
    }
};
