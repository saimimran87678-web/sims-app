# Adminova SIMS — Application Core (`sims-app`)

[![Laravel 12](https://img.shields.io/badge/Laravel-12.x-FF2D20.svg?logo=laravel)](https://laravel.com)
[![Livewire 3](https://img.shields.io/badge/Livewire-3.7-4E56A6.svg?logo=livewire)](https://livewire.laravel.com)
[![PHP 8.2+](https://img.shields.io/badge/PHP-8.2%2B-777BB4.svg?logo=php)](https://php.net)
[![SQLite WAL](https://img.shields.io/badge/Database-SQLite%20WAL-003B57.svg?logo=sqlite)](https://sqlite.org)
[![Tailwind CSS](https://img.shields.io/badge/Tailwind-3.x-38B2AC.svg?logo=tailwind-css)](https://tailwindcss.com)

The `sims-app` directory contains the full Laravel 12 & Livewire 3 application engine for the **Adminova School Information Management System**. It powers the offline-first web interface, local REST APIs, fee computation engine, timetable solvers, and cryptographic license checks.

---

## 🛠️ Tech Stack & Architecture

- **Backend Framework:** Laravel 12.x (PHP 8.2 / 8.3)
- **Reactive UI Engine:** Livewire 3.7+ & Alpine.js 3.4+
- **Styling & Components:** Tailwind CSS 3.x with DaisyUI
- **Database Engine:** SQLite 3 with Write-Ahead Logging (`PRAGMA journal_mode=WAL;`)
- **Document Rendering:** Barryvdh Laravel DomPDF 3.1+ (High-resolution 3-part bank vouchers and report cards)
- **Role-Based Access Control:** Spatie Laravel-Permission 6.24+
- **Security & Licensing:** Asymmetric RSA-2048 verification with BIOS hardware UUID binding

---

## 📂 Core Application Structure

```
sims-app/
├── app/
│   ├── Http/
│   │   ├── Controllers/         # REST endpoints & public voucher download handlers
│   │   └── Middleware/          # Shift/Session Context Shifter & RSA License Gate
│   ├── Livewire/                # Single-page reactive components
│   │   ├── AcademicSessionManager.php
│   │   ├── AttendanceManager.php
│   │   ├── ClassManager.php
│   │   ├── ExamManager.php
│   │   ├── FeeManager.php       # 3-part challans, partial receipts & ledger
│   │   ├── ScheduleManager.php  # Master timetable constraint grid
│   │   ├── StudentManager.php   # Admissions, roll numbers & profile photo
│   │   ├── SubstitutionManager.php # 1-click substitute teacher allocation
│   │   └── UserManagement.php   # RBAC & staff permissions
│   ├── Models/                  # Eloquent entities & relationships
│   └── Services/
│       ├── LicenseVerifier.php  # Hardware UUID probe & RSA signature validator
│       ├── SubstitutionEngine.php # Constraint solver for teacher availability
│       └── WhatsAppService.php  # Local queue dispatcher for Baileys bridge
├── config/                      # Application, database, and auth configurations
├── database/
│   ├── migrations/              # Schema definitions for all school modules
│   └── seeders/                 # Baseline roles, permissions & admin seeders
├── resources/
│   ├── css/                     # Tailwind stylesheets
│   ├── js/                      # Alpine.js & client plugins
│   └── views/                   # Blade templates & PDF layouts
└── routes/
    ├── web.php                  # Application routes & Livewire endpoints
    └── api.php                  # Local background worker interfaces
```

---

## 🚀 Local Development Setup

### 1. Prerequisites
- PHP 8.2 or 8.3 with extensions: `pdo_sqlite`, `bcmath`, `curl`, `gd`, `intl`, `mbstring`, `xml`, `zip`
- Composer 2.x
- Node.js 20.x & NPM

### 2. Installation Steps
```bash
# 1. Install Composer dependencies
composer install

# 2. Install NPM dependencies
npm install

# 3. Create environment configuration
cp .env.example .env

# 4. Generate application encryption key
php artisan key:generate

# 5. Initialize SQLite database
touch database/database.sqlite
php artisan migrate --seed

# 6. Build frontend assets
npm run build
```

### 3. Launch Development Server
```bash
# Terminal 1: Run the web server
php artisan serve --port=8000

# Terminal 2: Run the Vite asset compiler (for hot reload during UI dev)
npm run dev

# Terminal 3: Run the background WhatsApp queue processor
php artisan whatsapp:process-queue
```

---

## ⚙️ Essential Artisan Commands

Adminova SIMS includes custom Artisan commands to manage offline operations:

| Command | Purpose |
| :--- | :--- |
| `php artisan sims:activate --token=SIMS-TOK-XXXX` | Unlocks the installation using a remote activation token. |
| `php artisan sims:verify-license` | Re-verifies local RSA signature and hardware UUID integrity. |
| `php artisan whatsapp:process-queue` | Background daemon polling and dispatching outgoing WhatsApp messages. |
| `php artisan schedule:run` | Executes scheduled cron jobs (morning absence checks, fee overdue alerts). |
| `php artisan optimize:clear` | Flushes all configuration, route, and view caches. |

---

## 🧪 Testing

Execute automated unit and feature tests:

```bash
# Run the entire test suite
php artisan test

# Run licensing & security validation tests only
php artisan test --filter=LicenseTest

# Run timetable & substitution balancer tests
php artisan test --filter=TimetableTest
```

---

## 📄 License & Intellectual Property

Proprietary software developed by Adminova Solutions. All rights reserved. Unauthorized reproduction or reverse engineering is prohibited.
