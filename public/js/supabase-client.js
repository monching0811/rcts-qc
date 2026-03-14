/**
 * RCTS-QC | supabase-client.js
 * Browser-side Supabase REST client.
 * Loaded by pages that need to call Supabase directly from the browser
 * (e.g., real-time subscriptions). Most data fetching goes through PHP.
 *
 * CONFIG: Set SUPABASE_URL and SUPABASE_ANON_KEY in config.js or in the
 * window object before loading this file.
 */

const RCTS_SUPABASE = (() => {
  /* ── Config ─────────────────────────────────────────────────── */
  const URL = window.SUPABASE_URL || "";
  const KEY = window.SUPABASE_ANON_KEY || "";
  const HEADERS = {
    "Content-Type": "application/json",
    apikey: KEY,
    Authorization: "Bearer " + KEY,
  };

  if (!URL || !KEY) {
    console.warn(
      "[RCTS-QC] supabase-client.js: SUPABASE_URL or SUPABASE_ANON_KEY not set.",
    );
  }

  /* ── REST helper ─────────────────────────────────────────────── */
  async function rest(method, path, body = null) {
    const opts = { method, headers: { ...HEADERS } };
    if (body) {
      opts.body = JSON.stringify(body);
    }
    try {
      const res = await fetch(URL + "/rest/v1/" + path, opts);
      const data = await res.json();
      return { ok: res.ok, status: res.status, data };
    } catch (err) {
      console.error("[RCTS-QC] Supabase fetch error:", err);
      return { ok: false, status: 0, data: null, error: err.message };
    }
  }

  /* ── CRUD helpers ────────────────────────────────────────────── */
  const select = (table, params = "") =>
    rest("GET", table + (params ? "?" + params : ""));
  const insert = (table, row) => rest("POST", table, row);
  const update = (table, filter, row) =>
    rest("PATCH", table + "?" + filter, row);
  const remove = (table, filter) => rest("DELETE", table + "?" + filter);

  /* ── Real-time subscription via WebSocket ────────────────────── */
  let ws = null;

  function subscribe(table, event, callback) {
    if (!URL) return;
    const wsUrl =
      URL.replace("https://", "wss://").replace("http://", "ws://") +
      "/realtime/v1/websocket?apikey=" +
      KEY +
      "&vsn=1.0.0";
    ws = new WebSocket(wsUrl);

    ws.onopen = () => {
      ws.send(
        JSON.stringify({
          topic: "realtime:public:" + table,
          event: "phx_join",
          payload: {},
          ref: "1",
        }),
      );
    };

    ws.onmessage = (msg) => {
      try {
        const payload = JSON.parse(msg.data);
        if (payload.event === event || event === "*") {
          callback(payload.payload);
        }
      } catch (e) {
        /* ignore parse errors */
      }
    };

    ws.onerror = (err) => console.warn("[RCTS-QC] Realtime error:", err);
    return () => {
      if (ws) ws.close();
    };
  }

  function unsubscribe() {
    if (ws) {
      ws.close();
      ws = null;
    }
  }

  /* ── Specific helpers for RCTS tables ───────────────────────── */
  const getLedgerFeed = (limit = 20, offset = 0) =>
    select(
      "rcts_treasury_ledger",
      `select=*&order=entry_timestamp.desc&limit=${limit}&offset=${offset}`,
    );

  const getPendingBills = (qcitizen_id) =>
    select(
      "rcts_assessment_billing_hub",
      `qcitizen_id=eq.${qcitizen_id}&status=eq.Pending&select=*`,
    );

  const getDashboardSnapshot = () =>
    select(
      "rcts_treasury_dashboard",
      "select=*&order=snapshot_timestamp.desc&limit=1",
    );

  const getTreasuryKPIs = async () => {
    const snap = await getDashboardSnapshot();
    return snap.data?.[0] ?? null;
  };

  /* ── Subscribe to live ledger updates ───────────────────────── */
  function onNewLedgerEntry(callback) {
    return subscribe("rcts_treasury_ledger", "INSERT", callback);
  }

  return {
    select,
    insert,
    update,
    remove,
    subscribe,
    unsubscribe,
    getLedgerFeed,
    getPendingBills,
    getDashboardSnapshot,
    getTreasuryKPIs,
    onNewLedgerEntry,
    isConfigured: () => !!(URL && KEY),
  };
})();
