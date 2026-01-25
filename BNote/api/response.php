<?php
/**
 * Simple JSON response helper for BNote API
 * Provides consistent response format across all endpoints
 */
class Response {
    /**
     * Return successful response with data
     * @param mixed $data Response data
     */
    public static function success($data) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'data' => $data
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    /**
     * Return error response
     * @param string $message Error message
     * @param int $code HTTP status code (default: 400)
     */
    public static function error($message, $code = 400) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code($code);
        echo json_encode([
            'success' => false,
            'error' => $message,
            'code' => $code
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}
