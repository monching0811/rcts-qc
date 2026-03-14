-- Quick test: Disable RLS to see if that's the issue
-- Run this in Supabase SQL Editor

-- Disable RLS completely on the billing hub table
ALTER TABLE rcts_assessment_billing_hub DISABLE ROW LEVEL SECURITY;

-- Verify it's disabled
SELECT relname, relrowsecurity 
FROM pg_class 
WHERE relname = 'rcts_assessment_billing_hub';

-- Also try to query directly to see if there are any bills
SELECT COUNT(*) as total_bills FROM rcts_assessment_billing_hub;

-- Show all bills
SELECT bill_reference_no, qcitizen_id, bill_type, status, total_amount_due 
FROM rcts_assessment_billing_hub 
LIMIT 10;
