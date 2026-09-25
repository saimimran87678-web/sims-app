<#
.SYNOPSIS
    SIMS Professional Windows Installer Compiler (PowerShell)

.DESCRIPTION
    Compiles the standalone SIMS distribution into a single professional installer:
    SIMS-Installer-v{Version}.exe with One-Time Token validation and post-install self-destruction.
#>

[CmdletBinding()]
param(
    [Parameter(Position = 0)]
    [string]$Version = "2.5.1"
)

Set-StrictMode -Version Latest
$ErrorActionPreference = "Stop"

function Write-Header {
    param([string]$Text)
    Write-Host ""
    Write-Host "====================================================" -ForegroundColor Cyan
    Write-Host " $Text" -ForegroundColor Cyan
    Write-Host "====================================================" -ForegroundColor Cyan
}

function Write-Success {
    param([string]$Text)
    Write-Host " [OK] $Text" -ForegroundColor Green
}

function Write-Info {
    param([string]$Text)
    Write-Host " [INFO] $Text" -ForegroundColor Yellow
}

function Write-Failure {
    param([string]$Text)
    Write-Host " [ERROR] $Text" -ForegroundColor Red
}

$ScriptDir = Split-Path -Parent $MyInvocation.MyCommand.Definition
$RootDir = (Resolve-Path "$ScriptDir\..\..\..").Path
$IssFile = Join-Path $ScriptDir "sims-installer.iss"
$DistDir = Join-Path $RootDir "dist\installer"

Write-Header "SIMS Windows Executable Installer Compiler"
Write-Info "Repository Root : $RootDir"
Write-Info "Inno Script Path: $IssFile"

if (-not (Test-Path $IssFile)) {
    Write-Failure "Inno Setup script not found at $IssFile!"
    exit 1
}

# 1. Locate ISCC.exe
$IsccPaths = @(
    "C:\Program Files (x86)\Inno Setup 6\ISCC.exe",
    "C:\Program Files\Inno Setup 6\ISCC.exe",
    "C:\Program Files (x86)\Inno Setup 5\ISCC.exe"
)

$IsccExe = $null
foreach ($Path in $IsccPaths) {
    if (Test-Path $Path) {
        $IsccExe = $Path
        break
    }
}

if (-not $IsccExe) {
    $WhereResult = where.exe iscc 2>$null
    if ($WhereResult) {
        $IsccExe = $WhereResult[0]
    }
}

if (-not $IsccExe) {
    Write-Info "Inno Setup compiler (ISCC.exe) not found on system."
    Write-Info "Attempting automatic installation via winget..."
    try {
        & winget install --id JRSoftware.InnoSetup -e --accept-source-agreements --accept-package-agreements --silent
        foreach ($Path in $IsccPaths) {
            if (Test-Path $Path) {
                $IsccExe = $Path
                break
            }
        }
    } catch {
        Write-Failure "Automatic installation via winget failed."
    }
}

if (-not $IsccExe) {
    Write-Failure "Please install Inno Setup 6 from https://jrsoftware.org/isdl.php to compile the .exe installer."
    exit 1
}

Write-Success "Found Inno Setup Compiler: $IsccExe"

# 2. Compile Installer
if (-not (Test-Path $DistDir)) {
    New-Item -ItemType Directory -Path $DistDir -Force | Out-Null
}

Write-Info "Compiling single-use disposable installer for version v$Version..."
& "$IsccExe" "/DMyAppVersion=$Version" "$IssFile"

$InstallerExe = Join-Path $DistDir "SIMS-Installer-v$Version.exe"
if (-not (Test-Path $InstallerExe)) {
    Write-Failure "Compilation did not produce expected output at $InstallerExe!"
    exit 1
}

$Hash = (Get-FileHash -Path $InstallerExe -Algorithm SHA256).Hash.ToLowerInvariant()
$SizeMB = [math]::Round((Get-Item $InstallerExe).Length / 1MB, 2)

Write-Header "SIMS Installer Successfully Compiled!"
Write-Host " Output Executable : $InstallerExe" -ForegroundColor Green
Write-Host " File Size         : $SizeMB MB" -ForegroundColor Green
Write-Host " SHA-256 Checksum  : $Hash" -ForegroundColor Yellow
Write-Host "====================================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "How to use with a client school:" -ForegroundColor White
Write-Host "  1. Open firebase_admin_panel.html" -ForegroundColor White
Write-Host "  2. Click '+ Generate 1-Time Token' for the school" -ForegroundColor White
Write-Host "  3. Give the client this SIMS-Installer.exe and their unique Token" -ForegroundColor White
Write-Host "  4. The installer validates with Firebase, locks to hardware, and self-destructs!" -ForegroundColor White
Write-Host ""
