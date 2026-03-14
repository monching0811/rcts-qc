/**
 * RCTS-QC | main.js
 * Global UI utilities, nav initialisation, and shared micro-interactions.
 * Loaded on every page after main.css.
 */

/* ── Live date in header ─────────────────────────────────────────── */
(function initHeaderDate() {
  const el = document.getElementById("header-date");
  if (!el) return;
  el.textContent = new Date().toLocaleDateString("en-PH", {
    weekday: "short",
    year: "numeric",
    month: "long",
    day: "numeric",
  });
})();

/* ── Top bar philippine time clock ───────────────────────────────── */
(function initClock() {
  const el = document.getElementById("rcts-clock");
  if (!el) return;
  const tick = () => {
    el.textContent = new Date().toLocaleTimeString("en-PH", {
      hour: "2-digit",
      minute: "2-digit",
      second: "2-digit",
    });
  };
  tick();
  setInterval(tick, 1000);
})();

/* ── Toast global shortcut ───────────────────────────────────────── */
window.rcts_toast = function (msg, type = "success", duration = 4000) {
  if (window.RCTS_API) {
    RCTS_API.toast(msg, type, duration);
    return;
  }
  let el = document.getElementById("rcts-toast");
  if (!el) {
    el = document.createElement("div");
    el.id = "rcts-toast";
    el.style.cssText =
      "position:fixed;bottom:28px;right:28px;padding:14px 20px;border-radius:10px;font-size:14px;font-weight:500;z-index:200;display:none;max-width:380px;box-shadow:0 4px 20px rgba(0,0,0,0.3)";
    document.body.appendChild(el);
  }
  const colours = {
    success:
      "background:linear-gradient(135deg, rgba(46,204,113,0.2), rgba(46,204,113,0.1));border:1px solid rgba(46,204,113,0.5);color:#2ecc71",
    error:
      "background:linear-gradient(135deg, rgba(231,76,60,0.2), rgba(231,76,60,0.1));border:1px solid rgba(231,76,60,0.5);color:#ff7675",
    warning:
      "background:linear-gradient(135deg, rgba(241,196,15,0.2), rgba(241,196,15,0.1));border:1px solid rgba(241,196,15,0.5);color:#fdcb6e",
    info: "background:linear-gradient(135deg, rgba(52,152,219,0.2), rgba(52,152,219,0.1));border:1px solid rgba(52,152,219,0.5);color:#74b9ff",
  };
  const icons = {
    success: "✅ ",
    error: "❌ ",
    warning: "⚠️ ",
    info: "ℹ️ ",
  };
  el.style.cssText += ";" + (colours[type] || colours.info);
  el.innerHTML = `<span style="margin-right:8px">${icons[type] || icons.info}</span>${msg}`;
  el.style.display = "flex";
  el.style.alignItems = "center";
  clearTimeout(el._t);
  el._t = setTimeout(() => (el.style.display = "none"), duration);
};

/* ── Enhanced Toast Wrapper (global) ──────────────────────────────── */
window.Toast = {
  success(msg, duration) {
    window.rcts_toast(msg, "success", duration);
  },
  error(msg, duration) {
    window.rcts_toast(msg, "error", duration);
  },
  warning(msg, duration) {
    window.rcts_toast(msg, "warning", duration);
  },
  info(msg, duration) {
    window.rcts_toast(msg, "info", duration);
  },
};

/* ── Alert helper ────────────────────────────────────────────────── */
window.rcts_alert = function (id, msg, type = "success") {
  const el = document.getElementById(id);
  if (!el) return;
  el.textContent = msg;
  el.className = "alert alert-" + type + " show";
  el.style.display = "flex";
};

window.rcts_alert_hide = function (id) {
  const el = document.getElementById(id);
  if (el) {
    el.style.display = "none";
    el.className = "alert";
  }
};

/* ── Loading state helper ────────────────────────────────────────── */
window.rcts_loading = function (containerId, msg = "Loading...") {
  const el = document.getElementById(containerId);
  if (el)
    el.innerHTML = `<div class="loading-state"><div class="spinner" style="margin-bottom:10px"></div><br>${msg}</div>`;
};

/* ── Number formatting ───────────────────────────────────────────── */
window.rcts_fmt = (n) =>
  "₱" +
  Number(n || 0).toLocaleString("en-PH", {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  });

/* ── Button loading state ────────────────────────────────────────── */
window.rcts_btn_loading = function (btnId, isLoading, originalText = null) {
  const btn = document.getElementById(btnId);
  if (!btn) return;
  if (isLoading) {
    btn._originalText = btn.textContent;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner"></span> Processing...';
  } else {
    btn.disabled = false;
    btn.textContent = originalText || btn._originalText || "Submit";
  }
};

/* ── Confirm dialog (async) ──────────────────────────────────────── */
window.rcts_confirm = function (message, title = "Confirm Action") {
  return new Promise((resolve) => {
    // Remove any existing confirm dialog
    const existing = document.getElementById("rcts-confirm-overlay");
    if (existing) existing.remove();

    // Inline modal — no external dependency
    const overlay = document.createElement("div");
    overlay.id = "rcts-confirm-overlay";
    overlay.style.cssText =
      "position:fixed;inset:0;background:rgba(10,22,40,.9);z-index:150;display:flex;align-items:center;justify-content:center;padding:20px;backdrop-filter:blur(4px)";
    overlay.innerHTML = `
      <div style="background:linear-gradient(135deg, #112240, #0a1628);border:1px solid rgba(201,168,76,0.3);border-radius:16px;padding:32px;max-width:440px;width:100%;text-align:center;box-shadow:0 8px 32px rgba(0,0,0,0.5)">
        <div style="font-size:32px;margin-bottom:16px">⚠️</div>
        <div style="font-family:'Playfair Display',serif;font-size:20px;margin-bottom:12px;color:#f5f3ee">${title}</div>
        <div style="font-size:14px;color:#8892a4;line-height:1.6;margin-bottom:28px">${message}</div>
        <div style="display:flex;gap:12px;justify-content:center">
          <button id="_rcts_cancel" style="padding:10px 24px;border:1px solid rgba(201,168,76,0.3);border-radius:8px;background:transparent;color:#8892a4;cursor:pointer;font-family:'DM Sans',sans-serif;font-size:13px;font-weight:500;transition:all 0.2s">Cancel</button>
          <button id="_rcts_confirm" style="padding:10px 24px;border:none;border-radius:8px;background:linear-gradient(135deg,#c9a84c,#e8c878);color:#0a1628;cursor:pointer;font-family:'DM Sans',sans-serif;font-size:13px;font-weight:700;transition:all 0.2s;box-shadow:0 4px 12px rgba(201,168,76,0.3)">OK</button>
        </div>
      </div>`;
    document.body.appendChild(overlay);
    document.getElementById("_rcts_confirm").onclick = () => {
      document.body.removeChild(overlay);
      resolve(true);
    };
    document.getElementById("_rcts_cancel").onclick = () => {
      document.body.removeChild(overlay);
      resolve(false);
    };
    // Close on escape
    document.addEventListener("keydown", function esc(e) {
      if (e.key === "Escape") {
        document.body.removeChild(overlay);
        resolve(false);
        document.removeEventListener("keydown", esc);
      }
    });
  });
};

/* ── Tooltip helper ─────────────────────────────────────────────────── */
window.rcts_tooltip = function (element, message, position = "top") {
  const el =
    typeof element === "string" ? document.querySelector(element) : element;
  if (!el) return;

  // Remove existing tooltip
  const existing = document.querySelector(".rcts-tooltip");
  if (existing) existing.remove();

  const tooltip = document.createElement("div");
  tooltip.className = "rcts-tooltip";
  tooltip.textContent = message;

  const positions = {
    top: "bottom:100%;left:50%;transform:translateX(-50%);margin-bottom:8px",
    bottom: "top:100%;left:50%;transform:translateX(-50%);margin-top:8px",
    left: "right:100%;top:50%;transform:translateY(-50%);margin-right:8px",
    right: "left:100%;top:50%;transform:translateY(-50%);margin-left:8px",
  };

  tooltip.style.cssText = `
    position:absolute;${positions[position] || positions.top};
    background:rgba(17,34,64,0.95);border:1px solid rgba(201,168,76,0.4);
    color:#f5f3ee;padding:8px 12px;border-radius:8px;font-size:12px;white-space:nowrap;
    z-index:300;pointer-events:none;box-shadow:0 4px 12px rgba(0,0,0,0.3);
  `;

  el.style.position = "relative";
  el.appendChild(tooltip);

  // Auto remove after 3 seconds
  setTimeout(() => tooltip.remove(), 3000);
};

// Make tooltip globally available
window.showTooltip = window.rcts_tooltip;

/* ── Global Logout with confirmation ────────────────────────────────── */
window.logoutWithConfirm = function () {
  rcts_confirm(
    "Are you sure you want to log out? You will need to sign in again to access your account.",
    "Sign Out",
  ).then((confirmed) => {
    if (confirmed) {
      sessionStorage.clear();
      window.location.href = "login.html";
    }
  });
};

/* ── Staggered fade-in for cards ─────────────────────────────────── */
(function initFadeIns() {
  document.querySelectorAll(".fade-up").forEach((el, i) => {
    if (!el.style.animationDelay) el.style.animationDelay = i * 0.07 + "s";
  });
})();

/* ── Keyboard shortcut: ? → log session info (dev) ──────────────── */
document.addEventListener("keydown", (e) => {
  if (e.key === "?" && e.ctrlKey) {
    console.group("[RCTS-QC] Session Debug");
    console.log(
      "Citizen:",
      JSON.parse(sessionStorage.getItem("rcts_citizen") || "{}"),
    );
    console.log("Token:", sessionStorage.getItem("rcts_token") || "none");
    console.groupEnd();
  }
});

/* ── Passive scroll handler: shrink nav on scroll ────────────────── */
(function initStickyNav() {
  const nav = document.querySelector("nav");
  if (!nav) return;
  window.addEventListener(
    "scroll",
    () => {
      nav.style.boxShadow =
        window.scrollY > 8 ? "0 4px 20px rgba(0,0,0,.4)" : "none";
    },
    { passive: true },
  );
})();
