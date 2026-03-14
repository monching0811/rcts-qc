-- Fix RLS Policy to allow Treasury staff to view all bills
-- Run this in your Supabase SQL Editor

-- Drop existing restrictive policy if it exists
DROP POLICY IF EXISTS citizen_own_bills ON rcts_assessment_billing_hub;

-- Allow citizens to see their own bills
CREATE POLICY citizen_own_bills ON rcts_assessment_billing_hub
    FOR SELECT USING (qcitizen_id = current_setting('app.current_user_id', TRUE));

-- Allow treasury staff to view all bills
CREATE POLICY treasury_all_bills ON rcts_assessment_billing_hub
    FOR SELECT TO authenticated
    USING (
        current_setting('app.user_role', TRUE) IN ('treasurer', 'revenue_officer', 'admin', 'system')
    );

-- Also apply the same fix to other treasury tables that might have this issue
-- Check if treasury_ledger has similar issue
DROP POLICY IF EXISTS citizen_own_ledger ON rcts_treasury_ledger;
CREATE POLICY citizen_own_ledger ON rcts_treasury_ledger
    FOR SELECT USING (qcitizen_id = current_setting('app.current_user_id', TRUE));

CREATE POLICY treasury_all_ledger ON rcts_treasury_ledger
    FOR SELECT TO authenticated
    USING (
        current_setting('app.user_role', TRUE) IN ('treasurer', 'revenue_officer', 'admin', 'system')
    );

-- Check if aid_payout_registry has similar issue
DROP POLICY IF EXISTS citizen_own_payout ON rcts_aid_payout_registry;
CREATE POLICY citizen_own_payout ON rcts_aid_payout_registry
    FOR SELECT USING (qcitizen_id = current_setting('app.current_user_id', TRUE));

CREATE POLICY treasury_all_payout ON rcts_aid_payout_registry
    FOR SELECT TO authenticated
    USING (
        current_setting('app.user_role', TRUE) IN ('treasurer', 'revenue_officer', 'admin', 'system')
    );

-- Verify the policies
SELECT tablename, policyname, permissive, roles, cmd, qual 
FROM pg_policies 
WHERE schemaname = 'public' 
AND tablename IN ('rcts_assessment_billing_hub', 'rcts_treasury_ledger', 'rcts_aid_payout_registry');
