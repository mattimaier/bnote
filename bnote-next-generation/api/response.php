<?php
/**
 * BNote Next Generation - Response Helper
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
 * Simple JSON response helper for BNote API
 * Provides consistent response format across all endpoints
 * 
 * Note: Logging is handled in api/index.php before calling these methods
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
