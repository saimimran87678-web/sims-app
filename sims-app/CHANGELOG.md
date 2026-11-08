# SIMS (School Information & Management System) - Comprehensive Changelog

All notable changes, architectural improvements, audit remediations, security hardening, and module updates are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [v2.7.2] - 2026-11-08

### 1. Module: Student Management & Directory
* **Session-Bound Graduating Batches (Option B)**:
  * **Scoping:** The `🎓 Graduated` filter now strictly scopes to students who concluded their studies in the currently selected academic session (`enrollments.academic_session_id = $selectedSessionId`).
  * **Ghost Record Elimination:** Students who graduated in previous sessions (e.g., 2026–2027) no longer leak into newer sessions (e.g., 2027–2028), preventing distorted student headcounts and staff confusion.
  * **Global Search Access:** The `🏛️ Alumni Directory` tab provides an institutional-wide search across all historical graduation years, equipped with a live counter badge.
  * **SLC Session Scoping:** The `📄 SLC Issued` filter mirrors this clean scoping, isolating students who were issued departure certificates during that specific academic session.
* **Permanent Ledger Deletion Suppression**:
  * **UI Suppression:** The Delete (trash can) button is completely hidden in both List View and Grid View for all graduated, passed out, SLC issued, withdrawn, or struck-off students.
  * **Backend Protection:** Added pre-emptive integrity validation in `delete($id)` and `forceDeleteStudent($id)` within `StudentManager.php`, rejecting any deletion attempt on departed students.
* **Session & Class Selector Layout Overhaul**:
  * **Fixed Flexbox Width Blowout:** Resolved an issue where uncompiled `w-full` utility classes caused the session dropdown to span 70% of the screen, squishing the search input into an unreadable snippet (`...roll`) and hiding the class selector.
  * **Responsive Width Bounding:** Applied explicit responsive widths (`sm:w-56` for Session, `sm:w-48` for Class, `min-w-[180px]` for Search) with `min-w-0` on flex parents.
  * **Visual Design:** Added branded SVG icons (Blue Calendar for Session, Indigo Classroom for Class) and explicit session status tags (`(Current)` / `(Archived)`).
  * **Historical Session Discovery:** Replaced `AcademicSession::active()` with `AcademicSession::orderBy('start_date', 'desc')` in `mount()`, enabling administrators to inspect any historical session.
  * **State Synchronization:** Switching sessions automatically resets the selected class, clears pagination, and unchecks selected student checkboxes.
* **More Filters Drawer Livewire Reactivity**:
  * **Livewire State:** Moved filter drawer visibility directly into Livewire (`public $showFilters = false;` with `toggleFilters()`), removing dependencies on uninstalled Alpine collapse plugins.
  * **Form Resiliency:** Prevented drawer snap-shut glitch where Livewire round-trips from changing dropdowns caused Alpine state resets.
  * **Adaptive Controls:** Added dynamic button label (`More Filters` / `Hide Filters`), active filter counter badge, and an animated rotating chevron.
* **Microsoft Excel (.xls) Export Engine**:
  * **Institutional Headings:** Implemented `exportExcel()` streaming authentic XML-based spreadsheets (`application/vnd.ms-excel`) with Calibri typography, styled dark emerald headers (`#047857`), and formal headers (School Name, Official Report title, Session, Class, Total Students, and Generation Timestamp).
  * **Format Preservation:** Applied `mso-number-format:"\@"` to preserve leading zeros on Admission Numbers, Roll Numbers, CNICs, and Phone Numbers.
* **Responsive Command Ribbon**:
  * Replaced ~700px of competing KPI boxes with a sleek, horizontally scrollable segmented status ribbon showing live metrics for `Active`, `All in Session`, `Graduated`, `Alumni Directory`, `SLC Issued`, `Struck Off`, and `Recycle Bin`.

---

### 2. Module: Student Portal & Sibling Hub
* **Terminal Class Resolution**:
  * **Root Cause Fix:** Fixed an issue where the student portal summary card rendered literal text `"Class"` instead of the student's true graduation class (e.g. `12C`) due to `$student->class` scoping exclusively to active sessions.
  * **Fallback Engine:** Added `getTerminalClassAttribute()` and `getTerminalClassNameAttribute()` on `Student.php` to retrieve the latest historical enrollment class for departed alumni.
* **Gated Departed Access**:
  * Display a formal institutional completion banner ("You have graduated from this institution" or "School Leaving Certificate Issued") while withholding live active student panel components (daily attendance, current datesheets, ongoing fee generation).
* **Family Unit Sibling Switcher**:
  * Upgraded sibling switcher to differentiate active studying siblings from departed/graduated siblings, keeping their historical transcripts accessible while preventing active operations.

---

## [v2.7.1] - 2026-11-05

### 3. Module: Academic Sessions & Session Manager
* **Session Timeline Inception & Archival Isolation**:
  * **Database Migration:** Added `archived_academic_session_id` to `classes` table with historical backfilling.
  * **Future Classroom Leak Prevention:** Classrooms created in newer academic sessions (e.g. Session 2 / 2027–2028) **never leak** into past concluded sessions (e.g. Session 1 / 2026–2027).
  * **Historical Archive Integrity:** Classrooms archived in newer sessions remain visible and active in historical sessions where they were operational.
* **Hybrid Session Immutability**:
  * **Mutative Guards:** Concluded sessions are sealed into a Read-Only Archive Mode across `Enrollment`, `Attendance`, and `FeeRecord` models.
  * **Clerical Correction Exception:** Master biographical fields (Student Name spelling, Father's Name, DOB, B-Form / CNIC) remain editable across all sessions with automated audit trails recorded in `activity_logs`.
  * **Data Export Availability:** Full historical reporting, CSV export, Excel export, and 360° student portfolio viewing remain active in historical sessions.
* **Sealed Archive UI**:
  * Historical sessions display a prominent amber banner (`Historical Academic Session - Sealed Archive`).
  * Mutative buttons (Add Student, Add Class, Rename, Archive, Delete) are replaced with read-only badges.

---

### 4. Module: Institutional Classroom Hierarchy
* **Early Childhood Education (ECE / Montessori) Scaling**:
  * Full support for negative numerical grade levels (Montessori = -3, Nursery = -2, Prep / KG = -1, Kindergarten = 0) sorting logically before Class 1.
* **Custom Hyphenated Section Naming**:
  * Fully accommodates non-alphabetic, descriptive, and hyphenated section names (e.g., `Rose`, `Tulip`, `Morning-A`, `Pre-Engineering`).
* **Active vs. Archived Classroom Separation**:
  * Segregated active classrooms from decommissioned ones via dedicated tabs.
* **Pristine Classroom Direct Deletion**:
  * Classrooms created by mistake with zero historical enrollments can be permanently purged; rooms with academic or fee history are protected with soft-archival.
* **Permanent Classrooms Progression Architecture**:
  * Configured `promotes_to_class_id` mapping for automated year-end promotion transitions.

---

## [v2.7.0] - 2026-11-02

### 5. Module: Security, Cryptography & Audit Hygiene (Audit Report Remediations)
* **Cryptographic Blind Indexing (`HMAC-SHA256`)**:
  * Normalized Father CNIC, Guardian CNIC, B-Form/CRC, and Phone numbers into deterministic blind indexes (`*_hash`).
  * Enables fast $O(1)$ lookups without full-table scans or decrypting sensitive PII in memory.
* **Two-Way Envelope Encryption (`AES-256-CBC`)**:
  * Sensitive student and guardian identity fields encrypted at rest using Laravel's cryptographic encrypter.
* **Tamper-Evident Cryptographic Audit Logging**:
  * Implemented `activity_logs` subsystem with SHA-256 hash chaining to ensure log entries cannot be modified or purged without breaking chain verification.
* **Database Engine Invariants**:
  * Enforced foreign key constraints, explicit indexing, and cascade delete safeguards across all relational tables.

---

### 6. Module: Student Lifecycle, SLC Departure Wizard & Financial Clearance
* **3-Step SLC Departure Wizard**:
  * **Step 1 (Financial Clearance):** Verifies fee balances; integrates an administrative Hardship Fee Waiver modal.
  * **Step 2 (Academic Assessment):** Verifies library, exam, and conduct clearances.
  * **Step 3 (Certificate Sealing):** Issues authentic, tamper-evident School Leaving Certificates (SLC) with unique serial numbers.
* **Alumni B-Form Lookup & Re-Admission**:
  * Instant auto-fill of historical biographical data upon entering a returning student's 13-digit B-Form, preserving original admission records while creating new enrollments.
* **360° Lifetime History Portfolio**:
  * Aggregated timeline modal detailing a student's complete institutional journey (enrollment history, class progressions, exam grades, attendance percentages, fee ledger summaries, and conduct remarks).

---

### 7. Module: Examination Lifecycle (M5 Exam Engine)
* **Multi-Term Exam Scheduling**:
  * Comprehensive exam lifecycle: Exam creation, Class assignment, Datesheet scheduling, and Marks entry.
* **Grading Scales & Marksheets**:
  * Dynamic grading scales (percentage brackets, grade points, pass/fail thresholds).
  * Automated generation of formal institutional report cards and consolidated gazettes.

---

### 8. Module: Attendance & RFID IoT Subsystem
* **Dual-Shift & Regular Attendance**:
  * Real-time attendance logging supporting Morning, Evening, and Regular institutional shifts.
* **RFID IoT Device Integration**:
  * Automated card punch recording with duplicate tap debouncing and missing scan alerts.

---

### 9. Module: Licensing, Multi-Shift & Infrastructure Automation
* **License Verification Engine**:
  * Cryptographic hardware-locked license verification service with offline validation grace periods.
* **Cross-Platform Startup & Service Daemons**:
  * Production scripts for both Linux (systemd) and Windows service wrappers.
* **Shift Switching & Data Scope Isolation**:
  * Multi-shift session shifters ensuring teachers and administrators only see students belonging to their active shift.

---

## Summary of Verification & Test Coverage

All modules are guarded by automated feature and integration tests:

| Test Suite | Tests | Assertions | Status |
|---|---|---|---|
| `Tests\Feature\GraduatedAndAlumniStudentPortalTest` | 9 | 55 | 🟢 PASS |
| `Tests\Feature\ClassManagerTest` | 12 | 58 | 🟢 PASS |
| `Tests\Feature\HybridSessionImmutabilityTest` | 7 | 25 | 🟢 PASS |
| `Tests\Feature\StudentLifecycleTest` | 11 | 83 | 🟢 PASS |
| `Tests\Feature\StudentBFormSecurityTest` | 8 | 54 | 🟢 PASS |
| `Tests\Feature\StudentShiftAssignmentTest` | 5 | 29 | 🟢 PASS |
| `Tests\Feature\PermanentClassroomArchitectureTest` | 6 | 15 | 🟢 PASS |
| **Total Automated Coverage** | **58** | **319** | 🟢 **100% Pass** |
