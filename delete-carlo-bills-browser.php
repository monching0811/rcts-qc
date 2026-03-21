<?php
/**
 * Browser-Accessible Carlo Nicolas Bill Deletion
 * 
 * Access this file in your browser at:
 * http://localhost/rcts-qc/delete-carlo-bills-browser.php
 * 
 * Click the button to delete all pending bills for Carlo Nicolas
 */

require_once __DIR__ . '/api/config/supabase.php';

$carlo_id = 'QC-2024-000009';
$message = '';
$success = false;
$deleted_count = 0;

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_bills'])) {
    
    // First, get current bills count
    $currentBills = db_select('rcts_assessment_billing_hub', [
        'qcitizen_id' => 'eq.' . $carlo_id
    ]);
    
    if ($currentBills['success'] && count($currentBills['data']) > 0) {
        $deleted_count = count($currentBills['data']);
        
        // Delete all bills for Carlo
        $deleteResult = db_delete('rcts_assessment_billing_hub', [
            'qcitizen_id' => 'eq.' . $carlo_id
        ]);
        
        if ($deleteResult['success']) {
            $success = true;
            $message = "Successfully deleted $deleted_count bills for Carlo Nicolas!";
        } else {
            $message = "Failed to delete bills: " . json_encode($deleteResult);
        }
    } else {
        $success = true;
        $message = "No bills found for Carlo Nicolas. Nothing to delete.";
    }
}

// Get current count for display
$currentBills = db_select('rcts_assessment_billing_hub', [
    'qcitizen_id' => 'eq.' . $carlo_id
]);
$currentCount = ($currentBills['success']) ? count($currentBills['data']) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Carlo Nicolas Bills - RCTS-QC</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 600px;
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
        }
        
        .card-header {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
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
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 25px;
        }
        
        .citizen-info h3 {
            color: #333;
            margin-bottom: 15px;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        .info-box {
            background: white;
            padding: 12px;
            border-radius: 8px;
            border-left: 3px solid #dc3545;
        }
        
        .info-box label {
            display: block;
            color: #666;
            font-size: 0.8rem;
            margin-bottom: 3px;
            text-transform: uppercase;
        }
        
        .info-box span {
            display: block;
            color: #333;
            font-weight: 600;
        }
        
        .bill-count {
            background: #fff3cd;
            border: 1px solid #ffc107;
            color: #856404;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .bill-count strong {
            font-size: 1.5rem;
            display: block;
        }
        
        .warning {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 0.95rem;
        }
        
        .btn {
            display: inline-block;
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(220, 53, 69, 0.4);
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
        
        .btn-secondary {
            background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%);
            margin-top: 15px;
        }
        
        .btn-secondary:hover {
            box-shadow: 0 5px 20px rgba(108, 117, 125, 0.4);
        }
        
        .success-message {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
        }
        
        .success-message h3 {
            margin-bottom: 10px;
        }
        
        .success-message .count {
            font-size: 2rem;
            font-weight: 700;
            display: block;
            margin: 10px 0;
        }
        
        .info-message {
            background: #cce5ff;
            border: 1px solid #b8daff;
            color: #004085;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
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
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🗑️ Delete Bills</h1>
            <p>RCTS-QC - Carlo Nicolas Bill Manager</p>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h2>🧹 Clear Carlo Nicolas Bills</h2>
            </div>
            <div class="card-body">
                <?php if ($success): ?>
                    <div class="success-message">
                        <h3>✅ Bills Deleted Successfully!</h3>
                        <span class="count"><?= $deleted_count ?> bills removed</span>
                        <p><?= htmlspecialchars($message) ?></p>
                        <a href="allocate-carlo-bills-browser.php" class="login-link">
                            ➕ Create New Bills
                        </a>
                    </div>
                <?php elseif ($currentCount === 0): ?>
                    <div class="info-message">
                        <h3>ℹ️ No Bills Found</h3>
                        <p>There are no pending bills for Carlo Nicolas in the database.</p>
                        <a href="allocate-carlo-bills-browser.php" class="login-link">
                            ➕ Create Bills
                        </a>
                    </div>
                <?php else: ?>
                    <div class="citizen-info">
                        <h3>Citizen Information</h3>
                        <div class="info-grid">
                            <div class="info-box">
                                <label>Name</label>
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
                                <label>Status</label>
                                <span>Active</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bill-count">
                        <strong><?= $currentCount ?></strong>
                        Pending Bills Found
                    </div>
                    
                    <div class="warning">
                        <strong>⚠️ Warning:</strong> This action cannot be undone! 
                        All pending bills for Carlo Nicolas will be permanently deleted from the database.
                    </div>
                    
                    <form method="POST">
                        <button type="submit" name="delete_bills" class="btn" onclick="return confirm('Are you sure you want to delete all <?= $currentCount ?> bills for Carlo Nicolas? This cannot be undone!');">
                            🗑️ Delete All Bills (<?= $currentCount ?>)
                        </button>
                    </form>
                    
                    <a href="allocate-carlo-bills-browser.php" class="btn btn-secondary">
                        ➕ Go to Create Bills Page
                    </a>
                <?php endif; ?>
            </div>
        </div>
        
        <div style="text-align: center; margin-top: 20px; color: white; opacity: 0.8;">
            <p>RCTS-QC - Real Property Tax Collection System - Quezon City</p>
        </div>
    </div>
</body>
</html>
