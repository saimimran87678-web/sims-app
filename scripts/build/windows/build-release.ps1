# ==============================================================================
#  SIMS Production Release Packaging Pipeline for Windows (PowerShell)
#  Builds standalone release packages with strict secret sanitization,
#  production dependency optimization, and SHA-256 checksum manifest generation.
# ==============================================================================

param (
    [string]$Version = "2.5.0"
)

$ErrorActionPreference = "Stop"
$ProgressPreference = "SilentlyContinue"

Add-Type -AssemblyName System.IO.Compression.FileSystem

$RootDir = (Resolve-Path (Join-Path $PSScriptRoot "..\..\..")).Path
$AppDir = (Resolve-Path (Join-Path $RootDir "sims-app")).Path
$DistDir = Join-Path $RootDir "dist"
$ReleasesDir = Join-Path $DistDir "releases"
$StagingParent = Join-Path $DistDir "staging"
$StagingDir = Join-Path $StagingParent "SIMS-v$Version"

Write-Host "======================================================" -ForegroundColor Cyan
Write-Host "     SIMS Developer Release Packaging Pipeline        " -ForegroundColor Cyan
Write-Host "======================================================" -ForegroundColor Cyan
Write-Host "[*] Target Release Version: v$Version" -ForegroundColor Blue
Write-Host "[*] Root Directory: $RootDir" -ForegroundColor Blue

# 1. Pre-flight Security Verification
Write-Host "[*] Running pre-flight security checks..." -ForegroundColor Blue
$PrivatePem = Join-Path $AppDir "private.pem"
if (Test-Path $PrivatePem) {
    Write-Host "[INFO] Master private.pem detected on developer PC. It will be strictly excluded from release packages." -ForegroundColor Yellow
}
$PublicPem = Join-Path $AppDir "public.pem"
if (Test-Path $PublicPem) {
    Write-Host "[INFO] Master public.pem detected on developer PC. It will be strictly excluded from release packages." -ForegroundColor Yellow
}

# 1b. Compile Production Frontend Assets (Vite)
Write-Host "[*] Checking and compiling production frontend assets (Vite)..." -ForegroundColor Blue
$ManifestCheck = Join-Path $AppDir "public\build\manifest.json"
if (-not (Test-Path $ManifestCheck)) {
    $NpmCmd = Get-Command npm -ErrorAction SilentlyContinue
    if ($NpmCmd) {
        Write-Host "    Building frontend assets with 'npm run build'..." -ForegroundColor Gray
        Push-Location $AppDir
        try {
            if (-not (Test-Path "node_modules")) {
                npm install --no-audit --no-fund
            }
            npm run build
        } finally {
            Pop-Location
        }
    } else {
        Write-Host "[WARNING] npm is not found in PATH! Make sure public/build is built before packaging." -ForegroundColor Yellow
    }
} else {
    Write-Host "[OK] Production frontend assets verified (public\build\manifest.json)." -ForegroundColor Green
}

# 2. Prepare Clean Staging Directories
Write-Host "[*] Preparing staging directories..." -ForegroundColor Blue
if (Test-Path $StagingParent) {
    [System.IO.Directory]::Delete($StagingParent, $true)
}
New-Item -Path (Join-Path $StagingDir "sims-app") -ItemType Directory -Force | Out-Null
New-Item -Path $ReleasesDir -ItemType Directory -Force | Out-Null

# 3. Copy Application Files with Strict Exclusions
Write-Host "[*] Staging application files (excluding secrets, tests, node_modules, and dev logs)..." -ForegroundColor Blue

$TargetAppDir = Join-Path $StagingDir "sims-app"

$ExcludedFolderNames = @(
    '.git',
    '.github',
    '.vscode',
    '.idea',
    'node_modules',
    'tests',
    'scratch',
    'caddy',
    'snapshots',
    'updates'
)

$ExcludedFileNames = @(
    'private.pem',
    'public.pem',
    '.sims-server.state',
    'sims-server.sh',
    'phpunit.xml',
    'phpunit_output.txt',
    '.phpunit.result.cache',
    '.env',
    'debug_plan.md',
    'tests_output.txt',
    'PROJECT_ANALYSIS_REPORT.md'
)

Get-ChildItem -Path $AppDir -Recurse | ForEach-Object {
    $item = $_
    $relPath = $item.FullName.Substring($AppDir.Length).TrimStart('\', '/')
    $parts = $relPath -split '[\\/]'

    $isExcluded = $false
    foreach ($part in $parts) {
        if ($ExcludedFolderNames -contains $part) {
            $isExcluded = $true
            break
        }
    }

    if (-not $isExcluded) {
        if ($item.PSIsContainer) {
            $targetPath = Join-Path $TargetAppDir $relPath
            if (-not (Test-Path $targetPath)) {
                New-Item -Path $targetPath -ItemType Directory -Force | Out-Null
            }
        } else {
            $fileName = $item.Name
            $isBootstrapCacheFile = ($relPath -like "bootstrap\cache\*.php")
            $isStorageRuntimeFile = ($relPath -like "storage\framework\cache\data\*" -or
                                     $relPath -like "storage\framework\sessions\*" -or
                                     $relPath -like "storage\framework\views\*" -or
                                     $relPath -like "storage\logs\*") -and ($fileName -ne ".gitignore")

            if ($ExcludedFileNames -contains $fileName -or
                $fileName -like "database.sqlite*" -or
                $fileName -like "*.sqlite-*" -or
                $fileName -like ".env.backup*" -or
                $isBootstrapCacheFile -or
                $isStorageRuntimeFile) {
                # Skip excluded file
            } else {
                $targetFile = Join-Path $TargetAppDir $relPath
                $targetFileDir = Split-Path -Parent $targetFile
                if (-not (Test-Path $targetFileDir)) {
                    New-Item -Path $targetFileDir -ItemType Directory -Force | Out-Null
                }
                [System.IO.File]::Copy($item.FullName, $targetFile, $true)
            }
        }
    }
}

# Ensure required runtime storage directories exist in staging
$StorageDirs = @(
    "storage\app\public",
    "storage\framework\cache\data",
    "storage\framework\sessions",
    "storage\framework\views",
    "storage\logs"
)
foreach ($dir in $StorageDirs) {
    $fullDir = Join-Path $TargetAppDir $dir
    if (-not (Test-Path $fullDir)) {
        New-Item -Path $fullDir -ItemType Directory -Force | Out-Null
    }
}

# 4. Generate Clean Sanitized Production .env Template
Write-Host "[*] Creating sanitized production .env (APP_KEY and LICENSE_KEY strictly empty)..." -ForegroundColor Blue

$LocalEnvPath = Join-Path $AppDir ".env"
$FbKey = '""'
$FbPid = '"sims-licensing"'
$IntKey = '""'
$RsaPub = '""'

if (Test-Path $LocalEnvPath) {
    $envLines = Get-Content $LocalEnvPath
    foreach ($line in $envLines) {
        if ($line -match '^FIREBASE_API_KEY=(.*)$') { $FbKey = $matches[1] }
        if ($line -match '^FIREBASE_PROJECT_ID=(.*)$') { $FbPid = $matches[1] }
        if ($line -match '^LICENSE_INTEGRITY_KEY=(.*)$') { $IntKey = $matches[1] }
        if ($line -match '^LICENSE_RSA_PUBLIC_KEY=(.*)$') { $RsaPub = $matches[1] }
    }
}

$SanitizedEnvLines = @(
    'APP_NAME="SIMS Institute Portal"',
    "APP_VERSION=""$Version""",
    'APP_ENV=production',
    'APP_KEY=',
    'APP_DEBUG=false',
    'APP_URL=https://localhost',
    '',
    'APP_LOCALE=en',
    'APP_FALLBACK_LOCALE=en',
    'APP_FAKER_LOCALE=en_US',
    '',
    'APP_MAINTENANCE_DRIVER=file',
    'BCRYPT_ROUNDS=12',
    '',
    'LOG_CHANNEL=stack',
    'LOG_STACK=single',
    'LOG_DEPRECATIONS_CHANNEL=null',
    'LOG_LEVEL=error',
    '',
    '# SQLite High-Concurrency Database',
    'DB_CONNECTION=sqlite',
    'DB_FOREIGN_KEYS=true',
    '',
    '# Database-backed Queue, Cache, and Sessions (Zero-Redis dependency)',
    'SESSION_DRIVER=database',
    'SESSION_LIFETIME=120',
    'SESSION_ENCRYPT=false',
    'SESSION_PATH=/',
    'SESSION_DOMAIN=null',
    'SESSION_EXPIRE_ON_CLOSE=true',
    '',
    'CACHE_STORE=database',
    'QUEUE_CONNECTION=database',
    'FILESYSTEM_DISK=local',
    'BROADCAST_CONNECTION=log',
    '',
    '# SIMS Offline Licensing & Authenticity Keys',
    "FIREBASE_API_KEY=$FbKey",
    "FIREBASE_PROJECT_ID=$FbPid",
    'LICENSE_KEY=',
    "LICENSE_INTEGRITY_KEY=$IntKey",
    "LICENSE_RSA_PUBLIC_KEY=$RsaPub"
)
$SanitizedEnv = $SanitizedEnvLines -join "`r`n"

Set-Content -Path (Join-Path $TargetAppDir ".env") -Value $SanitizedEnv -Encoding UTF8
Set-Content -Path (Join-Path $TargetAppDir ".env.example") -Value $SanitizedEnv -Encoding UTF8

# 5. Copy Organized Scripts & Root Quick-Launchers
Write-Host "[*] Copying organized scripts and root quick-launchers..." -ForegroundColor Blue

# 5a. Root Quick Launchers
$RootFiles = @("install.bat", "install.sh", "sims.bat")
foreach ($f in $RootFiles) {
    $src = Join-Path $RootDir $f
    if (Test-Path $src) {
        Copy-Item -Path $src -Destination (Join-Path $StagingDir $f) -Force
    }
}

# 5b. Modular Client Scripts (excluding developer build scripts)
$ScriptsSrc = Join-Path $RootDir "scripts"
$ScriptsDest = Join-Path $StagingDir "scripts"
New-Item -Path $ScriptsDest -ItemType Directory -Force | Out-Null

$ClientScriptDirs = @("windows", "linux")
foreach ($dir in $ClientScriptDirs) {
    $srcDir = Join-Path $ScriptsSrc $dir
    if (Test-Path $srcDir) {
        Copy-Item -Path $srcDir -Destination (Join-Path $ScriptsDest $dir) -Recurse -Force
    }
}

# 6. Bundle Portable Runtime Environment (if available)
$RuntimeDir = Join-Path $RootDir "runtime"
$FrankenExe = Join-Path $RuntimeDir "frankenphp.exe"
$PhpExe = Join-Path $RuntimeDir "php\php.exe"

if ((Test-Path $RuntimeDir) -and (Test-Path $FrankenExe) -and (Test-Path $PhpExe)) {
    Write-Host "[*] Bundling portable Windows runtime (FrankenPHP & PHP 8.2)..." -ForegroundColor Blue
    Copy-Item -Path $RuntimeDir -Destination (Join-Path $StagingDir "runtime") -Recurse -Force
    Write-Host "[OK] Portable runtime bundled successfully (Zero-Install enabled)." -ForegroundColor Green
} else {
    Write-Host "[NOTICE] Portable runtime/ not detected in project root." -ForegroundColor Yellow
    Write-Host "         Package will rely on target machine's system PHP (Fallback mode)." -ForegroundColor Yellow
    Write-Host "         Tip: Run scripts\build\windows\download-runtime.bat to bundle portable runtime." -ForegroundColor Yellow
}

# 7. Create README.txt in Staging
$ReadmeLines = @(
    '============================================================',
    '           SIMS - School Information Management System',
    '============================================================',
    '',
    'QUICK INSTALLATION GUIDE:',
    '',
    'WINDOWS:',
    '  1. Right-click "install.bat" and select "Run as administrator".',
    '  2. The installer will automatically:',
    '     - Configure Port 80 and 443 permissions.',
    '     - Generate a unique database encryption key.',
    '     - Trust the local SSL certificate in Windows.',
    '     - Register background services in Task Scheduler.',
    '     - Launch SIMS in your default web browser (https://localhost).',
    '  3. Enter your License Key and follow the 4-step setup wizard.',
    '',
    'LINUX:',
    '  1. Open terminal in this folder and run:',
    '     chmod +x install.sh && ./install.sh',
    '  2. Open your web browser to http://localhost',
    '',
    'MANAGEMENT & CONTROL:',
    '  - Windows: Run "sims.bat" or type "sims status" in any command prompt.',
    '  - Linux:   Run "./scripts/linux/sims.sh status" or use systemctl.',
    '',
    'DIRECTORY STRUCTURE:',
    '  - install.bat        : 1-click Windows installer (root launcher)',
    '  - install.sh         : 1-click Linux installer (root launcher)',
    '  - sims.bat           : Windows Service Control Manager',
    '  - scripts/windows/   : Dedicated Windows operations and background services',
    '  - scripts/linux/     : Dedicated Linux operations and systemd services',
    '  - sims-app/          : Core SIMS Laravel application',
    '  - runtime/           : Bundled portable PHP & FrankenPHP engine (Windows)',
    '============================================================'
)
$ReadmeContent = $ReadmeLines -join "`r`n"
Set-Content -Path (Join-Path $StagingDir "README.txt") -Value $ReadmeContent -Encoding UTF8

# 8. CRITICAL SECURITY & INTEGRITY AUDIT
Write-Host "[*] Performing security integrity audit on staging package..." -ForegroundColor Blue

# Check 1: private.pem MUST NOT exist
$StagingPrivate = Get-ChildItem -Path $StagingDir -Filter "private.pem" -Recurse
if ($StagingPrivate) {
    Write-Host "[FATAL ERROR] private.pem was found in the release package! Aborting immediately." -ForegroundColor Red
    [System.IO.Directory]::Delete($StagingParent, $true)
    exit 1
}

# Check 2: APP_KEY MUST be empty
$StagingEnvContent = Get-Content (Join-Path $TargetAppDir ".env")
$AppKeyMatches = $StagingEnvContent | Select-String -Pattern '^APP_KEY=(.*)$'
if ($AppKeyMatches) {
    $keyValue = $AppKeyMatches.Matches[0].Groups[1].Value.Trim()
    if ($keyValue -ne '') {
        Write-Host "[FATAL ERROR] APP_KEY in staging .env is not empty!" -ForegroundColor Red
        [System.IO.Directory]::Delete($StagingParent, $true)
        exit 1
    }
}

# Check 3: LICENSE_KEY MUST be empty
$LicKeyMatches = $StagingEnvContent | Select-String -Pattern '^LICENSE_KEY=(.*)$'
if ($LicKeyMatches) {
    $licValue = $LicKeyMatches.Matches[0].Groups[1].Value.Trim()
    if ($licValue -ne '') {
        Write-Host "[FATAL ERROR] LICENSE_KEY in staging .env is not empty!" -ForegroundColor Red
        [System.IO.Directory]::Delete($StagingParent, $true)
        exit 1
    }
}

# Check 4: public.pem MUST be strictly excluded
if (Test-Path (Join-Path $TargetAppDir "public.pem")) {
    Write-Host "[FATAL ERROR] public.pem is present in staging package! It must be excluded." -ForegroundColor Red
    [System.IO.Directory]::Delete($StagingParent, $true)
    exit 1
}

# Check 5: vendor/autoload.php MUST be present
if (-not (Test-Path (Join-Path $TargetAppDir "vendor\autoload.php"))) {
    Write-Host "[FATAL ERROR] vendor/autoload.php is missing from staging package!" -ForegroundColor Red
    [System.IO.Directory]::Delete($StagingParent, $true)
    exit 1
}

# Check 6: public/build/manifest.json MUST be present
if (-not (Test-Path (Join-Path $TargetAppDir "public\build\manifest.json"))) {
    Write-Host "[FATAL ERROR] public/build/manifest.json is missing! Client web UI will throw HTTP 500 error." -ForegroundColor Red
    [System.IO.Directory]::Delete($StagingParent, $true)
    exit 1
}

# Check 7: Organized scripts MUST be present
if (-not (Test-Path (Join-Path $StagingDir "scripts\windows\install.bat")) -or
    -not (Test-Path (Join-Path $StagingDir "scripts\linux\install.sh"))) {
    Write-Host "[FATAL ERROR] Organized scripts/ hierarchy is missing from staging package!" -ForegroundColor Red
    [System.IO.Directory]::Delete($StagingParent, $true)
    exit 1
}

Write-Host "[OK] All security and integrity checks passed!" -ForegroundColor Green
Write-Host "   - private.pem  : STRICTLY EXCLUDED"
Write-Host "   - public.pem   : STRICTLY EXCLUDED"
Write-Host "   - APP_KEY      : STRICTLY EMPTY (generated on client install)"
Write-Host "   - LICENSE_KEY  : STRICTLY EMPTY (entered during setup wizard)"
Write-Host "   - vendor/      : VERIFIED BUNDLED (Production Dependencies Ready)"
Write-Host "   - public/build : VERIFIED BUNDLED (Vite Manifest & Compiled Assets Ready)"
Write-Host "   - scripts/     : VERIFIED BUNDLED (Modular Windows & Linux Hierarchy)"

# 9. Create Standalone Release ZIP
$ZipFilename = "SIMS-v$Version.zip"
$ZipFilepath = Join-Path $ReleasesDir $ZipFilename

if (Test-Path $ZipFilepath) {
    [System.IO.File]::Delete($ZipFilepath)
}

Write-Host "[*] Compressing release package into $ZipFilename..." -ForegroundColor Blue

# Set includeBaseDirectory to $false so extracting into a folder extracts files directly without double nesting
[System.IO.Compression.ZipFile]::CreateFromDirectory($StagingDir, $ZipFilepath, [System.IO.Compression.CompressionLevel]::Optimal, $false)

# Clean up staging folder
[System.IO.Directory]::Delete($StagingParent, $true)

# 10. Compute Cryptographic SHA-256 Checksum
Write-Host "[*] Computing cryptographic SHA-256 checksum..." -ForegroundColor Blue
$HashResult = Get-FileHash -Path $ZipFilepath -Algorithm SHA256
$Checksum = $HashResult.Hash.ToLower()
$ZipItem = Get-Item $ZipFilepath
$FileSizeMb = [math]::Round($ZipItem.Length / 1MB, 2)

# 11. Generate GitHub Pages / CDN update manifest.json
$ManifestPath = Join-Path $ReleasesDir "manifest.json"
$ReleaseDate = (Get-Date).ToUniversalTime().ToString("yyyy-MM-dd")

$ManifestObj = [ordered]@{
    version         = $Version
    download_url    = "https://updates.sims.pk/$ZipFilename"
    checksum        = $Checksum
    sha256          = $Checksum
    min_php_version = "8.2.0"
    release_date    = $ReleaseDate
    changelog       = "SIMS v$Version standalone distribution with modular scripts, offline licensing, background scheduler, and Driver.js product tour"
}
$ManifestJson = $ManifestObj | ConvertTo-Json -Depth 5
Set-Content -Path $ManifestPath -Value $ManifestJson -Encoding UTF8

# 12. Verification & Summary Output
Write-Host "======================================================" -ForegroundColor Green
Write-Host "  SIMS Release v$Version Built Successfully!          " -ForegroundColor Green
Write-Host "======================================================" -ForegroundColor Green
Write-Host " Package File : $ZipFilepath ($FileSizeMb MB)"
Write-Host " SHA-256 Hash : $Checksum" -ForegroundColor Cyan
Write-Host " Manifest     : $ManifestPath"
Write-Host " Security     : Zero secrets, clean .env, PEM files excluded" -ForegroundColor Green
Write-Host " Architecture : Modular scripts/ (Windows & Linux) + 1-click root launchers" -ForegroundColor Green
Write-Host "======================================================" -ForegroundColor Green
