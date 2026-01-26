# Git Source Control - Beginner's Guide

## What is Git?
Git is a **save system for code**. Like saving a game before a boss fight, Git lets you create "save points" (commits) that you can return to anytime.

**GitHub** is a cloud storage for your Git saves. It lets you access your code from any PC.

---

## What We Did (Step by Step)

### 1. Initialized Git
```bash
git init
```
This created a hidden `.git` folder that tracks all changes.

### 2. Configured Your Identity
```bash
git config --global user.email "saim.imran.87678@gmail.com"
git config --global user.name "Saim Imran"
```
Git needs to know WHO is making changes.

### 3. Added Files to Staging
```bash
git add .
```
The `.` means "all files". This tells Git: "I want to save these files in my next save point."

### 4. Created a Commit (Save Point)
```bash
git commit -m "Initial commit: SIMS App v1.0"
```
This creates a permanent snapshot. The `-m` adds a message describing what changed.

### 5. Created Version Tag
```bash
git tag -a v1.0 -m "Version 1.0"
```
Tags mark important versions. You can always go back to v1.0.

### 6. Connected to GitHub
```bash
git remote add origin https://github.com/saimimran87678-web/sims-app.git
```
This links your local Git to your GitHub repository.

### 7. Pushed to GitHub
```bash
git push -u origin main
```
This uploaded your code to GitHub.

---

## Daily Workflow

### Making Changes
```bash
# 1. Make your code changes in VS Code

# 2. Stage all changes
git add .

# 3. Commit with a message
git commit -m "Fixed login bug"

# 4. Push to GitHub
git push
```

---

## Using VS Code Source Control

1. **Open Source Control Panel**: Click the branch icon (3rd icon in left sidebar)
2. **See Changes**: Modified files appear under "Changes"
3. **Stage Files**: Click `+` next to a file (or `+` on "Changes" header for all)
4. **Commit**: Type message in the text box, click ✓ checkmark
5. **Push**: Click `...` menu → Push (or use Sync button)

---

## On a Fresh PC (Complete Setup)

### Step 1: Clone Repository
```bash
# Get latest version
git clone https://github.com/saimimran87678-web/sims-app.git ~/SIMS/sims-app

# OR get a specific version
git clone --branch v1.001 https://github.com/saimimran87678-web/sims-app.git ~/SIMS/sims-app
```

### Step 2: Install Dependencies
```bash
cd ~/SIMS/sims-app
composer install
npm install
```

### Step 3: Setup Environment
```bash
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate
npm run build
```

### Step 4: Run the App
```bash
php artisan serve
```

> ⚠️ **Important**: Fresh clone = empty database. No users, students, or data!

---

## Transfer Data Between PCs

Git stores **code only**, not database data. Transfer your database manually:

### On Your Current PC (with data):
```bash
cp ~/SIMS/sims-app/database/database.sqlite ~/Desktop/database_backup.sqlite
```

### Transfer to New PC (USB, Google Drive, etc.), then:
```bash
cp ~/Desktop/database_backup.sqlite ~/SIMS/sims-app/database/database.sqlite
```

Now your new PC has all students, teachers, and attendance data! 🎉

---

## Get Latest Changes (Existing Clone)
```bash
git pull
```

## Push Your Changes
```bash
git add .
git commit -m "Description of changes"
git push
```

---

## Going Back in Time

### See All Save Points
```bash
git log --oneline
```

### See All Tags
```bash
git tag -l
```

### Go Back to v1.0
```bash
git checkout v1.0
```

### Return to Latest
```bash
git checkout main
```

---

## Common Commands Cheat Sheet

| Command | What it does |
|---------|--------------|
| `git status` | See what's changed |
| `git add .` | Stage all changes |
| `git commit -m "msg"` | Create save point |
| `git push` | Upload to GitHub |
| `git pull` | Download from GitHub |
| `git log --oneline` | View history |
| `git tag -l` | List all versions |
| `git checkout <tag>` | Go to version |
| `git clone <url>` | Download repo to new PC |
| `git clone --branch <tag> <url>` | Clone specific version |

---

## Your Repository
**URL**: https://github.com/saimimran87678-web/sims-app

**Current Tags**: v1.0, v1.001

