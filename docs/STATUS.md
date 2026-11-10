# Status matrix (updated after the repair/verification pass)

See `QA-REPORT.md` for the evidence. Legend: ✅ run and passing in the test environment · 🔒 needs your credentials · ⚠️ not testable here

| Area | Status | Notes |
|---|---|---|
| Boot, migrations, seeders (MariaDB) | ✅ | fresh + existing-DB safe (additive migrations) |
| Automated tests | ✅ | 116 tests / 626 assertions |
| End-to-end HTTP walkthrough | ✅ | 58/58 checks, no errors in log |
| Blade ParseError | ✅ fixed | root cause: inline `@php()` mixed with `@php…@endphp`; guarded by `BladeCompilationTest` |
| PHP 8.5 PDO deprecation | ⚠️ | fix in `config/database.php`; run on your 8.5 to confirm |
| Registration / login / reset / redirects | ✅ | |
| Cart, checkout, COD | ✅ | |
| Order tracking + statuses + receipt | ✅ | pending_payment → processing → confirmed → packed → shipped → out_for_delivery → delivered (+ cancelled/failed/refunded/on_hold, legacy `completed`) |
| Image upload → optimise → display | ✅ | GD: resize, EXIF rotate, WebP + fallback + thumbnails, `/storage` fallback route, `jbo:doctor` |
| Admin (all modules) | ✅ | all 23 pages load; CRUD covered by tests |
| Razorpay | 🔒 ✅ | logic + signature + webhook tested with faked HTTP; live test needs your keys |
| Omnisend | 🔒 ✅ | local list, consent, status, retry tested with faked HTTP |
| Chatbase | 🔒 | embed in place; needs Bot ID |
| Try-On (MyWebAR) | 🔒 ⚠️ | per-product URL + iframe + allow-list built; your 3D models/URLs were not provided; AR alignment untested |
| Visual match to PDF/video, mobile/tablet in a real browser | ⚠️ | not testable here |
| WordPress/WooCommerce version | ❌ | not built — `WORDPRESS-STATUS.md` |
| Public deployment | ⚠️ | `DEPLOYMENT.md` |

## Known limitations
* Logo is an inline stand-in; fonts assumed Poppins; colours estimated from the PDF.
* Hero/product images are processed in PHP/GD at upload time (no CDN). Existing images uploaded before this version are served as-is (no WebP/thumbnail) until re-uploaded.
* Tax is one exclusive percentage; no GST invoice PDFs.
* No wishlist (not visible in the reference).
