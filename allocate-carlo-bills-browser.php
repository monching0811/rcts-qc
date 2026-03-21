<?php
/**
 * Browser-Accessible Carlo Nicolas Bill Allocation
 * 
 * Access this file in your browser at:
 * http://localhost/rcts-qc/allocate-carlo-bills-browser.php
 * 
 * Click the button to create all pending bills for Carlo Nicolas
 */

require_once __DIR__ . '/api/config/supabase.php';
require_once __DIR__ . '/api/config/constants.php';

$message = '';
$success = false;
$billSummary = [];

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_bills'])) {
    
    $carlo_id = 'QC-2024-000009';
    $carlo_name = 'Carlo Nicolas';
    
    $all_created_bills = [
        'RPT' => [],
        'BusinessTax' => [],
        'TrafficFine' => [],
        'MarketRental' => []
    ];
    
    $total_amount = 0;
    
    // ============================================================
    // 1. CREATE REAL PROPERTY TAX (RPT) BILLS FROM SUBSYSTEM 7
    // ============================================================
    
    $properties = [
        [
            'tdn_number' => 'TDN-QC-2024-CARLO-001',
            'property_name' => 'Residential Property at Barrio Fiesta',
            'assessed_value' => 700000,
            'annual_rpt_due' => 14000,
            'annual_sef_due' => 7000
        ],
        [
            'tdn_number' => 'TDN-QC-2024-CARLO-002',
            'property_name' => 'Commercial Property at Anonas Street',
            'assessed_value' => 4500000,
            'annual_rpt_due' => 90000,
            'annual_sef_due' => 45000
        ],
        [
            'tdn_number' => 'TDN-QC-2024-CARLO-003',
            'property_name' => 'Residential Property at Scout Borromeo',
            'assessed_value' => 560000,
            'annual_rpt_due' => 11200,
            'annual_sef_due' => 5600
        ],
        [
            'tdn_number' => 'TDN-QC-2024-CARLO-004',
            'property_name' => 'Residential Property at Katipunan Avenue',
            'assessed_value' => 520000,
            'annual_rpt_due' => 10400,
            'annual_sef_due' => 5200
        ],
        [
            'tdn_number' => 'TDN-QC-2024-CARLO-005',
            'property_name' => 'Industrial Property at Taguig Link Road',
            'assessed_value' => 4000000,
            'annual_rpt_due' => 80000,
            'annual_sef_due' => 40000
        ]
    ];
    
    foreach ($properties as $idx => $prop) {
        $base_amount = $prop['annual_rpt_due'] + $prop['annual_sef_due'];
        $bill_ref = 'RCTS-RPT-' . CURRENT_YEAR . '-CARLO-' . ($idx + 1);
        
        $bill_data = [
            'bill_reference_no' => $bill_ref,
            'qcitizen_id' => $carlo_id,
            'bill_type' => 'RPT',
            'originating_dept_id' => 7,
            'asset_id' => $prop['tdn_number'],
            'tax_year' => CURRENT_YEAR,
            'base_amount' => $base_amount,
            'discount_amount' => 0,
            'penalty_amount' => 0,
            'total_amount_due' => $base_amount,
            'status' => 'Pending',
            'due_date' => CURRENT_YEAR . '-03-31'
        ];
        
        $result = db_insert('rcts_assessment_billing_hub', $bill_data);
        
        if ($result) {
            $all_created_bills['RPT'][] = ['ref' => $bill_ref, 'amount' => $base_amount, 'property' => $prop['property_name']];
            $total_amount += $base_amount;
        }
    }
    
    // ============================================================
    // 2. CREATE BUSINESS TAX BILLS FROM SUBSYSTEM 2
    // ============================================================
    
    $businesses = [
        [
            'bin_number' => 'BIN-QC-2024-CARLO-001',
            'business_name' => "Carlo's Import & Export Trading",
            'gross_sales_declared' => 2500000,
            'business_tax_rate' => 0.03,
            'sanitary_fee' => 2000,
            'garbage_fee' => 1000,
            'fire_safety_fee' => 800
        ],
        [
            'bin_number' => 'BIN-QC-2024-CARLO-002',
            'business_name' => "Carlo's Construction Materials Supply",
            'gross_sales_declared' => 1800000,
            'business_tax_rate' => 0.03,
            'sanitary_fee' => 1500,
            'garbage_fee' => 750,
            'fire_safety_fee' => 600
        ]
    ];
    
    foreach ($businesses as $idx => $biz) {
        $business_tax = $biz['gross_sales_declared'] * $biz['business_tax_rate'];
        $total_fees = $biz['sanitary_fee'] + $biz['garbage_fee'] + $biz['fire_safety_fee'];
        $base_amount = $business_tax + $total_fees;
        $bill_ref = 'RCTS-BIZ-' . CURRENT_YEAR . '-CARLO-' . ($idx + 1);
        
        $bill_data = [
            'bill_reference_no' => $bill_ref,
            'qcitizen_id' => $carlo_id,
            'bill_type' => 'BusinessTax',
            'originating_dept_id' => 2,
            'asset_id' => $biz['bin_number'],
            'tax_year' => CURRENT_YEAR,
            'base_amount' => $base_amount,
            'discount_amount' => 0,
            'penalty_amount' => 0,
            'total_amount_due' => $base_amount,
            'status' => 'Pending',
            'due_date' => CURRENT_YEAR . '-03-31'
        ];
        
        $result = db_insert('rcts_assessment_billing_hub', $bill_data);
        
        if ($result) {
            $all_created_bills['BusinessTax'][] = ['ref' => $bill_ref, 'amount' => $base_amount, 'business' => $biz['business_name']];
            $total_amount += $base_amount;
        }
    }
    
    // ============================================================
    // 3. CREATE TRAFFIC FINE BILLS FROM SUBSYSTEM 9
    // ============================================================
    
    $violations_file = __DIR__ . '/mock-data/subsystem9/traffic-violations.json';
    $violations_data = json_decode(file_get_contents($violations_file), true);
    $all_violations = $violations_data['violations'] ?? [];
    $violations = array_filter($all_violations, function($v) use ($carlo_id) {
        return $v['qcitizen_id'] === $carlo_id;
    });
    
    foreach ($violations as $violation) {
        $ticket_num = $violation['ticket_number'];
        $violation_type = $violation['violation_type'];
        $fine_amount = $violation['fine_amount'];
        $bill_ref = 'RCTS-TF-' . CURRENT_YEAR . '-' . strtoupper(substr(uniqid(), -6));
        
        $bill_data = [
            'bill_reference_no' => $bill_ref,
            'qcitizen_id' => $carlo_id,
            'bill_type' => 'TrafficFine',
            'originating_dept_id' => 3,
            'asset_id' => $ticket_num,
            'tax_year' => CURRENT_YEAR,
            'base_amount' => $fine_amount,
            'discount_amount' => 0,
            'penalty_amount' => 0,
            'total_amount_due' => $fine_amount,
            'status' => 'Pending',
            'due_date' => CURRENT_YEAR . '-04-12'
        ];
        
        $result = db_insert('rcts_assessment_billing_hub', $bill_data);
        
        if ($result) {
            $all_created_bills['TrafficFine'][] = ['ref' => $bill_ref, 'amount' => $fine_amount, 'violation' => $violation_type];
            $total_amount += $fine_amount;
        }
    }
    
    // ============================================================
    // 4. CREATE MARKET STALL RENTAL BILLS FROM SUBSYSTEM 10
    // ============================================================
    
    $stalls_file = __DIR__ . '/mock-data/subsystem10/stalls.json';
    $stalls_data = json_decode(file_get_contents($stalls_file), true);
    $all_stalls = $stalls_data['stalls'] ?? [];
    $carlo_stalls = array_filter($all_stalls, function($stall) use ($carlo_id) {
        return $stall['qcitizen_id'] === $carlo_id;
    });
    
    foreach ($carlo_stalls as $stall) {
        $stall_id = $stall['stall_asset_id'];
        $stall_name = $stall['stall_name'];
        $monthly_rate = $stall['monthly_rental_rate'];
        $bill_ref = 'RCTS-MKT-' . CURRENT_YEAR . '-' . strtoupper(substr(uniqid(), -6));
        
        $bill_data = [
            'bill_reference_no' => $bill_ref,
            'qcitizen_id' => $carlo_id,
            'bill_type' => 'MarketRental',
            'originating_dept_id' => 10,
            'asset_id' => $stall_id,
            'tax_year' => CURRENT_YEAR,
            'base_amount' => $monthly_rate,
            'discount_amount' => 0,
            'penalty_amount' => 0,
            'total_amount_due' => $monthly_rate,
            'status' => 'Pending',
            'due_date' => CURRENT_YEAR . '-03-15'
        ];
        
        $result = db_insert('rcts_assessment_billing_hub', $bill_data);
        
        if ($result) {
            $all_created_bills['MarketRental'][] = ['ref' => $bill_ref, 'amount' => $monthly_rate, 'stall' => $stall_name];
            $total_amount += $monthly_rate;
        }
    }
    
    // Build summary
    $total_bills = 0;
    foreach ($all_created_bills as $type => $bills) {
        $count = count($bills);
        $total_bills += $count;
        $typeTotal = array_sum(array_column($bills, 'amount'));
        $billSummary[] = [
            'type' => $type,
            'count' => $count,
            'total' => $typeTotal,
            'bills' => $bills
        ];
    }
    
    $success = true;
    $message = "Successfully created $total_bills pending bills for Carlo Nicolas!";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Allocate Carlo Nicolas Bills - RCTS-QC</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 900px;
            margin: 0 auto;
        }
        
        .header {
            text-align: center;
            color: white;
            margin-bottom: 30px;
        }
        
        .header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        
        .header p {
            font-size: 1.2rem;
            opacity: 0.9;
        }
        
        .card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            overflow: hidden;
            margin-bottom: 20px;
        }
        
        .card-header {
            background: linear-gradient(135deg, #2d7fb8 0%, #1a5a8a 100%);
            color: white;
            padding: 20px 25px;
        }
        
        .card-header h2 {
            font-size: 1.5rem;
        }
        
        .card-body {
            padding: 25px;
        }
        
        .citizen-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }
        
        .info-box {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            border-left: 4px solid #2d7fb8;
        }
        
        .info-box label {
            display: block;
            color: #666;
            font-size: 0.85rem;
            margin-bottom: 5px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .info-box span {
            display: block;
            color: #333;
            font-size: 1.1rem;
            font-weight: 600;
        }
        
        .btn {
            display: inline-block;
            padding: 15px 40px;
            background: linear-gradient(135deg, #2d7fb8 0%, #1a5a8a 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
            text-decoration: none;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(45, 127, 184, 0.4);
        }
        
        .btn:active {
            transform: translateY(0);
        }
        
        .btn:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        
        .success-message {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        .success-message h3 {
            margin-bottom: 10px;
        }
        
        .bill-section {
            margin-bottom: 25px;
        }
        
        .bill-section h4 {
            color: #2d7fb8;
            font-size: 1.2rem;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e9ecef;
        }
        
        .bill-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .bill-table th,
        .bill-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e9ecef;
        }
        
        .bill-table th {
            background: #f8f9fa;
            color: #666;
            font-weight: 600;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .bill-table tr:hover {
            background: #f8f9fa;
        }
        
        .bill-table .amount {
            font-weight: 600;
            color: #dc3545;
        }
        
        .total-row {
            background: #e9ecef !important;
            font-weight: 700;
        }
        
        .total-row td {
            border-top: 2px solid #2d7fb8;
        }
        
        .grand-total {
            background: linear-gradient(135deg, #2d7fb8 0%, #1a5a8a 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            margin-top: 20px;
        }
        
        .grand-total h3 {
            font-size: 1rem;
            opacity: 0.9;
            margin-bottom: 5px;
        }
        
        .grand-total .amount {
            font-size: 2.5rem;
            font-weight: 700;
        }
        
        .login-link {
            display: inline-block;
            margin-top: 20px;
            padding: 12px 25px;
            background: #28a745;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
        }
        
        .login-link:hover {
            background: #218838;
        }
        
        .warning {
            background: #fff3cd;
            border: 1px solid #ffc107;
            color: #856404;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🏛️ RCTS-QC Bill Allocation</h1>
            <p>Citizen Portal - Pending Bills Generator</p>
        </div>
        
        <?php if ($success): ?>
        <div class="card">
            <div class="card-header">
                <h2>✅ Bills Created Successfully!</h2>
            </div>
            <div class="card-body">
                <div class="success-message">
                    <h3><?= $message ?></h3>
                </div>
                
                <?php foreach ($billSummary as $section): ?>
                <div class="bill-section">
                    <h4><?= htmlspecialchars($section['type']) ?> (<?= $section['count'] ?> bills)</h4>
                    <table class="bill-table">
                        <thead>
                            <tr>
                                <th>Reference No.</th>
                                <th>Description</th>
                                <th>Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($section['bills'] as $bill): ?>
                            <tr>
                                <td><?= htmlspecialchars($bill['ref']) ?></td>
                                <td>
                                    <?php 
                                    if (isset($bill['property'])) echo htmlspecialchars($bill['property']);
                                    elseif (isset($bill['business'])) echo htmlspecialchars($bill['business']);
                                    elseif (isset($bill['violation'])) echo htmlspecialchars($bill['violation']);
                                    elseif (isset($bill['stall'])) echo htmlspecialchars($bill['stall']);
                                    ?>
                                </td>
                                <td class="amount">₱<?= number_format($bill['amount'], 2) ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <tr class="total-row">
                                <td colspan="2">Subtotal</td>
                                <td class="amount">₱<?= number_format($section['total'], 2) ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <?php endforeach; ?>
                
                <div class="grand-total">
                    <h3>TOTAL PENDING BILLS</h3>
                    <div class="amount">₱<?= number_format($total_amount, 2) ?></div>
                </div>
                
                <div style="text-align: center; margin-top: 30px;">
                    <p><strong>Citizen:</strong> Carlo Nicolas (QC-2024-000009)</p>
                    <p><strong>Email:</strong> jackbobert24@gmail.com</p>
                    <a href="https://rcts-qc.great-site.net/pages/citizen/login.html" target="_blank" class="login-link">
                        🔐 Login to Citizen Portal
                    </a>
                </div>
            </div>
        </div>
        <?php else: ?>
        <div class="card">
            <div class="card-header">
                <h2>📋 Create Pending Bills for Carlo Nicolas</h2>
            </div>
            <div class="card-body">
                <div class="warning">
                    <strong>⚠️ Note:</strong> This will create 17 pending bills in the database. 
                    If bills already exist, they will be duplicated. Consider clearing existing bills first.
                </div>
                
                <div class="citizen-info">
                    <div class="info-box">
                        <label>Citizen Name</label>
                        <span>Carlo Nicolas</span>
                    </div>
                    <div class="info-box">
                        <label>Citizen ID</label>
                        <span>QC-2024-000009</span>
                    </div>
                    <div class="info-box">
                        <label>Email</label>
                        <span>jackbobert24@gmail.com</span>
                    </div>
                    <div class="info-box">
                        <label>Expected Bills</label>
                        <span>17 bills</span>
                    </div>
                </div>
                
                <h4 style="color: #2d7fb8; margin-bottom: 15px;">Bill Types to be Created:</h4>
                <ul style="margin-left: 20px; margin-bottom: 25px; color: #555;">
                    <li><strong>Real Property Tax (RPT)</strong> - 5 properties from Subsystem 7</li>
                    <li><strong>Business Tax</strong> - 2 businesses from Subsystem 2</li>
                    <li><strong>Traffic Fines</strong> - 5 violations from Subsystem 9</li>
                    <li><strong>Market Stall Rental</strong> - 5 stalls from Subsystem 10</li>
                </ul>
                
                <form method="POST">
                    <button type="submit" name="create_bills" class="btn">
                        🚀 Create All Pending Bills
                    </button>
                </form>
            </div>
        </div>
        <?php endif; ?>
        
        <div style="text-align: center; margin-top: 20px; color: white; opacity: 0.8;">
            <p>RCTS-QC - Real Property Tax Collection System - Quezon City</p>
        </div>
    </div>
</body>
</html>
