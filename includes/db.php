<?php
/**
 * INCLUDE: Database Helper Aliases
 * includes/db.php
 *
 * Thin wrapper that loads the Supabase config and exposes
 * clean, short-named helpers for use in any endpoint or page.
 * Just do: require_once __DIR__ . '/../includes/db.php';
 */

require_once __DIR__ . '/../api/config/supabase.php';
require_once __DIR__ . '/../api/config/constants.php';

/**
 * Shorthand: SELECT from any table with optional query params.
 *
 * @param  string $table   Supabase table or view name
 * @param  array  $params  PostgREST filter params e.g. ['status'=>'eq.Pending']
 * @return array  ['success'=>bool, 'data'=>array]
 *
 * EXAMPLE:
 *   $bills = db('rcts_assessment_billing_hub', ['status'=>'eq.Pending']);
 */
function db(string $table, array $params = []): array {
    return db_select($table, $params);
}

/**
 * Shorthand: INSERT a single row.
 *
 * @param  string $table  Table name
 * @param  array  $data   Associative array of column => value
 * @return array  ['success'=>bool, 'data'=>array]
 */
function db_create(string $table, array $data): array {
    return db_insert($table, $data);
}

/**
 * Shorthand: UPDATE rows matching filter.
 *
 * @param  string $table   Table name
 * @param  array  $filter  PostgREST filter e.g. ['id'=>'eq.5']
 * @param  array  $data    Columns to update
 * @return array  ['success'=>bool, 'data'=>array]
 */
function db_patch(string $table, array $filter, array $data): array {
    return db_update($table, $filter, $data);
}

/**
 * Shorthand: fetch a SINGLE row by primary key.
 *
 * @param  string $table  Table name
 * @param  string $pk     Primary key column name
 * @param  mixed  $value  Primary key value
 * @return array|null     The row or null if not found
 */
function db_find(string $table, string $pk, $value): ?array {
    $result = db_select($table, [$pk => 'eq.' . $value]);
    return $result['data'][0] ?? null;
}

/**
 * Shorthand: check if a row exists.
 *
 * @param  string $table  Table name
 * @param  array  $filter PostgREST filter
 * @return bool
 */
function db_exists(string $table, array $filter): bool {
    $result = db_select($table, array_merge($filter, ['select' => 'count']));
    return !empty($result['data']);
}

/**
 * Paginate a table query.
 *
 * @param  string $table   Table name
 * @param  array  $params  Filter params
 * @param  int    $page    Page number (0-indexed)
 * @param  int    $size    Page size
 * @return array  ['data'=>array, 'page'=>int, 'size'=>int, 'has_more'=>bool]
 */
function db_paginate(string $table, array $params, int $page = 0, int $size = 20): array {
    $params['limit']  = $size + 1; // fetch one extra to detect next page
    $params['offset'] = $page * $size;
    $result = db_select($table, $params);
    $rows   = $result['data'] ?? [];
    $has_more = count($rows) > $size;
    if ($has_more) array_pop($rows); // remove the extra row
    return [
        'data'     => $rows,
        'page'     => $page,
        'size'     => $size,
        'has_more' => $has_more
    ];
}