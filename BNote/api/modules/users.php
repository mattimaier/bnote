<?php
/**
 * Users API module
 * Provides user management endpoints
 * 
 * Note: This file is loaded after api/index.php has changed working directory to project root
 */
// Load DefaultController before LoginController (LoginController extends DefaultController)
require_once __DIR__ . '/../../src/logic/defaultcontroller.php';
require_once __DIR__ . '/../../src/data/modules/userdata.php';
require_once __DIR__ . '/../../src/logic/modules/logincontroller.php';
require_once __DIR__ . '/../../src/logic/mailing.php';
require_once __DIR__ . '/../response.php';
require_once __DIR__ . '/../auth.php';

class UsersModule {
    private $data;
    
    public function __construct() {
        // Check module permission
        if (!Auth::checkModule('User')) {
            Response::error('Access denied to User Management', 403);
        }
        
        $this->data = new UserData();
    }
    
    public function handle() {
        $action = $_GET['action'] ?? $_POST['action'] ?? 'list';
        
        switch ($action) {
            case 'list':
                return $this->listUsers();
            case 'get':
                return $this->getUser();
            case 'create':
                return $this->createUser();
            case 'update':
                return $this->updateUser();
            case 'delete':
                return $this->deleteUser();
            case 'activate':
                return $this->activateUser();
            case 'getPrivileges':
                return $this->getPrivileges();
            case 'updatePrivileges':
                return $this->updatePrivileges();
            case 'getContacts':
                return $this->getContacts();
            case 'getLongInactiveUsers':
                return $this->getLongInactiveUsers();
            case 'deleteUsersFull':
                return $this->deleteUsersFull();
            default:
                Response::error('Unknown action: ' . $action, 400);
        }
    }
    
    /**
     * Get all users (respects super user restrictions)
     */
    private function listUsers() {
        // Use custom query to get firstName and lastName separately
        global $system_data;
        $query = "SELECT u.id, u.isActive, u.login, ";
        $query .= "c.name as firstName, c.surname as lastName, ";
        $query .= "CONCAT_WS(' ', c.name, c.surname) as name, u.lastlogin";
        $query .= " FROM user u LEFT JOIN contact c ON u.contact = c.id";
        
        $params = [];
        if (!$system_data->isUserSuperUser() && count($system_data->getSuperUsers()) > 0) {
            $whereQ = [];
            foreach ($system_data->getSuperUsers() as $su) {
                $whereQ[] = "u.id <> ?";
                $params[] = ['i', $su];
            }
            $query .= " WHERE " . join(" AND ", $whereQ);
        }
        $query .= " ORDER BY name, id";
        
        $users = $system_data->dbcon->getSelection($query, $params);
        
        // Convert to array format (skip first row which is header)
        $result = [];
        for ($i = 1; $i < count($users); $i++) {
            $user = $users[$i];
            $result[] = [
                'id' => intval($user['id']),
                'login' => $user['login'] ?? '',
                'name' => $user['name'] ?? '', // Keep for backward compatibility
                'firstName' => $user['firstName'] ?? '',
                'lastName' => $user['lastName'] ?? '',
                'isActive' => intval($user['isActive']) === 1,
                'lastlogin' => $user['lastlogin'] ?? null
            ];
        }
        
        return $result;
    }
    
    /**
     * Get single user by ID
     */
    private function getUser() {
        $id = $_GET['id'] ?? $_POST['id'] ?? null;
        if (!$id) {
            Response::error('User ID required', 400);
        }
        
        // Check super user restrictions
        global $system_data;
        if (!$system_data->isUserSuperUser() && $system_data->isUserSuperUser($id)) {
            Response::error('Access denied', 403);
        }
        
        // Get user with joined contact info
        $user = $this->data->findByIdJoined($id, ['contact' => ['name', 'surname']]);
        
        if (!$user) {
            Response::error('User not found', 404);
        }
        
        // Format response
        $result = [
            'id' => intval($user['id']),
            'login' => $user['login'] ?? '',
            'isActive' => intval($user['isActive']) === 1,
            'lastlogin' => $user['lastlogin'] ?? null,
            'contact' => intval($user['contact'] ?? 0)
        ];
        
        // Add contact info if available
        if (isset($user['contactname']) || isset($user['contactsurname'])) {
            $result['contactName'] = trim(($user['contactname'] ?? '') . ' ' . ($user['contactsurname'] ?? ''));
            $result['contactSurname'] = $user['contactsurname'] ?? '';
            $result['contactFirstName'] = $user['contactname'] ?? '';
        }
        
        return $result;
    }
    
    /**
     * Create new user
     */
    private function createUser() {
        $rawInput = file_get_contents('php://input');
        $data = json_decode($rawInput, true);
        
        if (!$data) {
            $data = $_POST;
        }
        
        // Validate required fields
        if (empty($data['login'])) {
            Response::error('Login is required', 400);
        }
        if (empty($data['password'])) {
            Response::error('Password is required', 400);
        }
        if (empty($data['contact'])) {
            Response::error('Contact is required', 400);
        }
        
        // Prepare values for UserData->create()
        $values = [
            'login' => $data['login'],
            'password' => $data['password'],
            'contact' => $data['contact'],
            'isActive' => isset($data['isActive']) ? ($data['isActive'] ? 'on' : '') : 'on'
        ];
        
        try {
            $this->data->create($values);
            
            // Get the created user ID (it's returned by parent::create())
            // We need to find it by login
            $query = "SELECT id FROM user WHERE login = ?";
            global $system_data;
            $userId = $system_data->dbcon->colValue($query, 'id', [['s', $data['login']]]);
            
            return [
                'success' => true,
                'id' => intval($userId),
                'message' => 'User created successfully'
            ];
        } catch (BNoteError $e) {
            Response::error($e->getMessage(), 400);
        }
    }
    
    /**
     * Update user
     */
    private function updateUser() {
        $rawInput = file_get_contents('php://input');
        $data = json_decode($rawInput, true);
        
        if (!$data) {
            $data = $_POST;
        }
        
        $id = $data['id'] ?? $_GET['id'] ?? null;
        if (!$id) {
            Response::error('User ID required', 400);
        }
        
        // Check super user restrictions
        global $system_data;
        if (!$system_data->isUserSuperUser() && $system_data->isUserSuperUser($id)) {
            Response::error('Access denied', 403);
        }
        
        // Prepare values for UserData->update()
        // Note: UserData->update() expects $_POST format, so we need to simulate it
        $_POST = [];
        $_GET['id'] = $id;
        
        if (isset($data['password']) && !empty($data['password'])) {
            $_POST['password'] = $data['password'];
        }
        
        if (isset($data['contact'])) {
            $_POST['contact'] = $data['contact'] == 0 ? '0' : $data['contact'];
        }
        
        if (isset($data['isActive'])) {
            $_POST['isActive'] = $data['isActive'] ? 'on' : '';
        }
        
        try {
            $this->data->update($id, $_POST);
            
            return [
                'success' => true,
                'message' => 'User updated successfully'
            ];
        } catch (BNoteError $e) {
            Response::error($e->getMessage(), 400);
        }
    }
    
    /**
     * Delete user
     */
    private function deleteUser() {
        $rawInput = file_get_contents('php://input');
        $data = json_decode($rawInput, true);
        
        if (!$data) {
            $data = $_POST;
        }
        
        $id = $data['id'] ?? $_GET['id'] ?? null;
        if (!$id) {
            Response::error('User ID required', 400);
        }
        
        // Check super user restrictions
        global $system_data;
        if (!$system_data->isUserSuperUser() && $system_data->isUserSuperUser($id)) {
            Response::error('Access denied', 403);
        }
        
        try {
            $this->data->delete($id);
            
            return [
                'success' => true,
                'message' => 'User deleted successfully'
            ];
        } catch (BNoteError $e) {
            Response::error($e->getMessage(), 400);
        }
    }
    
    /**
     * Activate/deactivate user
     */
    private function activateUser() {
        $rawInput = file_get_contents('php://input');
        $data = json_decode($rawInput, true);
        
        if (!$data) {
            $data = $_POST;
        }
        
        $id = $data['id'] ?? $_GET['id'] ?? null;
        if (!$id) {
            Response::error('User ID required', 400);
        }
        
        // Check super user restrictions
        global $system_data;
        if (!$system_data->isUserSuperUser() && $system_data->isUserSuperUser($id)) {
            Response::error('Access denied', 403);
        }
        
        try {
            $wasActivated = $this->data->changeUserStatus($id);
            
            // Send email if user was activated
            if ($wasActivated) {
                $to = $this->data->getUsermail($id);
                if ($to) {
                    $subject = Lang::txt("UserController_activate.message_1");
                    $body = Lang::txt("UserController_activate.message_2") . 
                            $system_data->getCompany() . 
                            Lang::txt("UserController_activate.message_3");
                    $body .= Lang::txt("UserController_activate.message_4") . 
                            $system_data->getSystemURL() . 
                            Lang::txt("UserController_activate.message_5");
                    
                    $mail = new Mailing($subject, $body);
                    $mail->setTo($to);
                    $mail->sendMail(); // Don't fail if email fails
                }
            }
            
            return [
                'success' => true,
                'isActive' => $wasActivated,
                'message' => $wasActivated ? 'User activated' : 'User deactivated'
            ];
        } catch (BNoteError $e) {
            Response::error($e->getMessage(), 400);
        }
    }
    
    /**
     * Get user's module privileges
     */
    private function getPrivileges() {
        $id = $_GET['id'] ?? $_POST['id'] ?? null;
        if (!$id) {
            Response::error('User ID required', 400);
        }
        
        // Check super user restrictions
        global $system_data;
        if (!$system_data->isUserSuperUser() && $system_data->isUserSuperUser($id)) {
            Response::error('Access denied', 403);
        }
        
        $privileges = $this->data->getPrivileges($id);
        
        // Convert to simple array of module IDs
        $moduleIds = [];
        for ($i = 1; $i < count($privileges); $i++) {
            $moduleIds[] = intval($privileges[$i]['id']);
        }
        
        // Also get all available modules for the form
        $allModules = $system_data->getModuleArray();
        $modules = [];
        foreach ($allModules as $modId => $modRow) {
            // Skip public modules
            if ($modRow['category'] == 'public') {
                continue;
            }
            
            $modules[] = [
                'id' => intval($modId),
                'name' => $modRow['name'],
                'hasAccess' => in_array(intval($modId), $moduleIds)
            ];
        }
        
        return [
            'userId' => intval($id),
            'privileges' => $moduleIds,
            'modules' => $modules
        ];
    }
    
    /**
     * Update user's module privileges
     */
    private function updatePrivileges() {
        $rawInput = file_get_contents('php://input');
        $data = json_decode($rawInput, true);
        
        if (!$data) {
            $data = $_POST;
        }
        
        $id = $data['id'] ?? $_GET['id'] ?? null;
        if (!$id) {
            Response::error('User ID required', 400);
        }
        
        // Check super user restrictions
        global $system_data;
        if (!$system_data->isUserSuperUser() && $system_data->isUserSuperUser($id)) {
            Response::error('Access denied', 403);
        }
        
        // Prepare $_POST format for UserData->updatePrivileges()
        // It expects: [modid] => 'on' for checked modules
        $_POST = [];
        if (isset($data['privileges']) && is_array($data['privileges'])) {
            foreach ($data['privileges'] as $moduleId) {
                $_POST[$moduleId] = 'on';
            }
        }
        
        try {
            $this->data->updatePrivileges($id);
            
            return [
                'success' => true,
                'message' => 'Privileges updated successfully'
            ];
        } catch (BNoteError $e) {
            Response::error($e->getMessage(), 400);
        }
    }
    
    /**
     * Get list of contacts for dropdown
     */
    private function getContacts() {
        $contacts = $this->data->getContacts();
        
        $result = [];
        // Skip first row (header)
        for ($i = 1; $i < count($contacts); $i++) {
            $contact = $contacts[$i];
            $name = trim(($contact['name'] ?? '') . ' ' . ($contact['surname'] ?? ''));
            $instrument = isset($contact['instrumentname']) ? $contact['instrumentname'] : '';
            
            $label = $name;
            if ($instrument) {
                $label .= ' (' . $instrument . ')';
            }
            
            $result[] = [
                'id' => intval($contact['id']),
                'label' => $label,
                'name' => $contact['name'] ?? '',
                'surname' => $contact['surname'] ?? '',
                'instrument' => $instrument
            ];
        }
        
        return $result;
    }
    
    /**
     * Get users inactive for 24+ months (GDPR)
     */
    private function getLongInactiveUsers() {
        // Check if user is super user (GDPR is admin function)
        global $system_data;
        if (!$system_data->isUserSuperUser()) {
            Response::error('Access denied - Super user required', 403);
        }
        
        $users = $this->data->getLongInactiveUsers();
        
        $result = [];
        for ($i = 1; $i < count($users); $i++) {
            $user = $users[$i];
            $result[] = [
                'id' => intval($user['id']),
                'login' => $user['login'] ?? '',
                'lastlogin' => $user['lastlogin'] ?? null
            ];
        }
        
        return $result;
    }
    
    /**
     * Delete users with full data cleanup (GDPR)
     */
    private function deleteUsersFull() {
        // Check if user is super user (GDPR is admin function)
        global $system_data;
        if (!$system_data->isUserSuperUser()) {
            Response::error('Access denied - Super user required', 403);
        }
        
        $rawInput = file_get_contents('php://input');
        $data = json_decode($rawInput, true);
        
        if (!$data) {
            $data = $_POST;
        }
        
        $userIds = $data['userIds'] ?? [];
        if (empty($userIds) || !is_array($userIds)) {
            Response::error('User IDs array required', 400);
        }
        
        // Get full user records
        $inactiveUsers = [];
        foreach ($userIds as $userId) {
            $user = $this->data->findByIdNoRef($userId);
            if ($user) {
                $inactiveUsers[] = $user;
            }
        }
        
        // Add header row (UserData->deleteUsersFull expects this format)
        array_unshift($inactiveUsers, ['id' => 'id']);
        
        try {
            $this->data->deleteUsersFull($inactiveUsers);
            
            return [
                'success' => true,
                'message' => 'Users deleted successfully',
                'count' => count($userIds)
            ];
        } catch (BNoteError $e) {
            Response::error($e->getMessage(), 400);
        }
    }
}
