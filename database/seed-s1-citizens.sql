-- ============================================================
-- RCTS-QC: Seed Mock Data for S1 Citizens
-- ============================================================
-- This script adds the 4 Subsystem 1 citizens to RCTS registry
-- and creates mock bills so they have pending payments.
-- ============================================================

-- DROP existing tables first (to fix VARCHAR size issues)
DROP TABLE IF EXISTS rcts_assessment_billing_hub CASCADE;
DROP TABLE IF EXISTS rcts_public_asset_stall CASCADE;
DROP TABLE IF EXISTS rcts_business_entity CASCADE;
DROP TABLE IF EXISTS rcts_real_property CASCADE;
DROP TABLE IF EXISTS rcts_citizen_registry CASCADE;

-- STEP 1: Create RCTS Tables
-- ============================================================

-- Citizen Registry
CREATE TABLE IF NOT EXISTS rcts_citizen_registry (
    qcitizen_id         VARCHAR(50)     PRIMARY KEY,
    full_name           VARCHAR(150)    NOT NULL,
    date_of_birth       DATE,
    address             TEXT,
    email               VARCHAR(100)    UNIQUE NOT NULL,
    mobile_no           VARCHAR(15),
    biometric_token     VARCHAR(255),
    digital_wallet_link VARCHAR(100),
    is_senior_citizen   BOOLEAN         DEFAULT FALSE,
    is_pwd              BOOLEAN         DEFAULT FALSE,
    is_solo_parent      BOOLEAN         DEFAULT FALSE,
    role                VARCHAR(20)     DEFAULT 'citizen',
    status              VARCHAR(10)     DEFAULT 'active',
    created_at          TIMESTAMPTZ     DEFAULT NOW(),
    updated_at          TIMESTAMPTZ     DEFAULT NOW()
);

-- Real Property (RPT)
CREATE TABLE IF NOT EXISTS rcts_real_property (
    tdn_number              VARCHAR(30)     PRIMARY KEY,
    qcitizen_id             VARCHAR(50)     NOT NULL,
    property_class          VARCHAR(20)     NOT NULL,
    property_address        TEXT            NOT NULL,
    gis_coordinate_id       VARCHAR(50),
    land_area_sqm           DECIMAL(12,2)   NOT NULL,
    current_market_value    DECIMAL(15,2)   NOT NULL,
    assessed_value          DECIMAL(15,2)   NOT NULL,
    assessment_level        DECIMAL(5,4)    DEFAULT 0.2000,
    annual_tax_due          DECIMAL(15,2),
    zoning_status           VARCHAR(30)     DEFAULT 'Residential',
    assessed_value_update_flag BOOLEAN      DEFAULT FALSE,
    last_payment_year       INT,
    tax_clearance_status    VARCHAR(20)     DEFAULT 'Pending',
    created_at              TIMESTAMPTZ     DEFAULT NOW(),
    updated_at              TIMESTAMPTZ     DEFAULT NOW()
);

-- Business Entity
CREATE TABLE IF NOT EXISTS rcts_business_entity (
    bin_number              VARCHAR(20)     PRIMARY KEY,
    qcitizen_id             VARCHAR(50)     NOT NULL,
    business_name           VARCHAR(200)    NOT NULL,
    nature_of_business      VARCHAR(100)    NOT NULL,
    business_address        TEXT            NOT NULL,
    gross_sales_declared    DECIMAL(15,2)   DEFAULT 0.00,
    assessment_cycle        VARCHAR(15)     DEFAULT 'Annual',
    regulatory_clearance_id VARCHAR(30),
    permit_status           VARCHAR(20)     DEFAULT 'Pending',
    franchise_type          VARCHAR(30),
    is_puv_franchise        BOOLEAN         DEFAULT FALSE,
    created_at              TIMESTAMPTZ     DEFAULT NOW(),
    updated_at              TIMESTAMPTZ     DEFAULT NOW()
);

-- Assessment & Billing Hub
CREATE TABLE IF NOT EXISTS rcts_assessment_billing_hub (
    bill_reference_no       VARCHAR(30)     PRIMARY KEY,
    qcitizen_id             VARCHAR(50)     NOT NULL,
    bill_type               VARCHAR(30)     NOT NULL,
    originating_dept_id     INT             NOT NULL,
    asset_id                VARCHAR(30),
    tax_year                INT             NOT NULL,
    base_amount             DECIMAL(15,2)   NOT NULL,
    discount_rate           DECIMAL(5,4)    DEFAULT 0.0000,
    discount_amount         DECIMAL(15,2),
    penalty_rate            DECIMAL(5,4)    DEFAULT 0.0000,
    penalty_amount          DECIMAL(15,2),
    total_amount_due        DECIMAL(15,2)   NOT NULL,
    verification_ref_id     VARCHAR(30),
    status                  VARCHAR(20)     DEFAULT 'Pending',
    due_date                DATE,
    created_at              TIMESTAMPTZ     DEFAULT NOW(),
    updated_at              TIMESTAMPTZ     DEFAULT NOW()
);

-- Market Stalls
CREATE TABLE IF NOT EXISTS rcts_public_asset_stall (
    stall_asset_id              VARCHAR(30)     PRIMARY KEY,
    qcitizen_id                 VARCHAR(50),
    facility_location_id        VARCHAR(30)     NOT NULL,
    facility_name               VARCHAR(100)    NOT NULL,
    stall_number                VARCHAR(20)     NOT NULL,
    stall_type                  VARCHAR(30)     DEFAULT 'Market',
    area_sqm                    DECIMAL(8,2),
    monthly_rental_rate         DECIMAL(10,2)   NOT NULL,
    lease_start_date            DATE,
    lease_end_date              DATE,
    occupancy_status_flag       VARCHAR(12)     DEFAULT 'Vacant',
    occupancy_verification_method VARCHAR(20)   DEFAULT 'Manual',
    occupancy_last_verified     TIMESTAMPTZ,
    verification_source_subsystem INT           DEFAULT 10,
    created_at                  TIMESTAMPTZ     DEFAULT NOW(),
    updated_at                  TIMESTAMPTZ     DEFAULT NOW()
);

-- STEP 2: Insert S1 Citizens into RCTS Registry
-- ============================================================

-- Citizen 1: Vince Nico Escala
INSERT INTO rcts_citizen_registry (qcitizen_id, full_name, email, mobile_no, address, is_senior_citizen, is_pwd)
VALUES ('b529bf30-50bf-43ab-a314-cc4c2f79c3f5', 'Vince Nico Escala', 'escalavincenico28@gmail.com', '09123456789', 'Block 5, Commonwealth Avenue, Quezon City', false, false)
ON CONFLICT (qcitizen_id) DO NOTHING;

-- Citizen 2: Raven Pogi
INSERT INTO rcts_citizen_registry (qcitizen_id, full_name, email, mobile_no, address, is_senior_citizen, is_pwd)
VALUES ('92be37af-7c34-4c9b-80cb-47cde7c3a9fd', 'Raven Pogi', 'ravengutierrez2018@gmail.com', '09234567890', '15 Mindanao Avenue, Quezon City', false, false)
ON CONFLICT (qcitizen_id) DO NOTHING;

-- Citizen 3: vince nico
INSERT INTO rcts_citizen_registry (qcitizen_id, full_name, email, mobile_no, address, is_senior_citizen, is_pwd)
VALUES ('bcb37eaa-4b68-48a5-9110-0439c7a3865e', 'vince o nico', 'escalavincenico555@gmail.com', '091231456', '1919 Pk 19 Adarna Street, Commonwealth, Quezon City', false, false)
ON CONFLICT (qcitizen_id) DO NOTHING;

-- Citizen 4: Brylle Kenneth Mendez
INSERT INTO rcts_citizen_registry (qcitizen_id, full_name, email, mobile_no, address, is_senior_citizen, is_pwd)
VALUES ('a135da1e-6727-430e-9771-e15688e6f79e', 'Brylle Kenneth Mendez', 'bryllekennethmendez@gmail.com', '09876543210', 'Tandang Sora Avenue, Quezon City', false, false)
ON CONFLICT (qcitizen_id) DO NOTHING;

-- STEP 3: Add Real Properties (RPT) for Citizens
-- ============================================================

-- Vince Nico - Residential Property
INSERT INTO rcts_real_property (tdn_number, qcitizen_id, property_class, property_address, land_area_sqm, current_market_value, assessed_value, annual_tax_due)
VALUES ('TDN-QC-2024-001', 'b529bf30-50bf-43ab-a314-cc4c2f79c3f5', 'Residential', 'Lot 5 Block 5, Commonwealth Heights Subdivision, Quezon City', 150.00, 2500000.00, 500000.00, 10000.00)
ON CONFLICT (tdn_number) DO NOTHING;

-- Raven Pogi - Residential Property  
INSERT INTO rcts_real_property (tdn_number, qcitizen_id, property_class, property_address, land_area_sqm, current_market_value, assessed_value, annual_tax_due)
VALUES ('TDN-QC-2024-002', '92be37af-7c34-4c9b-80cb-47cde7c3a9fd', 'Residential', 'Unit 15B, Mindanao Towers, Quezon City', 80.00, 1800000.00, 360000.00, 7200.00)
ON CONFLICT (tdn_number) DO NOTHING;

-- vince nico - Commercial Property
INSERT INTO rcts_real_property (tdn_number, qcitizen_id, property_class, property_address, land_area_sqm, current_market_value, assessed_value, annual_tax_due)
VALUES ('TDN-QC-2024-003', 'bcb37eaa-4b68-48a5-9110-0439c7a3865e', 'Commercial', '1919 Pk 19 Adarna Street, Commercial Zone, Quezon City', 200.00, 5000000.00, 1000000.00, 25000.00)
ON CONFLICT (tdn_number) DO NOTHING;

-- STEP 4: Add Business Entities
-- ============================================================

-- vince nico - Has a restaurant business
INSERT INTO rcts_business_entity (bin_number, qcitizen_id, business_name, nature_of_business, business_address, gross_sales_declared, permit_status)
VALUES ('BIN-QC-2024-001', 'bcb37eaa-4b68-48a5-9110-0439c7a3865e', 'Nico''s Kainan', 'Restaurant/Fastfood', '1919 Pk 19 Adarna Street, Quezon City', 500000.00, 'Active')
ON CONFLICT (bin_number) DO NOTHING;

-- Raven Pogi - Has a retail store
INSERT INTO rcts_business_entity (bin_number, qcitizen_id, business_name, nature_of_business, business_address, gross_sales_declared, permit_status)
VALUES ('BIN-QC-2024-002', '92be37af-7c34-4c9b-80cb-47cde7c3a9fd', 'Pogi General Merchandise', 'Retail', '15 Mindanao Avenue, Quezon City', 250000.00, 'Active')
ON CONFLICT (bin_number) DO NOTHING;

-- STEP 5: Add Market Stalls (for vendors)
-- ============================================================

-- Vince Nico - Market Stall
INSERT INTO rcts_public_asset_stall (stall_asset_id, qcitizen_id, facility_location_id, facility_name, stall_number, stall_type, area_sqm, monthly_rental_rate, occupancy_status_flag)
VALUES ('STALL-QC-001', 'b529bf30-50bf-43ab-a314-cc4c2f79c3f5', 'NOV-MKT-001', 'Novaliches Public Market', 'A-012', 'Market', 6.0, 2500.00, 'Active')
ON CONFLICT (stall_asset_id) DO NOTHING;

-- STEP 6: Add Pending Bills (Assessment & Billing Hub)
-- ============================================================

-- Vince Nico - RPT Bill
INSERT INTO rcts_assessment_billing_hub (bill_reference_no, qcitizen_id, bill_type, originating_dept_id, asset_id, tax_year, base_amount, discount_rate, total_amount_due, status, due_date)
VALUES ('BILL-RPT-2024-001', 'b529bf30-50bf-43ab-a314-cc4c2f79c3f5', 'RPT', 7, 'TDN-QC-2024-001', 2024, 10000.00, 0.00, 10000.00, 'Pending', '2026-03-31')
ON CONFLICT (bill_reference_no) DO NOTHING;

-- Vince Nico - Market Rental Bill
INSERT INTO rcts_assessment_billing_hub (bill_reference_no, qcitizen_id, bill_type, originating_dept_id, asset_id, tax_year, base_amount, discount_rate, total_amount_due, status, due_date)
VALUES ('BILL-MKT-2024-001', 'b529bf30-50bf-43ab-a314-cc4c2f79c3f5', 'MarketRental', 10, 'STALL-QC-001', 2024, 2500.00, 0.00, 2500.00, 'Pending', '2026-03-15')
ON CONFLICT (bill_reference_no) DO NOTHING;

-- Raven Pogi - RPT Bill
INSERT INTO rcts_assessment_billing_hub (bill_reference_no, qcitizen_id, bill_type, originating_dept_id, asset_id, tax_year, base_amount, discount_rate, total_amount_due, status, due_date)
VALUES ('BILL-RPT-2024-002', '92be37af-7c34-4c9b-80cb-47cde7c3a9fd', 'RPT', 7, 'TDN-QC-2024-002', 2024, 7200.00, 0.00, 7200.00, 'Pending', '2026-03-31')
ON CONFLICT (bill_reference_no) DO NOTHING;

-- Raven Pogi - Business Tax Bill
INSERT INTO rcts_assessment_billing_hub (bill_reference_no, qcitizen_id, bill_type, originating_dept_id, asset_id, tax_year, base_amount, discount_rate, total_amount_due, status, due_date)
VALUES ('BILL-BIZ-2024-001', '92be37af-7c34-4c9b-80cb-47cde7c3a9fd', 'BusinessTax', 2, 'BIN-QC-2024-002', 2024, 12500.00, 0.00, 12500.00, 'Pending', '2026-01-31')
ON CONFLICT (bill_reference_no) DO NOTHING;

-- vince nico - RPT Bill (Commercial - higher tax)
INSERT INTO rcts_assessment_billing_hub (bill_reference_no, qcitizen_id, bill_type, originating_dept_id, asset_id, tax_year, base_amount, discount_rate, total_amount_due, status, due_date)
VALUES ('BILL-RPT-2024-003', 'bcb37eaa-4b68-48a5-9110-0439c7a3865e', 'RPT', 7, 'TDN-QC-2024-003', 2024, 25000.00, 0.00, 25000.00, 'Pending', '2026-03-31')
ON CONFLICT (bill_reference_no) DO NOTHING;

-- vince nico - Business Tax Bill (Restaurant)
INSERT INTO rcts_assessment_billing_hub (bill_reference_no, qcitizen_id, bill_type, originating_dept_id, asset_id, tax_year, base_amount, discount_rate, total_amount_due, status, due_date)
VALUES ('BILL-BIZ-2024-002', 'bcb37eaa-4b68-48a5-9110-0439c7a3865e', 'BusinessTax', 2, 'BIN-QC-2024-001', 2024, 50000.00, 0.00, 50000.00, 'Pending', '2026-01-31')
ON CONFLICT (bill_reference_no) DO NOTHING;

-- Brylle Kenneth Mendez - RPT Bill (Senior Citizen - 20% discount!)
INSERT INTO rcts_assessment_billing_hub (bill_reference_no, qcitizen_id, bill_type, originating_dept_id, asset_id, tax_year, base_amount, discount_rate, total_amount_due, status, due_date)
VALUES ('BILL-RPT-2024-004', 'a135da1e-6727-430e-9771-e15688e6f79e', 'RPT', 7, NULL, 2024, 8000.00, 0.20, 6400.00, 'Pending', '2026-03-31')
ON CONFLICT (bill_reference_no) DO NOTHING;

-- STEP 7: Verify Data
-- ============================================================
SELECT 'Citizens Registered: ' || COUNT(*) AS status FROM rcts_citizen_registry;
SELECT 'Properties: ' || COUNT(*) AS status FROM rcts_real_property;
SELECT 'Businesses: ' || COUNT(*) AS status FROM rcts_business_entity;
SELECT 'Stalls: ' || COUNT(*) AS status FROM rcts_public_asset_stall;
SELECT 'Pending Bills: ' || COUNT(*) AS status FROM rcts_assessment_billing_hub WHERE status = 'Pending';

-- STEP 8: Create Views for API
-- ============================================================
CREATE OR REPLACE VIEW v_citizen_pending_bills AS
SELECT b.bill_reference_no, b.qcitizen_id, c.full_name, b.bill_type,
       b.tax_year, b.base_amount, b.discount_amount, b.penalty_amount,
       b.total_amount_due, b.due_date, b.status, b.created_at
FROM rcts_assessment_billing_hub b
JOIN rcts_citizen_registry c ON b.qcitizen_id = c.qcitizen_id
WHERE b.status = 'Pending'
ORDER BY b.created_at DESC;

-- Verify Views
SELECT 'View v_citizen_pending_bills created!' AS status;
