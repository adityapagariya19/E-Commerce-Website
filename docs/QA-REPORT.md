# QA report — what was actually run

Environment used for testing: Ubuntu sandbox, **PHP 8.3.6**, **MariaDB (MySQL-compatible)**, Laravel **11.57.1**, PHPUnit 11.5, GD with WebP + EXIF.
Packages were assembled directly from GitHub (Composer's package index was blocked), so the tested `vendor/` is equivalent but not produced by `composer install`.
Nothing below is fabricated; items I could not test are listed at the end.

## 1. Automated test suite — `php artisan test`
**Result: 116 tests, 626 assertions, 0 failures** (final run on the packaged files).

| Area | Tests | Result |
|---|---|---|
| Every Blade view compiles to valid PHP; no inline/block `@php` mixing (the old ParseError class) | 1 (all 66 views) | PASS |
| Pricing: sale price, coupons (percent/fixed/cap/min/expiry/category/usage-limit), shipping threshold, tax | 8 | PASS |
| Cart: stock limits, browser-supplied price ignored, variants, no-price products, guest→member merge | 5 | PASS |
| Checkout: COD order, stock reservation, cart cleared, duplicate-submit guard, stale stock, validation, coupon, Razorpay order created pending/unpaid | 10 | PASS |
| Razorpay flow: signature OK/forged, wrong-owner 404, webhook bad signature 400, duplicate event idempotent, amount mismatch rejected (no retry storm), failed→retryable, late payment re-reserves stock / on_hold | 10 | PASS |
| Popular This Week: window, tie-breaks, excluded statuses, demo flag, refunds, variants, fallback label, cache refresh | 9 | PASS |
| Admin access: guests, customers, role matrix (4 roles × many URLs), last-super-admin guard, encrypted secrets, URL/map validation, activity log | 9 | PASS |
| Admin catalogue: product CRUD/validation/variants, upload accept/reject (php, svg, oversize, tiny, fake), hero CRUD, homepage edits without code | 6 | PASS |
| **Image pipeline**: resize to 1200/1920, aspect ratio, WebP twin, 480px thumb, no upscaling, **EXIF phone rotation**, PNG alpha path, replace deletes all variants only, edit keeps image, homepage `<picture>`+WebP+fetchpriority, URL really serves, missing-file fallback, path-traversal blocked, corrupt file rejected | 9 | PASS |
| Auth: register (name/email/password/confirm), hashing, auto-login, welcome mail, mail failure tolerated, validation messages, cart follows user, return-to-checkout after login/register, open-redirect blocked, remember-me cookie, logout, **password reset end-to-end (single-use token)**, session id rotation | 11 | PASS |
| Customer account: own orders only, confirmation needs key/owner, login required, inactive blocked, address privacy, password change, cancel rules | 7 | PASS |
| Orders/refunds: paid-order cancel blocked, COD paid on delivery, stock/coupon release idempotent, partial + full Razorpay refund (HTTP faked), over-refund blocked, expiry job, reports/CSV exclude demo | 7 | PASS |
| Tracking: full courier workflow + timestamps, tracking details validated & shown to owner only, no fake tracking, cancel eligibility, receipt privacy, new statuses counted | 6 | PASS |
| Newsletter/Omnisend: consent required+recorded, native list without key, sync success/fail/retry/5-attempt cap, 409 duplicate, admin retry, CSV export + formula neutralised, key never in HTML | 12 | PASS |
| Try-On: host allow-list (http, look-alike hosts, javascript:), page embeds product URL with camera permission, fallback overlay | 2 | PASS |
| Contact/Help/Chat, shop filters/sort/search/visibility/load-more, reviews moderation | 11 | PASS |

Bugs **found by running the code and fixed** in this pass: `Undefined array key "slug"` crash when saving products/categories/brands/pages in admin; Blade `@php` mixing that broke `layouts/store.blade.php` (the reported ParseError) and a second occurrence I had just introduced in `account/order.blade.php`; price filter wrong on SQLite (PDO float binding); two payment rows per online order; `consent_at`/`synced_at` not cast to dates (Omnisend sync silently failing); newsletter `Help`/layout `$ticks` undefined.

## 2. Real MySQL/MariaDB + real HTTP server (end-to-end script)
`migrate:fresh --seed` on MariaDB: **OK** (4+1 migration files, all seeders).
Real `php artisan serve` + curl with cookie jars — **58 checks, 58 passed, 0 failed, 0 ERROR lines in laravel.log** (run twice, identical):
* 19 public pages return 200 with no PHP/Blade errors (home, shop + filters, categories, product, variable product, about, contact, help, try-on, cart, login, forgot-password, policy pages, search suggest, admin login, health).
* Register (redirect to /checkout) → account page → add to cart (server total) → COD checkout → confirmation → **duplicate submit = one order** → stock 20→18 → order history/detail/receipt → another customer's order 404 → customer blocked from /admin.
* Admin login → **all 23 admin pages 200**.
* **Real multipart hero upload** (3200×1200): homepage references the new image + WebP; file served as `image/jpeg` / `image/webp`; downscaled to 1920 px; smaller than the original.
* **Symlink removed → image still served** (the usual "broken image" cause); `../` traversal 404; `jbo:doctor` detects the missing link; `--fix` recreates it.
* Product create with 2400×1600 upload → shop card uses `-thumb.jpg`, 480 px.
* Newsletter: no consent 422, with consent 200, stored with consent + `skipped` sync (no key), CSRF enforced (419).

## 3. Other checks
* `php -l` on every PHP file: 0 errors. Database browser script: Adminer 6.3.0 downloaded and served (login page HTTP 200).

## 4. NOT tested — please be aware
| Item | Why | What you need |
|---|---|---|
| PHP **8.5** itself | sandbox has 8.3. The PDO deprecation fix (`config/database.php`, uses `Pdo\Mysql::ATTR_SSL_CA` on 8.5+) is written but was not executed on 8.5 | run `php artisan jbo:doctor` and `php artisan test` on your Mac |
| `composer install` / `composer.lock` | Packagist blocked; no lock file could be produced | run `composer install` once |
| Razorpay **live sandbox** payment | needs your `rzp_test_` keys; HTTP was faked in tests | add keys → place a test order → register webhook |
| Omnisend **live** | needs your API key; request shape from documentation memory | add key, subscribe a test email, check Omnisend |
| Chatbase **live** | needs your Bot ID | paste Bot ID in Settings |
| **MyWebAR / 3D try-on** | your 3D models, MyWebAR project URLs and any embed code were **not provided** to me, so I could not inspect, identify or test them. I built the integration point only (per-product URL, host allow-list, iframe with camera permission). Camera/AR alignment on a phone is untested | paste each product's MyWebAR link in Admin › Products |
| Real email (SMTP) | needs your SMTP details | see docs/INTEGRATIONS.md |
| Browser visual comparison with the PDF/video; tablet/phone layouts; console errors in a real browser | no browser in the sandbox | open the site on desktop + phone and compare |
| Public deployment / free hosting | I cannot create accounts or deploy | docs/DEPLOYMENT.md |
| **WordPress/WooCommerce version** | **not built** — see docs/WORDPRESS-STATUS.md | — |
