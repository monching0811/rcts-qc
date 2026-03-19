-- ============================================================
-- TABLE: Internal Users (Staff Accounts)
-- For: Treasurer, Revenue Officer, Auditor, Admin
-- ============================================================
CREATE TABLE IF NOT EXISTS rcts_internal_users (
    user_id         SERIAL PRIMARY KEY,
    full_name       VARCHAR(150)    NOT NULL,
    email           VARCHAR(100)    UNIQUE NOT NULL,
    password_hash   VARCHAR(255)    NOT NULL,
    role            VARCHAR(20)     NOT NULL
        CHECK (role IN ('treasurer','revenue_officer','auditor','admin')),
    status          VARCHAR(10)     DEFAULT 'active'
        CHECK (status IN ('active','inactive','suspended')),
    created_at      TIMESTAMPTZ     DEFAULT NOW(),
    updated_at      TIMESTAMPTZ     DEFAULT NOW()
);
