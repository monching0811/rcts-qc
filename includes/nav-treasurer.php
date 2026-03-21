<?php
/**
 * INCLUDE: Treasurer Portal Sidebar Navigation
 * includes/nav-treasurer.php
 *
 * USAGE:
 *   $active_page = 'ledger'; // 'dashboard'|'ledger'|'disbursement'|'reports'|'funds'|'billing'|'admin'
 *   require_once __DIR__ . '/../../includes/nav-treasurer.php';
 */
$active_page = $active_page ?? '';
$base_url    = $base_url ?? '../../';

$c        = json_decode($_COOKIE['rcts_citizen'] ?? '{}', true);
$is_admin = isset($c['role']) && $c['role'] === 'admin';

$links = [
    'dashboard'    => ['href' => 'dashboard.html',        'label' => 'Dashboard',       'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',        'admin_only' => false],
    'ledger'       => ['href' => 'live-ledger.html',      'label' => 'Live Ledger',     'icon' => 'M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',                          'admin_only' => false],
    'disbursement' => ['href' => 'disbursement.html',     'label' => 'Disbursements',   'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z', 'admin_only' => false],
    'reports'      => ['href' => 'reports.html',          'label' => 'Reports / e-SRE', 'icon' => 'M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',                          'admin_only' => false],
    'funds'        => ['href' => 'fund-management.html',  'label' => 'Fund Management', 'icon' => 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z',                                                                   'admin_only' => false],
    'billing'      => ['href' => 'market-billing.html',   'label' => 'Market Billing',  'icon' => 'M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z',                                                                                                                 'admin_only' => false],
    'admin'        => ['href' => 'admin.html',            'label' => 'Admin Panel',     'icon' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z', 'admin_only' => true],
];
?>
<style>
  /* ── Sidebar shell ── */
  #rcts-sidebar {
    position: fixed; top: 0; left: 0; height: 100vh; z-index: 100;
    width: 240px;
    background: var(--navy2, #112240);
    border-right: 1px solid rgba(201,168,76,.15);
    display: flex; flex-direction: column;
    transition: width .25s cubic-bezier(.4,0,.2,1);
    overflow: hidden;
  }
  #rcts-sidebar.collapsed { width: 64px; }

  /* ── Top brand bar ── */
  .sb-brand {
    height: 60px; min-height: 60px;
    display: flex; align-items: center; gap: 12px;
    padding: 0 16px;
    border-bottom: 1px solid rgba(201,168,76,.12);
    flex-shrink: 0; overflow: hidden;
  }
  .sb-logo {
    width: 32px; height: 32px; border-radius: 50%; flex-shrink: 0;
    background: linear-gradient(135deg, var(--gold,#c9a84c), var(--gold2,#e8c878));
    display: flex; align-items: center; justify-content: center;
    font-size: 12px; font-weight: 700;
    color: var(--navy,#0a1628);
    font-family: 'Playfair Display', serif;
  }
  .sb-brand-text {
    font-size: 12px; font-weight: 600; white-space: nowrap;
    color: var(--white, #f5f3ee);
    opacity: 1; transition: opacity .15s;
  }
  #rcts-sidebar.collapsed .sb-brand-text { opacity: 0; pointer-events: none; }

  /* ── Toggle button ── */
  .sb-toggle {
    margin: 12px auto; width: 36px; height: 36px; border-radius: 8px; flex-shrink: 0;
    background: rgba(201,168,76,.08);
    border: 1px solid rgba(201,168,76,.18);
    color: var(--gold,#c9a84c); cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    transition: background .2s, transform .25s cubic-bezier(.4,0,.2,1);
  }
  .sb-toggle:hover { background: rgba(201,168,76,.15); }
  #rcts-sidebar.collapsed .sb-toggle { transform: rotate(180deg); }

  /* ── Nav links ── */
  .sb-nav { flex: 1; padding: 8px 10px; display: flex; flex-direction: column; gap: 2px; overflow-y: auto; overflow-x: hidden; }
  .sb-link {
    display: flex; align-items: center; gap: 12px;
    padding: 10px 10px; border-radius: 8px;
    text-decoration: none; white-space: nowrap;
    color: var(--muted, #8892a4); font-size: 13px; font-weight: 500;
    transition: background .18s, color .18s;
    position: relative;
  }
  .sb-link:hover { background: rgba(201,168,76,.07); color: var(--white,#f5f3ee); }
  .sb-link.active { background: rgba(201,168,76,.12); color: var(--gold,#c9a84c); }
  .sb-link.admin-link { border-top: 1px solid rgba(201,168,76,.1); margin-top: 6px; padding-top: 14px; }
  .sb-link svg { flex-shrink: 0; width: 18px; height: 18px; }
  .sb-link-label { opacity: 1; transition: opacity .15s; }
  #rcts-sidebar.collapsed .sb-link-label { opacity: 0; pointer-events: none; }

  /* Tooltip when collapsed */
  #rcts-sidebar.collapsed .sb-link::after {
    content: attr(data-label);
    position: absolute; left: 56px; top: 50%; transform: translateY(-50%);
    background: var(--navy2,#112240); color: var(--white,#f5f3ee);
    border: 1px solid rgba(201,168,76,.2); border-radius: 6px;
    padding: 5px 10px; font-size: 12px; white-space: nowrap;
    pointer-events: none; opacity: 0; transition: opacity .15s;
    z-index: 200;
  }
  #rcts-sidebar.collapsed .sb-link:hover::after { opacity: 1; }

  /* ── Footer (user + signout) ── */
  .sb-footer {
    padding: 12px 10px; border-top: 1px solid rgba(201,168,76,.12);
    display: flex; flex-direction: column; gap: 8px; flex-shrink: 0;
  }
  .sb-user {
    display: flex; align-items: center; gap: 10px;
    padding: 8px 10px; border-radius: 8px;
    background: rgba(255,255,255,.03); overflow: hidden;
  }
  .sb-avatar {
    width: 30px; height: 30px; border-radius: 50%; flex-shrink: 0;
    background: rgba(201,168,76,.2);
    display: flex; align-items: center; justify-content: center;
    font-size: 11px; font-weight: 700; color: var(--gold,#c9a84c);
  }
  .sb-user-info { overflow: hidden; }
  .sb-user-name { font-size: 12px; font-weight: 600; color: var(--white,#f5f3ee); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
  .sb-user-role { font-size: 10px; color: var(--gold,#c9a84c); text-transform: uppercase; letter-spacing: .05em; }
  .sb-user-info, .sb-signout-label {
    opacity: 1; transition: opacity .15s;
  }
  #rcts-sidebar.collapsed .sb-user-info,
  #rcts-sidebar.collapsed .sb-signout-label { opacity: 0; pointer-events: none; }

  .sb-signout {
    display: flex; align-items: center; gap: 10px;
    padding: 8px 10px; border-radius: 8px;
    background: none; border: 1px solid rgba(201,168,76,.18);
    color: var(--muted,#8892a4); font-size: 12px; font-family: 'DM Sans',sans-serif;
    cursor: pointer; width: 100%; text-align: left;
    transition: border-color .2s, color .2s;
  }
  .sb-signout:hover { border-color: rgba(231,76,60,.4); color: #ff8a80; }
  .sb-signout svg { flex-shrink: 0; width: 16px; height: 16px; }

  /* ── Page body offset ── */
  body { margin-left: 240px; transition: margin-left .25s cubic-bezier(.4,0,.2,1); }
  body.sb-collapsed { margin-left: 64px; }
</style>

<aside id="rcts-sidebar">
  <!-- Brand -->
  <div class="sb-brand">
    <a href="<?= $base_url ?>index.html" style="display:flex;align-items:center;gap:12px;text-decoration:none;color:inherit;">
      <div class="sb-logo">QC</div>
      <span class="sb-brand-text">RCTS-QC Treasury</span>
    </a>
  </div>

  <!-- Toggle -->
  <button class="sb-toggle" id="sb-toggle-btn" title="Toggle sidebar" onclick="rctsSidebarToggle()">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
      <polyline points="15 18 9 12 15 6"></polyline>
    </svg>
  </button>

  <!-- Nav links -->
  <nav class="sb-nav">
    <?php foreach ($links as $key => $link):
      if ($link['admin_only'] && !$is_admin) continue;
      $is_active = ($active_page === $key);
    ?>
    <a href="<?= $link['href'] ?>"
       class="sb-link <?= $is_active ? 'active' : '' ?> <?= $link['admin_only'] ? 'admin-link' : '' ?>"
       data-label="<?= htmlspecialchars($link['label']) ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
        <path d="<?= $link['icon'] ?>"/>
      </svg>
      <span class="sb-link-label"><?= htmlspecialchars($link['label']) ?></span>
    </a>
    <?php endforeach; ?>
  </nav>

  <!-- Footer -->
  <div class="sb-footer">
    <div class="sb-user">
      <div class="sb-avatar" id="sb-avatar">T</div>
      <div class="sb-user-info">
        <div class="sb-user-name" id="sb-user-name">Treasury Staff</div>
        <div class="sb-user-role" id="sb-user-role">Treasury Staff</div>
      </div>
    </div>
    <button class="sb-signout" onclick="sessionStorage.clear();location.href='<?= $base_url ?>pages/citizen/login.html'">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
        <path d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
      </svg>
      <span class="sb-signout-label">Sign Out</span>
    </button>
  </div>
</aside>

<script>
(function(){
  // Auth guard
  const c = JSON.parse(sessionStorage.getItem('rcts_citizen') || '{}');
  if (!['treasurer','revenue_officer','admin'].includes(c.role)) {
    location.href = '<?= $base_url ?>pages/citizen/login.html';
    return;
  }

  // Restore collapsed state
  const collapsed = localStorage.getItem('rcts_sb_collapsed') === '1';
  if (collapsed) {
    document.getElementById('rcts-sidebar').classList.add('collapsed');
    document.body.classList.add('sb-collapsed');
  }

  // Populate user info
  const nameEl   = document.getElementById('sb-user-name');
  const roleEl   = document.getElementById('sb-user-role');
  const avatarEl = document.getElementById('sb-avatar');
  if (nameEl)   nameEl.textContent   = c.full_name || 'Treasury Staff';
  if (roleEl)   roleEl.textContent   = c.role === 'admin' ? 'Admin' : 'Treasury Staff';
  if (avatarEl && c.full_name) avatarEl.textContent = c.full_name.charAt(0).toUpperCase();
})();

function rctsSidebarToggle() {
  const sb   = document.getElementById('rcts-sidebar');
  const body = document.body;
  sb.classList.toggle('collapsed');
  body.classList.toggle('sb-collapsed');
  localStorage.setItem('rcts_sb_collapsed', sb.classList.contains('collapsed') ? '1' : '0');
}
</script>