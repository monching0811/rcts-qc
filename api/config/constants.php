<?php
/**
 * RCTS-QC CONSTANTS
 * ─────────────────
 * All tax rates, discount rates, penalty rates, and system-wide
 * settings in one place. Change these values here and the entire
 * system updates automatically — no hunting through code.
 */

// ── System Info ─────────────────────────────────────────────────────────────
define('SYSTEM_NAME',    'RCTS-QC');
define('SYSTEM_VERSION', '1.0.0');
define('LGU_NAME',       'Quezon City Government');
define('LGU_ADDRESS',    'Elliptical Road, Diliman, Quezon City');

// ── Real Property Tax (RPT) Rates ───────────────────────────────────────────
define('RPT_BASIC_RATE',        0.02);    // 2% of assessed value
define('RPT_SEF_RATE',          0.01);    // 1% Special Education Fund
define('RPT_TOTAL_RATE',        0.03);    // 3% total

// Assessment Levels by property class
define('ASSESS_LEVEL_RESIDENTIAL',  0.20);  // 20% of market value
define('ASSESS_LEVEL_COMMERCIAL',   0.50);  // 50% of market value
define('ASSESS_LEVEL_INDUSTRIAL',   0.50);
define('ASSESS_LEVEL_AGRICULTURAL', 0.40);
define('ASSESS_LEVEL_SPECIAL',      0.15);

// Early Bird Discount (Jan 1 – Mar 31)
define('RPT_EARLY_BIRD_RATE',   0.20);   // 20% discount
define('RPT_EARLY_BIRD_START',  '01-01');
define('RPT_EARLY_BIRD_END',    '03-31');

// Late Payment Penalty
define('RPT_LATE_PENALTY_RATE', 0.02);   // 2% per month surcharge

// ── Business Tax Rates ───────────────────────────────────────────────────────
// Based on QC Revenue Code — % of annual gross sales
define('BIZ_TAX_RATE_RESTAURANT',   0.015);  // 1.5% - Food & Beverage
define('BIZ_TAX_RATE_RETAIL',       0.01);   // 1.0% - Retail
define('BIZ_TAX_RATE_SERVICE',      0.02);   // 2.0% - Services
define('BIZ_TAX_RATE_MANUFACTURING',0.01);
define('BIZ_TAX_RATE_DEFAULT',      0.015);  // default if not categorized

// Regulatory Fees (flat annual fees)
define('FEE_SANITARY_PERMIT',       500.00);
define('FEE_GARBAGE_COLLECTION',    1000.00);
define('FEE_FIRE_INSPECTION',       750.00);
define('FEE_MAYORS_PERMIT',         2000.00);

// Senior Citizen / PWD Business Discount
define('BIZ_SENIOR_PWD_DISCOUNT',   0.20);  // 20%

// ── Market Stall Rates ───────────────────────────────────────────────────────
define('MARKET_LATE_PENALTY_RATE',  0.05);  // 5% per month if stall rent unpaid

// ── Traffic Violations ───────────────────────────────────────────────────────
define('TRAFFIC_GRACE_PERIOD_DAYS', 7);
define('TRAFFIC_LATE_RATE',         0.02);  // 2% per day after grace period

// ── QRF (Quick Response Fund) ────────────────────────────────────────────────
define('QRF_MINIMUM_PERCENT',       0.05);  // 5% of annual budget reserved

// ── Mock Subsystem API URLs (for local XAMPP development) ───────────────────
define('S1_API_URL', 'http://localhost/rcts-qc/mock-data/subsystem1/citizen-registry-api.php');
define('S7_API_URL', 'http://localhost/rcts-qc/api/endpoints/subsystem7.php');

// Production API URLs (fill in when real subsystems are online)
define('S2_API_URL', 'http://localhost/subsystem2/api/');   // Permits & Licensing
define('S3_API_URL', 'http://localhost/subsystem3/api/');   // Social Services
define('S4_API_URL', 'http://localhost/subsystem4/api/');   // Health & Sanitation
define('S5_API_URL', 'http://localhost/subsystem5/api/');   // Education
define('S6_API_URL', 'http://localhost/subsystem6/api/');   // DRRM
define('S9_API_URL', 'http://localhost/subsystem9/api/');   // Transport
define('S10_API_URL','http://localhost/subsystem10/api/');  // Public Assets

// ── Date Helpers ─────────────────────────────────────────────────────────────
define('CURRENT_YEAR',  (int)date('Y'));
define('CURRENT_MONTH', (int)date('m'));
define('CURRENT_DATE',  date('Y-m-d'));
