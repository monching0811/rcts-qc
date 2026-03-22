-- ============================================================
-- System Settings Table for RCTS-QC
-- Stores tax rates, penalties, due dates, and other configurable parameters
-- ============================================================

CREATE TABLE IF NOT EXISTS rcts_system_settings (
    setting_key         VARCHAR(100)     PRIMARY KEY,
    setting_value       TEXT,
    category            VARCHAR(50)     DEFAULT 'General',
    description         TEXT,
    updated_at          TIMESTAMP       DEFAULT NOW(),
    updated_by          VARCHAR(100)    DEFAULT 'system'
);

-- Insert default system settings
INSERT INTO rcts_system_settings (setting_key, setting_value, category, description) VALUES
-- Real Property Tax (RPT) Settings
('RPT_BASIC_RATE', '0.025', 'RPT', 'Real Property Tax Basic Rate (2.5% of Assessed Value)'),
('RPT_SEF_RATE', '0.01', 'RPT', 'Special Education Fund Rate (1% of Assessed Value)'),
('RPT_DUE_DATE_Q1', '03-31', 'RPT', 'RPT Q1 Due Date (March 31)'),
('RPT_DUE_DATE_Q2', '06-30', 'RPT', 'RPT Q2 Due Date (June 30)'),
('RPT_DUE_DATE_Q3', '09-30', 'RPT', 'RPT Q3 Due Date (September 30)'),
('RPT_DUE_DATE_Q4', '12-31', 'RPT', 'RPT Q4 Due Date (December 31)'),

-- Business Tax Settings
('BIZ_TAX_RATE_RETAIL', '0.012', 'Business Tax', 'Business Tax Rate for Retail (1.2%)'),
('BIZ_TAX_RATE_WHOLESALE', '0.015', 'Business Tax', 'Business Tax Rate for Wholesale (1.5%)'),
('BIZ_TAX_RATE_MFR', '0.02', 'Business Tax', 'Business Tax Rate for Manufacturers (2%)'),
('BIZ_TAX_RATE_IMPORTER', '0.025', 'Business Tax', 'Business Tax Rate for Importers (2.5%)'),
('BIZ_TAX_DUE_DATE', '01-20', 'Business Tax', 'Business Tax Annual Due Date (January 20)'),

-- Penalty Settings
('PENALTY_RATE_MONTHLY', '0.02', 'Penalty', 'Monthly Penalty Rate for late payments (2% per month)'),
('PENALTY_MAX_MONTHS', '36', 'Penalty', 'Maximum months for penalty accumulation'),

-- Discount Settings
('EARLY_PAYMENT_DISCOUNT_RATE', '0.10', 'Discount', 'Early Payment Discount Rate (10% if paid within first month)'),
('EARLY_PAYMENT_DISCOUNT_DEADLINE', '01-31', 'Discount', 'Early Payment Discount Deadline (January 31)'),

-- Market Stall Settings
('MARKET_STALL_DAILY_RATE', '50.00', 'Market', 'Daily Market Stall Rate (PHP)'),
('MARKET_STALL_MONTHLY_RATE', '1200.00', 'Market', 'Monthly Market Stall Rate (PHP)'),

-- Traffic Fine Settings
('TRAFFIC_VIOLATION_BASE_FINE', '500.00', 'Traffic', 'Base Traffic Violation Fine (PHP)'),
('TRAFFIC_VIOLATION_SURCHARGE', '0.10', 'Traffic', 'Traffic Violation Surcharge (10% per month late)'),

-- Revenue Target
('REVENUE_TARGET_MONTHLY', '5000000.00', 'Target', 'Monthly Revenue Target (PHP)')
ON CONFLICT (setting_key) DO NOTHING;
