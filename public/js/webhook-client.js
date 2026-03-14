/**
 * RCTS-QC Webhook Client
 *
 * Provides real-time updates via webhook polling
 * TO-BE Feature: Webhook-Based Real-Time Updates
 *
 * AS-IS Gap: Uses polling/manual refresh
 * TO-BE Solution: Instant webhook notifications
 */

class WebhookClient {
  constructor(options = {}) {
    this.apiBase = options.apiBase || "../../api/endpoints/webhook.php";
    this.pollInterval = options.pollInterval || 3000; // 3 seconds
    this.subscriptions = new Map();
    this.lastTimestamps = new Map();
    this.isPolling = false;
    this.pollTimer = null;
    this.listeners = new Map();

    // Event callback handlers
    this.onPaymentCompleted = null;
    this.onBillCreated = null;
    this.onLedgerUpdated = null;
    this.onReceiptIssued = null;
    this.onDisbursementCompleted = null;
  }

  /**
   * Subscribe to webhook events
   * @param {string} endpointUrl - Webhook endpoint URL
   * @param {Array} events - Array of event names to subscribe
   * @returns {Promise}
   */
  async subscribe(endpointUrl, events) {
    const response = await fetch(`${this.apiBase}?action=subscribe`, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        endpoint_url: endpointUrl,
        events: events,
      }),
    });
    return response.json();
  }

  /**
   * Unsubscribe from webhook events
   * @param {string} endpointUrl - Webhook endpoint URL
   * @returns {Promise}
   */
  async unsubscribe(endpointUrl) {
    const response = await fetch(`${this.apiBase}?action=unsubscribe`, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ endpoint_url: endpointUrl }),
    });
    return response.json();
  }

  /**
   * Get list of available event types
   * @returns {Promise}
   */
  async getEventTypes() {
    const response = await fetch(`${this.apiBase}?action=events`);
    return response.json();
  }

  /**
   * Subscribe to real-time events (frontend)
   * @param {string} eventType - Event type to listen for
   * @param {function} callback - Callback function
   */
  on(eventType, callback) {
    if (!this.listeners.has(eventType)) {
      this.listeners.set(eventType, []);
    }
    this.listeners.get(eventType).push(callback);
  }

  /**
   * Emit event to all listeners
   * @param {string} eventType - Event type
   * @param {object} data - Event data
   */
  emit(eventType, data) {
    if (this.listeners.has(eventType)) {
      this.listeners.get(eventType).forEach((callback) => {
        try {
          callback(data);
        } catch (e) {
          console.error(`Webhook listener error for ${eventType}:`, e);
        }
      });
    }

    // Also emit to wildcard listeners
    if (this.listeners.has("*")) {
      this.listeners.get("*").forEach((callback) => {
        try {
          callback(eventType, data);
        } catch (e) {
          console.error(`Webhook wildcard listener error:`, e);
        }
      });
    }
  }

  /**
   * Start polling for real-time updates
   * @param {Array} eventTypes - Array of event types to poll
   */
  startPolling(
    eventTypes = [
      "payment.completed",
      "bill.created",
      "ledger.updated",
      "receipt.issued",
      "disbursement.completed",
    ],
  ) {
    if (this.isPolling) {
      console.warn("Webhook polling already started");
      return;
    }

    this.isPolling = true;
    this.pollEventTypes = eventTypes;

    console.log("[Webhook] Starting real-time updates...");

    const poll = async () => {
      if (!this.isPolling) return;

      try {
        for (const eventType of eventTypes) {
          const lastTimestamp = this.lastTimestamps.get(eventType) || 0;

          const response = await fetch(
            `${this.apiBase}?action=poll&event_type=${encodeURIComponent(eventType)}&since=${lastTimestamp}`,
          );
          const result = await response.json();

          if (result.success && result.events && result.events.length > 0) {
            console.log(
              `[Webhook] Received ${result.events.length} ${eventType} events`,
            );

            result.events.forEach((event) => {
              this.emit(eventType, event.data);

              // Update last timestamp
              if (event.timestamp > lastTimestamp) {
                this.lastTimestamps.set(eventType, event.timestamp);
              }
            });
          }
        }
      } catch (e) {
        console.error("[Webhook] Polling error:", e);
      }

      // Schedule next poll
      if (this.isPolling) {
        this.pollTimer = setTimeout(poll, this.pollInterval);
      }
    };

    // Start first poll immediately
    poll();
  }

  /**
   * Stop polling for real-time updates
   */
  stopPolling() {
    this.isPolling = false;
    if (this.pollTimer) {
      clearTimeout(this.pollTimer);
      this.pollTimer = null;
    }
    console.log("[Webhook] Stopped real-time updates");
  }

  /**
   * Manually trigger a webhook event (for testing)
   * @param {string} eventType - Event type
   * @param {object} data - Event data
   * @returns {Promise}
   */
  async triggerEvent(eventType, data = {}) {
    const response = await fetch(
      `${this.apiBase}?action=trigger&event_type=${encodeURIComponent(eventType)}&data=${encodeURIComponent(JSON.stringify(data))}`,
    );
    return response.json();
  }

  /**
   * Broadcast event to all subscribers (internal use)
   * @param {string} eventType - Event type
   * @param {object} eventData - Event data
   * @returns {Promise}
   */
  async broadcast(eventType, eventData) {
    const response = await fetch(`${this.apiBase}?action=broadcast`, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        event_type: eventType,
        event_data: eventData,
      }),
    });
    return response.json();
  }
}

// Global Webhook manager instance
window.WebhookManager = new WebhookClient();

// Utility function for pages to use
window.initRealTimeUpdates = function (options = {}) {
  const client = window.WebhookManager;

  // Set up default event handlers
  if (options.onPaymentCompleted) {
    client.on("payment.completed", options.onPaymentCompleted);
  }
  if (options.onBillCreated) {
    client.on("bill.created", options.onBillCreated);
  }
  if (options.onLedgerUpdated) {
    client.on("ledger.updated", options.onLedgerUpdated);
  }
  if (options.onReceiptIssued) {
    client.on("receipt.issued", options.onReceiptIssued);
  }
  if (options.onDisbursementCompleted) {
    client.on("disbursement.completed", options.onDisbursementCompleted);
  }
  if (options.onAny) {
    client.on("*", options.onAny);
  }

  // Start polling
  client.startPolling(
    options.events || [
      "payment.completed",
      "bill.created",
      "ledger.updated",
      "receipt.issued",
      "disbursement.completed",
    ],
  );

  return client;
};

// Auto-init for demo: show real-time indicator
console.log(
  "%c🚀 RCTS-QC Webhook Client Loaded",
  "color: #c9a84c; font-weight: bold;",
);
console.log(
  "%c   Use window.WebhookManager for advanced control",
  "color: #8892a4;",
);
