<?php
/**
 * RCTS-QC Webhook System
 * 
 * Handles real-time webhook subscriptions and event broadcasting
 * TO-BE Feature: Webhook-Based Real-Time Updates
 * 
 * AS-IS Gap: Currently uses polling/manual refresh
 * TO-BE Solution: Database-persisted webhook notifications for all financial events
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../config/supabase.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/db.php';

// Get request method and action
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// Supabase-based webhook store
function getWebhooks() {
    $result = supabase_request('webhook_subscriptions', 'GET', 
        ['active' => 'eq.true'], [], true);
    return $result['data'] ?? [];
}

function saveWebhook($data) {
    $webhook_data = [
        'endpoint_url' => $data['endpoint_url'],
        'events' => $data['events'] ?? [],
        'secret_key' => $data['secret_key'] ?? bin2hex(random_bytes(16)),
        'active' => true
    ];
    
    $result = supabase_request('webhook_subscriptions', 'POST', [], $webhook_data, true);
    
    if ($result['success'] ?? false) {
        return ['success' => true, 'message' => 'Webhook subscribed successfully'];
    } else {
        return ['success' => false, 'error' => 'Failed to save webhook', 'details' => $result];
    }
}

function triggerWebhook($eventType, $eventData) {
    // Get all active webhooks subscribed to this event
    // Note: In Supabase, we store events directly rather than calling webhooks
    $result = supabase_request('webhook_subscriptions', 'GET', 
        ['active' => 'eq.true'], [], true);
    
    $webhooks = $result['data'] ?? [];
    $results = [];
    
    foreach ($webhooks as $webhook) {
        $events = is_array($webhook['events']) ? $webhook['events'] : json_decode($webhook['events'], true);
        if (in_array('*', $events ?? []) || in_array($eventType, $events ?? [])) {
            $payload = [
                'event' => $eventType,
                'timestamp' => date('c'),
                'data' => $eventData
            ];
            
            // Send webhook (simulated for demo)
            $results[] = [
                'url' => $webhook['endpoint_url'],
                'status' => 'simulated',
                'payload' => $payload
            ];
        }
    }
    
    return $results;
}

/**
 * Store event in database for persistent polling
 * This solves the session persistence issue between API requests
 */
function storeEventInDB($eventType, $eventData) {
    try {
        $event_record = [
            'event_type' => $eventType,
            'event_data' => $eventData
        ];
        
        $result = supabase_request('webhook_events', 'POST', [], $event_record, true);
        
        if ($result['success'] ?? false) {
            return ['success' => true];
        } else {
            error_log("Failed to store webhook event: " . json_encode($result));
            return ['success' => false, 'error' => 'Failed to store event'];
        }
    } catch (Exception $e) {
        error_log("Failed to store webhook event: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Get events from database for polling
 * Uses Supabase PostgREST API for querying
 */
function getEventsFromDB($eventType = null, $lastId = 0, $since = null) {
    try {
        $params = [];
        
        if ($eventType) {
            $params['event_type'] = 'eq.' . $eventType;
        }
        
        if ($since) {
            // Convert Unix timestamp to ISO 8601 format
            $since_str = date('c', $since);
            $params['created_at'] = 'gt.' . urlencode($since_str);
        }
        
        $params['order'] = 'created_at.asc';
        $params['limit'] = '100';
        
        $result = supabase_request('webhook_events', 'GET', $params, [], true);
        
        if (!($result['success'] ?? false)) {
            error_log("Failed to get webhook events: " . json_encode($result));
            return [];
        }
        
        $events = [];
        foreach ($result['data'] ?? [] as $row) {
            $events[] = [
                'id' => (int)($row['id'] ?? 0),
                'type' => $row['event_type'],
                'timestamp' => strtotime($row['created_at']),
                'data' => is_array($row['event_data']) ? $row['event_data'] : json_decode($row['event_data'], true)
            ];
        }
        
        return $events;
    } catch (Exception $e) {
        error_log("Failed to get webhook events: " . $e->getMessage());
        return [];
    }
}

/**
 * Get all event types for polling
 */
function getAllEventTypes() {
    return [
        'payment_received', 'payment.completed', 'payment.failed',
        'bill_created', 'bill.paid', 'bill.created',
        'disbursement_completed', 'disbursement.completed',
        'ledger_updated', 'ledger.updated',
        'treasury_update', 'receipt.issued'
    ];
}

switch ($action) {
    case 'subscribe':
        // Subscribe to webhook events
        // POST: { endpoint_url, events: ["payment.completed", "bill.created"], secret_key }
        if ($method !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            exit;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (empty($input['endpoint_url'])) {
            http_response_code(400);
            echo json_encode(['error' => 'endpoint_url is required']);
            exit;
        }
        
        $result = saveWebhook($input);
        echo json_encode($result);
        break;
        
    case 'unsubscribe':
        // Unsubscribe from webhook events
        // POST: { endpoint_url }
        if ($method !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            exit;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (empty($input['endpoint_url'])) {
            http_response_code(400);
            echo json_encode(['error' => 'endpoint_url is required']);
            exit;
        }
        
        $result = supabase_request('webhook_subscriptions', 'PATCH',
            ['endpoint_url' => 'eq.' . $input['endpoint_url']],
            ['active' => false], true);
        
        echo json_encode(['success' => $result['success'] ?? false, 'message' => 'Webhook unsubscribed']);
        break;
        
    case 'list':
        // List all webhook subscriptions
        $webhooks = getWebhooks();
        echo json_encode(['success' => true, 'data' => $webhooks]);
        break;
        
    case 'events':
        // List available event types
        echo json_encode([
            'success' => true,
            'events' => [
                [
                    'name' => 'payment.completed',
                    'description' => 'Triggered when a payment is successfully processed'
                ],
                [
                    'name' => 'payment.failed',
                    'description' => 'Triggered when a payment fails'
                ],
                [
                    'name' => 'bill.created',
                    'description' => 'Triggered when a new bill is generated'
                ],
                [
                    'name' => 'bill.paid',
                    'description' => 'Triggered when a bill is marked as paid'
                ],
                [
                    'name' => 'disbursement.completed',
                    'description' => 'Triggered when a disbursement is completed'
                ],
                [
                    'name' => 'ledger.updated',
                    'description' => 'Triggered when the treasury ledger is updated'
                ],
                [
                    'name' => 'receipt.issued',
                    'description' => 'Triggered when an e-OR is issued'
                ],
                [
                    'name' => 'clearance.updated',
                    'description' => 'Triggered when a permit clearance status changes'
                ]
            ]
        ]);
        break;
        
    case 'trigger':
        // Manually trigger a webhook event (for testing)
        // GET: ?action=trigger&event_type=payment.completed&qcitizen_id=...
        $eventType = $_GET['event_type'] ?? 'test.event';
        $eventData = json_decode($_GET['data'] ?? '{}', true);
        
        if (empty($eventData)) {
            $eventData = [
                'test' => true,
                'message' => 'Test webhook event',
                'timestamp' => date('c')
            ];
        }
        
        $results = triggerWebhook($eventType, $eventData);
        
        // Store event in database for polling
        storeEventInDB($eventType, $eventData);
        
        echo json_encode([
            'success' => true,
            'event' => $eventType,
            'delivered_to' => count($results),
            'results' => $results
        ]);
        break;
        
    case 'poll':
        // Frontend polling endpoint for real-time updates
        // GET: ?action=poll&event_type=payment.completed&since=1234567890
        $eventType = $_GET['event_type'] ?? '';
        $since = intval($_GET['since'] ?? 0);
        $lastId = intval($_GET['last_id'] ?? 0);
        
        $events = getEventsFromDB($eventType ?: null, 0, $since ?: null);
        
        echo json_encode([
            'success' => true,
            'events' => $events,
            'server_time' => time()
        ]);
        break;
        
    case 'poll_events':
        // Poll ALL events across all types for real-time dashboard updates
        // GET: ?action=poll_events&last_id=0
        $lastId = intval($_GET['last_id'] ?? 0);
        
        // Get all events after last_id
        $events = getEventsFromDB(null, $lastId, null);
        
        echo json_encode([
            'success' => true,
            'events' => $events,
            'server_time' => time()
        ]);
        break;
        
    case 'broadcast':
        // Internal: Broadcast event to all subscribers and frontend
        // POST: { event_type, event_data }
        if ($method !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            exit;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        $eventType = $input['event_type'] ?? 'unknown.event';
        $eventData = $input['event_data'] ?? [];
        
        // Trigger external webhooks
        $webhookResults = triggerWebhook($eventType, $eventData);
        
        // Store event in database for persistent polling (THIS IS THE KEY FIX!)
        $dbResult = storeEventInDB($eventType, $eventData);
        
        echo json_encode([
            'success' => true,
            'event' => $eventType,
            'webhooks_delivered' => count($webhookResults),
            'event_stored' => $dbResult['success']
        ]);
        break;
        
    default:
        echo json_encode([
            'service' => 'RCTS-QC Webhook System',
            'version' => '1.0.0',
            'description' => 'Real-time webhook notifications for TO-BE Hyper-Automation',
            'storage' => 'Database (persistent)',
            'available_actions' => [
                'subscribe' => 'POST - Subscribe to webhook events',
                'unsubscribe' => 'POST - Unsubscribe from webhook events',
                'list' => 'GET - List all webhook subscriptions',
                'events' => 'GET - List available event types',
                'trigger' => 'GET - Manually trigger a webhook event (testing)',
                'poll' => 'GET - Frontend polling for real-time updates',
                'poll_events' => 'GET - Poll all events (recommended for dashboards)',
                'broadcast' => 'POST - Internal broadcast to subscribers'
            ]
        ]);
}
