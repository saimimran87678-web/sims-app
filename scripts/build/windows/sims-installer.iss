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

// 0. Pre-installation cleanup to prevent locked file errors
function InitializeSetup(): Boolean;
var
  ResultCode: Integer;
begin
  Exec('taskkill.exe', '/F /T /IM frankenphp.exe /IM php-cgi.exe /IM php.exe /IM Adminova-Control-Center.exe', '', SW_HIDE, ewWaitUntilTerminated, ResultCode);
  Result := True;
end;

// 1. Create Custom Page for One-Time Installation Token
procedure InitializeWizard;
begin
  TokenPage := CreateInputQueryPage(
    wpWelcome,
    'SIMS Installation Authorization',
    'Enter your One-Time Installation Token',
    'Please enter the authorization token provided to your school (e.g. SIMS-TOK-SCHOOL-XXXX):'
  );
  TokenPage.Add('One-Time Token:', False);
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

    VerifyScript := 
      '$token = "' + UserToken + '";' + #13#10 +
      '$url = "https://firestore.googleapis.com/v1/projects/sims-licensing/databases/(default)/documents/install_tokens/" + $token;' + #13#10 +
      'try {' + #13#10 +
      '  $doc = Invoke-RestMethod -Uri $url -Method Get -TimeoutSec 10;' + #13#10 +
      '  $status = $doc.fields.status.stringValue;' + #13#10 +
      '  $hw = (Get-CimInstance Win32_ComputerSystemProduct).UUID;' + #13#10 +
      '  if ($doc.fields.license_key -and $doc.fields.license_key.stringValue) {' + #13#10 +
      '    Set-Content -Path "' + ExpandConstant('{tmp}\extracted_license.txt') + '" -Value $doc.fields.license_key.stringValue;' + #13#10 +
      '  }' + #13#10 +
      '  if ($status -eq "unused") {' + #13#10 +
      '    $patchUrl = $url + "?updateMask.fieldPaths=status&updateMask.fieldPaths=bound_machine_uuid&updateMask.fieldPaths=burned_at";' + #13#10 +
      '    $body = @{ fields = @{ status = @{ stringValue = "burned" }; bound_machine_uuid = @{ stringValue = $hw }; burned_at = @{ stringValue = (Get-Date).ToUniversalTime().ToString("yyyy-MM-ddTHH:mm:ssZ") } } } | ConvertTo-Json -Depth 4;' + #13#10 +
      '    Invoke-RestMethod -Uri $patchUrl -Method Patch -Body $body -ContentType "application/json" | Out-Null;' + #13#10 +
      '    Set-Content -Path "' + TempOutputFile + '" -Value "AUTHORIZED";' + #13#10 +
      '  } elseif ($status -eq "burned" -and $doc.fields.bound_machine_uuid.stringValue -eq $hw) {' + #13#10 +
      '    Set-Content -Path "' + TempOutputFile + '" -Value "AUTHORIZED";' + #13#10 +
      '  } else {' + #13#10 +
      '    Set-Content -Path "' + TempOutputFile + '" -Value "BURNED";' + #13#10 +
      '  }' + #13#10 +
      '} catch {' + #13#10 +
      '  Set-Content -Path "' + TempOutputFile + '" -Value "INVALID";' + #13#10 +
      '}';

    SaveStringToFile(TempScriptFile, VerifyScript, False);

    Exec('powershell.exe', '-NoProfile -ExecutionPolicy Bypass -File "' + TempScriptFile + '"', '', SW_HIDE, ewWaitUntilTerminated, ResultCode);

    if LoadStringFromFile(TempOutputFile, AuthStatus) then
    begin
      AuthStatus := Trim(AuthStatus);
      if AuthStatus = 'AUTHORIZED' then
      begin
        Result := True;
      end
      else if AuthStatus = 'BURNED' then
      begin
        MsgBox('❌ Installation Blocked: This token has already been consumed on another computer.' + #13#10 + #13#10 + 'Each token is valid for 1 installation only. Please contact support.', mbCriticalError, MB_OK);
        Result := False;
      end
      else
      begin
        MsgBox('❌ Invalid Token: Token not found or unable to connect to authorization server.' + #13#10 + 'Please check your internet connection or verify your token.', mbError, MB_OK);
        Result := False;
      end;
    end
    else
    begin
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
            StringChange(EnvContent, 'LICENSE_KEY=', 'LICENSE_KEY=' + KeyStr);
            SaveStringToFile(EnvPath, EnvContent, False);
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
