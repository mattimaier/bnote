<?php
/**
 * BNote Lightweight JSON API Router
 * Routes requests to module-specific API handlers
 * 
 * URL Pattern: /next/api/index.php?module={module}&action={action}&id={id}
 * Example: /next/api/index.php?module=rehearsals&action=list
 */

// Start session (required for authentication) - check if already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Change to project root directory so relative paths work correctly (next/api -> project root)
$projectRoot = __DIR__ . '/../..';
$originalDir = getcwd();
chdir($projectRoot);

// Set global dir_prefix for data classes that use it (empty = project root)
if (!isset($GLOBALS['dir_prefix'])) {
    $GLOBALS['dir_prefix'] = '';
}

// Load BNote core - dirs.php must be loaded first
require_once $projectRoot . '/dirs.php';

// Load init.php (it uses relative paths that expect to be in project root)
require_once $projectRoot . '/src/logic/init.php';

// Load API bootstrap - all base classes needed by data modules
require_once __DIR__ . '/bootstrap.php';

// Load API helpers (keep working directory as project root for module files)
require_once __DIR__ . '/response.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/logger.php';

// Set JSON content type
header('Content-Type: application/json; charset=utf-8');

// Get module name from GET or POST
$module = $_GET['module'] ?? $_POST['module'] ?? 'dashboard';
$action = $_GET['action'] ?? $_POST['action'] ?? null;
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// Log request (before authentication check)
$requestParams = $_GET;
unset($requestParams['module'], $requestParams['action']); // Already logged separately
$requestBody = null;
if ($method === 'POST') {
    $rawInput = file_get_contents('php://input');
    if (!empty($rawInput)) {
        $requestBody = json_decode($rawInput, true);
        // Extract action from POST body if present
        if ($action === null && isset($requestBody['action'])) {
            $action = $requestBody['action'];
        }
    }
}
$requestStartTime = microtime(true);
ApiLogger::logRequest($module, $action ?? '', $method, $requestParams, $requestBody);

// Security: validate module name (alphanumeric, lowercase only)
if (!preg_match('/^[a-z]+$/', $module)) {
    Response::error('Invalid module name', 400);
}

// Load module API file
$moduleFile = __DIR__ . '/modules/' . $module . '.php';
if (!file_exists($moduleFile)) {
    Response::error('Module not found: ' . $module, 404);
}

require_once $moduleFile;

// Check authentication (except for auth and translations – login page needs both)
if ($module !== 'auth' && $module !== 'translations' && !Auth::check()) {
    Response::error('Authentication required', 403);
}

// Instantiate module handler and process request
try {
    // Convert module name to class name (capitalize first letter + "Module")
    // e.g., "auth" -> "AuthModule", "dashboard" -> "DashboardModule"
    $className = ucfirst($module) . 'Module';
    
    // Validate class exists
    if (!class_exists($className)) {
        Response::error('Module handler class not found: ' . $className, 500);
    }
    
    $handler = new $className();
    
    // Call handle method
    if (!method_exists($handler, 'handle')) {
        ApiLogger::logError($module, $action, 'Module handler missing handle() method', 500);
        Response::error('Module handler missing handle() method: ' . $module, 500);
    }
    
    $result = $handler->handle();
    $responseTime = round((microtime(true) - $requestStartTime) * 1000, 2); // Convert to milliseconds
    ApiLogger::logResponse($module, $action ?? '', 200, $result, $responseTime);
    Response::success($result);
    
} catch (BNoteError $e) {
    // BNote-specific errors
    $responseTime = round((microtime(true) - $requestStartTime) * 1000, 2);
    ApiLogger::logError($module, $action ?? '', $e->getMessage(), 400);
    ApiLogger::logResponse($module, $action ?? '', 400, ['error' => $e->getMessage()], $responseTime);
    Response::error($e->getMessage(), 400);
} catch (Error $e) {
    // PHP 7+ Error exceptions (fatal errors)
    $responseTime = round((microtime(true) - $requestStartTime) * 1000, 2);
    $errorMsg = 'Internal server error: ' . $e->getMessage();
    error_log('API Fatal Error in ' . $module . ': ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    ApiLogger::logError($module, $action ?? '', $e->getMessage(), 500);
    ApiLogger::logResponse($module, $action ?? '', 500, ['error' => $errorMsg], $responseTime);
    Response::error($errorMsg, 500);
} catch (Exception $e) {
    // General exceptions
    $responseTime = round((microtime(true) - $requestStartTime) * 1000, 2);
    $errorMsg = 'Internal server error: ' . $e->getMessage();
    error_log('API Error in ' . $module . ': ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    ApiLogger::logError($module, $action ?? '', $e->getMessage(), 500);
    ApiLogger::logResponse($module, $action ?? '', 500, ['error' => $errorMsg], $responseTime);
    Response::error($errorMsg, 500);
}
