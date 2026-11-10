# Architecture

Laravel 11 · PHP ≥ 8.2 · MySQL/MariaDB · Blade · vanilla JS/CSS (no build step, no npm required).

```
app/
  Models/                 Eloquent models (27)
  Services/               business logic — controllers stay thin
    PricingService        the ONLY place prices, coupons, shipping, tax are computed (integer paise)
    CartService           guest (session token) + member carts, stock checks, merge on login
    CheckoutService       transactional order creation, duplicate-submit guard, stock reservation
    InventoryService      reserve / release stock, idempotent via orders.stock_reserved
    OrderService          status transitions + history (admin transitions are whitelisted)
    PaymentService        payment state machine, webhook handling, idempotency
    RazorpayService       REST client + HMAC signature verification (no SDK dependency)
    RefundService         partial/full refunds, restock, Razorpay refund call
    PopularProductsService, ReportService
    NewsletterService, OmnisendService (optional connector)
  Http/Controllers/       storefront controllers, Admin/ (19), Auth/ (3)
  Http/Middleware/        EnsureAdmin, RequirePermission
config/permissions.php    role → permission map (super_admin, store_manager, content_manager, order_support)
config/jbo.php            store rules (statuses, transitions, popular window, upload limits)
database/migrations/      4 migration files · seeders/ clearly-labelled DEMO data
resources/views/          layouts/, store/, account/, auth/, admin/, mail/
public/css, public/js     design system + behaviour; public/images/placeholders (SVG stand-ins)
tests/                    PHPUnit (Unit + Feature), SQLite in-memory
```

## Order lifecycle
```
COD:       placed → processing (cod_pending, stock reserved) → shipped → completed (payment becomes paid)
Razorpay:  placed → pending_payment (unpaid, stock reserved)
              ├─ verified signature / webhook captured → processing (paid)
              ├─ payment.failed → stays pending_payment, payment_status=failed (customer can retry)
              ├─ customer cancel / 60-min expiry (jbo:expire-pending-orders) → cancelled (stock released)
              └─ paid after cancellation → stock re-reserved → processing, or on_hold if stock is gone
refund:    partial → payment_status=partially_refunded; full → status=refunded
```
Paid orders cannot be cancelled by hand; they must be refunded so payment and order state never disagree.

## Where the reference's third-party tools went
| Reference | Here |
|---|---|
| WooCommerce / Elementor / WP-Adminify | Laravel app + custom admin |
| Razorpay | Server-side REST integration, test mode first |
| Omnisend (forms, contact list, campaigns) | Native `newsletter_subscribers` + admin list + CSV export; optional Omnisend contact sync |
| Chatbase chatbot | Chatbase embed if `CHATBASE_BOT_ID` is set, otherwise a built-in FAQ/product search widget (not AI) |
| Google Analytics GA4 | GA4 tag when a measurement ID is configured (viewing reports stays in Google) |

## Not in the reference → not built
Wishlist (no wishlist icon appears in the reference header), downloadable products (page shows "No downloads"), multi-currency.
