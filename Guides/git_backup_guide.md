# Source Control & Database Guide

## 1. Source Control (Git)
Your project is now initialized with Git. This protects your **code**.

- **To save your work:**
  ```bash
  git add .
  git commit -m "Description of changes"
  ```
- **To sync with another PC:**
  - Create a repository on GitHub.
  - Push your code: `git push origin main`
  - On the other PC: `git clone <your-repo-url>`

## 2. Database Backup (SQLite)
Your database contains **live data** (students, attendance, etc.) and is **ignored** by Git for security.

### How to Backup
Run this simple command at any time:
```bash
php artisan db:backup
```
- This creates a timestamped copy in: `storage/app/backups/`
- Example: `database_2026-01-16_16-55-49.sqlite`

### How to Move to Another PC
1.  **Run the backup command** on your main PC.
2.  **Copy the file** from `sims-app/storage/app/backups/` to your USB or Cloud Drive (Google Drive/Dropbox).
3.  **On the new PC:**
    - Paste the file into `sims-app/database/`
    - **Rename it** to `database.sqlite` (replace the existing empty one).

## Summary
- **Git** = Syncs your **Code** (Logic, Views, CSS)
- **USB/Cloud** = Syncs your **Data** (The SQLite file)
