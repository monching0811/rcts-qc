-- Fix RLS policies for RCTS tables to allow INSERT operations
-- Run this in your RCTS Supabase SQL Editor

-- ============================================================
-- Fix RLS for rcts_payment_transaction table
-- ============================================================

-- Drop existing policies if they exist
DROP POLICY IF EXISTS citizen_own_transactions ON rcts_payment_transaction;
DROP POLICY IF EXISTS service_insert ON rcts_payment_transaction;
DROP POLICY IF EXISTS service_update ON rcts_payment_transaction;

-- Allow service role to do everything (bypass RLS)
CREATE POLICY service_all ON rcts_payment_transaction
    FOR ALL
    TO service_role
    USING (true)
    WITH CHECK (true);

-- Allow anonymous/anon role to INSERT (for checkout)
CREATE POLICY anon_insert ON rcts_payment_transaction
    FOR INSERT
    TO anon
    WITH CHECK (true);

-- Allow authenticated users to SELECT their own transactions
CREATE POLICY citizen_own_transactions ON rcts_payment_transaction
    FOR SELECT
    TO authenticated
    USING (qcitizen_id = current_setting('app.current_user_id', true));

-- ============================================================
-- Fix RLS for rcts_assessment_billing_hub table
-- ============================================================

DROP POLICY IF EXISTS citizen_own_bills ON rcts_assessment_billing_hub;
DROP POLICY IF EXISTS service_all_billing ON rcts_assessment_billing_hub;
DROP POLICY IF EXISTS anon_insert_billing ON rcts_assessment_billing_hub;

CREATE POLICY service_all_billing ON rcts_assessment_billing_hub
    FOR ALL
    TO service_role
    USING (true)
    WITH CHECK (true);

CREATE POLICY anon_insert_billing ON rcts_assessment_billing_hub
    FOR INSERT
    TO anon
    WITH CHECK (true);

-- ============================================================
-- Fix RLS for rcts_eor table
-- ============================================================

DROP POLICY IF EXISTS citizen_own_eor ON rcts_eor;
DROP POLICY IF EXISTS service_all_eor ON rcts_eor;
DROP POLICY IF EXISTS anon_insert_eor ON rcts_eor;

CREATE POLICY service_all_eor ON rcts_eor
    FOR ALL
    TO service_role
    USING (true)
    WITH CHECK (true);

CREATE POLICY anon_insert_eor ON rcts_eor
    FOR INSERT
    TO anon
    WITH CHECK (true);

-- ============================================================
-- Fix RLS for rcts_treasury_ledger table
-- ============================================================

DROP POLICY IF EXISTS service_all_ledger ON rcts_treasury_ledger;
DROP POLICY IF EXISTS anon_insert_ledger ON rcts_treasury_ledger;

CREATE POLICY service_all_ledger ON rcts_treasury_ledger
    FOR ALL
    TO service_role
    USING (true)
    WITH CHECK (true);

CREATE POLICY anon_insert_ledger ON rcts_treasury_ledger
    FOR INSERT
    TO anon
    WITH CHECK (true);

-- ============================================================
-- Fix RLS for rcts_citizen_registry table
-- ============================================================

DROP POLICY IF EXISTS citizen_own_profile ON rcts_citizen_registry;
DROP POLICY IF EXISTS service_all_registry ON rcts_citizen_registry;
DROP POLICY IF EXISTS anon_insert_registry ON rcts_citizen_registry;

CREATE POLICY service_all_registry ON rcts_citizen_registry
    FOR ALL
    TO service_role
    USING (true)
    WITH CHECK (true);

CREATE POLICY anon_insert_registry ON rcts_citizen_registry
    FOR INSERT
    TO anon
    WITH CHECK (true);
