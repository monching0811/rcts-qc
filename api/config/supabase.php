<?php
/**
 * SUPABASE CONFIG — RCTS-QC
 * ─────────────────────────
 * RCTS uses its own Supabase project for bills/payments.
 * Subsystem 1 integration uses separate credentials.
 */

// RCTS's own Supabase (for bills, payments, ledger)
define('SUPABASE_URL',      'https://ipjtrqcncyvmtzrbsjya.supabase.co');
define('SUPABASE_ANON_KEY', 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6ImlwanRycWNuY3l2bXR6cmJzanlhIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NzI2NDI0NTMsImV4cCI6MjA4ODIxODQ1M30.BLSGY7O-uXRf-OTiFwb1Vq05I3ZdG-D82tzXp64fGrQ');
define('SUPABASE_SERVICE_KEY', 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6ImlwanRycWNuY3l2bXR6cmJzanlhIiwicm9sZSI6InNlcnZpY2Vfcm9sZSIsImlhdCI6MTc3MjY0MjQ1MywiZXhwIjoyMDg4MjE4NDUzfQ.oZtMHlzG57tZHkpFKvFT5oqKZ9PVfVTgH6t2S10DKdI'); // For server-side only

// Subsystem 1's Supabase (for citizen authentication)
define('S1_SUPABASE_URL',      'https://tjcwwocpkpmhtdtlsiuc.supabase.co');
define('S1_SUPABASE_SERVICE_KEY', 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InRqY3d3b2Nwa3BtaHRkdGxzaXVjIiwicm9sZSI6InNlcnZpY2Vfcm9sZSIsImlhdCI6MTc3MDYxOTczNiwiZXhwIjoyMDg2MTk1NzM2fQ.Yv7Q0pcs5tx-JBWcsPzIemj_RoYj1MF87ca_DbqKmMY');

// Subsystem 9's Supabase (for traffic violations integration)
define('S9_SUPABASE_URL',      'https://nhnynmdhamvspyujjpws.supabase.co');
define('S9_SUPABASE_SERVICE_KEY', 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6Im5obnlubWRoYW12c3B5dWpqcHdzIiwicm9sZSI6InNlcnZpY2Vfcm9sZSIsImlhdCI6MTc3MzQ2MzE3OCwiZXhwIjoyMDg5MDM5MTc4fQ.j7gfUTF-G2riitI13k-N9OYcymeNnchC3KF06Wniys');

// ── REST API base URL (used by all PHP API files) ───────────────────────────
define('SUPABASE_REST',     SUPABASE_URL . '/rest/v1');
define('SUPABASE_AUTH',     SUPABASE_URL . '/auth/v1');

// S1 (Subsystem 1) REST API base URL
define('S1_SUPABASE_REST',  S1_SUPABASE_URL . '/rest/v1');

// S9 (Subsystem 9) REST API base URL
define('S9_SUPABASE_REST',  S9_SUPABASE_URL . '/rest/v1');

// ── Helper: build headers for Supabase REST requests ───────────────────────
function supabase_headers(bool $use_service_key = false): array {
    $key = $use_service_key ? SUPABASE_SERVICE_KEY : SUPABASE_ANON_KEY;
    return [
        'Content-Type: application/json',
        'apikey: ' . $key,
        'Authorization: Bearer ' . $key,
        'Prefer: return=representation'
    ];
}

// ── Helper: build headers for S1 (Subsystem 1) Supabase REST requests ────────
function s1_supabase_headers(): array {
    return [
        'Content-Type: application/json',
        'apikey: ' . S1_SUPABASE_SERVICE_KEY,
        'Authorization: Bearer ' . S1_SUPABASE_SERVICE_KEY,
        'Prefer: return=representation'
    ];
}

// ── Helper: build headers for S9 (Subsystem 9) Supabase REST requests ────────
function s9_supabase_headers(): array {
    return [
        'Content-Type: application/json',
        'apikey: ' . S9_SUPABASE_SERVICE_KEY,
        'Authorization: Bearer ' . S9_SUPABASE_SERVICE_KEY,
        'Prefer: return=representation'
    ];
}

// ── Core Supabase REST caller ───────────────────────────────────────────────
function supabase_request(
    string $table,
    string $method     = 'GET',
    array  $params     = [],
    array  $body       = [],
    bool   $service_key = false
): array {
    $url     = SUPABASE_REST . '/' . $table;
    $headers = supabase_headers($service_key);

    // Append query string params for GET/DELETE/PATCH
    if (!empty($params) && in_array($method, ['GET', 'DELETE', 'PATCH'])) {
        $url .= '?' . http_build_query($params);
    }

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_CUSTOMREQUEST  => $method,
        CURLOPT_TIMEOUT        => 15,
    ]);

    if (in_array($method, ['POST', 'PUT', 'PATCH']) && !empty($body)) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    }

    $response  = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error     = curl_error($ch);
    curl_close($ch);

    if ($error) {
        return ['success' => false, 'error' => $error, 'data' => null];
    }

    $decoded = json_decode($response, true);

    return [
        'success'   => ($http_code >= 200 && $http_code < 300),
        'http_code' => $http_code,
        'data'      => $decoded
    ];
}

// ── S1 (Subsystem 1) REST caller ──────────────────────────────────────────────
function s1_supabase_request(
    string $endpoint,
    string $method     = 'GET',
    array  $body       = []
): array {
    $url     = S1_SUPABASE_REST . '/' . $endpoint;
    $headers = s1_supabase_headers();

    // Append query string for GET
    if (strpos($url, '?') === false && $method === 'GET' && !empty($body)) {
        $url .= '?' . http_build_query($body);
    }

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_CUSTOMREQUEST  => $method,
        CURLOPT_TIMEOUT        => 15,
    ]);

    if (in_array($method, ['POST', 'PUT', 'PATCH']) && !empty($body)) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    }

    $response  = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error     = curl_error($ch);
    curl_close($ch);

    if ($error) {
        return ['success' => false, 'error' => $error, 'data' => null];
    }

    $decoded = json_decode($response, true);

    return [
        'success'   => ($http_code >= 200 && $http_code < 300),
        'http_code' => $http_code,
        'data'      => $decoded
    ];
}

// ── S9 (Subsystem 9) REST caller ──────────────────────────────────────────────
function s9_supabase_request(
    string $endpoint,
    string $method     = 'GET',
    array  $body       = []
): array {
    $url     = S9_SUPABASE_REST . '/' . $endpoint;
    $headers = s9_supabase_headers();

    // Append query string for GET
    if (strpos($url, '?') === false && $method === 'GET' && !empty($body)) {
        $url .= '?' . http_build_query($body);
    }

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_CUSTOMREQUEST  => $method,
        CURLOPT_TIMEOUT        => 15,
    ]);

    if (in_array($method, ['POST', 'PUT', 'PATCH']) && !empty($body)) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    }

    $response  = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error     = curl_error($ch);
    curl_close($ch);

    if ($error) {
        return ['success' => false, 'error' => $error, 'data' => null];
    }

    $decoded = json_decode($response, true);

    return [
        'success'   => ($http_code >= 200 && $http_code < 300),
        'http_code' => $http_code,
        'data'      => $decoded
    ];
}

// ── Shorthand query builder ─────────────────────────────────────────────────
// Usage: db_select('rcts_citizen_registry', ['qcitizen_id=eq.QC-2024-000001'])
function db_select(string $table, array $filters = [], string $select = '*'): array {
    $params = array_merge(['select' => $select], $filters);
    return supabase_request($table, 'GET', $params);
}

function db_insert(string $table, array $data): array {
    return supabase_request($table, 'POST', [], $data, true);
}

function db_update(string $table, array $filters, array $data): array {
    return supabase_request($table, 'PATCH', $filters, $data, true);
}

function db_delete(string $table, array $filters): array {
    return supabase_request($table, 'DELETE', $filters, [], true);
}
