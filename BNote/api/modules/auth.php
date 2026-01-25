<?php
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
require_once __DIR__ . '/../../src/logic/defaultcontroller.php';
require_once __DIR__ . '/../../src/data/modules/logindata.php';
require_once __DIR__ . '/../../src/logic/modules/logincontroller.php';
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
        
        $requestedUserId = $this->loginData->getUserIdForLogin($username);
        if ($requestedUserId < 0) {
            $requestedUserId = $this->loginData->getUserIdForEMail($username);
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
}
