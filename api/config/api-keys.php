<?php
/**
 * API KEYS — Cross-Subsystem Authentication
 * ─────────────────────────────────────────
 * These keys are what OTHER subsystem teams must include
 * in their requests when calling RCTS endpoints.
 *
 * IMPORTANT: In production, store these in environment variables.
 * For this project, this file is enough.
 *
 * HOW IT WORKS:
 *   Other subsystem calls RCTS endpoint → sends their API key
 *   in the header: X-API-Key: <key_below>
 *   RCTS middleware/auth.php validates it before processing.
 */

// ── Keys for subsystems calling INTO RCTS ───────────────────────────────────
// Share the matching key with the group assigned to each subsystem.
define('API_KEYS', [
    'S1'   => 'S1-CITIZEN-RCTS-KEY-2025',       // Subsystem 1  (exempted/mock)
    'S2'   => 'S2-PERMITS-RCTS-KEY-2025',        // Subsystem 2  (Permits & Licensing)
    'S3'   => 'S3-SOCIAL-RCTS-KEY-2025',         // Subsystem 3  (Social Services)
    'S4'   => 'S4-HEALTH-RCTS-KEY-2025',         // Subsystem 4  (Health & Sanitation)
    'S5'   => 'S5-EDUCATION-RCTS-KEY-2025',      // Subsystem 5  (Education)
    'S6'   => 'S6-DRRM-RCTS-KEY-2025',           // Subsystem 6  (DRRM)
    'S7'   => 'S7-ZONING-RCTS-KEY-2025',         // Subsystem 7  (exempted/mock)
    'S9'   => 'S9-TRANSPORT-RCTS-KEY-2025',      // Subsystem 9  (Transport)
    'S10'  => 'S10-ASSETS-RCTS-KEY-2025',        // Subsystem 10 (Public Assets)
    'DEV'  => 'DEV-BYPASS-KEY-LOCAL',            // Development testing bypass
]);

// ── Keys RCTS sends when calling OTHER subsystems ────────────────────────────
// These are the keys the other teams gave you for calling their APIs.
// Ask each group for their key and fill in here.
define('OUTBOUND_KEYS', [
    'S1'   => 'ASK-GROUP-1-FOR-THEIR-KEY',
    'S2'   => 'ASK-GROUP-2-FOR-THEIR-KEY',
    'S3'   => 'ASK-GROUP-3-FOR-THEIR-KEY',
    'S4'   => 'ASK-GROUP-4-FOR-THEIR-KEY',
    'S5'   => 'ASK-GROUP-5-FOR-THEIR-KEY',
    'S6'   => 'ASK-GROUP-6-FOR-THEIR-KEY',
    'S9'   => 'ASK-GROUP-9-FOR-THEIR-KEY',
    'S10'  => 'ASK-GROUP-10-FOR-THEIR-KEY',
]);

/**
 * Validate an incoming API key and return which subsystem it belongs to.
 * Returns the subsystem code (e.g. 'S2') or false if invalid.
 */
function validate_api_key(string $key): string|false {
    $keys = API_KEYS;
    $found = array_search($key, $keys);
    return $found !== false ? $found : false;
}

/**
 * Get the outbound key for calling a specific subsystem.
 */
function get_outbound_key(string $subsystem): string {
    $keys = OUTBOUND_KEYS;
    return $keys[$subsystem] ?? '';
}
