# Adminova SIMS — Application Core (`sims-app`)

<div align="center">

[![Release](https://img.shields.io/badge/Release-v2.6.0-blue.svg?style=for-the-badge&logo=git)](https://github.com/saimimran87678-web/sims-app/releases/tag/v2.6.0)
[![Laravel 12](https://img.shields.io/badge/Laravel-12.x-FF2D20.svg?logo=laravel)](https://laravel.com)
[![Livewire 3](https://img.shields.io/badge/Livewire-3.7-4E56A6.svg?logo=livewire)](https://livewire.laravel.com)
[![PHP 8.2+](https://img.shields.io/badge/PHP-8.2%20%7C%208.3-777BB4.svg?logo=php)](https://php.net)
[![SQLite WAL](https://img.shields.io/badge/Database-SQLite%20WAL-003B57.svg?logo=sqlite)](https://sqlite.org)
[![Runtime](https://img.shields.io/badge/Server-FrankenPHP%20Workers-teal.svg?logo=caddy)](https://frankenphp.dev)
[![Tailwind CSS](https://img.shields.io/badge/Tailwind-3.x-38B2AC.svg?logo=tailwind-css)](https://tailwindcss.com)

</div>

The `sims-app` directory contains the complete Laravel 12 & Livewire 3 application engine for the **Adminova School Information Management System**. It powers the 100% offline-first local LAN interface, fee calculation engine, 3-part bank challans, student lifecycle and departure workflows, timetable constraint solver, and cryptographic licensing subsystem.

---

## 🛠️ Tech Stack & Runtime Architecture

- **Backend Framework:** Laravel 12.x running on PHP 8.2+ / PHP 8.3.
- **Reactive UI Engine:** Livewire 3.7+ paired with Alpine.js 3.4+.
- **Server Runtime:** FrankenPHP 4-Worker Daemon (Caddy) for sub-second (<400ms) page loads and high concurrency.
- **Styling & Components:** Tailwind CSS 3.x with DaisyUI component library.
- **Database Engine:** SQLite 3 configured with Write-Ahead Logging (`PRAGMA journal_mode=WAL;`) and optimized busy timeout.
- **Document & Voucher Rendering:** Barryvdh Laravel DomPDF 3.1+ (High-resolution 3-part bank challans, School Leaving Certificates, and terminal report cards).
- **Role-Based Access Control:** Spatie Laravel-Permission 6.24+.
- **Security & Licensing:** Asymmetric RSA-2048 verification with Motherboard BIOS hardware UUID lock (`Win32_ComputerSystemProduct.UUID` / `/sys/class/dmi/id/product_uuid`).

---

## 📂 Core Application Structure

```
sims-app/
├── app/
│   ├── Http/
│   │   ├── Controllers/         # REST endpoints & public voucher download handlers
│   │   └── Middleware/          # Shift/Session Context Shifter & RSA License Gate
│   ├── Livewire/                # Single-page reactive components
│   │   └── Admin/
│   │       ├── AcademicSessionManager.php
│   │       ├── AttendanceManager.php
│   │       ├── ClassManager.php
│   │       ├── ExamManager.php
│   │       ├── FeeManager.php          # 3-part challans, partial receipts, concessions
│   │       ├── ScheduleManager.php     # Master timetable constraint grid
│   │       ├── StudentManager.php      # Admissions, B-Form verification, Sibling UI
│   │       ├── SubstitutionManager.php # 1-click substitute teacher allocation
│   │       └── UserManagement.php      # RBAC & staff permissions
│   ├── Models/                  # Eloquent entities & relationships
│   │   ├── Student.php          # Student profile, session enrollments, siblings
│   │   ├── SchoolLeavingCertificate.php # Issued SLC certificates & serial tracking
│   │   ├── FeeChallan.php       # 3-part bank challans & line items
│   │   └── ...
│   └── Services/
│       ├── StudentLifecycleService.php # SLC generation, financial clearance, 360° portfolio, alumni
│       ├── LicenseVerifier.php         # Hardware UUID probe & RSA signature validator
│       ├── SubstitutionEngine.php      # Constraint solver for teacher availability
│       └── WhatsAppService.php         # Local queue dispatcher for Baileys bridge
├── config/                      # Application, database, and auth configurations
├── database/
│   ├── migrations/              # Schema definitions for all school modules
│   └── seeders/                 # Baseline roles, permissions & admin seeders
├── resources/
│   ├── css/                     # Tailwind stylesheets
│   ├── js/                      # Alpine.js & client plugins
│   └── views/                   # Blade templates, Livewire views & PDF layouts
├── routes/
│   ├── web.php                  # Application routes & Livewire endpoints
│   └── api.php                  # Local background worker interfaces
└── tests/                       # Isolated test suite (:memory: SQLite environment)
    ├── Feature/
    │   ├── StudentBFormSecurityTest.php # 13-digit B-Form, parent CNIC collision, active locks
    │   ├── StudentLifecycleTest.php     # SLC issuance, financial clearance, alumni re-admission
    │   ├── LicenseTest.php              # RSA-2048 hardware lock and anti-tampering
    │   └── TimetableTest.php            # Substitution and schedule constraint solver
    └── TestCase.php                     # Test harness guaranteeing zero production DB touch
```

---

## 🛡️ Key Business Logic & Subsystems

### 1. Student Lifecycle & Alumni Re-Admission (`StudentLifecycleService.php`)
- **Audited Departure Workflow:** Transitions student statuses (`withdrawn`, `slc_issued`, `graduated`, `struck_off`) with departure date and audit reasons.
- **Mandatory Financial Clearance:** Verifies all outstanding fee challans are fully settled before departure or SLC issuance.
- **School Leaving Certificate (SLC):** Issues official, tamper-evident documents with serial tracking, conduct grade, attendance record, and reason for leaving.
- **360° Lifetime Student Portfolio & Audit Timeline:** Chronological timeline tracking academic history across all sessions, fee payment ledgers, and institutional events.
- **1-Click Returning Alumni Re-Admission:** Detects returning departed students by NADRA B-Form, auto-populates historical demographic data with fields locked against tampering, and creates a fresh enrollment.
- **Active Student Duplicate Admission Lockout:** Real-time prevention of duplicate admissions if a student with the same B-Form is already active in the institution.

### 2. NADRA B-Form / CRC Identity & Sibling Network
- **13-Digit Format Validation:** Enforces strict Pakistani identity format (`XXXXX-XXXXXXX-X`) for student B-Forms and CNICs.
- **Parent vs Student Anti-Collision Check:** Enforces that a student's B-Form/CNIC can never match the Father's or Guardian's CNIC.
- **Real-Time Sibling Network:** Scans all enrolled students sharing the same Father's CNIC, surfaces family relationships, and recommends automated sibling concession discounts.
- **Mandatory Admission Date:** Validates admission dates across all single-shift and dual-shift admission forms.

### 3. Financial Ledger & 3-Part Bank Challans
- **3-Part Bank Challan Generator:** Produces print-ready A4 vouchers featuring **Bank Copy**, **School Copy**, and **Student Copy** with barcodes.
- **Partial Payments & Dynamic Ledgers:** Real-time balance recalculation, concession overrides, fee waiver approvals, and fine handling.

### 4. Zero-Cost WhatsApp Local Gateway (`WhatsAppService.php`)
- Embedded socket bridge connecting directly to a local Node.js Baileys gateway.
- Dispatches fee payment receipts, morning absence alerts, and broadcast notifications without per-SMS carrier charges.

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

# 5. Initialize SQLite database & run migrations
touch database/database.sqlite
php artisan migrate --seed

# 6. Build frontend assets
npm run build
```

### 3. Launch Development Server
```bash
# Terminal 1: Run web server
php artisan serve --port=8000

# Terminal 2: Run Vite compiler (hot reload)
npm run dev

# Terminal 3: Run WhatsApp background queue processor
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

## 🧪 Automated Testing Suite

All tests run in an isolated in-memory SQLite database (`:memory:`) to guarantee zero interference with local school data:

```bash
# Run all tests
php artisan test

# Test NADRA B-Form validation & CNIC collision security
php artisan test --filter=StudentBFormSecurityTest

# Test Student Lifecycle, SLC generation & alumni re-admission
php artisan test --filter=StudentLifecycleTest

# Test RSA-2048 hardware licensing & anti-tampering
php artisan test --filter=LicenseTest

# Test Timetable solver and teacher substitution balancer
php artisan test --filter=TimetableTest
```

---

## 📄 License & Intellectual Property

Proprietary software developed by Adminova Solutions. All rights reserved. Unauthorized reproduction or reverse engineering is prohibited.

