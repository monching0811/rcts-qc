-- ============================================================
-- RCTS-QC: Revenue Collection & Treasury Services
-- Quezon City Government Service Management System
-- Department 8 — TO-BE Database Schema
-- ============================================================
-- HOW TO USE:
-- 1. Go to your Supabase project dashboard
-- 2. Click "SQL Editor" on the left sidebar
-- 3. Paste this entire file and click "Run"
-- ============================================================

-- ============================================================
-- STEP 1: CLEAN SLATE (Drop tables if re-running)
-- ============================================================
DROP TABLE IF EXISTS rcts_treasury_dashboard        CASCADE;
DROP TABLE IF EXISTS rcts_aid_payout_registry       CASCADE;
DROP TABLE IF EXISTS rcts_traffic_violation         CASCADE;
DROP TABLE IF EXISTS rcts_regulatory_clearance      CASCADE;
DROP TABLE IF EXISTS rcts_public_asset_stall        CASCADE;
DROP TABLE IF EXISTS rcts_eor                       CASCADE;
DROP TABLE IF EXISTS rcts_treasury_ledger           CASCADE;
DROP TABLE IF EXISTS rcts_payment_transaction       CASCADE;
DROP TABLE IF EXISTS rcts_assessment_billing_hub    CASCADE;
DROP TABLE IF EXISTS rcts_business_entity           CASCADE;
DROP TABLE IF EXISTS rcts_real_property             CASCADE;
DROP TABLE IF EXISTS rcts_citizen_registry          CASCADE;

-- ============================================================
-- TABLE 1: QCitizen Master Registry
-- Source: Subsystem 1 (Mock Data)
-- ============================================================
CREATE TABLE rcts_citizen_registry (
    qcitizen_id         VARCHAR(20)     PRIMARY KEY,
    full_name           VARCHAR(150)    NOT NULL,
    date_of_birth       DATE            NOT NULL,
    address             TEXT            NOT NULL,
    email               VARCHAR(100)    UNIQUE NOT NULL,
    mobile_no           VARCHAR(15)     NOT NULL,
    biometric_token     VARCHAR(255),
    digital_wallet_link VARCHAR(100),
    is_senior_citizen   BOOLEAN         DEFAULT FALSE,
    is_pwd              BOOLEAN         DEFAULT FALSE,
    is_solo_parent      BOOLEAN         DEFAULT FALSE,
    role                VARCHAR(20)     DEFAULT 'citizen'
        CHECK (role IN ('citizen','treasurer','revenue_officer','auditor','admin')),
    status              VARCHAR(10)     DEFAULT 'active'
        CHECK (status IN ('active','inactive','suspended')),
    created_at          TIMESTAMPTZ     DEFAULT NOW(),
    updated_at          TIMESTAMPTZ     DEFAULT NOW()
);

-- ============================================================
-- TABLE 2: Integrated Real Property (Subsystem 7)
-- ============================================================
CREATE TABLE rcts_real_property (
    tdn_number              VARCHAR(30)     PRIMARY KEY,
    qcitizen_id             VARCHAR(20)     NOT NULL
        REFERENCES rcts_citizen_registry(qcitizen_id) ON DELETE RESTRICT,
    property_class          VARCHAR(20)     NOT NULL
        CHECK (property_class IN ('Residential','Commercial','Industrial','Agricultural','Special')),
    property_address        TEXT            NOT NULL,
    gis_coordinate_id       VARCHAR(50),
    land_area_sqm           DECIMAL(12,2)   NOT NULL,
    current_market_value    DECIMAL(15,2)   NOT NULL,
    assessed_value          DECIMAL(15,2)   NOT NULL,
    assessment_level        DECIMAL(5,4)    DEFAULT 0.2000,
    annual_tax_due          DECIMAL(15,2)   GENERATED ALWAYS AS (assessed_value * 0.02) STORED,
    zoning_status           VARCHAR(30)     DEFAULT 'Residential',
    assessed_value_update_flag BOOLEAN      DEFAULT FALSE,
    last_payment_year       INT,
    tax_clearance_status    VARCHAR(20)     DEFAULT 'Pending'
        CHECK (tax_clearance_status IN ('Cleared','Pending','Delinquent')),
    created_at              TIMESTAMPTZ     DEFAULT NOW(),
    updated_at              TIMESTAMPTZ     DEFAULT NOW()
);

-- ============================================================
-- TABLE 3: Business & Regulatory Entity (Subsystem 2)
-- ============================================================
CREATE TABLE rcts_business_entity (
    bin_number              VARCHAR(20)     PRIMARY KEY,
    qcitizen_id             VARCHAR(20)     NOT NULL
        REFERENCES rcts_citizen_registry(qcitizen_id) ON DELETE RESTRICT,
    business_name           VARCHAR(200)    NOT NULL,
    nature_of_business      VARCHAR(100)    NOT NULL,
    business_address        TEXT            NOT NULL,
    gross_sales_declared    DECIMAL(15,2)   DEFAULT 0.00,
    assessment_cycle        VARCHAR(15)     DEFAULT 'Annual'
        CHECK (assessment_cycle IN ('Annual','Quarterly')),
    regulatory_clearance_id VARCHAR(30),
    permit_status           VARCHAR(20)     DEFAULT 'Pending'
        CHECK (permit_status IN ('Active','Pending','Expired','Revoked')),
    franchise_type          VARCHAR(30),
    is_puv_franchise        BOOLEAN         DEFAULT FALSE,
    created_at              TIMESTAMPTZ     DEFAULT NOW(),
    updated_at              TIMESTAMPTZ     DEFAULT NOW()
);

-- ============================================================
-- TABLE 4: Assessment & Billing Hub (CORE ENGINE)
-- ============================================================
CREATE TABLE rcts_assessment_billing_hub (
    bill_reference_no       VARCHAR(30)     PRIMARY KEY,
    qcitizen_id             VARCHAR(20)     NOT NULL
        REFERENCES rcts_citizen_registry(qcitizen_id) ON DELETE RESTRICT,
    bill_type               VARCHAR(30)     NOT NULL
        CHECK (bill_type IN ('RPT','BusinessTax','MarketRental','TrafficFine','FacilityFee','FranchiseFee')),
    originating_dept_id     INT             NOT NULL,
    asset_id                VARCHAR(30),
    tax_year                INT             NOT NULL DEFAULT EXTRACT(YEAR FROM NOW())::INT,
    base_amount             DECIMAL(15,2)   NOT NULL,
    discount_rate           DECIMAL(5,4)    DEFAULT 0.0000,
    discount_amount         DECIMAL(15,2)   GENERATED ALWAYS AS (base_amount * discount_rate) STORED,
    penalty_rate            DECIMAL(5,4)    DEFAULT 0.0000,
    penalty_amount          DECIMAL(15,2)   GENERATED ALWAYS AS (base_amount * penalty_rate) STORED,
    total_amount_due        DECIMAL(15,2)   GENERATED ALWAYS AS
                                (base_amount - (base_amount * discount_rate) + (base_amount * penalty_rate)) STORED,
    verification_ref_id     VARCHAR(30),
    status                  VARCHAR(20)     DEFAULT 'Pending'
        CHECK (status IN ('Pending','Paid','Delinquent','Cancelled','Waived')),
    due_date                DATE,
    created_at              TIMESTAMPTZ     DEFAULT NOW(),
    updated_at              TIMESTAMPTZ     DEFAULT NOW()
);

-- ============================================================
-- TABLE 5: Payment Transaction (QC-PAY)
-- ============================================================
CREATE TABLE rcts_payment_transaction (
    transaction_id          VARCHAR(30)     PRIMARY KEY,
    bill_reference_no       VARCHAR(30)     NOT NULL
        REFERENCES rcts_assessment_billing_hub(bill_reference_no) ON DELETE RESTRICT,
    qcitizen_id             VARCHAR(20)     NOT NULL
        REFERENCES rcts_citizen_registry(qcitizen_id) ON DELETE RESTRICT,
    gateway_provider        VARCHAR(30)     NOT NULL
        CHECK (gateway_provider IN ('GCash','Maya','Landbank','BPI','Metrobank','CreditCard','Cash','Debit')),
    transaction_timestamp   TIMESTAMPTZ     DEFAULT NOW(),
    amount_settled          DECIMAL(15,2)   NOT NULL,
    digital_hash            VARCHAR(255),
    bank_reference_no       VARCHAR(50),
    transaction_status      VARCHAR(20)     DEFAULT 'Pending'
        CHECK (transaction_status IN ('Pending','Success','Failed','Refunded')),
    settlement_loop_sent    BOOLEAN         DEFAULT FALSE,
    created_at              TIMESTAMPTZ     DEFAULT NOW()
);

-- ============================================================
-- TABLE 6: Electronic Official Receipt (e-OR)
-- ============================================================
CREATE TABLE rcts_eor (
    eor_number              VARCHAR(30)     PRIMARY KEY,
    transaction_id          VARCHAR(30)     NOT NULL UNIQUE
        REFERENCES rcts_payment_transaction(transaction_id) ON DELETE RESTRICT,
    qcitizen_id             VARCHAR(20)     NOT NULL
        REFERENCES rcts_citizen_registry(qcitizen_id) ON DELETE RESTRICT,
    issuance_date           TIMESTAMPTZ     DEFAULT NOW(),
    amount_paid             DECIMAL(15,2)   NOT NULL,
    bill_type               VARCHAR(30)     NOT NULL,
    digital_signature_token VARCHAR(255),
    blockchain_registry_id  VARCHAR(255),
    sent_to_citizen         BOOLEAN         DEFAULT FALSE,
    created_at              TIMESTAMPTZ     DEFAULT NOW()
);

-- ============================================================
-- TABLE 7: Unified Treasury Ledger
-- ============================================================
CREATE TABLE rcts_treasury_ledger (
    ledger_entry_id         BIGSERIAL       PRIMARY KEY,
    transaction_id          VARCHAR(30)
        REFERENCES rcts_payment_transaction(transaction_id) ON DELETE SET NULL,
    disbursement_ref_id     VARCHAR(30),
    entry_type              VARCHAR(10)     NOT NULL
        CHECK (entry_type IN ('Credit','Debit')),
    fund_id                 VARCHAR(30)     NOT NULL
        CHECK (fund_id IN ('GeneralFund','SEF','DRRM_QRF','TrustFund','SpecialAccount')),
    gl_account_code         VARCHAR(20)     NOT NULL,
    revenue_category        VARCHAR(30)     NOT NULL
        CHECK (revenue_category IN ('RPT','BusinessTax','MarketRental','TrafficFine',
                                    'FacilityFee','SocialAidDisbursement',
                                    'ScholarshipDisbursement','DRRMDisbursement',
                                    'FranchiseFee','OtherRevenue')),
    amount                  DECIMAL(15,2)   NOT NULL,
    remarks                 TEXT,
    entry_timestamp         TIMESTAMPTZ     DEFAULT NOW(),
    created_at              TIMESTAMPTZ     DEFAULT NOW()
);

-- ============================================================
-- TABLE 8: Aid & Payout Registry (Outbound — S3, S5, S6)
-- ============================================================
CREATE TABLE rcts_aid_payout_registry (
    disbursement_ref_id     VARCHAR(30)     PRIMARY KEY,
    qcitizen_id             VARCHAR(20)     NOT NULL
        REFERENCES rcts_citizen_registry(qcitizen_id) ON DELETE RESTRICT,
    originating_dept_id     INT             NOT NULL
        CHECK (originating_dept_id IN (3,5,6)),
    program_id              VARCHAR(50),
    program_name            VARCHAR(100),
    approved_amount         DECIMAL(15,2)   NOT NULL,
    disbursement_method     VARCHAR(20)     DEFAULT 'DigitalWallet'
        CHECK (disbursement_method IN ('DigitalWallet','BankTransfer','CashCard','Check')),
    recipient_wallet        VARCHAR(100),
    recipient_bank_account  VARCHAR(50),
    priority_flag           VARCHAR(10)     DEFAULT 'Normal'
        CHECK (priority_flag IN ('Normal','Emergency')),
    ledger_entry_id         BIGINT
        REFERENCES rcts_treasury_ledger(ledger_entry_id) ON DELETE SET NULL,
    status                  VARCHAR(20)     DEFAULT 'Scheduled'
        CHECK (status IN ('Scheduled','Released','Claimed','Failed','Cancelled')),
    scheduled_date          DATE,
    released_at             TIMESTAMPTZ,
    remarks                 TEXT,
    created_at              TIMESTAMPTZ     DEFAULT NOW(),
    updated_at              TIMESTAMPTZ     DEFAULT NOW()
);

-- ============================================================
-- TABLE 9: Public Asset & Market Stall (Subsystem 10)
-- ============================================================
CREATE TABLE rcts_public_asset_stall (
    stall_asset_id              VARCHAR(30)     PRIMARY KEY,
    qcitizen_id                 VARCHAR(20)
        REFERENCES rcts_citizen_registry(qcitizen_id) ON DELETE SET NULL,
    facility_location_id        VARCHAR(30)     NOT NULL,
    facility_name               VARCHAR(100)    NOT NULL,
    stall_number                VARCHAR(20)     NOT NULL,
    stall_type                  VARCHAR(30)     DEFAULT 'Market'
        CHECK (stall_type IN ('Market','Cemetery','Park','Gymnasium','Other')),
    area_sqm                    DECIMAL(8,2),
    monthly_rental_rate         DECIMAL(10,2)   NOT NULL,
    lease_start_date            DATE,
    lease_end_date              DATE,
    occupancy_status_flag       VARCHAR(12)     DEFAULT 'Vacant'
        CHECK (occupancy_status_flag IN ('Active','Vacant','UnderRepair')),
    occupancy_verification_method VARCHAR(20)   DEFAULT 'Manual'
        CHECK (occupancy_verification_method IN ('IoT','QR','MobileApp','Manual')),
    occupancy_last_verified     TIMESTAMPTZ,
    verification_source_subsystem INT           DEFAULT 10,
    created_at                  TIMESTAMPTZ     DEFAULT NOW(),
    updated_at                  TIMESTAMPTZ     DEFAULT NOW()
);

-- ============================================================
-- TABLE 10: Traffic & Violation Registry (Subsystem 9)
-- ============================================================
CREATE TABLE rcts_traffic_violation (
    violation_ticket_id     VARCHAR(30)     PRIMARY KEY,
    qcitizen_id             VARCHAR(20)
        REFERENCES rcts_citizen_registry(qcitizen_id) ON DELETE SET NULL,
    vehicle_plate_no        VARCHAR(15)     NOT NULL,
    violation_type          VARCHAR(50)     NOT NULL,
    fine_amount             DECIMAL(10,2)   NOT NULL,
    apprehension_date       DATE            NOT NULL,
    grace_period_days       INT             DEFAULT 7,
    late_penalty_rate       DECIMAL(5,4)    DEFAULT 0.0200,
    total_amount_due        DECIMAL(10,2),
    bill_reference_no       VARCHAR(30)
        REFERENCES rcts_assessment_billing_hub(bill_reference_no) ON DELETE SET NULL,
    payment_status          VARCHAR(20)     DEFAULT 'Unpaid'
        CHECK (payment_status IN ('Unpaid','Paid','Contested','Waived')),
    resolved_at             TIMESTAMPTZ,
    source_subsystem_id     INT             DEFAULT 9,
    created_at              TIMESTAMPTZ     DEFAULT NOW(),
    updated_at              TIMESTAMPTZ     DEFAULT NOW()
);

-- ============================================================
-- TABLE 11: Regulatory Clearance Registry (Subsystem 4)
-- ============================================================
CREATE TABLE rcts_regulatory_clearance (
    clearance_ref_id        VARCHAR(30)     PRIMARY KEY,
    qcitizen_id             VARCHAR(20)     NOT NULL
        REFERENCES rcts_citizen_registry(qcitizen_id) ON DELETE RESTRICT,
    business_bin            VARCHAR(20)
        REFERENCES rcts_business_entity(bin_number) ON DELETE SET NULL,
    clearance_type          VARCHAR(20)     NOT NULL
        CHECK (clearance_type IN ('Health','Fire','Sanitary','Zoning','Environmental')),
    inspection_date         DATE            NOT NULL,
    valid_until             DATE,
    status_flag             VARCHAR(10)     NOT NULL
        CHECK (status_flag IN ('Passed','Failed','Pending','Expired')),
    inspector_name          VARCHAR(100),
    remarks                 TEXT,
    source_subsystem_id     INT             DEFAULT 4,
    created_at              TIMESTAMPTZ     DEFAULT NOW(),
    updated_at              TIMESTAMPTZ     DEFAULT NOW()
);

-- ============================================================
-- TABLE 12: Treasury Dashboard Snapshots (Module 5)
-- ============================================================
CREATE TABLE rcts_treasury_dashboard (
    snapshot_id             BIGSERIAL       PRIMARY KEY,
    snapshot_timestamp      TIMESTAMPTZ     DEFAULT NOW(),
    total_collection_mtd    DECIMAL(15,2)   DEFAULT 0.00,
    total_rpt_collected     DECIMAL(15,2)   DEFAULT 0.00,
    total_business_tax      DECIMAL(15,2)   DEFAULT 0.00,
    total_market_rental     DECIMAL(15,2)   DEFAULT 0.00,
    total_fines_collected   DECIMAL(15,2)   DEFAULT 0.00,
    total_disbursed_mtd     DECIMAL(15,2)   DEFAULT 0.00,
    net_cash_position       DECIMAL(15,2)   GENERATED ALWAYS AS
                                (total_collection_mtd - total_disbursed_mtd) STORED,
    revenue_target          DECIMAL(15,2)   DEFAULT 0.00,
    target_variance         DECIMAL(15,2)   GENERATED ALWAYS AS
                                (total_collection_mtd - revenue_target) STORED,
    qrf_balance             DECIMAL(15,2)   DEFAULT 0.00,
    qrf_status              VARCHAR(10)     DEFAULT 'Locked'
        CHECK (qrf_status IN ('Locked','Active','Depleted')),
    delinquency_count       INT             DEFAULT 0,
    pending_disbursements   DECIMAL(15,2)   DEFAULT 0.00,
    liquidity_stress_result VARCHAR(10)     DEFAULT 'OK'
        CHECK (liquidity_stress_result IN ('OK','Warning','Critical')),
    generated_by            VARCHAR(50)     DEFAULT 'system'
);

-- ============================================================
-- INDEXES
-- ============================================================
CREATE INDEX idx_billing_qcitizen     ON rcts_assessment_billing_hub(qcitizen_id);
CREATE INDEX idx_billing_status       ON rcts_assessment_billing_hub(status);
CREATE INDEX idx_billing_type         ON rcts_assessment_billing_hub(bill_type);
CREATE INDEX idx_payment_bill         ON rcts_payment_transaction(bill_reference_no);
CREATE INDEX idx_payment_status       ON rcts_payment_transaction(transaction_status);
CREATE INDEX idx_ledger_entry_type    ON rcts_treasury_ledger(entry_type);
CREATE INDEX idx_ledger_category      ON rcts_treasury_ledger(revenue_category);
CREATE INDEX idx_ledger_fund          ON rcts_treasury_ledger(fund_id);
CREATE INDEX idx_payout_dept          ON rcts_aid_payout_registry(originating_dept_id);
CREATE INDEX idx_payout_status        ON rcts_aid_payout_registry(status);
CREATE INDEX idx_clearance_status     ON rcts_regulatory_clearance(status_flag);
CREATE INDEX idx_clearance_bin        ON rcts_regulatory_clearance(business_bin);
CREATE INDEX idx_violation_plate      ON rcts_traffic_violation(vehicle_plate_no);
CREATE INDEX idx_stall_status         ON rcts_public_asset_stall(occupancy_status_flag);
CREATE INDEX idx_property_owner       ON rcts_real_property(qcitizen_id);

-- ============================================================
-- VIEWS
-- ============================================================

CREATE OR REPLACE VIEW v_citizen_pending_bills AS
SELECT b.bill_reference_no, b.qcitizen_id, c.full_name, b.bill_type, b.asset_id,
       b.tax_year, b.base_amount, b.discount_amount, b.penalty_amount,
       b.total_amount_due, b.due_date, b.status, b.created_at
FROM rcts_assessment_billing_hub b
JOIN rcts_citizen_registry c ON b.qcitizen_id = c.qcitizen_id
WHERE b.status = 'Pending'
ORDER BY b.created_at DESC;

CREATE OR REPLACE VIEW v_live_ledger_summary AS
SELECT revenue_category, entry_type, fund_id,
       COUNT(*) AS transaction_count,
       SUM(amount) AS total_amount,
       DATE_TRUNC('month', entry_timestamp) AS month
FROM rcts_treasury_ledger
GROUP BY revenue_category, entry_type, fund_id, DATE_TRUNC('month', entry_timestamp)
ORDER BY month DESC, total_amount DESC;

CREATE OR REPLACE VIEW v_pending_disbursements AS
SELECT d.disbursement_ref_id, d.qcitizen_id, c.full_name, c.digital_wallet_link,
       d.originating_dept_id, d.program_name, d.approved_amount,
       d.disbursement_method, d.priority_flag, d.status, d.scheduled_date
FROM rcts_aid_payout_registry d
JOIN rcts_citizen_registry c ON d.qcitizen_id = c.qcitizen_id
WHERE d.status IN ('Scheduled')
ORDER BY d.priority_flag DESC, d.scheduled_date ASC;

CREATE OR REPLACE VIEW v_business_clearance_status AS
SELECT be.bin_number, be.business_name, be.qcitizen_id, be.gross_sales_declared,
       rc.clearance_type, rc.status_flag AS clearance_status,
       rc.inspection_date, rc.valid_until, be.permit_status
FROM rcts_business_entity be
LEFT JOIN rcts_regulatory_clearance rc ON be.bin_number = rc.business_bin
ORDER BY be.business_name;

CREATE OR REPLACE VIEW v_active_market_stalls AS
SELECT s.stall_asset_id, s.facility_name, s.stall_number, s.qcitizen_id,
       c.full_name AS vendor_name, c.mobile_no,
       s.monthly_rental_rate, s.occupancy_status_flag,
       s.occupancy_last_verified, s.occupancy_verification_method
FROM rcts_public_asset_stall s
LEFT JOIN rcts_citizen_registry c ON s.qcitizen_id = c.qcitizen_id
WHERE s.occupancy_status_flag = 'Active'
ORDER BY s.facility_name, s.stall_number;

CREATE OR REPLACE VIEW v_unbilled_violations AS
SELECT tv.violation_ticket_id, tv.qcitizen_id, c.full_name,
       tv.vehicle_plate_no, tv.violation_type, tv.fine_amount,
       tv.apprehension_date,
       CASE
           WHEN NOW()::DATE > (tv.apprehension_date + tv.grace_period_days)
           THEN tv.fine_amount * (1 + tv.late_penalty_rate *
                (NOW()::DATE - (tv.apprehension_date + tv.grace_period_days)))
           ELSE tv.fine_amount
       END AS computed_total_due
FROM rcts_traffic_violation tv
JOIN rcts_citizen_registry c ON tv.qcitizen_id = c.qcitizen_id
WHERE tv.payment_status = 'Unpaid'
ORDER BY tv.apprehension_date ASC;

-- ============================================================
-- ROW LEVEL SECURITY
-- ============================================================
ALTER TABLE rcts_citizen_registry       ENABLE ROW LEVEL SECURITY;
ALTER TABLE rcts_assessment_billing_hub ENABLE ROW LEVEL SECURITY;
ALTER TABLE rcts_payment_transaction    ENABLE ROW LEVEL SECURITY;
ALTER TABLE rcts_eor                    ENABLE ROW LEVEL SECURITY;

CREATE POLICY citizen_own_bills ON rcts_assessment_billing_hub
    FOR SELECT USING (qcitizen_id = current_setting('app.current_user_id', TRUE));

-- Allow treasury staff to view all bills
CREATE POLICY treasury_all_bills ON rcts_assessment_billing_hub
    FOR SELECT TO authenticated
    USING (
        current_setting('app.user_role', TRUE) IN ('treasurer', 'revenue_officer', 'admin', 'system')
    );

CREATE POLICY citizen_own_transactions ON rcts_payment_transaction
    FOR SELECT USING (qcitizen_id = current_setting('app.current_user_id', TRUE));

CREATE POLICY citizen_own_eor ON rcts_eor
    FOR SELECT USING (qcitizen_id = current_setting('app.current_user_id', TRUE));

-- ============================================================
-- SUCCESS CHECK
-- ============================================================
SELECT 'RCTS-QC Schema Created Successfully!' AS status,
       COUNT(*) AS tables_created
FROM information_schema.tables
WHERE table_schema = 'public' AND table_name LIKE 'rcts_%';

-- ============================================================
-- TO-BE FEATURE: Webhook Subscriptions Table
-- Stores webhook endpoints for real-time event notifications
-- ============================================================
DROP TABLE IF EXISTS webhook_subscriptions CASCADE;

CREATE TABLE webhook_subscriptions (
    id SERIAL PRIMARY KEY,
    endpoint_url TEXT NOT NULL,
    events JSONB NOT NULL DEFAULT '[]'::jsonb,
    secret_key TEXT,
    active BOOLEAN DEFAULT true,
    created_at TIMESTAMPTZ DEFAULT NOW(),
    last_triggered_at TIMESTAMPTZ
);

-- Index for faster lookups
CREATE INDEX idx_webhook_active ON webhook_subscriptions(active);

-- Enable Row Level Security
ALTER TABLE webhook_subscriptions ENABLE ROW LEVEL SECURITY;

-- Allow public read access (for admin purposes)
CREATE POLICY "Allow public read on webhooks" ON webhook_subscriptions
    FOR SELECT USING (true);

-- Allow service role to manage webhooks
CREATE POLICY "Allow service role to manage webhooks" ON webhook_subscriptions
    FOR ALL USING (true);

-- ============================================================
-- TO-BE FEATURE: Webhook Events Table
-- Stores real-time events for dashboard polling
-- Solves session persistence issue between API requests
-- ============================================================
DROP TABLE IF EXISTS webhook_events CASCADE;

CREATE TABLE webhook_events (
    id BIGSERIAL PRIMARY KEY,
    event_type VARCHAR(100) NOT NULL,
    event_data JSONB NOT NULL DEFAULT '{}'::jsonb,
    created_at TIMESTAMPTZ DEFAULT NOW()
);

-- Index for faster polling queries
CREATE INDEX idx_webhook_events_type_time ON webhook_events(event_type, created_at DESC);
CREATE INDEX idx_webhook_events_time ON webhook_events(created_at DESC);

-- Enable Row Level Security
ALTER TABLE webhook_events ENABLE ROW LEVEL SECURITY;

-- Allow public read access for polling
CREATE POLICY "Allow public read on webhook events" ON webhook_events
    FOR SELECT USING (true);

-- Allow service role to insert events
CREATE POLICY "Allow service role to insert webhook events" ON webhook_events
    FOR INSERT WITH CHECK (true);

SELECT 'Webhook Events Table Added!' AS status;

SELECT 'Webhook Subscriptions Table Added!' AS status;
