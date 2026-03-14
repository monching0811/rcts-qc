/**
 * RCTS-QC | api-handler.js
 * Unified client-side API call wrapper for all RCTS PHP endpoints.
 * Handles errors, loading states, and toast notifications consistently.
 */

const RCTS_API = (() => {
  /* ── API Base URL (change for deployment) ───────────────────── */
  const API_BASE = "http://localhost/rcts-qc/"; // For production: 'https://your-railway-app.railway.app/rcts-qc/'

  const ENDPOINTS = {
    rpt: API_BASE + "api/endpoints/rpt.php",
    businessTax: API_BASE + "api/endpoints/business-tax.php",
    marketStall: API_BASE + "api/endpoints/market-stall.php",
    payment: API_BASE + "api/endpoints/payment.php",
    disbursement: API_BASE + "api/endpoints/disbursement.php",
    dashboard: API_BASE + "api/endpoints/dashboard.php",
    inbound: API_BASE + "api/endpoints/inbound.php",
    s1: API_BASE + "mock-data/subsystem1/citizen-registry-api.php",
    s7: API_BASE + "mock-data/subsystem7/zoning-api.php",
  };

  /* ── Core fetch wrapper ──────────────────────────────────────── */
  async function call(
    endpoint,
    action,
    method = "GET",
    body = null,
    options = {},
  ) {
    const path = ENDPOINTS[endpoint];
    if (!path) throw new Error("Unknown endpoint: " + endpoint);

    const url = path + "?action=" + action;
    const headers = { "Content-Type": "application/json" };
    if (options.apiKey) headers["X-API-Key"] = options.apiKey;

    const fetchOpts = { method, headers };
    if (body && method !== "GET") fetchOpts.body = JSON.stringify(body);

    try {
      const res = await fetch(url, fetchOpts);
      const data = await res.json();
      return data; // always returns {success, data, message}
    } catch (err) {
      return {
        success: false,
        message: "Network error: " + err.message,
        data: null,
      };
    }
  }

  /* ── Shorthand GET / POST ────────────────────────────────────── */
  const get = (ep, action, params = {}) => {
    const base = _base();
    const path = ENDPOINTS[ep];
    const qs = new URLSearchParams({ action, ...params }).toString();
    return fetch(base + path + "?" + qs)
      .then((r) => r.json())
      .catch((err) => ({ success: false, message: err.message }));
  };

  const post = (ep, action, body = {}, options = {}) =>
    call(ep, action, "POST", body, options);

  /* ── RPT ─────────────────────────────────────────────────────── */
  const rpt = {
    getBills: (qcitizen_id) => get("rpt", "get_bills", { qcitizen_id }),
    compute: (tdn) => get("rpt", "compute", { tdn_number: tdn }),
    generateBill: (qcitizen_id) =>
      post("rpt", "generate_bill", { qcitizen_id }),
    markPaid: (bill_ref) =>
      post("rpt", "mark_paid", { bill_reference_no: bill_ref }),
  };

  /* ── Business Tax ────────────────────────────────────────────── */
  const businessTax = {
    getBills: (qcitizen_id) => get("businessTax", "get_bills", { qcitizen_id }),
    getClearanceStatus: (bin) =>
      get("businessTax", "clearance_status", { bin_number: bin }),
    receiveClearance: (payload) =>
      post("businessTax", "receive_clearance_signal", payload, {
        apiKey: "DEV-BYPASS-KEY-LOCAL",
      }),
    markPaid: (bill_ref) =>
      post("businessTax", "mark_paid", { bill_reference_no: bill_ref }),
  };

  /* ── Market Stall ────────────────────────────────────────────── */
  const marketStall = {
    getActiveStalls: () => get("marketStall", "get_active_stalls"),
    getVendorBill: (qcitizen_id) =>
      get("marketStall", "get_vendor_bill", { qcitizen_id }),
    generateInvoice: (stall_id) =>
      post("marketStall", "generate_invoice", { stall_asset_id: stall_id }),
    receiveOccupancy: (payload) =>
      post("marketStall", "receive_occupancy_signal", payload, {
        apiKey: "DEV-BYPASS-KEY-LOCAL",
      }),
    markPaid: (bill_ref) =>
      post("marketStall", "mark_paid", { bill_reference_no: bill_ref }),
  };

  /* ── Payment ─────────────────────────────────────────────────── */
  const payment = {
    getPendingBills: (qcitizen_id) =>
      get("payment", "get_pending_bills", { qcitizen_id }),
    getReceipt: (transaction_id) =>
      get("payment", "get_receipt", { transaction_id }),
    checkout: (qcitizen_id, bill_refs, gateway) =>
      post("payment", "checkout", {
        qcitizen_id,
        bill_reference_nos: bill_refs,
        gateway_provider: gateway,
      }),
    execute: (transaction_id) => post("payment", "execute", { transaction_id }),
  };

  /* ── Disbursement ────────────────────────────────────────────── */
  const disbursement = {
    getPending: () => get("disbursement", "get_pending"),
    getByDept: (dept_id) => get("disbursement", "get_by_dept", { dept_id }),
    executeBatch: (program_id) =>
      post(
        "disbursement",
        "execute_batch",
        { program_id },
        { apiKey: "DEV-BYPASS-KEY-LOCAL" },
      ),
    requestQRF: (payload) =>
      post("disbursement", "request_qrf_unlock", payload, {
        apiKey: "DEV-BYPASS-KEY-LOCAL",
      }),
  };

  /* ── Dashboard ───────────────────────────────────────────────── */
  const dashboard = {
    liveSummary: () => get("dashboard", "live_summary"),
    ledgerFeed: (limit = 20, offset = 0) =>
      get("dashboard", "ledger_feed", { limit, offset }),
    liquidityCheck: (proposed) =>
      get("dashboard", "liquidity_check", { proposed_payout: proposed }),
    delinquencyReport: () => get("dashboard", "delinquency_report"),
    esreData: (year, month = "") =>
      get("dashboard", "esre_data", { year, ...(month && { month }) }),
    refreshSnapshot: () => post("dashboard", "refresh_snapshot", {}),
  };

  /* ── S1 / S7 Mock APIs ───────────────────────────────────────── */
  const s1 = {
    getCitizen: (qcitizen_id) => get("s1", "get_citizen", { qcitizen_id }),
    verifyLogin: (email, pw) =>
      get("s1", "verify_login", { email, password: pw }),
  };
  const s7 = {
    getProperties: (qcitizen_id) =>
      get("s7", "get_properties_by_citizen", { qcitizen_id }),
    getValuation: (tdn) =>
      get("s7", "get_property_valuation", { tdn_number: tdn }),
  };

  /* ── UI helpers ──────────────────────────────────────────────── */
  function showLoading(containerId, msg = "Loading...") {
    const el = document.getElementById(containerId);
    if (el) el.innerHTML = `<div class="loading-state">${msg}</div>`;
  }

  function showEmpty(containerId, msg = "No data found.") {
    const el = document.getElementById(containerId);
    if (el)
      el.innerHTML = `<div class="empty-state"><div class="empty-icon">📭</div>${msg}</div>`;
  }

  function showError(containerId, msg) {
    const el = document.getElementById(containerId);
    if (el)
      el.innerHTML = `<div class="empty-state" style="color:var(--red)">${msg}</div>`;
  }

  /* ── Toast ───────────────────────────────────────────────────── */
  function toast(msg, type = "success", duration = 4000) {
    let el = document.getElementById("rcts-toast");
    if (!el) {
      el = document.createElement("div");
      el.id = "rcts-toast";
      document.body.appendChild(el);
    }
    el.textContent = msg;
    el.className = "toast toast-" + type + " show";
    clearTimeout(el._timer);
    el._timer = setTimeout(() => el.classList.remove("show"), duration);
  }

  /* ── Format currency ─────────────────────────────────────────── */
  const fmt = (n) =>
    "₱" + Number(n || 0).toLocaleString("en-PH", { minimumFractionDigits: 2 });

  return {
    call,
    get,
    post,
    rpt,
    businessTax,
    marketStall,
    payment,
    disbursement,
    dashboard,
    s1,
    s7,
    showLoading,
    showEmpty,
    showError,
    toast,
    fmt,
  };
})();
