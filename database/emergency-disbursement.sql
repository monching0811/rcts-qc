-- Emergency disbursement inserts
INSERT INTO rcts_aid_payout_registry (disbursement_ref_id, qcitizen_id, originating_dept_id, program_id, program_name, approved_amount, disbursement_method, priority_flag, status, scheduled_date) VALUES
('DISB-DRRM-2025-007','QC-2024-000007',6,'DRRM-2025-005',  'Flood Relief',         8000.00,'BankTransfer', 'Emergency', 'Scheduled', CURRENT_DATE + INTERVAL '2 days')
ON CONFLICT DO NOTHING;
INSERT INTO rcts_aid_payout_registry (disbursement_ref_id, qcitizen_id, originating_dept_id, program_id, program_name, approved_amount, disbursement_method, priority_flag, status, scheduled_date) VALUES
('DISB-DRRM-2025-008','QC-2024-000008',6,'DRRM-2025-006',  'Typhoon Aid',         7000.00,'BankTransfer', 'Emergency', 'Scheduled', CURRENT_DATE + INTERVAL '4 days')
ON CONFLICT DO NOTHING;
INSERT INTO rcts_aid_payout_registry (disbursement_ref_id, qcitizen_id, originating_dept_id, program_id, program_name, approved_amount, disbursement_method, priority_flag, status, scheduled_date) VALUES
('DISB-DRRM-2025-009','QC-2024-000009',6,'DRRM-2025-007',  'Landslide Support',   9000.00,'BankTransfer', 'Emergency', 'Scheduled', CURRENT_DATE + INTERVAL '1 day')
ON CONFLICT DO NOTHING;
