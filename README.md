# EazyPOS (Laravel + MySQL)

EazyPOS moved from a static HTML + Supabase build to **Laravel 12 + MySQL**.
The screens look and behave like the original. All data, business rules and
permissions now live on your own server.

## How it is built

| Layer | Where | Notes |
|---|---|---|
| Landing, contact, sign-in, invitations | `resources/views/landing.blade.php`, `inquiry.blade.php` | Original markup and styles. Public sign-up is replaced by **Request an account** (saved for your team). |
| POS app shell | `resources/views/pos/app.blade.php` | Original layout. |
| POS screens | `public/js/pos/01-…11-*.js` | The original screens, split per module. They talk only to the Laravel API through `00-api.js`. |
| Styles | `public/css/app.css` | Original CSS. |
| API | `routes/web.php` (`/api/*`), `app/Http/Controllers/Api` | Session + CSRF protected, validated, rate limited. |
| Business rules | `app/Services/*` | PHP versions of the Supabase functions (`complete_sale`, `update_sale`, returns, purchases, expenses, registers, shipments, staff). Each runs in one database transaction with row locks. |
| Access control | `app/Http/Middleware/EnsureBusinessMember.php`, `app/Support/BusinessContext.php` | Every request is tied to the signed-in user's own business. The browser can never pick another business. |
| Team console | `/admin` (`app/Http/Controllers/Admin`) | Create a business once the customer has paid, suspend or reactivate it, reset an owner password, read inquiries. |

Money is calculated in integer cents (`app/Support/Money.php`), so totals never drift.

## Local setup (XAMPP)

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed          # optional demo shop, local only
php artisan serve
```

Demo logins created by the seeder are listed at the top of
`database/seeders/DatabaseSeeder.php`. The seeder refuses to run in production.

Create your real team login for the Team Console:

```bash
php artisan eazypos:create-admin you@yourdomain.com
```

## Who can sign in

* **EazyPOS team** (super admin) signs in on the home page and lands on `/admin`.
* **Business owners** are created by the team in `/admin` after payment is agreed.
* **Staff** are invited by the owner from *User Management*. Until email is set
  up, the invitation link is shown on screen so the owner can send it by WhatsApp or SMS.
* Members can sign in with email or username. Five failed attempts lock that login for a minute.
* Suspending a business signs its users out right away.

## Moving the data from Supabase

Users keep their passwords, and every record keeps its original ID and document number.

**Option A – JSON export (no extra software)**
1. In Supabase, open *SQL Editor*, paste `database/supabase/export.sql` and run it.
2. Copy the `eazypos_export` value into `storage/app/private/supabase-export.json`.
3. Check it first, then import:
   ```bash
   php artisan eazypos:import-supabase storage/app/private/supabase-export.json --dry-run
   php artisan eazypos:import-supabase storage/app/private/supabase-export.json
   ```
4. Delete the JSON file afterwards (it contains password hashes).

**Option B – direct connection**
Enable `extension=pdo_pgsql` in `php.ini`, put `SUPABASE_DB_URL` in `.env`, then run:

```bash
php artisan eazypos:import-supabase --pgsql
```

The import can be run again safely: it updates existing rows instead of
duplicating them. Pending Supabase invitations cannot be redeemed here, so they
are marked expired and owners can simply invite those staff again.

## Deploying to Spaceship (cPanel shared hosting)

1. **PHP:** in cPanel, choose *Select PHP Version*, pick **PHP 8.2 or newer**, and enable
   `pdo_mysql`, `mbstring`, `openssl`, `fileinfo` and `bcmath`.
2. **Database:** in *MySQL Databases*, create a database and a user, and give that user ALL privileges.
3. **Build locally:** run `composer install --no-dev --optimize-autoloader`, then zip the project.
   Leave out `.env`, `node_modules` and `tests`.
4. **Upload:** put the project outside the web root, e.g. `/home/USER/eazypos`.
   * If you can point the domain's document root at `/home/USER/eazypos/public`, do that.
   * Otherwise copy the contents of `public/` into `public_html/` and edit
     `public_html/index.php` so its two `__DIR__.'/../…'` paths point to `/home/USER/eazypos/…`.
5. **`.env` on the server:** copy `.env.example`, then set:
   * `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://yourdomain`
   * the `DB_*` values from step 2
   * `SESSION_SECURE_COOKIE=true`, `LOG_LEVEL=error`
6. **Initialise** (cPanel *Terminal*):
   ```bash
   php artisan key:generate --force
   php artisan migrate --force
   php artisan eazypos:create-admin you@yourdomain.com
   php artisan config:cache && php artisan route:cache && php artisan view:cache
   ```
   With no terminal access, run the steps locally against a copy of the database,
   then import it with phpMyAdmin.
7. **Permissions:** `storage/` and `bootstrap/cache/` must be writable (755 for folders, 644 for files).
8. **HTTPS:** turn on SSL for the domain (AutoSSL) before going live.

**Updating later:** upload the changed files, run `php artisan migrate --force`
if there are new migrations, then `php artisan optimize:clear && php artisan optimize`.

## Email (when you have SMTP details)

Fill in the `MAIL_*` values in `.env`; there is an example in `.env.example`. Then run
`php artisan config:cache`. Invitations will then be emailed instead of shown on screen.

## Tests

The tests need a MySQL database named `eazypos_test` (see `phpunit.xml`).

```bash
php artisan test
```

They cover cash, credit and partly-paid sales, sale edits, deletes and returns,
purchases and purchase returns, expenses, register balances, stock adjustments,
permissions, isolation between businesses, sign-in, the team console, staff
invitations and the Supabase import.

## Fixes made during the move

These were bugs in the Supabase build, fixed here:

* **Register totals:** the expected cash in the register did not include the newest
  cash expense or sale return. It is now recalculated from every bucket.
* **COD delivery:** marking a COD shipment delivered used a transaction type the
  database rejects. It now posts correctly.
* **Editing a sale** that had nothing left to pay used a payment status the
  database rejects. Card or bank sales could not be edited at all. Both now work.
* **Over-restoring stock:** deleting a sale or purchase after a return put back
  more stock than was actually out. It now restores only the remaining quantity.
* **Product stock edits** wrote an invalid stock movement type.
* **Cashiers** could not see products, so the POS was empty for them. They can now
  see products to sell, while editing products still needs the Products permission.
* **Screen fixes:**
  * Product *Edit* buttons now open the product.
  * Register *Open* links in reports now work.
  * The invoice subtitle no longer shows `${…}`.
  * The import screens no longer show "undefined".
  * Already-returned quantities now show on the return screen.
  * The dashboard no longer counts cancelled sales.
  * Stock adjustment history is now shown.
* **Settings:** business name, currency, tax % and receipt footer are saved to the database.
  The receipt footer prints on invoices; tax % is stored but not yet applied to sale totals,
  the same as the original.
* **Quotations** were kept only in the browser and vanished on reload. They are now saved
  with numbers QUO-000001, QUO-000002, ... (same screen and form as before).

Modules that were placeholders in the original are kept exactly as they were:
Notification Templates, Payment Accounts, Variations and the import screens. Online/COD selling on the POS also stays disabled, as in the last build.
# eazypos.store
