/**
 * BNote Next Generation - Instrument Grouping Utility
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
 * Instrument Grouping Utility
 * Provides functions to group participants by individual instruments or by instrument category
 */

const InstrumentGrouping = {
    /**
     * Group participants by individual instruments (current behavior)
     * @param {Array} participantsByInstrument - Array of instrument groups
     * @returns {Array} Grouped participants (unchanged structure)
     */
    groupByInstrument(participantsByInstrument) {
        return participantsByInstrument;
    },

    /**
     * Group participants by instrument category
     * Combines all instruments within the same category
     * @param {Array} participantsByInstrument - Array of instrument groups
     * @returns {Array} Participants grouped by category
     */
    groupByCategory(participantsByInstrument) {
        if (!participantsByInstrument || participantsByInstrument.length === 0) {
            return [];
        }

        // Map to store categories and their aggregated data
        const categoryMap = new Map();

        participantsByInstrument.forEach(group => {
            const categoryId = group.instrument?.category?.id || 0;
            const categoryName = group.instrument?.category?.name || 'Uncategorized';

            if (!categoryMap.has(categoryId)) {
                // Initialize category group
                categoryMap.set(categoryId, {
                    category: {
                        id: categoryId,
                        name: categoryName
                    },
                    participants: [],
                    stats: {
                        yes: 0,
                        maybe: 0,
                        no: 0,
                        pending: 0
                    },
                    instruments: [] // Track which instruments are in this category
                });
            }

            const categoryGroup = categoryMap.get(categoryId);
            
            // Add all participants from this instrument
            categoryGroup.participants.push(...group.participants);
            
            // Aggregate stats
            categoryGroup.stats.yes += group.stats.yes || 0;
            categoryGroup.stats.maybe += group.stats.maybe || 0;
            categoryGroup.stats.no += group.stats.no || 0;
            categoryGroup.stats.pending += group.stats.pending || 0;
            
            // Track instrument
            categoryGroup.instruments.push({
                id: group.instrument.id,
                name: group.instrument.name
            });
        });

        // Convert map to array and sort by category name
        const result = Array.from(categoryMap.values()).map(group => {
            // Calculate total for stats
            const total = (group.stats.yes || 0) + (group.stats.maybe || 0) + (group.stats.no || 0) + (group.stats.pending || 0);
            return {
                category: group.category,
                participants: group.participants,
                stats: {
                    ...group.stats,
                    total: total
                },
                instruments: group.instruments
            };
        });

        // Sort by category name
        result.sort((a, b) => {
            const nameA = a.category.name || '';
            const nameB = b.category.name || '';
            return nameA.localeCompare(nameB);
        });

        return result;
    },

    /**
     * Group participants based on grouping mode
     * @param {Array} participantsByInstrument - Array of instrument groups
     * @param {string} mode - 'instrument' or 'category'
     * @returns {Array} Grouped participants
     */
    group(participantsByInstrument, mode = 'category') {
        if (mode === 'category') {
            return this.groupByCategory(participantsByInstrument);
        } else {
            return this.groupByInstrument(participantsByInstrument);
        }
    },

    /**
     * Get display name for a group (instrument name or category name)
     * @param {Object} group - Group object (either instrument or category group)
     * @param {string} mode - 'instrument' or 'category'
     * @returns {string} Display name
     */
    getGroupName(group, mode) {
        if (mode === 'category') {
            return group.category?.name || 'Uncategorized';
        } else {
            return group.instrument?.name || 'Unknown';
        }
    },

    /**
     * Get group identifier (for React keys or similar)
     * @param {Object} group - Group object
     * @param {string} mode - 'instrument' or 'category'
     * @returns {string} Unique identifier
     */
    getGroupId(group, mode) {
        if (mode === 'category') {
            return `category-${group.category?.id || 0}`;
        } else {
            return `instrument-${group.instrument?.id || 0}`;
        }
    }
};
