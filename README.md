# Laravel authentication pages

Sign-in, forgot-password and reset-password screens with login throttling.

**Stack:** Laravel 7.30.7 on PHP 7.2.34, SQLite. Built for PHP 7.2 by request — see
[Security note](#security-note).

## Running it

```bash
php artisan serve
```

Then open <http://127.0.0.1:8000>. `/` redirects to `/login`. No npm install and no asset build —
the styling is plain CSS.

## Account

| Email | Password |
| --- | --- |
| `admin@gmail.com` | `12345` |

Seeded by `database/seeds/DatabaseSeeder.php`. Rebuild the database at any time:

```bash
php artisan migrate:fresh --seed
```

`12345` is five characters. Nothing at sign-in enforces a minimum, but the reset-password form
requires eight, so this account cannot set the same password again through *Lupa Password*.

## Login throttling

Five failed attempts for a given **email + IP** pair open a **59 second** lockout. During the
lockout the form is disabled and shows a live countdown, then re-enables itself at zero without a
page reload. Both numbers live in one place — `App\Http\Requests\Auth\LoginRequest`:

```php
public const MAX_ATTEMPTS = 5;
public const LOCKOUT_SECONDS = 59;
```

Two details worth knowing about the implementation:

1. **Two rate-limiter keys, not one.** `RateLimiter::hit()` only sets a key's timer on its *first*
   hit, so a single shared key would time the cooldown from failure #1 and release early — five
   quick failures would leave you locked out for well under 59 seconds. A dedicated lockout key
   opens a full window at the moment of the fifth failure.

2. **No `RateLimiter` facade in Laravel 7** (it arrived in Laravel 8), so `Illuminate\Cache\RateLimiter`
   is resolved from the container instead.

A successful sign-in clears both keys. Throttling is scoped per email, so one attacker cannot lock
every account from a single address.

## Password reset

`MAIL_MAILER=log`, so no SMTP setup is needed — the reset email is written to
`storage/logs/laravel.log`. Grab the link with:

```bash
grep -o 'http://[^ ]*reset-password/[^ "]*' storage/logs/laravel.log | tail -1
```

Point the `MAIL_*` keys in `.env` at a real mailer to send properly. Repeat reset requests are
throttled for 60 seconds by the password broker (`config/auth.php` → `passwords.users.throttle`).

## Interface language

The screens are in Indonesian, and `config/app.php` sets `'locale' => 'id'` so pages render as
`<html lang="id">`. There is no `resources/lang/id` directory and `fallback_locale` stays `en`, so
framework strings still resolve — but they resolve to **English**. That covers the
failed-credentials and throttle messages (`resources/lang/en/auth.php`) and Laravel's validation
messages. To translate those too, add `resources/lang/id/{auth,passwords,validation}.php`.

## Layout

```
app/Http/Requests/Auth/LoginRequest.php     validation + throttling
app/Http/Controllers/Auth/                  login, forgot-password, reset-password
resources/views/layouts/auth.blade.php      shared shell
resources/views/auth/                       login, forgot-password, reset-password
resources/views/dashboard.blade.php         post-login page
public/css/auth.css                         all styling, no build step
tests/Feature/Auth/LoginTest.php            auth + throttling coverage
```

## Tests

```bash
vendor/bin/phpunit
```

17 tests / 67 assertions.

## Environment change made for this project

PHP 7.2 shipped with Laragon had `pdo_sqlite` disabled, so SQLite could not connect. These two
lines were uncommented in `C:\laragon\bin\php\php-7.2\php.ini`:

```ini
extension=pdo_sqlite
extension=sqlite3
```

The original file was backed up to `php.ini.bak-before-claude`. The change is additive — it only
adds a database driver, so it cannot affect your other PHP 7.2 projects.

## Security note

PHP 7.2 reached end of life in **November 2020** and Laravel 7 in **March 2021**. Neither receives
security patches, and this is authentication code handling password hashes. PHP 8.3.16 is already
installed at `C:\laragon\bin\php\php-8.3.16` if you later want to move to a supported stack —
switch it via the Laragon tray icon → *PHP* → *Version*.
