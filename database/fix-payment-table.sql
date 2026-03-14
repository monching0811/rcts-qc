-- Fix VARCHAR size for qcitizen_id in payment_transaction table
-- Run this in your RCTS Supabase SQL Editor

-- First drop the table if it exists (to recreate with correct sizes)
DROP TABLE IF EXISTS rcts_payment_transaction CASCADE;

-- Recreate with correct VARCHAR sizes for S1 UUIDs
CREATE TABLE rcts_payment_transaction (
    transaction_id          VARCHAR(30)     PRIMARY KEY,
    bill_reference_no       VARCHAR(30),
    qcitizen_id             VARCHAR(50)     NOT NULL,
    gateway_provider        VARCHAR(20),
    amount_settled          DECIMAL(15,2)  NOT NULL,
    digital_hash            VARCHAR(64),
    transaction_status      VARCHAR(20)     DEFAULT 'Pending',
    settlement_loop_sent    BOOLEAN         DEFAULT FALSE,
    bank_reference_no       TEXT,
    transaction_timestamp   TIMESTAMPTZ     DEFAULT NOW()
);

-- Enable RLS
ALTER TABLE rcts_payment_transaction ENABLE ROW LEVEL SECURITY;

-- Allow service role full access
CREATE POLICY service_all ON rcts_payment_transaction
    FOR ALL TO service_role USING (true) WITH CHECK (true);

-- Allow anon insert
CREATE POLICY anon_insert ON rcts_payment_transaction
    FOR INSERT TO anon WITH CHECK (true);

-- Allow authenticated select
CREATE POLICY citizen_select ON rcts_payment_transaction
    FOR SELECT TO authenticated USING (true);

-- Create index
CREATE INDEX idx_payment_citizen ON rcts_payment_transaction(qcitizen_id);
CREATE INDEX idx_payment_status ON rcts_payment_transaction(transaction_status);

-- Verify
SELECT 'rcts_payment_transaction table recreated with VARCHAR(50) for qcitizen_id' AS status;
