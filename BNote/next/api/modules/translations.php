<?php
/**
 * BNote Next Generation - Translations API Module
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
 * Translations API module
 * Bridges existing PHP language files with new JavaScript app
 * Reads PHP translations (read-only) and merges with JS-specific JSON translations
 * 
 * Note: This file is loaded after api/index.php has changed working directory to project root
 */
require_once __DIR__ . '/../../../lang/lang_base.php';
require_once __DIR__ . '/../response.php';
require_once __DIR__ . '/../auth.php';

class TranslationsModule {
    
    /**
     * Module name mapping: JS module names -> PHP module names
     */
    private function getModuleMapping() {
        return [
            'rehearsals' => 'Proben',
            'concerts' => 'Konzerte',
            'dashboard' => 'Start',
            'contacts' => 'Kontakte',
            'users' => 'User',
            'calendar' => 'Calendar',
            'repertoire' => 'Repertoire',
            'communication' => 'Kommunikation',
            'messages' => 'Nachrichten',
            'tasks' => 'Aufgaben',
            'finance' => 'Finance',
            'tours' => 'Tour',
            'locations' => 'Locations',
            'equipment' => 'Equipment',
            'outfits' => 'Outfits',
            'members' => 'Mitspieler',
            'groups' => 'Gruppen',
            'instruments' => 'Instrumente',
            'genres' => 'Genre',
            'programs' => 'Program',
            'rehearsal_phases' => 'Probenphasen',
            'voting' => 'Abstimmung',
            'accommodation' => 'Accommodation',
            'travel' => 'Travel',
            'appointments' => 'Appointment',
            'share' => 'Share',
            'website' => 'Website',
            'help' => 'Hilfe',
            'configuration' => 'Konfiguration',
            'admin' => 'Admin',
            'stats' => 'Stats'
        ];
    }
    
    /**
     * Read existing PHP translations (read-only)
     * @param string $langCode Language code (de, en, es, fr)
     * @return array Translations array
     */
    private function getPHPTranslations($langCode) {
        // Load existing PHP language file (read-only)
        $langFile = __DIR__ . '/../../../lang/lang_' . $langCode . '.php';
        if (!file_exists($langFile)) {
            return [];
        }
        
        // Include the file to get Translation class
        require_once $langFile;
        
        // Instantiate Translation class (read-only operation)
        $translation = new Translation();
        
        // Use reflection to access protected $texts property
        $reflection = new ReflectionClass($translation);
        $property = $reflection->getProperty('texts');
        $property->setAccessible(true);
        $texts = $property->getValue($translation);
        
        return $texts ?: [];
    }
    
    /**
     * Load JS-specific translations from JSON files
     * @param string $langCode Language code (de, en, es, fr)
     * @return array Translations array
     */
    private function getJSTranslations($langCode) {
        // Load JSON translation file (modern JS approach)
        // Path: next/api/modules/ -> next/lang/ (go up two levels from modules/)
        $jsonFile = __DIR__ . '/../../lang/' . $langCode . '.json';
        if (!file_exists($jsonFile)) {
            error_log('TranslationsModule: JSON file not found: ' . $jsonFile);
            return [];
        }
        
        $jsonContent = file_get_contents($jsonFile);
        $translations = json_decode($jsonContent, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('TranslationsModule: JSON decode error for ' . $langCode . ': ' . json_last_error_msg());
            return [];
        }
        
        return $translations ?: [];
    }
    
    /**
     * Public subset for unauthenticated users (login page): js.*, banner_*, etc.
     * @param array $translations All translations
     * @return array Filtered translations
     */
    private function getPublicTranslations($translations) {
        $filtered = [];
        foreach ($translations as $key => $value) {
            if (strpos($key, 'js.') === 0 ||
                strpos($key, 'banner_') === 0 ||
                strpos($key, 'AbstractView_') === 0 ||
                strpos($key, 'CrudView_') === 0 ||
                strpos($key, 'Form_') === 0 ||
                strpos($key, 'navigation_') === 0) {
                $filtered[$key] = $value;
            }
        }
        return $filtered;
    }
    
    /**
     * Filter translations by user permissions
     * Only return translations for modules the user can access
     * @param array $translations All translations
     * @return array Filtered translations
     */
    private function filterByPermissions($translations) {
        global $system_data;
        
        if (!Auth::check()) {
            return [];
        }
        
        $filtered = [];
        $moduleMapping = $this->getModuleMapping();
        
        // Always include common translations (js.*, banner_*, AbstractView_*, etc.)
        foreach ($translations as $key => $value) {
            // ALWAYS include js.* keys (no permission check needed)
            if (strpos($key, 'js.') === 0) {
                $filtered[$key] = $value;
                continue;
            }
            
            // ALWAYS include common UI keys
            if (strpos($key, 'banner_') === 0 ||
                strpos($key, 'AbstractView_') === 0 ||
                strpos($key, 'CrudView_') === 0 ||
                strpos($key, 'Form_') === 0 ||
                strpos($key, 'navigation_') === 0) {
                $filtered[$key] = $value;
                continue;
            }
            
            // Check module-specific keys (PHP module keys)
            $moduleFound = false;
            foreach ($moduleMapping as $jsModule => $phpModule) {
                // Check if key belongs to this PHP module
                if (strpos($key, $phpModule . '_') === 0) {
                    // Check if user has permission for this module
                    $moduleId = $system_data->getModuleId($phpModule);
                    if ($moduleId && $system_data->userHasPermission($moduleId)) {
                        $filtered[$key] = $value;
                        $moduleFound = true;
                        break;
                    }
                }
            }
            
            // If not module-specific or user has access, include it
            if (!$moduleFound) {
                // Include keys that don't match any module (might be shared)
                $filtered[$key] = $value;
            }
        }
        
        return $filtered;
    }
    
    /**
     * Get all translations for a language
     * @param string $langCode Language code
     * @return array Merged translations
     */
    private function getAllTranslations($langCode) {
        // Get PHP translations (read-only)
        $phpTranslations = $this->getPHPTranslations($langCode);
        
        // Get JS-specific translations
        $jsTranslations = $this->getJSTranslations($langCode);
        
        // Merge: JS translations override PHP translations if same key exists
        // Use array_merge with JS first so JS can override PHP
        $merged = array_merge($phpTranslations, $jsTranslations);
        
        // Filter by user permissions when authenticated; otherwise return public subset for login page
        $filtered = Auth::check() ? $this->filterByPermissions($merged) : $this->getPublicTranslations($merged);
        
        // Debug logging
        error_log('TranslationsModule: Loaded ' . count($phpTranslations) . ' PHP translations, ' . 
                  count($jsTranslations) . ' JSON translations, ' . 
                  count($merged) . ' merged, ' . 
                  count($filtered) . ' after filtering for lang: ' . $langCode);
        
        return $filtered;
    }
    
    /**
     * Get translations for a specific module
     * @param string $jsModule JS module name (e.g., 'rehearsals')
     * @param string $langCode Language code
     * @return array Module-specific translations
     */
    private function getModuleTranslations($jsModule, $langCode) {
        $allTranslations = $this->getAllTranslations($langCode);
        $moduleMapping = $this->getModuleMapping();
        
        if (!isset($moduleMapping[$jsModule])) {
            return [];
        }
        
        $phpModule = $moduleMapping[$jsModule];
        $moduleTranslations = [];
        
        foreach ($allTranslations as $key => $value) {
            // Include module-specific keys
            if (strpos($key, $phpModule . '_') === 0 || 
                strpos($key, 'js.' . $jsModule . '.') === 0) {
                $moduleTranslations[$key] = $value;
            }
        }
        
        return $moduleTranslations;
    }
    
    /**
     * Handle API requests
     * 'get' is allowed without auth for login page; 'getModule' requires auth.
     */
    public function handle() {
        $action = $_GET['action'] ?? $_POST['action'] ?? 'get';
        if ($action !== 'get' && !Auth::check()) {
            Response::error('Authentication required', 401);
        }
        $langCode = $_GET['lang'] ?? $_POST['lang'] ?? null;
        $module = $_GET['module'] ?? $_POST['module'] ?? null;
        
        // Always use system configuration language (not user preference or browser)
        global $system_data;
        $langCode = $system_data->getLang() ?: 'de';
        
        // Validate language code
        $validLanguages = ['de', 'en', 'es', 'fr'];
        if (!in_array($langCode, $validLanguages)) {
            $langCode = 'de'; // Default to German
        }
        
        switch ($action) {
            case 'get':
                // Get all translations
                $translations = $this->getAllTranslations($langCode);
                return [
                    'lang' => $langCode,
                    'translations' => $translations
                ];
                
            case 'getModule':
                // Get translations for a specific module
                if (!$module) {
                    Response::error('Module parameter required', 400);
                }
                $translations = $this->getModuleTranslations($module, $langCode);
                return [
                    'lang' => $langCode,
                    'module' => $module,
                    'translations' => $translations
                ];
                
            default:
                Response::error('Unknown action: ' . $action, 400);
        }
    }
}
