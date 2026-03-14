<?php
/**
 * MIDDLEWARE: API Rate Limiter
 * api/middleware/rate-limit.php
 *
 * Tracks requests per API key per minute using a flat-file counter.
 * In production, swap the file store for Redis or Memcached.
 *
 * USAGE (add at the top of any endpoint that needs limiting):
 *   require_once __DIR__ . '/rate-limit.php';
 *   rate_limit_check('S9', 60, 100); // allow S9 up to 100 req/min
 */

define('RATE_LIMIT_DIR', __DIR__ . '/../../logs/rate-limits/');

/**
 * Check and enforce rate limit for a given key.
 *
 * @param string $key       Identifier — API key, IP, or subsystem code
 * @param int    $window    Rolling window in seconds (default 60)
 * @param int    $max_reqs  Maximum requests allowed in that window (default 60)
 */
function rate_limit_check(string $key, int $window = 60, int $max_reqs = 60): void {
    if (!is_dir(RATE_LIMIT_DIR)) {
        mkdir(RATE_LIMIT_DIR, 0755, true);
    }

    $safe_key  = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $key);
    $file      = RATE_LIMIT_DIR . $safe_key . '.json';
    $now       = time();
    $data      = [];

    // Load existing counter
    if (file_exists($file)) {
        $raw  = file_get_contents($file);
        $data = json_decode($raw, true) ?? [];
    }

    // Prune timestamps outside the rolling window
    $data = array_values(array_filter($data, fn($ts) => $ts > $now - $window));

    // Check limit
    if (count($data) >= $max_reqs) {
        $retry_after = ($data[0] + $window) - $now;
        header('Retry-After: ' . $retry_after);
        http_response_code(429);
        echo json_encode([
            'success'     => false,
            'message'     => 'Rate limit exceeded. Maximum ' . $max_reqs . ' requests per ' . $window . ' seconds.',
            'retry_after' => $retry_after,
            'limit'       => $max_reqs,
            'window'      => $window
        ]);
        exit;
    }

    // Log this request
    $data[] = $now;
    file_put_contents($file, json_encode($data), LOCK_EX);

    // Expose rate limit headers
    header('X-RateLimit-Limit: '     . $max_reqs);
    header('X-RateLimit-Remaining: ' . ($max_reqs - count($data)));
    header('X-RateLimit-Reset: '     . ($now + $window));
}

/**
 * Stricter limit for inbound webhook endpoints.
 * Subsystems get 300 req/min; anonymous callers get 30 req/min.
 */
function rate_limit_inbound(string $caller_key): void {
    $is_known = !empty($caller_key) && $caller_key !== 'unknown';
    $max      = $is_known ? 300 : 30;
    rate_limit_check('inbound_' . ($is_known ? $caller_key : 'anon'), 60, $max);
}

/**
 * Strict limit for the payment execute endpoint — anti-fraud.
 * Max 5 payment attempts per IP per minute.
 */
function rate_limit_payment(): void {
    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    rate_limit_check('payment_' . $ip, 60, 5);
}