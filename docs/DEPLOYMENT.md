# Getting a public URL — honest guidance

I could not create accounts or deploy from here, and my web research returned mostly hosting-company marketing pages, so **I am not claiming any specific provider is free, current or suitable. Verify each item before relying on it.**

## What this app needs from a host
PHP ≥ 8.2 (8.3/8.4 preferred), MySQL/MariaDB, **Composer + SSH** (or the ability to upload a prepared `vendor/` folder), a writable `storage/` + `bootstrap/cache/`, document root pointing at `public/`, HTTPS, and a cron job running `php artisan schedule:run` every minute (expires unpaid orders, retries Omnisend).

## Realistic options (check current terms yourself)
1. **Small paid shared/cloud PHP hosting with SSH + Composer** (search results showed plans from roughly US$3/month with SSH, Composer, MySQL, free SSL, cron). Most reliable path; this is what I recommend for a real shop.
2. **Free-tier VPS** (for example a cloud provider's "always free" VM): you install PHP, MySQL and a web server yourself. Genuinely free tiers exist but usually need a card, have capacity limits, and require Linux administration. Re-check the provider's current free-tier rules.
3. **Free shared hosts (FTP only, no SSH/Composer, older PHP)**: only workable if PHP ≥ 8.2 is available and you upload a locally built `vendor/` folder; no cron means unpaid orders are not auto-expired. Not recommended for payments.
4. **Static/Jamstack hosts (Netlify, GitHub Pages, etc.): will NOT run Laravel.**
Free tiers can sleep, limit storage/databases, or disappear — do not promise permanence to customers.

## Deployment steps (any host)
1. Upload the project (or `git clone`) outside the web root; set the document root to `public/`.
2. `composer install --no-dev --optimize-autoloader`
3. Copy `.env.example` → `.env`, then set: `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://your-domain`, DB credentials, `SESSION_SECURE_COOKIE=true`, SMTP, `POPULAR_INCLUDE_DEMO=false`. **Never commit/upload `.env` publicly.**
4. `php artisan key:generate` (once) → `php artisan migrate --force` (do **not** run the demo seeder in production) → `php artisan storage:link` → `php artisan jbo:create-admin you@domain.com --role=super_admin`
5. `php artisan config:cache && php artisan route:cache && php artisan view:cache`
6. Cron: `* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1`
7. `php artisan jbo:doctor` → every line should be ✔.
8. Remove the seeded `*@jboptician.test` users; use test Razorpay keys until a full test order works; then switch to live keys.
9. Backups: `mysqldump` + copy `storage/app/public` + keep `.env` (APP_KEY encrypts stored secrets).
