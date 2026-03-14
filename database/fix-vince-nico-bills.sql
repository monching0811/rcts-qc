-- Manual SQL to fix Vince Nico's bills that were paid but status didn't update
-- Run this in Supabase SQL Editor

-- First, let's see all bills for Vince Nico
SELECT bill_reference_no, bill_type, total_amount_due, status, created_at
FROM rcts_assessment_billing_hub 
WHERE qcitizen_id = 'b529bf30-50bf-43ab-a314-cc4c2f79c3f5'
ORDER BY created_at DESC;

-- Update all pending bills to "Paid" for Vince Nico (since they paid already)
UPDATE rcts_assessment_billing_hub 
SET status = 'Paid', updated_at = NOW()
WHERE qcitizen_id = 'b529bf30-50bf-43ab-a314-cc4c2f79c3f5'
AND status = 'Pending';

-- Verify the update worked
SELECT bill_reference_no, bill_type, total_amount_due, status
FROM rcts_assessment_billing_hub 
WHERE qcitizen_id = 'b529bf30-50bf-43ab-a314-cc4c2f79c3f5';

-- Check the view now (should show 0 pending bills for Vince Nico)
SELECT * FROM v_citizen_pending_bills 
WHERE qcitizen_id = 'b529bf30-50bf-43ab-a314-cc4c2f79c3f5';
