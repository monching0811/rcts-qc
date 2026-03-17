<?php
/**
 * INCLUDE: Citizen Portal Navigation
 * includes/nav-citizen.php
 *
 * USAGE:
 *   $active_page = 'rpt'; // 'dashboard'|'rpt'|'business'|'market'|'payment'
 *   require_once __DIR__ . '/../../includes/nav-citizen.php';
 */
$active_page = $active_page ?? '';
$base_url    = $base_url ?? '../../';
?>
<nav style="position:relative;z-index:20;background:var(--navy2);border-bottom:1px solid rgba(201,168,76,.15);padding:0 32px;display:flex;align-items:center;height:60px">
    <a href="<?= $base_url ?>index.html" style="display:flex;align-items:center;gap:12px;text-decoration:none;color:inherit;margin-right:32px">
        <div style="width:34px;height:34px;border-radius:50%;background:linear-gradient(135deg,var(--gold),var(--gold2));display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;color:var(--navy);font-family:'Playfair Display',serif;flex-shrink:0">QC</div>
        <span style="font-size:13px;font-weight:600">RCTS-QC Citizen Portal</span>
    </a>
    <div style="display:flex;align-items:center;gap:2px;flex:1">
        <?php
        $links = [
            'dashboard' => ['href' => 'dashboard.html',        'label' => 'Dashboard'],
            'rpt'       => ['href' => 'rpt-payment.html',      'label' => 'Real Property Tax'],
            'business'  => ['href' => 'business-tax.html',     'label' => 'Business Tax'],
            'market'    => ['href' => 'market-stall.html',     'label' => 'Market Stall'],
            'traffic'   => ['href' => 'traffic-fines.html',    'label' => 'Traffic Fines'],
            'payment'   => ['href' => 'payment-gateway.html',  'label' => 'Pay Now'],
        ];
        foreach ($links as $key => $link):
            $is_active = ($active_page === $key);
        ?>
        <a href="<?= $link['href'] ?>" style="padding:8px 14px;font-size:12px;font-weight:500;color:<?= $is_active ? 'var(--gold)' : 'var(--muted)' ?>;text-decoration:none;border-radius:6px;background:<?= $is_active ? 'rgba(201,168,76,.08)' : 'transparent' ?>;transition:color .2s,background .2s">
            <?= htmlspecialchars($link['label']) ?>
        </a>
        <?php endforeach; ?>
    </div>
    <div style="display:flex;align-items:center;gap:10px;font-size:12px">
        <span id="nav-citizen-name" style="color:var(--white);font-weight:500">—</span>
        <span id="nav-citizen-role" style="font-size:10px;padding:2px 8px;border-radius:10px;background:rgba(52,152,219,.2);color:#74b9ff">Citizen</span>
        <button onclick="sessionStorage.clear();location.href='<?= $base_url ?>pages/citizen/login.html'"
                style="background:none;border:1px solid rgba(201,168,76,.25);border-radius:6px;padding:5px 12px;font-size:11px;color:var(--muted);cursor:pointer;font-family:'DM Sans',sans-serif">
            Sign Out
        </button>
    </div>
</nav>
<script>
(function(){
    const c = JSON.parse(sessionStorage.getItem('rcts_citizen') || '{}');
    if (!c.qcitizen_id) { location.href='<?= $base_url ?>pages/citizen/login.html'; return; }
    const nameEl = document.getElementById('nav-citizen-name');
    const roleEl = document.getElementById('nav-citizen-role');
    if (nameEl) nameEl.textContent = c.full_name || '—';
    if (roleEl) roleEl.textContent = c.role || 'Citizen';
})();
</script>

<!-- Side Navigation for Citizen Portal -->
<aside class="side-nav">
  <a class="side-nav-brand" href="dashboard.html">
    <div class="side-nav-seal">QC</div>
    <span>RCTS-QC</span>
  </a>
  <nav class="side-nav-links">
    <a href="dashboard.html" id="nav-dashboard">Dashboard</a>
    <a href="business-tax.html" id="nav-business-tax">Business Tax</a>
    <a href="market-stall.html" id="nav-market-stall">Market Stall</a>
    <a href="transactions.html" id="nav-transactions">Transactions</a>
    <a href="payment-gateway.html" id="nav-payment-gateway">Payment Gateway</a>
    <a href="traffic-fines.html" id="nav-traffic-fines">Traffic Fines</a>
  </nav>
  <div class="side-nav-user">
    <span class="side-nav-name" id="nav-name">Loading...</span>
    <span class="side-nav-tag" id="nav-role">Citizen</span>
    <button class="side-nav-logout" onclick="logout()">Logout</button>
  </div>
</aside>

<style>
.side-nav {
  position: fixed;
  top: 0; left: 0; bottom: 0;
  width: 220px;
  background: var(--navy2);
  color: var(--white);
  display: flex;
  flex-direction: column;
  align-items: stretch;
  z-index: 100;
  box-shadow: 2px 0 8px rgba(0,0,0,0.06);
}
.side-nav-brand {
  display: flex;
  align-items: center;
  gap: 12px;
  text-decoration: none;
  color: var(--white);
  font-size: 18px;
  font-weight: 700;
  padding: 28px 24px 18px 24px;
}
.side-nav-seal {
  width: 36px; height: 36px;
  border-radius: 50%;
  background: linear-gradient(135deg, var(--gold), var(--gold2));
  display: flex; align-items: center; justify-content: center;
  font-size: 15px; font-family: 'Playfair Display', serif;
  color: var(--navy);
}
.side-nav-links {
  display: flex;
  flex-direction: column;
  gap: 2px;
  padding: 0 12px;
  flex: 1;
}
.side-nav-links a {
  color: var(--white);
  text-decoration: none;
  font-size: 15px;
  font-weight: 500;
  padding: 12px 18px;
  border-radius: 8px;
  margin: 2px 0;
  transition: background 0.18s, color 0.18s;
}
.side-nav-links a:hover, .side-nav-links a.active {
  background: rgba(201,168,76,0.13);
  color: var(--gold);
}
.side-nav-user {
  padding: 18px 24px 24px 24px;
  display: flex;
  flex-direction: column;
  gap: 6px;
  border-top: 1px solid rgba(255,255,255,0.08);
}
.side-nav-name {
  font-size: 14px;
  font-weight: 600;
  color: var(--white);
}
.side-nav-tag {
  font-size: 12px;
  background: rgba(52,152,219,0.18);
  color: #74b9ff;
  border-radius: 10px;
  padding: 2px 10px;
  margin-top: 2px;
  display: inline-block;
}
.side-nav-logout {
  margin-top: 8px;
  background: none;
  border: 1px solid #fff2;
  color: var(--white);
  border-radius: 6px;
  padding: 6px 0;
  font-size: 13px;
  cursor: pointer;
  transition: background 0.18s, color 0.18s;
}
.side-nav-logout:hover {
  background: var(--gold);
  color: var(--navy2);
}
@media (max-width: 700px) {
  .side-nav { width: 100px; }
  .side-nav-brand span { display: none; }
  .side-nav-links a { font-size: 13px; padding: 10px 8px; }
  .side-nav-user { padding: 12px 8px; }
}
</style>
<script>
function logout() {
  sessionStorage.clear();
  window.location.href = 'login.html';
}
</script>