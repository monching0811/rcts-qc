/**
 * RCTS-QC Treasurer Sidebar
 * js/sidebar-treasurer.js
 *
 * Drop this script into any treasurer page BEFORE closing </body>.
 * It removes the old <nav> and injects a collapsible sidebar + body offset.
 *
 * Usage:
 *   <script src="../../js/sidebar-treasurer.js"></script>
 *   <script>TreasurerSidebar.init({ active: 'dashboard' });</script>
 */

window.TreasurerSidebar = (function () {

  const LINKS = [
    { key: 'dashboard',    href: 'dashboard.html',               label: 'Dashboard',             icon: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6' },
    { key: 'ledger',       href: 'live-ledger.html',             label: 'Live Ledger',           icon: 'M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z' },
    { key: 'disbursement', href: 'disbursement.html',            label: 'Disbursements',         icon: 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z' },
    { key: 'reports',      href: 'reports.html',                 label: 'Reports / e-SRE',       icon: 'M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z' },
    { key: 'funds',        href: 'fund-management.html',         label: 'Fund Management',       icon: 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z' },
    { key: 'billing',      href: 'market-billing.html',          label: 'Market Billing',        icon: 'M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z' },
    { key: 'rpt',          href: 'rpt-management.html',          label: 'RPT Management',        icon: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6 M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4' },
    { key: 'business',     href: 'business-tax-management.html', label: 'Business Tax',          icon: 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-2 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4' },
    { key: 'admin',        href: 'admin.html',                   label: 'Admin Panel',           icon: 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z', adminOnly: true },
  ];

  const CSS = `
    #rcts-sidebar *,#rcts-sidebar *::before,#rcts-sidebar *::after{box-sizing:border-box;margin:0;padding:0}
    #rcts-sidebar{
      position:fixed;top:0;left:0;height:100vh;z-index:100;
      width:240px;
      background:#112240;
      border-right:1px solid rgba(201,168,76,.15);
      display:flex;flex-direction:column;
      transition:width .25s cubic-bezier(.4,0,.2,1);
      overflow:hidden;
    }
    #rcts-sidebar.sb-collapsed{width:64px}
    .sb-brand{
      height:60px;min-height:60px;
      display:flex;align-items:center;gap:12px;
      padding:0 16px;
      border-bottom:1px solid rgba(201,168,76,.12);
      flex-shrink:0;overflow:hidden;text-decoration:none;color:inherit;
    }
    .sb-logo{
      width:32px;height:32px;border-radius:50%;flex-shrink:0;
      background:linear-gradient(135deg,#c9a84c,#e8c878);
      display:flex;align-items:center;justify-content:center;
      font-size:12px;font-weight:700;color:#0a1628;
      font-family:'Playfair Display',serif;
    }
    .sb-brand-text{font-size:12px;font-weight:600;white-space:nowrap;color:#f5f3ee;opacity:1;transition:opacity .15s}
    #rcts-sidebar.sb-collapsed .sb-brand-text{opacity:0;pointer-events:none}
    .sb-toggle{
      margin:12px auto;width:36px;height:36px;border-radius:8px;flex-shrink:0;
      background:rgba(201,168,76,.08);border:1px solid rgba(201,168,76,.18);
      color:#c9a84c;cursor:pointer;
      display:flex;align-items:center;justify-content:center;
      transition:background .2s,transform .25s cubic-bezier(.4,0,.2,1);
    }
    .sb-toggle:hover{background:rgba(201,168,76,.15)}
    #rcts-sidebar.sb-collapsed .sb-toggle{transform:rotate(180deg)}
    .sb-nav{flex:1;padding:8px 10px;display:flex;flex-direction:column;gap:2px;overflow-y:auto;overflow-x:hidden}
    .sb-link{
      display:flex;align-items:center;gap:12px;
      padding:10px;border-radius:8px;
      text-decoration:none;white-space:nowrap;
      color:#8892a4;font-size:13px;font-weight:500;
      font-family:'DM Sans',sans-serif;
      transition:background .18s,color .18s;
      position:relative;
    }
    .sb-link:hover{background:rgba(201,168,76,.07);color:#f5f3ee}
    .sb-link.sb-active{background:rgba(201,168,76,.12);color:#c9a84c}
    .sb-link.sb-admin-link{border-top:1px solid rgba(201,168,76,.1);margin-top:6px;padding-top:14px}
    .sb-link svg{flex-shrink:0;width:18px;height:18px}
    .sb-link-label{opacity:1;transition:opacity .15s}
    #rcts-sidebar.sb-collapsed .sb-link-label{opacity:0;pointer-events:none}
    #rcts-sidebar.sb-collapsed .sb-link::after{
      content:attr(data-label);
      position:absolute;left:56px;top:50%;transform:translateY(-50%);
      background:#112240;color:#f5f3ee;
      border:1px solid rgba(201,168,76,.2);border-radius:6px;
      padding:5px 10px;font-size:12px;white-space:nowrap;
      pointer-events:none;opacity:0;transition:opacity .15s;z-index:200;
      font-family:'DM Sans',sans-serif;
    }
    #rcts-sidebar.sb-collapsed .sb-link:hover::after{opacity:1}
    .sb-footer{padding:12px 10px;border-top:1px solid rgba(201,168,76,.12);display:flex;flex-direction:column;gap:8px;flex-shrink:0}
    .sb-user{display:flex;align-items:center;gap:10px;padding:8px 10px;border-radius:8px;background:rgba(255,255,255,.03);overflow:hidden}
    .sb-avatar{width:30px;height:30px;border-radius:50%;flex-shrink:0;background:rgba(201,168,76,.2);display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;color:#c9a84c}
    .sb-user-info{overflow:hidden;opacity:1;transition:opacity .15s}
    #rcts-sidebar.sb-collapsed .sb-user-info{opacity:0;pointer-events:none}
    .sb-user-name{font-size:12px;font-weight:600;color:#f5f3ee;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
    .sb-user-role{font-size:10px;color:#c9a84c;text-transform:uppercase;letter-spacing:.05em}
    .sb-signout{
      display:flex;align-items:center;gap:10px;
      padding:8px 10px;border-radius:8px;
      background:none;border:1px solid rgba(201,168,76,.18);
      color:#8892a4;font-size:12px;font-family:'DM Sans',sans-serif;
      cursor:pointer;width:100%;text-align:left;
      transition:border-color .2s,color .2s;
    }
    .sb-signout:hover{border-color:rgba(231,76,60,.4);color:#ff8a80}
    .sb-signout svg{flex-shrink:0;width:16px;height:16px}
    .sb-signout-label{opacity:1;transition:opacity .15s}
    #rcts-sidebar.sb-collapsed .sb-signout-label{opacity:0;pointer-events:none}
  `;

  function svgIcon(path) {
    return `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="${path}"/></svg>`;
  }

  function init(opts) {
    opts = opts || {};
    const activeKey  = opts.active || '';
    const baseUrl    = opts.baseUrl || '../../';
    const loginUrl   = opts.loginUrl || (baseUrl + 'pages/treasurer/login.html');

    // ── Auth guard ──────────────────────────────────────────────────────────
    const citizen = JSON.parse(sessionStorage.getItem('rcts_citizen') || '{}');
    if (!['treasurer', 'revenue_officer', 'admin'].includes(citizen.role)) {
      location.href = loginUrl;
      return;
    }

    // ── Inject CSS ──────────────────────────────────────────────────────────
    const style = document.createElement('style');
    style.textContent = CSS;
    document.head.appendChild(style);

    // ── Body offset ─────────────────────────────────────────────────────────
    const bodyStyle = document.createElement('style');
    bodyStyle.id = 'rcts-body-offset';
    bodyStyle.textContent = `body{margin-left:240px!important;transition:margin-left .25s cubic-bezier(.4,0,.2,1)}body.sb-collapsed{margin-left:64px!important}`;
    document.head.appendChild(bodyStyle);

    // ── Remove old <nav> if present ──────────────────────────────────────────
    const oldNav = document.querySelector('nav');
    if (oldNav) oldNav.remove();

    // ── Build sidebar HTML ───────────────────────────────────────────────────
    const navLinks = LINKS.filter(l => !l.adminOnly || citizen.role === 'admin').map(l => {
      const isActive  = l.key === activeKey;
      const isAdmin   = l.adminOnly;
      return `<a href="${l.href}" class="sb-link${isActive ? ' sb-active' : ''}${isAdmin ? ' sb-admin-link' : ''}" data-label="${l.label}">${svgIcon(l.icon)}<span class="sb-link-label">${l.label}</span></a>`;
    }).join('');

    const initials = citizen.full_name ? citizen.full_name.charAt(0).toUpperCase() : 'T';
    const roleLbl  = citizen.role === 'admin' ? 'Admin' : 'Treasury Staff';

    const sidebar = document.createElement('aside');
    sidebar.id = 'rcts-sidebar';
    sidebar.innerHTML = `
      <a class="sb-brand" href="${baseUrl}index.html">
        <div class="sb-logo">QC</div>
        <span class="sb-brand-text">RCTS-QC Treasury</span>
      </a>
      <button class="sb-toggle" title="Toggle sidebar" onclick="TreasurerSidebar.toggle()">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>
      </button>
      <nav class="sb-nav">${navLinks}</nav>
      <div class="sb-footer">
        <div class="sb-user">
          <div class="sb-avatar">${initials}</div>
          <div class="sb-user-info">
            <div class="sb-user-name">${citizen.full_name || 'Treasury Staff'}</div>
            <div class="sb-user-role">${roleLbl}</div>
          </div>
        </div>
        <button class="sb-signout" onclick="TreasurerSidebar.logout('${loginUrl}')">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
          <span class="sb-signout-label">Sign Out</span>
        </button>
      </div>`;

    document.body.insertBefore(sidebar, document.body.firstChild);

    // ── Restore collapsed state ──────────────────────────────────────────────
    if (localStorage.getItem('rcts_sb_collapsed') === '1') {
      sidebar.classList.add('sb-collapsed');
      document.body.classList.add('sb-collapsed');
    }

    // ── Update any leftover nav-name spans ───────────────────────────────────
    const nameEl = document.getElementById('nav-name');
    if (nameEl) nameEl.textContent = citizen.full_name || 'Treasury Staff';
  }

  function toggle() {
    const sb = document.getElementById('rcts-sidebar');
    sb.classList.toggle('sb-collapsed');
    document.body.classList.toggle('sb-collapsed');
    localStorage.setItem('rcts_sb_collapsed', sb.classList.contains('sb-collapsed') ? '1' : '0');
  }

  async function logout(loginUrl) {
    const citizen = JSON.parse(sessionStorage.getItem('rcts_citizen') || '{}');
    try {
      await fetch('../../api/endpoints/logout.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ user_id: citizen.user_id || citizen.email, email: citizen.email, role: citizen.role }),
      });
    } catch (e) {}
    sessionStorage.clear();
    location.href = loginUrl || '../../pages/treasurer/login.html';
  }

  return { init, toggle, logout };
})();