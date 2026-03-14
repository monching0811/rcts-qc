-- Fix RLS Policies for Payment Updates
-- Run this in your Supabase SQL Editor to allow payment updates

-- ============================================================
-- Fix rcts_assessment_billing_hub table - Allow service role to UPDATE
-- ============================================================

-- First, check existing policies
SELECT tablename, policyname, cmd, roles 
FROM pg_policies 
WHERE schemaname = 'public' 
AND tablename = 'rcts_assessment_billing_hub';

-- Allow service role (anon) to UPDATE via PATCH for payment status
DROP POLICY IF EXISTS service_update_bills ON rcts_assessment_billing_hub;
CREATE POLICY service_update_bills ON rcts_assessment_billing_hub
    FOR UPDATE TO anon, authenticated
    USING (true)
    WITH CHECK (true);

-- Also allow service role to INSERT for new bills
DROP POLICY IF EXISTS service_insert_bills ON rcts_assessment_billing_hub;
CREATE POLICY service_insert_bills ON rcts_assessment_billing_hub
    FOR INSERT TO anon, authenticated
    WITH CHECK (true);

-- ============================================================
-- Fix rcts_payment_transaction table - Allow service role full access
-- ============================================================

DROP POLICY IF EXISTS service_all_transactions ON rcts_payment_transaction;
CREATE POLICY service_all_transactions ON rcts_payment_transaction
    FOR ALL TO anon, authenticated
    USING (true)
    WITH CHECK (true);

-- ============================================================
-- Fix rcts_eor table - Allow service role full access
-- ============================================================

DROP POLICY IF EXISTS service_all_eor ON rcts_eor;
CREATE POLICY service_all_eor ON rcts_eor
    FOR ALL TO anon, authenticated
    USING (true)
    WITH CHECK (true);

-- ============================================================
-- Fix rcts_treasury_ledger table - Allow service role full access
-- ============================================================

DROP POLICY IF EXISTS service_all_ledger ON rcts_treasury_ledger;
CREATE POLICY service_all_ledger ON rcts_treasury_ledger
    FOR ALL TO anon, authenticated
    USING (true)
    WITH CHECK (true);

-- ============================================================
-- Verify RLS is enabled on key tables
-- ============================================================

SELECT 
    schemaname,
    tablename,
    rowsecurity
FROM pg_tables 
WHERE schemaname = 'public'
AND tablename IN (
    'rcts_assessment_billing_hub',
    'rcts_payment_transaction', 
    'rcts_eor',
    'rcts_treasury_ledger'
);

-- Test: Force RLS off if still having issues (development only)
ALTER TABLE rcts_assessment_billing_hub DISABLE ROW LEVEL SECURITY;
ALTER TABLE rcts_payment_transaction DISABLE ROW LEVEL SECURITY;
ALTER TABLE rcts_eor DISABLE ROW LEVEL SECURITY;
ALTER TABLE rcts_treasury_ledger DISABLE ROW LEVEL SECURITY;

-- ============================================================
-- Verify all policies are set up correctly
-- ============================================================

SELECT 
    'rcts_assessment_billing_hub' as table_name,
    policyname, 
    cmd, 
    roles,
    permissive
FROM pg_policies 
WHERE schemaname = 'public' 
AND tablename = 'rcts_assessment_billing_hub'

UNION ALL

SELECT 
    'rcts_payment_transaction' as table_name,
    policyname, 
    cmd, 
    roles,
    permissive
FROM pg_policies 
WHERE schemaname = 'public' 
AND tablename = 'rcts_payment_transaction'

UNION ALL

SELECT 
    'rcts_eor' as table_name,
    policyname, 
    cmd, 
    roles,
    permissive
FROM pg_policies 
WHERE schemaname = 'public' 
AND tablename = 'rcts_eor'

UNION ALL

SELECT 
    'rcts_treasury_ledger' as table_name,
    policyname, 
    cmd, 
    roles,
    permissive
FROM pg_policies 
WHERE schemaname = 'public' 
AND tablename = 'rcts_treasury_ledger';
