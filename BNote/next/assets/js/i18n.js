/**
 * BNote Internationalization (i18n) Service
 * Provides translation and date/time formatting for the JavaScript app
 * Bridges PHP translations with JS-specific translations
 */

const i18n = {
    // Current language code
    currentLang: 'de',
    
    // Current country code (for locale formatting)
    currentCountry: null,
    
    // Cached translations
    translations: {},
    
    // Translation parameter placeholder (matches PHP)
    PARAMETER: '%p',
    
    /**
     * Initialize i18n service
     * @param {string} langCode Language code (de, en, es, fr)
     * @param {string|null} countryCode Optional country code (e.g., 'DE', 'US', 'ES', 'FR')
     */
    async init(langCode, countryCode = null) {
        this.currentLang = langCode || 'de';
        this.currentCountry = countryCode || null;
        
        try {
            // Load translations from API
            const response = await TranslationsApi.get(this.currentLang);
            
            // Handle response structure - could be {translations: {...}} or direct object
            if (response && typeof response === 'object') {
                if (response.translations && typeof response.translations === 'object') {
                    this.translations = response.translations;
                } else if (Array.isArray(response) || Object.keys(response).length > 0) {
                    // Response might be the translations object directly
                    this.translations = response;
                } else {
                    this.translations = {};
                }
            } else {
                this.translations = {};
            }
            
            // Debug: log if translations are empty
            if (Object.keys(this.translations).length === 0) {
                console.warn('i18n: No translations loaded for language:', this.currentLang, 'Response:', response);
            } else {
                console.log('i18n: Loaded', Object.keys(this.translations).length, 'translations for language:', this.currentLang);
                // Log a few sample keys to verify
                const sampleKeys = Object.keys(this.translations).slice(0, 5);
                console.log('i18n: Sample translation keys:', sampleKeys);
            }
            
            // Update HTML lang attribute with full locale if country is available
            if (document.documentElement) {
                if (this.currentCountry) {
                    document.documentElement.lang = `${this.currentLang}-${this.currentCountry}`;
                } else {
                    document.documentElement.lang = this.currentLang;
                }
            }
            
            // Dispatch event when translations are loaded
            window.dispatchEvent(new CustomEvent('i18n:loaded', { 
                detail: { lang: this.currentLang, country: this.currentCountry, translations: this.translations } 
            }));
        } catch (error) {
            console.error('Failed to load translations:', error);
            this.translations = {};
            // Still set HTML lang attribute
            if (document.documentElement) {
                if (this.currentCountry) {
                    document.documentElement.lang = `${this.currentLang}-${this.currentCountry}`;
                } else {
                    document.documentElement.lang = this.currentLang;
                }
            }
        }
    },
    
    /**
     * Translate a key with optional parameters
     * @param {string} key Translation key
     * @param {Array} params Optional parameters array
     * @returns {string} Translated text or key if not found
     */
    t(key, params = []) {
        let text = this.translations[key];
        
        // Fallback to key if translation not found
        if (!text) {
            return key;
        }
        
        // Replace placeholders with parameters
        if (params && params.length > 0) {
            let paramIndex = 0;
            text = text.replace(/%p/g, () => {
                const param = paramIndex < params.length ? params[paramIndex] : '';
                paramIndex++;
                return param;
            });
        }
        
        return text;
    },
    
    /**
     * Get current language code
     * @returns {string} Language code
     */
    getLang() {
        return this.currentLang;
    },
    
    /**
     * Change language and reload translations
     * @param {string} langCode Language code
     * @param {string|null} countryCode Optional country code
     */
    async setLang(langCode, countryCode = null) {
        await this.init(langCode, countryCode);
    },
    
    /**
     * Get browser locale from language code and country
     * Uses system config country if available, otherwise defaults
     * @param {string} langCode Language code
     * @returns {string} Browser locale code (e.g., 'de-DE', 'en-US')
     */
    getBrowserLocale(langCode) {
        // Use configured country if available
        if (this.currentCountry) {
            return `${langCode}-${this.currentCountry}`;
        }
        
        // Fallback to default country for language
        const localeMap = {
            'de': 'de-DE',
            'en': 'en-US',
            'es': 'es-ES',
            'fr': 'fr-FR'
        };
        return localeMap[langCode] || 'de-DE';
    },

    /**
     * Parse event date string (YYYY-MM-DD or YYYY-MM-DD HH:MM:SS) to Date.
     * Returns null on failure. Use for API date fields only; pass strings.
     * @param {string} str Date string from API
     * @returns {Date|null}
     */
    parseEventDate(str) {
        if (str == null || typeof str !== 'string') return null;
        const normalized = String(str).trim().replace(' ', 'T');
        if (!normalized) return null;
        const date = new Date(normalized);
        return isNaN(date.getTime()) ? null : date;
    },

    /**
     * Format date based on browser locale (short format: DD.MM.YYYY or MM/DD/YYYY).
     * @param {Date|string} date Date object or ISO string
     * @param {object} options Intl.DateTimeFormat options
     * @returns {string} Formatted date string
     */
    formatDate(date, options = {}) {
        const dateObj = date instanceof Date ? date : new Date(date);
        if (isNaN(dateObj.getTime())) {
            return '';
        }
        
        const locale = this.getBrowserLocale(this.currentLang);
        
        const defaultOptions = {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric'
        };
        
        return new Intl.DateTimeFormat(locale, { ...defaultOptions, ...options })
            .format(dateObj);
    },

    /**
     * Format time (hours and minutes only, no seconds). hour12 follows locale.
     * @param {Date|string} date Date object or ISO string
     * @param {object} options Intl.DateTimeFormat options
     * @returns {string}
     */
    formatTime(date, options = {}) {
        const dateObj = date instanceof Date ? date : new Date(date);
        if (isNaN(dateObj.getTime())) return '';
        const locale = this.getBrowserLocale(this.currentLang);
        const defaultOptions = { hour: 'numeric', minute: '2-digit' };
        return new Intl.DateTimeFormat(locale, { ...defaultOptions, ...options }).format(dateObj);
    },
    
    /**
     * Format date and time based on browser locale (short date, time without seconds).
     * @param {Date|string} date Date object or ISO string
     * @param {object} options Intl.DateTimeFormat options
     * @returns {string} Formatted date and time string
     */
    formatDateTime(date, options = {}) {
        const dateObj = date instanceof Date ? date : new Date(date);
        if (isNaN(dateObj.getTime())) {
            return '';
        }
        
        const locale = this.getBrowserLocale(this.currentLang);
        
        const defaultOptions = {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: 'numeric',
            minute: '2-digit'
        };
        
        return new Intl.DateTimeFormat(locale, { ...defaultOptions, ...options })
            .format(dateObj);
    },
    
    /**
     * Format date from database format (YYYY-MM-DD)
     * @param {string} dbDate Database date string (YYYY-MM-DD)
     * @returns {string} Formatted date string
     */
    formatDateFromDb(dbDate) {
        if (!dbDate) return '';
        // Handle both YYYY-MM-DD and YYYY-MM-DD HH:MM:SS formats
        const datePart = dbDate.split(' ')[0];
        const [year, month, day] = datePart.split('-');
        if (!year || !month || !day) return '';
        return this.formatDate(new Date(year, month - 1, day));
    },
    
    /**
     * Format date and time from database format (YYYY-MM-DD HH:MM:SS)
     * @param {string} dbDateTime Database datetime string
     * @returns {string} Formatted date and time string
     */
    formatDateTimeFromDb(dbDateTime) {
        if (!dbDateTime) return '';
        // Replace space with T for ISO format
        const isoString = dbDateTime.replace(' ', 'T');
        return this.formatDateTime(new Date(isoString));
    },

    /**
     * Format address by location country. Multiple lines allowed.
     * @param {{ street?: string, city?: string, zip?: string, state?: string, country?: string }} address
     * @returns {string} Formatted address (newline-separated lines)
     */
    formatAddress(address) {
        if (!address || typeof address !== 'object') return '';
        const street = (address.street ?? '').toString().trim();
        const city = (address.city ?? '').toString().trim();
        const zip = (address.zip ?? '').toString().trim();
        const state = (address.state ?? '').toString().trim();
        const country = (address.country ?? '').toString().trim();
        const lines = [];
        if (street) lines.push(street);
        const usLike = /^US|UM|USA$/i.test(country);
        if (city || zip || state) {
            if (usLike) {
                const cityState = [city, state].filter(Boolean).join(', ');
                const rest = [cityState, zip].filter(Boolean).join(' ');
                if (rest) lines.push(rest);
            } else {
                const zipCity = [zip, city].filter(Boolean).join(' ');
                if (zipCity) lines.push(zipCity);
            }
        }
        if (country) lines.push(country);
        return lines.join('\n');
    },
    
    /**
     * Get localized month name
     * @param {number} month Month number (1-12)
     * @param {string} format 'long' or 'short'
     * @returns {string} Month name
     */
    getMonthName(month, format = 'long') {
        const locale = this.getBrowserLocale(this.currentLang);
        const date = new Date(2024, month - 1, 1);
        return new Intl.DateTimeFormat(locale, { month: format }).format(date);
    },
    
    /**
     * Get all month names
     * @returns {Array} Array of month names (indexed 1-12)
     */
    getMonths() {
        const months = [];
        for (let i = 1; i <= 12; i++) {
            months[i] = this.getMonthName(i);
        }
        return months;
    },
    
    /**
     * Get localized weekday name
     * @param {string|number} weekday Weekday ('Mon', 'Monday', or 0-6)
     * @param {string} format 'long' or 'short'
     * @returns {string} Weekday name
     */
    getWeekdayName(weekday, format = 'long') {
        const locale = this.getBrowserLocale(this.currentLang);
        
        let dayIndex;
        if (typeof weekday === 'string') {
            const dayMap = {
                'Mon': 1, 'Monday': 1,
                'Tue': 2, 'Tuesday': 2,
                'Wed': 3, 'Wednesday': 3,
                'Thu': 4, 'Thursday': 4,
                'Fri': 5, 'Friday': 5,
                'Sat': 6, 'Saturday': 6,
                'Sun': 0, 'Sunday': 0
            };
            dayIndex = dayMap[weekday] ?? 0;
        } else {
            dayIndex = weekday;
        }
        
        const date = new Date(2024, 0, 7 + dayIndex); // Jan 7, 2024 is a Sunday
        return new Intl.DateTimeFormat(locale, { weekday: format }).format(date);
    },
    
    /**
     * Format relative time (e.g., "2 hours ago", "in 3 days")
     * @param {Date|string} date Date to format
     * @returns {string} Relative time string
     */
    formatRelativeTime(date) {
        const dateObj = date instanceof Date ? date : new Date(date);
        if (isNaN(dateObj.getTime())) {
            return '';
        }
        
        const locale = this.getBrowserLocale(this.currentLang);
        const now = new Date();
        const diffMs = dateObj - now;
        const diffSecs = Math.floor(diffMs / 1000);
        
        const rtf = new Intl.RelativeTimeFormat(locale, { numeric: 'auto' });
        
        const intervals = [
            { unit: 'year', seconds: 31536000 },
            { unit: 'month', seconds: 2592000 },
            { unit: 'day', seconds: 86400 },
            { unit: 'hour', seconds: 3600 },
            { unit: 'minute', seconds: 60 }
        ];
        
        for (const { unit, seconds } of intervals) {
            const interval = Math.floor(Math.abs(diffSecs) / seconds);
            if (interval >= 1) {
                return rtf.format(Math.sign(diffSecs) * interval, unit);
            }
        }
        
        return rtf.format(0, 'second');
    },
    
    /**
     * Format date range (e.g., "27.01.2026 - 30.01.2026")
     * @param {Date|string} startDate Start date
     * @param {Date|string} endDate End date
     * @returns {string} Formatted date range
     */
    formatDateRange(startDate, endDate) {
        const start = this.formatDateFromDb(startDate);
        const end = this.formatDateFromDb(endDate);
        return `${start} - ${end}`;
    },
    
    /**
     * Translate all elements with data-i18n attribute
     */
    translatePage() {
        if (Object.keys(this.translations).length === 0) {
            console.warn('i18n.translatePage: No translations loaded yet');
            return; // No translations loaded yet
        }
        
        let translatedCount = 0;
        
        // Translate elements with data-i18n
        document.querySelectorAll('[data-i18n]').forEach(el => {
            const key = el.getAttribute('data-i18n');
            if (!key) return;
            
            // Check if element has special handling
            if (el.id === 'welcome-greeting' || el.id === 'dashboard-subtitle') {
                // These are handled separately in dashboard.js
                return;
            }
            
            const paramsAttr = el.getAttribute('data-i18n-params');
            const paramsArray = paramsAttr ? JSON.parse(paramsAttr) : [];
            const translated = this.t(key, paramsArray);
            
            // Only update if translation was found (not the key itself)
            if (translated && translated !== key) {
                el.textContent = translated;
                translatedCount++;
            } else {
                console.warn('i18n: Translation not found for key:', key);
            }
        });
        
        // Translate filter button labels (they have data-i18n-label)
        document.querySelectorAll('[data-i18n-label]').forEach(el => {
            const key = el.getAttribute('data-i18n-label');
            if (!key) return;
            
            const translated = this.t(key);
            if (translated && translated !== key) {
                const countSpan = el.querySelector('.filter-count');
                if (countSpan) {
                    const countHtml = countSpan.outerHTML;
                    el.innerHTML = translated + ' ' + countHtml;
                } else {
                    el.textContent = translated;
                }
                translatedCount++;
            } else {
                console.warn('i18n: Translation not found for label key:', key);
            }
        });
        
        // Translate placeholders (data-i18n-placeholder)
        document.querySelectorAll('[data-i18n-placeholder]').forEach(el => {
            const key = el.getAttribute('data-i18n-placeholder');
            if (!key) return;
            const translated = this.t(key);
            if (translated && translated !== key) {
                el.placeholder = translated;
                translatedCount++;
            }
        });
        
        console.log('i18n.translatePage: Translated', translatedCount, 'elements');
    },
    
    /**
     * Common UI strings helper
     */
    common: {
        loading: () => i18n.t('js.common.loading'),
        error: () => i18n.t('js.common.error'),
        save: () => i18n.t('js.common.save'),
        cancel: () => i18n.t('js.common.cancel'),
        delete: () => i18n.t('js.common.delete'),
        edit: () => i18n.t('js.common.edit'),
        close: () => i18n.t('js.common.close')
    }
};
