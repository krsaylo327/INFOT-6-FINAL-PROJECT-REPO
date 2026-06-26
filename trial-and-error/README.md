# Keep-Track

> Internal MOA / MOU (Memorandum of Agreement / Understanding) tracking and
> workflow-routing system for a legal/college office.

## About

Keep-Track manages the full lifecycle of an institutional agreement — from
draft to signed and active, with automatic expiration reminders.

Users create an agreement, upload documents, then forward it through a
multi-role review chain until it's signed and active.

**Roles:** `admin` → `uploader` → `coordinator` → `authorized_personnel` → `signatory`

All state transitions go through `AgreementWorkflowService.php`. See
`CLAUDE.md` for the full development guide.

## Tech stack

| Layer | Choice |
|---|---|
| Backend | Laravel 13 / PHP 8.3 with Fortify (2FA + passkeys) |
| Frontend | React 19 + TypeScript + Inertia-Laravel 3 |
| UI | shadcn/ui (neutral base) + lucide icons |
| Bundler | Vite 8 |
| Styling | Tailwind 4 (CSS-first, no config file) |
| Database | SQLite (default; no external services) |
| Routes | Wayfinder (generates typed route helpers) |
| Tests | PHPUnit + Dusk |

## Quick start

```bash
composer install          # creates .env, runs setup
php artisan migrate --force
php artisan db:seed       # dev users (password: "password")
npm install
```

```bash
# Frontend + Laravel
npm run dev

# Full stack (adds queue worker + live logs)
composer dev
```

Open **http://127.0.0.1:8000**.

## Common commands

```bash
# Frontend
npm run build
npm run lint
npm run types:check

# Backend
composer dev       # Laravel + Vite + queue + logs
composer test      # lint + PHPUnit
composer ci:check  # frontend + backend CI

# After adding or renaming routes
php artisan wayfinder:generate --with-form --no-interaction
```