# "Popular This Week" — rules

Implemented in `App\Services\PopularProductsService` (storefront + dashboard) and inspectable at **Admin › Popular This Week**.

| Question | Rule |
|---|---|
| Window | Rolling 7 days: orders with `placed_at >= now() − 7 days` (`config/jbo.php › popular.window_days`) |
| Which orders count as sales | status ∈ `processing`, `shipped`, `completed`. Online orders only reach `processing` after a **verified** Razorpay payment; COD orders are `processing` on placement |
| Excluded | `pending_payment`, `on_hold`, `cancelled`, `failed`, `refunded` |
| Refunds | units = `qty − refunded_qty` per line. Partial refunds reduce the count; a fully refunded line (or order) drops out |
| Variants | roll up to the parent product |
| Ranking | units ↓ → revenue ↓ → most recent sale ↓ → product id ↑ (deterministic) |
| Demo data | orders flagged `is_demo = 1` count only if `POPULAR_INCLUDE_DEMO=true` (default **false**; `.env.example` sets true for local testing). The storefront then shows a label |
| Not enough data | if fewer than 4 products qualify, the remaining slots are filled with featured/newest published products flagged `fallback`; the storefront shows a visible note ("Not enough sales…" / "topped up") |
| Caching | 10 minutes; cache is cleared on checkout, payment, status change, cancellation, refund |
| Unpublished products | never shown, even if they sold |

Covered by `tests/Feature/PopularProductsTest.php` (not yet executed — see STATUS.md).
