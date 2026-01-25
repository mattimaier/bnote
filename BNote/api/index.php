<?php
/**
 * BNote Lightweight JSON API Router
 * Routes requests to module-specific API handlers
 * 
 * URL Pattern: /api/index.php?module={module}&action={action}&id={id}
 * Example: /api/index.php?module=rehearsals&action=list
 */

// Start session (required for authentication) - check if already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Change to project root directory so relative paths work correctly
$projectRoot = __DIR__ . '/..';
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

// Set JSON content type
header('Content-Type: application/json; charset=utf-8');

// Get module name from GET or POST
$module = $_GET['module'] ?? $_POST['module'] ?? 'dashboard';

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

// Check authentication (except for auth module itself)
if ($module !== 'auth' && !Auth::check()) {
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
        Response::error('Module handler missing handle() method: ' . $module, 500);
    }
    
    $result = $handler->handle();
    Response::success($result);
    
} catch (BNoteError $e) {
    // BNote-specific errors
    Response::error($e->getMessage(), 400);
} catch (Error $e) {
    // PHP 7+ Error exceptions (fatal errors)
    error_log('API Fatal Error in ' . $module . ': ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    Response::error('Internal server error: ' . $e->getMessage(), 500);
} catch (Exception $e) {
    // General exceptions
    error_log('API Error in ' . $module . ': ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    Response::error('Internal server error: ' . $e->getMessage(), 500);
}
