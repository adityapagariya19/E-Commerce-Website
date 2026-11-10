# JB Optician: Reference Audit (Milestone 1)

Sources: `spectacle_shopping_website_screenshots_s_.pdf` (21 screens) and `VID-20260419-WA0275_1.mp4` (848x478, 5m26s, sampled every 10 s = 33 frames; contact sheets reviewed).
The reference site is WordPress + WooCommerce + Elementor (admin shows "WooCommerce Setup", "Elementor Overview", "WP Adminify"). Currency is INR (₹). Store is in Chhatrapati Sambhajinagar (Aurangabad), Maharashtra.

## Storefront pages

| # | Page | Source | Section order / contents |
|---|------|--------|--------------------------|
| 1 | Home | PDF p1-3, video | Announcement ticker ("FREE SHIPPING IN INDIA. ORDERS ₹2000+", "WELCOME TO JB OPTICIAN STORE", "SIGN UP & ENJOY 10% OFF") → header (logo, Home/About Us/Shop▾/All Products/Try On/Contact Us, search, account, cart) → hero (video shows ≥2 slides: contact lens/eye, sunglasses; text "Exclusively from JB Optician / WE'RE PROUD TO INTRODUCE / JB Optician a online store", blue pill CTA "SHOP WITH US") → "PASSION FOR FASHION" / "Shop By Categories" → 4-tile collage (large left "John Jacobs × Squid Game 2"; upper right "Sunglasses for women"; two small lower right) → "LET'S SHOP / New Arrivals" (4 cards, -12%, -100%, -19%, -14% badges) → promo banner (shopfront photo, "Build up your spectacle today with JB Optician™", "A new revolution. A new perspective.", SHOP NOW) → trust row (Free Shipping on orders over $200 [sic, currency mismatch with ₹2000 ticker], Dedicated Support, Money-Back Guarantee) → "TRENDING NOW / Popular This Week" (4 cards) → "STAY UP-TO-DATE / Follow Us On Instagram" (5 tiles) → footer |
| 2 | Footer | PDF p3 | JB Optician + "Your vision is our mission" + social icons; Follow Us (Instagram, Facebook, Youtube); Support (Contact Us, Help Center, Privacy Policy); Newsletter "DON'T MISS OUT" + email + SUBSCRIBE; "© 2025 JB Optician" . Video: after subscribe, "You're all signed up! You'll start to receive discounts by email and phone." (Omnisend popup) |
| 3 | About Us | video | "About **JB Optician**" (brand word in blue), intro paragraphs, "Visit Our Store" with address |
| 4 | Contact Us | PDF p3, video | Name/Email/Message form + full-width blue Submit; "Our Location" Google map with pin and 4.9★ 48 reviews card |
| 5 | Help Centre | PDF p9, video | Search box, topic list (Order Issues, Shipping Information, Returns & Refunds, Account Issues, Payment Issues, Technical Support), FAQ links per topic, Contact Us (Email Us blue / Call Us grey / Visit Our Contact Page green), Feedback form |
| 6 | Login/Register | PDF p4 | Modal: Login (username/email, password, remember me, lost password) + Register (email; "link to set password will be emailed") |
| 7 | All Products / Shop | PDF p4, video | Sidebar: search, category counts, price filter slider (₹0 – ₹4,500) + Filter, Product Tags; "Showing 1-6 of 33 results", sort dropdown (default, price high to low…), 3-col grid, sale badges, star ratings, "Read more" for no-price items, **View more** button |
| 8 | Category | video | Same shop layout filtered ("Showing all 4 results", Accessories) |
| 9 | Try On | PDF p5, video | Rows: frame image + **Try Now** + **Buy Now** buttons. Try-on mechanism NOT visible (no camera/AR frame in video) → assumption below |
| 10 | Product | PDF p5, video | Breadcrumb, prev/next, thumbnails (9), zoom icon, sale badge, title, strike price + sale, qty + ADD TO CART, categories, "GUARANTEED SAFE CHECKOUT" badges, Description/Reviews tabs, Related products. Video: star rating form (rating, review, name, email, SUBMIT; "Please select a rating" validation) |
| 11 | Cart | PDF p6, video | Items table, qty +/-, Remove item, coupon, subtotal, delivery FREE, "Delivers to MAHARASHTRA, INDIA", total, Proceed to Checkout; cart slide-out drawer ("SHOPPING CART") |
| 12 | Checkout | PDF p10, video | 3-step (Billing Details / Delivery / Payment); contact info (guest, create account, email offers), shipping address (India, state Maharashtra, PIN, phone), shipping options (Free), payment options (**Cash on delivery**, **Pay by Razorpay**: "Pay securely by Credit or Debit card or Internet Banking through Razorpay"), add note, T&C line, Return to Cart, Place Order, order summary |
| 13 | Razorpay modal | PDF p11, video | UPI QR, Cards, Netbanking, Wallet, Pay Later, "Verify and Pay" |
| 14 | Order Placed | PDF p11, video | Success banner, order number, date, total, payment method, order details table, billing & shipping address; video also shows a "Pay Now / Cancel" pending state |
| 15 | My Account | PDF p12, video | Sidebar Dashboard / Orders / Downloads / Addresses / Account details / Log out; orders table (Order, Date, Status, Total, View); account details form incl. password change |
| 16 | Chatbot | PDF p6, video | Floating black launcher bottom-left; panel "JB Optician" + "Hi! What can I help you with?"; answers about products; "Powered By Chatbase.co" |
| 17 | Floating UI | video | Blue back-to-top square bottom-right; chat launcher bottom-left; newsletter popup |

## Admin / third-party screens

| Page | Notes |
|------|-------|
| Admin login | Full-bleed glasses photo, centred logo, dark/glass form, pink-red "Log In" button, footer "© 2025 JB optician" |
| Admin dashboard | WP-Adminify dark/light UI: left sidebar (Dashboard, Posts, Media, Pages, Comments, WooCommerce, Products, Payments, Analytics, Marketing, Templates, Users); widgets |
| Add Product | Name, description (visual/text), product data (simple/variable, regular/sale price, inventory), publish box, product image, gallery, categories, brands, tags, product video URL |
| Add Page | Block editor + "Edit with Elementor" |
| Razorpay dashboard | Reference is the merchant's own Razorpay account (Live Mode, 95% activation). Not rebuilt; integrated only |
| Google Analytics | GA4 property `jboptician.ct.ws`: external. Integration = GA4 tag in layout, configurable ID |
| **OmniSend** ("Omniset" in the brief) | **Resolved:** Omnisend is an email/SMS marketing service. It supplies the newsletter popup, contact list (9 contacts, 3 email subs, 1 SMS sub), campaigns/automation/forms/audience/reports. Plan: native `newsletter_subscribers` table, admin audience list, CSV export, and an optional Omnisend adapter (API key setting) |

## Facts that cannot be verified (recorded as assumptions)
1. Fonts: look like Poppins (headings/body); to confirm against live CSS.
2. Exact hex values: blue CTA ≈ #1a47b8, ticker bar black, footer bg ≈ #ebebeb. Sampled by eye from the PDF; to be sampled from pixels.
3. Try-On mechanism: only frame rows + buttons are visible. Plan: Try Now opens a webcam overlay (browser `getUserMedia`, client-side only, no image upload). Unverified against reference.
4. Trust-row copy ("$200", "Money-Back Guarantee") conflicts with the ₹2000 ticker; will be admin-editable, seeded as clearly marked placeholders.
5. Original image assets are only available as low-res PDF renders. Seed images will be labelled placeholders.
6. Video sampled at 10 s; short transitions (carousel timing, drawer animation) not measured.
7. Mobile layout is not shown anywhere in the references.

## Environment findings (affects testing)
- Sandbox has Node, ffmpeg, apt (PHP 8.3 installable). **Packagist/Composer hosts are blocked**, so `composer create-project laravel/laravel` cannot run here.
- Consequence: Laravel code written in this sandbox cannot be booted or have its test suite run here. Everything delivered must be labelled *implemented, not yet executed* until run on a machine with Composer.

## Implementation mapping (added in Milestone 7)
| Audit item | Implemented as | Verified? |
|---|---|---|
| Home (ticker, header, hero, collage, New Arrivals, promo, trust, Popular, Instagram) | `store/home.blade.php`, `HomeController`, admin Hero slides + Homepage blocks | not run |
| Footer + newsletter | `layouts/store.blade.php`, `NewsletterController`, admin Newsletter | not run |
| About Us / Contact / Help Centre | `PageController`, `ContactController`, `HelpController` + admin Pages/FAQ/Messages | not run |
| Login/Register modal | `store/partials/login-modal.blade.php`, `CustomerAuthController` | not run |
| All Products / Category | `ShopController` (filters, sort, View more) | not run |
| Try On | `TryOnController` + webcam overlay | **unverified assumption** |
| Product (gallery, variants, reviews, related, prev/next) | `ProductController`, admin Products/Reviews | not run |
| Cart (+drawer), Checkout, Razorpay, Order Placed | `CartController`, `CheckoutController`, `PaymentController`, services | not run; Razorpay untested |
| My Account | `AccountController` | not run |
| Chatbot | Chatbase embed or built-in widget | not run |
| Admin login/dashboard/add product/add page | admin panel (Add Page → Admin › Pages) | not run |
| Razorpay dashboard / GA / Omnisend | external; integrated via keys/IDs; Omnisend → native newsletter + optional sync | not run |
