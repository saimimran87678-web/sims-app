<#
.SYNOPSIS
    Compiles Adminova-Control-Center.exe from C# source code using csc.exe.
#>
[CmdletBinding()]
param()

$ScriptDir = Split-Path -Parent $MyInvocation.MyCommand.Definition
$RootDir = (Resolve-Path "$ScriptDir\..\..\..").Path

$SourceFile = Join-Path $RootDir "scripts\windows\ControlCenter.cs"
$OutputFile = Join-Path $RootDir "Adminova-Control-Center.exe"
$IconFile = Join-Path $RootDir "resources\icons\adminova.ico"
if (-not (Test-Path $IconFile)) {
    $IconFile = Join-Path $RootDir "scripts\build\windows\app.ico"
}

Write-Host "====================================================" -ForegroundColor Cyan
Write-Host "  Compiling Adminova Native Windows Control Center  " -ForegroundColor Cyan
Write-Host "====================================================" -ForegroundColor Cyan
Write-Host " Source: $SourceFile" -ForegroundColor White
Write-Host " Output: $OutputFile" -ForegroundColor White
Write-Host " Icon:   $IconFile" -ForegroundColor White
Write-Host ""

if (-not (Test-Path $SourceFile)) {
    Write-Host "[ERROR] Source file not found: $SourceFile" -ForegroundColor Red
    exit 1
}

$CscCandidates = @(
    "$env:WINDIR\Microsoft.NET\Framework64\v4.0.30319\csc.exe",
    "$env:WINDIR\Microsoft.NET\Framework\v4.0.30319\csc.exe"
)

$CscExe = $null
foreach ($path in $CscCandidates) {
    if (Test-Path $path) {
        $CscExe = $path
        break
    }
}

if (-not $CscExe) {
    $whereCsc = where.exe csc 2>$null
    if ($whereCsc) {
        $CscExe = $whereCsc[0]
    }
}

if (-not $CscExe) {
    Write-Host "[ERROR] csc.exe compiler not found!" -ForegroundColor Red
    exit 1
}

Write-Host "[INFO] Using C# Compiler: $CscExe" -ForegroundColor Yellow

$Arguments = @(
    "/target:winexe",
    "/optimize+",
    "/platform:anycpu"
)

if (Test-Path $IconFile) {
    $Arguments += "/win32icon:$IconFile"
}

$Arguments += "/out:$OutputFile"
$Arguments += "/r:System.dll,System.Windows.Forms.dll,System.Drawing.dll"
$Arguments += $SourceFile

Write-Host "[INFO] Compiling executable..." -ForegroundColor Yellow
& $CscExe $Arguments

if ($LASTEXITCODE -eq 0 -and (Test-Path $OutputFile)) {
    Write-Host ""
    Write-Host "[OK] Adminova-Control-Center.exe compiled successfully!" -ForegroundColor Green
    Write-Host " Output: $OutputFile" -ForegroundColor Green
    Write-Host " Size  : $((Get-Item $OutputFile).Length) bytes" -ForegroundColor Green
    Write-Host "====================================================" -ForegroundColor Cyan
    exit 0
} else {
    Write-Host "[ERROR] Compilation failed with exit code $LASTEXITCODE!" -ForegroundColor Red
    exit 1
}
