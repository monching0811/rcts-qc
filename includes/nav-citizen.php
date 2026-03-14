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