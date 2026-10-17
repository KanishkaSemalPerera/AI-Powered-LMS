# AI-Powered LMS (LearnHub)

An online learning platform with a Laravel 12 REST API (`/backend`) and a React + Vite SPA (`/frontend`), featuring Stripe payments, PDF certificates and Claude-powered AI tools.

See [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md) for the architecture, ER diagram and API modules.

## Requirements

- PHP 8.2 (XAMPP) with the `gd`, `intl`, `sodium`, `zip`, `fileinfo` and `pdo_mysql` extensions enabled in `C:\xampp\php\php.ini`
- Composer 2.x
- MariaDB 10.4 (XAMPP MySQL)
- Node.js 24 and npm

## First-time setup (Windows PowerShell)

```powershell
# 1. Start MySQL from the XAMPP Control Panel, then create the database
C:\xampp\mysql\bin\mysql.exe -u root -e "CREATE DATABASE IF NOT EXISTS lms_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 2. Backend
cd backend
composer install
Copy-Item .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan storage:link

# 3. Frontend
cd ..\frontend
npm install
Copy-Item .env.example .env
```

## Running in development

Use three terminals:

```powershell
# Terminal 1 — API on http://localhost:8000
cd backend; php artisan serve

# Terminal 2 — queue worker (emails, notifications)
cd backend; php artisan queue:listen --tries=1

# Terminal 3 — React app on http://localhost:5173
cd frontend; npm run dev
```

## Demo data

`php artisan migrate:fresh --seed` rebuilds the database with demo content (this deletes all existing data).
Every demo account uses the password **`password`**.

| Role | Email | Notes |
|---|---|---|
| Admin | `admin@lms.test` | Alex Morgan |
| Instructor | `instructor1@lms.test` | Sarah Chen — JavaScript, React, Laravel (pending review) |
| Instructor | `instructor2@lms.test` | David Okafor — Python, Machine Learning, Prompt Engineering |
| Instructor | `instructor3@lms.test` | Maria Lopez — UI Design, Personal Branding (draft) |
| Student | `student1@lms.test` | Emma Wilson — 1 completed course with certificate, 1 in progress, 1 just started |
| Student | `student2@lms.test` … `student10@lms.test` | Random enrollments, progress, reviews and payments |

Tests run on an in-memory SQLite database (`phpunit.xml`), so `php artisan test` never touches `lms_db`.

## Branching (Git Flow)

`main` (releases) ← `release/*` ← `Develop` ← `feature/*`; urgent fixes use `hotfix/*` from `main`.
