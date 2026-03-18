-- ============================================================
-- RCTS-QC: Seed Data for Testing & Demo
-- Run AFTER schema.sql in Supabase SQL Editor
-- ============================================================

-- ============================================================
-- TABLE 1: Citizens
-- ============================================================
-- First, clean up any existing test data (delete in correct order due to foreign keys)
DELETE FROM rcts_eor WHERE transaction_id LIKE 'TXN-%';
DELETE FROM rcts_payment_transaction WHERE bill_reference_no LIKE '%RPT%' OR bill_reference_no LIKE '%BIZ%' OR bill_reference_no LIKE '%MKT%' OR bill_reference_no LIKE '%TRF%';
DELETE FROM rcts_assessment_billing_hub WHERE qcitizen_id LIKE 'QC-2024-%';
DELETE FROM rcts_aid_payout_registry WHERE qcitizen_id LIKE 'QC-2024-%';
DELETE FROM rcts_regulatory_clearance WHERE qcitizen_id LIKE 'QC-2024-%';
DELETE FROM rcts_public_asset_stall WHERE qcitizen_id LIKE 'QC-2024-%';
DELETE FROM rcts_business_entity WHERE qcitizen_id LIKE 'QC-2024-%';
DELETE FROM rcts_traffic_violation WHERE qcitizen_id LIKE 'QC-2024-%';
DELETE FROM rcts_real_property WHERE qcitizen_id LIKE 'QC-2024-%';
-- Clean up existing test accounts by email
DELETE FROM rcts_citizen_registry WHERE email IN ('juan.delacruz@email.com', 'maria.santos@email.com', 'pedro.reyes@email.com', 'ana.bautista@email.com', 'roberto.lim@email.com', 'cynthia.flores@email.com', 'emman.garcia@email.com', 'treasurer@qc.gov.ph', 'auditor@qc.gov.ph');
DELETE FROM rcts_citizen_registry WHERE qcitizen_id LIKE 'QC-2024-%';

INSERT INTO rcts_citizen_registry (qcitizen_id, full_name, date_of_birth, address, email, mobile_no, digital_wallet_link, is_senior_citizen, is_pwd, is_solo_parent, role, status) VALUES
('QC-2024-000001','Juan Dela Cruz',    '1985-03-12','123 Maharlika St., Cubao, QC',         'juan.delacruz@email.com',  '09171234567','GCASH-09171234567', false, false, false, 'citizen', 'active'),
('QC-2024-000002','Maria Santos',      '1952-07-20','456 Sampaguita Ave., Novaliches, QC',  'maria.santos@email.com',   '09271234568','GCASH-09271234568', true,  false, false, 'citizen', 'active'),
('QC-2024-000003','Pedro Reyes',       '1978-11-05','789 Bagumbayan Rd., Fairview, QC',     'pedro.reyes@email.com',    '09181234569','MAYA-09181234569',  false, true,  false, 'citizen', 'active'),
('QC-2024-000004','Ana Bautista',      '1990-02-28','12 Kalikasan St., Tandang Sora, QC',  'ana.bautista@email.com',   '09271234570','GCASH-09271234570', false, false, true,  'citizen', 'active'),
('QC-2024-000005','Roberto Lim',       '1968-09-17','34 Mabini St., Cubao, QC',            'roberto.lim@email.com',    '09171234571','BPI-001234567',     false, false, false, 'citizen', 'active'),
('QC-2024-000006','Cynthia Flores',    '1950-04-10','56 Katipunan Ave., Diliman, QC',      'cynthia.flores@email.com', '09281234572','GCASH-09281234572', true,  false, false, 'citizen', 'active'),
('QC-2024-000007','Emmanuel Garcia',   '1995-06-22','78 Aurora Blvd., Cubao, QC',          'emman.garcia@email.com',   '09191234573','MAYA-09191234573',  false, false, false, 'citizen', 'active'),
('QC-2024-000008','Treasurer Account', '1980-01-01','City Hall, Elliptical Rd., Diliman', 'treasurer@qc.gov.ph',      '09171110001','LBP-00000001',      false, false, false, 'treasurer', 'active'),
('QC-2024-000009','COA Auditor',       '1980-01-01','COA Regional Office, Diliman, QC',   'auditor@qc.gov.ph',        '09171110002',null,                  false, false, false, 'auditor', 'active');

-- ============================================================
-- TABLE 1.5: Real Property (for RPT bills)
-- ============================================================
INSERT INTO rcts_real_property (tdn_number, qcitizen_id, property_class, property_address, land_area_sqm, current_market_value, assessed_value, zoning_status) VALUES
('TDN-QC-2024-001','QC-2024-000001','Residential','123 Maharlika St., Cubao, QC',    120.00, 2400000.00, 480000.00, 'Residential'),
('TDN-QC-2024-002','QC-2024-000002','Residential','456 Sampaguita Ave., Novaliches, QC', 150.00, 3000000.00, 600000.00, 'Residential'),
('TDN-QC-2024-003','QC-2024-000003','Residential','789 Bagumbayan Rd., Fairview, QC', 200.00, 4000000.00, 800000.00, 'Residential'),
('TDN-QC-2024-004','QC-2024-000004','Commercial', '12 Kalikasan St., Tandang Sora, QC', 80.00, 3200000.00, 960000.00, 'Commercial')
ON CONFLICT (tdn_number) DO NOTHING;

-- ============================================================
-- TABLE 1.6: Traffic Violations (for traffic fines)
-- ============================================================
INSERT INTO rcts_traffic_violation (violation_ticket_id, qcitizen_id, vehicle_plate_no, violation_type, fine_amount, apprehension_date, total_amount_due, payment_status) VALUES
('TKT-20250115-001','QC-2024-000001','ABC-123', 'No Parking', 2000.00, CURRENT_DATE - INTERVAL '5 days', 2000.00, 'Unpaid'),
('TKT-20250116-001','QC-2024-000003','XYZ-789', 'Overtaking', 500.00, CURRENT_DATE - INTERVAL '4 days', 500.00, 'Unpaid'),
('TKT-20250116-002','QC-2024-000006','DEF-456', 'Speeding', 5000.00, CURRENT_DATE - INTERVAL '4 days', 5000.00, 'Unpaid')
ON CONFLICT (violation_ticket_id) DO NOTHING;

-- ============================================================
-- TABLE 2: Business Entities
-- ============================================================
INSERT INTO rcts_business_entity (bin_number, qcitizen_id, business_name, nature_of_business, business_address, gross_sales_declared, permit_status, assessment_cycle) VALUES
('BIN-QC-2024-001','QC-2024-000001','Dela Cruz Sari-Sari Store', 'Retail/Sari-Sari',     '123 Maharlika St., Cubao, QC',         125000.00,'Active',  'Annual'),
('BIN-QC-2024-002','QC-2024-000002','Santos Bakery',             'Food Manufacturing',    '456 Sampaguita Ave., Novaliches, QC',  450000.00,'Active',  'Annual'),
('BIN-QC-2024-003','QC-2024-000005','Lim Hardware & Construction','Retail/Hardware',      '34 Mabini St., Cubao, QC',            2300000.00,'Active',  'Annual'),
('BIN-QC-2024-004','QC-2024-000007','Garcia Repair Shop',        'Services/Repair',       '78 Aurora Blvd., Cubao, QC',           98000.00,'Pending', 'Annual')
ON CONFLICT (bin_number) DO NOTHING;

-- ============================================================
-- TABLE 3: Business Clearances (from S4)
-- ============================================================
INSERT INTO rcts_regulatory_clearance (clearance_ref_id, qcitizen_id, business_bin, clearance_type, inspection_date, valid_until, status_flag, inspector_name, source_subsystem_id) VALUES
('CLR-HLT-2024-001','QC-2024-000001','BIN-QC-2024-001','Health',    CURRENT_DATE - INTERVAL '15 days', DATE_TRUNC('year', CURRENT_DATE) + INTERVAL '1 year - 1 day', 'Passed', 'QC Health Dept', 4),
('CLR-SAN-2024-001','QC-2024-000001','BIN-QC-2024-001','Sanitary',  CURRENT_DATE - INTERVAL '15 days', DATE_TRUNC('year', CURRENT_DATE) + INTERVAL '1 year - 1 day', 'Passed', 'QC Sanitation Office', 4),
('CLR-FIR-2024-001','QC-2024-000001','BIN-QC-2024-001','Fire',      CURRENT_DATE + INTERVAL '7 days',  NULL, 'Pending','BFP-QC', 4),
('CLR-HLT-2024-002','QC-2024-000002','BIN-QC-2024-002','Health',    CURRENT_DATE - INTERVAL '7 days',  DATE_TRUNC('year', CURRENT_DATE) + INTERVAL '1 year - 1 day', 'Passed', 'QC Health Dept', 4),
('CLR-SAN-2024-002','QC-2024-000002','BIN-QC-2024-002','Sanitary',  CURRENT_DATE - INTERVAL '7 days',  DATE_TRUNC('year', CURRENT_DATE) + INTERVAL '1 year - 1 day', 'Passed', 'QC Sanitation Office', 4),
('CLR-FIR-2024-002','QC-2024-000002','BIN-QC-2024-002','Fire',      CURRENT_DATE - INTERVAL '7 days',  DATE_TRUNC('year', CURRENT_DATE) + INTERVAL '1 year - 1 day', 'Passed', 'BFP-QC', 4),
('CLR-HLT-2024-003','QC-2024-000005','BIN-QC-2024-003','Health',    CURRENT_DATE - INTERVAL '30 days', DATE_TRUNC('year', CURRENT_DATE) + INTERVAL '1 year - 1 day', 'Passed', 'QC Health Dept', 4),
('CLR-SAN-2024-003','QC-2024-000005','BIN-QC-2024-003','Sanitary',  CURRENT_DATE - INTERVAL '30 days', DATE_TRUNC('year', CURRENT_DATE) + INTERVAL '1 year - 1 day', 'Passed', 'QC Sanitation Office', 4),
('CLR-FIR-2024-003','QC-2024-000005','BIN-QC-2024-003','Fire',      CURRENT_DATE - INTERVAL '30 days', DATE_TRUNC('year', CURRENT_DATE) + INTERVAL '1 year - 1 day', 'Passed', 'BFP-QC', 4)
ON CONFLICT DO NOTHING;

-- ============================================================
-- TABLE 4: Market Stalls (from S10)
-- ============================================================
INSERT INTO rcts_public_asset_stall (stall_asset_id, qcitizen_id, facility_location_id, facility_name, stall_number, stall_type, monthly_rental_rate, occupancy_status_flag, occupancy_verification_method, occupancy_last_verified, verification_source_subsystem) VALUES
('STL-QC-2024-001','QC-2024-000001','LOC-NOV-001','Novaliches Public Market', 'A-01','Market', 1500.00,'Active',      'IoT',       NOW() - INTERVAL '2 hours', 10),
('STL-QC-2024-002','QC-2024-000002','LOC-NOV-001','Novaliches Public Market', 'B-05','Market', 1800.00,'Active',      'QR',        NOW() - INTERVAL '1 day', 10),
('STL-QC-2024-003','QC-2024-000003','LOC-FVW-001','Fairview Market Center',   'C-10','Market', 2000.00,'Active',      'MobileApp', NOW() - INTERVAL '3 hours', 10),
('STL-QC-2024-004',NULL,             'LOC-CUB-001','Cubao Market',             'D-02','Market', 2500.00,'Vacant',      'IoT',       NOW() - INTERVAL '6 hours', 10),
('STL-QC-2024-005','QC-2024-000006','LOC-CUB-001','Cubao Market',             'E-07','Market', 2200.00,'Active',      'IoT',       NOW() - INTERVAL '4 hours', 10),
('STL-QC-2024-006',NULL,             'LOC-FVW-001','Fairview Market Center',   'F-03','Market', 1900.00,'UnderRepair', 'Manual',    NOW() - INTERVAL '1 day', 10)
ON CONFLICT (stall_asset_id) DO UPDATE SET
  occupancy_status_flag        = EXCLUDED.occupancy_status_flag,
  occupancy_last_verified      = EXCLUDED.occupancy_last_verified;

-- ============================================================
-- TABLE 5: Assessment/Billing Hub — RPT Bills
-- ============================================================
INSERT INTO rcts_assessment_billing_hub (bill_reference_no, qcitizen_id, asset_id, bill_type, originating_dept_id, tax_year, base_amount, discount_rate, penalty_rate, due_date, status) VALUES
('RPT-2025-QC001-001','QC-2024-000001','TDN-QC-2024-001','RPT',          1, 2025, 4800.00, 0.0000, 0.0000, DATE_TRUNC('year', CURRENT_DATE) + INTERVAL '3 months - 1 day', 'Pending'),
('RPT-2025-QC002-001','QC-2024-000002','TDN-QC-2024-002','RPT',          1, 2025, 6000.00, 0.2000, 0.0000, DATE_TRUNC('year', CURRENT_DATE) + INTERVAL '3 months - 1 day', 'Pending'),
('RPT-2025-QC003-001','QC-2024-000003','TDN-QC-2024-003','RPT',          1, 2025, 9600.00, 0.2000, 0.0000, DATE_TRUNC('year', CURRENT_DATE) + INTERVAL '3 months - 1 day', 'Pending'),
('RPT-2024-QC001-001','QC-2024-000001','TDN-QC-2024-001','RPT',          1, 2024, 4600.00, 0.0000, 0.0500, DATE_TRUNC('year', CURRENT_DATE - INTERVAL '1 year') + INTERVAL '3 months - 1 day', 'Pending'),
('BIZ-2025-QC001-001','QC-2024-000001','BIN-QC-2024-001','BusinessTax',  2, 2025, 1875.00, 0.0000, 0.0000, DATE_TRUNC('year', CURRENT_DATE) + INTERVAL '1 month - 1 day',  'Pending'),
('BIZ-2025-QC002-001','QC-2024-000002','BIN-QC-2024-002','BusinessTax',  2, 2025, 6750.00, 0.2000, 0.0000, DATE_TRUNC('year', CURRENT_DATE) + INTERVAL '1 month - 1 day',  'Pending'),
('BIZ-2025-QC003-001','QC-2024-000005','BIN-QC-2024-003','BusinessTax',  2, 2025,34500.00, 0.0000, 0.0000, DATE_TRUNC('year', CURRENT_DATE) + INTERVAL '1 month - 1 day',  'Pending'),
('MKT-2025-QC001-001','QC-2024-000001','STL-QC-2024-001','MarketRental',10, 2025, 1500.00, 0.0000, 0.0000, CURRENT_DATE + INTERVAL '7 days',  'Pending'),
('MKT-2025-QC002-001','QC-2024-000002','STL-QC-2024-002','MarketRental',10, 2025, 1800.00, 0.2000, 0.0000, CURRENT_DATE + INTERVAL '7 days',  'Pending'),
('MKT-2025-QC003-001','QC-2024-000003','STL-QC-2024-003','MarketRental',10, 2025, 2000.00, 0.2000, 0.0000, CURRENT_DATE + INTERVAL '7 days',  'Pending'),
('MKT-2024-QC001-001','QC-2024-000001','STL-QC-2024-001','MarketRental',10, 2024, 1500.00, 0.0000, 0.0500, CURRENT_DATE - INTERVAL '23 days', 'Pending'),
('TRF-2025-QC001-001','QC-2024-000001','TKT-20250115-001','TrafficFine', 9, 2025, 2000.00, 0.0000, 0.0000, CURRENT_DATE + INTERVAL '15 days', 'Pending'),
('TRF-2025-QC003-001','QC-2024-000003','TKT-20250116-001','TrafficFine', 9, 2025,  500.00, 0.0000, 0.0000, CURRENT_DATE + INTERVAL '15 days', 'Pending'),
('TRF-2025-QC006-001','QC-2024-000006','TKT-20250116-002','TrafficFine', 9, 2025, 5000.00, 0.0000, 0.0000, CURRENT_DATE + INTERVAL '15 days', 'Pending'),
('RPT-2025-QC004-001','QC-2024-000004','TDN-QC-2024-004','RPT',          1, 2025, 7200.00, 0.0000, 0.0000, DATE_TRUNC('year', CURRENT_DATE) + INTERVAL '3 months - 1 day', 'Pending')
ON CONFLICT (bill_reference_no) DO NOTHING;

-- ============================================================
-- TABLE 6: Disbursement Queue
-- ============================================================
INSERT INTO rcts_aid_payout_registry (disbursement_ref_id, qcitizen_id, originating_dept_id, program_id, program_name, approved_amount, disbursement_method, priority_flag, status, scheduled_date) VALUES
('DISB-AICS-2025-001','QC-2024-000001',3,'AICS-2025-001',  'AICS Emergency Cash Assistance',  3000.00,'DigitalWallet', 'Normal',    'Scheduled', CURRENT_DATE),
('DISB-AICS-2025-002','QC-2024-000002',3,'AICS-2025-001',  'AICS Emergency Cash Assistance',  3000.00,'BankTransfer', 'Normal',    'Scheduled', CURRENT_DATE),
('DISB-LAG-2025-001', 'QC-2024-000003',3,'LAG-2025-003',   'Livelihood Assistance Grant',      5000.00,'DigitalWallet', 'Normal',    'Scheduled', CURRENT_DATE + INTERVAL '3 days'),
('DISB-QCS-2025-001', 'QC-2024-000001',5,'QCS-2025-BATCH1','QC-Iskolar Stipend',              6000.00,'DigitalWallet', 'Normal',    'Scheduled', CURRENT_DATE),
('DISB-QCS-2025-002', 'QC-2024-000002',5,'QCS-2025-BATCH1','QC-Iskolar Stipend',              6000.00,'BankTransfer', 'Normal',    'Scheduled', CURRENT_DATE),
('DISB-QCS-2025-003', 'QC-2024-000003',5,'QCS-2025-BATCH1','QC-Iskolar Stipend',              6000.00,'DigitalWallet', 'Normal',    'Scheduled', CURRENT_DATE),
('DISB-AICS-2025-002','QC-2024-000003',3,'AICS-2025-002',  'AICS Senior Allowance',           4000.00,'DigitalWallet', 'Normal',    'Scheduled', CURRENT_DATE + INTERVAL '2 days'),
('DISB-QCS-2025-004', 'QC-2024-000004',5,'QCS-2025-BATCH2','QC-Iskolar Stipend Round 2',     5500.00,'DigitalWallet', 'Normal',    'Scheduled', CURRENT_DATE + INTERVAL '1 day'),
('DISB-DRRM-2025-001','QC-2024-000001',6,'DRRM-2025-001',  'Typhoon Quezon Relief',           5000.00,'DigitalWallet', 'Emergency', 'Scheduled', CURRENT_DATE),
('DISB-DRRM-2025-002','QC-2024-000002',6,'DRRM-2025-001',  'Typhoon Quezon Relief',           5000.00,'BankTransfer', 'Emergency', 'Scheduled', CURRENT_DATE),
('DISB-DRRM-2025-003','QC-2024-000003',6,'DRRM-2025-002',  'Earthquake Marikina Aid',         6000.00,'DigitalWallet', 'Emergency', 'Scheduled', CURRENT_DATE + INTERVAL '3 days'),
('DISB-DRRM-2025-004','QC-2024-000004',6,'DRRM-2025-002',  'Earthquake Marikina Aid',         6000.00,'BankTransfer', 'Emergency', 'Scheduled', CURRENT_DATE + INTERVAL '3 days')
ON CONFLICT DO NOTHING;

-- ============================================================
-- TABLE 7: Treasury Ledger — seed with realistic entries
-- ============================================================
INSERT INTO rcts_treasury_ledger (entry_type, revenue_category, fund_id, gl_account_code, amount, entry_timestamp, remarks) VALUES
('Credit','RPT',          'GeneralFund','4-001-001', 48000.00, NOW() - INTERVAL '5 days',  'Batch RPT collection Jan 2025'),
('Credit','RPT',          'SEF',        '4-002-001', 16000.00, NOW() - INTERVAL '5 days',  'SEF component Jan 2025'),
('Credit','BusinessTax',  'GeneralFund','4-001-002', 41775.00, NOW() - INTERVAL '4 days',  'Unified OP batch Jan 2025'),
('Credit','MarketRental', 'GeneralFund','4-001-003',  7315.00, NOW() - INTERVAL '3 days',  'Market rental batch Jan 2025'),
('Credit','TrafficFine',  'GeneralFund','4-001-004',  7500.00, NOW() - INTERVAL '2 days',  'Traffic violation fines batch'),
('Debit', 'SocialAidDisbursement','GeneralFund','5-001-001', 9000.00, NOW() - INTERVAL '2 days',  'AICS payout batch Jan 2025'),
('Debit', 'ScholarshipDisbursement','SEF',       '5-002-001',18000.00, NOW() - INTERVAL '1 day',   'QC-Iskolar stipend Jan 2025'),
('Credit','RPT',          'GeneralFund','4-001-001',  4800.00, NOW() - INTERVAL '6 hours', 'RPT payment — Juan Dela Cruz'),
('Credit','BusinessTax',  'GeneralFund','4-001-002',  1875.00, NOW() - INTERVAL '5 hours', 'Business tax — Dela Cruz Store'),
('Credit','TrafficFine',  'GeneralFund','4-001-004',  2000.00, NOW() - INTERVAL '4 hours', 'Traffic fine TKT-20250115-001'),
('Credit','MarketRental', 'GeneralFund','4-001-003',  1500.00, NOW() - INTERVAL '3 hours', 'Market stall rental STL-QC-2024-001'),
('Credit','RPT',          'SEF',        '4-002-001',  2400.00, NOW() - INTERVAL '2 hours', 'SEF component — Dela Cruz RPT'),
('Debit', 'DRRMDisbursement','DRRM_QRF','5-003-001', 10000.00, NOW() - INTERVAL '1 hour',  'Typhoon Quezon QRF payout batch 1')
ON CONFLICT DO NOTHING;

-- ============================================================
-- TABLE 8: Dashboard snapshot
-- ============================================================
INSERT INTO rcts_treasury_dashboard (total_collection_mtd, total_rpt_collected, total_business_tax, total_market_rental, total_fines_collected, total_disbursed_mtd, revenue_target, qrf_balance, qrf_status, delinquency_count, pending_disbursements, liquidity_stress_result) VALUES
(
  120490.00,
  70400.00,
  41775.00,
  10315.00,
  9500.00,
  37000.00,
  150000.00,
  8765432.00,
  'Locked',
  5,
  49000.00,
  'OK'
) ON CONFLICT DO NOTHING;