<#
.SYNOPSIS
    SIMS Automated Lightweight Delta Patch Packaging Engine (Windows PowerShell)

.DESCRIPTION
    Packages only modified application code (app/, resources/, routes/, database/migrations/, config/, scripts/)
    into a lightweight delta patch archive (typically ~1-2 MB).
    Computes cryptographic SHA-256 checksum and generates a production-ready manifest.json for over-the-air updates.

.PARAMETER Version
    Target semantic version string (e.g., "2.5.1"). Defaults to auto-detect from config or prompt.

.PARAMETER Changelog
    Summary of changes included in this patch release.

.PARAMETER MinPhpVersion
    Minimum PHP runtime version required (defaults to "8.2.0").
#>

[CmdletBinding()]
param(
    [Parameter(Position = 0)]
    [string]$Version = "",

    [Parameter(Position = 1)]
    [string]$Changelog = "Maintenance release, bug fixes, and security patches.",

    [Parameter(Position = 2)]
    [string]$MinPhpVersion = "8.2.0",

    [Parameter()]
    [string]$RepoUrl = "https://github.com/saimimran87678/SIMS"
)

Set-StrictMode -Version Latest
$ErrorActionPreference = "Stop"

# Helper for console formatting
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

# 1. Resolve Root and Application Paths
$ScriptDir = Split-Path -Parent $MyInvocation.MyCommand.Definition
$RootDir = (Resolve-Path "$ScriptDir\..\..\..").Path
$AppDir = Join-Path $RootDir "sims-app"
$DistDir = Join-Path $RootDir "dist\patches"

Write-Header "SIMS Delta Patch Packaging Engine"
Write-Info "Repository Root: $RootDir"
Write-Info "Application Dir: $AppDir"

if (-not (Test-Path "$AppDir\artisan")) {
    Write-Failure "Could not locate Laravel application at $AppDir!"
    exit 1
}

# 2. Resolve Version
if ([string]::IsNullOrWhiteSpace($Version)) {
    $VersionFile = Join-Path $AppDir "config\app.php"
    if (Test-Path $VersionFile) {
        $Match = Select-String -Path $VersionFile -Pattern "'version'\s*=>\s*env\('APP_VERSION',\s*'([^']+)'\)" | Select-Object -First 1
        if ($Match -and $Match.Matches.Groups.Count -gt 1) {
            $CurrentVer = $Match.Matches.Groups[1].Value
            $Parts = $CurrentVer.Split('.')
            if ($Parts.Count -eq 3) {
                $NewPatch = [int]$Parts[2] + 1
                $Version = "$($Parts[0]).$($Parts[1]).$NewPatch"
            }
        }
    }
    if ([string]::IsNullOrWhiteSpace($Version)) {
        $Version = "2.5.1"
    }
}

Write-Success "Target Patch Version: v$Version"
Write-Info "Changelog: $Changelog"

# 3. Create Clean Staging Directory
if (-not (Test-Path $DistDir)) {
    New-Item -ItemType Directory -Path $DistDir -Force | Out-Null
}

$StagingDir = Join-Path $DistDir "staging_patch_v$Version"
if (Test-Path $StagingDir) {
    Remove-Item -Recurse -Force $StagingDir
}
New-Item -ItemType Directory -Path $StagingDir -Force | Out-Null

# 4. Copy Delta Code Files Only (Excluding state, database, secrets, vendor, runtime)
Write-Info "Collecting application code for delta package..."

$SourceAppDir = Join-Path $StagingDir "sims-app"
New-Item -ItemType Directory -Path $SourceAppDir -Force | Out-Null

# Directories to bundle
$DirsToInclude = @("app", "resources", "routes", "database\migrations", "config")
foreach ($Dir in $DirsToInclude) {
    $Src = Join-Path $AppDir $Dir
    $Dst = Join-Path $SourceAppDir $Dir
    if (Test-Path $Src) {
        Copy-Item -Path $Src -Destination (Split-Path -Parent $Dst) -Recurse -Force
        Write-Success "Included: sims-app\$Dir"
    }
}

# Bundle updated operational scripts
$ScriptsSrc = Join-Path $RootDir "scripts"
$ScriptsDst = Join-Path $StagingDir "scripts"
if (Test-Path $ScriptsSrc) {
    Copy-Item -Path $ScriptsSrc -Destination $StagingDir -Recurse -Force
    # Exclude build scripts from client patch
    if (Test-Path "$ScriptsDst\build") {
        Remove-Item -Recurse -Force "$ScriptsDst\build"
    }
    Write-Success "Included: scripts/ (operational runners)"
}

# Copy top-level launcher stubs
foreach ($Launcher in @("install.bat", "install.sh", "sims.bat")) {
    $LauncherPath = Join-Path $RootDir $Launcher
    if (Test-Path $LauncherPath) {
        Copy-Item -Path $LauncherPath -Destination $StagingDir -Force
    }
}

# 5. Sanitize Staging Folder (Strict Secret & State Purge)
Write-Info "Sanitizing staging files..."
Get-ChildItem -Path $StagingDir -Recurse -Include *.pem, *.key, *.log, *.sqlite*, .env*, .sims-server.state, *.tmp | ForEach-Object {
    Remove-Item -Force $_.FullName
}

# 6. Compress into Delta ZIP Package
$ZipFileName = "sims-patch-v$Version.zip"
$ZipFilePath = Join-Path $DistDir $ZipFileName

if (Test-Path $ZipFilePath) {
    Remove-Item -Force $ZipFilePath
}

Write-Info "Compressing patch into $ZipFileName..."
Add-Type -AssemblyName System.IO.Compression.FileSystem
[System.IO.Compression.ZipFile]::CreateFromDirectory($StagingDir, $ZipFilePath, [System.IO.Compression.CompressionLevel]::Optimal, $false)

# Clean up temporary staging
Remove-Item -Recurse -Force $StagingDir

# 7. Compute SHA-256 Checksum
Write-Info "Calculating SHA-256 cryptographic integrity hash..."
$FileHash = (Get-FileHash -Path $ZipFilePath -Algorithm SHA256).Hash.ToLowerInvariant()
$FileSizeBytes = (Get-Item $ZipFilePath).Length
$FileSizeMB = [math]::Round($FileSizeBytes / 1MB, 2)

# 8. Generate manifest.json
$DownloadUrl = "$RepoUrl/releases/download/v$Version/$ZipFileName"
$ManifestObj = [ordered]@{
    version         = $Version
    download_url    = $DownloadUrl
    checksum        = $FileHash
    sha256          = $FileHash
    min_php_version = $MinPhpVersion
    changelog       = $Changelog
    size_bytes      = $FileSizeBytes
    released_at     = (Get-Date).ToUniversalTime().ToString("yyyy-MM-ddTHH:mm:ssZ")
}

$ManifestJson = $ManifestObj | ConvertTo-Json -Depth 4
$ManifestPath = Join-Path $DistDir "manifest.json"
$VersionManifestPath = Join-Path $DistDir "manifest-v$Version.json"

[System.IO.File]::WriteAllText($ManifestPath, $ManifestJson, [System.Text.Encoding]::UTF8)
[System.IO.File]::WriteAllText($VersionManifestPath, $ManifestJson, [System.Text.Encoding]::UTF8)

# 9. Output Summary
Write-Header "Delta Patch Successfully Generated!"
Write-Host " Package Archive : $ZipFilePath" -ForegroundColor Green
Write-Host " Package Size    : $FileSizeMB MB ($FileSizeBytes bytes)" -ForegroundColor Green
Write-Host " SHA-256 Hash    : $FileHash" -ForegroundColor Yellow
Write-Host " Manifest File   : $ManifestPath" -ForegroundColor Green
Write-Host " Download URL    : $DownloadUrl" -ForegroundColor Cyan
Write-Host "====================================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Next Step to Publish:" -ForegroundColor White
Write-Host "  1. Create GitHub Release 'v$Version'" -ForegroundColor White
Write-Host "  2. Upload '$ZipFilePath' to the release assets" -ForegroundColor White
Write-Host "  3. Commit and push '$ManifestPath' to the main branch" -ForegroundColor White
Write-Host ""
