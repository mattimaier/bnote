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
require_once __DIR__ . '/../register_rate_limit.php';
require_once __DIR__ . '/../password_reset_rate_limit.php';
require_once __DIR__ . '/../nextgen_registration.php';
require_once __DIR__ . '/../nextgen_password_reset.php';
require_once __DIR__ . '/../participation_magic_rate_limit.php';
require_once __DIR__ . '/../nextgen_participation_token.php';
require_once __DIR__ . '/../participation_magic_apply.php';
require_once __DIR__ . '/../nextgen_calendar_subscription_token.php';
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../module_provisioning.php';

class AuthModule {
    private $loginData;

    public function __construct() {
        $this->loginData = new LoginData();
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
            case 'getRegistrationOptions':
                return $this->getRegistrationOptions();
            case 'register':
                return $this->registerAction();
            case 'requestPasswordReset':
                return $this->requestPasswordReset();
            case 'completePasswordReset':
                return $this->completePasswordReset();
            case 'applyParticipationToken':
                return $this->applyParticipationToken();
            case 'getParticipationTokenInfo':
                return $this->getParticipationTokenInfo();
            case 'getModules':
                return $this->getModules();
            case 'getCalendarSubscriptionLink':
                return $this->getCalendarSubscriptionLink();
            case 'regenerateCalendarSubscriptionLink':
                return $this->regenerateCalendarSubscriptionLink();
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
                'user' => null,
                'isAdmin' => false
            ];
        }
        
        global $system_data;
        $userInfo = Auth::getUserInfo();
        $userId = Auth::getUserId();
        $isAdmin = $system_data->isUserSuperUser($userId) || $system_data->isUserMemberGroup(1, $userId);
        return [
            'authenticated' => true,
            'user' => $userInfo,
            'isAdmin' => $isAdmin
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
        $userReg = $system_data->getDynamicConfigParameter('user_registration');
        $out = [
            'lang' => $lang ?: 'de',
            'country' => $country ?: null,
            'company' => $company ?: '',
            'user_registration' => strval($userReg) === '1',
            'auto_user_activation' => $system_data->autoUserActivation(),
            'demo_mode' => $system_data->inDemoMode(),
            'beta_bug_report_enabled' => strval($system_data->getDynamicConfigParameter('beta_bug_report_enabled')) === '1',
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

        // Lazy provisioning: create Wrapped module row when feature is enabled.
        $wrappedEnabled = strval($system_data->getDynamicConfigParameter('wrapped_module_enabled')) === '1';
        if ($wrappedEnabled) {
            ModuleProvisioning::ensureModuleExists('Wrapped', 'cake', 'main');
        }
        
        // Read all modules across categories. Some installations place
        // modules like "Mitspieler" or "Share" outside main/admin.
        $allDbModules = $system_data->getModuleArray();
        // Normalize into a stable id-keyed map.
        // We resolve IDs from row.id, key, or module name lookup.
        $allModules = [];
        $moduleSets = [is_array($allDbModules) ? $allDbModules : []];
        foreach ($moduleSets as $moduleSet) {
            foreach ($moduleSet as $modIdRaw => $modRowRaw) {
                if (!is_array($modRowRaw)) {
                    continue;
                }
                $resolvedId = intval($modRowRaw['id'] ?? 0);
                if ($resolvedId <= 0) {
                    $resolvedId = intval($modIdRaw);
                }
                if ($resolvedId <= 0) {
                    $nameLookup = trim(html_entity_decode((string)($modRowRaw['name'] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                    if ($nameLookup !== '') {
                        $resolvedId = intval($system_data->getModuleId($nameLookup));
                    }
                }
                if ($resolvedId <= 0) {
                    continue;
                }
                $row = $modRowRaw;
                $row['id'] = $resolvedId;
                $allModules[$resolvedId] = $row;
            }
        }
        $modules = [];
        $contactsVisible = false;
        
        // Debug: Log module counts
        error_log('getModules: dbModules count: ' . count($allDbModules));
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
            'Stats' => [
                'route' => '/stats',
                'icon' => 'chart-bar',
                'i18n' => 'js.sidebar.stats'
            ],
            'Auswertungen' => [
                'route' => '/stats',
                'icon' => 'chart-bar',
                'i18n' => 'js.sidebar.stats'
            ],
            'Statistik' => [
                'route' => '/stats',
                'icon' => 'chart-bar',
                'i18n' => 'js.sidebar.stats'
            ],
            'Statistics' => [
                'route' => '/stats',
                'icon' => 'chart-bar',
                'i18n' => 'js.sidebar.stats'
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
            'Mitspieler' => [
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
            'Kommunikation' => [
                'route' => '/email',
                'icon' => 'mail',
                'i18n' => 'js.sidebar.email'
            ],
            'Communication' => [
                'route' => '/email',
                'icon' => 'mail',
                'i18n' => 'js.sidebar.email'
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
            ],
            'Wrapped' => [
                'route' => '/wrapped',
                'icon' => 'cake',
                'i18n' => 'js.sidebar.wrapped'
            ]
        ];
        $calendarAlreadyAdded = false;
        
        foreach ($allModules as $modId => $modRow) {
            $modNameRaw = $modRow['name'] ?? '';
            $modName = trim(html_entity_decode((string)$modNameRaw, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            $modIdInt = intval($modRow['id'] ?? $modId);
            
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
            // Wrapped must be enabled by global configuration in addition to module permission.
            if ($modName === 'Wrapped') {
                $wrappedEnabled = strval($system_data->getDynamicConfigParameter('wrapped_module_enabled')) === '1';
                if (!$wrappedEnabled) {
                    error_log("getModules: Skipping $modName (wrapped disabled)");
                    continue;
                }
            }
            
            // Only check modules that have a mapping (i.e., implemented in bnote-next-generation/)
            if (!isset($moduleMappings[$modName])) {
                error_log("getModules: Skipping $modName (no mapping)");
                continue;
            }
            
            // Check if user has permission for this module
            $hasPermission = $system_data->userHasPermission($modIdInt);
            error_log("getModules: Module $modName (ID=$modIdInt) hasPermission=" . ($hasPermission ? 'true' : 'false'));
            $isCalendarModule = ($modName === 'Calendar' || $modName === 'Kalender');
            if (!$hasPermission && !$isCalendarModule) {
                continue;
            }
            // Calendar is visible to all authenticated users; keep only one calendar entry.
            if ($isCalendarModule && $calendarAlreadyAdded) {
                continue;
            }
            
            $mapping = $moduleMappings[$modName];
            $alreadyAddedByRoute = false;
            foreach ($modules as $existing) {
                if (($existing['route'] ?? '') === $mapping['route']) {
                    $alreadyAddedByRoute = true;
                    break;
                }
            }
            if ($alreadyAddedByRoute) {
                continue;
            }
            $modules[] = [
                'id' => $modIdInt,
                'name' => $modName,
                'route' => $mapping['route'],
                'icon' => $mapping['icon'],
                'i18n' => $mapping['i18n']
            ];
            if ($mapping['route'] === '/contacts') {
                $contactsVisible = true;
            }
            if ($isCalendarModule) {
                $calendarAlreadyAdded = true;
            }
            error_log("getModules: Added module $modName");
        }

        // Some setups expose Mitspieler permission without returning a mapped
        // Mitspieler module row in getModuleArray(). Ensure contacts is visible
        // for members-only users by synthesizing one /contacts module entry.
        $mitspielerModuleId = $system_data->getModuleId('Mitspieler');
        $hasMitspielerPermission = $mitspielerModuleId && $system_data->userHasPermission($mitspielerModuleId);
        if (!$hasMitspielerPermission) {
            foreach ($allModules as $modId => $modRow) {
                $name = trim(html_entity_decode((string)($modRow['name'] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                $nameLower = mb_strtolower($name);
                if (($nameLower === 'mitspieler' || $nameLower === 'members') && $system_data->userHasPermission(intval($modId))) {
                    $hasMitspielerPermission = true;
                    if (!$mitspielerModuleId) {
                        $mitspielerModuleId = intval($modId);
                    }
                    break;
                }
            }
        }
        if (!$contactsVisible && $hasMitspielerPermission) {
            $modules[] = [
                'id' => intval($mitspielerModuleId ?: 0),
                'name' => 'Mitspieler',
                'route' => '/contacts',
                'icon' => 'users',
                'i18n' => 'js.sidebar.contacts'
            ];
        }

        // Robustness fallback for installations where Wrapped module may not be returned
        // by getModuleArray(), but exists and is permission-controlled in module table.
        $wrappedEnabled = strval($system_data->getDynamicConfigParameter('wrapped_module_enabled')) === '1';
        $wrappedModuleId = intval($system_data->getModuleId('Wrapped'));
        if ($wrappedEnabled && $wrappedModuleId > 0 && $system_data->userHasPermission($wrappedModuleId)) {
            $wrappedExists = false;
            foreach ($modules as $m) {
                if (($m['route'] ?? '') === '/wrapped') {
                    $wrappedExists = true;
                    break;
                }
            }
            if (!$wrappedExists) {
                $modules[] = [
                    'id' => $wrappedModuleId,
                    'name' => 'Wrapped',
                    'route' => '/wrapped',
                    'icon' => 'cake',
                    'i18n' => 'js.sidebar.wrapped'
                ];
            }
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

        // Add Band Overview for admins only (synthetic module, not from BNote DB)
        $userId = Auth::getUserId();
        $isAdmin = $userId && ($system_data->isUserSuperUser($userId) || $system_data->isUserMemberGroup(1, $userId));
        if ($isAdmin) {
            $bandOverview = [
                'id' => -1,
                'name' => 'BandOverview',
                'route' => '/band-overview',
                'icon' => 'layout-dashboard',
                'i18n' => 'js.sidebar.bandOverview'
            ];
            // Insert after Dashboard (Start) if present
            $insertIndex = 0;
            foreach ($modules as $idx => $m) {
                if (($m['name'] ?? '') === 'Start') {
                    $insertIndex = $idx + 1;
                    break;
                }
            }
            array_splice($modules, $insertIndex, 0, [$bandOverview]);
        }

        error_log('getModules: Returning ' . count($modules) . ' modules');
        
        // Debug: Also return debug info if requested
        if (isset($_GET['debug']) && $_GET['debug'] === '1') {
            return [
                'modules' => $modules,
                'debug' => [
                    'dbModulesCount' => count($allDbModules),
                    'allModulesCount' => count($allModules),
                    'dbModuleNames' => array_map(function($m) { return $m['name'] ?? 'unknown'; }, is_array($allDbModules) ? $allDbModules : []),
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

    private function registrationEnabled(): bool {
        global $system_data;
        return strval($system_data->getDynamicConfigParameter('user_registration')) === '1';
    }

    /**
     * Public form data for /register (instruments, countries). GET.
     */
    private function getRegistrationOptions(): array {
        global $system_data;
        if (!$this->registrationEnabled()) {
            Response::error('register_deactivated', 403);
        }

        $instruments = $this->loginData->getInstruments();
        $cats = $system_data->getInstrumentCategories();
        $list = [];
        if (is_array($instruments)) {
            for ($i = 1; $i < count($instruments); $i++) {
                $row = $instruments[$i];
                if (!in_array($row['cat'], $cats)) {
                    continue;
                }
                $list[] = [
                    'id' => (int) ($row['id'] ?? 0),
                    'name' => ($row['category'] ?? '') . ': ' . ($row['instrument'] ?? ''),
                ];
            }
        }

        $countries = $this->loginData->getCountries();
        $lang = $system_data->getLang() ?: 'de';
        $countryList = [];
        foreach ($countries as $c) {
            $name = $c[$lang] ?? $c['en'] ?? $c['de'] ?? '';
            $countryList[] = [
                'code' => $c['code'] ?? '',
                'label' => trim($name . ' - ' . ($c['code'] ?? '')),
                'name' => $name,
            ];
        }

        $defaultCountry = $system_data->getDynamicConfigParameter('default_country');
        $defaultCountry = $defaultCountry !== null ? trim((string) $defaultCountry) : '';

        return [
            'instruments' => $list,
            'countries' => $countryList,
            'defaultCountry' => $defaultCountry,
            'autoUserActivation' => $system_data->autoUserActivation(),
        ];
    }

    /**
     * Create user via legacy LoginController::register(false).
     */
    private function registerAction(): array {
        global $system_data;
        if (!$this->registrationEnabled()) {
            Response::error('register_deactivated', 403);
        }

        RegisterRateLimit::consumeOr429();

        $body = $GLOBALS['API_REQUEST_BODY'] ?? null;
        if (!is_array($body)) {
            Response::error('register_validation', 400);
        }

        $result = NextGenRegistration::execute($body);

        $auto = $system_data->autoUserActivation();
        $mailOk = (bool) ($result['mailOk'] ?? false);
        $nextStep = 'done';
        if ($auto) {
            $nextStep = $mailOk ? 'confirm_email' : 'mail_failed';
        } else {
            $nextStep = 'wait_admin';
        }

        return [
            'userId' => (int) ($result['user'] ?? 0),
            'mailOk' => $mailOk,
            'autoUserActivation' => $auto,
            'nextStep' => $nextStep,
        ];
    }

    /**
     * Public: request password reset email (neutral response; no account enumeration).
     */
    private function requestPasswordReset(): array {
        PasswordResetRateLimit::consumeOr429();

        $body = $GLOBALS['API_REQUEST_BODY'] ?? null;
        $identifier = '';
        if (is_array($body) && isset($body['identifier']) && is_string($body['identifier'])) {
            $identifier = $body['identifier'];
        }

        $out = ['ok' => true];
        global $system_data;

        $resolved = NextGenPasswordReset::resolveActiveUserForReset(
            $identifier,
            $this->loginData,
            $system_data
        );
        if ($resolved === null) {
            return $out;
        }

        try {
            $db = $system_data->dbcon;
            $tokenInfo = NextGenPasswordReset::newTokenRow($resolved['userId'], $db);
            $plain = $tokenInfo['plainToken'];

            require_once __DIR__ . '/../mail/MailEnv.php';
            $base = MailEnv::nextgenPublicBaseUrl();
            $resetUrlForEmail = $base !== '' ? $base . '/reset-password/confirm/?token=' . rawurlencode($plain) : '';
            $demoResetUrl = $resetUrlForEmail !== '' ? $resetUrlForEmail : MailEnv::nextgenPasswordResetRelativeUrl($plain);

            require_once __DIR__ . '/../mail/NextGenMailPolicy.php';
            require_once __DIR__ . '/../mail/NextGenMailer.php';
            require_once __DIR__ . '/../mail/builders/PasswordResetMailBuilder.php';

            $mailSent = false;
            if (NextGenMailPolicy::shouldSendPublicMail($system_data)) {
                $locale = method_exists($system_data, 'getLang') ? (string) ($system_data->getLang() ?: 'en') : 'en';
                $msg = PasswordResetMailBuilder::build($system_data, $locale, $resolved['email'], $resetUrlForEmail);
                $mailSent = NextGenMailer::send($msg);
            }

            if ($system_data->inDemoMode() && !$mailSent) {
                $out['dev_reset_url'] = $demoResetUrl;
            }
        } catch (Throwable $e) {
            error_log('AuthModule::requestPasswordReset ' . $e->getMessage());
        }

        return $out;
    }

    /**
     * Public: set new password using token from email.
     */
    private function completePasswordReset(): array {
        $body = $GLOBALS['API_REQUEST_BODY'] ?? null;
        if (!is_array($body)) {
            Response::error('register_validation', 400);
        }

        $token = isset($body['token']) && is_string($body['token']) ? $body['token'] : '';
        $pw1 = isset($body['pw1']) && is_string($body['pw1']) ? $body['pw1'] : '';
        $pw2 = isset($body['pw2']) && is_string($body['pw2']) ? $body['pw2'] : '';

        global $system_data;
        $db = $system_data->dbcon;

        $userId = NextGenPasswordReset::loadValidTokenUserId($token, $db);
        NextGenPasswordReset::applyNewPassword($userId, $pw1, $pw2, $this->loginData);
        NextGenPasswordReset::deleteTokensForUser($userId, $db);

        return ['ok' => true];
    }

    /**
     * Public: set participation (yes/maybe/no/undecided) using token from event invite email.
     */
    private function applyParticipationToken(): array {
        ParticipationMagicRateLimit::consumeOr429();

        $body = $GLOBALS['API_REQUEST_BODY'] ?? null;
        if (!is_array($body)) {
            $body = [];
        }
        $token = isset($body['token']) && is_string($body['token']) ? trim($body['token']) : '';
        $status = isset($body['status']) && is_string($body['status']) ? trim($body['status']) : '';
        if ($token === '' || $status === '') {
            Response::error('participation_token_invalid', 400);
        }

        global $system_data;
        $db = $system_data->dbcon;
        $row = NextGenParticipationToken::loadValidTokenRow($token, $db);
        if ($row === null) {
            Response::error('participation_token_invalid', 400);
        }

        $reason = '';
        if (isset($body['reason']) && is_string($body['reason'])) {
            $reason = function_exists('mb_substr')
                ? mb_substr(trim($body['reason']), 0, 2000, 'UTF-8')
                : substr(trim($body['reason']), 0, 2000);
        }
        $result = ParticipationMagicApply::apply($system_data, $row, $status, $reason);
        if (empty($result['ok'])) {
            $code = isset($result['error']) ? (string) $result['error'] : 'participation_failed';
            $http = ($code === 'participation_locked' || $code === 'participation_maybe_disabled') ? 403 : 400;
            Response::error($code, $http);
        }

        return [
            'ok' => true,
            'status' => $result['status'],
            'eventType' => (string) $row['event_type'],
            'eventId' => (int) $row['event_id'],
            'expiresAt' => isset($row['expiresAt']) && is_string($row['expiresAt']) ? $row['expiresAt'] : '',
        ];
    }

    /**
     * Public: read magic-link expiry (and confirm token is valid) without changing participation.
     */
    private function getParticipationTokenInfo(): array {
        ParticipationMagicRateLimit::consumeOr429();

        $body = $GLOBALS['API_REQUEST_BODY'] ?? null;
        if (!is_array($body)) {
            $body = [];
        }
        $token = isset($body['token']) && is_string($body['token']) ? trim($body['token']) : '';
        if ($token === '') {
            Response::error('participation_token_invalid', 400);
        }

        global $system_data;
        $db = $system_data->dbcon;
        $row = NextGenParticipationToken::loadValidTokenRow($token, $db);
        if ($row === null) {
            Response::error('participation_token_invalid', 400);
        }

        return [
            'ok' => true,
            'expiresAt' => isset($row['expiresAt']) && is_string($row['expiresAt']) ? $row['expiresAt'] : '',
            'reusable' => true,
        ];
    }

    /**
     * Authenticated: read (or lazily create) stable calendar subscription token for current user.
     */
    private function getCalendarSubscriptionLink(): array {
        if (!Auth::check()) {
            Response::error('Authentication required', 403);
        }
        global $system_data;
        $db = $system_data->dbcon;
        $uid = Auth::getUserId();
        if (!$uid) {
            Response::error('Authentication required', 403);
        }
        $tokenInfo = NextGenCalendarSubscriptionToken::getOrCreateForUser((int) $uid, $db);
        $urls = $this->buildCalendarSubscriptionUrls((string) $tokenInfo['plainToken']);
        return [
            'ok' => true,
            'token' => (string) $tokenInfo['plainToken'],
            'subscriptionUrlHttp' => $urls['subscriptionUrlHttp'],
            'subscriptionUrlWebcal' => $urls['subscriptionUrlWebcal'],
            'downloadUrl' => $urls['downloadUrl'],
            'reusable' => true,
        ];
    }

    /**
     * Authenticated: rotate calendar subscription token for current user (invalidates old URL).
     */
    private function regenerateCalendarSubscriptionLink(): array {
        if (!Auth::check()) {
            Response::error('Authentication required', 403);
        }
        global $system_data;
        $db = $system_data->dbcon;
        $uid = Auth::getUserId();
        if (!$uid) {
            Response::error('Authentication required', 403);
        }
        $tokenInfo = NextGenCalendarSubscriptionToken::regenerateForUser((int) $uid, $db);
        $urls = $this->buildCalendarSubscriptionUrls((string) $tokenInfo['plainToken']);
        return [
            'ok' => true,
            'token' => (string) $tokenInfo['plainToken'],
            'subscriptionUrlHttp' => $urls['subscriptionUrlHttp'],
            'subscriptionUrlWebcal' => $urls['subscriptionUrlWebcal'],
            'downloadUrl' => $urls['downloadUrl'],
            'reusable' => true,
        ];
    }

    /**
     * @return array{subscriptionUrlHttp:string,subscriptionUrlWebcal:string,downloadUrl:string}
     */
    private function buildCalendarSubscriptionUrls(string $plainToken): array {
        require_once __DIR__ . '/../mail/MailEnv.php';

        $query = http_build_query(['token' => $plainToken]);
        $queryDl = http_build_query(['token' => $plainToken, 'download' => '1']);

        $base = trim(MailEnv::nextgenPublicBaseUrl());
        if ($base !== '') {
            $base = rtrim($base, '/');
            $http = $base . '/api/calendar.ics.php?' . $query;
            $download = $base . '/api/calendar.ics.php?' . $queryDl;
        } else {
            $scheme = 'https';
            if (
                (isset($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off' && $_SERVER['HTTPS'] !== '')
                || (isset($_SERVER['REQUEST_SCHEME']) && strtolower((string) $_SERVER['REQUEST_SCHEME']) === 'https')
            ) {
                $scheme = 'https';
            } elseif (isset($_SERVER['REQUEST_SCHEME']) && strtolower((string) $_SERVER['REQUEST_SCHEME']) === 'http') {
                $scheme = 'http';
            }
            $host = trim((string) ($_SERVER['HTTP_HOST'] ?? 'localhost'));
            $scriptName = trim((string) ($_SERVER['SCRIPT_NAME'] ?? '/api/index.php'));
            $apiDir = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
            if ($apiDir === '' || $apiDir === '.') {
                $apiDir = '/api';
            }
            $http = $scheme . '://' . $host . $apiDir . '/calendar.ics.php?' . $query;
            $download = $scheme . '://' . $host . $apiDir . '/calendar.ics.php?' . $queryDl;
        }

        $webcal = $http;
        if (stripos($webcal, 'https://') === 0) {
            $webcal = 'webcal://' . substr($webcal, 8);
        } elseif (stripos($webcal, 'http://') === 0) {
            $webcal = 'webcal://' . substr($webcal, 7);
        }

        return [
            'subscriptionUrlHttp' => $http,
            'subscriptionUrlWebcal' => $webcal,
            'downloadUrl' => $download,
        ];
    }
}
