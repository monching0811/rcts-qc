-- ============================================================
-- TO-BE FEATURE: Webhook Events Table
-- Stores real-time events for dashboard polling
-- Solves session persistence issue between API requests
-- ============================================================

-- Create the webhook_events table if it doesn't exist
CREATE TABLE IF NOT EXISTS webhook_events (
    id BIGSERIAL PRIMARY KEY,
    event_type VARCHAR(100) NOT NULL,
    event_data JSONB NOT NULL DEFAULT '{}'::jsonb,
    created_at TIMESTAMPTZ DEFAULT NOW()
);

-- Index for faster polling queries
CREATE INDEX IF NOT EXISTS idx_webhook_events_type_time ON webhook_events(event_type, created_at DESC);
CREATE INDEX IF NOT EXISTS idx_webhook_events_time ON webhook_events(created_at DESC);

-- Enable Row Level Security
ALTER TABLE webhook_events ENABLE ROW LEVEL SECURITY;

-- Drop existing policies if they exist
DROP POLICY IF EXISTS "Allow public read on webhook events" ON webhook_events;
DROP POLICY IF EXISTS "Allow service role to insert webhook events" ON webhook_events;

-- Allow public read access for polling
CREATE POLICY "Allow public read on webhook events" ON webhook_events
    FOR SELECT USING (true);

-- Allow service role to insert events
CREATE POLICY "Allow service role to insert webhook events" ON webhook_events
    FOR INSERT WITH CHECK (true);

SELECT 'Webhook Events Table Created Successfully!' AS status;
