# RCTS-QC Setup Guide

## Department 8 — Revenue Collection & Treasury Services

---

## Prerequisites

- XAMPP (Windows) with **Apache** and optionally MySQL
- A free [Supabase](https://supabase.com) account (for the database)
- Any modern browser (Chrome recommended)

---

## Step 1 — Copy project files

Place the entire `rcts-qc/` folder inside XAMPP's web root:

```
C:\xampp\htdocs\rcts-qc\
```

Your folder structure should look like:

```
C:\xampp\htdocs\rcts-qc\
  index.html
  .htaccess
  api\
  pages\
  mock-data\
  public\
  database\
  tests\
  logs\
```

---

## Step 2 — Start XAMPP

1. Open **XAMPP Control Panel**
2. Click **Start** next to **Apache**
3. Confirm it shows green / "Running"

---

## Step 3 — Set up Supabase

1. Go to [supabase.com](https://supabase.com) → Create a new project (e.g. `rcts-qc`)
2. In your project dashboard → go to **SQL Editor**
3. Run **`database/schema.sql`** first (creates all 12 tables)
4. Run **`database/seed-data.sql`** second (inserts demo data)

---

## Step 4 — Configure credentials

Open `api/config/supabase.php` and fill in your Supabase project details:

```php
define('SUPABASE_URL',     'https://ipjtrqcncyvmtzrbsjya.supabase.co');
define('SUPABASE_API_KEY', 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6ImlwanRycWNuY3l2bXR6cmJzanlhIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NzI2NDI0NTMsImV4cCI6MjA4ODIxODQ1M30.BLSGY7O-uXRf-OTiFwb1Vq05I3ZdG-D82tzXp64fGrQ');
```

Find these in Supabase → **Project Settings → API**:

- **Project URL** → goes into `SUPABASE_URL`
- **anon / public key** → goes into `SUPABASE_API_KEY`

---

## Step 5 — Enable mod_rewrite (if needed)

If pages return 403 or API calls fail, enable `.htaccess` in XAMPP:

1. Open `C:\xampp\apache\conf\httpd.conf`
2. Find the `<Directory "C:/xampp/htdocs">` block
3. Change `AllowOverride None` → `AllowOverride All`
4. Restart Apache

Also ensure `mod_rewrite` is not commented out:

```
LoadModule rewrite_module modules/mod_rewrite.so   ← remove # if present
```

---

## Step 6 — Create logs directory (auto-created, just verify)

Make sure this folder exists and is writable:

```
C:\xampp\htdocs\rcts-qc\logs\rate-limits\
```

XAMPP Apache runs as the local user so it should be writable by default.

---

## Step 7 — Open the app

Navigate to: **http://localhost/rcts-qc/**

You should see the RCTS-QC landing page with three portal cards.

---

## Demo Accounts

| Role                    | Email                   | Password      |
| ----------------------- | ----------------------- | ------------- |
| Citizen (Juan)          | juan.delacruz@email.com | password123   |
| Citizen (Maria, Senior) | maria.santos@email.com  | password123   |
| Citizen (Pedro, PWD)    | pedro.reyes@email.com   | password123   |
| Treasurer               | treasurer@qc.gov.ph     | treasurer2025 |
| COA Auditor             | auditor@qc.gov.ph       | auditor2025   |

---

## Testing the Integration

Open the signal sender demo page:
**http://localhost/rcts-qc/tests/signal-sender.html**

This lets you manually fire signals from all 6 subsystems:

- **S3** — Push AICS payout requests
- **S4** — Send business clearance results (Fire, Health, Sanitary)
- **S5** — Push scholarship stipend payroll
- **S6** — Declare disaster + unlock QRF
- **S9** — Issue traffic violation tickets
- **S10** — Send IoT/QR occupancy updates for market stalls

After firing a signal, switch to the **Treasurer Dashboard** to see the effect live.

---

## Troubleshooting

| Problem                                | Fix                                                                      |
| -------------------------------------- | ------------------------------------------------------------------------ |
| Blank page / 500 error                 | Check `logs/php-errors.log`                                              |
| API returns empty `{}`                 | Verify Supabase URL and key in `supabase.php`                            |
| CORS error in console                  | Ensure `.htaccess` is being read (mod_rewrite enabled)                   |
| Rate limit 429 error                   | Delete files in `logs/rate-limits/`                                      |
| Signal sender shows "No RCTS response" | The internal PHP-to-PHP call uses `localhost` — ensure Apache is running |
| Login fails                            | Run seed-data.sql again to reset demo accounts                           |

---

## Project Structure Reference

```
api/
  config/       ← supabase.php, constants.php, api-keys.php
  endpoints/    ← 7 PHP microservices
  middleware/   ← auth.php, cors.php, rate-limit.php

mock-data/
  subsystem1/   ← S1 Citizen Registry mock API
  subsystem3/   ← S3 Social Services mock
  subsystem4/   ← S4 Clearances mock
  subsystem5/   ← S5 Scholarship mock
  subsystem6/   ← S6 DRRM mock
  subsystem7/   ← S7 Zoning/Property mock API
  subsystem9/   ← S9 Traffic mock
  subsystem10/  ← S10 Public Assets mock

pages/
  citizen/      ← 7 citizen portal pages
  treasurer/    ← 6 treasurer portal pages
  auditor/      ← 2 COA auditor portal pages

public/
  css/          ← main.css, auth.css, citizen.css, dashboard.css
  js/           ← main.js, auth.js, api-handler.js, supabase-client.js

database/       ← schema.sql, seed-data.sql, rcts-erd.md
tests/          ← signal-sender.html (integration demo tool)
logs/           ← php-errors.log, rate-limits/ (auto-created)
```

---

_RCTS-QC · Department 8 · Quezon City Government Service Management System_
_BSIT 3rd Year Project · Microservices Architecture_
