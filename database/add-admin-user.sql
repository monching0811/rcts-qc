-- Add admin user to rcts_citizen_registry table for admin dashboard access
-- Run this SQL in Supabase SQL Editor

INSERT INTO rcts_citizen_registry (
    qcitizen_id,
    full_name,
    email,
    role,
    status
) VALUES (
    'QC-ADMIN-0001',
    'System Administrator',
    'admin@qc.gov.ph',
    'admin',
    'active'
) ON CONFLICT (qcitizen_id) DO UPDATE SET
    role = 'admin',
    status = 'active';
