# RCTS-QC — Revenue Collection & Treasury Services

### Quezon City Government Service Management System — Department 8

**Tech Stack:** HTML · CSS · JavaScript · PHP · Supabase · XAMPP · VSCode

---

## 📁 Project Structure

```
rcts-qc/
│
├── index.html                          ← Landing page / role selector (Citizen | Treasurer | Auditor)
│
├── 📂 pages/
│   ├── 📂 citizen/                     ← PUBLIC PORTAL (External Users)
│   │   ├── login.html                  ← QCitizen ID login (connects to Subsystem 1 mock)
│   │   ├── dashboard.html              ← Citizen home: shows all pending bills
│   │   ├── rpt-payment.html            ← Module 1: Real Property Tax payment
│   │   ├── business-tax.html           ← Module 2: Business Tax & Regulatory Fees
│   │   ├── market-stall.html           ← Module 3: Market Stall rental bill view
│   │   ├── payment-gateway.html        ← Module 4: QC-PAY unified checkout
│   │   └── receipt.html                ← e-OR (Electronic Official Receipt) viewer
│   │
│   ├── 📂 treasurer/                   ← BACKEND PORTAL (Internal Staff)
│   │   ├── dashboard.html              ← Module 5: Treasury Dashboard (KPIs, heatmaps)
│   │   ├── live-ledger.html            ← Real-time Unified Treasury Ledger
│   │   ├── market-billing.html         ← Generate market stall invoices (Module 3 admin)
│   │   ├── disbursement.html           ← Outbound payouts (S3 Social, S5 Edu, S6 DRRM)
│   │   ├── fund-management.html        ← QRF unlock + Liquidity Stress Test
│   │   └── reports.html                ← e-SRE auto-generator for COA
│   │
│   └── 📂 auditor/                     ← READ-ONLY PORTAL (COA / External Auditor)
│       ├── portal.html                 ← Auditor login & overview
│       └── audit-trail.html            ← Immutable transaction logs

│
├── 📂 api/                             ← PHP BACKEND (Microservices Endpoints)
│   ├── 📂 endpoints/                   ← One file per module
│   │   ├── rpt.php                     ← GET/POST for RPT assessment & payment
│   │   ├── business-tax.php            ← Receives S2/S4 signals, computes Unified OP
│   │   ├── market-stall.php            ← Receives S10 occupancy signal, generates invoice
│   │   ├── payment.php                 ← QC-PAY settlement (inbound revenue)
│   │   ├── disbursement.php            ← Outbound payouts (S3, S5, S6 batch transfers)
│   │   ├── inbound.php                 ← Webhook receiver for ALL external subsystems
│   │   └── dashboard.php               ← Live ledger data for Module 5 dashboard
│   │
│   ├── 📂 middleware/
│   │   ├── auth.php                    ← API key verification for cross-subsystem calls
│   │   ├── cors.php                    ← Allows other subsystem servers to call our API
│   │   └── rate-limit.php              ← Prevents API abuse
│   │
│   └── 📂 config/
│       ├── supabase.php                ← Supabase URL + anon key (your DB connection)
│       ├── constants.php               ← Tax rates, discount %, penalty rates
│       └── api-keys.php                ← API keys for each connected subsystem (2,3,4,5,6,9,10)
│
├── 📂 public/                          ← SHARED FRONTEND ASSETS
│   ├── 📂 css/
│   │   ├── main.css                    ← Global styles, CSS variables, typography
│   │   ├── auth.css                    ← Login page styles
│   │   ├── citizen.css                 ← Citizen portal styles
│   │   └── dashboard.css               ← Treasurer/internal portal styles
│   │
│   ├── 📂 js/
│   │   ├── supabase-client.js          ← Supabase JS SDK init (shared by all pages)
│   │   ├── auth.js                     ← Login/logout logic
│   │   ├── api-handler.js              ← Fetch wrapper for all PHP API calls
│   │   └── main.js                     ← Global JS utilities
│   │
│   └── 📂 assets/images/               ← QC logo, icons, etc.
│
├── 📂 includes/                        ← PHP REUSABLE COMPONENTS
│   ├── db.php                          ← Supabase REST API helper (PHP side)
│   ├── header.php                      ← HTML head + nav loader
│   ├── footer.php                      ← Footer HTML
│   ├── nav-citizen.php                 ← Citizen sidebar navigation
│   └── nav-treasurer.php               ← Treasurer sidebar navigation
│
├── 📂 mock-data/                       ← MOCK SUBSYSTEMS 1 & 7 (JSON API simulation)
│   ├── 📂 subsystem1/                  ← Citizen Information & Engagement (EXEMPTED)
│   │   ├── citizens.json               ← 10 mock citizen records with QCitizen_ID
│   │   ├── pwdsenior-registry.json     ← Mock PWD/Senior discount eligibility data
│   │   └── citizen-registry-api.php    ← PHP file that mimics S1 API responses
│   │
│   └── 📂 subsystem7/                  ← Urban Planning, Zoning & Housing (EXEMPTED)
│       ├── properties.json             ← 10 mock property records with TDN + GIS coords
│       ├── gis-data.json               ← Mock zoning classifications (Res/Comm/Ind)
│       └── zoning-api.php              ← PHP file that mimics S7 API responses
│
└── 📂 database/
    ├── schema.sql                      ← All 12 Supabase table definitions (ERD TO-BE)
    ├── seed-data.sql                   ← Sample rows for testing all modules
    └── rcts-erd.md                     ← ERD documentation reference

```

---

## 🔗 Integration Map (Who Calls Who)

| Direction | Subsystem      | RCTS Module              | What is Sent                  |
| --------- | -------------- | ------------------------ | ----------------------------- |
| **IN**    | S1 (Citizen)   | Payment / All            | Identity verification         |
| **IN**    | S2 (Permits)   | Business Tax             | Approved BIN + Gross Sales    |
| **IN**    | S4 (Health)    | Business Tax             | "Passed" clearance signal     |
| **IN**    | S7 (Assessor)  | RPT                      | Property value + GIS data     |
| **IN**    | S9 (Transport) | Payment                  | Traffic fine + ticket data    |
| **IN**    | S10 (Assets)   | Market Stall             | Occupancy verification signal |
| **IN**    | S3 (Social)    | Disbursement             | Beneficiary payout list       |
| **IN**    | S5 (Education) | Disbursement             | Scholarship payroll           |
| **IN**    | S6 (DRRM)      | Dashboard + Disbursement | QRF unlock + victim list      |
| **OUT**   | RCTS           | S1                       | e-OR notification             |
| **OUT**   | RCTS           | S2                       | "Paid" → permit release       |
| **OUT**   | RCTS           | S3/S5/S6                 | Disbursement completion log   |
| **OUT**   | RCTS           | S9                       | Violation ticket resolved     |

---

## 🚀 Development Phases

- [x] **Phase 1** — Project Structure ✅
- [ ] **Phase 2** — Database Schema (Supabase SQL)
- [ ] **Phase 3** — Mock Data (S1 & S7 JSON files)
- [ ] **Phase 4** — Config & Middleware (supabase.php, cors.php, auth.php)
- [ ] **Phase 5** — Module 1: RPT (citizen login → auto-fetch → bill → pay)
- [ ] **Phase 6** — Module 2: Business Tax (signal listener → unified OP → pay)
- [ ] **Phase 7** — Module 3: Market Stall (occupancy signal → invoice → pay)
- [ ] **Phase 8** — Module 4: QC-PAY (inbound + outbound gateway)
- [ ] **Phase 9** — Module 5: Treasury Dashboard (live ledger + reports)
- [ ] **Phase 10** — API Endpoints (all PHP microservice routes)
- [ ] **Phase 11** — Landing Page + Role Selector
- [ ] **Phase 12** — Integration Testing

---

## ⚙️ Setup Instructions (XAMPP)

1. Copy the `rcts-qc/` folder to `C:/xampp/htdocs/rcts-qc`
2. Start Apache in XAMPP Control Panel
3. Open `api/config/supabase.php` and add your Supabase URL and anon key
4. Run `database/schema.sql` in your Supabase SQL Editor
5. Run `database/seed-data.sql` to populate test data
6. Open browser: `http://localhost/rcts-qc/`

---

## 💳 Payment Gateway Integration (GCash / Maya / Stripe)

This project includes a pluggable payment gateway layer designed to support local development (Mock) and real payment providers.

### ✅ Configuration (environment variables)

Set these in your environment (e.g., XAMPP Apache env vars) or in `api/config/payment_gateways.php`:

- `GCASH_CLIENT_ID` / `GCASH_CLIENT_SECRET` / `GCASH_WEBHOOK_SECRET`
- `MAYA_API_KEY` / `MAYA_WEBHOOK_SECRET`
- `STRIPE_API_KEY` / `STRIPE_WEBHOOK_SECRET`

### 🧭 How to get GCash sandbox credentials (general guidance)

1. Register as a **GCash Business Merchant** at https://www.gcash.com/business/ or ask your LGU IT team if you already have a partner contact.
2. Request access to the **GCash sandbox/developer portal**. This is usually provided by Globe/GCash after onboarding.
3. Once approved, you should receive:
   - **Client ID / Client Secret** (for auth)
   - **Webhook Secret** (for validating callbacks)
   - **Sandbox API base URL** (replace `api_base` in `api/config/payment_gateways.php`)
4. Update `api/config/payment_gateways.php` or environment vars with the credentials.
5. Configure your GCash webhook callback URL to:
   - `https://<your-host>/rcts-qc/api/endpoints/payment.php?action=webhook&gateway_provider=GCash`

> ⚠️ If you don’t yet have credentials, the implementation will still work as a placeholder (it returns a “mock” redirect URL and accepts webhooks without signature validation).

### ✅ PayMongo (recommended for PH credit card / e-wallet)

PayMongo is a PH-based payment gateway with a publicly accessible sandbox. You can use your test API keys directly.

1. Set environment variables:
   - `PAYMONGO_API_KEY` (secret key, e.g. `sk_test_...`)
   - `PAYMONGO_WEBHOOK_SECRET` (e.g. `whsk_...`)
2. Configure your PayMongo webhook endpoint to:
   - `https://<your-host>/rcts-qc/api/endpoints/payment.php?action=webhook&gateway_provider=PayMongo`

**Webhook signature header:** `Paymongo-Signature`

### ✅ Stripe (quick test setup)

Stripe has a public sandbox and you can get test keys immediately from https://dashboard.stripe.com/test/apikeys.

1. Set `STRIPE_API_KEY` to the **test secret key**.
2. Set `STRIPE_WEBHOOK_SECRET` by creating a webhook endpoint in Stripe pointing to:
   - `https://<your-host>/rcts-qc/api/endpoints/payment.php?action=webhook&gateway_provider=Stripe`

---
