# Ron's Homepage / Ron Belisle Financial Planning — Project Overview

**Authoritative context for the codebase.** Use this document for architecture, deployment, and feature state.

---

## What the Web App Does

A PHP site offering **free financial/retirement planning calculators** with an optional **premium tier** (Stripe subscription).

- **Public:** Browse the homepage and use all calculators without logging in.
- **Premium (logged-in, subscribed):** Extended projections, save/load scenarios, download RMD reports as PDFs.
- **Auth:** Register, login, logout; subscription status stored and checked in the DB.

**Calculators:** RMD Impact, Future Value, Social Security Claiming Analyzer, SS + Spending Gap, Roth Conversion, Required vs. Desired Spending, Managed vs Vanguard, Time Value of Money (sub-tools), and a retirement projection app in `retirement-app/`.

---

## Technology Stack

| Layer | Technology |
|-------|------------|
| **Frontend** | Vanilla JavaScript, Chart.js for visualizations |
| **Backend** | PHP 8.x with Apache |
| **Database** | MySQL — `users` (source of truth for subscription + Stripe ids), `scenarios` (saved calculator state) |
| **Payment** | Stripe (Live mode). Checkout flow + `success.php` set premium; no webhook handler in repo yet (see below). |
| **PDF** | TCPDF library |
| **Deployment** | Digital Ocean (64.23.181.64), Git-based workflow |

---

## How Main Directories Relate

| Directory | Role |
|-----------|------|
| **`/` (root)** | `index.php` = homepage (session, premium check, app cards). Also `subscribe.php`, `checkout.php`, `success.php`, `disclaimer.php`. |
| **`/includes/`** | Shared config and UI: `db_config.php`, `stripe_config.php`, `analytics.php`, `footer.php`, `premium-banner-include.php`. |
| **`/auth/`** | `login.php`, `register.php`, `logout.php`. Uses root `db_config.php` and `getDBConnection()` (legacy; see DB config below). |
| **`/api/`** | JSON endpoints for premium: `save_scenario.php`, `load_scenarios.php`, `delete_scenario.php`, `generate_rmd_pdf.php`. Require login + premium; use `includes/db_config.php`. |
| **`/rmd-impact/`, `/future-value-app/`, `/ss-gap/`, etc.** | One folder per calculator: `index.php` + `calculator.js` (+ optional CSS). Link from homepage; call `/api/` for save/load/PDF. |
| **`/time-value-of-money/`** | Same pattern with subfolders (e.g. `present-value/`, `future-value-annuity/`) each with its own `index.php`. |
| **`/retirement-app/`** | Separate MVC-style app (own repo/submodule): `controllers/ProjectionController.php`, `models/ProjectionModel.php`, `views/projection.php`. |
| **`/css/`** | Shared styles. **`/vendor/`** = Composer (TCPDF, Stripe). |

---

## Premium Feature Implementation

**Per-calculator premium features:**

- **Extended projections:** Free users see 3 sample rows (e.g. ages 73, 78, 83) + blurred preview. Premium users see all years (e.g. 73–100). Controlled by `isPremiumUser` JavaScript variable injected from PHP.
- **Save/Load:** Scenarios stored in DB (e.g. `scenarios` table: `calculator_type`, `scenario_name`, `scenario_data` JSON). API: `save_scenario.php`, `load_scenarios.php`, `delete_scenario.php`.
- **PDF export:** Chart rendered client-side with Chart.js, captured as base64 image, sent to `api/generate_rmd_pdf.php`; TCPDF renders report with chart embedded.

---

## Critical File Patterns

**Each calculator follows this pattern:**

- **`index.php`:** Session start, premium check via `$isPremium` (query `users.subscription_status`), inject `isPremiumUser` into JS, include `premium-banner-include.php` if not premium.
- **`calculator.js`:** Core math, `displayResults()` with free vs premium views (limited rows vs full), `saveScenario` / `loadScenario` / `downloadPDF` calling `/api/`.
- **Shared:** `../includes/db_config.php` for DB; `/api/` for persistence and PDF.

---

## Session / Auth Flow

- **Session:** `$_SESSION['user_id']`, `$_SESSION['user_email']` (and `user_name`, `subscription_status` where used).
- **Premium:** Checked via `users.subscription_status = 'premium'`. Stripe is treated as authoritative for gating.
- **API:** Endpoints validate `isset($_SESSION['user_id'])` and then `subscription_status === 'premium'`; return 401/403 otherwise.
- **Stripe → DB:** No webhook file exists in the repo. Premium is set when the user lands on **`success.php`** after Checkout: `success.php` retrieves the Stripe Checkout Session by `session_id`, reads `client_reference_id` (user id) and `subscription`, and runs `UPDATE users SET subscription_status = 'premium', stripe_subscription_id = ? WHERE id = ?`. For subscription canceled/renewed events, add a root-level webhook endpoint (e.g. search for `Stripe\Webhook`, `constructEvent`, or `stripe-signature` to find it once added) and document it here.

---

## Where Core Business Logic Lives

- **Calculator math and rules:** In **JavaScript** in each app folder (e.g. `rmd-impact/calculator.js` — RMD divisors, tax brackets, projections). Core financial logic is **client-side JS** in those `calculator.js` files.
- **Retirement projection (multi-year balance):** **Server-side** in `retirement-app/models/ProjectionModel.php` (e.g. `projectYears` with compounding).
- **Premium persistence and PDF:** **`/api/`** — validate session + premium, then DB or TCPDF.

---

## Deployment Workflow

- **Local:** `/Applications/XAMPP/htdocs/` (Mac development).
- **Remote:** `/var/www/html/` (Ubuntu server on Digital Ocean).
- **Pattern:** `git commit` → push → SSH to server → `git pull` → copy/sync files to `/var/www/html/` as needed.

---

## Current Implementation Status

| Component | Status |
|-----------|--------|
| **RMD Impact Calculator** | **FULLY COMPLETE** — Extended projections (free vs premium), save/load scenarios, PDF export with chart, premium banner for free users. |
| **Other calculators** (Future Value, SS Gap, Roth Conversion, etc.) | **IN PROGRESS** — Some have save/load; need extended projections + PDF export to match RMD Impact. **Priority order (user value / payoff):** (1) Roth Conversion, (2) Social Security Claiming Analyzer, (3) RMD Impact (done), (4) Time Value of Money / Future Value. Adjust if user feedback suggests otherwise. |

---

## Key Design Decisions / Constraints

- Premium features are **not** promoted on the homepage (“pause and revisit” strategy).
- Admin access via bookmarked URLs: `/auth/login.php`, `/auth/logout.php`.
- Free users can still register at `/auth/login.php` (backend active, not promoted).
- Chart.js renders client-side; chart is captured as image for PDF embedding.

---

## Quick Reference

| Item | Value |
|------|--------|
| **Premium test account** | ronbelisle@gmail.com |
| **Stripe products** | Premium Monthly ($6), Premium Annual ($60) |
| **Database** | `retirement_calculators` (DigitalOcean managed MySQL). Local dev: `ronbelisle_premium` (see `includes/db_config.php`). |
| **Scenarios table** | `scenarios` (user_id, calculator_type, scenario_name, scenario_data JSON) |
| **Skills system** | `/mnt/skills/public/` — server-side path on Digital Ocean; **not referenced anywhere in this repo**. Treat as external if ever integrated. |

---

## Database & Stripe (verified from codebase)

- **Tables:** Only **`users`** and **`scenarios`** are used. No separate `subscriptions` table. User-centric: Stripe `customer_id` and subscription-related fields live on `users`; `users` is the source of truth for `subscription_status` and Stripe identifiers (e.g. `stripe_subscription_id` set in `success.php`).
- **Table name:** **`scenarios`** (confirmed: `api/save_scenario.php`, `load_scenarios.php`, `delete_scenario.php` use `scenarios`). Document as `scenarios`, not `saved_scenarios`.
- **Stripe webhook:** Repo search for `Stripe\Webhook`, `constructEvent`, `stripe-signature` returns **no matches**. There is no webhook handler in the repo. Premium is set only in **`success.php`** when the user returns from Stripe Checkout (see Session / Auth Flow above). When you add a webhook (e.g. for subscription canceled/renewed), put it at root level alongside checkout/success and document the file and events here.

---

## DB config (consolidation rule)

- **Current state:** Two configs exist. **Root `db_config.php`** defines `getDBConnection()` and is used only by **`auth/login.php`** and **`auth/register.php`**. **`includes/db_config.php`** creates **`$conn`** and is used by the rest of the app (index, subscribe, checkout, success, all calculator index.php files, all API scripts).
- **Rule for new code:** Use **`includes/db_config.php`** for app code. Treat root **`db_config.php`** as legacy and a **candidate for deprecation/consolidation** (no deliberate security boundary).

---

## Configuration & secrets (verified from codebase)

- **Where they live now:**  
  - **Stripe:** `includes/stripe_config.php` — defines `STRIPE_PUBLIC_KEY`, `STRIPE_SECRET_KEY`, `STRIPE_PRICE_MONTHLY`, `STRIPE_PRICE_ANNUAL`. File comment says "KEEP THIS FILE OUT OF GIT! Add to .gitignore".  
  - **Database:** `includes/db_config.php` — `$host`, `$dbname`, `$username`, `$password` (XAMPP default empty). Root `db_config.php` uses constants `DB_HOST`, `DB_USER`, `DB_PASS`, `DB_NAME`.  
  - **Usage:** `\Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY)` in `checkout.php` and `success.php`.
- **.gitignore:** Both `includes/stripe_config.php` and `includes/db_config.php` are listed in `.gitignore`. If they appear in the working tree, they are local overrides; production should not rely on committed secrets.
- **Goal:** No secrets in the repo. **To-do:** Migrate secrets to environment variables or a non-committed file (e.g. `.env` not in repo), and load them in PHP from env / one include.

---

## Retirement-app

- **Link:** Present on the main homepage (card/link to the app).
- **Premium:** Should eventually get the same premium pattern (save/load, extended detail, PDF/export) if it becomes a flagship tool. Option: keep it lightweight and public, with premium meaning “extended views / exporting” only.

---

*Last updated from Claude response: repo-wide searches confirmed paths, table names, and config locations. Treat this document as the single source of truth for project context.*
