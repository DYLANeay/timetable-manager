# Timetable Manager - Implementation Plan

## Context

Gas station shift management app. The manager needs to easily publish weekly schedules, and employees need to view their shifts and swap hours with colleagues. Mobile-first PWA so staff can install it from the browser on their phones.

**Stack:** Laravel (PHP) backend + Vue.js 3 (TypeScript) frontend + Shadcn-vue + PostgreSQL
**Auth:** Email + password via Laravel Sanctum (token-based for PWA compatibility)
**Scale:** 1-10 employees, single station

**Fixed shift slots:**
- Weekdays & Saturday: 6h15→13h45 (morning), 13h45→21h15 (afternoon)
- Sundays & public holidays: 7h45→14h30 (morning), 14h30→21h15 (afternoon)

---

## Project Structure (Monorepo)

```
timetable-manager/
├── backend/                    # Laravel API
│   ├── app/
│   │   ├── Enums/              # Role, ShiftType, SwapRequestStatus
│   │   ├── Http/Controllers/   # Auth, Shift, SwapRequest, Employee, PublicHoliday
│   │   ├── Http/Middleware/    # EnsureIsManager
│   │   ├── Http/Requests/     # Form requests
│   │   ├── Http/Resources/    # API resources
│   │   ├── Models/            # User, Shift, ShiftTemplate, SwapRequest, PublicHoliday
│   │   ├── Policies/          # ShiftPolicy, SwapRequestPolicy
│   │   └── Notifications/    # Swap-related notifications
│   ├── database/migrations/
│   ├── database/seeders/
│   └── routes/api.php
├── frontend/                   # Vue 3 SPA
│   ├── src/
│   │   ├── api/               # Axios client + endpoint wrappers
│   │   ├── components/ui/     # shadcn-vue components
│   │   ├── components/layout/ # AppShell, BottomNav
│   │   ├── components/shifts/ # WeeklyTimetable, WeekPicker, ShiftCell
│   │   ├── components/swaps/  # SwapRequestCard, SwapRequestForm
│   │   ├── pages/             # Page components
│   │   ├── router/
│   │   ├── stores/            # Pinia (auth, shifts, swaps, employees)
│   │   └── types/
│   ├── vite.config.ts
│   └── tailwind.config.ts
├── plan.md
└── README.md
```

---

## Database Schema

### `users`
id, name, email (unique), password, role (`employee`|`manager`), is_active (bool), timestamps

### `shift_templates` (4 rows, seeded)
id, day_type (`weekday`|`sunday_holiday`), shift_type (`morning`|`afternoon`), start_time, end_time

| day_type | shift_type | start | end |
|---|---|---|---|
| weekday | morning | 06:15 | 13:45 |
| weekday | afternoon | 13:45 | 21:15 |
| sunday_holiday | morning | 07:45 | 14:30 |
| sunday_holiday | afternoon | 14:30 | 21:15 |

### `shifts`
id, user_id (FK nullable), shift_template_id (FK), date, notes (nullable), timestamps
- Unique constraint: `(date, shift_template_id, user_id)`
- Indexes: `(date)`, `(user_id, date)`

### `swap_requests`
id, requester_id (FK), target_id (FK), requester_shift_id (FK), target_shift_id (FK), status (`pending_peer`→`peer_accepted`/`peer_declined`→`manager_approved`/`manager_denied` or `cancelled`), peer_responded_at, manager_decided_at, manager_id (FK nullable), note, timestamps

### `public_holidays`
id, date (unique), name, timestamps

---

## API Endpoints (all `/api` prefix, Sanctum auth)

**Auth:** POST /auth/login, POST /auth/logout, GET /auth/me, PUT /auth/me

**Shifts:** GET /shifts?week=, GET /shifts/my?week=, POST /shifts, PUT /shifts/{id}, DELETE /shifts/{id}, POST /shifts/bulk (all write = manager only)

**Shift Templates:** GET /shift-templates

**Swap Requests:** GET /swap-requests, POST /swap-requests, PUT /swap-requests/{id}/respond (peer), PUT /swap-requests/{id}/decide (manager), PUT /swap-requests/{id}/cancel

**Employees (manager):** GET /employees, POST /employees, PUT /employees/{id}, DELETE /employees/{id}

**Public Holidays:** GET /public-holidays?year=, POST /public-holidays, DELETE /public-holidays/{id}

---

## Frontend Pages

| Path | Role | Description |
|---|---|---|
| `/login` | public | Login form |
| `/schedule` | auth | Weekly timetable (default) |
| `/my-shifts` | employee | Personal upcoming shifts |
| `/swap-requests` | auth | List/manage swaps |
| `/swap-requests/new` | employee | Create swap request |
| `/employees` | manager | Manage employees |
| `/holidays` | manager | Manage public holidays |
| `/settings` | auth | Change own password/name |

**Layout:** Bottom tab navigation (mobile), sidebar on desktop.

---

## Implementation Phases

### Phase 1: Scaffolding & Auth
1. `laravel new backend` + PostgreSQL config
2. Install Sanctum, User migration with `role` enum
3. Auth controllers (login, logout, me)
4. `npm create vue@latest frontend` + Tailwind + shadcn-vue + Pinia + vue-router
5. Login page, auth store, axios interceptor
6. App shell with bottom nav + route guards

### Phase 2: Schedule Management (core feature)
1. Migrations: shift_templates, shifts, public_holidays
2. Seed 4 shift templates
3. Shift model + controller + resource + policy
4. Weekly timetable component (read-only for employees, editable for managers)
5. Week picker navigation
6. Bulk edit modal for managers

### Phase 3: Swap Requests
1. SwapRequest migration + model with status state machine
2. Controller with create/respond/decide/cancel actions
3. On approval: swap user_ids in a DB transaction
4. Frontend: request form, request cards, badge counts

### Phase 4: Employee Management
1. Employee CRUD controller (manager only)
2. Employee list page with create/edit dialogs
3. Soft deactivation, manager password reset

### Phase 5: PWA
1. `vite-plugin-pwa` configuration
2. manifest.json, service worker, icons
3. Cache strategy: cache-first for shell, network-first for API
4. Install prompt component

### Phase 6: Polish
1. Public holidays management page
2. Notifications (in-app + optional push)
3. Dark mode
4. Print-friendly schedule view

---

## Conventions

- **Commits:** Conventional commits (`feat:`, `fix:`, `chore:`, `docs:`, `refactor:`), atomic, no co-author line
- **Backend:** Laravel conventions (PSR-12, Eloquent resources, form requests, policies)
- **Frontend:** Vue 3 Composition API + `<script setup>`, TypeScript strict mode, Pinia stores
- **Naming:** snake_case for DB/PHP, camelCase for TS/Vue, kebab-case for components/routes
- **API:** RESTful, JSON:API-style responses via Laravel Resources

---

## Verification

- **Auth:** Login as employee and manager, verify role-based routing
- **Schedule:** Manager creates a week of shifts, employee views it
- **Swaps:** Full flow: request → peer accept → manager approve → verify shifts swapped in DB
- **PWA:** Install on mobile, verify offline cached schedule display
- **Edge cases:** Sunday/holiday shift templates apply correctly, double-booking prevention
