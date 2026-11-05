# Adminova SIMS — Application Core (`sims-app`)

<div align="center">

[![Release](https://img.shields.io/badge/Release-v2.7.0%20Enterprise-blue.svg?style=for-the-badge&logo=git)](https://github.com/saimimran87678-web/sims-app/releases/tag/v2.7.0)
[![Laravel 12](https://img.shields.io/badge/Laravel-12.x-FF2D20.svg?logo=laravel)](https://laravel.com)
[![Livewire 3](https://img.shields.io/badge/Livewire-3.7-4E56A6.svg?logo=livewire)](https://livewire.laravel.com)
[![PHP 8.2+](https://img.shields.io/badge/PHP-8.2%20%7C%208.3-777BB4.svg?logo=php)](https://php.net)
[![Database](https://img.shields.io/badge/Database-SQLite%20WAL%20%2B%20CHECKs-003B57.svg?logo=sqlite)](https://sqlite.org)
[![Runtime](https://img.shields.io/badge/Server-FrankenPHP%20Workers-teal.svg?logo=caddy)](https://frankenphp.dev)
[![Tailwind CSS](https://img.shields.io/badge/Tailwind-3.x-38B2AC.svg?logo=tailwind-css)](https://tailwindcss.com)

</div>

The `sims-app` repository contains the complete Laravel 12 & Livewire 3 enterprise application engine for the **Adminova School Information Management System**. It powers an offline-first local LAN architecture, high-throughput IoT RFID smart card attendance, student lifecycle & guardian normalization, curriculum program bundles, tamper-evident exam grading, immutable financial ledgers, and zero-knowledge PII encryption.

---

## 🛠️ Tech Stack & Runtime Architecture

- **Backend Framework:** Laravel 12.x running on PHP 8.2+ / PHP 8.3.
- **Reactive UI Engine:** Livewire 3.7+ paired with Alpine.js 3.4+.
- **Server Runtime:** FrankenPHP 4-Worker Daemon (Caddy) for sub-second (<400ms) page loads and high concurrency.
- **Styling & Components:** Tailwind CSS 3.x with DaisyUI component library.
- **Database Engine:** SQLite 3 configured with Write-Ahead Logging (`PRAGMA journal_mode=WAL;`), strict foreign key enforcement (`PRAGMA foreign_keys=ON;`), and engine-level `CHECK` constraints.
- **Hardware & IoT:** High-throughput RFID Scanner API (<20ms response time) supporting ESP32 / Arduino / PN532 with 48-byte EEPROM anti-tamper HMAC verification.
- **Security & PII Protection:** At-rest AES-256-CBC encryption for sensitive personal data paired with HMAC-SHA256 blind indexing for constant-time exact searching.
- **Role-Based Access Control:** Spatie Laravel-Permission 6.24+ synchronized in lockstep with user role models.
- **Document & Voucher Rendering:** Barryvdh Laravel DomPDF 3.1+ (High-resolution 3-part bank challans, School Leaving Certificates, and terminal report cards).
- **Licensing & Integrity:** Asymmetric RSA-2048 verification with Motherboard BIOS hardware UUID lock (`Win32_ComputerSystemProduct.UUID` / `/sys/class/dmi/id/product_uuid`).

---

## 📂 Core Application Architecture

```
sims-app/
├── app/
│   ├── Console/Commands/
│   │   ├── DatabaseBackup.php          # SHA-256 verified SQLite hot backup with retention policy
│   │   ├── DatabaseRestore.php         # Integrity-verified atomic database restoration
│   │   ├── ProcessWhatsAppQueue.php    # Local queue dispatcher honoring parent opt-in consent
│   │   ├── PurgeOperationalData.php    # Automated retention cleaner for logs and expired tokens
│   │   └── PurgeWhatsAppQueue.php      # Prunes sent/failed notifications past retention window
│   ├── Http/Controllers/
│   │   ├── Api/
│   │   │   └── RfidScanController.php  # High-throughput sub-20ms RFID tap, inspect, write & batch endpoints
│   │   ├── Admin/                      # Timetable, challan, and gradebook print handlers
│   │   ├── PublicVoucherController.php # Secure, time-expiring public fee voucher renderer
│   │   └── StudentPhotoController.php  # Authenticated and authorized streaming of student photos
│   ├── Livewire/                       # Reactive UI components
│   │   ├── Admin/
│   │   │   ├── AcademicSessionManager.php # Session progression, multi-criteria promotion wizard
│   │   │   ├── AttendanceManager.php      # Calendar-based attendance grid with shift filtering
│   │   │   ├── ClassManager.php           # Class, section, and curriculum program associations
│   │   │   ├── CurriculumManager.php      # Canonical subject catalogue & program bundle manager
│   │   │   ├── ExamManager.php            # 5-stage exam lifecycle state machine
│   │   │   ├── GradeManager.php           # Nullable grade entry with grading scale calculations
│   │   │   ├── Rfid/
│   │   │   │   └── SmartCardManager.php   # Live RFID card issuance, provisioning, and inspection
│   │   │   ├── ScheduleManager.php        # Collision-free timetable grid with divided elective slots
│   │   │   ├── StudentManager.php         # Student admissions, multi-guardian links, sibling groups
│   │   │   ├── SubstitutionManager.php    # Daily 1-click substitute teacher allocation
│   │   │   └── UserManager.php            # RBAC and session-user teacher subject allocations
│   │   └── Student/
│   │       ├── Auth/PortalLogin.php       # Blind-index authenticated student portal login
│   │       └── PortalDashboard.php        # 360° student portfolio, published results, WhatsApp toggle
│   ├── Models/
│   │   ├── Student.php                 # Decoupled student master entity with encrypted PII
│   │   ├── Guardian.php                # First-class normalized parent/guardian record
│   │   ├── Enrollment.php              # Session & shift-scoped student class and section placement
│   │   ├── Exam.php & ExamMark.php     # Tri-state marks (pending, zero, absent) with exam lifecycle
│   │   ├── GradeScale.php              # Institutional grading scales & GPA bounds
│   │   ├── FeeRecord.php               # Ledger-checked fee records with mathematical balance invariant
│   │   ├── FeePayment.php              # Immutable financial receipts with formal void workflow
│   │   ├── Program.php                 # Curriculum bundles (Science, Pre-Engineering, ICS, Humanities)
│   │   └── SessionUser.php             # SSOT for session-scoped teacher allocations
│   └── Services/
│       ├── StudentLifecycleService.php # Atomic student+enrollment creation, SLC issuance, alumni
│       ├── RfidCardService.php         # EEPROM payload encoding, decoding, and HMAC anti-tamper check
│       ├── GradeCalculationService.php # Dynamic letter grade, GPA, and pass/fail evaluation
│       ├── FeePaymentService.php       # Transactional payment recording & immutable void management
│       ├── ActivityLogger.php          # Audit logging with automated PII masking
│       └── WhatsAppService.php         # Socket bridge to local Baileys gateway with opt-in verification
├── database/
│   ├── migrations/                     # Complete relational migrations with SQLite engine CHECKs
│   └── seeders/                        # Baseline roles, permissions, programs & subjects
├── resources/
│   ├── iot/
│   │   └── esp32_rfid_firmware.cpp     # Complete C++ firmware for ESP32 + PN532 / RC522 scanners
│   └── views/                          # Blade templates, Livewire views & PDF layouts
└── tests/                              # Comprehensive feature & invariant test suites
    └── Feature/
        ├── DatabaseInvariantsAndModelDecouplingTest.php # SQLite CHECK constraints & model decoupling
        ├── RfidAttendanceSubsystemTest.php              # Sub-20ms scan, clone detection, batch sync
        ├── ExamMarkNullabilityTest.php                  # Tri-state marks, grading scales, portal gating
        ├── GuardianNormalizationTest.php                # Multi-child grouping, WhatsApp opt-in consent
        ├── PiiEncryptionAndSecurityTest.php             # Blind indexing, photo protection, token expiry
        ├── FeeLedgerImmutabilityTest.php                # Fee balance integrity & void workflow
        ├── SingleSourceOfTruthTest.php                  # SessionUser, Spatie RBAC & section scoping
        └── CurriculumProgramTest.php                    # Canonical subject bundles & elective filters
```

---

## 🛡️ Key Subsystems & Business Capabilities

### 1. RFID Smart Card Attendance & Hardware Anti-Tamper Security
- **Sub-20ms Edge Tap API:** `/api/rfid/scan` processes card taps with intelligent enrollment lookup and sub-20ms response times.
- **60-Second Tap Debounce Window:** Eliminates accidental multiple taps at entrance gates while returning friendly feedback.
- **Dual-Shift Routing:** Automatically directs morning students to morning enrollments and evening students to evening enrollments based on configurable session shift cutoffs.
- **48-Byte EEPROM Anti-Clone Architecture:** Writes a binary payload containing UID, Institution ID, Student ID, timestamp, and a cryptographic HMAC-SHA256 signature calculated with the institution secret key.
- **Anti-Tamper & Clone Detection:** Detects blank cards, modified data (e.g. mobile NFC Tools app), and cloned UIDs in `/api/rfid/inspect`.
- **Offline Batch Synchronization:** `/api/rfid/batch-sync` accepts buffered offline scan logs recorded during LAN interruptions and commits them atomically.
- **Dedicated Livewire Smart Card Manager:** Interface to arm RFID devices into `write` or `inspect` mode, monitor real-time scanner battery and firmware telemetry, and manage card states (`unassigned`, `active`, `blocked`, `lost`).

### 2. Academic Exam Lifecycle & Nullable Grading System
- **5-State Controlled Lifecycle:** Exams transition deterministically through:
  $$\text{Draft} \longrightarrow \text{Marks Entry} \longrightarrow \text{Moderation} \longrightarrow \text{Published} \longrightarrow \text{Archived}$$
- **Tri-State Marks Handling:** `exam_marks.marks_obtained` uses nullable floats to eliminate the ambiguity between:
  1. *Pending / Unentered:* (`marks_obtained = NULL`, `is_absent = false`)
  2. *Earned Zero:* (`marks_obtained = 0.0`, `is_absent = false`)
  3. *Absent:* (`marks_obtained = NULL`, `is_absent = true`)
- **Configurable Grading Scales:** System administrators configure percentage bands, letter grades (A+, A, B, etc.), and GPA values dynamically via `grade_scales`.
- **Student Portal Result Gate:** Students and parents can only view examination results once the examination state is explicitly set to `published`.
- **Grade Management Locks:** Teachers and staff are strictly blocked from editing marks once an examination is locked or moved into moderation/published states.

### 3. Guardian Normalization & Multi-Child Communication Model
- **First-Class Relational Guardians:** Normalized `guardians` table linked to students via `student_guardian` pivot, supporting multiple guardians per student (Father, Mother, Legal Guardian) with explicit roles (`is_primary`, `is_emergency`, `can_pickup`).
- **Zero Breaking Changes:** Virtual accessors (`$student->father_name`, `$student->father_cnic`, `$student->phone`) automatically fallback to the primary guardian, preserving backward compatibility across all existing vouchers and reports.
- **Automated Sibling Network:** Accurately groups multi-child households sharing common guardians, enabling institutional sibling concession discounts.
- **Communication Consent Governance:** Enforces guardian WhatsApp consent (`whatsapp_opt_in`). Parents can toggle their communication preferences live from the Student Portal dashboard, and queued messages are automatically verified prior to transmission.

### 4. PII At-Rest Encryption & Blind Indexing
- **Military-Grade Data Protection:** Sensitive student and guardian identifiers (`father_cnic`, `guardian_cnic`, `b_form_number`, `phone`, `address`) are stored as ciphertext in SQLite using Laravel's AES-256-CBC encryption.
- **High-Performance Blind Indexing:** Indexed deterministic HMAC-SHA256 hashes (`father_cnic_hash`, `b_form_hash`, `phone_hash`) enable constant-time exact lookups, portal authentication, and sibling matching without decrypting entire tables.
- **Secure Photo Streaming:** Student profile photos are served through authenticated route `/student-photo/{id}` with strict authorization gates, completely blocking unauthenticated access to minor student media.
- **Expiring Public Voucher Tokens:** Fee voucher URLs incorporate signed expiration timestamps (`access_token_expires_at`), preventing permanently exposed public URLs.

### 5. Decoupled Model Lifecycle & SQLite Engine Invariants
- **Request-Independent Model Lifecycle:** Removed ambient HTTP `request(...)` helper dependencies from `Student::booted()`. Seeders, queued jobs, API endpoints, and CLI utilities can create students safely.
- **Atomic Student & Enrollment Creation:** `StudentLifecycleService::createStudentWithEnrollment(...)` wraps master student records and session enrollments in an atomic transaction with rollback guarantees.
- **Engine-Level `CHECK` Constraints:** Enforced directly in SQLite DDL:
  - `students`: `dob < admission_date`, status checks, valid gender, valid transport mode, and card status.
  - `enrollments`: valid shift types (`morning`, `evening`, `regular`) and allowed progression statuses.
  - `fee_records`: $\text{total\_amount} \ge 0$, $\text{paid\_amount} \ge 0$, and $\text{round}(\text{balance}, 2) = \text{round}(\text{total\_amount} - \text{paid\_amount}, 2)$.
- **Timetable Slot Collision Prevention:** Partial unique indexes on `timetables` prevent double booking of class periods while supporting elective group splitting.

### 6. Curriculum Program Bundles & Canonical Subject Catalogue
- **Canonical Subjects Catalogue:** Deduplicated global subjects repository with unique codes and names.
- **Academic Programs (Tracks):** Group subjects into institutional streams (e.g. Science - Biology, Science - Computer Science, Humanities, Commerce) with compulsory and elective definitions.
- **Class-Program Allowances:** Assign allowable programs to specific classes with designated default tracks.
- **Elective Gradebook Filtering:** Gradebooks, datesheets, and result reports dynamically isolate elective students so teachers only grade students enrolled in that specific curriculum track.

### 7. Immutable Financial Ledgers & Audited Void Workflows
- **Strict Ledger Immutability:** Historical payment entries cannot be edited or arbitrarily deleted.
- **Audited Void Workflow:** Reversing payments requires formal voiding with recorded reasons (`void_reason`), timestamp (`voided_at`), and user identification (`voided_by_user_id`), automatically recalculating parent invoice balances safely.
- **3-Part Bank Challans:** High-resolution PDF generation with Bank Copy, School Copy, and Student Copy.

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
# Terminal 1: Run FrankenPHP or Artisan serve
php artisan serve --port=8000

# Terminal 2: Run Vite compiler (hot reload)
npm run dev

# Terminal 3: Run WhatsApp background queue processor
php artisan whatsapp:process-queue
```

---

## ⚙️ Essential Artisan Commands

| Command | Purpose |
| :--- | :--- |
| `php artisan db:backup` | Creates an immediate SHA-256 verified SQLite backup with retention pruning. |
| `php artisan db:restore {file?}` | Atomically restores database from the latest or specified verified backup. |
| `php artisan audit:verify-chain` | Cryptographically verifies the HMAC-SHA256 tamper-evident hash chain of activity logs. |
| `php artisan whatsapp:process-queue` | Background daemon polling and dispatching WhatsApp alerts with opt-in verification. |
| `php artisan whatsapp:purge-queue` | Prunes processed WhatsApp queue entries past the configured retention window. |
| `php artisan data:purge-operational` | Purges expired voucher tokens, orphan upload files, and audit records. |
| `php artisan sims:activate --token=SIMS-TOK-XXXX` | Unlocks the installation using a remote activation token. |
| `php artisan sims:verify-license` | Re-verifies local RSA signature and hardware UUID integrity. |

---

## 🧪 Automated Testing Suite

All tests execute against an isolated in-memory SQLite database (`:memory:`) ensuring zero risk to production school data:

```bash
# Run core architectural and invariant test suites
php artisan test --filter="DatabaseHygieneAndAuditChainTest|GuardianNormalizationTest|PiiEncryptionAndSecurityTest|DatabaseInvariantsAndModelDecouplingTest|ExamMarkNullabilityTest|GradebookShiftScopingTest|AcademicSessionManagerPromotionTest|SingleSourceOfTruthTest|RfidAttendanceSubsystemTest"

# Run specific functional suites
php artisan test --filter=DatabaseHygieneAndAuditChainTest         # Redundant index pruning, NULL-safe uniqueness & audit chain
php artisan test --filter=DatabaseInvariantsAndModelDecouplingTest # SQLite CHECK constraints & model decoupling
php artisan test --filter=RfidAttendanceSubsystemTest             # RFID edge scanner & HMAC security
php artisan test --filter=ExamMarkNullabilityTest                 # Exam lifecycle & nullable mark states
php artisan test --filter=GuardianNormalizationTest               # Guardian normalization & WhatsApp consent
php artisan test --filter=PiiEncryptionAndSecurityTest            # AES-256 encryption & blind indexing
php artisan test --filter=FeeLedgerImmutabilityTest               # Fee balance integrity & void workflow
php artisan test --filter=SingleSourceOfTruthTest                 # SessionUser & Spatie RBAC synchronization
php artisan test --filter=CurriculumProgramTest                   # Curriculum tracks & elective matrices
```

---

## 📄 License & Intellectual Property

Proprietary software developed by Adminova Solutions. All rights reserved. Unauthorized reproduction, modification, or reverse engineering is strictly prohibited.
