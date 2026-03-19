<?php
/**
 * INCLUDE: Treasurer Portal Navigation
 * includes/nav-treasurer.php
 *
 * USAGE:
 *   $active_page = 'ledger'; // 'dashboard'|'ledger'|'disbursement'|'reports'|'funds'
 *   require_once __DIR__ . '/../../includes/nav-treasurer.php';
 */
$active_page = $active_page ?? '';
$base_url    = $base_url ?? '../../';
?>
<nav style="position:relative;z-index:20;background:var(--navy2);border-bottom:1px solid rgba(201,168,76,.15);padding:0 28px;display:flex;align-items:center;height:60px">
    <a href="<?= $base_url ?>index.html" style="display:flex;align-items:center;gap:10px;text-decoration:none;color:inherit;margin-right:24px">
        <div style="width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,var(--gold),var(--gold2));display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;color:var(--navy);font-family:'Playfair Display',serif;flex-shrink:0">QC</div>
        <span style="font-size:12px;font-weight:600">RCTS-QC Treasury</span>
    </a>
    <div style="display:flex;align-items:center;gap:2px;flex:1">
        <?php
        $links = [
            'dashboard'    => ['href' => 'dashboard.html',      'label' => 'Dashboard'],
            'ledger'       => ['href' => 'live-ledger.html',    'label' => 'Live Ledger'],
            'disbursement' => ['href' => 'disbursement.html',   'label' => 'Disbursements'],
            'reports'      => ['href' => 'reports.html',        'label' => 'Reports / e-SRE'],
            'funds'        => ['href' => 'fund-management.html','label' => 'Fund Management'],
            'billing'      => ['href' => 'market-billing.html', 'label' => 'Market Billing'],
            'admin'        => ['href' => 'admin.html',          'label' => 'Admin Panel'],
        ];
        $c = json_decode($_COOKIE['rcts_citizen'] ?? '{}', true);
        $is_admin = isset($c['role']) && $c['role'] === 'admin';
        foreach ($links as $key => $link):
            if ($key === 'admin' && !$is_admin) continue;
            $is_active = ($active_page === $key);
        ?>
        <a href="<?= $link['href'] ?>" style="padding:7px 12px;font-size:12px;color:<?= $is_active ? 'var(--gold)' : 'var(--muted)' ?>;text-decoration:none;border-radius:6px;background:<?= $is_active ? 'rgba(201,168,76,.08)' : 'transparent' ?>">
            <?= htmlspecialchars($link['label']) ?>
        </a>
        <?php endforeach; ?>
    </div>
    <div style="display:flex;align-items:center;gap:10px">
        <span id="admin-badge" style="font-size:10px;padding:2px 8px;border-radius:8px;background:rgba(201,168,76,.15);color:var(--gold);font-weight:600">Treasury Staff</span>
        <span id="nav-treasurer-name" style="font-size:12px;color:var(--white)">—</span>
        <button onclick="sessionStorage.clear();location.href='<?= $base_url ?>pages/citizen/login.html'"
                style="background:none;border:1px solid rgba(201,168,76,.25);border-radius:6px;padding:5px 12px;font-size:11px;color:var(--muted);cursor:pointer;font-family:'DM Sans',sans-serif">
            Sign Out
        </button>
    </div>
</nav>
<script>
(function(){
    const c = JSON.parse(sessionStorage.getItem('rcts_citizen') || '{}');
    if (!['treasurer','revenue_officer','admin'].includes(c.role)) { location.href='<?= $base_url ?>pages/citizen/login.html'; return; }
    const el = document.getElementById('nav-treasurer-name');
    if (el) el.textContent = c.full_name || 'Treasury Staff';
    const badge = document.getElementById('admin-badge');
    if (badge) badge.textContent = (c.role === 'admin') ? 'Admin' : 'Treasury Staff';
})();
</script>