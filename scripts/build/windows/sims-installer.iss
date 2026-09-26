; ==============================================================================
; SIMS School Management System - Professional Windows Installer (Inno Setup)
; Features: One-Time Token Validation, Hardware UUID Binding, Service Auto-Boot,
; and Post-Install Self-Destruct.
; ==============================================================================

#define MyAppName "SIMS School Management System"
#define MyAppVersion "2.5.1"
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

[Languages]
Name: "english"; MessagesFile: "compiler:Default.isl"

[Tasks]
Name: "desktopicon"; Description: "Create Desktop Shortcuts for Adminova Control Center and School Portal"; GroupDescription: "{cm:AdditionalIcons}"

[Dirs]
Name: "{app}\sims-app\storage"; Permissions: users-modify authusers-modify
Name: "{app}\sims-app\storage\logs"; Permissions: users-modify authusers-modify
Name: "{app}\sims-app\storage\framework"; Permissions: users-modify authusers-modify
Name: "{app}\sims-app\storage\framework\views"; Permissions: users-modify authusers-modify
Name: "{app}\sims-app\storage\framework\sessions"; Permissions: users-modify authusers-modify
Name: "{app}\sims-app\storage\framework\cache"; Permissions: users-modify authusers-modify
Name: "{app}\sims-app\storage\framework\cache\data"; Permissions: users-modify authusers-modify
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
Source: "..\..\..\Adminova-Control-Center.exe"; DestDir: "{app}"; Flags: ignoreversion
Source: "..\..\..\manifest.json"; DestDir: "{app}"; Flags: ignoreversion skipifsourcedoesntexist
Source: "..\..\..\control-center.bat"; DestDir: "{app}"; Flags: ignoreversion
; Application Source
Source: "..\..\..\sims-app\*"; DestDir: "{app}\sims-app"; Flags: ignoreversion recursesubdirs createallsubdirs; Excludes: ".env, .env.*, database\database.sqlite*, storage\logs\*, storage\caddy\*, tests\*, node_modules\*"

[Icons]
Name: "{group}\Adminova Control Center"; Filename: "{app}\Adminova-Control-Center.exe"; IconFilename: "{app}\resources\icons\adminova.ico"
Name: "{group}\Adminova School Portal"; Filename: "https://localhost"; IconFilename: "{app}\resources\icons\adminova.ico"
Name: "{group}\Uninstall Adminova"; Filename: "{uninstallexe}"
Name: "{autodesktop}\Adminova Control Center"; Filename: "{app}\Adminova-Control-Center.exe"; IconFilename: "{app}\resources\icons\adminova.ico"; Tasks: desktopicon
Name: "{autodesktop}\Adminova School Portal"; Filename: "https://localhost"; IconFilename: "{app}\resources\icons\adminova.ico"; Tasks: desktopicon

[Run]
; Run automated initial installation and service registration in unattended mode
Filename: "{app}\install.bat"; Parameters: "--unattended"; StatusMsg: "Configuring database, background services, and SSL certificates..."; Flags: runhidden waituntilterminated
Filename: "{app}\Adminova-Control-Center.exe"; Description: "Launch Adminova Control Center"; Flags: postinstall nowait
Filename: "https://localhost"; Description: "Open SIMS in web browser"; Flags: postinstall shellexec nowait

[Code]
var
  TokenPage: TInputQueryWizardPage;
  UserToken: String;

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
      '  if ($status -eq "unused") {' + #13#10 +
      '    $hw = (Get-CimInstance Win32_ComputerSystemProduct).UUID;' + #13#10 +
      '    $patchUrl = $url + "?updateMask.fieldPaths=status&updateMask.fieldPaths=bound_machine_uuid&updateMask.fieldPaths=burned_at";' + #13#10 +
      '    $body = @{ fields = @{ status = @{ stringValue = "burned" }; bound_machine_uuid = @{ stringValue = $hw }; burned_at = @{ stringValue = (Get-Date).ToUniversalTime().ToString("yyyy-MM-ddTHH:mm:ssZ") } } } | ConvertTo-Json -Depth 4;' + #13#10 +
      '    Invoke-RestMethod -Uri $patchUrl -Method Patch -Body $body -ContentType "application/json" | Out-Null;' + #13#10 +
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

// 3. Post-Install Self-Destruct Routine (Deletes installer .exe from disk on close)
procedure DeinitializeSetup();
var
  SelfDeleteBat: String;
  BatPath: String;
  ResultCode: Integer;
begin
  // Only self-destruct if installation was successful
  if WizardIsTaskSelected('desktopicon') or True then
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
end;
