/**
 * BNote Next Generation - Configuration
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
 * BNote Configuration
 * Global configuration object for BNote Next Generation
 */
const BNoteConfig = (function() {
    /**
     * Calculate the path to the BNote folder (sibling of bnote-next-generation)
     * @returns {string} Path to BNote folder (e.g., '/bnote/BNote/')
     */
    function calculateBNotePath() {
        const pathname = window.location.pathname;
        
        // Find the bnote-next-generation folder in the path
        const bnoteNextGenSlashIndex = pathname.indexOf('/bnote-next-generation/');
        const bnoteNextGenIndex = pathname.indexOf('/bnote-next-generation');
        
        // Fallback for old paths (backward compatibility)
        const bnoteNextSlashIndex = pathname.indexOf('/BNoteNext/');
        const bnoteNextIndex = pathname.indexOf('/BNoteNext');
        const nextSlashIndex = pathname.indexOf('/next/');
        const nextIndex = pathname.indexOf('/next');
        
        let basePath;
        
        if (bnoteNextGenSlashIndex !== -1) {
            // Found '/bnote-next-generation/' - get path up to (but not including) this folder
            basePath = pathname.substring(0, bnoteNextGenSlashIndex);
        } else if (bnoteNextGenIndex !== -1) {
            // Found '/bnote-next-generation' without trailing slash
            basePath = pathname.substring(0, bnoteNextGenIndex);
        } else if (bnoteNextSlashIndex !== -1) {
            // Backward compatibility: /BNoteNext/
            basePath = pathname.substring(0, bnoteNextSlashIndex);
        } else if (bnoteNextIndex !== -1) {
            // Backward compatibility: /BNoteNext
            basePath = pathname.substring(0, bnoteNextIndex);
        } else if (nextSlashIndex !== -1) {
            // Backward compatibility: /next/
            basePath = pathname.substring(0, nextSlashIndex);
        } else if (nextIndex !== -1) {
            // Backward compatibility: /next
            basePath = pathname.substring(0, nextIndex);
        } else {
            // Fallback: try to find it in path parts
            const pathParts = pathname.split('/').filter(p => p && p !== '');
            const bnoteNextGenPos = pathParts.indexOf('bnote-next-generation');
            if (bnoteNextGenPos !== -1) {
                basePath = '/' + pathParts.slice(0, bnoteNextGenPos).join('/');
            } else {
                const nextPos = pathParts.indexOf('next');
                if (nextPos !== -1) {
                    basePath = '/' + pathParts.slice(0, nextPos).join('/');
                } else {
                    // Last resort: assume we're at root
                    basePath = '';
                }
            }
        }
        
        // Ensure basePath ends with / and add BNote folder
        const bnotePath = (basePath.endsWith('/') ? basePath : basePath + '/') + 'BNote/';
        return bnotePath;
    }
    
    return {
        /**
         * Path to the BNote folder (sibling of bnote-next-generation)
         * @type {string}
         */
        get bnotePath() {
            if (!this._bnotePath) {
                this._bnotePath = calculateBNotePath();
            }
            return this._bnotePath;
        },
        
        /**
         * Get a URL to a resource in the BNote folder
         * @param {string} resourcePath Path relative to BNote folder (e.g., 'style/images/logo.svg')
         * @returns {string} Full path to the resource
         */
        getBNoteResource(resourcePath) {
            // Remove leading slash if present
            const cleanPath = resourcePath.startsWith('/') ? resourcePath.substring(1) : resourcePath;
            return this.bnotePath + cleanPath;
        }
    };
})();
