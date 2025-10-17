Production readiness checklist — Paystub project

1) Objective
  - Ensure the paystub calculation, tax rows, and PDF generation produce correct, persisted output for production use.

2) Quick status (as of current changes)
  - AutoCalcService and CalculationOrchestrator produce per-stub earnings, detailed tax rows, and summary with `fit_taxable_wages`.
  - PdfService.normalizePayload now preserves/injects `fit_taxable_wages` when reconstructing `summary` from DB items.
  - OrdersService updated to persist `fit_taxable_wages` where possible when creating/updating orders.
  - A temporary debug endpoint `public_html/debug_calc_web.php` was added for quick remote inspection (DELETE after use).

3) Pre-flight checks on the server
  1. Ensure `.env` contains correct DB settings (DB_HOST, DB_NAME, DB_USER, DB_PASS) or DB_DSN.
     - Example: DB_HOST=127.0.0.1
                DB_NAME=paystub
                DB_USER=createpa_com
                DB_PASS=secret
  2. Ensure `vendor/` is installed (composer install) and `vendor/autoload.php` is readable by PHP.
  3. Ensure `storage/paystubs` is writable by the web user (mkdir -p storage/paystubs && chown/chmod as needed).

4) Persist a calculation and generate a PDF (recommended immediate test)
  - Two safe ways to do this:
    A) Run a small PHP script on the server (recommended if you have SSH access):
       1) Create `paystub/tests/persist_and_pdf.php` (script will call OrdersService::createOrUpdateFromCalc and PdfService::render).
       2) Run: `php paystub/tests/persist_and_pdf.php` and inspect output for order id and PDF path.
    B) Use an HTTP endpoint to persist+render (only if you cannot run CLI). If used, protect it with a strong token and remove it after use.

5) Verify results
  - In the DB check `orders` and `taxes` tables: taxes rows exist for the order and `orders.taxes_total` equals sum of taxes current_amount for each stub.
  - Open the generated PDF (`storage/paystubs/<orderId>/stub.pdf`) and confirm the Pay Summary shows `FIT Taxable Wages` non-zero and `Taxes` equals the sum of the rows.

6) Production hardening (before going live)
  1. Remove any debug endpoints from `public_html`.
  2. Ensure environment variables are set securely (use hosting env panel or `.env` outside webroot).
  3. Add automated tests: unit test for CalculationOrchestrator and an integration test that persists an order and asserts DB rows + PDF created.
  4. Add logging/monitoring and error reporting (Sentry or similar) for production.
  5. Backup DB and ensure migrations are in source control (if schema changes needed, add SQL migration files).

7) Tax accuracy options
  - If you require legally accurate federal withholding and full W-4 support, implement IRS Publication 15-T logic and W-4 parsing, or integrate a trusted payroll provider/library. This is a larger effort and must be validated against official tables.

8) Rollout checklist
  - Green tests
  - Remove debug artifacts
  - Deploy to production host, test end-to-end with a real order (use a non-production employee email)
  - Monitor logs and user reports for first 24–72 hours

9) Contact points
  - If you want me to: (A) create `persist_and_pdf.php` ready to run, (B) add a secure persist endpoint, or (C) start implementing Publication 15-T — indicate which option and I'll implement it.

--
Generated guidance file for the Paystub project. Keep this file updated as you complete steps.
