/**
 * RCTS-QC | auth.js
 * Client-side authentication, session management, and role-based access control.
 */

const RCTS_AUTH = (() => {
  const SESSION_KEY = "rcts_citizen";
  const TOKEN_KEY = "rcts_token";
  const ALLOWED_ROLES = {
    citizen: ["citizen", "vendor", "business_owner"],
    treasurer: ["treasurer", "revenue_officer", "admin"],
    auditor: ["auditor"],
    any: [
      "citizen",
      "vendor",
      "business_owner",
      "treasurer",
      "revenue_officer",
      "admin",
      "auditor",
    ],
  };

  /* ── Session helpers ─────────────────────────────────────────── */
  function getSession() {
    try {
      return JSON.parse(sessionStorage.getItem(SESSION_KEY) || "{}");
    } catch {
      return {};
    }
  }
  function getToken() {
    return sessionStorage.getItem(TOKEN_KEY) || "";
  }
  function isLoggedIn() {
    const s = getSession();
    return !!s.qcitizen_id;
  }

  function saveSession(citizen) {
    sessionStorage.setItem(SESSION_KEY, JSON.stringify(citizen));
    if (citizen.login_token)
      sessionStorage.setItem(TOKEN_KEY, citizen.login_token);
  }

  function clearSession() {
    sessionStorage.removeItem(SESSION_KEY);
    sessionStorage.removeItem(TOKEN_KEY);
  }

  function getRole() {
    return getSession().role || "";
  }
  function hasRole(portal) {
    const role = getRole();
    const allowed = ALLOWED_ROLES[portal] || [];
    return allowed.includes(role);
  }

  /* ── Guard: redirect if not authorized ──────────────────────── */
  function guard(portal = "citizen", redirectTo = null) {
    if (!isLoggedIn()) {
      window.location.href = redirectTo || _loginUrl();
      return false;
    }
    if (!hasRole(portal)) {
      window.location.href = redirectTo || _loginUrl();
      return false;
    }
    return true;
  }

  function _loginUrl() {
    const path = window.location.pathname;

    // For treasurer and auditor portals, login is in same directory
    if (path.includes("/treasurer/")) {
      return "login.html";
    } else if (path.includes("/auditor/")) {
      return "login.html";
    } else {
      // For citizen portal, use original depth calculation
      const depth = (window.location.pathname.match(/\//g) || []).length - 1;
      return "../".repeat(Math.max(0, depth - 1)) + "pages/citizen/login.html";
    }
  }

  /* ── Login via mock S1 API ───────────────────────────────────── */
  async function login(email, password) {
    try {
      const depth = (window.location.pathname.match(/\//g) || []).length - 1;
      const base = "../".repeat(Math.max(0, depth - 1));
      const url =
        base +
        "mock-data/subsystem1/citizen-registry-api.php?action=verify_login" +
        "&email=" +
        encodeURIComponent(email) +
        "&password=" +
        encodeURIComponent(password);
      const res = await fetch(url);
      const data = await res.json();

      if (data.success) {
        saveSession(data.data);
        return { success: true, citizen: data.data };
      }
      return { success: false, message: data.message || "Login failed." };
    } catch (err) {
      return {
        success: false,
        message:
          "Could not connect to authentication service. Is XAMPP running?",
      };
    }
  }

  function logout(redirect = null) {
    clearSession();
    window.location.href = redirect || _loginUrl();
  }

  /* ── Populate nav UI with citizen info ───────────────────────── */
  function populateNav() {
    const c = getSession();
    const nameEl =
      document.getElementById("nav-citizen-name") ||
      document.getElementById("nav-treasurer-name") ||
      document.getElementById("nav-name");
    const roleEl = document.getElementById("nav-citizen-role");
    if (nameEl) nameEl.textContent = c.full_name || "—";
    if (roleEl) roleEl.textContent = c.role || "Citizen";
  }

  /* ── Show discount badge if applicable ──────────────────────── */
  function showDiscountBadge(containerId) {
    const c = getSession();
    const el = document.getElementById(containerId);
    if (!el || !c.discount_info) return;
    const d = c.discount_info;
    el.textContent =
      d.discount_type + " — " + d.discount_rate * 100 + "% Discount Eligible";
    el.style.display = "inline-block";
  }

  return {
    getSession,
    getToken,
    isLoggedIn,
    getRole,
    hasRole,
    guard,
    login,
    logout,
    saveSession,
    clearSession,
    populateNav,
    showDiscountBadge,
  };
})();
