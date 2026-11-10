# JB Optician — START HERE / यहाँ से शुरू करें

यह **एक ही ZIP** है जिसमें पूरा project है (code, database migrations, images, tests, docs). अलग-अलग files कहीं डालने की ज़रूरत नहीं।
This single ZIP contains the whole project. You do not copy individual files anywhere.

## A) पहली बार / First-time setup (Mac)

1. ZIP को unzip करें → `JB-Optician-FINAL` folder मिलेगा। Terminal में उस folder में जाएँ:
   ```bash
   cd ~/Downloads/JB-Optician-FINAL        # अपने folder का path
   ```
2. ज़रूरी tools (एक बार): PHP 8.2+ (8.5 भी चलेगा), Composer, MySQL
   ```bash
   brew install php composer mysql
   brew services start mysql
   ```
3. Project install:
   ```bash
   composer install
   cp .env.example .env
   php artisan key:generate
   ```
4. `.env` file खोलकर अपना MySQL password डालें (`DB_PASSWORD=`; अगर password नहीं है तो खाली छोड़ें). Database बनाएँ:
   ```bash
   mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS jb_optician CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
   ```
5. Tables + demo data + image link:
   ```bash
   php artisan migrate --seed
   php artisan storage:link
   php artisan jbo:doctor          # सब ✔ होना चाहिए
   php artisan serve
   ```
6. Browser में खोलें:
   * Website: http://127.0.0.1:8000
   * Admin: http://127.0.0.1:8000/admin/login  → `admin@jboptician.test` / `ChangeMe!12345` (**असली use से पहले बदलें**)

## B) आपका database पहले से बना है? / Already have the old database?

**`php artisan migrate:fresh` या `--seed` मत चलाइए** — इससे data मिट जाएगा। सिर्फ़ यह चलाइए (केवल नए columns जोड़ता है, कुछ delete नहीं करता):
```bash
cp -r storage/app/public /tmp/jbo-uploads-backup        # backup
mysqldump -u root -p jb_optician > ~/jb_optician-backup.sql
php artisan migrate
php artisan storage:link
php artisan jbo:doctor
```

## C) Database browser में देखना (phpMyAdmin जैसा)
```bash
bash tools/db-browser.sh        # फिर http://127.0.0.1:8081 खोलें
```
System = MySQL, Server = 127.0.0.1, अपना MySQL user/password डालें, Database = jb_optician. यह सिर्फ़ आपके computer पर चलता है। Details: `docs/INSTALL-MAC.md`.

## D) Tests चलाना
```bash
php artisan test
```

## E) अगर Image टूटी दिखे (broken image)
```bash
php artisan jbo:doctor --fix
```
ज़्यादातर यह `public/storage` link न होने की वजह से होता है। इस version में website खुद भी `/storage/...` serve कर देती है, तो link न हो तब भी image दिखेगी।

## F) ज़रूरी docs
| File | क्या है |
|---|---|
| `docs/QA-REPORT.md` | असली test results — क्या verify हुआ, क्या नहीं |
| `docs/INSTALL-MAC.md` | Mac पर detailed setup + DB browser + troubleshooting |
| `docs/INTEGRATIONS.md` | Razorpay, Omnisend, Chatbase, MyWebAR, SMTP, GA4 — आपको कौन-सी keys देनी हैं |
| `docs/DEPLOYMENT.md` | Public URL / hosting (ईमानदार जानकारी) |
| `docs/WORDPRESS-STATUS.md` | WordPress version की स्थिति |
| `docs/STATUS.md`, `README.md` | पूरी technical जानकारी |
