# RCTS-QC Pending Bills Allocation Process

## Overview
This document outlines the proper TO-BE process for allocating pending bills to citizens in the RCTS-QC system. The key principle is that **all bills must be generated through subsystem API endpoints**, not direct database inserts, to maintain proper integration and data flow.

## Citizens and Their Bill Types

### 1. Vince Nico Escala (b529bf30-50bf-43ab-a314-cc4c2f79c3f5)
- **Bill Type**: Real Property Tax (RPT)
- **Integration**: Subsystem 7 (Urban Planning & Zoning via subsystem7.php)
- **API Endpoint**: `rpt.php?action=generate_bill`
- **Data Source**: GIS-linked property records from S7
- **Frontend Section**: "Your Registered Properties" + "Pending RPT Bills"
- **Expected Bills**: 5 RPT bills (one per property)

### 2. Raven Pogi (92be37af-7c34-4c9b-80cb-47cde7c3a9fd)
- **Bill Types**:
  - Business Tax (Subsystem 2/4 integration)
  - Traffic Fines (Subsystem 9 integration)
- **API Endpoints**:
  - `business-tax.php?action=generate_bill`
  - Traffic fines endpoint (via subsystem9.php)
- **Expected Bills**: 5 business tax + 5 traffic fines

### 3. Dave Mercado (eacd934b-0195-4640-b37c-aa0a8b40a9d2)
- **Bill Type**: Market Stall Rentals
- **Integration**: Subsystem 10 (Public Assets)
- **API Endpoint**: `market-stall.php?action=generate_bill`
- **Data Source**: Stall occupancy data from S10
- **Expected Bills**: 5 market stall rental bills

## Prerequisites

1. **Clean State**: Delete all existing pending bills first
   ```bash
   php delete-all-pending-bills.php
   ```

2. **Subsystem Data**: Ensure mock data is properly configured:
   - `mock-data/subsystem7/properties.json` - Vince's 5 properties
   - `mock-data/subsystem2/business-registry.json` - Raven's businesses
   - `mock-data/subsystem9/traffic-violations.json` - Raven's traffic fines
   - `mock-data/subsystem10/stalls.json` - Dave's market stalls

3. **API Services**: Ensure all subsystem APIs are accessible
   - S1 (Citizen Registry) - for citizen identity
   - S7 (Urban Planning) - for property data
   - S2/S4 (Business) - for business tax
   - S9 (Traffic) - for traffic violations
   - S10 (Public Assets) - for market stalls

## Step-by-Step Allocation Process

### Step 1: Vince Nico Escala - RPT Bills

```php
// POST request to rpt.php
$url = 'http://localhost/rcts-qc/api/endpoints/rpt.php?action=generate_bill';
$data = ['qcitizen_id' => 'b529bf30-50bf-43ab-a314-cc4c2f79c3f5'];

$options = [
    'http' => [
        'header'  => "Content-Type: application/json\r\n",
        'method'  => 'POST',
        'content' => json_encode($data),
    ],
];

$result = file_get_contents($url, false, stream_context_create($options));
```

**What happens internally:**
1. Fetches citizen data from S1 API
2. Retrieves 5 properties from S7 API (`get_properties_by_citizen`)
3. Computes RPT taxes using S7's `rpt_computation` data
4. Applies discounts (Senior/PWD, early bird) if applicable
5. Creates 5 bills in `rcts_assessment_billing_hub` table
6. Links bills to GIS coordinates and property data

**Expected Result:** 5 RPT bills totaling ₱253,200.00

### Step 2: Raven Pogi - Business Tax Bills

```php
// POST request to business-tax.php
$url = 'http://localhost/rcts-qc/api/endpoints/business-tax.php?action=generate_bill';
$data = ['qcitizen_id' => '92be37af-7c34-4c9b-80cb-47cde7c3a9fd'];

$options = [
    'http' => [
        'header'  => "Content-Type: application/json\r\n",
        'method'  => 'POST',
        'content' => json_encode($data),
    ],
];

$result = file_get_contents($url, false, stream_context_create($options));
```

**What happens internally:**
1. Fetches business registrations from S2/S4 APIs
2. Computes annual business taxes based on business type and revenue
3. Creates bills for each active business
4. Links to business permit data

**Expected Result:** 5 business tax bills

### Step 3: Raven Pogi - Traffic Fine Bills

```php
// POST request to traffic fines endpoint (via subsystem9.php)
$url = 'http://localhost/rcts-qc/api/endpoints/subsystem9.php?action=generate_traffic_fines';
$data = ['qcitizen_id' => '92be37af-7c34-4c9b-80cb-47cde7c3a9fd'];

$options = [
    'http' => [
        'header'  => "Content-Type: application/json\r\n",
        'method'  => 'POST',
        'content' => json_encode($data),
    ],
];

$result = file_get_contents($url, false, stream_context_create($options));
```

**What happens internally:**
1. Queries traffic violation records from S9
2. Computes fines based on violation type and date
3. Creates penalty bills for unpaid violations
4. Links to LTO/driver data

**Expected Result:** 5 traffic fine bills

### Step 4: Dave Mercado - Market Stall Bills

```php
// POST request to market-stall.php
$url = 'http://localhost/rcts-qc/api/endpoints/market-stall.php?action=generate_bill';
$data = ['qcitizen_id' => 'eacd934b-0195-4640-b37c-aa0a8b40a9d2'];

$options = [
    'http' => [
        'header'  => "Content-Type: application/json\r\n",
        'method'  => 'POST',
        'content' => json_encode($data),
    ],
];

$result = file_get_contents($url, false, stream_context_create($options));
```

**What happens internally:**
1. Fetches occupied stalls from S10 API
2. Computes monthly rental fees
3. Creates bills for current billing period
4. Links to stall occupancy verification

**Expected Result:** 5 market stall rental bills

## Verification Steps

### Database Verification
```sql
-- Check all pending bills by citizen
SELECT qcitizen_id, bill_type, COUNT(*) as bill_count,
       SUM(total_amount_due) as total_amount
FROM rcts_assessment_billing_hub
WHERE status = 'Pending'
GROUP BY qcitizen_id, bill_type
ORDER BY qcitizen_id, bill_type;
```

**Expected Results:**
- Vince: 5 RPT bills, ₱253,200.00 total
- Raven: 5 Business Tax + 5 Traffic Fines, ₱47,600.00 total
- Dave: 5 Market Stall bills, ₱15,500.00 total

### API Verification
```php
// Check bills via API
$result = db('rcts_assessment_billing_hub', [
    'status' => 'eq.Pending',
    'order' => 'qcitizen_id.asc,bill_type.asc'
]);
```

### Frontend Integration Check
- **Citizen Dashboard**: Verify bills appear in correct sections
- **Treasurer Dashboard**: Verify unified billing hub shows all bills
- **Property Section**: Verify Vince's properties link to RPT bills

## Why This Process is Correct (TO-BE Architecture)

### ❌ Wrong Approach (AS-IS)
```php
// Direct database insert - VIOLATES TO-BE
db_insert('rcts_assessment_billing_hub', [
    'bill_reference_no' => 'MANUAL-RPT-001',
    'qcitizen_id' => 'b529bf30-50bf-43ab-a314-cc4c2f79c3f5',
    'bill_type' => 'RPT',
    // ... manual data entry
]);
```
**Problems:**
- No subsystem integration
- Manual data entry errors
- No GIS/property linkage
- Cannot update from source systems
- Breaks audit trail

### ✅ Correct Approach (TO-BE)
```php
// API-driven generation - FOLLOWS TO-BE
POST /api/endpoints/rpt.php?action=generate_bill
{
    "qcitizen_id": "b529bf30-50bf-43ab-a314-cc4c2f79c3f5"
}
```
**Benefits:**
- Automatic data sync from subsystems
- Real-time property/business data
- Proper GIS integration
- Maintainable audit trail
- Supports automated updates
- Enables proper tax clearance workflow

## Automation Scripts

For convenience, use the individual implementation scripts:

```bash
# Complete bill allocation process
php delete-pending-bills.php
php recreate-raven-business-bills.php
php create-raven-traffic-fines.php
php create-dave-market-bills.php

# Verification
php check-all-citizen-bills.php
```

**Note**: Vince's RPT bills are generated automatically through the `rpt.php` API when the citizen dashboard loads properties from S7.

## Troubleshooting

### Common Issues

1. **"No properties found" for Vince**
   - Check `mock-data/subsystem7/properties.json` has 5 Vince properties
   - Verify `api/endpoints/subsystem7.php` has Vince's data

2. **"No businesses found" for Raven**
   - Check `mock-data/subsystem2/business-registry.json`
   - Verify business registration data

3. **Duplicate bills created**
   - Bills are deduplicated by TDN/asset_id + tax_year
   - Delete existing bills first if needed

4. **API connection errors**
   - Ensure XAMPP Apache is running
   - Check API endpoints are accessible
   - Verify mock data files exist

### Recovery Steps

1. **Clean reset:**
   ```bash
   php delete-pending-bills.php
   php recreate-raven-business-bills.php
   php create-raven-traffic-fines.php
   php create-dave-market-bills.php
   ```

2. **Individual citizen reset:**
   ```bash
   # Delete specific citizen's bills
   php delete-citizen-bills.php <citizen_id>

   # Regenerate for that citizen
   # Use appropriate script based on citizen:
   # - Raven business: php recreate-raven-business-bills.php
   # - Raven traffic: php create-raven-traffic-fines.php
   # - Dave: php create-dave-market-bills.php
   # - Vince: Bills auto-generate via rpt.php API
   ```

## Implementation Files

The following files were used to implement and test the bill allocation process. These can be reused for future allocations:

### Vince Nico Escala - RPT Bills
- **`api/endpoints/subsystem7.php`** - Mock S7 API with Vince's 5 properties (TDN-QC-2024-006 to 010)
- **`mock-data/subsystem7/properties.json`** - JSON data source for S7 properties
- **`test-subsystem7.php`** - Test script for S7 property API
- **`check-vince-rpt.php`** - Verification script for Vince's RPT bills

### Raven Pogi - Business Tax & Traffic Fines
- **`recreate-raven-business-bills.php`** - Generates 5 business tax bills via business-tax.php API
- **`create-raven-traffic-fines.php`** - Generates 5 traffic fine bills via subsystem9.php
- **`check-raven-bills.php`** - Verification script for all Raven's bills
- **`check-raven-business.php`** - Specific check for Raven's business tax bills
- **`delete-raven-specific-bills.php`** - Cleanup script for specific Raven bills

### Dave Mercado - Market Stall Bills
- **`create-dave-market-bills.php`** - Generates 5 market stall rental bills via market-stall.php API
- **`insert-dave-stalls.php`** - Sets up Dave's market stall occupancy data
- **`check-dave-stalls.php`** - Verification script for Dave's stall data
- **`check-dave-bills.php`** - Verification script for Dave's market bills

### Utility Scripts
- **`delete-pending-bills.php`** - Deletes all pending bills (clean slate)
- **`delete-all-pending-bills.php`** - Alternative clean slate script
- **`check-all-citizen-bills.php`** - Comprehensive bill verification for all citizens
- **`compare-amounts.php`** - Compares expected vs actual bill amounts

### Quick Re-run Commands

To recreate the same bill allocation setup:

```bash
# 1. Clean slate
php delete-pending-bills.php

# 2. Vince - RPT Bills (via S7 integration)
# Bills generated automatically through rpt.php API

# 3. Raven - Business Tax Bills
php recreate-raven-business-bills.php

# 4. Raven - Traffic Fines
php create-raven-traffic-fines.php

# 5. Dave - Market Stall Bills
php create-dave-market-bills.php

# 6. Verify all bills
php check-all-citizen-bills.php
```

## File References

- `api/endpoints/rpt.php` - RPT bill generation
- `api/endpoints/business-tax.php` - Business tax bills
- `api/endpoints/market-stall.php` - Market rental bills
- `mock-data/subsystem7/properties.json` - Vince's properties
- `mock-data/subsystem2/business-registry.json` - Raven's businesses
- `mock-data/subsystem9/traffic-violations.json` - Raven's violations
- `mock-data/subsystem10/stalls.json` - Dave's market stalls

---

**Last Updated**: March 15, 2026
**Version**: 1.2
**Cleanup Completed**: Obsolete files removed</content>
<parameter name="filePath">c:\xampp\htdocs\rcts-qc\PENDING-BILLS-ALLOCATION-PROCESS.md