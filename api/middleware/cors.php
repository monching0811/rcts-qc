<?php
/**
 * CORS MIDDLEWARE
 * ───────────────
 * Allows other subsystem servers to call RCTS API endpoints.
 * Include this at the top of every api/endpoints/*.php file.
 */

// Allow all origins during development (restrict in production)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-API-Key, Authorization');
header('Content-Type: application/json');

// Handle browser preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}
