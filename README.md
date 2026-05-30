# IMI 2026 Monorepo

Enterprise starter monorepo for IMEI/mobile services platform using React + Vite frontend and Laravel 12 backend.

## Stack

- **Frontend:** React 19, Vite, Tailwind CSS, React Router, React Query, Axios
- **Backend:** Laravel 12, Sanctum auth, MySQL, Redis, REST APIs
- **Infra:** Docker Compose, GitHub Actions CI

## Repository Structure

```text
/tmp/workspace/emaan624/imi2026
├── apps
│   ├── frontend
│   │   ├── src
│   │   │   ├── api
│   │   │   ├── components
│   │   │   ├── hooks
│   │   │   ├── layouts
│   │   │   └── pages
│   │   └── Dockerfile
│   └── backend
│       ├── app
│       │   ├── Http/Controllers/Api
│       │   ├── Http/Middleware
│       │   └── Models
│       ├── database
│       │   ├── factories
│       │   ├── migrations
│       │   └── seeders
│       ├── routes
│       │   ├── api.php
│       │   └── web.php
│       └── Dockerfile
├── .github/workflows/ci.yml
├── docker-compose.yml
└── deploy/docker-compose.prod.yml
```

## Phase 1 Implemented

- Authentication (register/login/logout/me) with Sanctum token flow
- Admin middleware and admin dashboard metrics endpoint
- User management CRUD endpoints (admin-only)
- Wallet system (wallet view, transactions)
- Deposits and withdrawals
- Internal transfers between users
- User notifications (list + mark read)
- Support tickets CRUD
- Seed data for admin/user + starter wallet/transaction/notifications/tickets
- Frontend login + user dashboard + admin dashboard

## API Entry Point

- Base URL: `/api`
- Auth routes: `/api/auth/*`
- Protected routes use `auth:sanctum`

## Local Development

### Frontend

```bash
cd /tmp/workspace/emaan624/imi2026/apps/frontend
npm install
npm run dev
```

### Backend

```bash
cd /tmp/workspace/emaan624/imi2026/apps/backend
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

### Docker

```bash
cd /tmp/workspace/emaan624/imi2026
docker compose up --build
```

## Validation Executed

- `apps/frontend`: `npm run lint` ✅
- `apps/frontend`: `npm run build` ✅
- `apps/backend`: PHP syntax checks (`php -l` across app/routes/database/tests) ✅
- `apps/backend`: `php artisan test` ⚠️ blocked in this environment due dependency install/auth restrictions to GitHub package sources

## Next Phases

- **Phase 2:** PTA approval and installment system (implemented in this update)
- **Phase 3:** Crypto deposits and confirmation tracking
- **Phase 4:** Payment gateways (Stripe, PayPal, Binance Pay, Coinbase Commerce, JazzCash, EasyPaisa, bank transfer)

## Phase 2 Implemented (PTA + Installments)

- PTA services catalog, tax calculator, IMEI validation, eligibility checks
- PTA order placement, status tracking, history, invoice + receipt payload generation
- Passport/CNIC/overseas registration endpoints and partial/full wallet payments for PTA tax
- Installment plans, contract creation with down payment support, schedule generation (weekly/monthly/custom)
- Installment payment APIs with wallet deduction, reminders, late-fee aware payment records, early settlement
- Admin PTA pricing/services management, order status updates, PTA revenue reporting
- Admin installment plan management, approval workflow, risk scoring, defaulter/recovery/manual adjustment flows, analytics
- New database tables:
  - `pta_services`
  - `pta_orders`
  - `pta_order_logs`
  - `installment_plans`
  - `installment_contracts`
  - `installment_payments`
  - `installment_schedules`
  - `installment_reminders`
- New frontend pages:
  - PTA Calculator
  - PTA Services
  - PTA Order Form
  - PTA Status Tracker
  - Installment Plans
  - My Installments
  - Payment Schedule
  - Installment Details
  - Admin PTA Dashboard
  - Admin Installment Dashboard
