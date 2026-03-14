-- ============================================================
-- RESET TEST BILLS
-- Run this to reset bills for testing purposes
-- ============================================================

-- Reset all bills for Juan Dela Cruz (QC-2024-000001) back to Pending
UPDATE rcts_assessment_billing_hub 
SET status = 'Pending', updated_at = NOW()
WHERE qcitizen_id = 'QC-2024-000001';

-- Reset all related payment records (optional - for clean testing)
-- DELETE FROM rcts_payment_transaction WHERE qcitizen_id = 'QC-2024-000001';
-- DELETE FROM rcts_eor WHERE qcitizen_id = 'QC-2024-000001';

-- Verify the reset
SELECT bill_reference_no, bill_type, status, total_amount_due 
FROM rcts_assessment_billing_hub 
WHERE qcitizen_id = 'QC-2024-000001'
ORDER BY bill_type, tax_year DESC;

-- Expected results after reset:
-- RPT-2025-QC001-001 | RPT | Pending | ₱4,800.00
-- RPT-2024-QC001-001 | RPT | Pending | ₱4,600.00 (with penalty)
-- BIZ-2025-QC001-001 | BusinessTax | Pending | ₱1,875.00
-- MKT-2025-QC001-001 | MarketRental | Pending | ₱1,500.00
-- MKT-2024-QC001-001 | MarketRental | Pending | ₱1,575.00 (with penalty)
-- TRF-2025-QC001-001 | TrafficFine | Pending | ₱2,000.00
