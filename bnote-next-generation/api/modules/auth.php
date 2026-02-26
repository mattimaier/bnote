<?php
/**
 * BNote Next Generation - Authentication API Module
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
 * Authentication API module
 * Handles login, logout, and session management
 * 
 * Note: This file is loaded after api/index.php has changed working directory to project root
 * So relative paths in logindata.php will work correctly
 */
// dirs.php, init.php, and bootstrap.php are already loaded by api/index.php
// All base classes (FieldType, AbstractData, AbstractLocationData) are loaded
// But we need to load DefaultController before LoginController
// Use BNOTE_ROOT constant from paths.php (loaded by api/index.php)
require_once BNOTE_ROOT . '/src/logic/defaultcontroller.php';
require_once BNOTE_ROOT . '/src/data/modules/logindata.php';
require_once BNOTE_ROOT . '/src/logic/modules/logincontroller.php';
require_once __DIR__ . '/../response.php';
require_once __DIR__ . '/../auth.php';

class AuthModule {
    private $loginData;
    private $loginController;
    
    public function __construct() {
        // Auth module doesn't require authentication check (it's for login)
        $this->loginData = new LoginData();
        $this->loginController = new LoginController();
        $this->loginController->setData($this->loginData);
    }
    
    public function handle() {
        $action = $_GET['action'] ?? $_POST['action'] ?? 'session';
        
        switch ($action) {
            case 'login':
                return $this->login();
            case 'logout':
                return $this->logout();
            case 'session':
                return $this->checkSession();
            case 'getUserLang':
                return $this->getUserLang();
            case 'getPublicConfig':
                return $this->getPublicConfig();
            case 'getModules':
                return $this->getModules();
            default:
                Response::error('Unknown action: ' . $action, 400);
        }
    }
    
    private function login() {
        // Get POST data - handle both form-encoded and JSON
        $input = $_POST;
        $rawInput = file_get_contents('php://input');
        if (!empty($rawInput)) {
            $jsonInput = json_decode($rawInput, true);
            if ($jsonInput) {
                $input = array_merge($input, $jsonInput);
            }
        }
        
        // Validate input - support both 'username' and 'login' for compatibility
        $username = $input['username'] ?? $input['login'] ?? null;
        $password = $input['password'] ?? null;
        
        if (!$username || !$password) {
            Response::error('Username and password required', 400);
        }
        
        // Set $_POST for existing validation logic (expects 'login' and 'password')
        $_POST['login'] = $username;
        $_POST['password'] = $password;
        
        // Use existing login logic
        $this->loginData->validateLogin();
        $db_pw = $this->loginData->getPasswordForLogin($username);
        $passwordHash = crypt($password, LoginController::ENCRYPTION_HASH);
        
        // Determine user ID based on whether login is email or username
        if (strpos($username, "@") !== false) {
            // Input is an email address
            $requestedUserId = $this->loginData->getUserIdForEMail($username);
        } else {
            // Input is a username
            $requestedUserId = $this->loginData->getUserIdForLogin($username);
        }
        $isUserActive = $this->loginData->isUserActive($requestedUserId);
        
        if ($db_pw == $passwordHash && $isUserActive) {
            // Set session
            $_SESSION['user'] = $requestedUserId;
            $this->loginData->saveLastLogin();
            
            // Get user info
            global $system_data;
            $contact = $system_data->getUsersContact($requestedUserId);
            $permissions = $system_data->getUserModulePermissions($requestedUserId);
            
            return [
                'user' => [
                    'id' => $requestedUserId,
                    'name' => $contact['name'] ?? '',
                    'surname' => $contact['surname'] ?? '',
                    'email' => $contact['email'] ?? ''
                ],
                'permissions' => $permissions
            ];
        } else {
            // Log failed login attempt
            global $system_data;
            $logActive = $system_data->getDynamicConfigParameter('enable_failed_login_log');
            if (strval($logActive) == '1') {
                $line = date('c') . "\t" . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . "\tInvalid login attempt\n";
                file_put_contents(LoginController::FAILED_LOGIN_LOG, $line, FILE_APPEND);
            }
            
            Response::error('Invalid credentials', 401);
        }
    }
    
    private function logout() {
        // Destroy session
        $_SESSION['user'] = null;
        unset($_SESSION);
        session_destroy();
        
        return ['message' => 'Logged out successfully'];
    }
    
    private function checkSession() {
        if (!Auth::check()) {
            return [
                'authenticated' => false,
                'user' => null
            ];
        }
        
        $userInfo = Auth::getUserInfo();
        return [
            'authenticated' => true,
            'user' => $userInfo
        ];
    }
    
    private function getUserLang() {
        global $system_data;
        $lang = $system_data->getLang();
        $country = $system_data->getDynamicConfigParameter('default_country');
        $country = $this->countryAlpha3ToAlpha2($country);
        return [
            'lang' => $lang ?: 'de',
            'country' => $country ?: null
        ];
    }

    /**
     * Get public configuration (company name, language, country) for login page.
     * This endpoint is public and doesn't require authentication.
     */
    private function getPublicConfig() {
        global $system_data;
        $lang = $system_data->getLang();
        $country = $system_data->getDynamicConfigParameter('default_country');
        $country = $this->countryAlpha3ToAlpha2($country);
        $company = $system_data->getCompany();
        $out = [
            'lang' => $lang ?: 'de',
            'country' => $country ?: null,
            'company' => $company ?: ''
        ];
        $debug = isset($_GET['debug']) && $_GET['debug'] === '1';
        if ($debug) {
            $configPath = 'config/company.xml';
            $absPath = getcwd() . DIRECTORY_SEPARATOR . $configPath;
            $out['_debug'] = [
                'company_from_getCompany' => $company,
                'config_path' => $configPath,
                'config_abs_path' => $absPath,
                'config_exists' => file_exists($absPath),
                'config_readable' => is_readable($absPath),
            ];
        }
        return $out;
    }

    /**
     * Get available modules for the authenticated user.
     * Returns modules with route, icon, and i18n key mappings.
     */
    private function getModules() {
        global $system_data;
        
        if (!Auth::check()) {
            Response::error('Authentication required', 403);
        }
        
        // Get modules from both 'main' and 'admin' categories (User is in admin)
        $mainModules = $system_data->getModuleArray('main');
        $adminModules = $system_data->getModuleArray('admin');
        // Use + operator to preserve numeric keys (module IDs)
        $allModules = $mainModules + $adminModules;
        $modules = [];
        
        // Debug: Log module counts
        error_log('getModules: mainModules count: ' . count($mainModules));
        error_log('getModules: adminModules count: ' . count($adminModules));
        error_log('getModules: allModules count: ' . count($allModules));
        
        // Technical modules to exclude
        $excludedModules = ['Home', 'Logout', 'WhyBNote', 'Gdpr', 'ExtGdpr'];
        
        // Check registration module visibility
        $userReg = $system_data->getDynamicConfigParameter('user_registration');
        $showRegistration = strval($userReg) == '1';
        
        // Module name to route/icon/i18n mappings (Next.js paths)
        $moduleMappings = [
            'Start' => [
                'route' => '/dashboard',
                'icon' => 'layout-dashboard',
                'i18n' => 'js.sidebar.dashboard'
            ],
            'Proben' => [
                'route' => '/rehearsals',
                'icon' => 'music',
                'i18n' => 'js.sidebar.rehearsals'
            ],
            'Konzerte' => [
                'route' => '/concerts',
                'icon' => 'trumpet',
                'i18n' => 'js.sidebar.concerts'
            ],
            'User' => [
                'route' => '/users',
                'icon' => 'user-cog',
                'i18n' => 'js.sidebar.users'
            ],
            'Kontakte' => [
                'route' => '/contacts',
                'icon' => 'users',
                'i18n' => 'js.sidebar.contacts'
            ],
            'Share' => [
                'route' => '/share',
                'icon' => 'share',
                'i18n' => 'js.sidebar.share'
            ],
            'Locations' => [
                'route' => '/locations',
                'icon' => 'map-pin',
                'i18n' => 'js.sidebar.locations'
            ],
            'Equipment' => [
                'route' => '/equipment',
                'icon' => 'package',
                'i18n' => 'js.sidebar.equipment'
            ],
            'Outfits' => [
                'route' => '/outfits',
                'icon' => 'shirt',
                'i18n' => 'js.sidebar.outfits'
            ],
            'Repertoire' => [
                'route' => '/repertoire',
                'icon' => 'music',
                'i18n' => 'js.sidebar.repertoire'
            ],
            'Abstimmung' => [
                'route' => '/votes',
                'icon' => 'vote',
                'i18n' => 'js.sidebar.votes'
            ],
            'Nachrichten' => [
                'route' => '/news',
                'icon' => 'newspaper',
                'i18n' => 'js.sidebar.news'
            ],
            'Aufgaben' => [
                'route' => '/tasks',
                'icon' => 'check-square',
                'i18n' => 'js.sidebar.tasks'
            ],
            'Calendar' => [
                'route' => '/calendar',
                'icon' => 'calendar-days',
                'i18n' => 'js.sidebar.calendar'
            ],
            'Kalender' => [
                'route' => '/calendar',
                'icon' => 'calendar-days',
                'i18n' => 'js.sidebar.calendar'
            ]
        ];
        
        foreach ($allModules as $modId => $modRow) {
            $modName = $modRow['name'] ?? '';
            $modIdInt = intval($modId);
            
            error_log("getModules: Checking module ID=$modIdInt, name='$modName'");
            
            // Skip technical modules first (before permission check)
            if (in_array($modName, $excludedModules)) {
                error_log("getModules: Skipping $modName (excluded)");
                continue;
            }
            
            // Skip Registration if disabled
            if ($modName === 'Registration' && !$showRegistration) {
                error_log("getModules: Skipping $modName (registration disabled)");
                continue;
            }
            
            // Only check modules that have a mapping (i.e., implemented in bnote-next-generation/)
            if (!isset($moduleMappings[$modName])) {
                error_log("getModules: Skipping $modName (no mapping)");
                continue;
            }
            
            // Check if user has permission for this module
            $hasPermission = $system_data->userHasPermission($modIdInt);
            error_log("getModules: Module $modName (ID=$modIdInt) hasPermission=" . ($hasPermission ? 'true' : 'false'));
            
            if (!$hasPermission) {
                continue;
            }
            
            $mapping = $moduleMappings[$modName];
            $modules[] = [
                'id' => $modIdInt,
                'name' => $modName,
                'route' => $mapping['route'],
                'icon' => $mapping['icon'],
                'i18n' => $mapping['i18n']
            ];
            error_log("getModules: Added module $modName");
        }
        
        // Sort: use config order if available, otherwise by module ID
        $orderConfigPath = __DIR__ . '/../../frontend/config/sidebar-module-order.json';
        $orderNames = null;
        if (is_readable($orderConfigPath)) {
            $orderConfig = json_decode(file_get_contents($orderConfigPath), true);
            if (!empty($orderConfig['order']) && is_array($orderConfig['order'])) {
                $orderNames = array_flip($orderConfig['order']);
            }
        }
        usort($modules, function($a, $b) use ($orderNames) {
            if ($orderNames !== null) {
                $posA = isset($orderNames[$a['name']]) ? $orderNames[$a['name']] : PHP_INT_MAX;
                $posB = isset($orderNames[$b['name']]) ? $orderNames[$b['name']] : PHP_INT_MAX;
                if ($posA !== $posB) {
                    return $posA <=> $posB;
                }
            }
            return $a['id'] <=> $b['id'];
        });

        error_log('getModules: Returning ' . count($modules) . ' modules');
        
        // Debug: Also return debug info if requested
        if (isset($_GET['debug']) && $_GET['debug'] === '1') {
            return [
                'modules' => $modules,
                'debug' => [
                    'mainModulesCount' => count($mainModules),
                    'adminModulesCount' => count($adminModules),
                    'allModulesCount' => count($allModules),
                    'mainModuleNames' => array_map(function($m) { return $m['name'] ?? 'unknown'; }, $mainModules),
                    'adminModuleNames' => array_map(function($m) { return $m['name'] ?? 'unknown'; }, $adminModules),
                    'allModuleNames' => array_map(function($m) { return $m['name'] ?? 'unknown'; }, $allModules),
                    'userId' => $system_data->getUserId(),
                    'userPermissions' => $system_data->user_module_permission ?? 'not set'
                ]
            ];
        }
        
        // Return modules array directly (not wrapped)
        return $modules;
    }

    /**
     * Convert ISO 3166-1 alpha-3 (e.g. DEU) to alpha-2 (e.g. DE) for BCP 47 locale tags.
     * Config stores 3-letter codes; Intl expects 2-letter. Uses bnote-next-generation/iso3166-alpha3-to-alpha2.json.
     *
     * @param string|null $country
     * @return string|null Alpha-2 code or original if already 2-letter; null if invalid/missing.
     */
    private function countryAlpha3ToAlpha2($country) {
        if ($country === null || $country === '') {
            return null;
        }
        $raw = trim((string) $country);
        if ($raw === '') {
            return null;
        }
        $upper = strtoupper($raw);
        if (strlen($upper) === 2) {
            return $upper;
        }
        if (strlen($upper) !== 3) {
            return null;
        }
        static $map = null;
        if ($map === null) {
            $path = __DIR__ . '/../../iso3166-alpha3-to-alpha2.json';
            if (!is_file($path)) {
                return null;
            }
            $json = file_get_contents($path);
            $map = json_decode($json, true);
            if (!is_array($map)) {
                $map = [];
            }
        }
        return isset($map[$upper]) ? $map[$upper] : null;
    }
}
