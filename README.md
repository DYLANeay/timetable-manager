# Timetable Manager

A mobile-first web application for managing gas station employee shift schedules.

## Features

- **Weekly schedule view** — See who works which shift at a glance
- **Shift swap requests** — Employees can request to swap shifts with colleagues
- **Manager dashboard** — Create schedules, manage employees, approve swap requests
- **Installable PWA** — Install on mobile from the browser

## Tech Stack

- **Backend:** Laravel (PHP 8.5) + PostgreSQL
- **Frontend:** Vue.js 3 + TypeScript + Tailwind CSS + shadcn-vue
- **Auth:** Laravel Sanctum (token-based)

## Shift Slots

| Period | Shift | Hours |
|---|---|---|
| Weekdays & Saturday | Morning | 06:15 – 13:45 |
| Weekdays & Saturday | Afternoon | 13:45 – 21:15 |
| Sundays & holidays | Morning | 07:45 – 14:30 |
| Sundays & holidays | Afternoon | 14:30 – 21:15 |

## Getting Started

### Prerequisites

- PHP 8.2+ with `pdo_pgsql` extension
- Composer
- Node.js 20+
- PostgreSQL

### Backend

```bash
cd backend
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

### Frontend

```bash
cd frontend
npm install
npm run dev
```

### Default Accounts

| Role | Email | Password |
|---|---|---|
| Manager | manager@station.com | password |
| Employee | employee@station.com | password |

## Project Structure

```
timetable-manager/
├── backend/    # Laravel API
├── frontend/   # Vue 3 SPA
├── plan.md     # Implementation plan
└── README.md
```
