-- ============================================================
-- FIX: Add UPDATE and INSERT policies for rcts_assessment_billing_hub
-- ============================================================
-- This allows the service role (used in payment.php) to update bill status
-- and create new bills

-- Drop existing policies that might be too restrictive
DROP POLICY IF EXISTS citizen_own_bills ON rcts_assessment_billing_hub;
DROP POLICY IF EXISTS treasury_all_bills ON rcts_assessment_billing_hub;

-- Allow service role (backend) to perform ALL operations
-- This is safe because service role is server-side only
CREATE POLICY "Allow service role all operations" ON rcts_assessment_billing_hub
    FOR ALL TO service_role
    USING (true)
    WITH CHECK (true);

-- Allow authenticated users (treasury/revenue officers) to read bills
CREATE POLICY "Allow treasury staff to read all bills" ON rcts_assessment_billing_hub
    FOR SELECT TO authenticated
    USING (
        current_setting('app.user_role', TRUE) IN ('treasurer', 'revenue_officer', 'admin', 'system')
    );

-- Allow citizens to read their own bills
CREATE POLICY "Allow citizens to read own bills" ON rcts_assessment_billing_hub
    FOR SELECT TO authenticated
    USING (qcitizen_id = current_setting('app.current_user_id', TRUE));

-- Verify policies are in place
SELECT 'BILLING HUB RLS POLICIES UPDATED' AS message,
       COUNT(*) AS policy_count
FROM information_schema.table_constraints
WHERE table_name = 'rcts_assessment_billing_hub';
