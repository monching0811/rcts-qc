<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RCTS-QC | Allocate Bills for Carlo Nicolas</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --navy: #0a1628;
            --gold: #c9a84c;
            --gold2: #e8c878;
            --green: #2ecc71;
            --red: #e74c3c;
        }
        body {
            font-family: 'DM Sans', sans-serif;
            background: #f5f5f5;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
        }
        .header {
            background: var(--navy);
            color: white;
            padding: 30px;
            border-radius: 12px 12px 0 0;
            text-align: center;
        }
        .header h1 {
            font-family: 'Playfair Display', serif;
            margin: 0 0 10px 0;
        }
        .header p {
            opacity: 0.8;
            margin: 0;
        }
        .card {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        }
        .btn {
            background: linear-gradient(135deg, var(--gold), var(--gold2));
            border: none;
            border-radius: 8px;
            padding: 14px 28px;
            font-size: 16px;
            font-weight: 600;
            color: var(--navy);
            cursor: pointer;
            width: 100%;
            margin-bottom: 15px;
        }
        .btn:hover {
            opacity: 0.9;
        }
        .btn-delete {
            background: linear-gradient(135deg, var(--red), #c0392b);
            color: white;
        }
        .section {
            margin-bottom: 25px;
            padding: 20px;
            background: #f9f9f9;
            border-radius: 8px;
        }
        .section h3 {
            margin-top: 0;
            color: var(--navy);
            border-bottom: 2px solid var(--gold);
            padding-bottom: 10px;
        }
        .success { color: var(--green); }
        .error { color: var(--red); }
        .log {
            background: #1a1a2e;
            color: #0f0;
            padding: 15px;
            border-radius: 8px;
            font-family: monospace;
            font-size: 13px;
            max-height: 400px;
            overflow-y: auto;
            white-space: pre-wrap;
        }
        .summary {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }
        .summary-card {
            background: var(--navy);
            color: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
        }
        .summary-card .count {
            font-size: 32px;
            font-weight: bold;
            color: var(--gold);
        }
        .summary-card .label {
            font-size: 12px;
            opacity: 0.8;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎫 RCTS-QC Bill Allocation Simulator</h1>
            <p>Allocate bills for Carlo Nicolas (QC-2024-000009)</p>
            <p style="font-size: 12px; margin-top: 10px; opacity: 0.6;">Simulates data flow from Subsystems 2, 4, 7, 9, 10 → Database</p>
        </div>
        
        <div class="card">
            <h2 style="margin-top: 0;">📋 Allocation Summary</h2>
            <div class="summary">
                <div class="summary-card">
                    <div class="count">3</div>
                    <div class="label">RPT Bills</div>
                </div>
                <div class="summary-card">
                    <div class="count">3</div>
                    <div class="label">Business Tax</div>
                </div>
                <div class="summary-card">
                    <div class="count">3</div>
                    <div class="label">Market Stalls</div>
                </div>
                <div class="summary-card">
                    <div class="count">3</div>
                    <div class="label">Traffic Fines</div>
                </div>
            </div>
            <p style="text-align: center; font-size: 18px;"><strong>Total: 12 bills = ₱146,260.00</strong></p>
        </div>
        
        <div class="card">
            <h2 style="margin-top: 0;">🚀 Run Allocation</h2>
            <p>Click the button below to simulate bill allocation from all subsystems:</p>
            <button class="btn" onclick="runAllocation()">Allocate Bills for Carlo Nicolas</button>
            <div id="result" style="margin-top: 20px;"></div>
        </div>
        
        <div class="card">
            <h2 style="margin-top: 0;">🗑️ Delete Allocation</h2>
            <p>Click to remove all Carlo Nicolas bills and data:</p>
            <button class="btn btn-delete" onclick="deleteAllocation()">Delete All Carlo Nicolas Data</button>
            <div id="deleteResult" style="margin-top: 20px;"></div>
        </div>
        
        <div class="card">
            <h2 style="margin-top: 0;">📖 Documentation</h2>
            <div class="section">
                <h3>How This Works (For Professor)</h3>
                <p>This simulation demonstrates how bills are created when there's <strong>NO real integration</strong> with the government subsystems:</p>
                <ol>
                    <li><strong>Mock Data Files</strong> - JSON files simulate responses from Subsystems 2, 4, 7, 9, 10</li>
                    <li><strong>Allocation Script</strong> - PHP reads mock data and creates bills in the database</li>
                    <li><strong>Billing Hub</strong> - All bills stored in <code>rcts_assessment_billing_hub</code> table</li>
                    <li><strong>Dashboard Display</strong> - Frontend reads from billing hub and displays to citizen</li>
                </ol>
            </div>
            <div class="section">
                <h3>Data Sources</h3>
                <ul>
                    <li><strong>RPT</strong>: mock-data/subsystem7/properties.json</li>
                    <li><strong>Business Tax</strong>: mock-data/subsystem2/businesses.json</li>
                    <li><strong>Regulatory Clearances</strong>: mock-data/subsystem4/clearances.json</li>
                    <li><strong>Traffic Violations</strong>: mock-data/subsystem9/traffic-violations.json</li>
                    <li><strong>Market Stalls</strong>: mock-data/subsystem10/stalls.json</li>
                </ul>
            </div>
        </div>
    </div>
    
    <script>
    async function runAllocation() {
        const resultDiv = document.getElementById('result');
        resultDiv.innerHTML = '<div class="log">⏳ Allocating bills...\n\nThis may take a few seconds...</div>';
        
        try {
            // Call the CLI PHP script which now includes early bird discount
            const response = await fetch('create-carlo-all-bills.php');
            const text = await response.text();
            resultDiv.innerHTML = '<div class="log">' + text + '</div>';
        } catch (e) {
            resultDiv.innerHTML = '<div class="error">Error: ' + e.message + '</div>';
        }
    }
    
    async function deleteAllocation() {
        if (!confirm('Are you sure you want to delete all Carlo Nicolas data?')) return;
        
        const resultDiv = document.getElementById('deleteResult');
        resultDiv.innerHTML = '<div class="log">⏳ Deleting data...\n</div>';
        
        try {
            const response = await fetch('delete-carlo-bills-browser.php');
            const text = await response.text();
            resultDiv.innerHTML = '<div class="log">' + text + '</div>';
        } catch (e) {
            resultDiv.innerHTML = '<div class="error">Error: ' + e.message + '</div>';
        }
    }
    </script>
</body>
</html>
