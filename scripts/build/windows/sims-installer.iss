; ==============================================================================
; SIMS School Management System - Professional Windows Installer (Inno Setup)
; Features: One-Time Token Validation, Hardware UUID Binding, Service Auto-Boot,
; and Post-Install Self-Destruct.
; ==============================================================================

#define MyAppName "SIMS School Management System"
#ifndef MyAppVersion
  #define MyAppVersion "2.5.2"
#endif
#define MyAppPublisher "Adminova Tech"
#define MyAppURL "https://sims.local"
#define MyAppExeName "sims.bat"

[Setup]
AppId={{D37F28A1-4B9E-4811-9F22-A8E2104BC99E}
AppName={#MyAppName}
AppVersion={#MyAppVersion}
AppPublisher={#MyAppPublisher}
AppPublisherURL={#MyAppURL}
AppSupportURL={#MyAppURL}
AppUpdatesURL={#MyAppURL}
DefaultDirName={autopf}\SIMS
DefaultGroupName={#MyAppName}
DisableProgramGroupPage=yes
LicenseFile=
OutputDir=..\..\..\dist\installer
OutputBaseFilename=SIMS-Installer-v{#MyAppVersion}
Compression=lzma2/max
SolidCompression=yes
WizardStyle=modern
PrivilegesRequired=admin
ArchitecturesInstallIn64BitMode=x64compatible
SetupIconFile=app.ico
UninstallDisplayIcon={app}\resources\icons\adminova.ico
CloseApplications=yes

[Languages]
Name: "english"; MessagesFile: "compiler:Default.isl"

[Tasks]
Name: "desktopicon"; Description: "Create Desktop Shortcuts for Adminova Control Center and School Portal"; GroupDescription: "{cm:AdditionalIcons}"

[Dirs]
Name: "{app}"; Permissions: users-modify authusers-modify
Name: "{app}\sims-app"; Permissions: users-modify authusers-modify
Name: "{app}\sims-app\storage"; Permissions: users-modify authusers-modify
Name: "{app}\sims-app\storage\logs"; Permissions: users-modify authusers-modify
Name: "{app}\sims-app\storage\framework"; Permissions: users-modify authusers-modify
Name: "{app}\sims-app\storage\framework\views"; Permissions: users-modify authusers-modify
Name: "{app}\sims-app\storage\framework\sessions"; Permissions: users-modify authusers-modify
Name: "{app}\sims-app\storage\framework\cache"; Permissions: users-modify authusers-modify
Name: "{app}\sims-app\storage\framework\cache\data"; Permissions: users-modify authusers-modify
Name: "{app}\sims-app\storage\caddy"; Permissions: users-modify authusers-modify
Name: "{app}\sims-app\bootstrap\cache"; Permissions: users-modify authusers-modify
Name: "{app}\sims-app\database"; Permissions: users-modify authusers-modify

[Files]
; Standalone Portable Runtime
Source: "..\..\..\runtime\*"; DestDir: "{app}\runtime"; Flags: ignoreversion recursesubdirs createallsubdirs
; Operational Scripts
Source: "..\..\..\scripts\windows\*"; DestDir: "{app}\scripts\windows"; Flags: ignoreversion recursesubdirs createallsubdirs
; Branding and Icons
Source: "..\..\..\resources\icons\*"; DestDir: "{app}\resources\icons"; Flags: ignoreversion recursesubdirs createallsubdirs
; Root Launcher scripts
Source: "..\..\..\install.bat"; DestDir: "{app}"; Flags: ignoreversion
Source: "..\..\..\sims.bat"; DestDir: "{app}"; Flags: ignoreversion
; Universal CLI Dispatcher Shim in System32 (guarantees global "sims" command from any terminal)
Source: "..\..\..\sims.bat"; DestDir: "{sys}"; DestName: "sims.bat"; Flags: ignoreversion
Source: "..\..\..\Adminova-Control-Center.exe"; DestDir: "{app}"; Flags: ignoreversion skipifsourcedoesntexist
Source: "..\..\..\manifest.json"; DestDir: "{app}"; Flags: ignoreversion skipifsourcedoesntexist
Source: "..\..\..\control-center.bat"; DestDir: "{app}"; Flags: ignoreversion
Source: "..\..\..\register-path.bat"; DestDir: "{app}"; Flags: ignoreversion skipifsourcedoesntexist
; Environment configuration templates
Source: "..\..\..\sims-app\.env.example"; DestDir: "{app}\sims-app"; DestName: ".env.example"; Flags: ignoreversion
Source: "..\..\..\sims-app\.env.example"; DestDir: "{app}\sims-app"; DestName: ".env"; Flags: onlyifdoesntexist
; Application Source
Source: "..\..\..\sims-app\*"; DestDir: "{app}\sims-app"; Flags: ignoreversion recursesubdirs createallsubdirs; Excludes: ".env, .env.backup*, .env.local, .env.testing, database\database.sqlite*, storage\logs\*, storage\caddy\*, tests\*, node_modules\*"

[Registry]
Root: HKLM; Subkey: "SYSTEM\CurrentControlSet\Control\Session Manager\Environment"; ValueType: expandsz; ValueName: "SIMS_HOME"; ValueData: "{app}"; Flags: uninsdeletevalue
Root: HKLM; Subkey: "SOFTWARE\Adminova\SIMS"; ValueType: string; ValueName: "InstallPath"; ValueData: "{app}"; Flags: uninsdeletekey
Root: HKCU; Subkey: "Environment"; ValueType: expandsz; ValueName: "SIMS_HOME"; ValueData: "{app}"; Flags: uninsdeletevalue
Root: HKCU; Subkey: "SOFTWARE\Adminova\SIMS"; ValueType: string; ValueName: "InstallPath"; ValueData: "{app}"; Flags: uninsdeletekey

[Icons]
Name: "{group}\Adminova Control Center"; Filename: "{app}\Adminova-Control-Center.exe"; IconFilename: "{app}\resources\icons\adminova.ico"
Name: "{group}\Adminova School Portal"; Filename: "https://localhost"; IconFilename: "{app}\resources\icons\adminova.ico"
Name: "{group}\Uninstall Adminova"; Filename: "{uninstallexe}"
Name: "{autodesktop}\Adminova Control Center"; Filename: "{app}\Adminova-Control-Center.exe"; IconFilename: "{app}\resources\icons\adminova.ico"; Tasks: desktopicon
Name: "{autodesktop}\Adminova School Portal"; Filename: "https://localhost"; IconFilename: "{app}\resources\icons\adminova.ico"; Tasks: desktopicon

[Run]
; Run automated initial installation, firewall configuration, service registration, and path setup
Filename: "{app}\install.bat"; Parameters: "--unattended"; StatusMsg: "Configuring database, background services, firewall, and SSL certificates..."; Flags: runhidden waituntilterminated
Filename: "{app}\Adminova-Control-Center.exe"; Description: "Launch Adminova Control Center"; Flags: postinstall nowait; Check: FileExists(ExpandConstant('{app}\Adminova-Control-Center.exe'))
Filename: "https://localhost"; Description: "Open SIMS in web browser"; Flags: postinstall shellexec nowait

[InstallDelete]
; Purge stale legacy shims so the new universal dispatcher is always installed clean
Type: files; Name: "{sys}\sims.bat"
Type: files; Name: "{localappdata}\Microsoft\WindowsApps\sims.bat"

[UninstallRun]
Filename: "taskkill.exe"; Parameters: "/F /T /IM frankenphp.exe /IM php-cgi.exe /IM php.exe /IM Adminova-Control-Center.exe"; Flags: runhidden
Filename: "schtasks.exe"; Parameters: "/end /tn ""SIMS-Web"""; Flags: runhidden
Filename: "schtasks.exe"; Parameters: "/end /tn ""SIMS-Queue"""; Flags: runhidden
Filename: "schtasks.exe"; Parameters: "/end /tn ""SIMS-Scheduler"""; Flags: runhidden
Filename: "schtasks.exe"; Parameters: "/delete /tn ""SIMS-Web"" /f"; Flags: runhidden
Filename: "schtasks.exe"; Parameters: "/delete /tn ""SIMS-Queue"" /f"; Flags: runhidden
Filename: "schtasks.exe"; Parameters: "/delete /tn ""SIMS-Scheduler"" /f"; Flags: runhidden
Filename: "netsh.exe"; Parameters: "advfirewall firewall delete rule name=""SIMS-Web-HTTP"""; Flags: runhidden
Filename: "netsh.exe"; Parameters: "advfirewall firewall delete rule name=""SIMS-Web-HTTPS"""; Flags: runhidden
Filename: "netsh.exe"; Parameters: "advfirewall firewall delete rule name=""SIMS-Web-Alt"""; Flags: runhidden
Filename: "netsh.exe"; Parameters: "advfirewall firewall delete rule name=""SIMS-mDNS"""; Flags: runhidden
Filename: "netsh.exe"; Parameters: "advfirewall firewall delete rule name=""SIMS-LLMNR"""; Flags: runhidden
Filename: "netsh.exe"; Parameters: "advfirewall firewall delete rule name=""SIMS-NetBIOS"""; Flags: runhidden
Filename: "cmd.exe"; Parameters: "/c del /f /q ""{sys}\sims.bat"" ""{localappdata}\Microsoft\WindowsApps\sims.bat"""; Flags: runhidden

[UninstallDelete]
Type: filesandordirs; Name: "{app}\sims-app\storage\caddy"
Type: filesandordirs; Name: "{app}\sims-app\storage\logs"
Type: filesandordirs; Name: "{app}\sims-app\storage\framework"
Type: filesandordirs; Name: "{app}\sims-app\bootstrap\cache"

[Code]
var
  TokenPage: TInputQueryWizardPage;
  UserToken: String;
  StatusLabel: TLabel;
  ProgressBar: TNewProgressBar;

// 0. Pre-installation cleanup to prevent locked file errors
function InitializeSetup(): Boolean;
var
  ResultCode: Integer;
begin
  Exec('taskkill.exe', '/F /T /IM frankenphp.exe /IM php-cgi.exe /IM php.exe /IM Adminova-Control-Center.exe', '', SW_HIDE, ewWaitUntilTerminated, ResultCode);
  Result := True;
end;

// 1. Create Custom Page for One-Time Installation Token with Loading UI
procedure InitializeWizard;
begin
  TokenPage := CreateInputQueryPage(
    wpWelcome,
    'SIMS Installation Authorization',
    'Enter your One-Time Installation Token',
    'Please enter the authorization token provided to your school (e.g. SIMS-TOK-SCHOOL-XXXX):'
  );
  TokenPage.Add('One-Time Token:', False);

  // Status message for token verification loading state
  StatusLabel := TLabel.Create(WizardForm);
  StatusLabel.Parent := TokenPage.Surface;
  StatusLabel.Left := ScaleX(0);
  StatusLabel.Top := ScaleY(115);
  StatusLabel.Width := TokenPage.SurfaceWidth;
  StatusLabel.Height := ScaleY(35);
  StatusLabel.AutoSize := False;
  StatusLabel.WordWrap := True;
  StatusLabel.Font.Style := [fsBold];
  StatusLabel.Font.Color := clNavy;
  StatusLabel.Caption := '🔐 Verifying token with Adminova licensing server... Please wait...';
  StatusLabel.Visible := False;

  // Animated Marquee Progress Bar
  ProgressBar := TNewProgressBar.Create(WizardForm);
  ProgressBar.Parent := TokenPage.Surface;
  ProgressBar.Left := ScaleX(0);
  ProgressBar.Top := ScaleY(155);
  ProgressBar.Width := TokenPage.SurfaceWidth;
  ProgressBar.Height := ScaleY(18);
  ProgressBar.Style := npbstMarquee;
  ProgressBar.Visible := False;
end;

// 2. Validate Token with Firebase Firestore REST API on Next Click
function NextButtonClick(CurPageID: Integer): Boolean;
var
  ResultCode: Integer;
  VerifyScript: String;
  TempScriptFile: String;
  TempOutputFile: String;
  AuthStatus: AnsiString;
begin
  Result := True;

  if CurPageID = TokenPage.ID then
  begin
    UserToken := Trim(TokenPage.Values[0]);
    if Length(UserToken) < 5 then
    begin
      MsgBox('Please enter a valid One-Time Installation Token.', mbError, MB_OK);
      Result := False;
      Exit;
    end;

    // Verify token using PowerShell calling Firebase Firestore REST API
    TempScriptFile := ExpandConstant('{tmp}\verify_token.ps1');
    TempOutputFile := ExpandConstant('{tmp}\token_result.txt');

    // Clean up any stale files from previous attempts
    DeleteFile(TempScriptFile);
    DeleteFile(TempOutputFile);

    // Show loading state, animated progress bar, hourglass cursor & disable buttons
    StatusLabel.Caption := '🔐 Connecting to Adminova Authorization Cloud... Verifying token & hardware binding...';
    StatusLabel.Font.Color := clNavy;
    StatusLabel.Visible := True;
    ProgressBar.Visible := True;
    WizardForm.Cursor := crHourGlass;
    WizardForm.NextButton.Enabled := False;
    WizardForm.BackButton.Enabled := False;
    WizardForm.CancelButton.Enabled := False;
    WizardForm.Refresh;

    VerifyScript := 
      '$token = "' + UserToken + '";' + #13#10 +
      '$url = "https://firestore.googleapis.com/v1/projects/sims-licensing/databases/(default)/documents/install_tokens/" + $token;' + #13#10 +
      '$outputFile = "' + TempOutputFile + '";' + #13#10 +
      'try {' + #13#10 +
      '  # 1. Fetch token record from Firestore' + #13#10 +
      '  $doc = Invoke-RestMethod -Uri $url -Method Get -TimeoutSec 10;' + #13#10 +
      '  $status = if ($doc.fields.status -and $doc.fields.status.stringValue) { $doc.fields.status.stringValue.Trim().ToLower() } else { "" };' + #13#10 +
      '  $boundHw = if ($doc.fields.bound_machine_uuid -and $doc.fields.bound_machine_uuid.stringValue) { $doc.fields.bound_machine_uuid.stringValue.Trim().ToUpper() } else { "" };' + #13#10 +
      '' + #13#10 +
      '  # 2. Derive Resilient Hardware Fingerprint' + #13#10 +
      '  $hwUuid = (Get-CimInstance Win32_ComputerSystemProduct -ErrorAction SilentlyContinue).UUID;' + #13#10 +
      '  $cpuId  = (Get-CimInstance Win32_Processor -ErrorAction SilentlyContinue | Select-Object -First 1).ProcessorId;' + #13#10 +
      '  $diskId = (Get-CimInstance Win32_DiskDrive -ErrorAction SilentlyContinue | Select-Object -First 1).SerialNumber;' + #13#10 +
      '  $board  = (Get-CimInstance Win32_BaseBoard -ErrorAction SilentlyContinue).SerialNumber;' + #13#10 +
      '' + #13#10 +
      '  # Guard against null or generic clone/VM dummy UUIDs' + #13#10 +
      '  $isGeneric = [string]::IsNullOrWhiteSpace($hwUuid) -or ($hwUuid -match "^(0{8}-0{4}-0{4}-0{4}-0{12}|F{8}-F{4}-F{4}-F{4}-F{12}|03000200-0400-0500-0006-000700080009)$");' + #13#10 +
      '  if ($isGeneric) {' + #13#10 +
      '    $rawHw = "$cpuId|$diskId|$board|$env:COMPUTERNAME";' + #13#10 +
      '    $sha = [System.Security.Cryptography.SHA256]::Create();' + #13#10 +
      '    $bytes = $sha.ComputeHash([System.Text.Encoding]::UTF8.GetBytes($rawHw));' + #13#10 +
      '    $hw = ([System.BitConverter]::ToString($bytes) -replace "-","").ToUpper();' + #13#10 +
      '  } else {' + #13#10 +
      '    $hw = $hwUuid.Trim().ToUpper();' + #13#10 +
      '  }' + #13#10 +
      '' + #13#10 +
      '  $hostName = $env:COMPUTERNAME;' + #13#10 +
      '  $ip = ""; $loc = ""; $isp = "";' + #13#10 +
      '  try {' + #13#10 +
      '    $geo = Invoke-RestMethod -Uri "http://ip-api.com/json/" -TimeoutSec 3;' + #13#10 +
      '    if ($geo -and $geo.query) { $ip = $geo.query; $loc = ($geo.city + ", " + $geo.country); $isp = $geo.isp; }' + #13#10 +
      '  } catch {' + #13#10 +
      '    try { $ip = (Invoke-RestMethod -Uri "https://api.ipify.org?format=json" -TimeoutSec 2).ip; } catch {}' + #13#10 +
      '  }' + #13#10 +
      '' + #13#10 +
      '  if ($doc.fields.license_key -and $doc.fields.license_key.stringValue) {' + #13#10 +
      '    Set-Content -Path "' + ExpandConstant('{tmp}\extracted_license.txt') + '" -Value $doc.fields.license_key.stringValue.Trim();' + #13#10 +
      '  }' + #13#10 +
      '' + #13#10 +
      '  # 3. Decision Matrix with Strict Non-Null Guard' + #13#10 +
      '  if ($status -eq "unused") {' + #13#10 +
      '    $nowIso = (Get-Date).ToUniversalTime().ToString("yyyy-MM-ddTHH:mm:ssZ");' + #13#10 +
      '    $patchUrl = $url + "?updateMask.fieldPaths=status&updateMask.fieldPaths=bound_machine_uuid&updateMask.fieldPaths=burned_at&updateMask.fieldPaths=hostname&updateMask.fieldPaths=public_ip&updateMask.fieldPaths=location&updateMask.fieldPaths=isp";' + #13#10 +
      '    $body = @{ fields = @{ status = @{ stringValue = "burned" }; bound_machine_uuid = @{ stringValue = $hw }; burned_at = @{ stringValue = $nowIso }; hostname = @{ stringValue = $hostName }; public_ip = @{ stringValue = $ip }; location = @{ stringValue = $loc }; isp = @{ stringValue = $isp } } } | ConvertTo-Json -Depth 4;' + #13#10 +
      '    Invoke-RestMethod -Uri $patchUrl -Method Patch -Body $body -ContentType "application/json" | Out-Null;' + #13#10 +
      '    if ($doc.fields.license_key -and $doc.fields.license_key.stringValue) {' + #13#10 +
      '      $licKey = $doc.fields.license_key.stringValue.Trim();' + #13#10 +
      '      $licPatchUrl = "https://firestore.googleapis.com/v1/projects/sims-licensing/databases/(default)/documents/licenses/" + $licKey + "?updateMask.fieldPaths=bound_machine_uuid&updateMask.fieldPaths=hostname&updateMask.fieldPaths=public_ip&updateMask.fieldPaths=location&updateMask.fieldPaths=isp&updateMask.fieldPaths=last_active_at&updateMask.fieldPaths=telemetry";' + #13#10 +
      '      $licBody = @{ fields = @{ bound_machine_uuid = @{ stringValue = $hw }; hostname = @{ stringValue = $hostName }; public_ip = @{ stringValue = $ip }; location = @{ stringValue = $loc }; isp = @{ stringValue = $isp }; last_active_at = @{ stringValue = $nowIso }; telemetry = @{ mapValue = @{ fields = @{ bound_machine_uuid = @{ stringValue = $hw }; hostname = @{ stringValue = $hostName }; public_ip = @{ stringValue = $ip }; location = @{ stringValue = $loc }; isp = @{ stringValue = $isp }; last_active_at = @{ stringValue = $nowIso } } } } } } | ConvertTo-Json -Depth 6;' + #13#10 +
      '      try { Invoke-RestMethod -Uri $licPatchUrl -Method Patch -Body $licBody -ContentType "application/json" | Out-Null; } catch {}' + #13#10 +
      '    }' + #13#10 +
      '    if ($doc.fields.school_id -and $doc.fields.school_id.stringValue) {' + #13#10 +
      '      $schId = $doc.fields.school_id.stringValue.Trim();' + #13#10 +
      '      $schPatchUrl = "https://firestore.googleapis.com/v1/projects/sims-licensing/databases/(default)/documents/schools/" + $schId + "?updateMask.fieldPaths=token.status&updateMask.fieldPaths=token.burned_at&updateMask.fieldPaths=telemetry.bound_machine_uuid&updateMask.fieldPaths=telemetry.hostname&updateMask.fieldPaths=telemetry.public_ip&updateMask.fieldPaths=telemetry.location&updateMask.fieldPaths=telemetry.isp&updateMask.fieldPaths=telemetry.last_active_at&updateMask.fieldPaths=updated_at";' + #13#10 +
      '      $schBody = @{ fields = @{ token = @{ mapValue = @{ fields = @{ token_string = @{ stringValue = $token }; status = @{ stringValue = "burned" }; burned_at = @{ stringValue = $nowIso } } } }; telemetry = @{ mapValue = @{ fields = @{ bound_machine_uuid = @{ stringValue = $hw }; hostname = @{ stringValue = $hostName }; public_ip = @{ stringValue = $ip }; location = @{ stringValue = $loc }; isp = @{ stringValue = $isp }; last_active_at = @{ stringValue = $nowIso } } } }; updated_at = @{ stringValue = $nowIso } } } | ConvertTo-Json -Depth 6;' + #13#10 +
      '      try { Invoke-RestMethod -Uri $schPatchUrl -Method Patch -Body $schBody -ContentType "application/json" | Out-Null; } catch {}' + #13#10 +
      '    }' + #13#10 +
      '    Set-Content -Path $outputFile -Value "AUTHORIZED";' + #13#10 +
      '  } elseif ($status -eq "burned" -or $status -eq "used") {' + #13#10 +
      '    # Token already burned! ONLY allow if bound to THIS exact hardware fingerprint' + #13#10 +
      '    if (![string]::IsNullOrWhiteSpace($boundHw) -and ![string]::IsNullOrWhiteSpace($hw) -and ($boundHw -eq $hw)) {' + #13#10 +
      '      Set-Content -Path $outputFile -Value "AUTHORIZED";' + #13#10 +
      '    } else {' + #13#10 +
      '      Set-Content -Path $outputFile -Value "BURNED";' + #13#10 +
      '    }' + #13#10 +
      '  } else {' + #13#10 +
      '    Set-Content -Path $outputFile -Value "INVALID";' + #13#10 +
      '  }' + #13#10 +
      '} catch {' + #13#10 +
      '  Set-Content -Path $outputFile -Value "INVALID";' + #13#10 +
      '}';

    SaveStringToFile(TempScriptFile, VerifyScript, False);

    Exec('powershell.exe', '-NoProfile -ExecutionPolicy Bypass -File "' + TempScriptFile + '"', '', SW_HIDE, ewWaitUntilTerminated, ResultCode);

    // Restore UI cursor, progress bar and navigation buttons
    WizardForm.Cursor := crDefault;
    WizardForm.NextButton.Enabled := True;
    WizardForm.BackButton.Enabled := True;
    WizardForm.CancelButton.Enabled := True;
    ProgressBar.Visible := False;

    if LoadStringFromFile(TempOutputFile, AuthStatus) then
    begin
      AuthStatus := Trim(AuthStatus);
      if AuthStatus = 'AUTHORIZED' then
      begin
        StatusLabel.Caption := '✅ Authorization verified successfully. Proceeding...';
        StatusLabel.Font.Color := clGreen;
        StatusLabel.Visible := True;
        Result := True;
      end
      else if AuthStatus = 'BURNED' then
      begin
        StatusLabel.Caption := '❌ Token already consumed on another computer.';
        StatusLabel.Font.Color := clRed;
        StatusLabel.Visible := True;
        MsgBox('Installation Blocked: This token has already been consumed on another computer.' + #13#10 + #13#10 + 'Each token is valid for 1 installation only. Please contact support.', mbCriticalError, MB_OK);
        Result := False;
      end
      else
      begin
        StatusLabel.Caption := '❌ Invalid token or connection error.';
        StatusLabel.Font.Color := clRed;
        StatusLabel.Visible := True;
        MsgBox('Invalid Token: Token not found or unable to connect to authorization server.' + #13#10 + 'Please check your internet connection or verify your token.', mbError, MB_OK);
        Result := False;
      end;
    end
    else
    begin
      StatusLabel.Caption := '❌ Authorization server unreachable.';
      StatusLabel.Font.Color := clRed;
      StatusLabel.Visible := True;
      MsgBox('Could not complete authorization check. Please ensure internet access is active.', mbError, MB_OK);
      Result := False;
    end;
  end;
end;

// 3. Post-Install Automatic License Injection into .env
procedure CurStepChanged(CurStep: TSetupStep);
var
  ExtractedKeyFile: String;
  ExtractedKey: AnsiString;
  EnvPath: String;
  EnvContent: AnsiString;
  EnvStr: String;
  KeyStr: String;
begin
  if CurStep = ssPostInstall then
  begin
    ExtractedKeyFile := ExpandConstant('{tmp}\extracted_license.txt');
    if LoadStringFromFile(ExtractedKeyFile, ExtractedKey) then
    begin
      KeyStr := Trim(String(ExtractedKey));
      if Length(KeyStr) > 0 then
      begin
        EnvPath := ExpandConstant('{app}\sims-app\.env');
        if FileExists(EnvPath) then
        begin
          if LoadStringFromFile(EnvPath, EnvContent) then
          begin
            EnvStr := String(EnvContent);
            StringChange(EnvStr, 'LICENSE_KEY=', 'LICENSE_KEY=' + KeyStr);
            SaveStringToFile(EnvPath, AnsiString(EnvStr), False);
          end;
        end;
      end;
    end;
  end;
end;

// 4. Post-Install Self-Destruct Routine (Deletes installer .exe from disk on close)
procedure DeinitializeSetup();
var
  SelfDeleteBat: String;
  BatPath: String;
  ResultCode: Integer;
begin
  BatPath := ExpandConstant('{tmp}\cleanup_setup.bat');
  SelfDeleteBat := 
    '@echo off' + #13#10 +
    ':REPEAT' + #13#10 +
    'timeout /t 2 /nobreak >nul' + #13#10 +
    'del /f /q "' + ExpandConstant('{srcexe}') + '" >nul 2>&1' + #13#10 +
    'if exist "' + ExpandConstant('{srcexe}') + '" goto :REPEAT' + #13#10 +
    'del /f /q "%~f0" >nul 2>&1' + #13#10;
  
  SaveStringToFile(BatPath, SelfDeleteBat, False);
  Exec('cmd.exe', '/c "' + BatPath + '"', '', SW_HIDE, ewNoWait, ResultCode);
end;
