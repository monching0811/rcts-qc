<?php
/**
 * INCLUDE: Admin Portal Navigation
 * includes/nav-admin.php
 *
 * USAGE:
 *   $active_page = 'dashboard'; // 'dashboard'|'users'|'settings'|'logs'|'apikeys'
 *   require_once __DIR__ . '/../includes/nav-admin.php';
 */
$active_page = $active_page ?? '';
$base_url    = $base_url ?? '../../';
?>
<nav style="position:relative;z-index:20;background:var(--navy2);border-bottom:1px solid rgba(201,168,76,.15);padding:0 28px;display:flex;align-items:center;height:60px">
    <a href="<?= $base_url ?>index.html" style="display:flex;align-items:center;gap:10px;text-decoration:none;color:inherit;margin-right:24px">
        <div style="width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,var(--gold),var(--gold2));display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;color:var(--navy);font-family:'Playfair Display',serif;flex-shrink:0">QC</div>
        <span style="font-size:12px;font-weight:600">RCTS-QC Administration</span>
    </a>
    <div style="display:flex;align-items:center;gap:2px;flex:1">
        <?php
        $links = [
            'dashboard'    => ['href' => 'dashboard.html',      'label' => 'Dashboard'],
            'users'        => ['href' => 'dashboard.html#users', 'label' => 'User Management'],
            'settings'     => ['href' => 'dashboard.html#settings', 'label' => 'System Settings'],
            'logs'         => ['href' => 'dashboard.html#logs',  'label' => 'Audit Logs'],
            'apikeys'      => ['href' => 'dashboard.html#apikeys', 'label' => 'API Management'],
        ];
        $c = json_decode($_COOKIE['rcts_citizen'] ?? '{}', true);
        $is_admin = isset($c['role']) && $c['role'] === 'admin';
        
        if (!$is_admin) {
            header('Location: ' . $base_url . 'index.html');
            exit;
        }
        
        foreach ($links as $key => $link):
            $is_active = ($active_page === $key);
        ?>
        <a href="<?= $link['href'] ?>" style="padding:7px 12px;font-size:12px;color:<?= $is_active ? 'var(--gold)' : 'var(--muted)' ?>;text-decoration:none;border-radius:6px;background:<?= $is_active ? 'rgba(201,168,76,.08)' : 'transparent' ?>">
            <?= htmlspecialchars($link['label']) ?>
        </a>
        <?php endforeach; ?>
    </div>
    <div style="display:flex;align-items:center;gap:10px">
        <span id="admin-role-badge" style="font-size:10px;padding:3px 10px;border-radius:8px;background:rgba(155,89,182,.15);color:#a29bfe;font-weight:600;text-transform:uppercase;letter-spacing:0.05em">Administrator</span>
        <span id="nav-admin-name" style="font-size:12px;color:var(--white)">—</span>
        <button onclick="if(confirm('Sign out from admin portal?')){sessionStorage.clear();location.href='<?= $base_url ?>index.html'}"
                style="background:none;border:1px solid rgba(201,168,76,.25);border-radius:6px;padding:5px 12px;font-size:11px;color:var(--muted);cursor:pointer;font-family:'DM Sans',sans-serif">
            Sign Out
        </button>
    </div>
</nav>
<script>
(function(){
    try {
        const citizen = JSON.parse(sessionStorage.getItem('rcts_citizen') || '{}');
        const nameEl = document.getElementById('nav-admin-name');
        if (nameEl && citizen.full_name) {
            nameEl.textContent = citizen.full_name;
        }
    } catch (e) {
        console.error('Failed to parse citizen data:', e);
    }
})();
</script>
