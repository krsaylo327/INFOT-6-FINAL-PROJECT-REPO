# UA-TRaMP — University of Antique Travel Request & Management Platform

**Group:** Cloud Chasers
**Course:** INFOT 6 — Final Project

A web-based platform that digitizes the University of Antique's official travel
workflow — from logging an incoming invitation, through endorsement and
multi-level approval, to the issuance, release, and closure of the Travel Order.
It replaces manual paper routing with automated approvals, QR-code document
verification, and digital signatures.

## Team (see members.txt)
- James John Martizano
- Ryan Neil Rabaya
- Neña Jezza Fernando
- Mary Chris Navaroza
- Rochelle Ann Delasan

## Tech Stack
- **Laravel 11** (PHP) — backend
- **MySQL** — database
- **Tailwind CSS v4 + Blade** — frontend
- **Vite** — asset bundling
- **Git / GitHub** — version control

## Key Features
- Multi-level approval workflow (Dean → VP → President)
- QR document tracing and digital-signature verification
- Digital signatures secured with a 6-digit PIN / password
- Role-based dashboards (Records Officer, President, Dean, VPAA/VPREI, Traveler, Admin)
- Records Office incoming & outgoing document registers
- Analytics dashboard

## Setup (local development)
```bash
composer install
npm install && npm run build
cp .env.example .env
php artisan key:generate
# configure your database in .env, then:
php artisan migrate --seed
php artisan serve     # or use Laravel Herd
```

Demo accounts are created by the database seeder. All demo passwords are `password`.

## Standards & Compliance
- Aligned with **RA 11032** (Ease of Doing Business Act, 2018)
- References **Executive Order No. 77, s. 2019** (government travel rules)
- Evaluated using **ISO/IEC 25010** software quality standards
