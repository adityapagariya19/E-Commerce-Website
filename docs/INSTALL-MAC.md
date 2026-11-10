# Mac installation, local DB browser, troubleshooting

## Install
See START-HERE.md. Alternatives to Homebrew: Laravel **Herd** (bundles PHP + Composer) and **DBngin** or MySQL from mysql.com.

## Browse the database in your browser
```bash
bash tools/db-browser.sh          # downloads Adminer once, serves http://127.0.0.1:8081
```
* System **MySQL**, Server **127.0.0.1**, your MySQL username/password, Database **jb_optician** — you type the password in the browser; nobody else sees it.
* You can view tables and relations, edit records, run SQL, Export (SQL backup) and Import.
* It listens on 127.0.0.1 only. **Never run it on a public server.** Stop with Ctrl+C.
* Alternatives (GUI apps): **TablePlus**, **Sequel Ace** (free).
* Install phpMyAdmin instead if you prefer: `brew install phpmyadmin` — Adminer was used here because it is one file and was verified to run.

## Backup / restore (local)
```bash
mysqldump -u root -p jb_optician > ~/jb_optician-$(date +%F).sql
mysql -u root -p jb_optician < ~/jb_optician-YYYY-MM-DD.sql
tar czf ~/jbo-uploads-$(date +%F).tgz storage/app/public
```

## Troubleshooting
| Problem | Fix |
|---|---|
| Image shows broken icon | `php artisan jbo:doctor --fix`; check `APP_URL`; re-upload if the file is missing on disk |
| `Access denied for user` | fix `DB_USERNAME`/`DB_PASSWORD` in `.env`, then `php artisan config:clear` |
| Page shows old settings | `php artisan optimize:clear` |
| `Class "Pdo\Mysql"`/Deprecated warnings on PHP 8.5 | this project ships its own `config/database.php`; run `composer install` again and `php artisan config:clear` |
| Blade `ParseError` | `php artisan view:clear` then `php artisan test --filter=BladeCompilationTest` names the exact file |
| Port busy | `php artisan serve --port=8001` and set `APP_URL` to match |
| Emails not arriving | default mailer writes to `storage/logs/laravel.log`; set `MAIL_*` |
| Camera/try-on blocked | needs HTTPS or `localhost`/`127.0.0.1`; allow camera permission in the browser |
