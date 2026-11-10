# Integrations — what you must supply

| Service | Where to configure | What you need | Notes |
|---|---|---|---|
| **Razorpay** | Admin › Settings (or `.env`) | Key ID `rzp_test_…`, Key Secret, Webhook secret | Register `https://<your-site>/webhooks/razorpay` with events `payment.captured, order.paid, payment.failed, refund.processed`; enable auto-capture. Secrets are stored encrypted; never sent to the browser. Test with test keys first. |
| **Omnisend** | Admin › Settings › Omnisend API key | API key with permission to create contacts | Signup always saves locally first. Status per subscriber: pending/synced/failed/skipped, visible in Admin › Newsletter. Failed syncs retry hourly (`jbo:sync-omnisend`, needs the scheduler) or via the Retry button; max 5 attempts. Footer form has a consent checkbox (consent time + IP stored). CSV export always works. The API request format is from Omnisend's v3 docs as I remember them — verify with one test email. |
| **Chatbase** | Admin › Settings › Chatbase bot ID | Bot ID from chatbase.co | Embeds the official script once. Without a Bot ID a small built-in Help-Centre search widget is shown (not AI). |
| **MyWebAR (try-on)** | Admin › Products › "AR Try-On project URL" per product; Settings › Try-On allowed hosts | Each product's MyWebAR project/share link | MyWebAR is my assumption from your message — I could not verify it from files (none supplied). If MyWebAR needs a paid plan or blocks iframes for your project, use its "open in new tab" link or tell me the embed method. Camera needs HTTPS (localhost is OK). |
| **Google Analytics 4** | Settings › GA4 ID | `G-XXXXXXXXXX` | Tag only. |
| **Email (SMTP)** | `.env` `MAIL_*` | SMTP host/port/user/password, from-address | Default `MAIL_MAILER=log` writes emails to `storage/logs/laravel.log`. Welcome / order / contact emails fail gracefully if SMTP is wrong. |
| **Google Map** | Settings › Map embed URL | Google Maps → Share → Embed → copy the `src` | Only `https://www.google.com/maps/embed…` accepted. |
