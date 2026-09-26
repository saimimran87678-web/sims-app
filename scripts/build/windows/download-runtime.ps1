# ==============================================================================
#  SIMS Portable Windows Runtime Downloader (PowerShell)
#  Downloads and configures FrankenPHP and Portable PHP 8.2 for Windows
#  into the ./runtime/ directory for a 100% self-contained, zero-install package.
# ==============================================================================

$ErrorActionPreference = "Stop"
$ProgressPreference = "SilentlyContinue"

Add-Type -AssemblyName System.IO.Compression.FileSystem

$RootDir = (Resolve-Path (Join-Path $PSScriptRoot "..\..\..")).Path
$RuntimeDir = Join-Path $RootDir "runtime"
$TmpDir = Join-Path $RootDir "dist\runtime_tmp"
$PhpDir = Join-Path $RuntimeDir "php"

Write-Host "======================================================" -ForegroundColor Cyan
Write-Host "     SIMS Windows Portable Runtime Downloader         " -ForegroundColor Cyan
Write-Host "======================================================" -ForegroundColor Cyan
Write-Host "Target Directory: $RuntimeDir`n" -ForegroundColor Blue

if (-not (Test-Path $PhpDir)) { New-Item -Path $PhpDir -ItemType Directory -Force | Out-Null }
if (-not (Test-Path $TmpDir)) { New-Item -Path $TmpDir -ItemType Directory -Force | Out-Null }

function Save-FileWithFallback {
    param (
        [string]$Url,
        [string]$OutputFile
    )
    $curlExe = "C:\Windows\System32\curl.exe"
    if (Test-Path $curlExe) {
        & $curlExe -fSL --connect-timeout 15 --retry 2 "$Url" -o "$OutputFile"
        if ($LASTEXITCODE -eq 0 -and (Test-Path $OutputFile) -and (Get-Item $OutputFile).Length -gt 1000) {
            return $true
        }
    }
    
    try {
        Invoke-WebRequest -Uri $Url -OutFile $OutputFile -UseBasicParsing -TimeoutSec 60
        if ((Test-Path $OutputFile) -and (Get-Item $OutputFile).Length -gt 1000) {
            return $true
        }
    } catch {
        return $false
    }
    return $false
}

# ------------------------------------------------------------------------------
# 1. Download & Extract FrankenPHP for Windows
# ------------------------------------------------------------------------------
$FrankenExe = Join-Path $RuntimeDir "frankenphp.exe"
if (Test-Path $FrankenExe) {
    Write-Host "[OK] FrankenPHP is already present at $FrankenExe" -ForegroundColor Green
} else {
    Write-Host "[1/3] Downloading FrankenPHP for Windows (x86_64)..." -ForegroundColor Blue
    $FrankenZip = Join-Path $TmpDir "frankenphp.zip"
    $FrankenUrl = "https://github.com/php/frankenphp/releases/latest/download/frankenphp-windows-x86_64.zip"
    
    $downloadSuccess = Save-FileWithFallback -Url $FrankenUrl -OutputFile $FrankenZip
    if (-not $downloadSuccess) {
        Write-Host "[ERROR] Failed to download FrankenPHP from $FrankenUrl" -ForegroundColor Red
        exit 1
    }
    Write-Host "[OK] FrankenPHP downloaded successfully. Extracting..." -ForegroundColor Green

    $FrankenExtracted = Join-Path $TmpDir "franken_extracted"
    if (Test-Path $FrankenExtracted) { [System.IO.Directory]::Delete($FrankenExtracted, $true) }
    
    [System.IO.Compression.ZipFile]::ExtractToDirectory($FrankenZip, $FrankenExtracted)
    
    Get-ChildItem -Path $FrankenExtracted -Include *.exe, *.dll -Recurse | ForEach-Object {
        Copy-Item -Path $_.FullName -Destination $RuntimeDir -Force
    }
    Write-Host "[OK] FrankenPHP and companion libraries installed to $RuntimeDir" -ForegroundColor Green
}

# ------------------------------------------------------------------------------
# 2. Download & Extract Portable Windows PHP 8.2 (NTS x64)
# ------------------------------------------------------------------------------
$PhpExe = Join-Path $PhpDir "php.exe"
if (Test-Path $PhpExe) {
    Write-Host "[OK] Portable PHP is already present at $PhpExe" -ForegroundColor Green
} else {
    Write-Host "`n[2/3] Downloading Portable Windows PHP 8.2 (x64 NTS)..." -ForegroundColor Blue
    $PhpZip = Join-Path $TmpDir "php82.zip"
    
    $candidateUrls = @(
        "https://windows.php.net/downloads/releases/php-8.2.28-nts-Win32-vs16-x64.zip",
        "https://windows.php.net/downloads/releases/php-8.2.27-nts-Win32-vs16-x64.zip",
        "https://windows.php.net/downloads/releases/archives/php-8.2.27-nts-Win32-vs16-x64.zip",
        "https://windows.php.net/downloads/releases/archives/php-8.2.26-nts-Win32-vs16-x64.zip",
        "https://windows.php.net/downloads/releases/archives/php-8.2.25-nts-Win32-vs16-x64.zip"
    )

    $phpDownloaded = $false
    foreach ($url in $candidateUrls) {
        Write-Host "Trying PHP source: $url" -ForegroundColor Cyan
        if (Test-Path $PhpZip) { Remove-Item $PhpZip -Force }
        if (Save-FileWithFallback -Url $url -OutputFile $PhpZip) {
            $phpDownloaded = $true
            Write-Host "[OK] PHP archive downloaded from $url" -ForegroundColor Green
            break
        }
    }

    if (-not $phpDownloaded) {
        Write-Host "[ERROR] Could not download Portable PHP 8.2 from windows.php.net" -ForegroundColor Red
        exit 1
    }

    Write-Host "[OK] Extracting PHP into $PhpDir..." -ForegroundColor Green
    [System.IO.Compression.ZipFile]::ExtractToDirectory($PhpZip, $PhpDir)
    Write-Host "[OK] Portable PHP files extracted successfully." -ForegroundColor Green
}

# ------------------------------------------------------------------------------
# 3. Configure production php.ini with required extensions
# ------------------------------------------------------------------------------
Write-Host "`n[3/3] Generating pre-configured php.ini with SQLite, cURL, MBString & Zip..." -ForegroundColor Blue

$PhpIniLines = @(
    '[PHP]',
    'engine = On',
    'short_open_tag = Off',
    'precision = 14',
    'output_buffering = 4096',
    'zlib.output_compression = Off',
    'implicit_flush = Off',
    'serialize_precision = -1',
    'zend.enable_gc = On',
    '',
    'max_execution_time = 300',
    'max_input_time = 120',
    'memory_limit = 512M',
    '',
    'error_reporting = E_ALL & ~E_DEPRECATED & ~E_STRICT',
    'display_errors = Off',
    'display_startup_errors = Off',
    'log_errors = On',
    'error_log = "php_errors.log"',
    '',
    'variables_order = "GPCS"',
    'request_order = "GP"',
    'register_argc_argv = Off',
    'auto_globals_jit = On',
    '',
    'post_max_size = 64M',
    'default_mimetype = "text/html"',
    'default_charset = "UTF-8"',
    '',
    'enable_dl = Off',
    'file_uploads = On',
    'upload_max_filesize = 64M',
    'max_file_uploads = 20',
    '',
    'allow_url_fopen = On',
    'allow_url_include = Off',
    'default_socket_timeout = 60',
    '',
    '; Extensions directory (relative to php.exe)',
    'extension_dir = "ext"',
    '',
    '; Core required extensions for SIMS',
    'extension=curl',
    'extension=fileinfo',
    'extension=mbstring',
    'extension=openssl',
    'extension=pdo_sqlite',
    'extension=sqlite3',
    'extension=zip',
    'extension=gd',
    '',
    '[Date]',
    'date.timezone = Asia/Karachi',
    '',
    '[Session]',
    'session.save_handler = files',
    'session.use_strict_mode = 1',
    'session.use_cookies = 1',
    'session.use_only_cookies = 1',
    'session.name = PHPSESSID',
    'session.auto_start = 0',
    'session.cookie_lifetime = 0',
    'session.cookie_path = /',
    'session.cookie_domain =',
    'session.cookie_httponly = 1',
    'session.cookie_samesite = "Lax"',
    'session.serialize_handler = php',
    'session.gc_probability = 1',
    'session.gc_divisor = 1000',
    'session.gc_maxlifetime = 1440',
    'session.cache_limiter = nocache',
    'session.cache_expire = 180',
    'session.use_trans_sid = 0',
    'session.sid_length = 26',
    'session.trans_sid_tags = "a=href,area=href,frame=src,form="',
    'session.sid_bits_per_character = 5',
    '',
    '[opcache]',
    'opcache.enable = 1',
    'opcache.enable_cli = 0',
    'opcache.memory_consumption = 128',
    'opcache.interned_strings_buffer = 8',
    'opcache.max_accelerated_files = 10000',
    'opcache.max_wasted_percentage = 5',
    'opcache.validate_timestamps = 1',
    'opcache.revalidate_freq = 2',
    'opcache.save_comments = 1',
    '',
    '[curl]',
    'curl.cainfo = "cacert.pem"',
    '',
    '[openssl]',
    'openssl.cafile = "cacert.pem"'
)

$PhpIniContent = $PhpIniLines -join "`r`n"
Set-Content -Path (Join-Path $PhpDir "php.ini") -Value $PhpIniContent -Encoding UTF8
Write-Host "[OK] php.ini generated successfully." -ForegroundColor Green

$CacertFile = Join-Path $PhpDir "cacert.pem"
if (-not (Test-Path $CacertFile)) {
    Write-Host "Downloading Mozilla Root CA bundle (cacert.pem)..." -ForegroundColor Cyan
    Save-FileWithFallback -Url "https://curl.se/ca/cacert.pem" -OutputFile $CacertFile | Out-Null
}

# ------------------------------------------------------------------------------
# 4. Bundle Microsoft Visual C++ 2015-2022 Runtime DLLs (VC15/VS16)
# ------------------------------------------------------------------------------
Write-Host "`n[4/5] Bundling Visual C++ Runtime DLLs (VCRUNTIME140.dll, MSVCP140.dll)..." -ForegroundColor Blue

$vcDllNames = @(
    "vcruntime140.dll",
    "vcruntime140_1.dll",
    "msvcp140.dll",
    "msvcp140_1.dll",
    "msvcp140_2.dll",
    "msvcp140_atomic_wait.dll",
    "msvcp140_codecvt_ids.dll",
    "concrt140.dll",
    "vcomp140.dll"
)

# Priority 1: Check system System32 on the build machine (e.g. GitHub Actions runner)
$system32 = "$env:WINDIR\System32"
foreach ($dll in $vcDllNames) {
    $src = Join-Path $system32 $dll
    if (Test-Path $src) {
        Copy-Item -Path $src -Destination $PhpDir -Force
        Copy-Item -Path $src -Destination $RuntimeDir -Force
    }
}

# Priority 2: Fallback to downloading official Microsoft VC_redist if missing
$checkDll = Join-Path $PhpDir "vcruntime140.dll"
if (-not (Test-Path $checkDll)) {
    Write-Host "Downloading Microsoft VC_redist.x64.exe fallback..." -ForegroundColor Cyan
    $vcRedistUrl = "https://aka.ms/vs/17/release/vc_redist.x64.exe"
    $vcRedistPath = Join-Path $TmpDir "vc_redist.x64.exe"
    if (Save-FileWithFallback -Url $vcRedistUrl -OutputFile $vcRedistPath) {
        Start-Process -FilePath $vcRedistPath -ArgumentList "/install /quiet /norestart" -Wait -WindowStyle Hidden
        foreach ($dll in $vcDllNames) {
            $src = Join-Path $system32 $dll
            if (Test-Path $src) {
                Copy-Item -Path $src -Destination $PhpDir -Force
                Copy-Item -Path $src -Destination $RuntimeDir -Force
            }
        }
    }
}

if (Test-Path (Join-Path $PhpDir "vcruntime140.dll")) {
    Write-Host "[OK] Microsoft Visual C++ runtime libraries bundled successfully." -ForegroundColor Green
} else {
    Write-Host "[WARNING] vcruntime140.dll could not be bundled. Ensure VC++ Redistributable is installed." -ForegroundColor Yellow
}

# ------------------------------------------------------------------------------
# 5. Clean up temporary download cache
# ------------------------------------------------------------------------------
if (Test-Path $TmpDir) {
    [System.IO.Directory]::Delete($TmpDir, $true)
}

# ------------------------------------------------------------------------------
# 5. Verification Test
# ------------------------------------------------------------------------------
Write-Host "`n[*] Verifying installed binaries..." -ForegroundColor Blue
if (Test-Path $FrankenExe) {
    Write-Host "[OK] FrankenPHP verified: $FrankenExe" -ForegroundColor Green
} else {
    Write-Host "[ERROR] FrankenPHP executable not found!" -ForegroundColor Red
    exit 1
}

if (Test-Path $PhpExe) {
    $phpVer = & $PhpExe -r "echo PHP_VERSION;" 2>$null
    Write-Host "[OK] PHP verified: v$phpVer ($PhpExe)" -ForegroundColor Green
} else {
    Write-Host "[ERROR] PHP executable not found!" -ForegroundColor Red
    exit 1
}

Write-Host "`n======================================================" -ForegroundColor Green
Write-Host "  Windows Portable Runtime Installed Successfully!    " -ForegroundColor Green
Write-Host "======================================================" -ForegroundColor Green
Write-Host " FrankenPHP : $FrankenExe"
Write-Host " PHP Binary : $PhpExe"
Write-Host " PHP Config : $(Join-Path $PhpDir 'php.ini')"
Write-Host " Status     : Ready for Zero-Install production distribution" -ForegroundColor Green
Write-Host "======================================================" -ForegroundColor Green
