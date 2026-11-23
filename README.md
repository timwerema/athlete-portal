# Athlete Profiles (working name)

A private recruiting profile for college athletes. An athlete builds one clean profile and shares it
with coaches through a secret link they control. Version one is for **current college soccer players
aged 18 or over**.

**Independent service.** This is not the NCAA Transfer Portal and is not affiliated with the NCAA, the
NAIA, the NJCAA, any conference or any school.

Built by Timasi Werema.

- What is done, tested and still open: [PROJECT_STATUS.md](PROJECT_STATUS.md)
- Threat model, controls and remaining risks: [SECURITY.md](SECURITY.md)

## Stack

Laravel 12, Inertia 2, React 19 + TypeScript, Tailwind 4, shadcn/ui parts, Vite. SQLite for local
work; migrations also run on PostgreSQL and MariaDB.

## Run it locally

Needs PHP 8.3+ (with gd, exif, zip, sqlite), Composer and Node 22.

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed     # the seeder adds one labelled sample profile at /demo
npm run build                  # or: npm run dev
php artisan serve
```

Email uses the log mailer in development: verification links and coach messages are written to
`storage/logs/laravel.log`.

## Checks

```bash
php artisan test        # back-end tests
npm test                # front-end unit tests
npm run types           # TypeScript
npm run lint:check      # ESLint
npm run format:check    # Prettier
vendor/bin/pint --test  # PHP code style
composer audit && npm audit --omit=dev
```

## How it is organised

| Where | What |
| --- | --- |
| `app/Sports/` | Everything sport-specific (positions, stat columns). A new sport is a new class here. |
| `app/Services/ProfilePresenter.php` | The only place that decides what a coach is sent. |
| `app/Services/PhotoStorage.php` | Photo checking and re-encoding. |
| `app/Services/VideoEmbed.php` | The video embed allow-list. |
| `app/Services/ViewCounter.php` | How visits are counted. |
| `app/Http/Middleware/SecurityHeaders.php` | Content-Security-Policy and other headers. |
| `resources/js/pages/share/show.tsx` | The coach-facing page. |
| `resources/js/pages/profile/edit.tsx` | The profile builder. |

## Before going live

Not deployed anywhere, and hosting is not chosen. When it is: set `APP_ENV=production`,
`APP_DEBUG=false`, `SESSION_SECURE_COOKIE=true`, a real mail provider, HTTPS, PHP
`upload_max_filesize` of at least 8M, a queue worker if mail is moved to a queue, backups, and review
the draft privacy notice and terms. See the checklist in SECURITY.md.
