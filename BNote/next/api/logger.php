<?php
/**
 * API Logger
 * Logs all API requests and responses when detailed logging is enabled
 */

class ApiLogger {
    private static $logDir = null;
    private static $enabled = null;
    
    /**
     * Check if detailed logging is enabled
     */
    private static function isEnabled() {
        if (self::$enabled === null) {
            global $system_data;
            if (!isset($system_data)) {
                // System not initialized yet, default to disabled
                self::$enabled = false;
                return false;
            }
            $setting = $system_data->getDynamicConfigParameter("api_detailed_logging");
            self::$enabled = ($setting === "1" || $setting === 1);
        }
        return self::$enabled;
    }
    
    /**
     * Get log directory path
     */
    private static function getLogDir() {
        if (self::$logDir === null) {
            // Use the existing log directory (next/api -> project root -> log/api)
            self::$logDir = __DIR__ . '/../../log/api/';
            if (!is_dir(self::$logDir)) {
                mkdir(self::$logDir, 0755, true);
            }
        }
        return self::$logDir;
    }
    
    /**
     * Log API request
     */
    public static function logRequest($module, $action, $method, $params, $body = null) {
        if (!self::isEnabled()) {
            return;
        }
        
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'type' => 'request',
            'module' => $module,
            'action' => $action,
            'method' => $method,
            'params' => $params,
            'body' => $body,
            'session_id' => session_id(),
            'user_id' => $_SESSION['user'] ?? null,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ];
        
        self::writeLog($logEntry);
    }
    
    /**
     * Log API response
     */
    public static function logResponse($module, $action, $statusCode, $response, $responseTime = null) {
        if (!self::isEnabled()) {
            return;
        }
        
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'type' => 'response',
            'module' => $module,
            'action' => $action,
            'status_code' => $statusCode,
            'response' => $response,
            'response_time_ms' => $responseTime,
            'session_id' => session_id(),
            'user_id' => $_SESSION['user'] ?? null
        ];
        
        self::writeLog($logEntry);
    }
    
    /**
     * Log API error
     */
    public static function logError($module, $action, $error, $code = 500) {
        if (!self::isEnabled()) {
            return;
        }
        
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'type' => 'error',
            'module' => $module,
            'action' => $action,
            'error' => $error,
            'code' => $code,
            'session_id' => session_id(),
            'user_id' => $_SESSION['user'] ?? null,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ];
        
        self::writeLog($logEntry);
    }
    
    /**
     * Write log entry to file
     */
    private static function writeLog($entry) {
        $logDir = self::getLogDir();
        $date = date('Y-m-d');
        $logFile = $logDir . 'api_' . $date . '.log';
        
        // Format log entry as JSON
        $logLine = json_encode($entry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
        
        // Append to log file
        file_put_contents($logFile, $logLine, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Get recent log entries (for debugging)
     */
    public static function getRecentLogs($limit = 100) {
        if (!self::isEnabled()) {
            return [];
        }
        
        $logDir = self::getLogDir();
        $date = date('Y-m-d');
        $logFile = $logDir . 'api_' . $date . '.log';
        
        if (!file_exists($logFile)) {
            return [];
        }
        
        $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $logs = [];
        
        // Get last N lines
        $lines = array_slice($lines, -$limit);
        
        foreach ($lines as $line) {
            $log = json_decode($line, true);
            if ($log) {
                $logs[] = $log;
            }
        }
        
        return $logs;
    }
}
