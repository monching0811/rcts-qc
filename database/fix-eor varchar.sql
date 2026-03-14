-- Fix VARCHAR sizes for rcts_eor table
-- Run this in your RCTS Supabase SQL Editor

-- Drop and recreate eOR table with proper sizes
DROP TABLE IF EXISTS rcts_eor CASCADE;

CREATE TABLE rcts_eor (
    eor_number              VARCHAR(30)     PRIMARY KEY,
    transaction_id          VARCHAR(30),
    qcitizen_id             VARCHAR(50),
    amount_paid             DECIMAL(15,2),
    bill_type               VARCHAR(30),
    digital_signature_token VARCHAR(64),
    blockchain_registry_id  VARCHAR(70),
    sent_to_citizen         BOOLEAN DEFAULT FALSE,
    issuance_date           TIMESTAMPTZ     DEFAULT NOW(),
    created_at              TIMESTAMPTZ     DEFAULT NOW()
);

-- Enable RLS
ALTER TABLE rcts_eor ENABLE ROW LEVEL SECURITY;

-- Allow service role full access
DROP POLICY IF EXISTS service_all_eor ON rcts_eor;
CREATE POLICY service_all_eor ON rcts_eor
    FOR ALL TO service_role USING (true) WITH CHECK (true);

-- Allow anon insert
DROP POLICY IF EXISTS anon_insert_eor ON rcts_eor;
CREATE POLICY anon_insert_eor ON rcts_eor
    FOR INSERT TO anon WITH CHECK (true);

-- Allow authenticated select
DROP POLICY IF EXISTS citizen_select_eor ON rcts_eor;
CREATE POLICY citizen_select_eor ON rcts_eor
    FOR SELECT TO authenticated USING (true);

-- Create indexes
DROP INDEX IF EXISTS idx_eor_transaction;
DROP INDEX IF EXISTS idx_eor_citizen;
CREATE INDEX idx_eor_transaction ON rcts_eor(transaction_id);
CREATE INDEX idx_eor_citizen ON rcts_eor(qcitizen_id);

SELECT 'rcts_eor table recreated with VARCHAR(50) for qcitizen_id' AS status;
