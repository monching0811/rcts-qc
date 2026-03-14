<?php
/**
 * INCLUDE: Shared HTML Head / Page Header
 * includes/header.php
 *
 * USAGE:
 *   $page_title = 'Real Property Tax';
 *   $portal     = 'citizen'; // 'citizen' | 'treasurer' | 'auditor'
 *   require_once __DIR__ . '/../../includes/header.php';
 */

$page_title = $page_title ?? 'RCTS-QC';
$portal     = $portal ?? 'citizen';
$base_url   = $base_url ?? '../../';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="description" content="RCTS-QC — Revenue Collection & Treasury Services, Quezon City Government">
<meta name="theme-color" content="#0a1628">
<title>RCTS-QC | <?= htmlspecialchars($page_title) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
/* ─── RCTS-QC Global Design Tokens ─────────────────────────────── */
:root {
    --navy:   #0a1628;
    --navy2:  #112240;
    --gold:   #c9a84c;
    --gold2:  #e8c878;
    --white:  #f5f3ee;
    --muted:  #8892a4;
    --green:  #2ecc71;
    --red:    #e74c3c;
    --orange: #f39c12;
    --blue:   #3498db;
    --purple: #a29bfe;
    /* Portal accent */
    --accent: <?php
        echo match($portal) {
            'treasurer' => '#c9a84c',
            'auditor'   => '#a29bfe',
            default     => '#3498db'
        };
    ?>;
}

/* ─── Reset & Base ──────────────────────────────────────────────── */
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

html { scroll-behavior: smooth; }

body {
    font-family: 'DM Sans', sans-serif;
    background: var(--navy);
    color: var(--white);
    min-height: 100vh;
    -webkit-font-smoothing: antialiased;
}

/* ─── Background geometric grid ────────────────────────────────── */
body::before {
    content: '';
    position: fixed; inset: 0;
    background-image:
        repeating-linear-gradient(45deg,  transparent, transparent 40px, rgba(201,168,76,.03) 40px, rgba(201,168,76,.03) 41px),
        repeating-linear-gradient(-45deg, transparent, transparent 40px, rgba(201,168,76,.03) 40px, rgba(201,168,76,.03) 41px);
    pointer-events: none; z-index: 0;
}

/* ─── Utilities ─────────────────────────────────────────────────── */
.sr-only { position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0,0,0,0); }

.text-gold   { color: var(--gold); }
.text-muted  { color: var(--muted); }
.text-green  { color: var(--green); }
.text-red    { color: var(--red); }
.text-purple { color: var(--purple); }

.font-display { font-family: 'Playfair Display', serif; }

/* ─── Common card ───────────────────────────────────────────────── */
.rcts-card {
    background: rgba(17, 34, 64, .7);
    border: 1px solid rgba(201, 168, 76, .12);
    border-radius: 12px;
    padding: 22px;
    margin-bottom: 18px;
}
.rcts-card h3 {
    font-family: 'Playfair Display', serif;
    font-size: 16px;
    margin-bottom: 16px;
    padding-bottom: 12px;
    border-bottom: 1px solid rgba(201, 168, 76, .1);
}

/* ─── Common buttons ────────────────────────────────────────────── */
.btn-primary {
    background: linear-gradient(135deg, var(--gold), var(--gold2));
    border: none; border-radius: 8px;
    padding: 11px 22px;
    font-family: 'DM Sans', sans-serif; font-size: 13px; font-weight: 700;
    color: var(--navy); cursor: pointer; letter-spacing: .04em;
    transition: opacity .2s, transform .15s; display: inline-block;
}
.btn-primary:hover { opacity: .88; transform: translateY(-1px); }
.btn-primary:disabled { opacity: .4; cursor: not-allowed; transform: none; }

.btn-secondary {
    background: rgba(201, 168, 76, .1);
    border: 1px solid rgba(201, 168, 76, .28);
    border-radius: 7px; padding: 7px 16px;
    font-family: 'DM Sans', sans-serif; font-size: 12px; font-weight: 600;
    color: var(--gold); cursor: pointer;
    transition: background .2s;
}
.btn-secondary:hover { background: rgba(201, 168, 76, .2); }

/* ─── Alert components ──────────────────────────────────────────── */
.alert {
    border-radius: 8px; padding: 12px 16px; font-size: 13px;
    margin-bottom: 16px; display: none; line-height: 1.5;
}
.alert.show   { display: block; }
.alert.success { background: rgba(46,204,113,.1);  border: 1px solid rgba(46,204,113,.3);  color: var(--green); }
.alert.error   { background: rgba(231,76,60,.1);   border: 1px solid rgba(231,76,60,.3);   color: #ff8a80; }
.alert.warning { background: rgba(243,156,18,.1);  border: 1px solid rgba(243,156,18,.3);  color: var(--orange); }
.alert.info    { background: rgba(52,152,219,.1);  border: 1px solid rgba(52,152,219,.3);  color: #74b9ff; }

/* ─── Form inputs ───────────────────────────────────────────────── */
.form-group { margin-bottom: 16px; }
.form-group label {
    display: block; font-size: 11px; font-weight: 600; letter-spacing: .06em;
    text-transform: uppercase; color: var(--muted); margin-bottom: 6px;
}
.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    background: rgba(10, 22, 40, .7);
    border: 1px solid rgba(201, 168, 76, .18);
    border-radius: 7px; padding: 10px 14px;
    color: var(--white); font-family: 'DM Sans', sans-serif; font-size: 13px;
    outline: none; transition: border-color .2s;
}
.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus { border-color: var(--gold); }
.form-group input::placeholder { color: var(--muted); }

/* ─── Status badges ─────────────────────────────────────────────── */
.badge { display: inline-block; font-size: 10px; padding: 2px 8px; border-radius: 8px; font-weight: 700; letter-spacing: .04em; }
.badge-pending  { background: rgba(243,156,18,.15); color: var(--orange); }
.badge-paid     { background: rgba(46,204,113,.15); color: var(--green); }
.badge-delinq   { background: rgba(231,76,60,.15);  color: var(--red); }
.badge-active   { background: rgba(46,204,113,.15); color: var(--green); }
.badge-inactive { background: rgba(136,146,164,.12);color: var(--muted); }

/* ─── Spinner ───────────────────────────────────────────────────── */
@keyframes spin { to { transform: rotate(360deg); } }
.spinner {
    display: inline-block; width: 16px; height: 16px;
    border: 2px solid rgba(255,255,255,.2); border-top-color: var(--gold);
    border-radius: 50%; animation: spin .7s linear infinite; vertical-align: middle;
}

/* ─── Pulse animation (live dots) ───────────────────────────────── */
@keyframes pulse { 0%,100% { opacity:1; } 50% { opacity:.3; } }

/* ─── Page fade-in ──────────────────────────────────────────────── */
@keyframes fadeUp { from { opacity:0; transform: translateY(20px); } to { opacity:1; transform:translateY(0); } }
.fade-up { opacity: 0; animation: fadeUp .45s forwards; }
</style>
<?php if (!empty($extra_styles)) echo $extra_styles; ?>
</head>
<body>
<!-- ─── Republic / City top bar ─────────────────────────────────── -->
<div style="background:var(--gold);padding:6px 32px;display:flex;align-items:center;gap:10px;position:relative;z-index:20">
    <span style="font-size:11px;font-weight:700;color:var(--navy);letter-spacing:.08em;text-transform:uppercase">Republic of the Philippines</span>
    <span style="width:4px;height:4px;border-radius:50%;background:var(--navy);opacity:.5;display:inline-block"></span>
    <span style="font-size:11px;font-weight:700;color:var(--navy);letter-spacing:.08em;text-transform:uppercase">Quezon City Government</span>
    <span style="width:4px;height:4px;border-radius:50%;background:var(--navy);opacity:.5;display:inline-block"></span>
    <span style="font-size:11px;font-weight:700;color:var(--navy);letter-spacing:.08em;text-transform:uppercase">Department 8 — RCTS</span>
    <span style="margin-left:auto;font-size:10px;color:var(--navy);opacity:.6" id="header-date"></span>
</div>
<script>
    document.getElementById('header-date').textContent =
        new Date().toLocaleDateString('en-PH', {weekday:'short', year:'numeric', month:'long', day:'numeric'});
</script>